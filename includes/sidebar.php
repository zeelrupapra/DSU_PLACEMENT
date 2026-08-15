<?php
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/db.php';
}

$page = isset($currentPage) ? $currentPage : 'dashboard';
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'student';
$userName = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$userEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';

// Get user profile photo
$userPhoto = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($userName);
if (isStudent() && isset($_SESSION['student_id'])) {
    $pdo = getDBConnection();
    $stData = $pdo->query("SELECT profile_photo, gender, full_name FROM students WHERE id = " . intval($_SESSION['student_id']))->fetch();
    if ($stData) {
        if ($stData['profile_photo'] && $stData['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $stData['profile_photo'])) {
            $userPhoto = '../uploads/' . $stData['profile_photo'];
        } else {
            $genderSeed = ($stData['gender'] === 'Female') ? 'female' : 'male';
            $userPhoto = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($stData['full_name']) . '&gender=' . $genderSeed;
        }
    }
}
?>
<aside class="sidebar">
    <div>
        <div class="sidebar-header" style="gap: 14px;">
            <img src="../assets/images/logo.png" alt="University Logo" style="height: 48px; object-fit: contain;">
            <div class="logo-text">
                <h1 style="font-size: 15px; font-weight: 800;">Dr. Subhash</h1>
                <p style="color: #3B82F6; font-weight: 700;">Placement Portal</p>
            </div>
        </div>

        <ul class="nav-menu">
            <?php if ($userRole === 'admin'): ?>
                <li class="nav-item <?= ($page === 'dashboard') ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Dashboard
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'manage_students') ? 'active' : ''; ?>">
                    <a href="manage_students.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M16 11l2 2 4-4"/></svg>
                            Students
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'students') ? 'active' : ''; ?>">
                    <a href="students.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Placement Details
                        </span>
                    </a>
                </li>
                <!-- CONSOLIDATED COMPANIES & OPENINGS -->
                <li class="nav-item <?= ($page === 'companies' || $page === 'drives' || $page === 'drives_history') ? 'active' : ''; ?>">
                    <a href="companies.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            Companies & Openings
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'workshops') ? 'active' : ''; ?>">
                    <a href="workshops.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            Workshop & Seminar
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'reports') ? 'active' : ''; ?>">
                    <a href="reports.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            Reports & Analytics
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'internships') ? 'active' : ''; ?>">
                    <a href="internships.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            Internships
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'mou') ? 'active' : ''; ?>">
                    <a href="mou.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                            MOU
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'mail_logs') ? 'active' : ''; ?>">
                    <a href="mail_logs.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            Mail Outbox & Logs
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'settings') ? 'active' : ''; ?>">
                    <a href="settings.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                            Settings
                        </span>
                    </a>
                </li>
            <?php else: ?>
                <!-- Student Menu -->
                <li class="nav-item <?= ($page === 'dashboard') ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Student Dashboard
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'profile') ? 'active' : ''; ?>">
                    <a href="profile.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            My Profile & Resume
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'drives') ? 'active' : ''; ?>">
                    <a href="drives.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                            Current Openings
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'drives_history') ? 'active' : ''; ?>">
                    <a href="drives_history.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Previous Openings (Closed)
                        </span>
                    </a>
                </li>
                <li class="nav-item <?= ($page === 'internships') ? 'active' : ''; ?>">
                    <a href="internships.php">
                        <span class="left-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            My Internships
                        </span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="sidebar-user">
        <img src="<?= $userPhoto; ?>" alt="User Photo">
        <div class="user-info">
            <h4><?= htmlspecialchars($userName); ?></h4>
            <p><?= ($userRole === 'admin') ? 'Placement Officer' : 'Student Candidate'; ?></p>
        </div>
        <a href="../logout.php" title="Logout" style="color: #94A3B8;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
    </div>
</aside>
