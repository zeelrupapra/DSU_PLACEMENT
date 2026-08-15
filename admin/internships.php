<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';
$error = '';

// Handle Form Posts for Add, Edit, and Delete Internship Records
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);

    if ($action === 'add_internship') {
        $studentId = intval($_POST['student_id']);
        $companyName = sanitize($_POST['company_name']);
        $title = sanitize($_POST['title']);
        $stipend = sanitize($_POST['stipend']);
        $startDate = sanitize($_POST['start_date']);
        $endDate = sanitize($_POST['end_date']);
        $status = sanitize($_POST['status']);
        $certFile = 'default_cert.pdf';

        if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['certificate_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $newFileName = 'intern_cert_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetDir = __DIR__ . '/../uploads/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                move_uploaded_file($_FILES['certificate_file']['tmp_name'], $targetDir . $newFileName);
                $certFile = $newFileName;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO internships (student_id, company_name, title, stipend, start_date, end_date, certificate_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$studentId, $companyName, $title, $stipend, $startDate, $endDate, $certFile, $status]);
        $msg = "New industrial internship record added successfully!";
    } elseif ($action === 'edit_internship') {
        $internshipId = intval($_POST['internship_id']);
        $companyName = sanitize($_POST['company_name']);
        $title = sanitize($_POST['title']);
        $stipend = sanitize($_POST['stipend']);
        $startDate = sanitize($_POST['start_date']);
        $endDate = sanitize($_POST['end_date']);
        $status = sanitize($_POST['status']);

        // Fetch existing record
        $stmtGet = $pdo->prepare("SELECT certificate_file FROM internships WHERE id = ?");
        $stmtGet->execute([$internshipId]);
        $currRecord = $stmtGet->fetch();
        $certFile = $currRecord ? $currRecord['certificate_file'] : 'default_cert.pdf';

        if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['certificate_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $newFileName = 'intern_cert_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetDir = __DIR__ . '/../uploads/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                move_uploaded_file($_FILES['certificate_file']['tmp_name'], $targetDir . $newFileName);
                $certFile = $newFileName;
            }
        }

        $stmt = $pdo->prepare("UPDATE internships SET company_name = ?, title = ?, stipend = ?, start_date = ?, end_date = ?, certificate_file = ?, status = ? WHERE id = ?");
        $stmt->execute([$companyName, $title, $stipend, $startDate, $endDate, $certFile, $status, $internshipId]);
        $msg = "Student internship record updated successfully!";
    } elseif ($action === 'delete_internship') {
        $internshipId = intval($_POST['internship_id']);
        $stmt = $pdo->prepare("DELETE FROM internships WHERE id = ?");
        $stmt->execute([$internshipId]);
        $msg = "Internship record removed successfully.";
    }
}

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

// Excel / CSV Export Engine
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=industrial_internships_report_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Enrollment No', 'Candidate Name', 'Branch', 'Batch Year', 'Company / Industry', 'Internship Role', 'Monthly Stipend', 'Start Date', 'End Date', 'Status']);

    $qExp = "SELECT i.*, s.enrollment_no, s.full_name, s.branch, s.batch_year FROM internships i JOIN students s ON i.student_id = s.id WHERE 1=1";
    if ($branchFilter) { $qExp .= " AND s.branch = '{$branchFilter}'"; }
    if ($yearFilter > 0) { $qExp .= " AND (s.batch_year = {$yearFilter} OR YEAR(i.start_date) = {$yearFilter})"; }
    $qExp .= " ORDER BY i.id DESC";

    $expList = $pdo->query($qExp)->fetchAll();
    foreach ($expList as $itRow) {
        fputcsv($output, [
            $itRow['enrollment_no'],
            $itRow['full_name'],
            $itRow['branch'],
            $itRow['batch_year'],
            $itRow['company_name'],
            $itRow['title'],
            $itRow['stipend'],
            $itRow['start_date'],
            $itRow['end_date'],
            $itRow['status']
        ]);
    }
    fclose($output);
    exit;
}

$q = "SELECT i.*, s.enrollment_no, s.full_name, s.branch, s.batch_year FROM internships i JOIN students s ON i.student_id = s.id WHERE 1=1";
$params = [];

if ($branchFilter) {
    $q .= " AND s.branch = ?";
    $params[] = $branchFilter;
}

if ($yearFilter > 0) {
    $q .= " AND (s.batch_year = ? OR YEAR(i.start_date) = ?)";
    $params[] = $yearFilter;
    $params[] = $yearFilter;
}

$q .= " ORDER BY i.id DESC";
$stmt = $pdo->prepare($q);
$stmt->execute($params);
$internships = $stmt->fetchAll();

// Fetch active student candidates list for Add Internship dropdown
$allStudents = $pdo->query("SELECT id, enrollment_no, full_name, branch FROM students WHERE is_detained = 0 ORDER BY full_name ASC")->fetchAll();

$pageTitle = 'Industrial Internships';
$currentPage = 'internships';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Action Row -->
<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Student Industrial Internships Roster</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Manage student internship records, stipend packages, completion certificates, and status controls.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <a href="internships.php?export_csv=1&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 700; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
            📊 Export Excel / CSV
        </a>
        <button class="btn-primary" onclick="openModal('addInternshipModal')" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);">
            + Record Student Internship
        </button>
    </div>
</div>

<?php if ($msg): ?>
    <div class="badge-status placed" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;">
        <?= htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<!-- Filter Bar with Branch & Year-Wise Filters -->
<div class="table-card" style="padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; background: white; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03); flex-wrap: wrap; gap: 14px;">
    <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <!-- Year-Wise Filter -->
        <select name="year" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px;">
            <option value="2026" <?= ($yearFilter===2026)?'selected':''; ?>>📅 2026 Academic Year (Current Year)</option>
            <option value="2025" <?= ($yearFilter===2025)?'selected':''; ?>>📅 2025 Academic Year</option>
            <option value="2024" <?= ($yearFilter===2024)?'selected':''; ?>>📅 2024 Academic Year</option>
            <option value="all" <?= ($yearFilter===0)?'selected':''; ?>>All Academic Years</option>
        </select>

        <!-- Branch Filter -->
        <select name="branch" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px;">
            <option value="">All Academic Branches</option>
            <option value="CSE" <?= ($branchFilter==='CSE')?'selected':''; ?>>CSE Branch</option>
            <option value="IT" <?= ($branchFilter==='IT')?'selected':''; ?>>IT Branch</option>
            <option value="ECE" <?= ($branchFilter==='ECE')?'selected':''; ?>>ECE Branch</option>
            <option value="MECH" <?= ($branchFilter==='MECH')?'selected':''; ?>>MECH Branch</option>
            <option value="CIVIL" <?= ($branchFilter==='CIVIL')?'selected':''; ?>>CIVIL Branch</option>
        </select>

        <a href="internships.php" class="btn-secondary" style="padding: 9px 16px; font-size: 13px; border-radius: 12px;">Reset Filters</a>
    </form>
    <span class="kpi-badge info" style="font-size: 12px; font-weight: 800;">Showing <?= count($internships); ?> Internships</span>
</div>

<div class="table-card" style="padding: 24px; border-radius: 20px;">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Student Candidate</th>
                    <th>Company / Industry</th>
                    <th>Internship Role</th>
                    <th>Monthly Stipend</th>
                    <th>Duration</th>
                    <th>Certificate</th>
                    <th>Status</th>
                    <th style="text-align: right; min-width: 140px;">Actions & Options</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($internships)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">No student internship records found for selected filters. Click <strong>+ Record Student Internship</strong> above to add one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($internships as $it): 
                        $jsonString = htmlspecialchars(json_encode($it), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                            <td style="border-top-left-radius: 16px; border-bottom-left-radius: 16px; padding: 14px 16px;">
                                <strong style="font-size: 14px; color: #0F172A;"><?= htmlspecialchars($it['full_name']); ?></strong><br>
                                <code style="font-size: 11px; color: #3B82F6; font-weight: 700;"><?= htmlspecialchars($it['enrollment_no']); ?> (<?= htmlspecialchars($it['branch']); ?> - <?= $it['batch_year']; ?>)</code>
                            </td>
                            <td style="padding: 14px 16px;"><strong>🏢 <?= htmlspecialchars($it['company_name']); ?></strong></td>
                            <td style="padding: 14px 16px;"><span class="branch-badge" style="border-radius: 8px; padding: 4px 10px;"><?= htmlspecialchars($it['title']); ?></span></td>
                            <td style="padding: 14px 16px;"><strong style="color: #10B981; font-size: 15px; font-weight: 800;"><?= htmlspecialchars($it['stipend']); ?></strong></td>
                            <td style="padding: 14px 16px;"><strong><?= date('M Y', strtotime($it['start_date'])); ?> - <?= date('M Y', strtotime($it['end_date'])); ?></strong></td>
                            <td style="padding: 14px 16px;">
                                <?php if ($it['certificate_file'] && file_exists(__DIR__ . '/../uploads/' . $it['certificate_file'])): ?>
                                    <a href="../uploads/<?= htmlspecialchars($it['certificate_file']); ?>" target="_blank" style="color: #3B82F6; font-weight: 700; text-decoration: none;">
                                        📄 View Certificate ↗
                                    </a>
                                <?php else: ?>
                                    <span style="color: #94A3B8; font-size: 12px;">No Certificate</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 16px;">
                                <span class="badge-status placed" style="font-weight: 800;"><?= htmlspecialchars($it['status']); ?></span>
                            </td>
                            <td style="text-align: right; border-top-right-radius: 16px; border-bottom-right-radius: 16px; padding: 14px 16px; white-space: nowrap;">
                                <div class="action-btn-group">
                                    <button class="btn-action-icon edit" onclick="openEditInternshipModal(<?= $jsonString; ?>)" title="Edit Internship Details" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; cursor: pointer;">
                                        ✏️ Edit
                                    </button>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this internship record?');">
                                        <input type="hidden" name="action" value="delete_internship">
                                        <input type="hidden" name="internship_id" value="<?= $it['id']; ?>">
                                        <button type="submit" class="btn-action-icon delete" title="Delete Internship" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; cursor: pointer;">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Add Student Internship Record -->
<div class="modal-overlay" id="addInternshipModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Record Student Industrial Internship</h3>
            <button class="close-modal" onclick="closeModal('addInternshipModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_internship">
            
            <div class="form-group">
                <label>Select Student Candidate *</label>
                <select name="student_id" class="form-control" required style="border-radius: 10px;">
                    <option value="">-- Choose Candidate --</option>
                    <?php foreach ($allStudents as $cand): ?>
                        <option value="<?= $cand['id']; ?>">
                            <?= htmlspecialchars($cand['full_name']); ?> (<?= htmlspecialchars($cand['enrollment_no']); ?> - <?= htmlspecialchars($cand['branch']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Company / Organization Name *</label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. Google India / TCS / AWS" required style="border-radius: 10px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Internship Role Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Cloud & Backend Intern" required style="border-radius: 10px;">
                </div>
                <div class="form-group">
                    <label>Monthly Stipend Offered *</label>
                    <input type="text" name="stipend" class="form-control" placeholder="e.g. ₹45,000 / PM" required style="border-radius: 10px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" class="form-control" required style="border-radius: 10px;">
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" class="form-control" required style="border-radius: 10px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Internship Status *</label>
                    <select name="status" class="form-control" required style="border-radius: 10px;">
                        <option value="Completed">Completed</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Verified">Verified</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Certificate Upload (PDF / Image)</label>
                    <input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="border-radius: 10px;">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 12px; border-radius: 12px;">Save Internship Record</button>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Student Internship Record -->
<div class="modal-overlay" id="editInternshipModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Edit Student Internship Details</h3>
            <button class="close-modal" onclick="closeModal('editInternshipModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_internship">
            <input type="hidden" name="internship_id" id="edit_internship_id">
            
            <div class="form-group">
                <label>Student Candidate</label>
                <input type="text" id="edit_student_name" class="form-control" readonly style="background: #F8FAFC; color: #0F172A; font-weight: 700; border-radius: 10px;">
            </div>

            <div class="form-group">
                <label>Company / Organization Name *</label>
                <input type="text" name="company_name" id="edit_company_name" class="form-control" required style="border-radius: 10px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Internship Role Title *</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required style="border-radius: 10px;">
                </div>
                <div class="form-group">
                    <label>Monthly Stipend Offered *</label>
                    <input type="text" name="stipend" id="edit_stipend" class="form-control" required style="border-radius: 10px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" id="edit_start_date" class="form-control" required style="border-radius: 10px;">
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" id="edit_end_date" class="form-control" required style="border-radius: 10px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Internship Status *</label>
                    <select name="status" id="edit_status" class="form-control" required style="border-radius: 10px;">
                        <option value="Completed">Completed</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Verified">Verified</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Update Certificate File (Optional)</label>
                    <input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="border-radius: 10px;">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 12px; border-radius: 12px;">Update Internship Record</button>
        </form>
    </div>
</div>

<script>
function openEditInternshipModal(data) {
    document.getElementById('edit_internship_id').value = data.id;
    document.getElementById('edit_student_name').value = data.full_name + ' (' + data.enrollment_no + ' - ' + data.branch + ')';
    document.getElementById('edit_company_name').value = data.company_name;
    document.getElementById('edit_title').value = data.title;
    document.getElementById('edit_stipend').value = data.stipend;
    document.getElementById('edit_start_date').value = data.start_date;
    document.getElementById('edit_end_date').value = data.end_date;
    document.getElementById('edit_status').value = data.status;
    openModal('editInternshipModal');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
