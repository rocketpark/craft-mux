// import JS
import { MuxElementSelectInput } from './js/MuxElementSelectInput.js';

// Initialize Mux Element Select Inputs
document.addEventListener("DOMContentLoaded", function() {
    window.RocketPark = window.RocketPark || {};
    window.RocketPark.Mux = window.RocketPark.Mux || {};

    // Set up observer for dynamically added fields
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function() {
            // Find all uninitialized Mux fields
            document.querySelectorAll('[data-mux-field-settings]:not([data-mux-init])').forEach(function(el) {
                try {
                    const settings = JSON.parse(el.dataset.muxFieldSettings);
                    const input = new MuxElementSelectInput(settings);
                    el.dataset.muxInit = true;
                    
                } catch (e) {
                    console.error('Failed to initialize Mux field:', e);
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        window.RocketPark.Mux._observer = observer;
        
        // Initial check
        observer.disconnect();
        observer.observe(document.body, { childList: true, subtree: true });
    }
});