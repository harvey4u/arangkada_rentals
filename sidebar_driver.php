<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
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
        overflow-y: auto;
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

    .menu-header {
        color: #bdc3c7;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 1.5rem 1.5rem 0.5rem;
        font-weight: 600;
    }

    .menu-divider {
        height: 1px;
        background: #34495e;
        margin: 1rem 1.5rem;
    }

    .user-info {
        position: sticky;
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

    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 9999px;
        background: #e74c3c;
        color: white;
        min-width: 1.5rem;
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
        justify-content: center;
    }

    .logout-btn:hover {
        background: #c0392b;
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

<div class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-brand">
            <i class="fas fa-car"></i>
            <h3>Arangkada</h3>
        </a>
    </div>

    <ul class="menu-items">
        <li class="menu-header">Maisn Navigation</li>
        <li class="menu-item">
            <a href="dashboard_driver.php" class="menu-link <?= $current_page === 'dashboard_driver.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">Trip Management</li>
        <li class="menu-item">
            <a href="active_trips.php" class="menu-link <?= $current_page === 'active_trips.php' ? 'active' : '' ?>">
                <i class="fas fa-route"></i>
                <span>Active Trips</span>
                <?php
                // Get count of active trips
                require_once 'db.php';
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE driver_id = ? AND status = 'active'");
                $stmt->execute([$_SESSION['user_id']]);
                $activeCount = $stmt->fetchColumn();
                if ($activeCount > 0): ?>
                    <span class="badge"><?= $activeCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="menu-item">
            <a href="upcoming_trips.php" class="menu-link <?= $current_page === 'upcoming_trips.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Upcoming Trips</span>
                <?php
                // Get count of scheduled trips
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE driver_id = ? AND status = 'scheduled'");
                $stmt->execute([$_SESSION['user_id']]);
                $scheduledCount = $stmt->fetchColumn();
                if ($scheduledCount > 0): ?>
                    <span class="badge"><?= $scheduledCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="menu-item">
            <a href="trip_history.php" class="menu-link <?= $current_page === 'trip_history.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                <span>Trip History</span>
            </a>
        </li>

        <li class="menu-header">Schedule & Earnings</li>
        <li class="menu-item">
            <a href="my_schedule.php" class="menu-link <?= $current_page === 'my_schedule.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-week"></i>
                <span>My Schedule</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="availability.php" class="menu-link <?= $current_page === 'availability.php' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i>
                <span>Set Availability</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="earnings.php" class="menu-link <?= $current_page === 'earnings.php' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>My Earnings</span>
            </a>
        </li>

        <li class="menu-header">Performance</li>
        <li class="menu-item">
            <a href="reviews.php" class="menu-link <?= $current_page === 'reviews.php' ? 'active' : '' ?>">
                <i class="fas fa-star"></i>
                <span>My Reviews</span>
                <?php
                // Get count of new reviews
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE driver_id = ? AND driver_rating IS NOT NULL AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $stmt->execute([$_SESSION['user_id']]);
                $newReviews = $stmt->fetchColumn();
                if ($newReviews > 0): ?>
                    <span class="badge"><?= $newReviews ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="menu-item">
            <a href="statistics.php" class="menu-link <?= $current_page === 'statistics.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Statistics</span>
            </a>
        </li>

        <li class="menu-header">Account</li>
        <li class="menu-item">
            <a href="profile.php" class="menu-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="documents.php" class="menu-link <?= $current_page === 'documents.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>My Documents</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="settings.php" class="menu-link <?= $current_page === 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <div class="user-info">
        <div class="user-info-content">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="user-role">Driver</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div> 