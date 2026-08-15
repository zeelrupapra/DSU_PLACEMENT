<?php
require_once __DIR__ . '/../config/db.php';
requireStudent();

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];

// Fetch Logged-in Student details
$stmtSt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmtSt->execute([$studentId]);
$student = $stmtSt->fetch();

if (!$student) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// 1. Fetch ALL Student's Drive Application & Booking Records
$stmtMyApps = $pdo->prepare("SELECT a.*, d.title, d.designation, d.package_ctc, d.location, d.drive_date, d.status as drive_status, c.company_name, c.industry 
                             FROM applications a 
                             JOIN drives d ON a.drive_id = d.id 
                             JOIN companies c ON d.company_id = c.id 
                             WHERE a.student_id = ? 
                             ORDER BY a.id DESC");
$stmtMyApps->execute([$studentId]);
$myApplicationsHistory = $stmtMyApps->fetchAll();

// 2. Fetch ALL Student's Workshop & Seminar Event Bookings
$stmtMyWorkshops = $pdo->prepare("SELECT wr.*, w.title as workshop_title, w.speaker, w.venue, w.event_date, w.description 
                                  FROM workshop_registrations wr 
                                  JOIN workshops w ON wr.workshop_id = w.id 
                                  WHERE wr.student_id = ? 
                                  ORDER BY wr.id DESC");
$stmtMyWorkshops->execute([$studentId]);
$myWorkshopBookings = $stmtMyWorkshops->fetchAll();

// 3. Fetch ALL Archived / Past Campus Recruitment Drives Across University
$stmtPastDrives = $pdo->query("SELECT d.*, c.company_name, c.industry 
                               FROM drives d 
                               JOIN companies c ON d.company_id = c.id 
                               WHERE d.status IN ('Completed', 'Cancelled') OR d.deadline < CURDATE() 
                               ORDER BY d.drive_date DESC");
$pastDrives = $stmtPastDrives->fetchAll();

// Active tab query parameter ('my_drives', 'workshops', 'archived')
$activeTab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'my_drives';
$searchQ = isset($_GET['q']) ? sanitize($_GET['q']) : '';

$pageTitle = 'Previous Bookings & Application Records';
$currentPage = 'drives_history';
include __DIR__ . '/../includes/header.php';
?>

<!-- Executive Banner Header -->
<div style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; padding: 28px 32px; border-radius: 24px; margin-bottom: 28px; box-shadow: 0 12px 30px -10px rgba(15, 23, 42, 0.3);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h3 style="font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.5px; margin-bottom: 6px;">
                📜 Previous Bookings & Application History
            </h3>
            <p style="font-size: 13px; color: #94A3B8; font-weight: 600;">
                Comprehensive record of all your campus drive applications, workshop event bookings, and past recruitment history.
            </p>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); padding: 10px 18px; border-radius: 14px; text-align: center;">
                <span style="font-size: 11px; color: #94A3B8; font-weight: 700; text-transform: uppercase; display: block;">Applied Drives</span>
                <strong style="font-size: 16px; color: #60A5FA; font-weight: 800;"><?= count($myApplicationsHistory); ?></strong>
            </div>

            <div style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); padding: 10px 18px; border-radius: 14px; text-align: center;">
                <span style="font-size: 11px; color: #94A3B8; font-weight: 700; text-transform: uppercase; display: block;">Event Bookings</span>
                <strong style="font-size: 16px; color: #34D399; font-weight: 800;"><?= count($myWorkshopBookings); ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigation & Live Search Bar -->
<div class="table-card" style="padding: 16px 24px; margin-bottom: 28px; border-radius: 20px; background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.02);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; background: #F1F5F9; border-radius: 14px; padding: 4px;">
            <a href="drives_history.php?tab=my_drives" class="btn-action-icon" style="border: none; padding: 9px 18px; font-weight: 800; border-radius: 10px; <?= ($activeTab==='my_drives')?'background:white; color:#2563EB; box-shadow:0 4px 10px rgba(0,0,0,0.06);':'background:transparent; color:#64748B;'; ?>">
                💼 My Drive Applications (<?= count($myApplicationsHistory); ?>)
            </a>
            <a href="drives_history.php?tab=workshops" class="btn-action-icon" style="border: none; padding: 9px 18px; font-weight: 800; border-radius: 10px; <?= ($activeTab==='workshops')?'background:white; color:#2563EB; box-shadow:0 4px 10px rgba(0,0,0,0.06);':'background:transparent; color:#64748B;'; ?>">
                🎓 My Workshop Bookings (<?= count($myWorkshopBookings); ?>)
            </a>
            <a href="drives_history.php?tab=archived" class="btn-action-icon" style="border: none; padding: 9px 18px; font-weight: 800; border-radius: 10px; <?= ($activeTab==='archived')?'background:white; color:#2563EB; box-shadow:0 4px 10px rgba(0,0,0,0.06);':'background:transparent; color:#64748B;'; ?>">
                📜 Archived Campus Drives (<?= count($pastDrives); ?>)
            </a>
        </div>
    </div>
</div>

<!-- TAB CONTENT 1: MY DRIVE APPLICATION BOOKINGS -->
<?php if ($activeTab === 'my_drives'): ?>
    <div class="table-card" style="border-radius: 24px; padding: 24px; background: white; border: 1px solid #E2E8F0; box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h4 style="font-size: 18px; font-weight: 800; color: #0F172A;">💼 My Campus Recruitment Application Records</h4>
                <p style="font-size: 13px; color: #64748B;">Complete log of placement drives you applied for, along with live selection pipeline status.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table" style="width: 100%;">
                <thead>
                    <tr style="background: #F8FAFC;">
                        <th style="padding: 14px 18px;">Company & Industry</th>
                        <th style="padding: 14px 18px;">Role Designation</th>
                        <th style="padding: 14px 18px;">Package CTC</th>
                        <th style="padding: 14px 18px;">Application Date</th>
                        <th style="padding: 14px 18px; text-align: right;">Pipeline Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($myApplicationsHistory)): ?>
                        <?php foreach ($myApplicationsHistory as $mah): 
                            $stClass = ($mah['status']==='Selected') ? 'placed' : (($mah['status']==='Rejected') ? 'unplaced' : 'in-process'); 
                        ?>
                            <tr style="background: #FFFFFF; border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 16px 18px;">
                                    <strong style="font-size: 14px; color: #0F172A;">🏢 <?= htmlspecialchars($mah['company_name']); ?></strong><br>
                                    <span style="font-size: 11px; color: #64748B; font-weight: 600;"><?= htmlspecialchars($mah['industry'] ?: 'Technology'); ?></span>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <strong style="font-size: 13.5px; color: #334155;"><?= htmlspecialchars($mah['title']); ?></strong><br>
                                    <span style="font-size: 12px; color: #2563EB; font-weight: 700;"><?= htmlspecialchars($mah['designation']); ?></span>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <strong style="font-size: 14px; color: #10B981; background: #ECFDF5; padding: 4px 10px; border-radius: 8px; border: 1px solid #A7F3D0; display: inline-block;">
                                        💰 <?= htmlspecialchars($mah['package_ctc']); ?>
                                    </strong>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <span style="font-size: 12.5px; color: #475569; font-weight: 600;">
                                        ⏰ <?= date('d M Y, h:i A', strtotime($mah['applied_date'])); ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 18px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                        <span class="badge-status <?= $stClass; ?>" style="font-size: 13px; font-weight: 800; padding: 8px 16px; border-radius: 12px;">
                                            <?= htmlspecialchars($mah['status']); ?>
                                        </span>
                                        <?php if ($mah['status'] === 'Selected' && !empty($student['offer_letter'])): ?>
                                            <a href="../view_offer.php?file=<?= urlencode($student['offer_letter']); ?>" target="_blank" class="btn-primary" style="padding: 6px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; background: #059669; border-color: #059669; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                📜 View Offer ↗
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #64748B;">
                                <div style="font-size: 32px; margin-bottom: 8px;">💼</div>
                                <strong style="font-size: 15px; color: #0F172A; display: block;">No Application Bookings Found</strong>
                                You have not submitted any drive applications yet. Explore <a href="drives.php" style="color: #2563EB; font-weight: 700;">Current Openings</a> to apply.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- TAB CONTENT 2: MY WORKSHOP & SEMINAR EVENT BOOKINGS -->
<?php elseif ($activeTab === 'workshops'): ?>
    <div class="table-card" style="border-radius: 24px; padding: 24px; background: white; border: 1px solid #E2E8F0; box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h4 style="font-size: 18px; font-weight: 800; color: #0F172A;">🎓 My Workshop & Seminar Event Bookings</h4>
                <p style="font-size: 13px; color: #64748B;">List of all expert skill bootcamps and technical seminars registered by you.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table" style="width: 100%;">
                <thead>
                    <tr style="background: #F8FAFC;">
                        <th style="padding: 14px 18px;">Workshop & Topic</th>
                        <th style="padding: 14px 18px;">Keynote Speaker</th>
                        <th style="padding: 14px 18px;">Venue & Event Schedule</th>
                        <th style="padding: 14px 18px;">Registration Timestamp</th>
                        <th style="padding: 14px 18px; text-align: right;">Attendance Record</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($myWorkshopBookings)): ?>
                        <?php foreach ($myWorkshopBookings as $mwb): 
                            $attSt = $mwb['attendance_status'] ?: 'Registered';
                            $attCls = ($attSt === 'Attended') ? 'placed' : (($attSt === 'Absent') ? 'unplaced' : 'in-process');
                        ?>
                            <tr style="background: #FFFFFF; border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 16px 18px;">
                                    <strong style="font-size: 14px; color: #0F172A;">🎓 <?= htmlspecialchars($mwb['workshop_title']); ?></strong><br>
                                    <span class="branch-badge" style="font-size: 11px; margin-top: 4px; display: inline-block;">Skill Workshop & Bootcamp</span>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <span style="font-size: 13px; color: #2563EB; font-weight: 700;">🎤 <?= htmlspecialchars($mwb['speaker']); ?></span>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <span style="font-size: 12.5px; color: #334155; font-weight: 600;">📍 <?= htmlspecialchars($mwb['venue']); ?></span><br>
                                    <span style="font-size: 12px; color: #64748B;">🕒 <?= date('d M Y, h:i A', strtotime($mwb['event_date'])); ?></span>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <span style="font-size: 12.5px; color: #475569; font-weight: 600;">
                                        ⏰ <?= date('d M Y, h:i A', strtotime($mwb['registered_at'])); ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 18px; text-align: right;">
                                    <span class="badge-status <?= $attCls; ?>" style="font-size: 13px; font-weight: 800; padding: 8px 16px; border-radius: 12px;">
                                        <?= htmlspecialchars($attSt); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #64748B;">
                                <div style="font-size: 32px; margin-bottom: 8px;">🎓</div>
                                <strong style="font-size: 15px; color: #0F172A; display: block;">No Event Bookings Found</strong>
                                You have not registered for any workshop events yet. View <a href="workshops.php" style="color: #2563EB; font-weight: 700;">Workshops & Events</a> to register.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- TAB CONTENT 3: ARCHIVED CAMPUS RECRUITMENT DRIVES ACROSS UNIVERSITY -->
<?php else: ?>
    <div class="table-card" style="border-radius: 24px; padding: 24px; background: white; border: 1px solid #E2E8F0; box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h4 style="font-size: 18px; font-weight: 800; color: #0F172A;">📜 Archived Campus Recruitment Drives</h4>
                <p style="font-size: 13px; color: #64748B;">Past recruitment drives conducted by Dr. Subhash University Corporate Relations Cell.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table" style="width: 100%;">
                <thead>
                    <tr style="background: #F8FAFC;">
                        <th style="padding: 14px 18px;">Company Name</th>
                        <th style="padding: 14px 18px;">Drive Title & Designation</th>
                        <th style="padding: 14px 18px;">Package CTC</th>
                        <th style="padding: 14px 18px;">Drive Date</th>
                        <th style="padding: 14px 18px; text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pastDrives)): ?>
                        <?php foreach ($pastDrives as $pd): ?>
                            <tr style="background: #FFFFFF; border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 16px 18px;">
                                    <strong style="font-size: 14px; color: #0F172A;">🏢 <?= htmlspecialchars($pd['company_name']); ?></strong><br>
                                    <span style="font-size: 11px; color: #64748B; font-weight: 600;"><?= htmlspecialchars($pd['industry'] ?: 'IT / Tech'); ?></span>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <strong style="font-size: 13.5px; color: #334155;"><?= htmlspecialchars($pd['title']); ?></strong><br>
                                    <span style="font-size: 12px; color: #2563EB; font-weight: 700;"><?= htmlspecialchars($pd['designation']); ?></span>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <strong style="font-size: 14px; color: #10B981; background: #ECFDF5; padding: 4px 10px; border-radius: 8px; border: 1px solid #A7F3D0; display: inline-block;">
                                        💰 <?= htmlspecialchars($pd['package_ctc']); ?>
                                    </strong>
                                </td>
                                <td style="padding: 16px 18px;">
                                    <span style="font-size: 12.5px; color: #475569; font-weight: 600;">
                                        📅 <?= date('d M Y', strtotime($pd['drive_date'])); ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 18px; text-align: right;">
                                    <span class="badge-status in-process" style="background:#F1F5F9; color:#475569; font-size: 12.5px; font-weight: 800; padding: 6px 14px; border-radius: 10px;">
                                        Closed Drive
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: #64748B;">No past drive records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
