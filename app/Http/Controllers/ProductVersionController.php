<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ProductVersionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(function ($request, $next) {
            $user = $request->user();
            abort_unless(
                $user && ($user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('access_product')),
                403
            );

            $method = $request->route()?->getActionMethod();
            if ($method !== 'index') {
                abort_unless(
                    $user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('create_sales_marketing'),
                    403
                );
            }

            return $next($request);
        })];
    }

    public function index(Product $product)
    {
        $versions = $product->versions()
            ->orderByDesc('id')
            ->get();

        return view('products.versions.index', compact('product', 'versions'));
    }

    public function store(Request $request, Product $product)
    {
        return DB::transaction(function () use ($product) {
            $latestVersion = $product->versions()->orderByDesc('id')->first();
            $nextVersionNumber = $this->nextVersionNumber($latestVersion?->version_number);

            $product->versions()->update(['is_active' => false]);

            ProductVersion::create([
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'service_area' => $product->service_area,
                'category' => $product->category,
                'pricing_type' => $product->pricing_type,
                'tax_treatment' => $product->tax_treatment,
                'version_number' => $nextVersionNumber,
                'is_active' => true,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'short_name' => $product->short_name,
                'expected_turnaround' => $product->expected_turnaround,
                'effective_date' => $product->effective_date,
                'description' => $product->description,
                'internal_description' => $product->internal_description,
                'client_description' => $product->client_description,
                'purpose' => $product->purpose,
                'when_to_use' => $product->when_to_use,
                'what_product_is_not' => $product->what_product_is_not,
                'scope_of_work' => $product->scope_of_work,
                'deliverables' => $product->deliverables,
                'client_responsibilities' => $product->client_responsibilities,
                'exclusions' => $product->exclusions,
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

            if (class_exists(AuditLog::class) && method_exists(AuditLog::class, 'log')) {
                AuditLog::log('created', 'Product Version', "Created {$nextVersionNumber} for Product #{$product->id}.");
            }

            return redirect()
                ->route('products.workspace', ['id' => $product->id, 'tab' => 1, 'mode' => 'edit'])
                ->with('success', "Product version {$nextVersionNumber} created successfully.");
        });
    }

    public function activate(Request $request, ProductVersion $productVersion)
    {
        abort_unless($request->user()->canApprove(), 403, 'Only authorized approvers can activate and publish product versions.');

        return DB::transaction(function () use ($productVersion) {
            ProductVersion::where('product_id', $productVersion->product_id)
                ->update(['is_active' => false]);

            $productVersion->update([
                'is_active' => true,
                'status' => 'published',
            ]);

            if (class_exists(AuditLog::class) && method_exists(AuditLog::class, 'log')) {
                AuditLog::log('activated', 'Product Version', "Activated {$productVersion->version_number} for Product #{$productVersion->product_id}.");
            }

            return back()->with('success', "Product version {$productVersion->version_number} is now active.");
        });
    }

    public function updateStatus(Request $request, ProductVersion $productVersion)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:draft,published,archived'],
        ]);

        if ($validated['status'] === 'published') {
            abort_unless($request->user()->canApprove(), 403, 'Only authorized approvers can publish product versions.');
        }

        $productVersion->update(['status' => $validated['status']]);

        if (class_exists(AuditLog::class) && method_exists(AuditLog::class, 'log')) {
            AuditLog::log('status_changed', 'Product Version', "Changed {$productVersion->version_number} status to {$validated['status']}.");
        }

        return back()->with('success', 'Product version status updated successfully.');
    }

    private function nextVersionNumber(?string $currentVersion): string
    {
        if (! $currentVersion) {
            return 'V1.0';
        }

        $version = strtoupper(trim($currentVersion));

        if (preg_match('/^V(\d+)\.(\d+)$/', $version, $matches)) {
            return 'V' . ((int) $matches[1]) . '.' . (((int) $matches[2]) + 1);
        }

        return 'V1.0';
    }
}
