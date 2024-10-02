import {
    reactive, inject, toRefs, toRaw,
} from 'vue';
//import axios from 'axios';
import * as UpChunk from '@mux/upchunk';

export const state = reactive({
    assets: [],
    asset: null,
    total: 0,
    selected: null,
    loading: false,
    params: {
        limit: 12,
        page: 1,
    },
});

export const uploadState = reactive({
    progress: 0,
    progressTotal: 100,
    uploading: false,
    uploadingFile: null,
});


/**
 * Set Selected Mux Asset
 * @param {JSON} selected
 */
export function setSelected(selected) {
    state.selected = selected;
}

/**
 * Set the State page params
 * @param {offset:string, page:int} params
 */
export function setPageParams(params) {
    state.params = { ...state.params, ...params };
}

/**
 * Update the states total number of assets
 */
function updateAssetsCount() {
    fetch('/actions/mux/assets/use-asset-elements-count')
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then((data) => {
            state.total = data;
        })
        .catch((error) => {
            console.error('There was a problem with the updateAssetsCount fetch operation:', error);
        });
}

/**
 * Update Selected Asset
 * After useAssets is called if there is a slected asset
 *  updated it with newly updated assets
 */
export function updatedSelectedAsset() {
    state.selected = state.selected !== null ? state.assets.find((asset) => { return asset.id === state.selected.id; }) : null;
}


/**
 * Use Assets
 * @param {JSON} params
 */
export function useAssets() {
    state.loading = true;

    const params = new URLSearchParams(state.params).toString();

    fetch(`/actions/mux/assets/use-asset-elements?${params}`)
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then((data) => {
            state.assets = data;
            updateAssetsCount();
            updatedSelectedAsset();
            state.loading = false;
        })
        .catch((error) => {
            console.error('There was a problem with the useAssets fetch operation:', error);
        });
}

/**
 * Use Asset By Id
 * @param {int} id
 */
export function useAssetById(id) {
    state.loading = true;
    const params = new URLSearchParams({ id }).toString();

    fetch(`/actions/mux/assets/use-asset-element-by-id?${params}`)
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then((data) => {
            state.asset = data;
            state.loading = false;
        })
        .catch((error) => {
            console.error('There was a problem with the fetch operation:', error);
        });
}


/**
 * Get MUX Upload URL
 * @returns Promise
 */
export const getUploadUrl = (file) => {

    const body = { passthrough: file.name };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
    return new Promise((resolve, reject) => {
        fetch('/actions/mux/assets/upload-asset', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        }).then((res) => {
            if (!res.ok) {
                throw new Error(`HTTP error ${res.status}`);
            }
            return res.json();
        }).then((json) => {
            resolve(json);
        }).catch((err) => {
            reject(err);
        });
    });
};


/**
 * Get Mux Asset By Upload ID
 * @param {string} id
 * @returns Promise
 */
export const getUploadById = (id) => {

    const body = { id };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
    return fetch('/actions/mux/assets/get-upload-by-id', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        updateAssetsCount();
        return res.json();
    }).catch((err) => {
        return Promise.reject(err);
    });

};

/**
 * Get Mux Asset By ID
 * @param {string} id
 * @returns Promise
 */
export const getAssetById = (id) => {

    const body = { id };
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/assets/get-asset-by-id', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        return res.json();
    }).catch((err) => {
        return Promise.reject(err);
    });

};

/**
 * Create Mux Asset
 * @param {JSON} data
 * @returns Promise
 */
export const createAsset = (data) => {

    let body = {};
    // Reset the values so they correspond to the element model
    data.asset_id = data.id;
    data.asset_status = data.status;
    data.title = data.title !== undefined ? data.title : data.id;
    // We don't want these assigned to the Element since they have been reassigned
    delete data.id;
    delete data.status;
    body = data;
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/assets/create', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        updateAssetsCount();
        return res.json();
    }).catch((err) => {
        return Promise.reject(err);
    });

};


/**
 * Save Mux Asset
 * @param {JSON} data
 * @returns Promise
 */
export const saveAsset = (data) => {

    const body = {};
    // Reset the values so they correspond to the element model
    data.asset_id = data.id;
    data.asset_status = data.status;
    data.id = null;
    body.asset = data;
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/assets/save', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        return res.json();
    }).catch((err) => {
        return Promise.reject(err);
    });
};


/**
 * Update Mux Asset
 * @param {JSON} data
 * @returns Promise
 */
export const updateAsset = (data) => {
    const body = data;
    body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

    return fetch('/actions/mux/assets/update', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        return res.json();
    }).catch((err) => {
        return Promise.reject(err);
    });
};

/**
 * Delete Mux Asset By ID
 * @param {string} id
 * @returns Promise
 */
export const deleteAssetById = (id) => {
    return fetch('/actions/mux/assets/delete-asset-by-id', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id,
            [window.Craft.csrfTokenName]: window.Craft.csrfTokenValue,
        }),
    }).then((res) => {
        if (!res.ok) { return Promise.reject(new Error(`HTTP error ${res.status}`)); }
        updateAssetsCount();
        return res.json();
    }).catch((err) => {
        return Promise.reject(err);
    });
};

/**
 * Upload File - Mux Asset
 * @param {file} file
 * @param {string} res
 * @returns Promise
 */
const uploadFile = (file, res) => {
    return new Promise((resolve, reject) => {
        const upload = UpChunk.createUpload({
            endpoint: res.url,
            file,
            chunkSize: 30720, // Uploads the file in ~30 MB chunks
        });

        // subscribe to events
        upload.on('error', (err) => {
            console.error('💥 🙀', err.detail);
            reject(err);
            uploadState.uploading = false;
        });

        upload.on('progress', (prog) => {
            uploadState.progress = prog.detail;
        });

        upload.on('success', (data) => {
            getUploadById(res.id)
                .then((data) => { return getAssetById(data.asset_id); })
                .then((data) => {
                    data.title = file.name;
                    createAsset(data);
                })
                .then((data) => {
                    uploadState.progress = 0;
                    resolve(data);
                });
            // console.log("Wrap it up, we're done here. 👋");
        });
    });
};


/**
 * Upload Files - Video Files
 * @param {files} files
 */
export const uploadFiles = async(files) => {
    uploadState.uploading = true;
    uploadState.progressTotal = files.length * 100;

    try {
        const uploadPromises = [];

        for (const file of files) {
            uploadState.uploadingFile = file;
            const uploadUrl = await getUploadUrl(file);
            const promise = uploadFile(file, uploadUrl);
            uploadPromises.push(promise);
            await promise;
        }

        await Promise.all(uploadPromises);
        uploadState.uploading = false;
        return Promise.resolve();

    } catch (error) {
        uploadState.uploading = false;
        return Promise.reject(error);
    }
};

/**
 * Remove Asset By ID
 *   - Removes the asset from the state assets variable array.
 * @param {string|number} id
 */
export const removeAssetById = (id) => {
    state.assets = state.assets.filter((asset) => { return asset.id !== id; });
};

/**
 * Add Asset Track By ID
 * @param {string|number} _id
 * @param {object} _track
 * @returns
 */
export const addAssetTrackById = (_id, _track) => {
    return new Promise((resolve, reject) => {
        const body = { id: _id, track: _track };
        const csrfKey = window.Craft.csrfTokenName;
        body[csrfKey] = window.Craft.csrfTokenValue;

        fetch('/actions/mux/assets/add-asset-track-by-id', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        }).then((res) => {
            if (!res.ok) {
                throw new Error(`HTTP error ${res.status}`);
            }
            return res.json();
        }).then((json) => {
            resolve(json);
        }).catch((err) => {
            console.log(err);
            reject('Adding asset track failed!');
        });
    });
};

/**
 * Delete Asset Track By ID
 * @param {*} _id
 * @param {*} _track_id
 * @returns
 */
export const deleteAssetTrack = (_id, _track_id) => {
    return new Promise((resolve, reject) => {
        const body = { id: _id, track_id: _track_id };
        const csrfKey = window.Craft.csrfTokenName;
        body[csrfKey] = window.Craft.csrfTokenValue;

        fetch('/actions/mux/assets/delete-asset-track-by-id', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        }).then((res) => {
            if (!res.ok) {
                throw new Error(`HTTP error ${res.status}`);
            }
            return res.json();
        }).then((json) => {
            resolve(json);
        }).catch((err) => {
            console.log(err);
            reject('Delete Aasset Track Failed!');
        });
    });
};
