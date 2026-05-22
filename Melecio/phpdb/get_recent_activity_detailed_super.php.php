<?php
session_start();
require 'db_connection.php';

// Protect endpoint - only super_admin can access
if (!isset($_SESSION['id_no']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get ALL recent activities (users, admins, and super_admins)
    $query = "SELECT 
                sl.username, 
                sl.action, 
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
              ORDER BY sl.timestamp DESC 
              LIMIT 15";
    
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
        
        // Determine icon and color based on action AND role
        $action = strtolower($row['action']);
        $role = $row['role'] ?? 'user';
        $icon = 'fa-circle';
        $color = 'info';
        
        if ($action === 'login') {
            $icon = 'fa-sign-in-alt';
            $color = ($role === 'admin' || $role === 'super_admin') ? 'purple' : 'success';
        } elseif ($action === 'logout') {
            $icon = 'fa-sign-out-alt';
            $color = ($role === 'admin' || $role === 'super_admin') ? 'purple' : 'danger';
        } elseif ($action === 'register') {
            $icon = 'fa-user-plus';
            $color = 'warning';
        }
        
        // Add role indicator to text for super_admin view
        $roleIndicator = '';
        if ($role === 'admin') {
            $roleIndicator = ' <span class="role-badge admin">[Admin]</span>';
        } elseif ($role === 'super_admin') {
            $roleIndicator = ' <span class="role-badge super-admin">[Super Admin]</span>';
        }
        
        $activities[] = [
            'username' => htmlspecialchars($row['username']),
            'action' => $row['action_text'],
            'time' => $timeAgo,
            'full_time' => date("M d, Y h:i:s A", $timestamp),
            'icon' => $icon,
            'color' => $color,
            'text' => htmlspecialchars($row['username']) . ' ' . $row['action_text'],
            'role' => $role,
            'role_indicator' => $roleIndicator
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