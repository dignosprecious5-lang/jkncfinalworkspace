<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\SyncsDeadlineTownHallMemo;
use App\Models\BirTax;
use App\Models\GisRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BirTaxController extends Controller
{
    use GeneratesPdfPreview;
    use HandlesUploads;
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

    public function index(Request $request)
    {
        $searchTaxTypes = trim((string) $request->query('search_tax_types', ''));
        $searchFormType = trim((string) $request->query('search_form_type', ''));

        $taxes = Schema::hasTable('bir_taxes')
            ? BirTax::query()->get()
            : collect();

        $taxes = $taxes
            ->when($searchTaxTypes !== '', function (Collection $items) use ($searchTaxTypes) {
                $term = mb_strtolower($searchTaxTypes);

                return $items->filter(fn (BirTax $item) => str_contains(mb_strtolower($item->tax_types ?? ''), $term));
            })
            ->when($searchFormType !== '', function (Collection $items) use ($searchFormType) {
                $term = mb_strtolower($searchFormType);

                return $items->filter(fn (BirTax $item) => str_contains(mb_strtolower($item->form_type ?? ''), $term));
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
            ->values();

        return view('corporate.bir-tax.index', [
            'taxes' => $taxes,
            'companyDefaults' => $this->companyDefaults(),
            'searchTaxTypes' => $searchTaxTypes,
            'searchFormType' => $searchFormType,
            'taxTypeOptions' => self::TAX_TYPE_OPTIONS,
            'formTypeOptions' => self::FORM_TYPE_OPTIONS,
            'filingFrequencyOptions' => self::FILING_FREQUENCY_OPTIONS,
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function create()
    {
        return view('corporate.common.form', [
            'title' => 'Add BIR & Tax',
            'action' => route('bir-tax.store'),
            'method' => 'POST',
            'cancelRoute' => route('bir-tax'),
            'fields' => $this->fields(),
            'item' => new BirTax($this->companyDefaults()),
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
            'uploads/bir-tax/drafts'
        );

        if (Schema::hasColumn('bir_taxes', 'approved_document_path')) {
            [$data['approved_document_path'], $data['approved_documents']] = $this->appendUploadedFiles(
                $request,
                'approved_document_path',
                'approved_document_paths',
                [],
                null,
                'uploads/bir-tax/approved'
            );
        }

        $birTax = BirTax::create($this->filterPersistableData($data));
        $this->syncDeadlineTownHallMemo(
            $birTax,
            $birTax->due_date?->toDateString(),
            'BIR & Tax',
            $this->recordLabel($birTax),
            'bir-tax.preview'
        );

        return redirect()->route('bir-tax')->with('success', 'BIR & Tax entry created.');
    }

    public function show(BirTax $birTax)
    {
        $generatedDraftPath = null;
        if (!$birTax->document_path) {
            $generatedDraftPath = $this->generatePdfPreview(
                'corporate.bir-tax.pdf',
                ['tax' => $birTax],
                'generated-previews/bir-tax/' . ($birTax->tin ?: $birTax->id) . '-draft.pdf'
            );
        }

        $uploadUrl = function (?string $path): ?string {
            if (!$path || !Storage::disk('public')->exists($path)) {
                return null;
            }

            $segments = array_map('rawurlencode', array_values(array_filter(explode('/', trim($path, '/')), fn ($segment) => $segment !== '')));

            return url('/uploads/' . implode('/', $segments));
        };

        $draftDocuments = collect($birTax->draft_documents ?? [])->filter(fn ($entry) => !empty($entry['path']) && Storage::disk('public')->exists($entry['path']))->values();
        $approvedDocuments = collect($birTax->approved_documents ?? [])->filter(fn ($entry) => !empty($entry['path']) && Storage::disk('public')->exists($entry['path']))->values();
        $draftOptions = $draftDocuments->map(function ($entry, $index) use ($uploadUrl) {
            return [
                'url' => $uploadUrl($entry['path']),
                'label' => $entry['name'] ?? ('Draft Revision ' . ($index + 1)),
                'uploaded_at' => $entry['uploaded_at'] ?? null,
            ];
        })->values()->all();

        $draftUrl = $uploadUrl($birTax->document_path) ?: ($generatedDraftPath && Storage::disk('public')->exists($generatedDraftPath) ? route('uploads.show', ['path' => $generatedDraftPath]) : null);
        $approvedUrl = $uploadUrl($birTax->approved_document_path);

        return view('corporate.bir-tax.preview', [
            'tax' => $birTax,
            'generatedDraftUrl' => $generatedDraftPath && Storage::disk('public')->exists($generatedDraftPath) ? route('uploads.show', ['path' => $generatedDraftPath]) : null,
            'draftUrl' => $draftUrl,
            'approvedUrl' => $approvedUrl,
            'draftDocuments' => $draftDocuments,
            'approvedDocuments' => $approvedDocuments,
            'draftOptions' => $draftOptions,
            'selectedDraftUrl' => !empty($draftOptions) ? $draftOptions[array_key_last($draftOptions)]['url'] : $draftUrl,
            'latestDraft' => $draftDocuments->last(),
            'latestApproved' => $approvedDocuments->last(),
            'visibleAuthorityNotes' => $this->visibleAuthorityNotes($birTax),
            'backRoute' => route('bir-tax'),
            'editRoute' => route('bir-tax.edit', $birTax),
            'deleteRoute' => route('bir-tax.destroy', $birTax),
            'updateRoute' => route('bir-tax.update', $birTax),
        ]);
    }

    public function edit(BirTax $birTax)
    {
        return view('corporate.common.form', [
            'title' => 'Edit BIR & Tax',
            'action' => route('bir-tax.update', $birTax),
            'method' => 'PUT',
            'cancelRoute' => route('bir-tax'),
            'fields' => $this->fields(),
            'item' => $birTax,
        ]);
    }

    public function update(Request $request, BirTax $birTax)
    {
        $data = $this->validateData($request);
        $data = $this->normalizePersistedData($request, $data, $birTax);

        [$data['document_path'], $data['draft_documents']] = $this->appendUploadedFiles(
            $request,
            'document_path',
            'document_paths',
            $birTax->draft_documents ?? [],
            $birTax->document_path,
            'uploads/bir-tax/drafts'
        );

        if (Schema::hasColumn('bir_taxes', 'approved_document_path')) {
            [$data['approved_document_path'], $data['approved_documents']] = $this->appendUploadedFiles(
                $request,
                'approved_document_path',
                'approved_document_paths',
                $birTax->approved_documents ?? [],
                $birTax->approved_document_path,
                'uploads/bir-tax/approved'
            );
        }

        $birTax->update($this->filterPersistableData($data));
        $birTax->refresh();

        $this->syncDeadlineTownHallMemo(
            $birTax,
            $birTax->due_date?->toDateString(),
            'BIR & Tax',
            $this->recordLabel($birTax),
            'bir-tax.preview'
        );

        return $this->birTaxRedirectResponse($request, $birTax, 'BIR & Tax entry updated.');
    }

    public function destroy(BirTax $birTax)
    {
        $this->deleteDeadlineTownHallMemo($birTax);
        $birTax->delete();

        return redirect()->route('bir-tax')->with('success', 'BIR & Tax entry deleted.');
    }

    public function storeAuthorityNote(Request $request, BirTax $birTax)
    {
        $data = $request->validate([
            'visible_to_role' => ['required', 'string', 'in:Admin,Employee'],
            'body' => ['required', 'string'],
        ]);

        $birTax->authorityNotes()->create([
            'user_id' => auth()->id(),
            'visible_to_role' => $data['visible_to_role'],
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('bir-tax.preview', $birTax)
            ->with('success', 'Authority note added.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'tin', 'label' => 'TIN', 'type' => 'text'],
            ['name' => 'tax_payer', 'label' => 'Taxpayer', 'type' => 'text'],
            ['name' => 'rdo', 'label' => 'RDO', 'type' => 'text'],
            ['name' => 'registered_address', 'label' => 'Registered Address', 'type' => 'textarea'],
            ['name' => 'tax_types', 'label' => 'Tax Type/s (comma-separated)', 'type' => 'textarea'],
            ['name' => 'form_type', 'label' => 'Form Type (comma-separated)', 'type' => 'textarea'],
            ['name' => 'tax_due', 'label' => 'Tax Due', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'filing_frequency', 'label' => 'Filing Frequency', 'type' => 'select', 'options' => self::FILING_FREQUENCY_OPTIONS],
            ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUS_OPTIONS],
            ['name' => 'document_path', 'label' => 'Upload Draft BIR & Tax Document (PDF)', 'type' => 'file'],
            ['name' => 'approved_document_path', 'label' => 'Upload Approved BIR & Tax Document (PDF)', 'type' => 'file'],
            ['name' => 'notes_visible_to', 'label' => 'Notes Visible To Authority', 'type' => 'text'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'tin' => ['nullable', 'string', 'max:255'],
            'tax_payer' => ['nullable', 'string', 'max:255'],
            'rdo' => ['nullable', 'string', 'max:255'],
            'registering_office' => ['nullable', 'string', 'max:255'],
            'registered_address' => ['nullable', 'string', 'max:1000'],
            'tax_types' => ['nullable', 'string', 'max:1000'],
            'tax_types_selected' => ['nullable', 'array'],
            'tax_types_selected.*' => ['string'],
            'tax_types_other' => ['nullable', 'string', 'max:255'],
            'form_type' => ['nullable', 'string', 'max:1000'],
            'form_types_selected' => ['nullable', 'array'],
            'form_types_selected.*' => ['string'],
            'form_type_other' => ['nullable', 'string', 'max:255'],
            'tax_due' => ['nullable', 'numeric', 'min:0'],
            'filing_frequency' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
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
                'tax_payer' => $gis->corporation_name ?: 'JK&C Group of Companies',
                'tin' => $gis->tin ?: '000-000-000-000',
                'registered_address' => $gis->business_address ?: ($gis->principal_address ?: 'JK&C Corporate Office'),
            ];
        }

        return [
            'tax_payer' => 'JK&C Group of Companies',
            'tin' => '000-000-000-000',
            'registered_address' => 'JK&C Corporate Office',
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

    private function normalizePersistedData(Request $request, array $data, ?BirTax $existing = null): array
    {
        $defaults = $this->companyDefaults();

        $data['tin'] = trim((string) (($data['tin'] ?? '') ?: ($existing?->tin ?: ($defaults['tin'] ?? ''))));
        $data['tax_payer'] = trim((string) (($data['tax_payer'] ?? '') ?: ($existing?->tax_payer ?: ($defaults['tax_payer'] ?? ''))));
        $data['registered_address'] = trim((string) (($data['registered_address'] ?? '') ?: ($existing?->registered_address ?: ($defaults['registered_address'] ?? ''))));
        $data['rdo'] = trim((string) (($data['rdo'] ?? '') ?: ($data['registering_office'] ?? '') ?: $existing?->rdo ?: $existing?->registering_office ?: ''));
        $data['registering_office'] = $data['rdo'];
        $data['tax_types'] = $this->normalizeMultiValueField(
            $request->input('tax_types_selected', []),
            $request->input('tax_types_other', ''),
            ($data['tax_types'] ?? '') ?: ($existing?->tax_types ?? '')
        );
        $data['form_type'] = $this->normalizeMultiValueField(
            $request->input('form_types_selected', []),
            $request->input('form_type_other', ''),
            ($data['form_type'] ?? '') ?: ($existing?->form_type ?? '')
        );
        $data['tax_due'] = $data['tax_due'] ?? $existing?->tax_due;
        $data['filing_frequency'] = trim((string) (($data['filing_frequency'] ?? '') ?: ($existing?->filing_frequency ?? '')));
        $data['status'] = trim((string) (($data['status'] ?? '') ?: ($existing?->status ?: 'Pending')));
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
            ->filter(fn ($value, $key) => Schema::hasColumn('bir_taxes', $key))
            ->all();
    }

    private function visibleAuthorityNotes(BirTax $birTax)
    {
        $role = auth()->user()?->role;

        return $birTax->authorityNotes()
            ->with('user:id,name,role')
            ->when($role && $role !== 'SuperAdmin', function ($query) use ($role) {
                $query->where('visible_to_role', $role);
            })
            ->get();
    }

    private function sortRankForBirTax(BirTax $record): int
    {
        $status = strtolower(trim((string) ($record->display_status ?: '')));

        return match ($status) {
            'overdue' => 0,
            'due today' => 1,
            'upcoming' => 2,
            'filed', 'completed' => 3,
            default => 4,
        };
    }

    private function recordLabel(BirTax $birTax): string
    {
        return trim(($birTax->form_type ?: 'BIR filing') . ' - ' . ($birTax->tax_payer ?: $birTax->tin ?: 'Untitled Record'), ' -');
    }

    private function birTaxRedirectResponse(Request $request, BirTax $birTax, string $message)
    {
        $redirectTo = trim((string) $request->input('redirect_to', ''));

        if ($redirectTo === 'preview') {
            return redirect()
                ->route('bir-tax.preview', $birTax)
                ->with('success', $message);
        }

        return redirect()
            ->route('bir-tax')
            ->with('success', $message);
    }
}
