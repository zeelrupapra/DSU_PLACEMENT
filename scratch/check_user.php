<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDBConnection();

$email = 'rupaparazeel224@gmail.com';
$u = $pdo->query("SELECT * FROM users WHERE username LIKE '%zeel%' OR username LIKE '%rupar%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($u);

$s = $pdo->query("SELECT * FROM students WHERE email LIKE '%zeel%' OR email LIKE '%rupar%' OR full_name LIKE '%zeel%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($s);
