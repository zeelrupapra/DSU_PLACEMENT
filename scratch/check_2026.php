<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDBConnection();

$s = $pdo->query("SELECT enrollment_no, full_name, batch_year, is_detained FROM students WHERE batch_year = 2026")->fetchAll(PDO::FETCH_ASSOC);
print_r($s);
