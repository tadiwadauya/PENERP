@extends('layouts.app')

@section('title', 'Review Monthly Contributions')

@section('page-heading', 'Review Monthly Contributions')


@section('content')


{{-- =========================================================
     PENSIONS ADMINISTRATION NAVIGATION
========================================================= --}}

@include(
    'pensions-administration.partials.navigation'
)


<div class="container-fluid">


    {{-- =========================================================
         PAGE HEADER
    ========================================================= --}}

    <div
        class="
            d-flex
            flex-wrap
            justify-content-between
            align-items-center
            gap-3
            mb-4
        "
    >

        <div>

            <h4 class="mb-1">
                Review Monthly Contributions
            </h4>


            <p class="text-muted mb-0">

                {{
                    $batch
                        ->employer
                        ?->name
                    ??
                    '-'
                }}

                —

                {{
                    $batch
                        ->contributionPeriod
                        ?->period_label
                    ??
                    '-'
                }}

                —

                <strong>

                    {{
                        $summary[
                            'currency'
                        ]
                        ??
                        'ZWG'
                    }}

                </strong>

            </p>

        </div>


        <div class="d-flex flex-wrap gap-2">


            {{-- =================================================
                 BATCH SUMMARY
            ================================================= --}}

            <a
                href="{{
                    route(
                        'pensions-administration.contributions.imports.show',
                        $batch
                    )
                }}"
                class="btn btn-light"
            >

                <i class="mdi mdi-arrow-left me-1"></i>

                Batch Summary

            </a>


            {{-- =================================================
                 EXPORT NEW MEMBERS
            ================================================= --}}

            @if(
                (int) (
                    $summary[
                        'new_members'
                    ]
                    ??
                    0
                )
                >
                0
            )

                @can(
                    'contributions.reports.view'
                )

                    <a
                        href="{{
                            route(
                                'pensions-administration.contributions.imports.new-members.export',
                                $batch
                            )
                        }}"
                        class="btn btn-info"
                    >

                        <i class="mdi mdi-account-plus-outline me-1"></i>

                        New Members

                        <span
                            class="
                                badge
                                bg-light
                                text-dark
                                ms-1
                            "
                        >

                            {{
                                number_format(
                                    $summary[
                                        'new_members'
                                    ]
                                )
                            }}

                        </span>

                    </a>

                @endcan

            @endif


            {{-- =================================================
                 EXPORT NIL CONTRIBUTORS
            ================================================= --}}

            @if(
                (int) (
                    $summary[
                        'nil_contributors'
                    ]
                    ??
                    0
                )
                >
                0
            )

                @can(
                    'contributions.reports.view'
                )

                    <a
                        href="{{
                            route(
                                'pensions-administration.contributions.imports.nil-contributors.export',
                                $batch
                            )
                        }}"
                        class="btn btn-warning"
                    >

                        <i class="mdi mdi-account-off-outline me-1"></i>

                        Nil Contributors

                        <span
                            class="
                                badge
                                bg-light
                                text-dark
                                ms-1
                            "
                        >

                            {{
                                number_format(
                                    $summary[
                                        'nil_contributors'
                                    ]
                                )
                            }}

                        </span>

                    </a>

                @endcan

            @endif


            {{-- =================================================
                 EXPORT REINSTATEMENTS
            ================================================= --}}

            @if(
                (int) (
                    $summary[
                        'reinstatements'
                    ]
                    ??
                    0
                )
                >
                0
            )

                @can(
                    'contributions.reports.view'
                )

                    <a
                        href="{{
                            route(
                                'pensions-administration.contributions.imports.reinstatements.export',
                                $batch
                            )
                        }}"
                        class="btn btn-success"
                    >

                        <i class="mdi mdi-account-reactivate-outline me-1"></i>

                        Reinstatements

                        <span
                            class="
                                badge
                                bg-light
                                text-dark
                                ms-1
                            "
                        >

                            {{
                                number_format(
                                    $summary[
                                        'reinstatements'
                                    ]
                                )
                            }}

                        </span>

                    </a>

                @endcan

            @endif


            {{-- =================================================
                 RECONCILIATION REPORTS
            ================================================= --}}

            @can(
                'contributions.reports.view'
            )

                <a
                    href="{{
                        route(
                            'pensions-administration.contributions.reconciliation.show',
                            $batch
                        )
                    }}"
                    class="btn btn-outline-primary"
                >

                    <i class="mdi mdi-scale-balance me-1"></i>

                    Reconciliation

                </a>


                <a
                    href="{{
                        route(
                            'pensions-administration.contributions.reconciliation.pdf',
                            $batch
                        )
                    }}"
                    class="btn btn-danger"
                    target="_blank"
                >

                    <i class="mdi mdi-file-pdf-box me-1"></i>

                    PDF

                </a>


                <a
                    href="{{
                        route(
                            'pensions-administration.contributions.reconciliation.excel',
                            $batch
                        )
                    }}"
                    class="btn btn-success"
                >

                    <i class="mdi mdi-microsoft-excel me-1"></i>

                    Excel

                </a>

            @endcan


            {{-- =================================================
                 REJECT
            ================================================= --}}

            @can(
                'contributions.monthly-imports.reject'
            )

                @if(
                    in_array(
                        $batch->status,
                        [
                            'awaiting_review',
                            'validated',
                            'approved',
                        ],
                        true
                    )
                )

                    <button
                        type="button"
                        class="btn btn-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#rejectContributionBatchModal"
                    >

                        <i class="mdi mdi-close-circle-outline me-1"></i>

                        Reject Batch

                    </button>

                @endif

            @endcan


            {{-- =================================================
                 APPROVE
            ================================================= --}}

            @can(
                'contributions.monthly-imports.approve'
            )

                @if(
                    in_array(
                        $batch->status,
                        [
                            'awaiting_review',
                            'validated',
                        ],
                        true
                    )
                )

                    @if(
                        (int) (
                            $summary[
                                'error_rows'
                            ]
                            ??
                            0
                        )
                        ===
                        0
                    )

                        <button
                            type="button"
                            class="btn btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#approveContributionModal"
                        >

                            <i class="mdi mdi-check-decagram-outline me-1"></i>

                            Approve Contributions

                        </button>

                    @else

                        <button
                            type="button"
                            class="btn btn-success"
                            disabled
                        >

                            <i class="mdi mdi-lock-outline me-1"></i>

                            Approval Blocked

                        </button>

                    @endif

                @endif

            @endcan


            {{-- =================================================
                 POST
            ================================================= --}}

            @can(
                'contributions.monthly-imports.post'
            )

                @if(
                    $batch->status
                    ===
                    'approved'
                )

                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#postContributionModal"
                    >

                        <i class="mdi mdi-database-check-outline me-1"></i>

                        Post Contributions

                    </button>

                @endif

            @endcan


            {{-- =================================================
                 POSTED STATUS
            ================================================= --}}

            @if(
                $batch->status
                ===
                'posted'
            )

                <button
                    type="button"
                    class="btn btn-success"
                    disabled
                >

                    <i class="mdi mdi-check-circle me-1"></i>

                    Contributions Posted

                </button>

            @endif

        </div>

    </div>


    {{-- =========================================================
         FLASH MESSAGES
    ========================================================= --}}

    @if(
        session(
            'success'
        )
    )

        <div
            class="
                alert
                alert-success
                alert-dismissible
                fade
                show
            "
        >

            <i class="mdi mdi-check-circle-outline me-1"></i>

            {{
                session(
                    'success'
                )
            }}


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif


    @if(
        session(
            'error'
        )
    )

        <div
            class="
                alert
                alert-danger
                alert-dismissible
                fade
                show
            "
        >

            <i class="mdi mdi-alert-circle-outline me-1"></i>

            {{
                session(
                    'error'
                )
            }}


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif


    {{-- =========================================================
         POSTING PROGRESS
    ========================================================= --}}

    @if(
        in_array(
            $batch->status,
            [
                'posting',
                'posting_failed',
            ],
            true
        )
    )

        <div
            class="card"
            id="posting-progress-card"
        >

            <div class="card-body">

                <div
                    class="
                        d-flex
                        flex-wrap
                        justify-content-between
                        align-items-center
                        gap-3
                        mb-3
                    "
                >

                    <div>

                        <h5 class="mb-1">

                            <i class="mdi mdi-database-sync-outline me-1"></i>

                            Posting Monthly Contributions

                        </h5>


                        <p
                            class="text-muted mb-0"
                            id="posting-status-text"
                        >

                            @if(
                                $batch->status
                                ===
                                'posting_failed'
                            )

                                Posting failed.

                            @else

                                Posting approved expected contributions...

                            @endif

                        </p>

                    </div>


                    <span
                        class="
                            badge
                            {{
                                $batch->status
                                ===
                                'posting_failed'
                                    ? 'bg-danger'
                                    : 'bg-primary'
                            }}
                            font-size-14
                        "
                        id="posting-status-badge"
                    >

                        {{
                            $batch->status
                            ===
                            'posting_failed'
                                ? 'Posting Failed'
                                : 'Posting'
                        }}

                    </span>

                </div>


                <div
                    class="progress"
                    style="height:28px;"
                >

                    <div
                        id="posting-progress-bar"
                        class="
                            progress-bar
                            progress-bar-striped
                            progress-bar-animated
                        "
                        role="progressbar"
                        style="
                            width:
                            {{
                                (float) (
                                    $batch
                                        ->progress_percentage
                                    ??
                                    0
                                )
                            }}%;
                        "
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >

                        <span id="posting-progress-percentage">

                            {{
                                number_format(
                                    (float) (
                                        $batch
                                            ->progress_percentage
                                        ??
                                        0
                                    ),
                                    0
                                )
                            }}%

                        </span>

                    </div>

                </div>


                <div
                    class="
                        d-flex
                        flex-wrap
                        justify-content-between
                        mt-2
                    "
                >

                    <small class="text-muted">

                        Posted

                        <strong id="posting-posted-rows">

                            {{
                                number_format(
                                    $batch
                                        ->posted_rows
                                    ??
                                    0
                                )
                            }}

                        </strong>

                        of

                        <strong id="posting-total-rows">

                            {{
                                number_format(
                                    $summary[
                                        'postable_rows'
                                    ]
                                    ??
                                    0
                                )
                            }}

                        </strong>

                        contribution rows

                    </small>


                    <small
                        class="text-muted"
                        id="posting-stage-text"
                    >

                        Please keep this page open.

                    </small>

                </div>


                <div
                    id="posting-failure-message"
                    class="
                        alert
                        alert-danger
                        mt-3
                        mb-0
                        {{
                            $batch->status
                            ===
                            'posting_failed'
                                ? ''
                                : 'd-none'
                        }}
                    "
                >

                    <i class="mdi mdi-alert-circle-outline me-1"></i>

                    <strong>
                        Posting failed:
                    </strong>

                    <span id="posting-failure-reason">

                        {{
                            $batch
                                ->failure_reason
                            ??
                            ''
                        }}

                    </span>

                </div>

            </div>

        </div>

    @endif


    {{-- =========================================================
         WORKFLOW STATUS
    ========================================================= --}}

    <div class="card">

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

                    <h5 class="mb-1">

                        Batch #{{ $batch->id }}

                    </h5>


                    <p class="text-muted mb-0">

                        {{
                            $batch
                                ->original_filename
                        }}

                    </p>

                </div>


                <div>

                    @switch(
                        $batch->status
                    )

                        @case('uploaded')

                            <span class="badge bg-secondary font-size-14">
                                Uploaded
                            </span>

                            @break


                        @case('processing')

                            <span class="badge bg-info font-size-14">
                                Validating
                            </span>

                            @break


                        @case('awaiting_review')

                            <span
                                class="
                                    badge
                                    bg-warning
                                    text-dark
                                    font-size-14
                                "
                            >
                                Awaiting Review / Approval
                            </span>

                            @break


                        @case('validated')

                            <span
                                class="
                                    badge
                                    bg-warning
                                    text-dark
                                    font-size-14
                                "
                            >
                                Validated
                            </span>

                            @break


                        @case('approved')

                            <span class="badge bg-primary font-size-14">
                                Approved - Ready For Posting
                            </span>

                            @break


                        @case('rejected')

                            <span class="badge bg-danger font-size-14">
                                Rejected
                            </span>

                            @break


                        @case('posting')

                            <span class="badge bg-info font-size-14">
                                Posting Contributions
                            </span>

                            @break


                        @case('posting_failed')

                            <span class="badge bg-danger font-size-14">
                                Posting Failed
                            </span>

                            @break


                        @case('posted')

                            <span class="badge bg-success font-size-14">
                                Posted
                            </span>

                            @break


                        @case('failed')

                            <span class="badge bg-danger font-size-14">
                                Validation Failed
                            </span>

                            @break


                        @case('cancelled')

                            <span class="badge bg-dark font-size-14">
                                Cancelled
                            </span>

                            @break


                        @default

                            <span class="badge bg-secondary font-size-14">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $batch->status
                                        )
                                    )
                                }}

                            </span>

                    @endswitch

                </div>

            </div>


            @if(
                $batch->status
                ===
                'rejected'
            )

                <div
                    class="
                        alert
                        alert-danger
                        mt-3
                        mb-0
                    "
                >

                    <strong>
                        Rejection Reason:
                    </strong>

                    {{
                        $batch
                            ->rejection_reason
                        ??
                        'No reason recorded.'
                    }}

                </div>

            @elseif(
                (
                    $summary[
                        'error_rows'
                    ]
                    ??
                    0
                )
                >
                0
            )

                <div
                    class="
                        alert
                        alert-danger
                        mt-3
                        mb-0
                    "
                >

                    <i class="mdi mdi-alert-circle-outline me-1"></i>

                    <strong>
                        Approval blocked.
                    </strong>

                    This batch contains

                    <strong>

                        {{
                            number_format(
                                $summary[
                                    'error_rows'
                                ]
                            )
                        }}

                    </strong>

                    validation error(s).

                </div>

            @elseif(
                (
                    $summary[
                        'warning_rows'
                    ]
                    ??
                    0
                )
                >
                0
            )

                <div
                    class="
                        alert
                        alert-warning
                        mt-3
                        mb-0
                    "
                >

                    <i class="mdi mdi-alert-outline me-1"></i>

                    <strong>
                        Review required.
                    </strong>

                    This batch contains

                    <strong>

                        {{
                            number_format(
                                $summary[
                                    'warning_rows'
                                ]
                            )
                        }}

                    </strong>

                    warning row(s).

                    Warnings do not prevent approval.

                </div>

            @else

                <div
                    class="
                        alert
                        alert-success
                        mt-3
                        mb-0
                    "
                >

                    <i class="mdi mdi-check-circle-outline me-1"></i>

                    No validation errors were found.

                </div>

            @endif

        </div>

    </div>


    {{-- =========================================================
         MEMBER SUMMARY
    ========================================================= --}}

    <div class="row">


        {{-- Existing Members --}}

        <div class="col-xl col-lg-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div
                        class="
                            d-flex
                            justify-content-between
                            align-items-center
                        "
                    >

                        <div>

                            <p class="text-muted mb-1">
                                Existing Members
                            </p>


                            <h3 class="mb-0">

                                {{
                                    number_format(
                                        $summary[
                                            'existing_members'
                                        ]
                                        ??
                                        0
                                    )
                                }}

                            </h3>

                        </div>


                        <i
                            class="
                                mdi
                                mdi-account-check-outline
                                font-size-32
                                text-primary
                            "
                        ></i>

                    </div>

                </div>

            </div>

        </div>


        {{-- New Members --}}

        <div class="col-xl col-lg-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div
                        class="
                            d-flex
                            justify-content-between
                            align-items-center
                        "
                    >

                        <div>

                            <p class="text-muted mb-1">
                                Proposed New Members
                            </p>


                            <h3 class="mb-0 text-info">

                                {{
                                    number_format(
                                        $summary[
                                            'new_members'
                                        ]
                                        ??
                                        0
                                    )
                                }}

                            </h3>

                        </div>


                        <i
                            class="
                                mdi
                                mdi-account-plus-outline
                                font-size-32
                                text-info
                            "
                        ></i>

                    </div>

                </div>

            </div>

        </div>


        {{-- Reinstatements --}}

        <div class="col-xl col-lg-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div
                        class="
                            d-flex
                            justify-content-between
                            align-items-center
                        "
                    >

                        <div>

                            <p class="text-muted mb-1">
                                Reinstatements
                            </p>


                            <h3 class="mb-0 text-success">

                                {{
                                    number_format(
                                        $summary[
                                            'reinstatements'
                                        ]
                                        ??
                                        0
                                    )
                                }}

                            </h3>


                            @if(
                                filled(
                                    $summary[
                                        'previous_period'
                                    ]
                                    ??
                                    null
                                )
                            )

                                <small class="text-muted">

                                    Nil contributor in

                                    {{
                                        $summary[
                                            'previous_period'
                                        ]
                                    }}

                                </small>

                            @else

                                <small class="text-muted">
                                    No previous period available
                                </small>

                            @endif

                        </div>


                        <i
                            class="
                                mdi
                                mdi-account-reactivate-outline
                                font-size-32
                                text-success
                            "
                        ></i>

                    </div>

                </div>

            </div>

        </div>


        {{-- Nil Contributors --}}

        <div class="col-xl col-lg-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div
                        class="
                            d-flex
                            justify-content-between
                            align-items-center
                        "
                    >

                        <div>

                            <p class="text-muted mb-1">
                                Nil Contributors
                            </p>


                            <h3 class="mb-0 text-warning">

                                {{
                                    number_format(
                                        $summary[
                                            'nil_contributors'
                                        ]
                                        ??
                                        0
                                    )
                                }}

                            </h3>

                        </div>


                        <i
                            class="
                                mdi
                                mdi-account-off-outline
                                font-size-32
                                text-warning
                            "
                        ></i>

                    </div>

                </div>

            </div>

        </div>


        {{-- Postable Rows --}}

        <div class="col-xl col-lg-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div
                        class="
                            d-flex
                            justify-content-between
                            align-items-center
                        "
                    >

                        <div>

                            <p class="text-muted mb-1">
                                Rows To Be Posted
                            </p>


                            <h3 class="mb-0 text-primary">

                                {{
                                    number_format(
                                        $summary[
                                            'postable_rows'
                                        ]
                                        ??
                                        0
                                    )
                                }}

                            </h3>

                        </div>


                        <i
                            class="
                                mdi
                                mdi-database-arrow-up-outline
                                font-size-32
                                text-primary
                            "
                        ></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         MULTI-CURRENCY CONTRIBUTION SUMMARY
    ========================================================= --}}

    <div class="card">

        <div class="card-body">

            <div class="mb-4">

                <h5 class="card-title mb-1">
                    Contribution Summary
                </h5>


                <p class="text-muted mb-0">

                    ZWG and USD are displayed independently.

                    Employee and employer expected contributions remain
                    separate.

                </p>

            </div>


            <div class="row">


                {{-- =================================================
                     ZWG SUMMARY
                ================================================= --}}

                <div class="col-xl-6">

                    <div
                        class="
                            border
                            rounded
                            p-3
                            h-100
                        "
                    >

                        <div
                            class="
                                d-flex
                                justify-content-between
                                align-items-center
                                mb-3
                            "
                        >

                            <div>

                                <h5 class="mb-1">
                                    ZWG Totals
                                </h5>

                                <small class="text-muted">
                                    Zimbabwe Gold
                                </small>

                            </div>


                            <span class="badge bg-primary">
                                Base Currency
                            </span>

                        </div>


                        <div class="table-responsive">

                            <table
                                class="
                                    table
                                    table-bordered
                                    mb-0
                                "
                            >

                                <tbody>

                                    <tr>

                                        <th>
                                            Basic Pay
                                        </th>

                                        <td class="text-end">

                                            ZWG

                                            {{
                                                number_format(
                                                    $summary[
                                                        'zwg_basic_pay_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employee Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                ZWG

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'zwg_employee_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                ZWG

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'zwg_employer_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employee AVC
                                        </th>

                                        <td class="text-end">

                                            ZWG

                                            {{
                                                number_format(
                                                    $summary[
                                                        'zwg_employee_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer AVC
                                        </th>

                                        <td class="text-end">

                                            ZWG

                                            {{
                                                number_format(
                                                    $summary[
                                                        'zwg_employer_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     USD SUMMARY
                ================================================= --}}

                <div class="col-xl-6">

                    <div
                        class="
                            border
                            rounded
                            p-3
                            h-100
                        "
                    >

                        <div
                            class="
                                d-flex
                                justify-content-between
                                align-items-center
                                mb-3
                            "
                        >

                            <div>

                                <h5 class="mb-1">
                                    USD Totals
                                </h5>

                                <small class="text-muted">
                                    United States Dollar
                                </small>

                            </div>


                            <span class="badge bg-success">
                                Foreign Currency
                            </span>

                        </div>


                        <div class="table-responsive">

                            <table
                                class="
                                    table
                                    table-bordered
                                    mb-0
                                "
                            >

                                <tbody>

                                    <tr>

                                        <th>
                                            Basic Pay
                                        </th>

                                        <td class="text-end">

                                            USD

                                            {{
                                                number_format(
                                                    $summary[
                                                        'usd_basic_pay_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employee Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                USD

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'usd_employee_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                USD

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'usd_employer_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employee AVC
                                        </th>

                                        <td class="text-end">

                                            USD

                                            {{
                                                number_format(
                                                    $summary[
                                                        'usd_employee_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer AVC
                                        </th>

                                        <td class="text-end">

                                            USD

                                            {{
                                                number_format(
                                                    $summary[
                                                        'usd_employer_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <div
                class="
                    alert
                    alert-light
                    border
                    mt-3
                    mb-0
                "
            >

                <i class="mdi mdi-information-outline me-1"></i>

                These figures represent expected member contributions due.

                They are not employer cash receipts.

                ZWG and USD are not added together.

            </div>

        </div>

    </div>


    {{-- =========================================================
         VALIDATION COUNTS
    ========================================================= --}}

    <div class="row">


        <div class="col-md-4">

            <div class="card">

                <div class="card-body text-center">

                    <span class="badge bg-success mb-2">
                        Valid
                    </span>


                    <h3 class="mb-0">

                        {{
                            number_format(
                                $summary[
                                    'valid_rows'
                                ]
                                ??
                                0
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card">

                <div class="card-body text-center">

                    <span
                        class="
                            badge
                            bg-warning
                            text-dark
                            mb-2
                        "
                    >
                        Warnings
                    </span>


                    <h3 class="mb-0">

                        {{
                            number_format(
                                $summary[
                                    'warning_rows'
                                ]
                                ??
                                0
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card">

                <div class="card-body text-center">

                    <span class="badge bg-danger mb-2">
                        Errors
                    </span>


                    <h3 class="mb-0">

                        {{
                            number_format(
                                $summary[
                                    'error_rows'
                                ]
                                ??
                                0
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         CONTRIBUTION ROWS
    ========================================================= --}}

    <div class="card">

        <div class="card-body">

            <div
                class="
                    d-flex
                    flex-wrap
                    justify-content-between
                    align-items-center
                    gap-2
                    mb-3
                "
            >

                <div>

                    <h5 class="card-title mb-1">
                        Contribution Schedule Rows
                    </h5>


                    <p class="text-muted mb-0">

                        Review member matching, contribution values,
                        new members and validation messages.

                    </p>

                </div>


                <span class="badge bg-primary font-size-14">

                    {{
                        $summary[
                            'currency'
                        ]
                        ??
                        'ZWG'
                    }}

                </span>

            </div>


            {{-- =================================================
                 FILTERS
            ================================================= --}}

            <form
                method="GET"
                action="{{
                    route(
                        'pensions-administration.contributions.imports.review',
                        $batch
                    )
                }}"
                class="row g-3 mb-4"
            >

                <div class="col-lg-4 col-md-6">

                    <label class="form-label">
                        Search
                    </label>


                    <input
                        type="text"
                        name="search"
                        value="{{
                            request(
                                'search'
                            )
                        }}"
                        class="form-control"
                        placeholder="Member number, staff number, National ID..."
                    >

                </div>


                <div class="col-lg-3 col-md-6">

                    <label class="form-label">
                        Validation Status
                    </label>


                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Results
                        </option>


                        <option
                            value="valid"
                            @selected(
                                request('status')
                                ===
                                'valid'
                            )
                        >
                            Valid
                        </option>


                        <option
                            value="warning"
                            @selected(
                                request('status')
                                ===
                                'warning'
                            )
                        >
                            Warning
                        </option>


                        <option
                            value="error"
                            @selected(
                                request('status')
                                ===
                                'error'
                            )
                        >
                            Error
                        </option>

                    </select>

                </div>


                <div class="col-lg-3 col-md-6">

                    <label class="form-label">
                        Member Type
                    </label>


                    <select
                        name="member_type"
                        class="form-select"
                    >

                        <option value="">
                            All Members
                        </option>


                        <option
                            value="existing"
                            @selected(
                                request('member_type')
                                ===
                                'existing'
                            )
                        >
                            Existing Members
                        </option>


                        <option
                            value="new"
                            @selected(
                                request('member_type')
                                ===
                                'new'
                            )
                        >
                            Proposed New Members
                        </option>

                    </select>

                </div>


                <div
                    class="
                        col-lg-2
                        col-md-6
                        d-flex
                        align-items-end
                    "
                >

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >

                        <i class="mdi mdi-filter-outline me-1"></i>

                        Filter

                    </button>

                </div>

            </form>


            <div class="table-responsive">

                <table
                    class="
                        table
                        table-bordered
                        table-hover
                        align-middle
                    "
                >

                    <thead>

                        <tr>

                            <th>Row</th>

                            <th>PenAd No.</th>

                            <th>PENERP No.</th>

                            <th>Staff No.</th>

                            <th>National ID</th>

                            <th>Member</th>

                            <th>Date Joined Fund</th>

                            <th>Match</th>

                            <th class="text-end">
                                Basic Pay
                            </th>

                            <th class="text-end">
                                Employee Contribution
                            </th>

                            <th class="text-end">
                                Employer Contribution
                            </th>

                            <th class="text-end">
                                Employee AVC
                            </th>

                            <th class="text-end">
                                Employer AVC
                            </th>

                            <th>Validation</th>

                            <th style="min-width:320px;">
                                Messages
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse(
                            $rows
                            as $row
                        )

                            @php

                                $data =
                                    $row
                                        ->normalized_data
                                    ?? [];


                                $currency =
                                    $summary[
                                        'currency'
                                    ]
                                    ??
                                    'ZWG';


                                if (
                                    $currency
                                    ===
                                    'USD'
                                ) {

                                    $basicPay =
                                        (float) (
                                            $data[
                                                'usd_basic_pay'
                                            ]
                                            ??
                                            0
                                        );


                                    $employeeContribution =
                                        (float) (
                                            $data[
                                                'usd_employee_contribution'
                                            ]
                                            ??
                                            0
                                        );


                                    $employerContribution =
                                        (float) (
                                            $data[
                                                'usd_employer_contribution'
                                            ]
                                            ??
                                            0
                                        );


                                    $employeeAvc =
                                        (float) (
                                            $data[
                                                'usd_employee_avc'
                                            ]
                                            ??
                                            0
                                        );


                                    $employerAvc =
                                        (float) (
                                            $data[
                                                'usd_employer_avc'
                                            ]
                                            ??
                                            0
                                        );

                                } else {

                                    $basicPay =
                                        (float) (
                                            $data[
                                                'zwg_basic_pay'
                                            ]
                                            ??
                                            0
                                        );


                                    $employeeContribution =
                                        (float) (
                                            $data[
                                                'zwg_employee_contribution'
                                            ]
                                            ??
                                            0
                                        );


                                    $employerContribution =
                                        (float) (
                                            $data[
                                                'zwg_employer_contribution'
                                            ]
                                            ??
                                            0
                                        );


                                    $employeeAvc =
                                        (float) (
                                            $data[
                                                'zwg_employee_avc'
                                            ]
                                            ??
                                            0
                                        );


                                    $employerAvc =
                                        (float) (
                                            $data[
                                                'zwg_employer_avc'
                                            ]
                                            ??
                                            0
                                        );
                                }


                                $dateJoinedFund =
                                    $data[
                                        'date_joined_fund'
                                    ]
                                    ??
                                    null;

                            @endphp


                            <tr>


                                <td>

                                    {{
                                        $row
                                            ->row_number
                                    }}

                                </td>


                                <td>

                                    {{
                                        $data[
                                            'penad_member_number'
                                        ]
                                        ??
                                        $data[
                                            'pension_reference_number'
                                        ]
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $data[
                                            'penerp_member_number'
                                        ]
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $data[
                                            'staff_number'
                                        ]
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $data[
                                            'national_id'
                                        ]
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    @if(
                                        $row
                                            ->matchedMember
                                    )

                                        <strong>

                                            {{
                                                $row
                                                    ->matchedMember
                                                    ->surname
                                            }}

                                            {{
                                                $row
                                                    ->matchedMember
                                                    ->first_names
                                            }}

                                        </strong>


                                        <br>


                                        <small class="text-muted">

                                            PENERP:

                                            {{
                                                $row
                                                    ->matchedMember
                                                    ->member_number
                                                ??
                                                '-'
                                            }}

                                        </small>

                                    @else

                                        <strong>

                                            {{
                                                $data[
                                                    'surname'
                                                ]
                                                ??
                                                ''
                                            }}

                                            {{
                                                $data[
                                                    'first_names'
                                                ]
                                                ??
                                                ''
                                            }}

                                        </strong>


                                        @if(
                                            $row
                                                ->is_new_member
                                        )

                                            <br>

                                            <span
                                                class="
                                                    badge
                                                    bg-info
                                                    mt-1
                                                "
                                            >
                                                Proposed New Member
                                            </span>

                                        @endif

                                    @endif

                                </td>


                                <td>

                                    @if(
                                        filled(
                                            $dateJoinedFund
                                        )
                                    )

                                        {{
                                            \Carbon\Carbon::parse(
                                                $dateJoinedFund
                                            )
                                                ->format(
                                                    'd M Y'
                                                )
                                        }}

                                    @elseif(
                                        $row
                                            ->is_new_member
                                    )

                                        <span class="badge bg-danger">
                                            Required
                                        </span>

                                    @else

                                        <span class="text-muted">
                                            -
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    @if(
                                        $row
                                            ->is_new_member
                                    )

                                        <span class="badge bg-info">
                                            New Member
                                        </span>

                                    @elseif(
                                        $row
                                            ->match_type
                                        ===
                                        'conflict'
                                    )

                                        <span class="badge bg-danger">
                                            Conflict
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">

                                            {{
                                                $row
                                                    ->match_label
                                            }}

                                        </span>

                                    @endif

                                </td>


                                <td class="text-end">

                                    {{
                                        number_format(
                                            $basicPay,
                                            2
                                        )
                                    }}

                                </td>


                                <td class="text-end">

                                    {{
                                        number_format(
                                            $employeeContribution,
                                            2
                                        )
                                    }}

                                </td>


                                <td class="text-end">

                                    {{
                                        number_format(
                                            $employerContribution,
                                            2
                                        )
                                    }}

                                </td>


                                <td class="text-end">

                                    {{
                                        number_format(
                                            $employeeAvc,
                                            2
                                        )
                                    }}

                                </td>


                                <td class="text-end">

                                    {{
                                        number_format(
                                            $employerAvc,
                                            2
                                        )
                                    }}

                                </td>


                                <td>

                                    <span
                                        class="
                                            badge
                                            {{
                                                $row
                                                    ->validation_badge
                                            }}
                                        "
                                    >

                                        {{
                                            $row
                                                ->validation_label
                                        }}

                                    </span>

                                </td>


                                <td>

                                    @foreach(
                                        $row
                                            ->error_messages
                                        ??
                                        []
                                        as $message
                                    )

                                        <div
                                            class="
                                                text-danger
                                                small
                                                mb-1
                                            "
                                        >

                                            <i class="mdi mdi-alert-circle-outline me-1"></i>

                                            {{
                                                $message
                                            }}

                                        </div>

                                    @endforeach


                                    @foreach(
                                        $row
                                            ->warning_messages
                                        ??
                                        []
                                        as $message
                                    )

                                        <div
                                            class="
                                                text-warning
                                                small
                                                mb-1
                                            "
                                        >

                                            <i class="mdi mdi-alert-outline me-1"></i>

                                            {{
                                                $message
                                            }}

                                        </div>

                                    @endforeach


                                    @if(
                                        empty(
                                            $row
                                                ->error_messages
                                            ??
                                            []
                                        )
                                        &&
                                        empty(
                                            $row
                                                ->warning_messages
                                            ??
                                            []
                                        )
                                    )

                                        <span
                                            class="
                                                text-success
                                                small
                                            "
                                        >

                                            <i class="mdi mdi-check-circle-outline me-1"></i>

                                            No validation issues

                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="15"
                                    class="
                                        text-center
                                        text-muted
                                        py-4
                                    "
                                >

                                    No contribution rows found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <div class="mt-3">

                {{
                    $rows
                        ->links()
                }}

            </div>

        </div>

    </div>


    {{-- =========================================================
         NIL CONTRIBUTORS
    ========================================================= --}}

    <div class="card">

        <div class="card-body">

            <div
                class="
                    d-flex
                    flex-wrap
                    justify-content-between
                    align-items-center
                    gap-2
                    mb-3
                "
            >

                <div>

                    <h5 class="card-title mb-1">
                        Nil Contributors
                    </h5>


                    <p class="text-muted mb-0">

                        Active members under this employer who did
                        not appear on this month's expected
                        contribution schedule.

                    </p>

                </div>


                <span
                    class="
                        badge
                        bg-warning
                        text-dark
                        font-size-14
                    "
                >

                    {{
                        number_format(
                            $summary[
                                'nil_contributors'
                            ]
                            ??
                            0
                        )
                    }}

                </span>

            </div>


            <div class="alert alert-light border">

                <i class="mdi mdi-information-outline me-1"></i>

                A nil contributor is not treated as an exited member.

                The member remains active unless a separate membership
                movement changes the member's status.

            </div>


            <div class="table-responsive">

                <table
                    class="
                        table
                        table-bordered
                        table-hover
                        align-middle
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
                                Member
                            </th>

                            <th>
                                Staff Number
                            </th>

                            <th>
                                Employer
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Reason
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse(
                            $nilContributors
                            as $status
                        )

                            <tr>

                                <td>

                                    {{
                                        $status
                                            ->member
                                            ?->member_number
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $status
                                            ->member
                                            ?->penad_member_number
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    <strong>

                                        {{
                                            $status
                                                ->member
                                                ?->surname
                                            ??
                                            ''
                                        }}

                                        {{
                                            $status
                                                ->member
                                                ?->first_names
                                            ??
                                            ''
                                        }}

                                    </strong>

                                </td>


                                <td>

                                    {{
                                        $status
                                            ->member
                                            ?->currentEmployment
                                            ?->staff_number
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $status
                                            ->member
                                            ?->currentEmployment
                                            ?->employer
                                            ?->name
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    <span
                                        class="
                                            badge
                                            bg-warning
                                            text-dark
                                        "
                                    >
                                        Nil Contributor
                                    </span>

                                </td>


                                <td>

                                    {{
                                        $status
                                            ->reason
                                        ??
                                        'Member did not appear on the contribution schedule.'
                                    }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="7"
                                    class="
                                        text-center
                                        text-muted
                                        py-4
                                    "
                                >

                                    No nil contributors identified.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <div class="mt-3">

                {{
                    $nilContributors
                        ->links()
                }}

            </div>

        </div>

    </div>


    {{-- =========================================================
         APPROVAL MODAL
    ========================================================= --}}

    @can(
        'contributions.monthly-imports.approve'
    )

        <div
            class="modal fade"
            id="approveContributionModal"
            tabindex="-1"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-lg">

                <div class="modal-content">

                    <form
                        method="POST"
                        action="{{
                            route(
                                'pensions-administration.contributions.imports.approve',
                                $batch
                            )
                        }}"
                    >

                        @csrf


                        <div class="modal-header">

                            <h5 class="modal-title">
                                Approve Monthly Contributions
                            </h5>


                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>

                        </div>


                        <div class="modal-body">

                            <div class="alert alert-info">

                                <i class="mdi mdi-information-outline me-1"></i>

                                Review the member and contribution summary
                                before approving this batch.

                            </div>


                            <h6 class="mb-3">
                                Member Summary
                            </h6>


                            <table class="table table-bordered">

                                <tbody>

                                    <tr>

                                        <th style="width:45%;">
                                            Employer
                                        </th>

                                        <td>

                                            {{
                                                $batch
                                                    ->employer
                                                    ?->name
                                                ??
                                                '-'
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Contribution Period
                                        </th>

                                        <td>

                                            {{
                                                $batch
                                                    ->contributionPeriod
                                                    ?->period_label
                                                ??
                                                '-'
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Rows To Be Posted
                                        </th>

                                        <td>

                                            <strong>

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'postable_rows'
                                                        ]
                                                        ??
                                                        0
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Existing Members
                                        </th>

                                        <td>

                                            {{
                                                number_format(
                                                    $summary[
                                                        'existing_members'
                                                    ]
                                                    ??
                                                    0
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            New Members To Be Created
                                        </th>

                                        <td>

                                            <strong class="text-info">

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'new_members'
                                                        ]
                                                        ??
                                                        0
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Reinstated Contributors
                                        </th>

                                        <td>

                                            <strong class="text-success">

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'reinstatements'
                                                        ]
                                                        ??
                                                        0
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Nil Contributors
                                        </th>

                                        <td>

                                            {{
                                                number_format(
                                                    $summary[
                                                        'nil_contributors'
                                                    ]
                                                    ??
                                                    0
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Warning Rows
                                        </th>

                                        <td>

                                            {{
                                                number_format(
                                                    $summary[
                                                        'warning_rows'
                                                    ]
                                                    ??
                                                    0
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Error Rows
                                        </th>

                                        <td>

                                            {{
                                                number_format(
                                                    $summary[
                                                        'error_rows'
                                                    ]
                                                    ??
                                                    0
                                                )
                                            }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>


                            {{-- =================================================
                                 ZWG TOTALS
                            ================================================= --}}

                            <h6 class="mt-4 mb-3">
                                ZWG Contribution Totals
                            </h6>


                            <table class="table table-bordered">

                                <tbody>

                                    <tr>

                                        <th style="width:45%;">
                                            Employee Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                ZWG

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'zwg_employee_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                ZWG

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'zwg_employer_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employee AVC
                                        </th>

                                        <td class="text-end">

                                            ZWG

                                            {{
                                                number_format(
                                                    $summary[
                                                        'zwg_employee_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer AVC
                                        </th>

                                        <td class="text-end">

                                            ZWG

                                            {{
                                                number_format(
                                                    $summary[
                                                        'zwg_employer_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>


                            {{-- =================================================
                                 USD TOTALS
                            ================================================= --}}

                            <h6 class="mt-4 mb-3">
                                USD Contribution Totals
                            </h6>


                            <table class="table table-bordered">

                                <tbody>

                                    <tr>

                                        <th style="width:45%;">
                                            Employee Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                USD

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'usd_employee_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                USD

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'usd_employer_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employee AVC
                                        </th>

                                        <td class="text-end">

                                            USD

                                            {{
                                                number_format(
                                                    $summary[
                                                        'usd_employee_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer AVC
                                        </th>

                                        <td class="text-end">

                                            USD

                                            {{
                                                number_format(
                                                    $summary[
                                                        'usd_employer_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>


                            <div class="mb-0">

                                <label class="form-label">
                                    Approval Notes
                                </label>


                                <textarea
                                    name="approval_notes"
                                    class="form-control"
                                    rows="3"
                                    maxlength="2000"
                                    placeholder="Optional approval comments..."
                                ></textarea>

                            </div>

                        </div>


                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                            >
                                Cancel
                            </button>


                            <button
                                type="submit"
                                class="btn btn-success"
                            >

                                <i class="mdi mdi-check-decagram-outline me-1"></i>

                                Confirm Approval

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    @endcan


    {{-- =========================================================
         POSTING MODAL
    ========================================================= --}}

    @can(
        'contributions.monthly-imports.post'
    )

        <div
            class="modal fade"
            id="postContributionModal"
            tabindex="-1"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-lg">

                <div class="modal-content">

                    <form
                        method="POST"
                        action="{{
                            route(
                                'pensions-administration.contributions.imports.post',
                                $batch
                            )
                        }}"
                    >

                        @csrf


                        <div class="modal-header">

                            <h5 class="modal-title">
                                Post Monthly Contributions
                            </h5>


                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>

                        </div>


                        <div class="modal-body">

                            <div class="alert alert-danger">

                                <i class="mdi mdi-alert-outline me-1"></i>

                                <strong>
                                    Permanent Posting
                                </strong>


                                <div class="mt-1">

                                    This action will permanently create
                                    expected contribution records for
                                    this batch.

                                </div>

                            </div>


                            <h6 class="mb-3">
                                Posting Summary
                            </h6>


                            <table class="table table-bordered">

                                <tbody>

                                    <tr>

                                        <th style="width:45%;">
                                            Employer
                                        </th>

                                        <td>

                                            {{
                                                $batch
                                                    ->employer
                                                    ?->name
                                                ??
                                                '-'
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Contribution Period
                                        </th>

                                        <td>

                                            {{
                                                $batch
                                                    ->contributionPeriod
                                                    ?->period_label
                                                ??
                                                '-'
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Rows To Post
                                        </th>

                                        <td>

                                            <strong>

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'postable_rows'
                                                        ]
                                                        ??
                                                        0
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Existing Members
                                        </th>

                                        <td>

                                            {{
                                                number_format(
                                                    $summary[
                                                        'existing_members'
                                                    ]
                                                    ??
                                                    0
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            New Members To Create
                                        </th>

                                        <td>

                                            <strong class="text-info">

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'new_members'
                                                        ]
                                                        ??
                                                        0
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Reinstated Contributors
                                        </th>

                                        <td>

                                            <strong class="text-success">

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'reinstatements'
                                                        ]
                                                        ??
                                                        0
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Nil Contributors
                                        </th>

                                        <td>

                                            {{
                                                number_format(
                                                    $summary[
                                                        'nil_contributors'
                                                    ]
                                                    ??
                                                    0
                                                )
                                            }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>


                            <h6 class="mt-4 mb-3">
                                ZWG Contribution Totals
                            </h6>


                            <table class="table table-bordered">

                                <tbody>

                                    <tr>

                                        <th style="width:45%;">
                                            Employee Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                ZWG

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'zwg_employee_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                ZWG

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'zwg_employer_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employee AVC
                                        </th>

                                        <td class="text-end">

                                            ZWG

                                            {{
                                                number_format(
                                                    $summary[
                                                        'zwg_employee_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer AVC
                                        </th>

                                        <td class="text-end">

                                            ZWG

                                            {{
                                                number_format(
                                                    $summary[
                                                        'zwg_employer_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>


                            <h6 class="mt-4 mb-3">
                                USD Contribution Totals
                            </h6>


                            <table class="table table-bordered">

                                <tbody>

                                    <tr>

                                        <th style="width:45%;">
                                            Employee Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                USD

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'usd_employee_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer Contribution
                                        </th>

                                        <td class="text-end">

                                            <strong>

                                                USD

                                                {{
                                                    number_format(
                                                        $summary[
                                                            'usd_employer_contribution_total'
                                                        ]
                                                        ??
                                                        0,
                                                        2
                                                    )
                                                }}

                                            </strong>

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employee AVC
                                        </th>

                                        <td class="text-end">

                                            USD

                                            {{
                                                number_format(
                                                    $summary[
                                                        'usd_employee_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Employer AVC
                                        </th>

                                        <td class="text-end">

                                            USD

                                            {{
                                                number_format(
                                                    $summary[
                                                        'usd_employer_avc_total'
                                                    ]
                                                    ??
                                                    0,
                                                    2
                                                )
                                            }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>


                            @if(
                                (
                                    $summary[
                                        'new_members'
                                    ]
                                    ??
                                    0
                                )
                                >
                                0
                            )

                                <div
                                    class="
                                        alert
                                        alert-info
                                        mb-0
                                    "
                                >

                                    <i class="mdi mdi-account-plus-outline me-1"></i>

                                    <strong>

                                        {{
                                            number_format(
                                                $summary[
                                                    'new_members'
                                                ]
                                            )
                                        }}

                                    </strong>

                                    new member(s) will be created during
                                    posting and allocated new
                                    PENERP/PenAd member numbers.

                                </div>

                            @endif

                        </div>


                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                            >
                                Cancel
                            </button>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="mdi mdi-database-check-outline me-1"></i>

                                Confirm Posting

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    @endcan


    {{-- =========================================================
         REJECTION MODAL
    ========================================================= --}}

    @can(
        'contributions.monthly-imports.reject'
    )

        <div
            class="modal fade"
            id="rejectContributionBatchModal"
            tabindex="-1"
            aria-labelledby="rejectContributionBatchModalLabel"
            aria-hidden="true"
        >

            <div
                class="
                    modal-dialog
                    modal-dialog-centered
                "
            >

                <div class="modal-content">

                    <form
                        method="POST"
                        action="{{
                            route(
                                'pensions-administration.contributions.imports.reject',
                                $batch
                            )
                        }}"
                    >

                        @csrf


                        <div
                            class="
                                modal-header
                                bg-danger
                                text-white
                            "
                        >

                            <h5
                                class="
                                    modal-title
                                    text-white
                                "
                                id="rejectContributionBatchModalLabel"
                            >

                                Reject Monthly Contribution Batch

                            </h5>


                            <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            ></button>

                        </div>


                        <div class="modal-body">

                            <div class="alert alert-warning">

                                <strong>
                                    Batch #{{ $batch->id }}
                                </strong>

                                will be rejected.

                                The user who uploaded the batch will
                                receive a notification containing the
                                rejection reason.

                            </div>


                            <div class="mb-3">

                                <label
                                    for="rejection_reason"
                                    class="form-label"
                                >

                                    Rejection Reason

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <textarea
                                    name="rejection_reason"
                                    id="rejection_reason"
                                    class="
                                        form-control
                                        @error('rejection_reason')
                                            is-invalid
                                        @enderror
                                    "
                                    rows="5"
                                    minlength="5"
                                    maxlength="3000"
                                    required
                                    placeholder="Explain clearly why this contribution batch is being rejected..."
                                >{{ old('rejection_reason') }}</textarea>


                                @error(
                                    'rejection_reason'
                                )

                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>

                                @enderror


                                <div class="form-text">

                                    The uploader will see this reason
                                    in their notification.

                                </div>

                            </div>

                        </div>


                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                            >
                                Cancel
                            </button>


                            <button
                                type="submit"
                                class="btn btn-danger"
                            >

                                <i class="mdi mdi-close-circle-outline me-1"></i>

                                Confirm Rejection

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    @endcan


</div>

@endsection


{{-- =============================================================
     POSTING STATUS POLLING
============================================================= --}}

@if(
    $batch->status
    ===
    'posting'
)

    @push('scripts')

        <script>

            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    const statusUrl =
                        @json(
                            route(
                                'pensions-administration.contributions.imports.status',
                                $batch
                            )
                        );


                    const progressBar =
                        document.getElementById(
                            'posting-progress-bar'
                        );


                    const percentageText =
                        document.getElementById(
                            'posting-progress-percentage'
                        );


                    const postedRowsText =
                        document.getElementById(
                            'posting-posted-rows'
                        );


                    const totalRowsText =
                        document.getElementById(
                            'posting-total-rows'
                        );


                    const statusText =
                        document.getElementById(
                            'posting-status-text'
                        );


                    const stageText =
                        document.getElementById(
                            'posting-stage-text'
                        );


                    const statusBadge =
                        document.getElementById(
                            'posting-status-badge'
                        );


                    const failureBox =
                        document.getElementById(
                            'posting-failure-message'
                        );


                    const failureReason =
                        document.getElementById(
                            'posting-failure-reason'
                        );


                    let pollingStopped =
                        false;


                    function getStageText(
                        percentage
                    ) {

                        if (
                            percentage
                            <
                            10
                        ) {
                            return 'Preparing contribution posting...';
                        }


                        if (
                            percentage
                            <
                            90
                        ) {
                            return 'Creating members and posting contribution rows...';
                        }


                        if (
                            percentage
                            <
                            98
                        ) {
                            return 'Finalising contribution period...';
                        }


                        return 'Completing posting...';
                    }


                    function checkPostingStatus() {

                        if (
                            pollingStopped
                        ) {
                            return;
                        }


                        fetch(
                            statusUrl,
                            {
                                headers: {

                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest'

                                }
                            }
                        )

                            .then(
                                function (
                                    response
                                ) {

                                    if (
                                        !response.ok
                                    ) {
                                        throw new Error(
                                            'Unable to retrieve posting progress.'
                                        );
                                    }


                                    return response.json();
                                }
                            )

                            .then(
                                function (
                                    data
                                ) {

                                    const percentage =
                                        Math.max(
                                            0,
                                            Math.min(
                                                100,
                                                parseFloat(
                                                    data.progress_percentage
                                                    ||
                                                    0
                                                )
                                            )
                                        );


                                    if (
                                        progressBar
                                    ) {

                                        progressBar.style.width =
                                            percentage
                                            +
                                            '%';


                                        progressBar.setAttribute(
                                            'aria-valuenow',
                                            percentage
                                        );
                                    }


                                    if (
                                        percentageText
                                    ) {

                                        percentageText.textContent =
                                            Math.round(
                                                percentage
                                            )
                                            +
                                            '%';
                                    }


                                    if (
                                        postedRowsText
                                    ) {

                                        postedRowsText.textContent =
                                            Number(
                                                data.posted_rows
                                                ||
                                                0
                                            )
                                                .toLocaleString();
                                    }


                                    if (
                                        totalRowsText
                                    ) {

                                        const totalRows =
                                            data.total_rows
                                            ||
                                            {{
                                                (int) (
                                                    $summary[
                                                        'postable_rows'
                                                    ]
                                                    ??
                                                    0
                                                )
                                            }};


                                        totalRowsText.textContent =
                                            Number(
                                                totalRows
                                            )
                                                .toLocaleString();
                                    }


                                    if (
                                        statusText
                                    ) {

                                        statusText.textContent =
                                            data.status_label
                                            ||
                                            'Posting Contributions';
                                    }


                                    if (
                                        stageText
                                    ) {

                                        stageText.textContent =
                                            getStageText(
                                                percentage
                                            );
                                    }


                                    if (
                                        data.status
                                        ===
                                        'posted'
                                    ) {

                                        pollingStopped =
                                            true;


                                        if (
                                            progressBar
                                        ) {

                                            progressBar.style.width =
                                                '100%';


                                            progressBar.classList.remove(
                                                'progress-bar-animated'
                                            );


                                            progressBar.classList.remove(
                                                'bg-danger'
                                            );


                                            progressBar.classList.add(
                                                'bg-success'
                                            );
                                        }


                                        if (
                                            percentageText
                                        ) {

                                            percentageText.textContent =
                                                '100%';
                                        }


                                        if (
                                            statusBadge
                                        ) {

                                            statusBadge.classList.remove(
                                                'bg-primary',
                                                'bg-info',
                                                'bg-danger'
                                            );


                                            statusBadge.classList.add(
                                                'bg-success'
                                            );


                                            statusBadge.textContent =
                                                'Posted';
                                        }


                                        if (
                                            statusText
                                        ) {

                                            statusText.textContent =
                                                'Contribution posting completed successfully.';
                                        }


                                        if (
                                            stageText
                                        ) {

                                            stageText.textContent =
                                                'Refreshing completed batch...';
                                        }


                                        setTimeout(
                                            function () {

                                                window.location.reload();

                                            },
                                            1000
                                        );


                                        return;
                                    }


                                    if (
                                        data.status
                                        ===
                                        'posting_failed'
                                    ) {

                                        pollingStopped =
                                            true;


                                        if (
                                            progressBar
                                        ) {

                                            progressBar.classList.remove(
                                                'progress-bar-animated'
                                            );


                                            progressBar.classList.add(
                                                'bg-danger'
                                            );
                                        }


                                        if (
                                            statusBadge
                                        ) {

                                            statusBadge.classList.remove(
                                                'bg-primary',
                                                'bg-info'
                                            );


                                            statusBadge.classList.add(
                                                'bg-danger'
                                            );


                                            statusBadge.textContent =
                                                'Posting Failed';
                                        }


                                        if (
                                            statusText
                                        ) {

                                            statusText.textContent =
                                                'Contribution posting failed.';
                                        }


                                        if (
                                            stageText
                                        ) {

                                            stageText.textContent =
                                                'Review the posting error below.';
                                        }


                                        if (
                                            failureBox
                                        ) {

                                            failureBox.classList.remove(
                                                'd-none'
                                            );
                                        }


                                        if (
                                            failureReason
                                        ) {

                                            failureReason.textContent =
                                                data.failure_reason
                                                ||
                                                'An unexpected posting error occurred.';
                                        }


                                        return;
                                    }


                                    setTimeout(
                                        checkPostingStatus,
                                        1200
                                    );
                                }
                            )

                            .catch(
                                function (
                                    error
                                ) {

                                    console.error(
                                        'Contribution posting status error:',
                                        error
                                    );


                                    if (
                                        !pollingStopped
                                    ) {

                                        setTimeout(
                                            checkPostingStatus,
                                            2500
                                        );
                                    }
                                }
                            );
                    }


                    checkPostingStatus();

                }
            );

        </script>

    @endpush

@endif