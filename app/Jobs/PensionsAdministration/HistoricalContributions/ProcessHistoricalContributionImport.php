<?php

namespace App\Jobs\PensionsAdministration\HistoricalContributions;

use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportBatch;
use App\Services\PensionsAdministration\HistoricalContributions\HistoricalContributionValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessHistoricalContributionImport implements ShouldQueue
{
    use Queueable;

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    public int $timeout = 3600;

    public int $tries = 1;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        public int $batchId
    ) {
        $this->onQueue(
            'historical-contribution-imports'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Handle
    |--------------------------------------------------------------------------
    */

    public function handle(
        HistoricalContributionValidator $validator
    ): void {
        $batch = HistoricalContributionImportBatch::query()
            ->findOrFail(
                $this->batchId
            );

        $validator->process(
            $batch
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Failed
    |--------------------------------------------------------------------------
    */

    public function failed(
        ?Throwable $exception
    ): void {
        $batch = HistoricalContributionImportBatch::query()
            ->find(
                $this->batchId
            );

        if (!$batch) {
            return;
        }

        $batch->update([
            'status' =>
                'failed',

            'failure_reason' =>
                $exception?->getMessage()
                ??
                'Historical contribution validation failed unexpectedly.',

            'completed_at' =>
                now(),
        ]);
    }
}