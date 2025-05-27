<?php
// Include your DB connection file
require_once 'db.php';
session_start();

// Ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<!-- Main Layout -->
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

    .container {
        display: flex;
        background-color: #f5f6fa;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        padding: 2rem;
        margin: 1rem;
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #eee;
    }

    .dashboard-title {
        font-size: 1.8rem;
        color: var(--text-dark);
        font-weight: 600;
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
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .card-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

    .card-box .trend {
        font-size: 0.85rem;
        color: var(--success-color);
        margin-top: 0.5rem;
    }

    .section-title {
        font-size: 1.2rem;
        color: var(--text-dark);
        margin: 2rem 0 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #eee;
    }

    /* Table styling */
    .table-responsive {
        overflow-x: auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
    }

    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    th {
        background-color: var(--background-light);
        color: var(--text-dark);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    tr:hover {
        background-color: #f8f9fa;
    }

    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-active {
        background-color: rgba(46, 204, 113, 0.1);
        color: var(--success-color);
    }

    .status-pending {
        background-color: rgba(241, 196, 15, 0.1);
        color: var(--warning-color);
    }

    .status-cancelled {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger-color);
    }

    .amount {
        font-weight: 600;
        color: var(--text-dark);
    }

    /* Card colors */
    .card-primary { border-left: 4px solid var(--primary-color); }
    .card-success { border-left: 4px solid var(--success-color); }
    .card-warning { border-left: 4px solid var(--warning-color); }
    .card-danger { border-left: 4px solid var(--danger-color); }
</style>

<div class="container">
    <?php include 'sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Admin Control Panel</h2>
            <div class="date"><?= date('F d, Y') ?></div>
        </div>

        <div class="stats-grid">
            <?php
            try {
                // Count users by role
                $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = 2"); // Admin
                $adminCount = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = 3"); // Staff
                $staffCount = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = 4"); // Clients
                $clientCount = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = 5"); // Drivers
                $driverCount = $stmt->fetchColumn();
                
                // Count cars
                $stmt = $pdo->query("SELECT COUNT(*) FROM cars");
                $totalCars = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'available'");
                $availableCars = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'rented'");
                $rentedCars = $stmt->fetchColumn();

                // Count total rentals and revenue
                $stmt = $pdo->query("SELECT COUNT(*) as total_rentals, SUM(total_amount) as total_revenue FROM rentals");
                $rentalStats = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                echo "Error fetching statistics: " . $e->getMessage();
            }
            ?>

            <div class="card-box card-primary">
                <h3>Total Staff</h3>
                <span><?= $staffCount ?></span>
                <div class="trend">↑ 12% from last month</div>
            </div>

            <div class="card-box card-success">
                <h3>Total Clients</h3>
                <span><?= $clientCount ?></span>
                <div class="trend">↑ 8% from last month</div>
            </div>

            <div class="card-box card-warning">
                <h3>Total Drivers</h3>
                <span><?= $driverCount ?></span>
                <div class="trend">↑ 5% from last month</div>
            </div>

            <div class="card-box card-primary">
                <h3>Total Cars</h3>
                <span><?= $totalCars ?></span>
            </div>

            <div class="card-box card-success">
                <h3>Available Cars</h3>
                <span><?= $availableCars ?></span>
            </div>

            <div class="card-box card-warning">
                <h3>Rented Cars</h3>
                <span><?= $rentedCars ?></span>
            </div>

            <div class="card-box card-primary">
                <h3>Total Rentals</h3>
                <span><?= $rentalStats['total_rentals'] ?? 0 ?></span>
            </div>

            <div class="card-box card-success">
                <h3>Total Revenue</h3>
                <span>₱<?= number_format($rentalStats['total_revenue'] ?? 0, 2) ?></span>
                <div class="trend">↑ 15% from last month</div>
            </div>
        </div>

        <h3 class="section-title">Recent Rentals</h3>
        <div class="table-responsive">
            <?php
            try {
                $stmt = $pdo->query("
                    SELECT r.*, u.username, c.make, c.model 
                    FROM rentals r
                    JOIN users u ON r.user_id = u.id
                    JOIN cars c ON r.car_id = c.id
                    ORDER BY r.rental_date DESC
                    LIMIT 5
                ");
                $recentRentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Car</th>
                            <th>Rental Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRentals as $rental): ?>
                            <tr>
                                <td><?= htmlspecialchars($rental['username']) ?></td>
                                <td><?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?></td>
                                <td><?= date('M d, Y', strtotime($rental['rental_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($rental['return_date'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($rental['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($rental['status'])) ?>
                                    </span>
                                </td>
                                <td class="amount">₱<?= number_format($rental['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php
            } catch (PDOException $e) {
                echo "Error fetching recent rentals: " . $e->getMessage();
            }
            ?>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?> 