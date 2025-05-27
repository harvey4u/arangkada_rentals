<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

// Verify if user has client role
if ($_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Fetch user's active rentals
$stmt = $pdo->prepare("
    SELECT r.*, c.make, c.model, c.year, c.plate_number 
    FROM rentals r 
    JOIN cars c ON r.car_id = c.id 
    WHERE r.user_id = ? AND r.status = 'active'
");
$stmt->execute([$_SESSION['user_id']]);
$activeRentals = $stmt->fetchAll();

// Fetch rental history
$stmt = $pdo->prepare("
    SELECT r.*, c.make, c.model, c.year, c.plate_number 
    FROM rentals r 
    JOIN cars c ON r.car_id = c.id 
    WHERE r.user_id = ? AND r.status != 'active'
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$rentalHistory = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2ecc71;
            --error-color: #e74c3c;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --background-light: #f4f6f8;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            background: var(--background-light);
        }

        .header {
            background: var(--primary-color);
            color: var(--white);
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--white);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .card h2 {
            color: var(--text-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .rental-list {
            list-style: none;
        }

        .rental-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }

        .rental-item:last-child {
            border-bottom: none;
        }

        .rental-item h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .rental-details {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .logout-btn {
            background: transparent;
            border: 1px solid var(--white);
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .nav-links {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
            <nav class="nav-links">
                <a href="browse_cars.php"><i class="fas fa-car"></i> Browse Cars</a>
                <a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a>
                <a href="logout.php" class="btn logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-grid">
            <div class="card">
                <h2><i class="fas fa-clock"></i> Active Rentals</h2>
                <?php if (empty($activeRentals)): ?>
                    <p>No active rentals.</p>
                <?php else: ?>
                    <ul class="rental-list">
                        <?php foreach ($activeRentals as $rental): ?>
                            <li class="rental-item">
                                <h3><?= htmlspecialchars($rental['make'] . ' ' . $rental['model'] . ' ' . $rental['year']) ?></h3>
                                <div class="rental-details">
                                    <p><i class="fas fa-calendar"></i> <?= htmlspecialchars($rental['start_date']) ?> to <?= htmlspecialchars($rental['end_date']) ?></p>
                                    <p><i class="fas fa-money-bill"></i> Total: ₱<?= number_format($rental['total_price'], 2) ?></p>
                                    <?php if ($rental['plate_number']): ?>
                                        <p><i class="fas fa-id-card"></i> Plate: <?= htmlspecialchars($rental['plate_number']) ?></p>
                                    <?php endif; ?>
                                    <span class="status-badge status-active">Active</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><i class="fas fa-history"></i> Rental History</h2>
                <?php if (empty($rentalHistory)): ?>
                    <p>No rental history.</p>
                <?php else: ?>
                    <ul class="rental-list">
                        <?php foreach ($rentalHistory as $rental): ?>
                            <li class="rental-item">
                                <h3><?= htmlspecialchars($rental['make'] . ' ' . $rental['model'] . ' ' . $rental['year']) ?></h3>
                                <div class="rental-details">
                                    <p><i class="fas fa-calendar"></i> <?= htmlspecialchars($rental['start_date']) ?> to <?= htmlspecialchars($rental['end_date']) ?></p>
                                    <p><i class="fas fa-money-bill"></i> Total: ₱<?= number_format($rental['total_price'], 2) ?></p>
                                    <span class="status-badge status-<?= $rental['status'] ?>"><?= ucfirst($rental['status']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2><i class="fas fa-star"></i> Quick Actions</h2>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="browse_cars.php" class="btn">
                    <i class="fas fa-search"></i> Find a Car to Rent
                </a>
                <a href="profile.php" class="btn">
                    <i class="fas fa-user-edit"></i> Update Profile
                </a>
            </div>
        </div>
    </div>
</body>
</html> 