<?php
require_once __DIR__ . '/../config/db.php';
requireStudent();

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];
$msg = '';
$error = '';

// Fetch Student details
$stmtSt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmtSt->execute([$studentId]);
$student = $stmtSt->fetch();

if (!$student) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'register_workshop') {
        $workshopId = intval($_POST['workshop_id']);
        try {
            $stmt = $pdo->prepare("INSERT INTO workshop_registrations (workshop_id, student_id) VALUES (?, ?)");
            $stmt->execute([$workshopId, $studentId]);
            $msg = "🎉 Registered for workshop successfully! You can upload your workshop report document below.";
        } catch (Exception $e) {
            $error = "You are already registered for this workshop event.";
        }
    } elseif ($action === 'upload_report') {
        $workshopId = intval($_POST['workshop_id']);
        
        $stmtWk = $pdo->prepare("SELECT * FROM workshops WHERE id = ?");
        $stmtWk->execute([$workshopId]);
        $wkData = $stmtWk->fetch();

        if ($wkData) {
            $cutoff = strtotime($wkData['event_date']) + 86400; // 24 hours post event date
            if (time() > $cutoff) {
                $error = "Report submission window for this workshop is closed (24 hours post-event limit expired).";
            } elseif (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'docx', 'doc', 'png', 'jpg', 'jpeg'];
                if (in_array($ext, $allowed)) {
                    $fileName = 'wk_report_' . $student['enrollment_no'] . '_' . $workshopId . '_' . time() . '.' . $ext;
                    $targetPath = __DIR__ . '/../uploads/' . $fileName;
                    if (move_uploaded_file($_FILES['report_file']['tmp_name'], $targetPath)) {
                        $pdo->prepare("UPDATE workshop_registrations SET report_file = ? WHERE workshop_id = ? AND student_id = ?")->execute([$fileName, $workshopId, $studentId]);
                        $msg = "🎉 Workshop report uploaded successfully! Training & Placement Cell admin can now view your document in your Candidate Dossier.";
                    } else {
                        $error = "Failed to save uploaded report file.";
                    }
                } else {
                    $error = "Invalid file format. Please upload a PDF, DOCX, or Image file.";
                }
            } else {
                $error = "Please select a valid report file to upload.";
            }
        }
    }
}

// Fetch all workshops
$workshops = $pdo->query("SELECT w.*, (SELECT COUNT(*) FROM workshop_registrations wr WHERE wr.workshop_id = w.id) as registered_count FROM workshops w ORDER BY w.event_date ASC")->fetchAll();

// Fetch student's registered workshop IDs & attendance map & report files
$stmtMyRegs = $pdo->prepare("SELECT workshop_id, attendance_status, report_file FROM workshop_registrations WHERE student_id = ?");
$stmtMyRegs->execute([$studentId]);
$myRegsRaw = $stmtMyRegs->fetchAll();

$registeredMap = [];
$registeredMapFiles = [];
foreach ($myRegsRaw as $mr) {
    $registeredMap[$mr['workshop_id']] = $mr['attendance_status'] ?: 'Registered';
    $registeredMapFiles[$mr['workshop_id']] = $mr['report_file'];
}

$pageTitle = 'Workshops & Skill Bootcamps';
$currentPage = 'workshops';
include __DIR__ . '/../includes/header.php';
?>

<!-- Executive Banner -->
<div style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; padding: 28px 32px; border-radius: 24px; margin-bottom: 28px; box-shadow: 0 12px 30px -10px rgba(15, 23, 42, 0.3);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h3 style="font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.5px; margin-bottom: 6px;">
                🎓 Expert Workshops & Skill Bootcamps
            </h3>
            <p style="font-size: 13px; color: #94A3B8; font-weight: 600;">
                Enhance technical mastery, coding performance, and interview readiness with campus keynotes and corporate seminars.
            </p>
        </div>
        <div>
            <a href="drives_history.php?tab=workshops" class="btn-secondary" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
                📜 View My Event Bookings (<?= count($registeredMap); ?>)
            </a>
        </div>
    </div>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 20px; display: inline-block; font-size: 14px; padding: 10px 18px; border-radius: 12px; font-weight: 800;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge-status unplaced" style="margin-bottom: 20px; display: inline-block; font-size: 14px; padding: 10px 18px; border-radius: 12px; font-weight: 800;"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Workshop Grid Layout -->
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
    <?php foreach ($workshops as $wk): 
        $isRegistered = isset($registeredMap[$wk['id']]);
        $myStatus = $isRegistered ? $registeredMap[$wk['id']] : null;
        $isPast = (strtotime($wk['event_date']) < time());
    ?>
        <div class="kpi-card" style="padding: 24px; border-radius: 24px; background: white; border: 1px solid #E2E8F0; box-shadow: 0 4px 14px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 12px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #2563EB; background: #EFF6FF; padding: 4px 10px; border-radius: 8px; border: 1px solid #BFDBFE;">
                            🎓 Skill Workshop & Bootcamp
                        </span>
                        <h4 style="font-size: 18px; font-weight: 800; color: #0F172A; margin-top: 8px; line-height: 1.3;">
                            <?= htmlspecialchars($wk['title']); ?>
                        </h4>
                    </div>
                    <span style="font-size: 12px; font-weight: 800; color: #475569; background: #F1F5F9; padding: 6px 12px; border-radius: 10px; flex-shrink: 0;">
                        👥 <?= $wk['registered_count']; ?> / <?= ($wk['capacity'] ?: 100); ?> Enrolled
                    </span>
                </div>

                <div style="font-size: 13px; color: #2563EB; font-weight: 700; margin-bottom: 14px;">
                    🎤 Keynote Speaker: <strong><?= htmlspecialchars($wk['speaker']); ?></strong>
                </div>

                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; padding: 14px 16px; margin-bottom: 20px; font-size: 12.5px; line-height: 1.8; color: #334155;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B; font-weight: 700;">📍 Campus Venue:</span>
                        <strong style="color: #0F172A;"><?= htmlspecialchars($wk['venue']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B; font-weight: 700;">🕒 Event Schedule:</span>
                        <strong style="color: #0F172A;"><?= date('d M Y, h:i A', strtotime($wk['event_date'])); ?></strong>
                    </div>
                </div>

                <p style="font-size: 13px; color: #64748B; line-height: 1.6; margin-bottom: 20px;">
                    <?= htmlspecialchars($wk['description'] ?: 'Hands-on technical workshop organized by Dr. Subhash University Corporate Relations Cell.'); ?>
                </p>
            </div>

            <div>
                <?php if ($isRegistered): ?>
                    <?php 
                        $attCls = ($myStatus === 'Attended') ? 'placed' : (($myStatus === 'Absent') ? 'unplaced' : 'in-process'); 
                        $eventTimestamp = strtotime($wk['event_date']);
                        $cutoffTimestamp = $eventTimestamp + 86400; // 24 hours after event
                        $canUploadReport = (time() <= $cutoffTimestamp);
                        $existingReport = isset($registeredMapFiles[$wk['id']]) ? $registeredMapFiles[$wk['id']] : null;
                    ?>
                    <button class="btn-secondary" style="width: 100%; justify-content: center; background: #F1F5F9; color: #334155; font-weight: 800; border-radius: 12px; padding: 11px; cursor: default;" disabled>
                        <span class="badge-status <?= $attCls; ?>" style="font-size: 12.5px;">✓ Booking Status: <?= htmlspecialchars($myStatus); ?></span>
                    </button>

                    <!-- Workshop Report Upload Controls (Available until 24h post event) -->
                    <?php if ($canUploadReport): ?>
                        <div style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #CBD5E1;">
                            <?php if ($existingReport && file_exists(__DIR__ . '/../uploads/' . $existingReport)): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; background: #ECFDF5; padding: 8px 12px; border-radius: 10px; border: 1px solid #A7F3D0;">
                                    <span style="font-size: 12px; font-weight: 700; color: #065F46;">📜 Report Uploaded</span>
                                    <a href="../uploads/<?= htmlspecialchars($existingReport); ?>" target="_blank" style="font-size: 12px; font-weight: 800; color: #059669; text-decoration: underline;">View File ↗</a>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 8px;">
                                <input type="hidden" name="action" value="upload_report">
                                <input type="hidden" name="workshop_id" value="<?= $wk['id']; ?>">
                                <label style="font-size: 11.5px; font-weight: 800; color: #334155; display: block;">
                                    📤 <?= ($existingReport ? 'Replace' : 'Upload') ?> Workshop Report / Certificate (PDF/Image)
                                </label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="file" name="report_file" accept=".pdf,.docx,.doc,.png,.jpg,.jpeg" required style="font-size: 11px; padding: 6px; border-radius: 8px; border: 1px solid #CBD5E1; flex: 1; background: white;">
                                    <button type="submit" class="btn-primary" style="font-size: 11.5px; padding: 6px 14px; border-radius: 8px; font-weight: 800; white-space: nowrap; background: #059669; border-color: #059669;">
                                        Upload
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #CBD5E1;">
                            <?php if ($existingReport && file_exists(__DIR__ . '/../uploads/' . $existingReport)): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #F8FAFC; padding: 8px 12px; border-radius: 10px; border: 1px solid #E2E8F0; margin-bottom: 6px;">
                                    <span style="font-size: 12px; font-weight: 700; color: #475569;">📜 Uploaded Report</span>
                                    <a href="../uploads/<?= htmlspecialchars($existingReport); ?>" target="_blank" style="font-size: 12px; font-weight: 800; color: #2563EB; text-decoration: underline;">View File ↗</a>
                                </div>
                            <?php endif; ?>
                            <span style="font-size: 11px; color: #94A3B8; font-weight: 700; display: block;">⌛ Report Upload Option Closed (24 Hours Post-Event Limit Expired)</span>
                        </div>
                    <?php endif; ?>
                <?php elseif ($isPast): ?>
                    <button class="btn-secondary" style="width: 100%; justify-content: center; background: #F8FAFC; color: #94A3B8; font-weight: 700; border-radius: 12px; padding: 11px; cursor: not-allowed;" disabled>
                        ⌛ Workshop Event Completed
                    </button>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="register_workshop">
                        <input type="hidden" name="workshop_id" value="<?= $wk['id']; ?>">
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px; padding: 11px; font-weight: 800; box-shadow: 0 4px 12px rgba(59,130,246,0.25);">
                            ⚡ Register for Workshop Event
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
