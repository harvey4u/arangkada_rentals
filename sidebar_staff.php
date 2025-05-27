<?php
// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
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
        <h3>Staff Panel</h3>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard_staff.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard_staff.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>

        <div class="menu-label">Car Management</div>
        <li>
            <a href="car_list.php" class="<?= basename($_SERVER['PHP_SELF']) == 'car_list.php' ? 'active' : '' ?>">
                <i class="fas fa-car"></i>
                Car List
            </a>
        </li>
        <li>
            <a href="car_maintenance.php" class="<?= basename($_SERVER['PHP_SELF']) == 'car_maintenance.php' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i>
                Maintenance
            </a>
        </li>

        <div class="menu-label">Rentals</div>
        <li>
            <a href="active_rentals.php" class="<?= basename($_SERVER['PHP_SELF']) == 'active_rentals.php' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i>
                Active Rentals
            </a>
        </li>
        <li>
            <a href="rental_requests.php" class="<?= basename($_SERVER['PHP_SELF']) == 'rental_requests.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                Rental Requests
            </a>
        </li>
        <li>
            <a href="rental_history.php" class="<?= basename($_SERVER['PHP_SELF']) == 'rental_history.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                Rental History
            </a>
        </li>

        <div class="menu-label">Support</div>
        <li>
            <a href="client_support.php" class="<?= basename($_SERVER['PHP_SELF']) == 'client_support.php' ? 'active' : '' ?>">
                <i class="fas fa-headset"></i>
                Client Support
            </a>
        </li>
    </ul>
</div> 