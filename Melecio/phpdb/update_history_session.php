<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id_no = $_SESSION['id_no'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    $session_name = isset($_POST['session_name']) ? trim($_POST['session_name']) : '';
    $time_spent = isset($_POST['time_spent']) ? (int)$_POST['time_spent'] : 0;
    $feeling = isset($_POST['feeling']) ? trim($_POST['feeling']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : '';

    if (!$session_id || !$session_name || !$time_spent || !$feeling || !$location || !$created_at) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Verify the session belongs to the user
    $checkQuery = "SELECT id FROM recorded_sessions WHERE id = ? AND id_no = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("is", $session_id, $id_no);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Session not found or access denied']);
        $checkStmt->close();
        $conn->close();
        exit;
    }
    $checkStmt->close();

    // Update the session
    $query = "UPDATE recorded_sessions 
              SET session_name = ?, time_spent = ?, feeling = ?, location = ?, notes = ?, created_at = ?
              WHERE id = ? AND id_no = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sississi", $session_name, $time_spent, $feeling, $location, $notes, $created_at, $session_id, $id_no);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Session updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update session']);
    }
    
    $stmt->close();
}

$conn->close();
?>