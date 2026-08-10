@extends('layouts.app')

@section('title', 'Correct Import Row')

@section('page-heading', 'Correct Membership Import Row')

@section('content')

@include('pensions-administration.partials.navigation')

@php
    $data = $row->normalized_data ?? [];
@endphp


@if($errors->any())

    <div class="alert alert-danger">

        <strong>
            Please correct the following:
        </strong>

        <ul class="mb-0 mt-2">

            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach

        </ul>

    </div>

@endif


<div class="card">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h4 class="header-title mb-1">
                    Excel Row {{ $row->row_number }}
                </h4>

                <p class="text-muted mb-0">
                    Changes are made to the staged import record only.
                </p>

            </div>


            <a href="{{ route('pensions-administration.updates.imports.review', $batch) }}"
               class="btn btn-light">

                <i class="mdi mdi-arrow-left me-1"></i>
                Back to Review

            </a>

        </div>

    </div>
</div>


<form method="POST"
      action="{{ route('pensions-administration.updates.imports.rows.update', [$batch, $row]) }}">

    @csrf
    @method('PUT')


    {{-- =====================================================
         MEMBER NAME
    ====================================================== --}}

    <div class="card">
        <div class="card-body">

            <h4 class="header-title mb-3">
                Member Details
            </h4>


            <div class="row">

                <div class="col-md-2 mb-3">

                    <label class="form-label">
                        Title
                    </label>

                    <input type="text"
                           name="title"
                           class="form-control"
                           value="{{ old('title', $data['title'] ?? '') }}">

                </div>


                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Surname
                    </label>

                    <input type="text"
                           name="surname"
                           class="form-control"
                           value="{{ old('surname', $data['surname'] ?? '') }}">

                </div>


                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        First Names
                    </label>

                    <input type="text"
                           name="first_names"
                           class="form-control"
                           value="{{ old('first_names', $data['first_names'] ?? '') }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Other Names
                    </label>

                    <input type="text"
                           name="other_names"
                           class="form-control"
                           value="{{ old('other_names', $data['other_names'] ?? '') }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Maiden Name
                    </label>

                    <input type="text"
                           name="maiden_name"
                           class="form-control"
                           value="{{ old('maiden_name', $data['maiden_name'] ?? '') }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        National ID
                    </label>

                    <input type="text"
                           name="national_id"
                           class="form-control"
                           value="{{ old('national_id', $data['national_id'] ?? '') }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Date of Birth
                    </label>

                    <input type="date"
                           name="date_of_birth"
                           class="form-control"
                           value="{{ old('date_of_birth', $data['date_of_birth'] ?? '') }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Gender
                    </label>

                    <select name="gender" class="form-select">

                        <option value="">
                            Select
                        </option>

                        <option value="Male"
                                @selected(old('gender', $data['gender'] ?? '') === 'Male')}>
                            Male
                        </option>

                        <option value="Female"
                                @selected(old('gender', $data['gender'] ?? '') === 'Female')}>
                            Female
                        </option>

                    </select>

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Marital Status
                    </label>

                    <select name="marital_status" class="form-select">

                        <option value="">
                            Select
                        </option>

                        @foreach([
                            'Single',
                            'Married',
                            'Divorced',
                            'Widowed'
                        ] as $status)

                            <option value="{{ $status }}"
                                    @selected(old('marital_status', $data['marital_status'] ?? '') === $status)>

                                {{ $status }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Occupation
                    </label>

                    <input type="text"
                           name="occupation"
                           class="form-control"
                           value="{{ old('occupation', $data['occupation'] ?? '') }}">

                </div>

            </div>

        </div>
    </div>


    {{-- =====================================================
         LEGACY REFERENCES
    ====================================================== --}}

    <div class="card">
        <div class="card-body">

            <h4 class="header-title mb-3">
                Legacy References
            </h4>


            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        PenAd Member Number
                    </label>

                    <input type="text"
                           name="penad_member_number"
                           class="form-control"
                           value="{{ old('penad_member_number', $data['penad_member_number'] ?? '') }}">

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Fundworx Member Number
                    </label>

                    <input type="text"
                           name="fundworx_member_number"
                           class="form-control"
                           value="{{ old('fundworx_member_number', $data['fundworx_member_number'] ?? '') }}">

                </div>

            </div>

        </div>
    </div>


    {{-- =====================================================
         EMPLOYER
    ====================================================== --}}

    <div class="card">
        <div class="card-body">

            <h4 class="header-title mb-3">
                Employer & Employment
            </h4>


            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Select Employer
                    </label>

                    <select name="selected_employer_id"
                            class="form-select">

                        <option value="">
                            Use employer references below
                        </option>

                        @foreach($employers as $employer)

                            <option value="{{ $employer->id }}"
                                    @selected($row->matched_employer_id == $employer->id)>

                                {{ $employer->employer_number }}
                                -
                                {{ $employer->name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-2 mb-3">

                    <label class="form-label">
                        PENERP Employer
                    </label>

                    <input type="text"
                           name="penerp_employer_number"
                           class="form-control"
                           value="{{ old('penerp_employer_number', $data['penerp_employer_number'] ?? '') }}">

                </div>


                <div class="col-md-2 mb-3">

                    <label class="form-label">
                        PenAd Employer
                    </label>

                    <input type="text"
                           name="penad_employer_number"
                           class="form-control"
                           value="{{ old('penad_employer_number', $data['penad_employer_number'] ?? '') }}">

                </div>


                <div class="col-md-2 mb-3">

                    <label class="form-label">
                        Fundworx Employer
                    </label>

                    <input type="text"
                           name="fundworx_employer_number"
                           class="form-control"
                           value="{{ old('fundworx_employer_number', $data['fundworx_employer_number'] ?? '') }}">

                </div>


                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Staff Number
                    </label>

                    <input type="text"
                           name="staff_number"
                           class="form-control"
                           value="{{ old('staff_number', $data['staff_number'] ?? '') }}">

                </div>


                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Vote Number
                    </label>

                    <input type="text"
                           name="vote_number"
                           class="form-control"
                           value="{{ old('vote_number', $data['vote_number'] ?? '') }}">

                </div>


                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Date Joined Employer
                    </label>

                    <input type="date"
                           name="date_joined_employer"
                           class="form-control"
                           value="{{ old('date_joined_employer', $data['date_joined_employer'] ?? '') }}">

                </div>


                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Date Joined Fund
                    </label>

                    <input type="date"
                           name="date_joined_fund"
                           class="form-control"
                           value="{{ old('date_joined_fund', $data['date_joined_fund'] ?? '') }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Membership Status
                    </label>

                    <select name="membership_status"
                            class="form-select"
                            required>

                        @foreach([
                            'active' => 'Active',
                            'dormant' => 'Dormant',
                            'inactive' => 'Inactive',
                            'suspended' => 'Suspended',
                        ] as $value => $label)

                            <option value="{{ $value }}"
                                    @selected(old('membership_status', $data['membership_status'] ?? '') === $value)>

                                {{ $label }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Department
                    </label>

                    <input type="text"
                           name="department"
                           class="form-control"
                           value="{{ old('department', $data['department'] ?? '') }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Branch
                    </label>

                    <input type="text"
                           name="branch"
                           class="form-control"
                           value="{{ old('branch', $data['branch'] ?? '') }}">

                </div>

            </div>

        </div>
    </div>


    {{-- =====================================================
         CONTACT
    ====================================================== --}}

    <div class="card">
        <div class="card-body">

            <h4 class="header-title mb-3">
                Contact Information
            </h4>


            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Email
                    </label>

                    <input type="email"
                           name="email"
                           class="form-control"
                           value="{{ old('email', $data['email'] ?? '') }}">

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Secondary Email
                    </label>

                    <input type="email"
                           name="secondary_email"
                           class="form-control"
                           value="{{ old('secondary_email', $data['secondary_email'] ?? '') }}">

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Cell Number
                    </label>

                    <input type="text"
                           name="cell_number"
                           class="form-control"
                           value="{{ old('cell_number', $data['cell_number'] ?? '') }}">

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Secondary Cell Number
                    </label>

                    <input type="text"
                           name="secondary_cell_number"
                           class="form-control"
                           value="{{ old('secondary_cell_number', $data['secondary_cell_number'] ?? '') }}">

                </div>

            </div>

        </div>
    </div>


    {{-- =====================================================
         ACTIONS
    ====================================================== --}}

    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between">

                <a href="{{ route('pensions-administration.updates.imports.review', $batch) }}"
                   class="btn btn-light">

                    Cancel

                </a>


                <button type="submit"
                        class="btn btn-primary">

                    <i class="mdi mdi-check-circle-outline me-1"></i>

                    Save & Revalidate Row

                </button>

            </div>

        </div>
    </div>

</form>

@endsection