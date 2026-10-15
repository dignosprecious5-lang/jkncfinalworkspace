<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Proposal;
use App\Models\ClientRequirement;
use App\Models\Engagement;
use App\Models\OperationalTask;

class ClientPortalController extends Controller
{
    // 1. Ipakita ang Client Portal View
    public function showProposal($token)
    {
        $proposal = Proposal::where('token', $token)
            ->orWhere('id', $token)
            ->firstOrFail();

        // Kunin ang lahat ng kaugnay na requirement records
        $requirements = ClientRequirement::where('proposal_id', $proposal->id)
            ->orWhere('service_id', $proposal->service_id)
            ->get();

        if ($requirements->isEmpty()) {
            $requirements = ClientRequirement::all();
        }

        return view('clients.portal', compact('proposal', 'requirements', 'token'));
    }

    // 2. Tanggapin ang Proposal (One-Click Accept)
    public function acceptProposal(Request $request, $token)
    {
        $proposal = Proposal::where('token', $token)
            ->orWhere('id', $token)
            ->firstOrFail();

        $proposal->status = 'ACCEPTED';

        if (Schema::hasColumn('proposals', 'accepted_at')) {
            $proposal->accepted_at = now();
        }

        if (Schema::hasColumn('proposals', 'client_signature')) {
            $proposal->client_signature = $request->input('signature_name', $proposal->client_name);
        }

        $proposal->save();

        $engagementData = [
            'client_name' => $proposal->client_name ?? 'Client',
            'status'      => 'Active',
            'type'        => 'Project',
        ];

        if (Schema::hasColumn('engagements', 'proposal_id')) {
            $engagementData['proposal_id'] = $proposal->id;
        }

        if (Schema::hasColumn('engagements', 'title')) {
            $engagementData['title'] = $proposal->title ?? $proposal->service_name ?? 'Client Engagement';
        }

        if (Schema::hasColumn('engagements', 'service_snapshot')) {
            $engagementData['service_snapshot'] = json_encode($proposal);
        }

        $engagement = Engagement::create($engagementData);

        $taskData = [
            'engagement_id' => $engagement->id,
            'title'         => 'Client Requirement Verification & Kickoff',
            'description'   => 'Verify uploaded client requirements and schedule engagement kickoff.',
            'status'        => 'pending',
        ];

        if (Schema::hasColumn('operational_tasks', 'expected_days')) {
            $taskData['expected_days'] = 2;
        }

        if (Schema::hasColumn('operational_tasks', 'expected_working_hours')) {
            $taskData['expected_working_hours'] = 16;
        }

        if (Schema::hasColumn('operational_tasks', 'is_billable')) {
            $taskData['is_billable'] = true;
        }

        OperationalTask::create($taskData);

        return redirect()->back()->with('success', 'Thank you! You have successfully accepted and signed the proposal.');
    }

    // 3. I-upload ang Requirements (WITH STRICT ROW MATCHING)
    public function uploadRequirement(Request $request, $token)
    {
        $request->validate([
            'document' => 'required|file|max:20480'
        ]);

        $proposal = Proposal::where('token', $token)
            ->orWhere('id', $token)
            ->firstOrFail();

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $file->getClientOriginalName());
            $path = $file->storeAs('client_requirements', $filename, 'public');

            $requirement = null;

            // 1. Unang hanapin gamit ang requirement_id mula sa hidden form input
            if ($request->filled('requirement_id')) {
                $requirement = ClientRequirement::find($request->requirement_id);
            }

            // 2. Fallback: Match gamit ang proposal_id, service_id, o pattern sa document_name
            if (!$requirement) {
                $requirement = ClientRequirement::where('proposal_id', $proposal->id)
                    ->orWhere('service_id', $proposal->service_id)
                    ->orWhere('document_name', 'LIKE', '%PROP-%')
                    ->first();
            }

            // 3. Fallback: Kunin ang kauna-unahang record sa table
            if (!$requirement) {
                $requirement = ClientRequirement::first();
            }

            // 4. Kung wala pa rin, gumawa ng panibagong record
            if (!$requirement) {
                $requirement = new ClientRequirement();
                $requirement->document_name = 'Initial Document Compliance - PROP-' . $proposal->id;
            }

            // Direct Force Assignment sa mga kailangang columns
            $requirement->file_path = $path;
            $requirement->status = 'SUBMITTED';

            if (Schema::hasColumn('client_requirements', 'proposal_id')) {
                $requirement->proposal_id = $proposal->id;
            }

            if (Schema::hasColumn('client_requirements', 'service_id')) {
                $requirement->service_id = $proposal->service_id;
            }

            if (Schema::hasColumn('client_requirements', 'submitted_at')) {
                $requirement->submitted_at = now();
            }

            $requirement->save();
        }

        return redirect()->back()->with('success', 'Document uploaded successfully!');
    }
}