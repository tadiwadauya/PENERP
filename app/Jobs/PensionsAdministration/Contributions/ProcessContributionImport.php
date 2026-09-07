<?php

namespace App\Jobs\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Services\PensionsAdministration\Contributions\ContributionImportValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessContributionImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1200;

    public function __construct(public int $batchId)
    {
        $this->onQueue('contribution-imports');
    }

    public function handle(ContributionImportValidator $validator): void
    {
        $batch = ContributionImportBatch::query()
            ->with([
                'employer',
                'contributionPeriod',
            ])
            ->findOrFail($this->batchId);

        $validator->process($batch);

        $batch->refresh();

        ContributionPeriodMemberStatus::rebuildForBatch($batch);

        $nilContributorRows = ContributionPeriodMemberStatus::query()
            ->where('contribution_period_id', $batch->contribution_period_id)
            ->where('employer_id', $batch->employer_id)
            ->where('import_batch_id', $batch->id)
            ->where(
                'contribution_status',
                ContributionPeriodMemberStatus::STATUS_NIL_CONTRIBUTOR
            )
            ->count();

        $batch->update([
            'nil_contributor_rows' => $nilContributorRows,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $batch = ContributionImportBatch::query()->find($this->batchId);

        if (!$batch) {
            return;
        }

        if (in_array($batch->status, [
            'validated',
            'awaiting_review',
            'approved',
            'posting',
            'posted',
        ], true)) {
            return;
        }

        $batch->update([
            'status' => 'processing_failed',
            'failure_reason' => $exception
                ? $exception->getMessage()
                : 'Contribution import processing failed.',
            'completed_at' => now(),
        ]);
    }
}