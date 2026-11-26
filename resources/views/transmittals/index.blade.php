@extends('layouts.app')

@section('title', 'Transmittals')

@push('styles')
    @vite(['resources/css/transmittal.css'])
@endpush

@push('scripts')
    @vite(['resources/js/transmittal.js'])
@endpush

@section('content')
<div class="page transmittal-index-page">
    @php
        $filterCounts = $filterCounts ?? [
            'all' => $summary->sum(),
            'uploaded' => $summary['draft'] ?? 0,
            'submitted' => $summary['pending_approval'] ?? 0,
            'accepted' => ($summary['approved'] ?? 0)
                + ($summary['sent'] ?? 0)
                + ($summary['acknowledged'] ?? 0),
            'reverted' => $summary['rejected'] ?? 0,
            'archived' => $summary['cancelled'] ?? 0,
        ];
        $total = $filterCounts['all'];

        $filters = [
            'all' => 'All',
            'uploaded' => 'Uploaded',
            'submitted' => 'Submitted',
            'accepted' => 'Accepted',
            'reverted' => 'Reverted',
            'archived' => 'Archived',
        ];
    @endphp

    {{-- Dashboard Header --}}
    <div class="transmittal-heading">
        <div>
            <h1>Transmittal</h1>
        </div>

        <div class="heading-actions">
            <a
                class="button primary smart-upload-button"
                href="{{ route('transmittals.smart-upload') }}"
            >
                Smart Upload
            </a>

            <button
                class="button primary add-button"
                type="button"
                data-open-drawer
            >
                <span class="button-plus">＋</span>
                Add Transmittal
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="metric-row">
        <div class="metric-card">
            <span class="metric-label">Total</span>
            <strong>{{ $filterCounts['all'] }}</strong>
        </div>

        <div class="metric-card">
            <span class="metric-label">Pending Approval</span>
            <strong>{{ $filterCounts['submitted'] }}</strong>
        </div>

        <div class="metric-card">
            <span class="metric-label">Awaiting Acknowledgment</span>
            <strong>{{ ($summary['sent'] ?? 0) + ($summary['approved'] ?? 0) }}</strong>
        </div>

        <div class="metric-card">
            <span class="metric-label">Received</span>
            <strong>{{ $filterCounts['accepted'] }}</strong>
        </div>
    </div>

    {{-- Transmittal Register --}}
    <section class="panel register-panel" aria-label="Transmittal register">

        {{-- Status Filters --}}
        <nav
            class="status-tabs"
            aria-label="Filter transmittals by status"
        >
            @foreach ($filters as $key => $label)
                @if (
                    $key !== 'deleted'
                    || auth()->user()?->canApproveTransmittals()
                )
                    <a
                        class="status-tab {{ $filter === $key ? 'active' : '' }}"
                        href="{{ route('transmittal.index', array_filter([
                            'filter' => $key === 'all' ? null : $key,
                            'q' => $search,
                        ])) }}"
                        @if ($filter === $key)
                            aria-current="page"
                        @endif
                    >
                        {{ $label }}
                        <span class="tab-count">
                            {{ $filterCounts[$key] }}
                        </span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="register-tools">
            <p class="register-hint">
                @if ($filter === 'uploaded')
                    These transmittals are saved drafts. Continue working on them when ready.
                @elseif ($filter === 'submitted')
                    These transmittals are waiting for an administrator decision.
                @elseif ($filter === 'archived')
                    View archived transmittal records.
                @else
                    View transmittal records or filter them by status.
                @endif
            </p>
        </div>

        {{-- Dashboard Table: No Action Columns --}}
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref#</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Delivery Type</th>
                        <th>Actions</th>
                        <th>Workflow Status</th>
                        <th>Approval Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($transmittals as $transmittal)
                        @php
                            $workflow = [
                                'draft' => 'Uploaded',
                                'pending_approval' => 'Submitted',
                                'approved' => 'Accepted',
                                'sent' => 'Accepted',
                                'acknowledged' => 'Accepted',
                                'rejected' => 'Reverted',
                                'cancelled' => 'Archived',
                            ][$transmittal->status] ?? $transmittal->status;

                            $approval = [
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending',
                                'approved' => 'Approved',
                                'sent' => 'Approved',
                                'acknowledged' => 'Approved',
                                'rejected' => 'Rejected',
                                'cancelled' => '—',
                            ][$transmittal->status] ?? '—';

                            $recipientNames = $transmittal->recipients
                                ->map(fn ($recipient) =>
                                    $recipient->name
                                    ?: ($recipient->organization ?: $recipient->email)
                                )
                                ->filter()
                                ->implode(', ');

                            $officeName = $transmittal->origin_office
                                ?: config('ordo.office_name');

                            $refNo = $transmittal->tracking_number ?: $transmittal->transmittal_no ?: '—';
                            $dateFormatted = $transmittal->transmittal_date?->format('Y-m-d')
                                ?? $transmittal->created_at?->format('Y-m-d')
                                ?? '—';
                            $modeValue = $transmittal->mode ?: strtoupper($transmittal->direction ?? 'SEND');
                            $fromValue = $transmittal->from_value ?: ($transmittal->direction === 'receive' ? ($recipientNames ?: '—') : $officeName);
                            $toValue = $transmittal->to_value ?: ($transmittal->direction === 'receive' ? $officeName : ($recipientNames ?: $transmittal->related_company ?: '—'));
                            $deliveryType = $transmittal->delivery_summary ?: str($transmittal->delivery_type ?? 'by_person')->replace('_', ' ')->title();
                            $actionsValue = $transmittal->actions_summary ?: '—';
                            $workflowStatus = $transmittal->workflow_status ?: ($workflow ?: 'Uploaded');
                            $approvalStatus = $transmittal->approval_status ?: ($approval ?: 'Pending');
                            $isV2 = ($transmittal->workflow_version === 'v2');
                            $targetUrl = $isV2 ? route('transmittals.workspace', $transmittal) : route('transmittal.preview', $transmittal);
                        @endphp

                        <tr class="record-row-clickable" onclick="window.location.href='{{ $targetUrl }}'" style="cursor: pointer;">
                            {{-- Ref#: Click to open the Workspace or Preview --}}
                            <td>
                                <a
                                    class="tracking-link"
                                    href="{{ $targetUrl }}"
                                    onclick="event.stopPropagation();"
                                >
                                    {{ $refNo }}
                                </a>
                            </td>

                            {{-- Date --}}
                            <td>{{ $dateFormatted }}</td>

                            {{-- Mode --}}
                            <td>
                                <span class="mode-badge" style="color: #374151; background: #f1f5f9; font-weight: 500;">
                                    {{ $modeValue }}
                                </span>
                            </td>

                            {{-- From --}}
                            <td>{{ $fromValue }}</td>

                            {{-- To --}}
                            <td
                                class="recipient-cell"
                                title="{{ $toValue }}"
                            >
                                {{ $toValue }}
                            </td>

                            {{-- Delivery Type --}}
                            <td>{{ $deliveryType }}</td>

                            {{-- Actions --}}
                            <td>{{ $actionsValue }}</td>

                            {{-- Workflow Status --}}
                            <td>
                                <span class="status-badge status-{{ strtolower($workflowStatus) }}">
                                    {{ $workflowStatus }}
                                </span>
                            </td>

                            {{-- Approval Status --}}
                            <td>
                                <span class="status-badge status-{{ strtolower($approvalStatus) }}">
                                    {{ $approvalStatus }}
                                </span>
                            </td>

                            {{-- Action --}}
                            <td>
                                <a
                                    href="{{ $targetUrl }}"
                                    class="button secondary"
                                    onclick="event.stopPropagation();"
                                    style="display: inline-flex; align-items: center; justify-content: center; padding: 4px 12px; font-size: 13px; font-weight: 500; text-decoration: none; border-radius: 6px;"
                                >
                                    Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <strong>No records found.</strong>

                                    @if ($filter === 'uploaded')
                                        <p>
                                            Save a draft from Add or Smart Upload
                                            to see it here.
                                        </p>
                                    @elseif ($filter === 'deleted')
                                        <p>No deleted records found.</p>
                                    @elseif ($filter === 'archived')
                                        <p>No archived records found.</p>
                                    @else
                                        <p>
                                            {{ $search
                                                ? 'Try changing your search.'
                                                : 'Start by adding a transmittal.' }}
                                        </p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pagination">
            {{ $transmittals->links() }}
        </div>
    </section>

    {{-- Document Preview --}}
    <section
        class="add-document-preview"
        data-document-preview-pane
        aria-label="Live two-page transmittal preview"
    >
        <div class="add-preview-toolbar">
            <div style="display: flex; align-items: center; gap: 8px;">
                <strong>DOCUMENT PREVIEW</strong>
                <span>2 PAGES · LIVE</span>
            </div>

            <button
                type="button"
                data-close-drawer
                aria-label="Close document preview"
                style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: 0; background: transparent; color: #64748b; font-size: 20px; cursor: pointer; border-radius: 4px;"
            >
                ×
            </button>
        </div>

        <div class="add-preview-pages">
            @include('transmittals._document-form', [
                'transmittal' => $editTransmittal ?? new \App\Models\Transmittal(),
                'preview' => true,
            ])
        </div>
    </section>

    {{-- Add Transmittal Drawer --}}
    @include('transmittals._form-drawer', [
        'transmittal' => $editTransmittal,
    ])
</div>
@endsection

@push('scripts')
    @if (request()->boolean('add') || $editTransmittal)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelector('.add-button')?.click();
            });
        </script>
    @endif
@endpush
