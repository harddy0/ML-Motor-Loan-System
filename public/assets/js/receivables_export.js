// ==========================================
// RECEIVABLES EXPORT: Print & Excel Output
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    setupReceivablesExportDropdown();
});

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

function printReceivablesReport() {
    const menu = document.getElementById('receivablesExportMenu');
    if (menu) menu.classList.add('hidden');

    const table = document.getElementById('receivablesTable');
    if (!table) return;

    const reportHeader = getReceivablesPrintHeaderInfo();
    const generatedAt = new Date().toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
    const generatedBy = (typeof CURRENT_USER_FULLNAME !== 'undefined' && String(CURRENT_USER_FULLNAME).trim())
        ? String(CURRENT_USER_FULLNAME).trim().toUpperCase()
        : 'SYSTEM USER';

    const exportHeaderHtml = buildReceivablesExportHeaderHtml();
    const visibleTableHtml = buildVisibleReceivablesTableHtml(table);
    if (!visibleTableHtml) return;

    const printWindow = window.open('', '_blank', 'width=1400,height=900');
    if (!printWindow) return;

    const esc = (value) => String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

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