import { createApp } from 'vue';
import App from '../vue/MuxDomainRestrictions.vue';

const el = document.getElementById('mux-domain-restrictions');

let defaultInfo = { id: null, raw: '', source: 'none' };
try {
    defaultInfo = JSON.parse(el?.dataset.default || '{}');
} catch (e) {
    // keep the empty default
}

const app = createApp(App, {
    defaultInfo,
    settingsUrl: el?.dataset.settingsUrl || '',
});
// -- Mount App
app.mount('#mux-domain-restrictions');
