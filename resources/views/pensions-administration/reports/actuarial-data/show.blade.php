@extends('layouts.app')

@section('title', 'Actuarial Data Extract')
@section('page-heading', 'Actuarial Data Extract')
@section('page-subheading', $batch->batch_number)

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-start mb-4">

            <div>
                <h4 class="header-title mb-1">{{ $batch->batch_number }}</h4>

                <p class="text-muted mb-0">
                    {{ $batch->date_from->format('d M Y') }}
                    -
                    {{ $batch->date_to->format('d M Y') }}
                    |
                    {{ $batch->employer?->name ?? 'All Employers' }}
                </p>
            </div>

            <a href="{{ route('pensions-administration.reports.actuarial-data.index') }}"
               class="btn btn-light">
                <i class="mdi mdi-arrow-left me-1"></i>
                Back
            </a>

        </div>

        <div class="mb-2 d-flex justify-content-between">
            <span id="status-label">{{ ucfirst($batch->status) }}</span>
            <strong id="progress-label">
                {{ number_format((float) $batch->progress_percentage, 0) }}%
            </strong>
        </div>

        <div class="progress mb-4" style="height:20px;">
            <div id="progress-bar"
                 class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width:{{ (float) $batch->progress_percentage }}%">
            </div>
        </div>

        <div class="row g-3 mb-4">

            <div class="col-md-4">
                <div class="border rounded p-3">
                    <div class="text-muted">Active Members</div>
                    <h4 id="active-count" class="mb-0">
                        {{ number_format($batch->active_members) }}
                    </h4>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3">
                    <div class="text-muted">Nil Contributors</div>
                    <h4 id="nil-count" class="mb-0">
                        {{ number_format($batch->nil_contributors) }}
                    </h4>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3">
                    <div class="text-muted">Exited Members</div>
                    <h4 id="exited-count" class="mb-0">
                        {{ number_format($batch->exited_members) }}
                    </h4>
                </div>
            </div>

        </div>

        <div id="failure-box"
             class="alert alert-danger {{ $batch->status === 'failed' ? '' : 'd-none' }}">
            {{ $batch->failure_reason }}
        </div>

        <div id="download-box"
             class="{{ $batch->status === 'completed' ? '' : 'd-none' }}">

            <a id="download-link"
               href="{{ route('pensions-administration.reports.actuarial-data.download', $batch) }}"
               class="btn btn-success">

                <i class="mdi mdi-file-excel-outline me-1"></i>
                Download Actuarial Excel Extract

            </a>

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const statusUrl =
        @json(route(
            'pensions-administration.reports.actuarial-data.status',
            $batch
        ));

    let timer = null;

    function refreshStatus() {

        fetch(statusUrl, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {

            const progress =
                Number(data.progress_percentage || 0);

            document.getElementById('progress-label').textContent =
                Math.round(progress) + '%';

            document.getElementById('progress-bar').style.width =
                progress + '%';

            document.getElementById('status-label').textContent =
                String(data.status || '')
                    .replaceAll('_', ' ')
                    .replace(/\b\w/g, c => c.toUpperCase());

            document.getElementById('active-count').textContent =
                Number(data.active_members || 0).toLocaleString();

            document.getElementById('nil-count').textContent =
                Number(data.nil_contributors || 0).toLocaleString();

            document.getElementById('exited-count').textContent =
                Number(data.exited_members || 0).toLocaleString();

            if (data.status === 'completed') {

                clearInterval(timer);

                document.getElementById('progress-bar')
                    .classList.remove('progress-bar-animated');

                const box =
                    document.getElementById('download-box');

                box.classList.remove('d-none');

                if (data.download_url) {
                    document.getElementById('download-link').href =
                        data.download_url;
                }

            }

            if (data.status === 'failed') {

                clearInterval(timer);

                document.getElementById('progress-bar')
                    .classList.remove('progress-bar-animated');

                const box =
                    document.getElementById('failure-box');

                box.textContent =
                    data.failure_reason || 'The actuarial extract failed.';

                box.classList.remove('d-none');

            }

        })
        .catch(() => {});

    }

    @if(!in_array($batch->status, ['completed', 'failed']))

        timer =
            setInterval(
                refreshStatus,
                1500
            );

        refreshStatus();

    @endif

});

</script>

@endpush