<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require 'db_connection.php';

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['id_no'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in to update your profile"]);
    exit;
}

$id_no = $_SESSION['id_no'];

// ==================== VALIDATION FUNCTIONS ====================

function hasNumberInName($name) {
    return preg_match('/\d/', $name);
}

function hasThreeConsecutiveLetters($str) {
    $lowerStr = strtolower($str);
    return preg_match('/(.)\1\1/', $lowerStr);
}

function hasDoubleSpaces($str) {
    return preg_match('/\s\s/', $str);
}

function isCapitalized($str, $fieldName) {
    // Check for double spaces
    if (hasDoubleSpaces($str)) {
        return false;
    }
    
    $words = trim($str);
    if (empty($words)) return true;
    
    $words = explode(' ', $words);

    foreach ($words as $index => $word) {
        if (empty($word)) continue;
        
        // Ensure the first letter is capitalized
        if ($word[0] !== strtoupper($word[0])) {
            return false;
        }
        
        // Check if the entire word is all uppercase
        if ($word === strtoupper($word)) {
            return false;
        }
        
        // Ensure the rest of the word is lowercase
        $restOfWord = substr($word, 1);
        if ($restOfWord !== strtolower($restOfWord)) {
            return false;
        }
    }
    
    return true;
}

function validateExtension($extension) {
    if (empty($extension)) return true;
    $pattern = '/^(Jr|Sr|M{0,3}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))$/';
    return preg_match($pattern, $extension);
}

function isSingleCapitalLetter($value) {
    if (empty($value)) return true;
    return preg_match('/^[A-Z]$/', $value);
}

function isNumeric($value) {
    return preg_match('/^[0-9]+$/', $value);
}

// Get form data
$f_name = $_POST['fname'] ?? '';
$m_initial = $_POST['minitial'] ?? '';
$l_name = $_POST['lname'] ?? '';
$extension = $_POST['extension'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$sex = $_POST['sex'] ?? '';
$email = $_POST['email'] ?? '';

$purok = $_POST['purok'] ?? '';
$barangay = $_POST['barangay'] ?? '';
$city = $_POST['city'] ?? '';
$province = $_POST['province'] ?? '';
$country = $_POST['country'] ?? '';
$zipcode = $_POST['zipcode'] ?? '';

// ==================== VALIDATION RULES ====================

// Validate First Name
if (empty($f_name)) {
    echo json_encode(["success" => false, "message" => "First name is required"]);
    exit;
}
if (strlen($f_name) < 2 || strlen($f_name) >= 20) {
    echo json_encode(["success" => false, "message" => "First name must be between 2 and 20 characters"]);
    exit;
}
if (hasNumberInName($f_name)) {
    echo json_encode(["success" => false, "message" => "First name must not contain numbers"]);
    exit;
}
if (hasThreeConsecutiveLetters($f_name)) {
    echo json_encode(["success" => false, "message" => "First name must not contain 3 consecutive identical letters"]);
    exit;
}
if (!isCapitalized($f_name, "First Name")) {
    echo json_encode(["success" => false, "message" => "First name must be properly capitalized (first letter capital, rest lowercase, no double spaces)"]);
    exit;
}

// Validate Middle Initial (optional)
if (!empty($m_initial) && !isSingleCapitalLetter($m_initial)) {
    echo json_encode(["success" => false, "message" => "Middle initial must be a single capital letter only"]);
    exit;
}

// Validate Last Name
if (empty($l_name)) {
    echo json_encode(["success" => false, "message" => "Last name is required"]);
    exit;
}
if (strlen($l_name) < 2 || strlen($l_name) >= 20) {
    echo json_encode(["success" => false, "message" => "Last name must be between 2 and 20 characters"]);
    exit;
}
if (hasNumberInName($l_name)) {
    echo json_encode(["success" => false, "message" => "Last name must not contain numbers"]);
    exit;
}
if (!isCapitalized($l_name, "Last Name")) {
    echo json_encode(["success" => false, "message" => "Last name must be properly capitalized (first letter capital, rest lowercase, no double spaces)"]);
    exit;
}
if (hasThreeConsecutiveLetters($l_name)) {
    echo json_encode(["success" => false, "message" => "Last name must not contain 3 consecutive identical letters"]);
    exit;
}

// Validate Extension (optional)
if (!empty($extension) && !validateExtension($extension)) {
    echo json_encode(["success" => false, "message" => "Extension name accepts Jr/Sr and Roman numerals only"]);
    exit;
}

// Validate Birthday
if (empty($birthday)) {
    echo json_encode(["success" => false, "message" => "Birthday is required"]);
    exit;
}

// Calculate Age and validate minimum age
$birthDate = new DateTime($birthday);
$today = new DateTime();
$age = $birthDate->diff($today)->y;
if ($age < 18) {
    echo json_encode(["success" => false, "message" => "User must be at least 18 years old"]);
    exit;
}

// Validate Sex
if (empty($sex)) {
    echo json_encode(["success" => false, "message" => "Please select sex"]);
    exit;
}

// Validate Email
if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email is required"]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// Check if email is already taken by another user
$checkEmailStmt = $conn->prepare("SELECT id_no FROM registeredacc WHERE email = ? AND id_no != ?");
$checkEmailStmt->bind_param("ss", $email, $id_no);
$checkEmailStmt->execute();
$emailResult = $checkEmailStmt->get_result();
if ($emailResult->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email is already in use by another account"]);
    exit;
}
$checkEmailStmt->close();

// Validate Purok
if (empty($purok)) {
    echo json_encode(["success" => false, "message" => "Purok/Street is required"]);
    exit;
}
if (strlen($purok) >= 20) {
    echo json_encode(["success" => false, "message" => "Purok must be less than 20 characters"]);
    exit;
}
// Allow single-digit numbers
if (!preg_match('/^\d$/', $purok)) {
    if (!isCapitalized($purok, "Purok")) {
        echo json_encode(["success" => false, "message" => "Purok must be properly capitalized"]);
        exit;
    }
}
if (hasThreeConsecutiveLetters($purok)) {
    echo json_encode(["success" => false, "message" => "Purok must not contain 3 consecutive identical letters"]);
    exit;
}

// Validate Barangay
if (empty($barangay)) {
    echo json_encode(["success" => false, "message" => "Barangay is required"]);
    exit;
}
if (strlen($barangay) < 2 || strlen($barangay) >= 20) {
    echo json_encode(["success" => false, "message" => "Barangay must be between 2 and 20 characters"]);
    exit;
}
if (!isCapitalized($barangay, "Barangay")) {
    echo json_encode(["success" => false, "message" => "Barangay must be properly capitalized"]);
    exit;
}
if (hasThreeConsecutiveLetters($barangay)) {
    echo json_encode(["success" => false, "message" => "Barangay must not contain 3 consecutive identical letters"]);
    exit;
}

// Validate City
if (empty($city)) {
    echo json_encode(["success" => false, "message" => "City is required"]);
    exit;
}
if (strlen($city) < 2 || strlen($city) >= 20) {
    echo json_encode(["success" => false, "message" => "City must be between 2 and 20 characters"]);
    exit;
}
if (!isCapitalized($city, "City")) {
    echo json_encode(["success" => false, "message" => "City must be properly capitalized"]);
    exit;
}
if (hasThreeConsecutiveLetters($city)) {
    echo json_encode(["success" => false, "message" => "City must not contain 3 consecutive identical letters"]);
    exit;
}

// Validate Province
if (empty($province)) {
    echo json_encode(["success" => false, "message" => "Province is required"]);
    exit;
}
if (strlen($province) < 2 || strlen($province) >= 20) {
    echo json_encode(["success" => false, "message" => "Province must be between 2 and 20 characters"]);
    exit;
}
if (!isCapitalized($province, "Province")) {
    echo json_encode(["success" => false, "message" => "Province must be properly capitalized"]);
    exit;
}
if (hasThreeConsecutiveLetters($province)) {
    echo json_encode(["success" => false, "message" => "Province must not contain 3 consecutive identical letters"]);
    exit;
}

// Validate Country
if (empty($country)) {
    echo json_encode(["success" => false, "message" => "Country is required"]);
    exit;
}
if (strlen($country) < 2 || strlen($country) >= 20) {
    echo json_encode(["success" => false, "message" => "Country must be between 2 and 20 characters"]);
    exit;
}
if (!isCapitalized($country, "Country")) {
    echo json_encode(["success" => false, "message" => "Country must be properly capitalized"]);
    exit;
}
if (hasThreeConsecutiveLetters($country)) {
    echo json_encode(["success" => false, "message" => "Country must not contain 3 consecutive identical letters"]);
    exit;
}

// Validate Zip Code
if (empty($zipcode)) {
    echo json_encode(["success" => false, "message" => "Zip code is required"]);
    exit;
}
if (!isNumeric($zipcode)) {
    echo json_encode(["success" => false, "message" => "Zip code must contain numbers only"]);
    exit;
}
if (!preg_match('/^\d{4}$/', $zipcode)) {
    echo json_encode(["success" => false, "message" => "Zip code must contain exactly 4 digits"]);
    exit;
}

// Update the user profile
$stmt = $conn->prepare("UPDATE registeredacc SET 
    f_name = ?,
    m_initial = ?,
    l_name = ?,
    extension = ?,
    birthday = ?,
    age = ?,
    sex = ?,
    email = ?,
    purok = ?,
    barangay = ?,
    city = ?,
    province = ?,
    country = ?,
    zipcode = ?,
    last_active = NOW()
    WHERE id_no = ?");

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error occurred"]);
    exit;
}

$stmt->bind_param("sssssisssssssss", 
    $f_name,
    $m_initial,
    $l_name,
    $extension,
    $birthday,
    $age,
    $sex,
    $email,
    $purok,
    $barangay,
    $city,
    $province,
    $country,
    $zipcode,
    $id_no
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Profile updated successfully!"
    ]);
} else {
    error_log("Update failed: " . $stmt->error);
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>