<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../config/roles.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit();
}

$current_user = $auth->getCurrentUser();

// Check permission
if (!hasPermission($current_user['role'], 'documents', 'read')) {
    header("HTTP/1.0 403 Forbidden");
    exit();
}

$document_id = intval($_GET['id'] ?? 0);
if (!$document_id) {
    header("HTTP/1.0 404 Not Found");
    exit();
}

// Fetch document details
$query = "SELECT * FROM project_documents WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    header("HTTP/1.0 404 Not Found");
    exit();
}

// Update download count
$update_query = "UPDATE project_documents SET download_count = download_count + 1 WHERE id = ?";
$update_stmt = $db->prepare($update_query);
$update_stmt->execute([$document_id]);

$file_path = '../../' . $document['file_path'];

if (!file_exists($file_path)) {
    header("HTTP/1.0 404 Not Found");
    exit();
}

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $document['original_name'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Clean output buffer
ob_clean();
flush();

// Output file
readfile($file_path);
exit();
?>