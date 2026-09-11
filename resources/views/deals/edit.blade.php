@extends('layouts.app')

@section('content')
<style>
    .deal-edit-page { padding:35px 30px; background:#f8fafc; min-height:calc(100vh - 60px); }
    .deal-edit-card { max-width:760px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:24px; }
    .deal-edit-card h1 { margin:0 0 6px; color:#020617; font-size:26px; }
    .deal-edit-card p { color:#64748b; font-size:13px; margin:0 0 22px; }
    .deal-edit-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .deal-edit-field label { display:block; color:#64748b; font-size:12px; font-weight:600; margin-bottom:6px; }
    .deal-edit-field input, .deal-edit-field select { width:100%; height:40px; border:1px solid #dbe3ef; border-radius:8px; padding:0 11px; font-size:13px; }
    .deal-edit-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:22px; }
    .deal-edit-actions a, .deal-edit-actions button { border:1px solid #dbe3ef; border-radius:8px; background:#fff; color:#334155; padding:10px 16px; text-decoration:none; font-size:12px; cursor:pointer; }
    .deal-edit-actions button { background:#2563eb; border-color:#2563eb; color:#fff; }
    @media (max-width:600px) { .deal-edit-page { padding:24px 16px; } .deal-edit-grid { grid-template-columns:1fr; } }
</style>

<div class="deal-edit-page">
    <div class="deal-edit-card">
        <h1>Edit Deal</h1>
        <p>{{ $deal->deal_code ?: 'Deal record' }}</p>
        <form method="POST" action="{{ route('deals.update', $deal) }}">
            @csrf
            @method('PUT')
            <div class="deal-edit-grid">
                <div class="deal-edit-field"><label for="deal_title">Deal Title</label><input id="deal_title" name="deal_title" value="{{ old('deal_title', $deal->deal_title) }}"></div>
                <div class="deal-edit-field"><label for="pipeline_stage">Pipeline Stage</label><select id="pipeline_stage" name="pipeline_stage">@foreach(['Inquiry', 'Qualification', 'Consultation', 'Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won', 'Closed Lost'] as $stage)<option value="{{ $stage }}" @selected(old('pipeline_stage', $deal->pipeline_stage) === $stage)>{{ $stage }}</option>@endforeach</select></div>
                <div class="deal-edit-field"><label for="company">Company / Client</label><input id="company" name="company" value="{{ old('company', $deal->company) }}"></div>
                <div class="deal-edit-field"><label for="amount">Deal Value</label><input id="amount" name="amount" type="number" min="0" step="0.01" value="{{ old('amount', $deal->amount) }}"></div>
                <div class="deal-edit-field"><label for="expected_close">Expected Close Date</label><input id="expected_close" name="expected_close" type="date" value="{{ old('expected_close', optional($deal->expected_close)->format('Y-m-d')) }}"></div>
                <div class="deal-edit-field"><label for="owner_name">Deal Owner</label><input id="owner_name" name="owner_name" value="{{ old('owner_name', $deal->owner_name) }}"></div>
            </div>
            <div class="deal-edit-actions"><a href="{{ route('deals.show', $deal) }}">Cancel</a><button type="submit">Save Deal</button></div>
        </form>
    </div>
</div>
@endsection