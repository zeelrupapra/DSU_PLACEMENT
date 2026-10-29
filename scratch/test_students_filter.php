<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDBConnection();

function testFilter($yearFilter, $branchFilter = '', $statusFilter = '') {
    global $pdo;
    $query = "SELECT s.* FROM students s WHERE s.is_detained = 0";
    $params = [];
    if ($branchFilter) { $query .= " AND s.branch = ?"; $params[] = $branchFilter; }
    if ($yearFilter > 0) { $query .= " AND s.batch_year = ?"; $params[] = $yearFilter; }
    if ($statusFilter) { $query .= " AND s.placement_status = ?"; $params[] = $statusFilter; }
    $query .= " ORDER BY s.id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $years = array_unique(array_column($rows, 'batch_year'));
    sort($years);
    return [
        'count' => count($rows),
        'years' => $years
    ];
}

echo "=== TEST 1: ALL BATCH YEARS (year = 0) ===\n";
$r1 = testFilter(0);
echo "Count: {$r1['count']} | Years present: " . implode(', ', $r1['years']) . "\n";

echo "\n=== TEST 2: 2026 BATCH ONLY (year = 2026) ===\n";
$r2 = testFilter(2026);
echo "Count: {$r2['count']} | Years present: " . implode(', ', $r2['years']) . "\n";

echo "\n=== TEST 3: 2025 BATCH ONLY (year = 2025) ===\n";
$r3 = testFilter(2025);
echo "Count: {$r3['count']} | Years present: " . implode(', ', $r3['years']) . "\n";

echo "\n=== TEST 4: 2024 BATCH ONLY (year = 2024) ===\n";
$r4 = testFilter(2024);
echo "Count: {$r4['count']} | Years present: " . implode(', ', $r4['years']) . "\n";

echo "\n=== TEST 5: ALL CSE STUDENTS (branch = 'CSE', year = 0) ===\n";
$r5 = testFilter(0, 'CSE');
echo "Count: {$r5['count']} | Years present: " . implode(', ', $r5['years']) . "\n";

echo "\n=== TEST 6: ALL PLACED STUDENTS (status = 'Placed', year = 0) ===\n";
$r6 = testFilter(0, '', 'Placed');
echo "Count: {$r6['count']} | Years present: " . implode(', ', $r6['years']) . "\n";
