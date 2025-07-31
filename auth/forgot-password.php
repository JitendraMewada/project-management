<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';
$step = $_GET['step'] ?? 'request';
$token = $_GET['token'] ?? '';

if ($_POST) {
    if ($step === 'request') {
        // Step 1: Send reset link
        try {
            $email = sanitizeInput($_POST['email']);
            
            if (!isValidEmail($email)) {
                throw new Exception('Please enter a valid email address.');
            }
            
            // Check if user exists
            $user_query = "SELECT id, name FROM users WHERE email = ? AND status = 'active'";
            $user_stmt = $db->prepare($user_query);
            $user_stmt->execute([$email]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save reset token
                $token_query = "INSERT INTO password_resets (user_id, token, expires_at, created_at) 
                                VALUES (?, ?, ?, NOW()) 
                                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?, created_at = NOW()";
                $token_stmt = $db->prepare($token_query);
                $token_stmt->execute([$user['id'], $reset_token, $expires_at, $reset_token, $expires_at]);
                
                // Send reset email
                if (class_exists('EmailService')) {
                    $emailService = new EmailService();
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/forgot-password.php?step=reset&token=" . $reset_token;
                    
                    $email_body = "
                    <h2>Password Reset Request</h2>
                    <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                    <p>You have requested to reset your password. Click the link below to reset it:</p>
                    <p><a href='$reset_link'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                    ";
                    
                    $emailService->sendEmail($email, 'Password Reset Request', $email_body);
                }
                
                logActivity($user['id'], 'password_reset_requested', 'Password reset requested for: ' . $email, $db);
            }
            
            // Always show success message for security
            $success = 'If an account with that email exists, we have sent a password reset link.';
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
    } elseif ($step === 'reset') {
        // Step 2: Reset password
        try {
            $new_password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($new_password) || empty($confirm_password)) {
                throw new Exception('Please fill in all fields.');
            }
            
            if (strlen($new_password) < 8) {
                throw new Exception('Password must be at least 8 characters long.');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('Passwords do not match.');
            }
            
            // Verify token
            $token_query = "SELECT pr.user_id, u.name, u.email 
                            FROM password_resets pr 
                            JOIN users u ON pr.user_id = u.id 
                            WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0";
            $token_stmt = $db->prepare($token_query);
            $token_stmt->execute([$token]);
            $reset_data = $token_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset_data) {
                throw new Exception('Invalid or expired reset token.');
            }
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$hashed_password, $reset_data['user_id']]);
            
            // Mark token as used
            $mark_used_query = "UPDATE password_resets SET used = 1 WHERE token = ?";
            $mark_used_stmt = $db->prepare($mark_used_query);
            $mark_used_stmt->execute([$token]);
            
            logActivity($reset_data['user_id'], 'password_reset_completed', 'Password was reset', $db);
            
            $success = 'Your password has been reset successfully. You can now log in with your new password.';
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Verify token for reset step
if ($step === 'reset' && $token) {
    $token_query = "SELECT pr.user_id, u.email 
                    FROM password_resets pr 
                    JOIN users u ON pr.user_id = u.id 
                    WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0";
    $token_stmt = $db->prepare($token_query);
    $token_stmt->execute([$token]);
    $reset_data = $token_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reset_data) {
        $error = 'Invalid or expired reset token.';
        $step = 'request';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $step === 'reset' ? 'Reset Password' : 'Forgot Password'; ?> - Interior Project Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
    }

    .forgot-password-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .forgot-password-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .btn-reset {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        font-weight: 500;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="forgot-password-card">
                    <div class="forgot-password-header">
                        <h2><i class="fas fa-key"></i>
                            <?php echo $step === 'reset' ? 'Reset Password' : 'Forgot Password'; ?>
                        </h2>
                        <p class="mb-0">
                            <?php echo $step === 'reset' ? 'Enter your new password' : 'Enter your email to receive reset instructions'; ?>
                        </p>
                    </div>

                    <div class="p-4">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <hr>
                            <a href="login.php" class="btn btn-success">Go to Login</a>
                        </div>
                        <?php else: ?>

                        <?php if ($step === 'request'): ?>
                        <!-- Request Reset Form -->
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-reset btn-primary text-white">
                                    <i class="fas fa-paper-plane"></i> Send Reset Link
                                </button>
                            </div>
                        </form>

                        <?php else: ?>
                        <!-- Reset Password Form -->
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" required
                                    minlength="8">
                                <label for="password"><i class="fas fa-lock"></i> New Password</label>
                                <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required>
                                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                                <div class="invalid-feedback">Please confirm your password.</div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-reset btn-primary text-white">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <?php endif; ?>

                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left"></i> Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Bootstrap form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    // Password confirmation validation
    if (document.getElementById('confirm_password')) {
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (password !== confirmPassword && confirmPassword !== '') {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    </script>
</body>

</html>