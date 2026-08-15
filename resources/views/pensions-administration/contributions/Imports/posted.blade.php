@extends('layouts.app')

@section(
    'title',
    'Posted Monthly Contributions'
)

@section(
    'page-heading',
    'Posted Monthly Contributions'
)

@section('page-subheading')
    Permanent expected member contribution transactions
@endsection


@section('content')


@include(
    'pensions-administration.partials.navigation'
)



{{-- =========================================================
     MESSAGES
========================================================= --}}

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



{{-- =========================================================
     BATCH HEADER
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div
            class="
                d-flex
                flex-wrap
                justify-content-between
                align-items-start
            "
        >

            <div>

                <h4 class="header-title mb-2">

                    {{
                        $batch
                            ->employer
                            ?->name
                        ??
                        '-'
                    }}

                </h4>


                <p class="text-muted mb-1">

                    Contribution Period:

                    <strong>

                        {{
                            $batch
                                ->contributionPeriod
                                ?->period_label
                            ??
                            '-'
                        }}

                    </strong>

                </p>


                <p class="text-muted mb-1">

                    Import Batch:

                    <strong>
                        #{{ $batch->id }}
                    </strong>

                </p>


                <p class="text-muted mb-0">

                    Source File:

                    {{
                        $batch
                            ->original_filename
                    }}

                </p>

            </div>


            <div class="mt-3 mt-md-0">

                <a
                    href="{{
                        route(
                            'pensions-administration.contributions.imports.show',
                            $batch
                        )
                    }}"
                    class="btn btn-light"
                >

                    <i
                        class="
                            mdi
                            mdi-arrow-left
                            me-1
                        "
                    ></i>

                    Batch Summary

                </a>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     POSTING INFORMATION
========================================================= --}}

<div class="row">


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Posted Members
                </p>

                <h3>

                    {{
                        number_format(
                            $summary[
                                'posted_rows'
                            ]
                        )
                    }}

                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Negative Adjustments
                </p>

                <h3 class="text-warning">

                    {{
                        number_format(
                            $summary[
                                'adjustments'
                            ]
                        )
                    }}

                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Approved By
                </p>

                <h5>

                    {{
                        $batch
                            ->approvedBy
                            ?->full_name
                        ??
                        '-'
                    }}

                </h5>

                <small class="text-muted">

                    {{
                        $batch
                            ->approved_at
                            ?->format(
                                'd M Y H:i'
                            )
                        ??
                        '-'
                    }}

                </small>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Posted By
                </p>

                <h5>

                    {{
                        $batch
                            ->postedBy
                            ?->full_name
                        ??
                        '-'
                    }}

                </h5>

                <small class="text-muted">

                    {{
                        $batch
                            ->posted_at
                            ?->format(
                                'd M Y H:i'
                            )
                        ??
                        '-'
                    }}

                </small>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     USD SUMMARY
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title mb-4">
            USD Contribution Totals
        </h4>


        <div class="row">


            <div class="col-xl-3 col-md-6">

                <p class="text-muted mb-1">
                    Employee
                </p>

                <h4>

                    {{
                        number_format(
                            $summary[
                                'usd_employee'
                            ],
                            2
                        )
                    }}

                </h4>

            </div>


            <div class="col-xl-3 col-md-6">

                <p class="text-muted mb-1">
                    Employer
                </p>

                <h4>

                    {{
                        number_format(
                            $summary[
                                'usd_employer'
                            ],
                            2
                        )
                    }}

                </h4>

            </div>


            <div class="col-xl-3 col-md-6">

                <p class="text-muted mb-1">
                    Employee AVC
                </p>

                <h4>

                    {{
                        number_format(
                            $summary[
                                'usd_employee_avc'
                            ],
                            2
                        )
                    }}

                </h4>

            </div>


            <div class="col-xl-3 col-md-6">

                <p class="text-muted mb-1">
                    Employer AVC
                </p>

                <h4>

                    {{
                        number_format(
                            $summary[
                                'usd_employer_avc'
                            ],
                            2
                        )
                    }}

                </h4>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     ZWG SUMMARY
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title mb-4">
            ZWG Contribution Totals
        </h4>


        <div class="row">


            <div class="col-xl-3 col-md-6">

                <p class="text-muted mb-1">
                    Employee
                </p>

                <h4>

                    {{
                        number_format(
                            $summary[
                                'zwg_employee'
                            ],
                            2
                        )
                    }}

                </h4>

            </div>


            <div class="col-xl-3 col-md-6">

                <p class="text-muted mb-1">
                    Employer
                </p>

                <h4>

                    {{
                        number_format(
                            $summary[
                                'zwg_employer'
                            ],
                            2
                        )
                    }}

                </h4>

            </div>


            <div class="col-xl-3 col-md-6">

                <p class="text-muted mb-1">
                    Employee AVC
                </p>

                <h4>

                    {{
                        number_format(
                            $summary[
                                'zwg_employee_avc'
                            ],
                            2
                        )
                    }}

                </h4>

            </div>


            <div class="col-xl-3 col-md-6">

                <p class="text-muted mb-1">
                    Employer AVC
                </p>

                <h4>

                    {{
                        number_format(
                            $summary[
                                'zwg_employer_avc'
                            ],
                            2
                        )
                    }}

                </h4>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     CONTRIBUTION TRANSACTIONS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title mb-4">
            Posted Member Contributions
        </h4>


        <div class="table-responsive">

            <table
                class="
                    table
                    table-bordered
                    table-hover
                    table-nowrap
                "
            >

                <thead>

                    <tr>

                        <th>
                            Row
                        </th>

                        <th>
                            PENERP No.
                        </th>

                        <th>
                            PenAd No.
                        </th>

                        <th>
                            Staff No.
                        </th>

                        <th>
                            Member
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            USD Salary
                        </th>

                        <th>
                            USD EE
                        </th>

                        <th>
                            USD ER
                        </th>

                        <th>
                            USD AVC
                        </th>

                        <th>
                            ZWG Salary
                        </th>

                        <th>
                            ZWG EE
                        </th>

                        <th>
                            ZWG ER
                        </th>

                        <th>
                            ZWG AVC
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $contributions
                        as $contribution
                    )

                        <tr>

                            <td>

                                {{
                                    $contribution
                                        ->source_row_number
                                    ??
                                    '-'
                                }}

                            </td>


                            <td>

                                <a
                                    href="{{
                                        route(
                                            'pensions-administration.updates.members.show',
                                            $contribution
                                                ->member_id
                                        )
                                    }}"
                                >

                                    {{
                                        $contribution
                                            ->penerp_member_number
                                        ??
                                        '-'
                                    }}

                                </a>

                            </td>


                            <td>

                                {{
                                    $contribution
                                        ->penad_member_number
                                    ??
                                    '-'
                                }}

                            </td>


                            <td>

                                {{
                                    $contribution
                                        ->staff_number
                                    ??
                                    '-'
                                }}

                            </td>


                            <td>

                                <strong>

                                    {{
                                        $contribution
                                            ->member
                                            ?->surname
                                    }},

                                    {{
                                        $contribution
                                            ->member
                                            ?->first_names
                                    }}

                                </strong>

                            </td>


                            <td>

                                @if(
                                    $contribution
                                        ->transaction_type
                                    ===
                                    'adjustment'
                                )

                                    <span
                                        class="
                                            badge
                                            bg-warning
                                            text-dark
                                        "
                                    >

                                        Adjustment

                                    </span>

                                @else

                                    <span
                                        class="
                                            badge
                                            bg-success
                                        "
                                    >

                                        Expected

                                    </span>

                                @endif

                            </td>


                            <td class="text-end">

                                {{
                                    number_format(
                                        $contribution
                                            ->usd_basic_pay,
                                        2
                                    )
                                }}

                            </td>


                            <td class="text-end">

                                {{
                                    number_format(
                                        $contribution
                                            ->usd_employee_contribution,
                                        2
                                    )
                                }}

                            </td>


                            <td class="text-end">

                                {{
                                    number_format(
                                        $contribution
                                            ->usd_employer_contribution,
                                        2
                                    )
                                }}

                            </td>


                            <td class="text-end">

                                {{
                                    number_format(
                                        (
                                            (float)
                                            $contribution
                                                ->usd_employee_avc
                                        )
                                        +
                                        (
                                            (float)
                                            $contribution
                                                ->usd_employer_avc
                                        ),
                                        2
                                    )
                                }}

                            </td>


                            <td class="text-end">

                                {{
                                    number_format(
                                        $contribution
                                            ->zwg_basic_pay,
                                        2
                                    )
                                }}

                            </td>


                            <td class="text-end">

                                {{
                                    number_format(
                                        $contribution
                                            ->zwg_employee_contribution,
                                        2
                                    )
                                }}

                            </td>


                            <td class="text-end">

                                {{
                                    number_format(
                                        $contribution
                                            ->zwg_employer_contribution,
                                        2
                                    )
                                }}

                            </td>


                            <td class="text-end">

                                {{
                                    number_format(
                                        (
                                            (float)
                                            $contribution
                                                ->zwg_employee_avc
                                        )
                                        +
                                        (
                                            (float)
                                            $contribution
                                                ->zwg_employer_avc
                                        ),
                                        2
                                    )
                                }}

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="14"
                                class="
                                    text-center
                                    text-muted
                                    py-4
                                "
                            >

                                No contribution transactions
                                have been posted for this batch.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-3">

            {{
                $contributions->links()
            }}

        </div>

    </div>

</div>


@endsection