<?php

namespace App\Http\Controllers;

use App\Models\ClientActionRequest;
use App\Services\ClientActionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicClientActionController extends Controller
{
    protected ClientActionService $clientActionService;

    public function __construct(ClientActionService $clientActionService)
    {
        $this->clientActionService = $clientActionService;
    }

    /**
     * Show public secure no-login action view.
     * Scoped STRICTLY to the authorized action. No internal deal notes/audit exposed.
     */
    public function show(string $token)
    {
        $action = ClientActionRequest::where('secure_token', $token)->firstOrFail();

        // Check expiration
        $isExpired = $action->expires_at && Carbon::now()->isAfter($action->expires_at);
        if ($isExpired && $action->status === ClientActionRequest::STATUS_AWAITING_CLIENT) {
            $action->update(['status' => ClientActionRequest::STATUS_EXPIRED]);
        }

        // Record opened timestamp on first access
        if (!$action->opened_at) {
            $action->update(['opened_at' => now()]);
            $action->recordAuditEntry(
                'Opened via Secure Link',
                "Client opened the secure no-login link from IP " . (request()->ip() ?: 'Unknown'),
                null,
                $action->client_name,
                ['ip' => request()->ip(), 'user_agent' => request()->userAgent()]
            );
        }

        // Check if OTP verification is required and session is unverified
        $otpVerified = session()->get("otp_verified_{$token}", false);
        $needsOtp = $action->requires_otp && !$otpVerified && !$action->is_completed && !$isExpired;

        return view('client-action.public-show', [
            'action' => $action,
            'isExpired' => $isExpired,
            'needsOtp' => $needsOtp,
            'isCompleted' => $action->is_completed,
        ]);
    }

    /**
     * Generate & send OTP for sensitive action.
     */
    public function requestOtp(string $token): JsonResponse
    {
        $action = ClientActionRequest::where('secure_token', $token)->firstOrFail();

        if ($action->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'This action has already been completed.',
            ], 422);
        }

        $otp = $this->clientActionService->generateOtp($action);

        // In a production mailer this would send email/SMS; here we simulate and return status
        return response()->json([
            'success' => true,
            'message' => "A 6-digit verification code has been sent to {$action->client_email}.",
            // For testing/convenience in development
            'debug_code' => config('app.debug') ? $otp : null,
        ]);
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(Request $request, string $token): JsonResponse
    {
        $action = ClientActionRequest::where('secure_token', $token)->firstOrFail();
        $code = $request->input('otp_code');

        if (!$this->clientActionService->verifyOtp($action, $code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code. Please request a new code.',
            ], 422);
        }

        // Mark verified in session
        session()->put("otp_verified_{$token}", true);

        return response()->json([
            'success' => true,
            'message' => 'Verification successful.',
        ]);
    }

    /**
     * Submit client response through Secure Link.
     */
    public function submitResponse(Request $request, string $token): JsonResponse
    {
        $action = ClientActionRequest::where('secure_token', $token)->firstOrFail();

        if ($action->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'This action has already been completed and cannot be resubmitted.',
            ], 422);
        }

        if ($action->expires_at && Carbon::now()->isAfter($action->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'This action request has expired.',
            ], 422);
        }

        // Check OTP requirement if active
        if ($action->requires_otp && !session()->get("otp_verified_{$token}", false)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code required before submitting response.',
            ], 403);
        }

        $validated = $request->validate([
            'decision' => 'required|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'signatory_name' => 'nullable|string|max:255',
            'signatory_title' => 'nullable|string|max:255',
            'selected_options' => 'nullable|array',
            'uploaded_file' => 'nullable|file|max:15360',
        ]);

        $decision = $validated['decision'];
        $notes = $validated['notes'] ?? '';

        $responseData = [
            'signatory_name' => $validated['signatory_name'] ?? $action->client_name,
            'signatory_title' => $validated['signatory_title'] ?? null,
            'selected_options' => $validated['selected_options'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now()->toIso8601String(),
        ];

        // Handle uploaded requirement if action is 'Upload'
        if ($request->hasFile('uploaded_file')) {
            $file = $request->file('uploaded_file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs('client-actions/uploads/' . $action->id, time() . '_' . $fileName, 'public');
            $responseData['uploaded_file'] = $fileName;
            $action->evidence_file_path = $filePath;
            $action->evidence_file_name = $fileName;
            $action->evidence_type = 'Client Uploaded Document';
            $action->save();
        }

        // Update the SAME ClientActionRequest record
        $this->clientActionService->recordSecureLinkResponse(
            $action,
            $decision,
            $notes,
            $responseData,
            $request->ip(),
            $request->userAgent()
        );

        if (!empty($validated['signatory_name'])) {
            $action->update([
                'signatory_name' => $validated['signatory_name'],
                'signatory_title' => $validated['signatory_title'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Thank you! Your response has been recorded successfully.",
            'action_code' => $action->action_code,
            'status' => $action->fresh()->status,
        ]);
    }
}
