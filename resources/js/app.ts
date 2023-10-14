import './bootstrap';
import '../css/app.css';

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { fas } from '@fortawesome/free-solid-svg-icons';
import { library, config } from '@fortawesome/fontawesome-svg-core'
import 'sweetalert2/dist/sweetalert2.min.css'

/* add icons to the library */
// library.add(faUserSecret)
library.add(fas)

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.$filters = {
            daysAgo(value: string) {
                if (!value) return '';

                const updatedAtDate = new Date(value);
                const now = new Date();
                const diffInDays = Math.floor((now - updatedAtDate) / (1000 * 60 * 60 * 24));

                return diffInDays + ' days ago';
            }
        };
        app.use(plugin)
            .use(ZiggyVue, Ziggy).
            component('font-awesome', FontAwesomeIcon)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
