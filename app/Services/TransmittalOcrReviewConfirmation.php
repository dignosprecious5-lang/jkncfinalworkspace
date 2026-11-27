<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

final class TransmittalOcrReviewConfirmation
{
    private const SESSION_KEY = 'transmittal_ocr.pending_review';

    public function begin(Request $request): string
    {
        $id = bin2hex(random_bytes(32));
        $request->session()->put(self::SESSION_KEY, [
            'id' => $id,
            'user_id' => (int) $request->user()->getAuthIdentifier(),
            'expires_at' => time() + 1800,
        ]);

        return $id;
    }

    public function confirm(Request $request): string
    {
        $pending = $this->pending($request);
        $request->validate(['analysis_id' => ['required', 'string', 'size:64']]);

        if (! hash_equals($pending['id'], (string) $request->input('analysis_id'))) {
            $this->fail();
        }

        return Crypt::encryptString(json_encode([
            'id' => $pending['id'],
            'user_id' => (int) $request->user()->getAuthIdentifier(),
            'session_hash' => hash('sha256', $request->session()->getId()),
            'payload_hash' => $this->payloadHash($request),
            'expires_at' => $pending['expires_at'],
        ], JSON_THROW_ON_ERROR));
    }

    public function assertStore(Request $request): void
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        $hasReviewFields = $request->filled('ocr_review_token') || $request->filled('ocr_analysis_id');

        if (! is_array($pending)) {
            if ($hasReviewFields) {
                $this->fail();
            }

            return;
        }

        try {
            $claims = json_decode(
                Crypt::decryptString((string) $request->input('ocr_review_token', '')),
                true,
                16,
                JSON_THROW_ON_ERROR
            );
        } catch (Throwable) {
            $this->fail();
        }

        $userId = (int) $request->user()->getAuthIdentifier();
        if (! is_array($claims)
            || (int) ($pending['expires_at'] ?? 0) < time()
            || (int) ($claims['expires_at'] ?? 0) < time()
            || ! hash_equals((string) ($pending['id'] ?? ''), (string) $request->input('ocr_analysis_id', ''))
            || ! hash_equals((string) ($pending['id'] ?? ''), (string) ($claims['id'] ?? ''))
            || (int) ($pending['user_id'] ?? 0) !== $userId
            || (int) ($claims['user_id'] ?? 0) !== $userId
            || ! hash_equals((string) ($claims['session_hash'] ?? ''), hash('sha256', $request->session()->getId()))
            || ! hash_equals((string) ($claims['payload_hash'] ?? ''), $this->payloadHash($request))) {
            $this->fail();
        }
    }

    public function complete(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function discard(Request $request): void
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        $request->validate(['analysis_id' => ['required', 'string', 'size:64']]);
        if (! is_array($pending)
            || (int) ($pending['user_id'] ?? 0) !== (int) $request->user()->getAuthIdentifier()
            || ! hash_equals((string) ($pending['id'] ?? ''), (string) $request->input('analysis_id'))) {
            $this->fail();
        }

        $request->session()->forget(self::SESSION_KEY);
    }

    private function pending(Request $request): array
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending)
            || (int) ($pending['expires_at'] ?? 0) < time()
            || (int) ($pending['user_id'] ?? 0) !== (int) $request->user()->getAuthIdentifier()) {
            $this->fail();
        }

        return $pending;
    }

    private function payloadHash(Request $request): string
    {
        $keys = [
            'transmittal_date', 'mode', 'party_name', 'office_name', 'address', 'delivery_type',
            'by_person_who', 'registered_mail_provider', 'electronic_method', 'recipient_email',
            'action_delivery', 'action_pick_up', 'action_drop_off', 'action_email', 'approved_by_name',
            'approved_position', 'document_custodian', 'delivered_by', 'received_by',
            'receiver_affiliation', 'received_at',
        ];
        $data = [];
        foreach ($keys as $key) {
            $value = $request->input($key, '');
            $data[$key] = is_scalar($value) ? (string) $value : '';
        }

        $items = $request->input('items', []);
        if (is_string($items)) {
            try {
                $items = json_decode($items, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $this->fail();
            }
        }
        if (! is_array($items)) {
            $this->fail();
        }

        $data['items'] = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                $this->fail();
            }
            $row = [];
            foreach (['particular', 'unique_id', 'qty', 'description', 'remarks'] as $key) {
                $value = $item[$key] ?? '';
                $row[$key] = is_scalar($value) ? (string) $value : '';
            }
            $data['items'][] = $row;
        }

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function fail(): never
    {
        throw ValidationException::withMessages([
            'ocr_review_token' => 'Review OCR-populated information and confirm it before saving, or discard OCR to continue manually.',
        ]);
    }
}
