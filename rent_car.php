<?php
require_once 'db.php';
session_start();

// Only allow logged-in users with role 'client' to access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get car ID from query string
$car_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($car_id <= 0) {
    die('<div class="alert alert-danger">Invalid car selected.</div>');
}

// Fetch car details (with category)
try {
    $stmt = $pdo->prepare("SELECT c.*, cc.name as category_name FROM cars c LEFT JOIN car_categories cc ON c.category_id = cc.id WHERE c.id = ? AND c.status = 'available'");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car) {
        die('<div class="alert alert-danger">Car not found or not available for rent.</div>');
    }
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Error fetching car details.</div>');
}

// Fetch available drivers
$drivers = [];
try {
    $stmt = $pdo->query("SELECT u.id, u.username, u.email, dd.license_number, dd.license_expiry, dd.contact_number, dd.address FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN driver_details dd ON u.id = dd.user_id WHERE ur.role_id = 5 AND dd.status = 'active' ORDER BY u.username");
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$success_message = $error_message = '';

// License type mapping
$category = strtolower($car['category_name'] ?? '');
$license_types = ['Non-Professional', 'Professional'];
$required_license = ($category === 'sports') ? 'Professional' : null;

// Reservation fee percent (can be set in config/db)
$reservation_percent = 20;

// Handle rental form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_date'], $_POST['end_date'], $_POST['rental_option'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $rental_option = $_POST['rental_option'];
    $days = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
    if ($days < 1) {
        $error_message = 'End date must be after start date.';
    } else {
        $total_price = $days * $car['price_per_day'];
        $driver_id = null;
        $client_license_number = null;
        $client_license_type = null;
        $client_license_file = null;
        if ($rental_option === 'no_driver') {
            $client_license_number = trim($_POST['client_license_number'] ?? '');
            $client_license_type = $_POST['client_license_type'] ?? '';
            if ($required_license && $client_license_type !== $required_license) {
                $error_message = 'This car requires a Professional license.';
            } elseif (!$client_license_number) {
                $error_message = 'Please provide your license number.';
            } elseif (!isset($_FILES['client_license_file']) || $_FILES['client_license_file']['error'] !== UPLOAD_ERR_OK) {
                $error_message = 'Please upload a photo of your license.';
            } else {
                $uploadDir = 'uploads/licenses/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExtension = strtolower(pathinfo($_FILES['client_license_file']['name'], PATHINFO_EXTENSION));
                $newFileName = uniqid('license_') . '.' . $fileExtension;
                $uploadFile = $uploadDir . $newFileName;
                if (move_uploaded_file($_FILES['client_license_file']['tmp_name'], $uploadFile)) {
                    $client_license_file = $newFileName;
                } else {
                    $error_message = 'Failed to upload license file.';
                }
            }
        } elseif ($rental_option === 'with_driver') {
            $driver_id = intval($_POST['driver_id'] ?? 0);
            if ($driver_id <= 0) {
                $error_message = 'Please select a driver.';
            }
        } else {
            $error_message = 'Invalid rental option.';
        }
        if (!$error_message) {
            // Generate unique code
            $unique_code = strtoupper(bin2hex(random_bytes(4)));
            try {
                $stmt = $pdo->prepare("INSERT INTO rentals (user_id, client_id, car_id, start_date, end_date, total_price, status, created_at, payment_status, driver_id, notes, reservation_code) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), 'pending', ?, ?, ?)");
                $notes = $rental_option === 'no_driver' ? 'License: ' . $client_license_number . ' (' . $client_license_type . ')' : null;
                $stmt->execute([$user_id, $user_id, $car_id, $start_date, $end_date, $total_price, $driver_id, $notes, $unique_code]);
                $rental_id = $pdo->lastInsertId();
                // Set rental as active after generating the receipt
                $pdo->prepare("UPDATE rentals SET status = 'active' WHERE id = ?")->execute([$rental_id]);
                // Mark car as rented (optional: keep as available until payment)
                // $pdo->prepare("UPDATE cars SET status = 'rented' WHERE id = ?")->execute([$car_id]);
                header('Location: receipt.php?rental_id=' . $rental_id . '&code=' . $unique_code);
                exit;
            } catch (PDOException $e) {
                $error_message = 'Error processing your rental.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Car - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f4f6f9; }
        .car-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden; margin-bottom: 30px; }
        .car-image { width: 100%; height: 200px; object-fit: cover; background: #eaeaea; }
        .car-details { padding: 20px; }
        .car-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 8px; }
        .car-info { color: #7f8c8d; font-size: 0.95rem; margin-bottom: 10px; }
        .car-price { font-size: 1.1rem; font-weight: 600; color: #3498db; margin-bottom: 10px; }
        .form-section { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px; }
        .driver-info-box { background: #f8f9fa; border-radius: 8px; padding: 12px; margin-top: 10px; border: 1px solid #e1e1e1; }
    </style>
</head>
<body>
<?php include 'sidebar_client.php'; ?>
<div class="main-content">
    <div class="container py-4">
        <h2 class="mb-4">Rent Car</h2>
        <?php if ($success_message): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success_message) ?> </div>
        <?php elseif ($error_message): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error_message) ?> </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <div class="car-card">
                    <img src="<?= $car['image'] ? 'uploads/cars/' . htmlspecialchars($car['image']) : 'assets/images/default-car.jpg' ?>" 
                         alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>" class="car-image" onerror="this.src='assets/images/default-car.jpg'">
                    <div class="car-details">
                        <div class="car-title"><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?> (<?= htmlspecialchars($car['year']) ?>)</div>
                        <div class="car-info">
                            <i class="fas fa-hashtag"></i> <?= htmlspecialchars($car['plate_number']) ?><br>
                            <i class="fas fa-cogs"></i> <?= htmlspecialchars(ucfirst($car['transmission'])) ?> | 
                            <i class="fas fa-gas-pump"></i> <?= htmlspecialchars(ucfirst($car['fuel_type'])) ?> | 
                            <i class="fas fa-users"></i> <?= htmlspecialchars($car['seats']) ?> seats<br>
                            <i class="fas fa-tags"></i> <?= htmlspecialchars($car['category_name']) ?> Category
                        </div>
                        <div class="car-price">₱<?= number_format($car['price_per_day'], 2) ?> per day</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-section">
                    <form method="POST" enctype="multipart/form-data" id="rentalForm">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rental Option</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rental_option" id="no_driver" value="no_driver" checked>
                                <label class="form-check-label" for="no_driver">No Driver (Self-Drive)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rental_option" id="with_driver" value="with_driver">
                                <label class="form-check-label" for="with_driver">With Driver</label>
                            </div>
                        </div>
                        <div id="noDriverFields">
                            <div class="mb-3">
                                <label for="client_license_number" class="form-label">Your License Number</label>
                                <input type="text" class="form-control" id="client_license_number" name="client_license_number">
                            </div>
                            <div class="mb-3">
                                <label for="client_license_type" class="form-label">License Type</label>
                                <select class="form-control" id="client_license_type" name="client_license_type">
                                    <?php foreach ($license_types as $type): ?>
                                        <option value="<?= $type ?>" <?= ($required_license === $type) ? 'selected' : '' ?>><?= $type ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($required_license): ?>
                                    <small class="text-danger">This car requires a Professional license.</small>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="client_license_file" class="form-label">Upload License Photo</label>
                                <input type="file" class="form-control" id="client_license_file" name="client_license_file" accept="image/*">
                            </div>
                        </div>
                        <div id="withDriverFields" style="display:none;">
                            <div class="mb-3">
                                <label for="driver_id" class="form-label">Select Driver</label>
                                <select class="form-control" id="driver_id" name="driver_id">
                                    <option value="">-- Select Driver --</option>
                                    <?php foreach ($drivers as $driver): ?>
                                        <option value="<?= $driver['id'] ?>" data-info='<?= htmlspecialchars(json_encode($driver)) ?>'><?= htmlspecialchars($driver['username']) ?> (<?= htmlspecialchars($driver['license_number']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="driverInfoBox" class="driver-info-box" style="display:none;"></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Confirm Rental</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show/hide fields based on rental option
const noDriverFields = document.getElementById('noDriverFields');
const withDriverFields = document.getElementById('withDriverFields');
document.querySelectorAll('input[name="rental_option"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'no_driver') {
            noDriverFields.style.display = '';
            withDriverFields.style.display = 'none';
        } else {
            noDriverFields.style.display = 'none';
            withDriverFields.style.display = '';
        }
    });
});
// Driver info display
const driverSelect = document.getElementById('driver_id');
const driverInfoBox = document.getElementById('driverInfoBox');
driverSelect && driverSelect.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (selected && selected.value) {
        const info = JSON.parse(selected.getAttribute('data-info'));
        driverInfoBox.innerHTML = `<strong>Name:</strong> ${info.username}<br>
            <strong>Email:</strong> ${info.email}<br>
            <strong>License #:</strong> ${info.license_number}<br>
            <strong>License Expiry:</strong> ${info.license_expiry}<br>
            <strong>Contact:</strong> ${info.contact_number}<br>
            <strong>Address:</strong> ${info.address}`;
        driverInfoBox.style.display = '';
    } else {
        driverInfoBox.innerHTML = '';
        driverInfoBox.style.display = 'none';
    }
});
</script>
</body>
</html> 