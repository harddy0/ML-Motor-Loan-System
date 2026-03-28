// ==========================================
// DEDUCTION EXPORT: Print & Excel Logic
// ==========================================

document.addEventListener("DOMContentLoaded", function () {
    setupExportDropdown();
});

// Standalone formatters to ensure this file works independently
function _exportFormatDate(dateStr) {
    if (!dateStr || dateStr === '--') return '--';
    const [month, day, year] = dateStr.split('/').map(Number);
    const d = new Date(year, month - 1, day);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function _exportFormatDateTime(dateTimeStr) {
    if (!dateTimeStr || dateTimeStr === '--') return '--';
    const [datePart, timePart, meridiem] = dateTimeStr.split(' ');
    const [month, day, year] = datePart.split('/').map(Number);
    const [hours, minutes] = timePart.split(':').map(Number);
    const d = new Date(year, month - 1, day, hours, minutes);
    return `${d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}`;
}

function setupExportDropdown() {
    const exportBtn = document.getElementById('exportMenuBtn');
    const exportMenu = document.getElementById('exportMenu');
    const excelBtn = document.getElementById('exportDeductionBtn');
    const printBtn = document.getElementById('printDeductionBtn');

    if (!exportBtn || !exportMenu) return;

    exportBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        exportMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!exportBtn.contains(e.target) && !exportMenu.contains(e.target)) {
            exportMenu.classList.add('hidden');
        }
    });

    if (excelBtn) excelBtn.addEventListener('click', () => window.exportDeductionsExcel());
    if (printBtn) printBtn.addEventListener('click', () => window.printDeductionsList());
}

window.exportDeductionsExcel = function () {
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) exportMenu.classList.add('hidden');

    const search = document.getElementById('searchInput')?.value.trim() ?? '';
    const from = document.getElementById('fromDate')?.value ?? '';
    const to = document.getElementById('toDate')?.value ?? '';

    const queryParams = new URLSearchParams({ search, from, to });
    const baseUrlPath = typeof BASE_URL !== 'undefined' ? BASE_URL : '../../..';
    window.location.href = `${baseUrlPath}/public/api/export_deductions.php?${queryParams.toString()}`;
};

window.printDeductionsList = async function () {
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) exportMenu.classList.add('hidden');

    const data = await fetchAllDeductionsForExport();
    if (!data.length) return;

    const reportInfo = getDeductionReportInfo();
    const exportHeaderHtml = buildDeductionExportHeaderHtml();
    const printWindow = window.open('', '_blank', 'width=1200,height=800');
    if (!printWindow) return;

    const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    const tableRowsHtml = data.map((row) => {
        const fullName = `${row.first || ''} ${row.last || ''}`.trim();
        const amount = parseFloat(row.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return `
            <tr>
                <td>${esc(row.pn_number || '---')}</td>
                <td>${esc(row.id || '')}</td>
                <td>${esc(_exportFormatDate(row.p_date || '--'))}</td>
                <td>${esc(fullName)}</td>
                <td>${esc(amount)}</td>
                <td>${esc(row.region || '')}</td>
                <td>${esc(_exportFormatDateTime(row.i_date || '--'))}</td>
            </tr>
        `;
    }).join('');

    const totalDeduction = data.reduce((sum, row) => sum + (parseFloat(row.amount || 0) || 0), 0);
    const totalDeductionFormatted = totalDeduction.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    printWindow.document.open();
    printWindow.document.write(`
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8" />
            <style>
                @page { size: landscape; margin: 10mm; }
                body { font-family: Arial, sans-serif; color: #0f172a; margin: 0; }
                * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .sys-header { border-bottom: 1px solid #cbd5e1; margin-bottom: 10px; padding: 8px 0 10px; }
                .sys-header-row { min-height: 48px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
                .sys-header-left { display: flex; align-items: center; min-width: 56px; }
                .sys-header-left img { height: 30px; width: auto; display: block; }
                .sys-header-center { flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 3px; }
                .sys-header-center img { height: 28px; width: auto; display: block; }
                .sys-header-center .brand-text { display: block; color: #64748b; font-size: 12px; letter-spacing: 0.18em; font-weight: 700; text-transform: uppercase; }
                .sys-header-right { min-width: 56px; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th, td { border: 1px solid #cbd5e1; padding: 6px 7px; font-size: 11px; word-break: break-word; text-align: left; }
                th { background: #ce2216 !important; color: #ffffff !important; font-weight: 700; }
                th:nth-child(5), td:nth-child(5) { text-align: right; }
                tr:nth-child(even) td { background: #f8fafc; }
                .total-row td { background: #f8fafc; font-weight: 700; }
                .total-label { text-align: right; color: #0f172a; }
                .total-amount { text-align: right; color: #ce2216; }
                .report-footer { margin-top: 10px; font-size: 11px; color: #475569; text-align: left; }
            </style>
        </head>
        <body>
            <div class="sys-header">${exportHeaderHtml}</div>
            <table>
                <thead>
                    <tr>
                        <th>System Loan No.</th>
                        <th>Employee ID</th>
                        <th>Due Date</th>
                        <th>Full Name</th>
                        <th>Deduction</th>
                        <th>Region</th>
                        <th>Date Imported</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRowsHtml}
                    <tr class="total-row">
                        <td colspan="4" class="total-label">TOTAL COLLECTION:</td>
                        <td class="total-amount">${esc(totalDeductionFormatted)}</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <div class="report-footer">Generated by: ${esc(reportInfo.generatedBy)} | Generated: ${esc(reportInfo.renderedAt)}</div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
};

async function fetchAllDeductionsForExport() {
    const search = document.getElementById('searchInput')?.value.trim() ?? '';
    const from = document.getElementById('fromDate')?.value ?? '';
    const to = document.getElementById('toDate')?.value ?? '';

    const params = new URLSearchParams({ page: '1', limit: '50000', search, from, to });
    const baseUrlPath = typeof BASE_URL !== 'undefined' ? BASE_URL : '../../..';

    try {
        const response = await fetch(`${baseUrlPath}/public/api/get_paginated_deductions.php?${params.toString()}`);
        const result = await response.json();
        if (result && result.success && result.payload && Array.isArray(result.payload.data)) {
            return result.payload.data;
        }
    } catch (error) {
        console.error('Failed to load data for print export:', error);
    }
    return [];
}

function getDeductionReportInfo() {
    const renderedAt = new Date().toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    let generatedBy = 'SYSTEM USER';
    if (typeof CURRENT_USER_FULLNAME !== 'undefined' && String(CURRENT_USER_FULLNAME).trim()) {
        generatedBy = String(CURRENT_USER_FULLNAME).trim().toUpperCase();
    }
    return { renderedAt, generatedBy };
}

function buildDeductionExportHeaderHtml() {
    const headerData = getDeductionExportHeaderData();
    const leftLogo = headerData.leftLogoSrc ? `<img src="${headerData.leftLogoSrc}" alt="ML Diamond" />` : '';
    const centerLogo = headerData.centerLogoSrc ? `<img src="${headerData.centerLogoSrc}" alt="M Lhuillier Logo" />` : '';

    return `
        <div class="sys-header-row">
            <div class="sys-header-left">${leftLogo}</div>
            <div class="sys-header-center">
                ${centerLogo}
                <span class="brand-text">${escapeDeductionHtml(headerData.brandText)}</span>
            </div>
            <div class="sys-header-right"></div>
        </div>
    `;
}

function getDeductionExportHeaderData() {
    const template = document.getElementById('exportHeaderTemplate');
    if (!template) {
        const baseUrlPath = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
        return {
            leftLogoSrc: `${window.location.origin}${baseUrlPath}/public/assets/img/ml-diamond.png`,
            centerLogoSrc: `${window.location.origin}${baseUrlPath}/public/assets/img/ml-logo-1.png`,
            brandText: 'ML MOTORCYCLE LOAN'
        };
    }

    const container = document.createElement('div');
    container.innerHTML = template.innerHTML.trim();
    return {
        leftLogoSrc: resolveDeductionAssetUrl(container.querySelector('[name="logo"] img')?.getAttribute('src')),
        centerLogoSrc: resolveDeductionAssetUrl(container.querySelector('[name="center"] img')?.getAttribute('src')),
        brandText: container.querySelector('[name="center"] span')?.textContent?.trim() || 'ML MOTORCYCLE LOAN'
    };
}

function resolveDeductionAssetUrl(rawSrc) {
    const src = String(rawSrc || '').trim();
    if (!src) return '';
    if (/^(https?:)?\/\//i.test(src) || src.startsWith('data:')) return src;
    if (src.startsWith('/')) return `${window.location.origin}${src}`;

    const normalizedBase = typeof BASE_URL !== 'undefined' ? String(BASE_URL).replace(/\/+$/, '') : '';
    if (normalizedBase && (src === normalizedBase || src.startsWith(`${normalizedBase}/`))) {
        return `${window.location.origin}/${src.replace(/^\/+/, '')}`;
    }

    const normalizedSrc = src.replace(/^\/+/, '');
    const basePath = normalizedBase ? `${normalizedBase}/` : '/';
    return `${window.location.origin}${basePath}${normalizedSrc}`;
}

function escapeDeductionHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}