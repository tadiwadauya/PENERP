@extends('layouts.app')

@section('title', 'Employer Import')

@section('page-heading', 'Employer Import')

@section('content')

@include('pensions-administration.partials.navigation')

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif


{{-- =========================================================
     BATCH INFORMATION
========================================================= --}}

<div class="card">
    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-start">

            <div>
                <h4 class="header-title mb-2">
                    {{ $batch->original_filename }}
                </h4>

                <p class="text-muted mb-0">
                    Batch: {{ $batch->import_uuid }}
                </p>
            </div>

            <span id="batch-status" class="badge
                @if($batch->status === 'completed') bg-success
                @elseif($batch->status === 'failed') bg-danger
                @elseif($batch->status === 'awaiting_review') bg-warning
                @elseif(in_array($batch->status, ['processing', 'validating', 'importing'])) bg-info
                @elseif($batch->status === 'cancelled') bg-secondary
                @else bg-primary
                @endif
            ">
                {{ $batch->status_label }}
            </span>

        </div>


        <hr>


        <div class="row">

            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">
                    Uploaded
                </small>

                <strong>
                    {{ $batch->created_at->format('d M Y H:i') }}
                </strong>
            </div>


            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">
                    Uploaded By
                </small>

                <strong>
                    {{ $batch->uploadedBy?->full_name ?? '-' }}
                </strong>
            </div>


            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">
                    Import Type
                </small>

                <strong>
                    Employers
                </strong>
            </div>


            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">
                    File Size
                </small>

                <strong>
                    {{ number_format(($batch->file_size ?? 0) / 1024, 1) }} KB
                </strong>
            </div>

        </div>

    </div>
</div>


{{-- =========================================================
     PROGRESS
========================================================= --}}

<div class="card">
    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

            <div>
                <h4 class="header-title mb-1">
                    Import Progress
                </h4>

                <p id="progress-message" class="text-muted mb-0">

                    @if($batch->status === 'uploaded')
                        Employer file uploaded successfully and is ready for validation.

                    @elseif(in_array($batch->status, ['processing', 'validating', 'duplicate_checking']))
                        PENERP is validating the employer workbook.

                    @elseif($batch->status === 'awaiting_review')
                        Validation is complete. Review and approve the employer records.

                    @elseif($batch->status === 'importing')
                        PENERP is importing approved employers into the live employer register.

                    @elseif($batch->status === 'completed')
                        Employer import completed successfully.

                    @elseif($batch->status === 'failed')
                        Employer import failed.

                    @elseif($batch->status === 'cancelled')
                        This import batch was cancelled.

                    @else
                        {{ $batch->status_label }}
                    @endif

                </p>
            </div>


            @if(in_array($batch->status, ['uploaded', 'failed']))

                <form method="POST"
                      action="{{ route('pensions-administration.updates.employer-imports.validate', $batch) }}">

                    @csrf

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-file-check-outline me-1"></i>
                        Validate Employers
                    </button>

                </form>

            @endif

        </div>


        <div class="progress mb-2" style="height:18px;">

            <div id="validation-progress"
                 class="progress-bar
                    @if($batch->status === 'completed') bg-success
                    @elseif($batch->status === 'failed') bg-danger
                    @else progress-bar-striped progress-bar-animated
                    @endif"
                 role="progressbar"
                 style="width: {{ $batch->progress_percentage }}%;">

                <span id="validation-percentage">
                    {{ number_format((float) $batch->progress_percentage, 1) }}%
                </span>

            </div>

        </div>


        <div id="failure-box"
             class="alert alert-danger mt-3 {{ $batch->failure_reason ? '' : 'd-none' }}">

            {{ $batch->failure_reason }}

        </div>

    </div>
</div>


{{-- =========================================================
     COUNTERS
========================================================= --}}

<div class="row">

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Total Rows
                </p>

                <h4 id="total-rows" class="text-primary">
                    {{ number_format($batch->total_rows) }}
                </h4>

            </div>
        </div>
    </div>


    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Valid
                </p>

                <h4 id="valid-rows" class="text-success">
                    {{ number_format($batch->valid_rows) }}
                </h4>

            </div>
        </div>
    </div>


    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Approved
                </p>

                <h4 id="approved-rows" class="text-success">
                    {{ number_format($batch->approved_rows) }}
                </h4>

            </div>
        </div>
    </div>


    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Imported
                </p>

                <h4 id="imported-rows" class="text-primary">
                    {{ number_format($batch->imported_rows) }}
                </h4>

            </div>
        </div>
    </div>


    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Errors
                </p>

                <h4 id="error-rows" class="text-danger">
                    {{ number_format($batch->error_rows) }}
                </h4>

            </div>
        </div>
    </div>


    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Rejected
                </p>

                <h4 id="rejected-rows" class="text-secondary">
                    {{ number_format($batch->rejected_rows) }}
                </h4>

            </div>
        </div>
    </div>

</div>


{{-- =========================================================
     ACTIONS
========================================================= --}}

<div class="card">
    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <a href="{{ route('pensions-administration.updates.employer-imports.index') }}"
               class="btn btn-light">

                <i class="mdi mdi-arrow-left me-1"></i>
                Back to Employer Imports

            </a>


            <div class="d-flex gap-2 mt-2 mt-md-0">

                <a id="review-button"
                   href="{{ route('pensions-administration.updates.employer-imports.review', $batch) }}"
                   class="btn btn-success
                        {{ $batch->status === 'awaiting_review' ? '' : 'd-none' }}">

                    <i class="mdi mdi-format-list-checks me-1"></i>
                    Review Validation

                </a>


                @if($batch->status === 'completed')

                    <a href="{{ route('pensions-administration.updates.employers.index') }}"
                       class="btn btn-primary">

                        <i class="mdi mdi-office-building-outline me-1"></i>
                        View Employers

                    </a>

                @endif


                @if(in_array($batch->status, ['uploaded', 'failed', 'awaiting_review']))

                    <form method="POST"
                          action="{{ route('pensions-administration.updates.employer-imports.destroy', $batch) }}"
                          onsubmit="return confirm('Cancel this employer import and upload another file?');">

                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-outline-danger">
                            <i class="mdi mdi-file-refresh-outline me-1"></i>
                            Cancel & Re-upload
                        </button>

                    </form>

                @endif

            </div>

        </div>

    </div>
</div>

@endsection


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    |
    | "importing" MUST be here.
    |
    | Previously it was missing, which caused the browser to stop polling
    | as soon as the final employer import started.
    |
    */

    const currentStatus = @json($batch->status);

    const activeStatuses = [
        'processing',
        'validating',
        'duplicate_checking',
        'importing'
    ];


    if (!activeStatuses.includes(currentStatus)) {
        return;
    }


    const statusUrl = @json(
        route('pensions-administration.updates.employer-imports.status', $batch)
    );


    const interval = setInterval(function () {

        fetch(statusUrl, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {

            if (!response.ok) {
                throw new Error(
                    'Unable to retrieve employer import status.'
                );
            }

            return response.json();
        })
        .then(data => {

            const percentage = Number(
                data.progress_percentage || 0
            );


            /*
            |--------------------------------------------------------------------------
            | Progress
            |--------------------------------------------------------------------------
            */

            const progressBar =
                document.getElementById('validation-progress');

            progressBar.style.width =
                percentage + '%';


            document.getElementById('validation-percentage').textContent =
                percentage.toFixed(1) + '%';


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            document.getElementById('batch-status').textContent =
                data.status_label;


            /*
            |--------------------------------------------------------------------------
            | Counters
            |--------------------------------------------------------------------------
            */

            document.getElementById('total-rows').textContent =
                Number(data.total_rows || 0).toLocaleString();

            document.getElementById('valid-rows').textContent =
                Number(data.valid_rows || 0).toLocaleString();

            document.getElementById('approved-rows').textContent =
                Number(data.approved_rows || 0).toLocaleString();

            document.getElementById('imported-rows').textContent =
                Number(data.imported_rows || 0).toLocaleString();

            document.getElementById('error-rows').textContent =
                Number(data.error_rows || 0).toLocaleString();

            document.getElementById('rejected-rows').textContent =
                Number(data.rejected_rows || 0).toLocaleString();


            /*
            |--------------------------------------------------------------------------
            | Validating
            |--------------------------------------------------------------------------
            */

            if (
                data.status === 'processing'
                || data.status === 'validating'
                || data.status === 'duplicate_checking'
            ) {
                document.getElementById('progress-message').textContent =
                    'PENERP is validating the employer workbook.';
            }


            /*
            |--------------------------------------------------------------------------
            | Importing
            |--------------------------------------------------------------------------
            */

            if (data.status === 'importing') {

                document.getElementById('progress-message').textContent =
                    'PENERP is importing approved employers into the live employer register.';
            }


            /*
            |--------------------------------------------------------------------------
            | Awaiting Review
            |--------------------------------------------------------------------------
            */

            if (data.status === 'awaiting_review') {

                clearInterval(interval);

                document.getElementById('progress-message').textContent =
                    'Validation is complete. Review and approve the employer records.';

                const reviewButton =
                    document.getElementById('review-button');

                if (reviewButton) {
                    reviewButton.classList.remove('d-none');
                    reviewButton.href = data.review_url;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Completed
            |--------------------------------------------------------------------------
            */

            if (data.status === 'completed') {

                clearInterval(interval);

                progressBar.style.width = '100%';

                document.getElementById('validation-percentage').textContent =
                    '100.0%';

                document.getElementById('progress-message').textContent =
                    'Employer import completed successfully.';

                /*
                | Reload once so completed action buttons appear.
                */

                setTimeout(function () {
                    window.location.reload();
                }, 800);
            }


            /*
            |--------------------------------------------------------------------------
            | Failed
            |--------------------------------------------------------------------------
            */

            if (data.status === 'failed') {

                clearInterval(interval);

                document.getElementById('progress-message').textContent =
                    'Employer import failed.';

                const failureBox =
                    document.getElementById('failure-box');

                failureBox.classList.remove('d-none');

                failureBox.textContent =
                    data.failure_reason
                    || 'Employer import failed.';
            }

        })
        .catch(error => {

            console.error(
                'Unable to retrieve employer import progress:',
                error
            );

        });

    }, 1500);

});
</script>

@endpush