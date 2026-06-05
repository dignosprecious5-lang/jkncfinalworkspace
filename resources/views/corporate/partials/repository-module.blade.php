@php
    $config = $config ?? [];
    $moduleId = $config['moduleId'] ?? 'corporateRepository';
    $title = $config['title'] ?? 'Corporate Repository';
    $workflowTabs = ['uploaded' => 'Uploaded', 'submitted' => 'Submitted', 'accepted' => 'Accepted', 'reverted' => 'Reverted', 'archived' => 'Archived'];
    $workflowMessages = [
        'uploaded' => ['border-blue-200 bg-blue-50 text-blue-700', 'These records are uploaded and ready for submission.'],
        'submitted' => ['border-yellow-200 bg-yellow-50 text-yellow-700', 'These records are already submitted and waiting for admin approval.'],
        'accepted' => ['border-green-200 bg-green-50 text-green-700', 'These records were already accepted.'],
        'reverted' => ['border-red-200 bg-red-50 text-red-700', 'These records were reverted and can be corrected then resubmitted.'],
        'archived' => ['border-gray-200 bg-gray-50 text-gray-700', 'These records are archived.'],
    ];
    $defaultWorkflowTab = array_key_exists($config['defaultWorkflowTab'] ?? '', $workflowTabs)
        ? $config['defaultWorkflowTab']
        : 'uploaded';
    $defaultWorkflowMessage = $workflowMessages[$defaultWorkflowTab] ?? $workflowMessages['uploaded'];
@endphp

@section('title', $title)
@section('content')
<div id="{{ $moduleId }}" class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col" data-config='@json($config)'>
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">
        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 gap-4">
            <div class="min-w-0">
                <h1 class="text-lg font-semibold text-gray-900">{{ $title }}</h1>
                <p class="text-xs text-gray-500 truncate">{{ $config['purpose'] ?? '' }}</p>
            </div>

            <button type="button" data-action="open-create" class="bg-blue-600 text-white px-6 py-2 rounded text-sm shrink-0 hover:bg-blue-700">
                + Add
            </button>
        </div>

        <div class="px-4 pt-4 bg-white border-b border-gray-100">
            <div class="flex gap-8 text-[15px] text-gray-700 overflow-x-auto">
                @foreach ($workflowTabs as $key => $label)
                    <button type="button" data-workflow-tab="{{ $key }}" class="pb-3 whitespace-nowrap {{ $key === $defaultWorkflowTab ? 'border-b-2 border-blue-600 font-medium text-gray-900' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div data-status-message class="mt-3 mb-4 border {{ $defaultWorkflowMessage[0] }} text-[14px] px-4 py-3 rounded-md">
                {{ $defaultWorkflowMessage[1] }}
            </div>

            @if (!empty($config['filters']))
                <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-3" data-filter-bar>
                    @foreach (($config['filters'] ?? []) as $filter)
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ $filter['label'] ?? '' }}</label>
                            @if (($filter['type'] ?? 'text') === 'select')
                                <select data-filter-key="{{ $filter['key'] ?? '' }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                    <option value="">{{ $filter['placeholder'] ?? 'All' }}</option>
                                    @foreach (($filter['options'] ?? ($config['options'][$filter['optionsKey'] ?? ''] ?? [])) as $value => $label)
                                        @php
                                            $optionValue = is_int($value) ? $label : $value;
                                            $optionLabel = is_int($value) ? $label : $label;
                                        @endphp
                                        <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input
                                    type="{{ $filter['type'] ?? 'text' }}"
                                    data-filter-key="{{ $filter['key'] ?? '' }}"
                                    placeholder="{{ $filter['placeholder'] ?? '' }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div data-table-section class="p-4 flex-grow overflow-hidden">
            <div class="border rounded-md h-full overflow-auto bg-white">
                <table class="min-w-full text-sm border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            @foreach (($config['columns'] ?? []) as $column)
                                <th class="p-3 text-left whitespace-nowrap">{{ $column['label'] ?? '' }}</th>
                            @endforeach
                            <th class="p-3 text-left whitespace-nowrap">Draft</th>
                            <th class="p-3 text-left whitespace-nowrap">Approved</th>
                            <th class="p-3 text-left whitespace-nowrap">Workflow</th>
                            <th class="p-3 text-left whitespace-nowrap">Approval</th>
                            <th class="p-3 text-left whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody data-table-body class="bg-white"></tbody>
                </table>
            </div>
        </div>

        <div data-preview-section class="hidden fixed inset-0 z-50 overflow-hidden">
            <div data-action="close-preview" class="absolute inset-0 bg-black/40"></div>

            <div class="absolute inset-y-0 right-0 flex w-[88vw] max-w-[1500px] min-w-[960px]">
                <div class="w-full bg-white shadow-2xl flex h-full">
                    <div class="flex-1 min-w-0 p-4 bg-gray-50 border-r border-gray-200">
                        <div class="h-full bg-white border border-gray-200 rounded-xl overflow-hidden flex flex-col">
                            <div class="flex items-center justify-end gap-2 border-b border-gray-100 bg-white px-3 py-2">
                                <button type="button" data-preview-source="draft" class="rounded-md border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">Draft</button>
                                <button type="button" data-preview-source="approved" class="rounded-md border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Approved</button>
                            </div>
                            <div class="min-h-0 flex-1">
                            <iframe data-preview-frame class="w-full h-full bg-white hidden" frameborder="0"></iframe>
                            <div data-preview-image-wrapper class="hidden h-full items-center justify-center bg-white">
                                <img data-preview-image src="" alt="Document Preview" class="max-w-full max-h-full object-contain">
                            </div>
                            <div data-preview-empty class="h-full flex items-center justify-center text-gray-400 text-sm">
                                No document available for preview.
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-[420px] shrink-0 flex flex-col bg-white">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800">Record Details</h2>
                            <button type="button" data-action="close-preview" class="text-gray-400 hover:text-gray-600 text-lg">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto px-6 py-5">
                            <div data-preview-details class="space-y-4 text-[14px]"></div>
                            <div data-preview-documents class="mt-5 pt-4 border-t border-gray-200 space-y-2"></div>
                            <div data-preview-actions class="mt-6 flex flex-col gap-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div data-slider class="hidden fixed inset-0 z-50" aria-hidden="true">
            <div data-action="close-slider" class="absolute inset-0 bg-black/40"></div>

            <div class="absolute inset-y-0 right-0 flex w-[88vw] max-w-[1500px] min-w-[960px]">
                <div class="w-full bg-white shadow-2xl flex h-full">
                    <div class="flex-1 min-w-0 p-4 bg-gray-50 border-r border-gray-200">
                        <div class="h-full bg-white border border-gray-200 rounded-xl overflow-hidden flex flex-col">
                            <div class="flex items-center justify-end gap-2 border-b border-gray-100 bg-white px-3 py-2">
                                <button type="button" data-live-source="draft" class="rounded-md border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">Preview Draft</button>
                                <button type="button" data-live-source="approved" class="rounded-md border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Preview Approved</button>
                            </div>
                            <div class="min-h-0 flex-1">
                            <div data-live-empty class="h-full flex items-center justify-center text-gray-400 text-sm">
                                Upload a PDF or image to preview it here.
                            </div>
                            <iframe data-live-frame class="hidden w-full h-full bg-white" frameborder="0"></iframe>
                            <div data-live-image-wrapper class="hidden h-full items-center justify-center bg-white">
                                <img data-live-image src="" alt="Live Preview" class="max-w-full max-h-full object-contain">
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-[420px] shrink-0 flex flex-col bg-white">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 data-slider-title class="text-lg font-semibold text-gray-800">Add Record</h2>
                            <button type="button" data-action="close-slider" class="text-gray-400 hover:text-gray-600 text-lg">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto px-6 py-5">
                            <div data-error-box class="hidden mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                            <div data-success-box class="hidden mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"></div>

                            <form data-form class="space-y-4">
                                <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                                    <label class="block text-xs font-semibold text-blue-700 mb-1">Company</label>
                                    <div data-company-name class="text-sm font-semibold text-gray-900"></div>
                                    <div class="mt-3 space-y-2 text-xs text-gray-700">
                                        <div class="flex justify-between gap-3">
                                            <span class="font-semibold text-blue-700">Registration Number</span>
                                            <span data-company-registration-number class="text-right text-gray-900"></span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-blue-700">Principal Address</div>
                                            <div data-company-principal-address class="mt-1 leading-relaxed text-gray-900"></div>
                                        </div>
                                    </div>
                                </div>

                                <div data-fields class="space-y-4"></div>

                                @php
                                    $documentAccept = $config['documentUpload']['accept'] ?? '.pdf,.jpg,.jpeg,.png,.doc,.docx';
                                    $documentHelp = $config['documentUpload']['help'] ?? null;
                                @endphp
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Draft Documents</label>
                                        <input name="draft_documents[]" type="file" multiple accept="{{ $documentAccept }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:border-0 file:bg-blue-50 file:text-blue-700 file:px-3 file:py-1.5 file:rounded-md">
                                        @if ($documentHelp)
                                            <p class="mt-1 text-xs text-gray-500">{{ $documentHelp }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Approved Documents</label>
                                        <input name="approved_documents[]" type="file" multiple accept="{{ $documentAccept }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:border-0 file:bg-emerald-50 file:text-emerald-700 file:px-3 file:py-1.5 file:rounded-md">
                                        @if ($documentHelp)
                                            <p class="mt-1 text-xs text-gray-500">{{ $documentHelp }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 space-y-2">
                                    <div class="flex justify-between gap-3"><span>Uploaded By</span><span data-audit-uploaded-by class="font-medium text-gray-900 text-right"></span></div>
                                    <div class="flex justify-between gap-3"><span>Date Uploaded</span><span data-audit-date-uploaded class="font-medium text-gray-900 text-right">System generated</span></div>
                                    <div class="flex justify-between gap-3"><span>Last Updated By</span><span data-audit-updated-by class="font-medium text-gray-900 text-right">System generated</span></div>
                                    <div class="flex justify-between gap-3"><span>Last Updated Date</span><span data-audit-updated-date class="font-medium text-gray-900 text-right">System generated</span></div>
                                </div>
                            </form>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-200 flex items-center gap-3">
                            <button type="button" data-action="close-slider" class="flex-1 border border-gray-300 text-gray-700 rounded-lg py-2.5 text-sm font-medium hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="button" data-action="save" class="flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-blue-700 transition">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById(@json($moduleId));
    if (!root) return;

    const config = JSON.parse(root.dataset.config || '{}');
    const csrf = @json(csrf_token());
    const params = new URLSearchParams(window.location.search);
    const autoOpenRecordId = params.get('record');
    const autoOpenTab = (params.get('tab') || '').toLowerCase();
    const defaultWorkflowTab = ['uploaded', 'submitted', 'accepted', 'reverted', 'archived'].includes(config.defaultWorkflowTab)
        ? config.defaultWorkflowTab
        : 'uploaded';
    const state = {
        rows: [],
        workflow: ['uploaded', 'submitted', 'accepted', 'reverted', 'archived'].includes(autoOpenTab) ? autoOpenTab : defaultWorkflowTab,
        editingId: null,
        autoOpened: false,
        previewIndex: null,
        previewSource: 'draft',
        liveSource: 'draft',
        liveFiles: { draft: null, approved: null },
        filters: Object.fromEntries((config.filters || []).map((filter) => [filter.key, ''])),
        locations: {
            provinces: [],
            cities: [],
            barangays: []
        }
    };
    const qs = (selector) => root.querySelector(selector);
    const qsa = (selector) => Array.from(root.querySelectorAll(selector));

    const statusMessages = {
        uploaded: ['border-blue-200 bg-blue-50 text-blue-700', 'These records are uploaded and ready for submission.'],
        submitted: ['border-yellow-200 bg-yellow-50 text-yellow-700', 'These records are already submitted and waiting for admin approval.'],
        accepted: ['border-green-200 bg-green-50 text-green-700', 'These records were already accepted.'],
        reverted: ['border-red-200 bg-red-50 text-red-700', 'These records were reverted and can be corrected then resubmitted.'],
        archived: ['border-gray-200 bg-gray-50 text-gray-700', 'These records are archived.']
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));

    const money = (value) => {
        if (value === null || value === undefined || value === '') return '';
        const number = Number(value);
        return Number.isNaN(number) ? value : number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const fieldValue = (row, column) => {
        if (column.type === 'money') return money(row[column.key]);
        if (column.type === 'document') return row.document_url ? 'Available' : 'No document';
        return row[column.key] ?? '';
    };

    const optionEntries = (source) => {
        if (Array.isArray(source)) {
            return source.map((item) => ({ value: item, label: item }));
        }

        if (source && typeof source === 'object') {
            return Object.entries(source).map(([value, label]) => ({ value, label }));
        }

        return [];
    };

    const splitSelectedValues = (value) => String(value || '')
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);

    const renderMultiEntryItems = (entries, inputName) => entries.map((entry) => `
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-800">
            <input type="hidden" name="${inputName}[]" value="${escapeHtml(entry)}">
            <span>${escapeHtml(entry)}</span>
            <button type="button" class="text-blue-500 hover:text-blue-700" data-multi-entry-remove="${escapeHtml(entry)}" aria-label="Remove ${escapeHtml(entry)}">&times;</button>
        </span>
    `).join('');

    const documentList = (label, documents) => {
        if (!documents?.length) return '';
        return `
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">${label}</div>
                <div class="mt-1 space-y-1">
                    ${documents.map((doc) => `<a href="${doc.url}" target="_blank" class="block text-sm text-blue-600 hover:underline break-all">${escapeHtml(doc.name || 'Document')}</a>`).join('')}
                </div>
            </div>
        `;
    };

    const firstDocument = (row, source = 'draft') => {
        const documents = source === 'approved' ? row.approved_documents : row.draft_documents;
        return documents?.[0] || null;
    };

    const documentButton = (row, source, index) => {
        const doc = firstDocument(row, source);
        if (!doc?.url) return '<span class="text-gray-400">None</span>';
        const label = source === 'approved' ? 'Approved' : 'Draft';
        return `<button type="button" data-preview-index="${index}" data-source="${source}" class="text-blue-600 hover:underline">View ${label}</button>`;
    };

    const detectKind = (url = '') => {
        const lower = url.toLowerCase();
        if (lower.endsWith('.pdf')) return 'pdf';
        if (lower.endsWith('.jpg') || lower.endsWith('.jpeg') || lower.endsWith('.png')) return 'image';
        return '';
    };

    const setPreviewDocument = (url, frame, imageWrapper, image, empty, forcedKind = '') => {
        frame.classList.add('hidden');
        imageWrapper.classList.add('hidden');
        empty.classList.add('hidden');
        frame.src = '';
        image.src = '';

        if (!url) {
            empty.classList.remove('hidden');
            return;
        }

        const kind = forcedKind || detectKind(url);
        if (kind === 'pdf') {
            frame.src = url;
            frame.classList.remove('hidden');
        } else if (kind === 'image') {
            image.src = url;
            imageWrapper.classList.remove('hidden');
        } else {
            empty.classList.remove('hidden');
        }
    };

    const statusClass = (status = '') => {
        if (['Active', 'Approved', 'Completed', 'Executed'].includes(status)) return 'text-green-700 bg-green-50 border-green-200';
        if (['For Renewal', 'Pending', 'Submitted'].includes(status)) return 'text-amber-700 bg-amber-50 border-amber-200';
        if (['Expiring Soon', 'Needs Revision'].includes(status)) return 'text-orange-700 bg-orange-50 border-orange-200';
        if (['Expired', 'Rejected', 'Cancelled', 'Terminated'].includes(status)) return 'text-red-700 bg-red-50 border-red-200';
        return 'text-gray-700 bg-gray-50 border-gray-200';
    };

    const updateWorkflowUi = () => {
        qsa('[data-workflow-tab]').forEach((tab) => {
            const active = tab.dataset.workflowTab === state.workflow;
            tab.className = `pb-3 whitespace-nowrap ${active ? 'border-b-2 border-blue-600 font-medium text-gray-900' : 'text-gray-700'}`;
        });

        const message = qs('[data-status-message]');
        const [classes, text] = statusMessages[state.workflow] || statusMessages.uploaded;
        message.className = `mt-3 mb-4 border text-[14px] px-4 py-3 rounded-md ${classes}`;
        message.textContent = text;
    };

    const fetchRows = async () => {
        const url = new URL(config.dataUrl, window.location.origin);
        url.searchParams.set('workflow_status', state.workflow);
        Object.entries(state.filters || {}).forEach(([key, value]) => {
            if (String(value || '').trim() !== '') {
                url.searchParams.set(key, value);
            }
        });
        const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
        state.rows = await res.json();
        renderTable();
        openRequestedRecord();
    };

    const renderTable = () => {
        closePreview(false);
        updateWorkflowUi();
        const body = qs('[data-table-body]');

        if (!state.rows.length) {
            body.innerHTML = `<tr><td colspan="${(config.columns || []).length + 5}" class="px-4 py-8 text-center text-gray-500">No records found.</td></tr>`;
            return;
        }

        body.innerHTML = state.rows.map((row, index) => `
            <tr class="border-t border-gray-200 hover:bg-gray-50">
                ${(config.columns || []).map((column) => {
                    const value = fieldValue(row, column);
                    if (column.key === 'status') {
                        return `<td class="p-3 whitespace-nowrap"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${statusClass(value)}">${escapeHtml(value)}</span></td>`;
                    }
                    if (column.type === 'document') {
                        return `<td class="p-3 whitespace-nowrap">${row.document_url ? `<button type="button" data-preview-index="${index}" class="text-blue-600 hover:underline">View</button>` : '<span class="text-gray-400">None</span>'}</td>`;
                    }
                    return `<td class="p-3 whitespace-nowrap">${escapeHtml(value || '-')}</td>`;
                }).join('')}
                <td class="p-3 whitespace-nowrap">${documentButton(row, 'draft', index)}</td>
                <td class="p-3 whitespace-nowrap">${documentButton(row, 'approved', index)}</td>
                <td class="p-3 whitespace-nowrap">${escapeHtml(row.workflow_status || '-')}</td>
                <td class="p-3 whitespace-nowrap">${escapeHtml(row.approval_status || '-')}</td>
                <td class="p-3 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <button type="button" data-preview-index="${index}" class="text-blue-600 hover:underline">Open</button>
                        ${row.can_submit ? `<button type="button" data-submit-id="${row.id}" class="rounded-md bg-blue-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-700">Submit</button>` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    };

    const closePreview = (showTable = true) => {
        qs('[data-preview-frame]').src = '';
        qs('[data-preview-image]').src = '';
        qs('[data-preview-section]').classList.add('hidden');
        if (showTable) qs('[data-table-section]').classList.remove('hidden');
    };

    const renderPreviewSourceButtons = () => {
        qsa('[data-preview-source]').forEach((button) => {
            const active = button.dataset.previewSource === state.previewSource;
            button.classList.toggle('bg-blue-50', active && state.previewSource === 'draft');
            button.classList.toggle('bg-emerald-50', active && state.previewSource === 'approved');
        });
    };

    const updatePreviewDocument = () => {
        const row = state.rows[state.previewIndex];
        const doc = row ? firstDocument(row, state.previewSource) : null;
        setPreviewDocument(doc?.url || row?.document_url, qs('[data-preview-frame]'), qs('[data-preview-image-wrapper]'), qs('[data-preview-image]'), qs('[data-preview-empty]'));
        renderPreviewSourceButtons();
    };

    const openPreview = (index, source = null) => {
        const row = state.rows[index];
        if (!row) return;

        state.previewIndex = index;
        state.previewSource = source || (firstDocument(row, 'draft') ? 'draft' : 'approved');
        qs('[data-preview-section]').classList.remove('hidden');
        updatePreviewDocument();

        qs('[data-preview-details]').innerHTML = (config.previewFields || config.columns || []).map((field) => {
            const value = fieldValue(row, field);
            return `<div class="flex justify-between gap-4"><span class="text-gray-500">${escapeHtml(field.label)}</span><span class="text-right font-medium text-gray-900 break-words">${escapeHtml(value || '-')}</span></div>`;
        }).join('');

        qs('[data-preview-documents]').innerHTML = [
            documentList('Draft Documents', row.draft_documents || []),
            documentList('Approved Documents', row.approved_documents || [])
        ].filter(Boolean).join('') || '<div class="text-sm text-gray-400">No documents attached.</div>';

        const notesHtml = config.enableNotes ? `
            <div class="mt-5 pt-4 border-t border-gray-200">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Notes</div>
                <div class="mt-2 space-y-2">
                    ${(row.notes || []).map((note) => `
                        <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold text-gray-700">${escapeHtml(note.owner || 'System User')}</div>
                                    <div class="mt-1 text-sm text-gray-800 whitespace-pre-wrap">${escapeHtml(note.content || '')}</div>
                                </div>
                                ${note.can_delete ? `<button type="button" data-delete-note-id="${note.id}" class="text-xs text-red-600 hover:underline">Delete</button>` : ''}
                            </div>
                        </div>
                    `).join('') || '<div class="text-sm text-gray-400">No notes yet.</div>'}
                </div>
                <div class="mt-3 flex gap-2">
                    <input data-note-input type="text" class="min-w-0 flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Add note">
                    <button type="button" data-add-note-id="${row.id}" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Add</button>
                </div>
            </div>
        ` : '';

        qs('[data-preview-documents]').insertAdjacentHTML('beforeend', notesHtml);

        qs('[data-preview-actions]').innerHTML = `
            ${row.document_url ? `<a href="${row.document_url}" target="_blank" class="w-full border border-gray-300 text-gray-700 rounded-md py-2 text-center text-sm hover:bg-gray-50">Open Document</a>` : ''}
            ${row.can_edit ? `<button type="button" data-edit-index="${index}" class="w-full border border-blue-200 text-blue-700 rounded-md py-2 text-sm hover:bg-blue-50">Edit Details</button>` : ''}
            ${row.can_submit ? `<button type="button" data-submit-id="${row.id}" class="w-full bg-blue-600 text-white rounded-md py-2 text-sm hover:bg-blue-700">Submit for Approval</button>` : ''}
        `;
    };

    const openRequestedRecord = () => {
        if (state.autoOpened || !autoOpenRecordId) return;

        const index = state.rows.findIndex((row) => String(row.id) === String(autoOpenRecordId));
        if (index === -1) return;

        state.autoOpened = true;
        openPreview(index);
    };

    const renderFields = (row = {}) => {
        const fieldsWrap = qs('[data-fields]');
        fieldsWrap.innerHTML = (config.fields || []).map((field) => {
            const value = row[field.key] ?? field.default ?? '';
            const required = field.required ? 'required' : '';
            const options = optionEntries(field.optionsKey ? (config.options?.[field.optionsKey] || []) : (field.options || []));
            const datalistId = `${config.moduleId}-${field.key}-list`;
            const otherValue = row[field.otherKey] || '';
            const otherClass = field.otherKey && String(value).toLowerCase() === 'other' ? '' : 'hidden';

            if (field.type === 'textarea') {
                return `<div><label class="block text-xs font-semibold text-gray-500 mb-1">${escapeHtml(field.label)}</label><textarea name="${field.key}" ${required} class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm min-h-[88px]">${escapeHtml(value)}</textarea></div>`;
            }

            if (field.type === 'location') {
                const optionValues = [...new Set([value, ...options.map((option) => option.value)].filter(Boolean))];

                return `
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">${escapeHtml(field.label)}</label>
                        <select name="${field.key}" data-field="${field.key}" id="${datalistId}" ${required} class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white" autocomplete="off">
                            <option value="">Select ${escapeHtml(field.label)}</option>
                            ${optionValues.map((option) => `<option value="${escapeHtml(option)}" ${String(option) === String(value) ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}
                        </select>
                    </div>
                `;
            }

            if (field.type === 'select') {
                return `
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">${escapeHtml(field.label)}</label>
                        <select name="${field.key}" data-field="${field.key}" ${required} class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">Select ${escapeHtml(field.label)}</option>
                            ${options.map((option) => `<option value="${escapeHtml(option.value)}" ${String(option.value) === String(value) ? 'selected' : ''}>${escapeHtml(option.label)}</option>`).join('')}
                        </select>
                        ${field.otherKey ? `<input name="${field.otherKey}" data-other-for="${field.key}" value="${escapeHtml(otherValue)}" placeholder="Enter ${escapeHtml(field.label)}" class="${otherClass} mt-2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">` : ''}
                    </div>
                `;
            }

            if (field.type === 'display') {
                return `
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">${escapeHtml(field.label)}</label>
                        <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">${escapeHtml(value || '')}</div>
                    </div>
                `;
            }

            if (field.type === 'checkbox_group') {
                const selectedValues = splitSelectedValues(value);
                const knownOptions = options.map((option) => option.value).filter((option) => option !== 'Other');
                const otherSelections = selectedValues.filter((item) => !knownOptions.includes(item));
                const hasOther = otherSelections.length > 0 || selectedValues.includes('Other');
                const selectedName = field.selectedKey || `${field.key}_selected`;
                const otherName = field.otherKey || `${field.key}_other`;
                const selectedSummary = selectedValues.length
                    ? `${selectedValues.length} selected`
                    : 'No selections yet';

                return `
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">${escapeHtml(field.label)}</label>
                                <p class="mt-1 text-xs text-gray-500">Select one or more options. Use Other only when the filing needs a custom value.</p>
                            </div>
                            <span class="shrink-0 rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">${escapeHtml(selectedSummary)}</span>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 max-h-72 overflow-y-auto pr-1">
                            ${options.map((option) => {
                                const optionValue = option.value;
                                const optionLabel = option.label;
                                const isOther = optionValue === 'Other';
                                const checked = isOther ? hasOther : selectedValues.includes(optionValue);
                                return `
                                    <label class="group flex items-start gap-3 rounded-xl border px-3 py-3 text-sm transition ${checked ? 'border-blue-300 bg-blue-50 text-blue-900 shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'}">
                                        <input type="checkbox" name="${selectedName}[]" value="${escapeHtml(optionValue)}" ${checked ? 'checked' : ''} ${isOther ? `data-other-toggle="${otherName}"` : ''} class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="leading-5">${escapeHtml(optionLabel)}</span>
                                    </label>
                                `;
                            }).join('')}
                        </div>
                        <div class="mt-3 ${hasOther ? '' : 'hidden'}" data-other-wrapper="${otherName}">
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Other ${escapeHtml(field.label)}</label>
                            <input name="${otherName}" data-other-input="${otherName}" value="${escapeHtml(otherSelections.join(', '))}" placeholder="Add custom ${escapeHtml(field.label.toLowerCase())}" class="w-full border border-gray-300 rounded-lg bg-white px-3 py-2 text-sm">
                        </div>
                    </div>
                `;
            }

            if (field.type === 'multi_entry_select') {
                const selectedName = field.selectedKey || `${field.key}_selected`;
                const selectedValues = splitSelectedValues(value);
                const knownOptions = options.map((option) => option.value).filter((option) => option !== 'Other');
                const customSelections = selectedValues.filter((item) => !knownOptions.includes(item));
                const helperText = field.helperText || 'Search from the list, then add one or more options. Custom entries are allowed when needed.';
                const selectedSummary = selectedValues.length ? `${selectedValues.length} selected` : 'No entries added yet';

                return `
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">${escapeHtml(field.label)}</label>
                                <p class="mt-1 text-xs text-gray-500">${escapeHtml(helperText)}</p>
                            </div>
                            <span class="shrink-0 rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700" data-multi-entry-count="${selectedName}">${escapeHtml(selectedSummary)}</span>
                        </div>
                        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-3">
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <input
                                    type="text"
                                    list="${datalistId}"
                                    data-multi-entry-input="${selectedName}"
                                    data-multi-entry-options="${escapeHtml(JSON.stringify(knownOptions))}"
                                    data-multi-entry-other="${field.otherKey || ''}"
                                    placeholder="${escapeHtml(field.placeholder || `Search or type ${field.label.toLowerCase()}`)}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                >
                                <button
                                    type="button"
                                    data-multi-entry-add="${selectedName}"
                                    class="shrink-0 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100"
                                >
                                    Add
                                </button>
                            </div>
                            <datalist id="${datalistId}">
                                ${options.filter((option) => option.value !== 'Other').map((option) => `<option value="${escapeHtml(option.value)}"></option>`).join('')}
                            </datalist>
                            <div class="mt-3 flex flex-wrap gap-2" data-multi-entry-items="${selectedName}">
                                ${selectedValues.length ? renderMultiEntryItems(selectedValues, selectedName) : '<span class="text-xs text-gray-400">No entries added yet.</span>'}
                            </div>
                            ${field.otherKey ? `
                                <div class="mt-3 ${customSelections.length ? '' : 'hidden'}" data-other-wrapper="${field.otherKey}">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Custom ${escapeHtml(field.label)}</label>
                                    <input
                                        name="${field.otherKey}"
                                        data-other-input="${field.otherKey}"
                                        value="${escapeHtml(customSelections.join(', '))}"
                                        readonly
                                        class="w-full border border-gray-300 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600"
                                    >
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }

            return `<div><label class="block text-xs font-semibold text-gray-500 mb-1">${escapeHtml(field.label)}</label><input name="${field.key}" data-field="${field.key}" type="${field.type || 'text'}" value="${escapeHtml(value)}" ${required} class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>`;
        }).join('');

        updateLocationLists();
    };

    const updateLocationLists = () => {
        if (!config.locationData) return;
        const provinceInput = qs('[name="province"]');
        const cityInput = qs('[name="city_municipality"]');
        const barangayInput = qs('[name="barangay"]');
        if (!provinceInput || !cityInput || !barangayInput) return;

        const cities = Object.keys(config.locationData[provinceInput.value] || {});
        setDatalistOptions(`${config.moduleId}-city_municipality-list`, cities);

        const barangays = (config.locationData[provinceInput.value] || {})[cityInput.value] || [];
        setDatalistOptions(`${config.moduleId}-barangay-list`, barangays);
    };

    const setDatalistOptions = (listId, items) => {
        const list = document.getElementById(listId);
        if (!list) return;

        const currentValue = list.value || '';
        const names = (items || [])
            .map((item) => typeof item === 'string' ? item : item.name)
            .filter(Boolean);

        if (list.tagName === 'SELECT') {
            const label = list.closest('div')?.querySelector('label')?.textContent?.trim() || 'Option';
            const options = [...new Set([currentValue, ...names].filter(Boolean))];
            list.innerHTML = `
                <option value="">Select ${escapeHtml(label)}</option>
                ${options.map((name) => `<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('')}
            `;
            list.value = options.includes(currentValue) ? currentValue : '';
            return;
        }

        list.innerHTML = names.map((name) => `<option value="${escapeHtml(name)}"></option>`).join('');
    };

    const selectedLocationItem = (items, value) => {
        const needle = String(value || '').trim().toLowerCase();
        if (!needle) return null;

        return (items || []).find((item) => String(item.name || '').trim().toLowerCase() === needle) || null;
    };

    const fetchLocationJson = async (url) => {
        if (!url) return [];

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) return [];

            const payload = await response.json();
            return Array.isArray(payload) ? payload : [];
        } catch (error) {
            console.warn('Location lookup failed.', error);
            return [];
        }
    };

    const loadProvinceOptions = async () => {
        if (!config.locationEndpoints?.provinces || state.locations.provinces.length) return;

        state.locations.provinces = await fetchLocationJson(config.locationEndpoints.provinces);
        config.options = config.options || {};
        config.options.provinces = state.locations.provinces.map((item) => item.name).filter(Boolean);
        setDatalistOptions(`${config.moduleId}-province-list`, state.locations.provinces);
    };

    const updateRemoteLocationLists = async (changedField = '') => {
        if (!config.locationEndpoints) {
            updateLocationLists();
            return;
        }

        const provinceInput = qs('[name="province"]');
        const cityInput = qs('[name="city_municipality"]');
        const barangayInput = qs('[name="barangay"]');
        if (!provinceInput || !cityInput || !barangayInput) return;

        await loadProvinceOptions();

        const province = selectedLocationItem(state.locations.provinces, provinceInput.value);
        if (changedField === 'province') {
            cityInput.value = '';
            barangayInput.value = '';
            state.locations.cities = [];
            state.locations.barangays = [];
            setDatalistOptions(`${config.moduleId}-city_municipality-list`, []);
            setDatalistOptions(`${config.moduleId}-barangay-list`, []);
        }

        if (province?.code && province?.type) {
            const cityUrl = config.locationEndpoints.cities
                .replace('__TYPE__', encodeURIComponent(province.type))
                .replace('__CODE__', encodeURIComponent(province.code));
            state.locations.cities = await fetchLocationJson(cityUrl);
            setDatalistOptions(`${config.moduleId}-city_municipality-list`, state.locations.cities);
        }

        const city = selectedLocationItem(state.locations.cities, cityInput.value);
        if (changedField === 'city_municipality') {
            barangayInput.value = '';
            state.locations.barangays = [];
            setDatalistOptions(`${config.moduleId}-barangay-list`, []);
        }

        if (city?.code) {
            const barangayUrl = config.locationEndpoints.barangays
                .replace('__CITY__', encodeURIComponent(city.code));
            state.locations.barangays = await fetchLocationJson(barangayUrl);
            setDatalistOptions(`${config.moduleId}-barangay-list`, state.locations.barangays);
        }
    };

    const clearLivePreviewUi = () => {
        qs('[data-live-frame]').src = '';
        qs('[data-live-image]').src = '';
        qs('[data-live-frame]').classList.add('hidden');
        qs('[data-live-image-wrapper]').classList.add('hidden');
        qs('[data-live-empty]').classList.remove('hidden');
    };

    const resetLivePreview = () => {
        state.liveFiles = { draft: null, approved: null };
        clearLivePreviewUi();
    };

    const renderLivePreview = () => {
        const file = state.liveFiles[state.liveSource];
        qsa('[data-live-source]').forEach((button) => {
            const active = button.dataset.liveSource === state.liveSource;
            button.classList.toggle('bg-blue-50', active && state.liveSource === 'draft');
            button.classList.toggle('bg-emerald-50', active && state.liveSource === 'approved');
        });

        if (!file) {
            clearLivePreviewUi();
            return;
        }

        const url = URL.createObjectURL(file);
        const name = file.name.toLowerCase();
        const kind = file.type.includes('pdf') || name.endsWith('.pdf')
            ? 'pdf'
            : (file.type.includes('image') || name.endsWith('.jpg') || name.endsWith('.jpeg') || name.endsWith('.png') ? 'image' : '');
        setPreviewDocument(url, qs('[data-live-frame]'), qs('[data-live-image-wrapper]'), qs('[data-live-image]'), qs('[data-live-empty]'), kind);
    };

    const openSlider = (row = null) => {
        state.editingId = row?.id || null;
        qs('[data-slider-title]').textContent = row ? `Edit ${config.title}` : `Add ${config.title}`;
        qs('[data-form]').reset();
        qs('[data-company-name]').textContent = config.company?.company_name || 'Latest Approved GIS Company';
        qs('[data-company-registration-number]').textContent = config.company?.registration_number || 'No registration number found';
        qs('[data-company-principal-address]').textContent = config.company?.principal_address || config.company?.company_address || 'No principal address found';
        qs('[data-audit-uploaded-by]').textContent = row?.uploaded_by || config.currentUser || 'System User';
        qs('[data-audit-date-uploaded]').textContent = row?.date_uploaded || 'System generated';
        qs('[data-audit-updated-by]').textContent = row?.last_updated_by || 'System generated';
        qs('[data-audit-updated-date]').textContent = row?.last_updated_date || 'System generated';
        renderFields(row || {});
        updateRemoteLocationLists();
        resetLivePreview();
        qs('[data-error-box]').classList.add('hidden');
        qs('[data-success-box]').classList.add('hidden');
        qs('[data-slider]').classList.remove('hidden');
    };

    const closeSlider = () => {
        qs('[data-slider]').classList.add('hidden');
        state.editingId = null;
        resetLivePreview();
    };

    const showError = (message) => {
        const box = qs('[data-error-box]');
        box.innerHTML = message;
        box.classList.remove('hidden');
        qs('[data-success-box]').classList.add('hidden');
    };

    const syncMultiEntryOtherField = (name) => {
        const input = qs(`[data-multi-entry-input="${name}"]`);
        const otherName = input?.dataset.multiEntryOther;
        if (!otherName) return;

        const knownOptions = JSON.parse(input.dataset.multiEntryOptions || '[]');
        const hiddenInputs = Array.from(root.querySelectorAll(`[data-multi-entry-items="${name}"] input[type="hidden"]`));
        const values = hiddenInputs.map((element) => element.value.trim()).filter(Boolean);
        const customValues = values.filter((item) => !knownOptions.includes(item));
        const otherInput = qs(`[data-other-input="${otherName}"]`);
        const otherWrapper = qs(`[data-other-wrapper="${otherName}"]`);

        if (otherInput) {
            otherInput.value = customValues.join(', ');
        }

        otherWrapper?.classList.toggle('hidden', customValues.length === 0);
    };

    const updateMultiEntrySummary = (name) => {
        const itemsWrap = qs(`[data-multi-entry-items="${name}"]`);
        if (!itemsWrap) return;

        const entries = Array.from(itemsWrap.querySelectorAll('input[type="hidden"]'))
            .map((element) => element.value.trim())
            .filter(Boolean);
        const countLabel = qs(`[data-multi-entry-count="${name}"]`);
        if (countLabel) {
            countLabel.textContent = entries.length ? `${entries.length} selected` : 'No entries added yet';
        }

        if (!entries.length) {
            itemsWrap.innerHTML = '<span class="text-xs text-gray-400">No entries added yet.</span>';
        }

        syncMultiEntryOtherField(name);
    };

    const addMultiEntryValue = (name, rawValue) => {
        const itemsWrap = qs(`[data-multi-entry-items="${name}"]`);
        if (!itemsWrap) return;

        const normalized = String(rawValue || '').trim();
        if (!normalized || normalized.toLowerCase() === 'other') return;

        const currentValues = Array.from(itemsWrap.querySelectorAll('input[type="hidden"]'))
            .map((element) => element.value.trim())
            .filter(Boolean);

        if (currentValues.some((value) => value.toLowerCase() === normalized.toLowerCase())) {
            updateMultiEntrySummary(name);
            return;
        }

        currentValues.push(normalized);
        itemsWrap.innerHTML = renderMultiEntryItems(currentValues, name);
        updateMultiEntrySummary(name);
    };

    const saveRecord = async () => {
        const form = qs('[data-form]');
        const formData = new FormData(form);
        const isEditing = !!state.editingId;
        const url = isEditing ? config.updateUrl.replace('__ID__', state.editingId) : config.storeUrl;

        if (isEditing) formData.append('_method', 'PUT');

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: formData
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            const message = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'Unable to save record.');
            showError(message);
            return;
        }

        closeSlider();
        await fetchRows();
    };

    const submitRecord = async (id) => {
        const res = await fetch(config.submitUrl.replace('__ID__', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data.message || 'Unable to submit record.');
            return;
        }
        state.workflow = 'submitted';
        await fetchRows();
    };

    const addNote = async (id) => {
        const input = qs('[data-note-input]');
        const content = input?.value?.trim();
        if (!content) return;

        const formData = new FormData();
        formData.append('content', content);

        const res = await fetch(config.noteStoreUrl.replace('__ID__', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: formData
        });
        if (!res.ok) {
            alert('Unable to add note.');
            return;
        }
        await fetchRows();
        const index = state.rows.findIndex((row) => String(row.id) === String(id));
        if (index >= 0) openPreview(index, state.previewSource);
    };

    const deleteNote = async (noteId) => {
        const row = state.rows[state.previewIndex];
        if (!row || !confirm('Delete this note?')) return;

        const res = await fetch(config.noteDeleteUrl.replace('__ID__', row.id).replace('__NOTE__', noteId), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: new URLSearchParams({ _method: 'DELETE' })
        });
        if (!res.ok) {
            alert('Unable to delete note.');
            return;
        }
        await fetchRows();
        const index = state.rows.findIndex((item) => String(item.id) === String(row.id));
        if (index >= 0) openPreview(index, state.previewSource);
    };

    root.addEventListener('click', (event) => {
        const action = event.target.closest('[data-action]')?.dataset.action;
        if (action === 'open-create') openSlider();
        if (action === 'close-slider') closeSlider();
        if (action === 'save') saveRecord();
        if (action === 'close-preview') closePreview();

        const tab = event.target.closest('[data-workflow-tab]');
        if (tab) {
            state.workflow = tab.dataset.workflowTab;
            fetchRows();
        }

        const preview = event.target.closest('[data-preview-index]');
        if (preview) openPreview(Number(preview.dataset.previewIndex), preview.dataset.source || null);

        const previewSource = event.target.closest('[data-preview-source]');
        if (previewSource) {
            state.previewSource = previewSource.dataset.previewSource;
            updatePreviewDocument();
        }

        const liveSource = event.target.closest('[data-live-source]');
        if (liveSource) {
            state.liveSource = liveSource.dataset.liveSource;
            renderLivePreview();
        }

        const edit = event.target.closest('[data-edit-index]');
        if (edit) openSlider(state.rows[Number(edit.dataset.editIndex)]);

        const submit = event.target.closest('[data-submit-id]');
        if (submit) submitRecord(submit.dataset.submitId);

        const addNoteButton = event.target.closest('[data-add-note-id]');
        if (addNoteButton) addNote(addNoteButton.dataset.addNoteId);

        const deleteNoteButton = event.target.closest('[data-delete-note-id]');
        if (deleteNoteButton) deleteNote(deleteNoteButton.dataset.deleteNoteId);

        const addMultiEntryButton = event.target.closest('[data-multi-entry-add]');
        if (addMultiEntryButton) {
            const input = qs(`[data-multi-entry-input="${addMultiEntryButton.dataset.multiEntryAdd}"]`);
            if (input) {
                addMultiEntryValue(addMultiEntryButton.dataset.multiEntryAdd, input.value);
                input.value = '';
            }
        }

        const removeMultiEntryButton = event.target.closest('[data-multi-entry-remove]');
        if (removeMultiEntryButton) {
            const itemsWrap = removeMultiEntryButton.closest('[data-multi-entry-items]');
            removeMultiEntryButton.closest('span')?.remove();
            if (itemsWrap) {
                updateMultiEntrySummary(itemsWrap.dataset.multiEntryItems);
            }
        }
    });

    root.addEventListener('input', (event) => {
        if (event.target.matches('[data-filter-key]')) {
            state.filters[event.target.dataset.filterKey] = event.target.value;
            fetchRows();
        }

        if (event.target.matches('[name="province"], [name="city_municipality"]')) updateRemoteLocationLists(event.target.name);

        if (event.target.matches('[data-multi-entry-input]') && event.target.value.includes(',')) {
            const input = event.target;
            input.value.split(',').forEach((entry) => addMultiEntryValue(input.dataset.multiEntryInput, entry));
            input.value = '';
        }

        const otherInput = qs(`[data-other-for="${event.target.name}"]`);
        if (otherInput) {
            otherInput.classList.toggle('hidden', String(event.target.value).toLowerCase() !== 'other');
        }
    });

    root.addEventListener('keydown', (event) => {
        if (event.target.matches('[data-multi-entry-input]') && event.key === 'Enter') {
            event.preventDefault();
            addMultiEntryValue(event.target.dataset.multiEntryInput, event.target.value);
            event.target.value = '';
        }
    });

    root.addEventListener('change', (event) => {
        if (event.target.matches('[name="province"], [name="city_municipality"]')) {
            updateRemoteLocationLists(event.target.name);
        }

        if (event.target.matches('[data-filter-key]')) {
            state.filters[event.target.dataset.filterKey] = event.target.value;
            fetchRows();
        }

        if (event.target.matches('[data-other-toggle]')) {
            const input = qs(`[data-other-input="${event.target.dataset.otherToggle}"]`);
            const wrapper = qs(`[data-other-wrapper="${event.target.dataset.otherToggle}"]`);
            if (input) {
                wrapper?.classList.toggle('hidden', !event.target.checked);
                if (!event.target.checked) {
                    input.value = '';
                }
            }
        }

        if (event.target.matches('[data-multi-entry-input]') && event.target.value.trim() !== '') {
            addMultiEntryValue(event.target.dataset.multiEntryInput, event.target.value);
            event.target.value = '';
        }

        if (!event.target.matches('input[type="file"]')) return;
        const file = event.target.files?.[0] || null;
        const source = event.target.name.startsWith('approved_documents') ? 'approved' : 'draft';
        state.liveFiles[source] = file;
        state.liveSource = source;
        renderLivePreview();
    });

    fetchRows();
})();
</script>
@endpush
