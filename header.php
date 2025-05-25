<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Arangkada</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
        }

        /* Header */
        header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        header .logo {
            font-size: 24px;
            font-weight: bold;
        }

        header .user-options a {
            color: white;
            margin-left: 15px;
            text-decoration: none;
            transition: color 0.3s;
        }

        header .user-options a:hover {
            color: #3498db;
        }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <div class="logo">Arangkada Car Rental</div>
    <div class="user-options">
        <a href="#">Login</a>
        <a href="#">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</header>
