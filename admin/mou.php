<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);
    if ($action === 'create_mou') {
        $compName = sanitize($_POST['company_name']);
        $signedDate = sanitize($_POST['signed_date']);
        $expiryDate = sanitize($_POST['expiry_date']);
        $scope = sanitize($_POST['scope']);
        $status = sanitize($_POST['status']);

        $stmt = $pdo->prepare("INSERT INTO mous (company_name, signed_date, expiry_date, scope, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$compName, $signedDate, $expiryDate, $scope, $status]);
        $msg = "MOU agreement recorded successfully!";
    }
}

// Active Filters
$yearFilter = isset($_GET['year']) ? intval($_GET['year']) : 0;
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Excel / CSV Export Engine
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=institutional_mou_report_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Partner Enterprise', 'Signing Date', 'Expiry Date', 'Agreement Scope', 'Active Status']);

    $qExp = "SELECT * FROM mous WHERE 1=1";
    if ($yearFilter > 0) { $qExp .= " AND (YEAR(signed_date) = $yearFilter OR YEAR(expiry_date) = $yearFilter)"; }
    if ($statusFilter !== '') { $qExp .= " AND status = '{$statusFilter}'"; }
    $qExp .= " ORDER BY id DESC";

    $expMous = $pdo->query($qExp)->fetchAll();
    foreach ($expMous as $mRow) {
        fputcsv($output, [
            $mRow['company_name'],
            $mRow['signed_date'],
            $mRow['expiry_date'],
            $mRow['scope'],
            $mRow['status']
        ]);
    }
    fclose($output);
    exit;
}

// Query filtered MOUs
$q = "SELECT * FROM mous WHERE 1=1";
if ($yearFilter > 0) { $q .= " AND (YEAR(signed_date) = $yearFilter OR YEAR(expiry_date) = $yearFilter)"; }
if ($statusFilter !== '') { $q .= " AND status = '{$statusFilter}'"; }
$q .= " ORDER BY id DESC";

$mous = $pdo->query($q)->fetchAll();

$pageTitle = 'Institutional MOUs';
$currentPage = 'mou';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Memorandum of Understanding (MOU) Partnerships</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Manage industrial collaborations, signed MOUs, expiry tracking, and partnership scopes.</p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <a href="mou.php?export_csv=1&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 700;">
            📊 Export Excel / CSV
        </a>
        <button class="btn-primary" onclick="openModal('createMouModal')" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Register New MOU
        </button>
    </div>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 16px; display: inline-block; font-size: 14px; padding: 10px 16px;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>

<!-- Multi-Filter Controls Bar -->
<div class="table-card" style="padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; background: white; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03); flex-wrap: wrap; gap: 12px;">
    <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <select name="year" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700;">
            <option value="0">📅 All Agreement Years</option>
            <option value="2026" <?= ($yearFilter===2026)?'selected':''; ?>>2026 Academic Year</option>
            <option value="2025" <?= ($yearFilter===2025)?'selected':''; ?>>2025 Academic Year</option>
            <option value="2024" <?= ($yearFilter===2024)?'selected':''; ?>>2024 Academic Year</option>
        </select>

        <select name="status" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700;">
            <option value="">All MOU Statuses</option>
            <option value="Active" <?= ($statusFilter==='Active')?'selected':''; ?>>🟢 Active MOUs</option>
            <option value="Expiring Soon" <?= ($statusFilter==='Expiring Soon')?'selected':''; ?>>🟡 Expiring Soon</option>
            <option value="Expired" <?= ($statusFilter==='Expired')?'selected':''; ?>>🔴 Expired</option>
        </select>

        <a href="mou.php" class="btn-secondary" style="padding: 9px 16px; font-size: 13px;">Reset Filter</a>
    </form>
    <span class="kpi-badge info">Showing <?= count($mous); ?> MOU Agreements</span>
</div>

<div class="table-card" style="padding: 24px;">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Partner Enterprise</th>
                    <th>Signed Date</th>
                    <th>Expiration Date</th>
                    <th>Collaboration Scope</th>
                    <th>Active Status</th>
                    <th>Agreement File</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mous as $m): ?>
                    <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                        <td style="border-top-left-radius: 16px; border-bottom-left-radius: 16px;">
                            <strong style="font-size: 15px; color: #0F172A;">🏢 <?= htmlspecialchars($m['company_name']); ?></strong>
                        </td>
                        <td><strong><?= date('d M Y', strtotime($m['signed_date'])); ?></strong></td>
                        <td><strong style="color: #EF4444;"><?= date('d M Y', strtotime($m['expiry_date'])); ?></strong></td>
                        <td><span style="font-size: 12px; color: var(--text-muted); font-weight: 600;"><?= htmlspecialchars($m['scope']); ?></span></td>
                        <td>
                            <?php $bClass = ($m['status']==='Active') ? 'placed' : (($m['status']==='Expiring Soon') ? 'in-process' : 'unplaced'); ?>
                            <span class="badge-status <?= $bClass; ?>"><?= $m['status']; ?></span>
                        </td>
                        <td style="border-top-right-radius: 16px; border-bottom-right-radius: 16px;">
                            <a href="#" style="color: #3B82F6; font-size: 13px; font-weight: 700; text-decoration: none;">📄 View PDF ↗</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Register MOU -->
<div class="modal-overlay" id="createMouModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Register Institutional MOU</h3>
            <button class="close-modal" onclick="closeModal('createMouModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_mou">
            <div class="form-group">
                <label>Partner Company Name *</label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. Cisco Networking Academy" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Signing Date *</label>
                    <input type="date" name="signed_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date *</label>
                    <input type="date" name="expiry_date" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Scope of Agreement</label>
                <textarea name="scope" class="form-control" rows="3" placeholder="Faculty development, laboratory sponsorship, direct recruitment..."></textarea>
            </div>
            <div class="form-group">
                <label>MOU Status</label>
                <select name="status" class="form-control">
                    <option value="Active">Active</option>
                    <option value="Expiring Soon">Expiring Soon</option>
                    <option value="Expired">Expired</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px;">Save MOU Record</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
