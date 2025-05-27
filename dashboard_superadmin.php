<?php
session_start();

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Fetch system statistics
$stats = [];

// Total users count
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Total cars count
$stmt = $pdo->query("SELECT COUNT(*) FROM cars");
$stats['total_cars'] = $stmt->fetchColumn();

// Total active rentals
$stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'");
$stats['active_rentals'] = $stmt->fetchColumn();

// Total revenue
$stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM rentals WHERE status != 'cancelled'");
$stats['total_revenue'] = $stmt->fetchColumn();

// Recent users
$stmt = $pdo->query("
    SELECT u.*, r.name as role_name 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    LEFT JOIN roles r ON ur.role_id = r.id 
    ORDER BY u.created_at DESC 
    LIMIT 5
");
$recentUsers = $stmt->fetchAll();

// Recent rentals
$stmt = $pdo->query("
    SELECT r.*, u.username, c.make, c.model, c.year 
    FROM rentals r 
    JOIN users u ON r.user_id = u.id 
    JOIN cars c ON r.car_id = c.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$recentRentals = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --primary-dark: #1a252f;
            --secondary-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --info-color: #3498db;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --background-light: #ecf0f1;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            background: var(--background-light);
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .stat-card.users i { color: var(--info-color); }
        .stat-card.cars i { color: var(--success-color); }
        .stat-card.rentals i { color: var(--warning-color); }
        .stat-card.revenue i { color: var(--secondary-color); }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .card {
            background: var(--white);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .card h2 {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--text-dark);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        /* Mobile menu button */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1001;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include the superadmin sidebar -->
    <?php include 'sidebar_superadmin.php'; ?>

    <!-- Mobile menu toggle button -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="stats-grid">
            <div class="stat-card users">
                <i class="fas fa-users"></i>
                <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card cars">
                <i class="fas fa-car"></i>
                <div class="stat-value"><?= number_format($stats['total_cars']) ?></div>
                <div class="stat-label">Total Cars</div>
            </div>
            <div class="stat-card rentals">
                <i class="fas fa-file-contract"></i>
                <div class="stat-value"><?= number_format($stats['active_rentals']) ?></div>
                <div class="stat-label">Active Rentals</div>
            </div>
            <div class="stat-card revenue">
                <i class="fas fa-money-bill-wave"></i>
                <div class="stat-value">₱<?= number_format($stats['total_revenue'], 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h2><i class="fas fa-user-plus"></i> Recent Users</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['role_name'] ?? 'No Role') ?></td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-file-contract"></i> Recent Rentals</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Car</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRentals as $rental): ?>
                            <tr>
                                <td><?= htmlspecialchars($rental['username']) ?></td>
                                <td><?= htmlspecialchars($rental['make'] . ' ' . $rental['model'] . ' ' . $rental['year']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $rental['status'] ?>">
                                        <?= ucfirst($rental['status']) ?>
                                    </span>
                                </td>
                                <td>₱<?= number_format($rental['total_price'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="quick-actions">
                <a href="add_user.php" class="btn">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
                <a href="add_car.php" class="btn">
                    <i class="fas fa-car-side"></i> Add New Car
                </a>
                <a href="reports.php" class="btn">
                    <i class="fas fa-chart-bar"></i> View Reports
                </a>
                <a href="backup.php" class="btn">
                    <i class="fas fa-database"></i> Backup System
                </a>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>
