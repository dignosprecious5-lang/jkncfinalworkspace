@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    @php
        $currentUser = auth()->user();
        $userRole = $currentUser->role ?? 'client';
        $isManager = in_array($userRole, ['manager', 'admin']) || ($currentUser && $currentUser->email === 'manager@example.com');
    @endphp

    <!-- Header Navigation & Action Bar -->
    <div class="flex justify-between items-center bg-white p-4 rounded-lg border border-slate-200 shadow-sm w-full">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Client Requirements Management</h1>
            <p class="text-xs text-slate-500">Track, upload, and verify document compliance submitted by clients.</p>
        </div>
        <div class="flex items-center space-x-2">
            <!-- Export Excel Button -->
            <a href="{{ Route::has('export.requirements.excel') ? route('export.requirements.excel') : '#' }}" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-3.5 py-2 rounded-lg text-xs shadow transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-file-excel text-xs"></i>
                <span>Export Excel</span>
            </a>

            <!-- Import Trigger Button -->
            <button type="button" 
                    onclick="openImportModal()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-3.5 py-2 rounded-lg text-xs shadow transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-file-import text-xs"></i>
                <span>Import File</span>
            </button>

            <a href="{{ Route::has('clients.index') ? route('clients.index') : '/clients' }}" class="text-xs text-slate-600 hover:text-slate-900 font-semibold border border-slate-300 px-3 py-1.5 rounded bg-white transition">
    &larr; Back to Clients
</a>

            @if($isManager)
                <button onclick="document.getElementById('addReqModal').classList.remove('hidden')" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-2 rounded shadow-sm transition cursor-pointer">
                    + Add Requirement Template
                </button>
            @endif
        </div>
    </div>

    <!-- Flash Messages -->
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

    <!-- Search & Filter Bar -->
    <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm flex items-center justify-between text-xs w-full">
        <form action="{{ Route::has('requirements.index') ? route('requirements.index') : '/requirements' }}" method="GET" class="flex items-center space-x-3 w-full">
            <div class="relative flex-1 max-w-xs">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search document name..." class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded outline-none focus:border-blue-500 text-xs">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400"></i>
            </div>
            <select name="status" class="border border-slate-300 rounded px-2.5 py-1.5 outline-none text-xs text-slate-700">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-3.5 py-1.5 rounded transition shadow-sm cursor-pointer">Filter</button>
            <a href="{{ Route::has('requirements.index') ? route('requirements.index') : '/requirements' }}" class="border border-slate-300 text-slate-600 px-3 py-1.5 rounded bg-white hover:bg-slate-50 transition">Reset</a>
        </form>
    </div>

    <!-- Requirements Data Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden w-full">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-semibold">
                    <th class="py-3 px-4">DOCUMENT NAME</th>
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
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4">
                            <span class="font-semibold text-slate-900 block leading-tight">
                                {{ $req->document_name ?? $req->requirement_name }}
                            </span>
                            <span class="text-[11px] text-slate-400 font-normal block mt-0.5">
                                ID {{ $req->id }}
                            </span>
                            @if(!empty($req->description ?? $req->instructions))
                                <span class="block text-[10px] text-slate-400 font-normal mt-0.5">{{ $req->description ?? $req->instructions }}</span>
                            @endif
                            @if(!empty($req->conditional_rule))
                                <span class="block text-[10px] text-amber-600 font-medium mt-0.5"><i class="fa-solid fa-code-branch me-1"></i> Rule: {{ $req->conditional_rule }}</span>
                            @endif
                            @if(!empty($req->rejection_reason))
                                <span class="block text-[10px] text-rose-600 font-medium mt-0.5">Reason: {{ $req->rejection_reason }}</span>
                            @endif
                        </td>

                        <!-- TARGET SERVICE (CLICKABLE LINK TO WORKSPACE) -->
                        <td class="py-3 px-4 font-medium">
                            @php
                                $targetServiceId = $req->service_id 
                                    ?? optional($req->service)->id 
                                    ?? optional(optional($req->serviceVersion)->service)->id
                                    ?? \App\Models\Service::where('name', 'like', '%' . ($req->target_service ?? $req->service_name ?? '') . '%')->value('id')
                                    ?? 1;

                                $targetServiceName = optional($req->service)->name 
                                    ?? optional(optional($req->serviceVersion)->service)->name 
                                    ?? $req->target_service 
                                    ?? $req->service_name 
                                    ?? 'General Service';
                            @endphp

                            <a href="{{ Route::has('services.workspace') ? route('services.workspace', ['service' => $targetServiceId, 'mode' => 'edit', 'tab' => 'requirements']) : '/services/' . $targetServiceId . '/workspace?mode=edit&tab=requirements' }}" 
                               class="text-blue-600 hover:text-blue-800 hover:underline font-bold inline-flex items-center gap-1"
                               title="Open Service Workspace">
                                <span>{{ $targetServiceName }}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-75"></i>
                            </a>
                        </td>

                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-medium border border-slate-200">
                                {{ $req->client_type ?? 'All Types' }}
                            </span>
                        </td>

                        <td class="py-3 px-4 text-slate-600">
                            {{ $req->source ?? 'Client-supplied' }}
                        </td>

                        <td class="py-3 px-4 space-y-1">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $req->is_mandatory ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $req->is_mandatory ? 'Mandatory' : 'Optional' }}
                                </span>
                            </div>
                            @if($req->file_required)
                                <div><span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-[9px]">File Upload</span></div>
                            @endif
                        </td>

                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 text-[10px] rounded-full font-bold uppercase tracking-wider
                                @if(($req->status ?? '') === 'approved') bg-emerald-100 text-emerald-700
                                @elseif(($req->status ?? '') === 'submitted') bg-blue-100 text-blue-700
                                @elseif(($req->status ?? '') === 'rejected') bg-rose-100 text-rose-700
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ $req->status ?? 'pending' }}
                            </span>
                        </td>

                        <td class="py-3 px-4">
                            @if(!empty($req->file_path))
                                <button type="button" 
                                        onclick="openPreviewModal('{{ $req->id }}', '{{ addslashes($req->document_name ?? $req->requirement_name) }}')" 
                                        class="text-blue-600 hover:underline font-semibold flex items-center space-x-1 cursor-pointer">
                                    <i class="fa-solid fa-paperclip"></i>
                                    <span>View Document</span>
                                </button>
                            @else
                                <span class="text-slate-400 italic">No file uploaded</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-center space-x-2">
                            @if(($req->status ?? 'pending') !== 'approved')
                                <button type="button" onclick="openUploadModal('{{ $req->id }}', '{{ addslashes($req->document_name ?? $req->requirement_name) }}')" class="text-blue-600 hover:underline font-semibold cursor-pointer">
                                    Upload
                                </button>
                            @endif

                            @if($isManager)
                                <button type="button" onclick="openVerifyModal('{{ $req->id }}', '{{ addslashes($req->document_name ?? $req->requirement_name) }}')" class="text-purple-600 hover:underline font-semibold cursor-pointer">
                                    Review
                                </button>

                                <form action="{{ Route::has('requirements.destroy') ? route('requirements.destroy', $req->id) : '/requirements/'.$req->id }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this requirement?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:underline font-semibold cursor-pointer">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            No client requirements recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- MODAL: BULK IMPORT REQUIREMENTS -->
    <div id="importModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-xs">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-sm font-bold text-slate-900">Import Requirements File</h3>
                <button type="button" onclick="closeImportModal()" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>
            <form action="{{ route('import.requirements') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Excel / CSV File *</label>
                    <input type="file" name="file" accept=".xlsx, .csv, .xls" required
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-lg p-1">
                </div>
                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeImportModal()" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-md cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-md shadow-sm cursor-pointer">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD REQUIREMENT TEMPLATE (MANAGER ONLY) -->
    @if($isManager)
        <div id="addReqModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-xs max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-sm font-bold text-slate-900">Add Requirement Template</h3>
                    <button onclick="document.getElementById('addReqModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
                </div>
                <form action="{{ Route::has('requirements.store') ? route('requirements.store') : '/requirements' }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Target Service *</label>
                        <select name="service_id" required class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 text-slate-700">
                            @foreach($services ?? [] as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->service_code ?? 'SVC' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Document Name *</label>
                        <input type="text" name="document_name" required placeholder="e.g. BIR Form 2303 / SEC Certificate" class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Client Name</label>
                            <input type="text" name="client_name" placeholder="Optional" class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Client Email</label>
                            <input type="email" name="client_email" placeholder="Optional — sends assignment" class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Client Type *</label>
                            <select name="client_type" required class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 text-slate-700">
                                <option value="All">All Client Types</option>
                                <option value="Individual">Individual</option>
                                <option value="Sole Proprietorship">Sole Proprietorship</option>
                                <option value="Corporation">Corporation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Source *</label>
                            <select name="source" required class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 text-slate-700">
                                <option value="Client-supplied">Client-supplied</option>
                                <option value="Internally prepared">Internally prepared</option>
                                <option value="Government agency">Government agency</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4 border-t mt-4">
                        <button type="button" onclick="document.getElementById('addReqModal').classList.add('hidden')" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded cursor-pointer">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium shadow-sm cursor-pointer">Save Requirement</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL: UPLOAD FILE (CLIENT / MANAGER) -->
    <div id="uploadModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-xs">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-sm font-bold text-slate-900">Upload Requirement Document</h3>
                <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>
            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Document Name</span>
                    <span id="uploadDocName" class="font-bold text-slate-800 text-sm"></span>
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Select File (PDF, PNG, JPG, DOC) *</label>
                    <input type="file" name="document_file" required class="w-full border border-slate-300 rounded p-2 outline-none">
                </div>
                <div class="flex justify-end space-x-2 pt-4 border-t mt-4">
                    <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded cursor-pointer">Cancel</button>
                    <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium shadow-sm cursor-pointer">Submit Document</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: VERIFY / REVIEW (MANAGER) -->
    @if($isManager)
        <div id="verifyModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-xs">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-sm font-bold text-slate-900">Verify Requirement Status</h3>
                    <button onclick="document.getElementById('verifyModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
                </div>
                <form id="verifyForm" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase font-bold">Document Name</span>
                        <span id="verifyDocName" class="font-bold text-slate-800 text-sm"></span>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Action *</label>
                        <select name="status" id="verifyStatus" onchange="toggleRejectionReason()" required class="w-full border border-slate-300 rounded p-2 outline-none text-slate-700">
                            <option value="approved">Approve Document</option>
                            <option value="rejected">Reject Document</option>
                        </select>
                    </div>
                    <div id="rejectionReasonBox" class="hidden">
                        <label class="block font-semibold text-slate-600 mb-1">Rejection Reason *</label>
                        <textarea name="rejection_reason" rows="2" placeholder="State why document was rejected..." class="w-full border border-slate-300 rounded p-2 outline-none"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4 border-t mt-4">
                        <button type="button" onclick="document.getElementById('verifyModal').classList.add('hidden')" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded cursor-pointer">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded font-medium shadow-sm cursor-pointer">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL: DOCUMENT PREVIEW -->
    <div id="previewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex justify-center items-center p-3 z-50">
        <div class="bg-white rounded-lg shadow-2xl w-[95vw] h-[92vh] flex flex-col p-4 text-xs overflow-hidden">
            <div class="flex justify-between items-center border-b pb-2.5 mb-2">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">DOCUMENT VIEWER</span>
                    <h3 id="previewDocName" class="text-sm font-bold text-slate-900"></h3>
                </div>
                <button type="button" onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 w-8 h-8 rounded-full flex items-center justify-center transition text-lg font-bold cursor-pointer">
                    &times;
                </button>
            </div>
            <div class="flex-1 w-full h-full bg-slate-100 rounded border border-slate-200 overflow-hidden">
                <iframe id="previewIframe" src="" class="w-full h-full border-0 rounded"></iframe>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC FOR MODALS -->
    <script>
        function openImportModal() { document.getElementById('importModal').classList.remove('hidden'); }
        function closeImportModal() { document.getElementById('importModal').classList.add('hidden'); }

        function openUploadModal(id, name) {
            document.getElementById('uploadForm').action = '/requirements/' + id + '/upload';
            document.getElementById('uploadDocName').innerText = name;
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function openVerifyModal(id, name) {
            const form = document.getElementById('verifyForm');
            if (form) {
                form.action = '/requirements/' + id + '/verify';
                document.getElementById('verifyDocName').innerText = name;
                document.getElementById('verifyModal').classList.remove('hidden');
                toggleRejectionReason();
            }
        }

        function toggleRejectionReason() {
            const verifyStatus = document.getElementById('verifyStatus');
            const box = document.getElementById('rejectionReasonBox');
            if (verifyStatus && box) {
                if (verifyStatus.value === 'rejected') {
                    box.classList.remove('hidden');
                } else {
                    box.classList.add('hidden');
                }
            }
        }

        function openPreviewModal(reqId, docName) {
            document.getElementById('previewDocName').innerText = docName;
            document.getElementById('previewIframe').src = '/requirements/' + reqId + '/view';
            document.getElementById('previewModal').classList.remove('hidden');
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
            document.getElementById('previewIframe').src = '';
        }
    </script>

</div>
@endsection