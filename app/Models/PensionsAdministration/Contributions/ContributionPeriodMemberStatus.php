<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ContributionPeriodMemberStatus extends Model
{
    public const STATUS_CONTRIBUTOR = 'contributor';
    public const STATUS_NIL_CONTRIBUTOR = 'nil_contributor';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REINSTATEMENT = 'reinstatement';
    public const STATUS_EXITED = 'exited';

    protected $fillable = [
        'contribution_period_id',
        'member_id',
        'employer_id',
        'contribution_status',
        'reason',
        'import_batch_id',
    ];

    public static function rebuildForBatch(ContributionImportBatch $batch): void
    {
        $batch->loadMissing('contributionPeriod');

        $currentPeriod = $batch->contributionPeriod;

        if (!$currentPeriod) {
            return;
        }

        $previousPeriod = ContributionPeriod::query()
            ->where('employer_id', $batch->employer_id)
            ->where('period_date', '<', $currentPeriod->period_date)
            ->orderByDesc('period_date')
            ->first();

        $currentContributorIds = ContributionImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn('validation_status', ['valid', 'warning'])
            ->whereNotNull('matched_member_id')
            ->pluck('matched_member_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $previousContributorIds = collect();

        if ($previousPeriod) {
            $previousContributorIds = DB::table('member_contributions')
                ->where('contribution_period_id', $previousPeriod->id)
                ->where('employer_id', $batch->employer_id)
                ->whereNotNull('member_id')
                ->pluck('member_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        $previousStatuses = collect();

        if ($previousPeriod) {
            $previousStatuses = static::query()
                ->where('contribution_period_id', $previousPeriod->id)
                ->where('employer_id', $batch->employer_id)
                ->whereIn('contribution_status', [
                    self::STATUS_NIL_CONTRIBUTOR,
                    self::STATUS_SUSPENDED,
                ])
                ->get(['member_id', 'contribution_status'])
                ->keyBy(fn (self $status) => (int) $status->member_id);
        }

        static::query()
            ->where('contribution_period_id', $currentPeriod->id)
            ->where('employer_id', $batch->employer_id)
            ->where('import_batch_id', $batch->id)
            ->delete();

        $memberIds = $currentContributorIds
            ->merge($previousContributorIds)
            ->merge($previousStatuses->keys())
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        foreach ($memberIds as $memberId) {
            $contributesNow = $currentContributorIds->contains($memberId);
            $contributedLastMonth = $previousContributorIds->contains($memberId);
            $previousStatus = $previousStatuses->get($memberId)?->contribution_status;

            if ($contributesNow) {
                if (in_array($previousStatus, [self::STATUS_NIL_CONTRIBUTOR, self::STATUS_SUSPENDED], true)) {
                    $status = self::STATUS_REINSTATEMENT;
                    $reason = $previousStatus === self::STATUS_NIL_CONTRIBUTOR
                        ? 'Member resumed contributions after being a Nil Contributor in the previous period.'
                        : 'Member resumed contributions after being Suspended in the previous period.';
                } else {
                    $status = self::STATUS_CONTRIBUTOR;
                    $reason = 'Member contributed in the current period.';
                }

                static::updateOrCreate(
                    [
                        'contribution_period_id' => $currentPeriod->id,
                        'member_id' => $memberId,
                        'employer_id' => $batch->employer_id,
                    ],
                    [
                        'contribution_status' => $status,
                        'reason' => $reason,
                        'import_batch_id' => $batch->id,
                    ]
                );

                continue;
            }

            if ($contributedLastMonth) {
                static::updateOrCreate(
                    [
                        'contribution_period_id' => $currentPeriod->id,
                        'member_id' => $memberId,
                        'employer_id' => $batch->employer_id,
                    ],
                    [
                        'contribution_status' => self::STATUS_NIL_CONTRIBUTOR,
                        'reason' => 'Member contributed in the immediately previous period but has no contribution in the current period.',
                        'import_batch_id' => $batch->id,
                    ]
                );

                continue;
            }

            if (in_array($previousStatus, [self::STATUS_NIL_CONTRIBUTOR, self::STATUS_SUSPENDED], true)) {
                static::updateOrCreate(
                    [
                        'contribution_period_id' => $currentPeriod->id,
                        'member_id' => $memberId,
                        'employer_id' => $batch->employer_id,
                    ],
                    [
                        'contribution_status' => self::STATUS_SUSPENDED,
                        'reason' => $previousStatus === self::STATUS_NIL_CONTRIBUTOR
                            ? 'Member remained without contributions after being Nil in the previous period and is now Suspended.'
                            : 'Member remains Suspended because no contribution was received in the current period.',
                        'import_batch_id' => $batch->id,
                    ]
                );
            }
        }

        $nilCount = static::query()
            ->where('contribution_period_id', $currentPeriod->id)
            ->where('employer_id', $batch->employer_id)
            ->where('contribution_status', self::STATUS_NIL_CONTRIBUTOR)
            ->count();

        $batch->update(['nil_contributor_rows' => $nilCount]);
    }

    public function contributionPeriod(): BelongsTo
    {
        return $this->belongsTo(ContributionPeriod::class, 'contribution_period_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class, 'employer_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ContributionImportBatch::class, 'import_batch_id');
    }
}
