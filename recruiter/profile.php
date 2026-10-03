<?php
require_once __DIR__ . '/../config/db.php';
requireRecruiter();

$pdo = getDBConnection();
$companyId = $_SESSION['company_id'];
$userId = $_SESSION['user_id'];
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $hrName = sanitize($_POST['hr_name']);
        $hrEmail = sanitize($_POST['hr_email']);
        $phone = sanitize($_POST['phone']);
        $website = sanitize($_POST['website']);
        $industry = sanitize($_POST['industry']);

        $stmtUp = $pdo->prepare("UPDATE companies SET hr_name = ?, hr_email = ?, phone = ?, website = ?, industry = ? WHERE id = ?");
        $stmtUp->execute([$hrName, $hrEmail, $phone, $website, $industry, $companyId]);

        // Also update recruiter profile contact name
        $stmtRp = $pdo->prepare("UPDATE recruiter_profiles SET contact_name = ? WHERE user_id = ?");
        $stmtRp->execute([$hrName, $userId]);
        $_SESSION['username'] = $hrName ?: $_SESSION['company_name'];

        $msg = "Company profile details updated successfully!";
    } elseif ($action === 'change_password') {
        $oldPass = $_POST['old_password'];
        $newPass = $_POST['new_password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && (password_verify($oldPass, $user['password']) || $oldPass === $user['password'])) {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmtUp = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUp->execute([$newHash, $userId]);
            $msg = "Account password updated successfully!";
        } else {
            $error = "Current password was incorrect.";
        }
    }
}

// Fetch company details
$stmtComp = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmtComp->execute([$companyId]);
$company = $stmtComp->fetch();

$pageTitle = 'Company Profile & Security';
$currentPage = 'profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Corporate Recruiter Profile & Security</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Manage your company representative profile, contact information, and portal access credentials.</p>
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

<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
    
    <!-- LEFT: COMPANY & HR DETAILS FORM -->
    <div class="table-card" style="padding: 26px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <h4 style="font-weight: 800; font-size: 17px; color: #0F172A; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            🏢 Enterprise Information & HR Contact
        </h4>

        <form method="POST">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Company / Enterprise Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($company['company_name']); ?>" disabled style="background: #F1F5F9; border-radius: 10px; padding: 10px 12px; font-weight: 700;">
                <span style="font-size: 11px; color: #94A3B8; margin-top: 4px; display: block;">Company name is verified and managed by the placement cell.</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">HR Lead / Representative Name *</label>
                    <input type="text" name="hr_name" class="form-control" value="<?= htmlspecialchars($company['hr_name'] ?? ''); ?>" required style="border-radius: 10px; padding: 10px 12px; font-weight: 700;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Official HR Email Address *</label>
                    <input type="email" name="hr_email" class="form-control" value="<?= htmlspecialchars($company['hr_email'] ?? ''); ?>" required style="border-radius: 10px; padding: 10px 12px; font-weight: 700;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Contact Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($company['phone'] ?? ''); ?>" style="border-radius: 10px; padding: 10px 12px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Industry Domain</label>
                    <input type="text" name="industry" class="form-control" value="<?= htmlspecialchars($company['industry'] ?? ''); ?>" style="border-radius: 10px; padding: 10px 12px;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Careers Website / Portal URL</label>
                <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($company['website'] ?? ''); ?>" placeholder="https://careers.company.com" style="border-radius: 10px; padding: 10px 12px;">
            </div>

            <button type="submit" class="btn-primary" style="border-radius: 12px; padding: 12px 24px; font-weight: 800; font-size: 14px;">
                💾 Save Company Details
            </button>
        </form>
    </div>

    <!-- RIGHT: CHANGE PASSWORD FORM -->
    <div class="table-card" style="padding: 26px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <h4 style="font-weight: 800; font-size: 17px; color: #0F172A; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            🔒 Recruiter Portal Password
        </h4>

        <form method="POST">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Current Password *</label>
                <input type="password" name="old_password" class="form-control" required style="border-radius: 10px; padding: 10px 12px;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">New Password *</label>
                <input type="password" name="new_password" class="form-control" required style="border-radius: 10px; padding: 10px 12px;">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 12px; padding: 12px; font-weight: 800;">
                Update Recruiter Password
            </button>
        </form>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
