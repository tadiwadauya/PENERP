@extends('layouts.app')

@section('title', 'Membership Import')

@section('page-heading', 'Membership Import')

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

            <div>
                <span id="batch-status" class="badge bg-primary">
                    {{ $batch->status_label }}
                </span>
            </div>

        </div>

        <hr>

        <div class="row">

            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Uploaded</small>
                <strong>{{ $batch->created_at->format('d M Y H:i') }}</strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Uploaded By</small>
                <strong>{{ $batch->uploadedBy?->full_name ?? '-' }}</strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">Employer</small>
                <strong>{{ $batch->employer?->name ?? 'Multiple / Auto Detect' }}</strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted d-block">File Size</small>
                <strong>{{ number_format(($batch->file_size ?? 0) / 1024, 1) }} KB</strong>
            </div>

        </div>

    </div>
</div>


<div class="card">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h4 class="header-title mb-0">
                Import Progress
            </h4>

            @if(in_array($batch->status, ['uploaded', 'failed', 'awaiting_review']))
                <form method="POST" action="{{ route('pensions-administration.updates.imports.validate', $batch) }}">
                    @csrf

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-file-check-outline me-1"></i>
                        {{ $batch->status === 'awaiting_review' ? 'Revalidate File' : 'Validate File' }}
                    </button>
                </form>
            @endif

        </div>


        <div class="progress mb-2" style="height:18px;">
            <div id="validation-progress"
                 class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: {{ $batch->progress_percentage }}%;">

                <span id="validation-percentage">
                    {{ number_format((float) $batch->progress_percentage, 1) }}%
                </span>

            </div>
        </div>


        <p id="progress-message" class="text-muted mb-0">

            @if($batch->status === 'uploaded')
                The workbook has been uploaded and is ready for validation.
            @elseif(in_array($batch->status, ['processing', 'validating', 'duplicate_checking']))
                PENERP is validating the membership workbook.
            @elseif($batch->status === 'awaiting_review')
                Validation is complete. Review warnings, errors and duplicates before importing.
            @elseif($batch->status === 'failed')
                Validation failed.
            @else
                {{ $batch->status_label }}
            @endif

        </p>

        <div id="failure-box" class="alert alert-danger mt-3 {{ $batch->failure_reason ? '' : 'd-none' }}">
            {{ $batch->failure_reason }}
        </div>

    </div>
</div>


<div class="row">

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Total Rows</p>
                <h4 id="total-rows" class="text-primary">
                    {{ number_format($batch->total_rows) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Processed</p>
                <h4 id="processed-rows">
                    {{ number_format($batch->processed_rows) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Valid</p>
                <h4 id="valid-rows" class="text-success">
                    {{ number_format($batch->valid_rows) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Warnings</p>
                <h4 id="warning-rows" class="text-warning">
                    {{ number_format($batch->warning_rows) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Errors</p>
                <h4 id="error-rows" class="text-danger">
                    {{ number_format($batch->error_rows) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Duplicates</p>
                <h4 id="duplicate-rows" class="text-info">
                    {{ number_format($batch->duplicate_rows) }}
                </h4>
            </div>
        </div>
    </div>

</div>


<div class="card">
    <div class="card-body">

        <div class="d-flex justify-content-between">

            <a href="{{ route('pensions-administration.updates.imports.index') }}" class="btn btn-light">
                <i class="mdi mdi-arrow-left me-1"></i>
                Back to Imports
            </a>


            <div class="d-flex gap-2">

                <a id="review-button"
                   href="{{ route('pensions-administration.updates.imports.review', $batch) }}"
                   class="btn btn-success {{ $batch->status === 'awaiting_review' ? '' : 'd-none' }}">

                    <i class="mdi mdi-format-list-checks me-1"></i>
                    Review Validation
                </a>


                @if(!in_array($batch->status, ['completed', 'cancelled', 'processing', 'validating']))
                    <form method="POST"
                          action="{{ route('pensions-administration.updates.imports.destroy', $batch) }}"
                          onsubmit="return confirm('Cancel this membership import?');">

                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-danger">
                            Cancel Import
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

    const currentStatus = @json($batch->status);

    const processingStatuses = [
        'processing',
        'validating',
        'duplicate_checking'
    ];

    if (!processingStatuses.includes(currentStatus)) {
        return;
    }

    const statusUrl = @json(
        route('pensions-administration.updates.imports.status', $batch)
    );

    const interval = setInterval(function () {

        fetch(statusUrl, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {

            const percentage = Number(
                data.progress_percentage || 0
            );

            document.getElementById('validation-progress').style.width =
                percentage + '%';

            document.getElementById('validation-percentage').textContent =
                percentage.toFixed(1) + '%';

            document.getElementById('batch-status').textContent =
                data.status_label;

            document.getElementById('total-rows').textContent =
                Number(data.total_rows).toLocaleString();

            document.getElementById('processed-rows').textContent =
                Number(data.processed_rows).toLocaleString();

            document.getElementById('valid-rows').textContent =
                Number(data.valid_rows).toLocaleString();

            document.getElementById('warning-rows').textContent =
                Number(data.warning_rows).toLocaleString();

            document.getElementById('error-rows').textContent =
                Number(data.error_rows).toLocaleString();

            document.getElementById('duplicate-rows').textContent =
                Number(data.duplicate_rows).toLocaleString();


            if (data.status === 'awaiting_review') {

                clearInterval(interval);

                document.getElementById('progress-message').textContent =
                    'Validation is complete. Review warnings, errors and duplicates before importing.';

                const reviewButton =
                    document.getElementById('review-button');

                reviewButton.classList.remove('d-none');
                reviewButton.href = data.review_url;
            }


            if (data.status === 'failed') {

                clearInterval(interval);

                document.getElementById('progress-message').textContent =
                    'Validation failed.';

                const failureBox =
                    document.getElementById('failure-box');

                failureBox.classList.remove('d-none');
                failureBox.textContent =
                    data.failure_reason || 'Validation failed.';
            }

        })
        .catch(error => {
            console.error(
                'Unable to retrieve import progress:',
                error
            );
        });

    }, 2000);

});
</script>

@endpush