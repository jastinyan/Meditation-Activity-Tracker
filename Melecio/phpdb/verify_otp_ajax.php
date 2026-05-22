<?php
session_start();
require 'db_connection.php';

require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json'); // always return JSON

// accept id_no from POST or GET (important for resend)
$id_no  = $_POST['id_no'] ?? $_GET['id_no'] ?? '';
$resend = isset($_GET['resend']);

if(!$id_no){
    echo json_encode([
        'status' => 'error',
        'message' => "Unauthorized access"
    ]);
    exit;
}

/* ================= EMAIL FUNCTION ================= */
function sendOTP($email, $otp){
    $mail = new PHPMailer(true);
    try{
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'meditationtracker2026@gmail.com';
        $mail->Password   = 'futsmhiqpvyxphsq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('meditationtracker2026@gmail.com', 'Meditation Tracker');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "
            <h2>Password Reset OTP</h2>
            <p>Your OTP code is:</p>
            <h1>$otp</h1>
            <p>This code expires in 5 minutes.</p>
        ";

        $mail->send();
        return true;

    } catch(Exception $e){
        return false;
    }
}

/* ================= RESEND OTP ================= */
if($resend){

    // get user email
    $stmt = $conn->prepare("SELECT email FROM registeredacc WHERE id_no=?");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if(!$user){
        echo json_encode([
            'status'=>'error',
            'message'=>'User not found.'
        ]);
        exit;
    }

    $email = $user['email'];

    // check last OTP expiration
    $stmt = $conn->prepare("
        SELECT expires_at 
        FROM password_resets 
        WHERE id_no=? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();

    // block resend if OTP still valid
    if($last && strtotime($last['expires_at']) > time()){
        $remaining = strtotime($last['expires_at']) - time();
        echo json_encode([
            'status'=>'wait',
            'message'=>"Please wait {$remaining} seconds before resending.",
            'remaining'=>$remaining
        ]);
        exit;
    }

    // generate new OTP
    $otp       = random_int(100000, 999999);
    $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
    $expires   = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $stmt = $conn->prepare("
        INSERT INTO password_resets (id_no, otp, expires_at, is_verified)
        VALUES (?, ?, ?, 0)
    ");
    $stmt->bind_param("sss", $id_no, $hashedOtp, $expires);
    $stmt->execute();

    // send OTP and return JSON
    if(sendOTP($email, $otp)){
        echo json_encode([
            'status'=>'success',
            'message'=>'A new OTP has been sent.',
            'remaining'=>300
        ]);
    } else {
        echo json_encode([
            'status'=>'error',
            'message'=>'Failed to send OTP. Try again later.'
        ]);
    }
    exit;
}

/* ================= VERIFY OTP ================= */
$inputOtp = trim($_POST['otp'] ?? '');

$stmt = $conn->prepare("
    SELECT reset_id, otp, expires_at 
    FROM password_resets 
    WHERE id_no=? AND is_verified=0 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if(!$row){
    echo json_encode([
        'status'=>'error',
        'message'=>'Code is already used. Please request a new one.'
    ]);
    exit;
}

if(strtotime($row['expires_at']) < time()){
    echo json_encode([
        'status'=>'error',
        'message'=>'OTP expired. Request a new one.'
    ]);
    exit;
}

if(password_verify($inputOtp, $row['otp'])){
    $stmt = $conn->prepare("
        UPDATE password_resets 
        SET is_verified=1 
        WHERE reset_id=?
    ");
    $stmt->bind_param("i", $row['reset_id']);
    $stmt->execute();

    echo json_encode([
        'status'=>'success',
        'message'=>'OTP verified successfully.'
    ]);
    exit;
} else {
    echo json_encode([
        'status'=>'error',
        'message'=>'Invalid OTP.'
    ]);
    exit;
}
?>
