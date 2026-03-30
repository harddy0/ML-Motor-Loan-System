document.addEventListener('DOMContentLoaded', function() {
    setupExportDropdown();
});

function setupExportDropdown() {
    const exportBtn = document.getElementById('exportMenuBtn');
    const exportMenu = document.getElementById('exportMenu');
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
}

window.exportBorrowersExcel = function() {
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) exportMenu.classList.add('hidden');

    const exportData = getCurrentTabExportData();
    if (!exportData.rows.length) {
        return;
    }

    const reportInfo = getReportInfo();
    const title = exportData.tab === 'pending' ? 'Upload KPTN Form' : 'All Loans';
    const reportLabel = (function(tab) {
        switch (tab) {
            case 'active': return 'Ongoing Loan Report';
            case 'fully-paid': return 'Fully Paid Loan Report';
            case 'pending': return 'Upload KPTN Form';
            case 'inactive': return 'Inactive Loan Report';
            case 'void': return 'Void Loan Report';
            default: return title;
        }
    })(exportData.tab);

    fetch(`${BASE_URL}/public/api/export_borrowers_excel.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            tab: exportData.tab,
            title,
            reportLabel,
            headers: exportData.headers,
            rows: exportData.rows,
            generatedBy: reportInfo.generatedBy,
            renderedAt: reportInfo.renderedAt
        })
    })
    .then(async (response) => {
        if (!response.ok) {
            let errorText = 'Failed to export Excel file.';
            try {
                const data = await response.json();
                if (data && data.error) errorText = data.error;
            } catch (_) {
            }
            throw new Error(errorText);
        }
        return response.blob();
    })
    .then((blob) => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `borrowers_${exportData.tab}_report_${new Date().toISOString().slice(0, 10)}.xlsx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    })
    .catch((error) => {
        alert(error.message || 'Failed to export Excel file.');
    });
};

window.printBorrowersList = function() {
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) exportMenu.classList.add('hidden');

    const exportData = getCurrentTabExportData();
    if (!exportData.rows.length) return;

    const title = exportData.tab === 'pending' ? 'Upload KPTN Form' : 'All Loans';
    const reportLabel = (function(tab) {
        switch (tab) {
            case 'active': return 'Ongoing Loan Report';
            case 'fully-paid': return 'Fully Paid Loan Report';
            case 'pending': return 'Upload KPTN Form';
            case 'inactive': return 'Inactive Loan Report';
            case 'void': return 'Void Loan Report';
            default: return title;
        }
    })(exportData.tab);
    const printWindow = window.open('', 's', 'width=1200,height=800');
    if (!printWindow) return;

    const reportInfo = getReportInfo();
    const exportHeaderHtml = buildExportHeaderHtml();

    const tableHeaderHtml = `<tr>${exportData.headers.map(h => `<th>${h}</th>`).join('')}</tr>`;
    const tableRowsHtml = exportData.rows.map(row => `<tr>${row.map(c => `<td>${String(c ?? '')}</td>`).join('')}</tr>`).join('');

    printWindow.document.open();
    printWindow.document.write(`
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8" />
            <style>
                @page { size: landscape; margin: 10mm; }
                body { font-family: Arial, sans-serif; color: #0f172a; margin: 0; }
                .sys-header {
                    border-bottom: 1px solid #cbd5e1;
                    margin-bottom: 10px;
                    padding: 8px 0 10px;
                }
                .sys-header-row {
                    min-height: 48px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                }
                .sys-header-left {
                    display: flex;
                    align-items: center;
                    min-width: 56px;
                }
                .sys-header-left img { height: 30px; width: auto; display: block; }
                .sys-header-center {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    gap: 3px;
                }
                .sys-header-center img { height: 28px; width: auto; display: block; }
                .sys-header-center .brand-text {
                    display: block;
                    color: #64748b;
                    font-size: 12px;
                    letter-spacing: 0.18em;
                    font-weight: 700;
                    text-transform: uppercase;
                }
                .sys-header-right {
                    min-width: 56px;
                }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th, td {
                    border: 1px solid #cbd5e1;
                    padding: 6px 7px;
                    font-size: 11px;
                    word-break: break-word;
                }
                th {
                    background: #ce1126;
                    color: #fff;
                    text-align: left;
                    font-weight: 700;
                }
                tr:nth-child(even) td { background: #f8fafc; }
                .report-footer {
                    margin-top: 10px;
                    font-size: 11px;
                    color: #475569;
                    text-align: left;
                }
            </style>
        </head>
            <body>
            <div class="sys-header">${exportHeaderHtml}</div>
            <div style="text-align:left; font-weight:700; font-size:14px; margin:8px 0">${reportLabel}</div>

            <table>
                <thead>${tableHeaderHtml}</thead>
                <tbody>${tableRowsHtml}</tbody>
            </table>

            <div class="report-footer">Generated By: ${reportInfo.generatedBy}<br>Generated Date and Time: ${reportInfo.renderedAt}</div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
};

function getCurrentTabExportData() {
    function extractFromTable(tableEl) {
        const headers = Array.from(tableEl.querySelectorAll('thead th')).map(h => h.innerText.trim());
        const rows = [];
        const bodyRows = tableEl.querySelectorAll('tbody tr');
        bodyRows.forEach(tr => {
            const cells = tr.querySelectorAll('td');
            if (!cells.length) return;
            if (cells.length === 1 && cells[0].hasAttribute('colspan')) return;
            rows.push(Array.from(cells).map(td => td.innerText.trim()));
        });
        return { headers, rows };
    }

    const tables = [
        { id: 'table-active', tab: 'active' },
        { id: 'table-fully-paid', tab: 'fully-paid' },
        { id: 'table-pending', tab: 'pending' },
        { id: 'table-inactive', tab: 'inactive' },
        { id: 'table-void', tab: 'void' }
    ];

    for (const t of tables) {
        const el = document.getElementById(t.id);
        if (el && !el.classList.contains('hidden')) {
            const data = extractFromTable(el);
            // For active and fully-paid exports, remove the last "Action" column
            if (t.tab === 'active' || t.tab === 'fully-paid') {
                if (data.headers.length > 0) data.headers.pop();
                const trimmedRows = data.rows.map(r => r.slice(0, Math.max(0, r.length - 1)));
                return { tab: t.tab, headers: data.headers, rows: trimmedRows };
            }

            return { tab: t.tab, headers: data.headers, rows: data.rows };
        }
    }

    // Fallback to using currentBorrowersData for active tab if DOM tables aren't present
    const headers = [
        'System Loan No.',
        'Reference Number',
        'Date Released',
        'Employee ID',
        'Full Name',
        'Region',
        'Status'
    ];

    const rows = (Array.isArray(currentBorrowersData) ? currentBorrowersData : []).map((b) => ([
        b.pn_no || '---',
        b.reference_no || '---',
        formatDate(b.raw_date),
        b.id || '',
        b.name || '',
        b.region || '',
        b.current_status || ''
    ]));

    return { tab: 'active', headers, rows };
};

function getReportInfo() {
    const renderedAt = new Date().toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    let generatedBy = 'SYSTEM USER';
    if (typeof CURRENT_USER_FULLNAME !== 'undefined' && String(CURRENT_USER_FULLNAME).trim()) {
        generatedBy = String(CURRENT_USER_FULLNAME).trim().toUpperCase();
    }

    return { renderedAt, generatedBy };
}

function buildExportHeaderHtml() {
    const headerData = getExportHeaderData();
    const leftLogo = headerData.leftLogoSrc ? `<img src="${headerData.leftLogoSrc}" alt="ML Diamond" />` : '';
    const centerLogo = headerData.centerLogoSrc ? `<img src="${headerData.centerLogoSrc}" alt="M Lhuillier Logo" />` : '';

    return `
        <div class="sys-header-row">
            <div class="sys-header-left">${leftLogo}</div>
            <div class="sys-header-center">
                ${centerLogo}
                <span class="brand-text">${escapeHtml(headerData.brandText)}</span>
            </div>
            <div class="sys-header-right"></div>
        </div>
    `;
}

function getExportHeaderData() {
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
    const leftLogoRaw = container.querySelector('[name="logo"] img')?.getAttribute('src') || '';
    const centerLogoRaw = container.querySelector('[name="center"] img')?.getAttribute('src') || '';
    const brandText = container.querySelector('[name="center"] span')?.textContent?.trim() || 'ML MOTORCYCLE LOAN';

    return {
        leftLogoSrc: resolveExportAssetUrl(leftLogoRaw),
        centerLogoSrc: resolveExportAssetUrl(centerLogoRaw),
        brandText
    };
}

function resolveExportAssetUrl(rawSrc) {
    const src = String(rawSrc || '').trim();
    if (!src) return '';
    if (/^(https?:)?\/\//i.test(src) || src.startsWith('data:')) return src;

    if (src.startsWith('/')) {
        return `${window.location.origin}${src}`;
    }

    const normalizedBase = String(BASE_URL || '').replace(/\/+$/, '');
    if (normalizedBase && (src === normalizedBase || src.startsWith(`${normalizedBase}/`))) {
        return `${window.location.origin}/${src.replace(/^\/+/, '')}`;
    }

    const normalizedSrc = src.replace(/^\/+/, '');
    const basePath = normalizedBase ? `${normalizedBase}/` : '/';
    return `${window.location.origin}${basePath}${normalizedSrc}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function buildExportHeaderPhotoSrc() {
    return `${window.location.origin}${BASE_URL}/public/assets/img/header.png?t=${Date.now()}`;
}