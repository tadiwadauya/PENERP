@extends('layouts.app')

@section('title', 'Review Monthly Contributions')

@section('content')

<div class="container-fluid">

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
                Review Monthly Contributions
            </h4>

            <p class="text-muted mb-0">

                {{
                    $batch
                        ->employer
                        ?->name
                }}

                —

                {{
                    $batch
                        ->contributionPeriod
                        ?->period_label
                }}

            </p>

        </div>


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


    <div class="row">

        <div class="col-md-3">

            <div class="card">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Existing Members
                    </p>

                    <h3>

                        {{
                            number_format(
                                $batch
                                    ->existing_member_rows
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Proposed New Members
                    </p>

                    <h3 class="text-info">

                        {{
                            number_format(
                                $batch
                                    ->new_member_rows
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Nil Contributors
                    </p>

                    <h3 class="text-warning">

                        {{
                            number_format(
                                $batch
                                    ->nil_contributor_rows
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Errors
                    </p>

                    <h3 class="text-danger">

                        {{
                            number_format(
                                $batch
                                    ->error_rows
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>

    </div>


    <div class="card">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3 mb-4"
            >

                <div class="col-md-4">

                    <input
                        type="text"
                        name="search"
                        value="{{
                            request(
                                'search'
                            )
                        }}"
                        class="form-control"
                        placeholder="
                            Member no, staff no,
                            National ID, surname...
                        "
                    >

                </div>


                <div class="col-md-3">

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Validation Results
                        </option>

                        <option
                            value="valid"
                            @selected(
                                request(
                                    'status'
                                )
                                ===
                                'valid'
                            )
                        >
                            Valid
                        </option>

                        <option
                            value="warning"
                            @selected(
                                request(
                                    'status'
                                )
                                ===
                                'warning'
                            )
                        >
                            Warning
                        </option>

                        <option
                            value="error"
                            @selected(
                                request(
                                    'status'
                                )
                                ===
                                'error'
                            )
                        >
                            Error
                        </option>

                    </select>

                </div>


                <div class="col-md-3">

                    <select
                        name="member_type"
                        class="form-select"
                    >

                        <option value="">
                            All Member Types
                        </option>

                        <option
                            value="existing"
                            @selected(
                                request(
                                    'member_type'
                                )
                                ===
                                'existing'
                            )
                        >
                            Existing Members
                        </option>

                        <option
                            value="new"
                            @selected(
                                request(
                                    'member_type'
                                )
                                ===
                                'new'
                            )
                        >
                            Proposed New Members
                        </option>

                    </select>

                </div>


                <div class="col-md-2">

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
                        table-nowrap
                        align-middle
                    "
                >

                    <thead>

                        <tr>

                            <th>
                                Row
                            </th>

                            <th>
                                PenAd / Pension Ref
                            </th>

                            <th>
                                Staff No.
                            </th>

                            <th>
                                National ID
                            </th>

                            <th>
                                Member
                            </th>

                            <th>
                                Match
                            </th>

                            <th class="text-end">
                                USD Employee
                            </th>

                            <th class="text-end">
                                USD Employer
                            </th>

                            <th class="text-end">
                                USD AVC
                            </th>

                            <th>
                                Validation
                            </th>

                            <th>
                                Messages
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse(
                            $rows
                            as $row
                        )

                            @php

                                $data =
                                    $row
                                        ->normalized_data
                                    ?? [];

                            @endphp


                            <tr>

                                <td>

                                    {{
                                        $row
                                            ->row_number
                                    }}

                                </td>


                                <td>

                                    {{
                                        $data[
                                            'pension_reference_number'
                                        ]
                                        ??
                                        $data[
                                            'penerp_member_number'
                                        ]
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $data[
                                            'staff_number'
                                        ]
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $data[
                                            'national_id'
                                        ]
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    @if(
                                        $row
                                            ->matchedMember
                                    )

                                        <strong>

                                            {{
                                                $row
                                                    ->matchedMember
                                                    ->surname
                                            }}

                                            {{
                                                $row
                                                    ->matchedMember
                                                    ->first_names
                                            }}

                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            {{
                                                $row
                                                    ->matchedMember
                                                    ->member_number
                                            }}

                                        </small>

                                    @else

                                        <strong>

                                            {{
                                                $data[
                                                    'surname'
                                                ]
                                                ??
                                                ''
                                            }}

                                            {{
                                                $data[
                                                    'first_names'
                                                ]
                                                ??
                                                ''
                                            }}

                                        </strong>

                                    @endif

                                </td>


                                <td>

                                    @if(
                                        $row
                                            ->is_new_member
                                    )

                                        <span
                                            class="
                                                badge
                                                bg-info
                                            "
                                        >
                                            New Member
                                        </span>

                                    @elseif(
                                        $row
                                            ->match_type
                                        ===
                                        'conflict'
                                    )

                                        <span
                                            class="
                                                badge
                                                bg-danger
                                            "
                                        >
                                            Conflict
                                        </span>

                                    @else

                                        <span
                                            class="
                                                badge
                                                bg-secondary
                                            "
                                        >

                                            {{
                                                $row
                                                    ->match_label
                                            }}

                                        </span>

                                    @endif

                                </td>


                                <td class="text-end">

                                    {{
                                        number_format(
                                            (
                                                float
                                            ) (
                                                $data[
                                                    'usd_employee_contribution'
                                                ]
                                                ??
                                                0
                                            ),
                                            2
                                        )
                                    }}

                                </td>


                                <td class="text-end">

                                    {{
                                        number_format(
                                            (
                                                float
                                            ) (
                                                $data[
                                                    'usd_employer_contribution'
                                                ]
                                                ??
                                                0
                                            ),
                                            2
                                        )
                                    }}

                                </td>


                                <td class="text-end">

                                    {{
                                        number_format(
                                            (
                                                float
                                            ) (
                                                $data[
                                                    'usd_employee_avc'
                                                ]
                                                ??
                                                0
                                            )
                                            +
                                            (
                                                float
                                            ) (
                                                $data[
                                                    'usd_employer_avc'
                                                ]
                                                ??
                                                0
                                            ),
                                            2
                                        )
                                    }}

                                </td>


                                <td>

                                    <span
                                        class="
                                            badge
                                            {{
                                                $row
                                                    ->validation_badge
                                            }}
                                        "
                                    >

                                        {{
                                            $row
                                                ->validation_label
                                        }}

                                    </span>

                                </td>


                                <td>

                                    @foreach(
                                        $row
                                            ->error_messages
                                        ??
                                        []
                                        as $message
                                    )

                                        <div class="text-danger small">

                                            <i
                                                class="
                                                    mdi
                                                    mdi-alert-circle
                                                    me-1
                                                "
                                            ></i>

                                            {{ $message }}

                                        </div>

                                    @endforeach


                                    @foreach(
                                        $row
                                            ->warning_messages
                                        ??
                                        []
                                        as $message
                                    )

                                        <div class="text-warning small">

                                            <i
                                                class="
                                                    mdi
                                                    mdi-alert
                                                    me-1
                                                "
                                            ></i>

                                            {{ $message }}

                                        </div>

                                    @endforeach

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="11"
                                    class="
                                        text-center
                                        text-muted
                                        py-4
                                    "
                                >

                                    No contribution rows match
                                    the selected filters.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <div class="mt-3">

                {{
                    $rows->links()
                }}

            </div>

        </div>

    </div>


    {{-- =========================================================
         NIL CONTRIBUTORS
    ========================================================= --}}

    <div class="card">

        <div class="card-body">

            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                    mb-3
                "
            >

                <div>

                    <h5 class="mb-1">
                        Nil Contributors
                    </h5>

                    <p class="text-muted mb-0">
                        Active members under this employer who did not
                        appear on this month's contribution schedule.
                    </p>

                </div>


                <span
                    class="
                        badge
                        bg-warning
                        text-dark
                        font-size-14
                    "
                >

                    {{
                        number_format(
                            $batch
                                ->nil_contributor_rows
                        )
                    }}

                </span>

            </div>


            <div class="table-responsive">

                <table
                    class="
                        table
                        table-bordered
                        table-hover
                    "
                >

                    <thead>

                        <tr>

                            <th>
                                PENERP No.
                            </th>

                            <th>
                                PenAd No.
                            </th>

                            <th>
                                Member
                            </th>

                            <th>
                                Staff No.
                            </th>

                            <th>
                                Employer
                            </th>

                            <th>
                                Monthly Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse(
                            $nilContributors
                            as $status
                        )

                            <tr>

                                <td>

                                    {{
                                        $status
                                            ->member
                                            ?->member_number
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $status
                                            ->member
                                            ?->penad_member_number
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    <strong>

                                        {{
                                            $status
                                                ->member
                                                ?->surname
                                        }}

                                        {{
                                            $status
                                                ->member
                                                ?->first_names
                                        }}

                                    </strong>

                                </td>


                                <td>

                                    {{
                                        $status
                                            ->member
                                            ?->currentEmployment
                                            ?->staff_number
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    {{
                                        $status
                                            ->member
                                            ?->currentEmployment
                                            ?->employer
                                            ?->name
                                        ??
                                        '-'
                                    }}

                                </td>


                                <td>

                                    <span
                                        class="
                                            badge
                                            bg-warning
                                            text-dark
                                        "
                                    >
                                        Nil Contributor
                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="
                                        text-center
                                        text-muted
                                        py-4
                                    "
                                >

                                    No nil contributors were identified
                                    for this period.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <div class="mt-3">

                {{
                    $nilContributors
                        ->appends(
                            request()
                                ->except(
                                    'nil_page'
                                )
                        )
                        ->links()
                }}

            </div>

        </div>

    </div>

</div>

@endsection