<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Transmittal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class TransmittalController extends Controller
{
    public function index(Request $request)
    {
        $prefill = $request->query('prefill');

        if (is_string($prefill)) {
            $decodedPrefill = json_decode($prefill, true);
            $prefill = is_array($decodedPrefill) ? $decodedPrefill : null;
        }

        return view('transmittal.index', [
            'prefill' => is_array($prefill) ? $prefill : null,
        ]);
    }

    public function createFromProject(Project $project): RedirectResponse
    {
        $project->loadMissing([
            'contact:id,first_name,last_name,email,company_name',
            'company:id,company_name',
        ]);

        return redirect()->route('transmittal.index', [
            'prefill' => json_encode($this->buildProjectPrefill($project, 'SOW')),
        ]);
    }

    public function createFromRegular(Project $regular): RedirectResponse
    {
        $regular->loadMissing([
            'contact:id,first_name,last_name,email,company_name',
            'company:id,company_name',
        ]);

        return redirect()->route('transmittal.index', [
            'prefill' => json_encode($this->buildProjectPrefill($regular, 'RSAT')),
        ]);
    }

    public function data(Request $request)
    {
        $workflow = $request->get('workflow_status', 'uploaded');
        $perPage = (int) $request->get('per_page', 10);

        if ($perPage < 1) {
            $perPage = 10;
        }

        $workflowMap = [
            'uploaded' => 'Uploaded',
            'submitted' => 'Submitted',
            'accepted' => 'Accepted',
            'reverted' => 'Reverted',
            'archived' => 'Archived',
        ];

        $workflowStatus = $workflowMap[$workflow] ?? 'Uploaded';

        $paginator = Transmittal::with(['items', 'receipt'])
            ->where('workflow_status', $workflowStatus)
            ->latest()
            ->paginate($perPage);

        $rows = collect($paginator->items())
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'transmittal_no' => $item->transmittal_no,
                    'date' => optional($item->transmittal_date)->format('Y-m-d'),
                    'mode' => $item->mode,
                    'from_value' => $item->from_value,
                    'to_value' => $item->to_value,
                    'delivery_type' => $item->delivery_summary,
                    'actions' => $item->actions_summary,
                    'workflow_status' => $item->workflow_status,
                    'approval_status' => $item->approval_status,
                    'party_name' => $item->party_name,
                    'office_name' => $item->office_name,
                    'address' => $item->address,
                    'by_person_who' => $item->by_person_who,
                    'registered_mail_provider' => $item->registered_mail_provider,
                    'electronic_method' => $item->electronic_method,
                    'recipient_email' => $item->recipient_email,
                    'action_delivery' => $item->action_delivery,
                    'action_pick_up' => $item->action_pick_up,
                    'action_drop_off' => $item->action_drop_off,
                    'action_email' => $item->action_email,
                    'prepared_by_name' => $item->prepared_by_name,
                    'prepared_at' => optional($item->prepared_at)->format('Y-m-d H:i'),
                    'approved_by_name' => $item->approved_by_name,
                    'approved_position' => $item->approved_position,
                    'approved_at' => optional($item->approved_at)->format('Y-m-d H:i'),
                    'document_custodian' => $item->document_custodian,
                    'delivered_by' => $item->delivered_by,
                    'received_by' => $item->received_by,
                    'receiver_affiliation' => $item->receiver_affiliation,
                    'received_at' => optional($item->received_at)->format('Y-m-d\TH:i'),
                    'items' => $item->items->map(function ($row) {
                        return [
                            'no' => $row->item_no,
                            'particular' => $row->particular,
                            'unique_id' => $row->unique_id,
                            'qty' => $row->qty,
                            'description' => $row->description,
                            'remarks' => $row->remarks,
                            'attachment_path' => $row->attachment_path,
                            'attachment_url' => $row->attachment_path ? asset('storage/' . $row->attachment_path) : null,
                        ];
                    })->values(),
                    'can_submit' => in_array($item->workflow_status, ['Uploaded', 'Reverted'], true),
                    'preview_url' => route('transmittal.preview', $item->id),
                    'receipt_id' => optional($item->receipt)->id,
                    'receipt_no' => optional($item->receipt)->receipt_no,
                    'receipt_url' => $item->receipt ? route('transmittal.receipts.show', $item->receipt->id) : null,
                ];
            })
            ->values();

        return response()->json([
            'data' => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transmittal_date' => ['nullable', 'date'],
            'mode' => ['required', 'in:SEND,RECEIVE'],
            'party_name' => ['nullable', 'string', 'max:255'],
            'office_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'delivery_type' => ['nullable', 'string', 'max:255'],
            'by_person_who' => ['nullable', 'string', 'max:255'],
            'registered_mail_provider' => ['nullable', 'string', 'max:255'],
            'electronic_method' => ['nullable', 'string', 'max:255'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'action_delivery' => ['nullable'],
            'action_pick_up' => ['nullable'],
            'action_drop_off' => ['nullable'],
            'action_email' => ['nullable'],
            'approved_position' => ['nullable', 'string', 'max:255'],
            'document_custodian' => ['nullable', 'string', 'max:255'],
            'delivered_by' => ['nullable', 'string', 'max:255'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'receiver_affiliation' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
            'items' => ['nullable'],
            'item_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        if (empty($validated['party_name']) || empty($validated['office_name'])) {
            return response()->json(['message' => 'Party name and office name are required.'], 422);
        }

        $items = $request->input('items', []);
        if (is_string($items)) {
            $decodedItems = json_decode($items, true);
            $items = is_array($decodedItems) ? $decodedItems : [];
        }

        DB::beginTransaction();

        try {
            $transmittal = Transmittal::create([
                'transmittal_date' => $validated['transmittal_date'] ?? now()->toDateString(),
                'mode' => $validated['mode'],
                'party_name' => $validated['party_name'] ?? null,
                'office_name' => $validated['office_name'] ?? null,
                'address' => $validated['address'] ?? null,
                'delivery_type' => $validated['delivery_type'] ?? null,
                'by_person_who' => $validated['by_person_who'] ?? null,
                'registered_mail_provider' => $validated['registered_mail_provider'] ?? null,
                'electronic_method' => $validated['electronic_method'] ?? null,
                'recipient_email' => $validated['recipient_email'] ?? null,
                'action_delivery' => $request->boolean('action_delivery'),
                'action_pick_up' => $request->boolean('action_pick_up'),
                'action_drop_off' => $request->boolean('action_drop_off'),
                'action_email' => $request->boolean('action_email'),
                'prepared_by_name' => Auth::user()?->name ?? 'System User',
                'prepared_at' => now(),
                'approved_by_name' => null,
                'approved_position' => $validated['approved_position'] ?? null,
                'document_custodian' => $validated['document_custodian'] ?? null,
                'delivered_by' => $validated['delivered_by'] ?? null,
                'received_by' => $validated['received_by'] ?? null,
                'receiver_affiliation' => $validated['receiver_affiliation'] ?? null,
                'received_at' => $validated['received_at'] ?? null,
                'workflow_status' => 'Uploaded',
                'approval_status' => 'Pending',
                'submitted_by' => Auth::id(),
            ]);

            $transmittal->update([
                'transmittal_no' => 'TRN-' . str_pad((string) $transmittal->id, 5, '0', STR_PAD_LEFT),
            ]);

            foreach (($items ?? []) as $index => $item) {
                $hasContent =
                    !empty($item['particular']) || !empty($item['unique_id']) ||
                    !empty($item['qty']) || !empty($item['description']) ||
                    !empty($item['remarks']) || $request->hasFile("item_files.$index");

                if (! $hasContent) continue;

                $attachmentPath = null;
                if ($request->hasFile("item_files.$index")) {
                    $attachmentPath = $request->file("item_files.$index")
                        ->store('transmittal/item-attachments', 'public');
                }

                $transmittal->items()->create([
                    'item_no' => $item['no'] ?? ($index + 1),
                    'particular' => $item['particular'] ?? null,
                    'unique_id' => $item['unique_id'] ?? null,
                    'qty' => $item['qty'] ?? null,
                    'description' => $item['description'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                    'attachment_path' => $attachmentPath,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Transmittal saved successfully.', 'id' => $transmittal->id]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to save transmittal.', 'error' => $e->getMessage()], 500);
        }
    }

    public function submit($id)
    {
        $transmittal = Transmittal::findOrFail($id);

        if (! in_array($transmittal->workflow_status, ['Uploaded', 'Reverted'], true)) {
            return response()->json(['message' => 'Only uploaded or reverted transmittals can be submitted.'], 422);
        }

        $transmittal->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'submitted_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Transmittal submitted for approval successfully.']);
    }

    public function preview($id)
    {
        $transmittal = Transmittal::with(['items', 'receipt'])->findOrFail($id);

        $transmittalPdfUrl = route('transmittal.preview.pdf', $transmittal->id);
        $receiptPdfUrl = $transmittal->receipt
            ? route('transmittal.receipt.pdf', $transmittal->id)
            : null;

        return view('transmittal.preview', compact('transmittal', 'transmittalPdfUrl', 'receiptPdfUrl'));
    }

    public function previewPdf($id)
    {
        $transmittal = Transmittal::with(['items', 'receipt'])->findOrFail($id);

        $pdf = Pdf::loadView('transmittal.preview-pdf', compact('transmittal'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('transmittal-' . $transmittal->transmittal_no . '.pdf');
    }

    public function receiptPdf($id)
    {
        $transmittal = Transmittal::with(['receipt'])->findOrFail($id);

        if (! $transmittal->receipt) {
            abort(404, 'Receipt not found.');
        }

        $customPaper = [0, 0, 612, 255];

        $receipt = $transmittal->receipt;

        $receiptDate = $receipt && $receipt->created_at
            ? $receipt->created_at->format('Y-m-d')
            : now()->format('Y-m-d');

        $receivedAt = $transmittal->received_at
            ? $transmittal->received_at->format('Y-m-d H:i:s')
            : 'N/A';

        $preparedAt = $transmittal->prepared_at
            ? $transmittal->prepared_at->format('Y-m-d H:i:s')
            : 'N/A';

        $approvedAt = $transmittal->approved_at
            ? $transmittal->approved_at->format('Y-m-d H:i:s')
            : 'N/A';

        $deliveryType = 'N/A';
        if (($transmittal->delivery_type ?? '') === 'By Person') {
            $deliveryType = $transmittal->by_person_who
                ? 'By Person - ' . $transmittal->by_person_who
                : 'By Person';
        } elseif (($transmittal->delivery_type ?? '') === 'Registered Mail') {
            $deliveryType = $transmittal->registered_mail_provider
                ? 'Registered Mail - ' . $transmittal->registered_mail_provider
                : 'Registered Mail';
        } elseif (($transmittal->delivery_type ?? '') === 'Electronic') {
            $deliveryType = $transmittal->electronic_method
                ? 'Electronic - ' . $transmittal->electronic_method
                : 'Electronic';
        }

        $actions = collect([
            $transmittal->action_delivery ? 'Delivery' : null,
            $transmittal->action_pick_up ? 'Pick Up' : null,
            $transmittal->action_drop_off ? 'Drop Off' : null,
            $transmittal->action_email ? 'Email' : null,
        ])->filter()->implode(', ');

        if ($actions === '') {
            $actions = '—';
        }

        $fromValue = $transmittal->mode === 'SEND'
            ? ($transmittal->office_name ?? 'N/A')
            : ($transmittal->party_name ?? 'N/A');

        $toValue = $transmittal->mode === 'SEND'
            ? ($transmittal->party_name ?? 'N/A')
            : ($transmittal->office_name ?? 'N/A');

        $approvedByText = ($transmittal->approved_by_name ?? 'N/A')
            . ($transmittal->approved_position ? ' (' . $transmittal->approved_position . ')' : '');

        try {
            $pdf = Pdf::loadView('transmittal.receipt-pdf', [
                    'transmittal' => $transmittal,
                    'receipt' => $receipt,
                    'receiptDate' => $receiptDate,
                    'receivedAt' => $receivedAt,
                    'preparedAt' => $preparedAt,
                    'approvedAt' => $approvedAt,
                    'deliveryType' => $deliveryType,
                    'actions' => $actions,
                    'fromValue' => $fromValue,
                    'toValue' => $toValue,
                    'approvedByText' => $approvedByText,
                ])
                ->setPaper($customPaper);

            return $pdf->stream('receipt-' . $transmittal->receipt->receipt_no . '.pdf');
        } catch (\Throwable $e) {
            Log::error('Styled receipt PDF failed on server', [
                'transmittal_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $fallbackHtml = '
                <html>
                <body style="font-family: Arial, sans-serif; font-size: 12px;">
                    <h3>Styled receipt PDF failed on server.</h3>
                    <p>Error was logged. Please check Render logs.</p>
                    <p>Receipt No: ' . e($transmittal->receipt->receipt_no) . '</p>
                    <p>Ref No: ' . e($transmittal->transmittal_no) . '</p>
                </body>
                </html>
            ';

            $fallbackPdf = Pdf::loadHTML($fallbackHtml)->setPaper($customPaper);

            return $fallbackPdf->stream('receipt-' . $transmittal->receipt->receipt_no . '.pdf');
        }
    }

    private function buildProjectPrefill(Project $project, string $sourceLabel): array
    {
        $formCode = $sourceLabel === 'RSAT' ? 'REG-F-004' : 'PROJ-F-006';
        $contactName = trim(collect([
            $project->contact?->first_name,
            $project->contact?->last_name,
        ])->filter()->implode(' '));

        $externalParty = $contactName
            ?: ($project->client_name ?: ($project->company?->company_name ?: ''));

        $officeName = $project->business_name
            ?: ($project->company?->company_name ?: 'John Kelly & Company');

        $projectCode = $project->project_code ?: ('PROJECT-' . $project->id);
        $clientEmail = $project->contact?->email;
        $services = trim((string) $project->services);
        $products = trim((string) $project->products);
        $scopeSummary = trim((string) $project->scope_summary);

        $descriptionParts = array_values(array_filter([
            $project->name ? "Engagement: {$project->name}" : null,
            $services !== '' ? "Services: {$services}" : null,
            $products !== '' ? "Products: {$products}" : null,
            $scopeSummary !== '' ? "Scope: {$scopeSummary}" : null,
        ]));

        return [
            'source_type' => strtolower($sourceLabel),
            'source_id' => $project->id,
            'source_label' => $sourceLabel,
            'form_code' => $formCode,
            'transmittal_date' => now()->toDateString(),
            'mode' => 'SEND',
            'party_name' => $externalParty,
            'office_name' => $officeName,
            'address' => '',
            'delivery_type' => $clientEmail ? 'Electronic' : '',
            'electronic_method' => $clientEmail ? 'Email' : '',
            'recipient_email' => $clientEmail,
            'action_delivery' => false,
            'action_pick_up' => false,
            'action_drop_off' => false,
            'action_email' => (bool) $clientEmail,
            'prepared_by_name' => (string) ($project->assigned_associate ?: ''),
            'approved_by_name' => (string) ($project->assigned_consultant ?: ''),
            'approved_position' => 'Lead Consultant',
            'document_custodian' => (string) ($project->assigned_associate ?: ''),
            'delivered_by' => '',
            'received_by' => $externalParty,
            'received_at' => '',
            'items' => [[
                'no' => 1,
                'particular' => "{$sourceLabel} Documents",
                'unique_id' => $projectCode,
                'qty' => 1,
                'description' => implode(' | ', $descriptionParts),
                'remarks' => "{$sourceLabel} generated from {$projectCode}",
            ]],
        ];
    }
}
