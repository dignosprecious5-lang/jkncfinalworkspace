@extends('layouts.app')
@section('title', ($pageTitle ?? 'Regular Registry').' · ORDO')
@section('content')
<div class="registry-layout">
    <nav class="side-menu">
        <div class="menu-head"><strong>Enterprise Menu</strong><small>Navigation</small></div>
        <p>♙ Admin</p><p>⚑ Town Hall</p><p>▤ Corporate</p><p>▧ Policies</p><p>▥ Finance</p><p>♟ Human Capital</p>
        <p class="parent">⌘ Operations</p>
        <div class="submenu">
            <a class="{{ empty($activeModule) ? 'active' : '' }}" href="{{ route('projects.index') }}">Overview</a>
            @foreach(config('ordo.modules') as $key => $label)
                <a class="{{ ($activeModule ?? '') === $key ? 'active' : '' }}" href="{{ route('portfolio.module', $key) }}">{{ $label }}</a>
            @endforeach
        </div>
    </nav>
    <section class="page">
        <div class="page-head"><div><span class="eyebrow">ORDO OPERATIONS</span><h1>{{ $pageTitle ?? 'Regular Registry' }}</h1><p>Manage projects through one governed lifecycle and one consistent workspace.</p></div><button class="primary" type="button" onclick="document.getElementById('createProject').showModal()">+ Create Regular</button></div>
        @if($activeModule === 'settings')
<form class="card content" method="post" action="{{ route('preferences.save') }}">@csrf<label>My name (used for For Me filtering)<input name="viewer" required maxlength="255" value="{{ $viewer }}"></label><button>Save preferences</button><p>Approval and delivery gates are mandatory. These preferences do not grant access or reviewer permissions.</p></form>
@else
@if($activeModule === 'reports')<p><a class="button" href="{{ route('projects.export') }}">Download registry CSV</a></p>@endif
<div class="kpis">
            <article><small>ALL REGULAR SERVICES</small><strong>{{ count($projects) }}</strong></article>
            <article><small>IN PROGRESS</small><strong>{{ collect($projects)->whereNotIn('stage',['Completed'])->count() }}</strong></article>
            <article><small>NEEDS ATTENTION</small><strong>{{ collect($projects)->whereIn('health',['At Risk','Needs Attention'])->count() }}</strong></article>
            <article><small>COMPLETED</small><strong>{{ collect($projects)->where('stage','Completed')->count() }}</strong></article>
        </div>
        <div class="card">
            <div class="card-head"><div><h2>Regular Registry</h2><p>Open any record in the standardized NTP-style workspace.</p></div><input id="projectSearch" aria-label="Search projects" placeholder="Search projects…"></div>
            <div class="table-wrap"><table><thead><tr><th>Regular</th><th>Deal</th><th>Company</th><th>Phase</th><th>Owner</th><th>Target</th><th></th></tr></thead><tbody>
            @foreach($projects as $project)
                <tr data-search="{{ strtolower($project['title'].' '.$project['business'].' '.$project['ref']) }}">
                    <td><strong>{{ $project['title'] }}</strong><small>{{ $project['ref'] }}</small></td><td>{{ $project['deal'] }}</td><td>{{ $project['business'] }}</td>
                    <td><span class="badge {{ strtolower(str_replace(' ','-',$project['stage'])) }}">{{ $project['stage'] }}</span></td><td>{{ $project['lead'] }}</td><td>{{ $project['target'] }}</td>
                    <td><a class="button" href="{{ route('projects.workspace', $project['id']) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody></table></div>
        </div>
        @endif
    </section>
</div>
<dialog id="createProject"><form method="post" action="{{ route('projects.create') }}">@csrf<h2>Create project</h2><div class="form-grid">
@foreach(['title'=>'Regular title','business'=>'Company','lead'=>'Owner','workOrder'=>'Work order / Service memo','client'=>'Client','deal'=>'Deal reference'] as $field=>$label)<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field) }}" maxlength="255" @required(!in_array($field,['client','deal']))></label>@endforeach
<label>Target completion<input name="target" type="date" required value="{{ old('target') }}"></label></div><div class="actions"><button>Create project</button><button type="button" onclick="this.closest('dialog').close()">Cancel</button></div></form></dialog>
@endsection
