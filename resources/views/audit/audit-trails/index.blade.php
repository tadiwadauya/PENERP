@extends('layouts.app')

@section('title', 'Audit Trail')

@section('page-heading', 'Audit Trail')

@section('page-subheading')
    Review system activities, changes and user actions
@endsection

@section('content')

<div class="dashboard-section">

    <div class="section-heading">
        <h2>System Audit Trail</h2>
        <p>
            All recorded system events are listed below.
        </p>
    </div>

    <div class="table-card">

        <div class="table-responsive">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Outcome</th>
                        <th>View</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($auditTrails as $audit)

                        <tr>

                            <td>
                                {{ optional($audit->occurred_at)->format('d M Y H:i:s') }}
                            </td>

                            <td>
                                @if($audit->user)
                                    {{ $audit->user->full_name }}
                                @else
                                    System / Guest
                                @endif
                            </td>

                            <td>
                                {{ $audit->module ?? '-' }}
                            </td>

                            <td>
                                {{ $audit->action ?? '-' }}
                            </td>

                            <td>
                                {{ $audit->description ?? '-' }}
                            </td>

                            <td>
                                {{ $audit->ip_address ?? '-' }}
                            </td>

                            <td>
                                @if($audit->outcome === 'success')
                                    <span class="status-badge status-success">
                                        Success
                                    </span>
                                @else
                                    <span class="status-badge status-danger">
                                        {{ ucfirst($audit->outcome) }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a
                                    href="{{ route('audit.audit-trails.show', $audit) }}"
                                    class="table-action-link"
                                >
                                    View
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="empty-table">
                                No audit records found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if(method_exists($auditTrails, 'links'))
            <div class="pagination-wrapper">
                {{ $auditTrails->links() }}
            </div>
        @endif

    </div>

</div>

@endsection