<?php
session_start();

if (isset($_SESSION['user_id'])) {
    // Check role and redirect accordingly
    if (isset($_SESSION['role'])) {
        switch($_SESSION['role']) {
            case 'superadmin':
                header('Location: superadmin_dashboard.php');
                break;
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
            case 'staff':
                header('Location: staff_dashboard.php');
                break;
            case 'client':
                header('Location: client_dashboard.php');
                break;
            case 'driver':
                header('Location: driver_dashboard.php');
                break;
            default:
                header('Location: dashboard.php');
        }
        exit;
    }
}

require 'db.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name 
                          FROM users u 
                          LEFT JOIN user_roles ur ON u.id = ur.user_id 
                          LEFT JOIN roles r ON ur.role_id = r.id 
                          WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        if (!password_verify($password, $user['password'])) {
            $error = "❌ Invalid username or password.";
        } elseif (!$user['is_verified']) {
            $error = "⚠️ Please verify your email before logging in.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_name'];

            // Redirect based on role
            switch($user['role_name']) {
                case 'superadmin':
                    header('Location: dashboard_superadmin.php');
                    break;
                case 'admin':
                    header('Location: dashboard_admin.php');
                    break;
                case 'staff':
                    header('Location: dashboard_staff.php');
                    break;
                case 'client':
                    header('Location: dashboard_client.php');
                    break;
                case 'driver':
                    header('Location: dashboard_driver.php');
                    break;
                default:
                    header('Location: dashboard.php');
            }
            exit;
        }
    } else {
        $error = "❌ Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Arangkada Car Rentals</title>
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
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-btn {
            background: #fff;
            color: #3498db;
            border: 2px solid #3498db;
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.2s;
        }
        .register-btn:hover {
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
        .forgot-password {
            text-align: center;
            margin: 15px 0;
        }
        .forgot-password a {
            color: #3498db;
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.2s;
        }
        .forgot-password a:hover {
            color: #2980b9;
            text-decoration: underline;
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
        <h2 class="form-title">Login to Your Account</h2>

        <?php
        if (isset($_SESSION['message'])) {
            echo "<div class='message success'>{$_SESSION['message']}</div>";
            unset($_SESSION['message']);
        }

        if (isset($error)) {
            echo "<div class='message error'>$error</div>";
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="forgot-password">
            <a href="recover.php">Forgot Password?</a>
        </div>

        <div class="register-link">
            <p>Don't have an account?</p>
            <a href="register.php"><button type="button" class="register-btn">Register Now</button></a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Arangkada Car Rentals. All rights reserved.</p>
    </footer>
</body>
</html>
