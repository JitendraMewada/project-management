<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$error = '';
$success = '';

// Check if registration is enabled
$registration_enabled = true; // Set to false to disable public registration

if ($_POST) {
    try {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone = sanitizeInput($_POST['phone']);
        $company = sanitizeInput($_POST['company'] ?? '');
        
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('Please fill in all required fields.');
        }
        
        if (!isValidEmail($email)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long.');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }
        
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$email]);
        
        if ($check_stmt->rowCount() > 0) {
            throw new Exception('An account with this email address already exists.');
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        
        // Insert user (default role as 'designer' for public registration)
        $insert_query = "INSERT INTO users (name, email, password, phone, company, role, status, verification_token, created_at) 
                         VALUES (?, ?, ?, ?, ?, 'designer', 'active', ?, NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$name, $email, $hashed_password, $phone, $company, $verification_token]);
        
        $user_id = $db->lastInsertId();
        
        // Log activity
        logActivity($user_id, 'user_registered', 'User registered: ' . $email, $db);
        
        // Send verification email (if email service is configured)
        if (class_exists('EmailService')) {
            $emailService = new EmailService();
            $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/verify.php?token=" . $verification_token;
            
            $email_body = "
            <h2>Welcome to Interior Project Management!</h2>
            <p>Thank you for registering. Please click the link below to verify your email address:</p>
            <p><a href='$verification_link'>Verify Email Address</a></p>
            <p>If you didn't create this account, please ignore this email.</p>
            ";
            
            $emailService->sendEmail($email, 'Verify Your Email Address', $email_body);
        }
        
        $success = 'Registration successful! Please check your email to verify your account.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Interior Project Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
    }

    .register-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .register-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .form-floating label {
        color: #6c757d;
    }

    .btn-register {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .password-strength {
        height: 4px;
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .strength-weak {
        background: #dc3545;
        width: 25%;
    }

    .strength-fair {
        background: #ffc107;
        width: 50%;
    }

    .strength-good {
        background: #28a745;
        width: 75%;
    }

    .strength-strong {
        background: #20c997;
        width: 100%;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card">
                    <div class="register-header">
                        <h2><i class="fas fa-user-plus"></i> Create Account</h2>
                        <p class="mb-0">Join our Interior Project Management System</p>
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

                        <?php if (!$registration_enabled): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            Public registration is currently disabled. Please contact your administrator.
                        </div>
                        <?php else: ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                        <label for="name"><i class="fas fa-user"></i> Full Name</label>
                                        <div class="invalid-feedback">Please provide your full name.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                                        <div class="invalid-feedback">Please provide a valid email address.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="company" name="company"
                                            value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                                        <label for="company"><i class="fas fa-building"></i> Company (Optional)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" required
                                    minlength="8" onkeyup="checkPasswordStrength()">
                                <label for="password"><i class="fas fa-lock"></i> Password</label>
                                <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                                <div class="password-strength mt-1" id="passwordStrength"></div>
                                <small class="text-muted">Use at least 8 characters with letters, numbers, and
                                    symbols.</small>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required onkeyup="checkPasswordMatch()">
                                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                                <div class="invalid-feedback" id="passwordMatchFeedback">Passwords do not match.</div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms
                                        of Service</a>
                                    and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy
                                        Policy</a>
                                </label>
                                <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-register btn-primary text-white">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                            </div>
                        </form>

                        <?php endif; ?>
                        <?php endif; ?>

                        <div class="text-center">
                            <p class="mb-0">Already have an account?
                                <a href="login.php" class="text-decoration-none fw-bold">Sign In</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By accessing and using our Interior Project Management System, you accept and agree to be bound
                        by the terms and provision of this agreement.</p>

                    <h6>2. Use License</h6>
                    <p>Permission is granted to temporarily use our system for personal and commercial purposes. This is
                        the grant of a license, not a transfer of title.</p>

                    <h6>3. User Responsibilities</h6>
                    <p>Users are responsible for maintaining the confidentiality of their account credentials and for
                        all activities that occur under their account.</p>

                    <h6>4. Data Privacy</h6>
                    <p>We are committed to protecting your privacy and will handle your personal information in
                        accordance with our Privacy Policy.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthBar = document.getElementById('passwordStrength');

        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[^a-zA-Z0-9]+/)) strength++;

        strengthBar.className = 'password-strength mt-1';

        if (strength < 2) {
            strengthBar.classList.add('strength-weak');
        } else if (strength < 3) {
            strengthBar.classList.add('strength-fair');
        } else if (strength < 4) {
            strengthBar.classList.add('strength-good');
        } else {
            strengthBar.classList.add('strength-strong');
        }
    }

    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const feedback = document.getElementById('passwordMatchFeedback');
        const confirmField = document.getElementById('confirm_password');

        if (confirmPassword && password !== confirmPassword) {
            confirmField.classList.add('is-invalid');
            feedback.style.display = 'block';
        } else {
            confirmField.classList.remove('is-invalid');
            feedback.style.display = 'none';
        }
    }

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
    </script>
</body>

</html>