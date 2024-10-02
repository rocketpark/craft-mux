<template>
    <div class="mux-progressbar">
        <div
            ref="bar"
            class="mux-progressbar-inner"
            :style="`--progressbar-width:${value}%;`"
        ></div>
    </div>
</template>
<script>
import { ref, watch } from 'vue';
export default {
    props: {
        value: {
            type: Number,
            default: 0,
        },
    },
    setup(props) {
        const bar = ref();

        return {
            bar,
        };
    },
};
</script>

