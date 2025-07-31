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

if (!hasPermission($current_user['role'], 'documents', 'create')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $project_id = intval($_POST['project_id']);
    $category = $_POST['category'] ?? 'other';
    $description = trim($_POST['description'] ?? '');
    
    if (!$project_id) {
        throw new Exception('Project ID is required');
    }
    
    // Verify project exists and user has access
    $project_query = "SELECT id FROM projects WHERE id = ?";
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([$project_id]);
    
    if (!$project_stmt->fetch()) {
        throw new Exception('Project not found');
    }
    
    // Create upload directory
    $upload_dir = '../../uploads/documents/' . $project_id . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded_files = [];
    $allowed_types = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'bmp',
        'dwg', 'skp', 'rvt',
        'zip', 'rar', '7z',
        'txt', 'csv'
    ];
    
    $max_file_size = 10 * 1024 * 1024; // 10MB
    
    foreach ($_FILES['files']['name'] as $key => $original_name) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            $file_size = $_FILES['files']['size'][$key];
            $file_type = $_FILES['files']['type'][$key];
            $temp_name = $_FILES['files']['tmp_name'][$key];
            
            // Validate file size
            if ($file_size > $max_file_size) {
                throw new Exception("File {$original_name} is too large. Maximum size is 10MB.");
            }
            
            // Validate file type
            $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("File type {$file_extension} is not allowed for {$original_name}.");
            }
            
            // Generate unique filename
            $file_name = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            // Move uploaded file
            if (move_uploaded_file($temp_name, $file_path)) {
                // Save to database
                $insert_query = "INSERT INTO project_documents 
                                (project_id, file_name, original_name, file_path, file_size, file_type, category, description, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([
                    $project_id,
                    $file_name,
                    $original_name,
                    'uploads/documents/' . $project_id . '/' . $file_name,
                    $file_size,
                    $file_type,
                    $category,
                    $description,
                    $current_user['id']
                ]);
                
                $uploaded_files[] = [
                    'id' => $db->lastInsertId(),
                    'name' => $original_name,
                    'size' => $file_size
                ];
            } else {
                throw new Exception("Failed to upload {$original_name}.");
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => count($uploaded_files) . ' file(s) uploaded successfully',
        'files' => $uploaded_files
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>