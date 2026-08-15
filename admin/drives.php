<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';

// Create Drive or Update Student Application Status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);

    if ($action === 'create_drive') {
        $companyId = intval($_POST['company_id']);
        $title = sanitize($_POST['title']);
        $designation = sanitize($_POST['designation']);
        $package = sanitize($_POST['package_ctc']);
        $location = sanitize($_POST['location']);
        $minCpi = floatval($_POST['min_cpi']);
        $branches = sanitize($_POST['allowed_branches']);
        $driveDate = sanitize($_POST['drive_date']);
        $deadline = sanitize($_POST['deadline']);
        $desc = sanitize($_POST['description']);

        $stmt = $pdo->prepare("INSERT INTO drives (company_id, title, designation, package_ctc, location, min_cpi, allowed_branches, drive_date, deadline, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$companyId, $title, $designation, $package, $location, $minCpi, $branches, $driveDate, $deadline, $desc]);
        $msg = "New recruitment drive published successfully!";
    } elseif ($action === 'update_app_status') {
        $appId = intval($_POST['app_id']);
        $newStatus = sanitize($_POST['status']);
        $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : '';

        $appInfo = $pdo->query("SELECT a.student_id, a.drive_id, d.package_ctc, c.company_name FROM applications a JOIN drives d ON a.drive_id = d.id JOIN companies c ON d.company_id = c.id WHERE a.id = $appId")->fetch();

        if ($newStatus === 'Selected' && $appInfo) {
            $check = canSelectStudentForCompany($pdo, $appInfo['student_id'], $appInfo['drive_id']);
            if (!$check['allowed']) {
                setFlashMessage($check['reason'], 'danger');
                header("Location: drives.php");
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
                // Check if candidate has any other 'Selected' application
                $otherSel = $pdo->query("SELECT COUNT(*) FROM applications WHERE student_id = {$appInfo['student_id']} AND status = 'Selected'")->fetchColumn();
                if ($otherSel == 0) {
                    $pdo->prepare("UPDATE students SET placement_status = 'In-Process', placed_companies = NULL, offer_letter = NULL WHERE id = ?")->execute([$appInfo['student_id']]);
                }
            }
            setFlashMessage("Candidate application status updated to $newStatus!", 'info');
        }
        header("Location: drives.php");
        exit;
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

            // Always update placement status to 'Placed' and set placed company name
            $pdo->prepare("UPDATE students SET placement_status = 'Placed', placed_companies = ?, offer_letter = ? WHERE id = ?")->execute([$companyName, $fileName, $stId]);

            if ($appId > 0) {
                $pdo->prepare("UPDATE applications SET status = 'Selected', remarks = 'Offer Letter Verified' WHERE id = ?")->execute([$appId]);
            } else {
                $pdo->prepare("UPDATE applications SET status = 'Selected' WHERE student_id = ? AND status != 'Rejected'")->execute([$stId]);
            }

            $msg = "🎉 Offer Letter & Placement Selection updated for " . htmlspecialchars($st['full_name']) . " at " . htmlspecialchars($companyName) . "!";
            setFlashMessage($msg, "success");
        }
    }

    header("Location: drives.php");
    exit;
}

// Fetch Drives with Company Info
$drives = $pdo->query("SELECT d.*, c.company_name, c.logo FROM drives d JOIN companies c ON d.company_id = c.id ORDER BY d.id DESC")->fetchAll();
$companies = $pdo->query("SELECT id, company_name FROM companies ORDER BY company_name ASC")->fetchAll();

$totalDrives = count($drives);
$totalAppsSubmitted = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn() ?: 0;
$totalSelected = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Selected'")->fetchColumn() ?: 0;

$pageTitle = 'Current Openings & Drives';
$currentPage = 'drives';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Action Row -->
<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800;">Current Openings & Placement Drives</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Manage active recruitment drives, eligibility cutoffs, and student candidate shortlisting.</p>
    </div>
    <button class="btn-primary" onclick="openModal('createDriveModal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Post New Placement Drive
    </button>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 16px; display: inline-block; font-size: 14px; padding: 10px 16px;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>

<!-- Summary KPI Cards Row -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Active Drives</span>
            <div class="kpi-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px;"><?= number_format($totalDrives); ?> Drives</div>
        <span class="kpi-badge info">Current Openings</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Applications Received</span>
            <div class="kpi-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #F59E0B;"><?= number_format($totalAppsSubmitted); ?> Submitted</div>
        <span class="kpi-badge info" style="background:#FEF3C7; color:#B45309;">📋 Candidate Submissions</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Selected Candidates</span>
            <div class="kpi-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #10B981;"><?= number_format($totalSelected); ?> Placed</div>
        <span class="kpi-badge up">🟢 Offer Extended</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Highest CTC</span>
            <div class="kpi-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px;">₹56.00 LPA</div>
        <span class="kpi-badge info">🏢 Microsoft</span>
    </div>
</div>

<!-- Drives Table -->
<div class="table-card" style="padding: 24px;">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="min-width: 240px;">Drive Title & Recruiter</th>
                    <th>Designation</th>
                    <th>Package CTC</th>
                    <th>Eligibility Cutoff</th>
                    <th>Drive Date</th>
                    <th style="text-align: right;">Candidate Submissions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drives as $drv): 
                    $appCount = $pdo->query("SELECT COUNT(*) FROM applications WHERE drive_id = {$drv['id']}")->fetchColumn();
                ?>
                    <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                        <td style="border-top-left-radius: 16px; border-bottom-left-radius: 16px;">
                            <strong style="font-size: 15px; color: #0F172A;"><?= htmlspecialchars($drv['title']); ?></strong><br>
                            <span style="font-size: 13px; color: #3B82F6; font-weight: 700;">🏢 <?= htmlspecialchars($drv['company_name']); ?></span>
                        </td>
                        <td><span class="branch-badge"><?= htmlspecialchars($drv['designation']); ?></span></td>
                        <td><strong style="color: #10B981; font-size: 15px; font-weight: 800;"><?= htmlspecialchars($drv['package_ctc']); ?></strong></td>
                        <td>
                            <span class="cpi-pill high">Min CPI: <?= $drv['min_cpi']; ?></span><br>
                            <span style="font-size: 11px; color: var(--text-muted); font-weight: 600; margin-top: 2px; display: block;"><?= htmlspecialchars($drv['allowed_branches']); ?></span>
                        </td>
                        <td><strong><?= date('d M Y', strtotime($drv['drive_date'])); ?></strong></td>
                        <td style="text-align: right; border-top-right-radius: 16px; border-bottom-right-radius: 16px;">
                            <button class="btn-action-icon dossier" style="padding: 8px 16px;" onclick="viewApplications(<?= $drv['id']; ?>, '<?= htmlspecialchars($drv['title']); ?>')">
                                👥 View Applications (<?= $appCount; ?>)
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Create Placement Drive -->
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
                    <label>Designation</label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. SDE-1" required>
                </div>
                <div class="form-group">
                    <label>Package CTC Offered</label>
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
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Drive Date</label>
                    <input type="date" name="drive_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Application Deadline</label>
                    <input type="date" name="deadline" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Job Description & Requirements</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Enter key responsibilities..."></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">Publish Recruitment Drive</button>
        </form>
    </div>
</div>

<!-- Modal 2: View Drive Applications Modal -->
<div class="modal-overlay" id="applicationsModal">
    <div class="modal-box full-size" style="max-width: 900px !important;">
        <div class="modal-header">
            <h3 id="appModalTitle">Drive Candidates</h3>
            <button class="close-modal" onclick="closeModal('applicationsModal')">&times;</button>
        </div>
        <div id="applicationsContainer" class="dossier-scroll-area">
            <p style="color: var(--text-muted);">Loading candidates...</p>
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

        <form method="POST" enctype="multipart/form-data" action="drives.php">
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
    const appIdEl = document.getElementById('offerAppId');

    if (stIdEl) stIdEl.value = studentId;
    if (stNameEl) stNameEl.value = studentName;
    if (compNameEl) compNameEl.value = companyName || '';
    if (appIdEl) appIdEl.value = appId || 0;

    modal.classList.add('active');
};

window.handleAppStatusSubmit = function(form) {
    const statusSelect = form.querySelector('select[name="status"]');
    if (statusSelect && statusSelect.value === 'Selected') {
        const studentId = form.getAttribute('data-id');
        const studentName = form.getAttribute('data-name');
        const companyName = form.getAttribute('data-company');
        const appId = form.getAttribute('data-appid');

        openOfferUploadModal(studentId, studentName, companyName, appId);
        return false; // Intercept submit to show Offer Upload Flash Modal!
    }
    return true;
};

window.handleSelectChange = function(selectEl) {
    if (selectEl.value === 'Selected') {
        handleAppStatusSubmit(selectEl.form);
    }
};

function viewApplications(driveId, driveTitle) {
    document.getElementById('appModalTitle').innerText = 'Candidates for ' + driveTitle;
    const container = document.getElementById('applicationsContainer');
    openModal('applicationsModal');
    
    fetch('drives.php?fetch_apps=1&drive_id=' + driveId)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}
</script>

<?php
// Handle AJAX endpoint for viewing applicants inside modal
if (isset($_GET['fetch_apps']) && isset($_GET['drive_id'])) {
    $dId = intval($_GET['drive_id']);
    $drv = $pdo->query("SELECT d.*, c.company_name FROM drives d JOIN companies c ON d.company_id = c.id WHERE d.id = $dId")->fetch();
    $companyLabel = $drv ? $drv['company_name'] . ' (' . $drv['package_ctc'] . ')' : 'Campus Recruiter';

    $apps = $pdo->query("SELECT a.*, s.enrollment_no, s.full_name, s.branch, s.cpi, s.resume_file, s.offer_letter FROM applications a JOIN students s ON a.student_id = s.id WHERE a.drive_id = $dId ORDER BY a.id DESC")->fetchAll();
    
    if (empty($apps)) {
        echo "<p style='color: var(--text-muted); text-align: center; padding: 30px;'>No student candidates have applied for this drive yet.</p>";
        exit;
    }

    echo "<table class='custom-table'><thead><tr><th>Candidate Name</th><th>Branch</th><th>CPI</th><th>Current Pipeline Status</th><th>Update Selection Stage</th></tr></thead><tbody>";
    foreach ($apps as $ap) {
        $stNameAttr = htmlspecialchars($ap['full_name'], ENT_QUOTES, 'UTF-8');
        $compLabelAttr = htmlspecialchars($companyLabel, ENT_QUOTES, 'UTF-8');

        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($ap['full_name']) . "</strong><br><code style='font-size:11px; color:#3B82F6;'>" . htmlspecialchars($ap['enrollment_no']) . "</code></td>";
        echo "<td><span class='branch-badge'>" . htmlspecialchars($ap['branch']) . "</span></td>";
        echo "<td><span class='cpi-pill high'>★ " . number_format($ap['cpi'], 2) . "</span></td>";
        echo "<td><span class='badge-status info'>" . htmlspecialchars($ap['status']) . "</span></td>";
        echo "<td style='white-space: nowrap;'>
            <form method='POST' style='display:inline-flex; gap:8px; align-items:center;' data-id='{$ap['student_id']}' data-name='{$stNameAttr}' data-company='{$compLabelAttr}' data-appid='{$ap['id']}' onsubmit='return handleAppStatusSubmit(this);'>
                <input type='hidden' name='action' value='update_app_status'>
                <input type='hidden' name='app_id' value='{$ap['id']}'>
                <select name='status' class='select-pill' style='font-size:12px; padding:6px 12px;' onchange='handleSelectChange(this);'>
                    <option value='Applied' " . ($ap['status']==='Applied'?'selected':'') . ">Applied</option>
                    <option value='Shortlisted' " . ($ap['status']==='Shortlisted'?'selected':'') . ">Shortlist</option>
                    <option value='Interview Round' " . ($ap['status']==='Interview Round'?'selected':'') . ">Interview Round</option>
                    <option value='Selected' " . ($ap['status']==='Selected'?'selected':'') . ">Selected (Placed)</option>
                    <option value='Rejected' " . ($ap['status']==='Rejected'?'selected':'') . ">Reject</option>
                </select>
                <button type='submit' class='btn-primary' style='padding:6px 12px; font-size:12px;'>Save</button>
            </form>";
        if (!empty($ap['offer_letter'])) {
            echo " <a href='../view_offer.php?file=" . urlencode($ap['offer_letter']) . "' target='_blank' class='btn-secondary' style='padding:5px 10px; font-size:11px; font-weight:800; background:#ECFDF5; color:#059669; border:1px solid #A7F3D0; text-decoration:none; border-radius:8px; display:inline-flex; align-items:center; gap:4px;'>📜 View Offer</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    exit;
}

include __DIR__ . '/../includes/footer.php'; 
?>
