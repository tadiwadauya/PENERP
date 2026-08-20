<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>
        Monthly Contribution Reconciliation
    </title>


    <style>

        @page {
            margin: 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #222;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mt-15 {
            margin-top: 15px;
        }

        .heading {
            font-size: 14px;
            font-weight: bold;
        }

        .subheading {
            font-size: 11px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #555;
            padding: 4px;
            vertical-align: middle;
        }

        th {
            background: #e7e7e7;
            font-weight: bold;
        }

        .total {
            background: #ddebf7;
            font-weight: bold;
        }

        .warning {
            background: #fff2cc;
            font-weight: bold;
        }

        .success {
            background: #e2f0d9;
            font-weight: bold;
        }

        .signature-table td {
            border: none;
            padding-top: 25px;
        }

    </style>

</head>


<body>

@php
    $batch = $report['batch'];
    $employer = $report['employer'];
    $period = $report['current_period'];
    $previousPeriod = $report['previous_period'];
    $currency = $report['currency'];
    $membership = $report['membership'];
    $contributions = $report['contributions'];
    $schedule = $report['schedule'];
    $calculation = $report['calculation'];
@endphp


<div class="text-center">

    <div class="heading">
        LOCAL AUTHORITIES PENSION FUND
    </div>

    <div class="subheading mt-10">
        {{ strtoupper($employer?->name ?? '') }}
    </div>

    <div class="subheading mt-10">
        MONTHLY CONTRIBUTION RECONCILIATION AS AT
        {{ strtoupper($period?->period_date?->format('d F Y')) }}
    </div>

    <div class="mt-10">
        Currency: {{ $currency }}
        |
        Batch #{{ $batch->id }}
    </div>

</div>


{{-- MEMBERSHIP --}}

<div class="mt-15">

    <div class="subheading text-center">
        MONTHLY MEMBERSHIP RECONCILIATION AS AT
        {{ $period?->period_date?->format('d F Y') }}
    </div>


    <table class="mt-10">

        <tbody>

            <tr>
                <td>Membership as at {{ $previousPeriod?->period_date?->format('d F Y') ?? 'Previous Period' }}</td>
                <td class="text-right">{{ number_format($membership['previous']) }}</td>
            </tr>

            <tr>
                <td>Add New Members</td>
                <td class="text-right">{{ number_format($membership['new_members']) }}</td>
            </tr>

            <tr>
                <td>Add Reinstatements</td>
                <td class="text-right">{{ number_format($membership['reinstatements']) }}</td>
            </tr>

            <tr>
                <td>Less Exits / Suspended / Nil Contributors</td>
                <td class="text-right">{{ number_format($membership['less_exits_suspended_nil']) }}</td>
            </tr>

            <tr class="total">
                <td>Membership as at {{ $period?->period_date?->format('d F Y') }}</td>
                <td class="text-right">{{ number_format($membership['current']) }}</td>
            </tr>

        </tbody>

    </table>

</div>


{{-- CONTRIBUTION MOVEMENT --}}

<div class="mt-15">

    <div class="subheading text-center">
        MONTHLY CONTRIBUTION MOVEMENT RECONCILIATION
    </div>


    <table class="mt-10">

        <thead>

            <tr>
                <th>Description</th>
                <th class="text-right">Normal</th>
                <th class="text-right">AVC</th>
                <th class="text-right">Total</th>
            </tr>

        </thead>


        <tbody>

            <tr>
                <td>Contributions Due as at {{ $previousPeriod?->period_date?->format('d F Y') ?? 'Previous Period' }}</td>
                <td class="text-right">{{ number_format($contributions['previous_normal'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['previous_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['previous_total'], 2) }}</td>
            </tr>

            <tr>
                <td>Add Contributions for New Members</td>
                <td class="text-right">{{ number_format($contributions['new_members_normal'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['new_members_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['new_members_total'], 2) }}</td>
            </tr>

            <tr>
                <td>Add Contributions for Reinstatements</td>
                <td class="text-right">{{ number_format($contributions['reinstatements_normal'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['reinstatements_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['reinstatements_total'], 2) }}</td>
            </tr>

            <tr>
                <td>Add Increase / Decrease on Contributions</td>
                <td class="text-right">{{ number_format($contributions['increase_decrease_normal'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['increase_decrease_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['increase_decrease_total'], 2) }}</td>
            </tr>

            <tr>
                <td>Add Differences on Contributions</td>
                <td class="text-right">{{ number_format($contributions['differences_normal'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['differences_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['differences_total'], 2) }}</td>
            </tr>

            <tr>
                <td>Less Contributions for Exits / Suspended / Nil Contributors</td>
                <td class="text-right">{{ number_format($contributions['less_nil_normal'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['less_nil_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['less_nil_total'], 2) }}</td>
            </tr>

            <tr class="total">
                <td>Total Contributions Due as at {{ $period?->period_date?->format('d F Y') }}</td>
                <td class="text-right">{{ number_format($contributions['normal_due'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['avc_due'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['total_due'], 2) }}</td>
            </tr>

            <tr>
                <td>Total Contributions as per Schedule from Local Authority</td>
                <td class="text-right">{{ number_format($contributions['schedule_normal'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['schedule_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['schedule_total'], 2) }}</td>
            </tr>

            <tr class="{{ abs($contributions['variance']) > 0.01 ? 'warning' : 'success' }}">
                <td>Movement Reconciliation Variance</td>
                <td class="text-right">{{ number_format($contributions['normal_variance'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['avc_variance'], 2) }}</td>
                <td class="text-right">{{ number_format($contributions['variance'], 2) }}</td>
            </tr>

        </tbody>

    </table>

</div>


{{-- SYSTEM CALCULATION --}}

<div class="mt-15">

    <div class="subheading text-center">
        PENERP SYSTEM CALCULATION VS UPLOADED SCHEDULE
    </div>


    <table class="mt-10">

        <thead>

            <tr>
                <th>Description</th>
                <th class="text-right">System Calculated</th>
                <th class="text-right">Uploaded Schedule</th>
                <th class="text-right">Variance</th>
            </tr>

        </thead>


        <tbody>

            <tr>
                <td>Employee Contributions</td>
                <td class="text-right">{{ number_format($calculation['employee_contribution'], 2) }}</td>
                <td class="text-right">{{ number_format($schedule['employee_contribution'], 2) }}</td>
                <td class="text-right">{{ number_format($calculation['employee_variance'], 2) }}</td>
            </tr>

            <tr>
                <td>Employer Contributions</td>
                <td class="text-right">{{ number_format($calculation['employer_contribution'], 2) }}</td>
                <td class="text-right">{{ number_format($schedule['employer_contribution'], 2) }}</td>
                <td class="text-right">{{ number_format($calculation['employer_variance'], 2) }}</td>
            </tr>

            <tr>
                <td>Employee AVC</td>
                <td class="text-right">{{ number_format($calculation['employee_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($schedule['employee_avc'], 2) }}</td>
                <td class="text-right">0.00</td>
            </tr>

            <tr>
                <td>Employer AVC</td>
                <td class="text-right">{{ number_format($calculation['employer_avc'], 2) }}</td>
                <td class="text-right">{{ number_format($schedule['employer_avc'], 2) }}</td>
                <td class="text-right">0.00</td>
            </tr>

            <tr class="{{ abs($calculation['variance']) > 0.01 ? 'warning' : 'success' }}">
                <td><strong>GRAND TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($calculation['total_expected'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($schedule['total_expected'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($calculation['variance'], 2) }}</strong></td>
            </tr>

        </tbody>

    </table>

</div>


{{-- SIGNATURES --}}

<table class="signature-table mt-15">

    <tr>

        <td style="width:33%;">
            DONE BY: ___________________________
            <br>
            Date: __________________
        </td>

        <td style="width:33%;">
            CHECKED BY: _________________________
            <br>
            Date: __________________
        </td>

        <td style="width:34%;">
            AUTHORISED BY: ______________________
            <br>
            Date: __________________
        </td>

    </tr>

</table>


<div class="mt-15">

    <strong>Comments:</strong>

    <div style="border-bottom:1px solid #555; height:18px;"></div>
    <div style="border-bottom:1px solid #555; height:18px;"></div>

</div>

</body>

</html>