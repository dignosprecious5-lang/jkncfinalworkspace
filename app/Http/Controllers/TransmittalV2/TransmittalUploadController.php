<?php

namespace App\Http\Controllers\TransmittalV2;

use App\Exceptions\OcrUnavailableException;
use App\Models\Transmittal;
use App\Services\TransmittalWorkflowBoundary;
use App\Models\TransmittalDocument;
use App\Services\TransmittalV2\ResumableTransmittalUploadService;
use App\Services\TransmittalV2\TransmittalUploadBudget;
use App\Services\TransmittalV2Authorization;
use App\Http\Controllers\Controller;
use App\Http\Middleware\TransmittalV2\EnsureTransmittalV2Enabled;
use App\Services\TransmittalOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TransmittalUploadController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [EnsureTransmittalV2Enabled::class];
    }

    public function start(Request $request, ResumableTransmittalUploadService $uploads): JsonResponse
    {
        $this->assertEnabled();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1', 'max:'.min((int) config('transmittal_v2.upload.max_bytes'), TransmittalUploadBudget::MAX_FILE_BYTES)],
            'type' => ['nullable', 'string', 'max:127'],
        ]);
        $token = Str::random(64);
        $upload = $uploads->start(
            $this->ownerHash($request),
            $token,
            $data['name'],
            (int) $data['size'],
            $data['type'] ?? 'application/octet-stream'
        );
        $tokens = $request->session()->get('transmittal_upload_tokens', []);
        $tokens[$upload->upload_id] = $token;
        $request->session()->put('transmittal_upload_tokens', $tokens);

        return response()->json([
            'upload_id' => $upload->upload_id,
            'chunk_size' => $upload->chunk_size,
            'chunk_count' => $upload->chunk_count,
            'expires_at' => $upload->expires_at->toIso8601String(),
        ], 201);
    }

    public function putChunk(
        Request $request,
        string $uploadId,
        int $index,
        ResumableTransmittalUploadService $uploads
    ): JsonResponse {
        $this->assertEnabled();
        $request->validate([
            'chunk' => ['required', 'file', 'max:'.(int) ceil(config('transmittal_v2.upload.chunk_bytes') / 1024)],
        ]);
        $result = $uploads->putChunk(
            $this->ownerHash($request),
            $uploadId,
            $this->uploadToken($request, $uploadId),
            $index,
            $request->file('chunk')
        );

        return response()->json($result);
    }

    public function complete(Request $request, string $uploadId, ResumableTransmittalUploadService $uploads): JsonResponse
    {
        $this->assertEnabled();
        $upload = $uploads->complete($this->ownerHash($request), $uploadId, $this->uploadToken($request, $uploadId));

        return response()->json([
            'upload_id' => $upload->upload_id,
            'name' => $upload->original_name,
            'size' => $upload->expected_size,
            'sha256' => $upload->sha256,
            'status' => $upload->status,
        ]);
    }

    public function analyze(
        Request $request,
        string $uploadId,
        ResumableTransmittalUploadService $uploads,
        TransmittalOcrService $ocr
    ): JsonResponse {
        $this->assertEnabled();
        $upload = $uploads->ownedUpload($this->ownerHash($request), $uploadId, $this->uploadToken($request, $uploadId));
        $file = $uploads->temporaryFile($upload);
        (new TransmittalUploadBudget())->validate($request->allFiles(), [$file]);
        validator(['document' => $file], [
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,tif,tiff', 'max:'.(int) ceil(config('transmittal_v2.upload.max_bytes') / 1024)],
        ])->validate();

        try {
            $analysis = $ocr->analyze($file);
            $path = $file->getRealPath();
            abort_unless(is_string($path), 422, 'The uploaded file could not be verified.');
            $sha256 = hash_file('sha256', $path);

            // Match against existing records only for authorized transmittal administrators.
            $canMatchExistingRecords = TransmittalV2Authorization::canApprove($request->user());
            $existingDoc = $canMatchExistingRecords
                ? TransmittalWorkflowBoundary::v2Children(TransmittalDocument::class)
                    ->with('transmittal:id,tracking_number,subject')
                    ->where(function ($query) use ($sha256, $upload): void {
                        $query->where('sha256', $sha256)
                            ->orWhere('original_name', $upload->original_name);
                    })
                    ->first()
                : null;

            $rawFields = (isset($analysis['fields']) && is_array($analysis['fields']))
                ? $analysis['fields']
                : (is_array($analysis) ? $analysis : []);

            $firstItem = $rawFields['items'][0] ?? [];

            $computed = [
                'subject' => $rawFields['subject'] ?? ($rawFields['particular'] ?? ($firstItem['particular'] ?? ($rawFields['purpose'] ?? null))),
                'reference' => $rawFields['reference'] ?? ($rawFields['unique_id'] ?? ($firstItem['unique_id'] ?? null)),
                'company' => $rawFields['company'] ?? ($rawFields['party_name'] ?? ($rawFields['office_name'] ?? null)),
                'recipient_address' => $rawFields['recipient_address'] ?? ($rawFields['address'] ?? null),
                'recipient_name' => $rawFields['recipient_name'] ?? ($rawFields['attention'] ?? ($rawFields['party_name'] ?? null)),
                'recipient_email' => $rawFields['recipient_email'] ?? null,
                'purpose' => $rawFields['purpose'] ?? null,
                'transmittal_date' => $rawFields['transmittal_date'] ?? null,
                'delivery_type' => $rawFields['delivery_type'] ?? null,
            ];

            $fields = $rawFields;
            foreach ($computed as $key => $val) {
                if ($val !== null && $val !== '') {
                    if (! isset($fields[$key]) || $fields[$key] === null || $fields[$key] === '') {
                        $fields[$key] = $val;
                    }
                }
            }

            $category = $analysis['category'] ?? 'document';
            $fieldMetadata = $analysis['field_metadata'] ?? [];
            if ($fieldMetadata === []) {
                foreach ($fields as $key => $val) {
                    if ($val !== null && $val !== '' && $key !== 'items') {
                        $fieldMetadata[$key] = [
                            'source' => 'tesseract_ocr',
                            'confidence' => null,
                        ];
                    }
                }
            }
            $text = $analysis['text'] ?? ($rawFields['description'] ?? ($firstItem['description'] ?? ''));

            $refMatch = $canMatchExistingRecords && ! empty($fields['reference'])
                ? TransmittalWorkflowBoundary::v2Query()->where('external_reference', $fields['reference'])->first(['id', 'tracking_number', 'subject'])
                : null;

            $matchedRecord = null;
            if ($existingDoc) {
                $matchedRecord = [
                    'type' => $existingDoc->sha256 === $sha256 ? 'duplicate_file' : 'similar_name',
                    'label' => $existingDoc->sha256 === $sha256 ? 'Duplicate File' : 'Matched Document',
                    'tracking_number' => $existingDoc->transmittal?->tracking_number,
                    'subject' => $existingDoc->transmittal?->subject,
                    'document_name' => $existingDoc->original_name,
                ];
            } elseif ($refMatch) {
                $matchedRecord = [
                    'type' => 'reference_match',
                    'label' => 'Matched Reference',
                    'tracking_number' => $refMatch->tracking_number,
                    'subject' => $refMatch->subject,
                ];
            }

            return response()->json([
                'name' => $upload->original_name,
                'category' => $category,
                'fields' => $fields,
                'field_metadata' => $fieldMetadata,
                'matched_record' => $matchedRecord,
                'sha256' => $sha256,
                'text_preview' => Str::limit($text, 500),
                'analysis_token' => Crypt::encryptString(json_encode([
                    'name' => basename($upload->original_name),
                    'sha256' => $sha256,
                    'category' => $category,
                    'text' => $text,
                    'fields' => $fields,
                    'field_metadata' => $fieldMetadata,
                ], JSON_THROW_ON_ERROR)),
            ]);
        } catch (OcrUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    private function ownerHash(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }

    private function uploadToken(Request $request, string $uploadId): string
    {
        $token = $request->session()->get("transmittal_upload_tokens.{$uploadId}");
        abort_unless(is_string($token), 404);

        return $token;
    }

    private function assertEnabled(): void
    {
        abort_unless((bool) config('transmittal_v2.enabled', false), 404);
    }
}
