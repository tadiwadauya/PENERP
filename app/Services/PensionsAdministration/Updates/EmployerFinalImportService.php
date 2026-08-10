<?php

namespace App\Services\PensionsAdministration\Updates;

use App\Models\Audit\AuditTrail;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\EmployerImportBatch;
use App\Models\PensionsAdministration\Updates\EmployerImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EmployerFinalImportService
{
    public function import(EmployerImportBatch $batch): void
    {
        $approvedRows = EmployerImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn('review_decision', [
                'create',
                'update',
                'use_existing',
                'ignore_warning',
            ])
            ->whereNull('imported_at')
            ->orderBy('row_number')
            ->get();

        if ($approvedRows->isEmpty()) {
            throw new RuntimeException(
                'There are no approved employer records waiting to be imported.'
            );
        }

        $totalApproved = $approvedRows->count();
        $processed = 0;
        $imported = 0;

        $batch->update([
            'status' => 'importing',
            'progress_percentage' => 0,
            'failure_reason' => null,
            'started_at' => $batch->started_at ?? now(),
        ]);

        foreach ($approvedRows as $row) {
            try {
                DB::transaction(function () use (
                    $batch,
                    $row,
                    &$imported
                ) {
                    $data = $row->normalized_data ?? [];

                    $decision = $row->review_decision;

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE
                    |--------------------------------------------------------------------------
                    */

                    if (in_array($decision, ['create', 'ignore_warning'], true)) {
                        $employer = $this->createEmployer(
                            $batch,
                            $row,
                            $data
                        );

                        $row->update([
                            'imported_employer_id' => $employer->id,
                            'imported_at' => now(),
                        ]);

                        $this->writeAudit(
                            batch: $batch,
                            employer: $employer,
                            action: 'CREATE_EMPLOYER_FROM_IMPORT',
                            description: 'Employer '
                                . $employer->name
                                . ' was created from employer import batch '
                                . $batch->import_uuid
                                . '.',
                            row: $row
                        );

                        $imported++;

                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE EXISTING
                    |--------------------------------------------------------------------------
                    */

                    if ($decision === 'update') {
                        if (!$row->matched_employer_id) {
                            throw new RuntimeException(
                                'Excel row '
                                . $row->row_number
                                . ' was approved for update but has no matched employer.'
                            );
                        }

                        $employer = Employer::query()
                            ->findOrFail($row->matched_employer_id);

                        $oldValues = $employer->getAttributes();

                        $this->updateEmployer(
                            $batch,
                            $row,
                            $employer,
                            $data
                        );

                        $row->update([
                            'imported_employer_id' => $employer->id,
                            'imported_at' => now(),
                        ]);

                        $this->writeAudit(
                            batch: $batch,
                            employer: $employer,
                            action: 'UPDATE_EMPLOYER_FROM_IMPORT',
                            description: 'Employer '
                                . $employer->name
                                . ' was updated from employer import batch '
                                . $batch->import_uuid
                                . '.',
                            row: $row,
                            oldValues: $oldValues,
                            newValues: $employer->fresh()->getAttributes()
                        );

                        $imported++;

                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | USE EXISTING
                    |--------------------------------------------------------------------------
                    */

                    if ($decision === 'use_existing') {
                        if (!$row->matched_employer_id) {
                            throw new RuntimeException(
                                'Excel row '
                                . $row->row_number
                                . ' was marked Use Existing but no employer is linked.'
                            );
                        }

                        $employer = Employer::query()
                            ->findOrFail($row->matched_employer_id);

                        $row->update([
                            'imported_employer_id' => $employer->id,
                            'imported_at' => now(),
                        ]);

                        $this->writeAudit(
                            batch: $batch,
                            employer: $employer,
                            action: 'USE_EXISTING_EMPLOYER_FROM_IMPORT',
                            description: 'Employer import row '
                                . $row->row_number
                                . ' was linked to existing employer '
                                . $employer->name
                                . '.',
                            row: $row
                        );

                        $imported++;

                        return;
                    }

                    throw new RuntimeException(
                        'Unsupported employer import decision: '
                        . $decision
                    );
                });

                $processed++;

                $percentage = round(
                    ($processed / $totalApproved) * 100,
                    2
                );

                $batch->update([
                    'imported_rows' => $imported,
                    'progress_percentage' => $percentage,
                ]);

            } catch (Throwable $e) {
                throw new RuntimeException(
                    'Employer import failed on Excel row '
                    . $row->row_number
                    . ': '
                    . $e->getMessage(),
                    previous: $e
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Determine Whether Batch Is Fully Resolved
        |--------------------------------------------------------------------------
        */

        $pendingRows = EmployerImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->where('review_decision', 'pending')
            ->count();

        $batch->update([
            'imported_rows' => $batch->rows()
                ->whereNotNull('imported_at')
                ->count(),

            'progress_percentage' => 100,

            'status' => $pendingRows > 0
                ? 'awaiting_review'
                : 'completed',

            'completed_at' => $pendingRows > 0
                ? null
                : now(),
        ]);

        $this->writeBatchAudit(
            $batch,
            $pendingRows
        );
    }

    private function createEmployer(
        EmployerImportBatch $batch,
        EmployerImportRow $row,
        array $data
    ): Employer {
        /*
        |--------------------------------------------------------------------------
        | PenAd Number Becomes PENERP Employer Number
        |--------------------------------------------------------------------------
        */

        $penadNumber = $this->clean(
            $data['penad_employer_number'] ?? null
        );

        if (!$penadNumber) {
            throw new RuntimeException(
                'PenAd employer number is required because it is used as the PENERP employer number for migrated employers.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Checks Before Insert
        |--------------------------------------------------------------------------
        */

        $existingPenerp = Employer::query()
            ->withTrashed()
            ->where('employer_number', $penadNumber)
            ->first();

        if ($existingPenerp) {
            throw new RuntimeException(
                'PENERP employer number '
                . $penadNumber
                . ' already exists.'
            );
        }

        $existingPenad = Employer::query()
            ->withTrashed()
            ->where('penad_employer_number', $penadNumber)
            ->first();

        if ($existingPenad) {
            throw new RuntimeException(
                'PenAd employer number '
                . $penadNumber
                . ' already exists.'
            );
        }

        if (!$row->matched_employer_group_id) {
            throw new RuntimeException(
                'A valid employer group is required.'
            );
        }

        $isActive = (bool) ($data['is_active'] ?? true);

        return Employer::create([
            'employer_number' => $penadNumber,

            'penad_employer_number' => $penadNumber,

            'fundworx_employer_number' => $this->clean(
                $data['fundworx_employer_number'] ?? null
            ),

            'employer_group_id' => $row->matched_employer_group_id,

            'name' => $this->clean(
                $data['employer_name'] ?? null
            ),

            'short_name' => $this->clean(
                $data['short_name'] ?? null
            ),

            'email' => $this->clean(
                $data['email'] ?? null
            ),

            'telephone' => $this->clean(
                $data['telephone'] ?? null
            ),

            'physical_address' => $this->buildPhysicalAddress(
                $data
            ),

            'postal_address' => $this->buildPostalAddress(
                $data
            ),

            'status' => $isActive
                ? 'active'
                : 'inactive',

            'is_active' => $isActive,

            'created_by' => $batch->approved_by
                ?? $batch->uploaded_by,

            'updated_by' => $batch->approved_by
                ?? $batch->uploaded_by,
        ]);
    }

    private function updateEmployer(
        EmployerImportBatch $batch,
        EmployerImportRow $row,
        Employer $employer,
        array $data
    ): void {
        $penadNumber = $this->clean(
            $data['penad_employer_number'] ?? null
        );

        /*
        |--------------------------------------------------------------------------
        | Existing Migrated Employer Number
        |--------------------------------------------------------------------------
        |
        | Where the import supplies a PenAd number, keep PENERP aligned to it.
        |
        */

        if ($penadNumber) {
            $conflict = Employer::query()
                ->withTrashed()
                ->where('employer_number', $penadNumber)
                ->where('id', '<>', $employer->id)
                ->exists();

            if ($conflict) {
                throw new RuntimeException(
                    'Employer number '
                    . $penadNumber
                    . ' belongs to another employer.'
                );
            }
        }

        $isActive = (bool) ($data['is_active'] ?? $employer->is_active);

        $employer->update([
            'employer_number' => $penadNumber
                ?: $employer->employer_number,

            'penad_employer_number' => $penadNumber
                ?: $employer->penad_employer_number,

            'fundworx_employer_number' => $this->clean(
                $data['fundworx_employer_number'] ?? null
            ) ?: $employer->fundworx_employer_number,

            'employer_group_id' => $row->matched_employer_group_id
                ?: $employer->employer_group_id,

            'name' => $this->clean(
                $data['employer_name'] ?? null
            ) ?: $employer->name,

            'short_name' => $this->clean(
                $data['short_name'] ?? null
            ),

            'email' => $this->clean(
                $data['email'] ?? null
            ),

            'telephone' => $this->clean(
                $data['telephone'] ?? null
            ),

            'physical_address' => $this->buildPhysicalAddress($data)
                ?: $employer->physical_address,

            'postal_address' => $this->buildPostalAddress($data)
                ?: $employer->postal_address,

            'status' => $isActive
                ? 'active'
                : 'inactive',

            'is_active' => $isActive,

            'updated_by' => $batch->approved_by
                ?? $batch->uploaded_by,
        ]);
    }

    private function buildPhysicalAddress(array $data): ?string
    {
        return $this->combineAddress([
            $data['physical_address_1'] ?? null,
            $data['physical_address_2'] ?? null,
            $data['physical_address_3'] ?? null,
            $data['physical_suburb'] ?? null,
            $data['physical_city'] ?? null,
            $data['physical_country'] ?? null,
        ]);
    }

    private function buildPostalAddress(array $data): ?string
    {
        return $this->combineAddress([
            $data['postal_address_1'] ?? null,
            $data['postal_address_2'] ?? null,
            $data['postal_address_3'] ?? null,
            $data['postal_city'] ?? null,
            $data['postal_country'] ?? null,
        ]);
    }

    private function combineAddress(array $parts): ?string
    {
        $parts = array_map(
            fn ($value) => $this->clean($value),
            $parts
        );

        $parts = array_values(
            array_filter($parts)
        );

        return $parts
            ? implode(', ', $parts)
            : null;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(
            preg_replace(
                '/\s+/',
                ' ',
                (string) $value
            )
        );

        return $value !== ''
            ? $value
            : null;
    }

    private function writeAudit(
        EmployerImportBatch $batch,
        Employer $employer,
        string $action,
        string $description,
        EmployerImportRow $row,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        AuditTrail::create([
            'event_uuid' => (string) Str::uuid(),

            'user_id' => $batch->approved_by
                ?? $batch->uploaded_by,

            'session_id' => null,

            'event_type' => 'employer_import',

            'module' => 'Pensions Administration - Updates',

            'action' => $action,

            'auditable_type' => Employer::class,

            'auditable_id' => (string) $employer->id,

            'description' => $description,

            'old_values' => $oldValues,

            'new_values' => $newValues
                ?? $employer->getAttributes(),

            'metadata' => [
                'import_batch_id' => $batch->id,
                'import_uuid' => $batch->import_uuid,
                'excel_row' => $row->row_number,
                'review_decision' => $row->review_decision,
            ],

            'route_name' => null,
            'url' => null,
            'http_method' => null,
            'ip_address' => null,
            'user_agent' => null,
            'device_identifier' => 'Background Import',

            'outcome' => 'success',

            'failure_reason' => null,

            'occurred_at' => now(),
        ]);
    }

    private function writeBatchAudit(
        EmployerImportBatch $batch,
        int $pendingRows
    ): void {
        AuditTrail::create([
            'event_uuid' => (string) Str::uuid(),

            'user_id' => $batch->approved_by
                ?? $batch->uploaded_by,

            'session_id' => null,

            'event_type' => 'employer_import',

            'module' => 'Pensions Administration - Updates',

            'action' => 'COMPLETE_EMPLOYER_IMPORT',

            'auditable_type' => EmployerImportBatch::class,

            'auditable_id' => (string) $batch->id,

            'description' => $pendingRows > 0
                ? 'Approved employers were imported. Some rows remain pending review.'
                : 'Employer import completed successfully.',

            'old_values' => null,

            'new_values' => [
                'total_rows' => $batch->total_rows,
                'approved_rows' => $batch->approved_rows,
                'imported_rows' => $batch->imported_rows,
                'rejected_rows' => $batch->rejected_rows,
                'pending_rows' => $pendingRows,
                'status' => $batch->status,
            ],

            'metadata' => [
                'import_uuid' => $batch->import_uuid,
                'filename' => $batch->original_filename,
            ],

            'route_name' => null,
            'url' => null,
            'http_method' => null,
            'ip_address' => null,
            'user_agent' => null,
            'device_identifier' => 'Background Import',

            'outcome' => 'success',

            'occurred_at' => now(),
        ]);
    }
}