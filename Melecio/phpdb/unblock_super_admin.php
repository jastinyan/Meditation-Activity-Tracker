<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is super_admin
if (!isset($_SESSION['id_no']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

$current_super_admin_id = $_SESSION['id_no'];
$current_super_admin_username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_id'])) {
    $id_to_unblock = mysqli_real_escape_string($conn, $_POST['unblock_id']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Verify that the account to unblock is a blocked super_admin
        $check_query = "SELECT role, status FROM registeredacc WHERE id_no = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $id_to_unblock);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $user_data = mysqli_fetch_assoc($result);
        
        if (!$user_data || $user_data['role'] !== 'super_admin' || $user_data['status'] !== 'blocked') {
            throw new Exception("Invalid account to unblock");
        }
        
        // Block current super_admin
        $block_current = "UPDATE registeredacc SET status = 'blocked' WHERE id_no = ?";
        $block_stmt = mysqli_prepare($conn, $block_current);
        mysqli_stmt_bind_param($block_stmt, "s", $current_super_admin_id);
        if (!mysqli_stmt_execute($block_stmt)) {
            throw new Exception("Failed to block current super admin");
        }
        
        // Unblock the selected super_admin
        $unblock_query = "UPDATE registeredacc SET status = 'active' WHERE id_no = ?";
        $unblock_stmt = mysqli_prepare($conn, $unblock_query);
        mysqli_stmt_bind_param($unblock_stmt, "s", $id_to_unblock);
        if (!mysqli_stmt_execute($unblock_stmt)) {
            throw new Exception("Failed to unblock selected super admin");
        }
        
        // Log the action (optional)
        $log_action = "Unblocked super admin " . $id_to_unblock . " and self-blocked";
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Destroy session since current admin is now blocked
        session_destroy();
        
        // Redirect to login with message
        header("Location: login.php?message=super_admin_switched");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        header("Location: manage_super_admins.php?error=unblock_failed");
        exit();
    }
} else {
    header("Location: manage_super_admins.php");
    exit();
}
?>