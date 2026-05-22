<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    echo json_encode([]);
    exit;
}

$id_no = $_SESSION['id_no'];

if (isset($_GET['date'])) {
    $date = $_GET['date'];

    $stmt = $conn->prepare("SELECT * FROM meditation_sessions WHERE user_id_no=? AND session_date=? ORDER BY session_time ASC");
    $stmt->bind_param("ss", $id_no, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }

    echo json_encode($sessions);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM meditation_sessions WHERE user_id_no=?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();

$sessions = [];
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}

echo json_encode($sessions);
