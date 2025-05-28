<?php
require_once 'session.php';
require_once 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .container {
        display: flex;
        min-height: 100vh;
        background-color: #f5f6fa;
        padding-left: 250px;
        transition: padding-left 0.3s ease;
    }

    .main-content {
        flex: 1;
        padding: 20px;
        background-color: white;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin: 20px;
        border-radius: 5px;
        width: 100%;
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

    @media (max-width: 768px) {
        .container {
            padding-left: 0;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar_' . $_SESSION['role'] . '.php'; ?>

        <main class="main-content">
            <h2 class="mb-4">Dashboard Overview</h2>

            <div class="row">
               <?php
$pdo = new PDO("mysql:host=localhost;port=3308;dbname=car_rental_system", "root", "");

try {
    // Count admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = 2"); // Assuming 3 = employee
    $adminCount = $stmt->fetchColumn();
    // Count staff
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = 3"); // Assuming 3 = employee
    $staffCount = $stmt->fetchColumn();
    // Count clients
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = 4"); // Assuming 3 = employee
    $clientCount = $stmt->fetchColumn();
    // Count drivers
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = 5"); // Assuming 4 = renter/driver
    $driverCount = $stmt->fetchColumn();
    // Count total cars
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars");
    $totalCars = $stmt->fetchColumn();
    // Count available cars
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'available'");
    $availableCars = $stmt->fetchColumn();
    // Count rented cars
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'rented'");
    $rentedCars = $stmt->fetchColumn();

} catch (PDOException $e) {
    echo "Error fetching counts: " . $e->getMessage();
}
?>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Clients</h3>
                        <span><?= $clientCount ?></span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Staff</h3>
                        <span><?= $staffCount ?></span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Drivers</h3>
                        <span><?= $driverCount ?></span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Total Cars</h3>
                        <span><?= $totalCars ?></span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Available Cars</h3>
                        <span><?= $availableCars ?></span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-box">
                        <h3>Rented Cars</h3>
                        <span><?= $rentedCars ?></span>
                    </div>
                </div>
            </div>

            <h2 class="mt-5">Available Cars</h2>

            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM cars WHERE available = 1");
                $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($cars) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Make</th>
                                <th>Model</th>
                                <th>Year</th>
                                <th>Price Per Day</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cars as $car): ?>
                                <tr>
                                    <td><?= htmlspecialchars($car['make']) ?></td>
                                    <td><?= htmlspecialchars($car['model']) ?></td>
                                    <td><?= htmlspecialchars($car['year']) ?></td>
                                    <td>$<?= number_format($car['price_per_day'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No cars are currently available.</p>
                <?php endif;
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Error fetching cars: " . $e->getMessage() . "</p>";
            }
            ?>
        </main>
    </div>
</body>
</html>
