<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesCorporateDocumentNumbers;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Models\DirectorOfficer;
use App\Models\GisRecord;
use App\Models\Minute;
use App\Models\Notice;
use App\Models\Resolution;
use App\Models\Stockholder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResolutionController extends Controller
{
    use GeneratesCorporateDocumentNumbers;
    use HandlesUploads;

public function index()
{
    $resolutions = Resolution::whereNull('company_id')
        ->with(['minute', 'notice'])
        ->withCount('secretaryCertificates')
        ->latest()
        ->paginate(10);

    $minutes = Minute::whereNull('company_id')
        ->with('notice')
        ->orderByDesc('date_of_meeting')
        ->get();

    return view('corporate.resolutions.index', [
        'resolutions' => $resolutions,
        'minutes' => $minutes,
        'nextResolutionNumber' => $this->nextResolutionNumber(),
    ]);
}

    public function create()
    {
        return view('corporate.common.form', [
            'title' => 'Add Resolution',
            'action' => route('resolutions.store'),
            'method' => 'POST',
            'cancelRoute' => route('resolutions'),
            'fields' => $this->fields(),
            'item' => new Resolution([
                'resolution_no' => $this->nextResolutionNumber(),
                'date_uploaded' => now()->toDateString(),
                'uploaded_by' => auth()->user()?->name ?? '',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data = $this->mergeMinuteData($data);
        $data = $this->applyAutomaticResolutionPeople($data);
        $data['draft_file_path'] = $this->handleUpload($request, 'draft_file_path');
        $data['notarized_file_path'] = $this->handleUpload($request, 'notarized_file_path');
        $data['resolution_no'] = $data['resolution_no'] ?: $this->nextResolutionNumber();
        $data = $this->filterPersistableData($data);

        $resolution = Resolution::create($data);
        $this->syncGeneratedResolutionPdf($resolution, $request->hasFile('draft_file_path'));
        $this->syncSecretaryCertificates($resolution);

        return redirect()->route('resolutions')->with('success', 'Resolution created.');
    }

public function show(Resolution $resolution)
{
    abort_if($resolution->company_id !== null, 404);

    /*
    |--------------------------------------------------------------------------
    | PERFORMANCE FIX
    |--------------------------------------------------------------------------
    | Do not generate or sync PDFs while opening the normal Resolution preview.
    | PDF generation is heavy and can cause PHP-FPM/MySQL pressure and 502
    | errors. The PDF will be generated only through the download route.
    */
    $resolution->loadMissing([
        'minute.notice.attendees',
        'notice.attendees',
        'secretaryCertificates',
    ]);

    $document = $this->resolutionDocumentData($resolution);

    return view('corporate.resolutions.preview', [
        'resolution' => $resolution,
        'document' => $document,
        'generatedBodyPreviewUrl' => route('resolutions.download', $resolution),
        'downloadRoute' => route('resolutions.download', $resolution),
        'backRoute' => route('resolutions'),
        'editRoute' => route('resolutions.edit', $resolution),
        'updateRoute' => route('resolutions.update', $resolution),
        'deleteRoute' => route('resolutions.destroy', $resolution),
    ]);
}

    public function downloadPdf(Resolution $resolution)
    {
        abort_if($resolution->company_id !== null, 404);

        $resolution->load(['minute.notice.attendees', 'notice.attendees']);
        $filename = 'resolution-' . Str::slug($resolution->resolution_no ?: ('resolution-' . $resolution->id)) . '.pdf';

        return Pdf::loadView('corporate.resolutions.pdf', [
                'resolution' => $resolution,
                'document' => $this->resolutionDocumentData($resolution),
            ])
            ->setPaper('a4')
            ->setOptions(['isPhpEnabled' => true])
            ->download($filename);
    }

    public function edit(Resolution $resolution)
    {
        abort_if($resolution->company_id !== null, 404);

        return view('corporate.common.form', [
            'title' => 'Edit Resolution',
            'action' => route('resolutions.update', $resolution),
            'method' => 'PUT',
            'cancelRoute' => route('resolutions'),
            'fields' => $this->fields(),
            'item' => $resolution,
        ]);
    }

    public function update(Request $request, Resolution $resolution)
    {
        abort_if($resolution->company_id !== null, 404);

        $data = $this->validateData($request);
        $data = $this->mergeMinuteData($data);
        $data = $this->applyAutomaticResolutionPeople($data);
        $data['draft_file_path'] = $this->handleUpload($request, 'draft_file_path', $resolution->draft_file_path);
        $data['notarized_file_path'] = $this->handleUpload($request, 'notarized_file_path', $resolution->notarized_file_path);
        $data = $this->filterPersistableData($data);

        $resolution->update($data);
        $this->syncGeneratedResolutionPdf($resolution->fresh(), $request->hasFile('draft_file_path'));
        $this->syncSecretaryCertificates($resolution->fresh());

        return redirect()->route('resolutions')->with('success', 'Resolution updated.');
    }

    public function destroy(Resolution $resolution)
    {
        abort_if($resolution->company_id !== null, 404);

        $resolution->delete();

        return redirect()->route('resolutions')->with('success', 'Resolution deleted.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'resolution_no', 'label' => 'Resolution No.', 'type' => 'text'],
            ['name' => 'date_uploaded', 'label' => 'Date Uploaded', 'type' => 'date'],
            ['name' => 'uploaded_by', 'label' => 'Uploaded By', 'type' => 'text'],
            ['name' => 'minute_id', 'label' => 'Linked Minutes', 'type' => 'text'],
            ['name' => 'governing_body', 'label' => 'Governing Body', 'type' => 'select', 'options' => $this->governingBodyOptions()],
            ['name' => 'type_of_meeting', 'label' => 'Type of Meeting', 'type' => 'select', 'options' => $this->meetingTypeOptions()],
            ['name' => 'notice_id', 'label' => 'Linked Notice', 'type' => 'text'],
            ['name' => 'notice_ref', 'label' => 'Notice Reference #', 'type' => 'text'],
            ['name' => 'meeting_no', 'label' => 'Meeting No.', 'type' => 'text'],
            ['name' => 'date_of_meeting', 'label' => 'Date of Meeting', 'type' => 'date'],
            ['name' => 'location', 'label' => 'Location of Meeting', 'type' => 'text'],
            ['name' => 'board_resolution', 'label' => 'Resolution Title', 'type' => 'text'],
            ['name' => 'resolution_body', 'label' => 'Resolution Body', 'type' => 'textarea'],
            ['name' => 'notary_doc_no', 'label' => 'Notary Doc No.', 'type' => 'text'],
            ['name' => 'notary_page_no', 'label' => 'Notary Page No.', 'type' => 'text'],
            ['name' => 'notary_book_no', 'label' => 'Notary Book No.', 'type' => 'text'],
            ['name' => 'notary_series_no', 'label' => 'Notary Series No.', 'type' => 'text'],
            ['name' => 'notary_public', 'label' => 'Notary Public', 'type' => 'text'],
            ['name' => 'notarized_on', 'label' => 'Notarized On', 'type' => 'date'],
            ['name' => 'notarized_at', 'label' => 'Notarized At', 'type' => 'text'],
            ['name' => 'draft_file_path', 'label' => 'Upload Draft (PDF)', 'type' => 'file'],
            ['name' => 'notarized_file_path', 'label' => 'Upload Notarized (PDF)', 'type' => 'file'],
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'resolution_no' => ['nullable', 'string', 'max:255'],
            'date_uploaded' => ['nullable', 'date'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'minute_id' => ['nullable', 'integer', 'exists:minutes,id', 'required_without:notice_id'],
            'governing_body' => ['nullable', 'string', 'max:255'],
            'type_of_meeting' => ['nullable', 'string', 'max:255'],
            'notice_id' => ['nullable', 'integer', 'exists:notices,id'],
            'notice_ref' => ['nullable', 'string', 'max:255'],
            'meeting_no' => ['nullable', 'string', 'max:255'],
            'date_of_meeting' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'board_resolution' => ['nullable', 'string', 'max:255'],
            'resolution_body' => ['nullable', 'string'],
            'directors' => ['nullable', 'string', 'max:255'],
            'chairman' => ['nullable', 'string', 'max:255'],
            'secretary' => ['nullable', 'string', 'max:255'],
            'notary_doc_no' => ['nullable', 'string', 'max:255'],
            'notary_page_no' => ['nullable', 'string', 'max:255'],
            'notary_book_no' => ['nullable', 'string', 'max:255'],
            'notary_series_no' => ['nullable', 'string', 'max:255'],
            'notary_public' => ['nullable', 'string', 'max:255'],
            'notarized_on' => ['nullable', 'date'],
            'notarized_at' => ['nullable', 'string', 'max:255'],
            'draft_file_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'notarized_file_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);
    }

    private function mergeMinuteData(array $data): array
    {
        if (empty($data['minute_id'])) {
            return $data;
        }

        $minute = Minute::with('notice')->find($data['minute_id']);
        if (!$minute) {
            return $data;
        }

        $notice = $minute->notice;

        $data['company_id'] = $minute->company_id ?: $notice?->company_id;
        $data['notice_id'] = $minute->notice_id;
        $data['notice_ref'] = $minute->notice_ref ?: $notice?->notice_number;
        $data['governing_body'] = $minute->governing_body ?: $notice?->governing_body;
        $data['type_of_meeting'] = $minute->type_of_meeting ?: $notice?->type_of_meeting;
        $data['meeting_no'] = $minute->meeting_no ?: $notice?->meeting_no;
        $data['date_of_meeting'] = optional($minute->date_of_meeting)?->toDateString()
            ?: optional($notice?->date_of_meeting)?->toDateString();
        $data['location'] = $minute->location ?: $notice?->location;
        $data['chairman'] = $minute->chairman ?: $notice?->chairman;
        $data['secretary'] = $minute->secretary ?: $notice?->secretary;

        return $data;
    }

    private function applyAutomaticResolutionPeople(array $data): array
    {
        $resolution = new Resolution($data);
        $document = $this->resolutionDocumentData($resolution);

        // Store the names used in the signatory section. These names now come
        // from the latest accepted GIS based on the governing body:
        // Board = Directors, Stockholders = Stockholders, Joint = both.
        $data['directors'] = collect($document['approval_rows'] ?? [])->pluck('name')->implode(', ');

        // Chairman must be whoever is assigned as chairman of the meeting.
        // Do not overwrite it with the first GIS director/signatory.
        $data['chairman'] = trim((string) ($data['chairman'] ?? '')) !== ''
            ? $data['chairman']
            : data_get($document, 'chairman.name');

        // Corporate Secretary must be whoever is assigned as secretary of the
        // meeting. Only use the text fallback if no meeting secretary exists.
        if (empty($data['secretary'])) {
            $data['secretary'] = 'Corporate Secretary';
        }

        // Default the notarial series to the current year, but keep it editable.
        if (empty($data['notary_series_no'])) {
            $data['notary_series_no'] = now()->year;
        }

        return $data;
    }


    private function syncSecretaryCertificates(Resolution $resolution): void
    {
        $shared = [
            'notice_id' => $resolution->notice_id,
            'notice_ref' => $resolution->notice_ref,
            'resolution_no' => $resolution->resolution_no,
            'governing_body' => $resolution->governing_body,
            'type_of_meeting' => $resolution->type_of_meeting,
            'meeting_no' => $resolution->meeting_no,
            'date_of_meeting' => $resolution->date_of_meeting,
            'location' => $resolution->location,
            'secretary' => $resolution->secretary,
            'purpose' => $resolution->board_resolution,
            // Secretary Certificates must certify the complete system-built
            // resolution text: user-typed WHEREAS/details + standard clauses.
            'resolution_body' => $this->completeResolutionBody($resolution),
            'notary_doc_no' => $resolution->notary_doc_no,
            'notary_page_no' => $resolution->notary_page_no,
            'notary_book_no' => $resolution->notary_book_no,
            'notary_series_no' => $resolution->notary_series_no,
            'notary_public' => $resolution->notary_public,
        ];

        $resolution->secretaryCertificates()->get()->each(function ($certificate) use ($shared) {
            $certificate->update($shared);
        });
    }


    private function completeResolutionBody(Resolution $resolution): string
    {
        $customBody = trim((string) $resolution->resolution_body);
        $standardClauses = trim($this->standardResolutionClauses($resolution));

        if ($customBody === '') {
            return $standardClauses;
        }

        $normalizedCustom = Str::lower(strip_tags($customBody));

        // If the user already typed/pasted the full standard clauses, do not
        // duplicate them. Otherwise append the required corporate clauses.
        if (Str::contains($normalizedCustom, 'whereas finally resolved')
            || Str::contains($normalizedCustom, 'be it further resolved')
            || Str::contains($normalizedCustom, 'all prior inconsistent resolutions')) {
            return $customBody;
        }

        return $customBody . "

" . $standardClauses;
    }

    private function standardResolutionClauses(Resolution $resolution): string
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
            ->filter(fn ($value, $key) => Schema::hasColumn('resolutions', $key))
            ->all();
    }

    private function syncGeneratedResolutionPdf(Resolution $resolution, bool $hasUploadedDraft): void
    {
        if ($hasUploadedDraft) {
            return;
        }

        // Regenerate system-built draft PDFs when the live template changes.
        // Only preserve a manually uploaded draft that is not in our generated folder.
        if ($resolution->draft_file_path && ! str_starts_with((string) $resolution->draft_file_path, 'uploads/resolutions/')) {
            return;
        }

        $pdfPath = $this->generateResolutionPdf($resolution->fresh());

        if ($pdfPath) {
            $resolution->update(['draft_file_path' => $pdfPath]);
        }
    }

    private function generateResolutionPdf(Resolution $resolution, ?string $targetPath = null): ?string
    {
        $resolution->loadMissing(['minute.notice.attendees', 'notice.attendees']);
        $targetPath = $targetPath ?: 'uploads/resolutions/' . ($resolution->resolution_no ?: 'draft-resolution') . '.pdf';

        $pdf = Pdf::loadView('corporate.resolutions.pdf', [
            'resolution' => $resolution,
            'document' => $this->resolutionDocumentData($resolution),
        ])->setPaper('a4')->setOptions(['isPhpEnabled' => true]);

        Storage::disk('public')->delete($targetPath);
        Storage::disk('public')->put($targetPath, $pdf->output());

        return $targetPath;
    }

    private function resolutionDocumentData(Resolution $resolution): array
    {
        $resolution->loadMissing(['minute.notice.attendees', 'notice.attendees']);

        // Use the GIS linked to the meeting/notice where possible. Fallback to
        // the latest accepted Corporate GIS. The signatory rows below are pulled
        // from this GIS, not hardcoded and not manually typed.
        $gis = $this->gisForResolution($resolution);

        $governingBody = trim((string) (
            $resolution->governing_body
            ?: $resolution->minute?->governing_body
            ?: $resolution->notice?->governing_body
        ));

        $chairmanName = trim((string) (
            $resolution->chairman
            ?: $resolution->minute?->chairman
            ?: $resolution->notice?->chairman
        ));

        // Fallback only: if no meeting chairman was saved, try to find a chair
        // record from the GIS directors/officers.
        if ($chairmanName === '' && $gis) {
            $chairmanName = (string) optional(
                $gis->directors->first(function ($person) {
                    $position = Str::lower((string) ($person->officer_type . ' ' . $person->board . ' ' . $person->committee));
                    return Str::contains($position, 'chair');
                })
            )->officer_name;
        }

        $chairman = $chairmanName !== ''
            ? ['name' => $chairmanName, 'role' => 'Chairman']
            : null;

        // President requested:
        // - Board meetings: show all directors/officers on the GIS
        // - Stockholders meetings: show all stockholders on the GIS
        // - Joint meetings: show both directors/officers and stockholders
        $approvalRows = $gis
            ? collect($this->gisPeopleForGoverningBody($gis, Str::lower($governingBody)))
            : collect();

        // The template prints the chairman separately below the approval grid.
        // Remove the chairman from the grid if the same name is already there.
        if ($chairmanName !== '') {
            $normalizedChairman = $this->normalizeName($chairmanName);

            $approvalRows = $approvalRows->reject(function ($row) use ($normalizedChairman) {
                return $this->normalizeName($row['name'] ?? '') === $normalizedChairman;
            });
        }

        $approvalRows = $approvalRows
            ->filter(fn ($row) => trim((string) ($row['name'] ?? '')) !== '')
            ->unique(fn ($row) => $this->normalizeName($row['name'] ?? ''))
            ->values()
            ->all();

        return [
            'company_name' => $gis?->corporation_name ?: 'JK&C INC.',
            'company_reg_no' => $gis?->company_reg_no ?: '2025120230900-02',
            'company_address' => $gis?->principal_address ?: $gis?->business_address ?: '3RD FLOOR, UNIT 305 CEBU HOLDINGS CENTER CARDINAL ROSALES AVE., CEBU BUSINESS PARK HIPPODROMO, CEBU CITY, 6000',
            'logo_path' => $gis?->logo_path,
            'approval_rows' => $approvalRows,
            'chairman' => $chairman,
            'resolution_label' => $this->resolutionNumberLabel($governingBody),
            'certifying_body' => $this->certifyingBodyLabel($governingBody),
            // For display/PDF only. The DB field resolution_body remains the
            // user-entered custom body so the builder stays simple.
            'standard_resolution_clauses' => $this->standardResolutionClauses($resolution),
            'full_resolution_body' => $this->completeResolutionBody($resolution),
        ];
    }


    private function gisForResolution(Resolution $resolution): ?GisRecord
    {
        $resolution->loadMissing(['minute.notice.attendees', 'notice.attendees']);

        $notice = $resolution->notice ?: $resolution->minute?->notice;
        $candidateGisIds = collect();

        // 1) Best source: the attendees selected in the linked Notice. These rows
        // usually remember whether the person came from Directors/Officers or Stockholders.
        if ($notice && $notice->relationLoaded('attendees')) {
            $attendees = $notice->attendees;
        } elseif ($notice) {
            $attendees = $notice->attendees()->get();
        } else {
            $attendees = collect();
        }

        $directorIds = $attendees
            ->filter(fn ($attendee) => Str::contains(Str::lower((string) $attendee->source_type), ['director', 'officer']))
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->values();

        if ($directorIds->isNotEmpty()) {
            $candidateGisIds = $candidateGisIds->merge(
                DirectorOfficer::whereIn('id', $directorIds)->pluck('gis_id')
            );
        }

        $stockholderIds = $attendees
            ->filter(fn ($attendee) => Str::contains(Str::lower((string) $attendee->source_type), 'stockholder'))
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->values();

        if ($stockholderIds->isNotEmpty()) {
            $candidateGisIds = $candidateGisIds->merge(
                Stockholder::whereIn('id', $stockholderIds)->pluck('gis_id')
            );
        }

        // 2) Fallback: match Minutes present names against active people in approved GIS records.
        // This handles older minutes where source_id/source_type was not saved.
        $minutePresentNames = collect($this->parsePeopleRows($resolution->minute?->directors_present))
            ->pluck('name')
            ->map(fn ($name) => $this->normalizeName($name))
            ->filter()
            ->unique()
            ->values();

        if ($minutePresentNames->isNotEmpty()) {
            DirectorOfficer::query()
                ->whereNotNull('gis_id')
                ->get(['gis_id', 'officer_name'])
                ->each(function (DirectorOfficer $person) use ($minutePresentNames, &$candidateGisIds) {
                    if ($minutePresentNames->contains($this->normalizeName($person->officer_name))) {
                        $candidateGisIds->push($person->gis_id);
                    }
                });

            Stockholder::query()
                ->whereNotNull('gis_id')
                ->get(['gis_id', 'stockholder_name'])
                ->each(function (Stockholder $person) use ($minutePresentNames, &$candidateGisIds) {
                    if ($minutePresentNames->contains($this->normalizeName($person->stockholder_name))) {
                        $candidateGisIds->push($person->gis_id);
                    }
                });
        }

        $bestGisId = $candidateGisIds
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        if ($bestGisId) {
            $matched = GisRecord::with(['directors', 'stockholders'])
                ->where('id', $bestGisId)
                ->first();

            if ($matched) {
                return $matched;
            }
        }

        // 3) Final fallback: latest approved Corporate GIS globally.
        // Do not scope by company_id here, because this Resolution is in the Corporate
        // module and company_id can point to the separate Company module record.
        return $this->latestApprovedGis();
    }

    private function latestApprovedGis($companyId = null): ?GisRecord
    {
        $query = GisRecord::with(['directors', 'stockholders'])
            ->whereIn('workflow_status', ['Accepted', 'accepted', 'APPROVED', 'Approved'])
            ->whereIn('approval_status', ['Approved', 'approved', 'ACCEPTED', 'Accepted']);

        if ($companyId) {
            $companyScoped = (clone $query)->where('company_id', $companyId)->latest()->first();

            if ($companyScoped) {
                return $companyScoped;
            }
        }

        return $query->latest()->first();
    }

    private function attendingSignatories(Resolution $resolution, ?GisRecord $gis): array
    {
        $governingBody = Str::lower((string) ($resolution->governing_body ?: $resolution->minute?->governing_body ?: $resolution->notice?->governing_body));
        $minute = $resolution->minute;
        $notice = $resolution->notice ?: $minute?->notice;

        $absentNames = collect($this->parsePeopleRows($minute?->directors_absent))
            ->pluck('name')
            ->map(fn ($name) => $this->normalizeName($name))
            ->filter()
            ->all();

        $presentRows = collect($this->parsePeopleRows($minute?->directors_present));

        if ($presentRows->isEmpty() && $notice) {
            $presentRows = $notice->attendees
                ->where('is_selected', true)
                ->map(fn ($attendee) => [
                    'name' => $attendee->name,
                    'position' => $attendee->position,
                    'source_type' => $attendee->source_type,
                    'source_id' => $attendee->source_id,
                ])
                ->values();
        }

        if ($presentRows->isEmpty() && $gis) {
            $presentRows = $this->gisPeopleForGoverningBody($gis, $governingBody);
        }

        $noticeSourceMap = $notice
            ? $notice->attendees
                ->mapWithKeys(fn ($attendee) => [
                    $this->normalizeName($attendee->name) => [
                        'source_type' => $attendee->source_type,
                        'position' => $attendee->position,
                        'source_id' => $attendee->source_id,
                    ],
                ])
                ->all()
            : [];

        $gisSourceMap = $gis ? $this->gisSourceMap($gis) : [];

        return $presentRows
            ->map(function ($row) use ($governingBody, $noticeSourceMap, $gisSourceMap) {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                $normalizedName = $this->normalizeName($name);
                $position = trim((string) ($row['position'] ?? ''));
                $sourceType = $row['source_type'] ?? null;

                if (!$sourceType && isset($noticeSourceMap[$normalizedName])) {
                    $sourceType = $noticeSourceMap[$normalizedName]['source_type'] ?? null;
                    $position = $position ?: (string) ($noticeSourceMap[$normalizedName]['position'] ?? '');
                }

                if (!$sourceType && isset($gisSourceMap[$normalizedName])) {
                    $sourceType = $gisSourceMap[$normalizedName]['source_type'] ?? null;
                    $position = $position ?: (string) ($gisSourceMap[$normalizedName]['position'] ?? '');
                }

                $role = $this->resolutionRoleLabel($governingBody, $sourceType, $position);

                if (!$this->roleAllowedForGoverningBody($governingBody, $role)) {
                    return null;
                }

                return [
                    'name' => $name,
                    'position' => $position,
                    'source_type' => $sourceType,
                    'role' => $role,
                ];
            })
            ->filter()
            ->reject(fn ($row) => in_array($this->normalizeName($row['name']), $absentNames, true))
            ->unique(fn ($row) => $this->normalizeName($row['name']))
            ->values()
            ->all();
    }

    private function gisPeopleForGoverningBody(GisRecord $gis, string $governingBody)
    {
        $body = Str::lower($governingBody);
        $people = collect();

        if (Str::contains($body, 'director') || Str::contains($body, 'board') || Str::contains($body, 'joint')) {
            $people = $people->merge($gis->directors->map(fn (DirectorOfficer $person) => [
                'name' => $person->officer_name,
                'position' => $person->officer_type ?: ($person->board ?: 'Director'),
                'source_type' => 'director',
                'role' => 'Director',
            ]));
        }

        if (Str::contains($body, 'stockholder') || Str::contains($body, 'joint')) {
            $people = $people->merge($gis->stockholders->map(fn (Stockholder $person) => [
                'name' => $person->stockholder_name,
                'position' => 'Stockholder',
                'source_type' => 'stockholder',
                'role' => 'Stockholder',
            ]));
        }

        return $people
            ->filter(fn ($row) => trim((string) ($row['name'] ?? '')) !== '')
            ->values();
    }


    private function gisSourceMap(GisRecord $gis): array
    {
        $directors = $gis->directors->mapWithKeys(fn (DirectorOfficer $person) => [
            $this->normalizeName($person->officer_name) => [
                'source_type' => 'director',
                'position' => $person->officer_type,
            ],
        ]);

        $stockholders = $gis->stockholders->mapWithKeys(fn (Stockholder $person) => [
            $this->normalizeName($person->stockholder_name) => [
                'source_type' => 'stockholder',
                'position' => 'Stockholder',
            ],
        ]);

        return $directors->merge($stockholders)->all();
    }

    private function parsePeopleRows($value): array
    {
        if (blank($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)
                ->map(function ($row) {
                    if (is_string($row)) {
                        return ['name' => trim($row), 'position' => '', 'source_type' => null];
                    }

                    return [
                        'name' => trim((string) ($row['name'] ?? $row['person'] ?? '')),
                        'position' => trim((string) ($row['position'] ?? $row['role'] ?? '')),
                        'source_type' => $row['source_type'] ?? null,
                        'source_id' => $row['source_id'] ?? null,
                    ];
                })
                ->filter(fn ($row) => $row['name'] !== '')
                ->values()
                ->all();
        }

        return collect(preg_split('/[\r\n,;]+/', (string) $value))
            ->map(fn ($name) => ['name' => trim($name), 'position' => '', 'source_type' => null])
            ->filter(fn ($row) => $row['name'] !== '')
            ->values()
            ->all();
    }

    private function resolutionRoleLabel(string $governingBody, $sourceType = null, string $position = ''): string
    {
        $source = Str::lower((string) $sourceType . ' ' . $position);

        if (Str::contains($source, 'chair')) {
            return 'Chairman';
        }

        /*
         * The minutes attendance rows are the source of truth for who actually attended.
         * Older minutes/notice records may still store the selected attendee inside a
         * generic/director-style field even when the meeting is for Stockholders.
         * Because of that, decide the legal role from the governing body first for
         * single-body meetings, then use the GIS/source role only for joint meetings.
         */
        if ($this->isStockholdersOnly($governingBody)) {
            return 'Stockholder';
        }

        if ($this->isBoardOnly($governingBody)) {
            return 'Director';
        }

        if (Str::contains($source, 'stockholder')) {
            return 'Stockholder';
        }

        if (Str::contains($source, 'director') || Str::contains($source, 'board')) {
            return 'Director';
        }

        return 'Director';
    }

    private function roleAllowedForGoverningBody(string $governingBody, string $role): bool
    {
        $role = Str::lower($role);

        if (Str::contains($role, 'chair')) {
            return true;
        }

        if ($this->isStockholdersOnly($governingBody)) {
            return Str::contains($role, 'stockholder');
        }

        if ($this->isBoardOnly($governingBody)) {
            return Str::contains($role, 'director');
        }

        return Str::contains($role, 'director') || Str::contains($role, 'stockholder');
    }

    private function isStockholdersOnly(string $governingBody): bool
    {
        return Str::contains($governingBody, 'stockholder')
            && !Str::contains($governingBody, 'director')
            && !Str::contains($governingBody, 'board')
            && !Str::contains($governingBody, 'joint');
    }

    private function isBoardOnly(string $governingBody): bool
    {
        return (Str::contains($governingBody, 'director') || Str::contains($governingBody, 'board'))
            && !Str::contains($governingBody, 'stockholder')
            && !Str::contains($governingBody, 'joint');
    }

    private function resolutionNumberLabel(string $governingBody): string
    {
        $body = Str::lower($governingBody);

        if ($this->isStockholdersOnly($body)) {
            return "Stockholders' Resolution No.";
        }

        if (Str::contains($body, 'joint')) {
            return 'Joint Board and Stockholders Resolution No.';
        }

        return 'Board Resolution No.';
    }

    private function certifyingBodyLabel(string $governingBody): string
    {
        $body = Str::lower($governingBody);

        if ($this->isStockholdersOnly($body)) {
            return 'Stockholders';
        }

        if (Str::contains($body, 'joint')) {
            return 'Stockholders and Board of Directors';
        }

        return 'Board of Directors';
    }

    private function normalizeName($name): string
    {
        return Str::of((string) $name)->lower()->replaceMatches('/\s+/', ' ')->trim()->toString();
    }

    private function sameName($left, $right): bool
    {
        return $this->normalizeName($left) !== '' && $this->normalizeName($left) === $this->normalizeName($right);
    }
}
