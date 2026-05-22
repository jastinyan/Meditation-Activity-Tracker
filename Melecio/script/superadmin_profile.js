// DOM Elements
const displayFullName = document.getElementById('displayFullName');
const displayUsername = document.getElementById('displayUsername');
const displayIdNo = document.getElementById('displayIdNo');

const personalInfoEl = document.getElementById('personalInfo');
const addressInfoEl = document.getElementById('addressInfo');
const accountInfoEl = document.getElementById('accountInfo');

const editProfileBtn = document.getElementById('editProfileBtn');
const editProfileModal = document.getElementById('editProfileModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelEditBtn = document.getElementById('cancelEditBtn');
const editProfileForm = document.getElementById('editProfileForm');

const successToast = document.getElementById('successToast');
const errorToast = document.getElementById('errorToast');

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, starting profile load...');
    setTimeout(() => {
        loadProfileData();
    }, 100);
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    editProfileBtn.addEventListener('click', () => {
        loadProfileForEdit();
        editProfileModal.style.display = 'flex';
    });

    closeModalBtn.addEventListener('click', () => closeModal(editProfileModal));
    cancelEditBtn.addEventListener('click', () => closeModal(editProfileModal));

    window.addEventListener('click', (e) => {
        if (e.target === editProfileModal) {
            closeModal(editProfileModal);
        }
    });

    const editBirthday = document.getElementById('editBirthday');
    if (editBirthday) {
        editBirthday.addEventListener('change', calculateAge);
    }

    editProfileForm.addEventListener('submit', handleProfileUpdate);
}

// Load profile data
async function loadProfileData() {
    try {
        let userId = displayIdNo.textContent;
        
        if (!userId || userId === 'Loading...' || userId.trim() === '') {
            console.log('Waiting for user ID...');
            await new Promise(resolve => setTimeout(resolve, 500));
            userId = displayIdNo.textContent;
            
            if (!userId || userId === 'Loading...' || userId.trim() === '') {
                console.error('User ID still not available');
                showError('Unable to load user information');
                return;
            }
        }
        
        console.log('Loading profile for user ID:', userId);
        
        const response = await fetch(`get_superadmin_profile.php?user_id=${encodeURIComponent(userId)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Profile data received:', data);

        if (data.success) {
            displayFullName.textContent = data.fullName || 'N/A';
            displayUsername.textContent = data.username ? '@' + data.username : '@N/A';
            displayIdNo.textContent = data.id_no || 'N/A';

            // Personal Information
            personalInfoEl.innerHTML = `
                <div class="info-item">
                    <span class="info-label">First Name</span>
                    <span class="info-value"><i class="fas fa-user"></i> ${escapeHtml(data.fname || 'N/A')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Middle Initial</span>
                    <span class="info-value">${escapeHtml(data.minitial || '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Last Name</span>
                    <span class="info-value">${escapeHtml(data.lname || 'N/A')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Extension</span>
                    <span class="info-value">${escapeHtml(data.extension || '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Birthday</span>
                    <span class="info-value"><i class="fas fa-birthday-cake"></i> ${formatDate(data.birthday)}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Age</span>
                    <span class="info-value">${data.age || '—'} years old</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sex</span>
                    <span class="info-value"><i class="fas ${data.sex === 'Male' ? 'fa-mars' : 'fa-venus'}"></i> ${escapeHtml(data.sex || '—')}</span>
                </div>
            `;

            // Address Information
            addressInfoEl.innerHTML = `
                <div class="info-item">
                    <span class="info-label">Purok/Street</span>
                    <span class="info-value"><i class="fas fa-road"></i> ${escapeHtml(data.purok || '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Barangay</span>
                    <span class="info-value">${escapeHtml(data.barangay || '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">City</span>
                    <span class="info-value">${escapeHtml(data.city || '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Province</span>
                    <span class="info-value">${escapeHtml(data.province || '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Country</span>
                    <span class="info-value"><i class="fas fa-globe"></i> ${escapeHtml(data.country || '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Zip Code</span>
                    <span class="info-value">${escapeHtml(data.zipcode || '—')}</span>
                </div>
            `;

            // Account Information
            accountInfoEl.innerHTML = `
                <div class="info-item">
                    <span class="info-label">Username</span>
                    <span class="info-value"><i class="fas fa-user-circle"></i> ${escapeHtml(data.username || 'N/A')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><i class="fas fa-envelope"></i> ${escapeHtml(data.email || 'N/A')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Role</span>
                    <span class="info-value"><i class="fas fa-crown" style="color: gold;"></i> ${escapeHtml(data.role || 'N/A')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Member Since</span>
                    <span class="info-value"><i class="fas fa-calendar-check"></i> ${formatDate(data.created_at)}</span>
                </div>
            `;
        } else {
            console.error('Failed to load profile:', data.message);
            showError('Failed to load profile data: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error in loadProfileData:', error);
        showError('Error loading profile data: ' + error.message);
    }
}

// Load profile for editing
async function loadProfileForEdit() {
    try {
        const userId = displayIdNo.textContent;
        
        if (!userId || userId === 'Loading...' || userId === 'N/A') {
            showError('User ID not available');
            return;
        }
        
        console.log('Loading profile for edit, user ID:', userId);
        
        const response = await fetch(`get_superadmin_profile.php?user_id=${encodeURIComponent(userId)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Edit profile data received:', data);

        if (data.success) {
            document.getElementById('editIdNo').value = data.id_no || '';
            document.getElementById('editFName').value = data.fname || '';
            document.getElementById('editMInitial').value = data.minitial || '';
            document.getElementById('editLName').value = data.lname || '';
            document.getElementById('editExtension').value = data.extension || '';
            document.getElementById('editBirthday').value = data.birthday || '';
            document.getElementById('editSex').value = data.sex || '';
            document.getElementById('editPurok').value = data.purok || '';
            document.getElementById('editBarangay').value = data.barangay || '';
            document.getElementById('editCity').value = data.city || '';
            document.getElementById('editProvince').value = data.province || '';
            document.getElementById('editCountry').value = data.country || 'Philippines';
            document.getElementById('editZipCode').value = data.zipcode || '';
            document.getElementById('editUsername').value = data.username || '';
            document.getElementById('editEmail').value = data.email || '';

            calculateAge();
        } else {
            showError('Failed to load profile for editing: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error in loadProfileForEdit:', error);
        showError('Error loading profile for editing: ' + error.message);
    }
}

// Handle profile update
async function handleProfileUpdate(e) {
    e.preventDefault();

    // Show loading state
    const submitBtn = editProfileForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;

    // Create FormData object instead of URLSearchParams
    const formData = new FormData();
    formData.append('id_no', document.getElementById('editIdNo').value);
    formData.append('fname', document.getElementById('editFName').value);
    formData.append('minitial', document.getElementById('editMInitial').value);
    formData.append('lname', document.getElementById('editLName').value);
    formData.append('extension', document.getElementById('editExtension').value);
    formData.append('birthday', document.getElementById('editBirthday').value);
    formData.append('sex', document.getElementById('editSex').value);
    formData.append('purok', document.getElementById('editPurok').value);
    formData.append('barangay', document.getElementById('editBarangay').value);
    formData.append('city', document.getElementById('editCity').value);
    formData.append('province', document.getElementById('editProvince').value);
    formData.append('country', document.getElementById('editCountry').value);
    formData.append('zipcode', document.getElementById('editZipCode').value);
    formData.append('email', document.getElementById('editEmail').value);
    
    // DO NOT send username - it's readonly and shouldn't be updated

    try {
        console.log('Updating profile with FormData');
        
        const response = await fetch('update_superadmin_profile.php', {
            method: 'POST',
            body: formData  // Use FormData directly, don't set Content-Type header
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Update response:', data);

        if (data.success) {
            closeModal(editProfileModal);
            showSuccess('Profile updated successfully');
            await loadProfileData(); // Reload profile data
        } else {
            showError(data.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showError('Error updating profile: ' + error.message);
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// Calculate age from birthday
function calculateAge() {
    const birthdayInput = document.getElementById('editBirthday');
    const ageInput = document.getElementById('editAge');

    if (birthdayInput && birthdayInput.value) {
        const today = new Date();
        const birthDate = new Date(birthdayInput.value);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        if (ageInput) {
            ageInput.value = age;
        }
    }
}

// Helper functions
function formatDate(dateString) {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (e) {
        return '—';
    }
}

function formatDateTime(dateString) {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return '—';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function closeModal(modal) {
    if (modal) {
        modal.style.display = 'none';
    }
}

function showSuccess(message) {
    if (successToast) {
        successToast.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        successToast.classList.add('show');
        setTimeout(() => {
            successToast.classList.remove('show');
        }, 3000);
    }
}

function showError(message) {
    console.error('Error:', message);
    if (errorToast) {
        errorToast.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        errorToast.classList.add('show');
        setTimeout(() => {
            errorToast.classList.remove('show');
        }, 3000);
    }
}