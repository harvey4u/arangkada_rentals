<?php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
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
        margin: 0;
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

    .menu-divider {
        height: 1px;
        background: #34495e;
        margin: 1rem 1.5rem;
    }

    .menu-header {
        color: #bdc3c7;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 1.5rem 1.5rem 0.5rem;
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
        <li class="menu-header">Main Navigation</li>
        <li class="menu-item">
            <a href="dashboard_superadmin.php" class="menu-link <?= $current_page === 'dashboard_superadmin.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">Management</li>
        <li class="menu-item">
            <a href="manage_users.php" class="menu-link <?= $current_page === 'manage_users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="manage_cars.php" class="menu-link <?= $current_page === 'manage_cars.php' ? 'active' : '' ?>">
                <i class="fas fa-car"></i>
                <span>Cars</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="manage_rentals.php" class="menu-link <?= $current_page === 'manage_rentals.php' ? 'active' : '' ?>">
                <i class="fas fa-file-contract"></i>
                <span>Rentals</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="manage_drivers.php" class="menu-link <?= $current_page === 'manage_drivers.php' ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i>
                <span>Drivers</span>
            </a>
        </li>

        <li class="menu-header">Reports & Analytics</li>
        <li class="menu-item">
            <a href="reports.php" class="menu-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="analytics.php" class="menu-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
        </li>

        <li class="menu-header">System</li>
        <li class="menu-item">
            <a href="system_settings.php" class="menu-link <?= $current_page === 'system_settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="backup.php" class="menu-link <?= $current_page === 'backup.php' ? 'active' : '' ?>">
                <i class="fas fa-database"></i>
                <span>Backup</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="activity_logs.php" class="menu-link <?= $current_page === 'activity_logs.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
            </a>
        </li>
    </ul>

    <div class="user-info">
        <div class="user-info-content">
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="user-role">Superadmin</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div> 