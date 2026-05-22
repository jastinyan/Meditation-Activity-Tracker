<?php
session_start();
require 'db_connection.php';

// Role check
if (!isset($_SESSION['id_no']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Filters
$searchUser = trim($_GET['username'] ?? '');
$searchDate = trim($_GET['date'] ?? '');

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Base query for counting - Group by user and date to combine login/logout
$countSql = "SELECT COUNT(*) as total FROM (
    SELECT sl.id_no, sl.username, DATE(sl.timestamp) as log_date
    FROM system_logs sl
    WHERE 1=1";
$params = [];
$types = "";

// Username filter
if ($searchUser !== '') {
    $countSql .= " AND sl.username LIKE ?";
    $params[] = "%$searchUser%";
    $types .= "s";
}

// Date filter
if ($searchDate !== '') {
    $countSql .= " AND DATE(sl.timestamp) = ?";
    $params[] = $searchDate;
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

$totalPages = max(ceil($totalRows / $limit), 1);

// Main query - Group login and logout actions for each user per day
$dataSql = "SELECT 
    sl.id_no, 
    sl.username,
    DATE(sl.timestamp) as log_date,
    MAX(CASE WHEN sl.action = 'login' THEN sl.timestamp END) as login_time,
    MAX(CASE WHEN sl.action = 'logout' THEN sl.timestamp END) as logout_time,
    MAX(CASE WHEN sl.action = 'login' THEN sl.browser END) as login_browser,
    MAX(CASE WHEN sl.action = 'logout' THEN sl.browser END) as logout_browser,
    MAX(CASE WHEN sl.action = 'login' THEN sl.ip_address END) as login_ip,
    MAX(CASE WHEN sl.action = 'logout' THEN sl.ip_address END) as logout_ip
    FROM system_logs sl
    WHERE 1=1";

// Add filters to main query
if ($searchUser !== '') {
    $dataSql .= " AND sl.username LIKE ?";
}
if ($searchDate !== '') {
    $dataSql .= " AND DATE(sl.timestamp) = ?";
}

$dataSql .= " GROUP BY sl.id_no, sl.username, DATE(sl.timestamp)
              ORDER BY log_date DESC, login_time DESC
              LIMIT ? OFFSET ?";

$stmt = $conn->prepare($dataSql);

// Bind params for main query
$bindParams = [];
$bindTypes = "";

if ($searchUser !== '') {
    $bindParams[] = "%$searchUser%";
    $bindTypes .= "s";
}
if ($searchDate !== '') {
    $bindParams[] = $searchDate;
    $bindTypes .= "s";
}

// Add limit and offset
$bindParams[] = $limit;
$bindParams[] = $offset;
$bindTypes .= "ii";

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MAT - System Logs</title>
    <link rel="stylesheet" href="../css/admin_home.css">
    <link rel="stylesheet" href="../css/admin_logs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="navbar-content">
        <p class="system-title">System Logs</p>
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
        <a href="admin_home.php"><i class="fas fa-home"></i> Overview</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Analytics/Reports</a>
        <a href="manage_users_account.php"><i class="fas fa-users"></i> Manage Accounts</a>
        <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
        <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
        <a href="admin_system_logs.php" class="active"><i class="fas fa-history"></i> Logs</a>
        <a href="admin_profile.php"><i class="fas fa-user-cog"></i>Profile</a>
        <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="dashboard-main">
        <div class="logs-container">
            <h1><i class="fas fa-history"></i> User Activity Logs</h1>

            <!-- Filter/Search Bar -->
            <form class="filter-bar" method="get" action="">
                <div class="filter-group">
                    <i class="fas fa-search"></i>
                    <input type="text" name="username" placeholder="Search by username..." 
                           value="<?= htmlspecialchars($searchUser) ?>">
                </div>
                <div class="filter-group">
                    <i class="fas fa-calendar"></i>
                    <input type="date" name="date" value="<?= htmlspecialchars($searchDate) ?>">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
                <a href="admin_system_logs.php" class="btn-reset">
                    <i class="fas fa-times"></i> Reset
                </a>
            </form>

            <!-- Table with grouped login/logout -->
            <div class="logs-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i> ID Number</th>
                            <th><i class="fas fa-user"></i> Username</th>
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
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <p>No logs found</p>
                                    <small>Try adjusting your filters or check back later</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&username=<?= urlencode($searchUser) ?>&date=<?= urlencode($searchDate) ?>" class="pagination-item">
                                <i class="fas fa-chevron-left"></i> Prev
                            </a>
                        <?php endif; ?>

                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1) {
                            echo '<a href="?page=1&username=' . urlencode($searchUser) . '&date=' . urlencode($searchDate) . '" class="pagination-item">1</a>';
                            if ($startPage > 2) {
                                echo '<span class="pagination-item disabled">...</span>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?= $i ?>&username=<?= urlencode($searchUser) ?>&date=<?= urlencode($searchDate) ?>" 
                               class="pagination-item <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor;
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span class="pagination-item disabled">...</span>';
                            }
                            echo '<a href="?page=' . $totalPages . '&username=' . urlencode($searchUser) . '&date=' . urlencode($searchDate) . '" class="pagination-item">' . $totalPages . '</a>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&username=<?= urlencode($searchUser) ?>&date=<?= urlencode($searchDate) ?>" class="pagination-item">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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

<!-- Footer -->
<div class="footer">
    <p><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script>
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>

</body>
</html>