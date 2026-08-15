@extends('layouts.app')

@section('title', 'Upload Monthly Contributions')

@section('content')

<div class="container-fluid">

    <div class="row">

        <div class="col-12">

            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                    mb-4
                "
            >

                <div>

                    <h4 class="mb-1">
                        Upload Monthly Contributions
                    </h4>

                    <p class="text-muted mb-0">
                        Upload the employer's expected contribution schedule.
                    </p>

                </div>


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

                    Back

                </a>

            </div>

        </div>

    </div>


    @if(session('error'))

        <div class="alert alert-danger">

            {{ session('error') }}

        </div>

    @endif


    @if($errors->any())

        <div class="alert alert-danger">

            <strong>
                Please correct the following:
            </strong>

            <ul class="mb-0 mt-2">

                @foreach(
                    $errors->all()
                    as $error
                )

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="row">

        <div class="col-xl-8">

            <div class="card">

                <div class="card-body">

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
                                                $employer->id
                                            }}"
                                            @selected(
                                                old(
                                                    'employer_id'
                                                )
                                                ==
                                                $employer->id
                                            )
                                        >

                                            {{
                                                $employer
                                                    ->employer_number
                                            }}

                                            -

                                            {{
                                                $employer
                                                    ->name
                                            }}

                                        </option>

                                    @endforeach

                                </select>


                                @error('employer_id')

                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>


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
                                    class="form-select"
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
                                            value="{{ $month }}"
                                            @selected(
                                                old(
                                                    'period_month',
                                                    now()->month
                                                )
                                                ==
                                                $month
                                            )
                                        >

                                            {{
                                                \Carbon\Carbon::create()
                                                    ->month(
                                                        $month
                                                    )
                                                    ->format(
                                                        'F'
                                                    )
                                            }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>


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
                                    class="form-control"
                                    min="2000"
                                    max="2100"
                                    required
                                >

                            </div>


                            <div class="col-md-4 mb-3">

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
                                    class="form-control"
                                >

                            </div>


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
                                    class="form-control"
                                    maxlength="50"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

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
                                    accept=".xlsx,.xls"
                                    required
                                >


                                @error('import_file')

                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>


                        <div class="alert alert-info">

                            <strong>
                                Important:
                            </strong>

                            Uploading this file does not immediately post
                            contributions to member accounts. The schedule
                            will first be validated and presented for review.

                        </div>


                        <div
                            class="
                                d-flex
                                justify-content-end
                                gap-2
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


        <div class="col-xl-4">

            <div class="card">

                <div class="card-body">

                    <h5 class="card-title">
                        Validation Process
                    </h5>

                    <p class="text-muted">
                        After upload, PENERP will:
                    </p>


                    <ol class="ps-3">

                        <li class="mb-2">
                            Read the Excel contribution schedule.
                        </li>

                        <li class="mb-2">
                            Match existing members using PenAd number,
                            PENERP number, staff number and National ID.
                        </li>

                        <li class="mb-2">
                            Identify proposed new members.
                        </li>

                        <li class="mb-2">
                            Identify nil contributors for the month.
                        </li>

                        <li class="mb-2">
                            Detect negative contributions and other warnings.
                        </li>

                        <li>
                            Present the results for review before posting.
                        </li>

                    </ol>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document
    .getElementById(
        'contributionUploadForm'
    )
    .addEventListener(
        'submit',
        function () {

            const button =
                document.getElementById(
                    'uploadButton'
                );

            button.disabled =
                true;

            button.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';

        }
    );

</script>

@endsection