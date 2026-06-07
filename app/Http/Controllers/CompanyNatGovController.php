<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Http\Controllers\Concerns\SyncsDeadlineTownHallMemo;
use App\Models\NatGov;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyNatGovController extends Controller
{
    use HandlesCorporateRepositoryRecords;
    use HandlesUploads;
    use ResolvesCompanyRecords;
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

    public function index(Request $request, int $company)
    {
        $companyData = $this->findCompany($request, $company);

        if ($request->expectsJson()) {
            $query = NatGov::query()->where('company_id', $company);
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

        return $this->page($company, $companyData);
    }

    private function page(int $company, array $companyData)
    {
        return view('corporate.natgov.index', [
            'company' => (object) $companyData,
            'companyDefaults' => $companyData,
            'defaultWorkflowTab' => 'accepted',
            'repositoryRoutes' => [
                'dataUrl' => route('company.natgov', $company),
                'storeUrl' => route('company.natgov.store', $company),
                'updateUrl' => route('company.natgov.update', ['company' => $company, 'record' => '__ID__']),
                'submitUrl' => route('company.natgov.submit', ['company' => $company, 'record' => '__ID__']),
            ],
            'agencyOptions' => self::AGENCY_OPTIONS,
            'statusOptions' => self::STATUS_OPTIONS,
            'renewalPeriodOptions' => self::RENEWAL_PERIOD_OPTIONS,
            'manualStatusOptions' => self::MANUAL_STATUS_OPTIONS,
            'canOverrideStatus' => $this->canOverrideNatGovStatus(),
        ]);
    }

    public function store(Request $request, int $company)
    {
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validatedPayload($request);
        $user = $this->currentUserLabel($request);

        [$documentPath, $draftDocuments] = $this->collectDraftDocuments($request);
        [$approvedDocumentPath, $approvedDocuments] = $this->collectApprovedDocuments($request);
        $primaryDocumentPath = $documentPath ?: $approvedDocumentPath;
        $primaryDocumentName = $this->resolveDocumentName($primaryDocumentPath, $draftDocuments, $approvedDocuments);

        $record = NatGov::create([
            'company_id' => $company,
            'company_name' => $companyData['company_name'],
            'client' => $companyData['company_name'],
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

        return response()->json([
            'message' => 'NatGov entry saved successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ], 201);
    }

    public function update(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $natgov = NatGov::query()->where('company_id', $company)->findOrFail($record);

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

        return response()->json([
            'message' => 'NatGov entry updated.',
            'data' => $this->transformRecord($natgov->fresh()),
        ]);
    }

    public function submitCompany(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $natgov = NatGov::query()->where('company_id', $company)->findOrFail($record);

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

    public function destroy(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $natgov = NatGov::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($natgov)) {
            abort(403, 'This record can no longer be deleted.');
        }

        $this->deleteDeadlineTownHallMemo($natgov);
        $natgov->delete();

        return response()->json(['message' => 'NatGov entry deleted successfully.']);
    }

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
            'uploads/company/natgov/drafts'
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
            'uploads/company/natgov/approved'
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
            'company' => $record->company_name ?: $record->client ?: 'Selected Company',
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

    private function recordLabel(NatGov $natgov): string
    {
        return trim(($natgov->agency ?: 'NatGov filing') . ' - ' . ($natgov->client ?: $natgov->registration_no ?: 'Untitled Record'), ' -');
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

    private function normalizeMultiValueField(array $selected, ?string $other, ?string $fallback = null): string
    {
        $values = collect($selected)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->reject(fn ($value) => strcasecmp($value, 'Other') === 0)
            ->values();

        $otherValue = trim((string) $other);
        if ($otherValue !== '') {
            $values->push($otherValue);
        }

        if ($values->isEmpty() && filled($fallback)) {
            return trim((string) $fallback);
        }

        return $values->unique()->implode(', ');
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, []);
    }
}
