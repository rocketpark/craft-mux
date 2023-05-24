const accept_extensions = 'mp4,webm,mov,m4v';
const max_file_size = 50000 * 1024 * 1024; // 52GB
const multiple_files = true;

export const checkFileExtensions = function(files) {
    // get non-empty, unique extension items
    const extList = [...new Set(
        accept_extensions.toLowerCase()
            .split(',')
            .filter(Boolean),
    )];
    const list = Array.from(files);
    // check if the selected files are in supported extensions
    const invalidFileIndex = list.findIndex((file) => {
        const ext = `${file.name.toLowerCase().split('.').pop()}`;
        return !extList.includes(ext);
    });

    // all exts are valid
    return invalidFileIndex === -1;
};

export const checkFileSize = function(files) {
    if (Number.isNaN(max_file_size)) {
        return true;
    }
    const list = Array.from(files);
    // find invalid file size
    const invalidFileIndex = list.findIndex((file) => { return file.size > max_file_size; });
    // all file size are valid
    return invalidFileIndex === -1;
};

export const validate = function(files) {
    // file selection
    if (!multiple_files && files.length > 1) {
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
    return true;
};

export const preprocessFiles = function(files) {
    return new Promise((resolve, reject) => {
        const result = validate(files);

        if (result === true) {
            resolve(result);
        } else {
            switch (result) {
                case 'MULTIFILES_ERROR':
                    alert('Please select only one file.');
                    reject(result);
                    break;
                case 'EXTENSION_ERROR':
                    alert(`These files are not supported. Upload video file types: (${accept_extensions})`);
                    reject(result);
                    break;
                case 'FILE_SIZE_ERROR':
                    alert(`These files are too large. Max file size: ${max_file_size} GB`);
                    reject(result);
                    break;
                default:
                    alert('Unkown error uploading files.');
                    reject(result);
                    break;
            }

        }

    });
};
