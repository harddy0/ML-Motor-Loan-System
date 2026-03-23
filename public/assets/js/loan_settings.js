// ============================================================
// loan_settings.js
// Handles: settings fetch, input pre-load, config log render,
//          and flash message dismissal for loan-settings/index.php
// ============================================================

// ==========================================
// SETTINGS FETCH & UI RENDER
// ==========================================

/**
 * Fetches the current add-on rate and audit info from the API,
 * pre-loads the rate input, and populates the configuration log.
 */
function loadSettings() {
    const url = (typeof BASE_URL !== 'undefined')
        ? `${BASE_URL}/public/api/get_system_settings.php`
        : '../../api/get_system_settings.php';

    fetch(url)
        .then(response => response.json())
        .then(res => {
            if (res.success && res.data) {
                setSettingsUI(res.data);
            } else {
                console.error('Failed to load settings:', res.error);
                setSettingsError();
            }
        })
        .catch(err => {
            console.error('Network error loading settings:', err);
            setSettingsError();
        })
        .finally(() => {
            removeLoaders();
        });
}

/**
 * Populates the input and configuration log with fetched data.
 * rate_percent comes as a raw float (e.g. 1.5000000000000002) —
 * we round to 3 decimal places and strip trailing zeroes before display.
 * @param {Object} data - { rate_percent, updated_at, updated_by }
 */
function setSettingsUI(data) {
    const rateInput   = document.getElementById('add_on_rate');
    const updatedAtEl = document.getElementById('ui-updated-at');
    const updatedByEl = document.getElementById('ui-updated-by');

    if (rateInput) {
        // Round to 3dp then strip trailing zeroes: 1.500 → "1.5", 1.250 → "1.25"
        const clean = parseFloat(parseFloat(data.rate_percent).toFixed(3));
        rateInput.value = clean;
    }

    if (updatedAtEl) updatedAtEl.textContent = data.updated_at;
    if (updatedByEl) updatedByEl.textContent = data.updated_by;
}

/**
 * Shows a generic error message in the configuration log fields.
 */
function setSettingsError() {
    const updatedAtEl = document.getElementById('ui-updated-at');
    const updatedByEl = document.getElementById('ui-updated-by');

    if (updatedAtEl) updatedAtEl.textContent = 'Error loading data';
    if (updatedByEl) updatedByEl.textContent = 'Error loading data';
}

// ==========================================
// LOADERS
// ==========================================

/**
 * Removes both skeleton loaders once data has settled (success or error).
 */
function removeLoaders() {
    const formLoader  = document.getElementById('form-loader');
    const auditLoader = document.getElementById('audit-loader');
    if (formLoader)  formLoader.remove();
    if (auditLoader) auditLoader.remove();
}

// ==========================================
// FLASH MESSAGE DISMISSAL
// ==========================================

/**
 * Attaches click handlers to all OK buttons inside flash messages.
 * On dismiss: removes the element from the DOM and cleans the URL
 * query string so a page refresh does not re-show the alert.
 */
function initFlashMessages() {
    document.querySelectorAll('.flash-ok-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const wrap = btn.closest('.flash-msg');
            if (wrap) wrap.remove();

            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                url.searchParams.delete('error');
                window.history.replaceState({ path: url.href }, '', url.href);
            }
        });
    });
}

// ==========================================
// INIT
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
    initFlashMessages();
});