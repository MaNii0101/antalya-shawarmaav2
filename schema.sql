-- ========================================
-- ANTALYA SHAWARMA - MySQL DATABASE SCHEMA
-- Version: 4.0.0
-- ========================================

-- Create database
CREATE DATABASE IF NOT EXISTS antalya_shawarma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE antalya_shawarma;

-- ========================================
-- USERS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    dob DATE,
    address TEXT,
    location_lat DECIMAL(10, 8),
    location_lng DECIMAL(11, 8),
    role ENUM('customer', 'staff', 'owner') DEFAULT 'customer',
    verified TINYINT(1) DEFAULT 0,
    verification_code VARCHAR(6),
    reset_code VARCHAR(6),
    reset_code_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ========================================
-- CATEGORIES TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10),
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB;

-- ========================================
-- MENU ITEMS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    icon VARCHAR(10),
    image VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    is_popular TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_available (is_available)
) ENGINE=InnoDB;

-- ========================================
-- ORDERS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    driver_id INT,
    status ENUM('pending', 'accepted', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL,
    delivery_fee DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'applepay', 'googlepay') NOT NULL,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    delivery_address TEXT NOT NULL,
    delivery_lat DECIMAL(10, 8),
    delivery_lng DECIMAL(11, 8),
    delivery_distance DECIMAL(5, 2),
    notes TEXT,
    estimated_time INT,
    accepted_at DATETIME,
    prepared_at DATETIME,
    picked_up_at DATETIME,
    delivered_at DATETIME,
    cancelled_at DATETIME,
    cancel_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_driver (driver_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ========================================
-- ORDER ITEMS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    options TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ========================================
-- DRIVERS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    vehicle_type ENUM('car', 'motorcycle', 'bicycle') DEFAULT 'car',
    vehicle_number VARCHAR(20),
    profile_pic VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    is_available TINYINT(1) DEFAULT 0,
    current_lat DECIMAL(10, 8),
    current_lng DECIMAL(11, 8),
    total_deliveries INT DEFAULT 0,
    total_earnings DECIMAL(10, 2) DEFAULT 0,
    rating DECIMAL(3, 2) DEFAULT 5.00,
    rating_count INT DEFAULT 0,
    last_active DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_available (is_available),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- ========================================
-- DRIVER RATINGS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS driver_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_rating (order_id),
    INDEX idx_driver (driver_id)
) ENGINE=InnoDB;

-- ========================================
-- REVIEWS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_rating (rating),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- ========================================
-- REVIEW REPLIES TABLE (Owner replies)
-- ========================================
CREATE TABLE IF NOT EXISTS review_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    reply TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    INDEX idx_review (review_id)
) ENGINE=InnoDB;

-- ========================================
-- FAVORITES TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, menu_item_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ========================================
-- NOTIFICATIONS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('order', 'promo', 'system', 'delivery') DEFAULT 'system',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ========================================
-- SETTINGS TABLE (Restaurant settings)
-- ========================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- ========================================
-- BANK DETAILS TABLE (Owner bank info)
-- ========================================
CREATE TABLE IF NOT EXISTS bank_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100),
    account_name VARCHAR(100),
    account_number VARCHAR(50),
    sort_code VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- SESSIONS TABLE (For PHP sessions)
-- ========================================
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    token VARCHAR(255) NOT NULL UNIQUE,
    user_type ENUM('customer', 'driver', 'staff', 'owner') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- ========================================
-- DAILY STATS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL UNIQUE,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(10, 2) DEFAULT 0,
    total_deliveries INT DEFAULT 0,
    new_customers INT DEFAULT 0,
    avg_order_value DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (stat_date)
) ENGINE=InnoDB;

-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

-- Default owner account (password: admin2024)
INSERT INTO users (name, email, password, role, verified) VALUES
('Owner', 'admin@antalyashawarma.com', '$2y$10$8KzQ5JxK5JxK5JxK5JxK5.K5JxK5JxK5JxK5JxK5JxK5JxK5JxK5K', 'owner', 1);

-- Default restaurant settings
INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
('restaurant_name', 'Antalya Shawarma', 'string'),
('restaurant_address', '181 Market St, Hyde SK14 1HF', 'string'),
('restaurant_phone', '+44 161 536 1862', 'string'),
('restaurant_lat', '53.4514', 'number'),
('restaurant_lng', '-2.0839', 'number'),
('open_time', '11', 'number'),
('close_time', '23', 'number'),
('last_order_time', '22.5', 'number'),
('delivery_fee_zone1', '3.99', 'number'),
('delivery_fee_zone2', '5.99', 'number'),
('max_delivery_distance', '6', 'number'),
('owner_pin', '1234', 'string'),
('staff_password', '1234', 'string');

-- Default categories
INSERT INTO categories (slug, name, icon, sort_order) VALUES
('grill_wraps', 'Grill Wraps', '🌯', 1),
('lahmacun', 'Lahmacun', '🫓', 2),
('pide', 'Pide', '🥖', 3),
('kebabs', 'Kebabs', '🍢', 4),
('meal_boxes', 'Meal Boxes', '📦', 5),
('kids_menu', 'Kids Menu', '🧒', 6),
('chips_extras', 'Chips & Extras', '🍟', 7),
('cold_drinks', 'Cold Drinks', '🥤', 8),
('desserts', 'Desserts', '🍰', 9);

-- Sample menu items (Grill Wraps)
INSERT INTO menu_items (category_id, name, description, price, icon) VALUES
(1, 'Chicken Shawarma Wrap', 'Marinated chicken with garlic sauce, salad & pickles', 8.99, '🌯'),
(1, 'Lamb Shawarma Wrap', 'Tender lamb with tahini, salad & pickles', 9.99, '🌯'),
(1, 'Mixed Shawarma Wrap', 'Chicken & lamb with all sauces', 10.99, '🌯'),
(1, 'Falafel Wrap', 'Crispy falafel with hummus & salad', 7.99, '🧆');

-- Sample menu items (Kebabs)
INSERT INTO menu_items (category_id, name, description, price, icon) VALUES
(4, 'Adana Kebab', 'Spicy minced lamb kebab', 14.99, '🍢'),
(4, 'Chicken Shish', 'Marinated chicken pieces', 13.99, '🍗'),
(4, 'Lamb Shish', 'Tender lamb cubes', 15.99, '🍖'),
(4, 'Mixed Grill', 'Selection of all kebabs', 18.99, '🍽️');

-- Sample driver
INSERT INTO drivers (name, email, password, phone, vehicle_type) VALUES
('Demo Driver', 'driver@antalyashawarma.com', '$2y$10$8KzQ5JxK5JxK5JxK5JxK5.K5JxK5JxK5JxK5JxK5JxK5JxK5JxK5K', '+44 7700 900123', 'car');

-- ========================================
-- STORED PROCEDURES
-- ========================================

DELIMITER //

-- Get order statistics for owner dashboard
CREATE PROCEDURE GetOrderStats(IN date_from DATE, IN date_to DATE)
BEGIN
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        COALESCE(SUM(total), 0) as total_revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN date_from AND date_to;
END //

-- Update daily statistics
CREATE PROCEDURE UpdateDailyStats(IN stat_date DATE)
BEGIN
    INSERT INTO daily_stats (stat_date, total_orders, total_revenue, total_deliveries, new_customers, avg_order_value)
    SELECT 
        stat_date,
        COUNT(*),
        COALESCE(SUM(total), 0),
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END),
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = stat_date),
        COALESCE(AVG(total), 0)
    FROM orders
    WHERE DATE(created_at) = stat_date
    ON DUPLICATE KEY UPDATE
        total_orders = VALUES(total_orders),
        total_revenue = VALUES(total_revenue),
        total_deliveries = VALUES(total_deliveries),
        new_customers = VALUES(new_customers),
        avg_order_value = VALUES(avg_order_value);
END //

-- Calculate driver earnings
CREATE PROCEDURE CalculateDriverEarnings(IN driver_id_param INT, IN date_from DATE, IN date_to DATE)
BEGIN
    SELECT 
        COUNT(*) as total_deliveries,
        COUNT(*) * 3.50 as total_earnings
    FROM orders
    WHERE driver_id = driver_id_param 
    AND status = 'delivered'
    AND DATE(delivered_at) BETWEEN date_from AND date_to;
END //

DELIMITER ;

-- ========================================
-- VIEWS
-- ========================================

-- Active orders view
CREATE OR REPLACE VIEW active_orders AS
SELECT 
    o.*,
    u.name as customer_name,
    u.phone as customer_phone,
    d.name as driver_name,
    d.phone as driver_phone
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
LEFT JOIN drivers d ON o.driver_id = d.id
WHERE o.status IN ('pending', 'accepted', 'preparing', 'ready', 'out_for_delivery');

-- Review summary view
CREATE OR REPLACE VIEW review_summary AS
SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as average_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
FROM reviews
WHERE is_approved = 1;

-- ========================================
-- TRIGGERS
-- ========================================

DELIMITER //

-- Update driver stats after delivery
CREATE TRIGGER after_order_delivered
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' AND NEW.driver_id IS NOT NULL THEN
        UPDATE drivers 
        SET 
            total_deliveries = total_deliveries + 1,
            total_earnings = total_earnings + 3.50
        WHERE id = NEW.driver_id;
    END IF;
END //

-- Delete user reviews when user is deleted
CREATE TRIGGER before_user_delete
BEFORE DELETE ON users
FOR EACH ROW
BEGIN
    DELETE FROM reviews WHERE user_id = OLD.id;
    DELETE FROM favorites WHERE user_id = OLD.id;
    DELETE FROM notifications WHERE user_id = OLD.id;
END //

DELIMITER ;
