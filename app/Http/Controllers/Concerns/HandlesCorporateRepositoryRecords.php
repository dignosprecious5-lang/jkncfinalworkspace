<?php

namespace App\Http\Controllers\Concerns;

use App\Models\GisRecord;
use App\Models\User;
use App\Notifications\CorporateApprovalSubmissionNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HandlesCorporateRepositoryRecords
{
    protected function latestCorporateCompany(): array
    {
        $gis = $this->latestApprovedCorporateGis();

        return [
            'company_id' => $gis?->company_id,
            'company_name' => $gis?->corporation_name ?: 'Latest Approved GIS Company',
            'registration_number' => $gis?->company_reg_no,
            'principal_address' => $gis?->principal_address ?: $gis?->business_address,
            'company_address' => $gis?->principal_address ?: $gis?->business_address,
            'gis_id' => $gis?->id,
        ];
    }

    protected function latestApprovedCorporateGis(): ?GisRecord
    {
        if (! Schema::hasTable('gis_records')) {
            return null;
        }

        return GisRecord::query()
            ->when(Schema::hasColumn('gis_records', 'company_id'), function ($query) {
                $query->where(function ($nested) {
                    $nested->whereNull('company_id')
                        ->orWhere('company_id', 0);
                });
            })
            ->when(
                Schema::hasColumn('gis_records', 'approval_status') || Schema::hasColumn('gis_records', 'workflow_status'),
                function ($query) {
                    $query->where(function ($nested) {
                        if (Schema::hasColumn('gis_records', 'approval_status')) {
                            $nested->where('approval_status', 'Approved');
                        }

                        if (Schema::hasColumn('gis_records', 'workflow_status')) {
                            $nested->orWhere('workflow_status', 'Accepted');
                        }
                    });
                }
            )
            ->latest('id')
            ->first();
    }

    protected function currentUserLabel(Request $request): string
    {
        $user = $request->user();

        return trim((string) (
            $user?->name
            ?? $user?->full_name
            ?? $user?->email
            ?? 'System User'
        ));
    }

    protected function resolveOtherChoice(?string $selected, ?string $other): string
    {
        $value = trim((string) $selected);

        if (strcasecmp($value, 'Other') === 0 && filled($other)) {
            return trim((string) $other);
        }

        return $value;
    }

    protected function storeDocumentSet(Request $request, string $input, string $directory): array
    {
        if (! $request->hasFile($input)) {
            return [];
        }

        $files = is_array($request->file($input)) ? $request->file($input) : [$request->file($input)];

        return collect($files)
            ->filter()
            ->map(function ($file) use ($directory) {
                $path = $file->store($directory, 'public');

                return [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => $this->publicDocumentUrl($path),
                    'uploaded_by' => Auth::user()?->name ?? Auth::user()?->email ?? 'System User',
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            })
            ->values()
            ->all();
    }

    protected function appendDocuments(?array $existing, array $incoming): array
    {
        return array_values(array_merge($existing ?? [], $incoming));
    }

    protected function documentLinks(?array $documents): array
    {
        return collect($documents ?? [])
            ->filter(fn ($document) => is_array($document) && filled($document['path'] ?? null))
            ->map(function (array $document) {
                $document['url'] = $this->publicDocumentUrl($document['path'] ?? null);

                return $document;
            })
            ->values()
            ->all();
    }

    protected function publicDocumentUrl(?string $path): ?string
    {
        $path = $this->normalizePublicPath($path);

        return $path ? route('uploads.show', ['path' => $path]) : null;
    }

    protected function normalizePublicPath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = ltrim((string) $path, '/');
        $path = preg_replace('#^public/#', '', $path);
        $path = preg_replace('#^storage/#', '', $path);

        return $path;
    }

    protected function repositoryStatusFromDeadline(?string $date, string $fallback = 'Active'): string
    {
        if (! $date) {
            return $fallback;
        }

        $days = now()->startOfDay()->diffInDays(Carbon::parse($date)->startOfDay(), false);

        return match (true) {
            $days < 0 => 'Expired',
            $days <= 30 => 'Expiring Soon',
            $days <= 90 => 'For Renewal',
            default => 'Active',
        };
    }

    protected function commonDocumentValidation(): array
    {
        return [
            'draft_documents' => ['nullable'],
            'draft_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'approved_documents' => ['nullable'],
            'approved_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ];
    }

    protected function notifyCorporateApproversOfSubmission($record, string $moduleKey): void
    {
        $submitter = Auth::user();
        $submitterName = trim((string) ($submitter?->name ?: $submitter?->email ?: 'A user'));
        $moduleName = $this->corporateApprovalModuleName($moduleKey);
        $recordTitle = $this->corporateApprovalRecordTitle($record, $moduleKey);
        $message = "A new {$moduleName} record has been submitted by {$submitterName} and is waiting for approval.";

        if ($recordTitle !== '') {
            $message .= " Record: {$recordTitle}.";
        }

        $recipients = User::with('userPermission')
            ->get()
            ->filter(function (User $user) use ($submitter) {
                if ($submitter && (int) $user->id === (int) $submitter->id) {
                    return false;
                }

                if (method_exists($user, 'isDisabled') && $user->isDisabled()) {
                    return false;
                }

                $role = trim((string) ($user->role ?? ''));

                return $user->hasPermission('approve_corporate')
                    || in_array(Str::lower($role), ['admin', 'corporate admin'], true);
            })
            ->values();

        if ($recipients->isEmpty()) {
            Log::warning('Corporate approval submission notification skipped: no approvers found.', [
                'module' => $moduleKey,
                'record_id' => $record->id ?? null,
            ]);

            return;
        }

        Notification::send($recipients, new CorporateApprovalSubmissionNotification(
            "{$moduleName} submitted for approval",
            $message,
            route('admin.corporate.dashboard'),
            $moduleName,
            $recordTitle,
            $submitterName
        ));
    }

    protected function corporateApprovalModuleName(string $moduleKey): string
    {
        return match ($moduleKey) {
            'bir-tax' => 'BIR & Tax',
            'natgov' => 'National Government',
            'lgu' => 'LGU',
            'accounting' => 'Accounting',
            'banking' => 'Banking',
            'legal' => 'Legal',
            'operations' => 'Operations',
            default => 'Corporate',
        };
    }

    protected function corporateApprovalRecordTitle($record, string $moduleKey): string
    {
        return trim((string) match ($moduleKey) {
            'bir-tax' => ($record->company_name ?? $record->tax_payer ?? 'Company') . ' - ' . ($record->form_type ?? 'BIR Filing'),
            'natgov' => ($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->agency ?? 'National Government Record'),
            'lgu' => ($record->company_name ?? 'Company') . ' - ' . ($record->permit_type ?? 'LGU Permit'),
            'accounting' => ($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->statement_type ?? 'Accounting Report'),
            'banking' => ($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->bank ?? 'Banking Record'),
            'legal' => ($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->document_title ?? $record->document_type ?? $record->legal_type ?? 'Legal Record'),
            'operations' => ($record->company_name ?? $record->client ?? 'Company') . ' - ' . ($record->document_title ?? $record->operation_type ?? 'Operations Record'),
            default => $record->company_name ?? $record->document_name ?? '',
        }, " \t\n\r\0\x0B-");
    }
}
