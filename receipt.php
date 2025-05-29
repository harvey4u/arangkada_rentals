<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;
$code = isset($_GET['code']) ? strtoupper($_GET['code']) : '';
if ($rental_id <= 0) {
    die('<div class="alert alert-danger">Invalid receipt request.</div>');
}

// Fetch rental, car, and client info
$stmt = $pdo->prepare("SELECT r.*, c.make, c.model, c.year, c.plate_number, c.category_id, cc.name as category_name, u.username, u.email FROM rentals r JOIN cars c ON r.car_id = c.id LEFT JOIN car_categories cc ON c.category_id = cc.id JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$rental_id]);
$rental = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rental) {
    die('<div class="alert alert-danger">Receipt not found.</div>');
}
if (!$code && !empty($rental['reservation_code'])) {
    $code = strtoupper($rental['reservation_code']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Receipt - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f4f6f9; }
        .receipt-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 30px; margin: 40px auto; max-width: 600px; }
        .receipt-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 18px; }
        .receipt-list { list-style: none; padding: 0; margin: 0 0 18px 0; }
        .receipt-list li { margin-bottom: 10px; }
        .receipt-amount { font-size: 1.1rem; font-weight: 600; color: #3498db; }
        .receipt-note { margin-top: 18px; font-size: 1rem; color: #155724; background: #d4edda; border-radius: 6px; padding: 10px; }
    </style>
</head>
<body>
<?php include 'sidebar_client.php'; ?>
<div class="main-content">
    <div class="container">
        <div class="receipt-card">
            <div class="receipt-title">Rental Reservation Receipt</div>
            <ul class="receipt-list">
                <li><strong>Client:</strong> <?= htmlspecialchars($rental['username']) ?> (<?= htmlspecialchars($rental['email']) ?>)</li>
                <li><strong>Car:</strong> <?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?> (<?= htmlspecialchars($rental['year']) ?>, <?= htmlspecialchars($rental['category_name']) ?>)</li>
                <li><strong>Plate Number:</strong> <?= htmlspecialchars($rental['plate_number']) ?></li>
                <li><strong>Rental Dates:</strong> <?= htmlspecialchars($rental['start_date']) ?> to <?= htmlspecialchars($rental['end_date']) ?></li>
                <li><strong>Price per Day:</strong> ₱<?= number_format($rental['price_per_day'], 2) ?></li>
                <li><strong>Total Price:</strong> ₱<?= number_format($rental['total_price'], 2) ?></li>
                <li class="receipt-amount"><strong>Reservation Code:</strong> <span style="font-size:1.2em;letter-spacing:2px;"><?= htmlspecialchars($code) ?></span></li>
                <li><strong>Payment Status:</strong> <?= ucfirst($rental['payment_status']) ?></li>
            </ul>
            <div class="receipt-note">
                Please present this receipt and your reservation code (<strong><?= htmlspecialchars($code) ?></strong>) at the shop to pay your reservation fee and confirm your booking.<br>
                This code is required for staff verification.
            </div>
            <a href="download_receipt.php?rental_id=<?= $rental_id ?>" class="btn btn-outline-primary mt-3"><i class="fas fa-download"></i> Download as PDF</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 