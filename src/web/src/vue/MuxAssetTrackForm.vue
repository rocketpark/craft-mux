<template>
    <div class="mux-asset-track-form">
        <div class="field width-100">
            <div class="heading">
                <label id="mux-track-url-heading" for="mux-track-url">
                    Caption/Subtitles File URL <span class="visually-hidden">Required</span><span class="required" aria-hidden="true"></span>
                    <span class="info">
                        <ol>
                            <li>Upload the caption file of the asset in either .srt or .vtt format to Craft CMS Assets.</li>
                            <li>Retrieve the URL associated with this input.</li>
                        </ol>
                    </span>
                </label>
            </div>
            <div class="input ltr">
                <input
                    id="mux-track-url" v-model="form.url" type="text" class="nicetext code text fullwidth" autocomplete="off" placeholder=""
                    dir="ltr" required
                >
            </div>
        </div>
        <div class="field-group">
            <div class="field width-100">
                <div class="heading">
                    <label id="mux-track-language-name-heading" for="mux-track-language-name">Language Name <span class="visually-hidden">Required</span><span class="required" aria-hidden="true"></span></label>
                </div>
                <div class="input ltr">
                    <input
                        id="mux-track-language-name" v-model="form.name" type="text" class="nicetext text fullwidth" autocomplete="off" placeholder="US English"
                        dir="ltr" required
                    >
                </div>
            </div>
            <div class="field width-100">
                <div class="heading">
                    <label id="mux-track-language-code-heading" for="mux-track-language-code">Language Code<span class="visually-hidden">Required</span><span class="required" aria-hidden="true"></span></label>
                </div>
                <div class="input ltr">
                    <input
                        id="mux-track-language-code" v-model="form.language_code" type="text" class="nicetext text fullwidth" autocomplete="off" placeholder="en-US"
                        dir="ltr" required
                    >
                </div>
            </div>
            <div class="field lightswitch-field">
                <div class="heading">
                    <label id="mux-track-closed-captions-heading" for="mux-track-closed-captions">Closed Captions</label>
                </div>
                <div class="input ltr">
                    <button
                        id="mux-track-closed-captions" type="button" class="lightswitch" :class="[form.closed_captions ? 'on' : '']" role="switch" aria-checked="true"
                        aria-labelledby="mux-track-closed-captions" aria-describedby="mux-track-closed-captions-instructions" @click.prevent="form.closed_captions = !form.closed_captions"
                    >
                        <div class="lightswitch-container" :style="[form.closed_captions ? 'margin-left: 0px;' : 'margin-left: -12px;']">
                            <div class="handle"></div>
                        </div>
                        <input v-model="form.closed_captions" type="hidden" name="mux-track-closed-captions">
                    </button>
                </div>
            </div>
        </div>
        <div class="field">
            <button type="button" class="btn submit" :class="{'loading':loading }" @click.prevet="onAddAssetTrack()">
                <div class="label">Add Track</div>
                <div class="spinner spinner-absolute"></div>
            </button>
        </div>
    </div>
</template>
<script setup>
import { inject, ref, reactive } from 'vue';
import { addAssetTrackById } from './AssetsStore';

const emit = defineEmits(['mux-asset-track-added']);

const props = defineProps({
    assetId: {
        type: [Number, String],
        required: true,
    },
});

const loading = ref(false);
const form = reactive({
    url: '',
    type: 'text',
    text_type: 'subtitles',
    language_code: '',
    name: '',
    closed_captions: true,
});

const onAddAssetTrack = function(evt) {
    loading.value = true;
    addAssetTrackById(props.assetId, form)
        .then((res) => {
            loading.value = false;
            emit('mux-asset-track-added');
        })
        .catch((err) => {
            loading.value = false;
        });
};
</script>
