<?php
$currentPage = 'user-mgt';
require_once __DIR__ . '/../../src/includes/init.php';

// KICK OUT UNAUTHORIZED USERS
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'ADMIN') {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>UNAUTHORIZED ACCESS</h1>");
}

$users = $auth->getAllUsers();
?>

<div class="p-8 h-full bg-slate-50 overflow-y-auto">
    
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">System User Management</h1>
            <p class="text-slate-500 mt-1">Add, restrict, and manage access levels for system personnel.</p>
        </div>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span class="font-medium"><?= $_SESSION['success_msg']; ?></span>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <span class="font-medium"><?= $_SESSION['error_msg']; ?></span>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <div class="xl:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 h-fit">
            <h2 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 mb-5 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#e11d48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                Create New Account
            </h2>
            
            <form action="/ML-MOTOR-LOAN-SYSTEM/public/actions/manage_user.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">First Name</label>
                        <input type="text" name="first_name" id="firstName" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] focus:border-transparent transition-all placeholder:text-slate-400" placeholder="Juan">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Last Name</label>
                        <input type="text" name="last_name" id="lastName" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] focus:border-transparent transition-all placeholder:text-slate-400" placeholder="Dela Cruz">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Username</label>
                    <input type="text" name="username" id="username" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] focus:border-transparent transition-all text-slate-700">
                    <p class="text-[10px] text-slate-400 mt-1">Auto-generated, but can be customized.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Password</label>
                    <input type="text" name="password" id="password" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] focus:border-transparent transition-all text-slate-700">
                    <p class="text-[10px] text-slate-400 mt-1">Default: First 4 letters of Last Name + Current Year.</p>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Role</label>
                        <select name="user_type" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] font-medium text-slate-700">
                            <option value="USER">User</option>
                            <option value="ADMIN">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                        <select name="status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] font-medium text-slate-700">
                            <option value="ACTIVE">Active</option>
                            <option value="RESTRICTED">Restricted</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full mt-6 bg-[#e11d48] text-white font-bold py-3 rounded-xl shadow-md shadow-rose-200 hover:bg-rose-700 transition-all uppercase text-xs tracking-widest flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Create Account
                </button>
            </form>
        </div>

        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Registered Personnel</h2>
                <span class="bg-slate-200 text-slate-600 py-1 px-3 rounded-full text-xs font-bold"><?= count($users) ?> Users</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                            <th class="p-4 font-bold">User Details</th>
                            <th class="p-4 font-bold text-center">Role & Status</th>
                            <th class="p-4 font-bold text-center">Last Login</th>
                            <th class="p-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-slate-800 text-sm">
                                    <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                </div>
                                <div class="text-xs text-slate-500 font-medium mt-0.5">@<?= htmlspecialchars($u['username']) ?></div>
                            </td>
                            <td class="p-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider <?= $u['user_type'] === 'ADMIN' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-blue-50 text-blue-600 border border-blue-100' ?>">
                                    <?= $u['user_type'] ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider ml-1 <?= $u['status'] === 'ACTIVE' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100' ?>">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                            <td class="p-4 text-center text-xs text-slate-500 font-medium">
                                <?= $u['last_login'] ? date('M d, Y - h:i A', strtotime($u['last_login'])) : '<span class="italic text-slate-400">Never Logged In</span>' ?>
                            </td>
                            <td class="p-4 text-right">
                                <form action="/ML-MOTOR-LOAN-SYSTEM/public/actions/manage_user.php" method="POST" class="inline-flex items-center justify-end gap-2 w-full">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    
                                    <select name="user_type" class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-slate-200 font-medium text-slate-600">
                                        <option value="USER" <?= $u['user_type'] === 'USER' ? 'selected' : '' ?>>User</option>
                                        <option value="ADMIN" <?= $u['user_type'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    
                                    <select name="status" class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-slate-200 font-medium <?= $u['status'] === 'RESTRICTED' ? 'text-red-600 font-bold' : 'text-slate-600' ?>">
                                        <option value="ACTIVE" <?= $u['status'] === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                                        <option value="RESTRICTED" <?= $u['status'] === 'RESTRICTED' ? 'selected' : '' ?>>Restrict</option>
                                    </select>
                                    
                                    <button type="submit" class="bg-slate-100 text-slate-600 p-1.5 rounded-lg hover:bg-[#e11d48] hover:text-white transition-colors border border-slate-200 hover:border-[#e11d48]" title="Save Changes">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const firstName = document.getElementById('firstName');
    const lastName = document.getElementById('lastName');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const currentYear = new Date().getFullYear();

    // Track if user manually typed in these fields
    let userEditedUsername = false;
    let userEditedPassword = false;

    username.addEventListener('input', () => userEditedUsername = true);
    password.addEventListener('input', () => userEditedPassword = true);

    function autoGenerateFields() {
        const fNameVal = firstName.value.trim();
        const lNameVal = lastName.value.trim();

        // 1. Auto-generate Username (lowercase: firstname.lastname)
        if (!userEditedUsername) {
            let fNameClean = fNameVal.toLowerCase().replace(/[^a-z0-9]/g, '');
            let lNameClean = lNameVal.toLowerCase().replace(/[^a-z0-9]/g, '');

            if (fNameClean && lNameClean) {
                username.value = fNameClean + '.' + lNameClean;
            } else {
                username.value = fNameClean + lNameClean;
            }
        }

        // 2. Auto-generate Password (First 4 of Last Name + Current Year)
        if (!userEditedPassword) {
            // Keep original casing for password prefix
            let passPrefix = lNameVal.substring(0, 4);
            
            if (passPrefix.length > 0) {
                password.value = passPrefix + currentYear;
            } else {
                password.value = '';
            }
        }
    }

    firstName.addEventListener('input', autoGenerateFields);
    lastName.addEventListener('input', autoGenerateFields);
});
</script>