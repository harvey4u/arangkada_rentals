<?php
header('Content-Type: application/json');
require_once '../db.php';

// Get the POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'client';
} else {
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'client';
}

// Validate input
if (empty($username) || empty($email) || empty($password)) {
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
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Begin transaction
    $pdo->beginTransaction();

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_verified, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt->execute([$username, $email, $hashedPassword]);
    $userId = $pdo->lastInsertId();

    // Get role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$role]);
    $roleId = $stmt->fetchColumn();

    if ($roleId) {
        // Assign role to user
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$userId, $roleId]);
    } else {
        throw new Exception("Selected role not found in database");
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful! Please check your email for verification.'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration error: ' . $e->getMessage()
    ]);
} 