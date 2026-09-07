<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriod;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ContributionBatchRollbackService
{
    public function rollbackAndDelete(ContributionImportBatch $batch, int $userId, string $reason): array
    {
        $batch->load(['contributionPeriod', 'employer']);

        if (!$batch->contributionPeriod) {
            throw new RuntimeException('The contribution batch does not have a valid contribution period.');
        }

        if ($batch->status !== 'posted') {
            throw new RuntimeException('Only a posted monthly contribution batch can be rolled back and deleted.');
        }

        $this->ensureNoLaterActiveBatch($batch);

        $filePath = $batch->file_path;
        $result = DB::transaction(function () use ($batch, $userId, $reason): array {
            $lockedBatch = ContributionImportBatch::query()
                ->with(['contributionPeriod', 'employer'])
                ->where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== 'posted') {
                throw new RuntimeException('The contribution batch is no longer in posted status and cannot be rolled back.');
            }

            $this->ensureNoLaterActiveBatch($lockedBatch);

            $period = $lockedBatch->contributionPeriod;
            if (!$period) {
                throw new RuntimeException('The contribution batch does not have a valid contribution period.');
            }

            $createdMemberIds = ContributionImportRow::query()
                ->where('import_batch_id', $lockedBatch->id)
                ->where('member_created', true)
                ->whereNotNull('created_member_id')
                ->pluck('created_member_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $contributionCount = DB::table('member_contributions')
                ->where('import_batch_id', $lockedBatch->id)
                ->count();

            $statusCount = ContributionPeriodMemberStatus::query()
                ->where('import_batch_id', $lockedBatch->id)
                ->count();

            DB::table('member_contributions')
                ->where('import_batch_id', $lockedBatch->id)
                ->delete();

            ContributionPeriodMemberStatus::query()
                ->where('import_batch_id', $lockedBatch->id)
                ->delete();

            $rowCount = ContributionImportRow::query()
                ->where('import_batch_id', $lockedBatch->id)
                ->count();

            ContributionImportRow::query()
                ->where('import_batch_id', $lockedBatch->id)
                ->delete();

            $remainingBatchExists = ContributionImportBatch::query()
                ->where('contribution_period_id', $lockedBatch->contribution_period_id)
                ->where('id', '<>', $lockedBatch->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->exists();

            if (!$remainingBatchExists) {
                ContributionPeriod::query()
                    ->where('id', $lockedBatch->contribution_period_id)
                    ->update([
                        'status' => 'open',
                        'scheduled_members' => 0,
                        'existing_members' => 0,
                        'new_members' => 0,
                        'nil_contributors' => 0,
                        'updated_by' => $userId,
                        'updated_at' => now(),
                    ]);
            }

            $batchId = $lockedBatch->id;
            $employerId = $lockedBatch->employer_id;
            $periodId = $lockedBatch->contribution_period_id;
            $periodLabel = $period->period_label
                ?? sprintf('%04d-%02d', (int) $period->period_year, (int) $period->period_month);
            $currencyCode = strtoupper($lockedBatch->currency_code ?? 'ZWG');

            $lockedBatch->delete();

            return [
                'batch_id' => $batchId,
                'employer_id' => $employerId,
                'contribution_period_id' => $periodId,
                'period_label' => $periodLabel,
                'currency_code' => $currencyCode,
                'deleted_contributions' => (int) $contributionCount,
                'deleted_period_statuses' => (int) $statusCount,
                'deleted_import_rows' => (int) $rowCount,
                'retained_created_member_ids' => $createdMemberIds,
                'retained_created_members' => count($createdMemberIds),
                'rollback_reason' => $reason,
            ];
        }, 3);

        if ($filePath && Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
        }

        return $result;
    }

    private function ensureNoLaterActiveBatch(ContributionImportBatch $batch): void
    {
        $period = $batch->contributionPeriod;
        if (!$period) {
            throw new RuntimeException('The contribution batch does not have a valid contribution period.');
        }

        $laterBatch = ContributionImportBatch::query()
            ->select('contribution_import_batches.*')
            ->join('contribution_periods as cp', 'cp.id', '=', 'contribution_import_batches.contribution_period_id')
            ->where('contribution_import_batches.employer_id', $batch->employer_id)
            ->where('contribution_import_batches.id', '<>', $batch->id)
            ->whereNotIn('contribution_import_batches.status', ['cancelled', 'rejected'])
            ->where(function ($query) use ($period): void {
                $query->where('cp.period_year', '>', (int) $period->period_year)
                    ->orWhere(function ($monthQuery) use ($period): void {
                        $monthQuery->where('cp.period_year', (int) $period->period_year)
                            ->where('cp.period_month', '>', (int) $period->period_month);
                    });
            })
            ->orderBy('cp.period_year')
            ->orderBy('cp.period_month')
            ->first();

        if (!$laterBatch) {
            return;
        }

        $laterPeriod = ContributionPeriod::query()->find($laterBatch->contribution_period_id);
        $laterLabel = $laterPeriod?->period_label
            ?? ($laterPeriod ? sprintf('%04d-%02d', (int) $laterPeriod->period_year, (int) $laterPeriod->period_month) : 'a later period');

        throw new RuntimeException(
            'This batch cannot be rolled back while a later contribution batch exists for the same employer. '
            . 'Roll back ' . $laterLabel . ' first.'
        );
    }
}
