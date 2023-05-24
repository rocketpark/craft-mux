<template>
    <div
        ref="dropZoneRef"
        class="dropzone-container" @keyup.esc="onEsc"
    >
        <div
            v-if="!loading"
            class="mux-grid"
        >
            <asset
                v-for="(item, index, key) in assets"
                :key="key"
                :asset="item"
            />
        </div>
        <div
            v-if="loading"
            class="mux-grid"
        >
            <div
                v-for="n in params.limit"
                :key="n + '-d'"
                class="pl-video"
            >
                <div class="aspect-video"></div>
            </div>
        </div>
        <drop-zone-overlay :is-over-drop-zone="isOverDropZone" />
    </div>
</template>
<script>
import {
    inject, onMounted, toRefs, ref,
} from 'vue';
import {
    state, uploadState, uploadFiles, useAssets,
} from './AssetsStore';
import Asset from './Asset';
import DropZoneOverlay from './DropZoneOverlay.vue';
import { useDropZone } from '@vueuse/core';
import ProgressBar from './ProgressBar.vue';

export default {
    components: {
        Asset,
        DropZoneOverlay,
        ProgressBar,
    },
    props: {
        siteId: {
            type: [Number, String],
            default: null,
        },
    },
    setup(props) {
        const emitter = inject('emitter');
        const dropZoneRef = ref();
        const filesData = ref([]);

        const onDrop = (files) => {
            if (files) {
                filesData.value = files.map((file) => {
                    return {
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        lastModified: file.lastModified,
                    };
                });
                uploadFiles(files).then(() => {
                    useAssets();
                });
            }
        };

        const onEsc = () => {
            emitter.emit('close-panel');
        };

        onMounted(async() => {
            const assets = await useAssets();
            emitter.on('mux-asset-track-deleted', () => {
                useAssets();
            });
            emitter.on('mux-asset-track-added', () => {
                useAssets();
            });
            emitter.on('onPage', () => {
                useAssets();
            });
        });

        const { isOverDropZone } = useDropZone(dropZoneRef, onDrop);

        return {
            ...toRefs(state),
            ...toRefs(uploadState),
            dropZoneRef,
            isOverDropZone,
            onEsc,
        };
    },
};
</script>
<style>
.dropzone-container {
    position: relative;
    min-height: calc(100vh - 300px);
}

.aspect-video {
    aspect-ratio: 16/9;
}

.mux-grid {
    display: grid;
    gap: 1.5rem;
    /*grid-template-rows: repeat(4, 1fr);*/
    grid-auto-rows: auto;
    grid-template-columns: repeat(1, 1fr);
}

@media screen and (min-width: 320px) {
    .mux-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media screen and (min-width: 768px) {
    .mux-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media screen and (min-width: 1280px) {
    .mux-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.pl-video {
    width: 100%;
}

.pulse {
    background: linear-gradient(245deg, #f8f8f8, #ededed);
    background-size: 150% 150%;

    animation: pulse 3s ease infinite;
}

@keyframes pulse {
    0% {
        background-position: 0% 70%;
    }

    50% {
        background-position: 100% 31%;
    }

    100% {
        background-position: 0% 70%;
    }
}
</style>
