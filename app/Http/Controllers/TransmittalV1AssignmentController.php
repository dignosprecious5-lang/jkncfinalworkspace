<?php

namespace App\Http\Controllers;

use App\Models\Transmittal;
use App\Models\TransmittalV1AssignmentHistory;
use App\Models\User;
use App\Services\TransmittalV1Authorization;
use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransmittalV1AssignmentController extends Controller
{
    public function update(Request $request, Transmittal $transmittal)
    {
        abort_unless(TransmittalV1Authorization::canManageAssignments($request->user()), 403);
        $transmittal = TransmittalWorkflowBoundary::v1Query()->findOrFail($transmittal->getKey());
        $validated = $request->validate([
            'assignment_type' => ['required', 'in:preparer,reviewer'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $column = $validated['assignment_type'] === 'preparer'
            ? 'v1_preparer_user_id'
            : 'v1_assigned_reviewer_id';
        $assignee = isset($validated['assignee_id']) ? User::findOrFail($validated['assignee_id']) : null;

        if ($validated['assignment_type'] === 'reviewer' && $assignee && ! $assignee->hasPermission('approve_corporate')) {
            throw ValidationException::withMessages(['assignee_id' => 'The selected reviewer does not hold approve_corporate.']);
        }

        DB::transaction(function () use ($transmittal, $validated, $column, $assignee): void {
            $locked = TransmittalWorkflowBoundary::v1Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail();
            $previous = $locked->{$column};
            $next = $assignee?->getKey();

            if ($validated['assignment_type'] === 'reviewer'
                && $next !== null
                && (string) $next === (string) $locked->v1_preparer_user_id) {
                throw ValidationException::withMessages(['assignee_id' => 'A preparer cannot be assigned as reviewer of their own record.']);
            }

            if ($validated['assignment_type'] === 'preparer'
                && $next !== null
                && (string) $next === (string) $locked->v1_assigned_reviewer_id) {
                throw ValidationException::withMessages(['assignee_id' => 'The assigned reviewer cannot also be the preparer.']);
            }

            if ((string) $previous === (string) $next) {
                throw ValidationException::withMessages(['assignee_id' => 'The assignment is unchanged.']);
            }

            $locked->forceFill([$column => $next])->saveOrFail();
            TransmittalV1AssignmentHistory::create([
                'transmittal_id' => $locked->getKey(),
                'assignment_type' => $validated['assignment_type'],
                'action' => $next === null ? 'revoked' : ($previous === null ? 'assigned' : 'reassigned'),
                'actor_user_id' => Auth::id(),
                'previous_assignee_id' => $previous,
                'new_assignee_id' => $next,
                'reason' => trim($validated['reason']),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'V1 assignment updated and recorded in the audit history.');
    }
}
