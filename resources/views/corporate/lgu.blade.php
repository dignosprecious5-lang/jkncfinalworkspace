@extends('layouts.app')

@php
    $moduleConfig = [
        'moduleId' => 'corporateLguRepository',
        'title' => 'LGU',
        'purpose' => 'Track LGU permits, licenses, clearances, registrations, fees, and renewal deadlines.',
        'company' => $companyDefaults ?? [],
        'currentUser' => Auth::user()->name ?? Auth::user()->email ?? 'System User',
        'dataUrl' => route('permits.index'),
        'storeUrl' => route('permits.store'),
        'updateUrl' => route('permits.update', '__ID__'),
        'submitUrl' => route('permits.submit', '__ID__'),
        'locationData' => $locationData ?? [],
        'options' => [
            'permitTypes' => $permitTypes ?? [],
            'statuses' => $statuses ?? [],
            'provinces' => array_keys($locationData ?? []),
            'cities' => [],
            'barangays' => [],
        ],
        'columns' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'province', 'label' => 'Province'],
            ['key' => 'city_municipality', 'label' => 'City/Municipality'],
            ['key' => 'barangay', 'label' => 'Barangay'],
            ['key' => 'permit_type', 'label' => 'Permit Type'],
            ['key' => 'permit_number', 'label' => 'Permit Number'],
            ['key' => 'date_registered', 'label' => 'Date Registered'],
            ['key' => 'renewal_date', 'label' => 'Renewal Date'],
            ['key' => 'total_permit_fee', 'label' => 'Total Permit Fee', 'type' => 'money'],
            ['key' => 'status', 'label' => 'Status'],
        ],
        'previewFields' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'province', 'label' => 'Province'],
            ['key' => 'city_municipality', 'label' => 'City/Municipality'],
            ['key' => 'barangay', 'label' => 'Barangay'],
            ['key' => 'permit_type', 'label' => 'Permit Type'],
            ['key' => 'permit_number', 'label' => 'Permit Number'],
            ['key' => 'date_registered', 'label' => 'Date Registered'],
            ['key' => 'renewal_date', 'label' => 'Renewal Date'],
            ['key' => 'total_permit_fee', 'label' => 'Total Permit Fee', 'type' => 'money'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'uploaded_by', 'label' => 'Uploaded By'],
            ['key' => 'date_uploaded', 'label' => 'Date Uploaded'],
            ['key' => 'last_updated_by', 'label' => 'Last Updated By'],
            ['key' => 'last_updated_date', 'label' => 'Last Updated Date'],
        ],
        'fields' => [
            ['key' => 'province', 'label' => 'Province', 'type' => 'location', 'optionsKey' => 'provinces', 'required' => true],
            ['key' => 'city_municipality', 'label' => 'City / Municipality', 'type' => 'location', 'optionsKey' => 'cities', 'required' => true],
            ['key' => 'barangay', 'label' => 'Barangay', 'type' => 'location', 'optionsKey' => 'barangays', 'required' => true],
            ['key' => 'permit_type', 'label' => 'Permit Type', 'type' => 'select', 'optionsKey' => 'permitTypes', 'otherKey' => 'permit_type_other', 'required' => true],
            ['key' => 'permit_number', 'label' => 'Permit Number', 'type' => 'text', 'required' => true],
            ['key' => 'date_registered', 'label' => 'Date Registered', 'type' => 'date'],
            ['key' => 'renewal_date', 'label' => 'Renewal Date', 'type' => 'date'],
            ['key' => 'total_permit_fee', 'label' => 'Total Permit Fee', 'type' => 'number'],
        ],
    ];
@endphp

@include('corporate.partials.repository-module', ['config' => $moduleConfig])
