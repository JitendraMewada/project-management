<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission (only authorized roles can manage users)
if (!hasPermission($current_user['role'], 'users', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    <?php if (hasPermission($current_user['role'], 'users', 'create')): ?>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add User
                    </a>
                    <?php endif; ?>
                </div>

                <!-- User Statistics -->
                <div class="row mb-4">
                    <?php 
                    $role_counts = [];
                    foreach ($users as $user) {
                        $role_counts[$user['role']] = ($role_counts[$user['role']] ?? 0) + 1;
                    }
                    ?>
                    <div class="col-md-2">
                        <div class="card role-admin text-center">
                            <div class="card-body">
                                <h4><?php echo $role_counts['admin'] ?? 0; ?></h4>
                                <p>Admins</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card role-manager text-center">
                            <div class="card-body">
                                <h4><?php echo $role_counts['manager'] ?? 0; ?></h4>
                                <p>Managers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card role-designer text-center">
                            <div class="card-body">
                                <h4><?php echo $role_counts['designer'] ?? 0; ?></h4>
                                <p>Designers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card role-site-manager text-center">
                            <div class="card-body">
                                <h4><?php echo $role_counts['site_manager'] ?? 0; ?></h4>
                                <p>Site Managers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card role-site-coordinator text-center">
                            <div class="card-body">
                                <h4><?php echo $role_counts['site_coordinator'] ?? 0; ?></h4>
                                <p>Coordinators</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card role-site-supervisor text-center">
                            <div class="card-body">
                                <h4><?php echo $role_counts['site_supervisor'] ?? 0; ?></h4>
                                <p>Supervisors</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-3 d-flex gap-2 justify-content-end flex-wrap">
                    <select class="form-control w-auto" id="roleFilter">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="designer">Designer</option>
                        <option value="site_manager">Site Manager</option>
                        <option value="site_coordinator">Site Coordinator</option>
                        <option value="site_supervisor">Site Supervisor</option>
                    </select>
                    <input type="text" class="form-control w-auto table-search"
                        placeholder="Search users by name or email">
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php if ($user['profile_image']): ?>
                                                <img src="../../<?php echo htmlspecialchars($user['profile_image']); ?>"
                                                    class="rounded-circle" width="40" height="40" alt="Avatar">
                                                <?php else: ?>
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px;">
                                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                <?php if ($user['id'] == $current_user['id']): ?>
                                                <span class="badge bg-info ms-1">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
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
                                        ?>"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></td>
                                    <td>
                                        <span
                                            class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view.php?id=<?php echo $user['id']; ?>"
                                                class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (hasPermission($current_user['role'], 'users', 'update')): ?>
                                            <a href="edit.php?id=<?php echo $user['id']; ?>"
                                                class="btn btn-outline-success" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (hasPermission($current_user['role'], 'users', 'delete') && $user['id'] != $current_user['id']): ?>
                                            <a href="delete.php?id=<?php echo $user['id']; ?>"
                                                class="btn btn-outline-danger delete-btn" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Role Filter
document.getElementById('roleFilter').addEventListener('change', function() {
    const selectedRole = this.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');

    rows.forEach(row => {
        const roleCellText = row.cells[2].textContent.trim().toLowerCase();
        // Normalize for role display vs value (underscores vs spaces)
        const normalizedRoleCellText = roleCellText.replace(/\s+/g, '_');
        if (selectedRole === '' || normalizedRoleCellText === selectedRole) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Search Filter (by name or email)
document.querySelector('.table-search').addEventListener('input', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');

    rows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const email = row.cells[1].textContent.toLowerCase();
        if (name.includes(searchValue) || email.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Delete Confirmation
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>