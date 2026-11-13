<?php

return [
    // V2 remains unavailable unless a future, separately approved activation enables it.
    'enabled' => (bool) env('ORDO_TRANSMITTAL_V2_ENABLED', false),
    'office_name' => env('ORDO_TRANSMITTAL_OFFICE_NAME', 'JK&C INC.'),
    'optional_features' => [
        'smart_upload' => (bool) env('ORDO_TRANSMITTAL_SMART_UPLOAD_ENABLED', false),
        'ocr' => (bool) env('ORDO_TRANSMITTAL_OCR_ENABLED', false),
    ],

    'upload' => [
        'max_files' => (int) env('ORDO_TRANSMITTAL_MAX_FILES', 50),
        'max_bytes' => (int) env('ORDO_TRANSMITTAL_UPLOAD_MAX_BYTES', 52428800),
        'chunk_bytes' => (int) env('ORDO_TRANSMITTAL_UPLOAD_CHUNK_BYTES', 5242880),
        'max_chunks' => (int) env('ORDO_TRANSMITTAL_UPLOAD_MAX_CHUNKS', 4096),
        'expiry_hours' => (int) env('ORDO_TRANSMITTAL_UPLOAD_EXPIRY_HOURS', 24),
        'completion_lock_seconds' => (int) env('ORDO_TRANSMITTAL_UPLOAD_LOCK_SECONDS', 3600),
    ],

    'acknowledgment' => [
        'days' => (int) env('ORDO_TRANSMITTAL_ACKNOWLEDGMENT_DAYS', 3),
        'hours' => (int) env('ORDO_TRANSMITTAL_ACKNOWLEDGMENT_HOURS', 72),
    ],

    'brevo' => [
        // Explicit opt-in for the encrypted V2 outbox adapter. Existing direct
        // calls retain their current behavior; this flag defaults off.
        'outbox_enabled' => (bool) env('ORDO_TRANSMITTAL_V2_BREVO_OUTBOX_ENABLED', false),
        'api_key' => env('ORDO_TRANSMITTAL_BREVO_API_KEY'),
        'sender_email' => env('ORDO_TRANSMITTAL_BREVO_SENDER_EMAIL'),
        'sender_name' => env('ORDO_TRANSMITTAL_BREVO_SENDER_NAME', env('APP_NAME', 'ORDO Transmittal')),
        'timeout_seconds' => (int) env('ORDO_TRANSMITTAL_BREVO_TIMEOUT_SECONDS', 20),
    ],

    'dispatch' => [
        // Sending remains disabled even when the V2 application routes are enabled.
        'enabled' => (bool) env('ORDO_TRANSMITTAL_V2_DISPATCH_ENABLED', false),
        'provider' => env('ORDO_TRANSMITTAL_V2_DISPATCH_PROVIDER', 'disabled'),
        'current_key_id' => env('ORDO_TRANSMITTAL_V2_ENCRYPTION_CURRENT_KEY_ID'),
        // JSON object of key-id => base64: 32-byte key. Secret values belong only in protected environment configuration.
        'keyring' => (static function (): array {
            $value = env('ORDO_TRANSMITTAL_V2_ENCRYPTION_KEYRING', '');
            if (! is_string($value) || trim($value) === '') {
                return [];
            }
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        })(),
        'operations_manager_user_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('ORDO_TRANSMITTAL_V2_OPERATIONS_MANAGER_USER_IDS', ''))))),
        'designated_admin_user_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('ORDO_TRANSMITTAL_V2_DESIGNATED_ADMIN_USER_IDS', ''))))),
        'max_attempts' => (int) env('ORDO_TRANSMITTAL_V2_DISPATCH_MAX_ATTEMPTS', 3),
        'max_attachment_bytes' => (int) env('ORDO_TRANSMITTAL_V2_DISPATCH_MAX_ATTACHMENT_BYTES', 52428800),
        'lease_seconds' => (int) env('ORDO_TRANSMITTAL_V2_DISPATCH_LEASE_SECONDS', 120),
        'event_secret' => env('ORDO_TRANSMITTAL_V2_PROVIDER_EVENT_SECRET'),
        'event_clock_skew_seconds' => (int) env('ORDO_TRANSMITTAL_V2_PROVIDER_EVENT_CLOCK_SKEW_SECONDS', 300),
    ],
];
