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
    // FIX: Safely parse as text first, then force it to JSON to prevent parsing failures
    .then(async res => {
        const textResponse = await res.text();
        try {
            return JSON.parse(textResponse);
        } catch (e) {
            console.error("Raw response from server:", textResponse);
            throw new Error("Server returned invalid JSON format. Check console.");
        }
    })
    .then(result => {
        proceedBtn.innerText = originalText;
        proceedBtn.disabled = false;

        // Strictly check for true boolean
        if(result.success === true) {
            showImportResults(result); 
        } else {
            alert("Database Error: " + (result.error || "Unknown error occurred."));
        }
    })
    .catch(err => {
        proceedBtn.innerText = originalText;
        proceedBtn.disabled = false;
        console.error("Detailed Error:", err);
        alert("JavaScript Error: " + err.message + "\n\nMake sure the new HTML modal is pasted into index.php.");
    });
}

// 4. Show the comprehensive results modal
function showImportResults(result) {
    // Hide Preview Modal
    document.getElementById('importPreviewModal').classList.replace('flex', 'hidden');
    
    const modal = document.getElementById('importResultsModal');
    const title = document.getElementById('result-title');
    const subtitle = document.getElementById('result-subtitle');
    const detailsContainer = document.getElementById('result-details-container');
    const issuesList = document.getElementById('result-issues-list');
    const iconContainer = document.getElementById('result-icon-container');

    // Set base success text
    subtitle.innerText = `Successfully recorded ${result.success_count} payment(s).`;
    issuesList.innerHTML = '';

    // Combine discrepancies (short/excess) and standard errors (missing loans)
    const allIssues = [...(result.discrepancies || []), ...(result.errors || [])];

    if (allIssues.length > 0) {
        // Change theme to Warning/Notice
        iconContainer.className = "inline-flex bg-yellow-100 p-4 rounded-full mb-4 shadow-sm";
        iconContainer.innerHTML = `<svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`;
        title.innerText = "Processed with Notices";
        
        // Populate the list
        allIssues.forEach(issue => {
            const li = document.createElement('li');
            li.className = "flex items-start gap-2 bg-white p-3 rounded-lg shadow-sm";
            li.innerHTML = `<span class="text-[#e11d48] mt-0.5">•</span> <span>${issue}</span>`;
            issuesList.appendChild(li);
        });
        
        detailsContainer.classList.remove('hidden');
        detailsContainer.classList.add('block');
    } else {
        // Pure Success Theme
        iconContainer.className = "inline-flex bg-green-100 p-4 rounded-full mb-4 shadow-sm";
        iconContainer.innerHTML = `<svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>`;
        title.innerText = "Upload Complete";
        
        detailsContainer.classList.add('hidden');
        detailsContainer.classList.remove('block');
    }

    // Show Modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeImportModal() {
    document.getElementById('importPreviewModal').classList.replace('flex', 'hidden');
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