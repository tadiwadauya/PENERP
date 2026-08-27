@extends('layouts.app')

@section('title', 'Upload Historical Contributions')

@section('page-heading', 'Upload Historical Contributions')


@section('page-actions')

<a href="{{ route('pensions-administration.historical-contributions.imports.index') }}"
   class="btn btn-light">

    <i class="mdi mdi-arrow-left me-1"></i>

    Back

</a>

@endsection


@section('content')

@include('pensions-administration.partials.navigation')


@if(session('error'))

<div class="alert alert-danger">

    <i class="mdi mdi-alert-circle-outline me-1"></i>

    {{ session('error') }}

</div>

@endif


@if($errors->any())

<div class="alert alert-danger">

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

@endif


<div class="row">

    <div class="col-xl-8">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title mb-1">
                    Historical Contribution Workbook
                </h4>

                <p class="text-muted">
                    Upload the historical contribution workbook for validation.
                    The file will be processed in the background.
                </p>


                <form method="POST"
                      action="{{ route('pensions-administration.historical-contributions.imports.store') }}"
                      enctype="multipart/form-data">

                    @csrf


                    <div class="mb-4">

                        <label class="form-label">
                            Historical Contribution Excel File
                            <span class="text-danger">*</span>
                        </label>

                        <input type="file"
                               name="file"
                               class="form-control @error('file') is-invalid @enderror"
                               accept=".xlsx,.xls"
                               required>

                        @error('file')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror


                        <div class="form-text">

                            Supported files:
                            XLSX and XLS.

                        </div>

                    </div>


                    <button type="submit"
                            class="btn btn-primary">

                        <i class="mdi mdi-cloud-upload-outline me-1"></i>

                        Upload and Validate

                    </button>

                </form>

            </div>

        </div>

    </div>


    <div class="col-xl-4">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title">
                    Historical Migration Rules
                </h4>


                <div class="alert alert-info">

                    <strong>
                        Contribution Period
                    </strong>

                    <br>

                    January 2009 to October 2023.

                </div>


                <p>
                    <strong>Take-On:</strong>
                    legacy opening balances are recorded separately as
                    January 2009 take-on transactions.
                </p>


                <p>
                    <strong>Staff Number:</strong>
                    matched only within the relevant employer.
                </p>


                <p>
                    <strong>Blank contribution:</strong>
                    may represent a break in service.
                </p>


                <p>
                    <strong>0.0000:</strong>
                    remains an actual zero value and is not treated as a blank.
                </p>


                <p class="mb-0">

                    <strong>New historical member:</strong>

                    if no existing member matches, the contributor can be
                    created during the approved posting stage.

                </p>

            </div>

        </div>

    </div>

</div>

@endsection