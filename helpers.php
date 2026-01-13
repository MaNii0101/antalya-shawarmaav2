<?php
/**
 * ANTALYA SHAWARMA - API Helper Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

// ========================================
// RESPONSE HELPERS
// ========================================

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function successResponse($data = null, $message = 'Success') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

function errorResponse($message, $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

// ========================================
// INPUT HELPERS
// ========================================

function getInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function getParam($key, $default = null) {
    return $_GET[$key] ?? $_POST[$key] ?? $default;
}

function requireParams($params, $input) {
    $missing = [];
    foreach ($params as $param) {
        if (!isset($input[$param]) || trim($input[$param]) === '') {
            $missing[] = $param;
        }
    }
    if (!empty($missing)) {
        errorResponse('Missing required fields: ' . implode(', ', $missing));
    }
}

// ========================================
// AUTHENTICATION HELPERS
// ========================================

function generateToken($userId, $userType = 'customer') {
    $payload = [
        'user_id' => $userId,
        'user_type' => $userType,
        'exp' => time() + JWT_EXPIRY
    ];
    return base64_encode(json_encode($payload)) . '.' . hash_hmac('sha256', json_encode($payload), JWT_SECRET);
}

function verifyToken($token) {
    if (!$token) return null;
    
    $parts = explode('.', $token);
    if (count($parts) !== 2) return null;
    
    $payload = json_decode(base64_decode($parts[0]), true);
    if (!$payload) return null;
    
    // Verify signature
    $expectedSig = hash_hmac('sha256', json_encode($payload), JWT_SECRET);
    if ($parts[1] !== $expectedSig) return null;
    
    // Check expiry
    if (isset($payload['exp']) && $payload['exp'] < time()) return null;
    
    return $payload;
}

function getAuthUser() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        $payload = verifyToken($token);
        
        if ($payload) {
            $db = db();
            if ($payload['user_type'] === 'driver') {
                return $db->fetch("SELECT * FROM drivers WHERE id = ?", [$payload['user_id']]);
            } else {
                return $db->fetch("SELECT * FROM users WHERE id = ?", [$payload['user_id']]);
            }
        }
    }
    return null;
}

function requireAuth() {
    $user = getAuthUser();
    if (!$user) {
        errorResponse('Unauthorized', 401);
    }
    return $user;
}

function requireOwner() {
    $user = requireAuth();
    if ($user['role'] !== 'owner') {
        errorResponse('Owner access required', 403);
    }
    return $user;
}

// ========================================
// VALIDATION HELPERS
// ========================================

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone($phone) {
    return preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateCode($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function generateOrderNumber() {
    return 'AS' . date('ymd') . strtoupper(substr(uniqid(), -4));
}

// ========================================
// DISTANCE CALCULATION
// ========================================

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 3959; // miles
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

function calculateDeliveryFee($distance) {
    if ($distance <= 1) return 0;
    if ($distance <= 3) return DELIVERY_FEE_ZONE1;
    if ($distance <= MAX_DELIVERY_DISTANCE) return DELIVERY_FEE_ZONE2;
    return null; // Out of delivery range
}
