import { LitElement, html, nothing } from 'lit';
import { startBatch } from './MuxUploadManager.js';

const MAX_FILE_SIZE = (() => {
    const s = window.RocketPark?.Mux?.Settings?.maxUploadFileSize;
    return s ? Number(s) * 1024 : 700 * 1024 * 1024;
})();

const ALLOWED_EXTENSIONS = (
    window.RocketPark?.Mux?.Settings?.defaultExtensions || 'MP4,MOV,MKV,WEBM,M4V,MP3,M4A,WAV,FLAC,AAC,OGG,OPUS'
).toLowerCase().split(',').map((e) => e.trim());

// Mirrors Languages::SUBTITLE_GENERATION_LANGUAGES from the PHP constants
const CAPTION_LANGUAGES = [
    { value: '', label: 'Auto detect language', beta: false },
    { value: 'en', label: 'English', beta: false },
    { value: 'es', label: 'Spanish', beta: false },
    { value: 'it', label: 'Italian', beta: false },
    { value: 'pt', label: 'Portuguese', beta: false },
    { value: 'de', label: 'German', beta: false },
    { value: 'fr', label: 'French', beta: false },
    { value: 'pl', label: 'Polish', beta: true },
    { value: 'ru', label: 'Russian', beta: true },
    { value: 'nl', label: 'Dutch', beta: true },
    { value: 'ca', label: 'Catalan', beta: true },
    { value: 'tr', label: 'Turkish', beta: true },
    { value: 'sv', label: 'Swedish', beta: true },
    { value: 'uk', label: 'Ukrainian', beta: true },
    { value: 'no', label: 'Norwegian', beta: true },
    { value: 'fi', label: 'Finnish', beta: true },
    { value: 'sk', label: 'Slovak', beta: true },
    { value: 'el', label: 'Greek', beta: true },
    { value: 'cs', label: 'Czech', beta: true },
    { value: 'hr', label: 'Croatian', beta: true },
    { value: 'da', label: 'Danish', beta: true },
    { value: 'ro', label: 'Romanian', beta: true },
    { value: 'bg', label: 'Bulgarian', beta: true },
];

const STEP_LABELS = ['Select', 'Details', 'Settings'];

// SVG icon constants as Lit TemplateResults
const ICON_CLOSE = html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;
const ICON_UPLOAD = html`<svg class="mux-wizard__dropzone-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M16 28l8-8 8 8"/><path d="M24 20v16"/><rect x="6" y="6" width="36" height="36" rx="4"/></svg>`;
const ICON_URL = html`<svg class="mux-wizard__url-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>`;
const ICON_VIDEO = html`<svg class="mux-wizard__thumb-placeholder" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="2"/><polygon points="10 8 16 12 10 16 10 8"/></svg>`;
const ICON_AUDIO = html`<svg class="mux-wizard__thumb-placeholder" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>`;
const AUDIO_EXTENSIONS = ['mp3', 'm4a', 'wav', 'flac', 'aac', 'ogg', 'opus'];
const ICON_EDIT = html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
const ICON_DELETE = html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>`;
const ICON_INFO = html`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><circle cx="12" cy="7.5" r="0.9" fill="currentColor" stroke="none"/></svg>`;

class MuxUploadWizard extends LitElement {
    static properties = {
        _step:        { state: true },
        _items:       { state: true },
        _settings:    { state: true },
        _isDragover:  { state: true },
        _dropError:   { state: true },
        _urlValue:    { state: true },
        _urlError:    { state: true },
        _editingItem: { state: true },
        _editValue:   { state: true },
        _qualityTipOpen: { state: true },
    };

    // Light DOM — Craft CP class-based styles apply directly
    createRenderRoot() { return this; }

    constructor() {
        super();
        this._step = 1;
        this._items = [];
        this._thumbnails = new Map(); // File → dataUrl; not reactive, triggers requestUpdate
        this._titles = new Map();     // item → string; not reactive, triggers requestUpdate
        this._settings = {
            videoQuality: 'plus',
            playbackPolicy: 'public',
            normalizeAudio: false,
            autoGenerateCaptions: true,
            captionsLanguage: '',
            watermark: {
                enabled: false,
                url: '',
                verticalAlign: 'top',
                verticalMargin: '0',
                horizontalAlign: 'left',
                horizontalMargin: '0',
                width: '',
                height: '',
                opacityPct: 75,
            },
        };
        this._isDragover = false;
        this._dropError = '';
        this._urlValue = '';
        this._urlError = '';
        this._editingItem = null;
        this._editValue = '';
        this._qualityTipOpen = false;
        this._onDocClickForTip = (e) => {
            if (!e.target.closest('.mux-wizard__info-wrap')) {
                this._qualityTipOpen = false;
            }
        };
    }

    get _dialog() {
        return this.querySelector('dialog');
    }

    open() {
        this._reset();
        // Wait for Lit to finish the re-render before calling showModal
        this.updateComplete.then(() => this._dialog?.showModal());
    }

    _reset() {
        this._step = 1;
        this._items = [];
        this._thumbnails.clear();
        this._titles.clear();
        this._isDragover = false;
        this._dropError = '';
        this._urlValue = '';
        this._urlError = '';
        this._editingItem = null;
        this._editValue = '';
        this._qualityTipOpen = false;
    }

    updated(changed) {
        // Focus the title input whenever we enter edit mode
        if (changed.has('_editingItem') && this._editingItem !== null) {
            const input = this.querySelector('.mux-wizard__title-input');
            input?.focus();
            input?.select();
        }

        // Close the quality tip on outside click. Craft's own `.info`/InfoIcon HUD always
        // (re-)appends itself to <body>, which renders *underneath* our native <dialog>
        // (shown via showModal(), so it lives in the browser's top layer) — so we can't use
        // it here and render our own small popover inside the dialog instead.
        if (changed.has('_qualityTipOpen')) {
            if (this._qualityTipOpen) {
                document.addEventListener('click', this._onDocClickForTip, { capture: true });
            } else {
                document.removeEventListener('click', this._onDocClickForTip, { capture: true });
            }
        }
    }

    render() {
        return html`
            <dialog
                class="mux-wizard"
                aria-labelledby="mux-wizard-heading"
                @click=${(e) => { if (e.target === this._dialog) this._dialog.close(); }}
                @close=${() => this._reset()}
            >
                <div class="mux-wizard__inner">
                    ${this._renderHeader()}
                    ${this._renderSteps()}
                    <div class="mux-wizard__panels">
                        ${this._step === 1 ? this._renderStep1() : nothing}
                        ${this._step === 2 ? this._renderStep2() : nothing}
                        ${this._step === 3 ? this._renderStep3() : nothing}
                    </div>
                    ${this._renderFooter()}
                </div>
            </dialog>`;
    }

    _renderHeader() {
        return html`
            <header class="mux-wizard__header">
                <h2 class="mux-wizard__heading" id="mux-wizard-heading">${Craft.t('mux', 'Upload Files')}</h2>
                <button type="button" class="mux-wizard__close-btn" aria-label="${Craft.t('mux', 'Cancel')}"
                    @click=${() => this._dialog?.close()}>
                    ${ICON_CLOSE}
                </button>
            </header>`;
    }

    _renderSteps() {
        return html`
            <ol class="mux-wizard__steps" aria-label="${Craft.t('mux', 'Steps')}">
                ${STEP_LABELS.map((label, i) => {
                    const s = i + 1;
                    return html`
                        <li class="mux-wizard__step ${this._step === s ? 'is-active' : ''} ${this._step > s ? 'is-done' : ''}"
                            data-step="${s}"
                            aria-current=${this._step === s ? 'step' : null}>
                            <span class="mux-wizard__step-label">${Craft.t('mux', label)}</span>
                            <span class="mux-wizard__step-indicator"></span>
                        </li>`;
                })}
            </ol>`;
    }

    _renderStep1() {
        return html`
            <div class="mux-wizard__dropzone ${this._isDragover ? 'is-dragover' : ''}"
                role="button" tabindex="0"
                aria-label="${Craft.t('mux', 'Drop video or audio files here or browse')}"
                @click=${(e) => { if (e.target === e.currentTarget) this.querySelector('.mux-wizard__file-input')?.click(); }}
                @keydown=${(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.querySelector('.mux-wizard__file-input')?.click(); } }}
                @dragover=${(e) => { e.preventDefault(); this._isDragover = true; }}
                @dragleave=${(e) => { if (!e.currentTarget.contains(e.relatedTarget)) this._isDragover = false; }}
                @drop=${(e) => { e.preventDefault(); this._isDragover = false; if (e.dataTransfer.files.length) this._onFilesSelected(Array.from(e.dataTransfer.files)); }}>
                <input type="file" multiple accept="video/*,audio/*" class="mux-wizard__file-input" tabindex="-1" aria-hidden="true"
                    @change=${(e) => { if (e.target.files.length) this._onFilesSelected(Array.from(e.target.files)); e.target.value = ''; }}>
                ${ICON_UPLOAD}
                <p class="mux-wizard__dropzone-label">${Craft.t('mux', 'Drag and drop video or audio files here')}</p>
                <button type="button" class="mux-wizard__browse-btn"
                    @click=${(e) => { e.stopPropagation(); this.querySelector('.mux-wizard__file-input')?.click(); }}>
                    ${Craft.t('mux', 'Upload a local file')}
                </button>
            </div>
            ${this._dropError ? html`<p class="mux-wizard__drop-error">${this._dropError}</p>` : nothing}

            <div class="mux-wizard__url-section">
                <p class="mux-wizard__url-label">${Craft.t('mux', 'Upload from a public URL')}</p>
                <div class="mux-wizard__url-row">
                    <input type="url" class="mux-wizard__url-input text" placeholder="https://"
                        aria-label="${Craft.t('mux', 'Video URL')}"
                        .value=${this._urlValue}
                        @input=${(e) => { this._urlValue = e.target.value; }}
                        @keydown=${(e) => { if (e.key === 'Enter') { e.preventDefault(); this._addUrl(); } }}>
                    <button type="button" class="btn mux-wizard__url-add-btn" @click=${() => this._addUrl()}>
                        ${Craft.t('mux', 'Add')}
                    </button>
                </div>
                ${this._urlError ? html`<p class="mux-wizard__url-error">${this._urlError}</p>` : nothing}
            </div>`;
    }

    _renderStep2() {
        return html`
            <div class="mux-wizard__file-list" role="list">
                ${this._items.map((item) => this._renderFileRow(item))}
            </div>`;
    }

    _renderFileRow(item) {
        const title = this._titles.get(item) || '';
        const isEditing = this._editingItem === item;
        return html`
            <div class="mux-wizard__file-row" role="listitem">
                <div class="mux-wizard__file-thumb">${this._renderThumb(item)}</div>
                <div class="mux-wizard__file-body">
                    <div class="mux-wizard__file-title-wrap">
                        ${isEditing ? html`
                            <input type="text" class="mux-wizard__title-input text"
                                .value=${this._editValue}
                                @input=${(e) => { this._editValue = e.target.value; }}
                                @blur=${() => this._commitEdit(item)}
                                @keydown=${(e) => {
                                    if (e.key === 'Enter') { e.preventDefault(); e.target.blur(); }
                                    if (e.key === 'Escape') { this._editValue = this._titles.get(item) || ''; e.target.blur(); }
                                }}>` : html`
                            <span class="mux-wizard__file-title" @click=${() => this._startEdit(item)}>${title}</span>
                            <button type="button" class="mux-wizard__edit-btn" aria-label="${Craft.t('mux', 'Edit title')}"
                                @click=${() => this._startEdit(item)}>${ICON_EDIT}</button>`}
                    </div>
                </div>
                <button type="button" class="mux-wizard__delete-btn" aria-label="${Craft.t('mux', 'Remove')}"
                    @click=${() => this._removeItem(item)}>${ICON_DELETE}</button>
            </div>`;
    }

    _renderThumb(item) {
        if (item.type === 'url') return ICON_URL;
        const dataUrl = this._thumbnails.get(item.file);
        if (dataUrl) return html`<img src=${dataUrl} alt="" class="mux-wizard__thumb-img">`;
        return this._isAudioFile(item.file) ? ICON_AUDIO : ICON_VIDEO;
    }

    _isAudioFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        return AUDIO_EXTENSIONS.includes(ext);
    }

    _renderStep3() {
        const s = this._settings;
        return html`
            <div class="mux-wizard__settings-form">
                <div class="field">
                    <div class="heading">
                        <label for="mux-wiz-quality">${Craft.t('mux', 'Video Quality')}</label>
                        <span class="mux-wizard__info-wrap">
                            <button type="button" class="mux-wizard__info-btn"
                                aria-label="${Craft.t('mux', 'More info')}"
                                aria-expanded="${this._qualityTipOpen}"
                                @click=${(e) => { e.stopPropagation(); this._qualityTipOpen = !this._qualityTipOpen; }}>${ICON_INFO}</button>
                            ${this._qualityTipOpen ? html`
                                <div class="mux-wizard__info-popover" role="tooltip">
                                    ${Craft.t('mux', "Basic is free but has a reduced quality ladder and no live streaming or DRM support. Plus (recommended) uses Mux's per-title encoding at standard quality, billed per minute of video. Premium costs more per minute but is tuned for top-tier content like live sports or studio releases.")}
                                </div>
                            ` : nothing}
                        </span>
                    </div>
                    <div class="input ltr"><div class="select">
                        <select id="mux-wiz-quality" .value=${s.videoQuality}
                            @change=${(e) => this._setSetting('videoQuality', e.target.value)}>
                            <option value="basic" title="${Craft.t('mux', 'Free encoding. Reduced quality ladder. No live streaming or DRM support.')}">${Craft.t('mux', 'Basic (free)')}</option>
                            <option value="plus" title="${Craft.t('mux', "Mux's recommended default. Standard quality per-title encoding, billed per minute.")}">${Craft.t('mux', 'Plus (standard, recommended)')}</option>
                            <option value="premium" title="${Craft.t('mux', 'Highest quality and extended encoding ladder, for premium content. Highest per-minute cost.')}">${Craft.t('mux', 'Premium (highest quality)')}</option>
                        </select>
                    </div></div>
                </div>

                <div class="field">
                    <div class="heading"><label>${Craft.t('mux', 'Playback Policy')}</label></div>
                    <div class="input ltr mux-wizard__radio-group">
                        <label class="mux-wizard__radio-label">
                            <input type="radio" name="mux-wiz-policy" value="public"
                                ?checked=${s.playbackPolicy === 'public'}
                                @change=${() => this._setSetting('playbackPolicy', 'public')}>
                            ${Craft.t('mux', 'Public')}
                        </label>
                        <label class="mux-wizard__radio-label">
                            <input type="radio" name="mux-wiz-policy" value="signed"
                                ?checked=${s.playbackPolicy === 'signed'}
                                @change=${() => this._setSetting('playbackPolicy', 'signed')}>
                            ${Craft.t('mux', 'Signed')}
                        </label>
                    </div>
                </div>

                <div class="field">
                    <div class="heading"><label for="mux-wiz-normalize">${Craft.t('mux', 'Normalize Audio')}</label></div>
                    <div class="input ltr"><label class="mux-wizard__toggle">
                        <input type="checkbox" id="mux-wiz-normalize"
                            ?checked=${s.normalizeAudio}
                            @change=${(e) => this._setSetting('normalizeAudio', e.target.checked)}>
                        <span class="mux-wizard__toggle-track"><span class="mux-wizard__toggle-thumb"></span></span>
                    </label></div>
                </div>

                <div class="field">
                    <div class="heading"><label for="mux-wiz-captions">${Craft.t('mux', 'Auto-Generate Captions')}</label></div>
                    <div class="input ltr"><label class="mux-wizard__toggle">
                        <input type="checkbox" id="mux-wiz-captions"
                            ?checked=${s.autoGenerateCaptions}
                            @change=${(e) => this._setSetting('autoGenerateCaptions', e.target.checked)}>
                        <span class="mux-wizard__toggle-track"><span class="mux-wizard__toggle-thumb"></span></span>
                    </label></div>
                </div>

                ${s.autoGenerateCaptions ? html`
                    <div class="field mux-wizard__lang-field">
                        <div class="heading"><label for="mux-wiz-lang">${Craft.t('mux', 'Caption Language')}</label></div>
                        <div class="input ltr"><div class="select">
                            <select id="mux-wiz-lang" .value=${s.captionsLanguage}
                                @change=${(e) => this._setSetting('captionsLanguage', e.target.value)}>
                                ${CAPTION_LANGUAGES.map((l) => html`
                                    <option value=${l.value}>
                                        ${l.beta ? `${Craft.t('mux', l.label)} (beta)` : Craft.t('mux', l.label)}
                                    </option>`)}
                            </select>
                        </div></div>
                    </div>` : nothing}

                ${this._renderWatermarkSection()}
            </div>`;
    }

    _renderFooter() {
        const isLast = this._step === 3;
        const nextDisabled = this._step === 2 && this._items.length === 0;
        return html`
            <footer class="mux-wizard__footer">
                ${this._step > 1 ? html`
                    <button type="button" class="btn mux-wizard__back-btn"
                        @click=${() => { this._step--; }}>
                        ${Craft.t('mux', 'Back')}
                    </button>` : nothing}
                <div class="mux-wizard__footer-spacer"></div>
                <button type="button" class="btn submit mux-wizard__next-btn"
                    ?disabled=${nextDisabled}
                    @click=${() => isLast ? this._startUploads() : this._step++}>
                    ${isLast ? Craft.t('mux', 'Start Uploads') : Craft.t('mux', 'Next')}
                </button>
            </footer>`;
    }

    _renderWatermarkSection() {
        const wm = this._settings.watermark;
        return html`
            <div class="mux-wizard__watermark-section">
                <div class="mux-wizard__watermark-header">
                    <span class="mux-wizard__watermark-label">${Craft.t('mux', 'Watermark')}</span>
                    <button type="button" role="switch"
                        class="mux-wizard__toggle-btn ${wm.enabled ? 'is-on' : ''}"
                        aria-checked=${wm.enabled ? 'true' : 'false'}
                        @click=${() => this._setWatermark('enabled', !wm.enabled)}>
                        <span class="mux-wizard__toggle-track"><span class="mux-wizard__toggle-thumb"></span></span>
                    </button>
                </div>

                ${wm.enabled ? html`
                    <div class="mux-wizard__watermark-body">
                        <div class="field mux-wizard__watermark-url-field">
                            <div class="heading"><label class="mux-wizard__section-subheading">${Craft.t('mux', 'Watermark URL')}</label></div>
                            <div class="input ltr">
                                <input type="url" class="text fullwidth" placeholder="https://"
                                    .value=${wm.url}
                                    @input=${(e) => this._setWatermark('url', e.target.value)}>
                            </div>
                        </div>

                        <div class="mux-wizard__watermark-grid">
                            <div class="mux-wizard__watermark-panel">
                                <span class="mux-wizard__panel-heading">${Craft.t('mux', 'Position')}</span>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t('mux', 'Vertical')}</label></div>
                                    <div class="mux-wizard__align-group" role="group" aria-label="${Craft.t('mux', 'Vertical alignment')}">
                                        ${[
                                            { value: 'top',    label: 'Top',    icon: html`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="9" y1="2" x2="9" y2="8"/><rect x="4" y="8" width="10" height="8" rx="1.5"/><line x1="2" y1="2" x2="16" y2="2" stroke-linecap="round"/></svg>` },
                                            { value: 'middle', label: 'Center', icon: html`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="9" y1="2" x2="9" y2="7"/><rect x="4" y="7" width="10" height="4" rx="1"/><line x1="9" y1="11" x2="9" y2="16"/><line x1="2" y1="9" x2="16" y2="9"/></svg>` },
                                            { value: 'bottom', label: 'Bottom', icon: html`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="4" y="2" width="10" height="8" rx="1.5"/><line x1="9" y1="10" x2="9" y2="16"/><line x1="2" y1="16" x2="16" y2="16" stroke-linecap="round"/></svg>` },
                                        ].map((opt) => html`
                                            <button type="button"
                                                class="mux-wizard__align-btn ${wm.verticalAlign === opt.value ? 'is-active' : ''}"
                                                title=${Craft.t('mux', opt.label)}
                                                aria-pressed=${wm.verticalAlign === opt.value ? 'true' : 'false'}
                                                @click=${() => this._setWatermark('verticalAlign', opt.value)}>
                                                ${opt.icon}
                                            </button>`)}
                                    </div>
                                </div>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t('mux', 'V. margin')} <span class="mux-wizard__unit">px</span></label></div>
                                    <input type="text" class="text mux-wizard__margin-input"
                                        .value=${wm.verticalMargin}
                                        @input=${(e) => this._setWatermark('verticalMargin', e.target.value)}>
                                </div>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t('mux', 'Horizontal')}</label></div>
                                    <div class="mux-wizard__align-group" role="group" aria-label="${Craft.t('mux', 'Horizontal alignment')}">
                                        ${[
                                            { value: 'left',   label: 'Left',   icon: html`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="2" y1="2" x2="2" y2="16" stroke-linecap="round"/><line x1="2" y1="9" x2="8" y2="9"/><rect x="8" y="4" width="8" height="10" rx="1.5"/></svg>` },
                                            { value: 'center', label: 'Center', icon: html`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="9" y1="2" x2="9" y2="16"/><rect x="5" y="5" width="8" height="8" rx="1"/></svg>` },
                                            { value: 'right',  label: 'Right',  icon: html`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="16" y1="2" x2="16" y2="16" stroke-linecap="round"/><line x1="16" y1="9" x2="10" y2="9"/><rect x="2" y="4" width="8" height="10" rx="1.5"/></svg>` },
                                        ].map((opt) => html`
                                            <button type="button"
                                                class="mux-wizard__align-btn ${wm.horizontalAlign === opt.value ? 'is-active' : ''}"
                                                title=${Craft.t('mux', opt.label)}
                                                aria-pressed=${wm.horizontalAlign === opt.value ? 'true' : 'false'}
                                                @click=${() => this._setWatermark('horizontalAlign', opt.value)}>
                                                ${opt.icon}
                                            </button>`)}
                                    </div>
                                </div>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t('mux', 'H. margin')} <span class="mux-wizard__unit">px</span></label></div>
                                    <input type="text" class="text mux-wizard__margin-input"
                                        .value=${wm.horizontalMargin}
                                        @input=${(e) => this._setWatermark('horizontalMargin', e.target.value)}>
                                </div>
                            </div>

                            <div class="mux-wizard__watermark-panel">
                                <span class="mux-wizard__panel-heading">${Craft.t('mux', 'Size & Appearance')}</span>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t('mux', 'Width')} <span class="mux-wizard__unit">px</span></label></div>
                                    <input type="text" class="text mux-wizard__size-input"
                                        .value=${wm.width}
                                        placeholder="auto"
                                        @input=${(e) => this._setWatermark('width', e.target.value)}>
                                </div>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t('mux', 'Height')} <span class="mux-wizard__unit">px</span></label></div>
                                    <input type="text" class="text mux-wizard__size-input"
                                        .value=${wm.height}
                                        placeholder="auto"
                                        @input=${(e) => this._setWatermark('height', e.target.value)}>
                                </div>

                                <div class="field">
                                    <div class="heading">
                                        <label>${Craft.t('mux', 'Opacity')}</label>
                                        <span class="mux-wizard__opacity-value">${wm.opacityPct}%</span>
                                    </div>
                                    <div class="mux-wizard__opacity-row">
                                        <input type="range" class="mux-wizard__opacity-slider"
                                            min="0" max="100" step="1"
                                            .value=${String(wm.opacityPct)}
                                            @input=${(e) => this._setWatermark('opacityPct', Number(e.target.value))}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>` : nothing}
            </div>`;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    _setSetting(key, val) {
        this._settings = { ...this._settings, [key]: val };
    }

    _setWatermark(key, val) {
        this._settings = {
            ...this._settings,
            watermark: { ...this._settings.watermark, [key]: val },
        };
    }

    _startEdit(item) {
        this._editingItem = item;
        this._editValue = this._titles.get(item) || '';
        // Focus handled in updated() lifecycle
    }

    _commitEdit(item) {
        const val = this._editValue.trim() || (item.type === 'file' ? item.file.name : item.url);
        this._titles.set(item, val);
        this._editingItem = null;
        this.requestUpdate(); // titles Map changed outside reactive system
    }

    _removeItem(item) {
        this._items = this._items.filter((i) => i !== item);
        if (!this._items.length) this._step = 1;
    }

    _onFilesSelected(incoming) {
        const valid = incoming.filter((f) => this._isValidFile(f));
        const invalid = incoming.filter((f) => !this._isValidFile(f));

        this._dropError = invalid.length
            ? Craft.t('mux', '{n} file(s) skipped: unsupported format or too large.', { n: invalid.length })
            : '';

        if (!valid.length) return;

        const newItems = [];
        valid.forEach((f) => {
            if (!this._items.some((i) => i.type === 'file' && i.file === f)) {
                const item = { type: 'file', file: f };
                newItems.push(item);
                this._titles.set(item, f.name.replace(/\.[^.]+$/, ''));
                // Start generating thumbnail immediately so it may be ready by step 2
                this._generateThumb(f).then((dataUrl) => {
                    this._thumbnails.set(f, dataUrl);
                    this.requestUpdate();
                });
            }
        });

        if (newItems.length) {
            this._items = [...this._items, ...newItems];
            this._step = 2;
        }
    }

    _addUrl() {
        const raw = this._urlValue.trim();
        if (!raw) return;
        if (!this._isValidUrl(raw)) {
            this._urlError = Craft.t('mux', 'Please enter a valid HTTP or HTTPS URL.');
            return;
        }
        this._urlError = '';
        const urlItem = { type: 'url', url: raw };
        this._items = [...this._items, urlItem];
        this._titles.set(urlItem, this._titleFromUrl(raw));
        this._urlValue = '';
        this._step = 2;
    }

    _startUploads() {
        if (!this._items.length) return;
        this._dialog?.close();
        startBatch(this._items, this._titles, { ...this._settings });
    }

    _isValidUrl(str) {
        try {
            const u = new URL(str);
            return u.protocol === 'http:' || u.protocol === 'https:';
        } catch (_) { return false; }
    }

    _titleFromUrl(url) {
        try {
            const u = new URL(url);
            const parts = u.pathname.split('/').filter(Boolean);
            const last = parts.at(-1) || '';
            return last.replace(/\.[^.]+$/, '') || u.hostname || url;
        } catch (_) { return url; }
    }

    _isValidFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        return ALLOWED_EXTENSIONS.includes(ext) && file.size <= MAX_FILE_SIZE;
    }

    _generateThumb(file) {
        if (this._isAudioFile(file)) {
            // No video frame to extract — fall back to the generic audio icon in _renderThumb.
            return Promise.resolve(null);
        }
        return new Promise((resolve) => {
            const video = document.createElement('video');
            const url = URL.createObjectURL(file);
            video.src = url;
            video.preload = 'metadata';
            video.muted = true;
            const cleanup = () => URL.revokeObjectURL(url);
            video.addEventListener('error', () => { cleanup(); resolve(null); }, { once: true });
            video.addEventListener('loadedmetadata', () => {
                video.currentTime = Math.min(1, video.duration * 0.1);
            }, { once: true });
            video.addEventListener('seeked', () => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 160;
                    canvas.height = 90;
                    canvas.getContext('2d').drawImage(video, 0, 0, 160, 90);
                    cleanup();
                    resolve(canvas.toDataURL('image/jpeg', 0.75));
                } catch (_) { cleanup(); resolve(null); }
            }, { once: true });
        });
    }
}

customElements.define('mux-upload-wizard', MuxUploadWizard);
