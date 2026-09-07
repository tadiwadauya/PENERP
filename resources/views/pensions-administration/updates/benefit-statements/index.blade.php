@extends('layouts.app')

@section('title', 'Benefit Statements')

@section('page-heading', 'Benefit Statements')

@section('page-subheading')
Generate member benefit statements as at any selected date
@endsection

@push('styles')
<style>
    .benefit-card {
        height: 100%;
        border: 0;
        box-shadow: 0 2px 10px rgba(0,0,0,.05);
    }

    .benefit-member-table th {
        white-space: nowrap;
        vertical-align: middle;
    }

    .benefit-member-table td {
        vertical-align: middle;
    }

    .member-name {
        font-weight: 600;
    }

    .member-reference {
        font-size: 12px;
    }

    .statement-date-box {
        background: rgba(13,110,253,.05);
        border: 1px solid rgba(13,110,253,.15);
        border-radius: 6px;
        padding: 15px;
    }
</style>
@endpush

@section('content')

@include('pensions-administration.partials.navigation')

<div class="row">
    <div class="col-12">
        <div class="card benefit-card">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-1"><i class="mdi mdi-file-document-outline me-1"></i> Generate Benefit Statement</h5>
                <p class="text-muted mb-0">Select the statement date first, then search for the member whose Benefit Statement you want to generate.</p>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('pensions-administration.updates.benefit-statements.index') }}">
                    <div class="statement-date-box mb-4">
                        <div class="row align-items-end">
                            <div class="col-lg-4 col-md-6">
                                <label for="statement_date" class="form-label">Statement Date <span class="text-danger">*</span></label>
                                <input type="date" id="statement_date" name="statement_date" class="form-control" value="{{ old('statement_date',$statementDate) }}" max="{{ now()->toDateString() }}" required>
                                <div class="form-text">All Benefit Statement calculations will be performed strictly as at this date.</div>
                            </div>

                            @if($statementDate)
                            <div class="col-lg-8 col-md-6 mt-3 mt-md-0">
                                <div class="d-flex align-items-center h-100">
                                    <div>
                                        <div class="text-muted small">Selected Statement Date</div>
                                        <div class="fw-semibold fs-5">{{ \Carbon\Carbon::parse($statementDate)->format('d/m/Y') }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-9 col-md-8">
                            <label for="search" class="form-label">Search Member</label>
                            <input type="text" id="search" name="search" class="form-control" value="{{ $query }}" placeholder="Member number, PenAd number, Fundworx number, National ID, surname or first name" required>
                        </div>

                        <div class="col-lg-3 col-md-4">
                            <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-magnify me-1"></i> Search Member</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($query !== '')
<div class="row">
    <div class="col-12">
        <div class="card benefit-card">
            <div class="card-header bg-transparent border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-1">Member Search Results</h5>
                        @if($statementDate)
                            <p class="text-muted mb-0">Statement date: <strong>{{ \Carbon\Carbon::parse($statementDate)->format('d/m/Y') }}</strong></p>
                        @endif
                    </div>

                    <span class="badge bg-light text-dark">{{ $members->count() }} {{ \Illuminate\Support\Str::plural('member',$members->count()) }} found</span>
                </div>
            </div>

            <div class="card-body">
                @if(!$statementDate)
                    <div class="alert alert-warning mb-0">
                        <i class="mdi mdi-alert-outline me-1"></i> Select a Statement Date before generating a Benefit Statement.
                    </div>
                @elseif($members->isEmpty())
                    <div class="text-center py-5">
                        <i class="mdi mdi-account-search-outline text-muted" style="font-size:48px;"></i>
                        <h5 class="mt-2">No Members Found</h5>
                        <p class="text-muted mb-0">No member matched <strong>{{ $query }}</strong>.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover benefit-member-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Member</th>
                                    <th>PENERP Number</th>
                                    <th>PenAd Number</th>
                                    <th>Fundworx Number</th>
                                    <th>National ID</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($members as $member)
                                <tr>
                                    <td>
                                        <div class="member-name">{{ $member->first_names }} {{ $member->surname }}</div>
                                        @if($member->date_of_birth)
                                            <div class="member-reference text-muted">DOB: {{ \Carbon\Carbon::parse($member->date_of_birth)->format('d/m/Y') }}</div>
                                        @endif
                                    </td>

                                    <td>{{ $member->member_number ?: '-' }}</td>
                                    <td>{{ $member->penad_member_number ?: '-' }}</td>
                                    <td>{{ $member->fundworx_member_number ?: '-' }}</td>
                                    <td>{{ $member->national_id ?: '-' }}</td>
                                    <td>{{ $member->gender ?: '-' }}</td>

                                    <td>
                                        @if(strtolower((string)$member->membership_status)==='active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($member->membership_status ?: 'Unknown') }}</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <form method="POST" action="{{ route('pensions-administration.updates.benefit-statements.preview') }}">
                                            @csrf
                                            <input type="hidden" name="member_id" value="{{ $member->id }}">
                                            <input type="hidden" name="statement_date" value="{{ $statementDate }}">
                                            <input type="hidden" name="search" value="{{ $query }}">
                                            <button type="submit" class="btn btn-primary btn-sm"><i class="mdi mdi-file-document-outline me-1"></i> Generate Statement</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@endsection