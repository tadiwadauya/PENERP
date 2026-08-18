<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>
        Monthly Contribution Reconciliation
    </title>


    <style>

        @page {
            margin:
                14px
                15px;
        }


        body {
            font-family:
                DejaVu Sans,
                sans-serif;

            font-size:
                10px;

            color:
                #111111;

            margin:
                0;

            padding:
                0;
        }


        /*
        |--------------------------------------------------------------------------
        | Outer Border
        |--------------------------------------------------------------------------
        */

        .document {
            border:
                1px solid
                #111111;

            padding:
                4px;

            min-height:
                760px;
        }


        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        .header {
            background:
                #c9c9c9;

            border:
                1px solid
                #111111;

            padding:
                13px
                11px;

            font-size:
                12px;

            font-weight:
                bold;

            line-height:
                2;
        }


        /*
        |--------------------------------------------------------------------------
        | Section Titles
        |--------------------------------------------------------------------------
        */

        .section-title {
            margin:
                12px
                26px
                10px
                26px;

            font-size:
                11px;

            font-weight:
                bold;
        }


        /*
        |--------------------------------------------------------------------------
        | Tables
        |--------------------------------------------------------------------------
        */

        table.reconciliation {
            width:
                94%;

            margin-left:
                3%;

            border-collapse:
                collapse;
        }


        table.reconciliation td {
            padding:
                7px
                9px;

            border-left:
                1px solid
                #111111;

            border-right:
                1px solid
                #111111;
        }


        table.reconciliation tr.border-row td {
            border-top:
                1px solid
                #111111;

            border-bottom:
                1px solid
                #111111;
        }


        .amount {
            width:
                31%;

            text-align:
                right;
        }


        .strong {
            font-weight:
                bold;
        }


        /*
        |--------------------------------------------------------------------------
        | Comments
        |--------------------------------------------------------------------------
        */

        .comments-title {
            margin:
                20px
                4%
                7px
                4%;

            font-weight:
                bold;

            font-style:
                italic;
        }


        .comments-box {
            margin:
                0
                4%;

            height:
                145px;

            border:
                1px solid
                #111111;
        }


        /*
        |--------------------------------------------------------------------------
        | Signatures
        |--------------------------------------------------------------------------
        */

        .signature-table {
            width:
                100%;

            margin-top:
                17px;

            border-collapse:
                collapse;
        }


        .signature-table td {
            border:
                none;

            padding:
                9px
                8px;
        }


        .signature-label {
            width:
                25%;
        }


        .signature-name {
            width:
                35%;
        }


        .date-label {
            width:
                9%;
        }


        .signature-line {
            border-bottom:
                1px dashed
                #111111;

            height:
                10px;
        }

    </style>

</head>


<body>


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


<div class="document">


    {{-- =========================================================
         HEADER
    ========================================================= --}}

    <div class="header">

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


    {{-- =========================================================
         MONTHLY MEMBERSHIP RECONCILIATION
    ========================================================= --}}

    <div class="section-title">

        MONTHLY MEMBERSHIP RECONCILIATION AS AT&nbsp;&nbsp;

        {{
            $currentPeriod
                ->period_date
                ->format(
                    'd F Y'
                )
        }}

    </div>


    <table class="reconciliation">


        <tr class="border-row">

            <td class="strong">

                Membership as at&nbsp;&nbsp;

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

                Membership as at&nbsp;&nbsp;

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


    {{-- =========================================================
         MONTHLY CONTRIBUTION RECONCILIATION
    ========================================================= --}}

    <div class="section-title">

        MONTHLY CONTRIBUTION RECONCILIATION AS AT&nbsp;&nbsp;

        {{
            $currentPeriod
                ->period_date
                ->format(
                    'd F Y'
                )
        }}

    </div>


    <table class="reconciliation">


        <tr class="border-row">

            <td class="strong">

                Contributions Due as at&nbsp;&nbsp;

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

                Add Contributions for New members

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


        <tr>

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

                Total Contributions Due as at&nbsp;&nbsp;

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


    {{-- =========================================================
         COMMENTS
    ========================================================= --}}

    <div class="comments-title">

        Comments:

    </div>


    <div class="comments-box"></div>


    {{-- =========================================================
         SIGNATURES
    ========================================================= --}}

    <table class="signature-table">


        <tr>

            <td class="signature-label">

                DONE BY

            </td>


            <td class="signature-name">

                <div class="signature-line"></div>

            </td>


            <td class="date-label">

                Date

            </td>


            <td>

                <div class="signature-line"></div>

            </td>

        </tr>


        <tr>

            <td>

                CHECKED BY

            </td>


            <td>

                <div class="signature-line"></div>

            </td>


            <td>

                Date

            </td>


            <td>

                <div class="signature-line"></div>

            </td>

        </tr>


        <tr>

            <td>

                AUTHORISED BY

            </td>


            <td>

                <div class="signature-line"></div>

            </td>


            <td>

                Date

            </td>


            <td>

                <div class="signature-line"></div>

            </td>

        </tr>

    </table>

</div>

</body>

</html>