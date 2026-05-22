<?php
session_start();
require 'db_connection.php';

// Protect endpoint - only admin can access
if (!isset($_SESSION['id_no']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get recent user activities ONLY with more details (exclude admins and super_admins)
    $query = "SELECT 
                sl.id,
                sl.id_no,
                sl.username, 
                sl.action, 
                sl.details,
                sl.ip_address,
                sl.user_agent,
                sl.timestamp,
                ra.role,
                CASE 
                    WHEN LOWER(sl.action) = 'login' THEN 'logged in'
                    WHEN LOWER(sl.action) = 'logout' THEN 'logged out'
                    WHEN LOWER(sl.action) = 'register' THEN 'registered'
                    ELSE LOWER(sl.action)
                END as action_text
              FROM system_logs sl
              LEFT JOIN registeredacc ra ON sl.id_no = ra.id_no
              WHERE sl.action IN ('LOGIN', 'LOGOUT', 'login', 'logout', 'register')
              AND (ra.role = 'user' OR ra.role IS NULL)
              ORDER BY sl.timestamp DESC 
              LIMIT 20";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format time nicely
        $timestamp = strtotime($row['timestamp']);
        $now = time();
        $diff = $now - $timestamp;
        
        // Time ago formatting
        if ($diff < 60) {
            $timeAgo = "just now";
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            $timeAgo = $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            $timeAgo = $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
        } else {
            $timeAgo = date("M d, h:i A", $timestamp);
        }
        
        // Determine icon and color based on action
        $action = strtolower($row['action']);
        $icon = 'fa-circle';
        $color = 'info';
        
        if ($action === 'login') {
            $icon = 'fa-sign-in-alt';
            $color = 'success';
        } elseif ($action === 'logout') {
            $icon = 'fa-sign-out-alt';
            $color = 'danger';
        } elseif ($action === 'register') {
            $icon = 'fa-user-plus';
            $color = 'warning';
        }
        
        // Parse user agent for browser info (simplified)
        $browser = 'Unknown';
        $ua = $row['user_agent'] ?? '';
        if (strpos($ua, 'Chrome') !== false) $browser = 'Chrome';
        elseif (strpos($ua, 'Firefox') !== false) $browser = 'Firefox';
        elseif (strpos($ua, 'Safari') !== false) $browser = 'Safari';
        elseif (strpos($ua, 'Edge') !== false) $browser = 'Edge';
        elseif (strpos($ua, 'MSIE') !== false) $browser = 'Internet Explorer';
        
        $activities[] = [
            'id' => $row['id'],
            'id_no' => $row['id_no'],
            'username' => htmlspecialchars($row['username']),
            'action' => $row['action_text'],
            'action_raw' => $row['action'],
            'details' => $row['details'],
            'ip_address' => $row['ip_address'] ?? '127.0.0.1',
            'browser' => $browser,
            'time' => $timeAgo,
            'full_time' => date("M d, Y h:i:s A", $timestamp),
            'timestamp' => $row['timestamp'],
            'icon' => $icon,
            'color' => $color,
            'text' => htmlspecialchars($row['username']) . ' ' . $row['action_text'],
            'role' => $row['role'] ?? 'user'
        ];
    }
    
    echo json_encode($activities);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'activities' => []
    ]);
}

$conn->close();
?>