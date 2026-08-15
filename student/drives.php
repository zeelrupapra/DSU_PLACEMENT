<?php
require_once __DIR__ . '/../config/db.php';
requireStudent();

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];
$msg = '';
$error = '';

// Fetch Logged-in Student details
$stmtSt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmtSt->execute([$studentId]);
$student = $stmtSt->fetch();

if (!$student) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// =========================================================================
// AJAX ENDPOINT: FETCH SINGLE DRIVE DETAILS FOR MODAL POPUP
// =========================================================================
if (isset($_GET['get_drive_details']) && isset($_GET['drive_id'])) {
    $dId = intval($_GET['drive_id']);
    $stmtDrv = $pdo->prepare("SELECT d.*, c.company_name, c.industry, c.website, c.hr_name, c.hr_email 
                              FROM drives d 
                              JOIN companies c ON d.company_id = c.id 
                              WHERE d.id = ?");
    $stmtDrv->execute([$dId]);
    $drvDetail = $stmtDrv->fetch();

    if (!$drvDetail) {
        echo "<div style='padding: 24px; text-align: center; color: #EF4444;'>Drive details not found.</div>";
        exit;
    }

    // Check if student applied to this drive
    $stmtAppChk = $pdo->prepare("SELECT status, remarks, applied_date FROM applications WHERE student_id = ? AND drive_id = ?");
    $stmtAppChk->execute([$studentId, $dId]);
    $myApp = $stmtAppChk->fetch();

    // Check eligibility
    $studentBranch = strtoupper(trim($student['branch']));
    $allowedBranches = array_map('trim', explode(',', strtoupper($drvDetail['allowed_branches'])));
    $isBranchEligible = in_array('ALL', $allowedBranches) || in_array($studentBranch, $allowedBranches);
    $isCpiEligible = ($student['cpi'] >= $drvDetail['min_cpi']);
    $isBacklogEligible = ($student['backlogs'] <= $drvDetail['max_backlogs']);
    $isDetained = ($student['is_detained'] == 1);
    $isPlaced = ($student['placement_status'] === 'Placed');

    $isDeadlinePassed = false;
    if ($drvDetail['deadline']) {
        $dlTimeStr = $drvDetail['deadline'] . ' ' . ($drvDetail['deadline_time'] ?: '23:59:59');
        if (strtotime($dlTimeStr) < time()) {
            $isDeadlinePassed = true;
        }
    }

    $isEligible = $isBranchEligible && $isCpiEligible && $isBacklogEligible;
    $canApply = $isEligible && !$isDetained && !$isPlaced && !$isDeadlinePassed;
    ?>

    <!-- Drive Header Card -->
    <div style="background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%); color: white; padding: 24px; border-radius: 20px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
            <div>
                <span style="font-size: 12px; font-weight: 800; background: rgba(59,130,246,0.25); color: #93C5FD; padding: 4px 10px; border-radius: 8px; border: 1px solid rgba(147,197,253,0.3); text-transform: uppercase;">
                    🏢 <?= htmlspecialchars($drvDetail['company_name']); ?> (<?= htmlspecialchars($drvDetail['industry'] ?: 'IT / Tech'); ?>)
                </span>
                <h2 style="font-size: 20px; font-weight: 800; color: #FFFFFF; margin-top: 8px;"><?= htmlspecialchars($drvDetail['title']); ?></h2>
                <p style="font-size: 13px; color: #94A3B8; margin-top: 4px;">Role Designation: <strong style="color: #60A5FA;"><?= htmlspecialchars($drvDetail['designation']); ?></strong></p>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 20px; font-weight: 800; color: #10B981; background: rgba(16,185,129,0.15); padding: 8px 16px; border-radius: 12px; border: 1px solid rgba(16,185,129,0.3); display: inline-block;">
                    💰 <?= htmlspecialchars($drvDetail['package_ctc']); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Eligibility Criteria Breakdown Grid -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px;">
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px; border-radius: 14px; text-align: center;">
            <span style="font-size: 11px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block; margin-bottom: 4px;">Academic CPI Rule</span>
            <strong style="font-size: 14px; color: #0F172A;">Min ★ <?= number_format($drvDetail['min_cpi'], 2); ?></strong>
            <div style="font-size: 11px; margin-top: 4px; font-weight: 700; color: <?= ($isCpiEligible ? '#10B981' : '#EF4444'); ?>;">
                <?= ($isCpiEligible ? '✓ Your CPI ('.number_format($student['cpi'], 2).') Meets Rule' : '✗ CPI Below Requirement'); ?>
            </div>
        </div>

        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px; border-radius: 14px; text-align: center;">
            <span style="font-size: 11px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block; margin-bottom: 4px;">Allowed Branches</span>
            <strong style="font-size: 13px; color: #0F172A;"><?= htmlspecialchars($drvDetail['allowed_branches']); ?></strong>
            <div style="font-size: 11px; margin-top: 4px; font-weight: 700; color: <?= ($isBranchEligible ? '#10B981' : '#EF4444'); ?>;">
                <?= ($isBranchEligible ? '✓ Your Branch ('.$student['branch'].') Eligible' : '✗ Branch Not Eligible'); ?>
            </div>
        </div>

        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px; border-radius: 14px; text-align: center;">
            <span style="font-size: 11px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block; margin-bottom: 4px;">Application Deadline</span>
            <strong style="font-size: 13px; color: #0F172A;"><?= date('d M Y', strtotime($drvDetail['deadline'])); ?> @ <?= date('h:i A', strtotime($drvDetail['deadline_time'] ?: '18:00')); ?></strong>
            <div style="font-size: 11px; margin-top: 4px; font-weight: 700; color: <?= ($isDeadlinePassed ? '#EF4444' : '#10B981'); ?>;">
                <?= ($isDeadlinePassed ? '⌛ Application Window Expired' : '🟢 Open for Applications'); ?>
            </div>
        </div>
    </div>

    <!-- Description & Agenda Section -->
    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
        <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 10px;">📋 Job Description & Selection Criteria</h4>
        <p style="font-size: 13px; color: #475569; line-height: 1.7; white-space: pre-line;">
            <?= htmlspecialchars($drvDetail['description'] ?: 'Recruiter has not specified extra descriptions. Apply with your profile resume.'); ?>
        </p>
    </div>

    <!-- Action Section -->
    <div style="text-align: right; border-top: 1px solid #E2E8F0; padding-top: 16px;">
        <?php if ($myApp): ?>
            <?php $stBadgeCls = ($myApp['status']==='Selected')?'placed':(($myApp['status']==='Rejected')?'unplaced':'in-process'); ?>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 13px; color: #64748B;">Applied on: <strong><?= date('d M Y, h:i A', strtotime($myApp['applied_date'])); ?></strong></span>
                <span class="badge-status <?= $stBadgeCls; ?>" style="font-size: 14px; padding: 10px 20px; font-weight: 800; border-radius: 12px;">
                    Application Pipeline Status: <?= htmlspecialchars($myApp['status']); ?>
                </span>
            </div>
        <?php elseif ($isPlaced): ?>
            <span class="badge-status placed" style="font-size: 14px; padding: 10px 20px; font-weight: 800; border-radius: 12px;">🎉 You are already PLACED! Exempt from further drive applications.</span>
        <?php elseif ($isDetained): ?>
            <span class="badge-status detained" style="font-size: 14px; padding: 10px 20px; font-weight: 800; border-radius: 12px;">⛔ Account Detained: Ineligible to apply.</span>
        <?php elseif ($isDeadlinePassed): ?>
            <span class="badge-status unplaced" style="font-size: 14px; padding: 10px 20px; font-weight: 800; border-radius: 12px;">⌛ Application Deadline Expired</span>
        <?php elseif (!$isEligible): ?>
            <span class="badge-status unplaced" style="font-size: 14px; padding: 10px 20px; font-weight: 800; border-radius: 12px;">⚠️ Ineligible for this Drive (CPI/Branch Criteria)</span>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="apply">
                <input type="hidden" name="drive_id" value="<?= $dId; ?>">
                <button type="submit" class="btn-primary" style="padding: 12px 28px; font-size: 14px; font-weight: 800; border-radius: 12px; box-shadow: 0 4px 14px rgba(59,130,246,0.3);">
                    ⚡ Submit 1-Click Application with Profile Resume
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php
    exit;
}

// =========================================================================
// POST ACTION HANDLER: 1-CLICK APPLICATION SUBMISSION
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    $driveId = intval($_POST['drive_id']);

    if ($student['is_detained'] == 1) {
        $error = "Account Detained by Admin - You are currently ineligible to apply for placement drives.";
    } elseif ($student['placement_status'] === 'Placed') {
        $error = "You are already PLACED! As per university policy, placed candidates are exempt from applying to further drives.";
    } elseif (empty($student['resume_file']) || $student['resume_file'] === 'default_resume.pdf') {
        $error = "Please upload your official Resume (PDF) in 'My Profile' before applying for campus drives.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insert Application
            $stmtApp = $pdo->prepare("INSERT INTO applications (drive_id, student_id, status) VALUES (?, ?, 'Applied')");
            $stmtApp->execute([$driveId, $studentId]);

            // Update student placement status to 'In-Process' if currently 'Unplaced'
            if ($student['placement_status'] === 'Unplaced') {
                $stmtStUp = $pdo->prepare("UPDATE students SET placement_status = 'In-Process' WHERE id = ?");
                $stmtStUp->execute([$studentId]);
                $student['placement_status'] = 'In-Process';
            }

            $pdo->commit();
            $msg = "Application submitted successfully! Your candidate pipeline status is set to In-Process.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "You have already applied for this placement drive.";
        }
    }
}

// Filtering & Tab parameters
$filterTab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'all'; // 'all', 'eligible', 'applied'
$searchQ = isset($_GET['q']) ? sanitize($_GET['q']) : '';

// Fetch all active open placement drives from non-closed corporate partners
$paramsDrives = [];
if ($filterTab === 'applied') {
    $qDrives = "SELECT d.*, c.company_name, c.logo, c.industry, c.website 
                FROM drives d 
                JOIN companies c ON d.company_id = c.id 
                JOIN applications a ON a.drive_id = d.id 
                WHERE a.student_id = ?";
    $paramsDrives[] = $studentId;
} else {
    $qDrives = "SELECT d.*, c.company_name, c.logo, c.industry, c.website 
                FROM drives d 
                JOIN companies c ON d.company_id = c.id 
                WHERE c.is_closed = 0 AND d.status IN ('Active', 'Upcoming')";
}

if ($searchQ) {
    $qDrives .= " AND (d.title LIKE ? OR c.company_name LIKE ? OR d.designation LIKE ? OR d.location LIKE ?)";
    $paramsDrives[] = "%$searchQ%";
    $paramsDrives[] = "%$searchQ%";
    $paramsDrives[] = "%$searchQ%";
    $paramsDrives[] = "%$searchQ%";
}

$qDrives .= " ORDER BY d.id DESC";
$stmtDrives = $pdo->prepare($qDrives);
$stmtDrives->execute($paramsDrives);
$allDrives = $stmtDrives->fetchAll();

// Fetch student's existing applications map (drive_id => application array)
$stmtUserApps = $pdo->prepare("SELECT a.*, d.title, c.company_name 
                               FROM applications a 
                               JOIN drives d ON a.drive_id = d.id 
                               JOIN companies c ON d.company_id = c.id 
                               WHERE a.student_id = ?");
$stmtUserApps->execute([$studentId]);
$userAppsRaw = $stmtUserApps->fetchAll();

$userAppsMap = [];
foreach ($userAppsRaw as $ua) {
    $userAppsMap[$ua['drive_id']] = $ua;
}

// Student eligibility helper checks
$studentBranch = strtoupper(trim($student['branch']));
$studentCpi = floatval($student['cpi']);
$studentBacklogs = intval($student['backlogs']);
$isDetained = ($student['is_detained'] == 1);
$isPlaced = ($student['placement_status'] === 'Placed');

$pageTitle = 'Current Openings & Campus Drives';
$currentPage = 'drives';
include __DIR__ . '/../includes/header.php';
?>

<!-- Executive Banner -->
<div style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; padding: 28px 32px; border-radius: 24px; margin-bottom: 28px; box-shadow: 0 12px 30px -10px rgba(15, 23, 42, 0.3);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h3 style="font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.5px; margin-bottom: 6px;">
                🚀 Campus Recruitment Drives & Current Openings
            </h3>
            <p style="font-size: 13px; color: #94A3B8; font-weight: 600;">
                Explore active campus opportunities, check eligibility criteria, and submit 1-click applications with your profile resume.
            </p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="drives_history.php" class="btn-secondary" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
                📜 Past Openings & History
            </a>
        </div>
    </div>
</div>

<?php if ($student['placement_status'] === 'Placed'): ?>
    <div class="badge-status placed" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 14px 20px; text-align: center; border-radius: 14px; font-weight: 800;">
        🎉 CONGRATULATIONS! You have been PLACED! As per university policy, placed candidates are exempt from applying to further drives.
    </div>
<?php endif; ?>

<?php if ($student['is_detained'] == 1): ?>
    <div class="badge-status unplaced" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 14px 20px; text-align: center; border-radius: 14px; font-weight: 800;">
        🚫 ACCOUNT DETAINED: Your candidate account has been marked as Detained by Placement Cell. You are currently ineligible to apply.
    </div>
<?php endif; ?>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 20px; display: inline-block; font-size: 14px; padding: 10px 18px; border-radius: 12px; font-weight: 800;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge-status unplaced" style="margin-bottom: 20px; display: inline-block; font-size: 14px; padding: 10px 18px; border-radius: 12px; font-weight: 800;"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Tab Filter Bar & Search Container -->
<div class="table-card" style="padding: 16px 24px; margin-bottom: 28px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.02);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; background: #F1F5F9; border-radius: 14px; padding: 4px;">
            <a href="drives.php?tab=all&q=<?= urlencode($searchQ); ?>" class="btn-action-icon" style="border: none; padding: 9px 18px; font-weight: 800; border-radius: 10px; <?= ($filterTab==='all')?'background:white; color:#2563EB; box-shadow:0 4px 10px rgba(0,0,0,0.06);':'background:transparent; color:#64748B;'; ?>">
                🌐 All Open Drives (<?= count($allDrives); ?>)
            </a>
            <a href="drives.php?tab=eligible&q=<?= urlencode($searchQ); ?>" class="btn-action-icon" style="border: none; padding: 9px 18px; font-weight: 800; border-radius: 10px; <?= ($filterTab==='eligible')?'background:white; color:#2563EB; box-shadow:0 4px 10px rgba(0,0,0,0.06);':'background:transparent; color:#64748B;'; ?>">
                🎯 Eligible Drives for Me
            </a>
            <a href="drives.php?tab=applied&q=<?= urlencode($searchQ); ?>" class="btn-action-icon" style="border: none; padding: 9px 18px; font-weight: 800; border-radius: 10px; <?= ($filterTab==='applied')?'background:white; color:#2563EB; box-shadow:0 4px 10px rgba(0,0,0,0.06);':'background:transparent; color:#64748B;'; ?>">
                💼 My Applications (<?= count($userAppsMap); ?>)
            </a>
        </div>

        <form method="GET" style="display: flex; gap: 8px;">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($filterTab); ?>">
            <div style="position: relative;">
                <input type="text" name="q" value="<?= htmlspecialchars($searchQ); ?>" placeholder="Search drive / company..." style="padding: 9px 16px 9px 36px; border-radius: 12px; border: 1px solid #CBD5E1; font-size: 13px; outline: none; width: 220px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
            <button type="submit" class="btn-primary" style="padding: 9px 16px; font-size: 13px; border-radius: 12px;">Search</button>
        </form>
    </div>
</div>

<!-- Dynamic Open Drives Grid Layout -->
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 28px;">
    <?php 
    $renderedCount = 0;
    foreach ($allDrives as $drv): 
        $allowedBranches = array_map('trim', explode(',', strtoupper($drv['allowed_branches'])));
        $isBranchEligible = in_array('ALL', $allowedBranches) || in_array($studentBranch, $allowedBranches);
        $isCpiEligible = ($studentCpi >= floatval($drv['min_cpi']));
        $isBacklogEligible = ($studentBacklogs <= intval($drv['max_backlogs']));
        
        $isDeadlinePassed = false;
        if ($drv['deadline']) {
            $dlTimeStr = $drv['deadline'] . ' ' . ($drv['deadline_time'] ?: '23:59:59');
            if (strtotime($dlTimeStr) < time()) {
                $isDeadlinePassed = true;
            }
        }

        $isEligible = $isBranchEligible && $isCpiEligible && $isBacklogEligible;
        $hasApplied = isset($userAppsMap[$drv['id']]);
        $appData = $hasApplied ? $userAppsMap[$drv['id']] : null;

        // Apply Tab Filter logic
        if ($filterTab === 'eligible' && !$isEligible) continue;
        if ($filterTab === 'applied' && !$hasApplied) continue;

        $renderedCount++;
    ?>
        <div class="kpi-card" style="padding: 24px; border-radius: 24px; background: white; border: 1px solid #E2E8F0; box-shadow: 0 4px 14px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <!-- Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; gap: 12px;">
                    <div>
                        <span style="font-size: 12px; font-weight: 800; color: #2563EB; background: #EFF6FF; padding: 4px 10px; border-radius: 8px; border: 1px solid #BFDBFE;">
                            🏢 <?= htmlspecialchars($drv['company_name']); ?>
                        </span>
                        <h4 style="font-size: 18px; font-weight: 800; color: #0F172A; margin-top: 8px; line-height: 1.3;">
                            <?= htmlspecialchars($drv['title']); ?>
                        </h4>
                        <p style="font-size: 12px; color: #64748B; font-weight: 600; margin-top: 2px;">
                            Role Designation: <strong style="color: #334155;"><?= htmlspecialchars($drv['designation']); ?></strong>
                        </p>
                    </div>

                    <div style="text-align: right; flex-shrink: 0;">
                        <span style="font-size: 15px; font-weight: 800; color: #10B981; background: #DCFCE7; padding: 6px 14px; border-radius: 12px; border: 1px solid #86EFAC; display: inline-block;">
                            💰 <?= htmlspecialchars($drv['package_ctc']); ?>
                        </span>
                    </div>
                </div>

                <!-- Drive Specifications -->
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; padding: 14px 16px; margin-bottom: 20px; font-size: 12.5px; line-height: 1.8; color: #334155;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B; font-weight: 700;">📍 Location:</span>
                        <strong style="color: #0F172A;"><?= htmlspecialchars($drv['location']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B; font-weight: 700;">🎓 Required Min CPI:</span>
                        <strong style="color: <?= ($isCpiEligible ? '#10B981' : '#EF4444'); ?>;">★ <?= number_format($drv['min_cpi'], 2); ?> (Your CPI: <?= number_format($studentCpi, 2); ?>)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B; font-weight: 700;">📚 Allowed Branches:</span>
                        <strong style="color: <?= ($isBranchEligible ? '#2563EB' : '#EF4444'); ?>;"><?= htmlspecialchars($drv['allowed_branches']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B; font-weight: 700;">📅 Drive Date:</span>
                        <strong style="color: #0F172A;"><?= date('d M Y', strtotime($drv['drive_date'])); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B; font-weight: 700;">⌛ Application Deadline:</span>
                        <strong style="color: <?= ($isDeadlinePassed ? '#EF4444' : '#F59E0B'); ?>;">
                            <?= date('d M Y', strtotime($drv['deadline'])); ?> @ <?= date('h:i A', strtotime($drv['deadline_time'] ?: '18:00')); ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Dynamic Action Controls -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button onclick="viewDriveDetails(<?= $drv['id']; ?>)" class="btn-secondary" style="width: 100%; justify-content: center; font-size: 13px; font-weight: 700; border-radius: 12px; padding: 9px;">
                    🔍 View Details & Job Description
                </button>

                <?php if ($hasApplied): ?>
                    <?php 
                        $appSt = $appData['status'];
                        $stBadgeCls = ($appSt==='Selected')?'placed':(($appSt==='Rejected')?'unplaced':'in-process');
                    ?>
                    <button class="btn-secondary" style="width: 100%; justify-content: center; background: #F1F5F9; color: #334155; font-weight: 800; border-radius: 12px; padding: 10px; cursor: default;" disabled>
                        <span class="badge-status <?= $stBadgeCls; ?>" style="font-size: 12px;">Applied Stage: <?= htmlspecialchars($appSt); ?></span>
                    </button>
                <?php elseif ($isPlaced): ?>
                    <button class="btn-secondary" style="width: 100%; justify-content: center; background: #DCFCE7; color: #15803D; font-weight: 800; border-radius: 12px; padding: 10px; cursor: not-allowed;" disabled>
                        🎉 Already Placed
                    </button>
                <?php elseif ($isDetained): ?>
                    <button class="btn-secondary" style="width: 100%; justify-content: center; background: #FEE2E2; color: #B91C1C; font-weight: 800; border-radius: 12px; padding: 10px; cursor: not-allowed;" disabled>
                        ⛔ Ineligible (Account Detained)
                    </button>
                <?php elseif ($isDeadlinePassed): ?>
                    <button class="btn-secondary" style="width: 100%; justify-content: center; background: #F8FAFC; color: #94A3B8; font-weight: 700; border-radius: 12px; padding: 10px; cursor: not-allowed;" disabled>
                        ⌛ Application Deadline Expired
                    </button>
                <?php elseif (!$isEligible): ?>
                    <button class="btn-secondary" style="width: 100%; justify-content: center; background: #FFFBEB; color: #B45309; font-weight: 700; border-radius: 12px; padding: 10px; cursor: not-allowed;" disabled>
                        ⚠️ Ineligible (CPI / Branch Criteria)
                    </button>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="apply">
                        <input type="hidden" name="drive_id" value="<?= $drv['id']; ?>">
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px; padding: 10px; font-weight: 800; box-shadow: 0 4px 12px rgba(59,130,246,0.25);">
                            ⚡ 1-Click Apply with Profile Resume
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($renderedCount === 0): ?>
    <div style="text-align: center; padding: 50px 20px; background: white; border-radius: 20px; border: 1px dashed #CBD5E1; margin-bottom: 28px;">
        <div style="font-size: 36px; margin-bottom: 12px;">🚀</div>
        <h4 style="font-size: 16px; font-weight: 800; color: #0F172A;">No Openings Match Filter</h4>
        <p style="color: #64748B; font-size: 13px; margin-top: 4px;">No recruitment drives matching your selected tab or search query.</p>
    </div>
<?php endif; ?>

<!-- Drive Details Modal Popup -->
<div class="modal-overlay" id="driveDetailsModal">
    <div class="modal-box full-size" style="max-width: 840px !important; border-radius: 24px !important; height: auto !important; max-height: 85vh !important;">
        <div class="modal-header" style="border-bottom: 1px solid #E2E8F0; padding-bottom: 16px;">
            <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Recruitment Drive Specification</h3>
            <button class="close-modal" onclick="closeModal('driveDetailsModal')">&times;</button>
        </div>
        <div id="driveDetailsContainer" class="dossier-scroll-area" style="padding-top: 16px;">
            <p style="color: var(--text-muted); text-align: center;">Loading drive specifications...</p>
        </div>
    </div>
</div>

<script>
function viewDriveDetails(driveId) {
    const container = document.getElementById('driveDetailsContainer');
    container.innerHTML = "<p style='color: var(--text-muted); text-align: center; padding: 30px;'>Loading drive specifications...</p>";
    openModal('driveDetailsModal');
    
    if (window.showToast) {
        window.showToast('🔍 Opening Opening Details', 'Fetching job description and drive requirements...', 'info');
    }

    fetch('drives.php?get_drive_details=1&drive_id=' + driveId)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
