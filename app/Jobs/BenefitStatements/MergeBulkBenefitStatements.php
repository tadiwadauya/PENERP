<?php

namespace App\Jobs\BenefitStatements;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Throwable;

class MergeBulkBenefitStatements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(public string $runId)
    {
        $this->onQueue('benefit-statements');
    }

    public function handle(): void
    {
        $run = DB::table('benefit_statement_bulk_runs')->where('id', $this->runId)->first();
        if (!$run || $run->status !== 'merging') return;

        $disk = Storage::disk('local');
        $folder = 'benefit-statements/bulk/' . $this->runId;
        $destination = $folder . '/all_statements.pdf';

        try {
            $registered = DB::table('member_benefit_statements')->where('bulk_run_id', $this->runId)->count();
            if ($registered !== (int) $run->total_members) {
                throw new RuntimeException('Expected ' . $run->total_members . ' individual member statements; found ' . $registered . '. Confirm the updated batch-generation job is installed.');
            }

            $missing = DB::table('member_benefit_statements')->where('bulk_run_id', $this->runId)
                ->pluck('pdf_path')->first(fn ($path) => !$path || !$disk->exists($path));
            if ($missing !== null) {
                throw new RuntimeException('An individual member PDF is missing: ' . $missing);
            }

            $merged = new Fpdi('L', 'mm', 'A3');
            $merged->SetAutoPageBreak(false);

            for ($i = 1; $i <= (int) $run->total_batches; $i++) {
                if (DB::table('benefit_statement_bulk_runs')->where('id', $this->runId)->value('status') !== 'merging') return;

                $file = $folder . '/batch_' . sprintf('%06d', $i) . '.pdf';
                if (!$disk->exists($file)) throw new RuntimeException('Missing PDF batch ' . $i . '.');

                $pages = $merged->setSourceFile($disk->path($file));
                for ($page = 1; $page <= $pages; $page++) {
                    $template = $merged->importPage($page);
                    $size = $merged->getTemplateSize($template);
                    $merged->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $merged->useTemplate($template, 0, 0, $size['width'], $size['height']);
                }
            }

            if (DB::table('benefit_statement_bulk_runs')->where('id', $this->runId)->value('status') !== 'merging') return;

            $merged->Output('F', $disk->path($destination));
            unset($merged);

            $completed = DB::table('benefit_statement_bulk_runs')->where('id', $this->runId)
                ->where('status', 'merging')->update([
                    'status' => 'completed',
                    'pdf_path' => $destination,
                    'updated_at' => now(),
                ]);

            if (!$completed) {
                $disk->delete($destination);
                return;
            }

            // Remove only intermediate files. Keep members/*.pdf for the Member Portal.
            for ($i = 1; $i <= (int) $run->total_batches; $i++) {
                $number = sprintf('%06d', $i);
                $disk->delete($folder . '/batch_' . $number . '.pdf');
                $disk->delete($folder . '/ids_' . $number . '.json');
            }
        } catch (Throwable $e) {
            DB::table('benefit_statement_bulk_runs')->where('id', $this->runId)
                ->where('status', 'merging')->update([
                    'status' => 'failed',
                    'error_message' => mb_substr('PDF merge: ' . $e->getMessage(), 0, 2000),
                    'updated_at' => now(),
                ]);
            throw $e;
        }
    }
}
