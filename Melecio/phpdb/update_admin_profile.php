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

// Get form data - matching the field names from JavaScript
$fname = $_POST['fname'] ?? '';
$minitial = $_POST['minitial'] ?? '';
$lname = $_POST['lname'] ?? '';
$extension = $_POST['extension'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$sex = $_POST['sex'] ?? '';
$purok = $_POST['purok'] ?? '';
$barangay = $_POST['barangay'] ?? '';
$city = $_POST['city'] ?? '';
$province = $_POST['province'] ?? '';
$country = $_POST['country'] ?? 'Philippines';
$zipcode = $_POST['zipcode'] ?? '';
$email = $_POST['email'] ?? '';

// Debug log
error_log("Updating profile for ID: $id_no");
error_log("Email received: " . $email);

// Validate required fields
if (empty($fname) || empty($lname) || empty($birthday) || empty($sex) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Required fields are missing"]);
    exit;
}

// Validate email format
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

// Calculate age from birthday
$age = 0;
if (!empty($birthday)) {
    try {
        $birthDate = new DateTime($birthday);
        $today = new DateTime();
        $age = $birthDate->diff($today)->y;
    } catch (Exception $e) {
        error_log("Age calculation error: " . $e->getMessage());
    }
}

// Update the user profile - CORRECTED DATA TYPES
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

// IMPORTANT: The data types string must match exactly with the parameters
// s = string, i = integer
// For 15 parameters: 14 strings + 1 integer (age) + 1 string (id_no) at the end
$stmt->bind_param("sssssisssssssss", 
    $fname,        // string - f_name
    $minitial,     // string - m_initial
    $lname,        // string - l_name
    $extension,    // string - extension
    $birthday,     // string - birthday
    $age,          // integer - age
    $sex,          // string - sex
    $email,        // string - email
    $purok,        // string - purok
    $barangay,     // string - barangay
    $city,         // string - city
    $province,     // string - province
    $country,      // string - country
    $zipcode,      // string - zipcode
    $id_no         // string - id_no for WHERE clause
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