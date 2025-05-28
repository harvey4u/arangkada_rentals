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

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<!-- Main Layout -->
<style>
    .container {
        display: flex;
    }

    .main-content {
        flex: 1;
        padding: 20px;
        background-color: white;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin: 20px;
        border-radius: 5px;
    }

    .card-box {
        padding: 20px;
        border-radius: 8px;
        background-color: #f8f9fa;
        border-left: 5px solid #3498db;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        text-align: center;
    }

    .card-box h3 {
        margin-bottom: 5px;
        color: #2c3e50;
    }

    .card-box span {
        font-size: 24px;
        font-weight: bold;
        color: #3498db;
    }

    /* Table styling */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    table, th, td {
        border: 1px solid #ddd;
    }

    th, td {
        padding: 12px;
        text-align: center;
    }

    th {
        background-color: #3498db;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #e3f2fd;
    }

    .car-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .car-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.2s;
    }

    .car-card:hover {
        transform: translateY(-5px);
    }

    .car-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .car-details {
        padding: 15px;
    }

    .car-title {
        font-size: 1.2em;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .car-price {
        color: #3498db;
        font-size: 1.1em;
        font-weight: bold;
    }

    .rent-btn {
        display: inline-block;
        background: #3498db;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        margin-top: 10px;
        transition: background 0.2s;
    }

    .rent-btn:hover {
        background: #2980b9;
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
</style>

<div class="container">
    <?php include 'sidebar_client.php'; ?>

    <main class="main-content">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>

        <div class="stats-section">
            <div class="row">
                <?php
                try {
                    // Get client's rental statistics with corrected column names
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
                    echo "Error fetching statistics: " . $e->getMessage();
                    $stats = [
                        'total_rentals' => 0,
                        'active_rentals' => 0,
                        'total_spent' => 0
                    ];
                }
                ?>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Total Rentals</h3>
                        <span><?= $stats['total_rentals'] ?></span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Active Rentals</h3>
                        <span><?= $stats['active_rentals'] ?></span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Total Spent</h3>
                        <span>₱<?= number_format($stats['total_spent'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rental-history">
            <h3>Your Rental History</h3>
            <?php
            try {
                // Updated query to use correct column names and dates
                $stmt = $pdo->prepare("
                    SELECT r.*, c.make, c.model
                    FROM rentals r
                    JOIN cars c ON r.car_id = c.id
                    WHERE r.user_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$user_id]);
                $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <table>
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
            <?php
            } catch (PDOException $e) {
                echo "Error fetching rental history: " . $e->getMessage();
            }
            ?>
        </div>

        <div class="available-cars">
            <h3>Available Cars</h3>
            <div class="car-grid">
                <?php
                try {
                    $stmt = $pdo->query("
                        SELECT * FROM cars 
                        WHERE status = 'available'
                        ORDER BY price_per_day ASC
                    ");
                    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($cars as $car):
                        // Set a default image if none is provided
                        $imageUrl = $car['image'] ? htmlspecialchars($car['image']) : 'assets/images/default-car.jpg';
                ?>
                    <div class="car-card">
                        <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>" class="car-image" onerror="this.src='assets/images/default-car.jpg'">
                        <div class="car-details">
                            <div class="car-title"><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?></div>
                            <div class="car-price">₱<?= number_format($car['price_per_day'], 2) ?> per day</div>
                            <a href="rent_car.php?id=<?= $car['id'] ?>" class="rent-btn">Rent Now</a>
                        </div>
                    </div>
                <?php
                    endforeach;
                } catch (PDOException $e) {
                    echo "Error fetching available cars: " . $e->getMessage();
                }
                ?>
            </div>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?> 