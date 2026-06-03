<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Transmittal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

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
            'contactOptions' => $this->transmittalContactOptions(),
            'employeeOptions' => $this->transmittalEmployeeOptions(),
            'companyOptions' => $this->transmittalCompanyOptions(),
            'corporateContext' => $this->transmittalCorporateContext(),
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

        $paginator = Transmittal::with(['items', 'receipt', 'attachments'])
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
                    'attachments_count' => $item->attachments->count(),
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
            'approved_by_name' => ['nullable', 'string', 'max:255'],
            'approved_position' => ['nullable', 'string', 'max:255'],
            'document_custodian' => ['nullable', 'string', 'max:255'],
            'delivered_by' => ['nullable', 'string', 'max:255'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'receiver_affiliation' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
            'items' => ['nullable'],
            'item_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt,csv', 'max:20480'],
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
                'approved_by_name' => $validated['approved_by_name'] ?? null,
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

                if (! $hasContent) {
                    continue;
                }

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

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $uploadedFile) {
                    if (! $uploadedFile) {
                        continue;
                    }

                    $path = $uploadedFile->store('transmittal/attachments', 'public');

                    $transmittal->attachments()->create([
                        'file_path' => $path,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'mime_type' => $uploadedFile->getClientMimeType(),
                        'size' => $uploadedFile->getSize(),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
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
        $transmittal = Transmittal::with(['items', 'receipt', 'attachments'])->findOrFail($id);

        $transmittalPdfUrl = route('transmittal.preview.pdf', $transmittal->id);
        $receiptPdfUrl = $transmittal->receipt
            ? route('transmittal.receipt.pdf', $transmittal->id)
            : null;

        $corporateContext = $this->transmittalCorporateContext();

        return view('transmittal.preview', compact('transmittal', 'transmittalPdfUrl', 'receiptPdfUrl', 'corporateContext'));
    }

    public function previewPdf($id)
    {
        $transmittal = Transmittal::with(['items', 'receipt', 'attachments'])->findOrFail($id);

        $approvedByDisplay = $this->resolveApprovedByName($transmittal);
        $corporateContext = $this->transmittalCorporateContext();

        $pdf = Pdf::loadView('transmittal.preview-pdf', [
                'transmittal' => $transmittal,
                'approvedByDisplay' => $approvedByDisplay,
                'corporateContext' => $corporateContext,
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('transmittal-' . $transmittal->transmittal_no . '.pdf');
    }

    public function sendEmail($id)
    {
        $transmittal = Transmittal::with(['items', 'receipt', 'attachments'])->findOrFail($id);

        $recipient = trim((string) $transmittal->recipient_email);
        if ($recipient === '') {
            return back()->with('error', 'Recipient email is required before sending the transmittal.');
        }

        $approvedByDisplay = $this->resolveApprovedByName($transmittal);
        $corporateContext = $this->transmittalCorporateContext();

        $pdf = Pdf::loadView('transmittal.preview-pdf', [
                'transmittal' => $transmittal,
                'approvedByDisplay' => $approvedByDisplay,
                'corporateContext' => $corporateContext,
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

        $pdfFileName = 'transmittal-' . Str::slug((string) $transmittal->transmittal_no, '-') . '.pdf';
        $pdfOutput = $pdf->output();

        $fileAttachments = [];

        foreach ($transmittal->items as $item) {
            if (! empty($item->attachment_path) && Storage::disk('public')->exists($item->attachment_path)) {
                $fileAttachments[] = [
                    'path' => Storage::disk('public')->path($item->attachment_path),
                    'name' => basename($item->attachment_path),
                ];
            }
        }

        foreach ($transmittal->attachments as $attachment) {
            if (! empty($attachment->file_path) && Storage::disk('public')->exists($attachment->file_path)) {
                $fileAttachments[] = [
                    'path' => Storage::disk('public')->path($attachment->file_path),
                    'name' => $attachment->original_name ?: basename($attachment->file_path),
                ];
            }
        }

        Mail::send('emails.transmittal-sent', [
            'transmittal' => $transmittal,
            'corporateContext' => $corporateContext,
        ], function ($message) use ($recipient, $transmittal, $pdfOutput, $pdfFileName, $fileAttachments) {
            $message->to($recipient)
                ->subject('Transmittal Form ' . ($transmittal->transmittal_no ?? ''))
                ->attachData($pdfOutput, $pdfFileName, ['mime' => 'application/pdf']);

            foreach ($fileAttachments as $attachment) {
                $message->attach($attachment['path'], ['as' => $attachment['name']]);
            }
        });

        return back()->with('success', 'Transmittal email sent successfully to ' . $recipient . '.');
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

        $corporateContext = $this->transmittalCorporateContext();

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
                    'corporateContext' => $corporateContext,
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
            'approved_position' => 'Operations Manager',
            'document_custodian' => '',
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

    private function transmittalContactOptions(): array
    {
        if (! Schema::hasTable('contacts')) {
            return [];
        }

        $columns = Schema::getColumnListing('contacts');
        $select = array_values(array_intersect([
            'id', 'first_name', 'middle_name', 'middle_initial', 'last_name', 'name_extension',
            'company_name', 'email', 'phone'
        ], $columns));

        if (! in_array('id', $select, true)) {
            $select[] = 'id';
        }

        return DB::table('contacts')
            ->select($select)
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(function ($contact) {
                $fullName = trim(collect([
                    $contact->first_name ?? null,
                    $contact->middle_initial ?? null,
                    $contact->middle_name ?? null,
                    $contact->last_name ?? null,
                    $contact->name_extension ?? null,
                ])->filter()->implode(' '));

                $company = trim((string) ($contact->company_name ?? ''));
                $label = $fullName !== '' ? $fullName : ($company !== '' ? $company : 'Contact #' . $contact->id);

                return [
                    'id' => $contact->id,
                    'type' => 'contact',
                    'label' => $label,
                    'affiliation' => $company,
                    'email' => $contact->email ?? '',
                    'phone' => $contact->phone ?? '',
                ];
            })
            ->values()
            ->all();
    }

    private function transmittalEmployeeOptions(): array
    {
        if (Schema::hasTable('employees')) {
            $columns = Schema::getColumnListing('employees');
            $select = array_values(array_intersect([
                'id', 'employee_id', 'full_name', 'first_name', 'middle_name', 'last_name',
                'work_email', 'company_email', 'personal_email', 'email', 'department', 'position'
            ], $columns));

            if (! in_array('id', $select, true)) {
                $select[] = 'id';
            }

            return DB::table('employees')
                ->select($select)
                ->orderByDesc('id')
                ->limit(500)
                ->get()
                ->map(function ($employee) {
                    $fullName = trim((string) ($employee->full_name ?? ''));

                    if ($fullName === '') {
                        $fullName = trim(collect([
                            $employee->first_name ?? null,
                            $employee->middle_name ?? null,
                            $employee->last_name ?? null,
                        ])->filter()->implode(' '));
                    }

                    return [
                        'id' => $employee->id,
                        'type' => 'employee',
                        'label' => $fullName !== '' ? $fullName : 'Employee #' . $employee->id,
                        'affiliation' => trim((string) ($employee->department ?? '')),
                        'email' => $employee->work_email ?? ($employee->company_email ?? ($employee->email ?? ($employee->personal_email ?? ''))),
                        'position' => $employee->position ?? '',
                    ];
                })
                ->values()
                ->all();
        }

        if (! Schema::hasTable('users')) {
            return [];
        }

        return DB::table('users')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'type' => 'employee',
                'label' => $user->name ?: $user->email,
                'affiliation' => 'Employee',
                'email' => $user->email ?? '',
                'position' => '',
            ])
            ->values()
            ->all();
    }

    private function transmittalCompanyOptions(): array
    {
        if (! Schema::hasTable('companies')) {
            return [];
        }

        $columns = Schema::getColumnListing('companies');
        $select = array_values(array_intersect(['id', 'company_name', 'name', 'email', 'address'], $columns));

        if (! in_array('id', $select, true)) {
            $select[] = 'id';
        }

        return DB::table('companies')
            ->select($select)
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(function ($company) {
                $label = trim((string) ($company->company_name ?? ($company->name ?? '')));

                return [
                    'id' => $company->id,
                    'label' => $label !== '' ? $label : 'Company #' . $company->id,
                    'email' => $company->email ?? '',
                    'address' => $company->address ?? '',
                ];
            })
            ->values()
            ->all();
    }

    private function resolveApprovedByName(Transmittal $transmittal): string
    {
        if (! empty($transmittal->approved_by) && Schema::hasTable('users')) {
            $userColumns = Schema::getColumnListing('users');
            $nameColumn = in_array('name', $userColumns, true) ? 'name' : (in_array('email', $userColumns, true) ? 'email' : null);

            if ($nameColumn) {
                $name = DB::table('users')
                    ->where('id', $transmittal->approved_by)
                    ->value($nameColumn);

                if (trim((string) $name) !== '') {
                    return trim((string) $name);
                }
            }
        }

        return trim((string) ($transmittal->approved_by_name ?? ''));
    }

    private function transmittalCorporateContext(): array
    {
        $fallback = [
            'companyName' => 'John Kelly & Company',
            'secRegNo' => '',
            'principalAddress' => '',
            'logoUrl' => asset('images/jknc_logo.png'),
            'logoBase64' => null,
        ];

        if (! Schema::hasTable('gis_records')) {
            return $fallback;
        }

        $buildCorporateGisQuery = function () {
            $query = DB::table('gis_records');

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT FIX
            |--------------------------------------------------------------------------
            | Transmittal belongs to the internal Corporate/Operations module.
            |
            | Corporate GIS records = company_id IS NULL
            | Company module GIS records = company_id = selected company ID
            |
            | This prevents Transmittal from using Company GIS logos/names/address
            | like AWEAWRREAWR.
            |--------------------------------------------------------------------------
            */
            if (Schema::hasColumn('gis_records', 'company_id')) {
                $query->whereNull('company_id');
            }

            return $query;
        };

        $query = $buildCorporateGisQuery();

        if (Schema::hasColumn('gis_records', 'workflow_status')) {
            $query->where(function ($q) {
                $q->where('workflow_status', 'Accepted');

                if (Schema::hasColumn('gis_records', 'approval_status')) {
                    $q->orWhere('approval_status', 'Approved')
                        ->orWhere('approval_status', 'Accepted');
                }

                if (Schema::hasColumn('gis_records', 'submission_status')) {
                    $q->orWhere('submission_status', 'Accepted')
                        ->orWhere('submission_status', 'Approved');
                }
            });
        } elseif (Schema::hasColumn('gis_records', 'approval_status')) {
            $query->where(function ($q) {
                $q->where('approval_status', 'Approved')
                    ->orWhere('approval_status', 'Accepted');
            });
        }

        $gis = $query
            ->latest('updated_at')
            ->latest('created_at')
            ->latest('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Fallback, but still Corporate only.
        |--------------------------------------------------------------------------
        | Do NOT fallback to all GIS records. That is what caused Transmittal to
        | accidentally use the latest Company GIS.
        |--------------------------------------------------------------------------
        */
        if (! $gis) {
            $gis = $buildCorporateGisQuery()
                ->latest('updated_at')
                ->latest('created_at')
                ->latest('id')
                ->first();
        }

        if (! $gis) {
            return $fallback;
        }

        $companyName = trim((string) ($gis->corporation_name ?? '')) ?: $fallback['companyName'];
        $secRegNo = trim((string) ($gis->company_reg_no ?? ''));
        $principalAddress = trim((string) ($gis->principal_address ?? ($gis->business_address ?? '')));
        $logoPath = trim((string) ($gis->logo_path ?? ''));

        $logoUrl = $fallback['logoUrl'];
        $logoBase64 = null;

        if ($logoPath !== '') {
            $normalizedLogoPath = preg_replace('#^/?storage/#', '', $logoPath);

            if (Storage::disk('public')->exists($normalizedLogoPath)) {
                $absoluteLogoPath = Storage::disk('public')->path($normalizedLogoPath);
                $mime = function_exists('mime_content_type')
                    ? (mime_content_type($absoluteLogoPath) ?: 'image/png')
                    : 'image/png';

                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absoluteLogoPath));
                $logoUrl = asset('storage/' . $normalizedLogoPath);
            } elseif (file_exists(public_path($normalizedLogoPath))) {
                $absoluteLogoPath = public_path($normalizedLogoPath);
                $mime = function_exists('mime_content_type')
                    ? (mime_content_type($absoluteLogoPath) ?: 'image/png')
                    : 'image/png';

                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absoluteLogoPath));
                $logoUrl = asset($normalizedLogoPath);
            }
        }

        return [
            'companyName' => $companyName,
            'secRegNo' => $secRegNo,
            'principalAddress' => $principalAddress,
            'logoUrl' => $logoUrl,
            'logoBase64' => $logoBase64,
        ];
    }
}