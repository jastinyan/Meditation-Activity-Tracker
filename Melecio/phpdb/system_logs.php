<?php
session_start();
require 'db_connection.php';

// Role check (case-insensitive)
if (!isset($_SESSION['id_no']) || strtolower($_SESSION['role']) !== 'super_admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$message = '';
$messageType = '';

// Filters
$searchUser = trim($_GET['username'] ?? '');
$searchRole = trim($_GET['role'] ?? '');
$searchFrom = trim($_GET['date_from'] ?? '');
$searchTo   = trim($_GET['date_to'] ?? '');

// Whitelist role
$allowedRoles = ['user', 'admin', 'super_admin'];
if (!in_array($searchRole, $allowedRoles)) $searchRole = '';

// Pagination
// Pagination — 0 means "Show All"
$allowedLimits = [5, 10, 15, 20, 25, 0];
$limit = isset($_GET["limit"]) && in_array((int)$_GET["limit"], $allowedLimits)
    ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($limit === 0) ? 0 : ($page - 1) * $limit;

// Base query for counting - Group by user, date, and session to combine login/logout
$countSql = "SELECT COUNT(*) as total FROM (
    SELECT sl.id_no, sl.username, DATE(sl.timestamp) as log_date
    FROM system_logs sl
    LEFT JOIN registeredacc ra ON sl.id_no = ra.id_no
    WHERE 1=1";
$params = [];
$types = "";

// Username filter
if ($searchUser !== '') {
    $countSql .= " AND sl.username LIKE ?";
    $params[] = "%$searchUser%";
    $types .= "s";
}

// Role filter
if ($searchRole !== '') {
    $countSql .= " AND ra.role = ?";
    $params[] = $searchRole;
    $types .= "s";
}

// Date range filter
if ($searchFrom !== '') {
    $countSql .= " AND DATE(sl.timestamp) >= ?";
    $params[] = $searchFrom;
    $types .= "s";
}
if ($searchTo !== '') {
    $countSql .= " AND DATE(sl.timestamp) <= ?";
    $params[] = $searchTo;
    $types .= "s";
}

$countSql .= " GROUP BY sl.id_no, sl.username, DATE(sl.timestamp)
) as grouped_logs";

$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ($limit === 0) ? 1 : max(ceil($totalRows / $limit), 1);

// Main query - Group login and logout actions for each user per day
$dataSql = "SELECT 
    sl.id_no, 
    sl.username,
    COALESCE(ra.role, 'user') as user_role,
    DATE(sl.timestamp) as log_date,
    MAX(CASE WHEN sl.action = 'login' THEN sl.timestamp END) as login_time,
    MAX(CASE WHEN sl.action = 'logout' THEN sl.timestamp END) as logout_time,
    MAX(CASE WHEN sl.action = 'login' THEN sl.browser END) as login_browser,
    MAX(CASE WHEN sl.action = 'logout' THEN sl.browser END) as logout_browser,
    MAX(CASE WHEN sl.action = 'login' THEN sl.ip_address END) as login_ip,
    MAX(CASE WHEN sl.action = 'logout' THEN sl.ip_address END) as logout_ip
    FROM system_logs sl
    LEFT JOIN registeredacc ra ON sl.id_no = ra.id_no
    WHERE 1=1";

// Add filters to main query
if ($searchUser !== '') {
    $dataSql .= " AND sl.username LIKE ?";
}
if ($searchRole !== '') {
    $dataSql .= " AND ra.role = ?";
}
if ($searchFrom !== '') {
    $dataSql .= " AND DATE(sl.timestamp) >= ?";
}
if ($searchTo !== '') {
    $dataSql .= " AND DATE(sl.timestamp) <= ?";
}

$dataSql .= " GROUP BY sl.id_no, sl.username, DATE(sl.timestamp)
              ORDER BY log_date DESC, login_time DESC";

if ($limit !== 0) {
    $dataSql .= " LIMIT ? OFFSET ?";
}

$stmt = $conn->prepare($dataSql);

// Bind params for main query
$bindParams = [];
$bindTypes = "";

if ($searchUser !== '') {
    $bindParams[] = "%$searchUser%";
    $bindTypes .= "s";
}
if ($searchRole !== '') {
    $bindParams[] = $searchRole;
    $bindTypes .= "s";
}
if ($searchFrom !== '') {
    $bindParams[] = $searchFrom;
    $bindTypes .= "s";
}
if ($searchTo !== '') {
    $bindParams[] = $searchTo;
    $bindTypes .= "s";
}

// Add limit and offset only when not showing all
if ($limit !== 0) {
    $bindParams[] = $limit;
    $bindParams[] = $offset;
    $bindTypes .= "ii";
}

// Bind parameters
if (!empty($bindParams)) {
    $stmt->bind_param($bindTypes, ...$bindParams);
}

$stmt->execute();
$result = $stmt->get_result();

// Function to get browser icon and name
function getBrowserInfo($userAgent) {
    if (empty($userAgent)) return ['icon' => 'fa-question-circle', 'name' => 'Unknown', 'device' => 'fa-desktop'];
    
    $browserIcon = 'fa-globe';
    $browserSimple = 'Unknown';
    
    if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
        $browserIcon = 'fa-chrome';
        $browserSimple = 'Chrome';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $browserIcon = 'fa-firefox';
        $browserSimple = 'Firefox';
    } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
        $browserIcon = 'fa-safari';
        $browserSimple = 'Safari';
    } elseif (strpos($userAgent, 'Edg') !== false) {
        $browserIcon = 'fa-edge';
        $browserSimple = 'Edge';
    } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
        $browserIcon = 'fa-internet-explorer';
        $browserSimple = 'IE';
    } elseif (strpos($userAgent, 'OPR') !== false || strpos($userAgent, 'Opera') !== false) {
        $browserIcon = 'fa-opera';
        $browserSimple = 'Opera';
    }
    
    $isMobile = (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false);
    $deviceIcon = $isMobile ? 'fa-mobile-alt' : 'fa-desktop';
    
    return [
        'icon' => $browserIcon,
        'name' => $browserSimple,
        'device' => $deviceIcon,
        'isMobile' => $isMobile
    ];
}

// Function to format timestamp nicely
function formatTimestamp($timestamp) {
    if (empty($timestamp)) return '—';
    return date("M d, Y h:i:s A", strtotime($timestamp));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Logs - Super Admin</title>
    <link rel="stylesheet" href="../css/superadmin_home.css">
    <link rel="stylesheet" href="../css/system_logs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
</head>
<body>

<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-notification"></div>

<!-- Navbar -->
<div class="navbar">
    <div class="navbar-content">
        <p class="system-title">System Logs</p>
        <div class="admin-badge">
            <span class="role-super_admin"><i class="fas fa-shield-alt"></i> Super Admin</span>
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
        <a href="superadmin_home.php"><i class="fas fa-home"></i> Overview</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Analytics/Reports</a>
        <a href="manage_account.php"><i class="fas fa-users-cog"></i> Manage Accounts</a>
        <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
        <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
        <a href="system_logs.php" class="active"><i class="fas fa-history"></i> Logs</a>
        <a href="superadmin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
        <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="dashboard-main">
        <div class="logs-container">
            <h1><i class="fas fa-history"></i> System Activity Logs</h1>
            
            <!-- Success/Error Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Filter/Search Bar -->
            <form class="filter-bar" method="get" action="">
                <div class="filter-group">
                    <i class="fas fa-search"></i>
                    <input type="text" name="username" placeholder="Search by username..."
                           value="<?= htmlspecialchars($searchUser) ?>">
                </div>
                <div class="filter-group">
                    <i class="fas fa-user-tag"></i>
                    <select name="role" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="user"        <?= $searchRole === 'user'        ? 'selected' : '' ?>>User</option>
                        <option value="admin"       <?= $searchRole === 'admin'       ? 'selected' : '' ?>>Admin</option>
                        <option value="super_admin" <?= $searchRole === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </div>
                <div class="filter-group">
                    <i class="fas fa-calendar-day"></i>
                    <input type="date" name="date_from" placeholder="From"
                           value="<?= htmlspecialchars($searchFrom) ?>" title="Date From">
                </div>
                <div class="filter-group date-separator-group">
                    <span class="date-separator">→</span>
                    <input type="date" name="date_to" placeholder="To"
                           value="<?= htmlspecialchars($searchTo) ?>" title="Date To">
                </div>
                <input type="hidden" name="limit" value="<?= $limit ?>">
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
                <a href="system_logs.php" class="btn-filter-reset">
                    <i class="fas fa-times"></i> Reset
                </a>
            </form>

            <!-- Logs Table -->
            <div class="logs-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i> ID Number</th>
                            <th><i class="fas fa-user"></i> Username</th>
                            <th><i class="fas fa-tag"></i> Role</th>
                            <th><i class="fas fa-exchange-alt"></i> Activity</th>
                            <th><i class="fas fa-calendar-alt"></i> Date</th>
                            <th><i class="fas fa-clock"></i> Login Time</th>
                            <th><i class="fas fa-clock"></i> Logout Time</th>
                            <th><i class="fas fa-globe"></i> Browser / IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $hasLogin = !empty($row['login_time']);
                                $hasLogout = !empty($row['logout_time']);
                                $userRole = strtolower($row['user_role'] ?? 'user');
                                $loginBrowserInfo = getBrowserInfo($row['login_browser'] ?? '');
                                $logoutBrowserInfo = getBrowserInfo($row['logout_browser'] ?? '');
                            ?>
                            <tr>
                                <td data-label="ID Number">
                                    <span class="user-id"><?= htmlspecialchars($row['id_no'] ?? 'N/A') ?></span>
                                </td>
                                <td data-label="Username">
                                    <strong><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></strong>
                                </td>
                                <td data-label="Role">
                                    <span class="role-badge <?= $userRole ?>">
                                        <i class="fas <?= $userRole === 'super_admin' ? 'fa-crown' : ($userRole === 'admin' ? 'fa-shield-alt' : 'fa-user') ?>"></i>
                                        <?= ucfirst(str_replace('_', ' ', $userRole)) ?>
                                    </span>
                                </td>
                                <td data-label="Activity">
                                    <div class="activity-group">
                                        <?php if ($hasLogin): ?>
                                            <span class="action-badge action-login">
                                                <i class="fas fa-sign-in-alt"></i> Login
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($hasLogout): ?>
                                            <span class="action-badge action-logout">
                                                <i class="fas fa-sign-out-alt"></i> Logout
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!$hasLogin && !$hasLogout): ?>
                                            <span class="action-badge">
                                                <i class="fas fa-circle"></i> No Activity
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Date">
                                    <div class="log-date">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= date("M d, Y", strtotime($row['log_date'])) ?>
                                    </div>
                                </td>
                                <td data-label="Login Time">
                                    <?php if ($hasLogin): ?>
                                        <div class="login-time">
                                            <i class="fas fa-sign-in-alt" style="color: #2ecc71;"></i>
                                            <?= date("h:i:s A", strtotime($row['login_time'])) ?>
                                            <?php if (!empty($row['login_ip'])): ?>
                                                <small class="ip-small">(<?= htmlspecialchars($row['login_ip']) ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Logout Time">
                                    <?php if ($hasLogout): ?>
                                        <div class="logout-time">
                                            <i class="fas fa-sign-out-alt" style="color: #e74c3c;"></i>
                                            <?= date("h:i:s A", strtotime($row['logout_time'])) ?>
                                            <?php if (!empty($row['logout_ip'])): ?>
                                                <small class="ip-small">(<?= htmlspecialchars($row['logout_ip']) ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Browser / IP">
                                    <div class="browser-ip-group">
                                        <?php if ($hasLogin): ?>
                                            <div class="browser-info login-browser">
                                                <i class="fab <?= $loginBrowserInfo['icon'] ?>"></i>
                                                <span><?= htmlspecialchars($loginBrowserInfo['name']) ?></span>
                                                <i class="fas <?= $loginBrowserInfo['device'] ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($hasLogout && !empty($row['logout_browser'])): ?>
                                            <div class="browser-info logout-browser">
                                                <i class="fab <?= $logoutBrowserInfo['icon'] ?>"></i>
                                                <span><?= htmlspecialchars($logoutBrowserInfo['name']) ?></span>
                                                <i class="fas <?= $logoutBrowserInfo['device'] ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <p>No logs found</p>
                                    <small>Try adjusting your filters or check back later</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination + Show Entries Row -->
                <div class="pagination-row">
                    <!-- Left: Show Entries dropdown -->
                    <div class="show-entries-inline">
                        <label><i class="fas fa-list-ol"></i> Show:</label>
                        <select class="entries-select" onchange="window.location.href=this.value">
                            <?php foreach ([5, 10, 15, 20, 25, 0] as $opt): 
                                $url = '?username=' . urlencode($searchUser) . '&role=' . urlencode($searchRole) . '&date_from=' . urlencode($searchFrom) . '&date_to=' . urlencode($searchTo) . '&limit=' . $opt . '&page=1';
                            ?>
                                <option value="<?= $url ?>" <?= $limit === $opt ? 'selected' : '' ?>>
                                    <?= $opt === 0 ? 'Show All' : $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Right: Page numbers -->
                    <?php if ($limit !== 0 && $totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&username=<?= urlencode($searchUser) ?>&role=<?= urlencode($searchRole) ?>&date_from=<?= urlencode($searchFrom) ?>&date_to=<?= urlencode($searchTo) ?>&limit=<?= $limit ?>" class="pagination-item">
                                <i class="fas fa-chevron-left"></i> Prev
                            </a>
                        <?php endif; ?>

                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1) {
                            echo '<a href="?page=1&username=' . urlencode($searchUser) . '&role=' . urlencode($searchRole) . '&date_from=' . urlencode($searchFrom) . '&date_to=' . urlencode($searchTo) . '&limit=' . $limit . '" class="pagination-item">1</a>';
                            if ($startPage > 2) {
                                echo '<span class="pagination-item disabled">...</span>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?= $i ?>&username=<?= urlencode($searchUser) ?>&role=<?= urlencode($searchRole) ?>&date_from=<?= urlencode($searchFrom) ?>&date_to=<?= urlencode($searchTo) ?>&limit=<?= $limit ?>" 
                               class="pagination-item <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor;
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span class="pagination-item disabled">...</span>';
                            }
                            echo '<a href="?page=' . $totalPages . '&username=' . urlencode($searchUser) . '&role=' . urlencode($searchRole) . '&date_from=' . urlencode($searchFrom) . '&date_to=' . urlencode($searchTo) . '&limit=' . $limit . '" class="pagination-item">' . $totalPages . '</a>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&username=<?= urlencode($searchUser) ?>&role=<?= urlencode($searchRole) ?>&date_from=<?= urlencode($searchFrom) ?>&date_to=<?= urlencode($searchTo) ?>&limit=<?= $limit ?>" class="pagination-item">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Log Stats -->
            <div class="log-stats">
                <div>
                    <i class="fas fa-database"></i>
                    <span>Total User Sessions: <strong><?= number_format($totalRows) ?></strong></span>
                </div>
                <div>
                    <i class="fas fa-file-alt"></i>
                    <span>Page <?= $page ?> of <?= $totalPages ?></span>
                </div>
                <?php if ($result && $result->num_rows > 0): ?>
                    <div>
                        <i class="fas fa-chart-line"></i>
                        <span>Displaying <?= $result->num_rows ?> sessions</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

</div>

<div class="footer">
    <p><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<style>
/* ── Date separator group ── */
.date-separator-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.date-separator {
    color: var(--text-muted);
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 2px;
}
.date-separator-group input[type="date"] {
    flex: 1;
    width: 100%;
    padding: 10px 12px;
    border: 2px solid var(--glass-border);
    border-radius: 10px;
    font-size: 14px;
    color: var(--text-primary);
    background: rgba(0, 0, 0, 0.5);
    transition: var(--transition);
    color-scheme: dark;
}
.date-separator-group input[type="date"]:focus {
    outline: none;
    border-color: var(--primary);
    background: rgba(0, 0, 0, 0.7);
    box-shadow: 0 0 0 3px var(--primary-glow);
}

/* ── Pagination + Show Entries row ── */
.pagination-row {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 24px;
    margin-bottom: 10px;
}

/* Left side: show entries dropdown */
.show-entries-inline {
    display: flex;
    align-items: center;
    gap: 10px;
}
.show-entries-inline label {
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
}
.show-entries-inline label i {
    margin-right: 5px;
    color: var(--primary-light);
}
.entries-select {
    padding: 7px 32px 7px 12px;
    border: 2px solid var(--glass-border);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-primary);
    background: rgba(0, 0, 0, 0.5);
    cursor: pointer;
    transition: var(--transition);
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ff6600' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}
.entries-select:hover,
.entries-select:focus {
    outline: none;
    border-color: var(--primary);
    background-color: rgba(0, 0, 0, 0.7);
    box-shadow: 0 0 0 3px var(--primary-glow);
}
.entries-select option {
    background: var(--dark-bg);
    color: var(--text-primary);
}

/* Right side: pagination stays the same, just remove its own margin */
.pagination-row .pagination {
    margin-top: 0;
    margin-bottom: 0;
}
</style>

<script>
// Toast notification function
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast_' + Date.now();
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.id = toastId;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <div class="toast-content">
            <div class="toast-title">${type === 'success' ? 'Success' : 'Error'}</div>
            <div class="toast-message">${message}</div>
        </div>
        <div class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        const toastElement = document.getElementById(toastId);
        if (toastElement) {
            toastElement.style.transition = 'opacity 0.5s';
            toastElement.style.opacity = '0';
            setTimeout(() => toastElement.remove(), 500);
        }
    }, 5000);
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
</script>

</body>
</html>