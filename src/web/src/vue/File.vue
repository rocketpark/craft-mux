<template>
    <div class="mux-field-main flex-grow">
        <div class="mux-field">
            <figure class="mux-player-figure">
                <mux-player
                    :playback-id="file.playback_ids[0].id"
                    metadata-video-id="props.file.id"
                    stream-type="on-demand"
                    primary-color="#ffffff"
                    secondary-color="rgba(0,0,0,.3)"
                />
                <!-- <img :src="thumb" /> -->
            </figure>

            <div class="mux-asset-menu">
                <button @click.prevent="menuOpen = !menuOpen">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path path="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
                <div class="menu menu--disclosure" v-if="menuOpen" ref="muxAssetMenu">
                    <ul>
                        <li>
                            <a href="#" class="" @click.prevent="trackFormOpen = !trackFormOpen">
                                <div class="flex-grow">
                                    <div class="flex flex-nowrap">
                                        <PlusCircleIcon class="pluscircleicon" /> <span>Add Language Track</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="error" alt="Delete Mux Asset" @click.prevent="onDeleteAsset">
                                <div class="flex-grow">
                                    <div class="flex flex-nowrap">
                                        <XCircleIcon class="xcircleicon" /> <span>Remove Asset</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <mux-asset-track-form
            v-if="trackFormOpen"
            :asset-id="file.id"
            @mux-asset-track-added="onTrackAdded"
        />
    </div>
</template>
<script>
import { ref, reactive } from 'vue';
import MuxAssetTrackForm from './MuxAssetTrackForm.vue';
import '@mux/mux-player';
import { onClickOutside } from '@vueuse/core';
import { XCircleIcon, EllipsisHorizontalCircleIcon, PlusCircleIcon } from '@heroicons/vue/24/solid';

export default {
    props: {
        file: {
            type: Object,
            default: {
                asset_id: null, 
                playback_ids: [] 
            }
        }
    },
    emits: ['mux-asset-deleted'],
    setup(props, { emit }) {
        const menuOpen = ref(false);
        const trackFormOpen = ref(false);
        const muxAssetMenu = ref(null);

        onClickOutside(muxAssetMenu, (event) => { menuOpen.value = false;});
        const deleteAssetById = function (_id) {
            return new Promise((resolve, reject) => {
                let body = { id: _id };
                let csrfKey = window.Craft.csrfTokenName;
                body[csrfKey] = window.Craft.csrfTokenValue;

                fetch('/actions/mux/assets/delete-asset-by-id', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(body),
                }).then((res) => {
                    if (!res.ok) {
                        throw new Error(`HTTP error ${res.status}`);
                    }
                    return res.json();
                }).then((json) => {
                    resolve(json);
                }).catch((err) => {
                    console.log(err);
                });
            });
        }

        const onDeleteAsset = function(evt) {
            menuOpen.value = false;
            deleteAssetById(props.file.id).then((res) => {
                emit('mux-asset-deleted', props.file);
            });
        };

        const onTrackAdded = function(data) {

        };

        // const thumb = ref(`https://image.mux.com/${props.file.playback_ids[0].id}/thumbnail.webp?width=250&height=141&fit_mode=smartcrop`);

        return {
            menuOpen,
            muxAssetMenu,
            onDeleteAsset,
            onTrackAdded,
            trackFormOpen,
        };
    },
    components: {
        EllipsisHorizontalCircleIcon,
        PlusCircleIcon,
        XCircleIcon,
        MuxAssetTrackForm
    }
}
</script>
