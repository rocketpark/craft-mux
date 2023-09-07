<template>
    <div id="settings-muxPlaybackRestrictions-field" class="field" data-attribute="muxPlaybackRestrictions">
        <div id="settings-muxPlaybackRestrictions-instructions" class="instructions">
            <div>
                Playback Restrictions allows you to set additional rules for playing videos. You can set the domains/hostnames allowed to play your videos. For instance, viewers can play videos embedded on the <code>https://example.com</code> website when you set the Playback Restrictions with <code>example.com</code> as an allowed domain. Any Video requests from other websites are denied.
                List of domains allowed to play videos. Possible values are:
                <ul>
                    <li>[] Empty Array indicates deny video playback requests for all domains</li>
                    <li>["*"] A Single Wildcard * entry means allow video playback requests from any domain</li>
                    <li>["*.example.com", "foo.com"] A list of up to 10 domains or valid dns-style wildcards</li>
                </ul>
            </div>
        </div>
        <div></div>
        <div class="input ltr">
            <table id="settings-muxPlaybackRestrictions" class="editable fullwidth" style="">
                <colgroup>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th id="settings-muxPlaybackRestrictions-heading-1" scope="col" class="singleline-cell textual has-info">
                            Domains
                        </th>
                        <th id="settings-muxPlaybackRestrictions-heading-2" scope="col" class="checkbox-cell thin has-info">
                            Allow No Referrer
                        </th>
                        <th colspan="1" scope="colgroup">
                            <span class="visually-hidden">Row actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <mux-domain-restrictions-row v-for="(row, index, key) in state.restrictions" :key="key" :idx="index" :restriction="row" />
                </tbody>
            </table>
            <button
                type="button"
                :class="btnClass"
                aria-label="Add a restriction"
                style="opacity: 1; pointer-events: auto;"
                :aria-disabled="state.loading"
                tabindex="0" :disabled="state.loading" @click.prevent="addRow">
                <span v-if="!state.loading">Add a restriction</span>
                <div
                    v-if="state.loading"
                    class="spinner spinner-absolute"
                ></div>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { state, usePlaybackRestrictionsList, createPlaybackRestriction } from './DomainRestrictionsStore.js';
import MuxDomainRestrictionsRow from './MuxDomainRestrictionsRow.vue';

const playbackRestrictionModel = {
    id: null,
    created_at: null,
    updated_at: null,
    referrer: {
        allowed_domains: ['*'],
        allow_no_referrer: false,
    }
}

const btnClass = computed(() => {
    return state.loading ? 'btn dashed w-full loading' : 'btn dashed w-full add icon' ;
});

usePlaybackRestrictionsList();

const addRow = () => {
    createPlaybackRestriction({ referrer: playbackRestrictionModel.referrer }).then((data) => {
        Craft.cp.displayNotice(Craft.t('mux', 'Playback Restriction Created.'));
        usePlaybackRestrictionsList();
    });
};

</script>
<style scoped>
    .w-full {
        display: block;
        width: 100%;
    }
</style>
