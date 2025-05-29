<?php
require_once 'db.php';
session_start();

// Only allow logged-in users with role 'client' to access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all rentals with payment for this client
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.make, c.model, c.year, c.image, c.plate_number
        FROM rentals r
        JOIN cars c ON r.car_id = c.id
        WHERE r.user_id = ? AND r.payment_status IS NOT NULL
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $invoices = [];
    $error_message = 'Error fetching invoices: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
        }
        .invoice-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            overflow: hidden;
            margin-bottom: 30px;
            transition: box-shadow 0.2s;
        }
        .invoice-card:hover {
            box-shadow: 0 4px 16px rgba(52,152,219,0.15);
        }
        .car-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: #eaeaea;
        }
        .invoice-details {
            padding: 18px;
        }
        .car-title {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .invoice-info {
            color: #7f8c8d;
            font-size: 0.93rem;
            margin-bottom: 8px;
        }
        .invoice-amount {
            font-size: 1.05rem;
            font-weight: 600;
            color: #3498db;
        }
        .invoice-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-failed, .status-refunded {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-download {
            margin-top: 10px;
            width: 100%;
        }
    </style>
</head>
<body>
<?php include 'sidebar_client.php'; ?>
<div class="main-content">
    <div class="container py-4">
        <h2 class="mb-4">My Invoices</h2>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <div class="row">
            <?php if (count($invoices) > 0): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="invoice-card">
                            <img src="<?= $invoice['image'] ? 'uploads/cars/' . htmlspecialchars($invoice['image']) : 'assets/images/default-car.jpg' ?>" 
                                 alt="<?= htmlspecialchars($invoice['make'] . ' ' . $invoice['model']) ?>" 
                                 class="car-image" onerror="this.src='assets/images/default-car.jpg'">
                            <div class="invoice-details">
                                <div class="car-title">
                                    <?= htmlspecialchars($invoice['make'] . ' ' . $invoice['model']) ?> (<?= htmlspecialchars($invoice['year']) ?>)
                                </div>
                                <span class="invoice-status status-<?= strtolower($invoice['payment_status']) ?>">
                                    <?= ucfirst(htmlspecialchars($invoice['payment_status'])) ?>
                                </span>
                                <div class="invoice-info">
                                    <i class="fas fa-hashtag"></i> <?= htmlspecialchars($invoice['plate_number']) ?><br>
                                    <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($invoice['start_date']) ?> to <?= htmlspecialchars($invoice['end_date']) ?><br>
                                    <i class="fas fa-clock"></i> <?= htmlspecialchars(date('M d, Y', strtotime($invoice['created_at']))) ?>
                                </div>
                                <div class="invoice-amount">₱<?= number_format($invoice['total_price'], 2) ?></div>
                                <a href="download_invoice.php?rental_id=<?= $invoice['id'] ?>" class="btn btn-outline-primary btn-download" target="_blank">
                                    <i class="fas fa-file-download"></i> Download Invoice
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">You have no invoices yet.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 