import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// On initialise Echo uniquement si la clé Reverb est définie et non vide
// Pourquoi ? Sur les pages publiques (login, accueil) Reverb n'est pas
// nécessaire. Si le serveur Reverb ne tourne pas, les tentatives de
// connexion en boucle polluent la console sans aucun impact fonctionnel.
// On les réduit en désactivant les reconnexions automatiques agressives.

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey && reverbKey !== 'votre-app-key') {
    window.Echo = new Echo({
        broadcaster:        'reverb',
        key:                reverbKey,
        wsHost:             import.meta.env.VITE_REVERB_HOST    ?? 'localhost',
        wsPort:             import.meta.env.VITE_REVERB_PORT    ?? 8080,
        wssPort:            import.meta.env.VITE_REVERB_PORT    ?? 8080,
        // forceTLS false en local car pas de certificat SSL
        // En prod, mettre VITE_REVERB_SCHEME=https dans .env
        forceTLS:           (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
        // On retire 'wss' pour ne pas avoir les double-erreurs ws/wss en local
        // En prod avec TLS, changer en ['wss'] ou ['ws', 'wss']
        enabledTransports:  ['ws'],
        // Délais augmentés pour éviter la boucle de reconnexion agressive
        activityTimeout:    30000,
        pongTimeout:        10000,
        unavailableTimeout: 30000,
    });
}
