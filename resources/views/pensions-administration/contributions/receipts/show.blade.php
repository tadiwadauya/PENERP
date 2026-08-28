@extends('layouts.app')

@section('title', 'Contribution Receipt')

@section('page-heading', 'Contribution Receipt Details')


@section('page-actions')

    <a
        href="{{ route('pensions-administration.contributions.receipts.index') }}"
        class="btn btn-light"
    >
        <i class="mdi mdi-arrow-left me-1"></i>
        Contribution Receipts
    </a>

@endsection


@section('content')

    <div class="card">

        <div class="card-header">

            <h5 class="card-title mb-0">

                Receipt #{{ $receipt->id }}

            </h5>

        </div>


        <div class="card-body">

            <div class="row g-4">

                <div class="col-md-6">

                    <div class="text-muted">
                        Employer
                    </div>

                    <strong>

                        {{ $receipt
                            ->employer
                            ?->employer_number }}

                        -

                        {{ $receipt
                            ->employer
                            ?->name }}

                    </strong>

                </div>


                <div class="col-md-3">

                    <div class="text-muted">
                        Receipt Date
                    </div>

                    <strong>

                        {{ $receipt
                            ->receipt_date
                            ->format('d M Y') }}

                    </strong>

                </div>


                <div class="col-md-3">

                    <div class="text-muted">
                        Contribution Month
                    </div>

                    <strong>

                        {{ $receipt
                            ->contribution_period
                            ->format('M Y') }}

                    </strong>

                </div>


                <div class="col-md-3">

                    <div class="text-muted">
                        Due Date
                    </div>

                    <strong>

                        {{ $receipt
                            ->due_date
                            ->format('d M Y') }}

                    </strong>

                </div>


                <div class="col-md-3">

                    <div class="text-muted">
                        Currency
                    </div>

                    <strong>
                        {{ $receipt->currency }}
                    </strong>

                </div>


                <div class="col-md-3">

                    <div class="text-muted">
                        Original Amount
                    </div>

                    <strong>

                        {{ number_format(
                            (float) $receipt->original_amount,
                            2
                        ) }}

                    </strong>

                </div>


                <div class="col-md-3">

                    <div class="text-muted">
                        Exchange Rate
                    </div>

                    <strong>

                        {{ number_format(
                            (float) $receipt->exchange_rate,
                            8
                        ) }}

                    </strong>

                </div>


                <div class="col-md-3">

                    <div class="text-muted">
                        Actual Paid ZWG
                    </div>

                    <strong>

                        {{ number_format(
                            (float) $receipt->amount_zwg,
                            2
                        ) }}

                    </strong>

                </div>


                <div class="col-md-3">

                    <div class="text-muted">
                        Posted
                    </div>

                    <strong>

                        {{ $receipt
                            ->posted_at
                            ?->format('d M Y H:i')
                            ?? '-' }}

                    </strong>

                </div>

            </div>

        </div>

    </div>

@endsection