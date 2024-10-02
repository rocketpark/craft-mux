// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept();
}
import { createApp } from 'vue';
import App from '../vue/Dashboard.vue';

const app = createApp(App);
app.mount('#mux-upload');


// Re-broadcast the custom `vite-script-loaded` event so that we know that this module has loaded
// Needed because when <script> tags are appended to the DOM, the `onload` handlers
// are not executed, which happens in the field Settings page, and in slideouts
// Do this after the document is ready to ensure proper execution order
document.addEventListener("DOMContentLoaded", function () {
    //document.dispatchEvent(new CustomEvent('vite-script-loaded', { detail: { path: 'src/js/mux-dashboard.js' } }));
});
