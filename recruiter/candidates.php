<?php
require_once __DIR__ . '/../config/db.php';
requireRecruiter();

$pdo = getDBConnection();
$companyId = $_SESSION['company_id'];

// Handle status updates from recruiter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_candidate_status') {
    $appId = intval($_POST['app_id']);
    $newStatus = sanitize($_POST['status']);
    $remarks = sanitize($_POST['remarks'] ?? '');

    // Verify application belongs to this company's drive
    $stmtVerify = $pdo->prepare("SELECT a.*, d.company_id, d.title as drive_title, d.package_ctc, c.company_name, s.full_name 
                                 FROM applications a 
                                 JOIN drives d ON a.drive_id = d.id 
                                 JOIN companies c ON d.company_id = c.id 
                                 JOIN students s ON a.student_id = s.id 
                                 WHERE a.id = ? AND d.company_id = ?");
    $stmtVerify->execute([$appId, $companyId]);
    $appData = $stmtVerify->fetch();

    if ($appData) {
        if ($newStatus === 'Selected') {
            $check = canSelectStudentForCompany($pdo, $appData['student_id'], $appData['drive_id']);
            if (!$check['allowed']) {
                setFlashMessage($check['reason'], 'danger');
                header("Location: candidates.php" . (isset($_GET['drive_id']) ? "?drive_id=" . intval($_GET['drive_id']) : ""));
                exit;
            }

            $stmtUp = $pdo->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?");
            $stmtUp->execute([$newStatus, $remarks, $appId]);

            $compTag = $appData['company_name'] . ' (' . $appData['package_ctc'] . ')';
            $numPackage = parseLpaValue($appData['package_ctc']);
            $pdo->prepare("UPDATE students SET placement_status = 'Placed', placed_companies = ?, current_package_lpa = ? WHERE id = ?")
                ->execute([$compTag, $numPackage > 0 ? $numPackage : null, $appData['student_id']]);

            setFlashMessage("Candidate " . htmlspecialchars($appData['full_name']) . " marked as SELECTED for " . htmlspecialchars($compTag) . "!", 'success');
        } else {
            $stmtUp = $pdo->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?");
            $stmtUp->execute([$newStatus, $remarks, $appId]);

            // If demoting from Selected
            if ($appData['status'] === 'Selected') {
                $otherSel = $pdo->query("SELECT COUNT(*) FROM applications WHERE student_id = {$appData['student_id']} AND status = 'Selected'")->fetchColumn();
                if ($otherSel == 0) {
                    $pdo->prepare("UPDATE students SET placement_status = 'In-Process', placed_companies = NULL, current_package_lpa = NULL WHERE id = ?")->execute([$appData['student_id']]);
                }
            }

            setFlashMessage("Candidate application updated to " . htmlspecialchars($newStatus) . "!", 'info');
        }
    }
    header("Location: candidates.php" . (isset($_GET['drive_id']) ? "?drive_id=" . intval($_GET['drive_id']) : ""));
    exit;
}

// Filters
$driveFilter = isset($_GET['drive_id']) ? intval($_GET['drive_id']) : 0;
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$branchFilter = isset($_GET['branch']) ? sanitize($_GET['branch']) : '';
$searchQ = isset($_GET['q']) ? sanitize($_GET['q']) : '';

// Fetch all drives for filter dropdown
$stmtAllDrives = $pdo->prepare("SELECT id, title, package_ctc, status FROM drives WHERE company_id = ? ORDER BY id DESC");
$stmtAllDrives->execute([$companyId]);
$companyDrives = $stmtAllDrives->fetchAll();

// Build query
$where = "WHERE d.company_id = ?";
$params = [$companyId];

if ($driveFilter > 0) {
    $where .= " AND d.id = ?";
    $params[] = $driveFilter;
}
if (!empty($statusFilter)) {
    $where .= " AND a.status = ?";
    $params[] = $statusFilter;
}
if (!empty($branchFilter)) {
    $where .= " AND s.branch = ?";
    $params[] = $branchFilter;
}
if (!empty($searchQ)) {
    $where .= " AND (s.full_name LIKE ? OR s.enrollment_no LIKE ? OR s.email LIKE ?)";
    $params[] = "%$searchQ%";
    $params[] = "%$searchQ%";
    $params[] = "%$searchQ%";
}

// Export CSV handler
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=candidates_pipeline_' . date('Y-m-d') . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Enrollment No', 'Candidate Name', 'Email', 'Phone', 'Gender', 'Branch', 'CPI', 'Backlogs', 'Semester', 'Drive Title', 'Application Status', 'Remarks', 'Applied Date']);

    $qExp = "SELECT a.*, s.full_name, s.enrollment_no, s.email, s.phone, s.gender, s.branch, s.cpi, s.backlogs, s.current_semester, d.title as drive_title 
             FROM applications a 
             JOIN students s ON a.student_id = s.id 
             JOIN drives d ON a.drive_id = d.id 
             $where ORDER BY a.id DESC";
    $stExp = $pdo->prepare($qExp);
    $stExp->execute($params);
    while ($row = $stExp->fetch()) {
        fputcsv($out, [
            $row['enrollment_no'],
            $row['full_name'],
            $row['email'],
            $row['phone'],
            $row['gender'],
            $row['branch'],
            $row['cpi'],
            $row['backlogs'],
            $row['current_semester'] ?: 'N/A',
            $row['drive_title'],
            $row['status'],
            $row['remarks'],
            $row['applied_date']
        ]);
    }
    fclose($out);
    exit;
}

$query = "SELECT a.*, s.full_name, s.enrollment_no, s.email, s.phone, s.gender, s.branch, s.cpi, s.backlogs, s.current_semester, s.resume_file, s.profile_photo, s.linkedin_url, d.title as drive_title, d.package_ctc 
          FROM applications a 
          JOIN students s ON a.student_id = s.id 
          JOIN drives d ON a.drive_id = d.id 
          $where ORDER BY a.id DESC";
$stmtCandidates = $pdo->prepare($query);
$stmtCandidates->execute($params);
$candidates = $stmtCandidates->fetchAll();

$pageTitle = 'Candidate Pipeline & Resumes';
$currentPage = 'candidates';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Toolbar -->
<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Candidate Applicant Pipeline & Resumes</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Review candidate dossiers, download verified PDF resumes, and update recruitment evaluation statuses.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="candidates.php?export_csv=1<?= $driveFilter ? '&drive_id=' . $driveFilter : ''; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?>" class="btn-secondary" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            📥 Export CSV Pipeline
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="table-card" style="padding: 18px; border-radius: 18px; background: white; border: 1px solid var(--border-color); margin-bottom: 24px;">
    <form method="GET" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="q" value="<?= htmlspecialchars($searchQ); ?>" placeholder="Search candidate name, enrollment code, or email..." class="form-control" style="border-radius: 10px; padding: 9px 14px;">
        </div>

        <div style="min-width: 200px;">
            <select name="drive_id" class="form-control" style="border-radius: 10px; padding: 9px 12px; font-weight: 700;">
                <option value="0">All Recruitment Drives</option>
                <?php foreach ($companyDrives as $cd): ?>
                    <option value="<?= $cd['id']; ?>" <?= ($driveFilter === intval($cd['id'])) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cd['title']); ?> (<?= htmlspecialchars($cd['package_ctc']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="min-width: 160px;">
            <select name="status" class="form-control" style="border-radius: 10px; padding: 9px 12px; font-weight: 700;">
                <option value="">All Application Statuses</option>
                <option value="Applied" <?= ($statusFilter === 'Applied') ? 'selected' : ''; ?>>Applied</option>
                <option value="Shortlisted" <?= ($statusFilter === 'Shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                <option value="Interview Round" <?= ($statusFilter === 'Interview Round') ? 'selected' : ''; ?>>Interview Round</option>
                <option value="Selected" <?= ($statusFilter === 'Selected') ? 'selected' : ''; ?>>Selected</option>
                <option value="Rejected" <?= ($statusFilter === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>

        <div style="min-width: 140px;">
            <select name="branch" class="form-control" style="border-radius: 10px; padding: 9px 12px; font-weight: 700;">
                <option value="">All Branches</option>
                <option value="CSE" <?= ($branchFilter === 'CSE') ? 'selected' : ''; ?>>CSE</option>
                <option value="IT" <?= ($branchFilter === 'IT') ? 'selected' : ''; ?>>IT</option>
                <option value="ECE" <?= ($branchFilter === 'ECE') ? 'selected' : ''; ?>>ECE</option>
                <option value="MECH" <?= ($branchFilter === 'MECH') ? 'selected' : ''; ?>>MECH</option>
                <option value="CIVIL" <?= ($branchFilter === 'CIVIL') ? 'selected' : ''; ?>>CIVIL</option>
            </select>
        </div>

        <button type="submit" class="btn-primary" style="border-radius: 10px; padding: 9px 18px; font-weight: 800;">
            Filter
        </button>
        <?php if ($searchQ || $driveFilter || $statusFilter || $branchFilter): ?>
            <a href="candidates.php" class="btn-secondary" style="border-radius: 10px; padding: 9px 14px; text-decoration: none; font-weight: 700;">
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Candidate Pipeline Table -->
<div class="table-card" style="padding: 24px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Candidate Profile</th>
                    <th>Branch & CPI</th>
                    <th>Drive Applied</th>
                    <th>Resume Dossier</th>
                    <th>Status</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($candidates)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 50px 20px; color: var(--text-muted);">
                            <div style="font-size: 36px; margin-bottom: 8px;">👥</div>
                            <strong style="font-size: 15px; color: #0F172A; display: block;">No Candidate Applications Found</strong>
                            <span style="font-size: 13px;">Candidates who apply for your active placement drives will appear here.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($candidates as $c): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php 
                                    $stPhoto = ($c['profile_photo'] && $c['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $c['profile_photo'])) 
                                        ? '../uploads/' . $c['profile_photo'] 
                                        : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($c['full_name']);
                                    ?>
                                    <img src="<?= $stPhoto; ?>" alt="Candidate Avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #E2E8F0;">
                                    <div>
                                        <strong style="font-size: 14px; color: #0F172A; display: block;"><?= htmlspecialchars($c['full_name']); ?></strong>
                                        <code style="font-size: 11.5px; color: #2563EB;"><?= htmlspecialchars($c['enrollment_no']); ?></code>
                                        <span style="font-size: 11px; color: #64748B; display: block;"><?= htmlspecialchars($c['email']); ?> • <?= htmlspecialchars($c['phone'] ?: 'No Phone'); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong style="font-size: 13px; color: #334155;"><?= htmlspecialchars($c['branch']); ?></strong> 
                                <?= $c['current_semester'] ? '<span style="font-size: 11px; color: #64748B;">(Sem ' . $c['current_semester'] . ')</span>' : ''; ?>
                                <div style="margin-top: 3px;">
                                    <span style="font-size: 13px; font-weight: 800; color: #2563EB;">★ <?= number_format($c['cpi'], 2); ?></span>
                                    <span style="font-size: 11px; color: <?= ($c['backlogs'] > 0) ? '#EF4444' : '#10B981'; ?>; font-weight: 700; margin-left: 6px;">
                                        <?= ($c['backlogs'] > 0) ? $c['backlogs'] . ' Backlog(s)' : '0 Backlogs'; ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <strong style="font-size: 13px; color: #0F172A; display: block;"><?= htmlspecialchars($c['drive_title']); ?></strong>
                                <span style="font-size: 12px; color: #059669; font-weight: 700;"><?= htmlspecialchars($c['package_ctc']); ?></span>
                                <span style="font-size: 11px; color: #94A3B8; display: block;">Applied: <?= date('M d, Y', strtotime($c['applied_date'])); ?></span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <?php if ($c['resume_file'] && $c['resume_file'] !== 'default_resume.pdf' && file_exists(__DIR__ . '/../uploads/' . $c['resume_file'])): ?>
                                        <a href="../uploads/<?= htmlspecialchars($c['resume_file']); ?>" target="_blank" class="btn-secondary" style="padding: 5px 10px; font-size: 11.5px; font-weight: 800; border-radius: 8px; color: #2563EB; border: 1px solid #BFDBFE; background: #EFF6FF; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; width: fit-content;">
                                            📄 View PDF Resume ↗
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #94A3B8;">No Resume</span>
                                    <?php endif; ?>

                                    <?php if (!empty($c['linkedin_url'])): ?>
                                        <a href="<?= htmlspecialchars($c['linkedin_url']); ?>" target="_blank" style="font-size: 11.5px; font-weight: 800; color: #0A66C2; text-decoration: none; display: inline-flex; align-items: center; gap: 3px;">
                                            🌐 LinkedIn Profile ↗
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $cStCls = ($c['status'] === 'Selected') ? 'placed' : (($c['status'] === 'Shortlisted' || $c['status'] === 'Interview Round') ? 'in-process' : (($c['status'] === 'Rejected') ? 'unplaced' : 'info'));
                                ?>
                                <span class="badge-status <?= $cStCls; ?>" style="font-weight: 800;"><?= htmlspecialchars($c['status']); ?></span>
                                <?php if (!empty($c['remarks'])): ?>
                                    <span style="display: block; font-size: 11px; color: #64748B; margin-top: 4px; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($c['remarks']); ?>">
                                        💬 <?= htmlspecialchars($c['remarks']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn-primary" onclick="openEvaluationModal(<?= $c['id']; ?>, '<?= addslashes($c['full_name']); ?>', '<?= $c['status']; ?>', '<?= addslashes($c['remarks'] ?? ''); ?>')" style="padding: 7px 14px; font-size: 12px; border-radius: 8px; font-weight: 800;">
                                    ⚙️ Evaluate
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Candidate Evaluation Status Update -->
<div id="evalModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(6px); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 20px; width: 90%; max-width: 460px; padding: 28px; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Update Candidate Evaluation</h3>
            <button onclick="closeEvaluationModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748B;">✕</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="update_candidate_status">
            <input type="hidden" name="app_id" id="modalAppId">

            <p style="font-size: 13px; color: #64748B; margin-bottom: 16px;">
                Evaluating candidate: <strong id="modalCandidateName" style="color: #0F172A;"></strong>
            </p>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Application Status *</label>
                <select name="status" id="modalStatusSelect" class="form-control" required style="border-radius: 10px; padding: 10px 12px; font-weight: 700;">
                    <option value="Applied">Applied (Initial)</option>
                    <option value="Shortlisted">Shortlisted for Assessment</option>
                    <option value="Interview Round">Technical / HR Interview Round</option>
                    <option value="Selected">Selected / Final Offer</option>
                    <option value="Rejected">Rejected / Ineligible</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Evaluation Remarks / Interview Feedback</label>
                <textarea name="remarks" id="modalRemarksText" class="form-control" rows="3" placeholder="e.g. Cleared round 1 technical coding; interview scheduled for tomorrow." style="border-radius: 10px; padding: 10px 12px; font-size: 13px;"></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeEvaluationModal()" class="btn-secondary" style="flex: 1; justify-content: center; border-radius: 10px; padding: 12px; font-weight: 800;">Cancel</button>
                <button type="submit" class="btn-primary" style="flex: 1; justify-content: center; border-radius: 10px; padding: 12px; font-weight: 800;">Save Evaluation</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEvaluationModal(appId, name, status, remarks) {
    document.getElementById('modalAppId').value = appId;
    document.getElementById('modalCandidateName').innerText = name;
    document.getElementById('modalStatusSelect').value = status;
    document.getElementById('modalRemarksText').value = remarks || '';
    document.getElementById('evalModal').style.display = 'flex';
}

function closeEvaluationModal() {
    document.getElementById('evalModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
