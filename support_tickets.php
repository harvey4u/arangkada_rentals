<?php
require_once 'db.php';
require_once 'session.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle new ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    try {
        $subject = $_POST['subject'];
        $message = $_POST['message'];
        
        $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message, status, created_at) VALUES (?, ?, ?, 'open', NOW())");
        $stmt->execute([$user_id, $subject, $message]);
        
        $_SESSION['success_message'] = "Support ticket created successfully!";
        header('Location: support_tickets.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error creating ticket: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Arangkada</title>
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

    .ticket-form {
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    textarea.form-control {
        min-height: 100px;
    }

    .btn-submit {
        background-color: #3498db;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-submit:hover {
        background-color: #2980b9;
    }

    .tickets-list {
        margin-top: 30px;
    }

    .ticket-card {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .ticket-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .ticket-subject {
        font-weight: bold;
        font-size: 1.1em;
        color: #2c3e50;
    }

    .ticket-status {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.9em;
    }

    .status-open {
        background-color: #d4edda;
        color: #155724;
    }

    .status-closed {
        background-color: #f8d7da;
        color: #721c24;
    }

    .ticket-date {
        color: #6c757d;
        font-size: 0.9em;
    }

    .ticket-message {
        margin-top: 10px;
        color: #4a4a4a;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
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
            <h2>Support Tickets</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="ticket-form">
                <h3>Create New Ticket</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea class="form-control" id="message" name="message" required></textarea>
                    </div>
                    <button type="submit" name="submit_ticket" class="btn-submit">Submit Ticket</button>
                </form>
            </div>

            <div class="tickets-list">
                <h3>Your Tickets</h3>
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT * FROM support_tickets 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC
                    ");
                    $stmt->execute([$user_id]);
                    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($tickets) > 0) {
                        foreach ($tickets as $ticket) {
                            ?>
                            <div class="ticket-card">
                                <div class="ticket-header">
                                    <div class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></div>
                                    <span class="ticket-status status-<?= strtolower($ticket['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($ticket['status'])) ?>
                                    </span>
                                </div>
                                <div class="ticket-date">
                                    Created: <?= date('F j, Y g:i A', strtotime($ticket['created_at'])) ?>
                                </div>
                                <div class="ticket-message">
                                    <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p>No tickets found.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p>Error loading tickets: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>
