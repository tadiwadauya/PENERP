@extends('layouts.app')

@section(
    'title',
    'Updates Dashboard'
)

@section(
    'page-heading',
    'Updates / Membership'
)


@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.members.create'
        ) }}"
        class="btn btn-success"
    >

        <i
            class="mdi mdi-account-plus-outline me-1"
        ></i>

        Add Member

    </a>

@endsection


@section('content')


@include(
    'pensions-administration.partials.navigation'
)



{{-- =========================================================
     SUMMARY CARDS
========================================================= --}}

<div class="row">


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <p class="font-size-16">
                    Total Members
                </p>

                <div
                    class="mini-stat-icon
                           mx-auto
                           mb-4 mt-3"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-primary"
                    >

                        <i
                            class="mdi
                                   mdi-account-group-outline
                                   text-primary
                                   font-size-20"
                        ></i>

                    </span>

                </div>


                <h5 class="font-size-22">

                    {{
                        number_format(
                            $statistics[
                                'total_members'
                            ]
                        )
                    }}

                </h5>

            </div>

        </div>

    </div>



    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <p class="font-size-16">
                    Active Members
                </p>

                <div
                    class="mini-stat-icon
                           mx-auto
                           mb-4 mt-3"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-success"
                    >

                        <i
                            class="mdi
                                   mdi-account-check-outline
                                   text-success
                                   font-size-20"
                        ></i>

                    </span>

                </div>


                <h5 class="font-size-22">

                    {{
                        number_format(
                            $statistics[
                                'active_members'
                            ]
                        )
                    }}

                </h5>

            </div>

        </div>

    </div>



    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <p class="font-size-16">
                    Dormant Members
                </p>

                <div
                    class="mini-stat-icon
                           mx-auto
                           mb-4 mt-3"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-warning"
                    >

                        <i
                            class="mdi
                                   mdi-account-clock-outline
                                   text-warning
                                   font-size-20"
                        ></i>

                    </span>

                </div>


                <h5 class="font-size-22">

                    {{
                        number_format(
                            $statistics[
                                'dormant_members'
                            ]
                        )
                    }}

                </h5>

            </div>

        </div>

    </div>



    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <p class="font-size-16">
                    Active Employers
                </p>

                <div
                    class="mini-stat-icon
                           mx-auto
                           mb-4 mt-3"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-info"
                    >

                        <i
                            class="mdi
                                   mdi-office-building-outline
                                   text-info
                                   font-size-20"
                        ></i>

                    </span>

                </div>


                <h5 class="font-size-22">

                    {{
                        number_format(
                            $statistics[
                                'active_employers'
                            ]
                        )
                    }}

                </h5>

            </div>

        </div>

    </div>


</div>



{{-- =========================================================
     QUICK ACTIONS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title mb-4">
            Membership Management
        </h4>


        <div class="row">


            <div class="col-xl-4 col-md-6 mb-3">

                <a
                    href="{{ route(
                        'pensions-administration.updates.members.index'
                    ) }}"
                    class="text-reset"
                >

                    <div
                        class="border
                               rounded
                               p-4
                               h-100"
                    >

                        <div class="d-flex">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-primary"
                                >

                                    <i
                                        class="mdi
                                               mdi-account-group-outline
                                               text-primary
                                               font-size-20"
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5>
                                    Members
                                </h5>

                                <p class="text-muted mb-0">
                                    View and maintain the
                                    membership register.
                                </p>

                            </div>

                        </div>

                    </div>

                </a>

            </div>



            <div class="col-xl-4 col-md-6 mb-3">

                <a
                    href="{{ route(
                        'pensions-administration.updates.employers.index'
                    ) }}"
                    class="text-reset"
                >

                    <div
                        class="border
                               rounded
                               p-4
                               h-100"
                    >

                        <div class="d-flex">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-success"
                                >

                                    <i
                                        class="mdi
                                               mdi-office-building-outline
                                               text-success
                                               font-size-20"
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5>
                                    Employers
                                </h5>

                                <p class="text-muted mb-0">
                                    Manage participating
                                    local authorities.
                                </p>

                            </div>

                        </div>

                    </div>

                </a>

            </div>



            <div class="col-xl-4 col-md-6 mb-3">

                <a
                    href="{{ route(
                        'pensions-administration.updates.employer-groups.index'
                    ) }}"
                    class="text-reset"
                >

                    <div
                        class="border
                               rounded
                               p-4
                               h-100"
                    >

                        <div class="d-flex">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-warning"
                                >

                                    <i
                                        class="mdi
                                               mdi-folder-multiple-outline
                                               text-warning
                                               font-size-20"
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5>
                                    Employer Groups
                                </h5>

                                <p class="text-muted mb-0">
                                    Maintain local authority
                                    group classifications.
                                </p>

                            </div>

                        </div>

                    </div>

                </a>

            </div>


        </div>

    </div>

</div>



{{-- =========================================================
     MONTHLY MOVEMENTS
========================================================= --}}

<div class="row">


    <div class="col-xl-4">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title mb-4">
                    This Month
                </h4>


                <ul
                    class="list-group
                           list-group-flush"
                >


                    <li
                        class="list-group-item
                               d-flex
                               justify-content-between
                               align-items-center"
                    >

                        New Members

                        <span
                            class="badge
                                   bg-primary
                                   rounded-pill"
                        >
                            {{
                                number_format(
                                    $monthlyMovements[
                                        'new_members'
                                    ]
                                )
                            }}
                        </span>

                    </li>


                    <li
                        class="list-group-item
                               d-flex
                               justify-content-between
                               align-items-center"
                    >

                        Reinstatements

                        <span
                            class="badge
                                   bg-success
                                   rounded-pill"
                        >
                            {{
                                number_format(
                                    $monthlyMovements[
                                        'reinstatements'
                                    ]
                                )
                            }}
                        </span>

                    </li>


                    <li
                        class="list-group-item
                               d-flex
                               justify-content-between
                               align-items-center"
                    >

                        Suspensions

                        <span
                            class="badge
                                   bg-warning
                                   rounded-pill"
                        >
                            {{
                                number_format(
                                    $monthlyMovements[
                                        'suspensions'
                                    ]
                                )
                            }}
                        </span>

                    </li>


                    <li
                        class="list-group-item
                               d-flex
                               justify-content-between
                               align-items-center"
                    >

                        Other Movements

                        <span
                            class="badge
                                   bg-secondary
                                   rounded-pill"
                        >
                            {{
                                number_format(
                                    $monthlyMovements[
                                        'other_movements'
                                    ]
                                )
                            }}
                        </span>

                    </li>


                </ul>

            </div>

        </div>

    </div>



    {{-- =====================================================
         RECENT MEMBERS
    ====================================================== --}}

    <div class="col-xl-8">

        <div class="card">

            <div class="card-body">

                <div
                    class="d-flex
                           justify-content-between
                           align-items-center
                           mb-4"
                >

                    <h4 class="header-title mb-0">
                        Recently Added Members
                    </h4>


                    <a
                        href="{{ route(
                            'pensions-administration.updates.members.index'
                        ) }}"
                    >
                        View All
                    </a>

                </div>


                <div class="table-responsive">

                    <table
                        class="table
                               table-centered
                               table-nowrap
                               mb-0"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Member No.
                                </th>

                                <th>
                                    Member
                                </th>

                                <th>
                                    Employer
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse(
                                $recentMembers
                                as $member
                            )

                                <tr>

                                    <td>

                                        <a
                                            href="{{ route(
                                                'pensions-administration.updates.members.show',
                                                $member
                                            ) }}"
                                        >
                                            {{
                                                $member
                                                    ->member_number
                                            }}
                                        </a>

                                    </td>


                                    <td>

                                        <strong>

                                            {{
                                                $member
                                                    ->surname
                                            }},

                                            {{
                                                $member
                                                    ->first_names
                                            }}

                                        </strong>

                                    </td>


                                    <td>

                                        {{
                                            $member
                                                ->currentEmployment
                                                ?->employer
                                                ?->name
                                            ?? '-'
                                        }}

                                    </td>


                                    <td>

                                        <span
                                            class="badge
                                            {{
                                                $member
                                                    ->membership_status
                                                === 'active'
                                                    ? 'bg-success'
                                                    : 'bg-secondary'
                                            }}"
                                        >

                                            {{
                                                ucfirst(
                                                    $member
                                                        ->membership_status
                                                )
                                            }}

                                        </span>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="4"
                                        class="text-center
                                               text-muted"
                                    >
                                        No members created yet.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


</div>



{{-- =========================================================
     RECENT MOVEMENTS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title mb-4">
            Recent Membership Movements
        </h4>


        <div class="table-responsive">

            <table class="table table-bordered">

                <thead>

                    <tr>

                        <th>Date</th>
                        <th>Member</th>
                        <th>Movement</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Reason</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $recentMovements
                        as $movement
                    )

                        <tr>

                            <td>

                                {{
                                    $movement
                                        ->effective_date
                                        ?->format('d M Y')
                                    ?? '-'
                                }}

                            </td>


                            <td>

                                @if($movement->member)

                                    <a
                                        href="{{ route(
                                            'pensions-administration.updates.members.show',
                                            $movement->member
                                        ) }}"
                                    >

                                        {{
                                            $movement
                                                ->member
                                                ->member_number
                                        }}

                                    </a>

                                    <br>

                                    <small
                                        class="text-muted"
                                    >

                                        {{
                                            $movement
                                                ->member
                                                ->surname
                                        }},

                                        {{
                                            $movement
                                                ->member
                                                ->first_names
                                        }}

                                    </small>

                                @else

                                    -

                                @endif

                            </td>


                            <td>

                                {{
                                    ucwords(
                                        strtolower(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $movement
                                                    ->movement_type
                                                ?? ''
                                            )
                                        )
                                    )
                                }}

                            </td>


                            <td>

                                {{
                                    $movement->old_status
                                        ? ucfirst(
                                            $movement
                                                ->old_status
                                        )
                                        : '-'
                                }}

                            </td>


                            <td>

                                {{
                                    ucfirst(
                                        $movement
                                            ->new_status
                                    )
                                }}

                            </td>


                            <td>

                                {{
                                    $movement->reason
                                    ?? '-'
                                }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="text-center text-muted"
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

@endsection