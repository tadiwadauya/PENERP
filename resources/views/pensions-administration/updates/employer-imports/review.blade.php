@extends('layouts.app')

@section('title', 'Review Employer Import')

@section('page-heading', 'Review Employer Import')

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

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <div>
                <h4 class="header-title mb-1">
                    {{ $batch->original_filename }}
                </h4>

                <p class="text-muted mb-0">
                    Review and approve validated employers before final import.
                </p>
            </div>


            <div class="d-flex gap-2 mt-3 mt-md-0">

                <a href="{{ route('pensions-administration.updates.employer-imports.show', $batch) }}"
                   class="btn btn-light">

                    <i class="mdi mdi-arrow-left me-1"></i>
                    Batch Summary
                </a>


                @if($batch->status === 'awaiting_review')

                    <form method="POST"
                          action="{{ route('pensions-administration.updates.employer-imports.destroy', $batch) }}"
                          onsubmit="return confirm('Cancel this batch and upload a corrected employer file?');">

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


<div class="row">

    <div class="col-xl-3 col-md-6">

        <div class="card">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Approved
                </p>

                <h4 class="text-success">
                    {{ number_format($counts['approved']) }}
                </h4>

            </div>
        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Pending Review
                </p>

                <h4 class="text-warning">
                    {{ number_format($counts['pending']) }}
                </h4>

            </div>
        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Imported
                </p>

                <h4 class="text-primary">
                    {{ number_format($counts['imported']) }}
                </h4>

            </div>
        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">
            <div class="card-body">

                <p class="text-muted mb-1">
                    Rejected
                </p>

                <h4 class="text-secondary">
                    {{ number_format($counts['rejected']) }}
                </h4>

            </div>
        </div>

    </div>

</div>


@if($batch->status === 'awaiting_review')

    <div class="card">
        <div class="card-body">

            <div class="d-flex flex-wrap justify-content-between align-items-center">

                <div>

                    <h4 class="header-title mb-1">
                        Approval & Import
                    </h4>

                    <p class="text-muted mb-0">
                        Approve clean employers first, then import the approved records.
                    </p>

                </div>


                <div class="d-flex gap-2 mt-3 mt-md-0">

                    @if($counts['pending'] > 0)

                        <form method="POST"
                              action="{{ route('pensions-administration.updates.employer-imports.approve-valid', $batch) }}">

                            @csrf

                            <button type="submit" class="btn btn-success">

                                <i class="mdi mdi-check-all me-1"></i>

                                Approve All Valid Employers

                            </button>

                        </form>

                    @endif


                    @if($counts['approved'] > $counts['imported'])

                        <form method="POST"
                              action="{{ route('pensions-administration.updates.employer-imports.import', $batch) }}"
                              onsubmit="return confirm('Import all approved employers into the live employer register?');">

                            @csrf

                            <button type="submit" class="btn btn-primary">

                                <i class="mdi mdi-database-import-outline me-1"></i>

                                Import Approved Employers

                            </button>

                        </form>

                    @endif

                </div>

            </div>

        </div>
    </div>

@endif


<div class="card">
    <div class="card-body">

        <form method="GET" class="row g-3 mb-4">

            <div class="col-md-3">

                <label class="form-label">
                    Validation
                </label>

                <select name="status" class="form-select">

                    <option value="">All</option>

                    <option value="valid" @selected(request('status') === 'valid')>
                        Valid
                    </option>

                    <option value="warning" @selected(request('status') === 'warning')>
                        Warning
                    </option>

                    <option value="error" @selected(request('status') === 'error')>
                        Error
                    </option>

                </select>

            </div>


            <div class="col-md-3">

                <label class="form-label">
                    Duplicate
                </label>

                <select name="duplicate" class="form-select">

                    <option value="">All</option>

                    <option value="none" @selected(request('duplicate') === 'none')>
                        No Duplicate
                    </option>

                    <option value="exact" @selected(request('duplicate') === 'exact')>
                        Exact Match
                    </option>

                </select>

            </div>


            <div class="col-md-3">

                <label class="form-label">
                    Decision
                </label>

                <select name="decision" class="form-select">

                    <option value="">All</option>

                    <option value="pending" @selected(request('decision') === 'pending')>
                        Pending
                    </option>

                    <option value="create" @selected(request('decision') === 'create')>
                        Create
                    </option>

                    <option value="update" @selected(request('decision') === 'update')>
                        Update Existing
                    </option>

                    <option value="use_existing" @selected(request('decision') === 'use_existing')>
                        Use Existing
                    </option>

                    <option value="reject" @selected(request('decision') === 'reject')>
                        Rejected
                    </option>

                </select>

            </div>


            <div class="col-md-3 d-flex align-items-end">

                <button type="submit" class="btn btn-primary me-2">
                    Filter
                </button>

                <a href="{{ route('pensions-administration.updates.employer-imports.review', $batch) }}"
                   class="btn btn-light">
                    Clear
                </a>

            </div>

        </form>


        <div class="table-responsive">

            <table class="table table-bordered table-striped table-hover align-middle">

                <thead>

                    <tr>

                        <th>Row</th>

                        <th>Employer</th>

                        <th>Group</th>

                        <th>PENERP No.</th>

                        <th>PenAd No.</th>

                        <th>Fundworx No.</th>

                        <th>Validation</th>

                        <th>Decision</th>

                        <th>Imported Employer</th>

                        <th style="min-width:300px;">
                            Details
                        </th>

                    </tr>

                </thead>


                <tbody>

                @forelse($rows as $row)

                    @php
                        $data = $row->normalized_data ?? [];
                    @endphp


                    <tr>

                        <td>
                            {{ $row->row_number }}
                        </td>


                        <td>

                            <strong>
                                {{ $data['employer_name'] ?? '-' }}
                            </strong>

                            @if(!empty($data['short_name']))
                                <br>

                                <small class="text-muted">
                                    {{ $data['short_name'] }}
                                </small>
                            @endif

                        </td>


                        <td>

                            {{ $row->matchedEmployerGroup?->name ?? '-' }}

                            @if($row->matchedEmployerGroup)

                                <br>

                                <small class="text-muted">
                                    {{ $row->matchedEmployerGroup->code }}
                                </small>

                            @endif

                        </td>


                        <td>

                            @if($row->importedEmployer)

                                {{ $row->importedEmployer->employer_number }}

                            @else

                                <span class="text-muted">
                                    Assigned from PenAd on import
                                </span>

                            @endif

                        </td>


                        <td>
                            {{ $data['penad_employer_number'] ?? '-' }}
                        </td>


                        <td>
                            {{ $data['fundworx_employer_number'] ?? '-' }}
                        </td>


                        <td>

                            @if($row->validation_status === 'valid')

                                <span class="badge bg-success">
                                    Valid
                                </span>

                            @elseif($row->validation_status === 'warning')

                                <span class="badge bg-warning">
                                    Warning
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    Error
                                </span>

                            @endif

                        </td>


                        <td>

                            @if($row->review_decision === 'pending')

                                <span class="badge bg-secondary">
                                    Pending
                                </span>

                            @elseif($row->review_decision === 'reject')

                                <span class="badge bg-danger">
                                    Rejected
                                </span>

                            @else

                                <span class="badge bg-success">

                                    {{ ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $row->review_decision
                                        )
                                    ) }}

                                </span>

                            @endif

                        </td>


                        <td>

                            @if($row->importedEmployer)

                                <a href="{{ route('pensions-administration.updates.employers.show', $row->importedEmployer) }}">

                                    {{ $row->importedEmployer->employer_number }}

                                </a>

                                <br>

                                <small>
                                    {{ $row->importedEmployer->name }}
                                </small>

                            @else

                                <span class="text-muted">
                                    Not imported
                                </span>

                            @endif

                        </td>


                        <td style="white-space:normal;">

                            @foreach(($row->error_messages ?? []) as $message)

                                <div class="text-danger mb-1">

                                    <i class="mdi mdi-alert-circle-outline me-1"></i>

                                    {{ $message }}

                                </div>

                            @endforeach


                            @foreach(($row->warning_messages ?? []) as $message)

                                <div class="text-warning mb-1">

                                    <i class="mdi mdi-alert-outline me-1"></i>

                                    {{ $message }}

                                </div>

                            @endforeach


                            @foreach(($row->duplicate_reasons ?? []) as $message)

                                <div class="text-info mb-1">

                                    <i class="mdi mdi-content-copy me-1"></i>

                                    {{ $message }}

                                </div>

                            @endforeach


                            @if(
                                empty($row->error_messages)
                                && empty($row->warning_messages)
                                && empty($row->duplicate_reasons)
                            )

                                @if($row->imported_at)

                                    <span class="text-primary">

                                        <i class="mdi mdi-check-decagram-outline me-1"></i>

                                        Imported
                                        {{ $row->imported_at->format('d M Y H:i') }}

                                    </span>

                                @else

                                    <span class="text-success">

                                        <i class="mdi mdi-check-circle-outline me-1"></i>

                                        Ready for import.

                                    </span>

                                @endif

                            @endif

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td colspan="10"
                            class="text-center text-muted py-4">

                            No employer records match the selected filters.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-3">
            {{ $rows->links() }}
        </div>

    </div>
</div>


@endsection