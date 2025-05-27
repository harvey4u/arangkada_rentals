<?php
// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
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
        <h3>Client Portal</h3>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard_client.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard_client.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>

        <div class="menu-label">Rentals</div>
        <li>
            <a href="available_cars.php" class="<?= basename($_SERVER['PHP_SELF']) == 'available_cars.php' ? 'active' : '' ?>">
                <i class="fas fa-car"></i>
                Available Cars
            </a>
        </li>
        <li>
            <a href="my_rentals.php" class="<?= basename($_SERVER['PHP_SELF']) == 'my_rentals.php' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i>
                My Rentals
            </a>
        </li>
        <li>
            <a href="rental_history.php" class="<?= basename($_SERVER['PHP_SELF']) == 'rental_history.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                Rental History
            </a>
        </li>

        <div class="menu-label">Account</div>
        <li>
            <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user"></i>
                My Profile
            </a>
        </li>
        <li>
            <a href="payment_methods.php" class="<?= basename($_SERVER['PHP_SELF']) == 'payment_methods.php' ? 'active' : '' ?>">
                <i class="fas fa-credit-card"></i>
                Payment Methods
            </a>
        </li>
        <li>
            <a href="support.php" class="<?= basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : '' ?>">
                <i class="fas fa-question-circle"></i>
                Support
            </a>
        </li>
    </ul>
</div> 