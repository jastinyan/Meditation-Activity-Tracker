<?php
session_start();
require 'db_connection.php';
require 'ip_helper.php';

header("Content-Type: text/plain");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit;
}

$input = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Fetch user with approval_status and status
$stmt = $conn->prepare("
    SELECT id_no, username, password, role, status, approval_status
    FROM registeredacc
    WHERE username = ? OR email = ?
");
$stmt->bind_param("ss", $input, $input);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    
    // CHECK IF ACCOUNT IS BLOCKED
    if (isset($user['status']) && $user['status'] === 'blocked') {
        echo "ACCOUNT_BLOCKED";
        exit;
    }
    
    // CHECK APPROVAL STATUS
    if ($user['approval_status'] === 'pending') {
        echo "ACCOUNT_PENDING";
        exit;
    }
    
    if ($user['approval_status'] === 'rejected') {
        echo "ACCOUNT_REJECTED";
        exit;
    }

    if (password_verify($password, $user['password'])) {

        // Store session info
        $_SESSION['id_no'] = $user['id_no'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // UPDATE LAST ACTIVE TIME
        $updateStmt = $conn->prepare("UPDATE registeredacc SET last_active = NOW() WHERE id_no=?");
        $updateStmt->bind_param("s", $user['id_no']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Get enhanced browser and IP info
        $ip_address = getRealIP();
        $browserInfo = getBrowserInfo();
        $user_agent = $browserInfo['full'];
        
        // Log the login event
        $logStmt = $conn->prepare(
            "INSERT INTO system_logs (id_no, username, action, browser, ip_address) VALUES (?, ?, 'LOGIN', ?, ?)"
        );
        $logStmt->bind_param("ssss", $user['id_no'], $user['username'], $user_agent, $ip_address);
        $logStmt->execute();
        $logStmt->close();

        echo "SUCCESS|" . $user['role'];

    } else {
        echo "Invalid password.";
    }

} else {
    echo "User not found.";
}

$stmt->close();
$conn->close();
exit;
?>