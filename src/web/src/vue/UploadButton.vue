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
        type="button" 
        class="btn" 
        data-icon="upload" 
        style="position: relative; overflow: hidden;"
        :class="[isOverDropZone ? 'dashed' : 'submit']"
        aria-label="Upload video(s)"
        @click.stop.prevent="openFileSelectWindow"
        ref="dropZoneRef" 
        >
            Upload files</button>
</template>

<script setup>
    import { ref } from 'vue';
    import { useDropZone } from '@vueuse/core';
    import { uploadState, uploadFiles, useAssets } from './AssetsStore';
    import { preprocessFiles } from './FileHelpers.js';

    const props = defineProps({
        view: {
            type: String,
            default: 'grid' //grid|element-index
        }
    })

    const dropZoneRef = ref(null);
    const filesData = ref([]);
    const fsFileInput = ref(null);

    const onDrop = (files) => {
        if (files) {
            preprocessFiles(files).then(() => {        
                filesData.value = files.map(file => ({
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    lastModified: file.lastModified,
                }));

                uploadFiles(files).then(() => {
                    if(props.view === 'grid') {
                        useAssets();
                    } else if(props.view === 'element-index') {
                        if(window.Craft) {
                            setTimeout(() => {
                                window.Craft.elementIndex.updateElements();
                            }, 300);
                        }
                    }
                });
            }).catch(err => {
                console.error(err);
            });
        }
    };

    const { isOverDropZone } = useDropZone(dropZoneRef, onDrop);

    const onFileSelect = function (evt) {
        onDrop(Array.from(evt.target.files));
    }

    const openFileSelectWindow = function (evt) {
        fsFileInput.value.click();
    }
</script>