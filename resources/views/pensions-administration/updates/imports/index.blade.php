@extends('layouts.app')

@section('title', 'Membership Imports')

@section('page-heading', 'Membership Imports')


@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.imports.create'
        ) }}"
        class="btn btn-primary"
    >
        <i class="mdi mdi-file-upload-outline me-1"></i>

        Upload Excel File
    </a>

@endsection


@section('content')


@include(
    'pensions-administration.partials.navigation'
)


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

        <h4 class="header-title">
            Static Membership Import Batches
        </h4>


        <p class="text-muted">
            Uploaded membership files are validated and reviewed
            before they are committed to the live membership register.
        </p>


        <div class="table-responsive">

            <table
                class="
                    table
                    table-bordered
                    table-striped
                    table-hover
                    align-middle
                "
            >

                <thead>

                    <tr>

                        <th>
                            Date
                        </th>

                        <th>
                            Batch
                        </th>

                        <th>
                            File
                        </th>

                        <th>
                            Employer
                        </th>

                        <th>
                            Rows
                        </th>

                        <th>
                            Progress
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Uploaded By
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

                                {{
                                    $batch
                                        ->created_at
                                        ->format(
                                            'd M Y H:i'
                                        )
                                }}

                            </td>


                            <td>

                                <small>

                                    {{
                                        $batch
                                            ->import_uuid
                                    }}

                                </small>

                            </td>


                            <td>

                                <strong>
                                    {{
                                        $batch
                                            ->original_filename
                                    }}
                                </strong>

                                <br>

                                <small
                                    class="text-muted"
                                >

                                    {{
                                        number_format(
                                            ($batch->file_size ?? 0)
                                            / 1024,
                                            1
                                        )
                                    }}
                                    KB

                                </small>

                            </td>


                            <td>

                                {{
                                    $batch
                                        ->employer
                                        ?->name
                                    ?? 'Multiple / Auto Detect'
                                }}

                            </td>


                            <td>

                                {{
                                    number_format(
                                        $batch
                                            ->processed_rows
                                    )
                                }}

                                /

                                {{
                                    number_format(
                                        $batch
                                            ->total_rows
                                    )
                                }}

                            </td>


                            <td style="min-width:160px;">

                                <div
                                    class="progress"
                                    style="height:8px;"
                                >

                                    <div
                                        class="
                                            progress-bar
                                            progress-bar-striped
                                            progress-bar-animated
                                        "
                                        role="progressbar"
                                        style="
                                            width:
                                            {{
                                                $batch
                                                    ->progress_percentage
                                            }}%
                                        "
                                    ></div>

                                </div>


                                <small
                                    class="text-muted"
                                >

                                    {{
                                        number_format(
                                            (float)
                                            $batch
                                                ->progress_percentage,
                                            1
                                        )
                                    }}%

                                </small>

                            </td>


                            <td>

                                @php
                                    $statusClass =
                                        match(
                                            $batch->status
                                        ) {
                                            'completed'
                                                => 'bg-success',

                                            'failed'
                                                => 'bg-danger',

                                            'awaiting_review'
                                                => 'bg-warning',

                                            'processing',
                                            'validating',
                                            'duplicate_checking',
                                            'importing'
                                                => 'bg-info',

                                            'cancelled'
                                                => 'bg-secondary',

                                            default
                                                => 'bg-primary',
                                        };
                                @endphp


                                <span
                                    class="
                                        badge
                                        {{ $statusClass }}
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
                                        ->uploadedBy
                                        ?->full_name
                                    ?? '-'
                                }}

                            </td>


                            <td>

                                <a
                                    href="{{ route(
                                        'pensions-administration.updates.imports.show',
                                        $batch
                                    ) }}"
                                    class="btn btn-sm btn-primary"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="
                                    text-center
                                    text-muted
                                    py-4
                                "
                            >

                                No membership imports have
                                been uploaded yet.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-3">

            {{ $batches->links() }}

        </div>

    </div>

</div>


@endsection