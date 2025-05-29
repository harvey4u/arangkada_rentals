<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once 'config/database.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->email) || !isset($data->password) || !isset($data->role)) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "Missing required fields"));
    exit();
}

// Sanitize input
$username = htmlspecialchars(strip_tags($data->username));
$email = htmlspecialchars(strip_tags($data->email));
$password = $data->password;
$role = htmlspecialchars(strip_tags($data->role));

// Validate username format
if (!preg_match('/^[a-zA-Z0-9._-]{3,20}$/', $username)) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "Invalid username format"));
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "Invalid email format"));
    exit();
}

// Validate password strength
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "Password must be at least 8 characters"));
    exit();
}

if (!preg_match('/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=])(?=\S+$).{8,}$/', $password)) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error", 
        "message" => "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character"
    ));
    exit();
}

// Validate role
$valid_roles = array("staff", "driver", "client");
if (!in_array($role, $valid_roles)) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "Invalid role"));
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
        echo json_encode(array("status" => "error", "message" => "Username already exists"));
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
        echo json_encode(array("status" => "error", "message" => "Email already exists"));
        exit();
    }

    // Hash password with strong algorithm and options
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insert new user
    $query = "INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "User created successfully",
            "user_id" => $stmt->insert_id
        ));
    } else {
        throw new Exception("Error executing query: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
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