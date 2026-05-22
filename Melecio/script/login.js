// =====================
// Lockout variables
// =====================
let failedAttempts = localStorage.getItem('failedAttempts')
    ? parseInt(localStorage.getItem('failedAttempts'))
    : 0;

let lockoutTime = localStorage.getItem('lockoutTime')
    ? parseInt(localStorage.getItem('lockoutTime'))
    : 0;

const lockoutIntervals = [15, 30, 60]; // seconds

// =====================
// Elements
// =====================
const loginForm = document.getElementById("loginForm");
const userInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const showPasswordCheckbox = document.getElementById("showPassword");
const timerDisplay = document.getElementById("timerDisplay");
const lockoutTimeDisplay = document.getElementById("lockoutTimeDisplay");
const loginButton = document.getElementById("loginButton");
const registerButton = document.getElementById("registerButton");
const registerLinkB = document.getElementById('registerLinkB');
const HomeButton = document.getElementById("HomeButton");
const HomeLinkB = document.getElementById('HomeLinkB');
const forgotPasswordContainer = document.getElementById("forgotPasswordContainer");
const registerlink = document.getElementById('register-link');
const errorContainer = document.getElementById("errorContainer");

// =====================
// Show / Hide password
// =====================
if (showPasswordCheckbox) {
    showPasswordCheckbox.addEventListener('change', function () {
        if (passwordInput) {
            passwordInput.type = this.checked ? 'text' : 'password';
        }
    });
}

// =====================
// Display error message below password field
// =====================
function showError(message, type = 'error') {
    if (errorContainer) {
        let icon = 'fa-exclamation-circle';
        let bgColor = '#ffebee'; // Light red background
        let borderColor = '#f44336'; // Red border
        let textColor = '#5f0b0b'; // Dark red text
        
        if (type === 'pending') {
            icon = 'fa-clock';
            bgColor = '#fff8e1'; // Light amber
            borderColor = '#ffc107'; // Amber
            textColor = '#693e05'; // Dark amber
        } else if (type === 'rejected') {
            icon = 'fa-times-circle';
            bgColor = '#ffebee';
            borderColor = '#dc3545';
            textColor = '#680909';
        } else if (type === 'blocked') {
            icon = 'fa-ban';
            bgColor = '#ffebee';
            borderColor = '#d32f2f';
            textColor = '#530808';
        } else if (type === 'info') {
            icon = 'fa-info-circle';
            bgColor = '#e3f2fd'; // Light blue
            borderColor = '#2196f3'; // Blue
            textColor = '#052352'; // Dark blue
        }
        
        errorContainer.innerHTML = `
            <div style="background: ${bgColor}; color: ${textColor}; padding: 12px 16px; border-radius: 8px; margin: 10px 0; border-left: 4px solid ${borderColor}; display: flex; align-items: center; gap: 12px; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <i class="fas ${icon}" style="font-size: 18px;"></i>
                <div style="flex: 1;">${message}</div>
            </div>
        `;
        errorContainer.style.display = 'block';
        
        // Auto-hide after 5 seconds for non-error messages
        if (type !== 'error' && type !== 'blocked' && type !== 'rejected') {
            setTimeout(() => {
                errorContainer.style.display = 'none';
            }, 5000);
        }
    } else {
        alert(message);
    }
}

// =====================
// Clear password field
// =====================
function clearPasswordField() {
    if (passwordInput) {
        passwordInput.value = '';
    }
}

// =====================
// Login submit
// =====================
if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const username = userInput?.value.trim();
        const password = passwordInput?.value.trim();

        if (!username || !password) {
            showError("Enter both username and password");
            clearPasswordField();
            return;
        }

        fetch('../phpdb/db_connectLogin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        })
        .then(response => response.text())
        .then(data => {
            data = data.trim();

            if (data.startsWith("SUCCESS|")) {
                resetFailedAttempts();
                const role = data.split("|")[1].toLowerCase();
                
                if (role === "user") window.location.href = "../phpdb/user_home.php";
                else if (role === "admin") window.location.href = "../phpdb/admin_home.php";
                else if (role === "super_admin") window.location.href = "../phpdb/superadmin_home.php";
                else alert("Unknown role.");
            }
            else if (data === "ACCOUNT_PENDING") {
                showError("Your account is pending approval. Please wait for administrator confirmation.", 'pending');
                incrementFailedAttempts();
                clearPasswordField();
            }
            else if (data === "ACCOUNT_REJECTED") {
                showError("Your registration has been rejected. Please contact support for assistance.", 'rejected');
                incrementFailedAttempts();
                clearPasswordField();
            }
            else if (data === "ACCOUNT_BLOCKED") {
                showError("Your account has been blocked. Please contact administrator.", 'blocked');
                incrementFailedAttempts();
                clearPasswordField();
            }
            else if (data === "User not found.") {
                showError("Username does not exist.");
                incrementFailedAttempts();
                clearPasswordField();
            }
            else if (data === "Invalid password.") {
                showError("Password is incorrect.");
                incrementFailedAttempts(true); // approved account — show reset link after 2 fails
                clearPasswordField();
            }
            else {
                console.error("Server response:", data);
                showError("Unexpected server response.");
                clearPasswordField();
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            showError("Login error occurred.");
            clearPasswordField();
        });
    });
}

// =====================
// Failed attempts logic
// =====================
function incrementFailedAttempts(isApprovedAccount = false) {
    failedAttempts++;

    // Only show forgot password link for approved accounts with wrong password
    if (failedAttempts >= 2 && forgotPasswordContainer && isApprovedAccount) {
        showForgotPassword();
    }

    if (
        failedAttempts % 3 === 0 &&
        lockoutIntervals[Math.floor((failedAttempts / 3) - 1)] !== undefined
    ) {
        lockoutTime = lockoutIntervals[Math.floor((failedAttempts / 3) - 1)];
        const lockoutEnd = Date.now() + lockoutTime * 1000;

        localStorage.setItem('lockoutEnd', lockoutEnd);
        localStorage.setItem('lockoutTime', lockoutTime);

        disableLogin();
        updateLockout();
    }

    if (failedAttempts >= 10) {
        disableLogin();
        showError("Maximum login attempts reached.");
    }

    localStorage.setItem('failedAttempts', failedAttempts);
}

function resetFailedAttempts() {
    failedAttempts = 0;
    localStorage.setItem('failedAttempts', failedAttempts);
    resetLockout();

    if (forgotPasswordContainer) {
        forgotPasswordContainer.style.display = "none";
    }
}

// =====================
// Lockout controls (original)
// =====================
function disableLogin() {
    if (loginButton) loginButton.disabled = true;
    if (userInput) userInput.disabled = true;
    if (passwordInput) passwordInput.disabled = true;
    if (registerButton) registerButton.disabled = true;
    if (registerLinkB) registerLinkB.style.pointerEvents = "none";
    if (registerlink) registerlink.style.pointerEvents = "none";
    if (HomeButton) HomeButton.disabled = true;
    if (HomeLinkB) HomeLinkB.style.pointerEvents = "none";
}

function enableLogin() {
    if (loginButton) loginButton.disabled = false;
    if (userInput) userInput.disabled = false;
    if (passwordInput) passwordInput.disabled = false;
    if (registerButton) registerButton.disabled = false;
    if (registerLinkB) registerLinkB.style.pointerEvents = "auto";
    if (registerlink) registerlink.style.pointerEvents = "auto";
    if (HomeButton) HomeButton.disabled = false;
    if (HomeLinkB) HomeLinkB.style.pointerEvents = "auto";
}

function resetLockout() {
    localStorage.removeItem('lockoutEnd');
    localStorage.removeItem('lockoutTime');

    if (timerDisplay) timerDisplay.style.display = "none";
    if (lockoutTimeDisplay) lockoutTimeDisplay.textContent = "";

    enableLogin();
}

// =====================
// Timer (original)
// =====================
function updateLockout() {
    const lockoutEnd = parseInt(localStorage.getItem('lockoutEnd'));

    if (!lockoutEnd) return;

    const remaining = Math.ceil((lockoutEnd - Date.now()) / 1000);

    if (remaining > 0) {
        if (timerDisplay) timerDisplay.style.display = "block";
        if (lockoutTimeDisplay) lockoutTimeDisplay.textContent = remaining;

        disableLogin();
        setTimeout(updateLockout, 1000);
    } else {
        resetLockout();
    }
}

// =====================
// Forgot password (original)
// =====================
function showForgotPassword() {
    if (forgotPasswordContainer) {
        forgotPasswordContainer.innerHTML =
            `<p>Forgot Password? <a href="forgot_password_flow.php">Reset Here</a></p>`;
        forgotPasswordContainer.style.display = "block";
    }
}

// =====================
// Clear password on page load
// =====================
document.addEventListener("DOMContentLoaded", () => {
    clearPasswordField();
});

// =====================
// Restore lockout on refresh (original)
// =====================
document.addEventListener("DOMContentLoaded", () => {
    const lockoutEnd = parseInt(localStorage.getItem('lockoutEnd'));

    if (lockoutEnd && Date.now() < lockoutEnd) {
        disableLogin();
        updateLockout();
    } else {
        resetLockout();
    }
});