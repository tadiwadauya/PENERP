<?php

namespace App\Services\PensionsAdministration\HistoricalContributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use Illuminate\Support\Collection;

class HistoricalContributionMemberMatcher
{
    /*
    |--------------------------------------------------------------------------
    | Lookup Maps
    |--------------------------------------------------------------------------
    */

    private array $employerByPenadNumber = [];

    private array $membersByPenerpNumber = [];

    private array $membersByPenadNumber = [];

    private array $membersByFundworxNumber = [];

    private array $membersByNationalId = [];

    /*
    |--------------------------------------------------------------------------
    | Staff Number Map
    |--------------------------------------------------------------------------
    |
    | key:
    |
    | employer_id|staff_number
    |
    | value:
    |
    | [
    |     member_id => [
    |         'member_id' => ...,
    |         'is_active' => ...,
    |     ],
    | ]
    |
    */

    private array $membersByEmployerStaffNumber = [];

    private array $membersById = [];

    private bool $initialised = false;

    /*
    |--------------------------------------------------------------------------
    | Initialise
    |--------------------------------------------------------------------------
    */

    public function initialise(): void
    {
        if ($this->initialised) {
            return;
        }

        $this->loadEmployers();

        $this->loadMembers();

        $this->loadStaffNumbers();

        $this->initialised = true;
    }

    /*
    |--------------------------------------------------------------------------
    | Match
    |--------------------------------------------------------------------------
    */

    public function match(
        array $data
    ): array {
        $this->initialise();

        /*
        |--------------------------------------------------------------------------
        | Employer
        |--------------------------------------------------------------------------
        */

        $employerMatch =
            $this->matchEmployer(
                $data
            );

        if (
            $employerMatch['status']
            !==
            'matched'
        ) {
            return [
                'employer' =>
                    null,

                'employer_match_type' =>
                    null,

                'member' =>
                    null,

                'member_match_type' =>
                    null,

                'is_new_member' =>
                    false,

                'ambiguous' =>
                    false,

                'error' =>
                    $employerMatch['message'],
            ];
        }

        /** @var Employer $employer */
        $employer =
            $employerMatch['employer'];

        // PenAd is the authoritative membership identifier. Other identifiers
        // describe the person, but must never select a different membership.
        $penad = $this->clean($data['penad_member_number'] ?? $data['legacy_member_number'] ?? null);
        if (!$penad) {
            return [
                'employer' => $employer,
                'employer_match_type' => 'penad_employer_number',
                'member' => null,
                'member_match_type' => 'missing_penad_member_number',
                'is_new_member' => false,
                'ambiguous' => false,
                'error' => 'PenAd Member Number is mandatory. This historical member cannot be posted.',
            ];
        }

        $memberIds = array_values(array_unique(array_map('intval',
            $this->membersByPenadNumber[$this->normalizeReference($penad)] ?? []
        )));

        if (count($memberIds) > 1) {
            return [
                'employer' => $employer,
                'employer_match_type' => 'penad_employer_number',
                'member' => null,
                'member_match_type' => 'duplicate_penad_member_number',
                'is_new_member' => false,
                'ambiguous' => true,
                'error' => "PenAd Member Number {$penad} is assigned to multiple PENERP members. Resolve this duplicate before posting.",
            ];
        }

        if (count($memberIds) === 1) {
            $member = $this->membersById[$memberIds[0]] ?? null;
            if (!$member) {
                return [
                    'employer' => $employer,
                    'employer_match_type' => 'penad_employer_number',
                    'member' => null,
                    'member_match_type' => 'identifier_conflict',
                    'is_new_member' => false,
                    'ambiguous' => true,
                    'error' => "PenAd Member Number {$penad} refers to a member that could not be loaded.",
                ];
            }

            return [
                'employer' => $employer,
                'employer_match_type' => 'penad_employer_number',
                'member' => $member,
                'member_match_type' => 'penad_member_number',
                'is_new_member' => false,
                'ambiguous' => false,
                'error' => null,
            ];
        }

        return [
            'employer' => $employer,
            'employer_match_type' => 'penad_employer_number',
            'member' => null,
            'member_match_type' => 'new_penad_membership',
            'is_new_member' => true,
            'ambiguous' => false,
            'error' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Match Staff Number
    |--------------------------------------------------------------------------
    */

    private function matchStaffNumber(
        Employer $employer,
        string $staffNumber,
        mixed $sourceStatus
    ): array {
        $key =
            $this->makeEmployerStaffKey(
                employerId:
                    $employer->id,

                staffNumber:
                    $staffNumber
            );

        $holders =
            $this->membersByEmployerStaffNumber[
                $key
            ]
            ??
            [];

        if (
            empty(
                $holders
            )
        ) {
            return [
                'member' =>
                    null,

                'match_type' =>
                    null,

                'active_conflict' =>
                    false,

                'reused_historically' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Single Historical Holder
        |--------------------------------------------------------------------------
        */

        if (
            count(
                $holders
            )
            ===
            1
        ) {
            $holder =
                reset(
                    $holders
                );

            $member =
                $this->membersById[
                    $holder['member_id']
                ]
                ??
                null;

            return [
                'member' =>
                    $member,

                'match_type' =>
                    'staff_number_employer',

                'active_conflict' =>
                    false,

                'reused_historically' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Active Holders
        |--------------------------------------------------------------------------
        */

        $activeHolders =
            array_filter(
                $holders,
                fn (array $holder) =>
                    (bool) $holder['is_active']
            );

        /*
        |--------------------------------------------------------------------------
        | Two Active Holders = Genuine Duplicate / Conflict
        |--------------------------------------------------------------------------
        */

        if (
            count(
                $activeHolders
            )
            >
            1
        ) {
            return [
                'member' =>
                    null,

                'match_type' =>
                    null,

                'active_conflict' =>
                    true,

                'reused_historically' =>
                    false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Active Source Row + One Active Holder
        |--------------------------------------------------------------------------
        */

        if (
            $this->statusIsActive(
                $sourceStatus
            )
            &&
            count(
                $activeHolders
            )
            ===
            1
        ) {
            $holder =
                reset(
                    $activeHolders
                );

            return [
                'member' =>
                    $this->membersById[
                        $holder['member_id']
                    ]
                    ??
                    null,

                'match_type' =>
                    'staff_number_active_holder',

                'active_conflict' =>
                    false,

                'reused_historically' =>
                    true,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Staff Number Was Reused Historically
        |--------------------------------------------------------------------------
        */

        return [
            'member' =>
                null,

            'match_type' =>
                'staff_number_reused_historically',

            'active_conflict' =>
                false,

            'reused_historically' =>
                true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Match Employer
    |--------------------------------------------------------------------------
    */

    private function matchEmployer(
        array $data
    ): array {
        $penadEmployerNumber =
            $this->clean(
                $data['penad_employer_number']
                ??
                $data['employer_number']
                ??
                null
            );

        if (!$penadEmployerNumber) {
            return [
                'status' =>
                    'not_found',

                'employer' =>
                    null,

                'match_type' =>
                    null,

                'message' =>
                    'PenAd Employer Number is required for historical contribution matching.',
            ];
        }

        $key =
            $this->normalizeReference(
                $penadEmployerNumber
            );

        if (
            isset(
                $this->employerByPenadNumber[
                    $key
                ]
            )
        ) {
            return [
                'status' =>
                    'matched',

                'employer' =>
                    $this->employerByPenadNumber[
                        $key
                    ],

                'match_type' =>
                    'penad_employer_number',

                'message' =>
                    null,
            ];
        }

        return [
            'status' =>
                'not_found',

            'employer' =>
                null,

            'match_type' =>
                null,

            'message' =>
                'PenAd Employer Number '
                . $penadEmployerNumber
                . ' does not match any employer in PENERP.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Load Employers
    |--------------------------------------------------------------------------
    */

    private function loadEmployers(): void
    {
        Employer::query()
            ->select([
                'id',
                'penad_employer_number',
                'name',
                'is_active',
            ])
            ->whereNotNull(
                'penad_employer_number'
            )
            ->get()
            ->each(
                function (
                    Employer $employer
                ): void {
                    $penad =
                        $this->clean(
                            $employer->penad_employer_number
                        );

                    if (!$penad) {
                        return;
                    }

                    $this->employerByPenadNumber[
                        $this->normalizeReference(
                            $penad
                        )
                    ] =
                        $employer;
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Load Members
    |--------------------------------------------------------------------------
    |
    | No per-row member database query is required after this.
    |
    */

    private function loadMembers(): void
    {
        Member::query()
            ->select([
                'id',
                'member_number',
                'penad_member_number',
                'fundworx_member_number',
                'surname',
                'first_names',
                'other_names',
                'maiden_name',
                'national_id',
                'national_id_normalized',
                'date_of_birth',
                'date_joined_fund',
                'membership_status',
                'is_active',
            ])
            ->chunkById(
                2000,
                function (
                    Collection $members
                ): void {
                    foreach (
                        $members
                        as $member
                    ) {
                        $memberId =
                            (int) $member->id;

                        $this->membersById[
                            $memberId
                        ] =
                            $member;

                        /*
                        |--------------------------------------------------------------------------
                        | PENERP
                        |--------------------------------------------------------------------------
                        */

                        $penerp =
                            $this->clean(
                                $member->member_number
                            );

                        if ($penerp) {
                            $this->appendMemberId(
                                map:
                                    $this->membersByPenerpNumber,

                                key:
                                    $this->normalizeReference(
                                        $penerp
                                    ),

                                memberId:
                                    $memberId
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | PenAd
                        |--------------------------------------------------------------------------
                        */

                        $penad =
                            $this->clean(
                                $member->penad_member_number
                            );

                        if ($penad) {
                            $this->appendMemberId(
                                map:
                                    $this->membersByPenadNumber,

                                key:
                                    $this->normalizeReference(
                                        $penad
                                    ),

                                memberId:
                                    $memberId
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Fundworx
                        |--------------------------------------------------------------------------
                        */

                        $fundworx =
                            $this->clean(
                                $member->fundworx_member_number
                            );

                        if ($fundworx) {
                            $this->appendMemberId(
                                map:
                                    $this->membersByFundworxNumber,

                                key:
                                    $this->normalizeReference(
                                        $fundworx
                                    ),

                                memberId:
                                    $memberId
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | National ID
                        |--------------------------------------------------------------------------
                        */

                        $nationalId =
                            $member->national_id_normalized
                            ?:
                            Member::normalizeNationalId(
                                $member->national_id
                            );

                        if ($nationalId) {
                            $this->appendMemberId(
                                map:
                                    $this->membersByNationalId,

                                key:
                                    $nationalId,

                                memberId:
                                    $memberId
                            );
                        }
                    }
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Load Employer Staff Numbers
    |--------------------------------------------------------------------------
    */

    private function loadStaffNumbers(): void
    {
        MemberEmployment::query()
            ->select([
                'id',
                'member_id',
                'employer_id',
                'staff_number',
                'employment_status',
                'is_current',
            ])
            ->whereNotNull(
                'staff_number'
            )
            ->chunkById(
                2000,
                function (
                    Collection $employments
                ): void {
                    foreach (
                        $employments
                        as $employment
                    ) {
                        $staffNumber =
                            $this->clean(
                                $employment->staff_number
                            );

                        if (!$staffNumber) {
                            continue;
                        }

                        $memberId =
                            (int) $employment->member_id;

                        $member =
                            $this->membersById[
                                $memberId
                            ]
                            ??
                            null;

                        if (!$member) {
                            continue;
                        }

                        $key =
                            $this->makeEmployerStaffKey(
                                employerId:
                                    (int) $employment->employer_id,

                                staffNumber:
                                    $staffNumber
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Determine Active / Current
                        |--------------------------------------------------------------------------
                        */

                        $isActive =
                            (bool) $employment->is_current
                            ||
                            (bool) $member->is_active
                            ||
                            $this->statusIsActive(
                                $employment->employment_status
                            )
                            ||
                            $this->statusIsActive(
                                $member->membership_status
                            );

                        $this->membersByEmployerStaffNumber[
                            $key
                        ][
                            $memberId
                        ] =
                            [
                                'member_id' =>
                                    $memberId,

                                'is_active' =>
                                    $isActive,
                            ];
                    }
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Strong Identifier Match Helper
    |--------------------------------------------------------------------------
    */

    private function addMemberIdentifierMatches(
        array &$matches,
        string $type,
        array $memberIds
    ): void {
        if (
            empty(
                $memberIds
            )
        ) {
            return;
        }

        $matches[
            $type
        ] =
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        $memberIds
                    )
                )
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Match Type
    |--------------------------------------------------------------------------
    */

    private function determineMemberMatchType(
        array $matches,
        int $memberId
    ): string {
        foreach (
            [
                'penad_member_number',
                'penerp_member_number',
                'fundworx_member_number',
                'national_id',
            ]
            as $type
        ) {
            if (
                isset(
                    $matches[
                        $type
                    ]
                )
                &&
                in_array(
                    $memberId,
                    $matches[
                        $type
                    ],
                    true
                )
            ) {
                return $type;
            }
        }

        return 'matched';
    }

    /*
    |--------------------------------------------------------------------------
    | Append Member ID
    |--------------------------------------------------------------------------
    */

    private function appendMemberId(
        array &$map,
        string $key,
        int $memberId
    ): void {
        if (
            !isset(
                $map[
                    $key
                ]
            )
        ) {
            $map[
                $key
            ] =
                [];
        }

        if (
            !in_array(
                $memberId,
                $map[
                    $key
                ],
                true
            )
        ) {
            $map[
                $key
            ][] =
                $memberId;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Staff Key
    |--------------------------------------------------------------------------
    */

    private function makeEmployerStaffKey(
        int $employerId,
        string $staffNumber
    ): string {
        return
            $employerId
            .
            '|'
            .
            $this->normalizeReference(
                $staffNumber
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Active Status
    |--------------------------------------------------------------------------
    */

    private function statusIsActive(
        mixed $value
    ): bool {
        $value =
            strtolower(
                trim(
                    (string) (
                        $value
                        ??
                        ''
                    )
                )
            );

        return in_array(
            $value,
            [
                'active',
                'current',
                'contributing',
                'in service',
                'in-service',
            ],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Reference
    |--------------------------------------------------------------------------
    */

    private function normalizeReference(
        mixed $value
    ): string {
        return strtoupper(
            trim(
                (string) $value
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Clean
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

        return
            $value !== ''
                ? $value
                : null;
    }
}