<?php

use App\Services\Messaging\MessengerMediaUrl;

test('Messenger media uses configured HTTPS origins and relative public storage paths', function () {
    config(['messaging.messenger.public_media_url' => 'https://media.example.test/']);
    expect(MessengerMediaUrl::forPath('activity-attachments/image.jpg'))
        ->toBe('https://media.example.test/storage/activity-attachments/image.jpg');
    expect(MessengerMediaUrl::forPath('activity-attachments/my image.jpg'))
        ->toBe('https://media.example.test/storage/activity-attachments/my%20image.jpg');
});

test('Messenger media rejects missing unsafe local and temporary origins', function ($origin) {
    config(['messaging.messenger.public_media_url' => $origin]);
    expect(MessengerMediaUrl::forPath('image.jpg'))->toBeNull();
})->with([null, '', 'http://media.example.test', 'https://localhost', 'https://127.0.0.1',
    'https://media.localhost', 'https://media.local', 'https://temporary.ngrok-free.app',
    'https://temporary.ngrok.io', 'https://temporary.ngrok.app', 'https://temporary.ngrok-free.dev',
    'https://temporary.ngrok-free.app.', 'https://localhost.',
    'https://user:password@media.example.test', 'https://media.example.test?key=value',
    'https://media.example.test/base', 'https://media.example.test:8443']);

test('Messenger media never publishes absolute or traversing storage paths', function ($path) {
    config(['messaging.messenger.public_media_url' => 'https://media.example.test']);
    expect(MessengerMediaUrl::forPath($path))->toBeNull();
})->with(['', '/var/www/private.jpg', 'C:\\private\\image.jpg', '../private.jpg', 'images/../private.jpg',
    'image.jpg?token=value', 'images//image.jpg']);

test('production Messenger media rejects reserved development domains', function () {
    app()->instance('env', 'production');
    config(['messaging.messenger.public_media_url' => 'https://media.example.test']);
    expect(MessengerMediaUrl::forPath('image.jpg'))->toBeNull();
    config(['messaging.messenger.public_media_url' => 'https://media.jknc.example.com']);
    expect(MessengerMediaUrl::forPath('image.jpg'))->toBe('https://media.jknc.example.com/storage/image.jpg');
});

test('Messenger configuration falls back to APP_URL only when the media override is absent', function () {
    $repository = Illuminate\Support\Env::getRepository();
    $originalAppUrl = $repository->get('APP_URL');
    $originalMediaUrl = $repository->get('MESSENGER_PUBLIC_MEDIA_URL');
    try {
        $repository->clear('MESSENGER_PUBLIC_MEDIA_URL');
        $repository->set('APP_URL', 'https://application.example.test');
        $settings = require base_path('config/messaging.php');
        expect($settings['messenger']['public_media_url'] === 'https://application.example.test')->toBeTrue();
        $repository->set('MESSENGER_PUBLIC_MEDIA_URL', 'http://invalid.example.test');
        $settings = require base_path('config/messaging.php');
        config(['messaging.messenger.public_media_url' => $settings['messenger']['public_media_url']]);
        expect(MessengerMediaUrl::forPath('image.jpg'))->toBeNull();
    } finally {
        foreach (['APP_URL' => $originalAppUrl, 'MESSENGER_PUBLIC_MEDIA_URL' => $originalMediaUrl] as $name => $value) {
            $value === null ? $repository->clear($name) : $repository->set($name, $value);
        }
    }
});
