<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ServiceWorkspaceController extends Controller
{
    /**
     * Ipakita ang pahina ng workspace.
     */
    public function show($id)
    {
        $service = Service::with([
            'versions' => function ($query) {
                $query->latest();
            },
            'activeVersion.requirements',
            'activeVersion.mainActivities.subActivities',
            'auditLogs.user'
        ])->findOrFail($id);

        $activeVersion = $service->activeVersion 
            ?? $service->versions()->latest()->first() 
            ?? new ServiceVersion(['version_number' => 'V1.0']);

        return view('ordo-workspace', compact('service', 'activeVersion'));
    }

    /**
     * Update/Save Logic para sa Version at Workspace Tabs (kabilang ang Overview).
     */
    public function updateVersion(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $tab = $request->input('tab', 'overview');
        $tabFormatted = ucwords(str_replace('_', ' ', $tab));

        // Kunin o lumikha ng Active Version
        $version = $service->activeVersion 
            ?? $service->versions()->latest()->first() 
            ?? new ServiceVersion(['service_id' => $service->id, 'version_number' => 'V1.0']);

        // 1. OVERVIEW TAB BACKEND LOGIC
        if ($tab === 'overview') {
            $validated = $request->validate([
                'short_name'           => 'nullable|string|max:255',
                'expected_turnaround'  => 'nullable|string|max:100',
                'effective_date'       => 'nullable|date',
                'internal_description' => 'required|string',
                'client_description'   => 'nullable|string',
                'about_service'        => 'required|string',
                'purpose'              => 'nullable|string',
                'when_to_use'          => 'nullable|string',
                'what_it_is_not'       => 'nullable|string',
            ]);

            // Update version record
            $version->fill($validated);
            $service->versions()->save($version);

            // Update main service alias/short_name kung ibinigay
            if ($request->filled('short_name')) {
                $service->update(['short_name' => $request->short_name]);
            }
        } else {
            // Generic update para sa iba pang tabs kung dynamic ang submit
            $version->update($request->except(['_token', '_method', 'tab', 'next_tab', 'mode']));
        }

        // Auto-change status: Kapag in-update ang incomplete draft, gawing DRAFT
        if ($service->status === 'INCOMPLETE') {
            $service->update(['status' => 'DRAFT']);
        }

        // Dynamic description para sa Audit Log
        $logDescription = match ($tab) {
            'overview'         => 'In-update ang Service Overview, Short Name, Effective Date, at SLAs.',
            'proposal_content' => 'Binago ang Scope of Work, Deliverables, at Exclusions.',
            'requirements'     => 'Nag-update ng Operational Requirements at Client Checklists.',
            'workflow'         => 'Inayos ang Main/Sub-Activity Workflow sequences at hours.',
            'commercials'      => 'Binago ang Pricing Model, Standard Price, o Payment Terms.',
            'engagement'       => 'In-update ang Engagement Delivery Behavior at Recurrence Cadence.',
            'reporting'        => 'Inayos ang Reporting Frequency at Content Scope.',
            'automation'       => 'Binago ang Workflow Automation at Escalation rules.',
            'terms'            => 'Nag-update ng Inherited Terms at Conditions Clauses.',
            default            => "Nagkaroon ng mga pagbabago sa mga detalye sa {$tabFormatted} section.",
        };

        // Automatic Audit Log entry wiring
        AuditLog::create([
            'service_id'  => $service->id,
            'user_id'     => auth()->id(),
            'title'       => "Updated {$tabFormatted}",
            'description' => $logDescription,
        ]);

        // Redirection papunta sa susunod na tab
        $nextTab = $request->input('next_tab', $tab);
        $mode = $request->input('mode', 'edit');

        return redirect()
            ->route('services.workspace', [
                'service' => $service->id,
                'tab'     => $nextTab,
                'mode'    => $mode,
            ])
            ->with('success', "Nai-save at na-record nang maayos ang mga pagbabago sa {$tabFormatted}!");
    }

    /**
     * Submit for Approval Action (Kapag pinihit ang Submit button).
     */
    public function submitApproval(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        // Palitan ang status ng service patungong PENDING_APPROVAL
        $service->update(['status' => 'PENDING_APPROVAL']);

        // Mag-record sa Audit Log
        AuditLog::create([
            'service_id'  => $service->id,
            'user_id'     => auth()->id(),
            'title'       => 'Submitted for Approval',
            'description' => 'Ipinasa ang buong service workspace para sa Manager Approval.',
        ]);

        // Kumuha ng redirection target (Dashboard/Services Index)
        $redirectTo = $request->input('redirect_to');

        if ($redirectTo) {
            return redirect($redirectTo)->with('success', 'Ang service ay matagumpay na naipasa para sa approval!');
        }

        return redirect()->route('services.index')->with('success', 'Ang service ay matagumpay na naipasa para sa approval!');
    }
}