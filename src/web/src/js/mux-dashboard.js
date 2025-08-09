// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept();
}
import { createApp } from 'vue';
import App from '../vue/Dashboard.vue';

const app = createApp(App);
app.mount('#mux-upload');
