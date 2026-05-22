<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

$id_no = $_POST['id_no'] ?? '';
if (!$id_no) {
    echo json_encode(["status" => "failed", "errors" => ["Unauthorized access"], "attempts_left" => 0]);
    exit;
}

// Initialize attempt tracking
if (!isset($_SESSION['sec_attempts'][$id_no])) {
    $_SESSION['sec_attempts'][$id_no] = ['count' => 0, 'locked_until' => null];
}
$attemptData = &$_SESSION['sec_attempts'][$id_no];
$now = time();

// Check lock
if ($attemptData['locked_until'] && $now < $attemptData['locked_until']) {
    $remaining = ceil(($attemptData['locked_until'] - $now) / 60);
    echo json_encode([
        "status" => "locked",
        "message" => "Too many attempts. Try again in {$remaining} minutes.",
        "locked_for" => $attemptData['locked_until'] - $now
    ]);
    exit;
}

// Fetch answers
$stmt = $conn->prepare("SELECT sec_q1, sec_q2, sec_q3, sec_a1, sec_a2, sec_a3 FROM registeredacc WHERE id_no=?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(["status" => "failed", "errors" => ["User not found"], "attempts_left" => 3]);
    exit;
}

// Validate answers
$errors = [];
if (strcasecmp(trim($_POST['sec_a1'] ?? ''), $row['sec_a1']) !== 0) $errors[] = "Incorrect answer for: {$row['sec_q1']}";
if (strcasecmp(trim($_POST['sec_a2'] ?? ''), $row['sec_a2']) !== 0) $errors[] = "Incorrect answer for: {$row['sec_q2']}";
if (strcasecmp(trim($_POST['sec_a3'] ?? ''), $row['sec_a3']) !== 0) $errors[] = "Incorrect answer for: {$row['sec_q3']}";

// Success
if (empty($errors)) {
    $_SESSION['security_verified'] = true;
    unset($_SESSION['sec_attempts'][$id_no]);
    echo json_encode(["status" => "success", "message" => "Security questions verified successfully."]);
    exit;
}

// Failed attempt
$attemptData['count']++;


// Redirect after 6 attempts first
if ($attemptData['count'] >= 6) {
    unset($_SESSION['sec_attempts'][$id_no]);
    echo json_encode([
        "status" => "redirect",
        "message" => "Too many failed attempts. Redirecting to OTP."
    ]);
    exit;
}

// Lock after 3 attempts (only if less than 6)
if ($attemptData['count'] % 3 === 0) {
    $attemptData['locked_until'] = $now + 120; // 2 minutes
    echo json_encode([
        "status" => "locked",
        "message" => "Too many attempts. Try again in 2 minutes.",
        "locked_for" => 120
    ]);
    exit;
}


// Remaining attempts
$attempts_left = 3 - ($attemptData['count'] % 3);
if ($attempts_left === 0) $attempts_left = 3;

echo json_encode([
    "status" => "failed",
    "errors" => $errors,
    "attempts_left" => $attempts_left
]);
