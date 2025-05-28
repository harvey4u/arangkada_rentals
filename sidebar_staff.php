<?php
// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}
?>

<style>
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
        padding: 1.5rem;
        text-align: center;
        background: var(--darker);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--light);
    }

    .sidebar-menu {
        list-style: none;
        padding: 1rem 0;
        margin: 0;
    }

    .sidebar-menu li {
        padding: 0.5rem 1.5rem;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: var(--light);
        text-decoration: none;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        gap: 0.75rem;
    }

    .sidebar-menu a:hover {
        background: rgba(52, 152, 219, 0.1);
        color: var(--primary);
        text-decoration: none;
    }

    .sidebar-menu a.active {
        background: var(--primary);
        color: white;
    }

    .sidebar-menu a.active:hover {
        background: var(--primary-dark);
        color: white;
    }

    .sidebar-menu i {
        width: 20px;
        text-align: center;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .menu-label {
        color: var(--gray);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 1.5rem 1.5rem 0.5rem;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: none;
        }

        .sidebar.active {
            transform: translateX(0);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
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