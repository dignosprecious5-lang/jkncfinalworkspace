@extends('layouts.app')

@php
    $moduleConfig = [
        'moduleId' => 'corporateAccountingRepository',
        'title' => 'Accounting',
        'purpose' => 'Store and track accounting and financial reports only.',
        'company' => $companyDefaults ?? [],
        'currentUser' => Auth::user()->name ?? Auth::user()->email ?? 'System User',
        'dataUrl' => route('corporate.accounting.data'),
        'storeUrl' => route('corporate.accounting.store'),
        'updateUrl' => route('corporate.accounting.update', '__ID__'),
        'submitUrl' => route('corporate.accounting.submit', '__ID__'),
        'options' => [
            'reportTypes' => $reportTypes ?? [],
            'statuses' => $statuses ?? [],
        ],
        'columns' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'report_type', 'label' => 'Report Type'],
            ['key' => 'report_date', 'label' => 'Report Date'],
            ['key' => 'reporting_period', 'label' => 'Reporting Period'],
            ['key' => 'uploaded_by', 'label' => 'Uploaded By'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'document_url', 'label' => 'Document', 'type' => 'document'],
        ],
        'fields' => [
            ['key' => 'report_type', 'label' => 'Report Type / Statement Type', 'type' => 'select', 'optionsKey' => 'reportTypes', 'otherKey' => 'report_type_other', 'required' => true],
            ['key' => 'report_date', 'label' => 'Report Date', 'type' => 'date', 'required' => true],
            ['key' => 'reporting_period_from', 'label' => 'Reporting Period From', 'type' => 'date'],
            ['key' => 'reporting_period_to', 'label' => 'Reporting Period To', 'type' => 'date'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'optionsKey' => 'statuses'],
        ],
    ];
@endphp

@include('corporate.partials.repository-module', ['config' => $moduleConfig])
