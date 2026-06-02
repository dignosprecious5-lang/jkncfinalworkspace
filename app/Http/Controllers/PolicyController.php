<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PolicyController extends Controller
{
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

        return view('policies.policies', compact('policies'));
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
            'policy_subtitle' => $validated['policy_subtitle'] ?? 'Policy Document',
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

        return redirect()
            ->route('policies.index')
            ->with('success', 'Policy submitted for admin review.');
    }

    public function previewPdf(Request $request)
    {
        $description = $request->input(
            'description',
            '<p style="color:#cbd5e0;">No description provided.</p>'
        );

        $description = preg_replace('/<colgroup\b[^>]*>.*?<\/colgroup>/is', '', $description);
        $description = preg_replace('/<col\b[^>]*\/?>/is', '', $description);

        $description = preg_replace_callback(
            '/<(table|thead|tbody|tfoot|tr|td|th)\b([^>]*)>/is',
            function ($matches) {
                $tag = $matches[1];
                $attrs = $matches[2];

                $attrs = preg_replace('/\sstyle=("|\')(.*?)\1/is', '', $attrs);
                $attrs = preg_replace('/\s(width|height)=("|\')(.*?)\2/is', '', $attrs);

                return '<' . $tag . $attrs . '>';
            },
            $description
        );


        $safePdfText = function ($value, int $chunk = 34): string {
            $value = trim((string) ($value ?? ''));

            if ($value === '') {
                return '';
            }

            return preg_replace_callback('/[^\s]{' . $chunk . ',}/u', function ($matches) use ($chunk) {
                return trim(chunk_split($matches[0], $chunk, ' '));
            }, $value);
        };

        $description = preg_replace_callback('/>([^<]+)</u', function ($matches) {
            $text = preg_replace_callback('/[^\s]{35,}/u', function ($longWord) {
                return trim(chunk_split($longWord[0], 35, ' '));
            }, $matches[1]);

            return '>' . $text . '<';
        }, $description);

        $data = [
            'code' => $safePdfText($request->input('code', 'AUTO-GENERATED'), 30),
            'policy' => $safePdfText($request->input('policy', ''), 32),
            'policy_subtitle' => $safePdfText($request->input('policy_subtitle', 'Policy Document'), 42),
            'version' => $safePdfText($request->input('version', '1.0'), 30),
            'effectivity_date' => $request->input('effectivity_date', ''),
            'prepared_by' => $safePdfText($request->input('prepared_by', auth()->user()->name ?? 'System Admin'), 30),
            'reviewed_by' => $safePdfText($request->input('reviewed_by', ''), 30),
            'approved_by' => $safePdfText($request->input('approved_by', ''), 30),
            'review_cycle' => $safePdfText($request->input('review_cycle', ''), 30),
            'classification' => $safePdfText($request->input('classification', 'Internal Use'), 30),
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

        $filename = ($request->input('code') ?: 'policy') . '.pdf';

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

        return view('admin.policies-dashboard', compact('policies'));
    }

    public function review($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $policy->update([
            'reviewed_by' => Auth::user()->name,
        ]);

        return redirect()
            ->route('admin.policies.show', $policy->id)
            ->with('success', 'Policy reviewed by ' . Auth::user()->name . '.');
    }

    public function approve($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $policy->update([
            'reviewed_by' => $policy->reviewed_by ?: Auth::user()->name,
            'approval_status' => 'Approved',
            'workflow_status' => 'Accepted',
            'approved_by_user_id' => Auth::id(),
            'approved_by' => Auth::user()->name,
            'approved_at' => now(),
            'review_note' => null,
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return redirect()->back()->with('success', 'Policy approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $policy->update([
            'reviewed_by' => $policy->reviewed_by ?: Auth::user()->name,
            'approval_status' => 'Rejected',
            'workflow_status' => 'Reverted',
            'approved_by_user_id' => Auth::id(),
            'approved_by' => Auth::user()->name,
            'approved_at' => now(),
            'review_note' => $request->input('review_note'),
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return redirect()->back()->with('success', 'Policy rejected successfully.');
    }

    public function revise(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        $policy->update([
            'reviewed_by' => $policy->reviewed_by ?: Auth::user()->name,
            'approval_status' => 'Needs Revision',
            'workflow_status' => 'Reverted',
            'approved_by_user_id' => Auth::id(),
            'approved_by' => Auth::user()->name,
            'approved_at' => now(),
            'review_note' => $request->input('review_note'),
            'is_archived' => false,
            'archived_at' => null,
        ]);

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

        return redirect()->back()->with('success', 'Policy unarchived successfully.');
    }

    public function showAdmin($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::with('attachments')->findOrFail($id);

        return view('admin.policy-show', compact('policy'));
    }

    public function show(Request $request, $id)
    {
        $policy = Policy::with('attachments')->where('is_archived', false)->findOrFail($id);
        $search = trim($request->input('search', ''));

        return view('policies.show', compact('policy', 'search'));
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

        return view('policies.edit', compact('policy'));
    }


    public function update(Request $request, $id)
    {
        $policy = Policy::with('attachments')->findOrFail($id);

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
            'policy_subtitle' => $validated['policy_subtitle'] ?? 'Policy Document',
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

        $redirect = $request->input('redirect_to') === 'admin'
            ? route('admin.policies.show', $policy->id)
            : route('policies.show', $policy->id);

        return redirect($redirect)->with('success', 'Policy updated successfully.');
    }
}
