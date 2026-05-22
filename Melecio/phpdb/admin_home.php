<?php
session_start();
require 'db_connection.php';

/* Protect admin page */
if (!isset($_SESSION['id_no']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MAT - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin_home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="navbar-content">
        <p class="system-title"> Overview</p>
        <div class="admin-badge">
            <span class="role-admin"><i class="fas fa-shield-alt"></i> Administrator</span>
        </div>
    </div>
</div>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-user-shield"></i> Welcome, <?= htmlspecialchars($username) ?>
        </div>
        <h3><i class="fas fa-cog"></i> Management</h3>
            <a href="admin_home.php" class="active"><i class="fas fa-home"></i> Overview</a>
            <a href="reports.php"><i class="fas fa-chart-pie"></i> Analytics/Reports</a>
            <a href="manage_users_account.php"><i class="fas fa-users"></i> Manage Accounts</a>
            <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
            <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
            <a href="admin_system_logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="admin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
        <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Dashboard Content -->
    <main class="dashboard-main">
        <div class="content-wrapper">
            <div class="welcome-section">
                <h2><i class="fas fa-tachometer-alt"></i> System Overview</h2>
                <p class="date-display"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></p>
            </div>

            <!-- Quick Stats Row -->
            <div class="quick-stats">
                <div class="stat-badge">
                    <i class="fas fa-clock"></i>
                    <span id="current-time"><?= date('h:i A') ?></span>
                </div>
                <div class="stat-badge">
                    <i class="fas fa-database"></i>
                    <span id="total-entries">Loading...</span>
                </div>
            </div>

            <!-- DASHBOARD CARDS -->
            <div class="cards">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-content">
                        <h3>Total Users</h3>
                        <div class="card-value" id="total-users">
                            <span class="loading-spinner-small"></span>
                        </div>
                    
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-content">
                        <h3>Total Sessions</h3>
                        <div class="card-value" id="total-sessions">
                            <span class="loading-spinner-small"></span>
                        </div>
                        
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="card-content">
                        <h3>Total Minutes</h3>
                        <div class="card-value" id="total-minutes">
                            <span class="loading-spinner-small"></span>
                        </div>
                       
                    </div>
                </div>
            </div>

            <!-- Today's Activity Cards -->
            <div class="cards today-cards">
                <div class="card today-card">
                    <div class="card-icon small">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="card-content">
                        <h3>Today's Sessions</h3>
                        <div class="card-value" id="today-sessions">---</div>
                    </div>
                </div>
                
                <div class="card today-card">
                    <div class="card-icon small">
                        <i class="fas fa-hourglass-start"></i>
                    </div>
                    <div class="card-content">
                        <h3>Today's Minutes</h3>
                        <div class="card-value" id="today-minutes">---</div>
                    </div>
                </div>
                
                <div class="card today-card">
                    <div class="card-icon small">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="card-content">
                        <h3>Active Today</h3>
                        <div class="card-value" id="active-today">---</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="recent-activity">
                <h3><i class="fas fa-history"></i> Recent User Activity</h3>
                <div id="recentActivity" class="activity-list">
                    <div class="loading-message">
                        <i class="fas fa-spinner fa-spin"></i> Loading recent activity...
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script>
// Update current time every second
function updateTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('current-time').textContent = timeStr;
}
setInterval(updateTime, 1000);

function updateDashboard() {
    // Show loading states
    document.getElementById('total-users').innerHTML = '<span class="loading-spinner-small"></span>';
    document.getElementById('total-sessions').innerHTML = '<span class="loading-spinner-small"></span>';
    document.getElementById('total-minutes').innerHTML = '<span class="loading-spinner-small"></span>';
    
    fetch('get_dashboard_counts.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Animate main numbers
                animateValue('total-users', 0, data.users, 1000);
                animateValue('total-sessions', 0, data.sessions, 1000);
                animateValue('total-minutes', 0, data.minutes, 1000);
                
                // Update today's stats
                document.getElementById('today-sessions').textContent = data.today_sessions || 0;
                document.getElementById('today-minutes').textContent = data.today_minutes || 0;
                document.getElementById('active-today').textContent = data.active_today || 0;
                
                // Update total entries
                document.getElementById('total-entries').textContent = 
                    `Total Entries: ${data.sessions || 0}`;
            } else {
                showError('Failed to load dashboard data');
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
            showError('Connection error');
        });
}

function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    // Format large numbers with commas
    const formatNumber = (num) => {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    };
    
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    // Clear any existing animation
    if (element.interval) {
        clearInterval(element.interval);
    }
    
    element.interval = setInterval(() => {
        current += increment;
        if (current >= end) {
            element.textContent = formatNumber(end);
            clearInterval(element.interval);
            delete element.interval;
        } else {
            element.textContent = formatNumber(Math.round(current));
        }
    }, 16);
}

function loadRecentActivity() {
    const activityList = document.getElementById('recentActivity');
    
    fetch('get_recent_activity.php')
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                activityList.innerHTML = '';
                data.forEach(activity => {
                    const item = document.createElement('div');
                    item.className = 'activity-item';
                    
                   // Determine icon based on action
                    let icon = 'fa-circle';
                    let iconColor = '';

                    // Check the action_text directly from the activity object
                    if (activity.action === 'logged in' || activity.action?.includes('login')) {
                        icon = 'fa-sign-in-alt';
                        iconColor = 'success';
                    } else if (activity.action === 'logged out' || activity.action?.includes('logout')) {
                        icon = 'fa-sign-out-alt';
                        iconColor = 'danger';
                    } else if (activity.action === 'registered' || activity.action?.includes('register')) {
                        icon = 'fa-user-plus';
                        iconColor = 'warning';
}
                    item.innerHTML = `
                        <div class="activity-icon ${iconColor}">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="activity-details">
                            <span class="activity-text">${activity.text}</span>
                            <span class="activity-time">${activity.time}</span>
                        </div>
                    `;
                    activityList.appendChild(item);
                });
            } else {
                activityList.innerHTML = '<div class="no-activity"><i class="fas fa-inbox"></i> No recent activity</div>';
            }
        })
        .catch(error => {
            console.error('Error loading recent activity:', error);
            activityList.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Failed to load activity</div>';
        });
}

function showError(message) {
    const cards = ['total-users', 'total-sessions', 'total-minutes'];
    cards.forEach(id => {
        document.getElementById(id).innerHTML = `<span class="error-text">${message}</span>`;
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateDashboard();
    loadRecentActivity();
    
    // Refresh every 30 seconds
    setInterval(updateDashboard, 30000);
    setInterval(loadRecentActivity, 30000);
});

// Mobile sidebar toggle
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

// Add keyboard shortcut (Ctrl+Shift+R) to refresh dashboard
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'R') {
        e.preventDefault();
        updateDashboard();
        loadRecentActivity();
    }
});
</script>

</body>
</html>