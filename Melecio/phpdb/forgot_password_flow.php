<?php
session_start();
require 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Meditation Activity Tracker</title>
    <link rel="stylesheet" href="../css/forgot_password_flow.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
</head>
<body>
<div class="page-wrapper">

    <!-- Navbar -->
    <div class="navbar">
        <p class="system-title container">Meditation Activity Tracker</p>
        <div class="buttons-container">
            <button type="button" class="btn1" id="HomeButton">
                <a id="HomeLinkB" href="../phpdb/homepage.php">Home</a>
            </button>
        </div>
    </div>

    <div class="navbar-spacer"></div>
    
    <div class="wizard-container">
        <!-- Progress Bar with Icons -->
        <div class="progress-bar">
            <div class="step active" data-step="1">
                <i class="fas fa-id-card"></i>
                <span class="step-label">Verify ID</span>
            </div>
            <div class="step" data-step="2">
                <i class="fas fa-envelope"></i>
                <span class="step-label">OTP</span>
            </div>
            <div class="step" data-step="3">
                <i class="fas fa-shield-alt"></i>
                <span class="step-label">Security</span>
            </div>
            <div class="step" data-step="4">
                <i class="fas fa-lock"></i>
                <span class="step-label">Reset</span>
            </div>
        </div>

        <!-- STEP 1: Enter ID -->
        <div class="wizard-step active" id="step1">
            <div class="step-icon">
                <i class="fas fa-id-card"></i>
            </div>
            <h3>Enter Username or ID</h3>
            <p class="step-description">Please enter your username or ID number to receive a verification code.</p>
            <form id="sendOtpForm">
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="user_input" placeholder="Username or ID Number" required>
                </div>
                <div class="buttons">
                    <div></div>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Send OTP
                    </button>
                </div>
                <div id="sendOtpError" class="message error"></div>
                <div id="sendOtpSuccess" class="message success"></div>
            </form>
        </div>

        <!-- STEP 2: OTP -->
        <div class="wizard-step" id="step2">
            <div class="step-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3>OTP Verification</h3>
            <p class="step-description" id="otpDescription">Enter the 6-digit code sent to your email.</p>
            <form id="otpForm">
                <input type="hidden" name="id_no" id="hidden_id_no">
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="text" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" pattern="\d{6}" title="Please enter a 6-digit code" required>
                </div>
                <div class="buttons">
                    <button type="button" onclick="prevStep(1)" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-check"></i> Verify
                    </button>
                    <button type="button" id="resendBtn" class="btn-outline">
                        <i class="fas fa-redo-alt"></i> Resend
                    </button>
                </div>
                <div id="otpError" class="message error"></div>
                <div id="otpSuccess" class="message success"></div>
            </form>
        </div>

        <!-- STEP 3: Security Questions -->
        <div class="wizard-step" id="step3">
            <div class="step-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>Security Questions</h3>
            <p class="step-description">Answer your security questions to verify your identity.</p>
            <form id="questionForm">
                <input type="hidden" name="id_no" id="hidden_id_no2">
                
                <div class="security-question">
                    <label id="label_q1" class="question-label">
                        <i class="fas fa-question-circle"></i>
                        <span id="question_text_1"></span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-eye-slash input-icon" id="toggleAnswer1" onclick="toggleAnswerVisibility('sec_a1', this)"></i>
                        <input type="password" name="sec_a1" id="sec_a1" class="security-answer" placeholder="Your answer" autocomplete="off" required>
                    </div>
                </div>

                <div class="security-question">
                    <label id="label_q2" class="question-label">
                        <i class="fas fa-question-circle"></i>
                        <span id="question_text_2"></span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-eye-slash input-icon" id="toggleAnswer2" onclick="toggleAnswerVisibility('sec_a2', this)"></i>
                        <input type="password" name="sec_a2" id="sec_a2" class="security-answer" placeholder="Your answer" autocomplete="off" required>
                    </div>
                </div>

                <div class="security-question">
                    <label id="label_q3" class="question-label">
                        <i class="fas fa-question-circle"></i>
                        <span id="question_text_3"></span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-eye-slash input-icon" id="toggleAnswer3" onclick="toggleAnswerVisibility('sec_a3', this)"></i>
                        <input type="password" name="sec_a3" id="sec_a3" class="security-answer" placeholder="Your answer" autocomplete="off" required>
                    </div>
                </div>

                <div class="buttons">
                    <button type="button" onclick="prevStep(2)" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-arrow-right"></i> Next
                    </button>
                </div>

                <div id="sec-msg" class="message error"></div>
            </form>
        </div>

        <!-- STEP 4: Reset Password -->
        <div class="wizard-step" id="step4">
            <div class="step-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h3>Reset Password</h3>
            <p class="step-description">Create a new strong password for your account.</p>
            <form id="resetForm">
                <input type="hidden" name="id_no" id="hidden_id_no3">
                
                <div class="input-group">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
                </div>
                <div id="pwStrength" class="password-strength"></div>
                
                <div class="input-group">
                    <i class="fas fa-check-circle input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div id="pwMatch" class="password-match"></div>
                
                <div class="password-requirements">
                    <p><i class="fas fa-info-circle"></i> Password must contain:</p>
                    <ul>
                        <li id="req-length"><i class="far fa-circle"></i> At least 8 characters</li>
                        <li id="req-uppercase"><i class="far fa-circle"></i> At least one uppercase letter</li>
                        <li id="req-lowercase"><i class="far fa-circle"></i> At least one lowercase letter</li>
                        <li id="req-number"><i class="far fa-circle"></i> At least one number</li>
                    </ul>
                </div>
                
                <div class="buttons">
                    <button type="button" onclick="prevStep(3)" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </div>
                
                <div id="resetError" class="message error"></div>
                <div id="resetSuccess" class="message success"></div>
            </form>
        </div>

    </div>

    <div class="footer-spacer"></div>
    <div class="footer">
        <p class="all"><i class="far fa-copyright"></i> 2026 Meditation Activity Tracker. All rights reserved.</p>
    </div>

</div>

<script>
// ==================== GLOBAL FUNCTIONS ====================
let currentStep = 1;
let currentUsername = '';
let currentCensoredEmail = '';

const showStep = step => {
    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
    document.getElementById('step' + step).classList.add('active');
    currentStep = step;

    document.querySelectorAll('.progress-bar .step').forEach((el, idx) => {
        if (idx < step) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });

    // When showing step 2, update the description with username and censored email
    if (step === 2 && currentUsername && currentCensoredEmail) {
        const otpDescription = document.getElementById('otpDescription');
        if (otpDescription) {
            otpDescription.innerHTML = `<strong>${currentUsername}</strong>, your verification code has been sent to <strong>${currentCensoredEmail}</strong>`;
        }
    }

    // Fetch questions when step 3 is shown
    if (step === 3 && typeof fetchSecurityQuestions === "function") {
        fetchSecurityQuestions();
    }
};

const prevStep = step => showStep(step);

// ==================== TOGGLE ANSWER VISIBILITY ====================
function toggleAnswerVisibility(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
    } else {
        input.type = "password";
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
    }
}

// ==================== STEP 1: Send OTP ====================
document.getElementById('sendOtpForm').addEventListener('submit', async e => {
    e.preventDefault();

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    submitBtn.disabled = true;

    const res = await fetch('send_otp_ajax.php', {
        method: 'POST',
        body: new FormData(e.target)
    });

    const data = await res.json();

    if (data.status === 'success') {
        document.getElementById('sendOtpSuccess').innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
        document.getElementById('sendOtpError').innerHTML = '';

        // Store the ID and user info
        document.getElementById('hidden_id_no').value = data.id_no;
        document.getElementById('hidden_id_no2').value = data.id_no;
        document.getElementById('hidden_id_no3').value = data.id_no;
        
        // Store username and censored email for step 2 display
        currentUsername = data.username;
        currentCensoredEmail = data.censored_email;
        
        // Start countdown for resend button
        if (data.remaining) {
            startCountdown(data.remaining);
        }
        
        // Show step 2 with the personalized message
        showStep(2);

    } else if (data.status === 'wait') {
        document.getElementById('sendOtpError').innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message}`;
        document.getElementById('sendOtpSuccess').innerHTML = '';
    } else {
        document.getElementById('sendOtpError').innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message}`;
        document.getElementById('sendOtpSuccess').innerHTML = '';
    }

    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
});

// ==================== STEP 2: Verify OTP ====================
document.getElementById('otpForm').addEventListener('submit', async e => {
    e.preventDefault();

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    submitBtn.disabled = true;

    const res = await fetch('verify_otp_ajax.php', {
        method: 'POST',
        body: new FormData(e.target)
    });

    const data = await res.json();

    if (data.status === "success") {
        document.getElementById("otpSuccess").innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
        document.getElementById("otpError").innerHTML = "";
        showStep(3);
    } else {
        document.getElementById("otpError").innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message}`;
        document.getElementById("otpSuccess").innerHTML = "";
    }

    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
});

// ==================== Resend OTP ====================
document.getElementById('resendBtn').addEventListener('click', async () => {
    const btn = document.getElementById('resendBtn');
    const originalText = btn.innerHTML;
    const otpError = document.getElementById('otpError');
    const otpSuccess = document.getElementById('otpSuccess');
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('id_no', document.getElementById('hidden_id_no').value);

    const res = await fetch('verify_otp_ajax.php?resend=1', {
        method: 'POST',
        body: formData
    });

    const data = await res.json();

    if (data.status === "success") {
        otpSuccess.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
        otpError.innerHTML = "";
        if (data.remaining) startCountdown(data.remaining);
        btn.innerHTML = originalText;
        btn.disabled = false;
    } else {
        const timeMatch = data.message.match(/(\d+)/);
        if (timeMatch && data.message.includes('seconds')) {
            const seconds = parseInt(timeMatch[0]);
            startResendCountdown(seconds, btn, otpError);
        } else {
            otpError.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message}`;
            otpSuccess.innerHTML = "";
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
});

// ==================== Resend Countdown Timer with Error Message ====================
function startResendCountdown(seconds, btn, errorElement) {
    let remaining = seconds;
    
    btn.disabled = true;
    
    const interval = setInterval(() => {
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        const timeString = `${m}:${s.toString().padStart(2, '0')}`;
        
        errorElement.innerHTML = `<i class="fas fa-hourglass-half"></i> Please wait ${timeString} before resending`;
        
        btn.innerHTML = `<i class="fas fa-hourglass-half"></i> Resend (${timeString})`;
        
        remaining--;

        if (remaining < 0) {
            clearInterval(interval);
            errorElement.innerHTML = '';
            btn.innerHTML = '<i class="fas fa-redo-alt"></i> Resend';
            btn.disabled = false;
        }
    }, 1000);
}

function startCountdown(seconds) {
    const btn = document.getElementById('resendBtn');
    let remaining = seconds;
    
    btn.disabled = true;

    const interval = setInterval(() => {
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        const timeString = `${m}:${s.toString().padStart(2, '0')}`;
        
        btn.innerHTML = `<i class="fas fa-hourglass-half"></i> Resend (${timeString})`;
        remaining--;

        if (remaining < 0) {
            clearInterval(interval);
            btn.innerHTML = '<i class="fas fa-redo-alt"></i> Resend';
            btn.disabled = false;
        }
    }, 1000);
}

// ==================== STEP 4: Reset Password ====================
document.getElementById('resetForm').addEventListener('submit', async e => {
    e.preventDefault();

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
    submitBtn.disabled = true;

    const res = await fetch('reset_password_ajax.php', {
        method: 'POST',
        body: new FormData(e.target)
    });

    const data = await res.text();

    if (data.trim() === 'success') {
        document.getElementById('resetSuccess').innerHTML = '<i class="fas fa-check-circle"></i> Password reset successfully! Redirecting to login...';
        document.getElementById('resetError').innerHTML = "";

        setTimeout(() => window.location.href = 'login.php', 2000);
    } else {
        document.getElementById('resetError').innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data}`;
        document.getElementById('resetSuccess').innerHTML = "";
    }

    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
});

// ==================== Restore lockout on page load ====================
document.addEventListener("DOMContentLoaded", () => {
    sessionStorage.removeItem('otp_verified');
    
    const qTexts = ['question_text_1', 'question_text_2', 'question_text_3'];
    qTexts.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = 'Loading question...';
    });
});
</script>

<script src="../script/sec_questions.js"></script>
<script src="../script/reset_password.js"></script>

</body>
</html>