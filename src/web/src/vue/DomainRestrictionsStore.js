import { reactive, toRefs, toRaw } from "vue";
//import axios from 'axios';

export const state  = reactive({
    restrictions: [],
    loading: false,
    defaultInfo: { id: null, raw: '', source: 'none' },
    error: null,
    params: {
        limit: 50,
        page: 1
    }
});

/**
 * Reads a JSON response and rejects with the server's error message when the request failed.
 * @param {Response} res
 */
function readJson(res) {
    return res.json().catch(() => ({})).then((json) => {
        if (!res.ok || json.success === false) {
            throw new Error(json.error || `Request failed (HTTP ${res.status}).`);
        }
        return json;
    });
}

export function addRestriction(restriction)
{
    state.restrictions.push(restriction);
}

/**
 * Use Playback Restrictions List
 * @param {JSON} params
 */
export function usePlaybackRestrictionsList() {
    state.loading = true;

    const params = new URLSearchParams(state.params).toString();

    return fetch(`/actions/mux/settings/use-playback-restrictions-list?${params}`, { headers: { Accept: 'application/json' } })
        .then(readJson)
        .then((data) => {
            state.restrictions = Array.isArray(data.result?.data) ? data.result.data : [];
            state.error = null;
            state.loading = false;
        })
        .catch((error) => {
            state.loading = false;
            state.error = Craft.t('mux', 'Couldn’t load playback restrictions from Mux.') + ' ' + error.message;
        });
}

export function createPlaybackRestriction(request) {
    state.loading = true;
    const body = { request };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/settings/create-playback-restriction', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
    }).then(readJson).then((json) => {
        state.loading = false;
        return json;
    }).catch((err) => {
        state.loading = false;
        return Promise.reject(err);
    });
}

export function updatePlaybackRestriction(id, request) {
    state.loading = true;
    const body = { id, referrer: request.referrer };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/settings/update-referrer-domain-restriction', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
    }).then(readJson).then((json) => {
        state.loading = false;
        return json;
    }).catch((err) => {
        state.loading = false;
        return Promise.reject(err);
    });
}

export function deletePlaybackRestriction(id) {
    state.loading = true;
    const body = { id };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/settings/delete-playback-restriction', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
    }).then(readJson).then((json) => {
        state.loading = false;
        return json;
    }).catch((err) => {
        state.loading = false;
        return Promise.reject(err);
    });
}

/**
 * Make a restriction the default for signed playback tokens ('' clears the default).
 * @param {string} id
 */
export function setDefaultPlaybackRestriction(id) {
    state.loading = true;
    const body = { id };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/settings/set-default-playback-restriction', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        return res.json();
    }).then((json) => {
        state.loading = false;
        if (json.default) {
            state.defaultInfo = json.default;
        }
        if (!json.success) {
            return Promise.reject(new Error(json.error || 'Could not set the default restriction.'));
        }
        return json;
    }).catch((err) => {
        state.loading = false;
        return Promise.reject(err);
    });
}
