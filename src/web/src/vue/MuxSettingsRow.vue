<template>
    <tr :data-id="idx">
        <td class="undefined singleline-cell textual">
            <textarea 
                :name="`[referrer][${idx}][allowed_domains]`" 
                rows="1" 
                v-model="allowedDomains"
                :disabled="state.loading"
                @input="updateRow"
                @keyup="updateRow"></textarea>
        </td>
        <td class="undefined checkbox-cell">
            <div class="checkbox-wrapper">
                <input type="hidden" :name="`[referrer][${idx}][allow_no_referrer]`" :value="allow_no_referrer">
                <input 
                    type="checkbox" 
                    value="true" 
                    v-model="allow_no_referrer" 
                    id="checkbox" 
                    class="checkbox" 
                    :name="`[referrer][${idx}][allow_no_referrer]`"
                    @change="updateRow">
                <label for="checkbox"></label>
            </div>
        </td>
        <td class="thin action">
            <button @click.prevent="deleteRow" class="delete icon" title="Delete" type="button" :disabled="state.loading" :aria-label="`Delete restriction ${idx + 1}`"></button>
        </td>
    </tr>
</template>
<script setup>
    import { computed, ref, toRaw } from 'vue';
    import { useDebounceFn } from '@vueuse/core';
    import { state, deletePlaybackRestriction, updatePlaybackRestriction, usePlaybackRestrictionsList } from "./SettingsStore.js";
    const props = defineProps({
        idx: {
            type: Number,
            default: null
        },
        restriction: {
            type: Object,
            default: {
                "id": null,
                "created_at": null,
                "updated_at": null,
                "referrer": {
                    "allowed_domains": ["*"],
                    "allow_no_referrer": false
                }
            }
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
        }
    });

    const deleteRow = () => {
        deletePlaybackRestriction(props.restriction.id).then(() =>{
            usePlaybackRestrictionsList();
        });
    };

    const updateRow = useDebounceFn(() => {
        let domains = toRaw(allowed_domains.value).filter((_d) => { 
            return _d !== "";
        });
        if(domains.length === 0 || validateDomains(domains)) {
            updatePlaybackRestriction(props.restriction.id, { "referrer": { "allowed_domains": domains, "allow_no_referrer": toRaw(allow_no_referrer.value) }}).then(() => {
                usePlaybackRestrictionsList();
            });
        }
    }, 1500);

    const validateDomains = (domains) => {
        const patterns = ["*", "*.domain.com", "sub.domain.com", "domain.com"];
        for(const str of domains) {
            for (const pattern of patterns) {
                const regex = new RegExp(`^${pattern.replace(/\./g, "\\.")}$`.replace(/^\*/, "[^.]*").replace(/\*/g, ".*") + "|^$");
                if (regex.test(str)) {
                    return true;
                }
            }
            return false;
        }
    }

</script>