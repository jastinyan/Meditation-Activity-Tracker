<?php
session_start();
require 'db_connection.php';

// Protect endpoint - must be logged in
if (!isset($_SESSION['id_no'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $role = $_SESSION['role'] ?? 'user';
    $response = [];

    // Basic counts that everyone can see
    $totalUsersQuery = $conn->query("SELECT COUNT(*) AS total FROM registeredacc WHERE role='user'");
    $totalUsers = $totalUsersQuery->fetch_assoc()['total'];
    
    // Session statistics from recorded_sessions
    $sessionsQuery = $conn->query("SELECT 
                                    COUNT(*) as total_sessions, 
                                    COALESCE(SUM(time_spent), 0) as total_seconds 
                                  FROM recorded_sessions");
    $sessionsData = $sessionsQuery->fetch_assoc();
    $totalSessions = $sessionsData['total_sessions'];
    $totalMinutes = round($sessionsData['total_seconds'] / 60);

    // Today's statistics
    $today = date('Y-m-d');
    $todayQuery = $conn->query("SELECT 
                                 COUNT(*) as today_sessions, 
                                 COALESCE(SUM(time_spent), 0) as today_seconds 
                               FROM recorded_sessions 
                               WHERE DATE(created_at) = '$today'");
    $todayData = $todayQuery->fetch_assoc();
    $todaySessions = $todayData['today_sessions'];
    $todayMinutes = round($todayData['today_seconds'] / 60);

    // Base response for all roles
    $response = [
        'users' => (int)$totalUsers,
        'sessions' => (int)$totalSessions,
        'minutes' => (int)$totalMinutes,
        'today_sessions' => (int)$todaySessions,
        'today_minutes' => (int)$todayMinutes,
        'role' => $role,
        'success' => true
    ];

    // Add admin/superadmin specific data
    if ($role === 'admin' || $role === 'super_admin') {
        // Total admins (excluding superadmin if needed)
        $adminQuery = $conn->query("SELECT COUNT(*) AS total FROM registeredacc WHERE role='admin'");
        $totalAdmins = $adminQuery->fetch_assoc()['total'];
        
        // Total accounts (all users)
        $totalAccountsQuery = $conn->query("SELECT COUNT(*) AS total FROM registeredacc");
        $totalAccounts = $totalAccountsQuery->fetch_assoc()['total'];
        
        // Active users today
        $activeTodayQuery = $conn->query("SELECT COUNT(DISTINCT id_no) as active 
                                         FROM system_logs 
                                         WHERE DATE(timestamp) = '$today' 
                                         AND action IN ('LOGIN', 'LOGOUT')");
        $activeToday = $activeTodayQuery->fetch_assoc()['active'];
        
        // Total log entries
        $logsQuery = $conn->query("SELECT COUNT(*) as total FROM system_logs");
        $totalLogs = $logsQuery->fetch_assoc()['total'];

        $response['admins'] = (int)$totalAdmins;
        $response['accounts'] = (int)$totalAccounts;
        $response['active_today'] = (int)$activeToday;
        $response['logs'] = (int)$totalLogs;
    }

    // Superadmin only data (if you need more granular control)
    if ($role === 'superadmin') {
        
        // Database size (if you have permissions)
        $dbSizeQuery = $conn->query("SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()");
        $dbSize = $dbSizeQuery->fetch_assoc()['size_mb'] ?? 0;
        
        $response['db_size'] = (float)$dbSize;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'users' => 0,
        'sessions' => 0,
        'minutes' => 0,
        'success' => false
    ]);
}

$conn->close();
?>