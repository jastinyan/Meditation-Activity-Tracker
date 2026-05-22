<?php
session_start();
require 'db_connection.php';

// Check if user is already logged in
if (isset($_SESSION['id_no'])) {
    // Redirect based on role
    if ($_SESSION['role'] === 'super_admin') {
        header("Location: superadmin_home.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin_home.php");
    } else {
        header("Location: user_home.php");
    }
    exit;
}

// This endpoint handles AJAX requests from login.js
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Check if user exists (by username or email)
    $stmt = $conn->prepare("SELECT id_no, username, password, role, status, approval_status FROM registeredacc WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // CHECK IF USER IS BLOCKED
        if (isset($user['status']) && $user['status'] === 'blocked') {
            echo "ACCOUNT_BLOCKED";
            exit;
        }
        
        // CHECK IF USER IS APPROVED
        if ($user['approval_status'] === 'pending') {
            echo "ACCOUNT_PENDING";
            exit;
        }
        
        if ($user['approval_status'] === 'rejected') {
            echo "ACCOUNT_REJECTED";
            exit;
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['id_no'] = $user['id_no'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Update last active
            $updateStmt = $conn->prepare("UPDATE registeredacc SET last_active = NOW() WHERE id_no = ?");
            $updateStmt->bind_param("s", $user['id_no']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Log the login action
            $logStmt = $conn->prepare("INSERT INTO system_logs (id_no, username, action, timestamp, browser, ip_address) VALUES (?, ?, 'login', NOW(), ?, ?)");
            $browser = $_SERVER['HTTP_USER_AGENT'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt->bind_param("ssss", $user['id_no'], $user['username'], $browser, $ip);
            $logStmt->execute();
            $logStmt->close();
            
            echo "SUCCESS|" . $user['role'];
            exit;
        } else {
            echo "Invalid password.";
            exit;
        }
    } else {
        echo "User not found.";
        exit;
    }
}

// Check for registration message
$registration_msg = '';
$registration_email = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'pending') {
    $registration_msg = 'pending';
    $registration_email = isset($_GET['email']) ? $_GET['email'] : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../phpdb/script.php?dir=css&file=login.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
    <title>Login - Meditation Activity Tracker</title>
  
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <p class="system-title container">Meditation Activity Tracker</p>
        <div class="buttons-container">
            <button type="button" class="btn1" id="HomeButton"><a id="HomeLinkB" href="../phpdb/homepage.php">Home</a></button>
            <button type="button" class="btn2" id="registerButton"><a id="registerLinkB" href="../phpdb/register.php">Register</a></button>
        </div>
    </div>

    <!-- Login Box -->
    <div class="login-box container">
        <h2 class="login-title">Log in</h2>

        <!-- Registration Success Message -->
        <?php if ($registration_msg === 'pending'): ?>
        <div class="status-message success">
            <i class="fas fa-check-circle"></i>
            <div class="message-content">
                <div class="message-title">Registration Submitted Successfully!</div>
                <div class="message-text">
                    Your account is now pending approval. You will receive an email at <strong><?= htmlspecialchars($registration_email) ?></strong> once your account is approved. This usually takes 24-48 hours.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form id="loginForm" action="" method="POST" class="form">
            <!-- Username Input -->
            <div class="input-field">
                <label class="label" for="username">Username or Email</label>
                <input class="input" type="text" id="username" name="username" required placeholder="Enter your username or email">
            </div>

            <!-- Password Input -->
            <div class="input-field">
                <label class="label" for="password">Password</label>
                <input class="input" type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <!-- Error Container for AJAX responses -->
            <div id="errorContainer" style="display: none;"></div>

            <!-- Static Message Container for direct messages -->
            <div id="staticMessageContainer" style="display: none;"></div>


            <!-- Show Password Checkbox -->
            <div class="show-password-container">
                <input type="checkbox" id="showPassword"> 
                <label for="showPassword" class="label">Show Password</label>
            </div>

            <!-- Login Button -->
            <div class="button">
                <button type="submit" class="btn" id="loginButton">Login</button>
            </div>

            <!-- Register Link -->
            <div class="div-create">
                <p class="create">Don't have an account? <a id="register-link" href="../phpdb/register.php">Register Here</a></p>
            </div>

            <!-- Forgot Password Container (appears after 2 failed attempts) -->
            <div id="forgotPasswordContainer" style="display:none;"></div>

            <!-- Lockout Timer -->
            <div id="timerDisplay" style="display:none;">
                <div class="status-message info">
                    <i class="fas fa-hourglass-half"></i>
                    <div class="message-content">
                        <div class="message-title">Account Locked</div>
                        <div class="message-text">Too many failed attempts. Please wait <span id="lockoutTimeDisplay"></span> seconds before trying again.</div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Contact Support (shown for blocked/rejected accounts) -->
        <div id="contactSupport" style="display: none;" class="contact-support">
            <i class="fas fa-envelope"></i> Need help? <a href="mailto:support@meditationtracker.com">Contact Support</a>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p class="all">&copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
    </div>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- JS -->
    <script src="../phpdb/script.php?dir=script&file=login.js" defer></script>
</body>
</html>