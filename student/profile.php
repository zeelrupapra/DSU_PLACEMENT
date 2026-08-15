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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? sanitize($_POST['action']) : 'update_profile';

    if ($action === 'remove_resume') {
        // Remove active resume file from uploads directory
        if ($student['resume_file'] && $student['resume_file'] !== 'default_resume.pdf') {
            $oldPath = __DIR__ . '/../uploads/' . $student['resume_file'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }
        $stmtRem = $pdo->prepare("UPDATE students SET resume_file = NULL WHERE id = ?");
        $stmtRem->execute([$studentId]);
        $msg = "Resume file removed successfully!";

        // Refresh student record
        $stmtSt->execute([$studentId]);
        $student = $stmtSt->fetch();
    } else {
        $phone = sanitize($_POST['phone']);
        $cpi = floatval($_POST['cpi']);
        $gender = sanitize($_POST['gender']);

        $profilePhoto = null;
        $resumeFile = null;

        // Profile Photo Upload (Strict single-photo replacement)
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                // Delete old profile photo if customized
                if ($student['profile_photo'] && $student['profile_photo'] !== 'default_avatar.png') {
                    $oldPhotoPath = __DIR__ . '/../uploads/' . $student['profile_photo'];
                    if (file_exists($oldPhotoPath)) {
                        @unlink($oldPhotoPath);
                    }
                }

                $photoName = 'photo_' . $_SESSION['enrollment_no'] . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['profile_photo']['tmp_name'], __DIR__ . '/../uploads/' . $photoName);
                $profilePhoto = $photoName;
            }
        }

        // Resume PDF Upload (STRICT SINGLE RESUME POLICY: Delete old file & replace)
        if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['resume_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                // Delete old resume file from disk to enforce single resume policy
                if ($student['resume_file'] && $student['resume_file'] !== 'default_resume.pdf') {
                    $oldResumePath = __DIR__ . '/../uploads/' . $student['resume_file'];
                    if (file_exists($oldResumePath)) {
                        @unlink($oldResumePath);
                    }
                }

                $resumeName = 'resume_' . $_SESSION['enrollment_no'] . '_' . time() . '.pdf';
                move_uploaded_file($_FILES['resume_file']['tmp_name'], __DIR__ . '/../uploads/' . $resumeName);
                $resumeFile = $resumeName;
            } else {
                $error = "Resume must be in PDF format!";
            }
        }

        if (!$error) {
            $sql = "UPDATE students SET phone = ?, cpi = ?, gender = ?";
            $params = [$phone, $cpi, $gender];

            if ($profilePhoto) {
                $sql .= ", profile_photo = ?";
                $params[] = $profilePhoto;
            }
            if ($resumeFile) {
                $sql .= ", resume_file = ?";
                $params[] = $resumeFile;
            }

            $sql .= " WHERE id = ?";
            $params[] = $studentId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = $resumeFile ? "Resume replaced & profile details saved!" : "Profile details saved successfully!";

            // Refresh student record
            $stmtSt->execute([$studentId]);
            $student = $stmtSt->fetch();
        }
    }
}

$genderSeed = ($student['gender'] === 'Female') ? 'female' : 'male';
$photoSrc = ($student['profile_photo'] && $student['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $student['profile_photo'])) 
    ? '../uploads/' . $student['profile_photo'] 
    : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($student['full_name']) . '&gender=' . $genderSeed;

$hasResume = (!empty($student['resume_file']) && file_exists(__DIR__ . '/../uploads/' . $student['resume_file']));
$resumeUrl = $hasResume ? '../uploads/' . htmlspecialchars($student['resume_file']) : '';

$pageTitle = 'My Profile & Resume';
$currentPage = 'profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Candidate Profile & Resume Dossier</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Manage your personal details, single active resume PDF, and view live document previews.</p>
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

<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px;">
    
    <!-- LEFT COLUMN: PROFILE EDIT & RESUME REPLACEMENT FORM -->
    <div class="table-card" style="padding: 26px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">
            
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <!-- Avatar with + Overlay Upload Button -->
                <div class="avatar-upload-wrapper" style="position: relative; flex-shrink: 0;">
                    <img src="<?= $photoSrc; ?>" alt="Profile Photo" id="avatarPreview" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #3B82F6; box-shadow: 0 4px 12px rgba(59,130,246,0.2);">
                    <label for="photoFileInput" class="avatar-plus-btn" title="Click to Upload New Photo" style="position: absolute; bottom: 0; right: 0; background: #2563EB; color: white; border-radius: 50%; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; cursor: pointer; border: 2px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">+</label>
                    <input type="file" name="profile_photo" id="photoFileInput" accept="image/*" style="display: none;" onchange="previewImage(this)">
                </div>

                <div>
                    <h2 style="font-size: 20px; font-weight: 800; color: #0F172A;"><?= htmlspecialchars($student['full_name']); ?></h2>
                    <p style="color: var(--text-muted); font-size: 13px; margin-top: 2px; font-weight: 600;"><?= htmlspecialchars($student['email']); ?></p>
                    <p style="font-size: 12.5px; font-weight: 700; color: #2563EB; margin-top: 4px; display: flex; gap: 8px; align-items: center;">
                        <span>🎓 <?= htmlspecialchars($student['branch']); ?> Branch</span> • 
                        <span>Batch <?= $student['batch_year']; ?></span> • 
                        <code><?= htmlspecialchars($student['enrollment_no']); ?></code>
                    </p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 6px; display: block;">Gender</label>
                    <select name="gender" class="form-control" style="border-radius: 10px; padding: 9px 12px;">
                        <option value="Male" <?= ($student['gender']==='Male')?'selected':''; ?>>Male</option>
                        <option value="Female" <?= ($student['gender']==='Female')?'selected':''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 6px; display: block;">Academic CPI *</label>
                    <input type="number" step="0.01" name="cpi" class="form-control" value="<?= htmlspecialchars($student['cpi']); ?>" required style="border-radius: 10px; padding: 9px 12px; font-weight: 700;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 6px; display: block;">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']); ?>" required style="border-radius: 10px; padding: 9px 12px;">
                </div>
            </div>

            <!-- Single Resume Upload / Replace Box -->
            <div class="form-group" style="background: #F8FAFC; border: 1px dashed #3B82F6; padding: 20px; border-radius: 16px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="font-size: 14px; font-weight: 800; color: #1D4ED8; display: block; margin: 0;">
                        📄 <?= $hasResume ? 'Replace Active Resume (PDF Only)' : 'Upload Resume PDF'; ?>
                    </label>
                    <span style="font-size: 11px; font-weight: 800; background: #EFF6FF; color: #2563EB; padding: 3px 8px; border-radius: 6px; border: 1px solid #BFDBFE;">Max 1 Active File</span>
                </div>
                
                <input type="file" name="resume_file" accept=".pdf" class="form-control" style="border-radius: 10px; background: white;">
                
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px; font-weight: 600;">
                    <?php if ($hasResume): ?>
                        Current File: <code style="color: #2563EB; font-weight: 700;"><?= htmlspecialchars($student['resume_file']); ?></code> (Uploading a new file will automatically delete & replace the old resume).
                    <?php else: ?>
                        No resume uploaded. Upload a PDF resume for 1-click recruiter applications.
                    <?php endif; ?>
                </p>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px; padding: 12px; font-weight: 800; box-shadow: 0 4px 14px rgba(59,130,246,0.3);">
                💾 Save Profile & Resume
            </button>
        </form>
    </div>

    <!-- RIGHT COLUMN: LIVE RESUME PDF PREVIEWER & QUICK ACTIONS -->
    <div class="table-card" style="padding: 24px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <h4 style="font-weight: 800; font-size: 16px; color: #0F172A; display: flex; align-items: center; gap: 8px;">
                    👁️ Active Resume Preview
                </h4>

                <?php if ($hasResume): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remove your current active resume PDF?');">
                        <input type="hidden" name="action" value="remove_resume">
                        <button type="submit" class="btn-action-icon delete" style="padding: 6px 14px; font-size: 12px; border-radius: 10px; font-weight: 800; border: 1px solid #FCA5A5; background: #FEE2E2; color: #B91C1C; cursor: pointer;">
                            🗑️ Remove Resume
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($hasResume): ?>
                <!-- Document Info Card -->
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px 16px; border-radius: 14px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #059669; background: #ECFDF5; padding: 3px 8px; border-radius: 6px; border: 1px solid #A7F3D0; text-transform: uppercase;">🟢 Active Resume Dossier</span>
                        <p style="font-size: 13px; font-weight: 800; color: #0F172A; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 240px;"><?= htmlspecialchars($student['resume_file']); ?></p>
                    </div>
                    <span style="font-size: 12px; font-weight: 700; color: #64748B;">PDF Document</span>
                </div>

                <!-- Action Buttons Toolbar -->
                <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                    <a href="<?= $resumeUrl; ?>" target="_blank" class="btn-secondary" style="flex: 1; justify-content: center; border-radius: 10px; padding: 10px 12px; font-size: 12.5px; font-weight: 800; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        👁️ Open Document ↗
                    </a>
                    <a href="<?= $resumeUrl; ?>" download class="btn-secondary" style="flex: 1; justify-content: center; border-radius: 10px; padding: 10px 12px; font-size: 12.5px; font-weight: 800; background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        📥 Download PDF
                    </a>
                </div>

                <!-- Embedded High-Performance PDF Viewer -->
                <div style="border-radius: 16px; overflow: hidden; border: 1px solid #CBD5E1; background: #F8FAFC; box-shadow: inset 0 2px 6px rgba(0,0,0,0.03);">
                    <object data="<?= $resumeUrl; ?>#view=FitH" type="application/pdf" style="width: 100%; height: 500px; display: block;">
                        <embed src="<?= $resumeUrl; ?>#view=FitH" type="application/pdf" style="width: 100%; height: 500px; display: block;" />
                        <div style="padding: 40px 20px; text-align: center; background: white;">
                            <div style="font-size: 36px; margin-bottom: 10px;">📄</div>
                            <h4 style="font-weight: 800; color: #0F172A; font-size: 15px;">PDF Resume Document Ready</h4>
                            <p style="color: #64748B; font-size: 13px; margin-top: 4px; margin-bottom: 16px;">Click below to open or view your active resume PDF document.</p>
                            <a href="<?= $resumeUrl; ?>" target="_blank" class="btn-primary" style="display: inline-flex; border-radius: 12px; padding: 10px 22px; font-weight: 800;">Open Resume PDF ↗</a>
                        </div>
                    </object>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; background: #F8FAFC; border-radius: 16px; border: 1px dashed #CBD5E1;">
                    <div style="font-size: 42px; margin-bottom: 12px;">📄</div>
                    <h4 style="font-size: 16px; font-weight: 800; color: #0F172A;">No Active Resume Uploaded</h4>
                    <p style="color: #64748B; font-size: 13px; margin-top: 6px; line-height: 1.5;">
                        Upload your single active PDF resume on the left form to preview it here and apply to campus placement drives.
                </div>
            <?php endif; ?>

            <?php if ($student['placement_status'] === 'Placed' || !empty($student['offer_letter'])): ?>
                <!-- Official Placement Offer Letter Showcase Card -->
                <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 22px; border-radius: 20px; margin-top: 24px; box-shadow: 0 8px 24px -6px rgba(5,150,105,0.3);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <span style="font-size: 11px; font-weight: 800; color: #ECFDF5; background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">🎉 Verified Campus Placement</span>
                            <h4 style="font-size: 17px; font-weight: 900; color: #FFFFFF; margin-top: 6px; margin-bottom: 2px;">
                                Placed at: <?= htmlspecialchars(!empty($student['placed_companies']) ? $student['placed_companies'] : 'Corporate Placement Partner'); ?>
                            </h4>
                            <p style="font-size: 12px; color: #A7F3D0; margin: 0; font-weight: 600;">Issued & Verified by Training & Placement Cell, Dr. Subhash University.</p>
                        </div>
                        
                        <?php if (!empty($student['offer_letter'])): ?>
                            <a href="../view_offer.php?file=<?= urlencode($student['offer_letter']); ?>" target="_blank" class="btn-primary" style="padding: 11px 20px; border-radius: 12px; font-size: 13px; font-weight: 800; background: #FFFFFF; color: #047857; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
                                📜 View & Download Offer PDF ↗
                            </a>
                        <?php else: ?>
                            <span style="background: rgba(255,255,255,0.2); color: #FFFFFF; font-weight: 800; padding: 8px 16px; border-radius: 12px; font-size: 12.5px;">
                                ✓ Placement Confirmed
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
