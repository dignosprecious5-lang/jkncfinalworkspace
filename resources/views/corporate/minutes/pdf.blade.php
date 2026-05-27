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
    $secretariatRows = $parseAttendanceRows($minute->secretariat ?? null, $minute->uploaded_by ? [
        ['name' => $minute->uploaded_by, 'position' => 'Secretariat / Minutes-Taker'],
    ] : []);
    $guestRows = $parseAttendanceRows($minute->guests ?? null);
    $jkCompanyName = 'JOHN KELLY & COMPANY';
    $jkCompanyAddress = '3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000';
    $meetingTitleLine = trim(($minute->type_of_meeting ?: 'Regular') . ' ' . ($minute->governing_body ?: 'Board of Directors') . ' Meeting');
    $meetingDateLine = optional($minute->date_of_meeting)->format('F d, Y') ?: '________________';
    $meetingTimeLine = $minute->time_started ? \Carbon\Carbon::parse($minute->time_started)->format('g:i A') : '________________';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $minutesDocumentTitle }}</title>
    <style>
        @page {
            size: A4;
            margin: 18mm 16mm 20mm;
        }

        body {
            font-family: Georgia, "Times New Roman", serif;
            color: #111827;
            font-size: 12.5pt;
            line-height: 1.75;
            margin: 0;
        }

        .page {
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .brand-main {
            font-size: 34pt;
            font-weight: 600;
            line-height: 0.95;
        }

        .brand-amp {
            color: #2563eb;
        }

        .company-name {
            font-size: 15pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .meta-line {
            margin-top: 4px;
            font-size: 11.5pt;
        }

        .title {
            margin-top: 30px;
            font-size: 14pt;
            font-weight: 700;
            text-align: center;
        }

        .subtitle {
            margin-top: 8px;
            text-align: center;
            font-size: 12pt;
        }

        .section {
            margin-top: 28px;
        }

        .attendance-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table td,
        .attendance-table th,
        .signature-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .attendance-table th {
            text-align: left;
            font-weight: 700;
        }

        .attendance-heading {
            margin-top: 12px;
            font-weight: 700;
        }

        .minutes-body {
            margin-top: 16px;
            min-height: 280px;
        }

        .minutes-body p {
            margin: 0 0 14px;
        }

        .minutes-body ol,
        .minutes-body ul {
            margin: 0 0 14px 28px;
        }

        .line {
            border-bottom: 1px solid #94a3b8;
            min-height: 18px;
        }

        .signature-label {
            padding-top: 6px;
            text-align: center;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="center">
            <div class="brand-main">John Kelly</div>
            <div class="brand-main"><span class="brand-amp">&amp;</span> Company</div>
            <div class="meta-line" style="margin-top:14px;">{{ $jkCompanyAddress }}</div>

            <div class="title" style="margin-top:28px;">MINUTES OF THE</div>
            <div class="subtitle">{{ $meetingTitleLine }}</div>
            <div class="subtitle">of</div>
            <div class="subtitle" style="text-transform:uppercase;">{{ $jkCompanyName }}</div>

            <div class="subtitle" style="margin-top:24px;">held at</div>
            <div class="subtitle">{{ $minute->location ?: '________________' }}</div>

            @if($minute->meeting_mode || $minute->call_link)
                <div class="subtitle">
                    and Mode of Meeting: {{ $minute->meeting_mode ?: '________________' }}
                    @if($minute->call_link)
                        <br>Meeting Link: <span style="color:#2563eb;text-decoration:underline;">{{ $minute->call_link }}</span>
                    @endif
                </div>
            @endif

            <div class="subtitle" style="margin-top:24px;">on</div>
            <div class="subtitle">{{ $meetingDateLine }}</div>
            <div class="subtitle">at {{ $meetingTimeLine }}</div>
        </div>

        <div class="section">
            <div class="attendance-heading">Directors Present</div>
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

            <div class="attendance-heading">Directors Absent</div>
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
                            <td>________________</td>
                            <td>________________</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="attendance-heading" style="margin-top:22px;">Secretariat</div>
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
                            <td>________________</td>
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
                            <td>________________</td>
                            <td>________________</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <div style="font-weight:700;">Minutes Proper:</div>
            <div class="minutes-body">{!! $minute->recording_notes ?: '<p>________________</p>' !!}</div>
        </div>

        <div class="section" style="margin-top:70px;">
            <div style="font-weight:700;">Prepared by:</div>
            <div style="margin-top:22px;font-weight:700;text-transform:uppercase;">{{ $minute->secretary ?: '________________' }}</div>
            <div style="font-weight:700;">Corporate Secretary</div>

            <div style="margin-top:70px;font-weight:700;">Attested by:</div>
            <div style="margin-top:22px;font-weight:700;text-transform:uppercase;">{{ $minute->chairman ?: '________________' }}</div>
            <div style="font-weight:700;">Chairman of the Meeting</div>
        </div>
    </div>
</body>
</html>
