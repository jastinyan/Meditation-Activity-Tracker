function hasInput(value) {
    return value.length > 0;
}
function validateIdNumber(value) {
    return /^\d{4}-\d{4}$/.test(value);
}
function addDashToIdNumber(value) {
    // Keep existing characters and insert a dash after 4 numeric characters
    const match = value.match(/^(\d{4})(\d*)/);
    if (match) {
        return match[1] + '-' + match[2];
    }
    return value; // Return unchanged if no match
}
function isNumeric(value){
        return /^[0-9]+$/.test(value);
}
function hasNumberInName(name) {
    return(/\d/.test(name));
}
function isCapitalized(str, fieldName) {
    // Check for double spaces
    if (hasDoubleSpaces(str)) {
        alert(`Error in ${fieldName}: Must not contain double spaces.`);
        return false; // Reject strings with double spaces
    }
    // Split the string into words
    const words = str.trim().split(' ');

    // Check the first word capitalization and no internal uppercase letters
    if (words.length > 0) {
        const firstWord = words[0];

        // Ensure the first letter is capitalized
        if (firstWord.charAt(0) !== firstWord.charAt(0).toUpperCase()) {
            alert(`Error in ${fieldName}: First letter of the first word must be capitalized.`);
            return false;
        }
        // Check if the entire string is all uppercase
        if (firstWord === firstWord.toUpperCase()) {
            alert(`Error in ${fieldName}: First word must not be in all capital letters.`);
            return false;
        }
        const restOfFirstWord = firstWord.slice(1);
        if (restOfFirstWord !== restOfFirstWord.toLowerCase()) {
            alert(`Error in ${fieldName}: First word must not contain uppercase letters after the first letter.`);
            return false;
        }
    }

    // Check the second word capitalization and no internal uppercase letters (if it exists)
    if (words.length > 1) {
        const secondWord = words[1];

        // Ensure the first letter is capitalized
        if (secondWord.charAt(0) !== secondWord.charAt(0).toUpperCase()) {
            alert(`Error in ${fieldName}: First letter of the second word must be capitalized.`);
            return false;
        }
        // Check if the entire string is all uppercase
        if (secondWord === secondWord.toUpperCase()) {
            alert(`Error in ${fieldName}: Second word must not be in all capital letters.`);
            return false;
        }
        // Ensure the rest of the second word is lowercase
        const restOfSecondWord = secondWord.slice(1);
        if (restOfSecondWord !== restOfSecondWord.toLowerCase()) {
            alert(`Error in ${fieldName}: Second word must not contain uppercase letters after the first letter.`);
            return false;
        }
    }

    return true; // All checks passed
}

function isSingleCapitalLetter(value) {
    return /^[A-Z]$/.test(value);
}
function hasThreeConsecutiveLetters(str) {
    // Convert the string to lowercase to ensure case-insensitive checking
    const lowerStr = str.toLowerCase();

    // Use a regular expression to check for three consecutive identical letters
    return /(.)\1\1/.test(lowerStr);
}

function hasDoubleSpaces(name) {
    return /\s\s/.test(name);
}
function validateExtension(extension) {
    // Regex to match Jr, Sr, or valid Roman numerals
    const pattern = /^(Jr|Sr|M{0,3}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))$/;
    return pattern.test(extension);
}

document.getElementById('id_no').addEventListener('input', function () {
    // Remove the dash temporarily to handle reformatting
    let value = this.value.replace('-', '');

    // Check if there are at least four digits to add the dash
    if (value.length > 4) {
        this.value = value.slice(0, 4) + '-' + value.slice(4);
    } else {
        this.value = value; // No dash if less than 4 digits
    }
});
//validate the form manually from capital letters to double space
function validateForm(event){
    const id_no = document.getElementById('id_no').value;
    const f_name = document.getElementById('f_name').value;
    const m_initial = document.getElementById('m_initial').value;
    const l_name = document.getElementById('l_name').value;
    const extension = document.getElementById('extension').value;
    const birthday = document.getElementById('birthday').value;
    const age = document.getElementById('age').value;
    const sex = document.getElementById('sex').value;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const email = document.getElementById('email').value;
        //declare for address
    const purok = document.getElementById('purok').value;
    const barangay = document.getElementById('barangay').value;
    const city = document.getElementById('city').value;
    const province = document.getElementById('province').value;
    const country = document.getElementById('country').value;
    const zipcode = document.getElementById('zipcode').value;

    

    event.preventDefault();
        if (id_no.length === 0){
            alert("Error: Must contain a unique ID number in this format: XXXX-XXXX");
                return;
        }
        if (!validateIdNumber(id_no)) {
            alert('Error: ID Number must contain numeric value and a dash only.           Use this format: XXXX-XXXX');
            return;
        }
//FOR FIRST NAME
        if (f_name.length === 0){
            alert("Error: First name must be filled");
                return;
        }
        if (f_name.length < 2 || f_name.length >= 20) {
            alert('Error: Firstname must contain at least 2 and less than 20 characters');
                return;
        }
        if(hasNumberInName(f_name)){
            alert("Error: First Name must not contain numbers");
                return;
        }
        if (hasThreeConsecutiveLetters(f_name)) {
            alert("Error: First name must not contain 3 consecutive identical letters");
            return;
        }
        if (!isCapitalized(f_name, "First Name")) {
            document.getElementById('f_name').focus();
            return; // Stop execution
        }
        
// FOR MIDDLENAME OPTIONAL
        if (m_initial && !isSingleCapitalLetter(m_initial)) {
            alert("Error: Middle initial must be a single capital letter only.");
            return;
        }
//FOR LAST NAME
        if (l_name.length === 0){
            alert("Error: Last name must be filled");
                return;
        }
        if (l_name.length < 2 || l_name.length >= 20) {
            alert('Error: Lastname must contain at least 2 and less than 20 characters');
                return;
        }
        if(hasNumberInName(l_name)){
            alert("Error: Last Name must not contain numbers");
                return;
        }
        if (!isCapitalized(l_name, "Last Name")) {
            document.getElementById('l_name').focus();
            return; // Stop execution
        }
        if (hasThreeConsecutiveLetters(l_name)) {
            alert("Error: Last name must not contain 3 consecutive identical letters");
            return;
        }

// FOR EXTENSION NAME

        if (extension && !validateExtension(extension)) {
            alert("Error: Extension Name accepts Jr/Sr and roman numerals only");
            return;
        }
// FOR SEX
        if (age.length === 0){
            alert("Error: Input valid birthday");
                return;
        }
        if (!validateAge(age)) {
            alert('Error: User is underage and cannot register.');
            return false;
        }
        if (sex.length === 0){
            alert("Error: Please select sex");
                return;
        }
// FOR USERNAME
        if (username.length === 0) {
            alert("Error: Input a valid Username");
            return;
        }
        if (hasDoubleSpaces(username)) {
            alert("Error: Username must not contain double space");
            return;
        }

        // Ensure the username length is between 5 and 20 characters
        if (username.length < 5 || username.length >= 20) {
            alert('Error: Username must contain at least 5 but less than 20 characters');
            return;
        }


        // Check if the username starts with a number
        if (/^[0-9]/.test(username)) {
            alert("Error: Username must not start with a number.");
            return;
        }

        // Check if the username contains only valid characters
        if (!/^[a-z][a-z0-9._]*$/.test(username)) {
            alert("Error: Username must start/contain only lowercase letters.");
            return;
        }


//PASSWORD and email
        if (password.length === 0){
            alert("Error: Input valid password");
                return;
        }
        if (password.length < 8 || password.length >= 20) {
            alert('Error: Password must contain at least 2 and less than 20 characters');
                return;
        }
        if (email.length === 0){
            alert("Error: Must input email");
                return;
        }
//FOR PUROK   
        // FOR PUROK
        if (purok.length === 0) {
            alert("Error: Purok must be filled");
            return;
        }
        if (purok.length >= 20) {
            alert('Error: Purok must contain at least 2 and less than 20 characters');
            return;
        }

        // Allow single-digit numbers
        if (!/^\d$/.test(purok)) { // If not a single digit
            if (!isCapitalized(purok, "Purok")) {
                document.getElementById('purok').focus();
                return; // Stop execution
            }
        }

        // Check for 3 consecutive identical letters
        if (hasThreeConsecutiveLetters(purok)) {
            alert("Error: Purok must not contain 3 consecutive identical letters");
            return;
        }

//FOR BARANGAY
        if (barangay.length === 0){
            alert("Error: Barangay must be filled");
                return;
        }
        if (barangay.length < 2 || barangay.length >= 20) {
            alert('Error: Barangay must contain at least 2 and less than 20 characters');
                return;
        }
        if (!isCapitalized(barangay, "Barangay")) {
            document.getElementById('barangay').focus();
            return; // Stop execution
        }
        if (hasThreeConsecutiveLetters(barangay)) {
            alert("Error: Barangay must not contain 3 consecutive identical letters");
            return;
        }

//FOR CITY
        if (city.length === 0){
            alert("Error: City must be filled");
                return;
        }
        if (city.length < 2 || city.length >= 20) {
            alert('Error: City must contain at least 2 and less than 20 characters');
                return;
        }
        if (!isCapitalized(city, "City")) {
            document.getElementById('city').focus();
            return; // Stop execution
        }
        if (hasThreeConsecutiveLetters(city)) {
            alert("Error: City must not contain 3 consecutive identical letters");
            return;
        }
//FOR PROVINCE
        if (province.length === 0){
            alert("Error: Province must be filled");
                return;
        }
        if (province.length < 2 || province.length >= 20) {
            alert('Error: Province must contain at least 2 and less than 20 characters');
                return;
        }
        if (!isCapitalized(province, "Province")) {
            document.getElementById('province').focus();
            return; // Stop execution
        }
        if (hasThreeConsecutiveLetters(province)) {
            alert("Error: Province must not contain 3 consecutive identical letters");
            return;
        }

//FOR COUNTRY
        if (country.length === 0){
            alert("Error: Country must be filled");
                return;
        }
        if (country.length < 2 || country.length >= 20) {
            alert('Error: Country must contain at least 2 and less than 20 characters');
                return;
        }
        if (!isCapitalized(country, "Country")) {
            document.getElementById('country').focus();
            return; // Stop execution
        }
        if (hasThreeConsecutiveLetters(country)) {
            alert("Error: Country must not contain 3 consecutive identical letters");
            return;
        }
//ZIPCODE
        if (zipcode.length === 0){
            alert("Error: Zipcode must be filled");
                return;
        }
        if(!isNumeric(zipcode)){
            alert("Zipcode must contain numeric value only");
                return;
            }
        if (!/^\d{4}$/.test(zipcode)) {
            alert('Error: Zipcode must contain exactly 4 digits.');
            return;
        }
        
        // ================= SECURITY QUESTIONS VALIDATION =================
    const sec_q1 = document.getElementById("sec_q1").value;
    const sec_q2 = document.getElementById("sec_q2").value;
    const sec_q3 = document.getElementById("sec_q3").value;

    const sec_a1 = document.getElementById("sec_a1").value.trim();
    const sec_a2 = document.getElementById("sec_a2").value.trim();
    const sec_a3 = document.getElementById("sec_a3").value.trim();

        // check if dropdown questions are selected
        if (sec_q1 === "" || sec_q2 === "" || sec_q3 === "") {
            alert("Error: Please select all 3 security questions.");
            return;
        }

        // prevent duplicates
        if (sec_q1 === sec_q2 || sec_q1 === sec_q3 || sec_q2 === sec_q3) {
            alert("Error: Security questions must be unique. Do not choose the same question twice.");
            return;
        }

        // check if answers are filled
        if (sec_a1.length === 0 || sec_a2.length === 0 || sec_a3.length === 0) {
            alert("Error: Please answer all 3 security questions.");
            return;
        }

        // prevent double spaces in answers
        if (hasDoubleSpaces(sec_a1) || hasDoubleSpaces(sec_a2) || hasDoubleSpaces(sec_a3)) {
            alert("Error: Security answers must not contain double spaces.");
            return;
        }

        // prevent 3 consecutive identical letters in answers
        if (hasThreeConsecutiveLetters(sec_a1) || hasThreeConsecutiveLetters(sec_a2) || hasThreeConsecutiveLetters(sec_a3)) {
            alert("Error: Security answers must not contain 3 consecutive identical letters.");
            return;
        }

            return true;
        }



//Checks if id already exist
function existingID() {
const id_no = document.getElementById('id_no').value.trim(); // Trim whitespace

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
        alert('ID number already exist. Input unique ID Number.');
        idField.value = ''; // Reset username field
        idField.setCustomValidity('ID number already exist.');
    } else {
        idField.setCustomValidity('');
    }
})
}
//Checks if username already exist
    function existingUsername() {
        const username = document.getElementById('username').value.trim(); // Trim whitespace

        if (username.length === 0) {
            // If username field is empty
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
                alert('Username is already taken. Please choose another.');
                usernameField.value = ''; // Reset username field
                usernameField.setCustomValidity('Username is already taken.');
            } else {
                usernameField.setCustomValidity('');
            }
        })
    }

    function validateAge() {
        const birthdayInput = document.getElementById('birthday').value; // Input value is a string
        const ageMessage = document.getElementById('ageMessage');
        const minAge = 18; // Set minimum age requirement
    
        if (!birthdayInput) {
            ageMessage.textContent = ''; // Clear message if no birthday is selected
            return false;
        }
    
        const birthday = new Date(birthdayInput); // Convert string to Date object
        const age = calculateAge(birthday);
    
        if (age < minAge) {
            ageMessage.textContent = 'User is underage. Not allowed to register.';
            return false; // Invalid age
        } else {
            ageMessage.textContent = ''; // Clear message if age is valid
            return true;
        }
    }
    
    document.getElementById('birthday').addEventListener('change', function () {
        const birthday = new Date(this.value); // Ensure value is converted to Date
        const age = calculateAge(birthday);
        document.getElementById('age').value = age; // Display in readonly field
        document.getElementById('ageHidden').value = age; // Assuming 'age' is an input field
    });
    
    // Calculates the age based on the birthday input
    function calculateAge(birthday) {
        const today = new Date();
        let age = today.getFullYear() - birthday.getFullYear();
        const monthDifference = today.getMonth() - birthday.getMonth();
    
        // Adjust age if the birthday hasn't occurred yet this year
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthday.getDate())) {
            age--;
        }
        return age;
    }
    
// Function to check password strength
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthText = document.getElementById('pwStrength');
    
    const regexWeak = /^[a-zA-Z0-9]{6,}$/; // Simple alphanumeric check
    const regexStrong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/; 

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

// Function to check if the re-entered password matches the original
let passwordMatchTimeout;

function checkPasswordMatch() {
clearTimeout(passwordMatchTimeout); // Clear the timeout if the user types again

passwordMatchTimeout = setTimeout(() => {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const matchText = document.getElementById('pwMatch');

    if (password !== confirmPassword) {
        matchText.innerHTML = 'Passwords do not match.';
        matchText.style.color = 'red';
        document.getElementById('confirmPassword').setCustomValidity('');
    } else {
        matchText.innerHTML = 'Passwords match!';
        matchText.style.color = 'green';
        document.getElementById('confirmPassword').setCustomValidity('');
    }
}, 1000); // Adjust the delay 
}
function existingPassword() {
    const password = document.getElementById('password').value.trim(); // Trim whitespace

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
            alert('Password already exists. Please input another valid password.');
            passwordField.value = ''; // Reset password field
            passwordField.setCustomValidity('Password already exists.');
        } else {
            passwordField.setCustomValidity(''); // Clear any previous validation messages
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function existingEmail() {
    const email = document.getElementById('email').value.trim(); // Trim whitespace

    if (email.length === 0) {
        // If email field is empty
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
            alert('Email is already taken. Please choose another.');
            emailField.value = ''; // Reset email field
            emailField.setCustomValidity('Email is already taken.');
        } else {
            emailField.setCustomValidity('');
        }
    })
}
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
        alert("Error: Security questions must be unique. Do not choose the same question twice.");
        return false;
    }

    return true;
}

// call function for validity
document.getElementById('id_no').addEventListener('input', existingID);
document.getElementById('birthday').addEventListener('input', validateAge);
document.getElementById('username').addEventListener('blur', existingUsername);
document.getElementById('email').addEventListener('blur', existingEmail);
document.getElementById('password').addEventListener('input', checkPasswordStrength);
document.getElementById('password').addEventListener('input', existingPassword);
document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);

document.getElementById("sec_q1").addEventListener("change", updateSecurityDropdowns);
document.getElementById("sec_q2").addEventListener("change", updateSecurityDropdowns);
document.getElementById("sec_q3").addEventListener("change", updateSecurityDropdowns);

document.getElementById('form').addEventListener('submit', function(event) {
event.preventDefault(); // Prevent default form submission

if (!validateForm(event)) {
    return; // Stop the submission if validation fails
}
if (!validateSecurityQuestions()) {
    return;
}

const formData = new FormData(document.getElementById('form'));

fetch('http://localhost/Melecio/phpdb/reg.php', {
    method: 'POST',
    body: formData
})
.then(data => {
    console.log(data); // Log the success message from the server
    alert("Congratulations! Successfully registered. You can now Log in"); // Show the success message from the server
    document.getElementById('form').reset(); // Clear the form upon success
    window.location.href = 'http://localhost/Melecio/phpdb/login.php'; // Redirect to login page
})
.catch(error => {
    console.error('Error:', error);
    alert('There was an error submitting the form.');
});
});