<?php
session_start();
require 'db_connection.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Function to censor email (show only first and last character of username)
function censorEmail($email) {
    $parts = explode('@', $email);
    $username = $parts[0];
    $domain = $parts[1] ?? '';
    
    $usernameLength = strlen($username);
    if ($usernameLength <= 2) {
        $censoredUsername = $username[0] . str_repeat('*', $usernameLength - 1);
    } else {
        $censoredUsername = $username[0] . str_repeat('*', $usernameLength - 2) . $username[$usernameLength - 1];
    }
    
    return $censoredUsername . '@' . $domain;
}

$input = trim($_POST['user_input'] ?? '');
if(!$input){ 
    echo json_encode([
        'status' => 'error',
        'message' => "Please enter your username or ID."
    ]); 
    exit; 
}

// Updated query to also get username
$stmt = $conn->prepare("SELECT id_no, email, username FROM registeredacc WHERE username=? OR id_no=?");
$stmt->bind_param("ss", $input, $input);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){ 
    echo json_encode([
        'status' => 'error',
        'message' => "Account does not exist."
    ]); 
    exit; 
}

$id_no = $user['id_no'];
$email = $user['email'];
$username = $user['username'];

// Check if there is already an unexpired OTP
$stmt = $conn->prepare("SELECT otp, expires_at FROM password_resets WHERE id_no=? AND is_verified=0 ORDER BY expires_at DESC LIMIT 1");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$existingOtp = $stmt->get_result()->fetch_assoc();

$current_time = date("Y-m-d H:i:s");

if($existingOtp && $existingOtp['expires_at'] > $current_time){
    // OTP exists and hasn't expired yet
    $remaining = strtotime($existingOtp['expires_at']) - strtotime($current_time);
    echo json_encode([
        'status' => 'wait',
        'message' => "OTP already sent. Please wait {$remaining} seconds before requesting again.",
        'remaining' => $remaining
    ]);
    exit;
}

// If no valid OTP exists, generate a new one
$otp = random_int(100000,999999);
$hashedOtp = password_hash($otp,PASSWORD_DEFAULT);
$expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

$stmt = $conn->prepare("INSERT INTO password_resets(id_no,otp,expires_at) VALUES(?,?,?)");
$stmt->bind_param("sss",$id_no,$hashedOtp,$expires);
$stmt->execute();

// Send OTP via PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'meditationtracker2026@gmail.com';
    $mail->Password   = 'futsmhiqpvyxphsq';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom('meditationtracker2026@gmail.com','Meditation Tracker');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code';
    $mail->Body = "<h2>Password Reset OTP</h2><p>Your OTP code is:</p><h1>$otp</h1><p>This code expires in 5 minutes.</p>";
    $mail->send();

    // Return all data including username and censored email in the JSON response
    echo json_encode([
        'status' => 'success',
        'message' => "OTP sent successfully.",
        'remaining' => 300,
        'id_no' => $id_no,
        'username' => $username,
        'censored_email' => censorEmail($email)
    ]);
} catch(Exception $e){
    echo json_encode([
        'status' => 'error',
        'message' => "Failed to send OTP: {$mail->ErrorInfo}"
    ]);
}
?>