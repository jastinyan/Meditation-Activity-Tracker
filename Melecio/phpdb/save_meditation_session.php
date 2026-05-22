<?php
session_start();
require 'db_connection.php';

header("Content-Type: application/json");

if (!isset($_SESSION['id_no'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$id_no = $_SESSION['id_no'];

$session_name = $_POST['session_name'] ?? '';
$time_spent = $_POST['time_spent'] ?? 0;
$feeling = $_POST['feeling'] ?? '';
$location = $_POST['location'] ?? '';
$notes = $_POST['notes'] ?? '';

if (empty($session_name) || empty($feeling) || empty($location) || $time_spent <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO recorded_sessions (id_no, session_name, time_spent, feeling, location, notes)
                        VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisss", $id_no, $session_name, $time_spent, $feeling, $location, $notes);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Session saved successfully!",
        "datetime" => date("Y-m-d h:i A")
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
