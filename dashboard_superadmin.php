<?php
session_start();

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Initialize statistics array
$stats = [];

/**
 * User Statistics
 */
function getUserStatistics($pdo) {
    $stmt = $pdo->query("
        SELECT r.name as role_name, COUNT(u.id) as count
        FROM roles r
        LEFT JOIN user_roles ur ON r.id = ur.role_id
        LEFT JOIN users u ON ur.user_id = u.id
        GROUP BY r.name
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

/**
 * Vehicle Statistics
 */
function getVehicleStatistics($pdo) {
    $stats = [];
    
    // Available cars
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'available'");
    $stats['available_cars'] = $stmt->fetchColumn();
    
    // Rented cars
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'rented'");
    $stats['rented_cars'] = $stmt->fetchColumn();
    
    // Maintenance cars
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'maintenance'");
    $stats['maintenance_cars'] = $stmt->fetchColumn();
    
    return $stats;
}

/**
 * Rental Statistics
 */
function getRentalStatistics($pdo) {
    $stats = [];
    
    // Active rentals
    $stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'");
    $stats['active_rentals'] = $stmt->fetchColumn();
    
    // Pending rentals
    $stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'pending'");
    $stats['pending_rentals'] = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM rentals WHERE status != 'cancelled'");
    $stats['total_revenue'] = $stmt->fetchColumn();
    
    return $stats;
}

/**
 * Recent Activity
 */
function getRecentActivity($pdo) {
    $activity = [];
    
    // Recent users
    $stmt = $pdo->query("
        SELECT u.*, r.name as role_name 
        FROM users u 
        LEFT JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN roles r ON ur.role_id = r.id 
        ORDER BY u.created_at DESC 
        LIMIT 5
    ");
    $activity['recent_users'] = $stmt->fetchAll();
    
    // Recent rentals
    $stmt = $pdo->query("
        SELECT r.*, u.username, c.make, c.model, c.year 
        FROM rentals r 
        JOIN users u ON r.user_id = u.id 
        JOIN cars c ON r.car_id = c.id 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ");
    $activity['recent_rentals'] = $stmt->fetchAll();
    
    return $activity;
}

/**
 * Pending Items Statistics
 */
function getPendingStatistics($pdo) {
    $stats = [];
    
    // Pending drivers
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        JOIN roles r ON ur.role_id = r.id 
        WHERE r.name = 'driver' AND u.is_verified = 0
    ");
    $stats['pending_drivers'] = $stmt->fetchColumn();
    
    // Cars in maintenance
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'maintenance'");
    $stats['pending_maintenance'] = $stmt->fetchColumn();
    
    return $stats;
}

// Gather all statistics
$user_stats = getUserStatistics($pdo);
$stats = array_merge(
    $stats,
    getVehicleStatistics($pdo),
    getRentalStatistics($pdo),
    getPendingStatistics($pdo)
);
$activity = getRecentActivity($pdo);
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
            /* Minimalist Color Palette */
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --secondary: #16a34a;
            --secondary-light: #dcfce7;
            --warning: #ca8a04;
            --warning-light: #fef9c3;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --white: #ffffff;
            
            /* Spacing */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            
            /* Other Variables */
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --transition: all 0.2s ease;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--gray-light);
            color: var(--dark);
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: var(--spacing-lg);
            min-height: 100vh;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--gray-light);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-md);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-icon.users { background: var(--primary-light); color: var(--primary); }
        .stat-icon.cars { background: var(--secondary-light); color: var(--secondary); }
        .stat-icon.rentals { background: var(--warning-light); color: var(--warning); }
        .stat-icon.revenue { background: var(--danger-light); color: var(--danger); }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark);
            margin: var(--spacing-xs) 0;
        }

        .stat-details {
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--gray-light);
        }

        .stat-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--gray);
            font-size: 0.875rem;
            padding: var(--spacing-xs) 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background: var(--gray-light);
            color: var(--gray);
        }

        .management-section {
            background: var(--white);
            border-radius: var(--radius);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-light);
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--gray-light);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .section-title i {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-md);
        }

        .management-card {
            background: var(--white);
            border: 1px solid var(--gray-light);
            border-radius: var(--radius);
            padding: var(--spacing-md);
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .management-card:hover {
            background: var(--gray-light);
            border-color: var(--primary);
        }

        .management-card i {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .management-info {
            flex: 1;
        }

        .management-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .management-subtitle {
            color: var(--gray);
            font-size: 0.875rem;
        }

        @media (max-width: 1200px) {
            .quick-stats {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: var(--spacing-md);
            }

            .management-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: var(--spacing-md);
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_superadmin.php'; ?>

    <main class="main-content">
        <h2 style="font-size:1.2rem;font-weight:600;color:#64748b;margin-bottom:1.5rem;">Quick Stats</h2>
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="badge badge-primary">Total Users</span>
                </div>
                <div class="stat-value"><?= array_sum($user_stats) ?></div>
                <div class="stat-details">
                    <?php foreach ($user_stats as $role => $count): ?>
                        <div class="stat-detail">
                            <?= ucfirst($role) ?>: <?= $count ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon cars">
                        <i class="fas fa-car"></i>
                    </div>
                    <span class="badge badge-primary">Vehicle Status</span>
                </div>
                <div class="stat-value"><?= $stats['available_cars'] ?></div>
                <div class="stat-details">
                    <div class="stat-detail">Available Cars</div>
                    <div class="stat-detail">Rented: <?= $stats['rented_cars'] ?></div>
                    <div class="stat-detail">Maintenance: <?= $stats['maintenance_cars'] ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon rentals">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <span class="badge badge-warning">Active Rentals</span>
                </div>
                <div class="stat-value"><?= $stats['active_rentals'] ?></div>
                <div class="stat-details">
                    <div class="stat-detail">Pending: <?= $stats['pending_rentals'] ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <span class="badge badge-danger">Total Revenue</span>
                </div>
                <div class="stat-value">₱<?= number_format($stats['total_revenue'], 2) ?></div>
            </div>
        </div>

        <div class="management-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-users-cog"></i>
                    User Management
                </h2>
            </div>
            <div class="management-grid">
                <a href="manage_admins.php" class="management-card">
                    <i class="fas fa-user-tie"></i>
                    <div class="management-info">
                        <div class="management-title">Manage Admins</div>
                        <div class="management-subtitle">System administrators</div>
                    </div>
                </a>
                <a href="manage_staff.php" class="management-card">
                    <i class="fas fa-user-cog"></i>
                    <div class="management-info">
                        <div class="management-title">Manage Staff</div>
                        <div class="management-subtitle">Support staff members</div>
                    </div>
                </a>
                <a href="manage_drivers.php" class="management-card">
                    <i class="fas fa-id-card"></i>
                    <div class="management-info">
                        <div class="management-title">Manage Drivers</div>
                        <div class="management-subtitle">Professional drivers</div>
                        <?php if ($stats['pending_drivers'] > 0): ?>
                            <span class="badge badge-warning"><?= $stats['pending_drivers'] ?> pending</span>
                        <?php endif; ?>
                    </div>
                </a>
                <a href="manage_clients.php" class="management-card">
                    <i class="fas fa-users"></i>
                    <div class="management-info">
                        <div class="management-title">Manage Clients</div>
                        <div class="management-subtitle">Rental customers</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="management-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-car"></i>
                    Vehicle Management
                </h2>
            </div>
            <div class="management-grid">
                <a href="manage_cars.php" class="management-card">
                    <i class="fas fa-car"></i>
                    <div class="management-info">
                        <div class="management-title">Manage Cars</div>
                        <div class="management-subtitle">Vehicle inventory</div>
                    </div>
                </a>
                <a href="car_maintenance.php" class="management-card">
                    <i class="fas fa-tools"></i>
                    <div class="management-info">
                        <div class="management-title">Maintenance</div>
                        <div class="management-subtitle">Vehicle maintenance</div>
                        <?php if ($stats['pending_maintenance'] > 0): ?>
                            <span class="badge badge-warning"><?= $stats['pending_maintenance'] ?> pending</span>
                        <?php endif; ?>
                    </div>
                </a>
                <a href="car_categories.php" class="management-card">
                    <i class="fas fa-tags"></i>
                    <div class="management-info">
                        <div class="management-title">Categories</div>
                        <div class="management-subtitle">Vehicle categories</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="management-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-file-contract"></i>
                    Rental Management
                </h2>
            </div>
            <div class="management-grid">
                <a href="manage_rentals.php" class="management-card">
                    <i class="fas fa-file-contract"></i>
                    <div class="management-info">
                        <div class="management-title">Active Rentals</div>
                        <div class="management-subtitle">Current rentals</div>
                        <?php if ($stats['active_rentals'] > 0): ?>
                            <span class="badge badge-primary"><?= $stats['active_rentals'] ?> active</span>
                        <?php endif; ?>
                    </div>
                </a>
                <a href="rental_requests.php" class="management-card">
                    <i class="fas fa-clock"></i>
                    <div class="management-info">
                        <div class="management-title">Rental Requests</div>
                        <div class="management-subtitle">Pending approvals</div>
                        <?php if ($stats['pending_rentals'] > 0): ?>
                            <span class="badge badge-warning"><?= $stats['pending_rentals'] ?> pending</span>
                        <?php endif; ?>
                    </div>
                </a>
                <a href="rental_calendar.php" class="management-card">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="management-info">
                        <div class="management-title">Rental Calendar</div>
                        <div class="management-subtitle">Schedule overview</div>
                    </div>
                </a>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
