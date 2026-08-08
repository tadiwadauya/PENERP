@extends('layouts.app')

@section('title', 'Edit Job Title')

@section(
    'page-heading',
    'Edit Job Title'
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


@if(session('success'))

    <div
        class="alert alert-success alert-dismissible fade show"
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
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        {{ session('error') }}

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

@endif


@if($errors->any())

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <h5 class="alert-heading">
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
     SUMMARY
========================================================= --}}

<div class="row">

    <div class="col-xl-8">

        <div class="card">

            <div class="card-body">

                <div
                    class="d-flex align-items-center"
                >

                    <div
                        class="avatar-lg me-4"
                    >

                        <span
                            class="avatar-title rounded-circle bg-soft-primary text-primary font-size-24"
                        >

                            <i
                                class="mdi mdi-briefcase-outline"
                            ></i>

                        </span>

                    </div>


                    <div>

                        <h4 class="mb-1">

                            {{ $jobTitle->name }}

                        </h4>


                        <p class="text-muted mb-2">

                            {{ $jobTitle->code }}

                        </p>


                        @if($jobTitle->is_active)

                            <span
                                class="badge bg-soft-success text-success"
                            >
                                Active Job Title
                            </span>

                        @else

                            <span
                                class="badge bg-soft-secondary text-secondary"
                            >
                                Inactive Job Title
                            </span>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="col-xl-4">

        <div class="card">

            <div class="card-body text-center">

                <div
                    class="avatar-sm mx-auto mb-2"
                >

                    <span
                        class="avatar-title rounded-circle bg-soft-info text-info"
                    >
                        <i
                            class="mdi mdi-account-group-outline font-size-20"
                        ></i>
                    </span>

                </div>


                <p class="text-muted mb-1">
                    Employees Assigned
                </p>


                <h4 class="mb-0">
                    {{ $employeeCount }}
                </h4>

            </div>

        </div>

    </div>

</div>



<form
    method="POST"
    action="{{ route(
        'user-management.job-titles.update',
        $jobTitle
    ) }}"
>

    @csrf
    @method('PUT')


    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Job Title Information
            </h4>


            <p class="card-title-desc">

                Update the employment position without
                affecting employee grades, roles or permissions.

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
                            class="form-control"
                            value="{{ old(
                                'code',
                                $jobTitle->code
                            ) }}"
                            required
                        >

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
                            class="form-control"
                            value="{{ old(
                                'name',
                                $jobTitle->name
                            ) }}"
                            required
                        >

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
                        >{{ old(
                            'description',
                            $jobTitle->description
                        ) }}</textarea>

                    </div>

                </div>



                {{-- Status --}}
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
                                        $jobTitle->is_active
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
         SAVE
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
                    class="btn btn-primary"
                >

                    <i
                        class="mdi mdi-content-save-outline me-1"
                    ></i>

                    Save Changes

                </button>

            </div>

        </div>

    </div>

</form>



{{-- =========================================================
     DELETE
========================================================= --}}

@can('user-management.job-titles.delete')

    <div class="card">

        <div class="card-body">

            <h4
                class="header-title text-danger"
            >
                Delete Job Title
            </h4>


            @if($employeeCount > 0)

                <div
                    class="alert alert-warning mb-0"
                >

                    <i
                        class="mdi mdi-alert-outline me-1"
                    ></i>

                    This job title cannot be deleted because

                    <strong>
                        {{ $employeeCount }}
                    </strong>

                    employee(s) are currently assigned to it.

                </div>

            @else

                <p class="card-title-desc">

                    Delete this job title only if it is
                    no longer required.

                </p>


                <form
                    method="POST"
                    action="{{ route(
                        'user-management.job-titles.destroy',
                        $jobTitle
                    ) }}"
                    onsubmit="
                        return confirm(
                            'Are you sure you want to delete this job title?'
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
                            class="mdi mdi-delete-outline me-1"
                        ></i>

                        Delete Job Title

                    </button>

                </form>

            @endif

        </div>

    </div>

@endcan

@endsection