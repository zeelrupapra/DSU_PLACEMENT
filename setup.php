<?php
// Dr. Subhash Placement Portal - Automated Database Installer & Data Seeder
require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Dr. Subhash Placement Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0F172A; color: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: #1E293B; border: 1px solid #334155; border-radius: 16px; width: 100%; max-width: 650px; padding: 32px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .logo { font-size: 24px; font-weight: 800; color: #3B82F6; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .logo img { height: 40px; }
        .logo span { color: #F8FAFC; }
        .step { background: #0F172A; border-left: 4px solid #3B82F6; padding: 12px 16px; margin: 8px 0; border-radius: 0 8px 8px 0; font-size: 14px; display: flex; justify-content: space-between; }
        .status-ok { color: #10B981; font-weight: 700; }
        .btn { display: inline-block; width: 100%; padding: 14px; background: #3B82F6; color: white; text-align: center; border-radius: 10px; font-weight: 700; text-decoration: none; margin-top: 20px; box-sizing: border-box; transition: all 0.3s; }
        .btn:hover { background: #2563EB; transform: translateY(-2px); }
        .credentials { background: rgba(59, 130, 246, 0.1); border: 1px dashed #3B82F6; padding: 16px; border-radius: 10px; margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <img src="assets/images/logo.png" alt="University Logo">
        <span>Dr. Subhash University</span>
    </div>
    <h2>Automated Database Setup</h2>
    <p style="color: #94A3B8; font-size: 14px; margin-bottom: 20px;">Setting up tables and seeding high-volume realistic placement records...</p>

    <?php
    $pdo = getDBConnection();

    // Table Schemas
    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(150) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
            status ENUM('active', 'inactive') DEFAULT 'active',
            reset_token VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "students" => "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            enrollment_no VARCHAR(50) NOT NULL UNIQUE,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            gender ENUM('Male', 'Female') DEFAULT 'Male',
            branch ENUM('CSE', 'IT', 'ECE', 'MECH', 'CIVIL', 'OTHERS') NOT NULL DEFAULT 'CSE',
            batch_year INT NOT NULL DEFAULT 2024,
            cpi DECIMAL(4,2) NOT NULL DEFAULT 8.00,
            backlogs INT NOT NULL DEFAULT 0,
            phone VARCHAR(20) DEFAULT '9876543210',
            profile_photo VARCHAR(255) DEFAULT 'default_avatar.png',
            resume_file VARCHAR(255) DEFAULT 'sample_resume.pdf',
            placement_status ENUM('Unplaced', 'In-Process', 'Placed') DEFAULT 'In-Process',
            is_detained TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        "companies" => "CREATE TABLE IF NOT EXISTS companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(150) NOT NULL,
            hr_name VARCHAR(100) NOT NULL,
            hr_email VARCHAR(150) NOT NULL,
            phone VARCHAR(20) DEFAULT '9988776655',
            website VARCHAR(255) DEFAULT 'https://example.com',
            logo VARCHAR(255) DEFAULT 'company_default.png',
            industry VARCHAR(100) DEFAULT 'Information Technology',
            address VARCHAR(255) DEFAULT 'Tech Park, Sector 5',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "drives" => "CREATE TABLE IF NOT EXISTS drives (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            designation VARCHAR(150) NOT NULL,
            package_ctc VARCHAR(50) NOT NULL,
            location VARCHAR(100) DEFAULT 'Pan India / Remote',
            min_cpi DECIMAL(4,2) DEFAULT 6.50,
            allowed_branches VARCHAR(255) DEFAULT 'CSE, IT, ECE, MECH, CIVIL',
            max_backlogs INT DEFAULT 0,
            drive_date DATE NOT NULL,
            deadline DATE NOT NULL,
            description TEXT,
            status ENUM('Upcoming', 'Active', 'Completed', 'Cancelled') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )",

        "applications" => "CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            drive_id INT NOT NULL,
            student_id INT NOT NULL,
            applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('Applied', 'Shortlisted', 'Interview Round', 'Selected', 'Rejected') DEFAULT 'Applied',
            is_read TINYINT(1) DEFAULT 0,
            remarks TEXT,
            FOREIGN KEY (drive_id) REFERENCES drives(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_app (drive_id, student_id)
        )",

        "internships" => "CREATE TABLE IF NOT EXISTS internships (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            company_name VARCHAR(150) NOT NULL,
            title VARCHAR(150) NOT NULL,
            stipend VARCHAR(50) DEFAULT '₹15,000 / month',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status ENUM('Ongoing', 'Completed') DEFAULT 'Completed',
            certificate_file VARCHAR(255) DEFAULT 'certificate_sample.pdf',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )",

        "workshops" => "CREATE TABLE IF NOT EXISTS workshops (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            speaker VARCHAR(150) NOT NULL,
            event_date DATETIME NOT NULL,
            venue VARCHAR(150) NOT NULL,
            capacity INT DEFAULT 100,
            description TEXT,
            status ENUM('Upcoming', 'Completed', 'Cancelled') DEFAULT 'Upcoming',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "workshop_registrations" => "CREATE TABLE IF NOT EXISTS workshop_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            workshop_id INT NOT NULL,
            student_id INT NOT NULL,
            registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_reg (workshop_id, student_id)
        )",

        "mous" => "CREATE TABLE IF NOT EXISTS mous (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(150) NOT NULL,
            signed_date DATE NOT NULL,
            expiry_date DATE NOT NULL,
            scope TEXT,
            status ENUM('Active', 'Expiring Soon', 'Expired') DEFAULT 'Active',
            document_file VARCHAR(255) DEFAULT 'mou_document.pdf',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "settings" => "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT
        )",

        "mail_logs" => "CREATE TABLE IF NOT EXISTS mail_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(150) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'sent'
        )"
    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        echo "<div class='step'><span>Table <strong>$name</strong> initialized</span><span class='status-ok'>SUCCESS</span></div>";
    }

    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN gender ENUM('Male', 'Female') DEFAULT 'Male'");
    } catch (Exception $e) {}

    // Default Seed Data Creation
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES ('admin', 'admin@drsubhash.edu.in', ?, 'admin')");
    $stmt->execute([$adminPassword]);

    $studentPassword = password_hash('student123', PASSWORD_BCRYPT);
    
    $sampleStudents = [
        ['EN2024001', 'Aarav Mehta', 'aarav.mehta@student.drsubhash.edu.in', 'Male', 'CSE', 2024, 9.12, 0, '9876543210', 'Placed', 0],
        ['EN2024002', 'Priya Sharma', 'priya.sharma@student.drsubhash.edu.in', 'Female', 'IT', 2024, 8.85, 0, '9876543211', 'Placed', 0],
        ['EN2024003', 'Rohan Patel', 'rohan.patel@student.drsubhash.edu.in', 'Male', 'CSE', 2024, 8.40, 0, '9876543212', 'Placed', 0],
        ['EN2024004', 'Ananya Verma', 'ananya.v@student.drsubhash.edu.in', 'Female', 'ECE', 2024, 7.95, 0, '9876543213', 'In-Process', 0],
        ['EN2024005', 'Vikram Joshi', 'vikram.j@student.drsubhash.edu.in', 'Male', 'MECH', 2024, 7.60, 1, '9876543214', 'Unplaced', 1],
        ['EN2024006', 'Siddharth Rao', 'sid.rao@student.drsubhash.edu.in', 'Male', 'CIVIL', 2024, 8.10, 0, '9876543215', 'Placed', 0],
        ['EN2024007', 'Neha Kapoor', 'neha.k@student.drsubhash.edu.in', 'Female', 'IT', 2024, 9.45, 0, '9876543216', 'Placed', 0],
        ['EN2024008', 'Karan Gupta', 'karan.g@student.drsubhash.edu.in', 'Male', 'CSE', 2024, 7.80, 0, '9876543217', 'In-Process', 0]
    ];

    foreach ($sampleStudents as $st) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'student')");
        $stmt->execute([$st[0], $st[2], $studentPassword]);
        $userId = $pdo->lastInsertId();

        if ($userId) {
            $stmtSt = $pdo->prepare("INSERT IGNORE INTO students (user_id, enrollment_no, full_name, email, gender, branch, batch_year, cpi, backlogs, phone, placement_status, is_detained) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtSt->execute([$userId, $st[0], $st[1], $st[2], $st[3], $st[4], $st[5], $st[6], $st[7], $st[8], $st[9], $st[10]]);
        }
    }

    // Seed Completed Drives for History
    $stmtC = $pdo->prepare("INSERT IGNORE INTO drives (company_id, title, designation, package_ctc, location, min_cpi, allowed_branches, max_backlogs, drive_date, deadline, description, status) VALUES 
        (1, 'Cloud Architect Drive 2023', 'Cloud Engineer', '₹24.00 LPA', 'Bengaluru', 7.50, 'CSE, IT', 0, '2023-11-10', '2023-11-05', 'Completed drive for 2023 batch.', 'Completed'),
        (3, 'TCS Ninja Hiring 2023', 'Systems Engineer', '₹7.00 LPA', 'Pan India', 6.00, 'CSE, IT, ECE, MECH, CIVIL', 1, '2023-10-15', '2023-10-10', 'Completed drive for 2023 batch.', 'Completed')");
    $stmtC->execute();

    echo "<div class='step'><span>Seeded Gender, Openings History & Database Schema</span><span class='status-ok'>COMPLETED</span></div>";
    ?>

    <div class="credentials">
        <strong>🔑 Default Access Credentials:</strong><br><br>
        <strong>Admin Login:</strong> <code>admin@drsubhash.edu.in</code> / <code>admin123</code><br>
        <strong>Student Login:</strong> <code>EN2024001</code> / <code>student123</code>
    </div>

    <a href="index.php" class="btn">Launch Placement Portal →</a>
</div>
</body>
</html>
