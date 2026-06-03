<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\PolicyAudit;
use App\Models\GisRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PolicyController extends Controller
{

    private function latestGisWithLogo(): ?GisRecord
    {
        $approved = GisRecord::query()
            ->whereNotNull('logo_path')
            ->where('logo_path', '!=', '')
            ->where(function ($query) {
                $query->where('approval_status', 'Approved')
                    ->orWhere('workflow_status', 'Approved')
                    ->orWhere('workflow_status', 'Accepted');
            })
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->first();

        if ($approved) {
            return $approved;
        }

        return GisRecord::query()
            ->whereNotNull('logo_path')
            ->where('logo_path', '!=', '')
            ->latest('id')
            ->first();
    }

    private function gisLogoUrl(?GisRecord $gisRecord): string
    {
        $fallback = asset('images/jk-logo.png');

        if (!$gisRecord || empty($gisRecord->logo_path)) {
            return $fallback;
        }

        $path = ltrim($gisRecord->logo_path, '/');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    private function gisLogoDataUri(?GisRecord $gisRecord): ?string
    {
        $candidates = [];

        if ($gisRecord && !empty($gisRecord->logo_path)) {
            $path = ltrim($gisRecord->logo_path, '/');

            if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
                $normalizedPath = str_starts_with($path, 'storage/')
                    ? substr($path, strlen('storage/'))
                    : $path;

                $candidates[] = storage_path('app/public/' . $normalizedPath);
                $candidates[] = public_path($path);
                $candidates[] = public_path('storage/' . $normalizedPath);
            }
        }

        $candidates[] = public_path('images/jk-logo.png');
        $candidates[] = public_path('images/logo.png');

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));

                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };

                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
            }
        }

        return null;
    }



    private function logPolicyAudit(
        Policy $policy,
        string $action,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        PolicyAudit::create([
            'policy_id' => $policy->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private function policyAuditSnapshot(Policy $policy): array
    {
        return [
            'code' => $policy->code,
            'policy' => $policy->policy,
            'policy_subtitle' => $policy->policy_subtitle,
            'version' => $policy->version,
            'effectivity_date' => optional($policy->effectivity_date)->format('Y-m-d'),
            'prepared_by' => $policy->prepared_by,
            'reviewed_by' => $policy->reviewed_by,
            'approved_by' => $policy->approved_by,
            'review_cycle' => $policy->review_cycle,
            'classification' => $policy->classification,
            'approval_status' => $policy->approval_status,
            'workflow_status' => $policy->workflow_status,
            'is_archived' => (bool) $policy->is_archived,
            'review_note' => $policy->review_note,
        ];
    }


    public function index(Request $request)
    {
        $query = Policy::where('workflow_status', 'Accepted')
            ->where('is_archived', false);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('classification', 'like', "%{$search}%")
                    ->orWhere('policy', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('review_cycle', 'like', "%{$search}%");
            });
        }

        $policies = $query->latest()->paginate(10)->withQueryString();

        $latestGisRecord = $this->latestGisWithLogo();
        $policyLogoUrl = $this->gisLogoUrl($latestGisRecord);

        return view('policies.policies', compact('policies', 'latestGisRecord', 'policyLogoUrl'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'policy' => 'nullable|string|max:255',
            'policy_subtitle' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'effectivity_date' => 'nullable|date',
            'prepared_by' => 'nullable|string|max:255',
            'reviewed_by' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'review_cycle' => 'nullable|string|max:255',
            'classification' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120',
        ]);

        $policy = Policy::create([
            'code' => $validated['code'] ?? null,
            'policy' => $validated['policy'] ?? null,
            'policy_subtitle' => $validated['policy_subtitle'] ?? null,
            'version' => $validated['version'] ?? '1.0',
            'effectivity_date' => $validated['effectivity_date'] ?? null,
            'prepared_by' => $validated['prepared_by'] ?? (Auth::user()->name ?? 'System Admin'),
            'reviewed_by' => $validated['reviewed_by'] ?? null,
            'approved_by' => $validated['approved_by'] ?? null,
            'review_cycle' => $validated['review_cycle'] ?? null,
            'classification' => $validated['classification'] ?? 'Internal Use',
            'description' => $validated['description'] ?? null,
            'attachment' => null,
            'approval_status' => 'Pending',
            'workflow_status' => 'Submitted',
            'is_archived' => false,
            'archived_at' => null,
            'submitted_by' => Auth::id(),
        ]);

        if (empty($policy->code)) {
            $policy->code = 'POL-' . str_pad((string) $policy->id, 5, '0', STR_PAD_LEFT);
            $policy->save();
        }

        $this->storePolicyAttachments($request, $policy);
        $this->syncLegacyPolicyAttachment($policy);

        $this->logPolicyAudit(
            $policy,
            'submitted',
            'Policy created and submitted for admin review.',
            null,
            $this->policyAuditSnapshot($policy->fresh())
        );

        return redirect()
            ->route('policies.index')
            ->with('success', 'Policy submitted for admin review.');
    }




    private function inlinePolicyPdfIndentStyles(string $html): string
    {
        /*
         * DomPDF can be inconsistent with Quill's ql-indent-* classes.
         * Convert those classes into inline margin/padding styles before rendering.
         */
        return preg_replace_callback('/<([a-z0-9]+)\b([^>]*)class=("|\')([^"\']*\bql-indent-([1-8])\b[^"\']*)\3([^>]*)>/i', function ($matches) {
            $tag = $matches[1];
            $beforeClass = $matches[2];
            $quote = $matches[3];
            $classes = $matches[4];
            $level = (int) $matches[5];
            $afterClass = $matches[6];

            $indentEm = $level * 3;
            $attrs = $beforeClass . 'class=' . $quote . $classes . $quote . $afterClass;

            if (preg_match('/\sstyle=("|\')(.*?)\1/is', $attrs, $styleMatch)) {
                $existingStyle = rtrim($styleMatch[2], ';');
                $newStyle = $existingStyle . '; margin-left:' . $indentEm . 'em; padding-left:0; text-indent:0;';
                $attrs = preg_replace('/\sstyle=("|\')(.*?)\1/is', ' style="' . e($newStyle) . '"', $attrs, 1);
            } else {
                $attrs .= ' style="margin-left:' . $indentEm . 'em; padding-left:0; text-indent:0;"';
            }

            return '<' . $tag . $attrs . '>';
        }, $html);
    }


    private function normalizePolicyPdfTables(string $html): string
    {
        return preg_replace_callback('/<table\b[^>]*>.*?<\/table>/is', function ($matches) {
            $tableHtml = $matches[0];
            $rows = [];

            if (preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $tableHtml, $rowMatches)) {
                foreach ($rowMatches[1] as $rowHtml) {
                    $cells = [];

                    if (preg_match_all('/<(td|th)\b[^>]*>(.*?)<\/\1>/is', $rowHtml, $cellMatches, PREG_SET_ORDER)) {
                        foreach ($cellMatches as $cellMatch) {
                            $tag = strtolower($cellMatch[1]) === 'th' ? 'th' : 'td';
                            $content = $cellMatch[2];

                            $content = preg_replace('/<colgroup\b[^>]*>.*?<\/colgroup>/is', '', $content);
                            $content = preg_replace('/<col\b[^>]*\/?>/is', '', $content);
                            $content = preg_replace('/<span\b[^>]*(qlbt|table-better|quill-better-table|ql-table)[^>]*>.*?<\/span>/is', '', $content);
                            $content = preg_replace('/<div\b[^>]*(qlbt|table-better|quill-better-table|ql-table)[^>]*>.*?<\/div>/is', '', $content);

                            $content = preg_replace('/\sstyle=("|\')(.*?)\1/is', '', $content);
                            $content = str_replace(['&amp;nbsp;', '&nbsp;', "\u{00A0}"], ' ', $content);
                            $content = preg_replace('/[ \t]{2,}/u', ' ', $content);

                            if (trim(strip_tags($content)) === '') {
                                $content = '&nbsp;';
                            }

                            $cells[] = [
                                'tag' => $tag,
                                'content' => $content,
                            ];
                        }
                    }

                    if (!empty($cells)) {
                        $rows[] = $cells;
                    }
                }
            }

            if (empty($rows)) {
                return $tableHtml;
            }

            $maxColumns = max(array_map('count', $rows));
            $maxColumns = max(1, min($maxColumns, 12));
            $cellWidth = round(100 / $maxColumns, 4);

            $safeTable = '<table class="policy-pdf-table" style="width:100%;border-collapse:collapse;table-layout:fixed;">';

            foreach ($rows as $row) {
                $safeTable .= '<tr>';

                for ($i = 0; $i < $maxColumns; $i++) {
                    $cell = $row[$i] ?? ['tag' => 'td', 'content' => '&nbsp;'];
                    $tag = $cell['tag'];

                    $safeTable .= '<' . $tag . ' style="width:' . $cellWidth . '%;border:1px solid #000;padding:7px;vertical-align:top;text-align:left;">'
                        . $cell['content']
                        . '</' . $tag . '>';
                }

                $safeTable .= '</tr>';
            }

            $safeTable .= '</table>';

            return $safeTable;
        }, $html);
    }


    public function previewPdf(Request $request)
    {
        $policyFromRequest = null;

        if ($request->filled('policy_id')) {
            $policyFromRequest = Policy::find($request->input('policy_id'));
        }

        $getPdfValue = function (string $key, $default = null) use ($request, $policyFromRequest) {
            if ($request->filled($key)) {
                return $request->input($key);
            }

            if ($policyFromRequest && isset($policyFromRequest->{$key})) {
                return $policyFromRequest->{$key};
            }

            return $default;
        };

        $description = $request->filled('description')
            ? $request->input('description')
            : ($policyFromRequest?->description ?: '<p style="color:#cbd5e0;">No description provided.</p>');

        /*
         * PDF body/table cleanup.
         * Use Quill's real paragraph indentation (ql-indent-* classes).
         * Do not convert indentation into fake spaces or &nbsp;.
         */
        $description = str_replace(['&amp;nbsp;', '&nbsp;', "\u{00A0}"], ' ', $description);
        $description = preg_replace('/[ \t]{2,}/u', ' ', $description);

        // Remove Quill/table-better column sizing so PDF tables use our stable CSS.
        $description = preg_replace('/<colgroup\b[^>]*>.*?<\/colgroup>/is', '', $description);
        $description = preg_replace('/<col\b[^>]*\/?>/is', '', $description);

        $description = $this->normalizePolicyPdfTables($description);
        $description = $this->inlinePolicyPdfIndentStyles($description);


        $safePdfText = function ($value, int $chunk = 34): string {
            $value = trim((string) ($value ?? ''));

            if ($value === '') {
                return '';
            }

            return $value;
        };

        /*
         * Do not split body text manually.
         * Manual chunk_split changes wrapping and can create small fragments on separate lines.
         * Let DomPDF and CSS handle wrapping instead.
         */

        $latestGisRecord = $this->latestGisWithLogo();

        $rawEffectivityDate = $getPdfValue('effectivity_date', '');

        try {
            $formattedEffectivityDate = !empty($rawEffectivityDate)
                ? Carbon::parse($rawEffectivityDate)->format('F d, Y')
                : '';
        } catch (\Throwable $e) {
            $formattedEffectivityDate = (string) $rawEffectivityDate;
        }

        $data = [
            'logo_src' => $this->gisLogoDataUri($latestGisRecord),
            'code' => $safePdfText($getPdfValue('code', 'AUTO-GENERATED'), 30),
            'policy' => $safePdfText($getPdfValue('policy', ''), 32),
            'policy_subtitle' => $safePdfText($getPdfValue('policy_subtitle', ''), 42),
            'version' => $safePdfText($getPdfValue('version', '1.0'), 30),
            'effectivity_date' => $formattedEffectivityDate,
            'prepared_by' => $safePdfText($getPdfValue('prepared_by', auth()->user()->name ?? 'System Admin'), 30),
            'reviewed_by' => $safePdfText($getPdfValue('reviewed_by', ''), 30),
            'approved_by' => $safePdfText($getPdfValue('approved_by', ''), 30),
            'review_cycle' => $safePdfText($getPdfValue('review_cycle', ''), 30),
            'classification' => $safePdfText($getPdfValue('classification', 'Internal Use'), 30),
            'description' => $description,
        ];

        $pdf = Pdf::loadView('policies.pdf_preview', compact('data'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Georgia',
                'isPhpEnabled' => true,
                'debugLayout' => false,
            ]);

        $filename = ($getPdfValue('code', null) ?: 'policy') . '.pdf';

        /*
         * Render first, then add page numbers on the final DomPDF canvas.
         * This avoids Page 1 of 0 / Page 1 of 1 problems.
         */
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();

        $font = $fontMetrics->getFont('Georgia', 'normal')
            ?: $fontMetrics->getFont('Times-Roman', 'normal');

        $fontSize = 10;
        $pageCount = method_exists($canvas, 'get_page_count')
            ? $canvas->get_page_count()
            : 1;

        $pageWidth = method_exists($canvas, 'get_width')
            ? $canvas->get_width()
            : 595.28;

        $pageHeight = method_exists($canvas, 'get_height')
            ? $canvas->get_height()
            : 841.89;

        $sampleText = 'Page ' . $pageCount . ' of ' . $pageCount;

        $textWidth = method_exists($fontMetrics, 'getTextWidth')
            ? $fontMetrics->getTextWidth($sampleText, $font, $fontSize)
            : $fontMetrics->get_text_width($sampleText, $font, $fontSize);

        $x = ($pageWidth - $textWidth) / 2;
        $y = $pageHeight - 42;

        $canvas->page_text(
            $x,
            $y,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            $fontSize,
            [0, 0, 0]
        );

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function submitted(Request $request)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $query = Policy::with('attachments');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('policy', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('prepared_by', 'like', "%{$search}%")
                    ->orWhere('classification', 'like', "%{$search}%")
                    ->orWhere('review_cycle', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'Archived') {
                $query->where('is_archived', true);
            } else {
                $query->where('workflow_status', $request->status);
            }
        }

        $policies = $query->latest()->paginate(10)->withQueryString();

        $latestGisRecord = $this->latestGisWithLogo();
        $policyLogoUrl = $this->gisLogoUrl($latestGisRecord);

        return view('admin.policies-dashboard', compact('policies', 'latestGisRecord', 'policyLogoUrl'));
    }

    public function review($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        /*
         * Do not overwrite reviewed_by here.
         * reviewed_by is a manual document field entered during Add/Edit Policy.
         * The logged-in reviewer is only the system user performing the action,
         * not necessarily the person whose name should appear on the policy document.
         */
        $policy->touch();

        $this->logPolicyAudit(
            $policy,
            'reviewed',
            'Policy review action recorded by admin.',
            null,
            $this->policyAuditSnapshot($policy->fresh())
        );

        return redirect()
            ->route('admin.policies.show', $policy->id)
            ->with('success', 'Policy review action recorded. Reviewed By field was preserved.');
    }

    public function approve($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $policy->update([
            /*
             * Do not overwrite reviewed_by / approved_by.
             * These are document names manually typed in Add/Edit Policy.
             * The actual system user who approved is still tracked through
             * approved_by_user_id and approved_at for audit purposes.
             */
            'approval_status' => 'Approved',
            'workflow_status' => 'Accepted',
            'approved_by_user_id' => Auth::id(),
            'approved_at' => now(),
            'review_note' => null,
            'is_archived' => false,
            'archived_at' => null,
        ]);

        $this->logPolicyAudit(
            $policy,
            'approved',
            'Policy approved and accepted.',
            null,
            $this->policyAuditSnapshot($policy->fresh())
        );

        return redirect()->back()->with('success', 'Policy approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $policy->update([
            /*
             * Preserve reviewed_by / approved_by document fields.
             * The user who performed this action is tracked through
             * approved_by_user_id and approved_at.
             */
            'approval_status' => 'Rejected',
            'workflow_status' => 'Reverted',
            'approved_by_user_id' => Auth::id(),
            'approved_at' => now(),
            'review_note' => $request->input('review_note'),
            'is_archived' => false,
            'archived_at' => null,
        ]);

        $this->logPolicyAudit(
            $policy,
            'rejected',
            'Policy rejected by admin.',
            null,
            $this->policyAuditSnapshot($policy->fresh())
        );

        return redirect()->back()->with('success', 'Policy rejected successfully.');
    }

    public function revise(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $policy->update([
            /*
             * Preserve reviewed_by / approved_by document fields.
             * The user who performed this action is tracked through
             * approved_by_user_id and approved_at.
             */
            'approval_status' => 'Needs Revision',
            'workflow_status' => 'Reverted',
            'approved_by_user_id' => Auth::id(),
            'approved_at' => now(),
            'review_note' => $request->input('review_note'),
            'is_archived' => false,
            'archived_at' => null,
        ]);

        $this->logPolicyAudit(
            $policy,
            'revision_requested',
            'Policy marked as needing revision.',
            null,
            $this->policyAuditSnapshot($policy->fresh())
        );

        return redirect()->back()->with('success', 'Policy marked for revision.');
    }

    public function archive($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $policy->update([
            'workflow_status' => 'Archived',
            'is_archived' => true,
            'archived_at' => Carbon::now(),
        ]);

        $this->logPolicyAudit(
            $policy,
            'archived',
            'Policy archived.',
            null,
            $this->policyAuditSnapshot($policy->fresh())
        );

        return redirect()->back()->with('success', 'Policy archived successfully.');
    }

    public function unarchive($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $restoreStatus = $policy->approval_status === 'Approved' ? 'Accepted' : 'Reverted';

        $policy->update([
            'workflow_status' => $restoreStatus,
            'is_archived' => false,
            'archived_at' => null,
        ]);

        $this->logPolicyAudit(
            $policy,
            'unarchived',
            'Policy unarchived.',
            null,
            $this->policyAuditSnapshot($policy->fresh())
        );

        return redirect()->back()->with('success', 'Policy unarchived successfully.');
    }

    public function showAdmin($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $latestGisRecord = $this->latestGisWithLogo();
        $policyLogoUrl = $this->gisLogoUrl($latestGisRecord);

        $policyAudits = PolicyAudit::with('user')
            ->where('policy_id', $policy->id)
            ->latest()
            ->get();

        return view('admin.policy-show', compact('policy', 'latestGisRecord', 'policyLogoUrl', 'policyAudits'));
    }

    public function show(Request $request, $id)
    {
        $policy = Policy::with('attachments')->where('is_archived', false)->findOrFail($id);
        $search = trim($request->input('search', ''));

        $latestGisRecord = $this->latestGisWithLogo();
        $policyLogoUrl = $this->gisLogoUrl($latestGisRecord);

        return view('policies.show', compact('policy', 'search', 'latestGisRecord', 'policyLogoUrl'));
    }


    private function storePolicyAttachments(Request $request, Policy $policy): void
    {
        $files = [];

        if ($request->hasFile('attachments')) {
            $uploadedFiles = $request->file('attachments');

            if (is_array($uploadedFiles)) {
                $files = array_merge($files, $uploadedFiles);
            }
        }

        // Backward compatibility for older single attachment input.
        if ($request->hasFile('attachment')) {
            $files[] = $request->file('attachment');
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $path = $file->store('policy_attachments', 'public');

            $policy->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);

            // Keep old attachment column populated for backward compatibility.
            if (empty($policy->attachment)) {
                $policy->update(['attachment' => $path]);
            }
        }
    }

    private function removeSelectedPolicyAttachments(Request $request, Policy $policy): void
    {
        $attachmentIds = collect($request->input('remove_attachment_ids', []))
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values();

        if ($attachmentIds->isEmpty()) {
            return;
        }

        $attachments = $policy->attachments()
            ->whereIn('id', $attachmentIds)
            ->get();

        foreach ($attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }

        $firstRemaining = $policy->attachments()->oldest()->first();

        $policy->update([
            'attachment' => $firstRemaining?->file_path,
        ]);
    }

    private function syncLegacyPolicyAttachment(Policy $policy): void
    {
        if (!$policy->attachment && $policy->attachments()->exists()) {
            $policy->update([
                'attachment' => $policy->attachments()->oldest()->value('file_path'),
            ]);
        }
    }


    public function edit($id)
    {
        $policy = Policy::with('attachments')->findOrFail($id);

        $latestGisRecord = $this->latestGisWithLogo();
        $policyLogoUrl = $this->gisLogoUrl($latestGisRecord);

        return view('policies.edit', compact('policy', 'latestGisRecord', 'policyLogoUrl'));
    }


    public function update(Request $request, $id)
    {
        $policy = Policy::with('attachments')->findOrFail($id);

        $oldSnapshot = $this->policyAuditSnapshot($policy);

        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'policy' => 'nullable|string|max:255',
            'policy_subtitle' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'effectivity_date' => 'nullable|date',
            'prepared_by' => 'nullable|string|max:255',
            'reviewed_by' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'review_cycle' => 'nullable|string|max:255',
            'classification' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120',
            'remove_attachment' => 'nullable|boolean',
            'remove_attachment_ids' => 'nullable|array',
            'remove_attachment_ids.*' => 'integer',
            'redirect_to' => 'nullable|string|max:50',
        ]);

        $payload = [
            'code' => $validated['code'] ?? null,
            'policy' => $validated['policy'] ?? null,
            'policy_subtitle' => $validated['policy_subtitle'] ?? null,
            'version' => $validated['version'] ?? '1.0',
            'effectivity_date' => $validated['effectivity_date'] ?? null,
            'prepared_by' => $validated['prepared_by'] ?? (Auth::user()->name ?? 'System Admin'),
            'reviewed_by' => $validated['reviewed_by'] ?? null,
            'approved_by' => $validated['approved_by'] ?? null,
            'review_cycle' => $validated['review_cycle'] ?? null,
            'classification' => $validated['classification'] ?? 'Internal Use Only',
            'description' => $validated['description'] ?? null,
        ];

        if ($request->boolean('remove_attachment') && $policy->attachment) {
            Storage::disk('public')->delete($policy->attachment);
            $payload['attachment'] = null;
        }

        $policy->update($payload);

        $this->removeSelectedPolicyAttachments($request, $policy);
        $this->storePolicyAttachments($request, $policy);
        $this->syncLegacyPolicyAttachment($policy);

        $this->logPolicyAudit(
            $policy,
            'updated',
            'Policy details updated.',
            $oldSnapshot,
            $this->policyAuditSnapshot($policy->fresh())
        );

        $redirect = $request->input('redirect_to') === 'admin'
            ? route('admin.policies.show', $policy->id)
            : route('policies.show', $policy->id);

        return redirect($redirect)->with('success', 'Policy updated successfully.');
    }
}
