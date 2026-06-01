<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesCorporateDocumentNumbers;
use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Models\DirectorOfficer;
use App\Models\GisRecord;
use App\Models\Minute;
use App\Models\Notice;
use App\Models\Resolution;
use App\Models\SecretaryCertificate;
use App\Models\Stockholder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SecretaryCertificateController extends Controller
{
    use GeneratesCorporateDocumentNumbers;
    use GeneratesPdfPreview;
    use HandlesUploads;

    public function index()
    {
        $certificates = SecretaryCertificate::with(['notice', 'resolution', 'minute'])->latest()->get();
        $resolutions = Resolution::with(['notice', 'minute'])->orderBy('date_of_meeting')->get()
            ->map(function (Resolution $resolution) {
                $resolution->full_resolution_body = $this->completeResolutionBodyForCertificate($resolution);
                return $resolution;
            });
        $minutes = Minute::with('notice')->orderBy('date_of_meeting')->get();

        return view('corporate.secretary-certificates.index', [
            'certificates' => $certificates,
            'resolutions' => $resolutions,
            'minutes' => $minutes,
            'nextCertificateNumber' => $this->nextSecretaryCertificateNumber(),
        ]);
    }

    public function create()
    {
        return view('corporate.common.form', [
            'title' => 'Add Secretary Certificate',
            'action' => route('secretary-certificates.store'),
            'method' => 'POST',
            'cancelRoute' => route('secretary-certificates'),
            'fields' => $this->fields(),
            'item' => new SecretaryCertificate([
                'certificate_no' => $this->nextSecretaryCertificateNumber(),
                'date_uploaded' => now()->toDateString(),
                'uploaded_by' => auth()->user()?->name ?? '',
                'date_issued' => now()->toDateString(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data = $this->mergeMeetingSourceData($data);
        $data['document_path'] = $this->handleUpload($request, 'document_path');
        $data['certificate_no'] = $data['certificate_no'] ?: $this->nextSecretaryCertificateNumber();
        $data = $this->filterPersistableData($data);

        SecretaryCertificate::create($data);

        return redirect()->route('secretary-certificates')->with('success', 'Secretary certificate created.');
    }

    public function show(SecretaryCertificate $secretaryCertificate)
    {
        $secretaryCertificate->load(['notice.attendees', 'resolution.notice.attendees', 'resolution.minute.notice.attendees', 'minute.notice.attendees']);
        $corporateContext = $this->corporateContextForCertificate($secretaryCertificate);

        $generatedDraftPath = $this->generatePdfPreview(
            'corporate.secretary-certificates.pdf',
            ['certificate' => $secretaryCertificate, 'corporateContext' => $corporateContext],
            'generated-previews/secretary-certificates/' . ($secretaryCertificate->certificate_no ?: $secretaryCertificate->id) . '-draft.pdf'
        );

        return view('corporate.secretary-certificates.preview', [
            'certificate' => $secretaryCertificate,
            'corporateContext' => $corporateContext,
            'generatedDraftUrl' => $generatedDraftPath ? route('uploads.show', ['path' => $generatedDraftPath]) : null,
            'backRoute' => route('secretary-certificates'),
            'editRoute' => route('secretary-certificates.edit', $secretaryCertificate),
            'updateRoute' => route('secretary-certificates.update', $secretaryCertificate),
            'deleteRoute' => route('secretary-certificates.destroy', $secretaryCertificate),
        ]);
    }

    public function edit(SecretaryCertificate $secretaryCertificate)
    {
        return view('corporate.common.form', [
            'title' => 'Edit Secretary Certificate',
            'action' => route('secretary-certificates.update', $secretaryCertificate),
            'method' => 'PUT',
            'cancelRoute' => route('secretary-certificates'),
            'fields' => $this->fields(),
            'item' => $secretaryCertificate,
        ]);
    }

    public function update(Request $request, SecretaryCertificate $secretaryCertificate)
    {
        $data = $this->validateData($request);
        $data = $this->mergeMeetingSourceData($data);
        $data['document_path'] = $this->handleUpload($request, 'document_path', $secretaryCertificate->document_path);
        $data = $this->filterPersistableData($data);

        $secretaryCertificate->update($data);

        return redirect()->route('secretary-certificates')->with('success', 'Secretary certificate updated.');
    }

    public function destroy(SecretaryCertificate $secretaryCertificate)
    {
        $secretaryCertificate->delete();

        return redirect()->route('secretary-certificates')->with('success', 'Secretary certificate deleted.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'certificate_no', 'label' => 'Certificate No.', 'type' => 'text'],
            ['name' => 'date_uploaded', 'label' => 'Date Uploaded', 'type' => 'date'],
            ['name' => 'uploaded_by', 'label' => 'Uploaded By', 'type' => 'text'],
            ['name' => 'governing_body', 'label' => 'Governing Body', 'type' => 'select', 'options' => $this->governingBodyOptions()],
            ['name' => 'type_of_meeting', 'label' => 'Type of Meeting', 'type' => 'select', 'options' => $this->meetingTypeOptions()],
            ['name' => 'minute_id', 'label' => 'Linked Minutes', 'type' => 'text'],
            ['name' => 'notice_id', 'label' => 'Linked Notice', 'type' => 'text'],
            ['name' => 'notice_ref', 'label' => 'Notice Reference #', 'type' => 'text'],
            ['name' => 'meeting_no', 'label' => 'Meeting No.', 'type' => 'text'],
            ['name' => 'minutes_ref', 'label' => 'Minutes Ref.', 'type' => 'text'],
            ['name' => 'resolution_id', 'label' => 'Linked Resolution', 'type' => 'text'],
            ['name' => 'resolution_no', 'label' => 'Resolution No.', 'type' => 'text'],
            ['name' => 'resolution_body', 'label' => 'Resolution Body', 'type' => 'textarea'],
            ['name' => 'date_issued', 'label' => 'Date Issued', 'type' => 'date'],
            ['name' => 'purpose', 'label' => 'Purpose', 'type' => 'text'],
            ['name' => 'date_of_meeting', 'label' => 'Date of Meeting', 'type' => 'date'],
            ['name' => 'location', 'label' => 'Location', 'type' => 'text'],
            ['name' => 'secretary', 'label' => 'Secretary', 'type' => 'text'],
            ['name' => 'secretary_address', 'label' => 'Secretary Address', 'type' => 'text'],
            ['name' => 'secretary_tin', 'label' => 'Secretary TIN', 'type' => 'text'],
            ['name' => 'notarial_place', 'label' => 'Notarial Place', 'type' => 'text'],
            ['name' => 'notary_doc_no', 'label' => 'Notary Doc No.', 'type' => 'text'],
            ['name' => 'notary_page_no', 'label' => 'Notary Page No.', 'type' => 'text'],
            ['name' => 'notary_book_no', 'label' => 'Notary Book No.', 'type' => 'text'],
            ['name' => 'notary_series_no', 'label' => 'Notary Series No.', 'type' => 'text'],
            ['name' => 'notary_public', 'label' => 'Notary Public', 'type' => 'text'],
            ['name' => 'document_path', 'label' => 'Upload Original Certificate (PDF)', 'type' => 'file'],
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'certificate_no' => ['nullable', 'string', 'max:255'],
            'date_uploaded' => ['nullable', 'date'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'governing_body' => ['nullable', 'string', 'max:255'],
            'type_of_meeting' => ['nullable', 'string', 'max:255'],
            'minute_id' => ['nullable', 'integer', 'exists:minutes,id', 'required_without:resolution_id'],
            'notice_id' => ['nullable', 'integer', 'exists:notices,id'],
            'notice_ref' => ['nullable', 'string', 'max:255'],
            'meeting_no' => ['nullable', 'string', 'max:255'],
            'minutes_ref' => ['nullable', 'string', 'max:255'],
            'resolution_id' => ['nullable', 'integer', 'exists:resolutions,id', 'required_without:minute_id'],
            'resolution_no' => ['nullable', 'string', 'max:255'],
            'resolution_body' => ['nullable', 'string'],
            'date_issued' => ['nullable', 'date'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'date_of_meeting' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'secretary' => ['nullable', 'string', 'max:255'],
            'secretary_address' => ['nullable', 'string', 'max:500'],
            'secretary_tin' => ['nullable', 'string', 'max:100'],
            'notarial_place' => ['nullable', 'string', 'max:500'],
            'notary_doc_no' => ['nullable', 'string', 'max:255'],
            'notary_page_no' => ['nullable', 'string', 'max:255'],
            'notary_book_no' => ['nullable', 'string', 'max:255'],
            'notary_series_no' => ['nullable', 'string', 'max:255'],
            'notary_public' => ['nullable', 'string', 'max:255'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);
    }

    private function mergeMeetingSourceData(array $data): array
    {
        if (!empty($data['resolution_id'])) {
            $resolution = Resolution::with(['notice', 'minute'])->find($data['resolution_id']);
            if (!$resolution) {
                return $data;
            }

            $data['minute_id'] = $resolution->minute_id;
            $data['minutes_ref'] = $resolution->minute?->minutes_ref;
            $data['notice_id'] = $resolution->notice_id;
            $data['notice_ref'] = $resolution->notice_ref;
            $data['resolution_no'] = $resolution->resolution_no;
            $data['resolution_body'] = $this->completeResolutionBodyForCertificate($resolution);
            $data['governing_body'] = $resolution->governing_body;
            $data['type_of_meeting'] = $resolution->type_of_meeting;
            $data['meeting_no'] = $resolution->meeting_no;
            $data['purpose'] = $resolution->board_resolution ?: $resolution->resolution_no;
            $data['date_of_meeting'] = $resolution->date_of_meeting;
            $data['location'] = $resolution->location;
            $secretaryPerson = $this->corporateSecretaryPerson($this->gisForSources($resolution->notice, $resolution->minute, $resolution));
            $data['secretary'] = $secretaryPerson?->officer_name ?: ($this->corporateSecretaryNameForSources($resolution->notice, $resolution->minute) ?: $resolution->secretary);
            $data['secretary_address'] = $data['secretary_address'] ?? ($secretaryPerson?->address ?: null);
            $data['secretary_tin'] = $data['secretary_tin'] ?? ($secretaryPerson?->tin ?: null);
            $data['notarial_place'] = $data['notarial_place'] ?? ($resolution->notarized_at ?: $this->guessNotarialPlace($resolution->location));
            $data['notary_doc_no'] = $resolution->notary_doc_no;
            $data['notary_page_no'] = $resolution->notary_page_no;
            $data['notary_book_no'] = $resolution->notary_book_no;
            $data['notary_series_no'] = $resolution->notary_series_no;
            $data['notary_public'] = $resolution->notary_public;

            return $data;
        }

        if (empty($data['minute_id'])) {
            return $data;
        }

        $minute = Minute::with('notice')->find($data['minute_id']);
        if (!$minute) {
            return $data;
        }

        $notice = $minute->notice;
        $data['minutes_ref'] = $minute->minutes_ref;
        $data['notice_id'] = $minute->notice_id;
        $data['notice_ref'] = $minute->notice_ref ?: $notice?->notice_number;
        $data['governing_body'] = $minute->governing_body ?: $notice?->governing_body;
        $data['type_of_meeting'] = $minute->type_of_meeting ?: $notice?->type_of_meeting;
        $data['meeting_no'] = $minute->meeting_no ?: $notice?->meeting_no;
        $data['date_of_meeting'] = $minute->date_of_meeting ?: $notice?->date_of_meeting;
        $data['location'] = $minute->location ?: $notice?->location;
        $secretaryPerson = $this->corporateSecretaryPerson($this->gisForSources($notice, $minute, null));
        $data['secretary'] = $secretaryPerson?->officer_name ?: ($this->corporateSecretaryNameForSources($notice, $minute) ?: ($minute->secretary ?: $notice?->secretary));
        $data['secretary_address'] = $data['secretary_address'] ?? ($secretaryPerson?->address ?: null);
        $data['secretary_tin'] = $data['secretary_tin'] ?? ($secretaryPerson?->tin ?: null);
        $data['notarial_place'] = $data['notarial_place'] ?? $this->guessNotarialPlace($minute->location ?: $notice?->location);
        $data['resolution_id'] = null;
        $data['resolution_no'] = ($data['resolution_no'] ?? null) ?: null;
        $data['resolution_body'] = ($data['resolution_body'] ?? null) ?: ('Certified from Minutes Ref. ' . ($minute->minutes_ref ?: '') . '.');
        $data['purpose'] = ($data['purpose'] ?? null) ?: ('Certified extract from Minutes Ref. ' . ($minute->minutes_ref ?: ''));

        return $data;
    }




    private function completeResolutionBodyForCertificate(Resolution $resolution): string
    {
        $customBody = trim((string) $resolution->resolution_body);
        $standardClauses = trim($this->standardResolutionClausesForCertificate($resolution));

        if ($customBody === '') {
            return $standardClauses;
        }

        $normalizedCustom = Str::lower(strip_tags($customBody));

        if (Str::contains($normalizedCustom, 'whereas finally resolved')
            || Str::contains($normalizedCustom, 'be it further resolved')
            || Str::contains($normalizedCustom, 'all prior inconsistent resolutions')) {
            return $customBody;
        }

        return $customBody . "

" . $standardClauses;
    }

    private function standardResolutionClausesForCertificate(Resolution $resolution): string
    {
        $meetingDate = optional($resolution->date_of_meeting)->format('jS \d\a\y \o\f F Y') ?: '_____ day of ____________';
        $location = trim((string) ($resolution->location ?: '_____________________'));

        return trim(implode("

", [
            'WHEREAS RESOLVED; that the foregoing resolutions are hereby approved and adopted.',
            'WHEREAS FINALLY RESOLVED, that the foregoing resolution is valid and existing until withdrawn, revoked, or modified by the Corporation.',
            "BE IT FURTHER RESOLVED, that the Corporate Secretary is hereby authorized and directed to include this Resolution in the Company's Minute Book and to notify all concerned parties of the adoption of this Resolution.",
            'FINALLY BE IT FURTHER RESOLVED that we, the undersigned, hereby accept and agree to the foregoing resolutions. We have affixed our signatures on this ' . $meetingDate . ' at ' . $location . '.',
            'All prior inconsistent resolutions or actions of the Board of Directors are hereby revoked and superseded. This resolution shall be effective immediately.',
        ]));
    }

    private function corporateContextForCertificate(SecretaryCertificate $certificate): array
    {
        $resolution = $certificate->resolution;
        $minute = $certificate->minute ?: $resolution?->minute;
        $notice = $certificate->notice ?: $resolution?->notice ?: $minute?->notice;
        $gis = $this->gisForSources($notice, $minute, $resolution);
        $secretaryPerson = $this->corporateSecretaryPerson($gis);

        $companyName = $gis?->corporation_name ?: '________________';
        $companyRegNo = $gis?->company_reg_no ?: '________________';
        $companyAddress = $gis?->principal_address ?: ($gis?->business_address ?: '________________');
        $secretaryName = $certificate->secretary ?: ($secretaryPerson?->officer_name ?: ($resolution?->secretary ?: ($minute?->secretary ?: 'Corporate Secretary')));
        $secretaryTin = data_get($certificate, 'secretary_tin') ?: ($secretaryPerson?->tin ?: null);
        $secretaryAddress = data_get($certificate, 'secretary_address') ?: ($secretaryPerson?->address ?: ($companyAddress ?: 'principal office of the Corporation'));
        $notarialPlace = data_get($certificate, 'notarial_place') ?: $this->guessNotarialPlace($certificate->location ?: $resolution?->location ?: $minute?->location ?: $companyAddress);
        $resolvedBody = $resolution ? $this->completeResolutionBodyForCertificate($resolution) : (data_get($certificate, 'resolution_body') ?: $certificate->purpose);
        $resolvedPurpose = $certificate->purpose ?: ($resolution?->board_resolution ?: ($certificate->resolution_no ? 'Resolution ' . $certificate->resolution_no : 'Certified Resolution'));

        return [
            'gis' => $gis,
            'company_name' => $companyName,
            'company_reg_no' => $companyRegNo,
            'company_address' => $companyAddress,
            'secretary_name' => $secretaryName,
            'secretary_tin' => $secretaryTin,
            'secretary_address' => $secretaryAddress,
            'notarial_place' => $notarialPlace,
            'resolution_body' => $resolvedBody,
            'purpose' => $resolvedPurpose,
            'logo_path' => $gis?->logo_path,
        ];
    }

    private function corporateSecretaryNameForSources(?Notice $notice = null, ?Minute $minute = null): ?string
    {
        $gis = $this->gisForSources($notice, $minute, null);
        return $this->corporateSecretaryPerson($gis)?->officer_name;
    }

    private function corporateSecretaryPerson(?GisRecord $gis): ?DirectorOfficer
    {
        if (!$gis) {
            return null;
        }

        return $gis->directors()
            ->where(function ($query) {
                $query->where('officer_type', 'like', '%Secretary%')
                    ->orWhere('committee', 'like', '%Secretary%');
            })
            ->orderByDesc('id')
            ->first();
    }

    private function gisForSources(?Notice $notice = null, ?Minute $minute = null, ?Resolution $resolution = null): ?GisRecord
    {
        $notice = $notice ?: $minute?->notice ?: $resolution?->notice;
        $candidateIds = collect();

        if ($notice && method_exists($notice, 'attendees')) {
            $notice->loadMissing('attendees');
            foreach ($notice->attendees as $attendee) {
                if (isset($attendee->gis_id) && $attendee->gis_id) {
                    $candidateIds->push($attendee->gis_id);
                }

                $sourceType = strtolower((string) ($attendee->source_type ?? $attendee->attendee_type ?? ''));
                $sourceId = $attendee->source_id ?? null;

                if ($sourceId && Str::contains($sourceType, ['director', 'officer'])) {
                    $candidateIds->push(DirectorOfficer::whereKey($sourceId)->value('gis_id'));
                }

                if ($sourceId && Str::contains($sourceType, 'stockholder')) {
                    $candidateIds->push(Stockholder::whereKey($sourceId)->value('gis_id'));
                }
            }
        }

        $candidateIds = $candidateIds->filter()->unique()->values();
        if ($candidateIds->isNotEmpty()) {
            $gis = GisRecord::whereIn('id', $candidateIds)
                ->where(function ($query) {
                    $query->where('approval_status', 'Approved')
                        ->orWhere('workflow_status', 'Accepted');
                })
                ->latest('created_at')
                ->first();

            if ($gis) {
                return $gis;
            }
        }

        return GisRecord::where(function ($query) {
                $query->where('approval_status', 'Approved')
                    ->orWhere('workflow_status', 'Accepted');
            })
            ->latest('created_at')
            ->first();
    }


    private function guessNotarialPlace(?string $source): string
    {
        $source = trim((string) $source);
        if ($source === '' || $source === '________________') {
            return 'Cebu City, Philippines';
        }

        $parts = collect(explode(',', $source))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->values();

        $cityLike = $parts->first(fn ($part) => Str::contains(Str::lower($part), ['city', 'cebu', 'mandaue', 'municipality']));
        if ($cityLike) {
            return Str::contains(Str::lower($cityLike), 'philippines') ? $cityLike : $cityLike . ', Philippines';
        }

        $first = $parts->first();
        return $first ? ($first . (Str::contains(Str::lower($first), 'philippines') ? '' : ', Philippines')) : 'Cebu City, Philippines';
    }

    private function governingBodyOptions(): array
    {
        return ['Stockholders', 'Board of Directors', 'Joint Stockholders and Board of Directors'];
    }

    private function meetingTypeOptions(): array
    {
        return ['Regular', 'Special'];
    }

    private function filterPersistableData(array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $key) => Schema::hasColumn('secretary_certificates', $key))
            ->all();
    }
}
