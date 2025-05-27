<?php
if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Define menu items for each role
$menu_items = [
    'superadmin' => [
        ['url' => 'dashboard_superadmin.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
        ['url' => 'manage_users.php', 'icon' => 'fas fa-users', 'text' => 'Manage Users'],
        ['url' => 'manage_cars.php', 'icon' => 'fas fa-car', 'text' => 'Manage Cars'],
        ['url' => 'manage_rentals.php', 'icon' => 'fas fa-file-contract', 'text' => 'Manage Rentals'],
        ['url' => 'reports.php', 'icon' => 'fas fa-chart-bar', 'text' => 'Reports'],
        ['url' => 'system_settings.php', 'icon' => 'fas fa-cog', 'text' => 'Settings']
    ],
    'admin' => [
        ['url' => 'dashboard_admin.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
        ['url' => 'manage_cars.php', 'icon' => 'fas fa-car', 'text' => 'Manage Cars'],
        ['url' => 'manage_rentals.php', 'icon' => 'fas fa-file-contract', 'text' => 'Manage Rentals'],
        ['url' => 'reports.php', 'icon' => 'fas fa-chart-bar', 'text' => 'Reports']
    ],
    'staff' => [
        ['url' => 'dashboard_staff.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
        ['url' => 'manage_rentals.php', 'icon' => 'fas fa-file-contract', 'text' => 'Manage Rentals'],
        ['url' => 'car_status.php', 'icon' => 'fas fa-car-side', 'text' => 'Car Status']
    ],
    'client' => [
        ['url' => 'client_dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
        ['url' => 'browse_cars.php', 'icon' => 'fas fa-car', 'text' => 'Browse Cars'],
        ['url' => 'my_rentals.php', 'icon' => 'fas fa-file-contract', 'text' => 'My Rentals'],
        ['url' => 'profile.php', 'icon' => 'fas fa-user-circle', 'text' => 'Profile']
    ],
    'driver' => [
        ['url' => 'dashboard_driver.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
        ['url' => 'my_assignments.php', 'icon' => 'fas fa-tasks', 'text' => 'My Assignments'],
        ['url' => 'schedule.php', 'icon' => 'fas fa-calendar', 'text' => 'Schedule'],
        ['url' => 'profile.php', 'icon' => 'fas fa-user-circle', 'text' => 'Profile']
    ]
];
?>

<style>
    .sidebar {
        background: #2c3e50;
        width: 250px;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        color: #ecf0f1;
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .sidebar-header {
        padding: 1.5rem;
        text-align: center;
        background: #243342;
        border-bottom: 1px solid #34495e;
    }

    .sidebar-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .sidebar-brand {
        color: #ecf0f1;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .menu-items {
        padding: 1rem 0;
        list-style: none;
    }

    .menu-item {
        padding: 0.5rem 1.5rem;
    }

    .menu-link {
        color: #ecf0f1;
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        gap: 0.75rem;
    }

    .menu-link:hover {
        background: #34495e;
        color: #3498db;
    }

    .menu-link.active {
        background: #3498db;
        color: #fff;
    }

    .menu-link i {
        width: 20px;
        text-align: center;
    }

    .user-info {
        position: absolute;
        bottom: 0;
        width: 100%;
        padding: 1rem 1.5rem;
        background: #243342;
        border-top: 1px solid #34495e;
    }

    .user-info-content {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #3498db;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-details {
        flex-grow: 1;
    }

    .user-name {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .user-role {
        font-size: 0.8rem;
        color: #bdc3c7;
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #ecf0f1;
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.9rem;
        margin-top: 0.5rem;
        background: #e74c3c;
        transition: background 0.3s ease;
    }

    .logout-btn:hover {
        background: #c0392b;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .content {
            margin-left: 0;
        }
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
        <?php
        // Get menu items for current user's role
        $role_menu = $menu_items[$_SESSION['role']] ?? [];
        
        foreach ($role_menu as $item):
            $is_active = $current_page === $item['url'];
        ?>
            <li class="menu-item">
                <a href="<?= $item['url'] ?>" class="menu-link <?= $is_active ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <span><?= $item['text'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="user-info">
        <div class="user-info-content">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="user-role"><?= ucfirst($_SESSION['role']) ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div> 