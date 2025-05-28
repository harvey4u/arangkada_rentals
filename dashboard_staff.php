<?php
require_once 'session.php';
require_once 'db.php';

// Ensure user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --secondary-dark: #27ae60;
            --warning: #f1c40f;
            --warning-dark: #f39c12;
            --danger: #e74c3c;
            --danger-dark: #c0392b;
            --success: #2ecc71;
            --dark: #2c3e50;
            --darker: #243342;
            --light: #ecf0f1;
            --gray: #95a5a6;
            --gray-dark: #7f8c8d;
            --border-radius: 0.5rem;
            --spacing: 1rem;
        }

        .container {
            display: flex;
            min-height: 100vh;
            background-color: #f5f6fa;
            padding-left: 250px;
            transition: padding-left 0.3s ease;
        }

        .main-content {
            flex: 1;
            padding: var(--spacing);
            width: 100%;
        }

        .dashboard-header {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: var(--spacing);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-title {
            font-size: 1.5rem;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--spacing);
            margin-bottom: var(--spacing);
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.primary { border-color: var(--primary); }
        .stat-card.success { border-color: var(--success); }
        .stat-card.warning { border-color: var(--warning); }
        .stat-card.danger { border-color: var(--danger); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--gray);
        }

        .stat-title {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-description {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .content-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: var(--spacing);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light);
        }

        .card-title {
            font-size: 1.25rem;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light);
        }

        th {
            font-weight: 600;
            color: var(--dark);
            background: var(--light);
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-available { background: rgba(46, 204, 113, 0.1); color: var(--success); }
        .status-rented { background: rgba(231, 76, 60, 0.1); color: var(--danger); }
        .status-maintenance { background: rgba(241, 196, 15, 0.1); color: var(--warning); }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .container {
                padding-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar_staff.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Staff Dashboard
                </h1>
                <div class="date"><?= date('F d, Y') ?></div>
            </div>

            <div class="stats-grid">
                <?php
                try {
                    // Count cars by status
                    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'available'");
                    $availableCars = $stmt->fetchColumn();
                    
                    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'rented'");
                    $rentedCars = $stmt->fetchColumn();
                    
                    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'maintenance'");
                    $maintenanceCars = $stmt->fetchColumn();
                    
                    // Count active rentals
                    $stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'");
                    $activeRentals = $stmt->fetchColumn();
                    
                    // Count pending returns
                    $stmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'pending_return'");
                    $pendingReturns = $stmt->fetchColumn();
                    
                } catch (PDOException $e) {
                    error_log("Error fetching statistics: " . $e->getMessage());
                    $availableCars = $rentedCars = $maintenanceCars = $activeRentals = $pendingReturns = 0;
                }
                ?>

                <div class="stat-card success">
                    <div class="stat-header">
                        <span class="stat-title">Available Cars</span>
                        <i class="fas fa-car stat-icon"></i>
                    </div>
                    <div class="stat-value"><?= $availableCars ?></div>
                    <div class="stat-description">Ready for rental</div>
                </div>

                <div class="stat-card primary">
                    <div class="stat-header">
                        <span class="stat-title">Rented Cars</span>
                        <i class="fas fa-key stat-icon"></i>
                    </div>
                    <div class="stat-value"><?= $rentedCars ?></div>
                    <div class="stat-description">Currently in use</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <span class="stat-title">In Maintenance</span>
                        <i class="fas fa-tools stat-icon"></i>
                    </div>
                    <div class="stat-value"><?= $maintenanceCars ?></div>
                    <div class="stat-description">Under service</div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-header">
                        <span class="stat-title">Pending Returns</span>
                        <i class="fas fa-clock stat-icon"></i>
                    </div>
                    <div class="stat-value"><?= $pendingReturns ?></div>
                    <div class="stat-description">Awaiting return</div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-car"></i>
                        Car Status Overview
                    </h2>
                </div>

                <div class="table-responsive">
                    <?php
                    try {
                        $stmt = $pdo->query("
                            SELECT c.*, 
                                   COALESCE(r.status, 'none') as rental_status,
                                   COALESCE(u.username, '') as rented_by
                            FROM cars c
                            LEFT JOIN rentals r ON c.id = r.car_id AND r.status = 'active'
                            LEFT JOIN users u ON r.user_id = u.id
                            ORDER BY c.status, c.make, c.model
                        ");
                        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Car</th>
                                    <th>Status</th>
                                    <th>Rented By</th>
                                    <th>Daily Rate</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cars as $car): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?></strong>
                                            <div class="text-muted"><?= htmlspecialchars($car['year']) ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($car['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($car['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $car['rented_by'] ? htmlspecialchars($car['rented_by']) : '-' ?></td>
                                        <td>₱<?= number_format($car['price_per_day'], 2) ?></td>
                                        <td>
                                            <a href="car_details.php?id=<?= $car['id'] ?>" class="btn btn-primary">
                                                <i class="fas fa-eye"></i>
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-danger">Error fetching car status: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 