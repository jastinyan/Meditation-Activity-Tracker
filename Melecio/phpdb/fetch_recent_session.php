<?php
session_start();
require 'db_connection.php';

header("Content-Type: application/json");

if (!isset($_SESSION['id_no'])) {
    echo json_encode([]);
    exit;
}

$id_no = $_SESSION['id_no'];

$stmt = $conn->prepare("SELECT session_name, time_spent, feeling, location, notes, created_at
                        FROM recorded_sessions
                        WHERE id_no = ?
                        ORDER BY created_at DESC
                        LIMIT 5");

$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();

$sessions = [];

while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}

echo json_encode($sessions);
