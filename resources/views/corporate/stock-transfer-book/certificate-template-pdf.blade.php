@php
    $isVoucher = (($certificate->certificate_type ?? 'COS') === 'CV') || !empty($certificate->source_certificate_id);

    $sourceCertificate = null;
    try {
        if (method_exists($certificate, 'sourceCertificate')) {
            $sourceCertificate = $certificate->relationLoaded('sourceCertificate')
                ? $certificate->sourceCertificate
                : $certificate->sourceCertificate()->first();
        }
    } catch (\Throwable $e) {
        $sourceCertificate = null;
    }

    $certificateNo = $certificate->stock_number ?? '-';
    $corporation = strtoupper($certificate->corporation_name ?? 'JOHN KELLY & COMPANY');
    $shareholder = strtoupper($certificate->issued_to ?? $certificate->stockholder_name ?? '-');

    $shareCountRaw = $certificate->number ?? '';
    $shareCount = ($shareCountRaw !== null && $shareCountRaw !== '')
        ? number_format((float) $shareCountRaw, 0, '.', ',')
        : '-';

    $parValue = $certificate->par_value !== null && $certificate->par_value !== ''
        ? number_format((float) $certificate->par_value, 2, '.', ',')
        : '-';

    $amount = $certificate->amount !== null && $certificate->amount !== ''
        ? number_format((float) $certificate->amount, 2, '.', ',')
        : '-';

    $amountWords = strtoupper($certificate->amount_in_words ?? 'AMOUNT IN WORDS');

    $issuedDateValue = $certificate->released_at ?? $certificate->date_issued ?? null;
    $issuedDate = $issuedDateValue ? \Illuminate\Support\Carbon::parse($issuedDateValue) : null;

    $issueDay = $issuedDate ? $issuedDate->format('d') : '-';
    $issueMonthYear = $issuedDate ? $issuedDate->format('F Y') : '-';
    $issueFull = $issuedDate ? strtoupper($issuedDate->format('F j, Y')) : '';

    $fromWhomTransferred = strtoupper(
        $sourceCertificate->stockholder_name
            ?? $certificate->from_whom_transferred
            ?? ''
    );

    $originalCertificateNo = $sourceCertificate->stock_number
        ?? $certificate->original_certificate_no
        ?? '';

    $originalShares = '';
    if (!empty($sourceCertificate?->number)) {
        $originalShares = number_format((float) $sourceCertificate->number, 0, '.', ',');
    } elseif (!empty($certificate->original_shares)) {
        $originalShares = number_format((float) $certificate->original_shares, 0, '.', ',');
    }

    $sharesTransferred = $shareCount !== '-' ? $shareCount : '';

    $receivedCertificateNo = $certificateNo;
    $receivedShares = $shareCount !== '-' ? $shareCount : '';

    $serialNumber = str_pad((string) ($certificate->id ?? 0), 5, '0', STR_PAD_LEFT);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    @if ($isVoucher)
        <style>
            @page {
                size: 4.25in 11in;
                margin: 0.15in;
            }

            * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                font-family: "Times New Roman", Georgia, serif;
                background: #f3f4f6;
                color: #111111;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .voucher-page {
                width: 3.35in;
                min-height: 10.25in;
                margin: 0 auto;
                border: 1px solid #d9d9d9;
                background: #ffffff;
                padding: 0.22in 0.2in 0.18in 0.2in;
            }

            .title {
                text-align: center;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 17px;
                font-weight: 700;
                letter-spacing: 0.1em;
                margin-bottom: 0.08in;
            }

            .number-wrap {
                text-align: center;
                margin-bottom: 0.06in;
            }

            .number-row {
                display: inline-flex;
                align-items: center;
                gap: 7px;
            }

            .number-no {
                font-size: 34px;
                font-style: italic;
                line-height: 1;
            }

            .number-ribbon {
                min-width: 1.05in;
                padding: 5px 12px;
                border: 1px solid #444;
                border-radius: 3px;
                font-size: 15px;
                text-align: center;
                line-height: 1;
            }

            .for-row,
            .received-row,
            .dated-row,
            .day-row {
                display: flex;
                align-items: flex-end;
                gap: 5px;
                margin-top: 0.05in;
            }

            .for-row {
                justify-content: center;
                margin-top: 0.02in;
                margin-bottom: 0.03in;
            }

            .for-label,
            .shares-label,
            .received-script,
            .dated-script,
            .day-script {
                font-size: 20px;
                font-style: italic;
                line-height: 1;
                white-space: nowrap;
            }

            .line {
                border-bottom: 1px solid #111;
                min-height: 20px;
                padding: 0 4px 1px 4px;
                line-height: 18px;
                font-size: 12px;
                flex: 1;
            }

            .line.center {
                text-align: center;
            }

            .line.upper {
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .line.shares {
                flex: 0 0 1.1in;
                text-align: center;
            }

            .issued-to {
                text-align: center;
                font-size: 20px;
                font-style: italic;
                margin-top: 0.03in;
                margin-bottom: 0.02in;
            }

            .issued-lines .line {
                margin-top: 0.04in;
            }

            .small-caption {
                text-align: center;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 9px;
                letter-spacing: 0.04em;
                margin-top: 0.05in;
                margin-bottom: 0.04in;
            }

            .mini-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 0.08in;
                margin-bottom: 0.08in;
            }

            .mini-table td,
            .mini-table th {
                border: 1px solid #111;
                padding: 5px 3px;
                vertical-align: top;
                font-size: 11px;
                text-align: center;
            }

            .mini-table th {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 8.5px;
                font-weight: 600;
                text-transform: uppercase;
                line-height: 1.15;
            }

            .received-label {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 8.5px;
                text-transform: uppercase;
                line-height: 1.15;
                white-space: nowrap;
            }

            .day-row .line.day {
                flex: 0 0 0.72in;
            }

            .day-row .line.month {
                flex: 1;
            }

            .signature-block {
                margin-top: 0.28in;
            }

            .signature-line {
                width: 100%;
                border-bottom: 1px solid #111;
                height: 30px;
                margin-bottom: 6px;
            }

            .serial-number {
                margin-top: 0.14in;
                color: #c84b4b;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 17px;
                font-weight: 700;
                letter-spacing: 0.08em;
            }

            @media screen {
                body {
                    padding: 0.18in 0;
                }

                .voucher-page {
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
                }
            }

            @media print {
                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                html,
                body {
                    background: #ffffff !important;
                    padding: 0;
                }

                .voucher-page {
                    width: 3.35in;
                    min-height: 10.25in;
                    box-shadow: none;
                }
            }
        </style>
    @else
        <style>
            @page {
                size: 11in 8.5in;
                margin: 0;
            }

            * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html,
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                margin: 0;
                font-family: "Times New Roman", Georgia, serif;
                color: #3f3721;
                background: #ffffff;
            }

            .page {
                position: relative;
                width: 11in;
                height: 8.5in;
                margin: 0 auto;
                overflow: hidden;
                background:
                    radial-gradient(circle at 20% 18%, rgba(201, 173, 79, 0.18) 0, rgba(201, 173, 79, 0.05) 10%, transparent 30%),
                    radial-gradient(circle at 80% 82%, rgba(120, 146, 83, 0.16) 0, rgba(120, 146, 83, 0.04) 12%, transparent 34%),
                    linear-gradient(180deg, #faf6e9 0%, #f3ecd1 48%, #fbf8ef 100%);
            }

            .outer-frame {
                position: absolute;
                inset: 0.22in;
                border: 0.1in solid #6d7b47;
                box-shadow: inset 0 0 0 0.04in #d8c78a, inset 0 0 0 0.08in #8d9d61;
                padding: 0.18in;
            }

            .inner-frame {
                position: relative;
                width: 100%;
                height: 100%;
                border: 0.03in solid rgba(110, 93, 38, 0.5);
                background:
                    linear-gradient(180deg, rgba(255,255,255,0.72), rgba(255,255,255,0.22)),
                    repeating-linear-gradient(90deg, rgba(126, 112, 52, 0.06) 0, rgba(126, 112, 52, 0.06) 1px, transparent 1px, transparent 11px);
                padding: 0.22in 0.34in 0.2in;
                display: flex;
                flex-direction: column;
            }

            .ornament-ring {
                position: absolute;
                inset: 0.14in;
                border: 0.015in solid rgba(146, 128, 65, 0.55);
                pointer-events: none;
            }

            .header {
                display: grid;
                grid-template-columns: 1.4in 1fr 1.4in;
                align-items: center;
                gap: 0.2in;
                flex: 0 0 auto;
            }

            .number-box {
                min-height: 0.82in;
                border: 0.025in solid #7f7553;
                background: linear-gradient(180deg, #f7f3e8, #ece2bd);
                text-align: center;
                padding: 0.12in 0.08in;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                font-size: 10px;
            }

            .number-box strong {
                display: block;
                margin-top: 0.1in;
                font-size: 18px;
                letter-spacing: 0.04em;
            }

            .company-crest {
                position: relative;
                min-height: 1.2in;
                border: 0.03in solid #84744b;
                border-radius: 999px;
                background:
                    radial-gradient(circle at center, rgba(255,255,255,0.96) 0%, rgba(251,248,240,0.96) 50%, rgba(227,216,176,0.94) 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 0.36in;
                text-align: center;
            }

            .company-crest:before,
            .company-crest:after {
                content: "";
                position: absolute;
                top: 50%;
                width: 1.1in;
                border-top: 0.02in solid rgba(126, 106, 44, 0.38);
            }

            .company-crest:before { left: -1.08in; }
            .company-crest:after { right: -1.08in; }

            .company-name {
                font-size: 0.28in;
                line-height: 1.05;
                letter-spacing: 0.08em;
                font-weight: 700;
                text-transform: uppercase;
                color: #4a4226;
            }

            .title {
                margin-top: 0.12in;
                text-align: center;
                text-transform: uppercase;
                letter-spacing: 0.18em;
                font-size: 0.23in;
                font-weight: 700;
                color: #4f5f30;
                flex: 0 0 auto;
            }

            .subtitle {
                margin-top: 0.04in;
                text-align: center;
                font-size: 0.11in;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                color: #736742;
                flex: 0 0 auto;
            }

            .rule {
                width: 82%;
                height: 0.03in;
                margin: 0.1in auto 0;
                background: linear-gradient(90deg, transparent, #907d42, transparent);
                flex: 0 0 auto;
            }

            .body {
                margin-top: 0.14in;
                padding: 0 0.12in;
                text-align: center;
                flex: 1 1 auto;
            }

            .certifies {
                font-size: 0.21in;
                font-weight: 700;
                color: #4d4528;
            }

            .copy {
                margin-top: 0.12in;
                font-size: 0.17in;
                line-height: 1.58;
                font-style: italic;
                color: #4d4528;
            }

            .fill {
                display: inline-block;
                border-bottom: 0.02in solid rgba(91, 79, 42, 0.55);
                padding: 0 0.08in 0.03in;
                line-height: 1.2;
                font-style: normal;
                font-weight: 700;
                letter-spacing: 0.03em;
                text-transform: uppercase;
            }

            .fill.name { min-width: 5.7in; font-size: 0.23in; }
            .fill.shares { min-width: 0.9in; }
            .fill.company { min-width: 3.8in; }
            .fill.day { min-width: 0.52in; }
            .fill.month { min-width: 1.7in; }

            .witness {
                margin-top: 0.14in;
                font-size: 0.155in;
                line-height: 1.52;
                font-style: italic;
            }

            .footer {
                display: grid;
                grid-template-columns: 1.2fr 0.88fr;
                gap: 0.3in;
                align-items: end;
                margin-top: 0.14in;
                flex: 0 0 auto;
            }

            .seal-area {
                display: flex;
                align-items: center;
                gap: 0.22in;
                min-height: 0.86in;
            }

            .seal {
                position: relative;
                width: 0.86in;
                height: 0.86in;
                border-radius: 50%;
                background:
                    radial-gradient(circle at center, #f9e9a5 0%, #c19b2d 57%, #8d6714 100%);
                box-shadow: inset 0 0 0 0.05in rgba(135, 95, 15, 0.42), 0 0 0 0.02in rgba(135, 95, 15, 0.42);
            }

            .seal:before {
                content: "";
                position: absolute;
                inset: 0.13in;
                border-radius: 50%;
                border: 0.02in dashed rgba(255, 246, 196, 0.72);
            }

            .seal-copy {
                max-width: 2.6in;
                font-size: 0.12in;
                line-height: 1.35;
                font-style: italic;
            }

            .signatures {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.26in;
                align-items: end;
            }

            .signature {
                text-align: center;
            }

            .sig-line {
                border-top: 0.02in solid rgba(84, 72, 36, 0.6);
                padding-top: 0.05in;
                font-size: 0.12in;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .sig-role {
                margin-top: 0.03in;
                font-size: 0.095in;
                text-transform: uppercase;
                letter-spacing: 0.12em;
                color: #6c613c;
            }

            .bottom-strip {
                display: grid;
                grid-template-columns: 2fr 0.9fr 0.7fr;
                gap: 0.14in;
                margin-top: 0.12in;
                flex: 0 0 auto;
            }

            .plate {
                min-height: 0.34in;
                border: 0.025in solid #7f7553;
                background: linear-gradient(180deg, #f7f3e8, #e7dbb4);
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 0.04in 0.08in;
                font-size: 0.095in;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            .plate.words {
                font-size: 0.08in;
                letter-spacing: 0.05em;
            }

            @media print {
                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                html,
                body {
                    background: #ffffff !important;
                }

                .page,
                .outer-frame,
                .inner-frame,
                .company-crest,
                .number-box,
                .plate,
                .seal {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
            }
        </style>
    @endif
</head>
<body>
@if ($isVoucher)
    <div class="voucher-page">
        <div class="title">CERTIFICATE</div>

        <div class="number-wrap">
            <div class="number-row">
                <div class="number-no">No.</div>
                <div class="number-ribbon">{{ $certificateNo }}</div>
            </div>
        </div>

        <div class="for-row">
            <div class="for-label">For</div>
            <div class="line shares center">{{ $shareCount }}</div>
            <div class="shares-label">Shares</div>
        </div>

        <div class="issued-to">Issued to</div>

        <div class="issued-lines">
            <div class="line upper">{{ $shareholder }}</div>
            <div class="line upper">{{ $corporation }}</div>
            <div class="line">&nbsp;</div>
        </div>

        <div class="dated-row" style="margin-top: 0.14in;">
            <div class="dated-script">Dated</div>
            <div class="line center">{{ $issueFull }}</div>
        </div>

        <div class="small-caption">FROM WHOM TRANSFERRED</div>
        <div class="line upper">{{ $fromWhomTransferred ?: ' ' }}</div>

        <div class="dated-row" style="margin-top: 0.2in;">
            <div class="dated-script">Dated</div>
            <div class="line">&nbsp;</div>
        </div>

        <table class="mini-table">
            <tr>
                <th>NO. ORIGINAL<br>CERTIFICATE</th>
                <th>NO. ORIGINAL<br>SHARES</th>
                <th>NO. OF SHARES<br>TRANSFERRED</th>
            </tr>
            <tr>
                <td style="height: 56px;">{{ $originalCertificateNo }}</td>
                <td>{{ $originalShares }}</td>
                <td>{{ $sharesTransferred }}</td>
            </tr>
        </table>

        <div class="received-row" style="margin-top: 0.1in;">
            <div class="received-script">Received</div>
            <div class="received-label">Certificate No.</div>
            <div class="line center">{{ $receivedCertificateNo }}</div>
        </div>

        <div class="for-row" style="margin-top: 0.08in;">
            <div class="for-label">For</div>
            <div class="line shares center">{{ $receivedShares }}</div>
            <div class="shares-label">Shares</div>
        </div>

        <div class="day-row" style="margin-top: 0.08in;">
            <div class="day-script">this</div>
            <div class="line day">&nbsp;</div>
            <div class="day-script">day of</div>
            <div class="line month">&nbsp;</div>
        </div>

        <div class="line" style="margin-top: 0.08in;">&nbsp;</div>

        <div class="signature-block">
            <div class="signature-line"></div>
        </div>

        <div class="serial-number">{{ $serialNumber }}</div>
    </div>
@else
    <div class="page">
        <div class="outer-frame">
            <div class="inner-frame">
                <div class="ornament-ring"></div>

                <div class="header">
                    <div class="number-box">
                        Certificate No.
                        <strong>{{ $certificateNo }}</strong>
                    </div>

                    <div class="company-crest">
                        <div class="company-name">{{ $corporation }}</div>
                    </div>

                    <div class="number-box">
                        No. of Shares
                        <strong>{{ $shareCount }}</strong>
                    </div>
                </div>

                <div class="title">Certificate for Shares of the Capital Stock</div>
                <div class="subtitle">Transferable only on the books of the corporation</div>
                <div class="rule"></div>

                <div class="body">
                    <div class="certifies">This Certifies That</div>

                    <div class="copy">
                        <span class="fill name">{{ $shareholder }}</span>
                        is the owner of
                        <span class="fill shares">{{ $shareCount }}</span>
                        shares of the capital stock of
                        <span class="fill company">{{ $corporation }}</span>,
                        transferable only on the books of the Corporation by the holder hereof in person or by duly authorized attorney upon surrender of this certificate properly endorsed.
                    </div>

                    <div class="witness">
                        In Witness Whereof, the said Corporation has caused this certificate to be signed by its duly authorized officers and its corporate seal to be hereunto affixed this
                        <span class="fill day">{{ $issueDay }}</span>
                        day of
                        <span class="fill month">{{ $issueMonthYear }}</span>.
                    </div>
                </div>

                <div class="footer">
                    <div class="seal-area">
                        <div class="seal"></div>
                        <div class="seal-copy">
                            Corporate seal affixed for authentication of this stock certificate and the shares represented herein.
                        </div>
                    </div>

                    <div class="signatures">
                        <div class="signature">
                            <div class="sig-line">{{ strtoupper((string) ($certificate->president ?? '-')) }}</div>
                            <div class="sig-role">President</div>
                        </div>

                        <div class="signature">
                            <div class="sig-line">{{ strtoupper((string) ($certificate->corporate_secretary ?? '-')) }}</div>
                            <div class="sig-role">Corporate Secretary</div>
                        </div>
                    </div>
                </div>

                <div class="bottom-strip">
                    <div class="plate words">{{ $amountWords }}</div>
                    <div class="plate">{{ $parValue }}</div>
                    <div class="plate">Each</div>
                </div>
            </div>
        </div>
    </div>
@endif
</body>
</html>