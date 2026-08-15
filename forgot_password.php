<?php
require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $stmtUpdate = $pdo->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
        $stmtUpdate->execute([$token, $user['id']]);

        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/DSU/forgot_password.php?token=" . $token;
        $body = "<h2>Password Reset Request</h2><p>Hello, click the link below to reset your Dr. Subhash Placement Portal password:</p><p><a href='$resetLink'>$resetLink</a></p>";
        sendPortalEmail($email, "Password Reset Link - Dr. Subhash Placement Portal", $body, $pdo);

        $message = "Password reset instructions sent to your registered email.";
    } else {
        $error = "No registered account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Dr. Subhash University</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #F8FAFC; 
            color: #0F172A; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 24px; 
            background-image: radial-gradient(#E2E8F0 1.5px, transparent 1.5px); 
            background-size: 32px 32px; 
        }

        .card { 
            background: #FFFFFF; 
            border: 1px solid #E2E8F0; 
            border-radius: 28px; 
            width: 100%; 
            max-width: 440px; 
            padding: 40px; 
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07); 
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-wrapper { text-align: center; margin-bottom: 24px; }
        .logo-wrapper img { height: 65px; object-fit: contain; margin-bottom: 12px; }
        .logo-wrapper h2 { font-size: 20px; font-weight: 800; color: #0F172A; }
        .logo-wrapper p { font-size: 13px; color: #64748B; margin-top: 4px; font-weight: 600; }

        .form-control { 
            width: 100%; 
            padding: 14px 18px; 
            background: #FFFFFF; 
            border: 1px solid #CBD5E1; 
            border-radius: 12px; 
            color: #0F172A; 
            font-size: 14px; 
            margin-top: 6px; 
            margin-bottom: 20px; 
            outline: none; 
            transition: all 0.2s; 
        }
        .form-control:focus { border-color: #3B82F6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12); }

        .btn { 
            display: inline-block; 
            width: 100%; 
            padding: 14px; 
            background: #3B82F6; 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: 800; 
            font-size: 15px; 
            cursor: pointer; 
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25); 
            transition: all 0.25s; 
        }
        .btn:hover { background: #2563EB; transform: translateY(-2px); }

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; }
        .alert-success { background: #DCFCE7; color: #15803D; border: 1px solid #86EFAC; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-wrapper">
        <img src="assets/images/logo.png" alt="University Logo">
        <h2>Reset Password</h2>
        <p>Dr. Subhash University Placement Portal</p>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message); ?></div><?php endif; ?>

    <form method="POST">
        <label style="font-size: 13px; font-weight: 700; color: #334155;">Registered Student/Admin Email</label>
        <input type="email" name="email" class="form-control" placeholder="name@drsubhash.edu.in" required>
        <button type="submit" class="btn">Send Recovery Reset Link</button>
    </form>
    
    <div style="margin-top: 24px; text-align: center;">
        <a href="index.php" style="color: #3B82F6; text-decoration: none; font-size: 13px; font-weight: 700;">← Back to Sign In</a>
    </div>
</div>
</body>
</html>
