<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyProductController extends Controller
{
    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($request, $company);
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));
        $category = trim((string) $request->query('category', 'all'));

        $linkedProducts = (Schema::hasTable('products') && Schema::hasTable('deals')
            ? $this->databaseProductsForCompany($companyData)
            : collect($request->session()->get($this->linkedKey($company), $this->defaultLinkedProducts($company))))
            ->when($search !== '', function (Collection $collection) use ($search) {
                $term = strtolower($search);

                return $collection->filter(function (array $product) use ($term) {
                    return collect([
                        $product['name'] ?? '',
                        $product['sku'] ?? '',
                        $product['category'] ?? '',
                        $product['pricing_type'] ?? '',
                        $product['status'] ?? '',
                    ])->contains(fn (?string $value) => str_contains(strtolower((string) $value), $term));
                });
            })
            ->when($status !== 'all', fn (Collection $collection) => $collection->where('status', $status))
            ->when($category !== 'all', fn (Collection $collection) => $collection->where('category', $category))
            ->sortBy('name')
            ->values();

        $productCatalog = collect($request->session()->get($this->catalogKey(), $this->defaultProductCatalog()))
            ->sortBy('name')
            ->values();

        return view('company.products', [
            'company' => (object) $companyData,
            'products' => $linkedProducts,
            'productCatalog' => $productCatalog,
            'search' => $search,
            'status' => $status,
            'category' => $category,
            'categoryOptions' => $productCatalog->pluck('category')->filter()->unique()->sort()->values(),
        ]);
    }

    private function databaseProductsForCompany(array $companyData): Collection
    {
        $companyName = trim((string) ($companyData['company_name'] ?? ''));

        $dealIds = Deal::query()
            ->when($companyName !== '', fn ($query) => $query->where('company_name', $companyName))
            ->pluck('id');

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
                    'notes' => '',
                    'updated_at' => optional($product->updated_at)->format('M d, Y h:i A') ?: now()->format('M d, Y h:i A'),
                    'show_url' => route('products.show', $product->product_id),
                    'readonly' => true,
                ];
            })
            ->values();
    }

    public function link(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $validated = $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        $catalogById = collect($request->session()->get($this->catalogKey(), $this->defaultProductCatalog()))->keyBy('id');
        $linked = collect($request->session()->get($this->linkedKey($company), $this->defaultLinkedProducts($company)));
        $linkedIds = $linked->pluck('id')->all();

        $requestedIds = collect($validated['product_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $newIds = $requestedIds->reject(fn (int $id) => in_array($id, $linkedIds, true));

        $newProducts = $newIds
            ->map(fn (int $id) => $catalogById->get($id))
            ->filter()
            ->map(function (array $product): array {
                $product['linked_at'] = now()->format('M d, Y h:i A');

                return $product;
            })
            ->values();

        $request->session()->put(
            $this->linkedKey($company),
            $linked->concat($newProducts)->values()->all()
        );

        $count = $newProducts->count();
        $message = $count > 0
            ? "Successfully linked {$count} product(s) to " . Arr::get($companyData, 'company_name', 'the company') . '.'
            : 'No new products were linked. Selected items may already be linked.';

        return redirect()
            ->route('company.products', $company)
            ->with('products_success', $message);
    }

    public function store(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validateProduct($request);

        $catalog = collect($request->session()->get($this->catalogKey(), $this->defaultProductCatalog()));
        $nextId = (int) ($catalog->max('id') ?? 700) + 1;

        $product = [
            'id' => $nextId,
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?: 'SKU-' . $nextId,
            'category' => $validated['category'] ?: 'General',
            'description' => $validated['description'] ?? '',
            'price' => (float) $validated['price'],
            'pricing_type' => $validated['pricing_type'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? '',
            'updated_at' => now()->format('M d, Y h:i A'),
        ];

        $catalog = $catalog->push($product)->values()->all();
        $linked = collect($request->session()->get($this->linkedKey($company), $this->defaultLinkedProducts($company)));
        $linked->push([...$product, 'linked_at' => now()->format('M d, Y h:i A')]);

        $request->session()->put($this->catalogKey(), $catalog);
        $request->session()->put($this->linkedKey($company), $linked->values()->all());

        return redirect()
            ->route('company.products', $company)
            ->with('products_success', 'Product created and linked to ' . Arr::get($companyData, 'company_name', 'the company') . '.');
    }

    public function show(Request $request, int $company, int $product): View
    {
        $companyData = $this->findCompany($request, $company);
        $linked = collect($request->session()->get($this->linkedKey($company), $this->defaultLinkedProducts($company)));
        $catalog = collect($request->session()->get($this->catalogKey(), $this->defaultProductCatalog()));
        $productData = $linked->firstWhere('id', $product) ?? $catalog->firstWhere('id', $product);

        abort_unless($productData, 404);

        return view('company.product-show', [
            'company' => (object) $companyData,
            'product' => (object) $productData,
        ]);
    }

    public function update(Request $request, int $company, int $product): RedirectResponse
    {
        $this->findCompany($request, $company);
        $validated = $this->validateProduct($request);

        $catalog = collect($request->session()->get($this->catalogKey(), $this->defaultProductCatalog()));
        abort_unless($catalog->contains('id', $product), 404);

        $updatedProduct = null;
        $catalog = $catalog->map(function (array $item) use ($product, $validated, &$updatedProduct) {
            if ((int) $item['id'] !== $product) {
                return $item;
            }

            $updatedProduct = [
                ...$item,
                'name' => $validated['name'],
                'sku' => $validated['sku'] ?: $item['sku'],
                'category' => $validated['category'] ?: $item['category'],
                'description' => $validated['description'] ?? '',
                'price' => (float) $validated['price'],
                'pricing_type' => $validated['pricing_type'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? '',
                'updated_at' => now()->format('M d, Y h:i A'),
            ];

            return $updatedProduct;
        })->values();

        $request->session()->put($this->catalogKey(), $catalog->all());

        foreach ($this->companyIds($request) as $companyId) {
            $linked = collect($request->session()->get($this->linkedKey($companyId), $this->defaultLinkedProducts($companyId)))
                ->map(function (array $item) use ($product, $updatedProduct) {
                    if ((int) $item['id'] !== $product) {
                        return $item;
                    }

                    return [
                        ...$item,
                        ...Arr::except($updatedProduct, ['linked_at']),
                    ];
                })
                ->values()
                ->all();

            $request->session()->put($this->linkedKey($companyId), $linked);
        }

        return redirect()
            ->route('company.products', $company)
            ->with('products_success', 'Product updated successfully.');
    }

    public function unlink(Request $request, int $company, int $product): RedirectResponse
    {
        $this->findCompany($request, $company);

        $linked = collect($request->session()->get($this->linkedKey($company), $this->defaultLinkedProducts($company)));
        abort_unless($linked->contains('id', $product), 404);

        $request->session()->put(
            $this->linkedKey($company),
            $linked->reject(fn (array $item) => (int) $item['id'] === $product)->values()->all()
        );

        return redirect()
            ->route('company.products', $company)
            ->with('products_success', 'Product unlinked from this company.');
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'pricing_type' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function findCompany(Request $request, int $company): array
    {
        if (Schema::hasTable('companies')) {
            $record = Company::query()->find($company);

            if ($record) {
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

        $companyData = collect($request->session()->get('mock_companies', $this->defaultCompanies()))
            ->firstWhere('id', $company);

        abort_unless($companyData, 404);

        return $companyData;
    }

    private function companyIds(Request $request): array
    {
        if (Schema::hasTable('companies')) {
            $ids = Company::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (! empty($ids)) {
                return $ids;
            }
        }

        return collect($request->session()->get('mock_companies', $this->defaultCompanies()))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function linkedKey(int $company): string
    {
        return "mock_company_products_{$company}";
    }

    private function catalogKey(): string
    {
        return 'mock_product_catalog';
    }

    private function defaultLinkedProducts(int $company): array
    {
        return match ($company) {
            1 => [
                [
                    'id' => 701,
                    'name' => 'Managed IT Services',
                    'sku' => '00101',
                    'category' => 'Consulting',
                    'description' => 'Recurring managed services package.',
                    'price' => 20000,
                    'pricing_type' => 'Recurring',
                    'status' => 'Active',
                    'notes' => '',
                    'linked_at' => 'Mar 01, 2026 10:00 AM',
                    'updated_at' => 'Mar 01, 2026 10:00 AM',
                ],
                [
                    'id' => 702,
                    'name' => 'Cloud Backup Solution',
                    'sku' => '00102',
                    'category' => 'Software',
                    'description' => 'Backup and recovery product.',
                    'price' => 15000,
                    'pricing_type' => 'Recurring',
                    'status' => 'Active',
                    'notes' => '',
                    'linked_at' => 'Mar 03, 2026 03:30 PM',
                    'updated_at' => 'Mar 03, 2026 03:30 PM',
                ],
            ],
            2 => [
                [
                    'id' => 703,
                    'name' => 'Accounting Software',
                    'sku' => '00103',
                    'category' => 'Software',
                    'description' => 'Finance and bookkeeping package.',
                    'price' => 30000,
                    'pricing_type' => 'One-Time',
                    'status' => 'Inactive',
                    'notes' => '',
                    'linked_at' => 'Mar 04, 2026 09:20 AM',
                    'updated_at' => 'Mar 04, 2026 09:20 AM',
                ],
            ],
            default => [],
        };
    }

    private function defaultProductCatalog(): array
    {
        return [
            [
                'id' => 701,
                'name' => 'Managed IT Services',
                'sku' => '00101',
                'category' => 'Consulting',
                'description' => 'Recurring managed services package.',
                'price' => 20000,
                'pricing_type' => 'Recurring',
                'status' => 'Active',
                'notes' => '',
                'updated_at' => 'Mar 01, 2026 10:00 AM',
            ],
            [
                'id' => 702,
                'name' => 'Cloud Backup Solution',
                'sku' => '00102',
                'category' => 'Software',
                'description' => 'Backup and recovery product.',
                'price' => 15000,
                'pricing_type' => 'Recurring',
                'status' => 'Active',
                'notes' => '',
                'updated_at' => 'Mar 03, 2026 03:30 PM',
            ],
            [
                'id' => 703,
                'name' => 'Accounting Software',
                'sku' => '00103',
                'category' => 'Software',
                'description' => 'Finance and bookkeeping package.',
                'price' => 30000,
                'pricing_type' => 'One-Time',
                'status' => 'Inactive',
                'notes' => '',
                'updated_at' => 'Mar 04, 2026 09:20 AM',
            ],
            [
                'id' => 704,
                'name' => 'Office Firewall Appliance',
                'sku' => '00104',
                'category' => 'Hardware',
                'description' => 'Security appliance for office networks.',
                'price' => 25500,
                'pricing_type' => 'One-Time',
                'status' => 'Active',
                'notes' => '',
                'updated_at' => 'Mar 06, 2026 11:45 AM',
            ],
            [
                'id' => 705,
                'name' => 'Payroll Automation Suite',
                'sku' => '00105',
                'category' => 'Software',
                'description' => 'Payroll and HR automation product.',
                'price' => 18500,
                'pricing_type' => 'Recurring',
                'status' => 'Draft',
                'notes' => '',
                'updated_at' => 'Mar 08, 2026 04:10 PM',
            ],
        ];
    }

    private function defaultCompanies(): array
    {
        return [
            [
                'id' => 1,
                'company_name' => 'Company 1',
                'company_type' => 'Corporation',
                'email' => 'company1@example.com',
                'phone' => '09012345678',
                'website' => 'https://bigin.example',
                'description' => 'Sample company record',
                'address' => 'Makati City',
                'owner_name' => 'Owner 1',
                'created_at' => '2026-03-01 10:00:00',
            ],
            [
                'id' => 2,
                'company_name' => 'Company 2',
                'company_type' => 'Corporation',
                'email' => 'company2@example.com',
                'phone' => '09000345678',
                'website' => 'https://bigin.example',
                'description' => 'Sample company record',
                'address' => 'Taguig City',
                'owner_name' => 'Owner 2',
                'created_at' => '2026-03-02 10:00:00',
            ],
            [
                'id' => 3,
                'company_name' => 'Company 3',
                'company_type' => 'Corporation',
                'email' => 'company3@example.com',
                'phone' => '09777345678',
                'website' => 'https://bigin.example',
                'description' => 'Sample company record',
                'address' => 'Pasig City',
                'owner_name' => 'Owner 3',
                'created_at' => '2026-03-03 10:00:00',
            ],
        ];
    }
}
