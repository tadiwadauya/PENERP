<?php

namespace App\Jobs\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Services\PensionsAdministration\Contributions\ContributionPostingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessContributionPosting implements ShouldQueue
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
    |
    | IMPORTANT:
    |
    | We deliberately use the same queue as contribution validation:
    |
    | contribution-imports
    |
    | Therefore your existing queue worker can process BOTH validation and
    | posting.
    |
    */

    public function __construct(
        public int $batchId,
        public int $postedBy
    ) {
        $this->onQueue(
            'contribution-imports'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Handle
    |--------------------------------------------------------------------------
    */

    public function handle(
        ContributionPostingService $postingService
    ): void {
        $batch =
            ContributionImportBatch::query()
                ->findOrFail(
                    $this->batchId
                );


        $postingService->post(
            $batch,
            $this->postedBy
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
        $batch =
            ContributionImportBatch::query()
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
                'Contribution posting failed unexpectedly.',

            /*
            |--------------------------------------------------------------------------
            | Do NOT Set 100%
            |--------------------------------------------------------------------------
            */

            'completed_at' =>
                now(),
        ]);
    }
}