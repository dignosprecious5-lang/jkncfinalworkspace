@extends('layouts.app')
@section('title', 'SEC – Certificate Of Incorporation')

@section('content')

@php
    $normalizeFilePath = function ($path) {
        if (empty($path)) { return null; }
        $path = ltrim($path, '/');
        $path = preg_replace('#^public/#', '', $path);
        $path = preg_replace('#^storage/#', '', $path);
        return $path;
    };

    $draftPath  = $normalizeFilePath($record->file_path);
    $notaryPath = $normalizeFilePath($record->notary_file_path);
    $draftUrl   = $draftPath  ? asset('storage/' . $draftPath)  : null;
    $notaryUrl  = $notaryPath ? asset('storage/' . $notaryPath) : null;

    $canEditRecord = auth()->user()?->hasPermission('approve_corporate')
        || in_array($record->workflow_status, ['Uploaded', 'Reverted', null]);
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4">

            <div class="mb-4 flex items-center gap-3">
                <a href="{{ route('company.corporate-formation.sec-coi', $company->id) }}"
                   class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Back to SEC-COI
                </a>
            </div>

            @if(session('corporate_formation_success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700 text-sm">
                    {{ session('corporate_formation_success') }}
                </div>
            @endif

            <div x-data="{
                    fileTab: '{{ $draftUrl ? 'draft' : ($notaryUrl ? 'notary' : 'draft') }}',
                    editDraft: false,
                    editNotary: false,
                    editDetails: false
                 }">

                <h1 class="text-2xl font-semibold mb-6">SEC – Certificate Of Incorporation</h1>

                <div class="grid grid-cols-3 gap-6">

                    {{-- FILE VIEWER --}}
                    <div class="col-span-2 bg-white border rounded-lg p-4">
                        <div class="flex items-center gap-6 border-b border-gray-200 mb-4">
                            <button @click="fileTab = 'draft'"
                                    :class="fileTab === 'draft' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600'"
                                    class="pb-3 text-sm">Draft</button>
                            <button @click="fileTab = 'notary'"
                                    :class="fileTab === 'notary' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600'"
                                    class="pb-3 text-sm">Notary</button>
                        </div>

                        <div x-show="fileTab === 'draft'">
                            @if($draftUrl)
                                <iframe src="{{ $draftUrl }}" class="w-full h-[700px] border rounded"></iframe>
                                <div class="mt-3">
                                    <a href="{{ $draftUrl }}" target="_blank"
                                       class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">
                                        Open Draft File
                                    </a>
                                </div>
                            @else
                                <div class="w-full h-[700px] border rounded flex items-center justify-center bg-gray-50 text-gray-400 text-sm">
                                    No draft file attached for this SEC-COI record.
                                </div>
                            @endif
                        </div>

                        <div x-show="fileTab === 'notary'" x-cloak>
                            @if($notaryUrl)
                                <iframe src="{{ $notaryUrl }}" class="w-full h-[700px] border rounded"></iframe>
                                <div class="mt-3">
                                    <a href="{{ $notaryUrl }}" target="_blank"
                                       class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 text-white text-sm hover:bg-green-700">
                                        Open Notary File
                                    </a>
                                </div>
                            @else
                                <div class="w-full h-[700px] border rounded flex flex-col items-center justify-center bg-gray-50 text-gray-400 text-sm px-6 text-center">
                                    <i class="far fa-file-alt text-4xl mb-4"></i>
                                    <p class="font-medium text-gray-500 mb-1">No notarized file attached yet.</p>
                                    <p class="text-gray-400">This section is reserved for the final notarized document.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- INFORMATION PANEL --}}
                    <div class="bg-white border rounded-lg p-6 space-y-4">

                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Certificate Information</h2>
                            @if($canEditRecord)
                                <button type="button" @click="editDetails = !editDetails"
                                        class="text-xs px-3 py-1.5 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                                    <span x-text="editDetails ? 'Cancel' : 'Edit Details'"></span>
                                </button>
                            @endif
                        </div>

                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Corporation</span>
                            <span class="font-medium text-right">{{ $record->corporate_name }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Company Reg No.</span>
                            <span class="text-right">{{ $record->company_reg_no }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Date Upload</span>
                            <span class="text-right">{{ $record->date_upload }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Date Created</span>
                            <span class="text-right">{{ $record->created_at?->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Issued On</span>
                            <span class="text-right">{{ $record->issued_on }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Issued By</span>
                            <span class="text-right">{{ $record->issued_by }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Workflow Status</span>
                            <span class="text-right">
                                @php
                                    $ws = $record->workflow_status ?? 'Uploaded';
                                    $wsBadge = match($ws) { 'Accepted' => 'bg-green-50 text-green-700', 'Reverted' => 'bg-yellow-50 text-yellow-700', 'Archived' => 'bg-gray-100 text-gray-700', 'Submitted' => 'bg-blue-50 text-blue-700', default => 'bg-orange-50 text-orange-700' };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $wsBadge }}">{{ $ws }}</span>
                            </span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Approval Status</span>
                            <span class="text-right">
                                @php
                                    $as = $record->approval_status ?? 'Pending';
                                    $asBadge = match($as) { 'Approved' => 'bg-green-50 text-green-700', 'Needs Revision' => 'bg-yellow-50 text-yellow-700', 'Rejected' => 'bg-red-50 text-red-700', default => 'bg-blue-50 text-blue-700' };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $asBadge }}">{{ $as }}</span>
                            </span>
                        </div>

                        @if($canEditRecord)
                            <form x-show="editDetails" x-cloak
                                  action="{{ route('company.corporate-formation.sec-coi.update', [$company->id, $record->id]) }}"
                                  method="POST" class="space-y-3 pt-4 border-t border-gray-100">
                                @csrf
                                @method('PUT')
                                <input type="text" name="corporate_name" value="{{ $record->corporate_name }}" placeholder="Corporate Name" class="w-full border rounded p-2" required>
                                <input type="text" name="company_reg_no" value="{{ $record->company_reg_no }}" placeholder="Company Reg No." class="w-full border rounded p-2" required>
                                <input type="text" name="issued_by" value="{{ $record->issued_by }}" placeholder="Issued By" class="w-full border rounded p-2" required>
                                <input type="date" name="issued_on" value="{{ $record->issued_on }}" class="w-full border rounded p-2" required>
                                <input type="date" name="date_upload" value="{{ $record->date_upload }}" class="w-full border rounded p-2" required>
                                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Save Details</button>
                            </form>
                        @endif

                        {{-- DRAFT FILE --}}
                        <div class="pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-700 uppercase">Draft File</h3>
                                @if($canEditRecord && $draftUrl)
                                    <button type="button" @click="editDraft = !editDraft"
                                            class="text-xs px-3 py-1.5 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                                        <span x-text="editDraft ? 'Cancel' : 'Edit'"></span>
                                    </button>
                                @endif
                            </div>

                            @if($draftUrl)
                                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 mb-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Draft file attached</p>
                                        <p class="text-xs text-gray-400">{{ basename($record->file_path) }}</p>
                                    </div>
                                    <button type="button" @click="fileTab = 'draft'"
                                            class="text-xs px-3 py-1.5 rounded-md bg-blue-600 text-white hover:bg-blue-700">View</button>
                                </div>
                            @else
                                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-4 text-sm text-gray-400 mb-3">
                                    No draft file attached yet.
                                </div>
                            @endif

                            @if($canEditRecord)
                                <form x-show="editDraft || !{{ $draftUrl ? 'true' : 'false' }}" x-cloak
                                      action="{{ route('company.corporate-formation.sec-coi.upload-draft', [$company->id, $record->id]) }}"
                                      method="POST" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <input type="file" name="draft_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full border rounded p-2" required>
                                    <button type="submit" class="block w-full text-center bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Save Draft File</button>
                                </form>
                            @endif
                        </div>

                        {{-- NOTARY FILE --}}
                        <div class="pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-700 uppercase">Notary File</h3>
                                @if($canEditRecord && $notaryUrl)
                                    <button type="button" @click="editNotary = !editNotary"
                                            class="text-xs px-3 py-1.5 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                                        <span x-text="editNotary ? 'Cancel' : 'Edit'"></span>
                                    </button>
                                @endif
                            </div>

                            @if($notaryUrl)
                                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 mb-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Notary file attached</p>
                                        <p class="text-xs text-gray-400">{{ basename($record->notary_file_path) }}</p>
                                    </div>
                                    <button type="button" @click="fileTab = 'notary'"
                                            class="text-xs px-3 py-1.5 rounded-md bg-green-600 text-white hover:bg-green-700">View</button>
                                </div>
                            @else
                                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-4 text-sm text-gray-400 mb-3">
                                    No notary file attached yet.
                                </div>
                            @endif

                            @if($canEditRecord)
                                <form x-show="editNotary || !{{ $notaryUrl ? 'true' : 'false' }}" x-cloak
                                      action="{{ route('company.corporate-formation.sec-coi.upload-notary', [$company->id, $record->id]) }}"
                                      method="POST" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <input type="file" name="notary_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full border rounded p-2" required>
                                    <button type="submit" class="block w-full text-center bg-green-600 text-white py-2 rounded-md hover:bg-green-700">Save Notary File</button>
                                </form>
                            @endif
                        </div>

                        {{-- SUBMIT --}}
                        @if(in_array($record->workflow_status, ['Uploaded', 'Reverted', null]) && $draftUrl && $notaryUrl)
                            <div class="pt-4 border-t border-gray-100">
                                <form action="{{ route('company.corporate-formation.sec-coi.submit', [$company->id, $record->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 text-sm font-medium">
                                        Submit for Approval
                                    </button>
                                </form>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

        </section>
    </div>
</div>

@endsection
