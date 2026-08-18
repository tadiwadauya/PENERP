@extends('layouts.app')

@section(
    'title',
    'Upload Monthly Contributions'
)


@section('content')
@include(
    'pensions-administration.partials.navigation'
)

<div class="container-fluid">


    {{-- =========================================================
         PAGE HEADER
    ========================================================= --}}

    <div class="row">

        <div class="col-12">

            <div
                class="
                    d-flex
                    flex-wrap
                    justify-content-between
                    align-items-center
                    gap-2
                    mb-4
                "
            >

                <div>

                    <h4 class="mb-1">
                        Upload Monthly Contributions
                    </h4>


                    <p class="text-muted mb-0">

                        Upload the monthly expected contribution schedule
                        for an employer.

                    </p>

                </div>


                <div>

                    <a
                        href="{{
                            route(
                                'pensions-administration.contributions.imports.index'
                            )
                        }}"
                        class="btn btn-light"
                    >

                        <i
                            class="
                                mdi
                                mdi-arrow-left
                                me-1
                            "
                        ></i>

                        Contribution Imports

                    </a>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         SUCCESS MESSAGE
    ========================================================= --}}

    @if(
        session(
            'success'
        )
    )

        <div
            class="
                alert
                alert-success
                alert-dismissible
                fade
                show
            "
            role="alert"
        >

            <i
                class="
                    mdi
                    mdi-check-circle-outline
                    me-1
                "
            ></i>


            {{
                session(
                    'success'
                )
            }}


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    @endif


    {{-- =========================================================
         ERROR MESSAGE
    ========================================================= --}}

    @if(
        session(
            'error'
        )
    )

        <div
            class="
                alert
                alert-danger
                alert-dismissible
                fade
                show
            "
            role="alert"
        >

            <i
                class="
                    mdi
                    mdi-alert-circle-outline
                    me-1
                "
            ></i>


            {{
                session(
                    'error'
                )
            }}


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    @endif


    {{-- =========================================================
         VALIDATION ERRORS
    ========================================================= --}}

    @if(
        $errors->any()
    )

        <div
            class="
                alert
                alert-danger
            "
        >

            <div
                class="
                    d-flex
                    align-items-center
                    mb-2
                "
            >

                <i
                    class="
                        mdi
                        mdi-alert-circle-outline
                        font-size-20
                        me-2
                    "
                ></i>


                <strong>
                    Please correct the following:
                </strong>

            </div>


            <ul class="mb-0">

                @foreach(
                    $errors->all()
                    as $error
                )

                    <li>

                        {{
                            $error
                        }}

                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="row">


        {{-- =====================================================
             MAIN UPLOAD FORM
        ===================================================== --}}

        <div class="col-xl-8">

            <div class="card">

                <div class="card-body">


                    <div class="mb-4">

                        <h5 class="card-title mb-1">
                            Contribution Schedule
                        </h5>


                        <p class="text-muted mb-0">

                            Select the employer, contribution period and
                            currency before uploading the Excel schedule.

                        </p>

                    </div>


                    <form
                        method="POST"
                        action="{{
                            route(
                                'pensions-administration.contributions.imports.store'
                            )
                        }}"
                        enctype="multipart/form-data"
                        id="contributionUploadForm"
                    >

                        @csrf


                        <div class="row">


                            {{-- =============================================
                                 EMPLOYER
                            ============================================= --}}

                            <div class="col-md-12 mb-3">

                                <label
                                    for="employer_id"
                                    class="form-label"
                                >

                                    Employer

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="employer_id"
                                    name="employer_id"
                                    class="
                                        form-select
                                        @error('employer_id')
                                            is-invalid
                                        @enderror
                                    "
                                    required
                                >

                                    <option value="">
                                        Select Employer
                                    </option>


                                    @foreach(
                                        $employers
                                        as $employer
                                    )

                                        <option
                                            value="{{
                                                $employer
                                                    ->id
                                            }}"
                                            @selected(
                                                old(
                                                    'employer_id'
                                                )
                                                ==
                                                $employer
                                                    ->id
                                            )
                                        >

                                            @if(
                                                filled(
                                                    $employer
                                                        ->employer_number
                                                )
                                            )

                                                {{
                                                    $employer
                                                        ->employer_number
                                                }}

                                                -

                                            @endif


                                            {{
                                                $employer
                                                    ->name
                                            }}

                                        </option>

                                    @endforeach

                                </select>


                                @error(
                                    'employer_id'
                                )

                                    <div class="invalid-feedback">

                                        {{
                                            $message
                                        }}

                                    </div>

                                @enderror

                            </div>


                            {{-- =============================================
                                 MONTH
                            ============================================= --}}

                            <div class="col-md-4 mb-3">

                                <label
                                    for="period_month"
                                    class="form-label"
                                >

                                    Contribution Month

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="period_month"
                                    name="period_month"
                                    class="
                                        form-select
                                        @error('period_month')
                                            is-invalid
                                        @enderror
                                    "
                                    required
                                >

                                    @foreach(
                                        range(
                                            1,
                                            12
                                        )
                                        as $month
                                    )

                                        <option
                                            value="{{
                                                $month
                                            }}"
                                            @selected(
                                                (int)
                                                old(
                                                    'period_month',
                                                    now()->month
                                                )
                                                ===
                                                $month
                                            )
                                        >

                                            {{
                                                \Carbon\Carbon::create(
                                                    2000,
                                                    $month,
                                                    1
                                                )
                                                    ->format(
                                                        'F'
                                                    )
                                            }}

                                        </option>

                                    @endforeach

                                </select>


                                @error(
                                    'period_month'
                                )

                                    <div class="invalid-feedback">

                                        {{
                                            $message
                                        }}

                                    </div>

                                @enderror

                            </div>


                            {{-- =============================================
                                 YEAR
                            ============================================= --}}

                            <div class="col-md-4 mb-3">

                                <label
                                    for="period_year"
                                    class="form-label"
                                >

                                    Contribution Year

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input
                                    id="period_year"
                                    type="number"
                                    name="period_year"
                                    value="{{
                                        old(
                                            'period_year',
                                            now()->year
                                        )
                                    }}"
                                    class="
                                        form-control
                                        @error('period_year')
                                            is-invalid
                                        @enderror
                                    "
                                    min="2000"
                                    max="2100"
                                    required
                                >


                                @error(
                                    'period_year'
                                )

                                    <div class="invalid-feedback">

                                        {{
                                            $message
                                        }}

                                    </div>

                                @enderror

                            </div>


                            {{-- =============================================
                                 CURRENCY
                            ============================================= --}}

                            <div class="col-md-4 mb-3">

                                <label
                                    for="currency_code"
                                    class="form-label"
                                >

                                    Contribution Currency

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="currency_code"
                                    name="currency_code"
                                    class="
                                        form-select
                                        @error('currency_code')
                                            is-invalid
                                        @enderror
                                    "
                                    required
                                >

                                    <option
                                        value="ZWG"
                                        @selected(
                                            old(
                                                'currency_code',
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
                                            old(
                                                'currency_code'
                                            )
                                            ===
                                            'USD'
                                        )
                                    >

                                        USD - United States Dollar

                                    </option>

                                </select>


                                @error(
                                    'currency_code'
                                )

                                    <div class="invalid-feedback">

                                        {{
                                            $message
                                        }}

                                    </div>

                                @enderror


                                <small class="text-muted">

                                    PENERP base currency is ZWG.

                                </small>

                            </div>


                            {{-- =============================================
                                 DUE DATE
                            ============================================= --}}

                            <div class="col-md-6 mb-3">

                                <label
                                    for="due_date"
                                    class="form-label"
                                >
                                    Due Date
                                </label>


                                <input
                                    id="due_date"
                                    type="date"
                                    name="due_date"
                                    value="{{
                                        old(
                                            'due_date'
                                        )
                                    }}"
                                    class="
                                        form-control
                                        @error('due_date')
                                            is-invalid
                                        @enderror
                                    "
                                >


                                @error(
                                    'due_date'
                                )

                                    <div class="invalid-feedback">

                                        {{
                                            $message
                                        }}

                                    </div>

                                @enderror


                                <small class="text-muted">

                                    Optional if the due date is supplied
                                    inside the Excel schedule.

                                </small>

                            </div>


                            {{-- =============================================
                                 SCHEME CODE
                            ============================================= --}}

                            <div class="col-md-6 mb-3">

                                <label
                                    for="scheme_code"
                                    class="form-label"
                                >
                                    Scheme Code
                                </label>


                                <input
                                    id="scheme_code"
                                    type="text"
                                    name="scheme_code"
                                    value="{{
                                        old(
                                            'scheme_code'
                                        )
                                    }}"
                                    class="
                                        form-control
                                        @error('scheme_code')
                                            is-invalid
                                        @enderror
                                    "
                                    maxlength="50"
                                >


                                @error(
                                    'scheme_code'
                                )

                                    <div class="invalid-feedback">

                                        {{
                                            $message
                                        }}

                                    </div>

                                @enderror

                            </div>


                            {{-- =============================================
                                 FILE
                            ============================================= --}}

                            <div class="col-md-12 mb-4">

                                <label
                                    for="import_file"
                                    class="form-label"
                                >

                                    Contribution Excel File

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input
                                    id="import_file"
                                    type="file"
                                    name="import_file"
                                    class="
                                        form-control
                                        @error('import_file')
                                            is-invalid
                                        @enderror
                                    "
                                    accept="
                                        .xlsx,
                                        .xls,
                                        application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,
                                        application/vnd.ms-excel
                                    "
                                    required
                                >


                                @error(
                                    'import_file'
                                )

                                    <div class="invalid-feedback">

                                        {{
                                            $message
                                        }}

                                    </div>

                                @enderror


                                <div
                                    class="
                                        form-text
                                        mt-2
                                    "
                                >

                                    Maximum file size: 50 MB.
                                    Accepted formats: XLSX and XLS.

                                </div>

                            </div>

                        </div>


                        {{-- =============================================
                             CURRENCY INFORMATION
                        ============================================= --}}

                        <div
                            class="
                                alert
                                alert-info
                            "
                        >

                            <div class="d-flex">

                                <div class="me-3">

                                    <i
                                        class="
                                            mdi
                                            mdi-currency-usd
                                            font-size-24
                                        "
                                    ></i>

                                </div>


                                <div>

                                    <strong>
                                        Multi-Currency Contributions
                                    </strong>


                                    <div class="mt-1">

                                        PENERP's base currency is
                                        <strong>ZWG</strong>.

                                        Select <strong>USD</strong> only
                                        when the contribution schedule
                                        being uploaded is denominated in
                                        United States Dollars.

                                    </div>


                                    <div class="mt-1">

                                        Expected contributions are stored
                                        in their original currency.
                                        This upload does not automatically
                                        convert USD contributions to ZWG.

                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- =============================================
                             EXPECTED CONTRIBUTION INFORMATION
                        ============================================= --}}

                        <div
                            class="
                                alert
                                alert-warning
                            "
                        >

                            <div class="d-flex">

                                <div class="me-3">

                                    <i
                                        class="
                                            mdi
                                            mdi-information-outline
                                            font-size-24
                                        "
                                    ></i>

                                </div>


                                <div>

                                    <strong>
                                        Expected Contributions
                                    </strong>


                                    <div class="mt-1">

                                        This upload records contributions
                                        due or expected for members.

                                        It does not represent actual cash
                                        received from the employer.

                                    </div>


                                    <div class="mt-1">

                                        Actual employer receipts will be
                                        captured separately in the
                                        receipts/reconciliation process.

                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- =============================================
                             VALIDATION INFORMATION
                        ============================================= --}}

                        <div
                            class="
                                alert
                                alert-light
                                border
                            "
                        >

                            <strong>
                                What happens after upload?
                            </strong>


                            <div class="mt-2">

                                The schedule is first placed in a staging
                                area and validated.

                                Contributions are not posted to member
                                records immediately.

                            </div>


                            <div class="mt-2">

                                The system will identify:

                            </div>


                            <ul class="mb-0 mt-2">

                                <li>
                                    Existing members
                                </li>

                                <li>
                                    Proposed new members
                                </li>

                                <li>
                                    Nil contributors
                                </li>

                                <li>
                                    Member identifier conflicts
                                </li>

                                <li>
                                    Duplicate rows
                                </li>

                                <li>
                                    Negative contribution adjustments
                                </li>

                                <li>
                                    Contribution validation errors
                                </li>

                            </ul>

                        </div>


                        {{-- =============================================
                             ACTIONS
                        ============================================= --}}

                        <div
                            class="
                                d-flex
                                flex-wrap
                                justify-content-end
                                gap-2
                                mt-4
                            "
                        >

                            <a
                                href="{{
                                    route(
                                        'pensions-administration.contributions.imports.index'
                                    )
                                }}"
                                class="btn btn-light"
                            >

                                Cancel

                            </a>


                            <button
                                type="submit"
                                class="btn btn-primary"
                                id="uploadButton"
                            >

                                <i
                                    class="
                                        mdi
                                        mdi-upload
                                        me-1
                                    "
                                ></i>

                                Upload & Validate

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        {{-- =====================================================
             RIGHT HAND INFORMATION
        ===================================================== --}}

        <div class="col-xl-4">


            {{-- =================================================
                 PROCESS CARD
            ================================================= --}}

            <div class="card">

                <div class="card-body">

                    <h5 class="card-title mb-3">
                        Validation Process
                    </h5>


                    <div class="mb-3">

                        <div class="d-flex">

                            <div class="me-3">

                                <span
                                    class="
                                        badge
                                        rounded-pill
                                        bg-primary
                                    "
                                >
                                    1
                                </span>

                            </div>


                            <div>

                                <strong>
                                    Upload Excel
                                </strong>

                                <div class="text-muted small">

                                    The contribution schedule is stored
                                    securely in the import staging area.

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="mb-3">

                        <div class="d-flex">

                            <div class="me-3">

                                <span
                                    class="
                                        badge
                                        rounded-pill
                                        bg-primary
                                    "
                                >
                                    2
                                </span>

                            </div>


                            <div>

                                <strong>
                                    Match Members
                                </strong>

                                <div class="text-muted small">

                                    PenAd number, PENERP number,
                                    employer + staff number and National ID
                                    are used to identify members.

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="mb-3">

                        <div class="d-flex">

                            <div class="me-3">

                                <span
                                    class="
                                        badge
                                        rounded-pill
                                        bg-primary
                                    "
                                >
                                    3
                                </span>

                            </div>


                            <div>

                                <strong>
                                    Validate Contributions
                                </strong>

                                <div class="text-muted small">

                                    Amounts, currency, duplicates and
                                    member differences are validated.

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="mb-3">

                        <div class="d-flex">

                            <div class="me-3">

                                <span
                                    class="
                                        badge
                                        rounded-pill
                                        bg-primary
                                    "
                                >
                                    4
                                </span>

                            </div>


                            <div>

                                <strong>
                                    Identify Nil Contributors
                                </strong>

                                <div class="text-muted small">

                                    Existing active members who are
                                    missing from this month's schedule
                                    are identified as nil contributors.

                                </div>

                            </div>

                        </div>

                    </div>


                    <div>

                        <div class="d-flex">

                            <div class="me-3">

                                <span
                                    class="
                                        badge
                                        rounded-pill
                                        bg-primary
                                    "
                                >
                                    5
                                </span>

                            </div>


                            <div>

                                <strong>
                                    Review
                                </strong>

                                <div class="text-muted small">

                                    The results are reviewed before
                                    approval and permanent posting.

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 MEMBER MATCHING CARD
            ================================================= --}}

            <div class="card">

                <div class="card-body">

                    <h5 class="card-title mb-3">
                        Member Matching Priority
                    </h5>


                    <div
                        class="
                            table-responsive
                        "
                    >

                        <table
                            class="
                                table
                                table-sm
                                table-borderless
                                mb-0
                            "
                        >

                            <tbody>

                                <tr>

                                    <td
                                        style="
                                            width: 35px;
                                        "
                                    >

                                        <span
                                            class="
                                                badge
                                                bg-success
                                            "
                                        >
                                            1
                                        </span>

                                    </td>

                                    <td>
                                        PenAd Member Number
                                    </td>

                                </tr>


                                <tr>

                                    <td>

                                        <span
                                            class="
                                                badge
                                                bg-success
                                            "
                                        >
                                            2
                                        </span>

                                    </td>

                                    <td>
                                        PENERP Member Number
                                    </td>

                                </tr>


                                <tr>

                                    <td>

                                        <span
                                            class="
                                                badge
                                                bg-success
                                            "
                                        >
                                            3
                                        </span>

                                    </td>

                                    <td>
                                        Staff Number + Employer
                                    </td>

                                </tr>


                                <tr>

                                    <td>

                                        <span
                                            class="
                                                badge
                                                bg-success
                                            "
                                        >
                                            4
                                        </span>

                                    </td>

                                    <td>
                                        National ID
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    <hr>


                    <p
                        class="
                            text-muted
                            small
                            mb-0
                        "
                    >

                        Staff numbers are unique only within an employer.
                        The same staff number may therefore exist under
                        different employers.

                    </p>

                </div>

            </div>


            {{-- =================================================
                 IMPORTANT RULES
            ================================================= --}}

            <div class="card">

                <div class="card-body">

                    <h5 class="card-title mb-3">
                        Contribution Rules
                    </h5>


                    <ul
                        class="
                            ps-3
                            mb-0
                        "
                    >

                        <li class="mb-2">

                            Missing members are not automatically treated
                            as exited members.

                        </li>


                        <li class="mb-2">

                            Existing active members missing from the
                            schedule become nil contributors for the month.

                        </li>


                        <li class="mb-2">

                            Negative contributions are permitted for
                            reconciliation and overpayment adjustments,
                            but are flagged for review.

                        </li>


                        <li class="mb-2">

                            Employee and employer contributions remain
                            separate.

                        </li>


                        <li class="mb-2">

                            Employee AVC and employer AVC remain separate.

                        </li>


                        <li>

                            Proposed new members are reviewed before
                            permanent creation.

                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =============================================================
     SUBMISSION SCRIPT
============================================================= --}}

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const form =
            document.getElementById(
                'contributionUploadForm'
            );


        const uploadButton =
            document.getElementById(
                'uploadButton'
            );


        if (
            !form
            ||
            !uploadButton
        ) {
            return;
        }


        form.addEventListener(
            'submit',
            function () {

                /*
                |--------------------------------------------------------------------------
                | Prevent Double Submission
                |--------------------------------------------------------------------------
                */

                uploadButton.disabled =
                    true;


                /*
                |--------------------------------------------------------------------------
                | Loading Indicator
                |--------------------------------------------------------------------------
                */

                uploadButton.innerHTML =
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

                        Uploading...
                    `;

            }
        );

    }
);

</script>

@endsection