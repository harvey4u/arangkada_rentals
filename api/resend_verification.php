<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get the POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $email = $_POST['email'] ?? '';
} else {
    $email = $input['email'] ?? '';
}

// Validate input
if (empty($email)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email is required'
    ]);
    exit;
}

try {
    // Check if user exists and is not verified
    $stmt = $pdo->prepare("SELECT id, username, verification_token FROM users WHERE email = ? AND is_verified = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found or already verified'
        ]);
        exit;
    }

    // Generate new verification token if needed
    if (empty($user['verification_token'])) {
        $verification_token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
        $stmt->execute([$verification_token, $user['id']]);
    } else {
        $verification_token = $user['verification_token'];
    }

    // Send verification email
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'espbrodkint@gmail.com'; // Your Gmail address
        $mail->Password = 'ofngdpaamtadngqz'; // Your Gmail app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('espbrodkint@gmail.com', 'Arangkada Rentals');
        $mail->addAddress($email, $user['username']);

        // Content
        $verification_link = "http://10.0.2.2/ARANGKADA/arangkada_rentals/api/verify_email.php?token=" . $verification_token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - Arangkada Rentals';
        $mail->Body = "
            <h2>Welcome to Arangkada Rentals!</h2>
            <p>Please click the button below to verify your email address:</p>
            <p>
                <a href='{$verification_link}' 
                   style='background-color: #4CAF50; 
                          color: white; 
                          padding: 10px 20px; 
                          text-decoration: none; 
                          border-radius: 5px;
                          display: inline-block;'>
                    Verify Email
                </a>
            </p>
            <p>Or copy and paste this link in your browser:</p>
            <p>{$verification_link}</p>
            <p>If you didn't create an account with us, you can safely ignore this email.</p>
        ";
        $mail->AltBody = "Please verify your email by clicking this link: {$verification_link}";

        $mail->send();

        echo json_encode([
            'status' => 'success',
            'message' => 'Verification email has been sent. Please check your inbox.'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not send verification email: ' . $mail->ErrorInfo
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred. Please try again later.'
    ]);
}