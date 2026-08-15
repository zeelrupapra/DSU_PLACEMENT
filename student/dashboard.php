<?php
require_once __DIR__ . '/../config/db.php';
requireStudent();

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];

$stmtSt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmtSt->execute([$studentId]);
$student = $stmtSt->fetch();

if (!$student) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// Fetch applied drives for student
$stmtApps = $pdo->prepare("SELECT a.*, d.title, d.package_ctc, c.company_name FROM applications a JOIN drives d ON a.drive_id = d.id JOIN companies c ON d.company_id = c.id WHERE a.student_id = ? ORDER BY a.id DESC");
$stmtApps->execute([$studentId]);
$myApplications = $stmtApps->fetchAll();

// Fetch applied drives map for student
$userAppsMap = [];
foreach ($myApplications as $ua) {
    $userAppsMap[$ua['drive_id']] = $ua;
}

$isPlaced = ($student['placement_status'] === 'Placed');
$isDetained = ($student['is_detained'] == 1);

// Eligible Active Open Drives
$stmtEligible = $pdo->prepare("SELECT d.*, c.company_name 
                               FROM drives d 
                               JOIN companies c ON d.company_id = c.id 
                               WHERE c.is_closed = 0 AND d.status = 'Active' AND d.min_cpi <= ? AND d.deadline >= CURDATE() 
                               ORDER BY d.id DESC");
$stmtEligible->execute([$student['cpi']]);
$eligibleDrivesRaw = $stmtEligible->fetchAll();

// Filter by branch eligibility
$studentBranch = strtoupper(trim($student['branch']));
$eligibleDrives = [];
foreach ($eligibleDrivesRaw as $ed) {
    $allowedBranches = array_map('trim', explode(',', strtoupper($ed['allowed_branches'])));
    if (in_array('ALL', $allowedBranches) || in_array($studentBranch, $allowedBranches)) {
        $eligibleDrives[] = $ed;
    }
}

$pageTitle = 'Student Portal Dashboard';
$currentPage = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<!-- Hero Banner Card -->
<div class="kpi-card" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; margin-bottom: 28px; padding: 30px; border-radius: 24px; box-shadow: 0 12px 30px -10px rgba(15,23,42,0.3);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 800; color: #FFFFFF;">Welcome back, <?= htmlspecialchars($student['full_name']); ?>! 👋</h2>
            <p style="font-size: 13px; color: #94A3B8; margin-top: 4px; font-weight: 600;">
                Enrollment Code: <code style="color: #60A5FA; background: rgba(59,130,246,0.15); padding: 3px 8px; border-radius: 6px;"><?= htmlspecialchars($student['enrollment_no']); ?></code> &nbsp;|&nbsp; 
                Branch: <strong><?= htmlspecialchars($student['branch']); ?></strong> (Batch <?= $student['batch_year']; ?>)
            </p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 16px; text-align: center;">
                <span style="font-size: 11px; color: #94A3B8; font-weight: 700; text-transform: uppercase; display: block;">Academic CPI</span>
                <strong style="font-size: 18px; color: #38BDF8; font-weight: 800;">★ <?= number_format($student['cpi'], 2); ?></strong>
            </div>
            
            <div style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 16px; text-align: center;">
                <span style="font-size: 11px; color: #94A3B8; font-weight: 700; text-transform: uppercase; display: block;">Placement Status</span>
                <?php $stCls = ($student['placement_status']==='Placed')?'#10B981':(($student['placement_status']==='In-Process')?'#F59E0B':'#EF4444'); ?>
                <strong style="font-size: 14px; color: <?= $stCls; ?>; font-weight: 800;"><?= htmlspecialchars($student['placement_status']); ?></strong>
            </div>
        </div>
    </div>
</div>

<?php if ($isPlaced): ?>
    <!-- Placed Offer Letter Showcase Card -->
    <div class="kpi-card" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; margin-bottom: 28px; padding: 26px 30px; border-radius: 24px; box-shadow: 0 12px 30px -10px rgba(5,150,105,0.3); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;">
                📜
            </div>
            <div>
                <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">🎉 Verified Campus Placement</span>
                <h3 style="font-size: 19px; font-weight: 900; margin: 6px 0 4px 0; color: #FFFFFF;">
                    Placed at: <?= htmlspecialchars(!empty($student['placed_companies']) ? $student['placed_companies'] : 'Corporate Partner'); ?>
                </h3>
                <p style="font-size: 13px; opacity: 0.9; margin: 0; font-weight: 600;">Issued by Training & Placement Cell, Dr. Subhash University. Official candidate record verified.</p>
            </div>
        </div>
        <?php if (!empty($student['offer_letter'])): ?>
            <a href="../view_offer.php?file=<?= urlencode($student['offer_letter']); ?>" target="_blank" style="background: #FFFFFF; color: #047857; font-weight: 800; padding: 12px 24px; border-radius: 14px; text-decoration: none; font-size: 14px; box-shadow: 0 6px 16px rgba(0,0,0,0.12); display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;">
                📜 View & Download Offer Letter (PDF)
            </a>
        <?php else: ?>
            <span style="background: rgba(255,255,255,0.2); color: #FFFFFF; font-weight: 800; padding: 10px 20px; border-radius: 14px; font-size: 13px;">
                ✓ Placement Confirmed
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isPlaced): ?>
    <div class="badge-status placed" style="margin-bottom: 24px; display: block; font-size: 14px; padding: 14px 20px; text-align: center; border-radius: 16px; font-weight: 800;">
        🎉 CONGRATULATIONS! You have been PLACED! As per university policy, placed candidates are exempt from applying to further campus drives.
    </div>
<?php endif; ?>

<?php if ($isDetained): ?>
    <div class="badge-status unplaced" style="margin-bottom: 24px; display: block; font-size: 14px; padding: 14px 20px; text-align: center; border-radius: 16px; font-weight: 800;">
        🚫 ACCOUNT DETAINED: Your candidate account has been marked as Detained by Placement Cell. You are currently ineligible to apply.
    </div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Applied Drives Table -->
    <div class="table-card" style="border-radius: 20px; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 16px; font-weight: 800; color: #0F172A;">💼 My Drive Applications (<?= count($myApplications); ?>)</h3>
            <a href="drives.php?tab=applied" style="font-size: 12px; font-weight: 800; color: #2563EB; text-decoration: none;">View All ↗</a>
        </div>

        <div class="table-responsive">
            <table class="custom-table" style="width: 100%;">
                <thead>
                    <tr style="background: #F8FAFC;">
                        <th>Recruiter Company</th>
                        <th>Role Title</th>
                        <th>Package CTC</th>
                        <th>Application Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($myApplications)): ?>
                        <?php foreach ($myApplications as $app): ?>
                            <tr>
                                <td><strong style="color: #0F172A; font-weight: 800;">🏢 <?= htmlspecialchars($app['company_name']); ?></strong></td>
                                <td style="color: #475569; font-size: 13px;"><?= htmlspecialchars($app['title']); ?></td>
                                <td><strong style="color: #10B981; font-weight: 800;"><?= htmlspecialchars($app['package_ctc']); ?></strong></td>
                                <td>
                                    <?php 
                                        $stClass = ($app['status']==='Selected') ? 'placed' : (($app['status']==='Rejected') ? 'unplaced' : 'in-process'); 
                                    ?>
                                    <span class="badge-status <?= $stClass; ?>" style="font-size: 12px; font-weight: 800;"><?= htmlspecialchars($app['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="color: var(--text-muted); text-align: center; padding: 28px;">You have not applied to any placement drives yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Eligible Drives Quick Apply -->
    <div class="table-card" style="border-radius: 20px; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 16px; font-weight: 800; color: #0F172A;">🚀 Eligible Drives for You</h3>
            <a href="drives.php?tab=eligible" style="font-size: 12px; font-weight: 800; color: #2563EB; text-decoration: none;">View All ↗</a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            <?php if (!empty($eligibleDrives)): ?>
                <?php foreach (array_slice($eligibleDrives, 0, 5) as $ed): 
                    $hasApplied = isset($userAppsMap[$ed['id']]);
                    $appData = $hasApplied ? $userAppsMap[$ed['id']] : null;

                    $isDeadlinePassed = false;
                    if (!empty($ed['deadline'])) {
                        $dlTimeStr = $ed['deadline'] . ' ' . ($ed['deadline_time'] ?: '23:59:59');
                        if (strtotime($dlTimeStr) < time()) {
                            $isDeadlinePassed = true;
                        }
                    }
                ?>
                    <div style="border: 1px solid #E2E8F0; padding: 16px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; background: #FFFFFF; flex-wrap: wrap; gap: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                        <div>
                            <strong style="font-size: 14px; color: #0F172A;"><?= htmlspecialchars($ed['title']); ?></strong><br>
                            <span style="font-size: 12px; color: #2563EB; font-weight: 700;">🏢 <?= htmlspecialchars($ed['company_name']); ?></span> &nbsp;•&nbsp; 
                            <span style="font-size: 12px; color: #10B981; font-weight: 800;"><?= htmlspecialchars($ed['package_ctc']); ?></span>
                        </div>

                        <div>
                            <?php if ($hasApplied): ?>
                                <?php 
                                    $appSt = $appData['status'];
                                    $stBadgeCls = ($appSt==='Selected') ? 'placed' : (($appSt==='Rejected') ? 'unplaced' : 'in-process');
                                ?>
                                <span class="badge-status <?= $stBadgeCls; ?>" style="font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 10px; display: inline-block;">
                                    Applied: <?= htmlspecialchars($appSt); ?>
                                </span>
                            <?php elseif ($isPlaced): ?>
                                <span class="badge-status placed" style="font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 10px; background: #DCFCE7; color: #15803D; border: 1px solid #86EFAC; display: inline-block;" title="Placed candidates are exempt from applying further">
                                    🎉 Already Placed
                                </span>
                            <?php elseif ($isDetained): ?>
                                <span class="badge-status unplaced" style="font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 10px; display: inline-block;">
                                    ⛔ Account Detained
                                </span>
                            <?php elseif ($isDeadlinePassed): ?>
                                <span class="badge-status" style="font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 10px; background: #F8FAFC; color: #94A3B8; border: 1px solid #E2E8F0; display: inline-block;">
                                    ⌛ Deadline Expired
                                </span>
                            <?php else: ?>
                                <a href="drives.php" class="btn-primary" style="padding: 8px 16px; font-size: 12px; font-weight: 800; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(59,130,246,0.25);">
                                    ⚡ Apply Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; background: #F8FAFC; border-radius: 14px; border: 1px dashed #CBD5E1;">
                    <p style="color: #64748B; font-size: 13px;">No active open drives matching your branch and CPI criteria right now.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
