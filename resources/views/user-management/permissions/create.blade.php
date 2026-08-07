@extends('layouts.app')

@section('title', 'Create Permission')

@section(
    'page-heading',
    'Create Permission'
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


@if($errors->any())

    <div
        class="alert
               alert-danger
               alert-dismissible
               fade show"
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



<form
    method="POST"
    action="{{ route(
        'user-management.permissions.store'
    ) }}"
>

    @csrf


    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Permission Information
            </h4>


            <p class="card-title-desc">

                Permissions use the format:

                <code>
                    module.resource.action
                </code>

                For example:

                <code>
                    claims.withdrawal.create
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

                            <span
                                class="text-danger"
                            >
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="module"
                            name="module"
                            class="form-control
                                   @error('module')
                                       is-invalid
                                   @enderror"
                            value="{{ old(
                                'module'
                            ) }}"
                            placeholder="e.g. claims"
                            required
                        >


                        @error('module')

                            <div
                                class="invalid-feedback"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        <div class="form-text">

                            Main system module,
                            for example:
                            claims,
                            payroll,
                            user-management.

                        </div>

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

                            <span
                                class="text-danger"
                            >
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="resource"
                            name="resource"
                            class="form-control
                                   @error('resource')
                                       is-invalid
                                   @enderror"
                            value="{{ old(
                                'resource'
                            ) }}"
                            placeholder="e.g. withdrawal"
                            required
                        >


                        @error('resource')

                            <div
                                class="invalid-feedback"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        <div class="form-text">

                            Specific area within
                            the module.

                        </div>

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

                            <span
                                class="text-danger"
                            >
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="action"
                            name="action"
                            class="form-control
                                   @error('action')
                                       is-invalid
                                   @enderror"
                            value="{{ old(
                                'action'
                            ) }}"
                            placeholder="e.g. view"
                            required
                        >


                        @error('action')

                            <div
                                class="invalid-feedback"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        <div class="form-text">

                            Typical actions:
                            view,
                            create,
                            update,
                            delete,
                            approve,
                            authorise,
                            process.

                        </div>

                    </div>

                </div>


            </div>


            {{-- Preview --}}
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
                    module.resource.action
                </code>

            </div>


        </div>

    </div>



    <div class="card">

        <div class="card-body">

            <div
                class="d-flex
                       justify-content-end
                       gap-2"
            >

                <a
                    href="{{ route(
                        'user-management.permissions.index'
                    ) }}"
                    class="btn btn-light"
                >

                    <i
                        class="mdi
                               mdi-close
                               me-1"
                    ></i>

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn
                           btn-success"
                >

                    <i
                        class="mdi
                               mdi-content-save-outline
                               me-1"
                    ></i>

                    Create Permission

                </button>

            </div>

        </div>

    </div>

</form>

@endsection


@push('scripts')

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
            )
            || 'module';


        const resource =
            normaliseSegment(
                $('#resource').val()
            )
            || 'resource';


        const action =
            normaliseSegment(
                $('#action').val()
            )
            || 'action';


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

@endpush