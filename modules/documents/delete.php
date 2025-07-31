<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../config/roles.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$current_user = $auth->getCurrentUser();

// Check permission
if (!hasPermission($current_user['role'], 'documents', 'delete')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

$document_id = intval($_GET['id'] ?? 0);
if (!$document_id) {
    echo json_encode(['success' => false, 'message' => 'Document ID required']);
    exit();
}

try {
    // Fetch document details
    $query = "SELECT * FROM project_documents WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$document_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
        exit();
    }
    
    // Check if user has permission to delete this specific document
    if ($current_user['role'] != 'admin' && $current_user['role'] != 'manager' && 
        $document['uploaded_by'] != $current_user['id']) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit();
    }
    
    // Delete file from server
    $file_path = '../../' . $document['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete from database
    $delete_query = "DELETE FROM project_documents WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->execute([$document_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Document deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting document: ' . $e->getMessage()
    ]);
}
?>