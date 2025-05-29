<?php
header('Content-Type: application/json');
require_once '../db.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all drivers or a specific driver
        $driver_id = isset($_GET['id']) ? $_GET['id'] : null;
        
        try {
            if ($driver_id) {
                // Get specific driver
                $stmt = $pdo->prepare("
                    SELECT u.id, u.username, u.email, dd.* 
                    FROM users u 
                    JOIN user_roles ur ON u.id = ur.user_id 
                    JOIN driver_details dd ON u.id = dd.user_id
                    WHERE u.id = ? AND ur.role_id = 5
                ");
                $stmt->execute([$driver_id]);
                $driver = $stmt->fetch();
                
                if ($driver) {
                    echo json_encode([
                        'status' => 'success',
                        'driver' => $driver
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Driver not found'
                    ]);
                }
            } else {
                // Get all drivers
                $stmt = $pdo->query("
                    SELECT u.id, u.username, u.email, dd.* 
                    FROM users u 
                    JOIN user_roles ur ON u.id = ur.user_id 
                    JOIN driver_details dd ON u.id = dd.user_id
                    WHERE ur.role_id = 5
                    ORDER BY u.username
                ");
                $drivers = $stmt->fetchAll();
                
                echo json_encode([
                    'status' => 'success',
                    'drivers' => $drivers
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
        // Create new driver
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        try {
            $pdo->beginTransaction();

            // Create user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password) 
                VALUES (?, ?, ?)
            ");
            
            // Generate a random password that will be changed on first login
            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            
            $stmt->execute([
                $input['username'],
                $input['email'],
                $password
            ]);
            
            $user_id = $pdo->lastInsertId();

            // Assign driver role
            $stmt = $pdo->prepare("
                INSERT INTO user_roles (user_id, role_id) 
                VALUES (?, 5)
            ");
            $stmt->execute([$user_id]);

            // Create driver details
            $stmt = $pdo->prepare("
                INSERT INTO driver_details (user_id, license_number, license_expiry, contact_number, address, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $input['license_number'],
                $input['license_expiry'],
                $input['contact_number'],
                $input['address'],
                $input['status']
            ]);

            $pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Driver created successfully',
                'driver_id' => $user_id
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        // Update driver
        $input = json_decode(file_get_contents('php://input'), true);
        $driver_id = $input['id'] ?? null;

        if (!$driver_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Driver ID is required'
            ]);
            break;
        }

        try {
            $pdo->beginTransaction();

            // Update user
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, 
                    email = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $input['username'],
                $input['email'],
                $driver_id
            ]);

            // Update driver details
            $stmt = $pdo->prepare("
                UPDATE driver_details 
                SET license_number = ?,
                    license_expiry = ?,
                    contact_number = ?,
                    address = ?,
                    status = ?
                WHERE user_id = ?
            ");
            
            $stmt->execute([
                $input['license_number'],
                $input['license_expiry'],
                $input['contact_number'],
                $input['address'],
                $input['status'],
                $driver_id
            ]);

            $pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Driver updated successfully'
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'DELETE':
        // Delete driver
        $input = json_decode(file_get_contents('php://input'), true);
        $driver_id = $input['id'] ?? null;

        if (!$driver_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Driver ID is required'
            ]);
            break;
        }

        try {
            $pdo->beginTransaction();

            // Delete driver details (will cascade to user_roles)
            $stmt = $pdo->prepare("DELETE FROM driver_details WHERE user_id = ?");
            $stmt->execute([$driver_id]);

            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$driver_id]);

            $pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Driver deleted successfully'
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
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