@extends('layouts.app')

@section('title', 'Create Employer')

@section('page-heading', 'Create Employer')

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
        'pensions-administration.updates.employers.store'
    ) }}"
>

    @csrf


    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Employer Identification
            </h4>


            <div class="row">


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            PenAd Employer Number
                        </label>

                        <input
                            type="text"
                            name="penad_employer_number"
                            class="form-control"
                            value="{{ old(
                                'penad_employer_number'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Fundworx Employer Number
                        </label>

                        <input
                            type="text"
                            name="fundworx_employer_number"
                            class="form-control"
                            value="{{ old(
                                'fundworx_employer_number'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Employer Group
                        </label>

                        <select
                            name="employer_group_id"
                            class="form-select"
                        >

                            <option value="">
                                Select Group
                            </option>

                            @foreach($groups as $group)

                                <option
                                    value="{{ $group->id }}"
                                >
                                    {{ $group->code }}
                                    -
                                    {{ $group->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="col-md-8">

                    <div class="mb-3">

                        <label class="form-label">
                            Employer Name
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


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Short Name
                        </label>

                        <input
                            type="text"
                            name="short_name"
                            class="form-control"
                            value="{{ old('short_name') }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Corporate Form
                        </label>

                        <input
                            type="text"
                            name="corporate_form"
                            class="form-control"
                            value="{{ old(
                                'corporate_form',
                                'Local Authority'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Fund Number
                        </label>

                        <input
                            type="text"
                            name="fund_number"
                            class="form-control"
                            value="{{ old('fund_number') }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            TPIN
                        </label>

                        <input
                            type="text"
                            name="tpin"
                            class="form-control"
                            value="{{ old('tpin') }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Registration Number
                        </label>

                        <input
                            type="text"
                            name="business_registration_number"
                            class="form-control"
                            value="{{ old(
                                'business_registration_number'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Telephone
                        </label>

                        <input
                            type="text"
                            name="telephone"
                            class="form-control"
                            value="{{ old('telephone') }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="{{ old('email') }}"
                        >

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Postal Address
                        </label>

                        <textarea
                            name="postal_address"
                            class="form-control"
                            rows="3"
                        >{{ old('postal_address') }}</textarea>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Physical Address
                        </label>

                        <textarea
                            name="physical_address"
                            class="form-control"
                            rows="3"
                        >{{ old('physical_address') }}</textarea>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                            <option value="dormant">
                                Dormant
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-md-6">

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <div class="form-check form-switch mt-4">

                        <input
                            type="checkbox"
                            class="form-check-input"
                            name="is_active"
                            value="1"
                            checked
                        >

                        <label class="form-check-label">
                            Active Employer
                        </label>

                    </div>

                </div>


            </div>


            <div class="text-end">

                <a
                    href="{{ route(
                        'pensions-administration.updates.employers.index'
                    ) }}"
                    class="btn btn-light"
                >
                    Cancel
                </a>

                <button
                    class="btn btn-success"
                    type="submit"
                >
                    Create Employer
                </button>

            </div>

        </div>

    </div>

</form>

@endsection