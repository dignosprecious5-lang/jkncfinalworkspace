@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 pb-8 flex flex-col">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col shadow-sm min-h-[calc(100vh-100px)]">
        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 gap-4">
            <div class="flex items-center flex-1 min-w-0 gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">Finance Operations</h1>
                    <p class="text-xs text-gray-500">Connected master data and transaction workflows</p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if($canManageFinanceSettings)
                    <button
                        id="financeDropdownSettingsButton"
                        type="button"
                        onclick="window.financeModule.openDropdownSettings()"
                        class="w-9 h-9 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-800 flex items-center justify-center"
                        title="Finance dropdown settings"
                        aria-label="Finance dropdown settings"
                    >
                        <i class="fas fa-cog text-sm"></i>
                    </button>
                @endif
                <button id="addButton" onclick="window.openFinanceDrawer()" class="bg-blue-600 text-white px-5 py-2 rounded-md text-sm hover:bg-blue-700 transition">
                    + Add
                </button>
            </div>
        </div>

        <div class="px-4 pt-4 bg-white border-b border-gray-100">
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.scrollFinanceModuleTabs(-1)" class="w-9 h-9 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center justify-center shrink-0">
                    <i class="fas fa-chevron-left text-[11px]"></i>
                </button>

                <div id="moduleTabsShell" class="flex-1 overflow-hidden">
                    <div id="moduleTabs" class="flex flex-nowrap gap-2 overflow-x-auto scroll-smooth no-scrollbar py-1 snap-x snap-mandatory"></div>
                </div>

                <button type="button" onclick="window.scrollFinanceModuleTabs(1)" class="w-9 h-9 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center justify-center shrink-0">
                    <i class="fas fa-chevron-right text-[11px]"></i>
                </button>
            </div>

            <div id="workflowTabs" class="flex gap-2 text-[13px] overflow-x-auto pb-3 border-t border-gray-100 pt-3"></div>
            <div id="statusMessage" class="mt-1 mb-4 border border-blue-200 bg-blue-50 text-blue-700 text-[14px] px-4 py-3 rounded-md">
                Finance records are ready for encoding.
            </div>
        </div>

        @if(!empty($inventoryHistoryBoard))
            <div class="px-4 pt-4">
                <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-slate-50 via-white to-blue-50 p-4 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Inventory History Board</h2>
                            <p class="mt-1 text-sm text-gray-500">Shared movement history for employees and admins. Stock in, stock out, transfer, return, and adjustment events are logged here for transparency.</p>
                            <p class="mt-2 text-xs text-gray-500">Inventory formulas: Available Quantity = Current Quantity - Reserved Quantity. Total Cost = Current Quantity x Unit Cost.</p>
                        </div>
                        <div class="rounded-full border border-blue-100 bg-white px-3 py-1 text-xs font-medium text-blue-700">
                            {{ count($inventoryHistoryBoard) }} recent updates
                        </div>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-3">
                        @foreach($inventoryHistoryBoard as $entry)
                            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $entry['record_title'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $entry['record_number'] }}</p>
                                    </div>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">{{ $entry['action'] }}</span>
                                </div>
                                <div class="mt-3 space-y-2 text-sm text-gray-600">
                                    <p>
                                        <span class="font-medium text-gray-800">{{ $entry['changed_by'] }}</span>
                                        <span class="text-gray-400">•</span>
                                        <span>{{ $entry['changed_at'] }}</span>
                                    </p>
                                    <p class="text-gray-700">{{ $entry['reason'] ?: 'Movement recorded in the inventory audit trail.' }}</p>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <div class="rounded-lg bg-gray-50 px-3 py-2">
                                            <p class="text-[11px] uppercase tracking-wide text-gray-400">Quantity</p>
                                            <p class="font-medium text-gray-800">{{ $entry['quantity'] ?? '-' }}</p>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 px-3 py-2">
                                            <p class="text-[11px] uppercase tracking-wide text-gray-400">Available</p>
                                            <p class="font-medium text-gray-800">{{ $entry['available_quantity'] ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        {{ collect([$entry['location'] ?: null, $entry['department'] ?: null])->filter()->implode(' • ') ?: 'Location and department not set' }}
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div id="tableSection" class="p-4">
            <div class="border rounded-md bg-white">
                <table class="w-full text-sm table-fixed border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr id="tableHeadRow"></tr>
                    </thead>
                    <tbody id="tableBody" class="bg-white"></tbody>
                </table>
            </div>
        </div>

        <div id="previewSection" class="hidden p-4">
            <div class="flex gap-4 items-start">
                <div class="relative z-0 flex-1 min-w-0 overflow-hidden bg-white border border-gray-200 rounded-xl">
                    <div class="p-6" id="previewDocument"></div>
                </div>

                <div class="relative z-10 w-[380px] shrink-0 flex flex-col gap-4 pointer-events-auto">
                    <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 flex items-center justify-between">
                        <h2 class="text-[20px] font-semibold text-gray-900">Record Preview</h2>
                        <button type="button" onclick="window.closePreview()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl px-5 py-6">
                        <h3 id="previewModuleTitle" class="text-[18px] font-semibold text-gray-900 mb-6">Finance Record</h3>
                        <div class="inline-flex rounded-full border border-gray-200 bg-gray-50 p-1 mb-4 shadow-sm">
                            <button type="button" id="previewTabDetails" onclick="window.financeModule.changePreviewTab('details')" class="rounded-full px-4 py-2 text-sm font-medium transition bg-white text-blue-700 shadow-sm border border-gray-200">Details</button>
                            <button type="button" id="previewTabAttachments" onclick="window.financeModule.changePreviewTab('attachments')" class="rounded-full px-4 py-2 text-sm font-medium transition text-gray-600 hover:text-gray-900">Attachments</button>
                            <button type="button" id="previewTabTemplate" onclick="window.financeModule.changePreviewTab('template')" class="hidden rounded-full px-4 py-2 text-sm font-medium transition text-gray-600 hover:text-gray-900">Template</button>
                        </div>

                        <div id="previewTabContent"></div>
                        <div class="mt-6 flex flex-col gap-2" id="previewActions"></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="drawerSection" class="hidden fixed inset-0 z-50" aria-hidden="true">
            <div class="absolute inset-0 bg-black/40" onclick="window.closeFinanceDrawer()"></div>
            <div class="absolute inset-y-0 right-0 flex max-w-full">
                <div id="drawerPanel" class="w-screen max-w-[100vw] bg-white shadow-2xl flex h-full transform translate-x-full transition-transform duration-300 ease-in-out">
                <div id="drawerPreviewPane" class="flex-1 min-w-0 p-4 bg-gray-50 border-r border-gray-200">
                        <div class="h-full bg-white border border-gray-200 rounded-xl overflow-auto">
                            <div class="p-6 border-b border-gray-100">
                                <h3 class="text-[18px] font-semibold text-gray-900" id="drawerPreviewTitle">New Finance Record</h3>
                                <p class="text-xs text-gray-500 mt-1">The printable preview updates as we encode the record.</p>
                            </div>
                            <div class="p-6" id="drawerPreview">
                                <div class="text-sm text-gray-400 italic">Start filling out the form to see the preview.</div>
                            </div>
                        </div>
                    </div>

                    <div id="drawerFormPane" class="w-full max-w-[540px] bg-white flex flex-col h-full">
                        <div class="p-6 border-b flex items-center justify-between shrink-0">
                            <div>
                                <h2 class="font-bold text-lg text-gray-900" id="drawerTitle">Add Finance Record</h2>
                                <p class="text-xs text-gray-500 mt-1" id="drawerSubtitle">Choose a tab, then encode the record.</p>
                            </div>
                            <button type="button" onclick="window.closeFinanceDrawer()" class="text-sm text-gray-500 hover:text-gray-700">
                                Close
                            </button>
                        </div>

                        <form id="financeForm" class="p-6 space-y-4 flex-1 overflow-y-auto min-h-0">
                            @csrf
                            <input type="hidden" id="financeRecordId" name="finance_record_id" value="">
                            <input type="hidden" id="financeModuleKey" name="module_key" value="">
                            <input type="hidden" id="existingAttachmentsJson" name="existing_attachments_json" value="[]">

                            <div id="supplierModeTabs" class="hidden"></div>

                            <div id="recordCoreFields" class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1" id="recordNumberLabel">Record Number</label>
                                    <div class="relative">
                                        <input id="recordNumberInput" name="record_number" type="text" class="w-full border rounded-md p-2 pr-16" placeholder="PR-00001">
                                        <button
                                            id="recordNumberEditButton"
                                            type="button"
                                            onclick="window.financeModule.toggleRecordNumberEditMode()"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50"
                                            aria-pressed="false"
                                        >
                                            Edit
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1" id="recordTitleLabel">Name</label>
                                    <input id="recordTitleInput" name="record_title" type="text" class="w-full border rounded-md p-2" placeholder="Name">
                                </div>
                            </div>

                            <div id="recordMetaFields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1" id="recordDateLabel">Date</label>
                                    <input id="recordDateInput" name="record_date" type="date" class="w-full border rounded-md p-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1" for="recordTimeInput">Time</label>
                                    <input id="recordTimeInput" name="data[transaction_time]" type="time" class="w-full border rounded-md p-2">
                                </div>
                            </div>
                            <input id="amountInput" name="amount" type="hidden" step="0.01" value="">

                            <div id="statusField">
                                <label class="block text-sm font-medium mb-1">Status</label>
                                <select id="statusInput" name="status" class="w-full border rounded-md p-2 bg-slate-50" disabled aria-readonly="true">
                                    <option value="Draft">Draft</option>
                                    <option value="Pending Approval">Pending Approval</option>
                                    <option value="Partially Approved">Partially Approved</option>
                                    <option value="Approved">Approved</option>
                                    <option value="On Hold">On Hold</option>
                                    <option value="Reverted">Reverted</option>
                                    <option value="Awaiting Disbursement">Awaiting Disbursement</option>
                                    <option value="Disbursed">Disbursed</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Status is system-controlled and updates automatically from workflow progress.</p>
                            </div>

                            <div id="dynamicFields" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

                            <div id="attachmentsSection">
                                <label class="block text-sm font-medium mb-1 text-blue-700">Attachments</label>
                                <select
                                    id="attachmentCategoryInput"
                                    name="attachment_category"
                                    class="mb-2 w-full border border-blue-200 rounded-md p-2 bg-white text-sm"
                                >
                                    @forelse(collect($financeAttachmentTypes)->filter(fn ($type) => data_get($type, 'active', true) && !data_get($type, 'hidden', false)) as $attachmentType)
                                        <option value="{{ $attachmentType['value'] ?? $attachmentType['label'] ?? 'Supporting Document' }}">
                                            {{ $attachmentType['label'] ?? $attachmentType['value'] ?? 'Supporting Document' }}
                                        </option>
                                    @empty
                                        <option value="Supporting Document">Supporting Document</option>
                                    @endforelse
                                </select>
                                <input
                                    id="attachmentsInput"
                                    name="attachments[]"
                                    type="file"
                                    multiple
                                    class="w-full border border-blue-200 rounded-md p-2 bg-blue-50"
                                >
                                <p id="attachmentHint" class="mt-2 text-xs text-gray-500">Upload supporting files if needed.</p>
                                <div id="existingAttachmentList" class="mt-3 space-y-2"></div>
                            </div>
                        </form>

                        <div class="p-6 border-t flex gap-2 shrink-0">
                            <button onclick="window.closeFinanceDrawer()" class="flex-1 border rounded py-2">Cancel</button>
                            <button id="drawerSaveButton" onclick="window.saveFinanceRecord(event)" class="flex-1 bg-blue-600 text-white rounded py-2 hover:bg-blue-700 transition">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
</div>

<div id="financeToastStack" class="fixed bottom-4 right-4 z-[60] flex flex-col gap-2 pointer-events-none"></div>
@if($canManageFinanceSettings)
<div id="financeDropdownSettingsModal" class="hidden fixed inset-0 z-[75]" aria-hidden="true">
    <div class="absolute inset-0 bg-black/40" onclick="window.financeModule.closeDropdownSettings()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-5xl h-[84vh] rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden flex flex-col">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Finance Dropdown Settings</h3>
                    <p class="mt-1 text-xs text-gray-500">Edit selectable values for dropdowns that are not linked to master records.</p>
                </div>
                <button type="button" onclick="window.financeModule.closeDropdownSettings()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
            </div>
            <div class="flex flex-1 min-h-0">
                <div id="financeDropdownSettingsModules" class="w-64 border-r border-gray-100 overflow-y-auto p-3 bg-gray-50"></div>
                <div class="flex-1 min-w-0 flex flex-col">
                    <div class="border-b border-gray-100 px-5 py-3">
                        <p id="financeDropdownSettingsTitle" class="text-sm font-semibold text-gray-900">Select a finance tab</p>
                    </div>
                    <div id="financeDropdownSettingsFields" class="flex-1 overflow-y-auto p-5 space-y-4"></div>
                    <div class="border-t border-gray-100 px-5 py-4 flex justify-end gap-2">
                        <button type="button" onclick="window.financeModule.closeDropdownSettings()" class="rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="button" onclick="window.financeModule.saveDropdownSettings()" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Save Settings</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
<div id="financeLookupSelectorModal" class="hidden fixed inset-0 z-[70]">
    <div class="absolute inset-0 bg-black/40" onclick="window.financeModule.closeLookupSelector()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-7xl h-[86vh] rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden flex flex-col">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 id="financeLookupSelectorTitle" class="text-lg font-semibold text-gray-900">Select Account</h3>
                    <p id="financeLookupSelectorSubtitle" class="mt-1 text-xs text-gray-500">Choose a linked record from the selector list.</p>
                </div>
                <button type="button" onclick="window.financeModule.closeLookupSelector()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
            </div>
            <div class="p-5 space-y-4 flex-1 flex flex-col">
                <input id="financeLookupSelectorSearch" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100" placeholder="Search accounts...">
                <div class="rounded-2xl border border-gray-200 bg-white flex-1 flex flex-col overflow-hidden shadow-sm">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <p class="text-sm font-semibold text-gray-900">Existing Accounts</p>
                        <p class="text-xs text-gray-500">Select an account first before linking it.</p>
                    </div>
                    <div id="financeLookupSelectorList" class="flex-1 overflow-y-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.financeBootstrap = @js([
        'records' => $records,
        'sourceRecords' => $sourceRecords,
        'moduleLabels' => $moduleLabels,
        'lookupOptions' => $lookupOptions,
        'currentModule' => $currentModule,
        'currentWorkflowFilter' => $currentWorkflowFilter,
        'canApproveFinance' => $canApproveFinance,
        'canManageFinanceSettings' => $canManageFinanceSettings,
        'financeDropdownOptions' => $financeDropdownOptions,
        'financeAttachmentTypes' => $financeAttachmentTypes,
        'financeLabelOverrides' => $financeLabelOverrides,
        'officialApproverOptions' => $officialApproverOptions,
        'defaultApprovalSteps' => $defaultApprovalSteps,
        'requestTypeModules' => $requestTypeModules,
        'currentUserName' => $currentUserName,
        'currentUserEmail' => $currentUserEmail,
        'currentUserContact' => $currentUserContact,
        'csrfToken' => csrf_token(),
    ]);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="{{ asset('js/finance.js') }}?v={{ filemtime(public_path('js/finance.js')) }}"></script>
@endsection
