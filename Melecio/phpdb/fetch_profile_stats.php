<?php
session_start();
require 'db_connection.php';

header("Content-Type: application/json");

if (!isset($_SESSION['id_no'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$id_no = $_SESSION['id_no'];

// Get total sessions and total time
$stmt = $conn->prepare("SELECT COUNT(*) as total_sessions, SUM(time_spent) as total_time 
                        FROM recorded_sessions 
                        WHERE id_no = ?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

$totalSessions = $stats['total_sessions'] ?? 0;
$totalTime = $stats['total_time'] ?? 0;
$totalMinutes = round($totalTime / 60);

// Calculate current streak
$streakStmt = $conn->prepare("SELECT DISTINCT DATE(created_at) as session_date 
                              FROM recorded_sessions 
                              WHERE id_no = ? 
                              ORDER BY session_date DESC");
$streakStmt->bind_param("s", $id_no);
$streakStmt->execute();
$streakResult = $streakStmt->get_result();

$dates = [];
while ($row = $streakResult->fetch_assoc()) {
    $dates[] = $row['session_date'];
}

$currentStreak = 0;
if (!empty($dates)) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if (in_array($today, $dates)) {
        $currentStreak = 1;
        $checkDate = $yesterday;
        while (in_array($checkDate, $dates)) {
            $currentStreak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        }
    } elseif (in_array($yesterday, $dates)) {
        $currentStreak = 1;
        $checkDate = date('Y-m-d', strtotime('-2 days'));
        while (in_array($checkDate, $dates)) {
            $currentStreak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        }
    }
}

echo json_encode([
    "success" => true,
    "stats" => [
        "totalSessions" => $totalSessions,
        "totalMinutes" => $totalMinutes,
        "currentStreak" => $currentStreak
    ]
]);
?>