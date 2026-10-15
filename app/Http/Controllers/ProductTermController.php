<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductTerm;
use App\Models\TermsTemplate;
use App\Models\ProductAuditLog;
use Illuminate\Http\Request;

class ProductTermController extends Controller
{
    /**
     * Add Product Term
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scope' => ['nullable', 'string', 'max:100'],
        ]);

        $nextSortOrder = ((int) $product->terms()->max('sort_order')) + 1;

        $term = $product->terms()->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'sort_order' => $nextSortOrder,
            'status' => 'active',
            'scope' => $validated['scope'] ?? 'product_specific',
        ]);

        /*
        |--------------------------------------------------------------------------
        | AUDIT HISTORY
        |--------------------------------------------------------------------------
        */

        $scopeLabels = [
            'global' => 'Global Scope',
            'service_area' => 'Service Area Scope',
            'category' => 'Category',
            'service_specific' => 'Product Specific',
            'product_specific' => 'Product Specific',
        ];

        $scope = $scopeLabels[$term->scope]
            ?? $term->scope;

        ProductAuditLog::create([
            'product_id' => $product->id,
            'user_name' => auth()->check()
                ? auth()->user()->name
                : 'System User',
            'action' => 'Term added',
            'title' => 'Term added',
            'details' =>
                "Term: {$term->title}\n"
                . "Scope: {$scope}\n"
                . "Content: {$term->content}",
            'description' =>
                "Term: {$term->title}\n"
                . "Scope: {$scope}\n"
                . "Content: {$term->content}",
            'message' =>
                "Term: {$term->title}\n"
                . "Scope: {$scope}\n"
                . "Content: {$term->content}",
        ]);

        return redirect()
            ->route('products.workspace', [
                'id' => $product->id,
                'tab' => 10,
                'mode' => 'edit',
            ])
            ->with(
                'success',
                'Product term added successfully.'
            );
    }


    /**
     * Update Product Term
     */
    public function update(Request $request, ProductTerm $productTerm)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scope' => ['required', 'string', 'max:100'],
        ]);

        $productTerm->update($validated);

        $productId = $productTerm->product_id;

        return redirect()
            ->route('products.workspace', [
                'id' => $productId,
                'tab' => 10,
                'mode' => 'edit',
            ])
            ->with(
                'success',
                'Product term updated successfully.'
            );
    }


    /**
     * Duplicate Product Term
     */
    public function duplicate(
        Product $product,
        ProductTerm $productTerm
    ) {
        $nextSortOrder = ((int) $product->terms()->max('sort_order')) + 1;

        $product->terms()->create([
            'title' => $productTerm->title . ' (Copy)',
            'content' => $productTerm->content,
            'scope' => $productTerm->scope,
            'sort_order' => $nextSortOrder,
            'status' => 'active',
        ]);

        return redirect()
            ->route('products.workspace', [
                'id' => $product->id,
                'tab' => 10,
                'mode' => 'edit',
            ])
            ->with(
                'success',
                'Product term duplicated successfully.'
            );
    }


    /**
     * Disable Product Term
     */
    public function disable(ProductTerm $productTerm)
    {
        $productTerm->update([
            'status' => 'inactive',
        ]);

        return redirect()
            ->route('products.workspace', [
                'id' => $productTerm->product_id,
                'tab' => 10,
                'mode' => 'edit',
            ])
            ->with(
                'success',
                'Product term disabled successfully.'
            );
    }


    /**
     * Delete Product Term
     */
    public function destroy(ProductTerm $productTerm)
    {
        $productId = $productTerm->product_id;

        $productTerm->delete();

        return redirect()
            ->route('products.workspace', [
                'id' => $productId,
                'tab' => 10,
                'mode' => 'edit',
            ])
            ->with(
                'success',
                'Product term deleted successfully.'
            );
    }


    /**
     * Save Individual Product Term as Template
     */
    public function saveAsTemplate(
        Request $request,
        Product $product,
        ProductTerm $productTerm
    ) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scope' => ['required', 'string', 'max:100'],
        ]);

        $template = TermsTemplate::create([
            'name' => $validated['name'],
            'status' => 'active',
        ]);

        $template->items()->create([
            'title' => $validated['name'],
            'content' => $validated['content'],
            'scope' => $validated['scope'],
            'sort_order' => 1,
        ]);

        return redirect()
            ->route('products.workspace', [
                'id' => $product->id,
                'tab' => 10,
                'mode' => 'edit',
            ])
            ->with(
                'success',
                'Term successfully saved as template.'
            );
    }


    /**
     * Save Multiple Terms as Template
     */
    public function storeTemplate(
        Request $request,
        Product $product
    ) {
        $validated = $request->validate([
            'template_name' => ['required', 'string', 'max:255'],
            'term_ids' => ['required', 'array'],
        ]);

        $template = TermsTemplate::create([
            'name' => $validated['template_name'],
            'status' => 'active',
        ]);

        $terms = ProductTerm::whereIn(
            'id',
            $validated['term_ids']
        )->get();

        foreach ($terms as $index => $term) {

            $template->items()->create([
                'title' => $term->title,
                'content' => $term->content,
                'scope' => $term->scope,
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()
            ->route('products.workspace', [
                'id' => $product->id,
                'tab' => 10,
                'mode' => 'edit',
            ])
            ->with(
                'success',
                'Template created successfully.'
            );
    }


    /**
     * Apply Existing Template
     */
    public function applyTemplate(
        Request $request,
        Product $product
    ) {
        $validated = $request->validate([
            'template_id' => [
                'required',
                'exists:terms_templates,id',
            ],
            'application_mode' => [
                'required',
                'in:add,replace',
            ],
        ]);

        $template = TermsTemplate::with('items')
            ->findOrFail($validated['template_id']);

        if ($validated['application_mode'] === 'replace') {
            $product->terms()->delete();
        }

        $currentMaxSort = (int) $product
            ->terms()
            ->max('sort_order');

        foreach ($template->items as $index => $item) {

            $product->terms()->create([
                'title' => $item->title,
                'content' => $item->content,
                'scope' => $item->scope,
                'sort_order' => $currentMaxSort + $index + 1,
                'status' => 'active',
            ]);
        }

        return redirect()
            ->route('products.workspace', [
                'id' => $product->id,
                'tab' => 10,
                'mode' => 'edit',
            ])
            ->with(
                'success',
                'Template applied successfully.'
            );
    }
}