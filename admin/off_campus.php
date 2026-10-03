<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';
$error = '';

// Handle Verification Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'verify_off_campus') {
        $offerId = intval($_POST['offer_id']);
        $newStatus = sanitize($_POST['verification_status']); // 'Verified' or 'Rejected'
        $remarks = sanitize($_POST['remarks'] ?? '');
        $adminId = $_SESSION['user_id'];

        $stmtGet = $pdo->prepare("SELECT o.*, s.full_name, s.email, s.enrollment_no 
                                  FROM off_campus_placements o 
                                  JOIN students s ON o.student_id = s.id 
                                  WHERE o.id = ?");
        $stmtGet->execute([$offerId]);
        $offer = $stmtGet->fetch();

        if ($offer) {
            $stmtUp = $pdo->prepare("UPDATE off_campus_placements SET verification_status = ?, verified_by = ?, remarks = ? WHERE id = ?");
            $stmtUp->execute([$newStatus, $adminId, $remarks, $offerId]);

            if ($newStatus === 'Verified') {
                $compTag = $offer['company_name'] . ' (' . $offer['package_ctc'] . ') [Off-Campus]';
                $numLpa = parseLpaValue($offer['package_ctc']);
                
                $stmtStUp = $pdo->prepare("UPDATE students SET placement_status = 'Placed', placed_companies = ?, current_package_lpa = ? WHERE id = ?");
                $stmtStUp->execute([$compTag, $numLpa > 0 ? $numLpa : null, $offer['student_id']]);

                // Send notification email
                $sub = "🎉 Off-Campus Placement Offer Verified - Dr. Subhash Placement Cell";
                $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #E2E8F0; border-radius: 16px; padding: 26px; background: #FFFFFF;'>
                    <h3 style='color: #059669;'>🎉 Off-Campus Placement Verified!</h3>
                    <p>Dear <strong>" . htmlspecialchars($offer['full_name']) . "</strong>,</p>
                    <p>Your off-campus placement offer at <strong>" . htmlspecialchars($offer['company_name']) . "</strong> has been officially verified by the Training & Placement Cell.</p>
                    <div style='background: #ECFDF5; border: 1px solid #A7F3D0; padding: 16px; border-radius: 12px; margin: 18px 0;'>
                        <p style='margin: 0 0 6px 0;'><strong>Company:</strong> " . htmlspecialchars($offer['company_name']) . "</p>
                        <p style='margin: 0 0 6px 0;'><strong>Role:</strong> " . htmlspecialchars($offer['designation']) . "</p>
                        <p style='margin: 0;'><strong>Package:</strong> " . htmlspecialchars($offer['package_ctc']) . "</p>
                    </div>
                    <p style='font-size: 13px; color: #64748B;'>Your status has been updated to <strong>Placed</strong> in university records.</p>
                </div>";
                sendPortalEmail($offer['email'], $sub, $body, $pdo);

                setFlashMessage("Off-campus offer for " . htmlspecialchars($offer['full_name']) . " verified and student marked as Placed!", 'success');
            } else {
                setFlashMessage("Off-campus offer for " . htmlspecialchars($offer['full_name']) . " marked as Rejected.", 'info');
            }
        }
        header("Location: off_campus.php");
        exit;
    }
}

// Filters
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$searchQ = isset($_GET['q']) ? sanitize($_GET['q']) : '';

$where = "WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $where .= " AND o.verification_status = ?";
    $params[] = $statusFilter;
}
if (!empty($searchQ)) {
    $where .= " AND (s.full_name LIKE ? OR s.enrollment_no LIKE ? OR o.company_name LIKE ?)";
    $params[] = "%$searchQ%";
    $params[] = "%$searchQ%";
    $params[] = "%$searchQ%";
}

// Metrics
$totalOffers = $pdo->query("SELECT COUNT(*) FROM off_campus_placements")->fetchColumn();
$pendingOffers = $pdo->query("SELECT COUNT(*) FROM off_campus_placements WHERE verification_status = 'Pending'")->fetchColumn();
$verifiedOffers = $pdo->query("SELECT COUNT(*) FROM off_campus_placements WHERE verification_status = 'Verified'")->fetchColumn();
$rejectedOffers = $pdo->query("SELECT COUNT(*) FROM off_campus_placements WHERE verification_status = 'Rejected'")->fetchColumn();

// Fetch list
$stmtList = $pdo->prepare("SELECT o.*, s.full_name, s.enrollment_no, s.branch, s.email, s.phone, s.cpi 
                          FROM off_campus_placements o 
                          JOIN students s ON o.student_id = s.id 
                          $where ORDER BY o.id DESC");
$stmtList->execute($params);
$offers = $stmtList->fetchAll();

$pageTitle = 'Off-Campus Placement Verification';
$currentPage = 'off_campus';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Toolbar -->
<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Off-Campus Placement Offers Verification</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Review student external hiring dossiers, verify appointment letters, and approve university placement records.</p>
    </div>
</div>

<!-- KPI Cards Grid -->
<div class="kpi-grid" style="margin-bottom: 24px;">
    <div class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-title">Total Submissions</span>
            <div class="kpi-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($totalOffers); ?></div>
        <span class="kpi-badge info">External Offers</span>
    </div>

    <div class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-title">Pending Verification</span>
            <div class="kpi-icon orange">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($pendingOffers); ?></div>
        <span class="kpi-badge in-process">Needs Review</span>
    </div>

    <div class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-title">Verified & Placed</span>
            <div class="kpi-icon green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($verifiedOffers); ?></div>
        <span class="kpi-badge placed">Approved Records</span>
    </div>

    <div class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-title">Rejected Submissions</span>
            <div class="kpi-icon red">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($rejectedOffers); ?></div>
        <span class="kpi-badge unplaced">Ineligible / Invalid</span>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="table-card" style="padding: 16px 20px; border-radius: 18px; background: white; border: 1px solid var(--border-color); margin-bottom: 24px;">
    <form method="GET" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 240px;">
            <input type="text" name="q" value="<?= htmlspecialchars($searchQ); ?>" placeholder="Search candidate, enrollment code, or company name..." class="form-control" style="border-radius: 10px; padding: 9px 14px;">
        </div>

        <div style="min-width: 180px;">
            <select name="status" class="form-control" style="border-radius: 10px; padding: 9px 12px; font-weight: 700;">
                <option value="">All Verification Statuses</option>
                <option value="Pending" <?= ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending Review</option>
                <option value="Verified" <?= ($statusFilter === 'Verified') ? 'selected' : ''; ?>>Verified (Approved)</option>
                <option value="Rejected" <?= ($statusFilter === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>

        <button type="submit" class="btn-primary" style="border-radius: 10px; padding: 9px 18px; font-weight: 800;">
            Filter
        </button>
        <?php if ($searchQ || $statusFilter): ?>
            <a href="off_campus.php" class="btn-secondary" style="border-radius: 10px; padding: 9px 14px; text-decoration: none; font-weight: 700;">
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Table Card -->
<div class="table-card" style="padding: 24px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Branch & CPI</th>
                    <th>Company & Role</th>
                    <th>Salary Package</th>
                    <th>Offer Document</th>
                    <th>Status</th>
                    <th style="text-align: right;">Review Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($offers)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 50px 20px; color: var(--text-muted);">
                            No off-campus placement records found matching filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($offers as $o): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 14px; color: #0F172A; display: block;"><?= htmlspecialchars($o['full_name']); ?></strong>
                                <code style="font-size: 11.5px; color: #2563EB;"><?= htmlspecialchars($o['enrollment_no']); ?></code>
                            </td>
                            <td>
                                <strong style="font-size: 13px; color: #334155;"><?= htmlspecialchars($o['branch']); ?></strong>
                                <span style="font-size: 12px; font-weight: 800; color: #2563EB; display: block;">★ <?= number_format($o['cpi'], 2); ?></span>
                            </td>
                            <td>
                                <strong style="font-size: 13.5px; color: #0F172A; display: block;"><?= htmlspecialchars($o['company_name']); ?></strong>
                                <span style="font-size: 12px; color: #64748B;"><?= htmlspecialchars($o['designation']); ?> • <?= htmlspecialchars($o['location'] ?: 'India'); ?></span>
                            </td>
                            <td>
                                <strong style="color: #059669; font-size: 14px;"><?= htmlspecialchars($o['package_ctc']); ?></strong>
                            </td>
                            <td>
                                <?php if ($o['offer_letter_file'] && file_exists(__DIR__ . '/../uploads/' . $o['offer_letter_file'])): ?>
                                    <a href="../uploads/<?= htmlspecialchars($o['offer_letter_file']); ?>" target="_blank" class="btn-secondary" style="padding: 5px 10px; font-size: 11.5px; font-weight: 800; border-radius: 8px; color: #2563EB; border: 1px solid #BFDBFE; background: #EFF6FF; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        📜 View Document ↗
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #94A3B8;">No File</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $vStCls = ($o['verification_status'] === 'Verified') ? 'placed' : (($o['verification_status'] === 'Rejected') ? 'unplaced' : 'in-process');
                                ?>
                                <span class="badge-status <?= $vStCls; ?>" style="font-weight: 800;"><?= htmlspecialchars($o['verification_status']); ?></span>
                                <?php if (!empty($o['remarks'])): ?>
                                    <span style="display: block; font-size: 11px; color: #64748B; margin-top: 4px; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($o['remarks']); ?>">
                                        💬 <?= htmlspecialchars($o['remarks']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button class="btn-primary" onclick="openVerifyModal(<?= $o['id']; ?>, '<?= addslashes($o['full_name']); ?>', '<?= addslashes($o['company_name']); ?>', '<?= addslashes($o['package_ctc']); ?>', '<?= $o['verification_status']; ?>', '<?= addslashes($o['remarks'] ?? ''); ?>')" style="padding: 7px 14px; font-size: 12px; border-radius: 8px; font-weight: 800;">
                                    ⚙️ Verify
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Verification Action Modal -->
<div id="verifyModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(6px); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 20px; width: 90%; max-width: 460px; padding: 28px; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Verify Off-Campus Placement</h3>
            <button onclick="closeVerifyModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748B;">✕</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="verify_off_campus">
            <input type="hidden" name="offer_id" id="modalOfferId">

            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px; border-radius: 12px; margin-bottom: 16px; font-size: 13px;">
                <p style="margin: 0 0 4px 0;"><strong>Candidate:</strong> <span id="modalCandName"></span></p>
                <p style="margin: 0;"><strong>Offer:</strong> <span id="modalCompOffer"></span></p>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Verification Decision *</label>
                <select name="verification_status" id="modalStatusSelect" class="form-control" required style="border-radius: 10px; padding: 10px 12px; font-weight: 700;">
                    <option value="Verified">🟢 Verify & Approve (Mark Student Placed)</option>
                    <option value="Rejected">🔴 Reject (Invalid Document / Criteria)</option>
                    <option value="Pending">🟡 Keep in Pending Review</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Verification Notes / Feedback</label>
                <textarea name="remarks" id="modalRemarksText" class="form-control" rows="3" placeholder="e.g. Verified official appointment letter and joined date." style="border-radius: 10px; padding: 10px 12px; font-size: 13px;"></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeVerifyModal()" class="btn-secondary" style="flex: 1; justify-content: center; border-radius: 10px; padding: 12px; font-weight: 800;">Cancel</button>
                <button type="submit" class="btn-primary" style="flex: 1; justify-content: center; border-radius: 10px; padding: 12px; font-weight: 800;">Submit Decision</button>
            </div>
        </form>
    </div>
</div>

<script>
function openVerifyModal(id, cand, comp, pkg, status, remarks) {
    document.getElementById('modalOfferId').value = id;
    document.getElementById('modalCandName').innerText = cand;
    document.getElementById('modalCompOffer').innerText = comp + ' (' + pkg + ')';
    document.getElementById('modalStatusSelect').value = status;
    document.getElementById('modalRemarksText').value = remarks || '';
    document.getElementById('verifyModal').style.display = 'flex';
}

function closeVerifyModal() {
    document.getElementById('verifyModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
