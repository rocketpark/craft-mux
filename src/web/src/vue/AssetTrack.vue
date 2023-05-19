<template>
    <div class="mux-track">
        <div class="mux-track-item" v-if="track.type == 'text'">
            <span><DocumentTextIcon class="icon" /></span> 
            <span>Text</span> 
            <span>{{  track.name }}</span>
            <span v-if="track.closed_captions">subtitles</span> 
            <span>({{ track.language_code }})</span>
        </div>

        <div class="mux-track-item" v-if="track.type == 'audio'">
            <span><MicrophoneIcon class="icon" /></span> 
            <span>Audio</span> 
            <span>{{ track.max_channel_layout }}</span>
        </div>

        <div class="mux-track-item" v-if="track.type == 'video'">
            <span><VideoCameraIcon class="icon" /></span> 
            <span>Video</span> 
            <span>{{ track.max_height }}p</span>
        </div>
        
        <button class="error" @click.prevent.stop="deleteTrack()" v-if="track.type == 'text'">
            <XCircleIcon class="xcircleicon" v-if="!loading" />
            <div class="spinner" v-if="loading"></div>
            <span class="sr-only">Delete Track {{ track.name  }}</span>
        </button>
    </div>
</template>
<script setup>
    import { defineProps, inject, ref } from 'vue';
    import { MicrophoneIcon, VideoCameraIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
    import { XCircleIcon } from '@heroicons/vue/24/solid';
    import { deleteAssetTrack } from "./AssetsStore.js";

    const emitter = inject('emitter');
    const props = defineProps({
        assetId: {
            type: [String, Number],
            required: true
        },
        track: {
            type: Object,
            required: true
        }
    });

    const loading = ref(false);

    const deleteTrack = () => {
        loading.value = true;
        deleteAssetTrack(props.assetId, props.track.id)
        .then(() => { 
            loading.value = false;
            emitter.emit('mux-asset-track-deleted');
        })
        .catch((err) => { 
            loading.value = false;
        });
    };

</script>
<style scoped>
    .mux-track {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px;
        border-bottom: 1px solid var(--hairline-color);
    }

    .mux-track-item {
        display: flex;
        align-items: center;
        gap: 16px;
        width: 100%;
    }

    .mux-track:hover {
        background-color: var(--hairline-color);
    }

    .mux-track:focus-within {
        border-color: rgba(0, 0, 0, 0.1);
    }

    .mux-track > span {
        line-height: 1;
    }
    .xcircleicon{
        color: currentColor;
        height: 24px;
        width: 24px;
    }
    .icon {
        height: 20px;
        width: 20px;
    }

    .sr-only {
        visibility: hidden;
        display:none;
    }
</style>