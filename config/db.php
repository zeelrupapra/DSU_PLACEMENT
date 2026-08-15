<?php
// Dr. Subhash Placement Portal - Database Configuration & Helpers
// Set Executive Security HTTP Headers
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dr_subhash_placement');

function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        
        // Auto-check and create tables if database was reset
        ensureSchemaExists($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        die("Database Connection Error: " . $e->getMessage());
    }
}

function ensureSchemaExists($pdo) {
    try {
        $tableExists = false;
        try {
            $pdo->query("SELECT 1 FROM users LIMIT 1");
            $tableExists = true;
        } catch (Exception $ex) {
            $tableExists = false;
        }

        if (!$tableExists) {
            // Clean orphaned .ibd files if InnoDB tablespace corruption occurred
            $dataDirs = [
                'd:/xampp/mysql/data/' . DB_NAME,
                'c:/xampp/mysql/data/' . DB_NAME,
                'C:/xampp/mysql/data/' . DB_NAME
            ];
            foreach ($dataDirs as $dDir) {
                if (is_dir($dDir)) {
                    $ibdFiles = glob($dDir . '/*.ibd');
                    if ($ibdFiles) {
                        foreach ($ibdFiles as $ibd) {
                            @unlink($ibd);
                        }
                    }
                }
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $tables = ['mail_logs', 'mou_agreements', 'mous', 'internships', 'workshop_registrations', 'workshops', 'applications', 'drives', 'companies', 'students', 'users', 'settings'];
            foreach ($tables as $tbl) {
                @$pdo->exec("DROP TABLE IF EXISTS `$tbl`");
            }

            $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `students` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `enrollment_no` VARCHAR(20) NOT NULL UNIQUE,
                `full_name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(100) NOT NULL UNIQUE,
                `phone` VARCHAR(20) DEFAULT NULL,
                `gender` ENUM('Male', 'Female') DEFAULT 'Male',
                `branch` VARCHAR(50) NOT NULL,
                `cpi` DECIMAL(4,2) DEFAULT 0.00,
                `backlogs` INT DEFAULT 0,
                `batch_year` INT NOT NULL,
                `resume_file` VARCHAR(255) DEFAULT 'default_resume.pdf',
                `offer_letter` VARCHAR(255) DEFAULT NULL,
                `placed_companies` VARCHAR(255) DEFAULT NULL,
                `profile_photo` VARCHAR(255) DEFAULT 'default_avatar.png',
                `placement_status` ENUM('In-Process', 'Placed', 'Unplaced') DEFAULT 'Unplaced',
                `is_detained` TINYINT(1) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `companies` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `company_name` VARCHAR(150) NOT NULL UNIQUE,
                `hr_name` VARCHAR(100) DEFAULT NULL,
                `hr_email` VARCHAR(100) DEFAULT NULL,
                `industry` VARCHAR(100) DEFAULT 'IT & Software',
                `website` VARCHAR(150) DEFAULT NULL,
                `phone` VARCHAR(20) DEFAULT NULL,
                `logo` VARCHAR(255) DEFAULT 'default_company.png',
                `is_closed` TINYINT(1) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `drives` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `company_id` INT NOT NULL,
                `title` VARCHAR(200) NOT NULL,
                `designation` VARCHAR(150) NOT NULL,
                `package_ctc` VARCHAR(50) NOT NULL,
                `location` VARCHAR(100) DEFAULT 'Pan India / Remote',
                `min_cpi` DECIMAL(4,2) DEFAULT 6.50,
                `allowed_branches` VARCHAR(255) DEFAULT 'CSE, IT, ECE, MECH, CIVIL',
                `max_backlogs` INT DEFAULT 0,
                `drive_date` DATE NOT NULL,
                `deadline` DATE NOT NULL,
                `deadline_time` VARCHAR(20) DEFAULT '18:00',
                `description` TEXT DEFAULT NULL,
                `status` ENUM('Upcoming', 'Active', 'Completed', 'Cancelled') DEFAULT 'Active',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `applications` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `drive_id` INT NOT NULL,
                `student_id` INT NOT NULL,
                `status` ENUM('Applied', 'Shortlisted', 'Interview Round', 'Selected', 'Rejected') DEFAULT 'Applied',
                `remarks` TEXT DEFAULT NULL,
                `applied_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_app` (`drive_id`, `student_id`),
                FOREIGN KEY (`drive_id`) REFERENCES `drives`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `workshops` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(200) NOT NULL,
                `speaker` VARCHAR(150) NOT NULL,
                `event_date` DATETIME NOT NULL,
                `venue` VARCHAR(150) NOT NULL,
                `capacity` INT DEFAULT 100,
                `description` TEXT DEFAULT NULL,
                `report_file` VARCHAR(255) DEFAULT NULL,
                `status` ENUM('Upcoming', 'Completed', 'Cancelled') DEFAULT 'Upcoming',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `workshop_registrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `workshop_id` INT NOT NULL,
                `student_id` INT NOT NULL,
                `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `attendance_status` ENUM('Registered', 'Attended', 'Absent') DEFAULT 'Registered',
                `report_file` VARCHAR(255) DEFAULT NULL,
                UNIQUE KEY `unique_wk_reg` (`workshop_id`, `student_id`),
                FOREIGN KEY (`workshop_id`) REFERENCES `workshops`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `internships` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `student_id` INT NOT NULL,
                `company_name` VARCHAR(150) NOT NULL,
                `title` VARCHAR(150) NOT NULL,
                `stipend` VARCHAR(50) DEFAULT '₹15,000 / month',
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `certificate_file` VARCHAR(255) DEFAULT 'certificate_sample.pdf',
                `status` ENUM('Ongoing', 'Completed') DEFAULT 'Completed',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `mou_agreements` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `company_name` VARCHAR(150) NOT NULL,
                `signing_date` DATE NOT NULL,
                `validity_years` INT NOT NULL DEFAULT 3,
                `expiry_date` DATE NOT NULL,
                `training_goals` TEXT DEFAULT NULL,
                `mou_doc_file` VARCHAR(255) DEFAULT 'mou_sample.pdf',
                `status` ENUM('Active', 'Expired', 'Pending') DEFAULT 'Active',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `mous` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `company_name` VARCHAR(150) NOT NULL,
                `signed_date` DATE NOT NULL,
                `expiry_date` DATE NOT NULL,
                `scope` TEXT DEFAULT NULL,
                `status` ENUM('Active', 'Expired', 'Pending') DEFAULT 'Active',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `mail_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `recipient` VARCHAR(100) NOT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `body` TEXT NOT NULL,
                `status` ENUM('Sent', 'Failed') DEFAULT 'Sent',
                `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `setting_key` VARCHAR(100) PRIMARY KEY,
                `setting_value` TEXT DEFAULT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
            $pdo->exec("INSERT IGNORE INTO `users` (`username`, `password`, `role`) VALUES ('admin', '$adminPass', 'admin')");
            $pdo->exec("INSERT IGNORE INTO `users` (`username`, `password`, `role`) VALUES ('admin@drsubhash.edu.in', '$adminPass', 'admin')");

            // Auto-trigger full seeding if reset script is available
            $seedScript = __DIR__ . '/../scratch/reset_and_seed.php';
            if (!file_exists($seedScript)) {
                $seedScript = 'C:/Users/Zeel/.gemini/antigravity/brain/03b1cb0a-6848-479c-85f4-5c88d45aab9c/scratch/reset_and_seed.php';
            }
            if (file_exists($seedScript)) {
                @include_once $seedScript;
            }
        }

        // Auto Column Migration Checks
        try { $pdo->exec("ALTER TABLE `students` ADD COLUMN `offer_letter` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `students` ADD COLUMN `placed_companies` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `workshops` ADD COLUMN `report_file` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `workshop_registrations` ADD COLUMN `attendance_status` VARCHAR(50) DEFAULT 'Registered'"); } catch (Exception $e) {}

    } catch (PDOException $e) {
        // Non-fatal safety fallback
    }
}

// Global Auth Helpers
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStudent() {
    return isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'student' && isset($_SESSION['student_id']);
}

// Single Placement Policy Helper: Enforces 1 Student = 1 Selected Company Limit
function canSelectStudentForCompany($pdo, $studentId, $targetDriveId = 0) {
    $stmt = $pdo->prepare("SELECT s.full_name, s.placement_status, s.placed_companies, a.drive_id, d.title, c.company_name 
                           FROM students s 
                           LEFT JOIN applications a ON s.id = a.student_id AND a.status = 'Selected' 
                           LEFT JOIN drives d ON a.drive_id = d.id 
                           LEFT JOIN companies c ON d.company_id = c.id 
                           WHERE s.id = ?");
    $stmt->execute([$studentId]);
    $st = $stmt->fetch();

    if ($st && $st['placement_status'] === 'Placed') {
        // If already selected in the same drive/company, allow updating
        if ($targetDriveId > 0 && $st['drive_id'] == $targetDriveId) {
            return ['allowed' => true];
        }
        $companyName = !empty($st['placed_companies']) ? $st['placed_companies'] : ($st['company_name'] ?: 'another company');
        return [
            'allowed' => false,
            'reason' => "⚠️ Single Placement Lock: Candidate " . $st['full_name'] . " is ALREADY PLACED at " . $companyName . "! A student can only be selected in ONE company."
        ];
    }
    return ['allowed' => true];
}

function requireAdmin() {
    if (!isAuthenticated()) {
        header("Location: ../index.php?error=unauthorized");
        exit;
    }
    if (isStudent()) {
        header("Location: ../student/dashboard.php");
        exit;
    }
    if (!isAdmin()) {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: ../index.php?error=unauthorized");
        exit;
    }
}

function requireStudent() {
    if (!isAuthenticated()) {
        header("Location: ../index.php?error=unauthorized");
        exit;
    }
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
        exit;
    }
    if (!isStudent()) {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: ../index.php?error=unauthorized");
        exit;
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Security: CSRF Protection Engine
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Security: URL Encryption & Token Obfuscation Engine
function encryptParam($val) {
    $secretKey = 'DSU_PORTAL_SECURE_2026';
    $hash = substr(hash('sha256', $val . $secretKey), 0, 8);
    $payload = base64_encode($val . '|' . $hash);
    return str_replace(['+', '/', '='], ['-', '_', ''], $payload);
}

function decryptParam($token) {
    if (empty($token) || is_numeric($token)) {
        return $token; // Native fallback for unencrypted numeric IDs
    }
    $secretKey = 'DSU_PORTAL_SECURE_2026';
    $b64 = str_replace(['-', '_'], ['+', '/'], $token);
    $decoded = base64_decode($b64, true);
    if (!$decoded || strpos($decoded, '|') === false) {
        return $token;
    }
    list($val, $hash) = explode('|', $decoded, 2);
    $expectedHash = substr(hash('sha256', $val . $secretKey), 0, 8);
    if (hash_equals($expectedHash, $hash)) {
        return $val;
    }
    return $token;
}

// Flash Message Helpers
function setFlashMessage($msg, $type = 'success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        $type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success';
        unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        return ['msg' => $msg, 'type' => $type];
    }
    return null;
}

// Mail Dispatcher Helper
function sendPortalEmail($toEmail, $subject, $bodyHtml, $pdo = null) {
    if (!$pdo) {
        $pdo = getDBConnection();
    }
    
    // Log outgoing mail in system audit logs
    try {
        $stmt = $pdo->prepare("INSERT INTO mail_logs (recipient, subject, body, sent_at, status) VALUES (?, ?, ?, NOW(), 'sent')");
        $stmt->execute([$toEmail, $subject, $bodyHtml]);
    } catch (Exception $e) {
        // Silent fail if table not created yet
    }

    // Try Gmail SMTP mail dispatching if config/mail.php exists
    if (file_exists(__DIR__ . '/mail.php')) {
        require_once __DIR__ . '/mail.php';
        if (function_exists('sendSmtpEmail')) {
            $smtpResult = sendSmtpEmail($toEmail, $subject, $bodyHtml);
            if ($smtpResult) {
                return true;
            }
        }
    }
    
    // Fallback to native mail header
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Dr. Subhash Placement Cell <rupaparazeel224@gmail.com>\r\n";
    
    @mail($toEmail, $subject, $bodyHtml, $headers);
    return true;
}
?>
