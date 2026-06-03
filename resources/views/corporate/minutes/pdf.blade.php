@php
    $parseAttendanceRows = function ($value, array $fallback = []) {
        $rows = [];

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $rows = $decoded;
            } else {
                $rows = collect(preg_split('/\r\n|\r|\n/', $value))
                    ->map(function ($line) {
                        $parts = array_map('trim', explode('|', $line, 2));

                        return [
                            'name' => $parts[0] ?? '',
                            'position' => $parts[1] ?? '',
                        ];
                    })
                    ->filter(fn ($row) => ($row['name'] ?? '') !== '' || ($row['position'] ?? '') !== '')
                    ->values()
                    ->all();
            }
        } elseif (is_array($value)) {
            $rows = $value;
        }

        $rows = collect($rows)
            ->map(function ($row) {
                return [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'position' => trim((string) ($row['position'] ?? $row['role'] ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['name'] !== '' || $row['position'] !== '')
            ->values()
            ->all();

        return !empty($rows) ? $rows : $fallback;
    };

    $directorsPresentRows = $parseAttendanceRows($minute->directors_present ?? null, array_values(array_filter([
        $minute->chairman ? ['name' => $minute->chairman, 'position' => 'President/Chairman'] : null,
        $minute->secretary ? ['name' => $minute->secretary, 'position' => 'Corporate Secretary'] : null,
    ])));
    $directorsAbsentRows = $parseAttendanceRows($minute->directors_absent ?? null);
    $secretariatRows = $parseAttendanceRows($minute->secretariat ?? null, $minute->secretary ? [
        ['name' => $minute->secretary, 'position' => 'Corporate Secretary'],
    ] : []);
    $guestRows = $parseAttendanceRows($minute->guests ?? null);
    $governingBodyLower = strtolower((string) ($minute->governing_body ?? ''));
    if (str_contains($governingBodyLower, 'joint')) {
        $attendanceBaseLabel = 'Directors and Stockholders';
    } elseif (str_contains($governingBodyLower, 'stockholder') && !str_contains($governingBodyLower, 'board')) {
        $attendanceBaseLabel = 'Stockholders';
    } else {
        $attendanceBaseLabel = 'Directors';
    }
    $presentHeading = $attendanceBaseLabel . ' Present';
    $absentHeading = $attendanceBaseLabel . ' Absent';
    $jkCompanyName = strtoupper((data_get($corporateContext ?? [], 'company_name') ?: data_get($corporateContext ?? [], 'companyName')) ?: 'JOHN KELLY & COMPANY');
    $jkCompanyAddress = (data_get($corporateContext ?? [], 'company_address') ?: data_get($corporateContext ?? [], 'companyAddress')) ?: '3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000';

    $gisLogoPath = (data_get($corporateContext ?? [], 'logo_path') ?: data_get($corporateContext ?? [], 'logoPath'));
    $gisLogoUrl = null;
    $gisLogoDataUri = null;
    if ($gisLogoPath) {
        $normalizedLogoPath = preg_replace('#^/?storage/#', '', (string) $gisLogoPath);
        try { $gisLogoUrl = route('uploads.show', ['path' => $normalizedLogoPath]); } catch (\Throwable $e) { $gisLogoUrl = asset('storage/' . $normalizedLogoPath); }
        $absoluteLogoPath = storage_path('app/public/' . $normalizedLogoPath);
        if (is_file($absoluteLogoPath)) {
            $mime = function_exists('mime_content_type') ? (mime_content_type($absoluteLogoPath) ?: 'image/png') : 'image/png';
            $gisLogoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absoluteLogoPath));
        }
    }

    $meetingTitleLine = trim(($minute->type_of_meeting ?: 'Regular') . ' ' . ($minute->governing_body ?: 'Board of Directors') . ' Meeting');
    $meetingDateLine = optional($minute->date_of_meeting)->format('F d, Y') ?: '________________';
    $meetingTimeLine = $minute->time_started ? \Carbon\Carbon::parse($minute->time_started)->format('g:i A') : '________________';

    $compactMinutesHtml = function ($html) {
        $html = trim((string) $html);

        do {
            $previous = $html;
            $html = preg_replace('/(?:\s|&nbsp;|<br\s*\/?>(?:\s|&nbsp;)*|<p[^>]*>(?:\s|&nbsp;|<br\s*\/?\s*)*<\/p>|<div[^>]*>(?:\s|&nbsp;|<br\s*\/?\s*)*<\/div>)+$/i', '', $html);
            $html = trim((string) $html);
        } while ($html !== $previous);

        return $html !== '' ? $html : '<p>________________</p>';
    };

    $minutesProperHtml = $compactMinutesHtml($minute->recording_notes ?? '');
    $pdfFooterLeft = trim(($minute->minutes_ref ?: 'MINUTES') . ' - ' . $jkCompanyName);

    $dompdfFooterLeft = trim('MINUTES ' . ($minute->minutes_ref ?: '') . ' - ' . $jkCompanyName);
    $dompdfFooterLeft = html_entity_decode((string) $dompdfFooterLeft, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $dompdfFooterLeft = preg_replace('/\s+/', ' ', $dompdfFooterLeft);
    if (mb_strlen($dompdfFooterLeft) > 90) {
        $dompdfFooterLeft = mb_substr($dompdfFooterLeft, 0, 87) . '...';
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $minutesDocumentTitle }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 16mm 22mm 24mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Georgia, "Times New Roman", serif;
            color: #111827;
            font-size: 11.5pt;
            line-height: 1.28;
            margin: 0;
        }

        .page {
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .gis-logo { max-height: 60px; max-width: 220px; object-fit: contain; margin: 0 auto 7px; display: block; }

        .brand-main {
            font-size: 31pt;
            font-weight: 600;
            line-height: 0.95;
        }

        .brand-amp {
            color: #2563eb;
        }

        .meta-line {
            margin-top: 8px;
            font-size: 10.5pt;
        }

        .title {
            margin-top: 15px;
            font-size: 13pt;
            font-weight: 700;
            text-align: center;
        }

        .subtitle {
            margin-top: 4px;
            text-align: center;
            font-size: 11pt;
        }

        .meeting-block {
            margin-top: 13px;
        }

        .section {
            margin-top: 12px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .attendance-table td,
        .attendance-table th {
            padding: 2.8px 0;
            vertical-align: top;
        }

        .attendance-table th {
            text-align: left;
            font-weight: 700;
        }

        .attendance-heading {
            margin-top: 10px;
            font-weight: 700;
        }

        .minutes-body {
            margin-top: 5px;
            min-height: 0;
            line-height: 1.32;
        }

        .minutes-body p,
        .minutes-body div {
            margin: 0 0 5px;
        }

        .minutes-body ol,
        .minutes-body ul {
            margin: 0 0 7px 24px;
            padding: 0;
        }

        .signature-section {
            margin-top: 14px;
            line-height: 1.32;
        }

        .signature-name {
            margin-top: 3px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .attested-block {
            margin-top: 13px;
        }

        .avoid-break {
            page-break-inside: avoid;
        }
</style>
</head>
<body>
<div class="page">
        <div class="center">
            @if($gisLogoDataUri)
                <img src="{{ $gisLogoDataUri }}" class="gis-logo" alt="Company Logo">
            @else
                <div class="brand-main">{{ $jkCompanyName }}</div>
            @endif
            <div class="meta-line">{{ $jkCompanyAddress }}</div>

            <div class="title">MINUTES OF THE</div>
            <div class="subtitle">{{ $meetingTitleLine }}</div>
            <div class="subtitle">of</div>
            <div class="subtitle" style="text-transform:uppercase;">{{ $jkCompanyName }}</div>

            <div class="subtitle meeting-block">held at</div>
            <div class="subtitle">{{ $minute->location ?: '________________' }}</div>

            @if($minute->meeting_mode)
                <div class="subtitle">and Mode of Meeting: {{ $minute->meeting_mode }}</div>
            @endif
            @if($minute->call_link && !str_contains(strtolower((string) $minute->meeting_mode), 'physical'))
                <div class="subtitle">Meeting Link:<br><span style="color:#2563eb;text-decoration:underline;word-break:break-all;">{{ $minute->call_link }}</span></div>
            @endif

            <div class="subtitle meeting-block">on</div>
            <div class="subtitle">{{ $meetingDateLine }}</div>
            <div class="subtitle">at {{ $meetingTimeLine }}</div>
        </div>

        <div class="section">
            <div class="attendance-heading">{{ $presentHeading }}</div>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th style="width:45%;">Name</th>
                        <th>Position</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($directorsPresentRows as $row)
                        <tr>
                            <td>{{ $row['name'] ?: '________________' }}</td>
                            <td>{{ $row['position'] ?: '________________' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>________________</td>
                            <td>________________</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="attendance-heading">{{ $absentHeading }}</div>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th style="width:45%;">Name</th>
                        <th>Position</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($directorsAbsentRows as $row)
                        <tr>
                            <td>{{ $row['name'] ?: '________________' }}</td>
                            <td>{{ $row['position'] ?: '________________' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="font-weight:700;">NO ABSENT</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="attendance-heading">Corporate Secretary</div>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th style="width:45%;">Name</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($secretariatRows as $row)
                        <tr>
                            <td>{{ $row['name'] ?: '________________' }}</td>
                            <td>{{ $row['position'] ?: '________________' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>________________</td>
                            <td>Corporate Secretary</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="attendance-heading">Guests</div>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th style="width:45%;">Name</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guestRows as $row)
                        <tr>
                            <td>{{ $row['name'] ?: '________________' }}</td>
                            <td>{{ $row['position'] ?: '________________' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="font-weight:700;">NO GUESTS</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section avoid-break">
            <div style="font-weight:700;">Minutes Proper:</div>
            <div class="minutes-body">{!! $minutesProperHtml !!}</div>
        </div>

        <div class="signature-section avoid-break">
            <div style="font-weight:700;">Prepared by:</div>
            <div class="signature-name">{{ $minute->secretary ?: '________________' }}</div>
            <div style="font-weight:700;">Corporate Secretary</div>

            <div class="attested-block" style="font-weight:700;">Attested by:</div>
            <div class="signature-name">{{ $minute->chairman ?: '________________' }}</div>
            <div style="font-weight:700;">Chairman of the Meeting</div>
        </div>
    </div>


@php
    $dompdfFooterLeft = trim('MINUTES ' . ($minute->minutes_ref ?: '') . ' - ' . $jkCompanyName);
    $dompdfFooterLeft = preg_replace('/\s+/', ' ', (string) $dompdfFooterLeft);
    if (mb_strlen($dompdfFooterLeft) > 90) {
        $dompdfFooterLeft = mb_substr($dompdfFooterLeft, 0, 87) . '...';
    }
@endphp
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("Times-Roman", "normal");
        $boldFont = $fontMetrics->get_font("Times-Roman", "bold");
        $footerLeft = {!! var_export($dompdfFooterLeft, true) !!};

        $pdf->line(40, 800, 555, 800, [0, 0, 0], 0.4);
        $pdf->page_text(40, 808, $footerLeft, $font, 8, [0, 0, 0]);
        $pdf->page_text(500, 808, "Page {PAGE_NUM} of {PAGE_COUNT}", $boldFont, 8, [0, 0, 0]);
    }
</script>

</body>
</html>
