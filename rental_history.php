<?php
require_once 'db.php';
session_start();

// Only allow logged-in users with role 'client' to access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch rental history for this client (completed and cancelled)
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.make, c.model, c.year, c.image, c.plate_number
        FROM rentals r
        JOIN cars c ON r.car_id = c.id
        WHERE r.user_id = ? AND r.status IN ('completed', 'cancelled')
        ORDER BY r.start_date DESC
    ");
    $stmt->execute([$user_id]);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rentals = [];
    $error_message = 'Error fetching rental history: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental History - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
        }
        .rental-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            overflow: hidden;
            margin-bottom: 30px;
            transition: box-shadow 0.2s;
        }
        .rental-card:hover {
            box-shadow: 0 4px 16px rgba(52,152,219,0.15);
        }
        .car-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: #eaeaea;
        }
        .rental-details {
            padding: 20px;
        }
        .car-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .rental-info {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }
        .rental-period {
            font-size: 0.98rem;
            margin-bottom: 8px;
        }
        .rental-amount {
            font-size: 1.05rem;
            font-weight: 600;
            color: #3498db;
        }
        .rental-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .status-completed {
            background: #cce5ff;
            color: #004085;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<?php include 'sidebar_client.php'; ?>
<div class="main-content">
    <div class="container py-4">
        <h2 class="mb-4">My Rental History</h2>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <div class="row">
            <?php if (count($rentals) > 0): ?>
                <?php foreach ($rentals as $rental): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="rental-card">
                            <img src="<?= $rental['image'] ? 'uploads/cars/' . htmlspecialchars($rental['image']) : 'assets/images/default-car.jpg' ?>" 
                                 alt="<?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?>" 
                                 class="car-image" onerror="this.src='assets/images/default-car.jpg'">
                            <div class="rental-details">
                                <div class="car-title">
                                    <?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?> (<?= htmlspecialchars($rental['year']) ?>)
                                </div>
                                <span class="rental-status status-<?= strtolower($rental['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($rental['status'])) ?>
                                </span>
                                <div class="rental-info">
                                    <i class="fas fa-hashtag"></i> <?= htmlspecialchars($rental['plate_number']) ?><br>
                                    <i class="fas fa-calendar-alt"></i> <span class="rental-period">From <?= htmlspecialchars($rental['start_date']) ?> to <?= htmlspecialchars($rental['end_date']) ?></span>
                                </div>
                                <div class="rental-amount">₱<?= number_format($rental['total_price'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">You have no rental history yet.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 