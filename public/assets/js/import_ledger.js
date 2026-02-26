document.addEventListener('DOMContentLoaded', function() {
    
    const uploadForm = document.getElementById('uploadLedgerForm');
    const fileInput = document.getElementById('ledgerFile');
    const displayFileName = document.getElementById('displayFileName');
    const buttonContainer = document.getElementById('buttonContainer');
    const btnUpload = document.getElementById('btnUploadLedger');
    const btnConfirm = document.getElementById('btnConfirmLedgerSave');
    const previewModal = document.getElementById('importLedgerPreviewModal');
    
    let parsedPayload = null;

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                displayFileName.textContent = this.files[0].name;
                displayFileName.classList.remove('text-[#dc2626]');
                displayFileName.classList.add('text-slate-800');
                buttonContainer.classList.remove('hidden');
            } else {
                displayFileName.textContent = 'No file selected';
                displayFileName.classList.add('text-[#dc2626]');
                displayFileName.classList.remove('text-slate-800');
                buttonContainer.classList.add('hidden');
            }
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!fileInput.files.length) return;

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            btnUpload.disabled = true;
            btnUpload.innerText = 'Analyzing...';

            fetch('../../api/parse_ledger_import.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (err) {
                    console.error("RAW SERVER RESPONSE:", text);
                    throw new Error("Server error (Check console for raw output)");
                }
            })
            .then(data => {
                btnUpload.disabled = false;
                btnUpload.innerText = 'Process File';

                if (data.success) {
                    parsedPayload = data;
                    renderPreviewModal(data);
                } else {
                    alert('Error parsing file: ' + data.error);
                }
            })
            .catch(error => {
                btnUpload.disabled = false;
                btnUpload.innerText = 'Process File';
                alert('System Error: ' + error.message);
            });
        });
    }

    function renderPreviewModal(data) {
        const b = data.borrower;
        const numFormat = (num) => parseFloat(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        document.getElementById('previewName').innerText = `${b.first_name} ${b.last_name}`;
        document.getElementById('previewId').innerText = b.employe_id || 'N/A';
        document.getElementById('previewContact').innerText = b.contact_number || 'N/A';
        document.getElementById('previewRegion').innerText = b.region || 'N/A';
        document.getElementById('previewBranch').innerText = b.branch || 'N/A';

        document.getElementById('previewAmount').innerText = '₱' + numFormat(b.loan_amount);
        document.getElementById('previewDeduction').innerText = '₱' + numFormat(b.semi_monthly_amortization);
        document.getElementById('previewRef').innerText = b.reference_number || 'N/A';
        document.getElementById('previewPn').innerText = b.pn_number || 'N/A';
        document.getElementById('previewGranted').innerText = b.date_released;
        document.getElementById('previewTerms').innerText = `${b.terms} Mos.`;
        document.getElementById('previewMaturity').innerText = b.maturity_date;

        document.getElementById('previewRowCount').innerText = `${data.ledger.length} Payment Rows Parsed`;
        const tbody = document.getElementById('previewLedgerTableBody');
        tbody.innerHTML = '';

        data.ledger.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition-colors';
            
            // Fix for dynamic badge coloring based on Status
            let badgeStyle = 'bg-amber-100 text-amber-700'; // Default to UNPAID
            let rowColor = 'bg-amber-50';
            
            if (row.status === 'PAID') {
                badgeStyle = 'bg-green-100 text-green-700';
                rowColor = 'bg-green-50';
            } else if (row.status === 'NO DEDUCTION') {
                badgeStyle = 'bg-slate-200 text-slate-700';
                rowColor = 'bg-slate-50';
            }

            const statusBadge = `<span class="px-2 py-1 ${badgeStyle} text-[10px] font-black uppercase rounded-full tracking-wider">${row.status}</span>`;

            tr.innerHTML = `
                <td class="px-4 py-2 border-r border-slate-100 text-center">${row.installment_no}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-center">${row.date}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${numFormat(row.principal)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${numFormat(row.interest)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right font-black italic ${rowColor}">${numFormat(row.total)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${numFormat(row.balance)}</td>
                <td class="px-4 py-2 text-center">${statusBadge}</td>
            `;
            tbody.appendChild(tr);
        });

        previewModal.classList.remove('hidden');
        previewModal.classList.add('flex');
    }

    if (btnConfirm) {
        btnConfirm.addEventListener('click', function() {
            if (!parsedPayload) return;

            btnConfirm.disabled = true;
            btnConfirm.innerText = 'Saving...';

            fetch('../../api/save_imported_ledger.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ borrower: parsedPayload.borrower, ledger: parsedPayload.ledger })
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (err) {
                    console.error("RAW SERVER RESPONSE:", text);
                    throw new Error("Server error during save.");
                }
            })
            .then(data => {
                btnConfirm.disabled = false;
                btnConfirm.innerText = 'Confirm Save';

                if (data.success) {
                    alert('Ledger imported and saved successfully!');
                    previewModal.classList.add('hidden');
                    previewModal.classList.remove('flex');
                    uploadForm.reset();
                    window.location.href = '../../reports/ledger/index.php'; 
                } else {
                    alert('Error saving data: ' + data.error);
                }
            })
            .catch(error => {
                btnConfirm.disabled = false;
                btnConfirm.innerText = 'Confirm Save';
                alert('System Error: ' + error.message);
            });
        });
    }
});