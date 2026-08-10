@extends('layouts.app')

@section('title', 'Upload Membership Excel')

@section('page-heading', 'Upload Membership Excel')


@section('content')


@include(
    'pensions-administration.partials.navigation'
)


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

        <form
            method="POST"
            action="{{ route(
                'pensions-administration.updates.imports.store'
            ) }}"
            enctype="multipart/form-data"
        >

            @csrf


            <div class="card">

                <div class="card-body">

                    <h4 class="header-title mb-3">
                        Membership Import File
                    </h4>


                    <div class="alert alert-info">

                        <i
                            class="
                                mdi
                                mdi-information-outline
                                me-1
                            "
                        ></i>

                        The uploaded workbook will first
                        be staged and validated. No live
                        member record will be created at
                        this stage.

                    </div>



                    {{-- Excel File --}}

                    <div class="mb-4">

                        <label
                            class="form-label"
                        >
                            Excel File
                        </label>


                        <input
                            type="file"
                            name="import_file"
                            class="form-control"
                            accept="
                                .xlsx,
                                .xls
                            "
                            required
                        >


                        <div class="form-text">

                            Accepted formats:
                            XLSX and XLS.

                            Maximum file size:
                            50 MB.

                        </div>

                    </div>



                    {{-- Employer --}}

                    <div class="mb-4">

                        <label
                            class="form-label"
                        >
                            Employer
                        </label>


                        <select
                            name="employer_id"
                            class="form-select"
                        >

                            <option value="">

                                Multiple Employers /
                                Detect From File

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


                        <div class="form-text">

                            Select an employer if the entire
                            file belongs to one employer.
                            Leave blank where the workbook
                            contains several employers.

                        </div>

                    </div>



                    <div
                        class="
                            d-flex
                            justify-content-between
                        "
                    >

                        <a
                            href="{{ route(
                                'pensions-administration.updates.imports.index'
                            ) }}"
                            class="btn btn-light"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i
                                class="
                                    mdi
                                    mdi-file-upload-outline
                                    me-1
                                "
                            ></i>

                            Upload File

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>



    {{-- =====================================================
         IMPORT INFORMATION
    ====================================================== --}}

    <div class="col-xl-4">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title">
                    Import Process
                </h4>


                <div class="mt-4">


                    <div class="d-flex mb-4">

                        <div class="avatar-xs me-3">

                            <span
                                class="
                                    avatar-title
                                    rounded-circle
                                    bg-primary
                                    text-white
                                "
                            >
                                1
                            </span>

                        </div>


                        <div>

                            <strong>
                                Upload
                            </strong>

                            <p class="text-muted mb-0">
                                Upload the PENERP membership
                                Excel template.
                            </p>

                        </div>

                    </div>



                    <div class="d-flex mb-4">

                        <div class="avatar-xs me-3">

                            <span
                                class="
                                    avatar-title
                                    rounded-circle
                                    bg-soft-secondary
                                    text-secondary
                                "
                            >
                                2
                            </span>

                        </div>


                        <div>

                            <strong>
                                Validate
                            </strong>

                            <p class="text-muted mb-0">
                                Validate required fields,
                                employer references and data.
                            </p>

                        </div>

                    </div>



                    <div class="d-flex mb-4">

                        <div class="avatar-xs me-3">

                            <span
                                class="
                                    avatar-title
                                    rounded-circle
                                    bg-soft-secondary
                                    text-secondary
                                "
                            >
                                3
                            </span>

                        </div>


                        <div>

                            <strong>
                                Duplicate Check
                            </strong>

                            <p class="text-muted mb-0">
                                Compare National IDs and
                                legacy member references.
                            </p>

                        </div>

                    </div>



                    <div class="d-flex">

                        <div class="avatar-xs me-3">

                            <span
                                class="
                                    avatar-title
                                    rounded-circle
                                    bg-soft-secondary
                                    text-secondary
                                "
                            >
                                4
                            </span>

                        </div>


                        <div>

                            <strong>
                                Review & Import
                            </strong>

                            <p class="text-muted mb-0">
                                Review warnings before
                                committing records.
                            </p>

                        </div>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>


@endsection