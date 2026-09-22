import * as UpChunk from '@mux/upchunk';

const ENDPOINTS = {
    UPLOAD_ASSET: '/actions/mux/assets/upload-asset',
    GET_UPLOAD: '/actions/mux/assets/get-upload-by-id',
    GET_ASSET: '/actions/mux/assets/get-asset-by-id',
    CREATE_ASSET: '/actions/mux/assets/create',
    CREATE_FROM_URL: '/actions/mux/assets/create-asset-from-url',
};

export const STATE = {
    QUEUED: 'queued',
    UPLOADING: 'uploading',
    PAUSED: 'paused',
    COMPLETE: 'complete',
    FAILED: 'failed',
    CANCELLED: 'cancelled',
};

const items = [];
let _batchCounter = 0;

// Warn before navigating away if uploads are in flight or any have failed.
window.addEventListener('beforeunload', (e) => {
    const shouldWarn = items.some(
        (i) => i.state === STATE.QUEUED ||
               i.state === STATE.UPLOADING ||
               i.state === STATE.PAUSED ||
               i.state === STATE.FAILED,
    );
    if (shouldWarn) {
        e.preventDefault();
        e.returnValue = ''; // required by some browsers to trigger the dialog
    }
});

function apiPost(endpoint, body) {
    const payload = { ...body };
    payload[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
    return fetch(endpoint, {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    }).then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    });
}

function getCurrentVolumeUid() {
    const sourceKey = window.Craft?.elementIndex?.sourceKey;
    return sourceKey?.startsWith('volume:') ? sourceKey.replace('volume:', '') : null;
}

function getCurrentFolderId() {
    return window.Craft?.elementIndex?.currentFolderId ?? null;
}

function dispatch(name, detail) {
    document.dispatchEvent(new CustomEvent(name, { detail, bubbles: false }));
}

function setState(item, state) {
    item.state = state;
    dispatch('mux:upload:state-change', { itemId: item.id, state, item: serialize(item) });
    checkBatchComplete();
}

function serialize(item) {
    return {
        id: item.id,
        title: item.title,
        type: item.type,
        state: item.state,
        progress: item.progress,
        elementId: item.elementId,
        elementCpUrl: item.elementCpUrl,
    };
}

function checkBatchComplete() {
    if (!items.length) return;
    const allSettled = items.every(
        (i) => i.state === STATE.COMPLETE || i.state === STATE.FAILED || i.state === STATE.CANCELLED,
    );
    if (allSettled) {
        dispatch('mux:upload:batch-complete', { items: items.map(serialize) });
    }
}

function makeItemId() {
    _batchCounter += 1;
    return `mux-${_batchCounter}-${Math.random().toString(36).slice(2, 8)}`;
}

// Mux creates the asset asynchronously after the upload completes — poll until asset_id appears.
async function pollForUploadWithAssetId(uploadId, maxAttempts = 12, delayMs = 2500) {
    for (let i = 0; i < maxAttempts; i++) {
        const record = await apiPost(ENDPOINTS.GET_UPLOAD, { id: uploadId });
        if (record?.asset_id) return record;
        if (i < maxAttempts - 1) {
            await new Promise((res) => setTimeout(res, delayMs));
        }
    }
    throw new Error('Timed out waiting for Mux to assign asset_id to upload');
}

// Shared: turn Mux asset data into a Craft element and return the element response.
async function createCraftElement(assetData, title, volumeUid, folderId) {
    const body = { ...assetData, asset_id: assetData.id, asset_status: assetData.status, title, volumeUid, folderId };
    delete body.id;
    delete body.status;
    const element = await apiPost(ENDPOINTS.CREATE_ASSET, body);
    if (!element?.id) throw new Error('CREATE_ASSET returned no element id');
    return element;
}

// ─── Watermark param builder ──────────────────────────────────────────────────

function buildWatermarkParams(wm) {
    if (!wm) return {};
    return {
        watermarkEnabled:          wm.enabled ?? false,
        watermarkUrl:              wm.url ?? '',
        watermarkVerticalAlign:    wm.verticalAlign ?? '',
        watermarkVerticalMargin:   wm.verticalMargin ?? '',
        watermarkHorizontalAlign:  wm.horizontalAlign ?? '',
        watermarkHorizontalMargin: wm.horizontalMargin ?? '',
        watermarkWidth:            wm.width ?? '',
        watermarkHeight:           wm.height ?? '',
        watermarkOpacity:          wm.opacityPct != null ? String(wm.opacityPct) + '%' : '',
    };
}

// ─── File upload (upchunk) ────────────────────────────────────────────────────

async function startFileUpload(item) {
    setState(item, STATE.UPLOADING);

    let uploadData;
    try {
        uploadData = await apiPost(ENDPOINTS.UPLOAD_ASSET, {
            title: item.title,
            volumeUid: item.volumeUid,
            folderId: item.folderId,
            normalizeAudio: item.settings.normalizeAudio,
            autoGenerateCaptions: item.settings.autoGenerateCaptions,
            captionsLanguage: item.settings.captionsLanguage,
            playbackPolicy: item.settings.playbackPolicy,
            videoQuality: item.settings.videoQuality,
            ...buildWatermarkParams(item.settings.watermark),
        });
    } catch (_) {
        setState(item, STATE.FAILED);
        return;
    }

    item.uploadData = uploadData;

    const chunkSize = parseInt(window.RocketPark?.Mux?.Settings?.uploadChunkSize) || 30720;
    const upload = UpChunk.createUpload({ endpoint: uploadData.url, file: item.file, chunkSize });
    item.uploadInstance = upload;

    upload.on('progress', (e) => {
        item.progress = e.detail;
        dispatch('mux:upload:progress', { itemId: item.id, progress: e.detail });
    });
    upload.on('error', () => setState(item, STATE.FAILED));
    upload.on('success', () => onFileUploadSuccess(item));
}

async function onFileUploadSuccess(item) {
    try {
        // Poll until Mux has finished creating the asset and linked it to the upload
        const uploadRecord = await pollForUploadWithAssetId(item.uploadData.id);
        const assetData = await apiPost(ENDPOINTS.GET_ASSET, { id: uploadRecord.asset_id });
        const element = await createCraftElement(assetData, item.title, item.volumeUid, item.folderId);

        item.elementId = element.id;
        const cpTrigger = window.Craft?.cpTrigger || 'admin';
        item.elementCpUrl = `/${cpTrigger}/mux/assets/${element.id}`;
        setState(item, STATE.COMPLETE);
    } catch (_) {
        setState(item, STATE.FAILED);
    }
}

// ─── URL ingest ───────────────────────────────────────────────────────────────

async function startUrlIngest(item) {
    setState(item, STATE.UPLOADING);

    try {
        // CREATE_FROM_URL returns raw Mux asset data (not a Craft element)
        const assetData = await apiPost(ENDPOINTS.CREATE_FROM_URL, {
            url: item.url,
            title: item.title,
            volumeUid: item.volumeUid,
            folderId: item.folderId,
            normalizeAudio: item.settings.normalizeAudio,
            autoGenerateCaptions: item.settings.autoGenerateCaptions,
            captionsLanguage: item.settings.captionsLanguage,
            playbackPolicy: item.settings.playbackPolicy,
            videoQuality: item.settings.videoQuality,
            ...buildWatermarkParams(item.settings.watermark),
        });

        // Create the Craft element from the Mux asset data
        const element = await createCraftElement(assetData, item.title, item.volumeUid, item.folderId);

        item.elementId = element.id;
        const cpTrigger = window.Craft?.cpTrigger || 'admin';
        item.elementCpUrl = `/${cpTrigger}/mux/assets/${element.id}`;
        item.progress = 100;
        setState(item, STATE.COMPLETE);
    } catch (_) {
        setState(item, STATE.FAILED);
    }
}

// ─── Public API ───────────────────────────────────────────────────────────────

export function startBatch(incomingItems, titlesMap, settings) {
    const volumeUid = getCurrentVolumeUid();
    const folderId = getCurrentFolderId();

    incomingItems.forEach((incoming) => {
        const id = makeItemId();
        const title = titlesMap.get(incoming)
            || (incoming.type === 'file' ? incoming.file.name : incoming.url);

        const item = {
            id,
            type: incoming.type,
            file: incoming.type === 'file' ? incoming.file : null,
            url: incoming.type === 'url' ? incoming.url : null,
            title,
            settings,
            volumeUid,
            folderId,
            state: STATE.QUEUED,
            progress: 0,
            uploadData: null,
            uploadInstance: null,
            elementId: null,
            elementCpUrl: null,
        };
        items.push(item);
        dispatch('mux:upload:start', { item: serialize(item) });
    });

    items
        .filter((i) => i.state === STATE.QUEUED)
        .forEach((i) => (i.type === 'url' ? startUrlIngest(i) : startFileUpload(i)));
}

export function pauseUpload(itemId) {
    const item = items.find((i) => i.id === itemId);
    if (item?.state === STATE.UPLOADING && item.uploadInstance?.pause) {
        item.uploadInstance.pause();
        setState(item, STATE.PAUSED);
    }
}

export function resumeUpload(itemId) {
    const item = items.find((i) => i.id === itemId);
    if (item?.state === STATE.PAUSED && item.uploadInstance?.resume) {
        item.uploadInstance.resume();
        setState(item, STATE.UPLOADING);
    }
}

export function cancelUpload(itemId) {
    const item = items.find((i) => i.id === itemId);
    if (!item) return;
    item.uploadInstance?.abort?.();
    setState(item, STATE.CANCELLED);
}

export function retryUpload(itemId) {
    const item = items.find((i) => i.id === itemId);
    if (!item || (item.state !== STATE.FAILED && item.state !== STATE.CANCELLED)) return;
    item.progress = 0;
    item.uploadInstance = null;
    item.uploadData = null;
    if (item.type === 'url') startUrlIngest(item);
    else startFileUpload(item);
}

export function removeItem(itemId) {
    const idx = items.findIndex((i) => i.id === itemId);
    if (idx !== -1) items.splice(idx, 1);
    dispatch('mux:upload:removed', { itemId });
}

export function getItems() {
    return items.map(serialize);
}

