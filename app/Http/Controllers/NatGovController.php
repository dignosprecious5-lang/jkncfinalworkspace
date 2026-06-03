<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\SyncsDeadlineTownHallMemo;
use App\Models\GisRecord;
use App\Models\NatGov;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class NatGovController extends Controller
{
    use GeneratesPdfPreview;
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

    private const SORTABLE_COLUMNS = [
        'client',
        'agency',
        'registration_no',
        'registration_date',
        'renewal_date',
        'status',
    ];

    public function index(Request $request)
    {
        $searchAgency = trim((string) $request->query('search_agency', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $agencyFilter = trim((string) $request->query('agency', ''));
        $renewalPeriod = trim((string) $request->query('renewal_period', ''));
        $sortBy = trim((string) $request->query('sort_by', ''));
        $sortDirection = strtolower(trim((string) $request->query('sort_direction', 'asc'))) === 'desc' ? 'desc' : 'asc';

        $natgovs = Schema::hasTable('nat_govs')
            ? NatGov::query()->get()
            : collect();

        $natgovs = $natgovs
            ->when($searchAgency !== '', function (Collection $items) use ($searchAgency) {
                $term = mb_strtolower($searchAgency);

                return $items->filter(function (NatGov $item) use ($term) {
                    return str_contains(mb_strtolower($item->agency ?? ''), $term)
                        || str_contains(mb_strtolower($item->registration_no ?? ''), $term);
                });
            })
            ->when($statusFilter !== '', function (Collection $items) use ($statusFilter) {
                return $items->filter(fn (NatGov $item) => $item->display_status === $statusFilter);
            })
            ->when($agencyFilter !== '', function (Collection $items) use ($agencyFilter) {
                $term = mb_strtolower($agencyFilter);

                return $items->filter(fn (NatGov $item) => str_contains(mb_strtolower($item->agency ?? ''), $term));
            })
            ->when($renewalPeriod !== '', function (Collection $items) use ($renewalPeriod) {
                return $items->filter(fn (NatGov $item) => $this->matchesRenewalPeriod($item, $renewalPeriod));
            });

        if (in_array($sortBy, self::SORTABLE_COLUMNS, true)) {
            $natgovs = $natgovs->sort(function (NatGov $left, NatGov $right) use ($sortBy, $sortDirection) {
                $comparison = $this->compareNatGovs($left, $right, $sortBy);

                return $sortDirection === 'desc' ? ($comparison * -1) : $comparison;
            })->values();
        } else {
            $natgovs = $natgovs->sort(function (NatGov $left, NatGov $right) {
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
            })->values();
        }

        return view('corporate.natgov.index', [
            'natgovs' => $natgovs,
            'companyDefaults' => $this->companyDefaults(),
            'agencyOptions' => self::AGENCY_OPTIONS,
            'statusOptions' => self::STATUS_OPTIONS,
            'renewalPeriodOptions' => self::RENEWAL_PERIOD_OPTIONS,
            'searchAgency' => $searchAgency,
            'statusFilter' => $statusFilter,
            'agencyFilter' => $agencyFilter,
            'renewalPeriodFilter' => $renewalPeriod,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        $item = new NatGov($this->companyDefaults());
        $item->setAttribute('status', $item->derived_status);

        return view('corporate.common.form', [
            'title' => 'Add NatGov',
            'action' => route('natgov.store'),
            'method' => 'POST',
            'cancelRoute' => route('natgov'),
            'fields' => $this->fields(),
            'item' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data = $this->normalizePersistedData($request, $data);

        [$data['document_path'], $data['draft_documents']] = $this->appendUploadedFiles(
            $request,
            'document_path',
            'document_paths',
            [],
            null,
            'uploads/natgov/drafts'
        );
        if (Schema::hasColumn('nat_govs', 'approved_document_path')) {
            [$data['approved_document_path'], $data['approved_documents']] = $this->appendUploadedFiles(
                $request,
                'approved_document_path',
                'approved_document_paths',
                [],
                null,
                'uploads/natgov/approved'
            );
        }

        $natgov = NatGov::create($this->filterPersistableData($data));
        $this->syncDeadlineTownHallMemo(
            $natgov,
            $natgov->renewal_date?->toDateString() ?: $natgov->deadline_date?->toDateString(),
            'NatGov',
            $this->recordLabel($natgov),
            'natgov.preview'
        );
        $this->syncNatGovReminderSeries(
            $natgov,
            $natgov->renewal_date?->toDateString() ?: $natgov->deadline_date?->toDateString(),
            'NatGov',
            $this->recordLabel($natgov),
            'natgov.preview'
        );

        return redirect()->route('natgov')->with('success', 'NatGov entry created.');
    }

    public function show(NatGov $natgov)
    {
        $generatedDraftPath = $this->generatePdfPreview(
            'corporate.natgov.pdf',
            ['natgov' => $natgov],
            'generated-previews/natgov/' . ($natgov->registration_no ?: $natgov->id) . '-draft.pdf'
        );

        $uploadUrl = function (?string $path): ?string {
            if (!$path || !Storage::disk('public')->exists($path)) {
                return null;
            }

            $segments = array_map('rawurlencode', array_values(array_filter(explode('/', trim($path, '/')), fn ($segment) => $segment !== '')));

            return url('/uploads/' . implode('/', $segments));
        };

        $draftDocuments = collect($natgov->draft_documents ?? [])->filter(fn ($entry) => !empty($entry['path']) && Storage::disk('public')->exists($entry['path']))->values();
        $approvedDocuments = collect($natgov->approved_documents ?? [])->filter(fn ($entry) => !empty($entry['path']) && Storage::disk('public')->exists($entry['path']))->values();
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
            'selectedDraftUrl' => $draftUrl ?: ($generatedDraftUrl ?: (!empty($draftOptions) ? $draftOptions[array_key_last($draftOptions)]['url'] : null)),
            'latestDraft' => $draftDocuments->last() ?: ($generatedDraftUrl ? [
                'name' => 'Template Draft',
                'path' => $generatedDraftPath,
                'uploaded_at' => 'Initial generated template',
            ] : null),
            'latestApproved' => $approvedDocuments->last(),
            'visibleAuthorityNotes' => $this->visibleAuthorityNotes($natgov),
            'backRoute' => route('natgov'),
            'editRoute' => route('natgov.edit', $natgov),
            'deleteRoute' => route('natgov.destroy', $natgov),
            'updateRoute' => route('natgov.update', $natgov),
        ]);
    }

    public function edit(NatGov $natgov)
    {
        $item = clone $natgov;
        $item->setAttribute('status', $natgov->derived_status);

        return view('corporate.common.form', [
            'title' => 'Edit NatGov',
            'action' => route('natgov.update', $natgov),
            'method' => 'PUT',
            'cancelRoute' => route('natgov'),
            'fields' => $this->fields(),
            'item' => $item,
        ]);
    }

    public function update(Request $request, NatGov $natgov)
    {
        $data = $this->validateData($request);
        $data = $this->normalizePersistedData($request, $data, $natgov);

        [$data['document_path'], $data['draft_documents']] = $this->appendUploadedFiles(
            $request,
            'document_path',
            'document_paths',
            $natgov->draft_documents ?? [],
            $natgov->document_path,
            'uploads/natgov/drafts'
        );
        $data['document_path'] = $natgov->document_path ?: $data['document_path'];
        if (Schema::hasColumn('nat_govs', 'approved_document_path')) {
            [$data['approved_document_path'], $data['approved_documents']] = $this->appendUploadedFiles(
                $request,
                'approved_document_path',
                'approved_document_paths',
                $natgov->approved_documents ?? [],
                $natgov->approved_document_path,
                'uploads/natgov/approved'
            );
        }

        $natgov->update($this->filterPersistableData($data));
        $natgov->refresh();
        $this->syncDeadlineTownHallMemo(
            $natgov,
            $natgov->renewal_date?->toDateString() ?: $natgov->deadline_date?->toDateString(),
            'NatGov',
            $this->recordLabel($natgov),
            'natgov.preview'
        );
        $this->syncNatGovReminderSeries(
            $natgov,
            $natgov->renewal_date?->toDateString() ?: $natgov->deadline_date?->toDateString(),
            'NatGov',
            $this->recordLabel($natgov),
            'natgov.preview'
        );

        return $this->natGovRedirectResponse($request, $natgov, 'NatGov entry updated.');
    }

    public function approve(NatGov $natgov)
    {
        abort_unless($this->canOverrideNatGovStatus(), 403);

        $updates = Schema::hasColumn('nat_govs', 'status_override')
            ? ['status_override' => 'Approved']
            : ['status' => 'Approved'];

        $natgov->forceFill($updates)->save();
        $natgov->refresh();

        return redirect()
            ->route('natgov.preview', $natgov)
            ->with('success', 'NatGov record approved successfully.');
    }

    public function destroy(NatGov $natgov)
    {
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

    private function fields(): array
    {
        $canOverrideStatus = $this->canOverrideNatGovStatus();

        return [
            ['name' => 'client', 'label' => 'Company', 'type' => 'text'],
            ['name' => 'agency', 'label' => 'Government Agency', 'type' => 'textarea'],
            ['name' => 'registration_no', 'label' => 'Registration Number', 'type' => 'text'],
            ['name' => 'registration_status', 'label' => 'Registration Status', 'type' => 'text'],
            ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date'],
            ['name' => 'renewal_date', 'label' => 'Renewal Date', 'type' => 'date'],
            ['name' => 'status', 'label' => 'Status (Auto)', 'type' => 'text', 'disabled' => true],
            ...($canOverrideStatus ? [[
                'name' => 'status_override',
                'label' => 'Admin Status Override',
                'type' => 'select',
                'options' => array_merge(['' => 'Auto / No Override'], array_combine(self::MANUAL_STATUS_OPTIONS, self::MANUAL_STATUS_OPTIONS) ?: []),
            ]] : []),
            ['name' => 'document_path', 'label' => 'Upload Draft NatGov Document (PDF)', 'type' => 'file'],
            ['name' => 'approved_document_path', 'label' => 'Upload Approved NatGov Document (PDF)', 'type' => 'file'],
            ['name' => 'notes_visible_to', 'label' => 'Notes Visible To Authority', 'type' => 'text'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'client' => ['nullable', 'string', 'max:255'],
            'agency' => ['nullable', 'string', 'max:1000'],
            'agencies_selected' => ['nullable', 'array'],
            'agencies_selected.*' => ['string'],
            'agency_other' => ['nullable', 'string', 'max:255'],
            'registration_status' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'deadline_date' => ['nullable', 'date'],
            'registration_no' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'status_override' => ['nullable', 'string', 'max:255'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'date_uploaded' => ['nullable', 'date'],
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

    private function companyDefaults(): array
    {
        $gis = $this->latestSubmittedGis();

        if ($gis) {
            return [
                'client' => $gis->corporation_name ?: 'JK&C Group of Companies',
            ];
        }

        return [
            'client' => 'JK&C Group of Companies',
        ];
    }

    private function latestSubmittedGis(): ?GisRecord
    {
        if (!Schema::hasTable('gis_records')) {
            return null;
        }

        $submitted = GisRecord::query()
            ->where(function ($query) {
                $query->whereNotNull('submitted_by')
                    ->orWhereIn('workflow_status', ['Submitted', 'Accepted', 'Approved'])
                    ->orWhereIn('approval_status', ['Pending', 'Approved'])
                    ->orWhereIn('submission_status', ['Submitted', 'Approved']);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        return $submitted ?: GisRecord::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
    }

    private function normalizePersistedData(Request $request, array $data, ?NatGov $existing = null): array
    {
        $defaults = $this->companyDefaults();
        $canOverrideStatus = $this->canOverrideNatGovStatus();

        $data['client'] = trim((string) (($data['client'] ?? '') ?: ($existing?->client ?: ($defaults['client'] ?? ''))));
        $data['agency'] = $this->normalizeMultiValueField(
            $request->input('agencies_selected', []),
            $request->input('agency_other', ''),
            ($data['agency'] ?? '') ?: ($existing?->agency ?? '')
        );
        $data['registration_no'] = trim((string) (($data['registration_no'] ?? '') ?: ($existing?->registration_no ?? '')));
        $data['registration_status'] = trim((string) (($data['registration_status'] ?? '') ?: ($existing?->registration_status ?? '')));
        $data['renewal_date'] = $data['renewal_date'] ?? $data['deadline_date'] ?? $existing?->renewal_date?->toDateString() ?? $existing?->deadline_date?->toDateString();
        $data['deadline_date'] = $data['renewal_date'];
        $data['status_override'] = $canOverrideStatus
            ? $this->normalizeNatGovStatusOverride($data['status_override'] ?? null, $existing?->status_override)
            : ($existing?->status_override ?: null);
        $data['status'] = $this->deriveNatGovSystemStatus($data, $existing);
        $data['uploaded_by'] = trim((string) (($data['uploaded_by'] ?? '') ?: (auth()->user()?->name ?: 'System User')));
        $data['date_uploaded'] = $data['date_uploaded'] ?? $existing?->date_uploaded?->toDateString() ?? now()->toDateString();

        return $data;
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

    private function filterPersistableData(array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $key) => Schema::hasColumn('nat_govs', $key))
            ->all();
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

    private function natGovRedirectResponse(Request $request, NatGov $natgov, string $message)
    {
        if (trim((string) $request->input('redirect_to', '')) === 'preview') {
            return redirect()->route('natgov.preview', $natgov)->with('success', $message);
        }

        return redirect()->route('natgov')->with('success', $message);
    }

    private function matchesRenewalPeriod(NatGov $item, string $renewalPeriod): bool
    {
        $renewalDate = $item->renewal_date ?? $item->deadline_date;
        if (!$renewalDate) {
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
        if ($sortBy === 'status') {
            return strcmp($left->display_status, $right->display_status);
        }

        if (in_array($sortBy, ['registration_date', 'renewal_date'], true)) {
            $leftValue = optional($sortBy === 'renewal_date' ? ($left->renewal_date ?? $left->deadline_date) : $left->registration_date)?->timestamp ?? 0;
            $rightValue = optional($sortBy === 'renewal_date' ? ($right->renewal_date ?? $right->deadline_date) : $right->registration_date)?->timestamp ?? 0;

            return $leftValue <=> $rightValue;
        }

        return strcmp(
            mb_strtolower(trim((string) data_get($left, $sortBy, ''))),
            mb_strtolower(trim((string) data_get($right, $sortBy, '')))
        );
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

    private function canOverrideNatGovStatus(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isAdmin() || $user->isSuperAdmin());
    }

    private function normalizeNatGovStatusOverride(mixed $statusOverride, ?string $fallback = null): ?string
    {
        $value = trim((string) $statusOverride);

        if ($value === '') {
            return null;
        }

        return in_array($value, self::MANUAL_STATUS_OPTIONS, true) ? $value : ($fallback ?: null);
    }

    private function deriveNatGovSystemStatus(array $data, ?NatGov $existing = null): string
    {
        $renewalDateValue = $data['renewal_date'] ?? $data['deadline_date'] ?? $existing?->renewal_date?->toDateString() ?? $existing?->deadline_date?->toDateString();
        $renewalDate = $renewalDateValue ? \Carbon\Carbon::parse($renewalDateValue)->startOfDay() : null;

        if (!$renewalDate) {
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
}
