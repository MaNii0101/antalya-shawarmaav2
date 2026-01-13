<?php
/**
 * ANTALYA SHAWARMA - Drivers API
 * Endpoints: /api/drivers.php
 */

require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = getParam('action', 'list');

switch ($method) {
    case 'GET':
        handleGet($action);
        break;
    case 'POST':
        handlePost($action);
        break;
    case 'PUT':
        handlePut($action);
        break;
    case 'DELETE':
        handleDelete($action);
        break;
    default:
        errorResponse('Method not allowed', 405);
}

// ========================================
// GET HANDLERS
// ========================================
function handleGet($action) {
    $db = db();
    
    switch ($action) {
        case 'list':
            requireOwner();
            $drivers = $db->fetchAll(
                "SELECT id, name, email, phone, vehicle_type, vehicle_number, is_active, is_available,
                        total_deliveries, total_earnings, rating, rating_count, last_active 
                 FROM drivers ORDER BY name"
            );
            successResponse($drivers);
            break;
            
        case 'available':
            requireOwner();
            $drivers = $db->fetchAll(
                "SELECT id, name, phone, vehicle_type, current_lat, current_lng, rating 
                 FROM drivers 
                 WHERE is_active = 1 AND is_available = 1"
            );
            successResponse($drivers);
            break;
            
        case 'profile':
            $driver = getDriverAuth();
            if (!$driver) errorResponse('Unauthorized', 401);
            unset($driver['password']);
            successResponse($driver);
            break;
            
        case 'my-deliveries':
            $driver = getDriverAuth();
            if (!$driver) errorResponse('Unauthorized', 401);
            
            $status = getParam('status', 'all');
            
            $sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.driver_id = ?";
            $params = [$driver['id']];
            
            if ($status === 'active') {
                $sql .= " AND o.status = 'out_for_delivery'";
            } elseif ($status === 'completed') {
                $sql .= " AND o.status = 'delivered'";
            }
            
            $sql .= " ORDER BY o.created_at DESC";
            
            $orders = $db->fetchAll($sql, $params);
            
            foreach ($orders as &$order) {
                $order['items'] = $db->fetchAll("SELECT name, quantity FROM order_items WHERE order_id = ?", [$order['id']]);
            }
            
            successResponse($orders);
            break;
            
        case 'earnings':
            $driver = getDriverAuth();
            if (!$driver) errorResponse('Unauthorized', 401);
            
            $period = getParam('period', 'today');
            
            switch ($period) {
                case 'today':
                    $dateFrom = date('Y-m-d');
                    break;
                case 'week':
                    $dateFrom = date('Y-m-d', strtotime('-7 days'));
                    break;
                case 'month':
                    $dateFrom = date('Y-m-d', strtotime('-30 days'));
                    break;
                default:
                    $dateFrom = date('Y-m-d');
            }
            
            $stats = $db->fetch(
                "SELECT COUNT(*) as deliveries, COUNT(*) * ? as earnings 
                 FROM orders 
                 WHERE driver_id = ? AND status = 'delivered' AND DATE(delivered_at) >= ?",
                [DRIVER_EARNING_PER_DELIVERY, $driver['id'], $dateFrom]
            );
            
            $stats['total_deliveries'] = $driver['total_deliveries'];
            $stats['total_earnings'] = $driver['total_earnings'];
            $stats['rating'] = $driver['rating'];
            
            successResponse($stats);
            break;
            
        case 'location':
            $driverId = getParam('driver_id');
            if (!$driverId) errorResponse('Driver ID required');
            
            $location = $db->fetch(
                "SELECT id, name, current_lat, current_lng, last_active FROM drivers WHERE id = ?",
                [$driverId]
            );
            
            if (!$location) errorResponse('Driver not found', 404);
            successResponse($location);
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

// ========================================
// POST HANDLERS
// ========================================
function handlePost($action) {
    $db = db();
    $input = getInput();
    
    switch ($action) {
        case 'login':
            requireParams(['email', 'password'], $input);
            
            $email = strtolower(trim($input['email']));
            $driver = $db->fetch("SELECT * FROM drivers WHERE email = ? AND is_active = 1", [$email]);
            
            if (!$driver) errorResponse('Driver account not found');
            if (!verifyPassword($input['password'], $driver['password'])) errorResponse('Incorrect password');
            
            // Update last active
            $db->update("UPDATE drivers SET last_active = NOW() WHERE id = ?", [$driver['id']]);
            
            $token = generateToken($driver['id'], 'driver');
            unset($driver['password']);
            
            successResponse([
                'token' => $token,
                'driver' => $driver
            ], 'Login successful');
            break;
            
        case 'add':
            requireOwner();
            requireParams(['name', 'email', 'password', 'phone'], $input);
            
            $email = strtolower(trim($input['email']));
            
            // Check if email exists
            $existing = $db->fetch("SELECT id FROM drivers WHERE email = ?", [$email]);
            if ($existing) errorResponse('Email already registered');
            
            $id = $db->insert(
                "INSERT INTO drivers (name, email, password, phone, vehicle_type, vehicle_number) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $input['name'],
                    $email,
                    hashPassword($input['password']),
                    $input['phone'],
                    $input['vehicle_type'] ?? 'car',
                    $input['vehicle_number'] ?? null
                ]
            );
            
            $driver = $db->fetch("SELECT id, name, email, phone, vehicle_type FROM drivers WHERE id = ?", [$id]);
            successResponse($driver, 'Driver added');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

// ========================================
// PUT HANDLERS
// ========================================
function handlePut($action) {
    $db = db();
    $input = getInput();
    
    switch ($action) {
        case 'location':
            $driver = getDriverAuth();
            if (!$driver) errorResponse('Unauthorized', 401);
            
            requireParams(['lat', 'lng'], $input);
            
            $db->update(
                "UPDATE drivers SET current_lat = ?, current_lng = ?, last_active = NOW() WHERE id = ?",
                [$input['lat'], $input['lng'], $driver['id']]
            );
            
            successResponse(null, 'Location updated');
            break;
            
        case 'availability':
            $driver = getDriverAuth();
            if (!$driver) errorResponse('Unauthorized', 401);
            
            $isAvailable = isset($input['is_available']) ? ($input['is_available'] ? 1 : 0) : 1;
            
            $db->update("UPDATE drivers SET is_available = ?, last_active = NOW() WHERE id = ?", 
                [$isAvailable, $driver['id']]);
            
            successResponse(null, $isAvailable ? 'Now available' : 'Now offline');
            break;
            
        case 'pickup':
            $driver = getDriverAuth();
            if (!$driver) errorResponse('Unauthorized', 401);
            
            requireParams(['order_id'], $input);
            
            $order = $db->fetch(
                "SELECT * FROM orders WHERE id = ? AND driver_id = ? AND status = 'ready'",
                [$input['order_id'], $driver['id']]
            );
            
            if (!$order) errorResponse('Order not found or not ready for pickup');
            
            $db->update(
                "UPDATE orders SET status = 'out_for_delivery', picked_up_at = NOW() WHERE id = ?",
                [$order['id']]
            );
            
            // Notify customer
            $db->insert(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'delivery', ?, ?)",
                [$order['user_id'], 'Order Picked Up', "Your order #{$order['order_number']} is on its way!"]
            );
            
            successResponse(null, 'Order picked up');
            break;
            
        case 'deliver':
            $driver = getDriverAuth();
            if (!$driver) errorResponse('Unauthorized', 401);
            
            requireParams(['order_id'], $input);
            
            $order = $db->fetch(
                "SELECT * FROM orders WHERE id = ? AND driver_id = ? AND status = 'out_for_delivery'",
                [$input['order_id'], $driver['id']]
            );
            
            if (!$order) errorResponse('Order not found or not out for delivery');
            
            $db->update(
                "UPDATE orders SET status = 'delivered', delivered_at = NOW(), payment_status = 'paid' WHERE id = ?",
                [$order['id']]
            );
            
            // Driver stats updated by trigger
            
            // Notify customer
            $db->insert(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'delivery', ?, ?)",
                [$order['user_id'], 'Order Delivered', "Your order #{$order['order_number']} has been delivered! Enjoy your meal! 🍽️"]
            );
            
            successResponse(null, 'Order delivered');
            break;
            
        case 'update':
            requireOwner();
            requireParams(['id'], $input);
            
            $updates = [];
            $params = [];
            
            foreach (['name', 'phone', 'vehicle_type', 'vehicle_number', 'is_active'] as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (isset($input['password']) && $input['password']) {
                $updates[] = 'password = ?';
                $params[] = hashPassword($input['password']);
            }
            
            if (empty($updates)) errorResponse('No fields to update');
            
            $params[] = $input['id'];
            $db->update("UPDATE drivers SET " . implode(', ', $updates) . " WHERE id = ?", $params);
            
            successResponse(null, 'Driver updated');
            break;
            
        case 'rate':
            $user = requireAuth();
            requireParams(['order_id', 'rating'], $input);
            
            $order = $db->fetch(
                "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'delivered'",
                [$input['order_id'], $user['id']]
            );
            
            if (!$order) errorResponse('Order not found or not delivered');
            if (!$order['driver_id']) errorResponse('No driver assigned to this order');
            
            // Check if already rated
            $existing = $db->fetch("SELECT id FROM driver_ratings WHERE order_id = ?", [$order['id']]);
            if ($existing) errorResponse('Already rated this delivery');
            
            $rating = max(1, min(5, (int)$input['rating']));
            
            $db->insert(
                "INSERT INTO driver_ratings (driver_id, order_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?)",
                [$order['driver_id'], $order['id'], $user['id'], $rating, $input['comment'] ?? null]
            );
            
            // Update driver average rating
            $avgRating = $db->fetch(
                "SELECT AVG(rating) as avg, COUNT(*) as count FROM driver_ratings WHERE driver_id = ?",
                [$order['driver_id']]
            );
            
            $db->update(
                "UPDATE drivers SET rating = ?, rating_count = ? WHERE id = ?",
                [$avgRating['avg'], $avgRating['count'], $order['driver_id']]
            );
            
            successResponse(null, 'Rating submitted');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

// ========================================
// DELETE HANDLERS
// ========================================
function handleDelete($action) {
    $db = db();
    requireOwner();
    
    switch ($action) {
        case 'driver':
            $id = getParam('id');
            if (!$id) errorResponse('Driver ID required');
            
            // Check for active deliveries
            $active = $db->fetch(
                "SELECT COUNT(*) as count FROM orders WHERE driver_id = ? AND status = 'out_for_delivery'",
                [$id]
            );
            if ($active['count'] > 0) errorResponse('Driver has active deliveries');
            
            $db->delete("DELETE FROM drivers WHERE id = ?", [$id]);
            successResponse(null, 'Driver deleted');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

// ========================================
// HELPER: Get driver from auth token
// ========================================
function getDriverAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $payload = verifyToken($matches[1]);
        if ($payload && $payload['user_type'] === 'driver') {
            return db()->fetch("SELECT * FROM drivers WHERE id = ?", [$payload['user_id']]);
        }
    }
    return null;
}
