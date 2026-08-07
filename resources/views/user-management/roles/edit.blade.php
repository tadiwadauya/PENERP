@extends('layouts.app')

@section('title', 'Edit Role')

@section('page-heading', 'Edit Role')


@section('page-actions')

    <a
        href="{{ route(
            'user-management.roles.index'
        ) }}"
        class="btn btn-light"
    >

        <i class="mdi mdi-arrow-left me-1"></i>

        Back to Roles

    </a>

@endsection


@section('content')


@php

    $selectedPermissions =
        old(
            'permissions',
            $selectedPermissions ?? []
        );


    $protectedRole =
        in_array(
            strtolower($role->name),
            [
                'system-administrator',
                'system administrator',
                'super-admin',
                'super_admin',
            ],
            true
        );


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

@endphp



@if(session('success'))

    <div
        class="alert alert-success
               alert-dismissible fade show"
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



@if($errors->any())

    <div
        class="alert alert-danger
               alert-dismissible fade show"
        role="alert"
    >

        <h5 class="alert-heading">

            <i
                class="mdi
                       mdi-alert-circle-outline
                       me-1"
            ></i>

            Please correct the following

        </h5>


        <ul class="mb-0">

            @foreach(
                $errors->all()
                as $error
            )

                <li>
                    {{ $error }}
                </li>

            @endforeach

        </ul>


        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

@endif



{{-- =========================================================
     ROLE SUMMARY
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <div
                    class="d-flex
                           flex-column
                           flex-md-row
                           align-items-md-center"
                >

                    <div
                        class="avatar-lg
                               me-md-4
                               mb-3
                               mb-md-0"
                    >

                        <span
                            class="avatar-title
                                   rounded-circle
                                   bg-soft-primary
                                   text-primary
                                   font-size-24"
                        >

                            <i
                                class="mdi
                                       mdi-shield-account-outline"
                            ></i>

                        </span>

                    </div>


                    <div class="flex-grow-1">

                        <h4 class="mb-1">

                            {{ $roleDisplayName }}

                        </h4>


                        <p class="text-muted mb-2">

                            {{ $role->name }}

                        </p>


                        <div
                            class="d-flex
                                   flex-wrap
                                   gap-2"
                        >

                            <span
                                class="badge
                                       bg-soft-info
                                       text-info"
                            >

                                {{
                                    $role
                                        ->permissions
                                        ->count()
                                }}

                                Permissions

                            </span>


                            <span
                                class="badge
                                       bg-soft-success
                                       text-success"
                            >

                                {{
                                    $role
                                        ->users()
                                        ->count()
                                }}

                                Users

                            </span>


                            @if($protectedRole)

                                <span
                                    class="badge
                                           bg-soft-warning
                                           text-warning"
                                >
                                    Protected System Role
                                </span>

                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<form
    method="POST"
    action="{{ route(
        'user-management.roles.update',
        $role
    ) }}"
>

    @csrf
    @method('PUT')


    {{-- =====================================================
         ROLE INFORMATION
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Role Information
            </h4>

            <p class="card-title-desc">

                Update the role name and the permissions
                assigned to it.

            </p>


            <div class="row">

                <div class="col-lg-6">

                    <div class="mb-3">

                        <label
                            for="name"
                            class="form-label"
                        >

                            Role Name

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control
                                   @error('name')
                                       is-invalid
                                   @enderror"
                            value="{{ old(
                                'name',
                                $role->name
                            ) }}"
                            required
                            @readonly($protectedRole)
                        >


                        @error('name')

                            <div
                                class="invalid-feedback"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        @if($protectedRole)

                            <div class="form-text">

                                This is a protected system role.
                                Its identifying name cannot be
                                changed, but its permissions can
                                still be reviewed and updated.

                            </div>

                        @else

                            <div class="form-text">

                                Spaces are converted to hyphens
                                when the role is saved.

                            </div>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- =====================================================
         PERMISSIONS
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <div
                class="d-flex
                       flex-wrap
                       align-items-center
                       justify-content-between
                       mb-4"
            >

                <div>

                    <h4 class="header-title mb-1">
                        Role Permissions
                    </h4>

                    <p
                        class="card-title-desc mb-0"
                    >

                        Permissions selected below are inherited
                        by every user assigned to this role.

                    </p>

                </div>


                <div class="mt-3 mt-md-0">

                    <button
                        type="button"
                        class="btn btn-sm btn-light"
                        id="select-all-permissions"
                    >

                        <i
                            class="mdi
                                   mdi-checkbox-multiple-marked-outline
                                   me-1"
                        ></i>

                        Select All

                    </button>


                    <button
                        type="button"
                        class="btn btn-sm btn-light"
                        id="clear-all-permissions"
                    >

                        <i
                            class="mdi
                                   mdi-checkbox-multiple-blank-outline
                                   me-1"
                        ></i>

                        Clear All

                    </button>

                </div>

            </div>


            @forelse(
                $permissionsByModule
                as $moduleName => $permissions
            )

                <div
                    class="border
                           rounded
                           mb-4
                           permission-module"
                >

                    <div
                        class="p-3
                               border-bottom
                               d-flex
                               flex-wrap
                               align-items-center
                               justify-content-between"
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
                                    $permissions
                                        ->count()
                                }}

                                permission(s)

                            </span>

                        </div>


                        <button
                            type="button"
                            class="btn
                                   btn-sm
                                   btn-soft-primary
                                   select-module"
                        >

                            Select Module

                        </button>

                    </div>


                    <div class="p-3">

                        <div class="row">

                            @foreach(
                                $permissions
                                as $permission
                            )

                                @php

                                    $parts =
                                        explode(
                                            '.',
                                            $permission->name
                                        );

                                    $action =
                                        end($parts);

                                    $permissionLabel =
                                        \Illuminate\Support\Str::of(
                                            $permission->name
                                        )
                                            ->replace(
                                                '.',
                                                ' › '
                                            )
                                            ->replace(
                                                '-',
                                                ' '
                                            )
                                            ->title();

                                @endphp


                                <div
                                    class="col-xl-4
                                           col-lg-6"
                                >

                                    <div
                                        class="border
                                               rounded
                                               p-3
                                               mb-3
                                               permission-item"
                                    >

                                        <div
                                            class="form-check"
                                        >

                                            <input
                                                type="checkbox"
                                                class="form-check-input
                                                       permission-checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->name }}"
                                                id="permission_{{ $permission->id }}"
                                                @checked(
                                                    in_array(
                                                        $permission->name,
                                                        $selectedPermissions
                                                    )
                                                )
                                            >


                                            <label
                                                class="form-check-label
                                                       w-100"
                                                for="permission_{{ $permission->id }}"
                                            >

                                                <strong>
                                                    {{ ucfirst($action) }}
                                                </strong>


                                                <span
                                                    class="d-block
                                                           text-muted
                                                           font-size-12
                                                           mt-1"
                                                >

                                                    {{ $permissionLabel }}

                                                </span>

                                            </label>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            @empty

                <div
                    class="alert
                           alert-warning
                           mb-0"
                >

                    No permissions have been configured.

                </div>

            @endforelse

        </div>

    </div>



    {{-- =====================================================
         SAVE
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <div
                class="d-flex
                       justify-content-end
                       flex-wrap
                       gap-2"
            >

                <a
                    href="{{ route(
                        'user-management.roles.index'
                    ) }}"
                    class="btn btn-light"
                >

                    <i class="mdi mdi-close me-1"></i>

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i
                        class="mdi
                               mdi-content-save-outline
                               me-1"
                    ></i>

                    Save Changes

                </button>

            </div>

        </div>

    </div>

</form>



{{-- =========================================================
     DELETE ROLE
========================================================= --}}

@if(!$protectedRole)

    @can('user-management.roles.delete')

        <div class="card">

            <div class="card-body">

                <h4 class="header-title text-danger">
                    Delete Role
                </h4>


                <p class="card-title-desc">

                    A role can only be deleted when it is not
                    assigned to any users.

                </p>


                <form
                    method="POST"
                    action="{{ route(
                        'user-management.roles.destroy',
                        $role
                    ) }}"
                    onsubmit="
                        return confirm(
                            'Are you sure you want to delete this role?'
                        );
                    "
                >

                    @csrf
                    @method('DELETE')


                    <button
                        type="submit"
                        class="btn btn-danger"
                    >

                        <i
                            class="mdi
                                   mdi-delete-outline
                                   me-1"
                        ></i>

                        Delete Role

                    </button>

                </form>

            </div>

        </div>

    @endcan

@endif

@endsection


@push('scripts')

<script>

$(document).ready(function () {


    /*
    |--------------------------------------------------------------------------
    | Select All
    |--------------------------------------------------------------------------
    */

    $('#select-all-permissions').on(
        'click',
        function () {

            $('.permission-checkbox')
                .prop(
                    'checked',
                    true
                );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Clear All
    |--------------------------------------------------------------------------
    */

    $('#clear-all-permissions').on(
        'click',
        function () {

            $('.permission-checkbox')
                .prop(
                    'checked',
                    false
                );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Module Selection
    |--------------------------------------------------------------------------
    */

    $('.select-module').on(
        'click',
        function () {

            const module =
                $(this)
                    .closest(
                        '.permission-module'
                    );


            const checkboxes =
                module.find(
                    '.permission-checkbox'
                );


            const allSelected =
                checkboxes.length > 0
                &&
                checkboxes.filter(
                    ':checked'
                ).length
                === checkboxes.length;


            checkboxes.prop(
                'checked',
                !allSelected
            );


            $(this).text(
                allSelected
                    ? 'Select Module'
                    : 'Clear Module'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Set Module Button State on Page Load
    |--------------------------------------------------------------------------
    */

    $('.permission-module')
        .each(
            function () {

                const module =
                    $(this);


                const checkboxes =
                    module.find(
                        '.permission-checkbox'
                    );


                const button =
                    module.find(
                        '.select-module'
                    );


                if (
                    checkboxes.length > 0
                    &&
                    checkboxes.filter(
                        ':checked'
                    ).length
                    === checkboxes.length
                ) {

                    button.text(
                        'Clear Module'
                    );

                }

            }
        );


});

</script>

@endpush