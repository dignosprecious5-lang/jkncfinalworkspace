<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductVersionController extends Controller
{
    /**
     * Show version history for a Product.
     */
    public function index(Product $product)
    {
        $versions = $product->versions()
            ->orderByDesc('id')
            ->get();

        return view('products.versions.index', compact(
            'product',
            'versions'
        ));
    }

    /**
     * Create a new version from the current Product data.
     */
    public function store(Request $request, Product $product)
    {
        return DB::transaction(function () use ($product) {

            // Get the latest version number.
            $latestVersion = $product->versions()
                ->orderByDesc('id')
                ->first();

            $nextVersionNumber = $this->nextVersionNumber(
                $latestVersion?->version_number
            );

            // Make all previous versions inactive.
            $product->versions()->update([
                'is_active' => false,
            ]);

            // Create the new Product snapshot.
            $version = ProductVersion::create([
                'product_id' => $product->id,

                // Basic Product Information
                'name' => $product->name,
                'sku' => $product->sku,
                'service_area' => $product->service_area,
                'category' => $product->category,
                'pricing_type' => $product->pricing_type,
                'tax_treatment' => $product->tax_treatment,

                // Version Control
                'version_number' => $nextVersionNumber,
                'is_active' => true,
                'status' => 'draft',

                // Ownership
                'created_by' => auth()->id(),

                // Product Overview
                'short_name' => $product->short_name,
                'expected_turnaround' => $product->expected_turnaround,
                'effective_date' => $product->effective_date,
                'description' => $product->description,
                'internal_description' => $product->internal_description,
                'client_description' => $product->client_description,
                'purpose' => $product->purpose,
                'when_to_use' => $product->when_to_use,
                'what_product_is_not' => $product->what_product_is_not,

                // Product Catalog
                'scope_of_work' => $product->scope_of_work,
                'deliverables' => $product->deliverables,
                'client_responsibilities' => $product->client_responsibilities,
                'exclusions' => $product->exclusions,

                // Inventory
                'inventory_type' => $product->inventory_type,
                'inventory_stock' => $product->inventory_stock,
                'unit_measure' => $product->unit_measure,
                'stock_tracking' => $product->stock_tracking,
                'reorder_level' => $product->reorder_level,
                'minimum_stock' => $product->minimum_stock,
                'maximum_stock' => $product->maximum_stock,
                'warehouse_location' => $product->warehouse_location,
                'storage_location' => $product->storage_location,
                'stock_status' => $product->stock_status,
            ]);

            AuditLog::log(
                'created',
                'Product Version',
                "Created {$nextVersionNumber} for Product #{$product->id}."
            );

            return redirect()
                ->route('products.workspace', [
                    'id' => $product->id,
                    'tab' => 1,
                    'mode' => 'edit',
                ])
                ->with(
                    'success',
                    "Product version {$nextVersionNumber} created successfully."
                );
        });
    }

    /**
     * Activate an existing version.
     */
    public function activate(ProductVersion $productVersion)
    {
        return DB::transaction(function () use ($productVersion) {

            ProductVersion::where(
                'product_id',
                $productVersion->product_id
            )->update([
                'is_active' => false,
            ]);

            $productVersion->update([
                'is_active' => true,
                'status' => 'published',
            ]);

            AuditLog::log(
                'activated',
                'Product Version',
                "Activated {$productVersion->version_number} for Product #{$productVersion->product_id}."
            );

            return back()->with(
                'success',
                "Product version {$productVersion->version_number} is now active."
            );
        });
    }

    /**
     * Update version status.
     */
    public function updateStatus(
        Request $request,
        ProductVersion $productVersion
    ) {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:draft,published,archived',
            ],
        ]);

        $productVersion->update([
            'status' => $validated['status'],
        ]);

        AuditLog::log(
            'status_changed',
            'Product Version',
            "Changed {$productVersion->version_number} status to {$validated['status']}."
        );

        return back()->with(
            'success',
            "Product version status updated successfully."
        );
    }

    /**
     * Generate the next version number.
     */
    private function nextVersionNumber(?string $currentVersion): string
    {
        if (!$currentVersion) {
            return 'V1.0';
        }

        $version = strtoupper(trim($currentVersion));

        if (preg_match('/^V(\d+)\.(\d+)$/', $version, $matches)) {
            $major = (int) $matches[1];
            $minor = (int) $matches[2];

            return 'V' . $major . '.' . ($minor + 1);
        }

        return 'V1.0';
    }
}