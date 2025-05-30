<?php
require_once 'session.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin', 'staff'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Handle car creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_car'])) {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $price_per_day = $_POST['price_per_day'];
    $plate_number = $_POST['plate_number'];
    $seats = $_POST['seats'];
    $transmission = $_POST['transmission'];
    $fuel_type = $_POST['fuel_type'];
    
    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/cars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $fileExtension;
        $uploadFile = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = $newFileName;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cars (make, model, year, price_per_day, plate_number, image, status, seats, transmission, fuel_type) 
            VALUES (?, ?, ?, ?, ?, ?, 'available', ?, ?, ?)
        ");
        $stmt->execute([$make, $model, $year, $price_per_day, $plate_number, $image, $seats, $transmission, $fuel_type]);
        $success_message = "Car added successfully!";
    } catch (PDOException $e) {
        $error_message = "Error adding car: " . $e->getMessage();
    }
}

// Handle car deletion
if (isset($_POST['delete_car'])) {
    $car_id = $_POST['car_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $success_message = "Car deleted successfully!";
    } catch (PDOException $e) {
        $error_message = "Error deleting car: " . $e->getMessage();
    }
}

// Handle car update
if (isset($_POST['edit_car'])) {
    $car_id = $_POST['car_id'];
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $price_per_day = $_POST['price_per_day'];
    $plate_number = $_POST['plate_number'];
    $seats = $_POST['seats'];
    $transmission = $_POST['transmission'];
    $fuel_type = $_POST['fuel_type'];
    $image = $_POST['existing_image'] ?? null;
    // Handle image upload if new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/cars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $fileExtension;
        $uploadFile = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = $newFileName;
        }
    }
    try {
        $stmt = $pdo->prepare("UPDATE cars SET make=?, model=?, year=?, price_per_day=?, plate_number=?, image=?, seats=?, transmission=?, fuel_type=? WHERE id=?");
        $stmt->execute([$make, $model, $year, $price_per_day, $plate_number, $image, $seats, $transmission, $fuel_type, $car_id]);
        $success_message = "Car updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating car: " . $e->getMessage();
    }
}

// Fetch all cars
$stmt = $pdo->query("
    SELECT c.*, 
           COUNT(DISTINCT r.id) as total_rentals,
           SUM(CASE WHEN r.status = 'active' THEN 1 ELSE 0 END) as active_rentals
    FROM cars c
    LEFT JOIN rentals r ON c.id = r.car_id
    GROUP BY c.id
    ORDER BY c.make, c.model
");
$cars = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--gray-light);
            color: var(--dark);
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: var(--spacing-lg);
            min-height: 100vh;
        }

        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--gray-light);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .card-title i {
            color: var(--primary);
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-warning {
            background: var(--warning);
            color: var(--white);
        }

        .btn:hover {
            opacity: 0.9;
        }

        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--spacing-lg);
        }

        .car-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            transition: var(--transition);
        }

        .car-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .car-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .car-details {
            padding: var(--spacing-md);
        }

        .car-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0 0 var(--spacing-xs);
        }

        .car-info {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: var(--spacing-sm);
        }

        .car-status {
            display: inline-flex;
            align-items: center;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-available {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .status-rented {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-maintenance {
            background: var(--danger-light);
            color: var(--danger);
        }

        .car-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
            flex-wrap: wrap;
            align-items: stretch;
        }

        .car-actions .btn {
            min-width: 0;
            flex: 1 1 0;
            box-sizing: border-box;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            padding: var(--spacing-lg);
            width: 100%;
            max-width: 500px;
        }

        .modal-header {
            margin-bottom: var(--spacing-lg);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .form-group {
            margin-bottom: var(--spacing-md);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: var(--spacing-sm);
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
        }

        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-md);
        }

        .alert-success {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .alert-danger {
            background: var(--danger-light);
            color: var(--danger);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: var(--spacing-md);
            }

            .car-grid {
                grid-template-columns: 1fr;
            }
        }

        .edit-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 700px) {
            .edit-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Include the appropriate sidebar based on user role
    if ($_SESSION['role'] === 'superadmin') {
        include 'sidebar_superadmin.php';
    } elseif ($_SESSION['role'] === 'admin') {
        include 'sidebar_admin.php';
    } else {
        include 'sidebar_staff.php';
    }
    ?>

    <main class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-car"></i>
                    Manage Cars
                </h2>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Add New Car
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

            <div class="car-grid">
                <?php foreach ($cars as $car): ?>
                    <div class="car-card">
                        <img src="<?= $car['image'] ? 'uploads/cars/' . htmlspecialchars($car['image']) : 'assets/images/car-placeholder.jpg' ?>" 
                             alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>" 
                             class="car-image">
                        <div class="car-details">
                            <h3 class="car-title">
                                <?= htmlspecialchars($car['make'] . ' ' . $car['model'] . ' ' . $car['year']) ?>
                            </h3>
                            <div class="car-info">
                                <div><i class="fas fa-hashtag"></i> <?= htmlspecialchars($car['plate_number']) ?></div>
                                <div><i class="fas fa-tag"></i> ₱<?= number_format($car['price_per_day'], 2) ?> per day</div>
                                <div><i class="fas fa-users"></i> <?= htmlspecialchars($car['seats']) ?> seats</div>
                                <div><i class="fas fa-cogs"></i> <?= htmlspecialchars(ucfirst($car['transmission'])) ?> | <i class="fas fa-gas-pump"></i> <?= htmlspecialchars(ucfirst($car['fuel_type'])) ?></div>
                                <div>
                                    <i class="fas fa-chart-bar"></i> 
                                    <?= $car['total_rentals'] ?> total rentals (<?= $car['active_rentals'] ?> active)
                                </div>
                            </div>
                            <span class="car-status status-<?= strtolower($car['status']) ?>">
                                <i class="fas fa-circle"></i>
                                <?= ucfirst($car['status']) ?>
                            </span>
                            <div class="car-actions">
                                <button class="btn btn-warning" onclick="requestMaintenance(<?= $car['id'] ?>)">
                                    <i class="fas fa-tools"></i>
                                    Maintenance
                                </button>
                                <button class="btn btn-primary" onclick='openEditModal(<?= json_encode($car) ?>)'>
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                <button class="btn btn-danger" onclick="deleteCar(<?= $car['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Add Car Modal -->
    <div class="modal" id="addCarModal">
        <div class="modal-content" style="max-width:600px;overflow-y:auto;">
            <div class="modal-header">
                <h3 class="modal-title">Add New Car</h3>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="edit-form-grid">
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="make">Make</label>
                            <input type="text" id="make" name="make" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="model">Model</label>
                            <input type="text" id="model" name="model" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="year">Year</label>
                            <input type="number" id="year" name="year" class="form-control" min="1900" max="<?= date('Y') + 1 ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="seats">Seats</label>
                            <input type="number" id="seats" name="seats" class="form-control" min="1" max="20" required>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="transmission">Transmission</label>
                            <select id="transmission" name="transmission" class="form-control" required>
                                <option value="automatic">Automatic</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="fuel_type">Fuel Type</label>
                            <select id="fuel_type" name="fuel_type" class="form-control" required>
                                <option value="gasoline">Gasoline</option>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="price_per_day">Price Per Day (₱)</label>
                            <input type="number" id="price_per_day" name="price_per_day" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="plate_number">Plate Number</label>
                            <input type="text" id="plate_number" name="plate_number" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="image">Car Image</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-top:1rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="submit" name="create_car" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Car
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Car Modal -->
    <div class="modal" id="editCarModal">
        <div class="modal-content" style="max-width:600px;overflow-y:auto;">
            <div class="modal-header">
                <h3 class="modal-title">Edit Car</h3>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="edit_car_id" name="car_id">
                <input type="hidden" id="edit_existing_image" name="existing_image">
                <div class="edit-form-grid">
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="edit_make">Make</label>
                            <input type="text" id="edit_make" name="make" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit_model">Model</label>
                            <input type="text" id="edit_model" name="model" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit_year">Year</label>
                            <input type="number" id="edit_year" name="year" class="form-control" min="1900" max="<?= date('Y') + 1 ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit_seats">Seats</label>
                            <input type="number" id="edit_seats" name="seats" class="form-control" min="1" max="20" required>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="edit_transmission">Transmission</label>
                            <select id="edit_transmission" name="transmission" class="form-control" required>
                                <option value="automatic">Automatic</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit_fuel_type">Fuel Type</label>
                            <select id="edit_fuel_type" name="fuel_type" class="form-control" required>
                                <option value="gasoline">Gasoline</option>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit_price_per_day">Price Per Day (₱)</label>
                            <input type="number" id="edit_price_per_day" name="price_per_day" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit_plate_number">Plate Number</label>
                            <input type="text" id="edit_plate_number" name="plate_number" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit_image">Car Image (leave blank to keep current)</label>
                            <input type="file" id="edit_image" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-top:1rem;display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="submit" name="edit_car" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeEditModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addCarModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('addCarModal').classList.remove('active');
        }

        function deleteCar(carId) {
            if (confirm('Are you sure you want to delete this car? This will also delete all associated rental records.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_car" value="1">
                                <input type="hidden" name="car_id" value="${carId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function requestMaintenance(carId) {
            // This will be implemented in car_maintenance.php
            window.location.href = `car_maintenance.php?car_id=${carId}`;
        }

        function openEditModal(car) {
            document.getElementById('editCarModal').classList.add('active');
            document.getElementById('edit_car_id').value = car.id;
            document.getElementById('edit_make').value = car.make;
            document.getElementById('edit_model').value = car.model;
            document.getElementById('edit_year').value = car.year;
            document.getElementById('edit_seats').value = car.seats;
            document.getElementById('edit_transmission').value = car.transmission;
            document.getElementById('edit_fuel_type').value = car.fuel_type;
            document.getElementById('edit_price_per_day').value = car.price_per_day;
            document.getElementById('edit_plate_number').value = car.plate_number;
            document.getElementById('edit_existing_image').value = car.image;
        }

        function closeEditModal() {
            document.getElementById('editCarModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addCarModal');
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