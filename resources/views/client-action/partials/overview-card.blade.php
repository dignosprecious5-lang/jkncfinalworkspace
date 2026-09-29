@php
    $recentActions = $deal->clientActionRequests ? $deal->clientActionRequests->take(4) : collect();
@endphp

<section class="detail-section" id="deal-client-actions" style="margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e4f95" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7.5" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            <span>Client Actions</span>
            @if($recentActions->count() > 0)
                <span style="font-size: 11px; font-weight: 700; background: #eff6ff; color: #1e4f95; border: 1px solid #bfdbfe; padding: 1px 7px; border-radius: 10px;">
                    {{ $deal->pendingClientActionRequests->count() }} Pending
                </span>
            @endif
        </h2>

        <div style="display: flex; align-items: center; gap: 8px;">
            <button type="button" class="btn-dli-edit" onclick="switchDealNavTab('client-actions', document.querySelector('[data-tab=\'client-actions\']'))" style="font-size: 12px; font-weight: 600;">
                View All Actions →
            </button>
            <button type="button" class="btn-dli-edit" onclick="openCreateClientActionModal()" style="background: #1e4f95; color: #ffffff !important; border-color: #1e4f95; font-size: 12px;">
                + New Action
            </button>
        </div>
    </div>

    @if($recentActions->count() > 0)
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @foreach($recentActions as $act)
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 11px; font-weight: 800; color: #1e4f95; background: #eff6ff; border: 1px solid #bfdbfe; padding: 2px 6px; border-radius: 4px;">
                            {{ $act->action_code }}
                        </span>
                        <div>
                            <div style="font-size: 13.5px; font-weight: 700; color: #0f172a;">
                                {{ $act->title }}
                                <span style="font-size: 11px; font-weight: 700; color: #5b21b6; background: #ede9fe; padding: 1px 5px; border-radius: 3px; margin-left: 4px;">
                                    {{ $act->document_version }}
                                </span>
                            </div>
                            <div style="font-size: 11.5px; color: #64748b;">
                                Client: {{ $act->client_name }} · Method: <strong>{{ $act->response_channel ?: 'Awaiting Client' }}</strong>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        @php
                            $statusStyle = match($act->status) {
                                'Approved', 'Accepted', 'Completed', 'Signed' => 'background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0;',
                                'Awaiting Client', 'Pending' => 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;',
                                default => 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;'
                            };
                        @endphp
                        <span style="font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 10px; {{ $statusStyle }}">
                            {{ $act->status }}
                        </span>

                        <a href="{{ $act->secure_url }}" target="_blank" class="btn-dli-edit" style="text-decoration: none; font-size: 11.5px;">
                            Open Action
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="padding: 24px; text-align: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
            <p style="font-size: 13px; color: #64748b; margin: 0 0 10px;">
                No client actions created yet. Create a request for Proposal Approval, Service Memo Acknowledgment, or Document Signatures.
            </p>
            <button type="button" class="btn-dli-edit" onclick="openCreateClientActionModal()" style="background: #1e4f95; color: #ffffff !important; border-color: #1e4f95;">
                + Create Client Action
            </button>
        </div>
    @endif
</section>
