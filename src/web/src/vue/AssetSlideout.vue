<template>
    <form class="mux-slideout" ref="slideout" @submit.prevent="updateMuxAsset">
        <input type="hidden" name="assetId" :value="asset.id"/>
        <header>
            <h3>Edit Asset</h3>
            <button class="mux-slideout-close" @click.prevent="closePanel()"></button>
        </header>
        <section>
            <div class="" v-if="asset != null">
                <div >
                    <mux-player class="mux-player" :playback-id="asset.playback_ids[0].id" :metadata-video-id="asset.id"
                        :metadata-video-title="title" primary-color="#ffffff"
                        stream-type="on-demand" />
                </div>
                <div class="field" data-type="text" data-complete="true">
                    <div class="field field-wrapper">
                        <div class="heading">
                            <label id="label-muxAssetId" for="muxAssetId" class="field-label">
                                Title
                            </label>
                            <!-- <div id="help-input_9" class="instructions">
                                <p>Maximum length 255 characters.</p>
                            </div> -->
                        </div>
                        <div class="input">
                            <input autocomplete="off" required="" type="text" class="text fullwidth" name="assetTitle"
                                id="muxAssetId" aria-describedby="assetTitle" v-model="title" maxlength="255" />
                        </div>
                    </div>
                </div>
                <div>
                    <h4>Closed Captions</h4>
                    <button class="btn" @click.prevent="emitter.emit('open-panel', 'asset-track-panel')">Edit</button>
                </div>
            </div>
        </section>
        <footer>
            <div class="mux-buttons">
                <div>
                    <a class="error" tabindex="-1" @click.prevent.stop="deleteAsset()">Delete</a>
                </div>
                <div class="mux-buttons-group">
                    <button class="btn" @click.prevent="closePanel()">Close</button>
                    <button class="btn submit" v-if="!loading">Apply</button>
                    <div class="spinner" v-if="loading"></div>
                </div>
            </div>
        </footer>
    </form>
</template>

<script setup>
import { computed, inject, ref } from "vue";
import { deleteAssetById, removeAssetById, setSelected, state, updateAsset } from "./AssetsStore";
import "@mux/mux-player";
import { onClickOutside } from '@vueuse/core';

const emitter = inject("emitter");
const slideout = ref(null);
const asset = state.selected;
const title = ref(asset.title);
const loading = ref(false);

const updateMuxAsset = () => {
    loading.value = true;
    let newAsset = asset;
    asset.passthrough = title.value;
    asset.title = title.value;

    updateAsset(newAsset).then((response) => {
        loading.value = false;
    });
};

const deleteAsset = () => {
    loading.value = true;
    deleteAssetById(asset.id).then((response) => {

        if(response.success) {  
            removeAssetById(asset.id);
            emitter.emit("close-panel");
        }

        loading.value = false;
    });
};

const tracks = computed(() => {
    return asset.tracks.filter((track) => {
        return track.type === "text";
    });
});

onClickOutside(slideout, (evt) => {
    emitter.emit("click-outside", 'asset-panel');
});

const closePanel = () => {
    setSelected(null);
    emitter.emit("close-panel");
};
</script>

<style scoped>
.mux-slideout {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    box-shadow: 0 25px 100px #1f293380;
    border-radius: 5px;
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0px;
    right: 0px;
    pointer-events: all;
    z-index: 101;
}

@media screen and (min-width: 768px) {
    .mux-slideout {
        width: 70%;
    }
}

@media screen and (min-width: 1024px) {
    .mux-slideout {
        width: 50%;
    }
}

@media screen and (min-width: 1440px) {
    .mux-slideout {
        width: 40%;
    }
}

.mux-slideout>header {
    width: 100%;
    background-color: #f3f7fc;
    box-shadow: inset 0 -1px #33404d1a;
    padding: 10px 24px;
    display: flex;
    align-items: center;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
}

.mux-slideout>header>h3 {
    margin: 0;
    padding: 0;
    font-weight: 600;
    font-size: 15px;
    line-height: 30px;
}

.mux-slideout>header>.mux-slideout-close {
    width: 20px;
    height: 20px;
    margin-left: auto;
    cursor: pointer;
    background-size: 20px 20px;
    background-repeat: no-repeat;
    transition: all 0.3s ease;
    background-image: url("data:image/svg+xml,%3Csvg aria-hidden='true' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%233f4d5a' d='M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z'%3E%3C/path%3E%3C/svg%3E");
    opacity: 0.6;
}

.mux-slideout>header>.mux-slideout-close:hover {
    opacity: 1;
}

.mux-slideout>section {
    height: 100%;
    position: relative;
    overflow: auto;
    padding: 24px;
    flex: 1 1 auto;
}

.mux-slideout>footer {
    width: 100%;
    background-color: #e4edf6;
    box-shadow: inset 0 1px #33404d1a;
    padding: 10px 24px;
    border-bottom-left-radius: 5px;
    border-bottom-right-radius: 5px;
    align-self: self-end;
}

.mux-player {
    aspect-ratio: 16/9;
}

.mux-buttons {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.mux-buttons > .mux-buttons-group {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}
</style>
