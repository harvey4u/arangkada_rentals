<?php
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check duplicates before proceeding
    $emailExists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $emailExists->execute([$email]);

    $usernameExists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $usernameExists->execute([$username]);

    if ($emailExists->fetchColumn()) {
        $_SESSION['error'] = "Email already registered.";
    } elseif ($usernameExists->fetchColumn()) {
        $_SESSION['error'] = "Username already taken.";
    } else {
        // Generate OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Store registration data in session
        $_SESSION['registration'] = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'otp' => $otp,
            'expires_at' => $expiresAt
        ];

        // Send OTP email
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
            $mail->addAddress($email, $username);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "Hi $username,\n\nYour OTP code is: $otp\nThis OTP will expire in 10 minutes.\nPlease enter this on the verification page.";

            $mail->send();
            $_SESSION['message'] = "Registration initiated! Please check your email for the OTP.";
            header("Location: otp_verify.php?email=" . urlencode($email));
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Error sending OTP: {$mail->ErrorInfo}";
        }
    }

    if (isset($_SESSION['error'])) {
        header("Location: register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Arangkada Car Rentals</title>
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
        input[type="password"],
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
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-btn {
            background: #fff;
            color: #3498db;
            border: 2px solid #3498db;
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.2s;
        }
        .login-btn:hover {
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
        <h2 class="form-title">Create Your Account</h2>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='message error'>{$_SESSION['error']}</div>";
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['message'])) {
            echo "<div class='message success'>{$_SESSION['message']}</div>";
            unset($_SESSION['message']);
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn">Register</button>
        </form>

        <div class="login-link">
            <p>Already have an account?</p>
            <a href="login.php"><button type="button" class="login-btn">Login Now</button></a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Arangkada Car Rentals. All rights reserved.</p>
    </footer>
</body>
</html>
