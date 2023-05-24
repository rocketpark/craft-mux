<template>
    <div class="progressbar">
        <div
            ref="bar"
            class="progressbar-inner"
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
<style scoped>
.progressbar {
    position: relative;
    margin-top: 16px;
    left: 0px;
    width: 100%;
}

.progressbar-inner {
    --progressbar-width: 0;
}

.progressbar:not(.pending) .progressbar-inner {
    width: var(--progressbar-width);
}
</style>
