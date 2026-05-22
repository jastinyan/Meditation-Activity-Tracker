<?php
session_start();
require 'db_connection.php';

// Protect page - only admin and super_admin can access
if (!isset($_SESSION['id_no']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$user_role = $_SESSION['role']; // Get the current user's role
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin_home.css">
    <link rel="stylesheet" href="../css/admin_register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
    <title>MAT - Admin Registration</title>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-content">
            <p class="system-title">
        
                <?= ($user_role === 'super_admin') ? 'Super Admin' : 'Admin' ?> Registration
            </p>
            <div class="admin-badge">
                <span class="role-admin">
                    <i class="fas fa-shield-alt"></i> 
                    <?= htmlspecialchars($user_role === 'super_admin' ? 'Super Admin' : 'Administrator') ?>
                </span>
            </div>
        </div>
    </div>

    <div class="dashboard-wrapper">
        <!-- SIDEBAR (based on role) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-user-shield"></i> Welcome, <?= htmlspecialchars($username) ?>
            </div>
            <h3><i class="fas fa-cog"></i> Management</h3>
            
            <?php if ($user_role === 'super_admin'): ?>
                <!-- Super Admin Sidebar -->
                <a href="superadmin_home.php"><i class="fas fa-home"></i> Overview</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Analytics/Reports</a>
                <a href="manage_account.php"><i class="fas fa-users-cog"></i> Manage Accounts</a>
                <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
                <a href="admin_register.php" class="active"><i class="fas fa-user-plus"></i> Register User</a>
                <a href="system_logs.php"><i class="fas fa-history"></i> Logs</a>
                <a href="superadmin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <?php else: ?>
                <!-- Admin Sidebar -->
                <a href="admin_home.php"><i class="fas fa-home"></i> Overview</a>
                <a href="reports.php"><i class="fas fa-chart-pie"></i> Analytics/Reports</a>
                <a href="manage_users_account.php"><i class="fas fa-users"></i> Manage Accounts</a>
                <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
                <a href="admin_register.php" class="active"><i class="fas fa-user-plus"></i> Register User</a>
                <a href="admin_system_logs.php"><i class="fas fa-history"></i> Logs</a>
                <a href="admin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <?php endif; ?>
            
            <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="content-wrapper">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h2><i class="fas fa-user-plus"></i> Register New User</h2>
                    <p class="date-display">
                        <i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?>
                    </p>
                </div>

                <!-- Role Information -->
                <div class="role-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Auto-Accept Registration:</strong> New users will be automatically approved and can log in immediately.
                        <?php if ($user_role === 'super_admin'): ?>
                            <br><small>You have permission to create Super Admin, Admin, and User accounts.</small>
                        <?php else: ?>
                            <br><small>You have permission to create Admin and User accounts only.</small>
                        <?php endif; ?>
                    </div>
                    <span class="auto-accept-badge">
                        <i class="fas fa-check-circle"></i> Auto-Accept
                    </span>
                </div>

                <!-- Registration Form -->
                <div class="register-box">
                    <form name="form" id="form" method="POST" action="admin_register_process.php">
                        
                        <!-- Personal Information -->
                        <h3 class="form-section-title"><i class="fas fa-user"></i> Personal Information</h3>
                        <div class="personal-info">
                            <div class="input-field">
                                <label class="label">ID No <span class="required">*</span></label>
                                <input type="text" name="id_no" class="input" placeholder="XXXX-XXXX" id="id_no" maxlength="9" required> 
                            </div>  
                            <div class="input-field">
                                <label class="label">First Name <span class="required">*</span></label>
                                <input type="text" class="input" name="f_name" placeholder="" id="f_name" required>
                            </div> 
                            <div class="input-field">
                                <label class="label">Middle Initial</label>
                                <input type="text" class="input" name="m_initial" placeholder="" id="m_initial" maxlength="1"> 
                            </div> 
                            <div class="input-field">
                                <label class="label">Last Name <span class="required">*</span></label>
                                <input type="text" class="input" name="l_name" placeholder="" id="l_name" required>
                            </div> 
                        </div>

                        <div class="personal-info">
                            <div class="input-field">
                                <label class="label">Extension Name</label>
                                <input type="text" name="extension" class="input" placeholder="Jr/Sr/III" id="extension">
                            </div> 
                            <div class="input-field">
                                <label class="label">Birthday <span class="required">*</span></label>
                                <input type="date" name="birthday" class="input" id="birthday" required>
                            </div>
                            <div class="input-field">
                                <label class="label">Age</label>
                                <input type="text" name="ageDisplay" class="input" id="age" readonly>
                                <div id="ageMessage" style="color: red; font-size: 12px;"></div>
                                <input type="hidden" id="ageHidden" name="age">
                            </div>
                            <div class="input-field">
                                <label class="label">Sex <span class="required">*</span></label>
                                <select name="sex" id="sex" required>
                                    <option value=""></option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div> 
                        </div>

                        <!-- Account Information -->
                        <h3 class="form-section-title"><i class="fas fa-lock"></i> Account Information</h3>
                        <div class="account-info">
                            <div class="input-field">
                                <label class="label">Username <span class="required">*</span></label>
                                <input type="text" class="input" name="username" placeholder="" id="username" required>
                            </div>  
                            <div class="input-field">
                                <label class="label">Password <span class="required">*</span></label>
                                <input type="password" class="input" name="password" placeholder="" id="password" required>
                                <div id="pwStrength" style="font-size: 12px;"></div>
                            </div>  
                            <div class="input-field">
                                <label class="label">Re-enter Password <span class="required">*</span></label>
                                <input type="password" class="input" name="confirm-pass" placeholder="" id="confirmPassword" required>
                                <div id="pwMatch" style="font-size: 12px;"></div>
                            </div>  
                            <div class="input-field">
                                <label class="label">Email <span class="required">*</span></label>
                                <input type="email" class="input" name="email" placeholder="" id="email" required>
                            </div>  
                        </div>

                        <!-- Role Selection -->
                        <h3 class="form-section-title"><i class="fas fa-user-tag"></i> Role Assignment</h3>
                        <div class="role-field">
                            <div class="input-field">
                                <label class="label"> Role <span class="required">*</span></label>
                                <select name="role" id="role" class="role-select" required>
                                    <option value=""> Select Role</option>
                                    <?php if ($user_role === 'super_admin'): ?>
                                        <option value="super_admin">Super Admin (Full Access)</option>
                                    <?php endif; ?>
                                    <option value="admin">Admin (Management Access)</option>
                                    <option value="user">User (Basic Access)</option>
                                </select>
                
                            </div>
                        </div>

                        <!-- Address Information -->
                        <h3 class="form-section-title"><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                        <div class="address-info">
                            <div class="input-field">
                                <label class="label">Purok/Street <span class="required">*</span></label>
                                <input type="text" class="input" name="purok" placeholder="" id="purok" required>
                            </div>  
                            <div class="input-field">
                                <label class="label">Barangay <span class="required">*</span></label>
                                <input type="text" class="input" name="barangay" placeholder="" id="barangay" required>
                            </div>  
                            <div class="input-field">
                                <label class="label">Municipality/City <span class="required">*</span></label>
                                <input type="text" class="input" name="city" placeholder="" id="city" required>
                            </div>  
                        </div>
                        <div class="address-info">
                            <div class="input-field">
                                <label class="label">Province <span class="required">*</span></label>
                                <input type="text" class="input" name="province" placeholder="" id="province" required>
                            </div> 
                            <div class="input-field">
                                <label class="label">Country <span class="required">*</span></label>
                                <input type="text" class="input" name="country" placeholder="" id="country" required>
                            </div> 
                            <div class="input-field">
                                <label class="label">Zip Code <span class="required">*</span></label>
                                <input type="text" class="input" name="zipcode" placeholder="" id="zipcode" maxlength="4" required>
                            </div> 
                        </div>

                        <!-- Security Questions -->
                        <h3 class="form-section-title"><i class="fas fa-question-circle"></i> Security Questions</h3>
                        <div class="security-info">
                            <div class="input-field">
                                <label class="label">Question 1 <span class="required">*</span></label>
                                <select name="sec_q1" id="sec_q1" required>
                                    <option value=""> Select a question </option>
                                    <option value="What is your mother's first name?">What is your mother's first name?</option>
                                    <option value="Where did you attend Elementary?">Where did you attend Elementary?</option>
                                    <option value="Where is your birthplace?">Where is your birthplace?</option>
                                    <option value="What is your favorite food?">What is your favorite food?</option>
                                    <option value="What is your favorite color?">What is your favorite color?</option>
                                    <option value="What is your childhood nickname?">What is your childhood nickname?</option>
                                    <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                                    <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                                    <option value="What city were you born in?">What city were you born in?</option>
                                    <option value="What is your father's middle name?">What is your father's middle name?</option>
                                </select>
                                <input type="password" class="input" name="sec_a1" placeholder="Answer" id="sec_a1" required>
                            </div>

                            <div class="input-field">
                                <label class="label">Question 2 <span class="required">*</span></label>
                                <select name="sec_q2" id="sec_q2" required>
                                    <option value="">Select a question</option>
                                    <option value="What is your mother's first name?">What is your mother's first name?</option>
                                    <option value="Where did you attend Elementary?">Where did you attend Elementary?</option>
                                    <option value="Where is your birthplace?">Where is your birthplace?</option>
                                    <option value="What is your favorite food?">What is your favorite food?</option>
                                    <option value="What is your favorite color?">What is your favorite color?</option>
                                    <option value="What is your childhood nickname?">What is your childhood nickname?</option>
                                    <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                                    <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                                    <option value="What city were you born in?">What city were you born in?</option>
                                    <option value="What is your father's middle name?">What is your father's middle name?</option>
                                </select>
                                <input type="password" class="input" name="sec_a2" placeholder="Answer" id="sec_a2" required>
                            </div>

                            <div class="input-field">
                                <label class="label">Question 3 <span class="required">*</span></label>
                                <select name="sec_q3" id="sec_q3" required>
                                    <option value="">Select a question</option>
                                    <option value="What is your mother's first name?">What is your mother's first name?</option>
                                    <option value="Where did you attend Elementary?">Where did you attend Elementary?</option>
                                    <option value="Where is your birthplace?">Where is your birthplace?</option>
                                    <option value="What is your favorite food?">What is your favorite food?</option>
                                    <option value="What is your favorite color?">What is your favorite color?</option>
                                    <option value="What is your childhood nickname?">What is your childhood nickname?</option>
                                    <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                                    <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                                    <option value="What city were you born in?">What city were you born in?</option>
                                    <option value="What is your father's middle name?">What is your father's middle name?</option>
                                </select>
                                <input type="password" class="input" name="sec_a3" placeholder="Answer" id="sec_a3" required>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn-submit" name="submit_btn">
                                <i class="fas fa-user-plus"></i> Register User
                            </button>
                            <button type="reset" class="btn-reset">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
    </div>

    <script src="../script/admin_register.js" defer></script>
</body>
</html>