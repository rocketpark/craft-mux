<template>
    <div
        v-if="isOverDropZone || uploadState.uploading"
        class="dropzone-overlay"
    >
        <div class="dropzone-overlay_progress">
            <div class="dropzone-overlay_label" v-text="uploadState.uploadingFile != null ? uploadState.uploadingFile.name : 'Preparing to upload…'"></div>
            <progress-bar :value="uploadState.progress" />
        </div>
    </div>
</template>
<script setup>
import { defineProps, toRefs, watch } from 'vue';
import { uploadState } from './AssetsStore';
import ProgressBar from './ProgressBar.vue';
const props = defineProps({
    isOverDropZone: {
        type: Boolean,
        default: false,
    },
});
</script>
<style scoped>
.dropzone-overlay {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0px;
    left: 0px;
    bottom: 0px;
    right: 0px;
    padding-top: 100px;
    background-color: rgba(96, 125, 159, .10);
    z-index: 20;
    backdrop-filter: blur(4px);
}

.dropzone-overlay_progress {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 6px;
    width: 50%;
    text-align: center;
}

.dropzone-overlay_label {
    padding: 1px 8px;
    background-color: rgba(255,255,255,.3);
    border-radius: 20px;
    text-align: center;
}
</style>
