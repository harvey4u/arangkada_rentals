<?php
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

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
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, verification_code, verification_expires_at, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$username, $password, $email, $otp, $expiresAt]);
        $userId = $pdo->lastInsertId();

        // Assign role
        $roleQuery = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $roleQuery->execute(['renter']);
        $roleId = $roleQuery->fetch()['id'];

        $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$userId, $roleId]);

        // Send OTP email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'hontiverosharvey04@gmail.com'; // Your email
            $mail->Password = 'mfpl qxgc kanx njfc'; // Your app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('hontiverosharvey04@gmail.com', 'Arangkada Rental');
            $mail->addAddress($email, $username);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "Hi $username,\n\nYour OTP code is: $otp\nThis OTP will expire in 10 minutes.\nPlease enter this on the verification page.";

            $mail->send();
            $_SESSION['message'] = "Registration successful! Please check your email for the OTP.";
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
    <title>Register</title>
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
