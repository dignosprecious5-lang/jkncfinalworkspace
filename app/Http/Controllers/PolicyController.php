<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'version' => 'nullable|string|max:50',
            'effectivity_date' => 'nullable|date',
            'prepared_by' => 'nullable|string|max:255',
            'reviewed_by' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'review_cycle' => 'nullable|string|max:255',
            'classification' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120',
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            if (!$file->isValid()) {
                return back()
                    ->withErrors(['attachment' => 'The attachment failed to upload.'])
                    ->withInput();
            }

            $validated['attachment'] = $file->store('policy_attachments', 'public');
        }

        $policy = Policy::create([
            'code' => $validated['code'] ?? null,
            'policy' => $validated['policy'] ?? null,
            'version' => $validated['version'] ?? '1.0',
            'effectivity_date' => $validated['effectivity_date'] ?? null,
            'prepared_by' => $validated['prepared_by'] ?? (Auth::user()->name ?? 'System Admin'),
            'reviewed_by' => $validated['reviewed_by'] ?? null,
            'approved_by' => $validated['approved_by'] ?? null,
            'review_cycle' => $validated['review_cycle'] ?? null,
            'classification' => $validated['classification'] ?? 'Internal Use',
            'description' => $validated['description'] ?? null,
            'attachment' => $validated['attachment'] ?? null,
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

        $data = [
            'code' => $request->input('code', 'AUTO-GENERATED'),
            'policy' => $request->input('policy', ''),
            'version' => $request->input('version', '1.0'),
            'effectivity_date' => $request->input('effectivity_date', ''),
            'prepared_by' => $request->input('prepared_by', auth()->user()->name ?? 'System Admin'),
            'reviewed_by' => $request->input('reviewed_by', ''),
            'approved_by' => $request->input('approved_by', ''),
            'review_cycle' => $request->input('review_cycle', ''),
            'classification' => $request->input('classification', 'Internal Use'),
            'description' => $description,
        ];

        $pdf = Pdf::loadView('policies.pdf_preview', compact('data'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'debugLayout' => false,
            ]);

        $filename = ($request->input('code') ?: 'policy') . '.pdf';

        return $pdf->download($filename);
    }

    public function submitted(Request $request)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $query = Policy::query();

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

    public function approve($id)
    {
        if (!Auth::user()->hasPermission('approve_policies')) {
            abort(403, 'Unauthorized');
        }

        $policy = Policy::findOrFail($id);

        $policy->update([
            'approval_status' => 'Approved',
            'workflow_status' => 'Accepted',
            'approved_by_user_id' => Auth::id(),
            'approved_by' => Auth::user()->name,
            'approved_at' => now(),
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

        $policy = Policy::findOrFail($id);

        $policy->update([
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

        $policy = Policy::findOrFail($id);

        $policy->update([
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

        $policy = Policy::findOrFail($id);

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

        $policy = Policy::findOrFail($id);

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

        $policy = Policy::findOrFail($id);

        return view('admin.policy-show', compact('policy'));
    }

    public function show(Request $request, $id)
    {
        $policy = Policy::where('is_archived', false)->findOrFail($id);
        $search = trim($request->input('search', ''));

        return view('policies.show', compact('policy', 'search'));
    }

    public function edit($id)
    {
        $policy = Policy::findOrFail($id);

        return view('policies.edit', compact('policy'));
    }
}
