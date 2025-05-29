<?php
require_once 'session.php';
require_once 'db.php';

try {
    // Get all users with role_id = 4 (Client) based on your database schema
    $sql = "SELECT users.id, users.username, users.email, users.created_at, users.is_verified,
                   roles.name as role_name
            FROM users 
            JOIN user_roles ON users.id = user_roles.user_id 
            JOIN roles ON user_roles.role_id = roles.id
            WHERE user_roles.role_id = 4 
            ORDER BY users.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - Arangkada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .container {
        display: flex;
        min-height: 100vh;
        background-color: #f5f6fa;
        padding-left: 250px;
        transition: padding-left 0.3s ease;
    }

    .main-content {
        flex: 1;
        padding: 20px;
        background-color: white;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin: 20px;
        border-radius: 5px;
        width: 100%;
    }

    .card-box {
        padding: 20px;
        border-radius: 8px;
        background-color: #f8f9fa;
        border-left: 5px solid #3498db;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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

    /* Table styling */
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

    .search-box {
        margin-bottom: 20px;
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 12px 20px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        outline: none;
        transition: border-color 0.3s;
    }

    .search-box input:focus {
        border-color: #3498db;
    }

    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-verified {
        background-color: #d4edda;
        color: #155724;
    }

    .status-unverified {
        background-color: #f8d7da;
        color: #721c24;
    }

    .actions {
        display: flex;
        gap: 5px;
        justify-content: center;
    }

    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }

    .btn-view {
        background-color: #17a2b8;
        color: white;
    }

    .btn-view:hover {
        background-color: #138496;
    }

    .btn-edit {
        background-color: #ffc107;
        color: #212529;
    }

    .btn-edit:hover {
        background-color: #e0a800;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #666;
        font-size: 1.1em;
    }

    .stats-row {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .stats-row .card-box {
        flex: 1;
        min-width: 200px;
    }

    @media (max-width: 768px) {
        .container {
            padding-left: 0;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar_' . $_SESSION['role'] . '.php'; ?>

        <main class="main-content">
            <h2 class="mb-4">Client Management</h2>

            <!-- Client Statistics -->
            <div class="stats-row">
                <div class="card-box">
                    <h3>Total Clients</h3>
                    <span><?= count($clients) ?></span>
                </div>
                <div class="card-box">
                    <h3>Verified Clients</h3>
                    <span><?= count(array_filter($clients, function($client) { return $client['is_verified'] == 1; })) ?></span>
                </div>
                <div class="card-box">
                    <h3>Unverified Clients</h3>
                    <span><?= count(array_filter($clients, function($client) { return $client['is_verified'] == 0; })) ?></span>
                </div>
            </div>

            <h2 class="mt-5">All Clients</h2>

            <!-- Search Box -->
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search clients by username or email..." onkeyup="searchTable()">
            </div>

            <?php if (count($clients) > 0): ?>
                <table id="clientsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clients as $client): ?>
                            <tr>
                                <td><?= htmlspecialchars($client["id"]) ?></td>
                                <td><?= htmlspecialchars($client["username"]) ?></td>
                                <td><?= htmlspecialchars($client["email"]) ?></td>
                                <td>
                                    <span class="status-badge <?= $client['is_verified'] ? 'status-verified' : 'status-unverified' ?>">
                                        <?= $client['is_verified'] ? 'Verified' : 'Unverified' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($client["role_name"]) ?></td>
                                <td><?= date('M d, Y', strtotime($client["created_at"])) ?></td>
                                <td class="actions">
                                    <a href="view_client.php?id=<?= $client['id'] ?>" class="btn btn-view">View</a>
                                    <a href="edit_client.php?id=<?= $client['id'] ?>" class="btn btn-edit">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <h3>No Clients Found</h3>
                    <p>There are currently no registered clients in the system.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function searchTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toUpperCase();
        const table = document.getElementById("clientsTable");
        
        if (!table) return; // If no table exists, exit
        
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            const tdUsername = tr[i].getElementsByTagName("td")[1];
            const tdEmail = tr[i].getElementsByTagName("td")[2];
            
            if (tdUsername || tdEmail) {
                const txtValueUsername = tdUsername.textContent || tdUsername.innerText;
                const txtValueEmail = tdEmail.textContent || tdEmail.innerText;
                
                if (txtValueUsername.toUpperCase().indexOf(filter) > -1 || 
                    txtValueEmail.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    </script>
</body>
</html>