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

            <i
                class="mdi
                       mdi-sitemap
                       me-1"
            ></i>

            Add Organisation Unit

        </a>

    @endcan

@endsection


@section('content')


{{-- =========================================================
     SUMMARY
========================================================= --}}

<div class="row">


    {{-- Total Units --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title
                                   rounded-circle
                                   bg-soft-primary
                                   text-primary"
                        >

                            <i
                                class="mdi
                                       mdi-sitemap
                                       font-size-20"
                            ></i>

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



    {{-- Departments --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title
                                   rounded-circle
                                   bg-soft-success
                                   text-success"
                        >

                            <i
                                class="mdi
                                       mdi-office-building-outline
                                       font-size-20"
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



    {{-- Sections --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title
                                   rounded-circle
                                   bg-soft-info
                                   text-info"
                        >

                            <i
                                class="mdi
                                       mdi-source-branch
                                       font-size-20"
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



    {{-- Active --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title
                                   rounded-circle
                                   bg-soft-warning
                                   text-warning"
                        >

                            <i
                                class="mdi
                                       mdi-check-network-outline
                                       font-size-20"
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



{{-- =========================================================
     MESSAGES
========================================================= --}}

@if(session('success'))

    <div
        class="alert
               alert-success
               alert-dismissible
               fade show"
        role="alert"
    >

        <i
            class="mdi
                   mdi-check-circle-outline
                   me-1"
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
        class="alert
               alert-danger
               alert-dismissible
               fade show"
        role="alert"
    >

        <i
            class="mdi
                   mdi-alert-circle-outline
                   me-1"
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
     ORGANISATION STRUCTURE TABLE
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">


                <div class="mb-4">

                    <h4 class="header-title">
                        LAPF Organisation Structure
                    </h4>


                    <p
                        class="card-title-desc
                               mb-0"
                    >

                        Manage departments, sections and reporting
                        relationships. Changing the parent unit changes
                        the reporting structure without changing application code.

                    </p>

                </div>


                <div class="table-responsive">

                    <table
                        class="table
                               table-striped
                               table-bordered
                               align-middle
                               mb-0"
                    >

                        <thead>

                            <tr>

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
                                    Employees
                                </th>

                                <th>
                                    Child Units
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


                                    $children =
                                        (int) (
                                            $childCounts[
                                                $organisationUnit->id
                                            ]
                                            ?? 0
                                        );


                                    $typeClass =
                                        match(
                                            $organisationUnit->type
                                        ) {
                                            'principal_office'
                                                => 'warning',

                                            'department'
                                                => 'primary',

                                            'section'
                                                => 'info',

                                            default
                                                => 'secondary',
                                        };


                                    $typeLabel =
                                        match(
                                            $organisationUnit->type
                                        ) {
                                            'principal_office'
                                                => 'Principal Office',

                                            'department'
                                                => 'Department',

                                            'section'
                                                => 'Section',

                                            default
                                                => ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $organisationUnit->type
                                                    )
                                                ),
                                        };

                                @endphp


                                <tr>


                                    {{-- Code --}}
                                    <td>

                                        <strong>
                                            {{ $organisationUnit->code }}
                                        </strong>

                                    </td>



                                    {{-- Name --}}
                                    <td>

                                        <div
                                            class="d-flex
                                                   align-items-center"
                                        >

                                            <div
                                                class="avatar-sm
                                                       me-3"
                                            >

                                                <span
                                                    class="avatar-title
                                                           rounded-circle
                                                           bg-soft-{{ $typeClass }}
                                                           text-{{ $typeClass }}"
                                                >

                                                    @if(
                                                        $organisationUnit->type
                                                        === 'principal_office'
                                                    )

                                                        <i
                                                            class="mdi
                                                                   mdi-account-tie-outline
                                                                   font-size-18"
                                                        ></i>

                                                    @elseif(
                                                        $organisationUnit->type
                                                        === 'department'
                                                    )

                                                        <i
                                                            class="mdi
                                                                   mdi-office-building-outline
                                                                   font-size-18"
                                                        ></i>

                                                    @else

                                                        <i
                                                            class="mdi
                                                                   mdi-source-branch
                                                                   font-size-18"
                                                        ></i>

                                                    @endif

                                                </span>

                                            </div>


                                            <div>

                                                <h6 class="mb-1">

                                                    {{ $organisationUnit->name }}

                                                </h6>


                                                @if(
                                                    $organisationUnit->description
                                                )

                                                    <span
                                                        class="text-muted
                                                               font-size-12"
                                                    >

                                                        {{
                                                            \Illuminate\Support\Str::limit(
                                                                $organisationUnit->description,
                                                                80
                                                            )
                                                        }}

                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    </td>



                                    {{-- Type --}}
                                    <td>

                                        <span
                                            class="badge
                                                   bg-soft-{{ $typeClass }}
                                                   text-{{ $typeClass }}"
                                        >

                                            {{ $typeLabel }}

                                        </span>

                                    </td>



                                    {{-- Parent --}}
                                    <td>

                                        @if($parent)

                                            <span>
                                                {{ $parent->name }}
                                            </span>

                                            <br>

                                            <small class="text-muted">
                                                {{ $parent->code }}
                                            </small>

                                        @else

                                            <span
                                                class="badge
                                                       bg-soft-success
                                                       text-success"
                                            >
                                                Root
                                            </span>

                                        @endif

                                    </td>



                                    {{-- Employees --}}
                                    <td>

                                        <span
                                            class="badge
                                                   bg-soft-primary
                                                   text-primary"
                                        >
                                            {{ $employeeCount }}
                                        </span>

                                    </td>



                                    {{-- Children --}}
                                    <td>

                                        <span
                                            class="badge
                                                   bg-soft-info
                                                   text-info"
                                        >
                                            {{ $children }}
                                        </span>

                                    </td>



                                    {{-- Status --}}
                                    <td>

                                        @if(
                                            $organisationUnit->is_active
                                        )

                                            <span
                                                class="badge
                                                       bg-success"
                                            >
                                                Active
                                            </span>

                                        @else

                                            <span
                                                class="badge
                                                       bg-secondary"
                                            >
                                                Inactive
                                            </span>

                                        @endif

                                    </td>



                                    {{-- Actions --}}
                                    <td>

                                        <div
                                            class="d-flex
                                                   gap-2"
                                        >

                                            @can(
                                                'user-management.organisation-units.update'
                                            )

                                                <a
                                                    href="{{ route(
                                                        'user-management.organisation-units.edit',
                                                        $organisationUnit
                                                    ) }}"
                                                    class="btn
                                                           btn-sm
                                                           btn-primary"
                                                    title="Edit Organisation Unit"
                                                >

                                                    <i
                                                        class="mdi
                                                               mdi-pencil-outline"
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
                                                        class="btn
                                                               btn-sm
                                                               btn-danger"
                                                        title="Delete Organisation Unit"
                                                    >

                                                        <i
                                                            class="mdi
                                                                   mdi-delete-outline"
                                                        ></i>

                                                    </button>

                                                </form>

                                            @endcan

                                        </div>

                                    </td>


                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="8"
                                        class="text-center
                                               py-5"
                                    >

                                        <div
                                            class="avatar-md
                                                   mx-auto
                                                   mb-3"
                                        >

                                            <span
                                                class="avatar-title
                                                       rounded-circle
                                                       bg-soft-secondary
                                                       text-secondary"
                                            >

                                                <i
                                                    class="mdi
                                                           mdi-sitemap
                                                           font-size-24"
                                                ></i>

                                            </span>

                                        </div>


                                        <h5>
                                            No Organisation Units Found
                                        </h5>


                                        <p
                                            class="text-muted
                                                   mb-0"
                                        >

                                            The LAPF organisation structure
                                            has not yet been configured.

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