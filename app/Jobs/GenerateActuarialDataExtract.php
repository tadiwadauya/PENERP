<?php

namespace App\Jobs;

use App\Models\PensionsAdministration\Reports\ActuarialDataExtractBatch;
use App\Services\PensionsAdministration\Reports\ActuarialDataExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateActuarialDataExtract implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;
    public int $tries = 2;

    public function __construct(
        public int $batchId
    ) {
        $this->onQueue('actuarial-data');
    }

    public function handle(
        ActuarialDataExportService $service
    ): void {
        $batch = ActuarialDataExtractBatch::query()
            ->findOrFail($this->batchId);

        $batch->update([
            'status' => 'processing',
            'progress_percentage' => 2,
            'processing_started_at' => now(),
            'failure_reason' => null,
        ]);

        try {
            $service->generate($batch);

            $batch->refresh();

            $batch->update([
                'status' => 'completed',
                'progress_percentage' => 100,
                'completed_at' => now(),
                'failure_reason' => null,
            ]);

        } catch (Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}