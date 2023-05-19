<template>
    <button class="mux-asset"
        @click="openPanel">
        <img :src="imgSrc" />
        <div class="mux-asset_heading">
            <h5 v-text="title"></h5>
        </div>
    </button>
</template>
<script setup>
import { computed, inject, ref } from 'vue';
import { setSelected } from './AssetsStore';
const props = defineProps({
    asset: Object
});

const emitter = inject('emitter');

const title = computed(() => { 
    return props.asset.title != "" ? props.asset.title : props.asset.id;
});
let imgSrc = ref(`https://image.mux.com/${props.asset.playback_ids[0].id}/thumbnail.webp?width=400&height=225&fit_mode=smartcrop`);

const openPanel = () => {
    setSelected(props.asset);
    emitter.emit('open-panel', 'asset-panel');
}

defineExpose({
    asset: props.asset
});

</script>
<style>
.mux-asset {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    background: #ffffff;
    box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
    border-radius: 3px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

.mux-asset:hover {
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
}

.mux-asset:focus {
    outline-color: rgba(0, 0, 0, 0.2) !important;
    outline-width: 1px;
}

.mux-asset_heading {
    text-align: left;
}
.mux-asset_heading h5 {
    max-width: 270px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.mux-asset>img {
    width: 100%;
}
</style>