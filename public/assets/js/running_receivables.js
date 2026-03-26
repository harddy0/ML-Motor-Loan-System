let masterLocationsFetched = false;
let masterRegions = []; // cached list for inline dropdown

document.addEventListener('DOMContentLoaded', () => {
    ensureMasterRegionsFetched().then(regions => {
        populateInlineRegionDropdown(regions);
    });

    setupReceivablesExportDropdown();
    initSearchFilter();
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
                        let selectedRegion = urlParams.get('region') || 'ALL';
                        
                        regionSelect.innerHTML = '<option value="ALL">ALL REGIONS</option>';

                        data.data.regions.forEach(region => {
                            if (region) {
                                // Extract ID and Label correctly
                                let val = (region.value || region).toString();
                                let lbl = (region.label || region).toString().toUpperCase();
                                
                                // Auto-correct if URL had old text instead of new code
                                if (selectedRegion.toUpperCase() === lbl) {
                                    selectedRegion = val;
                                }

                                let opt = document.createElement('option');
                                opt.value = val;
                                opt.textContent = lbl;
                                
                                if (val == selectedRegion) {
                                    opt.selected = true;
                                }
                                regionSelect.appendChild(opt);
                            }
                        });

                        const isSelectable = Array.from(regionSelect.options).some(opt => opt.value == selectedRegion);
                        if (!isSelectable && selectedRegion !== 'ALL') {
                            toggleInputType('region');
                            const inputEl = document.getElementById('picker-region-input');
                            if(inputEl) inputEl.value = selectedRegion;
                        }
                    }

                    masterLocationsFetched = true;
                    try { masterRegions = data.data.regions; } catch(e) { masterRegions = []; }
                }
            })
            .catch(err => console.error("Could not fetch master locations", err));
    }

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
        let regionVal = isInputActive ? inputEl.value.trim() : selectEl.value;
        if (!regionVal) regionVal = 'ALL'; // Guard against empty string
        finalRegion = encodeURIComponent(regionVal);
    }

    const paddedMonth = monthValue.padStart(2, '0');
    const periodString = `${year}-${paddedMonth}`;

    let halfParam = 'ALL';
    if(periodVal === '1') halfParam = '1ST';
    if(periodVal === '2') halfParam = '2ND';

    window.location.href = `?period=${periodString}&half=${halfParam}&status=${statusVal}&region=${finalRegion}`;
}

function initSearchFilter() {
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
            if (row.cells.length <= 1 || row.classList.contains('bg-slate-100')) return;
            const borrowerInfo = row.cells[1].textContent.toLowerCase().trim();
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

function setupReceivablesExportDropdown() {
    const menuBtn = document.getElementById('receivablesExportMenuBtn');
    const menu = document.getElementById('receivablesExportMenu');
    if (!menuBtn || !menu) return;

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target) && !menuBtn.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
}

function toggleReceivablesExportMenu(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('receivablesExportMenu');
    if (!menu) return;
    menu.classList.toggle('hidden');
}

function downloadExcelReport() {
    const menu = document.getElementById('receivablesExportMenu');
    if (menu) menu.classList.add('hidden');

    const urlParams = new URLSearchParams(window.location.search);
    const period = urlParams.get('period') || new Date().toISOString().slice(0, 7);
    const half = urlParams.get('half') || 'ALL';
    const status = urlParams.get('status') || 'ONGOING';
    let region = urlParams.get('region') || 'ALL';
    if (!region.trim()) region = 'ALL';

    const exportUrl = `${BASE_URL}/public/api/export_running_receivables.php?period=${period}&half=${half}&status=${status}&region=${encodeURIComponent(region)}`;
    window.location.href = exportUrl;
}

// ... [Keep the print functionality functions exactly as they were] ...
function printReceivablesReport() {
    const menu = document.getElementById('receivablesExportMenu');
    if (menu) menu.classList.add('hidden');

    const table = document.getElementById('receivablesTable');
    if (!table) return;

    const reportHeader = getReceivablesPrintHeaderInfo();
    const generatedAt = new Date().toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    const generatedBy = (typeof CURRENT_USER_FULLNAME !== 'undefined' && String(CURRENT_USER_FULLNAME).trim())
        ? String(CURRENT_USER_FULLNAME).trim().toUpperCase()
        : 'SYSTEM USER';

    const exportHeaderHtml = buildReceivablesExportHeaderHtml();

    const visibleTableHtml = buildVisibleReceivablesTableHtml(table);
    if (!visibleTableHtml) return;

    const printWindow = window.open('', '_blank', 'width=1400,height=900');
    if (!printWindow) return;

    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    printWindow.document.open();
    printWindow.document.write(`
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8" />
            <title>Running Receivables Print</title>
            <style>
                @page { size: landscape; margin: 10mm; }
                * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                body { margin: 0; font-family: Arial, sans-serif; color: #0f172a; font-size: 12px; }
                .sys-header { border-bottom: 1px solid #cbd5e1; margin-bottom: 8px; padding: 6px 0 10px; }
                .sys-header-row { min-height: 48px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
                .sys-header-left { display: flex; align-items: center; min-width: 56px; }
                .sys-header-left img { height: 30px; width: auto; display: block; }
                .sys-header-center { flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 3px; }
                .sys-header-center img { height: 28px; width: auto; display: block; }
                .sys-header-center .brand-text { display: block; color: #64748b; font-size: 12px; letter-spacing: 0.18em; font-weight: 700; text-transform: uppercase; }
                .sys-header-right { min-width: 56px; }
                .report-heading { margin: 6px 0 10px; text-align: center; }
                .line-title { margin: 0; font-weight: 700; font-size: 15px; }
                .line-sub { margin: 2px 0 0; font-weight: 700; font-size: 14px; }
                .line-asof { margin: 2px 0 0; font-size: 12px; color: #334155; font-weight: 600; }
                .line-filters { margin: 6px 0 0; font-size: 12px; color: #334155; font-weight: 700; text-align: left; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th, td { border: 1px solid #cbd5e1; padding: 6px 7px; font-size: 11px; word-break: break-word; }
                thead th { background: #ce1126 !important; color: #ffffff !important; font-weight: 700; text-align: center; }
                tbody td:nth-child(5), tbody td:nth-child(6), tbody td:nth-child(7), tbody td:nth-child(8), tbody td:nth-child(9), tbody td:nth-child(10), tfoot td:nth-child(5), tfoot td:nth-child(6), tfoot td:nth-child(7), tfoot td:nth-child(8), tfoot td:nth-child(9), tfoot td:nth-child(10) { text-align: right; }
                tbody tr:nth-child(even) td { background: #f8fafc; }
                tfoot td { background: #f1f5f9; font-weight: 700; }
                .print-footer { margin-top: 10px; font-size: 11px; color: #475569; line-height: 1.5; }
            </style>
        </head>
        <body>
            <div class="sys-header">${exportHeaderHtml}</div>
            <div class="report-heading">
                <p class="line-title">ML Motorcycle Loan</p>
                <p class="line-sub">Running Accounts Receivable</p>
                <p class="line-asof">As of ${esc(reportHeader.asOf)}</p>
                <p class="line-filters">Coverage: ${esc(reportHeader.coverage)}, Status: ${esc(reportHeader.status)}, Region: ${esc(reportHeader.region)}</p>
            </div>
            ${visibleTableHtml}
            <div class="print-footer">
                <div>Generated: ${esc(generatedAt)}</div>
                <div>Generated by: ${esc(generatedBy)}</div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

function getReceivablesPrintHeaderInfo() {
    const monthSelect = document.getElementById('picker-month');
    const yearSelect = document.getElementById('picker-year');
    const halfSelect = document.getElementById('picker-half');
    const statusSelect = document.getElementById('picker-status-inline');
    const regionSelect = document.getElementById('picker-region-inline');

    const selectedMonth = monthSelect ? monthSelect.options[monthSelect.selectedIndex]?.text || '' : '';
    const selectedYear = yearSelect ? String(yearSelect.value || '') : '';
    const asOf = `${selectedMonth} ${new Date().getDate()}, ${selectedYear}`.trim();
    const coverage = halfSelect ? halfSelect.options[halfSelect.selectedIndex]?.text || 'Whole Month' : 'Whole Month';
    const status = statusSelect ? statusSelect.options[statusSelect.selectedIndex]?.text || 'Ongoing' : 'Ongoing';
    const region = regionSelect ? regionSelect.options[regionSelect.selectedIndex]?.text || 'All Regions' : 'All Regions';

    return { asOf, coverage, status, region };
}

function buildVisibleReceivablesTableHtml(sourceTable) {
    const clone = sourceTable.cloneNode(true);
    const bodyRows = clone.querySelectorAll('tbody tr');
    bodyRows.forEach((row) => {
        if (row.style.display === 'none') row.remove();
    });
    if (clone.querySelectorAll('tbody tr').length === 0) return '';
    clone.removeAttribute('id');
    clone.className = '';
    return clone.outerHTML;
}

function buildReceivablesExportHeaderHtml() {
    const headerData = getReceivablesExportHeaderData();
    const leftLogo = headerData.leftLogoSrc ? `<img src="${headerData.leftLogoSrc}" alt="ML Diamond" />` : '';
    const centerLogo = headerData.centerLogoSrc ? `<img src="${headerData.centerLogoSrc}" alt="M Lhuillier Logo" />` : '';
    const brandText = headerData.brandText ? `<span class="brand-text">${escapeReceivablesHtml(headerData.brandText)}</span>` : '';
    return `<div class="sys-header-row"><div class="sys-header-left">${leftLogo}</div><div class="sys-header-center">${centerLogo}${brandText}</div><div class="sys-header-right"></div></div>`;
}

function getReceivablesExportHeaderData() {
    const template = document.getElementById('exportHeaderTemplate');
    if (!template) {
        return {
            leftLogoSrc: `${window.location.origin}${BASE_URL}/public/assets/img/ml-diamond.png`,
            centerLogoSrc: `${window.location.origin}${BASE_URL}/public/assets/img/ml-logo-1.png`,
            brandText: 'ML MOTORCYCLE LOAN'
        };
    }
    const container = document.createElement('div');
    container.innerHTML = template.innerHTML.trim();
    return {
        leftLogoSrc: resolveReceivablesAssetUrl(container.querySelector('[name="logo"] img')?.getAttribute('src')),
        centerLogoSrc: resolveReceivablesAssetUrl(container.querySelector('[name="center"] img')?.getAttribute('src')),
        brandText: container.querySelector('[name="center"] span')?.textContent?.trim() || ''
    };
}

function resolveReceivablesAssetUrl(rawSrc) {
    const src = String(rawSrc || '').trim();
    if (!src) return '';
    if (/^(https?:)?\/\//i.test(src) || src.startsWith('data:')) return src;
    if (src.startsWith('/')) return `${window.location.origin}${src}`;
    const normalizedBase = String(BASE_URL || '').replace(/\/+$/, '');
    if (normalizedBase && (src === normalizedBase || src.startsWith(`${normalizedBase}/`))) {
        return `${window.location.origin}/${src.replace(/^\/+/, '')}`;
    }
    return `${window.location.origin}${normalizedBase ? `${normalizedBase}/` : '/'}${src.replace(/^\/+/, '')}`;
}

function escapeReceivablesHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function resetReportFilters() {
    const d = new Date();
    const yearSelect = document.getElementById('picker-year');
    if(yearSelect) yearSelect.value = d.getFullYear();
    const monthSelect = document.getElementById('picker-month');
    if(monthSelect) monthSelect.value = d.getMonth() + 1;
    
    document.querySelectorAll('input[name="picker-period"]').forEach((radio) => radio.checked = (radio.value === '0'));
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

function ensureMasterRegionsFetched() {
    if (masterLocationsFetched && masterRegions.length) return Promise.resolve(masterRegions);

    return fetch(`${BASE_URL}/public/api/get_master_locations.php`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success && Array.isArray(data.data.regions)) {
                masterRegions = data.data.regions;
            }
            masterLocationsFetched = true;
            return masterRegions;
        })
        .catch(err => { console.error('Could not fetch master regions', err); return []; });
}

function populateInlineRegionDropdown(regions) {
    const select = document.getElementById('picker-region-inline');
    if (!select || select.tagName !== 'SELECT') return;

    const urlParams = new URLSearchParams(window.location.search);
    let selectedRegion = urlParams.get('region') || 'ALL';

    select.innerHTML = '<option value="ALL">All Regions</option>';

    regions.forEach(r => {
        let val = (r.value || r).toString();
        let lbl = (r.label || r).toString().toUpperCase();

        // Auto-correct text URLs to code values silently
        if (selectedRegion.toUpperCase() === lbl) {
            selectedRegion = val;
        }

        let opt = document.createElement('option');
        opt.value = val;
        opt.textContent = lbl;
        if (val == selectedRegion) opt.selected = true;
        select.appendChild(opt);
    });

    const exists = regions.some(r => (r.value || r).toString() == selectedRegion);
    if (selectedRegion !== 'ALL' && !exists) {
        let customOpt = document.createElement('option');
        customOpt.value = selectedRegion;
        customOpt.textContent = selectedRegion.toUpperCase();
        customOpt.selected = true;
        select.insertBefore(customOpt, select.children[1]);
    }
}

// Quick inline changes
function quickChangeStatus() { triggerInlineReload(); }
function quickChangeRegion() { triggerInlineReload(); }
function quickChangePeriod() { triggerInlineReload(); }
function quickChangeHalf() { triggerInlineReload(); }

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
    if (!regionVal || regionVal.toUpperCase() === 'ALL REGIONS') regionVal = 'ALL';

    window.location.href = `?period=${year}-${monthValue}&half=${halfVal}&status=${statusVal}&region=${encodeURIComponent(regionVal)}`;
}