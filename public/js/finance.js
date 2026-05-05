(() => {
    const bootstrap = window.financeBootstrap || {};
    const csrfToken = bootstrap.csrfToken || '';
    const workflowFilters = ['all', 'Uploaded', 'Shared', 'Submitted', 'Accepted', 'Reverted', 'Archived'];

    const textField = (name, label, options = {}) => ({ name, label, type: 'text', ...options });
    const numberField = (name, label, options = {}) => ({ name, label, type: 'number', ...options });
    const dateField = (name, label, options = {}) => ({ name, label, type: 'date', ...options });
    const timeField = (name, label, options = {}) => ({ name, label, type: 'time', ...options });
    const textareaField = (name, label, options = {}) => ({ name, label, type: 'textarea', rows: 3, fullWidth: true, ...options });
    const selectField = (name, label, options = {}) => ({ name, label, type: 'select', options: [], ...options });
    const checkboxField = (name, label, options = {}) => ({ name, label, type: 'checkbox', ...options });
    const checkboxGroupField = (name, label, options = {}) => ({ name, label, type: 'checkbox-group', options: [], fullWidth: true, ...options });
    const radioGroupField = (name, label, options = {}) => ({ name, label, type: 'radio-group', options: [], fullWidth: true, ...options });
    const calculationField = (name, label, options = {}) => ({ name, label, type: 'calculation', readOnly: true, ...options });

    const friendlyFieldLabels = {
        'module_key': 'Finance section',
        'record_number': 'Record number',
        'record_title': 'Name',
        'record_date': 'Date',
        'data.transaction_time': 'Time',
        'amount': 'Amount',
        'status': 'Status',
        'data.completion_mode': 'Completion mode',
        'data.supplier_id': 'Supplier',
        'data.coa_id': 'Chart of account',
        'data.parent_account_id': 'Main account',
        'data.linked_coa_id': 'Linked chart of account',
        'data.linked_pr_id': 'Linked PR',
        'data.linked_ca_id': 'Linked CA',
        'data.release_schedule': 'Release schedule',
        'data.release_count': 'Number of releases',
        'data.amount_per_release': 'Amount per release',
        'data.liquidation_calculation': 'Liquidation calculation',
        'data.cash_release_date': 'Cash release date',
        'data.cash_release_time': 'Cash release time',
        'data.paid_through': 'Paid through',
        'data.linked_lr_id': 'Linked LR',
        'data.linked_po_id': 'Linked PO',
        'data.linked_dv_id': 'Linked DV',
        'data.bank_account_id': 'Bank account',
        'data.funding_bank_account_id': 'Funding bank account',
        'data.receiving_bank_account_id': 'Receiving bank / cash account',
        'data.source_bank_account_id': 'Source bank account',
        'data.destination_bank_account_id': 'Destination bank account',
        'data.source_document_type': 'Linked document type',
        'data.source_document_id': 'Linked source document',
        'data.master_item_type': 'Item type',
        'data.master_item_id': 'Item',
        'data.linked_item_type': 'Item type',
        'data.linked_item_id': 'Item / service',
        'data.payroll_expense_coa_id': 'Payroll expense account',
        'data.asset_coa_id': 'Asset account',
        'data.asset_code': 'Asset code',
    };

    function friendlyLabelForError(fieldKey) {
        return friendlyFieldLabels[fieldKey] || fieldKey.replace(/^data\./, '').replace(/_/g, ' ');
    }

    function getFriendlyErrorMessage(message = '') {
        const match = String(message).match(/The\s+(.+?)\s+field is required/i);
        if (!match) return message;

        return `${friendlyLabelForError(match[1])} is required.`;
    }

    function chunkArray(items, size) {
        const result = [];
        for (let index = 0; index < items.length; index += size) {
            result.push(items.slice(index, index + size));
        }
        return result;
    }

    function todayDateValue() {
        return new Date().toISOString().slice(0, 10);
    }

    function currentTimeValue() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    function generateSupplierCode() {
        const stamp = new Date().toISOString().replace(/[-:TZ.]/g, '').slice(0, 14);
        return `SUP-${stamp}`;
    }

    function getModuleRecordPrefix(moduleKey) {
        const prefixMap = {
            supplier: 'SUP',
            service: 'SRV',
            product: 'PRD',
            chart_account: 'COA',
            bank_account: 'BA',
            pr: 'PR',
            po: 'PO',
            ca: 'CA',
            lr: 'LR',
            err: 'ERR',
            dv: 'DV',
            pda: 'PDA',
            crf: 'CRF',
            ibtf: 'IBTF',
            arf: 'ARF',
        };

        return prefixMap[moduleKey] || String(moduleKey || 'FIN').toUpperCase();
    }

    function generateModuleRecordNumber(moduleKey) {
        const prefix = getModuleRecordPrefix(moduleKey);
        const suffix = String(Math.floor(10000 + Math.random() * 90000));
        return `${prefix}-${suffix}`;
    }

    function generateDefaultRecordTitle(moduleKey, record = null) {
        if (record?.record_title) {
            return record.record_title;
        }

        const moduleConfig = getModuleConfig(moduleKey);
        return moduleConfig.recordTitleLabel || moduleConfig.label || 'Finance Record';
    }

    function generateFinanceBarcodeSvg(value) {
        const text = String(value || '').trim();
        if (!text) return '';

        const normalized = text.replace(/\s+/g, '').toUpperCase();
        if (!normalized) return '';

        let seed = 0;
        Array.from(normalized).forEach((char, index) => {
            seed += char.charCodeAt(0) * (index + 3);
        });

        let bits = '1010';
        Array.from(normalized).forEach((char) => {
            const code = char.charCodeAt(0) ^ (seed & 0xff);
            bits += code.toString(2).padStart(8, '0');
        });
        bits += '110101';

        const unit = 2;
        const quietZone = 10;
        const height = 56;
        let cursor = quietZone;
        let current = bits[0] || '0';
        let runLength = 0;
        let rects = '';

        const flushRun = () => {
            if (runLength > 0 && current === '1') {
                rects += `<rect x="${cursor}" y="8" width="${runLength * unit}" height="${height}" fill="#111827"></rect>`;
            }
            cursor += runLength * unit;
            runLength = 0;
        };

        Array.from(bits).forEach((bit) => {
            if (bit === current) {
                runLength += 1;
                return;
            }

            flushRun();
            current = bit;
            runLength = 1;
        });

        flushRun();

        const width = Math.max(240, cursor + quietZone);
        const label = escapeHtml(text);
        const centerX = Math.round(width / 2);

        return `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${width} 96" role="img" aria-label="Barcode for ${label}" class="block w-full max-w-full">
                <rect x="0" y="0" width="${width}" height="96" rx="10" fill="#ffffff"></rect>
                <rect x="0" y="0" width="${width}" height="96" rx="10" fill="none" stroke="#e5e7eb"></rect>
                ${rects}
                <text x="${centerX}" y="84" text-anchor="middle" font-family="monospace" font-size="11" fill="#111827">${label}</text>
            </svg>
        `;
    }

    function renderArfAssetTagCard(assetCode, location, serialNumber, barcodeSvg) {
        return `
            <div class="rounded-2xl border border-gray-300 bg-white overflow-hidden shadow-sm">
                <div class="border-b border-gray-300 bg-gray-50 px-4 py-3 text-center">
                    <p class="text-[11px] uppercase tracking-[0.32em] text-gray-500">JK&amp;C INC.</p>
                    <h5 class="mt-1 text-2xl font-black tracking-[0.22em] text-gray-900">ASSET TAG</h5>
                </div>
                <div class="grid grid-cols-[140px_minmax(0,1fr)] divide-x divide-gray-300">
                    <div class="border-b border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">Asset Code</div>
                    <div class="border-b border-gray-300 px-4 py-3 text-sm font-semibold text-gray-900 break-words">${escapeHtml(assetCode || 'N/A')}</div>
                    <div class="border-b border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">Location</div>
                    <div class="border-b border-gray-300 px-4 py-3 text-sm font-semibold text-gray-900 break-words">${escapeHtml(location || 'N/A')}</div>
                    <div class="border-b border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">Serial Number</div>
                    <div class="border-b border-gray-300 px-4 py-3 text-sm font-semibold text-gray-900 break-words">${escapeHtml(serialNumber || 'N/A')}</div>
                    <div class="bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">Barcode</div>
                    <div class="px-4 py-3">
                        <div class="rounded-xl border border-gray-200 bg-white px-2 py-2 overflow-hidden">
                            ${barcodeSvg || '<div class="flex h-20 items-center justify-center text-xs text-gray-400">Enter an asset code to generate the barcode.</div>'}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function setReadonlyState(input, readOnly = false) {
        if (!input) return;
        input.readOnly = readOnly;
        input.classList.toggle('bg-gray-100', readOnly);
        input.classList.toggle('cursor-not-allowed', readOnly);
    }

    function setRecordNumberLocked(locked = true) {
        const input = $('recordNumberInput');
        const button = $('recordNumberEditButton');
        if (!input) return;

        setReadonlyState(input, locked);
        if (button) {
            button.textContent = locked ? 'Edit' : 'Lock';
            button.setAttribute('aria-pressed', locked ? 'false' : 'true');
        }
    }

    function toggleRecordNumberEditMode() {
        const input = $('recordNumberInput');
        if (!input) return;

        const isLocked = input.readOnly;
        setRecordNumberLocked(!isLocked);
        if (isLocked) {
            input.focus();
            input.select();
        }
    }

    function showFinanceToast(message, type = 'info') {
        const host = $('financeToastStack');
        if (!host) {
            alert(message);
            return;
        }

        const palette = {
            info: 'border-sky-200 bg-sky-50 text-sky-800',
            success: 'border-green-200 bg-green-50 text-green-800',
            warning: 'border-amber-200 bg-amber-50 text-amber-800',
            error: 'border-red-200 bg-red-50 text-red-800',
        };

        const toast = document.createElement('div');
        toast.className = `pointer-events-auto min-w-[280px] max-w-[360px] rounded-xl border px-4 py-3 shadow-lg backdrop-blur ${palette[type] || palette.info}`;
        toast.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm leading-5 whitespace-pre-line">${escapeHtml(message)}</p>
                <button type="button" class="text-current/60 hover:text-current font-semibold" aria-label="Dismiss">&times;</button>
            </div>
        `;

        const close = () => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 180);
        };

        toast.querySelector('button')?.addEventListener('click', close);
        host.appendChild(toast);
        setTimeout(close, 4200);
    }

    let supplierCompletionMode = 'complete_internally';
    let pendingLiquidationBranchDraft = null;

    function isSupplierModule() {
        return currentModuleKey === 'supplier';
    }

    function isSendToSupplierMode() {
        return isSupplierModule() && supplierCompletionMode === 'send_to_supplier';
    }

    function renderSupplierModeTabs() {
        const target = $('supplierModeTabs');
        if (!target) return;

        if (!isSupplierModule()) {
            target.classList.add('hidden');
            target.innerHTML = '';
            return;
        }

        target.classList.remove('hidden');

        const activeComplete = supplierCompletionMode === 'complete_internally';
        const activeSend = supplierCompletionMode === 'send_to_supplier';

        target.innerHTML = `
            <div class="flex gap-2 rounded-xl border border-gray-200 bg-gray-50 p-1">
                <button type="button" onclick="window.financeModule.changeSupplierCompletionMode('complete_internally')"
                    class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition ${activeComplete ? 'bg-white text-blue-700 shadow-sm border border-blue-100' : 'text-gray-600 hover:text-gray-900'}">
                    Complete Internally
                </button>
                <button type="button" onclick="window.financeModule.changeSupplierCompletionMode('send_to_supplier')"
                    class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition ${activeSend ? 'bg-white text-blue-700 shadow-sm border border-blue-100' : 'text-gray-600 hover:text-gray-900'}">
                    Send to Supplier
                </button>
            </div>
            <p class="mt-2 text-xs text-gray-500">
                ${activeSend
                    ? 'Send a completion form to the supplier email address.'
                    : 'Fill out the supplier details internally and save the record.'}
            </p>
        `;
    }

    function setSupplierFormLayout() {
        const isSend = isSendToSupplierMode();
        [
            'recordCoreFields',
            'recordMetaFields',
            'statusField',
        ].forEach((id) => {
            const el = $(id);
            if (el) {
                el.classList.toggle('hidden', isSend);
            }
        });

        const dynamicFields = $('dynamicFields');
        if (dynamicFields) {
            dynamicFields.classList.toggle('grid', !isSend);
            dynamicFields.classList.toggle('md:grid-cols-2', !isSend);
            dynamicFields.classList.toggle('gap-4', !isSend);
        }
    }

    const financeModules = {
        supplier: {
            label: 'Supplier',
            addLabel: 'Add Supplier',
            recordNumberLabel: 'Supplier Code / ID',
            recordTitleLabel: 'Supplier Name',
            recordDateLabel: 'Created Date',
            summaryKeys: ['business_name', 'representative_full_name', 'email_address', 'completion_mode'],
            fields: [
                selectField('completion_mode', 'Completion Mode', {
                    options: [
                        { value: 'complete_internally', label: 'Complete Internally' },
                        { value: 'send_to_supplier', label: 'Send to Supplier' },
                    ],
                    required: true,
                }),
                textField('trade_name', 'Trade Name'),
                textField('supplier_type', 'Supplier Type'),
                textField('representative_full_name', 'Representative Full Name', { required: true }),
                textField('designation', 'Designation'),
                textField('email_address', 'Email Address', { inputType: 'email', required: true }),
                textField('phone_number', 'Phone Number', { required: true }),
                textField('alternate_contact_number', 'Alternate Contact Number'),
                textareaField('business_address', 'Business Address'),
                textareaField('billing_address', 'Billing Address'),
                textField('tin', 'TIN'),
                selectField('vat_status', 'VAT / Non-VAT', {
                    options: [
                        { value: 'VAT', label: 'VAT' },
                        { value: 'Non-VAT', label: 'Non-VAT' },
                    ],
                }),
                textField('payment_terms', 'Payment Terms'),
                selectField('accreditation_status', 'Accreditation Status', {
                    options: [
                        { value: 'Pending', label: 'Pending' },
                        { value: 'For Accreditation', label: 'For Accreditation' },
                        { value: 'Accredited', label: 'Accredited' },
                        { value: 'Blacklisted', label: 'Blacklisted' },
                    ],
                }),
                textField('bank_name', 'Bank Name'),
                textField('bank_account_name', 'Bank Account Name'),
                textField('bank_account_number', 'Bank Account Number'),
                textareaField('remarks', 'Remarks'),
            ],
        },
        service: {
            label: 'Service',
            addLabel: 'Add Service',
            recordNumberLabel: 'Service Code / Item Code',
            recordTitleLabel: 'Service Name',
            recordDateLabel: 'Date',
            summaryKeys: ['supplier_id', 'coa_id', 'default_cost', 'category'],
            fields: [
                textareaField('service_description', 'Service Description'),
                textareaField('products_services_provided', 'Products / Services Provided'),
                selectField('supplier_id', 'Supplier', { source: 'supplier', required: true }),
                selectField('coa_id', 'Account', { source: 'chart_account', required: true }),
                textField('category', 'Category'),
                selectField('unit_of_measure', 'Unit of Measure', {
                    options: [
                        { value: 'per hour', label: 'per hour' },
                        { value: 'per day', label: 'per day' },
                        { value: 'per month', label: 'per month' },
                        { value: 'per service/job', label: 'per service/job' },
                        { value: 'per unit', label: 'per unit' },
                        { value: 'per session', label: 'per session' },
                        { value: 'per contract', label: 'per contract' },
                    ],
                }),
                numberField('default_cost', 'Default Cost'),
                selectField('tax_type', 'Tax Type', {
                    options: [
                        { value: 'VAT', label: 'VAT' },
                        { value: 'Non-VAT', label: 'Non-VAT' },
                        { value: 'Zero-Rated', label: 'Zero-Rated' },
                    ],
                }),
                selectField('service_status', 'Service Status', {
                    options: [
                        { value: 'Active', label: 'Active' },
                        { value: 'Inactive', label: 'Inactive' },
                    ],
                }),
                textareaField('remarks', 'Remarks'),
            ],
        },
        product: {
            label: 'Product',
            addLabel: 'Add Product',
            recordNumberLabel: 'Product Code / Item Code',
            recordTitleLabel: 'Product Name',
            recordDateLabel: 'Date',
            summaryKeys: ['supplier_id', 'coa_id', 'default_cost', 'category'],
            fields: [
                textareaField('product_description', 'Product Description'),
                selectField('supplier_id', 'Supplier', { source: 'supplier', required: true }),
                selectField('coa_id', 'Account', { source: 'chart_account', required: true }),
                textField('category', 'Category'),
                selectField('unit_of_measure', 'Unit of Measure', {
                    options: [
                        { value: 'per hour', label: 'per hour' },
                        { value: 'per day', label: 'per day' },
                        { value: 'per month', label: 'per month' },
                        { value: 'per service/job', label: 'per service/job' },
                        { value: 'per unit', label: 'per unit' },
                        { value: 'per session', label: 'per session' },
                        { value: 'per contract', label: 'per contract' },
                    ],
                }),
                numberField('default_cost', 'Default Cost'),
                selectField('tax_type', 'Tax Type', {
                    options: [
                        { value: 'VAT', label: 'VAT' },
                        { value: 'Non-VAT', label: 'Non-VAT' },
                        { value: 'Zero-Rated', label: 'Zero-Rated' },
                    ],
                }),
                selectField('product_status', 'Product Status', {
                    options: [
                        { value: 'Active', label: 'Active' },
                        { value: 'Inactive', label: 'Inactive' },
                    ],
                }),
                textareaField('remarks', 'Remarks'),
            ],
        },
        chart_account: {
            label: 'Chart of Accounts',
            addLabel: 'Add Chart of Account',
            recordNumberLabel: 'Account Code',
            recordTitleLabel: 'Account Name',
            recordDateLabel: 'Date Created',
            summaryKeys: ['account_type', 'account_group', 'parent_account_id', 'normal_balance'],
            fields: [
                textareaField('account_description', 'Account Description'),
                checkboxField('is_sub_account', 'Sub-Account'),
                selectField('parent_account_id', 'Main Account', { source: 'chart_account', dependsOnCheckbox: 'is_sub_account' }),
                selectField('account_type', 'Account Type', {
                    options: [
                        { value: 'Asset', label: 'Asset' },
                        { value: 'Liability', label: 'Liability' },
                        { value: 'Equity', label: 'Equity' },
                        { value: 'Income', label: 'Income' },
                        { value: 'Expense', label: 'Expense' },
                        { value: 'Contra', label: 'Contra' },
                    ],
                }),
                textField('account_group', 'Account Group'),
                selectField('normal_balance', 'Normal Balance', {
                    options: [
                        { value: 'Debit', label: 'Debit' },
                        { value: 'Credit', label: 'Credit' },
                    ],
                }),
                textField('bank_account_name', 'Bank Account Name'),
                textField('bank_profile', 'Bank Profile'),
                textField('bank_account_number', 'Bank Account Number'),
                selectField('account_status', 'Status', {
                    options: [
                        { value: 'Active', label: 'Active' },
                        { value: 'Inactive', label: 'Inactive' },
                    ],
                }),
                textareaField('remarks', 'Remarks'),
            ],
        },
        bank_account: {
            label: 'Bank Accounts',
            addLabel: 'Add Bank Account',
            recordNumberLabel: 'Account Number',
            recordTitleLabel: 'Bank Account Name',
            recordDateLabel: 'Date',
            summaryKeys: ['bank_name', 'branch', 'currency', 'linked_coa_id'],
            fields: [
                textField('bank_name', 'Bank Name', { required: true }),
                textField('branch', 'Branch'),
                selectField('currency', 'Currency', {
                    options: [
                        { value: 'PHP', label: 'PHP' },
                        { value: 'USD', label: 'USD' },
                        { value: 'EUR', label: 'EUR' },
                        { value: 'Other', label: 'Other' },
                    ],
                }),
                selectField('bank_status', 'Status', {
                    options: [
                        { value: 'Active', label: 'Active' },
                        { value: 'Inactive', label: 'Inactive' },
                    ],
                }),
                selectField('account_type', 'Account Type', {
                    options: [
                        { value: 'Savings', label: 'Savings' },
                        { value: 'Checking', label: 'Checking' },
                        { value: 'Current', label: 'Current' },
                        { value: 'Payroll', label: 'Payroll' },
                        { value: 'Cash', label: 'Cash' },
                    ],
                }),
                { name: 'linked_coa_id', label: 'Linked Chart of Account', type: 'selector', source: 'chart_account', selectorFilter: 'bank_account' },
                textareaField('signatory_notes', 'Signatory Notes'),
                textareaField('remarks', 'Remarks'),
            ],
        },
        pr: {
            label: 'Purchase Request',
            addLabel: 'Add PR',
            recordNumberLabel: 'PR Number',
            recordTitleLabel: 'Request Title',
            recordDateLabel: 'Date',
            summaryKeys: ['requesting_department', 'requestor', 'supplier_id', 'coa_id'],
            fields: [
                textField('requesting_department', 'Department', { required: true }),
                selectField('requester_mode', 'Requester Option', {
                    options: [
                        { value: 'own_request', label: 'Own Request' },
                        { value: 'request_for_another', label: 'Request for Another' },
                    ],
                    required: true,
                }),
                textField('requestor', 'Employee Name', { required: true }),
                selectField('request_type', 'Type', {
                    options: [
                        { value: 'Service', label: 'Service' },
                        { value: 'Product', label: 'Product' },
                    ],
                    required: true,
                }),
                selectField('priority', 'Priority', {
                    options: [
                        { value: 'Normal', label: 'Normal' },
                        { value: 'Urgent', label: 'Urgent' },
                    ],
                }),
                selectField('for_client', 'Is this for a client?', {
                    options: [
                        { value: 'Yes', label: 'Yes' },
                        { value: 'No', label: 'No' },
                    ],
                }),
                checkboxGroupField('pr_reason_categories', 'Reason (tick all that apply)', {
                    options: [
                        { value: 'Office Supplies', label: 'Office Supplies' },
                        { value: 'IT / Hardware', label: 'IT / Hardware' },
                        { value: 'Software / Subscription', label: 'Software / Subscription' },
                        { value: 'Furniture / Fixtures', label: 'Furniture / Fixtures' },
                        { value: 'Marketing / Advertising', label: 'Marketing / Advertising' },
                        { value: 'Professional Services', label: 'Professional Services' },
                        { value: 'Training / Seminar', label: 'Training / Seminar' },
                        { value: 'Maintenance / Repair', label: 'Maintenance / Repair' },
                        { value: 'Replacement (Damaged/Old)', label: 'Replacement (Damaged/Old)' },
                        { value: 'New Hire / Onboarding', label: 'New Hire / Onboarding' },
                        { value: 'Compliance / Regulatory', label: 'Compliance / Regulatory' },
                        { value: 'Client / Project', label: 'Client / Project' },
                        { value: 'Emergency / Urgent Need', label: 'Emergency / Urgent Need' },
                        { value: 'Inventory Replenishment', label: 'Inventory Replenishment' },
                        { value: 'Others', label: 'Others' },
                    ],
                }),
                selectField('supplier_id', 'Supplier', { source: 'supplier' }),
                selectField('new_vendor', 'New Vendor?', {
                    options: [
                        { value: 'Yes', label: 'Yes' },
                        { value: 'No', label: 'No' },
                    ],
                }),
                textField('employee_id', 'Employee ID'),
                textField('employee_email', 'Email', { inputType: 'email' }),
                textField('contact_number', 'Contact #'),
                textField('position', 'Position'),
                textField('superior', 'Superior'),
                textField('superior_email', 'Superior Email', { inputType: 'email' }),
                textField('vendor_id_number', 'Vendor ID Number'),
                textField('vendors_tin', 'Vendors TIN#'),
                textField('company_name', 'Company'),
                textareaField('vendor_address', 'Address'),
                textField('city', 'City'),
                textField('province', 'Province'),
                textField('zip', 'Zip'),
                textField('vendor_phone', 'Phone Number'),
                textField('vendor_email', 'Email', { inputType: 'email' }),
                selectField('master_item_type', 'Item Type', {
                    options: [
                        { value: 'product', label: 'Product' },
                    ],
                }),
                selectField('master_item_id', 'Item Selected', {
                    sourceMap: { product: 'product' },
                    sourceKey: 'master_item_type',
                }),
                textareaField('description_specification', 'Description / Specification'),
                numberField('quantity', 'Quantity'),
                numberField('unit_cost', 'Unit Cost'),
                numberField('estimated_total_cost', 'Estimated Total Cost'),
                numberField('subtotal', 'Subtotal'),
                selectField('discount', 'Discount', {
                    options: [
                        { value: '0%', label: '0%' },
                        { value: '5%', label: '5%' },
                        { value: '10%', label: '10%' },
                        { value: '15%', label: '15%' },
                        { value: '20%', label: '20%' },
                        { value: '25%', label: '25%' },
                        { value: '30%', label: '30%' },
                    ],
                }),
                numberField('discount_amount', 'Discount Amount'),
                numberField('shipping_amount', 'Shipping'),
                selectField('tax_type', 'Tax (VAT/Non-VAT/N/A)', {
                    options: [
                        { value: 'VAT', label: 'VAT' },
                        { value: 'Non-VAT', label: 'Non-VAT' },
                        { value: 'N/A', label: 'N/A' },
                    ],
                }),
                numberField('tax_amount', 'Tax Amount'),
                numberField('wht_amount', 'WHT'),
                numberField('grand_total', 'Grand Total'),
                selectField('coa_id', 'Account', { source: 'chart_account' }),
                dateField('needed_date', 'Needed Date'),
                textareaField('purpose', 'Purpose / Justification'),
                textareaField('remarks', 'Remarks'),
            ],
        },
        po: {
            label: 'Purchase Order',
            addLabel: 'Add PO',
            recordNumberLabel: 'PO Number',
            recordTitleLabel: 'Order Title',
            recordDateLabel: 'Date',
            summaryKeys: ['linked_pr_id', 'supplier_id', 'total_amount', 'expected_delivery_date'],
            fields: [
                selectField('linked_pr_id', 'Linked PR', { source: 'pr' }),
                selectField('supplier_id', 'Supplier', { source: 'supplier', required: true }),
                textareaField('delivery_address', 'Delivery Address'),
                textareaField('terms_and_conditions', 'Terms and Conditions'),
                selectField('linked_item_type', 'Items / Services Type', {
                    options: [
                        { value: 'service', label: 'Service' },
                        { value: 'product', label: 'Product' },
                    ],
                }),
                selectField('linked_item_id', 'Items / Services', {
                    sourceMap: { service: 'service', product: 'product' },
                    sourceKey: 'linked_item_type',
                }),
                numberField('quantity', 'Quantity'),
                numberField('unit_cost', 'Unit Cost'),
                numberField('total_amount', 'Total Amount'),
                selectField('coa_id', 'Account', { source: 'chart_account' }),
                dateField('expected_delivery_date', 'Expected Delivery Date'),
                textareaField('remarks', 'Remarks'),
            ],
        },
        ca: {
            label: 'Cash Advance',
            addLabel: 'Add CA',
            recordNumberLabel: 'CA Number',
            recordTitleLabel: 'Requestor',
            recordDateLabel: 'Date',
            summaryKeys: ['requestor', 'cash_advance_type', 'amount_requested', 'mode_of_release'],
            fields: [
                selectField('requester_mode', 'Requester Option', {
                    options: [
                        { value: 'own_request', label: 'Own Request' },
                        { value: 'request_for_another', label: 'Request for Another' },
                    ],
                    required: true,
                }),
                textField('requestor', 'Requestor', { required: true }),
                textField('employee_id', 'Employee ID'),
                textField('employee_name', 'Employee Name'),
                textField('employee_email', 'Email', { inputType: 'email' }),
                textField('contact_number', 'Contact #'),
                textField('position', 'Position'),
                textField('department', 'Department'),
                textField('superior', 'Superior'),
                textField('superior_email', 'Superior Email', { inputType: 'email' }),
                dateField('needed_date', 'Date Needed'),
                selectField('priority', 'Priority', {
                    options: [
                        { value: 'Normal', label: 'Normal' },
                        { value: 'Urgent', label: 'Urgent' },
                        { value: 'High', label: 'High' },
                    ],
                }),
                radioGroupField('cash_advance_type', 'Cash Advance Type', {
                    options: [
                        { value: 'Employee Cash Advance (Personal - Payroll Deductible)', label: 'Employee Cash Advance (Personal - Payroll Deductible)' },
                        { value: 'Business Travel', label: 'Business Travel' },
                        { value: 'Project / Site Operations', label: 'Project / Site Operations' },
                        { value: 'Client Entertainment / Representation', label: 'Client Entertainment / Representation' },
                        { value: 'Operational Expenses', label: 'Operational Expenses' },
                        { value: 'Petty Cash Replenishment', label: 'Petty Cash Replenishment' },
                        { value: 'Emergency / Urgent Business Need', label: 'Emergency / Urgent Business Need' },
                        { value: 'Other Business Purpose', label: 'Other Business Purpose' },
                    ],
                }),
                textField('other_business_purpose_specify', 'Other Business Purpose - Specify', { fullWidth: true }),
                checkboxGroupField('usage_categories', 'Cash Advance Usage / Expense Categories', {
                    options: [
                        { value: 'Transportation / Fuel', label: 'Transportation / Fuel' },
                        { value: 'Meals / Per Diem', label: 'Meals / Per Diem' },
                        { value: 'Lodging / Accommodation', label: 'Lodging / Accommodation' },
                        { value: 'Registration / Conference / Fees', label: 'Registration / Conference / Fees' },
                        { value: 'Office Supplies / Minor Purchases', label: 'Office Supplies / Minor Purchases' },
                        { value: 'Materials / Tools', label: 'Materials / Tools' },
                        { value: 'Communication / Internet / Mobile', label: 'Communication / Internet / Mobile' },
                        { value: 'Site-Related Expenses', label: 'Site-Related Expenses' },
                        { value: 'Miscellaneous Business Expenses', label: 'Miscellaneous Business Expenses' },
                        { value: 'Other Expense', label: 'Other Expense' },
                    ],
                }),
                textField('other_expense_specify', 'Other Expense - Specify', { fullWidth: true }),
                textareaField('purpose', 'Justification / Business Need', { required: true, fullWidth: true }),
                selectField('for_client', 'For Client?', {
                    options: [
                        { value: 'No', label: 'No' },
                        { value: 'Yes', label: 'Yes' },
                    ],
                }),
                textField('client_names', 'Client Name(s)', { fullWidth: true }),
                numberField('amount_requested', 'Amount Requested', { required: true }),
                selectField('release_schedule', 'Release Schedule', {
                    options: [
                        { value: 'Full Release', label: 'Full Release' },
                        { value: 'Staggered Release', label: 'Staggered Release' },
                    ],
                }),
                numberField('release_count', 'Number of Releases', { help: 'Used to compute Amount per Release.' }),
                numberField('amount_per_release', 'Amount per Release', { readOnly: true }),
                calculationField('liquidation_calculation', 'Liquidation Calculation', {
                    fullWidth: true,
                    help: 'Shows how the cash advance release is computed and how the liquidation balance will be read in the liquidation report.',
                }),
                dateField('cash_release_date', 'Cash Release Date'),
                timeField('cash_release_time', 'Cash Release Time'),
                selectField('mode_of_release', 'Mode of Release', {
                    options: [
                        { value: 'Cash', label: 'Cash' },
                        { value: 'Bank Transfer', label: 'Bank Transfer' },
                        { value: 'Check', label: 'Check' },
                    ],
                }),
                selectField('paid_through', 'Paid Through', { source: 'chart_account', placeholder: 'Select chart of account' }),
                checkboxField('official_business_cash_advance', 'Official Business Cash Advance', { fullWidth: true, help: 'I acknowledge that this cash advance is granted for official company-related purposes and that liquidation is required within three (3) business days from receipt of the cash advance.' }),
                checkboxField('employee_cash_advance_personal', 'Employee Cash Advance - Personal Purpose', { fullWidth: true, help: 'I acknowledge that this cash advance is granted for personal use, is not subject to liquidation, and shall be recovered through payroll deduction in accordance with the approved schedule.' }),
                checkboxField('liquidation_non_compliance', 'Liquidation Non-Compliance', { fullWidth: true, help: 'I understand that failure to liquidate an Official Business Cash Advance within three (3) business days constitutes non-compliance with Company policy.' }),
                checkboxField('automatic_salary_deduction_authorization', 'Automatic Salary Deduction Authorization', { fullWidth: true, help: 'I authorize the Company to automatically deduct from my salary and/or any amounts due to me the outstanding balance of any unliquidated Official Business Cash Advance.' }),
                checkboxField('final_pay_deduction_authorization', 'Final Pay Deduction Authorization', { fullWidth: true, help: 'I authorize the Company to deduct any remaining cash advance balance from my final pay in the event of resignation, termination, or separation from the Company.' }),
                checkboxField('policy_acknowledgment', 'Policy Acknowledgment', { fullWidth: true, help: 'I confirm that I have read, understood, and agree to comply with the Company’s Cash Advance Policy.' }),
                textareaField('remarks', 'Remarks'),
            ],
        },
        lr: {
            label: 'Liquidation Report',
            addLabel: 'Add LR',
            recordNumberLabel: 'LR Number',
            recordTitleLabel: 'Liquidating Person',
            recordDateLabel: 'Date',
            summaryKeys: ['linked_ca_id', 'total_cash_advance', 'variance_indicator', 'grand_total'],
            fields: [
                selectField('requester_mode', 'Requester Option', {
                    options: [
                        { value: 'own_request', label: 'Own Request' },
                        { value: 'request_for_another', label: 'Request for Another' },
                    ],
                    required: true,
                }),
                selectField('linked_ca_id', 'CA Reference No.', { source: 'ca', required: true }),
                numberField('total_cash_advance', 'CA Amount', { required: true }),
                textareaField('purpose', 'Justification / Business Need', { required: true, fullWidth: true }),
                textField('employee_id', 'Employee ID'),
                textField('employee_name', 'Employee Name'),
                textField('employee_email', 'Email'),
                textField('contact_number', 'Contact #'),
                textField('position', 'Position'),
                textField('department', 'Department'),
                textField('superior', 'Superior'),
                textField('superior_email', 'Superior Email'),
                textField('for_client', 'For Client?', { readOnly: true }),
                textField('client_names', 'Client Name(s)', { readOnly: true, fullWidth: true }),
            ],
        },
        err: {
            label: 'Expense Reimbursement Request',
            addLabel: 'Add ERR',
            recordNumberLabel: 'ERR Number',
            recordTitleLabel: 'Requestor',
            recordDateLabel: 'Date',
            summaryKeys: ['linked_lr_id', 'amount', 'supplier_id', 'bank_account_id'],
            fields: [
                selectField('requester_mode', 'Requester Option', {
                    options: [
                        { value: 'own_request', label: 'Own Request' },
                        { value: 'request_for_another', label: 'Request for Another' },
                    ],
                    required: true,
                }),
                textField('requestor', 'Requestor', { required: true }),
                selectField('linked_lr_id', 'Linked LR', { source: 'lr_shortage', required: true }),
                textareaField('expense_details', 'Expense Details'),
                numberField('amount', 'Amount', { required: true }),
                textField('reimbursement_payment_details', 'Reimbursement Payment Details', { fullWidth: true }),
                checkboxField('manual_liquidation_entry', 'Manually edit reimbursement details', { fullWidth: true, help: 'Use this when the linked liquidation needs to be reviewed or entered again manually.' }),
                selectField('supplier_id', 'Supplier', { source: 'supplier' }),
                selectField('coa_id', 'Account from Chart of Accounts', { source: 'chart_account' }),
                selectField('reimbursement_mode', 'Mode of Reimbursement', {
                    options: [
                        { value: 'Cash', label: 'Cash' },
                        { value: 'Bank Transfer', label: 'Bank Transfer' },
                        { value: 'Check', label: 'Check' },
                    ],
                }),
                selectField('bank_account_id', 'Bank Account', { source: 'bank_account' }),
                textareaField('remarks', 'Remarks'),
            ],
        },
        dv: {
            label: 'Disbursement Voucher',
            addLabel: 'Add DV',
            recordNumberLabel: 'DV Number',
            recordTitleLabel: 'Payee',
            recordDateLabel: 'Date',
            summaryKeys: ['source_document_type', 'source_document_id', 'amount', 'disbursement_type', 'bank_account_id'],
            fields: [
                selectField('source_document_type', 'Linked Source Document Type', {
                    options: [
                        { value: 'ca', label: 'CA' },
                        { value: 'lr', label: 'LR' },
                        { value: 'err', label: 'ERR' },
                        { value: 'pda', label: 'PDA' },
                        { value: 'crf', label: 'CRF' },
                        { value: 'ibtf', label: 'IBTF' },
                        { value: 'arf', label: 'ARF' },
                    ],
                }),
                selectField('source_document_id', 'Linked Source Document', {
                    sourceMap: {
                        pr: 'pr',
                        po: 'po',
                        ca: 'ca',
                        lr: 'lr',
                        err: 'err',
                        pda: 'pda',
                        crf: 'crf',
                        ibtf: 'ibtf',
                        arf: 'arf',
                    },
                    sourceKey: 'source_document_type',
                }),
                selectField('supplier_id', 'Supplier', { source: 'supplier' }),
                numberField('amount', 'Amount', { required: true }),
                selectField('payment_type', 'Payment Type', {
                    options: [
                        { value: 'Cash', label: 'Cash' },
                        { value: 'Check', label: 'Check' },
                        { value: 'Bank Transfer', label: 'Bank Transfer' },
                        { value: 'E-Wallet', label: 'E-Wallet' },
                    ],
                }),
                selectField('disbursement_type', 'Disbursement Type', {
                    options: [
                        { value: 'Cash', label: 'Cash' },
                        { value: 'Check', label: 'Check' },
                        { value: 'Bank Transfer', label: 'Bank Transfer' },
                        { value: 'Petty Cash', label: 'Petty Cash' },
                    ],
                }),
                selectField('bank_account_id', 'Bank Account', { source: 'bank_account' }),
                selectField('coa_id', 'Account', { source: 'chart_account' }),
                textField('fund_source', 'Fund Source / Project'),
                textField('department', 'Department'),
                textField('reference_number', 'Reference Number'),
                textareaField('purpose', 'Purpose'),
                dateField('payment_date', 'Payment Date'),
                dateField('due_date', 'Due Date'),
                numberField('withholding_tax', 'Withholding Tax (EWT)'),
                numberField('vat_amount', 'VAT'),
                numberField('net_amount', 'Net Amount', { readOnly: true }),
                textField('currency', 'Currency'),
                numberField('exchange_rate', 'Exchange Rate'),
                textField('prepared_by', 'Prepared By'),
                textField('checked_by', 'Verified / Checked By'),
                textField('approved_by_name', 'Approved By'),
                textField('received_by_name', 'Received By'),
                textField('received_by_signature', 'Signature'),
                dateField('date_received', 'Date Received'),
                textareaField('remarks', 'Remarks'),
            ],
        },
        pda: {
            label: 'Payroll Disbursement Authorization',
            addLabel: 'Add PDA',
            recordNumberLabel: 'PDA Number',
            recordTitleLabel: 'Payroll Period',
            recordDateLabel: 'Date',
            summaryKeys: ['total_payroll_amount', 'department', 'funding_bank_account_id', 'payroll_expense_coa_id'],
            fields: [
                numberField('total_payroll_amount', 'Total Payroll Amount', { required: true }),
                textField('department', 'Department / Coverage'),
                selectField('funding_bank_account_id', 'Funding Bank Account', { source: 'bank_account' }),
                selectField('payroll_expense_coa_id', 'Payroll Expense Account', { source: 'chart_account' }),
                textareaField('supporting_payroll_summary', 'Supporting Payroll Summary'),
                textareaField('remarks', 'Remarks'),
            ],
        },
        crf: {
            label: 'Cash Return Form',
            addLabel: 'Add CRF',
            recordNumberLabel: 'CRF Number',
            recordTitleLabel: 'Returnee',
            recordDateLabel: 'Date',
            summaryKeys: ['linked_lr_id', 'amount_returned', 'receiving_bank_account_id', 'coa_id'],
            fields: [
                selectField('requester_mode', 'Requester Option', {
                    options: [
                        { value: 'own_request', label: 'Own Request' },
                        { value: 'request_for_another', label: 'Request for Another' },
                    ],
                    required: true,
                }),
                textField('requestor', 'Returnee', { required: true }),
                selectField('linked_lr_id', 'Linked LR', { source: 'lr_overage', required: true }),
                numberField('amount_returned', 'Amount Returned', { required: true }),
                checkboxField('manual_liquidation_entry', 'Manually edit return details', { fullWidth: true, help: 'Use this when the linked liquidation needs to be reviewed or entered again manually.' }),
                selectField('mode_of_return', 'Mode of Return', {
                    options: [
                        { value: 'Cash', label: 'Cash' },
                        { value: 'Bank Transfer', label: 'Bank Transfer' },
                        { value: 'Check', label: 'Check' },
                    ],
                }),
                selectField('receiving_bank_account_id', 'Receiving Bank / Cash Account', { source: 'bank_account' }),
                selectField('coa_id', 'Account from Chart of Accounts', { source: 'chart_account' }),
                textField('reference_number', 'Reference Number'),
                textareaField('remarks', 'Remarks'),
            ],
        },
        ibtf: {
            label: 'Interbank Fund Transfer Form',
            addLabel: 'Add IBTF',
            recordNumberLabel: 'IBTF Number',
            recordTitleLabel: 'Transfer Title',
            recordDateLabel: 'Date',
            summaryKeys: ['source_bank_account_id', 'destination_bank_account_id', 'amount', 'transfer_reference_number'],
            fields: [
                selectField('source_bank_account_id', 'Source Bank Account', { source: 'bank_account', required: true }),
                selectField('destination_bank_account_id', 'Destination Bank Account', { source: 'bank_account', required: true }),
                numberField('amount', 'Amount', { required: true }),
                textareaField('reason', 'Reason / Purpose'),
                textField('source_account_code', 'Source Account Code'),
                textField('destination_account_code', 'Destination Account Code'),
                textField('transfer_reference_number', 'Transfer Reference Number'),
                textareaField('remarks', 'Remarks'),
            ],
        },
        arf: {
            label: 'Asset Registration Form',
            addLabel: 'Add ARF',
            recordNumberLabel: 'ARF Number',
            recordTitleLabel: 'Asset Name',
            recordDateLabel: 'Date',
            summaryKeys: ['asset_code', 'linked_po_id', 'linked_dv_id', 'acquisition_cost', 'asset_coa_id'],
            fields: [
                selectField('linked_po_id', 'Linked PO', { source: 'po' }),
                selectField('linked_dv_id', 'Linked DV', { source: 'dv' }),
                textField('asset_code', 'Asset Code', { required: true }),
                textareaField('asset_description', 'Asset Description'),
                textField('asset_category', 'Asset Category'),
                textField('serial_number', 'Serial Number'),
                textField('model', 'Model'),
                selectField('supplier_id', 'Supplier', { source: 'supplier' }),
                numberField('acquisition_cost', 'Acquisition Cost'),
                dateField('acquisition_date', 'Acquisition Date'),
                selectField('asset_coa_id', 'Asset Account from Chart of Accounts', { source: 'chart_account' }),
                textField('location', 'Location'),
                textField('custodian', 'Custodian'),
                textField('useful_life', 'Useful Life'),
                numberField('residual_value', 'Residual Value'),
                textareaField('remarks', 'Remarks'),
            ],
        },
    };

    const moduleKeys = Object.keys(financeModules);
    let financeRecords = Array.isArray(bootstrap.records) ? bootstrap.records.slice() : [];
    let financeSourceRecords = Array.isArray(bootstrap.sourceRecords) ? bootstrap.sourceRecords.slice() : [];
    let financeLookupOptions = bootstrap.lookupOptions || {};
    let currentModuleKey = financeModules[bootstrap.currentModule] ? bootstrap.currentModule : 'supplier';
    let currentWorkflowFilter = workflowFilters.includes(bootstrap.currentWorkflowFilter) ? bootstrap.currentWorkflowFilter : 'all';
    let currentPreviewRecord = null;
    let currentPreviewTab = 'details';
    let currentPreviewAttachmentUrl = '';
    let currentPreviewPdfObjectUrl = '';
    let currentPreviewPdfGeneration = 0;
    let currentPreviewRefreshTimer = null;
    let currentEditRecordId = null;
    let activeLookupSelector = null;
    let activeBankAccountLookupQuery = '';
    let financeDraftContext = null;

    const $ = (id) => document.getElementById(id);

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatCurrency(value) {
        if (value === null || value === undefined || value === '') return 'N/A';
        const num = Number(value);
        if (Number.isNaN(num)) return String(value);
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getCashAdvanceLiquidationCalculation(values = {}) {
        const rawAmount = values['data[amount_requested]'] ?? values.amount_requested ?? 0;
        const amount = Number(rawAmount) || 0;
        const rawCount = values['data[release_count]'] ?? values.release_count ?? 1;
        const releaseCount = Math.max(parseInt(rawCount || '1', 10) || 1, 1);
        const amountPerRelease = amount / releaseCount;
        const releaseLabel = releaseCount === 1 ? 'release' : 'releases';

        return `CA ${formatCurrency(amount)} / ${releaseCount} ${releaseLabel} = ${formatCurrency(amountPerRelease)} per release. LR: CA - actual expenses = balance.`;
    }

    function formatDate(value) {
        return value || 'N/A';
    }

    function getModuleConfig(moduleKey) {
        return financeModules[moduleKey] || financeModules.supplier;
    }

    function shouldShowGenericAmount(recordOrModuleKey) {
        const moduleKey = typeof recordOrModuleKey === 'string'
            ? recordOrModuleKey
            : recordOrModuleKey?.module_key;
        const config = getModuleConfig(moduleKey);

        return (config.fields || []).some((field) => field.name === 'amount' && field.required);
    }

    function getLookupLabel(moduleKey, id) {
        const options = financeLookupOptions[moduleKey] || [];
        const match = options.find((item) => String(item.id) === String(id));
        return match ? match.label : '';
    }

    function getRecordByLookupValue(moduleKey, value) {
        const normalizedValue = String(value || '').trim();
        if (!normalizedValue) return null;

        const sourcePool = financeSourceRecords.length ? financeSourceRecords : financeRecords;

        return sourcePool.find((record) => {
            if (moduleKey && record.module_key !== moduleKey) {
                return false;
            }

            return [
                record.id,
                record.record_number,
                record.record_title,
                record.display_label,
            ].some((candidate) => String(candidate || '').trim() === normalizedValue);
        }) || null;
    }

    function getFieldValue(record, fieldName) {
        if (!record) return '';
        if (Object.prototype.hasOwnProperty.call(record, fieldName)) {
            return record[fieldName];
        }
        return record.data ? record.data[fieldName] : '';
    }

    function buildSummary(record, config) {
        const keys = config.summaryKeys || [];
        const lookupMap = {
            supplier_id: 'supplier',
            coa_id: 'chart_account',
            paid_through: 'chart_account',
            parent_account_id: 'chart_account',
            linked_coa_id: 'chart_account',
            bank_account_id: 'bank_account',
            funding_bank_account_id: 'bank_account',
            receiving_bank_account_id: 'bank_account',
            source_bank_account_id: 'bank_account',
            destination_bank_account_id: 'bank_account',
            linked_pr_id: 'pr',
            linked_po_id: 'po',
            linked_ca_id: 'ca',
            linked_dv_id: 'dv',
            linked_lr_id: 'lr',
            master_item_id: (record.data && record.data.master_item_type) ? record.data.master_item_type : null,
            source_document_id: (record.data && record.data.source_document_type) ? record.data.source_document_type : null,
        };
        const values = keys.map((key) => {
            const value = getFieldValue(record, key);
            if (!value) return '';
            if (key.endsWith('_id')) {
                const lookup = (config.summaryLookupSources && config.summaryLookupSources[key]) || lookupMap[key] || key.replace('_id', '');
                return getLookupLabel(lookup, value) || getLookupLabel('lr_shortage', value) || getLookupLabel('lr_overage', value) || String(value);
            }
            return String(value);
        }).filter(Boolean);

        return values.length ? values.join(' | ') : 'No additional summary available';
    }

    function workflowBadgeClass(value) {
        const v = (value || '').toLowerCase();
        if (v === 'uploaded') return 'text-orange-700';
        if (v === 'shared') return 'text-sky-700';
        if (v === 'submitted') return 'text-blue-700';
        if (v === 'accepted') return 'text-green-700';
        if (v === 'reverted') return 'text-yellow-700';
        if (v === 'archived') return 'text-gray-700';
        return 'text-gray-700';
    }

    function approvalBadgeClass(value) {
        const v = (value || '').toLowerCase();
        if (v === 'approved') return 'text-green-700';
        if (v === 'pending') return 'text-yellow-700';
        if (v === 'pending supplier completion') return 'text-sky-700';
        if (v === 'needs revision') return 'text-red-700';
        if (v === 'archived') return 'text-gray-700';
        return 'text-gray-700';
    }

    function workflowLabel(value) {
        return value || 'Uploaded';
    }

    function approvalLabel(value) {
        return value || 'Pending';
    }

    function previewApprovalLabel(record) {
        if (record.module_key === 'supplier' && dataCompletionMode(record) === 'send_to_supplier') {
            return record.workflow_status === 'Submitted'
                ? (record.approval_status || 'Pending')
                : 'Awaiting supplier response';
        }

        return approvalLabel(record.approval_status);
    }

    function dataCompletionMode(record) {
        return record?.data?.completion_mode || '';
    }

    function isPendingSupplierCompletion(record) {
        if (!record || record.module_key !== 'supplier') {
            return false;
        }

        return false;
    }

    function isPendingSupplierCompletion(record) {
        if (!record || record.module_key !== 'supplier') {
            return false;
        }

        const mode = dataCompletionMode(record);
        return mode === 'send_to_supplier'
            || record.workflow_status === 'Shared'
            || Boolean(record.share_token)
            || Boolean(record.supplier_completion_url);
    }

    function filteredRecords() {
        return financeRecords.filter((record) => {
            if (record.module_key !== currentModuleKey) {
                return false;
            }
            if (currentWorkflowFilter === 'all') {
                return true;
            }
            return (record.workflow_status || 'Uploaded') === currentWorkflowFilter;
        });
    }

    function setStatusMessage() {
        const messageBox = $('statusMessage');
        const moduleLabel = getModuleConfig(currentModuleKey).label;

        if (currentWorkflowFilter === 'all') {
            messageBox.className = 'mt-1 mb-4 border border-blue-200 bg-blue-50 text-blue-700 text-[14px] px-4 py-3 rounded-md';
            messageBox.textContent = `${moduleLabel} records are ready for encoding, review, and submission.`;
            return;
        }

        const messages = {
            Uploaded: 'Draft records are saved locally and ready for submission.',
            Shared: 'These supplier records have been shared for external completion.',
            Submitted: 'These records are submitted and waiting for review.',
            Accepted: 'These records are approved and active for lookup flows.',
            Reverted: 'These records were reverted and can be corrected then resubmitted.',
            Archived: 'These records are archived.',
        };

        const palette = {
            Uploaded: 'border-blue-200 bg-blue-50 text-blue-700',
            Shared: 'border-sky-200 bg-sky-50 text-sky-700',
            Submitted: 'border-yellow-200 bg-yellow-50 text-yellow-700',
            Accepted: 'border-green-200 bg-green-50 text-green-700',
            Reverted: 'border-red-200 bg-red-50 text-red-700',
            Archived: 'border-gray-200 bg-gray-50 text-gray-700',
        };

        const tone = palette[currentWorkflowFilter] || palette.Uploaded;
        messageBox.className = `mt-1 mb-4 border text-[14px] px-4 py-3 rounded-md ${tone}`;
        messageBox.textContent = messages[currentWorkflowFilter] || messages.Uploaded;
    }

    function setActiveModuleTabs() {
        const container = $('moduleTabs');
        const previousScrollLeft = container ? container.scrollLeft : 0;
        const buttons = moduleKeys.map((key) => {
            const active = key === currentModuleKey;
            return `
                <button id="finance-tab-${key}" type="button" onclick="window.financeModule.changeModule('${key}')"
                    style="flex: 0 0 calc((100% - 2rem) / 5); min-width: 150px;"
                    class="snap-start px-4 py-3 text-sm font-medium border rounded-xl whitespace-nowrap text-center transition ${active ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}">
                    ${escapeHtml(financeModules[key].label)}
                </button>
            `;
        });
        container.innerHTML = buttons.join('');
        container.scrollLeft = previousScrollLeft;
    }

    function setActiveWorkflowTabs() {
        const container = $('workflowTabs');
        const tabs = workflowFilters.map((status) => {
            const active = status === currentWorkflowFilter;
            const label = status === 'all' ? 'All' : status;
            return `
                <button type="button" onclick="window.financeModule.changeWorkflow('${status}')"
                    class="px-3 py-2 rounded-full border text-xs whitespace-nowrap transition ${active ? 'bg-gray-900 border-gray-900 text-white' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'}">
                    ${escapeHtml(label)}
                </button>
            `;
        });
        container.innerHTML = tabs.join('');
    }

    function renderTableHeader() {
        $('tableHeadRow').innerHTML = `
            <th class="w-36 p-3 text-left">Number</th>
            <th class="w-44 p-3 text-left">Title</th>
            <th class="p-3 text-left">Summary</th>
            <th class="w-32 p-3 text-left">Date</th>
            <th class="w-36 p-3 text-left">Workflow</th>
            <th class="w-36 p-3 text-left">Approval</th>
            <th class="w-32 p-3 text-left">Actions</th>
        `;
    }

    function renderTableRows() {
        const tableBody = $('tableBody');
        const rows = filteredRecords();
        const moduleConfig = getModuleConfig(currentModuleKey);

        tableBody.innerHTML = '';

        if (!rows.length) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="p-10 text-center text-gray-400 italic">No records found</td>
                </tr>
            `;
            return;
        }

        rows.forEach((item) => {
            tableBody.innerHTML += `
                <tr class="border-t hover:bg-blue-50 cursor-pointer" onclick="window.financeModule.openPreview(${item.id})">
                    <td class="p-3 break-words">${escapeHtml(item.record_number || '')}</td>
                    <td class="p-3 break-words">${escapeHtml(item.record_title || '')}</td>
                    <td class="p-3 text-gray-700">${escapeHtml(buildSummary(item, moduleConfig))}</td>
                    <td class="p-3">${escapeHtml(formatDate(item.record_date))}</td>
                    <td class="p-3 ${workflowBadgeClass(item.workflow_status)} font-medium">${escapeHtml(workflowLabel(item.workflow_status))}</td>
                    <td class="p-3 ${approvalBadgeClass(item.approval_status)} font-medium">${escapeHtml(approvalLabel(item.approval_status))}</td>
                    <td class="p-3">
                        <button type="button" onclick="event.stopPropagation(); window.financeModule.openPreview(${item.id})" class="text-blue-600 hover:underline">
                            View
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    function refreshFinanceView() {
        setActiveModuleTabs();
        setActiveWorkflowTabs();
        setStatusMessage();
        syncFinanceSidebarState();
        renderTableHeader();
        renderTableRows();
        updateAddButton();
    }

    function scrollFinanceModuleTabs(amount) {
        if (!moduleKeys.length) return;

        const container = $('moduleTabs');
        if (!container) return;

        const direction = amount > 0 ? 1 : -1;
        const distance = Math.max(container.clientWidth, 320);
        container.scrollBy({ left: distance * direction, behavior: 'smooth' });
    }

    function updateAddButton() {
        $('addButton').textContent = `+ ${getModuleConfig(currentModuleKey).addLabel}`;
    }

    function syncFinanceSidebarState() {
        const sidebarLinks = document.querySelectorAll('[data-finance-module]');
        sidebarLinks.forEach((link) => {
            const isActive = link.getAttribute('data-finance-module') === currentModuleKey;
            link.classList.toggle('bg-blue-50', isActive);
            link.classList.toggle('text-blue-700', isActive);
            link.classList.toggle('border', isActive);
            link.classList.toggle('border-blue-100', isActive);
            link.classList.toggle('font-semibold', isActive);
            link.classList.toggle('text-gray-700', !isActive);
            link.classList.toggle('hover:bg-gray-100', !isActive);
        });
    }

    function getFieldOptions(field, formValues = {}) {
        if (Array.isArray(field.options) && field.options.length) {
            return field.options;
        }

        if (field.source) {
            return financeLookupOptions[field.source] || [];
        }

        if (field.sourceMap && field.sourceKey) {
            const keyValue = formValues[`data[${field.sourceKey}]`] || formValues[field.sourceKey];
            const mappedModule = field.sourceMap[keyValue];
            return mappedModule ? (financeLookupOptions[mappedModule] || []) : [];
        }

        return [];
    }

    function isBankRelatedChartAccount(option) {
        const haystack = [
            option?.label,
            option?.record_number,
            option?.record_title,
            option?.account_type,
            option?.account_group,
            option?.account_description,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        const accountType = String(option?.account_type || '').toLowerCase();

        return /bank|cash/.test(haystack) || accountType === 'asset' || accountType === 'cash';
    }

    function getLookupSelectorOptions(field, formValues = {}, query = '') {
        const source = field.source;
        if (!source) return [];

        let options = [...(financeLookupOptions[source] || [])];

        if (source === 'chart_account' && field.selectorFilter === 'bank_account') {
            const filtered = options.filter(isBankRelatedChartAccount);
            options = filtered.length ? filtered : options;
        }

        const normalizedQuery = String(query || '').trim().toLowerCase();
        if (normalizedQuery) {
            options = options.filter((option) => {
                const text = [
                    option?.label,
                    option?.record_number,
                    option?.record_title,
                    option?.account_type,
                    option?.account_group,
                    option?.account_description,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();
                return text.includes(normalizedQuery);
            });
        }

        return options;
    }

    function findLinkedLiquidationRecord(caId) {
        const linkedRecords = financeRecords.filter((record) => {
            if (record.module_key !== 'lr') return false;
            return String(record.data?.linked_ca_id || '') === String(caId || '');
        });

        return linkedRecords[0] || null;
    }

    function buildLiquidationBranchDraft(linkedLrRecord) {
        const variance = parseFloat(linkedLrRecord?.data?.variance || '0') || 0;
        const linkedCaLabel = getLookupLabel('ca', linkedLrRecord?.data?.linked_ca_id) || linkedLrRecord?.data?.linked_ca_id || 'N/A';
        const detailsText = Array.isArray(linkedLrRecord?.data?.line_items) && linkedLrRecord.data.line_items.length
            ? linkedLrRecord.data.line_items
                .map((item) => [item.item_id, item.description, item.category].filter(Boolean).join(' - '))
                .filter(Boolean)
                .join(', ')
            : (linkedLrRecord?.data?.expense_line_items || linkedLrRecord?.data?.purpose || linkedLrRecord?.record_title || '');

        if (variance < 0) {
            const amount = Math.abs(variance).toFixed(2);
            return {
                moduleKey: 'err',
                linkedRecord: linkedLrRecord,
                prefill: {
                    linked_lr_id: linkedLrRecord.id,
                    requester_mode: 'own_request',
                    requestor: linkedLrRecord?.data?.employee_name || bootstrap.currentUserName || '',
                    expense_details: detailsText || `Shortage from ${linkedCaLabel}`,
                    amount,
                    supplier_id: linkedLrRecord?.data?.supplier_id || '',
                    coa_id: linkedLrRecord?.data?.coa_id || '',
                    reimbursement_mode: linkedLrRecord?.data?.mode_of_release || 'Bank Transfer',
                    bank_account_id: linkedLrRecord?.data?.bank_account_id || '',
                    reimbursement_payment_details: `Pay reimbursement for shortage amount ${amount} from ${linkedCaLabel}.`,
                    remarks: linkedLrRecord?.data?.remarks || 'Auto-filled from shortage liquidation.',
                },
            };
        }

        if (variance > 0) {
            const amount = Math.abs(variance).toFixed(2);
            return {
                moduleKey: 'crf',
                linkedRecord: linkedLrRecord,
                prefill: {
                    linked_lr_id: linkedLrRecord.id,
                    requester_mode: 'own_request',
                    requestor: linkedLrRecord?.data?.employee_name || bootstrap.currentUserName || '',
                    amount_returned: amount,
                    mode_of_return: linkedLrRecord?.data?.mode_of_return || 'Cash',
                    receiving_bank_account_id: linkedLrRecord?.data?.bank_account_id || '',
                    coa_id: linkedLrRecord?.data?.coa_id || '',
                    reference_number: linkedLrRecord?.record_number || '',
                    remarks: linkedLrRecord?.data?.remarks || 'Auto-filled from overage liquidation.',
                },
            };
        }

        return null;
    }

    function getLinkedLiquidationRecord(linkedLrId, expectedIndicator = '') {
        const record = getRecordById(linkedLrId) || getRecordByLookupValue('lr', linkedLrId);
        if (!record || record.module_key !== 'lr') return null;
        if (expectedIndicator && String(record.data?.variance_indicator || '') !== expectedIndicator) return null;
        return record;
    }

    function getLiquidationBranchPrefill(moduleKey, linkedLrRecord) {
        const branchDraft = buildLiquidationBranchDraft(linkedLrRecord);
        if (!branchDraft || branchDraft.moduleKey !== moduleKey) return {};
        return branchDraft.prefill || {};
    }

    function renderLinkedLiquidationBranchPanel(linkedLrRecord, moduleKey, values = {}) {
        if (!linkedLrRecord) return '';

        const indicator = linkedLrRecord.data?.variance_indicator || 'Balanced';
        const isShortage = indicator === 'Shortage';
        const borderClass = isShortage ? 'border-red-100 bg-red-50/40' : 'border-emerald-100 bg-emerald-50/40';
        const titleClass = isShortage ? 'text-red-700' : 'text-emerald-700';
        const routeLabel = isShortage ? 'ERR' : 'Cash Return';
        const expectedLabel = moduleKey === 'err' ? 'shortage' : 'overage';

        return `
            <div class="md:col-span-2 rounded-xl border ${borderClass} p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] ${titleClass}">Linked Liquidation</h4>
                        <p class="mt-2 text-sm text-gray-700">
                            This LR is marked as <span class="font-semibold">${escapeHtml(indicator)}</span> and routes to <span class="font-semibold">${escapeHtml(routeLabel)}</span>.
                        </p>
                    </div>
                    <span class="rounded-full border border-white/80 bg-white px-3 py-1 text-xs font-semibold text-gray-700">${escapeHtml(expectedLabel)}</span>
                </div>
                <div class="mt-4 space-y-4">
                    ${renderLiquidationReportSection(linkedLrRecord, values)}
                    ${renderLiquidationPreviewTable(linkedLrRecord)}
                    ${renderLiquidationPreviewSummary(linkedLrRecord)}
                </div>
            </div>
        `;
    }

    function syncLiquidationBranchFromSelection(moduleKey, linkedLrId, { preserveManual = false } = {}) {
        const expectedIndicator = moduleKey === 'err' ? 'Shortage' : 'Overage';
        const linkedLrRecord = getLinkedLiquidationRecord(linkedLrId, expectedIndicator);
        if (!linkedLrRecord) return null;

        const prefill = getLiquidationBranchPrefill(moduleKey, linkedLrRecord);
        const form = $('financeForm');
        if (!form) return linkedLrRecord;

        const manualInput = form.querySelector('input[name="data[manual_liquidation_entry]"]');
        const isManual = preserveManual && manualInput?.checked;

        Object.entries(prefill).forEach(([name, value]) => {
            const input = form.querySelector(`[name="data[${name}]"]`);
            if (!input) return;
            if (isManual && ['amount', 'amount_returned', 'expense_details', 'remarks'].includes(name)) return;
            input.value = value ?? '';
        });

        return linkedLrRecord;
    }

    function buildCurrentLiquidationDraft() {
        const form = $('financeForm');
        if (!form || currentModuleKey !== 'lr') return null;

        const data = {};
        new FormData(form).forEach((value, key) => {
            const match = String(key).match(/^data\[([^\]]+)\]$/);
            if (match) {
                data[match[1]] = value;
            }
        });

        data.line_items = Array.isArray(financeFormValues?.line_items) ? financeFormValues.line_items : [];

        return buildLiquidationBranchDraft({
            id: currentEditRecordId || data.linked_lr_id || '',
            record_number: data.record_number || '',
            record_title: data.employee_name || data.requestor || '',
            data,
        });
    }

    function openPendingLiquidationBranch() {
        if (!pendingLiquidationBranchDraft && currentModuleKey === 'lr') {
            pendingLiquidationBranchDraft = buildCurrentLiquidationDraft();
        }

        if (!pendingLiquidationBranchDraft) {
            showFinanceToast('No linked liquidation route is ready yet.', 'warning');
            return;
        }

        const branchDraft = pendingLiquidationBranchDraft;
        pendingLiquidationBranchDraft = null;
        financeDraftContext = branchDraft;
        changeModule(branchDraft.moduleKey);
        requestAnimationFrame(() => {
            openFinanceDrawer();
            showFinanceToast(
                branchDraft.moduleKey === 'err'
                    ? 'Opening ERR with the calculated shortage details.'
                    : 'Opening Cash Return with the calculated overage details.',
                'success'
            );
        });
    }

    function dismissPendingLiquidationBranch() {
        pendingLiquidationBranchDraft = null;
        document.querySelector('[data-pending-liquidation-panel]')?.remove();
        renderDrawerPreview();
        showFinanceToast('Staying on the liquidation report.', 'info');
    }

    function getDraftValue(name, record = null) {
        if (financeDraftContext?.prefill && Object.prototype.hasOwnProperty.call(financeDraftContext.prefill, name)) {
            return financeDraftContext.prefill[name];
        }

        return record ? getModuleFieldValue(record, { name }) : '';
    }

    function getLiquidationStatusMeta(variance) {
        const numericVariance = parseFloat(variance || '0') || 0;
        if (numericVariance > 0) {
            return {
                indicator: 'Overage',
                label: 'Overage',
                tone: 'emerald',
                border: 'border-emerald-200',
                bg: 'bg-emerald-50',
                badge: 'bg-emerald-100 text-emerald-700',
                text: 'text-emerald-700',
                message: 'The CA has excess funds and should route to Cash Return.',
                amountLabel: 'Overage amount',
                amountValue: numericVariance,
            };
        }

        if (numericVariance < 0) {
            return {
                indicator: 'Shortage',
                label: 'Shortage',
                tone: 'red',
                border: 'border-red-200',
                bg: 'bg-red-50',
                badge: 'bg-red-100 text-red-700',
                text: 'text-red-700',
                message: 'Expenses exceeded the CA and should route to ERR.',
                amountLabel: 'Shortage amount',
                amountValue: Math.abs(numericVariance),
            };
        }

        return {
            indicator: 'Balanced',
            label: 'Balanced',
            tone: 'slate',
            border: 'border-slate-200',
            bg: 'bg-slate-50',
            badge: 'bg-slate-100 text-slate-700',
            text: 'text-slate-700',
            message: 'No shortage or overage detected yet.',
            amountLabel: 'Difference',
            amountValue: 0,
        };
    }

    function getDvSourceDocumentFields(sourceType) {
        const fieldsByModule = {
            pr: ['record_number', 'record_title', 'record_date', 'requesting_department', 'requestor', 'supplier_id', 'amount', 'purpose', 'remarks'],
            po: ['record_number', 'record_title', 'record_date', 'linked_pr_id', 'supplier_id', 'amount', 'expected_delivery_date', 'purpose', 'remarks'],
            ca: ['record_number', 'record_title', 'record_date', 'requestor', 'department', 'amount_requested', 'mode_of_release', 'purpose', 'remarks'],
            lr: ['record_number', 'record_title', 'record_date', 'linked_ca_id', 'total_cash_advance', 'actual_expenses', 'variance', 'variance_indicator', 'purpose', 'remarks'],
            err: ['record_number', 'record_title', 'record_date', 'linked_lr_id', 'amount', 'expense_details', 'reimbursement_mode', 'purpose', 'remarks'],
            pda: ['record_number', 'record_title', 'record_date', 'total_payroll_amount', 'department', 'funding_bank_account_id', 'payroll_expense_coa_id', 'remarks'],
            crf: ['record_number', 'record_title', 'record_date', 'linked_lr_id', 'amount_returned', 'mode_of_return', 'remarks'],
            ibtf: ['record_number', 'record_title', 'record_date', 'source_bank_account_id', 'destination_bank_account_id', 'amount', 'reason', 'remarks'],
            arf: ['record_number', 'record_title', 'record_date', 'linked_po_id', 'linked_dv_id', 'asset_description', 'asset_category', 'acquisition_cost', 'acquisition_date', 'remarks'],
        };

        return fieldsByModule[sourceType] || ['record_number', 'record_title', 'record_date', 'amount', 'remarks'];
    }

    function normalizeDvPaymentType(value) {
        const text = String(value || '').trim().toLowerCase();
        if (!text) return '';
        if (text.includes('cash')) return 'Cash';
        if (text.includes('check') || text.includes('cheque')) return 'Check';
        if (text.includes('bank transfer') || text.includes('transfer')) return 'Bank Transfer';
        if (text.includes('wallet') || text.includes('ewallet') || text.includes('e-wallet')) return 'E-Wallet';
        return String(value);
    }

    function getDvSourceDocumentOptionsHtml(sourceType, selectedValue = '') {
        const options = sourceType ? (financeLookupOptions[sourceType] || []) : [];
        const currentValue = String(selectedValue || '');

        return [
            `<option value="">${escapeHtml(sourceType ? 'Select document' : 'Select source type first')}</option>`,
            ...options.map((option) => {
                const value = String(option.id ?? option.value ?? option.record_number ?? option.record_title ?? '');
                const label = option.label || option.record_title || option.record_number || 'Option';
                return `<option value="${escapeHtml(value)}" data-record-id="${escapeHtml(String(option.id ?? ''))}" ${value === currentValue ? 'selected' : ''}>${escapeHtml(label)}</option>`;
            }),
        ].join('');
    }

    function resolveDvSourceRecord(sourceType, selectEl) {
        const selectedValue = String(selectEl?.value || '').trim();
        const selectedText = String(selectEl?.selectedOptions?.[0]?.textContent || '').trim();
        const sourceOptions = financeLookupOptions[sourceType] || [];

        const optionMatch = sourceOptions.find((option) => {
            const candidates = [
                option.id,
                option.value,
                option.record_number,
                option.record_title,
                option.label,
            ].map((value) => String(value || '').trim()).filter(Boolean);
            return candidates.includes(selectedValue) || candidates.includes(selectedText);
        });

        const candidateValues = [
            selectedValue,
            selectedText,
            optionMatch?.id,
            optionMatch?.value,
            optionMatch?.record_number,
            optionMatch?.record_title,
            optionMatch?.label,
        ].filter(Boolean);

        for (const candidate of candidateValues) {
            const match = getRecordByLookupValue(sourceType, candidate)
                || getRecordByLookupValue('', candidate)
                || getRecordById(candidate);
            if (match) {
                return match;
            }
        }

        return null;
    }

    function getDvSourceDocumentInfoHtml(sourceType, sourceRecord = null) {
        if (!sourceType) {
            return '<div class="rounded-xl border border-dashed border-gray-200 bg-white/70 p-4 text-sm text-gray-500">Select a source type first.</div>';
        }

        if (!sourceRecord) {
            return '<div class="rounded-xl border border-dashed border-gray-200 bg-white/70 p-4 text-sm text-gray-500">Select a source document to auto-load its details.</div>';
        }

        const payload = getDvSourceDocumentPrefill(sourceType, sourceRecord);
        const statusLabel = sourceRecord.workflow_status || sourceRecord.approval_status || 'Accepted';
        const detailsFields = getDvSourceDocumentFields(sourceType);
        const data = sourceRecord.data || {};

        return `
            <div class="rounded-2xl border border-blue-100 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-blue-600">Source Document Loaded</p>
                        <h5 class="mt-1 text-lg font-semibold text-gray-900">${escapeHtml(sourceRecord.record_number || sourceRecord.record_title || 'Source Document')}</h5>
                        <p class="mt-1 text-sm text-gray-600">${escapeHtml(sourceRecord.record_title || 'No title')}</p>
                    </div>
                    <div class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">${escapeHtml(statusLabel)}</div>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-gray-100 bg-slate-50 px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Source Type</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(String(sourceType).toUpperCase())}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-slate-50 px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Amount</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(formatCurrency(payload.prefill.amount || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-slate-50 px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Reference</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(payload.prefill.reference_number || sourceRecord.record_number || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-slate-50 px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Auto-fill</p>
                        <p class="mt-1 font-semibold text-gray-900">Source fields populate after selection</p>
                    </div>
                </div>
                <div class="mt-4 rounded-xl border border-gray-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-gray-500">Linked Details</p>
                    <p class="mt-2 text-sm text-gray-700">${escapeHtml(payload.summary)}</p>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    ${detailsFields.map((fieldName) => {
                        const value = fieldName.includes('record_')
                            ? sourceRecord[fieldName]
                            : data[fieldName];
                        if (blank(value)) {
                            return '';
                        }
                        const label = friendlyLabelForError(`data.${fieldName}`) || fieldName;
                        return `
                            <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">${escapeHtml(label)}</p>
                                <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(getFormDisplayValue((getModuleConfig(sourceType).fields || []).find((item) => item.name === fieldName) || { name: fieldName, label }, value, data))}</p>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    function getDvSourceDocumentPrefill(sourceType, sourceRecord) {
        const data = sourceRecord?.data || {};
        const moduleKey = String(sourceType || '');
        const payload = (data.dv_payload && typeof data.dv_payload === 'object') ? data.dv_payload : {};
        const amount = [
            payload.amount,
            sourceRecord?.amount,
            data.amount,
            data.grand_total,
            data.amount_requested,
            data.total_cash_advance,
            data.amount_returned,
            data.total_payroll_amount,
            data.acquisition_cost,
        ].find((value) => !blank(value));

        const bankAccountId = [
            data.bank_account_id,
            data.funding_bank_account_id,
            data.receiving_bank_account_id,
            data.source_bank_account_id,
            data.destination_bank_account_id,
        ].find((value) => !blank(value));

        const paymentType = normalizeDvPaymentType([
            payload.payment_type,
            data.payment_type,
            data.mode_of_release,
            data.reimbursement_mode,
            data.mode_of_return,
            data.paid_through,
        ].find((value) => !blank(value)));

        const coaId = [
            payload.coa_id,
            data.coa_id,
            data.payroll_expense_coa_id,
            data.asset_coa_id,
        ].find((value) => !blank(value));

        const supplierId = payload.supplier_id || data.supplier_id || '';
        const purpose = [
            payload.purpose,
            data.purpose,
            data.expense_details,
            data.reason,
            data.supporting_payroll_summary,
            data.asset_description,
            data.remarks,
        ].find((value) => !blank(value)) || '';

        const paymentDate = payload.payment_date || data.payment_date || sourceRecord?.record_date || todayDateValue();
        const referenceNumber = payload.reference_number || data.reference_number || sourceRecord?.record_number || '';
        const recordTitle = sourceRecord?.record_title || sourceRecord?.display_label || referenceNumber || '';

        const prefill = {
            source_document_type: moduleKey,
            source_document_id: sourceRecord?.id || '',
            amount: amount ?? '',
            supplier_id: supplierId,
            coa_id: coaId || '',
            payment_type: paymentType,
            disbursement_type: paymentType || 'Cash',
            purpose,
            payment_date: paymentDate,
            reference_number: referenceNumber,
            remarks: payload.remarks || data.remarks || '',
        };

        if (moduleKey === 'ca') {
            prefill.supplier_id = data.supplier_id || '';
            prefill.bank_account_id = data.bank_account_id || '';
        }

        if (moduleKey === 'lr') {
            prefill.amount = data.grand_total || data.actual_expenses || data.total_cash_advance || amount || '';
            prefill.purpose = data.purpose || '';
        }

        if (moduleKey === 'err') {
            prefill.amount = data.amount || amount || '';
            prefill.supplier_id = data.supplier_id || '';
            prefill.bank_account_id = data.bank_account_id || '';
        }

        if (moduleKey === 'crf') {
            prefill.amount = data.amount_returned || amount || '';
            prefill.payment_type = normalizeDvPaymentType(data.mode_of_return || data.payment_type || '');
        }

        return {
            prefill,
            recordTitle,
            summary: buildSummary(sourceRecord, getModuleConfig(moduleKey)),
        };
    }

    function clearDvAutoFilledFields() {
        const form = $('financeForm');
        if (!form) return;

        ['source_document_id', 'supplier_id', 'amount', 'coa_id', 'payment_type', 'disbursement_type', 'purpose', 'payment_date', 'reference_number', 'remarks'].forEach((fieldName) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (input) {
                input.value = '';
            }
        });
        financeFormValues = financeFormValues || {};
        ['source_document_id', 'supplier_id', 'amount', 'coa_id', 'payment_type', 'disbursement_type', 'purpose', 'payment_date', 'reference_number', 'remarks'].forEach((fieldName) => {
            financeFormValues[fieldName] = '';
            financeFormValues[`data[${fieldName}]`] = '';
        });
        const info = $('dvSourceDocumentInfo');
        if (info) {
            info.innerHTML = '<div class="rounded-xl border border-dashed border-gray-200 bg-white/70 p-4 text-sm text-gray-500">Select a source document to auto-load its details.</div>';
        }
    }

    function getDvFieldPayload(sourceType, sourceRecord, sourceId = '') {
        const resolvedSourceType = sourceType || sourceRecord?.module_key || '';
        if (!sourceRecord) {
            return null;
        }

        const prefill = getDvSourceDocumentPrefill(resolvedSourceType, sourceRecord);
        return {
            source_document_type: resolvedSourceType,
            source_document_id: sourceId || sourceRecord.id || '',
            supplier_id: prefill?.prefill?.supplier_id || '',
            amount: prefill?.prefill?.amount || '',
            payment_type: prefill?.prefill?.payment_type || '',
            disbursement_type: prefill?.prefill?.disbursement_type || prefill?.prefill?.payment_type || 'Cash',
            coa_id: prefill?.prefill?.coa_id || '',
            purpose: prefill?.prefill?.purpose || '',
            payment_date: prefill?.prefill?.payment_date || '',
            reference_number: prefill?.prefill?.reference_number || '',
            remarks: prefill?.prefill?.remarks || '',
            record_title: prefill?.recordTitle || sourceRecord.record_title || sourceRecord.record_number || '',
            summary: prefill?.summary || '',
        };
    }

    function hydrateDvVoucherFields(sourceType, sourceRecord, sourceId = '') {
        const form = $('financeForm');
        if (!form || !sourceRecord) return;

        const payload = getDvFieldPayload(sourceType, sourceRecord, sourceId);
        if (!payload) return;

        const setField = (fieldName, value) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (input) {
                input.value = value ?? '';
            }
        };

        setField('source_document_type', payload.source_document_type);
        setField('source_document_id', payload.source_document_id);
        setField('supplier_id', payload.supplier_id);
        setField('amount', payload.amount);
        setField('payment_type', payload.payment_type);
        setField('disbursement_type', payload.disbursement_type);
        setField('coa_id', payload.coa_id);
        setField('purpose', payload.purpose);
        setField('payment_date', payload.payment_date);
        setField('reference_number', payload.reference_number);
        setField('remarks', payload.remarks);

        financeFormValues = financeFormValues || {};
        Object.entries(payload).forEach(([key, value]) => {
            if (key === 'record_title' || key === 'summary') return;
            financeFormValues[key] = value;
            financeFormValues[`data[${key}]`] = value;
        });

        const titleInput = $('recordTitleInput');
        if (titleInput && payload.record_title) {
            titleInput.value = payload.record_title;
        }
        if ($('amountInput')) {
            $('amountInput').value = payload.amount || '';
        }

        updateDvNetAmount();
        renderDvSourceDocumentInfo(payload.source_document_type, sourceRecord);
        renderDrawerPreview();
    }

    function renderDvSourceDocumentOptions(sourceType, selectedValue = '') {
        const select = $('dvSourceDocumentSelect');
        const title = $('dvSourceDocumentTitle');
        const hint = $('dvSourceDocumentHint');
        if (!select) return;

        select.innerHTML = getDvSourceDocumentOptionsHtml(sourceType, selectedValue);

        select.disabled = !sourceType;

        if (title) {
            title.textContent = sourceType ? `Select ${String(sourceType).toUpperCase()} Document` : 'Linked Source Document';
        }

        if (hint) {
            hint.textContent = sourceType
                ? 'Choose the exact approved source document and the voucher will auto-fill the fields below.'
                : 'Choose a source type first.';
        }
    }

    function renderDvSourceDocumentInfo(sourceType, sourceRecord = null) {
        const target = $('dvSourceDocumentInfo');
        if (!target) return;
        target.innerHTML = getDvSourceDocumentInfoHtml(sourceType, sourceRecord);
    }

    function applyDvSourceDocumentSelection(sourceType, sourceId) {
        const form = $('financeForm');
        if (!form) return;

        const sourceTypeSelect = form.querySelector('select[name="data[source_document_type]"]');
        const select = $('dvSourceDocumentSelect');
        const sourceRecord = sourceId ? resolveDvSourceRecord(sourceType, select) : null;
        const resolvedSourceType = sourceType || sourceRecord?.module_key || '';
        if (sourceTypeSelect && resolvedSourceType) {
            sourceTypeSelect.value = resolvedSourceType;
        }
        renderDvSourceDocumentOptions(resolvedSourceType, sourceId);
        if (select) {
            select.value = String(sourceId || '');
        }

        const currentBankAccountValue = form.querySelector('[name="data[bank_account_id]"]')?.value || financeFormValues['data[bank_account_id]'] || '';
        financeFormValues = financeFormValues || {};
        financeFormValues.bank_account_id = currentBankAccountValue;
        financeFormValues['data[bank_account_id]'] = currentBankAccountValue;
        if (form.querySelector('[name="data[bank_account_id]"]')) {
            form.querySelector('[name="data[bank_account_id]"]').value = currentBankAccountValue;
        }

        financeDraftContext = sourceRecord ? {
            moduleKey: 'dv',
            linkedRecord: sourceRecord,
            prefill: {
                ...getDvFieldPayload(resolvedSourceType, sourceRecord, sourceId),
                bank_account_id: currentBankAccountValue,
            },
        } : null;
        renderFinanceForm(currentEditRecordId ? getRecordById(currentEditRecordId) : null);
        requestAnimationFrame(() => {
            hydrateDvVoucherFields(resolvedSourceType, sourceRecord, sourceId);
        });
    }

    function renderBankAccountLookupList(query = '') {
        const list = $('bankAccountLookupList');
        const hidden = $('bankAccountLookupHidden');
        const display = $('bankAccountLookupDisplay');
        if (!list) return;

        const field = { source: 'chart_account', selectorFilter: 'bank_account' };
        const filtered = getLookupSelectorOptions(field, {}, query);
        const selectedValue = hidden?.value || '';
        const selectedLabel = getLookupLabel('chart_account', selectedValue) || selectedValue || '';

        if (display) {
            display.textContent = selectedLabel || 'Select Linked Chart of Account';
        }

        list.innerHTML = filtered.length ? `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                ${filtered.map((option) => {
                    const optionValue = String(option.id ?? option.value ?? option.record_number ?? option.record_title ?? '');
                    const optionLabel = option.label || option.record_title || option.record_number || 'Option';
                    const isSelected = optionValue === String(selectedValue);
                    return `
                        <button
                            type="button"
                            data-bank-account-option-value="${escapeHtml(optionValue)}"
                            data-bank-account-option-label="${escapeHtml(optionLabel)}"
                            class="group h-full rounded-2xl border ${isSelected ? 'border-blue-400 bg-blue-50 shadow-md' : 'border-gray-200 bg-white shadow-sm'} p-4 text-left transition hover:border-blue-200 hover:bg-blue-50/40 hover:shadow-md"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-gray-900 break-words group-hover:text-blue-700">${escapeHtml(optionLabel)}</p>
                                    <p class="mt-1 text-xs text-gray-500 break-words">
                                        ${escapeHtml(option.record_number || '')}${option.record_title ? ` • ${escapeHtml(option.record_title)}` : ''}
                                    </p>
                                </div>
                                ${isSelected ? '<span class="shrink-0 rounded-full bg-blue-600 px-2.5 py-1 text-[11px] font-semibold text-white">Selected</span>' : '<span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">Select</span>'}
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-1 text-xs text-gray-500">
                                ${option.account_type ? `<p><span class="font-medium text-gray-700">Type:</span> ${escapeHtml(option.account_type)}</p>` : ''}
                                ${option.account_group ? `<p><span class="font-medium text-gray-700">Group:</span> ${escapeHtml(option.account_group)}</p>` : ''}
                                ${option.account_description ? `<p><span class="font-medium text-gray-700">Details:</span> ${escapeHtml(option.account_description)}</p>` : ''}
                            </div>
                        </button>
                    `;
                }).join('')}
            </div>
        ` : '<div class="px-4 py-6 text-sm text-gray-500">No matching accounts found.</div>';
    }

    function renderDynamicField(field, value, formValues = {}) {
        const required = field.required ? 'required' : '';
        const label = escapeHtml(field.label);
        const hint = field.help ? `<p class="mt-1 text-xs text-gray-500">${escapeHtml(field.help)}</p>` : '';
        const fieldName = `data[${field.name}]`;
        const dependencyValue = field.dependsOnCheckbox ? formValues[`data[${field.dependsOnCheckbox}]`] : null;
        const disabledAttr = field.dependsOnCheckbox && !dependencyValue ? 'disabled' : '';
        const emptyOption = field.placeholder || `Select ${field.label}`;
        const readOnlyAttr = field.readOnly ? 'readonly' : '';
        const readOnlyClass = field.readOnly ? 'bg-gray-100 cursor-not-allowed' : '';

        let control = '';

        if (field.type === 'textarea') {
            control = `<textarea name="${fieldName}" rows="${field.rows || 3}" class="w-full border rounded-md p-2 ${readOnlyClass}" ${readOnlyAttr}>${escapeHtml(value)}</textarea>`;
        } else if (field.type === 'selector') {
            const selectedLabel = getLookupLabel(field.source, value) || value || '';
            control = `
                <div class="space-y-1.5">
                    <input type="hidden" name="${fieldName}" data-lookup-selector-hidden="${escapeHtml(field.name)}" value="${escapeHtml(value)}">
                    <button
                        type="button"
                        onclick="window.financeModule.openLookupSelector(${JSON.stringify(field.name)}, ${JSON.stringify(field.source)}, ${JSON.stringify(field.label)}, ${JSON.stringify(field.selectorFilter || '')})"
                        class="w-full rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-left shadow-sm hover:bg-blue-100 transition"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">${label}</span>
                                <span data-lookup-selector-display="${escapeHtml(field.name)}" class="mt-1 block truncate text-sm font-medium text-gray-800">${escapeHtml(selectedLabel || `Select ${field.label}`)}</span>
                            </div>
                            <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700 shadow-sm">Search list</span>
                        </div>
                    </button>
                </div>
            `;
        } else if (field.type === 'calculation') {
            const displayValue = field.name === 'liquidation_calculation'
                ? getCashAdvanceLiquidationCalculation(formValues)
                : (value || 'N/A');
            control = `
                <div class="w-full rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900">
                    ${escapeHtml(displayValue)}
                </div>
            `;
        } else if (field.type === 'select') {
            const options = getFieldOptions(field, formValues)
                .map((option) => `<option value="${escapeHtml(option.id ?? option.value)}" ${String(value) === String(option.id ?? option.value) ? 'selected' : ''}>${escapeHtml(option.label ?? option.value)}</option>`)
                .join('');
            control = `
                <select name="${fieldName}" class="w-full border rounded-md p-2 ${readOnlyClass}" ${disabledAttr} ${required} ${field.readOnly ? 'disabled' : ''}>
                    <option value="">${escapeHtml(emptyOption)}</option>
                ${options}
            </select>
        `;
        } else if (field.type === 'checkbox-group') {
            const selectedValues = Array.isArray(value)
                ? value.map((item) => String(item))
                : String(value || '')
                    .split(',')
                    .map((item) => item.trim())
                    .filter(Boolean);
            control = `
                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                    ${(field.options || []).map((option) => {
                        const optionValue = String(option.value ?? option.label ?? '');
                        const checked = selectedValues.includes(optionValue);
                        return `
                            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox" name="${fieldName}[]" value="${escapeHtml(optionValue)}" ${checked ? 'checked' : ''} class="rounded border-gray-300">
                                <span>${escapeHtml(option.label ?? optionValue)}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            `;
        } else if (field.type === 'radio-group') {
            const selectedValue = String(Array.isArray(value) ? value[0] : value || '');
            control = `
                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                    ${(field.options || []).map((option) => {
                        const optionValue = String(option.value ?? option.label ?? '');
                        const checked = selectedValue === optionValue;
                        return `
                            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                <input type="radio" name="${fieldName}" value="${escapeHtml(optionValue)}" ${checked ? 'checked' : ''} class="border-gray-300">
                                <span>${escapeHtml(option.label ?? optionValue)}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            `;
        } else if (field.type === 'checkbox') {
            control = `
                <label class="inline-flex items-center gap-2 mt-2">
                    <input type="checkbox" name="${fieldName}" value="1" ${value ? 'checked' : ''} class="rounded border-gray-300">
                    <span class="text-sm text-gray-700">${label}</span>
                </label>
            `;
        } else if (field.type === 'number') {
            control = `<input type="number" step="0.01" name="${fieldName}" value="${escapeHtml(value)}" class="w-full border rounded-md p-2 ${readOnlyClass}" ${required} ${readOnlyAttr}>`;
        } else if (field.type === 'date') {
            control = `<input type="date" name="${fieldName}" value="${escapeHtml(value)}" class="w-full border rounded-md p-2 ${readOnlyClass}" ${required} ${readOnlyAttr}>`;
        } else if (field.type === 'time') {
            control = `<input type="time" name="${fieldName}" value="${escapeHtml(value)}" class="w-full border rounded-md p-2 ${readOnlyClass}" ${required} ${readOnlyAttr}>`;
        } else {
            control = `<input type="${field.inputType || 'text'}" name="${fieldName}" value="${escapeHtml(value)}" class="w-full border rounded-md p-2 ${readOnlyClass}" ${required} ${readOnlyAttr}>`;
        }

        return `
            <div class="${field.fullWidth ? 'md:col-span-2' : ''}">
                <label class="block text-sm font-medium mb-1">${label}${field.required ? ' <span class="text-red-500">*</span>' : ''}</label>
                ${control}
            ${hint}
        </div>
        `;
    }

    function renderLookupSelectorModal(options = []) {
        const modal = $('financeLookupSelectorModal');
        const title = $('financeLookupSelectorTitle');
        const subtitle = $('financeLookupSelectorSubtitle');
        const search = $('financeLookupSelectorSearch');
        const list = $('financeLookupSelectorList');

        if (!modal || !title || !subtitle || !search || !list || !activeLookupSelector) {
            return;
        }

        title.textContent = activeLookupSelector.label;
        subtitle.textContent = activeLookupSelector.filterKey === 'bank_account'
            ? 'Search and pick a bank/cash related chart of account.'
            : 'Search and pick a linked record from the list below.';
        search.placeholder = `Search ${String(activeLookupSelector.label || 'records').toLowerCase()}...`;

        const query = search.value || '';
        const filtered = getLookupSelectorOptions(activeLookupSelector, {}, query);

        list.innerHTML = filtered.length ? `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4">
                ${filtered.map((option) => `
                    <button
                        type="button"
                        class="group h-full rounded-2xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-blue-200 hover:bg-blue-50/40 hover:shadow-md"
                        onclick="window.financeModule.selectLookupSelectorValue(${JSON.stringify(String(option.id ?? ''))}, ${JSON.stringify(option.label || option.record_title || option.record_number || 'Option')})"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 break-words group-hover:text-blue-700">${escapeHtml(option.label || option.record_title || option.record_number || 'Option')}</p>
                                <p class="mt-1 text-xs text-gray-500 break-words">
                                    ${escapeHtml(option.record_number || '')}${option.record_title ? ` • ${escapeHtml(option.record_title)}` : ''}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">Select</span>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-1 text-xs text-gray-500">
                            ${option.account_type ? `<p><span class="font-medium text-gray-700">Type:</span> ${escapeHtml(option.account_type)}</p>` : ''}
                            ${option.account_group ? `<p><span class="font-medium text-gray-700">Group:</span> ${escapeHtml(option.account_group)}</p>` : ''}
                            ${option.account_description ? `<p><span class="font-medium text-gray-700">Details:</span> ${escapeHtml(option.account_description)}</p>` : ''}
                            ${!option.account_type && !option.account_group && !option.account_description ? '<p class="text-gray-400">Tap to link this record.</p>' : ''}
                        </div>
                    </button>
                `).join('')}
            </div>
        ` : '<div class="px-4 py-6 text-sm text-gray-500">No matching accounts found.</div>';
    }

    function openLookupSelector(fieldName, source, label, filterKey = '') {
        activeLookupSelector = { fieldName, source, label, filterKey };
        const modal = $('financeLookupSelectorModal');
        const search = $('financeLookupSelectorSearch');
        if (!modal) return;

        modal.classList.remove('hidden');
        if (search) {
            search.value = '';
            setTimeout(() => search.focus(), 50);
        }

        renderLookupSelectorModal();
    }

    function closeLookupSelector() {
        const modal = $('financeLookupSelectorModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        activeLookupSelector = null;
    }

    function selectLookupSelectorValue(value, label) {
        if (!activeLookupSelector) return;

        const hidden = document.querySelector(`[data-lookup-selector-hidden="${activeLookupSelector.fieldName}"]`);
        const display = document.querySelector(`[data-lookup-selector-display="${activeLookupSelector.fieldName}"]`);
        if (hidden) hidden.value = value || '';
        if (display) display.textContent = label || '';

        closeLookupSelector();
        renderDrawerPreview();
        if (currentPreviewRecord) {
            renderPreviewTabContent(currentPreviewRecord);
            renderPreviewDocument(currentPreviewRecord);
            renderPreviewActions(currentPreviewRecord);
        }
    }

    function selectBankAccountLookupValue(value, label) {
        const hidden = $('bankAccountLookupHidden');
        const display = $('bankAccountLookupDisplay');
        const currentValue = hidden?.value || '';
        const nextValue = String(currentValue) === String(value || '') ? '' : (value || '');
        const nextLabel = nextValue ? (label || nextValue) : 'Select Linked Chart of Account';

        if (hidden) hidden.value = nextValue;
        if (display) display.textContent = nextLabel;

        activeBankAccountLookupQuery = $('bankAccountLookupSearch')?.value || activeBankAccountLookupQuery;
        renderBankAccountLookupList($('bankAccountLookupSearch')?.value || '');
        renderDrawerPreview();
        if (currentPreviewRecord) {
            renderPreviewTabContent(currentPreviewRecord);
            renderPreviewDocument(currentPreviewRecord);
            renderPreviewActions(currentPreviewRecord);
        }
    }

    function renderFieldsByNames(moduleConfig, fieldNames, values, record) {
        return fieldNames.map((fieldName) => {
            const field = (moduleConfig.fields || []).find((item) => item.name === fieldName);
            if (!field) return '';

            let fieldValue = field.type === 'calculation'
                ? getFormDisplayValue(field, '', record ? (record.data || {}) : values)
                : (record ? getModuleFieldValue(record, field) : (values[`data[${field.name}]`] || ''));
            if (!fieldValue && field.autoFillCurrentUser) {
                fieldValue = bootstrap.currentUserName || '';
            }
            if (!record && !fieldValue && isRequestOwnershipModule() && shouldUseCurrentUserRequesterDefaults(values)) {
                fieldValue = getRequesterDefaultForField(field.name);
            }
            values[field.name] = fieldValue;
            values[`data[${field.name}]`] = fieldValue;
            return renderDynamicField(field, fieldValue, values);
        }).join('');
    }

    function getPrLineItemRows(record) {
        const draftLineItems = Array.isArray(financeFormValues?.line_items) && financeFormValues.line_items.length
            ? financeFormValues.line_items
            : (Array.isArray(financeDraftContext?.prefill?.line_items) ? financeDraftContext.prefill.line_items : []);
        const lineItems = draftLineItems.length
            ? draftLineItems
            : (Array.isArray(record?.data?.line_items) ? record.data.line_items : []);

        if (lineItems.length) {
            return lineItems.map((item) => ({
                item_id: item.item_id || '',
                description: item.description || '',
                category: item.category || '',
                quantity: item.quantity || '',
                amount: item.amount || '',
                subtotal: item.subtotal || item.total || '',
                discount: item.discount || '0%',
                discount_amount: item.discount_amount || '',
                shipping_amount: item.shipping_amount || '',
                tax_type: item.tax_type || 'N/A',
                tax_amount: item.tax_amount || '',
                wht_amount: item.wht_amount || '',
                total: item.total || '',
                supplier_id: item.supplier_id || '',
                client_id: item.client_id || '',
            }));
        }

        if (record?.module_key === 'po') {
            const legacyItem = {
                item_id: record ? (getModuleFieldValue(record, { name: 'linked_item_id' }) || '') : '',
                description: record ? (getModuleFieldValue(record, { name: 'purpose' }) || '') : '',
                category: record ? (getModuleFieldValue(record, { name: 'linked_item_type' }) || '') : '',
                quantity: record ? (getModuleFieldValue(record, { name: 'quantity' }) || '') : '',
                amount: record ? (getModuleFieldValue(record, { name: 'unit_cost' }) || '') : '',
                subtotal: record ? (getModuleFieldValue(record, { name: 'total_amount' }) || '') : '',
                discount: record ? (getModuleFieldValue(record, { name: 'discount' }) || '0%') : '0%',
                discount_amount: record ? (getModuleFieldValue(record, { name: 'discount_amount' }) || '') : '',
                shipping_amount: record ? (getModuleFieldValue(record, { name: 'shipping_amount' }) || '') : '',
                tax_type: record ? (getModuleFieldValue(record, { name: 'tax_type' }) || 'N/A') : 'N/A',
                tax_amount: record ? (getModuleFieldValue(record, { name: 'tax_amount' }) || '') : '',
                wht_amount: record ? (getModuleFieldValue(record, { name: 'wht_amount' }) || '') : '',
                total: record ? (getModuleFieldValue(record, { name: 'total_amount' }) || '') : '',
                supplier_id: record ? (getModuleFieldValue(record, { name: 'supplier_id' }) || '') : '',
                client_id: '',
            };

            if (Object.values(legacyItem).some((value) => String(value || '').trim() !== '')) {
                return [legacyItem];
            }
        }

        const legacyItem = {
            item_id: record ? (getModuleFieldValue(record, { name: 'master_item_id' }) || '') : '',
            description: record ? (getModuleFieldValue(record, { name: 'description_specification' }) || '') : '',
            category: record ? (getModuleFieldValue(record, { name: 'master_item_type' }) || '') : '',
            quantity: record ? (getModuleFieldValue(record, { name: 'quantity' }) || '') : '',
            amount: record ? (getModuleFieldValue(record, { name: 'unit_cost' }) || '') : '',
            subtotal: record ? (getModuleFieldValue(record, { name: 'estimated_total_cost' }) || '') : '',
            discount: '0%',
            discount_amount: '',
            shipping_amount: '',
            tax_type: 'N/A',
            tax_amount: '',
            wht_amount: '',
            total: record ? (getModuleFieldValue(record, { name: 'estimated_total_cost' }) || '') : '',
            supplier_id: record ? (getModuleFieldValue(record, { name: 'supplier_id' }) || '') : '',
            client_id: '',
        };

        if (Object.values(legacyItem).some((value) => String(value || '').trim() !== '')) {
            return [legacyItem];
        }

        return [{
            item_id: '',
            description: '',
            category: '',
            quantity: '',
            amount: '',
            subtotal: '',
            discount: '0%',
            discount_amount: '',
            shipping_amount: '',
            tax_type: 'N/A',
            tax_amount: '',
            wht_amount: '',
            total: '',
            supplier_id: '',
            client_id: '',
        }];
    }

    function getPrItemDisplayValue(value) {
        if (currentModuleKey === 'po') {
            const lookups = [financeLookupOptions.product || [], financeLookupOptions.service || []];
            for (const lookup of lookups) {
                const matchById = lookup.find((option) => String(option.id) === String(value));
                if (matchById) {
                    return matchById.label;
                }
            }
        }

        const productLookup = financeLookupOptions.product || [];
        const matchById = productLookup.find((option) => String(option.id) === String(value));
        if (matchById) {
            return matchById.label;
        }

        return value || '';
    }

    function getPrCategoryDisplayValue(value) {
        return value || '';
    }

    function getFinanceCurrentUserProfile() {
        const contact = bootstrap.currentUserContact || {};
        const contactName = contact.name || contact.full_name || '';
        const contactEmail = contact.email || '';

        return {
            name: contactName || bootstrap.currentUserName || '',
            email: contactEmail || bootstrap.currentUserEmail || '',
            phone: contact.phone || contact.contact_number || '',
            contact_number: contact.contact_number || contact.phone || '',
            position: contact.position || '',
            department: contact.department || contact.company_name || '',
            company_name: contact.company_name || '',
            address: contact.address || contact.contact_address || '',
            contact_address: contact.contact_address || contact.address || '',
            contact_id: contact.id || '',
            employee_id: contact.employee_id || contact.employee_code || '',
            employee_code: contact.employee_code || contact.employee_id || '',
            superior: contact.superior || '',
            superior_email: contact.superior_email || '',
            cif_no: contact.cif_no || '',
            tin: contact.tin || '',
        };
    }

    function getPrRequesterDefaults() {
        const currentUser = getFinanceCurrentUserProfile();
        return {
            requestor: currentUser.name,
            employee_name: currentUser.name,
            employee_email: currentUser.email,
            contact_number: currentUser.contact_number || currentUser.phone,
            position: currentUser.position,
            department: currentUser.department,
            company_name: currentUser.company_name,
            address: currentUser.contact_address || currentUser.address,
            contact_id: currentUser.contact_id,
            employee_id: currentUser.employee_id || currentUser.employee_code,
            superior: currentUser.superior,
            superior_email: currentUser.superior_email,
        };
    }

    function getRequesterDefaultForField(fieldName) {
        const defaults = getPrRequesterDefaults();

        return defaults[fieldName] ?? '';
    }

    function shouldUseCurrentUserRequesterDefaults(values = {}) {
        const requesterMode = values['data[requester_mode]']
            || values.requester_mode
            || financeFormValues?.requester_mode
            || financeFormValues?.['data[requester_mode]']
            || 'own_request';

        return requesterMode !== 'request_for_another';
    }

    function getRequestOwnershipConfig(moduleKey = currentModuleKey) {
        const configs = {
            pr: { modeField: 'requester_mode', nameField: 'requestor', emailField: 'employee_email' },
            ca: { modeField: 'requester_mode', nameField: 'requestor', emailField: 'employee_email', mirrorNameField: 'employee_name' },
            lr: { modeField: 'requester_mode', nameField: 'employee_name', emailField: 'employee_email' },
            err: { modeField: 'requester_mode', nameField: 'requestor' },
            crf: { modeField: 'requester_mode', nameField: 'requestor' },
        };

        return configs[moduleKey] || null;
    }

    function isRequestOwnershipModule(moduleKey = currentModuleKey) {
        return Boolean(getRequestOwnershipConfig(moduleKey));
    }

    function syncRequestOwnershipFields({ preserveExisting = false } = {}) {
        const config = getRequestOwnershipConfig();
        if (!config) return;

        const form = $('financeForm');
        if (!form) return;

        const autoValues = getPrRequesterDefaults();
        const requesterModeInput = form.querySelector(`select[name="data[${config.modeField}]"]`);
        const requesterMode = requesterModeInput?.value
            || financeFormValues[config.modeField]
            || 'own_request';
        const shouldUseOwnRequest = requesterMode !== 'request_for_another';

        if (requesterModeInput) {
            requesterModeInput.value = requesterMode;
        }

        const syncField = (fieldName, autoValue, { readOnlyWhenOwn = true } = {}) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (!input) return;

            const currentValue = String(input.value || '').trim();
            const nextValue = shouldUseOwnRequest
                ? (preserveExisting && currentValue ? currentValue : (autoValue || ''))
                : (preserveExisting && currentValue && currentValue !== (autoValue || '') ? currentValue : '');

            input.value = nextValue;
            if (readOnlyWhenOwn) {
                setReadonlyState(input, shouldUseOwnRequest && Boolean(autoValue));
            }
            financeFormValues[fieldName] = nextValue;
            financeFormValues[`data[${fieldName}]`] = nextValue;
        };

        syncField(config.nameField, autoValues.requestor);
        syncField('employee_name', autoValues.employee_name);
        if (config.mirrorNameField) {
            syncField(config.mirrorNameField, autoValues.requestor);
        }
        if (config.emailField) {
            syncField(config.emailField, autoValues.employee_email);
        }
        syncField('contact_number', autoValues.contact_number);
        syncField('position', autoValues.position);
        syncField('department', autoValues.department);
        syncField('employee_id', autoValues.employee_id);
        syncField('superior', autoValues.superior);
        syncField('superior_email', autoValues.superior_email);

        financeFormValues[config.modeField] = requesterMode;
        financeFormValues[`data[${config.modeField}]`] = requesterMode;
    }

    function forceFillOwnRequesterDetails({ preserveExisting = true } = {}) {
        if (!isRequestOwnershipModule()) return;

        const form = $('financeForm');
        if (!form) return;

        const modeInput = form.querySelector('select[name="data[requester_mode]"]');
        const requesterMode = modeInput?.value
            || financeFormValues?.requester_mode
            || financeFormValues?.['data[requester_mode]']
            || 'own_request';

        if (requesterMode === 'request_for_another') {
            return;
        }

        const defaults = getPrRequesterDefaults();
        const fieldMap = {
            requestor: defaults.requestor,
            employee_name: defaults.employee_name || defaults.requestor,
            employee_email: defaults.employee_email,
            employee_id: defaults.employee_id,
            contact_number: defaults.contact_number,
            position: defaults.position,
            department: defaults.department,
            superior: defaults.superior,
            superior_email: defaults.superior_email,
        };

        Object.entries(fieldMap).forEach(([fieldName, value]) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (!input || value === undefined || value === null || value === '') return;

            const currentValue = String(input.value || '').trim();
            if (preserveExisting && currentValue && currentValue !== bootstrap.currentUserName && currentValue !== bootstrap.currentUserEmail) {
                return;
            }

            input.value = value;
            setReadonlyState(input, true);
            financeFormValues[fieldName] = value;
            financeFormValues[`data[${fieldName}]`] = value;
        });
    }

    function getPrSupplierRecord(value) {
        const supplierId = String(value || '').trim();
        if (!supplierId) {
            return null;
        }

        return getRecordByLookupValue('supplier', supplierId)
            || getRecordByLookupValue('', supplierId)
            || getRecordById(supplierId);
    }

    function getPrSupplierAutofillValues(supplierRecord) {
        const data = supplierRecord?.data || {};
        const vendorAddress = data.business_address || data.billing_address || '';

        return {
            new_vendor: supplierRecord ? 'No' : '',
            vendor_id_number: supplierRecord?.record_number || '',
            vendors_tin: data.tin || '',
            company_name: data.trade_name || supplierRecord?.record_title || supplierRecord?.display_label || '',
            vendor_address: vendorAddress,
            city: data.city || '',
            province: data.province || '',
            zip: data.zip || '',
            vendor_phone: data.phone_number || '',
            vendor_email: data.email_address || '',
        };
    }

    function setFinanceFieldValue(form, fieldName, value, { readOnly = null } = {}) {
        if (!form) return;

        const input = form.querySelector(`[name="data[${fieldName}]"]`);
        if (input) {
            input.value = value ?? '';
            if (readOnly !== null) {
                setReadonlyState(input, Boolean(readOnly));
            }
        }

        financeFormValues = financeFormValues || {};
        financeFormValues[fieldName] = value ?? '';
        financeFormValues[`data[${fieldName}]`] = value ?? '';
    }

    function getDvLineItemRows(record = null) {
        const rows = Array.isArray(financeFormValues?.dv_line_items) && financeFormValues.dv_line_items.length
            ? financeFormValues.dv_line_items
            : (Array.isArray(record?.data?.line_items) ? record.data.line_items : []);

        return rows.length ? rows : [{
            description: '',
            account_code: '',
            debit: '',
            credit: '',
        }];
    }

    function renderDvLineItemsTable(record = null) {
        const rows = getDvLineItemRows(record);

        return `
            <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4" data-dv-line-items-section>
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Breakdown / Line Items</h4>
                    <button type="button" onclick="window.financeModule.addDvLineItemRow()" class="rounded-md border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">Add Line</button>
                </div>
                <div class="mt-4 space-y-3">
                    ${rows.map((row, index) => `
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 rounded-lg border border-gray-100 bg-slate-50 p-3" data-dv-line-item-row>
                            <div class="md:col-span-5">
                                <label class="block text-xs font-medium text-gray-600">Description</label>
                                <input type="text" name="data[line_items][${index}][description]" data-dv-line-item-field="description" value="${escapeHtml(row.description || '')}" class="mt-1 w-full border rounded-md p-2 bg-white">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-gray-600">Account Code</label>
                                <input type="text" name="data[line_items][${index}][account_code]" data-dv-line-item-field="account_code" value="${escapeHtml(row.account_code || '')}" class="mt-1 w-full border rounded-md p-2 bg-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600">Debit</label>
                                <input type="number" step="0.01" min="0" name="data[line_items][${index}][debit]" data-dv-line-item-field="debit" value="${escapeHtml(row.debit || '')}" class="mt-1 w-full border rounded-md p-2 bg-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600">Credit</label>
                                <input type="number" step="0.01" min="0" name="data[line_items][${index}][credit]" data-dv-line-item-field="credit" value="${escapeHtml(row.credit || '')}" class="mt-1 w-full border rounded-md p-2 bg-white">
                            </div>
                            <div class="md:col-span-12 flex justify-end">
                                <button type="button" onclick="window.financeModule.removeDvLineItemRow(${index})" class="text-xs font-semibold text-red-600 hover:text-red-700">Remove</button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function collectDvLineItems() {
        const rows = Array.from(document.querySelectorAll('[data-dv-line-item-row]')).map((row) => {
            const getValue = (field) => row.querySelector(`[data-dv-line-item-field="${field}"]`)?.value || '';
            return {
                description: getValue('description'),
                account_code: getValue('account_code'),
                debit: getValue('debit'),
                credit: getValue('credit'),
            };
        });

        financeFormValues.dv_line_items = rows;
        return rows;
    }

    function replaceDvLineItemsTable(rows) {
        financeFormValues.dv_line_items = rows;
        const section = document.querySelector('[data-dv-line-items-section]');
        if (!section) return;
        section.outerHTML = renderDvLineItemsTable({ data: { line_items: rows } });
        bindDvLineItems();
        renderDrawerPreview();
    }

    function addDvLineItemRow() {
        const rows = collectDvLineItems();
        rows.push({ description: '', account_code: '', debit: '', credit: '' });
        replaceDvLineItemsTable(rows);
    }

    function removeDvLineItemRow(index) {
        const rows = collectDvLineItems().filter((_, rowIndex) => rowIndex !== index);
        replaceDvLineItemsTable(rows.length ? rows : [{ description: '', account_code: '', debit: '', credit: '' }]);
    }

    function bindDvLineItems() {
        document.querySelectorAll('[data-dv-line-item-field]').forEach((input) => {
            input.addEventListener('input', () => {
                collectDvLineItems();
                renderDrawerPreview();
            });
        });
    }

    function updateDvNetAmount() {
        const form = $('financeForm');
        if (!form || currentModuleKey !== 'dv') return;

        const amount = parseFloat(form.querySelector('[name="data[amount]"]')?.value || '0') || 0;
        const withholding = parseFloat(form.querySelector('[name="data[withholding_tax]"]')?.value || '0') || 0;
        const vat = parseFloat(form.querySelector('[name="data[vat_amount]"]')?.value || '0') || 0;
        const netAmount = Math.max(amount + vat - withholding, 0).toFixed(2);
        setFinanceFieldValue(form, 'net_amount', netAmount, { readOnly: true });
    }

    function getNormalizedLineItems(record) {
        const rows = getPrLineItemRows(record);
        return rows.map((row) => ({
            item_id: row.item_id || '',
            description: row.description || '',
            category: row.category || '',
            quantity: row.quantity || '',
            amount: row.amount || '',
            subtotal: row.subtotal || row.total || '',
            discount: row.discount || '0%',
            discount_amount: row.discount_amount || '',
            shipping_amount: row.shipping_amount || '',
            tax_type: row.tax_type || 'N/A',
            tax_amount: row.tax_amount || '',
            wht_amount: row.wht_amount || '',
            total: row.total || '',
            supplier_id: row.supplier_id || '',
            client_id: row.client_id || '',
        }));
    }

    function replaceCurrentLineItemSection(rows = []) {
        if (!isLineItemModule()) return;

        const section = document.querySelector('[data-pr-line-items-section]');
        if (!section) return;

        financeFormValues = financeFormValues || {};
        financeFormValues.line_items = rows.map((row) => ({ ...row }));
        section.outerHTML = renderPrLineItemsTable({
            module_key: currentModuleKey,
            data: { line_items: financeFormValues.line_items },
        });
        bindPurchaseRequestLineItems();
        updatePrTotals();
    }

    function getPoLinkedPrSupplierCounts(linkedPrRecord) {
        const counts = new Map();
        const sourceLineItems = getNormalizedLineItems(linkedPrRecord);

        sourceLineItems.forEach((item) => {
            const supplierId = String(item.supplier_id || '').trim();
            if (!supplierId) return;

            const current = counts.get(supplierId) || {
                id: supplierId,
                label: getLineItemLookupLabel('supplier', supplierId, supplierId),
                count: 0,
            };
            current.count += 1;
            counts.set(supplierId, current);
        });

        return Array.from(counts.values());
    }

    function renderPoLinkedPrSupplierSummary(linkedPrRecord = null) {
        const suppliers = linkedPrRecord ? getPoLinkedPrSupplierCounts(linkedPrRecord) : [];
        const summaryText = suppliers.map((supplier) => `${supplier.label} (${supplier.count})`).join(', ');

        return `
            <div data-po-linked-pr-supplier-summary class="md:col-span-2 rounded-xl border border-blue-100 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-700">PR Item Suppliers</h5>
                        <p class="mt-1 text-xs text-gray-500">Suppliers selected in the linked PR items are listed once with their item count.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">${escapeHtml(String(suppliers.length))} Supplier${suppliers.length === 1 ? '' : 's'}</span>
                </div>
                <input type="hidden" name="data[linked_pr_supplier_summary]" value="${escapeHtml(summaryText)}">
                <div class="mt-4 space-y-2">
                    ${suppliers.length ? suppliers.map((supplier) => `
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-slate-50 px-3 py-2">
                            <p class="text-sm font-semibold text-gray-900 break-words">${escapeHtml(supplier.label)}</p>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700">${escapeHtml(String(supplier.count))} item${supplier.count === 1 ? '' : 's'}</span>
                        </div>
                    `).join('') : `
                        <div class="rounded-lg border border-dashed border-gray-200 bg-slate-50 px-3 py-3 text-sm text-gray-500">
                            Select a PR with item suppliers to show them here.
                        </div>
                    `}
                </div>
            </div>
        `;
    }

    function updatePoLinkedPrSupplierSummary(linkedPrRecord = null) {
        const panel = document.querySelector('[data-po-linked-pr-supplier-summary]');
        if (!panel) return;

        panel.outerHTML = renderPoLinkedPrSupplierSummary(linkedPrRecord);
    }

    function syncPoLinkedPrFields({ preserveExisting = false } = {}) {
        if (currentModuleKey !== 'po') return;

        const form = $('financeForm');
        if (!form) return;

        const linkedPrId = form.querySelector('select[name="data[linked_pr_id]"]')?.value || financeFormValues.linked_pr_id || '';
        const linkedPrRecord = getRecordById(linkedPrId) || getRecordByLookupValue('pr', linkedPrId);

        financeFormValues.linked_pr_id = linkedPrId;
        financeFormValues['data[linked_pr_id]'] = linkedPrId;

        if (!linkedPrRecord) {
            updatePoLinkedPrSupplierSummary(null);
            return;
        }

        const sourceData = linkedPrRecord.data || {};
        const sourceLineItems = getNormalizedLineItems(linkedPrRecord);
        const supplierCounts = getPoLinkedPrSupplierCounts(linkedPrRecord);
        const primarySupplierId = sourceData.supplier_id || supplierCounts[0]?.id || '';
        const hasCurrentLineValues = Array.from(form.querySelectorAll('[data-pr-line-item-row] [data-pr-line-item-field]'))
            .some((input) => String(input.value || '').trim() !== '');

        if (!preserveExisting || !String(form.querySelector('select[name="data[supplier_id]"]')?.value || '').trim()) {
            setFinanceFieldValue(form, 'supplier_id', primarySupplierId);
        }
        if (!preserveExisting || !String(form.querySelector('select[name="data[coa_id]"]')?.value || '').trim()) {
            setFinanceFieldValue(form, 'coa_id', sourceData.coa_id || '');
        }
        if (!preserveExisting || !String(form.querySelector('textarea[name="data[remarks]"]')?.value || '').trim()) {
            setFinanceFieldValue(form, 'remarks', sourceData.remarks || '');
        }

        const titleInput = $('recordTitleInput');
        const currentTitle = String(titleInput?.value || '').trim();
        const defaultTitle = generateDefaultRecordTitle(currentModuleKey);
        if (titleInput && (!preserveExisting || !currentTitle || currentTitle === defaultTitle)) {
            titleInput.value = linkedPrRecord.record_title || `PO for ${linkedPrRecord.record_number || 'PR'}`;
        }

        if (sourceLineItems.length && (!preserveExisting || !hasCurrentLineValues)) {
            replaceCurrentLineItemSection(sourceLineItems);
        }

        updatePoLinkedPrSupplierSummary(linkedPrRecord);
        renderDrawerPreview();
    }

    function getArfAutofillValuesFromLinkedRecord(record) {
        const data = record?.data || {};
        const lineItems = getNormalizedLineItems(record);
        const firstLineItem = lineItems[0] || {};

        return {
            supplier_id: data.supplier_id || '',
            asset_description: firstLineItem.description || data.asset_description || data.purpose || record?.record_title || '',
            asset_category: firstLineItem.category || data.asset_category || data.linked_item_type || '',
            serial_number: data.serial_number || '',
            model: data.model || '',
            acquisition_cost: data.grand_total || data.total_amount || data.amount || record?.amount || '',
            acquisition_date: data.payment_date || record?.record_date || '',
            asset_coa_id: data.asset_coa_id || data.coa_id || '',
            remarks: data.remarks || '',
        };
    }

    function syncArfLinkedDocumentFields({ preserveExisting = false } = {}) {
        if (currentModuleKey !== 'arf') return;

        const form = $('financeForm');
        if (!form) return;

        const linkedPoId = form.querySelector('select[name="data[linked_po_id]"]')?.value || financeFormValues.linked_po_id || '';
        const linkedDvId = form.querySelector('select[name="data[linked_dv_id]"]')?.value || financeFormValues.linked_dv_id || '';
        const linkedPoRecord = linkedPoId ? (getRecordById(linkedPoId) || getRecordByLookupValue('po', linkedPoId)) : null;
        const linkedDvRecord = linkedDvId ? (getRecordById(linkedDvId) || getRecordByLookupValue('dv', linkedDvId)) : null;

        financeFormValues.linked_po_id = linkedPoId;
        financeFormValues['data[linked_po_id]'] = linkedPoId;
        financeFormValues.linked_dv_id = linkedDvId;
        financeFormValues['data[linked_dv_id]'] = linkedDvId;

        const payload = {
            ...(linkedPoRecord ? getArfAutofillValuesFromLinkedRecord(linkedPoRecord) : {}),
            ...(linkedDvRecord ? getArfAutofillValuesFromLinkedRecord(linkedDvRecord) : {}),
        };

        if (linkedDvRecord && !linkedPoId && String(linkedDvRecord.data?.source_document_type || '') === 'po' && linkedDvRecord.data?.source_document_id) {
            setFinanceFieldValue(form, 'linked_po_id', linkedDvRecord.data.source_document_id);
            financeFormValues.linked_po_id = linkedDvRecord.data.source_document_id;
            financeFormValues['data[linked_po_id]'] = linkedDvRecord.data.source_document_id;
        }

        ['supplier_id', 'asset_description', 'asset_category', 'serial_number', 'model', 'acquisition_cost', 'acquisition_date', 'asset_coa_id', 'remarks'].forEach((fieldName) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            const currentValue = String(input?.value || '').trim();
            if (!input) return;
            if (preserveExisting && currentValue) return;
            if (!Object.prototype.hasOwnProperty.call(payload, fieldName)) return;
            setFinanceFieldValue(form, fieldName, payload[fieldName] || '');
        });

        const titleInput = $('recordTitleInput');
        const currentTitle = String(titleInput?.value || '').trim();
        const defaultTitle = generateDefaultRecordTitle(currentModuleKey);
        if (titleInput && (!preserveExisting || !currentTitle || currentTitle === defaultTitle)) {
            titleInput.value = linkedPoRecord?.record_title || linkedDvRecord?.record_title || currentTitle;
        }

        renderDrawerPreview();
    }

    function getBankAccountCodeValue(bankAccountId) {
        const bankRecord = getRecordById(bankAccountId) || getRecordByLookupValue('bank_account', bankAccountId);
        const linkedCoaId = bankRecord?.data?.linked_coa_id || '';
        const linkedCoaRecord = linkedCoaId ? (getRecordById(linkedCoaId) || getRecordByLookupValue('chart_account', linkedCoaId)) : null;

        return linkedCoaRecord?.record_number
            || linkedCoaRecord?.data?.account_code
            || getLookupLabel('chart_account', linkedCoaId)
            || linkedCoaId
            || bankRecord?.record_number
            || '';
    }

    function syncIbtfAccountCodes({ changedField = '', preserveExisting = false } = {}) {
        if (currentModuleKey !== 'ibtf') return;

        const form = $('financeForm');
        if (!form) return;

        const map = {
            source_bank_account_id: 'source_account_code',
            destination_bank_account_id: 'destination_account_code',
        };

        Object.entries(map).forEach(([accountField, codeField]) => {
            if (changedField && changedField !== accountField) return;

            const accountValue = form.querySelector(`[name="data[${accountField}]"]`)?.value || '';
            const codeInput = form.querySelector(`[name="data[${codeField}]"]`);
            if (!codeInput) return;

            const currentCode = String(codeInput.value || '').trim();
            if (preserveExisting && currentCode) return;

            codeInput.value = getBankAccountCodeValue(accountValue);
            financeFormValues[codeField] = codeInput.value;
            financeFormValues[`data[${codeField}]`] = codeInput.value;
        });
    }

    function syncPrRequesterFields({ preserveExisting = false } = {}) {
        if (currentModuleKey !== 'pr') return;

        const form = $('financeForm');
        if (!form) return;

        const requesterMode = form.querySelector('select[name="data[requester_mode]"]')?.value || financeFormValues.requester_mode || 'own_request';
        const autoValues = getPrRequesterDefaults();
        const shouldUseOwnRequest = requesterMode !== 'request_for_another';
        const fieldsToSync = {
            requestor: autoValues.requestor,
            employee_name: autoValues.employee_name,
            employee_email: autoValues.employee_email,
            employee_id: autoValues.employee_id,
            contact_number: autoValues.contact_number,
            position: autoValues.position,
            department: autoValues.department,
            superior: autoValues.superior,
            superior_email: autoValues.superior_email,
        };

        Object.entries(fieldsToSync).forEach(([fieldName, autoValue]) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (!input) return;

            const currentValue = String(input.value || '').trim();
            const nextValue = shouldUseOwnRequest
                ? (preserveExisting && currentValue ? currentValue : (autoValue || ''))
                : (preserveExisting && currentValue && currentValue !== (autoValue || '') ? currentValue : '');

            input.value = nextValue;
            setReadonlyState(input, shouldUseOwnRequest && Boolean(autoValue));
            financeFormValues[fieldName] = nextValue;
            financeFormValues[`data[${fieldName}]`] = nextValue;
        });

        financeFormValues.requester_mode = requesterMode;
        financeFormValues['data[requester_mode]'] = requesterMode;
    }

    function syncPrVendorFields({ preserveExisting = false } = {}) {
        if (currentModuleKey !== 'pr') return;

        const form = $('financeForm');
        if (!form) return;

        const supplierSelect = form.querySelector('select[name="data[supplier_id]"]');
        const supplierValue = supplierSelect?.value || financeFormValues.supplier_id || '';
        const supplierRecord = getPrSupplierRecord(supplierValue);
        const autoValues = getPrSupplierAutofillValues(supplierRecord);
        const shouldAutofill = Boolean(supplierRecord);
        const fieldsToSync = [
            'new_vendor',
            'vendor_id_number',
            'vendors_tin',
            'company_name',
            'vendor_address',
            'city',
            'province',
            'zip',
            'vendor_phone',
            'vendor_email',
        ];

        fieldsToSync.forEach((fieldName) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (!input) return;

            const currentValue = String(input.value || '').trim();
            const nextValue = shouldAutofill
                ? (preserveExisting && currentValue ? currentValue : (autoValues[fieldName] ?? ''))
                : (preserveExisting && currentValue && currentValue !== (autoValues[fieldName] ?? '') ? currentValue : '');

            input.value = nextValue;
            financeFormValues[fieldName] = nextValue;
            financeFormValues[`data[${fieldName}]`] = nextValue;
        });

        if (shouldAutofill) {
            const newVendorInput = form.querySelector('[name="data[new_vendor]"]');
            if (newVendorInput) {
                newVendorInput.value = 'No';
                financeFormValues.new_vendor = 'No';
                financeFormValues['data[new_vendor]'] = 'No';
            }
        }

        financeFormValues.supplier_id = supplierValue;
        financeFormValues['data[supplier_id]'] = supplierValue;
    }

    function syncPrRequestDetails({ preserveExisting = false } = {}) {
        syncRequestOwnershipFields({ preserveExisting });
        syncPrVendorFields({ preserveExisting });
    }

    function updateCashAdvanceReleaseValues(changedFieldName = '') {
        if (currentModuleKey !== 'ca') return;

        const form = $('financeForm');
        if (!form) return;

        const amountInput = form.querySelector('input[name="data[amount_requested]"]');
        const releaseCountInput = form.querySelector('input[name="data[release_count]"]');
        const amountPerReleaseInput = form.querySelector('input[name="data[amount_per_release]"]');
        const scheduleInput = form.querySelector('[name="data[release_schedule]"]');
        const amount = parseFloat(amountInput?.value || '0') || 0;
        let releaseCount = parseInt(releaseCountInput?.value || '1', 10);

        if (!Number.isFinite(releaseCount) || releaseCount < 1) {
            releaseCount = 1;
        }

        if (scheduleInput && !scheduleInput.value) {
            scheduleInput.value = 'Full Release';
        }

        if (changedFieldName === 'release_schedule' && scheduleInput?.value === 'Full Release' && releaseCountInput) {
            releaseCountInput.value = '1';
            releaseCount = 1;
        }

        if (changedFieldName === 'release_count' && releaseCount > 1 && scheduleInput) {
            scheduleInput.value = 'Staggered Release';
        }

        if (amountPerReleaseInput) {
            amountPerReleaseInput.value = (amount / releaseCount).toFixed(2);
        }

        if ($('amountInput')) {
            $('amountInput').value = amount.toFixed(2);
        }
    }

    function formatPrQuantity(value) {
        const numeric = Number(value);
        if (!Number.isFinite(numeric)) {
            return String(value || '0');
        }

        return Number.isInteger(numeric) ? String(numeric) : numeric.toFixed(2).replace(/\.00$/, '');
    }

    function renderLineItemLookupOptions(source, selectedValue = '', blankLabel = 'Leave blank') {
        const options = financeLookupOptions[source] || [];
        return [
            `<option value="">${escapeHtml(blankLabel)}</option>`,
            ...options.map((option) => {
                const value = String(option.id ?? option.value ?? '');
                const label = option.label ?? option.record_title ?? option.value ?? value;
                return `<option value="${escapeHtml(value)}" ${String(selectedValue || '') === value ? 'selected' : ''}>${escapeHtml(label)}</option>`;
            }),
        ].join('');
    }

    function getLineItemLookupLabel(source, value, fallback = '') {
        return getLookupLabel(source, value) || fallback || '';
    }

    function renderPrLineItemCostSummary(row) {
        const quantity = Number(row?.quantity || 0) || 0;
        const unitCost = Number(row?.amount || 0) || 0;
        const total = Number(row?.total || quantity * unitCost) || 0;

        return `
            <div class="mt-2 rounded-lg border border-blue-100 bg-blue-50/70 px-3 py-2 text-[11px] text-blue-700">
                <span class="font-semibold uppercase tracking-[0.18em]">Cost Summary</span>
                <span class="ml-2 text-blue-900">${escapeHtml(formatPrQuantity(quantity))} x ${escapeHtml(formatCurrency(unitCost))} = ${escapeHtml(formatCurrency(total))}</span>
            </div>
        `;
    }

    function isLineItemModule() {
        return currentModuleKey === 'pr' || currentModuleKey === 'po' || currentModuleKey === 'err' || currentModuleKey === 'lr';
    }

    function isLiquidationModule() {
        return currentModuleKey === 'lr';
    }

    function getLineItemItemSuggestions() {
        if (isLiquidationModule()) {
            return [
                'Transportation / Fuel',
                'Meals / Per Diem',
                'Lodging / Accommodation',
                'Registration / Conference / Fees',
                'Office Supplies / Minor Purchases',
                'Materials / Tools',
                'Communication / Internet / Mobile',
                'Site-Related Expenses',
                'Miscellaneous Business Expenses',
                'Other Expense',
            ];
        }

        if (currentModuleKey === 'po') {
            return [
                ...(financeLookupOptions.product || []).map((option) => option.label || option.value || ''),
                ...(financeLookupOptions.service || []).map((option) => option.label || option.value || ''),
            ].filter(Boolean);
        }

        const productLookup = financeLookupOptions.product || [];
        return productLookup.map((option) => option.label || option.value || '').filter(Boolean);
    }

    function getLineItemCategorySuggestions() {
        if (isLiquidationModule()) {
            return [
                'Transportation',
                'Meals',
                'Lodging',
                'Registration',
                'Office Supplies',
                'Materials',
                'Communication',
                'Site-Related',
                'Miscellaneous',
                'Other',
            ];
        }

        return [
            'Office Supplies',
            'IT Hardware',
            'Printing / Reproduction',
            'Cleaning Supplies',
            'Pantry Supplies',
            'Furniture / Fixtures',
            'Maintenance / Repair',
            'Other',
        ];
    }

    function getPreviewFieldValue(record, fieldName, moduleConfig) {
        const field = (moduleConfig.fields || []).find((item) => item.name === fieldName);
        const rawValue = getFieldValue(record, fieldName);
        if (!field) {
            return rawValue;
        }

        return getFormDisplayValue(field, rawValue, record.data || {});
    }

    function renderPreviewSectionTable(record, moduleConfig, title, fieldNames) {
        const entries = fieldNames.map((fieldName) => {
            const field = (moduleConfig.fields || []).find((item) => item.name === fieldName);
            if (!field) {
                return null;
            }

            return {
                label: field.label,
                value: getPreviewFieldValue(record, fieldName, moduleConfig) || 'N/A',
            };
        }).filter(Boolean);

        return `
            <div class="finance-preview-box">
                <div class="finance-preview-inner">
                    <table class="finance-preview-details">
                        ${chunkArray(entries, 2).map((row) => `
                            <tr>
                                ${row.map((entry) => `
                                    <td>
                                        <p class="finance-preview-label">${escapeHtml(entry.label)}</p>
                                        <p class="finance-preview-value">${escapeHtml(entry.value)}</p>
                                    </td>
                                `).join('')}
                                ${Array.from({ length: 2 - row.length }).map(() => '<td></td>').join('')}
                            </tr>
                        `).join('')}
                    </table>
                </div>
            </div>
        `;
    }

    function renderFinanceReviewNotesSection(record) {
        const note = String(record.review_note || '').trim();
        const reviewer = record.approved_by || record.submitted_by || record.user || 'Finance Team';
        const noteDate = formatDate(record.approved_at || record.submitted_at || record.updated_at || '');

        return `
            <div class="finance-preview-box">
                <div class="finance-preview-section-title">Review Notes</div>
                <div class="finance-preview-inner">
                    ${note ? `
                        <div class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-3">
                            <div class="flex items-center justify-between gap-3 text-[11px] text-gray-500">
                                <div>
                                    <span class="font-semibold text-gray-800">${escapeHtml(reviewer)}</span>
                                </div>
                                <div>${escapeHtml(noteDate && noteDate !== 'N/A' ? noteDate : '')}</div>
                            </div>
                            <div class="mt-1 text-[11px] uppercase tracking-wide text-amber-700">Review Note</div>
                            <div class="mt-2 whitespace-pre-line text-sm text-gray-900">${escapeHtml(note)}</div>
                        </div>
                    ` : `
                        <p class="finance-preview-muted">No review notes yet.</p>
                    `}
                </div>
            </div>
        `;
    }

    function renderDvPreviewLineItems(record) {
        const rows = Array.isArray(record?.data?.line_items) ? record.data.line_items : [];
        const cleanRows = rows.filter((row) => row && Object.values(row).some((value) => String(value || '').trim() !== ''));

        return `
            <div class="finance-preview-box">
                <div class="finance-preview-section-title">Breakdown / Line Items</div>
                <div class="finance-preview-inner">
                    ${cleanRows.length ? `
                        <table class="finance-preview-details">
                            <tr>
                                <td><p class="finance-preview-label">Description</p></td>
                                <td><p class="finance-preview-label">Account Code</p></td>
                                <td><p class="finance-preview-label">Debit</p></td>
                                <td><p class="finance-preview-label">Credit</p></td>
                            </tr>
                            ${cleanRows.map((row) => `
                                <tr>
                                    <td><p class="finance-preview-value">${escapeHtml(row.description || 'N/A')}</p></td>
                                    <td><p class="finance-preview-value">${escapeHtml(row.account_code || 'N/A')}</p></td>
                                    <td><p class="finance-preview-value">${escapeHtml(formatCurrency(row.debit || 0))}</p></td>
                                    <td><p class="finance-preview-value">${escapeHtml(formatCurrency(row.credit || 0))}</p></td>
                                </tr>
                            `).join('')}
                        </table>
                    ` : '<p class="text-sm text-gray-500">No line items added.</p>'}
                </div>
            </div>
        `;
    }

    function getModulePreviewSections(record) {
        const data = record.data || {};
        const supplierIsPending = data.completion_mode === 'send_to_supplier' && !record.supplier_completed_at;

        switch (record.module_key) {
            case 'supplier':
                if (supplierIsPending) {
                    return [];
                }

                return [
                    { title: 'Supplier Profile', fieldNames: ['completion_mode', 'trade_name', 'supplier_type', 'representative_full_name', 'designation', 'email_address', 'phone_number', 'alternate_contact_number'] },
                    { title: 'Business & Billing', fieldNames: ['business_address', 'billing_address', 'tin', 'vat_status', 'payment_terms', 'accreditation_status'] },
                    { title: 'Banking & Notes', fieldNames: ['bank_name', 'bank_account_name', 'bank_account_number', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'service':
                return [
                    { title: 'Service Profile', fieldNames: ['service_description', 'products_services_provided', 'supplier_id', 'coa_id', 'category', 'unit_of_measure', 'default_cost'] },
                    { title: 'Classification & Notes', fieldNames: ['tax_type', 'service_status', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'product':
                return [
                    { title: 'Product Profile', fieldNames: ['product_description', 'supplier_id', 'coa_id', 'category', 'unit_of_measure', 'default_cost'] },
                    { title: 'Classification & Notes', fieldNames: ['tax_type', 'product_status', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'chart_account':
                return [
                    { title: 'Account Profile', fieldNames: ['account_description', 'is_sub_account', 'parent_account_id', 'account_type', 'account_group'] },
                    { title: 'Bank Profile', fieldNames: ['bank_account_name', 'bank_profile', 'bank_account_number'] },
                    { title: 'Balance & Status', fieldNames: ['normal_balance', 'account_status', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'bank_account':
                return [
                    { title: 'Bank Profile', fieldNames: ['bank_name', 'branch', 'currency', 'account_type', 'bank_status'] },
                    { title: 'Accounting Link & Notes', fieldNames: ['linked_coa_id', 'signatory_notes', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'pr':
                return [
                    { title: 'Request Details', fieldNames: ['requesting_department', 'request_type', 'priority', 'needed_date', 'for_client', 'pr_reason_categories'] },
                    { title: 'Requester Details', fieldNames: ['requester_mode', 'requestor', 'employee_id', 'employee_email', 'contact_number', 'position', 'superior', 'superior_email'] },
                    { title: 'Vendor Details', fieldNames: ['supplier_id', 'new_vendor', 'vendor_id_number', 'vendors_tin', 'company_name', 'vendor_phone', 'vendor_email', 'vendor_address', 'city', 'province', 'zip'] },
                    { title: 'Items / Cost Details', renderer: () => renderPrPreviewTable(record) },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'po':
                return [
                    { title: 'Order Details', fieldNames: ['linked_pr_id', 'supplier_id', 'expected_delivery_date', 'delivery_address', 'terms_and_conditions'] },
                    { title: 'Items / Cost Details', renderer: () => renderPrPreviewTable(record) },
                    { title: 'Purpose & Notes', fieldNames: ['purpose', 'remarks', 'coa_id'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'ca':
                return [
                    { title: 'Request Details', fieldNames: ['requester_mode', 'requestor', 'department', 'purpose', 'needed_date', 'mode_of_release', 'amount_requested', 'release_schedule', 'release_count', 'amount_per_release', 'liquidation_calculation', 'cash_release_date', 'cash_release_time', 'paid_through'] },
                    { title: 'Funding & Notes', fieldNames: ['bank_account_id', 'coa_id', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'lr':
                return [
                    { title: 'Liquidation Details', fieldNames: ['requester_mode', 'linked_ca_id', 'total_cash_advance', 'purpose'] },
                    { title: 'Requester Details', fieldNames: ['employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'] },
                    { type: 'line_items', renderer: () => renderLiquidationPreviewTable(record) },
                    { type: 'cost_summary', renderer: () => renderLiquidationPreviewSummary(record) },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'err':
                return [
                    { title: 'Reimbursement Details', fieldNames: ['requester_mode', 'linked_lr_id', 'requestor', 'expense_details', 'amount', 'reimbursement_payment_details', 'supplier_id', 'reimbursement_mode'] },
                    { title: 'Accounting & Funding', fieldNames: ['coa_id', 'bank_account_id', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'dv':
                return [
                    { title: 'Voucher Details', fieldNames: ['source_document_type', 'source_document_id', 'supplier_id', 'amount', 'payment_type', 'disbursement_type', 'payment_date', 'due_date'] },
                    { title: 'Accounting & Notes', fieldNames: ['bank_account_id', 'coa_id', 'fund_source', 'department', 'reference_number', 'purpose', 'remarks'] },
                    { renderer: () => renderDvPreviewLineItems(record) },
                    { title: 'Tax & Receipt', fieldNames: ['withholding_tax', 'vat_amount', 'net_amount', 'currency', 'exchange_rate', 'received_by_name', 'received_by_signature', 'date_received'] },
                    { title: 'Signatories', fieldNames: ['prepared_by', 'checked_by', 'approved_by_name'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'pda':
                return [
                    { title: 'Payroll Details', fieldNames: ['total_payroll_amount', 'department', 'funding_bank_account_id', 'payroll_expense_coa_id'] },
                    { title: 'Supporting Notes', fieldNames: ['supporting_payroll_summary', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'crf':
                return [
                    { title: 'Return Details', fieldNames: ['requester_mode', 'requestor', 'linked_lr_id', 'amount_returned', 'mode_of_return', 'receiving_bank_account_id', 'coa_id'] },
                    { title: 'Reference & Notes', fieldNames: ['reference_number', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'ibtf':
                return [
                    { title: 'Transfer Details', fieldNames: ['source_bank_account_id', 'destination_bank_account_id', 'amount', 'reason'] },
                    { title: 'Reference & Notes', fieldNames: ['source_account_code', 'destination_account_code', 'transfer_reference_number', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'arf':
                return [
                    { title: 'Asset Details', fieldNames: ['linked_po_id', 'linked_dv_id', 'supplier_id', 'asset_code', 'asset_description', 'asset_category', 'serial_number', 'model'] },
                    {
                        type: 'asset_tag',
                        title: 'Asset Tag',
                        assetCode: data.asset_code || record.record_number || 'N/A',
                        location: data.location || 'N/A',
                        serialNumber: data.serial_number || 'N/A',
                        barcodeSvg: generateFinanceBarcodeSvg(data.asset_code || record.record_number || ''),
                    },
                    { title: 'Valuation & Custody', fieldNames: ['acquisition_cost', 'acquisition_date', 'asset_coa_id', 'location', 'custodian', 'useful_life', 'residual_value', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            default:
                return [
                    { title: 'Record Details', fieldNames: ['status'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
        }
    }

    function resolvePrItemId(value) {
        const cleaned = String(value || '').trim().toLowerCase();
        const lookups = currentModuleKey === 'po'
            ? [financeLookupOptions.product || [], financeLookupOptions.service || []]
            : [financeLookupOptions.product || []];

        for (const lookup of lookups) {
            const match = lookup.find((option) => {
                const optionId = String(option.id || '').trim().toLowerCase();
                const optionLabel = String(option.label || '').trim().toLowerCase();
                return optionId === cleaned || optionLabel === cleaned;
            });

            if (match) {
                return match.id;
            }
        }

        return '';
    }

    function resolvePrCategory(value) {
        const cleaned = String(value || '').trim().toLowerCase();
        if (cleaned === 'service' || cleaned === 'product') {
            return cleaned;
        }
        return '';
    }

    function renderPrLineItemsTable(record) {
        const rows = getPrLineItemRows(record);
        const itemOptionsHtml = getLineItemItemSuggestions()
            .map((label) => `<option value="${escapeHtml(label)}"></option>`)
            .join('');
        const categoryOptionsHtml = getLineItemCategorySuggestions()
            .map((label) => `<option value="${escapeHtml(label)}"></option>`)
            .join('');
        const isLiquidation = isLiquidationModule();
        const title = isLiquidation ? 'Liquidation / Cost Details' : 'Line Items';
        const description = isLiquidation
            ? 'Enter up to 25 line items. Totals auto-calculate.'
            : 'Add as many items as you need.';
        const addButtonLabel = isLiquidation ? '+ Add row' : '+ Add Item';
        const removeButtonLabel = isLiquidation ? 'Remove row' : 'Remove';

        return `
            <div data-pr-line-items-section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-700">${escapeHtml(title)}</h5>
                        <p class="mt-1 max-w-2xl text-xs text-gray-500">${escapeHtml(description)}</p>
                    </div>
                    <button type="button" onclick="window.financeModule.addPrLineItemRow()" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">
                        ${escapeHtml(addButtonLabel)}
                    </button>
                </div>

                <div id="prLineItemsBody" data-pr-line-items-body class="mt-5 space-y-4">
                    ${rows.map((row, index) => `
                        <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4 shadow-sm" data-pr-line-item-row data-row-index="${index}">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white">${index + 1}</span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">Line Item ${index + 1}</p>
                                        <p class="text-xs text-gray-500">Fill the fields below to calculate the line total automatically.</p>
                                    </div>
                                </div>
                                <button type="button" onclick="window.financeModule.removePrLineItemRow(this)" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-red-600 hover:bg-red-50">${escapeHtml(removeButtonLabel)}</button>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Item</label>
                                        <input
                                            type="text"
                                            name="data[line_items][${index}][item_id]"
                                            data-pr-line-item-field="item_id"
                                            value="${escapeHtml(getPrItemDisplayValue(row.item_id))}"
                                            class="w-full rounded-xl border border-gray-200 bg-white p-3"
                                            placeholder="${escapeHtml(isLiquidation ? 'Type or select item' : 'Type or select item')}"
                                            list="prItemOptions"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Description</label>
                                        <input type="text" name="data[line_items][${index}][description]" data-pr-line-item-field="description" value="${escapeHtml(row.description || '')}" class="w-full rounded-xl border border-gray-200 bg-white p-3" placeholder="Item description">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Category</label>
                                        <input
                                            type="text"
                                            name="data[line_items][${index}][category]"
                                            data-pr-line-item-field="category"
                                            value="${escapeHtml(getPrCategoryDisplayValue(row.category))}"
                                            class="w-full rounded-xl border border-gray-200 bg-white p-3"
                                            placeholder="${escapeHtml(isLiquidation ? 'Type or select expense category' : 'Type or select purchase category')}"
                                            list="prCategoryOptions"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Qty</label>
                                        <input type="number" step="0.01" min="0" name="data[line_items][${index}][quantity]" data-pr-line-item-field="quantity" value="${escapeHtml(row.quantity || '')}" class="w-full rounded-xl border border-gray-200 bg-white p-3" placeholder="0">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Unit Cost / Amount</label>
                                        <input type="number" step="0.01" min="0" name="data[line_items][${index}][amount]" data-pr-line-item-field="amount" value="${escapeHtml(row.amount || '')}" class="w-full rounded-xl border border-gray-200 bg-white p-3" placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Supplier</label>
                                        <select name="data[line_items][${index}][supplier_id]" data-pr-line-item-field="supplier_id" class="w-full rounded-xl border border-gray-200 bg-white p-3">
                                            ${renderLineItemLookupOptions('supplier', row.supplier_id || '', 'Unknown / leave blank')}
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Client</label>
                                        <select name="data[line_items][${index}][client_id]" data-pr-line-item-field="client_id" class="w-full rounded-xl border border-gray-200 bg-white p-3">
                                            ${renderLineItemLookupOptions('client', row.client_id || '', 'Not for client / leave blank')}
                                        </select>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-blue-100 bg-white p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-blue-600">Cost Summary</p>
                                            <p class="mt-1 text-xs text-gray-500">Each item has its own adjustment values.</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 space-y-4">
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-center">
                                            <label class="text-sm font-semibold text-blue-900">Subtotal:</label>
                                            <input type="number" step="0.01" min="0" name="data[line_items][${index}][subtotal]" data-pr-line-item-field="subtotal" value="${escapeHtml(row.subtotal || '')}" class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-right font-semibold text-blue-900" placeholder="0.00" readonly>
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-center">
                                            <label class="text-sm font-semibold text-blue-900">Discount:</label>
                                            <select name="data[line_items][${index}][discount]" data-pr-line-item-field="discount" class="w-full rounded-xl border border-gray-200 bg-white p-3 font-semibold">
                                                ${['0%', '5%', '10%', '15%', '20%', '25%', '30%'].map((option) => `
                                                    <option value="${escapeHtml(option)}" ${String(row.discount || '0%') === option ? 'selected' : ''}>${escapeHtml(option)}</option>
                                                `).join('')}
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-center">
                                            <label class="text-sm font-semibold text-blue-900">Discount Amount:</label>
                                            <input type="number" step="0.01" min="0" name="data[line_items][${index}][discount_amount]" data-pr-line-item-field="discount_amount" value="${escapeHtml(row.discount_amount || '')}" class="w-full rounded-xl border border-gray-200 bg-white p-3 text-right font-semibold" placeholder="0.00">
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-center">
                                            <label class="text-sm font-semibold text-blue-900">Shipping:</label>
                                            <input type="number" step="0.01" min="0" name="data[line_items][${index}][shipping_amount]" data-pr-line-item-field="shipping_amount" value="${escapeHtml(row.shipping_amount || '')}" class="w-full rounded-xl border border-gray-200 bg-white p-3 text-right font-semibold" placeholder="0.00">
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-center">
                                            <label class="text-sm font-semibold text-blue-900">Tax (VAT/Non-VAT/N/A):</label>
                                            <select name="data[line_items][${index}][tax_type]" data-pr-line-item-field="tax_type" class="w-full rounded-xl border border-gray-200 bg-white p-3 font-semibold">
                                                ${['VAT', 'Non-VAT', 'N/A'].map((option) => `
                                                    <option value="${escapeHtml(option)}" ${String(row.tax_type || 'N/A') === option ? 'selected' : ''}>${escapeHtml(option)}</option>
                                                `).join('')}
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-center">
                                            <label class="text-sm font-semibold text-blue-900">Tax Amount:</label>
                                            <input type="number" step="0.01" min="0" name="data[line_items][${index}][tax_amount]" data-pr-line-item-field="tax_amount" value="${escapeHtml(row.tax_amount || '')}" class="w-full rounded-xl border border-gray-200 bg-white p-3 text-right font-semibold text-blue-900" placeholder="0.00">
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-center">
                                            <label class="text-sm font-semibold text-blue-900">WHT:</label>
                                            <input type="number" step="0.01" min="0" name="data[line_items][${index}][wht_amount]" data-pr-line-item-field="wht_amount" value="${escapeHtml(row.wht_amount || '')}" class="w-full rounded-xl border border-gray-200 bg-white p-3 text-right font-semibold" placeholder="0.00">
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-center">
                                            <label class="text-sm font-semibold text-blue-900">Grand Total:</label>
                                            <input type="number" step="0.01" min="0" name="data[line_items][${index}][total]" data-pr-line-item-field="total" value="${escapeHtml(row.total || '')}" class="w-full rounded-xl border border-blue-200 bg-gray-50 p-3 text-right font-semibold text-blue-900" placeholder="0.00" readonly>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold text-gray-900" data-pr-line-item-formula>${escapeHtml(formatPrQuantity(row.quantity || 0))} x ${escapeHtml(formatCurrency(row.amount || 0))} = ${escapeHtml(formatCurrency(row.total || (Number(row.quantity || 0) * Number(row.amount || 0))))}</p>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <datalist id="prItemOptions">
                    ${itemOptionsHtml}
                </datalist>
                <datalist id="prCategoryOptions">
                    ${categoryOptionsHtml}
                </datalist>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${currentModuleKey === 'po' ? `
                        <input type="hidden" name="data[linked_item_type]" data-pr-primary-field="linked_item_type" value="">
                        <input type="hidden" name="data[linked_item_id]" data-pr-primary-field="linked_item_id" value="">
                        <input type="hidden" name="data[quantity]" data-pr-primary-field="quantity" value="">
                        <input type="hidden" name="data[unit_cost]" data-pr-primary-field="unit_cost" value="">
                        <input type="hidden" name="data[total_amount]" data-pr-primary-field="total_amount" value="">
                    ` : `
                        <input type="hidden" name="data[master_item_type]" data-pr-primary-field="master_item_type" value="">
                        <input type="hidden" name="data[master_item_id]" data-pr-primary-field="master_item_id" value="">
                        <input type="hidden" name="data[description_specification]" data-pr-primary-field="description_specification" value="">
                        <input type="hidden" name="data[quantity]" data-pr-primary-field="quantity" value="">
                        <input type="hidden" name="data[unit_cost]" data-pr-primary-field="unit_cost" value="">
                        <input type="hidden" name="data[estimated_total_cost]" data-pr-primary-field="estimated_total_cost" value="">
                    `}
                </div>
            </div>
        `;
    }

    function renderPrCostSummary(record) {
        const values = record ? record.data || {} : {};
        if (isLiquidationModule()) {
            const statusMeta = getLiquidationStatusMeta(values.variance || record?.data?.variance || 0);
            return `
                <div data-liquidation-status-panel class="rounded-xl border ${statusMeta.border} ${statusMeta.bg} p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.24em] ${statusMeta.text}">Liquidation Status</p>
                            <p data-liquidation-status-label class="mt-1 text-lg font-semibold text-gray-900">${escapeHtml(statusMeta.label)}</p>
                        </div>
                        <span data-liquidation-status-badge class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusMeta.badge}">${escapeHtml(statusMeta.label)}</span>
                    </div>
                    <p data-liquidation-status-message class="mt-2 text-sm text-gray-700">${escapeHtml(statusMeta.message)}</p>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg border border-white/80 bg-white px-3 py-2">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">${escapeHtml(statusMeta.amountLabel)}</p>
                            <p data-liquidation-status-amount class="mt-1 font-semibold text-gray-900">${escapeHtml(formatCurrency(statusMeta.amountValue || 0))}</p>
                        </div>
                        <div class="rounded-lg border border-white/80 bg-white px-3 py-2">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Route</p>
                            <p data-liquidation-route-label class="mt-1 font-semibold text-gray-900">${escapeHtml(statusMeta.indicator === 'Shortage' ? 'ERR' : (statusMeta.indicator === 'Overage' ? 'Cash Return' : 'No follow-up'))}</p>
                        </div>
                    </div>
                    <div data-liquidation-route-actions class="mt-4 flex flex-wrap gap-3 ${statusMeta.indicator === 'Balanced' ? 'hidden' : ''}">
                        <button type="button" data-liquidation-route-open onclick="window.financeModule.openPendingLiquidationBranch()" class="rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">
                            Open ${escapeHtml(statusMeta.indicator === 'Shortage' ? 'ERR' : 'Cash Return')}
                        </button>
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-slate-50 p-4">
                    <h5 class="text-sm font-semibold text-gray-700">Cost Summary</h5>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        ${[
                            ['subtotal', 'Subtotal'],
                            ['discount_total', 'Discount Total'],
                            ['tax_total', 'Tax Total'],
                            ['shipping_total', 'Shipping Total'],
                            ['wht_total', 'WHT Total'],
                            ['grand_total', 'Grand Total'],
                        ].map(([name, label]) => `
                            <div>
                                <label class="block text-sm font-medium mb-1">${escapeHtml(label)}</label>
                                <input type="number" step="0.01" min="0" name="data[${name}]" value="${escapeHtml(financeFormValues[name] ?? values[name] ?? '')}" class="w-full border rounded-md p-2 bg-white" ${name === 'subtotal' || name === 'grand_total' ? 'readonly' : ''}>
                            </div>
                        `).join('')}
                    </div>
                    <input type="hidden" name="data[actual_expenses]" value="${escapeHtml(financeFormValues.actual_expenses ?? values.actual_expenses ?? '')}">
                    <input type="hidden" name="data[variance]" value="${escapeHtml(financeFormValues.variance ?? values.variance ?? '')}">
                    <input type="hidden" name="data[variance_indicator]" value="${escapeHtml(financeFormValues.variance_indicator ?? values.variance_indicator ?? '')}">
                </div>
            `;
        }

        const getValue = (name) => financeFormValues[name] ?? values[name] ?? '';
        const discountValue = getValue('discount') || '0%';
        const taxTypeValue = getValue('tax_type') || 'N/A';

        return `
            <input type="hidden" name="data[subtotal]" value="${escapeHtml(getValue('subtotal'))}">
            <input type="hidden" name="data[discount]" value="${escapeHtml(discountValue)}">
            <input type="hidden" name="data[discount_amount]" value="${escapeHtml(getValue('discount_amount'))}">
            <input type="hidden" name="data[shipping_amount]" value="${escapeHtml(getValue('shipping_amount'))}">
            <input type="hidden" name="data[tax_type]" value="${escapeHtml(taxTypeValue)}">
            <input type="hidden" name="data[tax_amount]" value="${escapeHtml(getValue('tax_amount'))}">
            <input type="hidden" name="data[wht_amount]" value="${escapeHtml(getValue('wht_amount'))}">
            <input type="hidden" name="data[grand_total]" value="${escapeHtml(getValue('grand_total'))}">
        `;
    }

    function renderLiquidationReportSummary(record) {
        const values = record ? record.data || {} : {};
        const statusMeta = getLiquidationStatusMeta(values.variance || record?.data?.variance || 0);
        const linkedCaLabel = getLookupLabel('ca', values.linked_ca_id) || values.linked_ca_id || 'N/A';
        const requestorLabel = values.employee_name || values.requestor || bootstrap.currentUserName || 'N/A';

        return `
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-700">Liquidation Report</h5>
                        <p class="mt-1 text-xs text-gray-500">A concise summary of the liquidation result and balances.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">${escapeHtml(statusMeta.label)}</span>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-gray-100 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">CA Reference No.</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 break-words">${escapeHtml(linkedCaLabel)}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">CA Amount</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(values.total_cash_advance || 0))}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Actual Expenses</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(values.actual_expenses || 0))}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Variance</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(values.variance || 0))}</p>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div class="rounded-xl border border-gray-100 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Report Status</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(statusMeta.label)}</p>
                        <p class="mt-1 text-xs text-gray-500">${escapeHtml(statusMeta.message)}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Requester</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(requestorLabel)}</p>
                        <p class="mt-1 text-xs text-gray-500">${escapeHtml(values.department || values.position || 'Requested liquidation details')}</p>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPrPreviewTable(record) {
        const rows = getPrLineItemRows(record);
        const productLookup = financeLookupOptions.product || [];
        const lookupLabel = (id) => {
            const match = productLookup.find((option) => String(option.id) === String(id));
            return match ? match.label : (id || 'N/A');
        };
        const summaryLookup = {
            subtotal: record.amount || '0.00',
            discount: record.data?.discount || '0%',
            discount_amount: record.data?.discount_amount || '0.00',
            shipping_amount: record.data?.shipping_amount || '0.00',
            tax_type: record.data?.tax_type || 'N/A',
            tax_amount: record.data?.tax_amount || '0.00',
            wht_amount: record.data?.wht_amount || '0.00',
            grand_total: record.data?.grand_total || record.amount || '0.00',
        };

        return `
            <div class="mt-6 rounded-2xl border border-gray-200 bg-white/95 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-700">Items / Cost Details</h5>
                        <p class="mt-1 text-xs text-gray-500">A cleaner breakdown of each item and its calculated total.</p>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    ${rows.map((row, index) => `
                        <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white">${index + 1}</span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">${escapeHtml(lookupLabel(row.item_id))}</p>
                                        <p class="text-xs text-gray-500">${escapeHtml(row.category || 'N/A')} | ${escapeHtml(formatPrQuantity(row.quantity || 0))} pcs</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(row.total || (Number(row.quantity || 0) * Number(row.amount || 0))))}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Description</p>
                                    <p class="mt-1 text-sm text-gray-900 break-words">${escapeHtml(row.description || 'N/A')}</p>
                                </div>
                                <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Unit Cost</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(row.amount || 0))}</p>
                                </div>
                                <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Line Total</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(row.total || (Number(row.quantity || 0) * Number(row.amount || 0))))}</p>
                                </div>
                                ${record?.module_key === 'pr' ? `
                                    <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Supplier</p>
                                        <p class="mt-1 text-sm text-gray-900 break-words">${escapeHtml(getLineItemLookupLabel('supplier', row.supplier_id, 'Blank'))}</p>
                                    </div>
                                    <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Client</p>
                                        <p class="mt-1 text-sm text-gray-900 break-words">${escapeHtml(getLineItemLookupLabel('client', row.client_id, 'Blank'))}</p>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                                ${[
                                    ['Subtotal', row.subtotal || (Number(row.quantity || 0) * Number(row.amount || 0))],
                                    ['Discount', row.discount_amount || '0.00'],
                                    ['Shipping', row.shipping_amount || '0.00'],
                                    ['Tax', row.tax_amount || '0.00'],
                                    ['WHT', row.wht_amount || '0.00'],
                                    ['Item Total', row.total || (Number(row.quantity || 0) * Number(row.amount || 0))],
                                ].map(([label, value]) => `
                                    <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">${escapeHtml(label)}</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(value || 0))}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h6 class="text-sm font-semibold text-gray-700">Cost Summary</h6>
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Receipt</span>
                        </div>
                        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-4 font-mono">
                            ${[
                                ['Subtotal', summaryLookup.subtotal],
                                ['Discount Amount', summaryLookup.discount_amount],
                                ['Shipping Amount', summaryLookup.shipping_amount],
                                ['Tax Amount', summaryLookup.tax_amount],
                                ['WHT Amount', summaryLookup.wht_amount],
                            ].map(([label, value]) => `
                                <div class="flex items-start justify-between gap-4 border-b border-dashed border-gray-200 py-2 last:border-b-0">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                    <p class="text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(value || 0))}</p>
                                </div>
                            `).join('')}
                            <div class="mt-3 flex items-start justify-between gap-4 border-t border-gray-300 pt-3">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.26em] text-gray-500">Grand Total</p>
                                    <p class="mt-1 text-xs text-gray-500">Final amount after all item adjustments</p>
                                </div>
                                <p class="text-lg font-bold text-gray-900">${escapeHtml(formatCurrency(summaryLookup.grand_total || 0))}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h6 class="text-sm font-semibold text-gray-700">Notes</h6>
                            <span class="rounded-full bg-gray-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Reference</span>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-3">
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Purpose / Justification</p>
                                <p class="mt-1 text-sm font-medium text-gray-900 break-words">${escapeHtml(record.data?.purpose || 'N/A')}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Remarks</p>
                                <p class="mt-1 text-sm font-medium text-gray-900 break-words">${escapeHtml(record.data?.remarks || 'N/A')}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Chart of Account</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 break-words">${escapeHtml(getLookupLabel('chart_account', record.data?.coa_id) || record.data?.coa_id || 'N/A')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderLiquidationPreviewTable(record) {
        const rows = getPrLineItemRows(record);

        return `
            <div class="mt-6 rounded-2xl border border-gray-200 bg-white/95 p-5">
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[860px] border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700">
                                <th class="border border-gray-200 px-3 py-2 text-left w-12">#</th>
                                <th class="border border-gray-200 px-3 py-2 text-left">Item</th>
                                <th class="border border-gray-200 px-3 py-2 text-left">Description</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-32">Category</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-24">Qty</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-32">Unit Cost</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-32">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows.map((row, index) => `
                                <tr>
                                    <td class="border border-gray-200 px-3 py-2 font-semibold text-blue-700">${index + 1}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(getPrItemDisplayValue(row.item_id) || 'N/A')}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.description || 'N/A')}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.category || 'N/A')}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.quantity || '0')}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.amount || '0.00')}</td>
                                    <td class="border border-gray-200 px-3 py-2 font-semibold">${escapeHtml(row.total || '0.00')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderLiquidationPreviewSummary(record) {
        const data = record?.data || {};
        const statusMeta = getLiquidationStatusMeta(data.variance || 0);
        const summaryLookup = {
            subtotal: data.subtotal || '0.00',
            discount_total: data.discount_total || '0.00',
            tax_total: data.tax_total || '0.00',
            shipping_total: data.shipping_total || '0.00',
            wht_total: data.wht_total || '0.00',
            grand_total: data.grand_total || record.amount || '0.00',
        };

        return `
            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border ${statusMeta.border} ${statusMeta.bg} p-4 md:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.24em] ${statusMeta.text}">Liquidation Status</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">${escapeHtml(statusMeta.label)}</p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusMeta.badge}">${escapeHtml(statusMeta.label)}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">${escapeHtml(statusMeta.message)}</p>
                    <div class="mt-3 flex items-center justify-between gap-4 rounded-lg border border-white/80 bg-white px-3 py-2">
                        <span class="text-sm text-gray-500">${escapeHtml(statusMeta.amountLabel)}</span>
                        <span class="font-semibold text-gray-900">${escapeHtml(formatCurrency(statusMeta.amountValue || 0))}</span>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-slate-50 p-4">
                    <div class="mt-2 space-y-2 text-sm">
                        ${[
                            ['Subtotal', summaryLookup.subtotal],
                            ['Discount Total', summaryLookup.discount_total],
                            ['Tax Total', summaryLookup.tax_total],
                            ['Shipping Total', summaryLookup.shipping_total],
                            ['WHT Total', summaryLookup.wht_total],
                            ['Grand Total', summaryLookup.grand_total],
                        ].map(([label, value]) => `
                            <div class="flex items-center justify-between gap-4 border-b border-dashed border-gray-200 pb-2 last:border-b-0">
                                <span class="text-gray-500">${escapeHtml(label)}</span>
                                <span class="font-semibold text-gray-900">${escapeHtml(String(value || '0.00'))}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mt-2 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-gray-500">Justification / Business Need</span>
                            <span class="font-medium text-gray-900 text-right break-words max-w-[60%]">${escapeHtml(data.purpose || 'N/A')}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-gray-500">Remarks</span>
                            <span class="font-medium text-gray-900 text-right break-words max-w-[60%]">${escapeHtml(data.remarks || 'N/A')}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderLiquidationReportSection(record, fallbackValues = {}) {
        const data = record?.data || {};
        const linkedCaId = fallbackValues['data[linked_ca_id]'] || data.linked_ca_id || '';
        const linkedCaLabel = getLookupLabel('ca', linkedCaId) || linkedCaId || 'N/A';
        const totalCashAdvance = fallbackValues['data[total_cash_advance]'] || data.total_cash_advance || '0.00';
        const forClient = fallbackValues['data[for_client]'] || data.for_client || 'N/A';
        const clientNames = fallbackValues['data[client_names]'] || data.client_names || 'N/A';
        const lineItemsTotal = fallbackValues['data[line_items_total]'] || data.line_items_total || '0.00';
        const actualExpenses = fallbackValues['data[actual_expenses]'] || data.actual_expenses || '0.00';
        const variance = fallbackValues['data[variance]'] || data.variance || '0.00';
        const varianceIndicator = fallbackValues['data[variance_indicator]'] || data.variance_indicator || getLiquidationStatusMeta(variance).label;
        const statusMeta = getLiquidationStatusMeta(variance);

        return `
            <div class="rounded-2xl border ${statusMeta.border} ${statusMeta.bg} p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.26em] ${statusMeta.text}">Liquidation Value Statement</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">${escapeHtml(varianceIndicator)}</p>
                        <p class="mt-1 text-sm text-gray-700">Built from the slider fields and the current item totals.</p>
                    </div>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusMeta.badge}">${escapeHtml(statusMeta.label)}</span>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-[1.35fr_0.85fr]">
                    <div class="rounded-xl border border-white/80 bg-white p-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">CA Reference No.</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 break-words">${escapeHtml(linkedCaLabel)}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">CA Amount</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(totalCashAdvance))}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Line Items Total</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(lineItemsTotal))}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">For Client?</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 break-words">${escapeHtml(forClient)}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3 sm:col-span-2">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Client Name(s)</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 break-words">${escapeHtml(clientNames)}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Actual Expenses</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(actualExpenses))}</p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-dashed border-blue-200 bg-blue-50/60 px-4 py-4">
                            <p class="text-[11px] uppercase tracking-[0.24em] text-blue-700">Calculation Band</p>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">CA Amount</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(totalCashAdvance))}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Less Actual Expenses</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">- ${escapeHtml(formatCurrency(actualExpenses))}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Variance</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(variance))}</p>
                                </div>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-gray-900">Line Items Total = Sum of all item totals</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-white/80 bg-white p-4">
                        <div class="space-y-3">
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Variance Indicator</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(varianceIndicator)}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Status Note</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">${escapeHtml(statusMeta.message)}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Purpose / Business Need</p>
                                <p class="mt-1 text-sm font-medium text-gray-900 break-words">${escapeHtml(fallbackValues['data[purpose]'] || data.purpose || 'N/A')}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Remarks</p>
                                <p class="mt-1 text-sm font-medium text-gray-900 break-words">${escapeHtml(fallbackValues['data[remarks]'] || data.remarks || 'N/A')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function getPrPrimaryFieldMap() {
        if (currentModuleKey === 'po') {
            return {
                linked_item_id: 'item_id',
                quantity: 'quantity',
                unit_cost: 'amount',
                total_amount: 'total',
            };
        }

        return {
            master_item_id: 'item_id',
            description_specification: 'description',
            quantity: 'quantity',
            unit_cost: 'amount',
            estimated_total_cost: 'total',
        };
    }

    function updatePrTotals() {
        if (!isLineItemModule()) return;

        const rows = Array.from(document.querySelectorAll('[data-pr-line-item-row]'));
        let subtotal = 0;
        let discountAmountTotal = 0;
        let shippingAmountTotal = 0;
        let taxAmountTotal = 0;
        let whtAmountTotal = 0;
        let grandTotal = 0;

        rows.forEach((row) => {
            const quantity = parseFloat(row.querySelector('[data-pr-line-item-field="quantity"]')?.value || '0') || 0;
            const amount = parseFloat(row.querySelector('[data-pr-line-item-field="amount"]')?.value || '0') || 0;
            const subtotalInput = row.querySelector('[data-pr-line-item-field="subtotal"]');
            const discountPercentInput = row.querySelector('[data-pr-line-item-field="discount"]');
            const discountInput = row.querySelector('[data-pr-line-item-field="discount_amount"]');
            const shippingInput = row.querySelector('[data-pr-line-item-field="shipping_amount"]');
            const taxInput = row.querySelector('[data-pr-line-item-field="tax_amount"]');
            const whtInput = row.querySelector('[data-pr-line-item-field="wht_amount"]');
            const totalInput = row.querySelector('[data-pr-line-item-field="total"]');
            const formulaText = row.querySelector('[data-pr-line-item-formula]');
            const rowSubtotal = quantity * amount;
            const discountPercent = parseFloat(String(discountPercentInput?.value || '0').replace('%', '')) || 0;
            const manualDiscountAmount = parseFloat(discountInput?.value || '0') || 0;
            const discountAmount = discountPercent > 0 ? rowSubtotal * (discountPercent / 100) : manualDiscountAmount;
            const shippingAmount = parseFloat(shippingInput?.value || '0') || 0;
            const taxAmount = parseFloat(taxInput?.value || '0') || 0;
            const whtAmount = parseFloat(whtInput?.value || '0') || 0;
            const lineTotal = rowSubtotal - discountAmount + shippingAmount + taxAmount - whtAmount;

            subtotal += rowSubtotal;
            discountAmountTotal += discountAmount;
            shippingAmountTotal += shippingAmount;
            taxAmountTotal += taxAmount;
            whtAmountTotal += whtAmount;
            grandTotal += lineTotal;

            if (subtotalInput) {
                subtotalInput.value = rowSubtotal.toFixed(2);
            }
            if (discountInput && discountPercent > 0) {
                discountInput.value = discountAmount.toFixed(2);
            }
            if (totalInput) {
                totalInput.value = lineTotal.toFixed(2);
                totalInput.readOnly = true;
                totalInput.classList.add('bg-gray-50');
                totalInput.classList.remove('bg-white');
            }
            if (formulaText) {
                formulaText.textContent = `${formatPrQuantity(quantity)} x ${formatCurrency(amount)} = ${formatCurrency(lineTotal)}`;
            }
        });

        const amountInput = $('amountInput');
        const form = $('financeForm');

        if (isLiquidationModule()) {
            const subtotalInput = form.querySelector('input[name="data[subtotal]"]');
            const discountInput = form.querySelector('input[name="data[discount_total]"]');
            const taxInput = form.querySelector('input[name="data[tax_total]"]');
            const shippingInput = form.querySelector('input[name="data[shipping_total]"]');
            const whtInput = form.querySelector('input[name="data[wht_total]"]');
            const grandTotalInput = form.querySelector('input[name="data[grand_total]"]');
            const actualExpensesInput = form.querySelector('input[name="data[actual_expenses]"]');
            const varianceInput = form.querySelector('input[name="data[variance]"]');
            const varianceIndicatorInput = form.querySelector('input[name="data[variance_indicator]"]');
            const caAmountInput = form.querySelector('input[name="data[total_cash_advance]"]');
            const caAmount = parseFloat(caAmountInput?.value || '0') || 0;
            const actualExpenses = grandTotal;
            const variance = caAmount - actualExpenses;
            const statusMeta = getLiquidationStatusMeta(variance);
            const statusPanel = form.querySelector('[data-liquidation-status-panel]');
            const statusBadge = form.querySelector('[data-liquidation-status-badge]');
            const statusLabel = form.querySelector('[data-liquidation-status-label]');
            const statusAmount = form.querySelector('[data-liquidation-status-amount]');
            const statusMessage = form.querySelector('[data-liquidation-status-message]');
            const routeLabel = form.querySelector('[data-liquidation-route-label]');
            const routeActions = form.querySelector('[data-liquidation-route-actions]');
            const routeOpenButton = form.querySelector('[data-liquidation-route-open]');

            if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
            if (discountInput && !document.activeElement?.isSameNode(discountInput)) discountInput.value = discountAmountTotal.toFixed(2);
            if (taxInput && !document.activeElement?.isSameNode(taxInput)) taxInput.value = taxAmountTotal.toFixed(2);
            if (shippingInput && !document.activeElement?.isSameNode(shippingInput)) shippingInput.value = shippingAmountTotal.toFixed(2);
            if (whtInput && !document.activeElement?.isSameNode(whtInput)) whtInput.value = whtAmountTotal.toFixed(2);
            if (grandTotalInput) grandTotalInput.value = actualExpenses.toFixed(2);
            if (actualExpensesInput) actualExpensesInput.value = actualExpenses.toFixed(2);
            if (varianceInput) varianceInput.value = variance.toFixed(2);
            if (varianceIndicatorInput) {
                varianceIndicatorInput.value = statusMeta.indicator;
            }
            if (amountInput) amountInput.value = grandTotal.toFixed(2);
            if (statusPanel) {
                statusPanel.className = `rounded-xl border p-4 ${statusMeta.border} ${statusMeta.bg}`;
            }
            if (statusBadge) {
                statusBadge.className = `inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusMeta.badge}`;
                statusBadge.textContent = statusMeta.label;
            }
            if (statusLabel) {
                statusLabel.textContent = statusMeta.indicator;
            }
            if (statusAmount) {
                statusAmount.textContent = formatCurrency(statusMeta.amountValue || 0);
            }
            if (statusMessage) {
                statusMessage.textContent = statusMeta.message;
            }
            pendingLiquidationBranchDraft = buildCurrentLiquidationDraft();
            if (routeLabel) {
                routeLabel.textContent = statusMeta.indicator === 'Shortage' ? 'ERR' : (statusMeta.indicator === 'Overage' ? 'Cash Return' : 'No follow-up');
            }
            if (routeActions) {
                routeActions.classList.toggle('hidden', statusMeta.indicator === 'Balanced');
            }
            if (routeOpenButton) {
                routeOpenButton.textContent = `Open ${statusMeta.indicator === 'Shortage' ? 'ERR' : 'Cash Return'}`;
            }
            return;
        }

        const subtotalInput = form.querySelector('input[name="data[subtotal]"]');
        const discountInput = form.querySelector('[name="data[discount]"]');
        const discountAmountInput = form.querySelector('input[name="data[discount_amount]"]');
        const shippingInput = form.querySelector('input[name="data[shipping_amount]"]');
        const taxTypeInput = form.querySelector('[name="data[tax_type]"]');
        const taxAmountInput = form.querySelector('input[name="data[tax_amount]"]');
        const whtInput = form.querySelector('input[name="data[wht_amount]"]');
        const grandTotalInput = form.querySelector('input[name="data[grand_total]"]');
        const discount = discountAmountTotal;
        const shipping = shippingAmountTotal;
        const taxAmount = taxAmountTotal;
        const wht = whtAmountTotal;
        grandTotal = subtotal - discount + shipping + taxAmount - wht;

        if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
        if (discountInput && !discountInput.value) discountInput.value = financeFormValues.discount || '0%';
        if (discountAmountInput) discountAmountInput.value = discount.toFixed(2);
        if (grandTotalInput) grandTotalInput.value = grandTotal.toFixed(2);
        if (shippingInput) shippingInput.value = shipping.toFixed(2);
        if (taxAmountInput) taxAmountInput.value = taxAmount.toFixed(2);
        if (whtInput) whtInput.value = wht.toFixed(2);
        if (taxTypeInput && !taxTypeInput.value) taxTypeInput.value = financeFormValues.tax_type || 'N/A';
        if (amountInput) amountInput.value = grandTotal.toFixed(2);
        syncPrPrimaryFields();
    }

    function bindPurchaseRequestLineItemRow(row) {
        if (!row || row.dataset.prBound === '1') return;
        row.dataset.prBound = '1';

        row.querySelectorAll('input, select, textarea').forEach((input) => {
            input.addEventListener('input', () => {
                updatePrTotals();
                renderDrawerPreview();
            });
            input.addEventListener('change', () => {
                updatePrTotals();
                renderDrawerPreview();
            });
        });
    }

    function bindPurchaseRequestLineItems() {
        if (!isLineItemModule()) return;

        document.querySelectorAll('[data-pr-line-item-row]').forEach((row) => bindPurchaseRequestLineItemRow(row));
        updatePrTotals();
    }

    function syncPrPrimaryFields() {
        if (currentModuleKey !== 'pr' && currentModuleKey !== 'po') return;

        const firstRow = Array.from(document.querySelectorAll('[data-pr-line-item-row]')).find((row) => {
            return Array.from(row.querySelectorAll('[data-pr-line-item-field]')).some((input) => String(input.value || '').trim() !== '');
        }) || document.querySelector('[data-pr-line-item-row]');
        const primaryMap = getPrPrimaryFieldMap();
        const form = $('financeForm');
        if (!form) return;

        Object.entries(primaryMap).forEach(([primaryField, rowField]) => {
            const target = form.querySelector(`[data-pr-primary-field="${primaryField}"]`);
            const source = firstRow?.querySelector(`[data-pr-line-item-field="${rowField}"]`);
            if (target) {
                if (primaryField === 'master_item_id' || primaryField === 'linked_item_id') {
                    target.value = source ? resolvePrItemId(source.value) : '';
                } else {
                    target.value = source ? (source.value || '') : '';
                }
            }
        });

        const typeTarget = form.querySelector('[data-pr-primary-field="master_item_type"]');
        if (typeTarget) {
            typeTarget.value = 'product';
        }

        const poTypeTarget = form.querySelector('[data-pr-primary-field="linked_item_type"]');
        if (poTypeTarget) {
            const itemLabel = String(firstRow?.querySelector('[data-pr-line-item-field="item_id"]')?.value || '').trim().toLowerCase();
            const serviceLookup = financeLookupOptions.service || [];
            const productLookup = financeLookupOptions.product || [];
            const matchedService = serviceLookup.find((option) => String(option.label || option.value || '').trim().toLowerCase() === itemLabel);
            const matchedProduct = productLookup.find((option) => String(option.label || option.value || '').trim().toLowerCase() === itemLabel);
            poTypeTarget.value = matchedService ? 'service' : (matchedProduct ? 'product' : 'product');
        }
    }

    function addPrLineItemRow() {
        if (!isLineItemModule()) return;

        const tbody = $('prLineItemsBody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('[data-pr-line-item-row]')).map((row) => {
            const getValue = (field) => row.querySelector(`[data-pr-line-item-field="${field}"]`)?.value || '';
            return {
                item_id: getValue('item_id'),
                description: getValue('description'),
                category: getValue('category'),
                quantity: getValue('quantity'),
                amount: getValue('amount'),
                subtotal: getValue('subtotal'),
                discount: getValue('discount'),
                discount_amount: getValue('discount_amount'),
                shipping_amount: getValue('shipping_amount'),
                tax_type: getValue('tax_type'),
                tax_amount: getValue('tax_amount'),
                wht_amount: getValue('wht_amount'),
                total: getValue('total'),
                supplier_id: getValue('supplier_id'),
                client_id: getValue('client_id'),
            };
        });
        replaceCurrentLineItemSection([...rows, {}]);
        updatePrTotals();
    }

    function removePrLineItemRow(button) {
        if (!isLineItemModule()) return;
        const row = button?.closest('[data-pr-line-item-row]');
        const tbody = $('prLineItemsBody');
        if (!row || !tbody) return;

        if (tbody.querySelectorAll('[data-pr-line-item-row]').length <= 1) {
            row.querySelectorAll('input, select').forEach((input) => {
                input.value = '';
            });
            updatePrTotals();
            return;
        }

        row.remove();
        Array.from(tbody.querySelectorAll('[data-pr-line-item-row]')).forEach((tr, index) => {
            tr.setAttribute('data-row-index', String(index));
            const badge = tr.querySelector('.inline-flex.h-8.w-8');
            if (badge) {
                badge.textContent = String(index + 1);
            }
            const title = tr.querySelector('p.text-sm.font-semibold.text-gray-800');
            if (title) {
                title.textContent = `Line Item ${index + 1}`;
            }
            tr.querySelectorAll('[name]').forEach((input) => {
                const name = input.getAttribute('name');
                input.setAttribute('name', name.replace(/data\[line_items\]\[\d+\]/, `data[line_items][${index}]`));
            });
            bindPurchaseRequestLineItemRow(tr);
        });
        updatePrTotals();
    }

    let financeFormValues = {};

    function getModuleFieldValue(record, field) {
        if (!record) return '';
        if (record.data && Object.prototype.hasOwnProperty.call(record.data, field.name)) {
            return record.data[field.name];
        }
        return '';
    }

    function getFormDisplayValue(field, value, formValues) {
        if (field.type === 'checkbox') {
            return value ? 'Yes' : 'No';
        }

        if (field.type === 'checkbox-group') {
            if (Array.isArray(value)) {
                return value.length ? value.join(', ') : 'N/A';
            }

            const text = String(value || '').trim();
            return text ? text : 'N/A';
        }

        if (field.type === 'radio-group') {
            if (Array.isArray(value)) {
                return value.length ? value.join(', ') : 'N/A';
            }

            const text = String(value || '').trim();
            return text ? text : 'N/A';
        }

        if (field.type === 'number') {
            return formatCurrency(value);
        }

        if (field.type === 'calculation') {
            if (field.name === 'liquidation_calculation') {
                return getCashAdvanceLiquidationCalculation(formValues);
            }

            return value || 'N/A';
        }

        if (field.type === 'date') {
            return value || 'N/A';
        }

        if (field.type === 'time') {
            return value || 'N/A';
        }

        if (field.type === 'select') {
            if (field.source) {
                return getLookupLabel(field.source, value) || value;
            }

            if (field.sourceMap && field.sourceKey) {
                const moduleKey = field.sourceMap[formValues[`data[${field.sourceKey}]`] || formValues[field.sourceKey]];
                return getLookupLabel(moduleKey, value) || value;
            }

            const option = (field.options || []).find((item) => String(item.value) === String(value));
            return option ? option.label : value;
        }

        return value;
    }

    function renderAttachmentList(attachments = []) {
        const target = $('existingAttachmentList');
        if (!attachments.length) {
            target.innerHTML = '<p class="text-xs text-gray-400 italic">No existing attachments.</p>';
            return;
        }

        target.innerHTML = attachments.map((attachment, index) => `
            <div class="flex items-center justify-between gap-3 border rounded-md px-3 py-2 text-sm">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 break-all">${escapeHtml(attachment.name || `Attachment ${index + 1}`)}</p>
                    <p class="text-xs text-gray-500 break-all">${escapeHtml(attachment.category || 'Supporting Document')} - ${escapeHtml(attachment.path || '')}</p>
                </div>
                <a href="${escapeHtml(attachment.url || normalizeAttachmentUrl(attachment.path || ''))}" target="_blank" class="text-blue-600 hover:underline shrink-0">Open</a>
            </div>
        `).join('');
    }

    function wireDynamicFieldEvents() {
        const form = $('financeForm');
        form.querySelectorAll('input, select, textarea').forEach((input) => {
            if (isLineItemModule() && input.closest('[data-pr-line-item-row]')) {
                return;
            }

            input.addEventListener('input', () => {
                if (isLineItemModule()) {
                    updatePrTotals();
                }
                if (currentModuleKey === 'ca') {
                    updateCashAdvanceReleaseValues(input.name?.match(/^data\[(.+)\]$/)?.[1] || '');
                }
                renderDrawerPreview();
            });
            input.addEventListener('change', () => {
                if (isLineItemModule()) {
                    updatePrTotals();
                }
                if (currentModuleKey === 'ca') {
                    updateCashAdvanceReleaseValues(input.name?.match(/^data\[(.+)\]$/)?.[1] || '');
                }
                renderDrawerPreview();
            });
        });

        if (currentModuleKey === 'lr') {
            const linkedCaSelect = form.querySelector('select[name="data[linked_ca_id]"]');
            if (linkedCaSelect) {
                linkedCaSelect.addEventListener('change', () => {
                    fetchLiquidationSource();
                });
            }
        }

        if (currentModuleKey === 'ibtf') {
            ['source_bank_account_id', 'destination_bank_account_id'].forEach((fieldName) => {
                const input = form.querySelector(`[name="data[${fieldName}]"]`);
                if (input) {
                    input.addEventListener('change', () => {
                        syncIbtfAccountCodes({ changedField: fieldName, preserveExisting: false });
                        renderDrawerPreview();
                    });
                }
            });
        }

        if (currentModuleKey === 'err' || currentModuleKey === 'crf') {
            const linkedLrSelect = form.querySelector('select[name="data[linked_lr_id]"]');
            if (linkedLrSelect) {
                linkedLrSelect.addEventListener('change', () => {
                    const expectedIndicator = currentModuleKey === 'err' ? 'Shortage' : 'Overage';
                    const linkedLrRecord = getLinkedLiquidationRecord(linkedLrSelect.value, expectedIndicator);
                    if (!linkedLrRecord) {
                        showFinanceToast(`Choose an approved ${expectedIndicator.toLowerCase()} liquidation report.`, 'warning');
                        return;
                    }

                    financeDraftContext = {
                        moduleKey: currentModuleKey,
                        linkedRecord: linkedLrRecord,
                        prefill: getLiquidationBranchPrefill(currentModuleKey, linkedLrRecord),
                    };
                    renderFinanceForm(currentEditRecordId ? getRecordById(currentEditRecordId) : null);
                    renderDrawerPreview();
                });
            }
        }

        if (currentModuleKey === 'dv') {
            const sourceTypeSelect = form.querySelector('select[name="data[source_document_type]"]');
            const sourceDocumentSelect = form.querySelector('select[name="data[source_document_id]"]');

            if (sourceTypeSelect) {
                sourceTypeSelect.addEventListener('change', () => {
                    const sourceType = sourceTypeSelect.value || '';
                    financeFormValues = financeFormValues || {};
                    financeFormValues.source_document_type = sourceType;
                    financeFormValues['data[source_document_type]'] = sourceType;
                    renderDvSourceDocumentOptions(sourceType, '');
                    clearDvAutoFilledFields();
                    renderDvSourceDocumentInfo(sourceType, null);
                    renderDrawerPreview();
                });
            }

            if (sourceDocumentSelect) {
                sourceDocumentSelect.addEventListener('change', () => {
                    const sourceType = sourceTypeSelect?.value || '';
                    const resolvedRecord = resolveDvSourceRecord(sourceType, sourceDocumentSelect);
                    if (resolvedRecord) {
                        applyDvSourceDocumentSelection(sourceType, resolvedRecord.id || sourceDocumentSelect.value || '');
                        return;
                    }
                    applyDvSourceDocumentSelection(sourceType, sourceDocumentSelect.value || '');
                });
            }

            ['withholding_tax', 'vat_amount'].forEach((fieldName) => {
                const input = form.querySelector(`[name="data[${fieldName}]"]`);
                if (input) {
                    input.addEventListener('input', () => {
                        updateDvNetAmount();
                        renderDrawerPreview();
                    });
                }
            });

            bindDvLineItems();
            updateDvNetAmount();
        }

        if (currentModuleKey === 'pr') {
            const requesterModeSelect = form.querySelector('select[name="data[requester_mode]"]');
            const supplierSelect = form.querySelector('select[name="data[supplier_id]"]');
            const newVendorSelect = form.querySelector('select[name="data[new_vendor]"]');

            if (requesterModeSelect) {
                requesterModeSelect.addEventListener('change', () => {
                    syncRequestOwnershipFields({ preserveExisting: false });
                    forceFillOwnRequesterDetails({ preserveExisting: false });
                    renderDrawerPreview();
                });
            }

            if (supplierSelect) {
                supplierSelect.addEventListener('change', () => {
                    syncPrVendorFields({ preserveExisting: false });
                    renderDrawerPreview();
                });
            }

            if (newVendorSelect) {
                newVendorSelect.addEventListener('change', () => {
                    syncPrVendorFields({ preserveExisting: false });
                    renderDrawerPreview();
                });
            }
        }

        if (isRequestOwnershipModule() && currentModuleKey !== 'pr') {
            const requesterModeSelect = form.querySelector('select[name="data[requester_mode]"]');
            if (requesterModeSelect) {
                requesterModeSelect.addEventListener('change', () => {
                    syncRequestOwnershipFields({ preserveExisting: false });
                    forceFillOwnRequesterDetails({ preserveExisting: false });
                    renderDrawerPreview();
                });
            }
        }

        if (currentModuleKey === 'po') {
            const linkedPrSelect = form.querySelector('select[name="data[linked_pr_id]"]');
            if (linkedPrSelect) {
                linkedPrSelect.addEventListener('change', () => {
                    syncPoLinkedPrFields({ preserveExisting: false });
                });
            }
        }

        if (currentModuleKey === 'arf') {
            const linkedPoSelect = form.querySelector('select[name="data[linked_po_id]"]');
            const linkedDvSelect = form.querySelector('select[name="data[linked_dv_id]"]');

            if (linkedPoSelect) {
                linkedPoSelect.addEventListener('change', () => {
                    syncArfLinkedDocumentFields({ preserveExisting: false });
                });
            }

            if (linkedDvSelect) {
                linkedDvSelect.addEventListener('change', () => {
                    syncArfLinkedDocumentFields({ preserveExisting: false });
                });
            }
        }
    }

    function renderFinanceForm(record = null) {
        const moduleConfig = getModuleConfig(currentModuleKey);
        const draftContext = financeDraftContext && financeDraftContext.moduleKey === currentModuleKey ? financeDraftContext : null;
        const draftLinkedRecord = draftContext?.linkedRecord || null;
        const values = {};
        financeFormValues = values;
        if (currentModuleKey === 'supplier' && record?.data?.completion_mode) {
            supplierCompletionMode = record.data.completion_mode;
        } else if (currentModuleKey !== 'supplier') {
            supplierCompletionMode = 'complete_internally';
        }

        const recordNumberValue = record
            ? (record.record_number || '')
            : generateModuleRecordNumber(currentModuleKey);
        const recordTitleValue = generateDefaultRecordTitle(currentModuleKey, record);
        const recordDateValue = record ? record.record_date || '' : todayDateValue();
        const recordTimeValue = record
            ? (record.data?.transaction_time || currentTimeValue())
            : (draftContext?.prefill?.transaction_time || currentTimeValue());
        const amountValue = record
            ? (record.amount || '')
            : (draftContext?.prefill?.amount || draftContext?.prefill?.amount_returned || '');
        const statusValue = currentModuleKey === 'dv'
            ? (record && !['Active', 'Inactive'].includes(record.status || '') ? record.status : 'Draft')
            : (record ? record.status || 'Active' : 'Active');
        const existingAttachments = record ? (record.attachments || []) : [];
        const activeRecord = record || draftLinkedRecord || null;

        $('financeRecordId').value = record ? record.id : '';
        $('financeModuleKey').value = currentModuleKey;
        $('recordNumberLabel').textContent = moduleConfig.recordNumberLabel;
        $('recordDateLabel').textContent = moduleConfig.recordDateLabel || 'Date';
        $('recordNumberInput').placeholder = `${getModuleRecordPrefix(currentModuleKey)}-00001`;
        const recordTitleLabel = $('recordTitleLabel');
        if (recordTitleLabel) {
            recordTitleLabel.textContent = moduleConfig.recordTitleLabel || `${moduleConfig.label} Name`;
        }
        const recordTitleInput = $('recordTitleInput');
        if (recordTitleInput) {
            recordTitleInput.placeholder = moduleConfig.recordTitleLabel || `${moduleConfig.label} Name`;
        }
        $('recordNumberInput').value = recordNumberValue;
        $('recordTitleInput').value = recordTitleValue;
        $('recordDateInput').value = recordDateValue;
        $('recordTimeInput').value = recordTimeValue;
        $('amountInput').value = amountValue;
        if (currentModuleKey === 'dv') {
            $('statusInput').innerHTML = ['Draft', 'For Approval', 'Approved', 'Released', 'Cancelled']
                .map((status) => `<option value="${status}">${status}</option>`)
                .join('');
        } else {
            $('statusInput').innerHTML = '<option value="Active">Active</option><option value="Inactive">Inactive</option>';
        }
        $('statusInput').value = statusValue;
        $('drawerTitle').textContent = record ? `Edit ${moduleConfig.label}` : `Add ${moduleConfig.label}`;
        $('drawerSubtitle').textContent = isSendToSupplierMode()
            ? 'Enter the supplier email address and send the completion form.'
            : (record
                ? `Update the ${moduleConfig.label.toLowerCase()} record and save changes.`
                : `Create a new ${moduleConfig.label.toLowerCase()} record.`);
        $('drawerPreviewTitle').textContent = record ? `Preview: ${record.display_label || record.record_title || moduleConfig.label}` : 'New Finance Record';
        $('drawerSaveButton').textContent = isSendToSupplierMode() ? 'Send Form' : (record ? 'Update' : 'Save');
        $('existingAttachmentsJson').value = JSON.stringify(existingAttachments);

        setReadonlyState($('recordNumberInput'), true);
        setReadonlyState($('recordDateInput'), currentModuleKey === 'supplier');
        setRecordNumberLocked(true);

        values.transaction_time = recordTimeValue;
        values['data[transaction_time]'] = recordTimeValue;

        const drawerPanel = $('drawerPanel');
        if (drawerPanel) {
            drawerPanel.classList.remove('max-w-[540px]', 'max-w-[680px]', 'max-w-[760px]', 'max-w-[1240px]', 'max-w-none');
            drawerPanel.classList.add('max-w-none');
        }

        const drawerPreviewPane = $('drawerPreviewPane');
        const drawerFormPane = $('drawerFormPane');
        if (drawerPreviewPane && drawerFormPane) {
            drawerPreviewPane.classList.remove('hidden');
            drawerPreviewPane.classList.remove('basis-1/2', 'basis-auto', 'basis-3/5', 'flex-1', 'w-full');
            drawerFormPane.classList.remove('flex-1', 'max-w-none', 'max-w-[540px]', 'max-w-[580px]', 'basis-1/2', 'basis-auto', 'basis-2/5', 'w-full');
            drawerPreviewPane.classList.add('basis-[45%]');
            drawerFormPane.classList.add('basis-[55%]', 'max-w-none');
        }

        renderSupplierModeTabs();
        setSupplierFormLayout();

        const supplierFields = moduleConfig.fields.filter((field) => field.name !== 'completion_mode');
        const fieldsHtml = (() => {
            if (currentModuleKey === 'supplier') {
                const fieldsToRender = isSendToSupplierMode()
                    ? supplierFields.filter((field) => field.name === 'email_address')
                    : supplierFields;

                return [
                    `<input type="hidden" name="data[completion_mode]" value="${escapeHtml(supplierCompletionMode)}">`,
                    ...fieldsToRender.map((field) => {
                        let fieldValue = record ? getModuleFieldValue(record, field) : (values[`data[${field.name}]`] || '');
                        if (!fieldValue && field.autoFillCurrentUser) {
                            fieldValue = bootstrap.currentUserName || '';
                        }
                        values[field.name] = fieldValue;
                        values[`data[${field.name}]`] = fieldValue;
                        return renderDynamicField(field, fieldValue, values);
                    }),
                ].join('');
            }

            if (currentModuleKey === 'lr') {
                const linkedCaId = getDraftValue('linked_ca_id', record);
                const totalCashAdvance = getDraftValue('total_cash_advance', record);
                const purposeValue = getDraftValue('purpose', record);
                const lineItemsTotal = Array.isArray(activeRecord?.data?.line_items)
                    ? activeRecord.data.line_items.reduce((sum, item) => sum + (parseFloat(item?.total || '0') || 0), 0).toFixed(2)
                    : '0.00';
                const hiddenActualExpenses = getDraftValue('actual_expenses', record);
                const hiddenVariance = getDraftValue('variance', record);
                const hiddenVarianceIndicator = getDraftValue('variance_indicator', record);
                const forClientValue = getDraftValue('for_client', record);
                const clientNamesValue = getDraftValue('client_names', record);

                values.linked_ca_id = linkedCaId;
                values['data[linked_ca_id]'] = linkedCaId;
                values.total_cash_advance = totalCashAdvance;
                values['data[total_cash_advance]'] = totalCashAdvance;
                values.line_items_total = lineItemsTotal;
                values['data[line_items_total]'] = lineItemsTotal;
                values.purpose = purposeValue;
                values['data[purpose]'] = purposeValue;
                values.actual_expenses = hiddenActualExpenses;
                values['data[actual_expenses]'] = hiddenActualExpenses;
                values.variance = hiddenVariance;
                values['data[variance]'] = hiddenVariance;
                values.variance_indicator = hiddenVarianceIndicator;
                values['data[variance_indicator]'] = hiddenVarianceIndicator;
                values.for_client = forClientValue;
                values['data[for_client]'] = forClientValue;
                values.client_names = clientNamesValue;
                values['data[client_names]'] = clientNamesValue;

                return `
                    ${draftLinkedRecord ? `
                        <div class="md:col-span-2 rounded-xl border ${draftLinkedRecord.data?.variance_indicator === 'Shortage' ? 'border-red-200 bg-red-50/50' : 'border-emerald-200 bg-emerald-50/50'} p-4">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.24em] ${draftLinkedRecord.data?.variance_indicator === 'Shortage' ? 'text-red-700' : 'text-emerald-700'}">Linked Liquidation Found</h4>
                            <p class="mt-2 text-sm text-gray-700">CA <span class="font-semibold">${escapeHtml(getLookupLabel('ca', draftLinkedRecord.data?.linked_ca_id) || draftLinkedRecord.data?.linked_ca_id || 'N/A')}</span> is marked as <span class="font-semibold">${escapeHtml(draftLinkedRecord.data?.variance_indicator || 'Balanced')}</span>.</p>
                            <p class="mt-1 text-xs text-gray-500">We loaded the linked liquidation items below so the cost details stay in sync.</p>
                        </div>
                    ` : ''}
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Liquidation Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2 rounded-lg border border-gray-200 bg-white px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.24em] text-gray-500">Source</p>
                                <p class="mt-2 inline-flex rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">CA (Cash Advance)</p>
                                <p class="mt-2 text-xs text-gray-500">Currently supports CA only.</p>
                            </div>
                            <div class="space-y-2">
                                ${renderDynamicField(selectField('linked_ca_id', 'CA Reference No.', { source: 'ca', required: true }), linkedCaId, values)}
                                <button type="button" onclick="window.financeModule.fetchLiquidationSource()" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-50">
                                    Fetch
                                </button>
                                <p class="text-xs text-gray-500">Choose the accepted CA record, then fetch its details.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">CA Amount <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" name="data[total_cash_advance]" value="${escapeHtml(totalCashAdvance)}" class="w-full border rounded-md p-2" required>
                                <p class="mt-1 text-xs text-gray-500">Auto-fills from the selected CA.</p>
                            </div>
                            <div class="md:col-span-2">
                                ${renderDynamicField(textareaField('purpose', 'Justification / Business Need', { required: true, fullWidth: true }), purposeValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('for_client', 'For Client?', { readOnly: true }), forClientValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('client_names', 'Client Name(s)', { readOnly: true }), clientNamesValue, values)}
                            </div>
                        </div>
                    </div>

                    ${pendingLiquidationBranchDraft ? `
                        <div data-pending-liquidation-panel class="md:col-span-2 rounded-xl border ${pendingLiquidationBranchDraft.moduleKey === 'err' ? 'border-red-200 bg-red-50/40' : 'border-emerald-200 bg-emerald-50/40'} p-4">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.24em] ${pendingLiquidationBranchDraft.moduleKey === 'err' ? 'text-red-700' : 'text-emerald-700'}">Next Section Available</h4>
                            <p class="mt-2 text-sm text-gray-700">The liquidation result has been calculated. You can open <span class="font-semibold">${escapeHtml(pendingLiquidationBranchDraft.moduleKey === 'err' ? 'ERR' : 'Cash Return')}</span> now, or stay here and continue editing.</p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <button type="button" onclick="window.financeModule.openPendingLiquidationBranch()" class="rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">Open ${escapeHtml(pendingLiquidationBranchDraft.moduleKey === 'err' ? 'ERR' : 'Cash Return')}</button>
                                <button type="button" onclick="window.financeModule.dismissPendingLiquidationBranch()" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Stay Here</button>
                            </div>
                        </div>
                    ` : ''}

                    <div class="md:col-span-2">
                        ${renderLiquidationReportSection(draftLinkedRecord || record, values)}
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Requester Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Choose Own Request to auto-fill your account details, or Request for Another to enter someone else&apos;s information.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requestor', 'employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        ${renderPrLineItemsTable(activeRecord)}
                    </div>

                    <div class="md:col-span-2">
                        ${renderPrCostSummary(activeRecord)}
                    </div>

                    <input type="hidden" name="data[coa_id]" value="${escapeHtml(record ? getModuleFieldValue(record, { name: 'coa_id' }) || '' : (values['data[coa_id]'] || ''))}">
                    <input type="hidden" name="data[linked_dv_id]" value="${escapeHtml(record ? getModuleFieldValue(record, { name: 'linked_dv_id' }) || '' : (values['data[linked_dv_id]'] || ''))}">
                `;
            }

            if (currentModuleKey === 'err') {
                const linkedLrId = getDraftValue('linked_lr_id', record);
                const amountValue = getDraftValue('amount', record);
                const requesterModeValue = getDraftValue('requester_mode', record) || 'own_request';
                const requestorValue = getDraftValue('requestor', record);
                const expenseDetails = getDraftValue('expense_details', record);
                const supplierValue = getDraftValue('supplier_id', record);
                const coaValue = getDraftValue('coa_id', record);
                const reimbursementModeValue = getDraftValue('reimbursement_mode', record);
                const bankAccountValue = getDraftValue('bank_account_id', record);
                const reimbursementPaymentDetails = getDraftValue('reimbursement_payment_details', record);
                const manualLiquidationEntry = getDraftValue('manual_liquidation_entry', record);
                const remarksValue = getDraftValue('remarks', record);
                const linkedLrRecord = draftLinkedRecord || getLinkedLiquidationRecord(linkedLrId, 'Shortage');
                const linkedPrefill = linkedLrRecord ? getLiquidationBranchPrefill('err', linkedLrRecord) : {};

                values.linked_lr_id = linkedLrId;
                values['data[linked_lr_id]'] = linkedLrId;
                values.requester_mode = requesterModeValue;
                values['data[requester_mode]'] = requesterModeValue;
                values.amount = amountValue || linkedPrefill.amount || '';
                values['data[amount]'] = values.amount;
                values.requestor = requestorValue || linkedPrefill.requestor || '';
                values['data[requestor]'] = values.requestor;
                values.expense_details = expenseDetails || linkedPrefill.expense_details || '';
                values['data[expense_details]'] = values.expense_details;
                values.supplier_id = supplierValue;
                values['data[supplier_id]'] = supplierValue;
                values.coa_id = coaValue || linkedPrefill.coa_id || '';
                values['data[coa_id]'] = values.coa_id;
                values.reimbursement_mode = reimbursementModeValue || linkedPrefill.reimbursement_mode || '';
                values['data[reimbursement_mode]'] = values.reimbursement_mode;
                values.bank_account_id = bankAccountValue || linkedPrefill.bank_account_id || '';
                values['data[bank_account_id]'] = values.bank_account_id;
                values.reimbursement_payment_details = reimbursementPaymentDetails || linkedPrefill.reimbursement_payment_details || '';
                values['data[reimbursement_payment_details]'] = values.reimbursement_payment_details;
                values.manual_liquidation_entry = manualLiquidationEntry;
                values['data[manual_liquidation_entry]'] = manualLiquidationEntry;
                values.remarks = remarksValue || linkedPrefill.remarks || '';
                values['data[remarks]'] = values.remarks;

                return `
                    ${renderLinkedLiquidationBranchPanel(linkedLrRecord, 'err', values)}
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Reimbursement Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Choose Own Request to auto-fill your account details, or Request for Another to enter someone else&apos;s information.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requestor', 'linked_lr_id', 'expense_details', 'amount', 'reimbursement_payment_details', 'manual_liquidation_entry', 'supplier_id', 'coa_id', 'reimbursement_mode', 'bank_account_id', 'remarks'], values, record)}
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'crf') {
                const linkedLrId = getDraftValue('linked_lr_id', record);
                const requesterModeValue = getDraftValue('requester_mode', record) || 'own_request';
                const requestorValue = getDraftValue('requestor', record);
                const amountReturnedValue = getDraftValue('amount_returned', record);
                const modeOfReturnValue = getDraftValue('mode_of_return', record);
                const receivingBankValue = getDraftValue('receiving_bank_account_id', record);
                const coaValue = getDraftValue('coa_id', record);
                const referenceNumberValue = getDraftValue('reference_number', record);
                const manualLiquidationEntry = getDraftValue('manual_liquidation_entry', record);
                const remarksValue = getDraftValue('remarks', record);
                const linkedLrRecord = draftLinkedRecord || getLinkedLiquidationRecord(linkedLrId, 'Overage');
                const linkedPrefill = linkedLrRecord ? getLiquidationBranchPrefill('crf', linkedLrRecord) : {};

                values.linked_lr_id = linkedLrId;
                values['data[linked_lr_id]'] = linkedLrId;
                values.requester_mode = requesterModeValue;
                values['data[requester_mode]'] = requesterModeValue;
                values.requestor = requestorValue || linkedPrefill.requestor || '';
                values['data[requestor]'] = values.requestor;
                values.amount_returned = amountReturnedValue || linkedPrefill.amount_returned || '';
                values['data[amount_returned]'] = values.amount_returned;
                values.mode_of_return = modeOfReturnValue || linkedPrefill.mode_of_return || '';
                values['data[mode_of_return]'] = values.mode_of_return;
                values.receiving_bank_account_id = receivingBankValue || linkedPrefill.receiving_bank_account_id || '';
                values['data[receiving_bank_account_id]'] = values.receiving_bank_account_id;
                values.coa_id = coaValue || linkedPrefill.coa_id || '';
                values['data[coa_id]'] = values.coa_id;
                values.reference_number = referenceNumberValue || linkedPrefill.reference_number || '';
                values['data[reference_number]'] = values.reference_number;
                values.manual_liquidation_entry = manualLiquidationEntry;
                values['data[manual_liquidation_entry]'] = manualLiquidationEntry;
                values.remarks = remarksValue || linkedPrefill.remarks || '';
                values['data[remarks]'] = values.remarks;

                return `
                    ${renderLinkedLiquidationBranchPanel(linkedLrRecord, 'crf', values)}
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Return Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Choose Own Request to auto-fill your account details, or Request for Another to enter someone else&apos;s information.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requestor', 'linked_lr_id', 'amount_returned', 'manual_liquidation_entry', 'mode_of_return', 'receiving_bank_account_id', 'coa_id', 'reference_number', 'remarks'], values, record)}
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'dv') {
                const sourceTypeValue = getDraftValue('source_document_type', record);
                const sourceDocumentValue = getDraftValue('source_document_id', record);
                const supplierValue = getDraftValue('supplier_id', record);
                const amountValue = getDraftValue('amount', record);
                const paymentTypeValue = getDraftValue('payment_type', record);
                const disbursementTypeValue = getDraftValue('disbursement_type', record) || paymentTypeValue || 'Cash';
                const bankAccountValue = getDraftValue('bank_account_id', record);
                const coaValue = getDraftValue('coa_id', record);
                const fundSourceValue = getDraftValue('fund_source', record);
                const departmentValue = getDraftValue('department', record);
                const referenceNumberValue = getDraftValue('reference_number', record);
                const purposeValue = getDraftValue('purpose', record);
                const paymentDateValue = getDraftValue('payment_date', record) || todayDateValue();
                const dueDateValue = getDraftValue('due_date', record);
                const withholdingTaxValue = getDraftValue('withholding_tax', record);
                const vatAmountValue = getDraftValue('vat_amount', record);
                const netAmountValue = getDraftValue('net_amount', record);
                const currencyValue = getDraftValue('currency', record) || 'PHP';
                const exchangeRateValue = getDraftValue('exchange_rate', record);
                const preparedByValue = getDraftValue('prepared_by', record) || bootstrap.currentUserName || '';
                const checkedByValue = getDraftValue('checked_by', record);
                const approvedByNameValue = getDraftValue('approved_by_name', record);
                const receivedByNameValue = getDraftValue('received_by_name', record);
                const receivedBySignatureValue = getDraftValue('received_by_signature', record);
                const dateReceivedValue = getDraftValue('date_received', record);
                const remarksValue = getDraftValue('remarks', record);
                const sourceRecord = sourceDocumentValue ? (getRecordByLookupValue(sourceTypeValue, sourceDocumentValue) || getRecordById(sourceDocumentValue) || getRecordByLookupValue('', sourceDocumentValue)) : null;
                const resolvedSourceTypeValue = sourceTypeValue || sourceRecord?.module_key || '';

                values.source_document_type = resolvedSourceTypeValue;
                values['data[source_document_type]'] = resolvedSourceTypeValue;
                values.source_document_id = sourceDocumentValue;
                values['data[source_document_id]'] = sourceDocumentValue;
                values.supplier_id = supplierValue;
                values['data[supplier_id]'] = supplierValue;
                values.amount = amountValue;
                values['data[amount]'] = amountValue;
                values.payment_type = paymentTypeValue;
                values['data[payment_type]'] = paymentTypeValue;
                values.disbursement_type = disbursementTypeValue;
                values['data[disbursement_type]'] = disbursementTypeValue;
                values.bank_account_id = bankAccountValue;
                values['data[bank_account_id]'] = bankAccountValue;
                values.coa_id = coaValue;
                values['data[coa_id]'] = coaValue;
                values.fund_source = fundSourceValue;
                values['data[fund_source]'] = fundSourceValue;
                values.department = departmentValue;
                values['data[department]'] = departmentValue;
                values.reference_number = referenceNumberValue;
                values['data[reference_number]'] = referenceNumberValue;
                values.purpose = purposeValue;
                values['data[purpose]'] = purposeValue;
                values.payment_date = paymentDateValue;
                values['data[payment_date]'] = paymentDateValue;
                values.due_date = dueDateValue;
                values['data[due_date]'] = dueDateValue;
                values.withholding_tax = withholdingTaxValue;
                values['data[withholding_tax]'] = withholdingTaxValue;
                values.vat_amount = vatAmountValue;
                values['data[vat_amount]'] = vatAmountValue;
                values.net_amount = netAmountValue;
                values['data[net_amount]'] = netAmountValue;
                values.currency = currencyValue;
                values['data[currency]'] = currencyValue;
                values.exchange_rate = exchangeRateValue;
                values['data[exchange_rate]'] = exchangeRateValue;
                values.prepared_by = preparedByValue;
                values['data[prepared_by]'] = preparedByValue;
                values.checked_by = checkedByValue;
                values['data[checked_by]'] = checkedByValue;
                values.approved_by_name = approvedByNameValue;
                values['data[approved_by_name]'] = approvedByNameValue;
                values.received_by_name = receivedByNameValue;
                values['data[received_by_name]'] = receivedByNameValue;
                values.received_by_signature = receivedBySignatureValue;
                values['data[received_by_signature]'] = receivedBySignatureValue;
                values.date_received = dateReceivedValue;
                values['data[date_received]'] = dateReceivedValue;
                values.remarks = remarksValue;
                values['data[remarks]'] = remarksValue;

                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Source Document</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                ${renderDynamicField(selectField('source_document_type', 'Linked Source Document Type', {
                                    required: true,
                                    options: [
                                        { value: 'ca', label: 'CA' },
                                        { value: 'lr', label: 'LR' },
                                        { value: 'err', label: 'ERR' },
                                        { value: 'pda', label: 'PDA' },
                                        { value: 'crf', label: 'CRF' },
                                        { value: 'ibtf', label: 'IBTF' },
                                        { value: 'arf', label: 'ARF' },
                                    ],
                                }), resolvedSourceTypeValue, values)}
                            </div>
                            <div>
                                <label id="dvSourceDocumentTitle" class="block text-sm font-medium mb-1">Linked Source Document</label>
                                <select
                                    id="dvSourceDocumentSelect"
                                    name="data[source_document_id]"
                                    class="w-full border rounded-md p-2"
                                    ${resolvedSourceTypeValue ? '' : 'disabled'}
                                    required
                                >
                                    ${getDvSourceDocumentOptionsHtml(resolvedSourceTypeValue, sourceDocumentValue)}
                                </select>
                                <p id="dvSourceDocumentHint" class="mt-2 text-xs text-gray-500">${escapeHtml(resolvedSourceTypeValue ? 'Choose the exact document to auto-fill the internal voucher values. The bank account stays manual.' : 'Choose a source type first.')}</p>
                            </div>
                            <div class="md:col-span-2">
                                <div id="dvSourceDocumentInfo">
                                    ${getDvSourceDocumentInfoHtml(resolvedSourceTypeValue, sourceRecord)}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Voucher Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Amount, supplier, account, payment date, purpose, and remarks are locked from the source document. Bank account, payment type, disbursement type, and reference no. stay editable.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                ${renderDynamicField(textField('supplier_id', 'Supplier', { readOnly: true }), supplierValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(numberField('amount', 'Amount', { readOnly: true }), amountValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(selectField('payment_type', 'Payment Type', {
                                    options: [
                                        { value: 'Cash', label: 'Cash' },
                                        { value: 'Check', label: 'Check' },
                                        { value: 'Bank Transfer', label: 'Bank Transfer' },
                                        { value: 'E-Wallet', label: 'E-Wallet' },
                                    ],
                                }), paymentTypeValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(selectField('disbursement_type', 'Disbursement Type', {
                                    options: [
                                        { value: 'Cash', label: 'Cash' },
                                        { value: 'Check', label: 'Check' },
                                        { value: 'Bank Transfer', label: 'Bank Transfer' },
                                        { value: 'Petty Cash', label: 'Petty Cash' },
                                    ],
                                }), disbursementTypeValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(selectField('bank_account_id', 'Bank Account', { source: 'bank_account' }), bankAccountValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('coa_id', 'Account', { readOnly: true }), coaValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('fund_source', 'Fund Source / Project'), fundSourceValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('department', 'Department'), departmentValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('reference_number', 'Reference Number'), referenceNumberValue, values)}
                            </div>
                            <div class="md:col-span-2">
                                ${renderDynamicField(textareaField('purpose', 'Purpose', { readOnly: true }), purposeValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(dateField('payment_date', 'Payment Date', { readOnly: true }), paymentDateValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(dateField('due_date', 'Due Date'), dueDateValue, values)}
                            </div>
                            <div class="md:col-span-2">
                                ${renderDynamicField(textareaField('remarks', 'Remarks', { readOnly: true }), remarksValue, values)}
                            </div>
                        </div>
                        <input type="hidden" name="data[source_document_id]" value="${escapeHtml(sourceDocumentValue)}">
                        <input type="hidden" name="data[source_document_type]" value="${escapeHtml(resolvedSourceTypeValue)}">
                    </div>

                    ${renderDvLineItemsTable(record)}

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Tax, Currency & Receipt</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderDynamicField(numberField('withholding_tax', 'Withholding Tax (EWT)'), withholdingTaxValue, values)}
                            ${renderDynamicField(numberField('vat_amount', 'VAT'), vatAmountValue, values)}
                            ${renderDynamicField(numberField('net_amount', 'Net Amount', { readOnly: true }), netAmountValue, values)}
                            ${renderDynamicField(textField('currency', 'Currency'), currencyValue, values)}
                            ${renderDynamicField(numberField('exchange_rate', 'Exchange Rate'), exchangeRateValue, values)}
                            ${renderDynamicField(textField('received_by_name', 'Received By'), receivedByNameValue, values)}
                            ${renderDynamicField(textField('received_by_signature', 'Signature'), receivedBySignatureValue, values)}
                            ${renderDynamicField(dateField('date_received', 'Date Received'), dateReceivedValue, values)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Signatories</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            ${renderDynamicField(textField('prepared_by', 'Prepared By'), preparedByValue, values)}
                            ${renderDynamicField(textField('checked_by', 'Verified / Checked By'), checkedByValue, values)}
                            ${renderDynamicField(textField('approved_by_name', 'Approved By'), approvedByNameValue, values)}
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'ca') {
                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Request Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['needed_date', 'priority', 'cash_advance_type', 'other_business_purpose_specify', 'usage_categories', 'other_expense_specify', 'purpose'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Requester Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Choose Own Request to auto-fill your account details, or Request for Another to enter someone else&apos;s information.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requestor', 'employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Client Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['for_client', 'client_names'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Cash Advance Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['amount_requested', 'release_schedule', 'release_count', 'amount_per_release', 'liquidation_calculation', 'cash_release_date', 'cash_release_time', 'mode_of_release', 'paid_through'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Declarations & Authorizations</h4>
                        <div class="mt-4 grid grid-cols-1 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['official_business_cash_advance', 'employee_cash_advance_personal', 'liquidation_non_compliance', 'automatic_salary_deduction_authorization', 'final_pay_deduction_authorization', 'policy_acknowledgment'], values, record)}
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'ca') {
                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Request Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['needed_date', 'priority', 'cash_advance_type', 'other_business_purpose_specify', 'usage_categories', 'other_expense_specify', 'purpose'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Requester Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Choose Own Request to auto-fill your account details, or Request for Another to enter someone else&apos;s information.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'employee_name', 'employee_id', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Client Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['for_client', 'client_names'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Cash Advance Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['amount_requested', 'release_schedule', 'release_count', 'amount_per_release', 'liquidation_calculation', 'cash_release_date', 'cash_release_time', 'mode_of_release', 'paid_through'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Declarations & Authorizations</h4>
                        <div class="mt-4 grid grid-cols-1 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['official_business_cash_advance', 'employee_cash_advance_personal', 'liquidation_non_compliance', 'automatic_salary_deduction_authorization', 'final_pay_deduction_authorization', 'policy_acknowledgment'], values, record)}
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'pr') {
                const requesterModeValue = record
                    ? (getDraftValue('requester_mode', record) || 'request_for_another')
                    : (getDraftValue('requester_mode', record) || 'own_request');
                const requesterDefaults = getPrRequesterDefaults();
                const supplierValue = getDraftValue('supplier_id', record);
                const supplierRecord = getPrSupplierRecord(supplierValue);
                const supplierDefaults = getPrSupplierAutofillValues(supplierRecord);
                const requestorBaseValue = getDraftValue('requestor', record);
                const employeeEmailBaseValue = getDraftValue('employee_email', record);
                const requesterValue = requesterModeValue === 'own_request'
                    ? (requestorBaseValue || requesterDefaults.requestor)
                    : requestorBaseValue;
                const employeeEmailValue = requesterModeValue === 'own_request'
                    ? (employeeEmailBaseValue || requesterDefaults.employee_email)
                    : employeeEmailBaseValue;
                const requestorField = {
                    ...((moduleConfig.fields || []).find((field) => field.name === 'requestor')
                        || textField('requestor', 'Employee Name', { required: true })),
                    readOnly: requesterModeValue === 'own_request',
                };
                const requestorFieldValue = requesterValue || '';
                values.requester_mode = requesterModeValue;
                values['data[requester_mode]'] = requesterModeValue;
                values.requestor = requestorFieldValue;
                values['data[requestor]'] = requestorFieldValue;
                values.employee_email = employeeEmailValue || '';
                values['data[employee_email]'] = employeeEmailValue || '';
                ['employee_id', 'employee_name', 'contact_number', 'position', 'department', 'superior', 'superior_email'].forEach((fieldName) => {
                    const existingValue = getDraftValue(fieldName, record);
                    const defaultValue = getRequesterDefaultForField(fieldName);
                    const nextValue = requesterModeValue === 'own_request'
                        ? (existingValue || defaultValue || '')
                        : existingValue;
                    values[fieldName] = nextValue;
                    values[`data[${fieldName}]`] = nextValue;
                });
                values.supplier_id = supplierValue;
                values['data[supplier_id]'] = supplierValue;
                values.new_vendor = supplierRecord ? 'No' : (getDraftValue('new_vendor', record) || '');
                values['data[new_vendor]'] = values.new_vendor;
                Object.entries(supplierDefaults).forEach(([fieldName, fieldValue]) => {
                    const existingValue = getDraftValue(fieldName, record);
                    const nextValue = existingValue || fieldValue || '';
                    values[fieldName] = nextValue;
                    values[`data[${fieldName}]`] = nextValue;
                });
                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Request Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['requesting_department', 'request_type', 'priority', 'needed_date', 'for_client', 'pr_reason_categories'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Requester Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Choose Own Request to auto-fill your signed-in account details. Choose Request for Another when the request belongs to someone else.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['requester_mode'], values, record)}
                            ${renderDynamicField(requestorField, requestorFieldValue, values)}
                            ${renderFieldsByNames(moduleConfig, ['employee_id', 'employee_email', 'contact_number', 'position', 'superior', 'superior_email'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Vendor Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Selecting a supplier will populate the vendor fields from the selected supplier record.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['supplier_id', 'new_vendor', 'vendor_id_number', 'vendors_tin', 'company_name', 'vendor_address', 'city', 'province', 'zip', 'vendor_phone', 'vendor_email'], values, record)}
                        </div>
                    </div>

                    <input type="hidden" name="data[coa_id]" value="${escapeHtml(record ? getModuleFieldValue(record, { name: 'coa_id' }) || '' : (values['data[coa_id]'] || ''))}">

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Items / Cost Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Add as many items as needed. Totals can be reviewed below and will keep the form spaced out.</p>
                        <div class="mt-4 space-y-4">
                            ${renderPrLineItemsTable(record)}
                            ${renderPrCostSummary(record)}

                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <h5 class="text-sm font-semibold text-gray-700">Purpose & Notes</h5>
                                <div class="mt-4 grid grid-cols-1 gap-4">
                                    ${renderFieldsByNames(moduleConfig, ['purpose', 'remarks'], values, record)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'po') {
                const supplierValue = getDraftValue('supplier_id', record);
                const linkedPrId = getDraftValue('linked_pr_id', record);
                const linkedPrRecord = getRecordById(linkedPrId) || getRecordByLookupValue('pr', linkedPrId);
                const supplierSummary = record
                    ? (getDraftValue('linked_pr_supplier_summary', record) || '')
                    : '';
                values.supplier_id = supplierValue;
                values['data[supplier_id]'] = supplierValue;
                values.linked_pr_id = linkedPrId;
                values['data[linked_pr_id]'] = linkedPrId;
                values.linked_pr_supplier_summary = supplierSummary;
                values['data[linked_pr_supplier_summary]'] = supplierSummary;

                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Order Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['linked_pr_id', 'supplier_id', 'expected_delivery_date', 'delivery_address', 'terms_and_conditions'], values, record)}
                        </div>
                    </div>

                    ${renderPoLinkedPrSupplierSummary(linkedPrRecord)}

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Items / Cost Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Use the same detailed item layout as the purchase request. The live preview updates as you add item rows.</p>
                        <div class="mt-4 space-y-4">
                            ${renderPrLineItemsTable(record)}

                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <h5 class="text-sm font-semibold text-gray-700">Purpose & Notes</h5>
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    ${renderFieldsByNames(moduleConfig, ['coa_id', 'remarks'], values, record)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'arf') {
                const linkedPoId = getDraftValue('linked_po_id', record);
                const linkedDvId = getDraftValue('linked_dv_id', record);
                const assetCodeValue = getDraftValue('asset_code', record) || recordNumberValue;
                const assetDescriptionValue = getDraftValue('asset_description', record);
                const assetCategoryValue = getDraftValue('asset_category', record);
                const serialNumberValue = getDraftValue('serial_number', record);
                const modelValue = getDraftValue('model', record);
                const supplierValue = getDraftValue('supplier_id', record);
                const acquisitionCostValue = getDraftValue('acquisition_cost', record);
                const acquisitionDateValue = getDraftValue('acquisition_date', record);
                const assetCoaValue = getDraftValue('asset_coa_id', record);
                const locationValue = getDraftValue('location', record);
                const custodianValue = getDraftValue('custodian', record);
                const usefulLifeValue = getDraftValue('useful_life', record);
                const residualValue = getDraftValue('residual_value', record);
                const remarksValue = getDraftValue('remarks', record);
                const barcodeSvg = generateFinanceBarcodeSvg(assetCodeValue || record?.record_number || '');

                [
                    ['linked_po_id', linkedPoId],
                    ['linked_dv_id', linkedDvId],
                    ['asset_code', assetCodeValue],
                    ['asset_description', assetDescriptionValue],
                    ['asset_category', assetCategoryValue],
                    ['serial_number', serialNumberValue],
                    ['model', modelValue],
                    ['supplier_id', supplierValue],
                    ['acquisition_cost', acquisitionCostValue],
                    ['acquisition_date', acquisitionDateValue],
                    ['asset_coa_id', assetCoaValue],
                    ['location', locationValue],
                    ['custodian', custodianValue],
                    ['useful_life', usefulLifeValue],
                    ['residual_value', residualValue],
                    ['remarks', remarksValue],
                ].forEach(([fieldName, fieldValue]) => {
                    values[fieldName] = fieldValue;
                    values[`data[${fieldName}]`] = fieldValue;
                });

                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Asset Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['linked_po_id', 'linked_dv_id', 'supplier_id', 'asset_code', 'asset_description', 'asset_category', 'serial_number', 'model'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Asset Tag</h4>
                        <p class="mt-2 text-xs text-gray-500">This tag mirrors the printable plate and updates automatically from the asset code, location, and serial number.</p>
                        <div class="mt-4">
                            ${renderArfAssetTagCard(assetCodeValue, locationValue, serialNumberValue, barcodeSvg)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Valuation & Custody</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['acquisition_cost', 'acquisition_date', 'asset_coa_id', 'location', 'custodian', 'useful_life', 'residual_value', 'remarks'], values, record)}
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'bank_account') {
                const linkedCoaValue = record ? getModuleFieldValue(record, { name: 'linked_coa_id' }) : (values['data[linked_coa_id]'] || '');
                values.linked_coa_id = linkedCoaValue;
                values['data[linked_coa_id]'] = linkedCoaValue;

                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Bank Profile</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['bank_name', 'branch', 'currency', 'bank_status'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Account Link</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                ${renderFieldsByNames(moduleConfig, ['account_type'], values, record)}
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Linked Chart of Account</label>
                                <input type="hidden" name="data[linked_coa_id]" id="bankAccountLookupHidden" value="${escapeHtml(linkedCoaValue)}">
                                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                                    <div class="mb-3">
                                        <input
                                            id="bankAccountLookupSearch"
                                            type="text"
                                            class="w-full rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                                            placeholder="Search chart of accounts..."
                                            value="${escapeHtml(activeBankAccountLookupQuery)}"
                                        >
                                        <p class="mt-2 text-xs text-blue-700/80">The linked chart of account is shown automatically below.</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/60 bg-white/80 px-3 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Selected</p>
                                        <p id="bankAccountLookupDisplay" class="mt-1 text-sm font-medium text-gray-800">${escapeHtml(getLookupLabel('chart_account', linkedCoaValue) || linkedCoaValue || 'Select Linked Chart of Account')}</p>
                                    </div>
                                    <div id="bankAccountLookupList" class="mt-4"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Notes & Attachments</h4>
                        <div class="mt-4 grid grid-cols-1 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['signatory_notes', 'remarks'], values, record)}
                        </div>
                    </div>
                `;
            }

            return supplierFields.map((field) => {
                let fieldValue = record ? getModuleFieldValue(record, field) : (values[`data[${field.name}]`] || '');
                if (!fieldValue && field.autoFillCurrentUser) {
                    fieldValue = bootstrap.currentUserName || '';
                }
                values[field.name] = fieldValue;
                values[`data[${field.name}]`] = fieldValue;
                return renderDynamicField(field, fieldValue, values);
            }).join('');
        })();

        $('dynamicFields').innerHTML = fieldsHtml;
        renderAttachmentList(existingAttachments);
        wireDynamicFieldEvents();
        if (currentModuleKey === 'pr') {
            syncPrRequestDetails({ preserveExisting: true });
        }
        if (isRequestOwnershipModule() && currentModuleKey !== 'pr') {
            syncRequestOwnershipFields({ preserveExisting: true });
        }
        if (currentModuleKey === 'po') {
            syncPoLinkedPrFields({ preserveExisting: true });
        }
        if (currentModuleKey === 'ca') {
            updateCashAdvanceReleaseValues();
        }
        if (currentModuleKey === 'ibtf') {
            syncIbtfAccountCodes({ preserveExisting: true });
        }
        if (currentModuleKey === 'arf') {
            syncArfLinkedDocumentFields({ preserveExisting: true });
        }
        if (currentModuleKey === 'bank_account') {
            renderBankAccountLookupList(activeBankAccountLookupQuery);
        }
        forceFillOwnRequesterDetails({ preserveExisting: true });
        bindPurchaseRequestLineItems();
        renderDrawerPreview();
    }

    function renderDrawerPreview() {
        const moduleConfig = getModuleConfig(currentModuleKey);
        const companyName = 'John Kelly & Company';
        const companyLegalName = 'JK&C INC.';
        const companyLogo = '/images/imaglogo.png';
        const previewDraftContext = financeDraftContext && financeDraftContext.moduleKey === currentModuleKey ? financeDraftContext : null;
        const draftLinkedRecord = previewDraftContext?.linkedRecord || null;
        const formValues = {};
        const formData = new FormData($('financeForm'));
        formData.forEach((value, key) => {
            if (Object.prototype.hasOwnProperty.call(formValues, key)) {
                const current = formValues[key];
                formValues[key] = Array.isArray(current) ? [...current, value] : [current, value];
                return;
            }

            formValues[key] = value;
        });
        const recordNumber = $('recordNumberInput').value.trim();
        const recordDate = $('recordDateInput').value;
        const recordTime = $('recordTimeInput').value;
        const amount = $('amountInput').value;
        const titleLabel = `${moduleConfig.label} Form`.toUpperCase();
        const recordTitleValue = $('recordTitleInput').value.trim() || generateDefaultRecordTitle(currentModuleKey);
        const summaryItems = [
            ['Number', recordNumber || 'N/A'],
            [moduleConfig.recordTitleLabel || 'Name', recordTitleValue || 'N/A'],
            ['Date', recordDate || 'N/A'],
            ['Time', recordTime || 'N/A'],
            ...(shouldShowGenericAmount(currentModuleKey) ? [['Amount', amount || '0.00']] : []),
        ];

        if (currentModuleKey === 'supplier' && isSendToSupplierMode()) {
            const email = $('dynamicFields').querySelector('input[name="data[email_address]"]')?.value || 'N/A';

            $('drawerPreview').innerHTML = `
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8">
                    <div class="mx-auto max-w-md text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Supplier Dispatch Pending</p>
                        <h4 class="mt-3 text-lg font-semibold text-gray-900">Waiting for completion form</h4>
                        <p class="mt-2 text-sm text-gray-600">
                            The supplier completion form has been sent and the preview will remain hidden until it is submitted.
                        </p>
                        <p class="mt-4 text-sm font-semibold text-gray-900 break-words">${escapeHtml(email)}</p>
                    </div>
                </div>
            `;
            return;
        }

        if (currentModuleKey === 'arf') {
            const assetCode = formValues['data[asset_code]'] || $('dynamicFields').querySelector('input[name="data[asset_code]"]')?.value || recordNumber || 'N/A';
            const linkedPo = getLookupLabel('po', formValues['data[linked_po_id]']) || formValues['data[linked_po_id]'] || 'N/A';
            const linkedDv = getLookupLabel('dv', formValues['data[linked_dv_id]']) || formValues['data[linked_dv_id]'] || 'N/A';
            const supplier = getLookupLabel('supplier', formValues['data[supplier_id]']) || formValues['data[supplier_id]'] || 'N/A';
            const assetDescription = formValues['data[asset_description]'] || 'Not filled yet';
            const assetCategory = formValues['data[asset_category]'] || 'Not filled yet';
            const model = formValues['data[model]'] || 'Not filled yet';
            const acquisitionCost = formValues['data[acquisition_cost]'] || '0.00';
            const acquisitionDate = formValues['data[acquisition_date]'] || 'N/A';
            const assetAccount = getLookupLabel('chart_account', formValues['data[asset_coa_id]']) || formValues['data[asset_coa_id]'] || 'N/A';
            const location = formValues['data[location]'] || $('dynamicFields').querySelector('input[name="data[location]"]')?.value || 'N/A';
            const custodian = formValues['data[custodian]'] || 'N/A';
            const usefulLife = formValues['data[useful_life]'] || 'N/A';
            const residualValue = formValues['data[residual_value]'] || '0.00';
            const serialNumber = formValues['data[serial_number]'] || $('dynamicFields').querySelector('input[name="data[serial_number]"]')?.value || 'N/A';
            const barcodeSvg = generateFinanceBarcodeSvg(assetCode === 'N/A' ? '' : assetCode);

            $('drawerPreview').innerHTML = `
                <div class="rounded-2xl border border-slate-200 bg-slate-100 p-4">
                    <div class="mb-3 flex items-center justify-between rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-500 shadow-sm">
                        <span>PDF Holder</span>
                        <span>Live Preview</span>
                    </div>
                    <div class="mx-auto max-w-[760px] overflow-hidden rounded-[6px] border border-gray-300 bg-white shadow-lg">
                        <div class="relative px-5 py-5 text-center border-b border-gray-300 bg-white">
                            <div class="mx-auto flex items-center justify-center rounded-xl bg-white px-4 py-2">
                                <img src="${companyLogo}" alt="${escapeHtml(companyName)}" class="block h-20 w-auto max-w-[200px] object-contain">
                            </div>
                            <div class="mt-3 text-[16px] font-semibold leading-tight text-gray-900">${escapeHtml(companyName)}</div>
                            <div class="text-[10px] font-medium tracking-[0.3em] text-gray-500">${escapeHtml(companyLegalName)}</div>
                        </div>

                        <div class="relative bg-blue-700 px-4 py-2 text-center text-[12px] font-semibold uppercase tracking-[0.32em] text-white">
                            ${escapeHtml(titleLabel)}
                        </div>

                        <div class="relative grid grid-cols-2 border-t border-gray-300 text-sm">
                            ${summaryItems.map(([label, value], index) => `
                                <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                    <p class="mt-1 text-[15px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                                </div>
                            `).join('')}
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Asset Details</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                ${[
                                    ['Linked PO', linkedPo],
                                    ['Linked DV', linkedDv],
                                    ['Supplier', supplier],
                                    ['Asset Code', assetCode],
                                    ['Asset Description', assetDescription],
                                    ['Asset Category', assetCategory],
                                    ['Serial Number', serialNumber],
                                    ['Model', model],
                                ].map(([label, value], index) => `
                                    <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                        <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                        <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Valuation & Custody</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                ${[
                                    ['Acquisition Cost', acquisitionCost],
                                    ['Acquisition Date', acquisitionDate],
                                    ['Asset Account', assetAccount],
                                    ['Location', location],
                                    ['Custodian', custodian],
                                    ['Useful Life', usefulLife],
                                    ['Residual Value', residualValue],
                                    ['Remarks', formValues['data[remarks]'] || 'N/A'],
                                ].map(([label, value], index) => `
                                    <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                        <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                        <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Asset Tag</h4>
                            </div>
                            <div class="relative px-5 py-5 text-center border-b border-gray-300 bg-white">
                                <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.3em] text-gray-500">JK&amp;C INC.</div>
                                <div class="mt-2 text-[20px] font-black uppercase tracking-[0.24em] text-gray-900">ASSET TAG</div>
                            </div>
                            <div class="grid grid-cols-[150px_minmax(0,1fr)] divide-x divide-gray-300">
                                <div class="border-b border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">Asset Code</div>
                                <div class="border-b border-gray-300 px-4 py-3 text-sm font-semibold text-gray-900 break-words">${escapeHtml(assetCode || 'N/A')}</div>
                                <div class="border-b border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">Location</div>
                                <div class="border-b border-gray-300 px-4 py-3 text-sm font-semibold text-gray-900 break-words">${escapeHtml(location || 'N/A')}</div>
                                <div class="border-b border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">Serial Number</div>
                                <div class="border-b border-gray-300 px-4 py-3 text-sm font-semibold text-gray-900 break-words">${escapeHtml(serialNumber || 'N/A')}</div>
                                <div class="bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">Barcode</div>
                                <div class="px-4 py-3">
                                    <div class="rounded-xl border border-gray-200 bg-white px-2 py-2 overflow-hidden">
                                        ${barcodeSvg || '<div class="flex h-20 items-center justify-center text-xs text-gray-400">Enter an asset code to generate the barcode.</div>'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        if (currentModuleKey === 'lr') {
            const rows = Array.from(document.querySelectorAll('[data-pr-line-item-row]')).map((row) => ({
                item_id: row.querySelector('[data-pr-line-item-field="item_id"]')?.value || '',
                description: row.querySelector('[data-pr-line-item-field="description"]')?.value || '',
                category: row.querySelector('[data-pr-line-item-field="category"]')?.value || '',
                quantity: row.querySelector('[data-pr-line-item-field="quantity"]')?.value || '',
                amount: row.querySelector('[data-pr-line-item-field="amount"]')?.value || '',
                total: row.querySelector('[data-pr-line-item-field="total"]')?.value || '',
            }));

            const summaryValues = {
                ca_reference_no: getLookupLabel('ca', formValues['data[linked_ca_id]']) || formValues['data[linked_ca_id]'] || 'N/A',
                ca_amount: formValues['data[total_cash_advance]'] || '0.00',
                for_client: formValues['data[for_client]'] || 'N/A',
                client_names: formValues['data[client_names]'] || 'N/A',
                line_items_total: rows.reduce((sum, row) => sum + (parseFloat(row.total || '0') || 0), 0).toFixed(2),
                subtotal: formValues['data[subtotal]'] || '0.00',
                discount_total: formValues['data[discount_total]'] || '0.00',
                tax_total: formValues['data[tax_total]'] || '0.00',
                shipping_total: formValues['data[shipping_total]'] || '0.00',
                wht_total: formValues['data[wht_total]'] || '0.00',
                grand_total: formValues['data[grand_total]'] || '0.00',
                variance: formValues['data[variance]'] || '0.00',
                variance_indicator: formValues['data[variance_indicator]'] || 'Balanced',
            };
            formValues['data[line_items_total]'] = summaryValues.line_items_total;

            $('drawerPreview').innerHTML = `
                <div class="rounded-2xl border border-slate-200 bg-slate-100 p-4">
                    <div class="mb-3 flex items-center justify-between rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-500 shadow-sm">
                        <span>PDF Holder</span>
                        <span>Live Preview</span>
                    </div>
                    <div class="mx-auto max-w-[760px] overflow-hidden rounded-[6px] border border-gray-300 bg-white shadow-lg">
                        <div class="relative px-5 py-5 text-center border-b border-gray-300 bg-white">
                            <div class="mx-auto flex items-center justify-center rounded-xl bg-white px-4 py-2">
                                <img src="${companyLogo}" alt="${escapeHtml(companyName)}" class="block h-24 w-auto max-w-[220px] object-contain">
                            </div>
                            <div class="mt-3 text-[16px] font-semibold leading-tight text-gray-900">${escapeHtml(companyName)}</div>
                            <div class="text-[10px] font-medium tracking-[0.3em] text-gray-500">${escapeHtml(companyLegalName)}</div>
                        </div>

                        <div class="relative bg-blue-700 px-4 py-2 text-center text-[12px] font-semibold uppercase tracking-[0.32em] text-white">
                            ${escapeHtml(titleLabel)}
                        </div>

                        <div class="relative grid grid-cols-2 border-t border-gray-300 text-sm">
                            ${summaryItems.map(([label, value], index) => `
                                <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                    <p class="mt-1 text-[15px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                                </div>
                            `).join('')}
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Liquidation Details</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="border-r border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Source</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">CA (Cash Advance)</p>
                                </div>
                                <div class="px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">CA Reference No.</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.ca_reference_no)}</p>
                                </div>
                                <div class="border-r border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">CA Amount</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.ca_amount)}</p>
                                </div>
                                <div class="px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Justification / Business Need</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[purpose]'] || 'Not filled yet')}</p>
                                </div>
                                <div class="border-r border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">For Client?</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.for_client)}</p>
                                </div>
                                <div class="border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Client Name(s)</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.client_names)}</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Requester Details</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                ${['employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'].map((fieldName, index) => `
                                    <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                        <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(fieldName.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase()))}</p>
                                        <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues[`data[${fieldName}]`] || 'Not filled yet')}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Liquidation Report</h4>
                            </div>
                            <div class="p-4">
                                ${renderLiquidationReportSection(draftLinkedRecord || null, formValues)}
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Liquidation / Cost Details</h4>
                            </div>
                            <div class="p-4 overflow-x-auto">
                                <table class="w-full min-w-[860px] border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-700">
                                            <th class="border border-gray-200 px-3 py-2 text-left w-12">#</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left">Item</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left">Description</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left w-32">Category</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left w-24">Qty</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left w-32">Amount</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left w-32">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows.map((row, index) => `
                                            <tr>
                                                <td class="border border-gray-200 px-3 py-2 font-semibold text-blue-700">${index + 1}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.item_id || 'N/A')}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.description || 'N/A')}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.category || 'N/A')}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.quantity || '0')}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.amount || '0.00')}</td>
                                                <td class="border border-gray-200 px-3 py-2 font-semibold">${escapeHtml(row.total || '0.00')}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            `;
            return;
        }

        if (currentModuleKey === 'po') {
            const rows = Array.from(document.querySelectorAll('[data-pr-line-item-row]')).map((row) => ({
                item_id: row.querySelector('[data-pr-line-item-field="item_id"]')?.value || '',
                description: row.querySelector('[data-pr-line-item-field="description"]')?.value || '',
                category: row.querySelector('[data-pr-line-item-field="category"]')?.value || '',
                quantity: row.querySelector('[data-pr-line-item-field="quantity"]')?.value || '',
                amount: row.querySelector('[data-pr-line-item-field="amount"]')?.value || '',
                subtotal: row.querySelector('[data-pr-line-item-field="subtotal"]')?.value || '',
                discount: row.querySelector('[data-pr-line-item-field="discount"]')?.value || '',
                discount_amount: row.querySelector('[data-pr-line-item-field="discount_amount"]')?.value || '',
                shipping_amount: row.querySelector('[data-pr-line-item-field="shipping_amount"]')?.value || '',
                tax_type: row.querySelector('[data-pr-line-item-field="tax_type"]')?.value || '',
                tax_amount: row.querySelector('[data-pr-line-item-field="tax_amount"]')?.value || '',
                wht_amount: row.querySelector('[data-pr-line-item-field="wht_amount"]')?.value || '',
                total: row.querySelector('[data-pr-line-item-field="total"]')?.value || '',
                supplier_id: row.querySelector('[data-pr-line-item-field="supplier_id"]')?.value || '',
                client_id: row.querySelector('[data-pr-line-item-field="client_id"]')?.value || '',
            }));

            $('drawerPreview').innerHTML = `
                <div class="rounded-2xl border border-slate-200 bg-slate-100 p-4">
                    <div class="mb-3 flex items-center justify-between rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-500 shadow-sm">
                        <span>PDF Holder</span>
                        <span>Live Preview</span>
                    </div>
                    <div class="mx-auto max-w-[760px] overflow-hidden rounded-[6px] border border-gray-300 bg-white shadow-lg">
                        <div class="relative px-5 py-5 text-center border-b border-gray-300 bg-white">
                            <div class="mx-auto flex items-center justify-center rounded-xl bg-white px-4 py-2">
                                <img src="${companyLogo}" alt="${escapeHtml(companyName)}" class="block h-24 w-auto max-w-[220px] object-contain">
                            </div>
                            <div class="mt-3 text-[16px] font-semibold leading-tight text-gray-900">${escapeHtml(companyName)}</div>
                            <div class="text-[10px] font-medium tracking-[0.3em] text-gray-500">${escapeHtml(companyLegalName)}</div>
                        </div>

                        <div class="relative bg-blue-700 px-4 py-2 text-center text-[12px] font-semibold uppercase tracking-[0.32em] text-white">
                            ${escapeHtml(titleLabel)}
                        </div>

                        <div class="relative grid grid-cols-2 border-t border-gray-300 text-sm">
                            ${summaryItems.map(([label, value], index) => `
                                <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                    <p class="mt-1 text-[15px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                                </div>
                            `).join('')}
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Order Details</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                ${[
                                    ['Linked PR', getLookupLabel('pr', formValues['data[linked_pr_id]']) || formValues['data[linked_pr_id]'] || 'Not filled yet'],
                                    ['Supplier', getLookupLabel('supplier', formValues['data[supplier_id]']) || formValues['data[supplier_id]'] || 'Not filled yet'],
                                    ['Expected Delivery Date', formValues['data[expected_delivery_date]'] || 'Not filled yet'],
                                    ['Delivery Address', formValues['data[delivery_address]'] || 'Not filled yet'],
                                    ['Terms and Conditions', formValues['data[terms_and_conditions]'] || 'Not filled yet'],
                                    ['Account', getLookupLabel('chart_account', formValues['data[coa_id]']) || formValues['data[coa_id]'] || 'Not filled yet'],
                                ].map(([label, value], index) => `
                                    <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                        <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                        <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Items / Cost Details</h4>
                            </div>
                            <div class="p-4 overflow-x-auto">
                                <table class="w-full min-w-[860px] border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-700">
                                            <th class="border border-gray-200 px-3 py-2 text-left w-12">#</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left">Item</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left">Description</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left w-32">Category</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left w-24">Qty</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left w-32">Amount</th>
                                            <th class="border border-gray-200 px-3 py-2 text-left w-32">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows.length ? rows.map((row, index) => `
                                            <tr>
                                                <td class="border border-gray-200 px-3 py-2 font-semibold text-blue-700">${index + 1}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(getPrItemDisplayValue(row.item_id) || 'N/A')}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.description || 'N/A')}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(getPrCategoryDisplayValue(row.category) || 'N/A')}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.quantity || '0')}</td>
                                                <td class="border border-gray-200 px-3 py-2">${escapeHtml(formatCurrency(row.amount || 0))}</td>
                                                <td class="border border-gray-200 px-3 py-2 font-semibold">${escapeHtml(formatCurrency(row.total || (Number(row.quantity || 0) * Number(row.amount || 0))))}</td>
                                            </tr>
                                        `).join('') : `
                                            <tr>
                                                <td colspan="7" class="border border-gray-200 px-3 py-4 text-center italic text-gray-400">No line items added yet.</td>
                                            </tr>
                                        `}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Remarks</h4>
                            </div>
                            <div class="px-4 py-3">
                                <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[remarks]'] || 'Not filled yet')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        if (currentModuleKey === 'ca') {
            const paidThroughValue = $('financeForm').querySelector('select[name="data[paid_through]"]')?.value || '';
            const summaryValues = {
                amount_requested: $('financeForm').querySelector('input[name="data[amount_requested]"]')?.value || '0.00',
                release_schedule: $('financeForm').querySelector('[name="data[release_schedule]"]')?.value || 'Full Release',
                release_count: $('financeForm').querySelector('input[name="data[release_count]"]')?.value || '1',
                amount_per_release: $('financeForm').querySelector('input[name="data[amount_per_release]"]')?.value || '0.00',
                cash_release_date: $('financeForm').querySelector('input[name="data[cash_release_date]"]')?.value || 'N/A',
                cash_release_time: $('financeForm').querySelector('input[name="data[cash_release_time]"]')?.value || 'N/A',
                mode_of_release: $('financeForm').querySelector('select[name="data[mode_of_release]"]')?.value || 'N/A',
                paid_through: getLookupLabel('chart_account', paidThroughValue) || paidThroughValue || 'N/A',
            };
            summaryValues.liquidation_calculation = getCashAdvanceLiquidationCalculation(summaryValues);

            $('drawerPreview').innerHTML = `
                <div class="rounded-2xl border border-slate-200 bg-slate-100 p-4">
                    <div class="mb-3 flex items-center justify-between rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-500 shadow-sm">
                        <span>PDF Holder</span>
                        <span>Live Preview</span>
                    </div>
                    <div class="mx-auto max-w-[760px] overflow-hidden rounded-[6px] border border-gray-300 bg-white shadow-lg">
                        <div class="relative px-5 py-5 text-center border-b border-gray-300 bg-white">
                            <div class="mx-auto flex items-center justify-center rounded-xl bg-white px-4 py-2">
                                <img src="${companyLogo}" alt="${escapeHtml(companyName)}" class="block h-24 w-auto max-w-[220px] object-contain">
                            </div>
                            <div class="mt-3 text-[16px] font-semibold leading-tight text-gray-900">${escapeHtml(companyName)}</div>
                            <div class="text-[10px] font-medium tracking-[0.3em] text-gray-500">${escapeHtml(companyLegalName)}</div>
                        </div>

                        <div class="relative bg-blue-700 px-4 py-2 text-center text-[12px] font-semibold uppercase tracking-[0.32em] text-white">
                            ${escapeHtml(titleLabel)}
                        </div>

                        <div class="relative grid grid-cols-2 border-t border-gray-300 text-sm">
                            ${summaryItems.map(([label, value], index) => `
                                <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                    <p class="mt-1 text-[15px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                                </div>
                            `).join('')}
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Request Details</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="border-r border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Date Needed</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[needed_date]'] || 'Not filled yet')}</p>
                                </div>
                                <div class="px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Priority</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[priority]'] || 'Not filled yet')}</p>
                                </div>
                                <div class="border-r border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Cash Advance Type</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[cash_advance_type]'] || 'Not filled yet')}</p>
                                </div>
                                <div class="px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">For Client?</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[for_client]'] || 'Not filled yet')}</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Cash Advance Details</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="border-r border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Amount Requested</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.amount_requested)}</p>
                                </div>
                                <div class="px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Mode of Release</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.mode_of_release)}</p>
                                </div>
                                <div class="border-r border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Release Schedule</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.release_schedule)}</p>
                                </div>
                                <div class="border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Number of Releases</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.release_count)}</p>
                                </div>
                                <div class="border-r border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Amount per Release</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.amount_per_release)}</p>
                                </div>
                                <div class="border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Liquidation Calculation</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.liquidation_calculation)}</p>
                                </div>
                                <div class="border-r border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Cash Release Date / Time</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml([summaryValues.cash_release_date, summaryValues.cash_release_time].filter((value) => value && value !== 'N/A').join(' ') || 'N/A')}</p>
                                </div>
                                <div class="border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Paid Through</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.paid_through)}</p>
                                </div>
                                <div class="border-r border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Other Business Purpose</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[other_business_purpose_specify]'] || formValues['data[other_expense_specify]'] || 'Not filled yet')}</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative border-t border-gray-300">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Justification & Notes</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="border-r border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Justification / Business Need</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[purpose]'] || 'Not filled yet')}</p>
                                </div>
                                <div class="px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Remarks</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[remarks]'] || 'Not filled yet')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        if (currentModuleKey === 'pr') {
            const rows = Array.from(document.querySelectorAll('[data-pr-line-item-row]')).map((row) => ({
                item_id: row.querySelector('[data-pr-line-item-field="item_id"]')?.value || '',
                description: row.querySelector('[data-pr-line-item-field="description"]')?.value || '',
                category: row.querySelector('[data-pr-line-item-field="category"]')?.value || '',
                quantity: row.querySelector('[data-pr-line-item-field="quantity"]')?.value || '',
                amount: row.querySelector('[data-pr-line-item-field="amount"]')?.value || '',
                subtotal: row.querySelector('[data-pr-line-item-field="subtotal"]')?.value || '',
                discount: row.querySelector('[data-pr-line-item-field="discount"]')?.value || '',
                discount_amount: row.querySelector('[data-pr-line-item-field="discount_amount"]')?.value || '',
                shipping_amount: row.querySelector('[data-pr-line-item-field="shipping_amount"]')?.value || '',
                tax_type: row.querySelector('[data-pr-line-item-field="tax_type"]')?.value || '',
                tax_amount: row.querySelector('[data-pr-line-item-field="tax_amount"]')?.value || '',
                wht_amount: row.querySelector('[data-pr-line-item-field="wht_amount"]')?.value || '',
                total: row.querySelector('[data-pr-line-item-field="total"]')?.value || '',
                supplier_id: row.querySelector('[data-pr-line-item-field="supplier_id"]')?.value || '',
                client_id: row.querySelector('[data-pr-line-item-field="client_id"]')?.value || '',
            }));

            const summaryValues = {
                subtotal: $('financeForm').querySelector('input[name="data[subtotal]"]')?.value || '0.00',
                discount_amount: $('financeForm').querySelector('input[name="data[discount_amount]"]')?.value || '0.00',
                shipping_amount: $('financeForm').querySelector('input[name="data[shipping_amount]"]')?.value || '0.00',
                tax_amount: $('financeForm').querySelector('input[name="data[tax_amount]"]')?.value || '0.00',
                wht_amount: $('financeForm').querySelector('input[name="data[wht_amount]"]')?.value || '0.00',
                grand_total: $('financeForm').querySelector('input[name="data[grand_total]"]')?.value || '0.00',
            };

            $('drawerPreview').innerHTML = `
                <div class="rounded-2xl border border-slate-200 bg-slate-100 p-4">
                    <div class="mb-3 flex items-center justify-between rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-500 shadow-sm">
                        <span>PDF Holder</span>
                        <span>Live Preview</span>
                    </div>
                    <div class="mx-auto max-w-[760px] overflow-hidden rounded-[6px] border border-gray-300 bg-white shadow-lg">
                        <div class="relative px-5 py-5 text-center border-b border-gray-300 bg-white">
                            <div class="mx-auto flex items-center justify-center rounded-xl bg-white px-4 py-2">
                                <img src="${companyLogo}" alt="${escapeHtml(companyName)}" class="block h-24 w-auto max-w-[220px] object-contain">
                            </div>
                            <div class="mt-3 text-[16px] font-semibold leading-tight text-gray-900">${escapeHtml(companyName)}</div>
                        <div class="text-[10px] font-medium tracking-[0.3em] text-gray-500">${escapeHtml(companyLegalName)}</div>
                    </div>

                    <div class="relative bg-blue-700 px-4 py-2 text-center text-[12px] font-semibold uppercase tracking-[0.32em] text-white">
                        ${escapeHtml(titleLabel)}
                    </div>

                    <div class="relative grid grid-cols-2 border-t border-gray-300 text-sm">
                        ${summaryItems.map(([label, value], index) => `
                            <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                                <p class="mt-1 text-[15px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                            </div>
                        `).join('')}
                    </div>

                    <div class="relative border-t border-gray-300">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                            <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Items / Cost Details</h4>
                        </div>
                        <div class="p-4 overflow-x-auto">
                            <table class="w-full min-w-[860px] border-collapse text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-700">
                                        <th class="border border-gray-200 px-3 py-2 text-left w-12">#</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Item</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Description</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left w-32">Category</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-24">Qty</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-32">Amount</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-40">Supplier</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-40">Client</th>
                                <th class="border border-gray-200 px-3 py-2 text-left w-32">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows.map((row, index) => `
                                <tr>
                                    <td class="border border-gray-200 px-3 py-2 font-semibold text-blue-700">${index + 1}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(getPrItemDisplayValue(row.item_id) || 'N/A')}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.description || 'N/A')}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(getPrCategoryDisplayValue(row.category) || 'N/A')}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(row.quantity || '0')}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(formatCurrency(row.amount || 0))}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(getLineItemLookupLabel('supplier', row.supplier_id, ''))}</td>
                                    <td class="border border-gray-200 px-3 py-2">${escapeHtml(getLineItemLookupLabel('client', row.client_id, ''))}</td>
                                    <td class="border border-gray-200 px-3 py-2 font-semibold">
                                        <div>${escapeHtml(formatCurrency(row.total || (Number(row.quantity || 0) * Number(row.amount || 0))))}</div>
                                        <div class="mt-1 text-[11px] text-gray-500">${escapeHtml(formatPrQuantity(row.quantity || 0))} x ${escapeHtml(formatCurrency(row.amount || 0))} = ${escapeHtml(formatCurrency(row.total || (Number(row.quantity || 0) * Number(row.amount || 0))))}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="9" class="border border-gray-200 px-3 py-3 bg-slate-50">
                                        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                                            ${[
                                                ['Subtotal', row.subtotal || (Number(row.quantity || 0) * Number(row.amount || 0))],
                                                ['Discount', row.discount_amount || '0.00'],
                                                ['Shipping', row.shipping_amount || '0.00'],
                                                ['Tax', row.tax_amount || '0.00'],
                                                ['WHT', row.wht_amount || '0.00'],
                                                ['Item Total', row.total || (Number(row.quantity || 0) * Number(row.amount || 0))],
                                            ].map(([label, value]) => `
                                                <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">${escapeHtml(label)}</p>
                                                    <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(value || 0))}</p>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                    </div>

                    <div class="relative border-t border-gray-300">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                            <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Purpose & Notes</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2">
                            <div class="border-r border-gray-300 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Purpose / Justification</p>
                                <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml($('financeForm').querySelector('textarea[name="data[purpose]"]')?.value || 'Not filled yet')}</p>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Remarks</p>
                                <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml($('financeForm').querySelector('textarea[name="data[remarks]"]')?.value || 'Not filled yet')}</p>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        const previewFields = moduleConfig.fields
            .filter((field) => field.name !== 'completion_mode')
            .filter((field) => {
                if (currentModuleKey === 'supplier' && isSendToSupplierMode()) {
                    return field.name === 'email_address';
                }

                return true;
            });

        const dataPairs = previewFields.map((field, index) => {
            const value = formValues[`data[${field.name}]`];
            const cellClasses = [
                'px-4',
                'py-3',
                'border-b',
                'border-gray-300',
                index % 2 === 0 ? 'md:border-r' : '',
            ].filter(Boolean).join(' ');

            if (!value) {
                return `
                    <div class="${cellClasses}">
                        <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(field.label)}</p>
                        <p class="mt-2 min-h-[20px] border-b border-dotted border-gray-300 text-[14px] font-semibold text-gray-900 italic">Not filled yet</p>
                    </div>
                `;
            }
            return `
                <div class="${cellClasses}">
                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(field.label)}</p>
                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(getFormDisplayValue(field, value, formValues))}</p>
                </div>
            `;
        }).join('');

        $('drawerPreview').innerHTML = `
            <div class="rounded-2xl border border-slate-200 bg-slate-100 p-4">
                <div class="mb-3 flex items-center justify-between rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-500 shadow-sm">
                    <span>PDF Holder</span>
                    <span>Live Preview</span>
                </div>
                <div class="mx-auto max-w-[760px] overflow-hidden rounded-[6px] border border-gray-300 bg-white shadow-lg">
                <div class="relative px-5 py-5 text-center border-b border-gray-300 bg-white">
                    <div class="mx-auto flex items-center justify-center rounded-xl bg-white px-4 py-2">
                        <img src="${companyLogo}" alt="${escapeHtml(companyName)}" class="block h-24 w-auto max-w-[220px] object-contain">
                    </div>
                    <div class="mt-3 text-[16px] font-semibold leading-tight text-gray-900">${escapeHtml(companyName)}</div>
                    <div class="text-[10px] font-medium tracking-[0.3em] text-gray-500">${escapeHtml(companyLegalName)}</div>
                </div>

                <div class="relative bg-blue-700 px-4 py-2 text-center text-[12px] font-semibold uppercase tracking-[0.32em] text-white">
                    ${escapeHtml(titleLabel)}
                </div>

                <div class="relative grid grid-cols-2 border-t border-gray-300 text-sm">
                    ${summaryItems.map(([label, value], index) => `
                        <div class="${index % 2 === 0 ? 'border-r' : ''} ${index > 1 ? 'border-t' : ''} border-gray-300 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">${escapeHtml(label)}</p>
                            <p class="mt-1 text-[15px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                        </div>
                    `).join('')}
                </div>

                <div class="relative border-t border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                        <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Details</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        ${dataPairs || '<div class="px-3 py-4 text-gray-400 italic">No module fields entered yet.</div>'}
                    </div>
                </div>
                </div>
            </div>
        `;
    }

    function showOnlySection(sectionId) {
        $('tableSection').classList.add('hidden');
        $('previewSection').classList.add('hidden');
        $(sectionId).classList.remove('hidden');
    }

    function closePreview() {
        clearPreviewRefreshTimer();
        currentPreviewRecord = null;
        currentPreviewTab = 'details';
        currentPreviewAttachmentUrl = '';
        currentPreviewPdfGeneration = 0;
        revokeCurrentPreviewPdfObjectUrl();
        $('previewDocument').innerHTML = '';
        $('previewTabContent').innerHTML = '';
        $('previewActions').innerHTML = '';
        showOnlySection('tableSection');
    }

    function openFinanceDrawer(record = null) {
        currentEditRecordId = record ? record.id : null;
        renderFinanceForm(record);
        const drawerSection = $('drawerSection');
        const drawerPanel = $('drawerPanel');
        drawerSection.classList.remove('hidden');
        requestAnimationFrame(() => drawerPanel.classList.remove('translate-x-full'));
        requestAnimationFrame(() => renderDrawerPreview());
    }

    function closeFinanceDrawer() {
        const drawerSection = $('drawerSection');
        const drawerPanel = $('drawerPanel');
        drawerPanel.classList.add('translate-x-full');
        setTimeout(() => {
            drawerSection.classList.add('hidden');
            currentEditRecordId = null;
            financeDraftContext = null;
        }, 300);
    }

    function changeSupplierCompletionMode(mode) {
        if (!isSupplierModule()) return;
        supplierCompletionMode = mode === 'send_to_supplier' ? 'send_to_supplier' : 'complete_internally';
        renderFinanceForm(currentEditRecordId ? getRecordById(currentEditRecordId) : null);
    }

    function fetchLiquidationSource() {
        if (currentModuleKey !== 'lr') return;

        const form = $('financeForm');
        if (!form) return;

        const linkedCaSelect = form.querySelector('select[name="data[linked_ca_id]"]');
        const sourceId = linkedCaSelect?.value || '';
        if (!sourceId) {
            showFinanceToast('Please choose a CA reference number first.', 'warning');
            return;
        }

        const sourceRecord = getRecordById(sourceId);
        if (!sourceRecord) {
            showFinanceToast('Unable to find the selected CA record.', 'error');
            return;
        }

        const sourceData = sourceRecord.data || {};
        const setField = (name, value) => {
            const input = form.querySelector(`[name="data[${name}]"]`);
            if (input && input.type !== 'hidden') {
                input.value = value ?? '';
            } else if (input) {
                input.value = value ?? '';
            }
        };

        setField('total_cash_advance', sourceRecord.amount || sourceData.amount_requested || '');
        setField('purpose', sourceData.purpose || sourceData.justification || '');
        setField('employee_id', sourceData.employee_id || '');
        setField('employee_name', sourceData.employee_name || sourceRecord.user || bootstrap.currentUserName || '');
        setField('employee_email', sourceData.employee_email || '');
        setField('contact_number', sourceData.contact_number || '');
        setField('position', sourceData.position || '');
        setField('department', sourceData.department || '');
        setField('superior', sourceData.superior || '');
        setField('superior_email', sourceData.superior_email || '');
        setField('for_client', sourceData.for_client || 'N/A');
        setField('client_names', sourceData.client_names || '');
        setField('coa_id', sourceData.coa_id || '');
        setField('linked_dv_id', sourceData.linked_dv_id || '');

        financeDraftContext = null;
        pendingLiquidationBranchDraft = null;
        updatePrTotals();
        renderDrawerPreview();
        showFinanceToast('CA details loaded. Shortage or overage will be calculated from this liquidation report.', 'success');
    }

    function getRecordById(id) {
        return financeRecords.find((record) => String(record.id) === String(id));
    }

    function normalizeAttachmentUrl(path) {
        return `/${String(path || '').replace(/^\//, '')}`;
    }

    function revokeCurrentPreviewPdfObjectUrl() {
        if (currentPreviewPdfObjectUrl) {
            URL.revokeObjectURL(currentPreviewPdfObjectUrl);
            currentPreviewPdfObjectUrl = '';
        }
    }

    function clearPreviewRefreshTimer() {
        if (currentPreviewRefreshTimer) {
            clearInterval(currentPreviewRefreshTimer);
            currentPreviewRefreshTimer = null;
        }
    }

    function syncPreviewFromRecord(record) {
        currentPreviewRecord = record;
        $('previewModuleTitle').textContent = record.module_label;
        renderPreviewDocument(record);
        renderPreviewTabContent(record);
        renderPreviewActions(record);
        updatePreviewTabButtons();
    }

    async function refreshPreviewRecord(id, silent = false) {
        if (!id) return;

        try {
            const res = await fetch(`/finance/${id}`, {
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) return;

            const data = await res.json();
            if (!data || !data.id) return;

            upsertFinanceRecord(data);

            if (currentPreviewRecord && String(currentPreviewRecord.id) === String(data.id)) {
                syncPreviewFromRecord(data);
            }

            clearPreviewRefreshTimer();
        } catch (error) {
            if (!silent) {
                console.error('Unable to refresh finance preview record:', error);
            }
        }
    }

    function startPreviewRefresh(record) {
        clearPreviewRefreshTimer();
        setTimeout(() => {
            if (currentPreviewRecord) {
                refreshPreviewRecord(currentPreviewRecord.id, true);
            }
        }, 2000);
    }

    function buildFinancePreviewSourceHtml(record) {
        const moduleConfig = getModuleConfig(record.module_key);
        const companyName = 'John Kelly & Company';
        const companyLegalName = 'JK&C INC.';
        const companyLogo = '/images/imaglogo.png';
        const data = record.data || {};
        const summaryItems = [
            ['Module', moduleConfig.label],
            ['Record Number', record.record_number || 'N/A'],
            [moduleConfig.recordTitleLabel || 'Name', record.record_title || 'N/A'],
            ['Record Date', record.record_date || 'N/A'],
            ['Record Time', data.transaction_time || 'N/A'],
            ...(shouldShowGenericAmount(record) ? [['Amount', record.amount ? formatCurrency(record.amount) : 'N/A']] : []),
            ['Status', record.status || 'N/A'],
            ['Workflow', record.workflow_status || 'N/A'],
            ['Approval', previewApprovalLabel(record)],
            ['Created By', record.user || 'N/A'],
            ['Submitted At', record.submitted_at || 'N/A'],
            ['Approved At', record.approved_at || 'N/A'],
        ];

        const previewSections = getModulePreviewSections(record);
        const modulePreviewHtml = previewSections.map((section) => {
            if (typeof section.renderer === 'function') {
                return section.renderer();
            }
            if (section.type === 'asset_tag') {
                return `
                    <div class="finance-preview-box">
                        <div class="finance-preview-section-title">${escapeHtml(section.title || 'Asset Tag')}</div>
                        <div class="finance-preview-inner">
                            ${renderArfAssetTagCard(section.assetCode, section.location, section.serialNumber, section.barcodeSvg)}
                        </div>
                    </div>
                `;
            }
            return renderPreviewSectionTable(record, moduleConfig, section.title, section.fieldNames || []);
        }).join('');

        const attachmentRows = (record.attachments || []).map((attachment) => `
            <tr>
                <td class="preview-cell-label">Attachment</td>
                <td class="preview-cell-value">${escapeHtml(attachment.name || attachment.path || 'Attachment')}</td>
            </tr>
        `).join('');

        return `
            <div class="finance-preview-source">
                <style>
                    .finance-preview-source {
                        width: 816px;
                        padding: 28px;
                        background: #ffffff;
                        color: #111827;
                        font-family: Arial, Helvetica, sans-serif;
                    }
                    @page {
                        size: letter;
                        margin: 0;
                    }
                    .finance-preview-source * { box-sizing: border-box; }
                    .finance-preview-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 24px;
                        padding: 18px 20px;
                        border: 1px solid #dbe2ea;
                        border-radius: 16px 16px 0 0;
                        background: linear-gradient(90deg, #fff 0%, #fff 75%, #eff6ff 100%);
                    }
                    .finance-preview-brand {
                        display: flex;
                        align-items: center;
                        gap: 14px;
                    }
                    .finance-preview-logo {
                        width: 76px;
                        height: 76px;
                        border: 1px solid #dbeafe;
                        border-radius: 18px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: #fff;
                    }
                    .finance-preview-logo img {
                        width: 64px;
                        height: 64px;
                        object-fit: contain;
                    }
                    .finance-preview-eyebrow,
                    .finance-preview-title,
                    .finance-preview-subtitle,
                    .finance-preview-note,
                    .finance-preview-status-title {
                        margin: 0;
                    }
                    .finance-preview-eyebrow,
                    .finance-preview-status-title {
                        text-transform: uppercase;
                        letter-spacing: .24em;
                        font-size: 10px;
                        color: #6b7280;
                    }
                    .finance-preview-title {
                        font-size: 28px;
                        line-height: 1.1;
                        font-weight: 700;
                    }
                    .finance-preview-subtitle {
                        margin-top: 5px;
                        color: #1d4ed8;
                        font-weight: 700;
                        font-size: 12px;
                    }
                    .finance-preview-note {
                        margin-top: 4px;
                        color: #6b7280;
                        font-size: 11px;
                    }
                    .finance-preview-status {
                        text-align: right;
                    }
                    .finance-preview-status p {
                        margin: 0;
                        font-size: 12px;
                        font-weight: 700;
                        line-height: 1.5;
                    }
                    .finance-preview-section-title {
                        margin: 0;
                        padding: 10px 14px;
                        background: #1d4ed8;
                        color: #fff;
                        text-transform: uppercase;
                        letter-spacing: .24em;
                        font-size: 11px;
                        font-weight: 700;
                    }
                    .finance-preview-summary,
                    .finance-preview-details,
                    .finance-preview-lineitems {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    .finance-preview-summary td,
                    .finance-preview-details td,
                    .finance-preview-lineitems td,
                    .finance-preview-lineitems th {
                        border: 1px solid #dbe2ea;
                        vertical-align: top;
                        padding: 10px 12px;
                    }
                    .finance-preview-summary td { width: 25%; height: 62px; }
                    .finance-preview-label {
                        margin: 0;
                        text-transform: uppercase;
                        letter-spacing: .18em;
                        color: #6b7280;
                        font-size: 10px;
                    }
                    .finance-preview-value {
                        margin: 5px 0 0;
                        font-size: 13px;
                        font-weight: 700;
                        word-break: break-word;
                    }
                    .finance-preview-box {
                        border: 1px solid #dbe2ea;
                        border-top: 0;
                    }
                    .finance-preview-inner {
                        padding: 14px;
                    }
                    .finance-preview-lineitems th {
                        background: #f8fafc;
                        text-align: left;
                        font-size: 10px;
                    }
                    .finance-preview-lineitems td {
                        font-size: 10.5px;
                    }
                    .finance-preview-muted {
                        color: #6b7280;
                        font-size: 12px;
                    }
                    .finance-preview-two-col {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    .finance-preview-two-col td {
                        width: 50%;
                        border: 1px solid #dbe2ea;
                        padding: 10px 12px;
                        vertical-align: top;
                    }
                </style>
                <div class="finance-preview-header">
                    <div class="finance-preview-brand">
                        <div class="finance-preview-logo">
                            <img src="${companyLogo}" alt="${escapeHtml(companyName)}">
                        </div>
                        <div>
                            <p class="finance-preview-eyebrow">Official Finance Form</p>
                            <div class="finance-preview-title">${escapeHtml(companyName)}</div>
                            <div class="finance-preview-subtitle">${escapeHtml(companyLegalName)} | ${escapeHtml(moduleConfig.label)}</div>
                            <div class="finance-preview-note">${escapeHtml(record.record_number || 'N/A')} - ${escapeHtml(record.record_title || 'N/A')}</div>
                        </div>
                    </div>
                    <div class="finance-preview-status">
                        <p class="finance-preview-status-title">Document Status</p>
                        <p>Workflow: ${escapeHtml(record.workflow_status || 'N/A')}</p>
                        <p>Approval: ${escapeHtml(record.approval_status || 'N/A')}</p>
                        <p>Status: ${escapeHtml(record.status || 'N/A')}</p>
                    </div>
                </div>

                <table class="finance-preview-summary">
                    ${chunkArray(summaryItems, 4).map((row) => `
                        <tr>
                            ${row.map(([label, value]) => `
                                <td>
                                    <p class="finance-preview-label">${escapeHtml(label)}</p>
                                    <p class="finance-preview-value">${escapeHtml(value)}</p>
                                </td>
                            `).join('')}
                            ${Array.from({ length: 4 - row.length }).map(() => '<td></td>').join('')}
                        </tr>
                    `).join('')}
                </table>

                ${modulePreviewHtml || '<p class="finance-preview-muted">No additional details provided.</p>'}

                ${record.attachments?.length ? `
                    <div class="finance-preview-box">
                        <div class="finance-preview-section-title">Attachments</div>
                        <div class="finance-preview-inner">
                            <table class="finance-preview-details">
                                ${record.attachments.map((attachment) => `
                                    <tr>
                                        <td class="finance-preview-label">Attachment</td>
                                        <td class="finance-preview-value">${escapeHtml(attachment.name || attachment.path || 'Attachment')}</td>
                                    </tr>
                                `).join('')}
                            </table>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    async function generateFinancePreviewPdf(record) {
        const frame = $('financePreviewPdfFrame');
        const source = $('financePreviewPdfSource');
        if (!frame || !source || typeof window.html2pdf !== 'function') {
            frame && (frame.src = '');
            return;
        }

        const generationId = ++currentPreviewPdfGeneration;
        source.innerHTML = buildFinancePreviewSourceHtml(record);
        frame.src = '';

        try {
            await new Promise((resolve) => requestAnimationFrame(() => resolve()));
            await new Promise((resolve) => setTimeout(resolve, 120));

            const blob = await window.html2pdf()
                .set({
                    margin: [0, 0, 0, 0],
                    filename: `${String(record.record_number || record.record_title || 'finance-record').replace(/[\\/:*?"<>|]+/g, '').replace(/\s+/g, '-').toLowerCase()}.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        scrollY: 0,
                        backgroundColor: '#ffffff',
                    },
                    jsPDF: { unit: 'pt', format: 'letter', orientation: 'portrait' },
                    pagebreak: { mode: ['css', 'legacy'] },
                })
                .from(source)
                .outputPdf('blob');

            if (generationId !== currentPreviewPdfGeneration) {
                return;
            }

            revokeCurrentPreviewPdfObjectUrl();
            currentPreviewPdfObjectUrl = URL.createObjectURL(blob);
            frame.src = currentPreviewPdfObjectUrl;
            const openLink = $('financePreviewOpenLink');
            if (openLink) {
                openLink.href = currentPreviewPdfObjectUrl;
                openLink.textContent = 'Open Preview';
            }
            if (currentPreviewRecord) {
                renderPreviewActions(currentPreviewRecord);
            }
        } catch (error) {
            frame.src = '';
            source.innerHTML = `<div style="padding:24px;font-family:Arial,sans-serif;color:#991b1b;">Unable to generate the finance preview PDF.</div>`;
            console.error('Finance PDF generation failed:', error);
        }
    }

    function renderPreviewDocument(record) {
        const attachmentMode = Boolean(currentPreviewAttachmentUrl);
        const attachmentName = currentPreviewAttachmentUrl
            ? (record.attachments || []).find((attachment) => normalizeAttachmentUrl(attachment.path) === currentPreviewAttachmentUrl)?.name || 'Attached PDF'
            : 'Finance Preview PDF';
        const holderLabel = attachmentMode ? 'Attachment PDF' : 'Finance PDF';
        const previewUrl = currentPreviewAttachmentUrl || `/finance/${record.id}/preview-pdf`;

        $('previewDocument').innerHTML = `
            <div class="max-w-5xl mx-auto">
                <div class="rounded-[24px] border border-gray-200 bg-slate-100 p-4">
                    <div class="mb-3 flex items-center justify-between gap-3 rounded-full border border-gray-200 bg-white px-4 py-2 text-xs font-medium text-gray-500 shadow-sm">
                        <span>${escapeHtml(holderLabel)}</span>
                        <span class="truncate">${escapeHtml(attachmentName)}</span>
                    </div>
                    <div class="overflow-hidden rounded-[14px] border border-gray-300 bg-white shadow-lg">
                        <iframe
                            id="financePreviewPdfFrame"
                            src="${escapeHtml(previewUrl)}"
                            title="Finance preview"
                            class="block w-full"
                            style="height: 980px; border: 0; background: #ffffff;"
                        ></iframe>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a id="financePreviewOpenLink" href="${escapeHtml(currentPreviewAttachmentUrl || `/finance/${record.id}/preview-pdf`)}" target="_blank" class="rounded-full border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Open ${escapeHtml(attachmentMode ? 'Attachment' : 'Preview')}
                        </a>
                    </div>
                </div>
                <div id="financePreviewPdfSource" class="fixed top-0 left-0 w-[816px] bg-white" style="transform: translateX(-120vw); pointer-events: none;" aria-hidden="true"></div>
            </div>
        `;
    }

    function renderPreviewTabContent(record) {
        const detailItems = `
            <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-4">
                <h4 class="text-[15px] font-semibold text-gray-900">${escapeHtml(record.module_label || 'Finance Record')} Details</h4>
                <div class="mt-4 space-y-4">
                    ${[
                        ['Record Number', record.record_number || 'N/A'],
                        [getModuleConfig(record.module_key).recordTitleLabel || 'Name', record.record_title || 'N/A'],
                        ['Record Date', record.record_date || 'N/A'],
                        ...(shouldShowGenericAmount(record) ? [['Amount', record.amount ? formatCurrency(record.amount) : 'N/A']] : []),
                        ['Workflow', record.workflow_status || 'N/A'],
                        ['Approval', previewApprovalLabel(record)],
                        ['Status', record.status || 'N/A'],
                        ['Created By', record.user || 'N/A'],
                    ].map(([label, value]) => `
                        <div class="space-y-1 border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">${escapeHtml(label)}</p>
                            <p class="text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        const notesHtml = `
            <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
                <h4 class="text-[15px] font-semibold text-gray-900">Review Notes</h4>
                <p class="mt-1 text-xs text-gray-500">Finance review notes are shown here in the same card-style layout used in BIR &amp; Tax.</p>
                <div class="mt-4 rounded-xl border border-amber-100 bg-white px-4 py-3">
                    <div class="flex items-center justify-between gap-3 text-[11px] text-gray-500">
                        <div>
                            <span class="font-semibold text-gray-800">${escapeHtml(record.approved_by || record.submitted_by || record.user || 'Finance Team')}</span>
                        </div>
                        <div>${escapeHtml(record.approved_at || record.submitted_at || '')}</div>
                    </div>
                    <div class="mt-1 text-[11px] uppercase tracking-[0.18em] text-amber-700">Review Note</div>
                    <div class="mt-2 whitespace-pre-line text-sm text-gray-900">${escapeHtml(record.review_note || 'No review notes yet.')}</div>
                </div>
            </div>
        `;

        const attachments = Array.isArray(record.attachments) ? record.attachments : [];
        const pdfAttachments = attachments.filter((attachment) => {
            const name = String(attachment.name || attachment.path || '').toLowerCase();
            const mime = String(attachment.mime || '').toLowerCase();
            return name.endsWith('.pdf') || mime.includes('pdf');
        });
        const otherAttachments = attachments.filter((attachment) => !pdfAttachments.includes(attachment));

        const attachmentsHtml = `
            <div class="space-y-4">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                    <h4 class="text-[15px] font-semibold text-gray-900">Attached PDFs</h4>
                    <p class="mt-1 text-xs text-gray-500">Choose a PDF to load it into the preview pane.</p>
                    <div class="mt-4 space-y-3">
                        ${pdfAttachments.length ? pdfAttachments.map((attachment, index) => {
                            const url = attachment.url || normalizeAttachmentUrl(attachment.path || '');
                            const active = currentPreviewAttachmentUrl === url;
                            return `
                                <button
                                    type="button"
                                    onclick="window.financeModule.previewAttachment(${JSON.stringify(url)}, ${JSON.stringify(attachment.name || `Attachment ${index + 1}`)})"
                                    class="w-full rounded-xl border px-4 py-3 text-left transition ${active ? 'border-blue-200 bg-white shadow-sm' : 'border-gray-200 bg-white hover:bg-gray-50'}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 break-all">${escapeHtml(attachment.name || `Attachment ${index + 1}`)}</p>
                                            <p class="mt-1 text-xs text-gray-500 break-all">${escapeHtml(attachment.path || '')}</p>
                                        </div>
                                        <span class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-medium text-gray-600">View</span>
                                    </div>
                                </button>
                            `;
                        }).join('') : '<p class="text-sm text-gray-400 italic">No PDF attachments uploaded.</p>'}
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <h4 class="text-[15px] font-semibold text-gray-900">Other Attachments</h4>
                        <div class="mt-4 space-y-3">
                        ${otherAttachments.length ? otherAttachments.map((attachment, index) => `
                            <a href="${escapeHtml(attachment.url || normalizeAttachmentUrl(attachment.path || ''))}" target="_blank" class="block rounded-xl border border-gray-200 bg-white px-4 py-3 hover:bg-gray-50 transition">
                                <p class="font-medium text-gray-900 break-all">${escapeHtml(attachment.name || `Attachment ${index + 1}`)}</p>
                                <p class="mt-1 text-xs text-gray-500 break-all">${escapeHtml(attachment.path || '')}</p>
                            </a>
                        `).join('') : '<p class="text-sm text-gray-400 italic">No other attachments uploaded.</p>'}
                    </div>
                </div>
            </div>
        `;

        $('previewTabContent').innerHTML = currentPreviewTab === 'attachments' ? attachmentsHtml : `${detailItems}${notesHtml}`;
    }

    function updatePreviewTabButtons() {
        const detailsButton = $('previewTabDetails');
        const attachmentsButton = $('previewTabAttachments');
        if (!detailsButton || !attachmentsButton) return;

        const isDetails = currentPreviewTab === 'details';
        detailsButton.className = `rounded-full px-4 py-2 text-sm font-medium transition ${isDetails ? 'bg-white text-blue-700 shadow-sm border border-gray-200' : 'text-gray-600 hover:text-gray-900'}`;
        attachmentsButton.className = `rounded-full px-4 py-2 text-sm font-medium transition ${!isDetails ? 'bg-white text-blue-700 shadow-sm border border-gray-200' : 'text-gray-600 hover:text-gray-900'}`;
    }

    function changePreviewTab(tab) {
        currentPreviewTab = tab === 'attachments' ? 'attachments' : 'details';
        if (currentPreviewRecord) {
            if (currentPreviewTab === 'attachments') {
                const firstPdf = (currentPreviewRecord.attachments || []).find((attachment) => {
                    const name = String(attachment.name || attachment.path || '').toLowerCase();
                    const mime = String(attachment.mime || '').toLowerCase();
                    return name.endsWith('.pdf') || mime.includes('pdf');
                });
                currentPreviewAttachmentUrl = firstPdf ? (firstPdf.url || normalizeAttachmentUrl(firstPdf.path || '')) : '';
            } else {
                currentPreviewAttachmentUrl = '';
            }
            renderPreviewTabContent(currentPreviewRecord);
            renderPreviewDocument(currentPreviewRecord);
            renderPreviewActions(currentPreviewRecord);
        }
        updatePreviewTabButtons();
    }

    function renderPreviewActions(record) {
        const actions = [];
        const supplierPending = isPendingSupplierCompletion(record);
        const previewUrl = currentPreviewAttachmentUrl || `/finance/${record.id}/preview-pdf`;
        const previewLabel = currentPreviewAttachmentUrl ? 'Attachment PDF' : 'Preview';
        if (!supplierPending) {
            actions.push(`<a href="${escapeHtml(previewUrl)}" target="_blank" class="block w-full border border-gray-300 rounded-md py-2 text-center hover:bg-gray-50">Open ${escapeHtml(previewLabel)}</a>`);
        }
        actions.push(`<button type="button" onclick="window.financeModule.openFinanceDrawer(window.financeModule.getRecordById(${record.id}))" class="w-full border border-gray-300 rounded-md py-2 hover:bg-gray-50">Edit</button>`);
        if (!supplierPending) {
            actions.push(`<button type="button" onclick="window.financeModule.printFinanceRecord(${record.id})" class="w-full border border-gray-300 rounded-md py-2 hover:bg-gray-50">Print</button>`);
        }

        if (record.can_submit) {
            actions.push(`<button type="button" onclick="window.financeModule.submitFinanceRecord(${record.id})" class="w-full bg-blue-600 text-white rounded-md py-2 hover:bg-blue-700">Submit for Review</button>`);
        }

        if (record.can_share_supplier) {
            actions.push(`<button type="button" onclick="window.financeModule.shareSupplierRecord(${record.id})" class="w-full bg-sky-600 text-white rounded-md py-2 hover:bg-sky-700">Send to Supplier</button>`);
        }

        if (supplierPending) {
            actions.push(`<button type="button" onclick="window.financeModule.resendSupplierForm(${record.id})" class="w-full bg-sky-700 text-white rounded-md py-2 hover:bg-sky-800">Resend Completion Form</button>`);
            actions.push(`<button type="button" onclick="window.financeModule.changeSupplierEmailAndResend(${record.id})" class="w-full border border-sky-300 text-sky-700 rounded-md py-2 hover:bg-sky-50">Change Email &amp; Resend</button>`);
        }

        if (record.can_review) {
            actions.push(`<button type="button" onclick="window.financeModule.approveFinanceRecord(${record.id})" class="w-full bg-green-600 text-white rounded-md py-2 hover:bg-green-700">Approve</button>`);
            actions.push(`<button type="button" onclick="window.financeModule.revertFinanceRecord(${record.id})" class="w-full bg-amber-500 text-white rounded-md py-2 hover:bg-amber-600">Return for Revision</button>`);
            actions.push(`<button type="button" onclick="window.financeModule.archiveFinanceRecord(${record.id})" class="w-full bg-gray-700 text-white rounded-md py-2 hover:bg-gray-800">Archive</button>`);
        }

        if (record.supplier_completion_url) {
            actions.push(`<button type="button" onclick="window.financeModule.copySupplierLink('${escapeHtml(record.supplier_completion_url)}')" class="w-full border border-sky-300 text-sky-700 rounded-md py-2 hover:bg-sky-50">Copy Supplier Link</button>`);
        }

        $('previewActions').innerHTML = actions.join('');
    }

    function openPreview(id) {
        const record = getRecordById(id);
        if (!record) return;

        clearPreviewRefreshTimer();
        currentPreviewRecord = record;
        currentPreviewTab = 'details';
        currentPreviewAttachmentUrl = '';
        currentPreviewPdfGeneration = 0;
        revokeCurrentPreviewPdfObjectUrl();
        $('previewModuleTitle').textContent = record.module_label;
        renderPreviewDocument(record);
        renderPreviewTabContent(record);
        updatePreviewTabButtons();
        renderPreviewActions(record);
        startPreviewRefresh(record);
        refreshPreviewRecord(record.id, true);
        showOnlySection('previewSection');
    }

    function previewAttachment(url) {
        if (!currentPreviewRecord || !url) return;

        currentPreviewAttachmentUrl = url;
        currentPreviewTab = 'attachments';
        revokeCurrentPreviewPdfObjectUrl();
        renderPreviewDocument(currentPreviewRecord);
        renderPreviewTabContent(currentPreviewRecord);
        renderPreviewActions(currentPreviewRecord);
        updatePreviewTabButtons();
    }

    function upsertFinanceRecord(record) {
        const index = financeRecords.findIndex((item) => String(item.id) === String(record.id));
        if (index >= 0) {
            financeRecords[index] = record;
        } else {
            financeRecords.unshift(record);
        }
        syncLookupOptions(record);

        const sourceIndex = financeSourceRecords.findIndex((item) => String(item.id) === String(record.id));
        const isSourceEligible = ['Accepted'].includes(record.workflow_status) || ['Approved'].includes(record.approval_status);
        if (isSourceEligible) {
            if (sourceIndex >= 0) {
                financeSourceRecords[sourceIndex] = record;
            } else {
                financeSourceRecords.unshift(record);
            }
        } else if (sourceIndex >= 0) {
            financeSourceRecords.splice(sourceIndex, 1);
        }
    }

    function syncLookupOptions(record) {
        const lookupKey = record.module_key;
        const eligible = ['Accepted'].includes(record.workflow_status) || ['Approved'].includes(record.approval_status);
        const options = financeLookupOptions[lookupKey] || [];
        const existingIndex = options.findIndex((option) => String(option.id) === String(record.id));

        if (eligible) {
            const newOption = {
                id: record.id,
                label: record.display_label || record.record_title || record.record_number || `${record.module_label} #${record.id}`,
            };

            if (existingIndex >= 0) {
                options[existingIndex] = newOption;
            } else {
                options.unshift(newOption);
            }

            financeLookupOptions[lookupKey] = options;
            return;
        }

        if (existingIndex >= 0) {
            financeLookupOptions[lookupKey] = options.filter((option) => String(option.id) !== String(record.id));
        }
    }

    async function saveFinanceRecord(event) {
        event.preventDefault();
        const form = $('financeForm');
        const formData = new FormData(form);
        const moduleConfig = getModuleConfig(currentModuleKey);
        const sendToSupplier = isSendToSupplierMode();

        formData.set('module_key', currentModuleKey);
        formData.set('data[completion_mode]', sendToSupplier ? 'send_to_supplier' : 'complete_internally');

        if (sendToSupplier && currentModuleKey === 'supplier') {
            const supplierEmail = String(formData.get('data[email_address]') || '').trim();
            if (!supplierEmail) {
                alert('Please enter the supplier email address.');
                return;
            }

            formData.set('record_number', $('recordNumberInput').value.trim() || generateModuleRecordNumber(currentModuleKey));
            formData.set('record_title', $('recordTitleInput').value.trim() || 'Supplier Completion');
            formData.set('record_date', $('recordDateInput').value || new Date().toISOString().slice(0, 10));
            formData.set('amount', $('amountInput').value || '');
            formData.set('status', $('statusInput').value || 'Active');
        } else {
            if (currentModuleKey === 'dv') {
                collectDvLineItems();
                updateDvNetAmount();
                formData.set('amount', formData.get('data[amount]') || $('amountInput').value || '');
            }

            formData.set('record_number', $('recordNumberInput').value.trim() || generateModuleRecordNumber(currentModuleKey));
            formData.set('record_title', $('recordTitleInput').value.trim() || generateDefaultRecordTitle(currentModuleKey));
            formData.set('record_date', $('recordDateInput').value);
            if (currentModuleKey !== 'dv') {
                formData.set('amount', $('amountInput').value);
            }
            formData.set('status', $('statusInput').value);

            if (!formData.get('record_number') || !formData.get('record_date')) {
                alert('Please fill in the record number and date.');
                return;
            }
        }

        const endpoint = currentEditRecordId ? `/finance/${currentEditRecordId}` : '/finance';
        if (currentEditRecordId) {
            formData.append('_method', 'PUT');
        }

        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        let data = {};
        try {
            data = await res.json();
        } catch (error) {
            data = {};
        }

        if (!res.ok) {
            const firstErrorKey = data.errors ? Object.keys(data.errors)[0] : null;
            const firstErrorMessage = firstErrorKey && data.errors[firstErrorKey] ? data.errors[firstErrorKey][0] : '';
            const friendlyMessage = getFriendlyErrorMessage(firstErrorMessage || data.message || '');
            alert(friendlyMessage || `Please complete the required fields for ${moduleConfig.label.toLowerCase()}.`);
            return;
        }

        upsertFinanceRecord(data.data);
        closeFinanceDrawer();
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function submitFinanceRecord(id) {
        const res = await fetch(`/finance/${id}/submit`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to submit record.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function approveFinanceRecord(id) {
        const res = await fetch(`/finance/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to approve record.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function revertFinanceRecord(id) {
        const note = prompt('Enter a review note for reverting this record:');
        if (!note) return;

        const formData = new FormData();
        formData.append('review_note', note);

        const res = await fetch(`/finance/${id}/revert`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to revert record.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function archiveFinanceRecord(id) {
        const res = await fetch(`/finance/${id}/archive`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to archive record.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function shareSupplierRecord(id) {
        const res = await fetch(`/finance/${id}/share-supplier-link`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to create supplier link.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
        if (data.link) {
            await navigator.clipboard.writeText(data.link).catch(() => {});
            showFinanceToast('Supplier link has been emailed and copied to clipboard.', 'success');
        } else {
            showFinanceToast(data.message || 'Supplier link has been emailed.', 'success');
        }
    }

    async function resendSupplierForm(id) {
        const res = await fetch(`/finance/${id}/share-supplier-link`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to resend supplier form.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
        if (data.link) {
            await navigator.clipboard.writeText(data.link).catch(() => {});
            showFinanceToast('Supplier completion form resent and copied to clipboard.', 'success');
        } else {
            showFinanceToast(data.message || 'Supplier completion form resent.', 'success');
        }
    }

    async function changeSupplierEmailAndResend(id) {
        const record = getRecordById(id);
        if (!record) return;

        const currentEmail = record.data?.email_address || '';
        const email = prompt('Enter the new supplier email address:', currentEmail);
        if (!email) return;

        const formData = new FormData();
        formData.append('email_address', email.trim());

        const res = await fetch(`/finance/${id}/supplier-email`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await res.json();
        if (!res.ok) {
            const firstErrorKey = data.errors ? Object.keys(data.errors)[0] : null;
            const firstErrorMessage = firstErrorKey && data.errors[firstErrorKey] ? data.errors[firstErrorKey][0] : '';
            alert(firstErrorMessage || data.message || 'Unable to update supplier email.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
        if (data.link) {
            await navigator.clipboard.writeText(data.link).catch(() => {});
            showFinanceToast('Supplier email updated and completion form resent.', 'success');
        } else {
            showFinanceToast(data.message || 'Supplier email updated and completion form resent.', 'success');
        }
    }

    async function copySupplierLink(link) {
        if (!link) return;
        await navigator.clipboard.writeText(link).catch(() => {});
        showFinanceToast('Supplier link copied to clipboard.', 'success');
    }

    function printFinanceRecord(id) {
        const record = getRecordById(id);
        if (!record) return;

        const moduleConfig = getModuleConfig(record.module_key);
        const fields = moduleConfig.fields || [];
        const doc = window.open('', '_blank', 'width=1100,height=900');
        if (!doc) return;

        doc.document.write(`
            <html>
                <head>
                    <title>${escapeHtml(moduleConfig.label)} - ${escapeHtml(record.record_number || '')}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 32px; color: #111827; }
                        .card { border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; }
                        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
                        .item { border: 1px solid #f3f4f6; border-radius: 12px; padding: 12px; }
                        .label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; }
                        .value { margin-top: 6px; font-weight: 600; }
                        .section { margin-top: 20px; }
                        .row { display: flex; justify-content: space-between; gap: 16px; border-bottom: 1px dashed #e5e7eb; padding: 8px 0; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; gap:16px; border-bottom:1px solid #f3f4f6; padding-bottom:16px; margin-bottom:20px;">
                            <div>
                                <div style="font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Finance Operations</div>
                                <h1 style="margin:8px 0 4px; font-size:28px;">${escapeHtml(moduleConfig.label)}</h1>
                                <div style="color:#6b7280;">${escapeHtml(record.display_label || '')}</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:11px; color:#6b7280;">Workflow</div>
                                <div style="font-weight:600;">${escapeHtml(record.workflow_status || '')}</div>
                                <div style="font-size:11px; color:#6b7280; margin-top:12px;">Approval</div>
                                <div style="font-weight:600;">${escapeHtml(record.approval_status || '')}</div>
                            </div>
                        </div>

                        <div class="grid">
                            <div class="item"><div class="label">Number</div><div class="value">${escapeHtml(record.record_number || 'N/A')}</div></div>
                            <div class="item"><div class="label">${escapeHtml(getModuleConfig(record.module_key).recordTitleLabel || 'Name')}</div><div class="value">${escapeHtml(record.record_title || 'N/A')}</div></div>
                            <div class="item"><div class="label">Date</div><div class="value">${escapeHtml(record.record_date || 'N/A')}</div></div>
                            <div class="item"><div class="label">Time</div><div class="value">${escapeHtml(record.data?.transaction_time || 'N/A')}</div></div>
                            ${shouldShowGenericAmount(record) ? `<div class="item"><div class="label">Amount</div><div class="value">${escapeHtml(record.amount ? formatCurrency(record.amount) : 'N/A')}</div></div>` : ''}
                            <div class="item"><div class="label">Status</div><div class="value">${escapeHtml(record.status || 'Active')}</div></div>
                            <div class="item"><div class="label">Created By</div><div class="value">${escapeHtml(record.user || '')}</div></div>
                        </div>

                        <div class="section">
                            <h2>Module Fields</h2>
                            ${fields.map((field) => `
                                <div class="row">
                                    <div style="color:#6b7280;">${escapeHtml(field.label)}</div>
                                    <div style="font-weight:600; text-align:right; max-width:60%;">${escapeHtml(getFormDisplayValue(field, getFieldValue(record, field.name), record.data || {}))}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <script>window.onload = function(){ window.print(); };</script>
                </body>
            </html>
        `);
        doc.document.close();
    }

    function changeModule(moduleKey) {
        currentModuleKey = moduleKey;
        currentWorkflowFilter = 'all';
        supplierCompletionMode = moduleKey === 'supplier' ? 'complete_internally' : 'complete_internally';
        const url = new URL(window.location.href);
        url.searchParams.set('module', moduleKey);
        url.searchParams.delete('workflow_status');
        window.history.replaceState({}, '', url);
        refreshFinanceView();
        requestAnimationFrame(() => {
            const activeTab = document.getElementById(`finance-tab-${moduleKey}`);
            activeTab?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        });
        closePreview();
    }

    function changeWorkflow(workflowStatus) {
        currentWorkflowFilter = workflowStatus;
        const url = new URL(window.location.href);
        url.searchParams.set('module', currentModuleKey);
        if (workflowStatus === 'all') {
            url.searchParams.delete('workflow_status');
        } else {
            url.searchParams.set('workflow_status', workflowStatus);
        }
        window.history.replaceState({}, '', url);
        refreshFinanceView();
        closePreview();
    }

    function initializeFinancePage() {
        const url = new URL(window.location.href);
        const moduleParam = url.searchParams.get('module');
        const workflowParam = url.searchParams.get('workflow_status');

        if (moduleParam && financeModules[moduleParam]) {
            currentModuleKey = moduleParam;
        }

        if (workflowParam && workflowFilters.includes(workflowParam)) {
            currentWorkflowFilter = workflowParam;
        }

        refreshFinanceView();
        renderTableHeader();
        renderTableRows();
        requestAnimationFrame(() => {
            const activeTab = document.getElementById(`finance-tab-${currentModuleKey}`);
            activeTab?.scrollIntoView({ behavior: 'auto', inline: 'center', block: 'nearest' });
        });
    }

    window.financeModule = {
        changeModule,
        changeWorkflow,
        changeSupplierCompletionMode,
        fetchLiquidationSource,
        openPendingLiquidationBranch,
        dismissPendingLiquidationBranch,
        openLookupSelector,
        closeLookupSelector,
        selectLookupSelectorValue,
        selectBankAccountLookupValue,
        toggleRecordNumberEditMode,
        changePreviewTab,
        openFinanceDrawer,
        closeFinanceDrawer,
        openPreview,
        closePreview,
        getRecordById,
        saveFinanceRecord,
        submitFinanceRecord,
        approveFinanceRecord,
        revertFinanceRecord,
        archiveFinanceRecord,
        shareSupplierRecord,
        resendSupplierForm,
        changeSupplierEmailAndResend,
        previewAttachment,
        copySupplierLink,
        printFinanceRecord,
        scrollFinanceModuleTabs,
        addPrLineItemRow,
        removePrLineItemRow,
        addDvLineItemRow,
        removeDvLineItemRow,
    };

    window.openFinanceDrawer = () => window.financeModule.openFinanceDrawer();
    window.closeFinanceDrawer = () => window.financeModule.closeFinanceDrawer();
    window.closePreview = () => window.financeModule.closePreview();
    window.changePreviewTab = (tab) => window.financeModule.changePreviewTab(tab);
    window.changeSupplierCompletionMode = (mode) => window.financeModule.changeSupplierCompletionMode(mode);
    window.openLookupSelector = (fieldName, source, label, filterKey) => window.financeModule.openLookupSelector(fieldName, source, label, filterKey);
    window.closeLookupSelector = () => window.financeModule.closeLookupSelector();
    window.selectLookupSelectorValue = (value, label) => window.financeModule.selectLookupSelectorValue(value, label);
    window.selectBankAccountLookupValue = (value, label) => window.financeModule.selectBankAccountLookupValue(value, label);
    window.toggleRecordNumberEditMode = () => window.financeModule.toggleRecordNumberEditMode();
    window.saveFinanceRecord = (event) => window.financeModule.saveFinanceRecord(event);
    window.scrollFinanceModuleTabs = (amount) => window.financeModule.scrollFinanceModuleTabs(amount);
    window.addPrLineItemRow = () => window.financeModule.addPrLineItemRow();
    window.removePrLineItemRow = (button) => window.financeModule.removePrLineItemRow(button);

    $('financeLookupSelectorSearch')?.addEventListener('input', () => renderLookupSelectorModal());
    $('financeLookupSelectorSearch')?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLookupSelector();
        }
    });

    $('bankAccountLookupSearch')?.addEventListener('input', (event) => {
        activeBankAccountLookupQuery = event.target.value || '';
        renderBankAccountLookupList(activeBankAccountLookupQuery);
    });

    $('bankAccountLookupSearch')?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.target.value = '';
            activeBankAccountLookupQuery = '';
            renderBankAccountLookupList('');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLookupSelector();
        }
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('#bankAccountLookupList [data-bank-account-option-value]');
        if (!button) return;

        const value = button.getAttribute('data-bank-account-option-value') || '';
        const label = button.getAttribute('data-bank-account-option-label') || '';
        window.financeModule.selectBankAccountLookupValue(value, label);
    });

        $('recordNumberInput').addEventListener('input', renderDrawerPreview);
        $('recordDateInput').addEventListener('input', renderDrawerPreview);
        $('recordTimeInput').addEventListener('input', renderDrawerPreview);
    $('amountInput').addEventListener('input', renderDrawerPreview);
    $('attachmentsInput').addEventListener('change', renderDrawerPreview);

    initializeFinancePage();
})();
