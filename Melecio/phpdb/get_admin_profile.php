<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require 'db_connection.php';

header("Content-Type: application/json");

function sendJsonResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    sendJsonResponse(false, 'Database connection failed');
}

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (empty($user_id)) {
    sendJsonResponse(false, 'User ID is required');
}

try {
    // Query from registeredacc table with all columns including created_at
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
        last_active,
        status,
        created_at
        FROM registeredacc WHERE id_no = ?");
    
    if (!$stmt) {
        sendJsonResponse(false, 'Database prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $user_id);
    
    if (!$stmt->execute()) {
        sendJsonResponse(false, 'Database execute error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Format full name
        $fullName = trim($row['f_name'] . ' ' . ($row['m_initial'] ?? '') . ' ' . $row['l_name'] . ' ' . ($row['extension'] ?? ''));
        $fullName = preg_replace('/\s+/', ' ', $fullName);
        
        sendJsonResponse(true, 'Success', [
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
            "role" => $row['role'] ?? 'user',
            "last_active" => $row['last_active'] ?? '',
            "status" => $row['status'] ?? 'active',
            "fullName" => $fullName,
            "created_at" => $row['created_at'] ?? '' // Using created_at directly from database
        ]);
    } else {
        sendJsonResponse(false, 'User not found with ID: ' . $user_id);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    sendJsonResponse(false, 'Server error: ' . $e->getMessage());
}
?>