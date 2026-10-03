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

// Handle Off-Campus Offer Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_off_campus') {
    $companyName = sanitize($_POST['company_name']);
    $designation = sanitize($_POST['designation']);
    $packageCtc = sanitize($_POST['package_ctc']);
    $location = sanitize($_POST['location'] ?? 'India');
    $offerDate = sanitize($_POST['offer_date']);

    $offerFile = null;
    if (isset($_FILES['offer_letter_file']) && $_FILES['offer_letter_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['offer_letter_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        if (in_array($ext, $allowed)) {
            $fileName = 'offcampus_' . $student['enrollment_no'] . '_' . time() . '.' . $ext;
            $targetDir = __DIR__ . '/../uploads/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            if (move_uploaded_file($_FILES['offer_letter_file']['tmp_name'], $targetDir . $fileName)) {
                $offerFile = $fileName;
            }
        } else {
            $error = "Invalid file type. Please upload a PDF or image format (JPG/PNG).";
        }
    }

    if (!$error) {
        $stmtIns = $pdo->prepare("INSERT INTO off_campus_placements (student_id, company_name, designation, package_ctc, location, offer_date, offer_letter_file, verification_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmtIns->execute([$studentId, $companyName, $designation, $packageCtc, $location, $offerDate, $offerFile]);
        $msg = "Off-campus placement offer submitted successfully! TPO cell will verify your documentation.";
    }
}

// Fetch Student's Submitted Off-Campus Offers
$stmtOffers = $pdo->prepare("SELECT * FROM off_campus_placements WHERE student_id = ? ORDER BY id DESC");
$stmtOffers->execute([$studentId]);
$myOffers = $stmtOffers->fetchAll();

$pageTitle = 'Record Off-Campus Placement';
$currentPage = 'off_campus';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Off-Campus Placement Offer Submission</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Record your external off-campus hiring offers for institutional credit verification and university placement records.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="badge-status placed" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;">
        <?= htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="badge-status unplaced" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;">
        <?= htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1.2fr 1.5fr; gap: 24px;">
    
    <!-- LEFT: SUBMIT OFFER FORM -->
    <div class="table-card" style="padding: 26px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <h4 style="font-weight: 800; font-size: 17px; color: #0F172A; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            📝 Submit Off-Campus Placement Offer
        </h4>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_off_campus">

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Hiring Company / Enterprise Name *</label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. Adobe Systems India" required style="border-radius: 10px; padding: 10px 12px; font-weight: 700;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Designation / Job Role *</label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. Software Engineer" required style="border-radius: 10px; padding: 10px 12px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Salary Package (CTC) *</label>
                    <input type="text" name="package_ctc" class="form-control" placeholder="e.g. ₹14.00 LPA" required style="border-radius: 10px; padding: 10px 12px; font-weight: 700; color: #059669;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Job Location</label>
                    <input type="text" name="location" class="form-control" placeholder="e.g. Bengaluru / Hybrid" style="border-radius: 10px; padding: 10px 12px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Offer Letter Date *</label>
                    <input type="date" name="offer_date" class="form-control" required style="border-radius: 10px; padding: 10px 12px;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 22px; background: #F8FAFC; border: 1px dashed #CBD5E1; padding: 16px; border-radius: 14px;">
                <label style="font-weight: 700; font-size: 13px; color: #1D4ED8; margin-bottom: 6px; display: block;">Upload Offer Letter Document (PDF / Image) *</label>
                <input type="file" name="offer_letter_file" accept=".pdf,image/*" class="form-control" required style="border-radius: 8px; background: white;">
                <span style="font-size: 11.5px; color: #64748B; margin-top: 4px; display: block;">Upload official appointment or offer letter for TPO verification.</span>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px; padding: 12px; font-weight: 800; box-shadow: 0 4px 14px rgba(59,130,246,0.3);">
                🚀 Submit Offer for Verification
            </button>
        </form>
    </div>

    <!-- RIGHT: MY SUBMITTED OFF-CAMPUS OFFERS -->
    <div class="table-card" style="padding: 26px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <h4 style="font-weight: 800; font-size: 17px; color: #0F172A; margin-bottom: 18px;">
            📋 Submitted Off-Campus Records
        </h4>

        <?php if (empty($myOffers)): ?>
            <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                <div style="font-size: 32px; margin-bottom: 8px;">📂</div>
                <strong style="color: #0F172A; display: block;">No Off-Campus Offers Submitted</strong>
                <span style="font-size: 12.5px;">If you receive an off-campus offer, submit it here to update your university placement status.</span>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($myOffers as $off): ?>
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 16px; border-radius: 16px; display: flex; justify-content: space-between; align-items: flex-start; gap: 14px;">
                        <div>
                            <strong style="font-size: 15px; color: #0F172A; display: block;">🏢 <?= htmlspecialchars($off['company_name']); ?></strong>
                            <span style="font-size: 13px; color: #475569; font-weight: 600;">
                                <?= htmlspecialchars($off['designation']); ?> • <strong style="color: #059669;"><?= htmlspecialchars($off['package_ctc']); ?></strong>
                            </span>
                            <div style="font-size: 12px; color: #64748B; margin-top: 4px;">
                                Offer Date: <?= date('d M Y', strtotime($off['offer_date'])); ?> &nbsp;•&nbsp; Location: <?= htmlspecialchars($off['location'] ?: 'N/A'); ?>
                            </div>
                            <?php if ($off['offer_letter_file'] && file_exists(__DIR__ . '/../uploads/' . $off['offer_letter_file'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($off['offer_letter_file']); ?>" target="_blank" style="display: inline-block; font-size: 12px; font-weight: 800; color: #2563EB; margin-top: 6px; text-decoration: none;">
                                    📜 View Uploaded Document ↗
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($off['remarks'])): ?>
                                <div style="margin-top: 6px; font-size: 11.5px; color: #334155; background: #FFFFFF; border: 1px solid #CBD5E1; padding: 6px 10px; border-radius: 8px;">
                                    <strong>TPO Remarks:</strong> <?= htmlspecialchars($off['remarks']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="text-align: right; flex-shrink: 0;">
                            <?php 
                            $offStCls = ($off['verification_status'] === 'Verified') ? 'placed' : (($off['verification_status'] === 'Rejected') ? 'unplaced' : 'in-process');
                            ?>
                            <span class="badge-status <?= $offStCls; ?>" style="font-weight: 800; font-size: 12px; padding: 6px 12px; border-radius: 10px;">
                                <?= htmlspecialchars($off['verification_status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
