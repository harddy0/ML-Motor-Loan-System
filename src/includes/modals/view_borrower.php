<div id="viewBorrowerModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl max-h-[95vh] flex flex-col rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden text-sm sm:text-base ">

        <!-- Header -->
        <div class="relative shrink-0">
            <div class="px-8 pt-2 pb-2 flex justify-between items-center">
                <div>
                    <p class="text-[13px] font-bold text-[#ce1126] uppercase tracking-[0.2em] mb-1">Account Record</p>
                </div>
                <button onclick="closeModal('viewBorrowerModal')"
                    class="w-9 h-9 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="h-px bg-slate-100"></div>
        </div>

        <!-- Body -->
        <div class="p-4 overflow-y-auto flex flex-col gap-8">

            <!-- Personal Information -->
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-1 h-4 rounded-full bg-[#ce1126] shrink-0"></span>
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-[0.18em]">Personal Information</h3>
                </div>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left">
                        <thead class="bg-[#ce1126] border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Employee ID</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">First Name</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Last Name</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Contact Number</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Region</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td id="m-id" class="px-6 py-1 text-[13px] text-slate-900 text-center"></td>
                                <td id="m-fname" class="px-6 py-1 text-[13px] text-slate-900 uppercase text-center"></td>
                                <td id="m-lname" class="px-6 py-1 text-[13px] text-slate-900 uppercase text-center"></td>
                                <td id="m-contact" class="px-6 py-1 text-[13px] text-slate-900 text-center"></td>
                                <td id="m-region" class="px-6 py-1 text-[13px] text-slate-900 lowercase first-letter:uppercase text-center"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Loan Information -->
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-1 h-4 rounded-full bg-[#ce1126] shrink-0"></span>
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-[0.18em]">Loan Information</h3>
                </div>
                <div class="overflow-x-auto border border-slate-200 rounded-xl mt-6">
                    <table class="w-full text-left text-slate-700">
                        <thead class="bg-[#ce1126] border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">System Loan Number</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Date Released</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Maturity Date</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Term(s)</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Loan Amount</th>
                                <th class="px-6 py-1 text-xs text-white font-bold tracking-widest text-center">Semi-Monthly Amortization</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td id="m-pn-no" class="px-6 py-1 text-[13px] text-center text-slate-900 "></td>
                                <td id="m-date" class="px-6 py-1 text-[13px] text-center text-slate-900 "></td>
                                <td id="m-pn-mat" class="px-6 py-1 text-[13px] text-center text-slate-900 "></td>
                                <td id="m-terms" class="px-6 py-1 text-[13px] text-center text-slate-900 "></td>
                                <td id="m-amount" class="px-6 py-1 text-[13px] text-slate-900 font-mono"></td>
                                <td id="m-deduct" class="px-6 py-1 text-[13px] text-slate-900 font-mono"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- KPTN Document -->
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-1 h-4 rounded-full bg-slate-300 shrink-0"></span>
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-[0.18em]">KPTN Form</h3>
                </div>

                <!-- STATE A: No deposit required -->
                <div id="kptn-no-deposit-state" class="hidden">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-6 py-5 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-lg bg-slate-200 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-400 tracking-wide">Not required</p>
                        </div>
                    </div>
                </div>

                <!-- STATE B: Deposit required — image/pdf viewer -->
                <div id="kptn-doc-state" class="hidden">

                    <!-- Image Viewer Card -->
                    <div id="kptn-viewer-card" class="w-full rounded-xl overflow-hidden border border-slate-200 shadow-sm">
                        <!-- Toolbar -->
                        <div id="kptn-toolbar" class="hidden bg-[#0f1117] px-4 py-2.5 items-center gap-2 justify-between border-b border-white/5">
                            <div class="flex items-center gap-1">
                                <button onclick="kptnZoom(-0.2)" title="Zoom Out" class="w-7 h-7 flex items-center justify-center rounded-md bg-white/10 hover:bg-white/20 text-white/70 hover:text-white transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/></svg>
                                </button>
                                <span id="kptn-zoom-label" class="text-white/50 text-sm font-mono w-9 text-center select-none">100%</span>
                                <button onclick="kptnZoom(0.2)" title="Zoom In" class="w-7 h-7 flex items-center justify-center rounded-md bg-white/10 hover:bg-white/20 text-white/70 hover:text-white transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                </button>
                                <div class="w-px h-4 bg-white/10 mx-1"></div>
                                <button onclick="kptnResetZoom()" title="Reset" class="w-7 h-7 flex items-center justify-center rounded-md bg-white/10 hover:bg-white/20 text-white/70 hover:text-white transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                </button>
                                <button onclick="kptnRotate()" title="Rotate 90°" class="w-7 h-7 flex items-center justify-center rounded-md bg-white/10 hover:bg-white/20 text-white/70 hover:text-white transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span id="kptn-file-label" class="text-white/25 text-xs sm:text-sm uppercase tracking-widest font-mono hidden sm:block mr-1"></span>
                                <a id="kptn-download-btn" href="#" download title="Download"
                                    class="w-7 h-7 flex items-center justify-center rounded-md bg-[#ce1126]/70 hover:bg-[#ce1126] text-white transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </a>
                                <button onclick="kptnFullscreen()" title="Fullscreen" class="w-7 h-7 flex items-center justify-center rounded-md bg-white/10 hover:bg-white/20 text-white/70 hover:text-white transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Stage -->
                        <div id="kptn-stage" class="relative bg-[#0c0e13] flex items-center justify-center overflow-hidden select-none"
                            style="min-height:380px;cursor:grab;"
                            onmousedown="kptnDragStart(event)"
                            onmousemove="kptnDragMove(event)"
                            onmouseup="kptnDragEnd()"
                            onmouseleave="kptnDragEnd()">
                            <div class="absolute inset-0 opacity-[0.03]" style="background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:28px 28px;"></div>

                            <!-- Skeleton loader -->
                            <div id="kptn-skeleton" class="absolute inset-0 flex items-center justify-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-14 h-14 rounded-xl bg-white/5 flex items-center justify-center">
                                        <svg class="w-7 h-7 text-white/15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="h-2 w-28 bg-white/8 rounded-full animate-pulse"></div>
                                        <div class="h-1.5 w-16 bg-white/5 rounded-full animate-pulse"></div>
                                    </div>
                                </div>
                            </div>

                            <img id="kptn-img" class="hidden relative" style="max-width:none;transform-origin:center center;" draggable="false" alt="KPTN Receipt"/>

                            <!-- Empty state: has deposit requirement but no doc uploaded yet -->
                            <div id="kptn-empty" class="hidden absolute inset-0 flex flex-col items-center justify-center gap-3">
                                <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/8 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white/15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="text-center">
                                    <p class="text-white/30 text-sm font-semibold tracking-widest uppercase">No Receipt Attached</p>
                                    <p class="text-white/15 text-sm mt-1">No KPTN deposit document has been uploaded.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Stage Footer -->
                        <div id="kptn-footer" class="hidden bg-[#0f1117] border-t border-white/5 px-4 py-2 items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                            <span class="text-white/25 text-xs sm:text-sm font-mono uppercase tracking-widest">Secure Preview</span>
                            <span class="ml-auto text-white/20 text-xs sm:text-sm font-mono" id="kptn-size-label"></span>
                        </div>
                    </div>

                    <!-- PDF Viewer -->
                    <div id="kptn-pdf-container" class="hidden w-full rounded-xl overflow-hidden border border-slate-200 shadow-sm">
                        <iframe id="kptn-pdf" src="" class="w-full border-0" style="height:580px;"></iframe>
                    </div>

                </div><!-- /kptn-doc-state -->
            </div><!-- /KPTN Document -->

        </div><!-- /Body -->

        <!-- Footer Actions -->
        <div class="shrink-0 border-t border-slate-100 bg-slate-50/80 px-8 py-4 flex items-center justify-between">
            <?php if (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER'])): ?>
                <button type="button" id="btnOpenVoidModal" onclick="openVoidConfirmationModal()"
                    class="h-8 px-4 bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 hover:bg-red-50 rounded-full text-[12px] font-semibold tracking-wide shadow-sm flex items-center gap-2 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    Void Loan
                </button>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            <button onclick="closeModal('viewBorrowerModal')"
                class="h-8 px-6 bg-slate-900 text-white rounded-full text-[12px] font-semibold tracking-wide hover:bg-slate-700 transition-all active:scale-95 shadow-sm">
                Close
            </button>
        </div>

    </div>
</div>

<!-- Fullscreen Lightbox -->
<div id="kptn-lightbox" class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/96" onclick="kptnCloseLightbox()">
    <button onclick="kptnCloseLightbox()" class="absolute top-5 right-5 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors z-10">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <img id="kptn-lightbox-img" src="" alt="KPTN Receipt" class="max-w-[92vw] max-h-[92vh] object-contain rounded-xl shadow-2xl" onclick="event.stopPropagation()"/>
</div>

<style>
#kptn-img.loaded { animation: kptnFadeIn 0.3s ease forwards; }
@keyframes kptnFadeIn { from{opacity:0} to{opacity:1} }
#kptn-stage[data-dragging="true"] { cursor:grabbing !important; }
#kptn-toolbar.flex, #kptn-footer.flex { display:flex; }
</style>

<script>
(function(){
    let _scale=1,_rotate=0,_tx=0,_ty=0;
    let _dragging=false,_lastX=0,_lastY=0,_currentUrl='';

    const $=id=>document.getElementById(id);
    const img=()=>$('kptn-img');
    const stage=()=>$('kptn-stage');
    const toolbar=()=>$('kptn-toolbar');
    const footer=()=>$('kptn-footer');
    const skeleton=()=>$('kptn-skeleton');
    const emptyEl=()=>$('kptn-empty');

    function applyTransform(){
        const el=img(); if(!el)return;
        el.style.transition='none';
        el.style.transform=`translate(${_tx}px,${_ty}px) rotate(${_rotate}deg) scale(${_scale})`;
        const lbl=$('kptn-zoom-label');
        if(lbl) lbl.textContent=Math.round(_scale*100)+'%';
    }

    window.kptnZoom=d=>{_scale=Math.min(5,Math.max(0.2,_scale+d));applyTransform();};
    window.kptnResetZoom=()=>{_scale=1;_tx=0;_ty=0;applyTransform();};
    window.kptnRotate=()=>{_rotate=(_rotate+90)%360;applyTransform();};

    function attachWheel(){
        const st=$('kptn-stage');
        if(!st){setTimeout(attachWheel,100);return;}
        st.addEventListener('wheel',e=>{
            if(img().classList.contains('hidden'))return;
            e.preventDefault();e.stopPropagation();
            _scale=Math.min(5,Math.max(0.2,_scale+(e.deltaY<0?0.12:-0.12)));
            applyTransform();
        },{passive:false});
    }
    document.readyState==='loading'?document.addEventListener('DOMContentLoaded',attachWheel):attachWheel();

    window.kptnDragStart=e=>{if(e.button!==0)return;_dragging=true;_lastX=e.clientX;_lastY=e.clientY;stage().dataset.dragging='true';};
    window.kptnDragMove=e=>{if(!_dragging)return;_tx+=e.clientX-_lastX;_ty+=e.clientY-_lastY;_lastX=e.clientX;_lastY=e.clientY;applyTransform();};
    window.kptnDragEnd=()=>{_dragging=false;stage().dataset.dragging='false';};

    window.kptnFullscreen=()=>{
        if(!_currentUrl)return;
        $('kptn-lightbox-img').src=_currentUrl;
        $('kptn-lightbox').classList.remove('hidden');$('kptn-lightbox').classList.add('flex');
    };
    window.kptnCloseLightbox=()=>{
        $('kptn-lightbox').classList.add('hidden');$('kptn-lightbox').classList.remove('flex');
    };

    window.kptnLoadDocument=function(url,mimeType,fileName){
        _currentUrl=url;_scale=1;_rotate=0;_tx=0;_ty=0;
        const el=img(),sk=skeleton(),em=emptyEl(),tb=toolbar(),ft=footer();
        const imgCard=$('kptn-viewer-card'),pdfWrap=$('kptn-pdf-container'),pdfFrame=$('kptn-pdf');
        const dl=$('kptn-download-btn'),fl=$('kptn-file-label');

        imgCard.classList.add('hidden');pdfWrap.classList.add('hidden');pdfFrame.src='';

        if(!url){
            imgCard.classList.remove('hidden');
            sk.classList.add('hidden');em.classList.remove('hidden');
            tb.classList.remove('flex');tb.classList.add('hidden');
            ft.classList.remove('flex');ft.classList.add('hidden');
            el.classList.add('hidden');return;
        }
        if(dl)dl.href=url;
        if(fl&&fileName)fl.textContent=fileName;

        if(mimeType&&mimeType.includes('pdf')){
            pdfFrame.src=url;pdfWrap.classList.remove('hidden');
        } else {
            imgCard.classList.remove('hidden');
            el.classList.add('hidden');el.classList.remove('loaded');
            sk.classList.remove('hidden');em.classList.add('hidden');
            tb.classList.remove('flex');tb.classList.add('hidden');
            ft.classList.remove('flex');ft.classList.add('hidden');
            el.onload=function(){
                sk.classList.add('hidden');el.classList.remove('hidden');el.classList.add('loaded');
                tb.classList.remove('hidden');tb.classList.add('flex');
                ft.classList.remove('hidden');ft.classList.add('flex');
                applyTransform();
                const sz=$('kptn-size-label');
                if(sz)sz.textContent=el.naturalWidth+' × '+el.naturalHeight+'px';
            };
            el.onerror=()=>{sk.classList.add('hidden');em.classList.remove('hidden');};
            el.src=url;
        }
    };

    window.kptnShowEmpty=()=>{
        $('kptn-viewer-card').classList.remove('hidden');
        $('kptn-pdf-container').classList.add('hidden');$('kptn-pdf').src='';
        skeleton().classList.add('hidden');emptyEl().classList.remove('hidden');
        toolbar().classList.remove('flex');toolbar().classList.add('hidden');
        footer().classList.remove('flex');footer().classList.add('hidden');
        img().classList.add('hidden');
    };

    window.kptnSetTitle=name=>{
        const el=$('modal-borrower-title');
        if(el&&name)el.textContent=name.toUpperCase();
    };

    // Called by openViewModal — decides which KPTN state to show
    window.kptnHandleState=function(requiresKptn, filePath, mimeType, loanId){
        const noDepositEl = $('kptn-no-deposit-state');
        const docEl       = $('kptn-doc-state');

        if (!requiresKptn) {
            // Loan exempt from deposit — hide viewer entirely
            noDepositEl.classList.remove('hidden');
            docEl.classList.add('hidden');
        } else {
            // Deposit required — show viewer
            noDepositEl.classList.add('hidden');
            docEl.classList.remove('hidden');

            if (filePath && mimeType && loanId) {
                const serveUrl = BASE_URL + '/public/api/serve_document.php?loan_id=' + loanId;
                const fileName = filePath.split('/').pop() || 'kptn_receipt';
                window.kptnLoadDocument(serveUrl, mimeType, fileName);
            } else {
                window.kptnShowEmpty();
            }
        }
    };
})();
</script>