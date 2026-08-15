@extends('layouts.app')

@section('title', 'Monthly Contributions')

@section('content')

<div class="container-fluid">

    <div class="row">
        <div class="col-12">

            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                    mb-4
                "
            >

                <div>
                    <h4 class="mb-1">
                        Monthly Contributions
                    </h4>

                    <p class="text-muted mb-0">
                        Expected monthly member contribution schedules
                    </p>
                </div>


                @can(
                    'contributions.monthly-imports.create'
                )

                    <a
                        href="{{
                            route(
                                'pensions-administration.contributions.imports.create'
                            )
                        }}"
                        class="btn btn-primary"
                    >

                        <i
                            class="
                                mdi
                                mdi-upload
                                me-1
                            "
                        ></i>

                        Upload Contributions

                    </a>

                @endcan

            </div>

        </div>
    </div>


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


    <div class="card">

        <div class="card-body">

            <form
                method="GET"
                action="{{
                    route(
                        'pensions-administration.contributions.imports.index'
                    )
                }}"
                class="row g-3 mb-4"
            >

                <div class="col-md-5">

                    <label class="form-label">
                        Employer
                    </label>

                    <select
                        name="employer_id"
                        class="form-select"
                    >

                        <option value="">
                            All Employers
                        </option>

                        @foreach(
                            $employers
                            as $employer
                        )

                            <option
                                value="{{
                                    $employer->id
                                }}"
                                @selected(
                                    request(
                                        'employer_id'
                                    )
                                    ==
                                    $employer->id
                                )
                            >

                                {{
                                    $employer->name
                                }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Year
                    </label>

                    <input
                        type="number"
                        name="year"
                        value="{{
                            request('year')
                        }}"
                        class="form-control"
                    >

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        @foreach([
                            'uploaded' => 'Uploaded',
                            'processing' => 'Processing',
                            'awaiting_review' => 'Awaiting Review',
                            'approved' => 'Approved',
                            'posting' => 'Posting',
                            'posted' => 'Posted',
                            'cancelled' => 'Cancelled',
                            'failed' => 'Failed',
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                                @selected(
                                    request('status')
                                    ===
                                    $value
                                )
                            >

                                {{ $label }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div
                    class="
                        col-md-2
                        d-flex
                        align-items-end
                    "
                >

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
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

                            <th>
                                Batch
                            </th>

                            <th>
                                Employer
                            </th>

                            <th>
                                Period
                            </th>

                            <th>
                                File
                            </th>

                            <th>
                                Rows
                            </th>

                            <th>
                                Existing
                            </th>

                            <th>
                                New
                            </th>

                            <th>
                                Nil
                            </th>

                            <th>
                                Errors
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Uploaded
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse(
                            $batches
                            as $batch
                        )

                            <tr>

                                <td>
                                    #{{ $batch->id }}
                                </td>


                                <td>

                                    {{
                                        $batch
                                            ->employer
                                            ?->name
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $batch
                                            ->contributionPeriod
                                            ?->period_label
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $batch
                                            ->original_filename
                                    }}

                                </td>


                                <td>

                                    {{
                                        number_format(
                                            $batch
                                                ->total_rows
                                        )
                                    }}

                                </td>


                                <td>

                                    {{
                                        number_format(
                                            $batch
                                                ->existing_member_rows
                                        )
                                    }}

                                </td>


                                <td>

                                    {{
                                        number_format(
                                            $batch
                                                ->new_member_rows
                                        )
                                    }}

                                </td>


                                <td>

                                    {{
                                        number_format(
                                            $batch
                                                ->nil_contributor_rows
                                        )
                                    }}

                                </td>


                                <td>

                                    <span
                                        class="
                                            badge
                                            {{
                                                $batch->error_rows > 0
                                                    ? 'bg-danger'
                                                    : 'bg-success'
                                            }}
                                        "
                                    >

                                        {{
                                            number_format(
                                                $batch
                                                    ->error_rows
                                            )
                                        }}

                                    </span>

                                </td>


                                <td>

                                    <span
                                        class="
                                            badge
                                            {{
                                                $batch
                                                    ->status_badge
                                            }}
                                        "
                                    >

                                        {{
                                            $batch
                                                ->status_label
                                        }}

                                    </span>

                                </td>


                                <td>

                                    {{
                                        $batch
                                            ->created_at
                                            ?->format(
                                                'd M Y H:i'
                                            )
                                    }}

                                </td>


                                <td>

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.show',
                                                $batch
                                            )
                                        }}"
                                        class="
                                            btn
                                            btn-sm
                                            btn-outline-primary
                                        "
                                    >

                                        View

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="12"
                                    class="
                                        text-center
                                        text-muted
                                        py-4
                                    "
                                >

                                    No contribution imports found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <div class="mt-3">

                {{
                    $batches->links()
                }}

            </div>

        </div>

    </div>

</div>

@endsection