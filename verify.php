<?php
require 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_verified']) {
            $message = "✅ Your email is already verified. You can now <a href='login.php'>login here</a>.";
        } else {
            // Mark as verified and clear token
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            $message = "✅ Your email has been successfully verified! You can now <a href='login.php'>login here</a>.";
        }
    } else {
        $message = "❌ Invalid or expired token.";
    }
} else {
    $message = "❌ No verification token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }
        .message {
            font-size: 1.2em;
            color: #333;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        a {
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="message <?php echo (str_contains($message, '✅')) ? 'success' : 'error'; ?>">
        <?php echo $message; ?>
    </div>
</body>
</html>
