<?php
require_once 'db.php';
require_once 'analytics.php';

class Reports {
    private $analytics;
    private $conn;

    public function __construct($pdo) {
        $this->conn = $pdo;
        $this->analytics = new Analytics($pdo);
    }

    // Generate revenue report
    public function generateRevenueReport($startDate = null, $endDate = null) {
        try {
            $revenue = $this->analytics->getTotalRevenue($startDate, $endDate);
            $popularCars = $this->analytics->getMostPopularCars();
            $rentalStats = $this->analytics->getRentalStatistics();

            return [
                'revenue_data' => $revenue ?: ['total_revenue' => 0],
                'popular_cars' => $popularCars ?: [],
                'rental_statistics' => $rentalStats ?: [
                    'total_rentals' => 0,
                    'active_rentals' => 0,
                    'completed_rentals' => 0,
                    'cancelled_rentals' => 0,
                    'scheduled_rentals' => 0
                ],
                'report_generated_at' => date('Y-m-d H:i:s'),
                'period' => [
                    'start_date' => $startDate ?? 'All time',
                    'end_date' => $endDate ?? 'All time'
                ]
            ];
        } catch (Exception $e) {
            return ['error' => 'Failed to generate revenue report: ' . $e->getMessage()];
        }
    }

    // Generate driver performance report
    public function generateDriverReport() {
        try {
            $driverPerformance = $this->analytics->getDriverPerformance();
            
            return [
                'driver_performance' => $driverPerformance ?: [],
                'report_generated_at' => date('Y-m-d H:i:s'),
                'total_drivers' => count($driverPerformance)
            ];
        } catch (Exception $e) {
            return ['error' => 'Failed to generate driver report: ' . $e->getMessage()];
        }
    }

    // Generate fleet utilization report
    public function generateFleetReport() {
        try {
            $carUtilization = $this->analytics->getCarUtilizationRate();
            $avgDuration = $this->analytics->getAverageRentalDuration();

            return [
                'car_utilization' => $carUtilization ?: [],
                'average_rental_duration' => $avgDuration ?: ['avg_duration' => 0, 'total_completed_rentals' => 0],
                'report_generated_at' => date('Y-m-d H:i:s'),
                'total_cars' => count($carUtilization)
            ];
        } catch (Exception $e) {
            return ['error' => 'Failed to generate fleet report: ' . $e->getMessage()];
        }
    }

    // Export report to CSV
    public function exportToCSV($reportData, $filename) {
        try {
            if (empty($reportData) || !is_array($reportData)) {
                throw new Exception('Invalid report data');
            }

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // Write headers
            $firstRow = reset($reportData);
            if (is_array($firstRow)) {
                fputcsv($output, array_keys($firstRow));
            }
            
            // Write data rows
            foreach ($reportData as $row) {
                if (is_array($row)) {
                    fputcsv($output, $row);
                }
            }
            
            fclose($output);
            return true;
        } catch (Exception $e) {
            return ['error' => 'Failed to export CSV: ' . $e->getMessage()];
        }
    }

    // Generate monthly summary report
    public function generateMonthlySummary($month, $year) {
        try {
            $startDate = date('Y-m-01', strtotime("$year-$month-01"));
            $endDate = date('Y-m-t', strtotime("$year-$month-01"));

            $sql = "SELECT 
                    COUNT(*) as total_rentals,
                    COALESCE(SUM(total_price), 0) as total_revenue,
                    COUNT(DISTINCT car_id) as cars_rented,
                    COUNT(DISTINCT client_id) as unique_customers,
                    COALESCE(AVG(driver_rating), 0) as average_driver_rating,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_rentals,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_rentals,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rentals
                    FROM rentals
                    WHERE created_at BETWEEN :start_date AND :end_date";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();

            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['month'] = date('F Y', strtotime($startDate));
            $summary['report_generated_at'] = date('Y-m-d H:i:s');
            $summary['period'] = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            return $summary;
        } catch (Exception $e) {
            return ['error' => 'Failed to generate monthly summary: ' . $e->getMessage()];
        }
    }

    // Generate customer activity report
    public function generateCustomerActivityReport($userId = null) {
        try {
            $sql = "SELECT 
                    u.id as user_id,
                    u.username,
                    u.email,
                    COUNT(r.id) as total_rentals,
                    COALESCE(SUM(r.total_price), 0) as total_spent,
                    MAX(r.created_at) as last_rental_date,
                    COUNT(CASE WHEN r.status = 'cancelled' THEN 1 END) as cancellations,
                    COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as completed_rentals,
                    COUNT(CASE WHEN r.status = 'active' THEN 1 END) as active_rentals
                    FROM users u
                    LEFT JOIN rentals r ON u.id = r.client_id
                    JOIN user_roles ur ON u.id = ur.user_id
                    WHERE ur.role_id = 4"; // Client role

            if ($userId) {
                $sql .= " AND u.id = :user_id";
            }

            $sql .= " GROUP BY u.id, u.username, u.email
                      ORDER BY total_rentals DESC";

            $stmt = $this->conn->prepare($sql);
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            }
            $stmt->execute();

            return [
                'customer_activity' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'report_generated_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            return ['error' => 'Failed to generate customer activity report: ' . $e->getMessage()];
        }
    }
}

// Example usage:
/*
$reports = new Reports($pdo);  // Using the existing $pdo from db.php

// Generate revenue report
$revenueReport = $reports->generateRevenueReport('2023-01-01', '2023-12-31');

// Generate driver report
$driverReport = $reports->generateDriverReport();

// Generate fleet report
$fleetReport = $reports->generateFleetReport();

// Generate monthly summary
$monthlySummary = $reports->generateMonthlySummary(5, 2023);

// Generate customer activity report
$customerReport = $reports->generateCustomerActivityReport();
*/
?> 