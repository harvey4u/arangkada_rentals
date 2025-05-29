<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once 'config/database.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error", 
        "message" => "Please fill in all required fields"
    ));
    exit();
}

// Sanitize input
$username = htmlspecialchars(strip_tags($data->username));
$email = htmlspecialchars(strip_tags($data->email));
$password = $data->password;

// Basic username validation
if (strlen($username) < 3) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error", 
        "message" => "Username must be at least 3 characters long"
    ));
    exit();
}

if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error", 
        "message" => "Username can only contain letters, numbers, dots, underscores, and hyphens"
    ));
    exit();
}

// Basic email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error", 
        "message" => "Please enter a valid email address"
    ));
    exit();
}

// Basic password validation
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error", 
        "message" => "Password must be at least 6 characters long"
    ));
    exit();
}

try {
    // Check if username already exists
    $check_query = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(array(
            "status" => "error", 
            "message" => "This username is already taken. Please choose another one."
        ));
        exit();
    }

    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(array(
            "status" => "error", 
            "message" => "This email is already registered. Please use a different email."
        ));
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user with default client role
    $query = "INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, 'client', 'active', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Prepare user data for response
        $user = array(
            "id" => $user_id,
            "username" => $username,
            "email" => $email,
            "role" => "client",
            "status" => "active"
        );

        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Welcome to Arangkada! Your account has been created successfully.",
            "user" => $user
        ));
    } else {
        throw new Exception($stmt->error);
    }

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