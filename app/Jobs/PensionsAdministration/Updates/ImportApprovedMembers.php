<?php

namespace App\Jobs\PensionsAdministration\Updates;

use App\Models\PensionsAdministration\Updates\MembershipImportBatch;
use App\Services\PensionsAdministration\Updates\MembershipImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ImportApprovedMembers implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200;

    public int $tries = 1;


    public function __construct(
        public int $batchId
    ) {
        $this->onQueue(
            'membership-imports'
        );
    }


    public function handle(
        MembershipImportService $service
    ): void {
        $batch =
            MembershipImportBatch::findOrFail(
                $this->batchId
            );


        $service->import(
            $batch
        );
    }


    public function failed(
        ?Throwable $exception
    ): void {
        $batch =
            MembershipImportBatch::find(
                $this->batchId
            );


        if (!$batch) {
            return;
        }


        $batch->update([
            'status' =>
                'failed',

            'failure_reason' =>
                $exception
                    ? $exception->getMessage()
                    : 'Membership import failed.',

            'completed_at' =>
                now(),
        ]);
    }
}