<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_POST) {
    try {
        $project_id = intval($_POST['project_id']);
        $material_name = trim($_POST['material_name']);
        $quantity = floatval($_POST['quantity']);
        $unit = trim($_POST['unit']);
        $unit_price = !empty($_POST['unit_price']) ? floatval($_POST['unit_price']) : null;
        $supplier = trim($_POST['supplier']);
        
        // Calculate total price
        $total_price = $unit_price ? ($quantity * $unit_price) : null;
        
        // Validation
        if (empty($material_name) || $quantity <= 0 || empty($unit)) {
            throw new Exception('Please provide valid material details.');
        }
        
        // Insert material
        $query = "INSERT INTO project_materials (project_id, material_name, quantity, unit, unit_price, total_price, supplier) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$project_id, $material_name, $quantity, $unit, $unit_price, $total_price, $supplier]);
        
        echo json_encode(['success' => true, 'message' => 'Material added successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>