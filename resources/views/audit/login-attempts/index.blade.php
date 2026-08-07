@extends('layouts.app')

@section('title', 'Login Attempts')

@section('page-heading', 'Login Attempts')

@section('page-subheading')
    Review successful and failed authentication attempts
@endsection

@section('content')

<div class="dashboard-section">

    <div class="section-heading">
        <h2>Authentication Activity</h2>
        <p>
            Review login attempts recorded by the system.
        </p>
    </div>

    <div class="table-card">

        <div class="table-responsive">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Login Identifier</th>
                        <th>IP Address</th>
                        <th>Result</th>
                        <th>Failure Reason</th>
                        <th>View</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($loginAttempts as $attempt)

                        <tr>

                            <td>
                                {{ optional($attempt->attempted_at)->format('d M Y H:i:s') }}
                            </td>

                            <td>
                                @if($attempt->user)
                                    {{ $attempt->user->full_name }}
                                @else
                                    Unknown / Unmatched
                                @endif
                            </td>

                            <td>
                                {{ $attempt->login_identifier }}
                            </td>

                            <td>
                                {{ $attempt->ip_address ?? '-' }}
                            </td>

                            <td>
                                @if($attempt->was_successful)
                                    <span class="status-badge status-success">
                                        Successful
                                    </span>
                                @else
                                    <span class="status-badge status-danger">
                                        Failed
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ $attempt->failure_reason ?? '-' }}
                            </td>

                            <td>
                                <a
                                    href="{{ route('audit.login-attempts.show', $attempt) }}"
                                    class="table-action-link"
                                >
                                    View
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="empty-table">
                                No login attempts found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if(method_exists($loginAttempts, 'links'))
            <div class="pagination-wrapper">
                {{ $loginAttempts->links() }}
            </div>
        @endif

    </div>

</div>

@endsection