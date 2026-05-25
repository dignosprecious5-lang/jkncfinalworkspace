@extends('layouts.app')
@section('title', 'Company General Information')

@section('content')
@php
    $activeTab = $activeTab ?? 'company-info';
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

    $displayDate = function ($value) {
        if (blank($value)) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('F d, Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $year = $gis?->period_date ?: now()->year;
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-white">
                    <a href="{{ route('company.corporate-formation', $company->id) }}"
                       class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>

                    <div class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap no-scrollbar">
                        <div class="flex items-stretch min-w-max">
                            @foreach ($tabConfig as $tabItem)
                                <a href="{{ $tabItem['href'] }}"
                                   class="shrink-0 w-[180px] px-4 py-3 text-sm font-medium text-center border-t border-b border-r border-gray-200 first:border-l {{ $activeTab === $tabItem['key'] ? 'bg-blue-50 text-blue-700 border-blue-500' : 'bg-white text-gray-800 hover:bg-gray-50' }}">
                                    <span class="block truncate">{{ $tabItem['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <a href="{{ route('company.corporate-formation.gis', $company->id) }}"
                       class="px-4 h-9 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-file-alt text-xs"></i>
                        Open GIS Records
                    </a>
                </div>

                <div class="px-4 py-4 bg-white border-b border-gray-100">
                    @if($sourceGis)
                        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            This company general information is generated from the latest approved/accepted GIS record of {{ $company->company_name }}.
                        </div>
                    @else
                        <div class="rounded-md border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                            No approved GIS record is available for this company yet. Company General Information will remain blank until a GIS is approved/accepted for this company.
                        </div>
                    @endif
                </div>

                <div class="bg-gray-50 min-h-[620px] p-6">
                    <div class="h-[760px] overflow-auto bg-gray-50 p-6 border border-gray-200 rounded-lg">
                        <div class="mx-auto max-w-[980px] bg-white border border-gray-300">

                            <div class="px-6 pt-6 text-center">
                                <div class="text-[13px] font-bold tracking-wide text-gray-900">GENERAL INFORMATION</div>
                                <div class="text-[11px] font-semibold text-gray-900 mt-1">
                                    FOR THE YEAR <span class="px-2 border-b border-gray-400">{{ $year }}</span>
                                </div>
                                <div class="text-[11px] font-semibold text-gray-900 mt-1">STOCK CORPORATION</div>
                            </div>

                            <div class="px-6 pb-6 pt-4">
                                <style>
                                    .gis-cell { border: 1px solid #6b7280; }
                                    .gis-label { font-size: 10px; font-weight: 700; letter-spacing: .02em; }
                                    .gis-value {
                                        width: 100%;
                                        min-height: 32px;
                                        padding: 6px 8px;
                                        font-size: 11px;
                                        line-height: 1.1rem;
                                        background: transparent;
                                        color: #111827;
                                    }
                                </style>

                                <div class="grid grid-cols-12 gap-0">
                                    <div class="col-span-8 gis-cell">
                                        <div class="px-2 pt-1 gis-label">CORPORATE NAME:</div>
                                        <div class="gis-value">{{ $gis->corporation_name ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label">DATE REGISTERED:</div>
                                        <div class="gis-value">{{ $displayDate($gis->date_registered ?? null) }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-8 gis-cell">
                                        <div class="px-2 pt-1 gis-label">BUSINESS/TRADE NAME:</div>
                                        <div class="gis-value">{{ $gis->trade_name ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label">FISCAL YEAR END:</div>
                                        <div class="gis-value">{{ $gis->fiscal_year_end ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-8 gis-cell">
                                        <div class="px-2 pt-1 gis-label">SEC REGISTRATION NUMBER:</div>
                                        <div class="gis-value">{{ $gis->company_reg_no ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label">CORPORATE TAX IDENTIFICATION NUMBER (TIN):</div>
                                        <div class="gis-value">{{ $gis->tin ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-8 gis-cell">
                                        <div class="px-2 pt-1 gis-label">DATE OF ANNUAL MEETING PER BY-LAWS:</div>
                                        <div class="gis-value">{{ $gis->meeting_type ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label">WEBSITE/URL ADDRESS:</div>
                                        <div class="gis-value">{{ $gis->website ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-8 gis-cell">
                                        <div class="px-2 pt-1 gis-label">ACTUAL DATE OF ANNUAL MEETING:</div>
                                        <div class="gis-value">{{ $displayDate($gis->annual_meeting ?? null) }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label">E-MAIL ADDRESS:</div>
                                        <div class="gis-value">{{ $gis->email ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-8 gis-cell">
                                        <div class="px-2 pt-1 gis-label">COMPLETE PRINCIPAL OFFICE ADDRESS:</div>
                                        <div class="gis-value">{{ $gis->principal_address ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label">FAX NUMBER:</div>
                                        <div class="gis-value">N/A</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-12 gis-cell">
                                        <div class="px-2 pt-1 gis-label">COMPLETE BUSINESS ADDRESS:</div>
                                        <div class="gis-value">{{ $gis->business_address ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-3 gis-cell">
                                        <div class="px-2 pt-1 gis-label">OFFICIAL E-MAIL ADDRESS</div>
                                        <div class="gis-value">{{ $gis->email ?? '' }}</div>
                                    </div>
                                    <div class="col-span-3 gis-cell">
                                        <div class="px-2 pt-1 gis-label">ALTERNATE E-MAIL ADDRESS</div>
                                        <div class="gis-value">N/A</div>
                                    </div>
                                    <div class="col-span-3 gis-cell">
                                        <div class="px-2 pt-1 gis-label">OFFICIAL MOBILE NUMBER</div>
                                        <div class="gis-value">{{ $gis->official_mobile ?? '' }}</div>
                                    </div>
                                    <div class="col-span-3 gis-cell">
                                        <div class="px-2 pt-1 gis-label">ALTERNATE MOBILE NUMBER</div>
                                        <div class="gis-value">{{ $gis->alternate_mobile ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-6 gis-cell">
                                        <div class="px-2 pt-1 gis-label">NAME OF EXTERNAL AUDITOR & ITS SIGNING PARTNER:</div>
                                        <div class="gis-value">{{ $gis->auditor ?? '' }}</div>
                                    </div>
                                    <div class="col-span-3 gis-cell">
                                        <div class="px-2 pt-1 gis-label">SEC ACCREDITATION NUMBER (if applicable):</div>
                                        <div class="gis-value">N/A</div>
                                    </div>
                                    <div class="col-span-3 gis-cell">
                                        <div class="px-2 pt-1 gis-label">TELEPHONE NUMBER(S):</div>
                                        <div class="gis-value">N/A</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-6 gis-cell min-h-[100px]">
                                        <div class="px-2 pt-1 gis-label">PRIMARY PURPOSE/ACTIVITY/INDUSTRY PRESENTLY ENGAGED IN:</div>
                                        <div class="gis-value">{{ $gis->industry ?? '' }}</div>
                                    </div>
                                    <div class="col-span-3 gis-cell min-h-[100px]">
                                        <div class="px-2 pt-1 gis-label">INDUSTRY CLASSIFICATION:</div>
                                        <div class="gis-value">{{ $gis->industry ?? '' }}</div>
                                    </div>
                                    <div class="col-span-3 gis-cell min-h-[100px]">
                                        <div class="px-2 pt-1 gis-label">GEOGRAPHICAL CODE:</div>
                                        <div class="gis-value">{{ $gis->geo_code ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-12 gis-cell text-center py-2 text-[11px] font-bold">
                                        INTERCOMPANY AFFILIATIONS
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label text-center">PARENT COMPANY</div>
                                        <div class="gis-value text-center">{{ $gis->parent_company_name ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label text-center">SEC REGISTRATION NO.</div>
                                        <div class="gis-value text-center">{{ $gis->parent_company_sec_no ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label text-center">ADDRESS</div>
                                        <div class="gis-value text-center">{{ $gis->parent_company_address ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-0 -mt-px">
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label text-center">SUBSIDIARY / AFFILIATE</div>
                                        <div class="gis-value text-center">{{ $gis->subsidiary_name ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label text-center">SEC REGISTRATION NO.</div>
                                        <div class="gis-value text-center">{{ $gis->subsidiary_sec_no ?? '' }}</div>
                                    </div>
                                    <div class="col-span-4 gis-cell">
                                        <div class="px-2 pt-1 gis-label text-center">ADDRESS</div>
                                        <div class="gis-value text-center">{{ $gis->subsidiary_address ?? '' }}</div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-gray-500">
                        This page is read-only and generated from the company’s GIS record. To change the values, update the GIS record under this same company.
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
