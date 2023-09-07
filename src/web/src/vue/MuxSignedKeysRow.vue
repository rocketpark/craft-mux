<!-- eslint-disable camelcase -->
<template>
    <tr :data-id="idx">
        <td class="singleline-cell textual">
            <div style="display:flex; justify-content: space-between; align-items:center; gap: 16px; text-align: left; padding: 3px 8px; width: 100%;">
                <div class="" style="font-family: monospace; user-select: all; word-break: break-all;" v-text="truncate(signedkey.private_key, 57)"></div>
                <button
                    class="copy icon"
                    style="justify-self: flex-end;"
                    title="Copy Private Key"
                    @click.prevent="copyPrivateKey"
                >
                    <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 384 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M280 64h40c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128C0 92.7 28.7 64 64 64h40 9.6C121 27.5 153.3 0 192 0s71 27.5 78.4 64H280zM64 112c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H304v24c0 13.3-10.7 24-24 24H192 104c-13.3 0-24-10.7-24-24V112H64zm128-8a24 24 0 1 0 0-48 24 24 0 1 0 0 48z"/></svg>
                </button>
            </div>
        </td>
        <td class="singleline-cell textual">
            <div class="" style="font-family: monospace; padding: 3px 8px; text-align: left; user-select: all; word-break: break-all;" v-text="signedkey.key_id"></div>
        </td>
        <td class="singleline-cell" style="width: 200px;">
            <div class="" v-text="unixTimestampToFormattedDate(signedkey.created_at)"></div>
        </td>
        <td class="thin action">
            <button class="delete icon" title="Delete" type="button" :disabled="state.loading" :aria-label="`Delete signed key ${idx + 1}`" @click.prevent="deleteRow"></button>
        </td>
    </tr>
</template>
<script setup>
import { computed, ref, toRaw } from 'vue';
import { state, deleteSignedKey, useSignedKeysList } from './SignedKeysStore.js';

const props = defineProps({
    idx: {
        type: Number,
        default: null,
    },
    signedkey: {
        type: Object,
        // eslint-disable-next-line vue/require-valid-default-prop
        default: {
            id: null,
            key_id: null,
            created_at: null,
            private_key: null,
        },
    },
});

const truncate = (str, maxLen) => {
    if (str.length > maxLen) {
        return `${str.substring(0, maxLen)}…`;
    }
    return str;
};

const deleteRow = () => {
    deleteSignedKey(props.signedkey.key_id).then((data) => {
        Craft.cp.displayNotice(Craft.t('mux', 'Private Key Deleted.'));
        useSignedKeysList();
    });
};

const copyPrivateKey = () => {
    navigator.clipboard.writeText(props.signedkey.private_key).then(() => {
        Craft.cp.displayNotice(Craft.t('mux', 'Private Key Copied.'));
    });
};

const unixTimestampToFormattedDate = (unixTimestamp) => {
    const date = new Date(unixTimestamp * 1000); // Convert from seconds to milliseconds

    // Use browser's locale for formatting
    const localeDateString = date.toLocaleDateString();
    const localeTimeString = date.toLocaleTimeString();

    const formattedDateTime = `${localeDateString} ${localeTimeString}`;

    return formattedDateTime;
};


</script>
