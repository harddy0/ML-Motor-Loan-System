<?php
$currentPage = 'borrower-mgt';
require_once __DIR__ . '/../../src/includes/init.php';

// KICK OUT UNAUTHORIZED USERS
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'ADMIN') {
    die("<h1 style='color:red; text-align:center; margin-top:50px; font-family:sans-serif;'>UNAUTHORIZED ACCESS</h1>");
}

// Fetch all borrowers using the existing LoanService method
$loanService = new \App\LoanService($pdo);
$borrowers = $loanService->getAllBorrowers();
?>

<div class="p-8 h-full bg-slate-50 overflow-y-auto">
    
    <div class="mb-8 border-l-4 border-orange-500 pl-4">
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Borrower Data Management</h1>
        <p class="text-slate-500 mt-1 font-medium">
            <span class="text-orange-600 font-bold">ADMIN ACTIONS:</span> Voiding a borrower here will flag their active loans, amortization ledgers, and payroll deductions as VOIDED for auditing purposes.
        </p>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm">
            <span class="block sm:inline font-bold"><?= htmlspecialchars($_SESSION['success_msg']); ?></span>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm">
            <span class="block sm:inline font-bold"><?= htmlspecialchars($_SESSION['error_msg']); ?></span>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800 text-white text-xs uppercase tracking-wider">
                        <th class="p-4 font-bold">Emp ID</th>
                        <th class="p-4 font-bold">Borrower Name</th>
                        <th class="p-4 font-bold">Region</th>
                        <th class="p-4 font-bold text-center">Active Loan Status</th>
                        <th class="p-4 font-bold text-right">Admin Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($borrowers)): ?>
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">No borrowers found in the system.</td></tr>
                    <?php else: ?>
                        <?php foreach ($borrowers as $b): ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="p-4 font-mono text-sm font-bold text-slate-600">
                                <?= htmlspecialchars($b['id']) ?>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-800 uppercase"><?= htmlspecialchars($b['name']) ?></div>
                                <div class="text-xs text-slate-500 font-mono">PN: <?= htmlspecialchars($b['pn_no'] ?? 'N/A') ?></div>
                            </td>
                            <td class="p-4 text-sm text-slate-600 font-medium uppercase">
                                <?= htmlspecialchars($b['region']) ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if($b['current_status'] === 'ONGOING'): ?>
                                    <span class="inline-block px-2 py-1 text-[10px] font-bold rounded-md bg-blue-100 text-blue-700 uppercase">Ongoing</span>
                                <?php elseif($b['current_status'] === 'FULLY PAID'): ?>
                                    <span class="inline-block px-2 py-1 text-[10px] font-bold rounded-md bg-green-100 text-green-700 uppercase">Fully Paid</span>
                                <?php elseif($b['current_status'] === 'VOIDED'): ?>
                                    <span class="inline-block px-2 py-1 text-[10px] font-bold rounded-md bg-orange-100 text-orange-700 uppercase">Voided</span>
                                <?php else: ?>
                                    <span class="inline-block px-2 py-1 text-[10px] font-bold rounded-md bg-slate-100 text-slate-700 uppercase"><?= htmlspecialchars($b['current_status'] ?? 'NO LOAN') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-right">
                                <?php if($b['current_status'] !== 'VOIDED'): ?>
                                <form action="/ML-MOTOR-LOAN-SYSTEM/public/actions/delete_borrower.php" method="POST" onsubmit="return confirmVoid(this, '<?= htmlspecialchars($b['name']) ?>');">
                                    <input type="hidden" name="action" value="void">
                                    <input type="hidden" name="employe_id" value="<?= htmlspecialchars($b['id']) ?>">
                                    <input type="hidden" name="borrower_name" value="<?= htmlspecialchars($b['name']) ?>">
                                    <input type="hidden" name="void_reason" class="void-reason-input" value="">
                                    
                                    <button type="submit" class="bg-orange-100 text-orange-600 border border-orange-200 px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wider hover:bg-orange-500 hover:text-white transition-all shadow-sm opacity-50 group-hover:opacity-100 flex items-center gap-2 ml-auto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        Void Data
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmVoid(form, borrowerName) {
    const reason = prompt(`WARNING!\n\nYou are about to VOID all active records for [ ${borrowerName} ].\nThis action is logged for auditing.\n\nPlease enter the reason for voiding:`);
    
    if (reason === null) {
        return false; // User cancelled
    }
    
    if (reason.trim() === "") {
        alert("Action Cancelled: A reason is strictly required to void records.");
        return false;
    }
    
    // Inject reason into hidden input
    form.querySelector('.void-reason-input').value = reason.trim();
    return true;
}
</script>