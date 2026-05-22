@extends('layouts.app')
@section('title', 'SEC – Articles Of Incorporation')
@section('content')
@php
    $hasAcceptedGis = filled(optional($latestAcceptedGis)->id);
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4"
     x-data="{
        openPanel: false,
        statusTab: null
     }">

<div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-white">
        @include('corporate.partials.section-ribbon', ['activeTab' => 'sec_aoi', 'topButtonLabel' => 'Generate AOI from GIS'])
    </div>

    <div class="px-4 pt-4 bg-white border-b border-gray-100">
        <div class="flex gap-8 text-[15px] text-gray-700 overflow-x-auto">

            <button
                @click="statusTab = 'uploaded'"
                :class="statusTab === 'uploaded' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                class="pb-3 whitespace-nowrap">
                Uploaded
            </button>

            <button
                @click="statusTab = 'submitted'"
                :class="statusTab === 'submitted' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                class="pb-3 whitespace-nowrap">
                Submitted
            </button>

            <button
                @click="statusTab = 'accepted'"
                :class="statusTab === 'accepted' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                class="pb-3 whitespace-nowrap">
                Accepted
            </button>

            <button
                @click="statusTab = 'reverted'"
                :class="statusTab === 'reverted' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                class="pb-3 whitespace-nowrap">
                Reverted
            </button>

            <button
                @click="statusTab = 'archived'"
                :class="statusTab === 'archived' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                class="pb-3 whitespace-nowrap">
                Archived
            </button>

        </div>
    </div>

    <div class="bg-gray-50 min-h-[680px]">

        <div class="px-4 pt-4">
            @if ($hasAcceptedGis)
                <div class="mb-3 border border-blue-200 bg-blue-50 text-blue-800 text-[13px] px-4 py-3 rounded-md">
                    Source GIS loaded: <strong>{{ $latestAcceptedGis->corporation_name ?: 'Unnamed Corporation' }}</strong>
                    @if($latestAcceptedGis->company_reg_no)
                        <span class="text-blue-700">({{ $latestAcceptedGis->company_reg_no }})</span>
                    @endif
                    <span class="block text-[12px] mt-1">SEC-AOI will auto-fill corporation name, SEC registration number, and principal address from the latest accepted GIS record.</span>
                </div>
            @else
                <div class="mb-3 border border-red-200 bg-red-50 text-red-800 text-[13px] px-4 py-3 rounded-md">
                    Please complete and accept a GIS record first before creating a SEC-AOI record.
                </div>
            @endif
        </div>


        <div class="px-4 pt-4">
            <div class="border border-green-200 bg-green-50 text-green-800 text-[14px] px-4 py-3 rounded-md"
                 x-show="statusTab === null || statusTab === 'accepted'">
                These SEC-AOI records were already accepted and approved.
            </div>

            <div class="border border-green-200 bg-green-50 text-green-800 text-[14px] px-4 py-3 rounded-md"
                 x-show="statusTab === 'uploaded'">
                These SEC-AOI records are uploaded drafts and not yet submitted for approval.
            </div>

            <div class="border border-blue-200 bg-blue-50 text-blue-800 text-[14px] px-4 py-3 rounded-md"
                 x-show="statusTab === 'submitted'">
                These SEC-AOI records have already been submitted and are waiting for review.
            </div>

            <div class="border border-yellow-200 bg-yellow-50 text-yellow-800 text-[14px] px-4 py-3 rounded-md"
                 x-show="statusTab === 'reverted'">
                These SEC-AOI records were reverted and need correction before resubmission.
            </div>

            <div class="border border-gray-200 bg-gray-50 text-gray-700 text-[14px] px-4 py-3 rounded-md"
                 x-show="statusTab === 'archived'">
                These SEC-AOI records are archived for reference.
            </div>
        </div>

        <div class="p-3">
            <div class="overflow-x-auto border border-gray-200 rounded-md bg-white">

                <table class="min-w-full text-[11px] text-left text-gray-700">
                    <thead class="bg-white border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2 font-semibold">Date Upload</th>
                            <th class="px-3 py-2 font-semibold">Uploaded By</th>
                            <th class="px-3 py-2 font-semibold">Company Reg No.</th>
                            <th class="px-3 py-2 font-semibold">Corporation Name</th>
                            <th class="px-3 py-2 font-semibold">Principal Address</th>
                            <th class="px-3 py-2 font-semibold">Par Value</th>
                            <th class="px-3 py-2 font-semibold">Authorized Capital Stock</th>
                            <th class="px-3 py-2 font-semibold">Number of Directors</th>
                            <th class="px-3 py-2 font-semibold">Type of Formation</th>
                            <th class="px-3 py-2 font-semibold">SEC-AOI Version</th>
                            <th class="px-3 py-2 font-semibold">Type of Version</th>
                            <th class="px-3 py-2 font-semibold">Workflow Status</th>
                            <th class="px-3 py-2 font-semibold">Files</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($records as $row)
                            @php
                                $workflow = $row->workflow_status;

                                if (!$workflow) {
                                    if ($row->approval_status === 'Approved') {
                                        $workflow = 'Accepted';
                                    } elseif ($row->approval_status === 'Needs Revision' || $row->approval_status === 'Rejected') {
                                        $workflow = 'Reverted';
                                    } else {
                                        $workflow = 'Uploaded';
                                    }
                                }

                                $showInUploaded = $workflow === 'Uploaded';
                                $showInSubmitted = $workflow === 'Submitted';
                                $showInAccepted = $workflow === 'Accepted';
                                $showInReverted = $workflow === 'Reverted';
                                $showInArchived = $workflow === 'Archived';

                                $hasDraft = !empty($row->file_path);
                                $hasNotary = !empty($row->notary_file_path);
                                $canSubmit = $hasDraft && $hasNotary;

                                $fileLabel = match(true) {
                                    $hasDraft && $hasNotary => 'Draft + Notary',
                                    $hasDraft => 'Draft Only',
                                    $hasNotary => 'Notary Only',
                                    default => 'No File',
                                };

                                $badgeClass = match($workflow) {
                                    'Accepted' => 'bg-green-50 text-green-700',
                                    'Reverted' => 'bg-yellow-50 text-yellow-700',
                                    'Archived' => 'bg-gray-100 text-gray-700',
                                    'Submitted' => 'bg-blue-50 text-blue-700',
                                    default => 'bg-orange-50 text-orange-700',
                                };
                            @endphp

                            <tr
                                x-show="
                                    (statusTab === null && {{ $showInAccepted ? 'true' : 'false' }}) ||
                                    (statusTab === 'uploaded' && {{ $showInUploaded ? 'true' : 'false' }}) ||
                                    (statusTab === 'submitted' && {{ $showInSubmitted ? 'true' : 'false' }}) ||
                                    (statusTab === 'accepted' && {{ $showInAccepted ? 'true' : 'false' }}) ||
                                    (statusTab === 'reverted' && {{ $showInReverted ? 'true' : 'false' }}) ||
                                    (statusTab === 'archived' && {{ $showInArchived ? 'true' : 'false' }})
                                "
                                data-url="{{ route('corporate.sec_aoi.show', $row->id) }}"
                                onclick="window.location.href=this.dataset.url"
                                class="border-b border-gray-200 hover:bg-gray-50 cursor-pointer">

                                <td class="px-3 py-2">{{ $row->date_upload }}</td>
                                <td class="px-3 py-2 font-semibold text-gray-800">{{ $row->uploaded_by }}</td>
                                <td class="px-3 py-2">{{ $row->company_reg_no }}</td>
                                <td class="px-3 py-2">{{ $row->corporation_name }}</td>
                                <td class="px-3 py-2">{{ $row->principal_address }}</td>
                                <td class="px-3 py-2">{{ $row->par_value }}</td>
                                <td class="px-3 py-2">{{ $row->authorized_capital_stock }}</td>
                                <td class="px-3 py-2">{{ $row->directors }}</td>
                                <td class="px-3 py-2">{{ $row->type_of_formation }}</td>
                                <td class="px-3 py-2">{{ $row->aoi_version }}</td>
                                <td class="px-3 py-2">{{ $row->aoi_type }}</td>

                                <td class="px-3 py-2">
                                    <span class="px-2 py-1 rounded-full text-[10px] font-medium {{ $badgeClass }}">
                                        {{ $workflow }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-blue-600 font-medium">
                                    <div class="flex flex-col items-start gap-2">
                                        <span>{{ $fileLabel }}</span>

                                        @if($workflow === 'Uploaded' || $workflow === 'Reverted')
                                            @if($canSubmit)
                                                <form action="{{ route('corporate.sec_aoi.submit', $row->id) }}"
                                                      method="POST"
                                                      onclick="event.stopPropagation();">
                                                    @csrf
                                                    <button type="submit"
                                                            class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                                        Submit
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button"
                                                        onclick="event.stopPropagation();"
                                                        disabled
                                                        title="Both Draft and Notary files are required before submitting"
                                                        class="px-3 py-1.5 text-xs rounded-md bg-gray-200 text-gray-500 cursor-not-allowed">
                                                    Incomplete
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        @if(count($records) === 0)
                            <tr>
                                <td colspan="13" class="px-3 py-6 text-center text-gray-400">
                                    No records found.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>

            </div>
        </div>

    </div>

</div>

<div x-show="openPanel"
x-transition.opacity
class="fixed inset-0 z-[70] bg-black/35"
style="display:none;"
@click="openPanel = false">
</div>

<div x-show="openPanel"
x-transition:enter="transform transition ease-out duration-300"
x-transition:enter-start="translate-x-full"
x-transition:enter-end="translate-x-0"
x-transition:leave="transform transition ease-in duration-200"
x-transition:leave-start="translate-x-0"
x-transition:leave-end="translate-x-full"
class="fixed top-0 right-0 bottom-0 z-[80] w-[430px] bg-white border-l border-gray-300 shadow-2xl"
style="display:none;">

<form action="{{ route('corporate.sec_aoi.store') }}" method="POST" enctype="multipart/form-data" class="h-full flex flex-col">
@csrf

<div class="h-16 px-6 border-b border-gray-200 flex items-center justify-between">
    <h2 class="text-[26px] font-semibold text-gray-900 leading-none">
        Generate SEC-AOI from GIS
    </h2>

    <button
        type="button"
        @click="openPanel = false"
        class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-500 hover:text-gray-800 flex items-center justify-center transition">
        <i class="fas fa-times text-sm"></i>
    </button>
</div>

<div class="flex-1 overflow-y-auto px-6 py-6">
    <div class="space-y-5">

        <div>
            <label class="block text-[13px] font-medium text-gray-700 mb-2">Corporation Name</label>
            <input type="text" name="corporation_name" value="{{ old('corporation_name', optional($latestAcceptedGis)->corporation_name) }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-gray-50" readonly>
        </div>

        <div>
            <label class="block text-[13px] font-medium text-gray-700 mb-2">Company Reg No.</label>
            <input type="text" name="company_reg_no" value="{{ old('company_reg_no', optional($latestAcceptedGis)->company_reg_no) }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-gray-50" readonly>
        </div>

        <div>
            <label class="block text-[13px] font-medium text-gray-700 mb-2">Principal Address</label>
            <input type="text" name="principal_address" value="{{ old('principal_address', optional($latestAcceptedGis)->principal_address ?: optional($latestAcceptedGis)->business_address) }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-gray-50" readonly>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-[13px] font-medium text-gray-700 mb-2">Par Value</label>
                <input type="text" name="par_value" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
            </div>

            <div>
                <label class="block text-[13px] font-medium text-gray-700 mb-2">No. of Directors</label>
                <input type="number" name="directors" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-[13px] font-medium text-gray-700 mb-2">Authorized Capital Stock</label>
            <input type="text" name="authorized_capital_stock" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-[13px] font-medium text-gray-700 mb-2">Type of Formation</label>
                <select name="type_of_formation" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-white">
                    <option>Stock Corporation</option>
                    <option>Non-Stock Corporation</option>
                </select>
            </div>

            <div>
                <label class="block text-[13px] font-medium text-gray-700 mb-2">SEC-AOI Version</label>
                <input type="text" name="aoi_version" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-[13px] font-medium text-gray-700 mb-2">Type of SEC-AOI Version</label>
            <select name="aoi_type" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-white">
                <option>Original</option>
                <option>Amended</option>
                <option>Revised</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-[13px] font-medium text-gray-700 mb-2">Uploaded By</label>
                <input
                    type="text"
                    value="{{ auth()->user()->name ?? auth()->user()->full_name ?? auth()->user()->employee_name ?? auth()->user()->username ?? auth()->user()->email }}"
                    class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-gray-50"
                    readonly
                >
            </div>

            <div>
                <label class="block text-[13px] font-medium text-gray-700 mb-2">Date Upload</label>
                <input type="date" name="date_upload" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
            </div>
        </div>

        <div class="pt-2">
            <label class="block text-[13px] font-medium text-gray-700 mb-2">Draft File Upload</label>
            <label class="w-full min-h-[84px] border border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 flex flex-col items-center justify-center gap-2 px-4 cursor-pointer transition">
                <i class="far fa-file-alt text-[26px] text-gray-500"></i>
                <span class="text-[14px] text-blue-600 font-medium">Choose draft file</span>
                <span class="text-[11px] text-gray-400">Optional • PDF, DOC, DOCX supported</span>
                <input type="file" name="draft_file_upload" class="hidden">
            </label>
        </div>

        <div class="pt-2">
            <label class="block text-[13px] font-medium text-gray-700 mb-2">Notary File Upload</label>
            <label class="w-full min-h-[84px] border border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 flex flex-col items-center justify-center gap-2 px-4 cursor-pointer transition">
                <i class="far fa-file-alt text-[26px] text-gray-500"></i>
                <span class="text-[14px] text-blue-600 font-medium">Choose notary file</span>
                <span class="text-[11px] text-gray-400">Optional • PDF, DOC, DOCX supported</span>
                <input type="file" name="notary_file_upload" class="hidden">
            </label>
        </div>

    </div>
</div>

<div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
    <button
        type="button"
        @click="openPanel = false"
        class="min-w-[92px] px-6 py-2.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
        Cancel
    </button>

    <button
        type="submit"
        @if(! $hasAcceptedGis) disabled @endif
        class="min-w-[92px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">
        Generate from GIS
    </button>
</div>

</form>
</div>
</div>
@endsection
