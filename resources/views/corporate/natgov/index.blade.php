@extends('layouts.app')

@php
    $repositoryRoutes = $repositoryRoutes ?? [
        'dataUrl' => route('natgov'),
        'storeUrl' => route('natgov.store'),
        'updateUrl' => route('natgov.update', '__ID__'),
        'submitUrl' => route('natgov.submit', '__ID__'),
    ];

    $moduleConfig = [
        'moduleId' => 'corporateNatGovRepository',
        'title' => 'NatGov',
        'purpose' => 'Track national government compliance records, renewal dates, reminders, approvals, and document versions.',
        'company' => $companyDefaults ?? [],
        'currentUser' => Auth::user()->name ?? Auth::user()->email ?? 'System User',
        'dataUrl' => $repositoryRoutes['dataUrl'],
        'storeUrl' => $repositoryRoutes['storeUrl'],
        'updateUrl' => $repositoryRoutes['updateUrl'],
        'submitUrl' => $repositoryRoutes['submitUrl'],
        'defaultWorkflowTab' => $defaultWorkflowTab ?? 'uploaded',
        'documentUpload' => [
            'accept' => '.pdf,application/pdf',
            'help' => 'PDF files only.',
        ],
        'options' => [
            'agencyOptions' => $agencyOptions ?? [],
            'statusOptions' => $statusOptions ?? [],
            'renewalPeriodOptions' => $renewalPeriodOptions ?? [],
            'manualStatusOptions' => $manualStatusOptions ?? [],
        ],
        'filters' => [
            ['key' => 'search_agency', 'label' => 'Search Government Agency', 'type' => 'text', 'placeholder' => 'Search agency or registration number...'],
            ['key' => 'status_filter', 'label' => 'Status', 'type' => 'select', 'optionsKey' => 'statusOptions', 'placeholder' => 'All Statuses'],
            ['key' => 'agency_filter', 'label' => 'Government Agency', 'type' => 'select', 'optionsKey' => 'agencyOptions', 'placeholder' => 'All Government Agencies'],
            ['key' => 'renewal_period', 'label' => 'Renewal Period', 'type' => 'select', 'options' => $renewalPeriodOptions ?? [], 'placeholder' => 'All Renewal Periods'],
            ['key' => 'sort_by', 'label' => 'Sort By', 'type' => 'select', 'options' => [
                'company' => 'Company',
                'government_agency' => 'Government Agency',
                'registration_no' => 'Registration Number',
                'registration_date' => 'Registration Date',
                'renewal_date' => 'Renewal Date',
                'status' => 'Status',
            ], 'placeholder' => 'Default Priority'],
            ['key' => 'sort_direction', 'label' => 'Sort Direction', 'type' => 'select', 'options' => [
                'asc' => 'Ascending',
                'desc' => 'Descending',
            ], 'placeholder' => 'Ascending'],
        ],
        'columns' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'agency', 'label' => 'Government Agency'],
            ['key' => 'registration_number', 'label' => 'Registration Number'],
            ['key' => 'registration_date', 'label' => 'Registration Date'],
            ['key' => 'renewal_date', 'label' => 'Renewal Date'],
            ['key' => 'status', 'label' => 'Status'],
        ],
        'previewFields' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'agency', 'label' => 'Government Agency'],
            ['key' => 'registration_number', 'label' => 'Registration Number'],
            ['key' => 'registration_date', 'label' => 'Registration Date'],
            ['key' => 'renewal_date', 'label' => 'Renewal Date'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'manual_status_override', 'label' => 'Admin Override'],
            ['key' => 'uploaded_by', 'label' => 'Uploaded By'],
            ['key' => 'date_uploaded', 'label' => 'Date Uploaded'],
            ['key' => 'last_updated_by', 'label' => 'Last Updated By'],
            ['key' => 'last_updated_date', 'label' => 'Last Updated Date'],
        ],
        'fields' => array_values(array_filter([
            ['key' => 'agency', 'label' => 'Government Agency Name', 'type' => 'multi_entry_select', 'optionsKey' => 'agencyOptions', 'selectedKey' => 'agencies_selected', 'otherKey' => 'agency_other', 'placeholder' => 'Search or type a government agency', 'helperText' => 'Search the agency list, add one or more entries, or type a custom agency when needed.'],
            ['key' => 'registration_no', 'label' => 'Registration Number', 'type' => 'text', 'required' => true],
            ['key' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date'],
            ['key' => 'renewal_date', 'label' => 'Renewal Date', 'type' => 'date'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'display', 'default' => 'Automatically generated from renewal date and admin override'],
            ($canOverrideStatus ?? false)
                ? ['key' => 'status_override', 'label' => 'Admin Status Override', 'type' => 'select', 'options' => array_combine($manualStatusOptions ?? [], $manualStatusOptions ?? [])]
                : null,
        ])),
    ];
@endphp

@include('corporate.partials.repository-module', ['config' => $moduleConfig])
