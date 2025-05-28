<?php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
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
    }

    .sidebar-brand {
        color: var(--light);
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .sidebar-brand:hover {
        color: var(--primary);
        text-decoration: none;
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
        color: var(--light);
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        gap: 0.75rem;
    }

    .menu-link:hover {
        background: rgba(52, 152, 219, 0.1);
        color: var(--primary);
        text-decoration: none;
    }

    .menu-link.active {
        background: var(--primary);
        color: white;
    }

    .menu-link.active:hover {
        background: var(--primary-dark);
        color: white;
    }

    .menu-link i {
        width: 20px;
        text-align: center;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .menu-header {
        color: var(--gray);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 1.5rem 1.5rem 0.5rem;
        font-weight: 600;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 9999px;
        background: var(--danger);
        color: white;
        min-width: 1.5rem;
        transition: all 0.3s ease;
    }

    .user-info {
        position: sticky;
        bottom: 0;
        width: 100%;
        padding: 1rem 1.5rem;
        background: var(--darker);
        border-top: 1px solid rgba(255,255,255,0.1);
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
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .user-avatar i {
        color: white;
        font-size: 1.2rem;
    }

    .user-details {
        flex-grow: 1;
    }

    .user-name {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
        color: var(--light);
    }

    .user-role {
        font-size: 0.8rem;
        color: var(--gray);
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--light);
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        margin-top: 0.5rem;
        background: var(--danger);
        transition: all 0.3s ease;
        justify-content: center;
        border: none;
        width: 100%;
    }

    .logout-btn:hover {
        background: var(--danger-dark);
        color: white;
        text-decoration: none;
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
        <a href="index.php" class="sidebar-brand">
            <i class="fas fa-car"></i>
            <h3>Arangkada</h3>
        </a>
    </div>

    <ul class="menu-items">
        <li class="menu-header">Main Navigation</li>
        <li class="menu-item">
            <a href="dashboard_admin.php" class="menu-link <?= $current_page === 'dashboard_admin.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">Management</li>
        <li class="menu-item">
            <a href="manage_clients.php" class="menu-link <?= $current_page === 'manage_clients.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Clients</span>
                <?php
                require_once 'db.php';
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role_id = 4 AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $stmt->execute();
                $newClients = $stmt->fetchColumn();
                if ($newClients > 0): ?>
                    <span class="badge"><?= $newClients ?></span>
                <?php endif; ?>
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
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'");
                $activeRentals = $stmt->fetchColumn();
                if ($activeRentals > 0): ?>
                    <span class="badge"><?= $activeRentals ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="menu-item">
            <a href="manage_drivers.php" class="menu-link <?= $current_page === 'manage_drivers.php' ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i>
                <span>Drivers</span>
            </a>
        </li>

        <li class="menu-header">Reports</li>
        <li class="menu-item">
            <a href="rental_reports.php" class="menu-link <?= $current_page === 'rental_reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Rental Reports</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="revenue_reports.php" class="menu-link <?= $current_page === 'revenue_reports.php' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Revenue Reports</span>
            </a>
        </li>

        <li class="menu-header">Settings</li>
        <li class="menu-item">
            <a href="profile.php" class="menu-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="notifications.php" class="menu-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
    </ul>

    <div class="user-info">
        <div class="user-info-content">
            <div class="user-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="user-role">Admin</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div> 