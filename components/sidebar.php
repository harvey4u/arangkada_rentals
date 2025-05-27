<?php
function renderSidebar($role) {
    $menuItems = [
        'superadmin' => [
            ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard', 'link' => 'dashboard_superadmin.php'],
            ['icon' => 'fas fa-users', 'text' => 'Manage Users', 'link' => 'manage_users.php'],
            ['icon' => 'fas fa-car', 'text' => 'Manage Cars', 'link' => 'manage_cars.php'],
            ['icon' => 'fas fa-file-contract', 'text' => 'Manage Rentals', 'link' => 'manage_rentals.php'],
            ['icon' => 'fas fa-chart-bar', 'text' => 'Reports', 'link' => 'reports.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => 'system_settings.php']
        ],
        'admin' => [
            ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard', 'link' => 'dashboard_admin.php'],
            ['icon' => 'fas fa-users', 'text' => 'Users', 'link' => 'users.php'],
            ['icon' => 'fas fa-car', 'text' => 'Cars', 'link' => 'cars.php'],
            ['icon' => 'fas fa-file-contract', 'text' => 'Rentals', 'link' => 'rentals.php'],
            ['icon' => 'fas fa-chart-line', 'text' => 'Reports', 'link' => 'reports.php']
        ],
        'staff' => [
            ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard', 'link' => 'dashboard_staff.php'],
            ['icon' => 'fas fa-car', 'text' => 'Cars', 'link' => 'cars.php'],
            ['icon' => 'fas fa-file-contract', 'text' => 'Rentals', 'link' => 'rentals.php'],
            ['icon' => 'fas fa-calendar', 'text' => 'Schedule', 'link' => 'schedule.php']
        ],
        'client' => [
            ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard', 'link' => 'client_dashboard.php'],
            ['icon' => 'fas fa-car', 'text' => 'Browse Cars', 'link' => 'browse_cars.php'],
            ['icon' => 'fas fa-file-contract', 'text' => 'My Rentals', 'link' => 'my_rentals.php'],
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => 'profile.php']
        ],
        'driver' => [
            ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard', 'link' => 'driver_dashboard.php'],
            ['icon' => 'fas fa-route', 'text' => 'My Routes', 'link' => 'my_routes.php'],
            ['icon' => 'fas fa-calendar', 'text' => 'Schedule', 'link' => 'schedule.php'],
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => 'profile.php']
        ]
    ];

    // Get current page filename
    $currentPage = basename($_SERVER['PHP_SELF']);
?>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <i class="fas fa-car"></i>
                <span>Arangkada</span>
            </div>
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="sidebar-user">
            <div class="user-image">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
                <span class="user-role"><?= ucfirst($role) ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($menuItems[$role] as $item): ?>
                <a href="<?= $item['link'] ?>" class="nav-link <?= $currentPage === $item['link'] ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <span><?= $item['text'] ?></span>
                </a>
            <?php endforeach; ?>

            <a href="logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <style>
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            background: #2c3e50;
            color: #ecf0f1;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: #ecf0f1;
            cursor: pointer;
            padding: 0.5rem;
            font-size: 1.25rem;
        }

        .sidebar-user {
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-image i {
            font-size: 2rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
        }

        .user-role {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            background: #3498db;
            font-weight: 600;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .logout {
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #e74c3c;
        }

        /* Main content adjustment */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.sidebar-active {
                margin-left: 250px;
            }
        }
    </style>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('sidebar-active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('active');
                document.querySelector('.main-content').classList.remove('sidebar-active');
            }
        });
    </script>
<?php
}
?> 