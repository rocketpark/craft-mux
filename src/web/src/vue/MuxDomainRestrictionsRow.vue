<template>
    <tr :data-id="idx">
        <td class="undefined singleline-cell textual">
            <textarea
                v-model="allowedDomains"
                :name="`[referrer][${idx}][allowed_domains]`"
                rows="1"
                :disabled="state.loading"
                @input="updateRow"
                @keyup="updateRow"
            ></textarea>
        </td>
        <td class="undefined checkbox-cell">
            <div class="checkbox-wrapper">
                <input type="hidden" :name="`[referrer][${idx}][allow_no_referrer]`" :value="allow_no_referrer">
                <input
                    id="checkbox"
                    v-model="allow_no_referrer"
                    type="checkbox"
                    value="true"
                    class="checkbox"
                    :name="`[referrer][${idx}][allow_no_referrer]`"
                    @change="updateRow"
                >
                <label for="checkbox"></label>
            </div>
        </td>
        <td class="thin action">
            <button
                class="delete icon"
                title="Delete"
                :disabled="state.loading"
                :aria-label="`Delete restriction ${idx + 1}`"
                type="button"
                @click.prevent="deleteRow"
            ></button>
        </td>
    </tr>
</template>
<script setup>
import { computed, ref, toRaw } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { state, deletePlaybackRestriction, updatePlaybackRestriction, usePlaybackRestrictionsList } from './DomainRestrictionsStore.js';
const props = defineProps({
    idx: {
        type: Number,
        default: null,
    },
    restriction: {
        type: Object,
        // eslint-disable-next-line vue/require-valid-default-prop
        default: {
            id: null,
            created_at: null,
            updated_at: null,
            referrer: {
                allowed_domains: ['*'],
                allow_no_referrer: false,
            },
        },
    }
});

const allowed_domains = ref(props.restriction.referrer.allowed_domains);
const allow_no_referrer = ref(props.restriction.referrer.allow_no_referrer);

const allowedDomains = computed({
    get() {
        return allowed_domains.value !== undefined ? allowed_domains.value.join(', ') : [];
    },
    set(value) {
        allowed_domains.value = value.split(',').map(val => val.trim());
    },
});

const deleteRow = () => {
    deletePlaybackRestriction(props.restriction.id).then(() => {
        Craft.cp.displayNotice(Craft.t('mux', 'Playback Restriction Deleted.'));
        usePlaybackRestrictionsList();
    });
};

const validateDomains = (domains) => {
    const patterns = ['*', '*.domain.com', 'sub.domain.com', 'domain.com'];
    for (const str of domains) {
        for (const pattern of patterns) {
            const regex = new RegExp(`^${pattern.replace(/\./g, '\\.')}$`.replace(/^\*/, '[^.]*').replace(/\*/g, '.*') + '|^$');
            if (regex.test(str)) {
                return true;
            }
        }
        return false;
    }
};

const updateRow = useDebounceFn(() => {
    let domains = toRaw(allowed_domains.value).filter((_d) => {
        return _d !== '';
    });
    if (domains.length === 0 || validateDomains(domains)) {
        updatePlaybackRestriction(props.restriction.id, { referrer: { allowed_domains: domains, allow_no_referrer: toRaw(allow_no_referrer.value) }}).then(() => {
            Craft.cp.displayNotice(Craft.t('mux', 'Playback Restriction Updated.'));
            usePlaybackRestrictionsList();
        });
    }
}, 1500);


</script>
