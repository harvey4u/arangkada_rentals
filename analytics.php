<?php
require_once 'db.php';

class Analytics {
    private $conn;

    public function __construct($pdo) {
        $this->conn = $pdo;
    }

    // Get total revenue for a given time period
    public function getTotalRevenue($startDate = null, $endDate = null) {
        $sql = "SELECT SUM(total_price) as total_revenue FROM rentals WHERE status = 'completed'";
        if ($startDate && $endDate) {
            $sql .= " AND created_at BETWEEN :start_date AND :end_date";
        }

        $stmt = $this->conn->prepare($sql);
        
        if ($startDate && $endDate) {
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get most popular cars (most rented)
    public function getMostPopularCars($limit = 5) {
        $sql = "SELECT c.make, c.model, COUNT(r.id) as rental_count 
                FROM cars c 
                LEFT JOIN rentals r ON c.id = r.car_id 
                GROUP BY c.id, c.make, c.model 
                ORDER BY rental_count DESC 
                LIMIT :limit";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get rental statistics
    public function getRentalStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_rentals,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rentals,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_rentals,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_rentals,
                COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled_rentals
                FROM rentals";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get average rental duration
    public function getAverageRentalDuration() {
        $sql = "SELECT AVG(DATEDIFF(end_date, start_date)) as avg_duration,
                       COUNT(*) as total_completed_rentals 
                FROM rentals 
                WHERE status = 'completed'";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get driver performance metrics
    public function getDriverPerformance() {
        $sql = "SELECT 
                u.username,
                u.id as driver_id,
                COUNT(DISTINCT r.id) as total_trips,
                COALESCE(AVG(r.driver_rating), 0) as avg_rating,
                COALESCE(SUM(r.driver_fee), 0) as total_earnings,
                COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as completed_trips,
                COUNT(CASE WHEN r.status = 'cancelled' THEN 1 END) as cancelled_trips
                FROM users u
                JOIN user_roles ur ON u.id = ur.user_id AND ur.role_id = 5
                LEFT JOIN rentals r ON u.id = r.driver_id
                GROUP BY u.id, u.username
                ORDER BY avg_rating DESC, total_trips DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get car utilization rate
    public function getCarUtilizationRate($carId = null) {
        $sql = "SELECT 
                c.id as car_id,
                c.make,
                c.model,
                c.status as current_status,
                COUNT(r.id) as total_rentals,
                COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as completed_rentals,
                COUNT(CASE WHEN r.status = 'active' THEN 1 END) as active_rentals,
                COALESCE(
                    (COUNT(r.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM rentals), 0)), 
                    0
                ) as utilization_rate
                FROM cars c
                LEFT JOIN rentals r ON c.id = r.car_id";
        
        if ($carId) {
            $sql .= " WHERE c.id = :car_id";
        }
        
        $sql .= " GROUP BY c.id, c.make, c.model, c.status";

        $stmt = $this->conn->prepare($sql);
        if ($carId) {
            $stmt->bindParam(':car_id', $carId);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 