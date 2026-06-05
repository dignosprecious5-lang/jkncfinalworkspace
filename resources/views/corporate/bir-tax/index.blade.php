@extends('layouts.app')

@php
    $repositoryRoutes = $repositoryRoutes ?? [
        'dataUrl' => route('bir-tax'),
        'storeUrl' => route('bir-tax.store'),
        'updateUrl' => route('bir-tax.update', '__ID__'),
        'submitUrl' => route('bir-tax.submit', '__ID__'),
    ];

    $moduleConfig = [
        'moduleId' => 'corporateBirTaxRepository',
        'title' => 'BIR & Tax',
        'purpose' => 'Track BIR filing records, due dates, uploaded draft and approved files, and approval workflow status.',
        'company' => $companyDefaults ?? [],
        'currentUser' => Auth::user()->name ?? Auth::user()->email ?? 'System User',
        'dataUrl' => $repositoryRoutes['dataUrl'],
        'storeUrl' => $repositoryRoutes['storeUrl'],
        'updateUrl' => $repositoryRoutes['updateUrl'],
        'submitUrl' => $repositoryRoutes['submitUrl'],
        'options' => [
            'taxTypeOptions' => $taxTypeOptions ?? [],
            'formTypeOptions' => $formTypeOptions ?? [],
            'filingFrequencyOptions' => $filingFrequencyOptions ?? [],
            'statusOptions' => $statusOptions ?? [],
        ],
        'filters' => [
            ['key' => 'search_tax_types', 'label' => 'Search Tax Type/s', 'type' => 'text', 'placeholder' => 'Search tax filing needed...'],
            ['key' => 'search_form_type', 'label' => 'Search Form Type', 'type' => 'text', 'placeholder' => 'Search BIR form type...'],
        ],
        'columns' => [
            ['key' => 'tin', 'label' => 'TIN'],
            ['key' => 'tax_payer', 'label' => 'Taxpayer'],
            ['key' => 'rdo', 'label' => 'RDO'],
            ['key' => 'registered_address', 'label' => 'Registered Address'],
            ['key' => 'tax_types', 'label' => 'Tax Type/s'],
            ['key' => 'form_type', 'label' => 'Form Type'],
            ['key' => 'tax_due', 'label' => 'Tax Due', 'type' => 'money'],
            ['key' => 'filing_frequency', 'label' => 'Filing Frequency'],
            ['key' => 'due_date', 'label' => 'Due Date'],
            ['key' => 'status', 'label' => 'Status'],
        ],
        'previewFields' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'tin', 'label' => 'TIN'],
            ['key' => 'tax_payer', 'label' => 'Taxpayer'],
            ['key' => 'rdo', 'label' => 'RDO'],
            ['key' => 'registered_address', 'label' => 'Registered Address'],
            ['key' => 'tax_types', 'label' => 'Tax Type/s'],
            ['key' => 'form_type', 'label' => 'Form Type'],
            ['key' => 'tax_due', 'label' => 'Tax Due', 'type' => 'money'],
            ['key' => 'filing_frequency', 'label' => 'Filing Frequency'],
            ['key' => 'due_date', 'label' => 'Due Date'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'filing_status', 'label' => 'Filing Status'],
            ['key' => 'uploaded_by', 'label' => 'Uploaded By'],
            ['key' => 'date_uploaded', 'label' => 'Date Uploaded'],
            ['key' => 'last_updated_by', 'label' => 'Last Updated By'],
            ['key' => 'last_updated_date', 'label' => 'Last Updated Date'],
        ],
        'fields' => [
            ['key' => 'tin', 'label' => 'TIN', 'type' => 'text', 'default' => $companyDefaults['tin'] ?? ''],
            ['key' => 'tax_payer', 'label' => 'Taxpayer', 'type' => 'text', 'default' => $companyDefaults['company_name'] ?? '', 'required' => true],
            ['key' => 'rdo', 'label' => 'RDO', 'type' => 'text'],
            ['key' => 'registered_address', 'label' => 'Registered Address', 'type' => 'textarea', 'default' => ($companyDefaults['principal_address'] ?? ($companyDefaults['company_address'] ?? ''))],
            ['key' => 'tax_types', 'label' => 'Tax Type/s', 'type' => 'checkbox_group', 'optionsKey' => 'taxTypeOptions', 'selectedKey' => 'tax_types_selected', 'otherKey' => 'tax_types_other'],
            ['key' => 'form_type', 'label' => 'Form Type', 'type' => 'checkbox_group', 'optionsKey' => 'formTypeOptions', 'selectedKey' => 'form_types_selected', 'otherKey' => 'form_type_other'],
            ['key' => 'tax_due', 'label' => 'Tax Due', 'type' => 'number'],
            ['key' => 'filing_frequency', 'label' => 'Filing Frequency', 'type' => 'select', 'optionsKey' => 'filingFrequencyOptions', 'default' => 'Monthly'],
            ['key' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
            ['key' => 'status', 'label' => 'Filing Status', 'type' => 'select', 'optionsKey' => 'statusOptions', 'default' => 'Pending'],
        ],
    ];
@endphp

@include('corporate.partials.repository-module', ['config' => $moduleConfig])
