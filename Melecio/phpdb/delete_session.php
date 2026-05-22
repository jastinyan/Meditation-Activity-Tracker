<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in."]);
    exit;
}

$id_no = $_SESSION['id_no'];

if (!isset($_GET['id'])) {
    echo json_encode(["status" => "error", "message" => "Missing ID."]);
    exit;
}

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM meditation_sessions WHERE id=? AND user_id_no=?");
$stmt->bind_param("is", $id, $id_no);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Session deleted successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to delete session."]);
}
