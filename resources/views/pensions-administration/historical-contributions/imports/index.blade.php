@extends('layouts.app')

@section('title', 'Historical Contributions')

@section('page-heading', 'Historical Contributions')


@section('page-actions')

<a href="{{ route('pensions-administration.historical-contributions.imports.create') }}"
   class="btn btn-primary">

    <i class="mdi mdi-file-upload-outline me-1"></i>

    Upload Historical Contributions

</a>

@endsection


@section('content')

@include('pensions-administration.partials.navigation')


@if(session('success'))

<div class="alert alert-success">

    <i class="mdi mdi-check-circle-outline me-1"></i>

    {{ session('success') }}

</div>

@endif


@if(session('warning'))

<div class="alert alert-warning">

    <i class="mdi mdi-alert-outline me-1"></i>

    {{ session('warning') }}

</div>

@endif


@if(session('error'))

<div class="alert alert-danger">

    <i class="mdi mdi-alert-circle-outline me-1"></i>

    {{ session('error') }}

</div>

@endif


<div class="card">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">
                Historical Contribution Imports
            </h4>

            <p class="text-muted mb-0">
                Historical contribution migration from January 2009 to October 2023,
                including January 2009 opening/take-on balances.
            </p>

        </div>


        <div class="table-responsive">

            <table class="table table-bordered table-striped table-hover align-middle">

                <thead>

                    <tr>

                        <th>Batch</th>

                        <th>File</th>

                        <th>Status</th>

                        <th>Progress</th>

                        <th>Source Rows</th>

                        <th>Transactions</th>

                        <th>Errors</th>

                        <th>Duplicates</th>

                        <th>New Members</th>

                        <th>Uploaded</th>

                        <th class="text-center">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($batches as $batch)

                        @php

                            $badgeClass = match($batch->status) {
                                'posted' =>
                                    'bg-success',

                                'approved' =>
                                    'bg-info',

                                'awaiting_review' =>
                                    'bg-warning text-dark',

                                'processing',
                                'queued' =>
                                    'bg-primary',

                                'failed',
                                'posting_failed',
                                'rejected' =>
                                    'bg-danger',

                                'cancelled' =>
                                    'bg-secondary',

                                default =>
                                    'bg-secondary',
                            };

                        @endphp


                        <tr>

                            <td>
                                #{{ $batch->id }}
                            </td>


                            <td>

                                <strong>
                                    {{ $batch->original_filename }}
                                </strong>

                                <br>

                                <small class="text-muted">
                                    {{ $batch->import_uuid }}
                                </small>

                            </td>


                            <td>

                                <span class="badge {{ $badgeClass }}">
                                    {{ ucwords(str_replace('_', ' ', $batch->status)) }}
                                </span>

                            </td>


                            <td style="min-width: 120px;">

                                <div class="progress"
                                     style="height: 8px;">

                                    <div class="progress-bar"
                                         style="width: {{ min(100, (float) $batch->progress_percentage) }}%;">
                                    </div>

                                </div>

                                <small class="text-muted">
                                    {{ number_format((float) $batch->progress_percentage, 2) }}%
                                </small>

                            </td>


                            <td>
                                {{ number_format($batch->processed_source_rows) }}
                                /
                                {{ number_format($batch->total_source_rows) }}
                            </td>


                            <td>
                                {{ number_format($batch->total_transaction_rows) }}
                            </td>


                            <td>

                                @if($batch->error_transaction_rows > 0)

                                    <span class="badge bg-danger">
                                        {{ number_format($batch->error_transaction_rows) }}
                                    </span>

                                @else

                                    0

                                @endif

                            </td>


                            <td>
                                {{ number_format($batch->duplicate_transaction_rows) }}
                            </td>


                            <td>
                                {{ number_format($batch->new_members_detected) }}
                            </td>


                            <td>

                                {{ optional($batch->created_at)->format('d M Y H:i') }}

                                @if($batch->uploadedBy)

                                    <br>

                                    <small class="text-muted">
                                        {{ $batch->uploadedBy->name ?? $batch->uploadedBy->email ?? 'User' }}
                                    </small>

                                @endif

                            </td>


                            <td class="text-center text-nowrap">

                                <a href="{{ route('pensions-administration.historical-contributions.imports.show', $batch) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="View Import">

                                    <i class="mdi mdi-eye-outline"></i>

                                </a>


                                @if(!in_array($batch->status, ['posting', 'posted'], true))

                                    <form method="POST"
                                          action="{{ route('pensions-administration.historical-contributions.imports.destroy', $batch) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this historical contribution import batch?');">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Delete">

                                            <i class="mdi mdi-delete-outline"></i>

                                        </button>

                                    </form>

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="11"
                                class="text-center text-muted py-4">

                                No historical contribution imports have been uploaded.

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