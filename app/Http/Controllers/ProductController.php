<?php

namespace App\Http\Controllers;

use App\Models\ProductVersion;
use App\Models\Product;
use App\Models\ProductActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductAuditLog;
use App\Models\ProductTerm;

class ProductController extends Controller
{

 /*
    |--------------------------------------------------------------------------
    | AUDIT
    |--------------------------------------------------------------------------
    */

private function logAudit($productId, $action, $details = null)
{
    if (!$productId) {
        return;
    }

    if (Schema::hasTable('product_audit_logs')) {

        $userName = auth()->check()
            ? auth()->user()->name
            : 'System User';

        $data = [
            'product_id'  => $productId,
            'user_name'   => $userName,
            'action'      => $action,
            'title'       => $action,
            'details'     => $details,
            'description' => $details,
            'message'     => $details,
        ];

        $validData = [];

        foreach ($data as $key => $value) {
            if (Schema::hasColumn('product_audit_logs', $key)) {
                $validData[$key] = $value;
            }
        }

        ProductAuditLog::create($validData);
    }
}
    /*
    |--------------------------------------------------------------------------
    | PRODUCT LIST
    |--------------------------------------------------------------------------
    */

  public function index(\Illuminate\Http\Request $request)
    {
        // 1. Kunin ang lahat ng produkto para sa analytics cards
        $allProducts = \App\Models\Product::all();

        // --- MGA KALKULASYON PARA SA ANALYTICS CARDS (Lahat nakadeklara na dito) ---
        $highestPriced     = $allProducts->sortByDesc('price')->first();
        $highestName       = $highestPriced ? $highestPriced->name : 'No data yet';
        $highestPrice      = $highestPriced ? number_format($highestPriced->price, 2) : '0.00';

        $mostStock         = $allProducts->sortByDesc('inventory_stock')->first();
        $mostPurchasedName = $mostStock ? $mostStock->name : 'No data yet';
        $purchasedCount    = $mostStock ? $mostStock->inventory_stock : 0;

        $laborName         = $mostPurchasedName;
        $laborHours        = $purchasedCount;

        $discountName      = $highestName;
        $maxDiscount       = '0 Max Discount';

        $marginName        = $highestName;
        $marginPct         = 100;

        $fastestName       = $highestName;
        $fastestRate       = 'Active';
        $decliningName     = 'None';


        // 2. Query para sa Table sa ibaba (Na-filter)
        $query = \App\Models\Product::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            if ($request->category === 'Other' && $request->filled('other_category')) {
                $query->where('category', $request->other_category);
            } else {
                $query->where('category', $request->category);
            }
        }

        // --- IDAGDAG ANG PRODUCT TYPE FILTER ---
        if ($request->filled('product_type')) {
            if ($request->product_type === 'Other' && $request->filled('other_product_type')) {
                $query->where('product_type', $request->other_product_type);
            } else {
                $query->where('product_type', $request->product_type);
            }
        }

        // --- IDAGDAG ANG INVENTORY TYPE FILTER ---
        if ($request->filled('inventory_type')) {
            $query->where('inventory_type', $request->inventory_type);
        }

        // --- IDAGDAG ANG SERVICE AREA FILTER (Na nasa baba na ngayon) ---
        if ($request->filled('service_area')) {
            if ($request->service_area === 'Others' && $request->filled('other_service_area')) {
                $query->where('service_area', $request->other_service_area);
            } else {
                $query->where('service_area', $request->service_area);
            }
        }

     $perPage = $request->get('per_page', 10);
        $products = $query->latest()->paginate($perPage)->withQueryString();

        // 3. Bilang ng bawat status para sa summary cards
        $allProductsCount = [
            'incomplete'   => \App\Models\Product::where('status', 'incomplete')->count(),
            'draft'        => \App\Models\Product::where('status', 'draft')->count(),
            'for_approval' => \App\Models\Product::where('status', 'for_approval')->count(),
            'active'       => \App\Models\Product::where('status', 'active')->count(),
            'archived'     => \App\Models\Product::where('status', 'archived')->count(),
        ];


        // 4. Ipasa sa view nang may kumpletong variables
        return view('products.index', compact(
            'products', 
            'allProductsCount', 
            'mostPurchasedName', 
            'purchasedCount', 
            'highestName', 
            'highestPrice', 
            'laborName', 
            'laborHours', 
            'discountName', 
            'maxDiscount', 
            'marginName', 
            'marginPct', 
            'fastestName', 
            'fastestRate', 
            'decliningName'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'service_area' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'pricing_type' => 'required|string|max:255',
            'tax_treatment' => 'required|string|max:255',
            'inventory_type' => 'required|string|max:255',
            'inventory_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $nextNumber = Product::count() + 1;

$validated['sku'] = 'PRD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $validated['status'] = 'Draft';

        $product = Product::create($validated);
ProductVersion::create([
    'product_id' => $product->id,

    // Basic Product Information
    'name' => $product->name,
    'sku' => $product->sku,
    'service_area' => $product->service_area,
    'category' => $product->category,
    'pricing_type' => $product->pricing_type,
    'tax_treatment' => $product->tax_treatment,

    // Version Control
    'version_number' => 'V1.0',
    'is_active' => true,
    'status' => 'draft',
    'created_by' => auth()->id(),
]);

return redirect()
    ->route('products.workspace', $product->id)
    ->with('success', 'Product created successfully.');

    }
 /*
    |--------------------------------------------------------------------------
    | reports
    |--------------------------------------------------------------------------
    */


   public function reports(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\Product::query();

        // 1. Mga Filter batay sa input ng user sa Report UI
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('service_area')) {
            $query->where('service_area', $request->service_area);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('pricing_type')) {
            $query->where('pricing_type', $request->pricing_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Kunin ang mga tunay na produkto mula sa database gamit ang pagination
        $products = $query->latest()->paginate(10);

        // 2. Mga Tunay na Bilang / Metrics na kukunin direkta sa database
        $totalProducts = \App\Models\Product::count();
        $activeProducts = \App\Models\Product::where('status', 'active')->count();
        $archivedProducts = \App\Models\Product::where('status', 'archived')->count();
        $draftProducts = \App\Models\Product::where('status', 'draft')->count();

        // Ibalik ang mga tunay na data patungo sa view ng reports
        return view('products.reports', compact(
            'products', 
            'totalProducts', 
            'activeProducts', 
            'archivedProducts', 
            'draftProducts'
        ));
    }
    /*
    |--------------------------------------------------------------------------
    | PRODUCT WORKSPACE
    |--------------------------------------------------------------------------
    */

    public function workspace($id)
    {

    
        // Pansamantalang inalis ang eager loading ng relations para hindi mag-error
        $product = Product::findOrFail($id);


        
        // Pansamantalang naka-comment ang metrics habang wala pa ang mga models sa iyong branch
        /*
        $product->metrics = [
            'deals_count'          => 0,
            'proposals_count'      => 0,
            'accepted_count'       => 0,
            'unique_clients_count' => 0,
            'engagements_count'    => 0,
            'proposed_value'       => 0,
            'accepted_value'       => 0,
            'discount_value'       => 0,
            'billed_revenue'       => 0,
            'collected_revenue'    => 0,
        ];
        */

        $templates = DB::table('product_requirements')
            ->select(
                'name',
                'client_type',
                'source',
                'is_mandatory',
                'file_required',
                'instructions'
            )
            ->distinct()
            ->get();

            $termsTemplates = \App\Models\TermsTemplate::where('status', 'active')->get();
        /*
        |--------------------------------------------------------------------------
        | STEP 1
        |--------------------------------------------------------------------------
        */

        $overviewCompleted =
            !empty($product->internal_description) &&
            !empty($product->effective_date);

        /*
        |--------------------------------------------------------------------------
        | STEP 2
        |--------------------------------------------------------------------------
        */

        $catalogCompleted =
            !empty($product->scope_of_work) &&
            !empty($product->deliverables);

        /*
        |--------------------------------------------------------------------------
        | STEP 3
        |--------------------------------------------------------------------------
        */

        $inventoryCompleted =
            !empty($product->unit_measure) &&
            !empty($product->stock_tracking) &&
            !empty($product->warehouse_location) &&
            !empty($product->stock_status);

        /*
        |--------------------------------------------------------------------------
        | STEP 4
        |--------------------------------------------------------------------------
        */

        $requirements = $product->requirements()
            ->orderBy('sequence')
            ->get();

        $requirementsCompleted =
            $requirements->isNotEmpty();

        /*
        |--------------------------------------------------------------------------
        | STEP 5 — WORKFLOW
        |--------------------------------------------------------------------------
        */

        $activities = $product->activities()
            ->with('children')
            ->orderBy('sequence')
            ->get();

        $mainActivitiesList = $activities
            ->where('activity_level', 'Main Activity')
            ->values();

        $workflowCompleted =
            $activities->isNotEmpty();

        /*
        |--------------------------------------------------------------------------
        | FUTURE STEPS
        |--------------------------------------------------------------------------
        */

        $commercialsCompleted = false;
        $engagementCompleted = false;
        $reportingCompleted = false;
        $automationCompleted = false;
        $termsCompleted = false;
        $usagePerformanceCompleted = false;


$visionHistoryCompleted = $product->versions()->exists();

$currentVersion = $product->versions()
    ->where('is_active', true)
    ->first();

$versionHistory = $product->versions()
    ->with('creator')
    ->orderByDesc('id')
    ->get();

$auditLogs = collect();

if (Schema::hasTable('product_audit_logs')) {

    $auditLogs = ProductAuditLog::where('product_id', $product->id)
        ->latest()
        ->paginate(10);
}



/*
|--------------------------------------------------------------------------
| WORKSPACE MODE
|--------------------------------------------------------------------------
*/

$isViewOnly = false;
$mode = 'edit';

/*
|--------------------------------------------------------------------------
| EDIT ACTIVITY ROUTE TEMPLATE
|--------------------------------------------------------------------------
*/

$actUpdateRouteTemplate = route(
    'products.activities.update',
    [
        $product->id,
        ':id',
    ]
);

return view(
            'products.workspace',
            compact(
                'product',
                'termsTemplates',
                'templates',
                'overviewCompleted',
                'catalogCompleted',
                'inventoryCompleted',
                'requirementsCompleted',
                'workflowCompleted',
                'commercialsCompleted',
                'engagementCompleted',
                'reportingCompleted',
                'automationCompleted',
                'termsCompleted',
                'usagePerformanceCompleted',
                'visionHistoryCompleted',
                'requirements',
                'activities',
                'mainActivitiesList',
                'isViewOnly',
                'mode',
                'actUpdateRouteTemplate',
                'currentVersion',
                'versionHistory',
                'auditLogs'
            )
        );
    }

    /*
   /*



|--------------------------------------------------------------------------
| STEP 1 — PRODUCT OVERVIEW
|--------------------------------------------------------------------------
*/

public function updateOverview(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'short_name' => 'nullable|string|max:255',
        'expected_turnaround' => 'nullable|string|max:255',
        'effective_date' => 'nullable|date',
        'internal_description' => 'required|string',
        'client_description' => 'nullable|string',
        'purpose' => 'nullable|string',
        'when_to_use' => 'nullable|string',
        'what_product_is_not' => 'nullable|string',
    ]);

    // Get old values BEFORE update
    $oldValues = $product->only(array_keys($validated));

    // Update product
    $product->update($validated);

    // Field labels for Audit History
    $fieldLabels = [
        'short_name' => 'Product Name',
        'expected_turnaround' => 'Expected Turnaround',
        'effective_date' => 'Effective Date',
        'internal_description' => 'Internal Description',
        'client_description' => 'Client Description',
        'purpose' => 'Purpose',
        'when_to_use' => 'When to Use',
        'what_product_is_not' => 'What Product Is Not',
    ];

    // Record each changed field
    foreach ($validated as $field => $newValue) {

        $oldValue = $oldValues[$field] ?? null;

        if ((string) $oldValue !== (string) $newValue) {

            $label = $fieldLabels[$field]
                ?? ucwords(str_replace('_', ' ', $field));

            $oldText = $oldValue !== null && $oldValue !== ''
                ? $oldValue
                : 'Empty';

            $newText = $newValue !== null && $newValue !== ''
                ? $newValue
                : 'Empty';

            $this->logAudit(
                $product->id,
                $label . ' updated',
                "Changed from: {$oldText}\nTo: {$newText}"
            );
        }
    }

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 2,
            'mode' => $request->input('mode', 'edit'),
        ])
        ->with(
            'success',
            'Product Overview saved successfully.'
        );
}
    /*
    |--------------------------------------------------------------------------
    | STEP 2 — PRODUCT CATALOG
    |--------------------------------------------------------------------------
    */

    public function updateCatalog(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'scope_of_work' => 'required|string',
            'deliverables' => 'required|string',
            'client_responsibilities' => 'nullable|string',
            'exclusions' => 'nullable|string',
        ]);

       $oldValues = $product->only(array_keys($validated));

$product->update($validated);

$fieldLabels = [
    'scope_of_work' => 'Scope of Product',
    'deliverables' => 'Deliverables',
    'client_responsibilities' => 'Client Responsibilities',
    'exclusions' => 'Exclusions',
];

foreach ($validated as $field => $newValue) {

    $oldValue = $oldValues[$field] ?? null;

    if ((string) $oldValue !== (string) $newValue) {

        $label = $fieldLabels[$field]
            ?? ucwords(str_replace('_', ' ', $field));

        $oldText = $oldValue !== null && $oldValue !== ''
            ? $oldValue
            : 'Empty';

        $newText = $newValue !== null && $newValue !== ''
            ? $newValue
            : 'Empty';

        $this->logAudit(
            $product->id,
            $label . ' updated',
            "Changed from: {$oldText}\nTo: {$newText}"
        );
    }
}

        return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 3,
            'mode' => $request->input('mode', 'edit'),
        ])
        ->with(
            'success',
            'Product Catalog saved successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3 — INVENTORY SPECIFICATIONS
    |--------------------------------------------------------------------------
    */

public function updateInventory(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'inventory_type' => 'required|string|max:255',
        'unit_measure' => 'required|string|max:255',
        'stock_tracking' => 'required|string|max:255',
        'reorder_level' => 'required|integer|min:0',
       'inventory_stock' => 'required|integer|min:0',
        'minimum_stock' => 'required|integer|min:0',
        'maximum_stock' => 'required|integer|min:0',
        'warehouse_location' => 'required|string|max:255',
        'storage_location' => 'nullable|string|max:255',
        'stock_status' => 'required|string|max:255',
    ]);

    $oldValues = $product->only(array_keys($validated));

$product->update($validated);

$fieldLabels = [
    'inventory_type' => 'Inventory Type',
    'unit_measure' => 'Unit Measure',
    'stock_tracking' => 'Stock Tracking',
    'reorder_level' => 'Reorder Level',
    'current_stock' => 'Current Stock',
    'minimum_stock' => 'Minimum Stock',
    'maximum_stock' => 'Maximum Stock',
    'warehouse_location' => 'Warehouse Location',
    'storage_location' => 'Storage Location',
    'stock_status' => 'Stock Status',
];

foreach ($validated as $field => $newValue) {

    $oldValue = $oldValues[$field] ?? null;

    if ((string) $oldValue !== (string) $newValue) {

        $label = $fieldLabels[$field]
            ?? ucwords(str_replace('_', ' ', $field));

        $oldText = $oldValue !== null && $oldValue !== ''
            ? $oldValue
            : 'Empty';

        $newText = $newValue !== null && $newValue !== ''
            ? $newValue
            : 'Empty';

        $this->logAudit(
            $product->id,
            $label . ' updated',
            "Changed from: {$oldText}\nTo: {$newText}"
        );
    }
}

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 4,
            'mode' => $request->input('mode', 'edit'),
        ])
        ->with(
            'success',
            'Inventory Specifications saved successfully.'
        );
}
    /*
/*
|--------------------------------------------------------------------------
| STEP 4 — STORE REQUIREMENT
|--------------------------------------------------------------------------
*/

public function storeRequirement(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'requirement_type' => ['nullable', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'client_type' => ['nullable', 'string', 'max:255'],
        'source' => ['nullable', 'string', 'max:255'],
        'file_required' => ['nullable', 'boolean'],
        'validity_expiration' => ['nullable', 'string', 'max:255'],
        'instructions' => ['nullable', 'string'],
        'is_mandatory' => ['nullable', 'boolean'],
    ]);

    $nextSequence = ((int) $product->requirements()->max('sequence')) + 1;

    $requirement = $product->requirements()->create([
        'requirement_type' => $validated['requirement_type'] ?? null,
        'name' => $validated['name'],
        'description' => $validated['description'] ?? null,
        'client_type' => $validated['client_type'] ?? null,
        'source' => $validated['source'] ?? null,
        'file_required' => $request->boolean('file_required'),
        'validity_expiration' => $validated['validity_expiration'] ?? null,
        'instructions' => $validated['instructions'] ?? null,
        'is_mandatory' => $request->boolean('is_mandatory'),
        'sequence' => $nextSequence,
        'status' => 'Active',
    ]);

    $this->logAudit(
        $product->id,
        'Requirement added',
        "Requirement: {$requirement->name}\n"
        . "Type: " . ($requirement->requirement_type ?: 'Empty') . "\n"
        . "Client Type: " . ($requirement->client_type ?: 'Empty') . "\n"
        . "Source: " . ($requirement->source ?: 'Empty') . "\n"
        . "File Required: " . ($requirement->file_required ? 'Yes' : 'No') . "\n"
        . "Mandatory: " . ($requirement->is_mandatory ? 'Yes' : 'No')
    );

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 4,
            'mode' => $request->input('mode', 'edit'),
        ])
        ->with(
            'success',
            'Product Requirement "' .
            $validated['name'] .
            '" saved successfully.'
        );
}


/*
|--------------------------------------------------------------------------
| STEP 4 — UPDATE REQUIREMENT
|--------------------------------------------------------------------------
*/

public function updateRequirement(
    Request $request,
    $id,
    $requirementId
) {
    $product = Product::findOrFail($id);

    $requirement = $product->requirements()
        ->where('id', $requirementId)
        ->firstOrFail();

    $validated = $request->validate([
        'requirement_type' => ['nullable', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'client_type' => ['nullable', 'string', 'max:255'],
        'source' => ['nullable', 'string', 'max:255'],
        'file_required' => ['nullable', 'boolean'],
        'validity_expiration' => ['nullable', 'string', 'max:255'],
        'instructions' => ['nullable', 'string'],
        'is_mandatory' => ['nullable', 'boolean'],
    ]);

    $requirement->update([
        'requirement_type' => $validated['requirement_type'] ?? null,
        'name' => $validated['name'],
        'description' => $validated['description'] ?? null,
        'client_type' => $validated['client_type'] ?? null,
        'source' => $validated['source'] ?? null,
        'file_required' => $request->boolean('file_required'),
        'validity_expiration' => $validated['validity_expiration'] ?? null,
        'instructions' => $validated['instructions'] ?? null,
        'is_mandatory' => $request->boolean('is_mandatory'),
    ]);

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 4,
            'mode' => $request->input('mode', 'edit'),
        ])
        ->with(
            'success',
            'Product Requirement "' .
            $requirement->name .
            '" updated successfully.'
        );
}

/*
    
/*
|--------------------------------------------------------------------------
| STEP 4 — DELETE REQUIREMENT
|--------------------------------------------------------------------------
*/

public function destroyRequirement(
    $id,
    $requirementId
) {
    $product = Product::findOrFail($id);

    $requirement = $product->requirements()
        ->where('id', $requirementId)
        ->firstOrFail();

    $requirementName = $requirement->name;

    DB::transaction(function () use ($product, $requirement) {

        $requirement->delete();

        $remainingRequirements = $product->requirements()
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $sequence = 1;

        foreach ($remainingRequirements as $remainingRequirement) {

            if ((int) $remainingRequirement->sequence !== $sequence) {
                $remainingRequirement->update([
                    'sequence' => $sequence,
                ]);
            }

            $sequence++;
        }
    });

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 4,
        ])
        ->with(
            'success',
            'Product Requirement "' .
            $requirementName .
            '" deleted successfully.'
        );
}

    /*
    |--------------------------------------------------------------------------
    | STEP 5 — STORE ACTIVITY
    |--------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| STEP 5 — WORKFLOW / ACTIVITIES
|--------------------------------------------------------------------------
*/

public function storeActivity(
    Request $request,
    $id
) {
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'activity_level' => [
            'required',
            'in:Main Activity,Sub-Activity',
        ],
        'parent_id' => [
            'nullable',
            'integer',
        ],
        'name' => [
            'required',
            'string',
            'max:255',
        ],
        'description' => [
            'nullable',
            'string',
        ],
        'expected_days' => [
            'required',
            'numeric',
            'min:0',
        ],
        'working_hours' => [
            'required',
            'numeric',
            'min:0',
        ],
        'is_billable' => [
            'required',
            'boolean',
        ],
        'is_mandatory' => [
            'required',
            'boolean',
        ],
    ]);

    if (
        $validated['activity_level'] ===
        'Main Activity'
    ) {
        $validated['parent_id'] = null;
    }

    if (
        $validated['activity_level'] ===
        'Sub-Activity'
    ) {
        if (
            empty($validated['parent_id'])
        ) {
            return redirect()
                ->route('products.workspace', [
                    'id' => $product->id,
                    'tab' => 5,
                ])
                ->with(
                    'error',
                    'Sub-Activity must have a Main Activity parent.'
                );
        }

        $parent = ProductActivity::where(
            'product_id',
            $product->id
        )
            ->where(
                'id',
                $validated['parent_id']
            )
            ->first();

        if (!$parent) {
            return redirect()
                ->route('products.workspace', [
                    'id' => $product->id,
                    'tab' => 5,
                ])
                ->with(
                    'error',
                    'Selected parent activity was not found.'
                );
        }

        if (
            $parent->activity_level !==
            'Main Activity'
        ) {
            return redirect()
                ->route('products.workspace', [
                    'id' => $product->id,
                    'tab' => 5,
                ])
                ->with(
                    'error',
                    'Sub-Activity can only belong to a Main Activity.'
                );
        }
    }

    $nextSequence = ProductActivity::where(
        'product_id',
        $product->id
    )->count() + 1;

    $activity = ProductActivity::create([
        'product_id' => $product->id,
        'activity_level' => $validated['activity_level'],
        'parent_id' => $validated['parent_id'] ?? null,
        'name' => $validated['name'],
        'description' => $validated['description'] ?? null,
        'sequence' => $nextSequence,
        'expected_days' => $validated['expected_days'],
        'working_hours' => $validated['working_hours'],
        'is_billable' => $validated['is_billable'],
        'is_mandatory' => $validated['is_mandatory'],
    ]);

    $parentName = 'None';

    if ($activity->parent_id) {
        $parentName = ProductActivity::where(
            'id',
            $activity->parent_id
        )->value('name') ?? 'None';
    }

    $this->logAudit(
        $product->id,
        'Activity added',
        "Activity: {$activity->name}\n"
        . "Level: {$activity->activity_level}\n"
        . "Parent Activity: {$parentName}\n"
        . "Expected Days: {$activity->expected_days}\n"
        . "Working Hours: {$activity->working_hours}\n"
        . "Billable: " . ($activity->is_billable ? 'Yes' : 'No') . "\n"
        . "Mandatory: " . ($activity->is_mandatory ? 'Yes' : 'No')
    );

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 5,
        ])
        ->with(
            'success',
            'Activity #' .
            $nextSequence .
            ' saved successfully.'
        );
}
    /*
    |--------------------------------------------------------------------------
    | STEP 5 — UPDATE ACTIVITY
    |--------------------------------------------------------------------------
    */

    public function updateActivity(
        Request $request,
        $id,
        $activityId
    ) {
        $product = Product::findOrFail($id);

        $activity = $product->activities()
            ->where('id', $activityId)
            ->firstOrFail();

        $validated = $request->validate([
            'activity_level' => [
                'required',
                'in:Main Activity,Sub-Activity',
            ],
            'parent_id' => [
                'nullable',
                'integer',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'expected_days' => [
                'required',
                'numeric',
                'min:0',
            ],
            'working_hours' => [
                'required',
                'numeric',
                'min:0',
            ],
            'is_billable' => [
                'required',
                'boolean',
            ],
            'is_mandatory' => [
                'required',
                'boolean',
            ],
        ]);

        if (
            $validated['activity_level'] ===
            'Main Activity'
        ) {
            $validated['parent_id'] = null;
        }

        if (
            $validated['activity_level'] ===
            'Sub-Activity'
        ) {
            if (
                empty($validated['parent_id'])
            ) {
                return redirect()
                    ->route('products.workspace', [
                        'id' => $product->id,
                        'tab' => 5,
                    ])
                    ->with(
                        'error',
                        'Sub-Activity must have a Main Activity parent.'
                    );
            }

            $parent = ProductActivity::where(
                'product_id',
                $product->id
            )
                ->where(
                    'id',
                    $validated['parent_id']
                )
                ->first();

            if (!$parent) {
                return redirect()
                    ->route('products.workspace', [
                        'id' => $product->id,
                        'tab' => 5,
                    ])
                    ->with(
                        'error',
                        'Selected parent activity was not found.'
                    );
            }

            if (
                $parent->activity_level !==
                'Main Activity'
            ) {
                return redirect()
                    ->route('products.workspace', [
                        'id' => $product->id,
                        'tab' => 5,
                    ])
                    ->with(
                        'error',
                        'Sub-Activity can only belong to a Main Activity.'
                    );
            }
        }

        $activity->update([
            'activity_level' => $validated['activity_level'],
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'expected_days' => $validated['expected_days'],
            'working_hours' => $validated['working_hours'],
            'is_billable' => $validated['is_billable'],
            'is_mandatory' => $validated['is_mandatory'],
        ]);

        return redirect()
            ->route('products.workspace', [
                'id' => $product->id,
                'tab' => 5,
            ])
            ->with(
                'success',
                'Activity #' .
                $activity->sequence .
                ' updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 5 — DELETE ACTIVITY
    |--------------------------------------------------------------------------
    */

    public function destroyActivity(
        $id,
        $activityId
    ) {
        $product = Product::findOrFail($id);

        $activity = $product->activities()
            ->where('id', $activityId)
            ->firstOrFail();

        $activityName = $activity->name;

        DB::transaction(
            function () use (
                $product,
                $activity
            ) {
                $activity->delete();

                $remainingActivities =
                    ProductActivity::where(
                        'product_id',
                        $product->id
                    )
                    ->orderBy('sequence')
                    ->orderBy('id')
                    ->get();

                $sequence = 1;

                foreach (
                    $remainingActivities
                    as $remainingActivity
                ) {
                    if (
                        (int) $remainingActivity->sequence
                        !== $sequence
                    ) {
                        $remainingActivity->update([
                            'sequence' => $sequence,
                        ]);
                    }

                    $sequence++;
                }
            }
        );

        return redirect()
            ->route('products.workspace', [
                'id' => $product->id,
                'tab' => 5,
            ])
            ->with(
                'success',
                'Activity "' .
                $activityName .
                '" deleted successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 5 — BULK IMPORT
    |--------------------------------------------------------------------------
    */

    public function bulkImportActivities(
        Request $request,
        $id
    ) {
        $product = Product::findOrFail($id);

        $request->validate([
            'activity_file' => [
                'nullable',
                'file',
                'mimes:csv,txt',
                'max:2048',
            ],
            'csv_text' => [
                'nullable',
                'string',
            ],
            'outline_text' => [
                'nullable',
                'string',
            ],
            'default_expected_days' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'default_working_hours' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $hasFile = $request->hasFile('activity_file');

        $csvText = trim(
            (string) $request->input('csv_text', '')
        );

        $outlineText = trim(
            (string) $request->input('outline_text', '')
        );

        if (
            !$hasFile &&
            $csvText === '' &&
            $outlineText === ''
        ) {
            return redirect()
                ->route('products.workspace', [
                    'id' => $product->id,
                    'tab' => 5,
                ])
                ->with(
                    'error',
                    'Please upload a CSV file, paste CSV data, or paste an activity outline.'
                );
        }

        try {
            if ($hasFile || $csvText !== '') {
                if ($hasFile) {
                    $file = $request->file('activity_file');

                    $csvText = file_get_contents(
                        $file->getRealPath()
                    );

                    if ($csvText === false) {
                        throw new \Exception(
                            'Unable to read the uploaded CSV file.'
                        );
                    }
                }

                $csvText = preg_replace(
                    '/^\xEF\xBB\xBF/',
                    '',
                    $csvText
                );

                $lines = preg_split(
                    '/\r\n|\r|\n/',
                    trim($csvText)
                );

                $lines = array_values(
                    array_filter(
                        $lines,
                        function ($line) {
                            return trim($line) !== '';
                        }
                    )
                );

                if (count($lines) < 2) {
                    throw new \Exception(
                        'The CSV data must contain a header and at least one activity.'
                    );
                }

                $header = str_getcsv(
                    array_shift($lines)
                );

                $header = array_map(
                    function ($column) {
                        return strtolower(
                            trim($column)
                        );
                    },
                    $header
                );

                $requiredColumns = [
                    'activity_level',
                    'parent_sequence',
                    'name',
                    'description',
                    'expected_days',
                    'working_hours',
                    'is_billable',
                    'is_mandatory',
                ];

                foreach ($requiredColumns as $requiredColumn) {
                    if (!in_array(
                        $requiredColumn,
                        $header,
                        true
                    )) {
                        throw new \Exception(
                            'Invalid CSV format. Missing column: ' .
                            $requiredColumn
                        );
                    }
                }

                $rows = [];

                foreach ($lines as $index => $line) {
                    $values = str_getcsv($line);

                    $values = array_pad(
                        $values,
                        count($header),
                        null
                    );

                    $values = array_slice(
                        $values,
                        0,
                        count($header)
                    );

                    $row = array_combine(
                        $header,
                        $values
                    );

                    if (
                        !is_array($row)
                    ) {
                        throw new \Exception(
                            'Invalid CSV data on row ' .
                            ($index + 2) .
                            '.'
                        );
                    }

                    $rows[] = $row;
                }

                if (empty($rows)) {
                    throw new \Exception(
                        'No activity records were found in the CSV.'
                    );
                }

                $importedCount = 0;

                DB::transaction(
                    function () use (
                        $rows,
                        $product,
                        &$importedCount
                    ) {
                        $lastSequence =
                            ProductActivity::where(
                                'product_id',
                                $product->id
                            )->max('sequence');

                        $nextSequence =
                            ((int) $lastSequence) + 1;

                        $mainActivityMap = [];
                        $mainActivityReference = 0;

                        foreach (
                            $rows as $index => $row
                        ) {
                            $csvRowNumber =
                                $index + 2;

                            $activityLevel =
                                strtolower(
                                    trim(
                                        (string) (
                                            $row[
                                                'activity_level'
                                            ] ?? ''
                                        )
                                    )
                                );

                            if (
                                in_array(
                                    $activityLevel,
                                    [
                                        'main',
                                        'main activity',
                                        'main_activity',
                                    ],
                                    true
                                )
                            ) {
                                $activityLevel =
                                    'Main Activity';
                            } elseif (
                                in_array(
                                    $activityLevel,
                                    [
                                        'sub',
                                        'sub-activity',
                                        'sub_activity',
                                    ],
                                    true
                                )
                            ) {
                                $activityLevel =
                                    'Sub-Activity';
                            } else {
                                throw new \Exception(
                                    "CSV row {$csvRowNumber}: Invalid activity_level."
                                );
                            }

                            $name = trim(
                                (string) (
                                    $row['name'] ?? ''
                                )
                            );

                            if ($name === '') {
                                throw new \Exception(
                                    "CSV row {$csvRowNumber}: Activity Name is required."
                                );
                            }

                            $description = trim(
                                (string) (
                                    $row['description'] ?? ''
                                )
                            );

                            $expectedDays =
                                ($row['expected_days'] ?? '') !== ''
                                    ? (float) $row['expected_days']
                                    : 0;

                            if ($expectedDays < 0) {
                                throw new \Exception(
                                    "CSV row {$csvRowNumber}: Expected Days cannot be negative."
                                );
                            }

                            $workingHours =
                                ($row['working_hours'] ?? '') !== ''
                                    ? (float) $row['working_hours']
                                    : 0;

                            if ($workingHours < 0) {
                                throw new \Exception(
                                    "CSV row {$csvRowNumber}: Working Hours cannot be negative."
                                );
                            }

                            $isBillable = in_array(
                                strtolower(
                                    trim(
                                        (string) (
                                            $row[
                                                'is_billable'
                                            ] ?? ''
                                        )
                                    )
                                ),
                                [
                                    '1',
                                    'true',
                                    'yes',
                                    'y',
                                    'on',
                                    'billable',
                                ],
                                true
                            );

                            $isMandatory = in_array(
                                strtolower(
                                    trim(
                                        (string) (
                                            $row[
                                                'is_mandatory'
                                            ] ?? ''
                                        )
                                    )
                                ),
                                [
                                    '1',
                                    'true',
                                    'yes',
                                    'y',
                                    'on',
                                    'mandatory',
                                ],
                                true
                            );

                            if (
                                $activityLevel ===
                                'Main Activity'
                            ) {
                                $mainActivityReference++;

                                $activity =
                                    ProductActivity::create([
                                        'product_id' =>
                                            $product->id,
                                        'activity_level' =>
                                            'Main Activity',
                                        'parent_id' =>
                                            null,
                                        'name' =>
                                            $name,
                                        'description' =>
                                            $description !== ''
                                                ? $description
                                                : null,
                                        'sequence' =>
                                            $nextSequence,
                                        'expected_days' =>
                                            $expectedDays,
                                        'working_hours' =>
                                            $workingHours,
                                        'is_billable' =>
                                            $isBillable,
                                        'is_mandatory' =>
                                            $isMandatory,
                                    ]);

                                $mainActivityMap[
                                    $mainActivityReference
                                ] = $activity->id;

                                $nextSequence++;
                                $importedCount++;

                                continue;
                            }

                            $parentSequence = trim(
                                (string) (
                                    $row[
                                        'parent_sequence'
                                    ] ?? ''
                                )
                            );

                            if (
                                $parentSequence === ''
                            ) {
                                throw new \Exception(
                                    "CSV row {$csvRowNumber}: Sub-Activity requires parent_sequence."
                                );
                            }

                            if (
                                !ctype_digit(
                                    $parentSequence
                                )
                            ) {
                                throw new \Exception(
                                    "CSV row {$csvRowNumber}: parent_sequence must be a number."
                                );
                            }

                            $parentSequence =
                                (int) $parentSequence;

                            if (
                                !isset(
                                    $mainActivityMap[
                                        $parentSequence
                                    ]
                                )
                            ) {
                                throw new \Exception(
                                    "CSV row {$csvRowNumber}: Parent Main Activity {$parentSequence} was not found."
                                );
                            }

                            ProductActivity::create([
                                'product_id' =>
                                    $product->id,
                                'activity_level' =>
                                    'Sub-Activity',
                                'parent_id' =>
                                    $mainActivityMap[
                                        $parentSequence
                                    ],
                                'name' =>
                                    $name,
                                'description' =>
                                    $description !== ''
                                        ? $description
                                        : null,
                                'sequence' =>
                                    $nextSequence,
                                'expected_days' =>
                                    $expectedDays,
                                'working_hours' =>
                                    $workingHours,
                                'is_billable' =>
                                    $isBillable,
                                'is_mandatory' =>
                                    $isMandatory,
                            ]);

                            $nextSequence++;
                            $importedCount++;
                        }
                    }
                );

                return redirect()
                    ->route(
                        'products.workspace',
                        [
                            'id' =>
                                $product->id,
                            'tab' =>
                                5,
                        ]
                    )
                    ->with(
                        'success',
                        $importedCount .
                        ' product activities imported successfully.'
                    );
            }

            $defaultExpectedDays =
                (float) $request->input(
                    'default_expected_days',
                    1
                );

            $defaultWorkingHours =
                (float) $request->input(
                    'default_working_hours',
                    1
                );

            if ($outlineText === '') {
                throw new \Exception(
                    'Activity outline is empty.'
                );
            }

            $lines = preg_split(
                '/\r\n|\r|\n/',
                $outlineText
            );

            $lastSequence =
                ProductActivity::where(
                    'product_id',
                    $product->id
                )->max('sequence');

            $nextSequence =
                ((int) $lastSequence) + 1;

            $currentMain = null;
            $importedCount = 0;

            DB::transaction(
                function () use (
                    $lines,
                    $product,
                    $defaultExpectedDays,
                    $defaultWorkingHours,
                    &$nextSequence,
                    &$currentMain,
                    &$importedCount
                ) {
                    foreach ($lines as $line) {
                        if (
                            trim($line) === ''
                        ) {
                            continue;
                        }

                        $trimmed =
                            trim($line);

                        $leadingCharacters =
                            strlen($line) -
                            strlen(
                                ltrim(
                                    $line,
                                    " \t"
                                )
                            );

                        $isSubActivity =
                            $leadingCharacters > 0;

                        if (!$isSubActivity) {
                            $activity =
                                ProductActivity::create([
                                    'product_id' =>
                                        $product->id,
                                    'activity_level' =>
                                        'Main Activity',
                                    'parent_id' =>
                                        null,
                                    'name' =>
                                        $trimmed,
                                    'description' =>
                                        null,
                                    'sequence' =>
                                        $nextSequence,
                                    'expected_days' =>
                                        $defaultExpectedDays,
                                    'working_hours' =>
                                        $defaultWorkingHours,
                                    'is_billable' =>
                                        true,
                                    'is_mandatory' =>
                                        true,
                                ]);

                            $currentMain =
                                $activity;

                            $nextSequence++;
                            $importedCount++;

                            continue;
                        }

                        if (!$currentMain) {
                            throw new \Exception(
                                'A Sub-Activity was found before a Main Activity.'
                            );
                        }

                        ProductActivity::create([
                            'product_id' =>
                                $product->id,
                            'activity_level' =>
                                'Sub-Activity',
                            'parent_id' =>
                                $currentMain->id,
                            'name' =>
                                $trimmed,
                            'description' =>
                                null,
                            'sequence' =>
                                $nextSequence,
                            'expected_days' =>
                                $defaultExpectedDays,
                            'working_hours' =>
                                $defaultWorkingHours,
                            'is_billable' =>
                                true,
                            'is_mandatory' =>
                                true,
                        ]);

                        $nextSequence++;
                        $importedCount++;
                    }
                }
            );

            return redirect()
                ->route(
                    'products.workspace',
                    [
                        'id' =>
                            $product->id,
                        'tab' =>
                            5,
                    ]
                )
                ->with(
                    'success',
                    $importedCount .
                    ' product activities imported successfully.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route(
                    'products.workspace',
                    [
                        'id' =>
                            $product->id,
                        'tab' =>
                            5,
                    ]
                )
                ->with(
                    'error',
                    'Bulk import failed: ' .
                    $e->getMessage()
                );
        }
    }
// =========================================================
// STEP 6 — UPDATE PRODUCT COMMERCIALS
// =========================================================

public function updateCommercials(Request $request, $id)
{
    $product = Product::findOrFail($id);

    
    $validated = $request->validate([
        'pricing_model' => 'nullable|string|max:255',
        'currency' => 'nullable|string|max:10',
        'price' => 'nullable|numeric|min:0',
        'minimum_price' => 'nullable|numeric|min:0',
        'maximum_price' => 'nullable|numeric|min:0',
        'tax_treatment' => 'nullable|string|max:255',
        'cost_per_unit' => 'nullable|numeric|min:0',
        'expected_margin' => 'nullable|numeric|min:0',
        'discount_allowed' => 'nullable|string|max:255',
        'expected_hours' => 'nullable|numeric|min:0',
        'payment_structure' => 'nullable|string|max:255',
        'payment_structure_custom' => 'nullable|string|max:1000',
        'payment_notes' => 'nullable|string|max:5000',
    ]);

    $product->update($validated);

    return redirect()
    ->route('products.workspace', [
        'id' => $product->id,
        'tab' => 7,
    ])
    ->with('success', 'Product commercials updated successfully.');
}
    /*
    |--------------------------------------------------------------------------
    | STEP 7 — UPDATE ENGAGEMENT
    |--------------------------------------------------------------------------
    */
    public function updateEngagement(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
       'payment_structure' => 'nullable|string|max:255',
        'payment_structure_custom' => 'nullable|string|max:255',
        'instantiation_execution_mode' => 'nullable|string|max:255',
        'recurrence_frequency' => 'required|string|max:255',
        'recurrence_frequency_custom' => 'nullable|string|max:255',
        'billing_frequency' => 'required|string|max:255',
        'billing_frequency_custom' => 'nullable|string|max:255',
        'reporting_frequency' => 'required|string|max:255',
        'reporting_frequency_custom' => 'nullable|string|max:255',
        'auto_carryover' => 'nullable|boolean',
    ]);

    $oldValues = $product->only([
        'payment_structure',
        'payment_structure_custom',
        'instantiation_execution_mode',
        'recurrence_frequency',
        'recurrence_frequency_custom',
        'billing_frequency',
        'billing_frequency_custom',
        'reporting_frequency',
        'reporting_frequency_custom',
        'auto_carryover',
    ]);

    $updateData = [
       'payment_structure' => $validated['payment_structure'] ?? null,
        'payment_structure_custom' => $validated['payment_structure_custom'] ?? null,
        'instantiation_execution_mode' => $validated['instantiation_execution_mode'] ?? null,
        'recurrence_frequency' => $validated['recurrence_frequency'],
        'recurrence_frequency_custom' => $validated['recurrence_frequency_custom'] ?? null,
        'billing_frequency' => $validated['billing_frequency'],
        'billing_frequency_custom' => $validated['billing_frequency_custom'] ?? null,
        'reporting_frequency' => $validated['reporting_frequency'],
        'reporting_frequency_custom' => $validated['reporting_frequency_custom'] ?? null,
        'auto_carryover' => $request->has('auto_carryover'),
    ];

    $product->update($updateData);

    $fieldLabels = [
        'payment_structure' => 'Payment Structure',
        'payment_structure_custom' => 'Custom Payment Structure',
        'instantiation_execution_mode' => 'Instantiation / Execution Mode',
        'recurrence_frequency' => 'Recurrence Frequency',
        'recurrence_frequency_custom' => 'Custom Recurrence Frequency',
        'billing_frequency' => 'Billing Frequency',
        'billing_frequency_custom' => 'Custom Billing Frequency',
        'reporting_frequency' => 'Reporting Frequency',
        'reporting_frequency_custom' => 'Custom Reporting Frequency',
        'auto_carryover' => 'Auto Carryover',
    ];

    foreach ($updateData as $field => $newValue) {

        $oldValue = $oldValues[$field] ?? null;

        if ((string) $oldValue !== (string) $newValue) {

            $label = $fieldLabels[$field]
                ?? ucwords(str_replace('_', ' ', $field));

            $oldText = $oldValue !== null && $oldValue !== ''
                ? $oldValue
                : 'Empty';

            $newText = $newValue !== null && $newValue !== ''
                ? $newValue
                : 'Empty';

            if ($field === 'auto_carryover') {
                $oldText = $oldValue ? 'Yes' : 'No';
                $newText = $newValue ? 'Yes' : 'No';
            }

            $this->logAudit(
                $product->id,
                $label . ' updated',
                "Changed from: {$oldText}\nTo: {$newText}"
            );
        }
    }

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 8,
        ])
        ->with(
            'success',
            'Engagement configuration saved successfully.'
        );
}

/*
|--------------------------------------------------------------------------
| STEP 8 — UPDATE REPORTING
|--------------------------------------------------------------------------
*/
public function updateReporting(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'reporting_frequency' => 'required|string|max:255',
        'project_reporting_type' => 'required|string|max:255',
        'report_scope' => 'nullable|array',
        'report_scope.*' => 'string|max:255',
    ]);

    // Get old values BEFORE update
    $oldValues = $product->only([
        'reporting_frequency',
        'project_reporting_type',
        'report_scope',
    ]);

    $newReportScope = $validated['report_scope'] ?? [];

    // Update product
    $product->update([
        'reporting_frequency' => $validated['reporting_frequency'],
        'project_reporting_type' => $validated['project_reporting_type'],
        'report_scope' => json_encode($newReportScope),
    ]);

    // Field labels for Audit History
    $fieldLabels = [
        'reporting_frequency' => 'Reporting Frequency',
        'project_reporting_type' => 'Project Reporting Type',
        'report_scope' => 'Report Scope',
    ];

    $newValues = [
        'reporting_frequency' => $validated['reporting_frequency'],
        'project_reporting_type' => $validated['project_reporting_type'],
        'report_scope' => $newReportScope,
    ];

    foreach ($newValues as $field => $newValue) {

        $oldValue = $oldValues[$field] ?? null;

        // Normalize report scope for comparison
        if ($field === 'report_scope') {

            $oldScope = $oldValue;

            if (is_string($oldScope)) {
                $decoded = json_decode($oldScope, true);
                $oldScope = is_array($decoded)
                    ? $decoded
                    : [];
            }

            $oldValue = $oldScope;

            $oldCompare = json_encode($oldScope);
            $newCompare = json_encode($newValue);

        } else {

            $oldCompare = (string) $oldValue;
            $newCompare = (string) $newValue;
        }

        if ($oldCompare !== $newCompare) {

            $label = $fieldLabels[$field]
                ?? ucwords(str_replace('_', ' ', $field));

            if ($field === 'report_scope') {

                $oldText = !empty($oldValue)
                    ? implode(', ', (array) $oldValue)
                    : 'Empty';

                $newText = !empty($newValue)
                    ? implode(', ', (array) $newValue)
                    : 'Empty';

            } else {

                $oldText = $oldValue !== null && $oldValue !== ''
                    ? $oldValue
                    : 'Empty';

                $newText = $newValue !== null && $newValue !== ''
                    ? $newValue
                    : 'Empty';
            }

            $this->logAudit(
                $product->id,
                $label . ' updated',
                "Changed from: {$oldText}\nTo: {$newText}"
            );
        }
    }

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 9,
        ])
        ->with(
            'success',
            'Reporting configuration saved successfully.'
        );
}
   /*
    |--------------------------------------------------------------------------
    | STEP 9 — UPDATE AUTOMATION
    |--------------------------------------------------------------------------
    */

public function updateAutomation(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'base_reference_date' => 'nullable|string|max:255',
        'lead_time_generation' => 'nullable|integer',
        'default_auto_assignment_rule' => 'nullable|string|max:255',
        'auto_carryover_automation' => 'nullable|boolean',
        'internal_reminder_trigger' => 'nullable|integer',
        'client_followup_cadence' => 'nullable|string|max:255',
        'notification_channel' => 'nullable|string|max:255',
        'overdue_escalation_threshold' => 'nullable|integer',
        'escalation_recipient_role' => 'nullable|string|max:255',
        'missing_requirements_gate_rule' => 'nullable|string|max:255',
    ]);

    // Get old values BEFORE update
    $oldValues = $product->only([
        'base_reference_date',
        'lead_time_generation',
        'default_auto_assignment_rule',
        'auto_carryover_automation',
        'internal_reminder_trigger',
        'client_followup_cadence',
        'notification_channel',
        'overdue_escalation_threshold',
        'escalation_recipient_role',
        'missing_requirements_gate_rule',
    ]);

    // New values
    $updateData = [
        'base_reference_date' => $validated['base_reference_date'] ?? null,
        'lead_time_generation' => $validated['lead_time_generation'] ?? null,
        'default_auto_assignment_rule' => $validated['default_auto_assignment_rule'] ?? null,
        'auto_carryover_automation' => $request->has('auto_carryover_automation'),
        'internal_reminder_trigger' => $validated['internal_reminder_trigger'] ?? null,
        'client_followup_cadence' => $validated['client_followup_cadence'] ?? null,
        'notification_channel' => $validated['notification_channel'] ?? null,
        'overdue_escalation_threshold' => $validated['overdue_escalation_threshold'] ?? null,
        'escalation_recipient_role' => $validated['escalation_recipient_role'] ?? null,
        'missing_requirements_gate_rule' => $validated['missing_requirements_gate_rule'] ?? null,
    ];

    // Update product
    $product->update($updateData);

    // Field labels for Audit History
    $fieldLabels = [
        'base_reference_date' => 'Base Reference Date',
        'lead_time_generation' => 'Lead Time Generation',
        'default_auto_assignment_rule' => 'Default Auto Assignment Rule',
        'auto_carryover_automation' => 'Auto Carryover Automation',
        'internal_reminder_trigger' => 'Internal Reminder Trigger',
        'client_followup_cadence' => 'Client Follow-up Cadence',
        'notification_channel' => 'Notification Channel',
        'overdue_escalation_threshold' => 'Overdue Escalation Threshold',
        'escalation_recipient_role' => 'Escalation Recipient Role',
        'missing_requirements_gate_rule' => 'Missing Requirements Gate Rule',
    ];

    // Record each changed field
    foreach ($updateData as $field => $newValue) {

        $oldValue = $oldValues[$field] ?? null;

        if ((string) $oldValue !== (string) $newValue) {

            $label = $fieldLabels[$field]
                ?? ucwords(str_replace('_', ' ', $field));

            if (
                in_array($field, [
                    'auto_carryover_automation',
                ])
            ) {
                $oldText = $oldValue ? 'Yes' : 'No';
                $newText = $newValue ? 'Yes' : 'No';
            } else {
                $oldText = $oldValue !== null && $oldValue !== ''
                    ? $oldValue
                    : 'Empty';

                $newText = $newValue !== null && $newValue !== ''
                    ? $newValue
                    : 'Empty';
            }

            $this->logAudit(
                $product->id,
                $label . ' updated',
                "Changed from: {$oldText}\nTo: {$newText}"
            );
        }
    }

    return redirect()
        ->route('products.workspace', [
            'id' => $product->id,
            'tab' => 10,
        ])
        ->with(
            'success',
            'Automation configuration saved successfully.'
        );
}


 /*
    |--------------------------------------------------------------------------
    | STEP 12 — VERSION
    |--------------------------------------------------------------------------
    */

  public function update(Request $request, $id)
{
    // 1. Sinesave ang mga binago mo sa form
    $product = Product::findOrFail($id);
    $product->update($request->all());

    // 2. Kunin kung aling tab ang kasalukuyang binabago (kung mayroon man)
    $currentTab = $request->input('tab', 'overview');

    // 3. I-log ang audit kasama ang product id at detalye ng tab
    // (Depende sa kung paano ginawa ang AuditLog class mo, pwede mong idagdag ang product_id)
    AuditLog::log(
        'WORKSPACE_UPDATED', 
        'Workspace', 
        'Saved changes for tab: ' . ucfirst(str_replace('_', ' ', $currentTab)),
        $product->id // Isama natin ang ID para sa produktong ito
    );

    return redirect()->back()->with('success', 'Updated successfully!');
}


 /*
|--------------------------------------------------------------------------
| SUBMIT FOR APPROVAL
|--------------------------------------------------------------------------
*/

public function submitApproval($id)
{
    $product = Product::findOrFail($id);

    // 1. I-update ang status ng produkto patungong Pending Approval
    $oldStatus = $product->status;

    $product->update([
        'status' => 'Pending Approval'
    ]);

    // 2. Audit History
    $this->logAudit(
        $product->id,
        'Product Status updated',
        "Changed from: " . ($oldStatus ?: 'Empty') . "\n"
        . "To: Pending Approval"
    );

    // 3. I-redirect pabalik sa Product List / Dashboard ng mga Produkto
    return redirect()
        ->route('products.index')
        ->with(
            'success',
            'Product successfully submitted for approval!'
        );
}

 /*
    |--------------------------------------------------------------------------
    | Archive
    |--------------------------------------------------------------------------
    */

    public function archive($id)
{
    $product = \App\Models\Product::findOrFail($id);
    $product->status = 'archived'; // O kung ano mang column/status value ang ginagamit mo para sa archived
    $product->save();

    return redirect()->route('products.index')->with('success', 'Product archived successfully.');
}

/*
    |--------------------------------------------------------------------------
    | duplicate
    |--------------------------------------------------------------------------
    */


public function duplicate($id)
{
    $product = \App\Models\Product::findOrFail($id);
    
    // Kopyahin ang produkto
    $newProduct = $product->replicate();
    $newProduct->name = $product->name . ' (Copy)';
    
    // Gumawa ng bagong unique SKU para hindi magka-conflict sa database
    $latestId = \App\Models\Product::max('id') + 1;
    $newProduct->sku = 'PRD-' . sprintf('%04d', $latestId);
    
    $newProduct->save();

    return redirect()->route('products.index')->with('success', 'Product duplicated successfully.');
}
}