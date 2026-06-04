@extends('layouts.app')

@php
    $moduleConfig = [
        'moduleId' => 'corporateBankingRepository',
        'title' => 'Banking',
        'purpose' => 'Store bank statements, certificates, account records, loan documents, and banking compliance records.',
        'company' => $companyDefaults ?? [],
        'currentUser' => Auth::user()->name ?? Auth::user()->email ?? 'System User',
        'dataUrl' => route('banking.index'),
        'storeUrl' => route('banking.store'),
        'updateUrl' => route('banking.update', '__ID__'),
        'submitUrl' => route('banking.submit', '__ID__'),
        'options' => [
            'banks' => $banks ?? [],
            'bankDocumentTypes' => $bankDocumentTypes ?? [],
            'statuses' => $statuses ?? [],
        ],
        'columns' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'bank', 'label' => 'Bank'],
            ['key' => 'bank_document_type', 'label' => 'Bank Document Type'],
            ['key' => 'document_date', 'label' => 'Document Date'],
            ['key' => 'uploaded_by', 'label' => 'Uploaded By'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'document_url', 'label' => 'Document', 'type' => 'document'],
        ],
        'fields' => [
            ['key' => 'bank', 'label' => 'Bank', 'type' => 'select', 'optionsKey' => 'banks', 'otherKey' => 'bank_other', 'required' => true],
            ['key' => 'bank_document_type', 'label' => 'Bank Document Type', 'type' => 'select', 'optionsKey' => 'bankDocumentTypes', 'otherKey' => 'bank_document_type_other', 'required' => true],
            ['key' => 'document_date', 'label' => 'Document Date', 'type' => 'date', 'required' => true],
            ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'optionsKey' => 'statuses'],
        ],
    ];
@endphp

@include('corporate.partials.repository-module', ['config' => $moduleConfig])
