<?php
require_once 'session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

// Additional role-specific access check
$allowed_roles = ['superadmin', 'admin', 'staff', 'client'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

// Get user information
function fetch_user_with_role($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        JOIN roles r ON ur.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
$user = fetch_user_with_role($pdo, $_SESSION['user_id']);

// Get additional role-specific information
$role_stats = [];
switch($user['role_name']) {
    case 'superadmin':
        // Get total users count
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $role_stats['total_users'] = $stmt->fetchColumn();
        // Get total roles count
        $stmt = $pdo->query("SELECT COUNT(*) FROM roles");
        $role_stats['total_roles'] = $stmt->fetchColumn();
        break;
    case 'admin':
        // Get total staff count
        $stmt = $pdo->query("SELECT COUNT(*) FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.name = 'staff'");
        $role_stats['total_staff'] = $stmt->fetchColumn();
        // Get total clients count
        $stmt = $pdo->query("SELECT COUNT(*) FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.name = 'client'");
        $role_stats['total_clients'] = $stmt->fetchColumn();
        break;
    case 'staff':
        // Get total handled rentals
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE staff_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role_stats['handled_rentals'] = $stmt->fetchColumn();
        break;
    case 'client':
        // Get total rentals and active rentals
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE client_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role_stats['total_rentals'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE client_id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        $role_stats['active_rentals'] = $stmt->fetchColumn();
        break;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $profile_picture = $user['profile_picture'] ?? null;

    // Handle profile photo upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profile_photos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $newFileName = uniqid('profile_') . '.' . $fileExtension;
        $uploadFile = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
            // Remove old photo if exists and is not default
            if (!empty($profile_picture) && file_exists($uploadDir . $profile_picture)) {
                @unlink($uploadDir . $profile_picture);
            }
            $profile_picture = $newFileName;
        }
    }

    try {
        $pdo->beginTransaction();

        // Verify current password if trying to change password
        if (!empty($new_password)) {
            if (empty($current_password) || !password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
        }

        // Update other information, including profile_picture
        $stmt = $pdo->prepare("UPDATE users SET email = ?, first_name = ?, last_name = ?, phone = ?, address = ?, date_of_birth = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$email, $first_name, $last_name, $phone, $address, $date_of_birth, $profile_picture, $_SESSION['user_id']]);

        $pdo->commit();
        $success_message = "Profile updated successfully!";

        // Refresh user data (with role)
        $user = fetch_user_with_role($pdo, $_SESSION['user_id']);

    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Arangkada</title>
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

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: var(--spacing-md);
        }

        .role-superadmin {
            background: #4f46e5;
            color: white;
        }

        .role-admin {
            background: #0891b2;
            color: white;
        }

        .role-staff {
            background: #059669;
            color: white;
        }

        .role-client {
            background: #0284c7;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .stat-card {
            background: var(--gray-light);
            padding: var(--spacing-md);
            border-radius: var(--radius-sm);
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: var(--spacing-xs);
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.875rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
        @media (max-width: 600px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Include the appropriate sidebar based on user role
    if ($user['role_name'] === 'superadmin') {
        include 'sidebar_superadmin.php';
    } elseif ($user['role_name'] === 'admin') {
        include 'sidebar_admin.php';
    } elseif ($user['role_name'] === 'staff') {
        include 'sidebar_staff.php';
    } else {
        include 'sidebar_client.php';
    }
    ?>

    <main class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-user-circle"></i>
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

            <div class="role-badge role-<?= strtolower($user['role_name']) ?>">
                <i class="fas <?php
                    switch($user['role_name']) {
                        case 'superadmin':
                            echo 'fa-user-shield';
                            break;
                        case 'admin':
                            echo 'fa-user-tie';
                            break;
                        case 'staff':
                            echo 'fa-user-gear';
                            break;
                        default:
                            echo 'fa-user';
                    }
                ?>"></i>
                <?= ucfirst(htmlspecialchars($user['role_name'])) ?>
            </div>

            <?php if (!empty($role_stats)): ?>
            <div class="stats-grid">
                <?php foreach($role_stats as $label => $value): ?>
                    <div class="stat-card">
                        <div class="stat-value"><?= number_format($value) ?></div>
                        <div class="stat-label"><?= ucwords(str_replace('_', ' ', $label)) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div style="display:flex;justify-content:center;margin-bottom:1.5rem;">
                <?php if (!empty($user['profile_picture']) && file_exists('uploads/profile_photos/' . $user['profile_picture'])): ?>
                    <div style="width:100px;height:100px;border-radius:50%;overflow:hidden;background:#e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
                        <img src="uploads/profile_photos/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                <?php else: ?>
                    <div style="width:100px;height:100px;border-radius:50%;overflow:hidden;background:#e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,0.07);"></div>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label class="form-label" for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Contact Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password (required for password change)</label>
                    <input type="password" id="current_password" name="current_password" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile_picture">Profile Photo</label>
                    <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Profile
                </button>
            </form>
        </div>
    </main>

    <script>
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