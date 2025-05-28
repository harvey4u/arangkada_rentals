<?php
header('Content-Type: application/json');
require_once '../db.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
} else {
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? '';
}

// Validate input
if (empty($username) || empty($email) || empty($password) || empty($role)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Validate role
$allowedRoles = ['admin', 'client'];
if (!in_array($role, $allowedRoles)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid role selected'
    ]);
    exit;
}

try {
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username already exists'
        ]);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Email already exists'
        ]);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Begin transaction
    $pdo->beginTransaction();

    // Insert user with is_verified = 1 (verified by default)
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_verified, created_at) VALUES (?, ?, ?, 1, NOW())");
    $stmt->execute([$username, $email, $hashed_password]);
    $user_id = $pdo->lastInsertId();

    // Get role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$role]);
    $role_data = $stmt->fetch();
    
    if (!$role_data) {
        $pdo->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid role'
        ]);
        exit;
    }

    // Assign role to user
    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $role_data['id']]);
        
    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful. You can now login.',
        'user_id' => $user_id,
        'email' => $email
    ]);

} catch (PDOException $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 