<?php
header('Content-Type: application/json');
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . "/project-management/config/database.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/project-management/config/auth.php";

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user = $auth->getCurrentUser();
$user_id = $user['id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user session']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'check_in') {
        $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = CURDATE()");
        $stmt->execute([$user_id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already checked in today']);
            exit;
        }
        $stmt = $db->prepare("INSERT INTO attendance (user_id, date, check_in_time, status, created_at) VALUES (?, CURDATE(), NOW(), 'present', NOW())");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'Checked in successfully']);
    } elseif ($action === 'check_out') {
        $stmt = $db->prepare("SELECT id, check_in_time, check_out_time FROM attendance WHERE user_id = ? AND date = CURDATE()");
        $stmt->execute([$user_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'Please check in first']);
            exit;
        }
        if (!empty($record['check_out_time'])) {
            echo json_encode(['success' => false, 'message' => 'You have already checked out today']);
            exit;
        }
        $stmt = $db->prepare("UPDATE attendance SET check_out_time = NOW(), total_hours = TIMESTAMPDIFF(SECOND, check_in_time, NOW())/3600 WHERE id = ?");
        $stmt->execute([$record['id']]);
        echo json_encode(['success' => true, 'message' => 'Checked out successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $ex->getMessage()]);
}