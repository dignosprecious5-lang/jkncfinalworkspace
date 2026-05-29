<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesCorporateDocumentNumbers;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Mail\NoticeOfMeetingMail;
use App\Models\GisRecord;
use App\Models\Notice;
use App\Models\NoticeAttendee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
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
        $notices = Notice::whereNull('company_id')
            ->with(['minutes', 'resolutions', 'secretaryCertificates', 'attendees'])
            ->latest()
            ->get();

        return view('corporate.notices.index', [
            'notices' => $notices,
            'nextNoticeNumber' => $this->nextGlobalNoticeNumber(),
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
                'company_id' => null,
                'notice_number' => $this->nextGlobalNoticeNumber(),
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

        $data['company_id'] = null;
        $data['document_path'] = $this->handleUpload($request, 'document_path');
        $data['notice_number'] = $data['notice_number'] ?: $this->nextGlobalNoticeNumber();
        $data['body_mode'] = $this->resolveBodyMode($hasUploadedDocument, $bodyHtml, null, $data['body_mode'] ?? null);
        $data = $this->filterPersistableData($data);

        $notice = Notice::create($data);
        $this->syncGeneratedNoticePdf($notice, $bodyHtml, $hasUploadedDocument);
        $this->syncNoticeAttendeesFromLatestGis($notice);

        return redirect()->route('notices')->with('success', 'Notice created.');
    }

    public function show(Notice $notice)
    {
        abort_if($notice->company_id !== null, 404);

        $this->syncNoticeAttendeesFromLatestGis($notice->fresh());
        $notice->load(['minutes', 'resolutions', 'secretaryCertificates', 'attendees']);

        return view('corporate.notices.preview', [
            'notice' => $notice,
        ]);
    }

    public function edit(Notice $notice)
    {
        abort_if($notice->company_id !== null, 404);

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
        abort_if($notice->company_id !== null, 404);

        $data = $this->validateData($request);
        $bodyHtml = $data['body_html'] ?? null;
        $hasUploadedDocument = $request->hasFile('document_path');

        $data['company_id'] = null;
        $data['document_path'] = $this->handleUpload($request, 'document_path', $notice->document_path);
        $data['body_mode'] = $this->resolveBodyMode($hasUploadedDocument, $bodyHtml, $notice, $data['body_mode'] ?? null);
        $data = $this->filterPersistableData($data);

        $notice->update($data);
        $this->syncGeneratedNoticePdf($notice->fresh(), $bodyHtml, $hasUploadedDocument);
        $this->syncNoticeAttendeesFromLatestGis($notice->fresh());

        return redirect()->route('notices')->with('success', 'Notice updated.');
    }

    public function destroy(Notice $notice)
    {
        abort_if($notice->company_id !== null, 404);

        $notice->delete();

        return redirect()->route('notices')->with('success', 'Notice deleted.');
    }


    public function downloadPdf(Notice $notice)
    {
        abort_if($notice->company_id !== null, 404);

        $this->syncNoticeAttendeesFromLatestGis($notice->fresh());
        $notice = $notice->fresh(['attendees']);

        $filename = 'notice-' . Str::slug($notice->notice_number ?: 'meeting') . '.pdf';

        return response($this->noticePdfBinary($notice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }


    public function sendNotice(Request $request, Notice $notice)
    {
        abort_if($notice->company_id !== null, 404);
        $notice->load('attendees');
        $attendees = $notice->attendees()->whereIn('id', $request->input('attendee_ids', []))->whereNotNull('email')->get();
        if ($attendees->isEmpty()) {
            return back()->with('error', 'No selected attendees with valid email addresses.');
        }
        $pdfBinary = $this->noticePdfBinary($notice->fresh(['attendees']));
        $filename = 'notice-' . Str::slug($notice->notice_number ?: 'meeting') . '.pdf';
        foreach ($attendees as $attendee) {
            Mail::to($attendee->email)->send(new NoticeOfMeetingMail($notice, $attendee, $pdfBinary, $filename));
            $attendee->update(['sent_at' => now(), 'is_selected' => true]);
        }
        return back()->with('success', 'Notice sent to ' . $attendees->count() . ' attendee(s).');
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

    private function nextGlobalNoticeNumber(): string
    {
        $year = now()->year;

        $lastNotice = Notice::whereNull('company_id')
            ->where('notice_number', 'like', $year . '-%')
            ->orderByDesc('notice_number')
            ->first();

        if (!$lastNotice || !$lastNotice->notice_number) {
            return $year . '-001';
        }

        $lastNumber = (int) substr($lastNotice->notice_number, -3);

        return $year . '-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
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

    private function syncNoticeAttendeesFromLatestGis(Notice $notice): void
    {
        /*
            Corporate Notices usually have company_id = null.
            Do not simply use the latest GIS record because the latest GIS may have no
            directors/stockholders encoded yet. Instead, use the latest Accepted/Approved GIS
            that actually contains the needed attendee type with email addresses.
        */

        $governingBody = strtolower((string) $notice->governing_body);

        $needsDirectors = str_contains($governingBody, 'board')
            || str_contains($governingBody, 'director')
            || str_contains($governingBody, 'joint');

        $needsStockholders = str_contains($governingBody, 'stockholder')
            || str_contains($governingBody, 'joint');

        // If governing body is blank/unknown, default to directors so the preview is not empty.
        if (!$needsDirectors && !$needsStockholders) {
            $needsDirectors = true;
        }

        $latestGis = $this->latestGisWithExpectedAttendees(
            notice: $notice,
            needsDirectors: $needsDirectors,
            needsStockholders: $needsStockholders
        );

        if (!$latestGis) {
            return;
        }

        $rows = collect();

        if ($needsDirectors) {
            foreach ($latestGis->directors as $director) {
                if (blank($director->officer_name)) {
                    continue;
                }

                $rows->push([
                    'name' => $director->officer_name,
                    'position' => $director->officer_type ?: $director->board,
                    'email' => $director->email,
                    'source_type' => 'director_officer',
                    'source_id' => $director->id,
                ]);
            }
        }

        if ($needsStockholders) {
            foreach ($latestGis->stockholders as $stockholder) {
                if (blank($stockholder->stockholder_name)) {
                    continue;
                }

                $rows->push([
                    'name' => $stockholder->stockholder_name,
                    'position' => 'Stockholder',
                    'email' => $stockholder->email,
                    'source_type' => 'stockholder',
                    'source_id' => $stockholder->id,
                ]);
            }
        }

        $rows
            ->unique(fn ($row) => strtolower(trim($row['source_type'] . ':' . $row['source_id'] . ':' . $row['name'])))
            ->values()
            ->each(function (array $row, int $index) use ($notice) {
                NoticeAttendee::updateOrCreate(
                    [
                        'notice_id' => $notice->id,
                        'source_type' => $row['source_type'],
                        'source_id' => $row['source_id'],
                    ],
                    [
                        'name' => $row['name'],
                        'position' => $row['position'],
                        'email' => $row['email'],
                        'is_selected' => filled($row['email']),
                        'sort_order' => $index + 1,
                    ]
                );
            });
    }

    private function latestGisWithExpectedAttendees(Notice $notice, bool $needsDirectors, bool $needsStockholders): ?GisRecord
    {
        $baseQuery = GisRecord::query()
            ->with(['directors', 'stockholders'])
            ->when($notice->company_id, fn ($query) => $query->where('company_id', $notice->company_id))
            ->where(function ($query) {
                $query->where('workflow_status', 'Accepted')
                    ->orWhere('approval_status', 'Approved');
            });

        /*
            Prefer a GIS that has the needed attendee email type.
            This fixes global Corporate Notices where company_id is null and the latest GIS
            may not have encoded Directors/Officers or Stockholders yet.
        */
        if ($needsDirectors && !$needsStockholders) {
            $gis = (clone $baseQuery)
                ->whereHas('directors', fn ($query) => $query->whereNotNull('email')->where('email', '<>', ''))
                ->latest('id')
                ->first();

            if ($gis) {
                return $gis;
            }
        }

        if ($needsStockholders && !$needsDirectors) {
            $gis = (clone $baseQuery)
                ->whereHas('stockholders', fn ($query) => $query->whereNotNull('email')->where('email', '<>', ''))
                ->latest('id')
                ->first();

            if ($gis) {
                return $gis;
            }
        }

        if ($needsDirectors && $needsStockholders) {
            $gis = (clone $baseQuery)
                ->where(function ($query) {
                    $query->whereHas('directors', fn ($subQuery) => $subQuery->whereNotNull('email')->where('email', '<>', ''))
                        ->orWhereHas('stockholders', fn ($subQuery) => $subQuery->whereNotNull('email')->where('email', '<>', ''));
                })
                ->latest('id')
                ->first();

            if ($gis) {
                return $gis;
            }
        }

        // Last fallback: latest accepted/approved GIS even if no emails are encoded.
        return $baseQuery->latest('id')->first();
    }

    private function noticePdfBinary(Notice $notice): string
    {
        $notice->loadMissing('attendees');
        return Pdf::loadView('corporate.notices.pdf', ['notice'=>$notice,'bodyHtml'=>$notice->body_html])->setPaper('a4')->output();
    }
}
