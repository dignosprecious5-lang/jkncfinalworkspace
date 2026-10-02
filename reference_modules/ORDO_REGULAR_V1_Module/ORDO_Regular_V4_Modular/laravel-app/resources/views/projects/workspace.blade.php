@extends('layouts.app')
@section('title', $sections[$section].' · '.$record['ref'])
@section('content')
@php
    $p=$record;
    $unlocked=$p['sow']==='approved' && $p['ntp'];
    $total=\App\Services\ProjectWorkflow::seconds($p['timer'])+collect($p['tasks'])->sum(fn($t)=>\App\Services\ProjectWorkflow::seconds($t['timer']));
    $done=collect($p['tasks'])->where('done',true)->count();
@endphp
<div class="workspace">
    <div class="breadcrumb"><a href="{{ route('projects.index') }}">← Regular registry</a> / {{ $p['ref'] }}</div>
    <section class="workspace-head"><div><span class="eyebrow">REGULAR WORKSPACE</span><h1>{{ $p['title'] }}</h1><p>{{ $p['ref'] }} · {{ $p['deal'] ?? '' }} · {{ $p['workOrder'] }}</p></div><div class="facts"><span>Business<strong>{{ $p['business'] }}</strong></span><span>Owner<strong>{{ $p['lead'] }}</strong></span><span>Target<strong>{{ $p['target'] }}</strong></span></div></section>
    <nav class="tabs" aria-label="Regular sections">@foreach($sections as $key=>$name)<a @if($section===$key) aria-current="page" @endif class="{{ $section===$key?'active':'' }}" href="{{ $key==='project-dashboard'?route('projects.workspace',$p['id']):route('projects.section',[$p['id'],$key]) }}">{{ $name }}</a>@endforeach</nav>
    @php($stages=['Work Order','Plan','Review','NTP','Execution'])
    @php($current=array_search($p['stage'],$stages,true))
    <section class="lifecycle"><div><strong>Regular lifecycle</strong><small>{{ $p['stage'] }}</small></div><div class="stage-row">@foreach($stages as $i=>$stage)<span class="{{ $i<$current?'done':($i===$current?'current':'') }}">{{ $stage }}</span>@endforeach</div></section>
    <div class="workspace-grid"><aside class="quick card"><h3>REGULAR STATUS</h3><p class="status">RSAT: {{ $p['sow'] }}</p><p class="status">NTP: {{ $p['ntp']?'Approved':'Pending' }}</p><p class="status">{{ $p['closed']?'Closed':'Open' }}</p><h4>REGULAR DIRECT TIMER</h4><div class="timer"><strong data-seconds="{{ $p['timer']['seconds'] }}" data-started="{{ $p['timer']['started'] }}">00:00:00</strong></div><div class="timer-controls">
    @include('projects.action',['action'=>'timer-start','label'=>'Start','disabled'=>!$unlocked])
    @include('projects.action',['action'=>'timer-pause','label'=>'Pause'])
    @include('projects.action',['action'=>'timer-stop','label'=>'Stop'])
    </div></aside><section class="content card"><div class="section-title"><h2>{{ $sections[$section] }}</h2><span class="badge">{{ $p['health'] }}</span></div>
    @if($p['closed'])<p class="callout">This regular service is closed. Its records remain available for viewing and download.</p>@endif
    @if($section==='project-dashboard')
        <div class="metric-grid"><article><small>PROGRESS</small><strong>{{ $p['progress'] }}%</strong></article><article><small>TASKS COMPLETED</small><strong>{{ $done }}/{{ count($p['tasks']) }}</strong></article><article><small>HANDLING</small><strong>{{ gmdate('H:i:s',$total) }}</strong></article></div>
        <p>{{ $p['scope'] ?: 'Prepare the RSAT to begin the approval process.' }}</p><a class="button" href="{{ route('projects.section',[$p['id'],$unlocked?'execution':'scope-of-work']) }}">{{ $unlocked?'Open execution':'Open RSAT' }}</a>
    @elseif($section==='work-order')
        <dl><dt>Work order / Service memo</dt><dd>{{ $p['workOrder'] }}</dd><dt>Deal</dt><dd>{{ $p['deal'] ?? '—' }}</dd><dt>Client</dt><dd>{{ $p['client'] ?? '—' }}</dd><dt>Target completion</dt><dd>{{ $p['target'] }}</dd></dl>
    @include('projects.action',['action'=>'work-order-approve','label'=>'Approve Work Order','disabled'=>$p['workOrderApproved']??true])
    @elseif($section==='scope-of-work')
        <form id="rsatBackendForm" method="post" action="{{ route('projects.action',[$p['id'],'rsat-save']) }}">@csrf<input type="hidden" name="rowsJson" id="rsatRowsJson"><input type="hidden" name="rsatSummary" id="rsatSummaryValue" value="{{ $p['rsatReportSummary'] ?? '' }}"><div id="rsatForm"></div><button @disabled($p['closed'] || $p['sow']!=='draft' || !($p['workOrderApproved']??true))>Save RSAT</button></form>
        @include('projects.action',['action'=>'scope-submit','label'=>'Submit saved RSAT for review','disabled'=>$p['sow']!=='draft' || !$p['scope']])
        @push('scripts')
        <link rel="stylesheet" href="{{ asset('css/rsat-form.css') }}">
        <script src="{{ asset('js/rsat-recurrence.js') }}"></script>
        <script>
        (()=>{const record=@json($p),R=RSAT_RECURRENCE;
        const rows=record.rsatRows||record.tasks.map(task=>({id:task.id,service:task.group||record.title,activity:task.title,schedules:task.recurringRules||[R.defaults()]}));
        window.RSAT_ADAPTER={summary:()=>document.getElementById('rsatSummaryValue').value,saveSummary:value=>{document.getElementById('rsatSummaryValue').value=value;},read:()=>rows,editable:()=>!record.closed&&record.sow==='draft'&&(record.workOrderApproved??true),metadata:()=>({client:record.rsatClient||(record.id===120?'May Flor D. Dabatos and Stephan Tenten':record.client),business:record.business,deal:record.deal,version:String(record.regular.planRevision||1)+'.0',prepared:record.datePrepared||(record.id===120?'08/20/2026':'Not recorded'),lead:record.lead,associate:record.leadAssociate||'Rubeca Potayre',servicePreparedBy:record.servicePreparedBy||record.lead+' · '+(record.leadAssociate||'Rubeca Potayre'),activityPreparedBy:record.activityPreparedBy||record.lead+' · '+(record.leadAssociate||'Rubeca Potayre'),ref:record.rsatNo||(record.id===120?'RSAT-2026-032':'REG-RSAT-'+record.id+'-'+record.regular.cycle),cycle:record.regular.cycle}),save:rows=>{document.getElementById('rsatRowsJson').value=JSON.stringify(rows);}};
        RSAT_ADAPTER.save(rows);document.getElementById('rsatBackendForm').addEventListener('submit',event=>{try{RSAT_FORM.validate();RSAT_ADAPTER.save(RSAT_FORM.rows());}catch(error){event.preventDefault();alert(error.message);}});
        })();
        </script><script src="{{ asset('js/rsat-form.js') }}"></script>
        @endpush
    @elseif($section==='review')
        @php($reviewers=$p['reviewers'] ?? [])
        @php($accepted=count(array_filter($reviewers)))
        <p>Review started: {{ $p['reviewStartedAt'] ?? 'Seeded approval' }}</p><p>Required reviewer acceptance: {{ $accepted }} / {{ count($reviewers) }}</p><progress max="{{ max(1,count($reviewers)) }}" value="{{ $accepted }}"></progress>
        @forelse($reviewers as $name=>$approved)<div class="list-row"><strong>{{ $name }}</strong><span>{{ $approved?'Accepted':'Pending' }}<small>{{ $p['reviewResponses'][$name] ?? '' }}</small></span>@include('projects.action',['action'=>'scope-approve','label'=>'Record acceptance','reviewer'=>$name,'disabled'=>$approved || $p['sow']!=='review'])</div>@empty<p>Submit a scope draft to start review.</p>@endforelse
        <form method="post" action="{{ route('projects.action',[$p['id'],'scope-revert']) }}">@csrf<fieldset @disabled($p['closed'] || $p['sow']!=='review')><label>Reason to return to planning<textarea name="note" required maxlength="2000"></textarea></label><button>Return to planning</button></fieldset></form>
    @elseif($section==='ntp')
        <p>{{ $p['ntp']?'Client approval recorded.':'Execution stays locked until client approval is recorded.' }}</p>
        <form method="post" action="{{ route('projects.action',[$p['id'],'ntp-approve']) }}">@csrf<fieldset @disabled($p['closed'] || $p['sow']!=='approved')><label>NTP type<select name="ntpType"><option value="original">Original NTP</option><option value="supplemental" @selected(($p['ntpType']??'')==='supplemental')>Supplemental NTP</option></select></label><label>Change package reference (required for supplemental NTP)<input name="changePackage" value="{{ old('changePackage',$p['changePackage']??'') }}" maxlength="2000"></label><label>Approval method and evidence reference<textarea name="note" maxlength="2000">{{ old('note',$p['ntpNote']??'') }}</textarea></label><button formaction="{{ route('projects.action',[$p['id'],'ntp-issue']) }}">Issue NTP</button> <button>Record client approval</button></fieldset></form>
    @elseif($section==='execution')
        @unless($unlocked)<p class="callout">Approve RSAT and NTP to unlock execution.</p>@endunless
        @forelse($p['tasks'] as $task)<div class="list-row"><span><strong>{{ $task['title'] }}</strong><small>{{ $task['assignee'] }} · {{ $task['done']?'Completed':'Open' }} · <span data-seconds="{{ $task['timer']['seconds'] }}" data-started="{{ $task['timer']['started'] }}"></span></small></span><div class="actions">
        @include('projects.action',['action'=>'timer-start','label'=>'Start','item'=>$task['id'],'disabled'=>!$unlocked || $task['done']])
        @include('projects.action',['action'=>'timer-stop','label'=>'Stop','item'=>$task['id']])
        @include('projects.action',['action'=>'task-done','label'=>$task['done']?'Reopen':'Complete','item'=>$task['id'],'disabled'=>!$unlocked || !!$p['transmittal']])
        @include('projects.action',['action'=>'task-duplicate','label'=>'Duplicate','item'=>$task['id'],'disabled'=>!$unlocked || !!$p['transmittal']])
        </div></div>@empty<p>No tasks yet.</p>@endforelse
        <form method="post" action="{{ route('projects.action',[$p['id'],'task-add']) }}">@csrf<fieldset @disabled($p['closed'] || !$unlocked || $p['transmittal'])><div class="form-grid"><label>Task title<input name="title" required maxlength="255"></label><label>Assignee<input name="assignee" value="{{ $p['lead'] }}" required maxlength="255"></label></div><button>Add task</button></fieldset></form>
    @elseif($section==='files-evidence')
        @forelse($p['files'] as $file)<div class="list-row"><span>{{ $file['name'] }}</span><a class="button" href="{{ route('projects.file',[$p['id'],$file['id']]) }}">Download</a></div>@empty<p>No attachments uploaded.</p>@endforelse
        <form method="post" enctype="multipart/form-data" action="{{ route('projects.action',[$p['id'],'file-add']) }}">@csrf<fieldset @disabled($p['closed'])><label>Attachment (up to 10 MB)<input type="file" name="attachment" required></label><button>Upload evidence</button></fieldset></form>
    @elseif($section==='time-aht')
        <div class="metric-grid"><article><small>TOTAL HANDLING</small><strong>{{ intdiv($total,3600) }}h {{ intdiv($total%3600,60) }}m</strong></article><article><small>AHT / COMPLETED TASK</small><strong>{{ $done?round($total/$done/60).'m':'—' }}</strong></article><article><small>COMPLETED TASKS</small><strong>{{ $done }}</strong></article></div><p>Handling includes direct project time and task time. Starting a timer stops the previously running timer.</p>
    @elseif($section==='client-actions')
        @forelse($p['actions'] as $entry)<div class="list-row"><span>{{ $entry['subject'] }} · {{ $entry['done']?'Completed':'Pending' }}</span>@include('projects.action',['action'=>'client-done','label'=>$entry['done']?'Reopen':'Complete','item'=>$entry['id']])</div>@empty<p>No client actions.</p>@endforelse
        <form method="post" action="{{ route('projects.action',[$p['id'],'client-add']) }}">@csrf<fieldset @disabled($p['closed'])><label>Client action<input name="subject" required maxlength="255"></label><button>Add action</button></fieldset></form>
    @elseif($section==='sow-report')
        <p>Report status: {{ $p['report']['approved']?'Approved':'Draft' }}</p><form method="post" action="{{ route('projects.action',[$p['id'],'report-save']) }}">@csrf<fieldset @disabled($p['closed'] || $p['transmittal'])>
        @foreach($p['tasks'] as $task)<label class="check-label"><input type="checkbox" name="selected[]" value="{{ $task['id'] }}" @checked(in_array($task['id'],$p['report']['selected']))>{{ $task['title'] }} — {{ $task['done']?'Completed':'Ongoing / Pending' }}</label>@endforeach
        @foreach(['issues'=>'Issues and observations','recommendations'=>'Recommendations','way'=>'Way forward'] as $key=>$label)<label>{{ $label }}<textarea name="{{ $key }}" maxlength="20000">{{ old($key,$p['report'][$key]) }}</textarea></label>@endforeach<button>Save report</button></fieldset></form>
        @include('projects.action',['action'=>'report-approve','label'=>'Approve saved report','disabled'=>!$unlocked || !count($p['tasks']) || !$p['report']['selected'] || $p['report']['approved']])
        <button type="button" onclick="window.print()">Print saved report</button>
    @elseif($section==='history-updates')
        <form method="post" action="{{ route('projects.action',[$p['id'],'update']) }}">@csrf<fieldset @disabled($p['closed'])><label>Regular update<textarea name="text" required maxlength="2000"></textarea></label><button>Post update</button></fieldset></form>
        @foreach($p['updates'] as $update)<div class="list-row"><span>{{ $update['text'] }}</span><small>{{ $update['at'] }}</small></div>@endforeach
        <h3>History</h3>@foreach($p['history'] as $entry)<div class="list-row"><span>{{ $entry['action'] }}</span><small>{{ $entry['at'] }}</small></div>@endforeach
    @elseif($section==='delivery-completion')
        @foreach($p['deliverables'] as $d)<div class="list-row"><span>{{ $d['name'] }} · {{ $d['ready']?'Ready':'Pending' }}</span>@include('projects.action',['action'=>'deliverable-toggle','label'=>$d['ready']?'Reopen':'Mark ready','item'=>$d['id'],'disabled'=>!$unlocked || !!$p['transmittal']])</div>@endforeach
        <form method="post" action="{{ route('projects.action',[$p['id'],'deliverable-add']) }}">@csrf<fieldset @disabled($p['closed'] || !$unlocked || $p['transmittal'])><label>Deliverable<input name="name" required maxlength="255"></label><button>Add deliverable</button></fieldset></form>
        <p>Tasks: {{ $done }}/{{ count($p['tasks']) }} · Report: {{ $p['report']['approved']?'Approved':'Pending' }}</p><p>Transmittal: {{ $p['transmittal'] ?: 'Pending' }}</p>
        @include('projects.action',['action'=>'transmittal','label'=>'Issue transmittal','disabled'=>!$p['report']['approved'] || !$unlocked || !!$p['transmittal']])
        @include('projects.action',['action'=>'close','label'=>'Close engagement','disabled'=>!$p['transmittal']])
        <details><summary>Suspend engagement</summary><form method="post" action="{{ route('projects.action',[$p['id'],'suspend']) }}">@csrf<label>Reason<textarea name="note" required></textarea></label><button @disabled($p['closed'])>Suspend engagement</button></form></details>
        @if($p['regular']['status']==='Suspended')<form method="post" action="{{ route('projects.action',[$p['id'],'resume']) }}">@csrf<button>Resume engagement</button></form>@endif
    @endif
    </section></div>
</div>
@endsection
