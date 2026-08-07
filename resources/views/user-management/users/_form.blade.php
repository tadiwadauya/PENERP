@php

    $editing = isset($user);

    $selectedRoles = old(
        'roles',
        $editing
            ? $user->roles->pluck('name')->all()
            : []
    );

    $selectedDashboards = old(
        'dashboard_ids',
        $editing
            ? $user->dashboards->pluck('id')->all()
            : []
    );

    $defaultDashboard = old(
        'default_dashboard_id',
        $editing
            ? optional(
                $user->dashboards->firstWhere(
                    'pivot.is_default',
                    true
                )
            )->id
            : null
    );

@endphp



{{-- =========================================================
     PERSONAL INFORMATION
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Personal Information
        </h4>

        <p class="card-title-desc">
            Enter the employee's personal and login information.
        </p>


        <div class="row">


            {{-- Employee Number --}}
            <div class="col-lg-4 col-md-6">

                <div class="mb-3">

                    <label
                        for="employee_number"
                        class="form-label"
                    >
                        Employee Number
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="employee_number"
                        name="employee_number"
                        class="form-control"
                        value="{{ old(
                            'employee_number',
                            $user->employee_number ?? ''
                        ) }}"
                        required
                    >

                </div>

            </div>


            {{-- Title --}}
            <div class="col-lg-2 col-md-6">

                <div class="mb-3">

                    <label
                        for="title"
                        class="form-label"
                    >
                        Title
                    </label>

                    <select
                        name="title"
                        id="title"
                        class="form-select"
                    >

                        <option value="">
                            Select
                        </option>

                        @foreach([
                            'Mr',
                            'Mrs',
                            'Ms',
                            'Dr',
                            'Prof',
                        ] as $title)

                            <option
                                value="{{ $title }}"
                                @selected(
                                    old(
                                        'title',
                                        $user->title ?? ''
                                    ) === $title
                                )
                            >
                                {{ $title }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- First Name --}}
            <div class="col-lg-3 col-md-6">

                <div class="mb-3">

                    <label
                        for="first_name"
                        class="form-label"
                    >
                        First Name
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        class="form-control"
                        value="{{ old(
                            'first_name',
                            $user->first_name ?? ''
                        ) }}"
                        required
                    >

                </div>

            </div>


            {{-- Middle Name --}}
            <div class="col-lg-3 col-md-6">

                <div class="mb-3">

                    <label
                        for="middle_name"
                        class="form-label"
                    >
                        Middle Name
                    </label>

                    <input
                        type="text"
                        id="middle_name"
                        name="middle_name"
                        class="form-control"
                        value="{{ old(
                            'middle_name',
                            $user->middle_name ?? ''
                        ) }}"
                    >

                </div>

            </div>


            {{-- Surname --}}
            <div class="col-lg-4 col-md-6">

                <div class="mb-3">

                    <label
                        for="surname"
                        class="form-label"
                    >
                        Surname
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="surname"
                        name="surname"
                        class="form-control"
                        value="{{ old(
                            'surname',
                            $user->surname ?? ''
                        ) }}"
                        required
                    >

                </div>

            </div>


            {{-- Username --}}
            <div class="col-lg-4 col-md-6">

                <div class="mb-3">

                    <label
                        for="username"
                        class="form-label"
                    >
                        Username
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        value="{{ old(
                            'username',
                            $user->username ?? ''
                        ) }}"
                        required
                    >

                </div>

            </div>


            {{-- Login Email --}}
            <div class="col-lg-4 col-md-6">

                <div class="mb-3">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Login Email
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="{{ old(
                            'email',
                            $user->email ?? ''
                        ) }}"
                        required
                    >

                </div>

            </div>


            {{-- Work Email --}}
            <div class="col-lg-4 col-md-6">

                <div class="mb-3">

                    <label
                        for="work_email"
                        class="form-label"
                    >
                        Work Email
                    </label>

                    <input
                        type="email"
                        id="work_email"
                        name="work_email"
                        class="form-control"
                        value="{{ old(
                            'work_email',
                            $user->work_email ?? ''
                        ) }}"
                    >

                </div>

            </div>


            {{-- Cell Number --}}
            <div class="col-lg-3 col-md-6">

                <div class="mb-3">

                    <label
                        for="cell_number"
                        class="form-label"
                    >
                        Cell Number
                    </label>

                    <input
                        type="text"
                        id="cell_number"
                        name="cell_number"
                        class="form-control"
                        value="{{ old(
                            'cell_number',
                            $user->cell_number ?? ''
                        ) }}"
                    >

                </div>

            </div>


            {{-- Telephone --}}
            <div class="col-lg-3 col-md-6">

                <div class="mb-3">

                    <label
                        for="telephone_number"
                        class="form-label"
                    >
                        Telephone
                    </label>

                    <input
                        type="text"
                        id="telephone_number"
                        name="telephone_number"
                        class="form-control"
                        value="{{ old(
                            'telephone_number',
                            $user->telephone_number ?? ''
                        ) }}"
                    >

                </div>

            </div>


            {{-- Extension --}}
            <div class="col-lg-2 col-md-6">

                <div class="mb-3">

                    <label
                        for="phone_extension"
                        class="form-label"
                    >
                        Extension
                    </label>

                    <input
                        type="text"
                        id="phone_extension"
                        name="phone_extension"
                        class="form-control"
                        value="{{ old(
                            'phone_extension',
                            $user->phone_extension ?? ''
                        ) }}"
                    >

                </div>

            </div>


        </div>

    </div>

</div>



{{-- =========================================================
     EMPLOYMENT & ORGANISATION
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Employment & Organisation
        </h4>

        <p class="card-title-desc">
            Assign the employee to the LAPF organisational structure.
        </p>


        <div class="row">


            {{-- Organisation Unit --}}
            <div class="col-lg-6">

                <div class="mb-3">

                    <label
                        for="organisation_unit_id"
                        class="form-label"
                    >
                        Department / Section
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="organisation_unit_id"
                        id="organisation_unit_id"
                        class="form-control select2"
                        required
                    >

                        <option value="">
                            Select Department / Section
                        </option>

                        @foreach($organisationUnits as $organisationUnit)

                            <option
                                value="{{ $organisationUnit->id }}"
                                @selected(
                                    old(
                                        'organisation_unit_id',
                                        $user->organisation_unit_id ?? ''
                                    )
                                    == $organisationUnit->id
                                )
                            >
                                {{ $organisationUnit->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- Job Title --}}
            <div class="col-lg-6">

                <div class="mb-3">

                    <label
                        for="job_title_id"
                        class="form-label"
                    >
                        Job Title
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="job_title_id"
                        id="job_title_id"
                        class="form-control select2"
                        required
                    >

                        <option value="">
                            Select Job Title
                        </option>

                        @foreach($jobTitles as $jobTitle)

                            <option
                                value="{{ $jobTitle->id }}"
                                @selected(
                                    old(
                                        'job_title_id',
                                        $user->job_title_id ?? ''
                                    )
                                    == $jobTitle->id
                                )
                            >
                                {{ $jobTitle->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- Grade --}}
            <div class="col-lg-4">

                <div class="mb-3">

                    <label
                        for="grade_id"
                        class="form-label"
                    >
                        Grade
                    </label>

                    <select
                        name="grade_id"
                        id="grade_id"
                        class="form-control select2"
                    >

                        <option value="">
                            Select Grade
                        </option>

                        @foreach($grades as $grade)

                            <option
                                value="{{ $grade->id }}"
                                @selected(
                                    old(
                                        'grade_id',
                                        $user->grade_id ?? ''
                                    )
                                    == $grade->id
                                )
                            >
                                {{ $grade->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- Reports To --}}
            <div class="col-lg-4">

                <div class="mb-3">

                    <label
                        for="reports_to_user_id"
                        class="form-label"
                    >
                        Reports To
                    </label>

                    <select
                        name="reports_to_user_id"
                        id="reports_to_user_id"
                        class="form-control select2"
                    >

                        <option value="">
                            None
                        </option>

                        @foreach($supervisors as $supervisor)

                            <option
                                value="{{ $supervisor->id }}"
                                @selected(
                                    old(
                                        'reports_to_user_id',
                                        $user->reports_to_user_id ?? ''
                                    )
                                    == $supervisor->id
                                )
                            >

                                {{ $supervisor->surname }},
                                {{ $supervisor->first_name }}

                                @if($supervisor->jobTitle)

                                    -
                                    {{ $supervisor->jobTitle->name }}

                                @endif

                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- Employment Date --}}
            <div class="col-lg-4">

                <div class="mb-3">

                    <label
                        for="employment_date"
                        class="form-label"
                    >
                        Employment Date
                    </label>

                    <input
                        type="date"
                        id="employment_date"
                        name="employment_date"
                        class="form-control"
                        value="{{ old(
                            'employment_date',
                            isset($user)
                            && $user->employment_date
                                ? $user->employment_date->format('Y-m-d')
                                : ''
                        ) }}"
                    >

                </div>

            </div>


            @if($editing)

                {{-- Employment Status --}}
                <div class="col-lg-4">

                    <div class="mb-3">

                        <label
                            for="employment_status"
                            class="form-label"
                        >
                            Employment Status
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="employment_status"
                            id="employment_status"
                            class="form-select"
                            required
                        >

                            @foreach([
                                'active' => 'Active',
                                'leave' => 'On Leave',
                                'seconded' => 'Seconded',
                                'suspended' => 'Suspended',
                                'retired' => 'Retired',
                                'terminated' => 'Terminated',
                            ] as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        old(
                                            'employment_status',
                                            $user->employment_status
                                        ) === $value
                                    )
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

            @endif


        </div>

    </div>

</div>



{{-- =========================================================
     ROLES & ACCESS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Roles & Access Rights
        </h4>

        <p class="card-title-desc">
            Assign roles that determine the user's authorised system functions.
        </p>


        <div class="row">

            @forelse($roles as $role)

                <div class="col-xl-4 col-md-6">

                    <div class="border rounded p-3 mb-3">

                        <div class="form-check">

                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="role_{{ $role->id }}"
                                name="roles[]"
                                value="{{ $role->name }}"
                                @checked(
                                    in_array(
                                        $role->name,
                                        $selectedRoles
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="role_{{ $role->id }}"
                            >

                                <strong>
                                    {{ $role->display_name ?: $role->name }}
                                </strong>

                            </label>

                        </div>

                    </div>

                </div>

            @empty

                <div class="col-12">

                    <div class="alert alert-warning mb-0">
                        No roles have been configured.
                    </div>

                </div>

            @endforelse

        </div>

    </div>

</div>



{{-- =========================================================
     DASHBOARD ACCESS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Dashboard Access
        </h4>

        <p class="card-title-desc">
            Select the dashboards the user is authorised to access and
            specify the default dashboard.
        </p>


        <div class="row">

            @foreach($dashboards as $dashboard)

                <div class="col-xl-4 col-md-6">

                    <div class="border rounded p-3 mb-3">

                        <div class="form-check">

                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="dashboard_{{ $dashboard->id }}"
                                name="dashboard_ids[]"
                                value="{{ $dashboard->id }}"
                                @checked(
                                    in_array(
                                        $dashboard->id,
                                        array_map(
                                            'intval',
                                            $selectedDashboards
                                        )
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="dashboard_{{ $dashboard->id }}"
                            >

                                <strong>
                                    {{ $dashboard->name }}
                                </strong>

                            </label>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>


        <div class="row mt-2">

            <div class="col-lg-6">

                <div class="mb-3">

                    <label
                        for="default_dashboard_id"
                        class="form-label"
                    >
                        Default Dashboard
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="default_dashboard_id"
                        id="default_dashboard_id"
                        class="form-control select2"
                        required
                    >

                        <option value="">
                            Select Default Dashboard
                        </option>

                        @foreach($dashboards as $dashboard)

                            <option
                                value="{{ $dashboard->id }}"
                                @selected(
                                    (int) $defaultDashboard
                                    === $dashboard->id
                                )
                            >
                                {{ $dashboard->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     TEMPORARY PASSWORD
========================================================= --}}

@if(!$editing)

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Temporary Password
            </h4>

            <p class="card-title-desc">
                Set how the user's one-time temporary password should be created.
                The user must change this password on first login.
            </p>


            <div class="row">

                <div class="col-lg-6">


                    <div class="form-check mb-3">

                        <input
                            type="radio"
                            class="form-check-input"
                            name="password_option"
                            id="password_generate"
                            value="generate"
                            @checked(
                                old(
                                    'password_option',
                                    'generate'
                                ) === 'generate'
                            )
                        >

                        <label
                            class="form-check-label"
                            for="password_generate"
                        >

                            <strong>
                                Generate Automatically
                            </strong>

                            <br>

                            <span class="text-muted">
                                Generate a secure temporary password automatically.
                            </span>

                        </label>

                    </div>


                    <div class="form-check mb-3">

                        <input
                            type="radio"
                            class="form-check-input"
                            name="password_option"
                            id="password_manual"
                            value="manual"
                            @checked(
                                old('password_option') === 'manual'
                            )
                        >

                        <label
                            class="form-check-label"
                            for="password_manual"
                        >

                            <strong>
                                Enter Manually
                            </strong>

                            <br>

                            <span class="text-muted">
                                ICT enters the temporary password.
                            </span>

                        </label>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="mb-3">

                        <label
                            for="temporary_password"
                            class="form-label"
                        >
                            Manual Temporary Password
                        </label>

                        <input
                            type="password"
                            id="temporary_password"
                            name="temporary_password"
                            class="form-control"
                            autocomplete="new-password"
                        >

                        <div class="form-text">
                            Required only when Enter Manually is selected.
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endif