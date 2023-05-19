<template>
    <div class="mux-tracks">
        <slot name="heading"></slot>
        <div class="mux-tracks-list" v-if="state.asset != null && !state.loading">
            <div class="mux-track" v-for="track in state.asset.tracks" :key="track.id">
                <asset-track :track="track" :asset-id="state.asset_id"></asset-track>
            </div>
        </div>

        <mux-asset-track-form :asset-id="state.asset_id"></mux-asset-track-form>
    </div>
</template>
<script setup>
    import { defineProps, inject, ref } from 'vue';
    import { state, useAssetById } from "./AssetsStore";
    import MuxAssetTrackForm from './MuxAssetTrackForm.vue';
    import AssetTrack from './AssetTrack.vue';

    const emitter = inject('emitter');
    
    const props = defineProps({
        muxAsset: {
            type: Object,
            required: true
        }
    });

    useAssetById(props.muxAsset.id);

    emitter.on('mux-asset-track-added', (track) => {
        useAssetById(props.muxAsset.id);
    });

    emitter.on('mux-asset-track-deleted', (track) => {
        useAssetById(props.muxAsset.id);
    });

</script>
<style>
.mux-video {
        aspect-ratio:16/9; 
        max-width:100%;
    }

    .mux-tracks {
        margin-top: 16px;
    }

    @media (min-width: 1440px) {
        .mux-video {
            max-width: 50%;
        }
    }

    .mux-tracks-list {
        margin-bottom: 26px;
    }
</style>