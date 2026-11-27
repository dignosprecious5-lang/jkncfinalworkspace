@php($decisionSummary = $proposal->decision_summary_snapshot ?: app(\App\Services\DealProposalDecisions::class)->summary($proposal))
<section class="bg-white border border-gray-200 rounded-xl p-4 mb-4" data-proposal-decision-summary style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;">Client Proposal Decision · Version {{ $decisionSummary['version'] }}</h3>
        <span style="padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;background:{{ $decisionSummary['status']==='approved' ? '#dcfce7' : ($decisionSummary['status']==='rejected' ? '#fee2e2' : '#eff6ff') }};color:#1e293b;">{{ $decisionSummary['status_label'] }}</span>
    </div>
    @if ($proposal->handler_name)<p style="font-size:12px;color:#64748b;">Proposal Handler: {{ $proposal->handler_name }}</p>@endif
    <p style="font-size:13px;color:#475569;">Proposed: <strong>₱{{ number_format($decisionSummary['proposed_total'],2) }}</strong> · Approved: <strong>₱{{ number_format($decisionSummary['approved_total'],2) }}</strong></p>
    @if($decisionSummary['responded_at'])<p style="font-size:12px;color:#64748b;">Submitted by {{ $decisionSummary['client_name'] }} · {{ \Carbon\Carbon::parse($decisionSummary['responded_at'])->timezone('Asia/Manila')->format('M j, Y g:i A') }} (Asia/Manila)</p>@endif
    <div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">
        <thead><tr><th style="padding:8px;">Item</th><th style="padding:8px;">Amount</th><th style="padding:8px;">Status</th><th style="padding:8px;">Client Comment</th></tr></thead>
        <tbody>@foreach($decisionSummary['items'] as $item)<tr style="border-top:1px solid #e2e8f0;">
        <td style="padding:8px;">{{ $item['name'] }}</td><td style="padding:8px;">₱{{ number_format($item['amount'],2) }}</td>
        <td style="padding:8px;"><span style="border-radius:999px;padding:3px 8px;background:{{ $item['status']==='Approved' ? '#dcfce7' : ($item['status']==='Rejected' ? '#fee2e2' : '#f1f5f9') }};">{{ $item['status'] }}</span></td>
        <td style="padding:8px;">{{ $item['comment'] ?: '—' }}</td></tr>@endforeach</tbody>
    </table></div>
    @if(($showNotificationStatus ?? true) && $proposal->decision_notification_error)<p style="font-size:12px;color:#b45309;">Decision saved. Handler email delivery is awaiting retry.</p>@endif
</section>
