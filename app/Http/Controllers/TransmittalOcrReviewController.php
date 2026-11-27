<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TransmittalOcrReviewConfirmation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransmittalOcrReviewController extends Controller
{
    public function confirm(Request $request, TransmittalOcrReviewConfirmation $review): JsonResponse
    {
        abort_unless((bool) config('transmittal_ocr.enabled', false), 404);

        return response()->json(['review_token' => $review->confirm($request)]);
    }

    public function discard(Request $request, TransmittalOcrReviewConfirmation $review): JsonResponse
    {
        abort_unless((bool) config('transmittal_ocr.enabled', false), 404);

        $review->discard($request);

        return response()->json(['discarded' => true]);
    }

}
