<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\Contributions\ProcessContributionImport;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriod;
use App\Models\PensionsAdministration\Updates\Employer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ContributionImportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Import List
    |--------------------------------------------------------------------------
    */

    public function index(): View
{
    $employers = Employer::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    $batches = ContributionImportBatch::query()
        ->with([
            'employer',
            'contributionPeriod',
            'uploadedBy',
        ])
        ->when(
            request()->filled('employer_id'),
            function ($query) {
                $query->where(
                    'employer_id',
                    request('employer_id')
                );
            }
        )
        ->when(
            request()->filled('status'),
            function ($query) {
                $query->where(
                    'status',
                    request('status')
                );
            }
        )
        ->when(
            request()->filled('currency_code'),
            function ($query) {
                $query->where(
                    'currency_code',
                    request('currency_code')
                );
            }
        )
        ->latest('id')
        ->paginate(25)
        ->withQueryString();

    return view(
        'pensions-administration.contributions.imports.index',
        compact(
            'batches',
            'employers'
        )
    );
}


    /*
    |--------------------------------------------------------------------------
    | Upload Form
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        $employers = Employer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $defaultPeriod = now()->startOfMonth();

        return view(
            'pensions-administration.contributions.imports.create',
            compact(
                'employers',
                'defaultPeriod'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Contribution Import
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'employer_id' => [
                'required',
                'integer',
                'exists:employers,id',
            ],

            'period_month' => [
                'required',
                'integer',
                'between:1,12',
            ],

            'period_year' => [
                'required',
                'integer',
                'between:2000,2100',
            ],

            'due_date' => [
                'nullable',
                'date',
            ],

            'currency_code' => [
                'required',
                'string',
                'in:ZWG,USD',
            ],

            'exchange_rate_to_base' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'scheme_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | The Blade uses:
            |
            | name="import_file"
            |
            | Therefore every controller reference must also use import_file.
            |
            */

            'import_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:51200',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Employer
        |--------------------------------------------------------------------------
        */

        $employer = Employer::query()
            ->findOrFail(
                $validated['employer_id']
            );


        /*
        |--------------------------------------------------------------------------
        | Contribution Period
        |--------------------------------------------------------------------------
        */

        $periodYear = (int) $validated['period_year'];
        $periodMonth = (int) $validated['period_month'];

        $periodDate = Carbon::create(
            $periodYear,
            $periodMonth,
            1
        )
            ->endOfMonth()
            ->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Due Date
        |--------------------------------------------------------------------------
        |
        | If no due date is entered manually, PENERP initially uses the end
        | of the selected contribution month.
        |
        | The Excel validator can still validate this against the schedule.
        |
        */

        $dueDate = !empty($validated['due_date'])
            ? Carbon::parse(
                $validated['due_date']
            )->startOfDay()
            : $periodDate->copy();


        /*
        |--------------------------------------------------------------------------
        | Period / Due Date Integrity
        |--------------------------------------------------------------------------
        |
        | Prevent situations such as:
        |
        | Period: November 2004
        | Due Date: 30 November 2025
        |
        */

        if (
            $dueDate->year !== $periodYear
            ||
            $dueDate->month !== $periodMonth
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'due_date' =>
                        'The selected contribution period is '
                        . $periodDate->format('F Y')
                        . ', but the due date is '
                        . $dueDate->format('d M Y')
                        . '. The contribution period and due date must be in the same month and year.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        */

        $currencyCode = strtoupper(
            trim(
                $validated['currency_code']
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Exchange Rate
        |--------------------------------------------------------------------------
        |
        | ZWG is the base currency.
        |
        */

        $exchangeRate = $currencyCode === 'ZWG'
            ? 1
            : (
                isset($validated['exchange_rate_to_base'])
                    ? (float) $validated['exchange_rate_to_base']
                    : null
            );


        /*
        |--------------------------------------------------------------------------
        | USD Exchange Rate Requirement
        |--------------------------------------------------------------------------
        */

        if (
            $currencyCode === 'USD'
            &&
            !$exchangeRate
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'exchange_rate_to_base' =>
                        'An exchange rate is required when importing USD contributions.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Uploaded Excel File
        |--------------------------------------------------------------------------
        */

        $uploadedFile = $request->file('import_file');


        /*
        |--------------------------------------------------------------------------
        | File Integrity
        |--------------------------------------------------------------------------
        */

        if (
            !$uploadedFile
            ||
            !$uploadedFile->isValid()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'import_file' =>
                        'The contribution Excel file could not be received correctly. Please select the file again and retry.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Import UUID
        |--------------------------------------------------------------------------
        */

        $importUuid = (string) Str::uuid();


        /*
        |--------------------------------------------------------------------------
        | File Information
        |--------------------------------------------------------------------------
        */

        $extension = strtolower(
            $uploadedFile->getClientOriginalExtension()
        );

        $storedFilename = strtolower(
            $importUuid
        )
            . '.'
            . $extension;

        $directory = 'contribution-imports';

        $filePath = $directory
            . '/'
            . $storedFilename;


        /*
        |--------------------------------------------------------------------------
        | File Hash
        |--------------------------------------------------------------------------
        */

        $realPath = $uploadedFile->getRealPath();

        if (!$realPath) {
            return back()
                ->withInput()
                ->withErrors([
                    'import_file' =>
                        'PENERP could not access the uploaded Excel file temporarily. Please select the file again.',
                ]);
        }

        $fileHash = hash_file(
            'sha256',
            $realPath
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Active Upload
        |--------------------------------------------------------------------------
        |
        | Cancelled, rejected and failed batches may be uploaded again.
        |
        */

        $existingFile = ContributionImportBatch::query()
            ->where(
                'file_hash',
                $fileHash
            )
            ->where(
                'employer_id',
                $employer->id
            )
            ->whereNotIn(
                'status',
                [
                    'cancelled',
                    'rejected',
                    'failed',
                    'posting_failed',
                ]
            )
            ->first();


        if ($existingFile) {
            return back()
                ->withInput()
                ->withErrors([
                    'import_file' =>
                        'This contribution file has already been uploaded for this employer and is currently in the system.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Store Uploaded File
        |--------------------------------------------------------------------------
        */

        $stored = Storage::disk('local')
            ->putFileAs(
                $directory,
                $uploadedFile,
                $storedFilename
            );


        if (!$stored) {
            return back()
                ->withInput()
                ->withErrors([
                    'import_file' =>
                        'PENERP could not save the uploaded contribution Excel file.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Find Existing Contribution Period
            |--------------------------------------------------------------------------
            |
            | There must only be one contribution period for:
            |
            | Employer + Year + Month
            |
            */

            $period = ContributionPeriod::query()
                ->where(
                    'employer_id',
                    $employer->id
                )
                ->where(
                    'period_year',
                    $periodYear
                )
                ->where(
                    'period_month',
                    $periodMonth
                )
                ->lockForUpdate()
                ->first();


            /*
            |--------------------------------------------------------------------------
            | Create New Period
            |--------------------------------------------------------------------------
            */

            if (!$period) {
                $period = ContributionPeriod::create([
                    'employer_id' =>
                        $employer->id,

                    'period_date' =>
                        $periodDate->toDateString(),

                    'due_date' =>
                        $dueDate->toDateString(),

                    'period_year' =>
                        $periodYear,

                    'period_month' =>
                        $periodMonth,

                    'scheme_code' =>
                        $validated['scheme_code']
                        ?? null,

                    'status' =>
                        'open',

                    'scheduled_members' =>
                        0,

                    'existing_members' =>
                        0,

                    'new_members' =>
                        0,

                    'nil_contributors' =>
                        0,

                    'created_by' =>
                        auth()->id(),

                    'updated_by' =>
                        auth()->id(),
                ]);

            } else {

                /*
                |--------------------------------------------------------------------------
                | Existing Period Integrity Check
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $period->period_year !== $periodYear
                    ||
                    (int) $period->period_month !== $periodMonth
                ) {
                    throw new RuntimeException(
                        'The existing contribution period is internally inconsistent.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Repair Derived Period Date
                |--------------------------------------------------------------------------
                |
                | period_date is derived from year/month and should therefore always
                | agree with them.
                |
                */

                $period->period_date =
                    $periodDate->toDateString();


                /*
                |--------------------------------------------------------------------------
                | Update Due Date
                |--------------------------------------------------------------------------
                */

                $period->due_date =
                    $dueDate->toDateString();


                /*
                |--------------------------------------------------------------------------
                | Scheme Code
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $validated['scheme_code']
                    )
                ) {
                    $period->scheme_code =
                        $validated['scheme_code'];
                }


                $period->updated_by =
                    auth()->id();


                $period->save();
            }


            /*
            |--------------------------------------------------------------------------
            | Final Period Integrity Check
            |--------------------------------------------------------------------------
            */

            if (
                (int) $period->period_year !== $periodYear
                ||
                (int) $period->period_month !== $periodMonth
                ||
                Carbon::parse($period->period_date)->year !== $periodYear
                ||
                Carbon::parse($period->period_date)->month !== $periodMonth
            ) {
                throw new RuntimeException(
                    'The contribution period could not be validated after creation.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Create Import Batch
            |--------------------------------------------------------------------------
            */

            $batch = ContributionImportBatch::create([
                'import_uuid' =>
                    $importUuid,

                'contribution_period_id' =>
                    $period->id,

                'employer_id' =>
                    $employer->id,

                'original_filename' =>
                    $uploadedFile->getClientOriginalName(),

                'stored_filename' =>
                    $storedFilename,

                'file_path' =>
                    $filePath,

                'file_extension' =>
                    $extension,

                'file_size' =>
                    $uploadedFile->getSize(),

                'file_hash' =>
                    $fileHash,

                'source_system' =>
                    'monthly_excel',

                'scheme_code' =>
                    $validated['scheme_code']
                    ?? null,

                'due_date' =>
                    $dueDate->toDateString(),

                'currency_code' =>
                    $currencyCode,

                'exchange_rate_to_base' =>
                    $exchangeRate,

                'status' =>
                    'uploaded',

                'progress_percentage' =>
                    0,

                'total_rows' =>
                    0,

                'processed_rows' =>
                    0,

                'valid_rows' =>
                    0,

                'warning_rows' =>
                    0,

                'error_rows' =>
                    0,

                'existing_member_rows' =>
                    0,

                'new_member_rows' =>
                    0,

                'nil_contributor_rows' =>
                    0,

                'usd_basic_pay_total' =>
                    0,

                'usd_employee_contribution_total' =>
                    0,

                'usd_employer_contribution_total' =>
                    0,

                'usd_employee_avc_total' =>
                    0,

                'usd_employer_avc_total' =>
                    0,

                'zwg_basic_pay_total' =>
                    0,

                'zwg_employee_contribution_total' =>
                    0,

                'zwg_employer_contribution_total' =>
                    0,

                'zwg_employee_avc_total' =>
                    0,

                'zwg_employer_avc_total' =>
                    0,

                'posted_rows' =>
                    0,

                'failure_reason' =>
                    null,

                'uploaded_by' =>
                    auth()->id(),
            ]);


            DB::commit();

        } catch (Throwable $e) {

            DB::rollBack();


            /*
            |--------------------------------------------------------------------------
            | Remove File When Database Work Fails
            |--------------------------------------------------------------------------
            */

            if (
                Storage::disk('local')
                    ->exists($filePath)
            ) {
                Storage::disk('local')
                    ->delete($filePath);
            }


            report($e);


            return back()
                ->withInput()
                ->withErrors([
                    'import_file' =>
                        'The contribution import could not be created: '
                        . $e->getMessage(),
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Dispatch Validation
        |--------------------------------------------------------------------------
        */

        ProcessContributionImport::dispatch(
            $batch->id
        );


        /*
        |--------------------------------------------------------------------------
        | Redirect To Import
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'pensions-administration.contributions.imports.show',
                $batch
            )
            ->with(
                'success',
                'Contribution file uploaded successfully. PENERP is validating the schedule.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Show Import
    |--------------------------------------------------------------------------
    */

    public function show(
        ContributionImportBatch $batch
    ): View {
        $batch->load([
            'employer',
            'contributionPeriod',
            'uploadedBy',
        ]);


        $counts = [
            'total' =>
                $batch->total_rows,

            'processed' =>
                $batch->processed_rows,

            'valid' =>
                $batch->valid_rows,

            'warnings' =>
                $batch->warning_rows,

            'errors' =>
                $batch->error_rows,

            'existing_members' =>
                $batch->existing_member_rows,

            'new_members' =>
                $batch->new_member_rows,

            'nil_contributors' =>
                $batch->nil_contributor_rows,
        ];


        return view(
            'pensions-administration.contributions.imports.show',
            compact(
                'batch',
                'counts'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Import Status
    |--------------------------------------------------------------------------
    */

    public function status(
        ContributionImportBatch $batch
    ) {
        return response()->json([
            'id' =>
                $batch->id,

            'status' =>
                $batch->status,

            'progress_percentage' =>
                (float) $batch->progress_percentage,

            'total_rows' =>
                $batch->total_rows,

            'processed_rows' =>
                $batch->processed_rows,

            'valid_rows' =>
                $batch->valid_rows,

            'warning_rows' =>
                $batch->warning_rows,

            'error_rows' =>
                $batch->error_rows,

            'existing_member_rows' =>
                $batch->existing_member_rows,

            'new_member_rows' =>
                $batch->new_member_rows,

            'nil_contributor_rows' =>
                $batch->nil_contributor_rows,

            'failure_reason' =>
                $batch->failure_reason,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Cancel Import
    |--------------------------------------------------------------------------
    */

    public function destroy(
        ContributionImportBatch $batch
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Do Not Cancel Posted / Posting Batches
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $batch->status,
                [
                    'posted',
                    'posting',
                ],
                true
            )
        ) {
            return back()
                ->with(
                    'error',
                    'A posted or currently posting contribution batch cannot be cancelled.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Staging Rows
        |--------------------------------------------------------------------------
        */

        ContributionImportRow::query()
            ->where(
                'import_batch_id',
                $batch->id
            )
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | Cancel Batch
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'status' =>
                'cancelled',

            'failure_reason' =>
                null,

            'completed_at' =>
                now(),
        ]);


        return redirect()
            ->route(
                'pensions-administration.contributions.imports.index'
            )
            ->with(
                'success',
                'Contribution import cancelled successfully.'
            );
    }
}