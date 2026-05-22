<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id_no = $_SESSION['id_no'];
$session_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

$query = "SELECT id, session_name, time_spent, feeling, location, notes, created_at 
          FROM recorded_sessions 
          WHERE id = ? AND id_no = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $session_id, $id_no);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'session' => [
            'id' => $row['id'],
            'session_name' => $row['session_name'],
            'time_spent' => (int)$row['time_spent'],
            'feeling' => $row['feeling'],
            'location' => $row['location'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Session not found']);
}

$stmt->close();
$conn->close();
?>