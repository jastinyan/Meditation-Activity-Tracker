<?php
session_start();
require 'db_connection.php';

header("Content-Type: application/json");

if (!isset($_SESSION['id_no'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$id_no = $_SESSION['id_no'];


$stmt = $conn->prepare("SELECT 
    id_no, username, email, 
    f_name as first_name, m_initial as middle_initial, l_name as last_name, 
    extension as extension_name, birthday, age, sex,
    purok as purok_street, barangay, city as municipality_city, 
    province, country, zipcode as zip_code,
    created_at
    FROM registeredacc WHERE id_no = ?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Calculate age if not stored but birthday exists
    if (empty($row['age']) && !empty($row['birthday'])) {
        $birthday = new DateTime($row['birthday']);
        $today = new DateTime();
        $row['age'] = $birthday->diff($today)->y;
    }
    
    // Format full name
    $fullName = trim($row['first_name'] . ' ' . $row['middle_initial'] . ' ' . $row['last_name'] . ' ' . $row['extension_name']);
    $row['full_name'] = preg_replace('/\s+/', ' ', $fullName);
    
    echo json_encode([
        "success" => true,
        "profile" => $row
    ]);
} else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}
?>