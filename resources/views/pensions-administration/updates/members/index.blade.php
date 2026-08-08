@extends('layouts.app')

@section('title', 'Membership')

@section('page-heading', 'Membership')

@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.updates.members.create'
        ) }}"
        class="btn btn-success"
    >
        <i class="mdi mdi-account-plus-outline me-1"></i>

        Add Member
    </a>

@endsection


@section('content')
@include(
    'pensions-administration.partials.navigation'
)

<div class="card">

    <div class="card-body">

        <form
            method="GET"
            action="{{ route(
                'pensions-administration.updates.members.index'
            ) }}"
        >

            <div class="row">


                <div class="col-md-5">

                    <div class="mb-3">

                        <label class="form-label">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Member number, legacy number, ID, name..."
                        >

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Employer
                        </label>

                        <select
                            name="employer_id"
                            class="form-select"
                        >

                            <option value="">
                                All Employers
                            </option>

                            @foreach($employers as $employer)

                                <option
                                    value="{{ $employer->id }}"
                                    @selected(
                                        request('employer_id')
                                        == $employer->id
                                    )
                                >
                                    {{ $employer->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">
                                All Statuses
                            </option>

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


            <button
                class="btn btn-primary"
                type="submit"
            >
                Search
            </button>


            <a
                href="{{ route(
                    'pensions-administration.updates.members.index'
                ) }}"
                class="btn btn-light"
            >
                Clear
            </a>

        </form>

    </div>

</div>



<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Membership Register
        </h4>


        <div class="table-responsive">

            <table
                class="table table-bordered table-striped"
            >

                <thead>

                    <tr>

                        <th>PENERP No.</th>
                        <th>PenAd No.</th>
                        <th>Fundworx No.</th>
                        <th>Member</th>
                        <th>National ID</th>
                        <th>Employer</th>
                        <th>Staff No.</th>
                        <th>Vote No.</th>
                        <th>Status</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($members as $member)

                        <tr>

                            <td>
                                <strong>
                                    {{ $member->member_number }}
                                </strong>
                            </td>

                            <td>
                                {{ $member->penad_member_number ?? '-' }}
                            </td>

                            <td>
                                {{ $member->fundworx_member_number ?? '-' }}
                            </td>

                            <td>

                                <strong>
                                    {{ $member->surname }},
                                    {{ $member->first_names }}
                                </strong>

                                @if($member->date_of_birth)

                                    <br>

                                    <small class="text-muted">
                                        DOB:
                                        {{
                                            $member
                                                ->date_of_birth
                                                ->format('d M Y')
                                        }}
                                    </small>

                                @endif

                            </td>

                            <td>
                                {{ $member->national_id ?? '-' }}
                            </td>

                            <td>
                                {{
                                    $member
                                        ->currentEmployment
                                        ?->employer
                                        ?->name
                                    ?? '-'
                                }}
                            </td>

                            <td>
                                {{
                                    $member
                                        ->currentEmployment
                                        ?->staff_number
                                    ?? '-'
                                }}
                            </td>

                            <td>
                                {{
                                    $member
                                        ->currentEmployment
                                        ?->vote_number
                                    ?? '-'
                                }}
                            </td>

                            <td>

                                <span
                                    class="badge
                                    {{
                                        $member->membership_status
                                        === 'active'
                                            ? 'bg-success'
                                            : 'bg-secondary'
                                    }}"
                                >
                                    {{
                                        ucfirst(
                                            $member->membership_status
                                        )
                                    }}
                                </span>

                            </td>

                            <td>

                                <a
                                    href="{{ route(
                                        'pensions-administration.updates.members.show',
                                        $member
                                    ) }}"
                                    class="btn btn-sm btn-info"
                                >
                                    View
                                </a>

                                <a
                                    href="{{ route(
                                        'pensions-administration.updates.members.edit',
                                        $member
                                    ) }}"
                                    class="btn btn-sm btn-primary"
                                >
                                    Edit
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="10"
                                class="text-center"
                            >
                                No members found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-3">

            {{ $members->links() }}

        </div>

    </div>

</div>

@endsection