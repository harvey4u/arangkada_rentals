<?php
session_start();
require_once 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Arangkada Car Rentals</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: #f4f6f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
        }
        .main-content {
            flex: 1;
            padding: 30px 40px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            margin: 30px;
            border-radius: 8px;
        }
        .header {
            background: #3498db;
            color: #fff;
            padding: 24px 0;
            text-align: center;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .header h1 {
            margin: 0;
            font-size: 2.2em;
            font-weight: 700;
            letter-spacing: 2px;
        }
        .nav {
            margin-top: 12px;
        }
        .nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 18px;
            font-size: 1.1em;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav a:hover {
            color: #e3f2fd;
        }
        .hero {
            background: linear-gradient(90deg, #3498db 60%, #2980b9 100%);
            color: #fff;
            padding: 40px 30px 30px 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(52,152,219,0.08);
        }
        .hero h2 {
            font-size: 2em;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .hero p {
            font-size: 1.2em;
            margin-bottom: 18px;
        }
        .btn {
            background: #fff;
            color: #3498db;
            padding: 12px 32px;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s, color 0.2s;
        }
        .btn:hover {
            background: #2980b9;
            color: #fff;
        }
        .info-section {
            margin: 32px 0;
            padding: 24px 28px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .info-section h2 {
            color: #3498db;
            margin-top: 0;
            font-size: 1.4em;
            font-weight: 600;
        }
        .info-section p {
            color: #34495e;
            font-size: 1.08em;
        }
        .card-box {
            padding: 20px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border-left: 5px solid #3498db;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            margin-bottom: 20px;
            text-align: center;
        }
        .card-box h3 {
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .card-box span {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e3f2fd;
        }
        .footer {
            background: #3498db;
            color: #fff;
            text-align: center;
            padding: 18px 0;
            margin-top: 40px;
            font-size: 1em;
            letter-spacing: 1px;
        }
        @media (max-width: 900px) {
            .container {
                flex-direction: column;
            }
            .main-content {
                margin: 20px 5px;
                padding: 18px 5px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>🚗 Arangkada Car Rentals</h1>
        <nav class="nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
                <a href="recover.php">Forgot Password?</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <main class="main-content">
            <section class="hero">
                <h2>Your Journey Begins Here</h2>
                <p>Reliable, Affordable, and Convenient Car Rentals</p>
                <a href="fleet.php" class="btn">Browse Fleet</a>
            </section>

            <div class="row" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between;">
                <?php
                // Show some quick stats like dashboard
                try {
                    $pdo = new PDO("mysql:host=localhost;port=3308;dbname=car_rental_system", "root", "");
                } catch (PDOException $e) {
                    // Handle connection error
                }
                ?>
            </div>

            <h2 style="margin-top:40px;">Car List</h2>
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM cars");
                $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($cars) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Make</th>
                                <th>Model</th>
                                <th>Year</th>
                                <th>Price Per Day</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cars as $car): ?>
                                <tr>
                                    <td><?= htmlspecialchars($car['make']) ?></td>
                                    <td><?= htmlspecialchars($car['model']) ?></td>
                                    <td><?= htmlspecialchars($car['year']) ?></td>
                                    <td>$<?= number_format($car['price_per_day'], 2) ?></td>
                                    <td><?= htmlspecialchars($car['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No cars are currently available.</p>
                <?php endif;
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Error fetching cars: " . $e->getMessage() . "</p>";
            }
            ?>

            <section class="info-section">
                <h2>About Us</h2>
                <p>We provide a wide range of cars for rent at competitive prices. Whether you need a car for a day, a week, or longer, we have you covered.</p>
            </section>

            <section class="info-section">
                <h2>Our Fleet</h2>
                <p>Explore our diverse fleet of vehicles, ranging from economy to luxury. We ensure that all our cars are well-maintained and ready for your next adventure.</p>
            </section>

            <section class="info-section">
                <h2>Contact Us</h2>
                <p>Have questions or need assistance? Contact our customer service team for help with your rental needs.</p>
            </section>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Arangkada Car Rentals. All rights reserved.</p>
    </footer>
</body>
</html>
