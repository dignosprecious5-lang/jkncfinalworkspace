<?php

namespace App\Http\Controllers;

use App\Mail\TaskAssignedClientMail;
use App\Models\ServiceRequirement;
use App\Models\Service;
use App\Models\ServiceVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;

class RequirementController extends Controller
{
    /**
     * Display a listing of client requirements with search and filters.
     */
    public function index(Request $request)
    {
        // Kunin ang authenticated user
        $user = auth()->user();

        // Dynamic check kung manager/admin ang user
        $isManager = $user && (in_array($user->role, ['manager', 'admin']) || $user->email === 'manager@example.com');

        $query = ServiceRequirement::with(['service', 'serviceVersion.service']);

        // KUNG CLIENT: I-filter para ang sarili lang nilang requirements ang makita nila
        if (!$isManager && $user) {
            $query->where('client_id', $user->id);
        }

        // Search Filter (Document name, Requirement name, or Service details)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('requirement_name', 'like', "%{$search}%")
                  ->orWhere('document_name', 'like', "%{$search}%")
                  ->orWhereHas('service', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('service_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('serviceVersion.service', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('service_code', 'like', "%{$search}%");
                  });
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Client Type Filter
        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }

        // Mandatory Filter
        if ($request->filled('is_mandatory')) {
            $query->where('is_mandatory', $request->is_mandatory === '1');
        }

        $perPage = $request->get('per_page', 10);
        $requirements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Fetch Services list for the "Add Requirement Template" Modal
        $services = Service::all();

        // Metrics Summary Calculation
        $metricsQuery = $isManager ? ServiceRequirement::query() : ServiceRequirement::where('client_id', optional($user)->id);
        $allReqs = $metricsQuery->get();

        $metrics = [
            'total'         => $allReqs->count(),
            'mandatory'     => $allReqs->where('is_mandatory', true)->count(),
            'file_required' => $allReqs->where('file_required', true)->count(),
            'corporation'   => $allReqs->whereIn('client_type', ['corporation', 'all', 'Corporation', 'All'])->count(),
        ];

        return view('requirements.index', compact('requirements', 'services', 'metrics', 'isManager'));
    }

    /**
     * Store a new requirement template.
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id'    => 'required|exists:services,id',
            'document_name' => 'required|string|max:255',
            'client_type'   => 'nullable|string',
            'source'        => 'nullable|string',
            'description'   => 'nullable|string',
            'client_name'   => 'nullable|string|max:255',
            'client_email'  => 'nullable|email|max:255',
        ]);

        // Kukunin ang pinakabagong service_version_id kaugnay sa napiling service_id
        $serviceVersion = ServiceVersion::where('service_id', $request->service_id)->latest()->first();

        $requirement = ServiceRequirement::create([
            'service_id'         => $request->service_id,
            'service_version_id' => $serviceVersion ? $serviceVersion->id : null,
            'document_name'      => $request->document_name,
            'requirement_name'   => $request->document_name,
            'client_type'        => $request->client_type ?? 'All',
            'source'             => $request->source ?? 'Client-supplied',
            'description'        => $request->description,
            'conditional_rule'   => $request->conditional_rule,
            'is_mandatory'       => $request->has('is_mandatory'),
            'file_required'      => $request->has('file_required'),
            'status'             => 'pending',
        ]);

        if ($request->filled('client_email')) {
            try {
                Mail::to($request->client_email)->send(new TaskAssignedClientMail(
                    clientName: $request->input('client_name', 'Client'),
                    taskTitle: $requirement->requirement_name,
                    message: $requirement->description,
                    workspaceUrl: route('requirements.index'),
                ));
            } catch (\Throwable $exception) {
                Log::warning('Requirement created, but its client assignment email could not be sent.', [
                    'requirement_id' => $requirement->id,
                    'client_email' => $request->client_email,
                    'exception' => $exception->getMessage(),
                ]);

                return redirect()->route('requirements.index')
                    ->with('error', 'Requirement template was created, but the client email could not be sent.');
            }
        }

        return redirect()->route('requirements.index')->with(
            'success',
            $request->filled('client_email')
                ? 'Requirement template created and assignment email sent to the client.'
                : 'Requirement template created successfully.'
        );
    }

    /**
     * View document inline for preview (No Direct Download).
     */
    public function viewDocument($id)
    {
        $requirement = ServiceRequirement::findOrFail($id);

        if (!$requirement->file_path || !Storage::disk('public')->exists($requirement->file_path)) {
            abort(404, 'Document file not found on server.');
        }

        $path = storage_path('app/public/' . $requirement->file_path);

        return response()->file($path, [
            'Content-Type' => File::mimeType($path),
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
        ]);
    }

    /**
     * Import Requirements from Excel/CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            if (($handle = fopen($path, 'r')) !== FALSE) {
                $header = fgetcsv($handle, 1000, ',');
                
                DB::beginTransaction();
                $importedCount = 0;

                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    if (count($data) < 2) continue;

                    $serviceId    = trim($data[0]);
                    $documentName = trim($data[1]);
                    $clientType   = isset($data[2]) ? trim($data[2]) : 'All';
                    $source       = isset($data[3]) ? trim($data[3]) : 'Client-supplied';
                    $isMandatory  = isset($data[4]) ? (bool)trim($data[4]) : true;

                    if (Service::where('id', $serviceId)->exists()) {
                        $serviceVersion = ServiceVersion::where('service_id', $serviceId)->latest()->first();

                        ServiceRequirement::create([
                            'service_id'         => $serviceId,
                            'service_version_id' => $serviceVersion ? $serviceVersion->id : null,
                            'document_name'      => $documentName,
                            'requirement_name'   => $documentName,
                            'client_type'        => $clientType,
                            'source'             => $source,
                            'is_mandatory'       => $isMandatory,
                            'file_required'      => true,
                            'status'             => 'pending',
                        ]);
                        $importedCount++;
                    }
                }

                fclose($handle);
                DB::commit();

                if ($importedCount > 0) {
                    return redirect()->route('requirements.index')->with('success', "Successfully imported {$importedCount} requirement(s).");
                } else {
                    return redirect()->route('requirements.index')->with('error', 'Import failed: No valid records or matching service IDs found.');
                }
            }

            return redirect()->route('requirements.index')->with('error', 'Unable to read the uploaded file.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('requirements.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload document attachment for a requirement.
     */
    public function upload(Request $request, $id)
    {
        $request->validate([
            'document_file' => 'required|file|mimes:pdf,png,jpg,jpeg,doc,docx|max:10240',
        ]);

        $requirement = ServiceRequirement::findOrFail($id);

        if ($request->hasFile('document_file')) {
            if ($requirement->file_path) {
                Storage::disk('public')->delete($requirement->file_path);
            }

            $path = $request->file('document_file')->store('requirements', 'public');
            $requirement->update([
                'file_path'        => $path,
                'status'           => 'submitted',
                'rejection_reason' => null,
            ]);
        }

        return redirect()->route('requirements.index')->with('success', 'Document uploaded successfully.');
    }

    /**
     * Verify or review a requirement document status (Approve / Reject).
     */
    public function verify(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user || (!in_array($user->role, ['manager', 'admin']) && $user->email !== 'manager@example.com')) {
            abort(403, 'Unauthorized action.');
        }

        // Updated validation to support 'verified', 'approved', and 'rejected'
        $request->validate([
            'status'           => 'required|in:verified,approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $requirement = ServiceRequirement::findOrFail($id);
        
        // Standardize status to 'verified' for UI consistency
        $newStatus = in_array($request->status, ['verified', 'approved']) ? 'verified' : 'rejected';

        $requirement->update([
            'status'           => $newStatus,
            'rejection_reason' => $newStatus === 'rejected' ? $request->rejection_reason : null,
        ]);

        return redirect()->route('requirements.index')->with('success', 'Requirement status updated to ' . ucfirst($newStatus) . '.');
    }

    /**
     * Delete a requirement record.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user || (!in_array($user->role, ['manager', 'admin']) && $user->email !== 'manager@example.com')) {
            abort(403, 'Unauthorized action.');
        }

        $requirement = ServiceRequirement::findOrFail($id);

        if ($requirement->file_path) {
            Storage::disk('public')->delete($requirement->file_path);
        }

        $requirement->delete();

        return redirect()->route('requirements.index')->with('success', 'Requirement deleted successfully.');
    }
}