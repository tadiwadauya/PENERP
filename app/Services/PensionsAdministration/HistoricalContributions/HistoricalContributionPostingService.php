<?php

namespace App\Services\PensionsAdministration\HistoricalContributions;

use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportBatch;
use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportRow;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class HistoricalContributionPostingService
{
    private const ROW_CHUNK = 250;
    private const CONTRIBUTION_INSERT_CHUNK = 20;

    public function post(
        HistoricalContributionImportBatch $batch,
        int $postedBy
    ): void {
        if (!in_array($batch->status, ['approved', 'posting_failed'], true)) {
            throw new RuntimeException(
                'Historical contribution batch must be approved before posting.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Approved Rows
        |--------------------------------------------------------------------------
        */

        $approvedQuery = HistoricalContributionImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->where('review_decision', 'approved')
            ->where('duplicate_status', 'none')
            ->where('validation_status', '<>', 'error');

        $approvedRows = (clone $approvedQuery)->count();

        if ($approvedRows <= 0) {
            throw new RuntimeException(
                'There are no approved historical contribution transactions to post.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Already Posted Counts
        |--------------------------------------------------------------------------
        */

        $alreadyPostedContributionRows = (clone $approvedQuery)
            ->whereNotNull('posted_contribution_id')
            ->count();

        $alreadyPostedServiceRows = (clone $approvedQuery)
            ->where('service_status', 'break_in_service')
            ->whereNotNull('posted_at')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Start / Resume Posting
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'status' => 'posting',
            'progress_percentage' => 2,
            'posting_started_at' => $batch->posting_started_at ?? now(),
            'posted_by' => $postedBy,
            'posted_at' => null,
            'posted_transaction_rows' => $alreadyPostedContributionRows,
            'posted_service_period_rows' => $alreadyPostedServiceRows,
            'failure_reason' => null,
            'completed_at' => null,
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | Create / Resolve Historical Members
            |--------------------------------------------------------------------------
            */

            $this->createHistoricalMembers(
                batch: $batch,
                userId: $postedBy
            );

            $newMembersCreated = HistoricalContributionImportRow::query()
                ->where('import_batch_id', $batch->id)
                ->whereNotNull('created_member_id')
                ->distinct()
                ->count('created_member_id');

            $batch->update([
                'new_members_created' => $newMembersCreated,
                'progress_percentage' => 10,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Counters
            |--------------------------------------------------------------------------
            */

            $processedRows = 0;
            $postedContributionRows = $alreadyPostedContributionRows;
            $postedServiceRows = $alreadyPostedServiceRows;

            /*
            |--------------------------------------------------------------------------
            | Caches
            |--------------------------------------------------------------------------
            */

            $existingContributionCache = [];
            $contributionPeriodCache = [];
            $employmentCache = [];

            /*
            |--------------------------------------------------------------------------
            | Member Status Cache
            |--------------------------------------------------------------------------
            |
            | Exit details are included in the cache key so a later source row
            | containing an exit date/reason is not skipped.
            |
            */

            $memberStatusCache = [];

            /*
            |--------------------------------------------------------------------------
            | Process Approved Rows
            |--------------------------------------------------------------------------
            */

            HistoricalContributionImportRow::query()
                ->where('import_batch_id', $batch->id)
                ->where('review_decision', 'approved')
                ->where('duplicate_status', 'none')
                ->where('validation_status', '<>', 'error')
                ->orderBy('id')
                ->chunkById(
                    self::ROW_CHUNK,
                    function ($rows) use (
                        $batch,
                        $postedBy,
                        $approvedRows,
                        &$processedRows,
                        &$postedContributionRows,
                        &$postedServiceRows,
                        &$existingContributionCache,
                        &$contributionPeriodCache,
                        &$employmentCache,
                        &$memberStatusCache
                    ): void {
                        $contributionBuffer = [];
                        $contributionRowMap = [];

                        foreach ($rows as $row) {
                            /*
                            |--------------------------------------------------------------------------
                            | Already Posted
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $row->posted_at
                                ||
                                $row->posted_contribution_id
                            ) {
                                $processedRows++;

                                continue;
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Resolve Member
                            |--------------------------------------------------------------------------
                            */

                            $memberId =
                                $row->matched_member_id
                                ?:
                                $row->created_member_id;

                            if (!$memberId) {
                                throw new RuntimeException(
                                    'Excel row '
                                    . $row->source_row_number
                                    . ': No member could be resolved for posting.'
                                );
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Resolve Employer
                            |--------------------------------------------------------------------------
                            */

                            $employerId =
                                (int) $row->matched_employer_id;

                            if (!$employerId) {
                                throw new RuntimeException(
                                    'Excel row '
                                    . $row->source_row_number
                                    . ': No employer could be resolved for posting.'
                                );
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Preserve Historical Member Status / Exit Details
                            |--------------------------------------------------------------------------
                            */

                            $this->syncMemberStatusFromHistorical(
                                memberId: (int) $memberId,
                                row: $row,
                                userId: $postedBy,
                                cache: $memberStatusCache
                            );

                            /*
                            |--------------------------------------------------------------------------
                            | Ensure Employment
                            |--------------------------------------------------------------------------
                            */

                            $this->ensureEmployment(
                                memberId: (int) $memberId,
                                employerId: $employerId,
                                row: $row,
                                userId: $postedBy,
                                cache: $employmentCache
                            );

                            /*
                            |--------------------------------------------------------------------------
                            | Break In Service
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $row->service_status === 'break_in_service'
                                &&
                                $this->normaliseTransactionType(
                                    $row->transaction_type
                                ) === 'expected'
                            ) {
                                $inserted = $this->storeServicePeriod(
                                    memberId: (int) $memberId,
                                    employerId: $employerId,
                                    row: $row,
                                    batch: $batch,
                                    userId: $postedBy
                                );

                                if ($inserted) {
                                    $postedServiceRows++;
                                }

                                $row->update([
                                    'posted_at' => now(),
                                    'updated_at' => now(),
                                ]);

                                $processedRows++;

                                continue;
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Contribution Period
                            |--------------------------------------------------------------------------
                            */

                            $periodId = $this->resolveContributionPeriod(
                                employerId: $employerId,
                                year: (int) $row->period_year,
                                month: (int) $row->period_month,
                                periodDate: $row->period_date,
                                userId: $postedBy,
                                cache: $contributionPeriodCache
                            );

                            /*
                            |--------------------------------------------------------------------------
                            | Existing Contribution
                            |--------------------------------------------------------------------------
                            */

                            $existingContributionId =
                                $this->findExistingContribution(
                                    memberId: (int) $memberId,
                                    employerId: $employerId,
                                    year: (int) $row->period_year,
                                    month: (int) $row->period_month,
                                    transactionType:
                                        $this->normaliseTransactionType(
                                            $row->transaction_type
                                        ),
                                    cache: $existingContributionCache
                                );

                            if ($existingContributionId) {
                                $row->update([
                                    'posted_contribution_id' =>
                                        $existingContributionId,

                                    'posted_at' =>
                                        now(),

                                    'updated_at' =>
                                        now(),
                                ]);

                                $processedRows++;

                                continue;
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Build Historical Contribution
                            |--------------------------------------------------------------------------
                            */

                            $contributionBuffer[] =
                                $this->buildContributionRecord(
                                    memberId: (int) $memberId,
                                    employerId: $employerId,
                                    contributionPeriodId: $periodId,
                                    row: $row,
                                    batch: $batch,
                                    userId: $postedBy
                                );

                            $contributionRowMap[] =
                                $row->id;

                            /*
                            |--------------------------------------------------------------------------
                            | Flush Buffer
                            |--------------------------------------------------------------------------
                            */

                            if (
                                count($contributionBuffer)
                                >=
                                self::CONTRIBUTION_INSERT_CHUNK
                            ) {
                                $linked = $this->flushContributions(
                                    contributionBuffer: $contributionBuffer,
                                    stagingRowIds: $contributionRowMap
                                );

                                $postedContributionRows += $linked;
                            }

                            $processedRows++;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Flush Remaining
                        |--------------------------------------------------------------------------
                        */

                        if (!empty($contributionBuffer)) {
                            $linked = $this->flushContributions(
                                contributionBuffer: $contributionBuffer,
                                stagingRowIds: $contributionRowMap
                            );

                            $postedContributionRows += $linked;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Progress
                        |--------------------------------------------------------------------------
                        */

                        $progress =
                            10
                            +
                            (
                                (
                                    $processedRows
                                    /
                                    max(1, $approvedRows)
                                )
                                *
                                88
                            );

                        $batch->update([
                            'posted_transaction_rows' =>
                                $postedContributionRows,

                            'posted_service_period_rows' =>
                                $postedServiceRows,

                            'progress_percentage' =>
                                round(
                                    min(98, $progress),
                                    2
                                ),
                        ]);

                        gc_collect_cycles();
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Final Counts
            |--------------------------------------------------------------------------
            */

            $finalContributionCount =
                HistoricalContributionImportRow::query()
                    ->where('import_batch_id', $batch->id)
                    ->where('review_decision', 'approved')
                    ->whereNotNull('posted_contribution_id')
                    ->count();

            $finalServicePeriodCount =
                HistoricalContributionImportRow::query()
                    ->where('import_batch_id', $batch->id)
                    ->where('review_decision', 'approved')
                    ->where('service_status', 'break_in_service')
                    ->whereNotNull('posted_at')
                    ->count();

            /*
            |--------------------------------------------------------------------------
            | Complete
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'status' => 'posted',
                'progress_percentage' => 100,
                'posted_transaction_rows' => $finalContributionCount,
                'posted_service_period_rows' => $finalServicePeriodCount,
                'new_members_created' => $newMembersCreated,
                'posted_by' => $postedBy,
                'posted_at' => now(),
                'completed_at' => now(),
                'failure_reason' => null,
            ]);

        } catch (Throwable $e) {
            $batch->update([
                'status' => 'posting_failed',
                'failure_reason' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Historical Members
    |--------------------------------------------------------------------------
    */

    private function createHistoricalMembers(
        HistoricalContributionImportBatch $batch,
        int $userId
    ): void {
        $sourceRows =
            HistoricalContributionImportRow::query()
                ->where('import_batch_id', $batch->id)
                ->where('is_new_member', true)
                ->where('review_decision', 'approved')
                ->whereNull('matched_member_id')
                ->whereNull('created_member_id')
                ->select('source_row_number')
                ->distinct()
                ->orderBy('source_row_number')
                ->pluck('source_row_number');

        foreach ($sourceRows as $sourceRowNumber) {
            DB::transaction(
                function () use (
                    $batch,
                    $sourceRowNumber,
                    $userId
                ): void {
                    /*
                    |--------------------------------------------------------------------------
                    | Representative Source Row
                    |--------------------------------------------------------------------------
                    */

                    $row =
                        HistoricalContributionImportRow::query()
                            ->where('import_batch_id', $batch->id)
                            ->where('source_row_number', $sourceRowNumber)
                            ->where('is_new_member', true)
                            ->where('review_decision', 'approved')
                            ->orderBy('id')
                            ->first();

                    if (!$row) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Already Created
                    |--------------------------------------------------------------------------
                    */

                    $alreadyCreated =
                        HistoricalContributionImportRow::query()
                            ->where('import_batch_id', $batch->id)
                            ->where('source_row_number', $sourceRowNumber)
                            ->whereNotNull('created_member_id')
                            ->value('created_member_id');

                    if ($alreadyCreated) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Strong Identifier Recheck
                    |--------------------------------------------------------------------------
                    */

                    $existingMemberId =
                        $this->findExistingMemberBeforeCreate(
                            $row
                        );

                    if ($existingMemberId) {
                        $memberStatusCache = [];

                        $this->syncMemberStatusFromHistorical(
                            memberId: (int) $existingMemberId,
                            row: $row,
                            userId: $userId,
                            cache: $memberStatusCache
                        );

                        HistoricalContributionImportRow::query()
                            ->where('import_batch_id', $batch->id)
                            ->where('source_row_number', $sourceRowNumber)
                            ->update([
                                'matched_member_id' =>
                                    $existingMemberId,

                                'created_member_id' =>
                                    null,

                                'is_new_member' =>
                                    false,

                                'member_match_type' =>
                                    'posting_recheck',

                                'updated_at' =>
                                    now(),
                            ]);

                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create Member
                    |--------------------------------------------------------------------------
                    */

                    $memberNumber =
                        $this->nextMemberNumber();

                    $status =
                        HistoricalMembershipStatus::normalize(
                            $row->membership_status
                        );

                    $member =
                        Member::query()
                            ->create([
                                'member_number' =>
                                    $memberNumber,

                                'penad_member_number' =>
                                    $this->clean(
                                        $row->penad_member_number
                                    ),

                                'fundworx_member_number' =>
                                    $this->clean(
                                        $row->fundworx_member_number
                                    ),

                                'title' =>
                                    $this->clean(
                                        $row->title
                                    ),

                                'surname' =>
                                    $this->clean(
                                        $row->surname
                                    )
                                    ??
                                    'UNKNOWN',

                                'first_names' =>
                                    $this->clean(
                                        $row->first_names
                                    )
                                    ??
                                    'UNKNOWN',

                                'other_names' =>
                                    $this->clean(
                                        $row->other_names
                                    ),

                                'maiden_name' =>
                                    $this->clean(
                                        $row->maiden_name
                                    ),

                                'national_id' =>
                                    $this->clean(
                                        $row->national_id
                                    ),

                                'national_id_normalized' =>
                                    Member::normalizeNationalId(
                                        $row->national_id
                                    ),

                                'date_of_birth' =>
                                    $row->date_of_birth,

                                'gender' =>
                                    $this->clean(
                                        $row->gender
                                    ),

                                'marital_status' =>
                                    $this->clean(
                                        $row->marital_status
                                    ),

                                'occupation' =>
                                    $this->clean(
                                        $row->occupation
                                    ),

                                'email' =>
                                    $this->clean(
                                        $row->email
                                    ),

                                'secondary_email' =>
                                    $this->clean(
                                        $row->secondary_email
                                    ),

                                'cell_number' =>
                                    $this->clean(
                                        $row->cell_number
                                    ),

                                'secondary_cell_number' =>
                                    $this->clean(
                                        $row->secondary_cell_number
                                    ),

                                'cellphone_number' =>
                                    $this->clean(
                                        $row->cell_number
                                    ),

                                'email_address' =>
                                    $this->clean(
                                        $row->email
                                    ),

                                'home_address' =>
                                    $this->buildHomeAddress(
                                        $row
                                    ),

                                'physical_address_1' =>
                                    $this->clean(
                                        $row->physical_address_1
                                    ),

                                'physical_address_2' =>
                                    $this->clean(
                                        $row->physical_address_2
                                    ),

                                'physical_address_3' =>
                                    $this->clean(
                                        $row->physical_address_3
                                    ),

                                'physical_suburb' =>
                                    $this->clean(
                                        $row->physical_suburb
                                    ),

                                'physical_city' =>
                                    $this->clean(
                                        $row->physical_city
                                    ),

                                'physical_country' =>
                                    $this->clean(
                                        $row->physical_country
                                    ),

                                'postal_address_1' =>
                                    $this->clean(
                                        $row->postal_address_1
                                    ),

                                'postal_address_2' =>
                                    $this->clean(
                                        $row->postal_address_2
                                    ),

                                'postal_address_3' =>
                                    $this->clean(
                                        $row->postal_address_3
                                    ),

                                'postal_city' =>
                                    $this->clean(
                                        $row->postal_city
                                    ),

                                'postal_country' =>
                                    $this->clean(
                                        $row->postal_country
                                    ),

                                'date_joined_fund' =>
                                    $row->date_joined_fund,

                                'membership_status' =>
                                    $status,

                                /*
                                |--------------------------------------------------------------------------
                                | Historical Exit Details
                                |--------------------------------------------------------------------------
                                |
                                | Optional.
                                |
                                | Never infer exit date from contribution activity.
                                |
                                */

                                'exit_date' =>
                                    $row->exit_date,

                                'exit_reason' =>
                                    $this->clean(
                                        $row->exit_reason
                                    ),

                                'is_active' =>
                                    HistoricalMembershipStatus::isActive(
                                        $status
                                    ),

                                'created_by' =>
                                    $userId,

                                'updated_by' =>
                                    $userId,
                            ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Associate Every Period With Created Member
                    |--------------------------------------------------------------------------
                    */

                    HistoricalContributionImportRow::query()
                        ->where('import_batch_id', $batch->id)
                        ->where('source_row_number', $sourceRowNumber)
                        ->update([
                            'created_member_id' =>
                                $member->id,

                            'penerp_member_number' =>
                                $member->member_number,

                            'updated_at' =>
                                now(),
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Create Employment
                    |--------------------------------------------------------------------------
                    */

                    if ($row->matched_employer_id) {
                        $cache = [];

                        $this->ensureEmployment(
                            memberId: $member->id,
                            employerId: (int) $row->matched_employer_id,
                            row: $row,
                            userId: $userId,
                            cache: $cache
                        );
                    }
                }
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Member Recheck
    |--------------------------------------------------------------------------
    */

    private function findExistingMemberBeforeCreate(
        HistoricalContributionImportRow $row
    ): ?int {
        /*
        |--------------------------------------------------------------------------
        | PenAd
        |--------------------------------------------------------------------------
        */

        if (filled($row->penad_member_number)) {
            $ids =
                Member::query()
                    ->where(
                        'penad_member_number',
                        trim($row->penad_member_number)
                    )
                    ->limit(2)
                    ->pluck('id');

            if ($ids->count() === 1) {
                return (int) $ids->first();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PENERP
        |--------------------------------------------------------------------------
        */

        if (filled($row->penerp_member_number)) {
            $ids =
                Member::query()
                    ->where(
                        'member_number',
                        trim($row->penerp_member_number)
                    )
                    ->limit(2)
                    ->pluck('id');

            if ($ids->count() === 1) {
                return (int) $ids->first();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fundworx
        |--------------------------------------------------------------------------
        */

        if (filled($row->fundworx_member_number)) {
            $ids =
                Member::query()
                    ->where(
                        'fundworx_member_number',
                        trim($row->fundworx_member_number)
                    )
                    ->limit(2)
                    ->pluck('id');

            if ($ids->count() === 1) {
                return (int) $ids->first();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | National ID
        |--------------------------------------------------------------------------
        */

        $nationalId =
            Member::normalizeNationalId(
                $row->national_id
            );

        if ($nationalId) {
            $ids =
                Member::query()
                    ->where(
                        'national_id_normalized',
                        $nationalId
                    )
                    ->limit(2)
                    ->pluck('id');

            if ($ids->count() === 1) {
                return (int) $ids->first();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Staff Number
        |--------------------------------------------------------------------------
        */

        if (
            $row->matched_employer_id
            &&
            filled($row->staff_number)
            &&
            HistoricalMembershipStatus::isActive(
                $row->membership_status
            )
        ) {
            $activeIds =
                MemberEmployment::query()
                    ->join(
                        'members',
                        'members.id',
                        '=',
                        'member_employments.member_id'
                    )
                    ->where(
                        'member_employments.employer_id',
                        $row->matched_employer_id
                    )
                    ->where(
                        'member_employments.staff_number',
                        trim($row->staff_number)
                    )
                    ->where(
                        'members.is_active',
                        true
                    )
                    ->distinct()
                    ->limit(2)
                    ->pluck(
                        'member_employments.member_id'
                    );

            if ($activeIds->count() === 1) {
                return (int) $activeIds->first();
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Synchronise Member Status From Historical Source
    |--------------------------------------------------------------------------
    */

    private function syncMemberStatusFromHistorical(
        int $memberId,
        HistoricalContributionImportRow $row,
        int $userId,
        array &$cache
    ): void {
        $status =
            HistoricalMembershipStatus::normalize(
                $row->membership_status
            );

        /*
        |--------------------------------------------------------------------------
        | Cache Key
        |--------------------------------------------------------------------------
        |
        | Exit information is included because a later source row can contain
        | exit information that an earlier monthly row did not contain.
        |
        */

        $sourceExitDate =
            $row->exit_date
                ? $row->exit_date->format('Y-m-d')
                : '';

        $sourceExitReason =
            trim(
                (string) $row->exit_reason
            );

        $cacheKey =
            $memberId
            . '|'
            . $status
            . '|'
            . $sourceExitDate
            . '|'
            . $sourceExitReason;

        if (isset($cache[$cacheKey])) {
            return;
        }

        $member =
            Member::query()
                ->find($memberId);

        if (!$member) {
            throw new RuntimeException(
                'Excel row '
                . $row->source_row_number
                . ': Member '
                . $memberId
                . ' could not be found while synchronising historical membership status.'
            );
        }

        $isActive =
            HistoricalMembershipStatus::isActive(
                $status
            );

        $updates = [];

        /*
        |--------------------------------------------------------------------------
        | Membership Status
        |--------------------------------------------------------------------------
        */

        if (
            strtolower(
                trim(
                    (string) $member->membership_status
                )
            )
            !==
            $status
        ) {
            $updates['membership_status'] =
                $status;
        }

        /*
        |--------------------------------------------------------------------------
        | Exit Date
        |--------------------------------------------------------------------------
        |
        | Exit date is optional.
        |
        | Only use an actual supplied source exit date.
        | Never derive it from the final contribution month.
        |
        */

        if (
            $status === 'exited'
            &&
            $row->exit_date
        ) {
            $currentExitDate =
                $member->exit_date
                    ? $member->exit_date->format('Y-m-d')
                    : null;

            if (
                $currentExitDate
                !==
                $sourceExitDate
            ) {
                $updates['exit_date'] =
                    $row->exit_date;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Exit Reason
        |--------------------------------------------------------------------------
        */

        if (
            $status === 'exited'
            &&
            filled($row->exit_reason)
        ) {
            $currentExitReason =
                trim(
                    (string) $member->exit_reason
                );

            if (
                $currentExitReason
                !==
                $sourceExitReason
            ) {
                $updates['exit_reason'] =
                    $sourceExitReason;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Active Flag
        |--------------------------------------------------------------------------
        */

        if (
            (bool) $member->is_active
            !==
            $isActive
        ) {
            $updates['is_active'] =
                $isActive;
        }

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        if (!empty($updates)) {
            $updates['updated_by'] =
                $userId;

            $updates['updated_at'] =
                now();

            Member::query()
                ->where(
                    'id',
                    $memberId
                )
                ->update(
                    $updates
                );
        }

        $cache[$cacheKey] =
            true;
    }

    /*
    |--------------------------------------------------------------------------
    | Ensure Employment
    |--------------------------------------------------------------------------
    */

    private function ensureEmployment(
        int $memberId,
        int $employerId,
        HistoricalContributionImportRow $row,
        int $userId,
        array &$cache
    ): int {
        $cacheKey =
            $memberId
            . '|'
            . $employerId;

        if (isset($cache[$cacheKey])) {
            return (int) $cache[$cacheKey];
        }

        $status =
            HistoricalMembershipStatus::normalize(
                $row->membership_status
            );

        $isActiveStatus =
            HistoricalMembershipStatus::isActive(
                $status
            );

        /*
        |--------------------------------------------------------------------------
        | Employment Relationship
        |--------------------------------------------------------------------------
        */

        $isCurrentEmployment = $isActiveStatus;

        /*
        |--------------------------------------------------------------------------
        | Active Staff Number Conflict Only
        |--------------------------------------------------------------------------
        */

        if (
            $isActiveStatus
            &&
            filled($row->staff_number)
        ) {
            $conflictingActiveMember =
                MemberEmployment::query()
                    ->join(
                        'members',
                        'members.id',
                        '=',
                        'member_employments.member_id'
                    )
                    ->where(
                        'member_employments.employer_id',
                        $employerId
                    )
                    ->where(
                        'member_employments.staff_number',
                        trim($row->staff_number)
                    )
                    ->where(
                        'member_employments.member_id',
                        '<>',
                        $memberId
                    )
                    ->where(
                        'members.is_active',
                        true
                    )
                    ->value(
                        'member_employments.member_id'
                    );

            if ($conflictingActiveMember) {
                throw new RuntimeException(
                    'Excel row '
                    . $row->source_row_number
                    . ': Staff Number '
                    . $row->staff_number
                    . ' is already assigned to another active member under this employer.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Employment
        |--------------------------------------------------------------------------
        */

        $employment =
            MemberEmployment::query()
                ->where(
                    'member_id',
                    $memberId
                )
                ->where(
                    'employer_id',
                    $employerId
                )
                ->first();

        if ($employment) {
            $updates = [
                'employment_status' =>
                    HistoricalMembershipStatus::employmentStatus(
                        $status
                    ),

                'is_current' =>
                    $isCurrentEmployment,
            ];

            if (
                blank($employment->staff_number)
                &&
                filled($row->staff_number)
            ) {
                $updates['staff_number'] =
                    trim($row->staff_number);
            }

            if (
                blank($employment->vote_number)
                &&
                filled($row->vote_number)
            ) {
                $updates['vote_number'] =
                    trim($row->vote_number);
            }

            if (
                !$employment->date_joined_employer
                &&
                $row->date_joined_employer
            ) {
                $updates['date_joined_employer'] =
                    $row->date_joined_employer;
            }

            if (!empty($updates)) {
                $updates['updated_by'] =
                    $userId;

                $employment->update(
                    $updates
                );
            }

            $cache[$cacheKey] =
                (int) $employment->id;

            return (int) $employment->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Create Employment
        |--------------------------------------------------------------------------
        */

        $employment =
            MemberEmployment::query()
                ->create([
                    'member_id' =>
                        $memberId,

                    'employer_id' =>
                        $employerId,

                    'staff_number' =>
                        $this->clean(
                            $row->staff_number
                        ),

                    'vote_number' =>
                        $this->clean(
                            $row->vote_number
                        ),

                    'branch' =>
                        null,

                    'department' =>
                        null,

                    'date_joined_employer' =>
                        $row->date_joined_employer
                        ?:
                        $row->date_joined_fund,

                    'effective_from' =>
                        $row->date_joined_employer
                        ?:
                        $row->date_joined_fund,

                    /*
                    |--------------------------------------------------------------------------
                    | Do Not Infer Employment End From Membership Exit
                    |--------------------------------------------------------------------------
                    */

                    'effective_to' =>
                        null,

                    'employment_status' =>
                        HistoricalMembershipStatus::employmentStatus(
                            $status
                        ),

                    'is_current' =>
                        $isCurrentEmployment,

                    'created_by' =>
                        $userId,

                    'updated_by' =>
                        $userId,
                ]);

        $cache[$cacheKey] =
            (int) $employment->id;

        return (int) $employment->id;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Contribution Period
    |--------------------------------------------------------------------------
    */

    private function resolveContributionPeriod(
        int $employerId,
        int $year,
        int $month,
        mixed $periodDate,
        int $userId,
        array &$cache
    ): int {
        $cacheKey =
            $employerId
            . '|'
            . $year
            . '|'
            . $month;

        if (isset($cache[$cacheKey])) {
            return (int) $cache[$cacheKey];
        }

        $periodId =
            DB::table(
                'contribution_periods'
            )
                ->where(
                    'employer_id',
                    $employerId
                )
                ->where(
                    'period_year',
                    $year
                )
                ->where(
                    'period_month',
                    $month
                )
                ->value('id');

        if ($periodId) {
            $cache[$cacheKey] =
                (int) $periodId;

            return (int) $periodId;
        }

        $periodId =
            (int) DB::table(
                'contribution_periods'
            )
                ->insertGetId([
                    'employer_id' =>
                        $employerId,

                    'period_date' =>
                        $periodDate,

                    'due_date' =>
                        $periodDate,

                    'period_year' =>
                        $year,

                    'period_month' =>
                        $month,

                    'scheme_code' =>
                        null,

                    'status' =>
                        'posted',

                    'scheduled_members' =>
                        0,

                    'existing_members' =>
                        0,

                    'new_members' =>
                        0,

                    'nil_contributors' =>
                        0,

                    'created_by' =>
                        $userId,

                    'updated_by' =>
                        $userId,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

        $cache[$cacheKey] =
            $periodId;

        return $periodId;
    }

    /*
    |--------------------------------------------------------------------------
    | Find Existing Contribution
    |--------------------------------------------------------------------------
    */

    private function findExistingContribution(
        int $memberId,
        int $employerId,
        int $year,
        int $month,
        string $transactionType,
        array &$cache
    ): ?int {
        $memberEmployerKey =
            $memberId
            . '|'
            . $employerId;

        if (!isset($cache[$memberEmployerKey])) {
            $existing = [];

            $rows =
                DB::table(
                    'member_contributions'
                )
                    ->where(
                        'member_id',
                        $memberId
                    )
                    ->where(
                        'employer_id',
                        $employerId
                    )
                    ->select([
                        'id',
                        'period_year',
                        'period_month',
                        'transaction_type',
                    ])
                    ->get();

            foreach ($rows as $existingRow) {
                $key =
                    (int) $existingRow->period_year
                    . '|'
                    . (int) $existingRow->period_month
                    . '|'
                    . $this->normaliseTransactionType(
                        $existingRow->transaction_type
                    );

                $existing[$key] =
                    (int) $existingRow->id;
            }

            $cache[$memberEmployerKey] =
                $existing;
        }

        $periodKey =
            $year
            . '|'
            . $month
            . '|'
            . $transactionType;

        return $cache[
            $memberEmployerKey
        ][
            $periodKey
        ]
        ??
        null;
    }

    /*
    |--------------------------------------------------------------------------
    | Build Historical Contribution
    |--------------------------------------------------------------------------
    */

    private function buildContributionRecord(
        int $memberId,
        int $employerId,
        int $contributionPeriodId,
        HistoricalContributionImportRow $row,
        HistoricalContributionImportBatch $batch,
        int $userId
    ): array {
        $basicPay =
            $this->nullableDecimal4(
                $row->basic_pay
            );

        $employeeContribution =
            $this->nullableDecimal4(
                $row->employee_contribution
            );

        $employerContribution =
            $this->nullableDecimal4(
                $row->employer_contribution
            );

        $employeeAvc =
            $this->nullableDecimal4(
                $row->employee_avc
            );

        $employerAvc =
            $this->nullableDecimal4(
                $row->employer_avc
            );

        $now =
            now();

        return [
            'member_id' =>
                $memberId,

            'employer_id' =>
                $employerId,

            'contribution_period_id' =>
                $contributionPeriodId,

            'import_batch_id' =>
                null,

            'import_row_id' =>
                null,

            'historical_import_batch_id' =>
                $batch->id,

            'historical_import_row_id' =>
                $row->id,

            'source_row_number' =>
                $row->source_row_number,

            'source_system' =>
                'historical_migration',

            'penerp_member_number' =>
                $row->penerp_member_number,

            'penad_member_number' =>
                $row->penad_member_number,

            'fundworx_member_number' =>
                $row->fundworx_member_number,

            'staff_number' =>
                $row->staff_number,

            'period_date' =>
                $row->period_date,

            'period_year' =>
                $row->period_year,

            'period_month' =>
                $row->period_month,

            'due_date' =>
                $row->period_date,

            'scheme_code' =>
                null,

            'transaction_type' =>
                $this->normaliseTransactionType(
                    $row->transaction_type
                ),

            'payment_flag' =>
                null,

            /*
            |--------------------------------------------------------------------------
            | USD Legacy Fields
            |--------------------------------------------------------------------------
            */

            'usd_basic_pay' => 0,
            'usd_employee_rate' => 0,
            'usd_employer_rate' => 0,
            'usd_employee_contribution' => 0,
            'usd_employer_contribution' => 0,
            'usd_employee_avc' => 0,
            'usd_employer_avc' => 0,
            'usd_employee_arrear' => 0,
            'usd_employer_arrear' => 0,
            'usd_employee_transfer_in' => 0,
            'usd_employer_transfer_in' => 0,
            'usd_employee_late_interest' => 0,
            'usd_employer_late_interest' => 0,

            /*
            |--------------------------------------------------------------------------
            | ZWG Legacy Fields
            |--------------------------------------------------------------------------
            */

            'zwg_basic_pay' => 0,
            'zwg_employee_rate' => 0,
            'zwg_employer_rate' => 0,
            'zwg_employee_contribution' => 0,
            'zwg_employer_contribution' => 0,
            'zwg_employee_avc' => 0,
            'zwg_employer_avc' => 0,
            'zwg_employee_arrear' => 0,
            'zwg_employer_arrear' => 0,
            'zwg_employee_transfer_in' => 0,
            'zwg_employer_transfer_in' => 0,
            'zwg_employee_late_interest' => 0,
            'zwg_employer_late_interest' => 0,

            /*
            |--------------------------------------------------------------------------
            | Generic Historical Values
            |--------------------------------------------------------------------------
            */

            'currency_code' =>
                $row->currency_code,

            'basic_pay' =>
                $basicPay,

            'employee_rate' =>
                $this->nullableDecimal6(
                    $row->employee_rate
                ),

            'employer_rate' =>
                $this->nullableDecimal6(
                    $row->employer_rate
                ),

            'employee_contribution' =>
                $employeeContribution,

            'employer_contribution' =>
                $employerContribution,

            'employee_avc' =>
                $employeeAvc,

            'employer_avc' =>
                $employerAvc,

            'employee_arrear' =>
                null,

            'employer_arrear' =>
                null,

            'employee_transfer_in' =>
                null,

            'employer_transfer_in' =>
                null,

            'employee_late_interest' =>
                null,

            'employer_late_interest' =>
                null,

            'comments' =>
                $row->comments
                ??
                $row->source_reference,

            'posted_by' =>
                $userId,

            'posted_at' =>
                $now,

            'created_by' =>
                $userId,

            'updated_by' =>
                $userId,

            'created_at' =>
                $now,

            'updated_at' =>
                $now,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Flush Historical Contributions
    |--------------------------------------------------------------------------
    */

    private function flushContributions(
        array &$contributionBuffer,
        array &$stagingRowIds
    ): int {
        if (empty($contributionBuffer)) {
            return 0;
        }

        $recordsBySourceKey = [];
        $stagingRowBySourceKey = [];

        foreach ($contributionBuffer as $index => $record) {
            $sourceKey =
                $record['historical_import_batch_id']
                . '|'
                . $record['historical_import_row_id'];

            $recordsBySourceKey[$sourceKey] =
                $record;

            $stagingRowBySourceKey[$sourceKey] =
                (int) $stagingRowIds[$index];
        }

        $batchIds =
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        array_column(
                            $contributionBuffer,
                            'historical_import_batch_id'
                        )
                    )
                )
            );

        $historicalRowIds =
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        array_column(
                            $contributionBuffer,
                            'historical_import_row_id'
                        )
                    )
                )
            );

        $existingRows =
            DB::table(
                'member_contributions'
            )
                ->whereIn(
                    'historical_import_batch_id',
                    $batchIds
                )
                ->whereIn(
                    'historical_import_row_id',
                    $historicalRowIds
                )
                ->select([
                    'id',
                    'historical_import_batch_id',
                    'historical_import_row_id',
                ])
                ->get();

        $linkedCount = 0;

        foreach ($existingRows as $existing) {
            $sourceKey =
                $existing->historical_import_batch_id
                . '|'
                . $existing->historical_import_row_id;

            $stagingRowId =
                $stagingRowBySourceKey[$sourceKey]
                ??
                null;

            if (!$stagingRowId) {
                continue;
            }

            DB::table(
                'historical_contribution_import_rows'
            )
                ->where(
                    'id',
                    $stagingRowId
                )
                ->update([
                    'posted_contribution_id' =>
                        (int) $existing->id,

                    'posted_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

            unset(
                $recordsBySourceKey[$sourceKey]
            );

            $linkedCount++;
        }

        if (empty($recordsBySourceKey)) {
            $contributionBuffer = [];
            $stagingRowIds = [];

            return $linkedCount;
        }

        $newRecords =
            array_values(
                $recordsBySourceKey
            );

        foreach (
            array_chunk(
                $newRecords,
                self::CONTRIBUTION_INSERT_CHUNK
            )
            as $chunk
        ) {
            DB::table(
                'member_contributions'
            )
                ->insert(
                    $chunk
                );
        }

        $newHistoricalRowIds =
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        array_column(
                            $newRecords,
                            'historical_import_row_id'
                        )
                    )
                )
            );

        $insertedRows =
            DB::table(
                'member_contributions'
            )
                ->whereIn(
                    'historical_import_batch_id',
                    $batchIds
                )
                ->whereIn(
                    'historical_import_row_id',
                    $newHistoricalRowIds
                )
                ->select([
                    'id',
                    'historical_import_batch_id',
                    'historical_import_row_id',
                ])
                ->get();

        $stagingUpdates = [];

        foreach ($insertedRows as $inserted) {
            $sourceKey =
                $inserted->historical_import_batch_id
                . '|'
                . $inserted->historical_import_row_id;

            $stagingRowId =
                $stagingRowBySourceKey[$sourceKey]
                ??
                null;

            if (!$stagingRowId) {
                continue;
            }

            $stagingUpdates[] = [
                'staging_row_id' =>
                    (int) $stagingRowId,

                'contribution_id' =>
                    (int) $inserted->id,
            ];
        }

        foreach (
            array_chunk(
                $stagingUpdates,
                100
            )
            as $updateChunk
        ) {
            if (empty($updateChunk)) {
                continue;
            }

            $caseSql =
                'CASE id ';

            $bindings = [];
            $ids = [];

            foreach ($updateChunk as $update) {
                $caseSql .=
                    'WHEN ? THEN ? ';

                $bindings[] =
                    $update['staging_row_id'];

                $bindings[] =
                    $update['contribution_id'];

                $ids[] =
                    $update['staging_row_id'];
            }

            $caseSql .=
                'END';

            $placeholders =
                implode(
                    ',',
                    array_fill(
                        0,
                        count($ids),
                        '?'
                    )
                );

            $bindings =
                array_merge(
                    $bindings,
                    $ids
                );

            DB::update(
                "
                UPDATE historical_contribution_import_rows
                SET
                    posted_contribution_id = {$caseSql},
                    posted_at = GETDATE(),
                    updated_at = GETDATE()
                WHERE id IN ({$placeholders})
                ",
                $bindings
            );
        }

        $linkedCount +=
            count(
                $stagingUpdates
            );

        $contributionBuffer = [];
        $stagingRowIds = [];

        unset(
            $recordsBySourceKey,
            $stagingRowBySourceKey,
            $existingRows,
            $newRecords,
            $insertedRows,
            $stagingUpdates
        );

        return $linkedCount;
    }

    /*
    |--------------------------------------------------------------------------
    | Store Break In Service
    |--------------------------------------------------------------------------
    */

    private function storeServicePeriod(
        int $memberId,
        int $employerId,
        HistoricalContributionImportRow $row,
        HistoricalContributionImportBatch $batch,
        int $userId
    ): bool {
        $existing =
            DB::table(
                'historical_member_service_periods'
            )
                ->where(
                    'member_id',
                    $memberId
                )
                ->where(
                    'employer_id',
                    $employerId
                )
                ->where(
                    'period_year',
                    $row->period_year
                )
                ->where(
                    'period_month',
                    $row->period_month
                )
                ->exists();

        if ($existing) {
            return false;
        }

        DB::table(
            'historical_member_service_periods'
        )
            ->insert([
                'member_id' =>
                    $memberId,

                'employer_id' =>
                    $employerId,

                'period_year' =>
                    $row->period_year,

                'period_month' =>
                    $row->period_month,

                'period_date' =>
                    $row->period_date,

                'service_status' =>
                    'break_in_service',

                'source_system' =>
                    'historical_migration',

                'historical_import_batch_id' =>
                    $batch->id,

                'source_row_number' =>
                    $row->source_row_number,

                'reason' =>
                    'Historical monthly salary, contribution and AVC cells were blank between earlier and later contribution activity.',

                'created_by' =>
                    $userId,

                'updated_by' =>
                    $userId,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Next PENERP Member Number
    |--------------------------------------------------------------------------
    */

    private function nextMemberNumber(): string
    {
        $recentNumbers =
            Member::query()
                ->where(
                    'member_number',
                    'like',
                    'PEN%'
                )
                ->orderByDesc('id')
                ->limit(500)
                ->pluck('member_number');

        $highest = 0;

        foreach ($recentNumbers as $memberNumber) {
            if (
                preg_match(
                    '/^PEN(\d+)$/i',
                    trim(
                        (string) $memberNumber
                    ),
                    $matches
                )
            ) {
                $highest =
                    max(
                        $highest,
                        (int) $matches[1]
                    );
            }
        }

        if ($highest <= 0) {
            $highest =
                (int) Member::query()
                    ->max('id');
        }

        $next =
            $highest + 1;

        do {
            $candidate =
                'PEN'
                .
                str_pad(
                    (string) $next,
                    8,
                    '0',
                    STR_PAD_LEFT
                );

            if (
                !Member::query()
                    ->where(
                        'member_number',
                        $candidate
                    )
                    ->exists()
            ) {
                return $candidate;
            }

            $next++;

        } while (true);
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Type
    |--------------------------------------------------------------------------
    */

    private function normaliseTransactionType(
        mixed $value
    ): string {
        $value =
            strtolower(
                trim(
                    (string) $value
                )
            );

        return match ($value) {
            'take_on',
            'take-on',
            'take on' =>
                'take_on',

            default =>
                'expected',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Membership Status
    |--------------------------------------------------------------------------
    */

    private function normaliseMembershipStatus(
        mixed $value
    ): string {
        return HistoricalMembershipStatus::normalize(
            $value
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Build Home Address
    |--------------------------------------------------------------------------
    */

    private function buildHomeAddress(
        HistoricalContributionImportRow $row
    ): ?string {
        $parts =
            array_filter([
                $this->clean(
                    $row->physical_address_1
                ),

                $this->clean(
                    $row->physical_address_2
                ),

                $this->clean(
                    $row->physical_address_3
                ),

                $this->clean(
                    $row->physical_suburb
                ),

                $this->clean(
                    $row->physical_city
                ),
            ]);

        return !empty($parts)
            ? implode(
                ', ',
                $parts
            )
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Decimal 4
    |--------------------------------------------------------------------------
    */

    private function nullableDecimal4(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            trim(
                (string) $value
            )
            ===
            ''
        ) {
            return null;
        }

        if (is_string($value)) {
            $value =
                str_replace(
                    [
                        ',',
                        '$',
                        ' ',
                    ],
                    '',
                    trim($value)
                );
        }

        if (!is_numeric($value)) {
            return null;
        }

        return number_format(
            (float) $value,
            4,
            '.',
            ''
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Decimal 6
    |--------------------------------------------------------------------------
    */

    private function nullableDecimal6(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            trim(
                (string) $value
            )
            ===
            ''
        ) {
            return null;
        }

        if (is_string($value)) {
            $value =
                str_replace(
                    [
                        ',',
                        '%',
                        ' ',
                    ],
                    '',
                    trim($value)
                );
        }

        if (!is_numeric($value)) {
            return null;
        }

        return number_format(
            (float) $value,
            6,
            '.',
            ''
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Clean String
    |--------------------------------------------------------------------------
    */

    private function clean(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value !== ''
            ? $value
            : null;
    }
}