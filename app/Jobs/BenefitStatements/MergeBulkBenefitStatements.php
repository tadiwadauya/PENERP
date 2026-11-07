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
    public int $timeout=1800;
    public int $tries=1;
    public function __construct(public string $runId) { $this->onQueue('benefit-statements'); }

    public function handle(): void
    {
        $run=DB::table('benefit_statement_bulk_runs')->where('id',$this->runId)->first();
        if (!$run || $run->status!=='merging') return;
        $disk=Storage::disk('local');
        $folder='benefit-statements/bulk/'.$this->runId;
        $destination=$folder.'/all_statements.pdf';
        try {
            $merged=new Fpdi('L','mm','A3');
            $merged->SetAutoPageBreak(false);
            for ($i=1;$i<=(int)$run->total_batches;$i++) {
                $file=$folder.'/batch_'.sprintf('%06d',$i).'.pdf';
                if (!$disk->exists($file)) throw new RuntimeException('Missing PDF batch '.$i.'.');
                $pages=$merged->setSourceFile($disk->path($file));
                for ($page=1;$page<=$pages;$page++) {
                    $template=$merged->importPage($page);
                    $size=$merged->getTemplateSize($template);
                    $merged->AddPage($size['orientation'],[$size['width'],$size['height']]);
                    $merged->useTemplate($template,0,0,$size['width'],$size['height']);
                }
            }
            $merged->Output('F',$disk->path($destination));
            DB::table('benefit_statement_bulk_runs')->where('id',$this->runId)->update([
                'status'=>'completed','pdf_path'=>$destination,'updated_at'=>now(),
            ]);
            foreach ($disk->files($folder) as $file) if ($file!==$destination) $disk->delete($file);
        } catch (Throwable $e) {
            DB::table('benefit_statement_bulk_runs')->where('id',$this->runId)->update([
                'status'=>'failed','error_message'=>mb_substr('PDF merge: '.$e->getMessage(),0,2000),'updated_at'=>now(),
            ]);
            throw $e;
        }
    }
}
