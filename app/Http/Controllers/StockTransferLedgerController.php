<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\MatchesShareholder;
use App\Http\Controllers\Concerns\GeneratesStockTransferIds;
use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Models\StockTransferCertificate;
use App\Models\StockTransferInstallment;
use App\Models\StockTransferIssuanceRequest;
use App\Models\StockTransferJournal;
use App\Models\StockTransferLedger;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StockTransferLedgerController extends Controller
{
    use HandlesUploads;
    use MatchesShareholder;
    use GeneratesStockTransferIds;
    use GeneratesPdfPreview;

    public function indexPage()
    {
        $ledgers = StockTransferLedger::query()
            ->whereNull('journal_id')
            ->latest()
            ->get();

        $contacts = $this->loadContactDirectory();

        return view('corporate.stock-transfer-book.stb-index', compact('ledgers', 'contacts'));
    }

    public function index()
    {
        $ledgers = StockTransferLedger::query()
            ->whereNotNull('journal_id')
            ->with('journal')
            ->latest()
            ->get();

        $contacts = $this->loadContactDirectory();

        return view('corporate.stock-transfer-book.ledger', compact('ledgers', 'contacts'));
    }

    private function loadContactDirectory(): Collection
    {
        if (!Schema::hasTable('contacts')) {
            return collect();
        }

        $selects = ['id'];

        foreach (
            [
                'name',
                'first_name',
                'middle_initial',
                'middle_name',
                'last_name',
                'name_extension',
                'email',
                'phone',
                'contact_address',
                'company_address',
                'tin',
            ] as $column
        ) {
            if (Schema::hasColumn('contacts', $column)) {
                $selects[] = $column;
            }
        }

        $query = Contact::query()->select($selects);

        if (Schema::hasColumn('contacts', 'last_name')) {
            $query->orderBy('last_name');
        }

        if (Schema::hasColumn('contacts', 'first_name')) {
            $query->orderBy('first_name');
        }

        return $query->get()
            ->map(function (Contact $contact) {
                $cifData = $this->loadContactCifData($contact);
                $firstName = trim((string) ($cifData['first_name'] ?? $contact->first_name ?? ''));
                $middleName = trim((string) ($cifData['middle_name'] ?? $contact->middle_name ?? $contact->middle_initial ?? ''));
                $lastName = trim((string) ($cifData['last_name'] ?? $contact->last_name ?? ''));
                $nameExtension = trim((string) ($cifData['name_extension'] ?? $contact->name_extension ?? ''));

                if ($firstName === '' && $lastName === '' && Schema::hasColumn('contacts', 'name')) {
                    $legacyName = trim((string) ($contact->getAttribute('name') ?? ''));
                    $parts = preg_split('/\s+/', $legacyName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    $firstName = $parts[0] ?? '';
                    $lastName = count($parts) > 1 ? (string) end($parts) : '';
                    $middleName = count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : '';
                }

                $name = trim(collect([
                    $firstName,
                    $middleName,
                    $lastName,
                    $nameExtension,
                ])->filter()->implode(' '));

                $address = trim(collect([
                    $cifData['present_address_line1'] ?? null,
                    $cifData['present_address_line2'] ?? null,
                ])->filter()->implode(' '));

                if ($address === '') {
                    $address = (string) (
                        $contact->contact_address
                        ?? $contact->company_address
                        ?? ''
                    );
                }

                return (object) [
                    'id' => $contact->id,
                    'name' => $name,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'name_extension' => $nameExtension,
                    'email' => $cifData['email'] ?? $contact->email ?? null,
                    'phone' => $cifData['mobile'] ?? $contact->phone ?? null,
                    'nationality' => $cifData['citizenship_nationality'] ?? null,
                    'address' => $address !== '' ? $address : null,
                    'tax_id' => $cifData['tin'] ?? $contact->tin ?? null,
                ];
            })
            ->filter(fn($contact) => !empty($contact->name))
            ->values();
    }

    private function loadContactCifData(Contact $contact): array
    {
        $path = 'contact-cif-data/'.$contact->id.'.json';

        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        $data = json_decode((string) Storage::disk('local')->get($path), true);

        return is_array($data) ? $data : [];
    }

    public function create()
    {
        return view('corporate.common.form', [
            'title' => 'Add Shareholder',
            'action' => route('stock-transfer-book.ledger.store'),
            'method' => 'POST',
            'cancelRoute' => route('stock-transfer-book.ledger'),
            'fields' => $this->fields(),
            'item' => new StockTransferLedger(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['document_path'] = $this->handleUpload($request, 'document_path');

        $contact = $this->resolveContact(
            $data['first_name'] ?? null,
            $data['middle_name'] ?? null,
            $data['family_name'] ?? null
        );

        if (!$contact) {
            return back()->withErrors([
                'first_name' => 'Stockholder must exist in Contacts before adding to Index.',
            ])->withInput();
        }

        if (empty($data['certificate_no'])) {
            $data['certificate_no'] = $this->nextStockNumber();
        }

        $cifData = $this->loadContactCifData($contact);
        $cifAddress = trim(collect([
            $cifData['present_address_line1'] ?? null,
            $cifData['present_address_line2'] ?? null,
        ])->filter()->implode(' '));

        $data['email'] = $data['email'] ?: ($cifData['email'] ?? $contact->email ?? null);
        $data['nationality'] = $data['nationality'] ?: ($cifData['citizenship_nationality'] ?? null);
        $data['address'] = $data['address'] ?: ($cifAddress ?: ($contact->contact_address ?? null));
        $data['tin'] = $data['tin'] ?: ($cifData['tin'] ?? $contact->tin ?? null);
        $data['phone'] = $data['phone'] ?: ($cifData['mobile'] ?? $contact->phone ?? null);

        if (empty($data['date_registered'])) {
            $data['date_registered'] = now()->toDateString();
        }

        DB::transaction(function () use ($data) {
            StockTransferLedger::create($data);
        });

        return redirect()->route('stock-transfer-book.ledger')->with('success', 'Shareholder added.');
    }

    public function show(StockTransferLedger $stockTransferLedger)
    {
        $certificateNo = $stockTransferLedger->certificate_no;
        $fullName = trim(collect([
            $stockTransferLedger->first_name,
            $stockTransferLedger->middle_name,
            $stockTransferLedger->family_name,
        ])->filter()->implode(' '));

        $journalEntries = StockTransferJournal::query()
            ->where(function ($query) use ($certificateNo, $fullName) {
                if ($certificateNo) {
                    $query->orWhere('certificate_no', $certificateNo);
                }

                $this->applyNameTokens($query, $fullName, ['shareholder']);
            })
            ->latest()
            ->get();

        $relatedCertificates = StockTransferCertificate::query()
            ->where(function ($query) use ($certificateNo, $fullName) {
                if ($certificateNo) {
                    $query->orWhere('stock_number', $certificateNo);
                }

                $this->applyNameTokens($query, $fullName, ['stockholder_name']);
            })
            ->latest()
            ->get();

        $relatedInstallments = StockTransferInstallment::query()
            ->where(function ($query) use ($certificateNo, $fullName) {
                if ($certificateNo) {
                    $query->orWhere('stock_number', $certificateNo);
                }

                $this->applyNameTokens($query, $fullName, ['subscriber']);
            })
            ->latest()
            ->get();

        $relatedRequests = Schema::hasTable('stock_transfer_issuance_requests')
            ? StockTransferIssuanceRequest::query()
            ->where(function ($query) use ($certificateNo, $fullName) {
                $this->applyNameTokens($query, $fullName, ['requester']);

                if ($certificateNo) {
                    $query->orWhereHas('certificate', function ($certificateQuery) use ($certificateNo) {
                        $certificateQuery->where('stock_number', $certificateNo);
                    });
                }
            })
            ->latest()
            ->get()
            : collect();

        $generatedPreviewPath = $this->generatePdfPreview(
            'corporate.stock-transfer-book.ledger-pdf',
            [
                'ledger' => $stockTransferLedger,
                'journalEntries' => $journalEntries,
            ],
            'generated-previews/stock-transfer-book/ledger/' . ($stockTransferLedger->certificate_no ?: $stockTransferLedger->id) . '.pdf'
        );

        return view('corporate.stock-transfer-book.ledger-preview', [
            'ledger' => $stockTransferLedger,
            'generatedPreviewUrl' => $generatedPreviewPath ? route('uploads.show', ['path' => $generatedPreviewPath]) : null,
            'journalEntries' => $journalEntries,
            'relatedCertificates' => $relatedCertificates,
            'relatedInstallments' => $relatedInstallments,
            'relatedRequests' => $relatedRequests,
            'backRoute' => route('stock-transfer-book.ledger'),
            'editRoute' => route('stock-transfer-book.ledger.edit', $stockTransferLedger),
        ]);
    }

    public function edit(StockTransferLedger $stockTransferLedger)
    {
        return view('corporate.common.form', [
            'title' => 'Edit Shareholder',
            'action' => route('stock-transfer-book.ledger.update', $stockTransferLedger),
            'method' => 'PUT',
            'cancelRoute' => route('stock-transfer-book.ledger'),
            'fields' => $this->fields(),
            'item' => $stockTransferLedger,
        ]);
    }

    public function update(Request $request, StockTransferLedger $stockTransferLedger)
    {
        $data = $this->validateData($request);
        $data['document_path'] = $this->handleUpload($request, 'document_path', $stockTransferLedger->document_path);

        $stockTransferLedger->update($data);

        return redirect()->route('stock-transfer-book.ledger')->with('success', 'Shareholder updated.');
    }

    public function destroy(StockTransferLedger $stockTransferLedger)
    {
        $stockTransferLedger->delete();

        return redirect()->route('stock-transfer-book.ledger')->with('success', 'Shareholder deleted.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'family_name', 'label' => 'Family Name', 'type' => 'text', 'required' => true],
            ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],
            ['name' => 'middle_name', 'label' => 'Middle Name', 'type' => 'text'],
            ['name' => 'nationality', 'label' => 'Nationality', 'type' => 'text'],
            ['name' => 'address', 'label' => 'Current Residential Address', 'type' => 'text'],
            ['name' => 'tin', 'label' => 'TIN', 'type' => 'text'],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
            ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
            ['name' => 'shares', 'label' => 'Number of Shares', 'type' => 'number'],
            ['name' => 'certificate_no', 'label' => 'Certificate No.', 'type' => 'text'],
            ['name' => 'date_registered', 'label' => 'Date Registered', 'type' => 'date'],
            ['name' => 'document_path', 'label' => 'Upload Document (PDF)', 'type' => 'file'],
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'family_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'shares' => ['nullable', 'integer'],
            'certificate_no' => ['nullable', 'string', 'max:255'],
            'date_registered' => ['nullable', 'date'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);
    }

    private function resolveContact(?string $firstName, ?string $middleName, ?string $familyName): ?Contact
    {
        $name = trim(collect([$firstName, $middleName, $familyName])->filter()->implode(' '));

        if ($name === '') {
            return null;
        }

        $query = Contact::query();
        $this->applyNameTokens($query, $name, ['first_name', 'middle_name', 'last_name']);

        return $query->first();
    }
}
