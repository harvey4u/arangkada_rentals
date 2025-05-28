<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    $error = '';
    $success = '';
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $success = "Password successfully updated!";
            } else {
                $error = "New password must be at least 8 characters long.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

// Handle notification preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE user_preferences SET email_notifications = ?, sms_notifications = ? WHERE user_id = ?");
    $stmt->execute([$email_notifications, $sms_notifications, $_SESSION['user_id']]);
    $pref_success = "Preferences updated successfully!";
}

// Get current preferences
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$preferences = $stmt->fetch();
if (!$preferences) {
    // Create default preferences if none exist
    $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, email_notifications, sms_notifications) VALUES (?, 1, 1)");
    $stmt->execute([$_SESSION['user_id']]);
    $preferences = ['email_notifications' => 1, 'sms_notifications' => 1];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar_client.php'; ?>

<div class="container" style="transition: margin-left 0.3s ease; margin-left: 250px; padding: 20px;">
    <h2 class="mb-4">Settings</h2>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Notification Preferences</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($pref_success)): ?>
                        <div class="alert alert-success"><?= $pref_success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="email_notifications" name="email_notifications"
                                <?= $preferences['email_notifications'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notifications">
                                Receive Email Notifications
                            </label>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="sms_notifications" name="sms_notifications"
                                <?= $preferences['sms_notifications'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sms_notifications">
                                Receive SMS Notifications
                            </label>
                        </div>
                        
                        <button type="submit" name="update_preferences" class="btn btn-primary">Save Preferences</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 