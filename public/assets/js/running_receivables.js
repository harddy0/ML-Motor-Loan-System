let masterLocationsFetched = false;
let masterRegions = []; // cached list for inline autocomplete

document.addEventListener('DOMContentLoaded', () => {
    // prepare inline region autocomplete and cache
    ensureMasterRegionsFetched();
    attachRegionAutocomplete();

    initSearchFilter();

    // Automatically validate the pre-filled PHP values so button is active
    checkFormReady();
    updateCoverageStyles();
});

// MODAL CONTROLS
function openReportPicker() {
    const modal = document.getElementById('reportPeriodModal'); 
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // If inline pickers exist on the page, populate modal pickers so they stay in sync
    try {
        const inlineYear = document.getElementById('picker-year');
        const inlineMonth = document.getElementById('picker-month');
        const modalYear = document.getElementById('picker-year-modal');
        const modalMonth = document.getElementById('picker-month-modal');

        if(inlineYear && modalYear) modalYear.value = inlineYear.value;
        if(inlineMonth && modalMonth) modalMonth.value = inlineMonth.value;
    } catch(e) {
        // ignore
    }

    if (!masterLocationsFetched) {
        fetch(`${BASE_URL}/public/api/get_master_locations.php`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const regionSelect = document.getElementById('picker-region-select');
                    const urlParams = new URLSearchParams(window.location.search);
                    const selectedRegion = urlParams.get('region') || 'ALL';
                    
                    regionSelect.innerHTML = '<option value="ALL">ALL REGIONS</option>';

                    data.data.regions.forEach(region => {
                        if (region) {
                            let opt = document.createElement('option');
                            opt.value = region.toUpperCase();
                            opt.textContent = region.toUpperCase();
                            if (region.toUpperCase() === selectedRegion.toUpperCase()) {
                                opt.selected = true;
                            }
                            regionSelect.appendChild(opt);
                        }
                    });

                    // If a custom region was typed in previously and isn't in the list
                    const isSelectable = Array.from(regionSelect.options).some(opt => opt.value === selectedRegion.toUpperCase());
                    if (!isSelectable && selectedRegion !== 'ALL') {
                        toggleInputType('region');
                        document.getElementById('picker-region-input').value = selectedRegion;
                    }

                    masterLocationsFetched = true;
                    // cache regions for inline use (uppercased values)
                    try {
                        masterRegions = data.data.regions.map(r => r ? r.toString().toUpperCase() : '').filter(Boolean);
                    } catch(e) { masterRegions = []; }
                }
            })
            .catch(err => console.error("Could not fetch master locations", err));
    }

        // Sync modal coverage radios to current URL/inline selection
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const half = urlParams.get('half') || 'ALL';

            let targetVal = '0';
            if (half === '1ST') targetVal = '1';
            if (half === '2ND') targetVal = '2';

            const radio = document.querySelector(`input[name="picker-period"][value="${targetVal}"]`);
            if (radio) {
                radio.checked = true;
                updateCoverageStyles();
            }
        } catch (e) {}
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex'); 
    }
}

// UI TOGGLE LOGIC
function toggleInputType(field) {
    const selectWrapper = document.getElementById(`wrapper_${field}_select`);
    const select = document.getElementById(`picker-${field}-select`);
    const input = document.getElementById(`picker-${field}-input`);
    const btn = document.getElementById(`btn_toggle_${field}`);

    if (selectWrapper.classList.contains('hidden')) {
        // Switch back to Select Dropdown
        selectWrapper.classList.remove('hidden');
        select.disabled = false;
        
        input.classList.add('hidden');
        input.disabled = true;
        
        btn.innerText = "Type Manually";
    } else {
        // Switch to Text Input Field
        selectWrapper.classList.add('hidden');
        select.disabled = true;
        
        input.classList.remove('hidden');
        input.disabled = false;
        
        btn.innerText = "Select from List";
    }
}

// PICKER UI LOGIC
function validateSelect(el) {
    if (el.value !== "") {
        el.classList.remove('border-slate-100', 'text-slate-400');
        el.classList.add('border-green-500', 'text-slate-800', 'bg-green-50/30');
    }
    checkFormReady();
}

function updateCoverageStyles() {
    const radios = document.querySelectorAll('input[name="picker-period"]');
    radios.forEach((radio) => {
        const box = radio.nextElementSibling;
        const indicator = box.querySelector('.radio-indicator');
        const innerDot = indicator ? indicator.querySelector('div') : null;

        if (radio.checked) {
            box.classList.remove('border-slate-100', 'bg-white');
            box.classList.add('border-slate-200', 'bg-white');
            if (indicator) {
                indicator.classList.remove('border-slate-200');
                indicator.classList.add('border-slate-200');
                innerDot.classList.remove('scale-0');
                innerDot.classList.add('scale-100');
            }
        } else {
            box.classList.remove('border-slate-200', 'bg-white');
            box.classList.add('border-slate-100', 'bg-white');
            if (indicator) {
                indicator.classList.remove('border-slate-200');
                indicator.classList.add('border-slate-100');
                innerDot.classList.remove('scale-100');
                innerDot.classList.add('scale-0');
            }
        }
    });
}

function checkFormReady() {
    const year = document.getElementById('picker-year').value;
    const month = document.getElementById('picker-month').value;
    const btn = document.getElementById('generate-btn');

    if (year !== "" && month !== "") {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// SUBMIT LOGIC (Refreshes page with data and ALL filters)
function applyReportPeriod() {
    const year = document.getElementById('picker-year').value;
    const monthValue = document.getElementById('picker-month').value;
    const periodVal = document.querySelector('input[name="picker-period"]:checked').value;
    const statusVal = document.getElementById('picker-status').value;
    
    // Check Region Value
    const inputEl = document.getElementById('picker-region-input');
    const selectEl = document.getElementById('picker-region-select');
    const isInputActive = !inputEl.disabled && !inputEl.classList.contains('hidden');
    
    const regionVal = isInputActive ? inputEl.value.trim() : selectEl.value;
    const finalRegion = regionVal ? encodeURIComponent(regionVal.toUpperCase()) : 'ALL';
    
    // Format month to have leading zero (e.g. '01', '02')
    const paddedMonth = monthValue.padStart(2, '0');
    const periodString = `${year}-${paddedMonth}`;

    let halfParam = 'ALL';
    if(periodVal === '1') halfParam = '1ST';
    if(periodVal === '2') halfParam = '2ND';

    // Redirect the page with the GET parameters
    window.location.href = `?period=${periodString}&half=${halfParam}&status=${statusVal}&region=${finalRegion}`;
}

// TABLE SEARCH LOGIC
function initSearchFilter() {
    const searchInput = document.querySelector('input[placeholder="SEARCH NAME OR ID..."]');
    const tableBody = document.querySelector('#receivablesTable tbody');
    
    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const tableRows = tableBody.querySelectorAll('tr');

            tableRows.forEach(row => {
                if (row.cells.length === 1) return;
                const empId = row.cells[0].textContent.toLowerCase().trim();
                const empName = row.cells[1].textContent.toLowerCase().trim();

                if (empId.includes(searchTerm) || empName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

function downloadExcelReport() {
    // 1. Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Default values if no parameters are present
    const period = urlParams.get('period') || new Date().toISOString().slice(0, 7);
    const half = urlParams.get('half') || 'ALL';
    const status = urlParams.get('status') || 'ONGOING';
    const region = urlParams.get('region') || 'ALL';

    // 2. Construct the export URL
    const exportUrl = `${BASE_URL}/public/api/export_running_receivables.php?period=${period}&half=${half}&status=${status}&region=${encodeURIComponent(region)}`;

    // 3. Trigger the download by navigating to the URL
    window.location.href = exportUrl;
}

function resetReportFilters() {
    const d = new Date();
    
    // 1. Reset Time Period to Current Year/Month
    const yearSelect = document.getElementById('picker-year');
    yearSelect.value = d.getFullYear();
    
    const monthSelect = document.getElementById('picker-month');
    monthSelect.value = d.getMonth() + 1;
    
    // 2. Reset Coverage to 'Whole Month' (value = 0)
    const radios = document.querySelectorAll('input[name="picker-period"]');
    radios.forEach((radio) => {
        radio.checked = (radio.value === '0');
    });
    updateCoverageStyles(); // Visually update the coverage boxes
    
    // 3. Reset Status Filter to 'ONGOING'
    const statusSelect = document.getElementById('picker-status');
    statusSelect.value = 'ONGOING';
    
    // 4. Reset Region Filter
    const regionSelect = document.getElementById('picker-region-select');
    const regionInput = document.getElementById('picker-region-input');
    const selectWrapper = document.getElementById('wrapper_region_select');
    const toggleBtn = document.getElementById('btn_toggle_region');
    
    regionSelect.value = 'ALL';
    regionInput.value = '';
    
    // Force Region back to "Select dropdown" mode instead of "Typing" mode
    selectWrapper.classList.remove('hidden');
    regionSelect.disabled = false;
    
    regionInput.classList.add('hidden');
    regionInput.disabled = true;
    
    toggleBtn.innerText = "Type Manually";

    // 5. Explicitly Trigger visual updates to clear active colors in selectors
    [yearSelect, monthSelect, statusSelect, regionSelect].forEach(el => {
        // We dispatch an event so if there's any other logic bound, it safely updates
        el.dispatchEvent(new Event('change'));
        
        // Remove the visual 'active' styles from custom filters
        el.classList.remove('border-green-500', 'bg-green-50/30', 'text-slate-800');
        if(el.id === 'picker-status' || el.id === 'picker-region-select') {
             el.classList.add('border-slate-100', 'bg-slate-50', 'text-slate-700');
        } else {
             el.classList.add('border-green-500', 'bg-green-50/30'); // Keep Year/Month naturally green
        }
    });

    // Re-verify the form state
    checkFormReady();
}

// Sync helper: when modal pickers change, update inline pickers (if present)
function syncPickerFromModal(el, type) {
    try {
        // Handle specific modal -> inline mappings
        if (type === 'status') {
            const inline = document.getElementById('picker-status-inline');
            if (inline) inline.value = el.value;
            return;
        }

        if (type === 'region') {
            const inline = document.getElementById('picker-region-inline');
            if (inline) {
                if (inline.tagName === 'SELECT') {
                    // ensure option exists and set value
                    let opt = inline.querySelector(`option[value="${el.value}"]`);
                    if (!opt) {
                        opt = document.createElement('option');
                        opt.value = el.value;
                        opt.textContent = (el.value === 'ALL') ? 'All Regions' : el.value;
                        inline.insertBefore(opt, inline.firstChild);
                    }
                    inline.value = el.value;
                } else {
                    // input case
                    inline.value = (el.value === 'ALL') ? 'All Regions' : el.value;
                }
            }
            return;
        }

        const main = document.getElementById(`picker-${type}`);
        if (main) main.value = el.value;
    } catch (e) {}
    checkFormReady();
}

// Ensure we have master regions cached (used by inline region autocomplete)
function ensureMasterRegionsFetched() {
    if (masterLocationsFetched && masterRegions.length) return Promise.resolve(masterRegions);

    return fetch(`${BASE_URL}/public/api/get_master_locations.php`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success && Array.isArray(data.data.regions)) {
                masterRegions = data.data.regions.map(r => r ? r.toString().toUpperCase() : '').filter(Boolean);
            }
            masterLocationsFetched = true;
            return masterRegions;
        })
        .catch(err => { console.error('Could not fetch master regions', err); return []; });
}

// Attach inline region autocomplete behaviour
function attachRegionAutocomplete() {
    const el = document.getElementById('picker-region-inline');
    const box = document.getElementById('region-suggestions');
    if (!el || !box) return;

    // Only attach autocomplete if the inline control is an INPUT (typing mode)
    if (el.tagName === 'INPUT') {
        el.addEventListener('focus', () => {
            if (!masterLocationsFetched) ensureMasterRegionsFetched();
            box.classList.remove('hidden');
            renderRegionSuggestions(el.value || '');
        });

        el.addEventListener('input', (e) => renderRegionSuggestions(e.target.value || ''));

        el.addEventListener('blur', () => setTimeout(() => box.classList.add('hidden'), 150));
    }
}

function renderRegionSuggestions(query) {
    const box = document.getElementById('region-suggestions');
    const input = document.getElementById('picker-region-inline');
    if (!box || !input) return;

    const q = (query || '').toString().toUpperCase().trim();
    let matches = [];
    if (q === '') {
        matches = ['ALL REGIONS', ...masterRegions.slice(0, 7)];
    } else {
        matches = masterRegions.filter(r => r.includes(q)).slice(0, 8);
    }

    if (!matches.length) {
        box.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">No matches</div>';
        box.classList.remove('hidden');
        return;
    }

    box.innerHTML = matches.map(r => `<div class="px-3 py-2 text-sm hover:bg-slate-100 cursor-pointer">${r}</div>`).join('');

    Array.from(box.children).forEach(child => {
        child.addEventListener('click', () => {
            input.value = child.textContent;
            box.classList.add('hidden');
            quickChangeRegion();
        });
    });
    box.classList.remove('hidden');
}

// Quick change: inline status select changed -> reload preserving other params
function quickChangeStatus() {
    const statusEl = document.getElementById('picker-status-inline');
    if (!statusEl) return;

    const yearEl = document.getElementById('picker-year');
    const monthEl = document.getElementById('picker-month');
    if (!yearEl || !monthEl) return;

    const year = yearEl.value;
    const monthValue = String(monthEl.value).padStart(2, '0');
    const statusVal = statusEl.value || 'ONGOING';

    const urlParams = new URLSearchParams(window.location.search);
    const half = urlParams.get('half') || 'ALL';
    const region = urlParams.get('region') || 'ALL';

    const modalStatus = document.getElementById('picker-status');
    if (modalStatus) modalStatus.value = statusVal;

    window.location.href = `?period=${year}-${monthValue}&half=${half}&status=${statusVal}&region=${encodeURIComponent(region)}`;
}

// Quick change: inline region selected/entered -> reload preserving other params
function quickChangeRegion() {
    const input = document.getElementById('picker-region-inline');
    if (!input) return;

    const yearEl = document.getElementById('picker-year');
    const monthEl = document.getElementById('picker-month');
    if (!yearEl || !monthEl) return;

    const year = yearEl.value;
    const monthValue = String(monthEl.value).padStart(2, '0');
    let regionVal = input.value.trim() || '';
    if (regionVal.toUpperCase() === 'ALL REGIONS') regionVal = '';
    const finalRegion = regionVal ? encodeURIComponent(regionVal.toUpperCase()) : 'ALL';

    const regionInputModal = document.getElementById('picker-region-input');
    const regionSelectModal = document.getElementById('picker-region-select');
    if (regionInputModal) {
        const selectWrapper = document.getElementById('wrapper_region_select');
        if (selectWrapper) selectWrapper.classList.add('hidden');
        if (regionSelectModal) regionSelectModal.disabled = true;
        regionInputModal.classList.remove('hidden');
        regionInputModal.disabled = false;
        regionInputModal.value = regionVal;
    }
    if (regionSelectModal) regionSelectModal.value = finalRegion;

    const urlParams = new URLSearchParams(window.location.search);
    const half = urlParams.get('half') || 'ALL';
    const status = urlParams.get('status') || 'ONGOING';

    window.location.href = `?period=${year}-${monthValue}&half=${half}&status=${status}&region=${finalRegion}`;
}

// Quick change: when inline year/month change, reload page preserving other params
function quickChangePeriod() {
    const yearEl = document.getElementById('picker-year');
    const monthEl = document.getElementById('picker-month');
    if (!yearEl || !monthEl) return;

    const year = yearEl.value;
    const monthValue = String(monthEl.value).padStart(2, '0');

    const urlParams = new URLSearchParams(window.location.search);
    const half = urlParams.get('half') || 'ALL';
    const status = urlParams.get('status') || 'ONGOING';
    const region = urlParams.get('region') || 'ALL';

    window.location.href = `?period=${year}-${monthValue}&half=${half}&status=${status}&region=${encodeURIComponent(region)}`;
}

// When modal coverage radio toggles, update inline half select if present
function syncCoverageFromModal(el) {
    try {
        const inline = document.getElementById('picker-half');
        if (!inline) return;

        if (el.value === '0') inline.value = 'ALL';
        if (el.value === '1') inline.value = '1ST';
        if (el.value === '2') inline.value = '2ND';
    } catch (e) {}
}

// Quick change for half dropdown (inline) — reload preserving other params
function quickChangeHalf() {
    const yearEl = document.getElementById('picker-year');
    const monthEl = document.getElementById('picker-month');
    const halfEl = document.getElementById('picker-half');
    if (!yearEl || !monthEl || !halfEl) return;

    const year = yearEl.value;
    const monthValue = String(monthEl.value).padStart(2, '0');
    const halfVal = halfEl.value || 'ALL';

    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status') || 'ONGOING';
    const region = urlParams.get('region') || 'ALL';

    // Redirect with selected half
    window.location.href = `?period=${year}-${monthValue}&half=${halfVal}&status=${status}&region=${encodeURIComponent(region)}`;
}