<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in."]);
    exit;
}

$id_no = $_SESSION['id_no'];

$session_name = $_POST['session_name'];
$session_date = $_POST['session_date'];
$session_time = $_POST['session_time'];
$color = $_POST['color'];

$stmt = $conn->prepare("INSERT INTO meditation_sessions (user_id_no, session_name, session_date, session_time, color)
VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("sssss", $id_no, $session_name, $session_date, $session_time, $color);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Session added successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to add session."]);
}
