<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\TownHallCommunication;
use Illuminate\Support\Facades\Storage;
use App\Models\TownHallAcknowledgement;
use App\Models\Contact;
use Carbon\Carbon;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use App\Mail\TownHallPostedNotification;
use App\Mail\TownHallApprovalRequestNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;

class TownHallController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->hasPermission('access_townhall')) {
            abort(403, 'Unauthorized');
        }

        $query = TownHallCommunication::with('recipientUser')
            ->where('approval_status', 'Approved');

        if (Schema::hasColumn('townhall_communications', 'is_archived')) {
            $query->where('is_archived', false);
        }

        // All approved active memos appear in the Town Hall list.
        // Memos not intended for the current user are censored in the Blade table.
        // Direct opening is still protected in show() through canUserViewCommunication().

        if ($request->filled('department')) {
            $query->where('department_stakeholder', $request->department);
        }

        $communications = $query->latest()->paginate(10);
        $todayAttendance = app(AttendanceController::class)->currentClockAttendance(Auth::user());

        $departmentQuery = TownHallCommunication::query();
        if (Schema::hasColumn('townhall_communications', 'is_archived')) {
            $departmentQuery->where('is_archived', false);
        }
        $departments = $departmentQuery->select('department_stakeholder')
            ->distinct()
            ->pluck('department_stakeholder');

        $employees = User::where('role', 'Employee')
            ->orderBy('name')
            ->get();

        $usersForRecipients = User::whereIn('role', [
            'Employee',
            'employee',
            'Admin',
            'admin',
            'SuperAdmin',
            'superadmin',
            'super admin',
            'System Super Admin',
            'system super admin',
        ])
            ->orderBy('name')
            ->get();

        $contactsForRecipients = Contact::orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $managementApprovers = $this->activeEmployeeApprovers();
        $executiveApprover = $this->resolveExecutiveApprover();
        return view('townhall.townhall', compact(
            'communications',
            'departments',
            'employees',
            'todayAttendance',
            'usersForRecipients',
            'contactsForRecipients',
            'managementApprovers',
            'executiveApprover'
        ));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('create_townhall')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'communication_date' => ['nullable', 'date'],
            'department_stakeholder' => ['nullable', 'string', 'max:1000'],
            'recipient_label' => ['nullable', 'in:To,For'],
            'to_for' => ['nullable', 'string', 'max:255'],
            'recipient_type' => ['nullable', 'in:all,employee,all_admins,all_clients,all_users'],
            'recipient_user_ids' => ['nullable', 'array'],
            'recipient_user_ids.*' => ['exists:users,id'],
            'recipient_contact_ids' => ['nullable', 'array'],
            'recipient_contact_ids.*' => ['exists:contacts,id'],
            'priority' => ['nullable', 'in:High,Low'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'cc' => ['nullable', 'string', 'max:255'],
            'additional' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:5120'],
            'expires_at' => ['nullable', 'date'],
            'management_approver_id' => ['required', 'integer'],
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            if (!$file->isValid()) {
                return back()
                    ->withErrors(['attachment' => 'The attachment failed to upload.'])
                    ->withInput();
            }

            $validated['attachment'] = $file->store('townhall_attachments', 'public');
        }

        $validated = $this->normalizeRecipientFields($validated, $request);
        $validated = array_merge($validated, $this->buildApprovalData($request->input('management_approver_id')));

        // Default to today's date when Add Communication is submitted without a date.
        // The field is still editable from the form.
        $validated['communication_date'] = $validated['communication_date'] ?? now()->format('Y-m-d');

        $validated['from_name'] = Auth::user()->name;
        $validated['priority'] = $request->priority ?? 'Low';
        $validated['created_by'] = Auth::id();
        $validated['approval_status'] = 'Pending Approval';
        $validated['workflow_status'] = 'Submitted';
        $validated['status'] = 'Pending Approval';
        $validated['submitted_at'] = now();
        $validated['is_archived'] = false;
        $validated['archived_at'] = null;

        $communication = TownHallCommunication::create($validated);

        $communication->ref_no = 'MEMO-' . str_pad((string) $communication->id, 5, '0', STR_PAD_LEFT);
        $communication->save();
        $communication->refresh();

        $this->notifyPendingApprover($communication, 'management');

        return redirect()
            ->route('townhall')
            ->with('success', 'Communication submitted for approval workflow. The Level 1 approver was notified by email.');
    }

    public function department(Request $request)
    {
        $departmentQuery = TownHallCommunication::query();
        if (Schema::hasColumn('townhall_communications', 'is_archived')) {
            $departmentQuery->where('is_archived', false);
        }
        $departments = $departmentQuery->select('department_stakeholder')
            ->distinct()
            ->pluck('department_stakeholder');

        $query = TownHallCommunication::where('approval_status', 'Approved');
        if (Schema::hasColumn('townhall_communications', 'is_archived')) {
            $query->where('is_archived', false);
        }

        if ($request->filled('department')) {
            $query->where('department_stakeholder', $request->department);
        }

        $communications = $query->latest()->get();

        return view('townhall.department', compact('communications', 'departments'));
    }

    public function attachments(Request $request)
    {
        if (!Auth::user()->hasPermission('access_townhall')) {
            abort(403, 'Unauthorized');
        }

        $query = TownHallCommunication::whereNotNull('attachment')
            ->where('approval_status', 'Approved');

        if (Schema::hasColumn('townhall_communications', 'is_archived')) {
            $query->where('is_archived', false);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('ref_no', 'like', "%{$search}%")
                    ->orWhere('from_name', 'like', "%{$search}%")
                    ->orWhere('department_stakeholder', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $type = $request->type;

            if ($type === 'image') {
                $query->where(function ($q) {
                    $q->where('attachment', 'like', '%.jpg')
                        ->orWhere('attachment', 'like', '%.jpeg')
                        ->orWhere('attachment', 'like', '%.png')
                        ->orWhere('attachment', 'like', '%.gif')
                        ->orWhere('attachment', 'like', '%.webp');
                });
            } elseif ($type === 'pdf') {
                $query->where('attachment', 'like', '%.pdf');
            } elseif ($type === 'document') {
                $query->where(function ($q) {
                    $q->where('attachment', 'like', '%.doc')
                        ->orWhere('attachment', 'like', '%.docx');
                });
            }
        }

        $communications = $query->latest()->paginate(12)->withQueryString();

        return view('townhall.attachments', compact('communications'));
    }

    public function edit($id)
    {
        if (!Auth::user()->hasPermission('create_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::with('recipientUser')->findOrFail($id);

        if ($communication->created_by !== Auth::id()) {
            abort(403, 'You can only edit your own communication.');
        }

        if (
            !in_array($communication->approval_status, ['Draft', 'Needs Revision'], true)
            && !in_array((string) ($communication->workflow_status ?? ''), ['Draft', 'Needs Revision'], true)
        ) {
            abort(403, 'Only draft communications or communications returned for revision can be edited.');
        }

        $employees = User::where('role', 'Employee')
            ->orderBy('name')
            ->get();

        $usersForRecipients = User::whereIn('role', [
            'Employee',
            'employee',
            'Admin',
            'admin',
            'SuperAdmin',
            'superadmin',
            'super admin',
            'System Super Admin',
            'system super admin',
        ])
            ->orderBy('name')
            ->get();

        $contactsForRecipients = Contact::orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $managementApprovers = $this->activeEmployeeApprovers();
        $executiveApprover = $this->resolveExecutiveApprover();
        return view('townhall.edit', compact(
            'communication',
            'employees',
            'usersForRecipients',
            'contactsForRecipients',
            'managementApprovers',
            'executiveApprover'
        ));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('create_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        if ($communication->created_by !== Auth::id()) {
            abort(403, 'You can only update your own communication.');
        }

        if (
            !in_array($communication->approval_status, ['Draft', 'Needs Revision'], true)
            && !in_array((string) ($communication->workflow_status ?? ''), ['Draft', 'Needs Revision'], true)
        ) {
            abort(403, 'Only draft communications or communications returned for revision can be updated.');
        }

        $validated = $request->validate([
            'communication_date' => ['nullable', 'date'],
            'department_stakeholder' => ['nullable', 'string', 'max:1000'],
            'recipient_label' => ['nullable', 'in:To,For'],
            'to_for' => ['nullable', 'string', 'max:255'],
            'recipient_type' => ['nullable', 'in:all,employee,all_admins,all_clients,all_users'],
            'recipient_user_ids' => ['nullable', 'array'],
            'recipient_user_ids.*' => ['exists:users,id'],
            'recipient_contact_ids' => ['nullable', 'array'],
            'recipient_contact_ids.*' => ['exists:contacts,id'],
            'priority' => ['nullable', 'in:High,Low'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'cc' => ['nullable', 'string', 'max:255'],
            'additional' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:5120'],
            'expires_at' => ['nullable', 'date'],
            'management_approver_id' => ['required', 'integer'],
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            if (!$file->isValid()) {
                return back()
                    ->withErrors(['attachment' => 'The attachment failed to upload.'])
                    ->withInput();
            }

            if ($communication->attachment && Storage::disk('public')->exists($communication->attachment)) {
                Storage::disk('public')->delete($communication->attachment);
            }

            $validated['attachment'] = $file->store('townhall_attachments', 'public');
        }

        $validated = $this->normalizeRecipientFields($validated, $request);
        $validated = array_merge($validated, $this->buildApprovalData($request->input('management_approver_id')));

        $validated['approval_status'] = 'Pending Approval';
        $validated['workflow_status'] = 'Submitted';
        $validated['status'] = 'Pending Approval';
        $validated['submitted_at'] = now();
        $validated['approved_by'] = null;
        $validated['approved_at'] = null;
        $validated['posted_at'] = null;
        $validated['posted_by'] = null;
        $validated['recipient_notified_at'] = null;
        $validated['approval_notes'] = null;
        $validated['is_archived'] = false;
        $validated['archived_at'] = null;

        $communication->update($validated);
        $communication->refresh();

        $this->notifyPendingApprover($communication, 'management');

        return redirect()
            ->route('townhall')
            ->with('success', 'Communication updated and resubmitted for approval. The Level 1 approver was notified by email.');
    }

    public function show($id)
    {
        if (!Auth::user()->hasPermission('access_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::with('recipientUser')->findOrFail($id);

        // If user is not admin approver, hide expired/archived memos completely
        if (!Auth::user()->hasPermission('approve_townhall')) {
            if ($communication->approval_status !== 'Approved' || $communication->is_archived) {
                abort(404);
            }

            if (!$this->canUserViewCommunication(Auth::user(), $communication)) {
                abort(404);
            }
        }

        $attachmentType = null;
        if ($communication->attachment) {
            $ext = strtolower(pathinfo($communication->attachment, PATHINFO_EXTENSION));

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'])) {
                $attachmentType = 'image';
            } elseif ($ext === 'pdf') {
                $attachmentType = 'pdf';
            } else {
                $attachmentType = 'file';
            }
        }

        $intendedUsers = $this->getAcknowledgementUsers($communication);
        $intendedUserIds = $intendedUsers->pluck('id')->values()->toArray();

        $acknowledgedUserIds = TownHallAcknowledgement::where('townhall_communication_id', $id)
            ->whereIn('user_id', $intendedUserIds)
            ->pluck('user_id')
            ->toArray();

        $acknowledgedUsers = $intendedUsers->whereIn('id', $acknowledgedUserIds)->values();
        $notAcknowledgedUsers = $intendedUsers->whereNotIn('id', $acknowledgedUserIds)->values();

        // Kept this variable name so the existing Blade file still works.
        // It now means total intended recipients, not only employees.
        $totalEmployees = $intendedUsers->count();
        $ackCount = count($acknowledgedUserIds);

        $progress = $totalEmployees > 0
            ? round(($ackCount / $totalEmployees) * 100)
            : 0;

        $hasAcknowledged = $communication->hasBeenAcknowledgedBy(Auth::id());

        $isIntendedRecipient = in_array(
            (int) Auth::id(),
            array_map('intval', $intendedUserIds),
            true
        );

        $requiresAcknowledgement = $communication->approval_status === 'Approved'
            && !$communication->is_archived
            && $isIntendedRecipient
            && !$hasAcknowledged;

        return view('townhall.show', compact(
            'communication',
            'attachmentType',
            'hasAcknowledged',
            'requiresAcknowledgement',
            'acknowledgedUsers',
            'notAcknowledgedUsers',
            'progress',
            'totalEmployees',
            'ackCount'
        ));
    }

    public function approve(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        if ($communication->is_archived) {
            abort(403, 'Archived communications cannot be approved.');
        }

        $notes = $request->input('approval_notes');
        $now = Carbon::now();

        if (Schema::hasColumn('townhall_communications', 'management_approval_status')) {
            if (($communication->management_approval_status ?? 'Pending') !== 'Approved') {
                $communication->update([
                    'management_approval_status' => 'Approved',
                    'management_approved_at' => $now,
                    'approval_status' => 'Level 1 Approved',
                    'workflow_status' => 'Pending Executive Approval',
                    'status' => 'Pending Executive Approval',
                    'approved_by' => Auth::id(),
                    'approved_at' => $now,
                    'approval_notes' => $notes,
                ]);

                $communication->refresh();
                $this->notifyPendingApprover($communication, 'executive');

                return redirect()->back()->with('success', 'Level 1 Management approval completed. The Executive Management approver was notified by email.');
            }

            if (($communication->executive_approval_status ?? 'Pending') !== 'Approved') {
                $communication->update([
                    'executive_approval_status' => 'Approved',
                    'executive_approved_at' => $now,
                    'approval_status' => 'Approved',
                    'workflow_status' => 'Posted',
                    'status' => 'Posted',
                    'posted_at' => $now,
                    'posted_by' => Auth::id(),
                    'approved_by' => Auth::id(),
                    'approved_at' => $now,
                    'approval_notes' => $notes,
                    'is_archived' => false,
                    'archived_at' => null,
                ]);

                $communication->refresh();

                $this->notifyRecipientsAfterPosting($communication);

                return redirect()->back()->with('success', 'Executive Management approval completed. Communication is now posted and recipients were notified.');
            }

            return redirect()->back()->with('success', 'This communication is already fully approved.');
        }

        $communication->update([
            'approval_status' => 'Approved',
            'workflow_status' => 'Posted',
            'status' => 'Posted',
            'posted_at' => $now,
            'posted_by' => Auth::id(),
            'approved_by' => Auth::id(),
            'approved_at' => $now,
            'approval_notes' => $notes,
        ]);

        $communication->refresh();

        $this->notifyRecipientsAfterPosting($communication);

        return redirect()->back()->with('success', 'Communication approved, posted, and recipients were notified.');
    }


    public function approveFromEmail(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired approval link.');
        }

        $communication = TownHallCommunication::findOrFail($id);
        $level = (string) $request->query('level', 'management');
        $approverUserId = (int) $request->query('approver', 0);

        if (!$this->isExpectedApprovalEmailApprover($communication, $level, $approverUserId)) {
            abort(403, 'This approval link is not assigned to this approver.');
        }

        if ($communication->is_archived) {
            abort(403, 'Archived communications cannot be approved.');
        }

        $now = Carbon::now();

        if ($level === 'management') {
            if (($communication->management_approval_status ?? 'Pending') === 'Approved') {
                return redirect()->route('townhall.show', $communication->id)
                    ->with('success', 'This communication already completed Level 1 approval.');
            }

            $communication->update([
                'management_approval_status' => 'Approved',
                'management_approved_at' => $now,
                'approval_status' => 'Level 1 Approved',
                'workflow_status' => 'Pending Executive Approval',
                'status' => 'Pending Executive Approval',
                'approved_by' => $approverUserId ?: null,
                'approved_at' => $now,
                'approval_notes' => 'Approved through email notification.',
            ]);

            $communication->refresh();
            $this->notifyPendingApprover($communication, 'executive');

            return redirect()->route('townhall.show', $communication->id)
                ->with('success', 'Level 1 approval completed through email. Executive Management was notified.');
        }

        if ($level === 'executive') {
            if (($communication->management_approval_status ?? 'Pending') !== 'Approved') {
                abort(403, 'Level 1 Management approval must be completed before Executive approval.');
            }

            if (($communication->executive_approval_status ?? 'Pending') === 'Approved') {
                return redirect()->route('townhall.show', $communication->id)
                    ->with('success', 'This communication is already posted.');
            }

            $communication->update([
                'executive_approval_status' => 'Approved',
                'executive_approved_at' => $now,
                'approval_status' => 'Approved',
                'workflow_status' => 'Posted',
                'status' => 'Posted',
                'posted_at' => $now,
                'posted_by' => $approverUserId ?: null,
                'approved_by' => $approverUserId ?: null,
                'approved_at' => $now,
                'approval_notes' => 'Approved through email notification.',
                'is_archived' => false,
                'archived_at' => null,
            ]);

            $communication->refresh();
            $this->notifyRecipientsAfterPosting($communication);

            return redirect()->route('townhall.show', $communication->id)
                ->with('success', 'Executive approval completed through email. Communication is now posted.');
        }

        abort(403, 'Invalid approval level.');
    }

    public function rejectFromEmail(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired rejection link.');
        }

        $communication = TownHallCommunication::findOrFail($id);
        $level = (string) $request->query('level', 'management');
        $approverUserId = (int) $request->query('approver', 0);

        if (!$this->isExpectedApprovalEmailApprover($communication, $level, $approverUserId)) {
            abort(403, 'This rejection link is not assigned to this approver.');
        }

        $communication->update([
            'approval_status' => 'Rejected',
            'workflow_status' => 'Rejected',
            'status' => 'Rejected',
            'management_approval_status' => $level === 'management' ? 'Rejected' : ($communication->management_approval_status ?? 'Approved'),
            'executive_approval_status' => $level === 'executive' ? 'Rejected' : 'Pending',
            'approved_by' => $approverUserId ?: null,
            'approved_at' => Carbon::now(),
            'approval_notes' => 'Rejected through email notification.',
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return redirect()->route('townhall.show', $communication->id)
            ->with('success', 'Communication rejected through email.');
    }

    public function reject(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        $communication->update([
            'approval_status' => 'Rejected',
            'workflow_status' => 'Rejected',
            'status' => 'Rejected',
            'management_approval_status' => 'Rejected',
            'executive_approval_status' => 'Pending',
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
            'approval_notes' => $request->input('approval_notes') ?: 'Rejected by approver.',
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return redirect()->back()->with('success', 'Communication rejected successfully. It will not appear in Town Hall.');
    }

    public function revise(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        $communication->update([
            'approval_status' => 'Needs Revision',
            'workflow_status' => 'Needs Revision',
            'status' => 'Needs Revision',
            'management_approval_status' => 'Pending',
            'executive_approval_status' => 'Pending',
            'management_approved_at' => null,
            'executive_approved_at' => null,
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
            'approval_notes' => $request->input('approval_notes') ?: 'Needs revision.',
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return redirect()->back()->with('success', 'Communication returned for revision.');
    }


    public function archive($id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        $communication->update([
            'is_archived' => true,
            'archived_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Communication archived successfully.');
    }

    public function unarchive($id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        $communication->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return redirect()->back()->with('success', 'Communication unarchived successfully.');
    }

    public function destroy($id)
    {
        if (!Auth::user()->hasPermission('create_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        if ($communication->attachment && Storage::disk('public')->exists($communication->attachment)) {
            Storage::disk('public')->delete($communication->attachment);
        }

        $communication->delete();

        return redirect()
            ->route('townhall')
            ->with('success', 'Communication deleted successfully.');
    }

    public function acknowledge($id)
    {
        if (!Auth::user()->hasPermission('access_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::with('recipientUser')->findOrFail($id);

        if ($communication->approval_status !== 'Approved' || $communication->is_archived) {
            abort(403, 'This communication is not available for acknowledgment.');
        }

        if (!$this->canUserViewCommunication(Auth::user(), $communication)) {
            abort(403, 'This communication is not assigned to you.');
        }

        $intendedUsers = $this->getAcknowledgementUsers($communication);

        $isIntendedRecipient = $intendedUsers
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->contains((int) Auth::id());

        if (!$isIntendedRecipient) {
            abort(403, 'Acknowledgment is only available for intended recipients.');
        }

        TownHallAcknowledgement::updateOrCreate(
            [
                'townhall_communication_id' => $communication->id,
                'user_id' => Auth::id(),
            ],
            [
                'acknowledged_at' => now(),
            ]
        );

        return redirect()->back()->with('success', 'Communication acknowledged successfully.');
    }

    public function downloadPdf($id)
    {
        $communication = TownHallCommunication::findOrFail($id);

        if ($communication->approval_status !== 'Approved' || $communication->is_archived) {
            abort(403, 'Only active approved communications can be downloaded.');
        }

        $pdf = Pdf::loadView('townhall.show-pdf', compact('communication'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->download($communication->ref_no . '.pdf');
    }


    public function humanCapitalMemos(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $user = Auth::user();

        $role = strtolower(trim((string) $user->role));

        $isAdmin = in_array($role, [
            'admin',
            'superadmin',
            'super admin',
            'system super admin',
        ]);

        $selectedEmployee = $request->employee_id;

        $employees = collect();

        $query = TownHallCommunication::with('recipientUser')
            ->where('approval_status', 'Approved');

        if (Schema::hasColumn('townhall_communications', 'is_archived')) {
            $query->where('is_archived', false);
        }

        if ($isAdmin) {
            $employees = User::where(function ($q) {
                $q->where('role', 'Employee')
                    ->orWhere('role', 'employee');
            })
                ->orderBy('name')
                ->get();

            if ($selectedEmployee) {
                $query->where(function ($q) use ($selectedEmployee) {
                    $q->where('recipient_type', 'all')
                        ->orWhere('recipient_type', 'all_users')
                        ->orWhere('recipient_user_id', $selectedEmployee)
                        ->orWhereJsonContains('recipient_user_ids', (int) $selectedEmployee);
                });
            }
        } else {
            /*
         * EMPLOYEE VIEW:
         * Employee can only see:
         * 1. Memos for all employees
         * 2. Memos specifically sent to their user ID
         */
            $query->where(function ($q) use ($user) {
                $q->where('recipient_type', 'all')
                    ->orWhere('recipient_type', 'all_users')
                    ->orWhere('recipient_user_id', $user->id)
                    ->orWhereJsonContains('recipient_user_ids', $user->id);
            });
        }

        $communications = $query->latest()->paginate(10)->withQueryString();

        return view('human-capital.memos', compact(
            'communications',
            'employees',
            'isAdmin',
            'selectedEmployee'
        ));
    }



    private function activeEmployeeApprovers()
    {
        if (!class_exists(Employee::class)) {
            return collect();
        }

        return Employee::query()
            ->whereNotNull('user_id')
            ->where(function ($query) {
                $query->whereNull('employment_status')
                    ->orWhereIn('employment_status', ['Active', 'active', 'Regular', 'regular', 'Probationary', 'probationary']);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) {
                return $this->formatEmployeeApprover($employee);
            })
            ->filter(fn($employee) => !empty($employee['name']))
            ->values();
    }

    private function buildApprovalData($managementApproverId): array
    {
        $management = $this->getEmployeeApproverData($managementApproverId);
        $executive = $this->resolveExecutiveApprover();

        return [
            'management_approver_id' => $management['id'] ?? null,
            'management_approver_user_id' => $management['user_id'] ?? null,
            'management_approver_name' => $management['name'] ?? null,
            'management_approver_position' => $management['position'] ?? null,
            'management_approver_department' => $management['department'] ?? null,
            'management_approval_status' => 'Pending',
            'management_approved_at' => null,

            'executive_approver_id' => $executive['id'] ?? null,
            'executive_approver_user_id' => $executive['user_id'] ?? null,
            'executive_approver_name' => $executive['name'] ?? 'John Kelly D. Abalde',
            'executive_approver_position' => $executive['position'] ?? 'President and CEO',
            'executive_approver_department' => $executive['department'] ?? 'Executive Management',
            'executive_approval_status' => 'Pending',
            'executive_approved_at' => null,
        ];
    }

    private function getEmployeeApproverData($employeeId): array
    {
        if (!$employeeId || !class_exists(Employee::class)) {
            return [];
        }

        $employee = Employee::find($employeeId);

        return $employee ? $this->formatEmployeeApprover($employee) : [];
    }

    private function resolveExecutiveApprover(): array
    {
        if (class_exists(Employee::class)) {
            $employee = Employee::query()
                ->where(function ($query) {
                    $query->whereNull('employment_status')
                        ->orWhereIn('employment_status', ['Active', 'active', 'Regular', 'regular', 'Probationary', 'probationary']);
                })
                ->where(function ($query) {
                    $query->where('position', 'like', '%President%')
                        ->orWhere('position', 'like', '%President and CEO%')
                        ->orWhere('position', 'like', '%CEO%');
                })
                ->latest('updated_at')
                ->first();

            if ($employee) {
                return $this->formatEmployeeApprover($employee);
            }
        }

        return [
            'id' => null,
            'user_id' => null,
            'name' => 'John Kelly D. Abalde',
            'position' => 'President and CEO',
            'department' => 'Executive Management',
        ];
    }

    private function formatEmployeeApprover($employee): array
    {
        $name = trim(collect([
            $employee->first_name ?? null,
            $employee->middle_name ?? null,
            $employee->last_name ?? null,
            $employee->suffix ?? null,
        ])->filter()->implode(' '));

        if (!$name && !empty($employee->user_id)) {
            $name = User::whereKey($employee->user_id)->value('name');
        }

        return [
            'id' => $employee->id,
            'user_id' => $employee->user_id,
            'name' => $name ?: 'Unnamed Employee',
            'position' => $employee->position ?: '—',
            'department' => $this->resolveDepartmentName($employee->department_id ?? null),
        ];
    }

    private function resolveDepartmentName($departmentId): string
    {
        if (!$departmentId) {
            return '—';
        }

        foreach (['departments', 'organizational_departments'] as $table) {
            if (Schema::hasTable($table)) {
                $record = DB::table($table)->where('id', $departmentId)->first();

                if ($record) {
                    return $record->name
                        ?? $record->department_name
                        ?? $record->title
                        ?? ('Department #' . $departmentId);
                }
            }
        }

        return 'Department #' . $departmentId;
    }

    private function normalizeRecipientFields(array $validated, Request $request): array
    {
        if (!Schema::hasColumn('townhall_communications', 'recipient_type')) {
            unset(
                $validated['recipient_type'],
                $validated['recipient_user_id'],
                $validated['recipient_user_ids'],
                $validated['recipient_contact_ids']
            );

            return $validated;
        }

        // Base group + extra specific recipients.
        // Example: recipient_type = all_admins, recipient_user_ids = [5, 9]
        // Display: All Admins, MJ Nicolai, Brian
        $recipientType = $request->input('recipient_type', 'all');
        $validated['recipient_type'] = $recipientType;

        $userIds = collect($request->input('recipient_user_ids', []))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $contactIds = collect($request->input('recipient_contact_ids', []))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $users = User::whereIn('id', $userIds)
            ->orderBy('name')
            ->get();

        $contacts = Contact::whereIn('id', $contactIds)
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => trim(collect([
                        $contact->first_name,
                        $contact->middle_name,
                        $contact->last_name,
                        $contact->name_extension,
                    ])->filter()->implode(' ')) ?: $contact->company_name,
                ];
            });

        $baseLabel = match ($recipientType) {
            'all_admins' => 'All Admins',
            'all_clients' => 'All Clients',
            'all_users' => 'All Users',
            'employee' => null,
            default => 'All Employees',
        };

        $recipientNames = collect();

        if ($baseLabel) {
            $recipientNames->push($baseLabel);
        }

        $recipientNames = $recipientNames
            ->merge($users->pluck('name'))
            ->merge($contacts->pluck('name'))
            ->filter()
            ->unique()
            ->values();

        $validated['recipient_user_id'] = $users->first()?->id;
        $validated['recipient_user_ids'] = $users->pluck('id')->values()->toArray();
        $validated['recipient_contact_ids'] = $contacts->pluck('id')->values()->toArray();
        $validated['to_for'] = $recipientNames->implode(', ');

        return $validated;
    }

    private function applyRecipientVisibility($query, User $user): void
    {
        if ($user->hasPermission('approve_townhall')) {
            return;
        }

        if (
            Schema::hasColumn('townhall_communications', 'recipient_type')
            && Schema::hasColumn('townhall_communications', 'recipient_user_id')
        ) {
            $query->where(function ($q) use ($user) {
                $role = strtolower(trim((string) $user->role));

                $q->where('recipient_type', 'all_users')
                    ->orWhere('recipient_user_id', $user->id)
                    ->orWhereJsonContains('recipient_user_ids', $user->id);

                if ($role === 'employee') {
                    $q->orWhere('recipient_type', 'all');
                }

                if (in_array($role, ['admin', 'superadmin', 'super admin', 'system super admin'], true)) {
                    $q->orWhere('recipient_type', 'all_admins');
                }

                if (in_array($role, ['client', 'customer'], true)) {
                    $q->orWhere('recipient_type', 'all_clients');
                }

                $q->orWhere(function ($legacy) use ($user) {
                    $legacy->whereNull('recipient_type')
                        ->where(function ($old) use ($user) {
                            $old->where('to_for', 'like', '%' . $user->name . '%')
                                ->orWhere('to_for', 'like', '%All%')
                                ->orWhere('to_for', 'like', '%Everyone%')
                                ->orWhere('to_for', 'like', '%All Employees%');
                        });
                });
            });

            return;
        }

        $query->where(function ($q) use ($user) {
            $q->where('to_for', 'like', '%' . $user->name . '%')
                ->orWhere('to_for', 'like', '%All%')
                ->orWhere('to_for', 'like', '%Everyone%')
                ->orWhere('to_for', 'like', '%All Employees%');
        });
    }

    private function applyRecipientFilterForEmployee($query, User $employee): void
    {
        if (
            Schema::hasColumn('townhall_communications', 'recipient_type')
            && Schema::hasColumn('townhall_communications', 'recipient_user_id')
        ) {
            $query->where(function ($q) use ($employee) {
                $q->where('recipient_type', 'all')
                    ->orWhere('recipient_type', 'all_users')
                    ->orWhere('recipient_user_id', $employee->id)
                    ->orWhereJsonContains('recipient_user_ids', $employee->id)
                    ->orWhere(function ($legacy) use ($employee) {
                        $legacy->whereNull('recipient_type')
                            ->where(function ($old) use ($employee) {
                                $old->where('to_for', 'like', '%' . $employee->name . '%')
                                    ->orWhere('to_for', 'like', '%All%')
                                    ->orWhere('to_for', 'like', '%Everyone%')
                                    ->orWhere('to_for', 'like', '%All Employees%');
                            });
                    });
            });

            return;
        }

        $query->where(function ($q) use ($employee) {
            $q->where('to_for', 'like', '%' . $employee->name . '%')
                ->orWhere('to_for', 'like', '%All%')
                ->orWhere('to_for', 'like', '%Everyone%')
                ->orWhere('to_for', 'like', '%All Employees%');
        });
    }

    private function canUserViewCommunication(User $user, TownHallCommunication $communication): bool
    {
        if ($user->hasPermission('approve_townhall')) {
            return true;
        }

        if (
            Schema::hasColumn('townhall_communications', 'recipient_type')
            && Schema::hasColumn('townhall_communications', 'recipient_user_id')
        ) {
            $role = strtolower(trim((string) $user->role));

            if ($communication->recipient_type === 'all_users') {
                return true;
            }

            if ($communication->recipient_type === 'all' && $role === 'employee') {
                return true;
            }

            if ($communication->recipient_type === 'all_admins' && in_array($role, ['admin', 'superadmin', 'super admin', 'system super admin'], true)) {
                return true;
            }

            if ($communication->recipient_type === 'all_clients' && in_array($role, ['client', 'customer'], true)) {
                return true;
            }

            if ((int) $communication->recipient_user_id === (int) $user->id) {
                return true;
            }

            $recipientIds = $communication->recipient_user_ids ?? [];

            if (is_array($recipientIds) && in_array((int) $user->id, array_map('intval', $recipientIds), true)) {
                return true;
            }

            // If this is a new structured recipient record and none of the rules above matched,
            // do not fall through to legacy text matching. This prevents "All Clients" from being visible to all login users.
            if (!is_null($communication->recipient_type)) {
                return false;
            }
        }

        $toFor = strtolower((string) $communication->to_for);
        $userName = strtolower((string) $user->name);

        return str_contains($toFor, $userName)
            || str_contains($toFor, 'all')
            || str_contains($toFor, 'everyone')
            || str_contains($toFor, 'all employees');
    }

    private function getAcknowledgementUsers(TownHallCommunication $communication)
    {
        $recipientType = $communication->recipient_type ?? 'all';

        $adminRoles = [
            'admin',
            'superadmin',
            'super admin',
            'system super admin',
        ];

        $clientRoles = [
            'client',
            'customer',
        ];

        $baseUsers = collect();

        if (in_array($recipientType, ['all', 'all_employees'], true)) {
            $baseUsers = User::whereRaw('LOWER(role) = ?', ['employee'])
                ->orderBy('name')
                ->get();
        }

        if ($recipientType === 'all_admins') {
            $baseUsers = User::whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(role)'), $adminRoles)
                ->orderBy('name')
                ->get();
        }

        if ($recipientType === 'all_clients') {
            $baseUsers = User::whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(role)'), $clientRoles)
                ->orderBy('name')
                ->get();
        }

        if ($recipientType === 'all_users') {
            $baseUsers = User::orderBy('name')->get();
        }

        $extraUserIds = collect($communication->recipient_user_ids ?? [])
            ->push($communication->recipient_user_id)
            ->filter()
            ->unique()
            ->values();

        $extraUsers = collect();

        if ($extraUserIds->isNotEmpty()) {
            $extraUsers = User::whereIn('id', $extraUserIds)
                ->orderBy('name')
                ->get();
        }

        /*
         * Contacts/clients from recipient_contact_ids can only acknowledge
         * if they also have a user login account with the same email.
         */
        $contactUserAccounts = collect();

        $contactIds = collect($communication->recipient_contact_ids ?? [])
            ->filter()
            ->unique()
            ->values();

        if ($contactIds->isNotEmpty()) {
            $contactEmails = Contact::whereIn('id', $contactIds)
                ->whereNotNull('email')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values();

            if ($contactEmails->isNotEmpty()) {
                $contactUserAccounts = User::whereIn('email', $contactEmails)
                    ->orderBy('name')
                    ->get();
            }
        }

        return $baseUsers
            ->merge($extraUsers)
            ->merge($contactUserAccounts)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }



    private function notifyPendingApprover(TownHallCommunication $communication, string $level): void
    {
        $approver = $this->resolveApprovalNotificationUser($communication, $level);

        if (!$approver || empty($approver->email)) {
            Log::warning('TownHall approval notification skipped because no approver email was found.', [
                'communication_id' => $communication->id,
                'ref_no' => $communication->ref_no,
                'level' => $level,
            ]);

            return;
        }

        try {
            $pdfBinary = Pdf::loadView('townhall.show-pdf', compact('communication'))
                ->setPaper('a4', 'portrait')
                ->output();

            $filename = ($communication->ref_no ?: 'townhall-approval-request') . '.pdf';

            Mail::to($approver->email)->send(
                new TownHallApprovalRequestNotification($communication, $pdfBinary, $filename, $level, (int) $approver->id)
            );
        } catch (\Throwable $e) {
            Log::error('TownHall approval notification failed.', [
                'communication_id' => $communication->id,
                'ref_no' => $communication->ref_no,
                'level' => $level,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveApprovalNotificationUser(TownHallCommunication $communication, string $level): ?User
    {
        $userId = null;

        if ($level === 'management') {
            $userId = $communication->management_approver_user_id;

            if (!$userId && $communication->management_approver_id && class_exists(Employee::class)) {
                $userId = Employee::whereKey($communication->management_approver_id)->value('user_id');
            }
        }

        if ($level === 'executive') {
            $userId = $communication->executive_approver_user_id;

            if (!$userId && $communication->executive_approver_id && class_exists(Employee::class)) {
                $userId = Employee::whereKey($communication->executive_approver_id)->value('user_id');
            }
        }

        return $userId ? User::find($userId) : null;
    }

    private function isExpectedApprovalEmailApprover(TownHallCommunication $communication, string $level, int $approverUserId): bool
    {
        if ($approverUserId <= 0) {
            return false;
        }

        $expectedApprover = $this->resolveApprovalNotificationUser($communication, $level);

        return $expectedApprover && (int) $expectedApprover->id === $approverUserId;
    }

    private function notifyRecipientsAfterPosting(TownHallCommunication $communication): void
    {
        if (!Schema::hasColumn('townhall_communications', 'recipient_notified_at')) {
            return;
        }

        if (!is_null($communication->recipient_notified_at)) {
            return;
        }

        $recipients = $this->getNotificationRecipients($communication);

        if ($recipients->isEmpty()) {
            $communication->update([
                'recipient_notified_at' => Carbon::now(),
            ]);

            return;
        }

        try {
            $pdfBinary = Pdf::loadView('townhall.show-pdf', compact('communication'))
                ->setPaper('a4', 'portrait')
                ->output();

            $filename = ($communication->ref_no ?: 'townhall-communication') . '.pdf';

            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(
                    new TownHallPostedNotification($communication, $pdfBinary, $filename)
                );
            }

            $communication->update([
                'recipient_notified_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('TownHall recipient notification failed.', [
                'communication_id' => $communication->id,
                'ref_no' => $communication->ref_no,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function getNotificationRecipients(TownHallCommunication $communication)
    {
        return $this->getAcknowledgementUsers($communication)
            ->filter(fn($user) => !empty($user->email))
            ->unique('email')
            ->values();
    }


    public function searchRecipients(Request $request)
    {
        if (!Auth::user()->hasPermission('create_townhall')) {
            abort(403, 'Unauthorized');
        }

        $search = trim((string) $request->get('q', ''));
        $limit = (int) $request->get('limit', 8);
        $limit = $limit > 0 ? min($limit, 15) : 8;

        $contactsQuery = \App\Models\Contact::query();
        $usersQuery = \App\Models\User::query();

        if ($search !== '') {
            $contactsQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search . '%')
                    ->orWhere('middle_name', 'like', $search . '%')
                    ->orWhere('last_name', 'like', $search . '%')
                    ->orWhere('company_name', 'like', $search . '%')
                    ->orWhere('email', 'like', $search . '%')
                    ->orWhereRaw(
                        "CONCAT_WS(' ', first_name, middle_name, last_name, name_extension) LIKE ?",
                        [$search . '%']
                    )
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });

            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', $search . '%')
                    ->orWhere('email', 'like', $search . '%')
                    ->orWhere('role', 'like', $search . '%')
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $contacts = $contactsQuery
            ->limit($limit)
            ->get()
            ->map(function ($contact) {
                $fullName = trim(collect([
                    $contact->first_name,
                    $contact->middle_name,
                    $contact->last_name,
                    $contact->name_extension,
                ])->filter()->implode(' '));

                return [
                    'id' => $contact->id,
                    'type' => 'contact',
                    'role' => 'Client',
                    'name' => $fullName,
                    'email' => $contact->email,
                    'company_name' => $contact->company_name,
                    'display' => $fullName . ' — Client' . ($contact->company_name ? ' • ' . $contact->company_name : ''),
                ];
            });

        $users = $usersQuery
            ->whereIn('role', ['Employee', 'Admin', 'SuperAdmin'])
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'type' => 'user',
                    'role' => $user->role,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_name' => null,
                    'display' => $user->name . ' — ' . $user->role,
                ];
            });

        $results = $contacts
            ->concat($users)
            ->take($limit)
            ->values();

        return response()->json($results);
    }
}
