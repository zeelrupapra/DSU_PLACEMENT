<?php
require_once __DIR__ . '/../config/db.php';
requireRecruiter();

$pdo = getDBConnection();
$companyId = $_SESSION['company_id'];

// Fetch company details
$stmtComp = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmtComp->execute([$companyId]);
$company = $stmtComp->fetch();

if (!$company) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// Metrics for recruiter
$stmtDrivesCount = $pdo->prepare("SELECT COUNT(*) FROM drives WHERE company_id = ?");
$stmtDrivesCount->execute([$companyId]);
$totalDrives = $stmtDrivesCount->fetchColumn();

$stmtTotalApps = $pdo->prepare("SELECT COUNT(a.id) FROM applications a JOIN drives d ON a.drive_id = d.id WHERE d.company_id = ?");
$stmtTotalApps->execute([$companyId]);
$totalApplicants = $stmtTotalApps->fetchColumn();

$stmtShortlisted = $pdo->prepare("SELECT COUNT(a.id) FROM applications a JOIN drives d ON a.drive_id = d.id WHERE d.company_id = ? AND a.status IN ('Shortlisted', 'Interview Round')");
$stmtShortlisted->execute([$companyId]);
$shortlistedCount = $stmtShortlisted->fetchColumn();

$stmtSelected = $pdo->prepare("SELECT COUNT(a.id) FROM applications a JOIN drives d ON a.drive_id = d.id WHERE d.company_id = ? AND a.status = 'Selected'");
$stmtSelected->execute([$companyId]);
$selectedCount = $stmtSelected->fetchColumn();

// Fetch drives created for this company
$stmtDrives = $pdo->prepare("SELECT d.*, 
                             (SELECT COUNT(*) FROM applications a WHERE a.drive_id = d.id) as app_count,
                             (SELECT COUNT(*) FROM applications a WHERE a.drive_id = d.id AND a.status = 'Selected') as hired_count
                             FROM drives d 
                             WHERE d.company_id = ? 
                             ORDER BY d.id DESC");
$stmtDrives->execute([$companyId]);
$myDrives = $stmtDrives->fetchAll();

// Recent applications
$stmtRecent = $pdo->prepare("SELECT a.*, s.full_name, s.enrollment_no, s.branch, s.cpi, s.resume_file, d.title as drive_title 
                             FROM applications a 
                             JOIN students s ON a.student_id = s.id 
                             JOIN drives d ON a.drive_id = d.id 
                             WHERE d.company_id = ? 
                             ORDER BY a.id DESC LIMIT 8");
$stmtRecent->execute([$companyId]);
$recentApplicants = $stmtRecent->fetchAll();

$pageTitle = 'Recruiter Dashboard';
$currentPage = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="kpi-card" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; margin-bottom: 28px; padding: 30px; border-radius: 24px; box-shadow: 0 12px 30px -10px rgba(15,23,42,0.3);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php 
            $compLogo = ($company['logo'] && $company['logo'] !== 'default_company.png' && file_exists(__DIR__ . '/../uploads/' . $company['logo'])) 
                ? '../uploads/' . $company['logo'] 
                : 'https://api.dicebear.com/7.x/identicon/svg?seed=' . urlencode($company['company_name']);
            ?>
            <img src="<?= $compLogo; ?>" alt="Company Logo" style="width: 64px; height: 64px; border-radius: 16px; object-fit: contain; background: white; padding: 6px; box-shadow: 0 4px 14px rgba(0,0,0,0.2);">
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: #FFFFFF;">Welcome, <?= htmlspecialchars($company['company_name']); ?>! 🏢</h2>
                <p style="font-size: 13px; color: #94A3B8; margin-top: 4px; font-weight: 600;">
                    HR Contact: <strong style="color: #60A5FA;"><?= htmlspecialchars($company['hr_name'] ?: 'Corporate HR'); ?></strong> (<?= htmlspecialchars($company['hr_email'] ?: 'HR Portal'); ?>) &nbsp;|&nbsp; 
                    Industry: <strong><?= htmlspecialchars($company['industry'] ?: 'Technology'); ?></strong>
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="candidates.php" class="btn-primary" style="border-radius: 14px; padding: 12px 22px; font-weight: 800; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                👥 Candidate Pipeline ➔
            </a>
        </div>
    </div>
</div>

<!-- KPI Summary Cards Row -->
<div class="kpi-grid" style="margin-bottom: 28px;">
    <div class="kpi-card" style="animation-delay: 0.05s;">
        <div class="kpi-top">
            <span class="kpi-title">Recruitment Drives</span>
            <div class="kpi-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($totalDrives); ?></div>
        <div class="kpi-badge info">Active & Completed</div>
    </div>

    <div class="kpi-card" style="animation-delay: 0.1s;">
        <div class="kpi-top">
            <span class="kpi-title">Total Applicants</span>
            <div class="kpi-icon purple">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($totalApplicants); ?></div>
        <div class="kpi-badge neutral">1-Click Submissions</div>
    </div>

    <div class="kpi-card" style="animation-delay: 0.15s;">
        <div class="kpi-top">
            <span class="kpi-title">Shortlisted & Interview</span>
            <div class="kpi-icon orange">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($shortlistedCount); ?></div>
        <div class="kpi-badge in-process">In Evaluation</div>
    </div>

    <div class="kpi-card" style="animation-delay: 0.2s;">
        <div class="kpi-top">
            <span class="kpi-title">Selected Candidates</span>
            <div class="kpi-icon green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($selectedCount); ?></div>
        <div class="kpi-badge placed">Hired / Offered</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px;">
    
    <!-- LEFT: RECRUITMENT DRIVES TABLE -->
    <div class="table-card" style="padding: 24px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h4 style="font-weight: 800; font-size: 17px; color: #0F172A;">
                🚀 Your Campus Recruitment Drives
            </h4>
            <a href="candidates.php" style="font-size: 12.5px; font-weight: 700; color: #2563EB; text-decoration: none;">View Candidate Dossiers →</a>
        </div>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Drive Title & Role</th>
                        <th>Package (CTC)</th>
                        <th>Drive Date</th>
                        <th>Status</th>
                        <th>Applicants</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myDrives)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No placement drives assigned yet. Placement cell will configure recruitment drives for your company.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myDrives as $d): ?>
                            <tr>
                                <td>
                                    <strong style="color: #0F172A; font-size: 13.5px; display: block;"><?= htmlspecialchars($d['title']); ?></strong>
                                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;"><?= htmlspecialchars($d['designation']); ?> • <?= htmlspecialchars($d['location']); ?></span>
                                </td>
                                <td>
                                    <strong style="color: #059669; font-size: 13.5px;"><?= htmlspecialchars($d['package_ctc']); ?></strong>
                                </td>
                                <td>
                                    <span style="font-size: 12.5px; font-weight: 700; color: #334155;"><?= date('M d, Y', strtotime($d['drive_date'])); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $stBadge = ($d['status'] === 'Active') ? 'placed' : (($d['status'] === 'Upcoming') ? 'info' : 'neutral');
                                    ?>
                                    <span class="badge-status <?= $stBadge; ?>"><?= htmlspecialchars($d['status']); ?></span>
                                </td>
                                <td>
                                    <span style="font-weight: 800; font-size: 14px; color: #2563EB;"><?= $d['app_count']; ?></span> 
                                    <span style="font-size: 11px; color: #059669; font-weight: 700;">(<?= $d['hired_count']; ?> Selected)</span>
                                </td>
                                <td>
                                    <a href="candidates.php?drive_id=<?= $d['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size: 12px; border-radius: 8px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        Review ➔
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RIGHT: RECENT APPLICANTS STREAM -->
    <div class="table-card" style="padding: 24px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <h4 style="font-weight: 800; font-size: 17px; color: #0F172A; margin-bottom: 16px;">
            ⚡ Recent Applicants
        </h4>

        <?php if (empty($recentApplicants)): ?>
            <p style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 30px 0;">No applicant submissions yet.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($recentApplicants as $ra): ?>
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px 16px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                        <div>
                            <strong style="font-size: 13.5px; color: #0F172A; display: block;"><?= htmlspecialchars($ra['full_name']); ?></strong>
                            <span style="font-size: 11.5px; color: #64748B; font-weight: 600;">
                                <?= htmlspecialchars($ra['branch']); ?> • CPI: <strong style="color: #2563EB;"><?= number_format($ra['cpi'], 2); ?></strong>
                            </span>
                        </div>
                        <div style="text-align: right;">
                            <?php 
                            $appStCls = ($ra['status'] === 'Selected') ? 'placed' : (($ra['status'] === 'Shortlisted' || $ra['status'] === 'Interview Round') ? 'in-process' : (($ra['status'] === 'Rejected') ? 'unplaced' : 'info'));
                            ?>
                            <span class="badge-status <?= $appStCls; ?>" style="font-size: 11px; padding: 3px 8px;"><?= htmlspecialchars($ra['status']); ?></span>
                            <?php if ($ra['resume_file'] && $ra['resume_file'] !== 'default_resume.pdf' && file_exists(__DIR__ . '/../uploads/' . $ra['resume_file'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($ra['resume_file']); ?>" target="_blank" style="display: block; font-size: 11px; font-weight: 800; color: #2563EB; margin-top: 4px; text-decoration: none;">📄 Resume ↗</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="candidates.php" class="btn-secondary" style="width: 100%; justify-content: center; margin-top: 16px; border-radius: 10px; padding: 10px; font-weight: 800; text-decoration: none; display: flex;">
                View All Candidate Dossiers
            </a>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
