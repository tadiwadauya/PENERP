@extends('layouts.app')

@section('title', 'Organisation Structure')

@section(
    'page-heading',
    'Organisation Structure'
)


@section('page-actions')

    @can(
        'user-management.organisation-units.create'
    )

        <a
            href="{{ route(
                'user-management.organisation-units.create'
            ) }}"
            class="btn btn-success"
        >
            <i class="mdi mdi-sitemap me-1"></i>
            Add Organisation Unit
        </a>

    @endcan

@endsection


@section('content')


@if(session('success'))

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert"
    >
        <i class="mdi mdi-check-circle-outline me-1"></i>

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
        {{ session('error') }}

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>
    </div>

@endif



{{-- Summary --}}
<div class="row">


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-primary text-primary"
                        >
                            <i class="mdi mdi-sitemap font-size-20"></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Total Units
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
                            class="avatar-title rounded-circle bg-soft-primary text-primary"
                        >
                            <i
                                class="mdi mdi-office-building-outline font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Departments
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['departments'] }}
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
                                class="mdi mdi-source-branch font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Sections
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['sections'] }}
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
                            Active Units
                        </p>

                        <h4 class="mb-0">
                            {{ $summary['active'] }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


</div>



<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            LAPF Organisation Structure
        </h4>

        <p class="card-title-desc">
            Departments, sections and offices can be moved within the
            reporting structure without changing system code.
        </p>


        <div class="table-responsive">

            <table
                class="table table-striped table-bordered align-middle mb-0"
            >

                <thead>

                    <tr>

                        <th>
                            Order
                        </th>

                        <th>
                            Code
                        </th>

                        <th>
                            Organisation Unit
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Reports To
                        </th>

                        <th>
                            Dashboard
                        </th>

                        <th>
                            Contact
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
                        $organisationUnits
                        as $organisationUnit
                    )

                        @php

                            $parent =
                                $organisationUnit->parent_id
                                    ? $unitLookup->get(
                                        $organisationUnit->parent_id
                                    )
                                    : null;


                            $employeeCount =
                                (int) (
                                    $userCounts[
                                        $organisationUnit->id
                                    ]
                                    ?? 0
                                );


                            $typeClass =
                                match(
                                    $organisationUnit->unit_type
                                ) {
                                    'office'
                                        => 'warning',

                                    'department'
                                        => 'primary',

                                    'section'
                                        => 'info',

                                    default
                                        => 'secondary',
                                };


                            $typeLabel =
                                ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $organisationUnit->unit_type
                                    )
                                );

                        @endphp


                        <tr>

                            <td>
                                {{ $organisationUnit->display_order }}
                            </td>


                            <td>
                                <strong>
                                    {{ $organisationUnit->code }}
                                </strong>
                            </td>


                            <td>

                                <div class="d-flex align-items-center">

                                    <div class="avatar-sm me-3">

                                        <span
                                            class="avatar-title rounded-circle bg-soft-{{ $typeClass }} text-{{ $typeClass }}"
                                        >

                                            @if(
                                                $organisationUnit->unit_type
                                                === 'office'
                                            )

                                                <i
                                                    class="mdi mdi-account-tie-outline font-size-18"
                                                ></i>

                                            @elseif(
                                                $organisationUnit->unit_type
                                                === 'department'
                                            )

                                                <i
                                                    class="mdi mdi-office-building-outline font-size-18"
                                                ></i>

                                            @else

                                                <i
                                                    class="mdi mdi-source-branch font-size-18"
                                                ></i>

                                            @endif

                                        </span>

                                    </div>


                                    <div>

                                        <h6 class="mb-1">
                                            {{ $organisationUnit->name }}
                                        </h6>


                                        @if(
                                            $organisationUnit->physical_location
                                        )

                                            <span class="text-muted font-size-12">

                                                <i
                                                    class="mdi mdi-map-marker-outline me-1"
                                                ></i>

                                                {{
                                                    $organisationUnit
                                                        ->physical_location
                                                }}

                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            <td>

                                <span
                                    class="badge bg-soft-{{ $typeClass }} text-{{ $typeClass }}"
                                >
                                    {{ $typeLabel }}
                                </span>

                            </td>


                            <td>

                                @if($parent)

                                    {{ $parent->name }}

                                    <br>

                                    <small class="text-muted">
                                        {{ $parent->code }}
                                    </small>

                                @else

                                    <span
                                        class="badge bg-soft-success text-success"
                                    >
                                        Root
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if(
                                    $organisationUnit->dashboard
                                )

                                    {{
                                        $organisationUnit
                                            ->dashboard
                                            ->name
                                    }}

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if($organisationUnit->email)

                                    <div>
                                        <i
                                            class="mdi mdi-email-outline me-1"
                                        ></i>

                                        {{ $organisationUnit->email }}
                                    </div>

                                @endif


                                @if($organisationUnit->telephone)

                                    <div>
                                        <i
                                            class="mdi mdi-phone-outline me-1"
                                        ></i>

                                        {{ $organisationUnit->telephone }}
                                    </div>

                                @endif


                                @if(
                                    !$organisationUnit->email
                                    && !$organisationUnit->telephone
                                )

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>


                            <td>

                                <span
                                    class="badge bg-soft-primary text-primary"
                                >
                                    {{ $employeeCount }}
                                </span>

                            </td>


                            <td>

                                @if($organisationUnit->is_active)

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

                                @can(
                                    'user-management.organisation-units.update'
                                )

                                    <a
                                        href="{{ route(
                                            'user-management.organisation-units.edit',
                                            $organisationUnit
                                        ) }}"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i
                                            class="mdi mdi-pencil-outline"
                                        ></i>
                                    </a>

                                @endcan


                                @can(
                                    'user-management.organisation-units.delete'
                                )

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'user-management.organisation-units.destroy',
                                            $organisationUnit
                                        ) }}"
                                        class="d-inline"
                                        onsubmit="
                                            return confirm(
                                                'Delete this organisation unit?'
                                            );
                                        "
                                    >

                                        @csrf
                                        @method('DELETE')


                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >
                                            <i
                                                class="mdi mdi-delete-outline"
                                            ></i>
                                        </button>

                                    </form>

                                @endcan

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="10"
                                class="text-center py-5"
                            >
                                No organisation units found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection