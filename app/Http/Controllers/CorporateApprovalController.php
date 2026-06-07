<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Models\SecCoi;
use App\Models\SecAoi;
use App\Models\Bylaw;
use App\Models\BirTax;
use App\Models\GisRecord;
use App\Models\Minute;
use App\Models\NatGov;
use App\Models\Notice;
use App\Models\Permit;
use App\Models\Resolution;
use App\Models\SecretaryCertificate;
use App\Models\Accounting;
use App\Models\Banking;
use App\Models\Operation;
use App\Models\Correspondence;
use App\Models\Legal;
use App\Models\StockTransferCertificate;
use App\Models\Transmittal;
use App\Models\TransmittalReceipt;
use App\Mail\CorporateStatusNotificationMail;
use App\Mail\TransmittalDeliveryMail;
use App\Notifications\SystemRealtimeNotification;

class CorporateApprovalController extends Controller
{
    private function authorizeApprover()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasPermission('approve_corporate')) {
            abort(403, 'Unauthorized');
        }
    }

    private function normalizeWorkflow($record): string
    {
        if (!empty($record->workflow_status)) {
            return $record->workflow_status;
        }

        return match ($record->approval_status) {
            'Approved' => 'Accepted',
            'Needs Revision', 'Rejected' => 'Reverted',
            default => 'Uploaded',
        };
    }

    private function getModuleName(string $module): string
    {
        return match ($module) {
            'sec-coi' => 'SEC-COI',
            'sec-aoi' => 'SEC-AOI',
            'bylaws' => 'Bylaws',
            'gis' => 'GIS',
            'lgu' => 'LGU',
            'bir-tax' => 'BIR & Tax',
            'natgov' => 'NatGov',
            'accounting' => 'Accounting',
            'banking' => 'Banking',
            'operations' => 'Operations',
            'correspondence' => 'Correspondence',
            'legal' => 'Legal',
            'transmittal' => 'Transmittal',
            default => 'Corporate Module',
        };
    }

    private function buildTransmittalDeliveryDetail($record): string
    {
        return match ($record->delivery_type) {
            'By Person' => $record->by_person_who ? 'By Person - ' . $record->by_person_who : 'By Person',
            'Registered Mail' => $record->registered_mail_provider ? 'Registered Mail - ' . $record->registered_mail_provider : 'Registered Mail',
            'Electronic' => $record->electronic_method ? 'Electronic - ' . $record->electronic_method : 'Electronic',
            default => '',
        };
    }

    private function buildTransmittalActionsSummary($record): string
    {
        $actions = [];

        if ($record->action_delivery) $actions[] = 'Delivery';
        if ($record->action_pick_up) $actions[] = 'Pick Up';
        if ($record->action_drop_off) $actions[] = 'Drop Off';
        if ($record->action_email) $actions[] = 'Email';

        return implode(', ', $actions);
    }

    private function generateTransmittalReceipt($record): void
    {
        if ($record->receipt) {
            return;
        }

        $nextId = (TransmittalReceipt::max('id') ?? 0) + 1;
        $receiptNo = 'TRR-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);

        TransmittalReceipt::create([
            'transmittal_id' => $record->id,
            'receipt_no' => $receiptNo,
            'receipt_date' => now()->toDateString(),
            'mode' => $record->mode,
            'from_name' => $record->from_value,
            'to_name' => $record->to_value,
            'office_name' => $record->office_name,
            'delivery_type' => $record->delivery_type,
            'delivery_detail' => $this->buildTransmittalDeliveryDetail($record),
            'recipient_email' => $record->recipient_email,
            'actions_summary' => $this->buildTransmittalActionsSummary($record),
            'prepared_by_name' => $record->prepared_by_name,
            'approved_by_name' => $record->approved_by_name,
            'approved_position' => $record->approved_position,
            'document_custodian' => $record->document_custodian,
            'delivered_by' => $record->delivered_by,
            'received_by' => $record->received_by,
            'received_at' => $record->received_at,
            'generated_by' => Auth::id(),
        ]);
    }

    private function sendTransmittalDeliveryEmail($record): void
    {
        $record->loadMissing(['items', 'receipt']);

        if (
            $record->delivery_type !== 'Electronic' ||
            $record->electronic_method !== 'Email' ||
            empty($record->recipient_email)
        ) {
            return;
        }

        try {
            Mail::to($record->recipient_email)->send(new TransmittalDeliveryMail($record));

            Log::info('Transmittal delivery email sent successfully.', [
                'transmittal_id' => $record->id,
                'recipient_email' => $record->recipient_email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Transmittal delivery email failed.', [
                'transmittal_id' => $record->id,
                'recipient_email' => $record->recipient_email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getCorporationName($record, string $module): string
    {
        return match ($module) {
            'sec-coi' => $record->corporate_name ?? '',
            'sec-aoi' => $record->corporation_name ?? '',
            'bylaws' => $record->corporation_name ?? '',
            'gis' => $record->corporation_name ?? '',
            'lgu' => trim(($record->company_name ?? 'Company') . ' - ' . ($record->permit_type ?? 'LGU Permit')),
            'bir-tax' => trim(($record->company_name ?? $record->tax_payer ?? 'Company') . ' - ' . ($record->form_type ?? 'BIR Filing')),
            'natgov' => trim(($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->agency ?? 'NatGov Record')),
            'accounting' => trim(($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->statement_type ?? 'Accounting Report')),
            'banking' => trim(($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->bank ?? 'Banking Record')),
            'operations' => trim(($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->document_title ?? $record->operation_type ?? 'Operations Record')),
            'correspondence' => ($record->type ?? 'Correspondence') . ' - ' . ($record->subject ?? ''),
            'legal' => trim(($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->document_title ?? $record->document_type ?? $record->legal_type ?? 'Legal Record')),
            'transmittal' => ($record->mode ?? 'Transmittal') . ' - ' . ($record->transmittal_no ?? ('TRN-' . $record->id)),
            default => '',
        };
    }

    private function getReferenceNumber($record, string $module): string
    {
        return match ($module) {
            'lgu' => $record->permit_number ?? '',
            'bir-tax' => $record->tin ?? '',
            'natgov' => $record->registration_no ?? '',
            'accounting' => $record->statement_type ?? '',
            'banking' => $record->bank_doc ?? '',
            'operations' => $record->document_type ?? '',
            'correspondence' => $record->tin ?? '',
            'legal' => $record->document_type ?? $record->legal_type ?? '',
            'transmittal' => $record->transmittal_no ?? '',
            default => $record->company_reg_no ?? '',
        };
    }

    private function dashboardStatusBadge(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($status) {
            'Accepted', 'Approved', 'Released', 'Issued', 'Completed', 'Paid' => 'Accepted',
            'Reverted', 'Rejected', 'Cancelled', 'Voided' => 'Reverted',
            'Archived' => 'Archived',
            'Submitted', 'Pending', 'Draft' => 'Submitted',
            default => in_array($normalized, ['active', 'open', 'released', 'issued', 'approved', 'completed', 'paid'], true)
                ? 'Accepted'
                : (in_array($normalized, ['cancelled', 'canceled', 'voided', 'rejected', 'reverted'], true)
                    ? 'Reverted'
                    : (in_array($normalized, ['submitted', 'pending', 'draft'], true)
                        ? 'Submitted'
                        : ($status ?: 'Submitted'))),
        };
    }

    private function addDocumentWorkflowItem(\Illuminate\Support\Collection $items, array $data): void
    {
        $data += [
            'approve_route' => null,
            'reject_route' => null,
            'revise_route' => null,
            'archive_route' => null,
            'supports_actions' => false,
        ];

        $data['status'] = $this->dashboardStatusBadge($data['status'] ?? 'Submitted');
        $data['show_route'] = $data['show_route'] ?? '#';
        $items->push((object) $data);
    }

    private function sendStatusEmail($record, string $module, string $decision, ?string $reviewNote = null): void
    {
        if (empty($record->submitted_by)) {
            Log::warning('Corporate status email skipped: submitted_by is empty.', [
                'module' => $module,
                'record_id' => $record->id ?? null,
            ]);
            return;
        }

        $employee = User::find($record->submitted_by);

        if (!$employee || empty($employee->email)) {
            Log::warning('Corporate status email skipped: employee not found or email missing.', [
                'module' => $module,
                'record_id' => $record->id ?? null,
                'submitted_by' => $record->submitted_by,
            ]);
            return;
        }

        try {
            Mail::to($employee->email)->send(
                new CorporateStatusNotificationMail(
                    $employee->name,
                    $this->getModuleName($module),
                    $this->getCorporationName($record, $module),
                    $this->getReferenceNumber($record, $module),
                    $decision,
                    $reviewNote
                )
            );

            Log::info('Corporate status email sent successfully.', [
                'module' => $module,
                'record_id' => $record->id ?? null,
                'email' => $employee->email,
                'decision' => $decision,
            ]);
        } catch (\Throwable $e) {
            Log::error('Corporate status email failed.', [
                'module' => $module,
                'record_id' => $record->id ?? null,
                'email' => $employee->email,
                'decision' => $decision,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendStatusRealtimeNotification($record, string $module, string $decision, ?string $reviewNote = null): void
    {
        if (empty($record->submitted_by)) {
            return;
        }

        $submitter = User::find($record->submitted_by);

        if (! $submitter) {
            return;
        }

        $moduleName = $this->getModuleName($module);
        $recordName = $this->getCorporationName($record, $module);
        $message = "Your {$moduleName} submission";

        if ($recordName !== '') {
            $message .= " ({$recordName})";
        }

        $message .= match ($decision) {
            'Approved' => ' has been approved.',
            'Needs Revision' => ' was reverted and needs revision.',
            'Rejected' => ' was rejected.',
            default => ' has an approval update.',
        };

        if (filled($reviewNote)) {
            $message .= ' Note: ' . $reviewNote;
        }

        $submitter->notify(new SystemRealtimeNotification(
            "{$moduleName} submission {$decision}",
            $message,
            $this->submittedRecordUrl($record, $module),
            'Corporate',
            'fa-building'
        ));
    }

    private function submittedRecordUrl($record, string $module): string
    {
        return match ($module) {
            'lgu' => route('corporate.lgu', ['record' => $record->id, 'tab' => strtolower((string) ($record->workflow_status ?? 'uploaded'))]),
            'bir-tax' => route('bir-tax.preview', ['birTax' => $record->id]),
            'natgov' => route('natgov.preview', ['natgov' => $record->id]),
            'accounting' => route('corporate.accounting', ['record' => $record->id, 'tab' => strtolower((string) ($record->workflow_status ?? 'uploaded'))]),
            'banking' => route('corporate.banking', ['record' => $record->id, 'tab' => strtolower((string) ($record->workflow_status ?? 'uploaded'))]),
            'operations' => route('corporate.operations', ['record' => $record->id, 'tab' => strtolower((string) ($record->workflow_status ?? 'uploaded'))]),
            'legal' => route('corporate.legal', ['record' => $record->id, 'tab' => strtolower((string) ($record->workflow_status ?? 'uploaded'))]),
            default => route('admin.corporate.dashboard'),
        };
    }

    private function canAppearInAdminDashboard(string $workflow): bool
    {
        return in_array($workflow, ['Submitted', 'Accepted', 'Reverted', 'Archived'], true);
    }

    private function ensureSubmittedForDecision($record)
    {
        $workflow = $this->normalizeWorkflow($record);

        if ($workflow !== 'Submitted') {
            return back()->with('error', 'Only submitted records can be approved, rejected, or revised.');
        }

        return null;
    }

    public function dashboard()
    {
        $this->authorizeApprover();

        $items = collect();

        foreach (SecCoi::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'SEC-COI',
                'title' => $row->corporate_name,
                'company_reg_no' => $row->company_reg_no,
                'uploaded_by' => $row->submitted_by,
                'date_uploaded' => $row->date_upload,
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('corporate.formation.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'sec-coi', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'sec-coi', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'sec-coi', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'sec-coi', 'id' => $row->id]),
            ]);
        }

        foreach (SecAoi::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'SEC-AOI',
                'title' => $row->corporation_name,
                'company_reg_no' => $row->company_reg_no,
                'uploaded_by' => $row->uploaded_by,
                'date_uploaded' => $row->date_upload,
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('corporate.sec_aoi.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'sec-aoi', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'sec-aoi', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'sec-aoi', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'sec-aoi', 'id' => $row->id]),
            ]);
        }

        foreach (Bylaw::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'Bylaws',
                'title' => $row->corporation_name,
                'company_reg_no' => $row->company_reg_no,
                'uploaded_by' => $row->uploaded_by,
                'date_uploaded' => $row->date_upload,
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('corporate.bylaws.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'bylaws', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'bylaws', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'bylaws', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'bylaws', 'id' => $row->id]),
            ]);
        }

        foreach (GisRecord::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'GIS',
                'title' => $row->corporation_name,
                'company_reg_no' => $row->company_reg_no,
                'uploaded_by' => $row->uploaded_by,
                'date_uploaded' => $row->created_at ? $row->created_at->format('Y-m-d') : '',
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('gis.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'gis', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'gis', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'gis', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'gis', 'id' => $row->id]),
            ]);
        }

        foreach (Permit::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'LGU',
                'title' => trim(($row->company_name ?? 'Company') . ' - ' . ($row->permit_type ?? 'LGU Permit')),
                'company_reg_no' => $row->permit_number,
                'uploaded_by' => $row->uploaded_by ?: $row->user,
                'date_uploaded' => $row->date_uploaded_at ? $row->date_uploaded_at->format('Y-m-d') : ($row->created_at ? $row->created_at->format('Y-m-d') : ''),
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('corporate.lgu', ['record' => $row->id, 'tab' => strtolower($workflow)]),
                'preview_route' => route('permits.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'lgu', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'lgu', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'lgu', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'lgu', 'id' => $row->id]),
            ]);
        }

        foreach (BirTax::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (! $this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'BIR & Tax',
                'title' => trim(($row->company_name ?? $row->tax_payer ?? 'Company') . ' - ' . ($row->form_type ?? 'BIR Filing')),
                'company_reg_no' => $row->tin ?? '',
                'uploaded_by' => $row->uploaded_by ?: $row->user,
                'date_uploaded' => $row->date_uploaded_at ? $row->date_uploaded_at->format('Y-m-d') : ($row->date_uploaded ? $row->date_uploaded->format('Y-m-d') : ($row->created_at ? $row->created_at->format('Y-m-d') : '')),
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('bir-tax', ['record' => $row->id, 'tab' => strtolower($workflow)]),
                'preview_route' => route('bir-tax.preview', ['birTax' => $row->id]),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'bir-tax', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'bir-tax', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'bir-tax', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'bir-tax', 'id' => $row->id]),
            ]);
        }

        foreach (NatGov::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (! $this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'NatGov',
                'title' => trim(($row->company_name ?? $row->client ?? 'Company') . ' - ' . ($row->agency ?? 'NatGov Record')),
                'company_reg_no' => $row->registration_no ?? '',
                'uploaded_by' => $row->uploaded_by ?: $row->user,
                'date_uploaded' => $row->date_uploaded_at ? $row->date_uploaded_at->format('Y-m-d') : ($row->date_uploaded ? $row->date_uploaded->format('Y-m-d') : ($row->created_at ? $row->created_at->format('Y-m-d') : '')),
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('natgov', ['record' => $row->id, 'tab' => strtolower($workflow)]),
                'preview_route' => route('natgov.preview', ['natgov' => $row->id]),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'natgov', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'natgov', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'natgov', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'natgov', 'id' => $row->id]),
            ]);
        }

        foreach (Accounting::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'Accounting',
                'title' => trim(($row->company_name ?? $row->client ?? 'Company') . ' - ' . ($row->statement_type ?? 'Accounting Report')),
                'company_reg_no' => $row->statement_type ?? '',
                'uploaded_by' => $row->uploaded_by ?: $row->user,
                'date_uploaded' => $row->date_uploaded_at ? $row->date_uploaded_at->format('Y-m-d') : ($row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d') : ''),
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('corporate.accounting', ['record' => $row->id, 'tab' => strtolower($workflow)]),
                'preview_route' => route('corporate.accounting.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'accounting', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'accounting', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'accounting', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'accounting', 'id' => $row->id]),
            ]);
        }

        foreach (Banking::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'Banking',
                'title' => trim(($row->company_name ?? $row->client ?? 'Company') . ' - ' . ($row->bank ?? 'Banking Record')),
                'company_reg_no' => $row->bank_doc ?? '',
                'uploaded_by' => $row->uploaded_by ?: $row->user,
                'date_uploaded' => $row->date_uploaded_at ? $row->date_uploaded_at->format('Y-m-d') : ($row->date_uploaded ? \Carbon\Carbon::parse($row->date_uploaded)->format('Y-m-d') : ''),
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('corporate.banking', ['record' => $row->id, 'tab' => strtolower($workflow)]),
                'preview_route' => route('banking.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'banking', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'banking', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'banking', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'banking', 'id' => $row->id]),
            ]);
        }

        foreach (Operation::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'Operations',
                'title' => trim(($row->company_name ?? $row->client ?? 'Company') . ' - ' . ($row->document_title ?? $row->operation_type ?? 'Operations Record')),
                'company_reg_no' => $row->document_type ?? '',
                'uploaded_by' => $row->uploaded_by ?: $row->user,
                'date_uploaded' => $row->date_uploaded_at ? $row->date_uploaded_at->format('Y-m-d') : ($row->date_uploaded ? \Carbon\Carbon::parse($row->date_uploaded)->format('Y-m-d') : ''),
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('corporate.operations', ['record' => $row->id, 'tab' => strtolower($workflow)]),
                'preview_route' => route('operations.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'operations', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'operations', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'operations', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'operations', 'id' => $row->id]),
            ]);
        }

        foreach (Correspondence::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            /*
             * Correspondence now has its own dedicated Admin Correspondence dashboard.
             * Keep it visible here for monitoring/searching only, but do not approve/reject/revise it here.
             * This prevents duplicate approval logic between Corporate Approval Dashboard and Admin Correspondence.
             */
            $items->push((object) [
                'id' => $row->id,
                'module' => 'Correspondence',
                'title' => ($row->type ?? 'Correspondence') . ' - ' . ($row->subject ?? ''),
                'company_reg_no' => $row->registration_number ?: ($row->tin ?? ''),
                'uploaded_by' => $row->creator?->name ?: ($row->user ?: $row->submitted_by),
                'date_uploaded' => $row->correspondence_date
                    ? \Carbon\Carbon::parse($row->correspondence_date)->format('Y-m-d')
                    : ($row->created_at ? $row->created_at->format('Y-m-d') : ''),
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('admin.correspondence.show', $row->id),
                'approve_route' => null,
                'reject_route' => null,
                'revise_route' => null,
                'archive_route' => null,
                'supports_actions' => false,
            ]);
        }

        foreach (Legal::latest()->get() as $row) {
            $workflow = $this->normalizeWorkflow($row);
            if (!$this->canAppearInAdminDashboard($workflow)) continue;

            $items->push((object) [
                'id' => $row->id,
                'module' => 'Legal',
                'title' => trim(($row->company_name ?? $row->client ?? 'Company') . ' - ' . ($row->document_title ?? $row->document_type ?? $row->legal_type ?? 'Legal Record')),
                'company_reg_no' => $row->document_type ?? $row->legal_type ?? '',
                'uploaded_by' => $row->uploaded_by ?: $row->user,
                'date_uploaded' => $row->date_uploaded_at ? $row->date_uploaded_at->format('Y-m-d') : ($row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d') : ''),
                'status' => $workflow,
                'approval_status' => $row->approval_status,
                'show_route' => route('corporate.legal', ['record' => $row->id, 'tab' => strtolower($workflow)]),
                'preview_route' => route('legal.show', $row->id),
                'approve_route' => route('corporate.approvals.approve', ['module' => 'legal', 'id' => $row->id]),
                'reject_route' => route('corporate.approvals.reject', ['module' => 'legal', 'id' => $row->id]),
                'revise_route' => route('corporate.approvals.revise', ['module' => 'legal', 'id' => $row->id]),
                'archive_route' => route('corporate.approvals.archive', ['module' => 'legal', 'id' => $row->id]),
            ]);
        }



        foreach (Notice::latest()->get() as $row) {
            if (empty($row->document_path)) {
                continue;
            }

            $this->addDocumentWorkflowItem($items, [
                'id' => $row->id,
                'module' => 'Notices',
                'title' => $row->notice_number ?: ('Notice #' . $row->id),
                'company_reg_no' => $row->notice_number ?? '',
                'uploaded_by' => $row->uploaded_by,
                'date_uploaded' => $row->date_updated ? \Carbon\Carbon::parse($row->date_updated)->format('Y-m-d') : ($row->date_of_notice ? $row->date_of_notice->format('Y-m-d') : ''),
                'status' => 'Submitted',
                'approval_status' => 'Pending',
                'show_route' => route('notices.preview', $row),
            ]);
        }

        foreach (Minute::latest()->get() as $row) {
            if (empty($row->document_path) && empty($row->approved_minutes_path)) {
                continue;
            }

            $status = $row->approved_minutes_path ? 'Accepted' : 'Submitted';

            $this->addDocumentWorkflowItem($items, [
                'id' => $row->id,
                'module' => 'Minutes',
                'title' => $row->minutes_ref ?: ('Minutes #' . $row->id),
                'company_reg_no' => $row->minutes_ref ?? '',
                'uploaded_by' => $row->uploaded_by,
                'date_uploaded' => $row->date_uploaded ? $row->date_uploaded->format('Y-m-d') : '',
                'status' => $status,
                'approval_status' => $status === 'Accepted' ? 'Approved' : 'Pending',
                'show_route' => route('minutes.preview', $row),
            ]);
        }

        foreach (Resolution::latest()->get() as $row) {
            if (empty($row->draft_file_path) && empty($row->notarized_file_path)) {
                continue;
            }

            $status = $row->notarized_file_path ? 'Accepted' : 'Submitted';

            $this->addDocumentWorkflowItem($items, [
                'id' => $row->id,
                'module' => 'Resolutions',
                'title' => $row->resolution_no ?: ('Resolution #' . $row->id),
                'company_reg_no' => $row->resolution_no ?? '',
                'uploaded_by' => $row->uploaded_by,
                'date_uploaded' => $row->date_uploaded ? $row->date_uploaded->format('Y-m-d') : '',
                'status' => $status,
                'approval_status' => $status === 'Accepted' ? 'Approved' : 'Pending',
                'show_route' => route('resolutions.preview', $row),
            ]);
        }

        foreach (SecretaryCertificate::latest()->get() as $row) {
            if (empty($row->document_path)) {
                continue;
            }

            $this->addDocumentWorkflowItem($items, [
                'id' => $row->id,
                'module' => 'Secretary Certificates',
                'title' => $row->certificate_no ?: ('Secretary Certificate #' . $row->id),
                'company_reg_no' => $row->certificate_no ?? '',
                'uploaded_by' => $row->uploaded_by,
                'date_uploaded' => $row->date_uploaded ? $row->date_uploaded->format('Y-m-d') : '',
                'status' => 'Submitted',
                'approval_status' => 'Pending',
                'show_route' => route('secretary-certificates.preview', $row),
            ]);
        }

        foreach (StockTransferCertificate::latest()->get() as $row) {
            if (!is_null($row->source_certificate_id)) {
                continue;
            }

            $status = $this->dashboardStatusBadge($row->status ?: 'Submitted');

            if (!in_array($status, ['Submitted', 'Reverted'], true)) {
                continue;
            }

            $this->addDocumentWorkflowItem($items, [
                'id' => $row->id,
                'module' => 'Stock Transfer Book - Certificate',
                'title' => $row->stock_number ?: ('Certificate #' . $row->id),
                'company_reg_no' => $row->stock_number ?? '',
                'uploaded_by' => $row->uploaded_by ?? '',
                'date_uploaded' => $row->date_uploaded ? $row->date_uploaded->format('Y-m-d') : '',
                'status' => $status,
                'approval_status' => $status === 'Reverted' ? 'Rejected' : 'Pending',
                'show_route' => route('stock-transfer-book.certificates.show', $row),
            ]);
        }

        $items = $items->sortByDesc('id')->values();

        $pendingCount = $items->where('status', 'Submitted')->count();
        $approvedCount = $items->where('status', 'Accepted')->count();
        $rejectedCount = $items->where('approval_status', 'Rejected')->count();
        $revisionCount = $items->where('status', 'Reverted')->count();

        return view('admin.corporate-dashboard', compact(
            'items',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'revisionCount'
        ));
    }

    private function resolveModel($module, $id)
    {
        return match ($module) {
            'sec-coi' => SecCoi::findOrFail($id),
            'sec-aoi' => SecAoi::findOrFail($id),
            'bylaws' => Bylaw::findOrFail($id),
            'gis' => GisRecord::findOrFail($id),
            'lgu' => Permit::findOrFail($id),
            'bir-tax' => BirTax::findOrFail($id),
            'natgov' => NatGov::findOrFail($id),
            'accounting' => Accounting::findOrFail($id),
            'banking' => Banking::findOrFail($id),
            'operations' => Operation::findOrFail($id),
            'correspondence' => Correspondence::findOrFail($id),
            'legal' => Legal::findOrFail($id),
            'transmittal' => Transmittal::with(['items', 'receipt'])->findOrFail($id),
            default => abort(404),
        };
    }

    public function approve($module, $id)
    {

        if ($module === 'correspondence') {
            return redirect()
                ->route('admin.correspondence.dashboard')
                ->with('error', 'Correspondence approvals are handled in the Admin Correspondence dashboard.');
        }

        $this->authorizeApprover();

        $record = $this->resolveModel($module, $id);

        if ($response = $this->ensureSubmittedForDecision($record)) {
            return $response;
        }

        $updateData = [
            'approval_status' => 'Approved',
            'workflow_status' => 'Accepted',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_note' => null,
        ];

        if ($module === 'transmittal') {
            $updateData['approved_by_name'] = Auth::user()?->name ?? 'Admin User';
        }

        $record->update($updateData);

        if ($module === 'transmittal') {
            $this->generateTransmittalReceipt($record);
            $record->refresh()->load(['items', 'receipt']);
            $this->sendTransmittalDeliveryEmail($record);
        }

        $this->sendStatusEmail($record, $module, 'Approved', null);
        $this->sendStatusRealtimeNotification($record, $module, 'Approved', null);

        return back()->with('success', 'Record approved successfully.');
    }

    public function reject(Request $request, $module, $id)
    {

        if ($module === 'correspondence') {
            return redirect()
                ->route('admin.correspondence.dashboard')
                ->with('error', 'Correspondence approvals are handled in the Admin Correspondence dashboard.');
        }

        $this->authorizeApprover();

        $record = $this->resolveModel($module, $id);

        if ($response = $this->ensureSubmittedForDecision($record)) {
            return $response;
        }

        $record->update([
            'approval_status' => 'Rejected',
            'workflow_status' => 'Reverted',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_note' => $request->review_note,
        ]);

        $this->sendStatusEmail($record, $module, 'Rejected', $request->review_note);
        $this->sendStatusRealtimeNotification($record, $module, 'Rejected', $request->review_note);

        return back()->with('success', 'Record rejected successfully.');
    }

    public function revise(Request $request, $module, $id)
    {

        if ($module === 'correspondence') {
            return redirect()
                ->route('admin.correspondence.dashboard')
                ->with('error', 'Correspondence approvals are handled in the Admin Correspondence dashboard.');
        }

        $this->authorizeApprover();

        $record = $this->resolveModel($module, $id);

        if ($response = $this->ensureSubmittedForDecision($record)) {
            return $response;
        }

        $record->update([
            'approval_status' => 'Needs Revision',
            'workflow_status' => 'Reverted',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_note' => $request->review_note,
        ]);

        $this->sendStatusEmail($record, $module, 'Needs Revision', $request->review_note);
        $this->sendStatusRealtimeNotification($record, $module, 'Needs Revision', $request->review_note);

        return back()->with('success', 'Record marked as needs revision.');
    }

    public function archive($module, $id)
    {

        if ($module === 'correspondence') {
            return redirect()
                ->route('admin.correspondence.dashboard')
                ->with('error', 'Correspondence approvals are handled in the Admin Correspondence dashboard.');
        }

        $this->authorizeApprover();

        $record = $this->resolveModel($module, $id);

        $workflow = $this->normalizeWorkflow($record);

        if ($workflow === 'Uploaded') {
            return back()->with('error', 'Draft records cannot be archived from the admin dashboard.');
        }

        $record->update([
            'workflow_status' => 'Archived',
        ]);

        return back()->with('success', 'Record archived successfully.');
    }
}
