<?php
session_start();
require 'db.php';

if (!isset($_GET['email'])) {
    header('Location: recover.php');
    exit;
}

$email = $_GET['email'];

// Check if user has a valid, non-expired OTP before showing the form
$stmt = $pdo->prepare("SELECT verification_code, verification_expires_at FROM users WHERE email = ?");
$stmt->execute([$email]);
$user_otp = $stmt->fetch();
if (!$user_otp || !$user_otp['verification_code'] || strtotime($user_otp['verification_expires_at']) < time()) {
    $_SESSION['error_message'] = "Your OTP code is missing or expired. Please request a new password reset email.";
    header('Location: recover.php?email=' . urlencode($email));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ?");
            $stmt->execute([$email, $otp]);
            $user = $stmt->fetch();
            if ($user) {
                if (strtotime($user['verification_expires_at']) < time()) {
                    $_SESSION['error_message'] = "Your OTP code has expired. Please request a new password reset email.";
                } else {
                    // Hash the new password and update the user
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ?, verification_code = NULL, verification_expires_at = NULL WHERE email = ?");
                    $updateStmt->execute([$hashed_password, $email]);
                    $_SESSION['message'] = "✅ Your password has been changed. You can now log in with your new password.";
                    header("Location: login.php");
                    exit;
                }
            } else {
                $_SESSION['error_message'] = "Invalid OTP code. Please check your email and try again.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Arangkada Car Rentals</title>
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
        input[type="text"],
        input[type="password"] {
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
        <h2 class="form-title">Reset Password</h2>
        
        <p class="description">Enter the OTP code sent to your email and your new password.</p>

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
                <input type="text" name="otp" placeholder="Enter OTP Code" required>
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            </div>
            <button type="submit" class="btn">Reset Password</button>
        </form>
        <div style="text-align:center;margin-top:1.5em;">
            <a href="recover.php?email=<?=urlencode($email)?>" style="color:#3498db;text-decoration:underline;font-size:0.98em;">Resend OTP / Forgot your code?</a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Arangkada Car Rentals. All rights reserved.</p>
    </footer>
</body>
</html> 