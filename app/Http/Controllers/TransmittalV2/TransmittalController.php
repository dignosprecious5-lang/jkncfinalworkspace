<?php

namespace App\Http\Controllers\TransmittalV2;

use App\Http\Controllers\Controller;
use App\Exceptions\OcrUnavailableException;
use App\Models\Transmittal;
use App\Services\TransmittalWorkflowBoundary;
use App\Services\TransmittalWorkspaceItemSyncService;
use App\Models\TransmittalDocument;
use App\Models\TransmittalReceipt;
use App\Models\TransmittalRecipient;
use App\Models\TransmittalUpload;
use App\Models\User;
use App\Services\TransmittalV2\BrevoTransmittalService;
use App\Jobs\TransmittalV2\DispatchTransmittalEmail;
use App\Services\TransmittalV2\TransmittalDispatchPayloadCipher;
use App\Services\TransmittalV2\TransmittalEmailDispatchService;
use App\Services\TransmittalV2\TransmittalEmailDispatchResender;
use App\Models\TransmittalEmailDispatch;
use App\Services\TransmittalV2\ResumableTransmittalUploadService;
use App\Services\TransmittalV2\TransmittalUploadBudget;
use App\Services\TransmittalV2\TransmittalApprovalService;
use App\Services\TransmittalOcrService;
use App\Http\Middleware\TransmittalV2\EnsureTransmittalV2Enabled;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class TransmittalController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Keep every routed V2 action unavailable unless the explicit feature flag is enabled.
     * Authorization remains enforced independently in the action/policy logic.
     */
    public static function middleware(): array
    {
        return [EnsureTransmittalV2Enabled::class];
    }

    public function index(Request $request): View
    {
        $query = $this->visibleTransmittalQuery($request)
            ->with(['preparer', 'recipients'])
            ->withCount(['recipients', 'documents', 'proofs', 'receipts'])
            ->orderByDesc('transmittal_date')
            ->orderByDesc('id');

        $request->validate([
            'filter' => ['nullable', Rule::in(['all', 'uploaded', 'submitted', 'accepted', 'reverted', 'archived'])],
            'q' => ['nullable', 'string', 'max:200'],
        ]);
        $filter = $request->string('filter', 'all')->toString();
        $filterStatuses = [
            'uploaded' => ['draft'],
            'submitted' => ['pending_approval'],
            'accepted' => ['approved', 'sent', 'acknowledged'],
            'reverted' => ['rejected'],
            'archived' => ['cancelled'],
        ];
        $filterWorkflowStatuses = [
            'uploaded' => ['Uploaded'],
            'submitted' => ['Submitted'],
            'accepted' => ['Accepted'],
            'reverted' => ['Reverted'],
            'archived' => ['Archived'],
        ];

        if (isset($filterStatuses[$filter])) {
            $query->where(function ($q) use ($filterStatuses, $filterWorkflowStatuses, $filter) {
                $q->whereIn('status', $filterStatuses[$filter])
                  ->orWhereIn('workflow_status', $filterWorkflowStatuses[$filter]);
            });
        }

        $query->when($request->filled('q'), function ($builder) use ($request): void {
            $term = '%'.addcslashes((string) $request->string('q'), '%_\\').'%';
            $builder->where(fn ($search) => $search
                ->where('tracking_number', 'like', $term)
                ->orWhere('transmittal_no', 'like', $term)
                ->orWhere('subject', 'like', $term)
                ->orWhere('external_reference', 'like', $term)
                ->orWhere('origin_office', 'like', $term)
                ->orWhere('office_name', 'like', $term)
                ->orWhere('party_name', 'like', $term)
                ->orWhere('related_company', 'like', $term)
                ->orWhereHas('documents', fn ($documents) => $documents->where('original_name', 'like', $term))
                ->orWhereHas('recipients', fn ($recipients) => $recipients
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('organization', 'like', $term)));
        });

        $filterCounts = [
            'all' => $this->visibleTransmittalQuery($request)->count(),
            'uploaded' => $this->visibleTransmittalQuery($request)->where(fn ($q) => $q->whereIn('status', ['draft'])->orWhere('workflow_status', 'Uploaded'))->count(),
            'submitted' => $this->visibleTransmittalQuery($request)->where(fn ($q) => $q->whereIn('status', ['pending_approval'])->orWhere('workflow_status', 'Submitted'))->count(),
            'accepted' => $this->visibleTransmittalQuery($request)->where(fn ($q) => $q->whereIn('status', ['approved', 'sent', 'acknowledged'])->orWhere('workflow_status', 'Accepted'))->count(),
            'reverted' => $this->visibleTransmittalQuery($request)->where(fn ($q) => $q->whereIn('status', ['rejected'])->orWhere('workflow_status', 'Reverted'))->count(),
            'archived' => $this->visibleTransmittalQuery($request)->where(fn ($q) => $q->whereIn('status', ['cancelled'])->orWhere('workflow_status', 'Archived'))->count(),
        ];

        $summary = $this->visibleTransmittalQuery($request)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $editableDraftIds = $this->sessionAuthorizedDraftIds($request);
        $user = $request->user();
        if ($user && $user->id !== null && ! $this->canApproveTransmittals($user)) {
            $ownedEditableIds = TransmittalWorkflowBoundary::v2Query()
                ->where('prepared_by', $user->id)
                ->whereIn('status', ['draft', 'rejected', 'pending_approval'])
                ->pluck('id')
                ->all();
            $editableDraftIds = array_values(array_unique(array_merge($editableDraftIds, $ownedEditableIds)));
        }

        $editTransmittal = null;
        if ($request->filled('edit')) {
            $editTransmittal = TransmittalWorkflowBoundary::v2Query()->findOrFail($request->integer('edit'));
            abort_unless($this->canEditDraft($request, $editTransmittal), 404);
            $editTransmittal->load('recipients', 'documents', 'workspaceItems.document', 'preparer', 'approver');
        }

        return view('transmittals.index', [
            'transmittals' => $query->paginate(10)->withQueryString(),
            'filter' => $filter,
            'search' => $request->string('q')->toString(),
            'summary' => $summary,
            'filterCounts' => $filterCounts,
            'editableDraftIds' => $editableDraftIds,
            'editTransmittal' => $editTransmittal,
        ] + $this->signatoryDirectory());
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('transmittals.index', ['add' => 1]);
    }

    public function smartUpload(Request $request): View
    {
        // Smart Upload is available to guests, so keep its document picker
        // limited to transmittals visible to this user or session.
        $visibleTransmittals = $this->visibleTransmittalQuery($request)->select('transmittals.id');
        $recentDocuments = TransmittalWorkflowBoundary::v2Children(TransmittalDocument::class)
            ->whereIn('transmittal_id', $visibleTransmittals)
            ->with('transmittal:id,tracking_number,subject')
            ->latest('id')
            ->limit(25)
            ->get(['id', 'transmittal_id', 'original_name', 'category', 'size_bytes', 'created_at']);

        return view('transmittals.smart-upload', [
            'recentDocuments' => $recentDocuments,
        ]);
    }

    public function createFromSmartUpload(Request $request, ResumableTransmittalUploadService $uploads): RedirectResponse
    {
        if (array_key_exists('workflow_version', $request->all())) {
            throw ValidationException::withMessages([
                'workflow_version' => 'Workflow version is assigned by the server.',
            ]);
        }
        $validated = $request->validate([
            'documents' => ['nullable', 'array', 'max:50'],
            'documents.*' => ['required', 'file', 'max:'.(TransmittalUploadBudget::MAX_FILE_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png,tif,tiff,doc,docx,txt'],
            'categories' => ['nullable', 'array', 'max:50'],
            'categories.*' => ['required', Rule::in(['document', 'receipt', 'proof', 'attachment'])],
            'ocr_tokens' => ['nullable', 'array', 'max:50'],
            'ocr_tokens.*' => ['nullable', 'string', 'max:100000'],
            'upload_ids' => ['nullable', 'array', 'max:50'],
            'upload_ids.*' => ['required', 'uuid', 'distinct'],
            'upload_categories' => ['nullable', 'array', 'max:50'],
            'upload_categories.*' => ['required', Rule::in(['document', 'receipt', 'proof', 'attachment'])],
            'upload_ocr_tokens' => ['nullable', 'array', 'max:50'],
            'upload_ocr_tokens.*' => ['nullable', 'string', 'max:100000'],
            'subject' => ['required', 'string', 'max:200'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'related_company' => ['nullable', 'string', 'max:200'],
            'recipient_address' => ['nullable', 'string', 'max:500'],
            'recipient_name' => ['nullable', 'string', 'max:160'],
            'recipient_email' => ['nullable', 'email', 'max:254'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'transmittal_date' => ['nullable', 'date'],
            'direction' => ['nullable', 'string', Rule::in(['send', 'receive'])],
            'items' => ['sometimes', 'array', 'max:50'],
            'items.*.particular' => ['nullable', 'string', 'max:255'],
            'items.*.unique_id' => ['nullable', 'string', 'max:120'],
            'items.*.quantity' => ['nullable', 'string', 'max:40'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.remarks' => ['nullable', 'string', 'max:1000'],
            'items.*.document_index' => ['nullable', 'integer', 'min:0'],
        ]);

        $directFiles = $request->file('documents', []);
        $uploadIds = $validated['upload_ids'] ?? [];
        if ($directFiles === [] && $uploadIds === []) {
            throw ValidationException::withMessages(['documents' => 'Choose at least one file to upload.']);
        }
        if (count($directFiles) + count($uploadIds) > config('transmittal_v2.upload.max_files', 50)) {
            throw ValidationException::withMessages(['documents' => 'A maximum of '.config('transmittal_v2.upload.max_files', 50).' files can be attached.']);
        }

        $fileEntries = [];
        foreach ($directFiles as $index => $file) {
            $fileEntries[] = [
                'file' => $file,
                'category' => $validated['categories'][$index] ?? 'document',
                'ocr_token' => $validated['ocr_tokens'][$index] ?? null,
                'upload' => null,
            ];
        }
        $uploadOwnerHash = hash('sha256', $request->session()->getId());
        foreach ($uploadIds as $index => $uploadId) {
            $uploadToken = $request->session()->get("transmittal_upload_tokens.{$uploadId}");
            abort_unless(is_string($uploadToken), 404);
            $upload = $uploads->ownedUpload(
                $uploadOwnerHash,
                $uploadId,
                $uploadToken
            );
            abort_unless($upload->status === 'completed', 409, 'A resumable upload is not complete.');
            $file = $uploads->temporaryFile($upload);
            validator(['document' => $file], [
                'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,tif,tiff,doc,docx,txt'],
            ])->validate();
            $fileEntries[] = [
                'file' => $file,
                'category' => $validated['upload_categories'][$index] ?? 'document',
                'ocr_token' => $validated['upload_ocr_tokens'][$index] ?? null,
                'upload' => $upload,
            ];
        }

        // Validate the complete submission before claiming uploads or writing records/files.
        (new TransmittalUploadBudget())->validate($request->allFiles(), array_column($fileEntries, 'file'));

        $user = $request->user();
        $token = Str::random(64);
        $transmittal = DB::transaction(function () use ($validated, $user, $token, $fileEntries, $uploadIds, $uploadOwnerHash, $request): Transmittal {
            if ($uploadIds !== []) {
                $claimed = TransmittalUpload::query()
                    ->whereIn('upload_id', $uploadIds)
                    ->where('owner_session_hash', $uploadOwnerHash)
                    ->where('status', 'completed')
                    ->where('expires_at', '>', now())
                    ->update([
                        'status' => 'consumed',
                        'upload_token_hash' => hash('sha256', Str::random(64)),
                        'expires_at' => now()->addHours((int) config('transmittal_v2.upload.expiry_hours')),
                    ]);
                abort_unless($claimed === count($uploadIds), 409, 'One or more uploads have already been imported or have expired.');
            }

            $direction = $validated['direction'] ?? 'send';
            $transmittal = Transmittal::createForWorkflowVersion('v2', [
                'tracking_number' => 'PENDING-'.Str::uuid(),
                'subject' => $validated['subject'],
                'external_reference' => $validated['external_reference'] ?? null,
                'related_company' => $validated['related_company'] ?? null,
                'party_name' => $validated['recipient_name'] ?? null,
                'recipient_address' => $validated['recipient_address'] ?? null,
                'address' => $validated['recipient_address'] ?? null,
                'recipient_email' => $validated['recipient_email'] ?? null,
                'purpose' => $validated['purpose'] ?? 'Review the uploaded documents and complete the transmittal details.',
                'direction' => $direction,
                'mode' => strtoupper($direction),
                'transmittal_date' => $validated['transmittal_date'] ?? now()->toDateString(),
                'origin_office' => config('transmittal_v2.office_name', 'JK&C INC.'),
                'office_name' => config('transmittal_v2.office_name', 'JK&C INC.'),
                'delivery_method' => 'electronic',
                'delivery_type' => 'by_person',
                'delivery_actions' => ['email'],
                'action_email' => true,
                'prepared_by' => $user?->id,
                'prepared_by_name' => $user?->name ?? 'Public submitter',
                'prepared_at' => now(),
                'status' => 'draft',
                'workflow_status' => 'Uploaded',
                'approval_status' => 'Draft',
            ]);
            $trackingNumber = 'TRN-'.str_pad((string) $transmittal->id, 5, '0', STR_PAD_LEFT);
            $transmittal->forceFill([
                'tracking_number' => $trackingNumber,
                'transmittal_no' => $trackingNumber,
                'edit_token_hash' => hash('sha256', $token),
            ])->save();

            if (! empty($validated['recipient_email']) || ! empty($validated['recipient_name'])) {
                $transmittal->recipients()->create([
                    'name' => $validated['recipient_name'] ?? null,
                    'email' => $validated['recipient_email'] ?? null,
                    'organization' => $validated['related_company'] ?? null,
                ]);
            }

            $storedDocuments = [];
            foreach ($fileEntries as $index => $entry) {
                $file = $entry['file'];
                $document = $this->storeDocument(
                    $transmittal,
                    $user,
                    $file,
                    $entry['category'],
                    $this->verifiedOcrData($entry['ocr_token'], $file)
                );
                $storedDocuments[$index] = $document;
            }

            $itemPayload = $validated;
            if (! array_key_exists('items', $validated)) {
                // Only a new Smart Upload draft gets explicit default workspace rows.
                $itemPayload['items'] = [];
                foreach ($storedDocuments as $index => $document) {
                    $fields = $document->extracted_data ?? [];
                    $itemPayload['items'][] = [
                        'document_index' => $index,
                        'line_number' => $index + 1,
                        'particular' => pathinfo($document->original_name, PATHINFO_FILENAME),
                        'unique_id' => $fields['unique_id'] ?? $fields['reference'] ?? null,
                        'quantity' => '1',
                        'description' => $fields['description'] ?? $fields['subject'] ?? null,
                        'remarks' => 'Auto-filled from Smart Upload; review before submission.',
                    ];
                }
            }
            $this->syncItems($transmittal, $request, $user, $itemPayload, $storedDocuments);

            $transmittal->histories()->create([
                'actor_id' => $user?->id,
                'action' => 'smart_upload_draft_created',
                'description' => 'Draft created from Smart Upload with permanent tracking number '.$transmittal->tracking_number.'.',
                'created_at' => now(),
            ]);

            return $transmittal;
        });

        if ($uploadIds !== []) {
            $tokens = $request->session()->get('transmittal_upload_tokens', []);
            foreach ($uploadIds as $uploadId) {
                unset($tokens[$uploadId]);
            }
            $request->session()->put('transmittal_upload_tokens', $tokens);
        }

        $tokens = $request->session()->get('transmittal_draft_tokens', []);
        $tokens[$transmittal->id] = $token;
        $request->session()->put('transmittal_draft_tokens', $tokens);

        return redirect()->route('transmittals.workspace', $transmittal)
            ->with('status', 'Files uploaded. Complete the transmittal workspace and save or submit it.');
    }

public function workspace(Request $request, Transmittal $transmittal): View
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canManageLifecycle($request, $transmittal), 404);

        if ($transmittal->status === 'draft') {
            abort_unless($this->canEditDraft($request, $transmittal), 404);
        }

        $transmittal->load([
            'recipients',
            'documents',
            'workspaceItems.document',
            'receipts.recipient',
            'proofs.recipient',
            'proofs.sender',
            'proofs.document',
            'histories.actor',
        ]);

        return view('transmittals.workspace', [
            'transmittal' => $transmittal,
            'canViewPrivateFiles' => $this->canViewPrivateFiles($request, $transmittal),
            'canViewAttachments' => $this->canViewPrivateAttachments($request, $transmittal),
            'canManageLifecycle' => $this->canManageLifecycle($request, $transmittal),
            'canUploadProof' => true,
            'contactOptions' => $this->transmittalContactOptions(),
            'employeeOptions' => $this->transmittalEmployeeOptions(),
            'companyOptions' => $this->transmittalCompanyOptions(),
        ] + $this->signatoryDirectory());
    }

    public function edit(Request $request, Transmittal $transmittal): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canEditDraft($request, $transmittal), 404);

        return redirect()->route('transmittals.index', ['edit' => $transmittal->id]);
    }

    public function analyze(Request $request, TransmittalOcrService $ocr): JsonResponse
    {
        (new TransmittalUploadBudget())->validate($request->allFiles());

        $data = $request->validate([
            'documents' => ['required', 'array', 'min:1', 'max:'.config('transmittal_v2.upload.max_files', 50)],
            'documents.*' => ['required', 'file', 'max:'.(TransmittalUploadBudget::MAX_FILE_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png,tif,tiff'],
        ]);

        try {
            return response()->json([
                'documents' => collect($data['documents'])->map(function (UploadedFile $file) use ($ocr, $request): array {
                    $analysis = $ocr->analyze($file);
                    $path = $file->getRealPath();
                    abort_unless(is_string($path), 422, 'The uploaded file could not be verified.');
                    $sha256 = hash_file('sha256', $path);

                    $canMatchExistingRecords = $this->canApproveTransmittals($request->user());
                    $existingDoc = $canMatchExistingRecords
                        ? TransmittalWorkflowBoundary::v2Children(TransmittalDocument::class)
                            ->with('transmittal:id,tracking_number,subject')
                            ->where('sha256', $sha256)
                            ->orWhere('original_name', $file->getClientOriginalName())
                            ->first()
                        : null;

                    $refMatch = $canMatchExistingRecords && ! empty($analysis['fields']['reference'])
                        ? TransmittalWorkflowBoundary::v2Query()->where('external_reference', $analysis['fields']['reference'])->first(['id', 'tracking_number', 'subject'])
                        : null;

                    $matchedRecord = null;
                    if ($existingDoc) {
                        $matchedRecord = [
                            'type' => $existingDoc->sha256 === $sha256 ? 'duplicate_file' : 'similar_name',
                            'label' => $existingDoc->sha256 === $sha256 ? 'Duplicate File' : 'Matched Document',
                            'tracking_number' => $existingDoc->transmittal?->tracking_number,
                            'subject' => $existingDoc->transmittal?->subject,
                            'document_name' => $existingDoc->original_name,
                        ];
                    } elseif ($refMatch) {
                        $matchedRecord = [
                            'type' => 'reference_match',
                            'label' => 'Matched Reference',
                            'tracking_number' => $refMatch->tracking_number,
                            'subject' => $refMatch->subject,
                        ];
                    }

                    return [
                        'name' => $file->getClientOriginalName(),
                        'category' => $analysis['category'],
                        'fields' => $analysis['fields'],
                        'field_metadata' => $analysis['field_metadata'],
                        'matched_record' => $matchedRecord,
                        'sha256' => $sha256,
                        'text_preview' => Str::limit($analysis['text'], 500),
                        'analysis_token' => Crypt::encryptString(json_encode([
                            'name' => basename($file->getClientOriginalName()),
                            'sha256' => $sha256,
                            'category' => $analysis['category'],
                            'text' => $analysis['text'],
                            'fields' => $analysis['fields'],
                            'field_metadata' => $analysis['field_metadata'],
                        ], JSON_THROW_ON_ERROR)),
                    ];
                }),
            ]);
        } catch (OcrUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $this->validateTransmittal($request);
        $isDraft = $validated['intent'] === 'draft';
        $user = $request->user();
        $editToken = Str::random(64);

        try {
            $transmittal = DB::transaction(function () use ($validated, $request, $user, $editToken, $isDraft): Transmittal {
                $deliveryActions = array_values(array_filter((array) ($validated['delivery_actions'] ?? [])));
                if ($deliveryActions === []) {
                    if ($request->boolean('action_delivery')) $deliveryActions[] = 'delivery';
                    if ($request->boolean('action_pick_up')) $deliveryActions[] = 'pick_up';
                    if ($request->boolean('action_drop_off')) $deliveryActions[] = 'drop_off';
                    if ($request->boolean('action_email')) $deliveryActions[] = 'email';
                }

                $deliveryType = $validated['delivery_type'] ?? 'by_person';
                $registeredMailProvider = filled($validated['registered_mail_provider_other'] ?? null) && ($validated['registered_mail_provider'] ?? '') === 'Others'
                    ? $validated['registered_mail_provider_other']
                    : ($validated['registered_mail_provider'] ?? null);
                $electronicMethod = $validated['electronic_method'] ?? (strtolower((string) $deliveryType) === 'electronic' ? 'Email' : null);

                $primaryRecipient = $validated['recipients'][0] ?? [];
                $partyName = filled($validated['party_name'] ?? null)
                    ? trim($validated['party_name'])
                    : (filled($primaryRecipient['name'] ?? null) ? trim($primaryRecipient['name']) : null);
                $recipientEmail = filled($validated['recipient_email'] ?? null)
                    ? trim($validated['recipient_email'])
                    : (filled($primaryRecipient['email'] ?? null) ? trim($primaryRecipient['email']) : null);
                $recipientAddress = filled($validated['recipient_address'] ?? null)
                    ? $validated['recipient_address']
                    : ($validated['address'] ?? null);
                $officeName = filled($validated['office_name'] ?? null)
                    ? $validated['office_name']
                    : $validated['origin_office'];

                $operationsManager = $this->resolveSignatoryName($validated, 'operations_manager', 'operations_manager_employee')
                    ?? (filled($validated['approved_position'] ?? null) ? trim($validated['approved_position']) : null);
                $processingCompany = $this->resolveSelectedName($validated, 'processing_company', 'processing_company_selection')
                    ?? (filled($validated['receiver_affiliation'] ?? null) ? trim($validated['receiver_affiliation']) : null);
                $preparedByName = $user?->name
                    ?? (filled($validated['prepared_by_name'] ?? null) ? trim($validated['prepared_by_name']) : 'Public submitter');
                $preparedAt = filled($validated['prepared_at'] ?? null)
                    ? Carbon::parse($validated['prepared_at'])
                    : now();
                $receivedAt = filled($validated['received_at'] ?? null)
                    ? Carbon::parse($validated['received_at'])
                    : null;

                $transmittal = Transmittal::createForWorkflowVersion('v2', [
                    'tracking_number' => 'PENDING-'.Str::uuid(),
                    'external_reference' => $validated['external_reference'] ?? null,
                    'subject' => $validated['subject'],
                    'purpose' => $validated['purpose'],
                    'direction' => $validated['direction'],
                    'mode' => strtoupper($validated['direction']),
                    'transmittal_date' => $validated['transmittal_date'],
                    'party_name' => $partyName,
                    'origin_office' => $validated['origin_office'],
                    'office_name' => $officeName,
                    'recipient_address' => $recipientAddress,
                    'address' => $recipientAddress,
                    'related_company' => $validated['related_company'] ?? ($primaryRecipient['organization'] ?? null),
                    'delivery_method' => $validated['delivery_method'] ?? 'electronic',
                    'delivery_type' => $deliveryType,
                    'by_person_who' => $validated['by_person_who'] ?? null,
                    'registered_mail_provider' => $registeredMailProvider,
                    'electronic_method' => $electronicMethod,
                    'recipient_email' => $recipientEmail,
                    'delivery_actions' => $deliveryActions,
                    'action_delivery' => in_array('delivery', $deliveryActions, true) || $request->boolean('action_delivery'),
                    'action_pick_up' => in_array('pick_up', $deliveryActions, true) || $request->boolean('action_pick_up'),
                    'action_drop_off' => in_array('drop_off', $deliveryActions, true) || $request->boolean('action_drop_off'),
                    'action_email' => in_array('email', $deliveryActions, true) || $request->boolean('action_email'),
                    'prepared_by_name' => $preparedByName,
                    'prepared_at' => $preparedAt,
                    'approved_by_name' => $this->resolveSignatoryName($validated, 'approved_by_name', 'approved_by_employee'),
                    'operations_manager' => $operationsManager,
                    'approved_position' => $operationsManager,
                    'document_custodian' => filled($validated['document_custodian'] ?? null) ? trim($validated['document_custodian']) : null,
                    'delivered_by' => $this->resolveSelectedName($validated, 'delivered_by', 'delivered_by_employee'),
                    'received_by' => $this->resolveSelectedName($validated, 'received_by', 'received_by_contact'),
                    'processing_company' => $processingCompany,
                    'receiver_affiliation' => $processingCompany,
                    'received_at' => $receivedAt,
                    'confidentiality' => $validated['confidentiality'],
                    'status' => $isDraft ? 'draft' : 'pending_approval',
                    'workflow_status' => $isDraft ? 'Uploaded' : 'Submitted',
                    'approval_status' => $isDraft ? 'Draft' : 'Pending',
                    'submitted_by' => $isDraft ? null : $user?->id,
                    'prepared_by' => $user?->id,
                ]);
                $trackingNumber = 'TRN-'.str_pad((string) $transmittal->id, 5, '0', STR_PAD_LEFT);
                $transmittal->forceFill([
                    'tracking_number' => $trackingNumber,
                    'transmittal_no' => $trackingNumber,
                    'edit_token_hash' => hash('sha256', $editToken),
                ])->save();

                $this->syncRecipients($transmittal, $validated['recipients'] ?? []);
                $uploadedDocuments = $this->storeDocuments($transmittal, $request, $user, $validated);
                $this->syncItems($transmittal, $request, $user, $validated, $uploadedDocuments);

                $transmittal->histories()->create([
                    'actor_id' => $user?->id,
                    'action' => $isDraft ? 'draft_saved' : 'submitted',
                    'description' => $isDraft
                        ? 'Public draft saved and linked to the submitter browser session.'
                        : 'Public submission received and added to the administrator review queue.',
                    'created_at' => now(),
                ]);

                return $transmittal;
            });
        } catch (\Throwable $e) {
            \Log::error('Transmittal save failed: ' . $e->getMessage(), ['exception' => $e]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Kindly fill up the required form fields properly before proceeding.',
                    'errors' => ['form' => ['Kindly fill up the required form fields properly before proceeding.']],
                ], 422);
            }
            return back()->withInput()->withErrors(['form' => 'Kindly fill up the required form fields properly before proceeding.']);
        }

        $tokens = $request->session()->get('transmittal_draft_tokens', []);
        $tokens[$transmittal->id] = $editToken;
        $request->session()->put('transmittal_draft_tokens', $tokens);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $isDraft
                    ? "Draft {$transmittal->tracking_number} saved successfully."
                    : 'Transmittal submitted for review successfully.',
                'tracking_number' => $transmittal->tracking_number,
                'id' => $transmittal->id,
                'is_draft' => $isDraft,
                'redirect_url' => route($this->indexRouteFor($request), ['filter' => $isDraft ? 'uploaded' : 'submitted']),
            ]);
        }

        if (! $isDraft) {
            return redirect()->route($this->indexRouteFor($request), ['filter' => 'submitted'])
                ->with('status', 'Transmittal submitted for administrator review.');
        }

        return redirect()->route($this->indexRouteFor($request), [
            'filter' => 'uploaded',
        ])->with('status', "Draft {$transmittal->tracking_number} saved. You can continue editing it from this browser.");
    }

    public function update(Request $request, Transmittal $transmittal): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canEditDraft($request, $transmittal), 404);
        $validated = $this->validateTransmittal($request);
        $isDraft = $validated['intent'] === 'draft';
        $user = $request->user();

        try {
            $isReverting = DB::transaction(function () use ($transmittal, $validated, $request, $user, $isDraft): bool {
                $transmittal = TransmittalWorkflowBoundary::v2Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail();
                abort_unless($this->canManageLifecycle($request, $transmittal), 404);
                $record = $transmittal;
                abort_unless($this->canEditDraft($request, $record), 404);
                app(TransmittalWorkspaceItemSyncService::class)->assertEditable($record);
                $isReverting = $record->status === 'rejected' && ! $isDraft;

                $deliveryActions = array_key_exists('delivery_actions', $validated)
                    ? array_values(array_filter((array) $validated['delivery_actions']))
                    : ($record->delivery_actions ?? []);
                if ($deliveryActions === [] && ($request->has('action_delivery') || $request->has('action_pick_up') || $request->has('action_drop_off') || $request->has('action_email'))) {
                    if ($request->boolean('action_delivery')) $deliveryActions[] = 'delivery';
                    if ($request->boolean('action_pick_up')) $deliveryActions[] = 'pick_up';
                    if ($request->boolean('action_drop_off')) $deliveryActions[] = 'drop_off';
                    if ($request->boolean('action_email')) $deliveryActions[] = 'email';
                }

                $deliveryType = $validated['delivery_type'] ?? $record->delivery_type ?? 'by_person';
                $registeredMailProvider = filled($validated['registered_mail_provider_other'] ?? null) && ($validated['registered_mail_provider'] ?? '') === 'Others'
                    ? $validated['registered_mail_provider_other']
                    : ($validated['registered_mail_provider'] ?? $record->registered_mail_provider);
                $electronicMethod = $validated['electronic_method'] ?? (strtolower((string) $deliveryType) === 'electronic' ? 'Email' : $record->electronic_method);

                $primaryRecipient = $validated['recipients'][0] ?? [];
                $partyName = filled($validated['party_name'] ?? null)
                    ? trim($validated['party_name'])
                    : (filled($primaryRecipient['name'] ?? null) ? trim($primaryRecipient['name']) : $record->party_name);
                $recipientEmail = filled($validated['recipient_email'] ?? null)
                    ? trim($validated['recipient_email'])
                    : (filled($primaryRecipient['email'] ?? null) ? trim($primaryRecipient['email']) : $record->recipient_email);
                $recipientAddress = filled($validated['recipient_address'] ?? null)
                    ? $validated['recipient_address']
                    : (filled($validated['address'] ?? null) ? $validated['address'] : $record->recipient_address);
                $officeName = filled($validated['office_name'] ?? null)
                    ? $validated['office_name']
                    : $validated['origin_office'];

                $operationsManager = $this->resolveSignatoryName($validated, 'operations_manager', 'operations_manager_employee')
                    ?? (filled($validated['approved_position'] ?? null) ? trim($validated['approved_position']) : $record->operations_manager);
                $processingCompany = $this->resolveSelectedName($validated, 'processing_company', 'processing_company_selection')
                    ?? (filled($validated['receiver_affiliation'] ?? null) ? trim($validated['receiver_affiliation']) : $record->processing_company);
                $preparedByName = $user?->name
                    ?? (filled($validated['prepared_by_name'] ?? null) ? trim($validated['prepared_by_name']) : ($record->prepared_by_name ?? 'Public submitter'));
                $preparedAt = filled($validated['prepared_at'] ?? null)
                    ? Carbon::parse($validated['prepared_at'])
                    : ($record->prepared_at ?? now());
                $receivedAt = filled($validated['received_at'] ?? null)
                    ? Carbon::parse($validated['received_at'])
                    : $record->received_at;

                $record->forceFill([
                    'transmittal_no' => $record->transmittal_no ?? $record->tracking_number,
                    'external_reference' => filled($validated['external_reference'] ?? null)
                        ? $validated['external_reference']
                        : $record->external_reference,
                    'subject' => $validated['subject'],
                    'purpose' => $validated['purpose'],
                    'direction' => $validated['direction'],
                    'mode' => strtoupper($validated['direction']),
                    'transmittal_date' => $validated['transmittal_date'],
                    'party_name' => $partyName,
                    'origin_office' => $validated['origin_office'],
                    'office_name' => $officeName,
                    'recipient_address' => $recipientAddress,
                    'address' => $recipientAddress,
                    'related_company' => filled($validated['related_company'] ?? null)
                        ? $validated['related_company']
                        : $record->related_company,
                    'delivery_method' => $validated['delivery_method'] ?? 'electronic',
                    'delivery_type' => $deliveryType,
                    'by_person_who' => array_key_exists('by_person_who', $validated) ? $validated['by_person_who'] : $record->by_person_who,
                    'registered_mail_provider' => $registeredMailProvider,
                    'electronic_method' => $electronicMethod,
                    'recipient_email' => $recipientEmail,
                    'delivery_actions' => $deliveryActions,
                    'action_delivery' => in_array('delivery', $deliveryActions, true) || $request->boolean('action_delivery'),
                    'action_pick_up' => in_array('pick_up', $deliveryActions, true) || $request->boolean('action_pick_up'),
                    'action_drop_off' => in_array('drop_off', $deliveryActions, true) || $request->boolean('action_drop_off'),
                    'action_email' => in_array('email', $deliveryActions, true) || $request->boolean('action_email'),
                    'prepared_by_name' => $preparedByName,
                    'prepared_at' => $preparedAt,
                    'approved_by_name' => $this->resolveSignatoryName($validated, 'approved_by_name', 'approved_by_employee')
                        ?? $record->approved_by_name,
                    'operations_manager' => $operationsManager,
                    'approved_position' => $operationsManager ?? $record->approved_position,
                    'document_custodian' => filled($validated['document_custodian'] ?? null)
                        ? trim($validated['document_custodian'])
                        : $record->document_custodian,
                    'delivered_by' => $this->resolveSelectedName($validated, 'delivered_by', 'delivered_by_employee')
                        ?? $record->delivered_by,
                    'received_by' => $this->resolveSelectedName($validated, 'received_by', 'received_by_contact')
                        ?? $record->received_by,
                    'processing_company' => $processingCompany,
                    'receiver_affiliation' => $processingCompany ?? $record->receiver_affiliation,
                    'received_at' => $receivedAt,
                    'confidentiality' => $validated['confidentiality'],
                    'status' => $isDraft ? ($record->status === 'rejected' ? 'rejected' : 'draft') : 'pending_approval',
                    'workflow_status' => $isDraft ? ($isReverting ? 'Reverted' : ($record->workflow_status ?? 'Uploaded')) : 'Submitted',
                    'approval_status' => $isDraft ? ($record->approval_status ?? 'Draft') : 'Pending',
                    'submitted_by' => $isDraft ? $record->submitted_by : ($user?->id ?? $record->submitted_by),
                    'edit_token_hash' => $record->edit_token_hash,
                ])->save();

                $recipients = $validated['recipients'] ?? [];
                $hasRecipientInfo = collect($recipients)->contains(
                    fn ($recipient): bool => is_array($recipient) && (filled($recipient['email'] ?? null) || filled($recipient['name'] ?? null) || filled($recipient['organization'] ?? null))
                );
                if ($hasRecipientInfo || ! $isDraft || $record->recipients()->doesntExist()) {
                    $this->syncRecipients($record, $recipients);
                }
                $uploadedDocuments = $this->storeDocuments($record, $request, $user, $validated);
                if (array_key_exists('items', $validated)) {
                    $this->syncItems($record, $request, $user, $validated, $uploadedDocuments);
                }
                $record->histories()->create([
                    'actor_id' => $user?->id,
                    'action' => $isReverting && ! $isDraft ? 'reverted_for_revision' : ($isDraft ? 'draft_updated' : 'submitted'),
                    'description' => $isReverting && ! $isDraft
                        ? 'Reverted transmittal revised and resubmitted under the same tracking number.'
                        : ($isDraft
                            ? 'Public draft details updated.'
                            : 'Saved draft submitted to the administrator review queue.'),
                    'created_at' => now(),
                ]);

                return $isReverting;
            });
        } catch (\Throwable $e) {
            \Log::error('Transmittal update failed: ' . $e->getMessage(), ['exception' => $e]);
            return back()->withInput()->withErrors(['form' => 'Kindly fill up the required form fields properly before proceeding.']);
        }

        if (! $isDraft) {
            return redirect()->route($this->indexRouteFor($request), ['filter' => 'submitted'])
                ->with('status', 'Transmittal submitted for administrator review.');
        }

        return redirect()->route($this->indexRouteFor($request), [
            'filter' => $isReverting ? 'reverted' : 'uploaded',
        ])->with('status', "Draft {$transmittal->tracking_number} updated.");
    }

    /**
     * '/transmittal' lives behind the auth middleware; public (session-owned) draft
     * senders must land on the public '/transmittals' list instead of the login page.
     */
    private function indexRouteFor(Request $request): string
    {
        return 'transmittals.index';
    }

    private function validateTransmittal(Request $request): array
    {
        if (array_key_exists('workflow_version', $request->all())) {
            throw ValidationException::withMessages([
                'workflow_version' => 'Workflow version is assigned by the server.',
            ]);
        }
        // Includes category arrays and every nested item attachment before store/update transactions.
        (new TransmittalUploadBudget())->validate($request->allFiles());

        if ($request->has('items') && is_string($request->input('items'))) {
            $decoded = json_decode($request->input('items'), true);
            if (is_array($decoded)) {
                $request->merge(['items' => $decoded]);
            }
        }

        return $request->validate([
            'intent' => ['required', Rule::in(['draft', 'submit'])],
            'subject' => ['required', 'string', 'max:200'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'purpose' => ['required', 'string', 'max:5000'],
            'direction' => ['required', Rule::in(['send', 'receive'])],
            'transmittal_date' => ['required', 'date'],
            'party_name' => ['nullable', 'string', 'max:200'],
            'origin_office' => ['required', 'string', 'max:160'],
            'office_name' => ['nullable', 'string', 'max:160'],
            'recipient_address' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:2000'],
            'related_company' => ['nullable', 'string', 'max:200'],
            'delivery_method' => ['nullable', 'string', 'max:60'],
            'delivery_type' => ['nullable', 'string', 'max:60'],
            'by_person_who' => ['nullable', 'string', 'max:160'],
            'registered_mail_provider' => ['nullable', 'string', 'max:160'],
            'registered_mail_provider_other' => ['nullable', 'string', 'max:160'],
            'electronic_method' => ['nullable', 'string', 'max:160'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'delivery_actions' => ['nullable', 'array', 'max:4'],
            'delivery_actions.*' => ['nullable', 'string', 'max:40'],
            'action_delivery' => ['nullable'],
            'action_pick_up' => ['nullable'],
            'action_drop_off' => ['nullable'],
            'action_email' => ['nullable'],
            'prepared_by_name' => ['nullable', 'string', 'max:160'],
            'prepared_at' => ['nullable', 'date'],
            'operations_manager' => ['nullable', 'string', 'max:160'],
            'operations_manager_employee' => ['nullable', 'integer', 'exists:users,id'],
            'approved_position' => ['nullable', 'string', 'max:160'],
            'approved_by_name' => ['nullable', 'string', 'max:160'],
            'approved_by_employee' => ['nullable', 'integer', 'exists:users,id'],
            'document_custodian' => ['nullable', 'string', 'max:160'],
            'delivered_by_employee' => ['nullable', 'string', 'max:160'],
            'received_by_contact' => ['nullable', 'string', 'max:160'],
            'processing_company_selection' => ['nullable', 'string', 'max:160'],
            'delivered_by' => ['nullable', 'string', 'max:160'],
            'received_by' => ['nullable', 'string', 'max:160'],
            'receiver_affiliation' => ['nullable', 'string', 'max:160'],
            'processing_company' => ['nullable', 'string', 'max:160'],
            'received_at' => ['nullable', 'date'],
            'confidentiality' => ['required', Rule::in(['standard', 'confidential', 'restricted'])],
            'recipients' => ['required_if:intent,submit', 'array', 'min:1', 'max:20'],
            'recipients.*.name' => ['nullable', 'string', 'max:120'],
            'recipients.*.email' => [
                Rule::requiredIf(fn () => $request->input('intent') === 'submit' && ($request->input('delivery_type') === 'electronic' || in_array('email', (array) $request->input('delivery_actions', []), true))),
                'nullable',
                'email',
                'max:255',
                'distinct:ignore_case'
            ],
            'recipients.*.organization' => ['nullable', 'string', 'max:160'],
            'documents' => ['nullable', 'array', 'max:'.config('transmittal_v2.upload.max_files', 50)],
            'documents.*' => ['file', 'max:'.(TransmittalUploadBudget::MAX_FILE_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png,tif,tiff,doc,docx'],
            'supporting_attachments' => ['nullable', 'array', 'max:'.config('transmittal_v2.upload.max_files', 50)],
            'supporting_attachments.*' => ['file', 'max:'.(TransmittalUploadBudget::MAX_FILE_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png,tif,tiff,doc,docx'],
            'attachments' => ['nullable', 'array', 'max:'.config('transmittal_v2.upload.max_files', 50)],
            'attachments.*' => ['file', 'max:'.(TransmittalUploadBudget::MAX_FILE_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png,tif,tiff,doc,docx'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['required', Rule::in(['document', 'receipt', 'proof', 'attachment'])],
            'ocr_tokens' => ['nullable', 'array', 'max:'.config('transmittal_v2.upload.max_files', 50)],
            'ocr_tokens.*' => ['nullable', 'string', 'max:100000'],
            'items' => ['sometimes', 'array', 'max:50'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.no' => ['nullable', 'integer', 'min:1'],
            'items.*.line_number' => ['nullable', 'integer', 'min:1'],
            'items.*.document_id' => ['nullable', 'integer'],
            'items.*.document_index' => ['nullable', 'integer', 'min:0'],
            'items.*.particular' => ['nullable', 'string', 'max:200'],
            'items.*.unique_id' => ['nullable', 'string', 'max:120'],
            'items.*.quantity' => ['nullable', 'string', 'max:40'],
            'items.*.qty' => ['nullable', 'string', 'max:40'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.remarks' => ['nullable', 'string', 'max:2000'],
            'items.*.attachment' => ['nullable', 'file', 'max:'.(TransmittalUploadBudget::MAX_FILE_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png,tif,tiff,doc,docx'],
            'items.*.attachments' => ['nullable', 'array'],
            'items.*.attachments.*' => ['nullable', 'file', 'max:'.(TransmittalUploadBudget::MAX_FILE_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png,tif,tiff,doc,docx'],
        ], [
            'intent.required' => 'The intent field is required. Choose Save Draft or Submit for review.',
            'subject.required' => 'Kindly fill up the required subject field.',
            'purpose.required' => 'Kindly fill up the transmittal purpose.',
            'direction.required' => 'Kindly select the transmittal mode (Send or Receive).',
            'transmittal_date.required' => 'Kindly select the transmittal date.',
            'origin_office.required' => 'Kindly specify the office name.',
            'recipients.required_if' => 'Kindly specify at least one recipient before submitting for review.',
            'recipients.*.email.required' => 'Kindly fill up the required recipient email address.',
            'recipients.*.email.email' => 'Kindly provide a valid recipient email address.',
            'recipients.*.name.max' => 'Recipient name cannot exceed 120 characters.',
            'items.array' => 'Kindly fill up the item details properly.',
            'items.max' => 'The items list cannot exceed 50 items.',
        ]);
    }

    private function resolveSignatoryName(array $validated, string $manualField, string $employeeField): ?string
    {
        if (filled($validated[$manualField] ?? null)) {
            return trim($validated[$manualField]);
        }

        return isset($validated[$employeeField])
            ? User::query()->find($validated[$employeeField])?->name
            : null;
    }

    private function resolveSelectedName(array $validated, string $manualField, string $selectionField): ?string
    {
        $name = filled($validated[$manualField] ?? null)
            ? $validated[$manualField]
            : ($validated[$selectionField] ?? null);

        return filled($name) ? trim($name) : null;
    }

    private function signatoryDirectory(): array
    {
        $employees = User::query()->orderBy('id')->get(['id', 'name']);

        $contacts = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->whereRaw('LENGTH(name) <= 60')
            ->where('name', 'not like', '%confidential%')
            ->where('name', 'not like', '%LOM%')
            ->orderBy('id')
            ->get(['id', 'name', 'organization'])
            ->unique(fn (TransmittalRecipient $recipient): string => mb_strtolower(trim($recipient->name)))
            ->values();

        $companies = TransmittalWorkflowBoundary::v2Query()->whereNotNull('related_company')->orderBy('id')->pluck('related_company')
            ->merge(TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)->whereNotNull('organization')->orderBy('id')->pluck('organization'))
            ->map(fn ($company): string => trim((string) $company))
            ->filter(fn (string $company): bool => strlen($company) <= 60 && ! str_contains(strtolower($company), 'confidential') && ! str_contains(strtolower($company), 'address:'))
            ->unique(fn (string $company): string => mb_strtolower($company))
            ->values();

        return compact('employees', 'contacts', 'companies');
    }

    private function syncRecipients(Transmittal $transmittal, array $recipients): void
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $transmittal->recipients()->delete();

        foreach ($recipients as $recipient) {
            $name = filled($recipient['name'] ?? null) ? trim((string) $recipient['name']) : null;
            $email = filled($recipient['email'] ?? null) ? trim((string) $recipient['email']) : null;
            $org = filled($recipient['organization'] ?? null) ? trim((string) $recipient['organization']) : null;

            if ($email !== null || $name !== null || $org !== null) {
                $transmittal->recipients()->create([
                    'name' => $name,
                    'email' => $email,
                    'organization' => $org,
                ]);
            }
        }
    }

    private function storeDocuments(Transmittal $transmittal, Request $request, ?User $user, array $validated): array
    {
        $storedDocuments = [];
        foreach ($request->file('documents', []) as $index => $file) {
            $storedDocuments[$index] = $this->storeDocument(
                $transmittal,
                $user,
                $file,
                $validated['categories'][$index] ?? 'document',
                $this->verifiedOcrData($validated['ocr_tokens'][$index] ?? null, $file)
            );
        }

        foreach ($request->file('supporting_attachments', []) as $file) {
            $this->storeDocument($transmittal, $user, $file, 'attachment', null);
        }

        foreach ($request->file('attachments', []) as $file) {
            $this->storeDocument($transmittal, $user, $file, 'attachment', null);
        }

        return $storedDocuments;
    }

    private function syncItems(
        Transmittal $transmittal,
        Request $request,
        ?User $user,
        array $validated,
        array $uploadedDocuments = []
    ): void
    {
        // Omission is not an instruction to replace the saved list. Null is invalid.
        if (! array_key_exists('items', $validated)) {
            return;
        }
        if (! is_array($validated['items'])) {
            throw ValidationException::withMessages(['items' => 'The items payload must be an array.']);
        }
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $sync = app(TransmittalWorkspaceItemSyncService::class);
        $sync->assertEditable($transmittal);
        $items = [];
        foreach ($validated['items'] as $position => $data) {
            if (! is_array($data)) {
                throw ValidationException::withMessages(['items' => 'Each item must be an object.']);
            }
            if (filled($data['id'] ?? null)) {
                $transmittal->workspaceItems()->whereKey($data['id'])->lockForUpdate()->firstOrFail();
            }
            if (filled($data['document_id'] ?? null)) {
                $transmittal->documents()->whereKey($data['document_id'])->lockForUpdate()->firstOrFail();
            }
            // Keep quantity as submitted text. Accept the old request alias only;
            // never read, synchronize or coerce a legacy transmittal_items quantity.
            if (! array_key_exists('quantity', $data) && array_key_exists('qty', $data)) {
                $data['quantity'] = $data['qty'];
            }
            if (array_key_exists('document_index', $data) && $data['document_index'] !== null) {
                $document = $uploadedDocuments[$data['document_index']] ?? null;
                if (! $document instanceof TransmittalDocument) {
                    throw ValidationException::withMessages(["items.{$position}.document_index" => 'The selected uploaded document is unavailable.']);
                }
                TransmittalWorkflowBoundary::assertChild($transmittal, $document);
                if (filled($data['document_id'] ?? null) && (string) $data['document_id'] !== (string) $document->id) {
                    throw ValidationException::withMessages(["items.{$position}.document_id" => 'The selected document references conflict.']);
                }
                $data['document_id'] = $document->id;
            }
            // Explicit per-item file fields retain their sparse request indices.
            $files = $request->file("items.{$position}.attachments", []);
            $files = is_array($files) ? $files : [$files];
            $single = $request->file("items.{$position}.attachment");
            if ($single instanceof UploadedFile) {
                array_unshift($files, $single);
            }
            $linked = false;
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $document = $this->storeDocument($transmittal, $user, $file, 'attachment', null);
                    if (! $linked) {
                        $data['document_id'] = $document->id;
                        $linked = true;
                    }
                }
            }
            $items[] = $data;
        }
        $sync->synchronize($transmittal, ['items' => $items]);
    }

    private function canApproveTransmittals(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->isAdmin();
    }

    private function sessionAuthorizedDraftIds(Request $request): array
    {
        $tokens = $request->hasSession() ? $request->session()->get('transmittal_draft_tokens', []) : [];
        if (! is_array($tokens) || $tokens === []) {
            return [];
        }

        $validTokens = [];
        foreach (array_slice($tokens, 0, 100, true) as $id => $token) {
            if (ctype_digit((string) $id) && (int) $id > 0 && is_string($token)
                && strlen($token) >= 32 && strlen($token) <= 100) {
                $validTokens[(int) $id] = $token;
            }
        }
        if ($validTokens === []) {
            return [];
        }

        return TransmittalWorkflowBoundary::v2Query()
            ->whereIn('id', array_keys($validTokens))
            ->whereIn('status', ['draft', 'rejected', 'pending_approval'])
            ->get(['id', 'edit_token_hash'])
            ->filter(fn (Transmittal $record): bool => is_string($validTokens[$record->id] ?? null)
                && is_string($record->edit_token_hash)
                && hash_equals($record->edit_token_hash, hash('sha256', $validTokens[$record->id])))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function visibleTransmittalQuery(Request $request)
    {
        $query = Transmittal::query();
        $user = $request->user();
        if ($this->canApproveTransmittals($user)) {
            return $query;
        }

        $sessionIds = $this->sessionAuthorizedDraftIds($request);
        if ($user && $user->id !== null) {
            return $query;
        }

        return $query->whereIn('id', $sessionIds);
    }

    private function canEditDraft(Request $request, Transmittal $transmittal): bool
    {
        if (! TransmittalWorkflowBoundary::isV2($transmittal)) {
            return false;
        }

        if (! in_array($transmittal->status, ['draft', 'rejected', 'pending_approval'], true)) {
            return false;
        }

        $user = $request->user();

        if ($this->canApproveTransmittals($user) || ($user && $user->id !== null && $transmittal->prepared_by === $user->id)) {
            return true;
        }

        $token = $request->hasSession() ? $request->session()->get("transmittal_draft_tokens.{$transmittal->id}") : null;

        return is_string($token)
            && is_string($transmittal->edit_token_hash)
            && hash_equals($transmittal->edit_token_hash, hash('sha256', $token));
    }

    public function show(Request $request, Transmittal $transmittal): View|RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canManageLifecycle($request, $transmittal), 404);

        $transmittal->load([
            'preparer',
            'approver',
            'rejector',
            'recipients.receipt',
            'documents',
            'workspaceItems.document',
            'proofs.recipient',
            'proofs.sender',
            'proofs.document',
            'receipts.recipient',
            'histories.actor',
            'authorityCode',
        ]);
        $canEditDraft = $this->canEditDraft($request, $transmittal);

        if ($transmittal->status === 'draft' || $transmittal->status === 'rejected') {
            return redirect()->route('transmittals.workspace', $transmittal);
        }

        $canViewPrivateFiles = $this->canViewPrivateFiles($request, $transmittal);
        $canViewAttachments = $this->canViewPrivateAttachments($request, $transmittal);
        $canManageLifecycle = $this->canManageLifecycle($request, $transmittal);

        // Recipient accounts may preview files addressed to them in the workspace,
        // but must not enter the internal transmittal detail page.
        if (! $canViewPrivateFiles && $canViewAttachments) {
            return redirect()->route('transmittals.workspace', $transmittal);
        }

        return view('transmittals.show', compact('transmittal', 'canEditDraft', 'canViewPrivateFiles', 'canViewAttachments', 'canManageLifecycle'));
    }

    public function approve(Request $request, Transmittal $transmittal, TransmittalApprovalService $approval): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $this->authorize('transmittal-v2.approve', $transmittal);
        $authority = $approval->approve($transmittal, $request->user());

        return redirect()->route('transmittals.show', $transmittal)
            ->with('status', "Approved. Authority code: {$authority->code}");
    }

    public function reject(
        Request $request,
        Transmittal $transmittal,
        TransmittalApprovalService $approval
    ): RedirectResponse {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $this->authorize('transmittal-v2.reject', $transmittal);
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $approval->reject($transmittal, $request->user(), $validated['rejection_reason']);

        return redirect()->route('transmittals.show', $transmittal)->with('status', 'Transmittal rejected.');
    }

    public function recall(Request $request, Transmittal $transmittal): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canManageLifecycle($request, $transmittal), 404);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $transmittal, $validated): void {
            $transmittal = TransmittalWorkflowBoundary::v2Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($this->canManageLifecycle($request, $transmittal), 404);
            $record = $transmittal;
            abort_unless($record->status === 'pending_approval', 409, 'Only a submission awaiting review can be recalled.');
            abort_if($this->hasFrozenApprovalEvidence($record), 409, 'Frozen approval evidence prevents recall.');
            $record->forceFill([
                'status' => 'draft',
                'workflow_status' => 'Uploaded',
                'approval_status' => 'Draft',
            ])->save();
            $record->histories()->create([
                'actor_id' => $request->user()?->id,
                'action' => 'recalled',
                'description' => 'Submission recalled from administrator review.',
                'metadata' => ['reason' => $validated['reason']],
                'created_at' => now(),
            ]);
        });

        return redirect()->route('transmittals.workspace', $transmittal)
            ->with('status', 'Submission recalled to its draft workspace.');
    }

    public function cancel(Request $request, Transmittal $transmittal): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canManageLifecycle($request, $transmittal), 404);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $transmittal, $validated): void {
            $transmittal = TransmittalWorkflowBoundary::v2Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($this->canManageLifecycle($request, $transmittal), 404);
            $record = $transmittal;
            abort_unless(in_array($record->status, ['draft', 'pending_approval', 'rejected'], true), 409, 'This transmittal can no longer be cancelled.');
            abort_if($this->hasFrozenApprovalEvidence($record), 409, 'Frozen approval evidence prevents cancellation.');
            $previousStatus = $record->status;
            $record->forceFill([
                'status' => 'cancelled',
                'workflow_status' => 'Archived',
                'cancelled_by' => $request->user()?->id,
                'cancelled_from_status' => $previousStatus,
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
            ])->save();
            $record->histories()->create([
                'actor_id' => $request->user()?->id,
                'action' => 'cancelled',
                'description' => 'Transmittal cancelled before approval.',
                'metadata' => ['reason' => $validated['reason'], 'previous_status' => $previousStatus],
                'created_at' => now(),
            ]);
        });

        return redirect()->route('transmittals.index', ['filter' => 'archived'])
            ->with('status', 'Transmittal archived and recorded in its history.');
    }

    public function revokeRecipientLink(Request $request, Transmittal $transmittal, TransmittalRecipient $recipient): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canApproveTransmittals($request->user()), 403);
        abort_unless((int) $recipient->transmittal_id === (int) $transmittal->id, 404);
        abort_unless($recipient->sent_at && ! $recipient->acknowledgment_revoked_at, 409, 'This acknowledgment link is not active.');
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $transmittal, $recipient, $validated): void {
            $transmittal = TransmittalWorkflowBoundary::v2Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($this->canManageLifecycle($request, $transmittal), 404);
            $recipient = $transmittal->recipients()->whereKey($recipient->id)->lockForUpdate()->firstOrFail();
            abort_unless($recipient->sent_at && ! $recipient->acknowledgment_revoked_at, 409);
            $revokedAt = now();
            $recipient->forceFill(['acknowledgment_revoked_at' => $revokedAt])->save();
            $transmittal->acknowledgments()->create([
                'recipient_id' => $recipient->id,
                'event' => 'link_revoked',
                'details' => ['reason' => $validated['reason'], 'revoked_by' => $request->user()->id],
                'occurred_at' => $revokedAt,
            ]);
            $transmittal->histories()->create([
                'actor_id' => $request->user()->id,
                'action' => 'recipient_link_revoked',
                'description' => "Acknowledgment link revoked for {$recipient->email}.",
                'metadata' => ['reason' => $validated['reason'], 'recipient_id' => $recipient->id],
                'created_at' => $revokedAt,
            ]);
        });

        return redirect()->route('transmittals.show', $transmittal)
            ->with('status', 'Recipient acknowledgment link revoked.');
    }


public function transmit(
    Request $request,
    Transmittal $transmittal,
    TransmittalEmailDispatchService $dispatches,
    TransmittalDispatchPayloadCipher $cipher
): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse {
    $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
    $this->authorize('transmittal-v2.transmit', $transmittal);
    abort_unless(\App\Services\TransmittalV2\TransmittalDispatchProviderAvailability::isEnabled(), 503,
        'V2 dispatch is disabled or no approved provider is configured. No message was sent.');

    $transmittal = TransmittalWorkflowBoundary::v2Query()->with(['recipients', 'documents', 'workspaceItems.document'])
        ->whereKey($transmittal->getKey())->firstOrFail();
    abort_unless(in_array($transmittal->status, ['approved', 'sent'], true), 409,
        'Only approved V2 transmittals can be transmitted.');
    if ($transmittal->recipients->isEmpty()) {
        throw ValidationException::withMessages(['recipients' => 'At least one recipient is required before transmission.']);
    }
    $queued = 0;
    foreach ($transmittal->recipients as $recipientPreview) {
        if ($recipientPreview->sent_at !== null) {
            continue;
        }
        $email = trim((string) $recipientPreview->email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['recipients' => 'Every unsent recipient needs a valid email address.']);
        }

        $dispatchId = DB::transaction(function () use ($request, $transmittal, $recipientPreview, $email, $dispatches, $cipher): ?int {
            $parent = TransmittalWorkflowBoundary::v2Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail();
            $this->authorize('transmittal-v2.transmit', $parent);
            abort_unless(in_array($parent->status, ['approved', 'sent'], true), 409);
            $recipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
                ->where('transmittal_id', $parent->getKey())->whereKey($recipientPreview->getKey())->lockForUpdate()->firstOrFail();
            if ($recipient->sent_at !== null) {
                return null;
            }
            $existing = \App\Models\TransmittalEmailDispatch::query()
                ->where('workflow_version', 'v2')->where('transmittal_id', $parent->getKey())
                ->where('recipient_id', $recipient->getKey())
                ->whereIn('state', ['pending', 'processing', 'accepted', 'unknown', 'failed'])->first();
            if ($existing !== null) {
                return null;
            }

            $token = Str::random(64);
            $expiresAt = now()->addHours((int) config('transmittal_v2.acknowledgment.hours', 72));
            $recipient->forceFill([
                'acknowledgment_token_hash' => hash('sha256', $token),
                'acknowledgment_expires_at' => $expiresAt,
            ])->save();
            $acknowledgmentUrl = route('transmittal-v2.acknowledgments.show', ['token' => $token]);
            $html = view('emails.transmittal-v2.acknowledgment-request', [
                'recipient' => $recipient, 'transmittal' => $parent, 'acknowledgmentUrl' => $acknowledgmentUrl,
            ])->render();
            $attachmentRefs = $parent->documents->where('category', 'attachment')->map(function (TransmittalDocument $document) use ($parent): array {
                TransmittalWorkflowBoundary::assertChild($parent, $document);
                return ['disk' => 'local', 'path' => $document->storage_path, 'sha256' => $document->sha256, 'name' => $document->original_name];
            })->values()->all();
            $versionFingerprint = hash('sha256', json_encode([
                'transmittal' => $parent->getKey(), 'recipient_id' => $recipient->getKey(), 'email' => $email,
                'subject' => $parent->subject, 'snapshot' => $parent->approved_snapshot,
                'attachments' => array_map(static fn ($item) => [$item['path'], $item['sha256']], $attachmentRefs),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
            $dispatch = $dispatches->createSendableIntent($parent, $recipient, $request->user(), $versionFingerprint, [
                'to' => ['email' => $email, 'name' => $recipient->name],
                'subject' => 'Transmittal '.($parent->tracking_number ?: $parent->id).': '.$parent->subject,
                'html' => $html,
                'acknowledgment_token' => $token,
                'attachments' => $attachmentRefs,
            ], $cipher);
            DB::afterCommit(fn () => DispatchTransmittalEmail::dispatch((int) $dispatch->getKey())->onQueue('transmittal-v2'));
            return (int) $dispatch->getKey();
        });
        if ($dispatchId !== null) {
            $queued++;
        }
    }

    $message = $queued > 0 ? "{$queued} recipient dispatch intent(s) queued." : 'No new recipient dispatch was created.';
    return $request->expectsJson()
        ? response()->json(['success' => true, 'message' => $message, 'queued' => $queued], 202)
        : redirect()->route('transmittals.show', $transmittal)->with('status', $message);
}

public function manualResend(Request $request, TransmittalEmailDispatch $dispatch, TransmittalEmailDispatchResender $resender): JsonResponse|RedirectResponse
{
    abort_unless(\App\Services\TransmittalV2\TransmittalDispatchProviderAvailability::isEnabled(), 503, 'V2 dispatch is disabled.');
    $validated = $request->validate([
        'reason' => ['required', 'string', 'min:10', 'max:1000'],
        'request_uuid' => ['required', 'uuid'],
    ]);
    $copy = $resender->request($dispatch, $request->user(), $validated['reason'], $validated['request_uuid']);
    DB::afterCommit(fn () => DispatchTransmittalEmail::dispatch((int) $copy->getKey())->onQueue('transmittal-v2'));

    return $request->expectsJson()
        ? response()->json(['success' => true, 'dispatch_uuid' => $copy->dispatch_uuid], 202)
        : redirect()->back()->with('status', 'Authorized manual resend was queued.');
}


    public function uploadProof(Request $request, Transmittal $transmittal): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $this->authorize('transmittal-v2.upload-proof', $transmittal);

        $rules = [
            'recipient_id' => [
                'nullable',
                Rule::exists('transmittal_recipients', 'id')
                    ->where('transmittal_id', $transmittal->id),
            ],
            'proof' => ['required', 'file', 'max:51200', 'mimes:pdf,jpg,jpeg,png,tif,tiff,mp4,mov,webm'],
            'channel' => ['required', Rule::in(['email', 'courier', 'in_person', 'registered_mail', 'portal', 'manual', 'other'])],
            'channel_other' => ['nullable', 'string', 'max:160'],
            'proof_type' => ['required', Rule::in(['delivery_receipt', 'email_confirmation', 'courier_receipt', 'other'])],
            'proof_type_other' => ['nullable', 'string', 'max:160'],
            'sender_name' => ['required', 'string', 'max:160'],
            'sender_position' => ['nullable', 'string', 'max:160'],
            'receiver_name' => ['required', 'string', 'max:160'],
            'receiver_email' => ['nullable', 'email', 'max:254'],
            'acknowledged_by' => ['nullable', 'required_with:acknowledged_at', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:200'],
            'device' => ['nullable', 'string', 'max:300'],
            'device_category' => ['required', Rule::in(['desktop', 'laptop', 'mobile', 'tablet', 'other'])],
            'device_category_other' => ['nullable', 'string', 'max:160'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'proof_date' => ['nullable', 'date'],
            'transmission_at' => ['required', 'date'],
            'acknowledged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'ocr_token' => ['nullable', 'string', 'max:100000'],
            'return_to' => ['nullable', Rule::in(['workspace', 'show'])],
        ];
        if (filled($request->input('ocr_token'))) {
            $rules['ocr_reviewed'] = ['required', 'accepted'];
        }
        (new TransmittalUploadBudget())->validate($request->allFiles());
        $validated = $request->validate($rules);

        /** @var UploadedFile $file */
        $file = $validated['proof'];
        $ocrData = $this->verifiedOcrData($validated['ocr_token'] ?? null, $file);

        $document = $this->storeDocument($transmittal, $request->user(), $file, 'proof', $ocrData);
        $uploadedAt = now();
        $transmissionAt = Carbon::parse($validated['transmission_at']);
        $acknowledgedAt = filled($validated['acknowledged_at'] ?? null)
            ? Carbon::parse($validated['acknowledged_at'])
            : null;
        $transactionReference = 'TXN-'.strtoupper(Str::random(18));

        try {
            DB::transaction(function () use ($request, $transmittal, $validated, $document, $uploadedAt, $transmissionAt, $acknowledgedAt, $transactionReference, $ocrData): void {
                $transmittal = TransmittalWorkflowBoundary::v2Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail();
                abort_unless($this->canManageLifecycle($request, $transmittal), 404);
                $this->authorize('transmittal-v2.upload-proof', $transmittal);
                TransmittalWorkflowBoundary::assertChild($transmittal, $document);
                if (! empty($validated['recipient_id'])) {
                    $transmittal->recipients()->whereKey($validated['recipient_id'])->lockForUpdate()->firstOrFail();
                }
                $transmittal->proofs()->create([
                    'recipient_id' => $validated['recipient_id'],
                    'document_id' => $document->id,
                    'sent_by' => $request->user()?->id,
                    'channel' => $validated['channel'],
                    'status' => $acknowledgedAt ? 'acknowledged' : 'uploaded',
                    'transaction_reference' => $transactionReference,
                    'acknowledged_at' => $acknowledgedAt,
                    'transmitted_at' => $transmissionAt,
                    'provider_metadata' => [
                        'source' => $ocrData === null ? 'manual_upload' : 'ocr_assisted_upload',
                        'proof_type' => $validated['proof_type'],
                        'proof_type_other' => $validated['proof_type_other'] ?? null,
                        'channel_other' => $validated['channel_other'] ?? null,
                        'sender_name' => $validated['sender_name'],
                        'sender_position' => $validated['sender_position'] ?? null,
                        'sender_location' => $validated['location'] ?? null,
                        'location' => $validated['location'] ?? null,
                        'location_source' => filled($validated['location'] ?? null) ? 'user_entered' : null,
                        'device_category' => $validated['device_category'] ?? null,
                        'device_category_other' => $validated['device_category_other'] ?? null,
                        'device' => $validated['device'] ?? null,
                        'device_source' => filled($validated['device'] ?? null) || filled($validated['device_category'] ?? null) ? 'user_entered' : null,
                        'transmission_at_source' => 'user_entered',
                        'recorded_by_name' => $request->user()?->name ?? 'Public Submitter',
                        'recorded_at' => $uploadedAt->toIso8601String(),
                        'receiver_name' => $validated['receiver_name'],
                        'receiver_email' => $validated['receiver_email'] ?? null,
                        'acknowledged_by' => $validated['acknowledged_by'] ?? null,
                        'ip_address' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                        'reference_number' => $validated['reference_number'] ?? null,
                        'proof_date' => $transmissionAt->toDateString(),
                        'transmission_at' => $transmissionAt->toIso8601String(),
                        'acknowledged_at' => $acknowledgedAt?->toIso8601String(),
                        'notes' => $validated['notes'] ?? null,
                        'ocr_suggestions' => $ocrData['fields'] ?? [],
                        'ocr_field_metadata' => $ocrData['field_metadata'] ?? [],
                        'reviewed_values' => [
                            'receiver_name' => $validated['receiver_name'],
                            'sender_name' => $validated['sender_name'],
                            'sender_position' => $validated['sender_position'] ?? null,
                            'sender_location' => $validated['location'] ?? null,
                            'receiver_email' => $validated['receiver_email'] ?? null,
                            'acknowledged_by' => $validated['acknowledged_by'] ?? null,
                            'location' => $validated['location'] ?? null,
                            'device' => $validated['device'] ?? null,
                            'device_category' => $validated['device_category'] ?? null,
                            'reference_number' => $validated['reference_number'] ?? null,
                            'proof_date' => $transmissionAt->toDateString(),
                            'transmission_at' => $transmissionAt->toIso8601String(),
                            'acknowledged_at' => $acknowledgedAt?->toIso8601String(),
                            'notes' => $validated['notes'] ?? null,
                        ],
                        'reviewed_by' => $request->user()?->id,
                        'reviewed_at' => $uploadedAt->toIso8601String(),
                        'confirmed_by' => $request->user()?->id,
                        'confirmed_at' => $uploadedAt->toIso8601String(),
                        'initial_confirmation' => [
                            'user_id' => $request->user()?->id,
                            'user_name' => $request->user()?->name ?? 'Public Submitter',
                            'at' => $uploadedAt->toIso8601String(),
                        ],
                        'entry_mode' => $ocrData === null ? 'manual' : 'ocr',
                        'ocr_confirmed' => $ocrData !== null,
                    ],
                ]);

                $transmittal->histories()->create([
                    'actor_id' => $request->user()?->id,
                    'action' => 'proof_uploaded',
                    'description' => $acknowledgedAt
                        ? "Acknowledgment proof recorded for {$validated['acknowledged_by']}."
                        : "Manual proof uploaded for {$validated['receiver_name']}.",
                    'metadata' => [
                        'document_id' => $document->id,
                        'transaction_reference' => $transactionReference,
                    ],
                    'created_at' => $uploadedAt,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->reportRetainedV2DocumentFailure('proof_upload', $exception);
            throw $exception;
        }

        $destination = ($validated['return_to'] ?? 'show') === 'workspace'
            ? 'transmittals.workspace'
            : 'transmittals.show';

        return redirect()->route($destination, $transmittal)
            ->with('status', 'Proof of Transmission uploaded and recorded.');
    }

    public function updateProof(Request $request, Transmittal $transmittal, int $proof): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $this->authorize('transmittal-v2.upload-proof', $transmittal);

        $record = $transmittal->proofs()->findOrFail($proof);
        (new TransmittalUploadBudget())->validate($request->allFiles());
        $validated = $request->validate([
            'recipient_id' => ['nullable', Rule::exists('transmittal_recipients', 'id')->where('transmittal_id', $transmittal->id)],
            'proof' => ['nullable', 'file', 'max:51200', 'mimes:pdf,jpg,jpeg,png,tif,tiff,mp4,mov,webm'],
            'channel' => ['required', Rule::in(['email', 'courier', 'in_person', 'registered_mail', 'portal', 'manual', 'other'])],
            'channel_other' => ['nullable', 'string', 'max:160'],
            'proof_type' => ['required', Rule::in(['delivery_receipt', 'email_confirmation', 'courier_receipt', 'other'])],
            'proof_type_other' => ['nullable', 'string', 'max:160'],
            'sender_name' => ['required', 'string', 'max:160'],
            'sender_position' => ['nullable', 'string', 'max:160'],
            'receiver_name' => ['required', 'string', 'max:160'],
            'receiver_email' => ['nullable', 'email', 'max:254'],
            'acknowledged_by' => ['nullable', 'required_with:acknowledged_at', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:200'],
            'device' => ['nullable', 'string', 'max:300'],
            'device_category' => ['required', Rule::in(['desktop', 'laptop', 'mobile', 'tablet', 'other'])],
            'device_category_other' => ['nullable', 'string', 'max:160'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'transmission_at' => ['nullable', 'date'],
            'acknowledged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'edit_reason' => ['required', 'string', 'max:500'],
            'return_to' => ['nullable', Rule::in(['workspace', 'show'])],
        ]);

        $document = null;
        $metadata = $record->provider_metadata ?? [];
        $fields = [
            'sender_name' => $validated['sender_name'],
            'sender_position' => $validated['sender_position'] ?? null,
            'receiver_name' => $validated['receiver_name'],
            'receiver_email' => $validated['receiver_email'] ?? null,
            'location' => $validated['location'] ?? null,
            'device_category' => $validated['device_category'] ?? null,
            'device' => $validated['device'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'transmission_at' => filled($validated['transmission_at'] ?? null)
                ? Carbon::parse($validated['transmission_at'])->toIso8601String()
                : $record->transmitted_at?->toIso8601String(),
            'acknowledged_by' => $validated['acknowledged_by'] ?? null,
            'acknowledged_at' => filled($validated['acknowledged_at'] ?? null) ? Carbon::parse($validated['acknowledged_at'])->toIso8601String() : null,
            'notes' => $validated['notes'] ?? null,
        ];
        $previous = collect($fields)->map(fn ($value, $key) => match ($key) {
            'sender_name' => $metadata['sender_name'] ?? null,
            'sender_position' => $metadata['sender_position'] ?? null,
            'receiver_name' => $metadata['receiver_name'] ?? null,
            'receiver_email' => $metadata['receiver_email'] ?? null,
            'location' => $metadata['location'] ?? null,
            'device_category' => $metadata['device_category'] ?? null,
            'device' => $metadata['device'] ?? null,
            'reference_number' => $metadata['reference_number'] ?? null,
            'transmission_at' => $record->transmitted_at?->toIso8601String(),
            'acknowledged_by' => $metadata['acknowledged_by'] ?? null,
            'acknowledged_at' => $record->acknowledged_at?->toIso8601String(),
            'notes' => $metadata['notes'] ?? null,
        })->all();
        $editedAt = now();
        $transmissionAt = filled($validated['transmission_at'] ?? null)
            ? Carbon::parse($validated['transmission_at'])
            : $record->transmitted_at;
        $metadata['edit_history'] ??= [];
        $metadata['edit_history'][] = [
            'edited_by' => $request->user()->id,
            'edited_by_name' => $request->user()->name,
            'edited_at' => $editedAt->toIso8601String(),
            'reason' => $validated['edit_reason'],
            'previous' => $previous,
            'updated' => $fields,
        ];
        $metadata = array_merge($metadata, [
            'sender_name' => $validated['sender_name'],
            'sender_position' => $validated['sender_position'] ?? null,
            'sender_location' => $validated['location'] ?? null,
            'location' => $validated['location'] ?? null,
            'location_source' => filled($validated['location'] ?? null) ? 'user_entered' : null,
            'device_category' => $validated['device_category'] ?? null,
            'device' => $validated['device'] ?? null,
            'device_source' => filled($validated['device'] ?? null) || filled($validated['device_category'] ?? null) ? 'user_entered' : null,
            'reference_number' => $validated['reference_number'] ?? null,
            'receiver_name' => $validated['receiver_name'],
            'receiver_email' => $validated['receiver_email'] ?? null,
            'acknowledged_by' => $validated['acknowledged_by'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'reviewed_values' => $fields,
            'last_edited_by' => $request->user()->id,
            'last_edited_at' => $editedAt->toIso8601String(),
            'transmission_at_source' => filled($validated['transmission_at'] ?? null)
                ? 'user_entered'
                : ($metadata['transmission_at_source'] ?? null),
        ]);

        try {
            DB::transaction(function () use ($record, $transmittal, $request, $validated, $metadata, &$document, $editedAt, $transmissionAt): void {
                $transmittal = TransmittalWorkflowBoundary::v2Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail();
                abort_unless($this->canManageLifecycle($request, $transmittal), 404);
                $this->authorize('transmittal-v2.upload-proof', $transmittal);
                $record = $transmittal->proofs()->whereKey($record->id)->lockForUpdate()->firstOrFail();
                if ($record->document_id !== null) {
                    $transmittal->documents()->findOrFail($record->document_id);
                }
                if (! empty($validated['recipient_id'])) {
                    $transmittal->recipients()->whereKey($validated['recipient_id'])->lockForUpdate()->firstOrFail();
                }
                if ($request->file('proof')) {
                    $document = $this->storeDocument($transmittal, $request->user(), $validated['proof'], 'proof', null);
                }
                $editIndex = array_key_last($metadata['edit_history']);
                $metadata['edit_history'][$editIndex]['previous']['document_id'] = $record->document_id;
                $metadata['edit_history'][$editIndex]['previous']['document_name'] = $record->document?->original_name;
                $metadata['edit_history'][$editIndex]['updated']['document_id'] = $document?->id ?? $record->document_id;
                $metadata['edit_history'][$editIndex]['updated']['document_name'] = $document?->original_name ?? $record->document?->original_name;
                $record->forceFill([
                    'recipient_id' => $validated['recipient_id'] ?? null,
                    'document_id' => $document?->id ?? $record->document_id,
                    'channel' => $validated['channel'],
                    'status' => filled($validated['acknowledged_at'] ?? null) ? 'acknowledged' : 'uploaded',
                    'transmitted_at' => $transmissionAt,
                    'acknowledged_at' => filled($validated['acknowledged_at'] ?? null) ? Carbon::parse($validated['acknowledged_at']) : null,
                    'provider_metadata' => $metadata,
                ])->save();

                $transmittal->histories()->create([
                    'actor_id' => $request->user()->id,
                    'action' => 'proof_updated',
                    'description' => "Proof record {$record->transaction_reference} updated: {$validated['edit_reason']}",
                    'metadata' => ['proof_id' => $record->id, 'edited_at' => $editedAt->toIso8601String()],
                    'created_at' => $editedAt,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->reportRetainedV2DocumentFailure('proof_update', $exception);
            throw $exception;
        }

        $destination = ($validated['return_to'] ?? 'show') === 'workspace' ? 'transmittals.workspace' : 'transmittals.show';

        return redirect()->route($destination, $transmittal)->with('status', 'Proof record updated with an audit history entry.');
    }

    public function download(Request $request, Transmittal $transmittal, int $document): StreamedResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canViewPrivateAttachments($request, $transmittal), 404);
        $record = $transmittal->documents()->findOrFail($document);

        return $this->streamDocument($transmittal, $record, $request->boolean('download'));
    }

    public function downloadForRecipient(string $token, TransmittalDocument $document): StreamedResponse
    {
        $recipient = $this->recipientForToken($token);
        $document = TransmittalWorkflowBoundary::v2Children(TransmittalDocument::class)->findOrFail($document->id);
        abort_unless($recipient->transmittal_id === $document->transmittal_id, 404);

        return $this->streamDocument($document->transmittal, $document);
    }

    public function pdf(Request $request, Transmittal $transmittal): Response
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canViewPrivateFiles($request, $transmittal), 404);
        $transmittal->load(['preparer', 'approver', 'rejector', 'recipients', 'documents', 'workspaceItems.document', 'proofs.recipient', 'receipts', 'histories']);

        $pdf = Pdf::loadView('transmittals.pdf', compact('transmittal'))
            ->setPaper('a4');

        return $this->markPrivateFileResponse($pdf->download($transmittal->tracking_number.'.pdf'));
    }

    public function receiptPdf(Request $request, Transmittal $transmittal, ?int $receipt = null): Response
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canViewPrivateFiles($request, $transmittal), 404);
        if ($receipt === null) {
            $record = $transmittal->receipts()
                ->whereNotNull('recipient_id')
                ->with('recipient')
                ->orderByDesc('id')
                ->first();

            if (! $record) {
                abort(404, 'Receipt not found.');
            }

            return $this->renderReceiptPdf($transmittal, $record);
        }

        abort_unless($this->canViewPrivateFiles($request, $transmittal), 404);

        $record = $transmittal->receipts()
            ->whereNotNull('recipient_id')
            ->with('recipient')
            ->whereKey($receipt)
            ->first();
        abort_unless($record, 404);

        return $this->renderReceiptPdf($transmittal, $record);
    }

    public function receiptPdfForRecipient(string $token, int $receipt): Response
    {
        $recipient = $this->recipientForToken($token);
        $record = $recipient->receipt;
        abort_unless($record && $record->id === $receipt, 404);

        return $this->renderReceiptPdf($recipient->transmittal, $record);
    }

    private function streamDocument(Transmittal $transmittal, TransmittalDocument $record, bool $asDownload = false): StreamedResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        TransmittalWorkflowBoundary::assertChild($transmittal, $record);
        abort_unless(Storage::disk('local')->exists($record->storage_path), 404);
        $this->verifyDocumentIntegrity($transmittal, $record);

        $response = Storage::disk('local')->response(
            $record->storage_path,
            $record->original_name,
            [
                'Content-Type' => $record->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $asDownload ? 'attachment' : 'inline',
        );

        return $response;
    }

    private function renderReceiptPdf(Transmittal $transmittal, TransmittalReceipt $receipt): Response
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        TransmittalWorkflowBoundary::assertChild($transmittal, $receipt);
        if ($receipt->recipient_id !== null) {
            $recipient = $transmittal->recipients()->whereKey($receipt->recipient_id)->firstOrFail();
            TransmittalWorkflowBoundary::assertChild($transmittal, $recipient);
        }
        $transmittal->loadMissing(['workspaceItems.document', 'documents', 'attachments', 'recipients', 'receipts']);
        $receipt->loadMissing('recipient');

        $receiptDate = $receipt->created_at
            ? $receipt->created_at->format('Y-m-d')
            : now()->format('Y-m-d');

        $receivedAt = $receipt->received_at
            ? $receipt->received_at->format('Y-m-d H:i:s')
            : ($transmittal->received_at ? $transmittal->received_at->format('Y-m-d H:i:s') : 'N/A');

        $preparedAt = $transmittal->prepared_at
            ? $transmittal->prepared_at->format('Y-m-d H:i:s')
            : ($transmittal->created_at ? $transmittal->created_at->format('Y-m-d H:i:s') : 'N/A');

        $approvedAt = $transmittal->approved_at
            ? $transmittal->approved_at->format('Y-m-d H:i:s')
            : 'N/A';

        $deliveryType = 'N/A';
        if (($transmittal->delivery_type ?? '') === 'By Person' || ($transmittal->delivery_type ?? '') === 'by_person') {
            $deliveryType = $transmittal->by_person_who
                ? 'By Person - ' . $transmittal->by_person_who
                : 'By Person';
        } elseif (($transmittal->delivery_type ?? '') === 'Registered Mail' || ($transmittal->delivery_type ?? '') === 'registered_mail') {
            $deliveryType = $transmittal->registered_mail_provider
                ? 'Registered Mail - ' . $transmittal->registered_mail_provider
                : 'Registered Mail';
        } elseif (($transmittal->delivery_type ?? '') === 'Electronic' || ($transmittal->delivery_type ?? '') === 'electronic') {
            $deliveryType = 'Electronic' . ($transmittal->electronic_method ? ' - ' . $transmittal->electronic_method : '');
        }

        $actions = collect([
            $transmittal->action_delivery ? 'Delivery' : null,
            $transmittal->action_pick_up ? 'Pick Up' : null,
            $transmittal->action_drop_off ? 'Drop Off' : null,
            $transmittal->action_email ? 'Email' : null,
        ])->filter()->implode(', ');

        if ($actions === '') {
            $actions = collect($transmittal->delivery_actions ?? [])
                ->map(fn ($action) => str($action)->replace('_', ' ')->title())
                ->implode(', ') ?: '—';
        }

        $fromValue = ($transmittal->direction ?? $transmittal->mode ?? 'send') === 'send'
            ? ($transmittal->origin_office ?: $transmittal->office_name ?: config('transmittal_v2.office_name', 'JK&C INC.'))
            : ($transmittal->party_name ?: $receipt->recipient?->name ?: 'N/A');

        $toValue = ($transmittal->direction ?? $transmittal->mode ?? 'send') === 'send'
            ? ($receipt->recipient?->name ?: $transmittal->party_name ?: $transmittal->recipients->first()?->name ?: 'N/A')
            : ($transmittal->origin_office ?: $transmittal->office_name ?: config('transmittal_v2.office_name', 'JK&C INC.'));

        $approvedByText = ($transmittal->approver?->name ?? $transmittal->approved_by_name ?? 'N/A')
            . ($transmittal->approved_position ? ' (' . $transmittal->approved_position . ')' : '');

        $corporateContext = $this->transmittalCorporateContext();

        $fileName = ($transmittal->tracking_number ?? ($transmittal->transmittal_no ?? 'TRN')) . '-PACKAGE-RECEIPT.pdf';

        $response = Pdf::loadView('transmittals.pdf', [
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
        ->setPaper('a4', 'portrait')
        ->setOptions(['isPhpEnabled' => false, 'isRemoteEnabled' => false]);

        $basePdfContent = $response->output();

        // Check for attached PDF documents to append their full pages
        $pdfDocs = ($transmittal->documents ?? collect())->filter(function ($doc) {
            $ext = strtolower(pathinfo($doc->original_name, PATHINFO_EXTENSION));
            return ($ext === 'pdf' || $doc->mime_type === 'application/pdf')
                && Storage::disk('local')->exists($doc->storage_path);
        });

        // Workspace links reference private documents; never legacy attachment_path.
        $itemPdfPaths = $transmittal->workspaceItems->map(function ($item) use ($transmittal, $pdfDocs) {
            $document = $item->document;
            if ($document === null) {
                return null;
            }
            TransmittalWorkflowBoundary::assertChild($transmittal, $document);
            if ($pdfDocs->contains('id', $document->id) || $document->mime_type !== 'application/pdf') {
                return null;
            }
            $this->verifyDocumentIntegrity($transmittal, $document);
            return Storage::disk('local')->path($document->storage_path);
        })->filter()->values();

        if ($pdfDocs->isNotEmpty() || $itemPdfPaths->isNotEmpty()) {
            try {
                $fpdi = new \setasign\Fpdi\Fpdi();

                // 1. Import base package pages (Cover, Form, Receipt, Manifest)
                $tempBaseFile = tempnam(sys_get_temp_dir(), 'base_pkg_');
                file_put_contents($tempBaseFile, $basePdfContent);
                $basePageCount = $fpdi->setSourceFile($tempBaseFile);
                for ($pageNo = 1; $pageNo <= $basePageCount; $pageNo++) {
                    $templateId = $fpdi->importPage($pageNo);
                    $size = $fpdi->getTemplateSize($templateId);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($templateId);
                }
                @unlink($tempBaseFile);

                // 2. Append all attached PDF documents with clear "ATTACHMENT" header banner
                foreach ($pdfDocs as $pdfDoc) {
                    $docFullPath = Storage::disk('local')->path($pdfDoc->storage_path);
                    try {
                        $docPageCount = $fpdi->setSourceFile($docFullPath);
                        for ($pageNo = 1; $pageNo <= $docPageCount; $pageNo++) {
                            $templateId = $fpdi->importPage($pageNo);
                            $size = $fpdi->getTemplateSize($templateId);
                            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $fpdi->useTemplate($templateId);

                            // Add prominent ATTACHMENT top header banner
                            $fpdi->SetFont('Arial', 'B', 8);
                            $fpdi->SetTextColor(15, 23, 42);
                            $fpdi->SetFillColor(241, 245, 249);
                            $fpdi->SetDrawColor(203, 213, 225);
                            $fpdi->Rect(5, 4, $size['width'] - 10, 6, 'DF');
                            $fpdi->SetXY(8, 5.2);
                            $headerText = 'ATTACHMENT: ' . strtoupper($pdfDoc->original_name) . ($docPageCount > 1 ? " (Page {$pageNo} of {$docPageCount})" : '');
                            $fpdi->Cell($size['width'] - 16, 4, $headerText, 0, 0, 'L');
                        }
                    } catch (\Throwable $docErr) {
                        \Log::warning("Could not merge attached PDF document ID {$pdfDoc->id}: " . $docErr->getMessage());
                    }
                }

                // 3. Append all item attachment PDFs with clear "ATTACHMENT" header banner
                foreach ($itemPdfPaths as $itemPath) {
                    try {
                        $docPageCount = $fpdi->setSourceFile($itemPath);
                        $baseFileName = basename($itemPath);
                        for ($pageNo = 1; $pageNo <= $docPageCount; $pageNo++) {
                            $templateId = $fpdi->importPage($pageNo);
                            $size = $fpdi->getTemplateSize($templateId);
                            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $fpdi->useTemplate($templateId);

                            // Add prominent ATTACHMENT top header banner
                            $fpdi->SetFont('Arial', 'B', 8);
                            $fpdi->SetTextColor(15, 23, 42);
                            $fpdi->SetFillColor(241, 245, 249);
                            $fpdi->SetDrawColor(203, 213, 225);
                            $fpdi->Rect(5, 4, $size['width'] - 10, 6, 'DF');
                            $fpdi->SetXY(8, 5.2);
                            $headerText = 'ATTACHMENT: ' . strtoupper($baseFileName) . ($docPageCount > 1 ? " (Page {$pageNo} of {$docPageCount})" : '');
                            $fpdi->Cell($size['width'] - 16, 4, $headerText, 0, 0, 'L');
                        }
                    } catch (\Throwable $itemErr) {
                        \Log::warning("Could not merge item PDF attachment: " . $itemErr->getMessage());
                    }
                }

                $mergedPdf = $fpdi->Output('S');

                return $this->markPrivateFileResponse(
                    response($mergedPdf, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    ])
                );
            } catch (\Throwable $mergeErr) {
                \Log::error("FPDI PDF merge failed, falling back to base package: " . $mergeErr->getMessage());
            }
        }

        return $this->markPrivateFileResponse(
            response($basePdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ])
        );
    }

    private function markPrivateFileResponse(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }


private function storeDocument(
    Transmittal $transmittal,
    ?User $user,
    UploadedFile $file,
    string $category,
    ?array $ocrData
): TransmittalDocument {
    $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
    $directory = "transmittals/{$transmittal->id}";
    $extension = strtolower($file->guessExtension() ?: 'bin');
    $attemptId = (string) Str::uuid();
    $filename = $attemptId.'.'.$extension;
    $path = $directory.'/'.$filename;
    $journalPath = 'transmittal-attempts/'.$attemptId.'.json';
    $disk = Storage::disk('local');
    $inputPath = $file->getRealPath();
    $expectedSize = $file->getSize();
    $expectedHash = is_string($inputPath) && is_file($inputPath) ? hash_file('sha256', $inputPath) : false;
    if (! is_int($expectedSize) || $expectedSize <= 0 || ! is_string($expectedHash)) {
        throw new \RuntimeException('The uploaded document could not be verified.');
    }
    if ($disk->exists($path) || $disk->exists($journalPath)) {
        throw new \RuntimeException('A unique document attempt path could not be reserved.');
    }

    // Persist reconciliation evidence BEFORE any document write or row creation.
    // This is an intent journal, never a claim that the database rolled back.
    // Successful and failed attempt journals remain private; no automatic deletion.
    $journal = json_encode([
        'version' => 1, 'attempt_reference' => $attemptId,
        'transmittal_id' => $transmittal->id, 'storage_path' => $path,
        'expected_size_bytes' => $expectedSize, 'expected_sha256' => $expectedHash,
        'state' => 'write_intended_outcome_unverified', 'created_at' => now()->toIso8601String(),
    ], JSON_THROW_ON_ERROR);
    if (! $disk->put($journalPath, $journal)) {
        throw new \RuntimeException('Document reconciliation evidence could not be recorded.');
    }

    try {
        $storedPath = $file->storeAs($directory, $filename, 'local');
        if (! is_string($storedPath) || $storedPath !== $path) {
            throw new \RuntimeException('Unable to store the uploaded document.');
        }
        $realPath = $disk->path($path);
        if (! is_file($realPath) || filesize($realPath) !== $expectedSize
            || ! hash_equals($expectedHash, (string) hash_file('sha256', $realPath))) {
            throw new \RuntimeException('Unable to verify the uploaded file content.');
        }

        return $transmittal->documents()->create([
            'uploaded_by' => $user?->id,
            'category' => $category,
            'original_name' => basename($file->getClientOriginalName()),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $expectedSize,
            'sha256' => $expectedHash,
            'ocr_text' => $ocrData['text'] ?? null,
            'extracted_data' => $ocrData === null
                ? null
                : array_merge($ocrData['fields'], ['_field_metadata' => $ocrData['field_metadata'] ?? []]),
        ]);
    } catch (\Throwable $exception) {
        // A lost commit acknowledgment can leave referenced rows behind.
        // Retain every attempt path and journal; do not query rows to justify deletion.
        $this->reportRetainedV2DocumentFailure('document_record', $exception);
        throw $exception;
    }
}

    private function reportRetainedV2DocumentFailure(string $phase, \Throwable $exception): void
    {
        try {
            Log::error('V2 document attempt retained after failure.', [
                'phase' => $phase,
                'exception_class' => $exception::class,
                'reconciliation_directory' => 'transmittal-attempts',
            ]);
        } catch (\Throwable) {
            // Logging must not mask the primary failure or remove durable intent journals.
        }
    }


    private function verifiedOcrData(?string $token, UploadedFile $file): ?array
    {
        if ($token === null || $token === '') {
            return null;
        }

        try {
            $analysis = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            throw ValidationException::withMessages([
                'documents' => 'OCR analysis has expired or is invalid. Please classify the files again.',
            ]);
        }

        $path = $file->getRealPath();
        if (! is_string($path)
            || ! is_array($analysis)
            || ($analysis['name'] ?? null) !== basename($file->getClientOriginalName())
            || ! hash_equals((string) ($analysis['sha256'] ?? ''), hash_file('sha256', $path) ?: '')
            || ! in_array($analysis['category'] ?? null, ['document', 'receipt', 'proof', 'attachment'], true)
            || ! is_string($analysis['text'] ?? null)
            || ! is_array($analysis['fields'] ?? null)
            || ! is_array($analysis['field_metadata'] ?? [])) {
            throw ValidationException::withMessages([
                'documents' => 'The uploaded files do not match the completed OCR analysis.',
            ]);
        }

        return $analysis;
    }

    private function verifyDocumentIntegrity(Transmittal $transmittal, TransmittalDocument $document): void
    {
        TransmittalWorkflowBoundary::assertChild($transmittal, $document);
        $path = Storage::disk('local')->path($document->storage_path);
        $digest = is_file($path) ? hash_file('sha256', $path) : false;

        if (! is_string($digest) || ! hash_equals($document->sha256, $digest)) {
            Log::error('Transmittal attachment integrity check failed.', [
                'transmittal_id' => $transmittal->id,
                'document_id' => $document->id,
            ]);
            abort(409, 'Attachment integrity verification failed. Contact a workspace administrator.');
        }
    }

    private function canViewPrivateFiles(Request $request, Transmittal $transmittal): bool
    {
        if ($request->user()) {
            return true;
        }

        return $this->canEditDraft($request, $transmittal);
    }

    private function canViewPrivateAttachments(Request $request, Transmittal $transmittal): bool
    {
        if ($request->user()) {
            return true;
        }

        return $this->canEditDraft($request, $transmittal);
    }

    private function canManageLifecycle(Request $request, Transmittal $transmittal): bool
    {
        $user = $request->user();

        if ($user && $user->id !== null) {
            return true;
        }

        $token = $request->hasSession() ? $request->session()->get("transmittal_draft_tokens.{$transmittal->id}") : null;

        return is_string($token)
            && is_string($transmittal->edit_token_hash)
            && hash_equals($transmittal->edit_token_hash, hash('sha256', $token));
    }

    private function hasFrozenApprovalEvidence(Transmittal $transmittal): bool
    {
        return $transmittal->approved_snapshot !== null
            || $transmittal->approved_at !== null
            || $transmittal->approved_by !== null
            || $transmittal->approval_authority_code !== null
            || strtolower((string) $transmittal->approval_status) === 'approved'
            || $transmittal->authorityCode()->exists();
    }

    private function recipientForToken(string $token): TransmittalRecipient
    {
        abort_if(strlen($token) < 32 || strlen($token) > 100, 404);

        $recipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
            ->with(['transmittal', 'receipt'])
            ->where('acknowledgment_token_hash', hash('sha256', $token))
            ->where('acknowledgment_expires_at', '>', now())
            ->whereNull('acknowledgment_revoked_at')
            ->first();

        abort_unless($recipient && $recipient->sent_at, 404);
        $parent = TransmittalWorkflowBoundary::requireV2($recipient->transmittal);
        $recipient->setRelation('transmittal', $parent);
        if ($recipient->receipt !== null) {
            TransmittalWorkflowBoundary::assertChild($parent, $recipient->receipt);
            abort_unless((string) $recipient->receipt->recipient_id === (string) $recipient->id, 404);
        }

        return $recipient;
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

    public function data(Request $request): JsonResponse
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

        $query = $this->visibleTransmittalQuery($request)->with(['workspaceItems.document', 'receipts.recipient', 'attachments', 'recipients']);

        // `status` is the source of truth; `workflow_status` can be stale, so it is only a fallback when status is NULL.
        $statusMap = [
            'uploaded' => ['draft'],
            'submitted' => ['pending_approval'],
            'accepted' => ['approved', 'sent', 'acknowledged'],
            'reverted' => ['rejected'],
            'archived' => ['cancelled'],
        ];

        if (isset($statusMap[$workflow])) {
            $statuses = $statusMap[$workflow];
            $label = $workflowMap[$workflow];
            $query->where(function ($q) use ($statuses, $label) {
                $q->whereIn('status', $statuses)
                    ->orWhere(function ($sub) use ($label) {
                        $sub->whereNull('status')->where('workflow_status', $label);
                    });
            });
        }

        $searchTerm = $request->string('q')->trim()->toString() ?: $request->string('search')->trim()->toString();
        if ($searchTerm !== '') {
            $term = '%'.addcslashes($searchTerm, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('transmittal_no', 'like', $term)
                    ->orWhere('tracking_number', 'like', $term)
                    ->orWhere('subject', 'like', $term)
                    ->orWhere('party_name', 'like', $term)
                    ->orWhere('office_name', 'like', $term)
                    ->orWhere('external_reference', 'like', $term)
                    ->orWhere('origin_office', 'like', $term)
                    ->orWhere('related_company', 'like', $term)
                    ->orWhereHas('documents', fn ($documents) => $documents->where('original_name', 'like', $term))
                    ->orWhereHas('recipients', fn ($recipients) => $recipients
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('organization', 'like', $term));
            });
        }

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())
            ->map(function ($item) {
                $deliveryActions = is_array($item->delivery_actions) ? $item->delivery_actions : [];
                $receipts = $item->receipts->map(function (TransmittalReceipt $receipt) use ($item): array {
                    TransmittalWorkflowBoundary::assertChild($item, $receipt);

                    if ($receipt->recipient_id !== null) {
                        abort_unless($receipt->recipient !== null, 409, 'Receipt recipient association is invalid.');
                        TransmittalWorkflowBoundary::assertChild($item, $receipt->recipient);
                        abort_unless((string) $receipt->recipient->id === (string) $receipt->recipient_id, 409, 'Receipt recipient association is invalid.');
                    }

                    return [
                        'id' => $receipt->id,
                        'receipt_no' => $receipt->receipt_no,
                        'recipient_id' => $receipt->recipient_id,
                        'signed_name' => $receipt->signed_name,
                        'received_at' => optional($receipt->received_at)->format('Y-m-d H:i'),
                        'url' => route('transmittals.receipts.pdf', ['transmittal' => $item->id, 'receipt' => $receipt->id]),
                    ];
                })->values();
                $primaryReceipt = $receipts->first();

                return [
                    'id' => $item->id,
                    'transmittal_no' => $item->transmittal_no ?? $item->tracking_number,
                    'date' => optional($item->transmittal_date)->format('Y-m-d'),
                    'mode' => strtoupper($item->direction ?? ($item->mode ?? 'SEND')),
                    'from_value' => $item->from_value,
                    'to_value' => $item->to_value,
                    'delivery_type' => $item->delivery_summary,
                    'actions' => $item->actions_summary,
                    'workflow_status' => match ($item->status) {
                        'draft' => 'Uploaded',
                        'pending_approval' => 'Submitted',
                        'approved', 'sent', 'acknowledged' => 'Accepted',
                        'rejected' => 'Reverted',
                        'cancelled' => 'Archived',
                        default => $item->workflow_status ?? 'Uploaded',
                    },
                    'approval_status' => match ($item->status) {
                        'approved', 'sent', 'acknowledged' => 'Approved',
                        'rejected' => 'Rejected',
                        default => $item->approval_status ?? 'Pending',
                    },
                    'party_name' => $item->party_name ?? $item->recipients->first()?->name,
                    'office_name' => $item->office_name ?? $item->origin_office,
                    'address' => $item->address ?? $item->recipient_address,
                    'by_person_who' => $item->by_person_who,
                    'registered_mail_provider' => $item->registered_mail_provider,
                    'electronic_method' => $item->electronic_method,
                    'recipient_email' => $item->recipient_email ?? $item->recipients->first()?->email,
                    'action_delivery' => (bool) ($item->action_delivery || in_array('delivery', $deliveryActions, true)),
                    'action_pick_up' => (bool) ($item->action_pick_up || in_array('pick_up', $deliveryActions, true)),
                    'action_drop_off' => (bool) ($item->action_drop_off || in_array('drop_off', $deliveryActions, true)),
                    'action_email' => (bool) ($item->action_email || in_array('email', $deliveryActions, true)),
                    'prepared_by_name' => $item->prepared_by_name,
                    'prepared_at' => optional($item->prepared_at)->format('Y-m-d H:i'),
                    'approved_by_name' => $item->approved_by_name,
                    'approved_position' => $item->approved_position ?? $item->operations_manager,
                    'approved_at' => optional($item->approved_at)->format('Y-m-d H:i'),
                    'document_custodian' => $item->document_custodian,
                    'delivered_by' => $item->delivered_by,
                    'received_by' => $item->received_by,
                    'receiver_affiliation' => $item->receiver_affiliation ?? $item->processing_company,
                    'received_at' => optional($item->received_at)->format('Y-m-d\TH:i'),
                    'attachments_count' => $item->attachments ? $item->attachments->count() : 0,
                    'items' => $item->workspaceItems ? $item->workspaceItems->map(function ($row) use ($item) {
                        TransmittalWorkflowBoundary::assertChild($item, $row);
                        if ($row->document !== null) {
                            TransmittalWorkflowBoundary::assertChild($item, $row->document);
                        }
                        return [
                            'id' => $row->id,
                            'document_id' => $row->document_id,
                            'line_number' => $row->line_number,
                            'no' => $row->line_number,
                            'particular' => $row->particular,
                            'unique_id' => $row->unique_id,
                            'quantity' => $row->quantity,
                            'qty' => $row->quantity,
                            'description' => $row->description,
                            'remarks' => $row->remarks,
                            'attachment_path' => $row->document?->original_name,
                            'attachment_url' => $row->document ? route('transmittals.documents.download', [$item, $row->document->id]) : null,
                        ];
                    })->values() : [],
                    'can_submit' => in_array($item->workflow_status ?? $item->status, ['Uploaded', 'Reverted', 'draft', 'rejected'], true),
                    'preview_url' => route('transmittals.workspace', $item->id),
                    'workspace_url' => route('transmittals.workspace', $item->id),
                    'receipts' => $receipts,
                    'receipt_id' => $primaryReceipt['id'] ?? null,
                    'receipt_no' => $primaryReceipt['receipt_no'] ?? null,
                    'receipt_url' => $primaryReceipt['url'] ?? null,
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

    public function submit($id): JsonResponse
    {
        $transmittal = TransmittalWorkflowBoundary::v2Query()->findOrFail($id);
        abort_unless($this->canEditDraft(request(), $transmittal), 404);

        if (! in_array($transmittal->workflow_status ?? $transmittal->status, ['Uploaded', 'Reverted', 'draft', 'rejected'], true)) {
            return response()->json(['message' => 'Only uploaded or reverted transmittals can be submitted.'], 422);
        }

        $transmittal->update([
            'workflow_status' => 'Submitted',
            'status' => 'pending_approval',
            'approval_status' => 'Pending',
            'submitted_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Transmittal submitted for approval successfully.']);
    }

    public function submitForApproval(Request $request, Transmittal $transmittal): RedirectResponse|JsonResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        abort_unless($this->canEditDraft($request, $transmittal), 404);

        if (! in_array($transmittal->status, ['draft', 'rejected'], true)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Only draft or reverted transmittals can be submitted for approval.'], 422);
            }
            return back()->withErrors(['status' => 'Only draft or reverted transmittals can be submitted for approval.']);
        }

        $transmittal->forceFill([
            'status' => 'pending_approval',
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'submitted_by' => $request->user()?->id ?? $transmittal->submitted_by,
        ])->save();

        $transmittal->histories()->create([
            'actor_id' => $request->user()?->id,
            'action' => 'submitted',
            'description' => 'Saved draft submitted to the administrator review queue.',
            'created_at' => now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Transmittal submitted for approval successfully.']);
        }

        return redirect()->route($this->indexRouteFor($request), ['filter' => 'submitted'])
            ->with('status', 'Transmittal submitted for administrator review.');
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->canApproveTransmittals($request->user()), 403);
        $activeTab = $request->query('status', 'submitted');
        $statusMap = [
            'submitted' => ['pending_approval'],
            'accepted' => ['approved', 'sent', 'acknowledged'],
            'reverted' => ['rejected'],
            'archived' => ['cancelled'],
        ];
        $workflowMap = [
            'submitted' => 'Submitted',
            'accepted' => 'Accepted',
            'reverted' => 'Reverted',
            'archived' => 'Archived',
        ];

        if (! isset($statusMap[$activeTab])) {
            $activeTab = 'submitted';
        }

        $counts = [];
        foreach ($statusMap as $key => $statuses) {
            $label = $workflowMap[$key];
            $counts[$key] = TransmittalWorkflowBoundary::v2Query()
                ->where(function ($q) use ($statuses, $label) {
                    $q->whereIn('status', $statuses)
                        ->orWhere(function ($sub) use ($label) {
                            $sub->whereNull('status')->where('workflow_status', $label);
                        });
                })
                ->count();
        }

        $statuses = $statusMap[$activeTab];
        $label = $workflowMap[$activeTab];
        $transmittals = TransmittalWorkflowBoundary::v2Query()->with(['recipients', 'preparer'])
            ->where(function ($q) use ($statuses, $label) {
                $q->whereIn('status', $statuses)
                    ->orWhere(function ($sub) use ($label) {
                        $sub->whereNull('status')->where('workflow_status', $label);
                    });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('transmittal.dashboard', compact('transmittals', 'activeTab', 'counts'));
    }

    public function dashboardApprove(Request $request, Transmittal $transmittal, TransmittalApprovalService $approval): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $this->authorize('transmittal-v2.approve', $transmittal);
        $approval->approve($transmittal, $request->user());

        return back()->with('success', 'Transmittal approved successfully.');
    }

    public function dashboardRevise(Request $request, Transmittal $transmittal, TransmittalApprovalService $approval): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $this->authorize('transmittal-v2.reject', $transmittal);
        $reason = $request->input('review_note') ?: $request->input('rejection_reason') ?: 'Please revise this transmittal.';
        $approval->reject($transmittal, $request->user(), $reason);

        return back()->with('success', 'Transmittal sent back for revision.');
    }

    public function dashboardReject(Request $request, Transmittal $transmittal, TransmittalApprovalService $approval): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $this->authorize('transmittal-v2.reject', $transmittal);
        $reason = $request->input('review_note') ?: $request->input('rejection_reason') ?: 'Transmittal rejected.';
        $approval->reject($transmittal, $request->user(), $reason);

        return back()->with('success', 'Transmittal rejected.');
    }

    public function preview($id): View
    {
        $transmittal = TransmittalWorkflowBoundary::v2Query()->findOrFail($id);
        abort_unless($this->canViewPrivateFiles(request(), $transmittal), 404);

        return $this->workspace(request(), $transmittal);
    }

    public function previewPdf($id): Response
    {
        $transmittal = TransmittalWorkflowBoundary::v2Query()->findOrFail($id);
        abort_unless($this->canViewPrivateFiles(request(), $transmittal), 404);

        return $this->pdf(request(), $transmittal);
    }

    public function sendEmail($id): RedirectResponse
    {
        $transmittal = TransmittalWorkflowBoundary::v2Query()->with(['workspaceItems.document', 'receipt', 'attachments', 'recipients'])->findOrFail($id);
        $this->authorize('transmittal-v2.transmit', $transmittal);
        abort(503, 'V2 email delivery is unavailable until durable outbox dispatch is implemented. No message was sent.');

        $recipient = trim((string) ($transmittal->recipient_email ?: $transmittal->recipients->first()?->email));
        if ($recipient === '') {
            return back()->with('error', 'Recipient email is required before sending the transmittal.');
        }

        $approvedByDisplay = $this->resolveApprovedByName($transmittal);
        $corporateContext = $this->transmittalCorporateContext();

        $pdf = Pdf::loadView('transmittals.pdf', [
                'transmittal' => $transmittal,
                'approvedByDisplay' => $approvedByDisplay,
                'corporateContext' => $corporateContext,
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

        $pdfFileName = 'transmittal-' . Str::slug((string) ($transmittal->transmittal_no ?? $transmittal->tracking_number), '-') . '.pdf';
        $pdfOutput = $pdf->output();

        $fileAttachments = [];
        $addedPaths = [];

        foreach ($transmittal->workspaceItems as $item) {
            $document = $item->document;
            if ($document !== null) {
                TransmittalWorkflowBoundary::assertChild($transmittal, $document);
                $this->verifyDocumentIntegrity($transmittal, $document);
                $path = Storage::disk('local')->path($document->storage_path);
                if (! in_array($path, $addedPaths, true)) {
                    $fileAttachments[] = ['path' => $path, 'name' => $document->original_name];
                    $addedPaths[] = $path;
                }
            }
        }

        if ($transmittal->attachments) {
            foreach ($transmittal->attachments as $attachment) {
                $attachmentPath = null;
                $attachmentName = $attachment->original_name ?: null;

                if (! empty($attachment->file_path) && Storage::disk('public')->exists($attachment->file_path)) {
                    $attachmentPath = Storage::disk('public')->path($attachment->file_path);
                    $attachmentName ??= basename($attachment->file_path);
                } elseif (! empty($attachment->storage_path) && Storage::disk('local')->exists($attachment->storage_path)) {
                    $attachmentPath = Storage::disk('local')->path($attachment->storage_path);
                    $attachmentName ??= basename($attachment->storage_path);
                }

                abort_unless(
                    is_string($attachmentPath) && is_file($attachmentPath) && is_readable($attachmentPath),
                    409,
                    'An associated attachment is missing or unreadable; delivery was stopped.'
                );

                if (! in_array($attachmentPath, $addedPaths, true)) {
                    $fileAttachments[] = [
                        'path' => $attachmentPath,
                        'name' => $attachmentName ?: basename($attachmentPath),
                    ];
                    $addedPaths[] = $attachmentPath;
                }
            }
        }

        $emailSubject = 'Transmittal Form ' . ($transmittal->transmittal_no ?? $transmittal->tracking_number);
        $htmlContent = view('emails.transmittal-sent', [
            'transmittal' => $transmittal,
            'corporateContext' => $corporateContext,
        ])->render();

        $brevoApiKey = config('transmittal_v2.brevo.api_key');
        $brevoSender = config('transmittal_v2.brevo.sender_email');

        if (filled($brevoApiKey) && filled($brevoSender)) {
            try {
                app(BrevoTransmittalService::class)->sendTransmittalDocument(
                    $recipient,
                    $emailSubject,
                    $htmlContent,
                    $pdfOutput,
                    $pdfFileName,
                    $fileAttachments,
                    $transmittal
                );

                return back()->with('success', 'Transmittal email sent successfully to ' . $recipient . ' via verified delivery.');
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors([
                    'email' => 'Brevo delivery could not be confirmed. Automatic fallback was stopped to avoid a duplicate transmission.',
                ]);
            }
        }

        Mail::send([], [], function ($message) use ($recipient, $transmittal, $emailSubject, $htmlContent, $pdfOutput, $pdfFileName, $fileAttachments) {
            $senderEmail = config('transmittal_v2.brevo.sender_email') ?: config('mail.from.address');
            $senderName = config('transmittal_v2.brevo.sender_name') ?: config('mail.from.name');

            $message->to($recipient)
                ->from($senderEmail, $senderName)
                ->replyTo($senderEmail, $senderName)
                ->subject($emailSubject)
                ->html($htmlContent)
                ->attachData($pdfOutput, $pdfFileName, ['mime' => 'application/pdf']);

            foreach ($fileAttachments as $attachment) {
                $message->attach($attachment['path'], ['as' => $attachment['name']]);
            }
        });

        return back()->with('success', 'Transmittal email sent successfully to ' . $recipient . '.');
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
            'logoUrl' => asset('images/FINAL_LOGO.jpg'),
            'logoBase64' => null,
        ];

        if (! Schema::hasTable('gis_records')) {
            return $fallback;
        }

        $buildCorporateGisQuery = function () {
            $query = DB::table('gis_records');

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
