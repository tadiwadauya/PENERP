@extends('layouts.app')

@section('title', 'Employer Membership Report')

@section('page-heading', 'Employer Membership Report')

@section('page-subheading')
Membership totals, status distribution and nil contributors by employer
@endsection


@push('styles')

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"
>

<style>

    .summary-card {
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        height: 100%;
    }

    .summary-card .card-body {
        min-height: 95px;
    }

    .summary-value {
        font-size: 24px;
        font-weight: 600;
    }

    .report-table th {
        white-space: nowrap;
    }

    .report-table td {
        vertical-align: middle;
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
     SUMMARY
========================================================= --}}

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">

        <div class="card summary-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Total Members
                </p>

                <div class="summary-value">

                    {{
                        number_format(
                            $summary[
                                'total_members'
                            ]
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-lg-4 col-md-6">

        <div class="card summary-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Active
                </p>

                <div class="summary-value text-success">

                    {{
                        number_format(
                            $summary[
                                'active_members'
                            ]
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-lg-4 col-md-6">

        <div class="card summary-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Exited
                </p>

                <div class="summary-value">

                    {{
                        number_format(
                            $summary[
                                'exited_members'
                            ]
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-lg-4 col-md-6">

        <div class="card summary-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Inactive
                </p>

                <div class="summary-value text-secondary">

                    {{
                        number_format(
                            $summary[
                                'inactive_members'
                            ]
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-lg-4 col-md-6">

        <div class="card summary-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Suspended
                </p>

                <div class="summary-value text-danger">

                    {{
                        number_format(
                            $summary[
                                'suspended_members'
                            ]
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-lg-4 col-md-6">

        <div class="card summary-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Waiting Approval
                </p>

                <div class="summary-value text-warning">

                    {{
                        number_format(
                            $summary[
                                'waiting_approval_members'
                            ]
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-lg-4 col-md-6">

        <div class="card summary-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Deferred
                </p>

                <div class="summary-value text-info">

                    {{
                        number_format(
                            $summary[
                                'deferred_members'
                            ]
                        )
                    }}

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     EMPLOYER TABLE
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">
                Membership by Employer
            </h4>

            <p class="text-muted mb-0">
                Click any membership count to view the underlying members.
                Nil Contributors are active members who did not contribute in
                that employer's latest contribution period.
            </p>

        </div>


        <div class="table-responsive">

            <table
                id="employer-membership-table"
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
                            PENERP Employer No.
                        </th>

                        <th>
                            PenAd Employer No.
                        </th>

                        <th>
                            Fundworx Employer No.
                        </th>

                        <th>
                            Employer
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Active
                        </th>

                        <th>
                            Inactive
                        </th>

                        <th>
                            Exited
                        </th>

                        <th>
                            Suspended
                        </th>

                        <th>
                            Waiting Approval
                        </th>

                        <th>
                            Deferred
                        </th>

                        <th>
                            Nil Contributors
                        </th>

                        <th>
                            Latest Contribution Period
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

    $('#employer-membership-table')
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
                            'pensions-administration.updates.reports.employer-membership.data'
                        )
                    ),

                type:
                    'GET'

            },

            columns: [

                {
                    data:
                        'employer_number'
                },

                {
                    data:
                        'penad_employer_number'
                },

                {
                    data:
                        'fundworx_employer_number'
                },

                {
                    data:
                        'employer_name'
                },

                {
                    data:
                        'total_members'
                },

                {
                    data:
                        'active_members'
                },

                {
                    data:
                        'inactive_members'
                },

                {
                    data:
                        'exited_members'
                },

                {
                    data:
                        'suspended_members'
                },

                {
                    data:
                        'waiting_approval_members'
                },

                {
                    data:
                        'deferred_members'
                },

                /*
                |--------------------------------------------------------------------------
                | Nil Contributors
                |--------------------------------------------------------------------------
                */

                {
                    data:
                        'nil_contributors',

                    orderable:
                        false,

                    searchable:
                        false
                },

                /*
                |--------------------------------------------------------------------------
                | Latest Contribution Period
                |--------------------------------------------------------------------------
                */

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
                    3,
                    'asc'
                ]
            ],

            language: {

                processing:
                    '<span class="spinner-border spinner-border-sm me-2"></span> Loading employers...',

                search:
                    'Search:',

                searchPlaceholder:
                    'Employer number or name...',

                lengthMenu:
                    'Show _MENU_ employers',

                info:
                    'Showing _START_ to _END_ of _TOTAL_ employers',

                infoEmpty:
                    'No employers found',

                zeroRecords:
                    'No matching employers found'

            }

        });

});

</script>

@endpush