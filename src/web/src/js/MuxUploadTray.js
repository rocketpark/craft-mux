import { LitElement, html, nothing } from 'lit';
import {
    STATE,
    pauseUpload,
    resumeUpload,
    cancelUpload,
    retryUpload,
    removeItem,
} from './MuxUploadManager.js';

const FILTER_STATES = {
    all:       null,
    queued:    STATE.QUEUED,
    uploading: STATE.UPLOADING,
    uploaded:  STATE.COMPLETE,
    failed:    [STATE.FAILED, STATE.CANCELLED],
};

const TABS = [
    { filter: 'all',       label: 'All' },
    { filter: 'queued',    label: 'Queued' },
    { filter: 'uploading', label: 'Uploading' },
    { filter: 'uploaded',  label: 'Uploaded' },
    { filter: 'failed',    label: 'Failed' },
];

// State icons as Lit TemplateResults — safe to embed in html`` templates
const STATE_ICONS = {
    [STATE.QUEUED]:    html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
    [STATE.UPLOADING]: html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>`,
    [STATE.PAUSED]:    html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>`,
    [STATE.COMPLETE]:  html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>`,
    [STATE.FAILED]:    html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
    [STATE.CANCELLED]: html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
};

class MuxUploadTray extends LitElement {
    static properties = {
        _items:        { state: true },
        _activeFilter: { state: true },
        _collapsed:    { state: true },
        _visible:      { state: true },
    };

    // Light DOM — Craft CP class-based styles apply directly
    createRenderRoot() { return this; }

    constructor() {
        super();
        this._items = [];
        this._activeFilter = 'all';
        this._collapsed = false;
        this._visible = false;
        this._progress = new Map(); // itemId → number; not reactive, uses requestUpdate()
        this._dismissTimer = null;
        this._handlers = null;
    }

    connectedCallback() {
        super.connectedCallback();
        this._handlers = {
            start:         (e) => this._onStart(e.detail.item),
            progress:      (e) => this._onProgress(e.detail.itemId, e.detail.progress),
            stateChange:   (e) => this._onStateChange(e.detail.itemId, e.detail.item),
            removed:       (e) => this._onRemoved(e.detail.itemId),
            batchComplete: ()  => this._onBatchComplete(),
        };
        document.addEventListener('mux:upload:start',         this._handlers.start);
        document.addEventListener('mux:upload:progress',      this._handlers.progress);
        document.addEventListener('mux:upload:state-change',  this._handlers.stateChange);
        document.addEventListener('mux:upload:removed',       this._handlers.removed);
        document.addEventListener('mux:upload:batch-complete',this._handlers.batchComplete);
    }

    disconnectedCallback() {
        super.disconnectedCallback();
        document.removeEventListener('mux:upload:start',         this._handlers.start);
        document.removeEventListener('mux:upload:progress',      this._handlers.progress);
        document.removeEventListener('mux:upload:state-change',  this._handlers.stateChange);
        document.removeEventListener('mux:upload:removed',       this._handlers.removed);
        document.removeEventListener('mux:upload:batch-complete',this._handlers.batchComplete);
        if (this._dismissTimer) clearTimeout(this._dismissTimer);
    }

    // ─── Upload event handlers ────────────────────────────────────────────────

    _onStart(item) {
        this._visible = true;
        if (this._dismissTimer) { clearTimeout(this._dismissTimer); this._dismissTimer = null; }
        this._items = [...this._items, item];
    }

    _onProgress(itemId, progress) {
        this._progress.set(itemId, progress);
        this.requestUpdate();
    }

    _onStateChange(itemId, updatedItem) {
        this._items = this._items.map((i) => (i.id === itemId ? updatedItem : i));
    }

    _onRemoved(itemId) {
        this._progress.delete(itemId);
        this._items = this._items.filter((i) => i.id !== itemId);
    }

    _onBatchComplete() {
        if (window.Craft?.elementIndex) {
            window.Craft.elementIndex.updateElements(true);
        }
        const hasFailures = this._items.some(
            (i) => i.state === STATE.FAILED || i.state === STATE.CANCELLED,
        );
        if (!hasFailures && this._items.length > 0) {
            this._dismissTimer = setTimeout(() => this._dismiss(), 3000);
        }
    }

    // ─── Computed getters ─────────────────────────────────────────────────────

    get _filteredItems() {
        const f = FILTER_STATES[this._activeFilter];
        if (f === null) return this._items;
        if (Array.isArray(f)) return this._items.filter((i) => f.includes(i.state));
        return this._items.filter((i) => i.state === f);
    }

    get _counts() {
        return {
            all:       this._items.length,
            queued:    this._items.filter((i) => i.state === STATE.QUEUED).length,
            uploading: this._items.filter((i) => i.state === STATE.UPLOADING || i.state === STATE.PAUSED).length,
            uploaded:  this._items.filter((i) => i.state === STATE.COMPLETE).length,
            failed:    this._items.filter((i) => i.state === STATE.FAILED || i.state === STATE.CANCELLED).length,
        };
    }

    get _allSettled() {
        return (
            this._items.length > 0 &&
            this._items.every(
                (i) =>
                    i.state === STATE.COMPLETE ||
                    i.state === STATE.FAILED ||
                    i.state === STATE.CANCELLED,
            )
        );
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    render() {
        if (!this._visible) return nothing;
        const counts = this._counts;
        return html`
            <div class="mux-tray ${this._collapsed ? 'is-collapsed' : ''}"
                role="region"
                aria-label="${Craft.t('mux', 'Upload progress')}">
                <header class="mux-tray__header">
                    <button type="button" class="mux-tray__collapse-btn"
                        aria-label="${Craft.t('mux', 'Toggle uploads tray')}"
                        @click=${() => { this._collapsed = !this._collapsed; }}>
                        <svg class="mux-tray__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <span class="mux-tray__title">${Craft.t('mux', 'Uploads')}</span>
                    <span class="mux-tray__badge" aria-live="polite">${counts.all}</span>
                    ${this._allSettled
                        ? html`<button type="button" class="mux-tray__dismiss-btn"
                                aria-label="${Craft.t('mux', 'Dismiss')}"
                                @click=${() => this._dismiss()}>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>`
                        : nothing}
                </header>

                ${this._collapsed ? nothing : html`
                    <div class="mux-tray__body">
                        <div class="mux-tray__tabs" role="tablist" aria-label="${Craft.t('mux', 'Filter uploads')}">
                            ${TABS.map((tab) => html`
                                <button role="tab"
                                    aria-selected=${this._activeFilter === tab.filter ? 'true' : 'false'}
                                    class="mux-tray__tab ${this._activeFilter === tab.filter ? 'is-active' : ''}"
                                    data-filter=${tab.filter}
                                    @click=${() => { this._activeFilter = tab.filter; }}>
                                    ${Craft.t('mux', tab.label)}
                                    <span class="mux-tray__tab-count">${counts[tab.filter]}</span>
                                </button>`)}
                        </div>
                        <div class="mux-tray__items" role="list">
                            ${this._filteredItems.map((item) => this._renderItem(item))}
                        </div>
                    </div>`}
            </div>`;
    }

    _renderItem(item) {
        const pct = this._progress.get(item.id) ?? 0;
        const showProgress = item.state === STATE.UPLOADING || item.state === STATE.PAUSED;
        return html`
            <div class="mux-tray__item" role="listitem" data-item-id=${item.id} data-state=${item.state}>
                <div class="mux-tray__item-icon" aria-hidden="true">
                    ${STATE_ICONS[item.state] ?? nothing}
                </div>
                <div class="mux-tray__item-body">
                    ${item.state === STATE.COMPLETE && item.elementCpUrl
                        ? html`<a href=${item.elementCpUrl} class="mux-tray__item-title-link">${item.title}</a>`
                        : html`<span class="mux-tray__item-title">${item.title}</span>`}
                    ${showProgress ? html`
                        <div class="mux-tray__progress-row">
                            <div class="mux-tray__progress-track">
                                <div class="mux-tray__progress-bar" style="--mux-progress: ${pct}%"></div>
                            </div>
                            <span class="mux-tray__progress-label">${Math.round(pct)}%</span>
                        </div>` : nothing}
                </div>
                <div class="mux-tray__item-actions">
                    ${this._renderActions(item)}
                </div>
            </div>`;
    }

    _renderActions(item) {
        const { state, id, elementCpUrl } = item;
        switch (state) {
            case STATE.UPLOADING:
                return html`
                    <button type="button" class="btn small secondary" @click=${() => pauseUpload(id)}>${Craft.t('mux', 'Pause')}</button>
                    <button type="button" class="btn small secondary" @click=${() => cancelUpload(id)}>${Craft.t('mux', 'Cancel')}</button>`;
            case STATE.PAUSED:
                return html`
                    <button type="button" class="btn small submit" @click=${() => resumeUpload(id)}>${Craft.t('mux', 'Resume')}</button>
                    <button type="button" class="btn small secondary" @click=${() => cancelUpload(id)}>${Craft.t('mux', 'Cancel')}</button>`;
            case STATE.COMPLETE:
                return elementCpUrl
                    ? html`<a href=${elementCpUrl} class="btn small submit">${Craft.t('mux', 'View Asset')}</a>`
                    : nothing;
            case STATE.FAILED:
            case STATE.CANCELLED:
                return html`
                    <button type="button" class="btn small submit" @click=${() => retryUpload(id)}>${Craft.t('mux', 'Retry')}</button>
                    <button type="button" class="btn small secondary" @click=${() => removeItem(id)}>${Craft.t('mux', 'Remove')}</button>`;
            default:
                return nothing;
        }
    }

    // ─── Dismiss ──────────────────────────────────────────────────────────────

    _dismiss() {
        if (this._dismissTimer) { clearTimeout(this._dismissTimer); this._dismissTimer = null; }
        this._visible = false;
        this._items = [];
        this._progress.clear();
        this._activeFilter = 'all';
        this._collapsed = false;
    }
}

customElements.define('mux-upload-tray', MuxUploadTray);
