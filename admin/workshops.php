<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';
$error = '';

// Ensure attendance_status & report_file columns exist safely
try {
    $pdo->exec("ALTER TABLE workshop_registrations ADD COLUMN attendance_status VARCHAR(50) DEFAULT 'Registered'");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE workshops ADD COLUMN report_file VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {}

// =========================================================================
// EXCEL / CSV EXPORT FOR SPECIFIC WORKSHOP STUDENT ATTENDANCE DATA
// =========================================================================
if (isset($_GET['export_attendance']) && isset($_GET['workshop_id'])) {
    $wkId = intval($_GET['workshop_id']);
    $stmtWk = $pdo->prepare("SELECT * FROM workshops WHERE id = ?");
    $stmtWk->execute([$wkId]);
    $wkInfo = $stmtWk->fetch();

    $wkTitleClean = preg_replace('/[^A-Za-z0-9_]/', '_', $wkInfo['title'] ?: 'Workshop');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=workshop_attendance_' . $wkTitleClean . '_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Workshop Title', 'Guest Speaker', 'Event Date', 'Enrollment No', 'Candidate Full Name', 'Branch', 'CPI', 'Email', 'Registration Timestamp', 'Attendance Status']);

    $qExp = "SELECT wr.*, s.enrollment_no, s.full_name, s.branch, s.cpi, s.email 
             FROM workshop_registrations wr 
             JOIN students s ON wr.student_id = s.id 
             WHERE wr.workshop_id = $wkId 
             ORDER BY s.enrollment_no ASC";

    $enrolled = $pdo->query($qExp)->fetchAll();
    foreach ($enrolled as $row) {
        fputcsv($output, [
            $wkInfo['title'],
            $wkInfo['speaker'],
            $wkInfo['event_date'],
            $row['enrollment_no'],
            $row['full_name'],
            $row['branch'],
            $row['cpi'],
            $row['email'],
            $row['registered_at'],
            $row['attendance_status'] ?: 'Registered'
        ]);
    }
    fclose($output);
    exit;
}

// =========================================================================
// AJAX ENDPOINT FOR VIEWING / FILTERING ENROLLED WORKSHOP STUDENTS
// =========================================================================
if (isset($_GET['fetch_workshop_students']) && isset($_GET['workshop_id'])) {
    $wkId = intval($_GET['workshop_id']);
    $branchFilter = isset($_GET['branch']) ? sanitize($_GET['branch']) : '';
    $statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

    $stmtWk = $pdo->prepare("SELECT * FROM workshops WHERE id = ?");
    $stmtWk->execute([$wkId]);
    $wkInfo = $stmtWk->fetch();

    if (!$wkInfo) {
        echo "<div style='padding: 24px; text-align: center; color: #EF4444;'>Workshop not found.</div>";
        exit;
    }

    // Build filter query for enrolled students
    $qReg = "SELECT wr.*, s.enrollment_no, s.full_name, s.branch, s.cpi, s.email, s.profile_photo 
             FROM workshop_registrations wr 
             JOIN students s ON wr.student_id = s.id 
             WHERE wr.workshop_id = $wkId";

    if ($branchFilter) { $qReg .= " AND s.branch = '{$branchFilter}'"; }
    if ($statusFilter) { $qReg .= " AND wr.attendance_status = '{$statusFilter}'"; }

    $qReg .= " ORDER BY wr.id DESC";
    $enrolledStudents = $pdo->query($qReg)->fetchAll();

    // Fetch all active students for the quick add dropdown
    $allStudents = $pdo->query("SELECT id, enrollment_no, full_name, branch FROM students WHERE is_detained = 0 ORDER BY full_name ASC")->fetchAll();

    ?>
    <!-- Workshop Info Header Card -->
    <div style="background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%); color: white; padding: 20px 24px; border-radius: 16px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #FFFFFF;"><?= htmlspecialchars($wkInfo['title']); ?></h3>
                <p style="font-size: 13px; color: #94A3B8; margin-top: 4px;">
                    🎤 Speaker: <strong style="color: #60A5FA;"><?= htmlspecialchars($wkInfo['speaker']); ?></strong> &nbsp;|&nbsp; 
                    📍 Venue: <strong style="color: #F59E0B;"><?= htmlspecialchars($wkInfo['venue']); ?></strong> &nbsp;|&nbsp; 
                    ⏰ Date: <strong style="color: #10B981;"><?= date('d M Y, h:i A', strtotime($wkInfo['event_date'])); ?></strong>
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <!-- EXPORT WORKSHOP ATTENDANCE EXCEL BUTTON -->
                <a href="workshops.php?export_attendance=1&workshop_id=<?= $wkId; ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 12px; font-size: 13px; font-weight: 800; background: #3B82F6; color: white; border: none; box-shadow: 0 4px 10px rgba(59,130,246,0.25);">
                    📊 Export Attendance CSV
                </a>
                <span style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: #93C5FD; padding: 8px 16px; border-radius: 12px; font-weight: 800; font-size: 14px;">
                    👥 <?= count($enrolledStudents); ?> Enrolled
                </span>
            </div>
        </div>
    </div>

    <!-- Add Candidate & Filter Controls Row -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; align-items: start;">
        
        <!-- Form 1: Add New Candidate to Workshop -->
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 16px; border-radius: 16px;">
            <h4 style="font-size: 14px; font-weight: 800; color: #0F172A; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                ➕ Enroll Student Candidate
            </h4>
            <form method="POST" style="display: flex; gap: 8px;">
                <input type="hidden" name="action" value="add_workshop_registration">
                <input type="hidden" name="workshop_id" value="<?= $wkId; ?>">
                
                <select name="student_id" class="form-control" style="font-size: 12px; padding: 8px 12px; border-radius: 10px; flex: 1;" required>
                    <option value="">Select Candidate from Cohort...</option>
                    <?php foreach ($allStudents as $stOpt): ?>
                        <option value="<?= $stOpt['id']; ?>"><?= htmlspecialchars($stOpt['full_name']); ?> (<?= $stOpt['enrollment_no']; ?> - <?= $stOpt['branch']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary" style="padding: 8px 16px; font-size: 12px; font-weight: 800; border-radius: 10px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(59,130,246,0.2);">
                    + Enroll Student
                </button>
            </form>
        </div>

        <!-- Form 2: Filter Enrolled Roster -->
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 16px; border-radius: 16px;">
            <h4 style="font-size: 14px; font-weight: 800; color: #0F172A; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                🔍 Filter Enrolled Candidates
            </h4>
            <div style="display: flex; gap: 8px;">
                <select id="wkFilterBranch" class="select-pill" onchange="filterWorkshopStudents(<?= $wkId; ?>)" style="font-size: 12px; padding: 8px 12px; border-radius: 10px; flex: 1;">
                    <option value="">All Academic Branches</option>
                    <option value="CSE" <?= ($branchFilter==='CSE')?'selected':''; ?>>CSE Branch</option>
                    <option value="IT" <?= ($branchFilter==='IT')?'selected':''; ?>>IT Branch</option>
                    <option value="ECE" <?= ($branchFilter==='ECE')?'selected':''; ?>>ECE Branch</option>
                    <option value="MECH" <?= ($branchFilter==='MECH')?'selected':''; ?>>MECH Branch</option>
                    <option value="CIVIL" <?= ($branchFilter==='CIVIL')?'selected':''; ?>>CIVIL Branch</option>
                </select>

                <select id="wkFilterStatus" class="select-pill" onchange="filterWorkshopStudents(<?= $wkId; ?>)" style="font-size: 12px; padding: 8px 12px; border-radius: 10px; flex: 1;">
                    <option value="">All Attendance Statuses</option>
                    <option value="Registered" <?= ($statusFilter==='Registered')?'selected':''; ?>>🟡 Registered</option>
                    <option value="Attended" <?= ($statusFilter==='Attended')?'selected':''; ?>>🟢 Attended</option>
                    <option value="Absent" <?= ($statusFilter==='Absent')?'selected':''; ?>>🔴 Absent</option>
                </select>

                <button onclick="filterWorkshopStudents(<?= $wkId; ?>, true)" class="btn-secondary" style="padding: 8px 12px; font-size: 12px; border-radius: 10px;">Reset</button>
            </div>
        </div>
    </div>

    <!-- Enrolled Students Table Roster -->
    <?php if (empty($enrolledStudents)): ?>
        <div style="text-align: center; padding: 40px 20px; background: #F8FAFC; border-radius: 16px; border: 1px dashed #CBD5E1;">
            <div style="font-size: 32px; margin-bottom: 8px;">🎓</div>
            <h4 style="font-size: 15px; font-weight: 800; color: #0F172A;">No Enrolled Candidates Match Filter</h4>
            <p style="color: #64748B; font-size: 13px;">No student candidates enrolled or matching the selected branch/status filter.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="custom-table" style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">
                <thead>
                    <tr style="background: #F8FAFC;">
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #64748B;">Student Candidate</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #64748B;">Branch & CPI</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #64748B;">Registration Timestamp</th>
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 800; color: #64748B;">Attendance Status</th>
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 800; color: #64748B; text-align: right; white-space: nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrolledStudents as $es): 
                        $photoSrc = ($es['profile_photo'] && $es['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $es['profile_photo'])) 
                            ? '../uploads/' . $es['profile_photo'] 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($es['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';
                        
                        $attStatus = $es['attendance_status'] ?: 'Registered';
                        $attClass = ($attStatus === 'Attended') ? 'placed' : (($attStatus === 'Absent') ? 'unplaced' : 'in-process');
                    ?>
                        <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px;">
                            <td style="padding: 14px 18px; border-top-left-radius: 14px; border-bottom-left-radius: 14px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?= $photoSrc; ?>" alt="Photo" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #3B82F6;">
                                    <div>
                                        <strong style="font-size: 14px; color: #0F172A;"><?= htmlspecialchars($es['full_name']); ?></strong><br>
                                        <code style="font-size: 11px; color: #3B82F6; font-weight: 700;"><?= htmlspecialchars($es['enrollment_no']); ?></code>
                                    </div>
                                </div>
                            </td>

                            <td style="padding: 14px 18px;">
                                <span class="branch-badge" style="border-radius: 8px;"><?= htmlspecialchars($es['branch']); ?></span>
                                <span class="cpi-pill high" style="margin-left: 6px; font-weight: 800;">★ <?= number_format($es['cpi'], 2); ?></span>
                            </td>

                            <td style="padding: 14px 18px;">
                                <span style="font-size: 12px; color: #475569; font-weight: 600;">
                                    ⏰ <?= date('d M Y, h:i A', strtotime($es['registered_at'])); ?>
                                </span>
                            </td>

                            <!-- EDIT ATTENDANCE STATUS FORM -->
                            <td style="padding: 14px 18px; white-space: nowrap;">
                                <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="action" value="update_attendance_status">
                                    <input type="hidden" name="registration_id" value="<?= $es['id']; ?>">
                                    <input type="hidden" name="workshop_id" value="<?= $wkId; ?>">
                                    <select name="attendance_status" class="select-pill" style="font-size: 12px; padding: 7px 12px; font-weight: 700; border-radius: 10px; border: 1px solid #CBD5E1;">
                                        <option value="Registered" <?= ($attStatus==='Registered')?'selected':''; ?>>🟡 Registered</option>
                                        <option value="Attended" <?= ($attStatus==='Attended')?'selected':''; ?>>🟢 Attended</option>
                                        <option value="Absent" <?= ($attStatus==='Absent')?'selected':''; ?>>🔴 Absent</option>
                                    </select>
                                    <button type="submit" class="btn-primary" style="padding: 7px 14px; font-size: 12px; border-radius: 10px; font-weight: 800; border: none; height: 34px;">Save</button>
                                </form>
                            </td>

                            <!-- DELETE / UNENROLL CANDIDATE -->
                            <td style="padding: 14px 20px; text-align: right; border-top-right-radius: 14px; border-bottom-right-radius: 14px; white-space: nowrap;">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Unenroll student candidate from this workshop?');">
                                    <input type="hidden" name="action" value="delete_workshop_registration">
                                    <input type="hidden" name="registration_id" value="<?= $es['id']; ?>">
                                    <input type="hidden" name="workshop_id" value="<?= $wkId; ?>">
                                    <button type="submit" class="btn-action-icon delete" title="Unenroll Candidate">
                                        🗑️ Unenroll
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    exit;
}

// =========================================================================
// POST ACTIONS HANDLER (CREATE WORKSHOP, ENROLL, UPDATE STATUS, UNENROLL)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);
    
    if ($action === 'create_workshop') {
        $title = sanitize($_POST['title']);
        $speaker = sanitize($_POST['speaker']);
        $eventDate = sanitize($_POST['event_date']);
        $venue = sanitize($_POST['venue']);
        $capacity = intval($_POST['capacity']);
        $desc = sanitize($_POST['description']);

        $reportFile = null;
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['xlsx', 'xls', 'csv', 'pdf', 'docx', 'doc'];
            if (in_array($ext, $allowed)) {
                $newReportName = 'workshop_report_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['report_file']['tmp_name'], __DIR__ . '/../uploads/' . $newReportName)) {
                    $reportFile = $newReportName;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO workshops (title, speaker, event_date, venue, capacity, description, report_file) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $speaker, $eventDate, $venue, $capacity, $desc, $reportFile]);
        setFlashMessage("Workshop / Seminar scheduled successfully!", "success");
    } elseif ($action === 'upload_workshop_report') {
        $wkId = intval($_POST['workshop_id']);
        $stmtWk = $pdo->prepare("SELECT title FROM workshops WHERE id = ?");
        $stmtWk->execute([$wkId]);
        $wkTitle = $stmtWk->fetchColumn() ?: 'Workshop';

        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['xlsx', 'xls', 'csv', 'pdf', 'docx', 'doc'];

            if (in_array($ext, $allowed)) {
                $newReportName = 'workshop_report_' . $wkId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['report_file']['tmp_name'], __DIR__ . '/../uploads/' . $newReportName)) {
                    $stmtUp = $pdo->prepare("UPDATE workshops SET report_file = ? WHERE id = ?");
                    $stmtUp->execute([$newReportName, $wkId]);
                    setFlashMessage("📊 Excel Report uploaded successfully for workshop: " . htmlspecialchars($wkTitle) . "!", "success");
                }
            } else {
                setFlashMessage("Invalid file format. Please upload Excel (.xlsx, .csv) or PDF document.", "danger");
            }
        }
    } elseif ($action === 'add_workshop_registration') {
        $wkId = intval($_POST['workshop_id']);
        $stId = intval($_POST['student_id']);

        // Check if candidate is already enrolled
        $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM workshop_registrations WHERE workshop_id = ? AND student_id = ?");
        $stmtChk->execute([$wkId, $stId]);
        if ($stmtChk->fetchColumn() > 0) {
            setFlashMessage("Student candidate is already enrolled in this workshop!", "danger");
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO workshop_registrations (workshop_id, student_id, attendance_status) VALUES (?, ?, 'Registered')");
            $stmtIns->execute([$wkId, $stId]);
            setFlashMessage("Student candidate enrolled into workshop successfully!", "success");
        }
    } elseif ($action === 'update_attendance_status') {
        $regId = intval($_POST['registration_id']);
        $attStatus = sanitize($_POST['attendance_status']);

        $stmtUp = $pdo->prepare("UPDATE workshop_registrations SET attendance_status = ? WHERE id = ?");
        $stmtUp->execute([$attStatus, $regId]);
        setFlashMessage("Candidate workshop attendance status updated to: $attStatus!", "success");
    } elseif ($action === 'delete_workshop_registration') {
        $regId = intval($_POST['registration_id']);

        $stmtDel = $pdo->prepare("DELETE FROM workshop_registrations WHERE id = ?");
        $stmtDel->execute([$regId]);
        setFlashMessage("Student candidate unenrolled from workshop.", "warning");
    }

    $redirectUrl = $_SERVER['PHP_SELF'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    header("Location: " . $redirectUrl);
    exit;
}

// Excel / CSV Export Engine (General Overview)
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=workshops_and_seminars_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Workshop Title', 'Guest Speaker', 'Event Date & Time', 'Venue', 'Capacity Seats', 'Total Registrations', 'Status']);

    $expWorkshops = $pdo->query("SELECT * FROM workshops ORDER BY id DESC")->fetchAll();
    foreach ($expWorkshops as $wkRow) {
        $regCount = $pdo->query("SELECT COUNT(*) FROM workshop_registrations WHERE workshop_id = {$wkRow['id']}")->fetchColumn();
        fputcsv($output, [
            $wkRow['title'],
            $wkRow['speaker'],
            $wkRow['event_date'],
            $wkRow['venue'],
            $wkRow['capacity'],
            $regCount,
            $wkRow['status']
        ]);
    }
    fclose($output);
    exit;
}

$workshops = $pdo->query("SELECT * FROM workshops ORDER BY id DESC")->fetchAll();

$pageTitle = 'Workshop & Seminar Management';
$currentPage = 'workshops';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Action Row -->
<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Workshops, Seminars & Guest Lectures</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Organize skill-enhancement events, guest speaker sessions, and manage enrolled student rosters.</p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <a href="workshops.php?export_csv=1" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 700; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
            📊 Export Excel / CSV
        </a>
        <button class="btn-primary" onclick="openModal('createWorkshopModal')" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Schedule New Workshop
        </button>
    </div>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 16px; display: inline-block; font-size: 14px; padding: 10px 16px;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge-status unplaced" style="margin-bottom: 16px; display: inline-block; font-size: 14px; padding: 10px 16px;"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Workshop Events Table -->
<div class="table-card" style="padding: 24px; border-radius: 20px;">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Event Topic & Title</th>
                    <th>Guest Speaker</th>
                    <th>Date & Time</th>
                    <th>Venue / Location</th>
                    <th>Registrations Roster</th>
                    <th>Status</th>
                    <th style="text-align: right;">Enrolled Candidates Control</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($workshops as $wk): 
                    $regCount = $pdo->query("SELECT COUNT(*) FROM workshop_registrations WHERE workshop_id = {$wk['id']}")->fetchColumn();
                ?>
                    <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                        <td style="border-top-left-radius: 16px; border-bottom-left-radius: 16px; padding: 14px 16px;">
                            <strong style="font-size: 15px; color: #0F172A;"><?= htmlspecialchars($wk['title']); ?></strong>
                        </td>
                        <td style="padding: 14px 16px;"><strong style="color: #334155;">🎤 <?= htmlspecialchars($wk['speaker']); ?></strong></td>
                        <td style="padding: 14px 16px;"><strong><?= date('d M Y, h:i A', strtotime($wk['event_date'])); ?></strong></td>
                        <td style="padding: 14px 16px;"><span class="branch-badge" style="border-radius: 8px; padding: 6px 12px;">📍 <?= htmlspecialchars($wk['venue']); ?></span></td>
                        <td style="padding: 14px 16px;"><strong style="color: #10B981; font-size: 15px; font-weight: 800;"><?= $regCount; ?> / <?= $wk['capacity']; ?></strong> Seats</td>
                        <td style="padding: 14px 16px;">
                            <span class="badge-status in-process" style="border-radius: 10px; font-weight: 800;"><?= htmlspecialchars($wk['status']); ?></span>
                        </td>
                        <td style="text-align: right; border-top-right-radius: 16px; border-bottom-right-radius: 16px; padding: 14px 16px; white-space: nowrap;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                <?php if (!empty($wk['report_file'])): ?>
                                    <a href="../uploads/<?= htmlspecialchars($wk['report_file']); ?>" target="_blank" class="btn-action-icon" title="View / Download Uploaded Workshop Excel Report" style="padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 12px; background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        📊 View Report
                                    </a>
                                    <button type="button" class="btn-action-icon" onclick="openWorkshopReportModal(<?= $wk['id']; ?>, '<?= htmlspecialchars(addslashes($wk['title']), ENT_QUOTES); ?>')" title="Re-upload or Update Excel Report File" style="padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 12px; background: #FEF3C7; color: #D97706; border: 1px solid #FCD34D; cursor: pointer;">
                                        📤 Update Report
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn-action-icon" onclick="openWorkshopReportModal(<?= $wk['id']; ?>, '<?= htmlspecialchars(addslashes($wk['title']), ENT_QUOTES); ?>')" title="Upload Excel Report Document for this Workshop" style="padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 12px; background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; cursor: pointer; box-shadow: 0 2px 6px rgba(16,185,129,0.15);">
                                        📤 Upload Excel Report
                                    </button>
                                <?php endif; ?>

                                <button class="btn-action-icon dossier" style="padding: 8px 16px; font-weight: 800; border-radius: 12px; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; cursor: pointer; box-shadow: 0 2px 6px rgba(37,99,235,0.15);" onclick="viewWorkshopStudents(<?= $wk['id']; ?>, '<?= htmlspecialchars(addslashes($wk['title'])); ?>')">
                                    👥 Enrolled Students (<?= $regCount; ?>)
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Schedule Workshop -->
<div class="modal-overlay" id="createWorkshopModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Schedule Workshop / Seminar</h3>
            <button class="close-modal" onclick="closeModal('createWorkshopModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_workshop">
            <div class="form-group">
                <label>Event Topic Title *</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Generative AI & LLM Deployment Masterclass" required>
            </div>
            <div class="form-group">
                <label>Guest Speaker / Keynote Expert *</label>
                <input type="text" name="speaker" class="form-control" placeholder="e.g. Dr. Ramesh Kumar (Google AI)" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Event Date & Time *</label>
                    <input type="datetime-local" name="event_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Max Capacity Seats</label>
                    <input type="number" name="capacity" class="form-control" value="200" required>
                </div>
            </div>
            <div class="form-group">
                <label>Venue Auditorium / Hall</label>
                <input type="text" name="venue" class="form-control" value="Main Campus Auditorium" required>
            </div>
            <div class="form-group">
                <label>Event Synopsis / Details</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Enter workshop agenda..."></textarea>
            </div>
            <div class="form-group">
                <label>Optional Workshop Excel Report / File (.xlsx, .csv, .pdf)</label>
                <input type="file" name="report_file" accept=".xlsx,.xls,.csv,.pdf,.docx,.doc" class="form-control" style="background: #F8FAFC;">
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px;">Publish Event Schedule</button>
        </form>
    </div>
</div>

<!-- Modal 2: Enrolled Students View/Add/Edit/Delete & Filter Modal Pop-up -->
<div class="modal-overlay" id="workshopStudentsModal">
    <div class="modal-box full-size" style="max-width: 980px !important; border-radius: 24px !important;">
        <div class="modal-header" style="border-bottom: 1px solid #E2E8F0; padding-bottom: 16px;">
            <h3 id="wkModalTitle" style="font-size: 18px; font-weight: 800; color: #0F172A;">Enrolled Candidates Roster</h3>
            <button class="close-modal" onclick="closeModal('workshopStudentsModal')">&times;</button>
        </div>
        <div id="workshopStudentsContainer" class="dossier-scroll-area" style="padding-top: 16px;">
            <p style="color: var(--text-muted); text-align: center;">Loading enrolled students...</p>
        </div>
    </div>
</div>

<!-- Modal 3: Upload Workshop Excel Report Flash Modal -->
<div class="modal-overlay" id="uploadWorkshopReportModal">
    <div class="modal-box" style="max-width: 540px; border-radius: 24px; padding: 28px;">
        <div class="modal-header" style="border-bottom: 1px solid #E2E8F0; padding-bottom: 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 14px; background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    📊
                </div>
                <div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0;">Upload Workshop Excel Report</h3>
                    <span style="font-size: 12px; color: #64748B;">Upload summary or attendance report for this event</span>
                </div>
            </div>
            <button class="close-modal" onclick="closeModal('uploadWorkshopReportModal')">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_workshop_report">
            <input type="hidden" name="workshop_id" id="reportWorkshopId">

            <div class="form-group" style="background: #F8FAFC; padding: 14px; border-radius: 14px; border: 1px solid #E2E8F0; margin-bottom: 18px;">
                <label style="font-size: 11px; font-weight: 800; color: #059669; text-transform: uppercase; display: block; margin-bottom: 2px;">Selected Workshop Event</label>
                <input type="text" id="reportWorkshopTitle" readonly style="border: none; background: transparent; font-weight: 800; font-size: 15px; color: #0F172A; width: 100%; outline: none;">
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 8px; display: block;">Select Excel / CSV / PDF Report File *</label>
                <div style="border: 2px dashed #10B981; background: #ECFDF5; padding: 24px; border-radius: 16px; text-align: center; position: relative;">
                    <div style="font-size: 32px; margin-bottom: 6px;">📊</div>
                    <div id="reportFileNameDisplay" style="font-size: 13px; font-weight: 800; color: #065F46;">Choose file or drag & drop here</div>
                    <div style="font-size: 11.5px; color: #047857; margin-top: 4px;">Supports Excel (.xlsx, .csv), PDF, DOCX (Max 15MB)</div>
                    <input type="file" name="report_file" id="reportFileInput" accept=".xlsx,.xls,.csv,.pdf,.docx,.doc" onchange="updateReportFileName(this)" required style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px; padding: 12px; font-weight: 800; background: #059669; border-color: #059669; box-shadow: 0 4px 12px rgba(5,150,105,0.25);">
                📤 Save & Upload Report
            </button>
        </form>
    </div>
</div>

<script>
let currentWkId = 0;

function openWorkshopReportModal(wkId, wkTitle) {
    const modal = document.getElementById('uploadWorkshopReportModal');
    if (!modal) return;
    document.getElementById('reportWorkshopId').value = wkId;
    document.getElementById('reportWorkshopTitle').value = wkTitle;
    modal.classList.add('active');
}

function updateReportFileName(input) {
    const display = document.getElementById('reportFileNameDisplay');
    if (!display) return;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        display.innerHTML = '✅ Selected: <strong>' + file.name + '</strong> (' + sizeMB + ' MB)';
        display.style.color = '#065F46';
    } else {
        display.innerHTML = 'Choose file or drag & drop here';
        display.style.color = '#065F46';
    }
}

function viewWorkshopStudents(wkId, wkTitle) {
    currentWkId = wkId;
    document.getElementById('wkModalTitle').innerText = 'Enrolled Student Candidates — ' + wkTitle;
    openModal('workshopStudentsModal');
    loadWorkshopStudents(wkId, '', '');
}

function filterWorkshopStudents(wkId, reset = false) {
    let branch = '';
    let status = '';
    if (!reset) {
        const bEl = document.getElementById('wkFilterBranch');
        const sEl = document.getElementById('wkFilterStatus');
        if (bEl) branch = bEl.value;
        if (sEl) status = sEl.value;
    }
    loadWorkshopStudents(wkId, branch, status);
}

function loadWorkshopStudents(wkId, branch, status) {
    const container = document.getElementById('workshopStudentsContainer');
    container.innerHTML = "<p style='color: var(--text-muted); text-align: center; padding: 30px;'>Loading enrolled students...</p>";
    
    let url = 'workshops.php?fetch_workshop_students=1&workshop_id=' + wkId;
    if (branch) url += '&branch=' + encodeURIComponent(branch);
    if (status) url += '&status=' + encodeURIComponent(status);

    fetch(url)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
