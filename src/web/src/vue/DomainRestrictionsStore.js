import { reactive, toRefs, toRaw } from "vue";
//import axios from 'axios';

export const state  = reactive({
    restrictions: [],
    loading: false,
    params: {
        limit: 50,
        page: 1
    }
});

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

    fetch(`/actions/mux/settings/use-playback-restrictions-list?${params}`)
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                state.restrictions = data.result.data;
            }
            state.loading = false;
        })
        .catch((error) => {
            state.loading = false;
            console.error('There was a problem with the usePlaybackRestrictionsList fetch operation:', error);
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
    }).then((res) => {
        if (!res.ok) {return Promise.reject(new Error(`HTTP error ${res.status}`));}
        state.restrictions = res.data;
        state.loading = false;
        return res.json();
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
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        state.loading = false;
        return res.json();
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
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        state.loading = false;
        return res.json();
    }).catch((err) => {
        state.loading = false;
        return Promise.reject(err);
    });
}