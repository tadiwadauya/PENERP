@extends('layouts.app')

@section('title', 'Roles')

@section('page-heading', 'Role Management')


@section('page-actions')

    @can('user-management.roles.create')

        <a
            href="{{ route(
                'user-management.roles.create'
            ) }}"
            class="btn btn-success"
        >
            <i class="mdi mdi-shield-plus-outline me-1"></i>
            Add Role
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

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle
                                   bg-soft-primary text-primary"
                        >
                            <i
                                class="mdi mdi-shield-account-outline
                                       font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Total Roles
                        </p>

                        <h4 class="mb-0">
                            {{ $roles->count() }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-4 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle
                                   bg-soft-success text-success"
                        >
                            <i
                                class="mdi mdi-account-group-outline
                                       font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Users Assigned
                        </p>

                        <h4 class="mb-0">

                            {{
                                $roles->sum(
                                    'users_count'
                                )
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

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle
                                   bg-soft-info text-info"
                        >
                            <i
                                class="mdi mdi-key-variant
                                       font-size-20"
                            ></i>
                        </span>

                    </div>


                    <div>

                        <p class="text-muted mb-1">
                            Permission Assignments
                        </p>

                        <h4 class="mb-0">

                            {{
                                $roles->sum(
                                    'permissions_count'
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
     ROLE LIST
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">


                <div class="mb-4">

                    <h4 class="header-title">
                        System Roles
                    </h4>

                    <p class="card-title-desc mb-0">

                        Roles define groups of permissions that
                        can be assigned to LAPF system users.

                    </p>

                </div>


                @if(session('success'))

                    <div
                        class="alert alert-success
                               alert-dismissible fade show"
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
                        class="alert alert-danger
                               alert-dismissible fade show"
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


                <div class="table-responsive">

                    <table
                        class="table table-striped
                               table-bordered
                               align-middle
                               mb-0"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Permissions
                                </th>

                                <th>
                                    Users
                                </th>

                                <th>
                                    Access Summary
                                </th>

                                <th
                                    style="width: 140px;"
                                >
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($roles as $role)

                                @php

                                    $roleDisplayName =
                                        \Illuminate\Support\Str::of(
                                            $role->name
                                        )
                                            ->replace(
                                                '-',
                                                ' '
                                            )
                                            ->replace(
                                                '_',
                                                ' '
                                            )
                                            ->title();

                                    $protectedRole =
                                        in_array(
                                            strtolower(
                                                $role->name
                                            ),
                                            [
                                                'system-administrator',
                                                'system administrator',
                                                'super-admin',
                                                'super_admin',
                                            ],
                                            true
                                        );

                                @endphp


                                <tr>


                                    {{-- Role --}}
                                    <td>

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
                                                               mdi-shield-account-outline
                                                               font-size-18"
                                                    ></i>

                                                </span>

                                            </div>


                                            <div>

                                                <h6 class="mb-1">

                                                    {{ $roleDisplayName }}

                                                </h6>


                                                <span
                                                    class="text-muted
                                                           font-size-12"
                                                >

                                                    {{ $role->name }}

                                                </span>


                                                @if($protectedRole)

                                                    <span
                                                        class="badge
                                                               bg-soft-warning
                                                               text-warning
                                                               ms-1"
                                                    >
                                                        System Role
                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    </td>



                                    {{-- Permissions --}}
                                    <td>

                                        <span
                                            class="badge
                                                   bg-soft-info
                                                   text-info
                                                   font-size-12"
                                        >

                                            {{
                                                $role
                                                    ->permissions_count
                                            }}

                                            permission(s)

                                        </span>

                                    </td>



                                    {{-- Users --}}
                                    <td>

                                        <span
                                            class="badge
                                                   bg-soft-success
                                                   text-success
                                                   font-size-12"
                                        >

                                            {{
                                                $role->users_count
                                            }}

                                            user(s)

                                        </span>

                                    </td>



                                    {{-- Permission Preview --}}
                                    <td>

                                        @forelse(
                                            $role->permissions
                                                ->take(4)
                                            as $permission
                                        )

                                            <span
                                                class="badge
                                                       bg-soft-secondary
                                                       text-secondary
                                                       me-1
                                                       mb-1"
                                            >

                                                {{
                                                    $permission->name
                                                }}

                                            </span>

                                        @empty

                                            <span
                                                class="text-muted"
                                            >
                                                No permissions assigned
                                            </span>

                                        @endforelse


                                        @if(
                                            $role->permissions_count
                                            > 4
                                        )

                                            <span
                                                class="badge
                                                       bg-secondary"
                                            >

                                                +{{
                                                    $role
                                                        ->permissions_count
                                                    - 4
                                                }}

                                            </span>

                                        @endif

                                    </td>



                                    {{-- Actions --}}
                                    <td>

                                        <div
                                            class="d-flex gap-2"
                                        >

                                            @can(
                                                'user-management.roles.update'
                                            )

                                                <a
                                                    href="{{ route(
                                                        'user-management.roles.edit',
                                                        $role
                                                    ) }}"
                                                    class="btn
                                                           btn-sm
                                                           btn-primary"
                                                    title="Edit Role"
                                                >

                                                    <i
                                                        class="mdi
                                                               mdi-pencil-outline"
                                                    ></i>

                                                </a>

                                            @endcan


                                            @can(
                                                'user-management.roles.delete'
                                            )

                                                @if(!$protectedRole)

                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'user-management.roles.destroy',
                                                            $role
                                                        ) }}"
                                                        onsubmit="
                                                            return confirm(
                                                                'Delete this role?'
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
                                                            title="Delete Role"
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

                            @empty

                                <tr>

                                    <td
                                        colspan="5"
                                        class="text-center py-5"
                                    >

                                        <div
                                            class="avatar-md
                                                   mx-auto mb-3"
                                        >

                                            <span
                                                class="avatar-title
                                                       rounded-circle
                                                       bg-soft-secondary
                                                       text-secondary"
                                            >

                                                <i
                                                    class="mdi
                                                           mdi-shield-off-outline
                                                           font-size-24"
                                                ></i>

                                            </span>

                                        </div>


                                        <h5>
                                            No Roles Found
                                        </h5>

                                        <p
                                            class="text-muted mb-0"
                                        >
                                            No system roles have been
                                            configured yet.
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