<?php
session_start();
require 'db_connection.php';

header("Content-Type: application/json");

// Fix: Allow both admin and super_admin
if (!isset($_SESSION['id_no']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Admin access required."]);
    exit;
}

$id_no = $_POST['id_no'] ?? '';

if (empty($id_no)) {
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit;
}

$f_name = $_POST['f_name'] ?? '';
$m_initial = $_POST['m_initial'] ?? '';
$l_name = $_POST['l_name'] ?? '';
$extension = $_POST['extension'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$sex = $_POST['sex'] ?? '';
$email = $_POST['email'] ?? '';
$username = $_POST['username'] ?? '';
$new_password = $_POST['new_password'] ?? '';

$purok = $_POST['purok'] ?? '';
$barangay = $_POST['barangay'] ?? '';
$city = $_POST['city'] ?? '';
$province = $_POST['province'] ?? '';
$country = $_POST['country'] ?? '';
$zipcode = $_POST['zipcode'] ?? '';

// Calculate age from birthday
$age = null;
if (!empty($birthday)) {
    $birthday_date = new DateTime($birthday);
    $today = new DateTime();
    $age = $birthday_date->diff($today)->y;
}

// Validate required fields
if (empty($f_name) || empty($l_name) || empty($birthday) || empty($sex) || 
    empty($purok) || empty($barangay) || empty($city) || 
    empty($province) || empty($country) || empty($zipcode) || empty($email) || empty($username)) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// Validate username format (from register.js)
if (!preg_match('/^[a-z][a-z0-9._]*$/', $username)) {
    echo json_encode(["success" => false, "message" => "Username must start with lowercase letter and contain only lowercase letters, numbers, dots, and underscores"]);
    exit;
}

if (strlen($username) < 5 || strlen($username) >= 20) {
    echo json_encode(["success" => false, "message" => "Username must be between 5 and 20 characters"]);
    exit;
}

// Validate name fields (no numbers)
if (preg_match('/\d/', $f_name) || preg_match('/\d/', $l_name)) {
    echo json_encode(["success" => false, "message" => "Names must not contain numbers"]);
    exit;
}

// Validate middle initial if provided
if (!empty($m_initial) && !preg_match('/^[A-Z]$/', $m_initial)) {
    echo json_encode(["success" => false, "message" => "Middle initial must be a single capital letter"]);
    exit;
}

// Validate extension if provided (from register.js)
if (!empty($extension)) {
    // Accept Jr, Sr, or Roman numerals I-X
    $valid_extensions = ['Jr', 'Sr', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
    if (!in_array($extension, $valid_extensions)) {
        echo json_encode(["success" => false, "message" => "Extension must be Jr, Sr, or Roman numerals (I-X)"]);
        exit;
    }
}

// Validate age (must be 18+)
if ($age < 18) {
    echo json_encode(["success" => false, "message" => "User must be at least 18 years old"]);
    exit;
}

// Validate zipcode (exactly 4 digits)
if (!preg_match('/^\d{4}$/', $zipcode)) {
    echo json_encode(["success" => false, "message" => "Zipcode must be exactly 4 digits"]);
    exit;
}

// Validate password if provided
if (!empty($new_password)) {
    // Password length check (from register.js)
    if (strlen($new_password) < 8 || strlen($new_password) >= 20) {
        echo json_encode(["success" => false, "message" => "Password must be between 8 and 20 characters"]);
        exit;
    }
    
    // Password strength check (from register.js)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $new_password)) {
        echo json_encode(["success" => false, "message" => "Password must contain at least one uppercase letter, one lowercase letter, and one number"]);
        exit;
    }
}

// Check if username already exists (excluding current user)
$checkStmt = $conn->prepare("SELECT id_no FROM registeredacc WHERE username = ? AND id_no != ?");
$checkStmt->bind_param("ss", $username, $id_no);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already taken by another user"]);
    exit;
}
$checkStmt->close();

// Check if email already exists (excluding current user)
$checkStmt = $conn->prepare("SELECT id_no FROM registeredacc WHERE email = ? AND id_no != ?");
$checkStmt->bind_param("ss", $email, $id_no);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already used by another user"]);
    exit;
}
$checkStmt->close();

// Start transaction for password update if needed
if (!empty($new_password)) {
    mysqli_begin_transaction($conn);
}

try {
    // Build the update query dynamically
    $sql = "UPDATE registeredacc SET 
        username = ?,
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
        zipcode = ?";
    
    $params = [$username, $f_name, $m_initial, $l_name, $extension, $birthday, $age, $sex, $email,
               $purok, $barangay, $city, $province, $country, $zipcode];
    $types = "ssssssissssssss";
    
    // Add password to update if provided
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql .= ", password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }
    
    $sql .= " WHERE id_no = ?";
    $params[] = $id_no;
    $types .= "s";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }
    
    if ($stmt->affected_rows > 0 || !empty($new_password)) {
        // Removed activity_logs insertion
        
        if (!empty($new_password)) {
            mysqli_commit($conn);
        }
        
        echo json_encode([
            "success" => true,
            "message" => "User profile updated successfully!" . (!empty($new_password) ? " Password has been changed." : "")
        ]);
    } else {
        if (!empty($new_password)) {
            mysqli_rollback($conn);
        }
        echo json_encode([
            "success" => false, 
            "message" => "No changes were made. The data might be the same or user not found."
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    if (!empty($new_password)) {
        mysqli_rollback($conn);
    }
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>