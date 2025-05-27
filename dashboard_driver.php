<?php
session_start();

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Fetch driver statistics
$stats = [];
$driver_id = $_SESSION['user_id'];

// Total completed trips
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE driver_id = ? AND status = 'completed'");
$stmt->execute([$driver_id]);
$stats['completed_trips'] = $stmt->fetchColumn();

// Total earnings
$stmt = $pdo->prepare("SELECT COALESCE(SUM(driver_fee), 0) FROM rentals WHERE driver_id = ? AND status = 'completed'");
$stmt->execute([$driver_id]);
$stats['total_earnings'] = $stmt->fetchColumn();

// Current active trip
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE driver_id = ? AND status = 'active'");
$stmt->execute([$driver_id]);
$stats['active_trips'] = $stmt->fetchColumn();

// Rating
$stmt = $pdo->prepare("SELECT AVG(driver_rating) FROM rentals WHERE driver_id = ? AND driver_rating IS NOT NULL");
$stmt->execute([$driver_id]);
$stats['average_rating'] = number_format($stmt->fetchColumn(), 1);

// Recent trips
$stmt = $pdo->prepare("
    SELECT r.*, u.username as client_name, c.make, c.model, c.year 
    FROM rentals r 
    JOIN users u ON r.user_id = u.id 
    JOIN cars c ON r.car_id = c.id 
    WHERE r.driver_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute([$driver_id]);
$recentTrips = $stmt->fetchAll();

// Upcoming trips
$stmt = $pdo->prepare("
    SELECT r.*, u.username as client_name, c.make, c.model, c.year 
    FROM rentals r 
    JOIN users u ON r.user_id = u.id 
    JOIN cars c ON r.car_id = c.id 
    WHERE r.driver_id = ? AND r.status = 'scheduled' 
    ORDER BY r.rental_start_date ASC 
    LIMIT 5
");
$stmt->execute([$driver_id]);
$upcomingTrips = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Arangkada Car Rentals</title>
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

        .stat-card.trips i { color: var(--info-color); }
        .stat-card.earnings i { color: var(--success-color); }
        .stat-card.active i { color: var(--warning-color); }
        .stat-card.rating i { color: var(--secondary-color); }

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
        .status-scheduled { background: #fff3cd; color: #856404; }
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

        .rating {
            color: var(--warning-color);
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
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
    <?php include 'sidebar_driver.php'; ?>

    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="stats-grid">
            <div class="stat-card trips">
                <i class="fas fa-route"></i>
                <div class="stat-value"><?= number_format($stats['completed_trips']) ?></div>
                <div class="stat-label">Completed Trips</div>
            </div>
            <div class="stat-card earnings">
                <i class="fas fa-money-bill-wave"></i>
                <div class="stat-value">₱<?= number_format($stats['total_earnings'], 2) ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-card active">
                <i class="fas fa-car"></i>
                <div class="stat-value"><?= number_format($stats['active_trips']) ?></div>
                <div class="stat-label">Active Trips</div>
            </div>
            <div class="stat-card rating">
                <i class="fas fa-star"></i>
                <div class="stat-value"><?= $stats['average_rating'] ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h2><i class="fas fa-history"></i> Recent Trips</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Car</th>
                                <th>Status</th>
                                <th>Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTrips as $trip): ?>
                            <tr>
                                <td><?= htmlspecialchars($trip['client_name']) ?></td>
                                <td><?= htmlspecialchars($trip['make'] . ' ' . $trip['model'] . ' ' . $trip['year']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $trip['status'] ?>">
                                        <?= ucfirst($trip['status']) ?>
                                    </span>
                                </td>
                                <td>₱<?= number_format($trip['driver_fee'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-calendar"></i> Upcoming Trips</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Car</th>
                                <th>Start Date</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingTrips as $trip): ?>
                            <tr>
                                <td><?= htmlspecialchars($trip['client_name']) ?></td>
                                <td><?= htmlspecialchars($trip['make'] . ' ' . $trip['model'] . ' ' . $trip['year']) ?></td>
                                <td><?= date('M d, Y', strtotime($trip['rental_start_date'])) ?></td>
                                <td><?= $trip['rental_duration'] ?> days</td>
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
                <a href="view_schedule.php" class="btn">
                    <i class="fas fa-calendar-alt"></i> View Schedule
                </a>
                <a href="update_availability.php" class="btn">
                    <i class="fas fa-clock"></i> Update Availability
                </a>
                <a href="trip_history.php" class="btn">
                    <i class="fas fa-history"></i> Trip History
                </a>
                <a href="profile.php" class="btn">
                    <i class="fas fa-user-edit"></i> Update Profile
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
