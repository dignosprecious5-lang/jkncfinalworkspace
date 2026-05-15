<?php

namespace App\Http\Controllers;

use App\Mail\SupplierCompletionMail;
use App\Models\Contact;
use App\Models\EmployeePayrollProfile;
use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\PayrollPeriod;
use App\Models\PayrollSummary;
use App\Models\PayrollSummaryItem;
use App\Models\Setting;
use App\Services\PayrollCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    private const MODULES = [
        'supplier' => 'Supplier',
        'service' => 'Service',
        'product' => 'Product',
        'chart_account' => 'Chart of Accounts',
        'bank_account' => 'Bank Accounts',
        'pr' => 'Purchase Request',
        'po' => 'Purchase Order',
        'ca' => 'Cash Advance',
        'lr' => 'Liquidation Report',
        'err' => 'Expense Reimbursement Request',
        'dv' => 'Disbursement Voucher',
        'pda' => 'Payroll Disbursement Authorization',
        'crf' => 'Cash Return Form',
        'ibtf' => 'Interbank Fund Transfer Form',
        'arf' => 'Asset Registration Form',
    ];

    private const WORKFLOW_STATUSES = [
        'Uploaded',
        'Submitted',
        'Shared',
        'Accepted',
        'Reverted',
        'Archived',
    ];

    private const DROPDOWN_SETTINGS_KEY = 'finance_dropdown_options';

    private function canApproveFinance(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('approve_corporate');
    }

    private function canManageFinanceSettings(): bool
    {
        $user = Auth::user();

        return $user
            && ($user->hasPermission('approve_corporate') || $user->hasPermission('manage_users'));
    }

    private function financeDropdownSettings(): array
    {
        if (
            !Schema::hasTable('settings')
            || !Schema::hasColumn('settings', 'key')
            || !Schema::hasColumn('settings', 'value')
        ) {
            return [];
        }

        $setting = Setting::query()
            ->where('key', self::DROPDOWN_SETTINGS_KEY)
            ->first();

        if (!$setting || blank($setting->value)) {
            return [];
        }

        $decoded = json_decode((string) $setting->value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function sanitizeFinanceDropdownSettings(array $settings): array
    {
        $moduleKeys = $this->moduleKeys();
        $sanitized = [];

        foreach ($settings as $moduleKey => $fields) {
            if (!is_string($moduleKey) || !in_array($moduleKey, $moduleKeys, true) || !is_array($fields)) {
                continue;
            }

            foreach ($fields as $fieldName => $options) {
                if (!is_string($fieldName) || !preg_match('/^[A-Za-z0-9_]+$/', $fieldName) || !is_array($options)) {
                    continue;
                }

                $fieldOptions = [];

                foreach ($options as $option) {
                    $value = is_array($option) ? trim((string) ($option['value'] ?? '')) : trim((string) $option);
                    $label = is_array($option) ? trim((string) ($option['label'] ?? $value)) : $value;

                    if ($value === '') {
                        continue;
                    }

                    $fieldOptions[$value] = [
                        'value' => Str::limit($value, 255, ''),
                        'label' => Str::limit($label !== '' ? $label : $value, 255, ''),
                    ];
                }

                $sanitized[$moduleKey][$fieldName] = array_values($fieldOptions);
            }
        }

        return $sanitized;
    }

    private function moduleKeys(): array
    {
        return array_keys(self::MODULES);
    }

    private function moduleLabel(string $moduleKey): string
    {
        return self::MODULES[$moduleKey] ?? Str::headline($moduleKey);
    }

    private function moduleRecordTitleLabel(string $moduleKey): string
    {
        return match ($moduleKey) {
            'supplier' => 'Supplier Name',
            'service' => 'Service Name',
            'product' => 'Product Name',
            'chart_account' => 'Account Name',
            'bank_account' => 'Bank Account Name',
            'pr' => 'Request Title',
            'po' => 'Order Title',
            'ca' => 'Requestor',
            'lr' => 'Liquidating Person',
            'err' => 'Requestor',
            'dv' => 'Payee',
            'pda' => 'Payroll Period',
            'crf' => 'Returnee',
            'ibtf' => 'Transfer Title',
            'arf' => 'Asset Name',
            default => 'Record Name',
        };
    }

    private function financePdfImageDataUri(string $relativePath): ?string
    {
        $absolutePath = public_path($relativePath);

        if (!is_file($absolutePath)) {
            return null;
        }

        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function financeAttachmentUrl(?array $attachment): ?string
    {
        $path = trim((string) data_get($attachment, 'path', ''));

        if ($path === '') {
            return null;
        }

        return route('uploads.show', ['path' => $path]);
    }

    private function financePdfLookupLabel(array $lookupOptions, string $moduleKey, mixed $id): ?string
    {
        if (blank($id) || !array_key_exists($moduleKey, $lookupOptions)) {
            return null;
        }

        foreach ($lookupOptions[$moduleKey] as $option) {
            if ((string) ($option['id'] ?? '') === (string) $id) {
                return (string) ($option['label'] ?? $option['record_number'] ?? $option['id']);
            }
        }

        return null;
    }

    private function financePdfValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $flattened = array_filter(array_map(function ($item) {
                if (is_array($item)) {
                    return json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                return blank($item) ? null : (string) $item;
            }, $value));

            return $flattened ? implode(', ', $flattened) : 'N/A';
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? 'N/A' : $stringValue;
    }

    private function financeBarcodeSvg(?string $value): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return '';
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $text) ?: '');
        if ($normalized === '') {
            return '';
        }

        $seed = 0;
        foreach (str_split($normalized) as $index => $char) {
            $seed += ord($char) * ($index + 3);
        }

        $bits = '1010';
        foreach (str_split($normalized) as $char) {
            $code = ord($char) ^ ($seed & 0xff);
            $bits .= str_pad(decbin($code), 8, '0', STR_PAD_LEFT);
        }
        $bits .= '110101';

        $unit = 2;
        $quietZone = 10;
        $height = 56;
        $textY = 84;
        $cursor = $quietZone;
        $current = $bits[0] ?? '0';
        $runLength = 0;
        $rects = '';

        $flushRun = function () use (&$rects, &$cursor, &$runLength, &$current, $unit, $height) {
            if ($runLength > 0 && $current === '1') {
                $rects .= sprintf(
                    '<rect x="%d" y="8" width="%d" height="%d" fill="#111827" />',
                    $cursor,
                    $runLength * $unit,
                    $height
                );
            }

            $cursor += $runLength * $unit;
            $runLength = 0;
        };

        foreach (str_split($bits) as $bit) {
            if ($bit === $current) {
                $runLength++;
                continue;
            }

            $flushRun();
            $current = $bit;
            $runLength = 1;
        }

        $flushRun();

        $width = max(240, $cursor + $quietZone);
        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $centerX = (int) round($width / 2);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} 96" role="img" aria-label="Barcode for {$safeText}">
    <rect x="0" y="0" width="{$width}" height="96" rx="10" fill="#ffffff" />
    <rect x="0" y="0" width="{$width}" height="96" rx="10" fill="none" stroke="#e5e7eb" />
    <g>{$rects}</g>
    <text x="{$centerX}" y="{$textY}" text-anchor="middle" font-family="monospace" font-size="11" fill="#111827">{$safeText}</text>
</svg>
SVG;
    }

    private function financePreviewFieldValue(FinanceRecord $record, string $fieldName, array $lookupOptions): string
    {
        $data = $record->data ?? [];
        $value = data_get($data, $fieldName);

        if ($fieldName === 'requester_mode') {
            return match ($value) {
                'own_request' => 'Own Request',
                'request_for_another' => 'Request for Another',
                default => $this->financePdfValue($value),
            };
        }

        if (in_array($fieldName, ['completion_mode', 'vat_status', 'accreditation_status', 'tax_type', 'payment_type', 'mode_of_release', 'mode_of_return', 'reimbursement_mode', 'bank_status', 'account_type', 'account_status', 'service_status', 'product_status', 'normal_balance', 'variance_indicator', 'priority', 'request_type', 'release_schedule'], true)) {
            return $this->financePdfValue($value);
        }

        return match ($fieldName) {
            'requester_employee_id' => $this->financePdfLookupLabel($lookupOptions, 'employee', $value) ?: $this->financePdfValue($value),
            'supplier_id' => $this->financePdfLookupLabel($lookupOptions, 'supplier', $value) ?: $this->financePdfValue($value),
            'coa_id', 'parent_account_id', 'payroll_expense_coa_id', 'asset_coa_id', 'paid_through' => $this->financePdfLookupLabel($lookupOptions, 'chart_account', $value) ?: $this->financePdfValue($value),
            'bank_account_id', 'funding_bank_account_id', 'receiving_bank_account_id', 'source_bank_account_id', 'destination_bank_account_id' => $this->financePdfLookupLabel($lookupOptions, 'bank_account', $value) ?: $this->financePdfValue($value),
            'linked_pr_id' => $this->financePdfLookupLabel($lookupOptions, 'pr', $value) ?: $this->financePdfValue($value),
            'linked_po_id' => $this->financePdfLookupLabel($lookupOptions, 'po', $value) ?: $this->financePdfValue($value),
            'linked_ca_id' => $this->financePdfLookupLabel($lookupOptions, 'ca', $value) ?: $this->financePdfValue($value),
            'linked_lr_id' => $this->financePdfLookupLabel($lookupOptions, 'lr', $value) ?: $this->financePdfValue($value),
            'linked_dv_id' => $this->financePdfLookupLabel($lookupOptions, 'dv', $value) ?: $this->financePdfValue($value),
            'payroll_period_id' => $this->financePdfLookupLabel($lookupOptions, 'payroll_period', $value) ?: $this->financePdfValue($value),
            'source_document_id' => $this->financePdfLookupLabel($lookupOptions, (string) data_get($data, 'source_document_type', ''), $value) ?: $this->financePdfValue($value),
            'master_item_id' => $this->financePdfLookupLabel($lookupOptions, (string) data_get($data, 'master_item_type', 'product'), $value) ?: $this->financePdfValue($value),
            'linked_item_id' => $this->financePdfLookupLabel($lookupOptions, (string) data_get($data, 'linked_item_type', 'product'), $value) ?: $this->financePdfValue($value),
            default => $this->financePdfValue($value),
        };
    }

    private function financePreviewRow(FinanceRecord $record, array $lookupOptions, string $fieldName, ?string $label = null): array
    {
        return [
            'label' => $label ?: Str::headline(str_replace('_id', ' id', $fieldName)),
            'value' => $this->financePreviewFieldValue($record, $fieldName, $lookupOptions),
        ];
    }

    private function financeResolveModuleRecord(string $moduleKey, mixed $recordId): ?FinanceRecord
    {
        if (blank($recordId) || !is_numeric($recordId)) {
            return null;
        }

        return FinanceRecord::query()
            ->where('module_key', $moduleKey)
            ->find((int) $recordId);
    }

    private function financeLegacyLineItems(FinanceRecord $record): array
    {
        $data = $record->data ?? [];

        $items = match ($record->module_key) {
            'pr' => [[
                'item_module' => 'product',
                'item_record_id' => data_get($data, 'master_item_id'),
                'item_id' => data_get($data, 'master_item_id'),
                'description' => data_get($data, 'description_specification'),
                'category' => data_get($data, 'master_item_type'),
                'quantity' => data_get($data, 'quantity'),
                'amount' => data_get($data, 'unit_cost'),
                'subtotal' => data_get($data, 'estimated_total_cost'),
                'discount_amount' => data_get($data, 'discount_amount'),
                'shipping_amount' => data_get($data, 'shipping_amount'),
                'tax_amount' => data_get($data, 'tax_amount'),
                'wht_amount' => data_get($data, 'wht_amount'),
                'total' => data_get($data, 'estimated_total_cost'),
                'supplier_id' => data_get($data, 'supplier_id'),
            ]],
            'po' => [[
                'item_module' => data_get($data, 'linked_item_type'),
                'item_record_id' => data_get($data, 'linked_item_id'),
                'item_id' => data_get($data, 'linked_item_id'),
                'description' => data_get($data, 'purpose'),
                'category' => data_get($data, 'linked_item_type'),
                'quantity' => data_get($data, 'quantity'),
                'amount' => data_get($data, 'unit_cost'),
                'subtotal' => data_get($data, 'total_amount'),
                'discount_amount' => data_get($data, 'discount_amount'),
                'shipping_amount' => data_get($data, 'shipping_amount'),
                'tax_amount' => data_get($data, 'tax_amount'),
                'wht_amount' => data_get($data, 'wht_amount'),
                'total' => data_get($data, 'total_amount'),
                'supplier_id' => data_get($data, 'supplier_id'),
            ]],
            default => [],
        };

        return array_values(array_filter($items, function (array $item) {
            return collect($item)->contains(fn ($value) => !blank($value) && $value !== 0 && $value !== '0');
        }));
    }

    private function financeResolvedLineItems(FinanceRecord $record, array $lookupOptions): array
    {
        $data = $record->data ?? [];
        $rawItems = array_values(array_filter(
            (array) data_get($data, 'line_items', []),
            fn ($item) => is_array($item)
        ));

        if (!$rawItems) {
            $rawItems = $this->financeLegacyLineItems($record);
        }

        $lineItems = [];

        foreach ($rawItems as $item) {
            $itemModule = (string) data_get($item, 'item_module', '');
            if (!in_array($itemModule, ['product', 'service'], true)) {
                $itemModule = $record->module_key === 'po'
                    ? ((string) data_get($item, 'linked_item_type', 'product') ?: 'product')
                    : 'product';
            }

            $itemRecordId = data_get($item, 'item_record_id');
            if (blank($itemRecordId)) {
                $itemRecordId = data_get($item, 'linked_item_id', data_get($item, 'item_id'));
            }

            $itemRecord = $this->financeResolveModuleRecord($itemModule, $itemRecordId);
            $quantity = (float) data_get($item, 'quantity', 0);
            $amount = (float) data_get($item, 'amount', 0);
            $subtotal = (float) data_get($item, 'subtotal', $quantity * $amount);
            $discount = (string) data_get($item, 'discount', '0%');
            $discountAmount = (float) data_get($item, 'discount_amount', 0);
            $shippingAmount = (float) data_get($item, 'shipping_amount', 0);
            $taxType = (string) data_get($item, 'tax_type', 'N/A');
            $taxAmount = (float) data_get($item, 'tax_amount', 0);
            $whtAmount = (float) data_get($item, 'wht_amount', 0);
            $total = (float) data_get($item, 'total', $subtotal - $discountAmount + $shippingAmount + $taxAmount - $whtAmount);
            $supplierId = data_get($item, 'supplier_id')
                ?: data_get($itemRecord?->data, 'supplier_id')
                ?: ($record->module_key === 'pr' ? null : data_get($data, 'supplier_id'));
            $clientId = data_get($item, 'client_id');

            $lineItems[] = [
                'item_module' => $itemModule,
                'item_record_id' => $itemRecordId,
                'item' => $itemRecord?->record_title
                    ?: $this->financePdfLookupLabel($lookupOptions, $itemModule, $itemRecordId)
                    ?: $this->financePdfValue(data_get($item, 'item_id', $itemRecordId)),
                'description' => $this->financePdfValue(data_get($item, 'description')),
                'category' => $this->financePdfValue(data_get($item, 'category') ?: Str::headline($itemModule)),
                'quantity' => $this->financePdfValue(data_get($item, 'quantity')),
                'amount' => $this->financePdfValue(data_get($item, 'amount')),
                'subtotal' => number_format($subtotal, 2),
                'discount' => $this->financePdfValue($discount ?: '0%'),
                'discount_amount' => number_format($discountAmount, 2),
                'shipping_amount' => number_format($shippingAmount, 2),
                'tax_type' => $this->financePdfValue($taxType ?: 'N/A'),
                'tax_amount' => number_format($taxAmount, 2),
                'wht_amount' => number_format($whtAmount, 2),
                'total' => number_format($total, 2),
                'total_value' => $total,
                'supplier_id' => $supplierId,
                'supplier_label' => blank($supplierId) ? '' : (
                    $this->financePdfLookupLabel($lookupOptions, 'supplier', $supplierId)
                    ?: $this->financePdfValue($supplierId)
                ),
                'client_id' => $clientId,
                'client_label' => blank($clientId) ? '' : (
                    $this->financePdfLookupLabel($lookupOptions, 'client', $clientId)
                    ?: $this->financePdfValue($clientId)
                ),
            ];
        }

        return array_values(array_filter($lineItems, function (array $item) {
            return collect([
                $item['item'] ?? null,
                $item['description'] ?? null,
                $item['quantity'] ?? null,
                $item['amount'] ?? null,
                $item['total'] ?? null,
            ])->contains(fn ($value) => trim((string) $value) !== '' && $value !== 'N/A');
        }));
    }

    private function financePoSupplierGroups(array $lineItems): array
    {
        $groups = [];

        foreach ($lineItems as $item) {
            $groupKey = (string) ($item['supplier_id'] ?: $item['supplier_label'] ?: 'unspecified');

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'supplier_id' => $item['supplier_id'] ?: null,
                    'supplier_label' => $item['supplier_label'] ?: 'Unspecified Supplier',
                    'items' => [],
                    'group_total_value' => 0,
                ];
            }

            $groups[$groupKey]['items'][] = $item;
            $groups[$groupKey]['group_total_value'] += (float) ($item['total_value'] ?? 0);
        }

        return array_values(array_map(function (array $group) {
            $group['items_count'] = count($group['items']);
            $group['group_total'] = number_format((float) $group['group_total_value'], 2);

            return $group;
        }, $groups));
    }

    private function financePreviewSections(FinanceRecord $record, array $lookupOptions): array
    {
        $moduleKey = $record->module_key;
        $data = $record->data ?? [];
        $notesSection = [
            'type' => 'notes',
            'title' => 'Review Notes',
        ];

        $section = function (string $title, array $fields) use ($record, $lookupOptions): array {
            return [
                'type' => 'fields',
                'title' => $title,
                'rows' => array_map(fn ($field) => is_array($field)
                    ? $this->financePreviewRow($record, $lookupOptions, $field['name'], $field['label'] ?? null)
                    : $this->financePreviewRow($record, $lookupOptions, $field), $fields),
            ];
        };

        return match ($moduleKey) {
            'supplier' => data_get($data, 'completion_mode') === 'send_to_supplier' && blank($record->supplier_completed_at)
                ? []
                : [
                    $section('Supplier Profile', [
                        ['name' => 'completion_mode', 'label' => 'Completion Mode'],
                        ['name' => 'trade_name', 'label' => 'Trade Name'],
                        ['name' => 'supplier_type', 'label' => 'Supplier Type'],
                        ['name' => 'representative_full_name', 'label' => 'Representative Full Name'],
                        ['name' => 'designation', 'label' => 'Designation'],
                        ['name' => 'email_address', 'label' => 'Email Address'],
                        ['name' => 'phone_number', 'label' => 'Phone Number'],
                        ['name' => 'alternate_contact_number', 'label' => 'Alternate Contact Number'],
                    ]),
                $section('Business & Billing', [
                    ['name' => 'business_address', 'label' => 'Business Address'],
                    ['name' => 'billing_address', 'label' => 'Billing Address'],
                    ['name' => 'tin', 'label' => 'TIN'],
                    ['name' => 'vat_status', 'label' => 'VAT / Non-VAT'],
                    ['name' => 'payment_terms', 'label' => 'Payment Terms'],
                    ['name' => 'accreditation_status', 'label' => 'Accreditation Status'],
                ]),
                $section('Banking & Notes', [
                    ['name' => 'bank_name', 'label' => 'Bank Name'],
                    ['name' => 'bank_account_name', 'label' => 'Bank Account Name'],
                    ['name' => 'bank_account_number', 'label' => 'Bank Account Number'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'service' => [
                $section('Service Profile', [
                    ['name' => 'service_description', 'label' => 'Service Description'],
                    ['name' => 'products_services_provided', 'label' => 'Products / Services Provided'],
                    ['name' => 'supplier_id', 'label' => 'Supplier'],
                    ['name' => 'coa_id', 'label' => 'Account'],
                    ['name' => 'category', 'label' => 'Category'],
                    ['name' => 'unit_of_measure', 'label' => 'Unit of Measure'],
                    ['name' => 'default_cost', 'label' => 'Default Cost'],
                ]),
                $section('Classification & Notes', [
                    ['name' => 'tax_type', 'label' => 'Tax Type'],
                    ['name' => 'service_status', 'label' => 'Service Status'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'product' => [
                $section('Product Profile', [
                    ['name' => 'product_description', 'label' => 'Product Description'],
                    ['name' => 'supplier_id', 'label' => 'Supplier'],
                    ['name' => 'coa_id', 'label' => 'Account'],
                    ['name' => 'category', 'label' => 'Category'],
                    ['name' => 'unit_of_measure', 'label' => 'Unit of Measure'],
                    ['name' => 'default_cost', 'label' => 'Default Cost'],
                ]),
                $section('Classification & Notes', [
                    ['name' => 'tax_type', 'label' => 'Tax Type'],
                    ['name' => 'product_status', 'label' => 'Product Status'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'chart_account' => [
                $section('Account Profile', [
                    ['name' => 'account_description', 'label' => 'Account Description'],
                    ['name' => 'is_sub_account', 'label' => 'Sub-Account'],
                    ['name' => 'parent_account_id', 'label' => 'Main Account'],
                    ['name' => 'account_type', 'label' => 'Account Type'],
                    ['name' => 'account_group', 'label' => 'Account Group'],
                ]),
                $section('Bank Profile', [
                    ['name' => 'bank_account_name', 'label' => 'Bank Account Name'],
                    ['name' => 'bank_profile', 'label' => 'Bank Profile'],
                    ['name' => 'bank_account_number', 'label' => 'Bank Account Number'],
                ]),
                $section('Balance & Status', [
                    ['name' => 'normal_balance', 'label' => 'Normal Balance'],
                    ['name' => 'account_status', 'label' => 'Status'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'bank_account' => [
                $section('Bank Profile', [
                    ['name' => 'bank_name', 'label' => 'Bank Name'],
                    ['name' => 'branch', 'label' => 'Branch'],
                    ['name' => 'currency', 'label' => 'Currency'],
                    ['name' => 'account_type', 'label' => 'Account Type'],
                    ['name' => 'bank_status', 'label' => 'Status'],
                ]),
                $section('Accounting Link & Notes', [
                    ['name' => 'linked_coa_id', 'label' => 'Linked Chart of Account'],
                    ['name' => 'signatory_notes', 'label' => 'Signatory Notes'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'pr' => [
                $section('Request Details', [
                    ['name' => 'requesting_department', 'label' => 'Department'],
                    ['name' => 'request_type', 'label' => 'Type'],
                    ['name' => 'priority', 'label' => 'Priority'],
                    ['name' => 'needed_date', 'label' => 'Needed Date'],
                    ['name' => 'for_client', 'label' => 'Is this for a client?'],
                    ['name' => 'pr_reason_categories', 'label' => 'Reason (tick all that apply)'],
                ]),
                $section('Requester Details', [
                    ['name' => 'requester_mode', 'label' => 'Requester Option'],
                    ['name' => 'requester_employee_id', 'label' => 'Selected Employee'],
                    ['name' => 'requestor', 'label' => 'Employee Name'],
                    ['name' => 'employee_id', 'label' => 'Employee ID'],
                    ['name' => 'employee_email', 'label' => 'Email'],
                    ['name' => 'contact_number', 'label' => 'Contact #'],
                    ['name' => 'position', 'label' => 'Position'],
                    ['name' => 'department', 'label' => 'Department'],
                    ['name' => 'superior', 'label' => 'Superior'],
                    ['name' => 'superior_email', 'label' => 'Superior Email'],
                ]),
                $section('Vendor Details', [
                    ['name' => 'supplier_id', 'label' => 'Supplier'],
                    ['name' => 'new_vendor', 'label' => 'New Vendor?'],
                    ['name' => 'vendor_id_number', 'label' => 'Vendor ID Number'],
                    ['name' => 'vendors_tin', 'label' => 'Vendors TIN#'],
                    ['name' => 'company_name', 'label' => 'Company'],
                    ['name' => 'vendor_phone', 'label' => 'Phone Number'],
                    ['name' => 'vendor_email', 'label' => 'Email'],
                    ['name' => 'vendor_address', 'label' => 'Address'],
                    ['name' => 'city', 'label' => 'City'],
                    ['name' => 'province', 'label' => 'Province'],
                    ['name' => 'zip', 'label' => 'Zip'],
                ]),
                ['type' => 'line_items', 'title' => 'Items / Cost Details'],
                $section('Purpose & Notes', [
                    ['name' => 'purpose', 'label' => 'Purpose / Justification'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'po' => [
                $section('Order Details', [
                    ['name' => 'linked_pr_id', 'label' => 'Linked PR'],
                    ['name' => 'expected_delivery_date', 'label' => 'Expected Delivery Date'],
                    ['name' => 'delivery_address', 'label' => 'Delivery Address'],
                    ['name' => 'terms_and_conditions', 'label' => 'Terms and Conditions'],
                ]),
                ['type' => 'line_items', 'title' => 'Items / Cost Details'],
                $section('Purpose & Notes', [
                    ['name' => 'supplier_id', 'label' => 'Primary Supplier'],
                    ['name' => 'purpose', 'label' => 'Purpose'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'ca' => [
                $section('Request Details', [
                    ['name' => 'requester_mode', 'label' => 'Requester Option'],
                    ['name' => 'requestor', 'label' => 'Requestor'],
                    ['name' => 'needed_date', 'label' => 'Needed Date'],
                    ['name' => 'priority', 'label' => 'Priority'],
                    ['name' => 'cash_advance_type', 'label' => 'Cash Advance Type'],
                    ['name' => 'for_client', 'label' => 'For Client?'],
                    ['name' => 'purpose', 'label' => 'Justification / Business Need'],
                    ['name' => 'usage_categories', 'label' => 'Cash Advance Usage / Expense Categories'],
                    ['name' => 'other_business_purpose_specify', 'label' => 'Other Business Purpose - Specify'],
                    ['name' => 'other_expense_specify', 'label' => 'Other Expense - Specify'],
                    ['name' => 'client_names', 'label' => 'Client Name(s)'],
                ]),
                $section('Requester Details', [
                    ['name' => 'requester_employee_id', 'label' => 'Selected Employee'],
                    ['name' => 'employee_id', 'label' => 'Employee ID'],
                    ['name' => 'employee_name', 'label' => 'Employee Name'],
                    ['name' => 'employee_email', 'label' => 'Email'],
                    ['name' => 'contact_number', 'label' => 'Contact #'],
                    ['name' => 'position', 'label' => 'Position'],
                    ['name' => 'department', 'label' => 'Department'],
                    ['name' => 'superior', 'label' => 'Superior'],
                    ['name' => 'superior_email', 'label' => 'Superior Email'],
                ]),
                $section('Cash Advance Details', [
                    ['name' => 'amount_requested', 'label' => 'Amount Requested'],
                    ['name' => 'release_schedule', 'label' => 'Release Schedule'],
                    ['name' => 'release_count', 'label' => 'Number of Releases'],
                    ['name' => 'amount_per_release', 'label' => 'Amount per Release'],
                    ['name' => 'cash_release_date', 'label' => 'Cash Release Date'],
                    ['name' => 'cash_release_time', 'label' => 'Cash Release Time'],
                    ['name' => 'mode_of_release', 'label' => 'Mode of Release'],
                    ['name' => 'paid_through', 'label' => 'Paid Through'],
                ]),
                [
                    'type' => 'ca_payment_tracking',
                    'title' => 'Cash Advance Payment Tracking',
                ],
                $section('Declarations & Authorizations', [
                    ['name' => 'official_business_cash_advance', 'label' => 'Official Business Cash Advance'],
                    ['name' => 'employee_cash_advance_personal', 'label' => 'Employee Cash Advance - Personal Purpose'],
                    ['name' => 'liquidation_non_compliance', 'label' => 'Liquidation Non-Compliance'],
                    ['name' => 'automatic_salary_deduction_authorization', 'label' => 'Automatic Salary Deduction Authorization'],
                    ['name' => 'final_pay_deduction_authorization', 'label' => 'Final Pay Deduction Authorization'],
                    ['name' => 'policy_acknowledgment', 'label' => 'Policy Acknowledgment'],
                ]),
                $section('Funding & Notes', [
                    ['name' => 'bank_account_id', 'label' => 'Bank Account / Cash Source'],
                    ['name' => 'coa_id', 'label' => 'Account'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'lr' => [
                $section('Liquidation Details', [
                    ['name' => 'requester_mode', 'label' => 'Requester Option'],
                    ['name' => 'linked_ca_id', 'label' => 'CA Reference No.'],
                    ['name' => 'total_cash_advance', 'label' => 'CA Amount'],
                    ['name' => 'purpose', 'label' => 'Justification / Business Need'],
                    ['name' => 'for_client', 'label' => 'For Client?'],
                    ['name' => 'client_names', 'label' => 'Client Name(s)'],
                ]),
                $section('Requester Details', [
                    ['name' => 'requester_employee_id', 'label' => 'Selected Employee'],
                    ['name' => 'employee_id', 'label' => 'Employee ID'],
                    ['name' => 'employee_name', 'label' => 'Employee Name'],
                    ['name' => 'employee_email', 'label' => 'Email'],
                    ['name' => 'contact_number', 'label' => 'Contact #'],
                    ['name' => 'position', 'label' => 'Position'],
                    ['name' => 'department', 'label' => 'Department'],
                    ['name' => 'superior', 'label' => 'Superior'],
                    ['name' => 'superior_email', 'label' => 'Superior Email'],
                ]),
                [
                    'type' => 'liquidation_report',
                    'title' => 'Liquidation Report',
                ],
                [
                    'type' => 'line_items',
                    'title' => 'Liquidation Cost Details',
                ],
                [
                    'type' => 'cost_summary',
                    'title' => 'Liquidation Summary',
                ],
                $notesSection,
            ],
            'err' => [
                $section('Reimbursement Details', [
                    ['name' => 'requester_mode', 'label' => 'Requester Option'],
                    ['name' => 'requester_employee_id', 'label' => 'Selected Employee'],
                    ['name' => 'requestor', 'label' => 'Requestor'],
                    ['name' => 'linked_lr_id', 'label' => 'Linked LR'],
                    ['name' => 'expense_details', 'label' => 'Expense Details'],
                    ['name' => 'amount', 'label' => 'Amount'],
                    ['name' => 'reimbursement_payment_details', 'label' => 'Reimbursement Payment Details'],
                    ['name' => 'reimbursement_mode', 'label' => 'Mode of Reimbursement'],
                    ...match (data_get($data, 'reimbursement_mode')) {
                        'Cash' => [
                            ['name' => 'cash_receiver_name', 'label' => 'Name of Receiver'],
                        ],
                        'Bank Transfer' => [
                            ['name' => 'recipient_bank_account', 'label' => 'Bank Account'],
                            ['name' => 'recipient_bank_number', 'label' => 'Bank Number'],
                        ],
                        'Check' => [
                            ['name' => 'bank_account_id', 'label' => 'Bank Account Source'],
                        ],
                        default => [],
                    },
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'dv' => [
                $section('Voucher Details', [
                    ['name' => 'source_document_type', 'label' => 'Linked Source Document Type'],
                    ['name' => 'source_document_id', 'label' => 'Linked Source Document'],
                    ['name' => 'supplier_id', 'label' => 'Supplier'],
                    ['name' => 'amount', 'label' => 'Amount'],
                    ['name' => 'payment_type', 'label' => 'Payment Type'],
                    ['name' => 'disbursement_type', 'label' => 'Disbursement Type'],
                    ['name' => 'payment_date', 'label' => 'Payment Date'],
                    ['name' => 'due_date', 'label' => 'Due Date'],
                ]),
                $section('Accounting & Notes', [
                    ['name' => 'bank_account_id', 'label' => 'Bank Account'],
                    ['name' => 'coa_id', 'label' => 'Account'],
                    ['name' => 'fund_source', 'label' => 'Fund Source / Project'],
                    ['name' => 'department', 'label' => 'Department'],
                    ['name' => 'reference_number', 'label' => 'Reference Number'],
                    ['name' => 'purpose', 'label' => 'Purpose'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                [
                    'type' => 'dv_line_items',
                    'title' => 'Breakdown / Line Items',
                ],
                $section('Tax & Receipt', [
                    ['name' => 'withholding_tax', 'label' => 'Withholding Tax'],
                    ['name' => 'vat_amount', 'label' => 'VAT'],
                    ['name' => 'net_amount', 'label' => 'Net Amount'],
                    ['name' => 'currency', 'label' => 'Currency'],
                    ['name' => 'exchange_rate', 'label' => 'Exchange Rate'],
                    ['name' => 'received_by_name', 'label' => 'Received By'],
                    ['name' => 'received_by_signature', 'label' => 'Signature'],
                    ['name' => 'date_received', 'label' => 'Date Received'],
                ]),
                $notesSection,
            ],
            'pda' => [
                $section('Payroll Details', [
                    ['name' => 'payroll_period_id', 'label' => 'Payroll Period'],
                    ['name' => 'period_start', 'label' => 'Period Start'],
                    ['name' => 'period_end', 'label' => 'Period End'],
                    ['name' => 'payroll_start', 'label' => 'Payroll Start'],
                    ['name' => 'payroll_end', 'label' => 'Payroll End'],
                    ['name' => 'pay_date', 'label' => 'Pay Date'],
                    ['name' => 'total_payroll_amount', 'label' => 'Total Payroll Amount'],
                    ['name' => 'employee_count', 'label' => 'Employees Included'],
                    ['name' => 'basic_salary_total', 'label' => 'Basic Salary'],
                    ['name' => 'yearly_basic_total', 'label' => 'Yearly Basic'],
                    ['name' => 'daily_rate_total', 'label' => 'Daily Rate Total'],
                    ['name' => 'hourly_rate_total', 'label' => 'Hourly Rate Total'],
                    ['name' => 'minute_rate_total', 'label' => 'Minute Rate Total'],
                    ['name' => 'gross_pay_total', 'label' => 'Gross Pay'],
                    ['name' => 'benefits_total', 'label' => 'Benefits'],
                    ['name' => 'allowances_total', 'label' => 'Allowances'],
                    ['name' => 'deductions_total', 'label' => 'Deductions'],
                    ['name' => 'night_differential_total', 'label' => 'Night Differential'],
                    ['name' => 'holiday_pay_total', 'label' => 'Holiday Pay'],
                    ['name' => 'department', 'label' => 'Department / Coverage'],
                    ['name' => 'funding_bank_account_id', 'label' => 'Funding Bank Account'],
                    ['name' => 'payroll_expense_coa_id', 'label' => 'Payroll Expense Account'],
                ]),
                $section('Supporting Notes', [
                    ['name' => 'supporting_payroll_summary', 'label' => 'Supporting Payroll Summary'],
                    ['name' => 'employee_payroll_breakdown', 'label' => 'Employee Payroll Breakdown'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'crf' => [
                $section('Return Details', [
                    ['name' => 'requester_mode', 'label' => 'Requester Option'],
                    ['name' => 'requester_employee_id', 'label' => 'Selected Employee'],
                    ['name' => 'requestor', 'label' => 'Returnee'],
                    ['name' => 'linked_lr_id', 'label' => 'Linked LR'],
                    ['name' => 'amount_returned', 'label' => 'Amount Returned'],
                    ['name' => 'mode_of_return', 'label' => 'Mode of Return'],
                    ['name' => 'receiving_bank_account_id', 'label' => 'Receiving Bank / Cash Account'],
                    ['name' => 'coa_id', 'label' => 'Account'],
                ]),
                $section('Reference & Notes', [
                    ['name' => 'reference_number', 'label' => 'Reference Number'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'ibtf' => [
                $section('Transfer Details', [
                    ['name' => 'source_bank_account_id', 'label' => 'Source Bank Account'],
                    ['name' => 'destination_bank_account_id', 'label' => 'Destination Bank Account'],
                    ['name' => 'amount', 'label' => 'Amount'],
                    ['name' => 'reason', 'label' => 'Reason / Purpose'],
                ]),
                $section('Reference & Notes', [
                    ['name' => 'source_account_code', 'label' => 'Source Account Code'],
                    ['name' => 'destination_account_code', 'label' => 'Destination Account Code'],
                    ['name' => 'transfer_reference_number', 'label' => 'Transfer Reference Number'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            'arf' => [
                $section('Asset Details', [
                    ['name' => 'linked_po_id', 'label' => 'Linked PO'],
                    ['name' => 'linked_dv_id', 'label' => 'Linked DV'],
                    ['name' => 'supplier_id', 'label' => 'Supplier'],
                    ['name' => 'asset_code', 'label' => 'Asset Code'],
                    ['name' => 'asset_description', 'label' => 'Asset Description'],
                    ['name' => 'asset_category', 'label' => 'Asset Category'],
                    ['name' => 'serial_number', 'label' => 'Serial Number'],
                    ['name' => 'model', 'label' => 'Model'],
                ]),
                [
                    'type' => 'asset_tag',
                    'title' => 'Asset Tag',
                    'asset_code' => data_get($data, 'asset_code') ?: $record->record_number ?: 'N/A',
                    'location' => data_get($data, 'location') ?: 'N/A',
                    'serial_number' => data_get($data, 'serial_number') ?: 'N/A',
                    'barcode_svg' => $this->financeBarcodeSvg(data_get($data, 'asset_code') ?: $record->record_number ?: ''),
                ],
                $section('Valuation & Custody', [
                    ['name' => 'acquisition_cost', 'label' => 'Acquisition Cost'],
                    ['name' => 'acquisition_date', 'label' => 'Acquisition Date'],
                    ['name' => 'asset_coa_id', 'label' => 'Asset Account from Chart of Accounts'],
                    ['name' => 'location', 'label' => 'Location'],
                    ['name' => 'custodian', 'label' => 'Custodian'],
                    ['name' => 'useful_life', 'label' => 'Useful Life'],
                    ['name' => 'residual_value', 'label' => 'Residual Value'],
                    ['name' => 'remarks', 'label' => 'Remarks'],
                ]),
                $notesSection,
            ],
            default => [],
        };
    }

    private function financeCashAdvancePaymentTracking(FinanceRecord $record): ?array
    {
        if ($record->module_key !== 'ca') {
            return null;
        }

        $data = $record->data ?? [];
        $amount = (float) data_get($data, 'amount_requested', $record->amount ?? 0);
        $releaseCount = max((int) data_get($data, 'release_count', 1), 1);
        $amountPerRelease = (float) data_get($data, 'amount_per_release', $releaseCount > 0 ? $amount / $releaseCount : $amount);
        $entries = collect((array) data_get($data, 'ca_payment_entries', []))
            ->filter(fn ($entry) => is_array($entry))
            ->values();
        $entriesByRelease = $entries->groupBy(fn (array $entry) => (int) data_get($entry, 'release_no', 0));

        $rows = collect(range(1, $releaseCount))->map(function (int $releaseNo) use ($releaseCount, $amount, $amountPerRelease, $entriesByRelease, $data) {
            $releaseEntries = $entriesByRelease->get($releaseNo, collect());
            $paidAmount = $releaseEntries->sum(fn (array $entry) => (float) data_get($entry, 'payment_amount', 0));
            $scheduledAmount = $releaseNo === $releaseCount
                ? max($amount - ($amountPerRelease * ($releaseCount - 1)), 0)
                : $amountPerRelease;
            $latestPayment = $releaseEntries->last() ?: [];
            $status = $paidAmount >= $scheduledAmount && $scheduledAmount > 0
                ? 'Paid'
                : ($paidAmount > 0 ? 'Partial' : 'Pending');

            return [
                'no' => $releaseNo,
                'scheduled_date' => $releaseNo === 1 ? (data_get($data, 'cash_release_date') ?: '-') : '-',
                'scheduled_amount' => number_format($scheduledAmount, 2),
                'paid_amount' => number_format($paidAmount, 2),
                'payment_date' => data_get($latestPayment, 'payment_date') ?: '-',
                'payment_remarks' => $releaseEntries->pluck('payment_remarks')->filter()->implode(' | '),
                'status' => $status,
            ];
        })->values();

        $totalPaid = $entries->sum(fn (array $entry) => (float) data_get($entry, 'payment_amount', 0));
        $remainingBalance = max($amount - $totalPaid, 0);
        $paidCount = $rows->where('status', 'Paid')->count();
        $status = $remainingBalance <= 0 && $amount > 0 ? 'Fully Released' : ($totalPaid > 0 ? 'Partially Released' : 'Pending Release');

        return [
            'summary' => [
                ['label' => 'Per Release', 'value' => number_format($amountPerRelease, 2)],
                ['label' => 'Total Cash Advance', 'value' => number_format($amount, 2)],
                ['label' => 'Released / Paid', 'value' => number_format($totalPaid, 2)],
                ['label' => 'Remaining for Release', 'value' => number_format($remainingBalance, 2)],
                ['label' => 'Releases Paid', 'value' => $paidCount],
                ['label' => 'Releases Remaining', 'value' => max($releaseCount - $paidCount, 0)],
                ['label' => 'Status', 'value' => $status],
            ],
            'rows' => $rows->all(),
        ];
    }

    private function financePdfContext(FinanceRecord $record, bool $includeLogo = true): array
    {
        $data = $record->data ?? [];
        $lookupOptions = $this->resolveLookupOptions();
        $moduleLabel = $this->moduleLabel($record->module_key);
        $recordTitleLabel = $this->moduleRecordTitleLabel($record->module_key);
        $companyName = 'John Kelly & Company';
        $companyLegalName = 'JK&C INC.';
        $companyLogo = $includeLogo ? $this->financePdfImageDataUri('images/imaglogo.png') : null;
        $summaryCards = [
            ['label' => 'Module', 'value' => $moduleLabel],
            ['label' => 'Record Number', 'value' => $this->normalizeFinanceRecordNumber($record->module_key, $record->record_number)],
            ['label' => $recordTitleLabel, 'value' => $record->record_title ?: 'N/A'],
            ['label' => 'Record Date', 'value' => optional($record->record_date)->format('Y-m-d') ?: 'N/A'],
            ['label' => 'Record Time', 'value' => data_get($data, 'transaction_time') ?: 'N/A'],
            ['label' => 'Amount', 'value' => $record->amount !== null ? number_format((float) $record->amount, 2) : 'N/A'],
            ['label' => 'Status', 'value' => $record->status ?: 'N/A'],
            ['label' => 'Workflow', 'value' => $record->workflow_status ?: 'N/A'],
            ['label' => 'Approval', 'value' => $record->approval_status ?: 'N/A'],
            ['label' => 'Created By', 'value' => $record->user ?: 'N/A'],
            ['label' => 'Submitted At', 'value' => optional($record->submitted_at)->format('Y-m-d H:i:s') ?: 'N/A'],
            ['label' => 'Approved At', 'value' => optional($record->approved_at)->format('Y-m-d H:i:s') ?: 'N/A'],
        ];

        $lineItems = $this->financeResolvedLineItems($record, $lookupOptions);
        $lineItemsTotal = array_reduce($lineItems, function (float $carry, array $item) {
            return $carry + (float) ($item['total_value'] ?? 0);
        }, 0.0);
        $poSupplierGroups = $record->module_key === 'po'
            ? $this->financePoSupplierGroups($lineItems)
            : [];

        $costSummary = match ($record->module_key) {
            'lr' => [
                ['label' => 'Subtotal', 'value' => $data['subtotal'] ?? '0.00'],
                ['label' => 'Discount Total', 'value' => $data['discount_total'] ?? '0.00'],
                ['label' => 'Tax Total', 'value' => $data['tax_total'] ?? '0.00'],
                ['label' => 'Shipping Total', 'value' => $data['shipping_total'] ?? '0.00'],
                ['label' => 'WHT Total', 'value' => $data['wht_total'] ?? '0.00'],
                ['label' => 'Grand Total', 'value' => $data['grand_total'] ?? $record->amount ?? '0.00'],
            ],
            default => [
                ['label' => 'Subtotal', 'value' => $data['subtotal'] ?? $record->amount ?? '0.00'],
                ['label' => 'Discount', 'value' => $data['discount'] ?? '0%'],
                ['label' => 'Discount Amount', 'value' => $data['discount_amount'] ?? '0.00'],
                ['label' => 'Shipping', 'value' => $data['shipping_amount'] ?? '0.00'],
                ['label' => 'Tax (VAT/Non-VAT/N/A)', 'value' => $data['tax_type'] ?? 'N/A'],
                ['label' => 'Tax Amount', 'value' => $data['tax_amount'] ?? '0.00'],
                ['label' => 'WHT', 'value' => $data['wht_amount'] ?? '0.00'],
                ['label' => 'Grand Total', 'value' => $data['grand_total'] ?? $record->amount ?? '0.00'],
            ],
        };

        $attachments = array_values(array_map(function ($attachment) {
            $attachment = is_array($attachment) ? $attachment : [];
            $attachment['url'] = $this->financeAttachmentUrl($attachment);
            $attachment['download_url'] = $attachment['url'] ? $attachment['url'] . '?download=1' : null;

            return $attachment;
        }, array_filter((array) ($record->attachments ?? []), fn ($attachment) => !blank(data_get($attachment, 'name')) || !blank(data_get($attachment, 'path')))));

        $detailRows = $record->module_key === 'arf'
            ? [
                $this->financePreviewRow($record, $lookupOptions, 'asset_code', 'Asset Code'),
                $this->financePreviewRow($record, $lookupOptions, 'linked_po_id', 'Linked PO'),
                $this->financePreviewRow($record, $lookupOptions, 'linked_dv_id', 'Linked DV'),
                $this->financePreviewRow($record, $lookupOptions, 'supplier_id', 'Supplier'),
                $this->financePreviewRow($record, $lookupOptions, 'asset_description', 'Asset Description'),
                $this->financePreviewRow($record, $lookupOptions, 'asset_category', 'Asset Category'),
                $this->financePreviewRow($record, $lookupOptions, 'serial_number', 'Serial Number'),
                $this->financePreviewRow($record, $lookupOptions, 'model', 'Model'),
                $this->financePreviewRow($record, $lookupOptions, 'acquisition_cost', 'Acquisition Cost'),
                $this->financePreviewRow($record, $lookupOptions, 'acquisition_date', 'Acquisition Date'),
                $this->financePreviewRow($record, $lookupOptions, 'asset_coa_id', 'Asset Account from Chart of Accounts'),
                $this->financePreviewRow($record, $lookupOptions, 'location', 'Location'),
                $this->financePreviewRow($record, $lookupOptions, 'custodian', 'Custodian'),
                $this->financePreviewRow($record, $lookupOptions, 'useful_life', 'Useful Life'),
                $this->financePreviewRow($record, $lookupOptions, 'residual_value', 'Residual Value'),
                $this->financePreviewRow($record, $lookupOptions, 'remarks', 'Remarks'),
            ]
            : [];

        $liquidationReport = $record->module_key === 'lr'
            ? [
                'ca_reference_no' => $this->financePdfLookupLabel($lookupOptions, 'ca', data_get($data, 'linked_ca_id')) ?: data_get($data, 'linked_ca_id') ?: 'N/A',
                'ca_amount' => data_get($data, 'total_cash_advance') ?: '0.00',
                'for_client' => data_get($data, 'for_client') ?: 'N/A',
                'client_names' => data_get($data, 'client_names') ?: 'N/A',
                'line_items_total' => number_format($lineItemsTotal, 2),
                'actual_expenses' => data_get($data, 'actual_expenses') ?: '0.00',
                'variance' => data_get($data, 'variance') ?: '0.00',
                'variance_indicator' => data_get($data, 'variance_indicator') ?: 'Balanced',
                'purpose' => data_get($data, 'purpose') ?: 'N/A',
                'remarks' => data_get($data, 'remarks') ?: 'N/A',
                'employee_name' => data_get($data, 'employee_name') ?: data_get($data, 'employee_id') ?: 'N/A',
                'status_label' => $this->financePdfValue(data_get($data, 'variance_indicator') ?: 'Balanced'),
                'calculation_label' => 'Line Items Total',
                'calculation_formula' => 'Line Items Total = Sum of all line item totals',
            ]
            : null;

        return [
            'companyName' => $companyName,
            'companyLegalName' => $companyLegalName,
            'companyLogo' => $companyLogo,
            'moduleLabel' => $moduleLabel,
            'recordTitleLabel' => $recordTitleLabel,
            'record' => $record,
            'assetTag' => $record->module_key === 'arf' ? [
                'asset_code' => data_get($data, 'asset_code') ?: $record->record_number ?: 'N/A',
                'location' => data_get($data, 'location') ?: 'N/A',
                'serial_number' => data_get($data, 'serial_number') ?: 'N/A',
                'barcode_svg' => $this->financeBarcodeSvg(data_get($data, 'asset_code') ?: $record->record_number ?: ''),
            ] : null,
            'summaryCards' => $summaryCards,
            'detailRows' => $detailRows,
            'previewSections' => $this->financePreviewSections($record, $lookupOptions),
            'lineItems' => $lineItems,
            'poSupplierGroups' => $poSupplierGroups,
            'costSummary' => $costSummary,
            'liquidationReport' => $liquidationReport,
            'cashAdvancePaymentTracking' => $this->financeCashAdvancePaymentTracking($record),
            'attachments' => $attachments,
            'chartAccountLabel' => $this->financePdfLookupLabel($lookupOptions, 'chart_account', data_get($data, 'coa_id')) ?: data_get($data, 'coa_id') ?: 'N/A',
        ];
    }

    private function acceptedRecordQuery(string $moduleKey, array $dataConstraints = [])
    {
        $query = FinanceRecord::query()
            ->where('module_key', $moduleKey)
            ->where(function ($statusQuery) {
                $statusQuery->where('workflow_status', 'Accepted')
                    ->orWhere('approval_status', 'Approved');
            });

        foreach ($dataConstraints as $field => $value) {
            $query->where("data->{$field}", $value);
        }

        return $query;
    }

    private function canEditRecord(FinanceRecord $record): bool
    {
        if ($this->canApproveFinance()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true);
    }

    private function canSubmitRecord(FinanceRecord $record): bool
    {
        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true);
    }

    private function canShareSupplierRecord(FinanceRecord $record): bool
    {
        return $record->module_key === 'supplier'
            && (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true)
            && data_get($record->data, 'completion_mode') === 'send_to_supplier';
    }

    private function canManageSupplierCompletion(FinanceRecord $record): bool
    {
        return $record->module_key === 'supplier'
            && data_get($record->data, 'completion_mode') === 'send_to_supplier'
            && ($this->canApproveFinance() || (int) $record->submitted_by === (int) Auth::id());
    }

    private function supplierCompletionEmailAddress(FinanceRecord $record): ?string
    {
        $email = trim((string) data_get($record->data, 'email_address', ''));

        return blank($email) ? null : $email;
    }

    private function ensureSupplierCompletionLink(FinanceRecord $record): string
    {
        $record->update([
            'share_token' => $record->share_token ?: Str::random(64),
            'shared_at' => now(),
            'workflow_status' => 'Shared',
            'approval_status' => 'Pending Supplier Completion',
        ]);

        return route('finance.supplier.completion', $record->fresh()->share_token);
    }

    private function sendSupplierCompletionEmail(FinanceRecord $record): string
    {
        $email = $this->supplierCompletionEmailAddress($record);

        if (blank($email)) {
            throw ValidationException::withMessages([
                'data.email_address' => 'Supplier email address is required to send the completion form.',
            ]);
        }

        $link = $this->ensureSupplierCompletionLink($record);
        $freshRecord = $record->fresh();

        Mail::to($email)->send(
            new SupplierCompletionMail($freshRecord, $link)
        );

        return $link;
    }

    private function optionLabel(FinanceRecord $record): string
    {
        $parts = array_filter([
            $this->normalizeFinanceRecordNumber($record->module_key, $record->record_number),
            $record->record_title,
        ]);

        if ($parts) {
            return implode(' - ', $parts);
        }

        $dataTitle = data_get($record->data, 'title')
            ?: data_get($record->data, 'business_name')
            ?: data_get($record->data, 'account_name')
            ?: data_get($record->data, 'service_name')
            ?: data_get($record->data, 'product_name')
            ?: data_get($record->data, 'payee')
            ?: data_get($record->data, 'supplier_name');

        if ($dataTitle) {
            return (string) $dataTitle;
        }

        return $this->moduleLabel($record->module_key) . ' #' . $record->id;
    }

    private function recordPrefixForModule(string $moduleKey): string
    {
        return match ($moduleKey) {
            'supplier' => 'SUP',
            'service' => 'SRV',
            'product' => 'PRD',
            'chart_account' => 'COA',
            'bank_account' => 'BA',
            'pr' => 'PR',
            'po' => 'PO',
            'ca' => 'CA',
            'lr' => 'LR',
            'err' => 'ERR',
            'dv' => 'DV',
            'pda' => 'PDA',
            'crf' => 'CRF',
            'ibtf' => 'IBTF',
            'arf' => 'ARF',
            default => Str::upper(Str::substr($moduleKey ?: 'FIN', 0, 5)),
        };
    }

    private function normalizeFinanceRecordNumber(string $moduleKey, ?string $recordNumber): string
    {
        $value = trim((string) $recordNumber);
        $prefix = $this->recordPrefixForModule($moduleKey);

        if ($value === '') {
            return $prefix . '-' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^([A-Z0-9]{2,10})-(\d+)$/i', $value, $matches)) {
            $normalizedPrefix = strtoupper($matches[1]);
            $numeric = substr(str_pad($matches[2], 5, '0', STR_PAD_LEFT), -5);

            return $normalizedPrefix . '-' . $numeric;
        }

        if (preg_match('/^\d+$/', $value)) {
            return $prefix . '-' . substr(str_pad($value, 5, '0', STR_PAD_LEFT), -5);
        }

        return $value;
    }

    private function resolveLookupOptions(): array
    {
        $options = [];

        foreach (self::MODULES as $moduleKey => $label) {
            $options[$moduleKey] = $this->acceptedRecordQuery($moduleKey)
                ->orderByDesc('record_date')
                ->orderByDesc('created_at')
                ->get()
                ->map(function (FinanceRecord $record) {
                    $option = [
                        'id' => $record->id,
                        'label' => $this->optionLabel($record),
                        'record_number' => $record->record_number,
                        'record_title' => $record->record_title,
                    ];

                    if ($record->module_key === 'chart_account') {
                        $option['account_type'] = data_get($record->data, 'account_type');
                        $option['account_group'] = data_get($record->data, 'account_group');
                        $option['account_description'] = data_get($record->data, 'account_description');
                    }

                    if ($record->module_key === 'product') {
                        $option['data'] = [
                            'product_description' => data_get($record->data, 'product_description'),
                            'category' => data_get($record->data, 'category'),
                            'default_cost' => data_get($record->data, 'default_cost'),
                            'supplier_id' => data_get($record->data, 'supplier_id'),
                            'tax_type' => data_get($record->data, 'tax_type'),
                            'unit_of_measure' => data_get($record->data, 'unit_of_measure'),
                        ];
                    }

                    if ($record->module_key === 'service') {
                        $option['data'] = [
                            'service_description' => data_get($record->data, 'service_description'),
                            'products_services_provided' => data_get($record->data, 'products_services_provided'),
                            'category' => data_get($record->data, 'category'),
                            'default_cost' => data_get($record->data, 'default_cost'),
                            'supplier_id' => data_get($record->data, 'supplier_id'),
                            'tax_type' => data_get($record->data, 'tax_type'),
                            'unit_of_measure' => data_get($record->data, 'unit_of_measure'),
                        ];
                    }

                    return $option;
                })
                ->values();
        }

        $options['dv_ca'] = $this->acceptedRecordQuery('dv', ['source_document_type' => 'ca'])
            ->orderByDesc('record_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FinanceRecord $record) => [
                'id' => $record->id,
                'label' => $this->optionLabel($record),
                'record_number' => $record->record_number,
                'record_title' => $record->record_title,
            ])
            ->values();

        $options['lr_overage'] = $this->acceptedRecordQuery('lr', ['variance_indicator' => 'Overage'])
            ->orderByDesc('record_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FinanceRecord $record) => [
                'id' => $record->id,
                'label' => $this->optionLabel($record),
                'record_number' => $record->record_number,
                'record_title' => $record->record_title,
            ])
            ->values();

        $options['lr_shortage'] = $this->acceptedRecordQuery('lr', ['variance_indicator' => 'Shortage'])
            ->orderByDesc('record_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FinanceRecord $record) => [
                'id' => $record->id,
                'label' => $this->optionLabel($record),
                'record_number' => $record->record_number,
                'record_title' => $record->record_title,
            ])
            ->values();

        $options['client'] = Contact::query()
            ->orderBy('first_name')
            ->get([
                'id',
                'salutation',
                'first_name',
                'middle_initial',
                'middle_name',
                'last_name',
                'name_extension',
                'email',
                'phone',
                'contact_address',
                'company_name',
                'company_address',
                'position',
                'customer_type',
                'client_status',
                'cif_no',
                'tin',
            ])
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'label' => $this->contactDisplayName($contact, includeEmail: true),
                'record_title' => $this->contactDisplayName($contact),
                'full_name' => $this->contactDisplayName($contact),
                'email' => $contact->email,
                'phone' => $contact->phone,
                'contact_address' => $contact->contact_address,
                'company_name' => $contact->company_name,
                'company_address' => $contact->company_address,
                'position' => $contact->position,
                'customer_type' => $contact->customer_type,
                'client_status' => $contact->client_status,
                'cif_no' => $contact->cif_no,
                'tin' => $contact->tin,
            ])
            ->values();

        $options['employee'] = Schema::hasTable('employees')
            ? Employee::query()
                ->when(Schema::hasTable('departments'), fn ($query) => $query->with('department'))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (Employee $employee) => $this->employeeRequesterOption($employee))
                ->values()
            : collect();

        $options['payroll_period'] = PayrollPeriod::query()
            ->orderByDesc('period_start')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PayrollPeriod $period) => $this->payrollPeriodSnapshot($period))
            ->values();

        return $options;
    }

    private function employeeRequesterOption(Employee $employee): array
    {
        $employeeName = trim(collect([$employee->first_name, $employee->last_name])->filter()->implode(' '));
        $departmentName = $employee->relationLoaded('department')
            ? (string) ($employee->department?->department_name ?? '')
            : '';
        $superior = $employee->relationLoaded('department')
            ? (string) ($employee->department?->department_head ?? '')
            : '';
        $superiorEmail = $this->resolveEmployeeSuperiorEmail($superior);
        $labelParts = array_filter([
            $employee->employee_code,
            $employeeName ?: null,
            $employee->email ? "({$employee->email})" : null,
        ]);

        return [
            'id' => $employee->id,
            'label' => implode(' - ', $labelParts) ?: 'Employee #' . $employee->id,
            'record_title' => $employeeName,
            'full_name' => $employeeName,
            'employee_id' => $employee->employee_code,
            'employee_code' => $employee->employee_code,
            'employee_email' => $employee->email,
            'email' => $employee->email,
            'contact_number' => $employee->phone_number,
            'phone_number' => $employee->phone_number,
            'phone' => $employee->phone_number,
            'position' => $employee->position,
            'department' => $departmentName,
            'department_name' => $departmentName,
            'superior' => $superior,
            'superior_email' => $superiorEmail,
            'address' => $employee->address,
        ];
    }

    private function payrollPeriodSnapshot(PayrollPeriod $period, bool $persistMissingSummaries = false): array
    {
        $summaries = PayrollSummary::query()
            ->with(['employee', 'payrollLevel.salaryGrade', 'items'])
            ->where('payroll_period_id', $period->id)
            ->get();

        if ($persistMissingSummaries || $summaries->isEmpty()) {
            $generatedSummaries = $this->generatePayrollSummariesForPeriod($period, $persistMissingSummaries);
            if ($generatedSummaries->isNotEmpty()) {
                $summaries = $generatedSummaries;
            }
        }

        $employeeLines = $summaries->map(function (PayrollSummary $summary) {
            $breakdown = $summary->breakdown_json ?? [];
            $employee = $summary->employee;
            $level = $summary->payrollLevel;
            $grade = $level?->salaryGrade;

            return [
                'employee_id' => $summary->employee_id,
                'employee_code' => $employee?->employee_code,
                'employee_name' => trim((string) ($employee?->full_name ?: $employee?->name ?: '')),
                'salary_grade' => $grade?->name,
                'salary_grade_code' => $grade?->code,
                'payroll_level' => $level?->level_name,
                'computation_type' => $summary->computation_type,
                'work_schedule' => data_get($breakdown, 'work_schedule') ?: $level?->work_schedule_label,
                'hours_per_day' => (float) data_get($breakdown, 'hours_per_day', $level?->hours_per_day ?: 0),
                'monthly_basic_salary' => (float) data_get($breakdown, 'monthly_basic_salary', 0),
                'yearly_basic_salary' => (float) data_get($breakdown, 'yearly_basic_salary', 0),
                'applicable_daily_rate' => (float) data_get($breakdown, 'applicable_daily_rate', 0),
                'hourly_rate' => (float) data_get($breakdown, 'hourly_rate', 0),
                'minute_rate' => (float) data_get($breakdown, 'minute_rate', 0),
                'gross_pay' => (float) $summary->gross_pay,
                'total_benefits' => (float) $summary->total_benefits,
                'total_allowances' => (float) $summary->total_allowances,
                'total_deductions' => (float) $summary->total_deductions,
                'night_differential_amount' => (float) $summary->night_differential_amount,
                'holiday_pay_amount' => (float) $summary->holiday_pay_amount,
                'net_pay' => (float) $summary->net_pay,
                'status' => $summary->status,
            ];
        })->values();

        $periodLabel = trim(sprintf(
            '%s (%s to %s)',
            $period->name,
            optional($period->period_start)->format('Y-m-d') ?: 'N/A',
            optional($period->period_end)->format('Y-m-d') ?: 'N/A'
        ));

        return [
            'id' => $period->id,
            'label' => $periodLabel,
            'record_title' => $period->name,
            'period_start' => optional($period->period_start)->format('Y-m-d'),
            'period_end' => optional($period->period_end)->format('Y-m-d'),
            'payroll_start' => optional($period->payroll_start)->format('Y-m-d'),
            'payroll_end' => optional($period->payroll_end)->format('Y-m-d'),
            'pay_date' => optional($period->pay_date)->format('Y-m-d'),
            'dispute_start' => optional($period->dispute_start)->format('Y-m-d'),
            'dispute_end' => optional($period->dispute_end)->format('Y-m-d'),
            'status' => $period->status,
            'employee_count' => $employeeLines->count(),
            'basic_salary_total' => round((float) $employeeLines->sum('monthly_basic_salary'), 2),
            'yearly_basic_total' => round((float) $employeeLines->sum('yearly_basic_salary'), 2),
            'daily_rate_total' => round((float) $employeeLines->sum('applicable_daily_rate'), 2),
            'hourly_rate_total' => round((float) $employeeLines->sum('hourly_rate'), 2),
            'minute_rate_total' => round((float) $employeeLines->sum('minute_rate'), 4),
            'gross_pay_total' => round((float) $employeeLines->sum('gross_pay'), 2),
            'benefits_total' => round((float) $employeeLines->sum('total_benefits'), 2),
            'allowances_total' => round((float) $employeeLines->sum('total_allowances'), 2),
            'deductions_total' => round((float) $employeeLines->sum('total_deductions'), 2),
            'night_differential_total' => round((float) $employeeLines->sum('night_differential_amount'), 2),
            'holiday_pay_total' => round((float) $employeeLines->sum('holiday_pay_amount'), 2),
            'total_payroll_amount' => round((float) $employeeLines->sum('net_pay'), 2),
            'payroll_summary_ids' => $summaries->pluck('id')->filter()->values()->all(),
            'employee_lines' => $employeeLines->all(),
        ];
    }

    private function generatePayrollSummariesForPeriod(PayrollPeriod $period, bool $persist): \Illuminate\Support\Collection
    {
        $profiles = EmployeePayrollProfile::query()
            ->with(['employee', 'payrollLevel.salaryGrade'])
            ->get();

        if ($profiles->isEmpty()) {
            return collect();
        }

        $calculator = app(PayrollCalculator::class);
        $summaries = collect();

        foreach ($profiles as $profile) {
            if (! $profile->employee || ! $profile->payrollLevel || ! $profile->payrollLevel->salaryGrade) {
                continue;
            }

            $computed = $calculator->compute($profile, $period);

            if ($persist) {
                $summary = PayrollSummary::updateOrCreate(
                    [
                        'employee_id' => $profile->employee_id,
                        'payroll_period_id' => $period->id,
                    ],
                    [
                        'payroll_level_id' => $profile->payroll_level_id,
                        'computation_type' => $computed['computation_type'],
                        'gross_pay' => $computed['gross_pay'],
                        'total_benefits' => $computed['total_benefits'],
                        'total_allowances' => $computed['total_allowances'],
                        'total_deductions' => $computed['total_deductions'],
                        'night_differential_amount' => $computed['night_differential_amount'],
                        'holiday_pay_amount' => $computed['holiday_pay_amount'],
                        'net_pay' => $computed['net_pay'],
                        'breakdown_json' => $computed['breakdown'],
                        'status' => 'generated',
                    ]
                );

                PayrollSummaryItem::where('payroll_summary_id', $summary->id)->delete();
                foreach ($computed['items'] as $item) {
                    PayrollSummaryItem::create([
                        'payroll_summary_id' => $summary->id,
                        'item_type' => $item['item_type'],
                        'category' => $item['category'],
                        'name' => $item['name'],
                        'amount' => $item['amount'],
                        'meta_json' => $item['meta_json'] ?? null,
                    ]);
                }

                $summary->load(['employee', 'payrollLevel.salaryGrade', 'items']);
                $summaries->push($summary);
                continue;
            }

            $summary = new PayrollSummary([
                'employee_id' => $profile->employee_id,
                'payroll_period_id' => $period->id,
                'payroll_level_id' => $profile->payroll_level_id,
                'computation_type' => $computed['computation_type'],
                'gross_pay' => $computed['gross_pay'],
                'total_benefits' => $computed['total_benefits'],
                'total_allowances' => $computed['total_allowances'],
                'total_deductions' => $computed['total_deductions'],
                'night_differential_amount' => $computed['night_differential_amount'],
                'holiday_pay_amount' => $computed['holiday_pay_amount'],
                'net_pay' => $computed['net_pay'],
                'breakdown_json' => $computed['breakdown'],
                'status' => 'computed',
            ]);
            $summary->setRelation('employee', $profile->employee);
            $summary->setRelation('payrollLevel', $profile->payrollLevel);
            $summaries->push($summary);
        }

        return $summaries;
    }

    private function resolveCurrentUserContactProfile(): ?array
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $email = trim((string) $user->email);
        $name = trim((string) $user->name);

        if ($email === '' && $name === '') {
            return null;
        }

        $contact = Schema::hasTable('contacts')
            ? Contact::query()
                ->where(function ($query) use ($email, $name) {
                    if ($email !== '') {
                        $query->where('email', $email);
                    }

                    if ($name !== '') {
                        $method = $email !== '' ? 'orWhereRaw' : 'whereRaw';
                        $query->{$method}("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) = ?", [$name])
                            ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) = ?", [$name]);
                    }
                })
                ->first()
            : null;

        $employee = $this->resolveCurrentUserEmployee($email, $name);

        $userProfileFields = [
            $user->getAttribute('employee_id'),
            $user->getAttribute('employee_code'),
            $user->getAttribute('phone'),
            $user->getAttribute('contact_number'),
            $user->getAttribute('position'),
            $user->getAttribute('department'),
            $user->getAttribute('superior'),
            $user->getAttribute('superior_email'),
        ];

        if (! $contact && ! $employee && ! collect($userProfileFields)->contains(fn ($value) => filled($value))) {
            return null;
        }

        $employeeName = $employee
            ? trim(collect([$employee->first_name, $employee->last_name])->filter()->implode(' '))
            : '';
        $departmentName = $employee?->relationLoaded('department')
            ? (string) ($employee->department?->department_name ?? '')
            : '';
        $superior = $employee?->relationLoaded('department')
            ? (string) ($employee->department?->department_head ?? '')
            : '';
        $superiorEmail = $this->resolveEmployeeSuperiorEmail($superior);

        return [
            'id' => $contact?->id,
            'contact_id' => $contact?->id,
            'employee_record_id' => $employee?->id,
            'name' => $this->firstFilledFinanceValue($employeeName, $contact ? $this->contactDisplayName($contact) : null, $name),
            'email' => $this->firstFilledFinanceValue($employee?->email, $contact?->email, $email),
            'phone' => $this->firstFilledFinanceValue($employee?->phone_number, $contact?->phone, $user->getAttribute('phone'), $user->getAttribute('contact_number')),
            'contact_number' => $this->firstFilledFinanceValue($employee?->phone_number, $contact?->phone, $user->getAttribute('contact_number'), $user->getAttribute('phone')),
            'employee_id' => $this->firstFilledFinanceValue($employee?->employee_code, $user->getAttribute('employee_id'), $user->getAttribute('employee_code')),
            'employee_code' => $this->firstFilledFinanceValue($employee?->employee_code, $user->getAttribute('employee_code'), $user->getAttribute('employee_id')),
            'position' => $this->firstFilledFinanceValue($employee?->position, $contact?->position, $user->getAttribute('position')),
            'department' => $this->firstFilledFinanceValue($departmentName, $contact?->company_name, $user->getAttribute('department')),
            'company_name' => $contact?->company_name,
            'address' => $this->firstFilledFinanceValue($employee?->address, $contact?->contact_address),
            'contact_address' => $this->firstFilledFinanceValue($contact?->contact_address, $employee?->address),
            'superior' => $this->firstFilledFinanceValue($superior, $user->getAttribute('superior')),
            'superior_email' => $this->firstFilledFinanceValue($superiorEmail, $user->getAttribute('superior_email')),
            'cif_no' => $contact?->cif_no,
            'tin' => $contact?->tin,
        ];
    }

    private function resolveCurrentUserEmployee(string $email, string $name): ?Employee
    {
        if (! Schema::hasTable('employees')) {
            return null;
        }

        $query = Employee::query();

        if (Schema::hasTable('departments')) {
            $query->with('department');
        }

        return $query
            ->where(function ($query) use ($email, $name) {
                if ($email !== '') {
                    $query->where('email', $email);
                }

                if ($name !== '') {
                    $method = $email !== '' ? 'orWhereRaw' : 'whereRaw';
                    $query->{$method}("TRIM(CONCAT_WS(' ', first_name, last_name)) = ?", [$name]);
                }
            })
            ->first();
    }

    private function resolveEmployeeSuperiorEmail(string $superior): string
    {
        $superior = trim($superior);

        if ($superior === '') {
            return '';
        }

        if (Schema::hasTable('employees')) {
            $employee = Employee::query()
                ->whereRaw("TRIM(CONCAT_WS(' ', first_name, last_name)) = ?", [$superior])
                ->first(['email']);

            if ($employee?->email) {
                return $employee->email;
            }
        }

        if (Schema::hasTable('users')) {
            $user = \App\Models\User::query()
                ->where('name', $superior)
                ->first(['email']);

            if ($user?->email) {
                return $user->email;
            }
        }

        return '';
    }

    private function firstFilledFinanceValue(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if (! blank($value)) {
                return $value;
            }
        }

        return '';
    }

    private function contactDisplayName(Contact $contact, bool $includeEmail = false): string
    {
        $name = trim(collect([
            $contact->salutation,
            $contact->first_name,
            $contact->middle_name ?: $contact->middle_initial,
            $contact->last_name,
            $contact->name_extension,
        ])->filter()->implode(' '));

        if ($name === '') {
            $name = trim((string) ($contact->company_name ?: $contact->email ?: 'Contact #'.$contact->id));
        }

        if ($includeEmail && filled($contact->email)) {
            return "{$name} ({$contact->email})";
        }

        return $name;
    }

    private function transformRecord(FinanceRecord $record): array
    {
        $data = $record->data ?? [];
        $existingDvPayload = is_array(data_get($data, 'dv_payload')) ? data_get($data, 'dv_payload') : [];

        return [
            'id' => $record->id,
            'module_key' => $record->module_key,
            'module_label' => $this->moduleLabel($record->module_key),
            'record_number' => $this->normalizeFinanceRecordNumber($record->module_key, $record->record_number),
            'record_title' => $record->record_title,
            'display_label' => $this->optionLabel($record),
            'record_date' => optional($record->record_date)->format('Y-m-d'),
            'amount' => $record->amount,
            'status' => $record->status ?? 'Active',
            'workflow_status' => $record->workflow_status ?? 'Uploaded',
            'approval_status' => $record->approval_status ?? 'Pending',
            'submitted_by' => $record->submitted_by,
            'submitted_at' => optional($record->submitted_at)->format('Y-m-d H:i:s'),
            'approved_by' => $record->approved_by,
            'approved_at' => optional($record->approved_at)->format('Y-m-d H:i:s'),
            'review_note' => $record->review_note,
            'data' => array_merge($data, [
                'dv_payload' => array_merge(
                    $this->fallbackDvPayload($record),
                    $existingDvPayload
                ),
            ]),
            'attachments' => array_values(array_map(function ($attachment) {
                $attachment = is_array($attachment) ? $attachment : [];
                $attachment['url'] = $this->financeAttachmentUrl($attachment);
                $attachment['download_url'] = $attachment['url'] ? $attachment['url'] . '?download=1' : null;

                return $attachment;
            }, (array) ($record->attachments ?? []))),
            'share_token' => $record->share_token,
            'shared_at' => optional($record->shared_at)->format('Y-m-d H:i:s'),
            'supplier_completed_at' => optional($record->supplier_completed_at)->format('Y-m-d H:i:s'),
            'user' => $record->user,
            'can_edit' => $this->canEditRecord($record),
            'can_submit' => $this->canSubmitRecord($record),
            'can_share_supplier' => $this->canShareSupplierRecord($record),
            'can_review' => $this->canApproveFinance(),
            'supplier_completion_url' => $record->share_token
                ? route('finance.supplier.completion', $record->share_token)
                : null,
        ];
    }

    private function defaultAcceptedFinanceRecordId(string $moduleKey): string
    {
        static $cache = [];

        if (!array_key_exists($moduleKey, $cache)) {
            $cache[$moduleKey] = (string) (FinanceRecord::query()
                ->where('module_key', $moduleKey)
                ->where(function ($query) {
                    $query->where('workflow_status', 'Accepted')
                        ->orWhere('approval_status', 'Approved');
                })
                ->orderByDesc('record_date')
                ->orderByDesc('created_at')
                ->value('id') ?: '');
        }

        return $cache[$moduleKey];
    }

    private function fallbackDvPayload(FinanceRecord $record): array
    {
        $data = $record->data ?? [];

        $firstFilled = function (array $values) {
            foreach ($values as $value) {
                if (!blank($value)) {
                    return $value;
                }
            }

            return null;
        };

        $paymentType = $firstFilled([
            data_get($data, 'payment_type'),
            data_get($data, 'mode_of_release'),
            data_get($data, 'reimbursement_mode'),
            data_get($data, 'mode_of_return'),
            data_get($data, 'paid_through'),
        ]) ?: 'Cash';

        $payload = [
            'supplier_id' => data_get($data, 'supplier_id') ?? '',
            'amount' => $firstFilled([
                data_get($data, 'amount'),
                data_get($data, 'grand_total'),
                data_get($data, 'amount_requested'),
                data_get($data, 'total_cash_advance'),
                data_get($data, 'amount_returned'),
                data_get($data, 'total_payroll_amount'),
                data_get($data, 'acquisition_cost'),
                $record->amount,
            ]) ?? '',
            'payment_type' => $paymentType,
            'disbursement_type' => $paymentType,
            'bank_account_id' => $firstFilled([
                data_get($data, 'bank_account_id'),
                data_get($data, 'funding_bank_account_id'),
                data_get($data, 'receiving_bank_account_id'),
                data_get($data, 'source_bank_account_id'),
                data_get($data, 'destination_bank_account_id'),
            ]) ?: $this->defaultAcceptedFinanceRecordId('bank_account'),
            'coa_id' => $firstFilled([
                data_get($data, 'coa_id'),
                data_get($data, 'payroll_expense_coa_id'),
                data_get($data, 'asset_coa_id'),
            ]) ?: $this->defaultAcceptedFinanceRecordId('chart_account'),
            'fund_source' => $firstFilled([
                data_get($data, 'fund_source'),
                data_get($data, 'project'),
                data_get($data, 'department'),
            ]) ?? '',
            'department' => data_get($data, 'department') ?: data_get($data, 'requesting_department') ?: '',
            'reference_number' => $firstFilled([
                data_get($data, 'reference_number'),
                data_get($data, 'transfer_reference_number'),
                $record->record_number ? $record->record_number . '-REF' : null,
            ]) ?? '',
            'purpose' => $firstFilled([
                data_get($data, 'purpose'),
                data_get($data, 'expense_details'),
                data_get($data, 'reason'),
                data_get($data, 'supporting_payroll_summary'),
                data_get($data, 'asset_description'),
                $record->record_title,
            ]) ?? '',
            'payment_date' => optional($record->record_date)->format('Y-m-d') ?: '',
            'due_date' => $firstFilled([
                data_get($data, 'due_date'),
                data_get($data, 'needed_date'),
                data_get($data, 'expected_delivery_date'),
                data_get($data, 'pay_date'),
                data_get($data, 'acquisition_date'),
            ]) ?? '',
            'withholding_tax' => $firstFilled([
                data_get($data, 'withholding_tax'),
                data_get($data, 'wht_amount'),
                data_get($data, 'wht_total'),
            ]) ?? '',
            'vat_amount' => $firstFilled([
                data_get($data, 'vat_amount'),
                data_get($data, 'tax_amount'),
                data_get($data, 'tax_total'),
            ]) ?? '',
            'currency' => data_get($data, 'currency') ?: 'PHP',
            'exchange_rate' => data_get($data, 'exchange_rate') ?: '',
            'received_by_name' => data_get($data, 'received_by_name') ?: '',
            'received_by_signature' => data_get($data, 'received_by_signature') ?: '',
            'date_received' => data_get($data, 'date_received') ?: '',
            'remarks' => data_get($data, 'remarks') ?: 'Seeded DV dummy payload.',
        ];

        $payload['line_items'] = $this->financeDvLineItemsFromSource($record, $payload);

        return $payload;
    }

    private function financeDvLineItemsFromSource(FinanceRecord $record, array $payload): array
    {
        $data = $record->data ?? [];
        $rows = collect((array) data_get($data, 'line_items', []))
            ->filter(fn ($row) => is_array($row) && collect($row)->contains(fn ($value) => !blank($value)))
            ->values();

        if ($rows->isNotEmpty()) {
            return $rows->map(function (array $row, int $index) use ($payload, $data) {
                $quantity = (float) data_get($row, 'quantity', 0);
                $unitAmount = (float) (data_get($row, 'amount') ?: data_get($row, 'unit_cost') ?: 0);
                $amount = (float) (data_get($row, 'total') ?: data_get($row, 'line_total') ?: data_get($row, 'debit') ?: data_get($row, 'credit') ?: ($quantity * $unitAmount) ?: $unitAmount);
                $description = trim(collect([
                    data_get($row, 'item_id') ?: data_get($row, 'description') ?: data_get($row, 'account_code') ?: 'Line '.($index + 1),
                    data_get($row, 'category') ? '('.data_get($row, 'category').')' : null,
                ])->filter()->implode(' '));

                return [
                    'description' => $description,
                    'account_code' => data_get($row, 'account_code') ?: data_get($row, 'coa_id') ?: data_get($payload, 'coa_id') ?: data_get($data, 'coa_id') ?: '',
                    'debit' => $amount > 0 ? number_format($amount, 2, '.', '') : '',
                    'credit' => '',
                ];
            })->all();
        }

        $amount = (float) (data_get($payload, 'amount') ?: $record->amount ?: data_get($data, 'amount') ?: 0);

        if ($record->module_key === 'ibtf') {
            return [
                [
                    'description' => 'Transfer to '.(data_get($data, 'destination_account_code') ?: data_get($data, 'destination_bank_account_id') ?: 'destination account'),
                    'account_code' => data_get($data, 'destination_account_code') ?: '',
                    'debit' => $amount > 0 ? number_format($amount, 2, '.', '') : '',
                    'credit' => '',
                ],
                [
                    'description' => 'Transfer from '.(data_get($data, 'source_account_code') ?: data_get($data, 'source_bank_account_id') ?: 'source account'),
                    'account_code' => data_get($data, 'source_account_code') ?: '',
                    'debit' => '',
                    'credit' => $amount > 0 ? number_format($amount, 2, '.', '') : '',
                ],
            ];
        }

        return [[
            'description' => trim(collect([
                $record->record_number ?: strtoupper($record->module_key),
                data_get($payload, 'purpose') ?: $record->record_title ?: 'Source document amount',
            ])->filter()->implode(' - ')),
            'account_code' => data_get($payload, 'coa_id') ?: '',
            'debit' => $amount > 0 ? number_format($amount, 2, '.', '') : '',
            'credit' => '',
        ]];
    }

    private function persistAttachments(Request $request, array $existingAttachments = []): array
    {
        $attachments = $existingAttachments;
        $category = $request->input('attachment_category');

        if (!$request->hasFile('attachments')) {
            return $attachments;
        }

        foreach ((array) $request->file('attachments') as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store('finance_documents', 'public');

            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => 'storage/' . $path,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'category' => $category ?: 'Supporting Document',
            ];
        }

        return $attachments;
    }

    private function commonValidationRules(): array
    {
        return [
            'module_key' => 'required|in:' . implode(',', $this->moduleKeys()),
            'record_number' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9]{2,10}-\d{5}$/'],
            'record_title' => 'nullable|string|max:255',
            'record_date' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive,Draft,For Approval,Approved,Released,Cancelled',
            'data' => 'nullable|array',
            'data.transaction_time' => 'nullable|date_format:H:i',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'attachment_category' => 'nullable|in:Invoice,OR,DR,Contract,Supporting Document',
        ];
    }

    private function acceptedLinkedRecordRule(string $moduleKey, array $dataConstraints = [])
    {
        return Rule::exists('finance_records', 'id')->where(function ($query) use ($moduleKey, $dataConstraints) {
            $query->where('module_key', $moduleKey)
                ->where(function ($statusQuery) {
                    $statusQuery->where('workflow_status', 'Accepted')
                        ->orWhere('approval_status', 'Approved');
                });

            foreach ($dataConstraints as $field => $value) {
                $query->where("data->{$field}", $value);
            }
        });
    }

    private function moduleSpecificRules(string $moduleKey): array
    {
        $rules = [
            'supplier' => [
                'data.completion_mode' => 'nullable|in:complete_internally,send_to_supplier',
                'data.email_address' => 'required|email|max:255',
                'data.representative_full_name' => 'required|string|max:255',
                'data.phone_number' => 'required|string|max:255',
            ],
            'service' => [
                'data.supplier_id' => ['required', $this->acceptedLinkedRecordRule('supplier')],
                'data.coa_id' => ['required', $this->acceptedLinkedRecordRule('chart_account')],
                'data.products_services_provided' => 'nullable|string|max:2000',
            ],
            'product' => [
                'data.supplier_id' => ['required', $this->acceptedLinkedRecordRule('supplier')],
                'data.coa_id' => ['required', $this->acceptedLinkedRecordRule('chart_account')],
            ],
            'chart_account' => [
                'data.is_sub_account' => 'nullable|boolean',
                'data.parent_account_id' => ['nullable', $this->acceptedLinkedRecordRule('chart_account')],
                'data.bank_account_name' => 'nullable|string|max:255',
                'data.bank_profile' => 'nullable|string|max:255',
                'data.bank_account_number' => 'nullable|string|max:255',
            ],
            'bank_account' => [
                'data.linked_coa_id' => ['required', $this->acceptedLinkedRecordRule('chart_account')],
            ],
            'pr' => [
                'data.requesting_department' => 'required|string|max:255',
                'data.requester_mode' => 'nullable|in:own_request,request_for_another',
                'data.requester_employee_id' => ['required_if:data.requester_mode,request_for_another', 'nullable', Rule::exists('employees', 'id')],
                'data.requestor' => 'required|string|max:255',
                'data.department' => 'nullable|string|max:255',
                'data.request_type' => 'required|in:Service,Product',
                'data.supplier_id' => ['nullable', $this->acceptedLinkedRecordRule('supplier')],
                'data.master_item_type' => 'nullable|in:service,product',
                'data.master_item_id' => 'nullable|integer',
                'data.coa_id' => ['nullable', $this->acceptedLinkedRecordRule('chart_account')],
                'data.quantity' => 'nullable|numeric|min:0',
                'data.unit_cost' => 'nullable|numeric|min:0',
                'data.estimated_total_cost' => 'nullable|numeric|min:0',
                'data.line_items' => 'nullable|array',
                'data.line_items.*.item_module' => 'nullable|in:service,product',
                'data.line_items.*.item_record_id' => 'nullable|integer',
                'data.line_items.*.item_id' => 'nullable|string|max:255',
                'data.line_items.*.description' => 'nullable|string|max:1000',
                'data.line_items.*.category' => 'nullable|string|max:255',
                'data.line_items.*.quantity' => 'nullable|numeric|min:0',
                'data.line_items.*.amount' => 'nullable|numeric|min:0',
                'data.line_items.*.supplier_id' => ['nullable', $this->acceptedLinkedRecordRule('supplier')],
                'data.line_items.*.client_id' => ['nullable', Rule::exists('contacts', 'id')],
            ],
            'po' => [
                'data.linked_pr_id' => ['required', $this->acceptedLinkedRecordRule('pr')],
                'data.supplier_id' => ['required', $this->acceptedLinkedRecordRule('supplier')],
                'data.linked_item_type' => 'required|in:service,product',
                'data.linked_item_id' => 'required|integer',
                'data.coa_id' => ['required', $this->acceptedLinkedRecordRule('chart_account')],
                'data.quantity' => 'nullable|numeric|min:0',
                'data.unit_cost' => 'nullable|numeric|min:0',
                'data.total_amount' => 'nullable|numeric|min:0',
            ],
            'ca' => [
                'data.requester_mode' => 'nullable|in:own_request,request_for_another',
                'data.requester_employee_id' => ['required_if:data.requester_mode,request_for_another', 'nullable', Rule::exists('employees', 'id')],
                'data.requestor' => 'required|string|max:255',
                'data.purpose' => 'required|string|max:2000',
                'data.amount_requested' => 'required|numeric|min:0',
                'data.release_schedule' => 'nullable|in:Full Release,Staggered Release',
                'data.release_count' => 'nullable|integer|min:1',
                'data.amount_per_release' => 'nullable|numeric|min:0',
                'data.cash_release_date' => 'nullable|date',
                'data.cash_release_time' => 'nullable|date_format:H:i',
                'data.mode_of_release' => 'required|in:Cash,Bank Transfer,Check',
                'data.paid_through' => ['nullable', $this->acceptedLinkedRecordRule('chart_account')],
                'data.bank_account_id' => ['nullable', $this->acceptedLinkedRecordRule('bank_account')],
                'data.coa_id' => ['nullable', $this->acceptedLinkedRecordRule('chart_account')],
                'data.ca_payment_entries' => 'nullable|array',
                'data.ca_payment_entries.*.release_no' => 'nullable|integer|min:1',
                'data.ca_payment_entries.*.payment_date' => 'nullable|date',
                'data.ca_payment_entries.*.payment_amount' => 'nullable|numeric|min:0',
                'data.ca_payment_entries.*.payment_remarks' => 'nullable|string|max:1000',
            ],
            'lr' => [
                'data.requester_mode' => 'nullable|in:own_request,request_for_another',
                'data.requester_employee_id' => ['required_if:data.requester_mode,request_for_another', 'nullable', Rule::exists('employees', 'id')],
                'data.linked_ca_id' => ['required', $this->acceptedLinkedRecordRule('ca')],
                'data.linked_dv_id' => ['nullable', $this->acceptedLinkedRecordRule('dv', ['source_document_type' => 'ca'])],
                'data.total_cash_advance' => 'required|numeric|min:0',
                'data.purpose' => 'required|string|max:2000',
                'data.employee_id' => 'nullable|string|max:255',
                'data.employee_name' => 'nullable|string|max:255',
                'data.employee_email' => 'nullable|email|max:255',
                'data.contact_number' => 'nullable|string|max:255',
                'data.position' => 'nullable|string|max:255',
                'data.department' => 'nullable|string|max:255',
                'data.superior' => 'nullable|string|max:255',
                'data.superior_email' => 'nullable|email|max:255',
                'data.for_client' => 'nullable|string|max:255',
                'data.client_names' => 'nullable|string|max:1000',
                'data.actual_expenses' => 'required|numeric|min:0',
                'data.variance' => 'required|numeric',
                'data.variance_indicator' => 'required|in:Shortage,Overage,Balanced',
                'data.coa_id' => ['nullable', $this->acceptedLinkedRecordRule('chart_account')],
            ],
            'err' => [
                'data.requester_mode' => 'nullable|in:own_request,request_for_another',
                'data.requester_employee_id' => ['required_if:data.requester_mode,request_for_another', 'nullable', Rule::exists('employees', 'id')],
                'data.requestor' => 'nullable|string|max:255',
                'data.linked_lr_id' => ['required', $this->acceptedLinkedRecordRule('lr', ['variance_indicator' => 'Shortage'])],
                'data.amount' => 'required|numeric|min:0',
                'data.reimbursement_payment_details' => 'nullable|string|max:1000',
                'data.manual_liquidation_entry' => 'nullable|boolean',
                'data.reimbursement_mode' => 'required|in:Cash,Bank Transfer,Check',
                'data.cash_receiver_name' => 'required_if:data.reimbursement_mode,Cash|nullable|string|max:255',
                'data.recipient_bank_account' => 'required_if:data.reimbursement_mode,Bank Transfer|nullable|string|max:255',
                'data.recipient_bank_number' => 'required_if:data.reimbursement_mode,Bank Transfer|nullable|string|max:255',
                'data.bank_account_id' => ['required_if:data.reimbursement_mode,Check', 'nullable', $this->acceptedLinkedRecordRule('bank_account')],
                'data.supplier_id' => ['nullable', $this->acceptedLinkedRecordRule('supplier')],
            ],
            'dv' => [
                'data.source_document_type' => 'required|in:pr,po,ca,lr,err,pda,crf,ibtf,arf',
                'data.source_document_id' => 'required',
                'data.amount' => 'required|numeric|min:0',
                'data.payment_type' => 'required|in:Cash,Check,Bank Transfer,E-Wallet',
                'data.disbursement_type' => 'required|in:Cash,Check,Bank Transfer,Petty Cash',
                'data.bank_account_id' => ['required', $this->acceptedLinkedRecordRule('bank_account')],
                'data.coa_id' => ['required', $this->acceptedLinkedRecordRule('chart_account')],
                'data.supplier_id' => ['nullable', $this->acceptedLinkedRecordRule('supplier')],
                'data.fund_source' => 'nullable|string|max:255',
                'data.department' => 'nullable|string|max:255',
                'data.due_date' => 'nullable|date',
                'data.received_by_name' => 'nullable|string|max:255',
                'data.received_by_signature' => 'nullable|string|max:255',
                'data.date_received' => 'nullable|date',
                'data.withholding_tax' => 'nullable|numeric|min:0',
                'data.vat_amount' => 'nullable|numeric|min:0',
                'data.net_amount' => 'nullable|numeric|min:0',
                'data.currency' => 'nullable|string|max:10',
                'data.exchange_rate' => 'nullable|numeric|min:0',
                'data.line_items' => 'nullable|array',
                'data.line_items.*.description' => 'nullable|string|max:1000',
                'data.line_items.*.account_code' => 'nullable|string|max:255',
                'data.line_items.*.debit' => 'nullable|numeric|min:0',
                'data.line_items.*.credit' => 'nullable|numeric|min:0',
            ],
            'pda' => [
                'data.payroll_period_id' => 'required|exists:payroll_periods,id',
                'data.total_payroll_amount' => 'nullable|numeric|min:0',
                'data.funding_bank_account_id' => ['required', $this->acceptedLinkedRecordRule('bank_account')],
                'data.payroll_expense_coa_id' => ['required', $this->acceptedLinkedRecordRule('chart_account')],
                'data.employee_count' => 'nullable|integer|min:0',
                'data.basic_salary_total' => 'nullable|numeric|min:0',
                'data.yearly_basic_total' => 'nullable|numeric|min:0',
                'data.daily_rate_total' => 'nullable|numeric|min:0',
                'data.hourly_rate_total' => 'nullable|numeric|min:0',
                'data.minute_rate_total' => 'nullable|numeric|min:0',
                'data.gross_pay_total' => 'nullable|numeric|min:0',
                'data.benefits_total' => 'nullable|numeric|min:0',
                'data.allowances_total' => 'nullable|numeric|min:0',
                'data.deductions_total' => 'nullable|numeric|min:0',
                'data.night_differential_total' => 'nullable|numeric|min:0',
                'data.holiday_pay_total' => 'nullable|numeric|min:0',
                'data.employee_payroll_breakdown' => 'nullable|string|max:10000',
            ],
            'crf' => [
                'data.requester_mode' => 'nullable|in:own_request,request_for_another',
                'data.requester_employee_id' => ['required_if:data.requester_mode,request_for_another', 'nullable', Rule::exists('employees', 'id')],
                'data.requestor' => 'required|string|max:255',
                'data.linked_lr_id' => ['required', $this->acceptedLinkedRecordRule('lr', ['variance_indicator' => 'Overage'])],
                'data.amount_returned' => 'required|numeric|min:0',
                'data.manual_liquidation_entry' => 'nullable|boolean',
                'data.receiving_bank_account_id' => ['required', $this->acceptedLinkedRecordRule('bank_account')],
                'data.coa_id' => ['required', $this->acceptedLinkedRecordRule('chart_account')],
            ],
            'ibtf' => [
                'data.source_bank_account_id' => ['required', $this->acceptedLinkedRecordRule('bank_account')],
                'data.destination_bank_account_id' => ['required', $this->acceptedLinkedRecordRule('bank_account')],
                'data.amount' => 'required|numeric|min:0',
                'data.reason' => 'required|string|max:2000',
            ],
            'arf' => [
                'data.linked_po_id' => ['nullable', $this->acceptedLinkedRecordRule('po')],
                'data.linked_dv_id' => ['nullable', $this->acceptedLinkedRecordRule('dv')],
                'data.asset_code' => 'required|string|max:255',
                'data.supplier_id' => ['required', $this->acceptedLinkedRecordRule('supplier')],
                'data.acquisition_cost' => 'required|numeric|min:0',
                'data.acquisition_date' => 'required|date',
                'data.asset_coa_id' => ['required', $this->acceptedLinkedRecordRule('chart_account')],
            ],
        ];

        if ($moduleKey === 'po') {
            $rules['data.linked_item_id'] = ['required', 'integer'];
        }

        if ($moduleKey === 'pr') {
            $rules['data.master_item_id'] = ['required', 'integer'];
        }

        if ($moduleKey === 'chart_account') {
            $rules['data.parent_account_id'] = ['nullable', $this->acceptedLinkedRecordRule('chart_account')];
        }

        if ($moduleKey === 'dv') {
            $rules['data.source_document_id'] = [
                'required',
                'integer',
            ];
        }

        if ($moduleKey === 'lr') {
            $rules['data.linked_dv_id'] = ['nullable', $this->acceptedLinkedRecordRule('dv', ['source_document_type' => 'ca'])];
        }

        if ($moduleKey === 'arf') {
            $rules['data.linked_po_id'] = ['nullable', $this->acceptedLinkedRecordRule('po')];
            $rules['data.linked_dv_id'] = ['nullable', $this->acceptedLinkedRecordRule('dv')];
            $rules['data.linked_po_id'][] = 'required_without:data.linked_dv_id';
            $rules['data.linked_dv_id'][] = 'required_without:data.linked_po_id';
        }

        return $rules[$moduleKey] ?? [];
    }

    private function validateModulePayload(Request $request, ?FinanceRecord $financeRecord = null): void
    {
        $moduleKey = (string) $request->input('module_key', $financeRecord?->module_key);
        $supplierSendMode = $moduleKey === 'supplier'
            && data_get($request->input('data', []), 'completion_mode') === 'send_to_supplier';
        $rules = array_merge($this->commonValidationRules(), $this->moduleSpecificRules($moduleKey));

        if ($supplierSendMode) {
            $rules['record_number'] = 'nullable|string|max:255';
            $rules['record_title'] = 'nullable|string|max:255';
            $rules['record_date'] = 'nullable|date';
            $rules['amount'] = 'nullable|numeric|min:0';
            $rules['status'] = 'nullable|in:Active,Inactive';
            $rules['data.representative_full_name'] = 'nullable|string|max:255';
            $rules['data.designation'] = 'nullable|string|max:255';
            $rules['data.phone_number'] = 'nullable|string|max:255';
            $rules['data.alternate_contact_number'] = 'nullable|string|max:255';
            $rules['data.business_address'] = 'nullable|string|max:1000';
            $rules['data.billing_address'] = 'nullable|string|max:1000';
            $rules['data.tin'] = 'nullable|string|max:255';
            $rules['data.vat_status'] = 'nullable|string|max:255';
            $rules['data.payment_terms'] = 'nullable|string|max:255';
            $rules['data.accreditation_status'] = 'nullable|string|max:255';
            $rules['data.bank_name'] = 'nullable|string|max:255';
            $rules['data.bank_account_name'] = 'nullable|string|max:255';
            $rules['data.bank_account_number'] = 'nullable|string|max:255';
            $rules['data.remarks'] = 'nullable|string|max:2000';
        }

        if ($financeRecord) {
            $rules['module_key'] = ['required', Rule::in([$financeRecord->module_key])];
        }

        $validated = $request->validate($rules);

        if ($moduleKey === 'chart_account' && !blank(data_get($validated, 'data.is_sub_account')) && blank(data_get($validated, 'data.parent_account_id'))) {
            throw ValidationException::withMessages([
                'data.parent_account_id' => 'Main Account is required when Sub-Account is enabled.',
            ]);
        }

        if ($moduleKey === 'pr') {
            $itemType = data_get($validated, 'data.master_item_type');
            $itemId = data_get($validated, 'data.master_item_id');

            $itemModule = $itemType === 'product' ? 'product' : 'service';
            if (!$this->recordExistsForWorkflow($itemModule, $itemId)) {
                throw ValidationException::withMessages([
                    'data.master_item_id' => 'The selected item must exist in the chosen master list and be approved.',
                ]);
            }
        }

        if ($moduleKey === 'po') {
            $itemType = data_get($validated, 'data.linked_item_type');
            $itemId = data_get($validated, 'data.linked_item_id');

            $itemModule = $itemType === 'product' ? 'product' : 'service';
            if (!$this->recordExistsForWorkflow($itemModule, $itemId)) {
                throw ValidationException::withMessages([
                    'data.linked_item_id' => 'The selected item must exist in the chosen master list and be approved.',
                ]);
            }
        }

        if ($moduleKey === 'dv') {
            $sourceDocumentType = data_get($validated, 'data.source_document_type');
            $sourceDocumentId = data_get($validated, 'data.source_document_id');

            $allowedModule = match ($sourceDocumentType) {
                'pr' => 'pr',
                'po' => 'po',
                'ca' => 'ca',
                'lr' => 'lr',
                'err' => 'err',
                'pda' => 'pda',
                'crf' => 'crf',
                'ibtf' => 'ibtf',
                'arf' => 'arf',
                default => null,
            };

            if (!$allowedModule || !$this->recordExistsForWorkflow($allowedModule, $sourceDocumentId)) {
                throw ValidationException::withMessages([
                    'data.source_document_id' => 'The linked source document is invalid or is not yet approved.',
                ]);
            }
        }

        if ($moduleKey === 'lr') {
            $linkedDvId = data_get($validated, 'data.linked_dv_id');
            $linkedCaId = data_get($validated, 'data.linked_ca_id');
            if (!blank($linkedDvId) && !$this->recordExistsForWorkflow('dv', $linkedDvId, ['source_document_type' => 'ca'])) {
                throw ValidationException::withMessages([
                    'data.linked_dv_id' => 'The selected DV must come from a cash advance.',
                ]);
            }

            if (!blank($linkedCaId) && !blank($linkedDvId)) {
                $linkedDv = FinanceRecord::query()->find($linkedDvId);
                $linkedCaFromDv = data_get($linkedDv?->data, 'source_document_id');

                if ((string) $linkedCaFromDv !== (string) $linkedCaId) {
                    throw ValidationException::withMessages([
                        'data.linked_ca_id' => 'The selected CA must match the CA used by the linked DV.',
                    ]);
                }
            }
        }

        if ($moduleKey === 'crf') {
            $linkedLrId = data_get($validated, 'data.linked_lr_id');

            if (!$this->recordExistsForWorkflow('lr', $linkedLrId, ['variance_indicator' => 'Overage'])) {
                throw ValidationException::withMessages([
                    'data.linked_lr_id' => 'The selected LR must be approved and marked as overage.',
                ]);
            }
        }

        if ($moduleKey === 'arf') {
            $linkedPoId = data_get($validated, 'data.linked_po_id');
            $linkedDvId = data_get($validated, 'data.linked_dv_id');

            if (blank($linkedPoId) && blank($linkedDvId)) {
                throw ValidationException::withMessages([
                    'data.linked_po_id' => 'ARF must be linked to either a PO or a DV.',
                    'data.linked_dv_id' => 'ARF must be linked to either a PO or a DV.',
                ]);
            }
        }

        if ($moduleKey === 'ibtf' && data_get($validated, 'data.source_bank_account_id') === data_get($validated, 'data.destination_bank_account_id')) {
            throw ValidationException::withMessages([
                'data.destination_bank_account_id' => 'Source and destination bank accounts must be different.',
            ]);
        }
    }

    private function normalizeModuleData(string $moduleKey, array $data): array
    {
        if ($moduleKey === 'supplier' && blank(data_get($data, 'completion_mode'))) {
            data_set($data, 'completion_mode', 'complete_internally');
        }

        $data = $this->normalizeRequesterEmployeeData($moduleKey, $data);

        if ($moduleKey === 'err') {
            unset($data['supplier_id'], $data['coa_id']);

            $mode = (string) data_get($data, 'reimbursement_mode', '');
            if ($mode !== 'Cash') {
                unset($data['cash_receiver_name']);
            }
            if ($mode !== 'Bank Transfer') {
                unset($data['recipient_bank_account'], $data['recipient_bank_number']);
            }
            if ($mode !== 'Check') {
                unset($data['bank_account_id']);
            }
        }

        if ($moduleKey === 'dv') {
            $sourceType = (string) data_get($data, 'source_document_type', '');
            $sourceId = data_get($data, 'source_document_id');
            $submittedLineItems = array_values((array) data_get($data, 'line_items', []));
            $sourceRecord = $sourceType && $sourceId
                ? FinanceRecord::query()->where('module_key', $sourceType)->find($sourceId)
                : null;

            if ($sourceRecord) {
                $sourcePayload = $this->fallbackDvPayload($sourceRecord);
                foreach ([
                    'supplier_id',
                    'amount',
                    'bank_account_id',
                    'payment_type',
                    'disbursement_type',
                    'coa_id',
                    'fund_source',
                    'department',
                    'purpose',
                    'payment_date',
                    'due_date',
                    'withholding_tax',
                    'vat_amount',
                    'currency',
                    'exchange_rate',
                    'received_by_name',
                    'received_by_signature',
                    'date_received',
                    'reference_number',
                    'remarks',
                ] as $lockedField) {
                    data_set($data, $lockedField, data_get($sourcePayload, $lockedField, data_get($data, $lockedField)));
                }

                $sourceLineItems = array_values((array) data_get($sourcePayload, 'line_items', []));
                $lineItems = array_map(function ($item, $index) use ($submittedLineItems) {
                    $submittedItem = $submittedLineItems[$index] ?? [];
                    if (!is_array($item) || !is_array($submittedItem)) {
                        return $item;
                    }

                    foreach (['debit', 'credit'] as $editableField) {
                        if (array_key_exists($editableField, $submittedItem)) {
                            $item[$editableField] = $submittedItem[$editableField];
                        }
                    }

                    return $item;
                }, $sourceLineItems, array_keys($sourceLineItems));

                data_set($data, 'line_items', $lineItems ?: $submittedLineItems);
            }

            if (blank(data_get($data, 'currency'))) {
                data_set($data, 'currency', 'PHP');
            }

            if (blank(data_get($data, 'disbursement_type'))) {
                data_set($data, 'disbursement_type', data_get($data, 'payment_type') ?: 'Cash');
            }

            $amount = (float) data_get($data, 'amount', 0);
            $withholdingTax = (float) data_get($data, 'withholding_tax', 0);
            $vatAmount = (float) data_get($data, 'vat_amount', 0);
            if (blank(data_get($data, 'net_amount'))) {
                data_set($data, 'net_amount', max($amount + $vatAmount - $withholdingTax, 0));
            }

            $lineItems = array_values(array_filter((array) data_get($data, 'line_items', []), function ($item) {
                return is_array($item) && collect($item)->contains(fn ($value) => !blank($value));
            }));
            data_set($data, 'line_items', $lineItems);
        }

        if ($moduleKey === 'pda') {
            $period = PayrollPeriod::query()->find(data_get($data, 'payroll_period_id'));

            if ($period) {
                $snapshot = $this->payrollPeriodSnapshot($period, true);

                foreach ([
                    'period_start',
                    'period_end',
                    'payroll_start',
                    'payroll_end',
                    'pay_date',
                    'dispute_start',
                    'dispute_end',
                    'status',
                    'employee_count',
                    'basic_salary_total',
                    'yearly_basic_total',
                    'daily_rate_total',
                    'hourly_rate_total',
                    'minute_rate_total',
                    'gross_pay_total',
                    'benefits_total',
                    'allowances_total',
                    'deductions_total',
                    'night_differential_total',
                    'holiday_pay_total',
                    'total_payroll_amount',
                    'payroll_summary_ids',
                    'employee_lines',
                ] as $field) {
                    data_set($data, $field, data_get($snapshot, $field));
                }

                data_set($data, 'payroll_period_label', data_get($snapshot, 'label'));

                if (blank(data_get($data, 'supporting_payroll_summary'))) {
                    data_set($data, 'supporting_payroll_summary', sprintf(
                        '%d payroll summaries for %s. Basic: PHP %s; Gross: PHP %s; Benefits: PHP %s; Allowances: PHP %s; Deductions: PHP %s; Night Differential: PHP %s; Holiday Pay: PHP %s; Net Payroll: PHP %s.',
                        (int) data_get($snapshot, 'employee_count', 0),
                        data_get($data, 'payroll_period_label'),
                        number_format((float) data_get($snapshot, 'basic_salary_total', 0), 2),
                        number_format((float) data_get($snapshot, 'gross_pay_total', 0), 2),
                        number_format((float) data_get($snapshot, 'benefits_total', 0), 2),
                        number_format((float) data_get($snapshot, 'allowances_total', 0), 2),
                        number_format((float) data_get($snapshot, 'deductions_total', 0), 2),
                        number_format((float) data_get($snapshot, 'night_differential_total', 0), 2),
                        number_format((float) data_get($snapshot, 'holiday_pay_total', 0), 2),
                        number_format((float) data_get($snapshot, 'total_payroll_amount', 0), 2)
                    ));
                }

                if (blank(data_get($data, 'employee_payroll_breakdown'))) {
                    $lines = collect((array) data_get($snapshot, 'employee_lines', []))
                        ->map(function (array $line, int $index) {
                            return sprintf(
                                '%d. %s | %s / %s | Basic PHP %s | Gross PHP %s | Deductions PHP %s | Net PHP %s',
                                $index + 1,
                                data_get($line, 'employee_name') ?: data_get($line, 'employee_code') ?: 'Employee',
                                data_get($line, 'salary_grade') ?: 'No grade',
                                data_get($line, 'payroll_level') ?: 'No level',
                                number_format((float) data_get($line, 'monthly_basic_salary', 0), 2),
                                number_format((float) data_get($line, 'gross_pay', 0), 2),
                                number_format((float) data_get($line, 'total_deductions', 0), 2),
                                number_format((float) data_get($line, 'net_pay', 0), 2)
                            );
                        })
                        ->implode("\n");

                    data_set($data, 'employee_payroll_breakdown', $lines ?: 'No employee payroll profiles or summaries found for this period.');
                }
            }
        }

        if ($moduleKey === 'ca') {
            $cashAdvanceAmount = (float) data_get($data, 'amount_requested', 0);
            $releaseCount = max((int) data_get($data, 'release_count', 1), 1);
            $amountPerRelease = $releaseCount > 0 ? $cashAdvanceAmount / $releaseCount : $cashAdvanceAmount;
            $entries = array_values(array_filter((array) data_get($data, 'ca_payment_entries', []), function ($entry) {
                return is_array($entry) && (
                    !blank(data_get($entry, 'payment_date'))
                    || (float) data_get($entry, 'payment_amount', 0) > 0
                    || !blank(data_get($entry, 'payment_remarks'))
                );
            }));

            $entries = array_values(array_map(function (array $entry, int $index) {
                return [
                    'release_no' => max((int) data_get($entry, 'release_no', $index + 1), 1),
                    'payment_date' => data_get($entry, 'payment_date') ?: null,
                    'payment_amount' => number_format((float) data_get($entry, 'payment_amount', 0), 2, '.', ''),
                    'payment_remarks' => trim((string) data_get($entry, 'payment_remarks', '')),
                ];
            }, $entries, array_keys($entries)));

            $totalPaid = collect($entries)->sum(fn (array $entry) => (float) data_get($entry, 'payment_amount', 0));
            $paidReleaseNos = collect($entries)
                ->groupBy(fn (array $entry) => (int) data_get($entry, 'release_no', 0))
                ->filter(fn ($releaseEntries) => $amountPerRelease > 0 && $releaseEntries->sum(fn (array $entry) => (float) data_get($entry, 'payment_amount', 0)) >= $amountPerRelease)
                ->keys()
                ->count();
            $remainingBalance = max($cashAdvanceAmount - $totalPaid, 0);

            data_set($data, 'amount_per_release', number_format($amountPerRelease, 2, '.', ''));
            data_set($data, 'ca_payment_entries', $entries);
            data_set($data, 'ca_payment_total_paid', number_format($totalPaid, 2, '.', ''));
            data_set($data, 'ca_payment_remaining_balance', number_format($remainingBalance, 2, '.', ''));
            data_set($data, 'ca_payment_paid_count', min($paidReleaseNos, $releaseCount));
            data_set($data, 'ca_payment_remaining_count', max($releaseCount - min($paidReleaseNos, $releaseCount), 0));
            data_set($data, 'ca_payment_status', $remainingBalance <= 0 && $cashAdvanceAmount > 0 ? 'Fully Released' : ($totalPaid > 0 ? 'Partially Released' : 'Pending Release'));
        }

        if ($moduleKey === 'lr') {
            $lineItems = array_values(array_filter((array) data_get($data, 'line_items', []), function ($item) {
                return is_array($item) && collect($item)->contains(fn ($value) => !blank($value));
            }));

            $subtotal = 0;
            $discountTotal = 0;
            $shippingTotal = 0;
            $taxTotal = 0;
            $whtTotal = 0;
            $actualExpenses = 0;

            foreach ($lineItems as $index => $item) {
                $quantity = (float) data_get($item, 'quantity', 0);
                $amount = (float) data_get($item, 'amount', 0);
                $rowSubtotal = $quantity * $amount;
                $discountPercent = (float) str_replace('%', '', (string) data_get($item, 'discount', '0'));
                $manualDiscount = (float) data_get($item, 'discount_amount', 0);
                $discountAmount = $discountPercent > 0 ? $rowSubtotal * ($discountPercent / 100) : $manualDiscount;
                $shippingAmount = (float) data_get($item, 'shipping_amount', 0);
                $taxAmount = (float) data_get($item, 'tax_amount', 0);
                $whtAmount = (float) data_get($item, 'wht_amount', 0);
                $rowTotal = $rowSubtotal - $discountAmount + $shippingAmount + $taxAmount - $whtAmount;

                data_set($lineItems, "{$index}.subtotal", number_format($rowSubtotal, 2, '.', ''));
                data_set($lineItems, "{$index}.discount_amount", number_format($discountAmount, 2, '.', ''));
                data_set($lineItems, "{$index}.total", number_format($rowTotal, 2, '.', ''));

                $subtotal += $rowSubtotal;
                $discountTotal += $discountAmount;
                $shippingTotal += $shippingAmount;
                $taxTotal += $taxAmount;
                $whtTotal += $whtAmount;
                $actualExpenses += $rowTotal;
            }

            $cashAdvance = (float) data_get($data, 'total_cash_advance', 0);
            $variance = $cashAdvance - $actualExpenses;
            $varianceIndicator = $variance > 0 ? 'Overage' : ($variance < 0 ? 'Shortage' : 'Balanced');

            data_set($data, 'line_items', $lineItems);
            data_set($data, 'subtotal', number_format($subtotal, 2, '.', ''));
            data_set($data, 'discount_total', number_format($discountTotal, 2, '.', ''));
            data_set($data, 'shipping_total', number_format($shippingTotal, 2, '.', ''));
            data_set($data, 'tax_total', number_format($taxTotal, 2, '.', ''));
            data_set($data, 'wht_total', number_format($whtTotal, 2, '.', ''));
            data_set($data, 'grand_total', number_format($actualExpenses, 2, '.', ''));
            data_set($data, 'actual_expenses', number_format($actualExpenses, 2, '.', ''));
            data_set($data, 'variance', number_format($variance, 2, '.', ''));
            data_set($data, 'variance_indicator', $varianceIndicator);
        }

        if ($moduleKey === 'ibtf') {
            $bankCodeFor = function (mixed $bankAccountId): string {
                $bankAccount = blank($bankAccountId)
                    ? null
                    : FinanceRecord::query()->where('module_key', 'bank_account')->find($bankAccountId);
                $linkedCoaId = data_get($bankAccount?->data, 'linked_coa_id');
                $chartAccount = blank($linkedCoaId)
                    ? null
                    : FinanceRecord::query()->where('module_key', 'chart_account')->find($linkedCoaId);

                return (string) (
                    $chartAccount?->record_number
                    ?: data_get($chartAccount?->data, 'account_code')
                    ?: $linkedCoaId
                    ?: $bankAccount?->record_number
                    ?: ''
                );
            };

            if (blank(data_get($data, 'source_account_code'))) {
                data_set($data, 'source_account_code', $bankCodeFor(data_get($data, 'source_bank_account_id')));
            }

            if (blank(data_get($data, 'destination_account_code'))) {
                data_set($data, 'destination_account_code', $bankCodeFor(data_get($data, 'destination_bank_account_id')));
            }
        }

        if (in_array($moduleKey, ['err', 'crf'], true) && !filter_var(data_get($data, 'manual_liquidation_entry'), FILTER_VALIDATE_BOOLEAN)) {
            $linkedLr = FinanceRecord::query()
                ->where('module_key', 'lr')
                ->find(data_get($data, 'linked_lr_id'));

            if ($linkedLr) {
                $variance = abs((float) data_get($linkedLr->data, 'variance', 0));
                if ($moduleKey === 'err') {
                    data_set($data, 'amount', number_format($variance, 2, '.', ''));
                    data_set($data, 'expense_details', data_get($data, 'expense_details') ?: 'Shortage from linked liquidation report.');
                } else {
                    data_set($data, 'amount_returned', number_format($variance, 2, '.', ''));
                }
            }
        }

        return $data;
    }

    private function normalizeRequesterEmployeeData(string $moduleKey, array $data): array
    {
        if (! in_array($moduleKey, ['pr', 'ca', 'lr', 'err', 'crf'], true)) {
            return $data;
        }

        if ((string) data_get($data, 'requester_mode', 'own_request') !== 'request_for_another') {
            unset($data['requester_employee_id']);

            return $data;
        }

        $employeeId = data_get($data, 'requester_employee_id');

        if (blank($employeeId) || ! Schema::hasTable('employees')) {
            return $data;
        }

        $employee = Employee::query()
            ->when(Schema::hasTable('departments'), fn ($query) => $query->with('department'))
            ->find($employeeId);

        if (! $employee) {
            return $data;
        }

        $option = $this->employeeRequesterOption($employee);

        data_set($data, 'requester_employee_id', $employee->id);
        data_set($data, 'requestor', $option['full_name']);
        data_set($data, 'employee_name', $option['full_name']);
        data_set($data, 'employee_id', $option['employee_code']);
        data_set($data, 'employee_email', $option['email']);
        data_set($data, 'contact_number', $option['contact_number']);
        data_set($data, 'position', $option['position']);
        data_set($data, 'department', $option['department']);
        data_set($data, 'superior', $option['superior']);
        data_set($data, 'superior_email', $option['superior_email']);

        return $data;
    }

    private function recordExistsForWorkflow(string $moduleKey, mixed $recordId, array $dataConstraints = []): bool
    {
        return $this->acceptedRecordQuery($moduleKey, $dataConstraints)
            ->where('id', $recordId)
            ->exists();
    }

    public function index(Request $request)
    {
        $moduleKey = $request->get('module', 'supplier');
        $workflowFilter = $request->get('workflow_status', 'all');

        if (!array_key_exists($moduleKey, self::MODULES)) {
            $moduleKey = 'supplier';
        }

        if (!in_array($workflowFilter, array_merge(['all'], self::WORKFLOW_STATUSES), true)) {
            $workflowFilter = 'all';
        }

        $query = FinanceRecord::query();

        if (!$this->canApproveFinance()) {
            $query->where('submitted_by', Auth::id());
        }

        $records = $query->orderByDesc('record_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FinanceRecord $record) => $this->transformRecord($record))
            ->values();

        $sourceRecords = FinanceRecord::query()
            ->where(function ($statusQuery) {
                $statusQuery->where('workflow_status', 'Accepted')
                    ->orWhere('approval_status', 'Approved');
            })
            ->orderByDesc('record_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FinanceRecord $record) => $this->transformRecord($record))
            ->values();

        return view('finance.index', [
            'records' => $records,
            'sourceRecords' => $sourceRecords,
            'moduleLabels' => self::MODULES,
            'lookupOptions' => $this->resolveLookupOptions(),
            'currentModule' => $moduleKey,
            'currentWorkflowFilter' => $workflowFilter,
            'canApproveFinance' => $this->canApproveFinance(),
            'canManageFinanceSettings' => $this->canManageFinanceSettings(),
            'financeDropdownOptions' => $this->financeDropdownSettings(),
            'currentUserName' => Auth::user()->name ?? 'Unknown User',
            'currentUserEmail' => Auth::user()->email ?? '',
            'currentUserContact' => $this->resolveCurrentUserContactProfile(),
        ]);
    }

    public function updateDropdownSettings(Request $request)
    {
        if (!$this->canManageFinanceSettings()) {
            abort(403, 'Unauthorized');
        }

        if (
            !Schema::hasTable('settings')
            || !Schema::hasColumn('settings', 'key')
            || !Schema::hasColumn('settings', 'value')
        ) {
            return response()->json([
                'message' => 'Finance dropdown settings storage is not ready. Please run the database migrations.',
            ], 422);
        }

        $validated = $request->validate([
            'options' => ['required', 'array'],
        ]);

        $settings = $this->sanitizeFinanceDropdownSettings($validated['options']);

        Setting::query()->updateOrCreate(
            ['key' => self::DROPDOWN_SETTINGS_KEY],
            ['value' => json_encode($settings, JSON_UNESCAPED_SLASHES)]
        );

        return response()->json([
            'message' => 'Finance dropdown settings saved.',
            'options' => $settings,
        ]);
    }

    public function show(FinanceRecord $financeRecord)
    {
        if (!$this->canApproveFinance() && (int) $financeRecord->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return response()->json($this->transformRecord($financeRecord));
    }

    public function previewHtml(FinanceRecord $financeRecord)
    {
        return view('finance.preview-html', $this->financePdfContext($financeRecord->fresh()));
    }

    public function previewPdf(FinanceRecord $financeRecord)
    {
        $context = $this->financePdfContext($financeRecord->fresh(), false);
        $pdf = Pdf::loadView('finance.pdf', $context)->setPaper('letter', 'portrait');

        $fileName = $this->normalizeFinanceRecordNumber($financeRecord->module_key, $financeRecord->record_number) ?: 'finance-record';

        return $pdf->stream($fileName . '.pdf');
    }

    public function store(Request $request)
    {
        $this->validateModulePayload($request);

        $isApprover = $this->canApproveFinance();
        $attachments = $this->persistAttachments($request);
        $data = $this->normalizeModuleData($request->module_key, $request->input('data', []));
        $supplierSendMode = $request->module_key === 'supplier' && data_get($data, 'completion_mode') === 'send_to_supplier';
        $recordNumber = trim((string) $request->input('record_number', ''));
        $recordTitle = trim((string) $request->input('record_title', ''));
        $recordDate = $request->input('record_date');

        if ($supplierSendMode) {
            $recordNumber = $recordNumber ?: $this->normalizeFinanceRecordNumber('supplier', '');
            $recordTitle = $recordTitle ?: 'Supplier Completion';
            $recordDate = $recordDate ?: now()->toDateString();
        } elseif ($recordTitle === '') {
            $recordTitle = self::MODULES[$request->module_key] ?? Str::headline($request->module_key);
        }

        $recordNumber = $this->normalizeFinanceRecordNumber($request->module_key, $recordNumber);

        $record = FinanceRecord::create([
            'module_key' => $request->module_key,
            'record_number' => $recordNumber,
            'record_title' => $recordTitle,
            'record_date' => $recordDate,
            'amount' => in_array($request->module_key, ['dv', 'pda'], true) ? data_get($data, $request->module_key === 'pda' ? 'total_payroll_amount' : 'amount') : $request->amount,
            'status' => $request->status,
            'workflow_status' => $isApprover ? 'Accepted' : 'Uploaded',
            'approval_status' => $isApprover ? 'Approved' : 'Pending',
            'submitted_by' => Auth::id(),
            'submitted_at' => $isApprover ? now() : null,
            'approved_by' => $isApprover ? Auth::id() : null,
            'approved_at' => $isApprover ? now() : null,
            'review_note' => null,
            'data' => $data,
            'attachments' => $attachments,
            'share_token' => null,
            'shared_at' => null,
            'user' => Auth::user()->name ?? 'Unknown User',
        ]);

        if ($request->module_key === 'supplier' && data_get($data, 'completion_mode') === 'send_to_supplier') {
            $this->sendSupplierCompletionEmail($record);
            $record = $record->fresh();
        }

        return response()->json([
            'message' => $record->workflow_status === 'Shared'
                ? 'Finance record created and emailed to the supplier.'
                : ($isApprover ? 'Finance record saved and accepted.' : 'Finance record saved successfully.'),
            'data' => $this->transformRecord($record),
        ], 201);
    }

    public function update(Request $request, FinanceRecord $financeRecord)
    {
        if (!$this->canEditRecord($financeRecord)) {
            abort(403, 'This record can no longer be edited.');
        }

        $this->validateModulePayload($request, $financeRecord);

        $existingAttachments = json_decode((string) $request->input('existing_attachments_json', '[]'), true);
        $existingAttachments = is_array($existingAttachments) ? $existingAttachments : [];
        $attachments = $this->persistAttachments($request, $existingAttachments);
        $data = $this->normalizeModuleData($request->module_key, $request->input('data', []));
        $supplierSendMode = $request->module_key === 'supplier' && data_get($data, 'completion_mode') === 'send_to_supplier';
        $recordNumber = trim((string) $request->input('record_number', ''));
        $recordTitle = trim((string) $request->input('record_title', ''));
        $recordDate = $request->input('record_date');

        if ($supplierSendMode) {
            $recordNumber = $recordNumber ?: ($financeRecord->record_number ?: $this->normalizeFinanceRecordNumber('supplier', ''));
            $recordTitle = $recordTitle ?: ($financeRecord->record_title ?: 'Supplier Completion');
            $recordDate = $recordDate ?: optional($financeRecord->record_date)->format('Y-m-d') ?: now()->toDateString();
        } elseif ($recordTitle === '') {
            $recordTitle = $financeRecord->record_title ?: (self::MODULES[$request->module_key] ?? Str::headline($request->module_key));
        }

        $recordNumber = $this->normalizeFinanceRecordNumber($request->module_key, $recordNumber);

        $payload = [
            'module_key' => $request->module_key,
            'record_number' => $recordNumber,
            'record_title' => $recordTitle,
            'record_date' => $recordDate,
            'amount' => in_array($request->module_key, ['dv', 'pda'], true) ? data_get($data, $request->module_key === 'pda' ? 'total_payroll_amount' : 'amount') : $request->amount,
            'status' => $request->status,
            'data' => $data,
            'attachments' => $attachments,
        ];

        if (($financeRecord->workflow_status ?? 'Uploaded') === 'Reverted') {
            $payload['approval_status'] = 'Pending';
            $payload['review_note'] = null;
        }

        $financeRecord->update($payload);

        if ($request->module_key === 'supplier' && data_get($data, 'completion_mode') === 'send_to_supplier' && blank($financeRecord->share_token)) {
            $this->sendSupplierCompletionEmail($financeRecord);
            $financeRecord = $financeRecord->fresh();
        }

        return response()->json([
            'message' => $financeRecord->workflow_status === 'Shared'
                ? 'Finance record updated and emailed to the supplier.'
                : 'Finance record updated successfully.',
            'data' => $this->transformRecord($financeRecord->fresh()),
        ]);
    }

    public function submit(FinanceRecord $financeRecord)
    {
        if ((int) $financeRecord->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($financeRecord->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true)) {
            return response()->json([
                'message' => 'Only uploaded or reverted records can be submitted.'
            ], 422);
        }

        $financeRecord->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'submitted_at' => now(),
            'review_note' => null,
        ]);

        return response()->json([
            'message' => 'Finance record submitted for review successfully.',
            'data' => $this->transformRecord($financeRecord->fresh()),
        ]);
    }

    public function approve(FinanceRecord $financeRecord)
    {
        if (!$this->canApproveFinance()) {
            abort(403, 'Unauthorized');
        }

        $financeRecord->update([
            'workflow_status' => 'Accepted',
            'approval_status' => 'Approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Finance record approved successfully.',
            'data' => $this->transformRecord($financeRecord->fresh()),
        ]);
    }

    public function revert(Request $request, FinanceRecord $financeRecord)
    {
        if (!$this->canApproveFinance()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'review_note' => 'required|string|max:1000',
        ]);

        $financeRecord->update([
            'workflow_status' => 'Reverted',
            'approval_status' => 'Needs Revision',
            'review_note' => $request->review_note,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Finance record reverted for revision.',
            'data' => $this->transformRecord($financeRecord->fresh()),
        ]);
    }

    public function archive(FinanceRecord $financeRecord)
    {
        if (!$this->canApproveFinance()) {
            abort(403, 'Unauthorized');
        }

        $financeRecord->update([
            'workflow_status' => 'Archived',
            'approval_status' => 'Archived',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Finance record archived successfully.',
            'data' => $this->transformRecord($financeRecord->fresh()),
        ]);
    }

    public function shareSupplierLink(FinanceRecord $financeRecord)
    {
        if (!$this->canManageSupplierCompletion($financeRecord)) {
            abort(403, 'Unauthorized');
        }

        $link = $this->sendSupplierCompletionEmail($financeRecord->fresh());
        $financeRecord = $financeRecord->fresh();

        return response()->json([
            'message' => 'Supplier completion link has been emailed.',
            'link' => $link,
            'data' => $this->transformRecord($financeRecord->fresh()),
        ]);
    }

    public function updateSupplierEmailAndResend(Request $request, FinanceRecord $financeRecord)
    {
        if (!$this->canManageSupplierCompletion($financeRecord)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'email_address' => 'required|email|max:255',
        ]);

        $data = $financeRecord->data ?? [];
        $data['email_address'] = $request->email_address;

        $financeRecord->update([
            'data' => $data,
        ]);

        $link = $this->sendSupplierCompletionEmail($financeRecord->fresh());
        $financeRecord = $financeRecord->fresh();

        return response()->json([
            'message' => 'Supplier email updated and completion form resent.',
            'link' => $link,
            'data' => $this->transformRecord($financeRecord),
        ]);
    }

    public function supplierCompletionForm(string $token)
    {
        $record = FinanceRecord::query()
            ->where('module_key', 'supplier')
            ->where('share_token', $token)
            ->firstOrFail();

        return view('finance.supplier-completion', [
            'record' => $this->transformRecord($record),
        ]);
    }

    public function submitSupplierCompletion(Request $request, string $token)
    {
        $record = FinanceRecord::query()
            ->where('module_key', 'supplier')
            ->where('share_token', $token)
            ->firstOrFail();

        $request->validate([
            'record_title' => 'required|string|max:255',
            'record_number' => 'required|string|max:255',
            'record_date' => 'required|date',
            'data.representative_full_name' => 'required|string|max:255',
            'data.email_address' => 'required|email|max:255',
            'data.phone_number' => 'required|string|max:255',
            'data.business_address' => 'nullable|string|max:1000',
            'data.billing_address' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'attachment_category' => 'nullable|in:Invoice,OR,DR,Contract,Supporting Document',
        ]);

        $data = array_merge($record->data ?? [], $request->input('data', []));
        $data['business_name'] = $request->record_title;
        $attachments = $this->persistAttachments($request, (array) ($record->attachments ?? []));

        $record->update([
            'record_number' => $request->record_number,
            'record_title' => $request->record_title,
            'record_date' => $request->record_date,
            'data' => $data,
            'attachments' => $attachments,
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'supplier_completed_at' => now(),
        ]);

        return redirect()
            ->route('finance.supplier.completion', $token)
            ->with('success', 'Supplier information submitted successfully. It is now ready for internal review.');
    }
}
