<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Benefit Statement</title>
<style>
    @page { size: A3 landscape; margin: 11mm 12mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; color: #171717; font-size: 9px; margin: 0; }
    .page { page-break-after: always; }
    .page:last-child { page-break-after: auto; }
    .masthead { border-bottom: 2px solid #32246f; padding-bottom: 5px; margin-bottom: 11px; color: #32246f; }
    .masthead-title { font-size: 20px; font-weight: bold; letter-spacing: .4px; }
    .masthead-meta { color: #333; font-size: 8px; padding-top: 3px; }
    .statement-heading { text-align: center; font-size: 13px; font-weight: bold; margin-bottom: 12px; }
    .columns { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .columns > tbody > tr > td { vertical-align: top; padding: 0 7px; }
    .columns > tbody > tr > td:first-child { padding-left: 0; }
    .columns > tbody > tr > td:last-child { padding-right: 0; }
    .col-left { width: 27%; } .col-centre { width: 36%; } .col-right { width: 37%; }
    .private { font-size: 13px; font-weight: bold; margin: 68px 0 12px; }
    .identity { border: 1px solid #222; border-radius: 9px; padding: 13px; min-height: 145px; }
    .identity-name { font-weight: bold; font-size: 11px; margin-bottom: 9px; }
    .identity-employer { margin-bottom: 28px; }
    .identity table { width: 100%; border-collapse: collapse; }
    .identity td { padding: 5px 2px; }
    .identity td:last-child { font-weight: bold; }
    .section { border: 1px solid #666; border-radius: 9px; overflow: hidden; margin-bottom: 9px; background: #e8e8e8; }
    .section-title { background: #111; color: #fff; padding: 7px 9px; font-weight: bold; font-size: 10px; }
    .section table { width: 100%; border-collapse: collapse; }
    .section td { padding: 5px 7px; vertical-align: top; }
    .section td:last-child { text-align: right; font-weight: bold; white-space: nowrap; width: 31%; border-left: 1px solid #888; }
    .section .subline td { padding-top: 0; font-size: 8px; }
    .section .subline td:last-child { border-left: 1px solid #888; }
    .history { width: 100%; border-collapse: collapse; font-size: 8px; text-align: right; }
    .history th { background: #eee; font-size: 7.5px; border: 1px solid #666; padding: 5px 3px; }
    .history td { border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px 5px; }
    .history td:first-child, .history th:first-child { text-align: left; }
    .history tr:last-child td { background: #dedede; border-bottom: 1px solid #666; font-weight: bold; }
    .notes { font-size: 7px; line-height: 1.25; margin-top: 8px; text-align: justify; }
    .notes p { margin: 0 0 4px; }
    .note-heading { font-weight: bold; }
    .turnover { margin-top: 15px; font-weight: bold; }
    .legal { font-size: 11px; line-height: 1.65; }
    .legal h2 { font-size: 15px; text-align: center; margin: 23px 0; }
    .legal h3 { font-size: 12px; margin: 16px 0 4px; }
    .legal p { margin: 4px 0 9px; }
    .legal .footer { margin-top: 30px; border-top: 1px solid #aaa; padding-top: 12px; }
    .draft { color: #7d4400; font-size: 8px; }
</style>
</head>
<body>
@php
    $member=$statement['member']; $employment=$statement['employment']; $pd=$statement['personal_details'];
    $wb=$statement['withdrawal_benefit']; $pb=$statement['pension_benefit']; $db=$statement['death_benefit'];
    $aif=$statement['accumulated_interest_in_fund']; $history=$statement['contribution_history'];
    $money=fn($v)=>number_format((float)($v??0),2); $currency=$statement['currency'];
    $date=fn($v)=>$v ? \Carbon\Carbon::parse($v)->format('d-M-Y') : '-';
@endphp
<div class="page">
    <div class="masthead"><div class="masthead-title">LOCAL AUTHORITIES PENSION FUND</div><div class="masthead-meta">Benefit Statement • {{ $statement['statement_date']->format('d/m/Y') }}</div></div>
    <div class="statement-heading">Benefit Statement as at {{ $statement['statement_date']->format('d/m/Y') }}</div>
    <table class="columns"><tr>
        <td class="col-left">
            <div class="private">PRIVATE AND CONFIDENTIAL</div>
            <div class="identity">
                <div class="identity-name">{{ strtoupper($member['surname']??'') }} &nbsp; {{ strtoupper($member['first_names']??'') }}</div>
                <div class="identity-employer">{{ $employment['employer_name']??'-' }}</div>
                <table><tr><td>NATIONAL ID</td><td>{{ $member['national_id']??'-' }}</td></tr>
                <tr><td>MEMBER NUMBER</td><td>{{ filled($member['penad_member_number']??null) ? trim((string)$member['penad_member_number']) : (filled($member['penerp_member_number']??null) ? trim((string)$member['penerp_member_number']) : ($member['member_number']??'-')) }}</td></tr>
                <tr><td>STAFF NUMBER</td><td>{{ $employment['staff_number']??'-' }}</td></tr>
                <tr><td>VOTE NUMBER</td><td>{{ $employment['vote_number']??'-' }}</td></tr></table>
            </div>
        </td>
        <td class="col-centre">
            <div class="section"><div class="section-title">A. PERSONAL DETAILS</div><table>
                <tr><td>GENDER</td><td>{{ $pd['gender']??'-' }}</td></tr>
                <tr><td>DATE OF BIRTH</td><td>{{ $date($pd['date_of_birth']??null) }}</td></tr>
                <tr><td>MARITAL STATUS</td><td>{{ $pd['marital_status']??'-' }}</td></tr>
                <tr><td>DATE JOINED FUND</td><td>{{ $date($pd['date_joined_fund']??null) }}</td></tr>
                <tr><td>PENSIONABLE SERVICE TO DATE (months)</td><td>{{ $pd['pensionable_service_months']??0 }}</td></tr>
                <tr><td>NORMAL RETIREMENT DATE</td><td>{{ $date($pd['normal_retirement_date']??null) }}</td></tr>
                <tr><td>CURRENT ANNUAL EMOLUMENTS</td><td>{{ $currency }} {{ $money($pd['current_annual_emoluments']??0) }}</td></tr>
                <tr><td>PROJECTED REPLACEMENT RATIO</td><td>{{ $money($pd['projected_replacement_ratio']??0) }}%</td></tr>
            </table></div>
            <div class="section"><div class="section-title">B. WITHDRAWAL BENEFIT</div><table>
                <tr><td>(i) ACCUMULATED EMPLOYEE CONTRIBUTIONS</td><td>{{ $money($wb['accumulated_employee_contributions']??0) }}</td></tr>
                <tr><td>(ii) INTEREST EARNED ON EMPLOYEE CONTRIBUTIONS DURING THE YEAR</td><td>{{ $money($wb['interest_earned_on_employee_contributions_during_period']??0) }}</td></tr>
                <tr><td>(iii) ACCUMULATED EMPLOYER CONTRIBUTIONS PLUS INTEREST</td><td>{{ $money($wb['accumulated_employer_contributions_plus_interest']??0) }}</td></tr>
                <tr><td>(iv) ACCUMULATED ADDITIONAL CONTRIBUTIONS PLUS INTEREST</td><td>{{ $money(0) }}</td></tr>
                <tr><td>(v) PAST SERVICE CONTRIBUTIONS PLUS INTEREST</td><td>{{ $money($wb['past_service_contributions_plus_interest']??0) }}</td></tr>
            </table></div>
            <div class="section"><div class="section-title">C. PENSION BENEFIT</div><table>
                @foreach($pb['ages'] as $age=>$benefit)
                    <tr><td>ESTIMATED ANNUAL PENSION AT {{ $age }}</td><td>{{ $money($benefit['annual_pension']??0) }}</td></tr>
                    <tr class="subline"><td>1/3 COMMUTATION AT {{ $age }}</td><td>{{ $money($benefit['commutation_amount']??0) }}</td></tr>
                @endforeach
            </table></div>
            <div class="section"><div class="section-title">D. DEATH BENEFIT</div><table>
                <tr><td>(i) LUMP SUM PAYMENT</td><td>{{ $money($db['lump_sum_payment']??0) }}</td></tr>
                <tr><td>(ii) SPOUSE PENSION</td><td>{{ $money($db['spouse_pension']??0) }}</td></tr>
                <tr><td>(iii) CHILDREN PENSION</td><td>{{ ($db['children_pension']??null)===null ? 'Subject to eligibility' : $money($db['children_pension']) }}</td></tr>
            </table></div>
            <div class="section"><div class="section-title">E. ACCUMULATED INTEREST IN THE FUND</div><table>
                <tr><td>ACCUMULATED INTEREST</td><td>{{ $money($aif['amount']??0) }}</td></tr>
            </table></div>
        </td>
        <td class="col-right">
            <div class="section-title" style="text-align:center;border-radius:9px 9px 0 0;">CONTRIBUTIONS HISTORY</div>
            <table class="history"><thead><tr><th>MONTH</th><th>EMPLOYEE CONTRIBUTIONS</th><th>ADDITIONAL VOLUNTARY CONTRIBUTIONS</th><th>EMPLOYER CONTRIBUTIONS</th></tr></thead><tbody>
                <tr><td>Opening Balance</td><td>{{ $money($history['opening_balance']['employee_contribution']??0) }}</td><td>{{ $money($history['opening_balance']['employee_avc']??0) }}</td><td>{{ $money($history['opening_balance']['employer_contribution']??0) }}</td></tr>
                @foreach($history['months'] as $month=>$amounts)
                <tr><td>{{ \Carbon\Carbon::create(2000,(int)$month,1)->format('F') }}</td><td>{{ $money($amounts['employee_contribution']??0) }}</td><td>{{ $money($amounts['employee_avc']??0) }}</td><td>{{ $money($amounts['employer_contribution']??0) }}</td></tr>
                @endforeach
                <tr><td>BALANCE C/FWD</td><td>{{ $money($history['closing_balance']['employee_contribution']??0) }}</td><td>{{ $money($history['closing_balance']['employee_avc']??0) }}</td><td>{{ $money($history['closing_balance']['employer_contribution']??0) }}</td></tr>
            </tbody></table>
            <div class="notes">
                <p class="note-heading">NOTES</p>
                <p><b>PERSONAL DETAILS:-</b>These are your particulars as indicated on the Notification of Membership form JF.2.</p>
                <p><b>Normal Retirement Date (NRD):-</b>Member's retirement date on attainment of 60 years.</p>
                <p><b>Projected Replacement Ratio:-</b>Shows the level of pension at retirement relative to pensionable emoluments.</p>
                <p>Formula: The Fund's Retirement Accrual Rate X Projected Pensionable Service to NRD (years)</p>
                <p>Example of a replacement ratio for a member who would have contributed for 25 years. (2% Fund's current accrual rate X 25 years = 50% Replacement Ratio)</p>
                <p><b>B. WITHDRAWAL BENEFIT:-</b>This information shows the amounts contributed to the Fund by your employer and yourself since you joined the Fund. On resignation or discharge you will be refunded your accumulated contributions with interest plus a return of the employer’s contributions dependent on length of service (minimum 5 years); i.e. 30% for 5 years service increasing by 10% for each additional year.</p>
                <p><b>C. RETIREMENT BENEFIT:-</b>The estimated pension is based on the assumption that your current pensionable emoluments remain constant until retirement. Subject to any maximum age of retirement laid down by your Local Authority, you may retire at any age between 55 &amp; 70 on a pension payable for seven years certain and thereafter for life. The following formula is presently applied: N/600 X PE; where N = Number of Pensionable Service (months) ; PE (Pensionable Emoluments) is the average annual salary over the last 3 months to member's retirement date.</p>
                <p><b>D. DEATH BENEFIT:-</b>Should you die in service, there is a return of twice your contributions with interest payable to your dependants or nominated beneficiaries. Where applicable spouse’s pension calculated at 25% of member's annual salary at death, children’s pensions up to a maximum of 3 at any one time are also payable. If member leaves a spouse, 3 children - 50% of spouse's pension; 2 children - 37.5% of spouse's pension; 1 child - 25% of spouse's pension. If member does not leave a spouse: 3 children - 100% of spouse's pension; 2 children - 75% of spouse's pension; 1 child - 50% of spouse's pension. If pensionable service amounts to 15 years or more a higher death benefit may be payable based on actuarial advice.</p>
                <p><b>E. ACCUMULATED INTEREST IN THE FUND:-</b>Is payable if pensionable service amounts to 15 years or more and the contributor is less than 55 years. The contributor will be required to purchase a retirement annuity from a registered insurer.</p>
                <p><b>F.</b> Please note that Employers currently owe the Fund substantial pension contribution arrears, a position which might result in the delayed settlement of your pension benefits upon exiting the Fund.</p>
            </div>
            <div class="turnover">[PLEASE TURN OVER]</div>
        </td>
    </tr></table>
</div>
<div class="page legal">
    <div class="masthead"><div class="masthead-title">LOCAL AUTHORITIES PENSION FUND</div><div class="masthead-meta">Benefit Statement as at {{ $statement['statement_date']->format('d/m/Y') }}</div></div>
    <h2>PENSIONS BENEFIT TAX AND LEGISLATIVE PROVISIONS</h2>
    <p>The various benefit payments from the Fund are subject to the following tax and legislative provisions :-</p>
    <h3>On Withdrawal from the Fund</h3>
    <p>The withdrawal amount is subject to tax unless the benefit is transferred to a registered insurance company to purchase an annuity or member elects for a deferred pension. If the benefit is taxable, the Fund only pays the benefit on receipt of a tax directive from the Zimbabwe Revenue Authority (ZIMRA). It should be noted that, where a member elects for an Accumulated Interest In the Fund, the benefit is not payable in cash but utilized to purchase a retirement annuity policy which will provide a pension for the member on attainment of at least age 55 years.</p>
    <h3>In the Event of a Death of a Member</h3>
    <p>Any lump sum payable to the member's dependants is tax free, and</p>
    <p>Any monthly pension payable to the member's spouse below 55 years is taxable as income.</p>
    <h3>On Retiring from the Fund</h3>
    <p>A member may commute up to one-third of his/her pension which is tax free and;</p>
    <p>Any monthly pension for members below 55 years is taxable as income</p>
    <p>Should a member be eligible and make an election for full commutation of his/her pension, the full commutation benefit is taxable as income.</p>
    <p>The Fund will only pay the benefit upon receipt of a tax directive from ZIMRA.</p>
    <p>Your queries regarding this Benefit Statement should be directed to the Pensions Administration Executive at the address</p>
</div>
</body>
</html>
