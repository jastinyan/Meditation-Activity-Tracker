<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$id_no = $_SESSION['id_no'];
$role = $_SESSION['role'] ?? 'super_admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Profile - Meditation Activity Tracker</title>
    <link rel="stylesheet" href="../css/superadmin_home.css">
    <link rel="stylesheet" href="../css/superadmin_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="navbar-content">
        <p class="system-title">Super Admin Profile</p>
        <div class="admin-badge">
            <span class="role-admin"><i class="fas fa-shield-alt"></i> Super Admin</span>
        </div>
    </div>
</div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-crown"></i> Welcome, <?= htmlspecialchars($username) ?>
    </div>
    <h3><i class="fas fa-cog"></i> Management</h3>
    <a href="superadmin_home.php"><i class="fas fa-home"></i> Overview</a>
    <a href="reports.php"><i class="fas fa-chart-bar"></i> Analytics/Reports</a>
    <a href="manage_users_account.php"><i class="fas fa-users"></i> Manage Account</a>
    <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
    <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
    <a href="system_logs.php"><i class="fas fa-history"></i> Logs</a>
    <a href="superadmin_profile.php" class="active"><i class="fas fa-user-cog"></i> Profile</a>
    <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<!-- MAIN CONTENT -->
<main class="dashboard-main">
    <div class="content-wrapper">
        
        <!-- Profile Header -->
        <div class="welcome-section">
            <h2><i class="fas fa-crown"></i> My Profile</h2>
            <button class="btn-edit-profile" id="editProfileBtn">
                <i class="fas fa-edit"></i> Edit Profile
            </button>
        </div>

        <!-- Profile Container -->
        <div class="profile-container">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="profile-name" id="displayFullName">Loading...</div>
                <div class="profile-username" id="displayUsername">@<?= htmlspecialchars($username) ?></div>
                <div class="profile-id">ID: <span id="displayIdNo"><?= htmlspecialchars($id_no) ?></span></div>
                <div class="profile-role">
                    <span class="role-badge superadmin"><i class="fas fa-crown"></i> Super Administrator</span>
                </div>

                <div class="profile-actions">
                    <a href="forgot_password_flow.php" class="btn-change-password">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="profile-info">
                <div class="info-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="info-grid" id="personalInfo">
                        <!-- Data will be loaded here -->
                        <div class="loading-message">
                            <i class="fas fa-spinner fa-spin"></i> Loading personal information...
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                    <div class="info-grid" id="addressInfo">
                        <!-- Data will be loaded here -->
                        <div class="loading-message">
                            <i class="fas fa-spinner fa-spin"></i> Loading address information...
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h3><i class="fas fa-envelope"></i> Account Information</h3>
                    <div class="info-grid" id="accountInfo">
                        <!-- Data will be loaded here -->
                        <div class="loading-message">
                            <i class="fas fa-spinner fa-spin"></i> Loading account information...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SUPER ADMIN PRIVILEGES SECTION (MOVED BELOW PROFILE CONTAINER) -->
        <div class="privileges-section">
            <h3><i class="fas fa-shield-alt"></i> Super Admin Privileges</h3>
            
            <!-- Detailed Privileges Grid -->
            <div class="privileges-grid">
                <div class="privilege-item">
                    <div class="privilege-icon analytics"><i class="fas fa-chart-line"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">View System Analytics</div>
                        <div class="privilege-desc">Access to all reports and statistics</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon view"><i class="fas fa-eye"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">View User Profiles</div>
                        <div class="privilege-desc">Access to all user information</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon edit"><i class="fas fa-edit"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">Edit User Information</div>
                        <div class="privilege-desc">Modify user profiles and details</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon delete"><i class="fas fa-trash"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">Delete User Accounts</div>
                        <div class="privilege-desc">Remove users from the system</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon block"><i class="fas fa-ban"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">Block/Unblock Users</div>
                        <div class="privilege-desc">Manage user access to the system</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon logs"><i class="fas fa-history"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">View System Logs</div>
                        <div class="privilege-desc">Monitor all system activities</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon accept"><i class="fas fa-check-circle"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">Accept Registrations</div>
                        <div class="privilege-desc">Approve new user accounts</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon reject"><i class="fas fa-times-circle"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">Reject Registrations</div>
                        <div class="privilege-desc">Decline new user applications</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon role"><i class="fas fa-user-tag"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">Change User Roles</div>
                        <div class="privilege-desc">Assign admin or user permissions</div>
                    </div>
                </div>

                <div class="privilege-item">
                    <div class="privilege-icon config"><i class="fas fa-database"></i></div>
                    <div class="privilege-content">
                        <div class="privilege-title">System Configuration</div>
                        <div class="privilege-desc">Manage system settings and parameters</div>
                    </div>
                </div>
            </div>

            <!-- Stats Summary -->
            <div class="privilege-stats">
                
                <div class="access-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Access Level:</span>
                    <strong>Full System Control</strong>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- EDIT PROFILE MODAL -->
<div class="modal" id="editProfileModal">
    <div class="modal-content">
        <span class="close-btn" id="closeModalBtn">&times;</span>
        <h3><i class="fas fa-edit"></i> Edit Profile</h3>

        <form id="editProfileForm" class="profile-form">
            <!-- Personal Information -->
            <div class="form-section">
                <h4><i class="fas fa-user"></i> Personal Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> ID No</label>
                        <input type="text" id="editIdNo" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> First Name</label>
                        <input type="text" id="editFName" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Middle Initial</label>
                        <input type="text" id="editMInitial" class="form-input" maxlength="2">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Last Name</label>
                        <input type="text" id="editLName" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Extension</label>
                        <input type="text" id="editExtension" class="form-input" placeholder="Jr., Sr., III">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-birthday-cake"></i> Birthday</label>
                        <input type="date" id="editBirthday" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Age</label>
                        <input type="text" id="editAge" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-venus-mars"></i> Sex</label>
                        <select id="editSex" class="form-input" required>
                            <option value="">Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="form-section">
                <h4><i class="fas fa-map-marker-alt"></i> Address Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-road"></i> Purok/Street</label>
                        <input type="text" id="editPurok" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-pin"></i> Barangay</label>
                        <input type="text" id="editBarangay" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-city"></i> City</label>
                        <input type="text" id="editCity" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map"></i> Province</label>
                        <input type="text" id="editProvince" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-globe"></i> Country</label>
                        <input type="text" id="editCountry" class="form-input" value="Philippines" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-mail-bulk"></i> Zip Code</label>
                        <input type="text" id="editZipCode" class="form-input" maxlength="4" required>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="form-section">
                <h4><i class="fas fa-envelope"></i> Account Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user-circle"></i> Username</label>
                        <input type="text" id="editUsername" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="editEmail" class="form-input" required>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" id="cancelEditBtn"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- SUCCESS TOAST -->
<div class="success-toast" id="successToast">
    <i class="fas fa-check-circle"></i> Profile updated successfully
</div>

<!-- ERROR TOAST -->
<div class="error-toast" id="errorToast">
    <i class="fas fa-exclamation-circle"></i> An error occurred
</div>

<!-- FOOTER -->
<div class="footer">
    <p><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script src="../script/superadmin_profile.js"></script>
</body>
</html>