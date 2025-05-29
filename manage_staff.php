<?php
require_once 'session.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Handle staff creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        $user_id = $pdo->lastInsertId();
        
        // Get staff role ID (role_id = 3 for staff)
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'staff'");
        $stmt->execute();
        $role_id = $stmt->fetchColumn();
        
        // Assign staff role
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $role_id]);
        
        $pdo->commit();
        $success_message = "Staff member created successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error creating staff member: " . $e->getMessage();
    }
}

// Handle staff deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $staff_id = $_POST['staff_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Delete from user_roles first (cascade will handle the rest)
        $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = (SELECT id FROM roles WHERE name = 'staff')");
        $stmt->execute([$staff_id]);
        
        // Delete from users
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$staff_id]);
        
        $pdo->commit();
        $success_message = "Staff member deleted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error deleting staff member: " . $e->getMessage();
    }
}

// Fetch all staff members
$stmt = $pdo->query("
    SELECT u.* 
    FROM users u 
    JOIN user_roles ur ON u.id = ur.user_id 
    JOIN roles r ON ur.role_id = r.id 
    WHERE r.name = 'staff'
    ORDER BY u.username
");
$staff_members = $stmt->fetchAll();

// Define departments
$departments = ['Operations', 'Customer Service', 'Maintenance', 'Administration'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Arangkada Car Rentals</title>
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

        .staff-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .staff-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: var(--secondary-light);
            color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .department-badge {
            display: inline-flex;
            align-items: center;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
            background: var(--primary-light);
            color: var(--primary);
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
    </style>
</head>
<body>
    <?php 
    // Include the appropriate sidebar based on user role
    if ($_SESSION['role'] === 'superadmin') {
        include 'sidebar_superadmin.php';
    } else {
        include 'sidebar_admin.php';
    }
    ?>

    <main class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-user-tie"></i>
                    Manage Staff
                </h2>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Add New Staff
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
                            <th>Staff Member</th>
                            <th>Email</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_members as $staff): ?>
                            <tr>
                                <td>
                                    <div class="staff-info">
                                        <div class="staff-avatar">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <?= htmlspecialchars($staff['username']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($staff['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($staff['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                        <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                        <button type="submit" name="delete_staff" class="btn btn-danger">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Staff Modal -->
    <div class="modal" id="addStaffModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Staff Member</h3>
            </div>
            <form method="POST">
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
                    <button type="submit" name="create_staff" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create Staff Member
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
            document.getElementById('addStaffModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('addStaffModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addStaffModal');
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