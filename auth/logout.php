<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Log logout activity
if ($auth->isLoggedIn()) {
    $current_user = $auth->getCurrentUser();
    logActivity($current_user['id'], 'user_logout', 'User logged out', $db);
    
    // Update last activity
    $update_query = "UPDATE users SET last_activity = NOW() WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$current_user['id']]);
}

// Perform logout
$auth->logout();

// Redirect to login page with success message
header("Location: project-management/auth/login.php?logged_out=1");
exit();
?>