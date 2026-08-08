@extends('layouts.app')

@section('title', 'Password Policy Report')

@section(
    'page-heading',
    'Password Policy Report'
)


@push('styles')

    <link
        href="{{ asset('layouts/assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}"
        rel="stylesheet"
        type="text/css"
    >

    <link
        href="{{ asset('layouts/assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}"
        rel="stylesheet"
        type="text/css"
    >

@endpush


@section('page-actions')

    <a
        href="{{ route(
            'user-management.password-policies.edit'
        ) }}"
        class="btn btn-light"
    >

        <i class="mdi mdi-arrow-left me-1"></i>

        Back to Policy

    </a>

@endsection


@section('content')


{{-- =========================================================
     REPORT HEADER
========================================================= --}}

<div class="card">

    <div class="card-body">


        <div
            class="d-flex flex-column flex-md-row align-items-md-center"
        >

            <div class="avatar-lg me-md-4 mb-3 mb-md-0">

                <span
                    class="avatar-title rounded-circle bg-soft-primary text-primary font-size-24"
                >

                    <i
                        class="mdi mdi-shield-key-outline"
                    ></i>

                </span>

            </div>


            <div class="flex-grow-1">

                <h4 class="mb-1">
                    {{ $policy->name }}
                </h4>


                <p class="text-muted mb-2">

                    LAPF Pension Fund System Password
                    & Authentication Security Policy

                </p>


                <div class="d-flex flex-wrap gap-2">

                    @if($policy->is_active)

                        <span
                            class="badge bg-soft-success text-success"
                        >
                            Active Policy
                        </span>

                    @endif


                    @if($policy->is_default)

                        <span
                            class="badge bg-soft-primary text-primary"
                        >
                            Default Policy
                        </span>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     AUDITOR INFORMATION
========================================================= --}}

<div class="alert alert-info">

    <i class="mdi mdi-information-outline me-1"></i>

    This report represents the password and account
    security policy configured in the LAPF Pension Fund System
    as at

    <strong>
        {{ now()->format('d M Y H:i') }}
    </strong>.

</div>



{{-- =========================================================
     POLICY REPORT
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Password Policy Configuration
        </h4>


        <p class="card-title-desc">

            Use the export buttons below to provide the
            configured policy to Internal Audit, External Audit,
            management or other authorised reviewers.

        </p>


        <div class="table-responsive">

            <table
                id="policy-report-table"
                class="table table-striped table-bordered"
                style="width:100%;"
            >

                <thead>

                    <tr>

                        <th>
                            Control Area
                        </th>

                        <th>
                            Policy Setting
                        </th>

                        <th>
                            Configured Value
                        </th>

                    </tr>

                </thead>


                <tbody>


                    {{-- Policy --}}
                    <tr>
                        <td>Policy</td>
                        <td>Policy Name</td>
                        <td>{{ $policy->name }}</td>
                    </tr>


                    <tr>
                        <td>Policy</td>
                        <td>Active Policy</td>
                        <td>
                            {{ $policy->is_active ? 'Yes' : 'No' }}
                        </td>
                    </tr>


                    <tr>
                        <td>Policy</td>
                        <td>Default Policy</td>
                        <td>
                            {{ $policy->is_default ? 'Yes' : 'No' }}
                        </td>
                    </tr>



                    {{-- Length --}}
                    <tr>
                        <td>Password Length</td>
                        <td>Minimum Password Length</td>
                        <td>
                            {{ $policy->minimum_length }} characters
                        </td>
                    </tr>


                    <tr>
                        <td>Password Length</td>
                        <td>Maximum Password Length</td>
                        <td>
                            {{ $policy->maximum_length }} characters
                        </td>
                    </tr>



                    {{-- Complexity --}}
                    <tr>
                        <td>Password Complexity</td>
                        <td>Uppercase Required</td>
                        <td>
                            {{ $policy->require_uppercase ? 'Yes' : 'No' }}
                        </td>
                    </tr>


                    <tr>
                        <td>Password Complexity</td>
                        <td>Lowercase Required</td>
                        <td>
                            {{ $policy->require_lowercase ? 'Yes' : 'No' }}
                        </td>
                    </tr>


                    <tr>
                        <td>Password Complexity</td>
                        <td>Number Required</td>
                        <td>
                            {{ $policy->require_number ? 'Yes' : 'No' }}
                        </td>
                    </tr>


                    <tr>
                        <td>Password Complexity</td>
                        <td>Special Character Required</td>
                        <td>
                            {{ $policy->require_special_character ? 'Yes' : 'No' }}
                        </td>
                    </tr>



                    {{-- Personal Info --}}
                    <tr>
                        <td>Password Restrictions</td>
                        <td>Username Allowed in Password</td>
                        <td>
                            {{
                                $policy->allow_username_in_password
                                    ? 'Yes'
                                    : 'No'
                            }}
                        </td>
                    </tr>


                    <tr>
                        <td>Password Restrictions</td>
                        <td>Employee Name Allowed in Password</td>
                        <td>
                            {{
                                $policy->allow_name_in_password
                                    ? 'Yes'
                                    : 'No'
                            }}
                        </td>
                    </tr>



                    {{-- Expiry --}}
                    <tr>
                        <td>Password Expiry</td>
                        <td>Password Expiry Period</td>
                        <td>

                            @if(
                                $policy->password_expiry_days > 0
                            )

                                {{
                                    $policy->password_expiry_days
                                }}
                                days

                            @else

                                No Expiry

                            @endif

                        </td>
                    </tr>


                    <tr>
                        <td>Password Expiry</td>
                        <td>Expiry Warning</td>
                        <td>
                            {{ $policy->expiry_warning_days }}
                            days before expiry
                        </td>
                    </tr>



                    {{-- History --}}
                    <tr>
                        <td>Password History</td>
                        <td>Previous Passwords Retained</td>
                        <td>
                            {{ $policy->password_history_count }}
                        </td>
                    </tr>


                    <tr>
                        <td>Password History</td>
                        <td>Password Reuse Allowed</td>
                        <td>
                            {{
                                $policy->allow_password_reuse
                                    ? 'Yes'
                                    : 'No'
                            }}
                        </td>
                    </tr>



                    {{-- Login Lock --}}
                    <tr>
                        <td>Account Lockout</td>
                        <td>Maximum Failed Login Attempts</td>
                        <td>
                            {{ $policy->maximum_login_attempts }}
                            attempts
                        </td>
                    </tr>


                    <tr>
                        <td>Account Lockout</td>
                        <td>Account Lock Duration</td>
                        <td>
                            {{ $policy->account_lock_minutes }}
                            minutes
                        </td>
                    </tr>



                    {{-- Temporary Password --}}
                    <tr>
                        <td>Temporary Password</td>
                        <td>Temporary Password Expiry</td>
                        <td>
                            {{
                                $policy
                                    ->temporary_password_expiry_hours
                            }}
                            hours
                        </td>
                    </tr>


                    <tr>
                        <td>Temporary Password</td>
                        <td>Force Change on First Login</td>
                        <td>
                            {{
                                $policy->force_first_login_change
                                    ? 'Yes'
                                    : 'No'
                            }}
                        </td>
                    </tr>



                    {{-- Audit Metadata --}}
                    <tr>
                        <td>Audit Information</td>
                        <td>Policy Created</td>
                        <td>
                            {{
                                $policy->created_at
                                    ? $policy
                                        ->created_at
                                        ->format(
                                            'd M Y H:i'
                                        )
                                    : 'Not recorded'
                            }}
                        </td>
                    </tr>


                    <tr>
                        <td>Audit Information</td>
                        <td>Policy Last Updated</td>
                        <td>
                            {{
                                $policy->updated_at
                                    ? $policy
                                        ->updated_at
                                        ->format(
                                            'd M Y H:i'
                                        )
                                    : 'Not recorded'
                            }}
                        </td>
                    </tr>


                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection



@push('scripts-before-app')


{{-- DataTables --}}
<script
    src="{{ asset('layouts/assets/libs/datatables.net/js/jquery.dataTables.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}">
</script>


{{-- Buttons --}}
<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}">
</script>


{{-- Excel --}}
<script
    src="{{ asset('layouts/assets/libs/jszip/jszip.min.js') }}">
</script>


{{-- PDF --}}
<script
    src="{{ asset('layouts/assets/libs/pdfmake/build/pdfmake.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/pdfmake/build/vfs_fonts.js') }}">
</script>


{{-- Export --}}
<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}">
</script>


@endpush



@push('scripts')

<script>

$(document).ready(function () {

    $('#policy-report-table')
        .DataTable({

            paging: false,

            searching: false,

            ordering: false,

            info: false,

            dom:
                "<'row mb-3'<'col-12'B>>"
                +
                "<'row'<'col-12'tr>>",

            buttons: [

                {
                    extend: 'copyHtml5',

                    text:
                        '<i class="mdi mdi-content-copy me-1"></i> Copy',

                    className:
                        'btn btn-light',

                    title:
                        'LAPF Password Policy'
                },


                {
                    extend: 'excelHtml5',

                    text:
                        '<i class="mdi mdi-file-excel-outline me-1"></i> Excel',

                    className:
                        'btn btn-success',

                    title:
                        'LAPF Password Policy',

                    filename:
                        'LAPF_Password_Policy'
                },


                {
                    extend: 'pdfHtml5',

                    text:
                        '<i class="mdi mdi-file-pdf-outline me-1"></i> PDF',

                    className:
                        'btn btn-danger',

                    title:
                        'LAPF Pension Fund System - Password Policy',

                    filename:
                        'LAPF_Password_Policy',

                    orientation:
                        'portrait',

                    pageSize:
                        'A4',

                    customize:
                        function (doc) {

                            doc.defaultStyle.fontSize =
                                9;


                            doc.styles.title = {

                                fontSize:
                                    14,

                                bold:
                                    true,

                                alignment:
                                    'center',

                                margin: [
                                    0,
                                    0,
                                    0,
                                    15
                                ]

                            };


                            doc.content.splice(
                                1,
                                0,
                                {
                                    text:
                                        'Generated: '
                                        + new Date()
                                            .toLocaleString(),

                                    alignment:
                                        'center',

                                    fontSize:
                                        8,

                                    margin: [
                                        0,
                                        0,
                                        0,
                                        10
                                    ]
                                }
                            );

                        }
                },


                {
                    extend: 'print',

                    text:
                        '<i class="mdi mdi-printer-outline me-1"></i> Print',

                    className:
                        'btn btn-primary',

                    title:
                        'LAPF Pension Fund System - Password Policy'
                }

            ]

        });

});

</script>

@endpush