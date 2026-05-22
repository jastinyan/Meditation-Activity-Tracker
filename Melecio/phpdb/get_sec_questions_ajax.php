<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

$id_no = $_POST['id_no'] ?? '';

if (!$id_no) {
    echo json_encode(["status" => "failed", "message" => "ID missing"]);
    exit;
}

$stmt = $conn->prepare("SELECT sec_q1, sec_q2, sec_q3 FROM registeredacc WHERE id_no=?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(["status" => "failed", "message" => "User not found"]);
    exit;
}

echo json_encode([
    "status" => "success",
    "questions" => [
        "sec_q1" => $row['sec_q1'],
        "sec_q2" => $row['sec_q2'],
        "sec_q3" => $row['sec_q3']
    ]
]);
