@extends('layouts.app')

@section('title', 'Permissions')

@section(
    'page-heading',
    'Permission Management'
)


@section('page-actions')

    @can(
        'user-management.permissions.create'
    )

        <a
            href="{{ route(
                'user-management.permissions.create'
            ) }}"
            class="btn btn-success"
        >

            <i
                class="mdi
                       mdi-key-plus
                       me-1"
            ></i>

            Add Permission

        </a>

    @endcan

@endsection


@section('content')


{{-- =========================================================
     SUMMARY
========================================================= --}}

<div class="row">


    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <div
                    class="d-flex
                           align-items-center"
                >

                    <div
                        class="avatar-sm me-3"
                    >

                        <span
                            class="avatar-title
                                   rounded-circle
                                   bg-soft-primary
                                   text-primary"
                        >

                            <i
                                class="mdi
                                       mdi-key-variant
                                       font-size-20"
                            ></i>

                        </span>

                    </div>


                    <div>

                        <p
                            class="text-muted
                                   mb-1"
                        >
                            Total Permissions
                        </p>

                        <h4 class="mb-0">

                            {{
                                $permissions
                                    ->count()
                            }}

                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <div
                    class="d-flex
                           align-items-center"
                >

                    <div
                        class="avatar-sm me-3"
                    >

                        <span
                            class="avatar-title
                                   rounded-circle
                                   bg-soft-info
                                   text-info"
                        >

                            <i
                                class="mdi
                                       mdi-folder-key-outline
                                       font-size-20"
                            ></i>

                        </span>

                    </div>


                    <div>

                        <p
                            class="text-muted
                                   mb-1"
                        >
                            Modules
                        </p>

                        <h4 class="mb-0">

                            {{
                                $permissionsByModule
                                    ->count()
                            }}

                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <div
                    class="d-flex
                           align-items-center"
                >

                    <div
                        class="avatar-sm me-3"
                    >

                        <span
                            class="avatar-title
                                   rounded-circle
                                   bg-soft-success
                                   text-success"
                        >

                            <i
                                class="mdi
                                       mdi-shield-account-outline
                                       font-size-20"
                            ></i>

                        </span>

                    </div>


                    <div>

                        <p
                            class="text-muted
                                   mb-1"
                        >
                            Role Assignments
                        </p>

                        <h4 class="mb-0">

                            {{
                                $permissions
                                    ->sum(
                                        'roles_count'
                                    )
                            }}

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
     PERMISSIONS
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">


                <div class="mb-4">

                    <h4 class="header-title">
                        System Permissions
                    </h4>

                    <p
                        class="card-title-desc
                               mb-0"
                    >

                        Permissions are the individual
                        access rights used by roles
                        throughout the LAPF Pension Fund
                        System.

                    </p>

                </div>


                @forelse(
                    $permissionsByModule
                    as $moduleName
                    => $modulePermissions
                )

                    <div
                        class="border
                               rounded
                               mb-4"
                    >


                        {{-- Module Heading --}}
                        <div
                            class="p-3
                                   border-bottom
                                   d-flex
                                   flex-wrap
                                   justify-content-between
                                   align-items-center"
                        >

                            <div>

                                <h5
                                    class="font-size-15
                                           mb-1"
                                >

                                    <i
                                        class="mdi
                                               mdi-folder-key-outline
                                               me-1"
                                    ></i>

                                    {{ $moduleName }}

                                </h5>


                                <span
                                    class="text-muted
                                           font-size-12"
                                >

                                    {{
                                        $modulePermissions
                                            ->count()
                                    }}

                                    permission(s)

                                </span>

                            </div>

                        </div>


                        {{-- Permission Table --}}
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
                                            Permission
                                        </th>

                                        <th>
                                            Resource
                                        </th>

                                        <th>
                                            Action
                                        </th>

                                        <th>
                                            Roles
                                        </th>

                                        <th
                                            style="
                                                width:140px;
                                            "
                                        >
                                            Actions
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach(
                                        $modulePermissions
                                        as $permission
                                    )

                                        @php

                                            $parts =
                                                explode(
                                                    '.',
                                                    $permission
                                                        ->name
                                                );

                                            $resource =
                                                $parts[1]
                                                ?? '-';

                                            $action =
                                                implode(
                                                    '.',
                                                    array_slice(
                                                        $parts,
                                                        2
                                                    )
                                                )
                                                ?: '-';


                                            $protected =
                                                in_array(
                                                    $permission
                                                        ->name,
                                                    [
                                                        'user-management.permissions.view',
                                                        'user-management.permissions.create',
                                                        'user-management.permissions.update',
                                                        'user-management.permissions.delete',
                                                    ],
                                                    true
                                                );

                                        @endphp


                                        <tr>


                                            {{-- Permission --}}
                                            <td>

                                                <div
                                                    class="d-flex
                                                           align-items-center"
                                                >

                                                    <div
                                                        class="avatar-xs
                                                               me-2"
                                                    >

                                                        <span
                                                            class="avatar-title
                                                                   rounded-circle
                                                                   bg-soft-primary
                                                                   text-primary"
                                                        >

                                                            <i
                                                                class="mdi
                                                                       mdi-key-outline"
                                                            ></i>

                                                        </span>

                                                    </div>


                                                    <div>

                                                        <strong>

                                                            {{
                                                                $permission
                                                                    ->name
                                                            }}

                                                        </strong>


                                                        @if($protected)

                                                            <span
                                                                class="badge
                                                                       bg-soft-warning
                                                                       text-warning
                                                                       ms-1"
                                                            >
                                                                Protected
                                                            </span>

                                                        @endif

                                                    </div>

                                                </div>

                                            </td>



                                            {{-- Resource --}}
                                            <td>

                                                <span
                                                    class="badge
                                                           bg-soft-info
                                                           text-info"
                                                >

                                                    {{
                                                        \Illuminate\Support\Str::of(
                                                            $resource
                                                        )
                                                            ->replace(
                                                                '-',
                                                                ' '
                                                            )
                                                            ->title()
                                                    }}

                                                </span>

                                            </td>



                                            {{-- Action --}}
                                            <td>

                                                <span
                                                    class="badge
                                                           bg-soft-secondary
                                                           text-secondary"
                                                >

                                                    {{
                                                        \Illuminate\Support\Str::of(
                                                            $action
                                                        )
                                                            ->replace(
                                                                '-',
                                                                ' '
                                                            )
                                                            ->title()
                                                    }}

                                                </span>

                                            </td>



                                            {{-- Roles --}}
                                            <td>

                                                <span
                                                    class="badge
                                                           bg-soft-success
                                                           text-success"
                                                >

                                                    {{
                                                        $permission
                                                            ->roles_count
                                                    }}

                                                    role(s)

                                                </span>

                                            </td>



                                            {{-- Actions --}}
                                            <td>

                                                <div
                                                    class="d-flex
                                                           gap-2"
                                                >

                                                    @can(
                                                        'user-management.permissions.update'
                                                    )

                                                        <a
                                                            href="{{ route(
                                                                'user-management.permissions.edit',
                                                                $permission
                                                            ) }}"
                                                            class="btn
                                                                   btn-sm
                                                                   btn-primary"
                                                            title="Edit Permission"
                                                        >

                                                            <i
                                                                class="mdi
                                                                       mdi-pencil-outline"
                                                            ></i>

                                                        </a>

                                                    @endcan


                                                    @can(
                                                        'user-management.permissions.delete'
                                                    )

                                                        @if(!$protected)

                                                            <form
                                                                method="POST"
                                                                action="{{ route(
                                                                    'user-management.permissions.destroy',
                                                                    $permission
                                                                ) }}"
                                                                onsubmit="
                                                                    return confirm(
                                                                        'Delete this permission?'
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
                                                                    title="Delete Permission"
                                                                >

                                                                    <i
                                                                        class="mdi
                                                                               mdi-delete-outline"
                                                                    ></i>

                                                                </button>

                                                            </form>

                                                        @endif

                                                    @endcan

                                                </div>

                                            </td>


                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>


                    </div>

                @empty

                    <div
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
                                           mdi-key-off-outline
                                           font-size-24"
                                ></i>

                            </span>

                        </div>


                        <h5>
                            No Permissions Found
                        </h5>


                        <p
                            class="text-muted
                                   mb-0"
                        >
                            No system permissions
                            have been configured.
                        </p>

                    </div>

                @endforelse


            </div>

        </div>

    </div>

</div>

@endsection