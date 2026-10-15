<?php

namespace App\Http\Controllers;

use App\Models\ServiceTerm;
use App\Models\ServiceVersion;
use App\Models\TermsTemplate;
use Illuminate\Http\Request;

class ServiceTermController extends Controller
{
    /**
     * Store a new Term & Agreement for a Service Version.
     */
    public function store(Request $request, ServiceVersion $serviceVersion)
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scope'   => ['required', 'in:service_specific,global,service_area,category'],
        ]);

        $nextSortOrder = (int) $serviceVersion->serviceTerms()->max('sort_order') + 1;

        $serviceVersion->serviceTerms()->create([
            'title'      => $validated['title'],
            'content'    => $validated['content'],
            'scope'      => $validated['scope'],
            'sort_order' => $nextSortOrder,
            'status'     => 'active',
        ]);

        return back()->with('success', 'Term added successfully.');
    }

    /**
     * Update an existing Service Term.
     */
    public function update(Request $request, ServiceTerm $serviceTerm)
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scope'   => ['required', 'in:service_specific,global,service_area,category'],
        ]);

        $serviceTerm->update($validated);

        return back()->with('success', 'Term updated successfully.');
    }

    /**
     * Duplicate an existing Service Term.
     */
    public function duplicate(ServiceVersion $serviceVersion, $serviceTerm)
    {
        $term = ServiceTerm::find($serviceTerm);
        
        if (!$term && class_exists(\App\Models\ServiceTermLibrary::class)) {
            $term = \App\Models\ServiceTermLibrary::find($serviceTerm);
        }

        if (!$term) {
            return back()->with('error', 'Term not found for duplication.');
        }

        $nextSortOrder = (int) $serviceVersion->serviceTerms()->max('sort_order') + 1;

        $serviceVersion->serviceTerms()->create([
            'title'      => ($term->title ?? $term->name ?? 'Duplicated Term') . ' (Copy)',
            'content'    => $term->content ?? $term->body_text ?? '',
            'scope'      => 'service_specific',
            'sort_order' => $nextSortOrder,
            'status'     => 'active',
        ]);

        return back()->with('success', 'Term duplicated successfully.');
    }

    /**
     * Disable a Service Term (soft status change).
     */
    public function disable($serviceTerm)
    {
        $termRecord = ServiceTerm::find($serviceTerm);

        if ($termRecord) {
            $termRecord->update(['status' => 'inactive']);
        } elseif (class_exists(\App\Models\ServiceTermLibrary::class)) {
            $libraryTerm = \App\Models\ServiceTermLibrary::find($serviceTerm);
            if ($libraryTerm) {
                $libraryTerm->update(['status' => 'inactive']);
            }
        }

        return back()->with('success', 'Term disabled successfully.');
    }

    /**
     * Completely delete a Service Term so it disappears instantly.
     */
    public function destroy($serviceTerm)
    {
        $termRecord = ServiceTerm::find($serviceTerm);

        if ($termRecord) {
            $termRecord->delete();
        }

        return back()->with('success', 'Term deleted successfully.');
    }

    /**
     * Save selected active terms as a reusable template.
     */
    public function storeTemplate(Request $request, ServiceVersion $serviceVersion)
    {
        $validated = $request->validate([
            'template_name' => ['required', 'string', 'max:255'],
            'term_ids'      => ['required', 'array'],
            'term_ids.*'    => ['exists:service_terms,id'],
        ]);

        $template = TermsTemplate::create([
            'name'   => $validated['template_name'],
            'status' => 'active',
        ]);

        $terms = ServiceTerm::whereIn('id', $validated['term_ids'])->get();

        foreach ($terms as $index => $term) {
            $template->items()->create([
                'title'      => $term->title,
                'content'    => $term->content,
                'scope'      => $term->scope,
                'sort_order' => $index + 1,
            ]);
        }

        return back()->with('success', 'Terms template created successfully.');
    }

    /**
     * Apply an existing template to the Service Version.
     */
    public function applyTemplate(Request $request, ServiceVersion $serviceVersion)
    {
        $validated = $request->validate([
            'template_id' => ['required', 'exists:terms_templates,id'],
            'apply_mode'  => ['required', 'in:add,replace'],
        ]);

        $template = TermsTemplate::with('items')->findOrFail($validated['template_id']);

        if ($validated['apply_mode'] === 'replace') {
            $serviceVersion->serviceTerms()->update(['status' => 'inactive']);
        }

        $currentMaxSort = (int) $serviceVersion->serviceTerms()->max('sort_order');

        foreach ($template->items as $index => $item) {
            $serviceVersion->serviceTerms()->create([
                'title'      => $item->title,
                'content'    => $item->content,
                'scope'      => $item->scope,
                'sort_order' => $currentMaxSort + $index + 1,
                'status'     => 'active',
            ]);
        }

        return back()->with('success', 'Template applied successfully.');
    }

    /**
     * Disable a Terms Template (soft status change).
     */
    public function disableTemplate(TermsTemplate $termsTemplate)
    {
        $termsTemplate->update(['status' => 'inactive']);

        return back()->with('success', 'Terms template disabled successfully.');
    }
}