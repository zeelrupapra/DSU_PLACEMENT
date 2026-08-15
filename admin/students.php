<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();

// =========================================================================
// CRITICAL AJAX ENDPOINT FOR SINGLE STUDENT DOSSIER POPUP (MUST RUN BEFORE HEADER)
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
        echo "<div style='padding: 40px; text-align: center; color: #EF4444; font-weight: 800; font-size: 15px;'>⚠️ Student candidate record not found. Please verify the enrollment number.</div>";
        exit;
    }

    $apps = $pdo->query("SELECT a.*, d.title, d.package_ctc, c.company_name FROM applications a JOIN drives d ON a.drive_id = d.id JOIN companies c ON d.company_id = c.id WHERE a.student_id = $stId ORDER BY a.id DESC")->fetchAll();
    $internships = $pdo->query("SELECT * FROM internships WHERE student_id = $stId ORDER BY id DESC")->fetchAll();

    $photoSrc = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
        ? '../uploads/' . $st['profile_photo'] 
        : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';

    ?>
    <div style="display: grid; grid-template-columns: 290px 1fr; gap: 24px; width: 100%; box-sizing: border-box; align-items: start;">
        
        <!-- Left Candidate Profile Card (FIXED / STICKY SIDE PANEL) -->
        <div style="position: sticky; top: 0; align-self: start; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 24px; padding: 24px; text-align: center; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.04);">
            <div class="avatar-upload-wrapper" style="margin-bottom: 16px;">
                <img src="<?= $photoSrc; ?>" alt="Candidate Photo" style="width: 108px; height: 108px; border-radius: 50%; object-fit: cover; border: 4px solid #3B82F6; box-shadow: 0 8px 20px rgba(59,130,246,0.25);">
            </div>
            <h2 style="font-size: 19px; font-weight: 800; color: #0F172A; margin-bottom: 4px;"><?= htmlspecialchars($st['full_name']); ?></h2>
            <p style="font-size: 13px; color: #2563EB; font-weight: 800; margin-bottom: 4px;"><code style="background: #EFF6FF; padding: 4px 10px; border-radius: 8px; border: 1px solid #BFDBFE;"><?= htmlspecialchars($st['enrollment_no']); ?></code></p>
            <p style="font-size: 12px; color: #64748B; word-break: break-all; margin-bottom: 16px;"><?= htmlspecialchars($st['email']); ?></p>
            
            <hr style="border: 0; border-top: 1px solid #E2E8F0; margin: 16px 0;">
            
            <div style="text-align: left; font-size: 13px; line-height: 2.1; color: #334155;">
                <p style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="color: #64748B;">Branch & Batch:</strong> 
                    <span style="font-weight: 700; color: #0F172A;"><?= htmlspecialchars($st['branch']); ?> (<?= htmlspecialchars($st['batch_year']); ?>)</span>
                </p>
                <p style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="color: #64748B;">Academic CPI:</strong> 
                    <span style="color: #2563EB; font-weight: 800; background: #EFF6FF; padding: 2px 8px; border-radius: 6px;">★ <?= number_format($st['cpi'], 2); ?></span>
                </p>
                <p style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="color: #64748B;">Active Backlogs:</strong> 
                    <span style="color: <?= ($st['backlogs']>0?'#EF4444':'#10B981'); ?>; font-weight: 800;"><?= $st['backlogs']; ?></span>
                </p>
                <p style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="color: #64748B;">Contact Phone:</strong> 
                    <span style="font-weight: 700; color: #0F172A;"><?= htmlspecialchars($st['phone']); ?></span>
                </p>
            </div>

            <div style="margin-top: 20px;">
                <?php if ($st['is_detained']): ?>
                    <span class="badge-status detained" style="padding: 10px 16px; font-weight: 800; width: 100%; justify-content: center; border-radius: 12px;">⛔ DETAINED BY ADMIN</span>
                <?php else: ?>
                    <?php $stCls = ($st['placement_status']==='Placed')?'placed':(($st['placement_status']==='In-Process')?'in-process':'unplaced'); ?>
                    <span class="badge-status <?= $stCls; ?>" style="padding: 10px 16px; font-weight: 800; width: 100%; justify-content: center; border-radius: 12px; font-size: 13px;">Status: <?= htmlspecialchars($st['placement_status']); ?></span>
                <?php endif; ?>
            </div>

            <div style="margin-top: 16px;">
                <a href="../uploads/<?= htmlspecialchars($st['resume_file']); ?>" target="_blank" class="btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 13px; font-weight: 800; border-radius: 12px; box-shadow: 0 4px 12px rgba(59,130,246,0.25);">
                    📥 Download Resume PDF
                </a>
            </div>

            <?php if (!empty($st['offer_letter'])): ?>
                <div style="margin-top: 10px;">
                    <a href="../view_offer.php?file=<?= urlencode($st['offer_letter']); ?>" target="_blank" class="btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 13px; font-weight: 800; border-radius: 12px; background: #059669; border-color: #059669; box-shadow: 0 4px 12px rgba(5,150,105,0.25); text-decoration: none;">
                        📜 View Official Offer Letter (PDF)
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Details Scrollable Column -->
        <div style="display: flex; flex-direction: column; gap: 24px; max-width: 100%; overflow: hidden;">
            
            <!-- Section 1: Live Resume Viewer -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    📄 Live Candidate Resume Document
                </h4>
                <?php if (file_exists(__DIR__ . '/../uploads/' . $st['resume_file'])): ?>
                    <iframe src="../uploads/<?= htmlspecialchars($st['resume_file']); ?>" style="width: 100%; height: 340px; border: 1px solid #E2E8F0; border-radius: 14px;"></iframe>
                <?php else: ?>
                    <div style="background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 14px; padding: 28px; text-align: center;">
                        <p style="color: #64748B; font-size: 13px;">Standard candidate resume file: <code style="color: #2563EB; font-weight: 700;"><?= htmlspecialchars($st['resume_file']); ?></code></p>
                        <a href="../uploads/<?= htmlspecialchars($st['resume_file']); ?>" target="_blank" style="color: #3B82F6; font-weight: 800; text-decoration: none; display: inline-block; margin-top: 10px;">Open Document PDF ↗</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 2: Recruitment Drive Applications -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    💼 Drive Applications & Placement History
                </h4>
                <?php if ($apps): ?>
                    <div class="table-responsive">
                        <table class="custom-table" style="font-size: 13px; min-width: 100%;">
                            <thead>
                                <tr style="background: #F8FAFC;">
                                    <th style="padding: 10px 14px;">Recruiter Company</th>
                                    <th style="padding: 10px 14px;">Drive Title</th>
                                    <th style="padding: 10px 14px;">Package CTC</th>
                                    <th style="padding: 10px 14px;">Pipeline Stage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apps as $ap): 
                                    $badgeCls = ($ap['status']==='Selected')?'placed':(($ap['status']==='Rejected')?'unplaced':'in-process');
                                ?>
                                    <tr style="background: white; border: 1px solid #E2E8F0;">
                                        <td style="padding: 12px 14px;"><strong style="color: #0F172A; font-weight: 800;"><?= htmlspecialchars($ap['company_name']); ?></strong></td>
                                        <td style="padding: 12px 14px; color: #475569;"><?= htmlspecialchars($ap['title']); ?></td>
                                        <td style="padding: 12px 14px;"><strong style="color: #10B981; font-weight: 800;"><?= htmlspecialchars($ap['package_ctc']); ?></strong></td>
                                        <td style="padding: 12px 14px;"><span class="badge-status <?= $badgeCls; ?>"><?= htmlspecialchars($ap['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: #64748B; font-size: 13px; background: #F8FAFC; padding: 16px; border-radius: 12px; text-align: center;">No placement drive applications recorded for this student candidate yet.</p>
                <?php endif; ?>
            </div>

            <!-- Section 3: Internships -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    🎓 Industrial Internships & Certificates
                </h4>
                <?php if ($internships): ?>
                    <div class="table-responsive">
                        <table class="custom-table" style="font-size: 13px; min-width: 100%;">
                            <thead>
                                <tr style="background: #F8FAFC;">
                                    <th style="padding: 10px 14px;">Organization</th>
                                    <th style="padding: 10px 14px;">Role Title</th>
                                    <th style="padding: 10px 14px;">Stipend</th>
                                    <th style="padding: 10px 14px;">Duration</th>
                                    <th style="padding: 10px 14px;">Certificate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($internships as $it): ?>
                                    <tr style="background: white; border: 1px solid #E2E8F0;">
                                        <td style="padding: 12px 14px;"><strong style="color: #0F172A; font-weight: 800;"><?= htmlspecialchars($it['company_name']); ?></strong></td>
                                        <td style="padding: 12px 14px; color: #475569;"><?= htmlspecialchars($it['title']); ?></td>
                                        <td style="padding: 12px 14px; color: #10B981; font-weight: 700;"><?= htmlspecialchars($it['stipend']); ?></td>
                                        <td style="padding: 12px 14px; color: #64748B; font-size: 12px;"><?= htmlspecialchars($it['start_date']); ?> to <?= htmlspecialchars($it['end_date']); ?></td>
                                        <td style="padding: 12px 14px;"><a href="../uploads/<?= htmlspecialchars($it['certificate_file']); ?>" target="_blank" style="color: #2563EB; font-weight: 800; text-decoration: none;">📄 View Certificate</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: #64748B; font-size: 13px; background: #F8FAFC; padding: 16px; border-radius: 12px; text-align: center;">No internship records submitted yet.</p>
                <?php endif; ?>
            </div>

            <!-- Section 4: Workshop & Seminar Registrations & Reports -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    🎓 Workshop & Seminar Event Registrations & Reports
                </h4>
                <?php 
                    $wkRegs = $pdo->query("SELECT wr.*, w.title, w.event_date, w.speaker, w.venue FROM workshop_registrations wr JOIN workshops w ON wr.workshop_id = w.id WHERE wr.student_id = $stId ORDER BY wr.id DESC")->fetchAll();
                ?>
                <?php if (!empty($wkRegs)): ?>
                    <div class="table-responsive">
                        <table class="custom-table" style="font-size: 13px; min-width: 100%;">
                            <thead>
                                <tr style="background: #F8FAFC;">
                                    <th style="padding: 10px 14px;">Event Title</th>
                                    <th style="padding: 10px 14px;">Keynote Speaker</th>
                                    <th style="padding: 10px 14px;">Event Schedule</th>
                                    <th style="padding: 10px 14px;">Attendance</th>
                                    <th style="padding: 10px 14px;">Uploaded Report</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wkRegs as $wr): ?>
                                    <tr style="background: white; border: 1px solid #E2E8F0;">
                                        <td style="padding: 12px 14px;"><strong style="color: #0F172A; font-weight: 800;"><?= htmlspecialchars($wr['title']); ?></strong></td>
                                        <td style="padding: 12px 14px; color: #475569;"><?= htmlspecialchars($wr['speaker']); ?></td>
                                        <td style="padding: 12px 14px; color: #64748B; font-size: 12px;"><?= date('d M Y, h:i A', strtotime($wr['event_date'])); ?></td>
                                        <td style="padding: 12px 14px;"><span class="badge-status info"><?= htmlspecialchars($wr['attendance_status'] ?: 'Registered'); ?></span></td>
                                        <td style="padding: 12px 14px;">
                                            <?php if (!empty($wr['report_file']) && file_exists(__DIR__ . '/../uploads/' . $wr['report_file'])): ?>
                                                <a href="../uploads/<?= htmlspecialchars($wr['report_file']); ?>" target="_blank" style="color: #059669; font-weight: 800; text-decoration: none; background: #ECFDF5; padding: 5px 12px; border-radius: 8px; border: 1px solid #A7F3D0; display: inline-flex; align-items: center; gap: 4px;">📜 View Report Document</a>
                                            <?php else: ?>
                                                <span style="color: #94A3B8; font-size: 12px;">No Report Uploaded</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: #64748B; font-size: 13px; background: #F8FAFC; padding: 16px; border-radius: 12px; text-align: center;">No workshop or seminar event registrations recorded for this student candidate yet.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <?php
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

// Process Status Change, Deletion, and Excel / CSV Import Actions
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
    } elseif ($action === 'change_status') {
        $id = intval($_POST['student_id']);
        $newStatus = sanitize($_POST['target_status']);

        if ($newStatus === 'Placed') {
            $check = canSelectStudentForCompany($pdo, $id);
            if (!$check['allowed']) {
                setFlashMessage($check['reason'], 'danger');
                header("Location: students.php");
                exit;
            }
            $stmt = $pdo->prepare("UPDATE students SET placement_status = 'Placed' WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage("Candidate placement status updated to: Placed", 'success');
        } elseif ($newStatus === 'Unplaced') {
            // Clear single placement lock, offer letter, and placed company tags
            $pdo->prepare("UPDATE students SET placement_status = 'Unplaced', placed_companies = NULL, offer_letter = NULL WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE applications SET status = 'Rejected' WHERE student_id = ? AND status = 'Selected'")->execute([$id]);
            setFlashMessage("Candidate status toggled to Unplaced. Single placement lock and offer letter reset successfully.", 'warning');
        } else {
            $stmt = $pdo->prepare("UPDATE students SET placement_status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            setFlashMessage("Candidate placement status updated to: $newStatus", 'info');
        }
        header("Location: students.php");
        exit;
    } elseif ($action === 'reset_status') {
        $id = intval($_POST['student_id']);
        $pdo->prepare("UPDATE students SET placement_status = 'In-Process', placed_companies = NULL, offer_letter = NULL WHERE id = ?")->execute([$id]);
        $pdo->prepare("UPDATE applications SET status = 'Applied' WHERE student_id = ? AND status = 'Selected'")->execute([$id]);
        setFlashMessage("Candidate placement status reset to default pipeline state: In-Process", 'info');
        header("Location: students.php");
        exit;
    } elseif ($action === 'upload_offer_letter') {
        $stId = intval($_POST['student_id']);
        $companyName = sanitize($_POST['company_name']);
        if (empty($companyName)) {
            $companyName = 'Assigned Campus Recruiter';
        }

        $check = canSelectStudentForCompany($pdo, $stId);
        if (!$check['allowed']) {
            setFlashMessage($check['reason'], 'danger');
            header("Location: students.php");
            exit;
        }

        $stmtSt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmtSt->execute([$stId]);
        $st = $stmtSt->fetch();

        if ($st) {
            $fileName = $st['offer_letter']; // retain existing file if any

            if (isset($_FILES['offer_file']) && $_FILES['offer_file']['error'] === UPLOAD_ERR_OK) {
                $fileExt = strtolower(pathinfo($_FILES['offer_file']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['pdf', 'docx', 'doc', 'png', 'jpg', 'jpeg'];

                if (in_array($fileExt, $allowedExts)) {
                    $newFileName = 'offer_letter_' . $st['enrollment_no'] . '_' . time() . '.' . $fileExt;
                    $targetPath = __DIR__ . '/../uploads/' . $newFileName;

                    if (move_uploaded_file($_FILES['offer_file']['tmp_name'], $targetPath)) {
                        $fileName = $newFileName;

                        // Send automated email to student
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

            // Always update placement_status to 'Placed' AND placed_companies to $companyName
            $pdo->prepare("UPDATE students SET placement_status = 'Placed', placed_companies = ?, offer_letter = ? WHERE id = ?")->execute([$companyName, $fileName, $stId]);

            setFlashMessage("🎉 Candidate " . htmlspecialchars($st['full_name']) . " successfully marked as Placed at " . htmlspecialchars($companyName) . "!", "success");
        } else {
            setFlashMessage("Candidate record not found.", "warning");
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['student_id']);

        $stmtU = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmtU->execute([$id]);
        $st = $stmtU->fetch();

        if ($st) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$st['user_id']]);
            setFlashMessage("Student candidate account deleted successfully.", 'danger');
        }
    }

    $redirectUrl = $_SERVER['PHP_SELF'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    header("Location: " . $redirectUrl);
    exit;
}

// Filtering parameters
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

$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$searchQuery = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$viewMode = isset($_GET['view']) ? sanitize($_GET['view']) : 'list'; // 'list' or 'grid'

// STRICT EXCLUSION OF DETAINED STUDENTS IN PLACEMENT DETAILS PAGE
$query = "SELECT s.*, 
          (SELECT GROUP_CONCAT(c.company_name SEPARATOR ', ') 
           FROM applications a JOIN drives d ON a.drive_id = d.id JOIN companies c ON d.company_id = c.id 
           WHERE a.student_id = s.id AND a.status = 'Selected') as placed_companies,
          (SELECT d.package_ctc 
           FROM applications a JOIN drives d ON a.drive_id = d.id 
           WHERE a.student_id = s.id AND a.status = 'Selected' ORDER BY a.id DESC LIMIT 1) as top_package_ctc
          FROM students s WHERE s.is_detained = 0";
$params = [];

if ($branchFilter) { $query .= " AND s.branch = ?"; $params[] = $branchFilter; }
if ($yearFilter) { $query .= " AND s.batch_year = ?"; $params[] = $yearFilter; }
if ($statusFilter) { 
    $query .= " AND s.placement_status = ?"; 
    $params[] = $statusFilter; 
}
if ($searchQuery) {
    $query .= " AND (s.full_name LIKE ? OR s.enrollment_no LIKE ? OR s.email LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}
$query .= " ORDER BY s.id DESC";

// EXCEL / CSV EXPORT ACTION HANDLER
if (isset($_GET['export_csv'])) {
    $stmtExp = $pdo->prepare($query);
    $stmtExp->execute($params);
    $exportStudents = $stmtExp->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=placement_details_export_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Enrollment No', 'Full Candidate Name', 'Email', 'Gender', 'Branch', 'Batch Year', 'CPI', 'Backlogs', 'Phone', 'Placement Status', 'Placed Company', 'Package CTC']);

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
            $stRow['placement_status'],
            $stRow['placed_companies'] ?: 'N/A',
            $stRow['top_package_ctc'] ?: 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Summary Counts for Active Eligible Candidates
$totalCount = $pdo->query("SELECT COUNT(*) FROM students WHERE is_detained = 0")->fetchColumn() ?: 0;
$placedCount = $pdo->query("SELECT COUNT(DISTINCT id) FROM students WHERE placement_status = 'Placed' AND is_detained = 0")->fetchColumn() ?: 0;
$inProcessCount = $pdo->query("SELECT COUNT(*) FROM students WHERE placement_status = 'In-Process' AND is_detained = 0")->fetchColumn() ?: 0;
$unplacedCount = $pdo->query("SELECT COUNT(*) FROM students WHERE placement_status = 'Unplaced' AND is_detained = 0")->fetchColumn() ?: 0;

$pageTitle = 'Placement Details & Status Control';
$currentPage = 'students';
include __DIR__ . '/../includes/header.php';
?>

<!-- Executive Header Card -->
<div style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; padding: 28px 32px; border-radius: 24px; margin-bottom: 28px; box-shadow: 0 12px 30px -10px rgba(15, 23, 42, 0.3);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h3 style="font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.5px; margin-bottom: 6px;">
                🎓 Master Cohort Placement Roster
            </h3>
            <p style="font-size: 13px; color: #94A3B8; font-weight: 600;">
                Comprehensive placement status tracker, dynamic recruiter company assignments, and candidate dossier management.
            </p>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Live Search Bar with Cancel Search Option -->
            <form method="GET" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="branch" value="<?= htmlspecialchars($branchFilter); ?>">
                <input type="hidden" name="year" value="<?= $yearFilter; ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter); ?>">
                <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode); ?>">
                
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="Search candidate name / enrollment..." style="padding: 10px 34px 10px 38px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: white; font-size: 13px; outline: none; width: 240px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <?php if ($searchQuery): ?>
                        <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #94A3B8; text-decoration: none; font-size: 14px; font-weight: 800;" title="Clear Search">✕</a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn-primary" style="padding: 10px 16px; font-size: 13px; border-radius: 12px;">Search</button>

                <?php if (!empty($searchQuery)): ?>
                    <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>" class="btn-secondary" style="padding: 10px 14px; font-size: 13px; border-radius: 12px; font-weight: 800; background: rgba(239, 68, 68, 0.2); color: #FECDD3; border: 1px solid rgba(239, 68, 68, 0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;" title="Clear search query">
                        ✖ Cancel Search
                    </a>
                <?php endif; ?>
            </form>

            <a href="students.php?export_csv=1&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>&q=<?= urlencode($searchQuery); ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 800; background: #3B82F6; color: white; border: none; box-shadow: 0 4px 14px rgba(59,130,246,0.3);">
                📊 Export Roster CSV
            </a>
        </div>
    </div>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge-status unplaced" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Executive Summary KPI Cards Row (4 Core Placement Metrics) -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px;">
    <div class="kpi-card" style="padding: 20px 24px; border-radius: 20px;">
        <div class="kpi-icon blue">🎓</div>
        <div>
            <div class="kpi-value"><?= $totalCount; ?></div>
            <div class="kpi-label">Active Candidate Pool</div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 20px 24px; border-radius: 20px;">
        <div class="kpi-icon green">🎉</div>
        <div>
            <div class="kpi-value" style="color: #10B981;"><?= $placedCount; ?></div>
            <div class="kpi-label">Confirmed Placements</div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 20px 24px; border-radius: 20px;">
        <div class="kpi-icon amber">⏳</div>
        <div>
            <div class="kpi-value" style="color: #F59E0B;"><?= $inProcessCount; ?></div>
            <div class="kpi-label">Pipeline In-Process</div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 20px 24px; border-radius: 20px;">
        <div class="kpi-icon red">🎯</div>
        <div>
            <div class="kpi-value" style="color: #EF4444;"><?= $unplacedCount; ?></div>
            <div class="kpi-label">Available Unplaced</div>
        </div>
    </div>
</div>

<!-- Multi-Dimensional Filter Bar with Search Clear -->
<div class="table-card" style="padding: 18px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background: white; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
    <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode); ?>">
        <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery); ?>">
        
        <select name="branch" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px;">
            <option value="">All Academic Branches</option>
            <option value="CSE" <?= ($branchFilter==='CSE')?'selected':''; ?>>CSE Branch</option>
            <option value="IT" <?= ($branchFilter==='IT')?'selected':''; ?>>IT Branch</option>
            <option value="ECE" <?= ($branchFilter==='ECE')?'selected':''; ?>>ECE Branch</option>
            <option value="MECH" <?= ($branchFilter==='MECH')?'selected':''; ?>>MECH Branch</option>
            <option value="CIVIL" <?= ($branchFilter==='CIVIL')?'selected':''; ?>>CIVIL Branch</option>
        </select>

        <select name="year" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px;">
            <option value="2026" <?= ($yearFilter===2026)?'selected':''; ?>>📅 2026 Batch (Current Year)</option>
            <option value="2025" <?= ($yearFilter===2025)?'selected':''; ?>>📅 2025 Batch</option>
            <option value="2024" <?= ($yearFilter===2024)?'selected':''; ?>>📅 2024 Batch</option>
            <option value="all" <?= ($yearFilter===0)?'selected':''; ?>>All Batch Years</option>
        </select>

        <select name="status" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px;">
            <option value="">All Placement Pipeline States</option>
            <option value="Placed" <?= ($statusFilter==='Placed')?'selected':''; ?>>🎉 Placed Candidates Only</option>
            <option value="In-Process" <?= ($statusFilter==='In-Process')?'selected':''; ?>>⏳ In-Process Pipeline Only</option>
            <option value="Unplaced" <?= ($statusFilter==='Unplaced')?'selected':''; ?>>🎯 Unplaced Candidates Only</option>
        </select>

        <?php if ($branchFilter || $yearFilter || $statusFilter || $searchQuery): ?>
            <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>" class="btn-secondary" style="padding: 9px 16px; font-size: 13px; border-radius: 12px; font-weight: 800; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; text-decoration: none;">
                ↺ Show All Cards (Reset Filters)
            </a>
        <?php endif; ?>
    </form>

    <!-- View Mode Switcher -->
    <div style="display: flex; gap: 8px; align-items: center; background: #F1F5F9; padding: 4px; border-radius: 14px;">
        <a href="students.php?view=grid&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>&q=<?= urlencode($searchQuery); ?>" 
           class="btn-secondary <?= ($viewMode==='grid')?'btn-primary':''; ?>" 
           style="padding: 8px 16px; font-size: 13px; border-radius: 10px; border: none; font-weight: 800; text-decoration: none;">
            🌁 Grid Cards
        </a>
        <a href="students.php?view=list&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>&q=<?= urlencode($searchQuery); ?>" 
           class="btn-secondary <?= ($viewMode==='list')?'btn-primary':''; ?>" 
           style="padding: 8px 16px; font-size: 13px; border-radius: 10px; border: none; font-weight: 800; text-decoration: none;">
            📑 Detailed Table
        </a>
    </div>
</div>

<?php if (empty($students)): ?>
    <!-- Empty State Result Card -->
    <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 24px; border: 1px dashed #CBD5E1; margin-bottom: 28px;">
        <div style="font-size: 44px; margin-bottom: 12px;">🔍</div>
        <h4 style="font-size: 18px; font-weight: 800; color: #0F172A;">No Student Candidates Found</h4>
        <p style="color: #64748B; font-size: 13px; margin-top: 4px; margin-bottom: 18px;">
            No candidate records match your current search term "<strong><?= htmlspecialchars($searchQuery); ?></strong>" or active filters.
        </p>
        <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>" class="btn-primary" style="display: inline-flex; border-radius: 12px; padding: 10px 24px; font-weight: 800; text-decoration: none;">
            ↺ Cancel Search & Show All Cards
        </a>
    </div>
<?php elseif ($viewMode === 'grid'): ?>

<!-- VIEW MODE 1: EXECUTIVE GRID CARDS VIEW -->
<div class="student-grid-view" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <?php foreach ($students as $st): 
        $stCls = ($st['placement_status']==='Placed')?'placed':(($st['placement_status']==='In-Process')?'in-process':'unplaced');
        $photoSrc = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
            ? '../uploads/' . $st['profile_photo'] 
            : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';
    ?>
        <div class="table-card student-card" onclick="viewStudentDetails(<?= $st['id']; ?>)" style="padding: 24px; border-radius: 24px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; cursor: pointer;">
            <div>
                <!-- Top Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; margin-bottom: 16px;">
                    <div style="display: flex; gap: 14px; align-items: center;">
                        <img src="<?= $photoSrc; ?>" alt="Candidate Avatar" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #3B82F6; box-shadow: 0 4px 10px rgba(59,130,246,0.2);">
                        <div>
                            <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
                                <?= htmlspecialchars($st['full_name']); ?>
                            </h4>
                            <code style="font-size: 11.5px; color: #2563EB; font-weight: 800; display: inline-block; margin-top: 2px;"><?= htmlspecialchars($st['enrollment_no']); ?></code>
                        </div>
                    </div>

                    <span class="badge-status <?= $stCls; ?>" style="font-size: 12px; font-weight: 800; white-space: nowrap;">
                        <?= htmlspecialchars($st['placement_status']); ?>
                    </span>
                </div>

                <!-- Academic Specs Grid -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px; border-radius: 14px; margin-bottom: 16px; text-align: center;">
                    <div>
                        <span style="font-size: 10px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block;">Branch</span>
                        <strong style="font-size: 12px; color: #0F172A;"><?= htmlspecialchars($st['branch']); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block;">CPI Score</span>
                        <strong style="font-size: 12px; color: #2563EB;">★ <?= number_format($st['cpi'], 2); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block;">Backlogs</span>
                        <strong style="font-size: 12px; color: <?= ($st['backlogs']>0?'#EF4444':'#10B981'); ?>;"><?= $st['backlogs']; ?></strong>
                    </div>
                </div>

                <!-- Placed Company Banner (If Placed) -->
                <?php if ($st['placement_status'] === 'Placed' && $st['placed_companies']): 
                    $firstCompanyBanner = trim(explode(',', $st['placed_companies'])[0]);
                ?>
                    <div style="background: #ECFDF5; border: 1px solid #A7F3D0; padding: 10px 14px; border-radius: 12px; margin-bottom: 16px;">
                        <span style="font-size: 11px; font-weight: 800; color: #059669; text-transform: uppercase; display: block;">🎉 Placed Recruiter & Package:</span>
                        <strong style="font-size: 13px; color: #065F46;" title="<?= htmlspecialchars($st['placed_companies']); ?>">🏢 <?= htmlspecialchars($firstCompanyBanner); ?></strong> 
                        <?php if ($st['top_package_ctc']): ?>
                            • <strong style="color: #10B981;"><?= htmlspecialchars($st['top_package_ctc']); ?></strong>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card Bottom Action Row -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid #E2E8F0; margin-top: 10px; flex-wrap: wrap; gap: 8px;">
                <button class="btn-action-icon edit" onclick="viewStudentDetails(<?= $st['id']; ?>)" style="padding: 6px 14px; font-size: 12px; font-weight: 800; border-radius: 10px; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; cursor: pointer;">
                    📄 View Dossier
                </button>

                <div onclick="event.stopPropagation()" class="action-btn-group" style="display: flex; gap: 6px;">
                    <?php if ($st['placement_status'] !== 'Placed'): ?>
                        <button type="button" class="btn-action-icon place" data-id="<?= $st['id']; ?>" data-name="<?= htmlspecialchars($st['full_name'], ENT_QUOTES); ?>" data-company="<?= htmlspecialchars($st['placed_companies'] ?: '', ENT_QUOTES); ?>" onclick="triggerOfferUploadModal(this)" title="Click to pop up Offer Letter upload modal and mark as Placed" style="padding: 6px 12px; height: 34px; border-radius: 10px; font-weight: 800; background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; cursor: pointer;">
                            ✓ Placed
                        </button>
                    <?php else: ?>
                        <?php if (!empty($st['offer_letter'])): ?>
                            <a href="../view_offer.php?file=<?= urlencode($st['offer_letter']); ?>" target="_blank" onclick="event.stopPropagation()" class="btn-action-icon" title="View Uploaded Offer Letter Document" style="padding: 6px 12px; height: 34px; border-radius: 10px; font-weight: 800; background: #FEF3C7; color: #D97706; border: 1px solid #FCD34D; text-decoration: none; display: inline-flex; align-items: center;">
                                📜 View Offer
                            </a>
                        <?php endif; ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Mark student as UNPLACED? This will clear placement status back to Unplaced.');">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                            <input type="hidden" name="target_status" value="Unplaced">
                            <button type="submit" class="btn-action-icon unplace" title="Mark candidate as Unplaced (Direct Toggle - No Popup)" style="padding: 6px 12px; height: 34px; border-radius: 10px; font-weight: 800; background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; cursor: pointer;">
                                ✗ Unplace
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($st['placement_status'] !== 'In-Process'): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Reset placement status back to default pipeline state (In-Process)?');">
                            <input type="hidden" name="action" value="reset_status">
                            <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                            <button type="submit" class="btn-action-icon reset" title="Reset status to In-Process">↺ Reset</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete student candidate account?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                        <button type="submit" class="btn-action-icon delete" title="Delete Candidate">🗑️</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<!-- VIEW MODE 2: DETAILED DATA TABLE VIEW -->
<div class="table-card" style="padding: 0; background: white; border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; margin-bottom: 32px;">
    <div class="table-responsive">
        <table class="custom-table" style="width: 100%; border-collapse: separate; border-spacing: 0 8px; padding: 12px;">
            <thead>
                <tr style="background: #F8FAFC;">
                    <th style="padding: 14px 16px;">Candidate Profile & Code</th>
                    <th style="padding: 14px 16px;">Branch & Batch</th>
                    <th style="padding: 14px 16px;">CPI Score</th>
                    <th style="padding: 14px 16px;">Backlogs</th>
                    <th style="padding: 14px 16px;">Contact Phone</th>
                    <th style="padding: 14px 16px;">Recruiter Assignment</th>
                    <th style="padding: 14px 16px;">Pipeline Status</th>
                    <th style="text-align: right; padding: 14px 16px;">Placement Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $st): 
                    $stCls = ($st['placement_status']==='Placed')?'placed':(($st['placement_status']==='In-Process')?'in-process':'unplaced');
                    $photoSrc = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
                        ? '../uploads/' . $st['profile_photo'] 
                        : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';
                ?>
                    <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                        <td style="padding: 14px 16px;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <img src="<?= $photoSrc; ?>" alt="Avatar" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #3B82F6;">
                                <div>
                                    <strong style="font-size: 14px; color: #0F172A;">
                                        <?= htmlspecialchars($st['full_name']); ?>
                                    </strong><br>
                                    <code style="font-size: 11.5px; color: #2563EB; font-weight: 700;"><?= htmlspecialchars($st['enrollment_no']); ?></code>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 16px;">
                            <span class="branch-badge"><?= htmlspecialchars($st['branch']); ?></span>
                            <span style="font-size: 12px; color: #64748B; font-weight: 700;">Batch '<?= substr($st['batch_year'], -2); ?></span>
                        </td>
                        <td style="padding: 14px 16px;"><strong style="color: #2563EB; font-size: 14px;">★ <?= number_format($st['cpi'], 2); ?></strong></td>
                        <td style="padding: 14px 16px;"><strong style="color: <?= ($st['backlogs']>0?'#EF4444':'#10B981'); ?>; font-size: 14px;"><?= $st['backlogs']; ?></strong></td>
                        <td style="padding: 14px 16px;"><span style="font-size: 13px; color: #334155; font-weight: 600;"><?= htmlspecialchars($st['phone']); ?></span></td>
                        <td style="padding: 14px 16px;">
                            <?php if ($st['placement_status'] === 'Placed' && $st['placed_companies']): 
                                $firstCompany = trim(explode(',', $st['placed_companies'])[0]);
                            ?>
                                <span style="display: inline-block; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 800; color: #059669; font-size: 13px;" title="<?= htmlspecialchars($st['placed_companies']); ?>">
                                    🏢 <?= htmlspecialchars($firstCompany); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #94A3B8; font-size: 12px;">None Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 14px 16px;">
                            <span class="badge-status <?= $stCls; ?>" style="font-weight: 800;"><?= htmlspecialchars($st['placement_status']); ?></span>
                        </td>
                        <td style="text-align: right; padding: 14px 16px; white-space: nowrap;">
                            <div class="action-btn-group">
                                <button class="btn-action-icon edit" onclick="viewStudentDetails(<?= $st['id']; ?>)" title="View Candidate Dossier" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; cursor: pointer;">
                                    📄 Dossier
                                </button>

                                <?php if ($st['placement_status'] !== 'Placed'): ?>
                                    <!-- UNPLACED -> PLACED: CLICKING '✓ Placed' OPENS OFFER LETTER POPUP MODAL -->
                                    <button type="button" class="btn-action-icon place" data-id="<?= $st['id']; ?>" data-name="<?= htmlspecialchars($st['full_name'], ENT_QUOTES); ?>" data-company="<?= htmlspecialchars($st['placed_companies'] ?: '', ENT_QUOTES); ?>" onclick="triggerOfferUploadModal(this)" title="Click to pop up Offer Letter upload modal and mark as Placed" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; cursor: pointer;">
                                        ✓ Placed
                                    </button>
                                <?php else: ?>
                                    <!-- ALREADY PLACED: SHOW VIEW OFFER BADGE & '✗ Unplace' TOGGLE BUTTON (NO POPUP ON UNPLACE) -->
                                    <?php if (!empty($st['offer_letter'])): ?>
                                        <a href="../view_offer.php?file=<?= urlencode($st['offer_letter']); ?>" target="_blank" onclick="event.stopPropagation()" class="btn-action-icon" title="View Uploaded Offer Letter Document" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #FEF3C7; color: #D97706; border: 1px solid #FCD34D; text-decoration: none; display: inline-flex; align-items: center;">
                                            📜 View Offer
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Mark student as UNPLACED? This will clear placement status back to Unplaced.');">
                                        <input type="hidden" name="action" value="change_status">
                                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                                        <input type="hidden" name="target_status" value="Unplaced">
                                        <button type="submit" class="btn-action-icon unplace" title="Mark candidate as Unplaced (Direct Toggle - No Popup)" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; cursor: pointer;">
                                            ✗ Unplace
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($st['placement_status'] !== 'In-Process'): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Reset placement status back to default pipeline state (In-Process)?');">
                                        <input type="hidden" name="action" value="reset_status">
                                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                                        <button type="submit" class="btn-action-icon reset" title="Reset status to In-Process">↺ Reset</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete Candidate -->
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
                    <a href="students.php?download_sample_csv=1" style="font-size: 12.5px; font-weight: 800; color: #2563EB; text-decoration: underline; display: inline-flex; align-items: center; gap: 6px;">
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

<!-- Modal 2: Premium Upload Offer Letter Flash Modal Popup -->
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

        <form method="POST" enctype="multipart/form-data" action="students.php">
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

<!-- Modal 3: Full Size Dossier Modal Popup -->
<div class="modal-overlay" id="studentDossierModal">
    <div class="modal-box full-size">
        <div class="modal-header">
            <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Dr. Subhash University — Student Candidate Dossier</h3>
            <button class="close-modal" onclick="closeModal('studentDossierModal')">&times;</button>
        </div>
        <div id="studentDossierContent" class="dossier-scroll-area">
            <p style="color: var(--text-muted); text-align: center; padding: 30px;">Loading candidate dossier...</p>
        </div>
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
        window.showToast('📄 Candidate Dossier', 'Fetching candidate profile & credentials...', 'dossier');
    }

    const container = document.getElementById('studentDossierContent');
    container.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 40px; font-weight: 700;">Loading candidate profile & credentials...</p>';
    
    openModal('studentDossierModal');

    fetch('students.php?get_dossier=1&student_id=' + studentId)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(err => {
            container.innerHTML = '<p style="color: #EF4444; text-align: center; padding: 40px; font-weight: 800;">Failed to load candidate details.</p>';
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
