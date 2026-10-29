<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDBConnection();

$dump = "-- Dr. Subhash University Placement Portal - Full Database Backup\n";
$dump .= "-- Generated on " . date('Y-m-d H:i:s') . "\n";
$dump .= "-- Database: dr_subhash_placement\n\n";
$dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$dump .= "CREATE DATABASE IF NOT EXISTS `dr_subhash_placement` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$dump .= "USE `dr_subhash_placement`;\n\n";

$tables = ['settings', 'companies', 'drives', 'users', 'students', 'applications', 'internships', 'workshops', 'workshop_registrations', 'mou_agreements', 'mous', 'mail_logs'];

foreach ($tables as $table) {
    // Check if table exists
    $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
    if (!$check) continue;

    $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $dump .= "DROP TABLE IF EXISTS `$table`;\n";
    $dump .= $createTable['Create Table'] . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $colList = implode('`, `', $cols);
            $vals = array_map(function($v) use ($pdo) {
                if (is_null($v)) return 'NULL';
                return $pdo->quote($v);
            }, array_values($row));
            $valList = implode(', ', $vals);
            $dump .= "INSERT INTO `$table` (`$colList`) VALUES ($valList);\n";
        }
        $dump .= "\n";
    }
}

$dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/../database.sql', $dump);
echo "Database successfully dumped to database.sql (" . strlen($dump) . " bytes).\n";
