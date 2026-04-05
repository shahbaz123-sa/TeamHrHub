import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

export const initializeEcho = (token) => {
  if (echoInstance) {
    echoInstance.disconnect();
  }

  window.Pusher = Pusher;

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Requested-With': 'XMLHttpRequest',
      },
    },
  });

  return echoInstance;
};

export default function (app) {
  const rawToken = useCookie('accessToken').value;
  let echo;

  if (rawToken) {
    // Decode the token to replace URL-encoded characters like %7C with |
    const decodedToken = decodeURIComponent(rawToken);
    echo = initializeEcho(decodedToken);
    app.config.globalProperties.$echo = echo;
    app.provide('socket', echo);
  }

  // Provide a function to initialize Echo later (e.g., after login)
  app.provide('initializeEcho', (token) => {
    const decodedToken = decodeURIComponent(token);
    const newEcho = initializeEcho(decodedToken);
    app.config.globalProperties.$echo = newEcho;
    app.provide('socket', newEcho);
  });
}

