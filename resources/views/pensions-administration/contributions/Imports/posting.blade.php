@extends('layouts.app')

@section('title', 'Posting Monthly Contributions')
@section('page-heading', 'Posting Monthly Contributions')

@section('content')

@include('pensions-administration.partials.navigation')


<div class="container-fluid">


    {{-- =========================================================
         PAGE HEADER
    ========================================================= --}}

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">

        <div>

            <h4 class="mb-1">
                Posting Monthly Contributions
            </h4>

            <p class="text-muted mb-0">

                Batch

                <strong>
                    #{{ $batch->id }}
                </strong>

                —

                {{ $batch->employer?->name ?? '-' }}

                —

                {{ $batch->contributionPeriod?->period_label ?? '-' }}

            </p>

        </div>


        <div class="d-flex flex-wrap gap-2">

            <a href="{{ route('pensions-administration.contributions.imports.review', $batch) }}" class="btn btn-light">
                <i class="mdi mdi-arrow-left me-1"></i>
                Review Batch
            </a>

            <a href="{{ route('pensions-administration.contributions.imports.show', $batch) }}" class="btn btn-outline-primary">
                <i class="mdi mdi-file-document-outline me-1"></i>
                Batch Summary
            </a>

        </div>

    </div>


    {{-- =========================================================
         POSTING CARD
    ========================================================= --}}

    <div class="row justify-content-center">

        <div class="col-xl-8 col-lg-10">

            <div class="card">

                <div class="card-body p-4">


                    {{-- =================================================
                         POSTING ICON / STATUS
                    ================================================= --}}

                    <div class="text-center mb-4">

                        <div class="mb-3">

                            <span class="rounded-circle bg-primary bg-soft d-inline-flex align-items-center justify-content-center" style="width:70px;height:70px;">
                                <i class="mdi mdi-database-sync-outline font-size-32 text-primary"></i>
                            </span>

                        </div>


                        <h4>
                            Posting Contributions
                        </h4>


                        <p class="text-muted mb-0" id="postingStatusText">

                            @if($batch->status === 'posting_failed')

                                Posting failed.

                            @elseif($batch->status === 'posted')

                                Posting completed successfully.

                            @else

                                Preparing contribution posting...

                            @endif

                        </p>

                    </div>


                    {{-- =================================================
                         STATUS BADGE
                    ================================================= --}}

                    <div class="text-center mb-3">

                        <span id="postingStatusBadge" class="badge {{ $batch->status === 'posted' ? 'bg-success' : ($batch->status === 'posting_failed' ? 'bg-danger' : 'bg-primary') }} font-size-14">

                            @if($batch->status === 'posted')

                                Posted

                            @elseif($batch->status === 'posting_failed')

                                Posting Failed

                            @else

                                Posting

                            @endif

                        </span>

                    </div>


                    {{-- =================================================
                         PROGRESS BAR
                    ================================================= --}}

                    <div class="mb-4">

                        <div class="d-flex justify-content-between align-items-center mb-2">

                            <span class="text-muted">
                                Progress
                            </span>

                            <strong id="progressText">
                                {{ number_format((float) ($batch->progress_percentage ?? 0), 0) }}%
                            </strong>

                        </div>


                        <div class="progress" style="height:24px;">

                            <div id="postingProgressBar" class="progress-bar progress-bar-striped {{ $batch->status === 'posting' ? 'progress-bar-animated' : '' }} {{ $batch->status === 'posted' ? 'bg-success' : ($batch->status === 'posting_failed' ? 'bg-danger' : '') }}" role="progressbar" style="width:{{ min(100, (float) ($batch->progress_percentage ?? 0)) }}%;" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (float) ($batch->progress_percentage ?? 0) }}">
                                {{ number_format((float) ($batch->progress_percentage ?? 0), 0) }}%
                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         POSTING COUNTS
                    ================================================= --}}

                    <div class="row text-center">

                        <div class="col-md-4 mb-3">

                            <div class="border rounded p-3 h-100">

                                <p class="text-muted mb-1">
                                    Posted Rows
                                </p>

                                <h3 class="mb-0" id="postedRows">
                                    {{ number_format($batch->posted_rows ?? 0) }}
                                </h3>

                            </div>

                        </div>


                        <div class="col-md-4 mb-3">

                            <div class="border rounded p-3 h-100">

                                <p class="text-muted mb-1">
                                    Total Rows
                                </p>

                                <h3 class="mb-0" id="totalRows">
                                    {{ number_format($batch->total_rows ?? 0) }}
                                </h3>

                            </div>

                        </div>


                        <div class="col-md-4 mb-3">

                            <div class="border rounded p-3 h-100">

                                <p class="text-muted mb-1">
                                    Progress
                                </p>

                                <h3 class="mb-0" id="progressCardText">
                                    {{ number_format((float) ($batch->progress_percentage ?? 0), 0) }}%
                                </h3>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         MEMBER SUMMARY
                    ================================================= --}}

                    @if(isset($summary))

                        <div class="row mt-2">


                            <div class="col-md-3 mb-3">

                                <div class="border rounded p-3 text-center h-100">

                                    <p class="text-muted mb-1">
                                        Existing Members
                                    </p>

                                    <h4 class="mb-0">
                                        {{ number_format($summary['existing_members'] ?? 0) }}
                                    </h4>

                                </div>

                            </div>


                            <div class="col-md-3 mb-3">

                                <div class="border rounded p-3 text-center h-100">

                                    <p class="text-muted mb-1">
                                        New Members
                                    </p>

                                    <h4 class="mb-0 text-info">
                                        {{ number_format($summary['new_members'] ?? 0) }}
                                    </h4>

                                </div>

                            </div>


                            <div class="col-md-3 mb-3">

                                <div class="border rounded p-3 text-center h-100">

                                    <p class="text-muted mb-1">
                                        Reinstatements
                                    </p>

                                    <h4 class="mb-0 text-success">
                                        {{ number_format($summary['reinstatements'] ?? 0) }}
                                    </h4>

                                </div>

                            </div>


                            <div class="col-md-3 mb-3">

                                <div class="border rounded p-3 text-center h-100">

                                    <p class="text-muted mb-1">
                                        Nil Contributors
                                    </p>

                                    <h4 class="mb-0 text-warning">
                                        {{ number_format($summary['nil_contributors'] ?? 0) }}
                                    </h4>

                                </div>

                            </div>

                        </div>

                    @endif


                    {{-- =================================================
                         INFORMATION MESSAGE
                    ================================================= --}}

                    <div id="postingMessage" class="alert {{ $batch->status === 'posted' ? 'alert-success' : ($batch->status === 'posting_failed' ? 'd-none' : 'alert-info') }} mt-3 mb-0">

                        @if($batch->status === 'posted')

                            <i class="mdi mdi-check-circle-outline me-1"></i>

                            All expected contribution rows were posted successfully.

                        @elseif($batch->status !== 'posting_failed')

                            <i class="mdi mdi-information-outline me-1"></i>

                            Contribution posting is running in the background. Please keep this page open.

                        @endif

                    </div>


                    {{-- =================================================
                         FAILURE MESSAGE
                    ================================================= --}}

                    <div id="postingFailure" class="alert alert-danger mt-3 mb-0 {{ $batch->status === 'posting_failed' ? '' : 'd-none' }}">

                        @if($batch->status === 'posting_failed')

                            <strong>
                                Posting Failed:
                            </strong>

                            {{ $batch->failure_reason ?? 'An unexpected error occurred while posting contributions.' }}

                        @endif

                    </div>


                    {{-- =================================================
                         COMPLETED ACTIONS
                    ================================================= --}}

                    <div id="postingCompletedActions" class="text-center mt-4 {{ $batch->status === 'posted' ? '' : 'd-none' }}">

                        <a href="{{ route('pensions-administration.contributions.imports.show', $batch) }}" class="btn btn-success">
                            <i class="mdi mdi-check-circle-outline me-1"></i>
                            View Posted Batch
                        </a>


                        @can('contributions.reports.view')

                            <a href="{{ route('pensions-administration.contributions.reconciliation.show', $batch) }}" class="btn btn-outline-primary">
                                <i class="mdi mdi-scale-balance me-1"></i>
                                Reconciliation
                            </a>

                            <a href="{{ route('pensions-administration.contributions.reconciliation.pdf', $batch) }}" target="_blank" class="btn btn-danger">
                                <i class="mdi mdi-file-pdf-box me-1"></i>
                                PDF
                            </a>

                            <a href="{{ route('pensions-administration.contributions.reconciliation.excel', $batch) }}" class="btn btn-success">
                                <i class="mdi mdi-microsoft-excel me-1"></i>
                                Excel
                            </a>

                        @endcan

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     POSTING STATUS POLLING
========================================================= --}}

@if($batch->status === 'posting')

    @push('scripts')

        <script>

            document.addEventListener('DOMContentLoaded', function () {

                const statusUrl = @json(route('pensions-administration.contributions.imports.posting-status', $batch));

                const progressBar = document.getElementById('postingProgressBar');
                const progressText = document.getElementById('progressText');
                const progressCardText = document.getElementById('progressCardText');
                const statusText = document.getElementById('postingStatusText');
                const statusBadge = document.getElementById('postingStatusBadge');
                const postedRows = document.getElementById('postedRows');
                const totalRows = document.getElementById('totalRows');
                const postingMessage = document.getElementById('postingMessage');
                const postingFailure = document.getElementById('postingFailure');
                const completedActions = document.getElementById('postingCompletedActions');

                let pollingStopped = false;


                function updateProgress(data) {

                    const percentage = Math.max(
                        0,
                        Math.min(
                            100,
                            parseFloat(data.progress_percentage || 0)
                        )
                    );


                    if (progressBar) {

                        progressBar.style.width = percentage + '%';

                        progressBar.setAttribute(
                            'aria-valuenow',
                            percentage
                        );

                        progressBar.innerText = Math.round(percentage) + '%';

                    }


                    if (progressText) {

                        progressText.innerText =
                            Math.round(percentage)
                            +
                            '%';

                    }


                    if (progressCardText) {

                        progressCardText.innerText =
                            Math.round(percentage)
                            +
                            '%';

                    }


                    if (postedRows) {

                        postedRows.innerText =
                            Number(
                                data.posted_rows
                                ||
                                0
                            )
                                .toLocaleString();

                    }


                    if (totalRows) {

                        totalRows.innerText =
                            Number(
                                data.total_rows
                                ||
                                0
                            )
                                .toLocaleString();

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Still Posting
                    |--------------------------------------------------------------------------
                    */

                    if (data.status === 'posting') {

                        if (percentage < 5) {

                            statusText.innerText =
                                'Preparing contribution posting...';

                        } else if (percentage < 90) {

                            statusText.innerText =
                                'Posting contribution rows...';

                        } else if (percentage < 99) {

                            statusText.innerText =
                                'Finalising contribution period...';

                        } else {

                            statusText.innerText =
                                'Completing posting...';

                        }


                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Posted Successfully
                    |--------------------------------------------------------------------------
                    */

                    if (data.status === 'posted') {

                        pollingStopped = true;


                        if (progressBar) {

                            progressBar.style.width =
                                '100%';

                            progressBar.innerText =
                                '100%';

                            progressBar.classList.remove(
                                'progress-bar-animated'
                            );

                            progressBar.classList.remove(
                                'progress-bar-striped'
                            );

                            progressBar.classList.remove(
                                'bg-danger'
                            );

                            progressBar.classList.add(
                                'bg-success'
                            );

                        }


                        if (progressText) {

                            progressText.innerText =
                                '100%';

                        }


                        if (progressCardText) {

                            progressCardText.innerText =
                                '100%';

                        }


                        if (statusText) {

                            statusText.innerText =
                                'Posting completed successfully.';

                        }


                        if (statusBadge) {

                            statusBadge.innerText =
                                'Posted';

                            statusBadge.classList.remove(
                                'bg-primary',
                                'bg-danger',
                                'bg-info'
                            );

                            statusBadge.classList.add(
                                'bg-success'
                            );

                        }


                        if (postingMessage) {

                            postingMessage.classList.remove(
                                'd-none',
                                'alert-info'
                            );

                            postingMessage.classList.add(
                                'alert-success'
                            );

                            postingMessage.innerHTML =
                                '<i class="mdi mdi-check-circle-outline me-1"></i>'
                                +
                                'All expected contribution rows were posted successfully.';

                        }


                        if (postingFailure) {

                            postingFailure.classList.add(
                                'd-none'
                            );

                        }


                        if (completedActions) {

                            completedActions.classList.remove(
                                'd-none'
                            );

                        }


                        setTimeout(
                            function () {

                                window.location.href =
                                    @json(
                                        route(
                                            'pensions-administration.contributions.imports.show',
                                            $batch
                                        )
                                    );

                            },
                            1500
                        );


                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Posting Failed
                    |--------------------------------------------------------------------------
                    */

                    if (
                        data.status === 'posting_failed'
                        ||
                        data.status === 'failed'
                    ) {

                        pollingStopped = true;


                        if (progressBar) {

                            progressBar.classList.remove(
                                'progress-bar-animated'
                            );

                            progressBar.classList.remove(
                                'progress-bar-striped'
                            );

                            progressBar.classList.add(
                                'bg-danger'
                            );

                        }


                        if (statusText) {

                            statusText.innerText =
                                'Posting failed.';

                        }


                        if (statusBadge) {

                            statusBadge.innerText =
                                'Posting Failed';

                            statusBadge.classList.remove(
                                'bg-primary',
                                'bg-success',
                                'bg-info'
                            );

                            statusBadge.classList.add(
                                'bg-danger'
                            );

                        }


                        if (postingFailure) {

                            postingFailure.classList.remove(
                                'd-none'
                            );

                            postingFailure.innerHTML =
                                '<strong>Posting Failed:</strong> '
                                +
                                (
                                    data.failure_reason
                                    ||
                                    'An unexpected error occurred while posting contributions.'
                                );

                        }


                        if (postingMessage) {

                            postingMessage.classList.add(
                                'd-none'
                            );

                        }

                    }

                }


                function pollStatus() {

                    if (pollingStopped) {
                        return;
                    }


                    fetch(
                        statusUrl,
                        {
                            method:
                                'GET',

                            headers: {
                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest'
                            },

                            cache:
                                'no-store'
                        }
                    )

                        .then(
                            function (response) {

                                if (!response.ok) {

                                    throw new Error(
                                        'Unable to retrieve posting status.'
                                    );

                                }


                                return response.json();

                            }
                        )

                        .then(
                            function (data) {

                                updateProgress(
                                    data
                                );


                                if (!pollingStopped) {

                                    setTimeout(
                                        pollStatus,
                                        1000
                                    );

                                }

                            }
                        )

                        .catch(
                            function (error) {

                                console.error(
                                    'Contribution posting status error:',
                                    error
                                );


                                if (!pollingStopped) {

                                    setTimeout(
                                        pollStatus,
                                        2500
                                    );

                                }

                            }
                        );

                }


                pollStatus();

            });

        </script>

    @endpush

@endif

@endsection