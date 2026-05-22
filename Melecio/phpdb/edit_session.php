<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in."]);
    exit;
}

$id_no = $_SESSION['id_no'];

$id = $_POST['id'];
$session_name = $_POST['session_name'];
$session_date = $_POST['session_date'];
$session_time = $_POST['session_time'];
$color = $_POST['color'];

$stmt = $conn->prepare("UPDATE meditation_sessions 
SET session_name=?, session_date=?, session_time=?, color=? 
WHERE id=? AND user_id_no=?");

$stmt->bind_param("ssssis", $session_name, $session_date, $session_time, $color, $id, $id_no);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Session updated successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update session."]);
}
