<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Include your DB connection file
require_once 'db.php';

// Get admin info
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

// Get current month and year for statistics
$currentMonth = date('m');
$currentYear = date('Y');

try {
    // Get monthly revenue data from view
    $stmt = $pdo->query("SELECT * FROM monthly_revenue_view ORDER BY month DESC LIMIT 1");
    $monthlyRevenue = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get car performance data
    $stmt = $pdo->query("SELECT * FROM car_performance_view");
    $carPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get driver performance data
    $stmt = $pdo->query("SELECT * FROM driver_performance_view");
    $driverPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user counts by role
    $stmt = $pdo->query("
        SELECT r.name, COUNT(ur.user_id) as count
        FROM roles r
        LEFT JOIN user_roles ur ON r.id = ur.role_id
        GROUP BY r.name
    ");
    $userCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get car statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_cars,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_cars,
            SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) as rented_cars,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_cars
        FROM cars
    ");
    $carStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get maintenance requests count
    $stmt = $pdo->query("
        SELECT COUNT(*) as pending_maintenance
        FROM maintenance_requests 
        WHERE status = 'pending'
    ");
    $maintenanceCount = $stmt->fetchColumn();

    // Get support tickets count
    $stmt = $pdo->query("
        SELECT COUNT(*) as open_tickets
        FROM support_tickets 
        WHERE status = 'open'
    ");
    $openTickets = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --background-light: #f8f9fa;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--background-light);
            color: var(--text-dark);
            line-height: 1.5;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Main container styles */
        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 250px; /* Match sidebar width */
            padding: 2rem;
            background-color: var(--background-light);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* Dashboard specific styles */
        .dashboard-header {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-title {
            font-size: 1.8rem;
            color: var(--text-dark);
            font-weight: 600;
            margin: 0;
        }

        .welcome-message {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .card-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-box h3 {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .card-box span {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-dark);
            display: block;
        }

        .trend {
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .trend-up { color: var(--success-color); }
        .trend-down { color: var(--danger-color); }

        .section-title {
            font-size: 1.2rem;
            color: var(--text-dark);
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        /* Table styles */
        .table-responsive {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        th {
            background: var(--background-light);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-active { background-color: rgba(46, 204, 113, 0.1); color: var(--success-color); }
        .status-pending { background-color: rgba(241, 196, 15, 0.1); color: var(--warning-color); }
        .status-cancelled { background-color: rgba(231, 76, 60, 0.1); color: var(--danger-color); }

        .amount {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include 'sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="dashboard-header">
            <div>
                <h2 class="dashboard-title">Admin Dashboard</h2>
                <p class="welcome-message">Welcome back, <?= htmlspecialchars($admin['username']) ?>!</p>
            </div>
            <div class="date"><?= date('F d, Y') ?></div>
        </div>

        <div class="stats-grid">
            <!-- User Statistics -->
            <div class="card-box card-primary">
                <h3>Total Clients</h3>
                <span><?= $userCounts['client'] ?? 0 ?></span>
                <div class="trend">
                    <i class="fas fa-users"></i> Active Users
                </div>
            </div>

            <div class="card-box card-success">
                <h3>Active Drivers</h3>
                <span><?= $userCounts['driver'] ?? 0 ?></span>
                <div class="trend">
                    <i class="fas fa-id-card"></i> Available for Hire
                </div>
            </div>

            <div class="card-box card-warning">
                <h3>Staff Members</h3>
                <span><?= $userCounts['staff'] ?? 0 ?></span>
                <div class="trend">
                    <i class="fas fa-user-tie"></i> Support Team
                </div>
            </div>

            <!-- Car Statistics -->
            <div class="card-box card-info">
                <h3>Fleet Status</h3>
                <span><?= $carStats['total_cars'] ?? 0 ?></span>
                <div class="trend">
                    Available: <?= $carStats['available_cars'] ?? 0 ?> | 
                    Rented: <?= $carStats['rented_cars'] ?? 0 ?> |
                    Maintenance: <?= $carStats['maintenance_cars'] ?? 0 ?>
                </div>
            </div>

            <!-- Revenue Statistics -->
            <div class="card-box card-success">
                <h3>Monthly Revenue</h3>
                <span>₱<?= number_format($monthlyRevenue['total_revenue'] ?? 0, 2) ?></span>
                <div class="trend">
                    <i class="fas fa-chart-line"></i> 
                    <?= $monthlyRevenue['total_rentals'] ?? 0 ?> rentals this month
                </div>
            </div>

            <!-- Support Statistics -->
            <div class="card-box card-danger">
                <h3>Pending Tasks</h3>
                <span><?= $maintenanceCount + $openTickets ?></span>
                <div class="trend">
                    <i class="fas fa-tools"></i> <?= $maintenanceCount ?> maintenance
                    <i class="fas fa-ticket-alt ml-2"></i> <?= $openTickets ?> tickets
                </div>
            </div>
        </div>

        <!-- Recent Rentals -->
        <h3 class="section-title">Recent Rentals</h3>
        <div class="table-responsive">
            <?php
            try {
                $stmt = $pdo->query("
                    SELECT 
                        r.*,
                        u.username as client_name,
                        c.make,
                        c.model,
                        d.username as driver_name,
                        cc.name as car_category
                    FROM rentals r
                    JOIN users u ON r.client_id = u.id
                    JOIN cars c ON r.car_id = c.id
                    LEFT JOIN users d ON r.driver_id = d.id
                    LEFT JOIN car_categories cc ON c.category_id = cc.id
                    ORDER BY r.created_at DESC
                    LIMIT 5
                ");
                $recentRentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Car</th>
                            <th>Category</th>
                            <th>Driver</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRentals as $rental): ?>
                            <tr>
                                <td><?= htmlspecialchars($rental['client_name']) ?></td>
                                <td><?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?></td>
                                <td><?= htmlspecialchars($rental['car_category']) ?></td>
                                <td><?= $rental['driver_name'] ? htmlspecialchars($rental['driver_name']) : 'No Driver' ?></td>
                                <td><?= date('M d, Y', strtotime($rental['start_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($rental['end_date'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($rental['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($rental['status'])) ?>
                                    </span>
                                </td>
                                <td class="amount">₱<?= number_format($rental['total_price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php
            } catch (PDOException $e) {
                error_log("Error fetching recent rentals: " . $e->getMessage());
                echo "<p class='text-danger'>Unable to load recent rentals.</p>";
            }
            ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 