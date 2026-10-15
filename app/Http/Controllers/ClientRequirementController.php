<?php

namespace App\Http\Controllers;

use App\Models\ClientRequirement;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ClientRequirementController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $userRole = $user->role ?? null;
        $isManager = in_array($userRole, ['manager', 'admin']) || ($user && $user->email === 'manager@example.com');

        $query = ClientRequirement::with('service');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_mandatory')) {
            $query->where('is_mandatory', $request->is_mandatory === '1');
        }

        $perPage = $request->get('per_page', 10);
        $requirements = $query->orderBy('created_at', 'desc')->paginate($perPage)->appends($request->query());
        $services = Service::all();

        return view('requirements.index', compact('requirements', 'services', 'isManager'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id'        => 'required|exists:services,id',
            'document_name'     => 'required|string|max:255',
            'client_type'       => 'nullable|string',
            'source'            => 'nullable|string',
            'description'       => 'nullable|string',
            'conditional_rule' => 'nullable|string',
            'is_mandatory'      => 'nullable|boolean',
            'file_required'     => 'nullable|boolean',
            'original_required' => 'nullable|boolean',
        ]);

        // Dynamically detect existing database columns to avoid SQL missing column errors
        $dataToInsert = [
            'service_id'    => $validated['service_id'],
            'document_name' => $validated['document_name'],
            'description'   => $validated['description'] ?? null,
            'is_mandatory'  => $request->has('is_mandatory'),
            'status'        => 'pending',
        ];

        if (Schema::hasColumn('client_requirements', 'client_type')) {
            $dataToInsert['client_type'] = $validated['client_type'] ?? 'All';
        }
        if (Schema::hasColumn('client_requirements', 'source')) {
            $dataToInsert['source'] = $validated['source'] ?? 'Client-supplied';
        }
        if (Schema::hasColumn('client_requirements', 'conditional_rule')) {
            $dataToInsert['conditional_rule'] = $validated['conditional_rule'] ?? null;
        }
        if (Schema::hasColumn('client_requirements', 'file_required')) {
            $dataToInsert['file_required'] = $request->has('file_required');
        }
        if (Schema::hasColumn('client_requirements', 'original_required')) {
            $dataToInsert['original_required'] = $request->has('original_required');
        }

        ClientRequirement::create($dataToInsert);

        return redirect()->back()->with('success', 'Requirement template created successfully!');
    }

    public function upload(Request $request, ClientRequirement $requirement)
    {
        $request->validate([
            'document_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if ($request->hasFile('document_file')) {
            if ($requirement->file_path && Storage::disk('public')->exists($requirement->file_path)) {
                Storage::disk('public')->delete($requirement->file_path);
            }

            $path = $request->file('document_file')->store('client_requirements', 'public');

            $requirement->update([
                'file_path' => $path,
                'status'    => 'submitted',
            ]);
        }

        return redirect()->back()->with('success', 'Document uploaded successfully!');
    }

    public function verify(Request $request, ClientRequirement $requirement)
    {
        $validated = $request->validate([
            'status'           => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string',
        ]);

        $updateData = [
            'status' => $validated['status'],
        ];

        if (Schema::hasColumn('client_requirements', 'rejection_reason')) {
            $updateData['rejection_reason'] = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        }

        $requirement->update($updateData);

        return redirect()->back()->with('success', 'Requirement status updated to ' . strtoupper($validated['status']) . '!');
    }

    public function approve(ClientRequirement $requirement)
    {
        $requirement->update(['status' => 'approved']);
        return redirect()->back()->with('success', 'Requirement approved successfully!');
    }

    public function reject(Request $request, ClientRequirement $requirement)
    {
        $updateData = ['status' => 'rejected'];
        if (Schema::hasColumn('client_requirements', 'rejection_reason')) {
            $updateData['rejection_reason'] = $request->input('rejection_reason', 'Document does not meet standard requirement.');
        }
        
        $requirement->update($updateData);
        return redirect()->back()->with('success', 'Requirement rejected.');
    }

    public function destroy(ClientRequirement $requirement)
    {
        if ($requirement->file_path && Storage::disk('public')->exists($requirement->file_path)) {
            Storage::disk('public')->delete($requirement->file_path);
        }

        $requirement->delete();

        return redirect()->back()->with('success', 'Requirement deleted successfully!');
    }
}