<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specimen Signature</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { min-height: 100%; }
        body {
            background: linear-gradient(180deg, #eaf1fb 0%, #f8fafc 100%);
            color: #000;
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-shell {
            margin: 0 auto;
            max-width: 1200px;
            padding: 24px;
        }
        .document-page {
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            background: #fff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-family: "Times New Roman", serif;
            font-size: 11.5px;
        }
        td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        .doc {
            width: 100%;
            border: 0;
        }
        .line {
            border-bottom: 1px solid #000;
            min-height: 14px;
        }
        .specimen-print-document {
            width: 100%;
        }
        .specimen-print-document > table {
            height: 100%;
        }
        @media print {
            .specimen-print-document {
                border: 0 !important;
            }

            .specimen-print-document > table {
                height: 100% !important;
            }

            .specimen-print-document table {
                line-height: 1.08 !important;
            }

            .specimen-print-document td {
                padding: 2px 3px !important;
            }

            .specimen-print-document .line {
                min-height: 11px !important;
            }
        }
    </style>
    @include('partials.a4-fit-to-page')
</head>
<body>
@php
    $authenticationData = (array) ($data->authentication_data ?? []);
    $isBusinessContact = ($contact->customer_type ?? null) === 'business';
    $signatories = collect((array) ($data->signatories ?? []))
        ->map(fn ($entry) => is_array($entry) ? ($entry['name'] ?? null) : null)
        ->pad(6, null)
        ->take(6)
        ->values()
        ->all();
    $logo = asset('images/imaglogo.png');
@endphp

<div class="print-shell a4-fit-shell">
    @unless (!empty($embedMode))
        <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Specimen Signature Form</h2>
                <p class="text-sm text-gray-500">Use your browser's print dialog and choose Save as PDF to export this document.</p>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="window.location.href='{{ $backUrl ?? route('contacts.show', ['contact' => $contact->id, 'tab' => 'kyc']) }}'"
                    class="inline-flex h-10 items-center rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Back
                </button>

                <button type="button" onclick="window.printA4FitPage()"
                    class="inline-flex h-10 items-center rounded-full bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                    Print / Save as PDF
                </button>
            </div>
        </div>
    @endunless

<div class="document-page a4-fit-page" data-a4-fit-inset-mm="1" data-a4-fit-safety-scale="1" data-a4-fit-fill-page="true">
<div class="doc specimen-print-document a4-fit-content" data-a4-fit-inset-mm="1" data-a4-fit-safety-scale="1" data-a4-fit-fill-page="true">
    <table>
        <tr>
            <td width="20%" style="border-right:0; border-bottom:0; padding:6px 6px 2px 6px;">
                <img src="{{ $logo }}" style="height:48px; width:auto; object-fit:contain;" alt="John Kelly and Company">
            </td>
            <td width="46%" style="border-left:0; border-right:0; border-bottom:0;">&nbsp;</td>
            <td width="34%" style="border-left:0; border-bottom:0; text-align:right; line-height:1.2; font-weight:bold; font-size:12px;">
                @if ($isBusinessContact)
                    AUTHORIZED SIGNATORY<br>
                    SPECIMEN SIGNATURE CARD<br>
                    <span style="font-style:italic; font-size:11px;">CORPORATION / PARTNERSHIP / OTHER JURIDICAL ENTITY</span><br>
                    <span style="font-size:10px;">CASA-F-005-V1.0-03.16.26</span>
                @else
                    AUTHORIZED SIGNATORY/SIGNATORY<br>
                    (Sole / OPC / INDIVIDUAL)<br>
                    SPECIMEN SIGNATURE CARD
                @endif
            </td>
        </tr>

        <tr>
            <td style="border-top:0; border-right:0; padding-top:14px;">
                <div style="font-weight:bold;">BIF NO.</div>
                <div class="line">{{ $data->bif_no ?? '' }}</div>
            </td>
            <td style="border-top:0; border-left:0; border-right:0;">&nbsp;</td>
            <td style="border-top:0; border-left:0; padding-top:14px;">
                <table style="border:0;">
                    <tr>
                        <td width="24%" style="border:0; font-weight:bold;">DATE:</td>
                        <td width="76%" style="border:0;"><div class="line">{{ optional($data->date)->format('Y-m-d') }}</div></td>
                    </tr>
                </table>
            </td>
        </tr>

        @if ($isBusinessContact)
        <tr>
            <td colspan="3" style="padding:4px 8px;">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse; table-layout:fixed;">
                    <tr>
                        @foreach (['new' => 'New Client', 'existing' => 'Existing Client', 'change' => 'Change Information'] as $value => $label)
                            <td style="border:0; text-align:center;">
                                <span style="display:inline-flex; align-items:center; gap:6px; font-size:12px;">
                                    <span style="display:inline-block; width:12px; height:12px; border:1px solid #000; text-align:center; line-height:10px; font-size:10px;">{{ ($data->client_type ?? '') === $value ? 'x' : '' }}</span>
                                    <span>{{ $label }}</span>
                                </span>
                            </td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        <tr>
            <td colspan="3" style="padding:0;">
                <table style="border:0;">
                    <tr>
                        <td width="50%" style="border:0; border-right:1px solid #000; padding:0;">
                            <table style="border:0;">
                                <tr><td style="border:0;">BUSINESS NAME</td></tr>
                                <tr><td style="border:0;"><div class="line">{{ $data->business_name_left ?? '' }}</div></td></tr>
                                <tr><td style="border:0;">BUSINESS ACCOUNT NUMBER</td></tr>
                                <tr><td style="border:0;"><div class="line">{{ $data->account_number_left ?? '' }}</div></td></tr>
                                <tr>
                                    <td style="border:0; padding:0;">
                                        <table style="border:0;">
                                            <tr>
                                                <td width="56%" style="border:0;">SIGNATURE COMBINATION</td>
                                                <td width="44%" style="border:0;">SIGNATURE CLASS</td>
                                            </tr>
                                            <tr>
                                                <td style="border:0;"><div class="line">{{ $authenticationData['signature_combination'] ?? '' }}</div></td>
                                                <td style="border:0;"><div class="line">{{ $authenticationData['signature_class'] ?? '' }}</div></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%" style="border:0; padding:0;">
                            <table style="border:0;">
                                <tr><td style="border:0;">BUSINESS NAME</td></tr>
                                <tr><td style="border:0;"><div class="line">{{ $data->business_name_right ?? '' }}</div></td></tr>
                                <tr><td style="border:0;">BUSINESS ACCOUNT NUMBER</td></tr>
                                <tr><td style="border:0;"><div class="line">{{ $data->account_number_right ?? '' }}</div></td></tr>
                                <tr>
                                    <td style="border:0; padding:0;">
                                        <table style="border:0;">
                                            <tr>
                                                <td width="56%" style="border:0;">SIGNATURE COMBINATION</td>
                                                <td width="44%" style="border:0;">SIGNATURE CLASS</td>
                                            </tr>
                                            <tr>
                                                <td style="border:0;"><div class="line">{{ $authenticationData['signature_combination'] ?? '' }}</div></td>
                                                <td style="border:0;"><div class="line">{{ $authenticationData['signature_class'] ?? '' }}</div></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="3" style="text-align:center; font-weight:bold; font-size:13px; padding-top:3px; padding-bottom:3px;">AUTHORIZE SIGNATORIES</td>
        </tr>

        <tr>
            <td colspan="3" style="padding:0;">
                <table style="border:0;">
                    <tr>
                        <td width="50%" style="border:0; border-right:1px solid #000; vertical-align:top; padding:0;">
                            <table style="border:0;">
                                <tr><td colspan="2" style="border:0;">CLIENT NAME:</td></tr>
                                <tr><td colspan="2" style="border:0;"><div class="line">{{ $authenticationData['left_client_name'] ?? '' }}</div></td></tr>
                                <tr>
                                    <td width="45%" style="border:0;">CIF NO.:</td>
                                    <td width="55%" style="border:0;">CIF Dated:</td>
                                </tr>
                                <tr>
                                    <td style="border:0;"><div class="line">{{ $authenticationData['left_cif_no'] ?? '' }}</div></td>
                                    <td style="border:0;"><div class="line">{{ $authenticationData['left_cif_dated'] ?? '' }}</div></td>
                                </tr>
                                @for ($i = 0; $i < 3; $i++)
                                    <tr><td colspan="2" style="border:0; padding-top:18px;">{{ $i + 1 }}</td></tr>
                                    <tr><td colspan="2" style="border:0;"><div class="line">{{ $signatories[$i] ?? '' }}</div></td></tr>
                                @endfor
                            </table>
                        </td>
                        <td width="50%" style="border:0; vertical-align:top; padding:0;">
                            <table style="border:0;">
                                <tr><td colspan="2" style="border:0;">CLIENT NAME:</td></tr>
                                <tr><td colspan="2" style="border:0;"><div class="line">{{ $authenticationData['right_client_name'] ?? '' }}</div></td></tr>
                                <tr>
                                    <td width="45%" style="border:0;">CIF NO.:</td>
                                    <td width="55%" style="border:0;">CIF Dated:</td>
                                </tr>
                                <tr>
                                    <td style="border:0;"><div class="line">{{ $authenticationData['right_cif_no'] ?? '' }}</div></td>
                                    <td style="border:0;"><div class="line">{{ $authenticationData['right_cif_dated'] ?? '' }}</div></td>
                                </tr>
                                @for ($i = 3; $i < 6; $i++)
                                    <tr><td colspan="2" style="border:0; padding-top:18px;">{{ $i - 2 }}</td></tr>
                                    <tr><td colspan="2" style="border:0;"><div class="line">{{ $signatories[$i] ?? '' }}</div></td></tr>
                                @endfor
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="3" style="padding:0;">
                <table width="100%" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse; table-layout:fixed; border:1px solid #000;">
                    <tr>
                        <td width="60%" style="vertical-align:top; font-size:11px; line-height:1.18; text-align:justify; border-right:1px solid #000;">
                            By my/our signature(s) herein, I/we certify that the information and specimen signatures provided are true, correct, and duly authorized for use by JK&amp;C Inc. The above-listed individual(s) are the authorized signatory/ies of the business entity or the individual client, and JK&amp;C Inc. may rely on these specimen signatures for verification, documentation, and official transactions. I/we undertake to notify JK&amp;C Inc. in writing of any change to the authorized signatory/ies or their authority. In the absence of a Board Resolution, Secretary's Certificate, or Special Power of Attorney (SPA), the signature(s) appearing herein shall be presumed to be the true and rightful authorized signatory/ies of the business entity or individual client, unless otherwise notified in writing.
                        </td>
                        <td width="40%" style="vertical-align:top; padding:0;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="4" style="border-collapse:collapse; table-layout:fixed;">
                                <tr>
                                    <td style="border:0; text-align:center; font-weight:bold; line-height:1.2; padding-top:4px;">
                                        AUTHENTICATED BY CORPORATE SECRETARY / AUTHORIZED REPRESENTATIVE
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border:0; padding-top:8px;">
                                        Board Resolution / Secretary's Certificate / Special Power of Attorney (SPA) No.
                                        <div class="line">{{ $authenticationData['board_resolution_spa_no'] ?? '' }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border:0; padding-top:6px;">
                                        Board Resolution / Secretary's Certificate / Special Power of Attorney (SPA) Date
                                        <div class="line">{{ $authenticationData['board_resolution_spa_date'] ?? '' }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border:0; text-align:center; padding-top:8px;">
                                        <div style="margin:0 auto 4px auto; width:72%; border-bottom:1px solid #000; min-height:14px;">{{ $authenticationData['signature_over_printed_name'] ?? '' }}</div>
                                        Signature over Printed Name
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border:0; text-align:center; padding-top:2px;">
                                        <div style="margin:0 auto 4px auto; width:72%; border-bottom:1px solid #000; min-height:14px;">{{ $authenticationData['authorized_signatory_date'] ?? '' }}</div>
                                        Date
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="4" style="border-collapse:collapse; table-layout:fixed;">
                                <tr>
                                    <td width="68%" style="border:0; text-align:center; vertical-align:bottom; padding-top:10px;">
                                        <div style="margin:0 auto 4px auto; width:70%; border-bottom:1px solid #000; min-height:14px;">{{ $authenticationData['authorized_signatory_signature'] ?? '' }}</div>
                                        Authorized Signatory's Signature over Printed Name
                                    </td>
                                    <td width="32%" style="border:0; text-align:center; vertical-align:bottom; padding-top:10px;">
                                        <div style="margin:0 auto 4px auto; width:78%; border-bottom:1px solid #000; min-height:14px;">{{ $authenticationData['authorized_signatory_date'] ?? '' }}</div>
                                        Date
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="padding:0; border-left:1px solid #000; border-top:1px solid #000;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align:center; font-weight:bold; padding-top:3px; padding-bottom:3px; border-top:1px solid #000; border-bottom:1px solid #000;">FOR JKNC USE ONLY</td>
                    </tr>
                    <tr>
                        <td width="36%" style="vertical-align:top; border-right:1px solid #000;">
                            <div style="font-weight:bold;">PROCESSING INSTRUCTION (FOR JK&amp;C USE ONLY)</div>
                            <div style="min-height:68px; line-height:1.15;">{{ $authenticationData['processing_instruction'] ?? '' }}</div>
                        </td>
                        <td width="64%" style="vertical-align:top;">
                            <div style="font-weight:bold;">REMARKS:</div>
                            <div style="min-height:68px; line-height:1.15;">{{ $data->remarks ?? '' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding:0; border-top:1px solid #000; border-bottom:1px solid #000;">
                            <table width="100%" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse; table-layout:fixed; border-left:0; border-right:0; border-bottom:1px solid #000;">
                                <tr>
                                    <td width="25%" style="text-align:center; border-top:0; border-bottom:0; border-left:0; border-right:1px solid #000;">SALES &amp; MARKETING</td>
                                    <td width="25%" style="text-align:center; border-top:0; border-bottom:0; border-left:0; border-right:1px solid #000;">PROCESSED BY / DATE</td>
                                    <td width="25%" style="text-align:center; border-top:0; border-bottom:0; border-left:0; border-right:1px solid #000;">FINANCE</td>
                                    <td width="25%" style="text-align:center; border-top:0; border-bottom:0; border-left:0; border-right:0;">SCANNED BY / DATE</td>
                                </tr>
                                <tr>
                                    <td style="text-align:center; vertical-align:bottom; padding:10px 4px 6px 4px; border-top:0; border-bottom:1px solid #000; border-left:0; border-right:1px solid #000;">
                                        <div style="margin:0 auto 4px auto; width:68%; border-bottom:1px solid #000;"></div>
                                        {{ $authenticationData['sales_marketing'] ?? '' }}<br>
                                        Signature over Printed Name
                                    </td>
                                    <td style="text-align:center; vertical-align:bottom; padding:10px 4px 6px 4px; border-top:0; border-bottom:1px solid #000; border-left:0; border-right:1px solid #000;">
                                        <div style="margin:0 auto 4px auto; width:72%; border-bottom:1px solid #000;"></div>
                                        {{ $authenticationData['processed_by'] ?? '' }}<br>
                                        {{ $authenticationData['processed_date'] ?? '' }}<br>
                                        Signature over Printed Name
                                    </td>
                                    <td style="text-align:center; vertical-align:bottom; padding:10px 4px 6px 4px; border-top:0; border-bottom:1px solid #000; border-left:0; border-right:1px solid #000;">
                                        <div style="margin:0 auto 4px auto; width:68%; border-bottom:1px solid #000;"></div>
                                        {{ $authenticationData['finance'] ?? '' }}<br>
                                        Signature over Printed Name
                                    </td>
                                    <td style="text-align:center; vertical-align:bottom; padding:10px 4px 6px 4px; border-top:0; border-bottom:1px solid #000; border-left:0; border-right:0;">
                                        <div style="margin:0 auto 4px auto; width:68%; border-bottom:1px solid #000;"></div>
                                        {{ $authenticationData['scanned_by'] ?? '' }}<br>
                                        {{ $authenticationData['scanned_date'] ?? '' }}<br>
                                        Signature over Printed Name
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="text-align:center; font-style:italic; vertical-align:bottom; padding:18px 8px 6px 8px; border-top:0; border-bottom:0; border-left:0; border-right:1px solid #000;">
                                        Record Custodian ( Name and Signature)
                                    </td>
                                    <td style="padding:0; border-top:0; border-bottom:0; border-left:0; border-right:1px solid #000;">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse; table-layout:fixed;">
                                            <tr>
                                                <td style="border:0; border-bottom:1px solid #000; padding:6px 6px 4px 6px;">Date Recorded:</td>
                                            </tr>
                                            <tr>
                                                <td style="border:0; padding:6px 6px 4px 6px;">Date Signed :</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="padding:0; border-top:0; border-bottom:0; border-left:0; border-right:0;">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse; table-layout:fixed;">
                                            <tr>
                                                <td style="border:0; border-bottom:1px solid #000; padding:6px 6px 4px 6px;">{{ $authenticationData['processed_date'] ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="border:0; padding:6px 6px 4px 6px;">{{ $authenticationData['scanned_date'] ?? '' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</div>

    @if (!empty($autoPrint))
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.printA4FitPage();
                }, 150);
            });
        </script>
    @else
        <script>
            const params = new URLSearchParams(window.location.search);
            if (params.get('autoprint') === '1') {
                window.addEventListener('load', function () {
                    window.setTimeout(function () {
                        window.printA4FitPage();
                    }, 150);
                });
            }
        </script>
    @endif
</div>
</body>
</html>
