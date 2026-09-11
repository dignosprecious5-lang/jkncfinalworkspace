<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\StartRecord;
use App\Models\StartEngagementGroup;
use App\Models\EngagementAssignment;
use Illuminate\Http\Request;

class StartController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | START Workspace
    |--------------------------------------------------------------------------
    */

    public function show($dealId)
    {
        $deal = Deal::findOrFail($dealId);

        /*
        |--------------------------------------------------------------------------
        | Find or create START record
        |--------------------------------------------------------------------------
        */

        $start = StartRecord::firstOrCreate(
            [
                'deal_id' => $deal->id,
            ],
            [
                'status' => 'Draft',
                'memo_status' => 'Draft',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Load START structure
        |--------------------------------------------------------------------------
        |
        | START
        |   └── Engagement Groups
        |          └── Assignments
        |
        */

        $start->load([
            'engagementGroups.assignments',
        ]);

        return view(
            'starts.show',
            compact(
                'deal',
                'start'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Engagement Group
    |--------------------------------------------------------------------------
    */

    public function storeGroup(Request $request, $dealId)
    {
        $deal = Deal::findOrFail($dealId);

        /*
        |--------------------------------------------------------------------------
        | Find or create START record
        |--------------------------------------------------------------------------
        */

        $start = StartRecord::firstOrCreate(
            [
                'deal_id' => $deal->id,
            ],
            [
                'status' => 'Draft',
                'memo_status' => 'Draft',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Engagement Group
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'engagement_type' => [
                'required',
                'in:Regular Engagement,Project Engagement',
            ],

            'group_name' => [
                'required',
                'string',
                'max:255',
            ],

            'title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'scope_deliverables' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'target_end_date' => [
                'nullable',
                'date',
            ],

            'duration_days' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'service_frequency' => [
                'nullable',
                'string',
                'max:255',
            ],

            'billing_frequency' => [
                'nullable',
                'string',
                'max:255',
            ],

            'reporting_frequency' => [
                'nullable',
                'string',
                'max:255',
            ],

            'project_manager' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Attach Group to START
        |--------------------------------------------------------------------------
        */

        $validated['start_record_id'] = $start->id;

        /*
        |--------------------------------------------------------------------------
        | New Groups Start as Draft
        |--------------------------------------------------------------------------
        */

        $validated['status'] = 'Draft';

        /*
        |--------------------------------------------------------------------------
        | Create Engagement Group
        |--------------------------------------------------------------------------
        */

        StartEngagementGroup::create($validated);

        /*
        |--------------------------------------------------------------------------
        | START moves into Structuring once a group exists
        |--------------------------------------------------------------------------
        */

        if ($start->status === 'Draft') {
            $start->update([
                'status' => 'Structuring',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Return to START Workspace
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'deals.start',
                ['id' => $deal->id]
            )
            ->with(
                'success',
                'Engagement group created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Engagement Group
    |--------------------------------------------------------------------------
    */

    public function updateGroup(
        Request $request,
        $dealId,
        $groupId
    ) {
        /*
        |--------------------------------------------------------------------------
        | Find Deal
        |--------------------------------------------------------------------------
        */

        $deal = Deal::findOrFail($dealId);

        /*
        |--------------------------------------------------------------------------
        | Find START record belonging to this Deal
        |--------------------------------------------------------------------------
        */

        $start = StartRecord::where(
            'deal_id',
            $deal->id
        )->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Find Engagement Group belonging to this START
        |--------------------------------------------------------------------------
        */

        $group = StartEngagementGroup::where(
            'id',
            $groupId
        )
            ->where(
                'start_record_id',
                $start->id
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Validate Engagement Group
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'engagement_type' => [
                'required',
                'in:Regular Engagement,Project Engagement',
            ],

            'group_name' => [
                'required',
                'string',
                'max:255',
            ],

            'title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'scope_deliverables' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'target_end_date' => [
                'nullable',
                'date',
            ],

            'duration_days' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'service_frequency' => [
                'nullable',
                'string',
                'max:255',
            ],

            'billing_frequency' => [
                'nullable',
                'string',
                'max:255',
            ],

            'reporting_frequency' => [
                'nullable',
                'string',
                'max:255',
            ],

            'project_manager' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Engagement Group
        |--------------------------------------------------------------------------
        */

        $group->update($validated);

        /*
        |--------------------------------------------------------------------------
        | Return to START Workspace
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'deals.start',
                ['id' => $deal->id]
            )
            ->with(
                'success',
                'Engagement group updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Engagement Assignment
    |--------------------------------------------------------------------------
    */

    public function storeAssignment(
        Request $request,
        $dealId,
        $groupId
    ) {
        /*
        |--------------------------------------------------------------------------
        | Find Deal
        |--------------------------------------------------------------------------
        */

        $deal = Deal::findOrFail($dealId);

        /*
        |--------------------------------------------------------------------------
        | Find START record belonging to this Deal
        |--------------------------------------------------------------------------
        */

        $start = StartRecord::where(
            'deal_id',
            $deal->id
        )->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Find Engagement Group belonging to this START
        |--------------------------------------------------------------------------
        */

        $group = StartEngagementGroup::where(
            'id',
            $groupId
        )
            ->where(
                'start_record_id',
                $start->id
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Validate Assignment
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                'max:255',
            ],

            'assigned_to' => [
                'required',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'in:Assigned,Pending,Reassigned,Completed',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Attach Assignment to Engagement Group
        |--------------------------------------------------------------------------
        */

        $validated['start_engagement_group_id'] = $group->id;

        /*
        |--------------------------------------------------------------------------
        | Create Assignment
        |--------------------------------------------------------------------------
        */

        EngagementAssignment::create($validated);

        /*
        |--------------------------------------------------------------------------
        | Return to START Workspace
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'deals.start',
                ['id' => $deal->id]
            )
            ->with(
                'success',
                'Engagement assignment created successfully.'
            );
    }
}