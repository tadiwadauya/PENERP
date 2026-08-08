@extends('layouts.app')

@section('title', 'Employers')

@section('page-heading', 'Employers')

@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.employers.create'
        ) }}"
        class="btn btn-success"
    >
        <i class="mdi mdi-office-building-plus me-1"></i>
        Add Employer
    </a>

@endsection


@section('content')
@include(
    'pensions-administration.partials.navigation'
)

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
            Participating Employers
        </h4>

        <p class="card-title-desc">
            Local authorities and other employers participating
            in LAPF.
        </p>


        <div class="table-responsive">

            <table
                id="employers-table"
                class="table table-bordered table-striped"
            >

                <thead>

                    <tr>

                        <th>PENERP No.</th>
                        <th>PenAd No.</th>
                        <th>Fundworx No.</th>
                        <th>Employer</th>
                        <th>Group</th>
                        <th>Members</th>
                        <th>Contacts</th>
                        <th>Status</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($employers as $employer)

                        <tr>

                            <td>
                                {{ $employer->employer_number }}
                            </td>

                            <td>
                                {{ $employer->penad_employer_number ?? '-' }}
                            </td>

                            <td>
                                {{ $employer->fundworx_employer_number ?? '-' }}
                            </td>

                            <td>

                                <strong>
                                    {{ $employer->name }}
                                </strong>

                                @if($employer->short_name)

                                    <br>

                                    <small class="text-muted">
                                        {{ $employer->short_name }}
                                    </small>

                                @endif

                            </td>

                            <td>
                                {{ $employer->employerGroup?->name ?? '-' }}
                            </td>

                            <td>
                                {{ $employer->current_member_employments_count }}
                            </td>

                            <td>
                                {{ $employer->contacts_count }}
                            </td>

                            <td>

                                @if($employer->is_active)

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        Inactive
                                    </span>

                                @endif

                            </td>

                            <td>

                                <a
                                    href="{{ route(
                                        'pensions-administration.updates.employers.show',
                                        $employer
                                    ) }}"
                                    class="btn btn-sm btn-info"
                                >
                                    View
                                </a>

                                <a
                                    href="{{ route(
                                        'pensions-administration.updates.employers.edit',
                                        $employer
                                    ) }}"
                                    class="btn btn-sm btn-primary"
                                >
                                    Edit
                                </a>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection