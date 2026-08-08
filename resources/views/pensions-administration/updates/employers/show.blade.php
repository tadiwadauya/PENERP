@extends('layouts.app')

@section('title', 'Employer Details')

@section('page-heading', 'Employer Details')

@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.employers.edit',
            $employer
        ) }}"
        class="btn btn-primary"
    >
        <i class="mdi mdi-pencil me-1"></i>
        Edit Employer
    </a>

@endsection


@section('content')
@include(
    'pensions-administration.partials.navigation'
)


@if(session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

@endif


<div class="card">

    <div class="card-body">

        <div class="d-flex justify-content-between">

            <div>

                <h3 class="mb-1">
                    {{ $employer->name }}
                </h3>

                <p class="text-muted mb-0">
                    {{ $employer->employer_number }}
                </p>

            </div>


            <div>

                @if($employer->is_active)

                    <span class="badge bg-success">
                        Active
                    </span>

                @else

                    <span class="badge bg-secondary">
                        Inactive
                    </span>

                @endif

            </div>

        </div>


        <hr>


        <div class="row">


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    PENERP Employer Number
                </small>

                <strong>
                    {{ $employer->employer_number }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    PenAd Employer Number
                </small>

                <strong>
                    {{
                        $employer->penad_employer_number
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Fundworx Employer Number
                </small>

                <strong>
                    {{
                        $employer->fundworx_employer_number
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Employer Group
                </small>

                <strong>
                    {{
                        $employer->employerGroup?->name
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Short Name
                </small>

                <strong>
                    {{ $employer->short_name ?? '-' }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Corporate Form
                </small>

                <strong>
                    {{ $employer->corporate_form ?? '-' }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Fund Number
                </small>

                <strong>
                    {{ $employer->fund_number ?? '-' }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    TPIN
                </small>

                <strong>
                    {{ $employer->tpin ?? '-' }}
                </strong>

            </div>


            <div class="col-md-4 mb-4">

                <small class="text-muted d-block">
                    Registration Number
                </small>

                <strong>
                    {{
                        $employer
                            ->business_registration_number
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-6 mb-4">

                <small class="text-muted d-block">
                    Email
                </small>

                <strong>
                    {{ $employer->email ?? '-' }}
                </strong>

            </div>


            <div class="col-md-6 mb-4">

                <small class="text-muted d-block">
                    Telephone
                </small>

                <strong>
                    {{ $employer->telephone ?? '-' }}
                </strong>

            </div>


            <div class="col-md-6 mb-4">

                <small class="text-muted d-block">
                    Physical Address
                </small>

                <strong>
                    {{
                        $employer->physical_address
                        ?? '-'
                    }}
                </strong>

            </div>


            <div class="col-md-6 mb-4">

                <small class="text-muted d-block">
                    Postal Address
                </small>

                <strong>
                    {{
                        $employer->postal_address
                        ?? '-'
                    }}
                </strong>

            </div>

        </div>

    </div>

</div>


<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Membership Summary
        </h4>

        <div class="row">

            <div class="col-md-4">

                <div class="border rounded p-3">

                    <small class="text-muted">
                        Current Members
                    </small>

                    <h3 class="mb-0">
                        {{
                            $employer
                                ->current_member_employments_count
                        }}
                    </h3>

                </div>

            </div>

        </div>

    </div>

</div>


<a
    href="{{ route(
        'pensions-administration.updates.employers.index'
    ) }}"
    class="btn btn-light"
>
    <i class="mdi mdi-arrow-left me-1"></i>
    Back to Employers
</a>

@endsection