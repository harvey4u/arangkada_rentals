<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

// Get user's role and permissions
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get user's permissions based on their role
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.* 
        FROM permissions p
        JOIN role_permissions rp ON p.id = rp.permission_id
        JOIN roles r ON rp.role_id = r.id
        WHERE r.name = ?
        ORDER BY p.menu_category, p.name
    ");
    $stmt->execute([$user_role]);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group permissions by category
    $menu_categories = [];
    foreach ($permissions as $permission) {
        $category = $permission['menu_category'];
        if (!isset($menu_categories[$category])) {
            $menu_categories[$category] = [];
        }
        $menu_categories[$category][] = $permission;
    }

    // Get notification counts
    $notifications = [
        'new_clients' => 0,
        'active_rentals' => 0,
        'pending_drivers' => 0,
        'unread_notifications' => 0,
        'active_trips' => 0,
        'scheduled_trips' => 0,
        'new_reviews' => 0,
        'pending_rentals' => 0
    ];

    // Only fetch counts if they have the corresponding permissions
    foreach ($permissions as $permission) {
        switch ($permission['name']) {
            case 'manage_clients':
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM users u 
                    JOIN user_roles ur ON u.id = ur.user_id 
                    WHERE ur.role_id = 4 
                    AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ");
                $stmt->execute();
                $notifications['new_clients'] = $stmt->fetchColumn();
                break;

            case 'manage_active_rentals':
                $stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'");
                $notifications['active_rentals'] = $stmt->fetchColumn();
                break;

            case 'manage_drivers':
                $stmt = $pdo->query("
                    SELECT COUNT(*) FROM users u 
                    JOIN user_roles ur ON u.id = ur.user_id 
                    WHERE ur.role_id = 5 
                    AND u.is_verified = 0
                ");
                $notifications['pending_drivers'] = $stmt->fetchColumn();
                break;

            case 'view_notifications':
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM notifications 
                    WHERE user_id = ? AND is_read = 0
                ");
                $stmt->execute([$user_id]);
                $notifications['unread_notifications'] = $stmt->fetchColumn();
                break;

            case 'view_active_trips':
                if ($user_role === 'driver') {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM rentals 
                        WHERE driver_id = ? AND status = 'active'
                    ");
                    $stmt->execute([$user_id]);
                    $notifications['active_trips'] = $stmt->fetchColumn();
                }
                break;

            // Add more cases as needed
        }
    }

} catch (PDOException $e) {
    error_log("Error fetching permissions: " . $e->getMessage());
    $permissions = [];
    $menu_categories = [];
    $notifications = [];
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* Keep all the existing CSS styles from the original sidebars */
    :root {
        --primary: #3498db;
        --primary-dark: #2980b9;
        --secondary: #2ecc71;
        --dark: #2c3e50;
        --darker: #243342;
        --light: #ecf0f1;
        --danger: #e74c3c;
        --danger-dark: #c0392b;
        --gray: #95a5a6;
        --gray-dark: #7f8c8d;
        --success: #2ecc71;
        --warning: #f1c40f;
    }

    .sidebar {
        background: var(--dark);
        width: 250px;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        color: var(--light);
        transition: all 0.3s ease;
        z-index: 1000;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar-header {
        padding: 20px 20px 20px 20px;
        border-bottom: 1px solid var(--gray);
    }

    .sidebar-brand {
        text-decoration: none;
        color: var(--light);
        font-size: 1.2em;
        font-weight: bold;
    }

    .sidebar-brand i {
        margin-right: 10px;
    }

    .menu-items {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .menu-header {
        padding: 10px 20px;
        font-weight: bold;
        color: var(--gray);
    }

    .menu-item {
        padding: 10px 20px;
        border-bottom: 1px solid var(--gray);
    }

    .menu-link {
        text-decoration: none;
        color: var(--light);
        display: flex;
        align-items: center;
        gap: 10px;
        transition: color 0.3s;
    }

    .menu-link:hover {
        color: var(--primary);
    }

    .menu-link.active {
        color: var(--primary);
    }

    .menu-link i {
        min-width: 20px;
    }

    .user-info {
        padding: 20px;
        border-top: 1px solid var(--gray);
    }

    .user-info-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--gray);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-avatar i {
        color: var(--dark);
        font-size: 1.2em;
    }

    .user-details {
        flex-grow: 1;
    }

    .user-name {
        font-weight: bold;
    }

    .user-role {
        color: var(--gray);
    }

    .logout-btn {
        background: none;
        border: none;
        color: var(--light);
        font: inherit;
        cursor: pointer;
        outline: inherit;
        width: 100%;
        padding: 10px;
        text-align: left;
        transition: background 0.3s;
    }

    .logout-btn:hover {
        background-color: var(--danger);
    }

    .logout-btn i {
        margin-right: 10px;
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-brand">
            <i class="fas fa-car"></i>
            <h3>Arangkada</h3>
        </a>
    </div>

    <ul class="menu-items">
        <?php foreach ($menu_categories as $category => $category_permissions): ?>
            <li class="menu-header"><?= htmlspecialchars($category) ?></li>
            <?php foreach ($category_permissions as $permission): ?>
                <li class="menu-item">
                    <a href="<?= htmlspecialchars($permission['menu_path']) ?>" 
                       class="menu-link <?= $current_page === basename($permission['menu_path']) ? 'active' : '' ?>">
                        <i class="<?= htmlspecialchars($permission['icon']) ?>"></i>
                        <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $permission['name']))) ?></span>
                        
                        <?php
                        // Add badges based on notification counts
                        $badge_count = 0;
                        switch ($permission['name']) {
                            case 'manage_clients':
                                $badge_count = $notifications['new_clients'];
                                $badge_class = 'client-badge';
                                break;
                            case 'manage_active_rentals':
                                $badge_count = $notifications['active_rentals'];
                                $badge_class = 'rental-badge';
                                break;
                            case 'manage_drivers':
                                $badge_count = $notifications['pending_drivers'];
                                $badge_class = 'driver-badge';
                                break;
                            case 'view_notifications':
                                $badge_count = $notifications['unread_notifications'];
                                $badge_class = 'notification-badge';
                                break;
                            case 'view_active_trips':
                                $badge_count = $notifications['active_trips'];
                                $badge_class = 'trip-badge';
                                break;
                        }
                        if ($badge_count > 0):
                        ?>
                            <span class="badge <?= $badge_class ?>"><?= $badge_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>

    <div class="user-info">
        <div class="user-info-content">
            <div class="user-avatar">
                <i class="fas <?= $user_role === 'superadmin' ? 'fa-user-shield' : 
                              ($user_role === 'admin' ? 'fa-user-tie' : 
                              ($user_role === 'driver' ? 'fa-id-card' : 'fa-user')) ?>"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="user-role"><?= ucfirst(htmlspecialchars($user_role)) ?></div>
            </div>
        </div>
        <form action="logout.php" method="post" style="width: 100%;">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add responsive sidebar toggle functionality if needed
    const sidebar = document.querySelector('.sidebar');
    
    function checkWidth() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
    }

    window.addEventListener('resize', checkWidth);
    checkWidth(); // Initial check
});
</script>
