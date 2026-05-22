<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$id_no = $_SESSION['id_no'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Meditation Activity Tracker</title>
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">

</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <p class="system-title container">Meditation Activity Tracker</p>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">Welcome <?= htmlspecialchars($username) ?></div>
    <a href="user_home.php">Your Calendar</a>
    <a href="session_timer.php">Session Timer</a>
    <a href="historysession.php">Past Sessions</a>
    <a href="progress.php">Session Progress</a>
    <a href="profile.php" style="background: rgb(181,65,7);">Profile</a>
    <a href="homepage.php" class="btn-logout">Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="profile-header">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
        <button class="btn-edit-profile" id="editProfileBtn">
            <i class="fas fa-edit"></i> Edit Profile
        </button>
    </div>

    <div class="profile-container">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-name" id="displayFullName">Loading...</div>
            <div class="profile-username" id="displayUsername">@<?= htmlspecialchars($username) ?></div>
            <div class="profile-id">ID: <span id="displayIdNo"><?= htmlspecialchars($id_no) ?></span></div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-value" id="totalSessions">0</span>
                    <span class="stat-label">Sessions</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="totalTime">0</span>
                    <span class="stat-label">Minutes</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="currentStreak">0</span>
                    <span class="stat-label">Streak</span>
                </div>
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
                    <div class="loading">Loading personal information...</div>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                <div class="info-grid" id="addressInfo">
                    <!-- Data will be loaded here -->
                    <div class="loading">Loading address information...</div>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-envelope"></i> Account Information</h3>
                <div class="info-grid" id="accountInfo">
                    <!-- Data will be loaded here -->
                    <div class="loading">Loading account information...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- USER PRIVILEGES SECTION (BELOW PROFILE CONTAINER) -->
    <div class="privileges-section">
        <h3><i class="fas fa-user-shield"></i> Your Privileges</h3>
        
        <!-- Detailed Privileges Grid -->
        <div class="privileges-grid">
            <!-- Schedule a Session -->
            <div class="privilege-item">
                <div class="privilege-icon schedule"><i class="fas fa-calendar-plus"></i></div>
                <div class="privilege-content">
                    <div class="privilege-title">Schedule a Session</div>
                    <div class="privilege-desc">Create and schedule meditation sessions on your calendar</div>
                </div>
            </div>

            <!-- Do a Session -->
            <div class="privilege-item">
                <div class="privilege-icon session"><i class="fas fa-play-circle"></i></div>
                <div class="privilege-content">
                    <div class="privilege-title">Do a Session</div>
                    <div class="privilege-desc">Start and complete meditation sessions</div>
                </div>
            </div>

            <!-- View Progress -->
            <div class="privilege-item">
                <div class="privilege-icon view"><i class="fas fa-chart-line"></i></div>
                <div class="privilege-content">
                    <div class="privilege-title">View Progress</div>
                    <div class="privilege-desc">View your meditation history and progress</div>
                </div>
            </div>

            <!-- Edit Progress -->
            <div class="privilege-item">
                <div class="privilege-icon edit"><i class="fas fa-edit"></i></div>
                <div class="privilege-content">
                    <div class="privilege-title">Edit Progress</div>
                    <div class="privilege-desc">Update your session notes and details</div>
                </div>
            </div>


            <!-- Edit Personal Information -->
            <div class="privilege-item">
                <div class="privilege-icon profile"><i class="fas fa-user-edit"></i></div>
                <div class="privilege-content">
                    <div class="privilege-title">Edit Personal Information</div>
                    <div class="privilege-desc">Update your profile details and preferences</div>
                </div>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="privilege-stats">
            <div class="access-badge">
                <i class="fas fa-user"></i>
                <span>Access Level:</span>
                <strong>Standard User</strong>
            </div>
        </div>
    </div>
</div>

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
                        <label>ID No</label>
                        <input type="text" id="editIdNo" name="id_no" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="editFName" name="fname" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" id="editMInitial" name="minitial" class="form-input" maxlength="2">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" id="editLName" name="lname" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Extension Name</label>
                        <input type="text" id="editExtension" name="extension" class="form-input" placeholder="Jr., Sr., III">
                    </div>
                    <div class="form-group">
                        <label>Birthday</label>
                        <input type="date" id="editBirthday" name="birthday" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Age</label>
                        <input type="text" id="editAge" name="age" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label>Sex</label>
                        <select id="editSex" name="sex" class="form-input" required>
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
                        <label>Purok/Street</label>
                        <input type="text" id="editPurok" name="purok" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Barangay</label>
                        <input type="text" id="editBarangay" name="barangay" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Municipality/City</label>
                        <input type="text" id="editCity" name="city" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <input type="text" id="editProvince" name="province" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" id="editCountry" name="country" class="form-input" value="Philippines" required>
                    </div>
                    <div class="form-group">
                        <label>Zip Code</label>
                        <input type="text" id="editZipCode" name="zipcode" class="form-input" maxlength="4" required>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="form-section">
                <h4><i class="fas fa-envelope"></i> Account Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="editUsername" name="username" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="editEmail" name="email" class="form-input" required>
                    </div>
                </div>
                <!-- Account Created Date (Read-only) -->
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Account Created</label>
                        <input type="text" id="editCreatedAt" name="created_at" class="form-input" readonly>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" id="cancelEditBtn">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
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
    <p class="all">&copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script src="../script/profile.js"></script>
</body>
</html>