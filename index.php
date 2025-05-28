<?php
session_start();
require_once 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --gray: #95a5a6;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        /* Navbar Styles */
        .navbar {
            background: var(--primary);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--light) !important;
        }

        .nav-link {
            color: var(--light) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 5rem 0;
            margin-bottom: 4rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: rotate(-15deg);
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        /* Car Cards */
        .car-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .car-card:hover {
            transform: translateY(-5px);
        }

        .car-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .car-details {
            padding: 1.5rem;
        }

        .car-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .car-price {
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .car-status {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-rented {
            background: #f8d7da;
            color: #721c24;
        }

        /* Features Section */
        .features {
            padding: 4rem 0;
            background: white;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: var(--light);
            padding: 3rem 0;
            margin-top: 4rem;
        }

        .footer h5 {
            color: white;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: var(--light);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 2rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-outline-light {
            border-width: 2px;
            font-weight: 500;
        }

        .btn-outline-light:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-car"></i> Arangkada
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard_client.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center">
            <h1>Your Journey Begins Here</h1>
            <p class="lead">Experience the freedom of the road with our premium car rental service</p>
            <a href="<?= isset($_SESSION['user_id']) ? 'browse_cars.php' : 'register.php' ?>" class="btn btn-outline-light btn-lg">
                <?= isset($_SESSION['user_id']) ? 'Browse Cars' : 'Get Started' ?>
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <h3>Wide Selection</h3>
                        <p>Choose from our diverse fleet of well-maintained vehicles</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3>Best Rates</h3>
                        <p>Competitive pricing with no hidden fees</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>24/7 Support</h3>
                        <p>Round-the-clock customer service for your needs</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Available Cars Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Available Cars</h2>
            <div class="row">
                <?php
                try {
                    $stmt = $pdo->query("SELECT * FROM cars WHERE status = 'available' LIMIT 6");
                    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($cars as $car):
                        $imageUrl = $car['image'] ?? 'assets/images/default-car.jpg';
                ?>
                    <div class="col-md-4">
                        <div class="car-card">
                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>" 
                                 class="car-image" onerror="this.src='assets/images/default-car.jpg'">
                            <div class="car-details">
                                <h3 class="car-title"><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?></h3>
                                <div class="car-price">₱<?= number_format($car['price_per_day'], 2) ?> per day</div>
                                <span class="car-status status-available">Available</span>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="rent_car.php?id=<?= $car['id'] ?>" class="btn btn-primary w-100 mt-3">Rent Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary w-100 mt-3">Login to Rent</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Error loading cars.</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Arangkada</h5>
                    <p>Your trusted partner in car rentals, providing quality vehicles and exceptional service since 2023.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="fleet.php">Our Fleet</a></li>
                        <li><a href="terms.php">Terms & Conditions</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Info</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-phone"></i> +63 123 456 7890</li>
                        <li><i class="fas fa-envelope"></i> info@arangkada.com</li>
                        <li><i class="fas fa-map-marker-alt"></i> Cebu City, Philippines</li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Arangkada Car Rentals. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
