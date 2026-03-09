let masterLocationsFetched = false;
let masterRegions = []; // cached list for inline dropdown

document.addEventListener('DOMContentLoaded', () => {
    // 1. Fetch regions properly mapped from the API and populate the inline dropdown
    ensureMasterRegionsFetched().then(regions => {
        populateInlineRegionDropdown(regions);
    });

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
    } catch(e) {}

    if (!masterLocationsFetched) {
        fetch(`${BASE_URL}/public/api/get_master_locations.php`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const regionSelect = document.getElementById('picker-region-select');
                    if (regionSelect) {
                        const urlParams = new URLSearchParams(window.location.search);
                        const selectedRegion = urlParams.get('region') || 'ALL';
                        
                        regionSelect.innerHTML = '<option value="ALL">ALL REGIONS</option>';

                        data.data.regions.forEach(region => {
                            if (region) {
                                // Extract label from the Object properly
                                let rName = typeof region === 'object' ? region.label : region;
                                let opt = document.createElement('option');
                                opt.value = rName.toUpperCase();
                                opt.textContent = rName.toUpperCase();
                                
                                if (rName.toUpperCase() === selectedRegion.toUpperCase()) {
                                    opt.selected = true;
                                }
                                regionSelect.appendChild(opt);
                            }
                        });

                        // If a custom region was typed in previously and isn't in the list
                        const isSelectable = Array.from(regionSelect.options).some(opt => opt.value === selectedRegion.toUpperCase());
                        if (!isSelectable && selectedRegion !== 'ALL') {
                            toggleInputType('region');
                            const inputEl = document.getElementById('picker-region-input');
                            if(inputEl) inputEl.value = selectedRegion;
                        }
                    }

                    masterLocationsFetched = true;
                    // cache regions for inline use (uppercased values)
                    try {
                        masterRegions = data.data.regions.map(r => {
                            let text = typeof r === 'object' ? r.label : r;
                            return text ? text.toString().toUpperCase() : '';
                        }).filter(Boolean);
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

    if (selectWrapper && selectWrapper.classList.contains('hidden')) {
        selectWrapper.classList.remove('hidden');
        select.disabled = false;
        input.classList.add('hidden');
        input.disabled = true;
        btn.innerText = "Type Manually";
    } else if (selectWrapper) {
        selectWrapper.classList.add('hidden');
        select.disabled = true;
        input.classList.remove('hidden');
        input.disabled = false;
        btn.innerText = "Select from List";
    }
}

function updateCoverageStyles() {
    const radios = document.querySelectorAll('input[name="picker-period"]');
    radios.forEach((radio) => {
        const box = radio.nextElementSibling;
        if(!box) return;
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
    const yearEl = document.getElementById('picker-year');
    const monthEl = document.getElementById('picker-month');
    const btn = document.getElementById('generate-btn');

    if (yearEl && monthEl && btn) {
        if (yearEl.value !== "" && monthEl.value !== "") {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

function applyReportPeriod() {
    const year = document.getElementById('picker-year').value;
    const monthValue = document.getElementById('picker-month').value;
    
    let periodVal = '0';
    const radioChecked = document.querySelector('input[name="picker-period"]:checked');
    if (radioChecked) periodVal = radioChecked.value;
    
    const statusVal = document.getElementById('picker-status') ? document.getElementById('picker-status').value : 'ONGOING';
    
    let finalRegion = 'ALL';
    const inputEl = document.getElementById('picker-region-input');
    const selectEl = document.getElementById('picker-region-select');
    
    if (inputEl && selectEl) {
        const isInputActive = !inputEl.disabled && !inputEl.classList.contains('hidden');
        const regionVal = isInputActive ? inputEl.value.trim() : selectEl.value;
        finalRegion = regionVal ? encodeURIComponent(regionVal.toUpperCase()) : 'ALL';
    }

    const paddedMonth = monthValue.padStart(2, '0');
    const periodString = `${year}-${paddedMonth}`;

    let halfParam = 'ALL';
    if(periodVal === '1') halfParam = '1ST';
    if(periodVal === '2') halfParam = '2ND';

    window.location.href = `?period=${periodString}&half=${halfParam}&status=${statusVal}&region=${finalRegion}`;
}

function initSearchFilter() {
    // Better to use the direct ID selector
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchInput');
    const tableBody = document.querySelector('#receivablesTable tbody');

    const toggleClearSearchBtn = () => {
        if (!searchInput || !clearSearchBtn) return;
        clearSearchBtn.classList.toggle('hidden', searchInput.value.length === 0);
    };

    const applySearch = () => {
        if (!searchInput || !tableBody) return;
        const searchTerm = searchInput.value.toLowerCase().trim();
        const tableRows = tableBody.querySelectorAll('tr');

        tableRows.forEach(row => {
            // Skip the "No records found" row (length 1) and the Grand Totals row (has bg-slate-100)
            if (row.cells.length <= 1 || row.classList.contains('bg-slate-100')) return;

            // In the new table, cells[1] contains BOTH the Borrower Name and Employee ID
            const borrowerInfo = row.cells[1].textContent.toLowerCase().trim();

            // Check if the search term exists anywhere in the name or ID
            row.style.display = borrowerInfo.includes(searchTerm) ? '' : 'none';
        });
    };
    
    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function() {
            toggleClearSearchBtn();
            applySearch();
        });
        toggleClearSearchBtn();
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput.value.length === 0) return;
            searchInput.value = '';
            toggleClearSearchBtn();
            applySearch();
            searchInput.focus();
        });
    }
}

function downloadExcelReport() {
    const urlParams = new URLSearchParams(window.location.search);
    const period = urlParams.get('period') || new Date().toISOString().slice(0, 7);
    const half = urlParams.get('half') || 'ALL';
    const status = urlParams.get('status') || 'ONGOING';
    const region = urlParams.get('region') || 'ALL';

    const exportUrl = `${BASE_URL}/public/api/export_running_receivables.php?period=${period}&half=${half}&status=${status}&region=${encodeURIComponent(region)}`;
    window.location.href = exportUrl;
}

function resetReportFilters() {
    const d = new Date();
    
    const yearSelect = document.getElementById('picker-year');
    if(yearSelect) yearSelect.value = d.getFullYear();
    
    const monthSelect = document.getElementById('picker-month');
    if(monthSelect) monthSelect.value = d.getMonth() + 1;
    
    const radios = document.querySelectorAll('input[name="picker-period"]');
    radios.forEach((radio) => {
        radio.checked = (radio.value === '0');
    });
    updateCoverageStyles(); 
    
    const statusSelect = document.getElementById('picker-status');
    if(statusSelect) statusSelect.value = 'ONGOING';
    
    const regionSelect = document.getElementById('picker-region-select');
    const regionInput = document.getElementById('picker-region-input');
    const selectWrapper = document.getElementById('wrapper_region_select');
    const toggleBtn = document.getElementById('btn_toggle_region');
    
    if (regionSelect && regionInput && selectWrapper && toggleBtn) {
        regionSelect.value = 'ALL';
        regionInput.value = '';
        
        selectWrapper.classList.remove('hidden');
        regionSelect.disabled = false;
        
        regionInput.classList.add('hidden');
        regionInput.disabled = true;
        
        toggleBtn.innerText = "Type Manually";
    }

    [yearSelect, monthSelect, statusSelect, regionSelect].forEach(el => {
        if(el) {
            el.dispatchEvent(new Event('change'));
            el.classList.remove('border-green-500', 'bg-green-50/30', 'text-slate-800');
            if(el.id === 'picker-status' || el.id === 'picker-region-select') {
                 el.classList.add('border-slate-100', 'bg-slate-50', 'text-slate-700');
            } else {
                 el.classList.add('border-green-500', 'bg-green-50/30'); 
            }
        }
    });

    checkFormReady();
}

// Ensure we have master regions cached and properly formatted from the Objects
function ensureMasterRegionsFetched() {
    if (masterLocationsFetched && masterRegions.length) return Promise.resolve(masterRegions);

    return fetch(`${BASE_URL}/public/api/get_master_locations.php`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success && Array.isArray(data.data.regions)) {
                masterRegions = data.data.regions.map(r => {
                    let text = typeof r === 'object' ? r.label : r;
                    return text ? text.toString().toUpperCase() : '';
                }).filter(Boolean);
            }
            masterLocationsFetched = true;
            return masterRegions;
        })
        .catch(err => { console.error('Could not fetch master regions', err); return []; });
}

// Populates the <select id="picker-region-inline"> directly on page load
function populateInlineRegionDropdown(regions) {
    const select = document.getElementById('picker-region-inline');
    if (!select || select.tagName !== 'SELECT') return;

    const urlParams = new URLSearchParams(window.location.search);
    const selectedRegion = urlParams.get('region') || 'ALL';

    select.innerHTML = '<option value="ALL">All Regions</option>';

    regions.forEach(r => {
        let opt = document.createElement('option');
        opt.value = r;
        opt.textContent = r;
        if (r === selectedRegion.toUpperCase()) opt.selected = true;
        select.appendChild(opt);
    });

    // If the URL has a custom region typed that isn't in the default dropdown, retain it
    if (selectedRegion !== 'ALL' && !regions.includes(selectedRegion.toUpperCase())) {
        let customOpt = document.createElement('option');
        customOpt.value = selectedRegion.toUpperCase();
        customOpt.textContent = selectedRegion.toUpperCase();
        customOpt.selected = true;
        select.insertBefore(customOpt, select.children[1]);
    }
}

// Quick inline changes
function quickChangeStatus() {
    triggerInlineReload();
}

function quickChangeRegion() {
    triggerInlineReload();
}

function quickChangePeriod() {
    triggerInlineReload();
}

function quickChangeHalf() {
    triggerInlineReload();
}

// Universal reload handler for the inline filters
function triggerInlineReload() {
    const yearEl = document.getElementById('picker-year');
    const monthEl = document.getElementById('picker-month');
    const halfEl = document.getElementById('picker-half');
    const statusEl = document.getElementById('picker-status-inline');
    const regionEl = document.getElementById('picker-region-inline');

    if (!yearEl || !monthEl) return;

    const year = yearEl.value;
    const monthValue = String(monthEl.value).padStart(2, '0');
    const halfVal = halfEl ? halfEl.value : 'ALL';
    const statusVal = statusEl ? statusEl.value : 'ONGOING';
    
    let regionVal = regionEl ? regionEl.value : 'ALL';
    if (regionVal.toUpperCase() === 'ALL REGIONS') regionVal = 'ALL';

    window.location.href = `?period=${year}-${monthValue}&half=${halfVal}&status=${statusVal}&region=${encodeURIComponent(regionVal)}`;
}