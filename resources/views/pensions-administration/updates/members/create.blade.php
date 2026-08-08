@extends('layouts.app')

@section('title', 'Create Member')

@section('page-heading', 'Create Member')

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
        'pensions-administration.updates.members.store'
    ) }}"
>

    @csrf


    {{-- =====================================================
         LEGACY REFERENCES
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Membership References
            </h4>

            <p class="card-title-desc">
                PENERP member number will be generated automatically.
            </p>


            <div class="row">

                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            PenAd Member Number
                        </label>

                        <input
                            type="text"
                            name="penad_member_number"
                            class="form-control"
                            value="{{ old(
                                'penad_member_number'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Fundworx Member Number
                        </label>

                        <input
                            type="text"
                            name="fundworx_member_number"
                            class="form-control"
                            value="{{ old(
                                'fundworx_member_number'
                            ) }}"
                        >

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- =====================================================
         PERSONAL DETAILS
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Personal Details
            </h4>


            <div class="row">


                <div class="col-md-2">

                    <div class="mb-3">

                        <label class="form-label">
                            Title
                        </label>

                        <select
                            name="title"
                            class="form-select"
                        >

                            <option value="">
                                -
                            </option>

                            <option value="Mr">
                                Mr
                            </option>

                            <option value="Mrs">
                                Mrs
                            </option>

                            <option value="Ms">
                                Ms
                            </option>

                            <option value="Dr">
                                Dr
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Surname
                        </label>

                        <input
                            type="text"
                            name="surname"
                            class="form-control"
                            value="{{ old('surname') }}"
                            required
                        >

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            First Names
                        </label>

                        <input
                            type="text"
                            name="first_names"
                            class="form-control"
                            value="{{ old('first_names') }}"
                            required
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            National ID
                        </label>

                        <input
                            type="text"
                            name="national_id"
                            class="form-control"
                            value="{{ old('national_id') }}"
                        >

                        <div class="form-text">
                            Required for active members.
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="mb-3">

                        <label class="form-label">
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                            class="form-control"
                            value="{{ old('date_of_birth') }}"
                        >

                    </div>

                </div>


                <div class="col-md-2">

                    <div class="mb-3">

                        <label class="form-label">
                            Gender
                        </label>

                        <select
                            name="gender"
                            class="form-select"
                        >

                            <option value="">
                                Select
                            </option>

                            <option value="Male">
                                Male
                            </option>

                            <option value="Female">
                                Female
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="mb-3">

                        <label class="form-label">
                            Marital Status
                        </label>

                        <select
                            name="marital_status"
                            class="form-select"
                        >

                            <option value="">
                                Select
                            </option>

                            <option value="Single">
                                Single
                            </option>

                            <option value="Married">
                                Married
                            </option>

                            <option value="Divorced">
                                Divorced
                            </option>

                            <option value="Widowed">
                                Widowed
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-md-6">

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
                            Cell Number
                        </label>

                        <input
                            type="text"
                            name="cell_number"
                            class="form-control"
                            value="{{ old(
                                'cell_number'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Date Joined Fund
                        </label>

                        <input
                            type="date"
                            name="date_joined_fund"
                            class="form-control"
                            value="{{ old(
                                'date_joined_fund'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Membership Status
                        </label>

                        <select
                            name="membership_status"
                            class="form-select"
                            required
                        >

                            <option value="active">
                                Active
                            </option>

                            <option value="dormant">
                                Dormant
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                            <option value="suspended">
                                Suspended
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- =====================================================
         EMPLOYMENT
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Current Employment
            </h4>


            <div class="row">


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Employer / Local Authority
                        </label>

                        <select
                            name="employer_id"
                            class="form-select"
                        >

                            <option value="">
                                Select Employer
                            </option>

                            @foreach($employers as $employer)

                                <option
                                    value="{{ $employer->id }}"
                                >
                                    {{ $employer->name }}

                                    @if(
                                        $employer->employerGroup
                                    )
                                        -
                                        {{
                                            $employer
                                                ->employerGroup
                                                ->code
                                        }}
                                    @endif

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="mb-3">

                        <label class="form-label">
                            Staff Number
                        </label>

                        <input
                            type="text"
                            name="staff_number"
                            class="form-control"
                            value="{{ old(
                                'staff_number'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="mb-3">

                        <label class="form-label">
                            Vote Number
                        </label>

                        <input
                            type="text"
                            name="vote_number"
                            class="form-control"
                            value="{{ old(
                                'vote_number'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Date Joined Employer
                        </label>

                        <input
                            type="date"
                            name="date_joined_employer"
                            class="form-control"
                            value="{{ old(
                                'date_joined_employer'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Department
                        </label>

                        <input
                            type="text"
                            name="department"
                            class="form-control"
                            value="{{ old(
                                'department'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Branch
                        </label>

                        <input
                            type="text"
                            name="branch"
                            class="form-control"
                            value="{{ old('branch') }}"
                        >

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         ADDRESS
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Address
            </h4>


            <div class="row">

                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Physical Address
                        </label>

                        <input
                            type="text"
                            name="physical_address_1"
                            class="form-control mb-2"
                            value="{{ old(
                                'physical_address_1'
                            ) }}"
                        >

                        <input
                            type="text"
                            name="physical_address_2"
                            class="form-control"
                            value="{{ old(
                                'physical_address_2'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="mb-3">

                        <label class="form-label">
                            City
                        </label>

                        <input
                            type="text"
                            name="physical_city"
                            class="form-control"
                            value="{{ old(
                                'physical_city'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="mb-3">

                        <label class="form-label">
                            Country
                        </label>

                        <input
                            type="text"
                            name="physical_country"
                            class="form-control"
                            value="{{ old(
                                'physical_country',
                                'Zimbabwe'
                            ) }}"
                        >

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="text-end mb-4">

        <a
            href="{{ route(
                'pensions-administration.updates.members.index'
            ) }}"
            class="btn btn-light"
        >
            Cancel
        </a>

        <button
            type="submit"
            class="btn btn-success"
        >
            Create Member
        </button>

    </div>

</form>

@endsection