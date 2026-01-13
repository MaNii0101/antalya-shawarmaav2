<?php
/**
 * ANTALYA SHAWARMA - Database Configuration
 * Version: 4.0.0
 */

// Database credentials - CHANGE THESE FOR YOUR SERVER
define('DB_HOST', 'localhost');
define('DB_NAME', 'antalya_shawarma');
define('DB_USER', 'root');           // Change to your MySQL username
define('DB_PASS', '');               // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// JWT Secret for authentication tokens
define('JWT_SECRET', 'your-secret-key-change-this-in-production');
define('JWT_EXPIRY', 86400 * 7); // 7 days

// Restaurant settings
define('RESTAURANT_NAME', 'Antalya Shawarma');
define('RESTAURANT_LAT', 53.4514);
define('RESTAURANT_LNG', -2.0839);

// Delivery settings
define('DELIVERY_FEE_ZONE1', 3.99);
define('DELIVERY_FEE_ZONE2', 5.99);
define('MAX_DELIVERY_DISTANCE', 6);
define('DRIVER_EARNING_PER_DELIVERY', 3.50);

// Owner credentials
define('OWNER_EMAIL', 'admin@antalyashawarma.com');
define('OWNER_PIN', '1234');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/London');

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
