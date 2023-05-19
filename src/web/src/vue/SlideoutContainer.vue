<template>
    <div class="mux-slideout-container" @keyup.esc="onEsc">
        <transition name="slide-fade-right">
            <asset-track-slideout v-if="stack.includes('asset-track-panel')"></asset-track-slideout>
        </transition>
        <transition name="slide-fade-right">
            <asset-slideout v-if="stack.includes('asset-panel')"></asset-slideout>
        </transition>
    </div>
</template>
<script setup>
    import { inject, ref, onMounted, toRaw } from 'vue';
    import AssetSlideout from './AssetSlideout.vue';
    import AssetTrackSlideout from './AssetTrackSlideout.vue';

    const emitter = inject('emitter');
    const shade = document.querySelector('.modal-shade');
    const stack = ref([]);

    const onEsc = () => {
        emitter.emit("close-panel");
    };

    onMounted(() => {
        emitter.on('open-panel', (id) => {
            stack.value.push(id);
            if(shade !== undefined) {
                shade.style.display = 'block';
            }
        });

        emitter.on('close-panel', () => {
            stack.value.pop();
            if(shade !== undefined && stack.value.length === 0) {
                shade.style.display = 'none';
            }
        });

        emitter.on('click-outside', (id) => {
            switch (id) {
                case 'asset-panel':
                    if (stack.value.length === 1) {
                        emitter.emit("close-panel");
                    }
                    break;
                case 'asset-track-panel':
                    if (stack.value.length === 2) {
                        emitter.emit("close-panel");
                    }
                    break;
                default:
                    // do nothing
            }
        });
    });

</script>
<style scoped>
.mux-slideout-container {
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    pointer-events: none;
}

.mux-slideout-container > .mux-slideout:not(.slide-fade-right-enter-active,.slide-fade-right-leave-active,.slide-fade-right-enter-from,.slide-fade-right-leave-to,.slide-fade-right-enter-to,.slide-fade-right-leave-from) {
    transform: translateX(0px);
    transition: all 0.2s ease-out;
}

.mux-slideout-container > .mux-slideout + .mux-slideout {
    transform: translateX(-75px);
    transition: all 0.3s ease-out;
}

.slide-fade-right-enter-active,
.slide-fade-right-leave-active {
  transition: all 0.3s ease-in-out;
}

.slide-fade-right-enter-from,
.slide-fade-right-leave-to {
  opacity: 0;
  transform: translateX(100px);
}

.slide-fade-right-enter-to,
.slide-fade-right-leave-from {
  opacity: 1;
  transform: translateX(0);
}
</style>