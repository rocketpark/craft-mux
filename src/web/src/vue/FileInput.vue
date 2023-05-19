<template>
    <div>
        <div class="">
            <button type="button"
                class="btn"
                :class="[isDragEnter ? '' : ' dashed']"
                data-icon="upload"
                aria-label="Upload a video"
                @click.stop.prevent="openFileSelectWindow"
                @dragenter.stop.prevent="isDragEnter = true"
                @dragover.stop.prevent="() => { }"
                @dragleave.stop.prevent="isDragEnter = false"
                @drop.stop.prevent="handleDrop">
                Upload a video to MUX
            </button>

            <input ref="fsFileInput"
                id="file-upload"
                name="file-upload"
                :accept="acceptExtensions"
                tabindex="-1"
                :multiple="multiple"
                type="file"
                class="sr-only"
                @change="onFileSelect" />
        </div>
        <slot name="files"></slot>
    </div>
</template>
<script>
import { ref } from "vue";
export default {
    props: {
        multiple: {
            type: Boolean,
            default: false,
        },
        isLoading: {
            type: Boolean,
            default: false,
        },
        acceptExtensions: {
            type: String,
            default: '',
        },
        maxFileSize: { // in bytes
            type: Number,
            default: NaN,
        },
        validateFn: {
            type: Function,
            default: () => true,
        },
    },
    emits: ['changed', 'validated'],
    setup(props, { emit }) {
        const fsFileInput = ref();
        const isDragEnter = ref(false);

        const checkFileExtensions = function (files) {
            // get non-empty, unique extension items
            const extList = [...new Set(
                props.acceptExtensions.toLowerCase()
                    .split(',')
                    .filter(Boolean)
            )];
            const list = Array.from(files);
            // check if the selected files are in supported extensions
            const invalidFileIndex = list.findIndex((file) => {
                const ext = `.${file.name.toLowerCase().split('.').pop()}`;
                return !extList.includes(ext);
            });
            // all exts are valid
            return invalidFileIndex === -1;
        }

        const checkFileSize = function (files) {
            if (Number.isNaN(props.maxFileSize)) {
                return true;
            }
            const list = Array.from(files);
            // find invalid file size
            const invalidFileIndex = list.findIndex((file) => file.size > props.maxFileSize);
            // all file size are valid
            return invalidFileIndex === -1;
        }

        const validate = function (files) {
            // file selection
            if (!props.multiple && files.length > 1) {
                return 'MULTIFILES_ERROR';
            }
            // extension
            if (!checkFileExtensions(files)) {
                return 'EXTENSION_ERROR';
            }
            // file size
            if (!checkFileSize(files)) {
                return 'FILE_SIZE_ERROR';
            }
            // custom validation
            return props.validateFn(files);
        }

        const preprocessFiles = function (files) {
            const result = validate(files);
            emit('validated', result, files);
            // validation
            if (result === true) {
                emit('changed', files);
            }
            // clear selected files
            fsFileInput.value = '';
        }

        const handleDrop = function (evt) {
            isDragEnter.value = false;
            preprocessFiles(evt.dataTransfer.files);
        }

        const onFileSelect = function (evt) {
            preprocessFiles(evt.dataTransfer.files);
        }

        const openFileSelectWindow = function (evt) {
            fsFileInput.value.click();
        }

        return {
            fsFileInput,
            isDragEnter,
            onFileSelect,
            openFileSelectWindow,
            handleDrop,
            validate
        }
    }
}
</script>
<style>
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
</style>