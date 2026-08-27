@extends('layouts.app')

@section('title', 'Historical Contribution Import')

@section('page-heading', 'Historical Contribution Import')

@section('page-actions')

<div class="d-flex flex-wrap gap-2">

    <a href="{{ route('pensions-administration.historical-contributions.imports.index') }}"
       class="btn btn-light">
        <i class="mdi mdi-arrow-left me-1"></i>
        Back to Imports
    </a>

    <a href="{{ route('pensions-administration.historical-contributions.imports.index') }}"
       class="btn btn-outline-primary">
        <i class="mdi mdi-upload-outline me-1"></i>
        Upload New File
    </a>

</div>

@endsection

@section('content')

@include('pensions-administration.partials.navigation')

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if(session('warning'))
<div class="alert alert-warning">
    {{ session('warning') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

{{-- =========================================================
     FILE DETAILS
========================================================= --}}

<div class="card">
    <div class="card-body">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <h4 class="header-title mb-1">
                    {{ $batch->original_filename }}
                </h4>

                <p class="text-muted mb-0">
                    Batch:
                    {{ $batch->import_uuid }}
                </p>

            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">

                <span id="status-badge"
                      class="badge bg-primary font-size-14">
                    {{ ucwords(str_replace('_', ' ', $batch->status)) }}
                </span>

            </div>

        </div>

    </div>
</div>

{{-- =========================================================
     PROGRESS
========================================================= --}}

<div class="card">
    <div class="card-body">

        <h4 class="header-title mb-3">
            {{ $batch->status === 'posting' ? 'Posting Progress' : 'Validation Progress' }}
        </h4>

        <div class="d-flex justify-content-between mb-2">

            <strong id="status-text">
                {{ ucwords(str_replace('_', ' ', $batch->status)) }}
            </strong>

            <strong id="progress-text">
                {{ number_format((float) $batch->progress_percentage, 2) }}%
            </strong>

        </div>

        <div class="progress" style="height: 18px;">

            <div id="progress-bar"
                 class="progress-bar progress-bar-striped {{ in_array($batch->status, ['uploaded', 'queued', 'processing', 'posting'], true) ? 'progress-bar-animated' : '' }}"
                 role="progressbar"
                 style="width: {{ min(100, (float) $batch->progress_percentage) }}%;">

                <span id="progress-inside">
                    {{ number_format((float) $batch->progress_percentage, 2) }}%
                </span>

            </div>

        </div>

        <div id="failure-box"
             class="alert alert-danger mt-3 {{ in_array($batch->status, ['failed', 'posting_failed'], true) ? '' : 'd-none' }}">

            <strong>
                {{ $batch->status === 'posting_failed' ? 'Posting Failed:' : 'Validation Failed:' }}
            </strong>

            <span id="failure-reason">
                {{ $batch->failure_reason }}
            </span>

        </div>

    </div>
</div>

{{-- =========================================================
     AWAITING REVIEW ACTIONS
========================================================= --}}

@if($batch->status === 'awaiting_review')

<div class="card border-primary">
    <div class="card-body">

        <div class="row align-items-center">

            <div class="col-lg-7">

                <h4 class="header-title mb-1">
                    Validation Complete
                </h4>

                <p class="text-muted mb-0">
                    Historical contribution transactions are ready for review.
                    Review the valid records, warnings, duplicates and service-break periods before approving the batch.
                </p>

            </div>

            <div class="col-lg-5 mt-3 mt-lg-0">

                <div class="d-flex flex-wrap justify-content-lg-end gap-2">

                    <a href="{{ route('pensions-administration.historical-contributions.review.index', $batch) }}"
                       class="btn btn-primary">

                        <i class="mdi mdi-clipboard-check-outline me-1"></i>

                        Review Historical Contributions

                    </a>

                    <a href="{{ route('pensions-administration.historical-contributions.imports.index') }}"
                       class="btn btn-outline-secondary">

                        <i class="mdi mdi-upload-outline me-1"></i>

                        Upload New File

                    </a>

                </div>

            </div>

        </div>

    </div>
</div>

@endif

{{-- =========================================================
     APPROVED / READY TO POST
========================================================= --}}

@if($batch->status === 'approved')

<div class="card border-success">
    <div class="card-body">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

            <div>

                <h4 class="header-title mb-1">
                    Historical Contribution Posting
                </h4>

                <p class="text-muted mb-0">
                    Review has been completed. The approved historical records are ready to be posted into the live PENERP membership and contribution registers.
                </p>

            </div>

            <form method="POST"
                  action="{{ route('pensions-administration.historical-contributions.posting.store', $batch) }}"
                  onsubmit="return confirm('Post all approved historical members, contribution transactions and service-break records into the live PENERP register?');">

                @csrf

                <button type="submit"
                        class="btn btn-success">

                    <i class="mdi mdi-database-import-outline me-1"></i>

                    Post Historical Contributions

                </button>

            </form>

        </div>

    </div>
</div>

@endif

{{-- =========================================================
     POSTING IN PROGRESS
========================================================= --}}

@if($batch->status === 'posting')

<div class="card border-primary">
    <div class="card-body">

        <div class="row g-3">

            <div class="col-lg-4">

                <p class="text-muted mb-1">
                    Posted Transactions
                </p>

                <h3 id="posted-transaction-rows"
                    class="text-success mb-0">
                    {{ number_format($batch->posted_transaction_rows ?? 0) }}
                </h3>

            </div>

            <div class="col-lg-4">

                <p class="text-muted mb-1">
                    Posted Service Periods
                </p>

                <h3 id="posted-service-period-rows"
                    class="text-primary mb-0">
                    {{ number_format($batch->posted_service_period_rows ?? 0) }}
                </h3>

            </div>

            <div class="col-lg-4">

                <p class="text-muted mb-1">
                    New Members Created
                </p>

                <h3 id="new-members-created"
                    class="text-info mb-0">
                    {{ number_format($batch->new_members_created ?? 0) }}
                </h3>

            </div>

        </div>

        <div class="alert alert-info mt-3 mb-0">
            Historical contribution posting is in progress. This page will update automatically.
        </div>

    </div>
</div>

@endif

{{-- =========================================================
     POSTING FAILED
========================================================= --}}

@if($batch->status === 'posting_failed')

<div class="card border-danger">
    <div class="card-body">

        <div class="alert alert-danger">

            <strong>Posting Failed:</strong>

            {{ $batch->failure_reason }}

        </div>

        <div class="d-flex flex-wrap gap-2">

            <form method="POST"
                  action="{{ route('pensions-administration.historical-contributions.posting.store', $batch) }}"
                  onsubmit="return confirm('Retry historical contribution posting? Records already posted will not be posted again.');">

                @csrf

                <button type="submit"
                        class="btn btn-warning">

                    <i class="mdi mdi-refresh me-1"></i>

                    Retry Posting

                </button>

            </form>

            <a href="{{ route('pensions-administration.historical-contributions.imports.index') }}"
               class="btn btn-outline-secondary">

                <i class="mdi mdi-arrow-left me-1"></i>

                Back to Imports

            </a>

        </div>

    </div>
</div>

@endif

{{-- =========================================================
     POSTED
========================================================= --}}

@if($batch->status === 'posted')

<div class="card border-success">
    <div class="card-body">

        <div class="alert alert-success mb-0">

            <strong>Historical contribution posting completed successfully.</strong>

            The approved historical membership, contribution and service-history records have been posted to PENERP.

        </div>

    </div>
</div>

@endif

{{-- =========================================================
     SOURCE / TRANSACTION COUNTS
========================================================= --}}

<div class="row g-3">

    <div class="col-xl-3 col-md-6">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Source Member Rows
                </p>

                <h3 class="mb-0">

                    <span id="processed-source-rows">
                        {{ number_format($batch->processed_source_rows) }}
                    </span>

                    <small class="text-muted">
                        /
                    </small>

                    <span id="total-source-rows">
                        {{ number_format($batch->total_source_rows) }}
                    </span>

                </h3>

            </div>
        </div>

    </div>

    <div class="col-xl-3 col-md-6">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Transactions Identified
                </p>

                <h3 id="transaction-rows"
                    class="mb-0">
                    {{ number_format($batch->total_transaction_rows) }}
                </h3>

            </div>
        </div>

    </div>

    <div class="col-xl-3 col-md-6">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Matched Source Members
                </p>

                <h3 id="matched-member-rows"
                    class="text-success mb-0">
                    {{ number_format($batch->matched_member_rows) }}
                </h3>

            </div>
        </div>

    </div>

    <div class="col-xl-3 col-md-6">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    New Members Detected
                </p>

                <h3 id="new-members"
                    class="text-primary mb-0">
                    {{ number_format($batch->new_members_detected) }}
                </h3>

            </div>
        </div>

    </div>

</div>

{{-- =========================================================
     VALIDATION COUNTS
========================================================= --}}

<div class="row g-3">

    <div class="col-xl-3 col-md-6">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Valid
                </p>

                <h3 id="valid-rows"
                    class="text-success mb-0">
                    {{ number_format($batch->valid_transaction_rows) }}
                </h3>

            </div>
        </div>

    </div>

    <div class="col-xl-3 col-md-6">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Warnings
                </p>

                <h3 id="warning-rows"
                    class="text-warning mb-0">
                    {{ number_format($batch->warning_transaction_rows) }}
                </h3>

            </div>
        </div>

    </div>

    <div class="col-xl-3 col-md-6">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Errors
                </p>

                <h3 id="error-rows"
                    class="text-danger mb-0">
                    {{ number_format($batch->error_transaction_rows) }}
                </h3>

            </div>
        </div>

    </div>

    <div class="col-xl-3 col-md-6">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Duplicates
                </p>

                <h3 id="duplicate-rows"
                    class="text-danger mb-0">
                    {{ number_format($batch->duplicate_transaction_rows) }}
                </h3>

            </div>
        </div>

    </div>

</div>

{{-- =========================================================
     SERVICE HISTORY
========================================================= --}}

<div class="row g-3">

    <div class="col-xl-4 col-md-4">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Contributed Periods
                </p>

                <h3 id="contributed-periods"
                    class="text-success mb-0">
                    {{ number_format($batch->contributed_periods) }}
                </h3>

            </div>
        </div>

    </div>

    <div class="col-xl-4 col-md-4">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Explicit 0.0000 Periods
                </p>

                <h3 id="zero-periods"
                    class="text-warning mb-0">
                    {{ number_format($batch->zero_contribution_periods) }}
                </h3>

            </div>
        </div>

    </div>

    <div class="col-xl-4 col-md-4">

        <div class="card h-100">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Break in Service Periods
                </p>

                <h3 id="break-periods"
                    class="text-danger mb-0">
                    {{ number_format($batch->break_in_service_periods) }}
                </h3>

            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    const validationStatusUrl = @json(
        route(
            'pensions-administration.historical-contributions.imports.status',
            $batch
        )
    );

    const postingStatusUrl = @json(
        route(
            'pensions-administration.historical-contributions.posting.status',
            $batch
        )
    );

    let pollingStopped = false;
    let reloadStarted = false;

    function number(value) {
        return new Intl.NumberFormat().format(
            Number(value || 0)
        );
    }

    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value;
        }
    }

    function reloadPage() {
        if (reloadStarted) {
            return;
        }

        reloadStarted = true;

        window.setTimeout(function () {
            window.location.reload();
        }, 600);
    }

    function updateProgress(data) {

        const progress = Number(
            data.progress_percentage || 0
        );

        setText(
            'status-text',
            data.status_label || data.status || ''
        );

        setText(
            'progress-text',
            progress.toFixed(2) + '%'
        );

        setText(
            'progress-inside',
            progress.toFixed(2) + '%'
        );

        const progressBar = document.getElementById(
            'progress-bar'
        );

        if (progressBar) {
            progressBar.style.width =
                Math.min(100, progress) + '%';
        }

        const statusBadge = document.getElementById(
            'status-badge'
        );

        if (statusBadge) {
            statusBadge.textContent =
                data.status_label || data.status || '';
        }

        /*
        |--------------------------------------------------------------------------
        | Validation Counters
        |--------------------------------------------------------------------------
        */

        if (data.processed_source_rows !== undefined) {
            setText(
                'processed-source-rows',
                number(data.processed_source_rows)
            );
        }

        if (data.total_source_rows !== undefined) {
            setText(
                'total-source-rows',
                number(data.total_source_rows)
            );
        }

        if (data.total_transaction_rows !== undefined) {
            setText(
                'transaction-rows',
                number(data.total_transaction_rows)
            );
        }

        if (data.matched_member_rows !== undefined) {
            setText(
                'matched-member-rows',
                number(data.matched_member_rows)
            );
        }

        if (data.new_members_detected !== undefined) {
            setText(
                'new-members',
                number(data.new_members_detected)
            );
        }

        if (data.valid_transaction_rows !== undefined) {
            setText(
                'valid-rows',
                number(data.valid_transaction_rows)
            );
        }

        if (data.warning_transaction_rows !== undefined) {
            setText(
                'warning-rows',
                number(data.warning_transaction_rows)
            );
        }

        if (data.error_transaction_rows !== undefined) {
            setText(
                'error-rows',
                number(data.error_transaction_rows)
            );
        }

        if (data.duplicate_transaction_rows !== undefined) {
            setText(
                'duplicate-rows',
                number(data.duplicate_transaction_rows)
            );
        }

        if (data.contributed_periods !== undefined) {
            setText(
                'contributed-periods',
                number(data.contributed_periods)
            );
        }

        if (data.zero_contribution_periods !== undefined) {
            setText(
                'zero-periods',
                number(data.zero_contribution_periods)
            );
        }

        if (data.break_in_service_periods !== undefined) {
            setText(
                'break-periods',
                number(data.break_in_service_periods)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Posting Counters
        |--------------------------------------------------------------------------
        */

        if (data.posted_transaction_rows !== undefined) {
            setText(
                'posted-transaction-rows',
                number(data.posted_transaction_rows)
            );
        }

        if (data.posted_service_period_rows !== undefined) {
            setText(
                'posted-service-period-rows',
                number(data.posted_service_period_rows)
            );
        }

        if (data.new_members_created !== undefined) {
            setText(
                'new-members-created',
                number(data.new_members_created)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Failure
        |--------------------------------------------------------------------------
        */

        if (
            data.status === 'failed'
            ||
            data.status === 'posting_failed'
        ) {
            const failureBox = document.getElementById(
                'failure-box'
            );

            if (failureBox) {
                failureBox.classList.remove(
                    'd-none'
                );
            }

            setText(
                'failure-reason',
                data.failure_reason
                ||
                'Historical contribution processing failed.'
            );
        }
    }

    async function fetchStatus(url) {

        const response = await fetch(
            url,
            {
                method: 'GET',

                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },

                cache: 'no-store'
            }
        );

        if (!response.ok) {
            throw new Error(
                'Unable to retrieve historical contribution progress.'
            );
        }

        return await response.json();
    }

    async function pollValidation() {

        if (pollingStopped) {
            return;
        }

        try {

            const data = await fetchStatus(
                validationStatusUrl
            );

            updateProgress(
                data
            );

            /*
            |--------------------------------------------------------------------------
            | Validation Complete
            |--------------------------------------------------------------------------
            */

            if (
                data.status === 'awaiting_review'
            ) {
                pollingStopped = true;

                reloadPage();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Validation Final States
            |--------------------------------------------------------------------------
            */

            if (
                [
                    'failed',
                    'cancelled',
                    'rejected'
                ].includes(data.status)
            ) {
                pollingStopped = true;

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | If Posting Starts
            |--------------------------------------------------------------------------
            */

            if (
                data.status === 'posting'
            ) {
                pollPosting();

                return;
            }

            window.setTimeout(
                pollValidation,
                1500
            );

        } catch (error) {

            console.error(
                error
            );

            window.setTimeout(
                pollValidation,
                3000
            );
        }
    }

    async function pollPosting() {

        if (pollingStopped) {
            return;
        }

        try {

            const data = await fetchStatus(
                postingStatusUrl
            );

            updateProgress(
                data
            );

            /*
            |--------------------------------------------------------------------------
            | Posted Successfully
            |--------------------------------------------------------------------------
            */

            if (
                data.status === 'posted'
            ) {
                pollingStopped = true;

                reloadPage();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Posting Failed
            |--------------------------------------------------------------------------
            */

            if (
                data.status === 'posting_failed'
            ) {
                pollingStopped = true;

                reloadPage();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Continue Polling
            |--------------------------------------------------------------------------
            */

            window.setTimeout(
                pollPosting,
                1500
            );

        } catch (error) {

            console.error(
                error
            );

            window.setTimeout(
                pollPosting,
                3000
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Start Correct Poller
    |--------------------------------------------------------------------------
    */

    @if(in_array($batch->status, ['uploaded', 'queued', 'processing'], true))

        pollValidation();

    @elseif($batch->status === 'posting')

        pollPosting();

    @endif

});
</script>

@endpush