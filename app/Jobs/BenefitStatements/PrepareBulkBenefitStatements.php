<?php

namespace App\Jobs\BenefitStatements;

use App\Services\PensionsAdministration\BenefitStatements\BenefitStatementBulkEligibility;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PrepareBulkBenefitStatements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout=600;
    public int $tries=1;
    public function __construct(public string $runId) { $this->onQueue('benefit-statements'); }

    public function handle(BenefitStatementBulkEligibility $eligibility): void
    {
        $run=DB::table('benefit_statement_bulk_runs')->where('id',$this->runId)->first();
        if (!$run || $run->status!=='queued') return;
        DB::table('benefit_statement_bulk_runs')->where('id',$this->runId)->update(['status'=>'preparing','updated_at'=>now()]);
        try {
            $date=Carbon::parse($run->statement_date)->endOfDay();
            $query=$eligibility->query((int)$run->employer_id,$date);
            $disk=Storage::disk('local');
            $folder='benefit-statements/bulk/'.$this->runId;
            $disk->makeDirectory($folder);
            $batch=0;
            $total=0;
            $pending=[];
            // Stream IDs into small manifests; never hold all members in memory.
            $query->select('members.id')->chunkById(250,function ($members) use (&$pending,&$batch,&$total,$disk,$folder) {
                foreach ($members as $member) {
                    $pending[]=(int)$member->id;
                    $total++;
                    if (count($pending)===25) {
                        $batch++;
                        $disk->put($folder.'/ids_'.sprintf('%06d',$batch).'.json',json_encode($pending));
                        $pending=[];
                    }
                }
            },'members.id','id');
            if ($pending) {
                $batch++;
                $disk->put($folder.'/ids_'.sprintf('%06d',$batch).'.json',json_encode($pending));
            }
            if (!$total) {
                DB::table('benefit_statement_bulk_runs')->where('id',$this->runId)->update([
                    'status'=>'failed','error_message'=>'No eligible active contributing members found for the selected employer and date.','updated_at'=>now(),
                ]);
                return;
            }
            DB::table('benefit_statement_bulk_runs')->where('id',$this->runId)->update([
                'total_members'=>$total,'total_batches'=>$batch,'status'=>'processing','updated_at'=>now(),
            ]);
            for ($i=1;$i<=$batch;$i++) GenerateBulkBenefitStatementBatch::dispatch($this->runId,$i);
        } catch (Throwable $e) {
            DB::table('benefit_statement_bulk_runs')->where('id',$this->runId)->update([
                'status'=>'failed','error_message'=>mb_substr($e->getMessage(),0,2000),'updated_at'=>now(),
            ]);
            throw $e;
        }
    }
}
