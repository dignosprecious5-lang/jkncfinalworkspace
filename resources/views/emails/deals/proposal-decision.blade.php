<!doctype html>
<html lang="en"><body style="margin:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#1e293b;">
<div style="max-width:680px;margin:24px auto;padding:28px;background:white;border:1px solid #e2e8f0;border-radius:12px;">
<h2 style="color:#0f172a;">Proposal Decision Received</h2>
<p>Hello {{ $handler['name'] }},</p>
<p>The client has submitted a decision regarding the proposal you handled.</p>
<p><strong>Proposal Reference:</strong> {{ $summary['reference'] }} (Version {{ $summary['version'] }})<br>
<strong>Proposal Title:</strong> {{ $summary['title'] }}<br>
<strong>Client:</strong> {{ $summary['client_name'] }}<br>
<strong>Company:</strong> {{ $summary['company'] }}<br>
<strong>Decision Date:</strong> {{ $summary['responded_at'] ? \Carbon\Carbon::parse($summary['responded_at'])->timezone('Asia/Manila')->format('F j, Y g:i A') . ' (Asia/Manila)' : 'Not recorded' }}<br>
<strong>Overall Status:</strong> {{ $summary['status_label'] }}</p>
@foreach(['approved'=>'Approved Items','rejected'=>'Rejected Items','pending'=>'Pending Items'] as $key=>$label)
<h3>{{ $label }}</h3>
<ul>@forelse($summary['groups'][$key] as $item)
<li>{{ $item['name'] }} — ₱{{ number_format($item['amount'],2) }}
@if(filled($item['comment']))<br>Client comment: {{ $item['comment'] }}@endif</li>
@empty<li>None</li>@endforelse</ul>
@endforeach
<p><strong>Total Proposal Amount:</strong> ₱{{ number_format($summary['proposed_total'],2) }}<br>
<strong>Approved Total:</strong> ₱{{ number_format($summary['approved_total'],2) }}</p>
<p><a href="{{ $proposalUrl }}" style="display:inline-block;padding:12px 18px;border-radius:8px;background:#2563eb;color:#fff;text-decoration:none;">View Proposal and Decision Details</a></p>
<p>Please log in to review the complete proposal and the client's comments.</p>
<p style="font-size:12px;color:#64748b;">This is an automated notification.</p>
</div></body></html>
