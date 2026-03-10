<?php
$currentPage = 'user-mgt';
require_once __DIR__ . '/../../src/includes/init.php';

// KICK OUT UNAUTHORIZED USERS
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'ADMIN') {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>UNAUTHORIZED ACCESS</h1>");
}

$users = $auth->getAllUsers();
?>

<div class="-mt-4 h-full overflow-y-auto">
    
    <div class="mb-1 flex justify-between items-end">
        <div>
            <h1 class="text-2xl text-slate-800 tracking-tight">System User Management</h1>
            <p class="text-slate-500 font-mono text-sm mt-1">Add, restrict, and manage access levels for system personnel.</p>
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

    <div class="grid grid-cols-1 gap-8 -mt-10">
        <div class="p-1 flex justify-end items-center">
                <div name="user-count-and-button" class="flex items-center gap-3">
                    <span class="text-slate-600 text-[14px] font-bold">Count: <?= count($users) ?> </span>
                    <button id="openCreateBtn" class="bg-[#ce1126] px-5 py-2 text-white text-[13px] rounded-md">Create New</button>
                </div>
            </div>

        <div class="bg-white rounded border border-slate-300 shadow-sm overflow-hidden -mt-6">
                <table class="w-full text-left border-collapse table-fixed">
                    <thead>
                        <tr class="bg-[#ce1126] border-b border-slate-300">
                            <th class="w-20 px-3 py-1 text-[14px] font-bold whitespace-nowrap text-white tracking-wider border-r border-slate-200 text-center">Employee ID</th>
                            <th class="w-32 px-3 py-1 text-[14px] font-bold whitespace-nowrap text-white tracking-wider border-r border-slate-200 text-center">Full Name</th>
                            <th class="w-20 px-3 py-1 text-[14px] font-bold whitespace-nowrap text-white tracking-wider border-r border-slate-200 text-center">User Name</th>
                            <th class="w-24 px-3 py-1 text-[14px] font-bold whitespace-nowrap text-white tracking-wider border-r border-slate-200 text-center">Role & Status</th>
                            <th class="w-24 px-3 py-1 text-[14px] font-bold whitespace-nowrap text-white tracking-wider border-r border-slate-200 text-center">Last Login</th>
                            <th class="w-44 px-3 py-1 text-[14px] font-bold whitespace-nowrap text-white tracking-wider border-r border-slate-200 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-1 py-0 whitespace-nowrap">
                                <div class=" text-[14px] text-slate-500">
                                    <?= htmlspecialchars($u['employe_id']) ?> 
                                </div>
                            </td>
                            <td class="px-1 py-0 whitespace-nowrap">
                                <div class="uppercase text-slate-800 text-[13px]"><?= htmlspecialchars($u['first_name'] . ' ' . (!empty($u['middle_name']) ? $u['middle_name'] . ' ' : '') . $u['last_name']) ?></div>
                            </td>
                            <td class="px-1 py-0 whitespace-nowrap">
                                <div class=" text-[13px] text-slate-500 ">
                                    @<span class="uppercase text-[13px] "><?= htmlspecialchars($u['username']) ?></span>
                                </div>
                            </td>
                            <td class="px-1 py-1 text-center">
                                <?php
                                    $badgeClasses = match($u['user_type']) {
                                        'ADMIN'     => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                                        'REVIEWER'  => 'bg-purple-50 text-purple-600 border-purple-100',
                                        default     => 'bg-blue-50 text-blue-600 border-blue-100',
                                    };
                                ?>
                                <span class="inline-flex items-center px-2.5 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider border <?= $badgeClasses ?>">
                                    <?= $u['user_type'] ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider ml-1 border <?= $u['status'] === 'ACTIVE' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100' ?>">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                            <td class="px-1 py-0 text-center text-xs text-slate-500">
                                <?= $u['last_login'] ? date('M d, Y - h:i A', strtotime($u['last_login'])) : '<span class="text-sm text-slate-400">Never Logged In</span>' ?>
                            </td>
                            <td class="px-1 py-0 text-center">
                                <div class="flex items-center justify-center gap-2 w-auto mx-auto">
                                    <form action="<?= BASE_URL ?>/public/actions/manage_user.php" method="POST" class="flex items-center gap-1">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="employe_id" value="<?= $u['employe_id'] ?>">
                                        
                                        <select name="user_type" class="text-[11px] border border-slate-200 rounded-md px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-medium text-slate-600">
                                            <option value="USER" <?= $u['user_type'] === 'USER' ? 'selected' : '' ?>>User</option>
                                            <option value="ADMIN" <?= $u['user_type'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                                            <option value="REVIEWER" <?= $u['user_type'] === 'REVIEWER' ? 'selected' : '' ?>>Reviewer</option>
                                        </select>
                                        
                                        <select name="status" class="text-[11px] border border-slate-200 rounded-md px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-medium <?= $u['status'] === 'RESTRICTED' ? 'text-red-600' : 'text-slate-600' ?>">
                                            <option value="ACTIVE" <?= $u['status'] === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                                            <option value="RESTRICTED" <?= $u['status'] === 'RESTRICTED' ? 'selected' : '' ?>>Restrict</option>
                                        </select>
                                        
                                        <button type="submit" class="bg-slate-100 text-slate-600 p-1.5 rounded-md hover:bg-emerald-600 hover:text-white transition-colors border border-slate-200" title="Save Changes">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </form>

                                    <form action="<?= BASE_URL ?>/public/actions/manage_user.php" method="POST" class="inline" onsubmit="return confirm('Reset password?');">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="employe_id" value="<?= $u['employe_id'] ?>">
                                        <button type="submit" class="bg-amber-50 text-amber-600 p-1.5 rounded-md hover:bg-amber-500 hover:text-white transition-colors border border-amber-200" title="Reset Password">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div id="createModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-2 flex justify-between items-center shrink-0">
            <h2 class="text-lg font-bold text-slate-800">
                Create New User Account
            </h2>
            <button id="closeCreateBtn" class="text-slate-400 hover:text-red-500 transition-colors p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="px-10 py-5 overflow-y-auto">
            <form id="createUserForm" action="<?= BASE_URL ?>/public/actions/manage_user.php" method="POST" class="space-y-2">
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Employee ID</label>
                    <input type="number" name="employe_id" id="employeId" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ce1126] focus:border-transparent transition-all text-slate-700" placeholder="e.g. 1001">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">First Name</label>
                        <input type="text" name="first_name" required oninput="this.value = this.value.toUpperCase()" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ce1126] focus:border-transparent transition-all placeholder:text-slate-400 uppercase" placeholder="JUAN">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Middle Name</label>
                        <input type="text" name="middle_name" oninput="this.value = this.value.toUpperCase()" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ce1126] focus:border-transparent transition-all placeholder:text-slate-400 uppercase" placeholder="(OPTIONAL)">
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Last Name</label>
                    <input type="text" name="last_name" id="lastName" required oninput="this.value = this.value.toUpperCase(); autoGenerateUsername();" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ce1126] focus:border-transparent transition-all placeholder:text-slate-400 uppercase" placeholder="DELA CRUZ">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Username</label>
                    <input type="text" name="username" id="username" required readonly class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-lg text-slate-500 uppercase cursor-not-allowed font-medium">
                    <p class="text-[10px] text-slate-400 mt-1">Auto-generated: First 4 chars of Last Name + Employee ID.</p>
                </div>

                <!-- Default Password section — light red theme -->
                <div class="bg-red-50 border border-red-100 rounded-lg p-3">
                    <label class="block text-xs font-bold text-red-700 uppercase mb-1">Default Password</label>
                    <code class="text-sm font-mono font-bold text-red-800">Mlinc1234@</code>
                    <p class="text-[10px] text-red-400 mt-1">User will be forced to change this on first login.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Role</label>
                        <select name="user_type" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ce1126] font-medium text-slate-700">
                            <option value="USER">User</option>
                            <option value="ADMIN">Admin</option>
                            <option value="REVIEWER">Reviewer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Status</label>
                        <select name="status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ce1126] font-medium text-slate-700">
                            <option value="ACTIVE">Active</option>
                            <option value="RESTRICTED">Restricted</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 sticky bottom-0 bg-white">
                    <button id="createSubmitBtn" type="submit" disabled class="w-full bg-[#ce1126] text-white font-bold py-3.5 rounded-lg shadow-lg shadow-red-100 hover:bg-red-700 transition-all uppercase text-xs tracking-widest flex justify-center items-center gap-2 opacity-50 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function autoGenerateUsername() {
    const employeIdInput = document.getElementById('employeId');
    const lastNameInput = document.getElementById('lastName');
    const usernameInput = document.getElementById('username');

    if (!lastNameInput || !employeIdInput || !usernameInput) return;

    // CLEAN, UPPERCASE, AND EXTRACT FIRST 4 CHARS
    const lNameVal = lastNameInput.value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    const lNamePrefix = lNameVal.substring(0, 4);
    
    // Get Employee ID
    const idVal = employeIdInput.value.trim();

    // Combine to generate capitalized username
    if (lNamePrefix || idVal) {
        usernameInput.value = lNamePrefix + idVal;
    } else {
        usernameInput.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const employeIdInput = document.getElementById('employeId');
    if (employeIdInput) employeIdInput.addEventListener('input', autoGenerateUsername);

    const openBtn = document.getElementById('openCreateBtn');
    const closeBtn = document.getElementById('closeCreateBtn');
    const createModal = document.getElementById('createModal');
    const createForm = document.getElementById('createUserForm');

    if (openBtn) {
        openBtn.addEventListener('click', () => {
            if (createForm) createForm.reset();
            const usernameInput = document.getElementById('username');
            if (usernameInput) usernameInput.value = '';
            createModal.classList.remove('hidden');
            createModal.classList.add('flex');
            updateSubmitState();
            const eid = document.getElementById('employeId');
            if (eid) eid.focus();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            createModal.classList.remove('flex');
            createModal.classList.add('hidden');
        });
    }

    if (createModal) {
        createModal.addEventListener('click', (e) => {
            if (e.target === createModal) {
                createModal.classList.remove('flex');
                createModal.classList.add('hidden');
            }
        });
    }
    
    // Enable submit only when form is valid
    const submitBtn = document.getElementById('createSubmitBtn');
    function updateSubmitState() {
        if (!createForm || !submitBtn) return;
        const valid = createForm.checkValidity();
        if (valid) {
            submitBtn.removeAttribute('disabled');
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    if (createForm) {
        createForm.addEventListener('input', updateSubmitState);
        createForm.addEventListener('change', updateSubmitState);
        updateSubmitState();
    }
});
</script>