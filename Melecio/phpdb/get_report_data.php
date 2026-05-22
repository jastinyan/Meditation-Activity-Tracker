<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

// Check authentication - allow both super_admin and admin
if (!isset($_SESSION['id_no']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_role = $_SESSION['role'];
$isSuperAdmin = ($user_role === 'super_admin');

// Get date parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Calculate previous period for trends
$days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
$previous_start = date('Y-m-d', strtotime($start_date . ' -' . $days_diff . ' days'));
$previous_end = date('Y-m-d', strtotime($start_date . ' -1 day'));

try {
    // Get KPIs for current period (only users)
    $kpis = getKPIs($conn, $start_date, $end_date);
    
    // Get KPIs for previous period (for trends)
    $previous_kpis = getKPIs($conn, $previous_start, $previous_end);
    
    // Calculate trends
    $trends = calculateTrends($kpis, $previous_kpis);
    
    // Get chart data
    $charts = getChartData($conn, $start_date, $end_date);
    
    // Get peak hours data
    $peakHours = getPeakHours($conn, $start_date, $end_date);
    
    // Get top users (only users, not admins)
    $topUsers = getTopUsers($conn, $start_date, $end_date);
    
    // Get user summary (only users, not admins)
    $userSummary = getUserSummary($conn, $start_date, $end_date);
    
    // Get recent sessions (only from users)
    $recentSessions = getRecentSessions($conn, $start_date, $end_date);
    
    echo json_encode([
        'success' => true,
        'kpis' => [
            'activeUsers' => $kpis['active_users'],
            'totalSessions' => $kpis['total_sessions'],
            'totalMinutes' => $kpis['total_minutes'],
            'avgSession' => $kpis['avg_session'],
            'userTrend' => $trends['user'],
            'sessionTrend' => $trends['session'],
            'minutesTrend' => $trends['minutes'],
            'avgTrend' => $trends['avg']
        ],
        'charts' => $charts,
        'peakHours' => $peakHours,
        'topUsers' => $topUsers,
        'userSummary' => $userSummary,
        'recentSessions' => $recentSessions
    ]);

} catch (Exception $e) {
    error_log("Report data error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error loading report data: ' . $e->getMessage()
    ]);
}

$conn->close();

// ==================== HELPER FUNCTIONS ====================

function getKPIs($conn, $start_date, $end_date) {
    // If dates are invalid, return zeros
    if (!$start_date || !$end_date) {
        return [
            'active_users' => 0,
            'total_sessions' => 0,
            'total_minutes' => 0,
            'avg_session' => 0
        ];
    }
    
    // Active users (users with sessions in period) - ONLY USERS
    $activeQuery = "SELECT COUNT(DISTINCT rs.id_no) as count 
                    FROM recorded_sessions rs
                    JOIN registeredacc ra ON rs.id_no = ra.id_no
                    WHERE ra.role = 'user'
                    AND DATE(rs.created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($activeQuery);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $active = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    // Total sessions - ONLY FROM USERS
    $sessionsQuery = "SELECT COUNT(*) as count 
                      FROM recorded_sessions rs
                      JOIN registeredacc ra ON rs.id_no = ra.id_no
                      WHERE ra.role = 'user'
                      AND DATE(rs.created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sessionsQuery);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    // Total minutes (convert from seconds) - ONLY FROM USERS
    $minutesQuery = "SELECT COALESCE(SUM(rs.time_spent), 0) as total 
                     FROM recorded_sessions rs
                     JOIN registeredacc ra ON rs.id_no = ra.id_no
                     WHERE ra.role = 'user'
                     AND DATE(rs.created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($minutesQuery);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $minutes = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Average session
    $avg = $sessions > 0 ? round($minutes / $sessions) : 0;

    return [
        'active_users' => (int)$active,
        'total_sessions' => (int)$sessions,
        'total_minutes' => (int)$minutes,
        'avg_session' => (int)$avg
    ];
}

function calculateTrends($current, $previous) {
    $trends = [];
    
    // User trend
    if ($previous['active_users'] > 0) {
        $trends['user'] = round((($current['active_users'] - $previous['active_users']) / $previous['active_users']) * 100);
    } else {
        $trends['user'] = $current['active_users'] > 0 ? 100 : 0;
    }
    
    // Session trend
    if ($previous['total_sessions'] > 0) {
        $trends['session'] = round((($current['total_sessions'] - $previous['total_sessions']) / $previous['total_sessions']) * 100);
    } else {
        $trends['session'] = $current['total_sessions'] > 0 ? 100 : 0;
    }
    
    // Minutes trend
    if ($previous['total_minutes'] > 0) {
        $trends['minutes'] = round((($current['total_minutes'] - $previous['total_minutes']) / $previous['total_minutes']) * 100);
    } else {
        $trends['minutes'] = $current['total_minutes'] > 0 ? 100 : 0;
    }
    
    // Average trend
    if ($previous['avg_session'] > 0) {
        $trends['avg'] = round((($current['avg_session'] - $previous['avg_session']) / $previous['avg_session']) * 100);
    } else {
        $trends['avg'] = $current['avg_session'] > 0 ? 100 : 0;
    }
    
    return $trends;
}

function getChartData($conn, $start_date, $end_date) {
    // If dates are invalid, return empty data
    if (!$start_date || !$end_date) {
        return [
            'activity' => [
                'labels' => ['No Data'],
                'sessions' => [0],
                'users' => [0]
            ],
            'distribution' => ['users' => 0, 'admins' => 0],
            'weekly' => [0,0,0,0,0,0,0],
            'retention' => [0,0,0,0,0,0]
        ];
    }
    
    // Get daily activity for the period (ONLY USERS)
    $activityQuery = "SELECT 
                        DATE(rs.created_at) as date,
                        COUNT(*) as sessions,
                        COUNT(DISTINCT rs.id_no) as users
                      FROM recorded_sessions rs
                      JOIN registeredacc ra ON rs.id_no = ra.id_no
                      WHERE ra.role = 'user'
                      AND DATE(rs.created_at) BETWEEN ? AND ?
                      GROUP BY DATE(rs.created_at)
                      ORDER BY date ASC";
    
    $stmt = $conn->prepare($activityQuery);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    $sessions = [];
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('M d', strtotime($row['date']));
        $sessions[] = (int)$row['sessions'];
        $users[] = (int)$row['users'];
    }
    $stmt->close();

    // If no data, provide sample data for demonstration
    if (empty($labels)) {
        $labels = ['No Data'];
        $sessions = [0];
        $users = [0];
    }

    // Get distribution by role
    $distQuery = "SELECT 
                    COUNT(CASE WHEN role = 'user' THEN 1 END) as users,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins
                  FROM registeredacc";
    $distResult = $conn->query($distQuery);
    $dist = $distResult->fetch_assoc();

    // Get weekly pattern (ONLY USERS)
    $weeklyQuery = "SELECT 
                      DAYOFWEEK(rs.created_at) as day,
                      COUNT(*) as count
                    FROM recorded_sessions rs
                    JOIN registeredacc ra ON rs.id_no = ra.id_no
                    WHERE ra.role = 'user'
                    AND DATE(rs.created_at) BETWEEN ? AND ?
                    GROUP BY DAYOFWEEK(rs.created_at)
                    ORDER BY day";
    
    $stmt = $conn->prepare($weeklyQuery);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $weeklyResult = $stmt->get_result();
    
    $weekly = [0,0,0,0,0,0,0];
    while ($row = $weeklyResult->fetch_assoc()) {
        // MySQL DAYOFWEEK: 1=Sunday, 2=Monday, ..., 7=Saturday
        $index = $row['day'] - 2; // Convert to 0=Monday, 6=Sunday
        if ($index < 0) $index = 6; // Sunday becomes index 6
        $weekly[$index] = (int)$row['count'];
    }
    $stmt->close();

    // Get retention data
    $retention = [100, 85, 70, 55, 40, 30]; // Sample data

    return [
        'activity' => [
            'labels' => $labels,
            'sessions' => $sessions,
            'users' => $users
        ],
        'distribution' => [
            'users' => (int)($dist['users'] ?? 0),
            'admins' => (int)($dist['admins'] ?? 0)
        ],
        'weekly' => $weekly,
        'retention' => $retention
    ];
}

function getPeakHours($conn, $start_date, $end_date) {
    // If dates are invalid, return zeros
    if (!$start_date || !$end_date) {
        return array_fill(0, 24, 0);
    }
    
    // ONLY FROM USERS
    $query = "SELECT 
                HOUR(rs.created_at) as hour,
                COUNT(*) as count
              FROM recorded_sessions rs
              JOIN registeredacc ra ON rs.id_no = ra.id_no
              WHERE ra.role = 'user'
              AND DATE(rs.created_at) BETWEEN ? AND ?
              GROUP BY HOUR(rs.created_at)
              ORDER BY hour";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $peakHours = array_fill(0, 24, 0);
    while ($row = $result->fetch_assoc()) {
        $peakHours[(int)$row['hour']] = (int)$row['count'];
    }
    $stmt->close();
    
    return $peakHours;
}

function getTopUsers($conn, $start_date, $end_date) {
    // If dates are invalid, return empty array
    if (!$start_date || !$end_date) {
        return [];
    }
    
    // ONLY USERS, NOT ADMINS
    $query = "SELECT 
                ra.username,
                COUNT(rs.id) as sessions,
                SUM(rs.time_spent) as total_time,
                ROUND(AVG(rs.time_spent)) as avg_time
              FROM recorded_sessions rs
              JOIN registeredacc ra ON rs.id_no = ra.id_no
              WHERE ra.role = 'user'
              AND DATE(rs.created_at) BETWEEN ? AND ?
              GROUP BY rs.id_no
              ORDER BY total_time DESC
              LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'username' => $row['username'],
            'sessions' => (int)$row['sessions'],
            'total_time' => (int)$row['total_time'],
            'avg_time' => (int)$row['avg_time']
        ];
    }
    $stmt->close();
    
    return $users;
}

function getUserSummary($conn, $start_date, $end_date) {
    // If dates are invalid, return empty array
    if (!$start_date || !$end_date) {
        return [];
    }
    
    // ONLY USERS, NOT ADMINS
    $query = "SELECT 
                ra.username,
                ra.role,
                COUNT(rs.id) as sessions,
                COALESCE(SUM(rs.time_spent), 0) as total_time,
                COALESCE(ROUND(AVG(rs.time_spent)), 0) as avg_time,
                MAX(rs.created_at) as last_active
              FROM registeredacc ra
              LEFT JOIN recorded_sessions rs ON ra.id_no = rs.id_no 
                  AND DATE(rs.created_at) BETWEEN ? AND ?
              WHERE ra.role = 'user'
              GROUP BY ra.id_no
              ORDER BY total_time DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $lastActive = $row['last_active'] ? date('M d, Y', strtotime($row['last_active'])) : 'Never';
        $users[] = [
            'username' => $row['username'],
            'role' => $row['role'],
            'sessions' => (int)$row['sessions'],
            'total_time' => (int)$row['total_time'],
            'avg_time' => (int)$row['avg_time'],
            'last_active' => $lastActive
        ];
    }
    $stmt->close();
    
    return $users;
}

function getRecentSessions($conn, $start_date, $end_date) {
    // If dates are invalid, return empty array
    if (!$start_date || !$end_date) {
        return [];
    }
    
    // ONLY FROM USERS
    $query = "SELECT 
                ra.username,
                rs.session_name,
                rs.time_spent,
                rs.feeling,
                rs.location,
                rs.created_at
              FROM recorded_sessions rs
              JOIN registeredacc ra ON rs.id_no = ra.id_no
              WHERE ra.role = 'user'
              AND DATE(rs.created_at) BETWEEN ? AND ?
              ORDER BY rs.created_at DESC
              LIMIT 50";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = [
            'username' => $row['username'],
            'session_name' => $row['session_name'],
            'time_spent' => (int)$row['time_spent'],
            'feeling' => $row['feeling'],
            'location' => $row['location'],
            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
    }
    $stmt->close();
    
    return $sessions;
}
?>