<?php
require_once __DIR__ . '/../config/db.php';
requireStudent();

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];
$msg = '';
$error = '';

// Fetch Student Info for Autofill
$stmtSt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmtSt->execute([$studentId]);
$student = $stmtSt->fetch();

// Submit Internship Form Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = sanitize($_POST['company_name']);
    $title = sanitize($_POST['title']);
    $stipend = sanitize($_POST['stipend']);
    $startDate = sanitize($_POST['start_date']);
    $endDate = sanitize($_POST['end_date']);

    // Certificate File Upload & Validation
    $certFile = 'certificate_sample.pdf';
    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['certificate_file']['tmp_name'];
        $fileName = $_FILES['certificate_file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        if (in_array($fileExt, $allowedExts)) {
            $certName = 'cert_' . $_SESSION['enrollment_no'] . '_' . time() . '.' . $fileExt;
            move_uploaded_file($fileTmp, __DIR__ . '/../uploads/' . $certName);
            $certFile = $certName;
        } else {
            $error = "Invalid certificate file format. Please upload PDF, JPG, or PNG.";
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO internships (student_id, company_name, title, stipend, start_date, end_date, certificate_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Completed')");
        $stmt->execute([$studentId, $companyName, $title, $stipend, $startDate, $endDate, $certFile]);
        $msg = "Internship record and completion certificate submitted successfully!";
    }
}

// Fetch Student's Submitted Internships
$stmtInt = $pdo->prepare("SELECT * FROM internships WHERE student_id = ? ORDER BY id DESC");
$stmtInt->execute([$studentId]);
$myInternships = $stmtInt->fetchAll();

$pageTitle = 'My Internships';
$currentPage = 'internships';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 800;">My Industrial Internships & Certifications</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Register your completed or ongoing industrial internships and upload completion certificates.</p>
    </div>
    <button class="btn-primary" onclick="openModal('registerInternshipModal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Register New Internship
    </button>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 16px; display: inline-block; font-size: 14px; padding: 10px 16px;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge-status unplaced" style="margin-bottom: 16px; display: inline-block; font-size: 14px; padding: 10px 16px;"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<div class="table-card">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Company / Enterprise</th>
                <th>Role Title</th>
                <th>Monthly Stipend</th>
                <th>Duration</th>
                <th>Certificate</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($myInternships)): ?>
                <?php foreach ($myInternships as $it): ?>
                    <tr>
                        <td><strong>🏢 <?= htmlspecialchars($it['company_name']); ?></strong></td>
                        <td><?= htmlspecialchars($it['title']); ?></td>
                        <td><strong style="color: #10B981;"><?= htmlspecialchars($it['stipend']); ?></strong></td>
                        <td><?= date('M Y', strtotime($it['start_date'])); ?> - <?= date('M Y', strtotime($it['end_date'])); ?></td>
                        <td>
                            <a href="../uploads/<?= htmlspecialchars($it['certificate_file']); ?>" target="_blank" style="color: #3B82F6; font-weight: 700; text-decoration: none;">
                                📄 View Certificate
                            </a>
                        </td>
                        <td><span class="badge-status placed"><?= htmlspecialchars($it['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="color: var(--text-muted); text-align: center;">No internship records on file. Click 'Register New Internship' above to submit.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Student Internship Registration Form with Autofill -->
<div class="modal-overlay" id="registerInternshipModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Register Industrial Internship</h3>
            <button class="close-modal" onclick="closeModal('registerInternshipModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <!-- Autofilled Student Info -->
            <div style="background: #F8FAFC; border: 1px solid var(--border-color); padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;">
                <p style="font-size: 12px; color: var(--text-muted);">Autofilled Candidate Info:</p>
                <strong style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($student['full_name']); ?></strong> 
                <span style="font-size: 12px; color: #3B82F6;">(<?= htmlspecialchars($student['enrollment_no']); ?> - <?= htmlspecialchars($student['branch']); ?>)</span>
            </div>

            <div class="form-group">
                <label>Company / Enterprise Name *</label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. Google / Microsoft / TCS" required>
            </div>
            <div class="form-group">
                <label>Internship Role Title *</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Full Stack Developer Intern" required>
            </div>
            <div class="form-group">
                <label>Monthly Stipend</label>
                <input type="text" name="stipend" class="form-control" value="₹20,000 / month" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
            </div>

            <div class="form-group" style="background: #EFF6FF; border: 1px dashed #3B82F6; padding: 16px; border-radius: 10px; text-align: center;">
                <label style="font-size: 13px; font-weight: 700; color: #1D4ED8; display: block; margin-bottom: 6px;">Upload Completion Certificate (PDF / JPG / PNG)</label>
                <input type="file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control" required>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Submit Internship Record</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
