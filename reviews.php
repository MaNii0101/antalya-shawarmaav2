<?php
/**
 * ANTALYA SHAWARMA - Reviews API
 * Endpoints: /api/reviews.php
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
            $limit = getParam('limit', 20);
            $offset = getParam('offset', 0);
            
            $reviews = $db->fetchAll(
                "SELECT r.*, u.name as user_name 
                 FROM reviews r 
                 JOIN users u ON r.user_id = u.id 
                 WHERE r.is_approved = 1 
                 ORDER BY r.created_at DESC 
                 LIMIT ? OFFSET ?",
                [(int)$limit, (int)$offset]
            );
            
            // Get replies for each review
            foreach ($reviews as &$review) {
                $review['replies'] = $db->fetchAll(
                    "SELECT * FROM review_replies WHERE review_id = ? ORDER BY created_at",
                    [$review['id']]
                );
            }
            
            successResponse($reviews);
            break;
            
        case 'summary':
            $summary = $db->fetch(
                "SELECT 
                    COUNT(*) as total_reviews,
                    ROUND(AVG(rating), 1) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                 FROM reviews WHERE is_approved = 1"
            );
            
            successResponse($summary);
            break;
            
        case 'my-reviews':
            $user = requireAuth();
            $reviews = $db->fetchAll(
                "SELECT r.*, 
                    (SELECT COUNT(*) FROM review_replies WHERE review_id = r.id) as reply_count
                 FROM reviews r 
                 WHERE r.user_id = ? 
                 ORDER BY r.created_at DESC",
                [$user['id']]
            );
            
            foreach ($reviews as &$review) {
                $review['replies'] = $db->fetchAll(
                    "SELECT * FROM review_replies WHERE review_id = ? ORDER BY created_at",
                    [$review['id']]
                );
            }
            
            successResponse($reviews);
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
            requireParams(['rating', 'comment'], $input);
            
            $rating = max(1, min(5, (int)$input['rating']));
            $comment = trim($input['comment']);
            $title = isset($input['title']) ? trim($input['title']) : null;
            
            if (strlen($comment) < 10) {
                errorResponse('Review must be at least 10 characters');
            }
            
            // Check if user already reviewed
            $existing = $db->fetch("SELECT id FROM reviews WHERE user_id = ?", [$user['id']]);
            if ($existing) {
                errorResponse('You have already submitted a review');
            }
            
            $id = $db->insert(
                "INSERT INTO reviews (user_id, rating, title, comment) VALUES (?, ?, ?, ?)",
                [$user['id'], $rating, $title, $comment]
            );
            
            $review = $db->fetch(
                "SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?",
                [$id]
            );
            
            successResponse($review, 'Review submitted');
            break;
            
        case 'reply':
            requireOwner();
            requireParams(['review_id', 'reply'], $input);
            
            $review = $db->fetch("SELECT id FROM reviews WHERE id = ?", [$input['review_id']]);
            if (!$review) errorResponse('Review not found', 404);
            
            $reply = trim($input['reply']);
            if (strlen($reply) < 5) {
                errorResponse('Reply must be at least 5 characters');
            }
            
            $id = $db->insert(
                "INSERT INTO review_replies (review_id, reply) VALUES (?, ?)",
                [$input['review_id'], $reply]
            );
            
            $replyData = $db->fetch("SELECT * FROM review_replies WHERE id = ?", [$id]);
            successResponse($replyData, 'Reply added');
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
    
    switch ($action) {
        case 'review':
            $user = requireAuth();
            $id = getParam('id');
            if (!$id) errorResponse('Review ID required');
            
            // User can delete own review, owner can delete any
            $review = $db->fetch("SELECT * FROM reviews WHERE id = ?", [$id]);
            if (!$review) errorResponse('Review not found', 404);
            
            if ($review['user_id'] != $user['id'] && $user['role'] !== 'owner') {
                errorResponse('Not authorized to delete this review', 403);
            }
            
            $db->delete("DELETE FROM reviews WHERE id = ?", [$id]);
            successResponse(null, 'Review deleted');
            break;
            
        case 'reply':
            requireOwner();
            $id = getParam('id');
            if (!$id) errorResponse('Reply ID required');
            
            $db->delete("DELETE FROM review_replies WHERE id = ?", [$id]);
            successResponse(null, 'Reply deleted');
            break;
            
        case 'all':
            requireOwner();
            $db->delete("DELETE FROM review_replies");
            $db->delete("DELETE FROM reviews");
            successResponse(null, 'All reviews deleted');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
