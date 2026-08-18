<?php

namespace App\Notifications\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContributionBatchRejected extends Notification
{
    use Queueable;


    public function __construct(
        private readonly ContributionImportBatch $batch,
        private readonly string $reason,
        private readonly string $rejectedByName
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Delivery Channels
    |--------------------------------------------------------------------------
    |
    | For now we use Laravel database notifications.
    |
    | We can add email later without changing the contribution workflow.
    |
    */

    public function via(
        object $notifiable
    ): array {
        return [
            'database',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Database Notification
    |--------------------------------------------------------------------------
    */

    public function toDatabase(
        object $notifiable
    ): array {
        return [
            'type' =>
                'contribution_batch_rejected',

            'title' =>
                'Monthly Contribution Batch Rejected',

            'message' =>
                'Contribution batch #'
                . $this->batch->id
                . ' was rejected by '
                . $this->rejectedByName
                . '.',

            'batch_id' =>
                $this->batch->id,

            'import_uuid' =>
                $this->batch->import_uuid,

            'employer_id' =>
                $this->batch->employer_id,

            'employer_name' =>
                $this->batch
                    ->employer
                    ?->name,

            'reason' =>
                $this->reason,

            'rejected_by' =>
                $this->rejectedByName,

            'rejected_at' =>
                now()->toDateTimeString(),

            'url' =>
                route(
                    'pensions-administration.contributions.imports.show',
                    $this->batch
                ),
        ];
    }
}