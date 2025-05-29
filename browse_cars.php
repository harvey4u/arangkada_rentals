<?php
require_once 'db.php';
session_start();

// Only allow logged-in users with role 'client' to access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Fetch available cars
try {
    $stmt = $pdo->query("SELECT * FROM cars WHERE status = 'available'");
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cars = [];
    $error_message = 'Error fetching cars: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Cars - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
        }
        .car-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            overflow: hidden;
            margin-bottom: 30px;
            transition: box-shadow 0.2s;
        }
        .car-card:hover {
            box-shadow: 0 4px 16px rgba(52,152,219,0.15);
        }
        .car-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #eaeaea;
        }
        .car-details {
            padding: 20px;
        }
        .car-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .car-info {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }
        .car-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: #3498db;
            margin-bottom: 10px;
        }
        .btn-rent {
            width: 100%;
            font-weight: 500;
        }
    </style>
</head>
<body>
<?php include 'sidebar_client.php'; ?>
<div class="main-content">
    <div class="container py-4">
        <h2 class="mb-4">Browse Available Cars</h2>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <div class="row">
            <?php if (count($cars) > 0): ?>
                <?php foreach ($cars as $car): ?>
                    <div class="col-md-4">
                        <div class="car-card">
                            <img src="<?= $car['image'] ? 'uploads/cars/' . htmlspecialchars($car['image']) : 'assets/images/default-car.jpg' ?>" 
                                 alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>" 
                                 class="car-image" onerror="this.src='assets/images/default-car.jpg'">
                            <div class="car-details">
                                <div class="car-title"><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?> (<?= htmlspecialchars($car['year']) ?>)</div>
                                <div class="car-info">
                                    <i class="fas fa-hashtag"></i> <?= htmlspecialchars($car['plate_number']) ?><br>
                                    <i class="fas fa-cogs"></i> <?= htmlspecialchars(ucfirst($car['transmission'])) ?> | 
                                    <i class="fas fa-gas-pump"></i> <?= htmlspecialchars(ucfirst($car['fuel_type'])) ?> | 
                                    <i class="fas fa-users"></i> <?= htmlspecialchars($car['seats']) ?> seats
                                    <br><span class="badge bg-secondary">Seater: <?= htmlspecialchars($car['seats']) ?></span>
                                </div>
                                <div class="car-price">₱<?= number_format($car['price_per_day'], 2) ?> per day</div>
                                <a href="rent_car.php?id=<?= $car['id'] ?>" class="btn btn-primary btn-rent">Rent Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No cars are currently available for rent.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 