<?php
session_start();
require 'db_connection.php';
date_default_timezone_set('Asia/Manila');

$id_no = $_POST['id_no'] ?? '';

if(!$id_no || !isset($_SESSION['security_verified']) || $_SESSION['security_verified']!==true){
    echo "Unauthorized access"; 
    exit;
}

/* CHECK OTP VERIFIED */
$stmt = $conn->prepare("SELECT reset_id FROM password_resets WHERE id_no=? AND is_verified=1 ORDER BY reset_id DESC LIMIT 1");
$stmt->bind_param("s",$id_no);
$stmt->execute();

if($stmt->get_result()->num_rows===0){ 
    echo "OTP verification required."; 
    exit; 
}

$new = trim($_POST['new_password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

/* MATCH CHECK */
if($new !== $confirm){
    echo "Passwords do not match."; 
    exit;
}

/* EMPTY CHECK */
if(strlen($new) === 0){
    echo "Password required."; 
    exit;
}

/* LENGTH CHECK (same as register.js) */
if(strlen($new) < 8 || strlen($new) >= 20){
    echo "Password must be 8-19 characters."; 
    exit;
}


if(!preg_match('/^[a-zA-Z0-9]+$/', $new)){
    echo "Password must be alphanumeric only."; 
    exit;
}

/* OPTIONAL: prevent reuse of old password */
$stmt = $conn->prepare("SELECT password FROM registeredacc WHERE id_no=?");
$stmt->bind_param("s",$id_no);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if($res && password_verify($new, $res['password'])){
    echo "New password cannot be same as old password.";
    exit;
}

/* HASH + SAVE */
$hashed = password_hash($new,PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE registeredacc SET password=? WHERE id_no=?");
$stmt->bind_param("ss",$hashed,$id_no);
$stmt->execute();

/* CLEAN OTP */
$stmt = $conn->prepare("DELETE FROM password_resets WHERE id_no=?");
$stmt->bind_param("s",$id_no);
$stmt->execute();

echo "success";
exit;
?>
