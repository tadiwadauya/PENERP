@extends('layouts.app')

@section('title', 'Benefit Statement Preview')

@section('page-heading', 'Benefit Statement Preview')

@section('page-subheading')
Benefit statement as at {{ $statement['statement_date']->format('d/m/Y') }}
@endsection

@push('styles')
<style>
    .benefit-statement-card {
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    .statement-header {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 18px;
        margin-bottom: 22px;
    }

    .statement-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 3px;
    }

    .statement-subtitle {
        font-size: 16px;
        font-weight: 600;
    }

    .statement-section-title {
        background: #f8f9fa;
        border-left: 4px solid var(--bs-primary);
        padding: 10px 12px;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 12px;
    }

    .statement-table th {
        background: #f8f9fa;
        font-weight: 600;
        vertical-align: middle;
    }

    .statement-table td {
        vertical-align: middle;
    }

    .amount-column {
        text-align: right;
        white-space: nowrap;
    }

    .statement-value {
        font-weight: 600;
    }

    .contribution-table th,
    .contribution-table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .statement-warning {
        border-left: 4px solid #ffc107;
    }

    @media print {
        .no-print,
        .pensions-context-navigation {
            display: none !important;
        }

        .benefit-statement-card {
            box-shadow: none !important;
            border: 0 !important;
        }

        .card-body {
            padding: 0 !important;
        }
    }
</style>
@endpush

@section('content')

@include('pensions-administration.partials.navigation')

@php
    $pd=$statement['personal_details'];
    $wb=$statement['withdrawal_benefit'];
    $pb=$statement['pension_benefit'];
    $db=$statement['death_benefit'];
    $aif=$statement['accumulated_interest_in_fund'];
    $history=$statement['contribution_history'];
    $money=fn($value)=>number_format((float)($value ?? 0),2);
@endphp

<div class="row no-print">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2 mb-3">
            <a href="{{ route('pensions-administration.updates.benefit-statements.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i> Back</a>
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="mdi mdi-printer-outline me-1"></i> Print Statement</button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card benefit-statement-card">
            <div class="card-body">

                <div class="statement-header text-center">
                    <div class="statement-title">LOCAL AUTHORITIES PENSION FUND</div>
                    <div class="statement-subtitle text-primary">BENEFIT STATEMENT</div>
                    <div class="text-muted mt-1">As at {{ $statement['statement_date']->format('d/m/Y') }}</div>
                </div>

                <div class="statement-section-title">A. PERSONAL DETAILS</div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered statement-table mb-0">
                        <tbody>
                            <tr>
                                <th style="width:20%;">Member Name</th>
                                <td style="width:30%;">{{ $statement['member']['name'] ?: '-' }}</td>
                                <th style="width:20%;">Employer</th>
                                <td style="width:30%;">{{ $statement['employment']['employer_name'] ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>National ID</th>
                                <td>{{ $statement['member']['national_id'] ?: '-' }}</td>
                                <th>Member Number</th>
                                <td>{{ $statement['member']['member_number'] ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>PenAd Member Number</th>
                                <td>{{ $statement['member']['penad_member_number'] ?: '-' }}</td>
                                <th>Staff Number</th>
                                <td>{{ $statement['employment']['staff_number'] ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Gender</th>
                                <td>{{ $pd['gender'] ?: '-' }}</td>
                                <th>Marital Status</th>
                                <td>{{ $pd['marital_status'] ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date of Birth</th>
                                <td>{{ $pd['date_of_birth'] ? \Carbon\Carbon::parse($pd['date_of_birth'])->format('d/m/Y') : '-' }}</td>
                                <th>Date Joined Fund</th>
                                <td>{{ $pd['date_joined_fund'] ? \Carbon\Carbon::parse($pd['date_joined_fund'])->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Pensionable Service</th>
                                <td>{{ number_format((int)$pd['pensionable_service_months']) }} months</td>
                                <th>Normal Retirement Date</th>
                                <td>{{ $pd['normal_retirement_date'] ? \Carbon\Carbon::parse($pd['normal_retirement_date'])->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Current Annual Emoluments</th>
                                <td class="statement-value">{{ $statement['currency'] }} {{ $money($pd['current_annual_emoluments']) }}</td>
                                <th>Projected Replacement Ratio</th>
                                <td class="statement-value">{{ number_format((float)$pd['projected_replacement_ratio'],2) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="statement-section-title">B. WITHDRAWAL BENEFIT</div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered statement-table mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="amount-column" style="width:25%;">Amount ({{ $statement['currency'] }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>(i) Accumulated Employee Contributions</td>
                                <td class="amount-column">{{ $money($wb['accumulated_employee_contributions']) }}</td>
                            </tr>
                            <tr>
                                <td>(ii) Interest Earned on Employee Contributions During Period</td>
                                <td class="amount-column">{{ $money($wb['interest_earned_on_employee_contributions_during_period']) }}</td>
                            </tr>
                            <tr>
                                <td>(iii) Accumulated Additional Contributions Plus Interest</td>
                                <td class="amount-column">{{ $money($wb['accumulated_additional_contributions_plus_interest']) }}</td>
                            </tr>
                            <tr>
                                <td>(iv) Accumulated Employer Contributions Plus Interest</td>
                                <td class="amount-column">{{ $money($wb['accumulated_employer_contributions_plus_interest']) }}</td>
                            </tr>
                            <tr>
                                <td>(v) Past Service Contributions Plus Interest</td>
                                <td class="amount-column">{{ $money($wb['past_service_contributions_plus_interest']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="statement-section-title">C. PENSION BENEFIT</div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered statement-table mb-0">
                        <thead>
                            <tr>
                                <th>Retirement Age</th>
                                <th>Retirement Date</th>
                                <th class="amount-column">Projected Service</th>
                                <th class="amount-column">Annual Pension</th>
                                <th class="amount-column">1/3 Commutation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pb['ages'] as $age=>$benefit)
                            <tr>
                                <td>Age {{ $age }}</td>
                                <td>{{ \Carbon\Carbon::parse($benefit['retirement_date'])->format('d/m/Y') }}</td>
                                <td class="amount-column">{{ number_format((int)$benefit['projected_service_months']) }} months</td>
                                <td class="amount-column">{{ $money($benefit['annual_pension']) }}</td>
                                <td class="amount-column">{{ $money($benefit['commutation_amount']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="statement-section-title">D. DEATH BENEFIT</div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered statement-table mb-0">
                        <tbody>
                            <tr>
                                <th style="width:75%;">Lump Sum Payment</th>
                                <td class="amount-column">{{ $statement['currency'] }} {{ $money($db['lump_sum_payment']) }}</td>
                            </tr>
                            <tr>
                                <th>Spouse Pension</th>
                                <td class="amount-column">{{ $statement['currency'] }} {{ $money($db['spouse_pension']) }}</td>
                            </tr>
                            <tr>
                                <th>Children Pension</th>
                                <td class="amount-column">
                                    @if($db['children_pension']===null)
                                        <span class="text-muted">Subject to dependant records</span>
                                    @else
                                        {{ $statement['currency'] }} {{ $money($db['children_pension']) }}
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="statement-section-title">E. ACCUMULATED INTEREST IN FUND</div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered statement-table mb-0">
                        <tbody>
                            <tr>
                                <th style="width:35%;">Eligibility</th>
                                <td>
                                    @if($aif['eligible'])
                                        <span class="badge bg-success">Eligible</span>
                                    @else
                                        <span class="badge bg-secondary">Not Eligible</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Accumulated Interest in Fund</th>
                                <td class="statement-value">{{ $statement['currency'] }} {{ $money($aif['amount']) }}</td>
                            </tr>
                            @if(!$aif['eligible'] && !empty($aif['reason']))
                            <tr>
                                <th>Reason</th>
                                <td>{{ $aif['reason'] }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="statement-section-title">CONTRIBUTION HISTORY</div>

                <div class="table-responsive">
                    <table class="table table-bordered contribution-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th class="amount-column">Employee Contributions</th>
                                <th class="amount-column">Additional Voluntary Contributions</th>
                                <th class="amount-column">Employer Contributions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="fw-semibold">
                                <td>Opening Balance</td>
                                <td class="amount-column">{{ $money($history['opening_balance']['employee_contribution']) }}</td>
                                <td class="amount-column">{{ $money($history['opening_balance']['employee_avc']) }}</td>
                                <td class="amount-column">{{ $money($history['opening_balance']['employer_contribution']) }}</td>
                            </tr>

                            @foreach($history['months'] as $month=>$amounts)
                            <tr>
                                <td>{{ \Carbon\Carbon::create(2000,(int)$month,1)->format('F') }}</td>
                                <td class="amount-column">{{ $money($amounts['employee_contribution']) }}</td>
                                <td class="amount-column">{{ $money($amounts['employee_avc']) }}</td>
                                <td class="amount-column">{{ $money($amounts['employer_contribution']) }}</td>
                            </tr>
                            @endforeach

                            <tr class="fw-bold table-light">
                                <td>Balance C/Fwd</td>
                                <td class="amount-column">{{ $money($history['closing_balance']['employee_contribution']) }}</td>
                                <td class="amount-column">{{ $money($history['closing_balance']['employee_avc']) }}</td>
                                <td class="amount-column">{{ $money($history['closing_balance']['employer_contribution']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning statement-warning mt-4 mb-0">
                    <div class="d-flex">
                        <i class="mdi mdi-information-outline font-size-20 me-2"></i>
                        <div>
                            <strong>Development Preview</strong>
                            <div class="small">This statement is currently being validated against the LAPF benefit calculation rules before final PDF generation is enabled.</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection