<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Performance Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #334155; }
        h2 { text-align: left; color: #0f172a; font-size: 18px; margin-bottom: 4px; }
        .subtitle { font-size: 10px; color: #64748b; margin-bottom: 15px; }
        
        /* Metrics Cards Styling */
        .metrics-container { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .metric-card { border: 1px solid #cbd5e1; background-color: #f8fafc; padding: 10px; width: 25%; vertical-align: top; }
        .metric-title { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 4px; }
        .metric-value { font-size: 18px; font-weight: bold; color: #0f172a; }
        .metric-value span { font-size: 11px; font-weight: normal; color: #64748b; }
        .metric-value.emerald { color: #059669; }
        .metric-value.slate { color: #475569; }

        /* Table Styling */
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; vertical-align: middle; }
        table.data-table th { background-color: #f8fafc; color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
        .product-name { font-weight: bold; color: #0f172a; }
        .product-code { font-size: 10px; color: #94a3b8; margin-top: 2px; }
        .price { font-family: monospace; font-weight: bold; color: #0f172a; }
        .stock { font-weight: bold; color: #059669; }
        .badge { display: inline-block; padding: 2px 8px; background-color: #f1f5f9; color: #334155; border-radius: 9999px; font-size: 9px; font-weight: bold; text-transform: uppercase; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <h2>Product Performance Report</h2>
    <div class="subtitle">System-generated product performance based on catalog configuration and inventory records.</div>
    
    <!-- Metrics Cards Section -->
    <table class="metrics-container">
        <tr>
            <td class="metric-card" style="border-right: none;">
                <span class="metric-title">Total Products</span>
                <div class="metric-value">{{ $totalProducts ?? 0 }} <span>products</span></div>
            </td>
            <td class="metric-card" style="border-right: none;">
                <span class="metric-title">Active Products</span>
                <div class="metric-value emerald">{{ $activeProducts ?? 0 }} <span>active</span></div>
            </td>
            <td class="metric-card" style="border-right: none;">
                <span class="metric-title">Archived Products</span>
                <div class="metric-value slate">{{ $archivedProducts ?? 0 }} <span>archived</span></div>
            </td>
            <td class="metric-card">
                <span class="metric-title">Draft Products</span>
                <div class="metric-value">{{ $draftProducts ?? 0 }} <span>draft</span></div>
            </td>
        </tr>
    </table>

    <!-- Data Table Section -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Pricing Type</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>
                    <div class="product-name">{{ $product->name }}</div>
                    <div class="product-code">Code: {{ $product->sku ?? 'PRD-' . sprintf('%04d', $product->id) }}</div>
                </td>
                <td>{{ $product->category ?? '-' }}</td>
                <td>{{ $product->pricing_type ?? '-' }}</td>
                <td class="price">Php {{ number_format($product->price ?? 0, 2) }}</td>
                <td class="stock">{{ $product->inventory_stock ?? 0 }} Units</td>
                <td>
                    <span class="badge">{{ ucfirst($product->status ?? 'Draft') }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>