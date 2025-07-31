<?php
include '../../includes/header.php';
require_once '../../config/roles.php';

// Only allow admin users to access settings
if ($current_user['role'] !== 'admin') {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$message = '';
$error = '';

// Below is an example of simple site settings stored in a database table `settings`
// with columns: `key` (varchar), `value` (text).
// You can adapt this to your actual config setup.

// Database connection assumed via $db

// Handle form submission to update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Example settings keys
    $settings_keys = ['site_name', 'site_email', 'timezone', 'items_per_page'];

    try {
        foreach ($settings_keys as $key) {
            $val = $_POST[$key] ?? '';
            $val = trim($val);

            // Check if setting exists
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key` = ?");
            $checkStmt->execute([$key]);
            $exists = $checkStmt->fetchColumn() > 0;

            if ($exists) {
                $updateStmt = $db->prepare("UPDATE settings SET `value` = ? WHERE `key` = ?");
                $updateStmt->execute([$val, $key]);
            } else {
                $insertStmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
                $insertStmt->execute([$key, $val]);
            }
        }
        $message = "Settings updated successfully.";
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Load current settings values
$settings = [];
$stmt = $db->query("SELECT `key`, `value` FROM settings");
if ($stmt) {
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['key']] = $row['value'];
    }
}

// Set some default values if empty
$settings['site_name'] = $settings['site_name'] ?? 'Interior Project Management';
$settings['site_email'] = $settings['site_email'] ?? 'admin@interior-pms.com';
$settings['timezone'] = $settings['timezone'] ?? 'Asia/Kolkata';
$settings['items_per_page'] = $settings['items_per_page'] ?? '20';

// Get list of PHP supported timezones
$timezones = timezone_identifiers_list();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <h2><i class="fas fa-cogs"></i> System Settings</h2>
                <p class="text-muted mb-4">Update your system's configuration options here.</p>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-control"
                            value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                        <div class="invalid-feedback">Please enter the site name.</div>
                    </div>

                    <div class="mb-3">
                        <label for="site_email" class="form-label">Admin Email</label>
                        <input type="email" id="site_email" name="site_email" class="form-control"
                            value="<?= htmlspecialchars($settings['site_email']) ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="mb-3">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select id="timezone" name="timezone" class="form-select" required>
                            <?php foreach ($timezones as $tz): ?>
                            <option value="<?= htmlspecialchars($tz) ?>"
                                <?= ($settings['timezone'] == $tz) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tz) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a timezone.</div>
                    </div>

                    <div class="mb-3">
                        <label for="items_per_page" class="form-label">Items Per Page</label>
                        <input type="number" id="items_per_page" name="items_per_page" class="form-control" min="5"
                            max="100" value="<?= (int)$settings['items_per_page'] ?>" required>
                        <div class="invalid-feedback">Please enter a number between 5 and 100.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap form validation
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../../includes/footer.php'; ?>