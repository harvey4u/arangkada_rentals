<?php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
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

    .sidebar.collapsed {
        left: -250px;
    }

    .content-wrapper {
        margin-left: 250px;
        transition: all 0.3s ease;
    }

    .content-wrapper.expanded {
        margin-left: 0;
    }

    .sidebar-header {
        padding: 1.5rem;
        text-align: center;
        background: var(--darker);
        border-bottom: 1px solid rgba(255,255,255,0.1);
        position: relative;
    }

    .hamburger-menu {
        position: absolute;
        right: -40px;
        top: 15px;
        background: var(--dark);
        border: none;
        color: var(--light);
        width: 40px;
        height: 40px;
        border-radius: 0 5px 5px 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .hamburger-menu:hover {
        background: var(--primary);
        color: white;
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
            left: -250px;
        }

        .sidebar.active {
            left: 0;
        }

        .content-wrapper {
            margin-left: 0;
        }

        .content-wrapper.expanded {
            margin-left: 0;
        }

        .hamburger-menu {
            display: flex;
        }
    }
</style>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="hamburger-menu" id="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index.php" class="sidebar-brand">
            <i class="fas fa-car"></i>
            <h3>Arangkada</h3>
        </a>
    </div>

    <ul class="menu-items">
        <li class="menu-header">Main Navigation</li>
        <li class="menu-item">
            <a href="dashboard_client.php" class="menu-link <?= $current_page === 'dashboard_client.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">Car Rental</li>
        <li class="menu-item">
            <a href="browse_cars.php" class="menu-link <?= $current_page === 'browse_cars.php' ? 'active' : '' ?>">
                <i class="fas fa-car"></i>
                <span>Browse Cars</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="active_rentals.php" class="menu-link <?= $current_page === 'active_rentals.php' ? 'active' : '' ?>">
                <i class="fas fa-file-contract"></i>
                <span>Active Rentals</span>
                <?php
                require_once 'db.php';
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = ? AND status = 'active'");
                $stmt->execute([$_SESSION['user_id']]);
                $activeRentals = $stmt->fetchColumn();
                if ($activeRentals > 0): ?>
                    <span class="badge"><?= $activeRentals ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="menu-item">
            <a href="rental_history.php" class="menu-link <?= $current_page === 'rental_history.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                <span>Rental History</span>
            </a>
        </li>

        <li class="menu-header">Payments</li>
        <li class="menu-item">
            <a href="payments.php" class="menu-link <?= $current_page === 'payments.php' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Payment History</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="invoices.php" class="menu-link <?= $current_page === 'invoices.php' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Invoices</span>
            </a>
        </li>

        <li class="menu-header">Support</li>
        <li class="menu-item">
            <a href="support_tickets.php" class="menu-link <?= $current_page === 'support_tickets.php' ? 'active' : '' ?>">
                <i class="fas fa-ticket-alt"></i>
                <span>Support Tickets</span>
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status = 'open'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $openTickets = $stmt->fetchColumn();
                    if ($openTickets > 0): ?>
                        <span class="badge"><?= $openTickets ?></span>
                    <?php endif;
                } catch (PDOException $e) {
                    // Table doesn't exist or other database error
                    // Silently fail - no badge will be shown
                } ?>
            </a>
        </li>
        <li class="menu-item">
            <a href="faq.php" class="menu-link <?= $current_page === 'faq.php' ? 'active' : '' ?>">
                <i class="fas fa-question-circle"></i>
                <span>FAQ</span>
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
            <a href="notifications.php" class="menu-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                    $stmt->execute([$_SESSION['user_id']]);
                    $unreadNotifications = $stmt->fetchColumn();
                    if ($unreadNotifications > 0): ?>
                        <span class="badge"><?= $unreadNotifications ?></span>
                    <?php endif;
                } catch (PDOException $e) {
                    // Table doesn't exist or other database error
                    // Silently fail - no badge will be shown
                } ?>
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
                <div class="user-role">Client</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const contentWrappers = document.querySelectorAll('.container');
    
    // Check for saved state
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    if (sidebarState === 'true') {
        sidebar.classList.add('collapsed');
        contentWrappers.forEach(wrapper => wrapper.style.marginLeft = '0');
    }

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        
        // Save state
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        
        // Update content margin
        contentWrappers.forEach(wrapper => {
            wrapper.style.marginLeft = isCollapsed ? '0' : '250px';
        });
    });

    // Handle responsive behavior
    function checkWidth() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            contentWrappers.forEach(wrapper => wrapper.style.marginLeft = '0');
        } else {
            if (localStorage.getItem('sidebarCollapsed') !== 'true') {
                sidebar.classList.remove('collapsed');
                contentWrappers.forEach(wrapper => wrapper.style.marginLeft = '250px');
            }
        }
    }

    window.addEventListener('resize', checkWidth);
    checkWidth(); // Initial check
});
</script> 