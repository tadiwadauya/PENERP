<?php

namespace App\Jobs\PensionsAdministration\HistoricalContributions;

use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportBatch;
use App\Services\PensionsAdministration\HistoricalContributions\HistoricalContributionPostingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessHistoricalContributionPosting implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        public int $batchId,
        public int $postedBy
    ) {
        $this->onQueue(
            'historical-contribution-posting'
        );
    }

    public function handle(
        HistoricalContributionPostingService $postingService
    ): void {
        $batch =
            HistoricalContributionImportBatch::query()
                ->findOrFail(
                    $this->batchId
                );

        $postingService->post(
            $batch,
            $this->postedBy
        );
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $batch =
            HistoricalContributionImportBatch::query()
                ->find(
                    $this->batchId
                );

        if (!$batch) {
            return;
        }

        $batch->update([
            'status' =>
                'posting_failed',

            'failure_reason' =>
                $exception?->getMessage()
                ??
                'Historical contribution posting failed unexpectedly.',

            'completed_at' =>
                now(),
        ]);
    }
}