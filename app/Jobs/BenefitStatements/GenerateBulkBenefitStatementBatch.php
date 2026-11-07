<?php

namespace App\Jobs\BenefitStatements;

use App\Services\PensionsAdministration\BenefitStatements\BenefitStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Throwable;

class GenerateBulkBenefitStatementBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 1;

    public function __construct(
        public string $runId,
        public int $batchNumber
    ) {
        $this->onQueue('benefit-statements');
    }

    public function handle(BenefitStatementService $service): void
    {
        $run = DB::table('benefit_statement_bulk_runs')
            ->where('id', $this->runId)
            ->first();

        if (!$run || $run->status !== 'processing') {
            return;
        }

        $disk = Storage::disk('local');

        $folder = 'benefit-statements/bulk/' . $this->runId;
        $number = sprintf('%06d', $this->batchNumber);

        $batchRelative = $folder . '/batch_' . $number . '.pdf';
        $manifestRelative = $folder . '/ids_' . $number . '.json';

        try {
            if (!$disk->exists($manifestRelative)) {
                throw new RuntimeException(
                    'Member manifest not found for batch ' . $this->batchNumber . '.'
                );
            }

            if (!$disk->exists($folder)) {
                $disk->makeDirectory($folder);
            }

            $ids = json_decode(
                $disk->get($manifestRelative),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($ids) || empty($ids)) {
                throw new RuntimeException(
                    'No members found in batch ' . $this->batchNumber . '.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Check for Previously Generated Batch
            |--------------------------------------------------------------------------
            */

            if ($disk->exists($batchRelative)) {
                return;
            }

            $date = Carbon::parse($run->statement_date)->endOfDay();

            $merged = new Fpdi('L', 'mm', 'A3');
            $merged->SetAutoPageBreak(false);

            $done = 0;

            /*
            |--------------------------------------------------------------------------
            | Generate Statements
            |--------------------------------------------------------------------------
            |
            | Each member's PDF is generated in memory.
            |
            | This avoids repeatedly writing and reopening the same
            | temporary PDF file, which can cause file-locking errors
            | on Windows.
            |
            */

            foreach ($ids as $id) {
                $memberId = (int) $id;

                $statement = $service->generate(
                    $memberId,
                    $date->copy()
                );

                /*
                |--------------------------------------------------------------------------
                | Verify Employer
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $statement['employment']['employer_id']
                    !== (int) $run->employer_id
                ) {
                    throw new RuntimeException(
                        'Employment mismatch for member ' . $memberId . '.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Generate Individual A3 Statement
                |--------------------------------------------------------------------------
                */

                $pdf = Pdf::loadView(
                    'pensions-administration.updates.benefit-statements.pdf',
                    compact('statement')
                )
                    ->setPaper('a3', 'landscape')
                    ->setOptions([
                        'isRemoteEnabled' => false,
                        'defaultFont' => 'DejaVu Sans',
                    ]);

                $pdfContent = $pdf->output();

                unset($pdf);

                /*
                |--------------------------------------------------------------------------
                | Import PDF Directly from Memory
                |--------------------------------------------------------------------------
                */

                $stream = StreamReader::createByString($pdfContent);

                $pages = $merged->setSourceFile($stream);

                if ($pages !== 2) {
                    throw new RuntimeException(
                        'Member ' . $memberId
                        . ' produced ' . $pages
                        . ' pages; expected 2.'
                    );
                }

                for ($page = 1; $page <= $pages; $page++) {
                    $template = $merged->importPage($page);

                    $size = $merged->getTemplateSize($template);

                    $merged->AddPage(
                        $size['orientation'],
                        [$size['width'], $size['height']]
                    );

                    $merged->useTemplate(
                        $template,
                        0,
                        0,
                        $size['width'],
                        $size['height']
                    );
                }

                unset($pdfContent, $stream, $statement);

                $done++;
            }

            /*
            |--------------------------------------------------------------------------
            | Save Completed Batch
            |--------------------------------------------------------------------------
            |
            | Save through Laravel Storage rather than repeatedly
            | opening a temporary file.
            |
            */

            $batchPdf = $merged->Output('S');

            if (!$disk->put($batchRelative, $batchPdf)) {
                throw new RuntimeException(
                    'Unable to save PDF for batch ' . $this->batchNumber . '.'
                );
            }

            unset($batchPdf, $merged);

            /*
            |--------------------------------------------------------------------------
            | Update Progress and Completed Batches
            |--------------------------------------------------------------------------
            */

            $finalise = false;

            DB::transaction(function () use ($done, &$finalise): void {
                $locked = DB::table('benefit_statement_bulk_runs')
                    ->where('id', $this->runId)
                    ->lockForUpdate()
                    ->first();

                if (!$locked || $locked->status !== 'processing') {
                    return;
                }

                $processed = (int) $locked->processed_members + $done;
                $completed = (int) $locked->completed_batches + 1;

                $finalise = $completed === (int) $locked->total_batches;

                DB::table('benefit_statement_bulk_runs')
                    ->where('id', $this->runId)
                    ->update([
                        'processed_members' => $processed,
                        'completed_batches' => $completed,
                        'status' => $finalise ? 'merging' : 'processing',
                        'updated_at' => now(),
                    ]);
            });

            /*
            |--------------------------------------------------------------------------
            | Merge All Completed Batches
            |--------------------------------------------------------------------------
            */

            if ($finalise) {
                MergeBulkBenefitStatements::dispatch($this->runId);
            }

        } catch (Throwable $e) {
            DB::table('benefit_statement_bulk_runs')
                ->where('id', $this->runId)
                ->where('status', 'processing')
                ->update([
                    'status' => 'failed',
                    'failed_members' => DB::raw('failed_members + 1'),
                    'error_message' => mb_substr(
                        'Batch ' . $this->batchNumber . ': ' . $e->getMessage(),
                        0,
                        2000
                    ),
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }
}