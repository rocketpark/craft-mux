import { reactive, toRefs, toRaw } from 'vue';

export const state = reactive({
    signedkeys: [],
    loading: false,
    params: {
        limit: 50,
        page: 1,
    },
});

export function addRestriction(restriction)
{
    state.restrictions.push(restriction);
}

/**
 * Use Playback Restrictions List
 * @param {JSON} params
 */
export function useSignedKeysList() {
    state.loading = true;

    const params = new URLSearchParams(state.params).toString();

    fetch(`/actions/mux/signing-keys/list-signed-keys?${params}`)
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then((data) => {
            state.signedkeys = data;
            state.loading = false;
        })
        .catch((error) => {
            state.loading = false;
            console.error('There was a problem with the fetch operation:', error);
        });
}

export function createSignedKey(request) {
    state.loading = true;
    const body = { request };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/signing-keys/create-signed-key', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        state.signedkeys = res.data;
        state.loading = false;
        return res.json();
    }).catch((err) => {
        state.loading = false;
        return Promise.reject(err);
    });
}


export function deleteSignedKey(signed_key_id) {
    state.loading = true;
    const body = { signed_key_id };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/signing-keys/delete-signed-key', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        state.loading = false;
        return res.json();
    }).catch((err) => {
        state.loading = false;
        return Promise.reject(err);
    });
}
