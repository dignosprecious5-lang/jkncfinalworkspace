<?php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Mail\CorrespondenceApprovalNotification;
use App\Models\Contact;
use App\Models\DirectorOfficer;
use App\Models\Employee;
use App\Models\GisRecord;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class CorrespondenceController extends Controller
{
    private array $correspondenceTypes = [
        'Letters',
        'Demand Letter',
        'Request Letter',
        'Follow-Up Letter',
        'Notice',
        'Advisory Letter',
        'Transmittal Letter',
        'Authorization Letter',
        'Acknowledgment Letter',
        'Invitation Letter',
        'Endorsement Letter',
        'Complaint Letter',
        'Explanation Letter',
        'Response Letter',
        'Other',
    ];

    public function index()
    {
        $latestGisRecord = $this->latestApprovedGisRecord();
        $companyInfo = $this->latestGisCompanyInfo($latestGisRecord);
        $correspondenceLogoUrl = $this->gisLogoUrl($latestGisRecord);
        $managementApprovers = $this->activeEmployeeApprovers();
        $executiveApprovers = $this->executiveApproversFromGis();

        return view('corporate.correspondence', compact(
            'latestGisRecord',
            'companyInfo',
            'correspondenceLogoUrl',
            'managementApprovers',
            'executiveApprovers'
        ));
    }

    public function data(Request $request)
    {
        /*
         * Corporate > Correspondence is a public/normal corporate list.
         * It should only show approved correspondence.
         * Admin Correspondence Dashboard is the place for Pending / Submitted / Reverted / Archived.
         */
        $query = Correspondence::with('creator')
            ->where(function ($q) {
                $q->where('is_archived', false)
                    ->orWhereNull('is_archived');
            })
            ->where(function ($q) {
                $q->where('approval_status', 'Approved')
                    ->orWhere('workflow_status', 'Accepted');
            });

        if ($request->filled('type') && $request->type !== 'All') {
            $query->where('type', $request->type);
        }

        return $query->latest()
            ->get()
            ->map(function (Correspondence $item) {
                return [
                    'id' => $item->id,
                    'ref_no' => $item->ref_no ?: 'COR-' . str_pad((string) $item->id, 5, '0', STR_PAD_LEFT),
                    'date' => optional($item->correspondence_date)->format('M d, Y') ?: optional($item->created_at)->format('M d, Y'),
                    'type' => $item->type,
                    'company_name' => $item->company_name,
                    'registration_number' => $item->registration_number,
                    'principal_address' => $item->principal_address,
                    'tin' => $item->tin,
                    'to_for_label' => $item->to_for_label ?: 'To',
                    'to_for' => $item->to_for,
                    'from_name' => $item->from_name,
                    'prepared_by_name' => $item->prepared_by_name,
                    'prepared_by_position' => $item->prepared_by_position,
                    'prepared_by_department' => $item->prepared_by_department,
                    'prepared_on' => optional($item->prepared_on)->format('M d, Y h:i A'),
                    'management_signature_name' => $item->management_signature_name,
                    'management_signature_position' => $item->management_signature_position,
                    'management_signature_department' => $item->management_signature_department,
                    'management_approved_on' => optional($item->management_approved_on ?: $item->management_approved_at)->format('M d, Y h:i A'),
                    'executive_signature_name' => $item->executive_signature_name,
                    'executive_signature_position' => $item->executive_signature_position,
                    'executive_signature_department' => $item->executive_signature_department,
                    'executive_approved_on' => optional($item->executive_approved_on ?: $item->executive_approved_at)->format('M d, Y h:i A'),
                    'department' => $item->department_stakeholder,
                    'subject' => $item->subject,
                    'deadline' => $item->deadline ? $item->deadline->format('M d, Y') : null,
                    'sent_via' => $item->sent_via,
                    'status' => $item->status,
                    'workflow_status' => 'Accepted',
                    'approval_status' => 'Approved',
                    'review_note' => $item->review_note,
                    'user' => $item->creator?->name ?: ($item->user ?: 'System'),
                    'can_submit' => false,
                ];
            })
            ->values();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100', 'in:' . implode(',', $this->correspondenceTypes)],
            'correspondence_date' => ['nullable', 'date'],
            'tin' => ['nullable', 'string', 'max:100'],
            'to_for_label' => ['nullable', 'string', 'max:10', 'in:To,For'],
            'to_for' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'prepared_by_name' => ['nullable', 'string', 'max:255'],
            'prepared_by_position' => ['nullable', 'string', 'max:255'],
            'prepared_by_department' => ['nullable', 'string', 'max:255'],
            'prepared_on' => ['nullable', 'date'],
            'management_signature_name' => ['nullable', 'string', 'max:255'],
            'management_signature_position' => ['nullable', 'string', 'max:255'],
            'management_signature_department' => ['nullable', 'string', 'max:255'],
            'management_approved_on' => ['nullable', 'date'],
            'executive_signature_name' => ['nullable', 'string', 'max:255'],
            'executive_signature_position' => ['nullable', 'string', 'max:255'],
            'executive_signature_department' => ['nullable', 'string', 'max:255'],
            'executive_approved_on' => ['nullable', 'date'],
            'department_stakeholder' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'cc' => ['nullable', 'string', 'max:255'],
            'additional' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'sent_via' => ['nullable', 'string', 'max:100'],
            'management_approver_id' => ['required', 'integer'],
            'executive_approver_id' => ['required', 'integer'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:5120'],
        ]);

        $latestGisRecord = $this->latestApprovedGisRecord();
        $companyInfo = $this->latestGisCompanyInfo($latestGisRecord);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('correspondence_attachments', 'public');
        }

        $record = Correspondence::create(array_merge($validated, [
            'ref_no' => null,
            'correspondence_date' => $validated['correspondence_date'] ?? now()->format('Y-m-d'),
            'company_name' => $companyInfo['company_name'],
            'registration_number' => $companyInfo['registration_number'],
            'principal_address' => $companyInfo['principal_address'],
            'from_name' => $validated['from_name'] ?: (Auth::user()->name ?? 'System Admin'),
            'body' => $validated['body'] ?? null,
            'sent_via' => $validated['sent_via'] ?? 'Email',
            'status' => 'Open',
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'is_archived' => false,
            'archived_at' => null,
            'submitted_at' => now(),
            'management_approval_status' => 'Pending',
            'executive_approval_status' => 'Waiting for Level 1',
            'created_by' => Auth::id(),
        ], $this->buildApprovalData(
            $request->input('management_approver_id'),
            $request->input('executive_approver_id')
        )));

        $record->update([
            'ref_no' => 'COR-' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT),
        ]);

        // Always copy approver emails directly from source tables before notification.
        $record = $this->syncApproverEmailsFromDatabase($record);

        // Send Town Hall-style approval notification to Level 1 and Level 2 approvers.
        $this->sendCorrespondenceLevelApprovalEmail($record, 1);

        return response()->json([
            'message' => 'Correspondence saved successfully.',
            'record' => $record->fresh(),
        ]);
    }

    public function submit($id)
    {
        $record = Correspondence::findOrFail($id);

        $record->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'submitted_at' => $record->submitted_at ?: now(),
            'management_approval_status' => $record->management_approval_status ?: 'Pending',
            'executive_approval_status' => $record->executive_approval_status ?: 'Pending',
        ]);

        return response()->json(['message' => 'Correspondence is already in the submitted workflow.']);
    }

    public function template($type, $id)
    {
        $correspondence = Correspondence::findOrFail($id);

        $latestGisRecord = $this->latestApprovedGisRecord();
        $correspondenceLogoUrl = $this->gisLogoUrl($latestGisRecord);

        return view('correspondence.template', [
            'correspondence' => $correspondence,
            'correspondenceLogoUrl' => $correspondenceLogoUrl,
        ]);
    }

    public function downloadPdf($id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $correspondence->body = $this->prepareCorrespondenceBodyForPdf($correspondence->body);

        $latestGisRecord = $this->latestApprovedGisRecord();
        $correspondenceLogoSrc = $this->gisLogoDataUri($latestGisRecord);

        $pdf = Pdf::loadView('correspondence.pdf', compact('correspondence', 'correspondenceLogoSrc'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Georgia',
            ]);

        return $pdf->download(($correspondence->ref_no ?: 'correspondence') . '.pdf');
    }

    public function approve($id)
    {
        $record = Correspondence::findOrFail($id);

        /*
         * Sequential approval:
         * 1. Level 1 approves first. This only marks management approval and emails Level 2.
         * 2. Level 2 approves next. This finally marks the correspondence Approved/Accepted and posts it.
         */
        if ($record->management_approval_status !== 'Approved') {
            $record->update([
                'workflow_status' => 'Submitted',
                'approval_status' => 'Pending Level 2 Approval',
                'status' => 'Pending Level 2 Approval',
                'is_archived' => false,
                'archived_at' => null,
                'management_approval_status' => 'Approved',
                'management_approved_at' => now(),
                'management_approved_on' => $record->management_approved_on ?: now(),
                'management_approved_on' => $record->management_approved_on ?: now(),
                'review_note' => null,
            ]);

            $record = $record->fresh(['creator']);
            $this->sendCorrespondenceLevelApprovalEmail($record, 2);

            return back()->with('success', 'Level 1 approved successfully. Level 2 approver has been notified.');
        }

        if ($record->executive_approval_status !== 'Approved') {
            $record->update([
                'workflow_status' => 'Accepted',
                'approval_status' => 'Approved',
                'status' => 'Open',
                'is_archived' => false,
                'archived_at' => null,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'executive_approval_status' => 'Approved',
                'executive_approved_at' => now(),
                'executive_approved_on' => $record->executive_approved_on ?: now(),
                'executive_approved_on' => $record->executive_approved_on ?: now(),
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'review_note' => null,
            ]);

            return back()->with('success', 'Level 2 approved successfully. Correspondence is now approved and posted.');
        }

        return back()->with('success', 'Correspondence is already fully approved.');
    }

    public function revise(Request $request, $id)
    {
        $record = Correspondence::findOrFail($id);

        $record->update([
            'workflow_status' => 'Reverted',
            'approval_status' => 'Needs Revision',
            'is_archived' => false,
            'archived_at' => null,
            'review_note' => $request->input('review_note', 'Needs revision.'),
        ]);

        return redirect()
            ->route('admin.correspondence.show', $record->id)
            ->with('success', 'Correspondence returned for revision. You may now edit all fields below.')
            ->with('open_revision_editor', true);
    }



    public function updateAdminRevision(Request $request, $id)
    {
        $record = Correspondence::findOrFail($id);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100', 'in:' . implode(',', $this->correspondenceTypes)],
            'correspondence_date' => ['nullable', 'date'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'principal_address' => ['nullable', 'string'],
            'to_for_label' => ['nullable', 'string', 'max:10', 'in:To,For'],
            'to_for' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'department_stakeholder' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'cc' => ['nullable', 'string', 'max:255'],
            'additional' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'sent_via' => ['nullable', 'string', 'max:100'],

            'prepared_by_name' => ['nullable', 'string', 'max:255'],
            'prepared_by_position' => ['nullable', 'string', 'max:255'],
            'prepared_by_department' => ['nullable', 'string', 'max:255'],
            'prepared_on' => ['nullable', 'date'],

            'management_approver_id' => ['nullable', 'integer'],
            'management_signature_name' => ['nullable', 'string', 'max:255'],
            'management_signature_position' => ['nullable', 'string', 'max:255'],
            'management_signature_department' => ['nullable', 'string', 'max:255'],
            'management_approved_on' => ['nullable', 'date'],

            'executive_approver_id' => ['nullable', 'integer'],
            'executive_signature_name' => ['nullable', 'string', 'max:255'],
            'executive_signature_position' => ['nullable', 'string', 'max:255'],
            'executive_signature_department' => ['nullable', 'string', 'max:255'],
            'executive_approved_on' => ['nullable', 'date'],

            'review_note' => ['nullable', 'string'],
        ]);

        $payload = [
            'type' => $validated['type'],
            'correspondence_date' => $validated['correspondence_date'] ?? $record->correspondence_date,
            'company_name' => $validated['company_name'] ?? $record->company_name,
            'registration_number' => $validated['registration_number'] ?? $record->registration_number,
            'principal_address' => $validated['principal_address'] ?? $record->principal_address,
            'to_for_label' => $validated['to_for_label'] ?? 'To',
            'to_for' => $validated['to_for'] ?? null,
            'from_name' => $validated['from_name'] ?? null,
            'department_stakeholder' => $validated['department_stakeholder'] ?? null,
            'subject' => $validated['subject'],
            'body' => $validated['body'] ?? null,
            'cc' => $validated['cc'] ?? null,
            'additional' => $validated['additional'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'sent_via' => $validated['sent_via'] ?? 'Email',

            'prepared_by_name' => $validated['prepared_by_name'] ?? null,
            'prepared_by_position' => $validated['prepared_by_position'] ?? null,
            'prepared_by_department' => $validated['prepared_by_department'] ?? null,
            'prepared_on' => $validated['prepared_on'] ?? null,

            'management_signature_name' => $validated['management_signature_name'] ?? null,
            'management_signature_position' => $validated['management_signature_position'] ?? null,
            'management_signature_department' => $validated['management_signature_department'] ?? null,
            'management_approved_on' => $validated['management_approved_on'] ?? null,

            'executive_signature_name' => $validated['executive_signature_name'] ?? null,
            'executive_signature_position' => $validated['executive_signature_position'] ?? null,
            'executive_signature_department' => $validated['executive_signature_department'] ?? null,
            'executive_approved_on' => $validated['executive_approved_on'] ?? null,

            'review_note' => $validated['review_note'] ?? $record->review_note,

            // After admin edits a revised correspondence, restart the approval workflow.
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'status' => 'Open',
            'is_archived' => false,
            'archived_at' => null,
            'management_approval_status' => 'Pending',
            'management_approved_at' => null,
            'executive_approval_status' => 'Waiting for Level 1',
            'executive_approved_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'posted_at' => null,
            'posted_by' => null,
        ];

        $managementApproverId = $validated['management_approver_id'] ?? $record->management_approver_id;
        $executiveApproverId = $validated['executive_approver_id'] ?? $record->executive_approver_id;

        if ($managementApproverId && $executiveApproverId) {
            $payload = array_merge($payload, $this->buildApprovalData($managementApproverId, $executiveApproverId));
        }

        // Preserve manually typed signature fields after buildApprovalData fills selected approver defaults.
        $payload['management_signature_name'] = $validated['management_signature_name'] ?? ($payload['management_approver_name'] ?? $record->management_signature_name);
        $payload['management_signature_position'] = $validated['management_signature_position'] ?? ($payload['management_approver_position'] ?? $record->management_signature_position);
        $payload['management_signature_department'] = $validated['management_signature_department'] ?? ($payload['management_approver_department'] ?? $record->management_signature_department);

        $payload['executive_signature_name'] = $validated['executive_signature_name'] ?? ($payload['executive_approver_name'] ?? $record->executive_signature_name);
        $payload['executive_signature_position'] = $validated['executive_signature_position'] ?? ($payload['executive_approver_position'] ?? $record->executive_signature_position);
        $payload['executive_signature_department'] = $validated['executive_signature_department'] ?? ($payload['executive_approver_department'] ?? $record->executive_signature_department);

        $record->update($payload);

        $record = $this->syncApproverEmailsFromDatabase($record->fresh(['creator']));
        $this->sendCorrespondenceLevelApprovalEmail($record, 1);

        return redirect()
            ->route('admin.correspondence.show', $record->id)
            ->with('success', 'Revised correspondence updated successfully and sent back to Level 1 approval.');
    }


    public function approveFromEmail(Request $request, $id)
    {
        $record = Correspondence::findOrFail($id);
        $level = (int) $request->query('level', 1);

        if ($level === 1 && $record->management_approval_status !== 'Approved') {
            $record->update([
                'workflow_status' => 'Submitted',
                'approval_status' => 'Pending Level 2 Approval',
                'status' => 'Pending Level 2 Approval',
                'is_archived' => false,
                'archived_at' => null,
                'management_approval_status' => 'Approved',
                'management_approved_at' => now(),
                'review_note' => null,
            ]);

            $record = $record->fresh(['creator']);
            $this->sendCorrespondenceLevelApprovalEmail($record, 2);

            return redirect()
                ->route('admin.correspondence.show', $record->id)
                ->with('success', 'Level 1 approved successfully from email. Level 2 approver has been notified.');
        }

        if ($level === 2 && $record->management_approval_status === 'Approved' && $record->executive_approval_status !== 'Approved') {
            $record->update([
                'workflow_status' => 'Accepted',
                'approval_status' => 'Approved',
                'status' => 'Open',
                'is_archived' => false,
                'archived_at' => null,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'executive_approval_status' => 'Approved',
                'executive_approved_at' => now(),
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'review_note' => null,
            ]);

            return redirect()
                ->route('admin.correspondence.show', $record->id)
                ->with('success', 'Level 2 approved successfully from email. Correspondence is now approved and posted.');
        }

        return redirect()
            ->route('admin.correspondence.show', $record->id)
            ->with('success', 'No approval action was needed for this correspondence.');
    }

    public function rejectFromEmail(Request $request, $id)
    {
        $record = Correspondence::findOrFail($id);

        $record->update([
            'workflow_status' => 'Reverted',
            'approval_status' => 'Rejected',
            'is_archived' => false,
            'archived_at' => null,
            'review_note' => 'Rejected from email.',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('admin.correspondence.show', $record->id)
            ->with('success', 'Correspondence rejected successfully from email.');
    }


    public function archive($id)
    {
        $record = Correspondence::findOrFail($id);

        $record->update([
            'workflow_status' => 'Archived',
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return back()->with('success', 'Correspondence archived.');
    }


    public function submittedDashboard(Request $request)
    {
        $query = Correspondence::with('creator');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('ref_no', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('to_for', 'like', "%{$search}%")
                    ->orWhere('from_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('workflow_status', 'like', "%{$search}%")
                    ->orWhere('approval_status', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && $request->type !== 'All') {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'Archived') {
                $query->where('is_archived', true);
            } else {
                $query->where('workflow_status', $request->status)
                    ->where('is_archived', false);
            }
        }

        $correspondences = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'submitted' => Correspondence::where('workflow_status', 'Submitted')->where('is_archived', false)->count(),
            'accepted' => Correspondence::where('workflow_status', 'Accepted')->where('is_archived', false)->count(),
            'reverted' => Correspondence::where('workflow_status', 'Reverted')->where('is_archived', false)->count(),
            'uploaded' => Correspondence::where('workflow_status', 'Uploaded')->where('is_archived', false)->count(), // legacy only
            'archived' => Correspondence::where('is_archived', true)->count(),
        ];

        $types = $this->correspondenceTypes;

        return view('admin.correspondence-dashboard', compact('correspondences', 'stats', 'types'));
    }

    public function showAdmin($id)
    {
        $correspondence = Correspondence::with('creator')->findOrFail($id);
        $managementApprovers = $this->activeEmployeeApprovers();
        $executiveApprovers = $this->executiveApproversFromGis();
        $types = $this->correspondenceTypes;

        return view('admin.correspondence-show', compact(
            'correspondence',
            'managementApprovers',
            'executiveApprovers',
            'types'
        ));
    }

    public function reject(Request $request, $id)
    {
        $record = Correspondence::findOrFail($id);

        $record->update([
            'workflow_status' => 'Reverted',
            'approval_status' => 'Rejected',
            'is_archived' => false,
            'archived_at' => null,
            'review_note' => $request->input('review_note', 'Rejected.'),
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Correspondence rejected successfully.');
    }

    public function unarchive($id)
    {
        $record = Correspondence::findOrFail($id);

        $record->update([
            'workflow_status' => $record->approval_status === 'Approved' ? 'Accepted' : 'Reverted',
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return back()->with('success', 'Correspondence unarchived successfully.');
    }



    private function gisLogoUrl($gisRecord): string
    {
        $fallback = asset('images/jk-logo.png');

        if (!$gisRecord || empty($gisRecord->logo_path)) {
            return $fallback;
        }

        $path = ltrim($gisRecord->logo_path, '/');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    private function gisLogoDataUri($gisRecord): ?string
    {
        $candidates = [];

        if ($gisRecord && !empty($gisRecord->logo_path)) {
            $path = ltrim($gisRecord->logo_path, '/');

            if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
                $normalizedPath = str_starts_with($path, 'storage/')
                    ? substr($path, strlen('storage/'))
                    : $path;

                $candidates[] = storage_path('app/public/' . $normalizedPath);
                $candidates[] = public_path($path);
                $candidates[] = public_path('storage/' . $normalizedPath);
            }
        }

        $candidates[] = public_path('images/jk-logo.png');
        $candidates[] = public_path('images/logo.png');

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));

                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };

                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
            }
        }

        return null;
    }


    private function latestApprovedGisRecord()
    {
        if (!class_exists(GisRecord::class) || !Schema::hasTable((new GisRecord())->getTable())) {
            return null;
        }

        $gisTable = (new GisRecord())->getTable();

        $query = GisRecord::with('directors')
            ->where(function ($q) {
                $q->where('approval_status', 'Approved')
                    ->orWhere('workflow_status', 'Accepted')
                    ->orWhere('workflow_status', 'Approved');
            });

        /*
         * Use Corporate > Corporate Formation > GIS only.
         * Ignore Account > Company > Corporate Formation > GIS records when company_id exists.
         */
        if (Schema::hasColumn($gisTable, 'company_id')) {
            $query->whereNull('company_id');
        }

        return $query
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->first();
    }

    private function latestGisCompanyInfo($gisRecord): array
    {
        return [
            'company_name' => $gisRecord?->corporation_name ?: 'JOHN KELLY & COMPANY (JK&C INC)',
            'registration_number' => $gisRecord?->company_reg_no ?: '',
            'principal_address' => $gisRecord?->principal_address ?: '3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000',
        ];
    }

    private function activeEmployeeApprovers()
    {
        if (class_exists(Employee::class) && Schema::hasTable((new Employee())->getTable())) {
            return Employee::query()->with('user')
                ->orderBy('id')
                ->get()
                ->map(fn($employee) => $this->formatEmployeeApprover($employee))
                ->filter(fn($item) => !empty($item['name']))
                ->values();
        }

        return User::query()
            ->whereIn('role', ['Admin', 'admin', 'SuperAdmin', 'superadmin', 'System Super Admin'])
            ->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->role ?: 'Management',
                'department' => 'Management',
            ]);
    }



    private function readableEmailValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach (['work_email', 'company_email', 'email', 'personal_email', 'official_email'] as $key) {
                    if (!empty($decoded[$key]) && filter_var($decoded[$key], FILTER_VALIDATE_EMAIL)) {
                        return $decoded[$key];
                    }
                }

                return null;
            }

            return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
        }

        if (is_array($value)) {
            foreach (['work_email', 'company_email', 'email', 'personal_email', 'official_email'] as $key) {
                if (!empty($value[$key]) && filter_var($value[$key], FILTER_VALIDATE_EMAIL)) {
                    return $value[$key];
                }
            }

            return null;
        }

        if (is_object($value)) {
            foreach (['work_email', 'company_email', 'email', 'personal_email', 'official_email'] as $key) {
                if (isset($value->{$key}) && filter_var($value->{$key}, FILTER_VALIDATE_EMAIL)) {
                    return $value->{$key};
                }
            }

            if (isset($value->user) && is_object($value->user) && isset($value->user->email) && filter_var($value->user->email, FILTER_VALIDATE_EMAIL)) {
                return $value->user->email;
            }
        }

        return null;
    }


    private function readableApproverValue($value, ?string $fallback = '—'): string
    {
        if ($value === null || $value === '') {
            return $fallback ?? '';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach (['department_name', 'office_name', 'branch_name', 'name', 'department_head', 'office_head'] as $key) {
                    if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                        return $decoded[$key];
                    }
                }

                return $fallback;
            }

            return $value;
        }

        if (is_array($value)) {
            foreach (['department_name', 'office_name', 'branch_name', 'name', 'department_head', 'office_head'] as $key) {
                if (!empty($value[$key]) && is_string($value[$key])) {
                    return $value[$key];
                }
            }

            return $fallback;
        }

        if (is_object($value)) {
            foreach (['department_name', 'office_name', 'branch_name', 'name', 'department_head', 'office_head'] as $key) {
                if (isset($value->{$key}) && is_string($value->{$key}) && $value->{$key} !== '') {
                    return $value->{$key};
                }
            }

            return $fallback;
        }

        return (string) $value;
    }



    private function readablePositionValue($value, string $fallback = 'Management'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach (['position_name', 'name', 'title', 'designation'] as $key) {
                    if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                        return $decoded[$key];
                    }
                }

                return $fallback;
            }

            return $value;
        }

        if (is_array($value)) {
            foreach (['position_name', 'name', 'title', 'designation'] as $key) {
                if (!empty($value[$key]) && is_string($value[$key])) {
                    return $value[$key];
                }
            }

            return $fallback;
        }

        if (is_object($value)) {
            foreach (['position_name', 'name', 'title', 'designation'] as $key) {
                if (isset($value->{$key}) && is_string($value->{$key}) && $value->{$key} !== '') {
                    return $value->{$key};
                }
            }

            return $fallback;
        }

        return (string) $value;
    }

    private function readableDepartmentValue($value, string $fallback = 'Management'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach (['department_name', 'name'] as $key) {
                    if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                        return $decoded[$key];
                    }
                }

                return $fallback;
            }

            return $value;
        }

        if (is_array($value)) {
            foreach (['department_name', 'name'] as $key) {
                if (!empty($value[$key]) && is_string($value[$key])) {
                    return $value[$key];
                }
            }

            return $fallback;
        }

        if (is_object($value)) {
            foreach (['department_name', 'name'] as $key) {
                if (isset($value->{$key}) && is_string($value->{$key}) && $value->{$key} !== '') {
                    return $value->{$key};
                }
            }

            return $fallback;
        }

        return (string) $value;
    }


    private function formatEmployeeApprover($employee): array
    {
        $name = $this->readableApproverValue(
            $employee->name
                ?? $employee->employee_name
                ?? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''))
                ?: null,
            ''
        );

        /*
         * LEVEL 1 is from Employee Profile / Management.
         * Do not use office fields here, because that makes Level 1 show
         * "Office of the ..." which should only appear in Level 2.
         */
        $position = $this->readablePositionValue(
            $employee->position_title
                ?? $employee->position_name
                ?? $employee->job_title
                ?? $employee->designation
                ?? null,
            'Management'
        );

        $department = $this->readableDepartmentValue(
            $employee->department_name
                ?? $employee->department
                ?? null,
            'Management'
        );

        return [
            'id' => $employee->id,
            'user_id' => $employee->user_id ?? null,
            'name' => $name,
            'email' => $this->readableEmailValue($employee),
            'position' => $position,
            'department' => $department,
        ];
    }

    private function getEmployeeApproverData($employeeId): array
    {
        if (!$employeeId) {
            return [];
        }

        if (class_exists(Employee::class) && Schema::hasTable((new Employee())->getTable())) {
            $employee = Employee::query()->find($employeeId);

            if ($employee) {
                return $this->formatEmployeeApprover($employee);
            }
        }

        $user = User::find($employeeId);

        if (!$user) {
            return [];
        }

        return [
            'id' => $user->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'position' => $user->role ?: 'Management',
            'department' => 'Management',
        ];
    }

    private function executiveApproversFromGis()
    {
        /*
         * Deployed database table is directors_officers.
         * Do not rely on DirectorOfficer model table name because some codebases
         * use director_officers while production uses directors_officers.
         */
        if (!Schema::hasTable('directors_officers')) {
            return collect();
        }

        $latestApprovedGis = $this->latestApprovedGisRecord();

        $query = DB::table('directors_officers');

        if ($latestApprovedGis) {
            $query->where('gis_id', $latestApprovedGis->id);
        }

        return $query->orderBy('officer_name')
            ->get()
            ->filter(fn($officer) => $this->isValidGisOfficerType($officer->officer_type ?? null))
            ->map(fn($officer) => $this->formatGisApprover($officer))
            ->values();
    }

    private function getGisApproverData($officerId): array
    {
        if (!$officerId || !Schema::hasTable('directors_officers')) {
            return [];
        }

        $latestApprovedGis = $this->latestApprovedGisRecord();

        $query = DB::table('directors_officers')->where('id', $officerId);

        if ($latestApprovedGis) {
            $query->where('gis_id', $latestApprovedGis->id);
        }

        $officer = $query->first();

        if (!$officer || !$this->isValidGisOfficerType($officer->officer_type ?? null)) {
            return [];
        }

        return $this->formatGisApprover($officer);
    }

    private function formatGisApprover($officer): array
    {
        $position = trim((string) ($officer->officer_type ?? 'Executive Management'));

        return [
            'id' => $officer->id,
            'user_id' => null,
            'name' => $officer->officer_name ?: 'Unnamed Officer',
            'email' => $this->readableEmailValue($officer),
            'position' => $position,
            'department' => 'Office of the ' . $position,
            'gis_id' => $officer->gis_id ?? null,
        ];
    }

    private function isValidGisOfficerType($officerType): bool
    {
        $value = trim((string) $officerType);

        if ($value === '') {
            return false;
        }

        return !in_array(strtolower($value), [
            'n/a',
            'na',
            'none',
            'null',
            '-',
            '--',
            'not applicable',
        ], true);
    }

    private function buildApprovalData($managementApproverId, $executiveApproverId): array
    {
        $management = $this->getEmployeeApproverData($managementApproverId);
        $executive = $this->getGisApproverData($executiveApproverId);

        return [
            'management_approver_id' => $management['id'] ?? null,
            'management_approver_user_id' => $management['user_id'] ?? null,
            'management_approver_name' => $management['name'] ?? null,
            'management_approver_position' => $management['position'] ?? null,
            'management_approver_department' => $management['department'] ?? null,
            'management_approval_status' => 'Pending',

            'executive_approver_id' => $executive['id'] ?? null,
            'executive_approver_user_id' => $executive['user_id'] ?? null,
            'executive_approver_name' => $executive['name'] ?? null,
            'executive_approver_position' => $executive['position'] ?? null,
            'executive_approver_department' => $executive['department'] ?? null,
            'executive_approval_status' => 'Pending',
        ];
    }



    private function sendCorrespondenceLevelApprovalEmail(Correspondence $record, int $level): void
    {
        $record = $this->syncApproverEmailsFromDatabase($record);

        if ($level === 1) {
            $recipient = [
                'level' => 'Level 1 Approver - From Management',
                'name' => $record->management_approver_name ?: 'Level 1 Approver',
                'email' => $record->management_approver_email,
            ];
        } else {
            $recipient = [
                'level' => 'Level 2 Approver - From Executive Management',
                'name' => $record->executive_approver_name ?: 'Level 2 Approver',
                'email' => $record->executive_approver_email,
            ];
        }

        $email = trim((string) ($recipient['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Correspondence approval email skipped because approver email is missing.', [
                'correspondence_id' => $record->id,
                'ref_no' => $record->ref_no,
                'level' => $recipient['level'],
                'name' => $recipient['name'],
                'email' => $email,
            ]);

            return;
        }

        $this->sendCorrespondenceApprovalEmail(
            email: $email,
            approverName: $recipient['name'],
            level: $recipient['level'],
            record: $record,
            approvalLevel: $level
        );
    }

    private function sendCorrespondenceApprovalEmail(
        string $email,
        string $approverName,
        string $level,
        Correspondence $record,
        int $approvalLevel
    ): void {
        try {
            $reviewUrl = route('admin.correspondence.show', $record->id);

            $approveUrl = URL::signedRoute('correspondence.email.approve', [
                'id' => $record->id,
                'level' => $approvalLevel,
            ]);

            $rejectUrl = URL::signedRoute('correspondence.email.reject', [
                'id' => $record->id,
                'level' => $approvalLevel,
            ]);

            $subject = 'Correspondence Approval Needed: ' . ($record->ref_no ?: 'COR-' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT));

            Mail::send('emails.correspondence.approval-notification', [
                'correspondence' => $record,
                'level' => $level,
                'approvalLevel' => $approvalLevel,
                'approverName' => $approverName,
                'subjectLine' => $subject,
                'messageText' => $approvalLevel === 1
                    ? 'A corporate correspondence has been submitted and requires your Level 1 approval.'
                    : 'Level 1 has approved this corporate correspondence. It now requires your final Level 2 approval.',
                'actionUrl' => $reviewUrl,
                'approveUrl' => $approveUrl,
                'rejectUrl' => $rejectUrl,
            ], function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            Log::info('Correspondence approval email sent.', [
                'correspondence_id' => $record->id,
                'ref_no' => $record->ref_no,
                'email' => $email,
                'level' => $level,
                'approval_level' => $approvalLevel,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Correspondence approval email failed.', [
                'correspondence_id' => $record->id,
                'ref_no' => $record->ref_no,
                'email' => $email,
                'level' => $level,
                'approval_level' => $approvalLevel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncApproverEmailsFromDatabase(Correspondence $record): Correspondence
    {
        $updates = [];

        if ($record->management_approver_id && Schema::hasTable('employees')) {
            $employee = DB::table('employees')
                ->where('id', $record->management_approver_id)
                ->first();

            $managementEmail = $this->readableEmailValue($employee);

            // Town Hall-style fallback: if Employee profile has no email, use the linked User account email.
            if (!$managementEmail && $employee && !empty($employee->user_id) && Schema::hasTable('users')) {
                $linkedUser = DB::table('users')
                    ->where('id', $employee->user_id)
                    ->first(['id', 'email']);

                $managementEmail = $this->readableEmailValue($linkedUser);
            }

            if ($managementEmail) {
                $updates['management_approver_email'] = $managementEmail;
            }
        }

        if ($record->executive_approver_id && Schema::hasTable('directors_officers')) {
            $officer = DB::table('directors_officers')
                ->where('id', $record->executive_approver_id)
                ->first();

            $executiveEmail = $this->readableEmailValue($officer);

            if ($executiveEmail) {
                $updates['executive_approver_email'] = $executiveEmail;
            }
        }

        if (!empty($updates)) {
            $record->forceFill($updates)->save();
            $record = $record->fresh(['creator']);
        }

        return $record;
    }


    private function normalizeCorrespondencePdfTables(string $html): string
    {
        return preg_replace_callback('/<table\b[^>]*>.*?<\/table>/is', function ($matches) {
            $tableHtml = $matches[0];
            $rows = [];

            if (preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $tableHtml, $rowMatches)) {
                foreach ($rowMatches[1] as $rowHtml) {
                    $cells = [];

                    if (preg_match_all('/<(td|th)\b[^>]*>(.*?)<\/\1>/is', $rowHtml, $cellMatches, PREG_SET_ORDER)) {
                        foreach ($cellMatches as $cellMatch) {
                            $tag = strtolower($cellMatch[1]) === 'th' ? 'th' : 'td';
                            $content = $cellMatch[2];

                            $content = preg_replace('/<colgroup\b[^>]*>.*?<\/colgroup>/is', '', $content);
                            $content = preg_replace('/<col\b[^>]*\/?>/is', '', $content);
                            $content = preg_replace('/\sstyle=("|\')(.*?)\1/is', '', $content);
                            $content = str_replace(['&amp;nbsp;', '&nbsp;', "\u{00A0}"], ' ', $content);
                            $content = preg_replace('/[ \t]{2,}/u', ' ', $content);

                            $cells[] = [
                                'tag' => $tag,
                                'content' => trim($content) === '' ? '&nbsp;' : $content,
                            ];
                        }
                    }

                    if (!empty($cells)) {
                        $rows[] = $cells;
                    }
                }
            }

            if (empty($rows)) {
                return $tableHtml;
            }

            $maxColumns = max(array_map('count', $rows));
            $maxColumns = max(1, min($maxColumns, 12));
            $cellWidth = round(100 / $maxColumns, 4);

            $safeTable = '<table class="correspondence-pdf-table" style="width:100%;border-collapse:collapse;table-layout:fixed;">';

            foreach ($rows as $row) {
                $safeTable .= '<tr>';

                for ($i = 0; $i < $maxColumns; $i++) {
                    $cell = $row[$i] ?? ['tag' => 'td', 'content' => '&nbsp;'];
                    $tag = $cell['tag'];

                    $safeTable .= '<' . $tag . ' style="width:' . $cellWidth . '%;border:1px solid #888;padding:7px;vertical-align:top;text-align:left;">'
                        . $cell['content']
                        . '</' . $tag . '>';
                }

                $safeTable .= '</tr>';
            }

            return $safeTable . '</table>';
        }, $html);
    }

    private function inlineCorrespondencePdfIndentStyles(string $html): string
    {
        return preg_replace_callback('/<([a-z0-9]+)\b([^>]*)class=("|\')([^"\']*\bql-indent-([1-8])\b[^"\']*)\3([^>]*)>/i', function ($matches) {
            $tag = $matches[1];
            $attrs = $matches[2] . 'class=' . $matches[3] . $matches[4] . $matches[3] . $matches[6];
            $indentEm = ((int) $matches[5]) * 3;

            if (preg_match('/\sstyle=("|\')(.*?)\1/is', $attrs, $styleMatch)) {
                $newStyle = rtrim($styleMatch[2], ';') . '; margin-left:' . $indentEm . 'em; padding-left:0; text-indent:0;';
                $attrs = preg_replace('/\sstyle=("|\')(.*?)\1/is', ' style="' . e($newStyle) . '"', $attrs, 1);
            } else {
                $attrs .= ' style="margin-left:' . $indentEm . 'em; padding-left:0; text-indent:0;"';
            }

            return '<' . $tag . $attrs . '>';
        }, $html);
    }

    private function prepareCorrespondenceBodyForPdf(?string $html): string
    {
        $html = (string) ($html ?: '<p style="color:#777;">No body provided.</p>');
        $html = str_replace(['&amp;nbsp;', '&nbsp;', "\u{00A0}"], ' ', $html);
        $html = preg_replace('/[ \t]{2,}/u', ' ', $html);
        $html = preg_replace('/<colgroup\b[^>]*>.*?<\/colgroup>/is', '', $html);
        $html = preg_replace('/<col\b[^>]*\/?>/is', '', $html);
        $html = $this->normalizeCorrespondencePdfTables($html);
        $html = $this->inlineCorrespondencePdfIndentStyles($html);

        return $html;
    }
}
