<?php
/**
 * ANTALYA SHAWARMA - Settings API
 * Endpoints: /api/settings.php
 */

require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = getParam('action', 'get');

switch ($method) {
    case 'GET':
        handleGet($action);
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
        case 'public':
            // Public settings (no auth required)
            $settings = $db->fetchAll(
                "SELECT setting_key, setting_value, setting_type FROM settings 
                 WHERE setting_key IN ('restaurant_name', 'restaurant_address', 'restaurant_phone', 
                                       'restaurant_lat', 'restaurant_lng', 'open_time', 'close_time', 
                                       'last_order_time', 'delivery_fee_zone1', 'delivery_fee_zone2', 
                                       'max_delivery_distance')"
            );
            
            $result = [];
            foreach ($settings as $s) {
                $value = $s['setting_value'];
                if ($s['setting_type'] === 'number') $value = floatval($value);
                elseif ($s['setting_type'] === 'boolean') $value = $value === '1';
                elseif ($s['setting_type'] === 'json') $value = json_decode($value, true);
                $result[$s['setting_key']] = $value;
            }
            
            successResponse($result);
            break;
            
        case 'all':
            requireOwner();
            $settings = $db->fetchAll("SELECT * FROM settings");
            
            $result = [];
            foreach ($settings as $s) {
                $value = $s['setting_value'];
                if ($s['setting_type'] === 'number') $value = floatval($value);
                elseif ($s['setting_type'] === 'boolean') $value = $value === '1';
                elseif ($s['setting_type'] === 'json') $value = json_decode($value, true);
                $result[$s['setting_key']] = $value;
            }
            
            successResponse($result);
            break;
            
        case 'bank':
            requireOwner();
            $bank = $db->fetch("SELECT * FROM bank_details WHERE is_active = 1 LIMIT 1");
            successResponse($bank);
            break;
            
        case 'stats':
            requireOwner();
            
            // Today's stats
            $today = $db->fetch(
                "SELECT 
                    COUNT(*) as orders_today,
                    COALESCE(SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END), 0) as revenue_today
                 FROM orders WHERE DATE(created_at) = CURDATE()"
            );
            
            // Pending orders
            $pending = $db->fetch(
                "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'accepted', 'preparing')"
            );
            
            // Total stats
            $total = $db->fetch(
                "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END), 0) as total_revenue,
                    (SELECT COUNT(*) FROM users WHERE role = 'customer') as total_customers,
                    (SELECT COUNT(*) FROM drivers WHERE is_active = 1) as total_drivers
                 FROM orders"
            );
            
            successResponse([
                'today' => $today,
                'pending' => $pending['count'],
                'total' => $total
            ]);
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
    requireOwner();
    
    switch ($action) {
        case 'update':
            if (empty($input)) errorResponse('No settings to update');
            
            foreach ($input as $key => $value) {
                // Determine type
                $type = 'string';
                if (is_numeric($value)) $type = 'number';
                elseif (is_bool($value)) $type = 'boolean';
                elseif (is_array($value)) {
                    $type = 'json';
                    $value = json_encode($value);
                }
                
                if (is_bool($value)) $value = $value ? '1' : '0';
                
                $db->query(
                    "INSERT INTO settings (setting_key, setting_value, setting_type) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?",
                    [$key, $value, $type, $value, $type]
                );
            }
            
            successResponse(null, 'Settings updated');
            break;
            
        case 'bank':
            requireParams(['bank_name', 'account_name', 'account_number'], $input);
            
            // Deactivate old
            $db->update("UPDATE bank_details SET is_active = 0");
            
            // Insert new
            $db->insert(
                "INSERT INTO bank_details (bank_name, account_name, account_number, sort_code, is_active) 
                 VALUES (?, ?, ?, ?, 1)",
                [
                    $input['bank_name'],
                    $input['account_name'],
                    $input['account_number'],
                    $input['sort_code'] ?? null
                ]
            );
            
            successResponse(null, 'Bank details updated');
            break;
            
        case 'pin':
            requireParams(['new_pin'], $input);
            
            if (strlen($input['new_pin']) !== 4 || !is_numeric($input['new_pin'])) {
                errorResponse('PIN must be 4 digits');
            }
            
            $db->query(
                "INSERT INTO settings (setting_key, setting_value, setting_type) 
                 VALUES ('owner_pin', ?, 'string') 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$input['new_pin'], $input['new_pin']]
            );
            
            successResponse(null, 'PIN updated');
            break;
            
        case 'reset':
            // Reset selected data
            $resetUsers = $input['reset_users'] ?? false;
            $resetOrders = $input['reset_orders'] ?? false;
            $resetDrivers = $input['reset_drivers'] ?? false;
            $resetReviews = $input['reset_reviews'] ?? false;
            
            if ($resetUsers) {
                $db->delete("DELETE FROM users WHERE role != 'owner'");
                // Reviews are deleted by trigger
            }
            
            if ($resetOrders) {
                $db->delete("DELETE FROM order_items");
                $db->delete("DELETE FROM orders");
            }
            
            if ($resetDrivers) {
                $db->delete("DELETE FROM driver_ratings");
                $db->delete("DELETE FROM drivers");
            }
            
            if ($resetReviews) {
                $db->delete("DELETE FROM review_replies");
                $db->delete("DELETE FROM reviews");
            }
            
            successResponse(null, 'Data reset complete');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
