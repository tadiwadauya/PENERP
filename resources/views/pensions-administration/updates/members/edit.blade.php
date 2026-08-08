@extends('layouts.app')

@section('title', 'Edit Member')

@section('page-heading', 'Edit Member')


@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.members.show',
            $member
        ) }}"
        class="btn btn-light"
    >
        <i class="mdi mdi-arrow-left me-1"></i>
        Back to Member
    </a>

@endsection


@section('content')

@include(
    'pensions-administration.partials.navigation'
)
@if($errors->any())

    <div
        class="alert alert-danger alert-dismissible fade show"
    >

        <strong>
            Please correct the following:
        </strong>

        <ul class="mb-0 mt-2">

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
        'pensions-administration.updates.members.update',
        $member
    ) }}"
>

    @csrf
    @method('PUT')


    {{-- =====================================================
         REFERENCES
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Membership References
            </h4>


            <div class="row">

                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            PENERP Member Number
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="{{ $member->member_number }}"
                            disabled
                        >

                        <div class="form-text">
                            PENERP member number cannot be changed.
                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            PenAd Member Number
                        </label>

                        <input
                            type="text"
                            name="penad_member_number"
                            class="form-control"
                            value="{{ old(
                                'penad_member_number',
                                $member->penad_member_number
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Fundworx Member Number
                        </label>

                        <input
                            type="text"
                            name="fundworx_member_number"
                            class="form-control"
                            value="{{ old(
                                'fundworx_member_number',
                                $member->fundworx_member_number
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

                            @foreach([
                                'Mr',
                                'Mrs',
                                'Ms',
                                'Dr'
                            ] as $title)

                                <option
                                    value="{{ $title }}"
                                    @selected(
                                        old(
                                            'title',
                                            $member->title
                                        )
                                        === $title
                                    )
                                >
                                    {{ $title }}
                                </option>

                            @endforeach

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
                            value="{{ old(
                                'surname',
                                $member->surname
                            ) }}"
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
                            value="{{ old(
                                'first_names',
                                $member->first_names
                            ) }}"
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
                            value="{{ old(
                                'national_id',
                                $member->national_id
                            ) }}"
                        >

                        <div class="form-text">
                            Required for active members.
                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                            class="form-control"
                            value="{{ old(
                                'date_of_birth',
                                $member->date_of_birth
                                    ?->format('Y-m-d')
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

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

                            @foreach([
                                'Male',
                                'Female'
                            ] as $gender)

                                <option
                                    value="{{ $gender }}"
                                    @selected(
                                        old(
                                            'gender',
                                            $member->gender
                                        )
                                        === $gender
                                    )
                                >
                                    {{ $gender }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="col-md-4">

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

                            @foreach([
                                'Single',
                                'Married',
                                'Divorced',
                                'Widowed'
                            ] as $status)

                                <option
                                    value="{{ $status }}"
                                    @selected(
                                        old(
                                            'marital_status',
                                            $member->marital_status
                                        )
                                        === $status
                                    )
                                >
                                    {{ $status }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Occupation
                        </label>

                        <input
                            type="text"
                            name="occupation"
                            class="form-control"
                            value="{{ old(
                                'occupation',
                                $member->occupation
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
                                'date_joined_fund',
                                $member->date_joined_fund
                                    ?->format('Y-m-d')
                            ) }}"
                        >

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         CONTACT
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Contact Details
            </h4>


            <div class="row">


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="{{ old(
                                'email',
                                $member->email
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Secondary Email
                        </label>

                        <input
                            type="email"
                            name="secondary_email"
                            class="form-control"
                            value="{{ old(
                                'secondary_email',
                                $member->secondary_email
                            ) }}"
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
                                'cell_number',
                                $member->cell_number
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Secondary Cell Number
                        </label>

                        <input
                            type="text"
                            name="secondary_cell_number"
                            class="form-control"
                            value="{{ old(
                                'secondary_cell_number',
                                $member->secondary_cell_number
                            ) }}"
                        >

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         CURRENT EMPLOYMENT
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Current Employment
            </h4>


            <div class="alert alert-info">

                <i class="mdi mdi-information-outline me-1"></i>

                Changing the employer does not overwrite the
                previous employment. PENERP will close the old
                employment record and create a new employment
                history record.

            </div>


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
                                No Current Employer
                            </option>


                            @foreach(
                                $employers
                                as $employer
                            )

                                <option
                                    value="{{ $employer->id }}"
                                    @selected(
                                        old(
                                            'employer_id',
                                            $member
                                                ->currentEmployment
                                                ?->employer_id
                                        )
                                        == $employer->id
                                    )
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
                                'staff_number',
                                $member
                                    ->currentEmployment
                                    ?->staff_number
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
                                'vote_number',
                                $member
                                    ->currentEmployment
                                    ?->vote_number
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
                                'date_joined_employer',
                                $member
                                    ->currentEmployment
                                    ?->date_joined_employer
                                    ?->format('Y-m-d')
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
                                'department',
                                $member
                                    ->currentEmployment
                                    ?->department
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
                            value="{{ old(
                                'branch',
                                $member
                                    ->currentEmployment
                                    ?->branch
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Employment Change Effective Date
                        </label>

                        <input
                            type="date"
                            name="employment_effective_date"
                            class="form-control"
                            value="{{ old(
                                'employment_effective_date'
                            ) }}"
                        >

                        <div class="form-text">
                            Required mainly when changing employer.
                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         MEMBERSHIP STATUS
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Membership Status
            </h4>


            <div class="row">


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="membership_status"
                            class="form-select"
                            required
                        >

                            @foreach([
                                'active' => 'Active',
                                'dormant' => 'Dormant',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                            ] as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        old(
                                            'membership_status',
                                            $member
                                                ->membership_status
                                        )
                                        === $value
                                    )
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Movement Type
                        </label>

                        <select
                            name="movement_type"
                            class="form-select"
                        >

                            <option value="">
                                Select if status changes
                            </option>

                            <option value="REINSTATEMENT">
                                Reinstatement
                            </option>

                            <option value="SUSPENSION">
                                Suspension
                            </option>

                            <option value="DORMANT">
                                Dormant
                            </option>

                            <option value="INACTIVE">
                                Inactive
                            </option>

                            <option value="CORRECTION">
                                Correction
                            </option>

                            <option value="STATUS_CHANGE">
                                Other Status Change
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Status Effective Date
                        </label>

                        <input
                            type="date"
                            name="status_effective_date"
                            class="form-control"
                            value="{{ old(
                                'status_effective_date'
                            ) }}"
                        >

                    </div>

                </div>


                <div class="col-12">

                    <div class="mb-3">

                        <label class="form-label">
                            Status Change Reason
                        </label>

                        <textarea
                            name="status_change_reason"
                            class="form-control"
                            rows="3"
                            placeholder="Explain the reason where membership status is changing"
                        >{{ old(
                            'status_change_reason'
                        ) }}</textarea>

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
                Address Information
            </h4>


            <div class="row">


                <div class="col-md-6">

                    <h6>
                        Physical Address
                    </h6>

                    <div class="mb-2">

                        <input
                            type="text"
                            name="physical_address_1"
                            class="form-control"
                            value="{{ old(
                                'physical_address_1',
                                $member
                                    ->physical_address_1
                            ) }}"
                            placeholder="Address line 1"
                        >

                    </div>


                    <div class="mb-2">

                        <input
                            type="text"
                            name="physical_address_2"
                            class="form-control"
                            value="{{ old(
                                'physical_address_2',
                                $member
                                    ->physical_address_2
                            ) }}"
                            placeholder="Address line 2"
                        >

                    </div>


                    <div class="mb-2">

                        <input
                            type="text"
                            name="physical_address_3"
                            class="form-control"
                            value="{{ old(
                                'physical_address_3',
                                $member
                                    ->physical_address_3
                            ) }}"
                            placeholder="Address line 3"
                        >

                    </div>


                    <div class="row">

                        <div class="col-md-4">

                            <input
                                type="text"
                                name="physical_suburb"
                                class="form-control"
                                value="{{ old(
                                    'physical_suburb',
                                    $member
                                        ->physical_suburb
                                ) }}"
                                placeholder="Suburb"
                            >

                        </div>


                        <div class="col-md-4">

                            <input
                                type="text"
                                name="physical_city"
                                class="form-control"
                                value="{{ old(
                                    'physical_city',
                                    $member
                                        ->physical_city
                                ) }}"
                                placeholder="City"
                            >

                        </div>


                        <div class="col-md-4">

                            <input
                                type="text"
                                name="physical_country"
                                class="form-control"
                                value="{{ old(
                                    'physical_country',
                                    $member
                                        ->physical_country
                                    ?? 'Zimbabwe'
                                ) }}"
                            >

                        </div>

                    </div>

                </div>



                <div class="col-md-6">

                    <h6>
                        Postal Address
                    </h6>

                    <div class="mb-2">

                        <input
                            type="text"
                            name="postal_address_1"
                            class="form-control"
                            value="{{ old(
                                'postal_address_1',
                                $member
                                    ->postal_address_1
                            ) }}"
                            placeholder="Postal address line 1"
                        >

                    </div>


                    <div class="mb-2">

                        <input
                            type="text"
                            name="postal_address_2"
                            class="form-control"
                            value="{{ old(
                                'postal_address_2',
                                $member
                                    ->postal_address_2
                            ) }}"
                            placeholder="Postal address line 2"
                        >

                    </div>


                    <div class="mb-2">

                        <input
                            type="text"
                            name="postal_address_3"
                            class="form-control"
                            value="{{ old(
                                'postal_address_3',
                                $member
                                    ->postal_address_3
                            ) }}"
                            placeholder="Postal address line 3"
                        >

                    </div>


                    <div class="row">

                        <div class="col-md-6">

                            <input
                                type="text"
                                name="postal_city"
                                class="form-control"
                                value="{{ old(
                                    'postal_city',
                                    $member
                                        ->postal_city
                                ) }}"
                                placeholder="City"
                            >

                        </div>


                        <div class="col-md-6">

                            <input
                                type="text"
                                name="postal_country"
                                class="form-control"
                                value="{{ old(
                                    'postal_country',
                                    $member
                                        ->postal_country
                                    ?? 'Zimbabwe'
                                ) }}"
                            >

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    <div class="card">

        <div class="card-body">

            <div
                class="d-flex justify-content-between"
            >

                <a
                    href="{{ route(
                        'pensions-administration.updates.members.show',
                        $member
                    ) }}"
                    class="btn btn-light"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="mdi mdi-content-save me-1"></i>
                    Save Member Changes
                </button>

            </div>

        </div>

    </div>

</form>

@endsection