<?php

require_once __DIR__ . '/../../src/includes/init.php';

?>



<style>

    .no-scrollbar {

        -ms-overflow-style: none;

        scrollbar-width: none;

    }

    .no-scrollbar::-webkit-scrollbar {

        display: none;

    }

</style>



    <div class="flex flex-col lg:flex-row justify-between items-end mb-3 pb-2 shrink-0">

        <div>

            <h1 class="text-2xl">

               Upload Payroll deduction

            </h1>

        </div>

    </div>



    <div id="dropZone" class="bg-white rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center transition-all hover:border-slate-500 hover:bg-slate-50/50 mx-10 mb-10 shadow-sm flex-1 min-h-[430px] overflow-hidden no-scrollbar">

        <input type="file" id="fileInput" accept=".xlsx, .xls, .csv" class="hidden" onchange="updateName(this)">



        <div onclick="document.getElementById('fileInput').click()" class="relative mb-6 cursor-pointer group">

            <div class="w-20 h-24 bg-slate-50 rounded-xl relative border-2 border-slate-200 overflow-hidden group-hover:border-green-500 group-hover:bg-white transition-all duration-300">

                <div class="absolute top-0 right-0 w-6 h-6 bg-slate-200 rounded-bl-lg group-hover:bg-green-500 transition-colors"></div>

                <div class="absolute bottom-0 left-0 right-0 h-8 bg-slate-100 flex items-center justify-center group-hover:bg-green-500 transition-colors">

                    <span class=" font-black text-slate-400 group-hover:text-white">XLSX</span>

                </div>

                <div class="mt-8 px-4 space-y-2">

                    <div class="h-1 bg-slate-200 rounded w-full"></div>

                    <div class="h-1 bg-slate-200 rounded w-3/4"></div>

                    <div class="h-1 bg-slate-100 rounded w-full"></div>

                </div>

            </div>

            <div class="absolute inset-0 bg-[#1d7fe1]/5 rounded-xl scale-110 opacity-0 group-hover:opacity-100 transition-all duration-500 -z-10"></div>

        </div>



        <div class="mb-2 shrink-0">

            <label for="fileInput" class="cursor-pointer">

                <h2 class="text-slate-800 text-xs text-center">

                    Drag & Drop file here or <span class="text-[#1d7fe1] hover:underline">Choose File</span>

                </h2>

            </label>

            <p class="text-slate-400 text-center mt-1">

                Supported formats: .XLSX, .XLS, .CSV

            </p>

        </div>



        <div class="mb-8 text-center shrink-0">

            <div class="inline-flex items-center bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">

                <span class=" text-slate-400 mr-2">File:</span>

                <span id="displayFileName" class=" text-[#e11d48] ">No file selected</span>

            </div>

        </div>



        <div id="buttonContainer" class="hidden flex items-center gap-4 shrink-0">

            <button onclick="openImportModal()" class="px-4 py-1 bg-[#e11d48] text-white rounded-full shadow-sm hover:shadow-lg hover:brightness-110 transition-all duration-200 active:scale-95">

                Import data

            </button>

            <button onclick="window.location.reload()" class="px-4 py-1 bg-white text-slate-400 border border-slate-200 rounded-full hover:bg-slate-50 hover:text-slate-600 hover:shadow-sm transition-all duration-200 active:scale-95">

                cancel

            </button>

        </div>

    </div>



    <div id="importPreviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div class="bg-[#eeeeee] w-full max-w-5xl rounded-xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">

            <div class="p-6 overflow-x-auto flex-1">

                <table class="w-full text-left border-collapse bg-white rounded-lg overflow-hidden shadow-sm">

                    <thead>

                        <tr class="text-[#8a3333] font-black  ">

                            <th class="px-4 py-3 text-center border-b">ID No</th>

                            <th class="px-4 py-3 text-center border-b">Payroll Date</th>

                            <th class="px-4 py-3 text-center border-b">First Name</th>

                            <th class="px-4 py-3 text-center border-b">Last Name</th>

                            <th class="px-4 py-3 text-center border-b">Amount Paid</th>

                            <th class="px-4 py-3 text-center border-b">Region</th>

                        </tr>

                    </thead>

                    <tbody id="preview-body" class="text-slate-800">

                        <?php

                        $preview_data = [

                            ['id' => '20150428', 'date' => '01/30/2026', 'fname' => 'REMARIM', 'lname' => 'CLARISA', 'amount' => '3825', 'region' => 'Head Office'],

                            ['id' => '20190617', 'date' => '01/30/2026', 'fname' => 'GOZON JR', 'lname' => 'FRANCIS', 'amount' => '1585', 'region' => 'Head Office'],

                        ];

                        foreach($preview_data as $row): ?>

                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">

                            <td class="px-4 py-2 border-x text-center bg-yellow-100/50"><?= $row['id'] ?></td>

                            <td class="px-4 py-2 border-x text-center"><?= $row['date'] ?></td>

                            <td class="px-4 py-2 border-x bg-yellow-100/50"><?= $row['fname'] ?></td>

                            <td class="px-4 py-2 border-x "><?= $row['lname'] ?></td>

                            <td class="px-4 py-2 border-x text-center bg-yellow-100/50 font-black italic"><?= $row['amount'] ?></td>

                            <td class="px-4 py-2 border-x text-center"><?= $row['region'] ?></td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>



            <div class="p-6 pt-0 flex justify-end gap-4">

                <button

                    onclick="processImport()"

                    class="px-4 py-1 bg-[#e11d48] text-white rounded-full

                    font-black

                    shadow-sm hover:shadow-md hover:brightness-110

                    transition-all duration-200 ease-in-out active:scale-95 active:shadow-inner">

                    Proceed

                </button>

                <button onclick="closeImportModal()" class="px-4 py-1 bg-white/20 text-slate-500 border border-slate-200 rounded-full font-black hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 hover:shadow-sm transition-all duration-200 active:scale-95">

                    Cancel

                </button>

            </div>

        </div>

    </div>



    <div id="importResultsModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl p-8 relative overflow-hidden flex flex-col max-h-[90vh]">

           

            <div class="text-center mb-6 shrink-0">

                <div class="inline-flex bg-green-100 p-4 rounded-full mb-4 shadow-sm" id="result-icon-container">

                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>

                    </svg>

                </div>

                <h3 class="text-slate-800 font-black text-xl" id="result-title">Upload Complete</h3>

                <p class="text-slate-500 text-xs font-bold mt-1" id="result-subtitle">Successfully processed 0 records.</p>

            </div>



            <div id="result-details-container" class="hidden flex-1 overflow-y-auto min-h-0 bg-slate-50 rounded-xl p-5 border border-slate-200 mb-6 custom-scrollbar">

                <h4 class=" font-black text-slate-400 mb-3">Notices & Discrepancies</h4>

                <ul id="result-issues-list" class="space-y-2 text-xs font-bold text-slate-700">

                    </ul>

            </div>



            <div class="flex justify-center shrink-0">

                <button onclick="window.location.href='../reports/running_receivables/index.php'" class="px-10 py-3 bg-[#e11d48] hover:bg-[#be123c] text-white rounded-full font-black shadow-md transition-all duration-200">

                    View Reports

                </button>

            </div>

        </div>

    </div>



<script src="../assets/js/upload.js?v=<?php echo time(); ?>"></script>