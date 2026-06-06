<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\SyncsDeadlineTownHallMemo;
use App\Models\NatGov;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NatGovController extends Controller
{
    use GeneratesPdfPreview;
    use HandlesCorporateRepositoryRecords;
    use HandlesUploads;
    use SyncsDeadlineTownHallMemo;

    private const AGENCY_OPTIONS = [
        'Securities and Exchange Commission (SEC)',
        'Bureau of Internal Revenue (BIR)',
        'Social Security System (SSS)',
        'Philippine Health Insurance Corporation (PhilHealth)',
        'Home Development Mutual Fund (Pag-IBIG Fund)',
        'Department of Labor and Employment (DOLE)',
        'Philippine Economic Zone Authority (PEZA)',
        'Board of Investments (BOI)',
        'Department of Trade and Industry (DTI)',
        'Cooperative Development Authority (CDA)',
        'Intellectual Property Office of the Philippines (IPOPHL)',
        'Food and Drug Administration (FDA)',
        'National Telecommunications Commission (NTC)',
        'Department of Environment and Natural Resources (DENR)',
        'Environmental Management Bureau (EMB)',
        'Energy Regulatory Commission (ERC)',
        'Land Transportation Franchising and Regulatory Board (LTFRB)',
        'Maritime Industry Authority (MARINA)',
        'Civil Aviation Authority of the Philippines (CAAP)',
        'Department of Science and Technology (DOST)',
        'Technical Education and Skills Development Authority (TESDA)',
        'Commission on Higher Education (CHED)',
        'Bangko Sentral ng Pilipinas (BSP)',
        'Anti-Money Laundering Council (AMLC)',
        'National Economic and Development Authority (NEDA)',
        'National Commission on Indigenous Peoples (NCIP)',
        'Bureau of Customs (BOC)',
        'Other',
    ];

    private const STATUS_OPTIONS = [
        'Active',
        'For Renewal',
        'Expiring Soon',
        'Expired',
        'Pending',
        'Approved',
        'Suspended',
        'Cancelled',
        'Revoked',
    ];

    private const MANUAL_STATUS_OPTIONS = [
        'Pending',
        'Approved',
        'Suspended',
        'Cancelled',
        'Revoked',
    ];

    private const RENEWAL_PERIOD_OPTIONS = [
        '30_days' => 'Expiring in 30 Days',
        '60_days' => 'Expiring in 60 Days',
        '90_days' => 'Expiring in 90 Days',
        'expired' => 'Expired',
    ];

    private function canApproveCorporate(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('approve_corporate');
    }

    private function canOverrideNatGovStatus(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isAdmin() || $user->isSuperAdmin());
    }

    private function canEditRecord(NatGov $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true);
    }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $query = NatGov::query();
            $searchAgency = trim((string) $request->query('search_agency', ''));
            $statusFilter = trim((string) $request->query('status_filter', ''));
            $agencyFilter = trim((string) $request->query('agency_filter', ''));
            $renewalPeriod = trim((string) $request->query('renewal_period', ''));
            $sortBy = trim((string) $request->query('sort_by', ''));
            $sortDirection = strtolower(trim((string) $request->query('sort_direction', 'asc'))) === 'desc' ? 'desc' : 'asc';

            if (! $this->canApproveCorporate()) {
                $query->where('submitted_by', Auth::id());
            }

            if ($request->filled('workflow_status') && $request->workflow_status !== 'all') {
                $query->where('workflow_status', ucfirst($request->workflow_status));
            }

            $records = $query->get()
                ->when($searchAgency !== '', function ($items) use ($searchAgency) {
                    $needle = mb_strtolower($searchAgency);

                    return $items->filter(fn (NatGov $record) => str_contains(mb_strtolower((string) $record->agency), $needle)
                        || str_contains(mb_strtolower((string) $record->registration_no), $needle));
                })
                ->when($statusFilter !== '', function ($items) use ($statusFilter) {
                    return $items->filter(fn (NatGov $record) => $record->display_status === $statusFilter);
                })
                ->when($agencyFilter !== '', function ($items) use ($agencyFilter) {
                    return $items->filter(fn (NatGov $record) => str_contains(mb_strtolower((string) $record->agency), mb_strtolower($agencyFilter)));
                })
                ->when($renewalPeriod !== '', function ($items) use ($renewalPeriod) {
                    return $items->filter(fn (NatGov $record) => $this->matchesRenewalPeriod($record, $renewalPeriod));
                });

            if ($sortBy !== '') {
                $records = $records->sort(function (NatGov $left, NatGov $right) use ($sortBy, $sortDirection) {
                    $comparison = $this->compareNatGovs($left, $right, $sortBy);

                    return $sortDirection === 'desc' ? ($comparison * -1) : $comparison;
                });
            } else {
                $records = $records->sort(function (NatGov $left, NatGov $right) {
                    $leftRank = $this->defaultSortRankForNatGov($left);
                    $rightRank = $this->defaultSortRankForNatGov($right);

                    if ($leftRank !== $rightRank) {
                        return $leftRank <=> $rightRank;
                    }

                    $leftDate = optional($left->renewal_date ?? $left->deadline_date)?->timestamp ?? PHP_INT_MAX;
                    $rightDate = optional($right->renewal_date ?? $right->deadline_date)?->timestamp ?? PHP_INT_MAX;

                    if ($leftDate !== $rightDate) {
                        return $leftDate <=> $rightDate;
                    }

                    return $right->id <=> $left->id;
                });
            }

            return response()->json(
                $records->map(fn (NatGov $record) => $this->transformRecord($record))
                    ->values()
            );
        }

        return $this->page();
    }

    public function page()
    {
        return view('corporate.natgov.index', [
            'companyDefaults' => $this->latestCorporateCompany(),
            'defaultWorkflowTab' => 'accepted',
            'repositoryRoutes' => [
                'dataUrl' => route('natgov'),
                'storeUrl' => route('natgov.store'),
                'updateUrl' => route('natgov.update', '__ID__'),
                'submitUrl' => route('natgov.submit', '__ID__'),
            ],
            'agencyOptions' => self::AGENCY_OPTIONS,
            'statusOptions' => self::STATUS_OPTIONS,
            'renewalPeriodOptions' => self::RENEWAL_PERIOD_OPTIONS,
            'manualStatusOptions' => self::MANUAL_STATUS_OPTIONS,
            'canOverrideStatus' => $this->canOverrideNatGovStatus(),
        ]);
    }

    public function create()
    {
        return redirect()->route('natgov');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);
        $company = $this->latestCorporateCompany();
        $user = $this->currentUserLabel($request);

        [$documentPath, $draftDocuments] = $this->collectDraftDocuments($request);
        [$approvedDocumentPath, $approvedDocuments] = $this->collectApprovedDocuments($request);
        $primaryDocumentPath = $documentPath ?: $approvedDocumentPath;
        $primaryDocumentName = $this->resolveDocumentName($primaryDocumentPath, $draftDocuments, $approvedDocuments);

        $record = NatGov::create([
            'company_id' => $company['company_id'],
            'company_name' => $company['company_name'],
            'client' => $company['company_name'],
            'agency' => $this->normalizeMultiValueField(
                $request->input('agencies_selected', []),
                $request->input('agency_other', ''),
                $validated['agency'] ?? ''
            ),
            'registration_no' => $validated['registration_no'],
            'registration_status' => $validated['registration_status'] ?? null,
            'registration_date' => $validated['registration_date'] ?? null,
            'renewal_date' => $validated['renewal_date'] ?? null,
            'deadline_date' => $validated['renewal_date'] ?? null,
            'status_override' => $this->canOverrideNatGovStatus()
                ? $this->normalizeNatGovStatusOverride($validated['status_override'] ?? null)
                : null,
            'status' => $this->deriveNatGovSystemStatus([
                'renewal_date' => $validated['renewal_date'] ?? null,
            ]),
            'user' => $user,
            'uploaded_by' => $user,
            'date_uploaded' => now()->toDateString(),
            'date_uploaded_at' => now(),
            'document_path' => $primaryDocumentPath,
            'document_name' => $primaryDocumentName,
            'draft_documents' => $draftDocuments,
            'approved_document_path' => $approvedDocumentPath,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'workflow_status' => 'Uploaded',
            'approval_status' => 'Pending',
            'submitted_by' => Auth::id(),
            'approved_by' => null,
            'approved_at' => null,
            'review_note' => null,
            'notes_visible_to' => $validated['notes_visible_to'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncNatGovDeadlines($record);

        return $this->natGovResponse($request, $record, 'NatGov entry saved successfully.', 201);
    }

    public function show(Request $request, NatGov $natgov)
    {
        if (! $this->canApproveCorporate() && (int) $natgov->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if ($request->expectsJson()) {
            return response()->json($this->transformRecord($natgov));
        }

        $generatedDraftPath = $this->generatePdfPreview(
            'corporate.natgov.pdf',
            ['natgov' => $natgov],
            'generated-previews/natgov/' . ($natgov->registration_no ?: $natgov->id) . '-draft.pdf'
        );

        $uploadUrl = function (?string $path): ?string {
            if (! $path || ! Storage::disk('public')->exists($path)) {
                return null;
            }

            $segments = array_map('rawurlencode', array_values(array_filter(explode('/', trim($path, '/')), fn ($segment) => $segment !== '')));

            return url('/uploads/' . implode('/', $segments));
        };

        $draftDocuments = collect($natgov->draft_documents ?? [])->filter(fn ($entry) => ! empty($entry['path']) && Storage::disk('public')->exists($entry['path']))->values();
        $approvedDocuments = collect($natgov->approved_documents ?? [])->filter(fn ($entry) => ! empty($entry['path']) && Storage::disk('public')->exists($entry['path']))->values();
        $generatedDraftUrl = $generatedDraftPath && Storage::disk('public')->exists($generatedDraftPath)
            ? route('uploads.show', ['path' => $generatedDraftPath])
            : null;
        $generatedDraftOption = $generatedDraftUrl ? [[
            'url' => $generatedDraftUrl,
            'label' => 'Template Draft',
            'uploaded_at' => 'Initial generated template',
        ]] : [];
        $draftOptions = $draftDocuments->map(function ($entry, $index) use ($uploadUrl) {
            return [
                'url' => $uploadUrl($entry['path']),
                'label' => $entry['name'] ?? ('Draft Revision ' . ($index + 1)),
                'uploaded_at' => $entry['uploaded_at'] ?? null,
            ];
        })->values()->all();
        $draftOptions = array_merge($generatedDraftOption, $draftOptions);

        $draftUrl = $uploadUrl($natgov->document_path) ?: ($generatedDraftPath && Storage::disk('public')->exists($generatedDraftPath) ? route('uploads.show', ['path' => $generatedDraftPath]) : null);
        $approvedUrl = $uploadUrl($natgov->approved_document_path);

        return view('corporate.natgov.preview', [
            'natgov' => $natgov,
            'generatedDraftUrl' => $generatedDraftUrl,
            'draftUrl' => $draftUrl,
            'approvedUrl' => $approvedUrl,
            'draftDocuments' => $draftDocuments,
            'approvedDocuments' => $approvedDocuments,
            'draftOptions' => $draftOptions,
            'selectedDraftUrl' => $draftUrl ?: ($generatedDraftUrl ?: (! empty($draftOptions) ? $draftOptions[array_key_last($draftOptions)]['url'] : null)),
            'latestDraft' => $draftDocuments->last() ?: ($generatedDraftUrl ? [
                'name' => 'Template Draft',
                'path' => $generatedDraftPath,
                'uploaded_at' => 'Initial generated template',
            ] : null),
            'latestApproved' => $approvedDocuments->last(),
            'visibleAuthorityNotes' => $this->visibleAuthorityNotes($natgov),
            'backRoute' => route('natgov', [
                'record' => $natgov->id,
                'tab' => strtolower((string) ($natgov->workflow_status ?? 'uploaded')),
            ]),
            'editRoute' => route('natgov.edit', $natgov),
            'deleteRoute' => route('natgov.destroy', $natgov),
            'updateRoute' => route('natgov.update', $natgov),
        ]);
    }

    public function edit(NatGov $natgov)
    {
        return redirect()->route('natgov.preview', $natgov);
    }

    public function update(Request $request, NatGov $natgov)
    {
        if (! $this->canEditRecord($natgov)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request);
        $user = $this->currentUserLabel($request);

        [$documentPath, $draftDocuments] = $this->collectDraftDocuments($request, $natgov);
        [$approvedDocumentPath, $approvedDocuments] = $this->collectApprovedDocuments($request, $natgov);
        $primaryDocumentPath = $documentPath ?: $approvedDocumentPath ?: $natgov->document_path;
        $primaryDocumentName = $this->resolveDocumentName($primaryDocumentPath, $draftDocuments, $approvedDocuments) ?: $natgov->document_name;

        $natgov->update([
            'agency' => $this->normalizeMultiValueField(
                $request->input('agencies_selected', []),
                $request->input('agency_other', ''),
                $validated['agency'] ?? $natgov->agency
            ),
            'registration_no' => $validated['registration_no'],
            'registration_status' => $validated['registration_status'] ?? $natgov->registration_status,
            'registration_date' => $validated['registration_date'] ?? null,
            'renewal_date' => $validated['renewal_date'] ?? null,
            'deadline_date' => $validated['renewal_date'] ?? null,
            'status_override' => $this->canOverrideNatGovStatus()
                ? $this->normalizeNatGovStatusOverride($validated['status_override'] ?? null, $natgov->status_override)
                : $natgov->status_override,
            'status' => $this->deriveNatGovSystemStatus([
                'renewal_date' => $validated['renewal_date'] ?? null,
            ]),
            'document_path' => $primaryDocumentPath,
            'document_name' => $primaryDocumentName,
            'draft_documents' => $draftDocuments,
            'approved_document_path' => $approvedDocumentPath,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'approval_status' => ($natgov->workflow_status ?? 'Uploaded') === 'Reverted' ? 'Pending' : $natgov->approval_status,
            'review_note' => ($natgov->workflow_status ?? 'Uploaded') === 'Reverted' ? null : $natgov->review_note,
            'notes_visible_to' => $validated['notes_visible_to'] ?? $natgov->notes_visible_to,
            'notes' => $validated['notes'] ?? $natgov->notes,
        ]);

        $natgov->refresh();
        $this->syncNatGovDeadlines($natgov);

        return $this->natGovResponse($request, $natgov, 'NatGov entry updated.');
    }

    public function submit(NatGov $natgov)
    {
        if ((int) $natgov->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (! in_array($natgov->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true)) {
            return response()->json(['message' => 'Only uploaded or reverted records can be submitted.'], 422);
        }

        $natgov->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
            'last_updated_by' => Auth::user()?->name ?? Auth::user()?->email ?? 'System User',
            'last_updated_at' => now(),
        ]);

        $this->notifyCorporateApproversOfSubmission($natgov->fresh(), 'natgov');

        return response()->json([
            'message' => 'NatGov entry submitted for approval successfully.',
            'data' => $this->transformRecord($natgov->fresh()),
        ]);
    }

    public function approve(NatGov $natgov)
    {
        abort_unless($this->canOverrideNatGovStatus(), 403);

        $natgov->forceFill([
            'status_override' => 'Approved',
        ])->save();

        return redirect()
            ->route('natgov.preview', $natgov)
            ->with('success', 'NatGov record approved successfully.');
    }

    public function destroy(NatGov $natgov)
    {
        if (! $this->canEditRecord($natgov)) {
            abort(403, 'This record can no longer be deleted.');
        }

        $this->deleteDeadlineTownHallMemo($natgov);
        $natgov->delete();

        return redirect()->route('natgov')->with('success', 'NatGov entry deleted.');
    }

    public function storeAuthorityNote(Request $request, NatGov $natgov)
    {
        $data = $request->validate([
            'visible_to_role' => ['required', 'string', 'in:Admin,Employee'],
            'body' => ['required', 'string'],
        ]);

        $natgov->authorityNotes()->create([
            'user_id' => auth()->id(),
            'visible_to_role' => $data['visible_to_role'],
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('natgov.preview', $natgov)
            ->with('success', 'Authority note added.');
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'agency' => ['nullable', 'string', 'max:2000'],
            'agencies_selected' => ['nullable', 'array'],
            'agencies_selected.*' => ['string'],
            'agency_other' => ['nullable', 'string', 'max:255'],
            'registration_no' => ['required', 'string', 'max:255'],
            'registration_status' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'status_override' => ['nullable', 'string', 'max:255'],
            'draft_documents' => ['nullable'],
            'draft_documents.*' => ['file', 'mimes:pdf', 'max:5120'],
            'approved_documents' => ['nullable'],
            'approved_documents.*' => ['file', 'mimes:pdf', 'max:5120'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'document_paths' => ['nullable', 'array'],
            'document_paths.*' => ['file', 'mimes:pdf', 'max:5120'],
            'approved_document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'approved_document_paths' => ['nullable', 'array'],
            'approved_document_paths.*' => ['file', 'mimes:pdf', 'max:5120'],
            'notes_visible_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function collectDraftDocuments(Request $request, ?NatGov $existing = null): array
    {
        return $this->collectDocumentSet(
            $request,
            'draft_documents',
            'document_path',
            'document_paths',
            $existing?->draft_documents ?? [],
            $existing?->document_path,
            'uploads/natgov/drafts'
        );
    }

    private function collectApprovedDocuments(Request $request, ?NatGov $existing = null): array
    {
        return $this->collectDocumentSet(
            $request,
            'approved_documents',
            'approved_document_path',
            'approved_document_paths',
            $existing?->approved_documents ?? [],
            $existing?->approved_document_path,
            'uploads/natgov/approved'
        );
    }

    private function collectDocumentSet(
        Request $request,
        string $repositoryField,
        string $legacySingleField,
        string $legacyMultiField,
        array $existingDocuments,
        ?string $existingPath,
        string $directory
    ): array {
        $documents = $existingDocuments;
        $primaryPath = $existingPath;

        if ($request->hasFile($repositoryField)) {
            $incomingDocuments = $this->storeDocumentSet($request, $repositoryField, $directory);
            $documents = $this->appendDocuments($documents, $incomingDocuments);
            $latestIncoming = collect($incomingDocuments)->last();
            $primaryPath = $latestIncoming['path'] ?? $primaryPath;
        }

        if ($request->hasFile($legacySingleField) || $request->hasFile($legacyMultiField)) {
            [$primaryPath, $documents] = $this->appendUploadedFiles(
                $request,
                $legacySingleField,
                $legacyMultiField,
                $documents,
                $primaryPath,
                $directory
            );
        }

        if (! $primaryPath) {
            $primaryPath = collect($documents)->last()['path'] ?? null;
        }

        return [$primaryPath, array_values($documents)];
    }

    private function resolveDocumentName(?string $primaryPath, array $draftDocuments, array $approvedDocuments): ?string
    {
        $documents = collect($draftDocuments)->merge($approvedDocuments);
        $match = $documents->first(fn ($document) => ($document['path'] ?? null) === $primaryPath);

        return $match['name'] ?? ($primaryPath ? basename($primaryPath) : null);
    }

    private function transformRecord(NatGov $record): array
    {
        $draftDocuments = $this->documentLinks($record->draft_documents);
        $approvedDocuments = $this->documentLinks($record->approved_documents);

        return [
            'id' => $record->id,
            'company' => $record->company_name ?: $record->client ?: 'Latest Approved GIS Company',
            'agency' => $record->agency,
            'registration_number' => $record->registration_no,
            'registration_date' => optional($record->registration_date)->format('Y-m-d'),
            'renewal_date' => optional($record->renewal_date ?? $record->deadline_date)->format('Y-m-d'),
            'status' => $record->display_status,
            'manual_status_override' => $record->status_override,
            'uploaded_by' => $record->uploaded_by ?: $record->user,
            'date_uploaded' => $record->date_uploaded_at?->format('Y-m-d H:i:s') ?: optional($record->date_uploaded)->format('Y-m-d'),
            'last_updated_by' => $record->last_updated_by,
            'last_updated_date' => $record->last_updated_at?->format('Y-m-d H:i:s') ?: $record->updated_at?->format('Y-m-d H:i:s'),
            'workflow_status' => $record->workflow_status ?? 'Uploaded',
            'approval_status' => $record->approval_status ?? 'Pending',
            'review_note' => $record->review_note,
            'document_name' => $record->document_name,
            'document_url' => $this->publicDocumentUrl($record->document_path)
                ?: ($draftDocuments[0]['url'] ?? null)
                ?: ($approvedDocuments[0]['url'] ?? null),
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'can_edit' => $this->canEditRecord($record),
            'can_submit' => (int) $record->submitted_by === (int) Auth::id()
                && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true),
        ];
    }

    private function syncNatGovDeadlines(NatGov $record): void
    {
        $date = $record->renewal_date?->toDateString() ?: $record->deadline_date?->toDateString();

        $this->syncDeadlineTownHallMemo(
            $record,
            $date,
            'NatGov',
            $this->recordLabel($record),
            'natgov.preview'
        );

        $this->syncNatGovReminderSeries(
            $record,
            $date,
            'NatGov',
            $this->recordLabel($record),
            'natgov.preview'
        );
    }

    private function visibleAuthorityNotes(NatGov $natgov)
    {
        $role = auth()->user()?->role;

        return $natgov->authorityNotes()
            ->with('user:id,name,role')
            ->when($role && $role !== 'SuperAdmin', function ($query) use ($role) {
                $query->where('visible_to_role', $role);
            })
            ->get();
    }

    private function recordLabel(NatGov $natgov): string
    {
        return trim(($natgov->agency ?: 'NatGov filing') . ' - ' . ($natgov->client ?: $natgov->registration_no ?: 'Untitled Record'), ' -');
    }

    private function natGovResponse(Request $request, NatGov $natgov, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $this->transformRecord($natgov->fresh()),
            ], $status);
        }

        if (trim((string) $request->input('redirect_to', '')) === 'preview') {
            return redirect()->route('natgov.preview', $natgov)->with('success', $message);
        }

        return redirect()->route('natgov')->with('success', $message);
    }

    private function matchesRenewalPeriod(NatGov $item, string $renewalPeriod): bool
    {
        $renewalDate = $item->renewal_date ?? $item->deadline_date;
        if (! $renewalDate) {
            return false;
        }

        $today = now()->startOfDay();
        $days = $today->diffInDays($renewalDate->copy()->startOfDay(), false);

        return match ($renewalPeriod) {
            '30_days' => $days >= 0 && $days <= 30,
            '60_days' => $days >= 0 && $days <= 60,
            '90_days' => $days >= 0 && $days <= 90,
            'expired' => $days < 0,
            default => true,
        };
    }

    private function compareNatGovs(NatGov $left, NatGov $right, string $sortBy): int
    {
        return match ($sortBy) {
            'status' => strcmp($left->display_status, $right->display_status),
            'registration_date' => (optional($left->registration_date)?->timestamp ?? 0) <=> (optional($right->registration_date)?->timestamp ?? 0),
            'renewal_date' => (optional($left->renewal_date ?? $left->deadline_date)?->timestamp ?? 0) <=> (optional($right->renewal_date ?? $right->deadline_date)?->timestamp ?? 0),
            'company' => strcmp(mb_strtolower(trim((string) ($left->company_name ?: $left->client))), mb_strtolower(trim((string) ($right->company_name ?: $right->client)))),
            'government_agency' => strcmp(mb_strtolower(trim((string) $left->agency)), mb_strtolower(trim((string) $right->agency))),
            default => strcmp(
                mb_strtolower(trim((string) data_get($left, $sortBy, ''))),
                mb_strtolower(trim((string) data_get($right, $sortBy, '')))
            ),
        };
    }

    private function defaultSortRankForNatGov(NatGov $item): int
    {
        return match ($item->display_status) {
            'Expired' => 0,
            'Expiring Soon' => 1,
            'For Renewal' => 2,
            'Pending' => 3,
            'Active' => 4,
            'Approved' => 5,
            'Suspended' => 6,
            'Cancelled' => 7,
            'Revoked' => 8,
            default => 9,
        };
    }

    private function normalizeNatGovStatusOverride(mixed $statusOverride, ?string $fallback = null): ?string
    {
        $value = trim((string) $statusOverride);

        if ($value === '') {
            return null;
        }

        return in_array($value, self::MANUAL_STATUS_OPTIONS, true) ? $value : ($fallback ?: null);
    }

    private function deriveNatGovSystemStatus(array $data): string
    {
        $renewalDateValue = $data['renewal_date'] ?? null;
        $renewalDate = $renewalDateValue ? \Carbon\Carbon::parse($renewalDateValue)->startOfDay() : null;

        if (! $renewalDate) {
            return 'Pending';
        }

        $today = now()->startOfDay();

        if ($renewalDate->lt($today)) {
            return 'Expired';
        }

        $daysUntilRenewal = $today->diffInDays($renewalDate->copy()->startOfDay(), false);

        if ($daysUntilRenewal <= 30) {
            return 'Expiring Soon';
        }

        if ($daysUntilRenewal <= 90) {
            return 'For Renewal';
        }

        return 'Active';
    }

    private function normalizeMultiValueField(array|string|null $selectedValues, ?string $otherValue, ?string $fallbackValue = ''): string
    {
        $values = [];

        if (is_array($selectedValues)) {
            $values = collect($selectedValues)
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->reject(fn ($value) => $value === 'Other')
                ->values()
                ->all();
        } else {
            $raw = trim((string) $selectedValues);
            if ($raw !== '') {
                $values = collect(explode(',', $raw))
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        $other = trim((string) $otherValue);
        if ($other !== '') {
            $values[] = $other;
        }

        if (empty($values)) {
            $values = collect(explode(',', trim((string) $fallbackValue)))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        return collect($values)->unique()->implode(', ');
    }
}
