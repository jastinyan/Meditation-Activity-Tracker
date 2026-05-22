// DOM Elements
const editProfileBtn = document.getElementById('editProfileBtn');
const editModal = document.getElementById('editProfileModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelEditBtn = document.getElementById('cancelEditBtn');
const editForm = document.getElementById('editProfileForm');
const successToast = document.getElementById('successToast');
const errorToast = document.getElementById('errorToast');

// Display elements
const displayFullName = document.getElementById('displayFullName');
const displayUsername = document.getElementById('displayUsername');
const displayIdNo = document.getElementById('displayIdNo');
const totalSessionsEl = document.getElementById('totalSessions');
const totalTimeEl = document.getElementById('totalTime');
const currentStreakEl = document.getElementById('currentStreak');

// Info sections
const personalInfo = document.getElementById('personalInfo');
const addressInfo = document.getElementById('addressInfo');
const accountInfo = document.getElementById('accountInfo');

// ==================== VALIDATION FUNCTIONS ====================

function hasNumberInName(name) {
    return /\d/.test(name);
}

function hasThreeConsecutiveLetters(str) {
    if (!str) return false;
    const lowerStr = str.toLowerCase();
    return /(.)\1\1/.test(lowerStr);
}

function hasDoubleSpaces(str) {
    if (!str) return false;
    return /\s\s/.test(str);
}

function isCapitalized(str, fieldName) {
    if (!str || str.trim() === '') return true; // Skip empty strings
    
    // Check for double spaces
    if (hasDoubleSpaces(str)) {
        showError(`${fieldName} must not contain double spaces.`);
        return false;
    }
    
    const words = str.trim().split(' ');

    // Check the first word
    if (words.length > 0) {
        const firstWord = words[0];
        if (firstWord.length === 0) return true;

        // Ensure the first letter is capitalized
        if (firstWord.charAt(0) !== firstWord.charAt(0).toUpperCase()) {
            showError(`${fieldName}: First letter of the first word must be capitalized.`);
            return false;
        }
        
        // Check if the entire word is all uppercase (allow single letters)
        if (firstWord.length > 1 && firstWord === firstWord.toUpperCase()) {
            showError(`${fieldName}: First word must not be in all capital letters.`);
            return false;
        }
        
        const restOfFirstWord = firstWord.slice(1);
        if (restOfFirstWord.length > 0 && restOfFirstWord !== restOfFirstWord.toLowerCase()) {
            showError(`${fieldName}: First word must not contain uppercase letters after the first letter.`);
            return false;
        }
    }

    // Check the second word if it exists
    if (words.length > 1) {
        const secondWord = words[1];
        if (secondWord.length === 0) return true;

        // Ensure the first letter is capitalized
        if (secondWord.charAt(0) !== secondWord.charAt(0).toUpperCase()) {
            showError(`${fieldName}: First letter of the second word must be capitalized.`);
            return false;
        }
        
        // Check if the entire word is all uppercase
        if (secondWord.length > 1 && secondWord === secondWord.toUpperCase()) {
            showError(`${fieldName}: Second word must not be in all capital letters.`);
            return false;
        }
        
        const restOfSecondWord = secondWord.slice(1);
        if (restOfSecondWord.length > 0 && restOfSecondWord !== restOfSecondWord.toLowerCase()) {
            showError(`${fieldName}: Second word must not contain uppercase letters after the first letter.`);
            return false;
        }
    }

    return true;
}

function isSingleCapitalLetter(value) {
    if (!value) return true;
    return /^[A-Z]$/.test(value);
}

function validateExtension(extension) {
    if (!extension) return true;
    const pattern = /^(Jr|Sr|M{0,3}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))$/;
    return pattern.test(extension);
}

function isNumeric(value) {
    return /^[0-9]+$/.test(value);
}

function validateAge() {
    const birthday = document.getElementById('editBirthday').value;
    const ageInput = document.getElementById('editAge');
    
    if (birthday) {
        const birthDate = new Date(birthday);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        ageInput.value = age;
        
        if (age < 18) {
            showError('You must be at least 18 years old.');
            return false;
        }
    }
    return true;
}

// ==================== FORM VALIDATION ====================

function validateProfileForm() {
    console.log("=== Starting Profile Validation ===");
    
    // Get form elements with null checks
    const fNameInput = document.getElementById('editFName');
    const mInitialInput = document.getElementById('editMInitial');
    const lNameInput = document.getElementById('editLName');
    const extensionInput = document.getElementById('editExtension');
    const birthdayInput = document.getElementById('editBirthday');
    const sexInput = document.getElementById('editSex');
    const emailInput = document.getElementById('editEmail');
    const purokInput = document.getElementById('editPurok');
    const barangayInput = document.getElementById('editBarangay');
    const cityInput = document.getElementById('editCity');
    const provinceInput = document.getElementById('editProvince');
    const countryInput = document.getElementById('editCountry');
    const zipcodeInput = document.getElementById('editZipCode');

    // Check if elements exist
    if (!fNameInput || !lNameInput || !birthdayInput || !sexInput || !emailInput) {
        console.error("Required form elements not found");
        showError("Form elements not found. Please refresh the page.");
        return false;
    }

    // Get values
    const f_name = fNameInput.value ? fNameInput.value.trim() : '';
    const m_initial = mInitialInput ? mInitialInput.value.trim() : '';
    const l_name = lNameInput.value ? lNameInput.value.trim() : '';
    const extension = extensionInput ? extensionInput.value.trim() : '';
    const birthday = birthdayInput.value || '';
    const sex = sexInput.value || '';
    const email = emailInput.value ? emailInput.value.trim() : '';
    const purok = purokInput ? purokInput.value.trim() : '';
    const barangay = barangayInput ? barangayInput.value.trim() : '';
    const city = cityInput ? cityInput.value.trim() : '';
    const province = provinceInput ? provinceInput.value.trim() : '';
    const country = countryInput ? countryInput.value.trim() : '';
    const zipcode = zipcodeInput ? zipcodeInput.value.trim() : '';

    console.log("Form values:", { f_name, l_name, email, birthday, sex, purok });

    // FIRST NAME validation
    if (!f_name || f_name.length === 0) {
        console.error("First name is empty");
        showError("First name is required");
        fNameInput.focus();
        return false;
    }
    
    if (f_name.length < 2 || f_name.length >= 20) {
        console.error("First name length invalid:", f_name.length);
        showError('First name must be between 2 and 20 characters');
        fNameInput.focus();
        return false;
    }
    
    if (hasNumberInName(f_name)) {
        console.error("First name contains numbers:", f_name);
        showError("First name must not contain numbers");
        fNameInput.focus();
        return false;
    }
    
    if (hasThreeConsecutiveLetters(f_name)) {
        console.error("First name has 3 consecutive letters:", f_name);
        showError("First name must not contain 3 consecutive identical letters");
        fNameInput.focus();
        return false;
    }
    
    if (!isCapitalized(f_name, "First name")) {
        console.error("First name capitalization failed:", f_name);
        fNameInput.focus();
        return false;
    }

    // MIDDLE INITIAL validation (optional)
    if (m_initial && m_initial.length > 0 && !isSingleCapitalLetter(m_initial)) {
        console.error("Middle initial invalid:", m_initial);
        showError("Middle initial must be a single capital letter only");
        if (mInitialInput) mInitialInput.focus();
        return false;
    }

    // LAST NAME validation
    if (!l_name || l_name.length === 0) {
        console.error("Last name is empty");
        showError("Last name is required");
        lNameInput.focus();
        return false;
    }
    
    if (l_name.length < 2 || l_name.length >= 20) {
        console.error("Last name length invalid:", l_name.length);
        showError('Last name must be between 2 and 20 characters');
        lNameInput.focus();
        return false;
    }
    
    if (hasNumberInName(l_name)) {
        console.error("Last name contains numbers:", l_name);
        showError("Last name must not contain numbers");
        lNameInput.focus();
        return false;
    }
    
    if (!isCapitalized(l_name, "Last name")) {
        console.error("Last name capitalization failed:", l_name);
        lNameInput.focus();
        return false;
    }
    
    if (hasThreeConsecutiveLetters(l_name)) {
        console.error("Last name has 3 consecutive letters:", l_name);
        showError("Last name must not contain 3 consecutive identical letters");
        lNameInput.focus();
        return false;
    }

    // EXTENSION validation
    if (extension && extension.length > 0 && !validateExtension(extension)) {
        console.error("Extension invalid:", extension);
        showError("Extension name accepts Jr/Sr and Roman numerals only");
        if (extensionInput) extensionInput.focus();
        return false;
    }

    // BIRTHDAY validation
    if (!birthday) {
        console.error("Birthday is empty");
        showError("Birthday is required");
        birthdayInput.focus();
        return false;
    }
    
    if (!validateAge()) {
        console.error("Age validation failed");
        return false;
    }

    // SEX validation
    if (!sex) {
        console.error("Sex is empty");
        showError("Please select sex");
        sexInput.focus();
        return false;
    }

    // EMAIL validation
    if (!email) {
        console.error("Email is empty");
        showError("Email is required");
        emailInput.focus();
        return false;
    }
    
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        console.error("Email format invalid:", email);
        showError("Invalid email format");
        emailInput.focus();
        return false;
    }

    // PUROK validation
    if (!purok) {
        console.error("Purok is empty");
        showError("Purok/Street is required");
        if (purokInput) purokInput.focus();
        return false;
    }
    
    if (purok.length >= 20) {
        console.error("Purok length too long:", purok.length);
        showError("Purok must be less than 20 characters");
        if (purokInput) purokInput.focus();
        return false;
    }
    
    // Allow single-digit numbers
    if (!/^\d$/.test(purok)) {
        if (!isCapitalized(purok, "Purok")) {
            console.error("Purok capitalization failed:", purok);
            if (purokInput) purokInput.focus();
            return false;
        }
    }
    
    if (hasThreeConsecutiveLetters(purok)) {
        console.error("Purok has 3 consecutive letters:", purok);
        showError("Purok must not contain 3 consecutive identical letters");
        if (purokInput) purokInput.focus();
        return false;
    }

    // BARANGAY validation
    if (!barangay) {
        console.error("Barangay is empty");
        showError("Barangay is required");
        if (barangayInput) barangayInput.focus();
        return false;
    }
    
    if (barangay.length < 2 || barangay.length >= 20) {
        console.error("Barangay length invalid:", barangay.length);
        showError("Barangay must be between 2 and 20 characters");
        if (barangayInput) barangayInput.focus();
        return false;
    }
    
    if (!isCapitalized(barangay, "Barangay")) {
        console.error("Barangay capitalization failed:", barangay);
        if (barangayInput) barangayInput.focus();
        return false;
    }
    
    if (hasThreeConsecutiveLetters(barangay)) {
        console.error("Barangay has 3 consecutive letters:", barangay);
        showError("Barangay must not contain 3 consecutive identical letters");
        if (barangayInput) barangayInput.focus();
        return false;
    }

    // CITY validation
    if (!city) {
        console.error("City is empty");
        showError("City is required");
        if (cityInput) cityInput.focus();
        return false;
    }
    
    if (city.length < 2 || city.length >= 20) {
        console.error("City length invalid:", city.length);
        showError("City must be between 2 and 20 characters");
        if (cityInput) cityInput.focus();
        return false;
    }
    
    if (!isCapitalized(city, "City")) {
        console.error("City capitalization failed:", city);
        if (cityInput) cityInput.focus();
        return false;
    }
    
    if (hasThreeConsecutiveLetters(city)) {
        console.error("City has 3 consecutive letters:", city);
        showError("City must not contain 3 consecutive identical letters");
        if (cityInput) cityInput.focus();
        return false;
    }

    // PROVINCE validation
    if (!province) {
        console.error("Province is empty");
        showError("Province is required");
        if (provinceInput) provinceInput.focus();
        return false;
    }
    
    if (province.length < 2 || province.length >= 20) {
        console.error("Province length invalid:", province.length);
        showError("Province must be between 2 and 20 characters");
        if (provinceInput) provinceInput.focus();
        return false;
    }
    
    if (!isCapitalized(province, "Province")) {
        console.error("Province capitalization failed:", province);
        if (provinceInput) provinceInput.focus();
        return false;
    }
    
    if (hasThreeConsecutiveLetters(province)) {
        console.error("Province has 3 consecutive letters:", province);
        showError("Province must not contain 3 consecutive identical letters");
        if (provinceInput) provinceInput.focus();
        return false;
    }

    // COUNTRY validation
    if (!country) {
        console.error("Country is empty");
        showError("Country is required");
        if (countryInput) countryInput.focus();
        return false;
    }
    
    if (country.length < 2 || country.length >= 20) {
        console.error("Country length invalid:", country.length);
        showError("Country must be between 2 and 20 characters");
        if (countryInput) countryInput.focus();
        return false;
    }
    
    if (!isCapitalized(country, "Country")) {
        console.error("Country capitalization failed:", country);
        if (countryInput) countryInput.focus();
        return false;
    }
    
    if (hasThreeConsecutiveLetters(country)) {
        console.error("Country has 3 consecutive letters:", country);
        showError("Country must not contain 3 consecutive identical letters");
        if (countryInput) countryInput.focus();
        return false;
    }

    // ZIPCODE validation
    if (!zipcode) {
        console.error("Zipcode is empty");
        showError("Zip code is required");
        if (zipcodeInput) zipcodeInput.focus();
        return false;
    }
    
    if (!isNumeric(zipcode)) {
        console.error("Zipcode not numeric:", zipcode);
        showError("Zip code must contain numbers only");
        if (zipcodeInput) zipcodeInput.focus();
        return false;
    }
    
    if (!/^\d{4}$/.test(zipcode)) {
        console.error("Zipcode length/format invalid:", zipcode);
        showError("Zip code must contain exactly 4 digits");
        if (zipcodeInput) zipcodeInput.focus();
        return false;
    }

    console.log("=== All validation passed ===");
    return true;
}

// ==================== INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM loaded, initializing profile...");
    loadProfileData();
    loadProfileStats();
    setupEventListeners();
});

function setupEventListeners() {
    console.log("Setting up event listeners");
    
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', openEditModal);
    }
    
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }
    
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', closeModal);
    }
    
    // Click outside modal to close
    window.addEventListener('click', (e) => {
        if (e.target === editModal) {
            closeModal();
        }
    });
    
    // Calculate age when birthday changes
    const birthdayInput = document.getElementById('editBirthday');
    if (birthdayInput) {
        birthdayInput.addEventListener('change', validateAge);
    }
    
    // Form submission
    if (editForm) {
        editForm.addEventListener('submit', handleProfileUpdate);
    }
}

// Calculate age from birthday
function calculateAge() {
    const birthday = document.getElementById('editBirthday').value;
    if (birthday) {
        const birthDate = new Date(birthday);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        document.getElementById('editAge').value = age;
    }
}

async function loadProfileData() {
    try {
        console.log("Loading profile data...");
        const response = await fetch('fetch_profile.php');
        const data = await response.json();
        
        if (data.success) {
            console.log("Profile data loaded:", data.profile);
            displayProfileData(data.profile);
            populateEditForm(data.profile);
        } else {
            console.error("Failed to load profile:", data.message);
            showError('Failed to load profile data');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showError('Error loading profile data');
    }
}

async function loadProfileStats() {
    try {
        const response = await fetch('fetch_profile_stats.php');
        const data = await response.json();
        
        if (data.success) {
            totalSessionsEl.textContent = data.stats.totalSessions || '0';
            totalTimeEl.textContent = data.stats.totalMinutes || '0';
            currentStreakEl.textContent = data.stats.currentStreak || '0';
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

function displayProfileData(profile) {
    // Display full name
    displayFullName.textContent = profile.full_name;
    
    // Personal Information
    const personalInfoHtml = `
        <div class="info-item">
            <span class="info-label">First Name</span>
            <span class="info-value">${escapeHtml(profile.first_name)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Middle Initial</span>
            <span class="info-value">${escapeHtml(profile.middle_initial || 'N/A')}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Last Name</span>
            <span class="info-value">${escapeHtml(profile.last_name)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Extension</span>
            <span class="info-value">${escapeHtml(profile.extension_name || 'N/A')}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Birthday</span>
            <span class="info-value">${escapeHtml(profile.birthday)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Age</span>
            <span class="info-value">${escapeHtml(profile.age)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Sex</span>
            <span class="info-value">${escapeHtml(profile.sex)}</span>
        </div>
    `;
    personalInfo.innerHTML = personalInfoHtml;
    
    // Address Information
    const addressInfoHtml = `
        <div class="info-item">
            <span class="info-label">Purok/Street</span>
            <span class="info-value">${escapeHtml(profile.purok_street)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Barangay</span>
            <span class="info-value">${escapeHtml(profile.barangay)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">City</span>
            <span class="info-value">${escapeHtml(profile.municipality_city)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Province</span>
            <span class="info-value">${escapeHtml(profile.province)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Country</span>
            <span class="info-value">${escapeHtml(profile.country)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Zip Code</span>
            <span class="info-value">${escapeHtml(profile.zip_code)}</span>
        </div>
    `;
    addressInfo.innerHTML = addressInfoHtml;
    
    // Account Information
    const createdDate = profile.created_at ? formatDate(profile.created_at) : 'N/A';
    
    const accountInfoHtml = `
        <div class="info-item">
            <span class="info-label">Username</span>
            <span class="info-value">${escapeHtml(profile.username)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Email</span>
            <span class="info-value">${escapeHtml(profile.email)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Account Created</span>
            <span class="info-value"><i class="fas fa-calendar-alt"></i> ${createdDate}</span>
        </div>
    `;
    accountInfo.innerHTML = accountInfoHtml;
}

function populateEditForm(profile) {
    console.log("Populating edit form with:", profile);
    
    const idNoField = document.getElementById('editIdNo');
    const fNameField = document.getElementById('editFName');
    const mInitialField = document.getElementById('editMInitial');
    const lNameField = document.getElementById('editLName');
    const extensionField = document.getElementById('editExtension');
    const birthdayField = document.getElementById('editBirthday');
    const ageField = document.getElementById('editAge');
    const sexField = document.getElementById('editSex');
    const usernameField = document.getElementById('editUsername');
    const emailField = document.getElementById('editEmail');
    const purokField = document.getElementById('editPurok');
    const barangayField = document.getElementById('editBarangay');
    const cityField = document.getElementById('editCity');
    const provinceField = document.getElementById('editProvince');
    const countryField = document.getElementById('editCountry');
    const zipcodeField = document.getElementById('editZipCode');
    
    if (idNoField) idNoField.value = profile.id_no || '';
    if (fNameField) fNameField.value = profile.first_name || '';
    if (mInitialField) mInitialField.value = profile.middle_initial || '';
    if (lNameField) lNameField.value = profile.last_name || '';
    if (extensionField) extensionField.value = profile.extension_name || '';
    if (birthdayField) birthdayField.value = profile.birthday || '';
    if (ageField) ageField.value = profile.age || '';
    if (sexField) sexField.value = profile.sex || '';
    if (usernameField) usernameField.value = profile.username || '';
    if (emailField) emailField.value = profile.email || '';
    if (purokField) purokField.value = profile.purok_street || '';
    if (barangayField) barangayField.value = profile.barangay || '';
    if (cityField) cityField.value = profile.municipality_city || '';
    if (provinceField) provinceField.value = profile.province || '';
    if (countryField) countryField.value = profile.country || 'Philippines';
    if (zipcodeField) zipcodeField.value = profile.zip_code || '';
    
    const editCreatedAt = document.getElementById('editCreatedAt');
    if (editCreatedAt) {
        const createdDate = profile.created_at ? formatDate(profile.created_at) : 'N/A';
        editCreatedAt.value = createdDate;
    }
}

// Handle profile update form submission
async function handleProfileUpdate(e) {
    e.preventDefault();
    console.log("Form submission started");
    
    // Validate form first
    if (!validateProfileForm()) {
        console.log("Validation failed");
        return;
    }
    
    // Show loading state
    const submitBtn = editForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(editForm);
        
        // Log form data for debugging
        console.log("Sending form data:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        const response = await fetch('update_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log("Server response:", data);
        
        if (data.success) {
            showSuccess(data.message || 'Profile updated successfully!');
            closeModal();
            // Reload profile data after a short delay
            setTimeout(() => {
                loadProfileData();
            }, 1500);
        } else {
            showError(data.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showError('Network error - please try again');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function openEditModal() {
    console.log("Opening edit modal");
    editModal.style.display = 'flex';
}

function closeModal() {
    console.log("Closing modal");
    editModal.style.display = 'none';
}

function showSuccess(message) {
    console.log("Success:", message);
    if (successToast) {
        successToast.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        successToast.classList.add('show');
        setTimeout(() => {
            successToast.classList.remove('show');
        }, 3000);
    } else {
        alert(message);
    }
}

function showError(message) {
    console.error("Error:", message);
    if (errorToast) {
        errorToast.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        errorToast.classList.add('show');
        setTimeout(() => {
            errorToast.classList.remove('show');
        }, 3000);
    } else {
        alert('Error: ' + message);
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}