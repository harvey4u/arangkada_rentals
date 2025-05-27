<?php
// Include your DB connection file
require_once 'db.php';
session_start();

// Ensure user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
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

    .status-available {
        background-color: rgba(46, 204, 113, 0.1);
        color: var(--success-color);
    }

    .status-rented {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger-color);
    }

    .status-maintenance {
        background-color: rgba(241, 196, 15, 0.1);
        color: var(--warning-color);
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

    /* Action buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--secondary-color);
        transform: translateY(-1px);
    }
</style>

<div class="container">
    <?php include 'sidebar_staff.php'; ?>

    <main class="main-content">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Staff Dashboard</h2>
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
                echo "Error fetching statistics: " . $e->getMessage();
            }
            ?>

            <div class="card-box card-success">
                <h3>Available Cars</h3>
                <span><?= $availableCars ?></span>
                <div class="trend">Ready for rental</div>
            </div>

            <div class="card-box card-primary">
                <h3>Rented Cars</h3>
                <span><?= $rentedCars ?></span>
                <div class="trend">Currently in use</div>
            </div>

            <div class="card-box card-warning">
                <h3>Cars in Maintenance</h3>
                <span><?= $maintenanceCars ?></span>
                <div class="trend">Under service</div>
            </div>

            <div class="card-box card-primary">
                <h3>Active Rentals</h3>
                <span><?= $activeRentals ?></span>
                <div class="trend">Ongoing rentals</div>
            </div>

            <div class="card-box card-warning">
                <h3>Pending Returns</h3>
                <span><?= $pendingReturns ?></span>
                <div class="trend">Awaiting return</div>
            </div>
        </div>

        <h3 class="section-title">Car Status Overview</h3>
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
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($car['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($car['status'])) ?>
                                    </span>
                                </td>
                                <td><?= $car['rented_by'] ? htmlspecialchars($car['rented_by']) : '-' ?></td>
                                <td class="amount">₱<?= number_format($car['price_per_day'], 2) ?></td>
                                <td>
                                    <a href="car_details.php?id=<?= $car['id'] ?>" class="btn btn-primary">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php
            } catch (PDOException $e) {
                echo "Error fetching car status: " . $e->getMessage();
            }
            ?>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?> 