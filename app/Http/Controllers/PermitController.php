<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Http\Controllers\Concerns\SyncsDeadlineTownHallMemo;
use App\Models\Permit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermitController extends Controller
{
    use HandlesCorporateRepositoryRecords;
    use SyncsDeadlineTownHallMemo;

    public const PERMIT_TYPES = [
        'Business Permit',
        "Mayor's Permit",
        'Barangay Business Clearance',
        'Barangay Clearance',
        'Community Tax Certificate / Cedula',
        'Building Permit',
        'Occupancy Permit',
        'Demolition Permit',
        'Fencing Permit',
        'Excavation Permit',
        'Electrical Permit',
        'Mechanical Permit',
        'Plumbing Permit',
        'Electronics Permit',
        'Sanitary Permit',
        'Fire Safety Inspection Certificate',
        'Fire Clearance',
        'Zoning Clearance',
        'Locational Clearance',
        'Development Permit',
        'Environmental Permit',
        'Waste Disposal Permit',
        'Septage Permit',
        'Signage Permit',
        'Business Sign Permit',
        'Advertising Permit',
        'Market Permit',
        'Vendor Permit',
        'Tricycle Franchise Permit',
        'Transport Permit',
        'Terminal Permit',
        'Tourism Permit',
        'Special Use Permit',
        'Special Event Permit',
        'Liquor Permit',
        'Night Operation Permit',
        'Amusement Permit',
        'Other',
    ];

    public const LOCATION_DATA = [
        'Cebu' => [
            'Cebu City' => ['Apas', 'Lahug', 'Mabolo', 'Talamban'],
            'Mandaue City' => ['Centro', 'Subangdaku', 'Banilad', 'Tipolo'],
            'Lapu-Lapu City' => ['Pajo', 'Pusok', 'Basak', 'Maribago'],
            'Liloan' => ['Cotcot', 'Poblacion', 'Yati', 'Tayud'],
        ],
        'Metro Manila' => [
            'Makati City' => ['Bel-Air', 'Poblacion', 'San Lorenzo', 'Urdaneta'],
            'Taguig City' => ['Fort Bonifacio', 'Pinagsama', 'Ususan', 'Western Bicutan'],
            'Quezon City' => ['Bagumbayan', 'Diliman', 'New Manila', 'Tandang Sora'],
        ],
        'Davao del Sur' => [
            'Davao City' => ['Buhangin', 'Matina', 'Poblacion', 'Talomo', 'Toril'],
        ],
        'Iloilo' => [
            'Iloilo City' => ['Arevalo', 'City Proper', 'Jaro', 'Mandurriao', 'Molo'],
        ],
    ];

    private function canApproveCorporate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->hasPermission('approve_corporate');
    }

    private function canEditRecord(Permit $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status, ['Uploaded', 'Reverted'], true);
    }

    public function page()
    {
        return view('corporate.lgu', [
            'companyDefaults' => $this->latestCorporateCompany(),
            'permitTypes' => self::PERMIT_TYPES,
            'locationData' => self::LOCATION_DATA,
            'statuses' => ['Active', 'For Renewal', 'Expiring Soon', 'Expired'],
        ]);
    }

    public function index(Request $request)
    {
        $query = Permit::query();

        if (! $this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        if ($request->filled('workflow_status') && $request->workflow_status !== 'all') {
            $query->where('workflow_status', ucfirst($request->workflow_status));
        }

        if ($request->filled('permit_type') && $request->permit_type !== 'All Permit Types') {
            $query->where('permit_type', $request->permit_type);
        }

        return response()->json(
            $query->orderByDesc('renewal_date')->orderByDesc('created_at')->get()
                ->map(fn (Permit $permit) => $this->transformRecord($permit))
                ->values()
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);
        $company = $this->latestCorporateCompany();
        $user = $this->currentUserLabel($request);
        $isApprover = $this->canApproveCorporate();
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'corporate/lgu/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'corporate/lgu/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $permit = Permit::create([
            'company_id' => $company['company_id'],
            'company_name' => $company['company_name'],
            'province' => $validated['province'],
            'city_municipality' => $validated['city_municipality'],
            'barangay' => $validated['barangay'],
            'permit_type' => $this->resolveOtherChoice($validated['permit_type'], $validated['permit_type_other'] ?? null),
            'document_type' => 'LGU Compliance Document',
            'permit_number' => $validated['permit_number'],
            'date_of_registration' => $validated['date_registered'] ?? null,
            'renewal_date' => $validated['renewal_date'] ?? null,
            'expiration_date_of_registration' => $validated['renewal_date'] ?? null,
            'total_permit_fee' => $validated['total_permit_fee'] ?? null,
            'user' => $user,
            'tin' => null,
            'document_name' => $primaryDocument['name'] ?? null,
            'document_path' => $primaryDocument['path'] ?? null,
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'uploaded_by' => $user,
            'date_uploaded_at' => now(),
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'approval_status' => $isApprover ? 'Approved' : 'Pending',
            'workflow_status' => $isApprover ? 'Accepted' : 'Submitted',
            'submitted_by' => Auth::id(),
            'approved_by' => $isApprover ? Auth::id() : null,
            'approved_at' => $isApprover ? now() : null,
            'review_note' => null,
        ]);

        $this->syncPermitDeadline($permit);

        return response()->json([
            'message' => 'LGU compliance record saved successfully.',
            'data' => $this->transformRecord($permit->fresh()),
        ], 201);
    }

    public function show($id)
    {
        $record = Permit::findOrFail($id);

        if (! $this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return response()->json($this->transformRecord($record));
    }

    public function update(Request $request, $id)
    {
        $record = Permit::findOrFail($id);

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request, false);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($record->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'corporate/lgu/drafts'));
        $approvedDocuments = $this->appendDocuments($record->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'corporate/lgu/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $record->update([
            'province' => $validated['province'],
            'city_municipality' => $validated['city_municipality'],
            'barangay' => $validated['barangay'],
            'permit_type' => $this->resolveOtherChoice($validated['permit_type'], $validated['permit_type_other'] ?? null),
            'permit_number' => $validated['permit_number'],
            'date_of_registration' => $validated['date_registered'] ?? null,
            'renewal_date' => $validated['renewal_date'] ?? null,
            'expiration_date_of_registration' => $validated['renewal_date'] ?? null,
            'total_permit_fee' => $validated['total_permit_fee'] ?? null,
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'document_name' => $primaryDocument['name'] ?? $record->document_name,
            'document_path' => $primaryDocument['path'] ?? $record->document_path,
            'approval_status' => ($record->workflow_status ?? 'Uploaded') === 'Reverted' ? 'Pending' : $record->approval_status,
            'review_note' => ($record->workflow_status ?? 'Uploaded') === 'Reverted' ? null : $record->review_note,
        ]);

        $this->syncPermitDeadline($record->fresh());

        return response()->json([
            'message' => 'LGU compliance record updated successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    public function uploadDocument(Request $request, $id)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $record = Permit::findOrFail($id);

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $file = $request->file('document');
        $path = $file->store('corporate/lgu/drafts', 'public');
        $documents = $this->appendDocuments($record->draft_documents, [[
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => $this->publicDocumentUrl($path),
            'uploaded_by' => Auth::user()?->name ?? 'System User',
            'uploaded_at' => now()->toDateTimeString(),
        ]]);

        $record->update([
            'draft_documents' => $documents,
            'document_name' => $file->getClientOriginalName(),
            'document_path' => $path,
            'last_updated_by' => Auth::user()?->name ?? 'System User',
            'last_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Document attached successfully.']);
    }

    public function submit($id)
    {
        $record = Permit::findOrFail($id);

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record cannot be submitted.');
        }

        $record->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
        ]);

        return response()->json(['message' => 'LGU submitted for approval.']);
    }

    private function validatedPayload(Request $request, bool $documentOptional = true): array
    {
        return $request->validate(array_merge([
            'province' => ['required', 'string', 'max:255'],
            'city_municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'permit_type' => ['required', 'string', 'max:255'],
            'permit_type_other' => ['nullable', 'string', 'max:255'],
            'permit_number' => ['required', 'string', 'max:255'],
            'date_registered' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'total_permit_fee' => ['nullable', 'numeric', 'min:0'],
        ], $this->commonDocumentValidation()));
    }

    private function transformRecord(Permit $permit): array
    {
        $draftDocuments = $this->documentLinks($permit->draft_documents);
        $approvedDocuments = $this->documentLinks($permit->approved_documents);

        return [
            'id' => $permit->id,
            'company' => $permit->company_name ?: $permit->client ?: 'Latest Approved GIS Company',
            'province' => $permit->province,
            'city_municipality' => $permit->city_municipality,
            'barangay' => $permit->barangay,
            'permit_type' => $permit->permit_type,
            'permit_number' => $permit->permit_number,
            'date_registered' => $permit->date_of_registration?->format('Y-m-d'),
            'renewal_date' => $permit->renewal_date?->format('Y-m-d') ?: $permit->expiration_date_of_registration?->format('Y-m-d'),
            'total_permit_fee' => $permit->total_permit_fee,
            'status' => $permit->status,
            'uploaded_by' => $permit->uploaded_by ?: $permit->user,
            'date_uploaded' => $permit->date_uploaded_at?->format('Y-m-d H:i:s') ?: $permit->created_at?->format('Y-m-d H:i:s'),
            'last_updated_by' => $permit->last_updated_by,
            'last_updated_date' => $permit->last_updated_at?->format('Y-m-d H:i:s') ?: $permit->updated_at?->format('Y-m-d H:i:s'),
            'workflow_status' => $permit->workflow_status ?? 'Uploaded',
            'approval_status' => $permit->approval_status ?? 'Pending',
            'review_note' => $permit->review_note,
            'document_name' => $permit->document_name,
            'document_url' => $draftDocuments[0]['url'] ?? $approvedDocuments[0]['url'] ?? $this->publicDocumentUrl($permit->document_path),
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'can_edit' => $this->canEditRecord($permit),
            'can_submit' => (int) $permit->submitted_by === (int) Auth::id()
                && in_array($permit->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true),
        ];
    }

    private function syncPermitDeadline(Permit $permit): void
    {
        $this->syncDeadlineTownHallMemo(
            $permit,
            $permit->renewal_date?->toDateString() ?: $permit->expiration_date_of_registration?->toDateString(),
            'LGU Compliance',
            trim(($permit->permit_type ?: 'LGU Permit') . ' - ' . ($permit->permit_number ?: $permit->company_name)),
            'permits.show'
        );
    }

    public function showMayorPermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.mayors-permit', compact('permit'));
    }

    public function showBarangayBusinessPermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.barangay-business-permit', compact('permit'));
    }

    public function showFirePermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.fire-permit', compact('permit'));
    }

    public function showSanitaryPermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.sanitary-permit', compact('permit'));
    }

    public function showOboPermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.obo-permit', compact('permit'));
    }
}
