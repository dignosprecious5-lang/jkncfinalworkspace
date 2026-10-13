<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportController extends Controller
{
    public function exportAll(Request $request)
    {
        $format = strtolower($request->query('format', ''));

        if ($format === 'csv') {
            return $this->exportCsv();
        }

        if ($format === 'pdf') {
            // Kunin ang lahat ng products mula sa database.
            $products = Product::query()
                ->orderBy('id')
                ->get();

            $totalProducts = $products->count();

            $activeProducts = $products->filter(
                fn ($product) => strtolower($product->status ?? '') === 'active'
            )->count();

            $archivedProducts = $products->filter(
                fn ($product) => strtolower($product->status ?? '') === 'archived'
            )->count();

            $draftProducts = $products->filter(
                fn ($product) => strtolower($product->status ?? '') === 'draft'
            )->count();

            $pdf = Pdf::loadView('exports.products-pdf', compact(
                'products',
                'totalProducts',
                'activeProducts',
                'archivedProducts',
                'draftProducts'
            ));

            return $pdf->download('products-report.pdf');
        }

        return response(
            'Invalid format specified. Use ?format=csv or ?format=pdf.',
            400
        );
    }

    private function exportCsv(): StreamedResponse
    {
        $filename = 'products-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM para mas maayos ang pagbukas sa Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            // Column headers.
            fputcsv($handle, [
                'ID',
                'Product Name',
                'Code',
                'Category',
                'Pricing Type',
                'Price',
                'Stock',
                'Status',
            ]);

            // I-export ang lahat ng records, hindi lang ang current page.
            Product::query()
                ->orderBy('id')
                ->chunk(500, function ($products) use ($handle) {
                    foreach ($products as $product) {
                        fputcsv($handle, [
                            $product->id,
                            $product->name ?? '',
                            $product->sku ?? $product->code ?? '',
                            $product->category ?? '',
                            $product->pricing_type ?? '',
                            $product->price ?? '',
                            $product->inventory_stock ?? 0,
                            $product->status ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
