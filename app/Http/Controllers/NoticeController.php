<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesCorporateDocumentNumbers;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class NoticeController extends Controller
{
    use GeneratesCorporateDocumentNumbers;
    use HandlesUploads;

    public function index()
    {
        $notices = Notice::with(['minutes', 'resolutions', 'secretaryCertificates'])->latest()->get();

        return view('corporate.notices.index', [
            'notices' => $notices,
            'nextNoticeNumber' => $this->nextNoticeNumber(),
        ]);
    }

    public function create()
    {
        return view('corporate.common.form', [
            'title' => 'Add Notice of Meeting',
            'action' => route('notices.store'),
            'method' => 'POST',
            'cancelRoute' => route('notices'),
            'fields' => $this->fields(),
            'item' => new Notice([
                'notice_number' => $this->nextNoticeNumber(),
                'date_of_notice' => now()->toDateString(),
                'uploaded_by' => auth()->user()?->name ?? '',
                'date_updated' => now()->toDateString(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $bodyHtml = $data['body_html'] ?? null;
        $hasUploadedDocument = $request->hasFile('document_path');
        $data['document_path'] = $this->handleUpload($request, 'document_path');
        $data['notice_number'] = $data['notice_number'] ?: $this->nextNoticeNumber();
        $data['body_mode'] = $this->resolveBodyMode($hasUploadedDocument, $bodyHtml, null, $data['body_mode'] ?? null);
        $data = $this->filterPersistableData($data);

        $notice = Notice::create($data);
        $this->syncGeneratedNoticePdf($notice, $bodyHtml, $hasUploadedDocument);

        return redirect()->route('notices')->with('success', 'Notice created.');
    }

    public function show(Notice $notice)
    {
        $notice->load(['minutes', 'resolutions', 'secretaryCertificates']);

        return view('corporate.notices.preview', [
            'notice' => $notice,
        ]);
    }

    public function edit(Notice $notice)
    {
        return view('corporate.common.form', [
            'title' => 'Edit Notice of Meeting',
            'action' => route('notices.update', $notice),
            'method' => 'PUT',
            'cancelRoute' => route('notices'),
            'fields' => $this->fields(),
            'item' => $notice,
        ]);
    }

    public function update(Request $request, Notice $notice)
    {
        $data = $this->validateData($request);
        $bodyHtml = $data['body_html'] ?? null;
        $hasUploadedDocument = $request->hasFile('document_path');
        $data['document_path'] = $this->handleUpload($request, 'document_path', $notice->document_path);
        $data['body_mode'] = $this->resolveBodyMode($hasUploadedDocument, $bodyHtml, $notice, $data['body_mode'] ?? null);
        $data = $this->filterPersistableData($data);

        $notice->update($data);
        $this->syncGeneratedNoticePdf($notice->fresh(), $bodyHtml, $hasUploadedDocument);

        return redirect()->route('notices')->with('success', 'Notice updated.');
    }

    public function destroy(Notice $notice)
    {
        $notice->delete();

        return redirect()->route('notices')->with('success', 'Notice deleted.');
    }

    private function fields(): array
    {
        $fields = [
            ['name' => 'notice_number', 'label' => 'Notice Number', 'type' => 'text'],
            ['name' => 'date_of_notice', 'label' => 'Date of Notice', 'type' => 'date'],
            ['name' => 'governing_body', 'label' => 'Governing Body', 'type' => 'select', 'options' => $this->governingBodyOptions()],
            ['name' => 'type_of_meeting', 'label' => 'Type of Meeting', 'type' => 'select', 'options' => $this->meetingTypeOptions()],
            ['name' => 'date_of_meeting', 'label' => 'Date of Meeting', 'type' => 'date'],
            ['name' => 'time_started', 'label' => 'Time Started', 'type' => 'time'],
            ['name' => 'location', 'label' => 'Location', 'type' => 'text'],
            ['name' => 'meeting_no', 'label' => 'Meeting Number', 'type' => 'text'],
            ['name' => 'chairman', 'label' => 'Chairman / Presiding Officer', 'type' => 'text'],
            ['name' => 'secretary', 'label' => 'Corporate Secretary', 'type' => 'text'],
            ['name' => 'meeting_mode', 'label' => 'Selected Mode', 'type' => 'select', 'options' => ['Physical', 'Virtual', 'Hybrid', 'In Absentia', 'Proxy', 'Written Consent', 'Resolution by Circulation', 'Email Approval', 'Other']],
            ['name' => 'meeting_platform', 'label' => 'Platform', 'type' => 'select', 'options' => ['Physical Venue', 'Google Meet', 'Zoom', 'Microsoft Teams', 'Other']],
            ['name' => 'meeting_link_details', 'label' => 'Meeting Link / Details', 'type' => 'text'],
            ['name' => 'authorized_meeting_officer', 'label' => 'Corporate Secretary / Authorized Meeting Officer', 'type' => 'text'],
            ['name' => 'confirmation_email', 'label' => 'Email Address', 'type' => 'text'],
            ['name' => 'confirmation_phone', 'label' => 'Phone Number', 'type' => 'text'],
            ['name' => 'office_address', 'label' => 'Office Address', 'type' => 'text'],
            ['name' => 'email_phone_confirmation_deadline', 'label' => 'Email / Phone Confirmation Deadline', 'type' => 'text'],
            ['name' => 'physical_submission_deadline', 'label' => 'Physical Submission Deadline', 'type' => 'text'],
            ['name' => 'authority_calling_meeting', 'label' => 'Authority Calling the Meeting', 'type' => 'text'],
            ['name' => 'uploaded_by', 'label' => 'Uploaded By', 'type' => 'text'],
            ['name' => 'date_updated', 'label' => 'Date Updated', 'type' => 'date'],
            ['name' => 'document_path', 'label' => 'Upload Notice (PDF)', 'type' => 'file'],
        ];

        if (Schema::hasColumn('notices', 'body_html')) {
            $fields[] = ['name' => 'body_html', 'label' => 'Notice Body', 'type' => 'textarea'];
        }

        if (Schema::hasColumn('notices', 'body_mode')) {
            $fields[] = ['name' => 'body_mode', 'label' => 'Body Mode', 'type' => 'select', 'options' => ['builder', 'upload']];
        }

        return $fields;
    }

    private function validateData(Request $request): array
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
            ->filter(fn ($value, $key) => Schema::hasColumn('notices', $key))
            ->all();
    }

    private function resolveBodyMode(bool $hasUploadedDocument, ?string $bodyHtml, ?Notice $existingNotice = null, ?string $requestedMode = null): string
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
}
