@extends('layouts.app')

@section('title', 'Edit Permission')

@section(
    'page-heading',
    'Edit Permission'
)


@section('page-actions')

    <a
        href="{{ route(
            'user-management.permissions.index'
        ) }}"
        class="btn btn-light"
    >

        <i
            class="mdi
                   mdi-arrow-left
                   me-1"
        ></i>

        Back to Permissions

    </a>

@endsection


@section('content')


@php

    $protectedPermission =
        in_array(
            $permission->name,
            [
                'user-management.permissions.view',
                'user-management.permissions.create',
                'user-management.permissions.update',
                'user-management.permissions.delete',
            ],
            true
        );

@endphp



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



@if($errors->any())

    <div
        class="alert
               alert-danger
               alert-dismissible
               fade show"
        role="alert"
    >

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
     SUMMARY
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div
            class="d-flex
                   align-items-center"
        >

            <div
                class="avatar-lg
                       me-4"
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
                               mdi-key-variant"
                    ></i>

                </span>

            </div>


            <div>

                <h4 class="mb-1">

                    {{
                        $permission->name
                    }}

                </h4>


                <p
                    class="text-muted
                           mb-2"
                >

                    Assigned to

                    <strong>

                        {{
                            $permission
                                ->roles
                                ->count()
                        }}

                    </strong>

                    role(s)

                </p>


                @if($protectedPermission)

                    <span
                        class="badge
                               bg-soft-warning
                               text-warning"
                    >
                        Protected System Permission
                    </span>

                @endif

            </div>

        </div>

    </div>

</div>



<form
    method="POST"
    action="{{ route(
        'user-management.permissions.update',
        $permission
    ) }}"
>

    @csrf
    @method('PUT')


    <div class="card">

        <div class="card-body">


            <h4 class="header-title">
                Permission Information
            </h4>


            <p class="card-title-desc">

                Permissions follow the convention:

                <code>
                    module.resource.action
                </code>

            </p>


            <div class="row">


                {{-- Module --}}
                <div class="col-lg-4">

                    <div class="mb-3">

                        <label
                            for="module"
                            class="form-label"
                        >
                            Module
                        </label>


                        <input
                            type="text"
                            id="module"
                            name="module"
                            class="form-control"
                            value="{{ old(
                                'module',
                                $permissionModule
                            ) }}"
                            required
                            @readonly(
                                $protectedPermission
                            )
                        >

                    </div>

                </div>



                {{-- Resource --}}
                <div class="col-lg-4">

                    <div class="mb-3">

                        <label
                            for="resource"
                            class="form-label"
                        >
                            Resource / Function
                        </label>


                        <input
                            type="text"
                            id="resource"
                            name="resource"
                            class="form-control"
                            value="{{ old(
                                'resource',
                                $permissionResource
                            ) }}"
                            required
                            @readonly(
                                $protectedPermission
                            )
                        >

                    </div>

                </div>



                {{-- Action --}}
                <div class="col-lg-4">

                    <div class="mb-3">

                        <label
                            for="action"
                            class="form-label"
                        >
                            Action
                        </label>


                        <input
                            type="text"
                            id="action"
                            name="action"
                            class="form-control"
                            value="{{ old(
                                'action',
                                $permissionAction
                            ) }}"
                            required
                            @readonly(
                                $protectedPermission
                            )
                        >

                    </div>

                </div>


            </div>


            @if($protectedPermission)

                <div
                    class="alert
                           alert-warning
                           mb-0"
                >

                    <i
                        class="mdi
                               mdi-shield-lock-outline
                               me-1"
                    ></i>

                    This permission protects the
                    Permission Management module.
                    Its name cannot be changed.

                </div>

            @else

                <div
                    class="alert
                           alert-info
                           mb-0"
                >

                    <strong>
                        Permission Preview:
                    </strong>

                    <code
                        id="permission-preview"
                        class="ms-1"
                    >

                        {{
                            $permission->name
                        }}

                    </code>

                </div>

            @endif


        </div>

    </div>



    <div class="card">

        <div class="card-body">


            <h4 class="header-title">
                Assigned Roles
            </h4>


            <p class="card-title-desc">

                Roles currently using this
                permission.

            </p>


            @forelse(
                $permission->roles
                as $role
            )

                <span
                    class="badge
                           bg-soft-primary
                           text-primary
                           font-size-13
                           me-1
                           mb-2"
                >

                    {{
                        \Illuminate\Support\Str::of(
                            $role->name
                        )
                            ->replace(
                                '-',
                                ' '
                            )
                            ->title()
                    }}

                </span>

            @empty

                <span class="text-muted">
                    This permission is not
                    currently assigned to any role.
                </span>

            @endforelse


        </div>

    </div>



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
                        'user-management.permissions.index'
                    ) }}"
                    class="btn btn-light"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn
                           btn-primary"
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



@if(!$protectedPermission)

    @can(
        'user-management.permissions.delete'
    )

        <div class="card">

            <div class="card-body">


                <h4
                    class="header-title
                           text-danger"
                >
                    Delete Permission
                </h4>


                <p class="card-title-desc">

                    A permission can only be
                    deleted when it is no longer
                    assigned to any role.

                </p>


                <form
                    method="POST"
                    action="{{ route(
                        'user-management.permissions.destroy',
                        $permission
                    ) }}"
                    onsubmit="
                        return confirm(
                            'Are you sure you want to delete this permission?'
                        );
                    "
                >

                    @csrf
                    @method('DELETE')


                    <button
                        type="submit"
                        class="btn
                               btn-danger"
                    >

                        <i
                            class="mdi
                                   mdi-delete-outline
                                   me-1"
                        ></i>

                        Delete Permission

                    </button>

                </form>


            </div>

        </div>

    @endcan

@endif

@endsection


@push('scripts')

@if(!$protectedPermission)

<script>

$(document).ready(function () {


    function normaliseSegment(
        value
    ) {

        return value
            .trim()
            .toLowerCase()
            .replace(
                /_/g,
                '-'
            )
            .replace(
                /\s+/g,
                '-'
            )
            .replace(
                /[^a-z0-9.-]/g,
                ''
            );

    }


    function updatePreview() {

        const module =
            normaliseSegment(
                $('#module').val()
            );


        const resource =
            normaliseSegment(
                $('#resource').val()
            );


        const action =
            normaliseSegment(
                $('#action').val()
            );


        $('#permission-preview')
            .text(
                module
                + '.'
                + resource
                + '.'
                + action
            );

    }


    $('#module, #resource, #action')
        .on(
            'keyup change',
            updatePreview
        );


    updatePreview();


});

</script>

@endif

@endpush