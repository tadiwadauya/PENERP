@extends('layouts.app')

@section('title', 'Actuarial Data Extract')
@section('page-heading', 'Actuarial Data Extract')
@section('page-subheading', 'Generate valuation data from PENERP membership and contribution records')

@section('content')

@include('pensions-administration.partials.navigation')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">

    <div class="col-xl-8">

        <div class="card">
            <div class="card-body">

                <h4 class="header-title mb-1">Generate Actuarial Extract</h4>

                <p class="text-muted mb-4">
                    Select the valuation period. Month-by-month salary and contribution
                    columns will be generated automatically for the selected period.
                </p>

                <form method="POST"
                      action="{{ route('pensions-administration.reports.actuarial-data.store') }}">

                    @csrf

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">From Date</label>

                            <input type="date"
                                   name="date_from"
                                   class="form-control @error('date_from') is-invalid @enderror"
                                   value="{{ old('date_from', now()->startOfYear()->format('Y-m-d')) }}"
                                   required>

                            @error('date_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">To Date / Valuation Date</label>

                            <input type="date"
                                   name="date_to"
                                   class="form-control @error('date_to') is-invalid @enderror"
                                   value="{{ old('date_to', now()->format('Y-m-d')) }}"
                                   required>

                            @error('date_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Employer</label>

                            <select name="employer_id" class="form-select">
                                <option value="">All Employers</option>

                                @foreach($employers as $employer)
                                    <option value="{{ $employer->id }}"
                                        @selected((string) old('employer_id') === (string) $employer->id)>
                                        {{ $employer->penad_employer_number ?: $employer->employer_number }}
                                        - {{ $employer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div class="alert alert-info">
                        <strong>Workbook output:</strong>
                        Active Members, Pending Exits / Nil Contributors and Exited Members.
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-file-excel-outline me-1"></i>
                        Generate Extract
                    </button>

                </form>

            </div>
        </div>

    </div>

</div>

<div class="card mt-3">

    <div class="card-body">

        <h4 class="header-title mb-3">Recent Extracts</h4>

        <div class="table-responsive">

            <table class="table table-bordered table-hover align-middle">

                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Period</th>
                        <th>Employer</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Generated</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($batches as $batch)

                        <tr>
                            <td>{{ $batch->batch_number }}</td>

                            <td>
                                {{ $batch->date_from->format('d M Y') }}
                                -
                                {{ $batch->date_to->format('d M Y') }}
                            </td>

                            <td>
                                {{ $batch->employer?->name ?? 'All Employers' }}
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    {{ ucfirst(str_replace('_', ' ', $batch->status)) }}
                                </span>
                            </td>

                            <td>{{ number_format((float) $batch->progress_percentage, 0) }}%</td>

                            <td>{{ $batch->created_at->format('d M Y H:i') }}</td>

                            <td>
                                <a href="{{ route('pensions-administration.reports.actuarial-data.show', $batch) }}"
                                   class="btn btn-sm btn-primary">
                                    View
                                </a>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No actuarial extracts generated yet.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection