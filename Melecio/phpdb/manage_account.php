<?php
session_start();
require 'db_connection.php';

// Single authentication check - protect admin page
if (!isset($_SESSION['id_no']) || $_SESSION['role'] !== 'super_admin') {
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

// Update role (for all users including admins)
if (isset($_POST['update_role'])) {
    $id = $_POST['id'];
    $newRole = $_POST['role'];

    // Prevent changing own role from super_admin
    if ($id === $_SESSION['id_no'] && $newRole !== 'super_admin') {
        $message = "You cannot change your own super admin role";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("UPDATE registeredacc SET role=? WHERE id_no=? AND approval_status='approved'");
        $stmt->bind_param("ss", $newRole, $id);
        
        if ($stmt->execute()) {
            $message = "Role updated successfully for ID: " . htmlspecialchars($id);
            $messageType = "success";
        } else {
            $message = "Failed to update role";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Delete account (with protection for self-deletion)
if (isset($_POST['delete_account'])) {
    $id = $_POST['id'];

    // Prevent self-deletion
    if ($id === $_SESSION['id_no']) {
        $message = "You cannot delete your own account";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM registeredacc WHERE id_no=? AND approval_status='approved'");
        $stmt->bind_param("s", $id);
        
        if ($stmt->execute()) {
            $message = "Account deleted successfully";
            $messageType = "success";
        } else {
            $message = "Failed to delete account";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Block/Unblock account functionality with Super Admin switching logic
if (isset($_POST['toggle_block'])) {
    $id = $_POST['id'];
    $currentStatus = $_POST['current_status'];
    $newStatus = ($currentStatus == 'blocked') ? 'active' : 'blocked';
    
    // Check if this is a super admin being unblocked
    $check_role = $conn->prepare("SELECT role FROM registeredacc WHERE id_no = ?");
    $check_role->bind_param("s", $id);
    $check_role->execute();
    $role_result = $check_role->get_result();
    $user_role = $role_result->fetch_assoc()['role'];
    $check_role->close();
    
    // SPECIAL CASE: Unblocking a super admin
    if ($user_role === 'super_admin' && $newStatus === 'active') {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Block current super admin
            $block_current = $conn->prepare("UPDATE registeredacc SET status = 'blocked' WHERE id_no = ? AND role = 'super_admin' AND status = 'active'");
            $block_current->bind_param("s", $_SESSION['id_no']);
            
            if (!$block_current->execute()) {
                throw new Exception("Failed to block current super admin");
            }
            
            // Unblock the selected super admin
            $unblock_selected = $conn->prepare("UPDATE registeredacc SET status = 'active' WHERE id_no = ?");
            $unblock_selected->bind_param("s", $id);
            
            if (!$unblock_selected->execute()) {
                throw new Exception("Failed to unblock selected super admin");
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Set session message
            $message = "Super admin switched successfully. You have been blocked and will be logged out.";
            $messageType = "warning";
            
            // Log out current user
            session_destroy();
            
            // Redirect to login with message
            header("Location: login.php?message=super_admin_switched");
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "Error switching super admin: " . $e->getMessage();
            $messageType = "error";
        }
        
        $block_current->close();
        $unblock_selected->close();
        
    } else {
        // Regular block/unblock for non-super admin accounts
        $stmt = $conn->prepare("UPDATE registeredacc SET status=? WHERE id_no=? AND approval_status='approved'");
        $stmt->bind_param("ss", $newStatus, $id);
        
        if ($stmt->execute()) {
            $message = "Account " . (($newStatus == 'blocked') ? 'blocked' : 'unblocked') . " successfully";
            $messageType = "success";
        } else {
            $message = "Failed to update account status";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Get filter parameters
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Build the WHERE clause for filtering
$where_conditions = ["id_no != ?", "approval_status = 'approved'"];
$params = [$_SESSION['id_no']];
$types = "s";

// Add search condition
if (!empty($search)) {
    $where_conditions[] = "(id_no LIKE ? OR username LIKE ? OR email LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Add month/year filter
if (!empty($filter_month) && !empty($filter_year)) {
    $where_conditions[] = "MONTH(created_at) = ? AND YEAR(created_at) = ?";
    $params[] = $filter_month;
    $params[] = $filter_year;
    $types .= "ii";
} elseif (!empty($filter_month)) {
    $where_conditions[] = "MONTH(created_at) = ?";
    $params[] = $filter_month;
    $types .= "i";
} elseif (!empty($filter_year)) {
    $where_conditions[] = "YEAR(created_at) = ?";
    $params[] = $filter_year;
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total records count for pagination
$count_sql = "SELECT COUNT(*) as total FROM registeredacc WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);

// Bind parameters dynamically
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Get records for current page
$sql = "SELECT id_no, username, email, role, last_active, status, created_at 
        FROM registeredacc 
        WHERE $where_clause
        ORDER BY 
            CASE 
                WHEN role = 'super_admin' THEN 1
                WHEN role = 'admin' THEN 2
                WHEN role = 'user' THEN 3
                ELSE 4
            END, 
            username ASC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Add pagination parameters
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

// Bind all parameters
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Get available years for filter dropdown
$years_sql = "SELECT DISTINCT YEAR(created_at) as year FROM registeredacc WHERE approval_status='approved' ORDER BY year DESC";
$years_result = $conn->query($years_sql);
$available_years = [];
while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = $year_row['year'];
}

// Get counts for summary (only approved accounts)
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM registeredacc WHERE role='user' AND approval_status='approved'")->fetch_assoc()['count'];
$totalAdmins = $conn->query("SELECT COUNT(*) as count FROM registeredacc WHERE role='admin' AND approval_status='approved'")->fetch_assoc()['count'];
$totalSuperAdmins = $conn->query("SELECT COUNT(*) as count FROM registeredacc WHERE role='super_admin' AND approval_status='approved'")->fetch_assoc()['count'];
$activeToday = $conn->query("SELECT COUNT(*) as count FROM registeredacc WHERE approval_status='approved' AND last_active >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetch_assoc()['count'];

// Active Checker function (active if within last 5 minutes)
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MAT - Account Management</title>
    <link rel="stylesheet" href="../css/superadmin_home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
    
</head>
<body>

<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-notification"></div>

<!-- Navbar -->
<div class="navbar">
    <div class="navbar-content">
        <p class="system-title">Account Management</p>
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
        <a href="manage_account.php" class="active"><i class="fas fa-users-cog"></i> Manage Accounts</a>
        <a href="pending_approvals.php"><i class="fas fa-clock"></i> Approvals</a>
        <a href="admin_register.php"><i class="fas fa-user-plus"></i> Register User</a>
        <a href="system_logs.php"><i class="fas fa-history"></i> Logs</a>
        <a href="superadmin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
        <a href="homepage.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="dashboard-main">

        <div class="account-container">
            <div class="account-header">
                <h1><i class="fas fa-users-cog"></i> Account Management</h1>
            </div>

            <!-- Account Stats -->
            <div class="account-stats">
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
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content-small">
                        <h4>Total Admins</h4>
                        <div class="stat-number"><?= number_format($totalAdmins) ?></div>
                    </div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-icon-small">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content-small">
                        <h4>Super Admins</h4>
                        <div class="stat-number"><?= number_format($totalSuperAdmins) ?></div>
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
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" style="width: 100%; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <!-- Search Input -->
                    <div class="filter-group" style="flex: 2;">
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" 
                               name="search" 
                               class="filter-select" 
                               placeholder="Search by ID, username, or email..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <!-- Month Filter -->
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Month</label>
                        <select name="filter_month" class="filter-select">
                            <option value="">All Months</option>
                            <option value="1" <?= $filter_month == '1' ? 'selected' : '' ?>>January</option>
                            <option value="2" <?= $filter_month == '2' ? 'selected' : '' ?>>February</option>
                            <option value="3" <?= $filter_month == '3' ? 'selected' : '' ?>>March</option>
                            <option value="4" <?= $filter_month == '4' ? 'selected' : '' ?>>April</option>
                            <option value="5" <?= $filter_month == '5' ? 'selected' : '' ?>>May</option>
                            <option value="6" <?= $filter_month == '6' ? 'selected' : '' ?>>June</option>
                            <option value="7" <?= $filter_month == '7' ? 'selected' : '' ?>>July</option>
                            <option value="8" <?= $filter_month == '8' ? 'selected' : '' ?>>August</option>
                            <option value="9" <?= $filter_month == '9' ? 'selected' : '' ?>>September</option>
                            <option value="10" <?= $filter_month == '10' ? 'selected' : '' ?>>October</option>
                            <option value="11" <?= $filter_month == '11' ? 'selected' : '' ?>>November</option>
                            <option value="12" <?= $filter_month == '12' ? 'selected' : '' ?>>December</option>
                        </select>
                    </div>

                    <!-- Year Filter -->
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> Year</label>
                        <select name="filter_year" class="filter-select">
                            <option value="">All Years</option>
                            <?php foreach ($available_years as $year): ?>
                                <option value="<?= $year ?>" <?= $filter_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Actions -->
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="manage_account.php" class="btn-filter-reset">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Active Filters Display -->
            <?php if (!empty($search) || !empty($filter_month) || !empty($filter_year)): ?>
            <div class="active-filters">
                <span style="color: var(--text-muted);"><i class="fas fa-sliders-h"></i> Active Filters:</span>
                
                <?php if (!empty($search)): ?>
                <div class="filter-tag">
                    <i class="fas fa-search"></i> "<?= htmlspecialchars($search) ?>"
                    <a href="?<?= http_build_query(array_merge($_GET, ['search' => '', 'page' => 1])) ?>" class="remove-filter">
                        <i class="fas fa-times-circle"></i>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($filter_month)): ?>
                <div class="filter-tag">
                    <i class="fas fa-calendar-alt"></i> <?= date('F', mktime(0, 0, 0, $filter_month, 1)) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['filter_month' => '', 'page' => 1])) ?>" class="remove-filter">
                        <i class="fas fa-times-circle"></i>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($filter_year)): ?>
                <div class="filter-tag">
                    <i class="fas fa-calendar"></i> <?= $filter_year ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['filter_year' => '', 'page' => 1])) ?>" class="remove-filter">
                        <i class="fas fa-times-circle"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Message Alert -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="account-table-wrapper">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-id-card"></i> ID NO.</th>
                                <th><i class="fas fa-user"></i> USERNAME</th>
                                <th><i class="fas fa-envelope"></i> EMAIL</th>
                                <th><i class="fas fa-calendar-plus"></i> CREATED</th>
                                <th><i class="fas fa-circle"></i> STATUS</th>
                                <th><i class="fas fa-clock"></i> LAST ACTIVE</th>
                                <th><i class="fas fa-tag"></i> ROLE</th>
                                <th><i class="fas fa-edit"></i> UPDATE ROLE</th>
                                <th><i class="fas fa-eye"></i> ACTIONS</th>
                                <th><i class="fas fa-ban"></i> BLOCK/UNBLOCK</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <?php 
                                [$status, $lastActive] = getUserStatus($row['last_active']); 
                                $isBlocked = isset($row['status']) && $row['status'] == 'blocked';
                                $currentRole = $row['role'];
                                $created_date = isset($row['created_at']) ? date("M d, Y", strtotime($row['created_at'])) : 'N/A';
                            ?>
                            <tr>
                                <td><span class="id-badge"><?= htmlspecialchars($row['id_no']) ?></span></td>
                                <td><i class="fas fa-user-circle" style="color: var(--primary-light); margin-right: 5px;"></i><?= htmlspecialchars($row['username']) ?></td>
                                <td><i class="fas fa-envelope" style="color: var(--text-muted); margin-right: 5px;"></i><?= htmlspecialchars($row['email']) ?></td>
                                <td><i class="fas fa-calendar-alt" style="color: var(--text-muted); margin-right: 5px;"></i><?= $created_date ?></td>

                                <td>
                                    <?php if($isBlocked): ?>
                                        <span class="status-blocked">
                                            <i class="fas fa-ban"></i> Blocked
                                        </span>
                                    <?php elseif($status == "Active"): ?>
                                        <span class="status-active">
                                            <i class="fas fa-circle"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="status-inactive">
                                            <i class="fas fa-circle"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($lastActive == "Never"): ?>
                                        <span class="never-logged">
                                            <i class="far fa-clock"></i> Never
                                        </span>
                                    <?php else: ?>
                                        <span class="last-active-time">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= htmlspecialchars(date("M d, Y h:i A", strtotime($lastActive))) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if($row['role'] == "super_admin"): ?>
                                        <span class="role-super_admin"><i class="fas fa-shield-alt"></i> SUPER ADMIN</span>
                                    <?php elseif($row['role'] == "admin"): ?>
                                        <span class="role-admin"><i class="fas fa-shield-alt"></i> ADMIN</span>
                                    <?php else: ?>
                                        <span class="role-user"><i class="fas fa-user"></i> USER</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <form method="POST" class="role-form">
                                        <input type="hidden" name="id" value="<?= $row['id_no'] ?>">
                                        <select name="role" class="role-select <?= ($row['role'] == "super_admin") ? 'admin-selected' : (($row['role'] == "admin") ? 'admin-selected' : 'user-selected') ?>">
                                            <option value="user" <?= ($row['role'] == "user") ? "selected" : "" ?>>User</option>
                                            <option value="admin" <?= ($row['role'] == "admin") ? "selected" : "" ?>>Admin</option>
                                            <?php if($currentRole == 'admin' || $currentRole == 'super_admin'): ?>
                                                <option value="super_admin" <?= ($row['role'] == "super_admin") ? "selected" : "" ?>>Super Admin</option>
                                            <?php endif; ?>
                                        </select>
                                        <button type="submit" name="update_role" class="btn btn-small">
                                            <i class="fas fa-save"></i> Update
                                        </button>
                                    </form>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn-action btn-view" onclick="viewUser('<?= $row['id_no'] ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button type="button" class="btn-action btn-edit" onclick="editUser('<?= $row['id_no'] ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn-action btn-delete-small" onclick="showDeleteModal('<?= $row['id_no'] ?>', '<?= htmlspecialchars($row['username']) ?>')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $row['id_no'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $isBlocked ? 'blocked' : 'active' ?>">
                                        <?php if($isBlocked): ?>
                                            <?php if($row['role'] === 'super_admin'): ?>
                                                <div class="super-admin-action">
                                                    <button type="submit" name="toggle_block" class="btn-unblock-super" 
                                                        onclick="return confirmSuperAdminUnblock(event, '<?= htmlspecialchars($row['username']) ?>', '<?= htmlspecialchars($row['id_no']) ?>')">
                                                        <i class="fas fa-shield-alt"></i>
                                                        <span>Unblock Super Admin</span>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <button type="submit" name="toggle_block" class="btn-unblock">
                                                    <i class="fas fa-check-circle"></i> Unblock
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="submit" name="toggle_block" class="btn-block">
                                                <i class="fas fa-ban"></i> Block
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <!-- First Page -->
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="pagination-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>

                        <!-- Previous Page -->
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="pagination-item">1</a>';
                            if ($start_page > 2) {
                                echo '<span class="pagination-item disabled">...</span>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="pagination-item <?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor;

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="pagination-item disabled">...</span>';
                            }
                            echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '" class="pagination-item">' . $total_pages . '</a>';
                        }
                        ?>

                        <!-- Next Page -->
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>

                        <!-- Last Page -->
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="pagination-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </div>

                    <!-- Pagination Info -->
                    <div class="pagination-info">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> accounts
                        (Page <?= $page ?> of <?= $total_pages ?>)
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No accounts found</p>
                        <?php if (!empty($search) || !empty($filter_month) || !empty($filter_year)): ?>
                            <small>Try different filters or <a href="manage_account.php">clear all filters</a></small>
                        <?php else: ?>
                            <small>No accounts available to display</small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Account Summary -->
            <div class="account-summary">
                <span><i class="fas fa-database"></i> Total: <?= $total_records ?> account(s) | Showing <?= $result->num_rows ?> on this page</span>
                <?php if (!empty($search) || !empty($filter_month) || !empty($filter_year)): ?>
                    <a href="manage_account.php" class="clear-search">
                        <i class="fas fa-undo"></i> Clear all filters
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </main>

</div>

<!-- View User Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeViewModal()">&times;</span>
        
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2>User Profile</h2>
            <p>Complete user information</p>
        </div>
        
        <div id="viewUserInfo">
            <div class="loading-spinner" id="viewLoading"></div>
            <div id="viewContent" style="display: none;"></div>
        </div>
        
        <div class="modal-actions">
            <button class="btn-modal btn-cancel" onclick="closeViewModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
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
            <div class="loading-spinner" id="editLoading"></div>
            <div id="editContent" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content delete-modal">
        <span class="close-modal" onclick="closeDeleteModal()">&times;</span>
        
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete the account for <strong id="deleteUsername"></strong>?</p>
        <p class="warning-text">
            <i class="fas fa-exclamation-circle"></i>
            This action cannot be undone!
        </p>
        
        <form method="POST" id="deleteForm">
            <input type="hidden" name="id" id="deleteId">
            <div class="modal-actions">
                <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" name="delete_account" class="btn-modal btn-delete-confirm">
                    <i class="fas fa-trash-alt"></i> Delete Permanently
                </button>
            </div>
        </form>
    </div>
</div>

<div class="footer">
    <p><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

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
    
    // Auto remove after 5 seconds
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

// Add keyboard shortcut (Ctrl+/) to focus search
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === '/') {
        e.preventDefault();
        document.querySelector('.search-input').focus();
    }
});

// Highlight search term in results
document.addEventListener('DOMContentLoaded', function() {
    const searchTerm = '<?= htmlspecialchars($search) ?>';
    if (searchTerm && searchTerm.length > 0) {
        const cells = document.querySelectorAll('td:not(:has(select)):not(:has(button))');
        cells.forEach(cell => {
            const html = cell.innerHTML;
            const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            cell.innerHTML = html.replace(regex, '<span class="search-highlight">$1</span>');
        });
    }
});

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
    
    fetch('get_user_profile.php?id=' + id)
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
                                <div class="field-label">
                                    <i class="fas fa-id-card"></i>
                                    <span>ID Number</span>
                                </div>
                                <div class="field-value">${profile.id_no}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-user"></i>
                                    <span>Username</span>
                                </div>
                                <div class="field-value">${profile.username}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-envelope"></i>
                                    <span>Email</span>
                                </div>
                                <div class="field-value">${profile.email}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-tag"></i>
                                    <span>Role</span>
                                </div>
                                <div class="field-value">
                                    <span class="badge-role ${profile.role}">${profile.role.toUpperCase()}</span>
                                </div>
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
                                <div class="field-label">
                                    <i class="fas fa-signature"></i>
                                    <span>Full Name</span>
                                </div>
                                <div class="field-value">${profile.full_name}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-birthday-cake"></i>
                                    <span>Birthday</span>
                                </div>
                                <div class="field-value">${profile.birthday}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Age</span>
                                </div>
                                <div class="field-value">${profile.age} years old</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-venus-mars"></i>
                                    <span>Sex</span>
                                </div>
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
                                <div class="field-label">
                                    <i class="fas fa-home"></i>
                                    <span>Purok/Street</span>
                                </div>
                                <div class="field-value">${profile.purok_street}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-map-pin"></i>
                                    <span>Barangay</span>
                                </div>
                                <div class="field-value">${profile.barangay}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-city"></i>
                                    <span>City</span>
                                </div>
                                <div class="field-value">${profile.municipality_city}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-globe"></i>
                                    <span>Province</span>
                                </div>
                                <div class="field-value">${profile.province}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-globe-asia"></i>
                                    <span>Country</span>
                                </div>
                                <div class="field-value">${profile.country}</div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">
                                    <i class="fas fa-mail-bulk"></i>
                                    <span>Zip Code</span>
                                </div>
                                <div class="field-value">${profile.zip_code}</div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('viewContent').innerHTML = html;
            } else {
                document.getElementById('viewContent').innerHTML = '<p class="error-text">' + data.message + '</p>';
            }
        })
        .catch(error => {
            document.getElementById('viewLoading').style.display = 'none';
            document.getElementById('viewContent').style.display = 'block';
            document.getElementById('viewContent').innerHTML = '<p class="error-text">Error loading user data</p>';
            console.error('Error:', error);
        });
}

// Edit User Function
function editUser(id) {
    document.getElementById('editModal').style.display = 'flex';
    document.getElementById('editLoading').style.display = 'block';
    document.getElementById('editContent').style.display = 'none';
    
    fetch('get_user_profile.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editLoading').style.display = 'none';
            document.getElementById('editContent').style.display = 'block';
            
            if (data.success) {
                const profile = data.profile;
                let html = `
                    <form id="editUserForm" onsubmit="updateUser(event)">
                        <input type="hidden" name="id_no" value="${profile.id_no}">
                        
                        <!-- Password Change Section -->
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
                                    <label>
                                        <i class="fas fa-id-card"></i>
                                        ID Number
                                    </label>
                                    <input type="text" value="${profile.id_no}" readonly class="readonly-field">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-user"></i>
                                        Username
                                    </label>
                                    <input type="text" name="username" value="${profile.username}" required 
                                           pattern="[a-z][a-z0-9._]*" 
                                           title="Username must start with lowercase letter and contain only lowercase letters, numbers, dots, and underscores">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-envelope"></i>
                                        Email
                                    </label>
                                    <input type="email" name="email" value="${profile.email}" required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-tag"></i>
                                        Role
                                    </label>
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
                                    <label>
                                        <i class="fas fa-signature"></i>
                                        First Name
                                    </label>
                                    <input type="text" name="f_name" value="${profile.first_name}" required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-signature"></i>
                                        Middle Initial
                                    </label>
                                    <input type="text" name="m_initial" value="${profile.middle_initial || ''}" maxlength="1" 
                                           pattern="[A-Z]" title="Middle initial must be a single capital letter">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-signature"></i>
                                        Last Name
                                    </label>
                                    <input type="text" name="l_name" value="${profile.last_name}" required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-signature"></i>
                                        Extension
                                    </label>
                                    <input type="text" name="extension" value="${profile.extension_name || ''}" 
                                           placeholder="Jr, Sr, II, III, etc.">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-birthday-cake"></i>
                                        Birthday
                                    </label>
                                    <input type="date" name="birthday" value="${profile.birthday}" required onchange="calculateEditAge()">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-venus-mars"></i>
                                        Sex
                                    </label>
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
                                    <label>
                                        <i class="fas fa-home"></i>
                                        Purok/Street
                                    </label>
                                    <input type="text" name="purok" value="${profile.purok_street}" required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-map-pin"></i>
                                        Barangay
                                    </label>
                                    <input type="text" name="barangay" value="${profile.barangay}" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-city"></i>
                                        City/Municipality
                                    </label>
                                    <input type="text" name="city" value="${profile.municipality_city}" required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-globe"></i>
                                        Province
                                    </label>
                                    <input type="text" name="province" value="${profile.province}" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-globe-asia"></i>
                                        Country
                                    </label>
                                    <input type="text" name="country" value="${profile.country}" required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-mail-bulk"></i>
                                        Zip Code
                                    </label>
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
                document.getElementById('editContent').innerHTML = '<p class="error-text">' + data.message + '</p>';
            }
        })
        .catch(error => {
            document.getElementById('editLoading').style.display = 'none';
            document.getElementById('editContent').style.display = 'block';
            document.getElementById('editContent').innerHTML = '<p class="error-text">Error loading user data</p>';
            console.error('Error:', error);
        });
}

// Update User Function
function updateUser(event) {
    event.preventDefault();
    
    const form = document.getElementById('editUserForm');
    const newPassword = document.getElementById('edit_new_password')?.value || '';
    const confirmPassword = document.getElementById('edit_confirm_password')?.value || '';
    
    // Validate password if provided
    if (newPassword) {
        // Check length
        if (newPassword.length < 8 || newPassword.length >= 20) {
            showToast('Password must be between 8 and 20 characters', 'error');
            return;
        }
        
        // Check password strength
        if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
            showToast('Password must contain at least one uppercase letter, one lowercase letter, and one number', 'error');
            return;
        }
        
        // Check if passwords match
        if (newPassword !== confirmPassword) {
            showToast('Passwords do not match', 'error');
            return;
        }
    }
    
    // Validate age
    const ageField = document.getElementById('edit_age');
    if (ageField && ageField.value < 18) {
        showToast('User must be at least 18 years old', 'error');
        return;
    }
    
    // Validate zipcode
    const zipcode = form.querySelector('input[name="zipcode"]').value;
    if (!/^\d{4}$/.test(zipcode)) {
        showToast('Zipcode must be exactly 4 digits', 'error');
        return;
    }
    
    // Validate username format
    const username = form.querySelector('input[name="username"]').value;
    if (!/^[a-z][a-z0-9._]*$/.test(username)) {
        showToast('Username must start with lowercase letter and contain only lowercase letters, numbers, dots, and underscores', 'error');
        return;
    }
    
    if (username.length < 5 || username.length >= 20) {
        showToast('Username must be between 5 and 20 characters', 'error');
        return;
    }
    
    // Validate middle initial if provided
    const mInitial = form.querySelector('input[name="m_initial"]')?.value;
    if (mInitial && !/^[A-Z]$/.test(mInitial)) {
        showToast('Middle initial must be a single capital letter', 'error');
        return;
    }
    
    // Validate names don't contain numbers
    const fName = form.querySelector('input[name="f_name"]').value;
    const lName = form.querySelector('input[name="l_name"]').value;
    if (/\d/.test(fName) || /\d/.test(lName)) {
        showToast('Names must not contain numbers', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    // Show loading state
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
            showToast(data.message, 'success');
            closeEditModal();
            // Reload to show updated data
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Network error - please try again', 'error');
        console.error('Error:', error);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show Delete Confirmation Modal
function showDeleteModal(id, username) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteUsername').textContent = username;
    document.getElementById('deleteModal').style.display = 'flex';
}

// Close Delete Modal
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
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
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === viewModal) {
        closeViewModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}

// Stylish confirmation for unblocking super admin
function confirmSuperAdminUnblock(event, username, userId) {
    // Prevent default form submission
    event.preventDefault();
    
    // Store the form data for later use
    const form = event.target.closest('form');
    const formData = new FormData(form);
    
    // Create custom modal
    const modal = document.createElement('div');
    modal.className = 'super-confirm-modal';
    modal.setAttribute('data-userid', userId);
    modal.setAttribute('data-username', username);
    
    modal.innerHTML = `
        <div class="super-confirm-content">
            <div class="super-confirm-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>⚠️ CRITICAL ACTION ⚠️</h3>
            </div>
            <div class="super-confirm-body">
                <div class="warning-icon-container">
                    <i class="fas fa-shield-alt"></i>
                    <i class="fas fa-arrow-right"></i>
                    <i class="fas fa-user-lock"></i>
                </div>
                <p class="confirm-message">
                    You are about to unblock <strong>${username}</strong> (Super Admin)
                </p>
                <div class="consequence-box">
                    <div class="consequence-item">
                        <i class="fas fa-ban"></i>
                        <span>Your account will be BLOCKED immediately</span>
                    </div>
                    <div class="consequence-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>You will be LOGGED OUT</span>
                    </div>
                    <div class="consequence-item">
                        <i class="fas fa-user-check"></i>
                        <span>${username} will become the ACTIVE Super Admin</span>
                    </div>
                </div>
                <p class="confirm-question">Are you absolutely sure you want to proceed?</p>
            </div>
            <div class="super-confirm-footer">
                <button class="confirm-btn cancel-btn" onclick="closeSuperConfirm(this)">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="confirm-btn proceed-btn" onclick="proceedSuperAdminUnblock(this)">
                    <i class="fas fa-check"></i> Yes, Proceed
                </button>
            </div>
        </div>
    `;
    
    // Store the form data in the modal for later use
    modal.formData = formData;
    modal.formAction = form.action;
    modal.formMethod = form.method;
    
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
    return false;
}

function closeSuperConfirm(btn) {
    const modal = btn.closest('.super-confirm-modal');
    modal.classList.remove('show');
    setTimeout(() => modal.remove(), 300);
}

function proceedSuperAdminUnblock(btn) {
    const modal = btn.closest('.super-confirm-modal');
    const username = modal.getAttribute('data-username');
    
    // Show loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    // Create a new form to submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = ''; // Current page
    
    // Add the form fields from the stored formData
    if (modal.formData) {
        for (let [key, value] of modal.formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    }
    
    // Add the toggle_block field to ensure it's recognized
    const toggleInput = document.createElement('input');
    toggleInput.type = 'hidden';
    toggleInput.name = 'toggle_block';
    toggleInput.value = '1';
    form.appendChild(toggleInput);
    
    // Append form to body and submit
    document.body.appendChild(form);
    
    // Close modal
    modal.classList.remove('show');
    setTimeout(() => {
        modal.remove();
        // Submit the form
        form.submit();
    }, 300);
}

// Check for URL parameters on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('message') === 'super_admin_switched') {
        showToast('Super admin switched successfully. Please login with the new super admin account.', 'warning');
    }
});
</script>

</body>
</html>