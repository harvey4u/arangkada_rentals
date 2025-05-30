<?php
require_once 'session.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Handle rental status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $rental_id = $_POST['rental_id'];
    $status = $_POST['status'];
    $car_id = $_POST['car_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Update rental status
        $stmt = $pdo->prepare("UPDATE rentals SET status = ? WHERE id = ?");
        $stmt->execute([$status, $rental_id]);
        
        // Update car status based on rental status
        $car_status = 'available';
        if ($status === 'active') {
            $car_status = 'rented';
        }
        
        $stmt = $pdo->prepare("UPDATE cars SET status = ? WHERE id = ?");
        $stmt->execute([$car_status, $car_id]);
        
        $pdo->commit();
        $success_message = "Rental status updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Helper function to generate a unique reservation code
function generateReservationCode($pdo) {
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE reservation_code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn() > 0);
    return $code;
}

// Handle rental creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_rental'])) {
    $car_id = $_POST['car_id'];
    $client_id = $_POST['client_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'] ?? '08:00';
    $end_time = $_POST['end_time'] ?? '08:00';
    $total_price = $_POST['total_price'];
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID
    $reservation_code = generateReservationCode($pdo);
    try {
        $pdo->beginTransaction();
        // Create rental record
        $stmt = $pdo->prepare("
            INSERT INTO rentals (car_id, client_id, user_id, start_date, end_date, start_time, end_time, total_price, status, reservation_code) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([$car_id, $client_id, $user_id, $start_date, $end_date, $start_time, $end_time, $total_price, $reservation_code]);
        $pdo->commit();
        $success_message = "Rental created successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error creating rental: " . $e->getMessage();
    }
}

// Fetch all rentals with related information
$stmt = $pdo->query("
    SELECT r.*,
           c.make, c.model, c.year, c.plate_number,
           u.username as client_name,
           DATEDIFF(r.end_date, r.start_date) as duration,
           cc.name as category_name
    FROM rentals r
    JOIN cars c ON r.car_id = c.id
    JOIN users u ON r.client_id = u.id
    LEFT JOIN car_categories cc ON c.category_id = cc.id
    ORDER BY r.created_at DESC
");
$rentals = $stmt->fetchAll();

// Fetch available cars for new rentals
$stmt = $pdo->query("
    SELECT c.*, cc.name as category_name, cc.base_price
    FROM cars c
    LEFT JOIN car_categories cc ON c.category_id = cc.id
    WHERE c.status = 'available'
    ORDER BY c.make, c.model
");
$available_cars = $stmt->fetchAll();

// Fetch clients for new rentals
$stmt = $pdo->query("
    SELECT u.*
    FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    WHERE r.name = 'client'
    ORDER BY u.username
");
$clients = $stmt->fetchAll();

// Reservation code search
$searched_rental = null;
if (isset($_GET['code']) && $_GET['code']) {
    $code = strtoupper(trim($_GET['code']));
    $stmt = $pdo->prepare("SELECT r.*, c.make, c.model, c.year, c.plate_number, u.username as client_name, DATEDIFF(r.end_date, r.start_date) as duration, cc.name as category_name
        FROM rentals r
        JOIN cars c ON r.car_id = c.id
        JOIN users u ON r.client_id = u.id
        LEFT JOIN car_categories cc ON c.category_id = cc.id
        WHERE r.reservation_code = ?");
    $stmt->execute([$code]);
    $searched_rental = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rentals - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Root variables */
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --secondary: #16a34a;
            --secondary-light: #dcfce7;
            --warning: #ca8a04;
            --warning-light: #fef9c3;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --white: #ffffff;
            
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --transition: all 0.2s ease;
            
            /* Add sidebar width variable */
            --sidebar-width: 250px;
        }

        /* Base styles */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--gray-light);
            color: var(--dark);
            line-height: 1.5;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Main content layout */
        .main-content {
            margin-left: var(--sidebar-width); /* Use sidebar width variable */
            padding: var(--spacing-lg);
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width)); /* Ensure content width accounts for sidebar */
            box-sizing: border-box; /* Include padding in width calculation */
        }

        /* Card container */
        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: var(--spacing-lg);
            overflow: hidden; /* Prevent content overflow */
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--gray-light);
            background: var(--white);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: var(--spacing-sm);
            }

            .rental-meta {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Modal improvements */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            padding: var(--spacing-md);
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 5vh;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 600px;
            margin: auto;
            position: relative;
            padding: var(--spacing-lg);
        }

        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-sm);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background: var(--danger-dark);
        }

        /* Alert styles */
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            transition: var(--transition);
        }

        .alert-success {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .alert-danger {
            background: var(--danger-light);
            color: var(--danger);
        }

        /* Form improvements */
        .form-control {
            width: 100%;
            padding: var(--spacing-sm);
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            transition: var(--transition);
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 500;
            color: var(--dark);
        }

        /* Rental specific styles */
        .rental-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow);
        }

        .rental-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--gray-light);
        }

        .rental-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .rental-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: var(--spacing-xs);
        }

        .meta-value {
            font-weight: 500;
        }

        .rental-status {
            display: inline-flex;
            align-items: center;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .status-completed {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .status-cancelled {
            background: var(--danger-light);
            color: var(--danger);
        }

        .rental-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--gray-light);
        }

        .rental-amount {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Form styles */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .form-group {
            margin-bottom: var(--spacing-md);
        }
    </style>
</head>
<body>
    <?php 
    // Include the appropriate sidebar based on user role
    if ($_SESSION['role'] === 'superadmin') {
        include 'sidebar_superadmin.php';
    } else {
        include 'sidebar_admin.php';
    }
    ?>

    <main class="main-content">
        <div style="max-width:400px;margin:0 auto 2rem auto;">
            <form action="manage_rentals.php" method="get" style="display: flex; gap: 0.5rem; align-items: center; background: #f4f6f9; border-radius: 8px; padding: 1rem; box-shadow: 0 1px 4px rgba(52,152,219,0.07);">
                <input type="text" name="code" class="form-control" placeholder="Enter Reservation Code" style="flex:1; border-radius:4px; border:1px solid #ccc; padding:0.5rem 0.8rem; font-size:1rem;" required>
                <button type="submit" class="btn btn-primary" style="padding:0.5rem 1rem; border-radius:4px;"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-calendar-alt"></i>
                    Manage Rentals
                </h2>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    New Rental
                </button>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['code'])): ?>
                <?php if ($searched_rental): ?>
                    <div class="rental-card" style="border:2px solid #3498db;">
                        <div class="rental-header">
                            <div>
                                <h3 class="rental-title">
                                    <?= htmlspecialchars($searched_rental['make'] . ' ' . $searched_rental['model'] . ' ' . $searched_rental['year']) ?>
                                    <small>(<?= htmlspecialchars($searched_rental['category_name']) ?>)</small>
                                </h3>
                                <div class="meta-value"><?= htmlspecialchars($searched_rental['plate_number']) ?></div>
                            </div>
                            <span class="rental-status status-<?= $searched_rental['status'] ?>">
                                <i class="fas fa-circle"></i>
                                <?= ucfirst($searched_rental['status']) ?>
                            </span>
                        </div>
                        <div class="rental-meta">
                            <div class="meta-item">
                                <span class="meta-label">Client</span>
                                <span class="meta-value"><?= htmlspecialchars($searched_rental['client_name']) ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Duration</span>
                                <span class="meta-value"><?= $searched_rental['duration'] ?> days</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Start Date & Time</span>
                                <span class="meta-value">
                                    <?= date('M d, Y', strtotime($searched_rental['start_date'])) ?> <?= htmlspecialchars($searched_rental['start_time'] ?? '') ?>
                                </span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">End Date & Time</span>
                                <span class="meta-value">
                                    <?= date('M d, Y', strtotime($searched_rental['end_date'])) ?> <?= htmlspecialchars($searched_rental['end_time'] ?? '') ?>
                                </span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Total Amount</span>
                                <span class="rental-amount">₱<?= number_format($searched_rental['total_price'], 2) ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Reservation Code</span>
                                <span class="meta-value" style="font-weight:700;letter-spacing:2px;"><?= htmlspecialchars($searched_rental['reservation_code']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        No rental found for code <strong><?= htmlspecialchars($_GET['code']) ?></strong>.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php foreach ($rentals as $rental): ?>
                <div class="rental-card">
                    <div class="rental-header">
                        <div>
                            <h3 class="rental-title">
                                <?= htmlspecialchars($rental['make'] . ' ' . $rental['model'] . ' ' . $rental['year']) ?>
                                <small>(<?= htmlspecialchars($rental['category_name']) ?>)</small>
                            </h3>
                            <div class="meta-value"><?= htmlspecialchars($rental['plate_number']) ?></div>
                        </div>
                        <span class="rental-status status-<?= $rental['status'] ?>">
                            <i class="fas fa-circle"></i>
                            <?= ucfirst($rental['status']) ?>
                        </span>
                    </div>

                    <div class="rental-meta">
                        <div class="meta-item">
                            <span class="meta-label">Client</span>
                            <span class="meta-value"><?= htmlspecialchars($rental['client_name']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Duration</span>
                            <span class="meta-value"><?= $rental['duration'] ?> days</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Start Date & Time</span>
                            <span class="meta-value">
                                <?= date('M d, Y', strtotime($rental['start_date'])) ?> <?= htmlspecialchars($rental['start_time'] ?? '') ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">End Date & Time</span>
                            <span class="meta-value">
                                <?= date('M d, Y', strtotime($rental['end_date'])) ?> <?= htmlspecialchars($rental['end_time'] ?? '') ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Total Amount</span>
                            <span class="rental-amount">₱<?= number_format($rental['total_price'], 2) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Reservation Code</span>
                            <span class="meta-value" style="font-weight:700;letter-spacing:2px;">
                                <?= htmlspecialchars($rental['reservation_code']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="rental-actions">
                        <form method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="rental_id" value="<?= $rental['id'] ?>">
                            <input type="hidden" name="car_id" value="<?= $rental['car_id'] ?>">
                            <select name="status" class="form-control" onchange="this.form.submit()" <?= $rental['status'] === 'completed' || $rental['status'] === 'cancelled' ? 'disabled' : '' ?>>
                                <option value="pending" <?= $rental['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="active" <?= $rental['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="completed" <?= $rental['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $rental['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- New Rental Modal -->
    <div class="modal" id="rentalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">New Rental</h3>
            </div>
            <form method="POST" id="rentalForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="car_id">Car</label>
                        <select id="car_id" name="car_id" class="form-control" required onchange="updateTotalAmount()">
                            <option value="">Select a car</option>
                            <?php foreach ($available_cars as $car): ?>
                                <option value="<?= $car['id'] ?>" data-price="<?= $car['price_per_day'] ?>">
                                    <?= htmlspecialchars($car['make'] . ' ' . $car['model'] . ' ' . $car['year']) ?>
                                    (<?= htmlspecialchars($car['category_name']) ?>) -
                                    ₱<?= number_format($car['price_per_day'], 2) ?>/day
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="client_id">Client</label>
                        <select id="client_id" name="client_id" class="form-control" required>
                            <option value="">Select a client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>">
                                    <?= htmlspecialchars($client['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required 
                               min="<?= date('Y-m-d') ?>" onchange="updateTotalAmount()">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="form-control" value="08:00" required onchange="updateTotalAmount()">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required 
                               min="<?= date('Y-m-d') ?>" onchange="updateTotalAmount()">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="form-control" value="08:00" required onchange="updateTotalAmount()">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="total_price">Total Amount</label>
                    <input type="number" id="total_price" name="total_price" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <button type="submit" name="create_rental" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create Rental
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('rentalModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('rentalModal').classList.remove('active');
            document.getElementById('rentalForm').reset();
        }

        function updateTotalAmount() {
            const carSelect = document.getElementById('car_id');
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            if (carSelect.value && startDate && endDate && startTime && endTime) {
                const pricePerDay = parseFloat(carSelect.options[carSelect.selectedIndex].dataset.price);
                const start = new Date(startDate + 'T' + startTime);
                const end = new Date(endDate + 'T' + endTime);
                let days = (end - start) / (1000 * 60 * 60 * 24);
                if (days > 0) {
                    days = Math.ceil(days); // Partial days count as 1
                    const totalPrice = pricePerDay * days;
                    document.getElementById('total_price').value = totalPrice.toFixed(2);
                } else {
                    document.getElementById('total_price').value = '';
                }
            }
        }

        // Form validation
        document.getElementById('rentalForm').addEventListener('submit', function(e) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            const carSelect = document.getElementById('car_id');
            const start = new Date(startDate + 'T' + startTime);
            const end = new Date(endDate + 'T' + endTime);
            if (end <= start) {
                e.preventDefault();
                alert('End date and time must be after start date and time');
                return;
            }
            let days = (end - start) / (1000 * 60 * 60 * 24);
            if (days < 1) {
                e.preventDefault();
                alert('Rental duration must be at least 1 day.');
                return;
            }
            if (!carSelect.value) {
                e.preventDefault();
                alert('Please select a car.');
                return;
            }
        });

        // After successful rental creation, close modal and refresh
        <?php if (isset($success_message) && $success_message === "Rental created successfully!"): ?>
        document.addEventListener('DOMContentLoaded', function() {
            closeModal();
            setTimeout(function() { window.location.reload(); }, 500);
        });
        <?php endif; ?>

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rentalModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html> 