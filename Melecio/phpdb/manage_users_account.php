<?php
session_start();
require 'db_connection.php';

// Single authentication check - protect admin page
if (!isset($_SESSION['id_no']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$message = '';
$messageType = '';

// Update current logged in user last active
$stmt = $conn->prepare("UPDATE registeredacc SET last_active = NOW() WHERE id_no=?");
$stmt->bind_param("s", $_SESSION['id_no']);
$stmt->execute();
$stmt->close();

// Block/Unblock account (ONLY USERS)
if (isset($_POST['block_account'])) {
    $id = $_POST['id'];
    $action = $_POST['action']; // 'block' or 'unblock'
    
    if ($action === 'block') {
        $stmt = $conn->prepare("UPDATE registeredacc SET status='blocked' WHERE id_no=? AND role='user' AND approval_status='approved'");
    } else {
        $stmt = $conn->prepare("UPDATE registeredacc SET status='active' WHERE id_no=? AND role='user' AND approval_status='approved'");
    }
    
    $stmt->bind_param("s", $id);
    
    if ($stmt->execute()) {
        $message = "User account " . ($action === 'block' ? 'blocked' : 'unblocked') . " successfully";
        $messageType = "success";
    } else {
        $message = "Failed to update account status";
        $messageType = "error";
    }
    $stmt->close();
}

// Search Function - ONLY SHOW APPROVED USERS
$search = $_GET['search'] ?? '';
$search = trim($search);

// Base query to include status - ONLY APPROVED USERS
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT id_no, username, email, last_active, status 
                            FROM registeredacc 
                            WHERE role='user'
                            AND approval_status = 'approved'
                            AND id_no != ?
                            AND (id_no LIKE ? OR username LIKE ? OR email LIKE ?)
                            ORDER BY username ASC");
    $likeSearch = "%" . $search . "%";
    $stmt->bind_param("ssss", $_SESSION['id_no'], $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT id_no, username, email, last_active, status 
                            FROM registeredacc 
                            WHERE role='user'
                            AND approval_status = 'approved'
                            AND id_no != ?
                            ORDER BY username ASC");
    $stmt->bind_param("s", $_SESSION['id_no']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}

// Get total user count (only approved users)
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM registeredacc WHERE role='user' AND approval_status='approved'")->fetch_assoc()['count'];
$activeToday = $conn->query("SELECT COUNT(*) as count FROM registeredacc WHERE role='user' AND approval_status='approved' AND last_active >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetch_assoc()['count'];
$blockedUsers = $conn->query("SELECT COUNT(*) as count FROM registeredacc WHERE role='user' AND approval_status='approved' AND status='blocked'")->fetch_assoc()['count'];

// Active Checker (active if within last 5 minutes)
function getUserStatus($last_active) {
    if (!$last_active) {
        return ["Inactive", "Never"];
    }
    
    $lastActiveTime = strtotime($last_active);
    $now = time();
    $diff = $now - $lastActiveTime;
    
    if ($diff <= 300) { // 5 minutes
        return ["Active", $last_active];
    } else {
        return ["Inactive", $last_active];
    }
}

// Get single user data for modal (only approved users)
if (isset($_GET['get_user'])) {
    $id = $_GET['get_user'];
    $stmt = $conn->prepare("SELECT id_no, username, email, role, last_active, status,
                            f_name as first_name, m_initial as middle_initial, l_name as last_name, 
                            extension as extension_name, birthday, age, sex,
                            purok as purok_street, barangay, city as municipality_city, 
                            province, country, zipcode as zip_code
                            FROM registeredacc WHERE id_no=? AND role='user' AND approval_status='approved'");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($userData) {
        // Calculate age if not stored but birthday exists
        if (empty($userData['age']) && !empty($userData['birthday'])) {
            $birthday = new DateTime($userData['birthday']);
            $today = new DateTime();
            $userData['age'] = $birthday->diff($today)->y;
        }
        
        // Format full name
        $fullName = trim($userData['first_name'] . ' ' . $userData['middle_initial'] . ' ' . $userData['last_name'] . ' ' . $userData['extension_name']);
        $userData['full_name'] = preg_replace('/\s+/', ' ', $fullName);
        
        echo json_encode(["success" => true, "profile" => $userData]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MAT - User Management</title>
    <link rel="stylesheet" href="../css/admin_home.css">
    <link rel="stylesheet" href="../css/admin_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
</head>
<body>

<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-notification"></div>

<!-- View User Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeViewModal()">&times;</span>
        
        <div class="modal-header">
            <h2><i class="fas fa-user-circle"></i> User Profile</h2>
            <p>Complete user information</p>
        </div>
        
        <div id="viewUserInfo">
            <div class="loading-spinner" id="viewLoading">
                <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>
            <div id="viewContent" style="display: none;"></div>
        </div>
        
        <div class="modal-actions">
            <button class="btn-modal btn-cancel" onclick="closeViewModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- Edit User Modal (Updated with Password Change) -->
<div id="editModal" class="modal">
    <div class="modal-content" style="max-width: 1000px;">
        <span class="close-modal" onclick="closeEditModal()">&times;</span>
        
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="fas fa-user-edit"></i>
            </div>
            <h2>Edit User</h2>
            <p>Update user information and password</p>
        </div>
        
        <div id="editUserInfo">
            <div class="loading-spinner" id="editLoading">
                <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>
            <div id="editContent" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- Block/Unblock Confirmation Modal -->
<div id="blockModal" class="modal block-modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeBlockModal()">&times;</span>
        
        <div class="block-icon" id="blockIcon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h2 id="blockModalTitle">Confirm Action</h2>
        <p id="blockModalMessage">Are you sure you want to <span id="blockActionText"></span> the account for <strong id="blockUsername"></strong>?</p>
        <p class="warning-text" id="blockWarning">
            <i class="fas fa-exclamation-circle"></i>
            This action will affect the user's access to the system.
        </p>
        
        <form method="POST" id="blockForm">
            <input type="hidden" name="id" id="blockId">
            <input type="hidden" name="action" id="blockAction">
            <div class="modal-actions">
                <button type="button" class="btn-modal btn-cancel" onclick="closeBlockModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" name="block_account" class="btn-modal" id="blockConfirmBtn">
                    <i class="fas fa-ban"></i> Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<div class="navbar">
    <div class="navbar-content">
        <p class="system-title">User Management</p>
        <div class="admin-badge">
            <span class="role-admin"><i class="fas fa-shield-alt"></i> Administrator</span>
         </div>
    </div>
</div>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-user-shield"></i> Welcome, <?= htmlspecialchars($username) ?>
        </div>       
         <h3><i class="fas fa-cog"></i> Management</h3>
            <a href="admin_home.php" ><i class="fas fa-home"></i> Overview</a>
            <a href="reports.php"><i class="fas fa-chart-pie"></i> Analytics/Reports</a>
            <a href="manage_users_account.php" class="active"><i class="fas fa-users"></i> Manage Accounts</a>
            <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
            <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
            <a href="admin_system_logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="admin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
        <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="dashboard-main">
        <div class="user-container">
            <h1><i class="fas fa-users-cog"></i> Users Management</h1>

            <!-- Stats Cards -->
            <div class="account-stats" style="margin-bottom: 25px;">
                <div class="stat-card-small">
                    <div class="stat-icon-small">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content-small">
                        <h4>Total Users</h4>
                        <div class="stat-number"><?= number_format($totalUsers) ?></div>
                    </div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-icon-small">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content-small">
                        <h4>Active Now</h4>
                        <div class="stat-number"><?= number_format($activeToday) ?></div>
                    </div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-icon-small">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="stat-content-small">
                        <h4>Blocked Users</h4>
                        <div class="stat-number"><?= number_format($blockedUsers) ?></div>
                    </div>
                </div>
            </div>

            <!-- SEARCH BAR -->
            <div class="search-section" style="margin-bottom: 25px;">
                <form method="GET" class="search-form">
                    <div class="search-input-group">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               name="search" 
                               class="search-input" 
                               placeholder="Search by ID, username, or email..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="manage_users_account.php" class="btn-reset">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </form>
                <?php if (!empty($search)): ?>
                    <div class="search-info">
                        <i class="fas fa-info-circle"></i>
                        Showing results for "<?= htmlspecialchars($search) ?>"
                    </div>
                <?php endif; ?>
            </div>

            <!-- Message Alert -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; <?= $messageType === 'success' ? 'background: rgba(0,200,83,0.15); border: 1px solid rgba(0,200,83,0.3); color: var(--success);' : 'background: rgba(255,61,0,0.15); border: 1px solid rgba(255,61,0,0.3); color: var(--danger);' ?>">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="user-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i> ID No</th>
                            <th><i class="fas fa-user"></i> Username</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-circle"></i> Status</th>
                            <th><i class="fas fa-clock"></i> Last Active</th>
                            <th><i class="fas fa-ban"></i> Account Status</th>
                            <th><i class="fas fa-cog"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php [$status, $lastActive] = getUserStatus($row['last_active']); 
                                $isBlocked = ($row['status'] ?? 'active') === 'blocked';
                                ?>
                                <tr <?= $isBlocked ? 'style="opacity: 0.7;"' : '' ?>>
                                    <td><span class="id-badge"><?= htmlspecialchars($row['id_no']) ?></span></td>
                                    <td><i class="fas fa-user-circle" style="color: var(--primary-light); margin-right: 5px;"></i><?= htmlspecialchars($row['username']) ?></td>
                                    <td><i class="fas fa-envelope" style="color: var(--text-muted); margin-right: 5px;"></i><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <?php if ($status == "Active"): ?>
                                            <span class="status-badge active">
                                                <i class="fas fa-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge inactive">
                                                <i class="fas fa-circle"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($lastActive == "Never"): ?>
                                            <span class="text-muted"><i class="far fa-clock"></i> Never</span>
                                        <?php else: ?>
                                            <span class="last-active-time">
                                                <i class="far fa-calendar-alt"></i> 
                                                <?= htmlspecialchars(date("M d, Y h:i A", strtotime($lastActive))) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isBlocked): ?>
                                            <span class="status-badge blocked">
                                                <i class="fas fa-ban"></i> Blocked
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge active">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-action btn-view" onclick="viewUser('<?= $row['id_no'] ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button type="button" class="btn-action btn-edit" onclick="editUser('<?= $row['id_no'] ?>')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if ($isBlocked): ?>
                                                <button type="button" class="btn-action btn-unblock-small" onclick="showBlockModal('<?= $row['id_no'] ?>', '<?= htmlspecialchars($row['username']) ?>', 'unblock')">
                                                    <i class="fas fa-check-circle"></i> Unblock
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-action btn-block-small" onclick="showBlockModal('<?= $row['id_no'] ?>', '<?= htmlspecialchars($row['username']) ?>', 'block')">
                                                    <i class="fas fa-ban"></i> Block
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 60px;">
                                    <i class="fas fa-users-slash" style="font-size: 48px; opacity: 0.5;"></i>
                                    <p style="margin-top: 15px; color: var(--text-muted);">No users found</p>
                                    <?php if (!empty($search)): ?>
                                        <small style="color: var(--text-muted);">Try different search terms or <a href="manage_users_account.php" style="color: var(--primary-light);">clear search</a></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- User Summary -->
            <div class="account-summary" style="margin-top: 20px;">
                <span><i class="fas fa-database"></i> Total: <?= $result->num_rows ?> user(s) displayed</span>
                <?php if (!empty($search)): ?>
                    <a href="manage_users_account.php" class="clear-search">
                        <i class="fas fa-undo"></i> Clear search
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<div class="footer">
    <p><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script>
// Toast notification function
function showToast(message, type = 'success', title = null) {
    const toastContainer = document.getElementById('toastContainer');
    
    if (!title) {
        switch(type) {
            case 'success': title = 'Success!'; break;
            case 'error': title = 'Error!'; break;
            default: title = 'Notification';
        }
    }
    
    let icon = 'fa-check-circle';
    switch(type) {
        case 'success': icon = 'fa-check-circle'; break;
        case 'error': icon = 'fa-exclamation-circle'; break;
    }
    
    const toastId = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.id = toastId;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icon}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <div class="toast-close" onclick="removeToast('${toastId}')">
            <i class="fas fa-times"></i>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const timeoutId = setTimeout(() => {
        removeToast(toastId);
    }, 5000);
    
    toast.dataset.timeoutId = timeoutId;
}

function removeToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        if (toast.dataset.timeoutId) {
            clearTimeout(parseInt(toast.dataset.timeoutId));
        }
        toast.classList.add('removing');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }
}

// Auto-hide alert messages after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Toggle password visibility
function togglePasswordVisibility(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Password strength checker for edit modal
function checkEditPasswordStrength() {
    const password = document.getElementById('edit_new_password').value;
    const strengthText = document.getElementById('edit_pwStrength');
    
    if (!password) {
        strengthText.innerHTML = '';
        // Reset requirement indicators
        const reqLength = document.getElementById('edit_req-length');
        const reqUppercase = document.getElementById('edit_req-uppercase');
        const reqLowercase = document.getElementById('edit_req-lowercase');
        const reqNumber = document.getElementById('edit_req-number');
        
        if (reqLength) reqLength.innerHTML = '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> 8-20 characters';
        if (reqUppercase) reqUppercase.innerHTML = '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> At least one uppercase letter';
        if (reqLowercase) reqLowercase.innerHTML = '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> At least one lowercase letter';
        if (reqNumber) reqNumber.innerHTML = '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> At least one number';
        return;
    }
    
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const isValidLength = password.length >= 8 && password.length < 20;
    
    // Update requirement indicators
    const reqLength = document.getElementById('edit_req-length');
    const reqUppercase = document.getElementById('edit_req-uppercase');
    const reqLowercase = document.getElementById('edit_req-lowercase');
    const reqNumber = document.getElementById('edit_req-number');
    
    if (reqLength) {
        reqLength.innerHTML = isValidLength ? 
            '<i class="fas fa-check-circle" style="color: #2ecc71; margin-right: 5px;"></i> 8-20 characters ✓' : 
            '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> 8-20 characters';
    }
    
    if (reqUppercase) {
        reqUppercase.innerHTML = hasUpperCase ? 
            '<i class="fas fa-check-circle" style="color: #2ecc71; margin-right: 5px;"></i> At least one uppercase letter ✓' : 
            '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> At least one uppercase letter';
    }
    
    if (reqLowercase) {
        reqLowercase.innerHTML = hasLowerCase ? 
            '<i class="fas fa-check-circle" style="color: #2ecc71; margin-right: 5px;"></i> At least one lowercase letter ✓' : 
            '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> At least one lowercase letter';
    }
    
    if (reqNumber) {
        reqNumber.innerHTML = hasNumbers ? 
            '<i class="fas fa-check-circle" style="color: #2ecc71; margin-right: 5px;"></i> At least one number ✓' : 
            '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> At least one number';
    }
    
    // Overall strength message
    if (password.length > 0) {
        if (isValidLength && hasUpperCase && hasLowerCase && hasNumbers) {
            strengthText.innerHTML = '<span style="color: #2ecc71;"><i class="fas fa-check-circle"></i> Strong password</span>';
        } else if (password.length >= 8) {
            strengthText.innerHTML = '<span style="color: #f39c12;"><i class="fas fa-exclamation-triangle"></i> Moderate password - meet all requirements above</span>';
        } else {
            strengthText.innerHTML = '<span style="color: #e74c3c;"><i class="fas fa-exclamation-circle"></i> Weak password - minimum 8 characters</span>';
        }
    }
}

// Password match checker for edit modal
function checkEditPasswordMatch() {
    const password = document.getElementById('edit_new_password').value;
    const confirmPassword = document.getElementById('edit_confirm_password').value;
    const matchText = document.getElementById('edit_pwMatch');
    const reqMatch = document.getElementById('edit_req-match');
    
    if (!password && !confirmPassword) {
        if (matchText) matchText.innerHTML = '';
        if (reqMatch) reqMatch.innerHTML = '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> Passwords match';
        return;
    }
    
    if (password && confirmPassword) {
        if (password === confirmPassword) {
            if (matchText) matchText.innerHTML = '<span style="color: #2ecc71;"><i class="fas fa-check-circle"></i> Passwords match</span>';
            if (reqMatch) reqMatch.innerHTML = '<i class="fas fa-check-circle" style="color: #2ecc71; margin-right: 5px;"></i> Passwords match ✓';
        } else {
            if (matchText) matchText.innerHTML = '<span style="color: #e74c3c;"><i class="fas fa-exclamation-circle"></i> Passwords do not match</span>';
            if (reqMatch) reqMatch.innerHTML = '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> Passwords match';
        }
    } else if (password && !confirmPassword) {
        if (matchText) matchText.innerHTML = '<span style="color: #f39c12;"><i class="fas fa-exclamation-triangle"></i> Please confirm password</span>';
        if (reqMatch) reqMatch.innerHTML = '<i class="fas fa-times" style="color: #e74c3c; margin-right: 5px;"></i> Passwords match';
    }
}

// Calculate age in edit modal
function calculateEditAge() {
    const birthdayInput = document.querySelector('input[name="birthday"]');
    const ageField = document.getElementById('edit_age');
    const ageMessage = document.getElementById('edit_ageMessage');
    
    if (!birthdayInput || !birthdayInput.value) return;
    
    const birthday = new Date(birthdayInput.value);
    const today = new Date();
    let age = today.getFullYear() - birthday.getFullYear();
    const monthDiff = today.getMonth() - birthday.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthday.getDate())) {
        age--;
    }
    
    if (ageField) ageField.value = age;
    
    if (ageMessage) {
        if (age < 18) {
            ageMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i> User must be at least 18 years old';
        } else {
            ageMessage.innerHTML = '';
        }
    }
}

// View User Function
function viewUser(id) {
    document.getElementById('viewModal').style.display = 'flex';
    document.getElementById('viewLoading').style.display = 'block';
    document.getElementById('viewContent').style.display = 'none';
    
    fetch('manage_users_account.php?get_user=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewLoading').style.display = 'none';
            document.getElementById('viewContent').style.display = 'block';
            
            if (data.success) {
                const profile = data.profile;
                let html = `
                    <div class="profile-card">
                        <div class="profile-section-title">
                            <i class="fas fa-id-card"></i>
                            <h3>Account Information</h3>
                        </div>
                        <div class="profile-grid">
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-id-card"></i> ID Number</div>
                                <div class="field-value">${profile.id_no}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-user"></i> Username</div>
                                <div class="field-value">${profile.username}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-envelope"></i> Email</div>
                                <div class="field-value">${profile.email}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-tag"></i> Role</div>
                                <div class="field-value"><span class="badge-role ${profile.role}">${profile.role.toUpperCase()}</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <div class="profile-section-title">
                            <i class="fas fa-user"></i>
                            <h3>Personal Information</h3>
                        </div>
                        <div class="profile-grid">
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-signature"></i> Full Name</div>
                                <div class="field-value">${profile.full_name}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-birthday-cake"></i> Birthday</div>
                                <div class="field-value">${profile.birthday}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-calendar-alt"></i> Age</div>
                                <div class="field-value">${profile.age} years old</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-venus-mars"></i> Sex</div>
                                <div class="field-value">${profile.sex}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <div class="profile-section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>Address Information</h3>
                        </div>
                        <div class="profile-grid">
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-home"></i> Purok/Street</div>
                                <div class="field-value">${profile.purok_street}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-map-pin"></i> Barangay</div>
                                <div class="field-value">${profile.barangay}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-city"></i> City</div>
                                <div class="field-value">${profile.municipality_city}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-globe"></i> Province</div>
                                <div class="field-value">${profile.province}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-globe-asia"></i> Country</div>
                                <div class="field-value">${profile.country}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label"><i class="fas fa-mail-bulk"></i> Zip Code</div>
                                <div class="field-value">${profile.zip_code}</div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('viewContent').innerHTML = html;
            } else {
                document.getElementById('viewContent').innerHTML = '<p style="color: var(--danger); text-align: center;">' + data.message + '</p>';
            }
        })
        .catch(error => {
            document.getElementById('viewLoading').style.display = 'none';
            document.getElementById('viewContent').style.display = 'block';
            document.getElementById('viewContent').innerHTML = '<p style="color: var(--danger); text-align: center;">Error loading user data</p>';
            console.error('Error:', error);
        });
}

// Edit User Function (Updated with Password Section)
function editUser(id) {
    document.getElementById('editModal').style.display = 'flex';
    document.getElementById('editLoading').style.display = 'block';
    document.getElementById('editContent').style.display = 'none';
    
    fetch('manage_users_account.php?get_user=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editLoading').style.display = 'none';
            document.getElementById('editContent').style.display = 'block';
            
            if (data.success) {
                const profile = data.profile;
                let html = `
                    <form id="editUserForm" onsubmit="updateUser(event)">
                        <input type="hidden" name="id_no" value="${profile.id_no}">
                        
                        <!-- Password Change Section (NEW) -->
                        <div class="profile-card" style="border: 2px solid #f39c12; margin-bottom: 25px; background: rgba(243, 156, 18, 0.05);">
                            <div class="profile-section-title" style="border-bottom-color: #f39c12;">
                                <i class="fas fa-key" style="color: #f39c12;"></i>
                                <h3>Change Password <span style="font-size: 12px; color: #666; font-weight: normal;">(Optional - leave blank to keep current)</span></h3>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-lock"></i>
                                        New Password
                                    </label>
                                    <div style="position: relative;">
                                        <input type="password" 
                                               name="new_password" 
                                               id="edit_new_password" 
                                               class="form-input" 
                                               placeholder="Enter new password"
                                               oninput="checkEditPasswordStrength()">
                                        <i class="fas fa-eye" 
                                           style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;"
                                           onclick="togglePasswordVisibility('edit_new_password', this)"></i>
                                    </div>
                                    <div id="edit_pwStrength" style="font-size: 12px; margin-top: 5px; min-height: 20px;"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-lock"></i>
                                        Confirm Password
                                    </label>
                                    <div style="position: relative;">
                                        <input type="password" 
                                               name="confirm_password" 
                                               id="edit_confirm_password" 
                                               class="form-input" 
                                               placeholder="Confirm new password"
                                               oninput="checkEditPasswordMatch()">
                                        <i class="fas fa-eye" 
                                           style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;"
                                           onclick="togglePasswordVisibility('edit_confirm_password', this)"></i>
                                    </div>
                                    <div id="edit_pwMatch" style="font-size: 12px; margin-top: 5px; min-height: 20px;"></div>
                                </div>
                            </div>
                            
                            
                        </div>
                        
                        <!-- Account Information -->
                        <div class="profile-card">
                            <div class="profile-section-title">
                                <i class="fas fa-id-card"></i>
                                <h3>Account Information</h3>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-id-card"></i> ID Number</label>
                                    <input type="text" value="${profile.id_no}" readonly class="readonly-field">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Username</label>
                                    <input type="text" name="username" value="${profile.username}" required 
                                           pattern="[a-z][a-z0-9._]*" 
                                           title="Username must start with lowercase letter and contain only lowercase letters, numbers, dots, and underscores">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" name="email" value="${profile.email}" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-tag"></i> Role</label>
                                    <input type="text" value="${profile.role.toUpperCase()}" readonly class="readonly-field">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personal Information -->
                        <div class="profile-card">
                            <div class="profile-section-title">
                                <i class="fas fa-user"></i>
                                <h3>Personal Information</h3>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-signature"></i> First Name</label>
                                    <input type="text" name="f_name" value="${profile.first_name}" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-signature"></i> Middle Initial</label>
                                    <input type="text" name="m_initial" value="${profile.middle_initial || ''}" maxlength="1" 
                                           pattern="[A-Z]" title="Middle initial must be a single capital letter">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-signature"></i> Last Name</label>
                                    <input type="text" name="l_name" value="${profile.last_name}" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-signature"></i> Extension</label>
                                    <input type="text" name="extension" value="${profile.extension_name || ''}" 
                                           placeholder="Jr, Sr, II, III, etc.">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-birthday-cake"></i> Birthday</label>
                                    <input type="date" name="birthday" value="${profile.birthday}" required onchange="calculateEditAge()">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-venus-mars"></i> Sex</label>
                                    <select name="sex" required>
                                        <option value="Male" ${profile.sex === 'Male' ? 'selected' : ''}>Male</option>
                                        <option value="Female" ${profile.sex === 'Female' ? 'selected' : ''}>Female</option>
                                        <option value="Other" ${profile.sex === 'Other' ? 'selected' : ''}>Other</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="age" id="edit_age" value="${profile.age}">
                            <div id="edit_ageMessage" style="color: #e74c3c; font-size: 12px; margin-top: 5px;"></div>
                        </div>
                        
                        <!-- Address Information -->
                        <div class="profile-card">
                            <div class="profile-section-title">
                                <i class="fas fa-map-marker-alt"></i>
                                <h3>Address Information</h3>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-home"></i> Purok/Street</label>
                                    <input type="text" name="purok" value="${profile.purok_street}" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-map-pin"></i> Barangay</label>
                                    <input type="text" name="barangay" value="${profile.barangay}" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-city"></i> City/Municipality</label>
                                    <input type="text" name="city" value="${profile.municipality_city}" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-globe"></i> Province</label>
                                    <input type="text" name="province" value="${profile.province}" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-globe-asia"></i> Country</label>
                                    <input type="text" name="country" value="${profile.country}" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-mail-bulk"></i> Zip Code</label>
                                    <input type="text" name="zipcode" value="${profile.zip_code}" required 
                                           pattern="\\d{4}" title="Zip code must be exactly 4 digits"
                                           maxlength="4" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn-modal btn-save" id="editSubmitBtn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                `;
                
                document.getElementById('editContent').innerHTML = html;
                
                // Initialize password validation listeners
                setTimeout(() => {
                    const pwInput = document.getElementById('edit_new_password');
                    const confirmInput = document.getElementById('edit_confirm_password');
                    
                    if (pwInput) {
                        pwInput.addEventListener('input', checkEditPasswordStrength);
                        pwInput.addEventListener('input', checkEditPasswordMatch);
                    }
                    if (confirmInput) {
                        confirmInput.addEventListener('input', checkEditPasswordMatch);
                    }
                }, 100);
                
            } else {
                document.getElementById('editContent').innerHTML = '<p style="color: var(--danger); text-align: center;">' + data.message + '</p>';
            }
        })
        .catch(error => {
            document.getElementById('editLoading').style.display = 'none';
            document.getElementById('editContent').style.display = 'block';
            document.getElementById('editContent').innerHTML = '<p style="color: var(--danger); text-align: center;">Error loading user data</p>';
            console.error('Error:', error);
        });
}

// Update User Function (Updated with Password Validation)
function updateUser(event) {
    event.preventDefault();
    
    const form = document.getElementById('editUserForm');
    const newPassword = document.getElementById('edit_new_password')?.value || '';
    const confirmPassword = document.getElementById('edit_confirm_password')?.value || '';
    
    // Validate password if provided
    if (newPassword) {
        // Check length
        if (newPassword.length < 8 || newPassword.length >= 20) {
            showToast('Password must be between 8 and 20 characters', 'error', 'Validation Error');
            return;
        }
        
        // Check password strength
        if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
            showToast('Password must contain at least one uppercase letter, one lowercase letter, and one number', 'error', 'Validation Error');
            return;
        }
        
        // Check if passwords match
        if (newPassword !== confirmPassword) {
            showToast('Passwords do not match', 'error', 'Validation Error');
            return;
        }
    }
    
    // Validate age
    const ageField = document.getElementById('edit_age');
    if (ageField && ageField.value < 18) {
        showToast('User must be at least 18 years old', 'error', 'Validation Error');
        return;
    }
    
    // Validate zipcode
    const zipcode = form.querySelector('input[name="zipcode"]').value;
    if (!/^\d{4}$/.test(zipcode)) {
        showToast('Zipcode must be exactly 4 digits', 'error', 'Validation Error');
        return;
    }
    
    // Validate username format
    const username = form.querySelector('input[name="username"]').value;
    if (!/^[a-z][a-z0-9._]*$/.test(username)) {
        showToast('Username must start with lowercase letter and contain only lowercase letters, numbers, dots, and underscores', 'error', 'Validation Error');
        return;
    }
    
    if (username.length < 5 || username.length >= 20) {
        showToast('Username must be between 5 and 20 characters', 'error', 'Validation Error');
        return;
    }
    
    // Validate middle initial if provided
    const mInitial = form.querySelector('input[name="m_initial"]')?.value;
    if (mInitial && !/^[A-Z]$/.test(mInitial)) {
        showToast('Middle initial must be a single capital letter', 'error', 'Validation Error');
        return;
    }
    
    // Validate names don't contain numbers
    const fName = form.querySelector('input[name="f_name"]').value;
    const lName = form.querySelector('input[name="l_name"]').value;
    if (/\d/.test(fName) || /\d/.test(lName)) {
        showToast('Names must not contain numbers', 'error', 'Validation Error');
        return;
    }
    
    const formData = new FormData(form);
    
    const submitBtn = document.getElementById('editSubmitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    fetch('admin_update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success', 'Profile Updated');
            closeEditModal();
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Failed to update user profile.', 'error', 'Update Failed');
        }
    })
    .catch(error => {
        showToast('An error occurred while updating the profile.', 'error', 'System Error');
        console.error('Error:', error);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show Block/Unblock Confirmation Modal
function showBlockModal(id, username, action) {
    document.getElementById('blockId').value = id;
    document.getElementById('blockUsername').textContent = username;
    document.getElementById('blockAction').value = action;
    document.getElementById('blockActionText').textContent = action;
    
    const modalTitle = document.getElementById('blockModalTitle');
    const confirmBtn = document.getElementById('blockConfirmBtn');
    const blockIcon = document.getElementById('blockIcon');
    const blockWarning = document.getElementById('blockWarning');
    
    if (action === 'block') {
        modalTitle.textContent = 'Block User';
        confirmBtn.innerHTML = '<i class="fas fa-ban"></i> Block User';
        confirmBtn.className = 'btn-modal btn-block-confirm';
        blockIcon.innerHTML = '<i class="fas fa-ban"></i>';
        blockWarning.innerHTML = '<i class="fas fa-exclamation-circle"></i> This user will lose access to the system.';
    } else {
        modalTitle.textContent = 'Unblock User';
        confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Unblock User';
        confirmBtn.className = 'btn-modal btn-unblock-confirm';
        blockIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
        blockWarning.innerHTML = '<i class="fas fa-info-circle"></i> This user will regain access to the system.';
    }
    
    document.getElementById('blockModal').style.display = 'flex';
}

// Close Block Modal
function closeBlockModal() {
    document.getElementById('blockModal').style.display = 'none';
}

// Close View Modal
function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
    document.getElementById('viewContent').style.display = 'none';
    document.getElementById('viewContent').innerHTML = '';
}

// Close Edit Modal
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editContent').style.display = 'none';
    document.getElementById('editContent').innerHTML = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('viewModal');
    const editModal = document.getElementById('editModal');
    const blockModal = document.getElementById('blockModal');
    
    if (event.target === viewModal) {
        closeViewModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === blockModal) {
        closeBlockModal();
    }
}

// Highlight search term in results
document.addEventListener('DOMContentLoaded', function() {
    const searchTerm = '<?= htmlspecialchars($search) ?>';
    if (searchTerm && searchTerm.length > 0) {
        const cells = document.querySelectorAll('td:not(:has(button))');
        cells.forEach(cell => {
            const html = cell.innerHTML;
            const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            cell.innerHTML = html.replace(regex, '<span class="search-highlight">$1</span>');
        });
    }
});
</script>

</body>
</html>