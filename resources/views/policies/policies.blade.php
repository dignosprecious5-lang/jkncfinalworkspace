@extends('layouts.app')
@section('title', 'Policy')

@section('content')
<div id="policy-page" class="w-full h-full bg-[#f8f8f8]" x-data="{
    showSlideOver: false,
    previewPolicy: '',
    previewCode: '',
    previewVersion: '1.0',
    previewDate: '',
    previewPrepared: '{{ Auth::user()->name }}',
    previewReviewed: '',
    previewApproved: '',
    previewReviewCycle: '',
    previewClassification: 'Internal Use Only',
    previewBody: '<p style=&quot;color:#9ca3af;&quot;>Define the policy scope and rules here...</p>'
}">

    @if(session('success'))
        <div class="px-6 pt-4">
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="px-6 pt-4">
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="w-full px-6 py-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-lg font-bold text-gray-800 tracking-tight">Policies</h1>
                <p class="text-xs text-gray-500">Create and manage corporate governance documents</p>
            </div>

            <button
                type="button"
                @click="showSlideOver = true; $nextTick(() => { initQuill(); syncPreview(); });"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition shadow-sm"
            >
                <i class="fas fa-plus text-xs"></i>
                <span>Add Policy</span>
            </button>
        </div>

        <form method="GET" action="{{ route('policies.index') }}" class="mb-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col md:flex-row gap-3 md:items-center">
                    <div class="flex-1">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search code, classification, title, review cycle, or policy content..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            class="px-4 py-2.5 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition"
                        >
                            Search
                        </button>

                        <a
                            href="{{ route('policies.index') }}"
                            class="px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="min-w-[1600px] w-full border-collapse text-sm text-gray-700">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[140px]">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[190px]">Code</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[240px]">Policy Title</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[100px]">Version</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[150px]">Effectivity</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[170px]">Prepared by</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[170px]">Reviewed by</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[170px]">Approved by</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[130px]">Review Cycle</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[160px]">Classification</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-100 w-[130px]">Attachment</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600 w-[120px]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse(($policies ?? []) as $policy)
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-4 py-3 border-r border-gray-100">{{ $policy->workflow_status ?? $policy->status ?? 'Draft' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100 font-mono text-xs">{{ $policy->code ?? '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100 font-medium">{{ $policy->policy ?? '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100">{{ $policy->version ?? '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100">{{ $policy->effectivity_date ? \Carbon\Carbon::parse($policy->effectivity_date)->format('M d, Y') : '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100">{{ $policy->prepared_by ?? '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100">{{ $policy->reviewed_by ?? '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100">{{ $policy->approved_by ?? '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100">{{ $policy->review_cycle ?? '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100">{{ $policy->classification ?? '-' }}</td>
                                <td class="px-4 py-3 border-r border-gray-100">
                                    @if(!empty($policy->attachment))
                                        <a href="{{ asset('storage/' . $policy->attachment) }}" target="_blank" class="text-blue-600 hover:underline text-xs">
                                            View File
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('policies.show', $policy->id) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition" title="View Policy">
                                            <i class="far fa-eye"></i>
                                        </a>

                                        <a href="{{ route('policies.edit', $policy->id) }}" class="p-1.5 text-gray-500 hover:bg-gray-100 rounded transition" title="Edit Policy">
                                            <i class="far fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                                    No policies found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(method_exists($policies, 'links'))
            <div class="mt-4">
                {{ $policies->links() }}
            </div>
        @endif
    </div>

    <div x-show="showSlideOver" x-cloak class="fixed inset-0 z-[60] overflow-hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showSlideOver = false"></div>

        <div class="absolute inset-0 flex">
            <div
                x-show="showSlideOver"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="w-[70%] h-full bg-[#f5f6f8] overflow-y-auto p-6 border-r border-gray-200"
            >
                <div class="max-w-[850px] mx-auto mb-4 flex justify-end sticky top-0 z-10">
                    <a
                        id="download-policy-pdf"
                        href="{{ route('policies.preview') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 shadow transition"
                    >
                        <i class="fas fa-file-pdf"></i>
                        Download PDF
                    </a>
                </div>

                <div class="max-w-[850px] mx-auto">
                    <div id="policy-preview-sheet" class="policy-preview bg-white border border-gray-300 shadow min-h-[1100px] px-[72px] py-[72px] overflow-hidden">

                        <div class="policy-memo-header">
                            <div class="policy-memo-logo">
                                <img src="{{ asset('images/jk-logo.png') }}" alt="John Kelly & Company Logo">
                            </div>

                            <div class="policy-memo-names">
                                <p>
                                    Atty. Jose B. Ogang, CPA, MMPSM · Jose Tamayo Rio,<br>
                                    MM-BM, CPA · Lyndon Earl P. Rio, RN, CB · John Kelly Abalde,<br>
                                    CLSSBB, CPM
                                </p>
                            </div>
                        </div>

                        <div class="policy-memo-title">
                            <h2 x-text="previewPolicy || 'POLICY TITLE'"></h2>
                        </div>

                        <div class="policy-memo-meta">
                            <p><strong>Policy Title:</strong> <span x-text="previewPolicy || '______________________________'"></span></p>
                            <p><strong>Code:</strong> <span x-text="previewCode || 'AUTO-GENERATED'"></span></p>
                            <p><strong>Version:</strong> <span x-text="previewVersion || '1.0'"></span></p>
                            <p><strong>Effectivity Date:</strong> <span x-text="previewDate || '______________________________'"></span></p>
                            <p><strong>Prepared by:</strong> <span x-text="previewPrepared || '______________________________'"></span></p>
                            <p><strong>Reviewed by:</strong> <span x-text="previewReviewed || '______________________________'"></span></p>
                            <p><strong>Approved by:</strong> <span x-text="previewApproved || '______________________________'"></span></p>
                            <p><strong>Review Cycle:</strong> <span x-text="previewReviewCycle || '______________________________'"></span></p>
                            <p><strong>Classification:</strong> <span x-text="previewClassification || 'Internal Use Only'"></span></p>
                        </div>

                        <div class="policy-memo-divider"></div>

                        <div class="text-[15px] leading-8 text-gray-900 min-h-[420px] max-w-full overflow-hidden">
                            <div
                                class="policy-preview-body prose prose-sm max-w-none w-full overflow-x-auto break-words [overflow-wrap:anywhere] [&_p]:my-4 [&_p]:leading-8 [&_ul]:my-4 [&_ol]:my-4"
                                x-html="previewBody"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="showSlideOver"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="w-[30%] h-full bg-white shadow-2xl flex flex-col"
            >
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">New Policy Details</h2>
                    <button @click="showSlideOver = false" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <form id="policyFormSubmit" method="POST" action="{{ route('policies.store') }}" enctype="multipart/form-data" class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    @csrf

                    <div>
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Policy Title</label>
                        <input
                            type="text"
                            name="policy"
                            x-model="previewPolicy"
                            placeholder="e.g. Policy Development, Drafting, and Document Control Policy"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                            oninput="syncPreview()"
                        >
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Code</label>
                        <input
                            type="text"
                            name="code"
                            x-model="previewCode"
                            placeholder="e.g. JKNC-POL-POLDDEV-2025-001"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                            oninput="syncPreview()"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Version</label>
                            <input
                                type="text"
                                name="version"
                                x-model="previewVersion"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                                oninput="syncPreview()"
                            >
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Effectivity Date</label>
                            <input
                                type="date"
                                name="effectivity_date"
                                x-model="previewDate"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                                oninput="syncPreview()"
                            >
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <div>
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Prepared By</label>
                            <input
                                type="text"
                                name="prepared_by"
                                x-model="previewPrepared"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50"
                            >
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Reviewed By</label>
                            <input
                                type="text"
                                name="reviewed_by"
                                x-model="previewReviewed"
                                placeholder="e.g. Policy Development Committee (PDC)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                                oninput="syncPreview()"
                            >
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Approved By</label>
                            <input
                                type="text"
                                name="approved_by"
                                x-model="previewApproved"
                                placeholder="e.g. Board of Directors"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                                oninput="syncPreview()"
                            >
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Review Cycle</label>
                            <input
                                type="text"
                                name="review_cycle"
                                x-model="previewReviewCycle"
                                placeholder="e.g. Annual"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                                oninput="syncPreview()"
                            >
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Classification</label>
                            <select
                                name="classification"
                                x-model="previewClassification"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                                onchange="syncPreview()"
                            >
                                <option value="Confidential">Confidential</option>
                                <option value="Internal Use Only">Internal Use Only</option>
                                <option value="Internal Use">Internal Use</option>
                                <option value="Public">Public</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Policy Body</label>

                        <div class="rounded-xl border border-gray-300 bg-[#fafafa] overflow-hidden shadow-sm">
                            <div class="word-ribbon border-b border-gray-200 bg-white px-3 py-2">
                                <div class="text-[11px] font-medium text-gray-500">Document Editor</div>
                                <div class="mt-1 text-[11px] text-gray-400">
                                    Tip: click the table icon to insert a table. For table actions, click inside the table and use the table menu.
                                </div>
                            </div>

                            <div id="policy-editor"></div>
                        </div>

                        <input type="hidden" name="description" id="description-input">
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Attachment</label>
                        <input
                            type="file"
                            name="attachment"
                            accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                        >
                    </div>

                    <div class="pt-6 border-t border-gray-200 flex items-center gap-3 bg-white sticky bottom-0">
                        <button type="button" @click="showSlideOver = false" class="flex-1 border border-gray-300 text-gray-700 rounded-xl py-2.5 text-sm font-semibold hover:bg-gray-50 transition">
                            Cancel
                        </button>

                        <button type="submit" class="flex-1 bg-blue-600 text-white rounded-xl py-2.5 text-sm font-bold hover:bg-blue-700 shadow-md transition">
                            Save Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.css" rel="stylesheet">

<style>
    [x-cloak] {
        display: none !important;
    }

    .policy-preview {
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        font-family: "Times New Roman", Georgia, serif;
    }

    .policy-memo-header {
        display: flex;
        align-items: flex-start;
        gap: 34px;
        margin-bottom: 34px;
    }

    .policy-memo-logo {
        flex: 0 0 auto;
        padding-top: 4px;
    }

    .policy-memo-logo img {
        height: 76px;
        width: auto;
        object-fit: contain;
    }

    .policy-memo-names {
        flex: 1 1 auto;
        padding-top: 8px;
    }

    .policy-memo-names p {
        font-size: 12px;
        line-height: 1.35;
        color: #111827;
        font-family: "Times New Roman", Georgia, serif;
        margin: 0;
    }

    .policy-memo-title {
        text-align: center;
        margin-bottom: 30px;
    }

    .policy-memo-title h2 {
        font-size: 24px;
        font-weight: 700;
        letter-spacing: 0.12em;
        color: #4b5563;
        font-family: "Times New Roman", Georgia, serif;
        text-transform: uppercase;
        margin: 0;
    }

    .policy-memo-meta {
        font-size: 14px;
        line-height: 1.45;
        color: #111827;
        font-family: "Times New Roman", Georgia, serif;
        margin-bottom: 10px;
    }

    .policy-memo-meta p {
        margin: 2px 0;
    }

    .policy-memo-meta strong {
        font-weight: 700;
    }

    .policy-memo-divider {
        border-bottom: 1px solid #6b7280;
        margin: 12px 0 26px 0;
    }

    .word-ribbon {
        background: linear-gradient(to bottom, #ffffff, #f8fafc);
    }

    #policy-editor .ql-toolbar.ql-snow {
        border: 0 !important;
        border-bottom: 1px solid #e5e7eb !important;
        background: #fff;
        padding: 10px 12px;
    }

    #policy-editor .ql-container.ql-snow {
        border: 0 !important;
        min-height: 340px;
        background: #fff;
    }

    #policy-editor .ql-editor {
        min-height: 340px;
        padding: 28px 26px;
        font-size: 15px;
        line-height: 1.85;
        color: #111827;
        font-family: "Calibri", "Arial", sans-serif;
    }

    .policy-preview-body table,
    .ql-editor table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin: 12px 0 !important;
    }

    .policy-preview-body th,
    .policy-preview-body td,
    .ql-editor th,
    .ql-editor td {
        min-width: 0 !important;
        border: 1px solid #94a3b8 !important;
        padding: 10px 12px !important;
        vertical-align: top !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
        white-space: normal !important;
    }

    .policy-preview-body th,
    .ql-editor th {
        background: #f8fafc !important;
        font-weight: 600 !important;
    }

    .policy-preview-body p,
    .policy-preview-body li,
    .policy-preview-body span,
    .policy-preview-body div,
    .ql-editor p,
    .ql-editor li,
    .ql-editor span,
    .ql-editor div {
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .policy-preview-body ul,
    .policy-preview-body ol {
        padding-left: 1.5rem;
    }

    .qlbt-operation-menu,
    .ql-table-better-menu,
    .quill-table-better-wrapper {
        z-index: 9999 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.js"></script>

<script>
    let policyQuill = null;

    function initQuill() {
        if (policyQuill) return;

        Quill.register({
            'modules/table-better': QuillTableBetter
        }, true);

        const rootEl = document.getElementById('policy-page');
        const alpineData = rootEl ? Alpine.$data(rootEl) : null;
        const hiddenInput = document.getElementById('description-input');
        const defaultHtml = '<p style="color:#9ca3af;">Define the policy scope and rules here...</p>';

        policyQuill = new Quill('#policy-editor', {
            theme: 'snow',
            placeholder: 'Define policy...',
            modules: {
                toolbar: [
                    [{ font: [] }, { size: ['small', false, 'large', 'huge'] }],
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ script: 'sub' }, { script: 'super' }],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ indent: '-1' }, { indent: '+1' }],
                    [{ align: [] }],
                    ['blockquote', 'link'],
                    ['table-better'],
                    ['clean']
                ],
                table: false,
                'table-better': {
                    language: 'en_US',
                    menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                    toolbarTable: true
                },
                keyboard: {
                    bindings: QuillTableBetter.keyboardBindings
                }
            }
        });

        function updatePolicyPreviewBody() {
            const html = policyQuill.root.innerHTML;
            const hasText = policyQuill.getText().trim().length > 0;
            const hasTable = !!policyQuill.root.querySelector('table');

            hiddenInput.value = html;

            if (alpineData) {
                alpineData.previewBody = (hasText || hasTable) ? html : defaultHtml;
            }

            syncPreviewLink();
        }

        policyQuill.on('text-change', function () {
            updatePolicyPreviewBody();
        });

        hiddenInput.value = '';

        if (alpineData) {
            alpineData.previewBody = defaultHtml;
        }

        syncPreviewLink();
    }

    function syncPreview() {
        syncPreviewLink();
    }

    function syncPreviewLink() {
        const form = document.getElementById('policyFormSubmit');
        const downloadBtn = document.getElementById('download-policy-pdf');

        if (!form || !downloadBtn) return;

        const formData = new FormData(form);

        if (policyQuill) {
            formData.set('description', policyQuill.root.innerHTML);
        }

        const params = new URLSearchParams(formData);
        downloadBtn.href = `{{ route('policies.preview') }}?${params.toString()}`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('policyFormSubmit');

        if (form) {
            form.addEventListener('input', function () {
                syncPreviewLink();
            });

            form.addEventListener('change', function () {
                syncPreviewLink();
            });

            form.addEventListener('submit', function () {
                const hiddenInput = document.getElementById('description-input');

                if (hiddenInput && policyQuill) {
                    hiddenInput.value = policyQuill.root.innerHTML;
                }
            });
        }
    });
</script>
@endpush
