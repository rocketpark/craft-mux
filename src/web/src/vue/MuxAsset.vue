<template>
    <div>
        <file-input
            v-if="assets.length == 0"
            accept-extensions=".mp4,.mov"
            :multiple="false"
            :max-file-size="700 * 1024 * 1024"
            @validated="onFileValidated"
            @changed="onFileSelect"
        >
            <template #files>
                <progress-bar :value="progress" />
            </template>
        </file-input>

        <div
            v-if="assets.length > 0"
            class=""
        >
            <file
                v-for="asset in assets"
                :key="asset.id"
                :file="asset"
                @mux-asset-deleted="onDeletedAsset"
            />
        </div>

        <input
            type="hidden"
            :name="name"
            :value="assets.length > 0 ? JSON.stringify(assets[0]) : ''"
        >
    </div>
</template>
<script>
import { inject, ref, onMounted } from 'vue';
import '@mux/mux-player';
import FileInput from './FileInput.vue';
import File from './File.vue';
import ProgressBar from './ProgressBar.vue';
import * as UpChunk from '@mux/upchunk';

export default {
    components: {
        FileInput,
        File,
        ProgressBar,
    },
    props: {
        name: {
            type: String,
            default: '',
        },
        value: {
            type: Object,
            default: null,
        },
    },
    setup(props, { emit }) {

        const assets = ref([]);
        const progress = ref(0);

        const loadAsDataUrl = (file) => {
            const url = new Promise((resolve) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (e) => {
                    const et = e.target;
                    resolve(et.result);
                };
            });
            return url;
        };

        const onFileValidated = (valid) => {
            // console.log(valid);
        };

        const getUploadUrl = () => {
            return new Promise((resolve, reject) => {
                fetch('/actions/mux/assets/upload-asset').then((res) => {
                    if (!res.ok) {
                        throw new Error(`HTTP error ${res.status}`);
                    }
                    return res.json();
                }).then((json) => {
                    resolve(json);
                }).catch(() => {
                    reject();
                });
            });
        };

        const getUploadById = (id) => {
            return new Promise((resolve, reject) => {
                const body = { id };
                body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

                fetch('/actions/mux/assets/get-upload-by-id', {
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
                });
            });
        };

        const getAssetById = (id) => {
            return new Promise((resolve, reject) => {
                const body = { id };
                body[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

                fetch('/actions/mux/assets/get-asset-by-id', {
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
                });
            });
        };

        const onFileSelect = (files) => {
            const file = files[0];

            getUploadUrl().then((res) => {
                const upload = UpChunk.createUpload({
                    endpoint: res.url,
                    file,
                    chunkSize: 30720, // Uploads the file in ~30 MB chunks
                });

                // subscribe to events
                upload.on('error', (err) => {
                    console.error('💥 🙀', err.detail);
                });

                upload.on('progress', (prog) => {
                    progress.value = prog.detail;
                });

                upload.on('success', (data) => {
                    getUploadById(res.id).then((data) => {
                        getAssetById(data.asset_id).then((data) => {
                            assets.value = [data];
                            progress.value = 0;
                        });
                    });
                    // console.log("Wrap it up, we're done here. 👋");
                });
            });
        };

        /**
         * onDeleteAsset
         *  When video is deleted from mux set field value to empty;
         * @param {*} evt
         */
        const onDeletedAsset = (evt) => {
            assets.value = [];
        };

        // Set saved asset data
        onMounted(() => {
            // console.log('MUX FIELD INITIAL VALUE');
            // console.log(props.value);
            if (props.value !== null && props.value !== '') {
                assets.value = [props.value];
            }
        });

        return {
            onFileValidated,
            onFileSelect,
            onDeletedAsset,
            assets,
            progress,
        };
    },
};

</script>
