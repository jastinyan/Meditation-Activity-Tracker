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
    <title>Session History - Meditation Activity Tracker</title>
    <link rel="stylesheet" href="../css/superadmin_home.css">
    <link rel="stylesheet" href="../css/historysession.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="navbar-content">
        <p class="system-title">Session History</p>
    </div>
</div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-header">Welcome <?= htmlspecialchars($username) ?></div>
    <a href="user_home.php"> Your Calendar</a>
    <a href="session_timer.php"> Session Timer</a>
    <a href="historysession.php" class="active"></i>Past Sessions</a>
    <a href="progress.php">Session Progress</a>
    <a href="profile.php">Profile</a>
    <a href="homepage.php" class="btn-logout"> Logout</a>
</aside>

<!-- MAIN CONTENT -->
<main class="dashboard-main">
    <div class="content-wrapper">
        
        <!-- Filter and Search Section -->
        <div class="filter-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search sessions by name or notes...">
            </div>
            
            <div class="filter-buttons">
                <select id="feelingFilter" class="filter-select">
                    <option value="">All Feelings</option>
                    <option value="Happy">😊 Happy</option>
                    <option value="Distracted">😅 Distracted</option>
                    <option value="Boring">😐 Boring</option>
                    <option value="Normal">🙂 Normal</option>
                </select>
                
                <select id="locationFilter" class="filter-select">
                    <option value="">All Locations</option>
                    <option value="Home">🏠 Home</option>
                    <option value="Work">🏢 Work</option>
                    <option value="Center">🧘 Center</option>
                    <option value="Outside">🌿 Outside</option>
                </select>
                
                <select id="sortBy" class="filter-select">
                    <option value="newest">🆕 Newest First</option>
                    <option value="oldest">📅 Oldest First</option>
                    <option value="longest">⏱️ Longest Duration</option>
                    <option value="shortest">⏱️ Shortest Duration</option>
                </select>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-info">
                    <span class="stat-label">Total Sessions</span>
                    <span class="stat-value" id="totalSessions">0</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-hourglass-half"></i>
                <div class="stat-info">
                    <span class="stat-label">Total Time</span>
                    <span class="stat-value" id="totalTime">00:00</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-alt"></i>
                <div class="stat-info">
                    <span class="stat-label">Average Time</span>
                    <span class="stat-value" id="avgTime">00:00</span>
                </div>
            </div>
        </div>

        <!-- Sessions Table -->
        <div class="sessions-table-container">
            <table class="sessions-table" id="sessionsTable">
                <thead>
                    <tr>
                        <th>Session Name</th>
                        <th>Duration</th>
                        <th>Date & Time</th>
                        <th>Feeling</th>
                        <th>Location</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sessionsList">
                    <!-- Data will be loaded here via JavaScript -->
                    <tr>
                        <td colspan="7" class="loading-message">
                            <i class="fas fa-spinner fa-spin"></i> Loading your sessions...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
    </div>
</main>

<!-- EDIT MODAL -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close-modal" id="closeEditBtn">&times;</span>
        <h2><i class="fas fa-edit"></i> Edit Session</h2>
        
        <form id="editForm" class="session-form">
            <input type="hidden" id="editSessionId">
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Session Name:</label>
                <select id="editSessionName" class="role-select">
                    <option value="Mindfulness Meditation">Mindfulness Meditation</option>
                    <option value="Breathing Meditation">Breathing Meditation</option>
                    <option value="Zen Meditation">Zen Meditation</option>
                    <option value="Guided Meditation">Guided Meditation</option>
                    <option value="Body Scan Meditation">Body Scan Meditation</option>
                    <option value="Loving-Kindness Meditation">Loving-Kindness Meditation</option>
                    <option value="Custom">✨ Custom (Type your own)</option>
                </select>
                <input type="text" id="editCustomSession" class="search-input" placeholder="Enter custom session name" style="display:none; margin-top: 10px;">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-hourglass-half"></i> Duration (seconds):</label>
                <input type="number" id="editTimeSpent" class="search-input" min="1" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Date & Time:</label>
                <input type="datetime-local" id="editDateTime" class="search-input" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-smile"></i> Feeling:</label>
                <div class="emoji-group" id="editFeelingGroup">
                    <div class="emoji-btn" data-value="Happy"><span>😊</span>Happy</div>
                    <div class="emoji-btn" data-value="Distracted"><span>😅</span>Distracted</div>
                    <div class="emoji-btn" data-value="Boring"><span>😐</span>Boring</div>
                    <div class="emoji-btn" data-value="Normal"><span>🙂</span>Normal</div>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Location:</label>
                <div class="emoji-group" id="editLocationGroup">
                    <div class="emoji-btn" data-value="Home"><span>🏠</span>Home</div>
                    <div class="emoji-btn" data-value="Work"><span>🏢</span>Work</div>
                    <div class="emoji-btn" data-value="Center"><span>🧘</span>Center</div>
                    <div class="emoji-btn" data-value="Outside"><span>🌿</span>Outside</div>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-sticky-note"></i> Notes:</label>
                <textarea id="editNotes" class="search-input" rows="3" placeholder="Add any notes about your session..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" id="cancelEditBtn"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Update Session</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <span class="close-modal" id="closeDeleteBtn">&times;</span>
        <div class="modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>Delete Session</h2>
        <p>Are you sure you want to delete this session? This action cannot be undone.</p>
        <div id="sessionInfo" class="log-info" style="margin: 20px 0;">
            <!-- Will be populated -->
        </div>
        <input type="hidden" id="deleteSessionId">
        <div class="modal-actions">
            <button class="btn-cancel" id="cancelDeleteBtn"><i class="fas fa-times"></i> Cancel</button>
            <button class="btn-delete-confirm" id="confirmDeleteBtn"><i class="fas fa-trash-alt"></i> Delete Permanently</button>
        </div>
    </div>
</div>

<!-- VIEW DETAILS MODAL -->
<div class="modal" id="viewModal">
    <div class="modal-content">
        <span class="close-modal" id="closeViewBtn">&times;</span>
        <h2><i class="fas fa-info-circle"></i> Session Details</h2>
        <div id="sessionDetails" class="session-details-view"></div>
        <div class="modal-actions">
            <button class="btn-cancel" id="closeViewDetailsBtn"><i class="fas fa-times"></i> Close</button>
        </div>
    </div>
</div>

<!-- TOAST NOTIFICATIONS -->
<div class="success-toast" id="successToast">
    <i class="fas fa-check-circle"></i> <span id="toastMessage">Operation completed successfully</span>
</div>

<div class="error-toast" id="errorToast">
    <i class="fas fa-exclamation-circle"></i> <span id="errorToastMessage">An error occurred</span>
</div>

<!-- FOOTER -->
<div class="footer">
    <p><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script src="../script/historysession.js"></script>
<script>
// Additional JavaScript for UI enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Emoji button selection
    const emojiGroups = document.querySelectorAll('.emoji-group');
    
    emojiGroups.forEach(group => {
        const btns = group.querySelectorAll('.emoji-btn');
        
        btns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons in this group
                btns.forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
            });
        });
    });
    
    // Custom session name input toggle
    const sessionSelect = document.getElementById('editSessionName');
    const customInput = document.getElementById('editCustomSession');
    
    if (sessionSelect && customInput) {
        sessionSelect.addEventListener('change', function() {
            if (this.value === 'Custom') {
                customInput.style.display = 'block';
            } else {
                customInput.style.display = 'none';
            }
        });
    }
});

// Toast notification function
function showToast(type, message) {
    const toast = document.getElementById(type === 'success' ? 'successToast' : 'errorToast');
    const messageSpan = document.getElementById(type === 'success' ? 'toastMessage' : 'errorToastMessage');
    
    if (messageSpan) {
        messageSpan.textContent = message;
    }
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>

</body>
</html>