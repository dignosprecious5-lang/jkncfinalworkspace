<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Service;
use App\Models\ClientRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProposalController extends Controller
{
    /**
     * Display a listing of client proposals & contracts with search and filters.
     */
    public function index(Request $request)
    {
        $query = Proposal::with(['service.activeVersion']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('proposal_code', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('client_email', 'like', "%{$search}%");
            });
        }

        $proposals = $query->latest()->get();
        $services = Service::with('activeVersion')->get();

        return view('proposals.index', compact('proposals', 'services'));
    }

    /**
     * Store a new proposal auto-populating default specifications from Service Workspace
     * AND auto-generating associated client requirements & operations instantiation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name'         => 'required|string|max:255',
            'client_email'        => 'required|email|max:255',
            'service_id'          => 'required|exists:services,id',
            'proposed_price'      => 'nullable|numeric|min:0',
            'valid_until'         => 'nullable|date',
            'notes'               => 'nullable|string',
            'engagement_behavior' => 'nullable|string',
        ]);

        $service = Service::with('activeVersion.requirements')->findOrFail($request->service_id);
        $activeVersion = $service->activeVersion ?? null;

        $proposalCode = 'PROP-' . strtoupper(Str::random(6));
        $validated['proposal_code'] = $proposalCode;
        $validated['token'] = (string) Str::uuid();

        if (empty($validated['proposed_price'])) {
            $validated['proposed_price'] = $activeVersion->standard_price ?? $service->price ?? 0.00;
        }

        $validated['service_version_id']   = $activeVersion->id ?? null;
        $validated['pricing_model']        = $activeVersion->pricing_model ?? 'Fixed Fee';
        $validated['payment_terms']        = $activeVersion->payment_terms ?? 'Full Advance';
        $validated['deliverables_summary'] = $activeVersion->deliverables ?? null;
        
        // Default status is accepted to immediately trigger operations & engagements
        $validated['status']               = 'accepted';

        // 1. Save Proposal
        $proposal = Proposal::create($validated);

        // 2. AUTO-INSTANTIATE OPERATIONS (Creates Engagements and Operational Tasks)
        $this->instantiateOperationsFromProposal($proposal);

        // 3. WIRING TO CLIENT REQUIREMENTS
        if ($activeVersion && $activeVersion->requirements && $activeVersion->requirements->count() > 0) {
            foreach ($activeVersion->requirements as $reqTemplate) {
                $docName = $reqTemplate->document_name 
                        ?? $reqTemplate->name 
                        ?? $reqTemplate->requirement_name 
                        ?? $reqTemplate->title 
                        ?? ('Required Document - ' . $proposalCode);

                $reqData = [
                    'service_id'    => $service->id,
                    'document_name' => $docName,
                    'description'   => 'Required for Proposal ' . $proposalCode . ' (' . $proposal->client_name . ')',
                    'is_mandatory'  => $reqTemplate->is_mandatory ?? true,
                    'status'        => 'pending',
                ];

                if (Schema::hasColumn('client_requirements', 'client_type')) {
                    $reqData['client_type'] = $reqTemplate->client_type ?? 'All';
                }
                if (Schema::hasColumn('client_requirements', 'source')) {
                    $reqData['source'] = $reqTemplate->source ?? 'Client-supplied';
                }

                ClientRequirement::create($reqData);
            }
        } else {
            // Fallback default requirement
            $reqData = [
                'service_id'    => $service->id,
                'document_name' => 'Initial Document Compliance - ' . $proposalCode,
                'description'   => 'Auto-generated document checklist for client: ' . $proposal->client_name,
                'is_mandatory'  => true,
                'status'        => 'pending',
            ];

            if (Schema::hasColumn('client_requirements', 'client_type')) {
                $reqData['client_type'] = 'All';
            }
            if (Schema::hasColumn('client_requirements', 'source')) {
                $reqData['source'] = 'Client-supplied';
            }

            ClientRequirement::create($reqData);
        }

        return redirect()->route('proposals.index')->with('success', 'Proposal created successfully & automatically converted to Active Engagement!');
    }

    /**
     * Display the specified proposal details.
     */
    public function show(Proposal $proposal)
    {
        $proposal->load(['service.activeVersion.requirements', 'service.activeVersion.mainActivities.subActivities']);
        return view('proposals.show', compact('proposal'));
    }

    /**
     * Update the proposal status.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,contracted',
        ]);

        $proposal = Proposal::findOrFail($id);
        $newStatus = strtolower($request->input('status'));

        // 1. Update proposal status
        $proposal->update(['status' => $newStatus]);

        // 2. Trigger Operational Instantiation when accepted or contracted
        if (in_array($newStatus, ['accepted', 'contracted'])) {
            $this->instantiateOperationsFromProposal($proposal);
        }

        return redirect()->route('proposals.index')->with('success', 'Proposal status updated successfully!');
    }

    /**
     * SECTION 10: AUTOMATIC OPERATIONAL INSTANTIATION
     * Guarantees Operational Tasks creation even if service has no predefined activities.
     */
    private function instantiateOperationsFromProposal(Proposal $proposal)
    {
        if (empty($proposal->service_id)) {
            return;
        }

        $service = Service::with('activeVersion.mainActivities.subActivities')->find($proposal->service_id);
        $activeVersion = $service->activeVersion ?? null;
        $activeVersionId = $activeVersion->id ?? $proposal->service_version_id ?? 1;
        
        // Ensure valid type constraint for engagements table
        $rawType = strtolower($service->engagement_behavior ?? 'project');
        $engagementType = in_array($rawType, ['project', 'recurring', 'one_time']) ? $rawType : 'project';
        $recurrenceRule = $activeVersion->activity_frequency ?? 'One-time';
        
        $serviceSnapshotJson = json_encode([
            'service_name' => $service->name ?? 'Service',
            'pricing'      => $proposal->proposed_price ?? 0,
            'client_name'  => $proposal->client_name ?? 'Client',
        ]);

        if (Schema::hasTable('engagements')) {
            try {
                if (DB::getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF;');
                }

                $engagementData = [
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('engagements', 'service_snapshot')) {
                    $engagementData['service_snapshot'] = $serviceSnapshotJson;
                }
                if (Schema::hasColumn('engagements', 'deliverables_snapshot')) {
                    $engagementData['deliverables_snapshot'] = json_encode($activeVersion->deliverables ?? []);
                }
                if (Schema::hasColumn('engagements', 'pricing_snapshot')) {
                    $engagementData['pricing_snapshot'] = json_encode(['price' => $proposal->proposed_price ?? 0]);
                }
                if (Schema::hasColumn('engagements', 'engagement_code')) {
                    $engagementData['engagement_code'] = 'ENG-' . strtoupper(Str::random(6));
                }
                if (Schema::hasColumn('engagements', 'start_date')) {
                    $engagementData['start_date'] = now()->toDateString();
                }
                if (Schema::hasColumn('engagements', 'service_version_id')) {
                    $engagementData['service_version_id'] = $activeVersionId;
                }
                if (Schema::hasColumn('engagements', 'service_id')) {
                    $engagementData['service_id'] = $service->id;
                }
                if (Schema::hasColumn('engagements', 'client_name')) {
                    $engagementData['client_name'] = $proposal->client_name ?? 'Client';
                }
                if (Schema::hasColumn('engagements', 'title')) {
                    $engagementData['title'] = ($service->name ?? 'Service') . ' - ' . ($proposal->client_name ?? 'Engagement');
                }
                if (Schema::hasColumn('engagements', 'type')) {
                    $engagementData['type'] = $engagementType;
                }
                if (Schema::hasColumn('engagements', 'recurrence_rule')) {
                    $engagementData['recurrence_rule'] = $recurrenceRule;
                }
                if (Schema::hasColumn('engagements', 'status')) {
                    $engagementData['status'] = 'active';
                }

                $engagementId = DB::table('engagements')->insertGetId($engagementData);

                // ==========================================
                // INSERT OPERATIONAL EXECUTION TASKS
                // ==========================================
                if (Schema::hasTable('operational_tasks')) {
                    $hasInsertedActivities = false;

                    if ($activeVersion && $activeVersion->mainActivities && $activeVersion->mainActivities->count() > 0) {
                        foreach ($activeVersion->mainActivities as $mainAct) {
                            $mainTaskData = [
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            if (Schema::hasColumn('operational_tasks', 'engagement_id')) {
                                $mainTaskData['engagement_id'] = $engagementId;
                            }
                            if (Schema::hasColumn('operational_tasks', 'service_activity_id')) {
                                $mainTaskData['service_activity_id'] = $mainAct->id;
                            }
                            if (Schema::hasColumn('operational_tasks', 'title')) {
                                $mainTaskData['title'] = $mainAct->name ?? 'Activity Task';
                            }
                            if (Schema::hasColumn('operational_tasks', 'description')) {
                                $mainTaskData['description'] = $mainAct->description ?? null;
                            }
                            if (Schema::hasColumn('operational_tasks', 'expected_days')) {
                                $mainTaskData['expected_days'] = $mainAct->expected_days ?? 1;
                            }
                            if (Schema::hasColumn('operational_tasks', 'expected_working_hours')) {
                                $mainTaskData['expected_working_hours'] = $mainAct->expected_working_hours ?? 8;
                            }
                            if (Schema::hasColumn('operational_tasks', 'is_mandatory')) {
                                $mainTaskData['is_mandatory'] = $mainAct->is_mandatory ?? true;
                            }
                            if (Schema::hasColumn('operational_tasks', 'is_billable')) {
                                $mainTaskData['is_billable'] = $mainAct->is_billable ?? true;
                            }
                            if (Schema::hasColumn('operational_tasks', 'status')) {
                                $mainTaskData['status'] = 'pending';
                            }

                            $mainTaskId = DB::table('operational_tasks')->insertGetId($mainTaskData);
                            $hasInsertedActivities = true;

                            $subActivities = $mainAct->subActivities ?? collect([]);
                            foreach ($subActivities as $subAct) {
                                $subTaskData = [
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];

                                if (Schema::hasColumn('operational_tasks', 'engagement_id')) {
                                    $subTaskData['engagement_id'] = $engagementId;
                                }
                                if (Schema::hasColumn('operational_tasks', 'parent_task_id')) {
                                    $subTaskData['parent_task_id'] = $mainTaskId;
                                }
                                if (Schema::hasColumn('operational_tasks', 'service_activity_id')) {
                                    $subTaskData['service_activity_id'] = $subAct->id;
                                }
                                if (Schema::hasColumn('operational_tasks', 'title')) {
                                    $subTaskData['title'] = $subAct->name ?? 'Sub-Activity Task';
                                }
                                if (Schema::hasColumn('operational_tasks', 'description')) {
                                    $subTaskData['description'] = $subAct->description ?? null;
                                }
                                if (Schema::hasColumn('operational_tasks', 'expected_days')) {
                                    $subTaskData['expected_days'] = $subAct->expected_days ?? 1;
                                }
                                if (Schema::hasColumn('operational_tasks', 'expected_working_hours')) {
                                    $subTaskData['expected_working_hours'] = $subAct->expected_working_hours ?? 8;
                                }
                                if (Schema::hasColumn('operational_tasks', 'is_mandatory')) {
                                    $subTaskData['is_mandatory'] = $subAct->is_mandatory ?? true;
                                }
                                if (Schema::hasColumn('operational_tasks', 'is_billable')) {
                                    $subTaskData['is_billable'] = $subAct->is_billable ?? true;
                                }
                                if (Schema::hasColumn('operational_tasks', 'status')) {
                                    $subTaskData['status'] = 'pending';
                                }

                                DB::table('operational_tasks')->insert($subTaskData);
                            }
                        }
                    }

                    // Automatic Fallback Default Tasks if no predefined activities exist
                    if (!$hasInsertedActivities) {
                        $defaultTasks = [
                            [
                                'title'       => 'Initial Client Onboarding & Requirement Handoff',
                                'description' => 'Verify accepted proposal documents and initiate client kick-off meeting for ' . ($proposal->client_name ?? 'Client') . '.',
                                'days'        => 2,
                                'hours'       => 16,
                            ],
                            [
                                'title'       => 'Service Delivery & Execution Phase',
                                'description' => 'Execute core service deliverables specified in proposal contract.',
                                'days'        => 5,
                                'hours'       => 40,
                            ],
                            [
                                'title'       => 'Final Output Review & Client Handoff',
                                'description' => 'Review completed deliverables and obtain formal client sign-off.',
                                'days'        => 1,
                                'hours'       => 8,
                            ],
                        ];

                        foreach ($defaultTasks as $defTask) {
                            $taskData = [
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            if (Schema::hasColumn('operational_tasks', 'engagement_id')) {
                                $taskData['engagement_id'] = $engagementId;
                            }
                            if (Schema::hasColumn('operational_tasks', 'title')) {
                                $taskData['title'] = $defTask['title'];
                            }
                            if (Schema::hasColumn('operational_tasks', 'description')) {
                                $taskData['description'] = $defTask['description'];
                            }
                            if (Schema::hasColumn('operational_tasks', 'expected_days')) {
                                $taskData['expected_days'] = $defTask['days'];
                            }
                            if (Schema::hasColumn('operational_tasks', 'expected_working_hours')) {
                                $taskData['expected_working_hours'] = $defTask['hours'];
                            }
                            if (Schema::hasColumn('operational_tasks', 'is_billable')) {
                                $taskData['is_billable'] = true;
                            }
                            if (Schema::hasColumn('operational_tasks', 'status')) {
                                $taskData['status'] = 'pending';
                            }

                            DB::table('operational_tasks')->insert($taskData);
                        }
                    }
                }

                if (DB::getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = ON;');
                }
            } catch (\Exception $e) {
                Log::warning('Standard instantiation skipped: ' . $e->getMessage());
            }
        }
    }

    /**
     * Remove the specified proposal from storage.
     */
    public function destroy($id)
    {
        $proposal = Proposal::findOrFail($id);
        $proposal->delete();

        return redirect()->route('proposals.index')->with('success', 'Proposal deleted successfully.');
    }
}