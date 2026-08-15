<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();

// =========================================================================
// AJAX DOSSIER POPUP ENDPOINT
// =========================================================================
if (isset($_GET['get_dossier'])) {
    $stId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
    $enrollNo = isset($_GET['enrollment_no']) ? sanitize($_GET['enrollment_no']) : '';

    if ($stId > 0) {
        $st = $pdo->query("SELECT * FROM students WHERE id = $stId")->fetch();
    } elseif ($enrollNo !== '') {
        $stmtSearch = $pdo->prepare("SELECT * FROM students WHERE enrollment_no = ?");
        $stmtSearch->execute([$enrollNo]);
        $st = $stmtSearch->fetch();
        if ($st) { $stId = $st['id']; }
    } else {
        $st = false;
    }

    if (!$st) {
        echo "<div style='padding: 32px; text-align: center; color: #EF4444; font-weight: 800; font-size: 15px;'>⚠️ Student candidate record not found. Please verify the enrollment number.</div>";
        exit;
    }

    $apps = $pdo->query("SELECT a.*, d.title, d.package_ctc, c.company_name FROM applications a JOIN drives d ON a.drive_id = d.id JOIN companies c ON d.company_id = c.id WHERE a.student_id = $stId ORDER BY a.id DESC")->fetchAll();
    $internships = $pdo->query("SELECT * FROM internships WHERE student_id = $stId ORDER BY id DESC")->fetchAll();

    $photoSrc = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
        ? '../uploads/' . $st['profile_photo'] 
        : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';

    echo "<div style='display: grid; grid-template-columns: 280px 1fr; gap: 24px; width: 100%; box-sizing: border-box; align-items: start;'>";
    
    // Left Profile Card (FIXED SIDE PANEL)
    echo "<div style='position: sticky; top: 0; align-self: start; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:20px; padding:24px; text-align:center;'>";
    echo "<img src='$photoSrc' alt='Student Photo' style='width:104px; height:104px; border-radius:50%; object-fit:cover; border:3px solid #3B82F6;'>";
    echo "<h2 style='font-size:19px; font-weight:800; color:#0F172A; margin-top:12px;'>".htmlspecialchars($st['full_name'])."</h2>";
    echo "<p style='font-size:13px; color:#3B82F6; font-weight:700;'><code>{$st['enrollment_no']}</code></p>";
    echo "<p style='font-size:12px; color:#64748B; margin-top:2px;'>{$st['email']}</p>";
    
    echo "<hr style='border:0; border-top:1px solid #E2E8F0; margin:16px 0;'>";
    echo "<div style='text-align:left; font-size:13px; line-height:1.9; color:#334155;'>";
    echo "<p style='display:flex; justify-content:space-between;'><strong>Branch & Batch:</strong> <span>{$st['branch']} ({$st['batch_year']})</span></p>";
    echo "<p style='display:flex; justify-content:space-between;'><strong>Academic CPI:</strong> <span style='color:#3B82F6; font-weight:800;'>★ {$st['cpi']}</span></p>";
    echo "<p style='display:flex; justify-content:space-between;'><strong>Active Backlogs:</strong> <span style='color:".($st['backlogs']>0?'#EF4444':'#10B981')."; font-weight:700;'>{$st['backlogs']}</span></p>";
    echo "<p style='display:flex; justify-content:space-between;'><strong>Phone:</strong> <span>{$st['phone']}</span></p>";
    echo "</div>";

    echo "<div style='margin-top:18px;'>";
    if ($st['is_detained']) {
        echo "<span class='badge-status detained' style='padding:8px 16px; font-weight:800; width:100%; justify-content:center;'>⛔ DETAINED BY ADMIN</span>";
    } else {
        $stCls = ($st['placement_status']==='Placed')?'placed':(($st['placement_status']==='In-Process')?'in-process':'unplaced');
        echo "<span class='badge-status $stCls' style='padding:8px 16px; font-weight:800; width:100%; justify-content:center;'>Status: {$st['placement_status']}</span>";
    }
    echo "</div>";

    echo "<div style='margin-top:20px;'>";
    echo "<a href='../uploads/{$st['resume_file']}' target='_blank' class='btn-primary' style='width:100%; justify-content:center; padding:11px; font-size:13px; font-weight:700;'>📥 Download Resume PDF</a>";
    echo "</div>";

    if (!empty($st['offer_letter']) && file_exists(__DIR__ . '/../uploads/' . $st['offer_letter'])) {
        echo "<div style='margin-top:10px;'>";
        echo "<a href='../uploads/{$st['offer_letter']}' target='_blank' class='btn-primary' style='width:100%; justify-content:center; padding:11px; font-size:13px; font-weight:700; background:#059669; border-color:#059669; text-decoration:none;'>📜 View Offer Letter (PDF)</a>";
        echo "</div>";
    }
    echo "</div>";

    // Right Column
    echo "<div style='display:flex; flex-direction:column; gap:20px; overflow:hidden;'>";
    echo "<div style='background:white; border:1px solid #E2E8F0; border-radius:18px; padding:20px;'>";
    echo "<h4 style='font-size:15px; font-weight:800; color:#0F172A; margin-bottom:14px;'>📄 Live Resume Document Preview</h4>";
    if (file_exists(__DIR__ . '/../uploads/' . $st['resume_file'])) {
        echo "<iframe src='../uploads/{$st['resume_file']}' style='width:100%; height:320px; border:1px solid #E2E8F0; border-radius:12px;'></iframe>";
    } else {
        echo "<p style='color:#64748B; font-size:13px;'>Standard candidate resume file: <code>{$st['resume_file']}</code></p>";
    }
    echo "</div>";

    // Workshop Registrations & Reports Section
    $wkRegs = $pdo->query("SELECT wr.*, w.title, w.event_date, w.speaker FROM workshop_registrations wr JOIN workshops w ON wr.workshop_id = w.id WHERE wr.student_id = $stId ORDER BY wr.id DESC")->fetchAll();
    echo "<div style='background:white; border:1px solid #E2E8F0; border-radius:18px; padding:20px; margin-top:16px;'>";
    echo "<h4 style='font-size:15px; font-weight:800; color:#0F172A; margin-bottom:14px;'>🎓 Workshop Registrations & Reports</h4>";
    if (!empty($wkRegs)) {
        echo "<table class='custom-table' style='font-size:12.5px;'><thead><tr><th>Title</th><th>Speaker</th><th>Schedule</th><th>Report</th></tr></thead><tbody>";
        foreach ($wkRegs as $wr) {
            echo "<tr>";
            echo "<td><strong>{$wr['title']}</strong></td>";
            echo "<td>{$wr['speaker']}</td>";
            echo "<td>" . date('d M Y', strtotime($wr['event_date'])) . "</td>";
            echo "<td>";
            if (!empty($wr['report_file']) && file_exists(__DIR__ . '/../uploads/' . $wr['report_file'])) {
                echo "<a href='../uploads/{$wr['report_file']}' target='_blank' style='color:#059669; font-weight:800; text-decoration:none;'>📜 View Report</a>";
            } else {
                echo "<span style='color:#94A3B8;'>None</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p style='color:#64748B; font-size:12.5px;'>No workshop event registrations recorded for this student.</p>";
    }
    echo "</div>";

    echo "</div></div>";
    exit;
}

// Download Sample CSV Import Template
if (isset($_GET['download_sample_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=student_import_sample_template.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['enrollment_no', 'full_name', 'email', 'phone', 'gender', 'branch', 'cpi', 'backlogs', 'batch_year', 'placement_status']);
    fputcsv($out, ['240130316099', 'Rohan Verma', 'rohan.verma@drsubhash.edu.in', '+91 98765 00099', 'Male', 'CSE', '8.50', '0', '2024', 'In-Process']);
    fputcsv($out, ['240130316100', 'Kavya Sharma', 'kavya.sharma@drsubhash.edu.in', '+91 98765 00100', 'Female', 'IT', '9.10', '0', '2024', 'Unplaced']);
    fclose($out);
    exit;
}

$msg = '';
$error = '';

// Filter parameters
$branchFilter = isset($_GET['branch']) ? sanitize($_GET['branch']) : '';

$currentYear = intval(date('Y')); // 2026
$yearParam = isset($_GET['year']) ? sanitize($_GET['year']) : '';
if ($yearParam === 'all' || $yearParam === '0') {
    $yearFilter = 0; // Show all years
} elseif ($yearParam !== '') {
    $yearFilter = intval($yearParam);
} else {
    $yearFilter = $currentYear; // Default to current academic year 2026!
}

$detainFilter = isset($_GET['detained']) ? sanitize($_GET['detained']) : '';
$viewMode = isset($_GET['view']) ? sanitize($_GET['view']) : 'list'; // 'list' or 'grid'

$query = "SELECT * FROM students WHERE 1=1";
$params = [];

if ($branchFilter) { $query .= " AND branch = ?"; $params[] = $branchFilter; }
if ($yearFilter > 0) { $query .= " AND batch_year = ?"; $params[] = $yearFilter; }
if ($detainFilter === 'yes') { $query .= " AND is_detained = 1"; }
if ($detainFilter === 'no') { $query .= " AND is_detained = 0"; }

$query .= " ORDER BY id DESC";

// Export Excel / CSV
if (isset($_GET['export_csv'])) {
    $stmtExp = $pdo->prepare($query);
    $stmtExp->execute($params);
    $exportStudents = $stmtExp->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=student_management_export_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Enrollment No', 'Full Candidate Name', 'Email', 'Gender', 'Branch', 'Batch Year', 'CPI', 'Backlogs', 'Phone', 'Detained']);

    foreach ($exportStudents as $stRow) {
        fputcsv($output, [
            $stRow['enrollment_no'],
            $stRow['full_name'],
            $stRow['email'],
            $stRow['gender'],
            $stRow['branch'],
            $stRow['batch_year'],
            $stRow['cpi'],
            $stRow['backlogs'],
            $stRow['phone'],
            $stRow['is_detained'] ? 'Yes' : 'No'
        ]);
    }
    fclose($output);
    exit;
}

// Action Handler (Add / Edit Profile / Delete / Detain Toggle / Excel CSV Import)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);

    if ($action === 'import_students') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($fileTmp, "r");

            if ($handle !== FALSE) {
                // Read header row
                $header = fgetcsv($handle, 1000, ",");

                $importedCount = 0;
                $skippedCount = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) < 10) {
                        $skippedCount++;
                        continue;
                    }

                    $enrollment = sanitize(trim($data[0]));
                    $fullName = sanitize(trim($data[1]));
                    $email = sanitize(trim($data[2]));
                    $phone = sanitize(trim($data[3]));
                    $gender = sanitize(trim($data[4]));
                    $branch = strtoupper(sanitize(trim($data[5])));
                    $cpi = floatval($data[6]);
                    $backlogs = intval($data[7]);
                    $batchYear = intval($data[8]);
                    $status = sanitize(trim($data[9]));

                    // Validation & Data Accuracy Checks
                    if (empty($enrollment) || empty($fullName) || empty($email)) {
                        $skippedCount++;
                        continue;
                    }

                    // Duplicate check for enrollment_no or email
                    $stmtChk = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = ? OR email = ?");
                    $stmtChk->execute([$enrollment, $email]);
                    if ($stmtChk->fetch()) {
                        $skippedCount++;
                        continue; // Skip existing duplicate
                    }

                    // Normalize valid choices
                    $validBranches = ['CSE', 'IT', 'ECE', 'MECH', 'CIVIL'];
                    if (!in_array($branch, $validBranches)) { $branch = 'CSE'; }
                    
                    $validStatuses = ['In-Process', 'Placed', 'Unplaced'];
                    if (!in_array($status, $validStatuses)) { $status = 'In-Process'; }

                    if ($gender !== 'Female') { $gender = 'Male'; }
                    if ($cpi < 0 || $cpi > 10) { $cpi = 7.50; }
                    if ($batchYear < 2020 || $batchYear > 2030) { $batchYear = date('Y'); }

                    // Create User Login Account
                    $defaultPass = password_hash('student123', PASSWORD_BCRYPT);
                    $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                    $stmtUser->execute([$enrollment, $defaultPass]);
                    $userId = $pdo->lastInsertId();

                    // Create Student Profile Record
                    $stmtSt = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, full_name, email, phone, gender, branch, cpi, backlogs, batch_year, placement_status, is_detained) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                    $stmtSt->execute([$userId, $enrollment, $fullName, $email, $phone, $gender, $branch, $cpi, $backlogs, $batchYear, $status]);
                    $importedCount++;
                }
                fclose($handle);

                $msg = "🎉 Excel/CSV Data Import Complete: $importedCount student candidates imported successfully! ($skippedCount records skipped due to duplicate enrollment numbers or missing fields).";
            } else {
                $error = "Unable to read the uploaded CSV file. Please upload a valid CSV template.";
            }
        } else {
            $error = "File upload failed. Please select a valid CSV file.";
        }
    } elseif ($action === 'add') {
        $enrollment = sanitize($_POST['enrollment_no']);
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $gender = sanitize($_POST['gender']);
        $branch = sanitize($_POST['branch']);
        $batchYear = intval($_POST['batch_year']);
        $cpi = floatval($_POST['cpi']);
        $backlogs = intval($_POST['backlogs']);
        $phone = sanitize($_POST['phone']);

        try {
            $pdo->beginTransaction();
            $defaultPassword = password_hash('student123', PASSWORD_BCRYPT);
            
            $stmtU = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
            $stmtU->execute([$enrollment, $defaultPassword]);
            $userId = $pdo->lastInsertId();

            $stmtS = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, full_name, email, gender, branch, batch_year, cpi, backlogs, phone, placement_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'In-Process')");
            $stmtS->execute([$userId, $enrollment, $fullName, $email, $gender, $branch, $batchYear, $cpi, $backlogs, $phone]);

            $pdo->commit();
            setFlashMessage("New student candidate registered successfully!", 'success');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage("Error adding student candidate: " . $e->getMessage(), 'danger');
        }
    } elseif ($action === 'edit_student') {
        $id = intval($_POST['student_id']);
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $gender = sanitize($_POST['gender']);
        $branch = sanitize($_POST['branch']);
        $batchYear = intval($_POST['batch_year']);
        $cpi = floatval($_POST['cpi']);
        $backlogs = intval($_POST['backlogs']);
        $phone = sanitize($_POST['phone']);

        try {
            $stmtUpdate = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, gender = ?, branch = ?, batch_year = ?, cpi = ?, backlogs = ?, phone = ? WHERE id = ?");
            $stmtUpdate->execute([$fullName, $email, $gender, $branch, $batchYear, $cpi, $backlogs, $phone, $id]);
            setFlashMessage("Candidate profile updated successfully!", 'success');
        } catch (Exception $e) {
            setFlashMessage("Error updating candidate profile: " . $e->getMessage(), 'danger');
        }
    } elseif ($action === 'toggle_detain') {
        $id = intval($_POST['student_id']);
        $currentDetain = intval($_POST['is_detained']);
        $newDetain = ($currentDetain === 1) ? 0 : 1;

        $stmt = $pdo->prepare("UPDATE students SET is_detained = ? WHERE id = ?");
        $stmt->execute([$newDetain, $id]);
        $detainMsg = ($newDetain === 1) ? "Student marked as Detained! Blocked from applying to drives." : "Student Detention removed. Eligible for drives again.";
        setFlashMessage($detainMsg, ($newDetain === 1 ? 'danger' : 'success'));
    } elseif ($action === 'toggle_placement') {
        $id = intval($_POST['student_id']);
        $targetStatus = isset($_POST['target_status']) ? sanitize($_POST['target_status']) : 'Unplaced';
        $pdo->prepare("UPDATE students SET placement_status = ?, placed_companies = '', offer_letter = '' WHERE id = ?")->execute([$targetStatus, $id]);
        $pdo->prepare("UPDATE applications SET status = 'Rejected' WHERE student_id = ? AND status = 'Selected'")->execute([$id]);
        setFlashMessage("Student status toggled back to UNPLACED.", "warning");
    } elseif ($action === 'upload_offer_letter') {
        $stId = intval($_POST['student_id']);
        $companyName = sanitize($_POST['company_name']);
        if (empty($companyName)) {
            $companyName = 'Assigned Campus Recruiter';
        }

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
            setFlashMessage("🎉 Offer Letter & Placement Selection updated for " . htmlspecialchars($st['full_name']) . " at " . htmlspecialchars($companyName) . "!", "success");
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['student_id']);
        $stmtGet = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmtGet->execute([$id]);
        $st = $stmtGet->fetch();
        if ($st) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$st['user_id']]);
            setFlashMessage("Student candidate record deleted.", 'danger');
        }
    }

    $redirectUrl = $_SERVER['PHP_SELF'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    header("Location: " . $redirectUrl);
    exit;
}

// Re-fetch students
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

$totalCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
$detainedCount = $pdo->query("SELECT COUNT(*) FROM students WHERE is_detained = 1")->fetchColumn() ?: 0;
$eligibleCount = $totalCount - $detainedCount;

$pageTitle = 'Student Candidates Management';
$currentPage = 'manage_students';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Action Row -->
<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800;">Student Candidates Directory & Master Roster</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Register candidates, import bulk Excel/CSV data, edit student profiles, and manage detentions.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button class="btn-primary" onclick="openModal('importStudentsModal')" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 800; background: #059669; border: none; box-shadow: 0 4px 14px rgba(5,150,105,0.3); cursor: pointer;">
            📥 Import Students (Excel / CSV)
        </button>
        <a href="manage_students.php?export_csv=1&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&detained=<?= urlencode($detainFilter); ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 700;">
            📊 Export Roster CSV
        </a>
        <button class="btn-primary" onclick="openModal('addStudentModal')" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);">
            + Add New Student
        </button>
    </div>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 16px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 12px; font-weight: 800;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge-status unplaced" style="margin-bottom: 16px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 12px; font-weight: 800;"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Summary KPI Cards Row -->
<div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="kpi-card" style="padding: 16px 20px;">
        <div class="kpi-top">
            <span class="kpi-title">Total Registered Candidates</span>
            <div class="kpi-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px;"><?= number_format($totalCount); ?></div>
        <span class="kpi-badge info">Total Enrolled</span>
    </div>

    <div class="kpi-card" style="padding: 16px 20px;">
        <div class="kpi-top">
            <span class="kpi-title">Eligible Candidates</span>
            <div class="kpi-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #10B981;"><?= number_format($eligibleCount); ?></div>
        <span class="kpi-badge up">🟢 Active for Drives</span>
    </div>

    <div class="kpi-card" style="padding: 16px 20px;">
        <div class="kpi-top">
            <span class="kpi-title">Detained Candidates</span>
            <div class="kpi-icon cyan"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #EF4444;"><?= number_format($detainedCount); ?></div>
        <span class="kpi-badge" style="background:#FEE2E2; color:#B91C1C;">⛔ Blocked / Detained</span>
    </div>
</div>

<!-- Multi-Filter Bar Card & Card / List View Switcher -->
<div class="table-card" style="padding: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <form method="GET" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
        <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode); ?>">

        <select name="branch" class="select-pill" onchange="this.form.submit()">
            <option value="">All Branches</option>
            <option value="CSE" <?= ($branchFilter==='CSE')?'selected':''; ?>>CSE Branch</option>
            <option value="IT" <?= ($branchFilter==='IT')?'selected':''; ?>>IT Branch</option>
            <option value="ECE" <?= ($branchFilter==='ECE')?'selected':''; ?>>ECE Branch</option>
            <option value="MECH" <?= ($branchFilter==='MECH')?'selected':''; ?>>MECH Branch</option>
            <option value="CIVIL" <?= ($branchFilter==='CIVIL')?'selected':''; ?>>CIVIL Branch</option>
        </select>

        <select name="year" class="select-pill" onchange="this.form.submit()">
            <option value="2026" <?= ($yearFilter===2026)?'selected':''; ?>>📅 2026 Batch (Current Year)</option>
            <option value="2025" <?= ($yearFilter===2025)?'selected':''; ?>>📅 2025 Batch</option>
            <option value="2024" <?= ($yearFilter===2024)?'selected':''; ?>>📅 2024 Batch</option>
            <option value="all" <?= ($yearFilter===0)?'selected':''; ?>>All Batch Years</option>
        </select>

        <select name="detained" class="select-pill" onchange="this.form.submit()">
            <option value="">All Detention Statuses</option>
            <option value="no" <?= ($detainFilter==='no')?'selected':''; ?>>🟢 Eligible (Not Detained)</option>
            <option value="yes" <?= ($detainFilter==='yes')?'selected':''; ?>>⛔ Detained Candidates</option>
        </select>

        <a href="manage_students.php" class="btn-secondary" style="padding: 8px 16px; font-size: 13px;">Reset Filter</a>
    </form>

    <!-- CARD VIEW VS LIST VIEW SWITCHER -->
    <div style="display: flex; background: #F1F5F9; border-radius: 12px; padding: 3px;">
        <a href="manage_students.php?view=list&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&detained=<?= urlencode($detainFilter); ?>" class="btn-action-icon" style="border:none; border-radius: 10px; padding: 7px 14px; font-weight: 700; <?= ($viewMode==='list')?'background:white; color:#3B82F6; box-shadow:0 2px 6px rgba(0,0,0,0.06);':'background:transparent; color:#64748B;'; ?>">
            📋 List View
        </a>
        <a href="manage_students.php?view=grid&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&detained=<?= urlencode($detainFilter); ?>" class="btn-action-icon" style="border:none; border-radius: 10px; padding: 7px 14px; font-weight: 700; <?= ($viewMode==='grid')?'background:white; color:#3B82F6; box-shadow:0 2px 6px rgba(0,0,0,0.06);':'background:transparent; color:#64748B;'; ?>">
            🎛️ Card View
        </a>
    </div>
</div>

<?php if ($viewMode === 'grid'): ?>
<!-- CARD VIEW GRID -->
<div class="student-grid-view">
    <?php foreach ($students as $st): 
        $photo = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
            ? '../uploads/' . $st['profile_photo'] 
            : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';
    ?>
        <div class="student-card-item" onclick="viewStudentDetails(<?= $st['id']; ?>)" style="cursor: pointer;">
            <div class="student-card-header">
                <img src="<?= $photo; ?>" alt="Avatar" style="width: 52px !important; height: 52px !important; border-radius: 50% !important; object-fit: cover !important;">
                <div class="student-card-info">
                    <h4><?= htmlspecialchars($st['full_name']); ?></h4>
                    <p><?= htmlspecialchars($st['email']); ?></p>
                </div>
            </div>

            <div class="student-card-meta">
                <span class="enrollment-pill"><?= htmlspecialchars($st['enrollment_no']); ?></span>
                <span class="branch-badge"><?= htmlspecialchars($st['branch']); ?> (<?= htmlspecialchars($st['batch_year']); ?>)</span>
                <span class="cpi-pill <?= ($st['cpi']>=8.5)?'high':''; ?>">★ <?= number_format($st['cpi'], 2); ?></span>
                <?php if ($st['is_detained']): ?>
                    <span class="badge-status detained"><span class="status-dot"></span> ⛔ Detained</span>
                <?php else: ?>
                    <span class="badge-status placed"><span class="status-dot"></span> 🟢 Eligible</span>
                <?php endif; ?>
            </div>

            <div class="student-card-actions" onclick="event.stopPropagation()">
                <button class="btn-action-icon reset" onclick='openEditStudentModal(<?= json_encode($st); ?>)' style="padding:6px 12px; background:#F8FAFC; color:#3B82F6; border:1px solid #BFDBFE;">✏️ Edit</button>

                <form method="POST" style="display: inline;" onsubmit="return confirm('<?= ($st['is_detained'] ? 'Undetain student?' : 'DETAIN this student? Student will be blocked from applying to drives.'); ?>');">
                    <input type="hidden" name="action" value="toggle_detain">
                    <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                    <input type="hidden" name="is_detained" value="<?= $st['is_detained']; ?>">
                    <?php if ($st['is_detained']): ?>
                        <button type="submit" class="btn-action-icon detain" style="padding:6px 12px; background:#FEE2E2; color:#DC2626; border:1px solid #FCA5A5;">⛔ Detained</button>
                    <?php else: ?>
                        <button type="submit" class="btn-action-icon detain" style="padding:6px 12px; background:#F3F4F6; color:#4B5563; border:1px solid #D1D5DB;">Mark Detain</button>
                    <?php endif; ?>
                </form>

                <div style="display: flex; gap: 4px;">
                    <button class="btn-action-icon dossier" onclick="viewStudentDetails(<?= $st['id']; ?>)" style="padding:6px 12px; background:#EFF6FF; color:#2563EB; border:1px solid #BFDBFE;">📄 Dossier</button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete student candidate account?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                        <button type="submit" class="btn-action-icon delete" style="padding:6px 10px; background:#FFF1F2; color:#E11D48; border:1px solid #FECDD3;">🗑️</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<!-- LIST VIEW TABLE -->
<div class="table-card" style="padding: 20px;">
    <div class="table-responsive">
        <table class="custom-table" style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
            <thead>
                <tr>
                    <th style="min-width: 240px; padding: 12px 16px;">Candidate Profile</th>
                    <th style="padding: 12px 16px;">Enrollment No</th>
                    <th style="padding: 12px 16px;">Branch & Batch</th>
                    <th style="padding: 12px 16px;">Academic CPI</th>
                    <th style="padding: 12px 16px;">Detain Status</th>
                    <th style="padding: 12px 16px; text-align: right;">Student Management Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $st): 
                    $photo = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
                        ? '../uploads/' . $st['profile_photo'] 
                        : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';
                ?>
                    <tr onclick="viewStudentDetails(<?= $st['id']; ?>)" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; cursor: pointer;">
                        <td style="padding: 14px 16px; border-top-left-radius: 16px; border-bottom-left-radius: 16px;">
                            <div style="display: flex !important; align-items: center !important; flex-direction: row !important; gap: 14px !important;">
                                <img src="<?= $photo; ?>" alt="Avatar" style="width: 46px !important; height: 46px !important; border-radius: 50% !important; object-fit: cover !important; border: 2px solid #3B82F6 !important;">
                                <div>
                                    <div style="font-size: 14px; font-weight: 800; color: #0F172A;"><?= htmlspecialchars($st['full_name']); ?></div>
                                    <div style="font-size: 12px; color: #64748B; margin-top: 2px;"><?= htmlspecialchars($st['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 16px;">
                            <span class="enrollment-pill"><?= htmlspecialchars($st['enrollment_no']); ?></span>
                        </td>
                        <td style="padding: 14px 16px;">
                            <span class="branch-badge"><?= htmlspecialchars($st['branch']); ?></span>
                            <span style="font-size: 12px; color: var(--text-muted);">(<?= htmlspecialchars($st['batch_year']); ?>)</span>
                        </td>
                        <td style="padding: 14px 16px;">
                            <span class="cpi-pill <?= ($st['cpi']>=8.5)?'high':''; ?>">★ <?= number_format($st['cpi'], 2); ?></span>
                        </td>
                        <td style="padding: 14px 16px;">
                            <?php if ($st['is_detained']): ?>
                                <span class="badge-status detained"><span class="status-dot"></span> ⛔ Detained</span>
                            <?php else: ?>
                                <span class="badge-status placed"><span class="status-dot"></span> 🟢 Eligible</span>
                            <?php endif; ?>
                        </td>
                        <td onclick="event.stopPropagation()" style="padding: 14px 16px; border-top-right-radius: 16px; border-bottom-right-radius: 16px;">
                            <div class="action-btn-group">

                                <!-- EDIT PROFILE BUTTON -->
                                <button class="btn-action-icon reset" onclick='openEditStudentModal(<?= json_encode($st); ?>)' title="Edit Profile">✏️ Edit</button>

                                <!-- TOGGLE DETAIN BUTTON -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?= ($st['is_detained'] ? 'Undetain student?' : 'DETAIN this student? Student will be blocked from applying to drives.'); ?>');">
                                    <input type="hidden" name="action" value="toggle_detain">
                                    <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                                    <input type="hidden" name="is_detained" value="<?= $st['is_detained']; ?>">
                                    <?php if ($st['is_detained']): ?>
                                        <button type="submit" class="btn-action-icon detain" style="background:#FEE2E2 !important; color:#DC2626 !important;">⛔ Detained</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-action-icon detain">Mark Detain</button>
                                    <?php endif; ?>
                                </form>

                                <!-- DOSSIER BUTTON -->
                                <button class="btn-action-icon dossier" onclick="viewStudentDetails(<?= $st['id']; ?>)" title="View Candidate Dossier">📄 Dossier</button>
                                
                                <!-- DELETE BUTTON -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete student candidate account?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                                    <button type="submit" class="btn-action-icon delete" title="Delete Candidate">🗑️</button>
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

<!-- Modal 1: Import Students via Excel / CSV -->
<div class="modal-overlay" id="importStudentsModal">
    <div class="modal-box" style="max-width: 620px; border-radius: 24px;">
        <div class="modal-header">
            <h3>📥 Bulk Student Candidate Import (Excel / CSV)</h3>
            <button class="close-modal" onclick="closeModal('importStudentsModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import_students">
            
            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; padding: 16px; border-radius: 16px; margin-bottom: 20px;">
                <h4 style="font-size: 13.5px; font-weight: 800; color: #1E40AF; margin-bottom: 6px;">📋 Required CSV Header Format:</h4>
                <p style="font-size: 12px; color: #3B82F6; line-height: 1.6; font-weight: 600;">
                    Your CSV spreadsheet must include the following 10 headers in order:<br>
                    <code style="background: white; padding: 4px 10px; border-radius: 8px; color: #0F172A; display: inline-block; margin-top: 6px; font-size: 11px; border: 1px solid #BFDBFE;">
                        enrollment_no, full_name, email, phone, gender, branch, cpi, backlogs, batch_year, placement_status
                    </code>
                </p>
                <div style="margin-top: 12px;">
                    <a href="manage_students.php?download_sample_csv=1" style="font-size: 12.5px; font-weight: 800; color: #2563EB; text-decoration: underline; display: inline-flex; align-items: center; gap: 6px;">
                        📥 Download Official Sample CSV Template (.csv) ↗
                    </a>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 800; font-size: 13px;">Select CSV / Excel File *</label>
                <input type="file" name="csv_file" accept=".csv, .xlsx, .xls" class="form-control" required style="border-radius: 12px; padding: 12px;">
            </div>

            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px 16px; border-radius: 14px; margin-bottom: 20px; font-size: 12px; color: #64748B; line-height: 1.6;">
                <strong style="color: #0F172A;">🛡️ Data Accuracy & Validation Controls:</strong><br>
                • Duplicate enrollment numbers or existing emails are automatically skipped.<br>
                • CPI must be between 0.00 and 10.00; invalid values default safely to 7.50.<br>
                • Default student portal password generated: <code style="color: #2563EB; font-weight: 700;">student123</code>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 14px; padding: 14px; font-weight: 800; background: #059669; border: none; box-shadow: 0 4px 14px rgba(5,150,105,0.3);">
                🚀 Import Candidate Records Now
            </button>
        </form>
    </div>
</div>

<!-- Modal 2: Add Student -->
<div class="modal-overlay" id="addStudentModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add New Student Candidate</h3>
            <button class="close-modal" onclick="closeModal('addStudentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Enrollment Number *</label>
                <input type="text" name="enrollment_no" class="form-control" placeholder="e.g. 240130316041" required>
            </div>
            <div class="form-group">
                <label>Full Candidate Name *</label>
                <input type="text" name="full_name" class="form-control" placeholder="e.g. Rahul Sharma" required>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control" placeholder="rahul@student.drsubhash.edu.in" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Branch</label>
                    <select name="branch" class="form-control">
                        <option value="CSE">CSE</option>
                        <option value="IT">IT</option>
                        <option value="ECE">ECE</option>
                        <option value="MECH">MECH</option>
                        <option value="CIVIL">CIVIL</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Batch Year</label>
                    <input type="number" name="batch_year" class="form-control" value="2024" required>
                </div>
                <div class="form-group">
                    <label>CPI / CGPA</label>
                    <input type="number" step="0.01" name="cpi" class="form-control" value="8.50" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Active Backlogs</label>
                    <input type="number" name="backlogs" class="form-control" value="0" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="9876543210">
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Create Student Credentials</button>
        </form>
    </div>
</div>

<!-- Modal 3: Edit Student Profile Modal -->
<div class="modal-overlay" id="editStudentModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Edit Candidate Profile & Academic Record</h3>
            <button class="close-modal" onclick="closeModal('editStudentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_student">
            <input type="hidden" name="student_id" id="edit_student_id">

            <div class="form-group">
                <label>Enrollment Number (Read Only)</label>
                <input type="text" id="edit_enrollment_no" class="form-control" readonly style="background:#F8FAFC; color:#64748B;">
            </div>
            <div class="form-group">
                <label>Full Candidate Name *</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" id="edit_gender" class="form-control">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Branch</label>
                    <select name="branch" id="edit_branch" class="form-control">
                        <option value="CSE">CSE</option>
                        <option value="IT">IT</option>
                        <option value="ECE">ECE</option>
                        <option value="MECH">MECH</option>
                        <option value="CIVIL">CIVIL</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Batch Year</label>
                    <input type="number" name="batch_year" id="edit_batch_year" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>CPI / CGPA</label>
                    <input type="number" step="0.01" name="cpi" id="edit_cpi" class="form-control" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Active Backlogs</label>
                    <input type="number" name="backlogs" id="edit_backlogs" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Save Profile Changes</button>
        </form>
    </div>
</div>

<!-- Modal 4: FULL SIZE Student Dossier Popup -->
<div class="modal-overlay" id="studentDossierModal">
    <div class="modal-box full-size">
        <div class="modal-header">
            <h3 style="font-size:18px; font-weight:800; color:#0F172A;">Dr. Subhash University — Student Candidate Dossier</h3>
            <button class="close-modal" onclick="closeModal('studentDossierModal')">&times;</button>
        </div>
        <div id="studentDossierContent" class="dossier-scroll-area">
            <p style="color: var(--text-muted); text-align: center;">Loading candidate dossier...</p>
        </div>
    </div>
</div>

<!-- Modal 4: Premium Upload Offer Letter Flash Modal Popup -->
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

        <form method="POST" enctype="multipart/form-data" action="manage_students.php">
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
window.openOfferUploadModal = function(studentId, studentName, companyName, appId = 0) {
    const modal = document.getElementById('uploadOfferLetterModal');
    if (!modal) return;
    const stIdEl = document.getElementById('offerStudentId');
    const stNameEl = document.getElementById('offerStudentName');
    const compNameEl = document.getElementById('offerCompanyName');

    if (stIdEl) stIdEl.value = studentId;
    if (stNameEl) stNameEl.value = studentName;
    if (compNameEl) compNameEl.value = companyName && companyName !== 'Assigned Recruiter' ? companyName : '';

    modal.classList.add('active');
};

function viewStudentDetails(studentId) {
    if (window.showToast) {
        window.showToast('📄 Candidate Dossier Opened', 'Fetching candidate profile, resume, and drive applications...', 'dossier');
    }
    const container = document.getElementById('studentDossierContent');
    openModal('studentDossierModal');
    
    fetch('manage_students.php?get_dossier=1&student_id=' + studentId)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}

function openEditStudentModal(st) {
    document.getElementById('edit_student_id').value = st.id;
    document.getElementById('edit_enrollment_no').value = st.enrollment_no;
    document.getElementById('edit_full_name').value = st.full_name;
    document.getElementById('edit_email').value = st.email;
    document.getElementById('edit_gender').value = st.gender || 'Male';
    document.getElementById('edit_branch').value = st.branch;
    document.getElementById('edit_batch_year').value = st.batch_year;
    document.getElementById('edit_cpi').value = st.cpi;
    document.getElementById('edit_backlogs').value = st.backlogs;
    document.getElementById('edit_phone').value = st.phone || '';
    openModal('editStudentModal');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
