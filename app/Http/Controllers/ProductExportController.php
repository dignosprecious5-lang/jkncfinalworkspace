<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(function ($request, $next) {
            $user = $request->user();
            abort_unless(
                $user && ($user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('access_product')),
                403
            );

            return $next($request);
        })];
    }

    public function exportCsvRoute(Request $request)
    {
        return $this->exportAll($request->merge(['format' => 'csv']));
    }

    public function exportPdfRoute(Request $request)
    {
        return $this->exportAll($request->merge(['format' => 'pdf']));
    }

    public function exportAll(Request $request)
    {
        $format = strtolower((string) $request->input('format', $request->query('format', '')));

        if ($format === 'csv') {
            return $this->exportCsv();
        }

        if ($format === 'pdf') {
            $products = Product::query()
                ->orderBy('id')
                ->get();

            $totalProducts = $products->count();
            $activeProducts = $products->filter(fn ($p) => strtolower((string) ($p->status ?? '')) === 'active')->count();
            $archivedProducts = $products->filter(fn ($p) => strtolower((string) ($p->status ?? '')) === 'archived')->count();
            $draftProducts = $products->filter(fn ($p) => strtolower((string) ($p->status ?? '')) === 'draft')->count();

            $pdf = Pdf::loadView('exports.products-pdf', compact(
                'products',
                'totalProducts',
                'activeProducts',
                'archivedProducts',
                'draftProducts'
            ));

            return $pdf->download('products-report.pdf');
        }

        return response('Invalid format specified. Use ?format=csv or ?format=pdf.', 400);
    }

    private function exportCsv(): StreamedResponse
    {
        $filename = 'products-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

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

            Product::query()
                ->orderBy('id')
                ->chunk(500, function ($products) use ($handle) {
                    foreach ($products as $product) {
                        fputcsv($handle, [
                            $product->id,
                            self::sanitizeCsvCell($product->name ?? $product->product_name ?? ''),
                            self::sanitizeCsvCell($product->sku ?? $product->code ?? ''),
                            self::sanitizeCsvCell($product->category ?? ''),
                            self::sanitizeCsvCell($product->pricing_type ?? ''),
                            $product->price ?? '',
                            $product->inventory_stock ?? $product->stock_qty ?? 0,
                            self::sanitizeCsvCell($product->status ?? ''),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public static function sanitizeCsvCell(mixed $value): string
    {
        $str = (string) ($value ?? '');
        if ($str !== '' && preg_match('/^[=+\-@\t\r]/', $str)) {
            return "'" . $str;
        }

        return $str;
    }
}
