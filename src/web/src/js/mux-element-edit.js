class MuxEditPanel {
    constructor(asset_id, element) {
        this.asset_id = asset_id;
        this.editPanel = $(element);
        // this.addTrack = this.editPanel.find(`.mux-track-add`);
        this.trackSlideout = null;
        this.contents = '';
        this.langCodes = {
            af: 'Afrikaans',
            sq: 'Albanian',
            an: 'Aragonese',
            ar: 'Arabic (Standard)',
            'ar-dz': 'Arabic (Algeria)',
            'ar-bh': 'Arabic (Bahrain)',
            'ar-eg': 'Arabic (Egypt)',
            'ar-iq': 'Arabic (Iraq)',
            'ar-jo': 'Arabic (Jordan)',
            'ar-kw': 'Arabic (Kuwait)',
            'ar-lb': 'Arabic (Lebanon)',
            'ar-ly': 'Arabic (Libya)',
            'ar-ma': 'Arabic (Morocco)',
            'ar-om': 'Arabic (Oman)',
            'ar-qa': 'Arabic (Qatar)',
            'ar-sa': 'Arabic (Saudi Arabia)',
            'ar-sy': 'Arabic (Syria)',
            'ar-tn': 'Arabic (Tunisia)',
            'ar-ae': 'Arabic (U.A.E.)',
            'ar-ye': 'Arabic (Yemen)',
            hy: 'Armenian',
            as: 'Assamese',
            ast: 'Asturian',
            az: 'Azerbaijani',
            eu: 'Basque',
            bg: 'Bulgarian',
            be: 'Belarusian',
            bn: 'Bengali',
            bs: 'Bosnian',
            br: 'Breton',
            bg: 'Bulgarian',
            my: 'Burmese',
            ca: 'Catalan',
            ch: 'Chamorro',
            ce: 'Chechen',
            zh: 'Chinese',
            'zh-hk': 'Chinese (Hong Kong)',
            'zh-cn': 'Chinese (PRC)',
            'zh-sg': 'Chinese (Singapore)',
            'zh-tw': 'Chinese (Taiwan)',
            cv: 'Chuvash',
            co: 'Corsican',
            cr: 'Cree',
            hr: 'Croatian',
            cs: 'Czech',
            da: 'Danish',
            nl: 'Dutch (Standard)',
            'nl-be': 'Dutch (Belgian)',
            en: 'English',
            'en-au': 'English (Australia)',
            'en-bz': 'English (Belize)',
            'en-ca': 'English (Canada)',
            'en-ie': 'English (Ireland)',
            'en-jm': 'English (Jamaica)',
            'en-nz': 'English (New Zealand)',
            'en-ph': 'English (Philippines)',
            'en-za': 'English (South Africa)',
            'en-tt': 'English (Trinidad & Tobago)',
            'en-gb': 'English (United Kingdom)',
            'en-us': 'English (United States)',
            'en-zw': 'English (Zimbabwe)',
            eo: 'Esperanto',
            et: 'Estonian',
            fo: 'Faeroese',
            fa: 'Farsi',
            fj: 'Fijian',
            fi: 'Finnish',
            fr: 'French (Standard)',
            'fr-be': 'French (Belgium)',
            'fr-ca': 'French (Canada)',
            'fr-fr': 'French (France)',
            'fr-lu': 'French (Luxembourg)',
            'fr-mc': 'French (Monaco)',
            'fr-ch': 'French (Switzerland)',
            fy: 'Frisian',
            fur: 'Friulian',
            gd: 'Gaelic (Scots)',
            'gd-ie': 'Gaelic (Irish)',
            gl: 'Galacian',
            ka: 'Georgian',
            de: 'German (Standard)',
            'de-at': 'German (Austria)',
            'de-de': 'German (Germany)',
            'de-li': 'German (Liechtenstein)',
            'de-lu': 'German (Luxembourg)',
            'de-ch': 'German (Switzerland)',
            el: 'Greek',
            gu: 'Gujurati',
            ht: 'Haitian',
            he: 'Hebrew',
            hi: 'Hindi',
            hu: 'Hungarian',
            is: 'Icelandic',
            id: 'Indonesian',
            iu: 'Inuktitut',
            ga: 'Irish',
            it: 'Italian (Standard)',
            'it-ch': 'Italian (Switzerland)',
            ja: 'Japanese',
            kn: 'Kannada',
            ks: 'Kashmiri',
            kk: 'Kazakh',
            km: 'Khmer',
            ky: 'Kirghiz',
            tlh: 'Klingon',
            ko: 'Korean',
            'ko-kp': 'Korean (North Korea)',
            'ko-kr': 'Korean (South Korea)',
            la: 'Latin',
            lv: 'Latvian',
            lt: 'Lithuanian',
            lb: 'Luxembourgish',
            mk: 'FYRO Macedonian',
            ms: 'Malay',
            ml: 'Malayalam',
            mt: 'Maltese',
            mi: 'Maori',
            mr: 'Marathi',
            mo: 'Moldavian',
            nv: 'Navajo',
            ng: 'Ndonga',
            ne: 'Nepali',
            no: 'Norwegian',
            nb: 'Norwegian (Bokmal)',
            nn: 'Norwegian (Nynorsk)',
            oc: 'Occitan',
            or: 'Oriya',
            om: 'Oromo',
            fa: 'Persian',
            'fa-ir': 'Persian/Iran',
            pl: 'Polish',
            pt: 'Portuguese',
            'pt-br': 'Portuguese (Brazil)',
            pa: 'Punjabi',
            'pa-in': 'Punjabi (India)',
            'pa-pk': 'Punjabi (Pakistan)',
            qu: 'Quechua',
            rm: 'Rhaeto-Romanic',
            ro: 'Romanian',
            'ro-mo': 'Romanian (Moldavia)',
            ru: 'Russian',
            'ru-mo': 'Russian (Moldavia)',
            sz: 'Sami (Lappish)',
            sg: 'Sango',
            sa: 'Sanskrit',
            sc: 'Sardinian',
            gd: 'Scots Gaelic',
            sd: 'Sindhi',
            si: 'Singhalese',
            sr: 'Serbian',
            sk: 'Slovak',
            sl: 'Slovenian',
            so: 'Somani',
            sb: 'Sorbian',
            es: 'Spanish',
            'es-ar': 'Spanish (Argentina)',
            'es-bo': 'Spanish (Bolivia)',
            'es-cl': 'Spanish (Chile)',
            'es-co': 'Spanish (Colombia)',
            'es-cr': 'Spanish (Costa Rica)',
            'es-do': 'Spanish (Dominican Republic)',
            'es-ec': 'Spanish (Ecuador)',
            'es-sv': 'Spanish (El Salvador)',
            'es-gt': 'Spanish (Guatemala)',
            'es-hn': 'Spanish (Honduras)',
            'es-mx': 'Spanish (Mexico)',
            'es-ni': 'Spanish (Nicaragua)',
            'es-pa': 'Spanish (Panama)',
            'es-py': 'Spanish (Paraguay)',
            'es-pe': 'Spanish (Peru)',
            'es-pr': 'Spanish (Puerto Rico)',
            'es-es': 'Spanish (Spain)',
            'es-uy': 'Spanish (Uruguay)',
            'es-ve': 'Spanish (Venezuela)',
            sx: 'Sutu',
            sw: 'Swahili',
            sv: 'Swedish',
            'sv-fi': 'Swedish (Finland)',
            'sv-sv': 'Swedish (Sweden)',
            ta: 'Tamil',
            tt: 'Tatar',
            te: 'Teluga',
            th: 'Thai',
            tig: 'Tigre',
            ts: 'Tsonga',
            tn: 'Tswana',
            tr: 'Turkish',
            tk: 'Turkmen',
            uk: 'Ukrainian',
            hsb: 'Upper Sorbian',
            ur: 'Urdu',
            ve: 'Venda',
            vi: 'Vietnamese',
            vo: 'Volapuk',
            wa: 'Walloon',
            cy: 'Welsh',
            xh: 'Xhosa',
            ji: 'Yiddish',
            zu: 'Zulu',
        };
        this.form = null;
        this.cancelBtn = null;
        this.submitBtn = null;

        this.setup();
    }

    setup() {
        this.initTrackEvents();
        this.initTrackFormElements();
        this.initSlideout();
        this.initAddTrackButton();
        this.initTrackForm();
    }

    initTrackEvents() {
        const _this = this;
        _this.editPanel.find('.mux-track-text').each(function(el) {
            const track = $(this);
            const deleteButton = $(this).find('button');
            const spinner = deleteButton.find('.spinner');
            const icon = deleteButton.find('.xcircleicon');
            const _track_id = deleteButton.attr('data-track-id');
            const _confirmMessage = deleteButton.attr('data-confirm-message');

            deleteButton.on('click', (evt) => {
                evt.preventDefault();
                if (confirm(_confirmMessage)) {
                    spinner.toggleClass('hidden');
                    icon.toggleClass('hidden');

                    const body = { id: _this.asset_id, track_id: _track_id };
                    body[Craft.csrfTokenName] = Craft.csrfTokenValue;

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
                        if (json.success) {
                            track.animate({ opacity: 0, height: 0 }, 200, function() {
                                $(this).remove();
                            });

                            spinner.toggleClass('hidden');
                            icon.toggleClass('hidden');
                        }
                    }).catch((err) => {
                        console.log(err);
                        spinner.toggleClass('hidden');
                        icon.toggleClass('hidden');
                    });
                }
            });
        });
    }

    removeTrackEvents() {
        this.editPanel.find('.mux-track-text button').off('click');
    }

    openSlideout() {
        this.trackSlideout.open();
    }

    initAddTrackButton() {
        // this.addTrack = this.editPanel.find(".mux-track-add");
        // this.addTrack.on('click', (evt) => {
        //     evt.preventDefault();
        //     this.trackSlideout.open();
        // });
    }

    initTrackFormElements() {
        const _this = this;
        /*
            * FIELDS
            * url: '',
            * type: 'text',
            * text_type: 'subtitles',
            * language_code: '',
            * name: '',
            * closed_captions: true
        */

        const $body = $('<div/>', {
            class: 'so-body',
        });

        const $header = $('<header/>', {
            class: 'pane-header',
        }).appendTo($body);

        const $toolbar = $('<div/>', {
            class: 'so-toolbar',
        }).appendTo($header);

        const $heading = $('<h2/>').text(Craft.t('mux', 'Add Mux Track')).appendTo($body);

        const $trackUrl = Craft.ui.createTextField({
            id: 'mux-track-url',
            name: 'track[url]',
            label: Craft.t('mux', 'Mux Asset Track URL'),
            value: '',
            maxLength: 200,
            placeholder: Craft.t('mux', 'Enter url of .srt or .vtt file'),
            autofocus: true,
            required: true,
            validateOnBlur: true,
            validationError: Craft.t('mux', 'Please enter a proper url'),
            class: '',
            width: '100%',
        }).appendTo($body);

        const $trackType = $('<input>').attr({
            type: 'hidden',
            id: 'mux-track-type',
            name: 'track[type]',
            value: 'text',
        }).appendTo($body);

        const $trackTextType = $('<input>').attr({
            type: 'hidden',
            id: 'mux-track-type',
            name: 'track[text_type]',
            value: 'subtitles',
        }).appendTo($body);

        const $trackName = Craft.ui.createTextField({
            id: 'mux-track-url',
            name: 'track[name]',
            value: Craft.t('mux', 'US English'),
            label: Craft.t('mux', 'Language Name'),
            instructions: Craft.t('mux', `Upload the caption file of the asset in either .srt or .vtt format to Craft CMS Assets.
            Retrieve the URL associated with this input.`),
            maxLength: 200,
            placeholder: Craft.t('mux', 'US English'),
            autofocus: true,
            required: true,
            validateOnBlur: true,
            validationError: Craft.t('mux', 'Please enter a title for the track'),
            class: '',
            width: '30%',
        }).appendTo($body);

        const $trackLanguageCode = Craft.ui.createSelectField({
            label: Craft.t('mux', 'Language Code'),
            options: _this.langCodes,
            value: 'en-us',
            required: true,
            instructions: Craft.t('mux', 'Please select an language code.'),
            id: 'mux-track-language-code',
            name: 'track[language_code]',
        }).appendTo($body);

        const $trackClosedCaptions = Craft.ui.createLightswitchField({
            id: 'mux-track-closed-captions',
            label: Craft.t('mux', 'Closed Captions'),
            name: 'track[closed_captions]',
            value: true,
        }).appendTo($body);

        const $footer = $('<div/>', {
            class: 'so-footer',
        });

        $('<div/>', { class: 'flex-grow' }).appendTo($footer);

        this.cancelBtn = Craft.ui.createButton({
            label: Craft.t('mux', 'Close'),
            spinner: !0,
        }).appendTo($footer);

        this.submitBtn = Craft.ui.createSubmitButton({
            class: 'submit',
            label: Craft.t('mux', 'Create Track'),
            spinner: true,
        }).appendTo($footer);

        this.contents = $body.add($footer);
    }

    initSlideout() {
        const _this = this;
        this.trackSlideout = new Craft.Slideout(
            this.contents,
            {
                containerElement: 'form',
                autoOpen: false,
                containerAttributes:
                {
                    action: '/actions/mux/assets/add-asset-track-by-id',
                    method: 'post',
                    novalidate: '',
                    class: 'mux-track-form',
                },
            },
        );

        this.form = $(this.trackSlideout.$container[0]);

        this.cancelBtn.on('click',
            () => {
                _this.trackSlideout.close();
            });
    }

    initTrackForm() {
        const _this = this;
        this.form.submit(function(evt) {
            evt.preventDefault();
            _this.submitBtn.toggleClass('loading');

            const formData = Object.fromEntries(new FormData(_this.trackSlideout.$container[0]));
            const track = {
                url: formData['track[url]'],
                type: formData['track[type]'],
                text_type: formData['track[text_type]'],
                language_code: formData['track[language_code]'],
                name: formData['track[name]'],
                closed_captions: formData['track[closed_captions]'] == 1 ? true : false,
            };

            const body = { id: _this.asset_id, track };
            body[Craft.csrfTokenName] = Craft.csrfTokenValue;

            if ($(this)[0].checkValidity()) {
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
                    if (json.success) {
                        _this.submitBtn.toggleClass('loading');
                        if (Craft.elementIndex !== undefined) {
                            // location.reload();
                        } else {
                            // Remove events before reloading the panel
                            _this.removeTrackEvents();
                            // _this.removeAddTrackButtonEvent();
                            // Grab the panel: index 1 is the bottom 0 is the top panel.
                            let panel = Craft.Slideout.openPanels[1];
                            panel.load().then(() => {
                                panel = Craft.Slideout.openPanels[1];
                                const tabId = panel.tabManager.$lastTab.data('id');
                                panel.tabManager.selectTab(tabId);
                                // Reinitialize events for tracks & add tracks button
                                _this.initTrackEvents();
                                // _this.initAddTrackButtonEvent();
                            });
                        }
                    }
                }).catch((err) => {
                    console.log(err);
                    _this.submitBtn.toggleClass('loading');
                });
            }
        });
    }
}

// window.MUX = {
//     panels: []
// };


// $(document).on("click", ".mux-track-add", function(evt) {

//     let MuxPanels = Array.prototype.slice.call(document.querySelectorAll('.mux-edit-panel'));

//     if (MuxPanels.length > 0) {
//         //console.log(MuxPanels);
//         for (let panel of MuxPanels) {
//             window.MUX.panels[panel.dataset.muxAssetId] = new MuxEditPanel(panel.dataset.muxAssetId, panel);
//             window.MUX.panels[panel.dataset.muxAssetId].openSlideout();
//         }
//     }
// });
