<template>
    <div id="settings-muxPlaybackRestrictions-field" class="field" data-attribute="muxPlaybackRestrictions">
        <div id="settings-muxPlaybackRestrictions-instructions" class="instructions">
            <p>
                Playback Restrictions set which websites may play your videos. For instance, with <code>example.com</code> as an allowed domain, videos play when embedded on <code>https://example.com</code> and requests from other websites are denied.
            </p>
            <ul>
                <li><code>*</code> allows every domain (only "Allow No Referrer" is then enforced)</li>
                <li><code>*.example.com, example.com</code>: up to 10 domains or DNS-style wildcards</li>
                <li>Empty: denies playback on every domain</li>
            </ul>
            <p>
                <strong>Restrictions only apply to signed videos, and only the default restriction is enforced.</strong>
                Creating a restriction here doesn't apply it to anything: Mux checks a restriction when a signed playback token names it. Tick <em>Default</em> on a restriction (or use the <a :href="settingsUrl">Default Playback Restriction ID</a> setting) to choose which one every signed token carries. Public playback IDs can't be restricted.
            </p>
        </div>

        <div v-if="state.error" class="error" role="alert" style="margin: 0 0 14px; padding: 10px 12px; border-radius: 4px; background: var(--red-050, #fef2f2);">
            {{ state.error }}
        </div>

        <div :style="defaultNoticeStyle">
            <template v-if="state.defaultInfo.source === 'none'">
                <span class="status-label orange" style="margin-right: 6px;"><span class="status orange"></span><span class="status-label-text">No default</span></span>
                Signed videos play without a restriction. Choose one below.
            </template>
            <template v-else>
                <span class="status-label green" style="margin-right: 6px;"><span class="status green"></span><span class="status-label-text">Default</span></span>
                <code>{{ state.defaultInfo.id || '(empty)' }}</code>
                <span v-if="state.defaultInfo.source === 'env'"> from environment variable <code>{{ state.defaultInfo.raw }}</code>. Change that variable in each environment's <code>.env</code>.</span>
                <span v-else-if="state.defaultInfo.source === 'config'"> from <code>config/mux.php</code>. Change it there.</span>
                <span v-else> saved in the plugin settings (project config). Tip: use an environment variable when your environments use different Mux environments.</span>
                <span v-if="defaultMissing" class="error"> This ID isn't in the list below, so it doesn't exist in this Mux environment and signed playback will be refused.</span>
            </template>
        </div>

        <div class="input ltr">
            <table id="settings-muxPlaybackRestrictions" class="editable fullwidth" style="">
                <colgroup>
                    <col>
                    <col style="width: 1%;">
                    <col style="width: 1%;">
                    <col style="width: 1%;">
                    <col style="width: 1%;">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col" class="singleline-cell textual">
                            Domains
                        </th>
                        <th scope="col" class="checkbox-cell thin" style="white-space: nowrap;">
                            Allow No Referrer
                        </th>
                        <th scope="col" class="checkbox-cell thin">
                            Default
                        </th>
                        <th scope="col" class="thin">
                            ID
                        </th>
                        <th colspan="1" scope="colgroup">
                            <span class="visually-hidden">Row actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <mux-domain-restrictions-row
                        v-for="(row, index) in state.restrictions"
                        :key="row.id"
                        :idx="index"
                        :restriction="row"
                        :is-default="row.id === state.defaultInfo.id"
                        :can-set-default="canSetDefault"
                    />
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
import { computed } from 'vue';
import { state, usePlaybackRestrictionsList, createPlaybackRestriction } from './DomainRestrictionsStore.js';
import MuxDomainRestrictionsRow from './MuxDomainRestrictionsRow.vue';

const props = defineProps({
    defaultInfo: {
        type: Object,
        default: () => ({ id: null, raw: '', source: 'none' }),
    },
    settingsUrl: {
        type: String,
        default: '',
    },
});

state.defaultInfo = props.defaultInfo;

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
    return state.loading ? 'btn dashed mux-w-full loading' : 'btn dashed mux-w-full add icon' ;
});

// The default can only be changed here when it isn't controlled by an env var or config/mux.php
const canSetDefault = computed(() => !['env', 'config'].includes(state.defaultInfo.source));

// The default points at a restriction that isn't in this Mux environment
const defaultMissing = computed(() => {
    return !state.loading
        && Array.isArray(state.restrictions)
        && state.restrictions.length > 0
        && !!state.defaultInfo.id
        && !state.restrictions.some((r) => r.id === state.defaultInfo.id);
});

const defaultNoticeStyle = computed(() => ({
    margin: '0 0 14px',
    padding: '10px 12px',
    borderRadius: '4px',
    lineHeight: '1.6',
    background: (state.defaultInfo.source === 'none' || defaultMissing.value) ? 'var(--yellow-050, #fffbeb)' : 'var(--gray-050, #f3f7fc)',
}));

usePlaybackRestrictionsList();

const addRow = () => {
    createPlaybackRestriction({ referrer: playbackRestrictionModel.referrer }).then(() => {
        Craft.cp.displayNotice(Craft.t('mux', 'Playback Restriction Created.'));
        usePlaybackRestrictionsList();
    }).catch((err) => {
        Craft.cp.displayError(Craft.t('mux', 'Couldn’t create the playback restriction.') + ' ' + err.message);
    });
};

</script>

