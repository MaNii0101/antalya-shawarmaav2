<?php
/**
 * ANTALYA SHAWARMA - Menu API
 * Endpoints: /api/menu.php
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
        case 'categories':
            $categories = $db->fetchAll(
                "SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order"
            );
            successResponse($categories);
            break;
            
        case 'list':
            $categoryId = getParam('category_id');
            $includeUnavailable = getParam('include_unavailable', '0') === '1';
            
            $sql = "SELECT m.*, c.name as category_name, c.slug as category_slug 
                    FROM menu_items m 
                    JOIN categories c ON m.category_id = c.id 
                    WHERE c.is_active = 1";
            $params = [];
            
            if ($categoryId) {
                $sql .= " AND m.category_id = ?";
                $params[] = $categoryId;
            }
            
            if (!$includeUnavailable) {
                $sql .= " AND m.is_available = 1";
            }
            
            $sql .= " ORDER BY c.sort_order, m.sort_order";
            
            $items = $db->fetchAll($sql, $params);
            successResponse($items);
            break;
            
        case 'item':
            $id = getParam('id');
            if (!$id) errorResponse('Item ID required');
            
            $item = $db->fetch(
                "SELECT m.*, c.name as category_name FROM menu_items m 
                 JOIN categories c ON m.category_id = c.id WHERE m.id = ?",
                [$id]
            );
            if (!$item) errorResponse('Item not found', 404);
            successResponse($item);
            break;
            
        case 'full':
            // Get categories with their items (for frontend)
            $categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");
            $items = $db->fetchAll("SELECT * FROM menu_items ORDER BY category_id, sort_order");
            
            // Group items by category
            $menu = [];
            foreach ($categories as $cat) {
                $catItems = array_filter($items, fn($item) => $item['category_id'] == $cat['id']);
                $menu[$cat['slug']] = [
                    'category' => $cat,
                    'items' => array_values($catItems)
                ];
            }
            successResponse($menu);
            break;
            
        case 'search':
            $query = getParam('q', '');
            if (strlen($query) < 2) errorResponse('Search query must be at least 2 characters');
            
            $items = $db->fetchAll(
                "SELECT m.*, c.name as category_name FROM menu_items m 
                 JOIN categories c ON m.category_id = c.id 
                 WHERE m.is_available = 1 AND (m.name LIKE ? OR m.description LIKE ?)
                 ORDER BY m.name",
                ["%$query%", "%$query%"]
            );
            successResponse($items);
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

// ========================================
// POST HANDLERS (Owner only)
// ========================================
function handlePost($action) {
    $db = db();
    $input = getInput();
    requireOwner();
    
    switch ($action) {
        case 'add-category':
            requireParams(['name', 'slug'], $input);
            
            $slug = preg_replace('/[^a-z0-9_]/', '', strtolower($input['slug']));
            
            // Check if slug exists
            $existing = $db->fetch("SELECT id FROM categories WHERE slug = ?", [$slug]);
            if ($existing) errorResponse('Category slug already exists');
            
            $maxOrder = $db->fetch("SELECT MAX(sort_order) as max FROM categories");
            $sortOrder = ($maxOrder['max'] ?? 0) + 1;
            
            $id = $db->insert(
                "INSERT INTO categories (slug, name, icon, image, sort_order) VALUES (?, ?, ?, ?, ?)",
                [$slug, $input['name'], $input['icon'] ?? '🍽️', $input['image'] ?? null, $sortOrder]
            );
            
            $category = $db->fetch("SELECT * FROM categories WHERE id = ?", [$id]);
            successResponse($category, 'Category added');
            break;
            
        case 'add-item':
            requireParams(['category_id', 'name', 'price'], $input);
            
            // Check category exists
            $category = $db->fetch("SELECT id FROM categories WHERE id = ?", [$input['category_id']]);
            if (!$category) errorResponse('Category not found');
            
            $maxOrder = $db->fetch("SELECT MAX(sort_order) as max FROM menu_items WHERE category_id = ?", [$input['category_id']]);
            $sortOrder = ($maxOrder['max'] ?? 0) + 1;
            
            $id = $db->insert(
                "INSERT INTO menu_items (category_id, name, description, price, icon, image, is_available, sort_order) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $input['category_id'],
                    $input['name'],
                    $input['description'] ?? '',
                    $input['price'],
                    $input['icon'] ?? '🍽️',
                    $input['image'] ?? null,
                    $input['is_available'] ?? 1,
                    $sortOrder
                ]
            );
            
            $item = $db->fetch("SELECT * FROM menu_items WHERE id = ?", [$id]);
            successResponse($item, 'Item added');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

// ========================================
// PUT HANDLERS (Owner only)
// ========================================
function handlePut($action) {
    $db = db();
    $input = getInput();
    requireOwner();
    
    switch ($action) {
        case 'category':
            requireParams(['id'], $input);
            
            $id = $input['id'];
            $updates = [];
            $params = [];
            
            if (isset($input['name'])) {
                $updates[] = 'name = ?';
                $params[] = $input['name'];
            }
            if (isset($input['icon'])) {
                $updates[] = 'icon = ?';
                $params[] = $input['icon'];
            }
            if (isset($input['image'])) {
                $updates[] = 'image = ?';
                $params[] = $input['image'];
            }
            if (isset($input['is_active'])) {
                $updates[] = 'is_active = ?';
                $params[] = $input['is_active'] ? 1 : 0;
            }
            if (isset($input['sort_order'])) {
                $updates[] = 'sort_order = ?';
                $params[] = $input['sort_order'];
            }
            
            if (empty($updates)) errorResponse('No fields to update');
            
            $params[] = $id;
            $db->update("UPDATE categories SET " . implode(', ', $updates) . " WHERE id = ?", $params);
            
            $category = $db->fetch("SELECT * FROM categories WHERE id = ?", [$id]);
            successResponse($category, 'Category updated');
            break;
            
        case 'item':
            requireParams(['id'], $input);
            
            $id = $input['id'];
            $updates = [];
            $params = [];
            
            if (isset($input['category_id'])) {
                $updates[] = 'category_id = ?';
                $params[] = $input['category_id'];
            }
            if (isset($input['name'])) {
                $updates[] = 'name = ?';
                $params[] = $input['name'];
            }
            if (isset($input['description'])) {
                $updates[] = 'description = ?';
                $params[] = $input['description'];
            }
            if (isset($input['price'])) {
                $updates[] = 'price = ?';
                $params[] = $input['price'];
            }
            if (isset($input['icon'])) {
                $updates[] = 'icon = ?';
                $params[] = $input['icon'];
            }
            if (isset($input['image'])) {
                $updates[] = 'image = ?';
                $params[] = $input['image'];
            }
            if (isset($input['is_available'])) {
                $updates[] = 'is_available = ?';
                $params[] = $input['is_available'] ? 1 : 0;
            }
            if (isset($input['sort_order'])) {
                $updates[] = 'sort_order = ?';
                $params[] = $input['sort_order'];
            }
            
            if (empty($updates)) errorResponse('No fields to update');
            
            $params[] = $id;
            $db->update("UPDATE menu_items SET " . implode(', ', $updates) . " WHERE id = ?", $params);
            
            $item = $db->fetch("SELECT * FROM menu_items WHERE id = ?", [$id]);
            successResponse($item, 'Item updated');
            break;
            
        case 'availability':
            requireParams(['id', 'is_available'], $input);
            
            $db->update("UPDATE menu_items SET is_available = ? WHERE id = ?", 
                [$input['is_available'] ? 1 : 0, $input['id']]);
            
            successResponse(null, 'Availability updated');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

// ========================================
// DELETE HANDLERS (Owner only)
// ========================================
function handleDelete($action) {
    $db = db();
    requireOwner();
    
    switch ($action) {
        case 'category':
            $id = getParam('id');
            if (!$id) errorResponse('Category ID required');
            
            // This will cascade delete all items in category
            $db->delete("DELETE FROM categories WHERE id = ?", [$id]);
            successResponse(null, 'Category deleted');
            break;
            
        case 'item':
            $id = getParam('id');
            if (!$id) errorResponse('Item ID required');
            
            $db->delete("DELETE FROM menu_items WHERE id = ?", [$id]);
            successResponse(null, 'Item deleted');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
