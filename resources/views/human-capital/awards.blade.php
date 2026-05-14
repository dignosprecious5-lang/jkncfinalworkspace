@extends('layouts.app')

@section('content')
@php
    $awardPayload = $awards->map(fn ($award) => [
        'id' => $award->id,
        'employee_name' => $award->employee?->full_name ?? 'Employee',
        'employee_code' => $award->employee?->employee_code,
        'position' => $award->employee?->position,
        'training_title' => $award->training?->title ?? 'Training Program',
        'provider' => $award->training?->provider,
        'duration' => $award->training?->formatted_duration,
        'certificate_code' => $award->certificate_code,
        'issued_at' => $award->issued_at?->format('F d, Y') ?? '-',
        'issued_year' => $award->issued_at?->format('Y') ?? now()->format('Y'),
    ])->values();
@endphp

<div
    class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col"
    x-data="awardsPage(@js($awardPayload))"
>
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">
        <div class="flex items-center justify-between px-5 py-4 border-b">
            <div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Human Capital</p>
                <h1 class="text-lg font-semibold text-gray-900">Awards & Certificates</h1>
                <p class="text-xs text-gray-500">Issued training certificates in printable A4 landscape format.</p>
            </div>
        </div>

        @if($isAdmin)
            <div class="px-5 py-3 border-b bg-gray-50">
                <div class="flex items-center justify-end gap-3">
                    <label for="employeeFilter" class="text-xs font-semibold uppercase tracking-wider text-gray-500">Employee</label>
                    <select
                        id="employeeFilter"
                        onchange="filterByEmployee(this.value)"
                        class="min-w-72 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">All employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $selectedEmployeeId == $employee->id ? 'selected' : '' }}>
                                {{ $employee->first_name }} {{ $employee->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <div class="p-5 flex-grow overflow-hidden">
            <div class="border rounded-xl h-full overflow-auto bg-white">
                <table class="w-full min-w-[980px] text-sm border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            <th class="p-3 text-left font-semibold">Employee</th>
                            <th class="p-3 text-left font-semibold">Certificate</th>
                            <th class="p-3 text-left font-semibold">Code</th>
                            <th class="p-3 text-left font-semibold">Issued</th>
                            <th class="p-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($awards as $award)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3">
                                    <div class="font-semibold text-gray-900">{{ $award->employee->full_name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $award->employee->position ?? 'Employee' }}</div>
                                </td>
                                <td class="p-3">
                                    <div class="font-semibold text-gray-900">{{ $award->training->title ?? 'Training' }}</div>
                                    <div class="text-xs text-gray-500">Certificate of Completion</div>
                                </td>
                                <td class="p-3 font-mono text-xs text-gray-700">{{ $award->certificate_code }}</td>
                                <td class="p-3 text-gray-700">{{ $award->issued_at ? $award->issued_at->format('F d, Y') : '-' }}</td>
                                <td class="p-3 text-right">
                                    <button
                                        type="button"
                                        @click="openCertificate({{ $award->id }})"
                                        class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                                    >
                                        View Certificate
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center text-gray-400">
                                    No certificates issued yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        x-show="showCertificate"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-6"
        style="display:none;"
        @click.self="closeCertificate()"
    >
        <div class="max-h-full w-full overflow-auto rounded-xl bg-gray-100 shadow-2xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b bg-white px-5 py-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Certificate Preview</p>
                    <p class="text-sm font-semibold text-gray-900" x-text="selected?.certificate_code || ''"></p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="window.print()" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">Print / Save PDF</button>
                    <button type="button" @click="closeCertificate()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Close</button>
                </div>
            </div>

            <div class="p-6">
                <div class="certificate-sheet print-area">
                    <div class="certificate-border">
                        <div class="certificate-corner top-left"></div>
                        <div class="certificate-corner top-right"></div>
                        <div class="certificate-corner bottom-left"></div>
                        <div class="certificate-corner bottom-right"></div>

                        <div class="certificate-header">
                            <img src="{{ asset('images/jk-logo-template.png') }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" class="certificate-logo" alt="John Kelly & Company Logo">
                            <div style="display:none" class="brand-fallback">John Kelly & Company</div>
                            <div class="company-lines">
                                <div>3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000</div>
                                <div>start@jknc.io | https://jknc.io | 0995-535-8729</div>
                            </div>
                        </div>

                        <div class="certificate-body">
                            <div class="eyebrow">Certificate of Completion</div>
                            <h2>This Certificate Is Proudly Presented To</h2>
                            <div class="recipient" x-text="selected?.employee_name || 'Employee'"></div>
                            <div class="recipient-meta" x-text="[selected?.employee_code, selected?.position].filter(Boolean).join(' | ')"></div>
                            <p class="certifies">for successfully completing the learning program</p>
                            <div class="training-title" x-text="selected?.training_title || 'Training Program'"></div>
                            <div class="training-meta">
                                <span x-text="selected?.provider || 'John Kelly & Company'"></span>
                                <span>•</span>
                                <span x-text="selected?.duration || 'Training duration recorded'"></span>
                            </div>
                        </div>

                        <div class="certificate-code">
                            <span>Certificate Code:</span>
                            <strong x-text="selected?.certificate_code || '-'"></strong>
                            <span>| Issued:</span>
                            <strong x-text="selected?.issued_at || '-'"></strong>
                        </div>

                        <div class="certificate-footer">
                            <div class="signature-block">
                                <div class="signature-line"></div>
                                <div class="signature-name">Human Capital</div>
                                <div class="signature-role">Authorized Representative</div>
                            </div>

                            <div class="signature-block">
                                <div class="signature-line"></div>
                                <div class="signature-name">Management</div>
                                <div class="signature-role">Approving Officer</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .certificate-sheet {
        width: 297mm;
        height: 210mm;
        margin: 0 auto;
        background: #fff;
        padding: 12mm;
        color: #111827;
        font-family: Georgia, "Times New Roman", serif;
    }

    .certificate-border {
        position: relative;
        height: 100%;
        border: 2px solid #1d4ed8;
        padding: 14mm 18mm;
        overflow: hidden;
    }

    .certificate-border::before {
        content: "";
        position: absolute;
        inset: 6mm;
        border: 1px solid #93c5fd;
        pointer-events: none;
    }

    .certificate-corner {
        position: absolute;
        width: 34mm;
        height: 34mm;
        border-color: #1d4ed8;
        pointer-events: none;
    }

    .top-left { top: 7mm; left: 7mm; border-top: 5px solid; border-left: 5px solid; }
    .top-right { top: 7mm; right: 7mm; border-top: 5px solid; border-right: 5px solid; }
    .bottom-left { bottom: 7mm; left: 7mm; border-bottom: 5px solid; border-left: 5px solid; }
    .bottom-right { bottom: 7mm; right: 7mm; border-bottom: 5px solid; border-right: 5px solid; }

    .certificate-header {
        position: relative;
        z-index: 1;
        text-align: center;
    }

    .certificate-logo {
        height: 24mm;
        margin: 0 auto 3mm;
        object-fit: contain;
    }

    .brand-fallback {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 4mm;
    }

    .company-lines {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9px;
        color: #4b5563;
        line-height: 1.5;
    }

    .certificate-body {
        position: relative;
        z-index: 1;
        text-align: center;
        padding-top: 8mm;
    }

    .eyebrow {
        color: #1d4ed8;
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .22em;
        text-transform: uppercase;
    }

    .certificate-body h2 {
        font-size: 17px;
        font-weight: normal;
        margin: 6mm 0 4mm;
    }

    .recipient {
        border-bottom: 1px solid #1f2937;
        display: inline-block;
        font-size: 42px;
        font-weight: bold;
        min-width: 170mm;
        padding-bottom: 2mm;
    }

    .recipient-meta {
        color: #6b7280;
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        margin-top: 2mm;
    }

    .certifies {
        font-size: 16px;
        margin: 7mm 0 3mm;
    }

    .training-title {
        color: #1d4ed8;
        font-size: 27px;
        font-weight: bold;
        line-height: 1.2;
        margin: 0 auto;
        max-width: 220mm;
    }

    .training-meta {
        align-items: center;
        color: #4b5563;
        display: flex;
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        gap: 8px;
        justify-content: center;
        margin-top: 4mm;
    }

    .certificate-footer {
        align-items: end;
        bottom: 18mm;
        display: grid;
        gap: 30mm;
        grid-template-columns: 1fr 1fr;
        left: 32mm;
        position: absolute;
        right: 32mm;
        z-index: 1;
    }

    .signature-block {
        text-align: center;
    }

    .signature-line {
        border-bottom: 1px solid #111827;
        height: 12mm;
    }

    .signature-name {
        font-size: 14px;
        font-weight: bold;
        margin-top: 2mm;
    }

    .signature-role {
        color: #6b7280;
        font-family: DejaVu Sans, sans-serif;
        font-size: 9px;
        margin-top: 1mm;
        text-transform: uppercase;
    }

    .certificate-code {
        bottom: 52mm;
        color: #4b5563;
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        left: 18mm;
        position: absolute;
        right: 18mm;
        text-align: center;
        z-index: 1;
    }

    @page {
        size: A4 landscape;
        margin: 0;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .print-area,
        .print-area * {
            visibility: visible;
        }

        .print-area {
            left: 0;
            margin: 0;
            position: absolute;
            top: 0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
function awardsPage(awards) {
    return {
        awards,
        selected: null,
        showCertificate: false,

        openCertificate(id) {
            this.selected = this.awards.find(award => Number(award.id) === Number(id));
            this.showCertificate = true;
        },

        closeCertificate() {
            this.showCertificate = false;
        },
    };
}

function filterByEmployee(employeeId) {
    const params = new URLSearchParams();
    if (employeeId) {
        params.append('employee_id', employeeId);
    }

    const queryString = params.toString();
    window.location.href = `{{ route('human-capital.awards') }}${queryString ? '?' + queryString : ''}`;
}
</script>
@endpush
@endsection
