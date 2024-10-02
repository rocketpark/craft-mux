<template>
    <input
        ref="fsFileInput"
        type="file"
        multiple="multiple"
        name="assets-upload"
        style="display: none; visibility: hidden;"
        tabindex="-1"
        @change="onFileSelect"
    >
    <button
        ref="dropZoneRef"
        type="button"
        class="btn"
        data-icon="upload"
        style="position: relative; overflow: hidden;"
        :class="[isOverDropZone ? 'dashed' : 'submit']"
        aria-label="Upload video(s)"
        @click.stop.prevent="openFileSelectWindow"
    >
        Upload video(s)
    </button>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useDropZone } from '@vueuse/core';
import { uploadFiles, uploadState, useAssets } from './AssetsStore';
import { preprocessFiles } from './FileHelpers.js';

const props = defineProps({
    view: {
        type: String,
        default: 'grid', // grid|element-index
    },
});

const dropZoneRef = ref(null);
const filesData = ref([]);
const fsFileInput = ref(null);

const onDrop = (files) => {
    if (files) {
        preprocessFiles(files).then(() => {
            filesData.value = files.map((file) => {
                return {
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    lastModified: file.lastModified,
                };
            });

            uploadFiles(files).then(() => {
                if (props.view === 'grid') {
                    useAssets();
                } else if (props.view === 'element-index') {
                    if (window.Craft) {
                        setTimeout(() => {
                            window.Craft.elementIndex.updateElements();
                        }, 300);
                    }
                }
            });
        }).catch((err) => {
            console.error(err);
        });
    }
};

const { isOverDropZone } = useDropZone(dropZoneRef, onDrop);

const onFileSelect = function(evt) {
    onDrop(Array.from(evt.target.files));
};

const openFileSelectWindow = function(evt) {
    fsFileInput.value.click();
};


/** Watch for upload state changes and update the drop zone accordingly */
const dropZone = document.querySelector('.mux-dropzone-overlay');
const label = dropZone.querySelector('.mux-dropzone-overlay_label');
const progressBar = dropZone.querySelector('.mux-progressbar-inner');
label.textContent = 'Drop files here or click upload button';

watch(() => uploadState, (state) => {
    if (state.uploading) {
        dropZone.classList.add('active-upload');
        label.textContent = state.uploadingFile != null ? state.uploadingFile.name : 'Preparing to upload…';
        progressBar.style.setProperty('--progressbar-width', `${state.progress}%`);
    } else {
        label.textContent = 'Drop files here or click upload button';
        dropZone.classList.remove('mux-dropzone-overlay--dragover', 'active-dropzone', 'active-upload');
    }
},{ deep: true });

let dragCounter = 0;

document.body.addEventListener('dragover', (event) => {
    event.preventDefault(); // Necessary to allow dropping
    dropZone.classList.add('active-dropzone'); // Ensure drop zone is ready to accept drops
});

dropZone.addEventListener('dragenter', (event) => {
    event.preventDefault();
    dragCounter++;
    if (dragCounter === 1) {
        dropZone.classList.add('mux-dropzone-overlay--dragover');
    }
});

dropZone.addEventListener('dragleave', (event) => {
    event.preventDefault();
    dragCounter = Math.max(0, dragCounter - 1); // Safeguard to avoid negative counter
    if (dragCounter === 0) {
        dropZone.classList.remove('mux-dropzone-overlay--dragover', 'active-dropzone');
    }
});

dropZone.addEventListener('drop', (event) => {
    event.preventDefault();
    dragCounter = 0; // Reset counter after drop
    dropZone.classList.remove('mux-dropzone-overlay--dragover', 'active-dropzone');
    onDrop(Array.from(event.dataTransfer.files));
});

</script>
