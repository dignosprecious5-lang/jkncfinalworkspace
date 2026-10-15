<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ORDO Products Dashboard</title>
    <style>
        @page { margin: 22px 20px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 8px; }
        h1 { font-size: 18px; margin: 0; color: #0f172a; }
        h2 { font-size: 11px; margin: 10px 0 5px; color: #0f172a; }
        .subtitle { color: #64748b; margin: 4px 0 9px; font-size: 8px; }
        table { border-collapse: collapse; }
        .insights { width: 100%; margin-bottom: 8px; }
        .insights td { width: 33.33%; padding: 6px; }
        .insight-card { border: 1px solid #dbe3ee; background: #fff; padding: 7px; height: 39px; }
        .label { color: #64748b; text-transform: uppercase; font-size: 6px; }
        .name { color: #0f172a; font-size: 9px; font-weight: bold; margin-top: 4px; }
        .value { color: #334155; margin-top: 3px; font-size: 7px; }
        .counts { width: 100%; margin-bottom: 8px; }
        .counts td { width: 20%; background: #f8fafc; border: 1px solid #dbe3ee; padding: 6px; }
        .count-label { color: #64748b; text-transform: uppercase; font-size: 6px; }
        .count-value { color: #0f172a; font-size: 13px; font-weight: bold; margin-top: 3px; }
        .filters { font-size: 6.5px; color: #475569; margin: 6px 0 9px; }
        .filters strong { color: #0f172a; }
        .products { width: 100%; table-layout: fixed; }
        .products th { background: #f1f5f9; color: #475569; text-align: left; text-transform: uppercase; font-size: 6px; padding: 6px 4px; border: 1px solid #dbe3ee; }
        .products td { padding: 5px 4px; border: 1px solid #dbe3ee; vertical-align: top; word-wrap: break-word; }
        .products tbody tr:nth-child(even) { background: #f8fafc; }
        .product-name { font-weight: bold; color: #0f172a; }
        .code { color: #64748b; font-size: 6px; margin-top: 2px; }
        .status { font-weight: bold; text-transform: uppercase; font-size: 6px; }
        .empty { text-align: center; color: #64748b; padding: 16px !important; }
    </style>
</head>
<body>
    <h1>ORDO Products Dashboard</h1>
    <div class="subtitle">Generated {{ now()->format('F j, Y g:i A') }} · {{ count($products) }} matching products</div>

    <table class="insights">
        @foreach(array_chunk($insights, 3, true) as $row)
            <tr>
                @foreach($row as $label => $values)
                    <td><div class="insight-card"><div class="label">{{ $label }}</div><div class="name">{{ $values[0] }}</div><div class="value">{{ $values[1] }}</div></div></td>
                @endforeach
                @for($i = count($row); $i < 3; $i++)<td></td>@endfor
            </tr>
        @endforeach
    </table>

    <table class="counts">
        <tr>
            @foreach($counts as $status => $count)
                <td><div class="count-label">{{ str_replace('_', ' ', $status) }}</div><div class="count-value">{{ $count }}</div></td>
            @endforeach
        </tr>
    </table>

    <div class="filters"><strong>Dashboard filters:</strong>
        @foreach($filters as $label => $value)
            {{ $label }}: {{ $value !== '' ? $value : 'All' }}@if(!$loop->last) &nbsp; · &nbsp; @endif
        @endforeach
    </div>

    <h2>Products</h2>
    <table class="products">
        <thead><tr>
            <th style="width:19%">Product Name &amp; Code</th>
            <th style="width:12%">Product Type</th>
            <th style="width:14%">Category</th>
            <th style="width:12%">Inventory Stock</th>
            <th style="width:12%">Pricing Type</th>
            <th style="width:10%">Price</th>
            <th style="width:12%">Tax Treatment</th>
            <th style="width:9%">Status</th>
        </tr></thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td><div class="product-name">{{ $product['Product Name'] }}</div><div class="code">Code: {{ $product['Code'] }}</div></td>
                    <td>{{ $product['Product Type'] }}</td>
                    <td>{{ $product['Category'] }}</td>
                    <td>{{ $product['Inventory Stock'] }}</td>
                    <td>{{ $product['Pricing Type'] }}</td>
                    <td>₱{{ $product['Price'] }}</td>
                    <td>{{ $product['Tax Treatment'] }}</td>
                    <td class="status">{{ $product['Status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No products match the selected dashboard filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
