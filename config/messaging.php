<?php

return [
    'messenger' => [
        'enabled' => filter_var(env('MESSENGER_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'outbound_enabled' => filter_var(env('MESSENGER_OUTBOUND_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'outbound_queue' => env('MESSENGER_OUTBOUND_QUEUE', 'messenger-outbound'),
        'app_id' => env('MESSENGER_APP_ID'),
        'app_secret' => env('MESSENGER_APP_SECRET'),
        'page_id' => env('MESSENGER_PAGE_ID'),
        'page_access_token' => env('MESSENGER_PAGE_ACCESS_TOKEN'),
        'verify_token' => env('MESSENGER_VERIFY_TOKEN'),
        // Configured Send API version; inbound webhooks do not depend on this value.
        'graph_version' => env('MESSENGER_GRAPH_VERSION', 'v22.0'),
        // Unset override uses the application's origin; transport validates public HTTPS.
        // An explicitly supplied invalid override fails closed, never silently falls back.
        'public_media_url' => env('MESSENGER_PUBLIC_MEDIA_URL', env('APP_URL')),
        'inbox_user_id' => env('MESSENGER_INBOX_USER_ID'),
        'queue' => env('MESSENGER_QUEUE', 'messaging'),
        'queue_connection' => env('MESSENGER_QUEUE_CONNECTION', 'database'),
    ],
];
