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

        $this->applyRecipientVisibility($query, Auth::user());

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

        return view('townhall.townhall', compact(
            'communications',
            'departments',
            'employees',
            'todayAttendance'
        ));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('create_townhall')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'communication_date' => ['nullable', 'date'],
            'department_stakeholder' => ['nullable', 'string', 'max:255'],
            'recipient_label' => ['nullable', 'in:To,For'],
            'to_for' => ['nullable', 'string', 'max:255'],
            'recipient_type' => ['nullable', 'in:all,employee'],
            'recipient_user_id' => ['nullable', 'required_if:recipient_type,employee', 'exists:users,id'],
            'priority' => ['nullable', 'in:High,Low'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'cc' => ['nullable', 'string', 'max:255'],
            'additional' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:5120'],
            'expires_at' => ['nullable', 'date'],
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

        $validated['from_name'] = Auth::user()->name;
        $validated['priority'] = $request->priority ?? 'Low';
        $validated['created_by'] = Auth::id();
        $validated['approval_status'] = 'Pending';
        $validated['is_archived'] = false;
        $validated['archived_at'] = null;

        $communication = TownHallCommunication::create($validated);

        $communication->ref_no = 'TH-' . str_pad((string) $communication->id, 5, '0', STR_PAD_LEFT);
        $communication->save();

        return redirect()
            ->route('townhall')
            ->with('success', 'Town Hall communication created successfully.');
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

        if ($communication->approval_status !== 'Needs Revision') {
            abort(403, 'Only communications marked for revision can be edited.');
        }

        $employees = User::where('role', 'Employee')
            ->orderBy('name')
            ->get();

        return view('townhall.edit', compact('communication', 'employees'));
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

        if ($communication->approval_status !== 'Needs Revision') {
            abort(403, 'Only communications marked for revision can be updated.');
        }

        $validated = $request->validate([
            'communication_date' => ['nullable', 'date'],
            'department_stakeholder' => ['nullable', 'string', 'max:255'],
            'recipient_label' => ['nullable', 'in:To,For'],
            'to_for' => ['nullable', 'string', 'max:255'],
            'recipient_type' => ['nullable', 'in:all,employee'],
            'recipient_user_id' => ['nullable', 'required_if:recipient_type,employee', 'exists:users,id'],
            'priority' => ['nullable', 'in:High,Low'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'cc' => ['nullable', 'string', 'max:255'],
            'additional' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:5120'],
            'expires_at' => ['nullable', 'date'],
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

        $validated['approval_status'] = 'Pending';
        $validated['approved_by'] = null;
        $validated['approved_at'] = null;
        $validated['approval_notes'] = null;
        $validated['is_archived'] = false;
        $validated['archived_at'] = null;

        $communication->update($validated);

        return redirect()
            ->route('townhall')
            ->with('success', 'Communication updated and resubmitted for approval.');
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

        $employees = User::where('role', 'Employee')->get();

        $acknowledgedUserIds = TownHallAcknowledgement::where('townhall_communication_id', $id)
            ->pluck('user_id')
            ->toArray();

        $acknowledgedUsers = $employees->whereIn('id', $acknowledgedUserIds);
        $notAcknowledgedUsers = $employees->whereNotIn('id', $acknowledgedUserIds);

        $totalEmployees = $employees->count();
        $ackCount = count($acknowledgedUserIds);

        $progress = $totalEmployees > 0
            ? round(($ackCount / $totalEmployees) * 100)
            : 0;

        $hasAcknowledged = $communication->hasBeenAcknowledgedBy(Auth::id());
        $requiresAcknowledgement = !Auth::user()->hasPermission('approve_townhall')
            && $communication->approval_status === 'Approved'
            && !$communication->is_archived;

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

        $communication->update([
            'approval_status' => 'Approved',
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);

        return redirect()->back()->with('success', 'Communication approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        $communication->update([
            'approval_status' => 'Rejected',
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);

        return redirect()->back()->with('success', 'Communication rejected successfully.');
    }

    public function revise(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_townhall')) {
            abort(403, 'Unauthorized');
        }

        $communication = TownHallCommunication::findOrFail($id);

        $communication->update([
            'approval_status' => 'Needs Revision',
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);

        return redirect()->back()->with('success', 'Communication marked for revision.');
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

        if (Auth::user()->hasPermission('approve_townhall')) {
            return redirect()->back()->with('success', 'Acknowledgment is not required for your access level.');
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

        $pdf = Pdf::loadView('townhall.show-pdf', compact('communication'));

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
                /*
             * STRICT FILTER:
             * Shows only memos specifically sent to the selected employee.
             * It will NOT include "All Employees" memos.
             */
                $query->where('recipient_type', 'employee')
                    ->where('recipient_user_id', $selectedEmployee);
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
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('recipient_type', 'employee')
                            ->where('recipient_user_id', $user->id);
                    });
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

    private function normalizeRecipientFields(array $validated, Request $request): array
    {
        if (!Schema::hasColumn('townhall_communications', 'recipient_type')) {
            unset($validated['recipient_type'], $validated['recipient_user_id']);
            return $validated;
        }

        $recipientType = $request->input('recipient_type', 'all');

        $validated['recipient_type'] = $recipientType;

        if ($recipientType === 'all') {
            $validated['recipient_user_id'] = null;
            $validated['to_for'] = 'All Employees';
            return $validated;
        }

        if ($recipientType === 'employee') {
            $recipient = User::find($request->input('recipient_user_id'));

            if ($recipient) {
                $validated['recipient_user_id'] = $recipient->id;
                $validated['to_for'] = $recipient->name;
            }
        }

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
                $q->where('recipient_type', 'all')
                    ->orWhere('recipient_user_id', $user->id)
                    ->orWhere(function ($legacy) use ($user) {
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
                    ->orWhere('recipient_user_id', $employee->id)
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
            if ($communication->recipient_type === 'all') {
                return true;
            }

            if ((int) $communication->recipient_user_id === (int) $user->id) {
                return true;
            }
        }

        $toFor = strtolower((string) $communication->to_for);
        $userName = strtolower((string) $user->name);

        return str_contains($toFor, $userName)
            || str_contains($toFor, 'all')
            || str_contains($toFor, 'everyone')
            || str_contains($toFor, 'all employees');
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
