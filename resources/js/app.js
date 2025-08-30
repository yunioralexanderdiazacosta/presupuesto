
import './bootstrap';
import '../css/app.css';
import 'sweetalert2/dist/sweetalert2.min.css';


import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import VueGates from 'vue-gates';
import rolesAndPermissions from './plugin';
import FloatingVue from 'floating-vue';
import 'floating-vue/dist/style.css';
import PrimeVue from 'primevue/config'
// import InputText from 'primevue/inputtext'
// import Slider from 'primevue/slider'
// import de tema global eliminado para PrimeVue 4.x modular

// import 'primeicons/primeicons.css'


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueGates)
            .use(rolesAndPermissions)
            .use(FloatingVue)
            .use(PrimeVue);
        // vueApp.component('InputText', InputText);
        // vueApp.component('Slider', Slider);
        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
