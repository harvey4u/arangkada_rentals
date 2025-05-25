<?php
require 'db.php';
session_start();

$message = "";

if (!isset($_GET['email'])) {
    $message = "❌ Email not provided.";
} else {
    $email = $_GET['email'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $otp = $_POST['otp'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ?");
        $stmt->execute([$email, $otp]);
        $user = $stmt->fetch();

        if ($user) {
            $currentTime = date('Y-m-d H:i:s');
            if ($user['verification_expires_at'] < $currentTime) {
                $message = "❌ OTP has expired. Please register again or request a new OTP.";
            } elseif ($user['is_verified']) {
                $message = "✅ Your email is already verified.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, verification_expires_at = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                $_SESSION['message'] = "✅ Your email has been successfully verified! You can now log in.";
                header("Location: login.php");
                exit;
            }
        } else {
            $message = "❌ Invalid OTP.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
</head>
<body>
    <h2>Enter the OTP sent to your email</h2>
    <?php if (!isset($_GET['email'])): ?>
        <p><?= $message ?></p>
    <?php else: ?>
        <form method="POST">
            <input type="text" name="otp" placeholder="Enter OTP" required><br><br>
            <button type="submit">Verify</button>
        </form>
        <?php if ($message): ?>
            <p><?= $message ?></p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
