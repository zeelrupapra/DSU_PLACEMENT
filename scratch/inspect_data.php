<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDBConnection();

echo "=== STUDENTS BY BATCH YEAR ===\n";
$yearCounts = $pdo->query("SELECT batch_year, COUNT(*) as cnt FROM students GROUP BY batch_year")->fetchAll(PDO::FETCH_ASSOC);
print_r($yearCounts);

echo "=== STUDENTS SAMPLE ===\n";
$students = $pdo->query("SELECT s.id, s.user_id, s.enrollment_no, s.full_name, s.email, s.batch_year, u.username, u.password FROM students s JOIN users u ON s.user_id = u.id LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($students);

echo "=== TEST PASSWORD VERIFICATION ===\n";
$admin = $pdo->query("SELECT * FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
if ($admin) {
    echo "Admin password verify with 'admin123': " . (password_verify('admin123', $admin['password']) ? 'YES' : 'NO') . "\n";
    echo "Admin password verify with 'admin': " . (password_verify('admin', $admin['password']) ? 'YES' : 'NO') . "\n";
}

$firstStudent = $pdo->query("SELECT s.*, u.password as user_pwd FROM students s JOIN users u ON s.user_id = u.id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($firstStudent) {
    echo "Student: " . $firstStudent['enrollment_no'] . " / " . $firstStudent['email'] . "\n";
    echo "Student verify with 'student123': " . (password_verify('student123', $firstStudent['user_pwd']) ? 'YES' : 'NO') . "\n";
    echo "Student verify with 'admin123': " . (password_verify('admin123', $firstStudent['user_pwd']) ? 'YES' : 'NO') . "\n";
    echo "Student verify with enrollment_no: " . ($firstStudent['enrollment_no'] === $firstStudent['user_pwd'] ? 'YES' : 'NO') . "\n";
}
