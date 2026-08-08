@extends('layouts.app')

@section('title', 'Create Grade')

@section(
    'page-heading',
    'Create Grade'
)


@section('page-actions')

    <a
        href="{{ route(
            'user-management.grades.index'
        ) }}"
        class="btn btn-light"
    >

        <i class="mdi mdi-arrow-left me-1"></i>

        Back to Grades

    </a>

@endsection


@section('content')


@if($errors->any())

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <h5 class="alert-heading">

            <i
                class="mdi mdi-alert-circle-outline me-1"
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
        'user-management.grades.store'
    ) }}"
>

    @csrf


    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Grade Information
            </h4>


            <p class="card-title-desc">

                Create a grade within the LAPF grade structure.
                Grades are independent from job titles.

            </p>


            <div class="row">


                {{-- Code --}}
                <div class="col-lg-3">

                    <div class="mb-3">

                        <label
                            for="code"
                            class="form-label"
                        >

                            Grade Code

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="code"
                            name="code"
                            class="form-control
                                   @error('code')
                                       is-invalid
                                   @enderror"
                            value="{{ old('code') }}"
                            placeholder="e.g. G1"
                            required
                            autofocus
                        >


                        @error('code')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

                </div>



                {{-- Name --}}
                <div class="col-lg-6">

                    <div class="mb-3">

                        <label
                            for="name"
                            class="form-label"
                        >

                            Grade Name

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
                            placeholder="e.g. Grade 1"
                            required
                        >


                        @error('name')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

                </div>



                {{-- Rank Order --}}
                <div class="col-lg-3">

                    <div class="mb-3">

                        <label
                            for="rank_order"
                            class="form-label"
                        >

                            Rank / Order

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="number"
                            id="rank_order"
                            name="rank_order"
                            class="form-control
                                   @error('rank_order')
                                       is-invalid
                                   @enderror"
                            value="{{ old('rank_order') }}"
                            min="1"
                            required
                        >


                        @error('rank_order')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror


                        <div class="form-text">

                            Lower numbers represent
                            higher grades.

                        </div>

                    </div>

                </div>



                {{-- Description --}}
                <div class="col-12">

                    <div class="mb-3">

                        <label
                            for="description"
                            class="form-label"
                        >
                            Description
                        </label>


                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            rows="4"
                            maxlength="1000"
                            placeholder="Optional description of this grade"
                        >{{ old('description') }}</textarea>

                    </div>

                </div>



                {{-- Active --}}
                <div class="col-lg-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>


                        <div
                            class="form-check form-switch"
                        >

                            <input
                                type="hidden"
                                name="is_active"
                                value="0"
                            >


                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="is_active"
                                name="is_active"
                                value="1"
                                @checked(
                                    old(
                                        'is_active',
                                        1
                                    )
                                )
                            >


                            <label
                                class="form-check-label"
                                for="is_active"
                            >
                                Active Grade
                            </label>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- Important Note --}}
    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Grade Assignment
            </h4>


            <div class="alert alert-info mb-0">

                <i
                    class="mdi mdi-information-outline me-1"
                ></i>

                <strong>
                    Grades do not determine job titles.
                </strong>

                A Chief Executive Officer, Executive, Head of
                Department, Officer or any other position can be
                assigned the appropriate grade independently.

            </div>

        </div>

    </div>



    <div class="card">

        <div class="card-body">

            <div
                class="d-flex justify-content-end gap-2"
            >

                <a
                    href="{{ route(
                        'user-management.grades.index'
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
                        class="mdi mdi-content-save-outline me-1"
                    ></i>

                    Create Grade

                </button>

            </div>

        </div>

    </div>

</form>

@endsection