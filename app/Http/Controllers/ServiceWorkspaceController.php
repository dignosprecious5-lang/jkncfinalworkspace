<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Service;
use App\Models\ServiceVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ServiceWorkspaceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(function ($request, $next) {
            $user = $request->user();
            abort_unless(
                $user && ($user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('access_services')),
                403
            );

            if (! $request->isMethod('GET')) {
                abort_unless(
                    $user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('create_sales_marketing'),
                    403
                );
            }

            return $next($request);
        })];
    }

    public function show($id)
    {
        $service = Service::with([
            'versions' => fn ($query) => $query->latest(),
            'activeVersion.requirements',
            'activeVersion.mainActivities.subActivities',
            'auditLogs.user',
        ])->findOrFail($id);

        $activeVersion = $service->activeVersion
            ?? $service->versions()->latest()->first()
            ?? new ServiceVersion(['version_number' => 'V1.0']);

        return view('ordo-workspace', compact('service', 'activeVersion'));
    }

    public function updateVersion(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $tab = (string) $request->input('tab', 'overview');
        $tabFormatted = ucwords(str_replace('_', ' ', $tab));

        $version = $service->activeVersion
            ?? $service->versions()->latest()->first()
            ?? new ServiceVersion(['service_id' => $service->id, 'version_number' => 'V1.0']);

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

            $version->fill($validated);
            $service->versions()->save($version);

            if ($request->filled('short_name')) {
                $service->update(['short_name' => $validated['short_name']]);
            }
        } else {
            // Explicit validated allowlist instead of unsafe $request->except(...) mass assignment.
            $validated = $request->validate([
                'scope_of_work'                  => 'nullable|string',
                'deliverables'                   => 'nullable|string',
                'client_responsibilities'        => 'nullable|string',
                'exclusions'                     => 'nullable|string',
                'pricing_model'                  => 'nullable|string|max:100',
                'standard_price'                 => 'nullable|numeric|min:0',
                'minimum_price'                  => 'nullable|numeric|min:0',
                'max_cap'                        => 'nullable|numeric|min:0',
                'max_discount_without_approval'  => 'nullable|numeric|min:0|max:100',
                'payment_terms'                  => 'nullable|string|max:255',
                'recurrence_frequency'           => 'nullable|string|max:100',
                'reporting_frequency'            => 'nullable|string|max:100',
                'notification_channel'           => 'nullable|string|max:50',
                'client_reminder_cadence'        => 'nullable|string|max:50',
                'internal_reminder_days'         => 'nullable|integer|min:0|max:365',
                'send_due_reminders'             => 'nullable|boolean',
                'auto_send_due_reminders'        => 'nullable|boolean',
                'escalation_target_role'         => 'nullable|string|max:100',
            ]);

            $version->fill($validated);
            $service->versions()->save($version);
        }

        if (strtoupper((string) $service->status) === 'INCOMPLETE') {
            $service->update(['status' => 'draft']);
        }

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

        if (class_exists(AuditLog::class)) {
            AuditLog::create([
                'service_id'  => $service->id,
                'user_id'     => auth()->id(),
                'title'       => "Updated {$tabFormatted}",
                'description' => $logDescription,
            ]);
        }

        return back()->with('success', "Successfully updated {$tabFormatted}.");
    }
}
