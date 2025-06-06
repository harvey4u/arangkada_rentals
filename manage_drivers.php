<?php
require_once 'session.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin', 'staff'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Handle driver creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_driver'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $license_number = $_POST['license_number'];
    $license_expiry = $_POST['license_expiry'];
    $experience_years = $_POST['experience_years'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $license_type = $_POST['license_type'];
    
    // Check for duplicate username or email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $username_exists = $stmt->fetchColumn() > 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $email_exists = $stmt->fetchColumn() > 0;

    if ($username_exists) {
        $error_message = "Username already exists. Please choose another.";
    } elseif ($email_exists) {
        $error_message = "Email already exists. Please use another email address.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $first_name, $last_name, $date_of_birth]);
            $user_id = $pdo->lastInsertId();
            
            // Get driver role ID
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'driver'");
            $stmt->execute();
            $role_id = $stmt->fetchColumn();
            
            // Assign driver role
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $role_id]);
            
            // Add driver details
            $stmt = $pdo->prepare("INSERT INTO driver_details (user_id, license_number, license_expiry, experience_years, contact_number, address, license_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $license_number, $license_expiry, $experience_years, $contact_number, $address, $license_type]);
            
            $pdo->commit();
            $success_message = "Driver created successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error creating driver: " . $e->getMessage();
        }
    }
}

// Fetch all drivers
$stmt = $pdo->query("
    SELECT u.*, r.name as role_name, dd.* 
    FROM users u 
    JOIN user_roles ur ON u.id = ur.user_id 
    JOIN roles r ON ur.role_id = r.id 
    LEFT JOIN driver_details dd ON u.id = dd.user_id
    WHERE r.name = 'driver'
    ORDER BY u.username
");
$drivers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --secondary: #16a34a;
            --secondary-light: #dcfce7;
            --warning: #ca8a04;
            --warning-light: #fef9c3;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --white: #ffffff;
            
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --transition: all 0.2s ease;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--gray-light);
            color: var(--dark);
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: var(--spacing-lg);
            min-height: 100vh;
        }

        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--gray-light);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--gray-light);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .card-title i {
            color: var(--primary);
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        .table-responsive {
            overflow-x: auto;
            margin: 0 -1rem;
            padding: 0 1rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th, td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        th {
            background: var(--gray-light);
            font-weight: 600;
            color: var(--dark);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .driver-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .driver-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: var(--warning-light);
            color: var(--warning);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .license-badge {
            display: inline-flex;
            align-items: center;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
            background: var(--warning-light);
            color: var(--warning);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            padding: var(--spacing-lg);
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .modal-header {
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--gray-light);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .form-group {
            margin-bottom: var(--spacing-md);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: var(--spacing-sm);
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-md);
            font-size: 0.875rem;
        }

        .alert-success {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .alert-danger {
            background: var(--danger-light);
            color: var(--danger);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: var(--spacing-md);
            }
        }

        .edit-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 700px) {
            .edit-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Include the appropriate sidebar based on user role
    if ($_SESSION['role'] === 'superadmin') {
        include 'sidebar_superadmin.php';
    } elseif ($_SESSION['role'] === 'admin') {
        include 'sidebar_admin.php';
    } else {
        include 'sidebar_staff.php';
    }
    ?>

    <main class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-id-card"></i>
                    Manage Drivers
                </h2>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Add New Driver
                </button>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Email</th>
                            <th>License Number</th>
                            <th>License Expiry</th>
                            <th>Experience</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>License Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drivers as $driver): ?>
                            <tr>
                                <td>
                                    <div class="driver-info">
                                        <div class="driver-avatar">
                                            <i class="fas fa-id-card"></i>
                                        </div>
                                        <?= htmlspecialchars($driver['username']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($driver['email']) ?></td>
                                <td>
                                    <span class="license-badge">
                                        <?= htmlspecialchars($driver['license_number']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($driver['license_expiry'])) ?></td>
                                <td><?= htmlspecialchars($driver['experience_years']) ?> years</td>
                                <td><?= htmlspecialchars($driver['contact_number']) ?></td>
                                <td><?= htmlspecialchars($driver['address']) ?></td>
                                <td><?= htmlspecialchars($driver['license_type']) ?></td>
                                <td>
                                    <button class="btn btn-danger" onclick="deleteDriver(<?= $driver['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Driver Modal -->
    <div class="modal" id="addDriverModal">
        <div class="modal-content" style="max-width:600px;overflow-y:auto;">
            <div class="modal-header">
                <h3 class="modal-title">Add New Driver</h3>
            </div>
            <form method="POST">
                <div class="edit-form-grid">
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_number">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="address">Address</label>
                            <input type="text" id="address" name="address" class="form-control">
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="license_number">License Number</label>
                            <input type="text" id="license_number" name="license_number" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="license_expiry">License Expiry</label>
                            <input type="date" id="license_expiry" name="license_expiry" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="experience_years">Years of Experience</label>
                            <input type="number" id="experience_years" name="experience_years" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="license_type">License Type</label>
                            <select id="license_type" name="license_type" class="form-control" required>
                                <option value="Non-Professional">Non-Professional</option>
                                <option value="Professional">Professional</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-top:1rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="submit" name="create_driver" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create Driver
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addDriverModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('addDriverModal').classList.remove('active');
        }

        function deleteDriver(driverId) {
            if (confirm('Are you sure you want to delete this driver?')) {
                // Add delete functionality here
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addDriverModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html> 