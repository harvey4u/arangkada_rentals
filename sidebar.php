<!-- Sidebar -->
<aside class="sidebar">
    
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="#"><i class="fas fa-car"></i> Manage Rentals</a></li>
        <li><a href="#"><i class="fas fa-car"></i> Manage staff</a></li>
        <li><a href="client.php"><i class="fas fa-car"></i> Manage client</a></li>
        <li><a href="#"><i class="fas fa-car"></i> Manage Driver</a></li>
        <li><a href="#"><i class="fas fa-car-side"></i> Manage Cars</a></li>
    </ul>
</aside>

<!-- Add Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-dU3K9uGjS/8uKN5dQf79ZQ9MLrBmi6Q7FdZn50TK9v7cJ4aP2P9GyB3xXK0g8EFw50tHPF9Dc0n33XReVq4zNw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
    .sidebar {
        width: 220px;
        background-color: #ffffff;
        padding: 20px;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
        min-height: 100vh;
        font-family: 'Segoe UI', sans-serif;
        border-right: 1px solid #ddd;
    }

    .sidebar h4 {
        margin: 0 0 20px;
        color: #2c3e50;
        font-size: 1.2em;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar li {
        margin-bottom: 10px;
    }

    .sidebar a {
        text-decoration: none;
        color: #34495e;
        padding: 10px 12px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background 0.3s, color 0.3s;
    }

    .sidebar a:hover {
        background-color: #3498db;
        color: white;
    }

    .sidebar a i {
        min-width: 20px;
    }
</style>
