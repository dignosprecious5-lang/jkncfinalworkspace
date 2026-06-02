@extends('layouts.app')
@section('title', 'Transmittal Preview')

@section('content')
@php
    $itemAttachmentItems = $transmittal->items->filter(fn($item) => !empty($item->attachment_path))->values();
    $recordAttachmentItems = ($transmittal->attachments ?? collect())->map(function ($attachment) {
        return (object) [
            'attachment_path' => $attachment->file_path,
            'remarks' => 'Supporting Attachment',
            'original_name' => $attachment->original_name,
        ];
    });
    $attachmentItems = $itemAttachmentItems->concat($recordAttachmentItems)->values();

    $firstAttachment = $attachmentItems->first();

    $firstAttachmentUrl = $firstAttachment ? asset('storage/' . $firstAttachment->attachment_path) : null;
    $firstAttachmentName = $firstAttachment ? basename($firstAttachment->attachment_path) : null;
    $firstAttachmentExt = $firstAttachment ? strtolower(pathinfo($firstAttachment->attachment_path, PATHINFO_EXTENSION)) : null;
@endphp

<div
    class="w-full h-full px-6 py-5"
    x-data="{
        activeTab: 'transmittal',
        selectedAttachmentUrl: @js($firstAttachmentUrl),
        selectedAttachmentName: @js($firstAttachmentName),
        selectedAttachmentExt: @js($firstAttachmentExt),
        setAttachment(url, name, ext) {
            this.selectedAttachmentUrl = url;
            this.selectedAttachmentName = name;
            this.selectedAttachmentExt = ext;
        },
        isImage(ext) {
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes((ext || '').toLowerCase());
        },
        isPdf(ext) {
            return (ext || '').toLowerCase() === 'pdf';
        }
    }"
>
    <div class="bg-white border border-gray-200 rounded-xl min-h-[calc(100vh-7rem)] overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h1 class="text-[28px] font-semibold text-gray-800 leading-none">Transmittal Preview</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $transmittal->transmittal_no ?? 'N/A' }}</p>
            </div>

            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('transmittal.send-email', $transmittal->id) }}" onsubmit="return confirm('Send this transmittal by email with PDF and attachments?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                        Send Email
                    </button>
                </form>

                <a href="{{ url('/transmittal') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Back
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mx-5 mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="p-5 bg-[#f8fafc]">
            <div class="grid grid-cols-1 2xl:grid-cols-[1fr_420px] gap-5">
                <div class="space-y-5">
                    <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                        <!-- Tabs -->
                        <div class="px-4 pt-3 border-b border-gray-200 bg-white">
                            <div class="flex items-end gap-6">
                                <button
                                    type="button"
                                    @click="activeTab = 'transmittal'"
                                    :class="activeTab === 'transmittal'
                                        ? 'text-blue-600 border-b-2 border-blue-600'
                                        : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700'"
                                    class="pb-3 text-sm font-medium transition">
                                    Transmittal
                                </button>

                                <button
                                    type="button"
                                    @click="activeTab = 'receipt'"
                                    :class="activeTab === 'receipt'
                                        ? 'text-blue-600 border-b-2 border-blue-600'
                                        : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700'"
                                    class="pb-3 text-sm font-medium transition">
                                    Receipt
                                </button>

                                <button
                                    type="button"
                                    @click="activeTab = 'attachments'"
                                    :class="activeTab === 'attachments'
                                        ? 'text-blue-600 border-b-2 border-blue-600'
                                        : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700'"
                                    class="pb-3 text-sm font-medium transition">
                                    Attachments
                                </button>
                            </div>
                        </div>

                        <!-- Header -->
                        <div class="px-4 py-3 border-b border-gray-200 bg-white flex items-center justify-between">
                            <div x-show="activeTab === 'transmittal'">
                                <h3 class="text-sm font-semibold text-gray-900">Transmittal Form Preview</h3>
                                <p class="text-xs text-gray-500">{{ ucfirst($transmittal->workflow_status ?? 'N/A') }}</p>
                            </div>

                            <div x-show="activeTab === 'receipt'" x-cloak>
                                <h3 class="text-sm font-semibold text-gray-900">Receipt Preview</h3>
                                <p class="text-xs text-gray-500">{{ optional($transmittal->receipt)->receipt_no ?? 'No receipt number yet' }}</p>
                            </div>

                            <div x-show="activeTab === 'attachments'" x-cloak>
                                <h3 class="text-sm font-semibold text-gray-900">Attachment Preview</h3>
                                <p class="text-xs text-gray-500">Uploaded file preview</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a x-show="activeTab === 'transmittal'"
                                   href="{{ $transmittalPdfUrl }}"
                                   target="_blank"
                                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    Open
                                </a>

                                @if(!empty($receiptPdfUrl))
                                    <a x-show="activeTab === 'receipt'" x-cloak
                                       href="{{ $receiptPdfUrl }}"
                                       target="_blank"
                                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                        Open
                                    </a>
                                @endif

                                <a x-show="activeTab === 'attachments' && selectedAttachmentUrl" x-cloak
                                   :href="selectedAttachmentUrl"
                                   target="_blank"
                                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    Open
                                </a>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-4 bg-[#f3f4f6]">
                            <!-- Transmittal -->
                            <div x-show="activeTab === 'transmittal'">
                                <div class="pdf-preview-frame">
                                    <iframe
                                        src="{{ $transmittalPdfUrl }}"
                                        class="w-full h-[920px] bg-white"
                                        frameborder="0">
                                    </iframe>
                                </div>
                            </div>

                            <!-- Receipt -->
                            <div x-show="activeTab === 'receipt'" x-cloak>
                                @if(!empty($receiptPdfUrl))
                                    <div class="pdf-preview-frame">
                                        <iframe
                                            src="{{ $receiptPdfUrl }}"
                                            class="w-full h-[920px] bg-white"
                                            frameborder="0">
                                        </iframe>
                                    </div>
                                @else
                                    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center text-sm text-gray-500">
                                        Receipt will appear here once the transmittal is approved.
                                    </div>
                                @endif
                            </div>

                            <!-- Attachments -->
                            <div x-show="activeTab === 'attachments'" x-cloak>
                                @if($attachmentItems->count())
                                    <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-4">
                                        <!-- Left list -->
                                        <div class="space-y-3">
                                            @foreach($attachmentItems as $item)
                                                @php
                                                    $fileUrl = asset('storage/' . $item->attachment_path);
                                                    $fileName = basename($item->attachment_path);
                                                    $fileExt = strtolower(pathinfo($item->attachment_path, PATHINFO_EXTENSION));
                                                @endphp

                                                <button
                                                    type="button"
                                                    @click="setAttachment('{{ $fileUrl }}', '{{ addslashes($fileName) }}', '{{ $fileExt }}')"
                                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-left hover:border-blue-400 hover:bg-blue-50 transition"
                                                >
                                                    <p class="text-sm font-semibold text-gray-900">
                                                        {{ $item->particular ?? $item->particulars ?? $item->description ?? $item->item_name ?? ('Item ' . ($item->item_no ?? $loop->iteration)) }}
                                                    </p>

                                                    <p class="mt-1 text-xs text-gray-500 break-all">
                                                        {{ $item->original_name ?? $fileName }}
                                                    </p>

                                                    @if(!empty($item->remarks))
                                                        <p class="mt-1 text-xs text-gray-400">
                                                            Remarks: {{ $item->remarks }}
                                                        </p>
                                                    @endif

                                                    <div class="mt-2">
                                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[11px] font-medium text-gray-600">
                                                            {{ strtoupper($fileExt) }}
                                                        </span>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>

                                        <!-- Right preview -->
                                        <div class="border border-gray-200 rounded-xl overflow-hidden bg-white min-h-[760px]">
                                            <div class="px-4 py-3 border-b border-gray-200 bg-white">
                                                <h4 class="text-sm font-semibold text-gray-900" x-text="selectedAttachmentName || 'Attachment Preview'"></h4>
                                            </div>

                                            <template x-if="selectedAttachmentUrl && isPdf(selectedAttachmentExt)">
                                                <iframe
                                                    :src="selectedAttachmentUrl"
                                                    class="w-full h-[700px] bg-white"
                                                    frameborder="0">
                                                </iframe>
                                            </template>

                                            <template x-if="selectedAttachmentUrl && isImage(selectedAttachmentExt)">
                                                <div class="h-[700px] flex items-center justify-center p-4 bg-[#f3f4f6]">
                                                    <img :src="selectedAttachmentUrl" class="max-w-full max-h-full object-contain rounded-lg border border-gray-200 bg-white">
                                                </div>
                                            </template>

                                            <template x-if="selectedAttachmentUrl && !isPdf(selectedAttachmentExt) && !isImage(selectedAttachmentExt)">
                                                <div class="h-[700px] flex items-center justify-center p-6 bg-[#f9fafb]">
                                                    <div class="text-center">
                                                        <p class="text-sm font-medium text-gray-800">This file type cannot be previewed directly.</p>
                                                        <p class="mt-1 text-xs text-gray-500" x-text="selectedAttachmentName"></p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center text-sm text-gray-500">
                                        No item attachments uploaded yet.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side -->
                <div class="space-y-4">
                    <div class="border border-gray-200 rounded-xl bg-white p-5">
                        <h3 class="text-[16px] font-semibold text-gray-900 mb-4">Transmittal Information</h3>

                        <div class="space-y-3 text-sm">
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Ref No</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->transmittal_no ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Date</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->transmittal_date ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Mode</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->mode ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">From</span>
                                <span class="font-medium text-gray-900">
                                    {{ $transmittal->mode === 'SEND' ? ($transmittal->office_name ?? 'N/A') : ($transmittal->party_name ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">To</span>
                                <span class="font-medium text-gray-900">
                                    {{ $transmittal->mode === 'SEND' ? ($transmittal->party_name ?? 'N/A') : ($transmittal->office_name ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Address</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->address ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Delivery Type</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->delivery_type ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Recipient Email</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->recipient_email ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Workflow</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->workflow_status ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Approval</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->approval_status ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-xl bg-white p-5">
                        <h3 class="text-[16px] font-semibold text-gray-900 mb-4">Receipt Information</h3>

                        <div class="space-y-3 text-sm">
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Receipt No</span>
                                <span class="font-medium text-gray-900">{{ optional($transmittal->receipt)->receipt_no ?? 'Not yet generated' }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Receipt File</span>
                                <span class="font-medium text-gray-900">{{ !empty($receiptPdfUrl) ? 'Available' : 'Not available' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-xl bg-white p-5">
                        <h3 class="text-[16px] font-semibold text-gray-900 mb-4">Attachment Summary</h3>

                        <div class="space-y-3 text-sm">
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">With Files</span>
                                <span class="font-medium text-gray-900">{{ $attachmentItems->count() }}</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <span class="text-gray-500">Total Items</span>
                                <span class="font-medium text-gray-900">{{ $transmittal->items->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End right side -->
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] {
        display: none !important;
    }

    .pdf-preview-frame {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
</style>
@endsection
