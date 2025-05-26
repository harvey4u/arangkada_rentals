<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Rental System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
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
        </div>
    </header>

    <main class="main">
        <section class="hero">
            <h2>Your Journey Begins Here</h2>
            <p>Reliable, Affordable, and Convenient Car Rentals</p>
            <a href="fleet.php" class="btn">Browse Fleet</a>
        </section>

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

    <footer class="footer">
        <p>&copy; 2025 Arangkada Car Rentals. All rights reserved.</p>
    </footer>
</body>
</html>
