@extends('layouts.app')

@section('title', 'Edit Employer Group')

@section('page-heading', 'Edit Employer Group')

@section('content')
@include(
    'pensions-administration.partials.navigation'
)

@if($errors->any())

    <div class="alert alert-danger">

        <strong>
            Please correct the following:
        </strong>

        <ul class="mb-0">

            @foreach($errors->all() as $error)

                <li>
                    {{ $error }}
                </li>

            @endforeach

        </ul>

    </div>

@endif


<form
    method="POST"
    action="{{ route(
        'pensions-administration.updates.employer-groups.update',
        $employerGroup
    ) }}"
>

    @csrf
    @method('PUT')


    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Employer Group Details
            </h4>

            <p class="card-title-desc">
                Update the employer group configuration.
            </p>


            <div class="row">


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Group Code
                        </label>

                        <input
                            type="text"
                            name="code"
                            class="form-control"
                            value="{{ old(
                                'code',
                                $employerGroup->code
                            ) }}"
                            required
                        >

                    </div>

                </div>


                <div class="col-md-8">

                    <div class="mb-3">

                        <label class="form-label">
                            Group Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="{{ old(
                                'name',
                                $employerGroup->name
                            ) }}"
                            required
                        >

                    </div>

                </div>


                <div class="col-12">

                    <div class="mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                        >{{ old(
                            'description',
                            $employerGroup->description
                        ) }}</textarea>

                    </div>

                </div>


                <div class="col-md-6">

                    <input
                        type="hidden"
                        name="vote_number_required"
                        value="0"
                    >

                    <div class="form-check form-switch mb-3">

                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="vote_number_required"
                            name="vote_number_required"
                            value="1"
                            @checked(
                                old(
                                    'vote_number_required',
                                    $employerGroup
                                        ->vote_number_required
                                )
                            )
                        >

                        <label
                            class="form-check-label"
                            for="vote_number_required"
                        >
                            Vote Number Required
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <div class="form-check form-switch mb-3">

                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="is_active"
                            name="is_active"
                            value="1"
                            @checked(
                                old(
                                    'is_active',
                                    $employerGroup->is_active
                                )
                            )
                        >

                        <label
                            class="form-check-label"
                            for="is_active"
                        >
                            Active
                        </label>

                    </div>

                </div>

            </div>


            <hr>


            <div class="d-flex justify-content-between">

                <a
                    href="{{ route(
                        'pensions-administration.updates.employer-groups.index'
                    ) }}"
                    class="btn btn-light"
                >
                    <i class="mdi mdi-arrow-left me-1"></i>
                    Cancel
                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="mdi mdi-content-save me-1"></i>
                    Save Changes
                </button>

            </div>

        </div>

    </div>

</form>

@endsection