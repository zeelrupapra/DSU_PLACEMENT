<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();

// Fetch Completed / Past Drives
$pastDrives = $pdo->query("SELECT d.*, c.company_name, c.logo FROM drives d JOIN companies c ON d.company_id = c.id WHERE d.status = 'Completed' OR d.deadline < CURDATE() ORDER BY d.drive_date DESC")->fetchAll();

$pageTitle = 'Previous Openings (Closed)';
$currentPage = 'drives_history';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 800;">Previous Openings & Archived Drives</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Historical placement drives, recruiter records, and past offers archive.</p>
    </div>
</div>

<div class="table-card">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Company & Role</th>
                <th>Designation</th>
                <th>Package CTC</th>
                <th>Drive Date</th>
                <th>Total Applicants</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pastDrives)): ?>
                <?php foreach ($pastDrives as $drv): 
                    $appCount = $pdo->query("SELECT COUNT(*) FROM applications WHERE drive_id = {$drv['id']}")->fetchColumn();
                    $selectedCount = $pdo->query("SELECT COUNT(*) FROM applications WHERE drive_id = {$drv['id']} AND status = 'Selected'")->fetchColumn();
                ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($drv['title']); ?></strong><br>
                            <span style="font-size: 12px; color: #3B82F6;">🏢 <?= htmlspecialchars($drv['company_name']); ?></span>
                        </td>
                        <td><?= htmlspecialchars($drv['designation']); ?></td>
                        <td><strong style="color: #10B981;"><?= htmlspecialchars($drv['package_ctc']); ?></strong></td>
                        <td><?= date('d M Y', strtotime($drv['drive_date'])); ?></td>
                        <td><strong><?= $appCount; ?> Applied</strong> (<?= $selectedCount; ?> Placed)</td>
                        <td><span class="badge-status in-process" style="background:#F3F4F6; color:#4B5563;">Closed Drive</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="color: var(--text-muted); text-align: center;">No archived or previous closed drives found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
