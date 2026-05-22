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
    // Get recent user activities (last 10 logins/logouts)
    $query = "SELECT 
                username, 
                action, 
                timestamp,
                CASE 
                    WHEN LOWER(action) = 'login' THEN 'logged in'
                    WHEN LOWER(action) = 'logout' THEN 'logged out'
                    WHEN LOWER(action) = 'register' THEN 'registered'
                    ELSE LOWER(action)
                END as action_text
              FROM system_logs 
              WHERE action IN ('LOGIN', 'LOGOUT', 'login', 'logout', 'register')
              ORDER BY timestamp DESC 
              LIMIT 10";
    
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
        
        $activities[] = [
            'username' => htmlspecialchars($row['username']),
            'action' => $row['action_text'],
            'time' => $timeAgo,
            'full_time' => date("M d, Y h:i:s A", $timestamp),
            'icon' => $icon,
            'color' => $color,
            'text' => htmlspecialchars($row['username']) . ' ' . $row['action_text']
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