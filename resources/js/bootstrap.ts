/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;
import Swal from 'sweetalert2'
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';


import Pusher from "pusher-js";
import { Notification } from './types/model_type';

// Enable pusher logging - don't include this in production
// Pusher.logToConsole = true;
const pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  //   encrypted: true,
});
window.pusher = pusher;
window.appChannel = pusher.subscribe('app');
const toast = Swal.mixin({
  toast: true, position: 'bottom-end', timer: 3000,
  timerProgressBar: true, confirmButtonText: 'Open'
});

window.appChannel.bind('notification', (data: { notification: Notification; }) => {
  const notification = data.notification;
  toast.fire(
    {
      title: notification.title,
      text: notification.subtitle,
      
    }, 
  ).then((value) => {
    if(value.isConfirmed && notification.link != ''){
      window.location.href = notification.link;
    }
  })
})
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });


