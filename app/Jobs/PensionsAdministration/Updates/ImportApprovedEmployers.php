<?php

namespace App\Jobs\PensionsAdministration\Updates;

use App\Models\PensionsAdministration\Updates\EmployerImportBatch;
use App\Services\PensionsAdministration\Updates\EmployerFinalImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ImportApprovedEmployers implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200;
    public int $tries = 1;

    public function __construct(
        public int $batchId
    ) {
    }

    public function handle(
        EmployerFinalImportService $service
    ): void {
        $batch = EmployerImportBatch::findOrFail(
            $this->batchId
        );

        $service->import(
            $batch
        );
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $batch = EmployerImportBatch::find(
            $this->batchId
        );

        if (!$batch) {
            return;
        }

        $batch->update([
            'status' => 'failed',

            'failure_reason' => $exception?->getMessage()
                ?? 'Final employer import failed unexpectedly.',
        ]);
    }
}