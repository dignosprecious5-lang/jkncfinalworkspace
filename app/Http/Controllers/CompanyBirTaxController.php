<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Http\Controllers\Concerns\SyncsDeadlineTownHallMemo;
use App\Models\BirTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyBirTaxController extends Controller
{
    use HandlesCorporateRepositoryRecords;
    use HandlesUploads;
    use ResolvesCompanyRecords;
    use SyncsDeadlineTownHallMemo;

    private const TAX_TYPE_OPTIONS = [
        'Income Tax',
        'Value Added Tax (VAT)',
        'Percentage Tax',
        'Withholding Tax on Compensation',
        'Expanded Withholding Tax (EWT)',
        'Final Withholding Tax (FWT)',
        'Documentary Stamp Tax (DST)',
        'Capital Gains Tax',
        "Donor's Tax",
        'Estate Tax',
        'Excise Tax',
        'Stock Transaction Tax',
        'Fringe Benefits Tax',
        'Improperly Accumulated Earnings Tax (IAET)',
        'Minimum Corporate Income Tax (MCIT)',
        'Branch Profit Remittance Tax',
        'Tax on Government Money Payments',
        'Local Business Tax',
        'Real Property Tax',
        'Other',
    ];

    private const FORM_TYPE_OPTIONS = [
        '1700',
        '1701',
        '1701A',
        '1701Q',
        '1702-RT',
        '1702-MX',
        '1702-EX',
        '1702-EXQ',
        '1702Q',
        '2550Q',
        '2551Q',
        '1601C',
        '0619E',
        '0619F',
        '1601EQ',
        '1601FQ',
        '1604C',
        '1604E',
        '2303',
        '2307',
        '2306',
        '2316',
        '0605',
        '2000',
        '2000-OT',
        '1706',
        '1707',
        '1800',
        '1801',
        '1901',
        '1902',
        '1903',
        '1904',
        '1905',
        '1906',
        '1907',
        '2200 Series',
        'Other',
    ];

    private const FILING_FREQUENCY_OPTIONS = [
        'Monthly',
        'Quarterly',
        'Annually',
        'One-Time',
        'As Needed',
    ];

    private const STATUS_OPTIONS = [
        'Pending',
        'Filed',
        'Completed',
    ];

    public function index(Request $request, int $company)
    {
        $companyData = $this->findCompany($request, $company);

        if ($request->expectsJson()) {
            $query = BirTax::query()->where('company_id', $company);
            $searchTaxTypes = trim((string) $request->query('search_tax_types', ''));
            $searchFormType = trim((string) $request->query('search_form_type', ''));

            if (! $this->canApproveCorporate()) {
                $query->where('submitted_by', Auth::id());
            }

            if ($request->filled('workflow_status') && $request->workflow_status !== 'all') {
                $query->where('workflow_status', ucfirst($request->workflow_status));
            }

            $records = $query->get()
                ->when($searchTaxTypes !== '', function ($items) use ($searchTaxTypes) {
                    $needle = mb_strtolower($searchTaxTypes);

                    return $items->filter(fn (BirTax $record) => str_contains(mb_strtolower((string) $record->tax_types), $needle));
                })
                ->when($searchFormType !== '', function ($items) use ($searchFormType) {
                    $needle = mb_strtolower($searchFormType);

                    return $items->filter(fn (BirTax $record) => str_contains(mb_strtolower((string) $record->form_type), $needle));
                })
                ->sort(function (BirTax $left, BirTax $right) {
                    $leftRank = $this->sortRankForBirTax($left);
                    $rightRank = $this->sortRankForBirTax($right);

                    if ($leftRank !== $rightRank) {
                        return $leftRank <=> $rightRank;
                    }

                    $leftDate = optional($left->due_date)?->timestamp ?? PHP_INT_MAX;
                    $rightDate = optional($right->due_date)?->timestamp ?? PHP_INT_MAX;

                    if ($leftDate !== $rightDate) {
                        return $leftDate <=> $rightDate;
                    }

                    return $right->id <=> $left->id;
                })
                ->map(fn (BirTax $record) => $this->transformRecord($record))
                ->values();

            return response()->json($records);
        }

        return view('corporate.bir-tax.index', [
            'company' => (object) $companyData,
            'companyDefaults' => array_merge($companyData, [
                'tin' => $companyData['tin_no'] ?? null,
                'company_address' => $companyData['address'] ?? null,
            ]),
            'defaultWorkflowTab' => 'accepted',
            'repositoryRoutes' => [
                'dataUrl' => route('company.bir-tax', $company),
                'storeUrl' => route('company.bir-tax.store', $company),
                'updateUrl' => route('company.bir-tax.update', ['company' => $company, 'record' => '__ID__']),
                'submitUrl' => route('company.bir-tax.submit', ['company' => $company, 'record' => '__ID__']),
            ],
            'taxTypeOptions' => self::TAX_TYPE_OPTIONS,
            'formTypeOptions' => self::FORM_TYPE_OPTIONS,
            'filingFrequencyOptions' => self::FILING_FREQUENCY_OPTIONS,
            'statusOptions' => self::STATUS_OPTIONS,
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

        $record = BirTax::create([
            'company_id' => $company,
            'company_name' => $companyData['company_name'],
            'tin' => $validated['tin'] ?: ($companyData['tin_no'] ?? ''),
            'tax_payer' => $validated['tax_payer'],
            'rdo' => $validated['rdo'],
            'registering_office' => $validated['rdo'],
            'registered_address' => $validated['registered_address'],
            'tax_types' => $this->normalizeMultiValueField(
                $request->input('tax_types_selected', []),
                $request->input('tax_types_other', ''),
                $validated['tax_types'] ?? ''
            ),
            'form_type' => $this->normalizeMultiValueField(
                $request->input('form_types_selected', []),
                $request->input('form_type_other', ''),
                $validated['form_type'] ?? ''
            ),
            'tax_due' => $validated['tax_due'] ?? null,
            'filing_frequency' => $validated['filing_frequency'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'] ?? 'Pending',
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

        $this->syncDeadlineTownHallMemo(
            $record,
            $record->due_date?->toDateString(),
            'BIR & Tax',
            $this->recordLabel($record),
            'bir-tax.preview'
        );

        return response()->json([
            'message' => 'BIR & Tax entry saved successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ], 201);
    }

    public function update(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $birTax = BirTax::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($birTax)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request);
        $user = $this->currentUserLabel($request);

        [$documentPath, $draftDocuments] = $this->collectDraftDocuments($request, $birTax);
        [$approvedDocumentPath, $approvedDocuments] = $this->collectApprovedDocuments($request, $birTax);
        $primaryDocumentPath = $documentPath ?: $approvedDocumentPath ?: $birTax->document_path;
        $primaryDocumentName = $this->resolveDocumentName($primaryDocumentPath, $draftDocuments, $approvedDocuments) ?: $birTax->document_name;

        $birTax->update([
            'tin' => $validated['tin'],
            'tax_payer' => $validated['tax_payer'],
            'rdo' => $validated['rdo'],
            'registering_office' => $validated['rdo'],
            'registered_address' => $validated['registered_address'],
            'tax_types' => $this->normalizeMultiValueField(
                $request->input('tax_types_selected', []),
                $request->input('tax_types_other', ''),
                $validated['tax_types'] ?? $birTax->tax_types
            ),
            'form_type' => $this->normalizeMultiValueField(
                $request->input('form_types_selected', []),
                $request->input('form_type_other', ''),
                $validated['form_type'] ?? $birTax->form_type
            ),
            'tax_due' => $validated['tax_due'] ?? null,
            'filing_frequency' => $validated['filing_frequency'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'] ?? $birTax->status,
            'document_path' => $primaryDocumentPath,
            'document_name' => $primaryDocumentName,
            'draft_documents' => $draftDocuments,
            'approved_document_path' => $approvedDocumentPath,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'approval_status' => ($birTax->workflow_status ?? 'Uploaded') === 'Reverted' ? 'Pending' : $birTax->approval_status,
            'review_note' => ($birTax->workflow_status ?? 'Uploaded') === 'Reverted' ? null : $birTax->review_note,
            'notes_visible_to' => $validated['notes_visible_to'] ?? $birTax->notes_visible_to,
            'notes' => $validated['notes'] ?? $birTax->notes,
        ]);

        $birTax->refresh();

        $this->syncDeadlineTownHallMemo(
            $birTax,
            $birTax->due_date?->toDateString(),
            'BIR & Tax',
            $this->recordLabel($birTax),
            'bir-tax.preview'
        );

        return response()->json([
            'message' => 'BIR & Tax entry updated.',
            'data' => $this->transformRecord($birTax->fresh()),
        ]);
    }

    public function submitCompany(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $birTax = BirTax::query()->where('company_id', $company)->findOrFail($record);

        if ((int) $birTax->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (! in_array($birTax->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true)) {
            return response()->json(['message' => 'Only uploaded or reverted records can be submitted.'], 422);
        }

        $birTax->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
            'last_updated_by' => Auth::user()?->name ?? Auth::user()?->email ?? 'System User',
            'last_updated_at' => now(),
        ]);

        $this->notifyCorporateApproversOfSubmission($birTax->fresh(), 'bir-tax');

        return response()->json([
            'message' => 'BIR & Tax entry submitted for approval successfully.',
            'data' => $this->transformRecord($birTax->fresh()),
        ]);
    }

    public function destroy(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $birTax = BirTax::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($birTax)) {
            abort(403, 'This record can no longer be deleted.');
        }

        $this->deleteDeadlineTownHallMemo($birTax);
        $birTax->delete();

        return response()->json(['message' => 'BIR & Tax entry deleted successfully.']);
    }

    private function canApproveCorporate(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('approve_corporate');
    }

    private function canEditRecord(BirTax $record): bool
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
            'tin' => ['nullable', 'string', 'max:255'],
            'tax_payer' => ['required', 'string', 'max:255'],
            'rdo' => ['nullable', 'string', 'max:255'],
            'registering_office' => ['nullable', 'string', 'max:255'],
            'registered_address' => ['nullable', 'string', 'max:1000'],
            'tax_types' => ['nullable', 'string', 'max:2000'],
            'tax_types_selected' => ['nullable', 'array'],
            'tax_types_selected.*' => ['string'],
            'tax_types_other' => ['nullable', 'string', 'max:255'],
            'form_type' => ['nullable', 'string', 'max:2000'],
            'form_types_selected' => ['nullable', 'array'],
            'form_types_selected.*' => ['string'],
            'form_type_other' => ['nullable', 'string', 'max:255'],
            'tax_due' => ['nullable', 'numeric', 'min:0'],
            'filing_frequency' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
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

    private function collectDraftDocuments(Request $request, ?BirTax $existing = null): array
    {
        return $this->collectDocumentSet(
            $request,
            'draft_documents',
            'document_path',
            'document_paths',
            $existing?->draft_documents ?? [],
            $existing?->document_path,
            'uploads/company/bir-tax/drafts'
        );
    }

    private function collectApprovedDocuments(Request $request, ?BirTax $existing = null): array
    {
        return $this->collectDocumentSet(
            $request,
            'approved_documents',
            'approved_document_path',
            'approved_document_paths',
            $existing?->approved_documents ?? [],
            $existing?->approved_document_path,
            'uploads/company/bir-tax/approved'
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

    private function transformRecord(BirTax $record): array
    {
        $draftDocuments = $this->documentLinks($record->draft_documents);
        $approvedDocuments = $this->documentLinks($record->approved_documents);

        return [
            'id' => $record->id,
            'company' => $record->company_name ?: 'Selected Company',
            'tin' => $record->tin,
            'tax_payer' => $record->tax_payer,
            'rdo' => $record->rdo ?: $record->registering_office,
            'registered_address' => $record->registered_address,
            'tax_types' => $record->tax_types,
            'form_type' => $record->form_type,
            'tax_due' => $record->tax_due,
            'filing_frequency' => $record->filing_frequency,
            'due_date' => optional($record->due_date)->format('Y-m-d'),
            'status' => $record->status,
            'filing_status' => $record->status,
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

    private function sortRankForBirTax(BirTax $record): int
    {
        return match ($record->status) {
            'Pending' => 0,
            'Filed' => 1,
            'Completed' => 2,
            default => 3,
        };
    }

    private function recordLabel(BirTax $record): string
    {
        return trim(($record->form_type ?: 'BIR filing') . ' - ' . ($record->tax_payer ?: $record->tin ?: 'Untitled Record'), ' -');
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, []);
    }
}
