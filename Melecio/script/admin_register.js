// ==================== VALIDATION FUNCTIONS ====================

function hasInput(value) {
    return value.length > 0;
}

function validateIdNumber(value) {
    return /^\d{4}-\d{4}$/.test(value);
}

function addDashToIdNumber(value) {
    const match = value.match(/^(\d{4})(\d*)/);
    if (match) {
        return match[1] + '-' + match[2];
    }
    return value;
}

function isNumeric(value) {
    return /^[0-9]+$/.test(value);
}

function hasNumberInName(name) {
    return /\d/.test(name);
}

function isCapitalized(str, fieldName) {
    // Check for double spaces
    if (hasDoubleSpaces(str)) {
        showNotification(`Error in ${fieldName}: Must not contain double spaces.`, 'error');
        return false;
    }
    
    const words = str.trim().split(' ');

    // Check the first word capitalization and no internal uppercase letters
    if (words.length > 0) {
        const firstWord = words[0];

        // Ensure the first letter is capitalized
        if (firstWord.charAt(0) !== firstWord.charAt(0).toUpperCase()) {
            showNotification(`Error in ${fieldName}: First letter of the first word must be capitalized.`, 'error');
            return false;
        }
        
        // Check if the entire string is all uppercase
        if (firstWord === firstWord.toUpperCase()) {
            showNotification(`Error in ${fieldName}: First word must not be in all capital letters.`, 'error');
            return false;
        }
        
        const restOfFirstWord = firstWord.slice(1);
        if (restOfFirstWord !== restOfFirstWord.toLowerCase()) {
            showNotification(`Error in ${fieldName}: First word must not contain uppercase letters after the first letter.`, 'error');
            return false;
        }
    }

    // Check the second word capitalization and no internal uppercase letters (if it exists)
    if (words.length > 1) {
        const secondWord = words[1];

        // Ensure the first letter is capitalized
        if (secondWord.charAt(0) !== secondWord.charAt(0).toUpperCase()) {
            showNotification(`Error in ${fieldName}: First letter of the second word must be capitalized.`, 'error');
            return false;
        }
        
        // Check if the entire string is all uppercase
        if (secondWord === secondWord.toUpperCase()) {
            showNotification(`Error in ${fieldName}: Second word must not be in all capital letters.`, 'error');
            return false;
        }
        
        const restOfSecondWord = secondWord.slice(1);
        if (restOfSecondWord !== restOfSecondWord.toLowerCase()) {
            showNotification(`Error in ${fieldName}: Second word must not contain uppercase letters after the first letter.`, 'error');
            return false;
        }
    }

    return true;
}

function isSingleCapitalLetter(value) {
    return /^[A-Z]$/.test(value);
}

function hasThreeConsecutiveLetters(str) {
    const lowerStr = str.toLowerCase();
    return /(.)\1\1/.test(lowerStr);
}

function hasDoubleSpaces(name) {
    return /\s\s/.test(name);
}

function validateExtension(extension) {
    const pattern = /^(Jr|Sr|M{0,3}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))$/;
    return pattern.test(extension);
}

// ==================== NOTIFICATION SYSTEM ====================

function showNotification(message, type = 'info') {
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(container);
    }

    const notification = document.createElement('div');
    notification.style.cssText = `
        background: ${type === 'error' ? '#f44336' : type === 'success' ? '#4CAF50' : '#2196F3'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
        font-size: 14px;
        min-width: 300px;
    `;

    const icon = type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️';
    notification.innerHTML = `${icon} ${message}`;

    container.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 4000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ==================== ID NUMBER FORMATTING ====================

document.getElementById('id_no').addEventListener('input', function () {
    let value = this.value.replace(/-/g, '');
    
    if (value.length > 4) {
        this.value = value.slice(0, 4) + '-' + value.slice(4, 8);
    } else {
        this.value = value;
    }
    
    // Limit to 9 characters (XXXX-XXXX)
    if (this.value.length > 9) {
        this.value = this.value.slice(0, 9);
    }
});

// ==================== FORM VALIDATION ====================

function validateForm(event) {
    const id_no = document.getElementById('id_no').value;
    const f_name = document.getElementById('f_name').value.trim();
    const m_initial = document.getElementById('m_initial').value.trim();
    const l_name = document.getElementById('l_name').value.trim();
    const extension = document.getElementById('extension').value.trim();
    const birthday = document.getElementById('birthday').value;
    const age = document.getElementById('age').value;
    const sex = document.getElementById('sex').value;
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const email = document.getElementById('email').value.trim();
    const role = document.getElementById('role').value;
    const purok = document.getElementById('purok').value.trim();
    const barangay = document.getElementById('barangay').value.trim();
    const city = document.getElementById('city').value.trim();
    const province = document.getElementById('province').value.trim();
    const country = document.getElementById('country').value.trim();
    const zipcode = document.getElementById('zipcode').value.trim();

    // ID Number validation
    if (id_no.length === 0) {
        showNotification("Error: Must contain a unique ID number in this format: XXXX-XXXX", 'error');
        document.getElementById('id_no').focus();
        return false;
    }
    if (!validateIdNumber(id_no)) {
        showNotification('Error: ID Number must contain numeric value and a dash only. Use this format: XXXX-XXXX', 'error');
        document.getElementById('id_no').focus();
        return false;
    }

    // FIRST NAME validation
    if (f_name.length === 0) {
        showNotification("Error: First name must be filled", 'error');
        document.getElementById('f_name').focus();
        return false;
    }
    if (f_name.length < 2 || f_name.length >= 20) {
        showNotification('Error: Firstname must contain at least 2 and less than 20 characters', 'error');
        document.getElementById('f_name').focus();
        return false;
    }
    if (hasNumberInName(f_name)) {
        showNotification("Error: First Name must not contain numbers", 'error');
        document.getElementById('f_name').focus();
        return false;
    }
    if (hasThreeConsecutiveLetters(f_name)) {
        showNotification("Error: First name must not contain 3 consecutive identical letters", 'error');
        document.getElementById('f_name').focus();
        return false;
    }
    if (!isCapitalized(f_name, "First Name")) {
        document.getElementById('f_name').focus();
        return false;
    }

    // MIDDLE INITIAL validation (optional)
    if (m_initial && !isSingleCapitalLetter(m_initial)) {
        showNotification("Error: Middle initial must be a single capital letter only.", 'error');
        document.getElementById('m_initial').focus();
        return false;
    }

    // LAST NAME validation
    if (l_name.length === 0) {
        showNotification("Error: Last name must be filled", 'error');
        document.getElementById('l_name').focus();
        return false;
    }
    if (l_name.length < 2 || l_name.length >= 20) {
        showNotification('Error: Lastname must contain at least 2 and less than 20 characters', 'error');
        document.getElementById('l_name').focus();
        return false;
    }
    if (hasNumberInName(l_name)) {
        showNotification("Error: Last Name must not contain numbers", 'error');
        document.getElementById('l_name').focus();
        return false;
    }
    if (!isCapitalized(l_name, "Last Name")) {
        document.getElementById('l_name').focus();
        return false;
    }
    if (hasThreeConsecutiveLetters(l_name)) {
        showNotification("Error: Last name must not contain 3 consecutive identical letters", 'error');
        document.getElementById('l_name').focus();
        return false;
    }

    // EXTENSION NAME validation
    if (extension && !validateExtension(extension)) {
        showNotification("Error: Extension Name accepts Jr/Sr and roman numerals only", 'error');
        document.getElementById('extension').focus();
        return false;
    }

    // BIRTHDAY validation
    if (birthday.length === 0) {
        showNotification("Error: Input valid birthday", 'error');
        document.getElementById('birthday').focus();
        return false;
    }
    if (!validateAge()) {
        return false;
    }

    // SEX validation
    if (sex.length === 0) {
        showNotification("Error: Please select sex", 'error');
        document.getElementById('sex').focus();
        return false;
    }

    // USERNAME validation
    if (username.length === 0) {
        showNotification("Error: Input a valid Username", 'error');
        document.getElementById('username').focus();
        return false;
    }
    if (hasDoubleSpaces(username)) {
        showNotification("Error: Username must not contain double space", 'error');
        document.getElementById('username').focus();
        return false;
    }
    if (username.length < 5 || username.length >= 20) {
        showNotification('Error: Username must contain at least 5 but less than 20 characters', 'error');
        document.getElementById('username').focus();
        return false;
    }
    if (/^[0-9]/.test(username)) {
        showNotification("Error: Username must not start with a number.", 'error');
        document.getElementById('username').focus();
        return false;
    }
    if (!/^[a-z][a-z0-9._]*$/.test(username)) {
        showNotification("Error: Username must start with lowercase letter and contain only lowercase letters, numbers, dots, and underscores.", 'error');
        document.getElementById('username').focus();
        return false;
    }

    // PASSWORD validation
    if (password.length === 0) {
        showNotification("Error: Input valid password", 'error');
        document.getElementById('password').focus();
        return false;
    }
    if (password.length < 8 || password.length >= 20) {
        showNotification('Error: Password must contain at least 8 and less than 20 characters', 'error');
        document.getElementById('password').focus();
        return false;
    }

    // EMAIL validation
    if (email.length === 0) {
        showNotification("Error: Must input email", 'error');
        document.getElementById('email').focus();
        return false;
    }

    // ROLE validation
    if (role.length === 0) {
        showNotification("Error: Please select a role for the user", 'error');
        document.getElementById('role').focus();
        return false;
    }

    // PUROK validation
    if (purok.length === 0) {
        showNotification("Error: Purok must be filled", 'error');
        document.getElementById('purok').focus();
        return false;
    }
    if (purok.length >= 20) {
        showNotification('Error: Purok must contain less than 20 characters', 'error');
        document.getElementById('purok').focus();
        return false;
    }
    // Allow single-digit numbers
    if (!/^\d$/.test(purok)) {
        if (!isCapitalized(purok, "Purok")) {
            document.getElementById('purok').focus();
            return false;
        }
    }
    if (hasThreeConsecutiveLetters(purok)) {
        showNotification("Error: Purok must not contain 3 consecutive identical letters", 'error');
        document.getElementById('purok').focus();
        return false;
    }

    // BARANGAY validation
    if (barangay.length === 0) {
        showNotification("Error: Barangay must be filled", 'error');
        document.getElementById('barangay').focus();
        return false;
    }
    if (barangay.length < 2 || barangay.length >= 20) {
        showNotification('Error: Barangay must contain at least 2 and less than 20 characters', 'error');
        document.getElementById('barangay').focus();
        return false;
    }
    if (!isCapitalized(barangay, "Barangay")) {
        document.getElementById('barangay').focus();
        return false;
    }
    if (hasThreeConsecutiveLetters(barangay)) {
        showNotification("Error: Barangay must not contain 3 consecutive identical letters", 'error');
        document.getElementById('barangay').focus();
        return false;
    }

    // CITY validation
    if (city.length === 0) {
        showNotification("Error: City must be filled", 'error');
        document.getElementById('city').focus();
        return false;
    }
    if (city.length < 2 || city.length >= 20) {
        showNotification('Error: City must contain at least 2 and less than 20 characters', 'error');
        document.getElementById('city').focus();
        return false;
    }
    if (!isCapitalized(city, "City")) {
        document.getElementById('city').focus();
        return false;
    }
    if (hasThreeConsecutiveLetters(city)) {
        showNotification("Error: City must not contain 3 consecutive identical letters", 'error');
        document.getElementById('city').focus();
        return false;
    }

    // PROVINCE validation
    if (province.length === 0) {
        showNotification("Error: Province must be filled", 'error');
        document.getElementById('province').focus();
        return false;
    }
    if (province.length < 2 || province.length >= 20) {
        showNotification('Error: Province must contain at least 2 and less than 20 characters', 'error');
        document.getElementById('province').focus();
        return false;
    }
    if (!isCapitalized(province, "Province")) {
        document.getElementById('province').focus();
        return false;
    }
    if (hasThreeConsecutiveLetters(province)) {
        showNotification("Error: Province must not contain 3 consecutive identical letters", 'error');
        document.getElementById('province').focus();
        return false;
    }

    // COUNTRY validation
    if (country.length === 0) {
        showNotification("Error: Country must be filled", 'error');
        document.getElementById('country').focus();
        return false;
    }
    if (country.length < 2 || country.length >= 20) {
        showNotification('Error: Country must contain at least 2 and less than 20 characters', 'error');
        document.getElementById('country').focus();
        return false;
    }
    if (!isCapitalized(country, "Country")) {
        document.getElementById('country').focus();
        return false;
    }
    if (hasThreeConsecutiveLetters(country)) {
        showNotification("Error: Country must not contain 3 consecutive identical letters", 'error');
        document.getElementById('country').focus();
        return false;
    }

    // ZIPCODE validation
    if (zipcode.length === 0) {
        showNotification("Error: Zipcode must be filled", 'error');
        document.getElementById('zipcode').focus();
        return false;
    }
    if (!isNumeric(zipcode)) {
        showNotification("Zipcode must contain numeric value only", 'error');
        document.getElementById('zipcode').focus();
        return false;
    }
    if (!/^\d{4}$/.test(zipcode)) {
        showNotification('Error: Zipcode must contain exactly 4 digits.', 'error');
        document.getElementById('zipcode').focus();
        return false;
    }

    // SECURITY QUESTIONS validation
    const sec_q1 = document.getElementById("sec_q1").value;
    const sec_q2 = document.getElementById("sec_q2").value;
    const sec_q3 = document.getElementById("sec_q3").value;
    const sec_a1 = document.getElementById("sec_a1").value.trim();
    const sec_a2 = document.getElementById("sec_a2").value.trim();
    const sec_a3 = document.getElementById("sec_a3").value.trim();

    if (sec_q1 === "" || sec_q2 === "" || sec_q3 === "") {
        showNotification("Error: Please select all 3 security questions.", 'error');
        document.getElementById('sec_q1').focus();
        return false;
    }

    if (sec_q1 === sec_q2 || sec_q1 === sec_q3 || sec_q2 === sec_q3) {
        showNotification("Error: Security questions must be unique. Do not choose the same question twice.", 'error');
        document.getElementById('sec_q1').focus();
        return false;
    }

    if (sec_a1.length === 0 || sec_a2.length === 0 || sec_a3.length === 0) {
        showNotification("Error: Please answer all 3 security questions.", 'error');
        document.getElementById('sec_a1').focus();
        return false;
    }

    if (hasDoubleSpaces(sec_a1) || hasDoubleSpaces(sec_a2) || hasDoubleSpaces(sec_a3)) {
        showNotification("Error: Security answers must not contain double spaces.", 'error');
        document.getElementById('sec_a1').focus();
        return false;
    }

    if (hasThreeConsecutiveLetters(sec_a1) || hasThreeConsecutiveLetters(sec_a2) || hasThreeConsecutiveLetters(sec_a3)) {
        showNotification("Error: Security answers must not contain 3 consecutive identical letters.", 'error');
        document.getElementById('sec_a1').focus();
        return false;
    }

    return true;
}

// ==================== EXISTENCE CHECKS ====================

function existingID() {
    const id_no = document.getElementById('id_no').value.trim();

    if (id_no.length === 0) return;

    fetch('../phpdb/db_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'type=id_no&value=' + encodeURIComponent(id_no),
    })
    .then(response => response.text())
    .then(data => {
        const idField = document.getElementById('id_no');
        if (data === 'id_exists') {
            showNotification('ID number already exists. Input unique ID Number.', 'error');
            idField.value = '';
            idField.setCustomValidity('ID number already exists.');
            idField.focus();
        } else {
            idField.setCustomValidity('');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function existingUsername() {
    const username = document.getElementById('username').value.trim();

    if (username.length === 0) {
        document.getElementById('username').setCustomValidity('Please enter a username.');
        return;
    }

    fetch('../phpdb/db_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'type=username&value=' + encodeURIComponent(username),
    })
    .then(response => response.text())
    .then(data => {
        const usernameField = document.getElementById('username');
        if (data === 'username_exists') {
            showNotification('Username is already taken. Please choose another.', 'error');
            usernameField.value = '';
            usernameField.setCustomValidity('Username is already taken.');
            usernameField.focus();
        } else {
            usernameField.setCustomValidity('');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function existingEmail() {
    const email = document.getElementById('email').value.trim();

    if (email.length === 0) {
        document.getElementById('email').setCustomValidity('Please enter an email.');
        return;
    }

    fetch('../phpdb/db_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'type=email&value=' + encodeURIComponent(email),
    })
    .then(response => response.text())
    .then(data => {
        const emailField = document.getElementById('email');
        if (data === 'email_exists') {
            showNotification('Email is already taken. Please choose another.', 'error');
            emailField.value = '';
            emailField.setCustomValidity('Email is already taken.');
            emailField.focus();
        } else {
            emailField.setCustomValidity('');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function existingPassword() {
    const password = document.getElementById('password').value.trim();

    if (password.length === 0) return;

    fetch('../phpdb/db_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'type=password&value=' + encodeURIComponent(password),
    })
    .then(response => response.text())
    .then(data => {
        const passwordField = document.getElementById('password');
        if (data === 'password_exists') {
            showNotification('Password already exists. Please input another valid password.', 'error');
            passwordField.value = '';
            passwordField.setCustomValidity('Password already exists.');
            passwordField.focus();
        } else {
            passwordField.setCustomValidity('');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// ==================== AGE VALIDATION ====================

function validateAge() {
    const birthdayInput = document.getElementById('birthday').value;
    const ageMessage = document.getElementById('ageMessage');
    const minAge = 18;

    if (!birthdayInput) {
        ageMessage.textContent = '';
        return false;
    }

    const birthday = new Date(birthdayInput);
    const age = calculateAge(birthday);

    if (age < minAge) {
        ageMessage.textContent = 'User is underage. Not allowed to register.';
        ageMessage.style.color = 'red';
        return false;
    } else {
        ageMessage.textContent = '';
        return true;
    }
}

function calculateAge(birthday) {
    const today = new Date();
    let age = today.getFullYear() - birthday.getFullYear();
    const monthDifference = today.getMonth() - birthday.getMonth();

    if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthday.getDate())) {
        age--;
    }
    return age;
}

document.getElementById('birthday').addEventListener('change', function () {
    const birthday = new Date(this.value);
    const age = calculateAge(birthday);
    document.getElementById('age').value = age;
    document.getElementById('ageHidden').value = age;
    validateAge();
});

document.getElementById('birthday').addEventListener('input', function() {
    validateAge();
});

// ==================== PASSWORD FUNCTIONS ====================

function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthText = document.getElementById('pwStrength');
    
    const regexWeak = /^[a-zA-Z0-9]{6,}$/;
    const regexStrong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;

    if (password.length === 0) {
        strengthText.innerHTML = '';
        return;
    }

    if (!regexWeak.test(password)) {
        strengthText.innerHTML = 'Weak password. Must be at least 8 characters long and alphanumeric.';
        strengthText.style.color = 'red';
    } else if (regexStrong.test(password)) {
        strengthText.innerHTML = 'Strong password. Alphanumeric with at least 8 characters.';
        strengthText.style.color = 'green';
    } else {
        strengthText.innerHTML = 'Moderate password. Use more characters for a stronger password.';
        strengthText.style.color = 'orange';
    }
}

let passwordMatchTimeout;

function checkPasswordMatch() {
    clearTimeout(passwordMatchTimeout);

    passwordMatchTimeout = setTimeout(() => {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const matchText = document.getElementById('pwMatch');

        if (confirmPassword.length === 0) {
            matchText.innerHTML = '';
            return;
        }

        if (password !== confirmPassword) {
            matchText.innerHTML = 'Passwords do not match.';
            matchText.style.color = 'red';
            document.getElementById('confirmPassword').setCustomValidity('Passwords do not match');
        } else {
            matchText.innerHTML = 'Passwords match!';
            matchText.style.color = 'green';
            document.getElementById('confirmPassword').setCustomValidity('');
        }
    }, 500);
}

// ==================== SECURITY QUESTIONS ====================

function updateSecurityDropdowns() {
    const q1 = document.getElementById("sec_q1");
    const q2 = document.getElementById("sec_q2");
    const q3 = document.getElementById("sec_q3");

    const selectedValues = [q1.value, q2.value, q3.value];

    [q1, q2, q3].forEach(dropdown => {
        Array.from(dropdown.options).forEach(option => {
            if (option.value === "") return;

            if (selectedValues.includes(option.value) && option.value !== dropdown.value) {
                option.disabled = true;
            } else {
                option.disabled = false;
            }
        });
    });
}

function validateSecurityQuestions() {
    const q1 = document.getElementById("sec_q1").value;
    const q2 = document.getElementById("sec_q2").value;
    const q3 = document.getElementById("sec_q3").value;

    if (q1 === "" || q2 === "" || q3 === "") {
        return false;
    }

    if (q1 === q2 || q1 === q3 || q2 === q3) {
        showNotification("Error: Security questions must be unique. Do not choose the same question twice.", 'error');
        return false;
    }

    return true;
}

// ==================== EVENT LISTENERS ====================

document.getElementById('id_no').addEventListener('input', existingID);
document.getElementById('username').addEventListener('blur', existingUsername);
document.getElementById('email').addEventListener('blur', existingEmail);
document.getElementById('password').addEventListener('input', checkPasswordStrength);
document.getElementById('password').addEventListener('input', existingPassword);
document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);

document.getElementById("sec_q1").addEventListener("change", updateSecurityDropdowns);
document.getElementById("sec_q2").addEventListener("change", updateSecurityDropdowns);
document.getElementById("sec_q3").addEventListener("change", updateSecurityDropdowns);

// ==================== FORM SUBMISSION ====================

document.getElementById('form').addEventListener('submit', function(event) {
    event.preventDefault();

    if (!validateForm(event)) {
        return;
    }

    if (!validateSecurityQuestions()) {
        return;
    }

    // Show loading state
    const submitBtn = document.querySelector('.btn-submit');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
    submitBtn.disabled = true;

    const formData = new FormData(document.getElementById('form'));

    fetch('admin_register_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            // Handle redirect
            const url = new URL(response.url);
            const params = new URLSearchParams(url.search);
            
            if (params.get('success') === '1') {
                const role = params.get('role');
                const previousBlocked = params.get('previous_blocked');
                
                if (previousBlocked === '1') {
                    showNotification(`✅ New ${role} registered successfully! Previous Super Admin has been blocked.`, 'success');
                } else {
                    showNotification(`✅ Successfully registered as ${role}!`, 'success');
                }
                
                document.getElementById('form').reset();
                
                // Reset form fields
                document.getElementById('age').value = '';
                document.getElementById('pwStrength').innerHTML = '';
                document.getElementById('pwMatch').innerHTML = '';
                document.getElementById('ageMessage').textContent = '';
                updateSecurityDropdowns();
                
                setTimeout(() => {
                    window.location.href = 'admin_register.php';
                }, 2000);
            } else if (params.get('error')) {
                let errorMsg = 'Registration failed.';
                switch(params.get('error')) {
                    case 'id_exists':
                        errorMsg = '❌ ID number already exists.';
                        break;
                    case 'username_exists':
                        errorMsg = '❌ Username already exists.';
                        break;
                    case 'email_exists':
                        errorMsg = '❌ Email already exists.';
                        break;
                    case 'invalid_role':
                        errorMsg = '❌ You do not have permission to create this role.';
                        break;
                    case 'insert_failed':
                        errorMsg = '❌ Database error. Please try again.';
                        break;
                    case 'prepare_failed':
                        errorMsg = '❌ System error. Please try again.';
                        break;
                    default:
                        errorMsg = '❌ Registration failed. Please try again.';
                }
                showNotification(errorMsg, 'error');
            }
        } else {
            return response.text();
        }
    })
    .then(text => {
        if (text) {
            console.log('Server response:', text);
            if (text.includes('error') || text.includes('Error')) {
                showNotification('❌ An error occurred during registration.', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('❌ There was an error submitting the form. Please check your connection.', 'error');
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// ==================== RESET FUNCTIONALITY ====================

document.querySelector('.btn-reset').addEventListener('click', function(e) {
    e.preventDefault();
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        document.getElementById('form').reset();
        document.getElementById('age').value = '';
        document.getElementById('pwStrength').innerHTML = '';
        document.getElementById('pwMatch').innerHTML = '';
        document.getElementById('ageMessage').textContent = '';
        updateSecurityDropdowns();
        showNotification('Form has been reset.', 'info');
    }
});

// ==================== PAGE LOAD INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize security dropdowns
    updateSecurityDropdowns();
    
    // Check for URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('success') === '1') {
        const role = urlParams.get('role');
        const previousBlocked = urlParams.get('previous_blocked');
        
        if (previousBlocked === '1') {
            showNotification(`✅ New ${role} registered successfully! Previous Super Admin has been blocked.`, 'success');
        } else {
            showNotification(`✅ User successfully registered as ${role}!`, 'success');
        }
        
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (urlParams.get('error')) {
        let errorMsg = '';
        switch(urlParams.get('error')) {
            case 'id_exists':
                errorMsg = '❌ ID number already exists.';
                break;
            case 'username_exists':
                errorMsg = '❌ Username already exists.';
                break;
            case 'email_exists':
                errorMsg = '❌ Email already exists.';
                break;
            case 'invalid_role':
                errorMsg = '❌ You do not have permission to create this role.';
                break;
            case 'insert_failed':
                errorMsg = '❌ Database error. Please try again.';
                break;
            case 'prepare_failed':
                errorMsg = '❌ System error. Please try again.';
                break;
            default:
                errorMsg = '❌ Registration failed. Please try again.';
        }
        showNotification(errorMsg, 'error');
        
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// ==================== ROLE SELECTOR TOOLTIP ====================

const roleSelect = document.getElementById('role');
if (roleSelect) {
    roleSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            let description = '';
            switch(selectedOption.value) {
                case 'super_admin':
                    description = 'Super Admin: Full system access, can manage all accounts and settings. WARNING: Registering a new Super Admin will block the previous one!';
                    break;
                case 'admin':
                    description = 'Admin: Can manage users and view reports';
                    break;
                case 'user':
                    description = 'User: Can track meditation activities only';
                    break;
            }
            if (description) {
                showNotification(description, 'info');
            }
        }
    });
}