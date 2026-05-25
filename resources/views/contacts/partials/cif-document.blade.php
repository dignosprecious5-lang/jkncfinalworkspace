@php
    $logoPath = asset('images/imaglogo.png');
    $text = static fn ($key, $fallback = '-') => filled($cifData[$key] ?? null) ? $cifData[$key] : $fallback;
    $checked = static fn (bool $state): string => $state ? 'active' : '';
    $hasFund = static fn (string $value): bool => in_array($value, $cifData['source_of_funds'] ?? [], true);
    $citizenshipType = $cifData['citizenship_type'] ?? '';
    $showForeignSections = in_array($citizenshipType, ['foreigner', 'dual_citizen'], true);
    $spouseName = trim((string) ($cifData['spouse_name'] ?? ''));
    $referredByFooter = trim((string) ($cifData['referred_by_footer'] ?? ''));
    $referredDate = trim((string) ($cifData['referred_date'] ?? ''));
@endphp

<style>
    .cif-doc { border: 1.2px solid #334155; background: #fff; font-family: "Times New Roman", Georgia, serif; color: #0f172a; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .cif-doc *, .cif-doc *::before, .cif-doc *::after { box-sizing: border-box; }
    .cif-doc-head { display: grid; grid-template-columns: 164px minmax(0, 1fr); gap: 10px; align-items: start; border-bottom: 1.1px solid #334155; padding: 8px 9px 6px; }
    .cif-doc-brand img { max-width: 140px; height: auto; object-fit: contain; }
    .cif-doc-title { font-family: Arial, sans-serif; font-size: 14px; font-weight: 700; text-transform: uppercase; text-align: right; line-height: 1.05; }
    .cif-doc-head-main { display: flex; flex-direction: column; gap: 5px; }
    .cif-doc-head-meta { display: grid; grid-template-columns: minmax(0,1fr) 208px; gap: 10px; align-items: start; }
    .cif-doc-line { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 12px; font-size: 9px; }
    .cif-doc-head-side { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; text-align: right; }
    .cif-doc-row { display: grid; grid-template-columns: repeat(24, minmax(0, 1fr)); }
    .cif-doc-cell { min-height: 29px; border-right: 1.05px solid #334155; border-bottom: 1.05px solid #334155; padding: 2px 4px; background: #fff; }
    .cif-doc-row > .cif-doc-cell:last-child { border-right: 0; }
    .cif-doc-label { display: block; font-size: 8px; line-height: 1.05; text-transform: uppercase; font-weight: 700; color: #111827; }
    .cif-doc-value { display: block; padding-top: 1px; font-size: 9px; line-height: 1.05; color: #0f172a; }
    .cif-doc-inline { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 3px 8px; padding-top: 2px; font-size: 8px; line-height: 1.05; }
    .cif-doc-inline-compact { display: flex; flex-wrap: wrap; gap: 3px 10px; padding-top: 2px; font-size: 8px; line-height: 1.05; }
    .cif-doc-inline-two { display: grid; grid-template-columns: repeat(4, minmax(76px, 1fr)) minmax(208px, 1.6fr); gap: 4px 12px; padding-top: 2px; font-size: 8px; line-height: 1.05; align-items: center; padding-right: 6px; }
    .cif-doc-inline-field { display: flex; align-items: center; gap: 8px; font-size: 8px; line-height: 1.05; min-width: 0; padding-right: 8px; }
    .cif-doc-inline-field-label { font-weight: 700; white-space: nowrap; }
    .cif-doc-inline-field-value { flex: 1 1 auto; min-width: 0; border-bottom: 1px solid #334155; padding: 0 8px 1px 2px; font-size: 9px; line-height: 1.05; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cif-doc-mark { display: inline-flex; width: 12px; height: 12px; align-items: center; justify-content: center; border: 1px solid #334155; border-radius: 2px; background: #fff; margin-right: 4px; }
    .cif-doc-mark.active { border-color: #1d54e2; background: #1d54e2; box-shadow: inset 0 0 0 2px #1d54e2; }
    .cif-doc-section { border-bottom: 1.05px solid #334155; padding: 3px 6px; background: #102d79; color: #ffffff; font-size: 9px; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 0.02em; }
    .cif-doc-note { font-size: 8px; line-height: 1.28; text-align: justify; color: #1f2937; }
    .cif-doc-sign-cell { display: flex; flex-direction: column; padding: 5px 8px 4px; }
    .cif-doc-sign-fill { flex: 0 0 auto; min-height: 16px; }
    .cif-doc-sign { border-top: 1px solid #334155; margin-top: 2px; margin-left: -8px; margin-right: -8px; padding-top: 11px; position: relative; width: calc(100% + 16px); font-size: 8px; line-height: 1.1; }
    .cif-doc-sign > span { position: absolute; top: 2px; left: 50%; transform: translateX(-50%); width: max-content; text-align: center; white-space: nowrap; }
    .cif-doc-position { border-bottom: 1px solid #334155; margin-top: 4px; margin-left: -8px; margin-right: -8px; width: calc(100% + 16px); padding: 2px 0 1px; font-size: 8px; text-align: center; }
    .cif-doc-sign-name { padding-top: 0; text-align: center; }
    .cif-doc-static-box { min-height: 66px; padding: 4px 6px; font-size: 8px; line-height: 1.3; }
    .cif-doc-static-box h4 { margin: 0 0 4px; font-size: 8px; font-weight: 700; text-transform: uppercase; text-align: center; }
    .cif-doc-static-box ol { margin: 0; padding-left: 14px; }
    .cif-doc-numbered-list { display: grid; gap: 1px; }
    .cif-doc-numbered-item { display: grid; grid-template-columns: 22px minmax(0, 1fr); gap: 6px; align-items: start; }
    .cif-doc-number { font-weight: 700; }
    .doc-col-2 { grid-column: span 2 / span 2; } .doc-col-3 { grid-column: span 3 / span 3; } .doc-col-4 { grid-column: span 4 / span 4; }
    .doc-col-5 { grid-column: span 5 / span 5; } .doc-col-6 { grid-column: span 6 / span 6; } .doc-col-7 { grid-column: span 7 / span 7; }
    .doc-col-8 { grid-column: span 8 / span 8; } .doc-col-9 { grid-column: span 9 / span 9; } .doc-col-10 { grid-column: span 10 / span 10; }
    .doc-col-12 { grid-column: span 12 / span 12; } .doc-col-14 { grid-column: span 14 / span 14; } .doc-col-15 { grid-column: span 15 / span 15; }
    .doc-col-16 { grid-column: span 16 / span 16; } .doc-col-18 { grid-column: span 18 / span 18; } .doc-col-24 { grid-column: span 24 / span 24; }
    @media (max-width: 1024px) {
        .cif-doc-head { grid-template-columns: 132px minmax(0, 1fr); gap: 8px; }
        .cif-doc-brand img { max-width: 116px; }
        .cif-doc-inline-two { grid-template-columns: repeat(4, minmax(66px, 1fr)) minmax(180px, 1.45fr); gap: 4px 8px; padding-right: 4px; }
    }
    @media (max-width: 820px) {
        .cif-doc-inline-two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            row-gap: 5px;
            column-gap: 10px;
            padding-right: 0;
        }
        .cif-doc-inline-two .cif-doc-inline-field {
            grid-column: 1 / -1;
            padding-right: 2px;
        }
    }
</style>

<div class="cif-doc cif-print-document mx-auto w-full max-w-5xl">
    <div class="cif-doc-head">
        <div class="cif-doc-brand"><img src="{{ $logoPath }}" alt="John Kelly and Company"></div>
        <div class="cif-doc-head-main">
            <div class="cif-doc-title">Client Information<br>Form</div>
            <div class="cif-doc-head-meta">
                <div></div>
                <div class="cif-doc-head-side">
                    <div class="cif-doc-line" style="justify-content:flex-end;"><span>DATE:</span><span>{{ $text('cif_date', '') }}</span></div>
                    <div class="cif-doc-line" style="justify-content:flex-end;"><span>CIF No.</span><span>{{ $text('cif_no', '') }}</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="cif-doc-section">Personal Information</div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-5"><span class="cif-doc-label">First Name</span><span class="cif-doc-value">{{ $text('first_name') }}</span></div>
        <div class="cif-doc-cell doc-col-5"><span class="cif-doc-label">Last Name</span><span class="cif-doc-value">{{ $text('last_name') }}</span></div>
        <div class="cif-doc-cell doc-col-4"><span class="cif-doc-label">Middle Name</span><span class="cif-doc-value">{{ $text('middle_name') }}</span></div>
        <div class="cif-doc-cell doc-col-3"><span class="cif-doc-label">Name Extension</span><span class="cif-doc-value">{{ $text('name_extension') }}</span></div>
        <div class="cif-doc-cell doc-col-7">
            <span class="cif-doc-label">Name Notes</span>
            <div class="cif-doc-inline-compact">
                <span><span class="cif-doc-mark {{ $checked((bool) ($cifData['no_middle_name'] ?? false)) }}"></span>No Middle Name</span>
                <span><span class="cif-doc-mark {{ $checked((bool) ($cifData['only_first_name'] ?? false)) }}"></span>Only First Name</span>
            </div>
        </div>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-18"><span class="cif-doc-label">Present Address</span><span class="cif-doc-value">{{ $text('present_address_line1') }}</span></div>
        <div class="cif-doc-cell doc-col-6"><span class="cif-doc-label">Zip Code</span><span class="cif-doc-value">{{ $text('zip_code') }}</span></div>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-18"><span class="cif-doc-label">Present Address (2nd Line)</span><span class="cif-doc-value">{{ $text('present_address_line2') }}</span></div>
        <div class="cif-doc-cell doc-col-6"><span class="cif-doc-label">Zip Code</span><span class="cif-doc-value">{{ $text('zip_code') }}</span></div>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-12"><span class="cif-doc-label">Email Address</span><span class="cif-doc-value">{{ $text('email') }}</span></div>
        <div class="cif-doc-cell doc-col-12"><span class="cif-doc-label">Phone No. / Mobile No.</span><span class="cif-doc-value">{{ $text('mobile') }}</span></div>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-5"><span class="cif-doc-label">Date of Birth</span><span class="cif-doc-value">{{ $text('date_of_birth') }}</span></div>
        <div class="cif-doc-cell doc-col-5"><span class="cif-doc-label">Place of Birth</span><span class="cif-doc-value">{{ $text('place_of_birth') }}</span></div>
        <div class="cif-doc-cell doc-col-14">
            <span class="cif-doc-label">Citizenship / Nationality</span>
            <div class="cif-doc-value">{{ $text('citizenship_nationality') }}</div>
            <div class="cif-doc-inline-compact">
                <span><span class="cif-doc-mark {{ $checked(($cifData['citizenship_type'] ?? '') === 'filipino') }}"></span>Filipino</span>
                <span><span class="cif-doc-mark {{ $checked(($cifData['citizenship_type'] ?? '') === 'foreigner') }}"></span>Foreigner</span>
                <span><span class="cif-doc-mark {{ $checked(($cifData['citizenship_type'] ?? '') === 'dual_citizen') }}"></span>Dual Citizen</span>
            </div>
        </div>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-4">
            <span class="cif-doc-label">Gender</span>
            <div class="cif-doc-inline-compact">
                <span><span class="cif-doc-mark {{ $checked(($cifData['gender'] ?? '') === 'male') }}"></span>Male</span>
                <span><span class="cif-doc-mark {{ $checked(($cifData['gender'] ?? '') === 'female') }}"></span>Female</span>
            </div>
        </div>
        <div class="cif-doc-cell doc-col-20">
            <span class="cif-doc-label">Civil Status</span>
            <div class="cif-doc-inline-two">
                <span><span class="cif-doc-mark {{ $checked(($cifData['civil_status'] ?? '') === 'single') }}"></span>Single</span>
                <span><span class="cif-doc-mark {{ $checked(($cifData['civil_status'] ?? '') === 'separated') }}"></span>Separated</span>
                <span><span class="cif-doc-mark {{ $checked(($cifData['civil_status'] ?? '') === 'widowed') }}"></span>Widowed</span>
                <span><span class="cif-doc-mark {{ $checked(($cifData['civil_status'] ?? '') === 'married') }}"></span>Married</span>
                @if (($cifData['civil_status'] ?? '') === 'married')
                    <span class="cif-doc-inline-field">
                        <span class="cif-doc-inline-field-label">Spouse's Name</span>
                        <span class="cif-doc-inline-field-value">{{ $spouseName !== '' ? $spouseName : ' ' }}</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if ($showForeignSections)
    <div class="cif-doc-section">Foreigner Information</div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-8"><span class="cif-doc-label">Passport No.</span><span class="cif-doc-value">{{ $text('foreigner_passport_no') }}</span></div>
        <div class="cif-doc-cell doc-col-4"><span class="cif-doc-label">Expiry Date</span><span class="cif-doc-value">{{ $text('foreigner_passport_expiry_date') }}</span></div>
        <div class="cif-doc-cell doc-col-12"><span class="cif-doc-label">Place of Issue</span><span class="cif-doc-value">{{ $text('foreigner_passport_place_of_issue') }}</span></div>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-8"><span class="cif-doc-label">ACR ID No.</span><span class="cif-doc-value">{{ $text('foreigner_acr_id_no') }}</span></div>
        <div class="cif-doc-cell doc-col-4"><span class="cif-doc-label">Expiry Date</span><span class="cif-doc-value">{{ $text('foreigner_acr_expiry_date') }}</span></div>
        <div class="cif-doc-cell doc-col-6"><span class="cif-doc-label">Place of Issue</span><span class="cif-doc-value">{{ $text('foreigner_acr_place_of_issue') }}</span></div>
        <div class="cif-doc-cell doc-col-6"><span class="cif-doc-label">Visa Status</span><span class="cif-doc-value">{{ $text('visa_status') }}</span></div>
    </div>
    @endif

    <div class="cif-doc-section">Employment and Identity</div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-10"><span class="cif-doc-label">Nature of Work / Business</span><span class="cif-doc-value">{{ $text('nature_of_work_business') }}</span></div>
        <div class="cif-doc-cell doc-col-4"><span class="cif-doc-label">TIN</span><span class="cif-doc-value">{{ $text('tin') }}</span></div>
        <div class="cif-doc-cell doc-col-5"><span class="cif-doc-label">Other Government ID</span><span class="cif-doc-value">{{ $text('other_government_id') }}</span></div>
        <div class="cif-doc-cell doc-col-5"><span class="cif-doc-label">ID Number</span><span class="cif-doc-value">{{ $text('id_number') }}</span></div>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-24"><span class="cif-doc-label">Mother's Maiden Name</span><span class="cif-doc-value">{{ $text('mothers_maiden_name') }}</span></div>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell doc-col-24">
            <span class="cif-doc-label">Source of Funds</span>
            <div class="cif-doc-inline">
                <span><span class="cif-doc-mark {{ $checked($hasFund('salary')) }}"></span>Salary</span>
                <span><span class="cif-doc-mark {{ $checked($hasFund('remittance')) }}"></span>Remittance</span>
                <span><span class="cif-doc-mark {{ $checked($hasFund('business')) }}"></span>Business</span>
                <span><span class="cif-doc-mark {{ $checked($hasFund('commission_fees')) }}"></span>Commission / Fees</span>
                <span><span class="cif-doc-mark {{ $checked($hasFund('retirement_pension')) }}"></span>Retirement / Pension</span>
                <span><span class="cif-doc-mark {{ $checked($hasFund('others')) }}"></span>Others</span>
            </div>
            <span class="cif-doc-value">{{ $text('source_of_funds_other_text', '') }}</span>
        </div>
    </div>

    <div class="cif-doc-section">Acknowledgment</div>
    <div class="border-b border-gray-600 px-2 py-2">
        <p class="cif-doc-note">By signing this Client Information Form, I certify that all personal information provided herein is true, correct, and complete to the best of my knowledge. I agree to comply with the policies, procedures, and service guidelines of JK&amp;C Inc. and authorize JK&amp;C Inc., its officers, employees, consultants, and representatives to collect, verify, record, process, store, and use the information provided for purposes of client registration, due diligence, compliance verification, service engagement, documentation, billing, and regulatory requirements.</p>
        <p class="cif-doc-note" style="margin-top:4px;">In accordance with the Data Privacy Act of 2012 (Republic Act No. 10173), I voluntarily consent to the collection, processing, storage, and lawful use of my personal information contained in this form. I acknowledge that the information provided shall constitute the official client information on record of JK&amp;C Inc. and may be relied upon in official communications, notices, service documents, billing statements, formal correspondence, and demand letters relating to services rendered or obligations arising from the engagement.</p>
    </div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell cif-doc-sign-cell doc-col-12" style="min-height:76px;">
            <div class="cif-doc-sign-fill"><span class="cif-doc-value cif-doc-sign-name">{{ $text('sig_name_left', ' ') }}</span></div>
            <div class="cif-doc-sign"><span>Signature over Printed Name</span></div>
            <div class="cif-doc-position">{{ $text('sig_position_left', 'Position') }}</div>
        </div>
        <div class="cif-doc-cell cif-doc-sign-cell doc-col-12" style="min-height:76px;">
            <div class="cif-doc-sign-fill"><span class="cif-doc-value cif-doc-sign-name">{{ $text('sig_name_right', ' ') }}</span></div>
            <div class="cif-doc-sign"><span>Signature over Printed Name</span></div>
            <div class="cif-doc-position">{{ $text('sig_position_right', 'Position') }}</div>
        </div>
    </div>

    <div class="cif-doc-section">Client Onboarding Requirements</div>
    <div class="border-b border-gray-600">
        <div class="cif-doc-static-box">
            <div class="cif-doc-numbered-list">
                <div class="cif-doc-numbered-item"><span class="cif-doc-number">1 |</span><span>2 Valid Government IDs</span></div>
                <div class="cif-doc-numbered-item"><span class="cif-doc-number">2 |</span><span>TIN ID (Signatory/Representative/Stockholders/Partners/Others)</span></div>
                <div class="cif-doc-numbered-item"><span class="cif-doc-number">3 |</span><span>AUTHORIZED SIGNATORY/SIGNATORY (Sole / OPC / Individual) SPECIMEN SIGNATURE CARD</span></div>
                @if ($showForeignSections)
                    <div class="cif-doc-numbered-item"><span class="cif-doc-number">4 |</span><span>If Foreign Signatory/Director/Officer: Passport (Bio Page)</span></div>
                    <div class="cif-doc-numbered-item"><span class="cif-doc-number">5 |</span><span>If Foreign Signatory/Director/Officer: Valid Visa / ACR I-Card</span></div>
                    <div class="cif-doc-numbered-item"><span class="cif-doc-number">6 |</span><span>If Foreign Signatory/Director/Officer Alien Employment Permit (AEP)</span></div>
                @endif
            </div>
        </div>
    </div>

    <div class="cif-doc-section">For JKNC Use Only</div>
    <div class="cif-doc-row">
        <div class="cif-doc-cell cif-doc-sign-cell doc-col-6" style="min-height:72px;">
            <div class="cif-doc-sign-fill">
                <span class="cif-doc-label">Referred By / Date</span>
                <span class="cif-doc-value">{{ $referredByFooter }}{{ $referredDate !== '' ? ' / '.$referredDate : '' }}</span>
            </div>
            <div class="cif-doc-sign"><span>Signature over Printed Name</span></div>
        </div>
        <div class="cif-doc-cell cif-doc-sign-cell doc-col-6" style="min-height:72px;">
            <div class="cif-doc-sign-fill">
                <span class="cif-doc-label">Sales &amp; Marketing</span>
                <span class="cif-doc-value">{{ $text('sales_marketing_footer', '') }}</span>
            </div>
            <div class="cif-doc-sign"><span>Signature over Printed Name</span></div>
        </div>
        <div class="cif-doc-cell cif-doc-sign-cell doc-col-6" style="min-height:72px;">
            <div class="cif-doc-sign-fill">
                <span class="cif-doc-label">Finance</span>
                <span class="cif-doc-value">{{ $text('finance_footer', '') }}</span>
            </div>
            <div class="cif-doc-sign"><span>Signature over Printed Name</span></div>
        </div>
        <div class="cif-doc-cell cif-doc-sign-cell doc-col-6" style="min-height:72px;">
            <div class="cif-doc-sign-fill">
                <span class="cif-doc-label">President</span>
                <span class="cif-doc-value">{{ $text('president_footer', '') }}</span>
            </div>
            <div class="cif-doc-sign"><span>Signature over Printed Name</span></div>
        </div>
    </div>
</div>
