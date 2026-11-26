@php
    $transmittal = $transmittal ?? new \App\Models\Transmittal();
    $isEditing = $transmittal->exists;
    $workspace = $workspace ?? false;
    $direction = old('direction', $transmittal->direction ?? 'send');
    $formRecipients = old('recipients', $isEditing
        ? $transmittal->recipients->map(fn ($recipient) => [
            'name' => $recipient->name,
            'email' => $recipient->email,
            'organization' => $recipient->organization,
        ])->all()
        : [['name' => '', 'email' => '', 'organization' => '']]);
    $formItems = old('items', $isEditing
        ? $transmittal->items->map(fn ($item) => [
            'id' => $item->id,
            'particular' => $item->particular,
            'unique_id' => $item->unique_id,
            'quantity' => $item->quantity,
            'description' => $item->description,
            'remarks' => $item->remarks,
        ])->all()
        : [['particular' => '', 'unique_id' => '', 'quantity' => '', 'description' => '', 'remarks' => '']]);
    $employees = $employees ?? collect();
    $contacts = $contacts ?? collect();
    $companies = $companies ?? collect();
    $approvedByName = old('approved_by_name', $transmittal->approved_by_name ?? '');
    $operationsManagerName = old('operations_manager', $transmittal->operations_manager ?? '');
    $selectedApprover = old('approved_by_employee', $employees->firstWhere('name', $approvedByName)?->id);
    $selectedOperationsManager = old('operations_manager_employee', $employees->firstWhere('name', $operationsManagerName)?->id);
    $deliveredByName = old('delivered_by', $transmittal->delivered_by ?? '');
    $receivedByName = old('received_by', $transmittal->received_by ?? '');
    $processingCompanyName = old('processing_company', $transmittal->processing_company ?? '');
    $deliveryType = old('delivery_type', $transmittal->delivery_type ?? '');
    $deliveryMethod = old('delivery_method', $transmittal->delivery_method ?? 'electronic');
    if ($formRecipients === []) {
        $formRecipients = [['name' => '', 'email' => '', 'organization' => '']];
    }
@endphp


<div class="drawer-backdrop" data-close-drawer></div>
<aside class="transmittal-drawer {{ $workspace ? 'workspace-drawer' : '' }}" data-transmittal-drawer aria-labelledby="drawer-title" aria-hidden="{{ $workspace ? 'false' : 'true' }}">
    <header class="drawer-header">
        <div>
            <h2 id="drawer-title">{{ $workspace ? 'Draft of Transmittal' : ($isEditing ? 'Edit Draft' : 'Add Transmittal') }}</h2>
            <p class="text-xs text-gray-500 mt-1">{{ $workspace ? 'Complete the transmittal information, save the draft, or submit it for review.' : 'Manual creation. Save a draft and continue later.' }}</p>
        </div>
        <div class="drawer-header-actions" style="display: flex; align-items: center; gap: 8px;">
            <button class="mobile-preview-toggle button subtle" type="button" data-show-document-preview style="display: none; font-size: 12px; padding: 4px 10px;">Preview</button>
            @if ($workspace)
                <a class="drawer-close" href="{{ route('transmittals.index', ['filter' => 'uploaded']) }}" aria-label="Back to uploaded transmittals">×</a>
            @else
                <button class="drawer-close" type="button" data-close-drawer aria-label="Close">×</button>
            @endif
        </div>
    </header>
    <form method="post" action="{{ $isEditing ? route('transmittals.update', $transmittal) : route('transmittals.store') }}" enctype="multipart/form-data" id="transmittal-form" class="drawer-form" data-upload-max-bytes="{{ config('ordo.upload.max_bytes') }}" data-upload-start-url="{{ route('transmittals.uploads.start') }}" data-chunk-bytes="{{ config('ordo.upload.chunk_bytes') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        <div class="drawer-body">
            @if (isset($errors) && $errors->any())
                <div class="drawer-errors" role="alert">
                    <strong>Please check these details:</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if ($isEditing && $transmittal->status === 'rejected')
                <div class="drawer-errors" role="status">
                    <strong>Reverted for revision</strong>
                    <p>{{ $transmittal->rejection_reason }}</p>
                    <small>Update this record and resubmit under {{ $transmittal->tracking_number }}.</small>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Ref #</label>
                <input type="text" value="{{ $isEditing ? $transmittal->tracking_number : 'AUTO-INCREMENT' }}" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600">
                <p class="mt-1 text-xs text-gray-400">Date is automatically set to today when saved.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Date</label>
                <input type="date" name="transmittal_date" value="{{ old('transmittal_date', $isEditing ? $transmittal->transmittal_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" data-preview="date">
                @error('transmittal_date')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Mode</label>
                <select name="direction" id="drawer-mode-select" required data-preview="direction" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" onchange="const hint = document.getElementById('drawer-mode-hint'); const partyLabel = document.getElementById('drawer-party-label'); const partyInput = document.getElementById('drawer-party-input'); const officeLabel = document.getElementById('drawer-office-label'); const officeInput = document.getElementById('drawer-office-input'); if (this.value === 'receive') { if (hint) hint.textContent = 'Incoming transmittal'; if (partyLabel) partyLabel.textContent = 'From'; if (partyInput) partyInput.placeholder = 'Enter external sender / source'; if (officeLabel) officeLabel.textContent = 'To (Office)'; if (officeInput) officeInput.placeholder = 'Enter office receiving the transmittal'; } else { if (hint) hint.textContent = 'Outgoing transmittal'; if (partyLabel) partyLabel.textContent = 'To'; if (partyInput) partyInput.placeholder = 'Enter external receiver / destination'; if (officeLabel) officeLabel.textContent = 'From (Office)'; if (officeInput) officeInput.placeholder = 'Enter office sending the transmittal'; }">
                    <option value="send" @selected($direction === 'send')>SEND</option>
                    <option value="receive" @selected($direction === 'receive')>RECEIVE</option>
                </select>
                <p class="mt-1 text-xs text-gray-400" id="drawer-mode-hint">{{ $direction === 'receive' ? 'Incoming transmittal' : 'Outgoing transmittal' }}</p>
                @error('direction')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
            </div>

            <input type="hidden" name="subject" value="{{ old('subject', $transmittal->subject ?: 'Transmittal Form') }}" id="subject-field" data-preview="subject">
            <input type="hidden" name="related_company" value="{{ old('related_company', $transmittal->related_company ?? '') }}" data-preview="related-company">
            <input type="hidden" name="confidentiality" value="{{ old('confidentiality', $transmittal->confidentiality ?? 'standard') }}" data-preview="confidentiality">
            <input type="hidden" name="purpose" value="{{ old('purpose', $transmittal->purpose ?? 'Official Transmittal') }}" data-preview="purpose">

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1" id="drawer-party-label"><span data-counterparty-label>{{ $direction === 'receive' ? 'From' : 'To' }}</span></label>
                <input type="text" name="recipients[0][name]" id="drawer-party-input" value="{{ old('recipients.0.name', $formRecipients[0]['name'] ?? '') }}" placeholder="{{ $direction === 'receive' ? 'Enter external sender / source' : 'Enter external receiver / destination' }}" data-preview="recipient" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                @error('recipients.0.name')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1" id="drawer-office-label"><span data-office-label>{{ $direction === 'receive' ? 'To (Office)' : 'From (Office)' }}</span></label>
                <input type="text" name="origin_office" id="drawer-office-input" value="{{ old('origin_office', $transmittal->origin_office ?? config('ordo.office_name', 'John Kelly & Company')) }}" required maxlength="160" placeholder="{{ $direction === 'receive' ? 'Enter office receiving the transmittal' : 'Enter office sending the transmittal' }}" data-preview="office" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                @error('origin_office')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Address</label>
                <textarea name="recipient_address" rows="3" placeholder="Enter address" data-preview="address" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">{{ old('recipient_address', $transmittal->recipient_address ?? '') }}</textarea>
                @error('recipient_address')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
            </div>

            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Delivery Type</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Type</label>
                        <select name="delivery_type" id="drawer-delivery-type" data-preview="delivery-type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" onchange="const bp = document.getElementById('drawer-by-person-block'); const rm = document.getElementById('drawer-registered-mail-block'); const el = document.getElementById('drawer-electronic-block'); const em = document.getElementById('drawer-recipient-email-block'); if (bp) bp.style.display = (this.value === 'by_person' || this.value === 'By Person') ? 'block' : 'none'; if (rm) rm.style.display = (this.value === 'registered_mail' || this.value === 'Registered Mail') ? 'block' : 'none'; if (el) el.style.display = (this.value === 'electronic' || this.value === 'Electronic') ? 'block' : 'none'; if (em) em.style.display = (this.value === 'electronic' || this.value === 'Electronic' || {{ $workspace || $isEditing ? 'true' : 'false' }}) ? 'block' : 'none';">
                            <option value="">Select delivery type</option>
                            <option value="by_person" @selected($deliveryType === 'by_person' || $deliveryType === 'By Person')>By Person</option>
                            <option value="registered_mail" @selected($deliveryType === 'registered_mail' || $deliveryType === 'Registered Mail')>Registered Mail</option>
                            <option value="electronic" @selected($deliveryType === 'electronic' || $deliveryType === 'Electronic')>Electronic</option>
                        </select>
                        @error('delivery_type')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
                    </div>

                    <div id="drawer-by-person-block" style="{{ ($deliveryType === 'by_person' || $deliveryType === 'By Person') ? 'display: block;' : 'display: none;' }}">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Who?</label>
                        <input type="text" name="by_person_who" value="{{ old('by_person_who', $transmittal->by_person_who ?? '') }}" placeholder="Enter person responsible" data-preview="by-person-who" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        @error('by_person_who')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
                    </div>

                    @php
                        $savedMailProvider = old('registered_mail_provider', $transmittal->registered_mail_provider ?? '');
                        $knownMailProviders = ['LBC', 'J&T', 'Postal'];
                        $isOtherMailProvider = $savedMailProvider !== '' && !in_array($savedMailProvider, $knownMailProviders, true) && $savedMailProvider !== 'Others';
                        $selectedMailProvider = $isOtherMailProvider ? 'Others' : $savedMailProvider;
                        $otherMailProviderVal = old('registered_mail_provider_other', $isOtherMailProvider ? $savedMailProvider : '');
                    @endphp
                    <div id="drawer-registered-mail-block" style="{{ ($deliveryType === 'registered_mail' || $deliveryType === 'Registered Mail') ? 'display: block;' : 'display: none;' }}">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Courier / Provider</label>
                        <select name="registered_mail_provider" id="drawer-registered-mail-provider" data-preview="courier-provider" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" onchange="const otherBlock = document.getElementById('drawer-registered-mail-other-block'); if (otherBlock) otherBlock.style.display = this.value === 'Others' ? 'block' : 'none';">
                            <option value="">Select provider</option>
                            @foreach (['LBC', 'J&T', 'Postal', 'Others'] as $provider)
                                <option value="{{ $provider }}" @selected($selectedMailProvider === $provider)>{{ $provider }}</option>
                            @endforeach
                        </select>
                        <div id="drawer-registered-mail-other-block" class="mt-2" style="{{ $selectedMailProvider === 'Others' ? 'display: block;' : 'display: none;' }}">
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Specify Provider / Details</label>
                            <input type="text" name="registered_mail_provider_other" value="{{ $otherMailProviderVal }}" placeholder="Enter provider name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        </div>
                    </div>

                    <div id="drawer-electronic-block" style="{{ ($deliveryType === 'electronic' || $deliveryType === 'Electronic') ? 'display: block;' : 'display: none;' }}">
                        <input type="hidden" name="delivery_method" value="{{ $deliveryMethod }}">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Electronic Method</label>
                        <select name="electronic_method" data-preview="delivery-method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                            <option value="">Select method</option>
                            <option value="Email" @selected(old('electronic_method', $transmittal->electronic_method ?? 'Email') === 'Email')>Email</option>
                        </select>
                    </div>

                    <div id="drawer-recipient-email-block" style="{{ ($deliveryType === 'electronic' || $deliveryType === 'Electronic' || $workspace || $isEditing) ? 'display: block;' : 'display: none;' }}">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Recipient Email</label>
                        <input type="email" name="recipients[0][email]" value="{{ old('recipients.0.email', $formRecipients[0]['email'] ?? '') }}" placeholder="Enter email address" data-preview="recipient-email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        @error('recipients.0.email')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <details open class="drawer-actions-details">
                    <summary class="flex items-center justify-between cursor-pointer list-none">
                        <h3 class="text-sm font-semibold text-gray-800">Actions</h3>
                        <span class="text-xs text-gray-500">▼</span>
                    </summary>
                    @php($selectedActions = old('delivery_actions', $transmittal->delivery_actions ?? ['email']))
                    <div class="grid grid-cols-2 gap-3 text-sm mt-3">
                        @foreach (['delivery' => 'Delivery', 'pick_up' => 'Pick Up', 'drop_off' => 'Drop Off', 'email' => 'Email'] as $value => $label)
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                                <input type="checkbox" name="delivery_actions[]" value="{{ $value }}" @checked(in_array($value, $selectedActions, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </details>
            </div>



            <section class="drawer-section item-section border-t border-gray-200 pt-4">
                <div class="item-section-heading flex items-center justify-between mb-3">
                    <label class="block text-xs font-semibold text-gray-700" style="font-size: 13px;">List of Items</label>
                    <button class="text-xs bg-blue-600 text-white px-2.5 py-1.5 rounded-md hover:bg-blue-700" type="button" id="add-item">＋ Add Row</button>
                </div>
                <div id="item-list" style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach ($formItems as $index => $item)
                        <fieldset class="item-editor-row rounded-lg border border-gray-200 p-3 bg-gray-50 space-y-2">
                            <legend class="flex items-center justify-between text-xs font-semibold text-gray-700 px-1">Item {{ $loop->iteration }}<button class="remove-item text-xs text-red-500 underline ml-2" type="button">Remove</button></legend>
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Particular</label>
                                <input name="items[{{ $index }}][particular]" value="{{ $item['particular'] ?? '' }}" maxlength="200" placeholder="Item name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Unique ID</label>
                                    <input name="items[{{ $index }}][unique_id]" value="{{ $item['unique_id'] ?? '' }}" maxlength="120" placeholder="ID / serial" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Qty.</label>
                                    <input type="number" min="0" step="any" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? '' }}" maxlength="40" placeholder="Quantity" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                </div>
                            </div>
                            @if ($isEditing && !empty($item['id']) && ($linkedItem = $transmittal->items->firstWhere('id', $item['id']))?->document)
                                <small class="item-file-existing block text-xs text-blue-600">Attached: <a href="{{ route('transmittals.documents.download', [$transmittal, $linkedItem->document]) }}">{{ $linkedItem->document->original_name }}</a></small>
                            @endif
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Description</label>
                                <input name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" maxlength="2000" placeholder="Description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Remarks</label>
                                <input name="items[{{ $index }}][remarks]" value="{{ $item['remarks'] ?? '' }}" maxlength="2000" placeholder="Remarks" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Upload File</label>
                                <input type="file" name="items[{{ $index }}][attachment]" accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff,.doc,.docx" data-file-name-target data-upload-max-bytes="{{ config('ordo.upload.max_bytes') }}" class="w-full text-xs">
                                <small class="file-selection-name block text-xs text-gray-400 mt-1">No file selected</small>
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                <template id="item-row-template">
                    <fieldset class="item-editor-row rounded-lg border border-gray-200 p-3 bg-gray-50 space-y-2">
                        <legend class="flex items-center justify-between text-xs font-semibold text-gray-700 px-1">Item<button class="remove-item text-xs text-red-500 underline ml-2" type="button">Remove</button></legend>
                        <input type="hidden" name="items[0][id]" value="">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Particular</label>
                            <input name="items[0][particular]" maxlength="200" placeholder="Item name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Unique ID</label>
                                <input name="items[0][unique_id]" maxlength="120" placeholder="ID / serial" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Qty.</label>
                                <input type="number" min="0" step="any" name="items[0][quantity]" maxlength="40" placeholder="Quantity" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Description</label>
                            <input name="items[0][description]" maxlength="2000" placeholder="Description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Remarks</label>
                            <input name="items[0][remarks]" maxlength="2000" placeholder="Remarks" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Upload File</label>
                            <input type="file" name="items[0][attachment]" accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff,.doc,.docx" data-file-name-target data-upload-max-bytes="{{ config('ordo.upload.max_bytes') }}" class="w-full text-xs">
                            <small class="file-selection-name block text-xs text-gray-400 mt-1">No file selected</small>
                        </div>
                    </fieldset>
                </template>
            </section>

            <div class="drawer-divider"></div>

            <section class="drawer-section supporting-attachments-section">
                <h3>Supporting Attachments</h3>
                @if ($isEditing && $transmittal->documents->isNotEmpty())
                    <div class="existing-files">
                        @foreach ($transmittal->documents as $document)
                            <a href="{{ route('transmittals.documents.download', [$transmittal, $document]) }}">{{ $document->original_name }} · {{ ucfirst($document->category) }}</a>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="supporting_attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff,.doc,.docx" data-supporting-attachments data-upload-max-bytes="{{ config('ordo.upload.max_bytes') }}">
                <small class="file-selection-name" data-supporting-file-names>No file selected</small>
                <p class="field-hint">You may upload multiple supporting files. These will be attached when using Send Email.</p>
            </section>

            <div class="drawer-divider"></div>

            <section class="drawer-section space-y-4 pt-4 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Signatories</h3>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Prepared by</label>
                    <input value="{{ $transmittal?->prepared_by_name ?: (auth()->user()?->name ?? 'Public submitter') }}" readonly data-preview="prepared-by" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Prepared at</label>
                    <input value="{{ $transmittal?->prepared_at?->format('m/d/Y, g:i:s A') ?? now()->format('m/d/Y, g:i:s A') }}" readonly data-preview="prepared-at" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Approved by</label>
                    <select name="approved_by_employee" data-signatory-select="approved_by_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white mb-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        <option value="">Select employee approver</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" data-employee-name="{{ $employee->name }}" @selected((string) $selectedApprover === (string) $employee->id)>{{ $employee->name }}{{ $employee->transmittal_role ? ' - '.str($employee->transmittal_role)->replace('_', ' ')->title() : '' }}</option>
                        @endforeach
                    </select>
                    <input name="approved_by_name" value="{{ $approvedByName }}" maxlength="160" placeholder="Or type approver manually" data-preview="approved-by" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Operations Manager</label>
                    <select name="operations_manager_employee" data-signatory-select="operations_manager" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white mb-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        <option value="">Select Operations Manager</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" data-employee-name="{{ $employee->name }}" @selected((string) $selectedOperationsManager === (string) $employee->id)>{{ $employee->name }}{{ $employee->transmittal_role ? ' - '.str($employee->transmittal_role)->replace('_', ' ')->title() : '' }}</option>
                        @endforeach
                    </select>
                    <input name="operations_manager" value="{{ $operationsManagerName }}" maxlength="160" placeholder="Or type Operations Manager manually" data-preview="operations-manager" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Delivered by</label>
                    <select name="delivered_by_employee" data-signatory-select="delivered_by" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white mb-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        <option value="">Select employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->name }}" data-employee-name="{{ $employee->name }}" @selected($deliveredByName === $employee->name)>{{ $employee->name }}{{ $employee->transmittal_role ? ' - '.str($employee->transmittal_role)->replace('_', ' ')->title() : '' }}</option>
                        @endforeach
                    </select>
                    <input name="delivered_by" value="{{ $deliveredByName }}" maxlength="160" placeholder="Or type delivered by manually" data-preview="delivered-by" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Received by (Contact / Employee)</label>
                    <select name="received_by_contact" data-signatory-select="received_by" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white mb-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        <option value="">Select contact or employee</option>
                        <optgroup label="Employees">
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->name }}" data-employee-name="{{ $employee->name }}" @selected($receivedByName === $employee->name)>{{ $employee->name }}{{ $employee->transmittal_role ? ' - '.$employee->transmittal_role : '' }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Contacts">
                            @foreach ($contacts as $contact)
                                <option value="{{ $contact->name }}" data-employee-name="{{ $contact->name }}" @selected($receivedByName === $contact->name)>{{ $contact->name }}{{ $contact->organization ? ' ('.$contact->organization.')' : '' }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <input name="received_by" value="{{ $receivedByName }}" maxlength="160" placeholder="Or type receiver manually" data-preview="received-by" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Processing for Company / Organization</label>
                    <select name="processing_company_selection" data-signatory-select="processing_company" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white mb-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        <option value="">Select company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company }}" data-employee-name="{{ $company }}" @selected($processingCompanyName === $company)>{{ $company }}</option>
                        @endforeach
                    </select>
                    <input name="processing_company" value="{{ $processingCompanyName }}" maxlength="160" placeholder="Or type company manually" data-preview="processing-company" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                </div>
            </section>
            @if ($isEditing && ! $workspace)
                <section class="drawer-section">
                    <h3>Attachments &amp; Smart Upload</h3>
                    @if ($isEditing && $transmittal->documents->isNotEmpty())
                        <div class="existing-files">
                            @foreach ($transmittal->documents as $document)
                                <a href="{{ route('transmittals.documents.download', [$transmittal, $document]) }}">{{ $document->original_name }} · {{ ucfirst($document->category) }}</a>
                            @endforeach
                        </div>
                    @endif
                    <label class="drop-zone" id="drop-zone" for="document-input">
                        <span class="upload-icon">↑</span><strong>Drop your files here</strong><span>or <u>browse files</u></span>
                        <small>PDF, images, DOCX · up to 20 MB each</small>
                        <input type="file" name="documents[]" id="document-input" multiple accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff,.doc,.docx" data-upload-max-bytes="{{ config('ordo.upload.max_bytes') }}">
                    </label>
                    <input type="file" id="transmittal-zip-input" accept=".zip,application/zip,application/x-zip-compressed" hidden>
                    <div class="upload-controls">
                        <button class="button subtle" type="button" id="analyze-files" data-max-files="{{ config('ordo.ocr.max_files') }}" data-analyze-url="{{ route('transmittals.analyze') }}" disabled>✧ Classify &amp; extract</button>
                        <span class="field-hint">OCR uses the configured server tools.</span>
                    </div>
                    <p class="ocr-message" id="ocr-message" role="status"></p>
                    <div id="file-list" class="file-list"></div>
                    <p class="privacy-callout"><span>◇</span> Attachments and register details are publicly accessible. Do not upload restricted or confidential material.</p>
                </section>
            @endif
        </div>
        
       <footer class="drawer-footer" style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid #e2e8f1; background: #fff; position: sticky; bottom: 0; z-index: 10;">
    @if ($workspace)
        <a
            class="button subtle"
            href="{{ route('transmittals.index', ['filter' => 'uploaded']) }}"
            style="min-height: 38px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #475569; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; text-decoration: none;"
        >
            Cancel
        </a>
        <button
            class="button secondary"
            type="submit"
            name="intent"
            value="draft"
            style="min-height: 38px; padding: 0 18px; border: 1px solid #2563eb; border-radius: 8px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;"
        >
            Save Draft
        </button>
        <button
            class="button primary submit-review-button"
            type="submit"
            name="intent"
            value="submit"
            style="min-height: 38px; padding: 0 18px; border: 1px solid #1e40af; border-radius: 8px; background: #1d4ed8; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;"
        >
            Submit for review
        </button>
    @else
        <button
            class="button subtle"
            type="button"
            data-close-drawer
            style="min-height: 38px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #475569; font-size: 13px; font-weight: 600; cursor: pointer;"
        >
            Cancel
        </button>
        <button
            class="button secondary"
            type="submit"
            name="intent"
            value="draft"
            style="min-height: 38px; padding: 0 18px; border: 1px solid #2563eb; border-radius: 8px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;"
        >
            Save Draft
        </button>
        <button
            class="button primary submit-review-button"
            type="submit"
            name="intent"
            value="submit"
            style="min-height: 38px; padding: 0 18px; border: 1px solid #1e40af; border-radius: 8px; background: #1d4ed8; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;"
        >
            Submit for review
        </button>
    @endif

    @error('intent')
        <span class="field-error" id="intent-error">
            {{ $message }}
        </span>
    @enderror
</footer>
    </form>
</aside>
