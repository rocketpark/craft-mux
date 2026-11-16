<template>
    <tr :data-id="idx">
        <td class="singleline-cell textual">
            <textarea
                v-model="allowedDomains"
                :name="`[referrer][${idx}][allowed_domains]`"
                rows="1"
                :disabled="state.loading"
                @input="updateRow"
                @keyup="updateRow"
            ></textarea>
        </td>
        <td class="checkbox-cell">
            <div class="checkbox-wrapper">
                <input
                    :id="`mux-restriction-${idx}-allow-no-referrer`"
                    v-model="allow_no_referrer"
                    type="checkbox"
                    class="checkbox"
                    :disabled="state.loading"
                    @change="updateRow"
                >
                <label :for="`mux-restriction-${idx}-allow-no-referrer`"><span class="visually-hidden">Allow no referrer</span></label>
            </div>
        </td>
        <td class="checkbox-cell" :title="defaultTitle">
            <div class="checkbox-wrapper">
                <input
                    :id="`mux-restriction-${idx}-default`"
                    type="checkbox"
                    class="checkbox"
                    :checked="isDefault"
                    :disabled="!canSetDefault || state.loading"
                    @change="toggleDefault"
                >
                <label :for="`mux-restriction-${idx}-default`"><span class="visually-hidden">Use as the default restriction</span></label>
            </div>
        </td>
        <td style="vertical-align: middle; white-space: nowrap;">
            <button
                type="button"
                :title="`${restriction.id}\n${copyLabel}`"
                :aria-label="`${copyLabel}: ${restriction.id}`"
                :style="copyButtonStyle"
                @click.prevent="copyId"
                @mouseenter="copyHover = true"
                @mouseleave="copyHover = false"
                @focus="copyHover = true"
                @blur="copyHover = false"
            >
                <span>{{ shortId }}</span>
                <span data-icon="clipboard" aria-hidden="true" style="margin-block-start: -3px;"></span>
            </button>
        </td>
        <td class="thin action">
            <button
                class="delete icon"
                :title="deleteLabel"
                :disabled="state.loading"
                :aria-label="`${deleteLabel} ${idx + 1}`"
                type="button"
                @click.prevent="deleteRow"
            ></button>
        </td>
    </tr>
</template>
<script setup>
import { computed, ref, toRaw } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { state, deletePlaybackRestriction, updatePlaybackRestriction, usePlaybackRestrictionsList, setDefaultPlaybackRestriction } from './DomainRestrictionsStore.js';
const props = defineProps({
    idx: {
        type: Number,
        default: null,
    },
    isDefault: {
        type: Boolean,
        default: false,
    },
    canSetDefault: {
        type: Boolean,
        default: true,
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

const copyLabel = Craft.t('app', 'Copy to clipboard');
const deleteLabel = Craft.t('app', 'Delete');
const copyHover = ref(false);

// Same look as Craft's <craft-copy-attribute> (used for element IDs/UIDs), but with a shortened ID:
// that component always prints the full value, and restriction IDs are 44 characters long.
const shortId = computed(() => {
    const id = props.restriction.id || '';
    return id.length > 10 ? `${id.slice(0, 10)}…` : id;
});

const copyButtonStyle = computed(() => ({
    display: 'inline-flex',
    alignItems: 'center',
    flexWrap: 'nowrap',
    gap: '5px',
    height: '1.375rem',
    paddingInline: '5px',
    border: `1px solid ${copyHover.value ? 'var(--border-hairline-medium)' : 'var(--border-hairline)'}`,
    borderRadius: 'var(--button-border-radius)',
    background: 'transparent',
    color: copyHover.value ? 'var(--text-color)' : 'var(--fg-subtle)',
    fontFamily: 'SFMono-Regular, Consolas, "Liberation Mono", Menlo, Courier, monospace',
    fontSize: '.6875rem',
    cursor: 'pointer',
}));

const copyId = (event) => {
    const button = event.currentTarget;
    const done = () => {
        Craft.cp.displayNotice(Craft.t('app', 'Copied to clipboard.'));
        button.focus();
    };
    const fallback = () => {
        const input = document.createElement('textarea');
        input.value = props.restriction.id;
        input.setAttribute('readonly', '');
        input.style.position = 'absolute';
        input.style.left = '-9999px';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
        done();
    };
    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(props.restriction.id).then(done).catch(fallback);
    } else {
        fallback();
    }
};

const defaultTitle = computed(() => {
    const info = state.defaultInfo || {};
    if (info.source === 'env') {
        return Craft.t('mux', 'The default comes from {var}. Change that environment variable instead.', { var: info.raw });
    }
    if (info.source === 'config') {
        return Craft.t('mux', 'The default is set in config/mux.php. Change it there.');
    }
    return props.isDefault
        ? Craft.t('mux', 'Default restriction for signed videos. Uncheck to play signed videos without a restriction.')
        : Craft.t('mux', 'Use as the default restriction for signed videos.');
});

const toggleDefault = (event) => {
    const checkbox = event.target;

    if (!checkbox.checked && !confirm(Craft.t('mux', 'Signed videos will play without a restriction. Continue?'))) {
        checkbox.checked = true;
        return;
    }

    setDefaultPlaybackRestriction(checkbox.checked ? props.restriction.id : '').then(() => {
        Craft.cp.displayNotice(checkbox.checked
            ? Craft.t('mux', 'Default playback restriction updated.')
            : Craft.t('mux', 'Default playback restriction removed.'));
    }).catch((err) => {
        checkbox.checked = props.isDefault;
        Craft.cp.displayError(err.message);
    });
};

const deleteRow = () => {
    const message = props.isDefault
        ? Craft.t('mux', 'This is the default restriction. Signed playback will fail until you choose another default or clear it. Delete it anyway?')
        : Craft.t('mux', 'Delete this playback restriction? Tokens that name it will stop working.');
    if (!confirm(message)) {
        return;
    }
    deletePlaybackRestriction(props.restriction.id).then(() => {
        Craft.cp.displayNotice(Craft.t('mux', 'Playback Restriction Deleted.'));
        usePlaybackRestrictionsList();
    }).catch((err) => {
        Craft.cp.displayError(Craft.t('mux', 'Couldn’t delete the playback restriction.') + ' ' + err.message);
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
        }).catch((err) => {
            Craft.cp.displayError(Craft.t('mux', 'Couldn’t update the playback restriction.') + ' ' + err.message);
        });
    }
}, 1500);
</script>

