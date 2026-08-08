@extends('layouts.app')

@section('title', 'Create Employer Group')

@section('page-heading', 'Create Employer Group')

@section('content')
@include(
    'pensions-administration.partials.navigation'
)

@if($errors->any())

    <div class="alert alert-danger">

        <ul class="mb-0">

            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach

        </ul>

    </div>

@endif


<form
    method="POST"
    action="{{ route(
        'pensions-administration.updates.employer-groups.store'
    ) }}"
>

    @csrf

    <div class="card">

        <div class="card-body">

            <div class="row">

                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Code
                        </label>

                        <input
                            type="text"
                            name="code"
                            class="form-control"
                            value="{{ old('code') }}"
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
                            value="{{ old('name') }}"
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
                            rows="3"
                        >{{ old('description') }}</textarea>

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
                            checked
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


            <div class="text-end">

                <a
                    href="{{ route(
                        'pensions-administration.updates.employer-groups.index'
                    ) }}"
                    class="btn btn-light"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    Save Employer Group
                </button>

            </div>

        </div>

    </div>

</form>

@endsection