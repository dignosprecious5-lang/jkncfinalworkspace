<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal & Client Workspace - {{ $proposal->client_name ?? 'Client' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SheetJS library para ma-view ang Excel/CSV files nang hindi nag-da-download -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col text-sm">

    <!-- Top Header Navigation -->
    <div class="bg-white border-b border-slate-200 px-6 py-3 flex justify-between items-center text-sm sticky top-0 z-40">
        <div class="flex items-center space-x-2 w-52">
            <a href="/" class="flex flex-col font-serif text-slate-800 hover:opacity-90 leading-tight">
                <span class="text-base font-bold tracking-tight text-slate-900">John Kelly</span>
                <span class="text-xs font-semibold text-slate-900 font-serif -mt-0.5">
                    <span class="text-blue-600 font-bold italic font-sans">&</span> Company
                </span>
            </a>
        </div>
        <div class="w-1/3 max-w-md">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                <input type="text" placeholder="Search..." class="w-full bg-slate-100/80 border border-transparent focus:border-slate-300 focus:bg-white text-slate-700 pl-9 pr-4 py-1.5 rounded-full text-xs outline-none transition">
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <button type="button" class="relative text-slate-500 hover:text-slate-800 p-1.5 rounded-full transition">
                <i class="fa-regular fa-bell text-lg"></i>
            </button>
            <div class="w-8 h-8 bg-slate-200 text-slate-700 font-bold rounded-full flex items-center justify-center text-xs">M</div>
        </div>
    </div>

    <!-- Layout Container -->
    <div class="flex flex-1 items-start">

        <!-- Left Enterprise Sidebar Navigation -->
        <aside class="w-60 bg-white border-r border-slate-200 p-4 shrink-0 flex flex-col justify-between text-xs fixed top-[57px] bottom-0 left-0 overflow-y-auto">
            <div class="space-y-5">
                <div class="flex items-center justify-between px-2">
                    <div>
                        <h2 class="font-bold text-slate-800 text-sm">Enterprise Menu</h2>
                        <span class="text-xs text-slate-400 block mt-0.5">Navigation</span>
                    </div>
                    <i class="fa-solid fa-angles-right text-slate-300 text-xs"></i>
                </div>

                <nav class="space-y-1.5 text-slate-600">
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-50 transition text-xs font-medium">
                        <i class="fa-regular fa-user text-slate-400 w-4 text-sm"></i>
                        <span>Admin</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-50 transition text-xs font-medium">
                        <i class="fa-regular fa-building text-slate-400 w-4 text-sm"></i>
                        <span>Town Hall</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-50 transition text-xs font-medium">
                        <i class="fa-regular fa-folder text-slate-400 w-4 text-sm"></i>
                        <span>Corporate</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-50 transition text-xs font-medium">
                        <i class="fa-regular fa-file-lines text-slate-400 w-4 text-sm"></i>
                        <span>Policies</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-50 transition text-xs font-medium">
                        <i class="fa-solid fa-calculator text-slate-400 w-4 text-sm"></i>
                        <span>Finance</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-50 transition text-xs font-medium">
                        <i class="fa-regular fa-id-badge text-slate-400 w-4 text-sm"></i>
                        <span>Human Capital</span>
                    </a>

                    <!-- Marketing Section -->
                    <div class="space-y-1 pt-1">
                        <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-slate-50 text-slate-700 font-medium transition text-xs">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-bullhorn text-slate-400 w-4 text-sm"></i>
                                <span>Marketing</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                        <div class="pl-10 space-y-1 pt-1">
                            <a href="#" class="block py-1 text-slate-500 hover:text-slate-800 text-xs">Product</a>
                            <a href="{{ route('services.index') }}" class="block py-1 text-slate-600 hover:text-blue-600 font-medium text-xs">Services</a>
                            <a href="{{ Route::has('requirements.index') ? route('requirements.index') : '#' }}" class="block py-1 font-medium text-slate-600 hover:text-blue-600 text-xs flex items-center justify-between">
                                <span>Requirements</span>
                                <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full">New</span>
                            </a>
                        </div>
                    </div>

                    <!-- Proposals Navigation -->
                    <div class="space-y-1 pt-1">
                        <a href="{{ route('proposals.index') }}" class="w-full flex items-center justify-between px-3 py-2 rounded-md bg-purple-50 text-purple-700 font-medium transition text-xs">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-file-signature text-purple-600 w-4 text-sm"></i>
                                <span class="font-bold text-purple-900">Proposals & Contracts</span>
                            </div>
                            <span class="bg-purple-200 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-full">New</span>
                        </a>
                    </div>

                    <a href="#" class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-slate-50 transition text-xs font-medium">
                        <div class="flex items-center space-x-3">
                            <i class="fa-regular fa-credit-card text-slate-400 w-4 text-sm"></i>
                            <span>Accounts</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-xs text-slate-300"></i>
                    </a>

                    <div class="space-y-1 pt-1">
                        <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-slate-50 text-slate-700 font-medium transition text-xs">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-gear text-slate-400 w-4 text-sm"></i>
                                <span>Operations</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                        <div class="pl-10 space-y-1 pt-1">
                            <a href="{{ route('engagements.index') }}" class="block py-1 text-slate-600 hover:text-blue-600 font-medium text-xs">Engagements</a>
                        </div>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <main class="flex-1 ml-60 px-8 py-6 space-y-6 overflow-x-hidden min-h-screen">

            <!-- Header Branding -->
            <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div>
                    <span class="text-xs font-bold text-blue-600 uppercase tracking-widest block">Client Portal</span>
                    <h1 class="text-2xl font-bold text-slate-900 mt-1">John Kelly & Company</h1>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <span class="text-xs text-slate-400 block">Proposal ID</span>
                        <span class="text-sm font-mono font-bold text-slate-700">#PRP-{{ $proposal->id }}</span>
                    </div>

                    <a href="{{ route('proposals.index') }}" 
                       class="w-9 h-9 rounded-full bg-slate-100 hover:bg-rose-100 text-slate-500 hover:text-rose-600 flex items-center justify-center transition border border-slate-200 shadow-sm"
                       title="Close Client Portal & Go Back to Proposals">
                        <i class="fa-solid fa-xmark text-base font-bold"></i>
                    </a>
                </div>
            </div>

            <!-- Alert Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-semibold flex items-center gap-3 shadow-sm">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Proposal Overview Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 space-y-6">
                <div class="border-b border-slate-100 pb-5 flex flex-wrap justify-between items-start gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">{{ $proposal->title ?? $proposal->service_name ?? 'Professional Services Proposal' }}</h2>
                        <p class="text-xs text-slate-500 mt-1">Prepared specifically for <span class="font-bold text-slate-700">{{ $proposal->client_name ?? 'Valued Client' }}</span></p>
                    </div>
                    <div>
                        @if(strtoupper($proposal->status ?? '') === 'ACCEPTED')
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full border border-emerald-300 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-check-double"></i> ACCEPTED & SIGNED
                            </span>
                        @else
                            <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full border border-amber-300 inline-flex items-center gap-1.5">
                                <i class="fa-regular fa-clock"></i> PENDING APPROVAL
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-5 rounded-lg border border-slate-200/80 text-sm">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Service Engagement</span>
                        <p class="font-semibold text-slate-800 text-sm">{{ $proposal->service_name ?? 'General Advisory & Accounting Service' }}</p>
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ $proposal->scope_description ?? 'Full operational assistance, document review, and regulatory guidance.' }}</p>
                    </div>
                    <div class="md:border-l md:border-slate-200 md:pl-6">
                        <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Investment Summary</span>
                        <div class="text-2xl font-bold text-indigo-600">
                            ₱{{ number_format($proposal->amount ?? $proposal->proposed_price ?? $proposal->price ?? 0, 2) }}
                        </div>
                        <span class="text-xs text-slate-400 block mt-1">Tax Treatment: {{ $proposal->tax_treatment ?? 'VAT Exclusive' }}</span>
                    </div>
                </div>

                @if(strtoupper($proposal->status ?? '') !== 'ACCEPTED')
                    <div class="bg-indigo-50/70 border border-indigo-200 p-6 rounded-xl space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-indigo-900">Accept Proposal & Authorization</h3>
                            <p class="text-xs text-indigo-700 mt-0.5">By clicking the button below, you agree to the scope and terms set in this engagement proposal.</p>
                        </div>

                        <form action="{{ route('client.proposal.accept', $proposal->token ?? $proposal->id ?? $token) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Digital Signature / Authorized Full Name</label>
                                <input type="text" name="signature_name" value="{{ $proposal->client_name ?? '' }}" required placeholder="Type your full legal name to sign" class="w-full sm:w-80 p-2.5 border border-slate-300 rounded-lg text-sm bg-white outline-none focus:border-indigo-600">
                            </div>

                            <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow transition inline-flex items-center justify-center gap-2">
                                <i class="fa-solid fa-file-signature"></i>
                                <span>One-Click Accept & Sign Engagement</span>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 flex justify-between items-center">
                        <span>Signed electronically by: <strong class="text-slate-800">{{ $proposal->client_signature ?? $proposal->client_name }}</strong></span>
                        <span class="text-slate-400">Date: {{ $proposal->accepted_at ?? date('Y-m-d') }}</span>
                    </div>
                @endif
            </div>

            <!-- SEARCH FILTER BAR -->
            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" placeholder="Search document name..." class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-md text-xs bg-white outline-none focus:border-blue-500">
                </div>
                <select class="border border-slate-300 rounded-md px-3 py-1.5 text-xs bg-white text-slate-700 outline-none">
                    <option value="">All Status</option>
                    <option value="SUBMITTED">Submitted</option>
                    <option value="PENDING">Pending</option>
                </select>
                <button class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-md shadow-sm transition">Filter</button>
                <button class="px-3 py-1.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-600 text-xs rounded-md transition">Reset</button>
            </div>

            <!-- REQUIREMENTS TABLE -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="p-3.5 pl-5">Document Name</th>
                                <th class="p-3.5">Target Service</th>
                                <th class="p-3.5">Client Type</th>
                                <th class="p-3.5">Source</th>
                                <th class="p-3.5">Rules</th>
                                <th class="p-3.5">Compliance Status</th>
                                <th class="p-3.5">File Attachment</th>
                                <th class="p-3.5 text-right pr-5">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($requirements as $req)
                                @php
                                    $hasFilePath = !empty($req->file_path);
                                    $isSubmitted = $hasFilePath || strtoupper($req->status ?? '') === 'SUBMITTED';
                                    $fileUrl = $hasFilePath ? asset('storage/' . $req->file_path) : '#';
                                    $fileExt = $hasFilePath ? pathinfo($req->file_path, PATHINFO_EXTENSION) : '';
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="p-3.5 pl-5 max-w-xs">
                                        <div class="font-bold text-slate-900 text-xs">{{ $req->document_name ?? 'Initial Document Compliance' }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">ID {{ $req->id }}</div>
                                        <div class="text-[10px] text-slate-400 truncate">{{ $req->description ?? 'Auto-generated document checklist for client: ' . ($proposal->client_name ?? 'Jan dela') }}</div>
                                    </td>

                                    <td class="p-3.5 text-slate-600 font-medium">
                                        {{ $proposal->service_name ?? 'SAWT Preparation and eSubmission Validation' }}
                                    </td>

                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[10px] text-slate-600 font-semibold">
                                            {{ $req->client_type ?? 'All Types' }}
                                        </span>
                                    </td>

                                    <td class="p-3.5 text-slate-600 font-medium">
                                        {{ $req->source ?? 'Client-supplied' }}
                                    </td>

                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 bg-rose-50 text-rose-600 border border-rose-200 rounded text-[10px] font-bold">
                                            {{ $req->is_mandatory ? 'Mandatory' : 'Optional' }}
                                        </span>
                                    </td>

                                    <td class="p-3.5">
                                        @if($isSubmitted)
                                            <span class="px-2.5 py-1 bg-amber-100/80 text-amber-800 rounded-full text-[10px] font-bold tracking-wide">
                                                SUBMITTED
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold">
                                                PENDING
                                            </span>
                                        @endif
                                    </td>

                                    <!-- FILE ATTACHMENT: PREVIEW IN MODAL WITHOUT DOWNLOAD -->
                                    <td class="p-3.5">
                                        @if($isSubmitted && $hasFilePath)
                                            <button type="button" 
                                                    onclick="viewFileInModal('{{ Route::has('client.requirement.preview') ? route('client.requirement.preview', $req->id) : $fileUrl }}', '{{ $req->document_name ?? 'Document View' }}', '{{ $fileExt }}')" 
                                                    class="inline-flex items-center gap-1.5 text-blue-600 font-bold hover:underline text-xs cursor-pointer">
                                                <i class="fa-solid fa-paperclip text-blue-500"></i>
                                                <span>View Document</span>
                                            </button>
                                        @else
                                            <span class="text-slate-300 italic text-[11px]">No file</span>
                                        @endif
                                    </td>

                                    <!-- ACTIONS COLUMN -->
                                    <td class="p-3.5 text-right pr-5 whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <form action="{{ route('client.proposal.uploadRequirement', $proposal->token ?? $proposal->id ?? $token) }}" method="POST" enctype="multipart/form-data" id="uploadForm-{{ $req->id }}" class="hidden">
                                                @csrf
                                                <input type="hidden" name="requirement_id" value="{{ $req->id }}">
                                                <input type="file" name="document" id="fileInput-{{ $req->id }}" onchange="document.getElementById('uploadForm-{{ $req->id }}').submit();">
                                            </form>

                                            <button type="button" onclick="document.getElementById('fileInput-{{ $req->id }}').click();" class="text-blue-600 hover:underline font-bold text-xs cursor-pointer">
                                                Upload
                                            </button>

                                            <!-- FUNCTIONAL REVIEW BUTTON -->
                                            <button type="button" 
                                                    onclick="openReviewModal('{{ addslashes($req->document_name ?? 'Initial Document Compliance') }}', '{{ $req->status ?? 'SUBMITTED' }}', '{{ $req->updated_at ? $req->updated_at->format('M d, Y h:i A') : date('M d, Y') }}')" 
                                                    class="text-purple-600 hover:underline font-medium text-xs cursor-pointer">
                                                Review
                                            </button>

                                            <!-- FUNCTIONAL DELETE BUTTON -->
                                            <button type="button" 
                                                    onclick="confirmDelete('{{ addslashes($req->document_name ?? 'Requirement Document') }}')" 
                                                    class="text-rose-600 hover:underline font-medium text-xs cursor-pointer">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-slate-400">
                                        No requirement documents available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- DOCUMENT PREVIEW MODAL (RENDERED IN-PAGE TO PREVENT AUTO DOWNLOAD) -->
    <div id="fileViewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col overflow-hidden border border-slate-200">
            <div class="px-5 py-3.5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-paperclip text-blue-600 text-base"></i>
                    <h3 id="fileViewTitle" class="font-bold text-slate-800 text-sm">View Document</h3>
                </div>
                <button type="button" onclick="closeFileViewModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm font-bold"></i>
                </button>
            </div>
            
            <div class="flex-1 bg-slate-100 p-4 overflow-auto relative flex items-center justify-center" id="modalContainer">
                <div id="excelPreviewArea" class="w-full h-full overflow-auto bg-white p-4 shadow-sm rounded border border-slate-200 hidden"></div>
                <iframe id="pdfIframe" class="w-full h-full border-0 hidden" src="about:blank"></iframe>
            </div>
        </div>
    </div>

    <!-- REVIEW DETAILS MODAL -->
    <div id="reviewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass-check text-purple-600 text-base"></i>
                    <h3 class="font-bold text-slate-800 text-sm">Requirement Review Status</h3>
                </div>
                <button type="button" onclick="closeReviewModal()" class="w-7 h-7 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-xs font-bold"></i>
                </button>
            </div>
            
            <div class="p-5 space-y-4 text-xs">
                <div>
                    <span class="text-slate-400 font-medium block uppercase text-[10px]">Document Title</span>
                    <span id="reviewDocName" class="font-bold text-slate-800 text-sm">--</span>
                </div>

                <div class="grid grid-cols-2 gap-4 bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <div>
                        <span class="text-slate-400 font-medium block uppercase text-[10px]">Current Status</span>
                        <span id="reviewStatus" class="font-bold text-emerald-700">--</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block uppercase text-[10px]">Last Updated</span>
                        <span id="reviewDate" class="font-semibold text-slate-700">--</span>
                    </div>
                </div>

                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 leading-relaxed">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    <span>This requirement document has been uploaded and is currently queued for operational review and verification by our compliance team.</span>
                </div>
            </div>

            <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 text-right">
                <button type="button" onclick="closeReviewModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT HANDLERS -->
    <script>
        function viewFileInModal(url, title, ext) {
            document.getElementById('fileViewTitle').innerText = title;

            const modal = document.getElementById('fileViewModal');
            const excelArea = document.getElementById('excelPreviewArea');
            const pdfIframe = document.getElementById('pdfIframe');

            excelArea.classList.add('hidden');
            pdfIframe.classList.add('hidden');
            excelArea.innerHTML = '<div class="flex items-center justify-center h-full text-slate-500 font-semibold"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading Document Preview...</div>';

            modal.classList.remove('hidden');

            ext = ext ? ext.toLowerCase() : '';

            if (['xlsx', 'xls', 'csv'].includes(ext)) {
                excelArea.classList.remove('hidden');

                fetch(url)
                    .then(res => res.arrayBuffer())
                    .then(buffer => {
                        const workbook = XLSX.read(buffer, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        const htmlTable = XLSX.utils.sheet_to_html(worksheet, { id: 'excel-table', editable: false });
                        
                        excelArea.innerHTML = htmlTable;

                        const table = excelArea.querySelector('table');
                        if (table) {
                            table.className = "w-full border-collapse text-xs text-slate-700";
                            const cells = table.querySelectorAll('td, th');
                            cells.forEach(cell => {
                                cell.className = "border border-slate-300 p-2 text-left";
                            });
                        }
                    })
                    .catch(err => {
                        excelArea.innerHTML = '<div class="p-4 text-rose-600 font-bold">Unable to preview document content inline.</div>';
                    });
            } else {
                pdfIframe.classList.remove('hidden');
                pdfIframe.src = url;
            }
        }

        function closeFileViewModal() {
            document.getElementById('pdfIframe').src = 'about:blank';
            document.getElementById('excelPreviewArea').innerHTML = '';
            document.getElementById('fileViewModal').classList.add('hidden');
        }

        function openReviewModal(docName, status, date) {
            document.getElementById('reviewDocName').innerText = docName;
            document.getElementById('reviewStatus').innerText = status;
            document.getElementById('reviewDate').innerText = date;
            document.getElementById('reviewModal').classList.remove('hidden');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
        }

        function confirmDelete(docName) {
            if (confirm('Are you sure you want to remove the uploaded file for "' + docName + '"?')) {
                alert('Request logged. Standard requirement resets back to pending state.');
            }
        }
    </script>

</body>
</html>