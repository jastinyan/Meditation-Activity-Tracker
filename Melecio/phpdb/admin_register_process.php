<?php
session_start();
require 'db_connection.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and has admin/super_admin role
if (!isset($_SESSION['id_no']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: login.php");
    exit;
}

$creator_role = $_SESSION['role']; // Role of the person creating the account
$creator_id = $_SESSION['id_no']; // ID of the person creating the account
$creator_username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id_no = trim($_POST['id_no']);
    $f_name = trim($_POST['f_name']);
    $m_initial = trim($_POST['m_initial']);
    $l_name = trim($_POST['l_name']);
    $extension = trim($_POST['extension']);
    $birthday = $_POST['birthday'];
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $role = $_POST['role']; // Selected role
    $purok = trim($_POST['purok']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $country = trim($_POST['country']);
    $zipcode = trim($_POST['zipcode']);
    $sec_a1 = trim($_POST['sec_a1']);
    $sec_a2 = trim($_POST['sec_a2']);
    $sec_a3 = trim($_POST['sec_a3']);
    $sec_q1 = $_POST['sec_q1'];
    $sec_q2 = $_POST['sec_q2'];
    $sec_q3 = $_POST['sec_q3'];

    // Validate role based on creator's permissions
    if ($creator_role === 'admin' && ($role === 'super_admin')) {
        // Admin cannot create super_admin
        header("Location: admin_register.php?error=invalid_role");
        exit;
    }

    // Check if ID number already exists
    $check_id = mysqli_prepare($conn, "SELECT id_no FROM registeredacc WHERE id_no = ?");
    mysqli_stmt_bind_param($check_id, "s", $id_no);
    mysqli_stmt_execute($check_id);
    mysqli_stmt_store_result($check_id);
    
    if (mysqli_stmt_num_rows($check_id) > 0) {
        header("Location: admin_register.php?error=id_exists");
        exit;
    }
    mysqli_stmt_close($check_id);

    // Check if username already exists
    $check_username = mysqli_prepare($conn, "SELECT username FROM registeredacc WHERE username = ?");
    mysqli_stmt_bind_param($check_username, "s", $username);
    mysqli_stmt_execute($check_username);
    mysqli_stmt_store_result($check_username);
    
    if (mysqli_stmt_num_rows($check_username) > 0) {
        header("Location: admin_register.php?error=username_exists");
        exit;
    }
    mysqli_stmt_close($check_username);

    // Check if email already exists
    $check_email = mysqli_prepare($conn, "SELECT email FROM registeredacc WHERE email = ?");
    mysqli_stmt_bind_param($check_email, "s", $email);
    mysqli_stmt_execute($check_email);
    mysqli_stmt_store_result($check_email);
    
    if (mysqli_stmt_num_rows($check_email) > 0) {
        header("Location: admin_register.php?error=email_exists");
        exit;
    }
    mysqli_stmt_close($check_email);

    // START: Super Admin blocking logic
    // If the new user is a super_admin, block the current active super_admin
    if ($role === 'super_admin') {
        // Find the current active super_admin
        $find_super_admin = mysqli_prepare($conn, "SELECT id_no FROM registeredacc WHERE role = 'super_admin' AND status = 'active'");
        mysqli_stmt_execute($find_super_admin);
        mysqli_stmt_store_result($find_super_admin);
        
        if (mysqli_stmt_num_rows($find_super_admin) > 0) {
            // There is an existing active super_admin - get their ID
            mysqli_stmt_bind_result($find_super_admin, $current_super_admin_id);
            mysqli_stmt_fetch($find_super_admin);
            mysqli_stmt_close($find_super_admin);
            
            // Block the current super_admin (set status to 'blocked')
            $block_super_admin = mysqli_prepare($conn, "UPDATE registeredacc SET status = 'blocked' WHERE id_no = ?");
            mysqli_stmt_bind_param($block_super_admin, "s", $current_super_admin_id);
            
            if (!mysqli_stmt_execute($block_super_admin)) {
                // If blocking fails, log error but continue with registration
                error_log("Failed to block previous super_admin: " . mysqli_error($conn));
            }
            mysqli_stmt_close($block_super_admin);
        } else {
            mysqli_stmt_close($find_super_admin);
        }
    }
    // END: Super Admin blocking logic

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Set approval status and created_at
    $approval_status = 'approved';
    $created_at = date('Y-m-d H:i:s');

    // Insert into database - WITHOUT created_by column
    $sql = "INSERT INTO `registeredacc` (
        id_no, f_name, m_initial, l_name, extension, birthday, age, sex, 
        username, password, email, role, purok, barangay, city, province, 
        country, zipcode, sec_q1, sec_a1, sec_q2, sec_a2, sec_q3, sec_a3, 
        approval_status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind parameters - 26 parameters total
        mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssssssss", 
            $id_no, $f_name, $m_initial, $l_name, $extension, $birthday, $age, $sex,
            $username, $hashed_password, $email, $role, $purok, $barangay, $city, 
            $province, $country, $zipcode, $sec_q1, $sec_a1, $sec_q2, $sec_a2, 
            $sec_q3, $sec_a3, $approval_status, $created_at
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Success - redirect with success message
            // If this was a super_admin registration, add a special message
            if ($role === 'super_admin') {
                header("Location: admin_register.php?success=1&role=" . urlencode($role) . "&previous_blocked=1");
            } else {
                header("Location: admin_register.php?success=1&role=" . urlencode($role));
            }
            exit();
        } else {
            // Error during insertion - show error for debugging
            die("MySQL Error: " . mysqli_error($conn));
            // header("Location: admin_register.php?error=insert_failed");
            // exit();
        }

        mysqli_stmt_close($stmt);
    } else {
        // Prepare failed - show error for debugging
        die("Prepare failed: " . mysqli_error($conn));
        // header("Location: admin_register.php?error=prepare_failed");
        // exit();
    }

    mysqli_close($conn);
} else {
    // If not POST request, redirect to form
    header("Location: admin_register.php");
    exit();
}
?>