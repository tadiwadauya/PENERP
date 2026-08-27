@extends('layouts.app')

@section('title', 'Upload Contribution Receipts')

@section('page-heading', 'Upload Contribution Receipts')


@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.contributions.receipts.imports.index'
        ) }}"
        class="btn btn-light"
    >

        <i class="mdi mdi-arrow-left me-1"></i>

        Receipt Imports

    </a>

@endsection


@push('styles')

<style>

    /*
    |--------------------------------------------------------------------------
    | Upload Card
    |--------------------------------------------------------------------------
    */

    .receipt-upload-card {
        border-left: 4px solid #0d6efd;
    }


    /*
    |--------------------------------------------------------------------------
    | Upload Box
    |--------------------------------------------------------------------------
    */

    .receipt-upload-box {
        border: 2px dashed #d8dce3;
        border-radius: 8px;
        padding: 30px;
        background: #fafbfc;
        transition: all 0.2s ease-in-out;
    }


    .receipt-upload-box:hover {
        border-color: #0d6efd;
        background: #f8fbff;
    }


    /*
    |--------------------------------------------------------------------------
    | File Format
    |--------------------------------------------------------------------------
    */

    .receipt-column {
        font-family: monospace;
        font-weight: 600;
    }


    /*
    |--------------------------------------------------------------------------
    | Information Cards
    |--------------------------------------------------------------------------
    */

    .receipt-info-card {
        border-left: 4px solid #6c757d;
    }


    .receipt-exchange-card {
        border-left: 4px solid #198754;
    }


    /*
    |--------------------------------------------------------------------------
    | Required Field
    |--------------------------------------------------------------------------
    */

    .required-indicator {
        color: #dc3545;
        font-weight: 700;
    }


    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media(max-width: 768px) {

        .receipt-upload-box {
            padding: 20px;
        }

    }

</style>

@endpush


@section('content')


{{-- =========================================================
     PENSIONS ADMINISTRATION NAVIGATION
========================================================= --}}

@include('pensions-administration.partials.navigation')


{{-- =========================================================
     SUCCESS MESSAGE
========================================================= --}}

@if(session('success'))

    <div class="alert alert-success">

        <i class="mdi mdi-check-circle-outline me-1"></i>

        {{ session('success') }}

    </div>

@endif


{{-- =========================================================
     ERROR MESSAGE
========================================================= --}}

@if(session('error'))

    <div class="alert alert-danger">

        <i class="mdi mdi-alert-circle-outline me-1"></i>

        {{ session('error') }}

    </div>

@endif


{{-- =========================================================
     VALIDATION ERRORS
========================================================= --}}

@if($errors->any())

    <div class="alert alert-danger">

        <div class="d-flex">

            <div class="me-2">

                <i class="mdi mdi-alert-circle-outline"></i>

            </div>


            <div>

                <strong>

                    Please correct the following:

                </strong>


                <ul class="mb-0 mt-2">

                    @foreach($errors->all() as $error)

                        <li>

                            {{ $error }}

                        </li>

                    @endforeach

                </ul>

            </div>

        </div>

    </div>

@endif


{{-- =========================================================
     PAGE INTRODUCTION
========================================================= --}}

<div class="card receipt-upload-card mb-4">

    <div class="card-body">

        <h4 class="header-title mb-1">

            Upload Employer Contribution Receipts

        </h4>


        <p class="text-muted mb-0">

            Upload actual employer remittances received by the Pension Fund.
            The receipt file will first be validated against the employer
            register before valid rows can be reviewed and posted.

        </p>

    </div>

</div>


{{-- =========================================================
     MAIN CONTENT
========================================================= --}}

<div class="row">


    {{-- =====================================================
         UPLOAD FORM
    ====================================================== --}}

    <div class="col-xl-8">

        <div class="card">

            <div class="card-header">

                <h5 class="card-title mb-0">

                    Employer Receipt File

                </h5>

            </div>


            <div class="card-body">

                <form
                    method="POST"
                    action="{{ route(
                        'pensions-administration.contributions.receipts.imports.store'
                    ) }}"
                    enctype="multipart/form-data"
                    id="receiptUploadForm"
                >

                    @csrf


                    {{-- =================================================
                         CURRENCY
                    ================================================== --}}

                    <div class="mb-4">

                        <label
                            class="form-label"
                            for="currency"
                        >

                            Currency of Receipt File

                            <span class="required-indicator">

                                *

                            </span>

                        </label>


                        <select
                            id="currency"
                            name="currency"
                            class="form-select"
                            required
                        >

                            <option
                                value="ZWG"
                                @selected(
                                    old(
                                        'currency',
                                        'ZWG'
                                    )
                                    ===
                                    'ZWG'
                                )
                            >

                                ZWG - Zimbabwe Gold

                            </option>


                            <option
                                value="USD"
                                @selected(
                                    old('currency')
                                    ===
                                    'USD'
                                )
                            >

                                USD - United States Dollar

                            </option>

                        </select>


                        <div class="form-text">

                            Select the currency applying to all receipts
                            in this file.

                            If a future receipt template contains a Currency
                            column, the row currency may be used instead.

                        </div>

                    </div>


                    {{-- =================================================
                         RECEIPT FILE
                    ================================================== --}}

                    <div class="receipt-upload-box mb-4">

                        <div class="mb-3">

                            <i
                                class="
                                    mdi
                                    mdi-file-excel-outline
                                    fs-2
                                    text-success
                                "
                            ></i>

                        </div>


                        <label
                            for="receipt_file"
                            class="form-label"
                        >

                            Receipt Excel File

                            <span class="required-indicator">

                                *

                            </span>

                        </label>


                        <input
                            type="file"
                            id="receipt_file"
                            name="receipt_file"
                            class="form-control"
                            accept=".xlsx,.xls,.csv"
                            required
                        >


                        <div class="form-text mt-2">

                            Supported file types:

                            <strong>
                                XLSX
                            </strong>,

                            <strong>
                                XLS
                            </strong>

                            and

                            <strong>
                                CSV
                            </strong>.

                        </div>

                    </div>


                    {{-- =================================================
                         WORKFLOW NOTICE
                    ================================================== --}}

                    <div class="alert alert-info">

                        <div class="d-flex">

                            <div class="me-2">

                                <i class="mdi mdi-information-outline"></i>

                            </div>


                            <div>

                                <strong>

                                    Receipt processing workflow

                                </strong>


                                <div class="mt-1">

                                    The uploaded file will be validated first.

                                    No receipt is posted to the receipt register
                                    until you review the validation results and
                                    select

                                    <strong>
                                        Post Valid Receipts
                                    </strong>.

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         BUTTONS
                    ================================================== --}}

                    <div
                        class="
                            d-flex
                            flex-wrap
                            justify-content-end
                            gap-2
                        "
                    >

                        <a
                            href="{{ route(
                                'pensions-administration.contributions.receipts.index'
                            ) }}"
                            class="btn btn-light"
                        >

                            <i class="mdi mdi-close me-1"></i>

                            Cancel

                        </a>


                        <button
                            type="submit"
                            id="uploadButton"
                            class="btn btn-primary"
                        >

                            <i class="mdi mdi-upload me-1"></i>

                            Upload and Validate

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    {{-- =====================================================
         RIGHT PANEL
    ====================================================== --}}

    <div class="col-xl-4">


        {{-- =================================================
             REQUIRED FILE FORMAT
        ================================================== --}}

        <div class="card receipt-info-card">

            <div class="card-header">

                <h5 class="card-title mb-0">

                    Required File Format

                </h5>

            </div>


            <div class="card-body">

                <p class="text-muted">

                    The receipt file must contain these columns:

                </p>


                <div class="table-responsive">

                    <table
                        class="
                            table
                            table-sm
                            table-bordered
                            mb-3
                        "
                    >

                        <thead class="table-light">

                            <tr>

                                <th>
                                    Column
                                </th>

                                <th>
                                    Required
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <tr>

                                <td class="receipt-column">

                                    Employer Code

                                </td>

                                <td>

                                    <span class="badge bg-success">

                                        Yes

                                    </span>

                                </td>

                            </tr>


                            <tr>

                                <td class="receipt-column">

                                    Receipt Date

                                </td>

                                <td>

                                    <span class="badge bg-success">

                                        Yes

                                    </span>

                                </td>

                            </tr>


                            <tr>

                                <td class="receipt-column">

                                    Due Date

                                </td>

                                <td>

                                    <span class="badge bg-success">

                                        Yes

                                    </span>

                                </td>

                            </tr>


                            <tr>

                                <td class="receipt-column">

                                    Amount

                                </td>

                                <td>

                                    <span class="badge bg-success">

                                        Yes

                                    </span>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>


                <div class="alert alert-info mb-0">

                    <div>

                        <strong>

                            Due Date

                        </strong>

                        determines the contribution month.

                    </div>


                    <div class="mt-2">

                        Due Date:

                        <strong>

                            28 Feb 2025

                        </strong>

                    </div>


                    <div>

                        Contribution Period:

                        <strong>

                            Feb 2025

                        </strong>

                    </div>

                </div>

            </div>

        </div>


        {{-- =================================================
             USD RECEIPTS
        ================================================== --}}

        <div class="card receipt-exchange-card mt-3">

            <div class="card-header">

                <h5 class="card-title mb-0">

                    USD Receipts

                </h5>

            </div>


            <div class="card-body">

                <div class="d-flex">

                    <div class="me-3">

                        <i
                            class="
                                mdi
                                mdi-currency-usd
                                fs-3
                                text-success
                            "
                        ></i>

                    </div>


                    <div>

                        <p class="mb-2">

                            USD receipts are converted to ZWG using
                            the USD → ZWG exchange rate applicable
                            on the

                            <strong>
                                Receipt Date
                            </strong>.

                        </p>


                        <p class="text-muted mb-3">

                            If no exchange rate exists for that date,
                            the receipt row will fail validation and
                            will not be posted.

                        </p>


                        @can('contributions.exchange-rates.view')

                            <a
                                href="{{ route(
                                    'pensions-administration.contributions.receipts.exchange-rates.index'
                                ) }}"
                                class="btn btn-sm btn-outline-success"
                            >

                                <i class="mdi mdi-currency-usd me-1"></i>

                                View Exchange Rates

                            </a>

                        @endcan

                    </div>

                </div>

            </div>

        </div>


        {{-- =================================================
             RECEIPT BUSINESS RULE
        ================================================== --}}

        <div class="card mt-3">

            <div class="card-header">

                <h5 class="card-title mb-0">

                    Receipt Processing

                </h5>

            </div>


            <div class="card-body">

                <p class="mb-2">

                    Each Excel row is treated as an individual
                    employer receipt transaction.

                </p>


                <div class="small text-muted">

                    Two receipts may have the same employer,
                    receipt date, due date and amount and still
                    both be valid transactions.

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | Elements
        |--------------------------------------------------------------------------
        */

        const form =
            document.getElementById(
                'receiptUploadForm'
            );

        const button =
            document.getElementById(
                'uploadButton'
            );

        const fileInput =
            document.getElementById(
                'receipt_file'
            );


        /*
        |--------------------------------------------------------------------------
        | Upload Button
        |--------------------------------------------------------------------------
        */

        if (
            !form
            ||
            !button
        ) {
            return;
        }


        form.addEventListener(
            'submit',
            function () {

                button.disabled =
                    true;


                button.innerHTML =
                    `
                        <span
                            class="
                                spinner-border
                                spinner-border-sm
                                me-1
                            "
                            role="status"
                            aria-hidden="true"
                        ></span>

                        Uploading and Validating...
                    `;

            }
        );


        /*
        |--------------------------------------------------------------------------
        | File Selection
        |--------------------------------------------------------------------------
        */

        if (fileInput) {

            fileInput.addEventListener(
                'change',
                function () {

                    if (
                        this.files
                        &&
                        this.files.length
                        >
                        0
                    ) {

                        const file =
                            this.files[0];


                        console.log(
                            'Receipt file selected:',
                            file.name
                        );

                    }

                }
            );

        }

    }
);

</script>

@endpush