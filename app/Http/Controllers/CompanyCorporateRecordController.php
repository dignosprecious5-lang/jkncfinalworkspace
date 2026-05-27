<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesCorporateDocumentNumbers;
use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Models\Company;
use App\Models\GisRecord;
use App\Models\Minute;
use App\Models\Notice;
use App\Models\Resolution;
use App\Models\SecAoi;
use App\Models\SecCoi;
use App\Models\SecretaryCertificate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class CompanyCorporateRecordController extends Controller
{
    use GeneratesCorporateDocumentNumbers;
    use GeneratesPdfPreview;
    use HandlesUploads;
    use ResolvesCompanyRecords;

    public function notices(Request $request, int $company): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $notices = $this->companyScopedQuery(Notice::query(), new Notice(), $company)
            ->with(['minutes', 'resolutions', 'secretaryCertificates'])
            ->latest()
            ->get()
            ->each(fn (Notice $notice) => $notice->preview_url = route('company.corporate-formation.notices.preview', [$company, $notice->id]));

        return view('corporate.notices.index', [
            'notices' => $notices,
            'nextNoticeNumber' => $this->nextCompanyNoticeNumber($company),
            'sectionRibbonPartial' => 'company.partials.corporate-formation-ribbon',
            'noticeStoreUrl' => route('company.corporate-formation.notices.store', $company),
            'documentDefaultsUrl' => route('corporate-document-defaults'),
            ...$this->companyViewData($companyData, $company),
        ]);
    }

    public function storeNotice(Request $request, int $company): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $data = $this->validateNoticeData($request);
        $bodyHtml = $data['body_html'] ?? null;
        $hasUploadedDocument = $request->hasFile('document_path');
        $data['document_path'] = $this->handleUpload($request, 'document_path');
        $data['notice_number'] = $data['notice_number'] ?: $this->nextCompanyNoticeNumber($company);
        $data['body_mode'] = $this->resolveNoticeBodyMode($hasUploadedDocument, $bodyHtml, null, $data['body_mode'] ?? null);
        $data = $this->filterPersistableData('notices', $this->attachCompanyId(new Notice(), $data, $company));

        $notice = Notice::create($data);
        $this->syncGeneratedNoticePdf($notice, $bodyHtml, $hasUploadedDocument);

        return redirect()->route('company.corporate-formation.notices', $company)->with('success', 'Notice created.');
    }

    public function showNotice(Request $request, int $company, int $notice): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $noticeRecord = $this->findCompanyNotice($company, $notice);
        $noticeRecord->load(['minutes', 'resolutions', 'secretaryCertificates']);

        return view('corporate.notices.preview', [
            'notice' => $noticeRecord,
            'backRoute' => route('company.corporate-formation.notices', $company),
            'sectionRibbonPartial' => 'company.partials.corporate-formation-ribbon',
            ...$this->companyViewData($companyData, $company),
        ]);
    }

    public function updateNotice(Request $request, int $company, int $notice): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $noticeRecord = $this->findCompanyNotice($company, $notice);
        $data = $this->validateNoticeData($request);
        $bodyHtml = $data['body_html'] ?? null;
        $hasUploadedDocument = $request->hasFile('document_path');
        $data['document_path'] = $this->handleUpload($request, 'document_path', $noticeRecord->document_path);
        $data['body_mode'] = $this->resolveNoticeBodyMode($hasUploadedDocument, $bodyHtml, $noticeRecord, $data['body_mode'] ?? null);
        $data = $this->filterPersistableData('notices', $this->attachCompanyId(new Notice(), $data, $company));

        $noticeRecord->update($data);
        $this->syncGeneratedNoticePdf($noticeRecord->fresh(), $bodyHtml, $hasUploadedDocument);

        return redirect()->route('company.corporate-formation.notices', $company)->with('success', 'Notice updated.');
    }

    public function destroyNotice(Request $request, int $company, int $notice): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $this->findCompanyNotice($company, $notice)->delete();

        return redirect()->route('company.corporate-formation.notices', $company)->with('success', 'Notice deleted.');
    }

    public function minutes(Request $request, int $company): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $minutes = $this->companyScopedQuery(Minute::query(), new Minute(), $company)
            ->with('notice')
            ->latest()
            ->get()
            ->each(function (Minute $minute) use ($company) {
                $minute->preview_url = route('company.corporate-formation.minutes.preview', [$company, $minute->id]);
                $minute->approve_url = route('company.corporate-formation.minutes.approve', [$company, $minute->id]);
            });
        $notices = $this->companyScopedQuery(Notice::query(), new Notice(), $company)
            ->orderBy('date_of_meeting')
            ->get();

        return view('corporate.minutes.index', [
            'minutes' => $minutes,
            'notices' => $notices,
            'nextMinutesRef' => $this->nextCompanyMinutesRef($company),
            'sectionRibbonPartial' => 'company.partials.corporate-formation-ribbon',
            'minutesStoreUrl' => route('company.corporate-formation.minutes.store', $company),
            'documentDefaultsUrl' => route('corporate-document-defaults'),
            ...$this->companyViewData($companyData, $company),
        ]);
    }

    public function storeMinute(Request $request, int $company): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $data = $this->validateMinuteData($request);
        $notice = $this->findCompanyNotice($company, (int) $data['notice_id']);
        $data = $this->mergeNoticeData($data, $notice);
        $data['document_path'] = $this->handleUpload($request, 'document_path');
        $data['minutes_ref'] = $data['minutes_ref'] ?: $this->nextCompanyMinutesRef($company);
        $data = $this->filterPersistableData('minutes', $this->attachCompanyId(new Minute(), $data, $company));

        Minute::create($data);

        return redirect()->route('company.corporate-formation.minutes', $company)->with('success', 'Minutes created.');
    }

    public function showMinute(Request $request, int $company, int $minute): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $minuteRecord = $this->findCompanyMinute($company, $minute);
        $minuteRecord->load('notice');
        $templatePreviewPath = $this->generateCompanyMinuteTemplatePreviewPdf($minuteRecord);

        return view('corporate.minutes.preview', [
            'minute' => $minuteRecord,
            'backRoute' => route('company.corporate-formation.minutes', $company),
            'editRoute' => route('company.corporate-formation.minutes.preview', [$company, $minuteRecord->id]),
            'deleteRoute' => route('company.corporate-formation.minutes.destroy', [$company, $minuteRecord->id]),
            'workspaceSaveUrl' => route('company.corporate-formation.minutes.workspace-save', [$company, $minuteRecord->id]),
            'finalAudioSaveUrl' => route('company.corporate-formation.minutes.final-audio', [$company, $minuteRecord->id]),
            'finalSaveUrl' => route('company.corporate-formation.minutes.final-save', [$company, $minuteRecord->id]),
            'templatePreviewUrl' => $templatePreviewPath ? route('uploads.show', ['path' => $templatePreviewPath]) : null,
            'templatePreviewDownloadUrl' => $templatePreviewPath ? route('uploads.show', ['path' => $templatePreviewPath, 'download' => 1]) : null,
            'sectionRibbonPartial' => 'company.partials.corporate-formation-ribbon',
            ...$this->companyViewData($companyData, $company),
        ]);
    }

    public function updateMinute(Request $request, int $company, int $minute): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $minuteRecord = $this->findCompanyMinute($company, $minute);
        $data = $this->validateMinuteData($request);
        $notice = $this->findCompanyNotice($company, (int) $data['notice_id']);
        $data = $this->mergeNoticeData($data, $notice);
        $data['document_path'] = $this->handleUpload($request, 'document_path', $minuteRecord->document_path);
        $data = $this->filterPersistableData('minutes', $this->attachCompanyId(new Minute(), $data, $company));

        $minuteRecord->update($data);

        return redirect()->route('company.corporate-formation.minutes', $company)->with('success', 'Minutes updated.');
    }

    public function approveMinute(Request $request, int $company, int $minute): RedirectResponse
    {
        abort_unless($this->userCanApprove(), 403);

        $minuteRecord = $this->findCompanyMinute($company, $minute);
        $request->validate([
            'approved_minutes_path' => [$minuteRecord->approved_minutes_path ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $this->updateExistingColumns($minuteRecord, 'minutes', [
            'approved_by' => auth()->user()?->name,
            'approved_minutes_path' => $this->handleUpload($request, 'approved_minutes_path', $minuteRecord->approved_minutes_path),
        ]);

        return back()->with('success', $request->hasFile('approved_minutes_path') ? 'Minutes approved and signed copy uploaded.' : 'Minutes approval updated.');
    }

    public function saveMinuteWorkspace(Request $request, int $company, int $minute): JsonResponse
    {
        $minuteRecord = $this->findCompanyMinute($company, $minute);
        $request->validate([
            'tentative_audio' => ['nullable', 'file', 'max:51200'],
            'remove_tentative_audio' => ['nullable', 'boolean'],
            'recording_clips' => ['nullable', 'array'],
            'recording_clips.*' => ['file', 'max:51200'],
            'sync_recording_clips' => ['nullable', 'boolean'],
            'retained_recording_clips' => ['nullable', 'array'],
            'retained_recording_clips.*' => ['string'],
            'meeting_video' => ['nullable', 'file', 'max:204800'],
            'remove_meeting_video' => ['nullable', 'boolean'],
            'script_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,txt', 'max:51200'],
            'remove_script_file' => ['nullable', 'boolean'],
            'recording_notes' => ['nullable', 'string'],
            'script_text' => ['nullable', 'string'],
        ]);

        $this->updateExistingColumns($minuteRecord, 'minutes', [
            'tentative_audio_path' => $this->handleUpload($request, 'tentative_audio', $minuteRecord->tentative_audio_path),
            'recording_clips' => $this->syncRecordingClips($request, $minuteRecord),
            'meeting_video_path' => $this->handleUpload($request, 'meeting_video', $minuteRecord->meeting_video_path),
            'script_file_path' => $this->handleUpload($request, 'script_file', $minuteRecord->script_file_path),
            'recording_notes' => $request->input('recording_notes', $minuteRecord->recording_notes),
            'script_text' => $request->input('script_text', $minuteRecord->script_text),
        ]);

        return response()->json($this->workspacePayload($minuteRecord->fresh(), $company));
    }

    public function saveMinuteFinalRecording(Request $request, int $company, int $minute): JsonResponse
    {
        $minuteRecord = $this->findCompanyMinute($company, $minute);
        $request->validate([
            'final_audio' => ['nullable', 'file', 'max:51200'],
            'remove_final_audio' => ['nullable', 'boolean'],
        ]);

        $finalPath = $this->handleUpload($request, 'final_audio', $minuteRecord->final_audio_path);
        if (
            !$request->hasFile('final_audio')
            && !$request->boolean('remove_final_audio')
            && !$finalPath
            && $minuteRecord->tentative_audio_path
        ) {
            $finalPath = $this->duplicateUpload($minuteRecord->tentative_audio_path);
        }

        if (!$finalPath) {
            return response()->json(['message' => 'No tentative audio is available to save to the final preview.'], 422);
        }

        $this->updateExistingColumns($minuteRecord, 'minutes', ['final_audio_path' => $finalPath]);

        return response()->json($this->workspacePayload($minuteRecord->fresh(), $company));
    }

    public function saveMinuteFinalPreview(Request $request, int $company, int $minute): JsonResponse
    {
        $minuteRecord = $this->findCompanyMinute($company, $minute);
        $request->validate([
            'tentative_audio' => ['nullable', 'file', 'max:51200'],
            'remove_tentative_audio' => ['nullable', 'boolean'],
            'final_audio' => ['nullable', 'file', 'max:51200'],
            'remove_final_audio' => ['nullable', 'boolean'],
            'recording_clips' => ['nullable', 'array'],
            'recording_clips.*' => ['file', 'max:51200'],
            'sync_recording_clips' => ['nullable', 'boolean'],
            'retained_recording_clips' => ['nullable', 'array'],
            'retained_recording_clips.*' => ['string'],
            'meeting_video' => ['nullable', 'file', 'max:204800'],
            'remove_meeting_video' => ['nullable', 'boolean'],
            'script_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,txt', 'max:51200'],
            'remove_script_file' => ['nullable', 'boolean'],
            'recording_notes' => ['nullable', 'string'],
            'script_text' => ['nullable', 'string'],
        ]);

        $finalPath = $this->handleUpload($request, 'final_audio', $minuteRecord->final_audio_path);
        if (
            !$request->hasFile('final_audio')
            && !$request->boolean('remove_final_audio')
            && !$finalPath
            && $minuteRecord->tentative_audio_path
        ) {
            $finalPath = $this->duplicateUpload($minuteRecord->tentative_audio_path);
        }

        $this->updateExistingColumns($minuteRecord, 'minutes', [
            'tentative_audio_path' => $this->handleUpload($request, 'tentative_audio', $minuteRecord->tentative_audio_path),
            'final_audio_path' => $finalPath,
            'recording_clips' => $this->syncRecordingClips($request, $minuteRecord),
            'meeting_video_path' => $this->handleUpload($request, 'meeting_video', $minuteRecord->meeting_video_path),
            'script_file_path' => $this->handleUpload($request, 'script_file', $minuteRecord->script_file_path),
            'recording_notes' => $request->input('recording_notes', $minuteRecord->recording_notes),
            'script_text' => $request->input('script_text', $minuteRecord->script_text),
        ]);

        return response()->json($this->workspacePayload($minuteRecord->fresh(), $company));
    }

    public function destroyMinute(Request $request, int $company, int $minute): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $this->findCompanyMinute($company, $minute)->delete();

        return redirect()->route('company.corporate-formation.minutes', $company)->with('success', 'Minutes deleted.');
    }

    public function resolutions(Request $request, int $company): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $resolutions = $this->companyScopedQuery(Resolution::query(), new Resolution(), $company)
            ->with(['minute', 'notice', 'secretaryCertificates'])
            ->latest()
            ->get()
            ->each(fn (Resolution $resolution) => $resolution->preview_url = route('company.corporate-formation.resolutions.preview', [$company, $resolution->id]));
        $minutes = $this->companyScopedQuery(Minute::query(), new Minute(), $company)
            ->with('notice')
            ->orderBy('date_of_meeting')
            ->get();

        return view('corporate.resolutions.index', [
            'resolutions' => $resolutions,
            'minutes' => $minutes,
            'nextResolutionNumber' => $this->nextCompanyResolutionNumber($company),
            'sectionRibbonPartial' => 'company.partials.corporate-formation-ribbon',
            'resolutionStoreUrl' => route('company.corporate-formation.resolutions.store', $company),
            'documentDefaultsUrl' => route('corporate-document-defaults'),
            ...$this->companyViewData($companyData, $company),
        ]);
    }

    public function storeResolution(Request $request, int $company): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $data = $this->validateResolutionData($request);
        $minute = !empty($data['minute_id']) ? $this->findCompanyMinute($company, (int) $data['minute_id']) : null;
        $data = $this->mergeMinuteData($data, $minute);
        $data['draft_file_path'] = $this->handleUpload($request, 'draft_file_path');
        $data['notarized_file_path'] = $this->handleUpload($request, 'notarized_file_path');
        $data['resolution_no'] = $data['resolution_no'] ?: $this->nextCompanyResolutionNumber($company);
        $data = $this->filterPersistableData('resolutions', $this->attachCompanyId(new Resolution(), $data, $company));

        $resolution = Resolution::create($data);
        $this->syncGeneratedResolutionPdf($resolution, $request->hasFile('draft_file_path'));
        $this->syncSecretaryCertificates($resolution, $company);

        return redirect()->route('company.corporate-formation.resolutions', $company)->with('success', 'Resolution created.');
    }

    public function showResolution(Request $request, int $company, int $resolution): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $resolutionRecord = $this->findCompanyResolution($company, $resolution);
        $this->syncGeneratedResolutionPdf($resolutionRecord, false);
        $resolutionRecord = $resolutionRecord->fresh();
        $resolutionRecord->load(['notice', 'secretaryCertificates']);
        $generatedBodyPreviewPath = $this->generateResolutionPdf(
            $resolutionRecord,
            'generated-previews/resolutions/' . ($resolutionRecord->resolution_no ?: $resolutionRecord->id) . '-body-built.pdf'
        );

        return view('corporate.resolutions.preview', [
            'resolution' => $resolutionRecord,
            'generatedBodyPreviewUrl' => $generatedBodyPreviewPath ? route('uploads.show', ['path' => $generatedBodyPreviewPath]) : null,
            'backRoute' => route('company.corporate-formation.resolutions', $company),
            'editRoute' => route('company.corporate-formation.resolutions.preview', [$company, $resolutionRecord->id]),
            'updateRoute' => route('company.corporate-formation.resolutions.update', [$company, $resolutionRecord->id]),
            'deleteRoute' => route('company.corporate-formation.resolutions.destroy', [$company, $resolutionRecord->id]),
            'sectionRibbonPartial' => 'company.partials.corporate-formation-ribbon',
            ...$this->companyViewData($companyData, $company),
        ]);
    }

    public function updateResolution(Request $request, int $company, int $resolution): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $resolutionRecord = $this->findCompanyResolution($company, $resolution);
        $data = $this->validateResolutionData($request);
        $minute = !empty($data['minute_id']) ? $this->findCompanyMinute($company, (int) $data['minute_id']) : null;
        $data = $this->mergeMinuteData($data, $minute);
        $data['draft_file_path'] = $this->handleUpload($request, 'draft_file_path', $resolutionRecord->draft_file_path);
        $data['notarized_file_path'] = $this->handleUpload($request, 'notarized_file_path', $resolutionRecord->notarized_file_path);
        $data = $this->filterPersistableData('resolutions', $this->attachCompanyId(new Resolution(), $data, $company));

        $resolutionRecord->update($data);
        $this->syncGeneratedResolutionPdf($resolutionRecord->fresh(), $request->hasFile('draft_file_path'));
        $this->syncSecretaryCertificates($resolutionRecord->fresh(), $company);

        return redirect()->route('company.corporate-formation.resolutions', $company)->with('success', 'Resolution updated.');
    }

    public function destroyResolution(Request $request, int $company, int $resolution): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $this->findCompanyResolution($company, $resolution)->delete();

        return redirect()->route('company.corporate-formation.resolutions', $company)->with('success', 'Resolution deleted.');
    }

    public function secretaryCertificates(Request $request, int $company): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $certificates = $this->companyScopedQuery(SecretaryCertificate::query(), new SecretaryCertificate(), $company)
            ->with(['notice', 'resolution', 'minute'])
            ->latest()
            ->get()
            ->each(fn (SecretaryCertificate $certificate) => $certificate->preview_url = route('company.corporate-formation.secretary-certificates.preview', [$company, $certificate->id]));
        $resolutions = $this->companyScopedQuery(Resolution::query(), new Resolution(), $company)
            ->with(['notice', 'minute'])
            ->orderBy('date_of_meeting')
            ->get();
        $minutes = $this->companyScopedQuery(Minute::query(), new Minute(), $company)
            ->with('notice')
            ->orderBy('date_of_meeting')
            ->get();

        return view('corporate.secretary-certificates.index', [
            'certificates' => $certificates,
            'resolutions' => $resolutions,
            'minutes' => $minutes,
            'nextCertificateNumber' => $this->nextCompanySecretaryCertificateNumber($company),
            'sectionRibbonPartial' => 'company.partials.corporate-formation-ribbon',
            'certificateStoreUrl' => route('company.corporate-formation.secretary-certificates.store', $company),
            'documentDefaultsUrl' => route('corporate-document-defaults'),
            ...$this->companyViewData($companyData, $company),
        ]);
    }

    public function storeSecretaryCertificate(Request $request, int $company): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $data = $this->validateSecretaryCertificateData($request);
        $data = $this->mergeMeetingSourceData($data, $company);
        $data['document_path'] = $this->handleUpload($request, 'document_path');
        $data['certificate_no'] = $data['certificate_no'] ?: $this->nextCompanySecretaryCertificateNumber($company);
        $data = $this->filterPersistableData('secretary_certificates', $this->attachCompanyId(new SecretaryCertificate(), $data, $company));

        SecretaryCertificate::create($data);

        return redirect()->route('company.corporate-formation.secretary-certificates', $company)->with('success', 'Secretary certificate created.');
    }

    public function showSecretaryCertificate(Request $request, int $company, int $certificate): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $certificateRecord = $this->findCompanySecretaryCertificate($company, $certificate);
        $certificateRecord->load(['notice', 'resolution.notice', 'minute.notice']);
        $generatedDraftPath = $this->generatePdfPreview(
            'corporate.secretary-certificates.pdf',
            ['certificate' => $certificateRecord],
            'generated-previews/secretary-certificates/' . ($certificateRecord->certificate_no ?: $certificateRecord->id) . '-draft.pdf'
        );

        return view('corporate.secretary-certificates.preview', [
            'certificate' => $certificateRecord,
            'generatedDraftUrl' => $generatedDraftPath ? route('uploads.show', ['path' => $generatedDraftPath]) : null,
            'backRoute' => route('company.corporate-formation.secretary-certificates', $company),
            'editRoute' => route('company.corporate-formation.secretary-certificates.preview', [$company, $certificateRecord->id]),
            'updateRoute' => route('company.corporate-formation.secretary-certificates.update', [$company, $certificateRecord->id]),
            'deleteRoute' => route('company.corporate-formation.secretary-certificates.destroy', [$company, $certificateRecord->id]),
            'sectionRibbonPartial' => 'company.partials.corporate-formation-ribbon',
            ...$this->companyViewData($companyData, $company),
        ]);
    }

    public function updateSecretaryCertificate(Request $request, int $company, int $certificate): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $certificateRecord = $this->findCompanySecretaryCertificate($company, $certificate);
        $data = $this->validateSecretaryCertificateData($request);
        $data = $this->mergeMeetingSourceData($data, $company);
        $data['document_path'] = $this->handleUpload($request, 'document_path', $certificateRecord->document_path);
        $data = $this->filterPersistableData('secretary_certificates', $this->attachCompanyId(new SecretaryCertificate(), $data, $company));

        $certificateRecord->update($data);

        return redirect()->route('company.corporate-formation.secretary-certificates', $company)->with('success', 'Secretary certificate updated.');
    }

    public function destroySecretaryCertificate(Request $request, int $company, int $certificate): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $this->findCompanySecretaryCertificate($company, $certificate)->delete();

        return redirect()->route('company.corporate-formation.secretary-certificates', $company)->with('success', 'Secretary certificate deleted.');
    }

    private function validateNoticeData(Request $request): array
    {
        return $request->validate([
            'notice_number' => ['nullable', 'string', 'max:255'],
            'date_of_notice' => ['nullable', 'date'],
            'governing_body' => ['nullable', 'string', 'max:255'],
            'type_of_meeting' => ['nullable', 'string', 'max:255'],
            'date_of_meeting' => ['nullable', 'date'],
            'time_started' => ['nullable'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_no' => ['nullable', 'string', 'max:255'],
            'chairman' => ['nullable', 'string', 'max:255'],
            'secretary' => ['nullable', 'string', 'max:255'],

            // President-requested notice procedure fields.
            // These are saved for company notices separately through company_id.
            'meeting_mode' => ['nullable', 'string', 'max:255'],
            'meeting_platform' => ['nullable', 'string', 'max:255'],
            'meeting_link_details' => ['nullable', 'string', 'max:1000'],
            'authorized_meeting_officer' => ['nullable', 'string', 'max:255'],
            'confirmation_email' => ['nullable', 'string', 'max:255'],
            'confirmation_phone' => ['nullable', 'string', 'max:255'],
            'office_address' => ['nullable', 'string', 'max:1000'],
            'email_phone_confirmation_deadline' => ['nullable', 'string', 'max:255'],
            'physical_submission_deadline' => ['nullable', 'string', 'max:255'],
            'authority_calling_meeting' => ['nullable', 'string', 'max:255'],

            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'date_updated' => ['nullable', 'date'],
            'body_html' => ['nullable', 'string'],
            'body_mode' => ['nullable', 'string', 'max:50'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);
    }

    private function validateMinuteData(Request $request): array
    {
        return $request->validate([
            'minutes_ref' => ['nullable', 'string', 'max:255'],
            'date_uploaded' => ['nullable', 'date'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'governing_body' => ['nullable', 'string', 'max:255'],
            'type_of_meeting' => ['nullable', 'string', 'max:255'],
            'meeting_mode' => ['nullable', 'string', 'max:255'],
            'notice_id' => ['required', 'integer'],
            'notice_ref' => ['nullable', 'string', 'max:255'],
            'date_of_meeting' => ['nullable', 'date'],
            'time_started' => ['nullable'],
            'time_ended' => ['nullable'],
            'location' => ['nullable', 'string', 'max:255'],
            'call_link' => ['nullable', 'string', 'max:255'],
            'recording_notes' => ['nullable', 'string'],
            'script_text' => ['nullable', 'string'],
            'meeting_no' => ['nullable', 'string', 'max:255'],
            'chairman' => ['nullable', 'string', 'max:255'],
            'secretary' => ['nullable', 'string', 'max:255'],
            'directors_present' => ['nullable', 'string'],
            'directors_absent' => ['nullable', 'string'],
            'secretariat' => ['nullable', 'string'],
            'guests' => ['nullable', 'string'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);
    }

    private function validateResolutionData(Request $request): array
    {
        return $request->validate([
            'resolution_no' => ['nullable', 'string', 'max:255'],
            'date_uploaded' => ['nullable', 'date'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'minute_id' => ['nullable', 'integer', 'required_without:notice_id'],
            'governing_body' => ['nullable', 'string', 'max:255'],
            'type_of_meeting' => ['nullable', 'string', 'max:255'],
            'notice_id' => ['nullable', 'integer'],
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

    private function validateSecretaryCertificateData(Request $request): array
    {
        return $request->validate([
            'certificate_no' => ['nullable', 'string', 'max:255'],
            'date_uploaded' => ['nullable', 'date'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'governing_body' => ['nullable', 'string', 'max:255'],
            'type_of_meeting' => ['nullable', 'string', 'max:255'],
            'minute_id' => ['nullable', 'integer', 'required_without:resolution_id'],
            'notice_id' => ['nullable', 'integer'],
            'notice_ref' => ['nullable', 'string', 'max:255'],
            'meeting_no' => ['nullable', 'string', 'max:255'],
            'minutes_ref' => ['nullable', 'string', 'max:255'],
            'resolution_id' => ['nullable', 'integer', 'required_without:minute_id'],
            'resolution_no' => ['nullable', 'string', 'max:255'],
            'resolution_body' => ['nullable', 'string'],
            'date_issued' => ['nullable', 'date'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'date_of_meeting' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'secretary' => ['nullable', 'string', 'max:255'],
            'notary_doc_no' => ['nullable', 'string', 'max:255'],
            'notary_page_no' => ['nullable', 'string', 'max:255'],
            'notary_book_no' => ['nullable', 'string', 'max:255'],
            'notary_series_no' => ['nullable', 'string', 'max:255'],
            'notary_public' => ['nullable', 'string', 'max:255'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);
    }

    private function mergeNoticeData(array $data, Notice $notice): array
    {
        $data['notice_ref'] = $data['notice_ref'] ?: $notice->notice_number;
        $data['governing_body'] = $data['governing_body'] ?: $notice->governing_body;
        $data['type_of_meeting'] = $data['type_of_meeting'] ?: $notice->type_of_meeting;
        $data['date_of_meeting'] = $data['date_of_meeting'] ?: optional($notice->date_of_meeting)?->toDateString();
        $data['time_started'] = $data['time_started'] ?: $notice->time_started;
        $data['location'] = $data['location'] ?: $notice->location;
        $data['meeting_no'] = $data['meeting_no'] ?: $notice->meeting_no;
        $data['chairman'] = $data['chairman'] ?: $notice->chairman;
        $data['secretary'] = $data['secretary'] ?: $notice->secretary;

        return $data;
    }

    private function mergeMinuteData(array $data, ?Minute $minute): array
    {
        if (!$minute) {
            return $data;
        }

        $minute->loadMissing('notice');
        $notice = $minute->notice;
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

    private function mergeMeetingSourceData(array $data, int $company): array
    {
        if (!empty($data['resolution_id'])) {
            $resolution = $this->findCompanyResolution($company, (int) $data['resolution_id']);
            $resolution->loadMissing(['notice', 'minute']);

            $data['minute_id'] = $resolution->minute_id;
            $data['minutes_ref'] = $resolution->minute?->minutes_ref;
            $data['notice_id'] = $resolution->notice_id;
            $data['notice_ref'] = $resolution->notice_ref;
            $data['resolution_no'] = $resolution->resolution_no;
            $data['resolution_body'] = $data['resolution_body'] ?: $resolution->resolution_body;
            $data['governing_body'] = $resolution->governing_body;
            $data['type_of_meeting'] = $resolution->type_of_meeting;
            $data['meeting_no'] = $resolution->meeting_no;
            $data['purpose'] = $data['purpose'] ?: $resolution->board_resolution;
            $data['date_of_meeting'] = $resolution->date_of_meeting;
            $data['location'] = $resolution->location;
            $data['secretary'] = $resolution->secretary;
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

        $minute = $this->findCompanyMinute($company, (int) $data['minute_id']);
        $minute->loadMissing('notice');
        $notice = $minute->notice;
        $data['minutes_ref'] = $minute->minutes_ref;
        $data['notice_id'] = $minute->notice_id;
        $data['notice_ref'] = $minute->notice_ref ?: $notice?->notice_number;
        $data['governing_body'] = $minute->governing_body ?: $notice?->governing_body;
        $data['type_of_meeting'] = $minute->type_of_meeting ?: $notice?->type_of_meeting;
        $data['meeting_no'] = $minute->meeting_no ?: $notice?->meeting_no;
        $data['date_of_meeting'] = $minute->date_of_meeting ?: $notice?->date_of_meeting;
        $data['location'] = $minute->location ?: $notice?->location;
        $data['secretary'] = $minute->secretary ?: $notice?->secretary;
        $data['resolution_id'] = null;
        $data['resolution_no'] = $data['resolution_no'] ?: null;
        $data['resolution_body'] = $data['resolution_body'] ?: ('Certified from Minutes Ref. ' . ($minute->minutes_ref ?: '') . '.');
        $data['purpose'] = $data['purpose'] ?: ('Certified extract from Minutes Ref. ' . ($minute->minutes_ref ?: ''));

        return $data;
    }

    private function syncSecretaryCertificates(Resolution $resolution, int $company): void
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
            'notary_doc_no' => $resolution->notary_doc_no,
            'notary_page_no' => $resolution->notary_page_no,
            'notary_book_no' => $resolution->notary_book_no,
            'notary_series_no' => $resolution->notary_series_no,
            'notary_public' => $resolution->notary_public,
        ];

        $this->companyScopedQuery($resolution->secretaryCertificates()->getQuery(), new SecretaryCertificate(), $company)
            ->where('resolution_id', $resolution->id)
            ->get()
            ->each(fn (SecretaryCertificate $certificate) => $certificate->update($shared));
    }

    private function filterPersistableData(string $table, array $data): array
    {
        return collect($data)->filter(fn ($value, $key) => Schema::hasColumn($table, $key))->all();
    }

    private function updateExistingColumns(Minute $minute, string $table, array $data): void
    {
        $payload = collect($data)->filter(fn ($value, $key) => Schema::hasColumn($table, $key))->all();
        if ($payload !== []) {
            $minute->update($payload);
        }
    }

    private function workspacePayload(Minute $minute, int $company): array
    {
        $templatePreviewPath = $this->generateCompanyMinuteTemplatePreviewPdf($minute);

        return [
            'message' => 'Workspace files saved.',
            'tentative_audio_url' => $minute->tentative_audio_path ? route('uploads.show', ['path' => $minute->tentative_audio_path]) : null,
            'tentative_audio_download_url' => $minute->tentative_audio_path ? route('uploads.show', ['path' => $minute->tentative_audio_path, 'download' => 1]) : null,
            'tentative_audio_filename' => $minute->tentative_audio_path ? basename($minute->tentative_audio_path) : null,
            'final_audio_url' => $minute->final_audio_path ? route('uploads.show', ['path' => $minute->final_audio_path]) : null,
            'final_audio_download_url' => $minute->final_audio_path ? route('uploads.show', ['path' => $minute->final_audio_path, 'download' => 1]) : null,
            'final_audio_filename' => $minute->final_audio_path ? basename($minute->final_audio_path) : null,
            'meeting_video_url' => $minute->meeting_video_path ? route('uploads.show', ['path' => $minute->meeting_video_path]) : null,
            'meeting_video_download_url' => $minute->meeting_video_path ? route('uploads.show', ['path' => $minute->meeting_video_path, 'download' => 1]) : null,
            'meeting_video_filename' => $minute->meeting_video_path ? basename($minute->meeting_video_path) : null,
            'script_file_url' => $minute->script_file_path ? route('uploads.show', ['path' => $minute->script_file_path]) : null,
            'script_file_download_url' => $minute->script_file_path ? route('uploads.show', ['path' => $minute->script_file_path, 'download' => 1]) : null,
            'script_file_filename' => $minute->script_file_path ? basename($minute->script_file_path) : null,
            'recording_notes' => $minute->recording_notes,
            'script_text' => $minute->script_text,
            'template_preview_url' => $templatePreviewPath ? route('uploads.show', ['path' => $templatePreviewPath]) : null,
            'template_preview_download_url' => $templatePreviewPath ? route('uploads.show', ['path' => $templatePreviewPath, 'download' => 1]) : null,
            'recording_clips' => collect($minute->recording_clips ?? [])->map(fn ($path) => [
                'id' => $path,
                'url' => route('uploads.show', ['path' => $path]),
                'download_url' => route('uploads.show', ['path' => $path, 'download' => 1]),
                'filename' => basename($path),
                'saved' => true,
                'preview_url' => route('company.corporate-formation.minutes.preview', [$company, $minute->id]),
            ])->values()->all(),
        ];
    }

    private function syncRecordingClips(Request $request, Minute $minute): array
    {
        $currentClips = collect($minute->recording_clips ?? []);
        $clips = $request->boolean('sync_recording_clips')
            ? $currentClips->filter(fn ($path) => in_array($path, $request->input('retained_recording_clips', []), true))->values()
            : $currentClips->values();

        $currentClips->diff($clips)->each(fn ($path) => Storage::disk('public')->delete($path));
        if ($request->hasFile('recording_clips')) {
            foreach ($request->file('recording_clips') as $clip) {
                $clips->push($this->storeUploadedFile($clip));
            }
        }

        return $clips->values()->all();
    }

    private function userCanApprove(): bool
    {
        return auth()->check() && auth()->user()?->role === 'Admin';
    }

    private function generateCompanyMinuteTemplatePreviewPdf(Minute $minute): ?string
    {
        $targetPath = 'uploads/minutes/template-preview-' . $minute->id . '.pdf';

        return $this->generatePdfPreview('corporate.minutes.pdf', [
            'minute' => $minute,
            'minutesDocumentTitle' => strtoupper(trim('Minutes of the ' . ($minute->type_of_meeting ?: 'Special') . ' ' . ($minute->governing_body ?: 'Meeting'))),
        ], $targetPath);
    }

    private function duplicateUpload(string $existingPath): ?string
    {
        if (!Storage::disk('public')->exists($existingPath)) {
            return null;
        }

        $copyPath = 'uploads/' . uniqid('final-audio-', true) . '/' . basename($existingPath);
        Storage::disk('public')->copy($existingPath, $copyPath);

        return $copyPath;
    }

    private function resolveNoticeBodyMode(bool $hasUploadedDocument, ?string $bodyHtml, ?Notice $existingNotice = null, ?string $requestedMode = null): string
    {
        if ($hasUploadedDocument) {
            return 'upload';
        }
        if ($this->hasMeaningfulBodyHtml($bodyHtml)) {
            return 'builder';
        }
        if ($existingNotice && ($existingNotice->body_mode === 'upload') && !$existingNotice->body_html) {
            return 'upload';
        }
        if ($requestedMode === 'upload' && !$this->hasMeaningfulBodyHtml($bodyHtml) && $existingNotice?->document_path) {
            return 'upload';
        }

        return 'builder';
    }

    private function hasMeaningfulBodyHtml(?string $bodyHtml): bool
    {
        return trim(strip_tags((string) $bodyHtml, '<br>')) !== '';
    }

    private function syncGeneratedNoticePdf(Notice $notice, ?string $bodyHtml, bool $hasUploadedDocument): void
    {
        if ($hasUploadedDocument || ($notice->body_mode ?? 'builder') !== 'builder') {
            return;
        }

        $pdfPath = $this->generateNoticePdf($notice->fresh(), $bodyHtml ?? $notice->body_html);
        if (!$pdfPath) {
            return;
        }

        $payload = ['document_path' => $pdfPath];
        if (Schema::hasColumn('notices', 'body_mode')) {
            $payload['body_mode'] = 'builder';
        }

        $notice->update($payload);
    }

    private function generateNoticePdf(Notice $notice, ?string $bodyHtml): ?string
    {
        $browserBinary = $this->browserBinary();
        if (!$browserBinary) {
            return null;
        }

        $html = view('corporate.notices.pdf', [
            'notice' => $notice,
            'bodyHtml' => $bodyHtml,
        ])->render();

        $tempDirectory = storage_path('app/temp');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0777, true);
        }

        $basename = 'notice-' . Str::slug($notice->notice_number ?: 'draft-notice');
        $htmlPath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-' . Str::uuid() . '.html';
        $pdfPath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-' . Str::uuid() . '.pdf';
        $profilePath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-profile-' . Str::uuid();

        file_put_contents($htmlPath, $html);
        if (!is_dir($profilePath)) {
            mkdir($profilePath, 0777, true);
        }

        $process = new Process([
            $browserBinary,
            '--headless',
            '--disable-gpu',
            '--user-data-dir=' . $profilePath,
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-crash-reporter',
            '--disable-features=Crashpad',
            '--noerrdialogs',
            '--allow-file-access-from-files',
            '--disable-web-security',
            '--print-to-pdf=' . $pdfPath,
            '--no-pdf-header-footer',
            'file:///' . str_replace(DIRECTORY_SEPARATOR, '/', $htmlPath),
        ]);

        $process->setTimeout(60);
        $process->setEnv([
            'TEMP' => $tempDirectory,
            'TMP' => $tempDirectory,
            'LOCALAPPDATA' => $tempDirectory,
            'APPDATA' => $tempDirectory,
        ]);
        $process->run();

        @unlink($htmlPath);
        $this->deleteDirectory($profilePath);

        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            @unlink($pdfPath);

            return null;
        }

        $targetPath = 'uploads/notices/' . ($notice->notice_number ?: 'draft-notice') . '.pdf';
        if ($notice->document_path) {
            Storage::disk('public')->delete($notice->document_path);
        }
        Storage::disk('public')->put($targetPath, file_get_contents($pdfPath));
        @unlink($pdfPath);

        return $targetPath;
    }

    private function syncGeneratedResolutionPdf(Resolution $resolution, bool $hasUploadedDraft): void
    {
        if ($hasUploadedDraft || $resolution->draft_file_path) {
            return;
        }

        $pdfPath = $this->generateResolutionPdf($resolution->fresh());
        if (!$pdfPath) {
            return;
        }

        $resolution->update(['draft_file_path' => $pdfPath]);
    }

    private function generateResolutionPdf(Resolution $resolution, ?string $targetPath = null): ?string
    {
        $browserBinary = $this->browserBinary();
        if (!$browserBinary) {
            return null;
        }

        $html = view('corporate.resolutions.pdf', ['resolution' => $resolution])->render();
        $tempDirectory = storage_path('app/temp');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0777, true);
        }

        $basename = 'resolution-' . Str::slug($resolution->resolution_no ?: 'draft-resolution');
        $htmlPath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-' . Str::uuid() . '.html';
        $pdfPath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-' . Str::uuid() . '.pdf';
        $profilePath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-profile-' . Str::uuid();

        file_put_contents($htmlPath, $html);
        if (!is_dir($profilePath)) {
            mkdir($profilePath, 0777, true);
        }

        $process = new Process([
            $browserBinary,
            '--headless',
            '--disable-gpu',
            '--user-data-dir=' . $profilePath,
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-crash-reporter',
            '--disable-features=Crashpad',
            '--noerrdialogs',
            '--allow-file-access-from-files',
            '--disable-web-security',
            '--print-to-pdf=' . $pdfPath,
            '--no-pdf-header-footer',
            'file:///' . str_replace(DIRECTORY_SEPARATOR, '/', $htmlPath),
        ]);

        $process->setTimeout(60);
        $process->setEnv([
            'TEMP' => $tempDirectory,
            'TMP' => $tempDirectory,
            'LOCALAPPDATA' => $tempDirectory,
            'APPDATA' => $tempDirectory,
        ]);
        $process->run();

        @unlink($htmlPath);
        $this->deleteDirectory($profilePath);
        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            @unlink($pdfPath);

            return null;
        }

        $targetPath = $targetPath ?: 'uploads/resolutions/' . ($resolution->resolution_no ?: 'draft-resolution') . '.pdf';
        Storage::disk('public')->delete($targetPath);
        Storage::disk('public')->put($targetPath, file_get_contents($pdfPath));
        @unlink($pdfPath);

        return $targetPath;
    }

    private function browserBinary(): ?string
    {
        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = array_diff(scandir($directory) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }

    private function companyScopedQuery(Builder $query, object $model, int $company): Builder
    {
        return Schema::hasColumn($model->getTable(), 'company_id')
            ? $query->where($model->getTable() . '.company_id', $company)
            : $query;
    }

    private function attachCompanyId(object $model, array $payload, int $company): array
    {
        if (Schema::hasColumn($model->getTable(), 'company_id')) {
            $payload['company_id'] = $company;
        }

        return $payload;
    }

    private function findCompanyNotice(int $company, int $notice): Notice
    {
        return $this->companyScopedQuery(Notice::query(), new Notice(), $company)->findOrFail($notice);
    }

    private function findCompanyMinute(int $company, int $minute): Minute
    {
        return $this->companyScopedQuery(Minute::query(), new Minute(), $company)->findOrFail($minute);
    }

    private function findCompanyResolution(int $company, int $resolution): Resolution
    {
        return $this->companyScopedQuery(Resolution::query(), new Resolution(), $company)->findOrFail($resolution);
    }

    private function findCompanySecretaryCertificate(int $company, int $certificate): SecretaryCertificate
    {
        return $this->companyScopedQuery(SecretaryCertificate::query(), new SecretaryCertificate(), $company)->findOrFail($certificate);
    }

    private function companyViewData(array $companyData, int $company): array
    {
        $companyRegNo = $this->resolveCompanyRegNo($company);

        return [
            'company' => (object) $companyData,
            'companyRecord' => (object) $companyData,
            'companyName' => $companyData['company_name'] ?? 'JK&C INC.',
            'companyAddress' => $companyData['address'] ?? 'Cebu City',
            'companyRegNo' => $companyRegNo ?: '2025120230900-02',
        ];
    }

    private function resolveCompanyRegNo(int $company): ?string
    {
        $secCoi = $this->companyScopedQuery(SecCoi::query(), new SecCoi(), $company)->latest()->value('company_reg_no');
        if ($secCoi) {
            return $secCoi;
        }

        $secAoi = $this->companyScopedQuery(SecAoi::query(), new SecAoi(), $company)->latest()->value('company_reg_no');
        if ($secAoi) {
            return $secAoi;
        }

        return $this->companyScopedQuery(GisRecord::query(), new GisRecord(), $company)->latest()->value('company_reg_no');
    }

    private function nextCompanyNoticeNumber(int $company): string
    {
        return $this->nextCompanySequenceFor(Notice::class, 'notice_number', now()->year . '-', $company, 3);
    }

    private function nextCompanyMinutesRef(int $company): string
    {
        return $this->nextCompanySequenceFor(Minute::class, 'minutes_ref', 'MIN-' . now()->year . '-', $company, 3);
    }

    private function nextCompanyResolutionNumber(int $company): string
    {
        return $this->nextCompanySequenceFor(Resolution::class, 'resolution_no', 'RES-' . now()->year . '-', $company, 3);
    }

    private function nextCompanySecretaryCertificateNumber(int $company): string
    {
        return $this->nextCompanySequenceFor(SecretaryCertificate::class, 'certificate_no', 'SEC-' . now()->year . '-', $company, 3);
    }

    private function nextCompanySequenceFor(string $modelClass, string $column, string $prefix, int $company, int $pad = 4): string
    {
        $instance = new $modelClass();
        $query = $modelClass::query()->where($column, 'like', $prefix . '%');
        if (Schema::hasColumn($instance->getTable(), 'company_id')) {
            $query->where('company_id', $company);
        }

        $values = $query->pluck($column)->all();
        $max = 0;
        foreach ($values as $value) {
            if (is_string($value) && preg_match('/(\d+)$/', $value, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix . str_pad((string) ($max + 1), $pad, '0', STR_PAD_LEFT);
    }

    private function findCompanyOrAbort(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, $this->defaultCompanies());
    }

    private function defaultCompanies(): array
    {
        return [
            ['id' => 1, 'company_name' => 'Company 1', 'company_type' => 'Corporation', 'email' => 'company1@example.com', 'phone' => '09012345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Makati City', 'owner_name' => 'Owner 1', 'created_at' => '2026-03-01 10:00:00'],
            ['id' => 2, 'company_name' => 'Company 2', 'company_type' => 'Corporation', 'email' => 'company2@example.com', 'phone' => '09000345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Taguig City', 'owner_name' => 'Owner 2', 'created_at' => '2026-03-02 10:00:00'],
            ['id' => 3, 'company_name' => 'Company 3', 'company_type' => 'Corporation', 'email' => 'company3@example.com', 'phone' => '09777345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Pasig City', 'owner_name' => 'Owner 3', 'created_at' => '2026-03-03 10:00:00'],
        ];
    }
}
