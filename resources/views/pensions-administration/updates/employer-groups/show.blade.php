@extends('layouts.app')

@section('title', 'Employer Group Details')

@section('page-heading', 'Employer Group Details')

@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.employer-groups.edit',
            $employerGroup
        ) }}"
        class="btn btn-primary"
    >
        <i class="mdi mdi-pencil me-1"></i>
        Edit Group
    </a>

@endsection


@section('content')
@include(
    'pensions-administration.partials.navigation'
)

<div class="card">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-start">

            <div>

                <h4 class="header-title mb-2">
                    {{ $employerGroup->name }}
                </h4>

                <p class="text-muted mb-0">
                    {{ $employerGroup->code }}
                </p>

            </div>


            <div>

                @if($employerGroup->is_active)

                    <span class="badge bg-success">
                        Active
                    </span>

                @else

                    <span class="badge bg-secondary">
                        Inactive
                    </span>

                @endif

            </div>

        </div>


        <hr>


        <div class="row">


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Group Code
                </small>

                <strong>
                    {{ $employerGroup->code }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Group Name
                </small>

                <strong>
                    {{ $employerGroup->name }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Vote Number Requirement
                </small>

                @if($employerGroup->vote_number_required)

                    <span class="badge bg-warning">
                        Required
                    </span>

                @else

                    <span class="badge bg-light text-dark">
                        Optional
                    </span>

                @endif

            </div>


            <div class="col-12">

                <small class="text-muted d-block">
                    Description
                </small>

                <p>
                    {{
                        $employerGroup->description
                        ?: 'No description provided.'
                    }}
                </p>

            </div>

        </div>

    </div>

</div>


<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Employers in this Group
        </h4>


        <div class="table-responsive">

            <table class="table table-bordered">

                <thead>

                    <tr>
                        <th>Employer Number</th>
                        <th>Employer</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse(
                        $employerGroup->employers
                        as $employer
                    )

                        <tr>

                            <td>
                                {{ $employer->employer_number }}
                            </td>

                            <td>
                                {{ $employer->name }}
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

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="4"
                                class="text-center"
                            >
                                No employers assigned to this group.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


<a
    href="{{ route(
        'pensions-administration.updates.employer-groups.index'
    ) }}"
    class="btn btn-light"
>
    <i class="mdi mdi-arrow-left me-1"></i>
    Back to Employer Groups
</a>

@endsection