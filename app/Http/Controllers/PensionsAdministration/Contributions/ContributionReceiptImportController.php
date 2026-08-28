<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionReceiptImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionReceiptImportRow;
use App\Services\PensionsAdministration\Contributions\ContributionReceiptImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ContributionReceiptImportController extends Controller
{
    public function __construct(
        private readonly ContributionReceiptImportService $importService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Import History
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        $this->ensurePermission(
            'contributions.receipts.view'
        );


        $batches =
            ContributionReceiptImportBatch::query()
                ->orderByDesc('created_at')
                ->paginate(50);


        return view(
            'pensions-administration.contributions.receipts.imports.index',
            compact(
                'batches'
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
        $this->ensurePermission(
            'contributions.receipts.create'
        );


        return view(
            'pensions-administration.contributions.receipts.imports.create'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Upload + Validate
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): RedirectResponse {

        $this->ensurePermission(
            'contributions.receipts.create'
        );


        $validated =
            $request->validate([
                'receipt_file' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls,csv',
                    'max:20480',
                ],

                'currency' => [
                    'required',
                    'in:ZWG,USD',
                ],
            ]);


        try {

            $batch =
                $this
                    ->importService
                    ->createBatch(
                        $validated['receipt_file'],
                        $validated['currency'],
                        auth()->id()
                    );


            $this
                ->importService
                ->process(
                    $batch
                );


            return redirect()
                ->route(
                    'pensions-administration.contributions.receipts.imports.review',
                    $batch
                )
                ->with(
                    'success',
                    'Receipt file was uploaded and validated successfully.'
                );


        } catch (Throwable $e) {

            report($e);


            return back()
                ->withInput()
                ->with(
                    'error',
                    'Receipt upload failed: '
                    . $e->getMessage()
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Review
    |--------------------------------------------------------------------------
    */

    public function review(
        Request $request,
        ContributionReceiptImportBatch $batch
    ): View {

        $this->ensurePermission(
            'contributions.receipts.view'
        );


        $query =
            ContributionReceiptImportRow::query()
                ->with(
                    'matchedEmployer'
                )
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->orderBy(
                    'row_number'
                );


        if (
            $request->filled(
                'status'
            )
        ) {

            $query->where(
                'validation_status',
                $request->input(
                    'status'
                )
            );
        }


        $rows =
            $query
                ->paginate(50)
                ->withQueryString();


        $unpostedValidRows =
            ContributionReceiptImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->where(
                    'validation_status',
                    'valid'
                )
                ->whereNull(
                    'imported_at'
                )
                ->count();


        return view(
            'pensions-administration.contributions.receipts.imports.review',
            compact(
                'batch',
                'rows',
                'unpostedValidRows'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Post Valid Receipts
    |--------------------------------------------------------------------------
    */

    public function post(
        ContributionReceiptImportBatch $batch
    ): RedirectResponse {

        $this->ensurePermission(
            'contributions.receipts.post'
        );


        try {

            $posted =
                $this
                    ->importService
                    ->postValid(
                        $batch,
                        auth()->id()
                    );


            return redirect()
                ->route(
                    'pensions-administration.contributions.receipts.index'
                )
                ->with(
                    'success',
                    number_format($posted)
                    . ' contribution receipt(s) were posted successfully.'
                );


        } catch (Throwable $e) {

            report($e);


            return back()->with(
                'error',
                'Posting failed: '
                . $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    */

    private function ensurePermission(
        string $permission
    ): void {

        abort_if(
            !auth()->check(),
            401
        );


        $user =
            auth()->user();


        if (
            $user->is_system_administrator
        ) {

            return;
        }


        abort_unless(
            $user->can(
                $permission
            ),
            403
        );
    }
}