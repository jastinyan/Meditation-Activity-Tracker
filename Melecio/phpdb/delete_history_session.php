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

    if (!$session_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        exit;
    }

    // Verify the session belongs to the user and delete
    $query = "DELETE FROM recorded_sessions WHERE id = ? AND id_no = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $session_id, $id_no);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Session deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Session not found or access denied']);
    }
    
    $stmt->close();
}

$conn->close();
?>