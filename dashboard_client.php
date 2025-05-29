<?php
// Include your DB connection file
require_once 'db.php';
session_start();

// Ensure user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            background-color: #f4f6f9;
        }

        .main-content {
            transition: margin-left 0.3s ease;
            margin-left: 250px;
            padding: 20px;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Stats cards */
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card h3 {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
        }

        /* Table styling */
        .custom-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .custom-table th {
            background: #3498db;
            color: white;
            padding: 15px;
        }

        .custom-table td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .custom-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Toggle button for mobile */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<?php include 'sidebar_client.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>

        <div class="row">
            <?php
            try {
                // Get client's rental statistics
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_rentals,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_rentals,
                        SUM(total_price) as total_spent
                    FROM rentals 
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                // Initialize stats if null
                if (!$stats) {
                    $stats = [
                        'total_rentals' => 0,
                        'active_rentals' => 0,
                        'total_spent' => 0
                    ];
                }
            } catch (PDOException $e) {
                $stats = [
                    'total_rentals' => 0,
                    'active_rentals' => 0,
                    'total_spent' => 0
                ];
            }
            ?>

            <div class="col-md-4">
                <div class="stats-card">
                    <h3><i class="fas fa-car-side me-2"></i>Total Rentals</h3>
                    <div class="number"><?= $stats['total_rentals'] ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stats-card">
                    <h3><i class="fas fa-clock me-2"></i>Active Rentals</h3>
                    <div class="number"><?= $stats['active_rentals'] ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stats-card">
                    <h3><i class="fas fa-money-bill-wave me-2"></i>Total Spent</h3>
                    <div class="number">₱<?= number_format($stats['total_spent'], 2) ?></div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h3 class="card-title mb-0">Recent Rentals</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT r.*, c.make, c.model
                                FROM rentals r
                                JOIN cars c ON r.car_id = c.id
                                WHERE r.user_id = ?
                                ORDER BY r.created_at DESC
                                LIMIT 5
                            ");
                            $stmt->execute([$user_id]);
                            $rentals = $stmt->fetchAll();
                        ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Car</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rentals as $rental): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?></td>
                                                <td><?= htmlspecialchars($rental['start_date']) ?></td>
                                                <td><?= htmlspecialchars($rental['end_date']) ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= strtolower($rental['status']) ?>">
                                                        <?= ucfirst(htmlspecialchars($rental['status'])) ?>
                                                    </span>
                                                </td>
                                                <td>₱<?= number_format($rental['total_price'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-danger">Error fetching rental history.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainContent = document.querySelector('.main-content');
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');

    // Check initial sidebar state
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        mainContent.classList.add('expanded');
    }

    // Listen for sidebar toggle events
    sidebarToggle.addEventListener('click', function() {
        mainContent.classList.toggle('expanded');
    });

    // Handle responsive behavior
    function checkWidth() {
        if (window.innerWidth <= 768) {
            mainContent.classList.add('expanded');
        } else {
            if (localStorage.getItem('sidebarCollapsed') !== 'true') {
                mainContent.classList.remove('expanded');
            }
        }
    }

    window.addEventListener('resize', checkWidth);
    checkWidth(); // Initial check
});
</script>

</body>
</html> 