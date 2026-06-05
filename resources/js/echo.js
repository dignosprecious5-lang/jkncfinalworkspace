import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT || null;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '');
const isSecure = reverbScheme === 'https' || window.location.protocol === 'https:';

const echoConfig = {
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbHost,
    forceTLS: isSecure,
    enabledTransports: isSecure ? ['wss'] : ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
};

if (reverbPort) {
    echoConfig.wsPort = Number(reverbPort);
    echoConfig.wssPort = Number(reverbPort);
}

window.Echo = new Echo(echoConfig);

console.log('Echo loaded:', {
    host: reverbHost,
    port: reverbPort,
    scheme: reverbScheme,
    secure: isSecure,
});