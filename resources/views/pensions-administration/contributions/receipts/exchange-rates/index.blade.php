@extends('layouts.app')

@section('title', 'USD to ZWG Exchange Rates')

@section('page-heading', 'USD to ZWG Exchange Rates')


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

    @if (session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif


    @if (session('error'))

        <div class="alert alert-danger">
            {{ session('error') }}
        </div>

    @endif


    @if ($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    @can('contributions.exchange-rates.manage')

        <div class="card mb-4">

            <div class="card-header">

                <h5 class="card-title mb-0">
                    Add Daily Rate
                </h5>

            </div>


            <div class="card-body">

                <form
                    method="POST"
                    action="{{ route('pensions-administration.contributions.receipts.exchange-rates.store') }}"
                >

                    @csrf


                    <div class="row g-3 align-items-end">

                        <div class="col-md-3">

                            <label class="form-label">
                                Rate Date
                            </label>

                            <input
                                type="date"
                                name="rate_date"
                                class="form-control"
                                value="{{ old(
                                    'rate_date',
                                    now()->format('Y-m-d')
                                ) }}"
                                required
                            >

                        </div>


                        <div class="col-md-2">

                            <label class="form-label">
                                From
                            </label>

                            <input
                                type="text"
                                value="USD"
                                class="form-control"
                                readonly
                            >

                        </div>


                        <div class="col-md-2">

                            <label class="form-label">
                                To
                            </label>

                            <input
                                type="text"
                                value="ZWG"
                                class="form-control"
                                readonly
                            >

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Rate
                            </label>

                            <input
                                type="number"
                                name="rate"
                                class="form-control"
                                step="0.00000001"
                                min="0.00000001"
                                value="{{ old('rate') }}"
                                required
                            >

                        </div>


                        <div class="col-md-2">

                            <label class="form-label">
                                Source
                            </label>

                            <input
                                type="text"
                                name="source"
                                class="form-control"
                                value="{{ old('source') }}"
                                placeholder="e.g. RBZ"
                            >

                        </div>


                        <div class="col-md-10">

                            <label class="form-label">
                                Notes
                            </label>

                            <textarea
                                name="notes"
                                class="form-control"
                                rows="2"
                            >{{ old('notes') }}</textarea>

                        </div>


                        <div class="col-md-2">

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                <i class="mdi mdi-content-save me-1"></i>
                                Save Rate
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    @endcan


    <div class="card">

        <div class="card-header">

            <h5 class="card-title mb-0">
                Exchange Rate History
            </h5>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">

                    <tr>

                        <th>Date</th>

                        <th>From</th>

                        <th>To</th>

                        <th class="text-end">
                            Rate
                        </th>

                        <th>Source</th>

                        <th>Notes</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse ($rates as $rate)

                        <tr>

                            <td>

                                {{ $rate
                                    ->rate_date
                                    ->format('d M Y') }}

                            </td>


                            <td>
                                {{ $rate->from_currency }}
                            </td>


                            <td>
                                {{ $rate->to_currency }}
                            </td>


                            <td class="text-end">

                                {{ number_format(
                                    (float) $rate->rate,
                                    8
                                ) }}

                            </td>


                            <td>
                                {{ $rate->source ?: '-' }}
                            </td>


                            <td>
                                {{ $rate->notes ?: '-' }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5 text-muted"
                            >

                                No exchange rates configured.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if ($rates->hasPages())

            <div class="card-footer">

                {{ $rates->links() }}

            </div>

        @endif

    </div>

@endsection