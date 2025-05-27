<?php
require 'db.php';
session_start();

if (!isset($_GET['email']) || !isset($_SESSION['registration'])) {
    header("Location: register.php");
    exit;
}

$email = $_GET['email'];
$registration = $_SESSION['registration'];

// Verify the email matches the registration
if ($email !== $registration['email']) {
    header("Location: register.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];

    if ($otp === $registration['otp']) {
        if (strtotime($registration['expires_at']) < time()) {
            $message = "❌ OTP has expired. Please register again.";
            unset($_SESSION['registration']);
        } else {
            try {
                // Begin transaction
                $pdo->beginTransaction();

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, is_verified) VALUES (?, ?, ?, 1)");
                $stmt->execute([
                    $registration['username'],
                    $registration['password'],
                    $registration['email']
                ]);
                $userId = $pdo->lastInsertId();

                // Assign client role
                $roleQuery = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
                $roleQuery->execute(['client']);
                $role = $roleQuery->fetch(PDO::FETCH_ASSOC);

                if ($role && isset($role['id'])) {
                    $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")
                         ->execute([$userId, $role['id']]);
                    
                    // Commit transaction
                    $pdo->commit();

                    // Clear registration data
                    unset($_SESSION['registration']);
                    
                    $_SESSION['message'] = "✅ Registration successful! You can now log in.";
                    header("Location: login.php");
                    exit;
                } else {
                    throw new Exception("Error assigning user role.");
                }
            } catch (Exception $e) {
                // Rollback on error
                $pdo->rollBack();
                $message = "❌ Registration failed: " . $e->getMessage();
            }
        }
    } else {
        $message = "❌ Invalid OTP code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification - Arangkada Car Rentals</title>
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
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
            margin-bottom: 15px;
            text-align: center;
            letter-spacing: 0.3em;
            font-size: 1.2em;
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
        .email-display {
            text-align: center;
            color: #3498db;
            font-weight: 600;
            margin-bottom: 20px;
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
        <h2 class="form-title">Email Verification</h2>
        
        <p class="description">Please enter the verification code sent to:</p>
        <p class="email-display"><?= htmlspecialchars($email) ?></p>

        <?php if ($message): ?>
            <div class="message error"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="otp" placeholder="Enter OTP Code" maxlength="6" required>
            </div>
            <button type="submit" class="btn">Verify Email</button>
        </form>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Arangkada Car Rentals. All rights reserved.</p>
    </footer>
</body>
</html>
