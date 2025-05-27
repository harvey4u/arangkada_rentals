<?php
session_start();

require 'db.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Handle already logged-in users
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            if (!password_verify($password, $user['password'])) {
                $error = "❌ Invalid username or password.";
            } elseif (!$user['is_verified']) {
                $error = "⚠️ Please verify your email before logging in.";
            } else {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // ✅ If called via API (e.g., Android), return plain message
                if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'okhttp') !== false) {
                    echo "Login successful";
                    exit;
                }

                // ✅ If browser, redirect to dashboard
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = "❌ Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }

    // For Android response
    if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'okhttp') !== false) {
        echo $error ?? "Login failed.";
        exit;
    }
}
?>

<!-- HTML Only for Browser UI -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Car Rental System</title>
</head>
<body>
    <h2>Login</h2>

    <?php
    if (isset($_SESSION['message'])) {
        echo "<p style='color:green;'>{$_SESSION['message']}</p>";
        unset($_SESSION['message']);
    }

    if (isset($error)) {
        echo "<p style='color:red;'>$error</p>";
    }
    ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? 
        <a href="register.php"><button type="button">Register</button></a>
    </p>
</body>
</html>
