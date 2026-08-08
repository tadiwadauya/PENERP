@extends('layouts.app')

@section('title', 'Job Titles')

@section(
    'page-heading',
    'Job Titles'
)


@section('page-actions')

    @can('user-management.job-titles.create')

        <a
            href="{{ route(
                'user-management.job-titles.create'
            ) }}"
            class="btn btn-success"
        >

            <i
                class="mdi mdi-briefcase-plus-outline me-1"
            ></i>

            Add Job Title

        </a>

    @endcan

@endsection


@section('content')


{{-- =========================================================
     MESSAGES
========================================================= --}}

@if(session('success'))

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert"
    >

        <i
            class="mdi mdi-check-circle-outline me-1"
        ></i>

        {{ session('success') }}

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

@endif


@if(session('error'))

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <i
            class="mdi mdi-alert-circle-outline me-1"
        ></i>

        {{ session('error') }}

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

@endif



{{-- =========================================================
     SUMMARY
========================================================= --}}

<div class="row">


    {{-- Total Job Titles --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-primary text-primary"
                        >
                            <i
                                class="mdi mdi-briefcase-outline font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Total Job Titles
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['total'] }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- Active --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-success text-success"
                        >
                            <i
                                class="mdi mdi-check-circle-outline font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Active
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['active'] }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- Inactive --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-warning text-warning"
                        >
                            <i
                                class="mdi mdi-briefcase-remove-outline font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Inactive
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['inactive'] }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- Employees --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-info text-info"
                        >
                            <i
                                class="mdi mdi-account-group-outline font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Employees Assigned
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['employees'] }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


</div>



{{-- =========================================================
     JOB TITLES
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">


                <div class="mb-4">

                    <h4 class="header-title">
                        LAPF Job Titles
                    </h4>


                    <p class="card-title-desc mb-0">

                        Maintain employee job titles independently
                        from employee grades, reporting structures,
                        roles and system permissions.

                    </p>

                </div>


                <div class="table-responsive">

                    <table
                        class="table table-striped table-bordered align-middle mb-0"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Code
                                </th>

                                <th>
                                    Job Title
                                </th>

                                <th>
                                    Description
                                </th>

                                <th>
                                    Employees
                                </th>

                                <th>
                                    Status
                                </th>

                                <th
                                    style="width:140px;"
                                >
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse(
                                $jobTitles
                                as $jobTitle
                            )

                                @php

                                    $employeeCount =
                                        (int) (
                                            $employeeCounts[
                                                $jobTitle->id
                                            ]
                                            ?? 0
                                        );

                                @endphp


                                <tr>


                                    {{-- Code --}}
                                    <td>

                                        <span
                                            class="badge bg-soft-primary text-primary font-size-12"
                                        >
                                            {{ $jobTitle->code }}
                                        </span>

                                    </td>



                                    {{-- Job Title --}}
                                    <td>

                                        <div
                                            class="d-flex align-items-center"
                                        >

                                            <div
                                                class="avatar-sm me-3"
                                            >

                                                <span
                                                    class="avatar-title rounded-circle bg-soft-primary text-primary"
                                                >

                                                    <i
                                                        class="mdi mdi-briefcase-outline font-size-18"
                                                    ></i>

                                                </span>

                                            </div>


                                            <div>

                                                <h6 class="mb-0">

                                                    {{ $jobTitle->name }}

                                                </h6>

                                            </div>

                                        </div>

                                    </td>



                                    {{-- Description --}}
                                    <td>

                                        @if($jobTitle->description)

                                            {{
                                                \Illuminate\Support\Str::limit(
                                                    $jobTitle->description,
                                                    100
                                                )
                                            }}

                                        @else

                                            <span class="text-muted">
                                                -
                                            </span>

                                        @endif

                                    </td>



                                    {{-- Employees --}}
                                    <td>

                                        <span
                                            class="badge bg-soft-info text-info"
                                        >

                                            {{ $employeeCount }}

                                            employee(s)

                                        </span>

                                    </td>



                                    {{-- Status --}}
                                    <td>

                                        @if($jobTitle->is_active)

                                            <span
                                                class="badge bg-success"
                                            >
                                                Active
                                            </span>

                                        @else

                                            <span
                                                class="badge bg-secondary"
                                            >
                                                Inactive
                                            </span>

                                        @endif

                                    </td>



                                    {{-- Actions --}}
                                    <td>

                                        <div
                                            class="d-flex gap-2"
                                        >

                                            @can(
                                                'user-management.job-titles.update'
                                            )

                                                <a
                                                    href="{{ route(
                                                        'user-management.job-titles.edit',
                                                        $jobTitle
                                                    ) }}"
                                                    class="btn btn-sm btn-primary"
                                                    title="Edit Job Title"
                                                >

                                                    <i
                                                        class="mdi mdi-pencil-outline"
                                                    ></i>

                                                </a>

                                            @endcan


                                            @can(
                                                'user-management.job-titles.delete'
                                            )

                                                @if($employeeCount === 0)

                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'user-management.job-titles.destroy',
                                                            $jobTitle
                                                        ) }}"
                                                        onsubmit="
                                                            return confirm(
                                                                'Are you sure you want to delete this job title?'
                                                            );
                                                        "
                                                    >

                                                        @csrf
                                                        @method('DELETE')


                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-danger"
                                                            title="Delete Job Title"
                                                        >

                                                            <i
                                                                class="mdi mdi-delete-outline"
                                                            ></i>

                                                        </button>

                                                    </form>

                                                @else

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-light"
                                                        disabled
                                                        title="This job title is assigned to employees"
                                                    >

                                                        <i
                                                            class="mdi mdi-delete-outline"
                                                        ></i>

                                                    </button>

                                                @endif

                                            @endcan

                                        </div>

                                    </td>


                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="6"
                                        class="text-center py-5"
                                    >

                                        <div
                                            class="avatar-md mx-auto mb-3"
                                        >

                                            <span
                                                class="avatar-title rounded-circle bg-soft-secondary text-secondary"
                                            >

                                                <i
                                                    class="mdi mdi-briefcase-off-outline font-size-24"
                                                ></i>

                                            </span>

                                        </div>


                                        <h5>
                                            No Job Titles Found
                                        </h5>


                                        <p
                                            class="text-muted mb-0"
                                        >
                                            No LAPF job titles have
                                            been configured yet.
                                        </p>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection