@extends('layouts.app')

@section('title', 'Edit Employer')

@section('page-heading', 'Edit Employer')

@section('content')

@include('pensions-administration.partials.navigation')


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

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <div>

                <h4 class="header-title mb-1">
                    {{ $employer->name }}
                </h4>

                <p class="text-muted mb-0">
                    Employer Number:
                    <strong>{{ $employer->employer_number }}</strong>
                </p>

            </div>


            <a href="{{ route('pensions-administration.updates.employers.show', $employer) }}"
               class="btn btn-light">

                <i class="mdi mdi-arrow-left me-1"></i>
                Back to Employer

            </a>

        </div>

    </div>
</div>


<form method="POST"
      action="{{ route('pensions-administration.updates.employers.update', $employer) }}">

    @csrf
    @method('PUT')


    {{-- =====================================================
         EMPLOYER REFERENCES
    ====================================================== --}}

    <div class="card">
        <div class="card-body">

            <h4 class="header-title mb-3">
                Employer References
            </h4>


            <div class="row">

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        PENERP Employer Number
                    </label>

                    <input type="text"
                           name="employer_number"
                           class="form-control"
                           value="{{ old('employer_number', $employer->employer_number) }}"
                           required>

                    <small class="text-muted">
                        Permanent PENERP employer reference.
                    </small>

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        PenAd Employer Number
                    </label>

                    <input type="text"
                           name="penad_employer_number"
                           class="form-control"
                           value="{{ old('penad_employer_number', $employer->penad_employer_number) }}">

                    <small class="text-muted">
                        Historical PenAd employer reference.
                    </small>

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Fundworx Employer Number
                    </label>

                    <input type="text"
                           name="fundworx_employer_number"
                           class="form-control"
                           value="{{ old('fundworx_employer_number', $employer->fundworx_employer_number) }}">

                    <small class="text-muted">
                        Historical Fundworx employer reference.
                    </small>

                </div>

            </div>

        </div>
    </div>


    {{-- =====================================================
         EMPLOYER DETAILS
    ====================================================== --}}

    <div class="card">
        <div class="card-body">

            <h4 class="header-title mb-3">
                Employer Details
            </h4>


            <div class="row">

                <div class="col-md-8 mb-3">

                    <label class="form-label">
                        Employer Name
                        <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                           name="name"
                           class="form-control"
                           value="{{ old('name', $employer->name) }}"
                           required>

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Short Name
                    </label>

                    <input type="text"
                           name="short_name"
                           class="form-control"
                           value="{{ old('short_name', $employer->short_name) }}">

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Employer Group
                    </label>

                    <select name="employer_group_id"
                            class="form-select">

                        <option value="">
                            Select Employer Group
                        </option>


                        @foreach($groups as $group)

                            <option value="{{ $group->id }}"
                                    @selected(
                                        old(
                                            'employer_group_id',
                                            $employer->employer_group_id
                                        )
                                        == $group->id
                                    )>

                                {{ $group->code }}
                                -
                                {{ $group->name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Corporate Form
                    </label>

                    <input type="text"
                           name="corporate_form"
                           class="form-control"
                           value="{{ old('corporate_form', $employer->corporate_form) }}"
                           placeholder="e.g. City Council, RDC, Municipality">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Fund Number
                    </label>

                    <input type="text"
                           name="fund_number"
                           class="form-control"
                           value="{{ old('fund_number', $employer->fund_number) }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Scheme Code
                    </label>

                    <input type="text"
                           name="scheme_code"
                           class="form-control"
                           value="{{ old('scheme_code', $employer->scheme_code) }}">

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        TPIN
                    </label>

                    <input type="text"
                           name="tpin"
                           class="form-control"
                           value="{{ old('tpin', $employer->tpin) }}">

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Business Registration Number
                    </label>

                    <input type="text"
                           name="business_registration_number"
                           class="form-control"
                           value="{{ old(
                               'business_registration_number',
                               $employer->business_registration_number
                           ) }}">

                </div>


                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select name="status"
                            class="form-select"
                            required>

                        <option value="active"
                                @selected(
                                    old('status', $employer->status) === 'active'
                                )>

                            Active

                        </option>


                        <option value="inactive"
                                @selected(
                                    old('status', $employer->status) === 'inactive'
                                )>

                            Inactive

                        </option>

                    </select>

                </div>


                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Active
                    </label>

                    <select name="is_active"
                            class="form-select"
                            required>

                        <option value="1"
                                @selected(
                                    old(
                                        'is_active',
                                        $employer->is_active ? 1 : 0
                                    ) == 1
                                )>

                            Yes

                        </option>


                        <option value="0"
                                @selected(
                                    old(
                                        'is_active',
                                        $employer->is_active ? 1 : 0
                                    ) == 0
                                )>

                            No

                        </option>

                    </select>

                </div>

            </div>

        </div>
    </div>


    {{-- =====================================================
         CONTACT DETAILS
    ====================================================== --}}

    <div class="card">
        <div class="card-body">

            <h4 class="header-title mb-3">
                Contact Information
            </h4>


            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Email Address
                    </label>

                    <input type="email"
                           name="email"
                           class="form-control"
                           value="{{ old('email', $employer->email) }}">

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Telephone
                    </label>

                    <input type="text"
                           name="telephone"
                           class="form-control"
                           value="{{ old('telephone', $employer->telephone) }}">

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Physical Address
                    </label>

                    <textarea name="physical_address"
                              class="form-control"
                              rows="5">{{ old(
                                  'physical_address',
                                  $employer->physical_address
                              ) }}</textarea>

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Postal Address
                    </label>

                    <textarea name="postal_address"
                              class="form-control"
                              rows="5">{{ old(
                                  'postal_address',
                                  $employer->postal_address
                              ) }}</textarea>

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

                <a href="{{ route('pensions-administration.updates.employers.show', $employer) }}"
                   class="btn btn-light">

                    <i class="mdi mdi-close me-1"></i>
                    Cancel

                </a>


                <button type="submit"
                        class="btn btn-primary">

                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Save Changes

                </button>

            </div>

        </div>
    </div>

</form>

@endsection