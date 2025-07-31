<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'users', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    header("Location: list.php");
    exit();
}

// Fetch user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: list.php");
    exit();
}

// Fetch user's projects
$projects_query = "SELECT p.*, 
                   CASE 
                     WHEN p.manager_id = ? THEN 'Manager'
                     WHEN p.designer_id = ? THEN 'Designer'
                     WHEN p.site_manager_id = ? THEN 'Site Manager'
                     ELSE 'Unknown'
                   END as user_role_in_project
                   FROM projects p 
                   WHERE p.manager_id = ? OR p.designer_id = ? OR p.site_manager_id = ?
                   ORDER BY p.created_at DESC";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's tasks
$tasks_query = "SELECT t.*, p.name as project_name FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                WHERE t.assigned_to = ? OR t.assigned_by = ?
                ORDER BY t.created_at DESC LIMIT 10";
$tasks_stmt = $db->prepare($tasks_query);
$tasks_stmt->execute([$user_id, $user_id]);
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate user statistics
$total_projects = count($projects);
$completed_projects = count(array_filter($projects, fn($p) => $p['status'] == 'completed'));
$assigned_tasks = count(array_filter($tasks, fn($t) => $t['assigned_to'] == $user_id));
$completed_tasks = count(array_filter($tasks, fn($t) => $t['assigned_to'] == $user_id && $t['status'] == 'completed'));
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-user"></i> User Profile</h2>
                    <div class="btn-group">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <?php if (hasPermission($current_user['role'], 'users', 'update')): ?>
                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit User
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Profile Card -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <?php if ($user['profile_image']): ?>
                                    <img src="../../<?php echo htmlspecialchars($user['profile_image']); ?>"
                                        class="rounded-circle img-thumbnail" width="150" height="150" alt="Profile">
                                    <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto"
                                        style="width: 150px; height: 150px; font-size: 48px;">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>

                                <span class="badge bg-<?php 
                                    echo match($user['role']) {
                                        'admin' => 'danger',
                                        'manager' => 'primary',
                                        'designer' => 'success',
                                        'site_manager' => 'warning',
                                        'site_coordinator' => 'info',
                                        'site_supervisor' => 'secondary',
                                        default => 'secondary'
                                    };
                                ?> fs-6 mb-3"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>

                                <div class="mt-3">
                                    <span
                                        class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?> fs-6">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>

                                <div class="mt-3">
                                    <?php if ($user['phone']): ?>
                                    <a href="tel:<?php echo $user['phone']; ?>"
                                        class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fas fa-phone"></i> Call
                                    </a>
                                    <?php endif; ?>
                                    <a href="mailto:<?php echo $user['email']; ?>"
                                        class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-envelope"></i> Email
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">User Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Full Name:</strong></td>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Email:</strong></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Phone:</strong></td>
                                                <td><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Role:</strong></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php 
                                                        echo match($user['role']) {
                                                            'admin' => 'danger',
                                                            'manager' => 'primary',
                                                            'designer' => 'success',
                                                            'site_manager' => 'warning',
                                                            'site_coordinator' => 'info',
                                                            'site_supervisor' => 'secondary',
                                                            default => 'secondary'
                                                        };
                                                    ?>"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status:</strong></td>
                                                <td>
                                                    <span