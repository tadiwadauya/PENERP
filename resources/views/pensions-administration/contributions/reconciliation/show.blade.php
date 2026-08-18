@extends('layouts.app')

@section(
    'title',
    'Monthly Contribution Reconciliation'
)

@section(
    'page-heading',
    'Monthly Contribution Reconciliation'
)


@section('page-actions')

    <a
        href="{{
            route(
                'pensions-administration.contributions.reconciliation.pdf',
                $report['batch']
            )
        }}"
        target="_blank"
        class="btn btn-danger"
    >

        <i class="mdi mdi-file-pdf-box me-1"></i>

        View PDF

    </a>


    <a
        href="{{
            route(
                'pensions-administration.contributions.reconciliation.pdf.download',
                $report['batch']
            )
        }}"
        class="btn btn-outline-danger"
    >

        <i class="mdi mdi-download me-1"></i>

        Download PDF

    </a>


    <a
        href="{{
            route(
                'pensions-administration.contributions.imports.review',
                $report['batch']
            )
        }}"
        class="btn btn-light"
    >

        <i class="mdi mdi-arrow-left me-1"></i>

        Back to Review

    </a>

@endsection


@push('styles')

<style>

    .reconciliation-wrapper {
        max-width: 1000px;
        margin: 0 auto;
    }


    .reconciliation-document {
        background: #ffffff;
        border: 1px solid #212529;
        padding: 8px;
    }


    .reconciliation-header {
        background: #d0d0d0;
        border: 1px solid #212529;
        padding: 18px 12px;
        font-weight: 700;
        font-size: 17px;
        line-height: 2;
    }


    .reconciliation-section-title {
        font-size: 15px;
        font-weight: 700;
        text-transform: uppercase;
        margin: 18px 30px 14px;
    }


    .reconciliation-table {
        width: calc(100% - 60px);
        margin: 0 30px;
        border-collapse: collapse;
    }


    .reconciliation-table td {
        border-left: 1px solid #212529;
        border-right: 1px solid #212529;
        padding: 10px 12px;
    }


    .reconciliation-table tr.border-row td {
        border-top: 1px solid #212529;
        border-bottom: 1px solid #212529;
    }


    .reconciliation-table .amount {
        width: 32%;
        text-align: right;
    }


    .reconciliation-table .strong {
        font-weight: 700;
    }


    .reconciliation-comments-label {
        margin: 24px 38px 8px;
        font-weight: 700;
        font-style: italic;
    }


    .reconciliation-comments-box {
        height: 180px;
        border: 1px solid #212529;
        margin: 0 38px;
    }


    .reconciliation-signatures {
        width: 100%;
        margin-top: 25px;
        border-collapse: separate;
        border-spacing: 8px 16px;
    }


    .reconciliation-signatures td {
        vertical-align: bottom;
    }


    .signature-line {
        display: inline-block;
        width: 230px;
        border-bottom: 1px dashed #212529;
    }

</style>

@endpush


@section('content')

@include(
    'pensions-administration.partials.navigation'
)


@php

    $batch =
        $report[
            'batch'
        ];

    $employer =
        $report[
            'employer'
        ];

    $currentPeriod =
        $report[
            'current_period'
        ];

    $previousPeriod =
        $report[
            'previous_period'
        ];

    $membership =
        $report[
            'membership'
        ];

    $contributions =
        $report[
            'contributions'
        ];

    $currency =
        $report[
            'currency'
        ];

@endphp


<div class="reconciliation-wrapper">

    <div class="card">

        <div class="card-body">

            <div class="reconciliation-document">


                {{-- =====================================================
                     HEADER
                ====================================================== --}}

                <div class="reconciliation-header">

                    LOCAL AUTHORITIES PENSION FUND

                    <br>

                    {{
                        strtoupper(
                            $employer
                                ?->name
                            ??
                            ''
                        )
                    }}

                </div>


                {{-- =====================================================
                     MEMBERSHIP RECONCILIATION
                ====================================================== --}}

                <div class="reconciliation-section-title">

                    MONTHLY MEMBERSHIP RECONCILIATION AS AT

                    {{
                        $currentPeriod
                            ->period_date
                            ->format(
                                'd F Y'
                            )
                    }}

                </div>


                <table class="reconciliation-table">


                    <tr class="border-row">

                        <td class="strong">

                            Membership as at

                            {{
                                $previousPeriod
                                    ? $previousPeriod
                                        ->period_date
                                        ->format(
                                            'd F Y'
                                        )
                                    : 'Previous Period'
                            }}

                        </td>


                        <td class="amount strong">

                            {{
                                number_format(
                                    $membership[
                                        'previous'
                                    ]
                                )
                            }}

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Add New Members
                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $membership[
                                        'new_members'
                                    ]
                                )
                            }}

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Add Reinstatements
                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $membership[
                                        'reinstatements'
                                    ]
                                )
                            }}

                        </td>

                    </tr>


                    <tr class="border-row">

                        <td>

                            Less Exits/Suspended/Nil Contributors

                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $membership[
                                        'less_exits_suspended_nil'
                                    ]
                                )
                            }}

                        </td>

                    </tr>


                    <tr class="border-row">

                        <td class="strong">

                            Membership as at

                            {{
                                $currentPeriod
                                    ->period_date
                                    ->format(
                                        'd F Y'
                                    )
                            }}

                        </td>

                        <td class="amount strong">

                            {{
                                number_format(
                                    $membership[
                                        'current'
                                    ]
                                )
                            }}

                        </td>

                    </tr>

                </table>


                {{-- =====================================================
                     CONTRIBUTION RECONCILIATION
                ====================================================== --}}

                <div class="reconciliation-section-title">

                    MONTHLY CONTRIBUTION RECONCILIATION AS AT

                    {{
                        $currentPeriod
                            ->period_date
                            ->format(
                                'd F Y'
                            )
                    }}

                </div>


                <table class="reconciliation-table">


                    <tr class="border-row">

                        <td class="strong">

                            Contributions Due as at

                            {{
                                $previousPeriod
                                    ? $previousPeriod
                                        ->period_date
                                        ->format(
                                            'd F Y'
                                        )
                                    : 'Previous Period'
                            }}

                        </td>


                        <td class="amount strong">

                            {{ $currency }}

                            {{
                                number_format(
                                    $contributions[
                                        'previous'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Add Contributions for New Members
                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $contributions[
                                        'new_members'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Add Contributions for Reinstatements
                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $contributions[
                                        'reinstatements'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>


                    <tr>

                        <td>
                            Add Increase/Decrease on Contributions
                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $contributions[
                                        'increase_decrease'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>


                    <tr class="border-row">

                        <td>
                            Add Differences on Contributions
                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $contributions[
                                        'differences'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>


                    <tr class="border-row">

                        <td>

                            Less Contributions for
                            Exits/Suspended/Nil Contributors

                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $contributions[
                                        'less_exits_suspended_nil'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>


                    <tr class="border-row">

                        <td class="strong">

                            Total Contributions Due as at

                            {{
                                $currentPeriod
                                    ->period_date
                                    ->format(
                                        'd F Y'
                                    )
                            }}

                        </td>

                        <td class="amount strong">

                            {{ $currency }}

                            {{
                                number_format(
                                    $contributions[
                                        'total_due'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>


                    <tr class="border-row">

                        <td>

                            Total Contributions as per Schedule
                            from Local Authority

                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $contributions[
                                        'schedule_total'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>


                    <tr class="border-row">

                        <td>

                            Variance on Remitted Contributions

                        </td>

                        <td class="amount">

                            {{
                                number_format(
                                    $contributions[
                                        'variance'
                                    ],
                                    2
                                )
                            }}

                        </td>

                    </tr>

                </table>


                {{-- =====================================================
                     COMMENTS
                ====================================================== --}}

                <div class="reconciliation-comments-label">

                    Comments:

                </div>


                <div class="reconciliation-comments-box"></div>


                {{-- =====================================================
                     SIGNATURES
                ====================================================== --}}

                <table class="reconciliation-signatures">

                    <tr>

                        <td style="width:25%;">
                            DONE BY
                        </td>

                        <td style="width:35%;">
                            <span class="signature-line"></span>
                        </td>

                        <td style="width:10%;">
                            Date
                        </td>

                        <td>
                            <span class="signature-line"></span>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            CHECKED BY
                        </td>

                        <td>
                            <span class="signature-line"></span>
                        </td>

                        <td>
                            Date
                        </td>

                        <td>
                            <span class="signature-line"></span>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            AUTHORISED BY
                        </td>

                        <td>
                            <span class="signature-line"></span>
                        </td>

                        <td>
                            Date
                        </td>

                        <td>
                            <span class="signature-line"></span>
                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection