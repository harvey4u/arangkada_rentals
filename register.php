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
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $date_of_birth = $_POST['date_of_birth'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];

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
            'expires_at' => $expiresAt,
            'phone' => $phone,
            'address' => $address,
            'date_of_birth' => $date_of_birth,
            'first_name' => $first_name,
            'last_name' => $last_name
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --danger: #e74c3c;
            --success: #2ecc71;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: var(--primary);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--light) !important;
        }

        .nav-link {
            color: var(--light) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }

        .register-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            min-width: 320px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 600;
        }

        .register-header p {
            color: var(--dark);
            opacity: 0.7;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-bottom: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 2rem;
            font-weight: 500;
            width: 100%;
            margin-bottom: 1rem;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 2rem;
            font-weight: 500;
            width: 100%;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        .alert {
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: none;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            color: #666;
            position: relative;
            z-index: 2;
        }

        footer {
            background: var(--dark);
            color: var(--light);
            padding: 1.5rem 0;
            margin-top: auto;
        }

        .password-requirements {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: -0.5rem;
            margin-bottom: 1rem;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
        }

        .password-requirements i {
            margin-right: 0.5rem;
            color: var(--primary);
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -0.5rem;
            margin-left: -0.5rem;
        }

        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding-right: 0.5rem;
            padding-left: 0.5rem;
        }

        @media (max-width: 600px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 1rem;
            }
            .row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-car"></i> Arangkada
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h1><i class="fas fa-user-plus"></i></h1>
                <h1>Create Account</h1>
                <p>Join Arangkada Car Rentals today</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['message'] ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="first_name" placeholder="First Name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="last_name" placeholder="Last Name" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" name="phone" placeholder="Contact Number" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" class="form-control" name="address" placeholder="Address" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                        <input type="date" class="form-control" name="date_of_birth" placeholder="Date of Birth" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="divider">
                <span>or</span>
            </div>

            <a href="login.php" class="btn btn-outline-primary">
                <i class="fas fa-sign-in-alt"></i> Login to Existing Account
            </a>
        </div>
    </div>

    <footer class="text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> Arangkada Car Rentals. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        var pw = document.getElementById('password').value;
        var cpw = document.getElementById('confirm_password').value;
        if (pw !== cpw) {
            e.preventDefault();
            alert('Passwords do not match!');
            document.getElementById('confirm_password').focus();
        }
        if (!document.getElementById('terms').checked) {
            e.preventDefault();
            alert('You must agree to the Terms and Conditions to register.');
            document.getElementById('terms').focus();
        }
    });
    </script>
</body>
</html>
