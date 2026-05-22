<?php
session_start();
require 'db_connection.php';

// Protect page - allow both super_admin and admin
if (!isset($_SESSION['id_no']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$isSuperAdmin = ($role === 'super_admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MAT - Analytics & Reports</title>
    <link rel="stylesheet" href="../css/superadmin_home.css">
    <link rel="stylesheet" href="../css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="navbar-content">
        <p class="system-title">Analytics and Reports</p>
        <div class="admin-badge">
            <?php if ($isSuperAdmin): ?>
            <span class="role-admin"><i class="fas fa-shield-alt"></i> Super Admin</span>
            <?php else: ?>
                <span class="role-admin"><i class="fas fa-shield-alt"></i> Admin</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dashboard-wrapper">

    <!-- SIDEBAR - Role-based navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-user-shield"></i> Welcome, <?= htmlspecialchars($username) ?>
        </div>
        <h3><i class="fas fa-cog"></i> Management</h3>
        
        <?php if ($isSuperAdmin): ?>
            <!-- Super Admin Sidebar -->
            <a href="superadmin_home.php"><i class="fas fa-home"></i> Overview</a>
            <a href="reports.php" class="active"><i class="fas fa-chart-pie"></i> Analytics/Reports</a>
            <a href="manage_account.php"><i class="fas fa-users-cog"></i> Manage Accounts</a>
            <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
            <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
            <a href="system_logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="superadmin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
        <?php else: ?>
            <!-- Admin Sidebar -->
            <a href="admin_home.php"><i class="fas fa-home"></i> Overview</a>
            <a href="reports.php" class="active"><i class="fas fa-chart-pie"></i> Analytics/Reports</a>
            <a href="manage_users_account.php"><i class="fas fa-users"></i> Manage Accounts</a>
            <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
            <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
            <a href="admin_system_logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="admin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
        <?php endif; ?>
        
        <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="reports-container">
            
            <!-- Date Range Selector -->
            <div class="date-range-card">
                <div class="date-range-header">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Report Period</h3>
                </div>
                <div class="date-range-controls">
                    <div class="quick-filters">
                        <button class="quick-filter-btn active" data-days="7">Last 7 Days</button>
                        <button class="quick-filter-btn" data-days="30">Last 30 Days</button>
                        <button class="quick-filter-btn" data-days="90">Last 90 Days</button>
                        <button class="quick-filter-btn" data-days="365">Last Year</button>
                        <button class="quick-filter-btn" data-days="all">All Time</button>
                    </div>
                    <div class="custom-range">
                        <div class="date-input">
                            <label>From:</label>
                            <input type="date" id="startDate" class="date-picker">
                        </div>
                        <div class="date-input">
                            <label>To:</label>
                            <input type="date" id="endDate" class="date-picker">
                        </div>
                        <button class="btn-apply" id="applyCustomRange">
                            <i class="fas fa-check"></i> Apply
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(255, 215, 0, 0.15);">
                        <i class="fas fa-users" style="color: #ffd700;"></i>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-label">Active Users</span>
                        <span class="kpi-value" id="activeUsers">0</span>
                        <span class="kpi-trend" id="userTrend">
                            <i class="fas fa-minus"></i> vs previous period
                        </span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(76, 175, 80, 0.15);">
                        <i class="fas fa-clock" style="color: #4CAF50;"></i>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-label">Total Sessions</span>
                        <span class="kpi-value" id="totalSessions">0</span>
                        <span class="kpi-trend" id="sessionTrend">
                            <i class="fas fa-minus"></i> vs previous period
                        </span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(33, 150, 243, 0.15);">
                        <i class="fas fa-hourglass-half" style="color: #2196F3;"></i>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-label">Total Minutes</span>
                        <span class="kpi-value" id="totalMinutes">0</span>
                        <span class="kpi-trend" id="minutesTrend">
                            <i class="fas fa-minus"></i> vs previous period
                        </span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(156, 39, 176, 0.15);">
                        <i class="fas fa-chart-line" style="color: #9C27B0;"></i>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-label">Avg Session</span>
                        <span class="kpi-value" id="avgSession">0</span>
                        <span class="kpi-trend" id="avgTrend">
                            <i class="fas fa-minus"></i> vs previous period
                        </span>
                    </div>
                </div>
            </div>

            <!-- Charts Grid --->
            <div class="charts-grid">
                <!-- Peak Hours Heatmap -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h4><i class="fas fa-clock"></i> Peak Activity Hours</h4>
                        <div class="chart-legend">
                            <span class="legend-item"><i class="fas fa-square" style="color: #ff6b6b;"></i> High</span>
                            <span class="legend-item"><i class="fas fa-square" style="color: #4ecdc4;"></i> Low</span>
                        </div>
                    </div>
                    <div class="heatmap-container" id="peakHoursHeatmap">
                        <!-- Heatmap will be generated here -->
                    </div>
                </div>

                <!-- Top Users -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h4><i class="fas fa-trophy"></i> Top Performing Users</h4>
                        <div class="chart-legend">
                            <span class="legend-item"><i class="fas fa-clock"></i> Total Minutes</span>
                        </div>
                    </div>
                    <div class="top-users-list" id="topUsersList">
                        <!-- Will be populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Data Tables Section -->
            <div class="tables-section">
                <!-- User Summary Table with Filters -->
                <div class="table-card">
                    <div class="table-header">
                        <h4><i class="fas fa-users"></i> User Summary</h4>
                        <div class="table-actions">
                            <div class="filter-wrapper">
                                
                                <select id="userStatusFilter" class="filter-select">
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <input type="text" id="userSearchFilter" class="filter-input" placeholder="Search users...">
                            </div>
                            <button class="btn-export" id="exportUserSummary">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="analytics-table" id="userSummaryTable">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Sessions</th>
                                    <th>Total Time</th>
                                    <th>Avg Time</th>
                                    <th>Last Active</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="userSummaryBody">
                                <tr>
                                    <td colspan="7" class="loading-message">
                                        <i class="fas fa-spinner fa-spin"></i> Loading data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

    <!-- Session Details Table -->
    <div class="table-card">
        <div class="table-header">
            <h4><i class="fas fa-history"></i> Recent Sessions</h4>
            <button class="btn-export" id="exportSessions">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
        <div class="table-responsive">
            <table class="analytics-table" id="sessionsTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Session Name</th>
                        <th>Duration</th>
                        <th>Date & Time</th>
                        <th>Feeling</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody id="sessionsTableBody">
                    <tr>
                        <td colspan="6" class="loading-message">
                            <i class="fas fa-spinner fa-spin"></i> Loading sessions...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

             
            <!-- Export Modal -->
            <div class="modal" id="exportModal">
                <div class="modal-content">
                    <span class="close-modal" id="closeExportModal">&times;</span>
                    <h2><i class="fas fa-file-export"></i> Export Report</h2>
                    
                    <div class="export-options">
                        <h4>Select Data to Export:</h4>
                        <label class="export-option">
                            <input type="checkbox" value="userSummary" checked> User Summary
                        </label>
                        <label class="export-option">
                            <input type="checkbox" value="sessions" checked> Session Details
                        </label>
                        <label class="export-option">
                            <input type="checkbox" value="charts"> Charts Data
                        </label>
                        
                        <h4 style="margin-top: 20px;">Format:</h4>
                        <div class="format-options">
                            <label class="format-option">
                                <input type="radio" name="exportFormat" value="csv" checked> CSV
                            </label>
                            <label class="format-option">
                                <input type="radio" name="exportFormat" value="excel"> Excel
                            </label>
                            <label class="format-option">
                                <input type="radio" name="exportFormat" value="pdf"> PDF
                            </label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button class="btn-cancel" id="cancelExport">Cancel</button>
                        <button class="btn-save" id="confirmExport">
                            <i class="fas fa-download"></i> Download Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Footer -->
<div class="footer">
    <p><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script src="../script/reports.js"></script>
</body>
</html>