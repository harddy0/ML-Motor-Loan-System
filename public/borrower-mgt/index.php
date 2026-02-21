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
    
    <div class="mb-8 border-l-4 border-red-600 pl-4">
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Borrower Data Management</h1>
        <p class="text-slate-500 mt-1 font-medium">
            <span class="text-red-600 font-bold">DANGER ZONE:</span> Deleting a borrower here will permanently wipe their profile, active loans, amortization ledgers, and payroll deductions. <span class="underline">This cannot be undone.</span>
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
                        <th class="p-4 font-bold text-right">Destructive Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($borrowers)): ?>
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">No borrowers found in the system.</td></tr>
                    <?php else: ?>
                        <?php foreach ($borrowers as $b): ?>
                        <tr class="hover:bg-red-50 transition-colors group">
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
                                <?php else: ?>
                                    <span class="inline-block px-2 py-1 text-[10px] font-bold rounded-md bg-slate-100 text-slate-700 uppercase"><?= htmlspecialchars($b['current_status'] ?? 'NO LOAN') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-right">
                                <form action="/ML-MOTOR-LOAN-SYSTEM/public/actions/delete_borrower.php" method="POST" onsubmit="return confirmWipe('<?= htmlspecialchars($b['name']) ?>');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="employe_id" value="<?= htmlspecialchars($b['id']) ?>">
                                    <input type="hidden" name="borrower_name" value="<?= htmlspecialchars($b['name']) ?>">
                                    
                                    <button type="submit" class="bg-red-100 text-red-600 border border-red-200 px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wider hover:bg-red-600 hover:text-white transition-all shadow-sm opacity-50 group-hover:opacity-100 flex items-center gap-2 ml-auto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Wipe Data
                                    </button>
                                </form>
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
function confirmWipe(borrowerName) {
    const msg = `WARNING! \n\nYou are about to PERMANENTLY DELETE [ ${borrowerName} ] and ALL associated loans, amortization schedules, and payment histories.\n\nThis cannot be undone. Are you absolutely sure?`;
    return confirm(msg);
}
</script>