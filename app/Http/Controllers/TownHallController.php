<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\TownHallCommunication;
use App\Models\TownHallApprovalAudit;
use Illuminate\Support\Facades\Storage;
use App\Models\TownHallAcknowledgement;
use App\Models\Contact;
use Carbon\Carbon;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use App\Mail\TownHallPostedNotification;
use App\Mail\TownHallApprovalRequestNotification;
use App\Mail\TownHallAcknowledgedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
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

        if (Schema::hasColumn('townhall_communications', 'workflow_status')) {
            $query->where('workflow_status', 'Posted');
        }

        if (Schema::hasColumn('townhall_communications', 'posted_at')) {
            $query->whereNotNull('posted_at');
        }

        if (Schema::hasColumn('townhall_communications', 'is_archived')) {
            $query->where('is_archived', false);
        }

        // Only posted active memos appear in the Town Hall list.
        // Memos not intended for the current user are censored in the Blade table.
        // Direct opening is still protected in show() through canUserViewCommunication().

        if ($request->filled('department')) {
            $query->where('department_stakeholder', $request->department);
        }

        if (Schema::hasColumn('townhall_communications', 'posted_at')) {
            $query->orderByDesc('posted_at');
        } else {
            $query->latest();
        }

        $communications = $query->paginate(10);
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

        $creatorEmployee = class_exists(Employee::class)
            ? Employee::where('user_id', Auth::id())->first()
            : null;

        $creatorPosition = $creatorEmployee?->position ?: 'Position';
        $creatorDepartment = $this->resolveDepartmentName($creatorEmployee?->department_id ?? null);

        return view('townhall.townhall', compact(
            'communications',
            'departments',
            'employees',
            'todayAttendance',
            'usersForRecipients',
            'contactsForRecipients',
            'managementApprovers',
            'executiveApprover',
            'creatorPosition',
            'creatorDepartment'
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

        $this->recordTownHallAudit(
            $communication,
            'Submitted',
            'Level 1 - From Management',
            Auth::id(),
            'Pending Approval',
            'Communication submitted and routed to Level 1 Management approver.'
        );

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

        $this->recordTownHallAudit(
            $communication,
            'Resubmitted',
            'Level 1 - From Management',
            Auth::id(),
            'Pending Approval',
            'Communication revised and resubmitted for approval.'
        );

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

        if (
            $communication->approval_status === 'Approved'
            && !$communication->is_archived
            && $isIntendedRecipient
        ) {
            $acknowledgement = TownHallAcknowledgement::firstOrCreate(
                [
                    'townhall_communication_id' => $communication->id,
                    'user_id' => Auth::id(),
                ],
                [
                    'viewed_at' => now(),
                ]
            );

            if (is_null($acknowledgement->viewed_at)) {
                $acknowledgement->update([
                    'viewed_at' => now(),
                ]);
            }

            if ($acknowledgement->wasRecentlyCreated || $acknowledgement->wasChanged('viewed_at')) {
                $this->recordTownHallAudit(
                    $communication,
                    'Viewed',
                    'Recipient Tracking',
                    Auth::id(),
                    'Viewed',
                    'Recipient viewed the communication.'
                );
            }
        }

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

                $this->recordTownHallAudit(
                    $communication,
                    'Approved',
                    'Level 1 - From Management',
                    Auth::id(),
                    'Level 1 Approved',
                    $notes
                );

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

                $this->recordTownHallAudit(
                    $communication,
                    'Approved and Posted',
                    'Level 2 - From Executive Management',
                    Auth::id(),
                    'Posted',
                    $notes
                );

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

        $this->recordTownHallAudit(
            $communication,
            'Approved and Posted',
            'Approval',
            Auth::id(),
            'Posted',
            $notes
        );

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

            $this->recordTownHallAudit(
                $communication,
                'Approved',
                'Level 1 - From Management',
                $approverUserId,
                'Level 1 Approved',
                'Approved through email notification.'
            );

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

            $this->recordTownHallAudit(
                $communication,
                'Approved and Posted',
                'Level 2 - From Executive Management',
                $approverUserId,
                'Posted',
                'Approved through email notification.'
            );

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

        $communication->refresh();

        $this->recordTownHallAudit(
            $communication,
            'Rejected',
            $level === 'executive' ? 'Level 2 - From Executive Management' : 'Level 1 - From Management',
            $approverUserId,
            'Rejected',
            'Rejected through email notification.'
        );

        return redirect()->route('townhall.show', $communication->id)
            ->with('success', 'Communication rejected through email.');
    }

    public function reject(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        $remarks = $request->input('approval_notes') ?: 'Rejected by approver.';

        $communication->update([
            'approval_status' => 'Rejected',
            'workflow_status' => 'Rejected',
            'status' => 'Rejected',
            'management_approval_status' => 'Rejected',
            'executive_approval_status' => 'Pending',
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
            'approval_notes' => $remarks,
            'is_archived' => false,
            'archived_at' => null,
        ]);

        $communication->refresh();

        $this->recordTownHallAudit(
            $communication,
            'Rejected',
            'Approval',
            Auth::id(),
            'Rejected',
            $remarks
        );

        return redirect()->back()->with('success', 'Communication rejected successfully. It will not appear in Town Hall.');
    }

    public function revise(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        $remarks = $request->input('approval_notes') ?: 'Needs revision.';

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
            'approval_notes' => $remarks,
            'is_archived' => false,
            'archived_at' => null,
        ]);

        $communication->refresh();

        $this->recordTownHallAudit(
            $communication,
            'Returned for Revision',
            'Approval',
            Auth::id(),
            'Needs Revision',
            $remarks
        );

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

        $communication->refresh();

        $this->recordTownHallAudit(
            $communication,
            'Archived',
            'Records Management',
            Auth::id(),
            'Archived',
            'Communication archived.'
        );

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

        $communication->refresh();

        $this->recordTownHallAudit(
            $communication,
            'Unarchived',
            'Records Management',
            Auth::id(),
            'Unarchived',
            'Communication unarchived.'
        );

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

    public function acknowledge(Request $request, $id)
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

        $acknowledgement = TownHallAcknowledgement::firstOrNew([
            'townhall_communication_id' => $communication->id,
            'user_id' => Auth::id(),
        ]);

        if (!is_null($acknowledgement->acknowledged_at)) {
            return redirect()->back()->with('success', 'You already acknowledged this communication.');
        }

        $employee = class_exists(Employee::class)
            ? Employee::where('user_id', Auth::id())->first()
            : null;

        $userAgent = (string) $request->userAgent();
        $browserInfo = $this->detectBrowser($userAgent);
        $operatingSystem = $this->detectOperatingSystem($userAgent);
        $deviceInformation = $this->detectDeviceInformation($userAgent);

        if (is_null($acknowledgement->viewed_at)) {
            $acknowledgement->viewed_at = now();
        }

        $acknowledgement->fill([
            'recipient_name' => Auth::user()->name,
            'recipient_position' => $employee?->position,
            'recipient_department' => $this->resolveDepartmentName($employee?->department_id ?? null),
            'user_account_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'device_information' => $deviceInformation,
            'browser_information' => $browserInfo,
            'operating_system' => $operatingSystem,
            'communication_ref_no' => $communication->ref_no,
            'session_id' => $request->session()?->getId(),
            'acknowledgement_status' => 'Acknowledged',
            'acknowledged_at' => now(),
        ]);

        $acknowledgement->save();

        $this->recordTownHallAudit(
            $communication,
            'Acknowledged',
            'Recipient Tracking',
            Auth::id(),
            'Acknowledged',
            'Recipient acknowledged the communication from IP ' . $request->ip()
        );

        $this->notifyAcknowledgementStakeholders($communication, $acknowledgement);

        return redirect()->back()->with('success', 'Communication acknowledged successfully.');
    }


    private function notifyAcknowledgementStakeholders(
        TownHallCommunication $communication,
        TownHallAcknowledgement $acknowledgement
    ): void {
        $recipients = $this->getAcknowledgementNotificationRecipients($communication);

        if ($recipients->isEmpty()) {
            return;
        }

        $summary = $this->buildAcknowledgementNotificationSummary($communication);

        try {
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(
                    new TownHallAcknowledgedNotification($communication, $acknowledgement, $summary)
                );
            }
        } catch (\Throwable $e) {
            Log::error('TownHall acknowledgement notification failed.', [
                'communication_id' => $communication->id,
                'ref_no' => $communication->ref_no,
                'acknowledgement_id' => $acknowledgement->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function getAcknowledgementNotificationRecipients(TownHallCommunication $communication)
    {
        $userIds = collect();

        if ($communication->created_by) {
            $userIds->push((int) $communication->created_by);
        }

        $managementApprover = $this->resolveApprovalNotificationUser($communication, 'management');
        if ($managementApprover) {
            $userIds->push((int) $managementApprover->id);
        }

        $executiveApprover = $this->resolveApprovalNotificationUser($communication, 'executive');
        if ($executiveApprover) {
            $userIds->push((int) $executiveApprover->id);
        }

        if ($communication->approved_by) {
            $userIds->push((int) $communication->approved_by);
        }

        return User::whereIn('id', $userIds->filter()->unique()->values())
            ->whereNotNull('email')
            ->get()
            ->filter(fn($user) => !empty($user->email))
            ->unique('email')
            ->values();
    }

    private function buildAcknowledgementNotificationSummary(TownHallCommunication $communication): array
    {
        $intendedUsers = $this->getAcknowledgementUsers($communication);
        $intendedUserIds = $intendedUsers
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values();

        $requiredCount = $intendedUserIds->count();

        $acknowledgedCount = TownHallAcknowledgement::where('townhall_communication_id', $communication->id)
            ->whereIn('user_id', $intendedUserIds)
            ->whereNotNull('acknowledged_at')
            ->count();

        $pendingCount = max($requiredCount - $acknowledgedCount, 0);

        return [
            'required_count' => $requiredCount,
            'acknowledged_count' => $acknowledgedCount,
            'pending_count' => $pendingCount,
            'percentage' => $requiredCount > 0
                ? round(($acknowledgedCount / $requiredCount) * 100)
                : 0,
        ];
    }



    private function buildTownHallPdf(
        TownHallCommunication $communication,
        ?int $totalPages = null,
        ?string $dateGenerated = null
    ) {
        $dateGenerated = $dateGenerated ?: now()->format('F d, Y h:i A');

        return Pdf::loadView('townhall.show-pdf', compact('communication', 'totalPages', 'dateGenerated'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);
    }

    private function resolveTownHallPdfPageCount(
        TownHallCommunication $communication,
        string $dateGenerated
    ): int {
        /*
        |--------------------------------------------------------------------------
        | Two-pass total page count
        |--------------------------------------------------------------------------
        | CSS counter(page) works for the current page. CSS counter(pages) caused
        | "0". Inline PHP caused blank PDF in your DomPDF setup. So we first render
        | once to get DomPDF's real page count, then render the final PDF with that
        | number passed into the Blade as $totalPages.
        */
        $previewPdf = $this->buildTownHallPdf($communication, null, $dateGenerated);
        $previewDomPdf = $previewPdf->getDomPDF();
        $previewDomPdf->render();

        $canvas = $previewDomPdf->getCanvas();

        return method_exists($canvas, 'get_page_count')
            ? max((int) $canvas->get_page_count(), 1)
            : 1;
    }

    private function townHallPdfOutput(TownHallCommunication $communication): string
    {
        $dateGenerated = now()->format('F d, Y h:i A');
        $totalPages = $this->resolveTownHallPdfPageCount($communication, $dateGenerated);

        return $this->buildTownHallPdf($communication, $totalPages, $dateGenerated)->output();
    }

    private function townHallPdfDownload(TownHallCommunication $communication)
    {
        $dateGenerated = now()->format('F d, Y h:i A');
        $totalPages = $this->resolveTownHallPdfPageCount($communication, $dateGenerated);

        return $this->buildTownHallPdf($communication, $totalPages, $dateGenerated)
            ->download(($communication->ref_no ?: 'townhall-communication') . '.pdf');
    }

    public function downloadPdf($id)
    {
        $communication = TownHallCommunication::findOrFail($id);

        if ($communication->approval_status !== 'Approved' || $communication->is_archived) {
            abort(403, 'Only active approved communications can be downloaded.');
        }

        return $this->townHallPdfDownload($communication);
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






    private function detectBrowser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') && !str_contains($userAgent, 'Edg/') => 'Google Chrome',
            str_contains($userAgent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome/') => 'Safari',
            default => 'Unknown Browser',
        };
    }

    private function detectOperatingSystem(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Windows NT 10.0') => 'Windows 10/11',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };
    }

    private function detectDeviceInformation(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') || str_contains($userAgent, 'iPhone') => 'Mobile Device',
            str_contains($userAgent, 'iPad') || str_contains($userAgent, 'Tablet') => 'Tablet',
            default => 'Desktop / Laptop',
        };
    }


    public function acknowledgementReport(Request $request)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communicationsQuery = TownHallCommunication::query()
            ->where('approval_status', 'Approved')
            ->latest('posted_at')
            ->latest();

        if ($request->filled('communication_id')) {
            $communicationsQuery->where('id', $request->communication_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $communicationsQuery->where(function ($q) use ($search) {
                $q->where('ref_no', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('from_name', 'like', "%{$search}%");
            });
        }

        $communications = $communicationsQuery->get();

        $rows = collect();

        foreach ($communications as $communication) {
            $intendedUsers = $this->getAcknowledgementUsers($communication);
            $records = TownHallAcknowledgement::where('townhall_communication_id', $communication->id)
                ->get()
                ->keyBy('user_id');

            foreach ($intendedUsers as $recipient) {
                $record = $records->get($recipient->id);

                $rows->push((object) [
                    'communication' => $communication,
                    'recipient' => $recipient,
                    'recipient_name' => $recipient->name,
                    'recipient_email' => $recipient->email,
                    'record' => $record,
                    'viewed_at' => $record?->viewed_at,
                    'acknowledged_at' => $record?->acknowledged_at,
                    'status' => $record?->acknowledged_at
                        ? 'Acknowledged'
                        : ($record?->viewed_at ? 'Viewed' : 'Not Viewed'),
                ]);
            }
        }

        if ($request->filled('recipient')) {
            $recipientSearch = strtolower(trim((string) $request->recipient));

            $rows = $rows->filter(function ($row) use ($recipientSearch) {
                return str_contains(strtolower((string) $row->recipient_name), $recipientSearch)
                    || str_contains(strtolower((string) $row->recipient_email), $recipientSearch);
            })->values();
        }

        if ($request->filled('tracking_status')) {
            $rows = $rows->where('status', $request->tracking_status)->values();
        }

        $summary = [
            'total' => $rows->count(),
            'viewed' => $rows->filter(fn($row) => !is_null($row->viewed_at))->count(),
            'acknowledged' => $rows->filter(fn($row) => !is_null($row->acknowledged_at))->count(),
            'not_viewed' => $rows->filter(fn($row) => is_null($row->viewed_at))->count(),
        ];

        $perPage = 15;
        $currentPage = max((int) $request->query('page', 1), 1);

        $reportRows = new LengthAwarePaginator(
            $rows->forPage($currentPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $communicationOptions = TownHallCommunication::where('approval_status', 'Approved')
            ->latest('posted_at')
            ->get(['id', 'ref_no', 'subject']);

        return view('admin.townhall-acknowledgement-report', compact(
            'reportRows',
            'summary',
            'communicationOptions'
        ));
    }


    public function auditTrail(Request $request)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $query = TownHallApprovalAudit::with(['communication', 'approver'])
            ->latest('acted_at')
            ->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('communication_ref_no', 'like', "%{$search}%")
                    ->orWhere('communication_subject', 'like', "%{$search}%")
                    ->orWhere('requestor_name', 'like', "%{$search}%")
                    ->orWhere('approver_name', 'like', "%{$search}%")
                    ->orWhere('approver_position', 'like', "%{$search}%")
                    ->orWhere('approver_department', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('approval_status', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('approval_level')) {
            $query->where('approval_level', $request->approval_level);
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('acted_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('acted_at', '<=', $request->date_to);
        }

        $audits = $query->paginate(15)->withQueryString();

        $summary = [
            'total' => TownHallApprovalAudit::count(),
            'approved' => TownHallApprovalAudit::whereIn('action', ['Approved', 'Approved and Posted'])->count(),
            'rejected' => TownHallApprovalAudit::where('action', 'Rejected')->count(),
            'revision' => TownHallApprovalAudit::where('action', 'Returned for Revision')->count(),
            'posted' => TownHallApprovalAudit::where('approval_status', 'Posted')->count(),
        ];

        $actionOptions = TownHallApprovalAudit::select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $levelOptions = TownHallApprovalAudit::select('approval_level')
            ->whereNotNull('approval_level')
            ->distinct()
            ->orderBy('approval_level')
            ->pluck('approval_level');

        $statusOptions = TownHallApprovalAudit::select('approval_status')
            ->whereNotNull('approval_status')
            ->distinct()
            ->orderBy('approval_status')
            ->pluck('approval_status');

        return view('admin.townhall-audit-trail', compact(
            'audits',
            'summary',
            'actionOptions',
            'levelOptions',
            'statusOptions'
        ));
    }

    private function recordTownHallAudit(
        TownHallCommunication $communication,
        string $action,
        ?string $approvalLevel,
        ?int $approverUserId,
        ?string $approvalStatus,
        ?string $remarks = null
    ): void {
        if (!class_exists(TownHallApprovalAudit::class)) {
            return;
        }

        try {
            $identity = $this->resolveAuditApproverIdentity($communication, $approvalLevel, $approverUserId);

            TownHallApprovalAudit::create([
                'townhall_communication_id' => $communication->id,
                'communication_ref_no' => $communication->ref_no,
                'communication_subject' => $communication->subject,
                'requestor_user_id' => $communication->created_by,
                'requestor_name' => $communication->from_name ?: ($communication->uploader?->name),
                'action' => $action,
                'approval_level' => $approvalLevel,
                'approver_user_id' => $approverUserId,
                'approver_name' => $identity['name'],
                'approver_position' => $identity['position'],
                'approver_department' => $identity['department'],
                'approval_status' => $approvalStatus,
                'remarks' => $remarks,
                'acted_at' => Carbon::now(),
                'ip_address' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
            ]);
        } catch (\Throwable $e) {
            Log::error('TownHall audit trail write failed.', [
                'communication_id' => $communication->id,
                'action' => $action,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveAuditApproverIdentity(
        TownHallCommunication $communication,
        ?string $approvalLevel,
        ?int $approverUserId
    ): array {
        $user = $approverUserId ? User::find($approverUserId) : Auth::user();
        $employee = null;

        if ($user && class_exists(Employee::class)) {
            $employee = Employee::where('user_id', $user->id)->first();
        }

        $name = $user?->name;
        $position = $employee?->position;
        $department = $this->resolveDepartmentName($employee?->department_id ?? null);

        if (!$name || $department === '—' || !$position) {
            if (str_contains((string) $approvalLevel, 'Executive')) {
                $name = $name ?: ($communication->executive_approver_name ?: 'John Kelly D. Abalde');
                $position = $position ?: ($communication->executive_approver_position ?: 'President and CEO');
                $department = $department !== '—'
                    ? $department
                    : ($communication->executive_approver_department ?: 'Executive Management');
            } elseif (str_contains((string) $approvalLevel, 'Management')) {
                $name = $name ?: ($communication->management_approver_name ?: '—');
                $position = $position ?: ($communication->management_approver_position ?: '—');
                $department = $department !== '—'
                    ? $department
                    : ($communication->management_approver_department ?: '—');
            }
        }

        return [
            'name' => $name ?: 'System',
            'position' => $position ?: '—',
            'department' => $department ?: '—',
        ];
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
            $pdfBinary = $this->townHallPdfOutput($communication);

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
            $pdfBinary = $this->townHallPdfOutput($communication);

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
