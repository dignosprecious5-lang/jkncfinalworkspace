@extends('layouts.app')

@php
    $moduleConfig = [
        'moduleId' => 'corporateOperationsRepository',
        'title' => 'Operations',
        'purpose' => 'Store operational records, manuals, procedures, reports, project documents, and internal records.',
        'company' => $companyDefaults ?? [],
        'currentUser' => Auth::user()->name ?? Auth::user()->email ?? 'System User',
        'dataUrl' => route('operations.index'),
        'storeUrl' => route('operations.store'),
        'updateUrl' => route('operations.update', '__ID__'),
        'submitUrl' => route('operations.submit', '__ID__'),
        'noteStoreUrl' => route('operations.notes.store', '__ID__'),
        'noteDeleteUrl' => route('operations.notes.destroy', ['id' => '__ID__', 'note' => '__NOTE__']),
        'enableNotes' => true,
        'options' => [
            'operationTypes' => $operationTypes ?? [],
            'operationDocumentTypes' => $operationDocumentTypes ?? [],
            'statuses' => $statuses ?? [],
        ],
        'columns' => [
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'operation_type', 'label' => 'Operation Type'],
            ['key' => 'document_type', 'label' => 'Document Type'],
            ['key' => 'document_title', 'label' => 'Document Title'],
            ['key' => 'document_date', 'label' => 'Document Date'],
            ['key' => 'uploaded_by', 'label' => 'Uploaded By'],
            ['key' => 'status', 'label' => 'Status'],
        ],
        'fields' => [
            ['key' => 'operation_type', 'label' => 'Operation Type', 'type' => 'select', 'optionsKey' => 'operationTypes', 'otherKey' => 'operation_type_other', 'required' => true],
            ['key' => 'document_type', 'label' => 'Document Type', 'type' => 'select', 'optionsKey' => 'operationDocumentTypes', 'otherKey' => 'document_type_other', 'required' => true],
            ['key' => 'document_title', 'label' => 'Document Title', 'type' => 'text', 'required' => true],
            ['key' => 'document_date', 'label' => 'Document Date', 'type' => 'date', 'required' => true],
            ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'optionsKey' => 'statuses'],
        ],
    ];
@endphp

@include('corporate.partials.repository-module', ['config' => $moduleConfig])
