<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../db.php';

// Get the verification data from URL
$encoded_data = $_GET['data'] ?? '';

if (empty($encoded_data)) {
    die('Invalid verification link');
}

try {
    // Decode and validate the data
    $user_data = json_decode(base64_decode($encoded_data), true);
    
    if (!$user_data || !isset($user_data['id']) || !isset($user_data['code'])) {
        die('Invalid verification data');
    }

    // Find user with this verification code and check if it's not expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND verification_code = ? AND is_verified = 0 AND verification_expires_at > NOW()");
    $stmt->execute([$user_data['id'], $user_data['code']]);
    $user = $stmt->fetch();

    if (!$user) {
        die('Invalid, expired, or already used verification code');
    }

    // Update user as verified and clear verification code
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, verification_expires_at = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Get user's role
    $stmt = $pdo->prepare("
        SELECT r.name 
        FROM roles r 
        JOIN user_roles ur ON r.id = ur.role_id 
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $role_data = $stmt->fetch();
    
    if (!$role_data) {
        die('User role not found');
    }

    // Determine the dashboard URL based on role
    $dashboard_url = '';
    switch (strtolower($role_data['name'])) {
        case 'admin':
            $dashboard_url = '/ARANGKADA/arangkada_rentals/admin_dashboard.php';
            break;
        case 'client':
            $dashboard_url = '/ARANGKADA/arangkada_rentals/client_dashboard.php';
            break;
        case 'superadmin':
            $dashboard_url = '/ARANGKADA/arangkada_rentals/superadmin_dashboard.php';
            break;
        default:
            die('Invalid user role');
    }

    // Create success page with auto-redirect
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Email Verified - Arangkada Rentals</title>
        <meta http-equiv="refresh" content="5;url=' . $dashboard_url . '">
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background-color: #f5f5f5;
            }
            .container {
                background-color: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                max-width: 600px;
                margin: 0 auto;
            }
            h1 { color: #4CAF50; }
            .message { margin: 20px 0; }
            .redirect { color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ Email Verified Successfully!</h1>
            <div class="message">
                <p>Thank you for verifying your email address.</p>
                <p>Your account has been activated successfully.</p>
            </div>
            <div class="redirect">
                <p>You will be redirected to your dashboard in 5 seconds...</p>
                <p>If you are not redirected, <a href="' . $dashboard_url . '">click here</a>.</p>
            </div>
        </div>
    </body>
    </html>';

} catch (Exception $e) {
    die('An error occurred. Please try again later.');
} 