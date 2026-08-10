@extends('layouts.app')

@section('title', 'Employer Imports')

@section('page-heading', 'Employer Imports')

@section('page-actions')
    <a href="{{ route('pensions-administration.updates.employer-imports.create') }}" class="btn btn-primary">
        <i class="mdi mdi-file-upload-outline me-1"></i>
        Upload Employer Excel
    </a>
@endsection

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

        <h4 class="header-title">
            Employer Import Batches
        </h4>

        <p class="text-muted">
            Employer spreadsheets are validated and reviewed before records are created or updated.
        </p>


        <div class="table-responsive">

            <table class="table table-bordered table-striped table-hover align-middle">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>File</th>
                        <th>Total Rows</th>
                        <th>Valid</th>
                        <th>Warnings</th>
                        <th>Errors</th>
                        <th>Duplicates</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Uploaded By</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($batches as $batch)

                        <tr>

                            <td>
                                {{ $batch->created_at->format('d M Y H:i') }}
                            </td>

                            <td>
                                <strong>{{ $batch->original_filename }}</strong>

                                <br>

                                <small class="text-muted">
                                    {{ number_format(($batch->file_size ?? 0) / 1024, 1) }} KB
                                </small>
                            </td>

                            <td>
                                {{ number_format($batch->total_rows) }}
                            </td>

                            <td>
                                <span class="text-success">
                                    {{ number_format($batch->valid_rows) }}
                                </span>
                            </td>

                            <td>
                                <span class="text-warning">
                                    {{ number_format($batch->warning_rows) }}
                                </span>
                            </td>

                            <td>
                                <span class="text-danger">
                                    {{ number_format($batch->error_rows) }}
                                </span>
                            </td>

                            <td>
                                <span class="text-info">
                                    {{ number_format($batch->duplicate_rows) }}
                                </span>
                            </td>

                            <td style="min-width:150px;">

                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar"
                                         style="width: {{ $batch->progress_percentage }}%;">
                                    </div>
                                </div>

                                <small>
                                    {{ number_format((float) $batch->progress_percentage, 1) }}%
                                </small>

                            </td>

                            <td>

                                @php
                                    $statusClass = match($batch->status) {
                                        'completed' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        'awaiting_review' => 'bg-warning',
                                        'processing', 'validating', 'duplicate_checking', 'importing' => 'bg-info',
                                        'cancelled' => 'bg-secondary',
                                        default => 'bg-primary',
                                    };
                                @endphp

                                <span class="badge {{ $statusClass }}">
                                    {{ $batch->status_label }}
                                </span>

                            </td>

                            <td>
                                {{ $batch->uploadedBy?->full_name ?? '-' }}
                            </td>

                            <td>
                                <a href="{{ route('pensions-administration.updates.employer-imports.show', $batch) }}"
                                   class="btn btn-sm btn-primary">
                                    View
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                No employer imports have been uploaded.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-3">
            {{ $batches->links() }}
        </div>

    </div>

</div>

@endsection