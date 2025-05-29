<?php
header('Content-Type: application/json');
require_once '../db.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all cars or a specific car
        $car_id = isset($_GET['id']) ? $_GET['id'] : null;
        
        try {
            if ($car_id) {
                // Get specific car
                $stmt = $pdo->prepare("
                    SELECT c.*, cc.name as category_name 
                    FROM cars c 
                    LEFT JOIN car_categories cc ON c.category_id = cc.id 
                    WHERE c.id = ?
                ");
                $stmt->execute([$car_id]);
                $car = $stmt->fetch();
                
                if ($car) {
                    echo json_encode([
                        'status' => 'success',
                        'car' => $car
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Car not found'
                    ]);
                }
            } else {
                // Get all cars
                $stmt = $pdo->query("
                    SELECT c.*, cc.name as category_name 
                    FROM cars c 
                    LEFT JOIN car_categories cc ON c.category_id = cc.id 
                    ORDER BY c.created_at DESC
                ");
                $cars = $stmt->fetchAll();
                
                echo json_encode([
                    'status' => 'success',
                    'cars' => $cars
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'POST':
        // Create new car
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO cars (make, model, year, price_per_day, status, image, plate_number, category_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['make'],
                $input['model'],
                $input['year'],
                $input['price_per_day'],
                $input['status'] ?? 'available',
                $input['image'] ?? null,
                $input['plate_number'],
                $input['category_id'] ?? null
            ]);
            
            $car_id = $pdo->lastInsertId();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Car created successfully',
                'car_id' => $car_id
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        // Update car
        $input = json_decode(file_get_contents('php://input'), true);
        $car_id = $input['id'] ?? null;

        if (!$car_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Car ID is required'
            ]);
            break;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE cars 
                SET make = ?, 
                    model = ?, 
                    year = ?, 
                    price_per_day = ?, 
                    status = ?, 
                    image = ?, 
                    plate_number = ?, 
                    category_id = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $input['make'],
                $input['model'],
                $input['year'],
                $input['price_per_day'],
                $input['status'],
                $input['image'],
                $input['plate_number'],
                $input['category_id'],
                $car_id
            ]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Car updated successfully'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'DELETE':
        // Delete car
        $input = json_decode(file_get_contents('php://input'), true);
        $car_id = $input['id'] ?? null;

        if (!$car_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Car ID is required'
            ]);
            break;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
            $stmt->execute([$car_id]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Car deleted successfully'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid request method'
        ]);
        break;
} 