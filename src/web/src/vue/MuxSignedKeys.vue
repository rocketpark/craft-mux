<template>
    <div id="settings-muxSignedKeys-field" class="field" data-attribute="muxSignedKeys">
        <div id="settings-muxSignedKeys-instructions" class="instructions">
            <div>
                Signing keys are used to sign JSON Web Tokens (JWTs) for securing certain requests, such as secure playback URLs and access to real-time viewer counts in Mux Data. One signing key can be used to sign multiple requests - you probably only need one active at a time. However, you can create multiple signing keys to enable key rotation, creating a new key and deleting the old only after any existing signed requests have expired.
            </div>
        </div>
        <br>
        <div class="input ltr">
            <table id="settings-muxSignedKeys" class="editable fullwidth" style="">
                <colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th id="settings-muxSignedKeys-heading-1" scope="col" class="singleline-cell textual has-info">
                            Private Key
                        </th>
                        <th id="settings-muxSignedKeys-heading-3" scope="col" class="singleline-cell has-info">
                            Key Id
                        </th>
                        <th id="settings-muxSignedKeys-heading-2" scope="col" style="width: 200px;" class="singleline-cell has-info">
                            Created At
                        </th>
                        <th colspan="1" scope="colgroup">
                            <span class="visually-hidden">Row actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <mux-signed-keys-row
                        v-for="(row, index, key) in state.signedkeys"
                        :key="key"
                        :idx="index"
                        :signedkey="row"
                    />
                </tbody>
            </table>
            <button
                type="button"
                :class="btnClass"
                aria-label="Add signed key"
                style="opacity: 1; pointer-events: auto;"
                :aria-disabled="state.loading"
                tabindex="0" :disabled="state.loading" @click.prevent="addRow"
            >
                <span v-if="!state.loading">Add signed key</span>
                <div v-if="state.loading" class="spinner spinner-absolute"></div>
            </button>
        </div>
    </div>
</template>

<script setup>
// eslint-disable camelcase
import { computed, ref } from 'vue';
import { state, useSignedKeysList, createSignedKey } from './SignedKeysStore.js';
import MuxSignedKeysRow from './MuxSignedKeysRow.vue';


const signedKeyModel = {
    id: null,
    key_id: null,
    created_at: null,
    private_key: null,
};

const btnClass = computed(() => {
    return state.loading ? 'btn dashed mux-w-full loading' : 'btn dashed mux-w-full add icon';
});

useSignedKeysList();

const addRow = () => {
    createSignedKey({ referrer: signedKeyModel.referrer }).then((data) => {
        Craft.cp.displayNotice(Craft.t('mux', 'Private Key Created.'));
        useSignedKeysList();
    });
};

</script>