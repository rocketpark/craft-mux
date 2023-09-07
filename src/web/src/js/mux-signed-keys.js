import { createApp } from 'vue';
import mitt from 'mitt';
import App from '../vue/MuxSignedKeys.vue';

const emitter = mitt();
const app = createApp(App);
app.provide('emitter', emitter);
// -- Mount App
app.mount('#mux-signed-keys');
