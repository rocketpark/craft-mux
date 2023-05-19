<template>
    <div class="mux-listbox-container">
        <button class="btn mux-tab-btn" role="button" type="button" @click.prevent="isMenuOpen = !isMenuOpen"></button>
        <div class="menu" role="listbox" ref="listbox" v-if="isMenuOpen">
            <ul role="group">
                <li role="option" aria-selected="false">
                    <a tabindex="-1" @click.prevent="emit('addClosedCaptions')">Add Closed Captions</a>
                </li>
            </ul>
            <hr>
            <ul role="group">
                <li role="option" aria-selected="false">
                    <a class="error" tabindex="-1" @click.prevent="emit('deleteAsset')">Delete</a>
                </li>
            </ul>
        </div>
    </div>
</template>
<script setup>
    import { defineEmits, inject, ref } from 'vue';
    import { onClickOutside } from '@vueuse/core';
    
    const emit = defineEmits(['addClosedCaptions', 'deleteAsset']);
    const isMenuOpen = ref(false);
    const listbox = ref(null);

    // onClickOutside(listbox, (event) => {
    //     isMenuOpen.value = false;
    // });

</script>
<style>
.mux-listbox-container {
    position: relative;
}

.mux-listbox-container > .menu {
    position: absolute;
    display: block !important;
    top: 100%;
    left: 0;
    max-width: fit-content;
    min-height: fit-content;
}

.mux-tab-btn {
    position: relative;
    padding: 4px 9px;
    border-radius: 3px;
    color: #33475b;
    font-size: 15px;
    margin: 10px 7px 10px auto;
    border: 1px solid #d7dce2;
    background-image: linear-gradient(#fff,#fafafa);
}
.mux-tab-btn:after {
    content: "settings";
    position: relative;
    top: 1px;
    font-family: Craft,sans-serif;
}
</style>