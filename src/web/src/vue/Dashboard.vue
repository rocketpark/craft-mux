<script>
import DropZoneOverlay from './DropZoneOverlay.vue';
import UploadButton from './UploadButton.vue';

export default {
    name: 'MuxDashoard',
    components: {
        DropZoneOverlay,
        UploadButton,
    },
};
</script>
<style>
.mux-thumb-figure {
    position: relative;
    display: flex;
    align-items: center;
    overflow: hidden;
    border-radius: 5px;
    aspect-ratio: 16 / 9;
    min-width: 100px;
    margin-right: 14px;
}

.tableview .mux-thumb-figure {
    min-width: 100px;
    max-width: 100px;
}

.thumbview .mux-thumb-figure {
    min-width: 140px;
    max-width: 140px;
}

.card-grid .mux-thumb-figure {
    min-width: 200px;
    max-width: 200px;
}

.mux-thumb-figure > .mux-base-thumb {
    display: block;
    visibility: visible;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    aspect-ratio: 16 / 9;
}

.mux-thumb-figure > .mux-animated-thumb {
    display: hidden;
    visibility: hidden;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    aspect-ratio: 16 / 9;
}

.mux-thumb-figure:hover .mux-base-thumb {
    visibility: hidden;
    display: none;
}

.mux-thumb-figure:hover .mux-animated-thumb {
    visibility: visible;
    display: block;
}
</style>
