<?php
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>

<style>
    .sidebar {
        width: 250px;
        background: #2c3e50;
        padding: 20px;
        min-height: 100vh;
        color: white;
        transition: all 0.3s ease;
    }

    .sidebar-header {
        padding: 15px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 20px;
    }

    .sidebar-header h3 {
        color: white;
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-menu li {
        margin-bottom: 5px;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .sidebar-menu a:hover, .sidebar-menu a.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .sidebar-menu i {
        margin-right: 10px;
        font-size: 1.1rem;
    }

    .menu-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.4);
        margin: 20px 0 10px;
        letter-spacing: 0.5px;
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>Admin Panel</h3>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard_admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>

        <div class="menu-label">User Management</div>
        <li>
            <a href="manage_staff.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_staff.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                Manage Staff
            </a>
        </li>
        <li>
            <a href="manage_clients.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_clients.php' ? 'active' : '' ?>">
                <i class="fas fa-user-friends"></i>
                Manage Clients
            </a>
        </li>
        <li>
            <a href="manage_drivers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_drivers.php' ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i>
                Manage Drivers
            </a>
        </li>

        <div class="menu-label">Vehicle Management</div>
        <li>
            <a href="manage_cars.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_cars.php' ? 'active' : '' ?>">
                <i class="fas fa-car"></i>
                Manage Cars
            </a>
        </li>
        <li>
            <a href="car_maintenance.php" class="<?= basename($_SERVER['PHP_SELF']) == 'car_maintenance.php' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i>
                Maintenance
            </a>
        </li>

        <div class="menu-label">Business</div>
        <li>
            <a href="rental_history.php" class="<?= basename($_SERVER['PHP_SELF']) == 'rental_history.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                Rental History
            </a>
        </li>
        <li>
            <a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                Reports
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </li>
    </ul>
</div> 