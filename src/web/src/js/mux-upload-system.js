if (import.meta.hot) {
    import.meta.hot.accept();
}

import './MuxUploadWizard.js'; // side-effect: registers <mux-upload-wizard>
import './MuxUploadTray.js';   // side-effect: registers <mux-upload-tray>

const wizard = document.createElement('mux-upload-wizard');
const tray   = document.createElement('mux-upload-tray');
document.body.append(wizard, tray);

document.querySelector('#mux-wizard-btn')
    ?.addEventListener('click', () => wizard.open());

document.addEventListener('mux:wizard:open', () => wizard.open());
