@extends('layouts.app')

@section('title', 'Grades')

@section(
    'page-heading',
    'Grades'
)


@section('page-actions')

    @can('user-management.grades.create')

        <a
            href="{{ route(
                'user-management.grades.create'
            ) }}"
            class="btn btn-success"
        >

            <i class="mdi mdi-layers-plus me-1"></i>

            Add Grade

        </a>

    @endcan

@endsection


@section('content')


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


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-primary text-primary"
                        >
                            <i class="mdi mdi-layers-outline font-size-20"></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Total Grades
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['total'] }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>



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
                            Active Grades
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['active'] }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-warning text-warning"
                        >
                            <i
                                class="mdi mdi-close-circle-outline font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Inactive Grades
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['inactive'] }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>



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
     GRADES TABLE
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <div class="mb-4">

                    <h4 class="header-title">
                        LAPF Grade Structure
                    </h4>

                    <p class="card-title-desc mb-0">

                        Grades are maintained independently from
                        job titles and positions. An employee's
                        grade is assigned individually.

                    </p>

                </div>


                <div class="table-responsive">

                    <table
                        class="table table-striped table-bordered align-middle mb-0"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Rank
                                </th>

                                <th>
                                    Code
                                </th>

                                <th>
                                    Grade
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

                                <th style="width:140px;">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse(
                                $grades
                                as $grade
                            )

                                @php

                                    $employeeCount =
                                        (int) (
                                            $employeeCounts[
                                                $grade->id
                                            ]
                                            ?? 0
                                        );

                                @endphp


                                <tr>


                                    <td>

                                        <span
                                            class="badge bg-soft-primary text-primary font-size-13"
                                        >

                                            {{ $grade->rank_order }}

                                        </span>

                                    </td>



                                    <td>

                                        <strong>
                                            {{ $grade->code }}
                                        </strong>

                                    </td>



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
                                                        class="mdi mdi-layers-outline font-size-18"
                                                    ></i>

                                                </span>

                                            </div>


                                            <div>

                                                <h6 class="mb-0">
                                                    {{ $grade->name }}
                                                </h6>

                                            </div>

                                        </div>

                                    </td>



                                    <td>

                                        @if($grade->description)

                                            {{
                                                \Illuminate\Support\Str::limit(
                                                    $grade->description,
                                                    100
                                                )
                                            }}

                                        @else

                                            <span class="text-muted">
                                                -
                                            </span>

                                        @endif

                                    </td>



                                    <td>

                                        <span
                                            class="badge bg-soft-info text-info"
                                        >

                                            {{ $employeeCount }}

                                            employee(s)

                                        </span>

                                    </td>



                                    <td>

                                        @if($grade->is_active)

                                            <span class="badge bg-success">
                                                Active
                                            </span>

                                        @else

                                            <span class="badge bg-secondary">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>



                                    <td>

                                        <div class="d-flex gap-2">

                                            @can(
                                                'user-management.grades.update'
                                            )

                                                <a
                                                    href="{{ route(
                                                        'user-management.grades.edit',
                                                        $grade
                                                    ) }}"
                                                    class="btn btn-sm btn-primary"
                                                    title="Edit Grade"
                                                >

                                                    <i
                                                        class="mdi mdi-pencil-outline"
                                                    ></i>

                                                </a>

                                            @endcan


                                            @can(
                                                'user-management.grades.delete'
                                            )

                                                @if($employeeCount === 0)

                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'user-management.grades.destroy',
                                                            $grade
                                                        ) }}"
                                                        onsubmit="
                                                            return confirm(
                                                                'Are you sure you want to delete this grade?'
                                                            );
                                                        "
                                                    >

                                                        @csrf
                                                        @method('DELETE')


                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-danger"
                                                            title="Delete Grade"
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
                                                        title="This grade is assigned to employees"
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
                                        colspan="7"
                                        class="text-center py-5"
                                    >

                                        <div
                                            class="avatar-md mx-auto mb-3"
                                        >

                                            <span
                                                class="avatar-title rounded-circle bg-soft-secondary text-secondary"
                                            >

                                                <i
                                                    class="mdi mdi-layers-off-outline font-size-24"
                                                ></i>

                                            </span>

                                        </div>


                                        <h5>
                                            No Grades Found
                                        </h5>


                                        <p class="text-muted mb-0">

                                            The LAPF grade structure has
                                            not yet been configured.

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