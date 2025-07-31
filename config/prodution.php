<?php
// Production configuration for Interior Project Management System

// Environment settings
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('DISPLAY_ERRORS', false);

// Security settings
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Database configuration for production
class ProductionDatabase extends Database {
    protected $host = 'your-production-host';
    protected $db_name = 'interior_project_management';
    protected $username = 'your-db-username';
    protected $password = 'your-secure-db-password';
    protected $port = 3306;
    
    public function getConnection() {
        if ($this->conn == null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
                        PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem'
                    ]
                );
            } catch(PDOException $exception) {
                error_log("Database connection error: " . $exception->getMessage());
                die("Service temporarily unavailable. Please try again later.");
            }
        }
        return $this->conn;
    }
}

// Email configuration for production
class ProductionEmailService extends EmailService {
    protected function configureSMTP() {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.your-domain.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'noreply@your-domain.com';
            $this->mailer->Password = 'your-smtp-password';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            
            $this->mailer->setFrom('noreply@your-domain.com', 'Interior Project Management');
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }
}

// File upload settings
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('UPLOAD_PATH', '/var/www/uploads/');
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'dwg', 'skp']);

// Security headers
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; img-src \'self\' data:; font-src \'self\' cdn.jsdelivr.net');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Error logging
function logError($message, $file = '', $line = '') {
    $logMessage = date('Y-m-d H:i:s') . " - Error: $message";
    if ($file) $logMessage .= " in $file";
    if ($line) $logMessage .= " on line $line";
    
    error_log($logMessage, 3, '/var/log/interior-pms/error.log');
}

// Performance monitoring
function logPerformance($operation, $duration, $memory_usage) {
    $logMessage = date('Y-m-d H:i:s') . " - Performance: $operation took {$duration}ms, Memory: {$memory_usage}MB";
    error_log($logMessage, 3, '/var/log/interior-pms/performance.log');
}

// Set error handlers
set_error_handler(function($severity, $message, $file, $line) {
    logError($message, $file, $line);
});

set_exception_handler(function($exception) {
    logError($exception->getMessage(), $exception->getFile(), $exception->getLine());
    if (!DEBUG_MODE) {
        header('HTTP/1.1 500 Internal Server Error');
        include 'error_pages/500.html';
        exit;
    }
});
?>