<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductExportController extends Controller
{
    public function exportAll(Request $request)
    {
        $format = $request->query('format');

        if ($format === 'pdf') {
            // Kunin ang mga totoong produkto mula sa database
            $products = Product::all();

            // I-load ang view para sa PDF at ipasa ang data
            $pdf = Pdf::loadView('exports.products-pdf', compact('products'));

            // I-download ito bilang totoong PDF file
            return $pdf->download('products-report.pdf');
        }

        if ($format === 'csv') {
            // Code para sa CSV Download
            $filename = "products-report.csv";
            $products = Product::all();

            $handle = fopen('php://output', 'w');
            
            // Headers ng CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            // Ilagay ang column headers
            fputcsv($handle, ['ID', 'Product Name', 'Code', 'Status']);

            // Isulat ang mga produkto mula sa database
            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->id,
                    $product->name ?? '',
                    $product->code ?? '',
                    $product->status ?? ''
                ]);
            }

            fclose($handle);
            exit;
        }

        return "Invalid format specified.";
    }
}