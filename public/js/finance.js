(() => {
    const bootstrap = window.financeBootstrap || {};
    const csrfToken = bootstrap.csrfToken || '';

    function currentCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value
            || csrfToken
            || '';
    }

    function csrfFetch(url, options = {}) {
        const headers = new Headers(options.headers || {});
        const token = currentCsrfToken();

        if (token) {
            headers.set('X-CSRF-TOKEN', token);
        }
        headers.set('X-Requested-With', 'XMLHttpRequest');

        return fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });
    }
    const workflowFilters = ['all', 'Uploaded', 'Shared', 'Submitted', 'On Hold', 'Accepted', 'Reverted', 'Archived', 'Delete Requested'];

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
        'data.cash_release_date': 'Cash release date',
        'data.cash_release_time': 'Cash release time',
        'data.paid_through': 'Paid through',
        'data.linked_lr_id': 'Linked LR',
        'data.linked_po_id': 'Linked PO',
        'data.linked_dv_id': 'Linked DV',
        'data.linked_crf_id': 'Linked CRF',
        'data.cash_receiver_name': 'Name of receiver',
        'data.recipient_bank_account': 'Bank account',
        'data.recipient_bank_number': 'Bank number',
        'data.bank_account_id': 'Bank account',
        'data.funding_bank_account_id': 'Funding bank account',
        'data.receiving_bank_account_id': 'Receiving bank / cash account',
        'data.source_bank_account_id': 'Source bank account',
        'data.destination_bank_account_id': 'Destination bank account',
        'data.source_document_type': 'Linked document type',
        'data.source_document_id': 'Linked source document',
        'data.payroll_period_id': 'Payroll period',
        'data.master_item_type': 'Item type',
        'data.master_item_id': 'Item',
        'data.linked_item_type': 'Item type',
        'data.linked_item_id': 'Item / service',
        'data.payroll_expense_coa_id': 'Payroll expense account',
        'data.asset_coa_id': 'Asset account',
        'data.asset_code': 'Asset code',
        'data.legal_acknowledgment': 'Legal acknowledgment',
        'data.electronic_signature_consent': 'Electronic submission consent',
        'data.data_privacy_consent': 'Data privacy consent',
        'data.confidentiality_undertaking': 'Confidentiality undertaking',
        'data.company_policy_compliance': 'Company policy compliance',
        'data.false_information_penalty': 'Penalty for false information',
        'data.first_approver_user_id': 'First approver',
        'data.second_approver_user_id': 'Second approver',
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

    function currentDateTimeValue() {
        const now = new Date();
        const date = now.toISOString().slice(0, 10);
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${date} ${hours}:${minutes}`;
    }

    const supplierEntityOptions = [
        'Sole Proprietorship',
        'Partnership',
        'Corporation',
        'One Person Corporation (OPC)',
        'Cooperative',
        'Freelancer / Individual Professional',
        'Independent Contractor',
        'Government Agency',
        'Non-Profit Organization',
        'Foreign Company',
        'Others',
    ].map((value) => ({ value, label: value }));

    const supplierCorporationTypeOptions = [
        'Domestic Stock Corporation',
        'Domestic Non-Stock Corporation',
        'Close Corporation',
        'Foreign Corporation',
        'Branch Office',
        'Representative Office',
        'Regional Headquarters',
        'Regional Operating Headquarters',
    ].map((value) => ({ value, label: value }));

    const supplierCategoryOptions = [
        'Product Supplier',
        'Service Provider',
        'Contractor',
        'Consultant',
        'Marketing Agency',
        'IT / Software Provider',
        'Logistics Provider',
        'Printing Supplier',
        'Professional Services',
        'Outsourcing Partner',
        'Equipment Supplier',
        'Office Supplies Supplier',
        'Others',
    ].map((value) => ({ value, label: value }));

    const paymentTermOptions = [
        'Cash on Delivery',
        'Upon Order',
        'Upon Completion',
        'Weekly',
        'Monthly',
        '7 Days',
        '15 Days',
        '30 Days',
        '45 Days',
        '60 Days',
        'Progress Billing',
        'Retainer-Based',
        'Others',
    ].map((value) => ({ value, label: value }));

    const preferredPaymentMethodOptions = [
        'Bank Transfer',
        'Check',
        'Online Payment',
        'Others',
    ].map((value) => ({ value, label: value }));

    const idTypeOptions = [
        'Passport',
        "Driver's License",
        'National ID',
        'PRC ID',
        'Company ID',
        'UMID',
        'SSS ID',
        'PhilHealth ID',
        "Voter's ID",
        'Postal ID',
        'Others',
    ].map((value) => ({ value, label: value }));

    const supplierLegalAcknowledgments = [
        {
            name: 'legal_acknowledgment',
            label: 'Legal Acknowledgment, Consent, and Electronic Signature',
            statement: 'I acknowledge that by completing, submitting, and/or electronically signing this Supplier Completion Form, I am confirming that all information and documents submitted are true, correct, complete, authentic, and updated. I further certify that I am duly authorized to submit this form on behalf of the supplier, entity, organization, company, or individual identified herein.',
        },
        {
            name: 'electronic_signature_consent',
            label: 'Electronic Submission and Signature Consent',
            statement: 'I agree that my submission of this form, including any typed name, uploaded signature, checked acknowledgment box, uploaded ID, email confirmation, or electronic submission, shall be treated as my valid signature and confirmation, pursuant to the Electronic Commerce Act of 2000, Republic Act No. 8792, which recognizes electronic documents and electronic signatures.',
        },
        {
            name: 'data_privacy_consent',
            label: 'Data Privacy Consent',
            statement: 'I consent to the collection, use, processing, verification, storage, retention, and sharing of the submitted personal information, business information, and documents for supplier accreditation, due diligence, procurement, payment processing, compliance, audit, legal, security, and business purposes, in accordance with the Data Privacy Act of 2012, Republic Act No. 10173.',
        },
        {
            name: 'confidentiality_undertaking',
            label: 'Confidentiality and NDA Undertaking',
            statement: 'I agree that all confidential, proprietary, client, operational, financial, technical, legal, business, and company information obtained from the Company shall remain strictly confidential and shall not be disclosed, copied, transferred, shared, or used without prior written authority from the Company.',
        },
        {
            name: 'company_policy_compliance',
            label: 'Compliance with Company Policies',
            statement: 'I agree that the Supplier shall comply with all applicable laws, rules, regulations, contracts, procurement policies, internal procedures, company memoranda, confidentiality obligations, data privacy requirements, and lawful instructions issued by the Company.',
        },
        {
            name: 'false_information_penalty',
            label: 'Penalty for False Information',
            statement: 'I understand that any false statement, concealment, misrepresentation, falsification, fraudulent document, or unauthorized submission may result in denial of accreditation, suspension, blacklisting, termination of engagement, withholding of payment, recovery of damages, and appropriate civil, criminal, administrative, or legal action, including liability for perjury or false testimony under applicable law. Article 183 of the Revised Penal Code covers false testimony in other cases and perjury in solemn affirmation.',
        },
    ];

    const supplierAttachmentRules = {
        corporation: [
            'SEC Certificate of Registration',
            'BIR 2303 Certificate of Registration',
            "Mayor's Permit / Business Permit",
            'Valid ID of Authorized Representative',
            'Company Profile',
            'Contract / Agreement',
        ],
        sole: [
            'DTI Certificate of Registration',
            'BIR 2303 Certificate of Registration',
            "Mayor's Permit / Business Permit",
            'Valid ID of Owner / Authorized Representative',
            'Business Profile / Company Profile',
            'Contract / Agreement',
        ],
        cooperative: [
            'CDA Certificate of Registration',
            'BIR 2303 Certificate of Registration',
            "Mayor's Permit / Business Permit",
            'Valid ID of Authorized Representative',
            'Cooperative Profile',
            'Contract / Agreement',
        ],
        individual: [
            'Resume',
            'Valid Government ID',
            'TIN / BIR Registration, if applicable',
            'Resume / Portfolio, if applicable',
            'Professional License, if applicable',
            'Signed Contract / Agreement',
        ],
        government: [
            'Agency Profile / Official Agency Information',
            'Authorized Representative ID',
            'Authority to Transact / Authorization Letter, if applicable',
            'Contract / Agreement / Purchase Order',
        ],
        nonprofit: [
            'SEC Registration / Relevant Registration Certificate',
            'BIR 2303 Certificate of Registration, if applicable',
            "Mayor's Permit / Business Permit, if applicable",
            'Valid ID of Authorized Representative',
            'Organization Profile',
            'Contract / Agreement',
        ],
        others: [
            'Valid Registration Document, if applicable',
            'Valid ID of Authorized Representative',
            'Supplier Profile',
            'Contract / Agreement',
            'Other supporting documents required by the Company',
        ],
    };

    function supplierAttachmentLabelsForEntity(entityType) {
        if (['Corporation', 'One Person Corporation (OPC)', 'Partnership', 'Foreign Company'].includes(entityType)) {
            return supplierAttachmentRules.corporation;
        }
        if (entityType === 'Sole Proprietorship') return supplierAttachmentRules.sole;
        if (entityType === 'Cooperative') return supplierAttachmentRules.cooperative;
        if (['Freelancer / Individual Professional', 'Independent Contractor'].includes(entityType)) return supplierAttachmentRules.individual;
        if (entityType === 'Government Agency') return supplierAttachmentRules.government;
        if (entityType === 'Non-Profit Organization') return supplierAttachmentRules.nonprofit;
        if (entityType === 'Others') return supplierAttachmentRules.others;
        return [];
    }

    function supplierAttachmentSlug(label) {
        return String(label || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function supplierRegistrationLabel(entityType) {
        if (['Corporation', 'One Person Corporation (OPC)', 'Partnership', 'Foreign Company', 'Non-Profit Organization'].includes(entityType)) {
            return 'SEC Registration No.';
        }
        if (entityType === 'Sole Proprietorship') return 'DTI Registration No.';
        if (entityType === 'Cooperative') return 'CDA Registration No.';
        if (['Freelancer / Individual Professional', 'Independent Contractor'].includes(entityType)) return 'Professional License No.';
        if (entityType === 'Government Agency') return 'Government ID No.';
        return 'Registration Number';
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

        return '';
    }

    function getVisibleRecordTitle(record) {
        const rawTitle = String(record?.record_title || '').trim();
        if (!rawTitle) {
            return '';
        }

        const defaultTitle = String(getModuleConfig(record?.module_key)?.recordTitleLabel || '').trim();
        if (defaultTitle && rawTitle.toLowerCase() === defaultTitle.toLowerCase()) {
            return '';
        }

        return rawTitle;
    }

    function isFinanceRecordTitleRequired(moduleKey) {
        return ['supplier', 'service', 'product', 'chart_account', 'bank_account'].includes(moduleKey);
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

    function generateFinanceTapeBarcodeSvg(value) {
        const text = String(value || '').trim();
        if (!text) return '';

        const normalized = text.replace(/\s+/g, '').toUpperCase();
        if (!normalized) return '';

        let seed = 0;
        Array.from(normalized).forEach((char, index) => {
            seed += char.charCodeAt(0) * (index + 5);
        });

        let bits = '101';
        Array.from(normalized).forEach((char) => {
            const code = char.charCodeAt(0) ^ (seed & 0xff);
            bits += code.toString(2).padStart(8, '0');
        });
        bits += '1101';

        const unit = 1.35;
        const quietZone = 5;
        const height = 28;
        let cursor = quietZone;
        let current = bits[0] || '0';
        let runLength = 0;
        let rects = '';

        const flushRun = () => {
            if (runLength > 0 && current === '1') {
                rects += `<rect x="${cursor.toFixed(2)}" y="3" width="${(runLength * unit).toFixed(2)}" height="${height}" fill="#111827"></rect>`;
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

        const width = Math.max(150, cursor + quietZone);
        const label = escapeHtml(text);

        return `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${width} 34" role="img" aria-label="Barcode for ${label}">
                <rect x="0" y="0" width="${width}" height="34" fill="#ffffff"></rect>
                ${rects}
            </svg>
        `;
    }

    function printArfAssetTag(assetCode, location = '', serialNumber = '') {
        const code = String(assetCode || '').trim() || 'N/A';
        const labelLocation = String(location || '').trim();
        const labelSerial = String(serialNumber || '').trim();
        const barcodeSvg = generateFinanceTapeBarcodeSvg(code === 'N/A' ? '' : code);
        const doc = window.open('', '_blank', 'width=520,height=260');
        if (!doc) return;

        doc.document.write(`
            <html>
                <head>
                    <title>15mm Asset Tag - ${escapeHtml(code)}</title>
                    <style>
                        @page { size: 70mm 15mm; margin: 0; }
                        * { box-sizing: border-box; }
                        html, body {
                            width: 70mm;
                            height: 15mm;
                            margin: 0;
                            padding: 0;
                            background: #fff;
                            color: #111827;
                            font-family: Arial, Helvetica, sans-serif;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        body {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .tape {
                            width: 68mm;
                            height: 13mm;
                            display: grid;
                            grid-template-columns: 22mm 1fr;
                            gap: 1.5mm;
                            align-items: center;
                            overflow: hidden;
                            border: 0.2mm solid #111827;
                            border-radius: 1mm;
                            padding: 1mm;
                        }
                        .brand {
                            min-width: 0;
                            border-right: 0.2mm solid #111827;
                            padding-right: 1.2mm;
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                        }
                        .company {
                            font-size: 4.5pt;
                            font-weight: 700;
                            letter-spacing: 0.7pt;
                            text-transform: uppercase;
                            white-space: nowrap;
                        }
                        .title {
                            margin-top: 0.5mm;
                            font-size: 5pt;
                            font-weight: 900;
                            letter-spacing: 0.8pt;
                            text-transform: uppercase;
                        }
                        .meta {
                            margin-top: 0.7mm;
                            font-size: 3.8pt;
                            line-height: 1.05;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .main {
                            min-width: 0;
                            height: 100%;
                            display: grid;
                            grid-template-rows: auto 1fr;
                            gap: 0.5mm;
                        }
                        .code {
                            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
                            font-size: 7pt;
                            line-height: 1;
                            font-weight: 900;
                            letter-spacing: 0.2pt;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .barcode {
                            width: 100%;
                            height: 7mm;
                            overflow: hidden;
                        }
                        .barcode svg {
                            display: block;
                            width: 100%;
                            height: 100%;
                        }
                        @media screen {
                            html, body {
                                width: 100%;
                                height: 100%;
                                min-height: 180px;
                                background: #f3f4f6;
                            }
                            .tape {
                                background: #fff;
                                transform: scale(2.4);
                                transform-origin: center;
                                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="tape">
                        <div class="brand">
                            <div class="company">JK&amp;C INC.</div>
                            <div class="title">Asset</div>
                            <div class="meta">${escapeHtml(labelLocation || 'No location')}</div>
                            <div class="meta">${escapeHtml(labelSerial || 'No serial')}</div>
                        </div>
                        <div class="main">
                            <div class="code">${escapeHtml(code)}</div>
                            <div class="barcode">${barcodeSvg || '<div style="height:7mm;border:0.2mm dashed #9ca3af;"></div>'}</div>
                        </div>
                    </div>
                    <script>window.onload = function(){ window.print(); };</script>
                </body>
            </html>
        `);
        doc.document.close();
    }

    function renderArfAssetTagPrintButton(assetCode, location, serialNumber, classes = '') {
        const printArgs = [assetCode || '', location || '', serialNumber || '']
            .map((value) => JSON.stringify(String(value)))
            .join(', ');

        return `
            <button
                type="button"
                onclick="window.financeModule.printArfAssetTag(${escapeHtml(printArgs)})"
                class="${classes || 'inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50'}"
            >
                Print 15mm Tape
            </button>
        `;
    }

    function renderArfAssetTagCard(assetCode, location, serialNumber, barcodeSvg, options = {}) {
        const printButton = options.withPrintButton
            ? `<div class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-right">${renderArfAssetTagPrintButton(assetCode, location, serialNumber)}</div>`
            : '';

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
                ${printButton}
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

    function isSupplierCompletionFinished(record = null) {
        return Boolean(record?.supplier_completed_at);
    }

    function isSupplierDispatchLayout(record = null) {
        return isSendToSupplierMode() && !isSupplierCompletionFinished(record);
    }

    function shouldShowSpecifyOtherField(fieldName, formValues = {}) {
        const cleanName = String(fieldName || '').trim();
        if (!cleanName.endsWith('_other')) {
            if (cleanName === 'other_business_purpose_specify') {
                return String(formValues['data[cash_advance_type]'] || '').trim() === 'Other Business Purpose';
            }

            if (cleanName === 'other_expense_specify') {
                const selectedUsageCategories = Array.isArray(formValues['data[usage_categories][]'])
                    ? formValues['data[usage_categories][]']
                    : (Array.isArray(formValues['data[usage_categories]']) ? formValues['data[usage_categories]'] : []);
                return selectedUsageCategories.map((item) => String(item).trim()).includes('Other Expense');
            }

            return true;
        }

        const baseFieldName = cleanName.replace(/_other$/, '');
        const selectValue = String(formValues[`data[${baseFieldName}]`] ?? formValues[baseFieldName] ?? '').trim();
        const checkboxValues = Array.isArray(formValues[`data[${baseFieldName}][]`])
            ? formValues[`data[${baseFieldName}][]`]
            : (Array.isArray(formValues[baseFieldName]) ? formValues[baseFieldName] : null);

        if (checkboxValues) {
            return checkboxValues.map((item) => String(item).trim()).includes('Others');
        }

        return selectValue === 'Others';
    }

    function getFinanceSourceFieldValue(source = {}, fieldName) {
        const directValue = source?.[fieldName];
        if (directValue !== undefined) {
            return directValue;
        }

        const bracketValue = source?.[`data[${fieldName}]`];
        if (bracketValue !== undefined) {
            return bracketValue;
        }

        const arrayValue = source?.[`${fieldName}[]`];
        if (arrayValue !== undefined) {
            return arrayValue;
        }

        const bracketArrayValue = source?.[`data[${fieldName}][]`];
        if (bracketArrayValue !== undefined) {
            return bracketArrayValue;
        }

        return '';
    }

    function shouldShowSupplierPreviewField(fieldName, source = {}) {
        const cleanName = String(fieldName || '').trim();
        if (!cleanName) return true;

        if (cleanName === 'entity_type_other') {
            return String(getFinanceSourceFieldValue(source, 'entity_type') || '').trim() === 'Others';
        }

        if (cleanName === 'corporation_type') {
            return ['Corporation', 'One Person Corporation (OPC)', 'Foreign Company'].includes(String(getFinanceSourceFieldValue(source, 'entity_type') || '').trim());
        }

        if (cleanName === 'supplier_category_other') {
            const value = getFinanceSourceFieldValue(source, 'supplier_category');
            const categories = Array.isArray(value) ? value : (Array.isArray(source?.['data[supplier_category][]']) ? source['data[supplier_category][]'] : []);
            return categories.map((item) => String(item).trim()).includes('Others');
        }

        if (cleanName === 'payment_terms_other') {
            return String(getFinanceSourceFieldValue(source, 'payment_terms') || '').trim() === 'Others';
        }

        if (cleanName === 'preferred_payment_method_other') {
            return String(getFinanceSourceFieldValue(source, 'preferred_payment_method') || '').trim() === 'Others';
        }

        if (cleanName === 'online_payment_details') {
            return String(getFinanceSourceFieldValue(source, 'preferred_payment_method') || '').trim() === 'Online Payment';
        }

        if (cleanName === 'id_type_other') {
            return String(getFinanceSourceFieldValue(source, 'id_type') || '').trim() === 'Others';
        }

        return true;
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

    function setSupplierFormLayout(record = null) {
        const isSend = isSupplierDispatchLayout(record);
        [
            'recordCoreFields',
            'recordMetaFields',
            'statusField',
            'attachmentsSection',
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

        const recordTitleInput = $('recordTitleInput');
        if (recordTitleInput) {
            recordTitleInput.required = !isSend && isFinanceRecordTitleRequired(currentModuleKey);
        }
    }

    const financeModules = {
        supplier: {
            label: 'Supplier',
            addLabel: 'Add Supplier',
            recordNumberLabel: 'Supplier Code',
            recordTitleLabel: 'Registered Business Name',
            recordDateLabel: 'Date Accomplished',
            summaryKeys: ['entity_type', 'representative_full_name', 'email_address', 'completion_mode'],
            fields: [
                selectField('completion_mode', 'Completion Mode', {
                    options: [
                        { value: 'complete_internally', label: 'Complete Internally' },
                        { value: 'send_to_supplier', label: 'Send to Supplier' },
                    ],
                    required: true,
                }),
                dateField('date_accomplished', 'Date Accomplished'),
                textField('trade_name', 'Trade Name / Brand Name'),
                selectField('entity_type', 'Entity Type', { options: supplierEntityOptions, required: true }),
                textField('entity_type_other', 'Specify Other Entity Type'),
                selectField('corporation_type', 'If Corporation, Specify Corporation Type', { options: supplierCorporationTypeOptions }),
                textField('registration_number', 'Registration Number'),
                textField('tin', 'Tax Identification Number (TIN)'),
                selectField('vat_status', 'VAT Status', {
                    options: [
                        { value: 'VAT Registered', label: 'VAT Registered' },
                        { value: 'Non-VAT', label: 'Non-VAT' },
                        { value: 'Percentage Tax', label: 'Percentage Tax' },
                        { value: 'Tax Exempt', label: 'Tax Exempt' },
                    ],
                }),
                textField('business_permit_number', 'Business Permit Number'),
                dateField('permit_expiry_date', 'Permit Expiry Date'),
                textareaField('nature_of_business', 'Nature of Business'),
                textareaField('products_services_offered', 'Products / Services Offered'),
                checkboxGroupField('supplier_category', 'Supplier Category', { options: supplierCategoryOptions }),
                textField('supplier_category_other', 'Specify Other Supplier Category'),
                numberField('years_in_operation', 'Years in Operation'),
                textareaField('registered_address', 'Registered Address'),
                textareaField('office_address', 'Office Address'),
                textareaField('warehouse_address', 'Warehouse Address'),
                textField('telephone_number', 'Telephone Number'),
                textField('mobile_number', 'Mobile Number'),
                textField('email_address', 'Official Email Address', { inputType: 'email', required: true }),
                textField('website_social_media', 'Website / Social Media'),
                textField('representative_full_name', 'Authorized Representative Full Name', { required: true }),
                textField('designation', 'Authorized Representative Position / Designation'),
                textField('phone_number', 'Authorized Representative Mobile Number', { required: true }),
                textField('representative_email_address', 'Authorized Representative Email Address', { inputType: 'email' }),
                textareaField('billing_address', 'Billing Address'),
                textField('accounting_contact_person', 'Accounting Contact Person'),
                textField('accounting_contact_number', 'Accounting Contact Number'),
                textField('accounting_email_address', 'Accounting Email Address', { inputType: 'email' }),
                selectField('payment_terms', 'Payment Terms', { options: paymentTermOptions }),
                textField('payment_terms_other', 'Specify Other Payment Terms'),
                selectField('preferred_payment_method', 'Preferred Payment Method', { options: preferredPaymentMethodOptions }),
                textField('online_payment_details', 'Online Payment Details'),
                textField('preferred_payment_method_other', 'Specify Other Payment Method'),
                textField('bank_name', 'Bank Name'),
                textField('bank_branch', 'Bank Branch'),
                textField('bank_account_name', 'Bank Account Name'),
                textField('bank_account_number', 'Bank Account Number'),
                textField('swift_code', 'Swift Code'),
                ...supplierLegalAcknowledgments.map((item) => ({
                    name: item.name,
                    label: item.label,
                    statement: item.statement,
                    type: 'acknowledgment',
                    required: true,
                    fullWidth: true,
                })),
                textField('person_accomplishing_full_name', 'Person Accomplishing the Form Full Name', { required: true }),
                textField('person_accomplishing_position', 'Position / Designation', { required: true }),
                selectField('id_type', 'ID Type', { options: idTypeOptions }),
                textField('id_type_other', 'Specify Other ID Type'),
                textField('id_number', 'ID Number'),
                textField('date_signed', 'Date Signed', { readOnly: true, autoDateTime: true }),
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
                    options: getFinanceTaxTypeOptions(),
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
                    options: getFinanceTaxTypeOptions(),
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
            recordTitleLabel: 'Title',
            recordDateLabel: 'Date',
            summaryKeys: ['requestor', 'project', 'cost_center', 'for_client', 'needed_date', 'grand_total'],
            fields: [
                textField('requesting_department', 'Department'),
                selectField('requester_mode', 'Requester Option', {
                    options: [
                        { value: 'own_request', label: 'Own Request' },
                        { value: 'request_for_another', label: 'Request for Another' },
                    ],
                    required: true,
                }),
                selectField('requester_employee_id', 'Employee List', { source: 'employee', placeholder: 'Select employee profile' }),
                textField('requestor', 'Employee Name', { required: true }),
                selectField('request_type', 'Type', {
                    options: [
                        { value: 'Service', label: 'Service' },
                        { value: 'Product', label: 'Product' },
                    ],
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
                        { value: 'Both', label: 'Both' },
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
                textField('department', 'Department'),
                textField('superior', 'Superior'),
                textField('superior_email', 'Superior Email', { inputType: 'email' }),
                textField('project', 'Project', { required: true }),
                textField('cost_center', 'Cost Center', { required: true }),
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
                        { value: 'service', label: 'Service' },
                    ],
                }),
                selectField('master_item_id', 'Item Selected', {
                    sourceMap: { product: 'product', service: 'service' },
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
                selectField('tax_type', 'Tax Classification', {
                    options: getFinanceTaxTypeOptions(),
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
            summaryKeys: ['linked_pr_id', 'supplier_id', 'project', 'cost_center', 'total_amount', 'expected_delivery_date'],
            fields: [
                selectField('linked_pr_id', 'Linked PR', { source: 'pr' }),
                selectField('supplier_id', 'Supplier', { source: 'supplier', required: true }),
                textField('project', 'Project', { readOnly: true }),
                textField('cost_center', 'Cost Center', { readOnly: true }),
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
            recordTitleLabel: 'Cash Advance Request',
            recordDateLabel: 'Date',
            summaryKeys: ['cash_advance_type', 'amount_requested', 'mode_of_release', 'release_schedule'],
            fields: [
                selectField('requester_mode', 'Requester Option', {
                    options: [
                        { value: 'own_request', label: 'Own Request' },
                        { value: 'request_for_another', label: 'Request for Another' },
                    ],
                    required: true,
                }),
                selectField('requester_employee_id', 'Employee List', { source: 'employee', placeholder: 'Select employee profile' }),
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
                selectField('requester_employee_id', 'Employee List', { source: 'employee', placeholder: 'Select employee profile' }),
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
            summaryKeys: ['linked_lr_id', 'amount', 'reimbursement_mode', 'bank_account_id'],
            fields: [
                selectField('requester_mode', 'Requester Option', {
                    options: [
                        { value: 'own_request', label: 'Own Request' },
                        { value: 'request_for_another', label: 'Request for Another' },
                    ],
                    required: true,
                }),
                selectField('requester_employee_id', 'Employee List', { source: 'employee', placeholder: 'Select employee profile' }),
                textField('requestor', 'Requestor', { required: true }),
                selectField('linked_lr_id', 'Linked LR', { source: 'lr_shortage', required: true }),
                textareaField('expense_details', 'Expense Details'),
                numberField('amount', 'Amount', { required: true }),
                textField('reimbursement_payment_details', 'Reimbursement Payment Details', { fullWidth: true }),
                checkboxField('manual_liquidation_entry', 'Manually edit reimbursement details', { fullWidth: true, help: 'Use this when the linked liquidation needs to be reviewed or entered again manually.' }),
                selectField('reimbursement_mode', 'Mode of Reimbursement', {
                    required: true,
                    options: [
                        { value: 'Cash', label: 'Cash' },
                        { value: 'Bank Transfer', label: 'Bank Transfer' },
                        { value: 'Check', label: 'Check' },
                    ],
                }),
                textField('cash_receiver_name', 'Name of Receiver'),
                textField('recipient_bank_account', 'Bank Account'),
                textField('recipient_bank_number', 'Bank Number'),
                selectField('bank_account_id', 'Bank Account Source', { source: 'bank_account' }),
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
                        { value: 'po', label: 'PO' },
                        { value: 'ca', label: 'CA' },
                        { value: 'err', label: 'ERR' },
                        { value: 'pda', label: 'PDA' },
                        { value: 'ibtf', label: 'IBTF' },
                    ],
                }),
                selectField('source_document_id', 'Linked Source Document', {
                    sourceMap: {
                        po: 'po',
                        ca: 'ca',
                        err: 'err',
                        pda: 'pda',
                        ibtf: 'ibtf',
                    },
                    sourceKey: 'source_document_type',
                }),
                textField('payee_type', 'Payee Type', { readOnly: true }),
                textField('payee_name', 'Payee', { readOnly: true }),
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
            summaryKeys: ['payroll_period_id', 'employee_count', 'basic_salary_total', 'gross_pay_total', 'deductions_total', 'total_payroll_amount', 'funding_bank_account_id', 'payroll_expense_coa_id'],
            fields: [
                selectField('payroll_period_id', 'Payroll Period', { source: 'payroll_period', required: true }),
                dateField('period_start', 'Period Start', { readOnly: true }),
                dateField('period_end', 'Period End', { readOnly: true }),
                dateField('payroll_start', 'Payroll Start', { readOnly: true }),
                dateField('payroll_end', 'Payroll End', { readOnly: true }),
                dateField('pay_date', 'Pay Date', { readOnly: true }),
                numberField('total_payroll_amount', 'Total Payroll Amount', { required: true, readOnly: true }),
                textField('employee_count', 'Employees Included', { readOnly: true }),
                numberField('basic_salary_total', 'Basic Salary Total', { readOnly: true }),
                numberField('yearly_basic_total', 'Yearly Basic Total', { readOnly: true }),
                numberField('daily_rate_total', 'Daily Rate Total', { readOnly: true }),
                numberField('hourly_rate_total', 'Hourly Rate Total', { readOnly: true }),
                numberField('minute_rate_total', 'Minute Rate Total', { readOnly: true }),
                numberField('gross_pay_total', 'Gross Pay', { readOnly: true }),
                numberField('benefits_total', 'Benefits', { readOnly: true }),
                numberField('allowances_total', 'Allowances', { readOnly: true }),
                numberField('deductions_total', 'Deductions', { readOnly: true }),
                numberField('night_differential_total', 'Night Differential', { readOnly: true }),
                numberField('holiday_pay_total', 'Holiday Pay', { readOnly: true }),
                textField('department', 'Department / Coverage'),
                selectField('funding_bank_account_id', 'Funding Bank Account', { source: 'bank_account' }),
                selectField('payroll_expense_coa_id', 'Payroll Expense Account', { source: 'chart_account' }),
                textareaField('supporting_payroll_summary', 'Supporting Payroll Summary'),
                textareaField('employee_payroll_breakdown', 'Employee Payroll Breakdown', { readOnly: true }),
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
                selectField('requester_employee_id', 'Employee List', { source: 'employee', placeholder: 'Select employee profile' }),
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
            summaryKeys: ['item_classification', 'asset_code', 'current_quantity', 'available_quantity', 'acquisition_cost'],
            fields: [
                selectField('linked_po_id', 'Linked PO', { source: 'po' }),
                selectField('linked_dv_id', 'Linked DV', { source: 'dv' }),
                selectField('item_classification', 'Item Classification', {
                    required: true,
                    help: 'Choose Fixed Asset for depreciable items or Consumable Inventory for office supplies and other stock items.',
                    options: [
                        { value: 'Fixed Asset', label: 'Fixed Asset' },
                        { value: 'Consumable Inventory', label: 'Consumable Inventory' },
                    ],
                }),
                textField('asset_code', 'Asset Code', { required: true }),
                textField('item_name', 'Item Name'),
                textField('item_code', 'Item Code'),
                textField('sku', 'SKU'),
                textField('barcode', 'Barcode'),
                textField('qr_code', 'QR Code'),
                textareaField('asset_description', 'Asset Description'),
                textField('asset_category', 'Category'),
                textField('serial_number', 'Serial Number'),
                textField('model', 'Model'),
                selectField('supplier_id', 'Supplier', { source: 'supplier' }),
                textField('goods_receiving_reference', 'Goods Receiving Reference', { help: 'Receiving report, delivery receipt, or goods acceptance reference.' }),
                numberField('ordered_quantity', 'Ordered Quantity'),
                numberField('delivered_quantity', 'Delivered Quantity'),
                numberField('accepted_quantity', 'Accepted Quantity'),
                numberField('rejected_quantity', 'Rejected Quantity'),
                textField('unit_of_measure', 'Unit of Measure'),
                numberField('beginning_quantity', 'Beginning Quantity'),
                numberField('current_quantity', 'Quantity on Hand'),
                numberField('reserved_quantity', 'Reserved Quantity'),
                numberField('available_quantity', 'Available Stock', { readOnly: true }),
                numberField('reorder_level', 'Reorder Level'),
                numberField('minimum_stock_level', 'Minimum Stock Level'),
                numberField('maximum_stock_level', 'Maximum Stock Level'),
                numberField('safety_stock_level', 'Safety Stock Level'),
                numberField('unit_cost', 'Unit Cost'),
                numberField('total_cost', 'Total Cost', { readOnly: true }),
                numberField('average_cost', 'Average Cost'),
                numberField('last_purchase_cost', 'Last Purchase Cost'),
                numberField('acquisition_cost', 'Acquisition Cost'),
                dateField('acquisition_date', 'Acquisition Date'),
                selectField('asset_coa_id', 'Asset Account from Chart of Accounts', { source: 'chart_account' }),
                textField('location', 'Location'),
                textField('department', 'Department'),
                selectField('custodian', 'Custodian', { source: 'employee' }),
                numberField('useful_life', 'Useful Life (Years)'),
                numberField('residual_value', 'Residual Value'),
                numberField('depreciable_amount', 'Depreciable Amount', { readOnly: true }),
                numberField('annual_depreciation', 'Annual Depreciation', { readOnly: true }),
                numberField('monthly_depreciation', 'Monthly Depreciation', { readOnly: true }),
                numberField('accumulated_depreciation', 'Accumulated Depreciation', { readOnly: true }),
                numberField('net_book_value', 'Net Book Value', { readOnly: true }),
                textareaField('movement_history_note', 'Inventory / Asset Movement Note'),
                textareaField('remarks', 'Remarks'),
            ],
        },
    };

    const moduleKeys = Object.keys(bootstrap.moduleLabels || {})
        .filter((key) => financeModules[key]);
    const canManageFinanceSettings = Boolean(bootstrap.canManageFinanceSettings);
    let financeRecords = Array.isArray(bootstrap.records) ? bootstrap.records.slice() : [];
    let financeSourceRecords = Array.isArray(bootstrap.sourceRecords) ? bootstrap.sourceRecords.slice() : [];
    let financeLookupOptions = bootstrap.lookupOptions || {};
    let financeDropdownOptions = normalizeDropdownSettings(bootstrap.financeDropdownOptions || {});
    let financeAttachmentTypes = normalizeAttachmentTypeSettings(bootstrap.financeAttachmentTypes || []);
    let financeLabelOverrides = normalizeLabelOverrides(bootstrap.financeLabelOverrides || {});
    const financeNoteVisibilityOptions = Array.isArray(bootstrap.financeNoteVisibilityOptions)
        ? bootstrap.financeNoteVisibilityOptions.slice()
        : [
            { value: 'all', label: 'All Finance Viewers' },
            { value: 'approvers', label: 'All Approvers' },
            { value: 'president', label: 'President Only' },
            { value: 'treasurer', label: 'Treasurer Only' },
            { value: 'president_treasurer', label: 'President + Treasurer' },
            { value: 'admins', label: 'Admins Only' },
        ];
    const officialApproverOptions = Array.isArray(bootstrap.officialApproverOptions) ? bootstrap.officialApproverOptions.slice() : [];
    const defaultApprovalSteps = Array.isArray(bootstrap.defaultApprovalSteps) ? bootstrap.defaultApprovalSteps.slice() : [];
    const requestTypeModules = new Set(Array.isArray(bootstrap.requestTypeModules) ? bootstrap.requestTypeModules : []);
    const currentUserEmployeeId = Number(bootstrap.currentUserEmployeeId || 0) || 0;
    let currentModuleKey = moduleKeys.includes(bootstrap.currentModule)
        ? bootstrap.currentModule
        : (moduleKeys[0] || 'supplier');
    let currentWorkflowFilter = workflowFilters.includes(bootstrap.currentWorkflowFilter) ? bootstrap.currentWorkflowFilter : 'all';
    let currentPreviewRecord = null;
    let currentPreviewTab = 'details';
    let currentPreviewAttachmentUrl = '';
    let currentPreviewAttachmentToken = 0;
    let currentPreviewAttachmentObjectUrl = '';
    let currentPreviewPdfObjectUrl = '';
    let currentPreviewPdfGeneration = 0;
    let currentPreviewRefreshTimer = null;
    let currentPreviewRefreshSignature = '';
    let currentEditRecordId = null;
    let financeFormLockedReadOnly = false;
    let activeLookupSelector = null;
    let activeBankAccountLookupQuery = '';
    let financeDraftContext = null;
    let activeDropdownSettingsModuleKey = currentModuleKey;
    const attachmentSettingsModuleKey = '__attachment_types__';

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

    function formatBytes(value) {
        const bytes = Number(value);
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }

        if (bytes < 1024) {
            return `${Math.round(bytes)} B`;
        }

        const units = ['KB', 'MB', 'GB', 'TB'];
        let size = bytes / 1024;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex += 1;
        }

        return `${size.toFixed(size >= 10 ? 0 : 1)} ${units[unitIndex]}`;
    }

    function blank(value) {
        if (value === null || value === undefined) return true;
        if (Array.isArray(value)) return value.length === 0;
        return String(value).trim() === '';
    }

    function numericAmount(value) {
        const numeric = Number(String(value ?? '').replace(/,/g, ''));
        return Number.isFinite(numeric) ? numeric : 0;
    }

    function getCashAdvanceDraftValue(values = {}, name, fallback = '') {
        return values[`data[${name}]`] ?? values[name] ?? fallback;
    }

    function normalizeCashAdvanceReleaseSchedule(value, releaseCount = 1) {
        const normalized = String(value || '').trim().toLowerCase();

        if (normalized === 'full release' || normalized === 'full') {
            return 'Full Release';
        }

        if (
            normalized === 'staggered release'
            || normalized === 'staggered'
            || normalized.includes('stagger')
            || normalized.includes('schedule')
            || normalized.includes('installment')
            || normalized.includes('partial')
        ) {
            return 'Staggered Release';
        }

        return (parseInt(releaseCount, 10) || 1) > 1 ? 'Staggered Release' : 'Full Release';
    }

    function normalizeCashAdvancePaymentEntries(entries = []) {
        const rows = Array.isArray(entries)
            ? entries
            : (entries && typeof entries === 'object' ? Object.values(entries) : []);

        return rows
            .map((entry, index) => {
                const row = entry && typeof entry === 'object' ? entry : {};
                const releaseNo = Math.max(parseInt(row.release_no ?? row.releaseNo ?? index + 1, 10) || index + 1, 1);
                const paymentAmount = numericAmount(row.payment_amount ?? row.paymentAmount ?? row.amount ?? 0);
                const paymentDate = String(row.payment_date ?? row.paymentDate ?? '').trim();
                const paymentRemarks = String(row.payment_remarks ?? row.paymentRemarks ?? row.remarks ?? '').trim();

                return {
                    release_no: releaseNo,
                    payment_date: paymentDate,
                    payment_amount: paymentAmount.toFixed(2),
                    payment_remarks: paymentRemarks,
                };
            })
            .filter((entry) => entry.payment_date || numericAmount(entry.payment_amount) > 0 || entry.payment_remarks);
    }

    function collectCashAdvancePaymentEntriesFromForm(form = $('financeForm')) {
        if (!form) return [];

        return normalizeCashAdvancePaymentEntries(Array.from(form.querySelectorAll('[data-ca-payment-entry-row]')).map((row) => ({
            release_no: row.querySelector('[data-ca-payment-entry-field="release_no"]')?.value || '',
            payment_date: row.querySelector('[data-ca-payment-entry-field="payment_date"]')?.value || '',
            payment_amount: row.querySelector('[data-ca-payment-entry-field="payment_amount"]')?.value || '',
            payment_remarks: row.querySelector('[data-ca-payment-entry-field="payment_remarks"]')?.value || '',
        })));
    }

    function getCurrentCashAdvanceDraftValues() {
        const form = $('financeForm');
        const values = {};
        if (!form) return values;

        new FormData(form).forEach((value, key) => {
            const match = String(key).match(/^data\[([^\]]+)\]$/);
            if (!match) return;

            if (Object.prototype.hasOwnProperty.call(values, match[1])) {
                const current = values[match[1]];
                values[match[1]] = Array.isArray(current) ? [...current, value] : [current, value];
            } else {
                values[match[1]] = value;
            }
            values[`data[${match[1]}]`] = values[match[1]];
        });

        values.ca_payment_entries = collectCashAdvancePaymentEntriesFromForm(form);
        return values;
    }

    function buildCashAdvancePaymentState(values = {}) {
        const amount = numericAmount(getCashAdvanceDraftValue(values, 'amount_requested', values.amount ?? 0));
        const releaseCount = Math.max(parseInt(getCashAdvanceDraftValue(values, 'release_count', 1), 10) || 1, 1);
        const amountPerRelease = numericAmount(getCashAdvanceDraftValue(values, 'amount_per_release', 0)) || (amount / releaseCount);
        const baseEntries = normalizeCashAdvancePaymentEntries(values.ca_payment_entries ?? values['data[ca_payment_entries]'] ?? []);
        const entriesByRelease = baseEntries.reduce((map, entry) => {
            const key = String(entry.release_no);
            map[key] = map[key] || [];
            map[key].push(entry);
            return map;
        }, {});

        const rows = Array.from({ length: releaseCount }).map((_, index) => {
            const releaseNo = index + 1;
            const releaseEntries = entriesByRelease[String(releaseNo)] || [];
            const paidAmount = releaseEntries.reduce((sum, entry) => sum + numericAmount(entry.payment_amount), 0);
            const latestPayment = releaseEntries[releaseEntries.length - 1] || {};
            const scheduledAmount = releaseNo === releaseCount
                ? Math.max(amount - (amountPerRelease * (releaseCount - 1)), 0)
                : amountPerRelease;
            const status = paidAmount >= scheduledAmount && scheduledAmount > 0
                ? 'Paid'
                : (paidAmount > 0 ? 'Partial' : 'Pending');

            return {
                no: releaseNo,
                scheduled_date: releaseNo === 1 ? String(getCashAdvanceDraftValue(values, 'cash_release_date', '') || '') : '',
                amount_value: scheduledAmount,
                paid_amount_value: paidAmount,
                remaining_amount_value: Math.max(scheduledAmount - paidAmount, 0),
                payment_date: latestPayment.payment_date || '',
                payment_remarks: releaseEntries.map((entry) => entry.payment_remarks).filter(Boolean).join(' | '),
                status,
            };
        });

        const totalPaid = baseEntries.reduce((sum, entry) => sum + numericAmount(entry.payment_amount), 0);
        const remainingBalance = Math.max(amount - totalPaid, 0);
        const paidCount = rows.filter((row) => row.status === 'Paid').length;
        const remainingCount = Math.max(rows.length - paidCount, 0);
        const nextPaymentRow = rows.find((row) => row.status !== 'Paid') || null;

        return {
            amount,
            releaseCount,
            amountPerRelease,
            entries: baseEntries,
            rows,
            totalPaid,
            remainingBalance,
            paidCount,
            remainingCount,
            nextPaymentRow,
            status: remainingBalance <= 0 && amount > 0 ? 'Fully Released' : (totalPaid > 0 ? 'Partially Released' : 'Pending Release'),
        };
    }

    function renderCashAdvancePaymentEntryInputs(entries = []) {
        return normalizeCashAdvancePaymentEntries(entries).map((entry, index) => `
            <span data-ca-payment-entry-row>
                <input type="hidden" name="data[ca_payment_entries][${index}][release_no]" data-ca-payment-entry-field="release_no" value="${escapeHtml(entry.release_no)}">
                <input type="hidden" name="data[ca_payment_entries][${index}][payment_date]" data-ca-payment-entry-field="payment_date" value="${escapeHtml(entry.payment_date)}">
                <input type="hidden" name="data[ca_payment_entries][${index}][payment_amount]" data-ca-payment-entry-field="payment_amount" value="${escapeHtml(entry.payment_amount)}">
                <input type="hidden" name="data[ca_payment_entries][${index}][payment_remarks]" data-ca-payment-entry-field="payment_remarks" value="${escapeHtml(entry.payment_remarks)}">
            </span>
        `).join('');
    }

    function renderCashAdvancePaymentSummaryPanel(values = {}, { editable = false, compact = false } = {}) {
        const state = buildCashAdvancePaymentState(values);
        const statusClass = state.status === 'Fully Released'
            ? 'bg-green-100 text-green-800'
            : (state.status === 'Partially Released' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700');
        const metricGridClass = compact
            ? 'mt-4 space-y-2'
            : 'mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3';
        const metricCardClass = compact
            ? 'flex items-center justify-between gap-3 rounded-lg border border-white/80 bg-white px-3 py-2'
            : 'rounded-lg border border-white/80 bg-white px-3 py-2';
        const metricLabelClass = compact
            ? 'text-[11px] font-medium text-gray-500'
            : 'text-[11px] uppercase tracking-[0.18em] text-gray-500';
        const metricValueClass = compact
            ? 'text-sm font-semibold text-gray-900 text-right'
            : 'mt-1 text-sm font-semibold text-gray-900';

        return `
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold text-gray-900">Cash Advance Payment Summary</h4>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${statusClass}">${escapeHtml(state.status)}</span>
                </div>
                <div class="${metricGridClass}">
                    ${[
                        ['Per Release', formatCurrency(state.amountPerRelease)],
                        ['Total Cash Advance', formatCurrency(state.amount)],
                        ['Released / Paid', formatCurrency(state.totalPaid)],
                        ['Remaining for Release', formatCurrency(state.remainingBalance)],
                        ['Releases Paid', state.paidCount],
                        ['Releases Remaining', state.remainingCount],
                    ].map(([label, value]) => `
                        <div class="${metricCardClass}">
                            <p class="${metricLabelClass}">${escapeHtml(label)}</p>
                            <p class="${metricValueClass}">${escapeHtml(value)}</p>
                        </div>
                    `).join('')}
                </div>
                ${editable ? renderCashAdvancePaymentEntryInputs(state.entries) : ''}
            </div>
        `;
    }

    function renderCashAdvancePaymentTrackerPanel(values = {}, { compact = false } = {}) {
        const state = buildCashAdvancePaymentState(values);
        const containerClass = compact
            ? 'rounded-xl border border-gray-200 bg-white p-3'
            : 'rounded-xl border border-gray-200 bg-white p-4';
        const rowsWrapperClass = compact
            ? 'mt-3 space-y-2'
            : 'mt-4 space-y-2';
        const rowClass = compact
            ? 'rounded-lg border border-gray-100 bg-gray-50 px-3 py-2'
            : 'rounded-lg border border-gray-100 bg-gray-50 px-3 py-3';
        const rowLayoutClass = compact
            ? 'space-y-2'
            : 'flex items-start justify-between gap-3';
        const rowAmountClass = compact
            ? 'flex items-center justify-between gap-3 border-t border-gray-100 pt-2'
            : 'shrink-0 text-right';
        const statusBadgeSpacingClass = compact
            ? 'inline-flex rounded-full px-2 py-1 text-xs font-semibold'
            : 'mt-2 inline-flex rounded-full px-2 py-1 text-xs font-semibold';

        return `
            <div class="${containerClass}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-sm font-semibold text-gray-900">Cash Advance Payment Tracker</h4>
                    ${compact ? `<span class="text-xs font-medium text-gray-500">${escapeHtml(state.paidCount)} paid / ${escapeHtml(state.remainingCount)} remaining</span>` : ''}
                </div>
                <div class="${rowsWrapperClass}">
                    ${state.rows.length ? state.rows.map((row) => {
                        const badgeClass = row.status === 'Paid'
                            ? 'bg-green-100 text-green-800'
                            : (row.status === 'Partial' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800');

                        return `
                            <div class="${rowClass}">
                                <div class="${rowLayoutClass}">
                                    <div class="min-w-0">
                                        <p class="text-xs uppercase tracking-[0.18em] text-gray-500">Release ${escapeHtml(row.no)}</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900">Scheduled ${escapeHtml(row.scheduled_date || '-')}</p>
                                        <p class="mt-1 text-xs text-gray-500">Payment Date: ${escapeHtml(row.payment_date || '-')}</p>
                                        ${row.payment_remarks ? `<p class="mt-1 text-xs text-gray-500 break-words">${escapeHtml(row.payment_remarks)}</p>` : ''}
                                    </div>
                                    <div class="${rowAmountClass}">
                                        <p class="text-sm font-semibold text-gray-900">${escapeHtml(formatCurrency(row.amount_value))}</p>
                                        ${row.paid_amount_value > 0 && row.status !== 'Paid' ? `<p class="mt-1 text-xs text-gray-500">Paid ${escapeHtml(formatCurrency(row.paid_amount_value))}</p>` : ''}
                                        <span class="${statusBadgeSpacingClass} ${badgeClass}">${escapeHtml(row.status)}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('') : '<p class="text-sm text-gray-500">No release schedule saved yet.</p>'}
                </div>
            </div>
        `;
    }

    function renderCashAdvanceRecordPaymentPanel(values = {}) {
        const state = buildCashAdvancePaymentState(values);
        const next = state.nextPaymentRow;

        return `
            <div class="rounded-xl border ${next ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-gray-50'} p-4">
                <h4 class="text-sm font-semibold text-gray-900">Record Cash Advance Payment</h4>
                ${next ? `
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-xs text-gray-600">Release</label>
                            <input type="text" value="Release ${escapeHtml(next.no)}" class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900" readonly>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Scheduled Date</label>
                            <input type="text" value="${escapeHtml(next.scheduled_date || '-')}" class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900" readonly>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Amount</label>
                            <input type="text" value="${escapeHtml(formatCurrency(next.remaining_amount_value || next.amount_value))}" class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900" readonly>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Payment Date</label>
                            <input type="date" data-ca-payment-date value="${escapeHtml(todayDateValue())}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-600">Payment Option</label>
                            <select data-ca-payment-scope class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900">
                                <option value="next">Pay next release only</option>
                                <option value="all_remaining">Pay all remaining releases</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-600">Payment Remarks</label>
                            <textarea data-ca-payment-remarks rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900" placeholder="Optional note for this cash advance payment."></textarea>
                        </div>
                    </div>
                    <button type="button" onclick="window.financeModule.recordCashAdvancePayment()" class="mt-4 w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        Record Payment
                    </button>
                    <p class="mt-2 text-xs text-emerald-700">Save the cash advance record after recording payments to keep this tracker.</p>
                ` : '<p class="mt-3 text-sm text-gray-600">All scheduled cash advance releases have already been recorded.</p>'}
            </div>
        `;
    }

    function renderCashAdvancePaymentTracker(values = {}) {
        return `
            <div data-ca-payment-tracker class="md:col-span-2 space-y-4">
                ${renderCashAdvancePaymentSummaryPanel(values, { editable: true })}
                ${renderCashAdvancePaymentTrackerPanel(values)}
                ${renderCashAdvanceRecordPaymentPanel(values)}
            </div>
        `;
    }

    function refreshCashAdvancePaymentTracker(nextValues = null) {
        const form = $('financeForm');
        const section = form?.querySelector('[data-ca-payment-tracker]');
        if (!form || !section) return;

        const values = nextValues || getCurrentCashAdvanceDraftValues();
        financeFormValues = { ...financeFormValues, ...values, ca_payment_entries: values.ca_payment_entries };
        section.outerHTML = renderCashAdvancePaymentTracker(values);
    }

    function recordCashAdvancePayment() {
        const form = $('financeForm');
        if (!form || currentModuleKey !== 'ca') return;

        const values = getCurrentCashAdvanceDraftValues();
        const state = buildCashAdvancePaymentState(values);
        if (!state.nextPaymentRow) {
            showFinanceToast('All scheduled cash advance releases have already been recorded.', 'info');
            return;
        }

        const paymentDate = form.querySelector('[data-ca-payment-date]')?.value || todayDateValue();
        const paymentScope = form.querySelector('[data-ca-payment-scope]')?.value || 'next';
        const paymentRemarks = String(form.querySelector('[data-ca-payment-remarks]')?.value || '').trim();
        const targets = paymentScope === 'all_remaining'
            ? state.rows.filter((row) => row.status !== 'Paid')
            : [state.nextPaymentRow];

        const newEntries = targets.map((row) => {
            const scheduleNote = `Release ${row.no}${row.scheduled_date ? ` scheduled ${row.scheduled_date}` : ''}`;
            return {
                release_no: row.no,
                payment_date: paymentDate,
                payment_amount: (row.remaining_amount_value || row.amount_value || 0).toFixed(2),
                payment_remarks: paymentRemarks ? `${paymentRemarks} | ${scheduleNote}` : `${scheduleNote} payment recorded from cash advance tracker.`,
            };
        });

        const nextValues = {
            ...values,
            ca_payment_entries: normalizeCashAdvancePaymentEntries([...state.entries, ...newEntries]),
        };
        financeFormValues.ca_payment_entries = nextValues.ca_payment_entries;
        refreshCashAdvancePaymentTracker(nextValues);
        renderDrawerPreview();
        showFinanceToast(paymentScope === 'all_remaining' ? 'All remaining cash advance payments were recorded.' : 'Cash advance payment recorded.', 'success');
    }

    function renderCashAdvancePaymentPreview(recordOrValues = {}) {
        const values = recordOrValues?.data ? recordOrValues.data : recordOrValues;

        return `
            <div class="finance-preview-box">
                <div class="finance-preview-section-title">Cash Advance Payment Tracking</div>
                <div class="finance-preview-inner">
                    ${renderCashAdvancePaymentSummaryPanel(values, { compact: true })}
                    <div class="mt-3">
                        ${renderCashAdvancePaymentTrackerPanel(values, { compact: true })}
                    </div>
                </div>
            </div>
        `;
    }

    function renderCashAdvancePreviewPaymentManager(record) {
        const values = record?.data || {};
        const state = buildCashAdvancePaymentState(values);
        const next = state.nextPaymentRow;

        return `
            <div data-ca-preview-payment-root="${escapeHtml(record.id)}" class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h4 class="text-[15px] font-semibold text-gray-900">Cash Advance Payment Tracking</h4>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700">${escapeHtml(state.status)}</span>
                </div>
                <div class="mt-4 space-y-4">
                    ${renderCashAdvancePaymentSummaryPanel(values, { compact: true })}
                    ${renderCashAdvancePaymentTrackerPanel(values, { compact: true })}
                    <div class="rounded-xl border ${next && record.can_edit ? 'border-emerald-200 bg-white' : 'border-gray-200 bg-gray-50'} p-4">
                        <h5 class="text-sm font-semibold text-gray-900">Record Cash Advance Payment</h5>
                        ${next && record.can_edit ? `
                            <div class="mt-4 grid grid-cols-1 gap-3">
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-600">Release</label>
                                        <input type="text" value="Release ${escapeHtml(next.no)}" class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900" readonly>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Amount</label>
                                        <input type="text" value="${escapeHtml(formatCurrency(next.remaining_amount_value || next.amount_value))}" class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900" readonly>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Payment Date</label>
                                    <input type="date" data-ca-preview-payment-date value="${escapeHtml(todayDateValue())}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Payment Option</label>
                                    <select data-ca-preview-payment-scope class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900">
                                        <option value="next">Pay next release only</option>
                                        <option value="all_remaining">Pay all remaining releases</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Payment Remarks</label>
                                    <textarea data-ca-preview-payment-remarks rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900" placeholder="Optional note for this cash advance payment."></textarea>
                                </div>
                            </div>
                            <button type="button" onclick="window.financeModule.recordCashAdvancePreviewPayment(${Number(record.id)})" class="mt-4 w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Record Payment
                            </button>
                        ` : `<p class="mt-3 text-sm text-gray-600">${next ? 'This record can no longer be edited from preview.' : 'All scheduled cash advance releases have already been recorded.'}</p>`}
                    </div>
                </div>
            </div>
        `;
    }

    function appendFinanceDataToFormData(formData, value, path) {
        if (Array.isArray(value)) {
            value.forEach((item, index) => {
                if (item && typeof item === 'object' && !Array.isArray(item)) {
                    appendFinanceDataToFormData(formData, item, `${path}[${index}]`);
                    return;
                }
                formData.append(`${path}[]`, item ?? '');
            });
            return;
        }

        if (value && typeof value === 'object') {
            Object.entries(value).forEach(([key, item]) => {
                appendFinanceDataToFormData(formData, item, `${path}[${key}]`);
            });
            return;
        }

        formData.set(path, value ?? '');
    }

    async function recordCashAdvancePreviewPayment(recordId) {
        const record = getRecordById(recordId);
        if (!record || record.module_key !== 'ca') return;

        const root = document.querySelector(`[data-ca-preview-payment-root="${cssIdentifier(recordId)}"]`);
        const state = buildCashAdvancePaymentState(record.data || {});
        if (!state.nextPaymentRow) {
            showFinanceToast('All scheduled cash advance releases have already been recorded.', 'info');
            return;
        }

        const paymentDate = root?.querySelector('[data-ca-preview-payment-date]')?.value || todayDateValue();
        const paymentScope = root?.querySelector('[data-ca-preview-payment-scope]')?.value || 'next';
        const paymentRemarks = String(root?.querySelector('[data-ca-preview-payment-remarks]')?.value || '').trim();
        const targets = paymentScope === 'all_remaining'
            ? state.rows.filter((row) => row.status !== 'Paid')
            : [state.nextPaymentRow];
        const nextEntries = targets.map((row) => {
            const scheduleNote = `Release ${row.no}${row.scheduled_date ? ` scheduled ${row.scheduled_date}` : ''}`;
            return {
                release_no: row.no,
                payment_date: paymentDate,
                payment_amount: (row.remaining_amount_value || row.amount_value || 0).toFixed(2),
                payment_remarks: paymentRemarks ? `${paymentRemarks} | ${scheduleNote}` : `${scheduleNote} payment recorded from finance preview.`,
            };
        });
        const nextData = {
            ...(record.data || {}),
            release_schedule: normalizeCashAdvanceReleaseSchedule(record.data?.release_schedule, record.data?.release_count),
            ca_payment_entries: normalizeCashAdvancePaymentEntries([...(record.data?.ca_payment_entries || []), ...nextEntries]),
        };
        const formData = new FormData();
        const token = currentCsrfToken();

        if (token) {
            formData.set('_token', token);
        }
        formData.set('_method', 'PUT');
        formData.set('module_key', record.module_key);
        formData.set('record_number', record.record_number || generateModuleRecordNumber(record.module_key));
        formData.set('record_title', record.record_title || generateDefaultRecordTitle(record.module_key, record));
        formData.set('record_date', record.record_date || todayDateValue());
        formData.set('amount', record.amount || nextData.amount_requested || '');
        formData.set('status', record.status || 'Active');
        formData.set('existing_attachments_json', JSON.stringify(record.attachments || []));
        Object.entries(nextData).forEach(([key, value]) => appendFinanceDataToFormData(formData, value, `data[${key}]`));

        const res = await csrfFetch(`/finance/${record.id}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
            },
            body: formData,
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            const firstErrorKey = data.errors ? Object.keys(data.errors)[0] : null;
            const firstErrorMessage = firstErrorKey && data.errors[firstErrorKey] ? data.errors[firstErrorKey][0] : '';
            showFinanceToast(getFriendlyErrorMessage(firstErrorMessage || data.message || 'Unable to record cash advance payment.'), 'error');
            return;
        }

        upsertFinanceRecord(data.data);
        currentPreviewRecord = data.data;
        renderPreviewTabContent(data.data);
        renderPreviewDocument(data.data);
        renderPreviewActions(data.data);
        showFinanceToast(paymentScope === 'all_remaining' ? 'All remaining cash advance payments were recorded.' : 'Cash advance payment recorded.', 'success');
    }

    function formatDate(value) {
        return value || 'N/A';
    }

    function getModuleConfig(moduleKey) {
        return getResolvedModuleConfig(moduleKey);
    }

    function normalizeOption(option) {
        const value = String(option?.value ?? option?.id ?? option?.label ?? option ?? '').trim();
        const label = String(option?.label ?? option?.value ?? option?.id ?? option ?? '').trim();

        if (!value) return null;

        return {
            value,
            label: label || value,
        };
    }

    function normalizeAttachmentType(option) {
        const label = String(option?.label ?? option?.value ?? option ?? '').trim();
        const value = String(option?.value ?? option?.label ?? option ?? '').trim();

        if (!label && !value) {
            return null;
        }

        const status = String(option?.status ?? '').trim().toLowerCase();
        const visibility = String(option?.visibility ?? '').trim().toLowerCase();
        const active = Object.prototype.hasOwnProperty.call(option || {}, 'active')
            ? Boolean(option?.active)
            : !['inactive', 'disabled', 'off'].includes(status);
        const hidden = Object.prototype.hasOwnProperty.call(option || {}, 'hidden')
            ? Boolean(option?.hidden)
            : ['hidden', 'hide', 'off'].includes(visibility);

        return {
            label: label || value,
            value: value || label,
            active,
            hidden,
        };
    }

    function normalizeAttachmentTypeSettings(settings = []) {
        return Array.isArray(settings)
            ? settings.map(normalizeAttachmentType).filter(Boolean)
            : [];
    }

    function normalizeDropdownSettings(settings = {}) {
        const normalized = {};

        Object.entries(settings || {}).forEach(([moduleKey, fields]) => {
            if (!financeModules[moduleKey] || !fields || typeof fields !== 'object') return;

            Object.entries(fields).forEach(([fieldName, options]) => {
                if (!Array.isArray(options)) return;

                normalized[moduleKey] = normalized[moduleKey] || {};
                normalized[moduleKey][fieldName] = options
                    .map(normalizeOption)
                    .filter(Boolean);
            });
        });

        return normalized;
    }

    function normalizeLabelOverrides(settings = {}) {
        const normalized = {};

        Object.entries(settings || {}).forEach(([moduleKey, config]) => {
            if (!financeModules[moduleKey] || !config || typeof config !== 'object') return;

            const moduleOverrides = {};
            const recordNumberLabel = String(config.record_number_label ?? '').trim();
            const recordTitleLabel = String(config.record_title_label ?? '').trim();
            const recordDateLabel = String(config.record_date_label ?? '').trim();
            const fieldLabels = config.field_labels && typeof config.field_labels === 'object' ? config.field_labels : {};

            if (recordNumberLabel) moduleOverrides.record_number_label = recordNumberLabel;
            if (recordTitleLabel) moduleOverrides.record_title_label = recordTitleLabel;
            if (recordDateLabel) moduleOverrides.record_date_label = recordDateLabel;

            const normalizedFieldLabels = {};
            Object.entries(fieldLabels).forEach(([fieldName, fieldLabel]) => {
                const cleanFieldName = String(fieldName || '').trim();
                const cleanFieldLabel = String(fieldLabel || '').trim();
                if (!cleanFieldName || !cleanFieldLabel) return;
                normalizedFieldLabels[cleanFieldName] = cleanFieldLabel;
            });

            if (Object.keys(normalizedFieldLabels).length) {
                moduleOverrides.field_labels = normalizedFieldLabels;
            }

            if (Object.keys(moduleOverrides).length) {
                normalized[moduleKey] = moduleOverrides;
            }
        });

        return normalized;
    }

    function isAttachmentSettingsModule(moduleKey) {
        return moduleKey === attachmentSettingsModuleKey;
    }

    function getModuleLabelOverrides(moduleKey) {
        return financeLabelOverrides[moduleKey] || {};
    }

    function getResolvedModuleConfig(moduleKey) {
        const baseConfig = financeModules[moduleKey] || financeModules.supplier;
        const overrides = getModuleLabelOverrides(moduleKey);
        const fieldLabels = overrides.field_labels || {};

        return {
            ...baseConfig,
            recordNumberLabel: overrides.record_number_label || baseConfig.recordNumberLabel,
            recordTitleLabel: overrides.record_title_label || baseConfig.recordTitleLabel,
            recordDateLabel: overrides.record_date_label || baseConfig.recordDateLabel,
            fields: (baseConfig.fields || []).map((field) => ({
                ...field,
                label: fieldLabels[field.name] || field.label,
            })),
        };
    }

    function getModuleLabelEntries(moduleKey) {
        const baseConfig = financeModules[moduleKey] || financeModules.supplier;
        const overrides = getModuleLabelOverrides(moduleKey);
        const fieldLabels = overrides.field_labels || {};
        const entries = [
            {
                key: 'record_number_label',
                label: 'Record Number',
                current: String(baseConfig.recordNumberLabel || '').trim(),
                override: String(overrides.record_number_label || '').trim(),
                helper: 'Changes the label shown for the record number across forms, previews, and PDFs.',
            },
            {
                key: 'record_title_label',
                label: 'Record Title',
                current: String(baseConfig.recordTitleLabel || '').trim(),
                override: String(overrides.record_title_label || '').trim(),
                helper: 'Changes the label shown for the main title or payee name on this module.',
            },
            {
                key: 'record_date_label',
                label: 'Record Date',
                current: String(baseConfig.recordDateLabel || '').trim(),
                override: String(overrides.record_date_label || '').trim(),
                helper: 'Changes the label shown for the date field in forms, previews, and PDFs.',
            },
        ];

        (baseConfig.fields || []).forEach((field) => {
            if (!field || !field.name) return;
            entries.push({
                key: field.name,
                label: field.label || field.name,
                current: String(field.label || '').trim(),
                override: String(fieldLabels[field.name] || '').trim(),
                helper: field.help || 'Changes the label shown for this field without changing stored record values.',
                isField: true,
            });
        });

        return entries;
    }

    function buildLabelOverridesFromInputs(moduleKey) {
        const overrides = {};
        const fieldLabels = {};
        const entries = getModuleLabelEntries(moduleKey);

        entries.forEach((entry) => {
            const input = document.querySelector(`[data-finance-label-input][data-module-key="${cssIdentifier(moduleKey)}"][data-label-key="${cssIdentifier(entry.key)}"]`);
            if (!input) return;

            const value = String(input.value || '').trim();
            if (!value) return;

            if (entry.key === 'record_number_label' || entry.key === 'record_title_label' || entry.key === 'record_date_label') {
                overrides[entry.key] = value;
                return;
            }

            fieldLabels[entry.key] = value;
        });

        if (Object.keys(fieldLabels).length) {
            overrides.field_labels = fieldLabels;
        }

        return overrides;
    }

    function countLabelOverrides(moduleKey) {
        const overrides = getModuleLabelOverrides(moduleKey);
        const fieldLabelCount = Object.keys(overrides.field_labels || {}).length;

        return [
            overrides.record_number_label,
            overrides.record_title_label,
            overrides.record_date_label,
        ].filter(Boolean).length + fieldLabelCount;
    }

    function isEditableDropdownField(field) {
        return field
            && field.type === 'select'
            && !field.source
            && !field.sourceMap
            && !field.readOnly;
    }

    function getEditableDropdownFields(moduleKey) {
        const seen = new Set();

        return (getModuleConfig(moduleKey).fields || []).filter((field) => {
            if (!isEditableDropdownField(field) || seen.has(field.name)) {
                return false;
            }

            seen.add(field.name);
            return true;
        });
    }

    function getCustomFieldOptions(moduleKey, fieldName) {
        const moduleOptions = financeDropdownOptions[moduleKey] || {};

        if (!Object.prototype.hasOwnProperty.call(moduleOptions, fieldName)) {
            return null;
        }

        return Array.isArray(moduleOptions[fieldName]) ? moduleOptions[fieldName] : [];
    }

    function formatOptionsForTextarea(moduleKey, field) {
        const options = getCustomFieldOptions(moduleKey, field.name) ?? (field.options || []);

        return options
            .map(normalizeOption)
            .filter(Boolean)
            .map((option) => option.label === option.value ? option.value : `${option.label} | ${option.value}`)
            .join('\n');
    }

    function formatAttachmentTypesForTextarea() {
        return financeAttachmentTypes
            .map((option) => {
                const parts = [option.label || option.value || ''];
                if ((option.value || '') !== (option.label || '')) {
                    parts.push(option.value || option.label || '');
                }
                if (option.active === false) {
                    parts.push('inactive');
                }
                if (option.hidden) {
                    parts.push('hidden');
                }
                return parts.filter(Boolean).join(' | ');
            })
            .join('\n');
    }

    function parseDropdownTextareaOptions(value) {
        const options = [];
        const seen = new Set();

        String(value || '').split(/\r?\n/).forEach((line) => {
            const trimmed = line.trim();
            if (!trimmed) return;

            const separatorIndex = trimmed.indexOf('|');
            const label = separatorIndex >= 0 ? trimmed.slice(0, separatorIndex).trim() : trimmed;
            const optionValue = separatorIndex >= 0 ? trimmed.slice(separatorIndex + 1).trim() : trimmed;
            const normalized = normalizeOption({ value: optionValue, label: label || optionValue });

            if (!normalized || seen.has(normalized.value)) return;

            seen.add(normalized.value);
            options.push(normalized);
        });

        return options;
    }

    function parseAttachmentTypesTextarea(value) {
        const options = [];
        const seen = new Set();

        String(value || '').split(/\r?\n/).forEach((line) => {
            const trimmed = line.trim();
            if (!trimmed) return;

            const parts = trimmed.split('|').map((part) => part.trim()).filter(Boolean);
            const label = parts[0] || '';
            const optionValue = parts[1] || parts[0] || '';
            const status = String(parts[2] || '').trim().toLowerCase();
            const visibility = String(parts[3] || '').trim().toLowerCase();
            const normalized = normalizeAttachmentType({
                label,
                value: optionValue,
                active: !['inactive', 'disabled', 'off'].includes(status),
                hidden: ['hidden', 'hide', 'off'].includes(visibility),
            });

            if (!normalized) return;

            const dedupeKey = normalized.value.toLowerCase();
            if (seen.has(dedupeKey)) return;

            seen.add(dedupeKey);
            options.push(normalized);
        });

        return options;
    }

    function renderDropdownSettingsModal() {
        const modal = $('financeDropdownSettingsModal');
        const moduleList = $('financeDropdownSettingsModules');
        const title = $('financeDropdownSettingsTitle');
        const fieldsHost = $('financeDropdownSettingsFields');

        if (!modal || !moduleList || !title || !fieldsHost) return;

        if (!financeModules[activeDropdownSettingsModuleKey] && !isAttachmentSettingsModule(activeDropdownSettingsModuleKey)) {
            activeDropdownSettingsModuleKey = currentModuleKey;
        }

        moduleList.innerHTML = `
            <button
                type="button"
                onclick="window.financeModule.changeDropdownSettingsModule('${escapeHtml(attachmentSettingsModuleKey)}')"
                class="mb-2 w-full rounded-lg px-3 py-2 text-left text-sm transition ${isAttachmentSettingsModule(activeDropdownSettingsModuleKey) ? 'bg-white text-blue-700 shadow-sm border border-blue-100 font-semibold' : 'text-gray-600 hover:bg-white'}"
            >
                <span class="block truncate">Attachment Types</span>
                <span class="mt-0.5 block text-[11px] text-gray-400">${financeAttachmentTypes.length} configured type${financeAttachmentTypes.length === 1 ? '' : 's'}</span>
            </button>
            ${moduleKeys.map((moduleKey) => {
            const active = moduleKey === activeDropdownSettingsModuleKey;
            const fieldsCount = getEditableDropdownFields(moduleKey).length;
            const labelCount = countLabelOverrides(moduleKey);

            return `
                <button
                    type="button"
                    onclick="window.financeModule.changeDropdownSettingsModule('${escapeHtml(moduleKey)}')"
                    class="w-full rounded-lg px-3 py-2 text-left text-sm transition ${active ? 'bg-white text-blue-700 shadow-sm border border-blue-100 font-semibold' : 'text-gray-600 hover:bg-white'}"
                >
                    <span class="block truncate">${escapeHtml(getModuleConfig(moduleKey).label)}</span>
                    <span class="mt-0.5 block text-[11px] text-gray-400">${labelCount} label override${labelCount === 1 ? '' : 's'} • ${fieldsCount} choice set${fieldsCount === 1 ? '' : 's'}</span>
                </button>
            `;
        }).join('')}
        `;

        if (isAttachmentSettingsModule(activeDropdownSettingsModuleKey)) {
            title.textContent = 'Attachment Types';
            fieldsHost.innerHTML = `
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900" for="financeAttachmentTypesTextarea">Attachment Types</label>
                            <p class="mt-1 text-xs text-gray-500">One type per line. Use <span class="font-mono">Label | value | inactive | hidden</span>. Leave the last flags out to keep the type active and visible.</p>
                        </div>
                        <button type="button" onclick="window.financeModule.resetAttachmentTypesSettings()" class="shrink-0 rounded-md border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">Reset</button>
                    </div>
                    <textarea
                        id="financeAttachmentTypesTextarea"
                        data-finance-attachment-setting
                        rows="8"
                        class="mt-3 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm font-mono leading-6 outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                    >${escapeHtml(formatAttachmentTypesForTextarea())}</textarea>
                </div>
            `;
            return;
        }

        const fields = getEditableDropdownFields(activeDropdownSettingsModuleKey);
        title.textContent = `${getModuleConfig(activeDropdownSettingsModuleKey).label} Editor`;
        const labelOverridesCount = countLabelOverrides(activeDropdownSettingsModuleKey);
        const dropdownFieldsCount = fields.length;
        const labelEntries = getModuleLabelEntries(activeDropdownSettingsModuleKey);
        const labelCardHtml = `
            <div id="financeFieldLabelsSection" class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-600">Field Labels</p>
                        <h4 class="text-sm font-semibold text-gray-900">Edit visible labels and save them globally</h4>
                        <p class="text-xs leading-5 text-gray-500">
                            Use this editor when you want the label text shown to users to be clearer or more consistent.
                            The change is saved in the database and updates the finance UI everywhere, but it does not rewrite any existing record values.
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button type="button" onclick="window.financeModule.focusDropdownSettingsSection('dropdown')" class="rounded-full border border-indigo-200 bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">Go to Dropdown Choices</button>
                        <button type="button" onclick="window.financeModule.resetLabelOverrides('${escapeHtml(activeDropdownSettingsModuleKey)}')" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Reset to defaults</button>
                    </div>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">How it works</p>
                        <p class="mt-1 text-xs leading-5 text-slate-700">Change only the words displayed in the UI, previews, and PDFs. Existing records keep their stored values.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">Saved to database</p>
                        <p class="mt-1 text-xs leading-5 text-slate-700">The new label is stored as a finance label override and applied globally for connected modules.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">Current overrides</p>
                        <p class="mt-1 text-xs text-slate-700">${labelOverridesCount} saved</p>
                    </div>
                </div>
                <div class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">Quick tip</p>
                            <p class="mt-1 text-sm text-slate-800">Edit the rows below. Leave a value blank to keep the default label. This only changes the display label, not the saved record data.</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Editable rows below</div>
                    </div>
                </div>
                <div class="mt-4 space-y-3">
                    ${labelEntries.map((entry) => {
                        const inputId = `financeLabelInput_${cssIdentifier(activeDropdownSettingsModuleKey)}_${cssIdentifier(entry.key)}`;
                        const currentLabel = entry.current || 'Untitled';
                        const overrideValue = entry.override || '';
                        const isCustom = Boolean(overrideValue);
                        return `
                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 space-y-1">
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">${escapeHtml(entry.label)}</p>
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            <span class="rounded-full bg-slate-100 px-2 py-1 font-medium text-slate-600">Current: ${escapeHtml(currentLabel)}</span>
                                            ${isCustom ? '<span class="rounded-full bg-emerald-50 px-2 py-1 font-medium text-emerald-700">Saved in database</span>' : '<span class="rounded-full bg-gray-100 px-2 py-1 font-medium text-gray-500">Using default</span>'}
                                        </div>
                                        <p class="text-xs leading-5 text-gray-500">${escapeHtml(entry.helper || 'Change the label without changing stored values.')}</p>
                                    </div>
                                    <button
                                        type="button"
                                        onclick="window.financeModule.resetSingleLabelOverride('${escapeHtml(activeDropdownSettingsModuleKey)}', '${escapeHtml(entry.key)}')"
                                        class="shrink-0 rounded-md border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50"
                                    >
                                        Reset
                                    </button>
                                </div>
                                <div class="mt-4 grid gap-3 md:grid-cols-[1fr_1.3fr]">
                                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">What users currently see</p>
                                        <p class="mt-1 text-sm font-medium text-slate-800">${escapeHtml(currentLabel)}</p>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] uppercase tracking-[0.16em] text-slate-500" for="${escapeHtml(inputId)}">New label to save</label>
                                        <input
                                            id="${escapeHtml(inputId)}"
                                            type="text"
                                            data-finance-label-input
                                            data-module-key="${escapeHtml(activeDropdownSettingsModuleKey)}"
                                            data-label-key="${escapeHtml(entry.key)}"
                                            value="${escapeHtml(overrideValue)}"
                                            placeholder="${escapeHtml(currentLabel)}"
                                            class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                                        >
                                        <p class="mt-2 text-xs text-gray-500">Leave blank to keep the default label. Saving stores only the new label, not the old record values.</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;

        const dropdownHelpHtml = `
            <div id="financeDropdownChoicesSection" class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-5 shadow-sm">
                <p class="text-[11px] uppercase tracking-[0.2em] text-indigo-700">Dropdown Choices</p>
                <h4 class="mt-1 text-sm font-semibold text-gray-900">Edit the options in a dropdown list</h4>
                <p class="mt-2 text-xs leading-5 text-gray-600">
                    One line equals one option. Use <span class="font-mono">Label | value</span> when the saved value should be different from the display text.
                    Add new lines to add choices, rename the label to update what users see, hide or deactivate choices from the attachment settings area, and save when you are done.
                </p>
                <div class="mt-3">
                    <button type="button" onclick="window.financeModule.focusDropdownSettingsSection('labels')" class="rounded-full border border-indigo-200 bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Back to Field Labels</button>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-indigo-100 bg-white p-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-indigo-600">Add</p>
                        <p class="mt-1 text-xs text-gray-700">Add a new line with the new option text.</p>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-white p-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-indigo-600">Rename</p>
                        <p class="mt-1 text-xs text-gray-700">Change the label to update the visible wording.</p>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-white p-3">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-indigo-600">Saved value</p>
                        <p class="mt-1 text-xs text-gray-700">Use <code>Label | value</code> if the stored value must differ.</p>
                    </div>
                </div>
            </div>
        `;

        const fieldCardsHtml = fields.length ? fields.map((field) => `
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900" for="financeDropdownField_${escapeHtml(activeDropdownSettingsModuleKey)}_${escapeHtml(field.name)}">${escapeHtml(field.label)}</label>
                        <p class="mt-1 text-xs text-gray-500">This controls the choices shown for this field across all connected finance screens.</p>
                    </div>
                    <button
                        type="button"
                        onclick="window.financeModule.resetDropdownSettingsField('${escapeHtml(activeDropdownSettingsModuleKey)}', '${escapeHtml(field.name)}')"
                        class="shrink-0 rounded-md border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50"
                    >
                        Reset choices
                    </button>
                </div>
                <textarea
                    id="financeDropdownField_${escapeHtml(activeDropdownSettingsModuleKey)}_${escapeHtml(field.name)}"
                    data-finance-dropdown-setting
                    data-module-key="${escapeHtml(activeDropdownSettingsModuleKey)}"
                    data-field-name="${escapeHtml(field.name)}"
                    rows="6"
                    class="mt-3 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm font-mono leading-6 outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                >${escapeHtml(formatOptionsForTextarea(activeDropdownSettingsModuleKey, field))}</textarea>
            </div>
        `).join('') : `
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center">
                <p class="text-sm font-medium text-gray-700">No editable dropdown choices on this tab.</p>
                <p class="mt-1 text-xs text-gray-500">Use the field label section above to rename the visible text for this module.</p>
            </div>
        `;

        fieldsHost.innerHTML = `${labelCardHtml}${dropdownFieldsCount ? dropdownHelpHtml : ''}<div class="space-y-4">${fieldCardsHtml}</div>`;
    }

    function openDropdownSettings() {
        if (!canManageFinanceSettings) return;

        activeDropdownSettingsModuleKey = currentModuleKey;
        renderDropdownSettingsModal();
        $('financeDropdownSettingsModal')?.classList.remove('hidden');
        $('financeDropdownSettingsModal')?.setAttribute('aria-hidden', 'false');
    }

    function closeDropdownSettings() {
        $('financeDropdownSettingsModal')?.classList.add('hidden');
        $('financeDropdownSettingsModal')?.setAttribute('aria-hidden', 'true');
    }

    function changeDropdownSettingsModule(moduleKey) {
        if (!financeModules[moduleKey] && !isAttachmentSettingsModule(moduleKey)) return;

        activeDropdownSettingsModuleKey = moduleKey;
        renderDropdownSettingsModal();
    }

    function focusDropdownSettingsSection(section) {
        const targetId = section === 'dropdown'
            ? 'financeDropdownChoicesSection'
            : 'financeFieldLabelsSection';
        const element = document.getElementById(targetId);

        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function resetAttachmentTypesSettings() {
        financeAttachmentTypes = normalizeAttachmentTypeSettings([
            { label: 'Supporting Document', value: 'Supporting Document', active: true, hidden: false },
            { label: 'Invoice', value: 'Invoice', active: true, hidden: false },
            { label: 'OR', value: 'OR', active: true, hidden: false },
            { label: 'DR', value: 'DR', active: true, hidden: false },
            { label: 'Contract', value: 'Contract', active: true, hidden: false },
        ]);

        renderDropdownSettingsModal();
    }

    function resetLabelOverrides(moduleKey) {
        financeLabelOverrides = {
            ...financeLabelOverrides,
        };
        delete financeLabelOverrides[moduleKey];

        renderDropdownSettingsModal();
    }

    function resetSingleLabelOverride(moduleKey, labelKey) {
        const input = document.querySelector(`[data-finance-label-input][data-module-key="${cssIdentifier(moduleKey)}"][data-label-key="${cssIdentifier(labelKey)}"]`);

        if (input) {
            input.value = '';
        }

        const normalized = normalizeLabelOverrides(financeLabelOverrides);
        const moduleOverrides = { ...(normalized[moduleKey] || {}) };

        if (labelKey === 'record_number_label' || labelKey === 'record_title_label' || labelKey === 'record_date_label') {
            delete moduleOverrides[labelKey];
        } else {
            const fieldLabels = { ...(moduleOverrides.field_labels || {}) };
            delete fieldLabels[labelKey];
            if (Object.keys(fieldLabels).length) {
                moduleOverrides.field_labels = fieldLabels;
            } else {
                delete moduleOverrides.field_labels;
            }
        }

        if (Object.keys(moduleOverrides).length) {
            normalized[moduleKey] = moduleOverrides;
        } else {
            delete normalized[moduleKey];
        }

        financeLabelOverrides = normalized;
        renderDropdownSettingsModal();
    }

    function resetDropdownSettingsField(moduleKey, fieldName) {
        const field = getEditableDropdownFields(moduleKey).find((item) => item.name === fieldName);
        const textarea = document.querySelector(`[data-finance-dropdown-setting][data-module-key="${cssIdentifier(moduleKey)}"][data-field-name="${cssIdentifier(fieldName)}"]`);

        if (!field || !textarea) return;

        textarea.value = (field.options || [])
            .map(normalizeOption)
            .filter(Boolean)
            .map((option) => option.label === option.value ? option.value : `${option.label} | ${option.value}`)
            .join('\n');
    }

    async function saveDropdownSettings() {
        if (!canManageFinanceSettings) return;

        const payload = normalizeDropdownSettings(financeDropdownOptions);
        const activeModuleOptions = {};

        document.querySelectorAll('[data-finance-dropdown-setting]').forEach((textarea) => {
            const moduleKey = textarea.getAttribute('data-module-key') || '';
            const fieldName = textarea.getAttribute('data-field-name') || '';
            if (moduleKey !== activeDropdownSettingsModuleKey || !fieldName) return;

            activeModuleOptions[fieldName] = parseDropdownTextareaOptions(textarea.value);
        });

        payload[activeDropdownSettingsModuleKey] = activeModuleOptions;
        const attachmentTextarea = document.querySelector('[data-finance-attachment-setting]');
        const attachmentTypes = attachmentTextarea ? parseAttachmentTypesTextarea(attachmentTextarea.value) : financeAttachmentTypes;
        const labelOverrides = buildLabelOverridesFromInputs(activeDropdownSettingsModuleKey);
        const mergedLabelOverrides = {
            ...normalizeLabelOverrides(financeLabelOverrides),
        };

        if (Object.keys(labelOverrides || {}).length) {
            mergedLabelOverrides[activeDropdownSettingsModuleKey] = labelOverrides;
        } else {
            delete mergedLabelOverrides[activeDropdownSettingsModuleKey];
        }

        const res = await csrfFetch('/finance/dropdown-settings', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                options: payload,
                attachment_types: attachmentTypes,
                label_overrides: mergedLabelOverrides,
            }),
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            showFinanceToast(data.message || 'Unable to save finance dropdown settings.', 'error');
            return;
        }

        financeDropdownOptions = normalizeDropdownSettings(data.options || payload);
        financeAttachmentTypes = normalizeAttachmentTypeSettings(data.attachment_types || attachmentTypes);
        financeLabelOverrides = normalizeLabelOverrides(data.label_overrides || mergedLabelOverrides);
        renderDropdownSettingsModal();
        refreshFinanceView();
        showFinanceToast(data.message || 'Finance dropdown settings saved.', 'success');
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
        if (match) {
            return match.label;
        }

        const visibleRecord = financeRecords.find((record) => String(record.id) === String(id) && String(record.module_key) === String(moduleKey));
        if (visibleRecord) {
            return visibleRecord.display_label
                || [visibleRecord.record_number || '', visibleRecord.record_title || ''].filter(Boolean).join(' - ')
                || visibleRecord.record_number
                || visibleRecord.record_title
                || '';
        }

        const sourceRecord = financeSourceRecords.find((record) => String(record.id) === String(id) && String(record.module_key) === String(moduleKey));
        if (sourceRecord) {
            return sourceRecord.display_label
                || [sourceRecord.record_number || '', sourceRecord.record_title || ''].filter(Boolean).join(' - ')
                || sourceRecord.record_number
                || sourceRecord.record_title
                || '';
        }

        return '';
    }

    function getApprovalRoutingRoleLabels(record) {
        const isSupplierSendMode = record?.module_key === 'supplier'
            && String(record?.data?.completion_mode || '').trim() === 'send_to_supplier';

        return isSupplierSendMode
            ? ['Treasurer', 'President']
            : ['President', 'Treasurer'];
    }

    function getApproverRoutingDisplayValue(record, index) {
        const data = record?.data || {};
        const step = Array.isArray(data.approval_steps) ? data.approval_steps[index] || {} : {};
        const roleLabels = getApprovalRoutingRoleLabels(record);
        const role = roleLabels[index] || (index === 0 ? 'President' : 'Treasurer');
        const userId = step.user_id || getFieldValue(record, index === 0 ? 'first_approver_user_id' : 'second_approver_user_id');
        const match = officialApproverOptions.find((option) => String(option.user_id || '') === String(userId || ''));
        const name = match?.user_name || match?.official_name || String(userId || '');

        if (!name) {
            return role;
        }

        return match ? `${name} (${role})` : `${name} (${role})`;
    }

    function getAttachmentSummaryValue(record) {
        const attachments = Array.isArray(record?.attachments) ? record.attachments : [];
        if (!attachments.length) {
            return 'No attachments';
        }
        return `${attachments.length} file${attachments.length === 1 ? '' : 's'}`;
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
            linked_crf_id: 'crf',
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
        if (v === 'on hold') return 'text-amber-700';
        if (v === 'accepted') return 'text-green-700';
        if (v === 'reverted') return 'text-yellow-700';
        if (v === 'archived') return 'text-gray-700';
        if (v === 'delete requested') return 'text-red-700';
        if (v === 'deleted') return 'text-gray-700';
        return 'text-gray-700';
    }

    function approvalBadgeClass(value) {
        const v = (value || '').toLowerCase();
        if (v === 'approved') return 'text-green-700';
        if (v === 'pending') return 'text-yellow-700';
        if (v === 'partially approved') return 'text-blue-700';
        if (v === 'on hold') return 'text-amber-700';
        if (v === 'pending supplier completion') return 'text-sky-700';
        if (v === 'deletion pending') return 'text-red-700';
        if (v === 'deleted') return 'text-gray-700';
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
        if (isPendingSupplierCompletion(record)) {
            return 'Awaiting supplier response';
        }

        return approvalLabel(record.approval_status);
    }

    function dataCompletionMode(record) {
        return record?.data?.completion_mode || '';
    }

    function hasSupplierCompletion(record) {
        return Boolean(record?.supplier_completed_at);
    }

    function isPendingSupplierCompletion(record) {
        if (!record || record.module_key !== 'supplier') {
            return false;
        }

        const mode = dataCompletionMode(record);
        return mode === 'send_to_supplier'
            && !hasSupplierCompletion(record)
            && (
                record.workflow_status === 'Shared'
                || Boolean(record.share_token)
                || Boolean(record.supplier_completion_url)
            );
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
            'On Hold': 'These records are on hold and require a reasoned review action.',
            Accepted: 'These records are approved and active for lookup flows.',
            Reverted: 'These records were reverted and can be corrected then resubmitted.',
            Archived: 'These records are archived.',
            'Delete Requested': 'These records are waiting for admin approval before deletion is finalized.',
        };

        const palette = {
            Uploaded: 'border-blue-200 bg-blue-50 text-blue-700',
            Shared: 'border-sky-200 bg-sky-50 text-sky-700',
            Submitted: 'border-yellow-200 bg-yellow-50 text-yellow-700',
            'On Hold': 'border-amber-200 bg-amber-50 text-amber-700',
            Accepted: 'border-green-200 bg-green-50 text-green-700',
            Reverted: 'border-red-200 bg-red-50 text-red-700',
            Archived: 'border-gray-200 bg-gray-50 text-gray-700',
            'Delete Requested': 'border-red-200 bg-red-50 text-red-700',
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
        if (currentModuleKey === 'pr') {
            $('tableHeadRow').innerHTML = `
                <th class="w-36 p-3 text-left">Number</th>
                <th class="w-44 p-3 text-left">Title</th>
                <th class="w-44 p-3 text-left">Requestor</th>
                <th class="w-28 p-3 text-left">Priority</th>
                <th class="w-32 p-3 text-left">Date Needed</th>
                <th class="w-32 p-3 text-left">Amount</th>
                <th class="w-32 p-3 text-left">Date</th>
                <th class="w-36 p-3 text-left">Workflow</th>
                <th class="w-36 p-3 text-left">Approval</th>
                <th class="w-32 p-3 text-left">Actions</th>
            `;
            return;
        }

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
                    <td colspan="${currentModuleKey === 'pr' ? '10' : '7'}" class="p-10 text-center text-gray-400 italic">No records found</td>
                </tr>
            `;
            return;
        }

        rows.forEach((item) => {
            if (currentModuleKey === 'pr') {
                tableBody.innerHTML += `
                    <tr class="border-t hover:bg-blue-50 cursor-pointer" onclick="window.financeModule.openPreview(${item.id})">
                        <td class="p-3 break-words">${escapeHtml(item.record_number || '')}</td>
                        <td class="p-3 break-words">${escapeHtml(getVisibleRecordTitle(item))}</td>
                        <td class="p-3 break-words">${escapeHtml(item.data?.requestor || item.data?.employee_name || '')}</td>
                        <td class="p-3">${escapeHtml(item.data?.priority || '')}</td>
                        <td class="p-3">${escapeHtml(formatDate(item.data?.needed_date))}</td>
                        <td class="p-3">${escapeHtml(formatCurrency(item.amount || item.data?.grand_total || 0))}</td>
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
                return;
            }

            tableBody.innerHTML += `
                <tr class="border-t hover:bg-blue-50 cursor-pointer" onclick="window.financeModule.openPreview(${item.id})">
                    <td class="p-3 break-words">${escapeHtml(item.record_number || '')}</td>
                    <td class="p-3 break-words">${escapeHtml(getVisibleRecordTitle(item))}</td>
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
            const moduleKey = link.getAttribute('data-finance-module');
            const isAllowed = moduleKeys.includes(moduleKey);
            const isActive = moduleKey === currentModuleKey;
            link.hidden = !isAllowed;
            link.classList.toggle('hidden', !isAllowed);
            link.classList.toggle('bg-blue-50', isActive);
            link.classList.toggle('text-blue-700', isActive);
            link.classList.toggle('border', isActive);
            link.classList.toggle('border-blue-100', isActive);
            link.classList.toggle('font-semibold', isActive);
            link.classList.toggle('text-gray-700', !isActive);
            link.classList.toggle('hover:bg-gray-100', !isActive);
        });
    }

    function getFieldOptions(field, formValues = {}, moduleKey = currentModuleKey) {
        const injectSelectedLookupOption = (options, lookupModuleKey, selectedValue) => {
            const normalizedValue = String(selectedValue || '').trim();
            if (!normalizedValue) {
                return options;
            }

            const exists = options.some((option) => String(option.id ?? option.value ?? '') === normalizedValue);
            if (exists) {
                return options;
            }

            const selectedLabel = getLookupLabel(lookupModuleKey, normalizedValue) || normalizedValue;
            return [{ id: normalizedValue, label: selectedLabel }, ...options];
        };

        if (isEditableDropdownField(field)) {
            const customOptions = getCustomFieldOptions(moduleKey, field.name);
            if (customOptions !== null) {
                return customOptions;
            }
        }

        if (Array.isArray(field.options) && field.options.length) {
            return field.options;
        }

        if (field.source) {
            const selectedValue = formValues[`data[${field.name}]`] || formValues[field.name];
            return injectSelectedLookupOption(financeLookupOptions[field.source] || [], field.source, selectedValue);
        }

        if (field.sourceMap && field.sourceKey) {
            const keyValue = formValues[`data[${field.sourceKey}]`] || formValues[field.sourceKey];
            const mappedModule = field.sourceMap[keyValue];
            const options = mappedModule ? (financeLookupOptions[mappedModule] || []) : [];
            const selectedValue = formValues[`data[${field.name}]`] || formValues[field.name];
            return mappedModule
                ? injectSelectedLookupOption(options, mappedModule, selectedValue)
                : options;
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
        const linkedLrData = linkedLrRecord?.data || {};
        const linkedCaLabel = getLookupLabel('ca', linkedLrRecord?.data?.linked_ca_id) || linkedLrRecord?.data?.linked_ca_id || 'N/A';
        const detailsText = Array.isArray(linkedLrRecord?.data?.line_items) && linkedLrRecord.data.line_items.length
            ? linkedLrRecord.data.line_items
                .map((item) => [item.item_id, item.description, item.category].filter(Boolean).join(' - '))
                .filter(Boolean)
                .join(', ')
            : (linkedLrRecord?.data?.expense_line_items || linkedLrRecord?.data?.purpose || linkedLrRecord?.record_title || '');
        const requesterPrefill = {
            requester_employee_id: linkedLrData.requester_employee_id || '',
            requestor: linkedLrData.requestor || linkedLrData.employee_name || bootstrap.currentUserName || '',
            employee_id: linkedLrData.employee_id || '',
            employee_name: linkedLrData.employee_name || linkedLrData.requestor || bootstrap.currentUserName || '',
            employee_email: linkedLrData.employee_email || '',
            contact_number: linkedLrData.contact_number || '',
            position: linkedLrData.position || '',
            department: linkedLrData.department || '',
            superior: linkedLrData.superior || '',
            superior_email: linkedLrData.superior_email || '',
        };

        if (variance < 0) {
            const amount = Math.abs(variance).toFixed(2);
            return {
                moduleKey: 'err',
                linkedRecord: linkedLrRecord,
                prefill: {
                    linked_lr_id: linkedLrRecord.id,
                    requester_mode: 'own_request',
                    ...requesterPrefill,
                    expense_details: detailsText || `Shortage from ${linkedCaLabel}`,
                    amount,
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
                    ...requesterPrefill,
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

        const variance = (numericAmount(linkedLrRecord?.data?.total_cash_advance || 0) - numericAmount(linkedLrRecord?.data?.actual_expenses || 0)).toFixed(2);
        const statusMeta = getLiquidationStatusMeta(variance);
        const indicator = statusMeta.label;
        const isShortage = statusMeta.indicator === 'Shortage';
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
            po: ['record_number', 'record_title', 'record_date', 'linked_pr_id', 'supplier_id', 'amount', 'expected_delivery_date', 'purpose', 'remarks'],
            ca: ['record_number', 'record_title', 'record_date', 'requestor', 'department', 'amount_requested', 'mode_of_release', 'purpose', 'remarks'],
            lr: ['record_number', 'record_title', 'record_date', 'linked_ca_id', 'total_cash_advance', 'actual_expenses', 'variance', 'variance_indicator', 'purpose', 'remarks'],
            err: ['record_number', 'record_title', 'record_date', 'linked_lr_id', 'amount', 'expense_details', 'reimbursement_mode', 'purpose', 'remarks'],
            pda: ['record_number', 'record_title', 'record_date', 'payroll_period_id', 'period_start', 'period_end', 'payroll_start', 'payroll_end', 'pay_date', 'employee_count', 'basic_salary_total', 'yearly_basic_total', 'daily_rate_total', 'hourly_rate_total', 'minute_rate_total', 'gross_pay_total', 'benefits_total', 'allowances_total', 'deductions_total', 'night_differential_total', 'holiday_pay_total', 'total_payroll_amount', 'department', 'funding_bank_account_id', 'payroll_expense_coa_id', 'remarks'],
            crf: ['record_number', 'record_title', 'record_date', 'linked_lr_id', 'amount_returned', 'mode_of_return', 'remarks'],
            ibtf: ['record_number', 'record_title', 'record_date', 'source_bank_account_id', 'destination_bank_account_id', 'amount', 'reason', 'remarks'],
            arf: ['record_number', 'record_title', 'record_date', 'linked_po_id', 'linked_dv_id', 'asset_description', 'asset_category', 'acquisition_cost', 'acquisition_date', 'remarks'],
        };

        return fieldsByModule[sourceType] || ['record_number', 'record_title', 'record_date', 'amount', 'remarks'];
    }

    function getDvSourceDocumentSnapshot(sourceType, sourceRecord = null) {
        const data = sourceRecord?.data || {};
        const amount = [
            data.amount,
            data.grand_total,
            data.amount_requested,
            data.total_cash_advance,
            data.amount_returned,
            data.total_payroll_amount,
            data.acquisition_cost,
            sourceRecord?.amount,
        ].find((value) => !blank(value));

        const remainingBalance = [
            data.ca_payment_remaining_balance,
            data.remaining_balance,
            data.balance_remaining,
            data.remaining_amount,
            data.available_balance,
        ].find((value) => !blank(value));

        const requester = [
            data.requestor,
            data.employee_name,
            data.requester_name,
            data.submitted_by_name,
            sourceRecord?.requestor,
            sourceRecord?.employee_name,
            sourceRecord?.requester_name,
            sourceRecord?.submitted_by_name,
        ].find((value) => !blank(value)) || '';

        const supplierName = [
            data.supplier_name,
            data.supplier_submitted_by_name,
            data.representative_full_name,
            sourceRecord?.supplier_name,
        ].find((value) => !blank(value)) || '';

        const employeeName = [
            data.employee_name,
            data.requestor,
            data.requester_name,
            sourceRecord?.employee_name,
            sourceRecord?.requestor,
        ].find((value) => !blank(value)) || '';

        const totalDisbursedAmount = [
            data.total_disbursed_amount,
            sourceRecord?.total_disbursed_amount,
        ].find((value) => !blank(value)) || '';

        const percentagePaid = [
            data.percentage_paid,
            sourceRecord?.percentage_paid,
        ].find((value) => !blank(value)) || '';

        const disbursementStatus = [
            data.disbursement_status,
            sourceRecord?.disbursement_status,
        ].find((value) => !blank(value)) || '';
        const payeeInfo = getDvSourceDocumentPayeeInfo(sourceType, sourceRecord);
        const currentBalance = amount ?? '';
        const availableBalance = remainingBalance ?? '';
        const reservedBalance = numericAmount(currentBalance || 0) > 0 || numericAmount(availableBalance || 0) >= 0
            ? Math.max(numericAmount(currentBalance || 0) - numericAmount(availableBalance || 0), 0).toFixed(2)
            : (totalDisbursedAmount || '0.00');
        const accountingRows = Array.isArray(sourceRecord?.data?.line_items) ? sourceRecord.data.line_items : [];
        const accountingTotals = accountingRows.reduce((totals, row) => {
            totals.debit += numericAmount(row?.debit || 0);
            totals.credit += numericAmount(row?.credit || 0);
            return totals;
        }, { debit: 0, credit: 0 });
        const totalDebitAmount = accountingTotals.debit.toFixed(2);
        const totalCreditAmount = accountingTotals.credit.toFixed(2);
        const accountingBalanceDifference = Math.abs(accountingTotals.debit - accountingTotals.credit).toFixed(2);
        const accountingBalanceStatus = numericAmount(accountingBalanceDifference) < 0.01 ? 'Balanced' : 'Unbalanced';

        return {
            source_record_number: sourceRecord?.record_number || '',
            source_record_date: sourceRecord?.record_date || '',
            source_requester: requester,
            source_department: data.department || data.requesting_department || '',
            source_project: data.project || data.project_name || data.project_code || data.fund_source || data.department || data.requesting_department || '',
            source_cost_center: data.cost_center || data.cost_center_code || data.cost_center_name || data.department || data.requesting_department || data.fund_source || '',
            source_fund_source: data.fund_source || data.project || data.department || '',
            source_amount: amount ?? '',
            source_remaining_balance: remainingBalance ?? '',
            source_current_balance: currentBalance,
            source_reserved_balance: reservedBalance,
            source_available_balance: availableBalance,
            source_approval_status: sourceRecord?.approval_status || 'Pending',
            source_approved_by_name: sourceRecord?.approved_by_name || data.approved_by_name || '',
            source_approved_at: sourceRecord?.approved_at || data.approved_at || '',
            source_supplier_name: supplierName || (payeeInfo.payee_type === 'Supplier' ? payeeInfo.payee_name : ''),
            source_employee_name: employeeName,
            source_payee_type: payeeInfo.payee_type,
            source_payee_name: payeeInfo.payee_name,
            source_payee_type_label: payeeInfo.payee_type,
            source_payee_name_label: payeeInfo.payee_name,
            source_status: sourceRecord?.status || '',
            source_workflow_status: sourceRecord?.workflow_status || '',
            source_relationship_status: sourceRecord?.relationship_status || '',
            total_disbursed_amount: totalDisbursedAmount,
            percentage_paid: percentagePaid,
            disbursement_status: disbursementStatus,
            total_debit_amount: totalDebitAmount,
            total_credit_amount: totalCreditAmount,
            accounting_balance_difference: accountingBalanceDifference,
            accounting_balance_status: accountingBalanceStatus,
            accounting_balance_warning: accountingBalanceStatus === 'Unbalanced'
                ? 'Total Debit must equal Total Credit before the Disbursement Voucher can be approved.'
                : '',
        };
    }

    function getDvSourceDocumentPayeeInfo(sourceType, sourceRecord = null) {
        const data = sourceRecord?.data || {};
        const moduleKey = String(sourceType || sourceRecord?.module_key || '').toLowerCase();
        const requestorName = [
            data.requestor,
            data.employee_name,
            data.requester_name,
            data.representative_full_name,
            sourceRecord?.requestor,
            sourceRecord?.employee_name,
        ].find((value) => !blank(value)) || '';
        const supplierName = [
            data.supplier_name,
            data.supplier_submitted_by_name,
            data.representative_full_name,
            sourceRecord?.supplier_name,
        ].find((value) => !blank(value)) || '';
        const representativeName = [
            data.representative_full_name,
            data.supplier_submitted_by_name,
            sourceRecord?.representative_full_name,
        ].find((value) => !blank(value)) || '';
        const position = String(data.position || data.officer_type || '').trim().toLowerCase();
        const payrollLabel = [
            data.payroll_period_label,
            data.payroll_period_name,
            sourceRecord?.record_title,
        ].find((value) => !blank(value)) || '';
        const bankAccountLabel = [
            getLookupLabel('bank_account', data.destination_bank_account_id),
            data.destination_account_code,
            data.destination_bank_account_id,
        ].find((value) => !blank(value)) || '';

        if (moduleKey === 'po') {
            return { payee_type: 'Supplier', payee_name: supplierName };
        }

        if (moduleKey === 'ca' || moduleKey === 'err') {
            return {
                payee_type: representativeName
                    ? 'Authorized Representative'
                    : (['president', 'treasurer', 'secretary', 'vice president', 'vice-president', 'chairperson', 'chairman', 'director'].includes(position) ? 'Officer' : 'Employee'),
                payee_name: representativeName || requestorName,
            };
        }

        if (moduleKey === 'pda') {
            return {
                payee_type: 'Payroll Group',
                payee_name: payrollLabel || sourceRecord?.record_number || '',
            };
        }

        if (moduleKey === 'ibtf') {
            return {
                payee_type: 'Receiving Bank Account',
                payee_name: bankAccountLabel,
            };
        }

        return {
            payee_type: '',
            payee_name: requestorName || supplierName || representativeName,
        };
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

    function firstLookupValue(source) {
        const option = (financeLookupOptions[source] || [])[0] || null;
        return option ? String(option.id ?? option.value ?? '') : '';
    }

    function getDvSourceBankAccountId(data = {}) {
        return [
            data.bank_account_id,
            data.funding_bank_account_id,
            data.receiving_bank_account_id,
            data.source_bank_account_id,
            data.destination_bank_account_id,
        ].find((value) => !blank(value)) || firstLookupValue('bank_account');
    }

    function getDvAccountCode(value) {
        if (blank(value)) return '';
        return getLookupLabel('chart_account', value) || String(value || '');
    }

    function getDvSourceLineItemAmount(row = {}) {
        const quantity = numericAmount(row.quantity || 0);
        const unitAmount = numericAmount(row.amount || row.unit_cost || row.default_cost || 0);
        return numericAmount(row.total || row.line_total || row.debit || row.credit || 0) || (quantity * unitAmount) || unitAmount;
    }

    function buildDvLineItemsFromSource(sourceType, sourceRecord, payload = {}) {
        const data = sourceRecord?.data || {};
        const sourceRows = Array.isArray(data.line_items) ? data.line_items : [];
        const accountCode = getDvAccountCode(payload.coa_id || data.coa_id || data.payroll_expense_coa_id || data.asset_coa_id);

        if (sourceRows.length) {
            const debitRows = sourceRows
                .filter((row) => row && Object.values(row).some((value) => !blank(value)))
                .map((row, index) => {
                    const amount = getDvSourceLineItemAmount(row);
                    const description = [
                        row.item_id || row.description || row.account_code || `Line ${index + 1}`,
                        row.category ? `(${row.category})` : '',
                    ].filter(Boolean).join(' ');

                    return {
                        description,
                        account_code: getDvAccountCode(row.coa_id || row.account_code || payload.coa_id || data.coa_id) || accountCode,
                        debit: amount ? amount.toFixed(2) : '',
                        credit: '',
                    };
                });

            const totalDebit = debitRows.reduce((sum, row) => sum + numericAmount(row.debit || 0), 0);
            const bankAccountRecord = getRecordByLookupValue('bank_account', payload.bank_account_id || data.bank_account_id || data.funding_bank_account_id || data.receiving_bank_account_id || data.source_bank_account_id || data.destination_bank_account_id);
            const creditAccountCode = getDvAccountCode(bankAccountRecord?.data?.linked_coa_id || payload.coa_id || data.coa_id || data.payroll_expense_coa_id || data.asset_coa_id || firstLookupValue('chart_account'));

            if (totalDebit > 0 && creditAccountCode) {
                debitRows.push({
                    description: `Credit ${getLookupLabel('bank_account', payload.bank_account_id || data.bank_account_id || data.funding_bank_account_id || data.receiving_bank_account_id || data.source_bank_account_id || data.destination_bank_account_id) || 'fund source'}`,
                    account_code: creditAccountCode,
                    debit: '',
                    credit: totalDebit.toFixed(2),
                });
            }

            return debitRows;
        }

        if (sourceType === 'ibtf') {
            const amount = numericAmount(payload.amount || data.amount || sourceRecord?.amount || 0);
            return [
                {
                    description: `Transfer to ${getLookupLabel('bank_account', data.destination_bank_account_id) || data.destination_bank_account_id || 'destination account'}`,
                    account_code: data.destination_account_code || '',
                    debit: amount ? amount.toFixed(2) : '',
                    credit: '',
                },
                {
                    description: `Transfer from ${getLookupLabel('bank_account', data.source_bank_account_id) || data.source_bank_account_id || 'source account'}`,
                    account_code: data.source_account_code || '',
                    debit: '',
                    credit: amount ? amount.toFixed(2) : '',
                },
            ];
        }

        const amount = numericAmount(payload.amount || sourceRecord?.amount || data.amount || data.grand_total || data.amount_requested || data.total_payroll_amount || data.acquisition_cost || 0);
        const description = [
            sourceRecord?.record_number || String(sourceType || '').toUpperCase(),
            data.purpose || data.expense_details || data.reason || data.asset_description || data.supporting_payroll_summary || sourceRecord?.record_title || 'Source document amount',
        ].filter(Boolean).join(' - ');

        const bankAccountRecord = getRecordByLookupValue('bank_account', payload.bank_account_id || data.bank_account_id || data.funding_bank_account_id || data.receiving_bank_account_id || data.source_bank_account_id || data.destination_bank_account_id);
        const creditAccountCode = getDvAccountCode(bankAccountRecord?.data?.linked_coa_id || payload.coa_id || data.coa_id || data.payroll_expense_coa_id || data.asset_coa_id || firstLookupValue('chart_account'));

        return [
            {
                description,
                account_code: accountCode,
                debit: amount ? amount.toFixed(2) : '',
                credit: '',
            },
            {
                description: `Credit ${getLookupLabel('bank_account', payload.bank_account_id || data.bank_account_id || data.funding_bank_account_id || data.receiving_bank_account_id || data.source_bank_account_id || data.destination_bank_account_id) || 'fund source'}`,
                account_code: creditAccountCode,
                debit: '',
                credit: amount ? amount.toFixed(2) : '',
            },
        ].filter((row) => blank(row.account_code) ? false : true);
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
        const sourceSnapshot = getDvSourceDocumentSnapshot(sourceType, sourceRecord);
        const statusLabel = sourceRecord.workflow_status || sourceRecord.approval_status || 'Accepted';
        const detailsFields = getDvSourceDocumentFields(sourceType);
        const data = sourceRecord.data || {};
        const approvalInformation = [
            sourceSnapshot.source_approval_status,
            sourceSnapshot.source_approved_by_name ? `Approved by ${sourceSnapshot.source_approved_by_name}` : '',
            sourceSnapshot.source_approved_at ? `on ${sourceSnapshot.source_approved_at}` : '',
        ].filter(Boolean).join(' • ');
        const disbursementInformation = [
            sourceSnapshot.disbursement_status,
            blank(sourceSnapshot.total_disbursed_amount) ? '' : `Disbursed ${formatCurrency(sourceSnapshot.total_disbursed_amount)}`,
            blank(sourceSnapshot.percentage_paid) ? '' : `${sourceSnapshot.percentage_paid}% paid`,
        ].filter(Boolean).join(' • ');
        const statusInformation = [
            sourceSnapshot.source_status,
            sourceSnapshot.source_workflow_status,
            sourceSnapshot.source_relationship_status,
        ].filter(Boolean).join(' • ');
        const projectedBalanceAfterPayment = Math.max(
            numericAmount(sourceSnapshot.source_available_balance || sourceSnapshot.source_remaining_balance || 0)
            - numericAmount(payload.prefill.amount || sourceSnapshot.source_amount || 0),
            0,
        );
        const partyInformation = [
            sourceSnapshot.source_payee_type && sourceSnapshot.source_payee_name ? `${sourceSnapshot.source_payee_type}: ${sourceSnapshot.source_payee_name}` : '',
            sourceSnapshot.source_supplier_name ? `Supplier: ${sourceSnapshot.source_supplier_name}` : '',
            sourceSnapshot.source_employee_name ? `Employee: ${sourceSnapshot.source_employee_name}` : '',
        ].filter(Boolean).join(' • ');

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
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Source Record Number</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.source_record_number || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Source Record Date</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.source_record_date || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Requester</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.source_requester || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Department</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.source_department || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Project</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.source_project || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Cost Center</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.source_cost_center || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Fund Source</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.source_fund_source || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Amount</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(formatCurrency(sourceSnapshot.source_amount || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Current Balance</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(formatCurrency(sourceSnapshot.source_current_balance || sourceSnapshot.source_amount || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Reserved Balance</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(formatCurrency(sourceSnapshot.source_reserved_balance || sourceSnapshot.total_disbursed_amount || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Available Balance</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(formatCurrency(sourceSnapshot.source_available_balance || sourceSnapshot.source_remaining_balance || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Projected Balance After Payment</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(formatCurrency(projectedBalanceAfterPayment || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Accounting Status</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.accounting_balance_status || 'Balanced')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Total Debit</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(formatCurrency(sourceSnapshot.total_debit_amount || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Total Credit</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(formatCurrency(sourceSnapshot.total_credit_amount || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Remaining Balance</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(blank(sourceSnapshot.source_remaining_balance) ? 'N/A' : formatCurrency(sourceSnapshot.source_remaining_balance))}</p>
                    </div>
                    ${(sourceSnapshot.accounting_balance_warning ? `
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 md:col-span-2">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-amber-700">Accounting Warning</p>
                            <p class="mt-1 font-semibold text-amber-900 break-words">${escapeHtml(sourceSnapshot.accounting_balance_warning)}</p>
                        </div>
                    ` : '')}
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 md:col-span-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Approval Information</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(approvalInformation || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 md:col-span-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Payee Information</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(sourceSnapshot.source_payee_type && sourceSnapshot.source_payee_name ? `${sourceSnapshot.source_payee_type}: ${sourceSnapshot.source_payee_name}` : 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 md:col-span-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Supplier / Employee Information</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(partyInformation || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 md:col-span-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Status Information</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(statusInformation || 'N/A')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 md:col-span-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Disbursement Summary</p>
                        <p class="mt-1 font-semibold text-gray-900 break-words">${escapeHtml(disbursementInformation || 'N/A')}</p>
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

        const bankAccountId = getDvSourceBankAccountId(data);

        const paymentType = normalizeDvPaymentType([
            payload.payment_type,
            data.payment_type,
            data.mode_of_release,
            data.reimbursement_mode,
            data.mode_of_return,
            data.paid_through,
        ].find((value) => !blank(value))) || 'Cash';

        const coaId = [
            payload.coa_id,
            data.coa_id,
            data.payroll_expense_coa_id,
            data.asset_coa_id,
        ].find((value) => !blank(value)) || firstLookupValue('chart_account');

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
        const recordTitle = getVisibleRecordTitle(sourceRecord) || referenceNumber || '';
        const sourceSnapshot = getDvSourceDocumentSnapshot(moduleKey, sourceRecord);
        const payeeInfo = getDvSourceDocumentPayeeInfo(moduleKey, sourceRecord);
        const currentBalance = numericAmount(sourceSnapshot.source_current_balance || sourceSnapshot.source_amount || amount || 0);
        const reservedBalance = numericAmount(sourceSnapshot.source_reserved_balance || sourceSnapshot.total_disbursed_amount || 0);
        const availableBalance = numericAmount(sourceSnapshot.source_available_balance || sourceSnapshot.source_remaining_balance || 0);
        const projectedBalanceAfterPayment = Math.max(availableBalance - numericAmount(amount || 0), 0);
        const accountingSourceRows = Array.isArray(sourceRecord?.data?.line_items) && sourceRecord.data.line_items.length
            ? sourceRecord.data.line_items
            : [];
        const accountingTotals = accountingSourceRows.reduce((totals, row) => {
            totals.debit += numericAmount(row?.debit || 0);
            totals.credit += numericAmount(row?.credit || 0);
            return totals;
        }, { debit: 0, credit: 0 });
        const totalDebit = accountingTotals.debit.toFixed(2);
        const totalCredit = accountingTotals.credit.toFixed(2);
        const accountingBalanceDifference = Math.abs(accountingTotals.debit - accountingTotals.credit).toFixed(2);
        const accountingBalanceStatus = numericAmount(accountingBalanceDifference) < 0.01 ? 'Balanced' : 'Unbalanced';

        const prefill = {
            source_document_type: moduleKey,
            source_document_id: sourceRecord?.id || '',
            ...sourceSnapshot,
            amount: amount ?? '',
            supplier_id: supplierId,
            coa_id: coaId || '',
            bank_account_id: bankAccountId || '',
            payment_type: paymentType,
            disbursement_type: paymentType || 'Cash',
            fund_source: data.fund_source || data.project || data.department || '',
            department: data.department || data.requesting_department || '',
            purpose,
            payment_date: paymentDate,
            due_date: data.due_date || data.needed_date || data.expected_delivery_date || data.pay_date || data.acquisition_date || '',
            reference_number: referenceNumber,
            withholding_tax: data.withholding_tax || data.wht_amount || data.wht_total || '',
            vat_amount: data.vat_amount || data.tax_amount || data.tax_total || '',
            currency: data.currency || 'PHP',
            exchange_rate: data.exchange_rate || '',
            received_by_name: data.received_by_name || '',
            received_by_signature: data.received_by_signature || '',
            date_received: data.date_received || '',
            remarks: payload.remarks || data.remarks || '',
            payee_type: payeeInfo.payee_type || '',
            payee_name: payeeInfo.payee_name || '',
            current_balance: currentBalance ? currentBalance.toFixed(2) : '',
            reserved_balance: reservedBalance ? reservedBalance.toFixed(2) : '',
            available_balance: availableBalance ? availableBalance.toFixed(2) : '',
            projected_balance_after_payment: projectedBalanceAfterPayment.toFixed(2),
            ...sourceSnapshot,
        };

        if (moduleKey === 'ca') {
            prefill.supplier_id = data.supplier_id || '';
            prefill.bank_account_id = data.bank_account_id || bankAccountId || '';
        }

        if (moduleKey === 'lr') {
            prefill.amount = data.grand_total || data.actual_expenses || data.total_cash_advance || amount || '';
            prefill.purpose = data.purpose || '';
        }

        if (moduleKey === 'err') {
            prefill.amount = data.amount || amount || '';
            prefill.supplier_id = data.supplier_id || '';
            prefill.bank_account_id = data.bank_account_id || bankAccountId || '';
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

        ['source_document_id', 'supplier_id', 'payee_type', 'payee_name', 'amount', 'bank_account_id', 'coa_id', 'payment_type', 'disbursement_type', 'fund_source', 'department', 'purpose', 'payment_date', 'due_date', 'withholding_tax', 'vat_amount', 'net_amount', 'currency', 'exchange_rate', 'received_by_name', 'received_by_signature', 'date_received', 'reference_number', 'remarks', 'source_record_number', 'source_record_date', 'source_requester', 'source_department', 'source_project', 'source_cost_center', 'source_fund_source', 'source_amount', 'source_remaining_balance', 'source_current_balance', 'source_reserved_balance', 'source_available_balance', 'source_approval_status', 'source_approved_by_name', 'source_approved_at', 'source_supplier_name', 'source_employee_name', 'source_payee_type', 'source_payee_name', 'source_status', 'source_workflow_status', 'source_relationship_status', 'total_disbursed_amount', 'percentage_paid', 'disbursement_status', 'projected_balance_after_payment', 'fund_availability_status', 'fund_availability_warning', 'fund_availability_requested_amount', 'fund_availability_available_balance', 'fund_availability_checked_at', 'fund_availability_checked_by', 'fund_availability_checked_by_name', 'fund_availability_policy_allows_approval', 'accounting_balance_status', 'accounting_balance_warning', 'total_debit_amount', 'total_credit_amount', 'accounting_balance_difference', 'accounting_balance_checked_at', 'accounting_balance_checked_by', 'accounting_balance_checked_by_name'].forEach((fieldName) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (input) {
                input.value = '';
            }
        });
        financeFormValues = financeFormValues || {};
        ['source_document_id', 'supplier_id', 'payee_type', 'payee_name', 'amount', 'bank_account_id', 'coa_id', 'payment_type', 'disbursement_type', 'fund_source', 'department', 'purpose', 'payment_date', 'due_date', 'withholding_tax', 'vat_amount', 'net_amount', 'currency', 'exchange_rate', 'received_by_name', 'received_by_signature', 'date_received', 'reference_number', 'remarks', 'source_record_number', 'source_record_date', 'source_requester', 'source_department', 'source_project', 'source_cost_center', 'source_fund_source', 'source_amount', 'source_remaining_balance', 'source_current_balance', 'source_reserved_balance', 'source_available_balance', 'source_approval_status', 'source_approved_by_name', 'source_approved_at', 'source_supplier_name', 'source_employee_name', 'source_payee_type', 'source_payee_name', 'source_status', 'source_workflow_status', 'source_relationship_status', 'total_disbursed_amount', 'percentage_paid', 'disbursement_status', 'projected_balance_after_payment', 'fund_availability_status', 'fund_availability_warning', 'fund_availability_requested_amount', 'fund_availability_available_balance', 'fund_availability_checked_at', 'fund_availability_checked_by', 'fund_availability_checked_by_name', 'fund_availability_policy_allows_approval', 'accounting_balance_status', 'accounting_balance_warning', 'total_debit_amount', 'total_credit_amount', 'accounting_balance_difference', 'accounting_balance_checked_at', 'accounting_balance_checked_by', 'accounting_balance_checked_by_name'].forEach((fieldName) => {
            financeFormValues[fieldName] = '';
            financeFormValues[`data[${fieldName}]`] = '';
        });
        financeFormValues.dv_line_items = [];
        replaceDvLineItemsTable([]);
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
            source_record_number: prefill?.prefill?.source_record_number || '',
            source_record_date: prefill?.prefill?.source_record_date || '',
            source_requester: prefill?.prefill?.source_requester || '',
            source_department: prefill?.prefill?.source_department || '',
            source_project: prefill?.prefill?.source_project || '',
            source_cost_center: prefill?.prefill?.source_cost_center || '',
            source_fund_source: prefill?.prefill?.source_fund_source || '',
            source_amount: prefill?.prefill?.source_amount || '',
            source_remaining_balance: prefill?.prefill?.source_remaining_balance || '',
            source_approval_status: prefill?.prefill?.source_approval_status || '',
            source_approved_by_name: prefill?.prefill?.source_approved_by_name || '',
            source_approved_at: prefill?.prefill?.source_approved_at || '',
            source_supplier_name: prefill?.prefill?.source_supplier_name || '',
            source_employee_name: prefill?.prefill?.source_employee_name || '',
            source_payee_type: prefill?.prefill?.source_payee_type || '',
            source_payee_name: prefill?.prefill?.source_payee_name || '',
            source_current_balance: prefill?.prefill?.source_current_balance || '',
            source_reserved_balance: prefill?.prefill?.source_reserved_balance || '',
            source_available_balance: prefill?.prefill?.source_available_balance || '',
            source_status: prefill?.prefill?.source_status || '',
            source_workflow_status: prefill?.prefill?.source_workflow_status || '',
            source_relationship_status: prefill?.prefill?.source_relationship_status || '',
            total_disbursed_amount: prefill?.prefill?.total_disbursed_amount || '',
            percentage_paid: prefill?.prefill?.percentage_paid || '',
            disbursement_status: prefill?.prefill?.disbursement_status || '',
            projected_balance_after_payment: prefill?.prefill?.projected_balance_after_payment || '',
            fund_availability_status: prefill?.prefill?.fund_availability_status || '',
            fund_availability_warning: prefill?.prefill?.fund_availability_warning || '',
            fund_availability_requested_amount: prefill?.prefill?.fund_availability_requested_amount || '',
            fund_availability_available_balance: prefill?.prefill?.fund_availability_available_balance || '',
            fund_availability_checked_at: prefill?.prefill?.fund_availability_checked_at || '',
            fund_availability_checked_by: prefill?.prefill?.fund_availability_checked_by || '',
            fund_availability_checked_by_name: prefill?.prefill?.fund_availability_checked_by_name || '',
            fund_availability_policy_allows_approval: prefill?.prefill?.fund_availability_policy_allows_approval || '',
            accounting_balance_status: accountingBalanceStatus,
            accounting_balance_warning: accountingBalanceStatus === 'Unbalanced'
                ? 'Total Debit must equal Total Credit before the Disbursement Voucher can be approved.'
                : '',
            total_debit_amount: totalDebit,
            total_credit_amount: totalCredit,
            accounting_balance_difference: accountingBalanceDifference,
            payee_type: prefill?.prefill?.payee_type || '',
            payee_name: prefill?.prefill?.payee_name || '',
            supplier_id: prefill?.prefill?.supplier_id || '',
            amount: prefill?.prefill?.amount || '',
            bank_account_id: prefill?.prefill?.bank_account_id || '',
            payment_type: prefill?.prefill?.payment_type || '',
            disbursement_type: prefill?.prefill?.disbursement_type || prefill?.prefill?.payment_type || 'Cash',
            coa_id: prefill?.prefill?.coa_id || '',
            fund_source: prefill?.prefill?.fund_source || '',
            department: prefill?.prefill?.department || '',
            purpose: prefill?.prefill?.purpose || '',
            payment_date: prefill?.prefill?.payment_date || '',
            due_date: prefill?.prefill?.due_date || '',
            withholding_tax: prefill?.prefill?.withholding_tax || '',
            vat_amount: prefill?.prefill?.vat_amount || '',
            currency: prefill?.prefill?.currency || 'PHP',
            exchange_rate: prefill?.prefill?.exchange_rate || '',
            received_by_name: prefill?.prefill?.received_by_name || '',
            received_by_signature: prefill?.prefill?.received_by_signature || '',
            date_received: prefill?.prefill?.date_received || '',
            reference_number: prefill?.prefill?.reference_number || '',
            remarks: prefill?.prefill?.remarks || '',
            line_items: buildDvLineItemsFromSource(resolvedSourceType, sourceRecord, prefill?.prefill || {}),
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
        setField('source_record_number', payload.source_record_number);
        setField('source_record_date', payload.source_record_date);
        setField('source_requester', payload.source_requester);
        setField('source_department', payload.source_department);
        setField('source_project', payload.source_project);
        setField('source_cost_center', payload.source_cost_center);
        setField('source_fund_source', payload.source_fund_source);
        setField('source_amount', payload.source_amount);
        setField('source_remaining_balance', payload.source_remaining_balance);
        setField('source_approval_status', payload.source_approval_status);
        setField('source_approved_by_name', payload.source_approved_by_name);
        setField('source_approved_at', payload.source_approved_at);
        setField('source_supplier_name', payload.source_supplier_name);
        setField('source_employee_name', payload.source_employee_name);
        setField('source_payee_type', payload.source_payee_type);
        setField('source_payee_name', payload.source_payee_name);
        setField('source_current_balance', payload.source_current_balance);
        setField('source_reserved_balance', payload.source_reserved_balance);
        setField('source_available_balance', payload.source_available_balance);
        setField('source_status', payload.source_status);
        setField('source_workflow_status', payload.source_workflow_status);
        setField('source_relationship_status', payload.source_relationship_status);
        setField('total_disbursed_amount', payload.total_disbursed_amount);
        setField('percentage_paid', payload.percentage_paid);
        setField('disbursement_status', payload.disbursement_status);
        setField('projected_balance_after_payment', payload.projected_balance_after_payment);
        setField('fund_availability_status', payload.fund_availability_status);
        setField('fund_availability_warning', payload.fund_availability_warning);
        setField('fund_availability_requested_amount', payload.fund_availability_requested_amount);
        setField('fund_availability_available_balance', payload.fund_availability_available_balance);
        setField('fund_availability_checked_at', payload.fund_availability_checked_at);
        setField('fund_availability_checked_by', payload.fund_availability_checked_by);
        setField('fund_availability_checked_by_name', payload.fund_availability_checked_by_name);
        setField('fund_availability_policy_allows_approval', payload.fund_availability_policy_allows_approval);
        setField('accounting_balance_status', payload.accounting_balance_status);
        setField('accounting_balance_warning', payload.accounting_balance_warning);
        setField('total_debit_amount', payload.total_debit_amount);
        setField('total_credit_amount', payload.total_credit_amount);
        setField('accounting_balance_difference', payload.accounting_balance_difference);
        setField('payee_type', payload.payee_type);
        setField('payee_name', payload.payee_name);
        setField('supplier_id', payload.supplier_id);
        setField('amount', payload.amount);
        setField('bank_account_id', payload.bank_account_id);
        setField('payment_type', payload.payment_type);
        setField('disbursement_type', payload.disbursement_type);
        setField('coa_id', payload.coa_id);
        setField('fund_source', payload.fund_source);
        setField('department', payload.department);
        setField('purpose', payload.purpose);
        setField('payment_date', payload.payment_date);
        setField('due_date', payload.due_date);
        setField('withholding_tax', payload.withholding_tax);
        setField('vat_amount', payload.vat_amount);
        setField('currency', payload.currency);
        setField('exchange_rate', payload.exchange_rate);
        setField('received_by_name', payload.received_by_name);
        setField('received_by_signature', payload.received_by_signature);
        setField('date_received', payload.date_received);
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

        replaceDvLineItemsTable(payload.line_items || []);
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

        financeDraftContext = sourceRecord ? {
            moduleKey: 'dv',
            linkedRecord: sourceRecord,
            prefill: getDvFieldPayload(resolvedSourceType, sourceRecord, sourceId),
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

    function isTruthyFormValue(value) {
        if (Array.isArray(value)) {
            return value.length > 0;
        }

        if (typeof value === 'boolean') {
            return value;
        }

        const normalized = String(value ?? '').trim().toLowerCase();
        return normalized !== '' && normalized !== '0' && normalized !== 'false' && normalized !== 'no' && normalized !== 'off';
    }

    function fieldNameFromInputName(inputName = '') {
        const match = String(inputName).match(/^data\[(.+)\]$/);
        return match ? match[1] : inputName;
    }

    function cssIdentifier(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
    }

    function syncDependentFieldState(controllerInput) {
        if (!controllerInput) return;

        const controllerName = fieldNameFromInputName(controllerInput.name);
        const enabled = controllerInput.type === 'checkbox'
            ? controllerInput.checked
            : isTruthyFormValue(controllerInput.value);
        const controllerValue = controllerInput.type === 'checkbox'
            ? (controllerInput.checked ? '1' : '')
            : controllerInput.value;

        financeFormValues[controllerName] = controllerValue;
        financeFormValues[`data[${controllerName}]`] = controllerValue;

        document.querySelectorAll(`[data-depends-on-checkbox="${cssIdentifier(controllerName)}"]`).forEach((dependentInput) => {
            const wrapper = dependentInput.closest('[data-finance-field]');
            if (wrapper) {
                wrapper.hidden = !enabled;
                wrapper.classList.toggle('hidden', !enabled);
            }

            dependentInput.disabled = !enabled;
            dependentInput.classList.toggle('bg-gray-100', !enabled);
            dependentInput.classList.toggle('cursor-not-allowed', !enabled);

            if (!enabled) {
                dependentInput.value = '';
                const dependentName = fieldNameFromInputName(dependentInput.name);
                financeFormValues[dependentName] = '';
                financeFormValues[`data[${dependentName}]`] = '';
            }
        });
    }

    function syncAllDependentFieldStates() {
        const form = $('financeForm');
        if (!form) return;

        form.querySelectorAll('[data-checkbox-field]').forEach((input) => {
            syncDependentFieldState(input);
        });
    }

    function syncErrReimbursementModeFields() {
        if (currentModuleKey !== 'err') return;

        const form = $('financeForm');
        const mode = form?.querySelector('[name="data[reimbursement_mode]"]')?.value || '';
        const requiredFieldsByMode = {
            Cash: ['cash_receiver_name'],
            'Bank Transfer': ['recipient_bank_account', 'recipient_bank_number'],
            Check: ['bank_account_id'],
        };

        form?.querySelectorAll('[data-err-reimbursement-fields]').forEach((section) => {
            const sectionMode = section.getAttribute('data-err-reimbursement-fields') || '';
            const active = sectionMode === mode;
            const requiredFields = requiredFieldsByMode[sectionMode] || [];

            section.classList.toggle('hidden', !active);
            section.querySelectorAll('input, select, textarea').forEach((input) => {
                const fieldName = fieldNameFromInputName(input.name);
                input.disabled = !active;
                input.required = active && requiredFields.includes(fieldName);
            });
        });
    }

    function renderDynamicField(field, value, formValues = {}) {
        const required = field.required ? 'required' : '';
        const label = escapeHtml(field.label);
        const hint = field.help ? `<p class="mt-1 text-xs text-gray-500">${escapeHtml(field.help)}</p>` : '';
        const fieldName = `data[${field.name}]`;
        const dependencyValue = field.dependsOnCheckbox ? formValues[`data[${field.dependsOnCheckbox}]`] : null;
        const specifyOtherVisible = shouldShowSpecifyOtherField(field.name, formValues);
        const dependencyEnabled = (!field.dependsOnCheckbox || isTruthyFormValue(dependencyValue)) && specifyOtherVisible;
        const forceReadOnly = financeFormLockedReadOnly;
        const disabledAttr = (field.dependsOnCheckbox && !dependencyEnabled) || forceReadOnly ? 'disabled' : '';
        const emptyOption = field.placeholder || `Select ${field.label}`;
        const readOnlyAttr = field.readOnly || forceReadOnly ? 'readonly' : '';
        const readOnlyClass = field.readOnly || (field.dependsOnCheckbox && !dependencyEnabled) || forceReadOnly ? 'bg-gray-100 cursor-not-allowed' : '';
        const wrapperHidden = (field.dependsOnCheckbox && !dependencyEnabled) || !specifyOtherVisible;

        let control = '';

        if (field.type === 'textarea') {
            control = `<textarea name="${fieldName}" rows="${field.rows || 3}" class="w-full border rounded-md p-2 ${readOnlyClass}" ${readOnlyAttr}>${escapeHtml(value)}</textarea>`;
        } else if (field.type === 'selector') {
            const selectedLabel = getLookupLabel(field.source, value) || value || '';
            control = forceReadOnly || field.readOnly
                ? `
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-800">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">${label}</div>
                        <div class="mt-1 break-words font-medium">${escapeHtml(selectedLabel || `Select ${field.label}`)}</div>
                    </div>
                    <input type="hidden" name="${fieldName}" data-lookup-selector-hidden="${escapeHtml(field.name)}" value="${escapeHtml(value)}">
                `
                : `
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
            const displayValue = value || 'N/A';
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
                <select name="${fieldName}" class="w-full border rounded-md p-2 ${readOnlyClass}" ${field.dependsOnCheckbox ? `data-depends-on-checkbox="${escapeHtml(field.dependsOnCheckbox)}"` : ''} ${disabledAttr} ${required} ${field.readOnly ? 'disabled' : ''}>
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
                                <input type="checkbox" name="${fieldName}[]" value="${escapeHtml(optionValue)}" ${checked ? 'checked' : ''} class="rounded border-gray-300" ${forceReadOnly ? 'disabled' : ''}>
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
                                <input type="radio" name="${fieldName}" value="${escapeHtml(optionValue)}" ${checked ? 'checked' : ''} class="border-gray-300" ${forceReadOnly ? 'disabled' : ''}>
                                <span>${escapeHtml(option.label ?? optionValue)}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            `;
        } else if (field.type === 'checkbox') {
            control = `
                <label class="inline-flex items-center gap-2 mt-2">
                    <input type="checkbox" name="${fieldName}" value="1" ${isTruthyFormValue(value) ? 'checked' : ''} data-checkbox-field="${escapeHtml(field.name)}" class="rounded border-gray-300" ${forceReadOnly ? 'disabled' : ''}>
                    <span class="text-sm text-gray-700">${label}</span>
                </label>
            `;
        } else if (field.type === 'acknowledgment') {
            control = `
                <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white px-3 py-3">
                    <input type="checkbox" name="${fieldName}" value="1" ${isTruthyFormValue(value) ? 'checked' : ''} class="mt-1 rounded border-gray-300" ${required} ${forceReadOnly ? 'disabled' : ''}>
                    <span class="text-sm leading-6 text-gray-700">${escapeHtml(field.statement || field.label)}</span>
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
            <div class="${field.fullWidth ? 'md:col-span-2' : ''} ${wrapperHidden ? 'hidden' : ''}" data-finance-field="${escapeHtml(field.name)}">
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
                item_module: item.item_module || item.linked_item_type || item.master_item_type || '',
                item_record_id: item.item_record_id || item.linked_item_id || item.master_item_id || '',
                item_id: item.item_id || '',
                description: item.description || '',
                category: item.category || '',
                quantity: item.quantity || '',
                amount: item.amount || '',
                subtotal: item.subtotal || item.total || '',
                discount: item.discount || '0%',
                discount_amount: item.discount_amount || '',
                shipping_amount: item.shipping_amount || '',
                tax_type: normalizeFinanceTaxType(item.tax_type || 'N/A'),
                tax_amount: item.tax_amount || '',
                wht_amount: item.wht_amount || '',
                tax_impact_label: item.tax_impact_label || '',
                total: item.total || '',
                supplier_id: item.supplier_id || '',
                client_id: item.client_id || '',
            }));
        }

        if (record?.module_key === 'po') {
            const legacyItem = {
                item_module: record ? (getModuleFieldValue(record, { name: 'linked_item_type' }) || '') : '',
                item_record_id: record ? (getModuleFieldValue(record, { name: 'linked_item_id' }) || '') : '',
                item_id: record ? (getModuleFieldValue(record, { name: 'linked_item_id' }) || '') : '',
                description: record ? (getModuleFieldValue(record, { name: 'purpose' }) || '') : '',
                category: record ? (getModuleFieldValue(record, { name: 'linked_item_type' }) || '') : '',
                quantity: record ? (getModuleFieldValue(record, { name: 'quantity' }) || '') : '',
                amount: record ? (getModuleFieldValue(record, { name: 'unit_cost' }) || '') : '',
                subtotal: record ? (getModuleFieldValue(record, { name: 'total_amount' }) || '') : '',
                discount: record ? (getModuleFieldValue(record, { name: 'discount' }) || '0%') : '0%',
                discount_amount: record ? (getModuleFieldValue(record, { name: 'discount_amount' }) || '') : '',
                shipping_amount: record ? (getModuleFieldValue(record, { name: 'shipping_amount' }) || '') : '',
                tax_type: record ? normalizeFinanceTaxType(getModuleFieldValue(record, { name: 'tax_type' }) || 'N/A') : 'N/A',
                tax_amount: record ? (getModuleFieldValue(record, { name: 'tax_amount' }) || '') : '',
                wht_amount: record ? (getModuleFieldValue(record, { name: 'wht_amount' }) || '') : '',
                tax_impact_label: record ? (getModuleFieldValue(record, { name: 'tax_impact_label' }) || '') : '',
                total: record ? (getModuleFieldValue(record, { name: 'total_amount' }) || '') : '',
                supplier_id: record ? (getModuleFieldValue(record, { name: 'supplier_id' }) || '') : '',
                client_id: '',
            };

            if (Object.values(legacyItem).some((value) => String(value || '').trim() !== '')) {
                return [legacyItem];
            }
        }

        const legacyItem = {
            item_module: record ? (getModuleFieldValue(record, { name: 'master_item_type' }) || '') : '',
            item_record_id: record ? (getModuleFieldValue(record, { name: 'master_item_id' }) || '') : '',
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
            item_module: '',
            item_record_id: '',
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
            tax_impact_label: '',
            total: '',
            supplier_id: '',
            client_id: '',
        }];
    }

    function getPrItemDisplayValue(value) {
        if (currentModuleKey === 'po' || currentModuleKey === 'pr') {
            const match = findLineItemMasterOption(value);
            if (match?.option?.label) {
                return match.option.label;
            }
        }

        const productLookup = financeLookupOptions.product || [];
        const matchById = productLookup.find((option) => String(option.id) === String(value));
        if (matchById) {
            return matchById.label;
        }

        return value || '';
    }

    function normalizeLineItemLookupValue(value) {
        return String(value || '').trim().toLowerCase();
    }

    function lineItemMasterSources() {
        if (currentModuleKey === 'pr' || currentModuleKey === 'po') {
            return ['product', 'service'];
        }

        return ['product'];
    }

    function findLineItemMasterOption(value) {
        const cleaned = normalizeLineItemLookupValue(value);
        if (!cleaned) return null;

        for (const moduleKey of lineItemMasterSources()) {
            const options = financeLookupOptions[moduleKey] || [];
            const option = options.find((item) => {
                return [
                    item.id,
                    item.value,
                    item.label,
                    item.record_number,
                    item.record_title,
                ].some((candidate) => normalizeLineItemLookupValue(candidate) === cleaned);
            });

            if (option) {
                return { moduleKey, option };
            }
        }

        return null;
    }

    function getLineItemMasterDefaults(value) {
        const match = findLineItemMasterOption(value);
        if (!match) return null;

        const data = match.option.data || {};
        const isService = match.moduleKey === 'service';
        const description = isService
            ? (data.service_description || data.products_services_provided || match.option.record_title || '')
            : (data.product_description || match.option.record_title || '');

        return {
            moduleKey: match.moduleKey,
            id: match.option.id ?? '',
            label: match.option.label || match.option.record_title || match.option.record_number || '',
            description,
            category: data.category || (isService ? 'Service' : 'Product'),
            amount: data.default_cost ?? '',
            supplier_id: data.supplier_id ?? '',
            tax_type: normalizeFinanceTaxType(data.tax_type ?? ''),
        };
    }

    function setLineItemFieldValue(row, fieldName, value) {
        const input = row.querySelector(`[data-pr-line-item-field="${fieldName}"]`);
        if (!input) return;

        input.value = blank(value) ? '' : String(value);
    }

    function autofillLineItemFromMaster(row, { force = false } = {}) {
        if (!row || (currentModuleKey !== 'pr' && currentModuleKey !== 'po')) return false;

        const itemInput = row.querySelector('[data-pr-line-item-field="item_id"]');
        const defaults = getLineItemMasterDefaults(itemInput?.value || '');

        if (!defaults) return false;

        const autofillKey = `${defaults.moduleKey}:${defaults.id}`;
        if (!force && row.dataset.lastAutofilledMaster === autofillKey) {
            return false;
        }

        row.dataset.lastAutofilledMaster = autofillKey;

        if (itemInput && defaults.label) {
            itemInput.value = defaults.label;
        }

        setLineItemFieldValue(row, 'item_module', defaults.moduleKey);
        setLineItemFieldValue(row, 'item_record_id', defaults.id);
        setLineItemFieldValue(row, 'description', defaults.description);
        setLineItemFieldValue(row, 'category', defaults.category);
        setLineItemFieldValue(row, 'amount', defaults.amount);

        if (defaults.supplier_id) {
            setLineItemFieldValue(row, 'supplier_id', defaults.supplier_id);
        }

        if (defaults.tax_type) {
            setLineItemFieldValue(row, 'tax_type', defaults.tax_type);
        }

        return true;
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
            requester_employee_id: '',
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

    function getEmployeeRequesterOption(employeeId) {
        const targetId = String(employeeId || '').trim();
        if (!targetId) return null;

        return (financeLookupOptions.employee || []).find((option) => {
            return String(option.id ?? option.value ?? '') === targetId
                || String(option.employee_id || '') === targetId
                || String(option.employee_code || '') === targetId;
        }) || null;
    }

    function getEmployeeRequesterDefaults(employeeId) {
        const employee = getEmployeeRequesterOption(employeeId);
        if (!employee) return null;

        const name = employee.full_name || employee.record_title || employee.name || employee.label || '';

        return {
            requester_employee_id: employee.id ?? employee.value ?? '',
            requestor: name,
            employee_name: name,
            employee_email: employee.employee_email || employee.email || '',
            contact_number: employee.contact_number || employee.phone_number || employee.phone || '',
            position: employee.position || '',
            department: employee.department || employee.department_name || '',
            employee_id: employee.employee_id || employee.employee_code || '',
            superior: employee.superior || '',
            superior_email: employee.superior_email || '',
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

    function moduleRequiresDualApproval(moduleKey = currentModuleKey) {
        return requestTypeModules.has(moduleKey);
    }

    function defaultApprovalUserId(index) {
        return String(defaultApprovalSteps[index]?.user_id || '');
    }

    function approverStepValue(record, fieldName, index) {
        return getDraftValue(fieldName, record)
            || record?.data?.approval_steps?.[index]?.user_id
            || defaultApprovalUserId(index)
            || '';
    }

    function renderApprovalSelectionPanel(record, values = {}) {
        if (!moduleRequiresDualApproval()) return '';
        if (currentModuleKey === 'supplier' && isSupplierDispatchLayout(record)) return '';

        const firstApproverValue = approverStepValue(record, 'first_approver_user_id', 0);
        const secondApproverValue = approverStepValue(record, 'second_approver_user_id', 1);
        const roleLabels = getApprovalRoutingRoleLabels(record);
        const employeeApproverOptions = (financeLookupOptions.employee || [])
            .map((option) => ({
                value: String(option.user_id || ''),
                label: option.label || option.user_name || option.full_name || option.record_title || 'Employee Approver',
                source: 'employee',
            }))
            .filter((option) => option.value);
        const approverOptionsSource = currentModuleKey === 'dv'
            ? officialApproverOptions
            : [...officialApproverOptions, ...employeeApproverOptions];
        const approverOptions = approverOptionsSource
            .map((option) => ({
                value: String(option.user_id || ''),
                label: option.label || option.user_name || option.official_name || option.full_name || 'Approver',
            }))
            .filter((option, index, options) => option.value && options.findIndex((candidate) => candidate.value === option.value) === index);

        values.first_approver_user_id = firstApproverValue;
        values['data[first_approver_user_id]'] = firstApproverValue;
        values.second_approver_user_id = secondApproverValue;
        values['data[second_approver_user_id]'] = secondApproverValue;

        return `
            <div class="md:col-span-2 rounded-xl border border-violet-100 bg-violet-50/60 p-4">
                <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-violet-700">Approval Routing</h4>
                <p class="mt-2 text-xs text-gray-600">${currentModuleKey === 'dv'
                    ? 'Disbursement Vouchers require two approvers from the official company officer / corporate record list.'
                    : 'Request-type records require two approvers. Defaults come from the official company officer records, and authorized users may choose approvers from the employee list when needed.'}</p>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${renderDynamicField(selectField('first_approver_user_id', roleLabels[0] || 'First Approver', { required: true, options: approverOptions }), firstApproverValue, values)}
                    ${renderDynamicField(selectField('second_approver_user_id', roleLabels[1] || 'Second Approver', { required: true, options: approverOptions }), secondApproverValue, values)}
                </div>
            </div>
        `;
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
        const requesterEmployeeInput = form.querySelector('select[name="data[requester_employee_id]"]');
        const requesterEmployeeWrapper = form.querySelector('[data-finance-field="requester_employee_id"]');
        const requesterEmployeeId = shouldUseOwnRequest
            ? ''
            : (requesterEmployeeInput?.value || financeFormValues.requester_employee_id || financeFormValues['data[requester_employee_id]'] || '');
        const employeeValues = getEmployeeRequesterDefaults(requesterEmployeeId);

        if (requesterModeInput) {
            requesterModeInput.value = requesterMode;
        }

        if (requesterEmployeeInput) {
            requesterEmployeeInput.value = requesterEmployeeId;
            requesterEmployeeInput.required = !shouldUseOwnRequest;
        }

        if (requesterEmployeeWrapper) {
            requesterEmployeeWrapper.classList.toggle('hidden', shouldUseOwnRequest);
        }

        const syncField = (fieldName, autoValue, { readOnlyWhenOwn = true } = {}) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (!input) return;

            const currentValue = String(input.value || '').trim();
            const employeeValue = employeeValues?.[fieldName] ?? '';
            const nextValue = shouldUseOwnRequest
                ? (preserveExisting && currentValue ? currentValue : (autoValue || ''))
                : (employeeValues
                    ? (preserveExisting && currentValue ? currentValue : employeeValue)
                    : (preserveExisting && currentValue && currentValue !== (autoValue || '') ? currentValue : ''));

            input.value = nextValue;
            if (readOnlyWhenOwn) {
                setReadonlyState(input, (shouldUseOwnRequest && Boolean(autoValue)) || (!shouldUseOwnRequest && Boolean(employeeValues) && Boolean(employeeValue)));
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

        financeFormValues.requester_employee_id = requesterEmployeeId;
        financeFormValues['data[requester_employee_id]'] = requesterEmployeeId;
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

        const requesterEmployeeInput = form.querySelector('[name="data[requester_employee_id]"]');
        if (requesterEmployeeInput) {
            requesterEmployeeInput.value = '';
            financeFormValues.requester_employee_id = '';
            financeFormValues['data[requester_employee_id]'] = '';
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
        const lockSourceFields = currentModuleKey === 'dv';
        const isLocked = Boolean(financeFormLockedReadOnly);
        const sourceFieldAttr = lockSourceFields || isLocked ? 'readonly' : '';
        const sourceFieldClass = lockSourceFields || isLocked ? 'bg-gray-100 cursor-not-allowed' : 'bg-white';

        return `
            <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4" data-dv-line-items-section>
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Breakdown / Line Items</h4>
                    ${isLocked ? '<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">Read-only</span>' : (lockSourceFields ? '<span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Debit/Credit editable</span>' : '<button type="button" onclick="window.financeModule.addDvLineItemRow()" class="rounded-md border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">Add Line</button>')}
                </div>
                <div class="mt-4 space-y-3">
                    ${rows.map((row, index) => `
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 rounded-lg border border-gray-100 bg-slate-50 p-3" data-dv-line-item-row>
                            <div class="md:col-span-5">
                                <label class="block text-xs font-medium text-gray-600">Description</label>
                                <input type="text" name="data[line_items][${index}][description]" data-dv-line-item-field="description" value="${escapeHtml(row.description || '')}" class="mt-1 w-full border rounded-md p-2 ${sourceFieldClass}" ${sourceFieldAttr}>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-gray-600">Account Code</label>
                                <input type="text" name="data[line_items][${index}][account_code]" data-dv-line-item-field="account_code" value="${escapeHtml(row.account_code || '')}" class="mt-1 w-full border rounded-md p-2 ${sourceFieldClass}" ${sourceFieldAttr}>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600">Debit</label>
                                <input type="number" step="0.01" min="0" name="data[line_items][${index}][debit]" data-dv-line-item-field="debit" value="${escapeHtml(row.debit || '')}" class="mt-1 w-full border rounded-md p-2 ${isLocked ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'}" ${isLocked ? 'readonly' : ''}>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600">Credit</label>
                                <input type="number" step="0.01" min="0" name="data[line_items][${index}][credit]" data-dv-line-item-field="credit" value="${escapeHtml(row.credit || '')}" class="mt-1 w-full border rounded-md p-2 ${isLocked ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'}" ${isLocked ? 'readonly' : ''}>
                            </div>
                            <div class="md:col-span-12 ${(lockSourceFields || isLocked) ? 'hidden' : 'flex'} justify-end">
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

    function getPoAutofillValuesFromLinkedRecord(linkedPrRecord) {
        const data = linkedPrRecord?.data || {};
        const supplierCounts = getPoLinkedPrSupplierCounts(linkedPrRecord);

        return {
            supplier_id: data.supplier_id || supplierCounts[0]?.id || '',
            project: data.project || data.project_name || data.project_code || '',
            cost_center: data.cost_center || data.cost_center_code || data.cost_center_name || '',
            coa_id: data.coa_id || '',
            linked_pr_supplier_summary: linkedPrRecord?.record_number || '',
        };
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
        const sourceQuantity = numericAmount(data.current_quantity || data.accepted_quantity || data.beginning_quantity || data.delivered_quantity || data.ordered_quantity || 0);
        const acquisitionCost = numericAmount(data.acquisition_cost || data.grand_total || data.total_amount || data.amount || record?.amount || 0);
        let unitCost = numericAmount(data.unit_cost || 0);
        let averageCost = numericAmount(data.average_cost || 0);
        let lastPurchaseCost = numericAmount(data.last_purchase_cost || 0);

        if (unitCost <= 0 && sourceQuantity > 0 && acquisitionCost > 0) {
            unitCost = acquisitionCost / sourceQuantity;
        }

        if (unitCost <= 0 && averageCost > 0) {
            unitCost = averageCost;
        }

        if (unitCost <= 0 && lastPurchaseCost > 0) {
            unitCost = lastPurchaseCost;
        }

        if (averageCost <= 0 && unitCost > 0) {
            averageCost = unitCost;
        }

        if (lastPurchaseCost <= 0 && unitCost > 0) {
            lastPurchaseCost = unitCost;
        }

        return {
            supplier_id: data.supplier_id || '',
            asset_description: firstLineItem.description || data.asset_description || data.purpose || record?.record_title || '',
            asset_category: firstLineItem.category || data.asset_category || data.linked_item_type || '',
            serial_number: data.serial_number || '',
            model: data.model || '',
            beginning_quantity: data.beginning_quantity || sourceQuantity || '',
            current_quantity: data.current_quantity || data.accepted_quantity || data.beginning_quantity || data.delivered_quantity || data.ordered_quantity || '',
            reserved_quantity: data.reserved_quantity || 0,
            unit_cost: unitCost || acquisitionCost || '',
            acquisition_cost: acquisitionCost || '',
            total_cost: sourceQuantity > 0 && unitCost > 0 ? (sourceQuantity * unitCost) : acquisitionCost || '',
            average_cost: averageCost || unitCost || '',
            last_purchase_cost: lastPurchaseCost || unitCost || '',
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

        ['supplier_id', 'asset_description', 'asset_category', 'serial_number', 'model', 'beginning_quantity', 'current_quantity', 'reserved_quantity', 'unit_cost', 'average_cost', 'last_purchase_cost', 'acquisition_cost', 'total_cost', 'acquisition_date', 'asset_coa_id', 'remarks'].forEach((fieldName) => {
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

    function updateArfCalculatedFields() {
        if (currentModuleKey !== 'arf') return;

        const form = $('financeForm');
        if (!form) return;

        const valueFor = (fieldName) => numericAmount(form.querySelector(`[name="data[${fieldName}]"]`)?.value || 0);
        const setValue = (fieldName, value) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (!input) return;
            input.value = Number.isFinite(value) ? value.toFixed(2) : '';
            financeFormValues[fieldName] = input.value;
            financeFormValues[`data[${fieldName}]`] = input.value;
        };

        const classification = form.querySelector('[name="data[item_classification]"]')?.value || 'Fixed Asset';
        const isFixedAsset = classification === 'Fixed Asset';
        const currentQuantity = valueFor('current_quantity') || valueFor('accepted_quantity') || valueFor('beginning_quantity');
        const reservedQuantity = valueFor('reserved_quantity');
        const acquisitionCost = valueFor('acquisition_cost');
        let unitCost = valueFor('unit_cost');
        const residualValue = valueFor('residual_value');
        const usefulLife = valueFor('useful_life');
        const acquisitionDateValue = String(form.querySelector('[name="data[acquisition_date]"]')?.value || '').trim();

        if (classification === 'Consumable Inventory') {
            const existingAverageCost = valueFor('average_cost');
            const existingLastPurchaseCost = valueFor('last_purchase_cost');

            if (unitCost <= 0 && currentQuantity > 0 && acquisitionCost > 0) {
                unitCost = acquisitionCost / currentQuantity;
            }

            if (unitCost <= 0 && existingAverageCost > 0) {
                unitCost = existingAverageCost;
            }

            if (unitCost <= 0 && existingLastPurchaseCost > 0) {
                unitCost = existingLastPurchaseCost;
            }

            setValue('unit_cost', unitCost);
            setValue('average_cost', unitCost > 0 ? unitCost : existingAverageCost);
            setValue('last_purchase_cost', unitCost > 0 ? unitCost : existingLastPurchaseCost);
        }

        setValue('available_quantity', Math.max(currentQuantity - reservedQuantity, 0));
        setValue('total_cost', currentQuantity * (unitCost > 0 ? unitCost : valueFor('unit_cost')));

        const depreciationFields = ['useful_life', 'residual_value', 'depreciable_amount', 'annual_depreciation', 'monthly_depreciation', 'accumulated_depreciation', 'net_book_value'];
        depreciationFields.forEach((fieldName) => {
            const wrapper = form.querySelector(`[data-finance-field="${fieldName}"]`);
            if (wrapper) {
                wrapper.classList.toggle('hidden', !isFixedAsset);
            }
        });

        form.querySelector('[name="data[useful_life]"]')?.toggleAttribute('required', isFixedAsset);

        if (!isFixedAsset) {
            ['useful_life', 'residual_value'].forEach((fieldName) => {
                const input = form.querySelector(`[name="data[${fieldName}]"]`);
                if (input) {
                    input.value = '';
                    financeFormValues[fieldName] = '';
                    financeFormValues[`data[${fieldName}]`] = '';
                }
            });
            ['depreciable_amount', 'annual_depreciation', 'monthly_depreciation', 'accumulated_depreciation', 'net_book_value'].forEach((fieldName) => setValue(fieldName, 0));
            return;
        }

        const depreciableAmount = Math.max(acquisitionCost - residualValue, 0);
        const annualDepreciation = usefulLife > 0 ? depreciableAmount / usefulLife : 0;
        const monthlyDepreciation = annualDepreciation / 12;
        const acquisitionDate = acquisitionDateValue ? new Date(`${acquisitionDateValue}T00:00:00`) : null;
        const now = new Date();
        const monthsElapsed = acquisitionDate && !Number.isNaN(acquisitionDate.getTime())
            ? Math.max(((now.getFullYear() - acquisitionDate.getFullYear()) * 12) + (now.getMonth() - acquisitionDate.getMonth()), 0)
            : 0;
        const accumulatedDepreciation = Math.min(monthlyDepreciation * monthsElapsed, depreciableAmount);

        setValue('depreciable_amount', depreciableAmount);
        setValue('annual_depreciation', annualDepreciation);
        setValue('monthly_depreciation', monthlyDepreciation);
        setValue('accumulated_depreciation', accumulatedDepreciation);
        setValue('net_book_value', Math.max(acquisitionCost - accumulatedDepreciation, 0));
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

    function syncPdaPayrollPeriod({ preserveExisting = false } = {}) {
        if (currentModuleKey !== 'pda') return;

        const form = $('financeForm');
        if (!form) return;

        const periodSelect = form.querySelector('[name="data[payroll_period_id]"]');
        const selectedPeriod = (financeLookupOptions.payroll_period || [])
            .find((period) => String(period.id) === String(periodSelect?.value || ''));

        const setFieldValue = (fieldName, value) => {
            const input = form.querySelector(`[name="data[${fieldName}]"]`);
            if (!input) return;
            const currentValue = String(input.value || '').trim();
            if (preserveExisting && currentValue) return;
            input.value = blank(value) ? '' : String(value);
            financeFormValues[fieldName] = input.value;
            financeFormValues[`data[${fieldName}]`] = input.value;
        };

        if (!selectedPeriod) {
            ['period_start', 'period_end', 'payroll_start', 'payroll_end', 'pay_date', 'total_payroll_amount', 'employee_count', 'basic_salary_total', 'yearly_basic_total', 'daily_rate_total', 'hourly_rate_total', 'minute_rate_total', 'gross_pay_total', 'benefits_total', 'allowances_total', 'deductions_total', 'night_differential_total', 'holiday_pay_total', 'employee_payroll_breakdown'].forEach((fieldName) => {
                setFieldValue(fieldName, '');
            });
            return;
        }

        setFieldValue('period_start', selectedPeriod.period_start || '');
        setFieldValue('period_end', selectedPeriod.period_end || '');
        setFieldValue('payroll_start', selectedPeriod.payroll_start || '');
        setFieldValue('payroll_end', selectedPeriod.payroll_end || '');
        setFieldValue('pay_date', selectedPeriod.pay_date || '');
        setFieldValue('total_payroll_amount', selectedPeriod.total_payroll_amount || 0);
        setFieldValue('employee_count', selectedPeriod.employee_count || 0);
        setFieldValue('basic_salary_total', selectedPeriod.basic_salary_total || 0);
        setFieldValue('yearly_basic_total', selectedPeriod.yearly_basic_total || 0);
        setFieldValue('daily_rate_total', selectedPeriod.daily_rate_total || 0);
        setFieldValue('hourly_rate_total', selectedPeriod.hourly_rate_total || 0);
        setFieldValue('minute_rate_total', selectedPeriod.minute_rate_total || 0);
        setFieldValue('gross_pay_total', selectedPeriod.gross_pay_total || 0);
        setFieldValue('benefits_total', selectedPeriod.benefits_total || 0);
        setFieldValue('allowances_total', selectedPeriod.allowances_total || 0);
        setFieldValue('deductions_total', selectedPeriod.deductions_total || 0);
        setFieldValue('night_differential_total', selectedPeriod.night_differential_total || 0);
        setFieldValue('holiday_pay_total', selectedPeriod.holiday_pay_total || 0);

        const recordTitleInput = $('recordTitleInput');
        if (recordTitleInput && (!recordTitleInput.value || recordTitleInput.value === getModuleConfig('pda').recordTitleLabel)) {
            recordTitleInput.value = selectedPeriod.record_title || selectedPeriod.label || 'Payroll Period';
        }

        const recordDateInput = $('recordDateInput');
        if (recordDateInput && !recordDateInput.value && selectedPeriod.pay_date) {
            recordDateInput.value = selectedPeriod.pay_date;
        }

        const summaryInput = form.querySelector('[name="data[supporting_payroll_summary]"]');
        if (summaryInput && (!preserveExisting || blank(summaryInput.value))) {
            summaryInput.value = `${selectedPeriod.employee_count || 0} payroll summaries. Basic: ${formatCurrency(selectedPeriod.basic_salary_total || 0)}; Gross: ${formatCurrency(selectedPeriod.gross_pay_total || 0)}; Benefits: ${formatCurrency(selectedPeriod.benefits_total || 0)}; Allowances: ${formatCurrency(selectedPeriod.allowances_total || 0)}; Deductions: ${formatCurrency(selectedPeriod.deductions_total || 0)}; Night Differential: ${formatCurrency(selectedPeriod.night_differential_total || 0)}; Holiday Pay: ${formatCurrency(selectedPeriod.holiday_pay_total || 0)}; Net Payroll: ${formatCurrency(selectedPeriod.total_payroll_amount || 0)}.`;
            financeFormValues.supporting_payroll_summary = summaryInput.value;
            financeFormValues['data[supporting_payroll_summary]'] = summaryInput.value;
        }

        const breakdownInput = form.querySelector('[name="data[employee_payroll_breakdown]"]');
        if (breakdownInput && (!preserveExisting || blank(breakdownInput.value))) {
            const employeeLines = Array.isArray(selectedPeriod.employee_lines) ? selectedPeriod.employee_lines : [];
            breakdownInput.value = employeeLines.length
                ? employeeLines.map((line, index) => `${index + 1}. ${line.employee_name || line.employee_code || 'Employee'} | ${line.salary_grade || 'No grade'} / ${line.payroll_level || 'No level'} | Basic ${formatCurrency(line.monthly_basic_salary || 0)} | Gross ${formatCurrency(line.gross_pay || 0)} | Deduct ${formatCurrency(line.total_deductions || 0)} | Net ${formatCurrency(line.net_pay || 0)}`).join('\n')
                : 'No employee payroll profiles or summaries found for this period.';
            financeFormValues.employee_payroll_breakdown = breakdownInput.value;
            financeFormValues['data[employee_payroll_breakdown]'] = breakdownInput.value;
        }
    }

    function syncPrRequesterFields({ preserveExisting = false } = {}) {
        if (currentModuleKey !== 'pr') return;
        syncRequestOwnershipFields({ preserveExisting });
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

    function syncCashAdvanceHiddenRequestor() {
        if (currentModuleKey !== 'ca') return;

        const form = $('financeForm');
        if (!form) return;

        const requestorInput = form.querySelector('input[name="data[requestor]"]');
        if (!requestorInput) return;

        const employeeName = form.querySelector('[name="data[employee_name]"]')?.value || '';
        const fallbackName = getPrRequesterDefaults().requestor || bootstrap.currentUserName || '';
        const nextValue = employeeName || requestorInput.value || fallbackName;

        requestorInput.value = nextValue;
        financeFormValues.requestor = nextValue;
        financeFormValues['data[requestor]'] = nextValue;
    }

    function updateCashAdvanceReleaseValues(changedFieldName = '') {
        if (currentModuleKey !== 'ca') return;

        const form = $('financeForm');
        if (!form) return;

        syncCashAdvanceHiddenRequestor();

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

        if (changedFieldName === 'amount_requested' || changedFieldName === 'release_schedule' || changedFieldName === 'release_count' || changedFieldName === 'cash_release_date') {
            refreshCashAdvancePaymentTracker();
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
        const serviceLookup = financeLookupOptions.service || [];

        return [
            ...productLookup.map((option) => option.label || option.value || ''),
            ...serviceLookup.map((option) => option.label || option.value || ''),
        ].filter(Boolean);
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
        if (fieldName === 'first_approver_user_id') {
            return getApproverRoutingDisplayValue(record, 0);
        }

        if (fieldName === 'second_approver_user_id') {
            return getApproverRoutingDisplayValue(record, 1);
        }

        const field = (moduleConfig.fields || []).find((item) => item.name === fieldName);
        const rawValue = getFieldValue(record, fieldName);
        if (!field) {
            return rawValue;
        }

        return getFormDisplayValue(field, rawValue, record.data || {});
    }

    function financePreviewHasValue(value) {
        if (value === null || value === undefined) {
            return false;
        }

        if (Array.isArray(value)) {
            return value.some((item) => financePreviewHasValue(item));
        }

        if (typeof value === 'object') {
            return Object.values(value).some((item) => financePreviewHasValue(item));
        }

        return String(value).trim() !== '' && String(value).trim() !== 'N/A';
    }

    function getFinancePreviewSummaryRows(record) {
        const moduleConfig = getModuleConfig(record.module_key);
        const data = record.data || {};
        const roleLabels = getApprovalRoutingRoleLabels(record);
        if (record.module_key === 'pr') {
            return [
                ['Request Number', record.record_number || ''],
                ['Requestor', data.requestor || data.employee_name || ''],
                ['Priority', data.priority || ''],
                ['Date Needed', data.needed_date || ''],
                ['Amount', record.amount ? formatCurrency(record.amount) : ''],
                ['Record Date', record.record_date || ''],
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Submitted By', record.user || ''],
                ['Submitted At', record.submitted_at || ''],
                ['Approved At', record.approved_at || ''],
            ];
        }

        if (record.module_key === 'err') {
            return [
                ['Record Number', record.record_number || ''],
                ['Requestor', data.requestor || data.employee_name || ''],
                ['Linked LR', getLookupLabel('lr', data.linked_lr_id) || data.linked_lr_id || ''],
                ['Reimbursement Mode', data.reimbursement_mode || ''],
                ['Amount', record.amount ? formatCurrency(record.amount) : ''],
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Submitted By', record.user || ''],
                ['Submitted At', record.submitted_at || ''],
                ['Approved At', record.approved_at || ''],
            ];
        }

        const rows = [
            ['Record Number', record.record_number || ''],
            [moduleConfig.recordTitleLabel || 'Name', getVisibleRecordTitle(record)],
            ['Record Date', record.record_date || ''],
            ['Status', record.status || ''],
            ['Created By', record.user || ''],
            ['Submitted At', record.submitted_at || ''],
            ['Approved At', record.approved_at || ''],
        ];

        if (shouldShowGenericAmount(record)) {
            rows.splice(3, 0, ['Amount', record.amount ? formatCurrency(record.amount) : '']);
        }

        const moduleSpecificRows = {
            supplier: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Completion Mode', data.completion_mode === 'send_to_supplier' ? 'Send to Supplier' : 'Complete Internally'],
                ['Approver 1', getApproverRoutingDisplayValue(record, 0)],
                ['Approver 2', getApproverRoutingDisplayValue(record, 1)],
                ['Supplier Completion', record.supplier_completed_at ? formatDate(record.supplier_completed_at) : 'Pending'],
            ],
            service: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Service Description', data.service_description || ''],
                ['Supplier', getLookupLabel('supplier', data.supplier_id) || ''],
                ['Chart of Account', getLookupLabel('chart_account', data.coa_id) || ''],
                ['Category', data.category || ''],
                ['Unit of Measure', data.unit_of_measure || ''],
                ['Default Cost', data.default_cost ? formatCurrency(data.default_cost) : ''],
                ['Status', data.service_status || ''],
            ],
            product: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Product Description', data.product_description || ''],
                ['Supplier', getLookupLabel('supplier', data.supplier_id) || ''],
                ['Chart of Account', getLookupLabel('chart_account', data.coa_id) || ''],
                ['Category', data.category || ''],
                ['Unit of Measure', data.unit_of_measure || ''],
                ['Default Cost', data.default_cost ? formatCurrency(data.default_cost) : ''],
                ['Status', data.product_status || ''],
            ],
            chart_account: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Account Description', data.account_description || ''],
                ['Main Account', data.is_sub_account ? getLookupLabel('chart_account', data.parent_account_id) || '' : ''],
                ['Account Type', data.account_type || ''],
                ['Account Group', data.account_group || ''],
                ['Normal Balance', data.normal_balance || ''],
                ['Account Status', data.account_status || ''],
            ],
            bank_account: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Bank Name', data.bank_name || ''],
                ['Branch', data.branch || ''],
                ['Currency', data.currency || ''],
                ['Account Type', data.account_type || ''],
                ['Linked Chart of Account', getLookupLabel('chart_account', data.linked_coa_id) || ''],
                ['Bank Status', data.bank_status || ''],
            ],
            pr: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Priority', data.priority || ''],
                ['Needed Date', data.needed_date || ''],
                ['Requester Option', data.requester_mode || ''],
                ['Linked PO', getLookupLabel('po', data.linked_po_id) || ''],
                ['Linked DV', getLookupLabel('dv', data.linked_dv_id) || ''],
                ['For Client', data.for_client || ''],
                ['Purpose', data.purpose || ''],
            ],
            po: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Linked PR', getLookupLabel('pr', data.linked_pr_id) || ''],
                ['Supplier', getLookupLabel('supplier', data.supplier_id) || ''],
                ['Expected Delivery Date', data.expected_delivery_date || ''],
                ['Delivery Address', data.delivery_address || ''],
                ['Terms and Conditions', data.terms_and_conditions || ''],
            ],
            ca: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                [roleLabels[0] || 'President', getApproverRoutingDisplayValue(record, 0)],
                [roleLabels[1] || 'Treasurer', getApproverRoutingDisplayValue(record, 1)],
                ['Requester Option', data.requester_mode || ''],
                ['Requested By', data.requestor || getLookupLabel('employee', data.requester_employee_id) || ''],
                ['Linked DV', getLookupLabel('dv', data.linked_dv_id) || ''],
                ['Linked LR', getLookupLabel('lr', data.linked_lr_id) || ''],
                ['Linked CRF', getLookupLabel('crf', data.linked_crf_id) || ''],
                ['Department', data.department || ''],
                ['Purpose', data.purpose || ''],
                ['Amount Requested', data.amount_requested ? formatCurrency(data.amount_requested) : ''],
                ['Release Schedule', data.release_schedule || ''],
                ['Number of Releases', data.release_count || ''],
                ['Amount per Release', data.amount_per_release ? formatCurrency(data.amount_per_release) : ''],
                ['Paid Through', getLookupLabel('bank_account', data.paid_through) || data.paid_through || ''],
            ],
            lr: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Requester Option', data.requester_mode || ''],
                ['Linked CA', getLookupLabel('ca', data.linked_ca_id) || ''],
                ['Linked DV', getLookupLabel('dv', data.linked_dv_id) || ''],
                ['Linked CRF', getLookupLabel('crf', data.linked_crf_id) || ''],
                ['Total Cash Advance', data.total_cash_advance ? formatCurrency(data.total_cash_advance) : ''],
                ['Attachments', getAttachmentSummaryValue(record)],
                ['Purpose', data.purpose || ''],
            ],
            err: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Requester Option', data.requester_mode || ''],
                ['Requested By', data.requestor || getLookupLabel('employee', data.requester_employee_id) || ''],
                ['Linked LR', getLookupLabel('lr', data.linked_lr_id) || ''],
                ['Linked DV', getLookupLabel('dv', data.linked_dv_id) || ''],
                ['Amount', data.amount ? formatCurrency(data.amount) : ''],
                ['Reimbursement Mode', data.reimbursement_mode || ''],
            ],
            dv: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Source Document Type', data.source_document_type || ''],
                ['Source Document', getLookupLabel(data.source_document_type || '', data.source_document_id) || ''],
                ['Linked PO', getLookupLabel('po', data.linked_po_id) || ''],
                ['Linked CA', getLookupLabel('ca', data.linked_ca_id) || ''],
                ['Linked LR', getLookupLabel('lr', data.linked_lr_id) || ''],
                ['Linked CRF', getLookupLabel('crf', data.linked_crf_id) || ''],
                ['Supplier', getLookupLabel('supplier', data.supplier_id) || ''],
                ['Amount', data.amount ? formatCurrency(data.amount) : ''],
                ['Payment Type', data.payment_type || ''],
                ['Disbursement Type', data.disbursement_type || ''],
            ],
            pda: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                [roleLabels[0] || 'President', getApproverRoutingDisplayValue(record, 0)],
                [roleLabels[1] || 'Treasurer', getApproverRoutingDisplayValue(record, 1)],
                ['Payroll Period', getLookupLabel('payroll_period', data.payroll_period_id) || ''],
                ['Linked DV', getLookupLabel('dv', data.linked_dv_id) || ''],
                ['Pay Date', data.pay_date || ''],
                ['Employee Count', data.employee_count || ''],
                ['Total Payroll Amount', data.total_payroll_amount ? formatCurrency(data.total_payroll_amount) : ''],
            ],
            crf: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Requester Option', data.requester_mode || ''],
                ['Requested By', data.requestor || getLookupLabel('employee', data.requester_employee_id) || ''],
                ['Linked LR', getLookupLabel('lr', data.linked_lr_id) || ''],
                ['Linked DV', getLookupLabel('dv', data.linked_dv_id) || ''],
                ['Amount Returned', data.amount_returned ? formatCurrency(data.amount_returned) : ''],
                ['Mode of Return', data.mode_of_return || ''],
            ],
            ibtf: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Source Bank Account', getLookupLabel('bank_account', data.source_bank_account_id) || ''],
                ['Destination Bank Account', getLookupLabel('bank_account', data.destination_bank_account_id) || ''],
                ['Linked DV', getLookupLabel('dv', data.linked_dv_id) || ''],
                ['Amount', data.amount ? formatCurrency(data.amount) : ''],
                ['Reason', data.reason || ''],
            ],
            arf: [
                ['Workflow', record.workflow_status || ''],
                ['Approval', previewApprovalLabel(record) || ''],
                ['Linked PO', getLookupLabel('po', data.linked_po_id) || ''],
                ['Linked DV', getLookupLabel('dv', data.linked_dv_id) || ''],
                ['Supplier', getLookupLabel('supplier', data.supplier_id) || ''],
                ['Asset Code', data.asset_code || ''],
                ['Custodian', getLookupLabel('employee', data.custodian) || data.custodian_name || ''],
                ['Asset Status', data.asset_status || ''],
                ['Last Event', data.asset_last_event || ''],
                ['Asset Description', data.asset_description || ''],
                ['Serial Number', data.serial_number || ''],
                ['Location', data.location || ''],
            ],
        };

        rows.push(...(moduleSpecificRows[record.module_key] || [
            ['Workflow', record.workflow_status || ''],
            ['Approval', previewApprovalLabel(record) || ''],
        ]));

        return rows.filter(([, value]) => financePreviewHasValue(value));
    }

    function renderPreviewSectionTable(record, moduleConfig, title, fieldNames) {
        const entries = fieldNames.map((fieldName) => {
            const field = (moduleConfig.fields || []).find((item) => item.name === fieldName);
            if (!field) {
                return null;
            }

            const value = getPreviewFieldValue(record, fieldName, moduleConfig);
            if (!financePreviewHasValue(value)) {
                return null;
            }

            return {
                label: field.label,
                value,
            };
        }).filter(Boolean);

        if (!entries.length) {
            return '';
        }

        return `
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                ${title ? `
                    <div class="border-b border-gray-100 bg-slate-50 px-4 py-3">
                        <h4 class="text-[15px] font-semibold text-gray-900">${escapeHtml(title)}</h4>
                    </div>
                ` : ''}
                <div class="p-4">
                    <table class="w-full border-collapse">
                        ${chunkArray(entries, 2).map((row) => `
                            <tr class="align-top">
                                ${row.map((entry) => `
                                    <td class="w-1/2 border-b border-gray-100 pb-3 ${row.length === 1 ? 'pr-0' : 'pr-3'}">
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">${escapeHtml(entry.label)}</p>
                                        <p class="mt-1 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(entry.value)}</p>
                                    </td>
                                `).join('')}
                                ${Array.from({ length: 2 - row.length }).map(() => '<td class="w-1/2 border-b border-gray-100 pb-3"></td>').join('')}
                            </tr>
                        `).join('')}
                    </table>
                </div>
            </div>
        `;
    }

    function renderPdaPayrollPeriodCard(record) {
        const data = record.data || {};
        const payrollPeriodLabel = getLookupLabel('payroll_period', data.payroll_period_id) || record.record_title || 'Payroll Period';
        const periodStart = formatDate(data.period_start || '');
        const periodEnd = formatDate(data.period_end || '');
        const payrollStart = formatDate(data.payroll_start || '');
        const payrollEnd = formatDate(data.payroll_end || '');
        const payDate = formatDate(data.pay_date || '');

        return `
            <div class="overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white shadow-sm">
                <div class="border-b border-blue-100 bg-white/80 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.18em] text-blue-700">Payroll Period</p>
                            <h4 class="mt-1 text-[15px] font-semibold text-gray-900">${escapeHtml(payrollPeriodLabel)}</h4>
                        </div>
                        <span class="rounded-full border border-blue-200 bg-blue-100 px-3 py-1 text-[11px] font-semibold text-blue-700">PDA</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="rounded-xl border border-blue-100 bg-white px-4 py-3 shadow-sm">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-gray-500">Period Start</p>
                            <p class="mt-2 text-[20px] font-semibold leading-none text-gray-900">${escapeHtml(periodStart)}</p>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white px-4 py-3 shadow-sm">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-gray-500">Period End</p>
                            <p class="mt-2 text-[20px] font-semibold leading-none text-gray-900">${escapeHtml(periodEnd)}</p>
                        </div>
                    </div>
                    <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <p class="text-[10px] uppercase tracking-[0.18em] text-gray-500">Payroll Start</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(payrollStart)}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-[0.18em] text-gray-500">Payroll End</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(payrollEnd)}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-[0.18em] text-gray-500">Pay Date</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(payDate)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPdaPayrollPeriodSourceHtml(record) {
        const data = record.data || {};
        const payrollPeriodLabel = getLookupLabel('payroll_period', data.payroll_period_id) || record.record_title || 'Payroll Period';
        const periodStart = formatDate(data.period_start || '');
        const periodEnd = formatDate(data.period_end || '');
        const payrollStart = formatDate(data.payroll_start || '');
        const payrollEnd = formatDate(data.payroll_end || '');
        const payDate = formatDate(data.pay_date || '');

        return `
            <div class="finance-preview-box">
                <div class="finance-preview-section-title">Payroll Period</div>
                <div class="finance-preview-inner">
                    <div style="border:1px solid #bfdbfe;border-radius:12px;background:linear-gradient(135deg,#eff6ff 0%,#ffffff 100%);overflow:hidden;">
                        <div style="padding:10px 12px;border-bottom:1px solid #dbeafe;background:rgba(255,255,255,.8);">
                            <p class="finance-preview-label" style="color:#2563eb;">Payroll Period</p>
                            <p class="finance-preview-value" style="font-size:15px;">${escapeHtml(payrollPeriodLabel)}</p>
                        </div>
                        <div style="padding:12px;">
                            <table class="finance-preview-details" style="border:none;">
                                <tr>
                                    <td style="width:50%;border:1px solid #dbe2ea;border-radius:10px;background:#fff;">
                                        <p class="finance-preview-label">Period Start</p>
                                        <p class="finance-preview-value" style="font-size:16px;">${escapeHtml(periodStart)}</p>
                                    </td>
                                    <td style="width:50%;border:1px solid #dbe2ea;border-radius:10px;background:#fff;">
                                        <p class="finance-preview-label">Period End</p>
                                        <p class="finance-preview-value" style="font-size:16px;">${escapeHtml(periodEnd)}</p>
                                    </td>
                                </tr>
                            </table>
                            <table class="finance-preview-details" style="margin-top:10px;">
                                <tr>
                                    <td>
                                        <p class="finance-preview-label">Payroll Start</p>
                                        <p class="finance-preview-value">${escapeHtml(payrollStart)}</p>
                                    </td>
                                    <td>
                                        <p class="finance-preview-label">Payroll End</p>
                                        <p class="finance-preview-value">${escapeHtml(payrollEnd)}</p>
                                    </td>
                                    <td>
                                        <p class="finance-preview-label">Pay Date</p>
                                        <p class="finance-preview-value">${escapeHtml(payDate)}</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPreviewRowsCard(title, rows, options = {}) {
        const filteredRows = (rows || [])
            .filter((row) => Array.isArray(row) && row.length >= 2)
            .filter(([, value]) => financePreviewHasValue(value));

        if (!filteredRows.length) {
            return '';
        }

        const wrapperClass = options.wrapperClass || 'overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm';
        const titleClass = options.titleClass || 'text-[15px] font-semibold text-gray-900';
        const bodyClass = options.bodyClass || 'p-4 space-y-4';
        const dividerClass = options.dividerClass || 'border-b border-gray-100';

        return `
            <div class="${wrapperClass}">
                ${title ? `
                    <div class="border-b border-gray-100 bg-slate-50 px-4 py-3">
                        <h4 class="${titleClass}">${escapeHtml(title)}</h4>
                    </div>
                ` : ''}
                <div class="${bodyClass}">
                    ${filteredRows.map(([label, value]) => `
                        <div class="space-y-1 ${dividerClass} pb-3 last:border-b-0 last:pb-0">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">${escapeHtml(label)}</p>
                            <p class="text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(value)}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderNextActionCallout(record, section = {}) {
        const data = record?.data || {};
        const nextAction = section.next_action || data.next_action || 'Continue workflow';
        const relationshipStatus = section.relationship_status || record?.relationship_status || data.relationship_status || 'In Progress';
        const description = section.description || 'This record is ready for the next step in the finance workflow.';

        return `
            <div class="overflow-hidden rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-cyan-50 shadow-sm">
                <div class="border-b border-indigo-100 bg-white/80 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.18em] text-indigo-700">Next Action</p>
                            <h4 class="mt-1 text-[16px] font-semibold text-gray-900">${escapeHtml(nextAction)}</h4>
                        </div>
                        <span class="rounded-full border border-indigo-200 bg-indigo-100 px-3 py-1 text-[11px] font-semibold text-indigo-700">${escapeHtml(relationshipStatus)}</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="rounded-xl border border-indigo-100 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[10px] uppercase tracking-[0.18em] text-gray-500">Status</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(relationshipStatus)}</p>
                        <p class="mt-2 text-sm text-gray-600">${escapeHtml(description)}</p>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPreviewSectionCard(record, moduleConfig, section) {
        if (!section) {
            return '';
        }

        if (typeof section.renderer === 'function') {
            return section.renderer();
        }

        if (section.type === 'history') {
            return renderFinanceHistoryCards(record);
        }

        if (section.type === 'attachments') {
            return renderFinanceAttachmentCards(record);
        }

        if (section.type === 'asset_tag') {
            return `
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 bg-slate-50 px-4 py-3">
                        <h4 class="text-[15px] font-semibold text-gray-900">${escapeHtml(section.title || 'Asset Tag')}</h4>
                    </div>
                    <div class="p-4">
                        ${renderArfAssetTagCard(section.assetCode, section.location, section.serialNumber, section.barcodeSvg, { withPrintButton: true })}
                    </div>
                </div>
            `;
        }

        if (section.type === 'next_action_callout') {
            return renderNextActionCallout(record, section);
        }

        if (section.type === 'pda_payroll_period') {
            return renderPdaPayrollPeriodCard(record);
        }

        if (section.type === 'notes') {
            return '';
        }

        return renderPreviewSectionTable(record, moduleConfig, section.title, section.fieldNames || []);
    }

    function getFinanceNoteVisibilityLabel(value) {
        const normalized = String(value || 'all').trim().toLowerCase();
        const option = financeNoteVisibilityOptions.find((entry) => String(entry.value || '').trim().toLowerCase() === normalized);
        return option ? option.label : 'Finance Viewers';
    }

    function getFinanceVisibleNotes(record) {
        const visible = Array.isArray(record?.visible_finance_notes) ? record.visible_finance_notes.slice() : [];
        if (visible.length) return visible;

        const fallbackNote = String(record?.review_note || '').trim();
        if (!fallbackNote) return [];

        return [{
            id: 'workflow-note',
            note: fallbackNote,
            visibility: 'all',
            visibility_label: 'Workflow Note',
            author_name: record?.approved_by || record?.submitted_by || record?.user || 'Finance Team',
            author_email: '',
            created_at: record?.approved_at || record?.submitted_at || record?.updated_at || '',
            source: 'workflow',
        }];
    }

    function canAddFinanceNote(record) {
        return Boolean(record?.can_add_note || record?.can_edit || record?.can_review || record?.can_approve || record?.can_hold || record?.can_revert || record?.can_archive || record?.can_request_delete);
    }

    function renderFinanceNotesCard(record, options = {}) {
        const context = options.context || 'details';
        const notes = getFinanceVisibleNotes(record);
        const canAddNote = canAddFinanceNote(record);
        const emptyMessage = context === 'template'
            ? 'No template notes have been added yet.'
            : 'No notes are visible to you yet.';

        return `
            <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-[15px] font-semibold text-gray-900">Notes</h4>
                        <p class="mt-1 text-xs text-gray-500">Add a note and choose which authority can see it.</p>
                    </div>
                    ${canAddNote ? `
                        <button
                            type="button"
                            onclick="window.financeModule.openFinanceNoteDialog(${record.id})"
                            class="rounded-full border border-amber-200 bg-white px-4 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50">
                            Add Note
                        </button>
                    ` : ''}
                </div>
                <div class="mt-4 space-y-3">
                    ${notes.length ? notes.map((note) => `
                        <div class="rounded-xl border border-amber-100 bg-white px-4 py-3 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3 text-[11px] text-gray-500">
                                <div class="font-semibold text-gray-800">${escapeHtml(note.author_name || 'Finance Team')}</div>
                                <div>${escapeHtml(note.created_at || '')}</div>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-[0.18em] text-amber-700">
                                <span>${escapeHtml(note.source === 'workflow' ? 'Workflow Note' : 'Internal Note')}</span>
                                <span class="rounded-full bg-amber-100 px-2 py-1 tracking-normal text-amber-700">${escapeHtml(note.visibility_label || getFinanceNoteVisibilityLabel(note.visibility))}</span>
                            </div>
                            <div class="mt-2 whitespace-pre-line text-sm text-gray-900">${escapeHtml(note.note || '')}</div>
                        </div>
                    `).join('') : `
                        <div class="rounded-xl border border-dashed border-amber-200 bg-white px-4 py-5 text-sm text-gray-500">
                            ${escapeHtml(emptyMessage)}
                        </div>
                    `}
                </div>
            </div>
        `;
    }

    function renderFinanceReviewNotesSection(record) {
        return renderFinanceNotesCard(record, { context: 'details' });
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
        const filterSupplierPreviewFields = (fieldNames) => fieldNames.filter((fieldName) => shouldShowSupplierPreviewField(fieldName, data));
        const filterConditionalPreviewFields = (fieldNames) => fieldNames.filter((fieldName) => shouldShowSpecifyOtherField(fieldName, data) || shouldShowSupplierPreviewField(fieldName, data));

        switch (record.module_key) {
            case 'supplier':
                if (supplierIsPending || isPendingSupplierCompletion(record)) {
                    return [];
                }

                return [
                    { title: 'Supplier Profile', fieldNames: filterSupplierPreviewFields(['completion_mode', 'date_accomplished', 'trade_name', 'entity_type', 'entity_type_other', 'corporation_type', 'registration_number', 'tin']) },
                    { title: 'Business Details', fieldNames: filterSupplierPreviewFields(['vat_status', 'business_permit_number', 'permit_expiry_date', 'nature_of_business', 'products_services_offered', 'supplier_category', 'supplier_category_other', 'years_in_operation']) },
                    { title: 'Addresses & Contacts', fieldNames: ['registered_address', 'office_address', 'warehouse_address', 'telephone_number', 'mobile_number', 'email_address', 'website_social_media'] },
                    { title: 'Authorized Representative', fieldNames: ['representative_full_name', 'designation', 'phone_number', 'representative_email_address'] },
                    { title: 'Billing & Payment', fieldNames: filterSupplierPreviewFields(['billing_address', 'accounting_contact_person', 'accounting_contact_number', 'accounting_email_address', 'payment_terms', 'payment_terms_other', 'preferred_payment_method', 'preferred_payment_method_other', 'online_payment_details', 'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number', 'swift_code']) },
                    { title: 'Acknowledgment', fieldNames: filterSupplierPreviewFields(['person_accomplishing_full_name', 'person_accomplishing_position', 'id_type', 'id_type_other', 'id_number', 'date_signed']) },
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
                    { title: 'Request Overview', fieldNames: ['record_number', 'record_title', 'requestor', 'priority', 'needed_date', 'amount', 'record_date', 'workflow_status', 'approval_status'] },
                    { title: 'Connected Records', fieldNames: ['linked_po_id', 'linked_dv_id'] },
                    { title: 'Request Details', fieldNames: ['requester_mode', 'requester_employee_id', 'for_client', 'pr_reason_categories', 'request_type'] },
                    { title: 'Requester Details', fieldNames: ['requestor', 'employee_name', 'employee_id', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'] },
                    { title: 'Items / Cost Details', renderer: () => renderPrPreviewTable(record) },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'po':
                return [
                    { title: 'Order Details', fieldNames: ['linked_pr_id', 'supplier_id', 'project', 'cost_center', 'linked_item_type', 'linked_item_id', 'quantity', 'unit_cost', 'total_amount', 'coa_id', 'expected_delivery_date', 'delivery_address', 'terms_and_conditions', 'remarks'] },
                    { title: 'Items / Cost Details', renderer: () => renderPrPreviewTable(record) },
                ];
            case 'ca':
                return [
                    { title: 'Request Details', fieldNames: filterConditionalPreviewFields(['requester_mode', 'requester_employee_id', 'employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email', 'needed_date', 'priority', 'cash_advance_type', 'other_business_purpose_specify', 'usage_categories', 'other_expense_specify', 'purpose', 'for_client', 'client_names', 'amount_requested', 'release_schedule', 'release_count', 'amount_per_release', 'cash_release_date', 'cash_release_time', 'mode_of_release', 'paid_through', 'remarks']) },
                    { title: 'Declarations & Authorizations', fieldNames: ['official_business_cash_advance', 'employee_cash_advance_personal', 'liquidation_non_compliance', 'automatic_salary_deduction_authorization', 'final_pay_deduction_authorization', 'policy_acknowledgment'] },
                ];
            case 'lr':
                return [
                    { title: 'Liquidation Details', fieldNames: ['requester_mode', 'requester_employee_id', 'linked_ca_id', 'total_cash_advance', 'purpose', 'employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email', 'for_client', 'client_names', 'actual_expenses'] },
                ];
            case 'err':
                const errPaymentFieldNames = {
                    Cash: ['cash_receiver_name'],
                    'Bank Transfer': ['recipient_bank_account', 'recipient_bank_number'],
                    Check: ['bank_account_id'],
                }[data.reimbursement_mode] || [];

                return [
                    { title: 'Reimbursement Details', fieldNames: ['requester_mode', 'requester_employee_id', 'requestor', 'linked_lr_id', 'expense_details', 'amount', 'reimbursement_payment_details', 'manual_liquidation_entry', 'reimbursement_mode', ...errPaymentFieldNames, 'remarks'] },
                ];
            case 'dv':
                return [
                    { title: 'Voucher Details', fieldNames: ['source_document_type', 'source_document_id', 'payee_type', 'payee_name', 'supplier_id', 'amount', 'payment_type', 'disbursement_type'] },
                    { title: 'Breakdown / Line Items', renderer: () => renderDvPreviewLineItems(record) },
                    { title: 'Funding & Notes', fieldNames: ['bank_account_id', 'coa_id', 'fund_source', 'department', 'reference_number', 'purpose', 'remarks'] },
                    { title: 'Tax & Receipt', fieldNames: ['withholding_tax', 'vat_amount', 'net_amount', 'currency', 'exchange_rate', 'received_by_name', 'received_by_signature', 'date_received'] },
                ];
            case 'pda':
                return [
                    { title: 'Payroll Details', fieldNames: ['payroll_period_id', 'period_start', 'period_end', 'payroll_start', 'payroll_end', 'pay_date', 'total_payroll_amount', 'employee_count', 'basic_salary_total', 'yearly_basic_total', 'daily_rate_total', 'hourly_rate_total', 'minute_rate_total', 'gross_pay_total', 'benefits_total', 'allowances_total', 'deductions_total', 'night_differential_total', 'holiday_pay_total', 'department', 'funding_bank_account_id', 'payroll_expense_coa_id', 'supporting_payroll_summary', 'employee_payroll_breakdown', 'remarks'] },
                ];
            case 'crf':
                return [
                    { title: 'Return Details', fieldNames: ['requester_mode', 'requester_employee_id', 'requestor', 'linked_lr_id', 'amount_returned', 'mode_of_return', 'receiving_bank_account_id', 'coa_id'] },
                    { title: 'Connected Records', fieldNames: ['linked_lr_id', 'linked_dv_id'] },
                    { title: 'Requester Details', fieldNames: ['requestor', 'employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'] },
                    { type: 'attachments', title: 'Attachments' },
                    { type: 'history' },
                    { title: 'Reference & Notes', fieldNames: ['reference_number', 'remarks'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
            case 'ibtf':
                return [
                    { title: 'Transfer Details', fieldNames: ['source_bank_account_id', 'destination_bank_account_id', 'amount', 'reason', 'source_account_code', 'destination_account_code', 'transfer_reference_number', 'remarks'] },
                ];
            case 'arf': {
                const isConsumableInventory = String(data.item_classification || '').toLowerCase() === 'consumable inventory';
                return [
                    { title: 'Asset Details', fieldNames: ['item_classification', 'linked_po_id', 'linked_dv_id', 'supplier_id', 'asset_code', 'asset_description', 'asset_category', 'serial_number', 'model'] },
                    { title: 'Inventory & Receiving', fieldNames: ['goods_receiving_reference', 'ordered_quantity', 'delivered_quantity', 'accepted_quantity', 'rejected_quantity', 'unit_of_measure', 'beginning_quantity', 'current_quantity', 'reserved_quantity', 'available_quantity', 'reorder_level', 'minimum_stock_level', 'maximum_stock_level', 'safety_stock_level', 'unit_cost', 'total_cost', 'average_cost', 'last_purchase_cost'] },
                    ...(isConsumableInventory
                        ? [{ title: 'Inventory Costing & Custody', fieldNames: ['acquisition_cost', 'acquisition_date', 'asset_coa_id', 'location', 'department', 'custodian', 'movement_history_note', 'remarks'] }]
                        : [{ title: 'Valuation & Custody', fieldNames: ['acquisition_cost', 'acquisition_date', 'asset_coa_id', 'location', 'department', 'custodian', 'useful_life', 'residual_value', 'movement_history_note', 'remarks'] }]),
                ];
            }
            default:
                return [
                    { title: 'Record Details', fieldNames: ['status'] },
                    { type: 'notes', renderer: () => renderFinanceReviewNotesSection(record) },
                ];
        }
    }

    function getTemplatePreviewSections(record) {
        const data = record.data || {};
        const filterSupplierPreviewFields = (fieldNames) => fieldNames.filter((fieldName) => shouldShowSupplierPreviewField(fieldName, data));

        if (record.module_key === 'pr') {
            return [
                { title: 'Request Details', fieldNames: ['request_type', 'priority', 'needed_date', 'for_client', 'pr_reason_categories'] },
                { title: 'Requester Details', fieldNames: ['requester_mode', 'requester_employee_id', 'requestor', 'employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'] },
                { title: 'Vendor / Supplier Details', fieldNames: ['supplier_id', 'new_vendor', 'vendor_id_number', 'vendors_tin', 'company_name', 'vendor_address', 'city', 'province', 'zip', 'vendor_phone', 'vendor_email'] },
                { title: 'Project Allocation', fieldNames: ['project', 'cost_center'] },
                { title: 'Items / Cost Details', renderer: () => renderPrPreviewTable(record) },
                { title: 'Purpose & Notes', fieldNames: ['coa_id', 'purpose', 'remarks'] },
            ];
        }

        if (record.module_key === 'supplier') {
            return [
                { title: 'Supplier Profile', fieldNames: filterSupplierPreviewFields(['completion_mode', 'date_accomplished', 'trade_name', 'entity_type', 'entity_type_other', 'corporation_type', 'registration_number', 'tin']) },
                { title: 'Business Details', fieldNames: filterSupplierPreviewFields(['vat_status', 'business_permit_number', 'permit_expiry_date', 'nature_of_business', 'products_services_offered', 'supplier_category', 'supplier_category_other', 'years_in_operation']) },
                { title: 'Address & Contact', fieldNames: ['registered_address', 'office_address', 'warehouse_address', 'telephone_number', 'mobile_number', 'email_address', 'website_social_media'] },
                { title: 'Authorized Representative', fieldNames: ['representative_full_name', 'designation', 'phone_number', 'representative_email_address'] },
                { title: 'Billing & Payment', fieldNames: filterSupplierPreviewFields(['billing_address', 'accounting_contact_person', 'accounting_contact_number', 'accounting_email_address', 'payment_terms', 'payment_terms_other', 'preferred_payment_method', 'preferred_payment_method_other', 'online_payment_details', 'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number', 'swift_code']) },
                { title: 'Acknowledgment', fieldNames: filterSupplierPreviewFields(['person_accomplishing_full_name', 'person_accomplishing_position', 'id_type', 'id_type_other', 'id_number', 'date_signed']) },
            ];
        }

        return getModulePreviewSections(record).filter((section) => section && section.type !== 'notes');
    }

    function resolvePrItemId(value) {
        return findLineItemMasterOption(value)?.option?.id || '';
    }

    function resolvePrCategory(value) {
        const cleaned = String(value || '').trim().toLowerCase();
        if (cleaned === 'service' || cleaned === 'product') {
            return cleaned;
        }
        return '';
    }

    function getFinanceTaxTypeOptions() {
        return [
            { value: 'VAT', label: 'VAT' },
            { value: 'Expanded Withholding Tax', label: 'Expanded Withholding Tax' },
            { value: 'VAT Exempt', label: 'VAT Exempt' },
            { value: 'Zero Rated', label: 'Zero Rated' },
            { value: 'Non-VAT', label: 'Non-VAT' },
            { value: 'N/A', label: 'N/A' },
        ];
    }

    function normalizeFinanceTaxType(value) {
        const cleaned = String(value || '').trim().toLowerCase().replace(/[_–]+/g, ' ').replace(/\s+/g, ' ');
        if (cleaned === 'vat') return 'VAT';
        if (cleaned === 'expanded withholding tax' || cleaned === 'expanded wht' || cleaned === 'ewt' || cleaned === 'withholding tax') return 'Expanded Withholding Tax';
        if (cleaned === 'vat exempt' || cleaned === 'vat-exempt' || cleaned === 'tax exempt') return 'VAT Exempt';
        if (cleaned === 'zero rated' || cleaned === 'zero-rated' || cleaned === 'zero rated transactions') return 'Zero Rated';
        if (cleaned === 'non-vat' || cleaned === 'non vat' || cleaned === 'non vat transaction') return 'Non-VAT';
        if (cleaned === 'n/a' || cleaned === 'na' || cleaned === 'not applicable') return 'N/A';
        return value || 'N/A';
    }

    function getFinanceLineItemTaxImpact(taxType, taxableAmount) {
        const normalizedTaxType = normalizeFinanceTaxType(taxType);
        const taxable = Math.max(Number(taxableAmount || 0) || 0, 0);
        let taxAmount = 0;
        let whtAmount = 0;
        let taxRate = 0;
        let whtRate = 0;
        let label = 'No tax impact';

        if (normalizedTaxType === 'VAT') {
            taxRate = 0.12;
            taxAmount = taxable * taxRate;
            label = 'VAT 12%';
        } else if (normalizedTaxType === 'Expanded Withholding Tax') {
            whtRate = 0.01;
            whtAmount = taxable * whtRate;
            label = 'Expanded Withholding Tax 1%';
        } else if (normalizedTaxType === 'Non-VAT') {
            taxRate = 0.03;
            taxAmount = taxable * taxRate;
            label = 'Non-VAT 3%';
        } else if (normalizedTaxType === 'VAT Exempt') {
            label = 'VAT Exempt';
        } else if (normalizedTaxType === 'Zero Rated') {
            label = 'Zero Rated';
        }

        return {
            taxType: normalizedTaxType,
            taxAmount: Number(taxAmount.toFixed(2)),
            whtAmount: Number(whtAmount.toFixed(2)),
            taxRate: Number((taxRate * 100).toFixed(2)),
            whtRate: Number((whtRate * 100).toFixed(2)),
            label,
        };
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

        if (isLiquidation) {
            return `
            <div data-pr-line-items-section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-700">Liquidation Items</h5>
                        <p class="mt-1 max-w-2xl text-xs text-gray-500">Add the actual expense lines for the selected Cash Advance.</p>
                    </div>
                    <button type="button" onclick="window.financeModule.addPrLineItemRow()" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">
                        + Add row
                    </button>
                </div>

                <div id="prLineItemsBody" data-pr-line-items-body class="mt-5 space-y-4">
                    ${rows.map((row, index) => `
                        <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4 shadow-sm" data-pr-line-item-row data-row-index="${index}">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white">${index + 1}</span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">Expense Line ${index + 1}</p>
                                        <p class="text-xs text-gray-500">Enter the actual liquidation expense.</p>
                                    </div>
                                </div>
                                <button type="button" onclick="window.financeModule.removePrLineItemRow(this)" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-red-600 hover:bg-red-50">${escapeHtml(removeButtonLabel)}</button>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Item</label>
                                    <input
                                        type="text"
                                        name="data[line_items][${index}][item_id]"
                                        data-pr-line-item-field="item_id"
                                        value="${escapeHtml(getPrItemDisplayValue(row.item_id))}"
                                        class="w-full rounded-xl border border-gray-200 bg-white p-3"
                                        placeholder="Type or select item"
                                        list="prItemOptions"
                                    >
                                    <input type="hidden" name="data[line_items][${index}][item_module]" data-pr-line-item-field="item_module" value="${escapeHtml(row.item_module || '')}">
                                    <input type="hidden" name="data[line_items][${index}][item_record_id]" data-pr-line-item-field="item_record_id" value="${escapeHtml(row.item_record_id || '')}">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Description</label>
                                    <input type="text" name="data[line_items][${index}][description]" data-pr-line-item-field="description" value="${escapeHtml(row.description || '')}" class="w-full rounded-xl border border-gray-200 bg-white p-3" placeholder="Expense description">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Category</label>
                                    <input
                                        type="text"
                                        name="data[line_items][${index}][category]"
                                        data-pr-line-item-field="category"
                                        value="${escapeHtml(getPrCategoryDisplayValue(row.category))}"
                                        class="w-full rounded-xl border border-gray-200 bg-white p-3"
                                        placeholder="Type or select expense category"
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
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Total</label>
                                    <input type="number" step="0.01" min="0" name="data[line_items][${index}][total]" data-pr-line-item-field="total" value="${escapeHtml(row.total || '')}" class="w-full rounded-xl border border-blue-200 bg-gray-50 p-3 text-right font-semibold text-blue-900" placeholder="0.00" readonly>
                                </div>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-gray-900" data-pr-line-item-formula>${escapeHtml(formatPrQuantity(row.quantity || 0))} x ${escapeHtml(formatCurrency(row.amount || 0))} = ${escapeHtml(formatCurrency(row.total || (Number(row.quantity || 0) * Number(row.amount || 0))))}</p>
                        </div>
                    `).join('')}
                </div>

                <datalist id="prItemOptions">
                    ${itemOptionsHtml}
                </datalist>
                <datalist id="prCategoryOptions">
                    ${categoryOptionsHtml}
                </datalist>
            </div>
            `;
        }

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
                                        <input type="hidden" name="data[line_items][${index}][item_module]" data-pr-line-item-field="item_module" value="${escapeHtml(row.item_module || '')}">
                                        <input type="hidden" name="data[line_items][${index}][item_record_id]" data-pr-line-item-field="item_record_id" value="${escapeHtml(row.item_record_id || '')}">
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
                                            <label class="text-sm font-semibold text-blue-900">Tax Classification:</label>
                                            <select name="data[line_items][${index}][tax_type]" data-pr-line-item-field="tax_type" class="w-full rounded-xl border border-gray-200 bg-white p-3 font-semibold">
                                                ${getFinanceTaxTypeOptions().map((option) => `
                                                    <option value="${escapeHtml(option.value)}" ${normalizeFinanceTaxType(row.tax_type || 'N/A') === option.value ? 'selected' : ''}>${escapeHtml(option.label)}</option>
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
                                <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Tax Classification</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900 break-words">${escapeHtml(normalizeFinanceTaxType(row.tax_type || 'N/A'))}</p>
                                </div>
                                <div class="rounded-xl border border-white/80 bg-white px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Tax Impact</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900 break-words">${escapeHtml(row.tax_impact_label || getFinanceLineItemTaxImpact(row.tax_type || 'N/A', Math.max((Number(row.quantity || 0) * Number(row.amount || 0)) - Number(row.discount_amount || 0), 0)).label)}</p>
                                </div>
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

    function renderSourceDisbursementSummaryCard(record) {
        const data = record?.data || {};
        const amount = data.amount || record.amount || data.amount_requested || data.total_cash_advance || data.total_payroll_amount || data.acquisition_cost || '0.00';
        const totalDisbursed = data.total_disbursed_amount || '0.00';
        const remainingBalance = data.remaining_balance || '0.00';
        const percentagePaid = data.percentage_paid || '0.00';
        const disbursementStatus = data.disbursement_status || data.relationship_status || record.relationship_status || 'Awaiting Disbursement';
        const dvCount = data.dv_count || 0;
        const releasedDvCount = data.released_dv_count || 0;

        return `
            <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 shadow-sm">
                <div class="border-b border-emerald-100 bg-white/80 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.18em] text-emerald-700">Disbursement Summary</p>
                            <h4 class="mt-1 text-[16px] font-semibold text-gray-900">${escapeHtml(disbursementStatus)}</h4>
                        </div>
                        <span class="rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-[11px] font-semibold text-emerald-700">${escapeHtml(disbursementStatus)}</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4">
                    <div class="rounded-lg border border-emerald-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Source Amount</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(formatCurrency(amount || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Total Disbursed</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(formatCurrency(totalDisbursed || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Remaining Balance</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(formatCurrency(remainingBalance || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Percentage Paid</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(String(percentagePaid || '0.00'))}%</p>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">DV Count</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(String(dvCount || 0))}</p>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-white px-3 py-2">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Released DVs</p>
                        <p class="mt-1 font-semibold text-gray-900">${escapeHtml(String(releasedDvCount || 0))}</p>
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
        const variance = (numericAmount(totalCashAdvance || 0) - numericAmount(actualExpenses || 0)).toFixed(2);
        const statusMeta = getLiquidationStatusMeta(variance);
        const varianceIndicator = statusMeta.label;

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
            const taxTypeInput = row.querySelector('[data-pr-line-item-field="tax_type"]');
            const whtInput = row.querySelector('[data-pr-line-item-field="wht_amount"]');
            const totalInput = row.querySelector('[data-pr-line-item-field="total"]');
            const formulaText = row.querySelector('[data-pr-line-item-formula]');
            const rowSubtotal = quantity * amount;
            const discountPercent = parseFloat(String(discountPercentInput?.value || '0').replace('%', '')) || 0;
            const manualDiscountAmount = parseFloat(discountInput?.value || '0') || 0;
            const discountAmount = discountPercentInput
                ? rowSubtotal * (discountPercent / 100)
                : manualDiscountAmount;
            const shippingAmount = parseFloat(shippingInput?.value || '0') || 0;
            const taxableAmount = Math.max(rowSubtotal - discountAmount, 0);
            const taxImpact = getFinanceLineItemTaxImpact(taxTypeInput?.value || 'N/A', taxableAmount);
            const taxAmount = taxImpact.taxAmount;
            const whtAmount = taxImpact.whtAmount;
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
            if (discountInput && (discountPercent > 0 || discountPercentInput)) {
                discountInput.value = discountAmount.toFixed(2);
            }
            if (taxInput) {
                taxInput.value = taxAmount.toFixed(2);
                taxInput.readOnly = true;
                taxInput.classList.add('bg-gray-50');
                taxInput.classList.remove('bg-white');
            }
            if (taxTypeInput) {
                taxTypeInput.value = taxImpact.taxType;
            }
            if (whtInput) {
                whtInput.value = whtAmount.toFixed(2);
                whtInput.readOnly = true;
                whtInput.classList.add('bg-gray-50');
                whtInput.classList.remove('bg-white');
            }
            if (totalInput) {
                totalInput.value = lineTotal.toFixed(2);
                totalInput.readOnly = true;
                totalInput.classList.add('bg-gray-50');
                totalInput.classList.remove('bg-white');
            }
            if (formulaText) {
                formulaText.textContent = `Subtotal ${formatCurrency(rowSubtotal)} - Discount ${formatCurrency(discountAmount)} + Shipping ${formatCurrency(shippingAmount)} + Tax ${formatCurrency(taxAmount)} - WHT ${formatCurrency(whtAmount)} = ${formatCurrency(lineTotal)} (${taxImpact.label})`;
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
            const manualActualExpenses = parseFloat(actualExpensesInput?.value || '0');
            const actualExpenses = Number.isFinite(manualActualExpenses) && String(actualExpensesInput?.value || '').trim() !== ''
                ? manualActualExpenses
                : grandTotal;
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
            if (actualExpensesInput && rows.length > 0 && !document.activeElement?.isSameNode(actualExpensesInput)) actualExpensesInput.value = actualExpenses.toFixed(2);
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
        if (taxTypeInput && !taxTypeInput.value) taxTypeInput.value = normalizeFinanceTaxType(financeFormValues.tax_type || 'N/A');
        if (amountInput) amountInput.value = grandTotal.toFixed(2);
        syncPrPrimaryFields();
    }

    function bindPurchaseRequestLineItemRow(row) {
        if (!row || row.dataset.prBound === '1') return;
        row.dataset.prBound = '1';

        const itemInput = row.querySelector('[data-pr-line-item-field="item_id"]');
        if (itemInput) {
            itemInput.addEventListener('input', () => {
                if (autofillLineItemFromMaster(row)) {
                    updatePrTotals();
                    renderDrawerPreview();
                }
            });
            itemInput.addEventListener('change', () => {
                if (autofillLineItemFromMaster(row, { force: true })) {
                    updatePrTotals();
                    renderDrawerPreview();
                }
            });
        }

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
            const itemValue = firstRow?.querySelector('[data-pr-line-item-field="item_id"]')?.value || '';
            typeTarget.value = findLineItemMasterOption(itemValue)?.moduleKey || 'product';
        }

        const poTypeTarget = form.querySelector('[data-pr-primary-field="linked_item_type"]');
        if (poTypeTarget) {
            const itemValue = firstRow?.querySelector('[data-pr-line-item-field="item_id"]')?.value || '';
            poTypeTarget.value = findLineItemMasterOption(itemValue)?.moduleKey || 'product';
        }
    }

    function addPrLineItemRow() {
        if (!isLineItemModule()) return;

        const tbody = $('prLineItemsBody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('[data-pr-line-item-row]')).map((row) => {
            const getValue = (field) => row.querySelector(`[data-pr-line-item-field="${field}"]`)?.value || '';
            return {
                item_module: getValue('item_module'),
                item_record_id: getValue('item_record_id'),
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

        if (field.type === 'acknowledgment') {
            return value ? 'Agreed' : 'Not agreed';
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

            const option = getFieldOptions(field, formValues).find((item) => String(item.value) === String(value));
            return option ? option.label : value;
        }

        return value;
    }

    function renderAttachmentList(attachments = []) {
        const target = $('existingAttachmentList');
        if (!target) return;

        if (!attachments.length) {
            target.innerHTML = '<p class="text-xs text-gray-400 italic">No existing attachments.</p>';
            return;
        }

        const imageAttachmentCard = (attachment, index) => {
            const previewUrl = attachment.image_data_uri || attachment.url || normalizeAttachmentUrl(attachment.path || '');
            return `
                <div class="overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm">
                    <div class="aspect-[4/3] bg-slate-50">
                        <img src="${escapeHtml(previewUrl)}" alt="${escapeHtml(attachment.name || `Attachment ${index + 1}`)}" class="h-full w-full object-cover">
                    </div>
                    <div class="px-3 py-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 break-all">${escapeHtml(attachment.name || `Attachment ${index + 1}`)}</p>
                                <p class="text-xs text-gray-500 break-all">${escapeHtml(attachment.category || 'Asset Photo')}${attachment.path ? ` - ${escapeHtml(attachment.path)}` : ''}</p>
                            </div>
                            <a href="${escapeHtml(attachment.url || normalizeAttachmentUrl(attachment.path || ''))}" target="_blank" class="text-blue-600 hover:underline">Open</a>
                        </div>
                    </div>
                </div>
            `;
        };

        const fileAttachmentCard = (attachment, index) => `
            <div class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900 break-all">${escapeHtml(attachment.name || `Attachment ${index + 1}`)}</p>
                        <p class="text-xs text-gray-500 break-all">${escapeHtml(attachment.category || 'Supporting Document')}${attachment.path ? ` - ${escapeHtml(attachment.path)}` : ''}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-green-700">${escapeHtml(attachment.status || 'uploaded')}</span>
                        <a href="${escapeHtml(attachment.url || normalizeAttachmentUrl(attachment.path || ''))}" target="_blank" class="text-blue-600 hover:underline">Open</a>
                    </div>
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-gray-500">
                    ${attachment.uploaded_by ? `<span>Uploaded by ${escapeHtml(attachment.uploaded_by)}</span>` : ''}
                    ${attachment.uploaded_at ? `<span>${escapeHtml(attachment.uploaded_at)}</span>` : ''}
                    ${attachment.size ? `<span>${escapeHtml(formatBytes(attachment.size))}</span>` : ''}
                </div>
            </div>
        `;

        target.innerHTML = attachments.map((attachment, index) => financeAttachmentIsImage(attachment) ? imageAttachmentCard(attachment, index) : fileAttachmentCard(attachment, index)).join('');
    }

    function renderPendingAttachmentList(container) {
        const target = container?.querySelector('#pendingAttachmentList');
        if (!target) return;

        const pendingFiles = Array.from(container.querySelectorAll('input[type="file"]'))
            .flatMap((input) => Array.from(input.files || []).map((file) => ({
                name: file.name,
                size: file.size,
            })));

        if (!pendingFiles.length) {
            target.innerHTML = '';
            return;
        }

        target.innerHTML = pendingFiles.map((file) => `
            <div class="flex items-center justify-between gap-3 rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-sm">
                <span class="min-w-0 break-all font-medium text-gray-800">${escapeHtml(file.name)}</span>
                <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-700">ready to upload</span>
            </div>
        `).join('');
    }

    function defaultAttachmentControlsHtml() {
        const attachmentOptions = financeAttachmentTypes.filter((option) => option.active !== false && !option.hidden);
        return `
            <label class="block text-sm font-medium mb-1 text-blue-700">Attachments</label>
            <select
                id="attachmentCategoryInput"
                name="attachment_category"
                class="mb-2 w-full border border-blue-200 rounded-md p-2 bg-white text-sm"
            >
                ${attachmentOptions.length ? attachmentOptions.map((option) => `
                    <option value="${escapeHtml(option.value)}">${escapeHtml(option.label || option.value)}</option>
                `).join('') : '<option value="Supporting Document">Supporting Document</option>'}
            </select>
            <input
                id="attachmentsInput"
                name="attachments[]"
                type="file"
                multiple
                class="w-full border border-blue-200 rounded-md p-2 bg-blue-50"
            >
            <p id="attachmentHint" class="mt-2 text-xs text-gray-500">Upload supporting files if needed.</p>
            <div id="pendingAttachmentList" class="mt-3 space-y-2"></div>
            <div id="existingAttachmentList" class="mt-3 space-y-2"></div>
        `;
    }

    function arfAttachmentControlsHtml() {
        const attachmentOptions = financeAttachmentTypes.filter((option) => option.active !== false && !option.hidden);
        return `
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-3">
                <label class="block text-sm font-medium mb-1 text-emerald-700">Asset Photos</label>
                <input
                    id="arfPhotosInput"
                    name="arf_photos[]"
                    type="file"
                    accept="image/*"
                    capture="environment"
                    multiple
                    class="w-full border border-emerald-200 rounded-md p-2 bg-white text-sm"
                >
                <p class="mt-2 text-xs text-gray-500">Upload photos or use the device camera. Photos are saved as Asset Photo attachments.</p>
            </div>
            <div class="rounded-lg border border-blue-100 bg-white p-3">
                <label class="block text-sm font-medium mb-1 text-blue-700">Supporting Attachments</label>
                <select
                    id="attachmentCategoryInput"
                    name="attachment_category"
                    class="mb-2 w-full border border-blue-200 rounded-md p-2 bg-white text-sm"
                >
                    ${attachmentOptions.length ? attachmentOptions.map((option) => `
                        <option value="${escapeHtml(option.value)}">${escapeHtml(option.label || option.value)}</option>
                    `).join('') : '<option value="Supporting Document">Supporting Document</option>'}
                </select>
                <input
                    id="attachmentsInput"
                    name="attachments[]"
                    type="file"
                    multiple
                    class="w-full border border-blue-200 rounded-md p-2 bg-blue-50"
                >
                <p id="attachmentHint" class="mt-2 text-xs text-gray-500">Upload supporting files if needed. Photos and documents stay in the same ARF record.</p>
            </div>
            <div id="pendingAttachmentList" class="mt-3 space-y-2"></div>
            <div id="existingAttachmentList" class="mt-3 space-y-2"></div>
        `;
    }

    function supplierAttachmentControlsHtml(entityType = '') {
        const labels = supplierAttachmentLabelsForEntity(entityType);
        const fields = labels.length
            ? labels.map((label) => {
                const slug = supplierAttachmentSlug(label);
                return `
                    <div class="rounded-lg border border-blue-100 bg-white px-3 py-3">
                        <label class="block text-sm font-medium text-gray-800">${escapeHtml(label)}</label>
                        <input type="hidden" name="attachment_labels[${escapeHtml(slug)}]" value="${escapeHtml(label)}">
                        <input
                            name="attachments[${escapeHtml(slug)}]"
                            type="file"
                            class="mt-2 w-full border border-blue-200 rounded-md p-2 bg-blue-50 text-sm"
                        >
                    </div>
                `;
            }).join('')
            : '<p class="text-sm text-gray-500">Select an entity type to show the required document uploads.</p>';

        return `
            <label class="block text-sm font-medium mb-1 text-blue-700">Required Attachments</label>
            <p id="attachmentHint" class="mb-3 text-xs text-gray-500">Each required supplier document has its own upload field and will be saved with the matching document label.</p>
            <div id="supplierAttachmentFields" class="grid grid-cols-1 gap-3">
                ${fields}
            </div>
            <div id="pendingAttachmentList" class="mt-3 space-y-2"></div>
            <div id="existingAttachmentList" class="mt-3 space-y-2"></div>
        `;
    }

    function renderAttachmentControls(existingAttachments = []) {
        const section = $('attachmentsSection');
        if (!section) return;

        const currentRecord = currentEditRecordId ? getRecordById(currentEditRecordId) : null;
        if (financeFormLockedReadOnly) {
            section.innerHTML = `
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-sm font-semibold text-gray-800">Attachments are locked</p>
                    <p class="mt-1 text-xs text-gray-500">This record is final and its attachments are part of the permanent record.</p>
                </div>
                <div id="existingAttachmentList" class="mt-3 space-y-2"></div>
            `;
            renderAttachmentList(existingAttachments);
            return;
        }

        if (currentModuleKey === 'supplier' && !isSupplierDispatchLayout(currentRecord)) {
            const entityType = $('dynamicFields')?.querySelector('[name="data[entity_type]"]')?.value || '';
            section.innerHTML = supplierAttachmentControlsHtml(entityType);
        } else if (currentModuleKey === 'arf') {
            section.innerHTML = arfAttachmentControlsHtml();
        } else {
            section.innerHTML = defaultAttachmentControlsHtml();
        }

        renderAttachmentList(existingAttachments);
        section.querySelectorAll('input[type="file"]').forEach((input) => {
            input.addEventListener('change', () => {
                renderPendingAttachmentList(section);
                renderDrawerPreview();
            });
        });
        renderPendingAttachmentList(section);
    }

    function syncSupplierConditionalFields(existingAttachments = []) {
        if (currentModuleKey !== 'supplier') return;

        const entityTypeInput = $('dynamicFields')?.querySelector('[name="data[entity_type]"]');
        const paymentTermsInput = $('dynamicFields')?.querySelector('[name="data[payment_terms]"]');
        const paymentMethodInput = $('dynamicFields')?.querySelector('[name="data[preferred_payment_method]"]');
        const entityType = entityTypeInput?.value || '';
        const paymentTerms = paymentTermsInput?.value || '';
        const paymentMethod = paymentMethodInput?.value || '';

        const corporationField = $('dynamicFields')?.querySelector('[data-finance-field="corporation_type"]');
        if (corporationField) {
            const show = ['Corporation', 'One Person Corporation (OPC)', 'Foreign Company'].includes(entityType);
            corporationField.hidden = !show;
            corporationField.classList.toggle('hidden', !show);
            corporationField.querySelectorAll('select, input, textarea').forEach((input) => {
                input.disabled = !show;
            });
        }

        const entityOtherField = $('dynamicFields')?.querySelector('[data-finance-field="entity_type_other"]');
        if (entityOtherField) {
            const show = entityType === 'Others';
            entityOtherField.hidden = !show;
            entityOtherField.classList.toggle('hidden', !show);
            entityOtherField.querySelectorAll('select, input, textarea').forEach((input) => {
                input.disabled = !show;
            });
        }

        const supplierCategoryOtherField = $('dynamicFields')?.querySelector('[data-finance-field="supplier_category_other"]');
        if (supplierCategoryOtherField) {
            const selectedCategories = Array.from($('dynamicFields')?.querySelectorAll('input[name="data[supplier_category][]"]:checked') || []).map((input) => input.value);
            const show = selectedCategories.includes('Others');
            supplierCategoryOtherField.hidden = !show;
            supplierCategoryOtherField.classList.toggle('hidden', !show);
            supplierCategoryOtherField.querySelectorAll('select, input, textarea').forEach((input) => {
                input.disabled = !show;
            });
        }

        const paymentTermsOtherField = $('dynamicFields')?.querySelector('[data-finance-field="payment_terms_other"]');
        if (paymentTermsOtherField) {
            const show = paymentTerms === 'Others';
            paymentTermsOtherField.hidden = !show;
            paymentTermsOtherField.classList.toggle('hidden', !show);
            paymentTermsOtherField.querySelectorAll('select, input, textarea').forEach((input) => {
                input.disabled = !show;
            });
        }

        const idTypeOtherField = $('dynamicFields')?.querySelector('[data-finance-field="id_type_other"]');
        if (idTypeOtherField) {
            const show = $('dynamicFields')?.querySelector('[name="data[id_type]"]')?.value === 'Others';
            idTypeOtherField.hidden = !show;
            idTypeOtherField.classList.toggle('hidden', !show);
            idTypeOtherField.querySelectorAll('select, input, textarea').forEach((input) => {
                input.disabled = !show;
            });
        }

        const onlinePaymentField = $('dynamicFields')?.querySelector('[data-finance-field="online_payment_details"]');
        if (onlinePaymentField) {
            const show = paymentMethod === 'Online Payment';
            onlinePaymentField.hidden = !show;
            onlinePaymentField.classList.toggle('hidden', !show);
            onlinePaymentField.querySelectorAll('select, input, textarea').forEach((input) => {
                input.disabled = !show;
            });
        }

        const paymentMethodOtherField = $('dynamicFields')?.querySelector('[data-finance-field="preferred_payment_method_other"]');
        if (paymentMethodOtherField) {
            const show = paymentMethod === 'Others';
            paymentMethodOtherField.hidden = !show;
            paymentMethodOtherField.classList.toggle('hidden', !show);
            paymentMethodOtherField.querySelectorAll('select, input, textarea').forEach((input) => {
                input.disabled = !show;
            });
        }

        const registrationField = $('dynamicFields')?.querySelector('[data-finance-field="registration_number"]');
        const registrationLabel = registrationField?.querySelector('label');
        if (registrationLabel) {
            registrationLabel.textContent = supplierRegistrationLabel(entityType);
        }
    }

    function collectFinanceFormValues() {
        const values = {};
        const form = $('financeForm');
        if (!form) return values;

        form.querySelectorAll('input, select, textarea').forEach((input) => {
            if (!input.name) return;

            const fieldName = fieldNameFromInputName(input.name);

            if (input.type === 'checkbox') {
                if (input.name.endsWith('[]')) {
                    const current = Array.isArray(values[input.name]) ? values[input.name] : [];
                    if (input.checked) {
                        current.push(input.value);
                    }
                    values[input.name] = current;
                    values[fieldName] = current;
                    return;
                }

                values[input.name] = input.checked ? (input.value || '1') : '';
                values[fieldName] = values[input.name];
                return;
            }

            if (input.type === 'radio' && !input.checked) {
                return;
            }

            values[input.name] = input.value;
            values[fieldName] = input.value;
        });

        return values;
    }

    function setConditionalFieldVisibility(field, show) {
        const wrapper = $('dynamicFields')?.querySelector(`[data-finance-field="${cssIdentifier(field.name)}"]`);
        if (!wrapper) return;

        wrapper.hidden = !show;
        wrapper.classList.toggle('hidden', !show);
        wrapper.querySelectorAll('input, select, textarea').forEach((input) => {
            if (field.dependsOnCheckbox || input.matches('[data-checkbox-field]')) return;
            input.disabled = !show || Boolean(field.readOnly) || financeFormLockedReadOnly;
        });
    }

    function syncConditionalDynamicFields(existingAttachments = []) {
        const moduleConfig = getModuleConfig(currentModuleKey);
        const formValues = collectFinanceFormValues();

        (moduleConfig.fields || []).forEach((field) => {
            if (!field || !field.name || field.type === 'calculation') return;

            let show = true;

            if (currentModuleKey === 'supplier') {
                show = shouldShowSupplierPreviewField(field.name, formValues);
            } else {
                show = shouldShowSpecifyOtherField(field.name, formValues);
            }

            if (currentModuleKey === 'err') {
                const mode = formValues['data[reimbursement_mode]'] || formValues.reimbursement_mode || '';
                const modeFields = ['cash_receiver_name', 'recipient_bank_account', 'recipient_bank_number', 'bank_account_id'];
                const visibleModeFields = {
                    Cash: ['cash_receiver_name'],
                    'Bank Transfer': ['recipient_bank_account', 'recipient_bank_number'],
                    Check: ['bank_account_id'],
                }[mode] || [];

                if (modeFields.includes(field.name)) {
                    show = visibleModeFields.includes(field.name);
                }
            }

            setConditionalFieldVisibility(field, show);
        });

        syncAllDependentFieldStates();
        syncSupplierConditionalFields(existingAttachments);
    }

    function wireDynamicFieldEvents() {
        const form = $('financeForm');
        form.querySelectorAll('input, select, textarea').forEach((input) => {
            if (isLineItemModule() && input.closest('[data-pr-line-item-row]')) {
                return;
            }

            input.addEventListener('input', () => {
                if (input.matches('[data-checkbox-field]')) {
                    syncDependentFieldState(input);
                }
                if (isLineItemModule()) {
                    updatePrTotals();
                }
                if (currentModuleKey === 'ca') {
                    updateCashAdvanceReleaseValues(input.name?.match(/^data\[(.+)\]$/)?.[1] || '');
                }
                syncConditionalDynamicFields();
                renderDrawerPreview();
            });
            input.addEventListener('change', () => {
                if (input.matches('[data-checkbox-field]')) {
                    syncDependentFieldState(input);
                }
                if (isLineItemModule()) {
                    updatePrTotals();
                }
                if (currentModuleKey === 'ca') {
                    updateCashAdvanceReleaseValues(input.name?.match(/^data\[(.+)\]$/)?.[1] || '');
                }
                syncConditionalDynamicFields();
                renderDrawerPreview();
            });
        });

        syncConditionalDynamicFields();

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

        if (currentModuleKey === 'pda') {
            const payrollPeriodSelect = form.querySelector('[name="data[payroll_period_id]"]');
            if (payrollPeriodSelect) {
                payrollPeriodSelect.addEventListener('change', () => {
                    syncPdaPayrollPeriod({ preserveExisting: false });
                    renderDrawerPreview();
                });
            }
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

        if (currentModuleKey === 'err') {
            const reimbursementModeSelect = form.querySelector('select[name="data[reimbursement_mode]"]');
            if (reimbursementModeSelect) {
                reimbursementModeSelect.addEventListener('change', () => {
                    syncErrReimbursementModeFields();
                    renderDrawerPreview();
                });
            }
        }

        if (currentModuleKey === 'supplier') {
            ['entity_type', 'preferred_payment_method', 'id_type', 'payment_terms'].forEach((fieldName) => {
                const input = form.querySelector(`[name="data[${fieldName}]"]`);
                if (input) {
                    input.addEventListener('change', () => {
                        const supplierRecord = currentEditRecordId ? getRecordById(currentEditRecordId) : null;
                        syncSupplierConditionalFields(supplierRecord?.attachments || []);
                        if (fieldName === 'entity_type') {
                            renderAttachmentControls(supplierRecord?.attachments || []);
                        }
                        renderDrawerPreview();
                    });
                }
            });

            form.querySelectorAll('input[name="data[supplier_category][]"]').forEach((input) => {
                input.addEventListener('change', () => {
                    const supplierRecord = currentEditRecordId ? getRecordById(currentEditRecordId) : null;
                    syncSupplierConditionalFields(supplierRecord?.attachments || []);
                    renderDrawerPreview();
                });
            });
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

        if (isRequestOwnershipModule()) {
            const requesterEmployeeSelect = form.querySelector('select[name="data[requester_employee_id]"]');
            if (requesterEmployeeSelect) {
                requesterEmployeeSelect.addEventListener('change', () => {
                    syncRequestOwnershipFields({ preserveExisting: false });
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

            ['item_classification', 'current_quantity', 'reserved_quantity', 'accepted_quantity', 'beginning_quantity', 'unit_cost', 'acquisition_cost', 'residual_value', 'useful_life'].forEach((fieldName) => {
                const input = form.querySelector(`[name="data[${fieldName}]"]`);
                if (input) {
                    input.addEventListener('input', () => {
                        updateArfCalculatedFields();
                        renderDrawerPreview();
                    });
                    input.addEventListener('change', () => {
                        updateArfCalculatedFields();
                        renderDrawerPreview();
                    });
                }
            });

            updateArfCalculatedFields();
        }
    }

    function renderFinanceForm(record = null) {
        const moduleConfig = getModuleConfig(currentModuleKey);
        const draftContext = financeDraftContext && financeDraftContext.moduleKey === currentModuleKey ? financeDraftContext : null;
        const draftLinkedRecord = draftContext?.linkedRecord || null;
        financeFormLockedReadOnly = Boolean(record && record.can_edit === false);
        const values = {};
        financeFormValues = values;
        if (currentModuleKey === 'supplier' && record?.data?.completion_mode) {
            supplierCompletionMode = record.data.completion_mode;
        } else if (currentModuleKey !== 'supplier') {
            supplierCompletionMode = 'complete_internally';
        }
        if (currentModuleKey === 'supplier' && isSupplierCompletionFinished(record)) {
            supplierCompletionMode = 'complete_internally';
        }

        const recordNumberValue = record
            ? (record.record_number || '')
            : generateModuleRecordNumber(currentModuleKey);
        const recordTitleValue = record
            ? (record.record_title || generateDefaultRecordTitle(currentModuleKey, record))
            : (draftContext?.prefill?.record_title || draftContext?.recordTitle || generateDefaultRecordTitle(currentModuleKey));
        const recordDateValue = record ? record.record_date || '' : (draftContext?.prefill?.record_date || todayDateValue());
        const recordTimeValue = record
            ? (record.data?.transaction_time || currentTimeValue())
            : (draftContext?.prefill?.transaction_time || currentTimeValue());
        const amountValue = record
            ? (record.amount || '')
            : (draftContext?.prefill?.amount || draftContext?.prefill?.amount_returned || '');
        const statusValue = record ? (record.status || 'Draft') : 'Draft';
        const systemStatusOptions = [
            'Draft',
            'Pending Approval',
            'Partially Approved',
            'Approved',
            'On Hold',
            'Reverted',
            'Awaiting Disbursement',
            'Disbursed',
            'Completed',
            'Cancelled',
        ];
        const existingAttachments = record ? (record.attachments || []) : [];
        const activeRecord = record || draftLinkedRecord || null;

        $('financeRecordId').value = record ? record.id : '';
        $('financeModuleKey').value = currentModuleKey;
        $('recordNumberLabel').textContent = moduleConfig.recordNumberLabel;
        $('recordDateLabel').textContent = moduleConfig.recordDateLabel || 'Date';
        $('recordNumberInput').placeholder = `${getModuleRecordPrefix(currentModuleKey)}-00001`;
        const recordTitleLabel = $('recordTitleLabel');
        if (recordTitleLabel) {
            const titleLabel = moduleConfig.recordTitleLabel || `${moduleConfig.label} Name`;
            recordTitleLabel.innerHTML = `${escapeHtml(titleLabel)}${isFinanceRecordTitleRequired(currentModuleKey) ? ' <span class="text-red-500">*</span>' : ''}`;
        }
        const recordTitleInput = $('recordTitleInput');
        if (recordTitleInput) {
            recordTitleInput.placeholder = ['supplier', 'pr'].includes(currentModuleKey)
                ? ''
                : (moduleConfig.recordTitleLabel || `${moduleConfig.label} Name`);
            recordTitleInput.required = isFinanceRecordTitleRequired(currentModuleKey);
        }
        $('recordNumberInput').value = recordNumberValue;
        $('recordTitleInput').value = recordTitleValue;
        $('recordDateInput').value = recordDateValue;
        $('recordTimeInput').value = recordTimeValue;
        $('amountInput').value = amountValue;
        $('statusInput').innerHTML = systemStatusOptions
            .map((status) => `<option value="${status}">${status}</option>`)
            .join('');
        $('statusInput').value = statusValue;
        $('statusInput').disabled = true;
        $('drawerTitle').textContent = record ? `Edit ${moduleConfig.label}` : `Add ${moduleConfig.label}`;
        const supplierDispatchLayout = currentModuleKey === 'supplier' && isSupplierDispatchLayout(record);

        $('drawerSubtitle').textContent = supplierDispatchLayout
            ? 'Enter the supplier email address and send the completion form.'
            : (record
                ? `Update the ${moduleConfig.label.toLowerCase()} record and save changes.`
                : `Create a new ${moduleConfig.label.toLowerCase()} record.`);
        $('drawerPreviewTitle').textContent = record ? `Preview: ${record.record_number || getVisibleRecordTitle(record) || record.display_label || moduleConfig.label}` : 'New Finance Record';
        $('drawerSaveButton').textContent = supplierDispatchLayout ? 'Send Form' : (record ? 'Update' : 'Save');
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
        setSupplierFormLayout(record);
        const recordTitleWrapper = $('recordTitleInput')?.closest('#recordCoreFields > div');
        if (recordTitleWrapper) {
            recordTitleWrapper.classList.toggle('hidden', currentModuleKey === 'pr');
        }

        const supplierFields = moduleConfig.fields.filter((field) => field.name !== 'completion_mode');
        const fieldsHtml = (() => {
            if (currentModuleKey === 'supplier') {
                const fieldsToRender = supplierDispatchLayout
                    ? supplierFields.filter((field) => field.name === 'email_address')
                    : supplierFields;

                return [
                    `<input type="hidden" name="data[completion_mode]" value="${escapeHtml(supplierCompletionMode)}">`,
                    ...fieldsToRender.map((field) => {
                        let fieldValue = record ? getModuleFieldValue(record, field) : (values[`data[${field.name}]`] || '');
                        if (!fieldValue && field.autoFillCurrentUser) {
                            fieldValue = bootstrap.currentUserName || '';
                        }
                        if (!fieldValue && field.autoDateTime) {
                            fieldValue = currentDateTimeValue();
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
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Liquidation Expenses</h4>
                        <p class="mt-2 text-xs text-gray-500">Enter the total actual expenses for this Cash Advance. Itemized lines are not required.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 mb-1">Actual Expenses</label>
                                <input type="number" step="0.01" min="0" name="data[actual_expenses]" value="${escapeHtml(values.actual_expenses || '0.00')}" class="w-full rounded-xl border border-blue-200 bg-white p-3 text-right font-semibold text-blue-900" placeholder="0.00" required>
                            </div>
                        </div>
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
                const reimbursementModeValue = getDraftValue('reimbursement_mode', record);
                const cashReceiverNameValue = getDraftValue('cash_receiver_name', record);
                const recipientBankAccountValue = getDraftValue('recipient_bank_account', record);
                const recipientBankNumberValue = getDraftValue('recipient_bank_number', record);
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
                values.reimbursement_mode = reimbursementModeValue || linkedPrefill.reimbursement_mode || '';
                values['data[reimbursement_mode]'] = values.reimbursement_mode;
                values.cash_receiver_name = cashReceiverNameValue;
                values['data[cash_receiver_name]'] = cashReceiverNameValue;
                values.recipient_bank_account = recipientBankAccountValue;
                values['data[recipient_bank_account]'] = recipientBankAccountValue;
                values.recipient_bank_number = recipientBankNumberValue;
                values['data[recipient_bank_number]'] = recipientBankNumberValue;
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
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requester_employee_id', 'requestor', 'linked_lr_id', 'expense_details', 'amount', 'reimbursement_payment_details', 'manual_liquidation_entry', 'reimbursement_mode'], values, record)}
                            <div data-err-reimbursement-fields="Cash">
                                ${renderDynamicField(textField('cash_receiver_name', 'Name of Receiver', { required: values.reimbursement_mode === 'Cash' }), values.cash_receiver_name, values)}
                            </div>
                            <div data-err-reimbursement-fields="Bank Transfer">
                                ${renderDynamicField(textField('recipient_bank_account', 'Bank Account', { required: values.reimbursement_mode === 'Bank Transfer' }), values.recipient_bank_account, values)}
                            </div>
                            <div data-err-reimbursement-fields="Bank Transfer">
                                ${renderDynamicField(textField('recipient_bank_number', 'Bank Number', { required: values.reimbursement_mode === 'Bank Transfer' }), values.recipient_bank_number, values)}
                            </div>
                            <div data-err-reimbursement-fields="Check">
                                ${renderDynamicField(selectField('bank_account_id', 'Bank Account Source', { source: 'bank_account', required: values.reimbursement_mode === 'Check' }), values.bank_account_id, values)}
                            </div>
                            ${renderFieldsByNames(moduleConfig, ['remarks'], values, record)}
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
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requester_employee_id', 'requestor', 'linked_lr_id', 'amount_returned', 'manual_liquidation_entry', 'mode_of_return', 'receiving_bank_account_id', 'coa_id', 'reference_number', 'remarks'], values, record)}
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
                const receivedByNameValue = getDraftValue('received_by_name', record);
                const receivedBySignatureValue = getDraftValue('received_by_signature', record);
                const dateReceivedValue = getDraftValue('date_received', record);
                const remarksValue = getDraftValue('remarks', record);
                const sourceRecordNumberValue = getDraftValue('source_record_number', record);
                const sourceRecordDateValue = getDraftValue('source_record_date', record);
                let sourceRequesterValue = getDraftValue('source_requester', record);
                let sourceDepartmentSnapshotValue = getDraftValue('source_department', record);
                let sourceProjectValue = getDraftValue('source_project', record);
                let sourceCostCenterValue = getDraftValue('source_cost_center', record);
                let sourceFundSourceSnapshotValue = getDraftValue('source_fund_source', record);
                let sourceAmountValue = getDraftValue('source_amount', record);
                let sourceRemainingBalanceValue = getDraftValue('source_remaining_balance', record);
                let sourceApprovalStatusValue = getDraftValue('source_approval_status', record);
                let sourceApprovedByNameValue = getDraftValue('source_approved_by_name', record);
                let sourceApprovedAtValue = getDraftValue('source_approved_at', record);
                let sourceSupplierNameValue = getDraftValue('source_supplier_name', record);
                let sourceEmployeeNameValue = getDraftValue('source_employee_name', record);
                let sourcePayeeTypeValue = getDraftValue('source_payee_type', record);
                let sourcePayeeNameValue = getDraftValue('source_payee_name', record);
                let sourceStatusValue = getDraftValue('source_status', record);
                let sourceWorkflowStatusValue = getDraftValue('source_workflow_status', record);
                let sourceRelationshipStatusValue = getDraftValue('source_relationship_status', record);
                let totalDisbursedAmountValue = getDraftValue('total_disbursed_amount', record);
                let percentagePaidValue = getDraftValue('percentage_paid', record);
                let disbursementStatusValue = getDraftValue('disbursement_status', record);
                let payeeTypeValue = getDraftValue('payee_type', record);
                let payeeNameValue = getDraftValue('payee_name', record);
                const sourceRecord = sourceDocumentValue ? (getRecordByLookupValue(sourceTypeValue, sourceDocumentValue) || getRecordById(sourceDocumentValue) || getRecordByLookupValue('', sourceDocumentValue)) : null;
                const resolvedSourceTypeValue = sourceTypeValue || sourceRecord?.module_key || '';
                const sourceSnapshotPrefill = sourceRecord
                    ? (getDvSourceDocumentPrefill(resolvedSourceTypeValue, sourceRecord)?.prefill || {})
                    : {};
                const sourceValue = (fieldName, fallback = '') => {
                    const draftValue = getDraftValue(fieldName, record);
                    if (!blank(draftValue)) {
                        return draftValue;
                    }

                    const prefillValue = sourceSnapshotPrefill[fieldName];
                    if (!blank(prefillValue)) {
                        return prefillValue;
                    }

                    return fallback;
                };
                const receiptDefault = sourceTypeValue === 'ca' ? todayDateValue() : '';
                const receivedByDefault = sourceRequesterValue || sourceEmployeeNameValue || payeeNameValue || bootstrap.currentUserName || '';
                const withholdingTaxDefault = sourceTypeValue === 'ca' ? '0.00' : '';
                const vatAmountDefault = sourceTypeValue === 'ca' ? '0.00' : '';
                const exchangeRateDefault = currencyValue === 'PHP' ? '1.00' : '';

                sourceRequesterValue = sourceValue('source_requester', sourceRequesterValue);
                sourceDepartmentSnapshotValue = sourceValue('source_department', sourceDepartmentSnapshotValue);
                sourceProjectValue = sourceValue('source_project', sourceProjectValue);
                sourceCostCenterValue = sourceValue('source_cost_center', sourceCostCenterValue);
                sourceFundSourceSnapshotValue = sourceValue('source_fund_source', sourceFundSourceSnapshotValue);
                sourceAmountValue = sourceValue('source_amount', sourceAmountValue);
                sourceRemainingBalanceValue = sourceValue('source_remaining_balance', sourceRemainingBalanceValue);
                sourceApprovalStatusValue = sourceValue('source_approval_status', sourceApprovalStatusValue);
                sourceApprovedByNameValue = sourceValue('source_approved_by_name', sourceApprovedByNameValue);
                sourceApprovedAtValue = sourceValue('source_approved_at', sourceApprovedAtValue);
                sourceSupplierNameValue = sourceValue('source_supplier_name', sourceSupplierNameValue);
                sourceEmployeeNameValue = sourceValue('source_employee_name', sourceEmployeeNameValue);
                sourcePayeeTypeValue = sourceValue('source_payee_type', sourcePayeeTypeValue);
                sourcePayeeNameValue = sourceValue('source_payee_name', sourcePayeeNameValue);
                sourceStatusValue = sourceValue('source_status', sourceStatusValue);
                sourceWorkflowStatusValue = sourceValue('source_workflow_status', sourceWorkflowStatusValue);
                sourceRelationshipStatusValue = sourceValue('source_relationship_status', sourceRelationshipStatusValue);
                totalDisbursedAmountValue = sourceValue('total_disbursed_amount', totalDisbursedAmountValue);
                percentagePaidValue = sourceValue('percentage_paid', percentagePaidValue);
                disbursementStatusValue = sourceValue('disbursement_status', disbursementStatusValue);
                payeeTypeValue = sourceValue('payee_type', payeeTypeValue);
                payeeNameValue = sourceValue('payee_name', payeeNameValue);
                const effectiveWithholdingTaxValue = blank(withholdingTaxValue) ? withholdingTaxDefault : withholdingTaxValue;
                const effectiveVatAmountValue = blank(vatAmountValue) ? vatAmountDefault : vatAmountValue;
                const effectiveExchangeRateValue = blank(exchangeRateValue) ? exchangeRateDefault : exchangeRateValue;
                const effectiveReceivedByNameValue = blank(receivedByNameValue) ? receivedByDefault : receivedByNameValue;
                const effectiveDateReceivedValue = blank(dateReceivedValue) ? receiptDefault : dateReceivedValue;
                const effectiveReceivedBySignatureValue = blank(receivedBySignatureValue) ? '' : receivedBySignatureValue;
                const effectiveNetAmountValue = blank(netAmountValue)
                    ? Math.max(numericAmount(amountValue || sourceAmountValue || 0) + numericAmount(effectiveVatAmountValue) - numericAmount(effectiveWithholdingTaxValue), 0).toFixed(2)
                    : netAmountValue;

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
                values.withholding_tax = effectiveWithholdingTaxValue;
                values['data[withholding_tax]'] = effectiveWithholdingTaxValue;
                values.vat_amount = effectiveVatAmountValue;
                values['data[vat_amount]'] = effectiveVatAmountValue;
                values.net_amount = effectiveNetAmountValue;
                values['data[net_amount]'] = effectiveNetAmountValue;
                values.currency = currencyValue;
                values['data[currency]'] = currencyValue;
                values.exchange_rate = effectiveExchangeRateValue;
                values['data[exchange_rate]'] = effectiveExchangeRateValue;
                values.received_by_name = effectiveReceivedByNameValue;
                values['data[received_by_name]'] = effectiveReceivedByNameValue;
                values.received_by_signature = effectiveReceivedBySignatureValue;
                values['data[received_by_signature]'] = effectiveReceivedBySignatureValue;
                values.date_received = effectiveDateReceivedValue;
                values['data[date_received]'] = effectiveDateReceivedValue;
                values.remarks = remarksValue;
                values['data[remarks]'] = remarksValue;
                values.source_record_number = sourceRecordNumberValue;
                values['data[source_record_number]'] = sourceRecordNumberValue;
                values.source_record_date = sourceRecordDateValue;
                values['data[source_record_date]'] = sourceRecordDateValue;
                values.source_requester = sourceValue('source_requester', sourceRequesterValue);
                values['data[source_requester]'] = sourceRequesterValue;
                values.source_department = sourceValue('source_department', sourceDepartmentSnapshotValue);
                values['data[source_department]'] = sourceDepartmentSnapshotValue;
                values.source_project = sourceValue('source_project', sourceProjectValue);
                values['data[source_project]'] = sourceProjectValue;
                values.source_cost_center = sourceValue('source_cost_center', sourceCostCenterValue);
                values['data[source_cost_center]'] = sourceCostCenterValue;
                values.source_fund_source = sourceValue('source_fund_source', sourceFundSourceSnapshotValue);
                values['data[source_fund_source]'] = sourceFundSourceSnapshotValue;
                values.source_amount = sourceValue('source_amount', sourceAmountValue);
                values['data[source_amount]'] = sourceAmountValue;
                values.source_remaining_balance = sourceValue('source_remaining_balance', sourceRemainingBalanceValue);
                values['data[source_remaining_balance]'] = sourceRemainingBalanceValue;
                values.source_approval_status = sourceValue('source_approval_status', sourceApprovalStatusValue);
                values['data[source_approval_status]'] = sourceApprovalStatusValue;
                values.source_approved_by_name = sourceValue('source_approved_by_name', sourceApprovedByNameValue);
                values['data[source_approved_by_name]'] = sourceApprovedByNameValue;
                values.source_approved_at = sourceValue('source_approved_at', sourceApprovedAtValue);
                values['data[source_approved_at]'] = sourceApprovedAtValue;
                values.source_supplier_name = sourceValue('source_supplier_name', sourceSupplierNameValue);
                values['data[source_supplier_name]'] = sourceSupplierNameValue;
                values.source_employee_name = sourceValue('source_employee_name', sourceEmployeeNameValue);
                values['data[source_employee_name]'] = sourceEmployeeNameValue;
                values.source_payee_type = sourceValue('source_payee_type', sourcePayeeTypeValue);
                values['data[source_payee_type]'] = sourcePayeeTypeValue;
                values.source_payee_name = sourceValue('source_payee_name', sourcePayeeNameValue);
                values['data[source_payee_name]'] = sourcePayeeNameValue;
                values.source_current_balance = sourceValue('source_current_balance');
                values['data[source_current_balance]'] = values.source_current_balance;
                values.source_reserved_balance = sourceValue('source_reserved_balance');
                values['data[source_reserved_balance]'] = values.source_reserved_balance;
                values.source_available_balance = sourceValue('source_available_balance');
                values['data[source_available_balance]'] = values.source_available_balance;
                values.source_status = sourceStatusValue;
                values['data[source_status]'] = sourceStatusValue;
                values.source_workflow_status = sourceWorkflowStatusValue;
                values['data[source_workflow_status]'] = sourceWorkflowStatusValue;
                values.source_relationship_status = sourceRelationshipStatusValue;
                values['data[source_relationship_status]'] = sourceRelationshipStatusValue;
                values.total_disbursed_amount = sourceValue('total_disbursed_amount', totalDisbursedAmountValue);
                values['data[total_disbursed_amount]'] = totalDisbursedAmountValue;
                values.percentage_paid = sourceValue('percentage_paid', percentagePaidValue);
                values['data[percentage_paid]'] = percentagePaidValue;
                values.disbursement_status = sourceValue('disbursement_status', disbursementStatusValue);
                values['data[disbursement_status]'] = disbursementStatusValue;
                values.payee_type = sourceValue('payee_type', payeeTypeValue);
                values['data[payee_type]'] = payeeTypeValue;
                values.payee_name = sourceValue('payee_name', payeeNameValue);
                values['data[payee_name]'] = payeeNameValue;
                values.projected_balance_after_payment = sourceValue('projected_balance_after_payment');
                values['data[projected_balance_after_payment]'] = values.projected_balance_after_payment;
                const draftLineItems = Array.isArray(draftContext?.prefill?.line_items)
                    ? draftContext.prefill.line_items.map((row) => ({ ...row }))
                    : (record && Array.isArray(record.data?.line_items) ? record.data.line_items.map((row) => ({ ...row })) : []);
                financeFormValues.dv_line_items = draftLineItems;
                financeFormValues.line_items = draftLineItems;
                values.dv_line_items = draftLineItems;
                values.line_items = draftLineItems;
                values['data[dv_line_items]'] = draftLineItems;
                values['data[line_items]'] = draftLineItems;

                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Source Document</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                ${renderDynamicField(selectField('source_document_type', 'Linked Source Document Type', {
                                    required: true,
                                    options: [
                                        { value: 'po', label: 'PO' },
                                        { value: 'ca', label: 'CA' },
                                        { value: 'err', label: 'ERR' },
                                        { value: 'pda', label: 'PDA' },
                                        { value: 'ibtf', label: 'IBTF' },
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
                                <p id="dvSourceDocumentHint" class="mt-2 text-xs text-gray-500">${escapeHtml(resolvedSourceTypeValue ? 'Choose the exact document to auto-fill the voucher values and breakdown.' : 'Choose a source type first.')}</p>
                            </div>
                    <div class="md:col-span-2">
                        <div id="dvSourceDocumentInfo">
                                    ${getDvSourceDocumentInfoHtml(resolvedSourceTypeValue, sourceRecord)}
                        </div>
                    </div>
                </div>
            </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Source Record Snapshot</h4>
                        <p class="mt-2 text-xs text-gray-500">These values are copied from the selected source document and remain read-only.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderDynamicField(textField('source_record_number', 'Source Record Number', { readOnly: true }), sourceRecordNumberValue, values)}
                            ${renderDynamicField(dateField('source_record_date', 'Source Record Date', { readOnly: true }), sourceRecordDateValue, values)}
                            ${renderDynamicField(textField('source_requester', 'Requester', { readOnly: true }), sourceRequesterValue, values)}
                            ${renderDynamicField(textField('source_department', 'Department', { readOnly: true }), sourceDepartmentSnapshotValue, values)}
                            ${renderDynamicField(textField('source_project', 'Project', { readOnly: true }), sourceProjectValue, values)}
                            ${renderDynamicField(textField('source_cost_center', 'Cost Center', { readOnly: true }), sourceCostCenterValue, values)}
                            ${renderDynamicField(textField('source_fund_source', 'Fund Source', { readOnly: true }), sourceFundSourceSnapshotValue, values)}
                            ${renderDynamicField(numberField('source_amount', 'Amount', { readOnly: true }), sourceAmountValue, values)}
                            ${renderDynamicField(numberField('source_remaining_balance', 'Remaining Balance', { readOnly: true }), sourceRemainingBalanceValue, values)}
                            ${renderDynamicField(textField('source_approval_status', 'Approval Status', { readOnly: true }), sourceApprovalStatusValue, values)}
                            ${renderDynamicField(textField('source_approved_by_name', 'Approved By', { readOnly: true }), sourceApprovedByNameValue, values)}
                            ${renderDynamicField(textField('source_approved_at', 'Approved At', { readOnly: true }), sourceApprovedAtValue, values)}
                            ${renderDynamicField(textField('source_payee_type', 'Payee Type', { readOnly: true }), sourcePayeeTypeValue, values)}
                            ${renderDynamicField(textField('source_payee_name', 'Payee', { readOnly: true }), sourcePayeeNameValue, values)}
                            ${renderDynamicField(textField('source_supplier_name', 'Supplier Information', { readOnly: true }), sourceSupplierNameValue, values)}
                            ${renderDynamicField(textField('source_employee_name', 'Employee Information', { readOnly: true }), sourceEmployeeNameValue, values)}
                            ${renderDynamicField(numberField('source_current_balance', 'Current Balance', { readOnly: true }), values.source_current_balance, values)}
                            ${renderDynamicField(numberField('source_reserved_balance', 'Reserved Balance', { readOnly: true }), values.source_reserved_balance, values)}
                            ${renderDynamicField(numberField('source_available_balance', 'Available Balance', { readOnly: true }), values.source_available_balance, values)}
                            ${renderDynamicField(textField('source_status', 'Status', { readOnly: true }), sourceStatusValue, values)}
                            ${renderDynamicField(textField('source_workflow_status', 'Workflow Status', { readOnly: true }), sourceWorkflowStatusValue, values)}
                            ${renderDynamicField(textField('source_relationship_status', 'Relationship Status', { readOnly: true }), sourceRelationshipStatusValue, values)}
                            ${renderDynamicField(numberField('total_disbursed_amount', 'Total Disbursed Amount', { readOnly: true }), totalDisbursedAmountValue, values)}
                            ${renderDynamicField(numberField('percentage_paid', 'Percentage Paid', { readOnly: true }), percentagePaidValue, values)}
                            ${renderDynamicField(textField('disbursement_status', 'Disbursement Status', { readOnly: true }), disbursementStatusValue, values)}
                            ${renderDynamicField(numberField('projected_balance_after_payment', 'Projected Balance After Payment', { readOnly: true }), values.projected_balance_after_payment, values)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Voucher Details</h4>
                        <p class="mt-2 text-xs text-gray-500">These values are generated from the selected source document.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                ${renderDynamicField(textField('payee_type', 'Payee Type', { readOnly: true }), payeeTypeValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('payee_name', 'Payee', { readOnly: true }), payeeNameValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('supplier_id', 'Supplier', { readOnly: true }), supplierValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(numberField('amount', 'Amount'), amountValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('payment_type', 'Payment Type', { readOnly: true }), paymentTypeValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('disbursement_type', 'Disbursement Type', { readOnly: true }), disbursementTypeValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('bank_account_id', 'Bank Account', { readOnly: true }), bankAccountValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('coa_id', 'Account', { readOnly: true }), coaValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('fund_source', 'Fund Source / Project', { readOnly: true }), fundSourceValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('department', 'Department', { readOnly: true }), departmentValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(textField('reference_number', 'Reference Number', { readOnly: true }), referenceNumberValue, values)}
                            </div>
                            <div class="md:col-span-2">
                                ${renderDynamicField(textareaField('purpose', 'Purpose', { readOnly: true }), purposeValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(dateField('payment_date', 'Payment Date', { readOnly: true }), paymentDateValue, values)}
                            </div>
                            <div>
                                ${renderDynamicField(dateField('due_date', 'Due Date', { readOnly: true }), dueDateValue, values)}
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
                            ${renderDynamicField(numberField('withholding_tax', 'Withholding Tax (EWT)', { readOnly: true }), effectiveWithholdingTaxValue, values)}
                            ${renderDynamicField(numberField('vat_amount', 'VAT', { readOnly: true }), effectiveVatAmountValue, values)}
                            ${renderDynamicField(numberField('net_amount', 'Net Amount', { readOnly: true }), effectiveNetAmountValue, values)}
                            ${renderDynamicField(textField('currency', 'Currency', { readOnly: true }), currencyValue, values)}
                            ${renderDynamicField(numberField('exchange_rate', 'Exchange Rate', { readOnly: true }), effectiveExchangeRateValue, values)}
                            ${renderDynamicField(textField('received_by_name', 'Received By', { readOnly: true }), effectiveReceivedByNameValue, values)}
                            ${renderDynamicField(textField('received_by_signature', 'Signature', { readOnly: true }), effectiveReceivedBySignatureValue, values)}
                            ${renderDynamicField(dateField('date_received', 'Date Received', { readOnly: true }), effectiveDateReceivedValue, values)}
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'ca') {
                values.ca_payment_entries = normalizeCashAdvancePaymentEntries(record?.data?.ca_payment_entries || values.ca_payment_entries || []);
                const requestorValue = getDraftValue('requestor', record) || getDraftValue('employee_name', record) || getPrRequesterDefaults().requestor || '';
                values.requestor = requestorValue;
                values['data[requestor]'] = requestorValue;
                return `
                    <input type="hidden" name="data[requestor]" value="${escapeHtml(requestorValue)}">
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
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requester_employee_id', 'employee_id', 'employee_name', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'], values, record)}
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
                            ${renderFieldsByNames(moduleConfig, ['amount_requested', 'release_schedule', 'release_count', 'amount_per_release', 'cash_release_date', 'cash_release_time', 'mode_of_release', 'paid_through'], values, record)}
                        </div>
                    </div>

                    ${renderCashAdvancePaymentTracker(values)}

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Declarations & Authorizations</h4>
                        <div class="mt-4 grid grid-cols-1 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['official_business_cash_advance', 'employee_cash_advance_personal', 'liquidation_non_compliance', 'automatic_salary_deduction_authorization', 'final_pay_deduction_authorization', 'policy_acknowledgment'], values, record)}
                        </div>
                    </div>
                `;
            }

            if (currentModuleKey === 'ca') {
                values.ca_payment_entries = normalizeCashAdvancePaymentEntries(record?.data?.ca_payment_entries || values.ca_payment_entries || []);
                const requestorValue = getDraftValue('requestor', record) || getDraftValue('employee_name', record) || getPrRequesterDefaults().requestor || '';
                values.requestor = requestorValue;
                values['data[requestor]'] = requestorValue;
                return `
                    <input type="hidden" name="data[requestor]" value="${escapeHtml(requestorValue)}">
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
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requester_employee_id', 'employee_name', 'employee_id', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'], values, record)}
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
                            ${renderFieldsByNames(moduleConfig, ['amount_requested', 'release_schedule', 'release_count', 'amount_per_release', 'cash_release_date', 'cash_release_time', 'mode_of_release', 'paid_through'], values, record)}
                        </div>
                    </div>

                    ${renderCashAdvancePaymentTracker(values)}

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
                const projectValue = getDraftValue('project', record);
                const costCenterValue = getDraftValue('cost_center', record);
                const requesterEmployeeValue = requesterModeValue === 'request_for_another'
                    ? getDraftValue('requester_employee_id', record)
                    : '';
                const requesterEmployeeDefaults = getEmployeeRequesterDefaults(requesterEmployeeValue);
                const requestorBaseValue = getDraftValue('requestor', record);
                const employeeEmailBaseValue = getDraftValue('employee_email', record);
                const requesterValue = requesterModeValue === 'own_request'
                    ? (requestorBaseValue || requesterDefaults.requestor)
                    : (requestorBaseValue || requesterEmployeeDefaults?.requestor || '');
                const employeeEmailValue = requesterModeValue === 'own_request'
                    ? (employeeEmailBaseValue || requesterDefaults.employee_email)
                    : (employeeEmailBaseValue || requesterEmployeeDefaults?.employee_email || '');
                const requestorField = {
                    ...((moduleConfig.fields || []).find((field) => field.name === 'requestor')
                        || textField('requestor', 'Employee Name', { required: true })),
                    readOnly: requesterModeValue === 'own_request' || Boolean(requesterEmployeeDefaults),
                };
                const requestorFieldValue = requesterValue || '';
                values.requester_mode = requesterModeValue;
                values['data[requester_mode]'] = requesterModeValue;
                values.requester_employee_id = requesterEmployeeValue;
                values['data[requester_employee_id]'] = requesterEmployeeValue;
                values.requestor = requestorFieldValue;
                values['data[requestor]'] = requestorFieldValue;
                values.employee_email = employeeEmailValue || '';
                values['data[employee_email]'] = employeeEmailValue || '';
                ['employee_id', 'employee_name', 'contact_number', 'position', 'department', 'superior', 'superior_email'].forEach((fieldName) => {
                    const existingValue = getDraftValue(fieldName, record);
                    const defaultValue = getRequesterDefaultForField(fieldName);
                    const nextValue = requesterModeValue === 'own_request'
                        ? (existingValue || defaultValue || '')
                        : (existingValue || requesterEmployeeDefaults?.[fieldName] || '');
                    values[fieldName] = nextValue;
                    values[`data[${fieldName}]`] = nextValue;
                });
                values.supplier_id = supplierValue;
                values['data[supplier_id]'] = supplierValue;
                values.new_vendor = supplierRecord ? 'No' : (getDraftValue('new_vendor', record) || '');
                values['data[new_vendor]'] = values.new_vendor;
                values.project = projectValue || '';
                values['data[project]'] = projectValue || '';
                values.cost_center = costCenterValue || '';
                values['data[cost_center]'] = costCenterValue || '';
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
                            ${renderFieldsByNames(moduleConfig, ['priority', 'needed_date', 'for_client', 'pr_reason_categories'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Requester Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Choose Own Request to auto-fill your signed-in account details. Choose Request for Another when the request belongs to someone else.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['requester_mode', 'requester_employee_id'], values, record)}
                            ${renderDynamicField(requestorField, requestorFieldValue, values)}
                            ${renderFieldsByNames(moduleConfig, ['employee_id', 'employee_email', 'contact_number', 'position', 'department', 'superior', 'superior_email'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Project Allocation</h4>
                        <p class="mt-2 text-xs text-gray-500">Project and Cost Center should be entered here so downstream modules can inherit them automatically.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['project', 'cost_center'], values, record)}
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
                const linkedPrId = getDraftValue('linked_pr_id', record);
                const linkedPrRecord = getRecordById(linkedPrId) || getRecordByLookupValue('pr', linkedPrId);
                const linkedPrAutofill = getPoAutofillValuesFromLinkedRecord(linkedPrRecord);
                const supplierValue = getDraftValue('supplier_id', record) || linkedPrAutofill.supplier_id;
                const projectValue = getDraftValue('project', record) || linkedPrAutofill.project;
                const costCenterValue = getDraftValue('cost_center', record) || linkedPrAutofill.cost_center;
                const coaValue = getDraftValue('coa_id', record) || linkedPrAutofill.coa_id;
                const supplierSummary = getDraftValue('linked_pr_supplier_summary', record) || linkedPrAutofill.linked_pr_supplier_summary || '';
                values.supplier_id = supplierValue;
                values['data[supplier_id]'] = supplierValue;
                values.project = projectValue;
                values['data[project]'] = projectValue;
                values.cost_center = costCenterValue;
                values['data[cost_center]'] = costCenterValue;
                values.coa_id = coaValue;
                values['data[coa_id]'] = coaValue;
                values.linked_pr_id = linkedPrId;
                values['data[linked_pr_id]'] = linkedPrId;
                values.linked_pr_supplier_summary = supplierSummary;
                values['data[linked_pr_supplier_summary]'] = supplierSummary;

                return `
                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Order Details</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['linked_pr_id', 'supplier_id', 'project', 'cost_center', 'expected_delivery_date', 'delivery_address', 'terms_and_conditions'], values, record)}
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
                const linkedPoRecord = linkedPoId ? (getRecordById(linkedPoId) || getRecordByLookupValue('po', linkedPoId)) : null;
                const linkedDvRecord = linkedDvId ? (getRecordById(linkedDvId) || getRecordByLookupValue('dv', linkedDvId)) : null;
                const linkedAutofill = {
                    ...(linkedPoRecord ? getArfAutofillValuesFromLinkedRecord(linkedPoRecord) : {}),
                    ...(linkedDvRecord ? getArfAutofillValuesFromLinkedRecord(linkedDvRecord) : {}),
                };
                const itemClassificationValue = getDraftValue('item_classification', record) || 'Fixed Asset';
                const isFixedAsset = itemClassificationValue === 'Fixed Asset';
                const assetCodeValue = getDraftValue('asset_code', record) || recordNumberValue;
                const assetDescriptionValue = getDraftValue('asset_description', record);
                const assetCategoryValue = getDraftValue('asset_category', record);
                const serialNumberValue = getDraftValue('serial_number', record);
                const modelValue = getDraftValue('model', record);
                const supplierValue = getDraftValue('supplier_id', record) || linkedAutofill.supplier_id;
                const acquisitionCostValue = getDraftValue('acquisition_cost', record) || linkedAutofill.acquisition_cost;
                const acquisitionDateValue = getDraftValue('acquisition_date', record) || linkedAutofill.acquisition_date;
                const assetCoaValue = getDraftValue('asset_coa_id', record) || linkedAutofill.asset_coa_id;
                const locationValue = getDraftValue('location', record);
                const custodianValue = getDraftValue('custodian', record);
                const usefulLifeValue = getDraftValue('useful_life', record);
                const residualValue = getDraftValue('residual_value', record);
                const remarksValue = getDraftValue('remarks', record);
                const barcodeSvg = generateFinanceBarcodeSvg(assetCodeValue || record?.record_number || '');

                [
                    ['linked_po_id', linkedPoId],
                    ['linked_dv_id', linkedDvId],
                    ['item_classification', itemClassificationValue],
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
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">Asset / Inventory Details</h4>
                        <p class="mt-2 text-xs text-gray-500">Use Fixed Asset for depreciable items. Use Consumable Inventory for office supplies, stock, and other consumables.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['linked_po_id', 'linked_dv_id', 'supplier_id', 'item_classification', 'asset_code', 'item_name', 'item_code', 'sku', 'barcode', 'qr_code', 'asset_description', 'asset_category', 'serial_number', 'model'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Inventory & Receiving</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['goods_receiving_reference', 'ordered_quantity', 'delivered_quantity', 'accepted_quantity', 'rejected_quantity', 'unit_of_measure', 'beginning_quantity', 'current_quantity', 'reserved_quantity', 'available_quantity', 'reorder_level', 'minimum_stock_level', 'maximum_stock_level', 'safety_stock_level', 'unit_cost', 'total_cost', 'average_cost', 'last_purchase_cost'], values, record)}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">Asset Tag</h4>
                        <p class="mt-2 text-xs text-gray-500">This tag mirrors the printable plate and updates automatically from the asset code, location, and serial number.</p>
                        <div class="mt-4">
                            ${renderArfAssetTagCard(assetCodeValue, locationValue, serialNumberValue, barcodeSvg, { withPrintButton: true })}
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.24em] text-gray-700">${isFixedAsset ? 'Valuation & Custody' : 'Inventory Costing & Custody'}</h4>
                        <p class="mt-2 text-xs text-gray-500">${isFixedAsset ? 'Depreciation fields appear for fixed assets.' : 'Depreciation fields are hidden for consumable inventory.'}</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${renderFieldsByNames(moduleConfig, ['acquisition_cost', 'acquisition_date', 'asset_coa_id', 'location', 'department', 'custodian', 'useful_life', 'residual_value', 'depreciable_amount', 'annual_depreciation', 'monthly_depreciation', 'accumulated_depreciation', 'net_book_value', 'movement_history_note', 'remarks'], values, record)}
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
                if (!fieldValue && field.autoDateTime) {
                    fieldValue = currentDateTimeValue();
                }
                values[field.name] = fieldValue;
                values[`data[${field.name}]`] = fieldValue;
                return renderDynamicField(field, fieldValue, values);
            }).join('');
        })();

        const approvalHtml = renderApprovalSelectionPanel(record, values);
        $('dynamicFields').innerHTML = `${fieldsHtml}${approvalHtml}`;
        renderAttachmentControls(existingAttachments);
        wireDynamicFieldEvents();
        syncConditionalDynamicFields(existingAttachments);
        syncErrReimbursementModeFields();
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
        if (currentModuleKey === 'pda') {
            syncPdaPayrollPeriod({ preserveExisting: Boolean(record) });
        }
        if (currentModuleKey === 'arf') {
            syncArfLinkedDocumentFields({ preserveExisting: true });
            updateArfCalculatedFields();
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
            ...(currentModuleKey === 'pr' ? [] : [[moduleConfig.recordTitleLabel || 'Name', recordTitleValue || 'N/A']]),
            ['Date', recordDate || 'N/A'],
            ['Time', recordTime || 'N/A'],
            ...(shouldShowGenericAmount(currentModuleKey) ? [['Amount', amount || '0.00']] : []),
        ];

        const currentRecord = currentEditRecordId ? getRecordById(currentEditRecordId) : null;
        if (currentModuleKey === 'supplier' && isSupplierDispatchLayout(currentRecord)) {
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
            const itemClassification = formValues['data[item_classification]'] || $('dynamicFields').querySelector('select[name="data[item_classification]"]')?.value || 'Fixed Asset';
            const isFixedAsset = itemClassification === 'Fixed Asset';
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
            const goodsReceivingReference = formValues['data[goods_receiving_reference]'] || 'N/A';
            const orderedQuantity = formValues['data[ordered_quantity]'] || '0.00';
            const deliveredQuantity = formValues['data[delivered_quantity]'] || '0.00';
            const acceptedQuantity = formValues['data[accepted_quantity]'] || '0.00';
            const rejectedQuantity = formValues['data[rejected_quantity]'] || '0.00';
            const unitOfMeasure = formValues['data[unit_of_measure]'] || 'N/A';
            const beginningQuantity = formValues['data[beginning_quantity]'] || '0.00';
            const currentQuantity = formValues['data[current_quantity]'] || '0.00';
            const reservedQuantity = formValues['data[reserved_quantity]'] || '0.00';
            const availableStock = formValues['data[available_quantity]'] || '0.00';
            const reorderLevel = formValues['data[reorder_level]'] || '0.00';
            const minimumStockLevel = formValues['data[minimum_stock_level]'] || '0.00';
            const maximumStockLevel = formValues['data[maximum_stock_level]'] || '0.00';
            const safetyStockLevel = formValues['data[safety_stock_level]'] || '0.00';
            const unitCost = formValues['data[unit_cost]'] || '0.00';
            const totalCost = formValues['data[total_cost]'] || '0.00';
            const averageCost = formValues['data[average_cost]'] || '0.00';
            const lastPurchaseCost = formValues['data[last_purchase_cost]'] || '0.00';
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
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Inventory & Receiving</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                ${[
                                    ['Goods Receiving Reference', goodsReceivingReference],
                                    ['Ordered Quantity', orderedQuantity],
                                    ['Delivered Quantity', deliveredQuantity],
                                    ['Accepted Quantity', acceptedQuantity],
                                    ['Rejected Quantity', rejectedQuantity],
                                    ['Unit of Measure', unitOfMeasure],
                                    ['Beginning Quantity', beginningQuantity],
                                    ['Quantity on Hand', currentQuantity],
                                    ['Reserved Quantity', reservedQuantity],
                                    ['Available Stock', availableStock],
                                    ['Reorder Level', reorderLevel],
                                    ['Minimum Stock Level', minimumStockLevel],
                                    ['Maximum Stock Level', maximumStockLevel],
                                    ['Safety Stock Level', safetyStockLevel],
                                    ['Unit Cost', unitCost],
                                    ['Total Cost', totalCost],
                                    ['Average Cost', averageCost],
                                    ['Last Purchase Cost', lastPurchaseCost],
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
                                    ['Item Classification', itemClassification],
                                    ['Custodian', custodian],
                                    ...(isFixedAsset ? [
                                        ['Useful Life (Years)', usefulLife],
                                        ['Residual Value', residualValue],
                                    ] : []),
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
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Asset Tag</h4>
                                    ${renderArfAssetTagPrintButton(assetCode, location, serialNumber, 'inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-gray-700 hover:bg-gray-50')}
                                </div>
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
                ca_payment_entries: collectCashAdvancePaymentEntriesFromForm(),
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
                                <h4 class="text-[12px] font-semibold uppercase tracking-[0.26em] text-gray-700">Cash Advance Payment Tracking</h4>
                            </div>
                            <div class="p-4">
                                ${renderCashAdvancePaymentSummaryPanel(summaryValues, { compact: true })}
                                <div class="mt-4">
                                    ${renderCashAdvancePaymentTrackerPanel(summaryValues, { compact: true })}
                                </div>
                            </div>
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
                                <div class="border-r border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Cash Release Date / Time</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml([summaryValues.cash_release_date, summaryValues.cash_release_time].filter((value) => value && value !== 'N/A').join(' ') || 'N/A')}</p>
                                </div>
                                <div class="border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Paid Through</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(summaryValues.paid_through)}</p>
                                </div>
                                ${(String(formValues['data[cash_advance_type]'] || '').trim() === 'Other Business Purpose' || (Array.isArray(formValues['data[usage_categories][]']) ? formValues['data[usage_categories][]'] : []).map((item) => String(item).trim()).includes('Other Expense'))
                                    ? `
                                <div class="border-r border-t border-gray-300 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-gray-500">Other Business Purpose</p>
                                    <p class="mt-2 min-h-[20px] border-b border-gray-300 text-[14px] font-semibold text-gray-900 break-words">${escapeHtml(formValues['data[other_business_purpose_specify]'] || formValues['data[other_expense_specify]'] || 'Not filled yet')}</p>
                                </div>
                                    `
                                    : ''}
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
                if (currentModuleKey === 'supplier' && isSupplierDispatchLayout(currentRecord)) {
                    return field.name === 'email_address';
                }

                if (currentModuleKey === 'supplier') {
                    return shouldShowSupplierPreviewField(field.name, formValues);
                }

                if (!shouldShowSpecifyOtherField(field.name, formValues)) {
                    return false;
                }

                if (currentModuleKey === 'err') {
                    const mode = formValues['data[reimbursement_mode]'] || '';
                    const modeFields = ['cash_receiver_name', 'recipient_bank_account', 'recipient_bank_number', 'bank_account_id'];
                    const visibleModeFields = {
                        Cash: ['cash_receiver_name'],
                        'Bank Transfer': ['recipient_bank_account', 'recipient_bank_number'],
                        Check: ['bank_account_id'],
                    }[mode] || [];

                    return !modeFields.includes(field.name) || visibleModeFields.includes(field.name);
                }

                return true;
            });

        const dataPairs = previewFields.map((field, index) => {
            const value = formValues[`data[${field.name}]`] ?? formValues[`data[${field.name}][]`];
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

    function openFinanceDraftFromSource(moduleKey, sourceRecord, prefill = {}, message = '') {
        const resolvedSourceRecord = typeof sourceRecord === 'object' && sourceRecord !== null
            ? sourceRecord
            : getRecordById(sourceRecord);

        if (!resolvedSourceRecord || !moduleKey) return;

        currentModuleKey = moduleKey;
        currentWorkflowFilter = 'all';

        const url = new URL(window.location.href);
        url.searchParams.set('module', moduleKey);
        url.searchParams.delete('workflow_status');
        window.history.replaceState({}, '', url);

        financeDraftContext = {
            moduleKey,
            linkedRecord: resolvedSourceRecord,
            prefill,
        };

        refreshFinanceView();
        openFinanceDrawer(null);
        if (message) {
            showFinanceToast(message, 'success');
        }
    }

    function openPurchaseOrderFromSource(sourceRecord) {
        const resolvedSourceRecord = typeof sourceRecord === 'object' && sourceRecord !== null
            ? sourceRecord
            : getRecordById(sourceRecord);

        if (!resolvedSourceRecord) return;

        const sourceData = resolvedSourceRecord.data || {};
        const poAutofill = getPoAutofillValuesFromLinkedRecord(resolvedSourceRecord);
        openFinanceDraftFromSource('po', resolvedSourceRecord, {
            linked_pr_id: resolvedSourceRecord.id,
            supplier_id: sourceData.supplier_id || poAutofill.supplier_id || '',
            coa_id: sourceData.coa_id || poAutofill.coa_id || '',
            expected_delivery_date: sourceData.expected_delivery_date || '',
            delivery_address: sourceData.delivery_address || '',
            terms_and_conditions: sourceData.terms_and_conditions || '',
            purpose: sourceData.purpose || '',
            remarks: sourceData.remarks || '',
            linked_pr_supplier_summary: resolvedSourceRecord.record_number || '',
            record_title: `PO for ${resolvedSourceRecord.record_number || 'PR'}`,
        }, 'PR details loaded into a new Purchase Order.');
    }

    function openLiquidationReportFromSource(sourceRecord) {
        const resolvedSourceRecord = typeof sourceRecord === 'object' && sourceRecord !== null
            ? sourceRecord
            : getRecordById(sourceRecord);

        if (!resolvedSourceRecord) return;

        const sourceData = resolvedSourceRecord.data || {};
        openFinanceDraftFromSource('lr', resolvedSourceRecord, {
            linked_ca_id: resolvedSourceRecord.id,
            total_cash_advance: resolvedSourceRecord.amount || sourceData.amount_requested || '',
            requester_mode: sourceData.requester_mode || 'own_request',
            requestor: sourceData.requestor || resolvedSourceRecord.user || bootstrap.currentUserName || '',
            purpose: sourceData.purpose || sourceData.justification || '',
            employee_id: sourceData.employee_id || '',
            employee_name: sourceData.employee_name || resolvedSourceRecord.user || bootstrap.currentUserName || '',
            employee_email: sourceData.employee_email || '',
            contact_number: sourceData.contact_number || '',
            position: sourceData.position || '',
            department: sourceData.department || '',
            superior: sourceData.superior || '',
            superior_email: sourceData.superior_email || '',
            for_client: sourceData.for_client || 'N/A',
            client_names: sourceData.client_names || '',
            coa_id: sourceData.coa_id || '',
            record_title: `LR for ${resolvedSourceRecord.record_number || 'CA'}`,
        }, 'CA details loaded into a new Liquidation Report.');
    }

    function openAssetRecordFromSource(sourceRecord) {
        const resolvedSourceRecord = typeof sourceRecord === 'object' && sourceRecord !== null
            ? sourceRecord
            : getRecordById(sourceRecord);

        if (!resolvedSourceRecord) return;

        const sourceData = resolvedSourceRecord.data || {};
        const linkedPoRecord = sourceData.linked_po_id ? (getRecordById(sourceData.linked_po_id) || getRecordByLookupValue('po', sourceData.linked_po_id)) : null;
        const linkedDvRecord = sourceData.linked_dv_id ? (getRecordById(sourceData.linked_dv_id) || getRecordByLookupValue('dv', sourceData.linked_dv_id)) : null;
        const linkedAutofill = {
            ...(linkedPoRecord ? getArfAutofillValuesFromLinkedRecord(linkedPoRecord) : {}),
            ...(linkedDvRecord ? getArfAutofillValuesFromLinkedRecord(linkedDvRecord) : {}),
        };
        const prefill = {
            linked_po_id: resolvedSourceRecord.module_key === 'po' ? resolvedSourceRecord.id : (sourceData.linked_po_id || ''),
            linked_dv_id: resolvedSourceRecord.module_key === 'dv' ? resolvedSourceRecord.id : (sourceData.linked_dv_id || ''),
            supplier_id: sourceData.supplier_id || linkedAutofill.supplier_id || '',
            item_classification: sourceData.item_classification || 'Fixed Asset',
            asset_code: sourceData.asset_code || resolvedSourceRecord.record_number || '',
            asset_description: sourceData.asset_description || sourceData.purpose || linkedAutofill.asset_description || resolvedSourceRecord.record_title || '',
            asset_category: sourceData.asset_category || sourceData.linked_item_type || linkedAutofill.asset_category || '',
            serial_number: sourceData.serial_number || '',
            model: sourceData.model || '',
            beginning_quantity: sourceData.beginning_quantity || linkedAutofill.beginning_quantity || '',
            current_quantity: sourceData.current_quantity || sourceData.accepted_quantity || linkedAutofill.current_quantity || '',
            reserved_quantity: sourceData.reserved_quantity || linkedAutofill.reserved_quantity || '',
            acquisition_cost: sourceData.grand_total || sourceData.total_amount || sourceData.amount || linkedAutofill.acquisition_cost || resolvedSourceRecord.amount || '',
            unit_cost: sourceData.unit_cost || linkedAutofill.unit_cost || '',
            average_cost: sourceData.average_cost || linkedAutofill.average_cost || '',
            last_purchase_cost: sourceData.last_purchase_cost || linkedAutofill.last_purchase_cost || '',
            total_cost: sourceData.total_cost || linkedAutofill.total_cost || '',
            acquisition_date: sourceData.payment_date || linkedAutofill.acquisition_date || resolvedSourceRecord.record_date || '',
            asset_coa_id: sourceData.asset_coa_id || sourceData.coa_id || linkedAutofill.asset_coa_id || '',
            location: sourceData.location || '',
            custodian: sourceData.custodian || '',
            remarks: sourceData.remarks || '',
            record_title: `Asset File for ${resolvedSourceRecord.record_number || resolvedSourceRecord.module_label || 'Source'}`,
        };

        openFinanceDraftFromSource('arf', resolvedSourceRecord, prefill, 'Source document loaded into a new Asset / Inventory record.');
    }

    function openDisbursementVoucherFromSource(sourceRecord, event = null) {
        if (event?.preventDefault) event.preventDefault();
        if (event?.stopPropagation) event.stopPropagation();

        const resolvedSourceRecord = typeof sourceRecord === 'object' && sourceRecord !== null
            ? sourceRecord
            : getRecordById(sourceRecord);

        if (!resolvedSourceRecord) {
            showFinanceToast('Unable to load the selected source document.', 'error');
            return;
        }

        const sourceData = resolvedSourceRecord.data || {};
        let payload = {};

        try {
            payload = getDvFieldPayload(resolvedSourceRecord.module_key, resolvedSourceRecord, resolvedSourceRecord.id) || {};
        } catch (error) {
            console.error('Unable to build DV payload from source record:', error);
        }

        const fallbackPrefill = {
            source_document_type: resolvedSourceRecord.module_key || '',
            source_document_id: resolvedSourceRecord.id || '',
            source_record_number: resolvedSourceRecord.record_number || '',
            source_record_date: resolvedSourceRecord.record_date || '',
            source_requester: sourceData.requestor || sourceData.employee_name || resolvedSourceRecord.user || bootstrap.currentUserName || '',
            source_department: sourceData.department || sourceData.requesting_department || '',
            source_project: sourceData.project || sourceData.project_name || sourceData.project_code || sourceData.fund_source || sourceData.department || sourceData.requesting_department || '',
            source_cost_center: sourceData.cost_center || sourceData.cost_center_code || sourceData.cost_center_name || sourceData.department || sourceData.requesting_department || sourceData.fund_source || '',
            source_fund_source: sourceData.fund_source || '',
            source_amount: resolvedSourceRecord.amount || sourceData.amount || sourceData.amount_requested || sourceData.total_cash_advance || '',
            source_remaining_balance: sourceData.remaining_balance || '',
            source_current_balance: sourceData.current_balance || '',
            source_reserved_balance: sourceData.reserved_balance || '',
            source_available_balance: sourceData.available_balance || '',
            source_approval_status: resolvedSourceRecord.approval_status || sourceData.approval_status || '',
            source_approved_by_name: sourceData.approved_by_name || '',
            source_approved_at: sourceData.approved_at || '',
            source_supplier_name: sourceData.supplier_name || (sourceData.payee_type === 'Supplier' ? sourceData.payee_name || resolvedSourceRecord.record_title : ''),
            source_employee_name: sourceData.employee_name || resolvedSourceRecord.user || bootstrap.currentUserName || '',
            source_payee_type: sourceData.payee_type || '',
            source_payee_name: sourceData.payee_name || '',
            source_status: resolvedSourceRecord.status || sourceData.status || '',
            source_workflow_status: resolvedSourceRecord.workflow_status || sourceData.workflow_status || '',
            source_relationship_status: resolvedSourceRecord.relationship_status || sourceData.relationship_status || '',
            amount: resolvedSourceRecord.amount || sourceData.amount || sourceData.amount_requested || sourceData.total_cash_advance || sourceData.amount_returned || sourceData.total_payroll_amount || sourceData.acquisition_cost || '',
            supplier_id: sourceData.supplier_id || '',
            bank_account_id: sourceData.bank_account_id || '',
            coa_id: sourceData.coa_id || sourceData.asset_coa_id || sourceData.payroll_expense_coa_id || '',
            payee_type: sourceData.payee_type || '',
            payee_name: sourceData.payee_name || sourceData.requestor || sourceData.employee_name || resolvedSourceRecord.user || bootstrap.currentUserName || '',
            payment_type: sourceData.payment_type || sourceData.mode_of_release || sourceData.mode_of_return || 'Cash',
            disbursement_type: sourceData.disbursement_type || sourceData.payment_type || sourceData.mode_of_release || sourceData.mode_of_return || 'Cash',
            fund_source: sourceData.fund_source || sourceData.project || sourceData.department || '',
            department: sourceData.department || sourceData.requesting_department || '',
            purpose: sourceData.purpose || sourceData.justification || sourceData.reason || sourceData.remarks || '',
            payment_date: sourceData.payment_date || resolvedSourceRecord.record_date || todayDateValue(),
            due_date: sourceData.due_date || sourceData.needed_date || sourceData.expected_delivery_date || sourceData.pay_date || sourceData.acquisition_date || '',
            withholding_tax: sourceData.withholding_tax || sourceData.wht_amount || sourceData.wht_total || '',
            vat_amount: sourceData.vat_amount || sourceData.tax_amount || sourceData.tax_total || '',
            currency: sourceData.currency || 'PHP',
            exchange_rate: sourceData.exchange_rate || '',
            received_by_name: sourceData.received_by_name || '',
            received_by_signature: sourceData.received_by_signature || '',
            date_received: sourceData.date_received || '',
            reference_number: sourceData.reference_number || resolvedSourceRecord.record_number || '',
            remarks: sourceData.remarks || '',
            record_title: `DV for ${resolvedSourceRecord.record_number || resolvedSourceRecord.module_label || 'Source'}`,
        };

        openFinanceDraftFromSource('dv', resolvedSourceRecord, {
            ...payload,
            ...fallbackPrefill,
            record_title: `DV for ${resolvedSourceRecord.record_number || resolvedSourceRecord.module_label || 'Source'}`,
        }, `${resolvedSourceRecord.module_label || 'Source'} details loaded into a new Disbursement Voucher.`);
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

            financeFormValues = financeFormValues || {};
            financeFormValues[name] = value ?? '';
            financeFormValues[`data[${name}]`] = value ?? '';
        };

        financeFormValues = financeFormValues || {};
        setField('total_cash_advance', sourceRecord.amount || sourceData.amount_requested || '');
        setField('requester_mode', sourceData.requester_mode || 'own_request');
        setField('requestor', sourceData.requestor || sourceRecord.user || bootstrap.currentUserName || '');
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

    function revokeCurrentPreviewAttachmentObjectUrl() {
        if (currentPreviewAttachmentObjectUrl) {
            URL.revokeObjectURL(currentPreviewAttachmentObjectUrl);
            currentPreviewAttachmentObjectUrl = '';
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
        currentPreviewRefreshSignature = buildPreviewRefreshSignature(record);
        $('previewModuleTitle').textContent = record.module_label;
        if (currentPreviewTab !== 'attachments' || !currentPreviewAttachmentUrl) {
            revokeCurrentPreviewAttachmentObjectUrl();
        }
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
            refreshFinanceView();

            if (currentPreviewRecord && String(currentPreviewRecord.id) === String(data.id)) {
                const nextSignature = buildPreviewRefreshSignature(data);
                if (nextSignature === currentPreviewRefreshSignature) {
                    currentPreviewRecord = data;
                    return;
                }

                syncPreviewFromRecord(data);
                if (!isPendingSupplierCompletion(data) || (currentPreviewTab === 'attachments' && currentPreviewAttachmentUrl)) {
                    clearPreviewRefreshTimer();
                }
            }
        } catch (error) {
            if (!silent) {
                console.error('Unable to refresh finance preview record:', error);
            }
        }
    }

    function startPreviewRefresh(record) {
        clearPreviewRefreshTimer();
        currentPreviewRefreshSignature = buildPreviewRefreshSignature(record);

        const refreshCurrentPreview = () => {
            if (!currentPreviewRecord) {
                clearPreviewRefreshTimer();
                return;
            }

            refreshPreviewRecord(currentPreviewRecord.id, true);
        };

        window.setTimeout(refreshCurrentPreview, 2000);

        if (isPendingSupplierCompletion(record)) {
            currentPreviewRefreshTimer = window.setInterval(refreshCurrentPreview, 5000);
        }
    }

    function financeHistoryEntries(record, limit = null) {
        const history = Array.isArray(record?.data?.history) ? record.data.history : [];
        const entries = history
            .filter((entry) => entry && typeof entry === 'object')
            .slice()
            .reverse();

        return Number.isFinite(limit) ? entries.slice(0, limit) : entries;
    }

    function financeHistoryDisplayValue(value) {
        if (value === null || value === undefined || value === '') {
            return 'Blank';
        }

        if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
            try {
                return JSON.stringify(value);
            } catch (error) {
                return String(value);
            }
        }

        return String(value);
    }

    function financeHistoryFieldLabel(field) {
        const normalized = String(field || '').trim().toLowerCase();

        if (!normalized || normalized === 'data' || normalized.startsWith('data.')) {
            return '';
        }

        return String(field || 'Field').replace(/^data\./, '').replace(/_/g, ' ');
    }

    function financeHistoryChangeRows(entry, limit = 6) {
        const changes = Array.isArray(entry?.changes) ? entry.changes : [];
        const rows = changes
            .filter((change) => financeHistoryFieldLabel(change?.field) !== '')
            .slice(0, limit)
            .map((change) => ({
                field: financeHistoryFieldLabel(change?.field),
                oldValue: financeHistoryDisplayValue(change?.old_value),
                newValue: financeHistoryDisplayValue(change?.new_value),
            }));

        return {
            rows,
            remaining: Math.max(changes.filter((change) => financeHistoryFieldLabel(change?.field) !== '').length - rows.length, 0),
        };
    }

    function renderFinanceHistoryCards(record) {
        if (record?.module_key === 'crf') {
            return renderCrfHistoryTimeline(record);
        }

        const entries = financeHistoryEntries(record, 5);

        if (!entries.length) {
            return `
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <h4 class="text-[15px] font-semibold text-gray-900">Record History / Audit Trail</h4>
                    <p class="mt-3 text-sm text-gray-500">No audit entries have been recorded yet.</p>
                </div>
            `;
        }

        return `
            <div class="rounded-2xl border border-gray-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-[15px] font-semibold text-gray-900">Record History / Audit Trail</h4>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-600">${escapeHtml(String(entries.length))} latest</span>
                </div>
                <div class="mt-4 space-y-3">
                    ${entries.map((entry) => {
                        const changeRows = financeHistoryChangeRows(entry, 6);
                        return `
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900">${escapeHtml(entry.action || 'Action')}</p>
                                        <p class="mt-1 text-xs text-gray-500">${escapeHtml(entry.changed_by || 'System')} | ${escapeHtml(entry.changed_at || 'N/A')} | ${escapeHtml(entry.module || record.module_key || 'Finance')}</p>
                                    </div>
                                </div>
                                ${entry.reason ? `<p class="mt-2 rounded-lg border border-amber-100 bg-white px-3 py-2 text-xs text-amber-800">${escapeHtml(entry.reason)}</p>` : ''}
                                ${changeRows.rows.length ? `
                                    <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 bg-white">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-gray-50 text-[10px] uppercase tracking-[0.16em] text-gray-500">
                                                <tr>
                                                    <th class="px-3 py-2">Field</th>
                                                    <th class="px-3 py-2">Old Value</th>
                                                    <th class="px-3 py-2">New Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${changeRows.rows.map((row) => `
                                                    <tr class="border-t border-gray-100">
                                                        <td class="px-3 py-2 font-semibold text-gray-700">${escapeHtml(row.field)}</td>
                                                        <td class="px-3 py-2 text-gray-500 break-words">${escapeHtml(row.oldValue)}</td>
                                                        <td class="px-3 py-2 text-gray-900 break-words">${escapeHtml(row.newValue)}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                    ${changeRows.remaining ? `<p class="mt-2 text-xs text-gray-500">+${escapeHtml(String(changeRows.remaining))} more change(s)</p>` : ''}
                                ` : '<p class="mt-2 text-xs text-gray-500">No field-level changes were captured for this action.</p>'}
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    function renderCrfHistoryTimeline(record) {
        const entries = financeHistoryEntries(record, 10);

        if (!entries.length) {
            return `
                <div class="rounded-2xl border border-amber-100 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-[15px] font-semibold text-gray-900">Record History / Audit Trail</h4>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-semibold text-amber-700">0 entries</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500">No audit entries have been recorded yet.</p>
                </div>
            `;
        }

        return `
            <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h4 class="text-[15px] font-semibold text-gray-900">Record History / Audit Trail</h4>
                        <p class="mt-1 text-xs text-gray-500">A compact timeline of CRF changes.</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-amber-700 shadow-sm">${escapeHtml(String(entries.length))} latest</span>
                </div>
                <div class="mt-4 space-y-3">
                    ${entries.map((entry) => {
                        return `
                            <div class="relative overflow-hidden rounded-xl border border-amber-100 bg-white px-4 py-3 shadow-sm">
                                <span class="absolute left-0 top-0 h-full w-1 bg-amber-300"></span>
                                <div class="pl-3">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-gray-900">${escapeHtml(entry.action || 'Action')}</p>
                                            <p class="mt-1 text-xs text-gray-500">${escapeHtml(entry.changed_by || 'System')} | ${escapeHtml(entry.changed_at || 'N/A')} | ${escapeHtml(entry.module || record.module_key || 'Finance')}</p>
                                        </div>
                                    </div>
                                    ${entry.reason ? `<p class="mt-2 text-xs text-amber-800">${escapeHtml(entry.reason)}</p>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    function financeAttachmentEntries(record) {
        return Array.isArray(record?.attachments) ? record.attachments.filter((attachment) => attachment && typeof attachment === 'object') : [];
    }

    function financeAttachmentIsPdf(attachment) {
        const name = String(attachment?.name || attachment?.path || '').toLowerCase();
        const mime = String(attachment?.mime || '').toLowerCase();
        return name.endsWith('.pdf') || mime.includes('pdf');
    }

    function financeAttachmentIsImage(attachment) {
        const name = String(attachment?.name || attachment?.path || '').toLowerCase();
        const mime = String(attachment?.mime || '').toLowerCase();
        const category = String(attachment?.category || '').toLowerCase();
        return mime.startsWith('image/') || ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg'].some((ext) => name.endsWith(ext)) || category === 'asset photo';
    }

    function renderFinanceAttachmentCards(record) {
        const attachments = financeAttachmentEntries(record);

        if (!attachments.length) {
            return `
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-[15px] font-semibold text-gray-900">Attachments</h4>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-600">0 files</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500">No attachments have been uploaded yet.</p>
                </div>
            `;
        }

        const pdfAttachments = attachments.filter((attachment) => financeAttachmentIsPdf(attachment));
        const imageAttachments = attachments.filter((attachment) => financeAttachmentIsImage(attachment));
        const otherAttachments = attachments.filter((attachment) => !financeAttachmentIsPdf(attachment) && !financeAttachmentIsImage(attachment));

        const attachmentCard = (attachment, index, isPdf = false) => `
            <div class="rounded-xl border ${isPdf ? 'border-sky-200 bg-sky-50/60' : 'border-gray-200 bg-white'} px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 break-all">${escapeHtml(attachment.name || `Attachment ${index + 1}`)}</p>
                        <p class="mt-1 text-xs text-gray-500 break-all">${escapeHtml(attachment.path || '')}</p>
                        <p class="mt-1 text-[11px] text-gray-500">${escapeHtml(attachment.category || 'Supporting Document')}${attachment.uploaded_by ? ` • ${escapeHtml(attachment.uploaded_by)}` : ''}${attachment.uploaded_at ? ` • ${escapeHtml(attachment.uploaded_at)}` : ''}</p>
                    </div>
                    <span class="rounded-full border ${isPdf ? 'border-sky-200 text-sky-700' : 'border-gray-200 text-gray-600'} bg-white px-3 py-1 text-[11px] font-medium">${isPdf ? 'PDF' : 'File'}</span>
                </div>
            </div>
        `;

        const imageCard = (attachment, index) => {
            const previewUrl = attachment.image_data_uri || attachment.url || normalizeAttachmentUrl(attachment.path || '');
            return `
                <div class="overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/50 shadow-sm">
                    <div class="aspect-[4/3] bg-white">
                        <img src="${escapeHtml(previewUrl)}" alt="${escapeHtml(attachment.name || `Attachment ${index + 1}`)}" class="h-full w-full object-cover">
                    </div>
                    <div class="px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 break-all">${escapeHtml(attachment.name || `Attachment ${index + 1}`)}</p>
                                <p class="mt-1 text-xs text-gray-500 break-all">${escapeHtml(attachment.path || '')}</p>
                                <p class="mt-1 text-[11px] text-gray-500">${escapeHtml(attachment.category || 'Asset Photo')}${attachment.uploaded_by ? ` • ${escapeHtml(attachment.uploaded_by)}` : ''}${attachment.uploaded_at ? ` • ${escapeHtml(attachment.uploaded_at)}` : ''}</p>
                            </div>
                            <a href="${escapeHtml(attachment.url || normalizeAttachmentUrl(attachment.path || ''))}" target="_blank" class="rounded-full border border-emerald-200 bg-white px-3 py-1 text-[11px] font-medium text-emerald-700 hover:bg-emerald-50">Open</a>
                        </div>
                    </div>
                </div>
            `;
        };

        return `
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-slate-50 px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-[15px] font-semibold text-gray-900">Attachments</h4>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-600">${escapeHtml(String(attachments.length))} file${attachments.length === 1 ? '' : 's'}</span>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    ${imageAttachments.length ? `
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.18em] text-emerald-700">Photos</p>
                            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                                ${imageAttachments.map((attachment, index) => imageCard(attachment, index)).join('')}
                            </div>
                        </div>
                    ` : ''}
                    ${pdfAttachments.length ? `
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.18em] text-sky-700">PDF Attachments</p>
                            <div class="mt-3 space-y-3">
                                ${pdfAttachments.map((attachment, index) => attachmentCard(attachment, index, true)).join('')}
                            </div>
                        </div>
                    ` : ''}
                    ${otherAttachments.length ? `
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Other Attachments</p>
                            <div class="mt-3 space-y-3">
                                ${otherAttachments.map((attachment, index) => attachmentCard(attachment, index, false)).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    function renderCrfPreviewCallout(record) {
        const attachments = financeAttachmentEntries(record);
        const historyEntries = financeHistoryEntries(record, 10);

        return `
            <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.18em] text-amber-700">Cash Return Form</p>
                        <h4 class="mt-1 text-[15px] font-semibold text-gray-900">Attachments and history are part of this record</h4>
                        <p class="mt-2 text-sm text-gray-600">Use the tabs below to review uploaded files and the audit trail, or open the printable source when you need a full record view.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="window.financeModule.changePreviewTab('attachments')" class="rounded-full border border-amber-200 bg-white px-3 py-2 text-[11px] font-semibold text-amber-800 hover:bg-amber-50">
                            View Attachments
                        </button>
                        <button type="button" onclick="window.financeModule.changePreviewTab('details')" class="rounded-full border border-gray-200 bg-white px-3 py-2 text-[11px] font-semibold text-gray-700 hover:bg-gray-50">
                            View Details
                        </button>
                    </div>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-white bg-white/90 px-4 py-3 shadow-sm">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Attachments</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">${escapeHtml(String(attachments.length))}</p>
                        <p class="mt-1 text-sm text-gray-500">${attachments.length === 1 ? 'file is' : 'files are'} available for review.</p>
                    </div>
                    <div class="rounded-xl border border-white bg-white/90 px-4 py-3 shadow-sm">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">History Entries</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">${escapeHtml(String(historyEntries.length))}</p>
                        <p class="mt-1 text-sm text-gray-500">${historyEntries.length === 1 ? 'action' : 'actions'} are recorded in the audit trail.</p>
                    </div>
                </div>
            </div>
        `;
    }

    function renderAuditControlCallout(record) {
        const historyEntries = financeHistoryEntries(record, 10);
        const relationshipStatus = record?.relationship_status || record?.data?.relationship_status || 'In Progress';
        const isLocked = record?.can_edit === false || ['Disbursed', 'Completed', 'Closed'].includes(String(record?.status || '').trim());
        const lockBadge = isLocked
            ? '<span class="rounded-full bg-slate-900 px-3 py-1 text-[11px] font-semibold text-white">Read-only</span>'
            : '<span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold text-emerald-700">Editable</span>';

        return `
            <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-indigo-50 p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-600">Audit / Control Status</p>
                        <h4 class="mt-1 text-[15px] font-semibold text-gray-900">System-managed history and transaction controls</h4>
                        <p class="mt-2 text-sm text-gray-600">The system updates status, relationship progress, validations, and audit entries automatically. Users manage the transaction, not the control state.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        ${lockBadge}
                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-[11px] font-semibold text-indigo-700">${escapeHtml(relationshipStatus)}</span>
                    </div>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-white bg-white/90 px-4 py-3 shadow-sm">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">History Entries</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">${escapeHtml(String(historyEntries.length))}</p>
                        <p class="mt-1 text-sm text-gray-500">Actions are retained in the audit trail.</p>
                    </div>
                    <div class="rounded-xl border border-white bg-white/90 px-4 py-3 shadow-sm">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Current Status</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">${escapeHtml(record?.status || 'N/A')}</p>
                        <p class="mt-1 text-sm text-gray-500">Controlled by workflow progression.</p>
                    </div>
                    <div class="rounded-xl border border-white bg-white/90 px-4 py-3 shadow-sm">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Relationship</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">${escapeHtml(relationshipStatus)}</p>
                        <p class="mt-1 text-sm text-gray-500">Derived from linked records and state changes.</p>
                    </div>
                </div>
            </div>
        `;
    }

    function renderFinanceAttachmentSourceHtml(record) {
        const attachments = financeAttachmentEntries(record);

        if (!attachments.length) {
            return '';
        }

        return `
            <div class="finance-preview-box">
                <div class="finance-preview-section-title">Attachments</div>
                <div class="finance-preview-inner">
                    <table class="finance-preview-details">
                        <tr>
                            <td><p class="finance-preview-label">Count</p></td>
                            <td><p class="finance-preview-value">${escapeHtml(String(attachments.length))} file${attachments.length === 1 ? '' : 's'}</p></td>
                        </tr>
                        ${attachments.map((attachment) => `
                            <tr>
                                <td class="finance-preview-label">Attachment</td>
                                <td class="finance-preview-value">
                                    ${escapeHtml(attachment.name || attachment.path || 'Attachment')}
                                    ${attachment.category ? `<div class="finance-preview-muted">Category: ${escapeHtml(attachment.category)}</div>` : ''}
                                    ${attachment.uploaded_by ? `<div class="finance-preview-muted">Uploaded by: ${escapeHtml(attachment.uploaded_by)}</div>` : ''}
                                    ${attachment.uploaded_at ? `<div class="finance-preview-muted">Uploaded at: ${escapeHtml(attachment.uploaded_at)}</div>` : ''}
                                </td>
                            </tr>
                        `).join('')}
                    </table>
                </div>
            </div>
        `;
    }

    function renderFinanceHistorySourceHtml(record) {
        const entries = financeHistoryEntries(record, 8);

        if (!entries.length) {
            return '';
        }

        if (record?.module_key === 'crf') {
            return `
                <div class="finance-preview-box">
                    <div class="finance-preview-section-title">Record History / Audit Trail</div>
                    <div class="finance-preview-inner">
                        ${entries.map((entry) => {
                            return `
                                <div class="finance-preview-audit-entry" style="border-left:3px solid #f59e0b; padding-left:10px;">
                                    <p class="finance-preview-value">${escapeHtml(entry.action || 'Action')}</p>
                                    <p class="finance-preview-muted">${escapeHtml(entry.changed_by || 'System')} | ${escapeHtml(entry.changed_at || 'N/A')} | ${escapeHtml(entry.module || record.module_key || 'Finance')}</p>
                                    ${entry.reason ? `<p class="finance-preview-muted">Reason: ${escapeHtml(entry.reason)}</p>` : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        }

        return `
            <div class="finance-preview-box">
                <div class="finance-preview-section-title">Record History / Audit Trail</div>
                <div class="finance-preview-inner">
                    ${entries.map((entry) => {
                        const changeRows = financeHistoryChangeRows(entry, 5);
                        return `
                            <div class="finance-preview-audit-entry">
                                <p class="finance-preview-value">${escapeHtml(entry.action || 'Action')}</p>
                                <p class="finance-preview-muted">${escapeHtml(entry.changed_by || 'System')} | ${escapeHtml(entry.changed_at || 'N/A')} | ${escapeHtml(entry.module || record.module_key || 'Finance')}</p>
                                ${entry.reason ? `<p class="finance-preview-muted">Reason: ${escapeHtml(entry.reason)}</p>` : ''}
                                ${changeRows.rows.length ? `
                                    <table class="finance-preview-details">
                                        ${changeRows.rows.map((row) => `
                                            <tr>
                                                <td><p class="finance-preview-label">${escapeHtml(row.field)}</p></td>
                                                <td><p class="finance-preview-muted">Old: ${escapeHtml(row.oldValue)}</p></td>
                                                <td><p class="finance-preview-value">New: ${escapeHtml(row.newValue)}</p></td>
                                            </tr>
                                        `).join('')}
                                    </table>
                                    ${changeRows.remaining ? `<p class="finance-preview-muted">+${escapeHtml(String(changeRows.remaining))} more change(s)</p>` : ''}
                                ` : '<p class="finance-preview-muted">No field-level changes were captured for this action.</p>'}
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    function renderFinanceHistoryPrintHtml(record) {
        const entries = financeHistoryEntries(record, 10);

        if (!entries.length) {
            return '';
        }

        return `
            <div class="section">
                <h2>Record History / Audit Trail</h2>
                ${entries.map((entry) => {
                    const changeRows = financeHistoryChangeRows(entry, 5);
                    return `
                        <div class="audit-entry">
                            <div style="font-weight:700;">${escapeHtml(entry.action || 'Action')}</div>
                            <div style="color:#6b7280;">${escapeHtml(entry.changed_by || 'System')} | ${escapeHtml(entry.changed_at || 'N/A')} | ${escapeHtml(entry.module || record.module_key || 'Finance')}</div>
                            ${entry.reason ? `<div style="margin-top:3px; color:#92400e;">Reason: ${escapeHtml(entry.reason)}</div>` : ''}
                            ${changeRows.rows.map((row) => `
                                <div class="row">
                                    <div style="color:#6b7280;">${escapeHtml(row.field)}</div>
                                    <div style="text-align:right; max-width:68%;"><span style="color:#6b7280;">Old:</span> ${escapeHtml(row.oldValue)} <span style="color:#6b7280;">New:</span> ${escapeHtml(row.newValue)}</div>
                                </div>
                            `).join('')}
                            ${changeRows.remaining ? `<div style="margin-top:3px; color:#6b7280;">+${escapeHtml(String(changeRows.remaining))} more change(s)</div>` : ''}
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    function buildFinancePreviewSourceHtml(record, { templateMode = false } = {}) {
        const moduleConfig = getModuleConfig(record.module_key);
        const companyName = 'John Kelly & Company';
        const companyLegalName = 'JK&C INC.';
        const companyLogo = '/images/imaglogo.png';
        const data = record.data || {};
        const roleLabels = getApprovalRoutingRoleLabels(record);
        const summaryItems = record.module_key === 'pr'
            ? [
                ['Module', moduleConfig.label],
                ['Request Number', record.record_number || 'N/A'],
                ['Requestor', data.requestor || data.employee_name || 'N/A'],
                ['Priority', data.priority || 'N/A'],
                ['Date Needed', data.needed_date || 'N/A'],
                ['Amount', record.amount ? formatCurrency(record.amount) : 'N/A'],
                ['Record Date', record.record_date || 'N/A'],
                ['Workflow', record.workflow_status || 'N/A'],
                ['Approval', previewApprovalLabel(record)],
                ['Created By', record.user || 'N/A'],
                ['Submitted At', record.submitted_at || 'N/A'],
                ['Approved At', record.approved_at || 'N/A'],
            ]
            : [
                ['Module', moduleConfig.label],
                ['Record Number', record.record_number || 'N/A'],
                [moduleConfig.recordTitleLabel || 'Name', getVisibleRecordTitle(record) || ''],
                ['Record Date', record.record_date || 'N/A'],
                ['Record Time', data.transaction_time || 'N/A'],
                ...(shouldShowGenericAmount(record) ? [['Amount', record.amount ? formatCurrency(record.amount) : 'N/A']] : []),
                ['Status', record.status || 'N/A'],
                ['Workflow', record.workflow_status || 'N/A'],
                ['Approval', previewApprovalLabel(record)],
                ['Created By', record.user || 'N/A'],
                ['Submitted At', record.submitted_at || 'N/A'],
                ['Approved At', record.approved_at || 'N/A'],
                ...(record.module_key === 'ca' ? [
                    [roleLabels[0] || 'President', getApproverRoutingDisplayValue(record, 0)],
                    [roleLabels[1] || 'Treasurer', getApproverRoutingDisplayValue(record, 1)],
                ] : []),
                ...(record.module_key === 'lr' ? [
                    ['Attachments', getAttachmentSummaryValue(record)],
                ] : []),
            ];

        const templateSummaryItems = templateMode
            ? summaryItems.filter(([label]) => ['Module', 'Record Number', moduleConfig.recordTitleLabel || 'Name', 'Record Date'].includes(label))
            : summaryItems;

        const previewSections = templateMode ? getTemplatePreviewSections(record) : getModulePreviewSections(record);
        const modulePreviewHtml = previewSections.map((section) => {
            if (typeof section.renderer === 'function') {
                return section.renderer();
            }
            if (section.type === 'pda_payroll_period') {
                return renderPdaPayrollPeriodSourceHtml(record);
            }
            if (section.type === 'attachments') {
                return renderFinanceAttachmentSourceHtml(record);
            }
            if (section.type === 'asset_tag') {
                return `
                    <div class="finance-preview-box">
                        <div class="finance-preview-section-title">${escapeHtml(section.title || 'Asset Tag')}</div>
                        <div class="finance-preview-inner">
                            ${renderArfAssetTagCard(section.assetCode, section.location, section.serialNumber, section.barcodeSvg, { withPrintButton: true })}
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

        const detailsHtml = `
            <div class="finance-preview-box">
                <div class="finance-preview-section-title">Audit / Workflow</div>
                <div class="finance-preview-inner">
                    ${renderAuditControlCallout(record)}
                </div>
            </div>
            ${renderFinanceHistorySourceHtml(record)}
        `;

        const templateHtml = `
            <div class="finance-preview-box">
                <div class="finance-preview-section-title">Template Form</div>
                <div class="finance-preview-inner">
                    ${modulePreviewHtml || '<p class="finance-preview-muted">No template sections available.</p>'}
                </div>
            </div>
        `;

        return `
            <div class="finance-preview-source">
                <style>
                    .finance-preview-source {
                        width: 816px;
                        min-height: 1056px;
                        padding: 12px;
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
                        gap: 12px;
                        padding: 10px 12px;
                        border: 1px solid #dbe2ea;
                        border-radius: 8px 8px 0 0;
                        background: linear-gradient(90deg, #fff 0%, #fff 75%, #eff6ff 100%);
                    }
                    .finance-preview-brand {
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }
                    .finance-preview-logo {
                        width: 52px;
                        height: 52px;
                        border: 1px solid #dbeafe;
                        border-radius: 10px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: #fff;
                    }
                    .finance-preview-logo img {
                        width: 44px;
                        height: 44px;
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
                        font-size: 8px;
                        color: #6b7280;
                    }
                    .finance-preview-title {
                        font-size: 17px;
                        line-height: 1.1;
                        font-weight: 700;
                    }
                    .finance-preview-subtitle {
                        margin-top: 3px;
                        color: #1d4ed8;
                        font-weight: 700;
                        font-size: 9px;
                    }
                    .finance-preview-note {
                        margin-top: 2px;
                        color: #6b7280;
                        font-size: 8.5px;
                    }
                    .finance-preview-status {
                        text-align: right;
                    }
                    .finance-preview-status p {
                        margin: 0;
                        font-size: 9px;
                        font-weight: 700;
                        line-height: 1.5;
                    }
                    .finance-preview-section-title {
                        margin: 0;
                        padding: 6px 8px;
                        background: #1d4ed8;
                        color: #fff;
                        text-transform: uppercase;
                        letter-spacing: .24em;
                        font-size: 8.5px;
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
                        padding: 6px 7px;
                    }
                    .finance-preview-summary td { width: 25%; height: 42px; }
                    .finance-preview-label {
                        margin: 0;
                        text-transform: uppercase;
                        letter-spacing: .18em;
                        color: #6b7280;
                        font-size: 7.5px;
                    }
                    .finance-preview-value {
                        margin: 3px 0 0;
                        font-size: 10px;
                        font-weight: 700;
                        word-break: break-word;
                    }
                    .finance-preview-box {
                        border: 1px solid #dbe2ea;
                        border-top: 0;
                    }
                    .finance-preview-inner {
                        padding: 7px;
                    }
                    .finance-preview-lineitems th {
                        background: #f8fafc;
                        text-align: left;
                        font-size: 8px;
                    }
                    .finance-preview-lineitems td {
                        font-size: 8px;
                    }
                    .finance-preview-muted {
                        color: #6b7280;
                        font-size: 9px;
                    }
                    .finance-preview-two-col {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    .finance-preview-two-col td {
                        width: 50%;
                        border: 1px solid #dbe2ea;
                        padding: 6px 7px;
                        vertical-align: top;
                    }
                    .finance-preview-audit-entry {
                        border: 1px solid #dbe2ea;
                        border-radius: 8px;
                        padding: 7px;
                        margin-bottom: 7px;
                        break-inside: avoid;
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
                            <div class="finance-preview-note">${escapeHtml(record.record_number || 'N/A')}${getVisibleRecordTitle(record) ? ` - ${escapeHtml(getVisibleRecordTitle(record))}` : ''}</div>
                        </div>
                    </div>
                    ${templateMode ? '' : `
                        <div class="finance-preview-status">
                            <p class="finance-preview-status-title">Document Status</p>
                            <p>Workflow: ${escapeHtml(record.workflow_status || 'N/A')}</p>
                            <p>Approval: ${escapeHtml(record.approval_status || 'N/A')}</p>
                            <p>Status: ${escapeHtml(record.status || 'N/A')}</p>
                        </div>
                    `}
                </div>

                <table class="finance-preview-summary">
                    ${chunkArray(templateSummaryItems, 4).map((row) => `
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

                ${templateMode ? templateHtml : detailsHtml}
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
        source.innerHTML = buildFinancePreviewSourceHtml(record, { templateMode: currentPreviewTab === 'template' });
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

    function buildPreviewRefreshSignature(record) {
        const data = record || {};
        return [
            data.updated_at || '',
            data.supplier_completed_at || '',
            data.submitted_at || '',
            data.approved_at || '',
            data.status || '',
            data.workflow_status || '',
            data.approval_status || '',
            Array.isArray(data.attachments) ? data.attachments.length : 0,
        ].join('|');
    }

    function renderPreviewDocument(record) {
        const templateMode = currentPreviewTab === 'template';
        const attachmentMode = Boolean(currentPreviewAttachmentUrl);
        const attachmentName = templateMode
            ? 'Template PDF'
            : (currentPreviewAttachmentUrl
            ? (record.attachments || []).find((attachment) => (attachment.url || normalizeAttachmentUrl(attachment.path || '')) === currentPreviewAttachmentUrl)?.name || 'Attached PDF'
            : 'Finance Preview PDF');
        const holderLabel = templateMode ? 'Template PDF' : (attachmentMode ? 'Attachment PDF' : 'Finance PDF');
        const previewCacheKey = encodeURIComponent(buildPreviewRefreshSignature(record) || record.id);
        const attachmentPreviewUrl = currentPreviewAttachmentObjectUrl || currentPreviewAttachmentUrl;
        const previewUrl = templateMode
            ? `/finance/${record.id}/preview-pdf?template=1&t=${previewCacheKey}`
            : (attachmentPreviewUrl
                ? `${attachmentPreviewUrl}${attachmentPreviewUrl.startsWith('blob:') ? '' : `${attachmentPreviewUrl.includes('?') ? '&' : '?'}preview=${encodeURIComponent(String(currentPreviewAttachmentToken || Date.now()))}`}`
                : `/finance/${record.id}/preview-pdf?t=${previewCacheKey}`);
        const previewFooterHtml = !templateMode && currentPreviewTab === 'details' ? `
            <div class="mt-4 space-y-4">
                ${renderFinanceNotesCard(record, { context: 'details' })}
                ${renderAuditControlCallout(record)}
            </div>
        ` : '';
        const templateFooterHtml = templateMode ? `
            <div class="mt-4 space-y-4">
                ${renderFinanceNotesCard(record, { context: 'template' })}
            </div>
        ` : '';

        $('previewDocument').innerHTML = `
            <div class="mx-auto w-full max-w-[100%] overflow-hidden">
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
                            style="height: 980px; width: 100%; max-width: 100%; border: 0; background: #ffffff;"
                        ></iframe>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a id="financePreviewOpenLink" href="${escapeHtml(previewUrl)}" target="_blank" class="rounded-full border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Open ${escapeHtml(templateMode ? 'Template PDF' : (attachmentMode ? 'Attachment' : 'Preview'))}
                        </a>
                    </div>
                </div>
                ${previewFooterHtml}
                ${templateFooterHtml}
                <div id="financePreviewPdfSource" class="fixed top-0 left-0 w-[816px] bg-white" style="transform: translateX(-120vw); pointer-events: none;" aria-hidden="true"></div>
            </div>
        `;
    }

    function renderFinanceProgressTracker(record) {
        const steps = Array.isArray(record?.data?.transaction_progress) ? record.data.transaction_progress : [];
        if (!steps.length) return '';
        const relationshipStatus = record?.relationship_status || record?.data?.relationship_status || 'In Progress';

        return `
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h4 class="text-[15px] font-semibold text-gray-900">Transaction Progress</h4>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">${escapeHtml(relationshipStatus)}</span>
                </div>
                <div class="mt-4 space-y-2">
                    ${steps.map((step) => {
                        const state = step.state || (step.completed ? 'completed' : 'pending');
                        const badgeClass = state === 'completed'
                            ? 'bg-emerald-100 text-emerald-700'
                            : (state === 'current' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500');
                        const marker = state === 'completed' ? 'Done' : (state === 'current' ? 'Now' : 'Next');

                        return `
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2">
                                <span class="text-sm font-medium text-gray-900">${escapeHtml(step.label || 'Step')}</span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold ${badgeClass}">${marker}</span>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    function renderPreviewTabContent(record) {
        const moduleConfig = getModuleConfig(record.module_key);
        const attachments = Array.isArray(record.attachments) ? record.attachments : [];
        const templateSections = getTemplatePreviewSections(record);
        const templateSectionCards = templateSections
            .map((section) => renderPreviewSectionCard(record, moduleConfig, section))
            .filter(Boolean)
            .join('');
        const summaryRows = getFinancePreviewSummaryRows(record);
        const detailItems = '';

        const crfCalloutHtml = record.module_key === 'crf' ? renderCrfPreviewCallout(record) : '';

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
                                      data-preview-attachment-url="${escapeHtml(url)}"
                                      data-preview-attachment-name="${escapeHtml(attachment.name || `Attachment ${index + 1}`)}"
                                      class="w-full rounded-xl border px-4 py-3 text-left transition ${active ? 'border-blue-200 bg-white shadow-sm' : 'border-gray-200 bg-white hover:bg-gray-50'}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 break-all">${escapeHtml(attachment.name || `Attachment ${index + 1}`)}</p>
                                            <p class="mt-1 text-xs text-gray-500 break-all">${escapeHtml(attachment.path || '')}</p>
                                            <p class="mt-1 text-[11px] text-gray-500">${escapeHtml(attachment.category || 'Supporting Document')}${attachment.uploaded_by ? ` • ${escapeHtml(attachment.uploaded_by)}` : ''}${attachment.uploaded_at ? ` • ${escapeHtml(attachment.uploaded_at)}` : ''}</p>
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
                                <p class="mt-1 text-[11px] text-gray-500">${escapeHtml(attachment.category || 'Supporting Document')}${attachment.uploaded_by ? ` • ${escapeHtml(attachment.uploaded_by)}` : ''}${attachment.uploaded_at ? ` • ${escapeHtml(attachment.uploaded_at)}` : ''}</p>
                            </a>
                        `).join('') : '<p class="text-sm text-gray-400 italic">No other attachments uploaded.</p>'}
                    </div>
                </div>
            </div>
        `;
        const templateHtml = `
            <div class="space-y-4">
                <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                    <h4 class="text-[15px] font-semibold text-gray-900">
                        ${escapeHtml(record.module_key === 'supplier' ? 'Supplier Template' : `${record.module_label || 'Finance Record'} Template`)}
                    </h4>
                    <p class="mt-2 text-sm text-gray-600">
                        ${escapeHtml(record.module_key === 'supplier'
                            ? 'The template tab shows the supplier-specific sections used to build the completion preview.'
                            : 'The template tab shows the module-specific sections used to build the printable preview.')}
                    </p>
                    <div class="mt-4 rounded-xl border border-sky-100 bg-white px-4 py-3">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">PDF Source</p>
                        <a href="${escapeHtml(`/finance/${record.id}/preview-pdf?template=1`)}" target="_blank" class="mt-2 block break-all text-sm font-semibold text-sky-700 hover:text-sky-800">
                            Open ${escapeHtml(record.module_key === 'supplier' ? 'Supplier' : record.module_label || 'Finance')} Template PDF
                        </a>
                    </div>
                </div>
                <div class="space-y-4">
                    ${templateSectionCards}
                </div>
            </div>
        `;

        const moduleTrackingHtml = record.module_key === 'ca'
            ? renderCashAdvancePreviewPaymentManager(record)
            : '';
        const progressHtml = renderFinanceProgressTracker(record);
        const historyHtml = renderFinanceHistoryCards(record);
        const detailCards = [
            crfCalloutHtml,
            moduleTrackingHtml,
            progressHtml,
            historyHtml,
        ].filter(Boolean);
        const detailContent = detailCards
            .map((html, index) => {
                const spanClass = index >= 4 ? 'xl:col-span-2' : '';
                return `<div class="${spanClass}">${html}</div>`;
            })
            .join('');

          $('previewTabContent').innerHTML = currentPreviewTab === 'attachments'
            ? attachmentsHtml
            : (currentPreviewTab === 'template'
                ? templateHtml
                : `<div class="space-y-4">${detailContent}</div>`);

        bindPreviewAttachmentCardClicks();
      }

    function bindPreviewAttachmentCardClicks() {
        const container = $('previewTabContent');
        if (!container) return;

        container.querySelectorAll('[data-preview-attachment-url]').forEach((button) => {
            if (button.dataset.previewAttachmentBound === 'true') {
                return;
            }

            button.dataset.previewAttachmentBound = 'true';
            button.addEventListener('click', () => {
                const url = button.getAttribute('data-preview-attachment-url') || '';
                if (url) {
                    previewAttachment(url);
                }
            });
        });
    }

    function updatePreviewTabButtons() {
        const detailsButton = $('previewTabDetails');
        const attachmentsButton = $('previewTabAttachments');
        const templateButton = $('previewTabTemplate');
        if (!detailsButton || !attachmentsButton || !templateButton) return;

        const isDetails = currentPreviewTab === 'details';
        const isAttachments = currentPreviewTab === 'attachments';
        const isTemplate = currentPreviewTab === 'template';
        detailsButton.className = `rounded-full px-4 py-2 text-sm font-medium transition ${isDetails ? 'bg-white text-blue-700 shadow-sm border border-gray-200' : 'text-gray-600 hover:text-gray-900'}`;
        attachmentsButton.className = `rounded-full px-4 py-2 text-sm font-medium transition ${isAttachments ? 'bg-white text-blue-700 shadow-sm border border-gray-200' : 'text-gray-600 hover:text-gray-900'}`;
        templateButton.className = `rounded-full px-4 py-2 text-sm font-medium transition ${isTemplate ? 'bg-white text-blue-700 shadow-sm border border-gray-200' : 'text-gray-600 hover:text-gray-900'}`;
    }

    function changePreviewTab(tab) {
        currentPreviewTab = ['attachments', 'template'].includes(tab) ? tab : 'details';
        if (currentPreviewRecord) {
            if (currentPreviewTab === 'attachments') {
                const firstPdf = (currentPreviewRecord.attachments || []).find((attachment) => {
                    const name = String(attachment.name || attachment.path || '').toLowerCase();
                    const mime = String(attachment.mime || '').toLowerCase();
                    return name.endsWith('.pdf') || mime.includes('pdf');
                });
                currentPreviewAttachmentUrl = firstPdf ? (firstPdf.url || normalizeAttachmentUrl(firstPdf.path || '')) : '';
                currentPreviewAttachmentToken = 0;
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
        const workflowStatus = String(record?.workflow_status || '').trim().toLowerCase();
        const relationshipStatus = String(record?.data?.relationship_status || record?.relationship_status || '').trim().toLowerCase();
        const nextAction = String(record?.data?.next_action || record?.next_action || '').trim().toLowerCase();
        const isFinalWorkflow = ['completed', 'paid', 'disbursed', 'liquidated', 'closed'].includes(workflowStatus)
            || ['completed', 'paid', 'disbursed', 'liquidated', 'closed'].includes(relationshipStatus);
        const isApprovedWorkflow = ['approved', 'accepted'].includes(workflowStatus);
        const matchesAny = (value, list) => list.includes(value);
        if (!supplierPending && record.module_key === 'arf') {
            const assetCode = record.data?.asset_code || record.record_number || '';
            const location = record.data?.location || '';
            const serialNumber = record.data?.serial_number || '';
            actions.push(renderArfAssetTagPrintButton(assetCode, location, serialNumber, 'w-full border border-gray-300 rounded-md py-2 hover:bg-gray-50'));
        }

        if (record.module_key === 'arf') {
            const custodianId = Number(record.data?.custodian || 0) || 0;
            const assetAcknowledged = Boolean(record.data?.custodian_acknowledged_at);
            const currentUserIsCustodian = currentUserEmployeeId > 0 && currentUserEmployeeId === custodianId;
            const canManageAsset = Boolean(bootstrap.canApproveFinance || record.can_edit || record.can_review || currentUserIsCustodian);
            const isConsumableInventory = String(record.data?.item_classification || '').toLowerCase() === 'consumable inventory';

            if (currentUserIsCustodian && !assetAcknowledged) {
                actions.push(`<button type="button" onclick="window.financeModule.acknowledgeArfAsset(${record.id})" class="w-full bg-emerald-600 text-white rounded-md py-2 hover:bg-emerald-700">Acknowledge Receipt</button>`);
            }

            if (canManageAsset && isConsumableInventory) {
                actions.push(`<button type="button" onclick="window.financeModule.openArfInventoryMovementDialog(${record.id}, 'stock_in')" class="w-full bg-emerald-600 text-white rounded-md py-2 hover:bg-emerald-700">Stock In</button>`);
                actions.push(`<button type="button" onclick="window.financeModule.openArfInventoryMovementDialog(${record.id}, 'stock_out')" class="w-full border border-red-300 text-red-700 rounded-md py-2 hover:bg-red-50">Stock Out</button>`);
                actions.push(`<button type="button" onclick="window.financeModule.openArfInventoryMovementDialog(${record.id}, 'stock_transfer')" class="w-full border border-indigo-300 text-indigo-700 rounded-md py-2 hover:bg-indigo-50">Stock Transfer</button>`);
                actions.push(`<button type="button" onclick="window.financeModule.openArfInventoryMovementDialog(${record.id}, 'stock_return')" class="w-full border border-sky-300 text-sky-700 rounded-md py-2 hover:bg-sky-50">Stock Return</button>`);
                actions.push(`<button type="button" onclick="window.financeModule.openArfInventoryMovementDialog(${record.id}, 'stock_adjustment')" class="w-full border border-gray-300 text-gray-700 rounded-md py-2 hover:bg-gray-50">Stock Adjustment</button>`);
            } else if (canManageAsset) {
                actions.push(`<button type="button" onclick="window.financeModule.openArfTransferDialog(${record.id})" class="w-full bg-indigo-600 text-white rounded-md py-2 hover:bg-indigo-700">Transfer Asset</button>`);
                actions.push(`<button type="button" onclick="window.financeModule.recordArfAssetEvent(${record.id}, 'loss')" class="w-full border border-red-300 text-red-700 rounded-md py-2 hover:bg-red-50">Record Loss</button>`);
                actions.push(`<button type="button" onclick="window.financeModule.recordArfAssetEvent(${record.id}, 'damage')" class="w-full border border-amber-300 text-amber-700 rounded-md py-2 hover:bg-amber-50">Record Damage</button>`);
                actions.push(`<button type="button" onclick="window.financeModule.recordArfAssetEvent(${record.id}, 'return')" class="w-full border border-sky-300 text-sky-700 rounded-md py-2 hover:bg-sky-50">Record Return</button>`);
                actions.push(`<button type="button" onclick="window.financeModule.recordArfAssetEvent(${record.id}, 'disposal')" class="w-full border border-gray-300 text-gray-700 rounded-md py-2 hover:bg-gray-50">Record Disposal</button>`);
            }
        }

        if (record.module_key === 'ca' && !isFinalWorkflow) {
            const canOpenDisbursementVoucher = matchesAny(nextAction, ['create disbursement voucher'])
                || matchesAny(relationshipStatus, ['awaiting disbursement', 'pending disbursement', 'approved for release']);
            const canOpenLiquidationReport = matchesAny(nextAction, ['submit liquidation report'])
                || matchesAny(relationshipStatus, ['awaiting liquidation', 'awaiting liquidation approval', 'disbursed']);
            if (canOpenDisbursementVoucher) {
                actions.push(`<button type="button" onclick="window.financeModule.openDisbursementVoucherFromSource(${record.id}, event)" class="w-full bg-indigo-600 text-white rounded-md py-2 hover:bg-indigo-700">Create Disbursement Voucher</button>`);
            }
            if (canOpenLiquidationReport) {
                actions.push(`<button type="button" onclick="window.financeModule.openLiquidationReportFromSource(${record.id})" class="w-full bg-indigo-600 text-white rounded-md py-2 hover:bg-indigo-700">Create Liquidation Report</button>`);
            }
        }

        if ((record.module_key === 'po' || record.module_key === 'dv') && !isFinalWorkflow) {
            actions.push(`<button type="button" onclick="window.financeModule.openAssetRecordFromSource(${record.id})" class="w-full bg-indigo-600 text-white rounded-md py-2 hover:bg-indigo-700">Create Asset / Inventory File</button>`);
        }

        if (record.module_key === 'pr' && !isFinalWorkflow) {
            const canOpenPurchaseOrder = matchesAny(nextAction, ['create purchase order'])
                || matchesAny(relationshipStatus, ['awaiting purchase order', 'converted to purchase order', 'purchase order approved']);
            if (canOpenPurchaseOrder) {
                actions.push(`<button type="button" onclick="window.financeModule.openPurchaseOrderFromSource(${record.id})" class="w-full bg-indigo-600 text-white rounded-md py-2 hover:bg-indigo-700">Create Purchase Order</button>`);
            }
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

        if (record.can_approve || record.can_review) {
            actions.push(`<button type="button" onclick="window.financeModule.approveFinanceRecord(${record.id})" class="w-full bg-green-600 text-white rounded-md py-2 hover:bg-green-700">Approve</button>`);
        }

        if (record.can_hold) {
            actions.push(`<button type="button" onclick="window.financeModule.holdFinanceRecord(${record.id})" class="w-full bg-yellow-500 text-white rounded-md py-2 hover:bg-yellow-600">Hold</button>`);
        }

        if (record.can_revert || record.can_review) {
            actions.push(`<button type="button" onclick="window.financeModule.openFinanceRevertDialog(${record.id})" class="w-full bg-amber-500 text-white rounded-md py-2 hover:bg-amber-600">Return for Revision</button>`);
        }

        const disbursementButtonStatus = String(record?.data?.next_action || record?.next_action || '').trim();
        const disbursementRelationshipStatus = String(record?.relationship_status || record?.data?.relationship_status || '').trim();
        const showCreateDisbursementVoucher = ['po', 'err', 'pda', 'ibtf'].includes(record.module_key)
            && !isFinalWorkflow
            && (
                disbursementButtonStatus === 'Create Disbursement Voucher'
                || matchesAny(disbursementRelationshipStatus.toLowerCase(), ['awaiting disbursement voucher', 'awaiting disbursement', 'pending disbursement', 'approved for payment'])
                || isApprovedWorkflow
            );

        if (showCreateDisbursementVoucher) {
            actions.push(`<button type="button" onclick="window.financeModule.openDisbursementVoucherFromSource(${record.id}, event)" class="w-full bg-indigo-600 text-white rounded-md py-2 hover:bg-indigo-700">Create Disbursement Voucher</button>`);
        }

        if (record.can_archive) {
            actions.push(`<button type="button" onclick="window.financeModule.archiveFinanceRecord(${record.id})" class="w-full bg-gray-700 text-white rounded-md py-2 hover:bg-gray-800">Archive</button>`);
        }

        if (record.can_request_delete) {
            actions.push(`<button type="button" onclick="window.financeModule.requestDeleteFinanceRecord(${record.id})" class="w-full border border-red-300 text-red-700 rounded-md py-2 hover:bg-red-50">Request Delete</button>`);
        }

        if (record.supplier_completion_url) {
            actions.push(`<button type="button" onclick="window.financeModule.copySupplierLink('${escapeHtml(record.supplier_completion_url)}')" class="w-full border border-sky-300 text-sky-700 rounded-md py-2 hover:bg-sky-50">Copy Supplier Link</button>`);
        }

        $('previewActions').innerHTML = actions.join('');
    }

    function removeArfTransferDialog() {
        $('arfTransferDialog')?.remove();
    }

    function openArfTransferDialog(recordId) {
        const record = getRecordById(recordId);
        if (!record || record.module_key !== 'arf') return;

        removeArfTransferDialog();
        const currentCustodian = record.data?.custodian_name || getLookupLabel('employee', record.data?.custodian) || 'Select employee';
        const dialog = document.createElement('div');
        dialog.id = 'arfTransferDialog';
        dialog.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/40 px-4';
        dialog.innerHTML = `
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Transfer Asset</h3>
                        <p class="text-sm text-gray-500">Choose a new custodian from the employee list and add the reason for transfer.</p>
                    </div>
                    <button type="button" onclick="window.financeModule.closeArfTransferDialog()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Current Custodian</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(currentCustodian)}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">New Custodian</p>
                                <p class="text-xs text-gray-500">Select from the employee list.</p>
                            </div>
                            <button type="button" onclick="window.financeModule.openLookupSelector('__arf_transfer_custodian__', 'employee', 'New Custodian')" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">Choose Employee</button>
                        </div>
                        <input type="hidden" data-lookup-selector-hidden="__arf_transfer_custodian__">
                        <div data-lookup-selector-display="__arf_transfer_custodian__" class="mt-3 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-500">No employee selected.</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Transfer Reason</label>
                        <textarea data-arf-transfer-reason rows="4" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Explain why the asset is being transferred."></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4">
                    <button type="button" onclick="window.financeModule.closeArfTransferDialog()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" onclick="window.financeModule.submitArfTransferDialog(${record.id})" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Transfer Asset</button>
                </div>
            </div>
        `;
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                removeArfTransferDialog();
            }
        });
        document.body.appendChild(dialog);
    }

    function closeArfTransferDialog() {
        removeArfTransferDialog();
    }

    function removeArfInventoryMovementDialog() {
        $('arfInventoryMovementDialog')?.remove();
    }

    function movementDialogConfig(eventType) {
        const labels = {
            stock_in: { title: 'Stock In', button: 'Record Stock In', quantityLabel: 'Quantity Received', direction: 'increase', note: 'Receive new stock into inventory.' },
            stock_out: { title: 'Stock Out', button: 'Record Stock Out', quantityLabel: 'Quantity Released', direction: 'decrease', note: 'Record stock issued or consumed.' },
            stock_transfer: { title: 'Stock Transfer', button: 'Record Transfer', quantityLabel: 'Transfer Quantity (optional)', direction: 'transfer', note: 'Move stock to another location, department, or custodian.' },
            stock_return: { title: 'Stock Return', button: 'Record Return', quantityLabel: 'Quantity Returned', direction: 'increase', note: 'Return stock back into inventory.' },
            stock_adjustment: { title: 'Stock Adjustment', button: 'Record Adjustment', quantityLabel: 'Adjusted Quantity', direction: 'set', note: 'Set the current quantity to the counted amount.' },
        };

        return labels[eventType] || labels.stock_adjustment;
    }

    function openArfInventoryMovementDialog(recordId, eventType) {
        const record = getRecordById(recordId);
        if (!record || record.module_key !== 'arf') return;

        removeArfInventoryMovementDialog();
        const config = movementDialogConfig(eventType);
        const currentQuantity = record.data?.current_quantity || '0.00';
        const currentLocation = record.data?.location || '';
        const currentDepartment = record.data?.department || '';
        const currentCustodian = record.data?.custodian_name || getLookupLabel('employee', record.data?.custodian) || 'Select employee';
        const requiresTarget = eventType === 'stock_transfer';
        const dialog = document.createElement('div');
        dialog.id = 'arfInventoryMovementDialog';
        dialog.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/40 px-4';
        dialog.innerHTML = `
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(config.title)}</h3>
                        <p class="text-sm text-gray-500">${escapeHtml(config.note)}</p>
                    </div>
                    <button type="button" onclick="window.financeModule.closeArfInventoryMovementDialog()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Current Quantity</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(String(currentQuantity))}</p>
                    </div>
                    ${requiresTarget ? `
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <p class="text-sm font-semibold text-gray-900">Target Location</p>
                                <input type="text" data-arf-movement-target-location class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. Main Office Storage" value="${escapeHtml(currentLocation)}">
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <p class="text-sm font-semibold text-gray-900">Target Department</p>
                                <input type="text" data-arf-movement-target-department class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. Admin" value="${escapeHtml(currentDepartment)}">
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Target Custodian</p>
                                    <p class="text-xs text-gray-500">Choose from the employee list if the stock is moving to a new custodian.</p>
                                </div>
                                <button type="button" onclick="window.financeModule.openLookupSelector('__arf_inventory_target_custodian__', 'employee', 'Target Custodian')" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">Choose Employee</button>
                            </div>
                            <input type="hidden" data-lookup-selector-hidden="__arf_inventory_target_custodian__">
                            <div data-lookup-selector-display="__arf_inventory_target_custodian__" class="mt-3 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-500">${escapeHtml(currentCustodian)}</div>
                        </div>
                    ` : `
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="text-sm font-semibold text-gray-900">${escapeHtml(config.quantityLabel)}</p>
                            <input type="number" min="0" step="0.01" data-arf-movement-quantity class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Enter quantity">
                        </div>
                    `}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason / Notes</label>
                        <textarea data-arf-movement-reason rows="4" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Describe the movement."></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4">
                    <button type="button" onclick="window.financeModule.closeArfInventoryMovementDialog()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" onclick="window.financeModule.submitArfInventoryMovementDialog(${record.id}, ${JSON.stringify(eventType)})" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">${escapeHtml(config.button)}</button>
                </div>
            </div>
        `;
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                removeArfInventoryMovementDialog();
            }
        });
        document.body.appendChild(dialog);
    }

    function closeArfInventoryMovementDialog() {
        removeArfInventoryMovementDialog();
    }

    async function submitArfInventoryMovementDialog(recordId, eventType) {
        const dialog = $('arfInventoryMovementDialog');
        if (!dialog) return;

        const reason = String(dialog.querySelector('[data-arf-movement-reason]')?.value || '').trim();
        if (!reason) {
            alert('Please enter a reason or note for the stock movement.');
            return;
        }

        const formData = new FormData();
        formData.append('event_type', eventType);
        formData.append('event_reason', reason);

        if (eventType === 'stock_transfer') {
            const targetLocation = String(dialog.querySelector('[data-arf-movement-target-location]')?.value || '').trim();
            const targetDepartment = String(dialog.querySelector('[data-arf-movement-target-department]')?.value || '').trim();
            const targetCustodian = String(dialog.querySelector('[data-lookup-selector-hidden="__arf_inventory_target_custodian__"]')?.value || '').trim();

            if (!targetLocation && !targetDepartment && !targetCustodian) {
                alert('Please choose at least one transfer destination detail.');
                return;
            }

            if (targetLocation) formData.append('target_location', targetLocation);
            if (targetDepartment) formData.append('target_department', targetDepartment);
            if (targetCustodian) formData.append('target_custodian', targetCustodian);
        } else {
            const quantity = String(dialog.querySelector('[data-arf-movement-quantity]')?.value || '').trim();
            if (!quantity || Number(quantity) <= 0) {
                alert('Please enter a valid quantity.');
                return;
            }
            formData.append('movement_quantity', quantity);
            formData.append('movement_direction', eventType === 'stock_out'
                ? 'decrease'
                : (eventType === 'stock_adjustment' ? 'set' : 'increase'));
        }

        const res = await csrfFetch(`/finance/${recordId}/asset-event`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData,
        });
        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to record inventory movement.');
            return;
        }

        removeArfInventoryMovementDialog();
        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function submitArfTransferDialog(recordId) {
        const dialog = $('arfTransferDialog');
        const custodianId = String(dialog?.querySelector('[data-lookup-selector-hidden="__arf_transfer_custodian__"]')?.value || '').trim();
        const reason = String(dialog?.querySelector('[data-arf-transfer-reason]')?.value || '').trim();

        if (!custodianId) {
            alert('Please choose a new custodian from the employee list.');
            return;
        }

        if (!reason) {
            alert('Please enter a reason for the transfer.');
            return;
        }

        const formData = new FormData();
        formData.append('new_custodian_id', custodianId);
        formData.append('transfer_reason', reason);

        const res = await csrfFetch(`/finance/${recordId}/asset-transfer`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData,
        });
        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to transfer asset.');
            return;
        }

        removeArfTransferDialog();
        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function acknowledgeArfAsset(recordId) {
        const res = await csrfFetch(`/finance/${recordId}/asset-acknowledge`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
        });
        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to acknowledge asset receipt.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function recordArfAssetEvent(recordId, eventType) {
        const reason = prompt(`Enter the reason for recording this asset ${eventType}:`);
        if (reason === null) return;

        const trimmedReason = String(reason || '').trim();
        if (!trimmedReason) return;

        const formData = new FormData();
        formData.append('event_type', eventType);
        formData.append('event_reason', trimmedReason);

        const res = await csrfFetch(`/finance/${recordId}/asset-event`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData,
        });
        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to record asset event.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    function openPreview(id) {
        const record = getRecordById(id);
        if (!record) return;

        clearPreviewRefreshTimer();
        currentPreviewRecord = record;
        currentPreviewTab = 'template';
        currentPreviewAttachmentUrl = '';
        currentPreviewAttachmentToken = 0;
        revokeCurrentPreviewAttachmentObjectUrl();
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
        currentPreviewAttachmentToken = (currentPreviewAttachmentToken || 0) + 1;
        currentPreviewTab = 'attachments';
        revokeCurrentPreviewPdfObjectUrl();
        revokeCurrentPreviewAttachmentObjectUrl();
        renderPreviewDocument(currentPreviewRecord);

        const requestToken = currentPreviewAttachmentToken;
        const loadPreviewAttachment = async () => {
            try {
                const res = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
                if (!res.ok) {
                    throw new Error(`Attachment fetch failed with ${res.status}`);
                }

                const blob = await res.blob();
                const objectUrl = URL.createObjectURL(blob);

                if (!currentPreviewRecord || currentPreviewAttachmentToken !== requestToken) {
                    URL.revokeObjectURL(objectUrl);
                    return;
                }

                revokeCurrentPreviewAttachmentObjectUrl();
                currentPreviewAttachmentObjectUrl = objectUrl;
                renderPreviewDocument(currentPreviewRecord);
            } catch (error) {
                if (currentPreviewRecord && currentPreviewAttachmentToken === requestToken) {
                    renderPreviewDocument(currentPreviewRecord);
                }
                console.error('Unable to load attachment preview:', error);
            } finally {
                if (currentPreviewRecord && currentPreviewAttachmentToken === requestToken) {
                    renderPreviewTabContent(currentPreviewRecord);
                    renderPreviewActions(currentPreviewRecord);
                    updatePreviewTabButtons();
                    const frame = $('financePreviewPdfFrame');
                    if (frame && currentPreviewAttachmentObjectUrl) {
                        frame.src = currentPreviewAttachmentObjectUrl;
                    }
                    const openLink = $('financePreviewOpenLink');
                    if (openLink && currentPreviewAttachmentObjectUrl) {
                        openLink.href = currentPreviewAttachmentObjectUrl;
                    }
                }
            }
        };

        loadPreviewAttachment();
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
            const visibleTitle = getVisibleRecordTitle(record);
            const newOption = {
                id: record.id,
                label: [record.record_number || '', visibleTitle || ''].filter(Boolean).join(' - ') || record.record_number || `${record.module_label} #${record.id}`,
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
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (currentModuleKey === 'ca') {
            syncCashAdvanceHiddenRequestor();
            updateCashAdvanceReleaseValues();
        }
        const formData = new FormData(form);
        const token = currentCsrfToken();
        const moduleConfig = getModuleConfig(currentModuleKey);
        const currentRecord = currentEditRecordId ? getRecordById(currentEditRecordId) : null;
        const sendToSupplier = isSupplierDispatchLayout(currentRecord);

        if (moduleRequiresDualApproval(currentModuleKey) && !(currentModuleKey === 'supplier' && sendToSupplier)) {
            const firstApprover = String(formData.get('data[first_approver_user_id]') || '');
            const secondApprover = String(formData.get('data[second_approver_user_id]') || '');
            if (!firstApprover || !secondApprover) {
                alert('Please select both approvers before saving this request.');
                return;
            }
            if (firstApprover === secondApprover) {
                alert('Please select two different approvers.');
                return;
            }
        }

        if (token) {
            formData.set('_token', token);
        }
        formData.set('module_key', currentModuleKey);
        formData.set('data[completion_mode]', sendToSupplier ? 'send_to_supplier' : 'complete_internally');

        if (sendToSupplier && currentModuleKey === 'supplier') {
            const supplierEmail = String(formData.get('data[email_address]') || '').trim();
            if (!supplierEmail) {
                alert('Please enter the supplier email address.');
                return;
            }

            formData.set('record_number', $('recordNumberInput').value.trim() || generateModuleRecordNumber(currentModuleKey));
            formData.set('record_title', $('recordTitleInput').value.trim());
            formData.set('record_date', $('recordDateInput').value || new Date().toISOString().slice(0, 10));
            formData.set('amount', $('amountInput').value || '');
        } else {
            if (currentModuleKey === 'dv') {
                collectDvLineItems();
                updateDvNetAmount();
                formData.set('amount', formData.get('data[amount]') || $('amountInput').value || '');
            }

            formData.set('record_number', $('recordNumberInput').value.trim() || generateModuleRecordNumber(currentModuleKey));
            formData.set('record_title', $('recordTitleInput').value.trim());
            formData.set('record_date', $('recordDateInput').value);
            if (currentModuleKey !== 'dv') {
                formData.set('amount', $('amountInput').value);
            }

            if (!formData.get('record_number') || !formData.get('record_date')) {
                alert('Please fill in the record number and date.');
                return;
            }
        }

        const endpoint = currentEditRecordId ? `/finance/${currentEditRecordId}` : '/finance';
        if (currentEditRecordId) {
            formData.append('_method', 'PUT');
        }

        const res = await csrfFetch(endpoint, {
            method: 'POST',
            headers: {
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
        const res = await csrfFetch(`/finance/${id}/submit`, {
            method: 'POST',
            headers: {
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
        const res = await csrfFetch(`/finance/${id}/approve`, {
            method: 'POST',
            headers: {
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

    function removeFinanceHoldDialog() {
        $('financeHoldDialog')?.remove();
    }

    function openFinanceHoldDialog(id) {
        const record = getRecordById(id);
        if (!record) return;

        removeFinanceHoldDialog();
        const dialog = document.createElement('div');
        dialog.id = 'financeHoldDialog';
        dialog.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/40 px-4';
        dialog.innerHTML = `
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Put Record On Hold</h3>
                        <p class="text-sm text-gray-500">Enter the reason for placing this record on hold. The reason is saved in history and included in notifications.</p>
                    </div>
                    <button type="button" onclick="window.financeModule.closeFinanceHoldDialog()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Record</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(record.record_number || 'N/A')} - ${escapeHtml(getVisibleRecordTitle(record) || 'Record')}</p>
                        <p class="mt-1 text-xs text-gray-500">${escapeHtml(record.module_label || 'Finance')} | ${escapeHtml(record.status || 'N/A')}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason for Hold</label>
                        <textarea data-finance-hold-reason rows="5" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Explain why this record is being placed on hold."></textarea>
                        <p class="mt-2 text-xs text-gray-500">This reason is required and will appear in the audit trail and email notification.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4">
                    <button type="button" onclick="window.financeModule.closeFinanceHoldDialog()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" onclick="window.financeModule.submitFinanceHoldDialog(${record.id})" class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600">Hold Record</button>
                </div>
            </div>
        `;
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                removeFinanceHoldDialog();
            }
        });
        document.body.appendChild(dialog);
    }

    function closeFinanceHoldDialog() {
        removeFinanceHoldDialog();
    }

    async function submitFinanceHoldDialog(id) {
        const dialog = $('financeHoldDialog');
        if (!dialog) return;

        const note = String(dialog.querySelector('[data-finance-hold-reason]')?.value || '').trim();
        if (!note) {
            alert('Please enter a reason for placing this record on hold.');
            return;
        }

        const formData = new FormData();
        formData.append('review_note', note);

        const res = await csrfFetch(`/finance/${id}/hold`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to place record on hold.');
            return;
        }

        removeFinanceHoldDialog();
        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function holdFinanceRecord(id) {
        openFinanceHoldDialog(id);
    }

    function removeFinanceRevertDialog() {
        $('financeRevertDialog')?.remove();
    }

    function openFinanceRevertDialog(id) {
        const record = getRecordById(id);
        if (!record) return;

        removeFinanceRevertDialog();
        const dialog = document.createElement('div');
        dialog.id = 'financeRevertDialog';
        dialog.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/40 px-4';
        dialog.innerHTML = `
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Return for Revision</h3>
                        <p class="text-sm text-gray-500">Enter the reason for reverting this record. The reason is saved in history and included in notifications.</p>
                    </div>
                    <button type="button" onclick="window.financeModule.closeFinanceRevertDialog()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Record</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(record.record_number || 'N/A')} - ${escapeHtml(getVisibleRecordTitle(record) || 'Record')}</p>
                        <p class="mt-1 text-xs text-gray-500">${escapeHtml(record.module_label || 'Finance')} | ${escapeHtml(record.status || 'N/A')}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason for Reversion</label>
                        <textarea data-finance-revert-reason rows="5" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Explain why this record is being returned for revision."></textarea>
                        <p class="mt-2 text-xs text-gray-500">This reason is required and will appear in the audit trail and email notification.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4">
                    <button type="button" onclick="window.financeModule.closeFinanceRevertDialog()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" onclick="window.financeModule.submitFinanceRevertDialog(${record.id})" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">Return for Revision</button>
                </div>
            </div>
        `;
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                removeFinanceRevertDialog();
            }
        });
        document.body.appendChild(dialog);
    }

    function closeFinanceRevertDialog() {
        removeFinanceRevertDialog();
    }

    async function submitFinanceRevertDialog(id) {
        const dialog = $('financeRevertDialog');
        if (!dialog) return;

        const reason = String(dialog.querySelector('[data-finance-revert-reason]')?.value || '').trim();
        if (!reason) {
            alert('Please enter a reason for reverting this record.');
            return;
        }

        const formData = new FormData();
        formData.append('reason', reason);
        formData.append('review_note', reason);

        const res = await csrfFetch(`/finance/${id}/revert`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to revert record.');
            return;
        }

        removeFinanceRevertDialog();
        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
    }

    async function revertFinanceRecord(id) {
        openFinanceRevertDialog(id);
    }

    function removeFinanceNoteDialog() {
        $('financeNoteDialog')?.remove();
    }

    function openFinanceNoteDialog(id) {
        const record = getRecordById(id);
        if (!record) return;

        removeFinanceNoteDialog();
        const visibilityOptionsHtml = financeNoteVisibilityOptions.map((option) => `
            <option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>
        `).join('');
        const dialog = document.createElement('div');
        dialog.id = 'financeNoteDialog';
        dialog.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/40 px-4';
        dialog.innerHTML = `
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Add Finance Note</h3>
                        <p class="text-sm text-gray-500">Choose who can see this note before saving it to the record.</p>
                    </div>
                    <button type="button" onclick="window.financeModule.closeFinanceNoteDialog()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Record</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(record.record_number || 'N/A')} - ${escapeHtml(getVisibleRecordTitle(record) || 'Record')}</p>
                        <p class="mt-1 text-xs text-gray-500">${escapeHtml(record.module_label || 'Finance')} | ${escapeHtml(record.status || 'N/A')}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Note</label>
                        <textarea data-finance-note-body rows="6" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Write the note you want to store on this record."></textarea>
                        <p class="mt-2 text-xs text-gray-500">This note is saved in the record history and will only be visible to the authority you choose below.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Visible To</label>
                        <select data-finance-note-visibility class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                            ${visibilityOptionsHtml}
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4">
                    <button type="button" onclick="window.financeModule.closeFinanceNoteDialog()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" onclick="window.financeModule.submitFinanceNoteDialog(${record.id})" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">Save Note</button>
                </div>
            </div>
        `;
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                removeFinanceNoteDialog();
            }
        });
        document.body.appendChild(dialog);
    }

    function closeFinanceNoteDialog() {
        removeFinanceNoteDialog();
    }

    async function submitFinanceNoteDialog(id) {
        const dialog = $('financeNoteDialog');
        if (!dialog) return;

        const note = String(dialog.querySelector('[data-finance-note-body]')?.value || '').trim();
        const visibility = String(dialog.querySelector('[data-finance-note-visibility]')?.value || 'all').trim();

        if (!note) {
            alert('Please enter a note before saving.');
            return;
        }

        const formData = new FormData();
        formData.append('note', note);
        formData.append('visibility', visibility || 'all');

        const res = await csrfFetch(`/finance/${id}/notes`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to save note.');
            return;
        }

        removeFinanceNoteDialog();
        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
        showFinanceToast('Note saved.', 'success');
    }

    async function archiveFinanceRecord(id) {
        const res = await csrfFetch(`/finance/${id}/archive`, {
            method: 'POST',
            headers: {
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

    async function requestDeleteFinanceRecord(id) {
        const record = getRecordById(id);
        if (!record) return;

        removeFinanceDeleteDialog();
        const dialog = document.createElement('div');
        dialog.id = 'financeDeleteDialog';
        dialog.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/40 px-4';
        dialog.innerHTML = `
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Request Delete</h3>
                        <p class="text-sm text-gray-500">Enter the reason for deleting this record. The request is saved for admin review.</p>
                    </div>
                    <button type="button" onclick="window.financeModule.closeFinanceDeleteDialog()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-gray-500">Record</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(record.record_number || 'N/A')} - ${escapeHtml(getVisibleRecordTitle(record) || 'Record')}</p>
                        <p class="mt-1 text-xs text-gray-500">${escapeHtml(record.module_label || 'Finance')} | ${escapeHtml(record.status || 'N/A')}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason for Delete Request</label>
                        <textarea data-finance-delete-reason rows="5" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Explain why this record should be deleted."></textarea>
                        <p class="mt-2 text-xs text-gray-500">This reason is required and will appear in the audit trail and admin review notification.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4">
                    <button type="button" onclick="window.financeModule.closeFinanceDeleteDialog()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" onclick="window.financeModule.submitFinanceDeleteDialog(${record.id})" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Request Delete</button>
                </div>
            </div>
        `;
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                removeFinanceDeleteDialog();
            }
        });
        document.body.appendChild(dialog);
    }

    function removeFinanceDeleteDialog() {
        $('financeDeleteDialog')?.remove();
    }

    function closeFinanceDeleteDialog() {
        removeFinanceDeleteDialog();
    }

    async function submitFinanceDeleteDialog(id) {
        const dialog = $('financeDeleteDialog');
        if (!dialog) return;

        const note = String(dialog.querySelector('[data-finance-delete-reason]')?.value || '').trim();
        if (!note) {
            alert('Please enter a reason for deleting this finance record.');
            return;
        }

        const formData = new FormData();
        formData.append('review_note', note);

        const res = await csrfFetch(`/finance/${id}/request-delete`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Unable to request deletion.');
            return;
        }

        upsertFinanceRecord(data.data);
        refreshFinanceView();
        openPreview(data.data.id);
        showFinanceToast('Delete request sent to the admin finance dashboard.', 'success');
    }

    async function shareSupplierRecord(id) {
        const res = await csrfFetch(`/finance/${id}/share-supplier-link`, {
            method: 'POST',
            headers: {
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
        const res = await csrfFetch(`/finance/${id}/share-supplier-link`, {
            method: 'POST',
            headers: {
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

        const res = await csrfFetch(`/finance/${id}/supplier-email`, {
            method: 'POST',
            headers: {
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
                        @page { size: letter; margin: 10mm; }
                        body { font-family: Arial, sans-serif; margin: 0; color: #111827; font-size: 9px; line-height: 1.18; }
                        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
                        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 5px; }
                        .item { border: 1px solid #f3f4f6; border-radius: 6px; padding: 5px; }
                        .label { font-size: 7px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; }
                        .value { margin-top: 2px; font-weight: 600; }
                        .section { margin-top: 8px; }
                        .row { display: flex; justify-content: space-between; gap: 8px; border-bottom: 1px dashed #e5e7eb; padding: 3px 0; break-inside: avoid; }
                        .audit-entry { border: 1px solid #e5e7eb; border-radius: 7px; padding: 6px; margin-bottom: 6px; break-inside: avoid; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; gap:10px; border-bottom:1px solid #f3f4f6; padding-bottom:8px; margin-bottom:8px;">
                            <div>
                                <div style="font-size:7px; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Finance Operations</div>
                                <h1 style="margin:4px 0 2px; font-size:16px;">${escapeHtml(moduleConfig.label)}</h1>
                                <div style="color:#6b7280;">${escapeHtml(record.display_label || '')}</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:7px; color:#6b7280;">Workflow</div>
                                <div style="font-weight:600;">${escapeHtml(record.workflow_status || '')}</div>
                                <div style="font-size:7px; color:#6b7280; margin-top:5px;">Approval</div>
                                <div style="font-weight:600;">${escapeHtml(record.approval_status || '')}</div>
                            </div>
                        </div>

                        <div class="grid">
                            <div class="item"><div class="label">Number</div><div class="value">${escapeHtml(record.record_number || 'N/A')}</div></div>
                            <div class="item"><div class="label">${escapeHtml(getModuleConfig(record.module_key).recordTitleLabel || 'Name')}</div><div class="value">${escapeHtml(getVisibleRecordTitle(record))}</div></div>
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

                        ${renderFinanceHistoryPrintHtml(record)}
                    </div>
                    <script>window.onload = function(){ window.print(); };</script>
                </body>
            </html>
        `);
        doc.document.close();
    }

    function changeModule(moduleKey) {
        if (!moduleKeys.includes(moduleKey)) return;

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
        const recordParam = url.searchParams.get('record');

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
            if (recordParam && getRecordById(recordParam)) {
                openPreview(recordParam);
            }
        });
    }

    window.financeModule = {
        changeModule,
        changeWorkflow,
        changeSupplierCompletionMode,
        openDropdownSettings,
        closeDropdownSettings,
        changeDropdownSettingsModule,
        focusDropdownSettingsSection,
        resetDropdownSettingsField,
        resetAttachmentTypesSettings,
        resetLabelOverrides,
        resetSingleLabelOverride,
        saveDropdownSettings,
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
        openFinanceDraftFromSource,
        openPurchaseOrderFromSource,
        openLiquidationReportFromSource,
        openAssetRecordFromSource,
        openDisbursementVoucherFromSource,
        closeFinanceDrawer,
        openPreview,
        closePreview,
        getRecordById,
        saveFinanceRecord,
        submitFinanceRecord,
        approveFinanceRecord,
        holdFinanceRecord,
        openFinanceHoldDialog,
        closeFinanceHoldDialog,
        submitFinanceHoldDialog,
        openFinanceRevertDialog,
        closeFinanceRevertDialog,
        submitFinanceRevertDialog,
        openFinanceNoteDialog,
        closeFinanceNoteDialog,
        submitFinanceNoteDialog,
        revertFinanceRecord,
        archiveFinanceRecord,
        requestDeleteFinanceRecord,
        openFinanceDeleteDialog: requestDeleteFinanceRecord,
        closeFinanceDeleteDialog,
        submitFinanceDeleteDialog,
        shareSupplierRecord,
        resendSupplierForm,
        changeSupplierEmailAndResend,
        previewAttachment,
        copySupplierLink,
        printFinanceRecord,
        printArfAssetTag,
        scrollFinanceModuleTabs,
        recordCashAdvancePayment,
        recordCashAdvancePreviewPayment,
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
            closeDropdownSettings();
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
