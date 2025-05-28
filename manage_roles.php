<?php
session_start();

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete existing role
        $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Get role ID for the new role
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$new_role]);
        $role_id = $stmt->fetchColumn();
        
        // Insert new role
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $role_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $success_message = "Role updated successfully!";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error_message = "Error updating role: " . $e->getMessage();
    }
}

// Fetch all users with their roles
$stmt = $pdo->query("
    SELECT u.*, r.name as role_name 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    LEFT JOIN roles r ON ur.role_id = r.id 
    ORDER BY u.username
");
$users = $stmt->fetchAll();

// Fetch available roles
$stmt = $pdo->query("SELECT name FROM roles ORDER BY name");
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            /* Minimalist Color Palette */
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
            
            /* Spacing */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            
            /* Other Variables */
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
            font-size: 1.25rem;
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
            margin-top: var(--spacing-md);
        }

        th, td {
            padding: var(--spacing-md) var(--spacing-sm);
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

        th:first-child { border-top-left-radius: var(--radius-sm); }
        th:last-child { border-top-right-radius: var(--radius-sm); }
        tr:last-child td:first-child { border-bottom-left-radius: var(--radius-sm); }
        tr:last-child td:last-child { border-bottom-right-radius: var(--radius-sm); }

        tr:hover {
            background-color: var(--gray-light);
        }

        .role-select {
            padding: var(--spacing-sm);
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-sm);
            width: 100%;
            max-width: 200px;
            font-size: 0.875rem;
            color: var(--dark);
            background-color: var(--white);
            cursor: pointer;
            transition: var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%232c3e50' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
        }

        .role-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary);
            opacity: 0.9;
        }

        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
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

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .role-superadmin { background: var(--danger-light); color: var(--danger); }
        .role-admin { background: var(--primary-light); color: var(--primary); }
        .role-staff { background: var(--secondary-light); color: var(--secondary); }
        .role-driver { background: var(--warning-light); color: var(--warning); }
        .role-client { background: var(--gray-light); color: var(--gray); }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: var(--spacing-md);
            }

            .card {
                padding: var(--spacing-md);
            }

            .table-responsive {
                margin: 0 -1.5rem;
                padding: 0 1.5rem;
            }

            th, td {
                padding: var(--spacing-sm);
            }

            .role-select {
                max-width: 140px;
            }

            .btn {
                padding: var(--spacing-xs) var(--spacing-sm);
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_superadmin.php'; ?>

    <main class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-user-tag"></i>
                    Role Management
                </h2>
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
                            <th>User</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>New Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <?= htmlspecialchars($user['username']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="role-badge role-<?= strtolower($user['role_name']) ?>">
                                        <i class="fas fa-<?= getRoleIcon($user['role_name']) ?>"></i>
                                        <?= htmlspecialchars($user['role_name'] ?? 'No Role') ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="new_role" class="role-select">
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= htmlspecialchars($role) ?>" 
                                                    <?= $role === $user['role_name'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($role) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                </td>
                                <td>
                                        <button type="submit" name="update_role" class="btn btn-primary">
                                            <i class="fas fa-sync-alt"></i>
                                            Update
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

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }

            // Auto-hide alerts after 5 seconds
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

    <?php
    // Helper function to get appropriate icon for each role
    function getRoleIcon($role) {
        switch (strtolower($role)) {
            case 'superadmin':
                return 'user-shield';
            case 'admin':
                return 'user-tie';
            case 'staff':
                return 'user-cog';
            case 'driver':
                return 'id-card';
            case 'client':
                return 'user';
            default:
                return 'user';
        }
    }
    ?>
</body>
</html>
