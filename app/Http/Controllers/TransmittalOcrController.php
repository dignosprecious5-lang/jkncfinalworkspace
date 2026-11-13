<?php

namespace App\Http\Controllers;

use App\Exceptions\OcrUnavailableException;
use App\Models\User;
use App\Services\TransmittalOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class TransmittalOcrController extends Controller
{
    public function __invoke(Request $request, TransmittalOcrService $ocr): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isSuperAdmin()), 403);

        $validated = $request->validate([
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:10240',
            ],
        ]);

        try {
            return response()->json([
                'suggestions' => $ocr->analyze($validated['document']),
            ]);
        } catch (OcrUnavailableException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Document analysis is temporarily unavailable.',
            ], 503);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Document analysis is temporarily unavailable.',
            ], 500);
        }
    }
}