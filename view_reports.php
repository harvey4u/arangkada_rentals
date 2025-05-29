<?php
session_start();

// Check if user is logged in and has appropriate role (admin or superadmin)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
require_once 'reports.php';

// Initialize Reports class
$reports = new Reports($pdo);

// Get the report type from URL parameter
$report_type = isset($_GET['type']) ? $_GET['type'] : 'revenue';

// Get date filters if provided
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Get the report data based on type
$report_data = [];
switch($report_type) {
    case 'revenue':
        $report_data = $reports->generateRevenueReport($start_date, $end_date);
        break;
    case 'driver':
        $report_data = $reports->generateDriverReport();
        break;
    case 'fleet':
        $report_data = $reports->generateFleetReport();
        break;
    case 'monthly':
        $month = isset($_GET['month']) ? $_GET['month'] : date('m');
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        $report_data = $reports->generateMonthlySummary($month, $year);
        break;
    case 'customer':
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        $report_data = $reports->generateCustomerActivityReport($user_id);
        break;
}

// Handle CSV export
if(isset($_GET['export']) && $_GET['export'] == 'csv') {
    $filename = $report_type . '_report_' . date('Y-m-d') . '.csv';
    $reports->exportToCSV($report_data, $filename);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arangkada Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        .report-card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        /* Add styles for sidebar layout */
        .main-content {
            margin-left: 250px; /* Adjust based on your sidebar width */
            padding: 20px;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Include the appropriate sidebar based on user role
    if ($_SESSION['role'] === 'superadmin') {
        include 'sidebar_superadmin.php';
    } else {
        include 'sidebar_admin.php';
    }
    ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <h2>Arangkada Reports</h2>
                </div>
            </div>

            <!-- Report Type Selection -->
            <div class="row mb-4">
                <div class="col">
                    <div class="btn-group" role="group">
                        <a href="?type=revenue" class="btn btn-<?php echo $report_type == 'revenue' ? 'primary' : 'outline-primary'; ?>">Revenue Report</a>
                        <a href="?type=driver" class="btn btn-<?php echo $report_type == 'driver' ? 'primary' : 'outline-primary'; ?>">Driver Report</a>
                        <a href="?type=fleet" class="btn btn-<?php echo $report_type == 'fleet' ? 'primary' : 'outline-primary'; ?>">Fleet Report</a>
                        <a href="?type=monthly" class="btn btn-<?php echo $report_type == 'monthly' ? 'primary' : 'outline-primary'; ?>">Monthly Summary</a>
                        <a href="?type=customer" class="btn btn-<?php echo $report_type == 'customer' ? 'primary' : 'outline-primary'; ?>">Customer Activity</a>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="row mb-4">
                <div class="col">
                    <form class="card p-3">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($report_type); ?>">
                        
                        <?php if($report_type == 'revenue' || $report_type == 'fleet'): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <label>Start Date:</label>
                                <input type="date" name="start_date" class="form-control datepicker" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <label>End Date:</label>
                                <input type="date" name="end_date" class="form-control datepicker" value="<?php echo $end_date; ?>">
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if($report_type == 'monthly'): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <label>Month:</label>
                                <select name="month" class="form-control">
                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo isset($_GET['month']) && $_GET['month'] == $i ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Year:</label>
                                <select name="year" class="form-control">
                                    <?php for($i = date('Y'); $i >= date('Y')-5; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php echo isset($_GET['year']) && $_GET['year'] == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row mt-3">
                            <div class="col">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="?type=<?php echo $report_type; ?>&export=csv" class="btn btn-success">Export to CSV</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Content -->
            <div class="row">
                <div class="col">
                    <?php if(isset($report_data['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($report_data['error']); ?></div>
                    <?php else: ?>
                        <?php switch($report_type):
                            case 'revenue': ?>
                                <!-- Revenue Report -->
                                <div class="card report-card">
                                    <div class="card-body">
                                        <h4 class="card-title">Revenue Overview</h4>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="metric-value">₱<?php echo number_format($report_data['revenue_data']['total_revenue'] ?? 0, 2); ?></div>
                                                <div class="metric-label">Total Revenue</div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-value"><?php echo $report_data['rental_statistics']['total_rentals'] ?? 0; ?></div>
                                                <div class="metric-label">Total Rentals</div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-value"><?php echo $report_data['rental_statistics']['active_rentals'] ?? 0; ?></div>
                                                <div class="metric-label">Active Rentals</div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-value"><?php echo $report_data['rental_statistics']['completed_rentals'] ?? 0; ?></div>
                                                <div class="metric-label">Completed Rentals</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Popular Cars -->
                                <div class="card report-card">
                                    <div class="card-body">
                                        <h4 class="card-title">Popular Cars</h4>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Make</th>
                                                        <th>Model</th>
                                                        <th>Rental Count</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($report_data['popular_cars'] as $car): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($car['make']); ?></td>
                                                        <td><?php echo htmlspecialchars($car['model']); ?></td>
                                                        <td><?php echo $car['rental_count']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php break; ?>

                            <?php case 'driver': ?>
                                <!-- Driver Performance -->
                                <div class="card report-card">
                                    <div class="card-body">
                                        <h4 class="card-title">Driver Performance</h4>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Driver</th>
                                                        <th>Total Trips</th>
                                                        <th>Rating</th>
                                                        <th>Earnings</th>
                                                        <th>Completed</th>
                                                        <th>Cancelled</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($report_data['driver_performance'] as $driver): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($driver['username']); ?></td>
                                                        <td><?php echo $driver['total_trips']; ?></td>
                                                        <td><?php echo number_format($driver['avg_rating'], 1); ?></td>
                                                        <td>₱<?php echo number_format($driver['total_earnings'], 2); ?></td>
                                                        <td><?php echo $driver['completed_trips']; ?></td>
                                                        <td><?php echo $driver['cancelled_trips']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php break; ?>

                            <?php case 'fleet': ?>
                                <!-- Fleet Utilization -->
                                <div class="card report-card">
                                    <div class="card-body">
                                        <h4 class="card-title">Fleet Utilization</h4>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Vehicle</th>
                                                        <th>Total Rentals</th>
                                                        <th>Utilization Rate</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($report_data['car_utilization'] as $car): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></td>
                                                        <td><?php echo $car['total_rentals']; ?></td>
                                                        <td><?php echo number_format($car['utilization_rate'], 1); ?>%</td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $car['current_status'] == 'available' ? 'success' : 'warning'; ?>">
                                                                <?php echo ucfirst($car['current_status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php break; ?>

                            <?php case 'monthly': ?>
                                <!-- Monthly Summary -->
                                <div class="card report-card">
                                    <div class="card-body">
                                        <h4 class="card-title">Monthly Summary - <?php echo $report_data['month']; ?></h4>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="metric-value">₱<?php echo number_format($report_data['total_revenue'], 2); ?></div>
                                                <div class="metric-label">Total Revenue</div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-value"><?php echo $report_data['total_rentals']; ?></div>
                                                <div class="metric-label">Total Rentals</div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-value"><?php echo $report_data['unique_customers']; ?></div>
                                                <div class="metric-label">Unique Customers</div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-value"><?php echo number_format($report_data['average_driver_rating'], 1); ?></div>
                                                <div class="metric-label">Avg Driver Rating</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php break; ?>

                            <?php case 'customer': ?>
                                <!-- Customer Activity -->
                                <div class="card report-card">
                                    <div class="card-body">
                                        <h4 class="card-title">Customer Activity</h4>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Customer</th>
                                                        <th>Total Rentals</th>
                                                        <th>Total Spent</th>
                                                        <th>Last Rental</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($report_data['customer_activity'] as $customer): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($customer['username']); ?></td>
                                                        <td><?php echo $customer['total_rentals']; ?></td>
                                                        <td>₱<?php echo number_format($customer['total_spent'], 2); ?></td>
                                                        <td><?php echo $customer['last_rental_date'] ? date('M d, Y', strtotime($customer['last_rental_date'])) : 'Never'; ?></td>
                                                        <td>
                                                            <?php if($customer['active_rentals'] > 0): ?>
                                                                <span class="badge bg-primary">Active Rental</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">No Active Rental</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php break; ?>
                        <?php endswitch; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d"
            });
        });
    </script>
</body>
</html> 