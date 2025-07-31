<?php
header('Content-Type: application/json');
// Optional: Suppress warnings in production, but best to fix at the source
error_reporting(0);
// Start the session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Replace with real DB notification logic if needed!
echo json_encode([
    'success' => true,
    'notifications' => [
        ['id' => 1, 'message' => 'Test notification', 'type' => 'info', 'read' => false]
    ]
]);