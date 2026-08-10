<?php

namespace App\Services\PensionsAdministration\Updates;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Models\PensionsAdministration\Updates\MembershipImportRow;

class MembershipImportRowValidationService
{
    public function revalidate(
        MembershipImportRow $row,
        ?array $replacementData = null
    ): MembershipImportRow {
        $batch = $row->batch;

        $data = $replacementData ?? $row->normalized_data ?? [];

        $data = $this->normalize($data);

        $errors = [];
        $warnings = [];
        $duplicateReasons = [];

        $matchedEmployerId = null;
        $matchedMemberId = null;
        $duplicateStatus = 'none';
        $duplicateScore = null;

        /*
        |--------------------------------------------------------------------------
        | Basic Validation
        |--------------------------------------------------------------------------
        */

        if (empty($data['surname'])) {
            $errors[] = 'Surname is required.';
        }

        if (empty($data['first_names'])) {
            $errors[] = 'First names are required.';
        }

        if (empty($data['membership_status'])) {
            $errors[] = 'Membership status is required.';
        } elseif (!in_array($data['membership_status'], [
            'active',
            'dormant',
            'inactive',
            'suspended',
        ], true)) {
            $errors[] = 'Invalid membership status.';
        }

        if (
            ($data['membership_status'] ?? null) === 'active'
            && empty($data['national_id_normalized'])
        ) {
            $errors[] = 'National ID is required for an active member.';
        }

        if (
            !empty($data['email'])
            && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'Primary email address is invalid.';
        }

        if (
            !empty($data['secondary_email'])
            && !filter_var($data['secondary_email'], FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'Secondary email address is invalid.';
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve Employer
        |--------------------------------------------------------------------------
        */

        if ($batch->employer_id) {
            $matchedEmployerId = (int) $batch->employer_id;
        } else {
            $employerMatches = [];

            if (!empty($data['penerp_employer_number'])) {
                $employer = Employer::query()
                    ->where('employer_number', $data['penerp_employer_number'])
                    ->first();

                if ($employer) {
                    $employerMatches[] = $employer->id;
                }
            }

            if (!empty($data['penad_employer_number'])) {
                $employer = Employer::query()
                    ->where('penad_employer_number', $data['penad_employer_number'])
                    ->first();

                if ($employer) {
                    $employerMatches[] = $employer->id;
                }
            }

            if (!empty($data['fundworx_employer_number'])) {
                $employer = Employer::query()
                    ->where('fundworx_employer_number', $data['fundworx_employer_number'])
                    ->first();

                if ($employer) {
                    $employerMatches[] = $employer->id;
                }
            }

            $employerMatches = array_values(
                array_unique(
                    array_filter($employerMatches)
                )
            );

            if (count($employerMatches) > 1) {
                $errors[] = 'The supplied employer references resolve to different employers.';
            } elseif (count($employerMatches) === 1) {
                $matchedEmployerId = (int) $employerMatches[0];
            }
        }

        if (
            ($data['membership_status'] ?? null) === 'active'
            && !$matchedEmployerId
        ) {
            $errors[] = 'An active member must have a valid employer.';
        }

        /*
        |--------------------------------------------------------------------------
        | Vote Number
        |--------------------------------------------------------------------------
        */

        if ($matchedEmployerId) {
            $employer = Employer::query()
                ->with('employerGroup')
                ->find($matchedEmployerId);

            if (
                $employer?->employerGroup?->vote_number_required
                && empty($data['vote_number'])
            ) {
                $errors[] = 'Vote number is required for this employer group.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Member Duplicate Detection
        |--------------------------------------------------------------------------
        */

        $memberMatches = [];

        if (!empty($data['penerp_member_number'])) {
            $match = Member::query()
                ->where('member_number', $data['penerp_member_number'])
                ->first();

            if ($match) {
                $memberMatches[] = $match->id;
                $duplicateReasons[] = 'PENERP member number already exists.';
            }
        }

        if (!empty($data['national_id_normalized'])) {
            $match = Member::query()
                ->where('national_id_normalized', $data['national_id_normalized'])
                ->first();

            if ($match) {
                $memberMatches[] = $match->id;
                $duplicateReasons[] = 'National ID already exists.';
            }
        }

        if (!empty($data['penad_member_number'])) {
            $match = Member::query()
                ->where('penad_member_number', $data['penad_member_number'])
                ->first();

            if ($match) {
                $memberMatches[] = $match->id;
                $duplicateReasons[] = 'PenAd member number already exists.';
            }
        }

        if (!empty($data['fundworx_member_number'])) {
            $match = Member::query()
                ->where('fundworx_member_number', $data['fundworx_member_number'])
                ->first();

            if ($match) {
                $memberMatches[] = $match->id;
                $duplicateReasons[] = 'Fundworx member number already exists.';
            }
        }

        $memberMatches = array_values(
            array_unique(
                array_filter($memberMatches)
            )
        );

        if (count($memberMatches) > 1) {
            $errors[] = 'The supplied references match different existing members.';
        } elseif (count($memberMatches) === 1) {
            $matchedMemberId = $memberMatches[0];
            $duplicateStatus = 'exact';
            $duplicateScore = 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate Inside Same Import File
        |--------------------------------------------------------------------------
        */

        if (!empty($data['national_id_normalized'])) {
            $duplicateRow = MembershipImportRow::query()
                ->where('import_batch_id', $row->import_batch_id)
                ->where('id', '<>', $row->id)
                ->get()
                ->first(function ($otherRow) use ($data) {
                    return ($otherRow->normalized_data['national_id_normalized'] ?? null)
                        === $data['national_id_normalized'];
                });

            if ($duplicateRow) {
                $duplicateStatus = 'exact';
                $duplicateScore = 100;

                $duplicateReasons[] =
                    'National ID is repeated in this file on Excel row '
                    . $duplicateRow->row_number
                    . '.';
            }
        }

        if (!empty($data['penad_member_number'])) {
            $duplicateRow = MembershipImportRow::query()
                ->where('import_batch_id', $row->import_batch_id)
                ->where('id', '<>', $row->id)
                ->get()
                ->first(function ($otherRow) use ($data) {
                    return ($otherRow->normalized_data['penad_member_number'] ?? null)
                        === $data['penad_member_number'];
                });

            if ($duplicateRow) {
                $duplicateStatus = 'exact';
                $duplicateScore = 100;

                $duplicateReasons[] =
                    'PenAd member number is repeated in this file on Excel row '
                    . $duplicateRow->row_number
                    . '.';
            }
        }

        if (!empty($data['fundworx_member_number'])) {
            $duplicateRow = MembershipImportRow::query()
                ->where('import_batch_id', $row->import_batch_id)
                ->where('id', '<>', $row->id)
                ->get()
                ->first(function ($otherRow) use ($data) {
                    return ($otherRow->normalized_data['fundworx_member_number'] ?? null)
                        === $data['fundworx_member_number'];
                });

            if ($duplicateRow) {
                $duplicateStatus = 'exact';
                $duplicateScore = 100;

                $duplicateReasons[] =
                    'Fundworx member number is repeated in this file on Excel row '
                    . $duplicateRow->row_number
                    . '.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Staff Number
        |--------------------------------------------------------------------------
        */

        if ($matchedEmployerId && !empty($data['staff_number'])) {
            $employment = MemberEmployment::query()
                ->where('employer_id', $matchedEmployerId)
                ->where('staff_number', $data['staff_number'])
                ->where('is_current', true)
                ->first();

            if (
                $employment
                && (!$matchedMemberId || (int) $employment->member_id !== (int) $matchedMemberId)
            ) {
                $errors[] =
                    'Staff number is already assigned to another current member under this employer.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate Warning
        |--------------------------------------------------------------------------
        */

        $duplicateReasons = array_values(
            array_unique($duplicateReasons)
        );

        if ($duplicateReasons && !$errors) {
            $warnings[] =
                'A duplicate or existing membership reference requires review.';
        }

        /*
        |--------------------------------------------------------------------------
        | Determine Status
        |--------------------------------------------------------------------------
        */

        if ($errors) {
            $validationStatus = 'error';
        } elseif ($warnings) {
            $validationStatus = 'warning';
        } else {
            $validationStatus = 'valid';
        }

        /*
        |--------------------------------------------------------------------------
        | Reset Decision After Correction
        |--------------------------------------------------------------------------
        */

        $row->update([
            'normalized_data' => $data,
            'validation_status' => $validationStatus,
            'error_messages' => $errors ?: null,
            'warning_messages' => $warnings ?: null,

            'matched_employer_id' => $matchedEmployerId,

            'duplicate_status' => $duplicateStatus,
            'matched_member_id' => $matchedMemberId,
            'duplicate_score' => $duplicateScore,
            'duplicate_reasons' => $duplicateReasons ?: null,

            'review_decision' => 'pending',
            'review_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        return $row->fresh([
            'matchedEmployer',
            'matchedMember',
        ]);
    }

    private function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value) ?: null;
            }
        }

        $data['membership_status'] = !empty($data['membership_status'])
            ? strtolower(trim($data['membership_status']))
            : null;

        $data['import_action'] = strtoupper(
            trim($data['import_action'] ?? 'AUTO')
        );

        $data['email'] = !empty($data['email'])
            ? strtolower(trim($data['email']))
            : null;

        $data['secondary_email'] = !empty($data['secondary_email'])
            ? strtolower(trim($data['secondary_email']))
            : null;

        $data['national_id_normalized'] = Member::normalizeNationalId(
            $data['national_id'] ?? null
        );

        return $data;
    }
}