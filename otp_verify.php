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

// Handle resend OTP timer
$resend_wait_seconds = 60;
$can_resend = true;
$resend_message = '';
if (!isset($_SESSION['otp_last_resend'])) {
    $_SESSION['otp_last_resend'] = 0;
}
if (isset($_GET['resent'])) {
    $resend_message = 'A new OTP has been sent to your email.';
}
if (isset($_SESSION['otp_last_resend']) && (time() - $_SESSION['otp_last_resend']) < $resend_wait_seconds) {
    $can_resend = false;
    $resend_seconds_left = $resend_wait_seconds - (time() - $_SESSION['otp_last_resend']);
}
if (isset($_GET['resend']) && $can_resend) {
    // Actually resend OTP (simulate or implement your email logic here)
    // For demo, just update the session and redirect
    $_SESSION['otp_last_resend'] = time();
    // You would generate a new OTP and send it here, e.g.:
    // $new_otp = ...; $_SESSION['registration']['otp'] = $new_otp; ... send email ...
    header('Location: otp_verify.php?email=' . urlencode($email) . '&resent=1');
    exit;
} elseif (isset($_GET['resend']) && !$can_resend) {
    $resend_message = '⏳ Please wait before requesting another OTP.';
}

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
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, verification_code, verification_expires_at, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([
                    $registration['username'],
                    $registration['password'],
                    $registration['email'],
                    $registration['otp'],
                    $registration['expires_at']
                ]);
                $userId = $pdo->lastInsertId();

                // Get the client role ID
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2ecc71;
            --error-color: #e74c3c;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --background-light: #f4f6f8;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--background-light);
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: var(--primary-color);
            color: var(--white);
            padding: 1.5rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav a {
            color: var(--white);
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }

        .nav a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .container {
            max-width: 450px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .form-title {
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .description {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .email-display {
            text-align: center;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 8px;
            font-size: 1.1rem;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .message.error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .otp-input {
            width: 100%;
            padding: 1rem;
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            background: var(--white);
            transition: all 0.3s ease;
            outline: none;
        }

        .otp-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .resend-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .resend-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .resend-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .footer {
            background: var(--primary-color);
            color: var(--white);
            text-align: center;
            padding: 1.5rem 0;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .nav a {
                margin: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1><i class="fas fa-car"></i> Arangkada Car Rentals</h1>
            <nav class="nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2 class="form-title">Email Verification</h2>
        
        <p class="description">We've sent a verification code to:</p>
        <div class="email-display">
            <i class="fas fa-envelope"></i> <?= htmlspecialchars($email) ?>
        </div>

        <?php if ($message): ?>
            <div class="message error"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($resend_message): ?>
            <div class="message" style="background:#fef9c3;color:#ca8a04;"> <?= $resend_message ?> </div>
        <?php endif; ?>

        <form method="POST">
            <div class="otp-input-container">
                <input type="text" 
                       name="otp" 
                       class="otp-input" 
                       placeholder="Enter OTP" 
                       maxlength="6" 
                       pattern="\d{6}" 
                       title="Please enter a 6-digit code"
                       autocomplete="off"
                       required>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-check-circle"></i> Verify Email
            </button>
        </form>

        <div class="resend-link">
            <a href="#" id="resendOtpLink" style="<?= $can_resend ? '' : 'pointer-events:none;opacity:0.5;' ?>"
               onclick="if(<?= $can_resend ? 'true' : 'false' ?>){window.location.href='otp_verify.php?email=<?= urlencode($email) ?>&resend=1';}return false;">
                <i class="fas fa-redo"></i> Didn't receive the code? Resend OTP
            </a>
            <span id="timer" style="margin-left:0.5em;color:#888;font-size:0.95em;"></span>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Arangkada Car Rentals. All rights reserved.</p>
    </footer>

    <script>
        // Auto-focus OTP input
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.otp-input').focus();
        });

        // Only allow numbers in OTP input
        document.querySelector('.otp-input').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Resend OTP timer
        let canResend = <?= $can_resend ? 'true' : 'false' ?>;
        let secondsLeft = <?= isset($resend_seconds_left) ? $resend_seconds_left : 0 ?>;
        const resendLink = document.getElementById('resendOtpLink');
        const timerSpan = document.getElementById('timer');
        function updateTimer() {
            if (!canResend && secondsLeft > 0) {
                resendLink.style.pointerEvents = 'none';
                resendLink.style.opacity = 0.5;
                timerSpan.textContent = ` (Wait ${secondsLeft}s)`;
                secondsLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                resendLink.style.pointerEvents = 'auto';
                resendLink.style.opacity = 1;
                timerSpan.textContent = '';
                canResend = true;
            }
        }
        if (!canResend && secondsLeft > 0) {
            updateTimer();
        }
    </script>
</body>
</html>
