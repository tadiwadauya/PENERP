@extends('layouts.app')

@section('title', 'User Sessions')

@section('page-heading', 'User Sessions')

@section('page-subheading')
    Review active and historical system sessions
@endsection

@section('content')

<div class="dashboard-section">

    <div class="section-heading">
        <h2>User Session History</h2>
        <p>
            Review login sessions, activity and logout information.
        </p>
    </div>

    <div class="table-card">

        <div class="table-responsive">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>User</th>
                        <th>Login Time</th>
                        <th>Last Activity</th>
                        <th>Logout Time</th>
                        <th>IP Address</th>
                        <th>Device</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($userSessions as $session)

                        <tr>

                            <td>
                                @if($session->user)
                                    {{ $session->user->full_name }}
                                @else
                                    Unknown User
                                @endif
                            </td>

                            <td>
                                {{ optional($session->login_at)->format('d M Y H:i:s') }}
                            </td>

                            <td>
                                {{ optional($session->last_activity_at)->format('d M Y H:i:s') ?? '-' }}
                            </td>

                            <td>
                                {{ optional($session->logout_at)->format('d M Y H:i:s') ?? '-' }}
                            </td>

                            <td>
                                {{ $session->ip_address ?? '-' }}
                            </td>

                            <td>
                                {{ $session->device_name ?? 'Unknown Device' }}
                            </td>

                            <td>
                                @if($session->is_active)
                                    <span class="status-badge status-success">
                                        Active
                                    </span>
                                @else
                                    <span class="status-badge status-muted">
                                        Closed
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a
                                    href="{{ route('audit.user-sessions.show', $session) }}"
                                    class="table-action-link"
                                >
                                    View
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="empty-table">
                                No user sessions found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if(method_exists($userSessions, 'links'))
            <div class="pagination-wrapper">
                {{ $userSessions->links() }}
            </div>
        @endif

    </div>

</div>

@endsection