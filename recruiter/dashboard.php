<?php
// Recruiter Management & Evaluation Portal
require_once '../config/db.php';
requireRecruiter();

$pdo = getDBConnection();
$companyId = intval($_SESSION['company_id']);
$companyName = $_SESSION['company_name'];
$recruiterName = $_SESSION['username'];

$flash = getFlashMessage();

// Process Applicant Status Evaluation by Recruiter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eval_applicant') {
    $appId = intval($_POST['app_id']);
    $newStatus = sanitize($_POST['status']);
    $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : '';

    // Verify application belongs to this recruiter's company drive
    $appInfo = $pdo->query("SELECT a.id, a.student_id, a.drive_id, d.company_id, d.package_ctc, c.company_name 
                           FROM applications a 
                           JOIN drives d ON a.drive_id = d.id 
                           JOIN companies c ON d.company_id = c.id 
                           WHERE a.id = $appId AND d.company_id = $companyId")->fetch();

    if ($appInfo) {
        if ($newStatus === 'Selected') {
            $check = canSelectStudentForCompany($pdo, $appInfo['student_id'], $appInfo['drive_id']);
            if (!$check['allowed']) {
                setFlashMessage($check['reason'], 'danger');
                header("Location: dashboard.php");
                exit;
            }
            $pdo->prepare("UPDATE applications SET status = 'Selected', remarks = ? WHERE id = ?")->execute([$remarks, $appId]);
            $compTag = $appInfo['company_name'] . ' (' . $appInfo['package_ctc'] . ')';
            $pdo->prepare("UPDATE students SET placement_status = 'Placed', placed_companies = ? WHERE id = ?")->execute([$compTag, $appInfo['student_id']]);
            setFlashMessage("Candidate evaluated & Selected for " . htmlspecialchars($compTag) . "!", 'success');
        } else {
            $pdo->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?")->execute([$newStatus, $remarks, $appId]);
            setFlashMessage("Applicant evaluation status updated to $newStatus.", 'info');
        }
    } else {
        setFlashMessage("Unauthorized evaluation attempt.", 'danger');
    }
    header("Location: dashboard.php");
    exit;
}

// Export CSV Roster for Recruiter's Drives
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $driveId = isset($_GET['drive_id']) ? intval($_GET['drive_id']) : 0;
    
    $where = "WHERE d.company_id = $companyId";
    if ($driveId > 0) {
        $where .= " AND a.drive_id = $driveId";
    }

    $apps = $pdo->query("SELECT a.id, s.enrollment_no, s.full_name, s.email, s.phone, s.branch, s.current_semester, s.cpi, s.backlogs, s.linkedin_url, a.status, a.remarks, a.applied_date, d.title as drive_title
                         FROM applications a
                         JOIN students s ON a.student_id = s.id
                         JOIN drives d ON a.drive_id = d.id
                         $where ORDER BY a.id DESC")->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . strtolower(str_replace(' ', '_', $companyName)) . '_applicant_roster.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Application ID', 'Enrollment No', 'Full Name', 'Email', 'Phone', 'Branch', 'Semester', 'CPI', 'Backlogs', 'LinkedIn URL', 'Drive Title', 'Pipeline Status', 'Recruiter Remarks', 'Applied Date']);
    
    foreach ($apps as $row) {
        fputcsv($output, [
            $row['id'],
            $row['enrollment_no'],
            $row['full_name'],
            $row['email'],
            $row['phone'],
            $row['branch'],
            'Sem ' . $row['current_semester'],
            $row['cpi'],
            $row['backlogs'],
            $row['linkedin_url'] ?: 'N/A',
            $row['drive_title'],
            $row['status'],
            $row['remarks'],
            $row['applied_date']
        ]);
    }
    fclose($output);
    exit;
}

// Fetch Metrics for this Recruiter
$totalDrives = $pdo->query("SELECT COUNT(*) FROM drives WHERE company_id = $companyId")->fetchColumn();
$totalApps = $pdo->query("SELECT COUNT(*) FROM applications a JOIN drives d ON a.drive_id = d.id WHERE d.company_id = $companyId")->fetchColumn();
$shortlistedApps = $pdo->query("SELECT COUNT(*) FROM applications a JOIN drives d ON a.drive_id = d.id WHERE d.company_id = $companyId AND a.status IN ('Shortlisted', 'Interview Round')")->fetchColumn();
$selectedApps = $pdo->query("SELECT COUNT(*) FROM applications a JOIN drives d ON a.drive_id = d.id WHERE d.company_id = $companyId AND a.status = 'Selected'")->fetchColumn();

// Fetch Recruiter's Active Drives
$drives = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM applications WHERE drive_id = d.id) as app_count 
                       FROM drives d WHERE d.company_id = $companyId ORDER BY d.id DESC")->fetchAll();

// Selected Drive Filter for Applicants
$filterDriveId = isset($_GET['drive_id']) ? intval($_GET['drive_id']) : 0;
$appWhere = "WHERE d.company_id = $companyId";
if ($filterDriveId > 0) {
    $appWhere .= " AND a.drive_id = $filterDriveId";
}

$applicants = $pdo->query("SELECT a.*, s.full_name, s.enrollment_no, s.email, s.phone, s.branch, s.current_semester, s.cpi, s.backlogs, s.resume_file, s.linkedin_url, d.title as drive_title, d.package_ctc
                           FROM applications a 
                           JOIN students s ON a.student_id = s.id 
                           JOIN drives d ON a.drive_id = d.id 
                           $appWhere ORDER BY a.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Recruiter Partner Portal - <?= htmlspecialchars($companyName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8FAFC; color: #0F172A; margin: 0; padding: 0; }
        .rec-navbar { background: #0F172A; color: white; padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .rec-logo { font-size: 18px; font-weight: 800; color: #3B82F6; display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .rec-container { max-width: 1300px; margin: 32px auto; padding: 0 24px; }
        .stat-card { background: white; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .stat-val { font-size: 28px; font-weight: 800; color: #0F172A; margin-top: 6px; }
        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; border-radius: 12px; overflow: hidden; border: 1px solid #E2E8F0; }
        .custom-table th { background: #F1F5F9; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase; padding: 14px 16px; text-align: left; }
        .custom-table td { padding: 14px 16px; border-top: 1px solid #E2E8F0; background: white; font-size: 14px; vertical-align: middle; }
        .btn-action { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; border: none; }
        .btn-blue { background: #2563EB; color: white; }
        .btn-green { background: #10B981; color: white; }
        .btn-dark { background: #1E293B; color: white; }
    </style>
</head>
<body>

    <!-- Recruiter Navigation Topbar -->
    <div class="rec-navbar">
        <a href="dashboard.php" class="rec-logo">
            <span style="font-size: 22px;">🏢</span> <?= htmlspecialchars($companyName); ?> <span style="font-size: 11px; background: #2563EB; color: white; padding: 3px 8px; border-radius: 12px; margin-left: 8px;">Recruiter Portal</span>
        </a>
        <div style="display: flex; align-items: center; gap: 20px;">
            <span style="font-size: 14px; font-weight: 600; color: #94A3B8;">Welcome, <strong style="color: white;"><?= htmlspecialchars($recruiterName); ?></strong></span>
            <a href="dashboard.php?export=csv<?= $filterDriveId ? '&drive_id='.$filterDriveId : ''; ?>" class="btn-action btn-green">📊 Export CSV Roster</a>
            <a href="../logout.php" class="btn-action btn-dark" onclick="return confirm('Log out of Recruiter Portal?');">🚪 Logout</a>
        </div>
    </div>

    <div class="rec-container">

        <!-- Flash Message Toast -->
        <?php if ($flash): ?>
            <div style="background: <?= $flash['type']==='success'?'#ECFDF5':($flash['type']==='danger'?'#FEF2F2':'#EFF6FF'); ?>; border: 1px solid <?= $flash['type']==='success'?'#10B981':($flash['type']==='danger'?'#EF4444':'#3B82F6'); ?>; color: <?= $flash['type']==='success'?'#065F46':($flash['type']==='danger'?'#991B1B':'#1E40AF'); ?>; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: space-between;">
                <span><?= htmlspecialchars($flash['msg']); ?></span>
                <span onclick="this.parentElement.remove()" style="cursor: pointer;">✕</span>
            </div>
        <?php endif; ?>

        <!-- Recruiter Overview Metrics Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px;">
            <div class="stat-card">
                <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Active Campus Drives</div>
                <div class="stat-val" style="color: #2563EB;"><?= $totalDrives; ?></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Applicants</div>
                <div class="stat-val" style="color: #8B5CF6;"><?= $totalApps; ?></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Shortlisted Candidates</div>
                <div class="stat-val" style="color: #F59E0B;"><?= $shortlistedApps; ?></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Selected / Hired</div>
                <div class="stat-val" style="color: #10B981;"><?= $selectedApps; ?></div>
            </div>
        </div>

        <!-- Section 1: Company Recruitment Drives -->
        <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 24px; margin-bottom: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <h3 style="font-size: 18px; font-weight: 800; margin: 0 0 16px 0; color: #0F172A; display: flex; align-items: center; justify-content: space-between;">
                <span>🎯 Your Company Recruitment Drives</span>
            </h3>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Drive Title</th>
                            <th>Designation</th>
                            <th>Package CTC</th>
                            <th>Allowed Semesters</th>
                            <th>Min CPI</th>
                            <th>Total Applicants</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drives as $d): ?>
                            <tr>
                                <td><strong style="color: #0F172A;"><?= htmlspecialchars($d['title']); ?></strong></td>
                                <td><?= htmlspecialchars($d['designation']); ?></td>
                                <td><strong style="color: #10B981;"><?= htmlspecialchars($d['package_ctc']); ?></strong></td>
                                <td><span style="background: #EFF6FF; color: #2563EB; font-weight: 700; padding: 4px 10px; border-radius: 8px; font-size: 12px;">Sem <?= htmlspecialchars($d['allowed_semesters'] ?: '5,6,7,8'); ?></span></td>
                                <td><?= htmlspecialchars($d['min_cpi']); ?> CPI</td>
                                <td><strong style="color: #8B5CF6;"><?= $d['app_count']; ?> Candidates</strong></td>
                                <td><span style="background: #ECFDF5; color: #047857; font-weight: 800; padding: 4px 10px; border-radius: 8px; font-size: 12px;"><?= htmlspecialchars($d['status']); ?></span></td>
                                <td>
                                    <a href="dashboard.php?drive_id=<?= $d['id']; ?>" class="btn-action btn-blue">👥 Review Applicants</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Candidate Evaluation & Roster Table -->
        <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 800; margin: 0; color: #0F172A;">
                    📋 Candidate Applicant Pipeline & Evaluation
                    <?php if ($filterDriveId): ?>
                        <span style="font-size: 13px; font-weight: 600; color: #64748B;">(Filtered for Drive #<?= $filterDriveId; ?>)</span>
                    <?php endif; ?>
                </h3>
                <?php if ($filterDriveId): ?>
                    <a href="dashboard.php" style="color: #2563EB; font-size: 13px; font-weight: 700; text-decoration: none;">View All Drives Applications</a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Candidate Student</th>
                            <th>Branch & Semester</th>
                            <th>CPI & Backlogs</th>
                            <th>Resume & Profiles</th>
                            <th>Drive Title</th>
                            <th>Current Stage</th>
                            <th>Recruiter Evaluation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applicants)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #64748B; padding: 32px;">No candidate applications recorded yet for your drives.</td></tr>
                        <?php else: ?>
                            <?php foreach ($applicants as $ap): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #0F172A; font-size: 15px; display: block;"><?= htmlspecialchars($ap['full_name']); ?></strong>
                                        <code style="background: #F1F5F9; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?= htmlspecialchars($ap['enrollment_no']); ?></code>
                                        <div style="font-size: 12px; color: #64748B; margin-top: 2px;"><?= htmlspecialchars($ap['email']); ?></div>
                                    </td>
                                    <td>
                                        <strong style="color: #1E293B;"><?= htmlspecialchars($ap['branch']); ?></strong>
                                        <div style="font-size: 12px; color: #64748B;">Semester <?= htmlspecialchars($ap['current_semester'] ?: 7); ?></div>
                                    </td>
                                    <td>
                                        <span style="color: #059669; font-weight: 800; font-size: 15px;"><?= htmlspecialchars($ap['cpi']); ?> CPI</span>
                                        <div style="font-size: 12px; color: <?= $ap['backlogs']>0?'#EF4444':'#64748B'; ?>; font-weight: 700;"><?= $ap['backlogs']; ?> Backlogs</div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                            <a href="../uploads/<?= htmlspecialchars($ap['resume_file']); ?>" target="_blank" style="color: #2563EB; font-weight: 700; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                📄 Preview PDF Resume
                                            </a>
                                            <?php if (!empty($ap['linkedin_url'])): ?>
                                                <a href="<?= htmlspecialchars($ap['linkedin_url']); ?>" target="_blank" style="color: #0A66C2; font-weight: 700; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                    🔗 LinkedIn Profile
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size: 11px; color: #94A3B8;">No LinkedIn link</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: #0F172A;"><?= htmlspecialchars($ap['drive_title']); ?></strong>
                                        <div style="color: #10B981; font-weight: 700; font-size: 12px;"><?= htmlspecialchars($ap['package_ctc']); ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = $ap['status'];
                                            $stColor = ($st==='Selected')?'#10B981':(($st==='Rejected')?'#EF4444':(($st==='Shortlisted')?'#F59E0B':'#3B82F6'));
                                        ?>
                                        <span style="background: <?= $stColor; ?>22; color: <?= $stColor; ?>; font-weight: 800; padding: 4px 12px; border-radius: 8px; font-size: 12px; display: inline-block;">
                                            <?= htmlspecialchars($st); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                            <input type="hidden" name="action" value="eval_applicant">
                                            <input type="hidden" name="app_id" value="<?= $ap['id']; ?>">
                                            <select name="status" style="padding: 6px 10px; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 13px; font-weight: 700;">
                                                <option value="Applied" <?= $ap['status']==='Applied'?'selected':''; ?>>Applied</option>
                                                <option value="Shortlisted" <?= $ap['status']==='Shortlisted'?'selected':''; ?>>Shortlisted</option>
                                                <option value="Interview Round" <?= $ap['status']==='Interview Round'?'selected':''; ?>>Interview Round</option>
                                                <option value="Selected" <?= $ap['status']==='Selected'?'selected':''; ?>>Selected (Hire)</option>
                                                <option value="Rejected" <?= $ap['status']==='Rejected'?'selected':''; ?>>Rejected</option>
                                            </select>
                                            <button type="submit" class="btn-action btn-blue">Save Evaluation</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>
