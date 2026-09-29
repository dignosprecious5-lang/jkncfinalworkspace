<?php

namespace App\Http\Controllers;

use App\Models\ClientActionRequest;
use App\Models\Deal;
use App\Services\ClientActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClientActionController extends Controller
{
    protected ClientActionService $clientActionService;

    public function __construct(ClientActionService $clientActionService)
    {
        $this->clientActionService = $clientActionService;
    }

    /**
     * List Client Action Requests with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClientActionRequest::query()->with(['deal', 'account', 'contact', 'createdByUser', 'recordedByUser', 'verifiedByUser']);

        if ($request->filled('deal_id')) {
            $query->where('deal_id', $request->deal_id);
        }

        if ($request->filled('action_type') && $request->action_type !== 'all') {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel') && $request->channel !== 'all') {
            $query->where('response_channel', $request->channel);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('action_code', 'like', "%{$s}%")
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhere('client_name', 'like', "%{$s}%")
                    ->orWhere('document_title', 'like', "%{$s}%")
                    ->orWhere('document_version', 'like', "%{$s}%");
            });
        }

        $actions = $query->latestFirst()->get();

        return response()->json([
            'success' => true,
            'count' => $actions->count(),
            'actions' => $actions,
        ]);
    }

    /**
     * Create a new Client Action Request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deal_id' => 'nullable|exists:deals,id',
            'action_type' => 'required|string|in:Review,Select,Accept,Approve,Sign,Acknowledge,Upload,Respond',
            'document_type' => 'required|string|max:100',
            'document_title' => 'required|string|max:255',
            'document_version' => 'required|string|max:50',
            'document_reference' => 'nullable|string|max:100',
            'document_url' => 'nullable|string|max:500',
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'requires_otp' => 'nullable|boolean',
            'actionable_type' => 'nullable|string',
            'actionable_id' => 'nullable|integer',
        ]);

        $action = $this->clientActionService->createActionRequest($validated);

        // If requested to dispatch immediately
        if ($request->boolean('send_now')) {
            $this->clientActionService->dispatchAction($action);
        }

        return response()->json([
            'success' => true,
            'message' => "Client Action Request {$action->action_code} created successfully.",
            'action' => $action->fresh(['deal', 'createdByUser']),
        ]);
    }

    /**
     * Show single Client Action Request details.
     */
    public function show(int $id): JsonResponse
    {
        $action = ClientActionRequest::with(['deal', 'account', 'contact', 'createdByUser', 'recordedByUser', 'verifiedByUser'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'action' => $action,
            'secure_url' => $action->secure_url,
        ]);
    }

    /**
     * Staff endpoint to record responses (Email, Manual Wet Signature, External Signing, or Portal).
     */
    public function recordResponse(Request $request, int $id): JsonResponse
    {
        $action = ClientActionRequest::findOrFail($id);

        $validated = $request->validate([
            'channel' => 'required|string|in:Email,Manual / Wet Signature,External Signing,Client Portal,Secure Link',
            'decision' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'signatory_name' => 'nullable|string|max:255',
            'document_date' => 'nullable|date',
            'external_method' => 'nullable|string|max:100',
            'verification_state' => 'nullable|string|in:Verified,Unverified,Pending,Verified (Staff Recorded Email),Verified (Physical Copy),Verified (External Proof)',
            'evidence_file' => 'nullable|file|max:15360', // 15MB max
        ]);

        $channel = $validated['channel'];
        $decision = $validated['decision'];
        $notes = $validated['notes'] ?? '';

        // Handle uploaded file if present
        $filePath = null;
        $fileName = null;
        if ($request->hasFile('evidence_file')) {
            $file = $request->file('evidence_file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs('client-actions/evidence/' . $action->id, time() . '_' . $fileName, 'public');
        }

        $user = auth()->user();
        $recorderId = $user ? $user->id : null;
        $recorderName = $user ? $user->name : 'Staff User';

        switch ($channel) {
            case ClientActionRequest::CHANNEL_EMAIL:
                $this->clientActionService->recordEmailResponse(
                    $action,
                    $decision,
                    $notes ?: 'Recorded from client email response.',
                    $filePath,
                    $fileName,
                    $recorderId,
                    $recorderName
                );
                break;

            case ClientActionRequest::CHANNEL_MANUAL_SIGNATURE:
                $signatory = $validated['signatory_name'] ?: ($action->client_name ?: 'Client Signatory');
                $docDate = $validated['document_date'] ?: now()->format('Y-m-d');
                $vState = $validated['verification_state'] ?? 'Verified (Physical Copy)';

                $this->clientActionService->recordManualSignature(
                    $action,
                    $signatory,
                    $docDate,
                    $filePath,
                    $fileName,
                    $notes,
                    $recorderId,
                    $recorderName,
                    $vState
                );
                break;

            case ClientActionRequest::CHANNEL_EXTERNAL_SIGNING:
                $method = $validated['external_method'] ?: 'External Signing';
                $signatory = $validated['signatory_name'] ?: ($action->client_name ?: 'Client Signatory');
                $docDate = $validated['document_date'] ?: now()->format('Y-m-d');
                $vState = $validated['verification_state'] ?? 'Verified (External Proof)';

                $this->clientActionService->recordExternalSigning(
                    $action,
                    $method,
                    $signatory,
                    $docDate,
                    $filePath,
                    $fileName,
                    $notes,
                    $recorderId,
                    $recorderName,
                    $vState
                );
                break;

            case ClientActionRequest::CHANNEL_PORTAL:
                $this->clientActionService->recordPortalResponse(
                    $action,
                    $decision,
                    $notes,
                    ['recorded_by' => $recorderName]
                );
                break;

            default:
                $this->clientActionService->recordSecureLinkResponse(
                    $action,
                    $decision,
                    $notes,
                    ['recorded_by' => $recorderName]
                );
                break;
        }

        return response()->json([
            'success' => true,
            'message' => "Client Action {$action->action_code} updated successfully via {$channel}.",
            'action' => $action->fresh(['deal', 'recordedByUser', 'verifiedByUser']),
        ]);
    }

    /**
     * Dispatch / Send action request.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $action = ClientActionRequest::findOrFail($id);
        $this->clientActionService->dispatchAction($action);

        return response()->json([
            'success' => true,
            'message' => "Client Action {$action->action_code} dispatched to {$action->client_name}.",
            'action' => $action->fresh(),
        ]);
    }

    /**
     * Verify evidence for an action request.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $action = ClientActionRequest::findOrFail($id);
        $user = auth()->user();
        $state = $request->input('verification_state', 'Verified');
        $notes = $request->input('verification_notes');

        $this->clientActionService->verifyAction(
            $action,
            $user ? $user->id : 1,
            $user ? $user->name : 'Staff User',
            $state,
            $notes
        );

        return response()->json([
            'success' => true,
            'message' => "Evidence verification updated for {$action->action_code}.",
            'action' => $action->fresh(['verifiedByUser']),
        ]);
    }

    /**
     * Client Portal Endpoint: Fetch actions for an authorized client.
     */
    public function portalActions(Request $request): JsonResponse
    {
        $email = $request->input('email');
        $dealId = $request->input('deal_id');

        $query = ClientActionRequest::query()->with('deal');

        if ($dealId) {
            $query->where('deal_id', $dealId);
        }

        if ($email) {
            $query->where('client_email', $email);
        }

        $actions = $query->latestFirst()->get();

        return response()->json([
            'success' => true,
            'actions' => $actions,
        ]);
    }

    /**
     * Download or view controlled printable document for manual/wet signing.
     */
    public function downloadControlledDocument(int $id)
    {
        $action = ClientActionRequest::with('deal')->findOrFail($id);

        return view('client-action.controlled-document', compact('action'));
    }
}
