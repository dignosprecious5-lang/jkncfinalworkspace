@extends('layouts.app')
@section('title', 'GIS')
@section('content')

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4"
     x-data="{
        openPanel:false,
        statusTab:null
     }">

    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-white">
            @include('corporate.partials.section-ribbon', ['activeTab' => 'gis', 'topButtonLabel' => 'SEC-GIS'])
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

        <div class="bg-gray-50 min-h-[680px] px-6 py-4">

            <div class="px-0 pb-4">
                <div class="border border-green-200 bg-green-50 text-green-800 text-[14px] px-4 py-3 rounded-md"
                     x-show="statusTab === null || statusTab === 'accepted'">
                    These GIS records were already accepted and approved.
                </div>

                <div class="border border-green-200 bg-green-50 text-green-800 text-[14px] px-4 py-3 rounded-md"
                     x-show="statusTab === 'uploaded'">
                    These GIS records are uploaded drafts and not yet submitted for approval.
                </div>

                <div class="border border-blue-200 bg-blue-50 text-blue-800 text-[14px] px-4 py-3 rounded-md"
                     x-show="statusTab === 'submitted'">
                    These GIS records have already been submitted and are waiting for review.
                </div>

                <div class="border border-yellow-200 bg-yellow-50 text-yellow-800 text-[14px] px-4 py-3 rounded-md"
                     x-show="statusTab === 'reverted'">
                    These GIS records were reverted and need correction before resubmission.
                </div>

                <div class="border border-gray-200 bg-gray-50 text-gray-700 text-[14px] px-4 py-3 rounded-md"
                     x-show="statusTab === 'archived'">
                    These GIS records are archived for reference.
                </div>
            </div>

            <div class="overflow-x-auto bg-white border border-gray-200 rounded">

                <table class="min-w-full text-[11px]">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-2 text-left">Date Upload</th>
                            <th class="px-3 py-2 text-left">Uploaded by</th>
                            <th class="px-3 py-2 text-left">Sec-Submission Status</th>
                            <th class="px-3 py-2 text-left">Sec-Receive on</th>
                            <th class="px-3 py-2 text-left">Sec-Period Date</th>
                            <th class="px-3 py-2 text-left">Company Reg No.</th>
                            <th class="px-3 py-2 text-left">Corporation Name</th>
                            <th class="px-3 py-2 text-left">Date of Annual Meeting</th>
                            <th class="px-3 py-2 text-left">Type of Meeting</th>
                            <th class="px-3 py-2 text-left">Workflow Status</th>
                            <th class="px-3 py-2 text-left">Files</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($gis ?? [] as $row)
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

                                $hasDraft = !empty($row->file);
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
                                data-url="{{ route('gis.show', $row->id) }}"
                                onclick="window.location.href=this.dataset.url"
                                class="border-b hover:bg-gray-50 cursor-pointer">

                                <td class="px-3 py-2">{{ $row->created_at ? $row->created_at->format('M d, Y') : '' }}</td>
                                <td class="px-3 py-2 font-medium">{{ $row->uploaded_by }}</td>
                                <td class="px-3 py-2">{{ $row->submission_status }}</td>
                                <td class="px-3 py-2">{{ $row->receive_on }}</td>
                                <td class="px-3 py-2">{{ $row->period_date }}</td>
                                <td class="px-3 py-2">{{ $row->company_reg_no }}</td>
                                <td class="px-3 py-2">{{ $row->corporation_name }}</td>
                                <td class="px-3 py-2">{{ $row->annual_meeting }}</td>
                                <td class="px-3 py-2">{{ $row->meeting_type }}</td>

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
                                                <form action="{{ route('corporate.gis.submit', $row->id) }}"
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
                        @empty
                            <tr>
                                <td colspan="11" class="px-3 py-6 text-center text-gray-400">
                                    No GIS records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

        </div>
    </div>

    <div x-show="openPanel"
         x-transition.opacity
         class="fixed inset-0 bg-black/40 z-40"
         @click="openPanel=false"
         style="display:none">
    </div>

    <div x-show="openPanel"
         x-transition:enter="transform transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 bottom-0 w-[430px] bg-white border-l border-gray-300 shadow-2xl z-50"
         style="display:none">

        <form action="{{ route('gis.store') }}" method="POST" enctype="multipart/form-data" class="h-full flex flex-col">
            @csrf

            <div class="h-16 px-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Add GIS Record</h2>

                <button type="button" @click="openPanel=false">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">

                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Uploaded By</label>
                    <input
                        type="text"
                        value="{{ auth()->user()->name ?? auth()->user()->full_name ?? auth()->user()->employee_name ?? auth()->user()->username ?? auth()->user()->email }}"
                        class="w-full border rounded p-2 bg-gray-50"
                        readonly
                    >
                </div>

                <select name="submission_status" class="w-full border rounded p-2">
                    <option>Submitted</option>
                    <option>Received</option>
                    <option>Pending</option>
                </select>

                <input name="receive_on" type="date" class="w-full border rounded p-2">

                <input name="period_date" placeholder="Period Date" class="w-full border rounded p-2">

                <input name="company_reg_no" placeholder="Company Reg No" class="w-full border rounded p-2">

                <input name="corporation_name" placeholder="Corporation Name" class="w-full border rounded p-2">

                <input name="annual_meeting" type="date" class="w-full border rounded p-2">

                <select name="meeting_type" class="w-full border rounded p-2">
                    <option>Regular Annual Meeting</option>
                    <option>Special Meeting</option>
                </select>



                <div class="pt-2">
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Corporation Logo</label>
                    <label class="w-full min-h-[84px] border border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 flex flex-col items-center justify-center gap-2 px-4 cursor-pointer transition">
                        <i class="far fa-image text-[26px] text-gray-500"></i>
                        <span class="text-[14px] text-blue-600 font-medium">Choose logo image</span>
                        <span class="text-[11px] text-gray-400">Optional • JPG, PNG, WEBP</span>
                        <input type="file" name="logo_upload" accept="image/*" class="hidden">
                    </label>
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

            <div class="px-6 py-4 border-t flex justify-end gap-3">

                <button type="button" @click="openPanel=false"
                        class="px-4 py-2 border rounded">
                    Cancel
                </button>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded">
                    Save
                </button>

            </div>

        </form>

    </div>

</div>
@endsection