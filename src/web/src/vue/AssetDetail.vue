<template>
    <div id="header-container">
        <header id="header">
            <div id="page-title" class="flex">
                <slot name="page-title"></slot>
            </div>

            <div id="action-buttons" class="flex">
                <slot name="action-buttons"></slot>

                <div class="btngroup">
                    <button class="btn submit loading" @click.prevent="updateMuxAsset">
                        <span v-if="!loading">Save</span>
                        <div v-if="loading" class="spinner"></div>
                    </button>
                    <button
                        type="button" class="btn submit menubtn" aria-label="More actions" role="combobox" aria-controls="menu.MuxAsset" aria-haspopup="listbox"
                        aria-expanded="false"
                    ></button>
                    <div id="menu.MuxAsset" class="menu" data-align="right" role="listbox">
                        <ul role="group">
                            <li id="menu.MuxAsset-aria-option-1" role="option" aria-selected="false">
                                <a id="menu.MuxAsset-option-1" class="formsubmit error" tabindex="-1" @click.prevent="deleteAsset">
                                    Delete MUX Asset
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header><!-- #header -->
    </div>

    <div id="main-content" class="">
        <div id="content-container">
            <div id="content" class="content-pane">
                <div id="title-field" class="field" data-attribute="title">
                    <div class="heading">
                        <label id="title-label" for="title">Title</label>
                    </div>
                    <div class="input ltr">
                        <input
                            id="title" v-model="title" type="text" class="text fullwidth" name="title" autocomplete="off"
                            dir="ltr" aria-labelledby="title-label"
                        >
                    </div>
                </div>

                <div class="">
                    <div v-if="asset.playbackSecure" class="mux-video">
                        <mux-player
                            primary-color="#ffffff"
                            stream-type="on-demand"
                            :metadata-video-id="asset.asset_id"
                            :playback-id="asset.playback_ids[0].id"
                            :playback-token="asset.tokens[0]"
                            :thumbnail-token="asset.tokens[0]"
                            :metadata-video-title="asset.title"
                        />
                    </div>
                    <div v-else class="mux-video">
                        <mux-player
                            primary-color="#ffffff"
                            stream-type="on-demand"
                            :metadata-video-id="asset.asset_id"
                            :playback-id="asset.playback_ids[0].id"
                            :metadata-video-title="asset.title"
                        />
                    </div>
                    <hr>
                    <div class="mux-tracks">
                        <h2>Tracks</h2>
                        <div class="mux-tracks-list">
                            <div v-for="track in asset.tracks" :key="track.id" class="mux-track">
                                <asset-track :track="track" :asset-id="asset.asset_id" />
                            </div>
                        </div>

                        <mux-asset-track-form :asset-id="asset.asset_id" />
                    </div>
                    <!-- <div class="">
                        <h4>Playback ID</h4>
                        {% for item in muxAsset.playback_ids %}
                            <div class="flex">
                                {{ forms.copytext({
                                    id: 'mux-asset',
                                    buttonId: 'copy-btn',
                                    value: item.id,
                                    size: 54,
                                }) }}
                            </div>
                        {% endfor %}
                    </div> -->
                </div>
            </div>
        </div><!-- #content-container -->

        <div id="details-container">
            <div id="details">
                <div class="details">
                    <slot name="details"></slot>
                </div>
            </div>
        </div>
    </div><!-- #main-content -->
</template>

<script setup>
import {
    defineProps, inject, onBeforeUnmount, onMounted, ref, watch,
} from 'vue';
import {
    deleteAssetById, removeAssetById, state, updateAsset, useAssetById,
} from './AssetsStore';
import MuxAssetTrackForm from './MuxAssetTrackForm.vue';
import AssetTrack from './AssetTrack.vue';
import '@mux/mux-player';
const props = defineProps({
    muxAsset: {
        type: Object,
        required: true,
    },
});

const emitter = inject('emitter');
const asset = ref(props.muxAsset);
const loading = ref(false);
const title = ref(asset.value.title);

useAssetById(props.muxAsset.id);

const updateMuxAsset = () => {
    loading.value = true;
    // Get page screen title to update directly.
    const screenTitle = document.querySelector('.screen-title');
    const asset = {
        id: props.muxAsset.id,
        asset_id: props.muxAsset.asset_id,
        passthrough: title.value,
        title: title.value,
    };

    updateAsset(asset).then((response) => {
        // Update the screen title directly since we don't refresh the page.
        screenTitle.innerText = title.value;
        screenTitle.setAttribute('title', title.value);

        useAssetById(props.muxAsset.id);
        loading.value = false;
    });
};

const deleteAsset = () => {
    loading.value = true;
    deleteAssetById(props.muxAsset.id).then((response) => {
        if (response.success) {
            document.location.href = '/admin/mux/assets';
        }
        loading.value = false;
    });
};

const handleKeyDown = (event) => {
    if (event.metaKey && event.keyCode === 83) {
        event.preventDefault();
        updateMuxAsset();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyDown);
});

emitter.on('mux-asset-track-added', (track) => {
    useAssetById(props.muxAsset.id);
});

emitter.on('mux-asset-track-deleted', (track) => {
    useAssetById(props.muxAsset.id);
});

watch(() => { return state.asset; }, (newAsset) => {
          asset.value = newAsset;
      },
      { deep: true });

</script>

<style scoped>
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
