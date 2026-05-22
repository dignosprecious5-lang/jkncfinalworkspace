<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyProductController extends Controller
{
    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($company);
        $products = $this->databaseProductsForCompany($companyData);

        return view('company.products', [
            'company' => (object) $companyData,
            'products' => $products,
            'search' => '',
            'status' => 'all',
            'category' => 'all',
            'summary' => [
                'total_products' => $products->count(),
                'total_value' => (float) $products->sum(fn (array $product): float => (float) ($product['price'] ?? 0)),
            ],
            'categoryOptions' => $products->pluck('category')->filter()->unique()->sort()->values(),
            'dataNotice' => $this->productsNotice($companyData, $products),
        ]);
    }

    public function link(Request $request, int $company): RedirectResponse
    {
        $this->findCompany($company);

        return redirect()
            ->route('company.products', $company)
            ->with('products_error', 'This page now shows live products tied to the company through deals. Link or manage products from the main Products or Deals modules.');
    }

    public function store(Request $request, int $company): RedirectResponse
    {
        $this->findCompany($company);

        return redirect()
            ->route('company.products', $company)
            ->with('products_error', 'Create products from the main Products module, then associate them through live deals so they appear here automatically.');
    }

    public function show(Request $request, int $company, int $product): RedirectResponse
    {
        $companyData = $this->findCompany($company);
        $productRecord = $this->findLinkedProduct($companyData, $product);

        if (! $productRecord) {
            return redirect()
                ->route('company.products', $company)
                ->with('products_error', 'That product is no longer tied to this company or could not be found.');
        }

        return redirect()->route('products.show', $productRecord->id);
    }

    public function update(Request $request, int $company, int $product): RedirectResponse
    {
        $this->findCompany($company);

        return redirect()
            ->route('company.products', $company)
            ->with('products_error', 'Edit products from the main Products module so the same live record stays consistent everywhere.');
    }

    public function unlink(Request $request, int $company, int $product): RedirectResponse
    {
        $this->findCompany($company);

        return redirect()
            ->route('company.products', $company)
            ->with('products_error', 'This view is read-only. Update the related deal or product record from the main modules to change what appears here.');
    }

    private function databaseProductsForCompany(array $companyData): Collection
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('deals')) {
            return collect();
        }

        $companyName = trim((string) ($companyData['company_name'] ?? ''));

        if ($companyName === '') {
            return collect();
        }

        $dealIds = Deal::query()
            ->where('company_name', $companyName)
            ->pluck('id');

        if ($dealIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('deal_id', $dealIds)
            ->latest('updated_at')
            ->get()
            ->map(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'name' => $product->product_name ?: ($product->product_id ?: 'Unnamed product'),
                    'sku' => $product->sku ?: '-',
                    'category' => $product->category ?: 'General',
                    'description' => $product->product_description ?: '',
                    'price' => (float) ($product->price ?? 0),
                    'pricing_type' => $product->pricing_type ?: 'One-Time',
                    'status' => $product->status ?: 'Open',
                    'show_url' => route('products.show', $product->id),
                ];
            })
            ->values();
    }

    private function productsNotice(array $companyData, Collection $products): ?string
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('deals')) {
            return 'Product linkage is unavailable because the products or deals tables are missing. Restore those modules to show company product activity here.';
        }

        if ($products->isEmpty()) {
            return 'No live products are tied to this company yet. Products will show here automatically once they are attached to deals for this company.';
        }

        return null;
    }

    private function findLinkedProduct(array $companyData, int $productId): ?Product
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('deals')) {
            return null;
        }

        $companyName = trim((string) ($companyData['company_name'] ?? ''));

        if ($companyName === '') {
            return null;
        }

        $dealIds = Deal::query()
            ->where('company_name', $companyName)
            ->pluck('id');

        if ($dealIds->isEmpty()) {
            return null;
        }

        return Product::query()
            ->where('id', $productId)
            ->whereIn('deal_id', $dealIds)
            ->first();
    }

    private function findCompany(int $company): array
    {
        abort_unless(Schema::hasTable('companies'), 404, 'Company data is unavailable right now.');

        $record = Company::query()->findOrFail($company);

        return [
            'id' => $record->id,
            'company_name' => $record->company_name,
            'company_type' => null,
            'email' => $record->email,
            'phone' => $record->phone,
            'website' => $record->website,
            'description' => $record->description,
            'address' => $record->address,
            'owner_name' => $record->owner_name,
            'created_at' => optional($record->created_at)->toDateTimeString(),
        ];
    }
}
