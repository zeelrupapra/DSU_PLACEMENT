<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';
$error = '';

// Handle AJAX endpoint for viewing applicants inside modal (ULTRA ATTRACTIVE POPUP LAYOUT)
if (isset($_GET['fetch_apps']) && isset($_GET['drive_id'])) {
    $dId = intval($_GET['drive_id']);
    $drv = $pdo->query("SELECT d.*, c.company_name FROM drives d JOIN companies c ON d.company_id = c.id WHERE d.id = $dId")->fetch();
    $companyLabel = $drv ? $drv['company_name'] . ' (' . $drv['package_ctc'] . ')' : 'Campus Recruiter';

    $apps = $pdo->query("SELECT a.*, s.enrollment_no, s.full_name, s.branch, s.cpi, s.resume_file, s.profile_photo, s.offer_letter FROM applications a JOIN students s ON a.student_id = s.id WHERE a.drive_id = $dId ORDER BY a.id DESC")->fetchAll();
    
    if (empty($apps)) {
        echo "<div style='text-align: center; padding: 48px 20px; background: #F8FAFC; border-radius: 16px; border: 1px dashed #CBD5E1;'>";
        echo "<div style='font-size: 36px; margin-bottom: 8px;'>📭</div>";
        echo "<h4 style='font-size: 16px; font-weight: 800; color: #0F172A;'>No Candidate Applications Yet</h4>";
        echo "<p style='color: #64748B; font-size: 13px;'>No student candidates have registered for this placement drive yet.</p>";
        echo "</div>";
        exit;
    }

    echo "<div style='display: flex; flex-direction: column; gap: 12px;'>";
    echo "<div class='table-responsive'><table class='custom-table' style='width: 100%; border-collapse: separate; border-spacing: 0 8px;'>";
    echo "<thead><tr style='background: #F8FAFC;'>";
    echo "<th style='padding: 12px 16px; font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase;'>Student Candidate</th>";
    echo "<th style='padding: 12px 16px; font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase;'>Branch</th>";
    echo "<th style='padding: 12px 16px; font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase;'>Academic CPI</th>";
    echo "<th style='padding: 12px 16px; font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase;'>Current Pipeline Stage</th>";
    echo "<th style='padding: 12px 16px; font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase; text-align: right;'>Update Selection Outcome</th>";
    echo "</tr></thead><tbody>";

    foreach ($apps as $ap) {
        $photoSrc = ($ap['profile_photo'] && $ap['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $ap['profile_photo'])) 
            ? '../uploads/' . $ap['profile_photo'] 
            : 'https://ui-avatars.com/api/?name=' . urlencode($ap['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';

        $stBadgeClass = ($ap['status']==='Selected') ? 'placed' : (($ap['status']==='Rejected') ? 'unplaced' : 'in-process');
        $stNameAttr = htmlspecialchars($ap['full_name'], ENT_QUOTES, 'UTF-8');
        $compLabelAttr = htmlspecialchars($companyLabel, ENT_QUOTES, 'UTF-8');

        echo "<tr style='background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px;'>";
        echo "<td style='padding: 12px 16px; border-top-left-radius: 14px; border-bottom-left-radius: 14px;'>";
        echo "<div style='display: flex; align-items: center; gap: 12px;'>";
        echo "<img src='$photoSrc' alt='Photo' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #3B82F6; flex-shrink: 0;'>";
        echo "<div>";
        echo "<strong style='font-size: 14px; color: #0F172A;'>{$ap['full_name']}</strong><br>";
        echo "<code style='font-size: 11px; color: #3B82F6; font-weight: 700;'>{$ap['enrollment_no']}</code>";
        echo "</div>";
        echo "</div>";
        echo "</td>";

        echo "<td style='padding: 12px 16px;'><span class='branch-badge' style='background: #F1F5F9; color: #475569; font-weight: 700;'>{$ap['branch']}</span></td>";
        echo "<td style='padding: 12px 16px;'><span class='cpi-pill high' style='font-weight: 800;'>★ {$ap['cpi']}</span></td>";
        echo "<td style='padding: 12px 16px;'><span class='badge-status {$stBadgeClass}' style='font-weight: 800;'>{$ap['status']}</span></td>";

        echo "<td style='padding: 12px 16px; text-align: right; border-top-right-radius: 14px; border-bottom-right-radius: 14px;'>";
        echo "<form method='POST' style='display: flex; gap: 8px; justify-content: flex-end; align-items: center;' data-id='{$ap['student_id']}' data-name='{$stNameAttr}' data-company='{$compLabelAttr}' data-appid='{$ap['id']}' onsubmit='return handleAppStatusSubmit(this);'>";
        echo "<input type='hidden' name='action' value='update_app_status'>";
        echo "<input type='hidden' name='app_id' value='{$ap['id']}'>";
        echo "<select name='status' class='select-pill' style='font-size: 12px; padding: 7px 12px; font-weight: 700; border-radius: 10px; border: 1px solid #CBD5E1;' onchange='handleSelectChange(this);'>";
        echo "<option value='Applied' ".($ap['status']==='Applied'?'selected':'').">Applied</option>";
        echo "<option value='Shortlisted' ".($ap['status']==='Shortlisted'?'selected':'').">Shortlisted</option>";
        echo "<option value='Interview Round' ".($ap['status']==='Interview Round'?'selected':'').">Interview Round</option>";
        echo "<option value='Selected' ".($ap['status']==='Selected'?'selected':'').">🟢 Selected (Placed)</option>";
        echo "<option value='Rejected' ".($ap['status']==='Rejected'?'selected':'').">🔴 Rejected</option>";
        echo "</select>";
        echo "<button type='submit' class='btn-primary' style='padding: 7px 14px; font-size: 12px; border-radius: 10px; font-weight: 800; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);'>Save</button>";
        echo "</form>";
        if (!empty($ap['offer_letter'])) {
            echo "<a href='../view_offer.php?file=" . urlencode($ap['offer_letter']) . "' target='_blank' class='btn-secondary' style='margin-top: 4px; padding: 4px 10px; font-size: 11px; font-weight: 800; background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px;'>📜 View Offer</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table></div>";
    echo "</div>";
    exit;
}

// Handle Form Posts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);

    if ($action === 'add_company') {
        $cName = sanitize($_POST['company_name']);
        $hrName = sanitize($_POST['hr_name']);
        $hrEmail = sanitize($_POST['hr_email']);
        $industry = sanitize($_POST['industry']);
        $website = sanitize($_POST['website']);
        $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '9988776655';

        $stmt = $pdo->prepare("INSERT INTO companies (company_name, hr_name, hr_email, industry, website, phone, is_closed) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$cName, $hrName, $hrEmail, $industry, $website, $phone]);
        $msg = "Company profile added successfully to Corporate Partners Directory!";
    } elseif ($action === 'toggle_company_closed') {
        $cId = intval($_POST['company_id']);
        $currClosed = intval($_POST['is_closed']);
        $newClosed = ($currClosed === 1) ? 0 : 1;

        $stmt = $pdo->prepare("UPDATE companies SET is_closed = ? WHERE id = ?");
        $stmt->execute([$newClosed, $cId]);
        $msg = ($newClosed === 1) ? "Company status marked as CLOSED / INACTIVE!" : "Company status reopened as ACTIVE!";
    } elseif ($action === 'create_drive') {
        $companyId = intval($_POST['company_id']);
        $title = sanitize($_POST['title']);
        $designation = sanitize($_POST['designation']);
        $package = sanitize($_POST['package_ctc']);
        $location = sanitize($_POST['location']);
        $minCpi = floatval($_POST['min_cpi']);
        $branches = sanitize($_POST['allowed_branches']);
        $driveDate = sanitize($_POST['drive_date']);
        $deadlineDate = sanitize($_POST['deadline']);
        $deadlineTime = isset($_POST['deadline_time']) ? sanitize($_POST['deadline_time']) : '18:00';
        $desc = sanitize($_POST['description']);

        $stmt = $pdo->prepare("INSERT INTO drives (company_id, title, designation, package_ctc, location, min_cpi, allowed_branches, drive_date, deadline, deadline_time, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->execute([$companyId, $title, $designation, $package, $location, $minCpi, $branches, $driveDate, $deadlineDate, $deadlineTime, $desc]);
        $msg = "New placement drive published with required application deadline $deadlineDate at $deadlineTime!";
    } elseif ($action === 'toggle_drive_status') {
        $dId = intval($_POST['drive_id']);
        $newStatus = sanitize($_POST['target_status']);

        $stmt = $pdo->prepare("UPDATE drives SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $dId]);
        $msg = "Placement Drive status manually updated to: $newStatus!";
    } elseif ($action === 'update_app_status') {
        $appId = intval($_POST['app_id']);
        $newStatus = sanitize($_POST['status']);
        $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : '';

        $appInfo = $pdo->query("SELECT a.student_id, a.drive_id, d.package_ctc, c.company_name FROM applications a JOIN drives d ON a.drive_id = d.id JOIN companies c ON d.company_id = c.id WHERE a.id = $appId")->fetch();

        if ($newStatus === 'Selected' && $appInfo) {
            $check = canSelectStudentForCompany($pdo, $appInfo['student_id'], $appInfo['drive_id']);
            if (!$check['allowed']) {
                setFlashMessage($check['reason'], 'danger');
                header("Location: companies.php?tab=active_drives");
                exit;
            }

            $stmt = $pdo->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?");
            $stmt->execute([$newStatus, $remarks, $appId]);

            $compTag = $appInfo['company_name'] . ' (' . $appInfo['package_ctc'] . ')';
            $pdo->prepare("UPDATE students SET placement_status = 'Placed', placed_companies = ? WHERE id = ?")->execute([$compTag, $appInfo['student_id']]);
            setFlashMessage("Candidate selected and placed at " . htmlspecialchars($compTag) . "!", 'success');
        } else {
            $stmt = $pdo->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?");
            $stmt->execute([$newStatus, $remarks, $appId]);

            if ($appInfo) {
                $otherSel = $pdo->query("SELECT COUNT(*) FROM applications WHERE student_id = {$appInfo['student_id']} AND status = 'Selected'")->fetchColumn();
                if ($otherSel == 0) {
                    $pdo->prepare("UPDATE students SET placement_status = 'In-Process', placed_companies = NULL, offer_letter = NULL WHERE id = ?")->execute([$appInfo['student_id']]);
                }
            }
            setFlashMessage("Candidate application status updated to $newStatus!", 'info');
        }
        header("Location: companies.php?tab=active_drives");
        exit;
    } elseif ($action === 'broadcast_students') {
        $subject = sanitize($_POST['subject']);
        $messageBody = sanitize($_POST['message_body']);
        $branchFilter = sanitize($_POST['target_branch']);

        $q = "SELECT email FROM students WHERE 1=1";
        $params = [];
        if ($branchFilter !== 'ALL') {
            $q .= " AND branch = ?";
            $params[] = $branchFilter;
        }
        $stmt = $pdo->prepare($q);
        $stmt->execute($params);
        $studentEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $sentCount = 0;
        foreach ($studentEmails as $toEmail) {
            sendPortalEmail($toEmail, $subject, nl2br($messageBody), $pdo);
            $sentCount++;
        }
        $msg = "Broadcast announcement dispatched to $sentCount student candidate(s)!";
    } elseif ($action === 'email_company_hr') {
        $companyId = intval($_POST['company_id']);
        $subject = sanitize($_POST['email_subject']);
        $customNote = sanitize($_POST['custom_note']);

        $stmtC = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmtC->execute([$companyId]);
        $comp = $stmtC->fetch();

        if ($comp) {
            $stmtSt = $pdo->prepare("SELECT * FROM students ORDER BY cpi DESC LIMIT 15");
            $stmtSt->execute();
            $candidates = $stmtSt->fetchAll();

            $html = "<h2>Student Candidates Export - Dr. Subhash Placement Cell</h2>";
            $html .= "<p>" . nl2br($customNote) . "</p>";
            $html .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%;'>";
            $html .= "<tr style='background:#3B82F6; color:white;'><th>Enrollment</th><th>Name</th><th>Branch</th><th>CPI</th><th>Email</th></tr>";
            foreach ($candidates as $cand) {
                $html .= "<tr><td>{$cand['enrollment_no']}</td><td>{$cand['full_name']}</td><td>{$cand['branch']}</td><td>{$cand['cpi']}</td><td>{$cand['email']}</td></tr>";
            }
            $html .= "</table>";

            sendPortalEmail($comp['hr_email'], $subject, $html, $pdo);
            $msg = "Direct email with candidate dossier dispatched to {$comp['company_name']} ({$comp['hr_email']})!";
        }
    } elseif ($action === 'upload_offer_letter') {
        $stId = intval($_POST['student_id']);
        $companyName = sanitize($_POST['company_name']);
        if (empty($companyName)) {
            $companyName = 'Assigned Campus Recruiter';
        }
        $appId = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;

        $stmtSt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmtSt->execute([$stId]);
        $st = $stmtSt->fetch();

        if ($st) {
            $fileName = $st['offer_letter'];

            if (isset($_FILES['offer_file']) && $_FILES['offer_file']['error'] === UPLOAD_ERR_OK) {
                $fileExt = strtolower(pathinfo($_FILES['offer_file']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['pdf', 'docx', 'doc', 'png', 'jpg', 'jpeg'];

                if (in_array($fileExt, $allowedExts)) {
                    $newFileName = 'offer_letter_' . $st['enrollment_no'] . '_' . time() . '.' . $fileExt;
                    $targetPath = __DIR__ . '/../uploads/' . $newFileName;

                    if (move_uploaded_file($_FILES['offer_file']['tmp_name'], $targetPath)) {
                        $fileName = $newFileName;

                        $toEmail = $st['email'];
                        $subject = "🎉 Official Placement Offer Letter - Dr. Subhash University Placement Cell";
                        $bodyHtml = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #E2E8F0; border-radius: 16px; padding: 30px; background: #FFFFFF;'>
                            <h2 style='color: #2563EB; margin-top: 0;'>🎉 Congratulations " . htmlspecialchars($st['full_name']) . "!</h2>
                            <p style='font-size: 15px; color: #334155; line-height: 1.6;'>
                                We are thrilled to inform you that your official <strong>Placement Offer Letter</strong> from <strong>" . htmlspecialchars($companyName) . "</strong> has been verified and issued by the Training & Placement Cell of Dr. Subhash University.
                            </p>
                            <div style='background: #EFF6FF; border: 1px solid #BFDBFE; padding: 18px; border-radius: 12px; margin: 20px 0;'>
                                <p style='margin: 0 0 6px 0; font-size: 14px; color: #1E40AF;'><strong>Candidate Name:</strong> " . htmlspecialchars($st['full_name']) . "</p>
                                <p style='margin: 0 0 6px 0; font-size: 14px; color: #1E40AF;'><strong>Enrollment No:</strong> " . htmlspecialchars($st['enrollment_no']) . "</p>
                                <p style='margin: 0; font-size: 14px; color: #1E40AF;'><strong>Recruiter Company:</strong> " . htmlspecialchars($companyName) . "</p>
                            </div>
                            <p style='font-size: 14px; color: #475569;'>
                                You can view and download your official offer letter document by logging into your Student Dashboard or clicking the button below:
                            </p>
                            <div style='text-align: center; margin: 26px 0;'>
                                <a href='http://localhost/DSU/uploads/" . urlencode($fileName) . "' style='background: #2563EB; color: #FFFFFF; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: bold; display: inline-block;'>📜 Download Official Offer Letter</a>
                            </div>
                            <p style='font-size: 13px; color: #64748B; border-top: 1px solid #F1F5F9; padding-top: 16px;'>
                                Training & Placement Cell<br>
                                <strong>Dr. Subhash University, Junagadh</strong>
                            </p>
                        </div>
                        ";

                        sendPortalEmail($toEmail, $subject, $bodyHtml, $pdo);
                    }
                }
            }

            $pdo->prepare("UPDATE students SET placement_status = 'Placed', placed_companies = ?, offer_letter = ? WHERE id = ?")->execute([$companyName, $fileName, $stId]);

            if ($appId > 0) {
                $pdo->prepare("UPDATE applications SET status = 'Selected', remarks = 'Offer Letter Verified' WHERE id = ?")->execute([$appId]);
            } else {
                $pdo->prepare("UPDATE applications SET status = 'Selected' WHERE student_id = ? AND status != 'Rejected'")->execute([$stId]);
            }

            setFlashMessage("🎉 Offer Letter & Placement Selection updated for " . htmlspecialchars($st['full_name']) . " at " . htmlspecialchars($companyName) . "!", "success");
        }
    }

    $redirectUrl = $_SERVER['PHP_SELF'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    header("Location: " . $redirectUrl);
    exit;
}

// Active Tab & Filters
$activeTab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'companies'; // 'companies', 'active_drives', 'closed_drives'
$companyStatusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$yearFilter = isset($_GET['year']) ? intval($_GET['year']) : 0;

// Excel / CSV Export Engine (Filter-Aware)
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=corporate_companies_and_openings_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    if ($activeTab === 'active_drives' || $activeTab === 'closed_drives') {
        fputcsv($output, ['Drive Title', 'Company Name', 'Designation', 'Package CTC', 'Min CPI', 'Allowed Branches', 'Drive Date', 'Deadline Date', 'Deadline Time', 'Status', 'Applications Count']);
        $driveStatusCondition = ($activeTab === 'active_drives') ? "d.status IN ('Active', 'Upcoming')" : "d.status IN ('Completed', 'Cancelled')";
        $qExp = "SELECT d.*, c.company_name FROM drives d JOIN companies c ON d.company_id = c.id WHERE $driveStatusCondition";
        if ($yearFilter > 0) { $qExp .= " AND YEAR(d.drive_date) = $yearFilter"; }
        $qExp .= " ORDER BY d.id DESC";
        $expDrives = $pdo->query($qExp)->fetchAll();
        foreach ($expDrives as $dRow) {
            $totApps = $pdo->query("SELECT COUNT(*) FROM applications WHERE drive_id = {$dRow['id']}")->fetchColumn();
            fputcsv($output, [
                $dRow['title'],
                $dRow['company_name'],
                $dRow['designation'],
                $dRow['package_ctc'],
                $dRow['min_cpi'],
                $dRow['allowed_branches'],
                $dRow['drive_date'],
                $dRow['deadline'],
                $dRow['deadline_time'],
                $dRow['status'],
                $totApps
            ]);
        }
    } else {
        fputcsv($output, ['Company Name', 'HR Name', 'HR Email', 'Industry', 'Website', 'Phone', 'Company Status', 'Total Drives Posted', 'Total Applications Received']);
        $qExp = "SELECT c.*, COUNT(d.id) as total_drives FROM companies c LEFT JOIN drives d ON c.id = d.company_id WHERE 1=1";
        if ($companyStatusFilter === 'closed') { $qExp .= " AND c.is_closed = 1"; }
        if ($companyStatusFilter === 'active') { $qExp .= " AND c.is_closed = 0"; }
        if ($yearFilter > 0) { $qExp .= " AND (YEAR(c.created_at) = $yearFilter OR YEAR(d.drive_date) = $yearFilter)"; }
        $qExp .= " GROUP BY c.id ORDER BY c.id DESC";
        $expCompanies = $pdo->query($qExp)->fetchAll();
        foreach ($expCompanies as $cRow) {
            $totApps = $pdo->query("SELECT COUNT(*) FROM applications a JOIN drives d ON a.drive_id = d.id WHERE d.company_id = {$cRow['id']}")->fetchColumn();
            fputcsv($output, [
                $cRow['company_name'],
                $cRow['hr_name'],
                $cRow['hr_email'],
                $cRow['industry'],
                $cRow['website'],
                $cRow['phone'],
                $cRow['is_closed'] ? 'Closed / Inactive' : 'Active',
                $cRow['total_drives'],
                $totApps
            ]);
        }
    }
    fclose($output);
    exit;
}

// Filtered Queries
$queryComp = "SELECT c.*, COUNT(d.id) as drive_count FROM companies c LEFT JOIN drives d ON c.id = d.company_id WHERE 1=1";
if ($companyStatusFilter === 'closed') { $queryComp .= " AND c.is_closed = 1"; }
if ($companyStatusFilter === 'active') { $queryComp .= " AND c.is_closed = 0"; }
if ($yearFilter > 0) { $queryComp .= " AND (YEAR(c.created_at) = $yearFilter OR YEAR(d.drive_date) = $yearFilter)"; }
$queryComp .= " GROUP BY c.id ORDER BY c.id DESC";
$companies = $pdo->query($queryComp)->fetchAll();

$queryActiveDr = "SELECT d.*, c.company_name, c.logo FROM drives d JOIN companies c ON d.company_id = c.id WHERE (d.status = 'Active' OR d.status = 'Upcoming')";
if ($yearFilter > 0) { $queryActiveDr .= " AND YEAR(d.drive_date) = $yearFilter"; }
$queryActiveDr .= " ORDER BY d.id DESC";
$allActiveDrives = $pdo->query($queryActiveDr)->fetchAll();

$queryClosedDr = "SELECT d.*, c.company_name, c.logo FROM drives d JOIN companies c ON d.company_id = c.id WHERE (d.status = 'Completed' OR d.status = 'Cancelled')";
if ($yearFilter > 0) { $queryClosedDr .= " AND YEAR(d.drive_date) = $yearFilter"; }
$queryClosedDr .= " ORDER BY d.id DESC";
$allClosedDrives = $pdo->query($queryClosedDr)->fetchAll();

$totalCompaniesCount = count($companies);
$activeCompaniesCount = $pdo->query("SELECT COUNT(*) FROM companies WHERE is_closed = 0")->fetchColumn();
$closedCompaniesCount = $pdo->query("SELECT COUNT(*) FROM companies WHERE is_closed = 1")->fetchColumn();
$activeDrivesCount = count($allActiveDrives);
$closedDrivesCount = count($allClosedDrives);

$pageTitle = 'Companies & Openings Directory';
$currentPage = 'companies';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Action Row -->
<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Corporate Partners & Placement Openings</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Unified hub for recruiters, active openings, closed drive history, and HR candidate dispatches.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <a href="companies.php?export_csv=1&tab=<?= htmlspecialchars($activeTab); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($companyStatusFilter); ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 700; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
            📊 Export Excel / CSV
        </a>
        <button class="btn-secondary" onclick="openModal('broadcastModal')" style="border-radius: 12px; padding: 10px 18px; font-weight: 700; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
            📢 Broadcast Email
        </button>
        <button class="btn-primary" onclick="openModal('createDriveModal')" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);">
            + Post Placement Drive
        </button>
        <button class="btn-primary" onclick="openModal('addCompanyModal')" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); border: none; border-radius: 12px; padding: 10px 18px; font-weight: 800; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
            🏢 Add Recruiter
        </button>
    </div>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 16px; display: inline-block; font-size: 14px; padding: 10px 16px;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>

<!-- Summary KPI Cards Row -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Corporate Partners</span>
            <div class="kpi-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px;"><?= number_format($totalCompaniesCount); ?> Companies</div>
        <span class="kpi-badge info"><?= $activeCompaniesCount; ?> Active / <?= $closedCompaniesCount; ?> Closed</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Current Openings</span>
            <div class="kpi-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #10B981;"><?= number_format($activeDrivesCount); ?> Drives</div>
        <span class="kpi-badge up">🟢 Accepting Applications</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Previous Drives (Closed)</span>
            <div class="kpi-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #8B5CF6;"><?= number_format($closedDrivesCount); ?> Closed</div>
        <span class="kpi-badge info">📁 Completed History</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">HR Communication</span>
            <div class="kpi-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #F59E0B;">Direct Email</div>
        <span class="kpi-badge info" style="background:#FEF3C7; color:#B45309;">✉️ Live Dossier Mail</span>
    </div>
</div>

<!-- Navigation Tabs & Executive Multi-Filter Bar -->
<div class="table-card" style="padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; background: white; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <a href="companies.php?tab=companies&year=<?= $yearFilter; ?>" class="select-pill" style="border-radius: 12px; padding: 10px 18px; <?= ($activeTab==='companies')?'background:#3B82F6; color:white; border-color:#3B82F6; font-weight:800; box-shadow: 0 4px 12px rgba(59,130,246,0.25);':'background:#F8FAFC; color:#475569; font-weight:700;'; ?>">
            🏢 Corporate Partners (<?= $totalCompaniesCount; ?>)
        </a>
        <a href="companies.php?tab=active_drives&year=<?= $yearFilter; ?>" class="select-pill" style="border-radius: 12px; padding: 10px 18px; <?= ($activeTab==='active_drives')?'background:#10B981; color:white; border-color:#10B981; font-weight:800; box-shadow: 0 4px 12px rgba(16,185,129,0.25);':'background:#F8FAFC; color:#475569; font-weight:700;'; ?>">
            💼 Current Openings (<?= $activeDrivesCount; ?>)
        </a>
        <a href="companies.php?tab=closed_drives&year=<?= $yearFilter; ?>" class="select-pill" style="border-radius: 12px; padding: 10px 18px; <?= ($activeTab==='closed_drives')?'background:#8B5CF6; color:white; border-color:#8B5CF6; font-weight:800; box-shadow: 0 4px 12px rgba(139,92,246,0.25);':'background:#F8FAFC; color:#475569; font-weight:700;'; ?>">
            📁 Previous Openings (Closed) (<?= $closedDrivesCount; ?>)
        </a>
    </div>

    <!-- Year-Wise & Status Filter Controls -->
    <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab); ?>">

        <!-- Year-Wise Filter -->
        <select name="year" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px;">
            <option value="0">📅 All Placement Years</option>
            <option value="2026" <?= ($yearFilter===2026)?'selected':''; ?>>2026 Academic Year</option>
            <option value="2025" <?= ($yearFilter===2025)?'selected':''; ?>>2025 Academic Year</option>
            <option value="2024" <?= ($yearFilter===2024)?'selected':''; ?>>2024 Academic Year</option>
        </select>

        <?php if ($activeTab === 'companies'): ?>
            <select name="status" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px;">
                <option value="">All Recruiter Statuses</option>
                <option value="active" <?= ($companyStatusFilter==='active')?'selected':''; ?>>🟢 Active Companies</option>
                <option value="closed" <?= ($companyStatusFilter==='closed')?'selected':''; ?>>⛔ Closed / Inactive Companies</option>
            </select>
        <?php endif; ?>

        <a href="companies.php?tab=<?= htmlspecialchars($activeTab); ?>" class="btn-secondary" style="padding: 9px 16px; font-size: 13px; border-radius: 12px;">Reset Filter</a>
    </form>
</div>

<!-- TAB CONTENT 1: CORPORATE PARTNERS DIRECTORY -->
<?php if ($activeTab === 'companies'): ?>
<div class="student-grid-view">
    <?php foreach ($companies as $comp): ?>
        <div class="student-card-item" style="border-radius: 20px; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); <?= $comp['is_closed'] ? 'border-color:#FCA5A5; background:#FFF1F2;' : 'border-color:#E2E8F0; background:#FFFFFF;'; ?>">
            <div>
                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; gap: 12px;">
                    <div style="display: flex; align-items: flex-start; gap: 12px; flex: 1; min-width: 0;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); border: 2px solid #BFDBFE; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #1D4ED8; font-size: 20px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.15);">
                            <?= strtoupper(substr($comp['company_name'], 0, 1)); ?>
                        </div>
                        <div style="min-width: 0; flex: 1;">
                            <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; line-height: 1.3; margin-bottom: 4px; word-break: break-word;"><?= htmlspecialchars($comp['company_name']); ?></h4>
                            <span class="branch-badge" style="background:#F1F5F9; color:#475569; font-weight:700; border-radius: 8px; font-size: 11px; padding: 3px 8px; max-width: 100%; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($comp['industry']); ?></span>
                        </div>
                    </div>

                    <!-- ADMIN MANUAL CLOSE COMPANY TOGGLE -->
                    <form method="POST" style="display: inline; flex-shrink: 0;" onsubmit="return confirm('Toggle company status?');">
                        <input type="hidden" name="action" value="toggle_company_closed">
                        <input type="hidden" name="company_id" value="<?= $comp['id']; ?>">
                        <input type="hidden" name="is_closed" value="<?= $comp['is_closed']; ?>">
                        <?php if ($comp['is_closed']): ?>
                            <button type="submit" class="btn-action-icon detain" style="padding: 6px 14px; border:1px solid #FCA5A5; background:#FEE2E2; color:#B91C1C; font-weight:800; border-radius:10px; cursor:pointer; white-space: nowrap;" title="Click to Reopen">⛔ Closed</button>
                        <?php else: ?>
                            <button type="submit" class="btn-action-icon place" style="padding: 6px 14px; border:1px solid #86EFAC; background:#DCFCE7; color:#15803D; font-weight:800; border-radius:10px; cursor:pointer; white-space: nowrap;" title="Click to Mark Closed">🟢 Active</button>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="font-size: 12.5px; color: #475569; margin-bottom: 18px; line-height: 1.8; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px 16px; border-radius: 14px; overflow: hidden;">
                    <p style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <span style="color:#64748B; font-weight:700;">👤 HR Lead:</span>
                        <strong style="color:#0F172A; text-align: right;"><?= htmlspecialchars($comp['hr_name']); ?></strong>
                    </p>
                    <p style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <span style="color:#64748B; font-weight:700; flex-shrink: 0;">✉️ HR Email:</span>
                        <code style="color:#2563EB; font-weight:700; font-size: 11.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 190px; text-align: right;" title="<?= htmlspecialchars($comp['hr_email']); ?>"><?= htmlspecialchars($comp['hr_email']); ?></code>
                    </p>
                    <p style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <span style="color:#64748B; font-weight:700; flex-shrink: 0;">🌐 Website:</span>
                        <a href="<?= htmlspecialchars($comp['website']); ?>" target="_blank" style="color: #3B82F6; font-weight: 700; text-decoration: none; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 190px; text-align: right;"><?= htmlspecialchars($comp['website']); ?> ↗</a>
                    </p>
                    <p style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <span style="color:#64748B; font-weight:700;">💼 Drives Posted:</span>
                        <span style="font-weight:800; color:#0F172A;"><?= $comp['drive_count']; ?> <?= ($comp['drive_count'] == 1 ? 'Opening' : 'Openings'); ?></span>
                    </p>
                </div>
            </div>

            <button class="btn-primary" style="width: 100%; justify-content: center; font-size: 13px; padding: 11px; border-radius: 12px; font-weight: 800; box-shadow: 0 4px 12px rgba(59,130,246,0.2);" onclick="openEmailCompanyModal(<?= $comp['id']; ?>, '<?= htmlspecialchars($comp['company_name']); ?>')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Mail Student Details to HR
            </button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- TAB CONTENT 2: CURRENT ACTIVE OPENINGS -->
<?php if ($activeTab === 'active_drives'): ?>
<div class="table-card" style="padding: 24px; border-radius: 20px;">
    <div style="margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center;">
        <h4 style="font-weight: 800; font-size: 18px; color: #0F172A;">Active Placement Drives (<?= count($allActiveDrives); ?> Openings)</h4>
        <span class="kpi-badge up" style="padding: 6px 14px; border-radius: 10px; font-weight: 800;">🟢 Accepting Student Applications</span>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="min-width: 240px;">Drive Title & Recruiter</th>
                    <th>Designation</th>
                    <th>Package CTC</th>
                    <th>Eligibility Cutoff</th>
                    <th>Registration Deadline</th>
                    <th style="text-align: right;">Actions & Applications</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allActiveDrives as $drv): 
                    $appCount = $pdo->query("SELECT COUNT(*) FROM applications WHERE drive_id = {$drv['id']}")->fetchColumn();
                ?>
                    <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                        <td style="border-top-left-radius: 16px; border-bottom-left-radius: 16px; padding: 14px 16px;">
                            <strong style="font-size: 15px; color: #0F172A;"><?= htmlspecialchars($drv['title']); ?></strong><br>
                            <span style="font-size: 13px; color: #3B82F6; font-weight: 700;">🏢 <?= htmlspecialchars($drv['company_name']); ?></span>
                        </td>
                        <td style="padding: 14px 16px;"><span class="branch-badge" style="border-radius: 8px; padding: 6px 12px;"><?= htmlspecialchars($drv['designation']); ?></span></td>
                        <td style="padding: 14px 16px;"><strong style="color: #10B981; font-size: 15px; font-weight: 800;"><?= htmlspecialchars($drv['package_ctc']); ?></strong></td>
                        <td style="padding: 14px 16px;">
                            <span class="cpi-pill high" style="border-radius: 8px; padding: 4px 10px;">Min CPI: <?= $drv['min_cpi']; ?></span><br>
                            <span style="font-size: 11px; color: var(--text-muted); font-weight: 600; margin-top: 4px; display: block;"><?= htmlspecialchars($drv['allowed_branches']); ?></span>
                        </td>
                        <td style="padding: 14px 16px;">
                            <strong style="color:#0F172A;">⏰ <?= date('d M Y', strtotime($drv['deadline'])); ?></strong><br>
                            <span style="font-size: 12px; color: #EF4444; font-weight: 800;">Time: <?= htmlspecialchars($drv['deadline_time'] ?: '18:00'); ?></span>
                        </td>
                        <td style="text-align: right; border-top-right-radius: 16px; border-bottom-right-radius: 16px; padding: 14px 16px; white-space: nowrap;">
                            <div class="action-btn-group">
                                <button class="btn-action-icon dossier" onclick="viewApplications(<?= $drv['id']; ?>, '<?= htmlspecialchars($drv['title']); ?>')" title="View Drive Applications">
                                    👥 Applications (<?= $appCount; ?>)
                                </button>

                                <form method="POST" style="display: inline;" onsubmit="return confirm('Mark this placement drive as COMPLETED / CLOSED?');">
                                    <input type="hidden" name="action" value="toggle_drive_status">
                                    <input type="hidden" name="drive_id" value="<?= $drv['id']; ?>">
                                    <input type="hidden" name="target_status" value="Completed">
                                    <button type="submit" class="btn-action-icon unplace" title="Close Drive">⛔ Close Drive</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- TAB CONTENT 3: CLOSED PREVIOUS OPENINGS -->
<?php if ($activeTab === 'closed_drives'): ?>
<div class="table-card" style="padding: 24px; border-radius: 20px;">
    <div style="margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center;">
        <h4 style="font-weight: 800; font-size: 18px; color: #0F172A;">Previous Completed Drives (<?= count($allClosedDrives); ?> Closed)</h4>
        <span class="kpi-badge info" style="padding: 6px 14px; border-radius: 10px; font-weight: 800;">📁 Closed History Record</span>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="min-width: 240px;">Drive Title & Recruiter</th>
                    <th>Designation</th>
                    <th>Package CTC</th>
                    <th>Drive Date</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions & History</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allClosedDrives as $drv): 
                    $appCount = $pdo->query("SELECT COUNT(*) FROM applications WHERE drive_id = {$drv['id']}")->fetchColumn();
                ?>
                    <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                        <td style="border-top-left-radius: 16px; border-bottom-left-radius: 16px; padding: 14px 16px;">
                            <strong style="font-size: 15px; color: #0F172A;"><?= htmlspecialchars($drv['title']); ?></strong><br>
                            <span style="font-size: 13px; color: #3B82F6; font-weight: 700;">🏢 <?= htmlspecialchars($drv['company_name']); ?></span>
                        </td>
                        <td style="padding: 14px 16px;"><span class="branch-badge" style="border-radius: 8px; padding: 6px 12px;"><?= htmlspecialchars($drv['designation']); ?></span></td>
                        <td style="padding: 14px 16px;"><strong style="color: #10B981; font-size: 15px; font-weight: 800;"><?= htmlspecialchars($drv['package_ctc']); ?></strong></td>
                        <td style="padding: 14px 16px;"><strong><?= date('d M Y', strtotime($drv['drive_date'])); ?></strong></td>
                        <td style="padding: 14px 16px;"><span class="badge-status detained" style="border-radius: 10px; font-weight: 800;">📁 <?= $drv['status']; ?></span></td>
                        <td style="text-align: right; border-top-right-radius: 16px; border-bottom-right-radius: 16px; padding: 14px 16px; white-space: nowrap;">
                            <div class="action-btn-group">
                                <button class="btn-action-icon dossier" onclick="viewApplications(<?= $drv['id']; ?>, '<?= htmlspecialchars($drv['title']); ?>')" title="View Drive Applications">
                                    👥 Applications (<?= $appCount; ?>)
                                </button>

                                <form method="POST" style="display: inline;" onsubmit="return confirm('Reopen this placement drive as ACTIVE?');">
                                    <input type="hidden" name="action" value="toggle_drive_status">
                                    <input type="hidden" name="drive_id" value="<?= $drv['id']; ?>">
                                    <input type="hidden" name="target_status" value="Active">
                                    <button type="submit" class="btn-action-icon place" title="Reopen Drive">↺ Reopen Drive</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal 1: Add Company -->
<div class="modal-overlay" id="addCompanyModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add New Recruiter / Company</h3>
            <button class="close-modal" onclick="closeModal('addCompanyModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_company">
            <div class="form-group">
                <label>Company Name *</label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. Google India" required>
            </div>
            <div class="form-group">
                <label>HR Representative Name *</label>
                <input type="text" name="hr_name" class="form-control" placeholder="e.g. Sundar Pichai HR Lead" required>
            </div>
            <div class="form-group">
                <label>HR Email Address *</label>
                <input type="email" name="hr_email" class="form-control" placeholder="hr@google.com" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Industry Vertical</label>
                    <input type="text" name="industry" class="form-control" placeholder="e.g. Software & Cloud">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="9988776655">
                </div>
            </div>
            <div class="form-group">
                <label>Company Website</label>
                <input type="url" name="website" class="form-control" placeholder="https://careers.google.com">
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 12px; border-radius: 12px;">Save Company Profile</button>
        </form>
    </div>
</div>

<!-- Modal 2: Create Placement Drive with Required Deadline Time -->
<div class="modal-overlay" id="createDriveModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Post New Placement Drive</h3>
            <button class="close-modal" onclick="closeModal('createDriveModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_drive">
            <div class="form-group">
                <label>Select Corporate Recruiter *</label>
                <select name="company_id" class="form-control" required>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['company_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Drive Title *</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Software Development Engineer 2024" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Designation *</label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. SDE-1" required>
                </div>
                <div class="form-group">
                    <label>Package CTC Offered *</label>
                    <input type="text" name="package_ctc" class="form-control" placeholder="e.g. ₹12.50 LPA" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Min CPI Cutoff</label>
                    <input type="number" step="0.01" name="min_cpi" class="form-control" value="7.00" required>
                </div>
                <div class="form-group">
                    <label>Job Location</label>
                    <input type="text" name="location" class="form-control" value="Pan India / Remote">
                </div>
            </div>
            <div class="form-group">
                <label>Allowed Branches</label>
                <input type="text" name="allowed_branches" class="form-control" value="CSE, IT, ECE, MECH, CIVIL">
            </div>

            <!-- REQUIRED REGISTRATION DEADLINE DATE & TIME -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Drive Date *</label>
                    <input type="date" name="drive_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Deadline Date *</label>
                    <input type="date" name="deadline" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Deadline Time *</label>
                    <input type="time" name="deadline_time" class="form-control" value="18:00" required>
                </div>
            </div>
            <div class="form-group">
                <label>Job Description & Requirements</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Enter key responsibilities..."></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 12px; border-radius: 12px;">Publish Recruitment Drive</button>
        </form>
    </div>
</div>

<!-- Modal 3: Broadcast Email to Students -->
<div class="modal-overlay" id="broadcastModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Broadcast Announcement to Students</h3>
            <button class="close-modal" onclick="closeModal('broadcastModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="broadcast_students">
            <div class="form-group">
                <label>Target Student Branch</label>
                <select name="target_branch" class="form-control">
                    <option value="ALL">All Branches (CSE, IT, ECE, MECH, CIVIL)</option>
                    <option value="CSE">CSE Branch Only</option>
                    <option value="IT">IT Branch Only</option>
                    <option value="ECE">ECE Branch Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Email Subject *</label>
                <input type="text" name="subject" class="form-control" placeholder="Urgent: Placement Drive Announcement" required>
            </div>
            <div class="form-group">
                <label>Email Body Content *</label>
                <textarea name="message_body" class="form-control" rows="5" placeholder="Dear Students, campus recruitment drive..." required></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px;">Send Broadcast Email</button>
        </form>
    </div>
</div>

<!-- Modal 4: Mail Student Profiles to Company HR -->
<div class="modal-overlay" id="emailCompanyModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Dispatch Candidate Dossiers to Company</h3>
            <button class="close-modal" onclick="closeModal('emailCompanyModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="email_company_hr">
            <input type="hidden" name="company_id" id="targetCompanyId">

            <div class="form-group">
                <label>Selected Recruiter</label>
                <input type="text" id="targetCompanyName" class="form-control" readonly style="background: #F8FAFC; color: #0F172A; font-weight: 700;">
            </div>
            <div class="form-group">
                <label>Email Subject</label>
                <input type="text" name="email_subject" class="form-control" value="Top Candidate Student Dossiers - Dr. Subhash Placement Cell" required>
            </div>
            <div class="form-group">
                <label>Cover Note / Instructions for HR</label>
                <textarea name="custom_note" class="form-control" rows="4">Dear HR Team, Please find attached the top shortlisted student candidate dossiers from Dr. Subhash University for your review.</textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px;">Dispatch Student List to HR</button>
        </form>
    </div>
</div>

<!-- Modal 5: View Drive Applications Modal (ULTRA ATTRACTIVE POPUP CONTAINER) -->
<div class="modal-overlay" id="applicationsModal">
    <div class="modal-box full-size" style="max-width: 960px !important; border-radius: 24px !important;">
        <div class="modal-header" style="border-bottom: 1px solid #E2E8F0; padding-bottom: 16px;">
            <h3 id="appModalTitle" style="font-size: 18px; font-weight: 800; color: #0F172A;">Drive Candidate Applications</h3>
            <button class="close-modal" onclick="closeModal('applicationsModal')">&times;</button>
        </div>
        <div id="applicationsContainer" class="dossier-scroll-area" style="padding-top: 16px;">
            <p style="color: var(--text-muted); text-align: center;">Loading candidate applications...</p>
        </div>
    </div>
</div>

<!-- Modal 6: Premium Upload Offer Letter Flash Modal Popup -->
<div class="modal-overlay" id="uploadOfferLetterModal">
    <div class="modal-box" style="max-width: 560px; border-radius: 28px; padding: 32px; background: #FFFFFF; box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35); border: 1px solid rgba(226, 232, 240, 0.8);">
        
        <!-- Header Banner -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; padding-bottom: 16px; border-bottom: 1px solid #F1F5F9;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 16px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4); color: white;">
                    📜
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #059669; background: #ECFDF5; padding: 3px 10px; border-radius: 20px; border: 1px solid #A7F3D0; letter-spacing: 0.5px;">✨ Campus Placement Dispatcher</span>
                    <h3 style="font-size: 19px; font-weight: 900; color: #0F172A; margin-top: 2px; margin-bottom: 0;">Upload Offer Letter & Mark Placed</h3>
                </div>
            </div>
            <button type="button" class="close-modal" onclick="closeModal('uploadOfferLetterModal')" style="background: #F1F5F9; border: none; font-size: 20px; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; color: #64748B; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data" action="companies.php">
            <input type="hidden" name="action" value="upload_offer_letter">
            <input type="hidden" name="student_id" id="offerStudentId">
            <input type="hidden" name="app_id" id="offerAppId" value="0">

            <!-- Candidate Summary Box -->
            <div style="background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%); border: 1px solid #BFDBFE; padding: 16px; border-radius: 18px; margin-bottom: 20px;">
                <div style="font-size: 11px; font-weight: 800; color: #2563EB; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Target Candidate Profile</div>
                <input type="text" id="offerStudentName" class="form-control-custom" readonly style="background: transparent; border: none; font-size: 16px; font-weight: 900; color: #0F172A; width: 100%; padding: 0; outline: none;">
            </div>

            <!-- Recruiter Company Name & Package -->
            <div class="form-group" style="margin-bottom: 18px;">
                <label style="font-size: 13px; font-weight: 800; color: #334155; display: block; margin-bottom: 6px;">
                    🏢 Recruiter Company Name & Salary Package *
                </label>
                <input type="text" name="company_name" id="offerCompanyName" class="form-control-custom" placeholder="e.g. Google India Pvt Ltd (₹24.5 LPA)" required style="width: 100%; padding: 13px 16px; border-radius: 14px; border: 1px solid #CBD5E1; font-weight: 700; font-size: 14px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); outline: none;">
            </div>

            <!-- Drag & Drop / File Select Box -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label style="font-size: 13px; font-weight: 800; color: #334155; display: block; margin-bottom: 6px;">
                    📄 Upload Official Offer Letter Document <span style="color: #64748B; font-weight: 600;">(PDF / DOCX / Image)</span>
                </label>
                
                <div style="border: 2px dashed #10B981; background: #ECFDF5; border-radius: 18px; padding: 22px; text-align: center; position: relative; transition: all 0.2s;" id="offerDropZone">
                    <div style="font-size: 32px; margin-bottom: 8px;">📜</div>
                    <div style="font-size: 14px; font-weight: 800; color: #065F46;" id="offerFileNameDisplay">Choose file or drag & drop here</div>
                    <div style="font-size: 12px; color: #047857; margin-top: 4px; font-weight: 600;">Supports PDF, DOCX, DOC, PNG, JPG (Max 10MB)</div>
                    <input type="file" name="offer_file" id="offerFileInput" accept=".pdf,.docx,.doc,.png,.jpg,.jpeg" onchange="updateOfferFileName(this)" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid #F1F5F9; padding-top: 20px;">
                <button type="button" class="btn-secondary" onclick="closeModal('uploadOfferLetterModal')" style="padding: 12px 22px; border-radius: 14px; font-weight: 800; font-size: 14px;">Cancel</button>
                <button type="submit" class="btn-primary" style="padding: 12px 26px; border-radius: 14px; font-weight: 800; font-size: 14px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); border: none; box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4); display: inline-flex; align-items: center; gap: 8px;">
                    📤 Upload & Confirm Placement
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.handleAppStatusSubmit = function(form) {
    const statusSelect = form.querySelector('select[name="status"]');
    if (statusSelect && statusSelect.value === 'Selected') {
        const studentId = form.getAttribute('data-id');
        const studentName = form.getAttribute('data-name');
        const companyName = form.getAttribute('data-company');
        const appId = form.getAttribute('data-appid');

        openOfferUploadModal(studentId, studentName, companyName, appId);
        return false; // Intercept form submit to show Offer Upload Flash Modal!
    }
    return true;
};

window.handleSelectChange = function(selectEl) {
    if (selectEl.value === 'Selected') {
        handleAppStatusSubmit(selectEl.form);
    }
};

function openEmailCompanyModal(cId, cName) {
    document.getElementById('targetCompanyId').value = cId;
    document.getElementById('targetCompanyName').value = cName;
    openModal('emailCompanyModal');
}

function viewApplications(driveId, driveTitle) {
    document.getElementById('appModalTitle').innerText = 'Candidates for ' + driveTitle;
    const container = document.getElementById('applicationsContainer');
    openModal('applicationsModal');
    
    fetch('companies.php?fetch_apps=1&drive_id=' + driveId)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
