<?php
require_once __DIR__ . '/config/db.php';

// Session Auto-Redirect Handler for Already Authenticated Users
if (isAuthenticated()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
        exit;
    } elseif (isStudent()) {
        header("Location: student/dashboard.php");
        exit;
    } else {
        // Destroy corrupted session states
        session_unset();
        session_destroy();
    }
}

$popupError = '';
$rawError = isset($_GET['error']) ? sanitize($_GET['error']) : '';
if ($rawError === 'unauthorized') {
    $popupError = 'Unauthorized Access! Please sign in to your account first.';
} elseif ($rawError) {
    $popupError = $rawError;
}

$successMsg = isset($_GET['success']) ? sanitize($_GET['success']) : '';

// Process Common Unified Single Sign-On Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier']);
    $password = $_POST['password'];

    $pdo = getDBConnection();

    // 1. Attempt Admin Authentication
    $stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE (username = ?) AND role = 'admin'");
    $stmtAdmin->execute([$identifier]);
    $adminUser = $stmtAdmin->fetch();

    if ($adminUser && (password_verify($password, $adminUser['password']) || $password === $adminUser['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $adminUser['id'];
        $_SESSION['username'] = 'Placement Officer';
        $_SESSION['email'] = $adminUser['username'];
        $_SESSION['role'] = 'admin';
        header("Location: admin/dashboard.php");
        exit;
    }

    // 2. Attempt Student Authentication
    $stmtStudent = $pdo->prepare("SELECT u.*, s.id as student_id, s.full_name, s.enrollment_no, s.email as student_email, s.is_detained 
                                  FROM users u 
                                  JOIN students s ON u.id = s.user_id 
                                  WHERE (s.enrollment_no = ? OR s.email = ? OR u.username = ?) AND u.role = 'student'");
    $stmtStudent->execute([$identifier, $identifier, $identifier]);
    $studentUser = $stmtStudent->fetch();

    if ($studentUser && (password_verify($password, $studentUser['password']) || $password === $studentUser['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $studentUser['id'];
        $_SESSION['student_id'] = $studentUser['student_id'];
        $_SESSION['username'] = $studentUser['full_name'];
        $_SESSION['enrollment_no'] = $studentUser['enrollment_no'];
        $_SESSION['email'] = $studentUser['student_email'];
        $_SESSION['role'] = 'student';
        $_SESSION['is_detained'] = $studentUser['is_detained'];
        header("Location: student/dashboard.php");
        exit;
    }

    // If credentials failed for both Admin and Student
    $popupError = "Invalid User Credentials or Password. Please verify your details.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Dr. Subhash University - Placement Portal Single Sign-On</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #090D16; 
            color: #F8FAFC; 
            height: 100vh !important; 
            max-height: 100vh !important;
            width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            position: relative;
        }

        /* Ambient Glow Background Circles */
        .ambient-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.35;
            pointer-events: none;
            z-index: 1;
        }
        .glow-1 { top: -10%; left: -10%; width: 600px; height: 600px; background: #3B82F6; }
        .glow-2 { bottom: -10%; right: -10%; width: 600px; height: 600px; background: #8B5CF6; }

        /* Full Screen Common Login Layout */
        .login-wrapper-fullscreen {
            position: relative;
            z-index: 2;
            width: 100vw;
            height: 100vh;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            overflow: hidden;
        }

        /* Left Executive Hero Panel */
        .login-hero {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.92) 100%), url('assets/images/university_bg.jpg') center/cover;
            padding: 64px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .university-badge {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .university-badge img {
            height: 65px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 14px rgba(59, 130, 246, 0.45));
        }

        .hero-title {
            font-size: 36px;
            font-weight: 900;
            line-height: 1.25;
            color: #FFFFFF;
            margin-top: 40px;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #FFFFFF 0%, #CBD5E1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 15px;
            color: #94A3B8;
            line-height: 1.6;
            font-weight: 600;
            margin-bottom: 40px;
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 16px 20px;
            border-radius: 18px;
            backdrop-filter: blur(10px);
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(59, 130, 246, 0.15);
            color: #38BDF8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .feature-text strong {
            font-size: 14px;
            color: #F8FAFC;
            display: block;
        }

        .feature-text span {
            font-size: 12.5px;
            color: #94A3B8;
        }

        .hero-footer {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 13px;
            color: #64748B;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        /* Right Clean Professional Form Panel */
        .login-form-box {
            padding: 64px 56px;
            background: #FFFFFF;
            color: #0F172A;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .form-header {
            margin-bottom: 32px;
        }

        .form-header h3 {
            font-size: 28px;
            font-weight: 900;
            color: #0F172A;
            letter-spacing: -0.5px;
        }

        .form-header p {
            font-size: 14px;
            color: #64748B;
            margin-top: 6px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 13.5px;
            font-weight: 800;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .input-icon-wrapper .toggle-pwd-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            color: #475569;
            cursor: pointer;
            padding: 7px 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .input-icon-wrapper .toggle-pwd-btn:hover {
            background: #E2E8F0;
            color: #2563EB;
        }

        .form-control-custom {
            width: 100%;
            padding: 16px 50px 16px 50px;
            background: #F8FAFC;
            border: 1.5px solid #E2E8F0;
            border-radius: 16px;
            color: #0F172A;
            font-size: 14.5px;
            font-weight: 700;
            outline: none;
            transition: all 0.25s ease;
        }

        .form-control-custom:focus {
            background: #FFFFFF;
            border-color: #2563EB;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .submit-btn-custom {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            color: #FFFFFF;
            border: none;
            border-radius: 16px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 14px;
        }

        .submit-btn-custom:hover {
            background: linear-gradient(135deg, #1D4ED8 0%, #1E40AF 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.4);
        }

        /* Responsive Breakpoints */
        @media (max-width: 960px) {
            .login-wrapper-fullscreen {
                grid-template-columns: 1fr;
            }
            .login-hero {
                display: none;
            }
            .login-form-box {
                padding: 40px 24px;
            }
        }

        /* Floating Error Modal Backdrop */
        .modal-backdrop {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }

        .error-popup {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 420px;
            padding: 32px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .modal-backdrop.active .error-popup {
            transform: scale(1);
        }

        .popup-icon-badge {
            width: 64px;
            height: 64px;
            background: #FEE2E2;
            color: #DC2626;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.2);
        }
    </style>
</head>
<body>

<div class="ambient-glow glow-1"></div>
<div class="ambient-glow glow-2"></div>

<div class="login-wrapper-fullscreen">
    <!-- Left Executive Hero Showcase -->
    <div class="login-hero">
        <div>
            <div class="university-badge">
                <img src="assets/images/logo.png" alt="Dr. Subhash University Logo">
                <div>
                    <h4 style="font-size: 17px; font-weight: 900; color: #FFFFFF;">DR. SUBHASH UNIVERSITY</h4>
                    <span style="font-size: 12px; color: #38BDF8; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Training & Placement Cell</span>
                </div>
            </div>

            <h1 class="hero-title">Empowering Careers, Shaping Tomorrow.</h1>
            <p class="hero-subtitle">Unified single sign-on access portal for University Administrators, Training Officers, and Student Candidates.</p>

            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon">🚀</div>
                    <div class="feature-text">
                        <strong>Campus Recruitment Drives</strong>
                        <span>1-Click candidate applications & live pipeline tracking</span>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🏢</div>
                    <div class="feature-text">
                        <strong>Tech Industry Partners</strong>
                        <span>Direct dispatches to Google, Microsoft, AWS, TCS & L&T</span>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📜</div>
                    <div class="feature-text">
                        <strong>Industrial Internships</strong>
                        <span>Verified completion dossiers & stipend management</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-footer">
            <span>© <?= date('Y'); ?> Dr. Subhash University</span>
            <span>Placement Officer Portal Support</span>
        </div>
    </div>

    <!-- Right Unified Form Panel (Common Sign-In for Admin & Student) -->
    <div class="login-form-box">
        <div style="max-width: 440px; margin: 0 auto; width: 100%;">
            <div class="form-header">
                <h3>Placement Portal Sign In</h3>
                <p>Enter your University Email, Username, or Enrollment Code to continue.</p>
            </div>

            <form method="POST" action="index.php" id="loginForm">
                <div class="form-group">
                    <label>Username / Email / Enrollment Code *</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" name="identifier" class="form-control-custom" placeholder="Enter username, email, or enrollment code" required autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="margin: 0;">Password *</label>
                        <a href="forgot_password.php" style="font-size: 12.5px; font-weight: 700; color: #2563EB; text-decoration: none;">Forgot Password?</a>
                    </div>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" name="password" id="passwordInput" class="form-control-custom" placeholder="Enter your account password" required autocomplete="current-password">
                        <button type="button" class="toggle-pwd-btn" onclick="togglePasswordVisibility()" id="togglePwdBtn" title="Toggle Password Visibility">
                            <!-- Professional SVG Eye Icon -->
                            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn-custom">
                    <span>Sign In to Placement Portal</span>
                    <span style="font-size: 16px;">➔</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Popup for Unauthorized Access / Credentials Errors -->
<div class="modal-backdrop <?= $popupError ? 'active' : ''; ?>" id="errorModal">
    <div class="error-popup">
        <div class="popup-icon-badge">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <h3 style="font-size: 20px; font-weight: 900; color: #0F172A; margin-bottom: 8px;">Authentication Notice</h3>
        <p style="font-size: 14px; color: #64748B; margin-bottom: 24px; line-height: 1.5; font-weight: 600;" id="popupMessageText"><?= htmlspecialchars($popupError); ?></p>
        <button style="width: 100%; padding: 14px; background: #DC2626; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(220,38,38,0.3);" onclick="closeErrorPopup()">Try Again</button>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($successMsg): ?>
        showToast('Success Notification', '<?= htmlspecialchars($successMsg); ?>', 'success');
    <?php endif; ?>
});

function closeErrorPopup() {
    const modal = document.getElementById('errorModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function togglePasswordVisibility() {
    const pwdInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        // Eye-Off Icon SVG
        eyeIcon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
        pwdInput.type = 'password';
        // Eye Open Icon SVG
        eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    }
}
</script>
</body>
</html>
