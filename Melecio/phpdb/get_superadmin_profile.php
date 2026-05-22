<?php
session_start();
require 'db_connection.php';

header("Content-Type: application/json");

if (!isset($_SESSION['id_no'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (empty($user_id)) {
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit;
}

// Verify that the user is a super_admin
$checkStmt = $conn->prepare("SELECT role FROM registeredacc WHERE id_no = ?");
$checkStmt->bind_param("s", $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$userData = $checkResult->fetch_assoc();

if (!$userData || $userData['role'] !== 'super_admin') {
    echo json_encode(["success" => false, "message" => "Access denied. Super admin only."]);
    exit;
}
$checkStmt->close();

$stmt = $conn->prepare("SELECT 
    id_no, 
    f_name, 
    m_initial, 
    l_name, 
    extension,
    birthday, 
    age, 
    sex, 
    username, 
    email,
    purok,
    barangay,
    city,
    province,
    country,
    zipcode,
    role,
    created_at
    FROM registeredacc WHERE id_no = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format full name
    $fullName = trim($row['f_name'] . ' ' . ($row['m_initial'] ?? '') . ' ' . $row['l_name'] . ' ' . ($row['extension'] ?? ''));
    $fullName = preg_replace('/\s+/', ' ', $fullName);
    
    echo json_encode([
        "success" => true,
        "id_no" => $row['id_no'],
        "fname" => $row['f_name'],
        "minitial" => $row['m_initial'] ?? '',
        "lname" => $row['l_name'],
        "extension" => $row['extension'] ?? '',
        "birthday" => $row['birthday'],
        "age" => $row['age'],
        "sex" => $row['sex'],
        "username" => $row['username'],
        "email" => $row['email'],
        "purok" => $row['purok'] ?? '',
        "barangay" => $row['barangay'] ?? '',
        "city" => $row['city'] ?? '',
        "province" => $row['province'] ?? '',
        "country" => $row['country'] ?? 'Philippines',
        "zipcode" => $row['zipcode'] ?? '',
        "role" => $row['role'] ?? 'super_admin',
        "created_at" => $row['created_at'] ?? '',
        "fullName" => $fullName
    ]);
} else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}

$stmt->close();
$conn->close();
?>