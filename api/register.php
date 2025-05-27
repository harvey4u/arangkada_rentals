<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    // Generate verification token
    $verification_code = substr(str_shuffle('0123456789'), 0, 6);
    
    // Set expiration time (24 hours from now)
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Begin transaction
    $pdo->beginTransaction();

    // Insert user with is_verified = 0
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_verified, verification_code, verification_expires_at, created_at) VALUES (?, ?, ?, 0, ?, ?, NOW())");
    $stmt->execute([$username, $email, $hashed_password, $verification_code, $verification_expires]);
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

    // Send verification email
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = 0;                      // Disable debug output
        $mail->isSMTP();                           // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';      // SMTP server
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = 'espbrodkint@gmail.com'; // SMTP username
        $mail->Password   = 'ofngdpaamtadngqz';    // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
        $mail->Port       = 587;                   // TCP port to connect to
        $mail->Timeout    = 60;                    // Set timeout to 60 seconds
        $mail->CharSet    = 'UTF-8';               // Set charset to UTF-8
        
        // Disable SSL certificate verification (only if needed)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('espbrodkint@gmail.com', 'Arangkada Rentals');
        $mail->addAddress($email, $username);
        
        // Create verification link with user data
        $user_data = base64_encode(json_encode([
            'id' => $user_id,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'code' => $verification_code
        ]));
        
        $verification_link = "http://10.0.2.2/ARANGKADA/arangkada_rentals/api/verify_email.php?data=" . urlencode($user_data);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Complete Your Registration - Arangkada Rentals';
        $mail->Body = "
            <h2>Welcome to Arangkada Rentals!</h2>
            <p>Thank you for registering. Please click the button below to complete your registration:</p>
            <p>
                <a href='{$verification_link}' 
                   style='background-color: #4CAF50; 
                          color: white; 
                          padding: 10px 20px; 
                          text-decoration: none; 
                          border-radius: 5px;
                          display: inline-block;'>
                    Complete Registration
                </a>
            </p>
            <p>Or copy and paste this link in your browser:</p>
            <p>{$verification_link}</p>
            <p>Your verification code is: <strong>{$verification_code}</strong></p>
            <p>This verification link will expire in 24 hours.</p>
            <p>If you didn't create an account with us, you can safely ignore this email.</p>
        ";
        $mail->AltBody = "Please complete your registration by clicking this link: {$verification_link}\n\nYour verification code is: {$verification_code}\n\nThis verification link will expire in 24 hours.";

        if (!$mail->send()) {
            throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
        }
        
        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Registration pending. Please check your email to complete registration.',
            'user_id' => $user_id,
            'email' => $email
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Email sending error: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not send verification email. Please try again later or contact support.'
        ]);
    }
} catch (PDOException $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 