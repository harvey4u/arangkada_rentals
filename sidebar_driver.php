<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: login.php');
    exit;
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

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

    .menu-header {
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
        overflow: hidden;
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
                <?php if (!empty($_SESSION['profile_photo'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['profile_photo']) ?>" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
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