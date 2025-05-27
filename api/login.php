<?php
header('Content-Type: application/json');
require_once '../db.php';

// Get the POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $username_email = $_POST['username_email'] ?? '';
    $password = $_POST['password'] ?? '';
} else {
    $username_email = $input['username_email'] ?? '';
    $password = $input['password'] ?? '';
}

// Validate input
if (empty($username_email) || empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Username/Email and password are required'
    ]);
    exit;
}

try {
    // Query to check user credentials (by username or email)
    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name 
                          FROM users u 
                          LEFT JOIN user_roles ur ON u.id = ur.user_id 
                          LEFT JOIN roles r ON ur.role_id = r.id 
                          WHERE u.username = ? OR u.email = ?");
    $stmt->execute([$username_email, $username_email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Check if user is verified
        if (!$user['is_verified']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please verify your email before logging in'
            ]);
            exit;
        }

        // Check if user has a role assigned
        if (empty($user['role_name'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No role assigned to this user'
            ]);
            exit;
        }

        // Success - return user data
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role_name'],
                'created_at' => $user['created_at']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid username/email or password'
        ]);
    }
} catch (PDOException $e) {
    // Log the error for debugging (in a production environment)
    error_log("Login error: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred during login. Please try again later.'
    ]);
} 