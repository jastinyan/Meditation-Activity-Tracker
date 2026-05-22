<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is admin or super_admin
if (!isset($_SESSION['id_no']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$isSuperAdmin = ($role === 'super_admin');
$message = '';
$messageType = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $id_no = $_POST['id_no'];
        $stmt = $conn->prepare("UPDATE registeredacc SET approval_status = 'approved' WHERE id_no = ?");
        $stmt->bind_param("s", $id_no);
        if ($stmt->execute()) {
            $message = "User approved successfully!";
            $messageType = "success";
            
            // Log the approval
            $logStmt = $conn->prepare("INSERT INTO system_logs (id_no, username, action, browser, ip_address) VALUES (?, ?, 'approve_user', ?, ?)");
            $browser = $_SERVER['HTTP_USER_AGENT'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt->bind_param("ssss", $_SESSION['id_no'], $_SESSION['username'], $browser, $ip);
            $logStmt->execute();
            $logStmt->close();
        }
        $stmt->close();
    } elseif (isset($_POST['reject'])) {
        $id_no = $_POST['id_no'];
        $reason = $_POST['rejection_reason'];
        $stmt = $conn->prepare("UPDATE registeredacc SET approval_status = 'rejected', rejection_reason = ? WHERE id_no = ?");
        $stmt->bind_param("ss", $reason, $id_no);
        if ($stmt->execute()) {
            $message = "User rejected successfully!";
            $messageType = "success";
            
            // Log the rejection
            $logStmt = $conn->prepare("INSERT INTO system_logs (id_no, username, action, browser, ip_address) VALUES (?, ?, 'reject_user', ?, ?)");
            $browser = $_SERVER['HTTP_USER_AGENT'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt->bind_param("ssss", $_SESSION['id_no'], $_SESSION['username'], $browser, $ip);
            $logStmt->execute();
            $logStmt->close();
        }
        $stmt->close();
    }
}

// Fetch pending users
$pendingQuery = "SELECT id_no, username, email, f_name, l_name, created_at FROM registeredacc WHERE approval_status = 'pending' ORDER BY created_at DESC";
$pendingResult = $conn->query($pendingQuery);

// Fetch approved users
$approvedQuery = "SELECT id_no, username, email, f_name, l_name, created_at FROM registeredacc WHERE approval_status = 'approved' ORDER BY created_at DESC";
$approvedResult = $conn->query($approvedQuery);

// Fetch rejected users
$rejectedQuery = "SELECT id_no, username, email, f_name, l_name, created_at, rejection_reason FROM registeredacc WHERE approval_status = 'rejected' ORDER BY created_at DESC";
$rejectedResult = $conn->query($rejectedQuery);

// Handle AJAX request for user details
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_user' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("SELECT 
        id_no, f_name, m_initial, l_name, extension, 
        birthday, age, sex, username, email,
        purok, barangay, city, province, country, zipcode,
        sec_q1, sec_a1, sec_q2, sec_a2, sec_q3, sec_a3,
        approval_status, created_at
        FROM registeredacc WHERE id_no = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        // Format full name
        $full_name = trim($user['f_name'] . ' ' . $user['m_initial'] . ' ' . $user['l_name'] . ' ' . $user['extension']);
        $full_name = preg_replace('/\s+/', ' ', $full_name);
        $user['full_name'] = $full_name;
        
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - Meditation Activity Tracker</title>
    <link rel="stylesheet" href="../css/superadmin_home.css">
        <link rel="stylesheet" href="../css/approvals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
    
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="navbar-content">
        <p class="system-title">Registration Approval</p>
        <div class="admin-badge">
            <span class="role-<?= $role ?>"><i class="fas fa-shield-alt"></i> <?= ucfirst(str_replace('_', ' ', $role)) ?></span>
        </div>
    </div>
</div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-user-shield"></i> Welcome, <?= htmlspecialchars($username) ?>
    </div>
    <h3><i class="fas fa-cog"></i> Management</h3>
    <?php if ($isSuperAdmin): ?>
        <!-- Super Admin Sidebar -->
        <a href="superadmin_home.php"><i class="fas fa-home"></i> Overview</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Analytics/Reports</a>
        <a href="manage_account.php"><i class="fas fa-users-cog"></i> Manage Accounts</a>
        <a href="pending_approvals.php" class="active"><i class="fas fa-clock"></i> Approvals</a>
        <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
        <a href="system_logs.php"><i class="fas fa-history"></i> Logs</a>
        <a href="superadmin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
    <?php else: ?>
        <!-- Admin Sidebar -->
        <a href="admin_home.php"><i class="fas fa-home"></i> Overview</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Analytics/Reports</a>
        <a href="manage_users_account.php"><i class="fas fa-users"></i> Manage Accounts</a>
        <a href="pending_approvals.php" class="active"><i class="fas fa-clock"></i> Approvals</a>
        <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
        <a href="admin_system_logs.php"><i class="fas fa-history"></i> Logs</a>
        <a href="admin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
    <?php endif; ?>
    
    <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<!-- MAIN CONTENT -->
<main class="dashboard-main">
    <div class="approvals-container">
        <h1><i class="fas fa-user-check"></i> Registration Approvals</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>" style="background: rgba(<?= $messageType === 'success' ? '40,167,69' : '220,53,69' ?>,0.15); border: 1px solid rgba(<?= $messageType === 'success' ? '40,167,69' : '220,53,69' ?>,0.3); color: <?= $messageType === 'success' ? '#28a745' : '#dc3545' ?>;">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="tab-container">
            <div class="tab active" onclick="switchTab('pending', this)">
                <i class="fas fa-clock"></i> Pending <span class="badge pending"><?= $pendingResult->num_rows ?></span>
            </div>
            <div class="tab" onclick="switchTab('approved', this)">
                <i class="fas fa-check-circle"></i> Approved <span class="badge approved"><?= $approvedResult->num_rows ?></span>
            </div>
            <div class="tab" onclick="switchTab('rejected', this)">
                <i class="fas fa-times-circle"></i> Rejected <span class="badge rejected"><?= $rejectedResult->num_rows ?></span>
            </div>
        </div>
        
        <!-- Pending Tab -->
        <div id="pending-tab" class="tab-content active">
            <?php if ($pendingResult->num_rows > 0): ?>
                <table class="pending-table">
                    <thead>
                        <tr>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $pendingResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id_no']) ?></td>
                            <td><?= htmlspecialchars($row['f_name'] . ' ' . $row['l_name']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-approve" onclick="approveUser('<?= $row['id_no'] ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn-reject" onclick="showRejectModal('<?= $row['id_no'] ?>')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <button class="btn-view" onclick="viewUser('<?= $row['id_no'] ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success); opacity: 0.5;"></i>
                    <p style="margin-top: 15px; color: var(--text-muted);">No pending approvals!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Approved Tab -->
        <div id="approved-tab" class="tab-content">
            <?php if ($approvedResult->num_rows > 0): ?>
                <table class="approved-table">
                    <thead>
                        <tr>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Approved Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $approvedResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id_no']) ?></td>
                            <td><?= htmlspecialchars($row['f_name'] . ' ' . $row['l_name']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-users" style="font-size: 48px; color: var(--text-muted); opacity: 0.5;"></i>
                    <p style="margin-top: 15px; color: var(--text-muted);">No approved users yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Rejected Tab -->
        <div id="rejected-tab" class="tab-content">
            <?php if ($rejectedResult->num_rows > 0): ?>
                <table class="rejected-table">
                    <thead>
                        <tr>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Rejection Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $rejectedResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id_no']) ?></td>
                            <td><?= htmlspecialchars($row['f_name'] . ' ' . $row['l_name']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td class="rejection-reason"><?= htmlspecialchars($row['rejection_reason']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-check" style="font-size: 48px; color: var(--success); opacity: 0.5;"></i>
                    <p style="margin-top: 15px; color: var(--text-muted);">No rejected users.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- View User Modal -->
<div id="viewUserModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeViewModal()">&times;</span>
        
        <div class="modal-header">
            <h2><i class="fas fa-user-circle"></i> User Profile</h2>
        </div>
        
        <div id="viewUserContent">
            <div class="loading-spinner" id="viewLoading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading user information...</p>
            </div>
            <div id="viewUserDetails" style="display: none;"></div>
        </div>
        
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content" style="border-left-color: #dc3545; max-width: 500px;">
        <span class="close-modal" onclick="closeRejectModal()">&times;</span>
        <h3 style="color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-times-circle" style="color: #dc3545;"></i> Reject Registration
        </h3>
        <form method="POST">
            <input type="hidden" name="id_no" id="rejectUserId">
            <textarea name="rejection_reason" rows="4" placeholder="Enter reason for rejection..." required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.5); border: 2px solid var(--glass-border); border-radius: 8px; color: var(--text-primary); font-size: 14px; margin-bottom: 20px; resize: vertical;"></textarea>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-modal-cancel" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" name="reject" style="background: #dc3545; color: white; padding: 12px 25px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;">Reject User</button>
            </div>
        </form>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    <p><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script>
function switchTab(tab, element) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    element.classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(tab + '-tab').classList.add('active');
}

function approveUser(id) {
    if (confirm('Are you sure you want to approve this user?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="id_no" value="${id}"><input type="hidden" name="approve" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(id) {
    document.getElementById('rejectUserId').value = id;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

function viewUser(id) {
    document.getElementById('viewUserModal').style.display = 'flex';
    document.getElementById('viewLoading').style.display = 'block';
    document.getElementById('viewUserDetails').style.display = 'none';
    
    fetch('pending_approvals.php?ajax=get_user&id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewLoading').style.display = 'none';
            document.getElementById('viewUserDetails').style.display = 'block';
            
            if (data.success) {
                const user = data.user;
                let statusClass = user.approval_status;
                let statusIcon = user.approval_status === 'pending' ? 'fa-clock' : 
                                (user.approval_status === 'approved' ? 'fa-check-circle' : 'fa-times-circle');
                
                const html = `
                    <div class="user-profile-modal">
                        <div class="profile-sidebar">
                            <div class="profile-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="profile-fullname">${user.full_name}</div>
                            <div class="profile-username">@${user.username}</div>
                            <div class="profile-id">ID: ${user.id_no}</div>
                            <span class="status-badge ${statusClass}">
                                <i class="fas ${statusIcon}"></i> ${user.approval_status.charAt(0).toUpperCase() + user.approval_status.slice(1)}
                            </span>
                        </div>
                        
                        <div class="profile-main">
                            <div class="modal-info-section">
                                <h3><i class="fas fa-user"></i> Personal Information</h3>
                                <div class="modal-info-grid">
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">First Name</div>
                                        <div class="modal-info-value">${user.f_name}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Middle Initial</div>
                                        <div class="modal-info-value">${user.m_initial || 'N/A'}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Last Name</div>
                                        <div class="modal-info-value">${user.l_name}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Extension</div>
                                        <div class="modal-info-value">${user.extension || 'N/A'}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Birthday</div>
                                        <div class="modal-info-value">${new Date(user.birthday).toLocaleDateString()}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Age</div>
                                        <div class="modal-info-value">${user.age} years old</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Sex</div>
                                        <div class="modal-info-value">${user.sex}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Registered</div>
                                        <div class="modal-info-value">${new Date(user.created_at).toLocaleDateString()}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-info-section">
                                <h3><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                                <div class="modal-info-grid">
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Purok/Street</div>
                                        <div class="modal-info-value">${user.purok}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Barangay</div>
                                        <div class="modal-info-value">${user.barangay}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">City</div>
                                        <div class="modal-info-value">${user.city}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Province</div>
                                        <div class="modal-info-value">${user.province}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Country</div>
                                        <div class="modal-info-value">${user.country}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Zip Code</div>
                                        <div class="modal-info-value">${user.zipcode}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-info-section">
                                <h3><i class="fas fa-envelope"></i> Account Information</h3>
                                <div class="modal-info-grid">
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Username</div>
                                        <div class="modal-info-value">${user.username}</div>
                                    </div>
                                    <div class="modal-info-item">
                                        <div class="modal-info-label">Email</div>
                                        <div class="modal-info-value">${user.email}</div>
                                    </div>
                                </div>
                            </div>
                            
                            
                        </div>
                    </div>
                `;
                
                document.getElementById('viewUserDetails').innerHTML = html;
            } else {
                document.getElementById('viewUserDetails').innerHTML = '<p style="color: var(--danger); text-align: center;">' + data.message + '</p>';
            }
        })
        .catch(error => {
            document.getElementById('viewLoading').style.display = 'none';
            document.getElementById('viewUserDetails').style.display = 'block';
            document.getElementById('viewUserDetails').innerHTML = '<p style="color: var(--danger); text-align: center;">Error loading user data</p>';
            console.error('Error:', error);
        });
}

function closeViewModal() {
    document.getElementById('viewUserModal').style.display = 'none';
    document.getElementById('viewUserDetails').style.display = 'none';
    document.getElementById('viewUserDetails').innerHTML = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('viewUserModal');
    const rejectModal = document.getElementById('rejectModal');
    
    if (event.target === viewModal) {
        closeViewModal();
    }
    if (event.target === rejectModal) {
        closeRejectModal();
    }
}
</script>

</body>
</html>