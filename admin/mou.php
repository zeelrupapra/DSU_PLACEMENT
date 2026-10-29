<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();
$msg = '';
$error = '';

// Download official CSV template
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=mou_import_template.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Company Name', 'Signed Date', 'Expiry Date', 'Scope', 'Status', 'Region', 'Reason', 'MOU Document']);
    fputcsv($output, [
        'Tata Consultancy Services (TCS)',
        '2026-01-15',
        '2029-01-14',
        'Campus Recruitment & Center of Excellence',
        'Active',
        'Gujarat',
        'Priority Campus Recruitment & NQT Hiring',
        'mou_sample.pdf'
    ]);
    fputcsv($output, [
        'Infosys Limited',
        '2026-02-01',
        '2029-01-31',
        'Springboard Learning & Skill Development',
        'Active',
        'Saurashtra',
        'Industry Faculty Enablement & Direct Hiring',
        'mou_sample.pdf'
    ]);
    fputcsv($output, [
        'L&T Technology Services',
        '2025-08-10',
        '2028-08-09',
        'Core Engineering Lab Sponsorship & Internships',
        'Active',
        'Pan India',
        'Research Lab Setup and Student Training',
        'mou_sample.pdf'
    ]);
    fclose($output);
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

    if ($action === 'create_mou') {
        $compName = sanitize($_POST['company_name']);
        $signedDate = sanitize($_POST['signed_date']);
        $expiryDate = sanitize($_POST['expiry_date']);
        $region = !empty($_POST['region']) ? sanitize($_POST['region']) : 'Gujarat';
        $reason = !empty($_POST['reason']) ? sanitize($_POST['reason']) : '';
        $scope = sanitize($_POST['scope']);
        $status = sanitize($_POST['status']);
        $mouDoc = null;

        // Document upload validation (pdf, doc, docx only)
        if (isset($_FILES['mou_document']) && $_FILES['mou_document']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['mou_document']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'doc', 'docx'];
            if (in_array($ext, $allowedExts)) {
                $cleanSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($compName));
                $docName = 'mou_' . $cleanSlug . '_' . time() . '.' . $ext;
                $targetPath = __DIR__ . '/../uploads/' . $docName;
                if (move_uploaded_file($_FILES['mou_document']['tmp_name'], $targetPath)) {
                    $mouDoc = $docName;
                } else {
                    $error = "Failed to upload MOU document to server.";
                }
            } else {
                $error = "Invalid document format! Only PDF, DOC, or DOCX documents are allowed.";
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO mous (company_name, signed_date, expiry_date, scope, status, region, reason, mou_document) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$compName, $signedDate, $expiryDate, $scope, $status, $region, $reason, $mouDoc]);

            // Sync with companies table for engagement metrics
            $pdo->prepare("INSERT IGNORE INTO companies (company_name, industry) VALUES (?, 'Corporate MOU Partner')")->execute([$compName]);

            $msg = "MOU agreement for '{$compName}' recorded successfully!";
        }
    } elseif ($action === 'update_mou') {
        $mouId = intval($_POST['mou_id']);
        $compName = sanitize($_POST['company_name']);
        $signedDate = sanitize($_POST['signed_date']);
        $expiryDate = sanitize($_POST['expiry_date']);
        $region = !empty($_POST['region']) ? sanitize($_POST['region']) : 'Gujarat';
        $reason = !empty($_POST['reason']) ? sanitize($_POST['reason']) : '';
        $scope = sanitize($_POST['scope']);
        $status = sanitize($_POST['status']);

        // Fetch current record
        $stmtCurr = $pdo->prepare("SELECT * FROM mous WHERE id = ?");
        $stmtCurr->execute([$mouId]);
        $currentMou = $stmtCurr->fetch();

        if ($currentMou) {
            $mouDoc = $currentMou['mou_document'];

            // Optional new document upload
            if (isset($_FILES['mou_document']) && $_FILES['mou_document']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['mou_document']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['pdf', 'doc', 'docx'];
                if (in_array($ext, $allowedExts)) {
                    // Remove old document if customized
                    if ($mouDoc && $mouDoc !== 'mou_sample.pdf') {
                        $oldPath = __DIR__ . '/../uploads/' . $mouDoc;
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    $cleanSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($compName));
                    $docName = 'mou_' . $cleanSlug . '_' . time() . '.' . $ext;
                    $targetPath = __DIR__ . '/../uploads/' . $docName;
                    if (move_uploaded_file($_FILES['mou_document']['tmp_name'], $targetPath)) {
                        $mouDoc = $docName;
                    } else {
                        $error = "Failed to upload updated MOU document.";
                    }
                } else {
                    $error = "Invalid document format! Only PDF, DOC, or DOCX documents are allowed.";
                }
            }

            if (empty($error)) {
                $stmtUp = $pdo->prepare("UPDATE mous SET company_name = ?, signed_date = ?, expiry_date = ?, scope = ?, status = ?, region = ?, reason = ?, mou_document = ? WHERE id = ?");
                $stmtUp->execute([$compName, $signedDate, $expiryDate, $scope, $status, $region, $reason, $mouDoc, $mouId]);

                // Sync with companies table
                $pdo->prepare("INSERT IGNORE INTO companies (company_name, industry) VALUES (?, 'Corporate MOU Partner')")->execute([$compName]);

                $msg = "MOU agreement for '{$compName}' updated successfully!";
            }
        } else {
            $error = "MOU record not found.";
        }
    } elseif ($action === 'delete_mou') {
        $mouId = intval($_POST['mou_id']);
        $stmtCurr = $pdo->prepare("SELECT mou_document FROM mous WHERE id = ?");
        $stmtCurr->execute([$mouId]);
        $currentMou = $stmtCurr->fetch();

        if ($currentMou && $currentMou['mou_document'] && $currentMou['mou_document'] !== 'mou_sample.pdf') {
            $oldPath = __DIR__ . '/../uploads/' . $currentMou['mou_document'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $stmtDel = $pdo->prepare("DELETE FROM mous WHERE id = ?");
        $stmtDel->execute([$mouId]);
        $msg = "MOU record deleted successfully.";
    } elseif ($action === 'import_mou_excel') {
        if (isset($_FILES['mou_file']) && $_FILES['mou_file']['error'] === UPLOAD_ERR_OK) {
            $tmpFile = $_FILES['mou_file']['tmp_name'];
            $handle = fopen($tmpFile, "r");
            if ($handle !== FALSE) {
                // Header row check
                $header = fgetcsv($handle, 2000, ",");
                $importedCount = 0;
                while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
                    if (empty($data[0])) continue;
                    $compName = sanitize($data[0]);
                    $signedDate = !empty($data[1]) ? date('Y-m-d', strtotime(sanitize($data[1]))) : date('Y-m-d');
                    $expiryDate = !empty($data[2]) ? date('Y-m-d', strtotime(sanitize($data[2]))) : date('Y-m-d', strtotime('+3 years'));
                    $scope = isset($data[3]) && trim($data[3]) !== '' ? sanitize($data[3]) : 'Skill Development & Recruitment MOU';
                    $status = isset($data[4]) && trim($data[4]) !== '' ? sanitize($data[4]) : 'Active';
                    $region = isset($data[5]) && trim($data[5]) !== '' ? sanitize($data[5]) : 'Gujarat';
                    $reason = isset($data[6]) && trim($data[6]) !== '' ? sanitize($data[6]) : 'Campus Recruitment & Industry Collaboration';
                    $docFile = isset($data[7]) && trim($data[7]) !== '' ? sanitize($data[7]) : null;

                    // Check if file exists or keep null
                    if ($docFile && !file_exists(__DIR__ . '/../uploads/' . $docFile)) {
                        // If file not on disk, fallback to mou_sample.pdf if exists
                        if (file_exists(__DIR__ . '/../uploads/mou_sample.pdf')) {
                            $docFile = 'mou_sample.pdf';
                        }
                    }

                    $stmt = $pdo->prepare("INSERT INTO mous (company_name, signed_date, expiry_date, scope, status, region, reason, mou_document) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                                           ON DUPLICATE KEY UPDATE 
                                           signed_date = VALUES(signed_date), 
                                           expiry_date = VALUES(expiry_date), 
                                           scope = VALUES(scope), 
                                           status = VALUES(status), 
                                           region = VALUES(region), 
                                           reason = VALUES(reason), 
                                           mou_document = COALESCE(VALUES(mou_document), mou_document)");
                    $stmt->execute([$compName, $signedDate, $expiryDate, $scope, $status, $region, $reason, $docFile]);
                    
                    // Sync with companies table
                    $pdo->prepare("INSERT IGNORE INTO companies (company_name, industry) VALUES (?, 'Corporate MOU Partner')")->execute([$compName]);
                    $importedCount++;
                }
                fclose($handle);
                $msg = "🎉 Excel / CSV MOU Import Complete: $importedCount MOU agreements imported successfully!";
            } else {
                $error = "Unable to parse the uploaded Excel/CSV file.";
            }
        } else {
            $error = "Please upload a valid Excel/CSV file.";
        }
    }
}

// Active Filters
$yearFilter = isset($_GET['year']) ? intval($_GET['year']) : 0;
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$regionFilter = isset($_GET['region']) ? sanitize($_GET['region']) : '';
$searchQ = isset($_GET['q']) ? sanitize($_GET['q']) : '';

// Excel / CSV Export Engine
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=institutional_mou_report_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Partner Enterprise', 'Signing Date', 'Expiry Date', 'Agreement Scope', 'Active Status', 'Region', 'Strategic Reason', 'MOU Document']);

    $qExp = "SELECT * FROM mous WHERE 1=1";
    $paramsExp = [];
    if ($yearFilter > 0) { 
        $qExp .= " AND (YEAR(signed_date) = ? OR YEAR(expiry_date) = ?)"; 
        $paramsExp[] = $yearFilter;
        $paramsExp[] = $yearFilter;
    }
    if ($statusFilter !== '') { 
        $qExp .= " AND status = ?"; 
        $paramsExp[] = $statusFilter;
    }
    if ($regionFilter !== '') { 
        $qExp .= " AND region = ?"; 
        $paramsExp[] = $regionFilter;
    }
    if ($searchQ !== '') {
        $qExp .= " AND (company_name LIKE ? OR scope LIKE ? OR reason LIKE ? OR region LIKE ?)";
        $paramsExp[] = "%$searchQ%";
        $paramsExp[] = "%$searchQ%";
        $paramsExp[] = "%$searchQ%";
        $paramsExp[] = "%$searchQ%";
    }
    $qExp .= " ORDER BY id DESC";

    $stmtExp = $pdo->prepare($qExp);
    $stmtExp->execute($paramsExp);
    $expMous = $stmtExp->fetchAll();

    foreach ($expMous as $mRow) {
        fputcsv($output, [
            $mRow['company_name'],
            $mRow['signed_date'],
            $mRow['expiry_date'],
            $mRow['scope'],
            $mRow['status'],
            $mRow['region'],
            $mRow['reason'],
            $mRow['mou_document']
        ]);
    }
    fclose($output);
    exit;
}

// Fetch distinct regions for filter dropdown
$distinctRegions = $pdo->query("SELECT DISTINCT region FROM mous WHERE region IS NOT NULL AND region != '' ORDER BY region ASC")->fetchAll(PDO::FETCH_COLUMN);

// Query filtered MOUs
$q = "SELECT * FROM mous WHERE 1=1";
$params = [];
if ($yearFilter > 0) { 
    $q .= " AND (YEAR(signed_date) = ? OR YEAR(expiry_date) = ?)"; 
    $params[] = $yearFilter;
    $params[] = $yearFilter;
}
if ($statusFilter !== '') { 
    $q .= " AND status = ?"; 
    $params[] = $statusFilter;
}
if ($regionFilter !== '') { 
    $q .= " AND region = ?"; 
    $params[] = $regionFilter;
}
if ($searchQ !== '') {
    $q .= " AND (company_name LIKE ? OR scope LIKE ? OR reason LIKE ? OR region LIKE ?)";
    $params[] = "%$searchQ%";
    $params[] = "%$searchQ%";
    $params[] = "%$searchQ%";
    $params[] = "%$searchQ%";
}
$q .= " ORDER BY id DESC";

$stmt = $pdo->prepare($q);
$stmt->execute($params);
$mous = $stmt->fetchAll();

$pageTitle = 'Institutional MOUs';
$currentPage = 'mou';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-header-flex" style="margin-bottom: 24px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Memorandum of Understanding (MOU) Partnerships</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Manage industrial collaborations, signed MOUs, documents, regions, expiry tracking, and partnership scopes.</p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <button class="btn-secondary" onclick="openModal('importMouModal')" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 700; background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">
            📥 Import MOUs from Excel / CSV
        </button>
        <a href="mou.php?export_csv=1&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>&region=<?= urlencode($regionFilter); ?>&q=<?= urlencode($searchQ); ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 18px; font-weight: 700;">
            📊 Export CSV
        </a>
        <button class="btn-primary" onclick="openModal('createMouModal')" style="border-radius: 12px; padding: 10px 18px; font-weight: 800; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Register New MOU
        </button>
    </div>
</div>

<?php if ($msg): ?>
    <div class="badge-status placed" style="margin-bottom: 16px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;">
        <?= htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="badge-status unplaced" style="margin-bottom: 16px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;">
        <?= htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Multi-Filter Controls Bar -->
<div class="table-card" style="padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; background: white; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03); flex-wrap: wrap; gap: 12px;">
    <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="position: relative;">
            <input type="text" name="q" value="<?= htmlspecialchars($searchQ); ?>" placeholder="Search partner, region, reason..." style="padding: 9px 16px 9px 36px; border-radius: 12px; border: 1px solid #CBD5E1; font-size: 13px; outline: none; width: 230px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>

        <select name="year" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700;">
            <option value="0">📅 All Agreement Years</option>
            <option value="2026" <?= ($yearFilter===2026)?'selected':''; ?>>2026 Academic Year</option>
            <option value="2025" <?= ($yearFilter===2025)?'selected':''; ?>>2025 Academic Year</option>
            <option value="2024" <?= ($yearFilter===2024)?'selected':''; ?>>2024 Academic Year</option>
        </select>

        <select name="status" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700;">
            <option value="">All MOU Statuses</option>
            <option value="Active" <?= ($statusFilter==='Active')?'selected':''; ?>>🟢 Active MOUs</option>
            <option value="Expiring Soon" <?= ($statusFilter==='Expiring Soon')?'selected':''; ?>>🟡 Expiring Soon</option>
            <option value="Expired" <?= ($statusFilter==='Expired')?'selected':''; ?>>🔴 Expired</option>
        </select>

        <select name="region" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700;">
            <option value="">📍 All Regions</option>
            <?php foreach ($distinctRegions as $reg): ?>
                <option value="<?= htmlspecialchars($reg); ?>" <?= ($regionFilter === $reg) ? 'selected' : ''; ?>>
                    📍 <?= htmlspecialchars($reg); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-primary" style="padding: 9px 16px; font-size: 13px; border-radius: 12px;">Filter</button>
        <a href="mou.php" class="btn-secondary" style="padding: 9px 16px; font-size: 13px; border-radius: 12px;">Reset</a>
    </form>
    <span class="kpi-badge info" style="font-weight: 800; padding: 8px 16px; border-radius: 12px;">
        Showing <?= count($mous); ?> MOU Agreements
    </span>
</div>

<div class="table-card" style="padding: 24px; border-radius: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
    <div class="table-responsive">
        <table class="custom-table" style="width: 100%;">
            <thead>
                <tr style="background: #F8FAFC;">
                    <th>Partner Enterprise & Document</th>
                    <th>Region & Purpose</th>
                    <th>Signed Date</th>
                    <th>Expiration Date</th>
                    <th>Collaboration Scope</th>
                    <th>Engagement Metrics</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($mous)): ?>
                    <?php foreach ($mous as $m): 
                        $compRow = $pdo->query("SELECT id FROM companies WHERE company_name LIKE " . $pdo->quote('%' . $m['company_name'] . '%'))->fetch();
                        $driveCount = 0; $wkCount = 0;
                        if ($compRow) {
                            $cId = $compRow['id'];
                            $driveCount = $pdo->query("SELECT COUNT(*) FROM drives WHERE company_id = $cId")->fetchColumn();
                            $wkCount = $pdo->query("SELECT COUNT(*) FROM workshops WHERE company_id = $cId")->fetchColumn();
                        }

                        $hasDoc = (!empty($m['mou_document']) && file_exists(__DIR__ . '/../uploads/' . $m['mou_document']));
                        $docExt = $hasDoc ? strtolower(pathinfo($m['mou_document'], PATHINFO_EXTENSION)) : '';
                        $docIcon = ($docExt === 'pdf') ? '📕' : '📘';

                        // Check expiry
                        $isExpired = (strtotime($m['expiry_date']) < time());
                    ?>
                        <tr style="background: #FFFFFF;">
                            <td>
                                <strong style="font-size: 14.5px; color: #0F172A; display: block;">
                                    🏢 <?= htmlspecialchars($m['company_name']); ?>
                                </strong>
                                <div style="margin-top: 6px;">
                                    <?php if ($hasDoc): ?>
                                        <a href="../uploads/<?= htmlspecialchars($m['mou_document']); ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 800; color: #2563EB; background: #EFF6FF; border: 1px solid #BFDBFE; padding: 3px 10px; border-radius: 8px; text-decoration: none;">
                                            <?= $docIcon; ?> View MOU (<?= strtoupper($docExt); ?>) ↗
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #94A3B8; font-weight: 600;">📄 No Document Attached</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span style="display: inline-block; font-size: 11px; font-weight: 800; color: #047857; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 3px 8px; border-radius: 6px; text-transform: uppercase;">
                                    📍 <?= htmlspecialchars($m['region'] ?: 'Gujarat'); ?>
                                </span>
                                <?php if (!empty($m['reason'])): ?>
                                    <div style="font-size: 12px; color: #475569; margin-top: 4px; font-weight: 600; line-height: 1.4;">
                                        <?= htmlspecialchars($m['reason']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= date('d M Y', strtotime($m['signed_date'])); ?></strong></td>
                            <td>
                                <strong style="color: <?= $isExpired ? '#EF4444' : '#0F172A'; ?>;">
                                    <?= date('d M Y', strtotime($m['expiry_date'])); ?>
                                </strong>
                                <?php if ($isExpired): ?>
                                    <span style="font-size: 10px; font-weight: 800; color: #EF4444; display: block;">(Expired)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 12px; color: var(--text-muted); font-weight: 600; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($m['scope'] ?: 'Industry-Academia Collaboration'); ?>
                                </span>
                            </td>
                            <td>
                                <span style="background: #EFF6FF; color: #2563EB; font-weight: 800; padding: 4px 8px; border-radius: 8px; font-size: 11px; display: inline-block;">
                                    🎯 <?= $driveCount; ?> Drives
                                </span>
                                <span style="background: #F3E8FF; color: #7C3AED; font-weight: 800; padding: 4px 8px; border-radius: 8px; font-size: 11px; display: inline-block; margin-left: 2px;">
                                    🎓 <?= $wkCount; ?> Workshops
                                </span>
                            </td>
                            <td>
                                <?php $bClass = ($m['status']==='Active') ? 'placed' : (($m['status']==='Expiring Soon') ? 'in-process' : 'unplaced'); ?>
                                <span class="badge-status <?= $bClass; ?>" style="font-size: 11px;"><?= $m['status']; ?></span>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 8px; align-items: center;">
                                    <button type="button" class="btn-action-icon edit" onclick='openEditMouModal(<?= json_encode($m, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' title="Edit MOU Agreement" style="padding: 7px 12px; border-radius: 10px; font-size: 12px; font-weight: 800; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; cursor: pointer;">
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this MOU record for <?= addslashes($m['company_name']); ?>?');">
                                        <input type="hidden" name="action" value="delete_mou">
                                        <input type="hidden" name="mou_id" value="<?= $m['id']; ?>">
                                        <button type="submit" class="btn-action-icon delete" title="Delete MOU" style="padding: 7px 12px; border-radius: 10px; font-size: 12px; font-weight: 800; background: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; cursor: pointer;">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            No MOU agreements found matching your search and filter criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Register New MOU -->
<div class="modal-overlay" id="createMouModal">
    <div class="modal-box" style="max-width: 640px; border-radius: 24px; padding: 28px;">
        <div class="modal-header" style="margin-bottom: 20px; border-bottom: 1px solid #E2E8F0; padding-bottom: 14px;">
            <h3 style="font-size: 19px; font-weight: 800; color: #0F172A;">📜 Register Institutional MOU Agreement</h3>
            <button class="close-modal" onclick="closeModal('createMouModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_mou">
            <div class="form-group">
                <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Partner Company / Organization Name *</label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. Cisco Networking Academy" required style="border-radius: 12px; padding: 10px 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Signing Date *</label>
                    <input type="date" name="signed_date" class="form-control" value="<?= date('Y-m-d'); ?>" required style="border-radius: 12px; padding: 10px 14px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Expiry Date *</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 years')); ?>" required style="border-radius: 12px; padding: 10px 14px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Tag Region / Territory *</label>
                    <input type="text" name="region" class="form-control" placeholder="e.g. Gujarat, Saurashtra, Pan India" value="Gujarat" required style="border-radius: 12px; padding: 10px 14px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Active Status</label>
                    <select name="status" class="form-control" style="border-radius: 12px; padding: 10px 14px;">
                        <option value="Active">🟢 Active</option>
                        <option value="Expiring Soon">🟡 Expiring Soon</option>
                        <option value="Expired">🔴 Expired</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Tag Reason / Strategic Objectives</label>
                <input type="text" name="reason" class="form-control" placeholder="e.g. Campus Recruitment, Center of Excellence, Faculty Training" style="border-radius: 12px; padding: 10px 14px;">
            </div>

            <div class="form-group">
                <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Scope of Agreement</label>
                <textarea name="scope" class="form-control" rows="3" placeholder="Faculty development programs, laboratory sponsorship, direct recruitment, internships..." style="border-radius: 12px; padding: 10px 14px;"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="font-weight: 700; font-size: 12.5px; color: #2563EB; margin-bottom: 6px; display: block;">
                    📎 Upload Signed MOU Document (PDF / DOC / DOCX only)
                </label>
                <div style="background: #F8FAFC; border: 1px dashed #3B82F6; padding: 16px; border-radius: 14px;">
                    <input type="file" name="mou_document" accept=".pdf,.doc,.docx" class="form-control" style="border-radius: 10px; background: white;">
                    <span style="font-size: 11.5px; color: #64748B; margin-top: 6px; display: block; font-weight: 600;">
                        Supported document formats: <strong>.pdf, .doc, .docx</strong> (Max 25MB)
                    </span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="closeModal('createMouModal')" style="padding: 10px 20px; border-radius: 12px;">Cancel</button>
                <button type="submit" class="btn-primary" style="padding: 10px 24px; border-radius: 12px; font-weight: 800; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">
                    💾 Save MOU Record
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Existing MOU -->
<div class="modal-overlay" id="editMouModal">
    <div class="modal-box" style="max-width: 640px; border-radius: 24px; padding: 28px;">
        <div class="modal-header" style="margin-bottom: 20px; border-bottom: 1px solid #E2E8F0; padding-bottom: 14px;">
            <h3 style="font-size: 19px; font-weight: 800; color: #0F172A;">✏️ Edit Institutional MOU Agreement</h3>
            <button class="close-modal" onclick="closeModal('editMouModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_mou">
            <input type="hidden" name="mou_id" id="edit_mou_id">

            <div class="form-group">
                <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Partner Company / Organization Name *</label>
                <input type="text" name="company_name" id="edit_company_name" class="form-control" required style="border-radius: 12px; padding: 10px 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Signing Date *</label>
                    <input type="date" name="signed_date" id="edit_signed_date" class="form-control" required style="border-radius: 12px; padding: 10px 14px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Expiry Date *</label>
                    <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control" required style="border-radius: 12px; padding: 10px 14px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Tag Region / Territory *</label>
                    <input type="text" name="region" id="edit_region" class="form-control" placeholder="e.g. Gujarat, Saurashtra, Pan India" required style="border-radius: 12px; padding: 10px 14px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Active Status</label>
                    <select name="status" id="edit_status" class="form-control" style="border-radius: 12px; padding: 10px 14px;">
                        <option value="Active">🟢 Active</option>
                        <option value="Expiring Soon">🟡 Expiring Soon</option>
                        <option value="Expired">🔴 Expired</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Tag Reason / Strategic Objectives</label>
                <input type="text" name="reason" id="edit_reason" class="form-control" placeholder="e.g. Campus Recruitment, Center of Excellence" style="border-radius: 12px; padding: 10px 14px;">
            </div>

            <div class="form-group">
                <label style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">Scope of Agreement</label>
                <textarea name="scope" id="edit_scope" class="form-control" rows="3" style="border-radius: 12px; padding: 10px 14px;"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="font-weight: 700; font-size: 12.5px; color: #2563EB; margin-bottom: 6px; display: block;">
                    📎 Replace MOU Document (PDF / DOC / DOCX only)
                </label>
                <div style="background: #F8FAFC; border: 1px dashed #3B82F6; padding: 16px; border-radius: 14px;">
                    <div id="edit_current_doc_preview" style="margin-bottom: 10px; font-size: 12px; color: #334155; font-weight: 700;"></div>
                    <input type="file" name="mou_document" accept=".pdf,.doc,.docx" class="form-control" style="border-radius: 10px; background: white;">
                    <span style="font-size: 11.5px; color: #64748B; margin-top: 6px; display: block; font-weight: 600;">
                        Leave blank to retain current document. Only <strong>.pdf, .doc, .docx</strong> allowed.
                    </span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="closeModal('editMouModal')" style="padding: 10px 20px; border-radius: 12px;">Cancel</button>
                <button type="submit" class="btn-primary" style="padding: 10px 24px; border-radius: 12px; font-weight: 800; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">
                    💾 Update MOU Record
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Excel / CSV MOU Bulk Import -->
<div class="modal-overlay" id="importMouModal">
    <div class="modal-box" style="max-width: 540px; border-radius: 24px; padding: 28px;">
        <div class="modal-header" style="margin-bottom: 20px; border-bottom: 1px solid #E2E8F0; padding-bottom: 14px;">
            <h3 style="font-size: 19px; font-weight: 800; color: #0F172A;">📥 Import MOUs from Excel / CSV Sheet</h3>
            <button class="close-modal" onclick="closeModal('importMouModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import_mou_excel">
            
            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 14px; padding: 14px 16px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="font-size: 13px; color: #1E40AF;">Standard MOU Import Template</strong>
                    <div style="font-size: 11.5px; color: #3B82F6;">Includes Region, Reason, and Document columns.</div>
                </div>
                <a href="mou.php?download_sample=1" class="btn-secondary" style="font-size: 12px; font-weight: 800; padding: 6px 12px; border-radius: 8px; background: white; text-decoration: none; color: #2563EB; border: 1px solid #BFDBFE;">
                    📥 Download CSV Template
                </a>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 800; font-size: 13px; color: #334155; display: block; margin-bottom: 8px;">Select MOU Excel / CSV File *</label>
                <div style="border: 2px dashed #10B981; background: #ECFDF5; border-radius: 16px; padding: 24px; text-align: center; position: relative;">
                    <div style="font-size: 36px; margin-bottom: 8px;">📊</div>
                    <div style="font-size: 14px; font-weight: 800; color: #065F46;">Choose file (.csv, .xlsx, .xls)</div>
                    <div style="font-size: 11.5px; color: #047857; margin-top: 4px;">Columns: Company, Signed Date, Expiry Date, Scope, Status, Region, Reason, Document</div>
                    <input type="file" name="mou_file" accept=".csv,.xlsx,.xls" required style="position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="closeModal('importMouModal')" style="padding: 10px 18px; border-radius: 12px;">Cancel</button>
                <button type="submit" class="btn-primary" style="padding: 10px 22px; background: #10B981; border: none; border-radius: 12px; font-weight: 800;">
                    📤 Start Excel Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditMouModal(mou) {
    document.getElementById('edit_mou_id').value = mou.id;
    document.getElementById('edit_company_name').value = mou.company_name;
    document.getElementById('edit_signed_date').value = mou.signed_date;
    document.getElementById('edit_expiry_date').value = mou.expiry_date;
    document.getElementById('edit_region').value = mou.region || 'Gujarat';
    document.getElementById('edit_reason').value = mou.reason || '';
    document.getElementById('edit_scope').value = mou.scope || '';
    document.getElementById('edit_status').value = mou.status || 'Active';

    const previewDiv = document.getElementById('edit_current_doc_preview');
    if (mou.mou_document) {
        previewDiv.innerHTML = '📄 Current Document: <a href="../uploads/' + encodeURIComponent(mou.mou_document) + '" target="_blank" style="color: #2563EB; font-weight: 800; text-decoration: underline;">' + escapeHtml(mou.mou_document) + ' ↗</a>';
    } else {
        previewDiv.innerHTML = '<span style="color: #64748B;">No document currently attached.</span>';
    }

    openModal('editMouModal');
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
