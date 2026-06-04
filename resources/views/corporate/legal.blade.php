@extends('layouts.app')

@php
    $moduleConfig = [
        'moduleId' => 'corporateLegalRepository',
        'title' => 'Legal',
        'purpose' => 'Maintain contracts, agreements, resolutions, notices, legal opinions, and legal records.',
        'company' => $companyDefaults ?? [],
        'currentUser' => Auth::user()->name ?? Auth::user()->email ?? 'System User',
        'dataUrl' => route('legal.index'),
        'storeUrl' => route('legal.store'),
        'updateUrl' => route('legal.update', '__ID__'),
        'submitUrl' => route('legal.submit', '__ID__'),
        'options' => [
            'legalDocumentTypes' => $legalDocumentTypes ?? [],
            'statuses' => $statuses ?? [],
        ],
        'columns' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'document_type', 'label' => 'Document Type'],
            ['key' => 'document_title', 'label' => 'Document Title'],
            ['key' => 'document_date', 'label' => 'Document Date'],
            ['key' => 'effective_date', 'label' => 'Effective Date'],
            ['key' => 'expiration_date', 'label' => 'Expiration Date'],
            ['key' => 'status', 'label' => 'Status'],
        ],
        'fields' => [
            ['key' => 'document_type', 'label' => 'Document Type', 'type' => 'select', 'optionsKey' => 'legalDocumentTypes', 'otherKey' => 'document_type_other', 'required' => true],
            ['key' => 'document_title', 'label' => 'Document Title', 'type' => 'text', 'required' => true],
            ['key' => 'document_date', 'label' => 'Document Date', 'type' => 'date', 'required' => true],
            ['key' => 'effective_date', 'label' => 'Effective Date', 'type' => 'date'],
            ['key' => 'expiration_date', 'label' => 'Expiration Date', 'type' => 'date'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'optionsKey' => 'statuses'],
        ],
    ];
@endphp

@include('corporate.partials.repository-module', ['config' => $moduleConfig])
