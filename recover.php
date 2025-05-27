<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Update user with reset OTP
            $updateStmt = $pdo->prepare("UPDATE users SET verification_code = ?, verification_expires_at = ? WHERE email = ?");
            $updateStmt->execute([$otp, $expiresAt, $email]);

            // Send email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'hontiverosharvey04@gmail.com';
                $mail->Password = 'mfpl qxgc kanx njfc';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('hontiverosharvey04@gmail.com', 'Arangkada Rental');
                $mail->addAddress($email, $user['username']);
                $mail->Subject = 'Password Reset OTP';
                
                $mail->isHTML(true);
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Hello {$user['username']},</p>
                    <p>Your OTP code for password reset is: <strong>{$otp}</strong></p>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                    <br>
                    <p>Best regards,<br>Arangkada Car Rentals</p>
                ";
                $mail->AltBody = "Hello {$user['username']},\n\nYour OTP code for password reset is: {$otp}\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nArangkada Car Rentals";

                $mail->send();
                $_SESSION['success_message'] = "OTP has been sent to your email.";
                header("Location: reset_password.php?email=" . urlencode($email));
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error sending email. Please try again later.";
            }
        } else {
            $_SESSION['error_message'] = "No account found with that email address.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error. Please try again later.";
    }
    
    header("Location: recover.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Recovery - Arangkada Car Rentals</title>
    <style>
        body {
            background: #f4f6f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }
        .header {
            background: #3498db;
            color: #fff;
            padding: 24px 0;
            text-align: center;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .header h1 {
            margin: 0;
            font-size: 2.2em;
            font-weight: 700;
            letter-spacing: 2px;
        }
        .nav {
            margin-top: 12px;
        }
        .nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 18px;
            font-size: 1.1em;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav a:hover {
            color: #e3f2fd;
        }
        .container {
            max-width: 400px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            border-radius: 8px;
        }
        .form-title {
            color: #3498db;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8em;
        }
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
            margin-bottom: 15px;
        }
        .btn {
            background: #3498db;
            color: #fff;
            padding: 12px 32px;
            border: none;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .back-btn {
            background: #fff;
            color: #3498db;
            border: 2px solid #3498db;
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.2s;
        }
        .back-btn:hover {
            background: #3498db;
            color: #fff;
        }
        .footer {
            background: #3498db;
            color: #fff;
            text-align: center;
            padding: 18px 0;
            margin-top: 40px;
            font-size: 1em;
            letter-spacing: 1px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        .description {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 0.95em;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>🚗 Arangkada Car Rentals</h1>
        <nav class="nav">
            <a href="index.php">Home</a>
        </nav>
    </header>

    <div class="container">
        <h2 class="form-title">Password Recovery</h2>
        
        <p class="description">Enter your email address below and we'll send you an OTP code to reset your password.</p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your email address" required>
            </div>
            <button type="submit" class="btn">Send OTP Code</button>
        </form>

        <div class="back-to-login">
            <p>Remember your password?</p>
            <a href="login.php"><button type="button" class="back-btn">Back to Login</button></a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Arangkada Car Rentals. All rights reserved.</p>
    </footer>
</body>
</html>
