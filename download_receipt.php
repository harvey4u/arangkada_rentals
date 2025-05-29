<?php
ob_start();
error_reporting(0);

require_once 'db.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;
$code = isset($_GET['code']) ? strtoupper($_GET['code']) : '';
if ($rental_id <= 0) {
    ob_end_clean();
    die('Invalid receipt request.');
}

// Fetch rental, car, and client info
$stmt = $pdo->prepare("SELECT r.*, c.make, c.model, c.year, c.plate_number, c.category_id, cc.name as category_name, u.username, u.email FROM rentals r JOIN cars c ON r.car_id = c.id LEFT JOIN car_categories cc ON c.category_id = cc.id JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$rental_id]);
$rental = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rental) {
    ob_end_clean();
    die('Receipt not found.');
}
if (!$code && !empty($rental['reservation_code'])) {
    $code = strtoupper($rental['reservation_code']);
}

// Prepare HTML for PDF with enhanced design and single-page fit
$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Receipt - Arangkada</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .receipt-container { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(52,152,219,0.10); border: 2px solid #3498db; padding: 0; margin: 24px auto; max-width: 650px; page-break-inside: avoid; }
        .receipt-header { background: #3498db; color: #fff; border-radius: 12px 12px 0 0; padding: 18px 20px 12px 20px; text-align: center; }
        .receipt-header .logo { font-size: 1.6rem; font-weight: 700; letter-spacing: 2px; margin-bottom: 4px; }
        .receipt-header .subtitle { font-size: 1rem; font-weight: 400; letter-spacing: 1px; }
        .receipt-title { font-size: 1.1rem; font-weight: 600; color: #222; margin: 18px 0 12px 0; text-align: center; }
        .receipt-list { list-style: none; padding: 0 20px; margin: 0 0 12px 0; }
        .receipt-list li { margin-bottom: 8px; font-size: 0.98rem; }
        .section-label { color: #3498db; font-weight: 600; font-size: 1.01rem; margin-top: 12px; margin-bottom: 3px; display: block; }
        .receipt-amount { font-size: 1.05rem; font-weight: 700; color: #e67e22; margin-top: 6px; }
        .receipt-note { margin: 16px 20px 0 20px; font-size: 0.97rem; color: #155724; background: #d4edda; border-radius: 6px; padding: 8px 12px; border-left: 4px solid #3498db; }
        .receipt-footer { text-align: center; color: #888; font-size: 0.93rem; margin: 18px 0 10px 0; }
        .divider { border-top: 1.2px dashed #3498db; margin: 14px 20px; }
        @page { margin: 18mm 10mm 18mm 10mm; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="logo">Arangkada Rentals</div>
            <div class="subtitle">Rental Reservation Receipt</div>
        </div>
        <div class="receipt-title">Reservation Details</div>
        <ul class="receipt-list">
            <li><span class="section-label">Client</span> ' . htmlspecialchars($rental['username']) . ' (' . htmlspecialchars($rental['email']) . ')</li>
            <li><span class="section-label">Car</span> ' . htmlspecialchars($rental['make'] . ' ' . $rental['model']) . ' (' . htmlspecialchars($rental['year']) . ', ' . htmlspecialchars($rental['category_name']) . ')</li>
            <li><span class="section-label">Plate Number</span> ' . htmlspecialchars($rental['plate_number']) . '</li>
            <li><span class="section-label">Rental Dates</span> ' . htmlspecialchars($rental['start_date']) . ' to ' . htmlspecialchars($rental['end_date']) . '</li>
            <li><span class="section-label">Price per Day</span> ₱' . number_format($rental['price_per_day'], 2) . '</li>
            <li><span class="section-label">Total Price</span> <span class="receipt-amount">₱' . number_format($rental['total_price'], 2) . '</span></li>
            <li><span class="section-label">Reservation Code</span> <span style="font-size:1.1em;letter-spacing:2px;color:#3498db;font-weight:700;">' . htmlspecialchars($code) . '</span></li>
            <li><span class="section-label">Payment Status</span> ' . ucfirst($rental['payment_status']) . '</li>
        </ul>
        <div class="divider"></div>
        <div class="receipt-note">
            Please present this receipt and your reservation code (<strong>' . htmlspecialchars($code) . '</strong>) at the shop to pay your reservation fee and confirm your booking.<br>
            This code is required for staff verification.
        </div>
        <div class="receipt-footer">
            Thank you for choosing Arangkada Rentals!<br>
            For inquiries, contact us at <a href="mailto:support@arangkada.com" style="color:#3498db;text-decoration:none;">support@arangkada.com</a>
        </div>
    </div>
</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = 'Rental_Receipt_' . $code . '.pdf';
ob_end_clean();
$dompdf->stream($filename, ['Attachment' => 1]);
exit; 