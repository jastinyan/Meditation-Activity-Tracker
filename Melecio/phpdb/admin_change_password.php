<?php
session_start();
require 'db_connection.php';

header("Content-Type: application/json");

// Check if user is super admin
if (!isset($_SESSION['id_no']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    exit;
}

$id_no = $data['id_no'] ?? '';
$new_password = $data['new_password'] ?? '';

// Validate input
if (empty($id_no) || empty($new_password)) {
    echo json_encode(["success" => false, "message" => "User ID and password are required"]);
    exit;
}

if (strlen($new_password) < 8) {
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters long"]);
    exit;
}

// Prevent changing own password? (Optional - you can enable this if you want)
// if ($id_no === $_SESSION['id_no']) {
//     echo json_encode(["success" => false, "message" => "Use your profile page to change your own password"]);
//     exit;
// }

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password in database
$stmt = $conn->prepare("UPDATE registeredacc SET password = ? WHERE id_no = ? AND approval_status = 'approved'");
$stmt->bind_param("ss", $hashed_password, $id_no);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Log the action (optional)
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'password_change', ?, ?)");
        $details = "Super admin changed password for user ID: " . $id_no;
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_stmt->bind_param("sss", $_SESSION['id_no'], $details, $ip);
        $log_stmt->execute();
        $log_stmt->close();
        
        echo json_encode(["success" => true, "message" => "Password changed successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found or not approved"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>