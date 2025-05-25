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
    <header>
        <h1>Welcome to Our Car Rental System</h1>
        <nav>
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

    <main>
        <section>
            <h2>About Us</h2>
            <p>We provide a wide range of cars for rent at competitive prices. Whether you need a car for a day, a week, or longer, we have you covered.</p>
        </section>

        <section>
            <h2>Our Fleet</h2>
            <p>Explore our diverse fleet of vehicles, ranging from economy to luxury. We ensure that all our cars are well-maintained and ready for your next adventure.</p>
        </section>

        <section>
            <h2>Contact Us</h2>
            <p>Have questions or need assistance? Contact our customer service team for help with your rental needs.</p>
        </section>
    </main>

    <footer>
        <p>&copy; 2023 Car Rental System. All rights reserved.</p>
    </footer>
</body>
</html>
