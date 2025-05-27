<?php
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Check duplicates
    $emailExists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $emailExists->execute([$email]);

    $usernameExists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $usernameExists->execute([$username]);

    if ($emailExists->fetchColumn()) {
        $_SESSION['error'] = "Email already registered.";
    } elseif ($usernameExists->fetchColumn()) {
        $_SESSION['error'] = "Username already taken.";
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, verification_code, verification_expires_at, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$username, $password, $email, $otp, $expiresAt]);
            $userId = $pdo->lastInsertId();

            // Get role ID for 'renter'
            $roleQuery = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
            $roleQuery->execute(['renter']);
            $role = $roleQuery->fetch();

            if (!$role) {
                // Rollback if role not found
                $pdo->rollBack();
                $_SESSION['error'] = "User role 'renter' not found. Please contact support.";
                header("Location: register.php");
                exit;
            }

            $roleId = $role['id'];

            // Assign role to user
            $assignRole = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $assignRole->execute([$userId, $roleId]);

            // Commit transaction
            $pdo->commit();

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
        } catch (Exception $e) {
            // Rollback on any error
            $pdo->rollBack();
            $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        }
    }

    header("Location: register.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Register</title>
</head>
<body>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <input type="email" name="email" placeholder="Email" required><br><br>
        <button type="submit">Register</button>
    </form>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<p style='color:red;'>{$_SESSION['error']}</p>";
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['message'])) {
        echo "<p style='color:green;'>{$_SESSION['message']}</p>";
        unset($_SESSION['message']);
    }
    ?>

    <p>Already have an account? <a href="login.php"><button type="button">Login</button></a></p>
</body>
</html>
