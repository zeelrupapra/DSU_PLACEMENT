<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';
$error = '';

// Ensure settings table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY, 
        setting_value TEXT, 
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

// Process Settings Save & Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);

    if ($action === 'save_settings') {
        foreach ($_POST['settings'] as $key => $val) {
            $keyClean = sanitize($key);
            $valClean = trim($val);
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$keyClean, $valClean, $valClean]);
        }

        // Dynamically update config/mail.php with saved SMTP credentials
        $smtpEnabled = isset($_POST['settings']['smtp_enabled']) && $_POST['settings']['smtp_enabled'] === '1' ? 'true' : 'false';
        $smtpHost = addslashes(trim($_POST['settings']['smtp_host'] ?? 'smtp.gmail.com'));
        $smtpPort = intval($_POST['settings']['smtp_port'] ?? 587);
        $smtpSecure = addslashes(trim($_POST['settings']['smtp_secure'] ?? 'tls'));
        $smtpUser = addslashes(trim($_POST['settings']['smtp_user'] ?? 'mrstrome4@gmail.com'));
        $smtpPass = addslashes(trim($_POST['settings']['smtp_pass'] ?? 'Zeel_patel@224'));
        $smtpFromName = addslashes(trim($_POST['settings']['smtp_from_name'] ?? 'Dr. Subhash Placement Cell'));

        $mailPhpContent = "<?php\n"
            . "// =========================================================================\n"
            . "// GMAIL SMTP CONFIGURATION - AUTOMATICALLY UPDATED FROM ADMIN SETTINGS\n"
            . "// =========================================================================\n\n"
            . "define('SMTP_ENABLED', {$smtpEnabled});\n"
            . "define('SMTP_HOST', '{$smtpHost}');\n"
            . "define('SMTP_PORT', {$smtpPort});\n"
            . "define('SMTP_SECURE', '{$smtpSecure}');\n"
            . "define('SMTP_USER', '{$smtpUser}');\n"
            . "define('SMTP_PASS', '{$smtpPass}');\n"
            . "define('SMTP_FROM_EMAIL', '{$smtpUser}');\n"
            . "define('SMTP_FROM_NAME', '{$smtpFromName}');\n\n"
            . "/**\n"
            . " * Socket-based Gmail SMTP Client Helper\n"
            . " */\n"
            . "function sendSmtpEmail(\$toEmail, \$subject, \$bodyHtml) {\n"
            . "    if (!SMTP_ENABLED || empty(SMTP_PASS)) return false;\n"
            . "    try {\n"
            . "        \$socket = @fsockopen(SMTP_HOST, SMTP_PORT, \$errno, \$errstr, 12);\n"
            . "        if (!\$socket) return false;\n"
            . "        \$read = function() use (\$socket) { \$s = ''; while (\$str = fgets(\$socket, 515)) { \$s .= \$str; if (substr(\$str, 3, 1) == ' ') break; } return \$s; };\n"
            . "        \$write = function(\$cmd) use (\$socket) { fputs(\$socket, \$cmd . \"\\r\\n\"); };\n"
            . "        \$read(); \$write('EHLO ' . gethostname()); \$read();\n"
            . "        if (SMTP_SECURE === 'tls') {\n"
            . "            \$write('STARTTLS'); \$read();\n"
            . "            stream_socket_enable_crypto(\$socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);\n"
            . "            \$write('EHLO ' . gethostname()); \$read();\n"
            . "        }\n"
            . "        \$write('AUTH LOGIN'); \$read();\n"
            . "        \$write(base64_encode(SMTP_USER)); \$read();\n"
            . "        \$write(base64_encode(SMTP_PASS)); \$authResp = \$read();\n"
            . "        if (substr(\$authResp, 0, 3) != '235') { fclose(\$socket); return false; }\n"
            . "        \$write('MAIL FROM: <' . SMTP_FROM_EMAIL . '>'); \$read();\n"
            . "        \$write('RCPT TO: <' . \$toEmail . '>'); \$read();\n"
            . "        \$write('DATA'); \$read();\n"
            . "        \$headers  = \"MIME-Version: 1.0\\r\\n\";\n"
            . "        \$headers .= \"Content-Type: text/html; charset=UTF-8\\r\\n\";\n"
            . "        \$headers .= \"From: \" . SMTP_FROM_NAME . \" <\" . SMTP_FROM_EMAIL . \">\\r\\n\";\n"
            . "        \$headers .= \"To: <\" . \$toEmail . \">\\r\\n\";\n"
            . "        \$headers .= \"Subject: \" . \$subject . \"\\r\\n\";\n"
            . "        \$headers .= \"Date: \" . date('r') . \"\\r\\n\";\n"
            . "        \$write(\$headers . \"\\r\\n\" . \$bodyHtml . \"\\r\\n.\"); \$read();\n"
            . "        \$write('QUIT'); fclose(\$socket); return true;\n"
            . "    } catch (Exception \$e) { return false; }\n"
            . "}\n";

        file_put_contents(__DIR__ . '/../config/mail.php', $mailPhpContent);
        $msg = "SMTP Mailer & Portal Configurations saved successfully!";
    } elseif ($action === 'send_test_email') {
        $testRecipient = sanitize($_POST['test_recipient']);
        $testSubject = "SMTP Test Connection - Dr. Subhash Placement Portal";
        $testBody = "<h3>🎉 Gmail SMTP Connection Test Successful!</h3><p>This test email confirms that your SMTP mail server settings for <strong>" . htmlspecialchars($_POST['settings_user_email'] ?? 'rupaparazeel224@gmail.com') . "</strong> are active and operational.</p><p>Sent on: " . date('Y-m-d H:i:s') . "</p>";

        $sentStatus = sendPortalEmail($testRecipient, $testSubject, $testBody, $pdo);
        if ($sentStatus) {
            $msg = "Test mail dispatched to $testRecipient! Check your inbox or system mail logs.";
        } else {
            $error = "Failed to dispatch test email. Please check your App Password or SMTP Port.";
        }
    } elseif ($action === 'change_password') {
        $oldPass = $_POST['old_password'];
        $newPass = $_POST['new_password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user && (password_verify($oldPass, $user['password']) || $oldPass === $user['password'])) {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmtUp = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUp->execute([$newHash, $_SESSION['user_id']]);
            $msg = "Admin password updated successfully!";
        } else {
            $error = "Invalid current password.";
        }
    }
}

// Fetch current settings
$settingsRows = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($settingsRows as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}

// Defaults for SMTP
$smtpEnabled = $settings['smtp_enabled'] ?? '1';
$smtpHost = $settings['smtp_host'] ?? 'smtp.gmail.com';
$smtpPort = $settings['smtp_port'] ?? '587';
$smtpSecure = $settings['smtp_secure'] ?? 'tls';
$smtpUser = $settings['smtp_user'] ?? 'mrstrome4@gmail.com';
$smtpPass = $settings['smtp_pass'] ?? (defined('SMTP_PASS') && !empty(SMTP_PASS) ? SMTP_PASS : 'Zeel_patel@224');
$smtpFromName = $settings['smtp_from_name'] ?? 'Dr. Subhash Placement Cell';

$pageTitle = 'Portal Settings & SMTP Mailer';
$currentPage = 'settings';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Banner -->
<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Portal Settings & Gmail SMTP Server</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Manage institution identity, Gmail SMTP credentials, mail dispatcher controls, and admin security.</p>
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

<div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px;">
    
    <!-- LEFT CARD: PORTAL BRANDING & SMTP CONFIGURATION -->
    <div class="table-card" style="padding: 26px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <h4 style="font-weight: 800; font-size: 17px; color: #0F172A; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            🏢 Institution Identity & Branding
        </h4>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Institution Portal Name *</label>
                    <input type="text" name="settings[portal_name]" class="form-control" value="<?= htmlspecialchars($settings['portal_name'] ?? 'Dr. Subhash Placement Portal'); ?>" required style="border-radius: 12px; padding: 10px 14px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Official Contact Email *</label>
                    <input type="email" name="settings[contact_email]" class="form-control" value="<?= htmlspecialchars($settings['contact_email'] ?? 'placements@drsubhash.edu.in'); ?>" required style="border-radius: 12px; padding: 10px 14px;">
                </div>
            </div>

            <hr style="border: 0; border-top: 1px dashed #CBD5E1; margin: 24px 0;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h4 style="font-weight: 800; font-size: 17px; color: #2563EB; display: flex; align-items: center; gap: 8px;">
                    ✉️ Gmail SMTP Server Configuration
                </h4>
                <div style="display: flex; align-items: center; gap: 8px; background: #EFF6FF; padding: 6px 14px; border-radius: 12px; border: 1px solid #BFDBFE;">
                    <input type="checkbox" id="smtpToggle" name="settings[smtp_enabled]" value="1" <?= ($smtpEnabled === '1') ? 'checked' : ''; ?> style="width: 16px; height: 16px; cursor: pointer;">
                    <label for="smtpToggle" style="font-size: 13px; font-weight: 800; color: #1D4ED8; cursor: pointer; margin: 0;">Enable SMTP Dispatch</label>
                </div>
            </div>

            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 20px; border-radius: 16px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: block;">SMTP Host Server *</label>
                        <input type="text" name="settings[smtp_host]" class="form-control" value="<?= htmlspecialchars($smtpHost); ?>" required style="border-radius: 10px; padding: 9px 12px;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: block;">SMTP Port *</label>
                        <input type="number" name="settings[smtp_port]" class="form-control" value="<?= htmlspecialchars($smtpPort); ?>" required style="border-radius: 10px; padding: 9px 12px;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: block;">Protocol *</label>
                        <select name="settings[smtp_secure]" class="select-pill" style="width: 100%; border-radius: 10px; padding: 9px 12px; font-weight: 700;">
                            <option value="tls" <?= ($smtpSecure === 'tls') ? 'selected' : ''; ?>>TLS (587)</option>
                            <option value="ssl" <?= ($smtpSecure === 'ssl') ? 'selected' : ''; ?>>SSL (465)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: block;">Sender Gmail Address *</label>
                        <input type="email" name="settings[smtp_user]" class="form-control" value="<?= htmlspecialchars($smtpUser); ?>" required style="border-radius: 10px; padding: 9px 12px; font-weight: 700; color: #2563EB;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: block;">Sender Display Name *</label>
                        <input type="text" name="settings[smtp_from_name]" class="form-control" value="<?= htmlspecialchars($smtpFromName); ?>" required style="border-radius: 10px; padding: 9px 12px;">
                    </div>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
                        <span>Google 16-Digit App Password *</span>
                        <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: #2563EB; font-size: 11px; font-weight: 700; text-decoration: none;">Get App Password ↗</a>
                    </label>
                    <div style="position: relative;">
                        <input type="password" id="smtpPassInput" name="settings[smtp_pass]" class="form-control" value="<?= htmlspecialchars($smtpPass); ?>" placeholder="e.g. abcd efgh ijkl mnop" style="border-radius: 10px; padding: 9px 40px 9px 12px; font-weight: 700; letter-spacing: 1px;">
                        <button type="button" onclick="togglePassVisibility()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748B; font-size: 14px;" title="Toggle Password Visibility">👁️</button>
                    </div>
                    <span style="font-size: 11px; color: #64748B; margin-top: 4px; display: block;">Generate a 16-character code at Google Account Security -> App Passwords.</span>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="border-radius: 12px; padding: 12px 24px; font-weight: 800; font-size: 14px; width: 100%; justify-content: center; box-shadow: 0 4px 14px rgba(59,130,246,0.3);">
                💾 Save SMTP & Portal Configurations
            </button>
        </form>
    </div>

    <!-- RIGHT CARDS: TEST MAILER & SECURITY -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- CARD 1: TEST EMAIL DISPATCHER -->
        <div class="table-card" style="padding: 24px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
            <h4 style="font-weight: 800; font-size: 16px; color: #0F172A; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                🧪 Test SMTP Dispatcher
            </h4>
            <p style="font-size: 12px; color: #64748B; margin-bottom: 14px;">Send a live test mail to verify connection to Google Mail Servers.</p>

            <form method="POST">
                <input type="hidden" name="action" value="send_test_email">
                <input type="hidden" name="settings_user_email" value="<?= htmlspecialchars($smtpUser); ?>">
                
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: block;">Recipient Email *</label>
                    <input type="email" name="test_recipient" class="form-control" value="<?= htmlspecialchars($smtpUser); ?>" required style="border-radius: 10px; padding: 9px 12px; font-weight: 700;">
                </div>
                
                <button type="submit" class="btn-secondary" style="width: 100%; justify-content: center; border-radius: 10px; padding: 10px; font-weight: 800; background: #F1F5F9; color: #0F172A; border: 1px solid #CBD5E1;">
                    ⚡ Dispatch Test Email Now
                </button>
            </form>
        </div>

        <!-- CARD 2: UPDATE ADMIN PASSWORD -->
        <div class="table-card" style="padding: 24px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
            <h4 style="font-weight: 800; font-size: 16px; color: #0F172A; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                🔒 Update Admin Security
            </h4>
            
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: block;">Current Password *</label>
                    <input type="password" name="old_password" class="form-control" required style="border-radius: 10px; padding: 9px 12px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="font-weight: 700; font-size: 12px; color: #475569; margin-bottom: 4px; display: block;">New Password *</label>
                    <input type="password" name="new_password" class="form-control" required style="border-radius: 10px; padding: 9px 12px;">
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 10px; padding: 10px; font-weight: 800;">
                    Update Security Password
                </button>
            </form>
        </div>

    </div>
</div>

<script>
function togglePassVisibility() {
    const input = document.getElementById('smtpPassInput');
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
