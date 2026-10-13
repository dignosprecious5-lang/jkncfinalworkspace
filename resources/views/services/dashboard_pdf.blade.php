<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ORDO Services Dashboard</title>
    <style>
        @page { margin: 24px 22px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 8px; }
        h1 { font-size: 18px; margin: 0; color: #0f172a; }
        .subtitle { color: #64748b; margin: 4px 0 12px; font-size: 8px; }
        .summary { border-collapse: separate; border-spacing: 4px; width: 100%; margin: 0 -4px 9px; }
        .summary td { background: #f8fafc; border: 1px solid #dbe3ee; padding: 7px; width: 25%; }
        .metric-label { color: #64748b; text-transform: uppercase; font-size: 7px; }
        .metric-value { font-size: 13px; font-weight: bold; margin-top: 3px; color: #0f172a; }
        .filters { margin: 5px 0 10px; color: #475569; font-size: 7px; }
        .filters strong { color: #0f172a; }
        h2 { margin: 7px 0 5px; font-size: 11px; color: #0f172a; }
        .services { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .services th { background: #f1f5f9; color: #475569; text-align: left; text-transform: uppercase; font-size: 6.5px; padding: 6px 4px; border: 1px solid #dbe3ee; }
        .services td { padding: 5px 4px; border: 1px solid #dbe3ee; vertical-align: top; word-wrap: break-word; }
        .services tbody tr:nth-child(even) { background: #f8fafc; }
        .name { font-weight: bold; color: #0f172a; }
        .code { color: #64748b; font-size: 6.5px; margin-top: 2px; }
        .status { font-weight: bold; text-transform: uppercase; font-size: 6px; }
        .empty { text-align: center; color: #64748b; padding: 16px !important; }
    </style>
</head>
<body>
    <h1>ORDO Services Dashboard</h1>
    <div class="subtitle">Generated {{ now()->format('F j, Y g:i A') }} · {{ count($services) }} matching services</div>

    <table class="summary">
        @foreach(array_chunk($summary, 4, true) as $row)
            <tr>
                @foreach($row as $label => $value)
                    <td><div class="metric-label">{{ $label }}</div><div class="metric-value">{{ $value }}</div></td>
                @endforeach
                @for($i = count($row); $i < 4; $i++)<td></td>@endfor
            </tr>
        @endforeach
    </table>

    <div class="filters"><strong>Dashboard filters:</strong>
        @foreach($filters as $label => $value)
            {{ $label }}: {{ $value !== '' ? $value : 'All' }}@if(!$loop->last) &nbsp; · &nbsp; @endif
        @endforeach
    </div>

    <h2>Services</h2>
    <table class="services">
        <thead><tr>
            <th style="width:18%">Service Name &amp; Code</th>
            <th style="width:13%">Category</th>
            <th style="width:10%">Company</th>
            <th style="width:8%">Engagement</th>
            <th style="width:10%">Standard Price</th>
            <th style="width:9%">Unit / Rate</th>
            <th style="width:11%">Tax Treatment</th>
            <th style="width:8%">Expected Hours</th>
            <th style="width:13%">Status</th>
        </tr></thead>
        <tbody>
            @forelse($services as $service)
                <tr>
                    <td><div class="name">{{ $service['Service Name'] }}</div><div class="code">Code: {{ $service['Code'] }}</div></td>
                    <td>{{ $service['Category'] }}</td>
                    <td>{{ $service['Company'] }}</td>
                    <td>{{ $service['Engagement'] }}</td>
                    <td>₱{{ $service['Standard Price'] }}</td>
                    <td>{{ $service['Unit / Rate'] === '—' ? '—' : '₱' . $service['Unit / Rate'] }}</td>
                    <td>{{ $service['Tax Treatment'] }}</td>
                    <td>{{ $service['Expected Hours'] }}</td>
                    <td class="status">{{ $service['Status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No services match the selected dashboard filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
