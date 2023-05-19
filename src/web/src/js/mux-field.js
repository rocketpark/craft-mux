import { createApp } from 'vue';
import MuxField from '../vue/MuxField.vue';

const app = createApp(MuxField);
//-- Mount App
app.mount('[id*=mux-muxfield]');