<?php
require_once 'session.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Get notifications and counts from database
require_once 'db.php';
try {
    // Count active rentals
    $stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'");
    $activeRentals = $stmt->fetchColumn();

    // Count pending rental requests
    $stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'pending'");
    $pendingRequests = $stmt->fetchColumn();

    // Count maintenance requests
    $stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'pending'");
    $maintenanceRequests = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Error fetching sidebar counts: " . $e->getMessage());
    $activeRentals = $pendingRequests = $maintenanceRequests = 0;
}
?>

<div class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard_staff.php" class="sidebar-brand">
            <i class="fas fa-car"></i>
            <h3>Arangkada</h3>
        </a>
    </div>

    <ul class="menu-items">
        <!-- Dashboard -->
        <li class="menu-item">
            <a href="dashboard_staff.php" class="menu-link <?= $current_page === 'dashboard_staff.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-divider"></li>

        <!-- User Management -->
        <li class="menu-label">User Management</li>
        <li class="menu-item">
            <a href="manage_clients.php" class="menu-link <?= $current_page === 'manage_clients.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Manage Clients</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="manage_drivers.php" class="menu-link <?= $current_page === 'manage_drivers.php' ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i>
                <span>Manage Drivers</span>
            </a>
        </li>

        <li class="menu-divider"></li>

        <!-- Car Management -->
        <li class="menu-label">Car Management</li>
        <li class="menu-item">
            <a href="manage_cars.php" class="menu-link <?= $current_page === 'manage_cars.php' ? 'active' : '' ?>">
                <i class="fas fa-car"></i>
                <span>Manage Cars</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="car_maintenance.php" class="menu-link <?= $current_page === 'car_maintenance.php' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i>
                <span>Maintenance</span>
                <?php if ($maintenanceRequests > 0): ?>
                    <span class="badge warning-badge"><?= $maintenanceRequests ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="menu-divider"></li>

        <!-- Rental Management -->
        <li class="menu-label">Rental Management</li>
        <li class="menu-item">
            <a href="active_rentals.php" class="menu-link <?= $current_page === 'active_rentals.php' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i>
                <span>Active Rentals</span>
                <?php if ($activeRentals > 0): ?>
                    <span class="badge primary-badge"><?= $activeRentals ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="menu-item">
            <a href="rental_requests.php" class="menu-link <?= $current_page === 'rental_requests.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Rental Requests</span>
                <?php if ($pendingRequests > 0): ?>
                    <span class="badge danger-badge"><?= $pendingRequests ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="menu-item">
            <a href="rental_history.php" class="menu-link <?= $current_page === 'rental_history.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                <span>Rental History</span>
            </a>
        </li>

        <li class="menu-divider"></li>

        <!-- Support -->
        <li class="menu-label">Support</li>
        <li class="menu-item">
            <a href="client_support.php" class="menu-link <?= $current_page === 'client_support.php' ? 'active' : '' ?>">
                <i class="fas fa-headset"></i>
                <span>Client Support</span>
            </a>
        </li>

        <li class="menu-divider"></li>

        <!-- Settings -->
        <li class="menu-label">Settings</li>
        <li class="menu-item">
            <a href="profile.php" class="menu-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
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
                <div class="user-role">Staff</div>
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

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

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
        
        /* Font variables */
        --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
        display: flex;
        flex-direction: column;
        font-family: var(--font-primary);
    }

    .sidebar-header {
        padding: 0.8rem;
        background: var(--darker);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-brand {
        color: var(--light);
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .sidebar-brand:hover {
        color: var(--primary);
    }

    .sidebar-brand h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .menu-items {
        padding: 0.5rem 0;
        list-style: none;
        margin: 0;
        flex: 1;
        overflow-y: auto;
    }

    .menu-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 0.8rem 0.8rem;
    }

    .menu-label {
        color: var(--gray);
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.0625rem;
        padding: 0.5rem 1.2rem 0.3rem;
        font-weight: 600;
    }

    .menu-item {
        padding: 0.15rem 0.8rem;
    }

    .menu-link {
        color: var(--light);
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 0.5rem 0.8rem;
        border-radius: 0.3rem;
        transition: all 0.3s ease;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .menu-link:hover {
        background: rgba(52, 152, 219, 0.1);
        color: var(--primary);
    }

    .menu-link.active {
        background: var(--primary);
        color: white;
        font-weight: 600;
    }

    .menu-link i {
        width: 16px;
        text-align: center;
        font-size: 0.9375rem;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 0.5rem;
        font-size: 0.6875rem;
        font-weight: 600;
        border-radius: 9999px;
        margin-left: auto;
        letter-spacing: 0.02em;
    }

    .primary-badge { background: var(--primary); color: white; }
    .danger-badge { background: var(--danger); color: white; }
    .warning-badge { background: var(--warning); color: var(--dark); }

    .user-info {
        padding: 0.8rem;
        background: var(--darker);
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .user-info-content {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 0.8rem;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-avatar i {
        color: white;
        font-size: 1rem;
    }

    .user-details {
        flex-grow: 1;
    }

    .user-name {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--light);
        letter-spacing: -0.01em;
    }

    .user-role {
        font-size: 0.75rem;
        color: var(--gray);
        font-weight: 500;
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--light);
        padding: 0.5rem;
        border-radius: 0.3rem;
        font-size: 0.8125rem;
        font-weight: 500;
        background: var(--danger);
        border: none;
        width: 100%;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .logout-btn:hover {
        background: var(--danger-dark);
    }

    .menu-items::-webkit-scrollbar {
        width: 4px;
    }

    .menu-items::-webkit-scrollbar-track {
        background: var(--darker);
    }

    .menu-items::-webkit-scrollbar-thumb {
        background: var(--gray);
        border-radius: 4px;
    }

    .menu-items::-webkit-scrollbar-thumb:hover {
        background: var(--gray-dark);
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
</style> 