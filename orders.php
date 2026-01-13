<?php
/**
 * ANTALYA SHAWARMA - Orders API
 * Endpoints: /api/orders.php
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
    default:
        errorResponse('Method not allowed', 405);
}

// ========================================
// GET HANDLERS
// ========================================
function handleGet($action) {
    $db = db();
    
    switch ($action) {
        case 'my-orders':
            $user = requireAuth();
            $orders = $db->fetchAll(
                "SELECT o.*, d.name as driver_name, d.phone as driver_phone 
                 FROM orders o 
                 LEFT JOIN drivers d ON o.driver_id = d.id 
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC",
                [$user['id']]
            );
            
            // Get items for each order
            foreach ($orders as &$order) {
                $order['items'] = $db->fetchAll(
                    "SELECT * FROM order_items WHERE order_id = ?",
                    [$order['id']]
                );
            }
            
            successResponse($orders);
            break;
            
        case 'active':
            $user = requireAuth();
            $orders = $db->fetchAll(
                "SELECT o.*, d.name as driver_name, d.phone as driver_phone,
                        d.current_lat as driver_lat, d.current_lng as driver_lng
                 FROM orders o 
                 LEFT JOIN drivers d ON o.driver_id = d.id 
                 WHERE o.user_id = ? AND o.status IN ('pending', 'accepted', 'preparing', 'ready', 'out_for_delivery')
                 ORDER BY o.created_at DESC",
                [$user['id']]
            );
            
            foreach ($orders as &$order) {
                $order['items'] = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
            }
            
            successResponse($orders);
            break;
            
        case 'get':
            $user = requireAuth();
            $id = getParam('id');
            $orderNumber = getParam('order_number');
            
            if ($id) {
                $order = $db->fetch("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$id, $user['id']]);
            } elseif ($orderNumber) {
                $order = $db->fetch("SELECT * FROM orders WHERE order_number = ? AND user_id = ?", [$orderNumber, $user['id']]);
            } else {
                errorResponse('Order ID or number required');
            }
            
            if (!$order) errorResponse('Order not found', 404);
            
            $order['items'] = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
            
            if ($order['driver_id']) {
                $driver = $db->fetch("SELECT id, name, phone, current_lat, current_lng FROM drivers WHERE id = ?", [$order['driver_id']]);
                $order['driver'] = $driver;
            }
            
            successResponse($order);
            break;
            
        case 'pending':
            // For restaurant dashboard
            requireOwner();
            $orders = $db->fetchAll(
                "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 WHERE o.status IN ('pending', 'accepted', 'preparing', 'ready')
                 ORDER BY o.created_at ASC"
            );
            
            foreach ($orders as &$order) {
                $order['items'] = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
            }
            
            successResponse($orders);
            break;
            
        case 'all':
            requireOwner();
            $limit = getParam('limit', 50);
            $offset = getParam('offset', 0);
            $status = getParam('status');
            
            $sql = "SELECT o.*, u.name as customer_name, d.name as driver_name 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    LEFT JOIN drivers d ON o.driver_id = d.id";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE o.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $orders = $db->fetchAll($sql, $params);
            successResponse($orders);
            break;
            
        case 'stats':
            requireOwner();
            $dateFrom = getParam('from', date('Y-m-d', strtotime('-30 days')));
            $dateTo = getParam('to', date('Y-m-d'));
            
            $stats = $db->fetch(
                "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status IN ('pending', 'accepted', 'preparing', 'ready', 'out_for_delivery') THEN 1 ELSE 0 END) as active,
                    COALESCE(SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END), 0) as total_revenue
                 FROM orders 
                 WHERE DATE(created_at) BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            );
            
            successResponse($stats);
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
        case 'create':
            $user = requireAuth();
            requireParams(['items', 'payment_method', 'delivery_address'], $input);
            
            $items = $input['items'];
            if (empty($items)) errorResponse('Cart is empty');
            
            // Calculate totals
            $subtotal = 0;
            $orderItems = [];
            
            foreach ($items as $item) {
                $menuItem = $db->fetch("SELECT * FROM menu_items WHERE id = ? AND is_available = 1", [$item['id']]);
                if (!$menuItem) errorResponse("Item '{$item['name']}' is not available");
                
                $itemTotal = $menuItem['price'] * $item['quantity'];
                $subtotal += $itemTotal;
                
                $orderItems[] = [
                    'menu_item_id' => $menuItem['id'],
                    'name' => $menuItem['name'],
                    'price' => $menuItem['price'],
                    'quantity' => $item['quantity'],
                    'options' => isset($item['options']) ? json_encode($item['options']) : null
                ];
            }
            
            // Calculate delivery fee
            $deliveryLat = $input['delivery_lat'] ?? $user['location_lat'];
            $deliveryLng = $input['delivery_lng'] ?? $user['location_lng'];
            
            $distance = 0;
            $deliveryFee = 0;
            
            if ($deliveryLat && $deliveryLng) {
                $distance = calculateDistance(RESTAURANT_LAT, RESTAURANT_LNG, $deliveryLat, $deliveryLng);
                $deliveryFee = calculateDeliveryFee($distance);
                
                if ($deliveryFee === null) {
                    errorResponse('Delivery address is outside our delivery zone (max ' . MAX_DELIVERY_DISTANCE . ' miles)');
                }
            }
            
            $total = $subtotal + $deliveryFee;
            
            // Create order
            $db->beginTransaction();
            try {
                $orderNumber = generateOrderNumber();
                
                $orderId = $db->insert(
                    "INSERT INTO orders (order_number, user_id, status, subtotal, delivery_fee, total, 
                     payment_method, delivery_address, delivery_lat, delivery_lng, delivery_distance, notes) 
                     VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderNumber,
                        $user['id'],
                        $subtotal,
                        $deliveryFee,
                        $total,
                        $input['payment_method'],
                        $input['delivery_address'],
                        $deliveryLat,
                        $deliveryLng,
                        $distance,
                        $input['notes'] ?? null
                    ]
                );
                
                // Insert order items
                foreach ($orderItems as $item) {
                    $db->insert(
                        "INSERT INTO order_items (order_id, menu_item_id, name, price, quantity, options) 
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [$orderId, $item['menu_item_id'], $item['name'], $item['price'], $item['quantity'], $item['options']]
                    );
                }
                
                // Create notification
                $db->insert(
                    "INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, 'order', ?, ?, ?)",
                    [
                        $user['id'],
                        'Order Placed',
                        "Your order #$orderNumber has been placed successfully!",
                        json_encode(['order_id' => $orderId, 'order_number' => $orderNumber])
                    ]
                );
                
                $db->commit();
                
                $order = $db->fetch("SELECT * FROM orders WHERE id = ?", [$orderId]);
                $order['items'] = $orderItems;
                
                successResponse($order, 'Order placed successfully');
                
            } catch (Exception $e) {
                $db->rollback();
                errorResponse('Failed to create order: ' . $e->getMessage());
            }
            break;
            
        case 'cancel':
            $user = requireAuth();
            requireParams(['order_id'], $input);
            
            $order = $db->fetch(
                "SELECT * FROM orders WHERE id = ? AND user_id = ?",
                [$input['order_id'], $user['id']]
            );
            
            if (!$order) errorResponse('Order not found', 404);
            if (!in_array($order['status'], ['pending'])) {
                errorResponse('Order cannot be cancelled at this stage');
            }
            
            $db->update(
                "UPDATE orders SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = ? WHERE id = ?",
                [$input['reason'] ?? 'Cancelled by customer', $order['id']]
            );
            
            // Notification
            $db->insert(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'order', ?, ?)",
                [$user['id'], 'Order Cancelled', "Your order #{$order['order_number']} has been cancelled."]
            );
            
            successResponse(null, 'Order cancelled');
            break;
            
        case 'reorder':
            $user = requireAuth();
            requireParams(['order_id'], $input);
            
            $order = $db->fetch("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$input['order_id'], $user['id']]);
            if (!$order) errorResponse('Order not found', 404);
            
            $items = $db->fetchAll("SELECT menu_item_id as id, name, quantity FROM order_items WHERE order_id = ?", [$order['id']]);
            
            // Check availability
            foreach ($items as &$item) {
                $menuItem = $db->fetch("SELECT is_available FROM menu_items WHERE id = ?", [$item['id']]);
                $item['available'] = $menuItem && $menuItem['is_available'];
            }
            
            successResponse($items);
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

// ========================================
// PUT HANDLERS (Restaurant/Owner)
// ========================================
function handlePut($action) {
    $db = db();
    $input = getInput();
    requireOwner();
    
    switch ($action) {
        case 'accept':
            requireParams(['order_id'], $input);
            
            $order = $db->fetch("SELECT * FROM orders WHERE id = ? AND status = 'pending'", [$input['order_id']]);
            if (!$order) errorResponse('Order not found or not pending');
            
            $estimatedTime = $input['estimated_time'] ?? 30;
            
            $db->update(
                "UPDATE orders SET status = 'accepted', accepted_at = NOW(), estimated_time = ? WHERE id = ?",
                [$estimatedTime, $order['id']]
            );
            
            // Notify customer
            $db->insert(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'order', ?, ?)",
                [$order['user_id'], 'Order Accepted', "Your order #{$order['order_number']} has been accepted! Estimated time: {$estimatedTime} minutes."]
            );
            
            successResponse(null, 'Order accepted');
            break;
            
        case 'preparing':
            requireParams(['order_id'], $input);
            
            $db->update("UPDATE orders SET status = 'preparing' WHERE id = ? AND status = 'accepted'", [$input['order_id']]);
            successResponse(null, 'Order is being prepared');
            break;
            
        case 'ready':
            requireParams(['order_id'], $input);
            
            $order = $db->fetch("SELECT * FROM orders WHERE id = ?", [$input['order_id']]);
            if (!$order) errorResponse('Order not found');
            
            $db->update("UPDATE orders SET status = 'ready', prepared_at = NOW() WHERE id = ?", [$order['id']]);
            
            // Notify customer
            $db->insert(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'order', ?, ?)",
                [$order['user_id'], 'Order Ready', "Your order #{$order['order_number']} is ready for pickup!"]
            );
            
            successResponse(null, 'Order marked as ready');
            break;
            
        case 'assign-driver':
            requireParams(['order_id', 'driver_id'], $input);
            
            $driver = $db->fetch("SELECT * FROM drivers WHERE id = ? AND is_active = 1", [$input['driver_id']]);
            if (!$driver) errorResponse('Driver not found or inactive');
            
            $db->update(
                "UPDATE orders SET driver_id = ?, status = 'out_for_delivery', picked_up_at = NOW() WHERE id = ?",
                [$input['driver_id'], $input['order_id']]
            );
            
            $order = $db->fetch("SELECT * FROM orders WHERE id = ?", [$input['order_id']]);
            
            // Notify customer
            $db->insert(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'delivery', ?, ?)",
                [$order['user_id'], 'Driver Assigned', "Your order #{$order['order_number']} is on its way! Driver: {$driver['name']}"]
            );
            
            successResponse(null, 'Driver assigned');
            break;
            
        case 'reject':
            requireParams(['order_id'], $input);
            
            $order = $db->fetch("SELECT * FROM orders WHERE id = ?", [$input['order_id']]);
            if (!$order) errorResponse('Order not found');
            
            $db->update(
                "UPDATE orders SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = ? WHERE id = ?",
                [$input['reason'] ?? 'Rejected by restaurant', $order['id']]
            );
            
            // Notify customer
            $db->insert(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'order', ?, ?)",
                [$order['user_id'], 'Order Rejected', "Sorry, your order #{$order['order_number']} could not be fulfilled. Reason: " . ($input['reason'] ?? 'Restaurant busy')]
            );
            
            successResponse(null, 'Order rejected');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
