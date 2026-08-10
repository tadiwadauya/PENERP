<?php

namespace App\Jobs\PensionsAdministration\Updates;

use App\Models\PensionsAdministration\Updates\MembershipImportBatch;
use App\Services\PensionsAdministration\Updates\MembershipImportValidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessMembershipImport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200;
    public int $tries = 1;

    public function __construct(
        public int $batchId
    ) {
    }

    public function handle(
        MembershipImportValidationService $service
    ): void {
        $batch = MembershipImportBatch::findOrFail(
            $this->batchId
        );

        $service->process($batch);
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $batch = MembershipImportBatch::find(
            $this->batchId
        );

        if (!$batch) {
            return;
        }

        $batch->update([
            'status' => 'failed',
            'failure_reason' =>
                $exception?->getMessage()
                ?? 'Membership validation failed unexpectedly.',
            'completed_at' => now(),
        ]);
    }
}