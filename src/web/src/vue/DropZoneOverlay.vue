<template>
    <div
        v-if="isOverDropZone || uploadState.uploading"
        class="mux-dropzone-overlay"
    >
        <div class="mux-dropzone-overlay_progress">
            <div class="mux-dropzone-overlay_label" v-text="uploadState.uploadingFile != null ? uploadState.uploadingFile.name : 'Preparing to upload…'"></div>
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
