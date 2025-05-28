<?php
session_start();

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get first and last day of the month
$first_day = date('Y-m-01', strtotime("$year-$month-01"));
$last_day = date('Y-m-t', strtotime("$year-$month-01"));

// Fetch all rentals for the month
$stmt = $pdo->prepare("
    SELECT r.*,
           c.make, c.model, c.year, c.plate_number,
           u.username as client_name,
           cc.name as category_name,
           DATEDIFF(r.end_date, r.start_date) as duration,
           creator.username as created_by
    FROM rentals r
    JOIN cars c ON r.car_id = c.id
    JOIN users u ON r.client_id = u.id
    JOIN users creator ON r.user_id = creator.id
    LEFT JOIN car_categories cc ON c.category_id = cc.id
    WHERE (r.start_date BETWEEN ? AND ? OR r.end_date BETWEEN ? AND ?)
          OR (r.start_date <= ? AND r.end_date >= ?)
    ORDER BY r.start_date
");
$stmt->execute([$first_day, $last_day, $first_day, $last_day, $first_day, $last_day]);
$rentals = $stmt->fetchAll();

// Process rentals into a daily array
$daily_rentals = [];
foreach ($rentals as $rental) {
    $start = max(strtotime($rental['start_date']), strtotime($first_day));
    $end = min(strtotime($rental['end_date']), strtotime($last_day));
    
    for ($day = $start; $day <= $end; $day = strtotime('+1 day', $day)) {
        $date = date('Y-m-d', $day);
        if (!isset($daily_rentals[$date])) {
            $daily_rentals[$date] = [];
        }
        $daily_rentals[$date][] = $rental;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Calendar - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--gray-light);
            color: var(--dark);
            line-height: 1.5;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: var(--spacing-lg);
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            box-sizing: border-box;
        }

        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: var(--spacing-lg);
            overflow: hidden;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md);
            background: var(--white);
            border-bottom: 1px solid var(--gray-light);
        }

        .calendar-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .calendar-nav {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-sm);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.875rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--gray-light);
            color: var(--dark);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: var(--gray-light);
            padding: 1px;
        }

        .calendar-weekday {
            background: var(--white);
            padding: var(--spacing-sm);
            text-align: center;
            font-weight: 600;
            color: var(--gray);
        }

        .calendar-day {
            background: var(--white);
            min-height: 120px;
            padding: var(--spacing-sm);
            position: relative;
        }

        .calendar-day.other-month {
            background: var(--gray-light);
            opacity: 0.7;
        }

        .calendar-day.today {
            background: var(--primary-light);
        }

        .day-number {
            position: absolute;
            top: var(--spacing-xs);
            right: var(--spacing-xs);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 0.875rem;
            border-radius: 50%;
        }

        .today .day-number {
            background: var(--primary);
            color: var(--white);
        }

        .rental-item {
            margin-top: 24px;
            padding: var(--spacing-xs);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: var(--spacing-xs);
        }

        .rental-item:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }

        .rental-item.status-pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .rental-item.status-active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .rental-item.status-completed {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .rental-item.status-cancelled {
            background: var(--danger-light);
            color: var(--danger);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            padding: var(--spacing-md);
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 5vh;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 600px;
            margin: auto;
            position: relative;
            padding: var(--spacing-lg);
        }

        .modal-header {
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--gray-light);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .rental-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }

        .detail-label {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .detail-value {
            font-weight: 500;
            color: var(--dark);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: var(--spacing-sm);
            }

            .calendar-header {
                flex-direction: column;
                gap: var(--spacing-md);
            }

            .calendar-grid {
                font-size: 0.875rem;
            }

            .calendar-day {
                min-height: 100px;
            }

            .rental-details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_superadmin.php'; ?>

    <main class="main-content">
        <div class="card">
            <div class="calendar-header">
                <h2 class="calendar-title">
                    <i class="fas fa-calendar"></i>
                    <?= date('F Y', strtotime("$year-$month-01")) ?>
                </h2>
                <div class="calendar-nav">
                    <?php
                    $prev_month = $month - 1;
                    $prev_year = $year;
                    if ($prev_month < 1) {
                        $prev_month = 12;
                        $prev_year--;
                    }
                    
                    $next_month = $month + 1;
                    $next_year = $year;
                    if ($next_month > 12) {
                        $next_month = 1;
                        $next_year++;
                    }
                    ?>
                    <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-primary">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </a>
                    <a href="?month=<?= date('m') ?>&year=<?= date('Y') ?>" class="btn btn-secondary">
                        Today
                    </a>
                    <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-primary">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <div class="calendar-grid">
                <?php
                // Display weekday headers
                $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($weekdays as $weekday) {
                    echo "<div class='calendar-weekday'>$weekday</div>";
                }

                // Get the first day of the month
                $first_day_of_month = date('w', strtotime($first_day));
                
                // Display days from previous month
                $prev_month_days = date('t', strtotime('-1 month', strtotime($first_day)));
                for ($i = $first_day_of_month - 1; $i >= 0; $i--) {
                    $day = $prev_month_days - $i;
                    echo "<div class='calendar-day other-month'><span class='day-number'>$day</span></div>";
                }

                // Display days of current month
                $days_in_month = date('t', strtotime($first_day));
                $today = date('Y-m-d');
                
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date = date('Y-m-d', strtotime("$year-$month-$day"));
                    $is_today = $date === $today;
                    
                    echo "<div class='calendar-day" . ($is_today ? ' today' : '') . "'>";
                    echo "<span class='day-number'>$day</span>";
                    
                    // Display rentals for this day
                    if (isset($daily_rentals[$date])) {
                        foreach ($daily_rentals[$date] as $rental) {
                            $start_date = date('Y-m-d', strtotime($rental['start_date']));
                            $is_start = $start_date === $date;
                            
                            echo "<div class='rental-item status-{$rental['status']}' onclick='showRentalDetails(" . json_encode($rental) . ")'>";
                            echo "<div>" . htmlspecialchars($rental['make'] . ' ' . $rental['model']) . "</div>";
                            if ($is_start) {
                                echo "<small>Client: " . htmlspecialchars($rental['client_name']) . "</small>";
                            }
                            echo "</div>";
                        }
                    }
                    
                    echo "</div>";
                }

                // Fill remaining days from next month
                $last_day_of_month = date('w', strtotime($last_day));
                if ($last_day_of_month < 6) {
                    for ($i = 1; $i <= (6 - $last_day_of_month); $i++) {
                        echo "<div class='calendar-day other-month'><span class='day-number'>$i</span></div>";
                    }
                }
                ?>
            </div>
        </div>
    </main>

    <!-- Rental Details Modal -->
    <div class="modal" id="rentalDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Rental Details</h3>
            </div>
            <div class="rental-details">
                <div class="rental-details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Car</span>
                        <span class="detail-value" id="modalCar"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Client</span>
                        <span class="detail-value" id="modalClient"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Created By</span>
                        <span class="detail-value" id="modalCreatedBy"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value" id="modalStatus"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Start Date</span>
                        <span class="detail-value" id="modalStartDate"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">End Date</span>
                        <span class="detail-value" id="modalEndDate"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value" id="modalDuration"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Amount</span>
                        <span class="detail-value" id="modalAmount"></span>
                    </div>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-primary" onclick="viewRental()">
                        <i class="fas fa-eye"></i>
                        View in Rentals
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentRental = null;

        function showRentalDetails(rental) {
            currentRental = rental;
            
            document.getElementById('modalCar').textContent = 
                `${rental.make} ${rental.model} ${rental.year} (${rental.category_name})`;
            document.getElementById('modalClient').textContent = rental.client_name;
            document.getElementById('modalCreatedBy').textContent = rental.created_by;
            document.getElementById('modalStatus').textContent = rental.status.charAt(0).toUpperCase() + rental.status.slice(1);
            document.getElementById('modalStartDate').textContent = new Date(rental.start_date).toLocaleDateString();
            document.getElementById('modalEndDate').textContent = new Date(rental.end_date).toLocaleDateString();
            document.getElementById('modalDuration').textContent = `${rental.duration} days`;
            document.getElementById('modalAmount').textContent = `₱${parseFloat(rental.total_price).toFixed(2)}`;
            
            document.getElementById('rentalDetailsModal').classList.add('active');
        }

        function viewRental() {
            if (currentRental) {
                window.location.href = `manage_rentals.php?rental_id=${currentRental.id}`;
            }
        }

        function closeModal() {
            document.getElementById('rentalDetailsModal').classList.remove('active');
            currentRental = null;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rentalDetailsModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 