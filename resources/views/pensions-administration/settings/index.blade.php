@extends('layouts.app')

@section('title', 'Pension Benefit Settings')

@section('page-heading', 'Pension Benefit Settings')


@section('content')


@include(
    'pensions-administration.partials.navigation'
)


<div class="row">


    {{-- =====================================================
         GENERAL BENEFIT RULES
    ====================================================== --}}

    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="
                                avatar-title
                                rounded-circle
                                bg-soft-primary
                                text-primary
                                font-size-20
                            "
                        >
                            <i class="mdi mdi-tune-variant"></i>
                        </span>

                    </div>


                    <div class="flex-grow-1">

                        <h5 class="mb-1">
                            General Benefit Rules
                        </h5>

                        <div class="text-muted">

                            {{
                                number_format(
                                    $counts[
                                        'general_settings'
                                    ]
                                    ?? 0
                                )
                            }}
                            configured rule(s)

                        </div>

                    </div>

                </div>


                <a
                    href="{{
                        route(
                            'pensions-administration.settings.general.index'
                        )
                    }}"
                    class="btn btn-primary btn-sm mt-3"
                >
                    Open Settings
                </a>

            </div>

        </div>

    </div>


    {{-- =====================================================
         WITHDRAWAL SCALE
    ====================================================== --}}

    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="
                                avatar-title
                                rounded-circle
                                bg-soft-primary
                                text-primary
                                font-size-20
                            "
                        >
                            <i class="mdi mdi-format-list-numbered"></i>
                        </span>

                    </div>


                    <div class="flex-grow-1">

                        <h5 class="mb-1">
                            Withdrawal Scales
                        </h5>

                        <div class="text-muted">

                            {{
                                number_format(
                                    $counts[
                                        'withdrawal_scales'
                                    ]
                                    ?? 0
                                )
                            }}
                            configured scale(s)

                        </div>

                    </div>

                </div>


                <a
                    href="{{
                        route(
                            'pensions-administration.settings.withdrawal-scales.index'
                        )
                    }}"
                    class="btn btn-primary btn-sm mt-3"
                >
                    Open Settings
                </a>

            </div>

        </div>

    </div>


    {{-- =====================================================
         ACCUMULATED INTEREST
    ====================================================== --}}

    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <h5>
                    Accumulated Interest Factors
                </h5>

                <p class="text-muted">

                    {{
                        number_format(
                            $counts[
                                'accumulated_interest_factors'
                            ]
                            ?? 0
                        )
                    }}
                    factor(s)

                </p>

                <a
                    href="{{
                        route(
                            'pensions-administration.settings.accumulated-interest-factors.index'
                        )
                    }}"
                    class="btn btn-primary btn-sm"
                >
                    Open Factors
                </a>

            </div>

        </div>

    </div>


    {{-- =====================================================
         COMMUTATION
    ====================================================== --}}

    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <h5>
                    Commutation Factors
                </h5>

                <p class="text-muted">

                    {{
                        number_format(
                            $counts[
                                'commutation_factors'
                            ]
                            ?? 0
                        )
                    }}
                    factor(s)

                </p>

                <a
                    href="{{
                        route(
                            'pensions-administration.settings.commutation-factors.index'
                        )
                    }}"
                    class="btn btn-primary btn-sm"
                >
                    Open Factors
                </a>

            </div>

        </div>

    </div>


    {{-- =====================================================
         RETIREMENT INCREASE
    ====================================================== --}}

    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <h5>
                    Retirement Increase Factors
                </h5>

                <p class="text-muted">

                    {{
                        number_format(
                            $counts[
                                'retirement_increase_factors'
                            ]
                            ?? 0
                        )
                    }}
                    factor(s)

                </p>

                <a
                    href="{{
                        route(
                            'pensions-administration.settings.retirement-increases.index'
                        )
                    }}"
                    class="btn btn-primary btn-sm"
                >
                    Open Factors
                </a>

            </div>

        </div>

    </div>


    {{-- =====================================================
         TAX
    ====================================================== --}}

    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <h5>
                    Tax Tables
                </h5>

                <p class="text-muted">

                    {{
                        number_format(
                            $counts[
                                'tax_tables'
                            ]
                            ?? 0
                        )
                    }}
                    tax table(s)

                </p>

                <a
                    href="{{
                        route(
                            'pensions-administration.settings.tax-tables.index'
                        )
                    }}"
                    class="btn btn-primary btn-sm"
                >
                    Open Tax Tables
                </a>

            </div>

        </div>

    </div>


    {{-- =====================================================
         EXCHANGE RATES
    ====================================================== --}}

    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <h5>
                    Exchange Rates
                </h5>

                <p class="text-muted">

                    {{
                        number_format(
                            $counts[
                                'exchange_rates'
                            ]
                            ?? 0
                        )
                    }}
                    rate(s)

                </p>

                <a
                    href="{{
                        route(
                            'pensions-administration.settings.exchange-rates.index'
                        )
                    }}"
                    class="btn btn-primary btn-sm"
                >
                    Open Exchange Rates
                </a>

            </div>

        </div>

    </div>


</div>


<div class="alert alert-info">

    <strong>
        Controlled Benefit Settings
    </strong>

    <br>

    Pension calculation rules, actuarial factors,
    tax tables and exchange rates are controlled
    settings. Changes must be effective-dated and
    audited.

</div>


@endsection