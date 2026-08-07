@extends('layouts.app')

@section('title', 'Create Role')

@section('page-heading', 'Create Role')


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

            @foreach($errors->all() as $error)

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



<form
    method="POST"
    action="{{ route(
        'user-management.roles.store'
    ) }}"
>

    @csrf


    {{-- =====================================================
         ROLE INFORMATION
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Role Information
            </h4>

            <p class="card-title-desc">

                Create a role that can later be assigned
                to LAPF system users.

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
                            value="{{ old('name') }}"
                            placeholder="e.g. Benefit Claims Officer"
                            required
                            autofocus
                        >


                        @error('name')

                            <div
                                class="invalid-feedback"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        <div class="form-text">

                            The system converts spaces to
                            hyphens automatically. For example,
                            "Benefit Claims Officer" becomes
                            "benefit-claims-officer".

                        </div>

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
                class="d-flex flex-wrap
                       align-items-center
                       justify-content-between
                       mb-4"
            >

                <div>

                    <h4 class="header-title mb-1">
                        Permissions
                    </h4>

                    <p
                        class="card-title-desc mb-0"
                    >

                        Select the specific system rights
                        that users assigned to this role
                        should receive.

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
                    class="border rounded
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

                            <h5 class="font-size-15 mb-1">

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
                                    $permissions->count()
                                }}

                                permission(s)

                            </span>

                        </div>


                        <div>

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

                    </div>


                    <div class="p-3">

                        <div class="row">

                            @foreach(
                                $permissions
                                as $permission
                            )

                                @php

                                    $permissionParts =
                                        explode(
                                            '.',
                                            $permission->name
                                        );

                                    $action =
                                        end(
                                            $permissionParts
                                        );

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
                                                        old(
                                                            'permissions',
                                                            []
                                                        )
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
                    class="alert alert-warning mb-0"
                >

                    <i
                        class="mdi
                               mdi-alert-outline
                               me-1"
                    ></i>

                    No system permissions have been configured.

                </div>

            @endforelse

        </div>

    </div>



    {{-- =====================================================
         FORM ACTIONS
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <div
                class="d-flex
                       flex-wrap
                       justify-content-end
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
                    class="btn btn-success"
                >

                    <i
                        class="mdi
                               mdi-content-save-outline
                               me-1"
                    ></i>

                    Create Role

                </button>

            </div>

        </div>

    </div>

</form>

@endsection


@push('scripts')

<script>

$(document).ready(function () {


    /*
    |--------------------------------------------------------------------------
    | Select All Permissions
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
    | Clear All Permissions
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
    | Select/Clear Individual Module
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


});

</script>

@endpush