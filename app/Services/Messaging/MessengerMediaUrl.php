<?php

namespace App\Services\Messaging;

class MessengerMediaUrl
{
    public static function forPath(string $path): ?string
    {
        $origin = config('messaging.messenger.public_media_url');
        if (! is_string($origin) || ! filter_var($origin, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($origin);
        $host = rtrim(strtolower($parts['host'] ?? ''), '.');
        if (($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] !== 443)
            || ! in_array($parts['path'] ?? '', ['', '/'], true)
            || ! str_contains($host, '.') || filter_var($host, FILTER_VALIDATE_IP)
            || preg_match('/(^|\.)(localhost|local|internal)$/i', $host)
            || preg_match('/(^|\.)ngrok(?:-free)?\.(app|dev|io)$/i', $host)
            || (app()->environment('production') && preg_match('/\.(test|invalid|example)$/i', $host))) {
            return null;
        }
        // Only relative public-disk paths; never publish absolute filesystem paths.
        if ($path === '' || preg_match('/[\\\\:\x00-\x1f\x7f?#]/', $path)
            || str_starts_with($path, '/') || array_intersect(explode('/', $path), ['.', '..', ''])) {
            return null;
        }

        return rtrim($origin, '/').'/storage/'.implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
