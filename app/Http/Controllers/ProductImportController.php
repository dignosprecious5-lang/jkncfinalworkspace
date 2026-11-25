<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ProductImportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(function ($request, $next) {
            $user = $request->user();
            abort_unless(
                $user && ($user->isAdmin() || $user->isSuperAdmin() || ($user->hasPermission('access_product') && $user->hasPermission('create_sales_marketing'))),
                403
            );

            return $next($request);
        })];
    }

    public function showImportForm()
    {
        return view('products.import');
    }

    public function storeImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $created = 0;
        $updated = 0;
        $errors = [];

        DB::transaction(function () use ($path, &$created, &$updated, &$errors) {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                $errors[] = 'Unable to read uploaded CSV file.';
                return;
            }

            $rowNumber = 0;
            while (($row = fgetcsv($handle, 4096, ',')) !== false) {
                $rowNumber++;
                if ($rowNumber === 1) {
                    continue;
                }

                $sku = trim((string) ($row[0] ?? ''));
                $name = trim((string) ($row[1] ?? ''));
                $description = trim((string) ($row[2] ?? ''));
                $rawPrice = trim((string) ($row[3] ?? '0'));

                if ($sku === '' && $name === '') {
                    continue;
                }

                if ($sku === '' || $name === '') {
                    $errors[] = "Row {$rowNumber}: SKU and Name are required.";
                    continue;
                }

                if (strlen($sku) > 100 || strlen($name) > 255) {
                    $errors[] = "Row {$rowNumber}: SKU or Name exceeds maximum length.";
                    continue;
                }

                if ($rawPrice !== '' && (! is_numeric($rawPrice) || (float) $rawPrice < 0)) {
                    $errors[] = "Row {$rowNumber}: Price must be a non-negative number.";
                    continue;
                }

                $price = $rawPrice === '' ? 0.0 : (float) $rawPrice;
                $existing = Product::where('sku', $sku)->first();

                if ($existing) {
                    $existing->update([
                        'name'        => $name,
                        'description' => $description !== '' ? $description : $existing->description,
                        'price'       => $price,
                    ]);
                    $updated++;
                } else {
                    Product::create([
                        'sku'         => $sku,
                        'name'        => $name,
                        'description' => $description !== '' ? $description : null,
                        'price'       => $price,
                        'status'      => 'draft',
                    ]);
                    $created++;
                }
            }

            fclose($handle);
        });

        if ($created === 0 && $updated === 0 && $errors !== []) {
            return back()->withErrors(['file' => implode(' ', array_slice($errors, 0, 5))]);
        }

        $message = "Products imported successfully: {$created} created, {$updated} updated.";
        if ($errors !== []) {
            $message .= ' Skipped ' . count($errors) . ' invalid row(s).';
        }

        return redirect()->route('products.index')->with('success', $message);
    }
}
