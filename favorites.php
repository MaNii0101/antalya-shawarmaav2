<?php
/**
 * ANTALYA SHAWARMA - Favorites & Notifications API
 * Endpoints: /api/favorites.php
 */

require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = getParam('action', 'list');
$type = getParam('type', 'favorites'); // favorites or notifications

if ($type === 'notifications') {
    handleNotifications($method, $action);
} else {
    handleFavorites($method, $action);
}

// ========================================
// FAVORITES HANDLERS
// ========================================
function handleFavorites($method, $action) {
    $db = db();
    $user = requireAuth();
    
    switch ($method) {
        case 'GET':
            $favorites = $db->fetchAll(
                "SELECT m.*, c.name as category_name 
                 FROM favorites f 
                 JOIN menu_items m ON f.menu_item_id = m.id 
                 JOIN categories c ON m.category_id = c.id 
                 WHERE f.user_id = ? 
                 ORDER BY f.created_at DESC",
                [$user['id']]
            );
            successResponse($favorites);
            break;
            
        case 'POST':
            $input = getInput();
            requireParams(['menu_item_id'], $input);
            
            // Check if item exists
            $item = $db->fetch("SELECT id FROM menu_items WHERE id = ?", [$input['menu_item_id']]);
            if (!$item) errorResponse('Menu item not found', 404);
            
            // Check if already favorited
            $existing = $db->fetch(
                "SELECT id FROM favorites WHERE user_id = ? AND menu_item_id = ?",
                [$user['id'], $input['menu_item_id']]
            );
            
            if ($existing) {
                errorResponse('Item already in favorites');
            }
            
            $db->insert(
                "INSERT INTO favorites (user_id, menu_item_id) VALUES (?, ?)",
                [$user['id'], $input['menu_item_id']]
            );
            
            successResponse(null, 'Added to favorites');
            break;
            
        case 'DELETE':
            $itemId = getParam('menu_item_id');
            if (!$itemId) errorResponse('Menu item ID required');
            
            $db->delete(
                "DELETE FROM favorites WHERE user_id = ? AND menu_item_id = ?",
                [$user['id'], $itemId]
            );
            
            successResponse(null, 'Removed from favorites');
            break;
            
        default:
            errorResponse('Method not allowed', 405);
    }
}

// ========================================
// NOTIFICATIONS HANDLERS
// ========================================
function handleNotifications($method, $action) {
    $db = db();
    $user = requireAuth();
    
    switch ($method) {
        case 'GET':
            if ($action === 'unread-count') {
                $count = $db->fetch(
                    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
                    [$user['id']]
                );
                successResponse(['count' => $count['count']]);
            } else {
                $limit = getParam('limit', 50);
                $notifications = $db->fetchAll(
                    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
                    [$user['id'], (int)$limit]
                );
                successResponse($notifications);
            }
            break;
            
        case 'PUT':
            if ($action === 'read') {
                $input = getInput();
                $id = $input['id'] ?? getParam('id');
                
                if ($id) {
                    $db->update(
                        "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
                        [$id, $user['id']]
                    );
                } else {
                    // Mark all as read
                    $db->update(
                        "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
                        [$user['id']]
                    );
                }
                successResponse(null, 'Marked as read');
            } else {
                errorResponse('Invalid action');
            }
            break;
            
        case 'DELETE':
            $id = getParam('id');
            if ($id) {
                $db->delete(
                    "DELETE FROM notifications WHERE id = ? AND user_id = ?",
                    [$id, $user['id']]
                );
            } else {
                // Delete all
                $db->delete("DELETE FROM notifications WHERE user_id = ?", [$user['id']]);
            }
            successResponse(null, 'Notifications deleted');
            break;
            
        default:
            errorResponse('Method not allowed', 405);
    }
}
