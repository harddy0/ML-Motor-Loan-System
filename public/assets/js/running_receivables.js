document.addEventListener('DOMContentLoaded', () => {
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
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex'); 
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
            box.classList.add('border-green-500', 'bg-green-50/30');
            if (indicator) {
                indicator.classList.remove('border-slate-200');
                indicator.classList.add('border-green-500');
                innerDot.classList.remove('scale-0');
                innerDot.classList.add('scale-100');
            }
        } else {
            box.classList.remove('border-green-500', 'bg-green-50/30');
            box.classList.add('border-slate-100', 'bg-white');
            if (indicator) {
                indicator.classList.remove('border-green-500');
                indicator.classList.add('border-slate-200');
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
    
    // Format month to have leading zero (e.g. '01', '02')
    const paddedMonth = monthValue.padStart(2, '0');
    const periodString = `${year}-${paddedMonth}`;

    let halfParam = 'ALL';
    if(periodVal === '1') halfParam = '1ST';
    if(periodVal === '2') halfParam = '2ND';

    // Redirect the page with the GET parameters
    window.location.href = `?period=${periodString}&half=${halfParam}&status=${statusVal}`;
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