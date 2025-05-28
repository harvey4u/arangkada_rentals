<?php
require_once 'session.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin', 'staff'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Handle new maintenance request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $car_id = $_POST['car_id'];
    $description = $_POST['description'];
    
    try {
        $pdo->beginTransaction();
        
        // Create maintenance request
        $stmt = $pdo->prepare("
            INSERT INTO maintenance_requests (car_id, description, requested_by) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$car_id, $description, $_SESSION['user_id']]);
        
        // Update car status
        $stmt = $pdo->prepare("UPDATE cars SET status = 'maintenance' WHERE id = ?");
        $stmt->execute([$car_id]);
        
        $pdo->commit();
        $success_message = "Maintenance request created successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error creating maintenance request: " . $e->getMessage();
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $car_id = $_POST['car_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Update request status
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $request_id]);
        
        // If completed, update car status back to available
        if ($status === 'completed') {
            $stmt = $pdo->prepare("UPDATE cars SET status = 'available' WHERE id = ?");
            $stmt->execute([$car_id]);
        }
        
        $pdo->commit();
        $success_message = "Status updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get car details if car_id is provided
$car = null;
if (isset($_GET['car_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$_GET['car_id']]);
    $car = $stmt->fetch();
}

// Fetch all maintenance requests
$stmt = $pdo->query("
    SELECT mr.*, c.make, c.model, c.year, c.plate_number,
           u.username as requested_by_name
    FROM maintenance_requests mr
    JOIN cars c ON mr.car_id = c.id
    JOIN users u ON mr.requested_by = u.id
    ORDER BY mr.created_at DESC
");
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Maintenance - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse existing styles from manage_cars.php */
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

        /* Inherit common styles */
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

        /* Additional styles for maintenance page */
        .request-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
        }

        .request-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
        }

        .request-meta {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .request-description {
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--gray-light);
            border-radius: var(--radius-sm);
        }

        .request-status {
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

        .status-in_progress {
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

        .request-actions {
            margin-top: var(--spacing-md);
            display: flex;
            gap: var(--spacing-sm);
        }

        .status-select {
            padding: var(--spacing-xs) var(--spacing-sm);
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
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
                    <i class="fas fa-tools"></i>
                    Car Maintenance
                </h2>
                <?php if ($car): ?>
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-plus"></i>
                        New Maintenance Request
                    </button>
                <?php endif; ?>
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

            <?php foreach ($requests as $request): ?>
                <div class="request-card">
                    <div class="request-header">
                        <div>
                            <h3 class="request-title">
                                <?= htmlspecialchars($request['make'] . ' ' . $request['model'] . ' ' . $request['year']) ?>
                                <small>(<?= htmlspecialchars($request['plate_number']) ?>)</small>
                            </h3>
                            <div class="request-meta">
                                <div>
                                    <i class="fas fa-user"></i>
                                    Requested by: <?= htmlspecialchars($request['requested_by_name']) ?>
                                </div>
                                <div>
                                    <i class="fas fa-calendar"></i>
                                    <?= date('M d, Y H:i', strtotime($request['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <span class="request-status status-<?= $request['status'] ?>">
                            <i class="fas fa-circle"></i>
                            <?= ucfirst($request['status']) ?>
                        </span>
                    </div>

                    <div class="request-description">
                        <?= nl2br(htmlspecialchars($request['description'])) ?>
                    </div>

                    <div class="request-actions">
                        <form method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <input type="hidden" name="car_id" value="<?= $request['car_id'] ?>">
                            <select name="status" class="status-select" onchange="this.form.submit()" <?= $request['status'] === 'completed' ? 'disabled' : '' ?>>
                                <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $request['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php if ($car): ?>
    <!-- New Maintenance Request Modal -->
    <div class="modal" id="maintenanceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">New Maintenance Request</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                <div class="form-group">
                    <label class="form-label" for="description">Description of Issues</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" name="create_request" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Submit Request
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openModal() {
            document.getElementById('maintenanceModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('maintenanceModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('maintenanceModal');
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