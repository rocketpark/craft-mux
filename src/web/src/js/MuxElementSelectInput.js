/** global: Craft */
/** global: Garnish */
/**
 * Mux select input
 */
import * as UpChunk from '@mux/upchunk';

// Constants
const CONSTANTS = {
    CHUNK_SIZE: (Number.isInteger(Number(window.RocketPark?.Mux?.Settings?.uploadChunkSize)) 
    && Number(window.RocketPark.Mux.Settings.uploadChunkSize) > 0)
    ? Number(window.RocketPark.Mux.Settings.uploadChunkSize)
    : 8192, // 8MB chunks
    DEFAULT_MAX_FILE_SIZE: (Number.isInteger(Number(window.RocketPark?.Mux?.Settings?.maxUploadFileSize)) 
    && Number(window.RocketPark.Mux.Settings.maxUploadFileSize) > 0)
    ? Number(window.RocketPark.Mux.Settings.maxUploadFileSize) * 1024
    : 700 * 1024 * 1024, // 700MB
    DEFAULT_EXTENSIONS: (window.RocketPark?.Mux?.Settings?.defaultExtensions || '').trim() || 'mp4,webm,mov,m4v,mkv',
    API_ENDPOINTS: {
        UPLOAD_ASSET: '/actions/mux/assets/upload-asset',
        CREATE_ASSET: '/actions/mux/assets/create',
        GET_UPLOAD: '/actions/mux/assets/get-upload-by-id',
        GET_ASSET: '/actions/mux/assets/get-asset-by-id',
    },
    ERROR_CODES: {
        MULTIFILES_ERROR: 'MULTIFILES_ERROR',
        EXTENSION_ERROR: 'EXTENSION_ERROR',
        FILE_SIZE_ERROR: 'FILE_SIZE_ERROR',
    }
};

// Helper functions
const Helpers = {
    /**
     * Make API request with CSRF token
     */
    apiRequest: function(endpoint, body = {}) {
        const requestBody = { ...body };
        requestBody[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
        
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody),
        }).then((res) => {
            if (!res.ok) {
                throw new Error(`HTTP error ${res.status}`);
            }
            return res.json();
        });
    },

    /**
     * Get file extension from filename
     */
    getFileExtension: function(filename) {
        return `${filename.toLowerCase().split('.').pop()}`;
    },

    /**
     * Create custom event for upload events
     */
    createUploadEvent: function(type, detail) {
        return new CustomEvent(type, { detail });
    },

    /**
     * Get the current volume UID
     */
    getCurrentVolumeUid: function(sourceKey) {
        if (typeof sourceKey === 'string' && sourceKey.indexOf('volume:') === 0) {
            return sourceKey.replace('volume:', '');
        }
        return sourceKey;
    },
};

// Add this class before the main MuxElementSelectInput class
class UploadProgressManager {
    constructor() {
        this.reset();
    }

    reset() {
        this.totalFiles = 0;
        this.completedFiles = 0;
        this.fileProgresses = {};
    }

    initialize(totalFiles) {
        this.reset();
        this.totalFiles = totalFiles;
        
        // Initialize progress for each file
        for (let i = 0; i < totalFiles; i++) {
            this.fileProgresses[i] = 0;
        }
    }

    updateFileProgress(fileIndex, progress) {
        if (this.fileProgresses.hasOwnProperty(fileIndex)) {
            this.fileProgresses[fileIndex] = progress;
        }
    }

    completeFile(fileIndex) {
        this.completedFiles++;
        this.fileProgresses[fileIndex] = 100;
    }

    getOverallProgress() {
        if (this.totalFiles === 0) return 0;
        
        const totalProgress = Object.values(this.fileProgresses)
            .reduce((sum, progress) => sum + progress, 0);
        return totalProgress / this.totalFiles;
    }

    isComplete() {
        return this.completedFiles === this.totalFiles;
    }
}

export const MuxElementSelectInput = Craft.BaseElementSelectInput.extend({
    $uploadBtn: null,
    uploader: null,
    progressBar: null,
    openPreviewTimeout: null,
    uploadProgress: null,

    init: function () {
        this.base.apply(this, arguments);
        this.uploadProgress = new UploadProgressManager();

        if (this.settings.canUpload) {
            this._attachUploader();
        }

        this.updateAddElementsBtn();

        this.addListener(
            this.$elementsContainer,
            'keydown',
            this._onKeyDown.bind(this)
        );
    },

    elementSelectSettings() {
        return Object.assign(this.base(), {
            makeFocusable: true,
        });
    },

    /**
     * Handle a keypress
     * @private
     */
    _onKeyDown: function (ev) {
        if (ev.keyCode === Garnish.SPACE_KEY && ev.shiftKey) {
            this.openPreview();
            ev.stopPropagation();
            return false;
        }
    },

    clearOpenPreviewTimeout: function () {
        if (this.openPreviewTimeout) {
            clearTimeout(this.openPreviewTimeout);
            this.openPreviewTimeout = null;
        }
    },

    openPreview: function ($element) {
        if (Craft.PreviewFileModal.openInstance) {
            Craft.PreviewFileModal.openInstance.hide();
        } else {
            if (!$element) {
                $element = this.$elements
                    .filter(':focus')
                    .add(this.$elements.has(':focus'));
            }

            if ($element.length) {
                Craft.PreviewFileModal.showForAsset($element, this.elementSelect);
            }
        }
    },

    /**
     * Attach the uploader with drag event handler
     */
    _attachUploader: function () {
        this._createProgressBar();
        this._createUploadButton();
        this._setupUploader();
        this._setupDragAndDrop();
        this._setupFileInput();
        this._setupUploadParams();
    },

    _createProgressBar: function() {
        this.progressBar = new Craft.ProgressBar(
            $('<div class="progress-shade"></div>').appendTo(this.$container)
        );
    },

    _createUploadButton: function() {
        if (!this.$addElementBtn) return;

        const buttonText = this.settings.limit == 1 
            ? Craft.t('mux', 'Upload a video')
            : Craft.t('mux', 'Upload videos');

        this.$uploadBtn = $('<button/>', {
            type: 'button',
            class: 'btn dashed',
            'data-icon': 'upload',
            'aria-label': buttonText,
            'aria-describedby': this.settings.describedBy,
            text: buttonText,
        }).insertAfter(this.$addElementBtn);

        this.$fileInput = $('<input/>', {
            type: 'file',
            class: 'hidden',
            multiple: this.settings.limit != 1,
        }).insertAfter(this.$uploadBtn);

        Garnish.$win.trigger('resize');
    },

    _setupUploader: function() {
        this.uploader = {
            allowKinds: ['video'],
            canAddMoreFiles: this.canAddMoreFiles.bind(this),
            completedFiles: [],
            files: [],
            params: {},
            isLastUpload: () => {
                return this.uploader.completedFiles.length === 0 ||
                    this.uploader.completedFiles.length === this.uploader.files.length;
            },
            setParams: (params) => {
                this.uploader.params = Object.assign(this.uploader.params, params);
            },
        };
    },

    _setupDragAndDrop: function() {
        if (!this.$container) return;

        this.$container.on('drop', (ev) => {
            this.uploader.files = ev.originalEvent.dataTransfer.files;
            this.handleDrop(ev.originalEvent.dataTransfer.files);
            this.$container.removeClass('dragging');
            ev.preventDefault();
            ev.stopPropagation();
        });

        this.$container.on('dragenter dragover dragleave', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            
            if (ev.type === 'dragenter' || ev.type === 'dragover') {
                this.$container.addClass('dragging');
            } else {
                this.$container.removeClass('dragging');
            }
        });
    },

    _setupFileInput: function() {
        if (!this.$fileInput) return;

        this.$fileInput.on('change', (ev) => {
            this.uploader.files = ev.target.files;
            this.handleFileSelect(ev.target.files);
            ev.target.value = '';
        });
    },

    _setupUploadParams: function() {
        const params = {
            fieldId: this.settings.fieldId,
        };
        
        if (this.settings.sourceElementId) {
            params.elementId = this.settings.sourceElementId;
        }
        
        if (this.settings.criteria.siteId) {
            params.siteId = this.settings.criteria.siteId;
        }
        
        this.uploader.setParams(params);

        if (this.$uploadBtn) {
            this.$uploadBtn.on('click', (ev) => {
                this.$uploadBtn.next('input[type=file]').trigger('click');
            });
        }
    },

    handleDrop: function(files) {
        const valid = this.validate(files)
        if (typeof valid !== 'boolean' || typeof valid === 'string') {
            Craft.cp.displayError(
                this.errorMessage(valid)
            );
            return;
        }

        // Add the files to the uploader
        this._uploadFiles(files);
    },

    handleFileSelect: function (files) {
        const valid = this.validate(files);
        if (typeof valid !== 'boolean' || typeof valid === 'string') {
            Craft.cp.displayError(
                this.errorMessage(valid)
            );
            return;
        }

        this._uploadFiles(files);
    },

    enableAddElementsBtn: function () {
        if (this.$uploadBtn) {
            this.$uploadBtn.removeClass('hidden');
        }

        this.base();
    },

    disableAddElementsBtn: function () {
        if (this.$uploadBtn) {
            this.$uploadBtn.addClass('hidden');
        }

        this.base();
    },

    /**
     * Add the freshly uploaded file to the input field.
     */
    selectUploadedFile: function (element) {
        // Check if we're able to add new elements
        if (!this.canAddMoreElements()) {
            return;
        }

        //console.log(element);

        var $newElement = element.$element;

        $newElement.appendTo(this.$elementsContainer);

        var margin = -($newElement.outerWidth() + 10);

        this.$addElementBtn.css('margin-' + Craft.left, margin + 'px');

        var animateCss = {};
        animateCss['margin-' + Craft.left] = 0;
        this.$addElementBtn.velocity(animateCss, 'fast');

        this.addElements($newElement);

        delete this.modal;
    },

    /**
     * Check file extensions.
     */
    checkFileExtensions: function(files) {
        const extensions = this.settings.acceptExtensions || CONSTANTS.DEFAULT_EXTENSIONS;
        const extList = new Set(
            extensions.toLowerCase()
                .split(',')
                .filter(Boolean)
        );
        
        return Array.from(files).every((file) => {
            const ext = Helpers.getFileExtension(file.name);
            return extList.has(ext);
        });
    },

    /**
     * Check file size.
     */
    checkFileSize: function(files) {
        const maxFileSize = this.settings.maxFileSize || CONSTANTS.DEFAULT_MAX_FILE_SIZE;
        
        if (Number.isNaN(maxFileSize)) {
            return true;
        }
        
        return Array.from(files).every((file) => file.size <= maxFileSize);
    },
    
    /**
     * Validate the selected files.
     */
    validate: function(files) {
        const validations = [
            { test: () => this.settings.limit && files.length > this.settings.limit, error: CONSTANTS.ERROR_CODES.MULTIFILES_ERROR },
            { test: () => !this.checkFileExtensions(files), error: CONSTANTS.ERROR_CODES.EXTENSION_ERROR },
            { test: () => !this.checkFileSize(files), error: CONSTANTS.ERROR_CODES.FILE_SIZE_ERROR },
        ];

        for (const validation of validations) {
            if (validation.test()) {
                return validation.error;
            }
        }
        
        return true;
    },

    /**
     * Get error message based on the validation result.
     */
    errorMessage: function (message) {
        const errorMessages = {
            [CONSTANTS.ERROR_CODES.MULTIFILES_ERROR]: Craft.t('app', 'You can only upload {num} file.', {
                num: this.settings.limit,
            }),
            [CONSTANTS.ERROR_CODES.EXTENSION_ERROR]: Craft.t('mux', 'The selected file is not a valid video.'),
            [CONSTANTS.ERROR_CODES.FILE_SIZE_ERROR]: Craft.t('mux', 'The selected file is too large.'),
        };

        return errorMessages[message] || Craft.t('app', 'An error occurred while uploading the file.');
    },

    _uploadFiles: async function (files) {
        this._onUploadStart();
        
        // Initialize progress tracking
        this.uploadProgress.initialize(files.length);
        
        const uploadPromises = Array.from(files).map(async (file, index) => {
            const uploadUrl = await this._getUploadUrl(file);
            // Store volumeUid and folderId for later use
            uploadUrl.volumeUid = Helpers.getCurrentVolumeUid(this.settings.defaultUploadLocationSource);
            uploadUrl.folderId = this.settings.defaultFolderId;
            return this._uploadFile(file, uploadUrl, index);
        });
        
        return Promise.all(uploadPromises);
    },

    _uploadFile: function (file, res, fileIndex) {
        return new Promise((resolve, reject) => {
            const upload = UpChunk.createUpload({
                endpoint: res.url,
                file,
                chunkSize: CONSTANTS.CHUNK_SIZE,
            });
    
            upload.on('error', (err) => {
                this._onUploadFailure(
                    Helpers.createUploadEvent('fileuploadfail', {
                        jqXHR: { responseJSON: err.detail },
                        files: [file],
                    }), 
                    err.detail
                );
                reject(err);
            });
    
            upload.on('progress', (prog) => {
                this.uploadProgress.updateFileProgress(fileIndex, prog.detail);
                this._updateOverallProgress();
            });
    
            upload.on('success', (data) => {
                // Store volumeUid and folderId from the upload response
                res.volumeUid = res.volumeUid || Helpers.getCurrentVolumeUid(this.settings.defaultUploadLocationSource);
                res.folderId = res.folderId || this.settings.defaultFolderId;
                this._processUploadSuccess(file, res, fileIndex, data, resolve);
            });
        });
    },

    _processUploadSuccess: function(file, res, fileIndex, data, resolve) {
        this._getUploadById(res.id)
            .then((data) => this._getAssetById(data.asset_id))
            .then((data) => {
                data.title = file.name;
                // Pass volumeUid and folderId from the upload response
                data.volumeUid = Helpers.getCurrentVolumeUid(this.settings.defaultUploadLocationSource);
                data.folderId = res.folderId;
                return this._createAsset(data);
            })
            .then((data) => {
                this.uploader.completedFiles.push(file.name);
                this.uploadProgress.completeFile(fileIndex);
                this._updateOverallProgress();
                
                this._onUploadComplete(
                    Helpers.createUploadEvent('fileuploaddone', {
                        assetId: data.id,
                    }), 
                    data
                );
                resolve(data);
            })
            .catch((error) => {
                reject(error);
            });
    },

    _createAsset: function (data) {
        // Reset the values so they correspond to the element model
        const body = {
            ...data,
            asset_id: data.id,
            asset_status: data.status,
            title: data.title !== undefined ? data.title : data.id,
        };
        
        // Remove properties that shouldn't be assigned to the Element
        delete body.id;
        delete body.status;

        return Helpers.apiRequest(CONSTANTS.API_ENDPOINTS.CREATE_ASSET, body);
    },

    _getUploadById: function (id) {
        return Helpers.apiRequest(CONSTANTS.API_ENDPOINTS.GET_UPLOAD, { id });
    },

    _getAssetById: function (id) {
        return Helpers.apiRequest(CONSTANTS.API_ENDPOINTS.GET_ASSET, { id });
    },

    /**
     * Get Mux upload URL.
     * @returns {Promise}
     * @private
     */
    _getUploadUrl: function (file) {
        const uploadSource = this.settings.defaultUploadLocationSource;
        const defaultFolderId = this.settings.defaultFolderId;
        const volumeUid = Helpers.getCurrentVolumeUid(uploadSource);

        return Helpers.apiRequest(CONSTANTS.API_ENDPOINTS.UPLOAD_ASSET, { 
            title: file.name,
            volumeUid: volumeUid,
            folderId: defaultFolderId,
        });
    },

    /**
     * On upload start.
     */
    _onUploadStart: function () {
        this.progressBar.$progressBar.css({
            top: Math.round(this.$container.outerHeight() / 2) - 6,
        });

        this.$container.addClass('uploading');
        this.progressBar.resetProgressBar();
        this.progressBar.showProgressBar();
    },

    /**
     * Calculate and update the overall progress across all files
     */
    _updateOverallProgress: function () {
        const progress = this.uploadProgress.getOverallProgress();
        this.progressBar.setProgressPercentage(Math.round(progress));
    },

    /**
     * On a file being uploaded.
     */
    _onUploadComplete: function (event, data = null) {
        const result = event instanceof CustomEvent ? event.detail : data.result;
        Craft.sendActionRequest('POST', 'app/render-elements', {
            data: {
                elements: [
                    {
                        type: 'rocketpark\\mux\\elements\\MuxAsset',
                        id: result.assetId,
                        siteId: this.settings.criteria.siteId,
                        instances: [
                            {
                                context: 'field',
                                ui: ['list', 'large'].includes(this.settings.viewMode)
                                    ? 'chip'
                                    : 'card',
                                size: this.settings.viewMode === 'large' ? 'large' : 'small',
                            },
                        ],
                    },
                ],
            },
        })
            .then(async ({ data }) => {
                const elementInfo = Craft.getElementInfo(
                    data.elements[result.assetId][0]
                );
                this.selectElements([elementInfo]);

                await Craft.appendHeadHtml(data.headHtml);
                await Craft.appendBodyHtml(data.bodyHtml);

                // Last file
                //console.log(this.uploader);
                if (this.uploader.isLastUpload()) {
                    this.progressBar.hideProgressBar();
                    this.$container.removeClass('uploading');
                    this.$container.trigger('change');
                }
            })
            .catch((error) => {
                if (error && error.response) {
                    Craft.cp.displayError(error.response.message || error.response.statusText);
                } else {
                    Craft.cp.displayError();
                    throw error;
                }
            });

        Craft.cp.runQueue();
    },

    /**
     * On Upload Failure.
     */
    _onUploadFailure: function (event, data = null) {
        const response =
            event instanceof CustomEvent ? event.detail : data?.jqXHR?.responseJSON;

        let { message, filename, errors } = response || {};

        filename = filename || data?.files?.[0].name;

        let errorMessages = errors ? Object.values(errors).flat() : [];

        if (!message) {
            if (errorMessages.length) {
                message = errorMessages.join('\n');
            } else if (filename) {
                message = Craft.t('app', 'Upload failed for "{filename}".', { filename });
            } else {
                message = Craft.t('app', 'Upload failed.');
            }
        }

        Craft.cp.displayError(message);
        this.progressBar.hideProgressBar();
        this.$container.removeClass('uploading');
    },

    /**
     * We have to take into account files about to be added as well
     */
    canAddMoreFiles: function (slotsTaken) {
        return (
            !this.settings.limit ||
            this.$elements.length + slotsTaken < this.settings.limit
        );
    },
});
