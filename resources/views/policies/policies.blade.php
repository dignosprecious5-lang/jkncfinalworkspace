@extends('layouts.app')
@section('title', 'Policy')

@section('content')
<div id="policy-page" class="w-full min-h-screen bg-slate-50" x-data="{
    showSlideOver: false,
    previewPolicy: '',
    previewPolicySubtitle: '',
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

    <div class="w-full px-6 py-5 space-y-5">
        @php
            $policiesCount = method_exists($policies, 'count') ? $policies->count() : 0;
        @endphp

        {{-- PAGE HEADER --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-6">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-sky-50"></div>

                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                            <i class="fas fa-file-signature text-[10px]"></i>
                            Corporate Governance
                        </div>

                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Policies</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Browse official company policies and governance documents.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="showSlideOver = true; $nextTick(() => { initQuill(); syncPreview(); });"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    >
                        <i class="fas fa-plus text-xs"></i>
                        Add Policy
                    </button>
                </div>
            </div>
        </div>

        {{-- SEARCH --}}
        <form method="GET" action="{{ route('policies.index') }}">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search code, classification, title, review cycle, or policy content..."
                            class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            <i class="fas fa-filter text-xs"></i>
                            Search
                        </button>

                        <a
                            href="{{ route('policies.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>

        {{-- POLICY LIST --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Policy Library</h2>
                    <p class="mt-1 text-sm text-slate-500">Browse and manage policy records.</p>
                </div>

                <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                    <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                    Showing {{ method_exists($policies, 'firstItem') ? ($policies->firstItem() ?? 0) : $policiesCount }}
                    to {{ method_exists($policies, 'lastItem') ? ($policies->lastItem() ?? $policiesCount) : $policiesCount }}
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse(($policies ?? []) as $policy)
                    @php
                        $status = $policy->workflow_status ?? $policy->status ?? 'Draft';
                        $statusKey = strtolower((string) $status);

                        $statusClasses = match(true) {
                            in_array($statusKey, ['accepted', 'approved', 'active']) => 'bg-green-50 text-green-700 ring-green-100',
                            str_contains($statusKey, 'reject') => 'bg-red-50 text-red-700 ring-red-100',
                            str_contains($statusKey, 'revision') => 'bg-blue-50 text-blue-700 ring-blue-100',
                            str_contains($statusKey, 'pending') => 'bg-yellow-50 text-yellow-700 ring-yellow-100',
                            default => 'bg-slate-100 text-slate-700 ring-slate-200',
                        };

                        $classification = $policy->classification ?? '-';
                        $classificationClasses = match(strtolower((string) $classification)) {
                            'confidential' => 'bg-red-50 text-red-700 ring-red-100',
                            'public' => 'bg-green-50 text-green-700 ring-green-100',
                            default => 'bg-blue-50 text-blue-700 ring-blue-100',
                        };

                        $policyAttachments = $policy->attachments ?? collect();
                        $hasAttachment = $policyAttachments->isNotEmpty() || !empty($policy->attachment);
                    @endphp

                    <div
                        class="policy-card cursor-pointer px-5 py-5 transition hover:bg-blue-50/30"
                        onclick="window.location='{{ route('policies.show', $policy->id) }}'"
                    >
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">
                                        {{ $status }}
                                    </span>

                                    <span class="inline-flex max-w-full rounded-full bg-slate-100 px-3 py-1 font-mono text-xs font-semibold text-slate-700">
                                        <span class="policy-one-line">{{ $policy->code ?? '-' }}</span>
                                    </span>

                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $classificationClasses }}">
                                        {{ $classification }}
                                    </span>
                                </div>

                                <h3 class="policy-title-text text-base font-semibold text-slate-900">
                                    {{ $policy->policy ?? '-' }}
                                </h3>


                                <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Version</p>
                                        <p class="policy-value mt-1 text-slate-700">{{ $policy->version ?? '-' }}</p>
                                    </div>

                                    <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Effectivity</p>
                                        <p class="policy-value mt-1 text-slate-700">
                                            {{ $policy->effectivity_date ? \Carbon\Carbon::parse($policy->effectivity_date)->format('M d, Y') : '-' }}
                                        </p>
                                    </div>

                                    <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Prepared by</p>
                                        <p class="policy-value mt-1 text-slate-700">{{ $policy->prepared_by ?? '-' }}</p>
                                    </div>

                                    <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Review Cycle</p>
                                        <p class="policy-value mt-1 text-slate-700">{{ $policy->review_cycle ?? '-' }}</p>
                                    </div>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                    <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Reviewed by</p>
                                        <p class="policy-value mt-1 text-slate-700">{{ $policy->reviewed_by ?? '-' }}</p>
                                    </div>

                                    <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Approved by</p>
                                        <p class="policy-value mt-1 text-slate-700">{{ $policy->approved_by ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-col gap-2 xl:w-[150px]">
                                @if($hasAttachment)
                                    <button
                                        type="button"
                                        onclick="event.stopPropagation(); window.location='{{ route('policies.show', $policy->id) }}#attachments'"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100"
                                    >
                                        <i class="fas fa-paperclip text-[10px]"></i>
                                        View Files
                                    </button>
                                @else
                                    <span class="inline-flex items-center justify-center rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-400 ring-1 ring-slate-100">
                                        No file
                                    </span>
                                @endif

                                <a
                                    href="{{ route('policies.show', $policy->id) }}"
                                    onclick="event.stopPropagation()"
                                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                                >
                                    Open
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i class="fas fa-folder-open text-lg"></i>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-800">No policies found</h3>
                        <p class="mt-1 text-sm text-slate-500">Create a policy to start building your policy library.</p>
                    </div>
                @endforelse
            </div>

            @if(method_exists($policies, 'links'))
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $policies->links() }}
                </div>
            @endif
        </div>
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
                class="w-[70%] h-full bg-[#f5f6f8] border-r border-gray-200 flex flex-col"
            >
                {{-- Sticky Download Header --}}
                <div class="shrink-0 z-[80] border-b border-gray-200 bg-[#f5f6f8] px-6 py-3">
                    <div class="max-w-[850px] mx-auto flex justify-end">
                        <a
                            id="download-policy-pdf"
                            href="{{ route('policies.preview') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 shadow transition"
                        >
                            <i class="fas fa-file-pdf"></i>
                            Download PDF
                        </a>
                    </div>
                </div>

                {{-- Scrollable Preview Area --}}
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
                                <p x-text="previewPolicySubtitle"></p>
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
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Policy Subtitle</label>
                        <input
                            type="text"
                            name="policy_subtitle"
                            x-model="previewPolicySubtitle"
                            placeholder="e.g. Corporate Meeting Documentation Policy"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                            oninput="syncPreview()"
                        >
                        <p class="mt-1 text-[11px] text-gray-400">
                            This appears below the policy title on the cover page.
                        </p>
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
                                oninput="syncPreview()"
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
                            name="attachments[]" multiple
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
        justify-content: center;
        align-items: center;
        margin-bottom: 34px;
        text-align: center;
    }

    .policy-memo-logo {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding-top: 4px;
    }

    .policy-memo-logo img {
        height: 90px;
        width: auto;
        object-fit: contain;
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

    /* Add Policy live preview: JK&C approved A4 format */
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
    /* Focused text wrapping for Policy Library cards */
    .policy-card,
    .policy-card * {
        min-width: 0;
    }

    .policy-title-text,
    .policy-value,
    .policy-one-line {
        max-width: 100%;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .policy-title-text {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .policy-one-line {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Focused wrap fix for Add Policy A4 preview only */
    #policy-preview-sheet,
    #policy-preview-sheet * {
        box-sizing: border-box !important;
    }

    #policy-preview-sheet .cover-title,
    #policy-preview-sheet .cover-title h1,
    #policy-preview-sheet .cover-title p,
    #policy-preview-sheet .cover-details,
    #policy-preview-sheet .cover-details table,
    #policy-preview-sheet .cover-details td,
    #policy-preview-sheet .policy-body-page,
    #policy-preview-sheet .description-content,
    #policy-preview-sheet .description-content * {
        max-width: 100% !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    #policy-preview-sheet .cover-title {
        width: 100% !important;
        overflow: hidden !important;
        padding-left: 16px !important;
        padding-right: 16px !important;
    }

    #policy-preview-sheet .cover-details {
        overflow: hidden !important;
    }

    #policy-preview-sheet .cover-details table {
        table-layout: fixed !important;
    }

    #policy-preview-sheet .cover-details td:first-child {
        width: 150px !important;
        min-width: 150px !important;
        max-width: 150px !important;
    }

    #policy-preview-sheet .cover-details td:last-child {
        width: auto !important;
        min-width: 0 !important;
        max-width: 335px !important;
        overflow: hidden !important;
    }


    /* Policy module default font */
    .policy-paper,
    .policy-paper *,
    #policy-preview-sheet,
    #policy-preview-sheet *,
    .description-content,
    .description-content *,
    .policy-preview-body,
    .policy-preview-body * {
        font-family: Georgia, "Times New Roman", serif !important;
    }

    /* Quill editor Georgia font option */
    .ql-font-georgia,
    .ql-font-georgia * {
        font-family: Georgia, "Times New Roman", serif !important;
    }

    .ql-picker.ql-font .ql-picker-label[data-value="georgia"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="georgia"]::before {
        content: "Georgia";
        font-family: Georgia, "Times New Roman", serif;
    }

    .ql-picker.ql-font .ql-picker-label[data-value="serif"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="serif"]::before {
        content: "Serif";
    }

    .ql-picker.ql-font .ql-picker-label[data-value="sans-serif"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="sans-serif"]::before {
        content: "Sans Serif";
    }

    .ql-picker.ql-font .ql-picker-label[data-value="monospace"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="monospace"]::before {
        content: "Monospace";
    }

    #policy-editor .ql-editor {
        font-family: Georgia, "Times New Roman", serif !important;
    }


    /* Quill alignment support for live preview, show pages, and PDF */
    .ql-align-left {
        text-align: left !important;
    }

    .ql-align-center {
        text-align: center !important;
    }

    .ql-align-right {
        text-align: right !important;
    }

    .ql-align-justify {
        text-align: justify !important;
        text-justify: inter-word;
    }

    .description-content .ql-align-left,
    .policy-preview-body .ql-align-left,
    #policy-preview-sheet .ql-align-left {
        text-align: left !important;
    }

    .description-content .ql-align-center,
    .policy-preview-body .ql-align-center,
    #policy-preview-sheet .ql-align-center {
        text-align: center !important;
    }

    .description-content .ql-align-right,
    .policy-preview-body .ql-align-right,
    #policy-preview-sheet .ql-align-right {
        text-align: right !important;
    }

    .description-content .ql-align-justify,
    .policy-preview-body .ql-align-justify,
    #policy-preview-sheet .ql-align-justify {
        text-align: justify !important;
        text-justify: inter-word;
    }


    /* Quill indentation support for live preview, show pages, and PDF */
    .ql-indent-1 { padding-left: 3em !important; }
    .ql-indent-2 { padding-left: 6em !important; }
    .ql-indent-3 { padding-left: 9em !important; }
    .ql-indent-4 { padding-left: 12em !important; }
    .ql-indent-5 { padding-left: 15em !important; }
    .ql-indent-6 { padding-left: 18em !important; }
    .ql-indent-7 { padding-left: 21em !important; }
    .ql-indent-8 { padding-left: 24em !important; }

    .description-content .ql-indent-1,
    .policy-preview-body .ql-indent-1,
    #policy-preview-sheet .ql-indent-1 { padding-left: 3em !important; }

    .description-content .ql-indent-2,
    .policy-preview-body .ql-indent-2,
    #policy-preview-sheet .ql-indent-2 { padding-left: 6em !important; }

    .description-content .ql-indent-3,
    .policy-preview-body .ql-indent-3,
    #policy-preview-sheet .ql-indent-3 { padding-left: 9em !important; }

    .description-content .ql-indent-4,
    .policy-preview-body .ql-indent-4,
    #policy-preview-sheet .ql-indent-4 { padding-left: 12em !important; }

    .description-content .ql-indent-5,
    .policy-preview-body .ql-indent-5,
    #policy-preview-sheet .ql-indent-5 { padding-left: 15em !important; }

    .description-content .ql-indent-6,
    .policy-preview-body .ql-indent-6,
    #policy-preview-sheet .ql-indent-6 { padding-left: 18em !important; }

    .description-content .ql-indent-7,
    .policy-preview-body .ql-indent-7,
    #policy-preview-sheet .ql-indent-7 { padding-left: 21em !important; }

    .description-content .ql-indent-8,
    .policy-preview-body .ql-indent-8,
    #policy-preview-sheet .ql-indent-8 { padding-left: 24em !important; }


    /* Real tab-space support for policy editor and preview */
    #policy-editor .ql-editor,
    .policy-preview-body,
    .description-content {
        tab-size: 4 !important;
    }


    /* Preserve manual spacing/tabs from the document editor */
    .policy-preview-body,
    .description-content {
        white-space: normal !important;
    }

    .policy-preview-body p,
    .policy-preview-body div,
    .description-content p,
    .description-content div {
        white-space: pre-wrap !important;
    }

    .policy-preview-body span,
    .description-content span {
        white-space: pre-wrap !important;
    }


    /* Toolbar indent buttons now insert spaces at cursor, not whole-block indent */
    #policy-editor .ql-editor {
        tab-size: 4 !important;
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

        const Font = Quill.import('formats/font');
        Font.whitelist = ['georgia', 'serif', 'sans-serif', 'monospace'];
        Quill.register(Font, true);

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
                toolbar: {
                    container: [
                        [{ font: ['georgia', 'serif', 'sans-serif', 'monospace'] }, { size: ['small', false, 'large', 'huge'] }],
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
                    handlers: {
                        indent: function(value) {
                            const range = this.quill.getSelection(true);
                            if (!range) return;

                            const spaces = '\u00a0\u00a0\u00a0\u00a0';

                            if (value === '+1') {
                                this.quill.insertText(range.index, spaces, Quill.sources.USER);
                                this.quill.setSelection(range.index + spaces.length, 0, Quill.sources.SILENT);
                                return;
                            }

                            if (value === '-1') {
                                const start = Math.max(0, range.index - 4);
                                const before = this.quill.getText(start, 4);

                                if (before === '\u00a0\u00a0\u00a0\u00a0' || before === '    ') {
                                    this.quill.deleteText(start, 4, Quill.sources.USER);
                                    this.quill.setSelection(start, 0, Quill.sources.SILENT);
                                }
                            }
                        }
                    }
                },
                table: false,
                'table-better': {
                    language: 'en_US',
                    menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                    toolbarTable: true
                },

                keyboard: {
                    bindings: {
                        ...QuillTableBetter.keyboardBindings,

                        policyTabInsertSpaces: {
                            key: 9,
                            handler: function(range, context) {
                                if (!range) return false;

                                const spaces = '\u00a0\u00a0\u00a0\u00a0';

                                this.quill.insertText(range.index, spaces, Quill.sources.USER);
                                this.quill.setSelection(range.index + spaces.length, 0, Quill.sources.SILENT);

                                return false;
                            }
                        },

                        policyShiftTabRemoveSpaces: {
                            key: 9,
                            shiftKey: true,
                            handler: function(range, context) {
                                if (!range || range.index < 1) return false;

                                const before = this.quill.getText(Math.max(0, range.index - 4), 4);

                                if (before === '\u00a0\u00a0\u00a0\u00a0' || before === '    ') {
                                    this.quill.deleteText(range.index - 4, 4, Quill.sources.USER);
                                    this.quill.setSelection(range.index - 4, 0, Quill.sources.SILENT);
                                }

                                return false;
                            }
                        }
                    }
                }
            }
        });
        policyQuill.root.style.fontFamily = 'Georgia, "Times New Roman", serif';
        policyQuill.format('font', 'georgia');


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
