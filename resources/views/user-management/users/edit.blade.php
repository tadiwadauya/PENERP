@extends('layouts.app')

@section('title', 'Edit User')

@section('page-heading', 'Edit User')


@push('styles')

    <link
        href="{{ asset('layouts/assets/libs/select2/css/select2.min.css') }}"
        rel="stylesheet"
        type="text/css"
    >

    <style>

        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 38px;
        }

        .select2-container--default
        .select2-selection--single
        .select2-selection__rendered {
            line-height: 36px;
        }

        .select2-container--default
        .select2-selection--single
        .select2-selection__arrow {
            height: 36px;
        }

        body.lapf-dark-mode
        .select2-container--default
        .select2-selection--single,
        body.lapf-dark-mode
        .select2-container--default
        .select2-selection--multiple {
            background-color: #20242c;
            border-color: #3b424f;
            color: #ffffff;
        }

        body.lapf-dark-mode
        .select2-container--default
        .select2-selection--single
        .select2-selection__rendered {
            color: #ffffff;
        }

        body.lapf-dark-mode
        .select2-dropdown {
            background-color: #252a34;
            border-color: #3b424f;
            color: #ffffff;
        }

    </style>

@endpush


@section('page-actions')

    <a
        href="{{ route(
            'user-management.users.show',
            $user
        ) }}"
        class="btn btn-light"
    >
        <i class="mdi mdi-arrow-left me-1"></i>
        Back to User
    </a>

@endsection


@section('content')


@if($errors->any())

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <h5 class="alert-heading">
            <i class="mdi mdi-alert-circle-outline me-1"></i>
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
        'user-management.users.update',
        $user
    ) }}"
>

    @csrf
    @method('PUT')


    @include('user-management.users._form')


    <div class="card">

        <div class="card-body">

            <div
                class="d-flex flex-wrap justify-content-end gap-2"
            >

                <a
                    href="{{ route(
                        'user-management.users.show',
                        $user
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
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Save Changes
                </button>

            </div>

        </div>

    </div>

</form>

@endsection


@push('scripts-before-app')

    <script
        src="{{ asset('layouts/assets/libs/select2/js/select2.min.js') }}">
    </script>

@endpush


@push('scripts')

<script>

$(document).ready(function () {

    $('.select2').select2({
        width: '100%'
    });

});

</script>

@endpush