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
    
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">System User Management</h1>
        <p class="text-slate-500 mt-1">Add, restrict, and manage access levels for system personnel.</p>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            <span class="block sm:inline font-semibold"><?= $_SESSION['success_msg']; ?></span>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <span class="block sm:inline font-semibold"><?= $_SESSION['error_msg']; ?></span>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-slate-200 p-6 h-fit">
            <h2 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 mb-5">Create New Account</h2>
            
            <form action="/ML-MOTOR-LOAN-SYSTEM/public/actions/manage_user.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Full Name</label>
                    <input type="text" name="full_name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48] focus:border-transparent transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Role</label>
                        <select name="user_type" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48]">
                            <option value="USER">Encoder (USER)</option>
                            <option value="ADMIN">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Status</label>
                        <select name="status" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e11d48]">
                            <option value="ACTIVE">Active</option>
                            <option value="RESTRICTED">Restricted</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full mt-4 bg-[#e11d48] text-white font-bold py-3 rounded-lg shadow-md hover:bg-rose-700 transition-colors uppercase text-sm tracking-widest">
                    Create Account
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                            <th class="p-4 font-bold border-b border-slate-200">User Details</th>
                            <th class="p-4 font-bold border-b border-slate-200 text-center">Role & Status</th>
                            <th class="p-4 font-bold border-b border-slate-200 text-center">Last Login</th>
                            <th class="p-4 font-bold border-b border-slate-200 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($u['full_name']) ?></div>
                                <div class="text-xs text-slate-500">@<?= htmlspecialchars($u['username']) ?></div>
                            </td>
                            <td class="p-4 text-center">
                                <span class="inline-block px-2 py-1 text-[10px] font-bold rounded-md <?= $u['user_type'] === 'ADMIN' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                                    <?= $u['user_type'] ?>
                                </span>
                                <span class="inline-block px-2 py-1 text-[10px] font-bold rounded-md ml-1 <?= $u['status'] === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                            <td class="p-4 text-center text-xs text-slate-500 font-medium">
                                <?= $u['last_login'] ? date('M d, Y - h:i A', strtotime($u['last_login'])) : 'Never Logged In' ?>
                            </td>
                            <td class="p-4 text-right">
                                <form action="/ML-MOTOR-LOAN-SYSTEM/public/actions/manage_user.php" method="POST" class="inline-flex items-center gap-2">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    
                                    <select name="user_type" class="text-xs border border-slate-200 rounded px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                                        <option value="USER" <?= $u['user_type'] === 'USER' ? 'selected' : '' ?>>Encoder</option>
                                        <option value="ADMIN" <?= $u['user_type'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    
                                    <select name="status" class="text-xs border border-slate-200 rounded px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 <?= $u['status'] === 'RESTRICTED' ? 'text-red-600 font-bold' : '' ?>">
                                        <option value="ACTIVE" <?= $u['status'] === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                                        <option value="RESTRICTED" <?= $u['status'] === 'RESTRICTED' ? 'selected' : '' ?>>Restrict</option>
                                    </select>
                                    
                                    <button type="submit" class="bg-slate-800 text-white p-1.5 rounded hover:bg-slate-700 transition-colors" title="Save Changes">
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