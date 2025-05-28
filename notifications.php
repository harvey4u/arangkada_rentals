<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Mark notifications as read if requested
if (isset($_POST['mark_read']) && is_numeric($_POST['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['mark_read'], $_SESSION['user_id']]);
}

// Get user's notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notification-item {
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
        }
        .notification-item.unread {
            background-color: #f8f9fa;
            border-left-color: #e74c3c;
        }
    </style>
</head>
<body>

<?php include 'sidebar_client.php'; ?>

<div class="container" style="margin-left: 270px; padding: 20px;">
    <h2 class="mb-4">Notifications</h2>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <p class="text-muted">No notifications yet.</p>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item p-3 <?= $notification['is_read'] ? '' : 'unread' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                            <small class="text-muted">
                                <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                            </small>
                        </div>
                        <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                        <?php if (!$notification['is_read']): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="mark_read" value="<?= $notification['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-light">Mark as Read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 