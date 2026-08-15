<?php
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/db.php';
}
$headerTitle = isset($pageTitle) ? $pageTitle : 'Admin Dashboard';
$pdo = getDBConnection();

$isSubDir = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/student/') !== false);
$assetsPrefix = $isSubDir ? '../assets' : 'assets';

// Fetch unread application notifications count for admin
$unreadAppsCount = 0;
$recentApps = [];
if (isAdmin()) {
    $unreadAppsCount = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Applied'")->fetchColumn();
    $recentApps = $pdo->query("SELECT a.*, s.full_name, s.enrollment_no, d.title as drive_title FROM applications a JOIN students s ON a.student_id = s.id JOIN drives d ON a.drive_id = d.id ORDER BY a.id DESC LIMIT 5")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title><?= htmlspecialchars($headerTitle); ?> - Dr. Subhash University Placement Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assetsPrefix; ?>/css/style.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .notif-dropdown { position: absolute; right: 0; top: 48px; width: 320px; background: white; border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-lg); padding: 16px; display: none; z-index: 500; }
        .notif-dropdown.active { display: block; }
    </style>
</head>
<body>
<div class="app-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <header class="top-bar">
            <div class="page-title">
                <h2><?= htmlspecialchars($headerTitle); ?></h2>
            </div>

            <div class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="tableSearch" placeholder="Search students, companies, drives...">
            </div>

            <div class="top-actions" style="position: relative;">
                <?php if (isAdmin()): ?>
                    <button class="icon-btn" title="Live Applications Notification" onclick="toggleNotifications()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <?php if ($unreadAppsCount > 0): ?>
                            <span class="badge-dot"></span>
                        <?php endif; ?>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div class="notif-dropdown" id="notifMenu">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <strong style="font-size: 14px;">Recent Drive Applications</strong>
                            <span class="kpi-badge info"><?= $unreadAppsCount; ?> New</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($recentApps as $ra): ?>
                                <div style="border-bottom: 1px solid #F1F5F9; padding-bottom: 8px;">
                                    <strong style="font-size: 13px; color: var(--text-main);"><?= htmlspecialchars($ra['full_name']); ?></strong> 
                                    <span style="font-size: 11px; color: var(--text-muted);">(<?= $ra['enrollment_no']; ?>)</span><br>
                                    <span style="font-size: 12px; color: #3B82F6;">Applied for <?= htmlspecialchars($ra['drive_title']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="drives.php" style="display: block; text-align: center; margin-top: 12px; font-size: 12px; color: #3B82F6; font-weight: 700; text-decoration: none;">View All Applications →</a>
                    </div>
                <?php endif; ?>

                <img src="<?= $assetsPrefix; ?>/images/logo.png" alt="Dr. Subhash Logo" style="height: 38px; border-radius: 8px; object-fit: contain;">
            </div>
        </header>

<?php 
$flash = getFlashMessage();
if ($flash): 
    $flashBg = ($flash['type'] === 'danger' || $flash['type'] === 'unplaced') ? '#FEF2F2' : (($flash['type'] === 'warning' || $flash['type'] === 'in-process') ? '#FFFBEB' : '#ECFDF5');
    $flashBorder = ($flash['type'] === 'danger' || $flash['type'] === 'unplaced') ? '#FCA5A5' : (($flash['type'] === 'warning' || $flash['type'] === 'in-process') ? '#FCD34D' : '#A7F3D0');
    $flashText = ($flash['type'] === 'danger' || $flash['type'] === 'unplaced') ? '#991B1B' : (($flash['type'] === 'warning' || $flash['type'] === 'in-process') ? '#92400E' : '#065F46');
    $flashIcon = ($flash['type'] === 'danger' || $flash['type'] === 'unplaced') ? '⚠️' : '🎉';
?>
    <div id="persistentFlashBanner" style="background: <?= $flashBg; ?>; border: 1px solid <?= $flashBorder; ?>; color: <?= $flashText; ?>; padding: 14px 20px; border-radius: 16px; margin: 20px 0; font-weight: 800; font-size: 14px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 14px rgba(0,0,0,0.04);">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 18px;"><?= $flashIcon; ?></span>
            <span><?= htmlspecialchars($flash['msg']); ?></span>
        </div>
        <button onclick="document.getElementById('persistentFlashBanner').remove()" style="background: none; border: none; font-size: 18px; color: <?= $flashText; ?>; cursor: pointer; font-weight: 800; padding: 0 6px;" title="Dismiss Banner">✕</button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.showToast) {
                window.showToast('Notice', <?= json_encode($flash['msg']); ?>, <?= json_encode($flash['type']); ?>, 8000);
            }
        });
    </script>
<?php endif; ?>

<script>
function toggleNotifications() {
    const menu = document.getElementById('notifMenu');
    if (menu) menu.classList.toggle('active');
}
</script>
