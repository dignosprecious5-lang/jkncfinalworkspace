<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Performance Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 5px 0;
        }
        .header p {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }
        .summary-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px;
            width: 33.33%;
            vertical-align: top;
        }
        .summary-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 6px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        th.right, td.right {
            text-align: right;
        }
        th.center, td.center {
            text-align: center;
        }
        td {
            padding: 8px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
            color: #334155;
        }
        .text-muted {
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <h1>Service Performance Report</h1>
        <p>System-generated service performance based on connected service and engagement records.</p>
    </div>

    <!-- SUMMARY CARDS -->
    <table class="summary-grid">
        <tr>
            <td class="summary-card" style="border-right: 5px solid #ffffff;">
                <div class="summary-title">Total Services</div>
                <div class="summary-value">{{ number_format($totalServices) }}</div>
            </td>
            <td class="summary-card" style="border-right: 5px solid #ffffff;">
                <div class="summary-title">Active Services</div>
                <div class="summary-value">{{ number_format($activeServices) }}</div>
            </td>
            <td class="summary-card">
                <div class="summary-title">Engagements</div>
                <div class="summary-value">{{ number_format($totalEngagements) }}</div>
            </td>
        </tr>
    </table>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th>Service</th>
                <th>Category</th>
                <th>Engagement</th>
                <th>Frequency</th>
                <th class="right">Standard Price</th>
                <th class="right">Unit / Rate</th>
                <th class="right">Expected Hours</th>
                <th class="center">Engagements</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $service)
                @php
                    $engagementType = strtolower(trim($service->engagement_behavior ?? ''));
                    $engagementLabel = match($engagementType) {
                        'project' => 'Project',
                        'regular' => 'Regular',
                        'hybrid', 'both' => 'Hybrid',
                        default => '—',
                    };
                    $serviceStatus = strtolower(trim($service->status ?? ''));
                    $standardPrice = $service->report_standard_price;
                    $unitRate = $service->report_unit_rate ?? $service->activeVersion?->unit_rate ?? null;
                    $expectedHours = $service->report_expected_hours;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $service->name }}</strong><br>
                        <span class="text-muted" style="font-size: 9px;">{{ $service->service_code ?? 'SVC-' . sprintf('%04d', $service->id) }}</span>
                    </td>
                    <td>{{ $service->category ?: '—' }}</td>
                    <td>{{ $engagementLabel }}</td>
                    <td>{{ $service->report_reporting_frequency ?: '—' }}</td>
                    <td class="right">
                        @if($standardPrice !== null)
                            Php {{ number_format((float) $standardPrice, 2) }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="right">
                        @if($unitRate !== null && $unitRate > 0)
                            Php {{ number_format((float) $unitRate, 2) }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="right">
                        @if($expectedHours !== null)
                            {{ number_format((float) $expectedHours, 1) }} hrs
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="center">
                        {{ number_format($service->report_engagements ?? 0) }}
                    </td>
                    <td>
                        {{ ucfirst(str_replace('_', ' ', $serviceStatus)) ?: '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center" style="padding: 20px;">
                        <span class="text-muted">No service records found.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
