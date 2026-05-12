<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\MatchesShareholder;
use App\Http\Controllers\Concerns\GeneratesStockTransferIds;
use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Models\AuthorizedCapitalStock;
use App\Models\GisRecord;
use App\Models\Stockholder;
use App\Models\StockTransferCertificate;
use App\Models\StockTransferIssuanceRequest;
use App\Models\StockTransferJournal;
use App\Models\StockTransferLedger;
use App\Models\StockTransferInstallment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class StockTransferCertificateController extends Controller
{
    use HandlesUploads;
    use MatchesShareholder;
    use GeneratesStockTransferIds;
    use GeneratesPdfPreview;

    public function index()
    {
        $manilaNow = Carbon::now('Asia/Manila');

        $existingActiveStockNumbers = StockTransferCertificate::query()
            ->whereNull('source_certificate_id')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'voided');
            })
            ->pluck('stock_number')
            ->filter()
            ->unique()
            ->values();

        if ($this->hasCertificateWorkflowColumns()) {
            $certificateStocks = StockTransferCertificate::query()
                ->whereNull('source_certificate_id')
                ->latest()
                ->get();

            $certificateVouchers = StockTransferCertificate::query()
                ->whereNotNull('source_certificate_id')
                ->with('sourceCertificate')
                ->latest()
                ->get();
        } else {
            $certificateStocks = StockTransferCertificate::query()
                ->latest()
                ->get();

            $certificateVouchers = collect();
        }

        $issuanceRequests = $this->hasIssuanceRequestsTable()
            ? StockTransferIssuanceRequest::query()->with('certificate')->latest()->get()
            : collect();

        $indexShareholders = StockTransferLedger::query()
            ->whereNull('journal_id')
            ->get(['first_name', 'middle_name', 'family_name'])
            ->map(function ($ledger) {
                return trim(collect([$ledger->first_name, $ledger->middle_name, $ledger->family_name])->filter()->implode(' '));
            })
            ->filter()
            ->unique()
            ->values();

        $availableStockNumbers = StockTransferLedger::query()
            ->whereNull('journal_id')
            ->pluck('certificate_no')
            ->merge(
                StockTransferInstallment::query()
                    ->get()
                    ->filter(fn($installment) => $installment->isFullyPaid())
                    ->pluck('stock_number')
            )
            ->filter()
            ->unique()
            ->values();

        $availableInstallments = StockTransferInstallment::query()
            ->get()
            ->filter(fn($installment) => $installment->isFullyPaid())
            ->values();

        $hasCompanyTables = Schema::hasTable('gis_records');
        $hasAuthorizedCapitalTable = Schema::hasTable('authorized_capital_stocks');
        $hasStockholdersTable = Schema::hasTable('stockholders');

        $latestCompany = $hasCompanyTables ? GisRecord::query()->latest()->first() : null;
        $companyParValue = $hasAuthorizedCapitalTable
            ? AuthorizedCapitalStock::query()->latest()->value('par_value')
            : null;

        if ($companyParValue === null && $latestCompany) {
            $companyParValue = optional($latestCompany?->authorizedCapital()->latest()->first())->par_value
                ?? optional($latestCompany?->subscribedCapital()->latest()->first())->par_value
                ?? optional($latestCompany?->paidUpCapital()->latest()->first())->par_value;
        }

        $availableInstallments = $availableInstallments->map(function ($installment) use ($latestCompany, $companyParValue, $hasStockholdersTable) {
            $stockholderRecord = null;

            if ($hasStockholdersTable && !empty($installment->subscriber)) {
                $query = Stockholder::query();
                $this->applyNameTokens($query, $installment->subscriber, ['stockholder_name']);
                $stockholderRecord = $query->latest()->first();
            }

            $amount = $installment->total_value;
            if (($amount === null || $amount === '') && $installment->no_shares && $companyParValue) {
                $amount = (float) $installment->no_shares * (float) $companyParValue;
            }

            return (object) [
                'stock_number' => $installment->stock_number,
                'subscriber' => $installment->subscriber,
                'stockholder_name' => $stockholderRecord?->stockholder_name ?: $installment->subscriber,
                'installment_date' => $installment->installment_date,
                'no_shares' => $installment->no_shares,
                'total_value' => $amount,
                'installment_amount' => $installment->installment_amount,
                'payment_status' => $installment->payment_status,
                'is_fully_paid' => $installment->isFullyPaid(),
                'corporation_name' => $latestCompany?->corporation_name,
                'company_reg_no' => $latestCompany?->company_reg_no,
                'par_value' => $installment->par_value ?: $companyParValue,
                'amount_in_words' => $this->amountToWords($amount),
            ];
        });

        $stockNumberDirectory = StockTransferLedger::query()
            ->whereNull('journal_id')
            ->get(['certificate_no', 'first_name', 'middle_name', 'family_name', 'shares', 'status'])
            ->map(function ($ledger) use ($existingActiveStockNumbers) {
                $stockNumber = trim((string) $ledger->certificate_no);
                if ($stockNumber === '' || $existingActiveStockNumbers->contains($stockNumber)) {
                    return null;
                }

                return (object) [
                    'stock_number' => $stockNumber,
                    'holder_name' => trim(collect([$ledger->first_name, $ledger->middle_name, $ledger->family_name])->filter()->implode(' ')),
                    'shares' => $ledger->shares,
                    'source' => 'Index',
                    'status_label' => ucfirst($ledger->status ?: 'active'),
                    'is_recommended' => true,
                ];
            })
            ->filter()
            ->values()
            ->merge(
                $availableInstallments
                    ->filter(function ($installment) use ($existingActiveStockNumbers) {
                        return $installment->stock_number
                            && !$existingActiveStockNumbers->contains($installment->stock_number);
                    })
                    ->map(function ($installment) {
                        return (object) [
                            'stock_number' => $installment->stock_number,
                            'holder_name' => $installment->stockholder_name ?: $installment->subscriber,
                            'shares' => $installment->no_shares,
                            'source' => 'Installment',
                            'status_label' => 'Fully Paid',
                            'is_recommended' => true,
                        ];
                    })
            )
            ->unique('stock_number')
            ->sortByDesc(fn($entry) => $entry->is_recommended)
            ->values();

        return view('corporate.stock-transfer-book.certificates', [
            'certificateStocks' => $certificateStocks,
            'certificateVouchers' => $certificateVouchers,
            'issuanceRequests' => $issuanceRequests,
            'indexShareholders' => $indexShareholders,
            'availableStockNumbers' => $availableStockNumbers,
            'availableInstallments' => $availableInstallments,
            'stockNumberDirectory' => $stockNumberDirectory,
            'nextIssuanceRequestReference' => $this->hasIssuanceRequestsTable()
                ? $this->nextIssuanceRequestReference()
                : 'REQ-0001',
            'defaultRequestedAt' => $manilaNow->format('Y-m-d\TH:i'),
        ]);
    }

    public function create()
    {
        return view('corporate.common.form', [
            'title' => 'New Certificate',
            'action' => route('stock-transfer-book.certificates.store'),
            'method' => 'POST',
            'cancelRoute' => route('stock-transfer-book.certificates'),
            'fields' => $this->fields(),
            'item' => new StockTransferCertificate(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['document_path'] = $this->handleUpload($request, 'document_path');
        $data['status'] = $data['status'] ?? 'draft';
        $data['certificate_type'] = $data['certificate_type'] ?? 'COS';

        $ledger = $this->resolveLedger($data['stock_number'] ?? null, $data['stockholder_name'] ?? null);
        if (!$ledger && empty($data['stockholder_name'])) {
            return back()->withErrors([
                'stockholder_name' => 'Enter a stockholder name or choose an existing stock number from the Index.',
            ])->withInput();
        }

        if (empty($data['stock_number'])) {
            $data['stock_number'] = $ledger?->certificate_no ?: $this->nextStockNumber();
            if ($ledger && !$ledger->certificate_no) {
                $ledger->certificate_no = $data['stock_number'];
                $ledger->save();
            }
        }

        if (empty($data['stockholder_name']) && $ledger) {
            $data['stockholder_name'] = trim(collect([$ledger->first_name, $ledger->middle_name, $ledger->family_name])->filter()->implode(' '));
        }

        if (empty($data['date_uploaded'])) {
            $data['date_uploaded'] = now()->toDateString();
        }

        if (empty($data['date_issued'])) {
            $data['date_issued'] = now()->toDateString();
        }

        if (empty($data['number']) && $ledger?->shares) {
            $data['number'] = $ledger->shares;
        }

        if (($data['amount'] === null || $data['amount'] === '') && !empty($data['par_value']) && !empty($data['number'])) {
            $data['amount'] = (float) $data['par_value'] * (int) $data['number'];
        }

        if (empty($data['amount_in_words']) && $data['amount'] !== null && $data['amount'] !== '') {
            $data['amount_in_words'] = $this->amountToWords($data['amount']);
        }

        if (!$ledger) {
            $ledger = StockTransferLedger::create([
                'family_name' => $data['stockholder_name'],
                'first_name' => '',
                'middle_name' => null,
                'shares' => $data['number'] ?? null,
                'certificate_no' => $data['stock_number'],
                'date_registered' => $data['date_issued'] ?? now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $installment = null;
        if (!empty($data['stock_number'])) {
            $installment = StockTransferInstallment::query()
                ->where('stock_number', $data['stock_number'])
                ->latest()
                ->first();
        }

        $existingCertificate = StockTransferCertificate::query()
            ->whereNull('source_certificate_id')
            ->where('stock_number', $data['stock_number'])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'voided');
            })
            ->latest()
            ->first();

        if ($existingCertificate) {
            return back()->withErrors([
                'stock_number' => 'An active certificate stock already exists for this stock number.',
            ])->withInput();
        }

        if ($installment && !$installment->isFullyPaid()) {
            return back()->withErrors([
                'stock_number' => 'Cannot issue certificate: installment is not fully paid.',
            ])->withInput();
        }

        if ($installment && $installment->certificate && $installment->certificate->status !== 'voided') {
            return back()->withErrors([
                'stock_number' => 'Cannot issue certificate: an active certificate already exists for this installment.',
            ])->withInput();
        }

        if ($installment) {
            $data['installment_id'] = $installment->id;
        }

        DB::transaction(function () use ($data, $ledger) {
            $stockPayload = $data;

            if ($this->hasCertificateWorkflowColumns()) {
                $stockPayload = array_merge($stockPayload, [
                    'issued_to' => $data['stockholder_name'] ?? null,
                    'issued_to_type' => 'Stockholder',
                ]);
            }

            $stock = StockTransferCertificate::create($stockPayload);

            if ($this->hasCertificateWorkflowColumns()) {
                StockTransferCertificate::create([
                    'source_certificate_id' => $stock->id,
                    'date_uploaded' => $stock->date_uploaded,
                    'uploaded_by' => $stock->uploaded_by,
                    'corporation_name' => $stock->corporation_name,
                    'company_reg_no' => $stock->company_reg_no,
                    'certificate_type' => $stock->certificate_type,
                    'stock_number' => $stock->stock_number,
                    'stockholder_name' => $stock->stockholder_name,
                    'issued_to' => $stock->stockholder_name,
                    'issued_to_type' => 'Stockholder',
                    'par_value' => $stock->par_value,
                    'number' => $stock->number,
                    'amount' => $stock->amount,
                    'amount_in_words' => $stock->amount_in_words,
                    'date_issued' => $stock->date_issued,
                    'released_at' => $stock->date_issued ? Carbon::parse($stock->date_issued) : now(),
                    'president' => $stock->president,
                    'corporate_secretary' => $stock->corporate_secretary,
                    'document_path' => $stock->document_path,
                    'status' => 'released',
                    'installment_id' => null,
                ]);
            }

            StockTransferJournal::create([
                'entry_date' => $stock->date_issued ?? now()->toDateString(),
                'journal_no' => $this->nextJournalNo(),
                'ledger_folio' => $this->nextLedgerFolio(),
                'particulars' => sprintf('%s certificate stock added', $stock->certificate_type),
                'no_shares' => $stock->number,
                'transaction_type' => 'Certificate Stock',
                'certificate_no' => $stock->stock_number,
                'shareholder' => $stock->stockholder_name,
                'remarks' => 'Auto-generated from certificate stock creation.',
                'status' => $stock->status,
            ]);

            $ledger->update([
                'shares' => $ledger->shares ?: $stock->number,
                'certificate_no' => $ledger->certificate_no ?: $stock->stock_number,
                'status' => $ledger->status ?: 'active',
            ]);
        });

        return redirect()->route('stock-transfer-book.certificates')->with('success', 'Certificate created.');
    }

    public function show(StockTransferCertificate $stockTransferCertificate)
    {
        $stockTransferCertificate->load(['sourceCertificate', 'issuanceRequests', 'installment']);

        $stockNumber = $stockTransferCertificate->stock_number;
        $stockholderName = $stockTransferCertificate->stockholder_name;

        $relatedJournals = StockTransferJournal::query()
            ->where(function ($query) use ($stockNumber, $stockholderName) {
                if ($stockNumber) {
                    $query->orWhere('certificate_no', $stockNumber);
                }
                $this->applyNameTokens($query, $stockholderName, ['shareholder']);
            })
            ->latest()
            ->get();

        $relatedLedgers = StockTransferLedger::query()
            ->where(function ($query) use ($stockNumber, $stockholderName) {
                if ($stockNumber) {
                    $query->orWhere('certificate_no', $stockNumber);
                }
                $this->applyNameTokens($query, $stockholderName, ['first_name', 'middle_name', 'family_name']);
            })
            ->latest()
            ->get();

        $relatedInstallments = StockTransferInstallment::query()
            ->where(function ($query) use ($stockNumber, $stockholderName) {
                if ($stockNumber) {
                    $query->orWhere('stock_number', $stockNumber);
                }
                $this->applyNameTokens($query, $stockholderName, ['subscriber']);
            })
            ->latest()
            ->get();

        $relatedRequests = $this->hasIssuanceRequestsTable()
            ? StockTransferIssuanceRequest::query()
            ->where('certificate_id', $stockTransferCertificate->id)
            ->orWhereHas('certificate', function ($query) use ($stockNumber) {
                $query->where('stock_number', $stockNumber);
            })
            ->latest()
            ->get()
            : collect();

        $generatedPreviewPath = $stockTransferCertificate->document_path
            ? null
            : $this->generateCertificateTemplatePdf($stockTransferCertificate);

        $stockTransferCertificate->loadMissing('sourceCertificate');
        
        return view('corporate.stock-transfer-book.certificate-preview', [
            'certificate' => $stockTransferCertificate,
            'generatedPreviewUrl' => $generatedPreviewPath ? route('uploads.show', ['path' => $generatedPreviewPath]) : null,
            'liveTemplateUrl' => !$stockTransferCertificate->document_path ? route('stock-transfer-book.certificates.template', $stockTransferCertificate) : null,
            'relatedJournals' => $relatedJournals,
            'relatedLedgers' => $relatedLedgers,
            'relatedInstallments' => $relatedInstallments,
            'relatedRequests' => $relatedRequests,
            'voucherProfile' => $this->buildVoucherProfile($stockTransferCertificate, $relatedRequests, $relatedLedgers, $relatedJournals),
            'backRoute' => route('stock-transfer-book.certificates'),
            'editRoute' => route('stock-transfer-book.certificates.edit', $stockTransferCertificate),
        ]);
    }

    public function issue(StockTransferCertificate $stockTransferCertificate)
    {
        if ($this->isIssuedCertificate($stockTransferCertificate)) {
            return redirect()
                ->route('stock-transfer-book.certificates.show', $stockTransferCertificate)
                ->with('warning', 'Certificate has already been issued.');
        }

        if (($stockTransferCertificate->status ?? null) === 'voided') {
            return redirect()
                ->route('stock-transfer-book.certificates.show', $stockTransferCertificate)
                ->with('warning', 'Voided certificates cannot be issued.');
        }

        $installment = null;

        if (!empty($stockTransferCertificate->installment_id)) {
            $installment = StockTransferInstallment::find($stockTransferCertificate->installment_id);
        }

        if (!$installment && !empty($stockTransferCertificate->stock_number)) {
            $installment = StockTransferInstallment::query()
                ->where('stock_number', $stockTransferCertificate->stock_number)
                ->latest()
                ->first();
        }

        if ($installment && !$installment->isFullyPaid()) {
            return redirect()
                ->route('stock-transfer-book.certificates.show', $stockTransferCertificate)
                ->with('warning', 'Cannot issue certificate: installment is not fully paid.');
        }

        $documentPath = $stockTransferCertificate->document_path;

        if (!$documentPath) {
            $documentPath = $this->generateCertificateTemplatePdf(
                $stockTransferCertificate,
                null,
                'uploads/stock-transfer-book/certificates/' . ($stockTransferCertificate->stock_number ?: $stockTransferCertificate->id) . '.pdf'
            );

            if (!$documentPath) {
                $documentPath = $this->ensureDefaultCertificateTemplatePreview(
                    $stockTransferCertificate,
                    'uploads/stock-transfer-book/certificates/' . ($stockTransferCertificate->stock_number ?: $stockTransferCertificate->id) . '.pdf'
                );
            }
        }

        $stockTransferCertificate->update([
            'status' => 'issued',
            'released_at' => $stockTransferCertificate->released_at ?: now(),
            'date_issued' => $stockTransferCertificate->date_issued ?: now()->toDateString(),
            'document_path' => $documentPath,
        ]);

        return redirect()
            ->route('stock-transfer-book.certificates.show', $stockTransferCertificate)
            ->with('success', 'Certificate issued and locked for editing.');
    }

    public function templatePreview(StockTransferCertificate $stockTransferCertificate)
{
    $stockTransferCertificate->loadMissing('sourceCertificate');

    return view('corporate.stock-transfer-book.certificate-template-pdf', [
        'certificate' => $stockTransferCertificate,
    ]);
}

    protected function ensureDefaultCertificateTemplatePreview(StockTransferCertificate $certificate, ?string $targetPath = null): ?string
    {
        $templatePath = resource_path('doc_templates/Certificate-for-Shares-of-the-Capital-Stock.pdf');

        if (!is_file($templatePath)) {
            return null;
        }

        $targetPath = $targetPath ?: 'generated-previews/stock-transfer-book/certificates/' . ($certificate->stock_number ?: $certificate->id) . '.pdf';

        Storage::disk('public')->put($targetPath, file_get_contents($templatePath));

        return $targetPath;
    }

    protected function ensureCertificateTemplateAsset(): ?string
    {
        $templatePath = resource_path('doc_templates/Certificate-for-Shares-of-the-Capital-Stock.pdf');

        if (!is_file($templatePath)) {
            return null;
        }

        $targetPath = 'generated-previews/stock-transfer-book/certificates/_template/certificate-of-shares-capital-stock.pdf';

        if (!Storage::disk('public')->exists($targetPath)) {
            Storage::disk('public')->put($targetPath, file_get_contents($templatePath));
        }

        return $targetPath;
    }

    protected function generateCertificateTemplatePdf(StockTransferCertificate $certificate, ?string $templatePdfUrl = null, ?string $targetPath = null): ?string
    {
        return $this->generatePdfPreview(
            'corporate.stock-transfer-book.certificate-template-pdf',
            [
                'certificate' => $certificate,
                'templatePdfUrl' => $templatePdfUrl,
            ],
            $targetPath ?: 'generated-previews/stock-transfer-book/certificates/' . ($certificate->stock_number ?: $certificate->id) . '.pdf'
        );
    }

    public function edit(StockTransferCertificate $stockTransferCertificate)
    {
        return view('corporate.common.form', [
            'title' => 'Edit Certificate',
            'action' => route('stock-transfer-book.certificates.update', $stockTransferCertificate),
            'method' => 'PUT',
            'cancelRoute' => route('stock-transfer-book.certificates'),
            'fields' => $this->fields(),
            'item' => $stockTransferCertificate,
        ]);
    }

    public function update(Request $request, StockTransferCertificate $stockTransferCertificate)
    {
        if ($this->isIssuedCertificate($stockTransferCertificate)) {
            return redirect()
                ->route('stock-transfer-book.certificates.show', $stockTransferCertificate)
                ->with('warning', 'Issued certificates can no longer be edited. Cancel the certificate first if changes are needed.');
        }

        $data = $this->validateData($request);
        $data['document_path'] = $this->handleUpload($request, 'document_path', $stockTransferCertificate->document_path);

        $stockTransferCertificate->update($data);

        return redirect()->route('stock-transfer-book.certificates')->with('success', 'Certificate updated.');
    }

    public function destroy(Request $request, StockTransferCertificate $stockTransferCertificate)
    {
        $data = $request->validate([
            'cancellation_date' => ['required', 'date'],
            'cancellation_effective_date' => ['required', 'date'],
            'cancellation_reason' => ['required', 'string', 'in:Delinquent,Buy-back,Redemption,Treasury Cancellation,Capital Reduction,Others'],
            'cancellation_types' => ['required', 'array', 'min:1'],
            'cancellation_types.*' => ['required', 'string', 'in:Delinquent,Buy-back,Redemption,Treasury Cancellation,Capital Reduction,Others'],
            'cancellation_other_reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'board_resolution' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'secretary_certificate_file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'scanned_stock_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'affidavit_of_loss' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'shareholder_valid_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'subscription_agreement' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'deed_or_buyback_agreement' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'redemption_agreement' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'proof_of_payment_ledger' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notice_of_delinquency' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'proof_of_notice_publication' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'amended_articles' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'audited_financial_statements' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'treasurer_affidavit' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'sec_filing_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $selectedTypes = collect($data['cancellation_types'] ?? []);

        if (
            !$request->hasFile('scanned_stock_certificate')
            && !($request->hasFile('affidavit_of_loss') && $request->hasFile('shareholder_valid_id'))
        ) {
            return back()->withErrors([
                'scanned_stock_certificate' => 'Upload the scanned stock certificate, or provide both the Affidavit of Loss and Valid ID of Shareholder.',
            ])->withInput();
        }

        if (($request->hasFile('affidavit_of_loss') xor $request->hasFile('shareholder_valid_id'))) {
            return back()->withErrors([
                'affidavit_of_loss' => 'Lost stock certificate cases require both the Affidavit of Loss and Valid ID of Shareholder.',
            ])->withInput();
        }

        if ($selectedTypes->contains('Delinquent') && (!$request->hasFile('notice_of_delinquency') || !$request->hasFile('proof_of_notice_publication'))) {
            return back()->withErrors([
                'notice_of_delinquency' => 'Delinquent cancellation requires the Notice of Delinquency and Proof of Notice / Publication files.',
            ])->withInput();
        }

        if ($selectedTypes->contains('Buy-back') && !$request->hasFile('deed_or_buyback_agreement')) {
            return back()->withErrors([
                'deed_or_buyback_agreement' => 'Buy-back cancellation requires a Deed of Sale / Buy-back Agreement.',
            ])->withInput();
        }

        if ($selectedTypes->contains('Buy-back') && !$request->hasFile('proof_of_payment_ledger')) {
            return back()->withErrors([
                'proof_of_payment_ledger' => 'Buy-back cancellation requires Proof of Payment / Ledger.',
            ])->withInput();
        }

        if ($selectedTypes->contains('Redemption') && !$request->hasFile('redemption_agreement')) {
            return back()->withErrors([
                'redemption_agreement' => 'Redemption cancellation requires a Redemption Agreement.',
            ])->withInput();
        }

        if ($selectedTypes->contains('Redemption') && !$request->hasFile('proof_of_payment_ledger')) {
            return back()->withErrors([
                'proof_of_payment_ledger' => 'Redemption cancellation requires Proof of Payment / Ledger.',
            ])->withInput();
        }

        if ($selectedTypes->contains('Capital Reduction') && (
            !$request->hasFile('amended_articles')
            || !$request->hasFile('audited_financial_statements')
            || !$request->hasFile('treasurer_affidavit')
            || !$request->hasFile('sec_filing_proof')
        )) {
            return back()->withErrors([
                'amended_articles' => 'Capital Reduction requires Amended Articles, Audited Financial Statements, Treasurer’s Affidavit, and SEC filing proof.',
            ])->withInput();
        }

        $uploadedCancellationFiles = collect([
            'board_resolution',
            'secretary_certificate_file',
            'scanned_stock_certificate',
            'affidavit_of_loss',
            'shareholder_valid_id',
            'subscription_agreement',
            'deed_or_buyback_agreement',
            'redemption_agreement',
            'proof_of_payment_ledger',
            'notice_of_delinquency',
            'proof_of_notice_publication',
            'amended_articles',
            'audited_financial_statements',
            'treasurer_affidavit',
            'sec_filing_proof',
        ])->filter(fn($field) => $request->hasFile($field))
            ->map(fn($field) => $request->file($field)->getClientOriginalName())
            ->values()
            ->all();

        DB::transaction(function () use ($stockTransferCertificate, $data, $uploadedCancellationFiles) {
            $stockTransferCertificate->status = 'voided';
            $stockTransferCertificate->save();

            StockTransferJournal::create([
                'entry_date' => $data['cancellation_date'],
                'journal_no' => $this->nextJournalNo(),
                'ledger_folio' => $this->nextLedgerFolio(),
                'particulars' => sprintf('%s certificate voided', $stockTransferCertificate->certificate_type ?: 'Stock'),
                'no_shares' => $stockTransferCertificate->number,
                'transaction_type' => 'Cancellation',
                'certificate_no' => $stockTransferCertificate->stock_number,
                'shareholder' => $stockTransferCertificate->stockholder_name,
                'remarks' => trim(implode(' | ', array_filter([
                    'Auto-generated from certificate void action.',
                    'Effective Date: ' . $data['cancellation_effective_date'],
                    'Reason: ' . $data['cancellation_reason'],
                    'Types: ' . implode(', ', $data['cancellation_types'] ?? []),
                    !empty($data['cancellation_other_reason']) ? 'Others: ' . $data['cancellation_other_reason'] : null,
                    !empty($data['remarks']) ? 'Remarks: ' . $data['remarks'] : null,
                    !empty($uploadedCancellationFiles) ? 'Files: ' . implode(', ', $uploadedCancellationFiles) : null,
                ]))),
                'status' => 'voided',
            ]);
        });

        return redirect()->route('stock-transfer-book.certificates')->with('success', 'Certificate voided.');
    }

    public function storeRequest(Request $request)
    {
        if (!$this->hasCertificateWorkflowColumns() || !$this->hasIssuanceRequestsTable()) {
            return back()->withErrors([
                'request_type' => 'Request for issuance requires the latest stock-transfer migration. Please run php artisan migrate.',
            ])->withInput();
        }

        $data = $request->validate([
            'reference_no' => ['nullable', 'string', 'max:255'],
            'requested_at' => ['nullable', 'date'],
            'request_type' => ['required', 'string', 'max:255'],
            'issuance_type' => ['required', 'string', 'in:COS,CV'],
            'requester' => ['required', 'string', 'max:255'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'issued_by' => ['nullable', 'string', 'max:255'],
            'certificate_id' => ['required', 'integer', 'exists:stock_transfer_certificates,id'],
            'notes' => ['nullable', 'string'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $certificate = StockTransferCertificate::query()
            ->whereKey($data['certificate_id'])
            ->whereNull('source_certificate_id')
            ->first();

        if (!$certificate) {
            return back()->withErrors([
                'certificate_id' => 'Please select a valid certificate stock record.',
            ])->withInput();
        }

        if (blank($data['requester']) && !blank($certificate->stockholder_name)) {
            $data['requester'] = $certificate->stockholder_name;
        }

        $documentPath = null;
        if (Schema::hasColumn('stock_transfer_issuance_requests', 'document_path')) {
            $documentPath = $this->handleUpload($request, 'document_path');
        }

        $requestPayload = [
            'reference_no' => $this->nextIssuanceRequestReference(),
            'requested_at' => $data['requested_at'] ?: now(),
            'request_type' => $data['request_type'],
            'issuance_type' => $data['issuance_type'],
            'requester' => $data['requester'],
            'received_by' => $data['received_by'] ?: auth()->user()?->name,
            'issued_by' => $data['issued_by'] ?: auth()->user()?->name,
            'certificate_id' => $certificate->id,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ];

        if (Schema::hasColumn('stock_transfer_issuance_requests', 'document_path')) {
            $requestPayload['document_path'] = $documentPath;
        }

        StockTransferIssuanceRequest::create($requestPayload);

        return redirect()->route('stock-transfer-book.certificates')->with('success', 'Issuance request added.');
    }

    public function showRequest(StockTransferIssuanceRequest $stockTransferIssuanceRequest)
    {
        $stockTransferIssuanceRequest->load(['certificate', 'journal', 'ledger']);

        $generatedPreviewPath = $this->generatePdfPreview(
            'corporate.stock-transfer-book.issuance-request-pdf',
            ['requestRecord' => $stockTransferIssuanceRequest],
            'generated-previews/stock-transfer-book/issuance-requests/' . ($stockTransferIssuanceRequest->reference_no ?: $stockTransferIssuanceRequest->id) . '.pdf'
        );

        return view('corporate.stock-transfer-book.issuance-request-preview', [
            'requestRecord' => $stockTransferIssuanceRequest,
            'backRoute' => route('stock-transfer-book.certificates'),
            'uploadedRequestUrl' => $stockTransferIssuanceRequest->document_path
                ? route('uploads.show', ['path' => $stockTransferIssuanceRequest->document_path])
                : null,
            'generatedPreviewUrl' => $generatedPreviewPath
                ? route('uploads.show', ['path' => $generatedPreviewPath])
                : null,
        ]);
    }

    public function approveRequest(StockTransferIssuanceRequest $stockTransferIssuanceRequest)
    {
        if (!$this->hasCertificateWorkflowColumns() || !$this->hasIssuanceRequestsTable()) {
            return redirect()->route('stock-transfer-book.certificates')->withErrors([
                'request_type' => 'Approving issuance requests requires the latest stock-transfer migration. Please run php artisan migrate.',
            ]);
        }

        if ($stockTransferIssuanceRequest->status === 'approved') {
            return redirect()
                ->route('stock-transfer-book.certificates.requests.show', $stockTransferIssuanceRequest)
                ->with('success', 'Issuance request is already approved.');
        }

        $stockTransferIssuanceRequest->load('certificate');
        $certificate = $stockTransferIssuanceRequest->certificate;

        if (!$certificate) {
            return redirect()->route('stock-transfer-book.certificates')->withErrors([
                'certificate' => 'The selected certificate stock is missing.',
            ]);
        }

        DB::transaction(function () use ($stockTransferIssuanceRequest, $certificate) {
            $journal = StockTransferJournal::create([
                'entry_date' => now()->toDateString(),
                'journal_no' => $this->nextJournalNo(),
                'ledger_folio' => $this->nextLedgerFolio(),
                'particulars' => sprintf('%s approved for %s', $stockTransferIssuanceRequest->request_type, $stockTransferIssuanceRequest->requester),
                'no_shares' => 0,
                'transaction_type' => 'Issuance Request',
                'certificate_no' => $certificate->stock_number,
                'shareholder' => $stockTransferIssuanceRequest->requester,
                'remarks' => sprintf(
                    'Approved issuance request %s using %s %s.',
                    $stockTransferIssuanceRequest->reference_no,
                    $stockTransferIssuanceRequest->issuance_type,
                    $certificate->stock_number
                ),
                'status' => 'approved',
            ]);

            $voucher = StockTransferCertificate::query()
                ->whereNotNull('source_certificate_id')
                ->where('source_certificate_id', $certificate->id)
                ->where('stock_number', $certificate->stock_number)
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', 'voided');
                })
                ->latest()
                ->first();

            if ($voucher) {
                $voucher->update([
                    'issuance_request_id' => $stockTransferIssuanceRequest->id,
                    'certificate_type' => $stockTransferIssuanceRequest->issuance_type,
                    'issued_to' => $stockTransferIssuanceRequest->requester,
                    'issued_to_type' => 'Requester',
                    'date_issued' => now()->toDateString(),
                    'released_at' => now(),
                    'status' => 'released',
                ]);
            } else {
                $voucher = StockTransferCertificate::create([
                    'source_certificate_id' => $certificate->id,
                    'issuance_request_id' => $stockTransferIssuanceRequest->id,
                    'date_uploaded' => $certificate->date_uploaded,
                    'uploaded_by' => $certificate->uploaded_by,
                    'corporation_name' => $certificate->corporation_name,
                    'company_reg_no' => $certificate->company_reg_no,
                    'certificate_type' => $stockTransferIssuanceRequest->issuance_type,
                    'stock_number' => $certificate->stock_number,
                    'stockholder_name' => $certificate->stockholder_name ?: $stockTransferIssuanceRequest->requester,
                    'issued_to' => $stockTransferIssuanceRequest->requester,
                    'issued_to_type' => 'Requester',
                    'par_value' => $certificate->par_value,
                    'number' => $certificate->number,
                    'amount' => $certificate->amount,
                    'amount_in_words' => $certificate->amount_in_words,
                    'date_issued' => now()->toDateString(),
                    'released_at' => now(),
                    'president' => $certificate->president,
                    'corporate_secretary' => $certificate->corporate_secretary,
                    'document_path' => $certificate->document_path,
                    'status' => 'released',
                    'installment_id' => null,
                ]);
            }

            $stockTransferIssuanceRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->user()?->name,
                'issued_by' => $stockTransferIssuanceRequest->issued_by ?: auth()->user()?->name,
                'journal_id' => $journal->id,
                'ledger_id' => null,
                'certificate_id' => $certificate->id,
                'notes' => trim(implode(' ', array_filter([
                    $stockTransferIssuanceRequest->notes,
                    'Voucher Ref: ' . ($voucher->stock_number ?: $certificate->stock_number),
                ]))),
            ]);
        });

        return redirect()
            ->route('stock-transfer-book.certificates.requests.show', $stockTransferIssuanceRequest)
            ->with('success', 'Issuance request approved.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'date_uploaded', 'label' => 'Date Uploaded', 'type' => 'date'],
            ['name' => 'uploaded_by', 'label' => 'Uploaded By', 'type' => 'text'],
            ['name' => 'corporation_name', 'label' => 'Corporation Name', 'type' => 'text'],
            ['name' => 'company_reg_no', 'label' => 'Company Reg. No.', 'type' => 'text'],
            ['name' => 'certificate_type', 'label' => 'Certificate Type', 'type' => 'select', 'options' => ['COS', 'CV']],
            ['name' => 'stock_number', 'label' => 'Stock Number', 'type' => 'text'],
            ['name' => 'stockholder_name', 'label' => 'Name of Stockholder', 'type' => 'text'],
            ['name' => 'par_value', 'label' => 'PAR', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'number', 'label' => 'Number', 'type' => 'number'],
            ['name' => 'amount', 'label' => 'Amount (PhP)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'amount_in_words', 'label' => 'Amount in Words', 'type' => 'text'],
            ['name' => 'date_issued', 'label' => 'Date Issued', 'type' => 'date'],
            ['name' => 'president', 'label' => 'President', 'type' => 'text'],
            ['name' => 'corporate_secretary', 'label' => 'Corporate Secretary', 'type' => 'text'],
            ['name' => 'document_path', 'label' => 'Upload Document (PDF)', 'type' => 'file'],
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'date_uploaded' => ['nullable', 'date'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'corporation_name' => ['nullable', 'string', 'max:255'],
            'company_reg_no' => ['nullable', 'string', 'max:255'],
            'certificate_type' => ['nullable', 'string', 'in:COS,CV'],
            'stock_number' => ['nullable', 'string', 'max:255'],
            'stockholder_name' => ['nullable', 'string', 'max:255'],
            'issued_to' => ['nullable', 'string', 'max:255'],
            'issued_to_type' => ['nullable', 'string', 'max:255'],
            'par_value' => ['nullable', 'numeric'],
            'number' => ['nullable', 'integer'],
            'amount' => ['nullable', 'numeric'],
            'amount_in_words' => ['nullable', 'string', 'max:255'],
            'date_issued' => ['nullable', 'date'],
            'released_at' => ['nullable', 'date'],
            'president' => ['nullable', 'string', 'max:255'],
            'corporate_secretary' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);
    }

    private function resolveLedger(?string $stockNumber, ?string $stockholderName): ?StockTransferLedger
    {
        $query = StockTransferLedger::query();
        $query->whereNull('journal_id');
        $query->where(function ($sub) use ($stockNumber, $stockholderName) {
            if ($stockNumber) {
                $sub->orWhere('certificate_no', $stockNumber);
            }
            $this->applyNameTokens($sub, $stockholderName, ['first_name', 'middle_name', 'family_name']);
        });

        return $query->first();
    }

    private function isIssuedCertificate(StockTransferCertificate $certificate): bool
    {
        return in_array(strtolower((string) ($certificate->status ?? '')), ['issued', 'released', 'approved'], true);
    }

    private function buildVoucherProfile(
        StockTransferCertificate $certificate,
        $relatedRequests,
        $relatedLedgers,
        $relatedJournals
    ): ?array {
        if (!$certificate->source_certificate_id) {
            return null;
        }

        $requestRecord = $certificate->issuanceRequests()->latest()->first()
            ?: collect($relatedRequests ?? collect())->firstWhere('certificate_id', $certificate->id);

        $sourceCertificate = $certificate->sourceCertificate;

        $ledger = collect($relatedLedgers ?? collect())
            ->first(fn($entry) => ($entry->journal_id ?? null) === null)
            ?: collect($relatedLedgers ?? collect())->first();

        $journal = collect($relatedJournals ?? collect())->first();

        return [
            ['label' => 'Voucher No.', 'value' => $requestRecord?->reference_no ?: $certificate->stock_number ?: '-'],
            ['label' => 'Date Prepared', 'value' => optional($certificate->created_at)->format('M d, Y') ?: '-'],
            ['label' => 'Prepared By', 'value' => $certificate->uploaded_by ?: '-'],
            ['label' => 'Verified By', 'value' => $requestRecord?->received_by ?: '-'],
            ['label' => 'Approved By', 'value' => $requestRecord?->approved_by ?: '-'],
            ['label' => 'Shareholder Name', 'value' => $certificate->stockholder_name ?: '-'],
            ['label' => 'Address', 'value' => $ledger?->address ?: '-'],
            ['label' => 'Nationality', 'value' => $ledger?->nationality ?: '-'],
            ['label' => 'TIN', 'value' => $ledger?->tin ?: '-'],
            ['label' => 'Stock Certificate No.', 'value' => $sourceCertificate?->stock_number ?: $certificate->stock_number ?: '-'],
            ['label' => 'Number of Shares', 'value' => $certificate->number ?: '-'],
            ['label' => 'Class of Shares', 'value' => 'Common'],
            ['label' => 'Par Value', 'value' => $certificate->par_value !== null ? number_format((float) $certificate->par_value, 2, '.', ',') : '-'],
            ['label' => 'Total Value', 'value' => $certificate->amount !== null ? number_format((float) $certificate->amount, 2, '.', ',') : '-'],
            ['label' => 'Date Issued', 'value' => optional($certificate->date_issued)->format('M d, Y') ?: '-'],
            ['label' => 'Certificate Action', 'value' => $this->resolveVoucherAction($certificate, $requestRecord)],
            ['label' => 'Status', 'value' => ucfirst((string) ($certificate->status ?: 'draft'))],
            ['label' => 'Date Reason', 'value' => $requestRecord?->request_type ?: '-'],
            ['label' => 'Date Released', 'value' => optional($certificate->released_at)->format('M d, Y h:i A') ?: '-'],
            ['label' => 'Released By', 'value' => $requestRecord?->issued_by ?: '-'],
            ['label' => 'Mode of Release', 'value' => $certificate->issued_to_type ?: '-'],
            ['label' => 'Received By', 'value' => $certificate->issued_to ?: '-'],
            ['label' => 'Relationship to Shareholder', 'value' => '-'],
            ['label' => 'ID Type and Number', 'value' => '-'],
            ['label' => 'Signature / Acknowledgment', 'value' => '-'],
            ['label' => 'Date Surrendered', 'value' => '-'],
            ['label' => 'Received By (Company Representative)', 'value' => $requestRecord?->received_by ?: '-'],
            ['label' => 'Condition of Certificate', 'value' => '-'],
            ['label' => 'Replacement Required (Yes/No)', 'value' => str_contains(strtolower((string) ($requestRecord?->request_type ?? '')), 'loss') || str_contains(strtolower((string) ($requestRecord?->request_type ?? '')), 'damage') ? 'Yes' : 'No'],
            ['label' => 'Buyer Name (if Sold)', 'value' => '-'],
            ['label' => 'Buyer Address', 'value' => '-'],
            ['label' => 'Date of Sale / Transfer', 'value' => '-'],
            ['label' => 'Transfer Reference No.', 'value' => $journal?->journal_no ?: '-'],
            ['label' => 'STB Updated (Yes/No)', 'value' => $journal ? 'Yes' : 'No'],
            ['label' => 'STB Reference / Page No.', 'value' => $journal?->ledger_folio ?: '-'],
            ['label' => 'Date of STB Update', 'value' => optional($journal?->entry_date)->format('M d, Y') ?: '-'],
        ];
    }

    private function resolveVoucherAction(StockTransferCertificate $certificate, ?StockTransferIssuanceRequest $requestRecord): string
    {
        $requestType = strtolower((string) ($requestRecord?->request_type ?? ''));

        if (str_contains($requestType, 'loss')) {
            return 'Replaced';
        }

        if (str_contains($requestType, 'damage')) {
            return 'Reissued';
        }

        if (($certificate->status ?? null) === 'voided') {
            return 'Cancelled';
        }

        return 'Issued';
    }

    private function hasCertificateWorkflowColumns(): bool
    {
        return Schema::hasColumn('stock_transfer_certificates', 'source_certificate_id')
            && Schema::hasColumn('stock_transfer_certificates', 'certificate_type');
    }

    private function hasIssuanceRequestsTable(): bool
    {
        return Schema::hasTable('stock_transfer_issuance_requests');
    }

    private function amountToWords($amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $amount = round((float) $amount, 2);
        $whole = (int) floor($amount);
        $cents = (int) round(($amount - $whole) * 100);

        $words = $this->integerToWords($whole) . ' Pesos';
        if ($cents > 0) {
            $words .= ' and ' . $this->integerToWords($cents) . ' Centavos';
        }

        return trim($words . ' Only');
    }

    private function integerToWords(int $number): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($number < 20) {
            return $number === 0 ? 'Zero' : $ones[$number];
        }

        if ($number < 100) {
            return trim($tens[intdiv($number, 10)] . ' ' . $ones[$number % 10]);
        }

        if ($number < 1000) {
            return trim($ones[intdiv($number, 100)] . ' Hundred ' . $this->integerToWords($number % 100));
        }

        if ($number < 1000000) {
            return trim($this->integerToWords(intdiv($number, 1000)) . ' Thousand ' . $this->integerToWords($number % 1000));
        }

        if ($number < 1000000000) {
            return trim($this->integerToWords(intdiv($number, 1000000)) . ' Million ' . $this->integerToWords($number % 1000000));
        }

        return trim($this->integerToWords(intdiv($number, 1000000000)) . ' Billion ' . $this->integerToWords($number % 1000000000));
    }

    private function nextIssuanceRequestReference(): string
    {
        $lastReference = StockTransferIssuanceRequest::query()
            ->latest('id')
            ->value('reference_no');

        $nextNumber = 1;

        if ($lastReference && preg_match('/(\d+)$/', $lastReference, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'REQ-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
