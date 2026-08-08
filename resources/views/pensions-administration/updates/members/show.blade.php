@extends('layouts.app')

@section('title', 'Member Details')

@section('page-heading', 'Member Details')


@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.members.edit',
            $member
        ) }}"
        class="btn btn-primary"
    >
        <i class="mdi mdi-pencil me-1"></i>
        Edit Member
    </a>

@endsection


@section('content')

@include(
    'pensions-administration.partials.navigation'
)

@if(session('success'))

    <div
        class="alert alert-success alert-dismissible fade show"
    >

        {{ session('success') }}

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

@endif



{{-- =========================================================
     MEMBER HEADER
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div
            class="d-flex flex-wrap justify-content-between align-items-start"
        >

            <div>

                <h3 class="mb-1">

                    {{ $member->surname }},
                    {{ $member->first_names }}

                </h3>


                <p class="text-muted mb-2">

                    {{ $member->member_number }}

                </p>


                <div class="d-flex gap-2 flex-wrap">

                    @if(
                        $member->membership_status
                        === 'active'
                    )

                        <span class="badge bg-success">
                            Active Member
                        </span>

                    @elseif(
                        $member->membership_status
                        === 'dormant'
                    )

                        <span class="badge bg-warning">
                            Dormant
                        </span>

                    @elseif(
                        $member->membership_status
                        === 'suspended'
                    )

                        <span class="badge bg-danger">
                            Suspended
                        </span>

                    @else

                        <span class="badge bg-secondary">
                            {{
                                ucfirst(
                                    $member
                                        ->membership_status
                                )
                            }}
                        </span>

                    @endif

                </div>

            </div>


            <div class="text-end">

                @if(
                    $member->currentEmployment
                )

                    <small
                        class="text-muted d-block"
                    >
                        Current Employer
                    </small>

                    <strong>

                        {{
                            $member
                                ->currentEmployment
                                ->employer
                                ?->name
                        }}

                    </strong>

                @endif

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     REFERENCES
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Membership References
        </h4>


        <div class="row">


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    PENERP Member Number
                </small>

                <strong>
                    {{ $member->member_number }}
                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    PenAd Member Number
                </small>

                <strong>
                    {{
                        $member
                            ->penad_member_number
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Fundworx Member Number
                </small>

                <strong>
                    {{
                        $member
                            ->fundworx_member_number
                        ?? '-'
                    }}
                </strong>

            </div>


        </div>

    </div>

</div>



{{-- =========================================================
     PERSONAL DETAILS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Personal Details
        </h4>


        <div class="row">


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    National ID
                </small>

                <strong>
                    {{ $member->national_id ?? '-' }}
                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Date of Birth
                </small>

                <strong>

                    {{
                        $member->date_of_birth
                            ? $member
                                ->date_of_birth
                                ->format('d M Y')
                            : '-'
                    }}

                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Gender
                </small>

                <strong>
                    {{ $member->gender ?? '-' }}
                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Marital Status
                </small>

                <strong>
                    {{
                        $member->marital_status
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Occupation
                </small>

                <strong>
                    {{ $member->occupation ?? '-' }}
                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Date Joined Fund
                </small>

                <strong>

                    {{
                        $member->date_joined_fund
                            ? $member
                                ->date_joined_fund
                                ->format('d M Y')
                            : '-'
                    }}

                </strong>

            </div>


        </div>

    </div>

</div>



{{-- =========================================================
     CURRENT EMPLOYMENT
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Current Employment
        </h4>


        @if($member->currentEmployment)

            <div class="row">


                <div class="col-md-4 mb-3">

                    <small class="text-muted d-block">
                        Employer
                    </small>

                    <strong>

                        {{
                            $member
                                ->currentEmployment
                                ->employer
                                ?->name
                        }}

                    </strong>

                </div>


                <div class="col-md-2 mb-3">

                    <small class="text-muted d-block">
                        Staff Number
                    </small>

                    <strong>

                        {{
                            $member
                                ->currentEmployment
                                ->staff_number
                            ?? '-'
                        }}

                    </strong>

                </div>


                <div class="col-md-2 mb-3">

                    <small class="text-muted d-block">
                        Vote Number
                    </small>

                    <strong>

                        {{
                            $member
                                ->currentEmployment
                                ->vote_number
                            ?? '-'
                        }}

                    </strong>

                </div>


                <div class="col-md-4 mb-3">

                    <small class="text-muted d-block">
                        Date Joined Employer
                    </small>

                    <strong>

                        {{
                            $member
                                ->currentEmployment
                                ->date_joined_employer
                                ?->format('d M Y')
                            ?? '-'
                        }}

                    </strong>

                </div>


                <div class="col-md-4 mb-3">

                    <small class="text-muted d-block">
                        Department
                    </small>

                    <strong>

                        {{
                            $member
                                ->currentEmployment
                                ->department
                            ?? '-'
                        }}

                    </strong>

                </div>


                <div class="col-md-4 mb-3">

                    <small class="text-muted d-block">
                        Branch
                    </small>

                    <strong>

                        {{
                            $member
                                ->currentEmployment
                                ->branch
                            ?? '-'
                        }}

                    </strong>

                </div>


                <div class="col-md-4 mb-3">

                    <small class="text-muted d-block">
                        Employer Group
                    </small>

                    <strong>

                        {{
                            $member
                                ->currentEmployment
                                ->employer
                                ?->employerGroup
                                ?->name
                            ?? '-'
                        }}

                    </strong>

                </div>


            </div>

        @else

            <div class="alert alert-warning mb-0">

                This member currently has no active
                employer relationship.

            </div>

        @endif

    </div>

</div>



{{-- =========================================================
     EMPLOYMENT HISTORY
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Employment History
        </h4>


        <div class="table-responsive">

            <table class="table table-bordered">

                <thead>

                    <tr>

                        <th>Employer</th>
                        <th>Staff No.</th>
                        <th>Vote No.</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $member->employments
                        as $employment
                    )

                        <tr>

                            <td>

                                {{
                                    $employment
                                        ->employer
                                        ?->name
                                    ?? '-'
                                }}

                            </td>

                            <td>
                                {{
                                    $employment
                                        ->staff_number
                                    ?? '-'
                                }}
                            </td>

                            <td>
                                {{
                                    $employment
                                        ->vote_number
                                    ?? '-'
                                }}
                            </td>

                            <td>

                                {{
                                    $employment
                                        ->effective_from
                                        ?->format('d M Y')
                                    ?? '-'
                                }}

                            </td>

                            <td>

                                {{
                                    $employment
                                        ->effective_to
                                        ?->format('d M Y')
                                    ?? '-'
                                }}

                            </td>

                            <td>

                                @if($employment->is_current)

                                    <span
                                        class="badge bg-success"
                                    >
                                        Current
                                    </span>

                                @else

                                    <span
                                        class="badge bg-secondary"
                                    >
                                        {{
                                            ucfirst(
                                                $employment
                                                    ->employment_status
                                            )
                                        }}
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="text-center"
                            >
                                No employment history available.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>



{{-- =========================================================
     MEMBERSHIP MOVEMENT HISTORY
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Membership Movement History
        </h4>


        <div class="table-responsive">

            <table class="table table-bordered">

                <thead>

                    <tr>

                        <th>Effective Date</th>
                        <th>Movement</th>
                        <th>Previous Status</th>
                        <th>New Status</th>
                        <th>Reason</th>
                        <th>Source</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $member->statusHistories
                        as $history
                    )

                        <tr>

                            <td>

                                {{
                                    $history
                                        ->effective_date
                                        ?->format('d M Y')
                                    ?? '-'
                                }}

                            </td>


                            <td>

                                {{
                                    str_replace(
                                        '_',
                                        ' ',
                                        $history
                                            ->movement_type
                                        ?? '-'
                                    )
                                }}

                            </td>


                            <td>

                                {{
                                    $history->old_status
                                        ? ucfirst(
                                            $history
                                                ->old_status
                                        )
                                        : '-'
                                }}

                            </td>


                            <td>

                                <strong>

                                    {{
                                        ucfirst(
                                            $history
                                                ->new_status
                                        )
                                    }}

                                </strong>

                            </td>


                            <td>

                                {{
                                    $history->reason
                                    ?? '-'
                                }}

                            </td>


                            <td>

                                {{
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $history
                                                ->source
                                        )
                                    )
                                }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="text-center"
                            >
                                No membership movements recorded.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>



{{-- =========================================================
     CONTACT & ADDRESS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Contact & Address
        </h4>


        <div class="row">


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Email
                </small>

                <strong>
                    {{ $member->email ?? '-' }}
                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Cell Number
                </small>

                <strong>
                    {{
                        $member->cell_number
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Secondary Cell
                </small>

                <strong>
                    {{
                        $member
                            ->secondary_cell_number
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-6">

                <small class="text-muted d-block">
                    Physical Address
                </small>

                <p>

                    {{
                        collect([
                            $member
                                ->physical_address_1,

                            $member
                                ->physical_address_2,

                            $member
                                ->physical_address_3,

                            $member
                                ->physical_suburb,

                            $member
                                ->physical_city,

                            $member
                                ->physical_country,
                        ])
                        ->filter()
                        ->implode(', ')
                        ?: '-'
                    }}

                </p>

            </div>


            <div class="col-md-6">

                <small class="text-muted d-block">
                    Postal Address
                </small>

                <p>

                    {{
                        collect([
                            $member
                                ->postal_address_1,

                            $member
                                ->postal_address_2,

                            $member
                                ->postal_address_3,

                            $member
                                ->postal_city,

                            $member
                                ->postal_country,
                        ])
                        ->filter()
                        ->implode(', ')
                        ?: '-'
                    }}

                </p>

            </div>


        </div>

    </div>

</div>



<div class="d-flex justify-content-between mb-4">

    <a
        href="{{ route(
            'pensions-administration.updates.members.index'
        ) }}"
        class="btn btn-light"
    >
        <i class="mdi mdi-arrow-left me-1"></i>
        Back to Membership
    </a>


    <a
        href="{{ route(
            'pensions-administration.updates.members.edit',
            $member
        ) }}"
        class="btn btn-primary"
    >
        <i class="mdi mdi-pencil me-1"></i>
        Edit Member
    </a>

</div>

@endsection