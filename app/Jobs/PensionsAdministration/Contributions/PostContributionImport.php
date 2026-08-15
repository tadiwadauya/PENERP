<?php

namespace App\Jobs\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Services\PensionsAdministration\Contributions\ContributionPostingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PostContributionImport implements ShouldQueue
{
    use Queueable;


    public int $timeout =
        1200;


    public int $tries =
        1;


    public function __construct(
        public int $batchId
    ) {
        $this->onQueue(
            'contribution-imports'
        );
    }


    public function handle(
        ContributionPostingService $service
    ): void {
        $batch =
            ContributionImportBatch::findOrFail(
                $this->batchId
            );


        $service->post(
            $batch
        );
    }


    public function failed(
        ?Throwable $exception
    ): void {
        $batch =
            ContributionImportBatch::find(
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
                    ?->getMessage()
                ??
                'Contribution posting failed unexpectedly.',
        ]);
    }
}