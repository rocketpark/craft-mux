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
                <input type="text" id="mux-track-url" class="nicetext code text fullwidth" autocomplete="off" placeholder="" dir="ltr" required v-model="form.url">
            </div>
        </div>
        <div class="field-group">
            <div class="field width-100">
                <div class="heading">
                    <label id="mux-track-language-name-heading" for="mux-track-language-name">Language Name <span class="visually-hidden">Required</span><span class="required" aria-hidden="true"></span></label>
                </div>
                <div class="input ltr">
                    <input type="text" id="mux-track-language-name" class="nicetext text fullwidth" autocomplete="off" placeholder="US English" dir="ltr" required v-model="form.name">
                </div>
            </div>
            <div class="field width-100">
                <div class="heading">
                    <label id="mux-track-language-code-heading" for="mux-track-language-code">Language Code<span class="visually-hidden">Required</span><span class="required" aria-hidden="true"></span></label>
                </div>
                <div class="input ltr">
                    <input type="text" id="mux-track-language-code" class="nicetext text fullwidth" autocomplete="off" placeholder="en-US" dir="ltr" required v-model="form.language_code">
                </div>
            </div>
            <div class="field lightswitch-field" >
                <div class="heading">
                    <label id="mux-track-closed-captions-heading" for="mux-track-closed-captions">Closed Captions</label>
                </div>
                <div class="input ltr">
                    <button @click.prevent="form.closed_captions = !form.closed_captions" type="button" id="mux-track-closed-captions" class="lightswitch" :class="[form.closed_captions ? 'on' : '']" role="switch" aria-checked="true" aria-labelledby="mux-track-closed-captions" aria-describedby="mux-track-closed-captions-instructions">
                        <div class="lightswitch-container" :style="[form.closed_captions ? 'margin-left: 0px;' : 'margin-left: -12px;']">
                            <div class="handle"></div>
                        </div>
                        <input type="hidden" name="mux-track-closed-captions"  v-model="form.closed_captions">
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
    import { addAssetTrackById } from "./AssetsStore";

    const emitter = inject('emitter');
    const props = defineProps({
        assetId: {
            type: [Number, String],
            required: true
        }
    });
        
    const loading = ref(false);
    const form = reactive({
        url: '',
        type: 'text',
        text_type: 'subtitles',
        language_code: '',
        name: '',
        closed_captions:true,
    });
    
    const onAddAssetTrack = function (evt) {
        loading.value = true;
        addAssetTrackById(props.assetId, form)
        .then(res => {
            loading.value = false;
            emitter.emit('mux-asset-track-added');
        })
        .catch((err) => { 
            loading.value = false;
        });
    }
</script>
<style scoped>
.mux-asset-track-form {
    flex: 1 1 auto;
}
.field-group {
    display: flex;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 24px;
}


.field-group .field {
    margin: 0 !important;
}

.field-group > div:not(:last-child) {
    flex: 1 1 auto;
}
.field-group > div:last-child {
    flex: 1 0 auto;
}
</style>