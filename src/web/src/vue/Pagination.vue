<template>
    <div id="count-container" class="light">
        <div class="flex pagination">
            <nav class="flex" aria-label="mux asset pagination">
                <button 
                    role="button" 
                    class="page-link prev-page" 
                    :class="{ 'disabled': prevBtnDisabled }" 
                    :disabled="prevBtnDisabled"
                    title="Previous Page"
                    @click.prevent="prevPage()"></button>
                <button 
                    role="button" 
                    class="page-link next-page" 
                    :class="{ 'disabled': nextBtnDisabled }" 
                    :disabled="nextBtnDisabled" 
                    title="Next Page"
                    @click.prevent="nextPage()"></button>
            </nav>
            <div class="page-info"><span v-text="page"></span>–<span v-text="pages"></span> of <span v-text="state.total"></span> mux assets</div>
        </div>
    </div>
</template>
<script setup>
    import { computed, inject } from 'vue';
    import { setPageParams, state } from './AssetsStore.js';

    const emitter = inject('emitter');

    const page = computed(() => pages.value === 0 ? 0 : state.params.page);
    const pages = computed(() => Math.ceil(state.total / state.params.limit));
    const prevBtnDisabled = computed(() => state.params.page === 1 || pages.value === 0);
    const nextBtnDisabled = computed(() => state.params.page === pages.value || pages.value === 0);

    const prevPage = () => {
        if(state.params.page > 1) {
            setPageParams({ page: state.params.page - 1 });
            emitter.emit('onPage');
        }
    };

    const nextPage = () => {
        if(state.params.page < pages.value) {
            setPageParams({ page: state.params.page + 1 });
            emitter.emit('onPage');
        }
    };

</script>