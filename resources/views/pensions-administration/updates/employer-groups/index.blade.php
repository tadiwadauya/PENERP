@extends('layouts.app')

@section('title', 'Employer Groups')

@section('page-heading', 'Employer Groups')

@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.employer-groups.create'
        ) }}"
        class="btn btn-success"
    >
        <i class="mdi mdi-plus me-1"></i>
        Add Employer Group
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
            Employer Groups
        </h4>

        <p class="card-title-desc">
            Maintain the grouping of LAPF participating employers.
        </p>


        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Employers</th>
                        <th>Vote Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($groups as $group)

                        <tr>

                            <td>
                                {{ $group->code }}
                            </td>

                            <td>
                                <strong>
                                    {{ $group->name }}
                                </strong>
                            </td>

                            <td>
                                {{ $group->employers_count }}
                            </td>

                            <td>

                                @if($group->vote_number_required)

                                    <span class="badge bg-warning">
                                        Required
                                    </span>

                                @else

                                    <span class="badge bg-light text-dark">
                                        Optional
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($group->is_active)

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
                                        'pensions-administration.updates.employer-groups.edit',
                                        $group
                                    ) }}"
                                    class="btn btn-sm btn-primary"
                                >
                                    Edit
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="text-center"
                            >
                                No employer groups found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection