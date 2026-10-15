<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductImportController extends Controller
{
    /**
     * Ipakita ang form para sa pag-import ng mga produkto.
     */
    public function showImportForm()
    {
        return view('products.import');
    }

    /**
     * I-process at i-save ang in-upload na file (CSV/TXT).
     */
    public function storeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        if (($handle = fopen($path, 'r')) !== FALSE) {
            $isHeader = true; // Flag para laktawan ang unang row (header)

            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // Laktawan ang header row
                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }

                // Siguraduhing may laman ang row bago i-save
                if (!empty($row[0]) || !empty($row[1])) {
                    $sku = trim($row[0] ?? '');
                    $name = trim($row[1] ?? '');
                    
                    if (!empty($sku) && !empty($name)) {
                        Product::updateOrCreate(
                            ['sku' => $sku], // Gamitin ang SKU para maiwasan ang duplicate at error
                            [
                                'name'        => $name,
                                'description' => trim($row[2] ?? null),
                                'price'       => is_numeric($row[3] ?? null) ? $row[3] : 0,
                            ]
                        );
                    }
                }
            }
            fclose($handle);
        }

        return redirect()->route('products.index')->with('success', 'Products imported and updated successfully!');
    }
}