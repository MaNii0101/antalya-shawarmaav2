<?php
/**
 * ANTALYA SHAWARMA - Users API
 * Endpoints: /api/users.php
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
        case 'profile':
            $user = requireAuth();
            unset($user['password']);
            successResponse($user);
            break;
            
        case 'list':
            requireOwner();
            $users = $db->fetchAll("SELECT id, name, email, phone, role, verified, created_at FROM users ORDER BY created_at DESC");
            successResponse($users);
            break;
            
        case 'get':
            requireOwner();
            $id = getParam('id');
            if (!$id) errorResponse('User ID required');
            $user = $db->fetch("SELECT id, name, email, phone, dob, address, role, verified, created_at FROM users WHERE id = ?", [$id]);
            if (!$user) errorResponse('User not found', 404);
            successResponse($user);
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
        case 'register':
            requireParams(['name', 'email', 'password'], $input);
            
            $name = trim($input['name']);
            $email = strtolower(trim($input['email']));
            $password = $input['password'];
            $phone = $input['phone'] ?? null;
            $dob = $input['dob'] ?? null;
            
            // Validate
            if (strlen($name) < 2) errorResponse('Name must be at least 2 characters');
            if (!isValidEmail($email)) errorResponse('Invalid email address');
            if (strlen($password) < 6) errorResponse('Password must be at least 6 characters');
            if ($phone && !isValidPhone($phone)) errorResponse('Invalid phone number');
            
            // Check if email exists
            $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) errorResponse('Email already registered');
            
            // Generate verification code
            $verificationCode = generateCode();
            
            // Insert user
            $userId = $db->insert(
                "INSERT INTO users (name, email, password, phone, dob, verification_code, verified) VALUES (?, ?, ?, ?, ?, ?, 0)",
                [$name, $email, hashPassword($password), $phone, $dob, $verificationCode]
            );
            
            // In production, send verification email here
            // sendVerificationEmail($email, $verificationCode);
            async function sendVerificationEmail(email, code) {
    try {
        await fetch('https://api.sendgrid.com/v3/mail/send', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer YOUR_SENDGRID_API_KEY',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                personalizations: [{
                    to: [{ email: email }],
                    subject: 'Verify your Antalya Shawarma account'
                }],
                from: { email: 'noreply@antalyashawarma.com' },
                content: [{
                    type: 'text/html',
                    value: `
                        <h2>Verify Your Account</h2>
                        <p>Your verification code is: <strong>${code}</strong></p>
                        <p>This code will expire in 10 minutes.</p>
                    `
                }]
            })
        });
        alert('📧 Verification code sent to your email!');
    } catch (error) {
        console.error('Email error:', error);
        alert('❌ Failed to send email. Please try again.');
    }
}
            successResponse([
                'user_id' => $userId,
                'verification_code' => $verificationCode, // Remove in production
                'message' => 'Verification code sent to email'
            ], 'Registration successful');
            break;
            
        case 'login':
            requireParams(['email', 'password'], $input);
            
            $email = strtolower(trim($input['email']));
            $password = $input['password'];
            
            // Check for owner login
            if ($email === OWNER_EMAIL) {
                $user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
                if ($user && verifyPassword($password, $user['password'])) {
                    $token = generateToken($user['id'], 'owner');
                    unset($user['password'], $user['verification_code'], $user['reset_code']);
                    successResponse([
                        'token' => $token,
                        'user' => $user,
                        'is_owner' => true,
                        'require_pin' => true
                    ]);
                }
            }
            
            // Regular user login
            $user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
            if (!$user) errorResponse('Account not found');
            if (!verifyPassword($password, $user['password'])) errorResponse('Incorrect password');
            
            // Generate verification code for 2FA
            $verificationCode = generateCode();
            $db->update("UPDATE users SET verification_code = ? WHERE id = ?", [$verificationCode, $user['id']]);
            
            // In production, send 2FA code via email
            // sendVerificationEmail($email, $verificationCode);
            
            successResponse([
                'user_id' => $user['id'],
                'verification_code' => $verificationCode, // Remove in production
                'message' => 'Verification code sent to email'
            ], 'Verification required');
            break;
            
        case 'verify':
            requireParams(['user_id', 'code'], $input);
            
            $userId = $input['user_id'];
            $code = $input['code'];
            
            $user = $db->fetch("SELECT * FROM users WHERE id = ? AND verification_code = ?", [$userId, $code]);
            if (!$user) errorResponse('Invalid verification code');
            
            // Mark as verified and clear code
            $db->update("UPDATE users SET verified = 1, verification_code = NULL WHERE id = ?", [$userId]);
            
            // Generate token
            $token = generateToken($userId, $user['role']);
            unset($user['password'], $user['verification_code'], $user['reset_code']);
            
            successResponse([
                'token' => $token,
                'user' => $user
            ], 'Login successful');
            break;
            
        case 'verify-pin':
            requireParams(['pin'], $input);
            $user = requireAuth();
            
            if ($user['role'] !== 'owner') errorResponse('Owner access required', 403);
            
            $settings = $db->fetch("SELECT setting_value FROM settings WHERE setting_key = 'owner_pin'");
            if (!$settings || $input['pin'] !== $settings['setting_value']) {
                errorResponse('Invalid PIN');
            }
            
            successResponse(['verified' => true], 'PIN verified');
            break;
            
        case 'forgot-password':
            requireParams(['email'], $input);
            
            $email = strtolower(trim($input['email']));
            $user = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if (!$user) errorResponse('Email not found');
            
            $resetCode = generateCode();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $db->update("UPDATE users SET reset_code = ?, reset_code_expires = ? WHERE id = ?", 
                [$resetCode, $expires, $user['id']]);
            
            // In production, send reset email
            // sendPasswordResetEmail($email, $resetCode);
            
            successResponse([
                'reset_code' => $resetCode // Remove in production
            ], 'Reset code sent to email');
            break;
            
        case 'reset-password':
            requireParams(['email', 'code', 'new_password'], $input);
            
            $email = strtolower(trim($input['email']));
            $code = $input['code'];
            $newPassword = $input['new_password'];
            
            if (strlen($newPassword) < 6) errorResponse('Password must be at least 6 characters');
            
            $user = $db->fetch(
                "SELECT id FROM users WHERE email = ? AND reset_code = ? AND reset_code_expires > NOW()",
                [$email, $code]
            );
            if (!$user) errorResponse('Invalid or expired reset code');
            
            $db->update(
                "UPDATE users SET password = ?, reset_code = NULL, reset_code_expires = NULL WHERE id = ?",
                [hashPassword($newPassword), $user['id']]
            );
            
            successResponse(null, 'Password reset successful');
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
    $user = requireAuth();
    
    switch ($action) {
        case 'profile':
            $updates = [];
            $params = [];
            
            if (isset($input['name']) && trim($input['name'])) {
                $updates[] = 'name = ?';
                $params[] = trim($input['name']);
            }
            if (isset($input['phone'])) {
                if ($input['phone'] && !isValidPhone($input['phone'])) errorResponse('Invalid phone number');
                $updates[] = 'phone = ?';
                $params[] = $input['phone'] ?: null;
            }
            if (isset($input['dob'])) {
                $updates[] = 'dob = ?';
                $params[] = $input['dob'] ?: null;
            }
            if (isset($input['address'])) {
                $updates[] = 'address = ?';
                $params[] = $input['address'];
            }
            if (isset($input['location_lat']) && isset($input['location_lng'])) {
                $updates[] = 'location_lat = ?, location_lng = ?';
                $params[] = $input['location_lat'];
                $params[] = $input['location_lng'];
            }
            
            if (empty($updates)) errorResponse('No fields to update');
            
            $params[] = $user['id'];
            $db->update("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?", $params);
            
            $updatedUser = $db->fetch("SELECT id, name, email, phone, dob, address, location_lat, location_lng, role FROM users WHERE id = ?", [$user['id']]);
            successResponse($updatedUser, 'Profile updated');
            break;
            
        case 'password':
            requireParams(['current_password', 'new_password'], $input);
            
            if (!verifyPassword($input['current_password'], $user['password'])) {
                errorResponse('Current password is incorrect');
            }
            if (strlen($input['new_password']) < 6) {
                errorResponse('New password must be at least 6 characters');
            }
            
            $db->update("UPDATE users SET password = ? WHERE id = ?", [hashPassword($input['new_password']), $user['id']]);
            successResponse(null, 'Password changed');
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
    $user = requireAuth();
    
    switch ($action) {
        case 'account':
            // Check for active orders
            $activeOrders = $db->fetch(
                "SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status IN ('pending', 'preparing', 'out_for_delivery')",
                [$user['id']]
            );
            if ($activeOrders['count'] > 0) {
                errorResponse('Cannot delete account with active orders');
            }
            
            // Delete user (triggers will handle reviews, favorites, notifications)
            $db->delete("DELETE FROM users WHERE id = ?", [$user['id']]);
            
            successResponse(null, 'Account deleted successfully');
            break;
            
        case 'user':
            requireOwner();
            $id = getParam('id');
            if (!$id) errorResponse('User ID required');
            if ($id == $user['id']) errorResponse('Cannot delete your own account');
            
            $db->delete("DELETE FROM users WHERE id = ?", [$id]);
            successResponse(null, 'User deleted');
            break;
            
        case 'all-users':
            requireOwner();
            // Delete all non-owner users (triggers will handle their data)
            $db->delete("DELETE FROM users WHERE role != 'owner'");
            successResponse(null, 'All users deleted');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
