<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'projects', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$project_id = intval($_GET['id'] ?? 0);
if (!$project_id) {
    header("Location: list.php");
    exit();
}

// Fetch project details
$query = "SELECT p.*, u1.name as manager_name, u2.name as designer_name, u3.name as site_manager_name, u4.name as created_by_name
          FROM projects p 
          LEFT JOIN users u1 ON p.manager_id = u1.id 
          LEFT JOIN users u2 ON p.designer_id = u2.id 
          LEFT JOIN users u3 ON p.site_manager_id = u3.id 
          LEFT JOIN users u4 ON p.created_by = u4.id
          WHERE p.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: list.php");
    exit();
}

// Fetch project tasks
$tasks_query = "SELECT t.*, u.name as assigned_to_name FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.project_id = ? ORDER BY t.priority DESC, t.due_date ASC";
$tasks_stmt = $db->prepare($tasks_query);
$tasks_stmt->execute([$project_id]);
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
// Assume $project_id is defined and validated
$materials_query = "SELECT * FROM project_materials WHERE project_id = ? ORDER BY created_at DESC";
$materials_stmt = $db->prepare($materials_query);
$materials_stmt->execute([$project_id]);
$materials = $materials_stmt->fetchAll(PDO::FETCH_ASSOC);

// Example: print materials
foreach ($materials as $material) {
    echo htmlspecialchars($material['material_name']) . ' - Quantity: ' . $material['quantity'] . ' ' . htmlspecialchars($material['unit']) . '<br>';
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-eye"></i> Project Details</h2>
                    <div class="btn-group">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <?php if (hasPermission($current_user['role'], 'projects', 'update')): ?>
                        <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Project
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Overview -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo htmlspecialchars($project['name']); ?></h5>
                                <span class="badge bg-<?php 
                                    echo match($project['status']) {
                                        'completed' => 'success',
                                        'in_progress' => 'warning',
                                        'planning' => 'info',
                                        'on_hold' => 'secondary',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>"><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Project Information</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Type:</strong></td>
                                                <td><?php echo ucfirst($project['project_type']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Client:</strong></td>
                                                <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Email:</strong></td>
                                                <td><?php echo htmlspecialchars($project['client_email'] ?? 'Not provided'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Phone:</strong></td>
                                                <td><?php echo htmlspecialchars($project['client_phone'] ?? 'Not provided'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Budget:</strong></td>
                                                <td><?php echo $project['budget'] ? '₹' . number_format($project['budget'], 2) : 'Not set'; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Timeline & Team</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Start Date:</strong></td>
                                                <td><?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'Not set'; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>End Date:</strong></td>
                                                <td><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : 'Not set'; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Manager:</strong></td>
                                                <td><?php echo htmlspecialchars($project['manager_name'] ?? 'Unassigned'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Designer:</strong></td>
                                                <td><?php echo htmlspecialchars($project['designer_name'] ?? 'Unassigned'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Site Manager:</strong></td>
                                                <td><?php echo htmlspecialchars($project['site_manager_name'] ?? 'Unassigned'); ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <?php if ($project['description']): ?>
                                <hr>
                                <h6>Description</h6>
                                <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Project Progress</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar bg-<?php echo $project['progress'] < 30 ? 'danger' : ($project['progress'] < 70 ? 'warning' : 'success'); ?>"
                                        style="width: <?php echo $project['progress']; ?>%">
                                        <?php echo $project['progress']; ?>%
                                    </div>
                                </div>

                                <div class="row text-center">
                                    <div class="col-4">
                                        <h5 class="text-primary"><?php echo count($tasks); ?></h5>
                                        <small>Total Tasks</small>
                                    </div>
                                    <div class="col-4">
                                        <h5 class="text-success">
                                            <?php echo count(array_filter($tasks, fn($t) => $t['status'] == 'completed')); ?>
                                        </h5>
                                        <small>Completed</small>
                                    </div>
                                    <div class="col-4">
                                        <h5 class="text-warning">
                                            <?php echo count(array_filter($tasks, fn($t) => $t['status'] == 'in_progress')); ?>
                                        </h5>
                                        <small>In Progress</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="../tasks/create.php?project_id=<?php echo $project['id']; ?>"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add Task
                                    </a>
                                    <a href="../reports/create.php?project_id=<?php echo $project['id']; ?>"
                                        class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-alt"></i> Add Report
                                    </a>
                                    <button class="btn btn-outline-info btn-sm" onclick="printProject()">
                                        <i class="fas fa-print"></i> Print Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Tasks -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-tasks"></i> Project Tasks</h6>
                        <?php if (hasPermission($current_user['role'], 'tasks', 'create')): ?>
                        <a href="../tasks/create.php?project_id=<?php echo $project['id']; ?>"
                            class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add Task
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No tasks assigned to this project yet.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Assigned To</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                                        <td><?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned'); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                    echo match($task['priority']) {
                                                        'critical' => 'danger',
                                                        'high' => 'warning',
                                                        'medium' => 'info',
                                                        'low' => 'secondary',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo ucfirst($task['priority']); ?></span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-<?php 
                                                    echo match($task['status']) {
                                                        'completed' => 'success',
                                                        'in_progress' => 'warning',
                                                        'pending' => 'info',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span>
                                        </td>
                                        <td><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <a href="../tasks/view.php?id=<?php echo $task['id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Materials -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-boxes"></i> Project Materials</h6>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#addMaterialModal">
                            <i class="fas fa-plus"></i> Add Material
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($materials)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No materials added to this project yet.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $total_cost = 0;
                                        foreach ($materials as $material): 
                                            $total_cost += $material['total_price'];
                                        ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                                        <td><?php echo $material['quantity'] . ' ' . $material['unit']; ?></td>
                                        <td><?php echo $material['unit_price'] ? '₹' . number_format($material['unit_price'], 2) : '-'; ?>
                                        </td>
                                        <td><?php echo $material['total_price'] ? '₹' . number_format($material['total_price'], 2) : '-'; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($material['supplier'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                    echo match($material['status']) {
                                                        'received' => 'success',
                                                        'ordered' => 'warning',
                                                        'required' => 'info',
                                                        'used' => 'secondary',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo ucfirst($material['status']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-info fw-bold">
                                        <td colspan="3">Total Material Cost</td>
                                        <td>₹<?php echo number_format($total_cost, 2); ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMaterialForm">
                <div class="modal-body">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Material Name</label>
                        <input type="text" class="form-control" name="material_name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control" name="unit" required
                                    placeholder="e.g., pieces, kg, meters">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Price (₹)</label>
                        <input type="number" class="form-control" name="unit_price" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <input type="text" class="form-control" name="supplier">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function printProject() {
    window.print();
}

// Add material form submission
document.getElementById('addMaterialForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('add_material.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.