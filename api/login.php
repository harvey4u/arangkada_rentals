<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once 'config/database.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Allow login with either username or email
if (!isset($data->username_email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error", 
        "message" => "Please enter your username/email and password"
    ));
    exit();
}

// Sanitize input
$username_email = htmlspecialchars(strip_tags($data->username_email));
$password = $data->password;

try {
    // Check if user exists and get their data
    $query = "SELECT id, username, email, password, role, status FROM users 
              WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(array(
            "status" => "error", 
            "message" => "The username/email or password you entered is incorrect"
        ));
        exit();
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(array(
            "status" => "error", 
            "message" => "The username/email or password you entered is incorrect"
        ));
        exit();
    }

    // Check if account is active
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(array(
            "status" => "error", 
            "message" => "Your account is currently inactive. Please contact support for assistance."
        ));
        exit();
    }

    // Remove password from response
    unset($user['password']);
    
    // Update last login
    $update_query = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();

    // Return success response with user data
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "message" => "Welcome back, " . $user['username'] . "!",
        "user" => $user
    ));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Oops! Something went wrong. Please try again later."
    ));
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 