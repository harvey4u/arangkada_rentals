<?php
header('Content-Type: application/json');
require_once '../db.php';

// Get the verification token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Verification token is required'
    ]);
    exit;
}

try {
    // Find user with this verification token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired verification token'
        ]);
        exit;
    }

    // Update user as verified and clear verification token
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Email verified successfully! You can now log in to your account.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred. Please try again later.'
    ]);
} 