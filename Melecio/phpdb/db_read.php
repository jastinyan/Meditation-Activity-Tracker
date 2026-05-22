<?php
$db_servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "mysystem";

// Create connection
$conn = mysqli_connect($db_servername, $db_username, $db_password, $dbname);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the type and value from the POST request
$type = $_POST['type'];
$value = $_POST['value'];

// Initialize response
$response = '';

// Check for existing username 
if ($type === 'username') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM registeredacc WHERE username = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $response = 'username_exists';
    } else {
        $response = 'username_available';
    }
}
// Check for existing id number
if ($type === 'id_no') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM registeredacc WHERE id_no = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $response = 'id_exists';
    } else {
        $response = 'id_available';
    }
}

// Check for existing email
if ($type === 'email') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM registeredacc WHERE email = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $response = 'email_exists';
    } else {
        $response = 'email_available';
    }
}

// Check for existing password
if ($type === 'password') {
    $stmt = $conn->prepare("SELECT password FROM registeredacc");
    $stmt->execute();
    $stmt->bind_result($hashedPassword);

    $passwordExists = false;
    while ($stmt->fetch()) {
        if (password_verify($value, $hashedPassword)) {
            $passwordExists = true;
            break;
        }
    }
    $stmt->close();

    // Return response to the frontend
    echo $passwordExists ? 'password_exists' : 'password_available';
}


// Return the response
echo $response;

$conn->close();
?>
