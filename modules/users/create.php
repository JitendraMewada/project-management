<?php





include '../../includes/header.php';
require_once '../../config/roles.php';





// Only allow users with create permission
if (!hasPermission($current_user['role'], 'users', 'create')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect and sanitize inputs
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $status = $_POST['status'] ?? 'active';

        // Validate required fields
        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            throw new Exception('Please fill all required fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters.');
        }

        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }

        // Check email uniqueness
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->rowCount() > 0) {
            throw new Exception('Email address already exists.');
        }

        // Handle profile image upload
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $fileTmp = $_FILES['profile_image']['tmp_name'];
            $fileName = $_FILES['profile_image']['name'];
            $fileSize = $_FILES['profile_image']['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($fileExt, $allowed)) {
                throw new Exception('Invalid image file type. Allowed: jpg, jpeg, png, gif.');
            }

            if ($fileSize > 2 * 1024 * 1024) {
                throw new Exception('Profile image size must be less than 2MB.');
            }

            $newFileName = uniqid('profile_', true) . '.' . $fileExt;
            $destination = $upload_dir . $newFileName;

            if (!move_uploaded_file($fileTmp, $destination)) {
                throw new Exception('Failed to upload profile image.');
            }

            $profile_image = 'uploads/profiles/' . $newFileName;
        }

        // Hash the password securely
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        $insertSql = "INSERT INTO users (name, email, password, role, phone, address, profile_image, status, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $db->prepare($insertSql);
        $stmt->execute([
            $name,
            $email,
            $password_hash,
            $role,
            $phone,
            $address,
            $profile_image,
            $status
        ]);

        // Redirect on success with message
        header("Location: list.php?success=1");
        exit;

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
                    <h2><i class="fas fa-user-plus"></i> Create New User</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif (isset($_GET['success'])): ?>
                <div class="alert alert-success">User created successfully.</div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            <div class="invalid-feedback">Please enter full name.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" id="password" name="password" class="form-control" required
                                minlength="6">
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                required minlength="6">
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="">Select role</option>
                                <?php
                                $roles = [
                                    'admin' => 'Admin',
                                    'manager' => 'Manager',
                                    'designer' => 'Designer',
                                    'site_manager' => 'Site Manager',
                                    'site_coordinator' => 'Site Coordinator',
                                    'site_supervisor' => 'Site Supervisor'
                                ];
                                $selectedRole = $_POST['role'] ?? '';
                                foreach ($roles as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"
                                    <?= ($selectedRole === $key) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <?php $statusVal = $_POST['status'] ?? 'active'; ?>
                                <option value="active" <?= ($statusVal === 'active') ? 'selected' : '' ?>>Active
                                </option>
                                <option value="inactive" <?= ($statusVal === 'inactive') ? 'selected' : '' ?>>Inactive
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" class="form-control"
                                rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image" class="form-control"
                            accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary">Save User</button>
                    <a href="list.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap validation and password confirmation check
(function() {
    'use strict';
    const form = document.querySelector('.needs-validation');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (passwordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.setCustomValidity('Passwords do not match.');
            event.preventDefault();
            event.stopPropagation();
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
        form.classList.add('was-validated');
    });
})();
</script>

<?php include '../../includes/footer.php'; ?>