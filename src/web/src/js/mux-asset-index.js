(function ($) {
    'use strict';

    Craft.MuxAssetIndex = Craft.BaseElementIndex.extend({
        $includeSubfoldersContainer: null,
        $includeSubfoldersCheckbox: null,
        $showingIncludeSubfoldersCheckbox: false,
        $progressBar: null,
        progressBar: null,
        promptHandler: null,
        currentFolderId: null,
        $listedFolders: null,
        itemDrag: null,

        $newFolderBtn: null,
        $newFolderModal: null,
        $deleteFolderModal: null,
        $moveModal: null,
        $renameModal: null,
        $sourcePathContainer: null,
        $folderActions: null,
        selectedElements: [],
        _includeSubfolders: null,


        init: function (elementType, $container, settings) {
            settings = Object.assign({}, Craft.MuxAssetIndex.defaults, settings);
            this.setSettings(settings, Craft.BaseElementIndex.defaults);

            this.promptHandler = new Craft.PromptHandler();

            if (this.settings.context === 'index') {
                // remember whether includeSubfolders was set in the query string,
                // before the URL is updated
                const queryParams = Craft.getQueryParams();
                if (queryParams.includeSubfolders !== undefined) {
                    this._includeSubfolders = !!parseInt(queryParams.includeSubfolders);
                }
            }

            this.base(elementType, $container, this.settings);

            if (this.settings.context === 'index') {
                this.itemDrag = new Garnish.DragDrop({
                    activeDropTargetClass: 'sel',
                    minMouseDist: 10,
                    hideDraggee: false,
                    moveHelperToCursor: true,
                    activeDropTargetClass: 'active-drop-target',
                    handle: (item) => $(item).closest('tr,li'),
                    filter: () => {
                        const $container = this.itemDrag.$targetItem.closest('tr,li');
                        this.view.elementSelect.selectItem($container);
                        return this._findDraggableItems(this.view.getSelectedElements());
                    },
                    helper: ($item, index) =>
                        $('<div class="offset-drag-helper"/>')
                            .append($item)
                            .css({
                                opacity: Math.max(0.9 - 0.05 * index, 0),
                                width: '',
                                height: '',
                            }),
                    dropTargets: () => {
                        // volume sources
                        let $dropTargets = $(
                            this.$visibleSources
                                .toArray()
                                .filter(
                                    (source) =>
                                        Garnish.hasAttr(source, 'data-folder-id')
                                )
                        );
                        if (this.sourcePath.length <= 1) {
                            // exclude the current source since we're already at the root of it
                            $dropTargets = $dropTargets.not(this.$source);
                        } else {
                            // parent folders in the source path
                            for (let i = 0; i < this.sourcePath.length - 1; i++) {
                                const step = this.sourcePath[i];
                                if (step.folderId) {
                                    $dropTargets = $dropTargets.add(step.$btn);
                                }
                            }
                        }
                        // folders in the elements listing
                        if (this.$listedFolders) {
                            $dropTargets = $dropTargets
                                .add(
                                    this.$listedFolders
                                        .filter('[data-folder-id]')
                                        .closest('tr,li')
                                )
                                .not(this.view.getSelectedElements());
                        }
                        return $dropTargets;
                    },
                    onDragStart: () => {
                        Garnish.$bod.addClass('dragging');
                        this.itemDrag.$draggee.closest('tr,li').addClass('draggee');
                    },
                    onDragStop: async () => {
                        Garnish.$bod.removeClass('dragging');

                        const $draggee = this.itemDrag.$draggee;
                        const targetFolderId = this._targetFolderId(
                            this.itemDrag.$activeDropTarget
                        );

                        if (!targetFolderId) {
                            $draggee.closest('tr,li').removeClass('draggee');
                            this.itemDrag.returnHelpersToDraggees();
                            return;
                        }

                        this.itemDrag.fadeOutHelpers();

                        const $folders = $draggee.filter('[data-is-folder]');
                        const $assets = $draggee.not($folders);
                        const folderIds = $folders.toArray().map((item) => {
                            return parseInt($(item).data('folder-id'));
                        });
                        const assetIds = $assets.toArray().map((item) => {
                            return parseInt($(item).data('id'));
                        });

                        const mover = new Craft.MuxAssetMover();

                        const moveParams = await mover.getMoveParams(folderIds, assetIds);
                        if (!moveParams.proceed) {
                            $draggee.closest('tr,li').removeClass('draggee');
                            return;
                        }

                        const totalFoldersMoved = await mover.moveFolders(
                            folderIds,
                            targetFolderId,
                            this.currentFolderId
                        );
                        const totalAssetsMoved = await mover.moveAssets(
                            assetIds,
                            targetFolderId,
                            this.currentFolderId
                        );
                        const totalItemsMoved = totalFoldersMoved + totalAssetsMoved;
                        if (totalItemsMoved) {
                            mover.successNotice(
                                moveParams,
                                Craft.t(
                                    'app',
                                    '{totalItems, plural, =1{Item} other{Items}} moved.',
                                    {
                                        totalItems: totalItemsMoved,
                                    }
                                )
                            );
                            Craft.elementIndex.updateElements(true);
                        } else {
                            $draggee.closest('tr,li').removeClass('draggee');
                        }
                    },
                });

                this.addListener(Garnish.$win, 'resize,scroll', '_positionProgressBar');
            } else {
                this.addListener(this.$main, 'scroll', '_positionProgressBar');

                if (this.settings.modal) {
                    this.settings.modal.on(
                        'updateSizeAndPosition',
                        this._positionProgressBar.bind(this)
                    );
                }
            }

            //this.addListener(this.$elements, 'keydown', this._onKeyDown.bind(this));
        },

        _findDraggableItems: function ($items) {
            return $(
                $items
                    .toArray()
                    .map((item) => $(item).find('.element:first')[0])
                    .filter((item) => item && Garnish.hasAttr(item, 'data-movable'))
            );
        },

        _targetFolderId: function ($dropTarget) {
            if (!$dropTarget || !$dropTarget.length) {
                return false;
            }

            // source?
            if ($dropTarget.is(this.$visibleSources)) {
                return $dropTarget.data('folder-id');
            }

            // source path step?
            for (let i = 0; i < this.sourcePath.length - 1; i++) {
                const step = this.sourcePath[i];
                if ($dropTarget.is(step.$btn)) {
                    return step.folderId;
                }
            }

            // folder in the element listing?
            return $dropTarget.find('.element:first').data('folder-id') || false;
        },

        afterInit: function () {
        
            // Double-clicking or double-tapping on folders should open them
            this.addListener(this.$elements, 'doubletap', function (ev, touchData) {
                // Make sure the touch targets are the same
                // (they may be different if Command/Ctrl/Shift-clicking on multiple elements quickly)
                if (touchData.firstTap.target === touchData.secondTap.target) {
                    const $element = $(touchData.firstTap.target)
                        .closest('tr,ul.thumbsview > li')
                        .find('.element:first');
                    if (Garnish.hasAttr($element, 'data-is-folder')) {
                        $element.find('a').trigger('activate');
                    }
                }
            });
            this.base();
        },
        /**
         * Handle source path change
         * @private
         */
        onSourcePathChange: function () {
            const currentFolder = this.sourcePath.length
              ? this.sourcePath[this.sourcePath.length - 1]
              : null;
            this.currentFolderId = currentFolder?.folderId;
      
            if (!this.settings.foldersOnly && this.currentFolderId) {      
                // will the user be allowed to move items in this folder?
                const canMoveSubItems = this.context === 'index' && !!currentFolder.canMoveSubItems;
                this.settings.selectable = this.settings.selectable || canMoveSubItems;
                this.settings.multiSelect = this.settings.multiSelect || canMoveSubItems;
            }
      
            this.base();
        },
        
        /**
         * Start searching
         * @private
         */
        startSearching: function () {
            // Does this source have subfolders?
            if (
                !this.settings.hideSidebar &&
                this.sourcePath.length &&
                this.sourcePath[this.sourcePath.length - 1].hasChildren
            ) {
                if (this.$includeSubfoldersContainer === null) {
                    var id =
                    'includeSubfolders-' + Math.floor(Math.random() * 1000000000);
        
                    this.$includeSubfoldersContainer = $(
                    '<div style="margin-bottom: -25px; opacity: 0;"/>'
                    ).insertAfter(this.$search);
                    var $subContainer = $('<div style="padding-top: 5px;"/>').appendTo(
                    this.$includeSubfoldersContainer
                    );
                    this.$includeSubfoldersCheckbox = $(
                    '<input type="checkbox" id="' + id + '" class="checkbox"/>'
                    ).appendTo($subContainer);
                    $('<label class="light smalltext" for="' + id + '"/>')
                    .text(' ' + Craft.t('app', 'Search in subfolders'))
                    .appendTo($subContainer);
        
                    this.addListener(
                        this.$includeSubfoldersCheckbox,
                        'change',
                        function () {
                            this.setSelecetedSourceState(
                                'includeSubfolders',
                                this.$includeSubfoldersCheckbox.prop('checked')
                            );
                            this.updateElements();
                        }
                    );
                } else {
                    this.$includeSubfoldersContainer
                    .velocity('stop')
                    .removeClass('hidden');
                }
      
                let checked;
                if (this._includeSubfolders !== null) {
                    checked = this._includeSubfolders;
                    this._includeSubfolders = null;
                } else {
                    checked = this.getSelectedSourceState('includeSubfolders', false);
                }
                this.$includeSubfoldersCheckbox.prop('checked', checked);
        
                this.$includeSubfoldersContainer.velocity(
                    {
                        marginBottom: 0,
                        opacity: 1,
                    },
                    'fast'
                );
      
                this.showingIncludeSubfoldersCheckbox = true;
            }
      
            this.base();
        },

        /**
         * Stop searching
         * @private
         */
        stopSearching: function () {
            if (this.showingIncludeSubfoldersCheckbox) {
                this.$includeSubfoldersContainer.velocity('stop');
        
                this.$includeSubfoldersContainer.velocity(
                    {
                        marginBottom: -25,
                        opacity: 0,
                    },
                    {
                        duration: 'fast',
                        complete: () => {
                            this.$includeSubfoldersContainer.addClass('hidden');
                        },
                    }
                );
        
                this.showingIncludeSubfoldersCheckbox = false;
            }
      
            this.base();
        },
        /**
         * Get the view settings
         * @returns {Object}
         */
        getViewSettings: function () {
            const settings = {};
      
            if (this.settings.context === 'index') {
              // Allow folders to be selected
              settings.canSelectElement = () => true;
            }
      
            return settings;
        },
        
        getViewParams: function () {
            const data = Object.assign(this.base(), {
                showFolders: this.settings.showFolders && !this.trashed,
                foldersOnly: this.settings.foldersOnly,
            });
      
            if (
                this.showingIncludeSubfoldersCheckbox &&
                this.$includeSubfoldersCheckbox.prop('checked')
            ) {
                data.criteria.includeSubfolders = true;
            }
      
            return data;
        },
        
        getSourceActions: function () {
            let actions = this.base();
            actions.push({
                label: Craft.t('mux', 'Add New Volume'),
                onSelect: () => {
                    this.showCreateVolumeModal();
                }
            });

            actions.push({
                label: Craft.t('mux', 'Delete Selected Volume'),
                destructive: true,
                onSelect: () => {
                    this.deleteSelectedVolume();
                }
            });

            return actions;
        },
        
        showCreateVolumeModal: function () {
            // Use browser prompt to get the volume name
            const volumeName = prompt(Craft.t('mux', 'Enter volume name:'));
            
            // If user cancels or enters empty name, return early
            if (!volumeName || volumeName.trim() === '') {
                return;
            }
            
            // Generate handle from the name using the _toHandle method
            const volumeHandle = this._toHandle(volumeName.trim());
            
            // Validate that we have a valid handle
            if (!volumeHandle) {
                Craft.cp.displayError(Craft.t('mux', 'Could not generate a valid handle from the volume name.'));
                return;
            }
            
            // Prepare form data
            const formData = {
                [Craft.csrfTokenName]: Craft.csrfTokenValue,
                name: volumeName.trim(),
                handle: volumeHandle
            };
            
            // Make the API request
            Craft.postActionRequest('mux/volumes/create', formData, (response) => {
                if (response && response.success) {
                    Craft.cp.displayNotice(Craft.t('mux', 'Volume created.'));
                    
                    // Store the new volume info for selection after refresh
                    const newVolumeUid = response.volume.uid;
                    
                    if (newVolumeUid) {
                        const newVolumeKey = `volume:${newVolumeUid}`;
                        this.selectSource(newVolumeKey);
                        location.reload();
                    }

                } else {
                    // Handle validation errors
                    if (response && response.errors) {
                        // Show field-specific errors
                        let errorMessage = '';
                        Object.keys(response.errors).forEach(field => {
                            response.errors[field].forEach(error => {
                                errorMessage += `${field}: ${error}\n`;
                            });
                        });
                        Craft.cp.displayError(errorMessage || Craft.t('mux', 'Could not create volume.'));
                    } else {
                        // Generic error
                        Craft.cp.displayError(
                            (response && response.message) ? response.message : Craft.t('mux', 'Could not create volume.')
                        );
                    }
                }
            });
        },
        /**
         * Delete the selected volume
         * @private
         */
        deleteSelectedVolume: function () {
            const currentSourceKey = this.sourceKey;
            // Open browser alert to ask if user is sure they want to delete the volume and all assets in it
            if (confirm(Craft.t('mux', 'Delete this volume with all folders and assets in it?'))) {

                // Extract volume UID if needed
                if (currentSourceKey && currentSourceKey.startsWith('volume:')) {
                    const volumeUid = currentSourceKey.replace('volume:', '');

                    Craft.postActionRequest('mux/volumes/delete', {
                        [Craft.csrfTokenName]: Craft.csrfTokenValue,
                        volumeUid: volumeUid
                    }, (response) => {
                        if (response && response.success) {
                            Craft.cp.displayNotice(Craft.t('mux', 'Volume deleted.'));
                            // Refresh the sources list after deleting a volume and select the first volume
                            // Find the first available volume source and select it
                            const $firstVolumeSource = this.$sources.filter('[data-key^="volume:"]').first();

                            if ($firstVolumeSource) {
                                this.selectSource($firstVolumeSource.data('key'));
                            }
                            location.reload();
                        } else {
                            Craft.cp.displayError(Craft.t('mux', 'Could not delete volume.'));
                        }
                    });
                }
            }
        },

        /**
         * Perform actions after updating elements
         * @private
         */
        onUpdateElements: function () {
            this._onUpdateElements(false, this.view.getAllElements());
            this.view.on('appendElements', (ev) => {
                this._onUpdateElements(true, ev.newElements);
            });
  
            this.base();
        },

        /**
         * Do the after-update initializations
         * @private
         */
        _onUpdateElements: function (append, $newElements) {
            this.$listedFolders = $newElements.find(
            '.element[data-is-folder][data-folder-name]'
            );
            for (let i = 0; i < this.$listedFolders.length; i++) {
                const $folder = this.$listedFolders.eq(i);
                const $label = $folder.find('.label');
                const $link = $label.find('.label-link');
                const folderId = parseInt($folder.data('folder-id'));
                const folderName = $folder.data('folder-name');
                const label = Craft.t('app', '{name} folder', {
                    name: folderName,
                });
                if (this.settings.disabledFolderIds.includes(folderId)) {
                    $label.attr('aria-label', label);
                    $newElements.has($folder).addClass('disabled');
                    continue;
                }
                const sourcePath = $folder.data('source-path');
                if (sourcePath) {
                    const $newLink = $('<a class="label-link"/>')
                    .html($link.html())
                    .attr({
                        href: Craft.getCpUrl(sourcePath[sourcePath.length - 1].uri),
                        role: 'button',
                        'aria-label': label,
                    });
                    $link.replaceWith($newLink);
                    this.addListener($newLink, 'activate', (ev) => {
                        this.sourcePath = sourcePath;
                        this.clearSearch(false);
                        this.updateElements().then(() => {
                            const firstFocusableEl = this.$elements.find(
                            ':focusable:not(.selectallcontainer)'
                            )[0];
                            if (firstFocusableEl) {
                                firstFocusableEl.focus();
                            }
                        });
                    });
                }
            }
    
            if (this.itemDrag) {
                const currentFolder = this.sourcePath[this.sourcePath.length - 1];
                const canMoveSubItems = !!(
                    currentFolder &&
                    currentFolder.folderId &&
                    currentFolder.canMoveSubItems
                );
                if (!canMoveSubItems || !append) {
                    this.itemDrag.removeAllItems();
                }
                if (canMoveSubItems) {
                    this.itemDrag.addItems(this._findDraggableItems($newElements));
                }
            }
        },

        /**
         * Handle a keypress
         * @private
         */
        _onKeyDown: function (ev) {
            if (ev.keyCode === Garnish.SPACE_KEY && ev.shiftKey) {
                if (this.view.elementSelect) {
                    let $element = $(ev.target).closest('.element');
                    if (!$element.length) {
                        $element = $(ev.target).find('.element:first');
                    }
                }
        
                ev.stopPropagation();
                return false;
            }
        },

        /**
         * Get the source path label
         * @returns {string}
         */
        getSourcePathLabel: function () {
            return Craft.t('app', 'Volume path');
        },

        /**
         * Get the source path action label
         * @returns {string}
         */
        getSourcePathActionLabel: function () {
            return Craft.t('app', 'Folder actions');
        },

        /**
         * Get the source path actions
         * @returns {Object[]}
         */
        getSourcePathActions: function () {
            const actions = [];
            const currentFolder = this.sourcePath[this.sourcePath.length - 1];

            if (currentFolder && currentFolder.canCreate) {
                actions.push({
                    label: Craft.t('app', 'New subfolder'),
                    onSelect: () => {
                        this._createSubfolder();
                    },
                });
            }

            if (this.settings.context === 'index') {
                if (currentFolder && currentFolder.canRename) {
                    actions.push({
                        label: Craft.t('app', 'Rename folder'),
                        onSelect: () => {
                            this._renameFolder();
                        },
                    });

                    if (
                        currentFolder && currentFolder.canMove &&
                        this.getMoveTargetSourceKeys(true).length
                    ) {
                        actions.push({
                            label: Craft.t('app', 'Move folder'),
                            onSelect: () => {
                                this._moveFolder();
                            },
                        });
                    }

                    if (currentFolder && currentFolder.canDelete) {
                        actions.push({
                            label: Craft.t('app', 'Delete folder'),
                            destructive: true,
                            onSelect: () => {
                                this.deleteCurrentFolder();
                            },
                        });
                    }
                }
            }

            return actions;
        },

        /**
         * Get the view params
         * @returns {Object}
         */
        getViewParams: function () {
            var params = this.base();
            // console.log('MuxAssetIndex getViewParams called');
            // console.log('this.settings.showFolders:', this.settings.showFolders);
            // console.log('this.trashed:', this.trashed);

            params.showFolders = this.settings.showFolders && !this.trashed;
            params.foldersOnly = this.settings.foldersOnly;

            //console.log('Final params:', params);
            return params;
        },

        /**
         * Create a subfolder
         * @private
         */
        _createSubfolder: function () {
            const currentFolder = this.sourcePath[this.sourcePath.length - 1];
            const subfolderName = prompt(
                Craft.t('app', 'Enter the name of the folder')
            );
      
            if (subfolderName) {
                const data = {
                    volumeId: currentFolder.volumeId,
                    parentId: currentFolder.folderId,
                    folderName: subfolderName,
                };
        
                this.setIndexBusy();
        
                Craft.sendActionRequest('POST', 'mux/folders/create', {data})
                    .then((response) => {
                        this.setIndexAvailable();
                        Craft.cp.displayNotice(Craft.t('app', 'Folder created.'));
                        this.updateElements(true);
                    })
                    .catch(({response}) => {
                        this.setIndexAvailable();
                        Craft.cp.displayError(response.data.message);
                    });
            }
        },

        /**
         * Delete the current folder
         * @private
         */
        deleteCurrentFolder: async function () {
            if (
                await this.deleteFolder(this.sourcePath[this.sourcePath.length - 1])
            ) {
                this.sourcePath = this.sourcePath.slice(0, this.sourcePath.length - 1);
                this.updateElements();
            }
        },

        /**
         * Delete a folder
         * @private
         */
        deleteFolder: async function (folder) {
            if (
              !confirm(
                    Craft.t('app', 'Really delete folder “{folder}”?', {
                        folder: folder.label,
                    })
              )
            ) {
                return false;
            }
      
            this.setIndexBusy();
      
            try {
                await Craft.sendActionRequest('POST', 'mux/folders/delete', {
                    data: {
                        folderId: folder.folderId,
                    },
                });
            } catch (e) {
                Craft.cp.displayError(e?.response?.data?.message);
                return false;
            } finally {
              this.setIndexAvailable();
            }
      
            Craft.cp.displayNotice(Craft.t('app', 'Folder deleted.'));
            return true;
        },

        /**
         * Rename the current folder
         * @private
         */
        _renameFolder: function () {
            const currentFolder = this.sourcePath[this.sourcePath.length - 1];
            const newName = prompt(
                Craft.t('app', 'Rename folder'),
                currentFolder.label
            );
      
            if (!newName || newName === currentFolder.label) {
                return;
            }
      
            this.setIndexBusy();
      
            Craft.sendActionRequest('POST', 'mux/folders/rename', {
                data: {
                    folderId: currentFolder.folderId,
                    newName: newName,
                },
            })
            .then((response) => {
                    Craft.cp.displayNotice(Craft.t('app', 'Folder renamed.'));
                    const sourcePath = this.sourcePath.slice();
                    sourcePath[sourcePath.length - 1].label = response.data.newName;
                    sourcePath[sourcePath.length - 1].uri =
                    sourcePath[sourcePath.length - 2].uri + `/${response.data.newName}`;
                    this.sourcePath = sourcePath;
            })
            .catch(({response}) => {
                Craft.cp.displayError(response.data.message);
            })
            .finally(() => {
                this.setIndexAvailable();
            });
        },

        /**
         * Get the source keys for the move target
         * @param {boolean} peerFiles
         * @returns {string[]}
         */
        getMoveTargetSourceKeys: function (peerFiles) {
            const attr = peerFiles
                ? 'data-can-move-peer-files-to'
                : 'data-can-move-to';
            return this.$sources
                .toArray()
                .filter((source) => {
                    const volumeHandle = $(source).data('volume-handle');
                    return (
                        volumeHandle &&
                        volumeHandle !== 'temp' &&
                        Garnish.hasAttr(source, attr)
                    );
                })
                .map((source) => $(source).data('key'));
        },

        /**
         * Move the current folder
         * @private
         */
        _moveFolder: function () {
            const currentFolder = this.sourcePath[this.sourcePath.length - 1];
            const parentFolder = this.sourcePath[this.sourcePath.length - 2];
      
            const disabledFolderIds = [currentFolder.folderId];
            if (parentFolder) {
                disabledFolderIds.push(parentFolder.folderId);
            }
      
            new Craft.MuxVolumeFolderSelectorModal({
                sources: this.getMoveTargetSourceKeys(true),
                showTitle: true,
                modalTitle: Craft.t('app', 'Move to'),
                selectBtnLabel: Craft.t('app', 'Move'),
                disabledFolderIds: disabledFolderIds,
                indexSettings: {
                    defaultSource: this.sourceKey,
                    defaultSourcePath: this.sourcePath.slice(
                        0,
                        this.sourcePath.length - 1
                    ),
                },
                onSelect: ([targetFolder]) => {
                    this.$sourcePathActionsBtn.focus();
                    const mover = new Craft.MuxAssetMover();
                    mover
                    .moveFolders([currentFolder.folderId], targetFolder.folderId)
                    .then((totalFoldersMoved) => {
                        if (totalFoldersMoved) {
                            Craft.cp.displayNotice(
                                Craft.t(
                                    'app',
                                    '{totalItems, plural, =1{Item} other{Items}} moved.',
                                    {
                                        totalItems: totalFoldersMoved,
                                    }
                                )
                            );
                            this.sourcePath = this.sourcePath.slice(
                                0,
                                this.sourcePath.length - 1
                            );
                            this.clearSearch(false);
                            this.updateElements();
                        }
                    });
                },
            });
        },
        _positionProgressBar: function () {
            if (!this.progressBar) {
                this.progressBar = new Craft.ProgressBar(this.$main, true);
            }
      
            let $container = $(),
                scrollTop = 0,
                offset = 0;
      
            if (this.settings.context === 'index') {
                $container = this.progressBar.$progressBar.closest('#content');
                scrollTop = Garnish.$win.scrollTop();
            } else {
                $container = this.progressBar.$progressBar.closest('.main');
                scrollTop = this.$main.scrollTop();
            }
      
            var containerTop = $container.offset().top;
            var diff = scrollTop - containerTop;
            var windowHeight = Garnish.$win.height();
      
            if ($container.height() > windowHeight) {
                offset = windowHeight / 2 - 6 + diff;
            } else {
                offset = $container.height() / 2 - 6;
            }
      
            if (this.settings.context !== 'index') {
                offset = scrollTop + ($container.height() / 2 - 6);
            }
      
            this.progressBar.$progressBar.css({
                top: offset,
            });
        },

        _toHandle: function (str) {
            if (!str) return '';
            
            // Remove HTML tags
            str = str.replace(/<[^>]*>/g, '');
            
            // Remove special characters
            str = str.replace(/['"'""ʻ\[\]\(\)\{\}:]/g, '');
            
            // Convert to lowercase
            str = str.toLowerCase();
            
            // Convert extended ASCII to basic ASCII (simplified)
            str = str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            
            // Handle must start with a letter
            str = str.replace(/^[^a-z]+/, '');
            
            // Replace non-alphanumeric/underscore with spaces
            str = str.replace(/[^a-z0-9_]/g, ' ');
            
            // Convert to camelCase (lowercase first letter)
            return str.split(' ')
                .map((word, index) => {
                    if (index === 0) {
                        return word; // First word stays lowercase
                    }
                    return word.charAt(0).toUpperCase() + word.slice(1);
                })
                .join('');
        }

    }, {
        defaults: {
            showFolders: true,
            foldersOnly: false,
            disabledFolderIds: [],
        },
    });

    // Register the element index class
    Craft.registerElementIndexClass('rocketpark\\mux\\elements\\MuxAsset', Craft.MuxAssetIndex);

})(jQuery);