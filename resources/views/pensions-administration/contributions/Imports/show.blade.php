@extends('layouts.app')

@section('title', 'Contribution Import')

@section('content')

<div class="container-fluid">

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
                Monthly Contribution Import
            </h4>

            <p class="text-muted mb-0">

                Batch #{{ $batch->id }}

                —

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


        <div>

            <a
                href="{{
                    route(
                        'pensions-administration.contributions.imports.index'
                    )
                }}"
                class="btn btn-light"
            >

                Back

            </a>


            @if(
                $batch->status
                ===
                'awaiting_review'
            )

                <a
                    href="{{
                        route(
                            'pensions-administration.contributions.imports.review',
                            $batch
                        )
                    }}"
                    class="btn btn-primary"
                >

                    Review Contributions

                </a>

            @endif

        </div>

    </div>


    <div class="row">

        @foreach([
            [
                'label' => 'Schedule Rows',
                'value' => $batch->total_rows,
            ],
            [
                'label' => 'Existing Members',
                'value' => $batch->existing_member_rows,
            ],
            [
                'label' => 'New Members',
                'value' => $batch->new_member_rows,
            ],
            [
                'label' => 'Nil Contributors',
                'value' => $batch->nil_contributor_rows,
            ],
        ] as $stat)

            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <p class="text-muted mb-1">

                            {{
                                $stat[
                                    'label'
                                ]
                            }}

                        </p>

                        <h3 class="mb-0">

                            {{
                                number_format(
                                    $stat[
                                        'value'
                                    ]
                                )
                            }}

                        </h3>

                    </div>

                </div>

            </div>

        @endforeach

    </div>


    <div class="row">

        <div class="col-md-4">

            <div class="card">

                <div class="card-body text-center">

                    <span class="badge bg-success mb-2">
                        Valid
                    </span>

                    <h3>

                        {{
                            number_format(
                                $batch
                                    ->valid_rows
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card">

                <div class="card-body text-center">

                    <span
                        class="
                            badge
                            bg-warning
                            text-dark
                            mb-2
                        "
                    >
                        Warnings
                    </span>

                    <h3>

                        {{
                            number_format(
                                $batch
                                    ->warning_rows
                            )
                        }}

                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card">

                <div class="card-body text-center">

                    <span class="badge bg-danger mb-2">
                        Errors
                    </span>

                    <h3>

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


    @if(
        in_array(
            $batch->status,
            [
                'uploaded',
                'processing',
            ],
            true
        )
    )

        <div class="card">

            <div class="card-body">

                <h5>
                    Validating Contribution Schedule
                </h5>


                <div class="progress mt-3">

                    <div
                        id="progressBar"
                        class="
                            progress-bar
                            progress-bar-striped
                            progress-bar-animated
                        "
                        role="progressbar"
                        style="
                            width:
                            {{
                                (float)
                                $batch
                                    ->progress_percentage
                            }}%
                        "
                    >

                        {{
                            number_format(
                                (float)
                                $batch
                                    ->progress_percentage,
                                0
                            )
                        }}%

                    </div>

                </div>


                <p
                    class="
                        text-muted
                        mt-2
                        mb-0
                    "
                    id="progressText"
                >

                    {{
                        number_format(
                            $batch
                                ->processed_rows
                        )
                    }}

                    rows processed.

                </p>

            </div>

        </div>

    @endif


    @if(
        $batch->status
        ===
        'failed'
    )

        <div class="alert alert-danger">

            <strong>
                Validation Failed:
            </strong>

            {{
                $batch
                    ->failure_reason
            }}

        </div>

    @endif


    <div class="card">

        <div class="card-body">

            <h5 class="card-title mb-3">
                USD Totals
            </h5>


            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>
                                Basic Pay
                            </th>

                            <th>
                                Employee
                            </th>

                            <th>
                                Employer
                            </th>

                            <th>
                                Employee AVC
                            </th>

                            <th>
                                Employer AVC
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>

                                {{
                                    number_format(
                                        $batch
                                            ->usd_basic_pay_total,
                                        2
                                    )
                                }}

                            </td>

                            <td>

                                {{
                                    number_format(
                                        $batch
                                            ->usd_employee_contribution_total,
                                        2
                                    )
                                }}

                            </td>

                            <td>

                                {{
                                    number_format(
                                        $batch
                                            ->usd_employer_contribution_total,
                                        2
                                    )
                                }}

                            </td>

                            <td>

                                {{
                                    number_format(
                                        $batch
                                            ->usd_employee_avc_total,
                                        2
                                    )
                                }}

                            </td>

                            <td>

                                {{
                                    number_format(
                                        $batch
                                            ->usd_employer_avc_total,
                                        2
                                    )
                                }}

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    @can(
        'contributions.monthly-imports.delete'
    )

        @if(
            in_array(
                $batch->status,
                [
                    'uploaded',
                    'awaiting_review',
                    'failed',
                ],
                true
            )
        )

            <form
                method="POST"
                action="{{
                    route(
                        'pensions-administration.contributions.imports.destroy',
                        $batch
                    )
                }}"
                onsubmit="
                    return confirm(
                        'Cancel this contribution import?'
                    );
                "
            >

                @csrf
                @method('DELETE')


                <button
                    type="submit"
                    class="btn btn-outline-danger"
                >

                    Cancel Import

                </button>

            </form>

        @endif

    @endcan

</div>


@if(
    in_array(
        $batch->status,
        [
            'uploaded',
            'processing',
        ],
        true
    )
)

<script>

(function () {

    const statusUrl =
        @json(
            route(
                'pensions-administration.contributions.imports.status',
                $batch
            )
        );


    const timer =
        setInterval(
            async function () {

                try {

                    const response =
                        await fetch(
                            statusUrl,
                            {
                                headers: {
                                    'Accept':
                                        'application/json'
                                }
                            }
                        );


                    if (!response.ok) {
                        return;
                    }


                    const data =
                        await response.json();


                    const progress =
                        Number(
                            data
                                .progress_percentage
                            ||
                            0
                        );


                    const progressBar =
                        document.getElementById(
                            'progressBar'
                        );


                    const progressText =
                        document.getElementById(
                            'progressText'
                        );


                    if (progressBar) {

                        progressBar.style.width =
                            progress
                            +
                            '%';

                        progressBar.textContent =
                            Math.round(
                                progress
                            )
                            +
                            '%';

                    }


                    if (progressText) {

                        progressText.textContent =
                            Number(
                                data
                                    .processed_rows
                                ||
                                0
                            )
                            +
                            ' rows processed.';

                    }


                    if (
                        [
                            'awaiting_review',
                            'failed',
                            'cancelled',
                        ]
                            .includes(
                                data.status
                            )
                    ) {

                        clearInterval(
                            timer
                        );


                        window.location.reload();

                    }

                } catch (error) {

                    console.error(
                        error
                    );

                }

            },
            2000
        );

})();

</script>

@endif

@endsection