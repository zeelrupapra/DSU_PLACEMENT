<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';
$error = '';

// Test Email Dispatch Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test_mail') {
    $testTo = sanitize($_POST['test_email']);
    if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address for testing.";
    } else {
        $subject = "🧪 Test Email Dispatch - Dr. Subhash Placement Portal";
        $bodyHtml = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #E2E8F0; border-radius: 16px; padding: 28px; background: #FFFFFF;'>
            <h2 style='color: #2563EB; margin-top: 0;'>🎉 Gmail SMTP Connection Test</h2>
            <p style='font-size: 14px; color: #334155; line-height: 1.6;'>
                This is a live test notification sent from the <strong>Dr. Subhash Placement Cell Portal</strong> to verify SMTP configuration for account <code>" . htmlspecialchars(defined('SMTP_USER') ? SMTP_USER : 'mrstrome4@gmail.com') . "</code>.
            </p>
            <div style='background: #EFF6FF; border: 1px solid #BFDBFE; padding: 14px 18px; border-radius: 12px; margin: 20px 0; font-size: 13px; color: #1E40AF;'>
                <strong>Dispatch Timestamp:</strong> " . date('Y-m-d H:i:s') . "<br>
                <strong>Recipient:</strong> " . htmlspecialchars($testTo) . "
            </div>
            <p style='font-size: 13px; color: #64748B;'>Training & Placement Cell — Dr. Subhash University, Junagadh</p>
        </div>
        ";

        if (file_exists(__DIR__ . '/../config/mail.php')) {
            require_once __DIR__ . '/../config/mail.php';
        }

        $sent = false;
        if (function_exists('sendSmtpEmail')) {
            $sent = sendSmtpEmail($testTo, $subject, $bodyHtml);
        }

        if ($sent) {
            $msg = "🎉 Test email sent successfully to $testTo via Gmail SMTP!";
            $pdo->prepare("INSERT INTO mail_logs (recipient, subject, body, sent_at, status) VALUES (?, ?, ?, NOW(), 'Sent (SMTP)')")->execute([$testTo, $subject, $bodyHtml]);
        } else {
            $error = "⚠️ SMTP Connection Failed: Google rejected login for " . (defined('SMTP_USER') ? SMTP_USER : 'mrstrome4@gmail.com') . ". Please verify your 16-character Google App Password in Settings.";
            $pdo->prepare("INSERT INTO mail_logs (recipient, subject, body, sent_at, status) VALUES (?, ?, ?, NOW(), 'Failed (App Password Required)')")->execute([$testTo, $subject, $bodyHtml]);
        }
    }
}

// Resend Logged Email Action
if (isset($_GET['resend_id'])) {
    $resendId = intval($_GET['resend_id']);
    $mail = $pdo->query("SELECT * FROM mail_logs WHERE id = $resendId")->fetch();
    if ($mail) {
        if (file_exists(__DIR__ . '/../config/mail.php')) {
            require_once __DIR__ . '/../config/mail.php';
        }
        $sent = false;
        if (function_exists('sendSmtpEmail')) {
            $sent = sendSmtpEmail($mail['recipient'], $mail['subject'], $mail['body']);
        }
        if ($sent) {
            $pdo->prepare("UPDATE mail_logs SET status = 'Sent (SMTP)', sent_at = NOW() WHERE id = ?")->execute([$resendId]);
            setFlashMessage("🎉 Email re-dispatched successfully to " . htmlspecialchars($mail['recipient']), "success");
        } else {
            setFlashMessage("⚠️ Resend failed: Google App Password required in Settings.", "warning");
        }
        header("Location: mail_logs.php");
        exit;
    }
}

// Fetch Mail Logs
$logs = $pdo->query("SELECT * FROM mail_logs ORDER BY id DESC LIMIT 100")->fetchAll();
$totalMails = count($logs);

$pageTitle = 'Mail Outbox & Dispatch Logs';
$currentPage = 'mail_logs';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">📧 Portal Mail Outbox & SMTP Dispatch Logs</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Monitor all outgoing student emails, offer letter notifications, and test live SMTP delivery.</p>
    </div>
    <button class="btn-primary" onclick="openModal('testMailModal')" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
        🧪 Send Live Test Email
    </button>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 20px; display: inline-block; font-size: 14px; padding: 10px 18px; border-radius: 12px; font-weight: 800;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge-status unplaced" style="margin-bottom: 20px; display: inline-block; font-size: 14px; padding: 10px 18px; border-radius: 12px; font-weight: 800;"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Gmail App Password Instruction Banner -->
<div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 20px; padding: 22px; margin-bottom: 28px;">
    <div style="display: flex; gap: 16px; align-items: flex-start;">
        <div style="width: 44px; height: 44px; background: #DBEAFE; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; color: #1E40AF;">
            🔑
        </div>
        <div style="flex: 1;">
            <h4 style="font-size: 16px; font-weight: 800; color: #1E40AF; margin-bottom: 6px;">How to Enable 100% Real Email Sending via Gmail (`<?= htmlspecialchars(defined('SMTP_USER') ? SMTP_USER : 'mrstrome4@gmail.com'); ?>`)</h4>
            <p style="font-size: 13px; color: #3B82F6; line-height: 1.6; margin-bottom: 12px; font-weight: 600;">
                Google requires a <strong>16-character App Password</strong> when sending emails from PHP scripts. Follow these 4 simple steps:
            </p>
            <ol style="font-size: 12.5px; color: #1E3A8A; line-height: 1.8; margin: 0; padding-left: 20px; font-weight: 700;">
                <li>Go to <a href="https://myaccount.google.com/security" target="_blank" style="color: #2563EB; text-decoration: underline;">myaccount.google.com/security</a> for account <code><?= htmlspecialchars(defined('SMTP_USER') ? SMTP_USER : 'mrstrome4@gmail.com'); ?></code>.</li>
                <li>Make sure <strong>2-Step Verification</strong> is turned <strong>ON</strong>.</li>
                <li>Search for <strong>App Passwords</strong> (or visit <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: #2563EB; text-decoration: underline;">myaccount.google.com/apppasswords</a>).</li>
                <li>Create an App Password named "Placement Portal" -> Copy the 16-letter code (e.g. <code>abcd efgh ijkl mnop</code>) -> Go to <a href="settings.php" style="color: #2563EB; text-decoration: underline;">Admin Settings</a> and paste it into <strong>SMTP Password</strong>!</li>
            </ol>
        </div>
    </div>
</div>

<!-- Mail Logs Table -->
<div class="table-card" style="padding: 0; background: white; border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden;">
    <div class="table-responsive">
        <table class="custom-table" style="width: 100%; border-collapse: separate; border-spacing: 0 8px; padding: 12px;">
            <thead>
                <tr style="background: #F8FAFC;">
                    <th style="padding: 14px 16px;">Recipient Email</th>
                    <th style="padding: 14px 16px;">Email Subject</th>
                    <th style="padding: 14px 16px;">Sent Timestamp</th>
                    <th style="padding: 14px 16px;">Delivery Status</th>
                    <th style="text-align: right; padding: 14px 16px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #64748B;">No outgoing mail dispatches logged yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): 
                        $isSuccess = (strpos($l['status'], 'Sent') !== false);
                        $badgeCls = $isSuccess ? 'placed' : 'unplaced';
                    ?>
                        <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                            <td style="padding: 14px 16px;">
                                <strong style="font-size: 13.5px; color: #0F172A;"><?= htmlspecialchars($l['recipient']); ?></strong>
                            </td>
                            <td style="padding: 14px 16px;">
                                <span style="font-size: 13px; font-weight: 700; color: #334155;"><?= htmlspecialchars($l['subject']); ?></span>
                            </td>
                            <td style="padding: 14px 16px;">
                                <span style="font-size: 12px; color: #64748B; font-weight: 600;"><?= date('d M Y, h:i A', strtotime($l['sent_at'])); ?></span>
                            </td>
                            <td style="padding: 14px 16px;">
                                <span class="badge-status <?= $badgeCls; ?>" style="font-weight: 800; font-size: 12px;"><?= htmlspecialchars($l['status']); ?></span>
                            </td>
                            <td style="text-align: right; padding: 14px 16px; white-space: nowrap;">
                                <button type="button" class="btn-action-icon edit" onclick="viewMailBody(<?= $l['id']; ?>)" style="padding: 6px 12px; height: 34px; border-radius: 10px; font-weight: 800; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; cursor: pointer;">
                                    👁️ View Template
                                </button>
                                <a href="mail_logs.php?resend_id=<?= $l['id']; ?>" class="btn-action-icon place" style="padding: 6px 12px; height: 34px; border-radius: 10px; font-weight: 800; background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; text-decoration: none; display: inline-flex; align-items: center;">
                                    🔄 Resend
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Test Email Modal -->
<div class="modal-overlay" id="testMailModal">
    <div class="modal-box" style="max-width: 500px; border-radius: 24px;">
        <div class="modal-header">
            <h3>🧪 Send Live Test Email</h3>
            <button class="close-modal" onclick="closeModal('testMailModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="send_test_mail">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 800; color: #334155; display: block; margin-bottom: 6px;">Recipient Test Email Address *</label>
                <input type="email" name="test_email" class="form-control-custom" placeholder="e.g. yourname@gmail.com" required value="<?= htmlspecialchars(defined('SMTP_USER') ? SMTP_USER : 'mrstrome4@gmail.com'); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #CBD5E1;">
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="closeModal('testMailModal')" style="padding: 10px 20px; border-radius: 12px; font-weight: 800;">Cancel</button>
                <button type="submit" class="btn-primary" style="padding: 10px 24px; border-radius: 12px; font-weight: 800; background: #2563EB;">🚀 Send Test Email Now</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: View Mail Body Modal -->
<div class="modal-overlay" id="viewMailBodyModal">
    <div class="modal-box" style="max-width: 680px; border-radius: 24px;">
        <div class="modal-header">
            <h3>📄 Sent Email Template Preview</h3>
            <button class="close-modal" onclick="closeModal('viewMailBodyModal')">&times;</button>
        </div>
        <div id="mailBodyContainer" style="padding: 20px; background: #F8FAFC; border-radius: 16px; border: 1px solid #E2E8F0; max-height: 480px; overflow-y: auto;">
            <p style="color: #64748B;">Loading email body...</p>
        </div>
    </div>
</div>

<script>
function viewMailBody(mailId) {
    const container = document.getElementById('mailBodyContainer');
    container.innerHTML = '<p style="color: #64748B;">Loading template content...</p>';
    openModal('viewMailBodyModal');

    fetch('mail_logs.php?fetch_body=1&mail_id=' + mailId)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}
</script>

<?php
if (isset($_GET['fetch_body']) && isset($_GET['mail_id'])) {
    $mId = intval($_GET['mail_id']);
    $mRow = $pdo->query("SELECT body FROM mail_logs WHERE id = $mId")->fetch();
    echo $mRow ? $mRow['body'] : "<p style='color:#EF4444;'>Mail content not found.</p>";
    exit;
}

include __DIR__ . '/../includes/footer.php';
?>
