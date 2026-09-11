<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Engagements Summary Report - ORDO System</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #334155;
            margin: 0;
            padding: 10px;
        }

        /* Header Section */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }

        .company-title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            font-family: Georgia, serif;
        }

        .company-subtitle {
            font-size: 11px;
            color: #2563eb;
            font-weight: bold;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            text-align: right;
            margin: 0;
            text-transform: uppercase;
        }

        .meta-text {
            font-size: 9px;
            color: #64748b;
            text-align: right;
            margin-top: 2px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }

        .data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* ID Column */
        .id-cell {
            font-family: monospace;
            font-weight: bold;
            color: #4f46e5;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-active {
            background-color: #d1fae5;
            color: #047857;
        }

        .badge-completed {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .badge-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #b45309;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td>
                <div class="company-title">John Kelly <span class="company-subtitle">& Company</span></div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">ORDO Management System</div>
            </td>
            <td>
                <div class="report-title">Operational Engagements Report</div>
                <div class="meta-text">Generated on: {{ date('F d, Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <!-- Main Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">ID</th>
                <th style="width: 35%;">Title / Service</th>
                <th style="width: 25%;">Client Name</th>
                <th style="width: 17%;">Type</th>
                <th style="width: 15%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($engagements as $eng)
                @php
                    $snapshot = !empty($eng->service_snapshot) 
                        ? json_decode($eng->service_snapshot, true) 
                        : [];

                    $title = $eng->title ?? $eng->service_name ?? $snapshot['service_name'] ?? 'Engagement #' . $eng->id;
                    $client = $eng->client_name ?? $eng->proposal_client ?? $eng->client ?? $snapshot['client_name'] ?? 'N/A';
                    $status = strtolower($eng->status ?? 'active');
                @endphp
                <tr>
                    <td class="id-cell">#{{ $eng->id }}</td>
                    <td>
                        <strong style="color: #0f172a;">{{ $title }}</strong>
                    </td>
                    <td>{{ $client }}</td>
                    <td>{{ $eng->type ?? 'Project' }}</td>
                    <td>
                        @if($status === 'active' || $status === 'approved' || $status === 'ongoing')
                            <span class="badge badge-active">{{ $eng->status ?? 'Active' }}</span>
                        @elseif($status === 'completed' || $status === 'complete')
                            <span class="badge badge-completed">{{ $eng->status ?? 'Completed' }}</span>
                        @elseif($status === 'cancelled' || $status === 'canceled' || $status === 'rejected')
                            <span class="badge badge-cancelled">{{ $eng->status ?? 'Cancelled' }}</span>
                        @else
                            <span class="badge badge-pending">{{ $eng->status ?? 'Pending' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">
                        No operational engagements found in the system.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer Page Indicator -->
    <div class="footer">
        Confidential — Internal System Report | John Kelly & Company
    </div>

</body>
</html>