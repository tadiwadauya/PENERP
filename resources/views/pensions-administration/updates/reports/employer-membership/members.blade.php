@extends('layouts.app')

@section('title', 'Employer Members')

@section('page-heading', 'Employer Members')

@section('page-subheading')
{{ $employer->name }}
@endsection


@push('styles')

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"
>

<style>

    .report-table th {
        white-space: nowrap;
    }

    .report-table td {
        vertical-align: middle;
    }

    .employer-info-card {
        border-left: 4px solid #0d6efd;
    }

    div.dataTables_processing {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
    }

</style>

@endpush


@section('content')

@include('pensions-administration.partials.navigation')


{{-- =========================================================
     EMPLOYER
========================================================= --}}

<div class="card employer-info-card mb-3">

    <div class="card-body">

        <div
            class="
                d-flex
                flex-wrap
                justify-content-between
                align-items-center
                gap-3
            "
        >

            <div>

                <h4 class="header-title mb-1">
                    {{ $employer->name }}
                </h4>

                <div class="text-muted">

                    PENERP:

                    <strong>
                        {{ $employer->employer_number }}
                    </strong>

                    &nbsp;|&nbsp;

                    PenAd:

                    <strong>
                        {{ $employer->penad_employer_number ?? '-' }}
                    </strong>

                    &nbsp;|&nbsp;

                    Fundworx:

                    <strong>
                        {{ $employer->fundworx_employer_number ?? '-' }}
                    </strong>

                </div>


                <div class="mt-2">

                    <span class="text-muted">
                        Latest Contribution Period:
                    </span>

                    <strong>

                        @if($latestContributionPeriod)

                            {{
                                \Carbon\Carbon::parse(
                                    $latestContributionPeriod
                                )->format(
                                    'F Y'
                                )
                            }}

                        @else

                            No contribution history

                        @endif

                    </strong>

                </div>

            </div>


            <a
                href="{{
                    route(
                        'pensions-administration.updates.reports.employer-membership.index'
                    )
                }}"
                class="btn btn-light"
            >

                <i class="mdi mdi-arrow-left me-1"></i>

                Back to Employers

            </a>

        </div>

    </div>

</div>


{{-- =========================================================
     MEMBERS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="row align-items-end mb-4">

            <div class="col-lg-4 col-md-6">

                <label class="form-label">
                    Membership / Contribution Type
                </label>

                <select
                    id="membership-status-filter"
                    class="form-select"
                >

                    <option
                        value=""
                        @selected(
                            $status === ''
                        )
                    >
                        All Members
                    </option>


                    <option
                        value="active"
                        @selected(
                            $status === 'active'
                        )
                    >
                        Active
                    </option>


                    <option
                        value="inactive"
                        @selected(
                            $status === 'inactive'
                        )
                    >
                        Inactive
                    </option>


                    <option
                        value="exited"
                        @selected(
                            $status === 'exited'
                        )
                    >
                        Exited
                    </option>


                    <option
                        value="suspended"
                        @selected(
                            $status === 'suspended'
                        )
                    >
                        Suspended
                    </option>


                    <option
                        value="waiting_approval"
                        @selected(
                            $status === 'waiting_approval'
                        )
                    >
                        Waiting Approval
                    </option>


                    <option
                        value="deferred"
                        @selected(
                            $status === 'deferred'
                        )
                    >
                        Deferred
                    </option>


                    <option
                        value="nil_contributor"
                        @selected(
                            $status === 'nil_contributor'
                        )
                    >
                        Nil Contributors
                    </option>

                </select>

            </div>


            <div class="col-lg-8 col-md-6 mt-3 mt-md-0">

                <div
                    id="membership-filter-description"
                    class="text-muted"
                >

                    @if($status === 'nil_contributor')

                        Showing active members who did not make a positive
                        contribution in the employer's latest contribution period.

                    @else

                        Only 25 members are loaded at a time.
                        Use search or pagination to navigate the employer membership.

                    @endif

                </div>

            </div>

        </div>


        @if($status === 'nil_contributor')

            <div
                id="nil-contributor-message"
                class="alert alert-warning"
            >

                <strong>
                    Nil Contributors
                </strong>

                <div>

                    These are active members who did not contribute in

                    <strong>

                        @if($latestContributionPeriod)

                            {{
                                \Carbon\Carbon::parse(
                                    $latestContributionPeriod
                                )->format(
                                    'F Y'
                                )
                            }}

                        @else

                            the latest contribution period

                        @endif

                    </strong>.

                    This does not change their membership status from Active.

                </div>

            </div>

        @else

            <div
                id="nil-contributor-message"
                class="alert alert-warning d-none"
            >

                <strong>
                    Nil Contributors
                </strong>

                <div>

                    These are active members who did not contribute in

                    <strong id="nil-period-label">

                        @if($latestContributionPeriod)

                            {{
                                \Carbon\Carbon::parse(
                                    $latestContributionPeriod
                                )->format(
                                    'F Y'
                                )
                            }}

                        @else

                            the latest contribution period

                        @endif

                    </strong>.

                    This does not change their membership status from Active.

                </div>

            </div>

        @endif


        <div class="table-responsive">

            <table
                id="employer-members-table"
                class="
                    table
                    table-bordered
                    table-striped
                    table-hover
                    report-table
                    w-100
                "
            >

                <thead>

                    <tr>

                        <th>
                            PENERP No.
                        </th>

                        <th>
                            PenAd No.
                        </th>

                        <th>
                            Fundworx No.
                        </th>

                        <th>
                            Staff No.
                        </th>

                        <th>
                            Member
                        </th>

                        <th>
                            National ID
                        </th>

                        <th>
                            DOB
                        </th>

                        <th>
                            Joined Fund
                        </th>

                        <th>
                            Joined Employer
                        </th>

                        <th>
                            Membership Status
                        </th>

                        <th>
                            Employment Status
                        </th>

                        <th>
                            Contribution Status
                        </th>

                        <th>
                            Contribution Period
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script
    src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"
></script>

<script
    src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"
></script>


<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Employer Members DataTable
    |--------------------------------------------------------------------------
    */

    const table =
        $('#employer-members-table')
            .DataTable({

                processing:
                    true,

                serverSide:
                    true,

                deferRender:
                    true,

                pageLength:
                    25,

                searchDelay:
                    500,

                lengthMenu: [
                    [
                        10,
                        25,
                        50,
                        100
                    ],
                    [
                        10,
                        25,
                        50,
                        100
                    ]
                ],

                ajax: {

                    url:
                        @json(
                            route(
                                'pensions-administration.updates.reports.employer-membership.members-data',
                                $employer
                            )
                        ),

                    type:
                        'GET',

                    data:
                        function (d) {

                            d.membership_status =
                                $('#membership-status-filter')
                                    .val();

                        }

                },

                columns: [

                    {
                        data:
                            'member_number'
                    },

                    {
                        data:
                            'penad_member_number'
                    },

                    {
                        data:
                            'fundworx_member_number'
                    },

                    {
                        data:
                            'staff_number'
                    },

                    {
                        data:
                            'member'
                    },

                    {
                        data:
                            'national_id'
                    },

                    {
                        data:
                            'date_of_birth'
                    },

                    {
                        data:
                            'date_joined_fund'
                    },

                    {
                        data:
                            'date_joined_employer'
                    },

                    {
                        data:
                            'membership_status'
                    },

                    {
                        data:
                            'employment_status'
                    },

                    {
                        data:
                            'contribution_status',

                        orderable:
                            false,

                        searchable:
                            false
                    },

                    {
                        data:
                            'latest_period',

                        orderable:
                            false,

                        searchable:
                            false
                    },

                    {
                        data:
                            'action',

                        orderable:
                            false,

                        searchable:
                            false
                    }

                ],

                order: [
                    [
                        4,
                        'asc'
                    ]
                ],

                language: {

                    processing:
                        '<span class="spinner-border spinner-border-sm me-2"></span> Loading members...',

                    search:
                        'Search:',

                    searchPlaceholder:
                        'Name, ID, member no., staff no...',

                    lengthMenu:
                        'Show _MENU_ members',

                    info:
                        'Showing _START_ to _END_ of _TOTAL_ members',

                    infoEmpty:
                        'No members found',

                    zeroRecords:
                        'No matching members found'

                }

            });


    /*
    |--------------------------------------------------------------------------
    | Membership Type Filter
    |--------------------------------------------------------------------------
    */

    $('#membership-status-filter')
        .on(
            'change',
            function () {

                const status =
                    $(this)
                        .val();


                /*
                |--------------------------------------------------------------------------
                | URL
                |--------------------------------------------------------------------------
                */

                const url =
                    new URL(
                        window.location.href
                    );

                if (status) {

                    url.searchParams.set(
                        'status',
                        status
                    );

                } else {

                    url.searchParams.delete(
                        'status'
                    );

                }

                history.replaceState(
                    null,
                    '',
                    url.toString()
                );


                /*
                |--------------------------------------------------------------------------
                | Explanation
                |--------------------------------------------------------------------------
                */

                if (
                    status
                    ===
                    'nil_contributor'
                ) {

                    $('#membership-filter-description')
                        .text(
                            'Showing active members who did not make a positive contribution in the employer\'s latest contribution period.'
                        );

                    $('#nil-contributor-message')
                        .removeClass(
                            'd-none'
                        );

                } else {

                    $('#membership-filter-description')
                        .text(
                            'Only 25 members are loaded at a time. Use search or pagination to navigate the employer membership.'
                        );

                    $('#nil-contributor-message')
                        .addClass(
                            'd-none'
                        );

                }


                /*
                |--------------------------------------------------------------------------
                | Reload First Page
                |--------------------------------------------------------------------------
                */

                table
                    .search(
                        ''
                    )
                    .ajax
                    .reload(
                        null,
                        true
                    );

            }
        );

});

</script>

@endpush