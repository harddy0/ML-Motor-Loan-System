let parsedDeductions = [];

document.addEventListener("DOMContentLoaded", function () {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');

    if (dropZone && fileInput) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, e => {
                e.preventDefault(); e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('bg-[#eeeeee]/90', 'border-[#8a3333]'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('bg-[#eeeeee]/90', 'border-[#8a3333]'), false);
        });

        dropZone.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                updateName(fileInput);
            }
        });
    }
});

function updateName(input) {
    const fileNameDisplay = document.getElementById('displayFileName');
    if (input.files && input.files[0]) {
        fileNameDisplay.innerText = input.files[0].name;
    } else {
        fileNameDisplay.innerText = 'No file selected';
    }
}

// 1. Sends the file to be parsed
function openImportModal() {
    const fileInput = document.getElementById('fileInput');
    if (!fileInput || !fileInput.files.length) {
        alert("Please select or drop a file first.");
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    fetch('../api/parse_payroll_deduction.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if(result.success) {
            parsedDeductions = result.data;
            populatePreviewTable(parsedDeductions);
            
            const modal = document.getElementById('importPreviewModal');
            modal.classList.replace('hidden', 'flex');
        } else {
            alert("Error reading file: " + result.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("System Error reading file.");
    });
}

// 2. Dynamically fills the HTML table in the modal
function populatePreviewTable(data) {
    const tbody = document.getElementById('preview-body');
    tbody.innerHTML = '';

    if(data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-xs font-bold text-slate-500 uppercase">No valid data found in the file.</td></tr>';
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = "border-b border-slate-100 hover:bg-slate-50 transition-colors";
        tr.innerHTML = `
            <td class="px-4 py-2 border-x text-center bg-yellow-100/50">${row.id}</td>
            <td class="px-4 py-2 border-x text-center">${row.date}</td>
            <td class="px-4 py-2 border-x bg-yellow-100/50 uppercase">${row.fname}</td>
            <td class="px-4 py-2 border-x uppercase">${row.lname}</td>
            <td class="px-4 py-2 border-x text-center bg-yellow-100/50 font-black italic">${row.amount}</td>
            <td class="px-4 py-2 border-x text-center">${row.region}</td>
        `;
        tbody.appendChild(tr);
    });
}

// 3. Sends the confirmed data to the processing Service
function processImport() {
    if(parsedDeductions.length === 0) {
        alert("No data to process.");
        return;
    }

    const proceedBtn = document.querySelector('#importPreviewModal button.bg-\\[\\#e11d48\\]');
    const originalText = proceedBtn.innerText;
    proceedBtn.innerText = "PROCESSING...";
    proceedBtn.disabled = true;

    fetch('../api/process_payroll_deduction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ deductions: parsedDeductions })
    })
    .then(res => res.json())
    .then(result => {
        proceedBtn.innerText = originalText;
        proceedBtn.disabled = false;

        if(result.success) {
            // Check if there are errors on specific rows
            if(result.errors && result.errors.length > 0) {
                alert("Processed " + result.success_count + " rows successfully.\n\nISSUES DETECTED:\n" + result.errors.join("\n"));
            }
            showSuccessAlert();
        } else {
            alert("Database Error: " + result.error);
        }
    })
    .catch(err => {
        proceedBtn.innerText = originalText;
        proceedBtn.disabled = false;
        console.error("Fetch Error:", err);
        alert("Fatal error communicating with the server. Check the developer console (F12).");
    });
}

function showSuccessAlert() {
    document.getElementById('importPreviewModal').classList.replace('flex', 'hidden');
    document.getElementById('successAlertModal').classList.remove('hidden');
    document.getElementById('successAlertModal').classList.add('flex');
}

function closeImportModal() {
    document.getElementById('importPreviewModal').classList.replace('flex', 'hidden');
}

function closeAllModals() {
    document.getElementById('successAlertModal').classList.replace('flex', 'hidden');
    document.getElementById('fileInput').value = "";
    document.getElementById('displayFileName').innerText = "No file selected";
    window.location.reload(); // Refresh the page to clear states
}