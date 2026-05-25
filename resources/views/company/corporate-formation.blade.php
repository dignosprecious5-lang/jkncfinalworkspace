@extends('layouts.app')
@section('title', 'Corporate Formation')

@section('content')
@php
    $tabConfig = [
        ['key' => 'company-info', 'label' => 'Company General Info', 'href' => route('company.corporate-formation.company-info', $company->id)],
        ['key' => 'sec-coi', 'label' => 'SEC-COI', 'href' => route('company.corporate-formation.sec-coi', $company->id)],
        ['key' => 'sec-aoi', 'label' => 'SEC-AOI', 'href' => route('company.corporate-formation.sec-aoi', $company->id)],
        ['key' => 'bylaws', 'label' => 'Bylaws', 'href' => route('company.corporate-formation.bylaws', $company->id)],
        ['key' => 'gis', 'label' => 'GIS', 'href' => route('company.corporate-formation.gis', $company->id)],
        ['key' => 'notices', 'label' => 'Notices of Meeting...', 'href' => route('company.corporate-formation.notices', $company->id)],
        ['key' => 'minutes', 'label' => 'Minutes of Meeting...', 'href' => route('company.corporate-formation.minutes', $company->id)],
        ['key' => 'resolution', 'label' => 'Resolution', 'href' => route('company.corporate-formation.resolutions', $company->id)],
        ['key' => 'secretary', 'label' => 'Secretary...', 'href' => route('company.corporate-formation.secretary-certificates', $company->id)],
    ];
    $activeRibbonIndex = collect($tabConfig)->search(fn ($item) => $item['key'] === $activeTab);
    $initialRibbonScroll = $activeRibbonIndex === false ? 0 : max(0, ($activeRibbonIndex - 1) * 180);
    $topButtonLabel = match ($activeTab) {
        'sec-coi' => 'SEC-COI',
        'sec-aoi' => 'SEC-AOI',
        'bylaws' => 'SEC-BYLAWS',
        default => 'SEC-GIS',
    };

    $formationDefaults = $formationDefaults ?? [];
    $defaultCompanyName = old('corporation_name', $formationDefaults['corporation_name'] ?? $formationDefaults['company_name'] ?? $company->company_name ?? '');
    $defaultCorporateName = old('corporate_name', $formationDefaults['corporate_name'] ?? $formationDefaults['company_name'] ?? $company->company_name ?? '');
    $defaultCompanyRegNo = old('company_reg_no', $formationDefaults['company_reg_no'] ?? '');
    $defaultPrincipalAddress = old('principal_address', $formationDefaults['principal_address'] ?? $formationDefaults['business_address'] ?? $company->address ?? '');
    $defaultTypeOfFormation = old('type_of_formation', $formationDefaults['type_of_formation'] ?? 'Stock Corporation');
    $defaultAoiVersion = old('aoi_version', $formationDefaults['aoi_version'] ?? 'Original');
    $defaultAoiType = old('aoi_type', $formationDefaults['aoi_type'] ?? 'Original');
    $defaultAoiDate = old('aoi_date', $formationDefaults['aoi_date'] ?? '');
    $defaultParValue = old('par_value', $formationDefaults['par_value'] ?? '');
    $defaultAuthorizedCapitalStock = old('authorized_capital_stock', $formationDefaults['authorized_capital_stock'] ?? '');
    $defaultDirectors = old('directors', $formationDefaults['directors'] ?? '');
    $defaultTradeName = old('trade_name', $formationDefaults['trade_name'] ?? '');
    $defaultDateRegistered = old('date_registered', $formationDefaults['date_registered'] ?? '');
    $defaultFiscalYearEnd = old('fiscal_year_end', $formationDefaults['fiscal_year_end'] ?? '');
    $defaultTin = old('tin', $formationDefaults['tin'] ?? '');
    $defaultWebsite = old('website', $formationDefaults['website'] ?? '');
    $defaultEmail = old('email', $formationDefaults['email'] ?? '');
    $defaultOfficialMobile = old('official_mobile', $formationDefaults['official_mobile'] ?? '');
    $defaultAlternateMobile = old('alternate_mobile', $formationDefaults['alternate_mobile'] ?? '');
    $defaultBusinessAddress = old('business_address', $formationDefaults['business_address'] ?? $company->address ?? '');
    $defaultAuditor = old('auditor', $formationDefaults['auditor'] ?? '');
    $defaultIndustry = old('industry', $formationDefaults['industry'] ?? '');
    $defaultGeoCode = old('geo_code', $formationDefaults['geo_code'] ?? '');
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8"
     x-data="{ openPanel: false, statusTab: null }">

    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-white">
                    <div x-data="{
                            scrollStep() {
                                const ribbon = this.$refs.ribbon;
                                const card = ribbon?.querySelector('[data-ribbon-card]');
                                return card ? card.getBoundingClientRect().width * 3 : 540;
                            },
                            prev() {
                                this.$refs.ribbon?.scrollBy({ left: -this.scrollStep(), behavior: 'smooth' });
                            },
                            next() {
                                this.$refs.ribbon?.scrollBy({ left: this.scrollStep(), behavior: 'smooth' });
                            }
                        }"
                        class="flex items-center justify-between gap-3 w-full min-w-0">

                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <button type="button"
                                    class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center"
                                    @click="prev()"
                                    aria-label="Scroll ribbon left">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>

                            <div x-ref="ribbon"
                                 x-init="$nextTick(() => { $el.scrollLeft = {{ $initialRibbonScroll }}; })"
                                 class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap scroll-smooth no-scrollbar">
                                <div class="flex items-stretch min-w-max">
                                    @foreach ($tabConfig as $tabItem)
                                        <a href="{{ $tabItem['href'] }}"
                                           data-ribbon-card
                                           class="shrink-0 w-[180px] px-4 py-3 text-sm font-medium text-center border-t border-b border-r border-gray-200 first:border-l {{ $activeTab === $tabItem['key'] ? 'bg-blue-50 text-blue-700 border-blue-500' : 'bg-white text-gray-800 hover:bg-gray-50' }}">
                                            <span class="block truncate">{{ $tabItem['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <button type="button"
                                    class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center"
                                    @click="next()"
                                    aria-label="Scroll ribbon right">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-bars text-sm"></i>
                            </button>

                            <button type="button" class="w-9 h-9 rounded-full border border-gray-200 text-gray-500 flex items-center justify-center hover:bg-gray-50">
                                <i class="fas fa-table-cells-large text-sm"></i>
                            </button>

                            <div class="flex items-center">
                                <button type="button" @click="openPanel = true"
                                        class="px-4 h-9 rounded-l-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                                    <span class="text-base leading-none">+</span>
                                    {{ $topButtonLabel }}
                                </button>

                                <button type="button"
                                        class="w-10 h-9 rounded-r-full bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center border-l border-white/20">
                                    <i class="fas fa-caret-down text-xs"></i>
                                </button>
                            </div>

                            <button type="button" class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-ellipsis-v text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-4 pt-4 bg-white border-b border-gray-100">
                    <div class="flex gap-8 text-[15px] text-gray-700 overflow-x-auto">
                        <button @click="statusTab = 'uploaded'"
                                :class="statusTab === 'uploaded' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                                class="pb-3 whitespace-nowrap">Uploaded</button>
                        <button @click="statusTab = 'submitted'"
                                :class="statusTab === 'submitted' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                                class="pb-3 whitespace-nowrap">Submitted</button>
                        <button @click="statusTab = 'accepted'"
                                :class="statusTab === 'accepted' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                                class="pb-3 whitespace-nowrap">Accepted</button>
                        <button @click="statusTab = 'reverted'"
                                :class="statusTab === 'reverted' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                                class="pb-3 whitespace-nowrap">Reverted</button>
                        <button @click="statusTab = 'archived'"
                                :class="statusTab === 'archived' ? 'text-green-800 border-b-[3px] border-green-800 font-medium' : 'text-gray-700'"
                                class="pb-3 whitespace-nowrap">Archived</button>
                    </div>
                </div>

                <div class="bg-gray-50 min-h-[620px]">

                    <div class="px-4 pt-4">
                        @if(session('corporate_formation_success'))
                            <div class="mb-3 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                                {{ session('corporate_formation_success') }}
                            </div>
                        @endif

                        <div class="border border-green-200 bg-green-50 text-green-800 text-[14px] px-4 py-3 rounded-md"
                             x-show="statusTab === null || statusTab === 'accepted'">
                            These records were already accepted and approved.
                        </div>
                        <div class="border border-green-200 bg-green-50 text-green-800 text-[14px] px-4 py-3 rounded-md"
                             x-show="statusTab === 'uploaded'">
                            These records are uploaded drafts and not yet submitted for approval.
                        </div>
                        <div class="border border-blue-200 bg-blue-50 text-blue-800 text-[14px] px-4 py-3 rounded-md"
                             x-show="statusTab === 'submitted'">
                            These records have already been submitted and are waiting for review.
                        </div>
                        <div class="border border-yellow-200 bg-yellow-50 text-yellow-800 text-[14px] px-4 py-3 rounded-md"
                             x-show="statusTab === 'reverted'">
                            These records were reverted and need correction before resubmission.
                        </div>
                        <div class="border border-gray-200 bg-gray-50 text-gray-700 text-[14px] px-4 py-3 rounded-md"
                             x-show="statusTab === 'archived'">
                            These records are archived for reference.
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="overflow-x-auto border border-gray-200 rounded-md bg-white">

                            {{-- ================================================================ --}}
                            {{-- SEC-COI TABLE --}}
                            {{-- ================================================================ --}}
                            @if($activeTab === 'sec-coi')
                            <table class="min-w-full text-[11px] text-left text-gray-700">
                                <thead class="bg-white border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold">Date Upload</th>
                                        <th class="px-3 py-2 font-semibold">Date Created</th>
                                        <th class="px-3 py-2 font-semibold">Company Reg No.</th>
                                        <th class="px-3 py-2 font-semibold">Corporation Name</th>
                                        <th class="px-3 py-2 font-semibold">Issued On</th>
                                        <th class="px-3 py-2 font-semibold">Issued By</th>
                                        <th class="px-3 py-2 font-semibold">Workflow Status</th>
                                        <th class="px-3 py-2 font-semibold">Files</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $row)
                                        @php
                                            $workflow = $row->workflow_status ?: ($row->approval_status === 'Approved' ? 'Accepted' : (in_array($row->approval_status, ['Needs Revision','Rejected']) ? 'Reverted' : 'Uploaded'));
                                            $hasDraft  = !empty($row->file_path);
                                            $hasNotary = !empty($row->notary_file_path);
                                            $canSubmit = $hasDraft && $hasNotary;
                                            $fileLabel = match(true) { $hasDraft && $hasNotary => 'Draft + Notary', $hasDraft => 'Draft Only', $hasNotary => 'Notary Only', default => 'No File' };
                                            $badgeClass = match($workflow) { 'Accepted' => 'bg-green-50 text-green-700', 'Reverted' => 'bg-yellow-50 text-yellow-700', 'Archived' => 'bg-gray-100 text-gray-700', 'Submitted' => 'bg-blue-50 text-blue-700', default => 'bg-orange-50 text-orange-700' };
                                        @endphp
                                        <tr x-show="(statusTab===null&&{{ $workflow==='Accepted'?'true':'false' }})||(statusTab==='uploaded'&&{{ $workflow==='Uploaded'?'true':'false' }})||(statusTab==='submitted'&&{{ $workflow==='Submitted'?'true':'false' }})||(statusTab==='accepted'&&{{ $workflow==='Accepted'?'true':'false' }})||(statusTab==='reverted'&&{{ $workflow==='Reverted'?'true':'false' }})||(statusTab==='archived'&&{{ $workflow==='Archived'?'true':'false' }})"
                                            data-url="{{ route('company.corporate-formation.sec-coi.show', [$company->id, $row->id]) }}"
                                            onclick="window.location.href=this.dataset.url"
                                            class="border-b border-gray-200 hover:bg-blue-50 transition cursor-pointer">
                                            <td class="px-3 py-2">{{ $row->date_upload }}</td>
                                            <td class="px-3 py-2">{{ $row->created_at?->format('M d, Y') }}</td>
                                            <td class="px-3 py-2">{{ $row->company_reg_no }}</td>
                                            <td class="px-3 py-2 font-semibold text-gray-800">{{ $row->corporate_name }}</td>
                                            <td class="px-3 py-2">{{ $row->issued_on }}</td>
                                            <td class="px-3 py-2">{{ $row->issued_by }}</td>
                                            <td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-[10px] font-medium {{ $badgeClass }}">{{ $workflow }}</span></td>
                                            <td class="px-3 py-2 text-blue-600 font-medium">
                                                <div class="flex flex-col items-start gap-2">
                                                    <span>{{ $fileLabel }}</span>
                                                    @if(in_array($workflow, ['Uploaded','Reverted']))
                                                        @if($canSubmit)
                                                            <form action="{{ route('company.corporate-formation.sec-coi.submit', [$company->id, $row->id]) }}" method="POST" onclick="event.stopPropagation();">@csrf
                                                                <button type="submit" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">Submit</button>
                                                            </form>
                                                        @else
                                                            <button type="button" onclick="event.stopPropagation();" disabled title="Both Draft and Notary files are required before submitting" class="px-3 py-1.5 text-xs rounded-md bg-gray-200 text-gray-500 cursor-not-allowed">Incomplete</button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if($records->isEmpty())
                                        <tr><td colspan="8" class="px-3 py-6 text-center text-gray-400">No SEC-COI records found for {{ $company->company_name }}.</td></tr>
                                    @endif
                                </tbody>
                            </table>

                            {{-- ================================================================ --}}
                            {{-- SEC-AOI TABLE --}}
                            {{-- ================================================================ --}}
                            @elseif($activeTab === 'sec-aoi')
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
                                            $workflow = $row->workflow_status ?: ($row->approval_status === 'Approved' ? 'Accepted' : (in_array($row->approval_status, ['Needs Revision','Rejected']) ? 'Reverted' : 'Uploaded'));
                                            $hasDraft  = !empty($row->file_path);
                                            $hasNotary = !empty($row->notary_file_path);
                                            $canSubmit = $hasDraft && $hasNotary;
                                            $fileLabel = match(true) { $hasDraft && $hasNotary => 'Draft + Notary', $hasDraft => 'Draft Only', $hasNotary => 'Notary Only', default => 'No File' };
                                            $badgeClass = match($workflow) { 'Accepted' => 'bg-green-50 text-green-700', 'Reverted' => 'bg-yellow-50 text-yellow-700', 'Archived' => 'bg-gray-100 text-gray-700', 'Submitted' => 'bg-blue-50 text-blue-700', default => 'bg-orange-50 text-orange-700' };
                                        @endphp
                                        <tr x-show="(statusTab===null&&{{ $workflow==='Accepted'?'true':'false' }})||(statusTab==='uploaded'&&{{ $workflow==='Uploaded'?'true':'false' }})||(statusTab==='submitted'&&{{ $workflow==='Submitted'?'true':'false' }})||(statusTab==='accepted'&&{{ $workflow==='Accepted'?'true':'false' }})||(statusTab==='reverted'&&{{ $workflow==='Reverted'?'true':'false' }})||(statusTab==='archived'&&{{ $workflow==='Archived'?'true':'false' }})"
                                            data-url="{{ route('company.corporate-formation.sec-aoi.show', [$company->id, $row->id]) }}"
                                            onclick="window.location.href=this.dataset.url"
                                            class="border-b border-gray-200 hover:bg-blue-50 transition cursor-pointer">
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
                                            <td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-[10px] font-medium {{ $badgeClass }}">{{ $workflow }}</span></td>
                                            <td class="px-3 py-2 text-blue-600 font-medium">
                                                <div class="flex flex-col items-start gap-2">
                                                    <span>{{ $fileLabel }}</span>
                                                    @if(in_array($workflow, ['Uploaded','Reverted']))
                                                        @if($canSubmit)
                                                            <form action="{{ route('company.corporate-formation.sec-aoi.submit', [$company->id, $row->id]) }}" method="POST" onclick="event.stopPropagation();">@csrf
                                                                <button type="submit" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">Submit</button>
                                                            </form>
                                                        @else
                                                            <button type="button" onclick="event.stopPropagation();" disabled title="Both Draft and Notary files are required before submitting" class="px-3 py-1.5 text-xs rounded-md bg-gray-200 text-gray-500 cursor-not-allowed">Incomplete</button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if($records->isEmpty())
                                        <tr><td colspan="13" class="px-3 py-6 text-center text-gray-400">No SEC-AOI records found for {{ $company->company_name }}.</td></tr>
                                    @endif
                                </tbody>
                            </table>

                            {{-- ================================================================ --}}
                            {{-- BYLAWS TABLE --}}
                            {{-- ================================================================ --}}
                            @elseif($activeTab === 'bylaws')
                            <table class="min-w-full text-[10px] text-left text-gray-700">
                                <thead class="bg-white border-b border-gray-200 align-top">
                                    <tr>
                                        <th class="px-2 py-2 font-semibold">Date Upload</th>
                                        <th class="px-2 py-2 font-semibold">Uploaded By</th>
                                        <th class="px-2 py-2 font-semibold">Company Reg No.</th>
                                        <th class="px-2 py-2 font-semibold">Corporation Name</th>
                                        <th class="px-2 py-2 font-semibold">Type of Formation</th>
                                        <th class="px-2 py-2 font-semibold">SEC-AOI Version</th>
                                        <th class="px-2 py-2 font-semibold">Type of Version</th>
                                        <th class="px-2 py-2 font-semibold">Date of Version</th>
                                        <th class="px-2 py-2 font-semibold">Regular ASM</th>
                                        <th class="px-2 py-2 font-semibold">Notice Time</th>
                                        <th class="px-2 py-2 font-semibold">Regular BODM</th>
                                        <th class="px-2 py-2 font-semibold">Notice Time</th>
                                        <th class="px-2 py-2 font-semibold">Workflow Status</th>
                                        <th class="px-2 py-2 font-semibold">Files</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $row)
                                        @php
                                            $workflow = $row->workflow_status ?: ($row->approval_status === 'Approved' ? 'Accepted' : (in_array($row->approval_status, ['Needs Revision','Rejected']) ? 'Reverted' : 'Uploaded'));
                                            $hasDraft  = !empty($row->file_path);
                                            $hasNotary = !empty($row->notary_file_path);
                                            $canSubmit = $hasDraft && $hasNotary;
                                            $fileLabel = match(true) { $hasDraft && $hasNotary => 'Draft + Notary', $hasDraft => 'Draft Only', $hasNotary => 'Notary Only', default => 'No File' };
                                            $badgeClass = match($workflow) { 'Accepted' => 'bg-green-50 text-green-700', 'Reverted' => 'bg-yellow-50 text-yellow-700', 'Archived' => 'bg-gray-100 text-gray-700', 'Submitted' => 'bg-blue-50 text-blue-700', default => 'bg-orange-50 text-orange-700' };
                                        @endphp
                                        <tr x-show="(statusTab===null&&{{ $workflow==='Accepted'?'true':'false' }})||(statusTab==='uploaded'&&{{ $workflow==='Uploaded'?'true':'false' }})||(statusTab==='submitted'&&{{ $workflow==='Submitted'?'true':'false' }})||(statusTab==='accepted'&&{{ $workflow==='Accepted'?'true':'false' }})||(statusTab==='reverted'&&{{ $workflow==='Reverted'?'true':'false' }})||(statusTab==='archived'&&{{ $workflow==='Archived'?'true':'false' }})"
                                            data-url="{{ route('company.corporate-formation.bylaws.show', [$company->id, $row->id]) }}"
                                            onclick="window.location.href=this.dataset.url"
                                            class="border-b border-gray-200 hover:bg-blue-50 transition cursor-pointer">
                                            <td class="px-2 py-2">{{ $row->date_upload }}</td>
                                            <td class="px-2 py-2 font-semibold text-gray-800">{{ $row->uploaded_by }}</td>
                                            <td class="px-2 py-2">{{ $row->company_reg_no }}</td>
                                            <td class="px-2 py-2">{{ $row->corporation_name }}</td>
                                            <td class="px-2 py-2">{{ $row->type_of_formation }}</td>
                                            <td class="px-2 py-2">{{ $row->aoi_version }}</td>
                                            <td class="px-2 py-2">{{ $row->aoi_type }}</td>
                                            <td class="px-2 py-2">{{ $row->aoi_date }}</td>
                                            <td class="px-2 py-2">{{ $row->regular_asm }}</td>
                                            <td class="px-2 py-2">{{ $row->asm_notice }}</td>
                                            <td class="px-2 py-2">{{ $row->regular_bodm }}</td>
                                            <td class="px-2 py-2">{{ $row->bodm_notice }}</td>
                                            <td class="px-2 py-2"><span class="px-2 py-1 rounded-full text-[10px] font-medium {{ $badgeClass }}">{{ $workflow }}</span></td>
                                            <td class="px-2 py-2 text-blue-600 font-medium">
                                                <div class="flex flex-col items-start gap-2">
                                                    <span>{{ $fileLabel }}</span>
                                                    @if(in_array($workflow, ['Uploaded','Reverted']))
                                                        @if($canSubmit)
                                                            <form action="{{ route('company.corporate-formation.bylaws.submit', [$company->id, $row->id]) }}" method="POST" onclick="event.stopPropagation();">@csrf
                                                                <button type="submit" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">Submit</button>
                                                            </form>
                                                        @else
                                                            <button type="button" onclick="event.stopPropagation();" disabled title="Both Draft and Notary files are required before submitting" class="px-3 py-1.5 text-xs rounded-md bg-gray-200 text-gray-500 cursor-not-allowed">Incomplete</button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if($records->isEmpty())
                                        <tr><td colspan="14" class="px-3 py-6 text-center text-gray-400">No Bylaws records found for {{ $company->company_name }}.</td></tr>
                                    @endif
                                </tbody>
                            </table>

                            {{-- ================================================================ --}}
                            {{-- GIS TABLE --}}
                            {{-- ================================================================ --}}
                            @else
                            <table class="min-w-full text-[11px] text-left text-gray-700">
                                <thead class="bg-white border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold">Date Upload</th>
                                        <th class="px-3 py-2 font-semibold">Uploaded By</th>
                                        <th class="px-3 py-2 font-semibold">Sec-Submission Status</th>
                                        <th class="px-3 py-2 font-semibold">Sec-Receive on</th>
                                        <th class="px-3 py-2 font-semibold">Sec-Period Date</th>
                                        <th class="px-3 py-2 font-semibold">Company Reg No.</th>
                                        <th class="px-3 py-2 font-semibold">Corporation Name</th>
                                        <th class="px-3 py-2 font-semibold">Date of Annual Meeting</th>
                                        <th class="px-3 py-2 font-semibold">Type of Meeting</th>
                                        <th class="px-3 py-2 font-semibold">Workflow Status</th>
                                        <th class="px-3 py-2 font-semibold">Files</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $row)
                                        @php
                                            $workflow = $row->workflow_status ?: ($row->approval_status === 'Approved' ? 'Accepted' : (in_array($row->approval_status, ['Needs Revision','Rejected']) ? 'Reverted' : 'Uploaded'));
                                            $hasDraft  = !empty($row->file);
                                            $hasNotary = !empty($row->notary_file_path);
                                            $canSubmit = $hasDraft && $hasNotary;
                                            $fileLabel = match(true) { $hasDraft && $hasNotary => 'Draft + Notary', $hasDraft => 'Draft Only', $hasNotary => 'Notary Only', default => 'No File' };
                                            $badgeClass = match($workflow) { 'Accepted' => 'bg-green-50 text-green-700', 'Reverted' => 'bg-yellow-50 text-yellow-700', 'Archived' => 'bg-gray-100 text-gray-700', 'Submitted' => 'bg-blue-50 text-blue-700', default => 'bg-orange-50 text-orange-700' };
                                        @endphp
                                        <tr x-show="(statusTab===null&&{{ $workflow==='Accepted'?'true':'false' }})||(statusTab==='uploaded'&&{{ $workflow==='Uploaded'?'true':'false' }})||(statusTab==='submitted'&&{{ $workflow==='Submitted'?'true':'false' }})||(statusTab==='accepted'&&{{ $workflow==='Accepted'?'true':'false' }})||(statusTab==='reverted'&&{{ $workflow==='Reverted'?'true':'false' }})||(statusTab==='archived'&&{{ $workflow==='Archived'?'true':'false' }})"
                                            data-url="{{ route('company.corporate-formation.gis.show', [$company->id, $row->id]) }}"
                                            onclick="window.location.href=this.dataset.url"
                                            class="border-b border-gray-200 hover:bg-blue-50 transition cursor-pointer">
                                            <td class="px-3 py-2">{{ $row->created_at?->format('M d, Y') }}</td>
                                            <td class="px-3 py-2 font-semibold text-gray-800">{{ $row->uploaded_by }}</td>
                                            <td class="px-3 py-2">{{ $row->submission_status }}</td>
                                            <td class="px-3 py-2">{{ $row->receive_on }}</td>
                                            <td class="px-3 py-2">{{ $row->period_date }}</td>
                                            <td class="px-3 py-2">{{ $row->company_reg_no }}</td>
                                            <td class="px-3 py-2">{{ $row->corporation_name }}</td>
                                            <td class="px-3 py-2">{{ $row->annual_meeting }}</td>
                                            <td class="px-3 py-2">{{ $row->meeting_type }}</td>
                                            <td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-[10px] font-medium {{ $badgeClass }}">{{ $workflow }}</span></td>
                                            <td class="px-3 py-2 text-blue-600 font-medium">
                                                <div class="flex flex-col items-start gap-2">
                                                    <span>{{ $fileLabel }}</span>
                                                    @if(in_array($workflow, ['Uploaded','Reverted']))
                                                        @if($canSubmit)
                                                            <form action="{{ route('company.corporate-formation.gis.submit', [$company->id, $row->id]) }}" method="POST" onclick="event.stopPropagation();">@csrf
                                                                <button type="submit" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">Submit</button>
                                                            </form>
                                                        @else
                                                            <button type="button" onclick="event.stopPropagation();" disabled title="Both Draft and Notary files are required before submitting" class="px-3 py-1.5 text-xs rounded-md bg-gray-200 text-gray-500 cursor-not-allowed">Incomplete</button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if($records->isEmpty())
                                        <tr><td colspan="11" class="px-3 py-6 text-center text-gray-400">No GIS records found for {{ $company->company_name }}.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Overlay --}}
    <div x-show="openPanel"
         x-transition.opacity
         class="fixed inset-0 z-[70] bg-black/35"
         style="display:none;"
         @click="openPanel = false">
    </div>

    {{-- Side panel drawer --}}
    <div x-show="openPanel"
         x-transition:enter="transform transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 bottom-0 z-[80] w-[430px] bg-white border-l border-gray-300 shadow-2xl"
         style="display:none;">

        {{-- ============================================================ --}}
        {{-- SEC-COI FORM --}}
        {{-- ============================================================ --}}
        @if($activeTab === 'sec-coi')
        <form action="{{ route('company.corporate-formation.sec-coi.store', $company->id) }}" method="POST" enctype="multipart/form-data" class="h-full flex flex-col">
            @csrf
            <div class="h-16 px-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-[26px] font-semibold text-gray-900 leading-none">Add SEC-COI Record</h2>
                    <p class="mt-1 text-xs text-gray-500">Scoped to {{ $company->company_name }}.</p>
                    <p class="mt-2 rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-[11px] leading-relaxed text-blue-700">
                        Auto-filled from this company’s General Information and latest Corporate Formation records. You can still edit the fields before saving.
                    </p>
                </div>
                <button type="button" @click="openPanel = false" class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-500 flex items-center justify-center">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Corporate Name <span class="text-red-500">*</span></label>
                    <input type="text" name="corporate_name" value="{{ $defaultCorporateName }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Company Reg No. <span class="text-red-500">*</span></label>
                    <input type="text" name="company_reg_no" value="{{ $defaultCompanyRegNo }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Issued By</label>
                    <input type="text" value="{{ auth()->user()->name ?? auth()->user()->full_name ?? auth()->user()->employee_name ?? auth()->user()->username ?? auth()->user()->email }}"
                           class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-gray-50" readonly>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Issued On <span class="text-red-500">*</span></label>
                        <input type="date" name="issued_on" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Date Upload <span class="text-red-500">*</span></label>
                        <input type="date" name="date_upload" value="{{ old('date_upload', now()->toDateString()) }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
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
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="openPanel = false" class="min-w-[92px] px-6 py-2.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="min-w-[92px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">Save</button>
            </div>
        </form>

        {{-- ============================================================ --}}
        {{-- SEC-AOI FORM --}}
        {{-- ============================================================ --}}
        @elseif($activeTab === 'sec-aoi')
        <form action="{{ route('company.corporate-formation.sec-aoi.store', $company->id) }}" method="POST" enctype="multipart/form-data" class="h-full flex flex-col">
            @csrf
            <div class="h-16 px-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-[26px] font-semibold text-gray-900 leading-none">Add SEC-AOI Record</h2>
                    <p class="mt-1 text-xs text-gray-500">Scoped to {{ $company->company_name }}.</p>
                    <p class="mt-2 rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-[11px] leading-relaxed text-blue-700">
                        Auto-filled from this company’s General Information and latest Corporate Formation records. You can still edit the fields before saving.
                    </p>
                </div>
                <button type="button" @click="openPanel = false" class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-500 flex items-center justify-center">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Corporation Name <span class="text-red-500">*</span></label>
                    <input type="text" name="corporation_name" value="{{ $defaultCompanyName }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Company Reg No. <span class="text-red-500">*</span></label>
                    <input type="text" name="company_reg_no" value="{{ $defaultCompanyRegNo }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Principal Address</label>
                    <input type="text" name="principal_address" value="{{ $defaultPrincipalAddress }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Par Value</label>
                        <input type="text" name="par_value" value="{{ $defaultParValue }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">No. of Directors</label>
                        <input type="number" name="directors" value="{{ $defaultDirectors }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Authorized Capital Stock</label>
                    <input type="text" name="authorized_capital_stock" value="{{ $defaultAuthorizedCapitalStock }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Type of Formation</label>
                        <select name="type_of_formation" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-white">
                            <option value="Stock Corporation" @selected($defaultTypeOfFormation === 'Stock Corporation')>Stock Corporation</option>
                            <option value="Non-Stock Corporation" @selected($defaultTypeOfFormation === 'Non-Stock Corporation')>Non-Stock Corporation</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">SEC-AOI Version</label>
                        <input type="text" name="aoi_version" value="{{ $defaultAoiVersion }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Type of SEC-AOI Version</label>
                    <select name="aoi_type" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-white">
                        <option value="Original" @selected($defaultAoiType === 'Original')>Original</option>
                        <option value="Amended" @selected($defaultAoiType === 'Amended')>Amended</option>
                        <option value="Revised" @selected($defaultAoiType === 'Revised')>Revised</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Uploaded By</label>
                        <input type="text" value="{{ auth()->user()->name ?? auth()->user()->full_name ?? auth()->user()->employee_name ?? auth()->user()->username ?? auth()->user()->email }}"
                               class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Date Upload <span class="text-red-500">*</span></label>
                        <input type="date" name="date_upload" value="{{ old('date_upload', now()->toDateString()) }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
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
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="openPanel = false" class="min-w-[92px] px-6 py-2.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="min-w-[92px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">Save</button>
            </div>
        </form>

        {{-- ============================================================ --}}
        {{-- BYLAWS FORM --}}
        {{-- ============================================================ --}}
        @elseif($activeTab === 'bylaws')
        <form action="{{ route('company.corporate-formation.bylaws.store', $company->id) }}" method="POST" enctype="multipart/form-data" class="h-full flex flex-col">
            @csrf
            <div class="h-16 px-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-[26px] font-semibold text-gray-900 leading-none">Add Bylaws Record</h2>
                    <p class="mt-1 text-xs text-gray-500">Scoped to {{ $company->company_name }}.</p>
                    <p class="mt-2 rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-[11px] leading-relaxed text-blue-700">
                        Auto-filled from this company’s General Information and latest Corporate Formation records. You can still edit the fields before saving.
                    </p>
                </div>
                <button type="button" @click="openPanel = false" class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-500 flex items-center justify-center">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Corporation Name <span class="text-red-500">*</span></label>
                    <input type="text" name="corporation_name" value="{{ $defaultCompanyName }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Company Reg No. <span class="text-red-500">*</span></label>
                    <input type="text" name="company_reg_no" value="{{ $defaultCompanyRegNo }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Type of Formation</label>
                    <input type="text" name="type_of_formation" value="{{ $defaultTypeOfFormation }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">SEC-AOI Version</label>
                    <input type="text" name="aoi_version" value="{{ $defaultAoiVersion }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Type of Version</label>
                    <input type="text" name="aoi_type" value="{{ $defaultAoiType }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Date of Version</label>
                    <input type="date" name="aoi_date" value="{{ $defaultAoiDate }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Regular ASM</label>
                    <input type="text" name="regular_asm" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">ASM Notice Time</label>
                    <input type="text" name="asm_notice" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Regular BODM</label>
                    <input type="text" name="regular_bodm" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">BODM Notice Time</label>
                    <input type="text" name="bodm_notice" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Uploaded By</label>
                    <input type="text" value="{{ auth()->user()->name ?? auth()->user()->full_name ?? auth()->user()->employee_name ?? auth()->user()->username ?? auth()->user()->email }}"
                           class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-gray-50" readonly>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Date Upload <span class="text-red-500">*</span></label>
                    <input type="date" name="date_upload" value="{{ old('date_upload', now()->toDateString()) }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
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
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="openPanel = false" class="min-w-[92px] px-6 py-2.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="min-w-[92px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">Save</button>
            </div>
        </form>

        {{-- ============================================================ --}}
        {{-- GIS FORM --}}
        {{-- ============================================================ --}}
        @else
        <form action="{{ route('company.corporate-formation.gis.store', $company->id) }}" method="POST" enctype="multipart/form-data" class="h-full flex flex-col">
            @csrf
            <div class="h-16 px-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-[26px] font-semibold text-gray-900 leading-none">Add GIS Record</h2>
                    <p class="mt-1 text-xs text-gray-500">Scoped to {{ $company->company_name }}.</p>
                    <p class="mt-2 rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-[11px] leading-relaxed text-blue-700">
                        Auto-filled from this company’s General Information and latest Corporate Formation records. You can still edit the fields before saving.
                    </p>
                </div>
                <button type="button" @click="openPanel = false" class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-500 flex items-center justify-center">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Uploaded By</label>
                    <input type="text" value="{{ auth()->user()->name ?? auth()->user()->full_name ?? auth()->user()->employee_name ?? auth()->user()->username ?? auth()->user()->email }}"
                           class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-gray-50" readonly>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Submission Status</label>
                    <select name="submission_status" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-white">
                        <option>Submitted</option>
                        <option>Received</option>
                        <option>Pending</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Receive On</label>
                    <input type="date" name="receive_on" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Period Date</label>
                    <input type="text" name="period_date" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Company Reg No. <span class="text-red-500">*</span></label>
                    <input type="text" name="company_reg_no" value="{{ $defaultCompanyRegNo }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Corporation Name <span class="text-red-500">*</span></label>
                    <input type="text" name="corporation_name" value="{{ $defaultCompanyName }}" required class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>

                <div class="pt-3 border-t border-gray-100">
                    <h3 class="text-xs font-bold tracking-[0.18em] text-blue-700 uppercase">Company General Information</h3>
                    <p class="mt-1 text-[11px] text-gray-500">These fields will appear in the Company General Information page for this specific company.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Date Registered</label>
                        <input type="date" name="date_registered" value="{{ $defaultDateRegistered }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Fiscal Year End</label>
                        <input type="text" name="fiscal_year_end" value="{{ $defaultFiscalYearEnd }}" placeholder="e.g. December 31" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Business / Trade Name</label>
                    <input type="text" name="trade_name" value="{{ $defaultTradeName }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">TIN</label>
                        <input type="text" name="tin" value="{{ $defaultTin }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Website / URL</label>
                        <input type="text" name="website" value="{{ $defaultWebsite }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ $defaultEmail }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Principal Office Address</label>
                    <textarea name="principal_address" rows="2" class="w-full border border-gray-300 rounded-md px-4 py-3 text-sm">{{ $defaultPrincipalAddress }}</textarea>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Business Address</label>
                    <textarea name="business_address" rows="2" class="w-full border border-gray-300 rounded-md px-4 py-3 text-sm">{{ $defaultBusinessAddress }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Official Mobile Number</label>
                        <input type="text" name="official_mobile" value="{{ $defaultOfficialMobile }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Alternate Mobile Number</label>
                        <input type="text" name="alternate_mobile" value="{{ $defaultAlternateMobile }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">External Auditor / Signing Partner</label>
                    <input type="text" name="auditor" value="{{ $defaultAuditor }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Industry Classification</label>
                        <input type="text" name="industry" value="{{ $defaultIndustry }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Geographical Code</label>
                        <input type="text" name="geo_code" value="{{ $defaultGeoCode }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                </div>

                <div class="pt-3 border-t border-gray-100">
                    <h3 class="text-xs font-bold tracking-[0.18em] text-blue-700 uppercase">Intercompany Affiliations</h3>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Parent Company</label>
                        <input type="text" name="parent_company_name" value="{{ old('parent_company_name', $formationDefaults['parent_company_name'] ?? '') }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Parent SEC No.</label>
                        <input type="text" name="parent_company_sec_no" value="{{ old('parent_company_sec_no', $formationDefaults['parent_company_sec_no'] ?? '') }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Parent Address</label>
                        <input type="text" name="parent_company_address" value="{{ old('parent_company_address', $formationDefaults['parent_company_address'] ?? '') }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Subsidiary Name</label>
                        <input type="text" name="subsidiary_name" value="{{ old('subsidiary_name', $formationDefaults['subsidiary_name'] ?? '') }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Subsidiary SEC No.</label>
                        <input type="text" name="subsidiary_sec_no" value="{{ old('subsidiary_sec_no', $formationDefaults['subsidiary_sec_no'] ?? '') }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-2">Subsidiary Address</label>
                        <input type="text" name="subsidiary_address" value="{{ old('subsidiary_address', $formationDefaults['subsidiary_address'] ?? '') }}" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Date of Annual Meeting</label>
                    <input type="date" name="annual_meeting" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-2">Type of Meeting</label>
                    <select name="meeting_type" class="w-full h-11 border border-gray-300 rounded-md px-4 text-sm bg-white">
                        <option>Regular Annual Meeting</option>
                        <option>Special Meeting</option>
                    </select>
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
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="openPanel = false" class="min-w-[92px] px-6 py-2.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="min-w-[92px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">Save</button>
            </div>
        </form>
        @endif

    </div>
</div>
@endsection
