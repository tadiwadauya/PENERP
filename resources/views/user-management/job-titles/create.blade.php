@extends('layouts.app')

@section('title', 'Create Job Title')

@section(
    'page-heading',
    'Create Job Title'
)


@section('page-actions')

    <a
        href="{{ route(
            'user-management.job-titles.index'
        ) }}"
        class="btn btn-light"
    >

        <i class="mdi mdi-arrow-left me-1"></i>

        Back to Job Titles

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
        'user-management.job-titles.store'
    ) }}"
>

    @csrf


    {{-- =====================================================
         JOB TITLE INFORMATION
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Job Title Information
            </h4>


            <p class="card-title-desc">

                Create a LAPF employment position.
                The job title is independent from grade,
                organisation unit, role and system permissions.

            </p>


            <div class="row">


                {{-- Code --}}
                <div class="col-lg-4">

                    <div class="mb-3">

                        <label
                            for="code"
                            class="form-label"
                        >

                            Job Title Code

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
                            placeholder="e.g. ICTO"
                            required
                            autofocus
                        >


                        @error('code')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror


                        <div class="form-text">
                            Short unique code used to identify the position.
                        </div>

                    </div>

                </div>



                {{-- Name --}}
                <div class="col-lg-8">

                    <div class="mb-3">

                        <label
                            for="name"
                            class="form-label"
                        >

                            Job Title

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
                            placeholder="e.g. ICT Officer"
                            required
                        >


                        @error('name')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

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
                            placeholder="Describe the position if required"
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
                                Active Job Title
                            </label>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         IMPORTANT INFORMATION
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Job Title & Grade
            </h4>


            <div
                class="alert alert-info mb-0"
            >

                <i
                    class="mdi mdi-information-outline me-1"
                ></i>

                <strong>
                    Job title and grade are separate.
                </strong>

                An employee's job title does not automatically
                determine their grade. Grade is assigned separately
                when creating or editing the employee.

            </div>

        </div>

    </div>



    {{-- =====================================================
         ACTIONS
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <div
                class="d-flex justify-content-end gap-2"
            >

                <a
                    href="{{ route(
                        'user-management.job-titles.index'
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

                    Create Job Title

                </button>

            </div>

        </div>

    </div>

</form>

@endsection