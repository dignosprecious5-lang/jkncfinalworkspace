@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Bar -->
    <div class="flex justify-between items-center bg-white p-4 rounded-lg border border-slate-200 shadow-sm w-full">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Client Requirements Management</h1>
            <p class="text-xs text-slate-500">Track, upload, and verify document compliance submitted by clients.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ Route::has('export.requirements.excel') ? route('export.requirements.excel') : '#' }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3 py-1.5 rounded shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-file-excel"></i> Export Excel
            </a>
            <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-3 py-1.5 rounded shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-file-import"></i> Import File
            </button>
            <a href="{{ Route::has('services.index') ? route('services.index') : '/services' }}" class="text-xs text-slate-600 hover:text-slate-900 font-semibold border border-slate-300 px-3 py-1.5 rounded bg-white transition">
                &larr; Back to Services
            </a>
            <button onclick="document.getElementById('addRequirementModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-2 rounded shadow-sm transition">
                + Add Requirement Template
            </button>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 px-4 py-2.5 rounded text-xs font-semibold flex justify-between items-center shadow-sm w-full">
            <span><i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-100 border border-rose-300 text-rose-800 px-4 py-2.5 rounded text-xs font-semibold flex justify-between items-center shadow-sm w-full">
            <span><i class="fa-solid fa-circle-xmark mr-2"></i> {{ session('error') }}</span>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm flex items-center justify-between text-xs w-full">
        <form action="{{ Route::has('requirements.index') ? route('requirements.index') : '/requirements' }}" method="GET" class="flex items-center space-x-3 w-full">
            <div class="relative flex-1 max-w-xs">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search document name..." class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded outline-none focus:border-blue-500 text-xs">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400"></i>
            </div>
            <select name="status" class="border border-slate-300 rounded px-2.5 py-1.5 outline-none text-xs text-slate-700">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-3.5 py-1.5 rounded transition shadow-sm">Filter</button>
            <a href="{{ Route::has('requirements.index') ? route('requirements.index') : '/requirements' }}" class="border border-slate-300 text-slate-600 px-3 py-1.5 rounded bg-white hover:bg-slate-50 transition">Reset</a>
        </form>
    </div>

    <!-- Requirements Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden w-full">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-semibold">
                    <th class="py-3 px-4">Document Name</th>
                    <th class="py-3 px-4">Target Service</th>
                    <th class="py-3 px-4">Client Type</th>
                    <th class="py-3 px-4">Source</th>
                    <th class="py-3 px-4">Rules</th>
                    <th class="py-3 px-4">Compliance Status</th>
                    <th class="py-3 px-4">File Attachment</th>
                    <th class="py-3 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($requirements ?? [] as $req)
                    @php
                        $serviceId = $req->service_id ?? optional($req->service)->id;
                        $serviceName = optional($req->service)->name ?? $req->service_name ?? $req->target_service ?? 'General Catalog';
                    @endphp
                    <tr class="hover:bg-slate-50 transition">
                        
                        <!-- DOCUMENT NAME -->
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            {{ $req->document_name ?? $req->name ?? 'Untitled Document' }}
                            <span class="block text-[10px] text-slate-400 font-normal">ID {{ $req->id }}</span>
                        </td>

                        <!-- CLICKABLE TARGET SERVICE LINK -->
                        <td class="py-3 px-4 font-medium">
                            @if($serviceId)
                                <a href="{{ Route::has('services.workspace') ? route('services.workspace', ['service' => $serviceId, 'mode' => 'edit', 'tab' => 'requirements']) : '/services/' . $serviceId . '/workspace' }}" 
                                   class="text-blue-600 hover:text-blue-800 hover:underline font-semibold flex items-center gap-1"
                                   title="Open Service Workspace">
                                    <span>{{ $serviceName }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-70"></i>
                                </a>
                            @else
                                <span class="text-slate-500">{{ $serviceName }}</span>
                            @endif
                        </td>

                        <!-- CLIENT TYPE -->
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                                {{ $req->client_type ?? 'All' }}
                            </span>
                        </td>

                        <!-- SOURCE -->
                        <td class="py-3 px-4 text-slate-600">
                            {{ $req->source ?? 'Client-supplied' }}
                        </td>

                        <!-- RULES -->
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                {{ ($req->is_mandatory ?? true) ? 'Mandatory' : 'Optional' }}
                            </span>
                            <span class="block text-[9px] text-blue-500 mt-0.5">File Upload</span>
                        </td>

                        <!-- COMPLIANCE STATUS -->
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 text-[10px] rounded-full font-bold uppercase tracking-wider
                                @if(strtolower($req->status ?? '') === 'verified') bg-emerald-100 text-emerald-700
                                @elseif(strtolower($req->status ?? '') === 'rejected') bg-rose-100 text-rose-700
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ strtoupper($req->status ?? 'PENDING') }}
                            </span>
                        </td>

                        <!-- FILE ATTACHMENT -->
                        <td class="py-3 px-4 text-slate-400 italic">
                            @if(!empty($req->file_path))
                                <a href="{{ route('requirements.view', $req->id) }}" target="_blank" class="text-blue-600 not-italic hover:underline font-semibold flex items-center gap-1">
                                    <i class="fa-solid fa-paperclip"></i> View File
                                </a>
                            @else
                                No file uploaded
                            @endif
                        </td>

                        <!-- ACTIONS -->
                        <td class="py-3 px-4 text-center space-x-2">
                            <button onclick="openUploadModal('{{ $req->id }}')" class="text-blue-600 hover:underline font-semibold cursor-pointer">Upload</button>
                            <button onclick="openVerifyModal('{{ $req->id }}')" class="text-purple-600 hover:underline font-semibold cursor-pointer">Review</button>
                            
                            <form action="{{ Route::has('requirements.destroy') ? route('requirements.destroy', $req->id) : '/requirements/' . $req->id }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this requirement?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:underline font-semibold cursor-pointer">Delete</button>
                            </form>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            No requirements found. Click <strong>+ Add Requirement Template</strong> to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection