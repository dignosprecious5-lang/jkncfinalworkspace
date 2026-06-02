@extends('layouts.app')
@section('title', 'Edit Policy')

@section('content')
<div id="policy-edit-page" class="w-full min-h-screen bg-slate-50" x-data="{
    previewPolicy: @js(old('policy', $policy->policy ?? '')),
    previewPolicySubtitle: @js(old('policy_subtitle', $policy->policy_subtitle ?? 'Policy Document')),
    previewCode: @js(old('code', $policy->code ?? '')),
    previewVersion: @js(old('version', $policy->version ?? '1.0')),
    previewDate: @js(old('effectivity_date', optional($policy->effectivity_date)->format('Y-m-d'))),
    previewPrepared: @js(old('prepared_by', $policy->prepared_by ?? Auth::user()->name)),
    previewReviewed: @js(old('reviewed_by', $policy->reviewed_by ?? '')),
    previewApproved: @js(old('approved_by', $policy->approved_by ?? '')),
    previewReviewCycle: @js(old('review_cycle', $policy->review_cycle ?? '')),
    previewClassification: @js(old('classification', $policy->classification ?? 'Internal Use Only')),
    previewBody: @js(old('description', $policy->description ?? '<p style=&quot;color:#9ca3af;&quot;>Define the policy scope and rules here...</p>'))
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

    <div class="fixed inset-0 z-[60] overflow-hidden bg-[#f5f6f8]">
        <div class="absolute inset-0 flex">
            {{-- LEFT PREVIEW --}}
            <div class="w-[70%] h-full bg-[#f5f6f8] border-r border-gray-200 flex flex-col">
                <div class="shrink-0 z-[80] border-b border-gray-200 bg-[#f5f6f8] px-6 py-3">
                    <div class="max-w-[850px] mx-auto flex items-center justify-between">
                        <a
                            href="{{ url()->previous() }}"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            ← Back
                        </a>

                        <a
                            id="download-policy-pdf"
                            href="{{ route('policies.preview') }}"
                            target="_blank"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 shadow transition"
                        >
                            <i class="fas fa-file-pdf"></i>
                            Download PDF
                        </a>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <div class="max-w-[850px] mx-auto">
                        <div id="policy-preview-sheet" class="policy-preview bg-white border border-gray-300 shadow overflow-hidden">
                            {{-- COVER PAGE --}}
                            <section class="policy-cover-page">
                                <div class="cover-logo" style="width:100%; display:flex; justify-content:center; align-items:center; text-align:center;">
                                    <img src="{{ $policyLogoUrl ?? asset('images/jk-logo.png') }}" alt="John Kelly & Company Logo" style="display:block; margin-left:auto; margin-right:auto; width:260px; max-height:120px; object-fit:contain;">
                                </div>

                                <div class="cover-company">
                                    <p class="cover-company-name">JOHN KELLY &amp; COMPANY (JK&amp;C INC)</p>
                                    <p>3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000</p>
                                </div>

                                <div class="cover-title">
                                    <h1 x-text="previewPolicy || 'POLICY TITLE'"></h1>
                                    <p x-text="previewPolicySubtitle || 'Policy Document'"></p>
                                </div>

                                <div class="cover-details">
                                    <table>
                                        <tr>
                                            <td>Code</td>
                                            <td x-text="previewCode || 'AUTO-GENERATED'"></td>
                                        </tr>
                                        <tr>
                                            <td>Version</td>
                                            <td x-text="previewVersion || '1.0'"></td>
                                        </tr>
                                        <tr>
                                            <td>Effectivity Date</td>
                                            <td x-text="previewDate || '______________________________'"></td>
                                        </tr>
                                        <tr>
                                            <td>Prepared by</td>
                                            <td x-text="previewPrepared || '______________________________'"></td>
                                        </tr>
                                        <tr>
                                            <td>Reviewed by</td>
                                            <td x-text="previewReviewed || '______________________________'"></td>
                                        </tr>
                                        <tr>
                                            <td>Approved by</td>
                                            <td x-text="previewApproved || '______________________________'"></td>
                                        </tr>
                                        <tr>
                                            <td>Review Cycle</td>
                                            <td x-text="previewReviewCycle || '______________________________'"></td>
                                        </tr>
                                        <tr>
                                            <td>Classification</td>
                                            <td x-text="previewClassification || 'Internal Use Only'"></td>
                                        </tr>
                                    </table>
                                </div>
                            </section>

                            {{-- BODY PAGE --}}
                            <section class="policy-body-page">
                                <h2 x-text="previewPolicy || 'POLICY TITLE'"></h2>

                                <div
                                    class="policy-preview-body description-content"
                                    x-html="previewBody"
                                ></div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT FORM --}}
            <div class="w-[30%] h-full bg-white shadow-2xl flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">Edit Policy Details</h2>
                    <a href="{{ url()->previous() }}" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-lg"></i>
                    </a>
                </div>

                <form id="policyFormSubmit" method="POST" action="{{ route('policies.update', $policy->id) }}" enctype="multipart/form-data" class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    @csrf
                    @method('PUT')

                    @if(str_contains(url()->previous(), '/admin/'))
                        <input type="hidden" name="redirect_to" value="admin">
                    @endif

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
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Policy Subtitle</label>
                        <input
                            type="text"
                            name="policy_subtitle"
                            x-model="previewPolicySubtitle"
                            placeholder="e.g. Policy Document"
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

                        @php
                            $policyAttachments = $policy->attachments ?? collect();
                            if ($policyAttachments->isEmpty() && $policy->attachment) {
                                $policyAttachments = collect([(object) [
                                    'id' => null,
                                    'file_path' => $policy->attachment,
                                    'original_name' => basename($policy->attachment),
                                ]]);
                            }
                        @endphp

                        @if($policyAttachments->isNotEmpty())
                            <div class="mb-3 space-y-2">
                                @foreach($policyAttachments as $attachmentItem)
                                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                                        <div class="flex items-center justify-between gap-2">
                                            <a href="{{ asset('storage/' . $attachmentItem->file_path) }}" target="_blank" class="font-semibold hover:underline truncate">
                                                {{ $attachmentItem->original_name ?? basename($attachmentItem->file_path) }}
                                            </a>

                                            @if(!empty($attachmentItem->id))
                                                <label class="inline-flex items-center gap-2 text-xs text-red-600 shrink-0">
                                                    <input type="checkbox" name="remove_attachment_ids[]" value="{{ $attachmentItem->id }}" class="rounded border-gray-300">
                                                    Remove
                                                </label>
                                            @else
                                                <label class="inline-flex items-center gap-2 text-xs text-red-600 shrink-0">
                                                    <input type="checkbox" name="remove_attachment" value="1" class="rounded border-gray-300">
                                                    Remove
                                                </label>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <input
                            type="file"
                            name="attachments[]" multiple
                            accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                        >
                        <p class="mt-1 text-[11px] text-gray-400">Upload one or more new files. Existing attachments will remain unless you check Remove.</p>
                    </div>

                    <div class="pt-6 border-t border-gray-200 flex items-center gap-3 bg-white sticky bottom-0">
                        <a href="{{ url()->previous() }}" class="flex-1 border border-gray-300 text-gray-700 rounded-xl py-2.5 text-sm font-semibold hover:bg-gray-50 transition text-center">
                            Cancel
                        </a>

                        <button type="submit" class="flex-1 bg-blue-600 text-white rounded-xl py-2.5 text-sm font-bold hover:bg-blue-700 shadow-md transition">
                            Save Changes
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

    #policy-preview-sheet.policy-preview {
        width: 210mm !important;
        min-height: 297mm !important;
        padding: 0 !important;
        overflow: visible !important;
        font-family: Georgia, "Times New Roman", serif !important;
        color: #000 !important;
        font-size: 12pt !important;
        line-height: 1.45 !important;
        background: #fff !important;
    }

    #policy-preview-sheet .policy-cover-page {
        min-height: 297mm;
        padding: 72px;
        position: relative;
        text-align: center;
        border-bottom: 1px solid #e5e7eb;
    }

    #policy-preview-sheet .cover-logo {
        margin-top: 16px;
        width: 100% !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        text-align: center !important;
    }

    #policy-preview-sheet .cover-logo img {
        display: block !important;
        margin-left: auto !important;
        margin-right: auto !important;
        float: none !important;
        width: 260px !important;
        max-height: 120px !important;
        object-fit: contain !important;
    }

    #policy-preview-sheet .cover-company {
        margin-top: 10px;
        font-size: 11pt;
        line-height: 1.2;
        text-align: center;
    }

    #policy-preview-sheet .cover-company-name {
        font-weight: bold;
        margin: 0;
    }

    #policy-preview-sheet .cover-title {
        margin-top: 145px;
        line-height: 1.25;
        text-align: center;
    }

    #policy-preview-sheet .cover-title h1 {
        margin: 0;
        font-size: 12pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    #policy-preview-sheet .cover-title p {
        margin: 2px 0 0;
        font-size: 10pt;
    }

    #policy-preview-sheet .cover-details {
        width: 485px;
        margin: 150px auto 0;
        text-align: left;
        font-size: 11pt;
    }

    #policy-preview-sheet .cover-details table {
        width: 100%;
        border-collapse: collapse;
    }

    #policy-preview-sheet .cover-details td {
        border: none !important;
        padding: 5px 8px !important;
        vertical-align: top;
    }

    #policy-preview-sheet .cover-details td:first-child {
        width: 150px;
        font-weight: normal;
    }

    #policy-preview-sheet .cover-details td:last-child {
        font-weight: bold;
    }

    #policy-preview-sheet .policy-body-page {
        min-height: 297mm;
        padding: 72px;
        page-break-before: always;
    }

    #policy-preview-sheet .policy-body-page h2 {
        margin: 0 0 24px 0;
        text-align: center;
        font-size: 14pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    #policy-preview-sheet .description-content {
        width: 100%;
        font-size: 12pt;
        line-height: 1.45;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    #policy-preview-sheet .description-content p,
    #policy-preview-sheet .description-content li,
    #policy-preview-sheet .description-content span,
    #policy-preview-sheet .description-content div {
        overflow-wrap: anywhere;
        word-break: break-word;
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

    /* FINAL HARD FIX: keep long policy text inside cards, tables, side panels, and A4 preview */
    .policy-paper,
    .policy-paper *,
    #policy-preview-sheet,
    #policy-preview-sheet *,
    .policy-library,
    .policy-library *,
    .policy-dashboard-table,
    .policy-dashboard-table *,
    .policy-details-panel,
    .policy-details-panel *,
    table,
    table *,
    td,
    th {
        min-width: 0 !important;
        max-width: 100% !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    .policy-paper,
    #policy-preview-sheet {
        overflow-x: hidden !important;
    }

    .cover-title,
    .cover-title h1,
    .cover-title p {
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
        text-align: center !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    .cover-details {
        max-width: 485px !important;
        overflow: hidden !important;
    }

    .cover-details table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
    }

    .cover-details td:first-child {
        width: 150px !important;
        min-width: 150px !important;
        max-width: 150px !important;
    }

    .cover-details td:last-child {
        width: auto !important;
        min-width: 0 !important;
        max-width: 335px !important;
        overflow: hidden !important;
    }

    .description-content,
    .description-content * {
        max-width: 100% !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    .description-content {
        overflow-x: hidden !important;
    }

    /* Keep long text in the list/dashboard rows from destroying table layout */
    .policy-table-fixed {
        table-layout: fixed !important;
        width: 100% !important;
    }

    .policy-table-fixed td,
    .policy-table-fixed th {
        overflow: hidden !important;
        text-overflow: ellipsis;
        vertical-align: top !important;
    }

    .policy-break {
        display: block !important;
        max-width: 100% !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    .policy-clamp-2 {
        display: -webkit-box !important;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
    }

    .policy-clamp-1 {
        display: block !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        max-width: 100% !important;
    }

</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.js"></script>

<script>
    let policyEditQuill = null;

    function initEditQuill() {
        if (policyEditQuill) return;

        Quill.register({
            'modules/table-better': QuillTableBetter
        }, true);

        const rootEl = document.getElementById('policy-edit-page');
        const alpineData = rootEl ? Alpine.$data(rootEl) : null;
        const hiddenInput = document.getElementById('description-input');
        const initialHtml = alpineData && alpineData.previewBody
            ? alpineData.previewBody
            : '<p style="color:#9ca3af;">Define the policy scope and rules here...</p>';

        policyEditQuill = new Quill('#policy-editor', {
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

        policyEditQuill.clipboard.dangerouslyPasteHTML(initialHtml);
        hiddenInput.value = initialHtml;

        function updatePolicyPreviewBody() {
            const html = policyEditQuill.root.innerHTML;
            const hasText = policyEditQuill.getText().trim().length > 0;
            const hasTable = !!policyEditQuill.root.querySelector('table');

            hiddenInput.value = html;

            if (alpineData) {
                alpineData.previewBody = (hasText || hasTable)
                    ? html
                    : '<p style="color:#9ca3af;">Define the policy scope and rules here...</p>';
            }

            syncPreviewLink();
        }

        policyEditQuill.on('text-change', function () {
            updatePolicyPreviewBody();
        });

        updatePolicyPreviewBody();
    }

    function syncPreview() {
        syncPreviewLink();
    }

    function syncPreviewLink() {
        const form = document.getElementById('policyFormSubmit');
        const downloadBtn = document.getElementById('download-policy-pdf');

        if (!form || !downloadBtn) return;

        const formData = new FormData(form);

        if (policyEditQuill) {
            formData.set('description', policyEditQuill.root.innerHTML);
        }

        formData.delete('_token');
        formData.delete('_method');
        formData.delete('attachment');
        formData.delete('remove_attachment');
        formData.delete('redirect_to');

        const params = new URLSearchParams(formData);
        downloadBtn.href = `{{ route('policies.preview') }}?${params.toString()}`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        initEditQuill();

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

                if (hiddenInput && policyEditQuill) {
                    hiddenInput.value = policyEditQuill.root.innerHTML;
                }
            });
        }
    });
</script>
@endpush
