<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(50));
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        $stmt->execute([$token, $email]);

        $resetLink = "http://yourdomain.com/reset.php?token=$token";
        mail($email, "Password Recovery", "Click the following link to reset your password: $resetLink");

        echo "Password recovery link has been sent to your email.";
    } else {
        echo "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Recovery</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit">Recover Password</button>
    </form>
</body>
</html>
