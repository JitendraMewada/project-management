<?php

include 'includes/header.php';
require_once 'config/roles.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data from database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // User not found, maybe deleted - log out or redirect
    session_destroy();
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate required fields
        if (empty($name) || empty($email)) {
            throw new Exception('Name and email are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }

        // Check if email is used by other user
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $user_id]);
        if ($checkStmt->rowCount() > 0) {
            throw new Exception('Email is already used by another account.');
        }

        // Handle profile image upload
        $profile_image = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_tmp = $_FILES['profile_image']['tmp_name'];
            $file_name = $_FILES['profile_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_ext, $allowed)) {
                throw new Exception('Invalid profile image type. Allowed: jpg, jpeg, png, gif.');
            }

            if ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                throw new Exception('Profile image must be less than 2MB.');
            }

            $new_file_name = uniqid('profile_', true) . '.' . $file_ext;
            $dest_path = $upload_dir . $new_file_name;

            if (!move_uploaded_file($file_tmp, $dest_path)) {
                throw new Exception('Failed to upload profile image.');
            }

            // Delete old image safely
            if ($user['profile_image'] && file_exists($user['profile_image'])) {
                @unlink($user['profile_image']);
            }

            $profile_image = $dest_path;
        }

        $params = [$name, $email, $phone, $address, $profile_image, $user_id];
        $password_sql = '';
        $update_password = false;

        // Handle password change if fields filled
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            // Make sure all password fields are filled
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('To change password, fill in all password fields.');
            }

            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Current password is incorrect.');
            }

            if ($new_password !== $confirm_password) {
                throw new Exception('New password and confirmation do not match.');
            }

            if (strlen($new_password) < 6) {
                throw new Exception('New password must be at least 6 characters.');
            }

            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $password_sql = ', password = ?';
            $params[] = $new_password_hash;

            $update_password = true;
        }

        $sql = "UPDATE users SET 
                    name = ?, 
                    email = ?, 
                    phone = ?, 
                    address = ?, 
                    profile_image = ? 
                    $password_sql
                WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // Refresh user data after update
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $message = 'Profile updated successfully.';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <h2><i class="fas fa-user-circle"></i> My Profile</h2>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?php if (!empty($user['profile_image'])): ?>
                    <div class="text-center mb-3">
                        <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile Image"
                            class="rounded-circle img-thumbnail" width="120" height="120">
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required
                                value="<?= htmlspecialchars($user['name']) ?>">
                            <div class="invalid-feedback">Please provide your full name.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required
                                value="<?= htmlspecialchars($user['email']) ?>">
                            <div class="invalid-feedback">Please provide a valid email address.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea id="address" name="address" class="form-control"
                            rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*"
                            class="form-control">
                        <small class="form-text text-muted">Upload a new image to replace the current one.</small>
                    </div>

                    <hr>

                    <h5>Change Password</h5>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control"
                                minlength="6">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control"
                                minlength="6">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap 5 validation and password confirmation validation
(function() {
    'use strict';
    const form = document.querySelector('.needs-validation');
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');

    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        // If any password field is filled, require all three and check match
        if (currentPassword.value || newPassword.value || confirmPassword.value) {
            if (!currentPassword.value || !newPassword.value || !confirmPassword.value) {
                event.preventDefault();
                event.stopPropagation();
                alert('To change password, please fill all password fields.');
            } else if (newPassword.value !== confirmPassword.value) {
                event.preventDefault();
                event.stopPropagation();
                alert('New password and confirmation do not match.');
            }
        }

        form.classList.add('was-validated');
    }, false);
})();
</script>

<?php include 'includes/footer.php'; ?>