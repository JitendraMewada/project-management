<?php
include '../../includes/header.php';
require_once '../../includes/functions.php';
require_once '../../config/roles.php';
var_dump($sql);
var_dump($params);
die();

// Permission check
if (!hasPermission($current_user['role'], 'users', 'update')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit;
}

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    header("Location: list.php");
    exit;
}

// Fetch user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: list.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect & sanitize input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $status = $_POST['status'] ?? 'inactive';

        // Validate mandatory fields
        if ($name === '' || $email === '' || $role === '') {
            throw new Exception('Please fill in all required fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        // Check email uniqueness excluding current user
        $stmtCheckEmail = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCheckEmail->execute([$email, $user_id]);
        if ($stmtCheckEmail->rowCount() > 0) {
            throw new Exception('Email address is already in use by another user.');
        }

        // Profile image handling
        $profile_image = $user['profile_image']; // default: current image
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_tmp = $_FILES['profile_image']['tmp_name'];
            $file_name = $_FILES['profile_image']['name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception('Invalid image type. Allowed: jpg, jpeg, png, gif.');
            }

            if ($file_size > 2 * 1024 * 1024) {
                throw new Exception('Profile image must be less than 2MB.');
            }

            $new_filename = uniqid('profile_', true) . '.' . $file_ext;
            $dest_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($file_tmp, $dest_path)) {
                throw new Exception('Failed to upload profile image.');
            }

            // Delete old image if exists and not default
            if ($user['profile_image'] && file_exists('../../' . $user['profile_image'])) {
                @unlink('../../' . $user['profile_image']);
            }

            $profile_image = 'uploads/profiles/' . $new_filename;
        }

        // Prepare SQL and parameters
        $params = [$name, $email, $role, $phone, $address, $profile_image, $status];

        $password_sql = '';
        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($password !== $confirm_password) {
                throw new Exception('Passwords do not match.');
            }
            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters.');
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $password_sql = ', password = ?';
            $params[] = $hashed_password;
        }

        // Append user ID for WHERE clause
        $params[] = $user_id;

        // Final SQL query with dynamic password update
        $sql = "UPDATE users SET 
                    name = ?, 
                    email = ?, 
                    role = ?, 
                    phone = ?, 
                    address = ?, 
                    profile_image = ?, 
                    status = ?, 
                    updated_at = NOW() 
                $password_sql
                WHERE id = ?";

        $stmtUpdate = $db->prepare($sql);
        $stmtUpdate->execute($params);

        // Reload updated user data
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $message = 'User updated successfully.';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
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
                    <h2><i class="fas fa-user-edit"></i> Edit User</h2>
                    <div class="btn-group">
                        <a href="view.php?id=<?= htmlspecialchars($user['id']) ?>" class="btn btn-secondary"
                            title="View Profile">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="list.php" class="btn btn-outline-secondary" title="Back to List">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?php if ($user['profile_image']): ?>
                    <div class="text-center mb-4">
                        <img src="../../<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile Image"
                            class="rounded-circle img-thumbnail" style="width:100px; height:100px;">
                        <p class="text-muted mt-2">Current profile image</p>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required
                                value="<?= htmlspecialchars($user['name']) ?>">
                            <div class="invalid-feedback">Please enter full name.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required
                                value="<?= htmlspecialchars($user['email']) ?>">
                            <div class="invalid-feedback">Please enter valid email.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" id="password" name="password" class="form-control" minlength="6"
                                placeholder="Leave blank to keep current password">
                            <div class="form-text">Leave blank if you do not want to change password.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                minlength="6" placeholder="Confirm new password">
                            <div class="invalid-feedback" id="password-match-error">Passwords do not match.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select id="role" name="role" class="form-select" required>
                                <?php
                                $roles = ['admin'=>'Admin','manager'=>'Manager','designer'=>'Designer','site_manager'=>'Site Manager','site_coordinator'=>'Site Coordinator','site_supervisor'=>'Site Supervisor'];
                                foreach ($roles as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($user['role'] === $key) ? 'selected' : '' ?>>
                                    <?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active" <?= ($user['status'] === 'active') ? 'selected' : '' ?>>Active
                                </option>
                                <option value="inactive" <?= ($user['status'] === 'inactive') ? 'selected' : '' ?>>
                                    Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" class="form-control"
                                accept="image/*">
                            <small class="form-text text-muted">Uploading new image replaces the old one.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea id="address" name="address" class="form-control"
                            rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <?php if ($user['id'] !== $current_user['id']): ?>
                    <div class="alert alert-warning">
                        <strong>Role Change Warning:</strong> Changing this user's role will immediately affect
                        permissions.
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const form = document.querySelector('form.needs-validation');
    const password = form.querySelector('#password');
    const confirm_password = form.querySelector('#confirm_password');
    const errorElem = document.getElementById('password-match-error');

    form.addEventListener('submit', function(event) {
        // Native validation
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Password match validation
        if (password.value !== confirm_password.value) {
            confirm_password.setCustomValidity("Passwords do not match.");
            confirm_password.classList.add('is-invalid');
            errorElem.style.display = 'block';
            event.preventDefault();
            event.stopPropagation();
        } else {
            confirm_password.setCustomValidity("");
            confirm_password.classList.remove('is-invalid');
            errorElem.style.display = 'none';
        }

        form.classList.add('was-validated');
    }, false);

    // Clear error on input
    confirm_password.addEventListener('input', function() {
        if (password.value === confirm_password.value) {
            confirm_password.setCustomValidity("");
            confirm_password.classList.remove('is-invalid');
            errorElem.style.display = 'none';
        }
    });
})();
</script>

<?php include '../../includes/footer.php'; ?>