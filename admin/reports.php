<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();

$enrollmentSearch = isset($_GET['enrollment']) ? sanitize($_GET['enrollment']) : '';
$branchFilter = isset($_GET['branch']) ? sanitize($_GET['branch']) : '';
$yearFilter = isset($_GET['year']) ? intval($_GET['year']) : 0;
$companyFilter = isset($_GET['company']) ? intval($_GET['company']) : 0;

// Analytics Query with Active Filters
$q = "SELECT s.*, GROUP_CONCAT(c.company_name SEPARATOR ', ') as companies_placed 
      FROM students s 
      LEFT JOIN applications a ON s.id = a.student_id AND a.status = 'Selected'
      LEFT JOIN drives d ON a.drive_id = d.id
      LEFT JOIN companies c ON d.company_id = c.id
      WHERE s.is_detained = 0";
$params = [];

if ($branchFilter) { $q .= " AND s.branch = ?"; $params[] = $branchFilter; }
if ($yearFilter) { $q .= " AND s.batch_year = ?"; $params[] = $yearFilter; }
if ($companyFilter) { $q .= " AND d.company_id = ?"; $params[] = $companyFilter; }

$q .= " GROUP BY s.id ORDER BY s.cpi DESC";
$stmtReport = $pdo->prepare($q);
$stmtReport->execute($params);
$reportData = $stmtReport->fetchAll();

// Filtered Cohort Counts & Breakdown
$totalCohort = count($reportData);
$countPlaced = 0;
$countInProcess = 0;
$countUnplaced = 0;
$countDetained = 0;

// Branch Breakdown Data Setup
$branchesList = ['CSE', 'IT', 'ECE', 'MECH', 'CIVIL'];
$branchTotals = ['CSE' => 0, 'IT' => 0, 'ECE' => 0, 'MECH' => 0, 'CIVIL' => 0];
$branchPlaced = ['CSE' => 0, 'IT' => 0, 'ECE' => 0, 'MECH' => 0, 'CIVIL' => 0];

foreach ($reportData as $row) {
    $br = strtoupper($row['branch']);
    if (isset($branchTotals[$br])) {
        $branchTotals[$br]++;
    } else {
        $branchTotals[$br] = 1;
        $branchPlaced[$br] = 0;
    }

    if ($row['is_detained']) {
        $countDetained++;
    } elseif ($row['placement_status'] === 'Placed') {
        $countPlaced++;
        if (isset($branchPlaced[$br])) {
            $branchPlaced[$br]++;
        }
    } elseif ($row['placement_status'] === 'In-Process') {
        $countInProcess++;
    } else {
        $countUnplaced++;
    }
}

$pctPlaced = $totalCohort > 0 ? round(($countPlaced / $totalCohort) * 100, 1) : 0;
$pctInProcess = $totalCohort > 0 ? round(($countInProcess / $totalCohort) * 100, 1) : 0;
$pctUnplaced = $totalCohort > 0 ? round(($countUnplaced / $totalCohort) * 100, 1) : 0;
$pctDetained = $totalCohort > 0 ? round(($countDetained / $totalCohort) * 100, 1) : 0;

// Compute Branch Placement Percentages for Bar Chart
$branchRates = [];
foreach ($branchTotals as $bName => $bTot) {
    $bPl = $branchPlaced[$bName] ?? 0;
    $branchRates[$bName] = $bTot > 0 ? round(($bPl / $bTot) * 100, 1) : 0;
}

// Corporate Recruiter Company Performance Analytics Query
$companyStats = $pdo->query("
    SELECT c.id, c.company_name, c.hr_name, c.hr_email, c.industry,
           COUNT(DISTINCT d.id) as drives_posted,
           COUNT(DISTINCT a.id) as students_placed,
           MAX(d.package_ctc) as top_package
    FROM companies c
    LEFT JOIN drives d ON c.id = d.company_id
    LEFT JOIN applications a ON d.id = a.drive_id AND a.status = 'Selected'
    GROUP BY c.id
    ORDER BY students_placed DESC, drives_posted DESC
")->fetchAll();

$totalCompaniesCount = count($companyStats);
$totalDrivesPosted = $pdo->query("SELECT COUNT(*) FROM drives")->fetchColumn();
$totalStudentsHiredCount = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM applications WHERE status = 'Selected'")->fetchColumn();

// Company Placement Data for Company-Wise Bar & Pie Charts
$compNamesArr = [];
$compHiredArr = [];
foreach (array_slice($companyStats, 0, 6) as $csRow) {
    $compNamesArr[] = $csRow['company_name'];
    $compHiredArr[] = intval($csRow['students_placed']);
}

$totCompHired = array_sum($compHiredArr);
$compPercentagesArr = [];
foreach ($compHiredArr as $hVal) {
    $compPercentagesArr[] = $totCompHired > 0 ? round(($hVal / $totCompHired) * 100, 1) : 0;
}

// Excel / CSV Export Action Handler
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=placement_cohort_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Enrollment No', 'Full Candidate Name', 'Email', 'Branch', 'Batch Year', 'CPI', 'Backlogs', 'Placement Status', 'Detained', 'Placed Companies']);
    
    foreach ($reportData as $r) {
        fputcsv($output, [
            $r['enrollment_no'],
            $r['full_name'],
            $r['email'],
            $r['branch'],
            $r['batch_year'],
            $r['cpi'],
            $r['backlogs'],
            $r['placement_status'],
            $r['is_detained'] ? 'Yes' : 'No',
            $r['companies_placed'] ?: 'None'
        ]);
    }
    fclose($output);
    exit;
}

$companiesList = $pdo->query("SELECT id, company_name FROM companies ORDER BY company_name ASC")->fetchAll();
$allStudentsList = $pdo->query("SELECT id, enrollment_no, full_name, branch FROM students WHERE is_detained = 0 ORDER BY full_name ASC")->fetchAll();

$pageTitle = 'Reports & Advanced Analytics';
$currentPage = 'reports';
include __DIR__ . '/../includes/header.php';
?>

<!-- Official PDF Print Header Banner (Hidden on Screen, Visible on PDF Export) -->
<div class="print-header-banner" style="display: none; border-bottom: 2px solid #3B82F6; padding-bottom: 14px; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0;">Dr. Subhash University — Official Campus Placement Analytics Report</h1>
        <p style="font-size: 12px; color: #64748B; margin-top: 4px;">
            Generated on: <strong><?= date('d M Y, h:i A'); ?></strong> | 
            Filtered Cohort Size: <strong><?= $totalCohort; ?> Candidates</strong>
        </p>
    </div>
    <div style="text-align: right; font-size: 11px; color: #64748B;">
        <p><strong>Active Filters:</strong></p>
        <p>Branch: <?= $branchFilter ?: 'All Branches'; ?> | Year: <?= $yearFilter ?: 'All Batch Years'; ?></p>
    </div>
</div>

<!-- Header Action Row -->
<div class="card-header-flex" style="margin-bottom: 20px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 800; color: #0F172A;">Placement Reports & Analytics Intelligence</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Executive cohort breakdown, department placement rates, recruiter hiring charts, and automated PDF/CSV export engine.</p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <a href="reports.php?export_csv=1&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&company=<?= $companyFilter; ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            📊 Export Excel / CSV
        </a>
        <button class="btn-primary" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print Clean PDF Report
        </button>
    </div>
</div>

<!-- =========================================================================
     UPPER MULTI-FILTER BAR (ARRANGED AT THE VERY TOP ABOVE CHARTS & CARDS)
     ========================================================================= -->
<div class="table-card" style="padding: 20px; margin-bottom: 24px; background: #FFFFFF; border: 1px solid var(--border-color); border-radius: 20px; box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 38px; height: 38px; background: #EFF6FF; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #3B82F6;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            </div>
            <div>
                <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; margin: 0;">Analytics Multi-Filter Controls</h4>
                <p style="font-size: 12px; color: #64748B; margin-top: 2px;">Select filters below to update charts, counts, and exported roster.</p>
            </div>
        </div>

        <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <select name="branch" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700;">
                <option value="">All Branches</option>
                <option value="CSE" <?= ($branchFilter==='CSE')?'selected':''; ?>>CSE Branch</option>
                <option value="IT" <?= ($branchFilter==='IT')?'selected':''; ?>>IT Branch</option>
                <option value="ECE" <?= ($branchFilter==='ECE')?'selected':''; ?>>ECE Branch</option>
                <option value="MECH" <?= ($branchFilter==='MECH')?'selected':''; ?>>MECH Branch</option>
                <option value="CIVIL" <?= ($branchFilter==='CIVIL')?'selected':''; ?>>CIVIL Branch</option>
            </select>

            <select name="year" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700;">
                <option value="">All Batch Years</option>
                <option value="2026" <?= ($yearFilter===2026)?'selected':''; ?>>2026 Batch</option>
                <option value="2025" <?= ($yearFilter===2025)?'selected':''; ?>>2025 Batch</option>
                <option value="2024" <?= ($yearFilter===2024)?'selected':''; ?>>2024 Batch</option>
            </select>

            <select name="company" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700;">
                <option value="">All Companies</option>
                <?php foreach ($companiesList as $c): ?>
                    <option value="<?= $c['id']; ?>" <?= ($companyFilter===$c['id'])?'selected':''; ?>><?= htmlspecialchars($c['company_name']); ?></option>
                <?php endforeach; ?>
            </select>

            <a href="reports.php" class="btn-secondary" style="padding: 9px 18px; font-size: 13px;">Reset Filter</a>
        </form>
    </div>
</div>

<!-- =========================================================================
     STUDENT SUMMARY METRICS KPI COUNTER CARDS
     ========================================================================= -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 28px;">
    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Filtered Cohort Size</span>
            <div class="kpi-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px;"><?= number_format($totalCohort); ?> Candidates</div>
        <span class="kpi-badge info">Active Criteria</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Placed Candidates</span>
            <div class="kpi-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #10B981;"><?= number_format($countPlaced); ?> <span style="font-size:13px; font-weight:600; color:#64748B;">(<?= $pctPlaced; ?>%)</span></div>
        <span class="kpi-badge up">🟢 Placed / Selected</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">In-Process Pipeline</span>
            <div class="kpi-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 16 14"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #F59E0B;"><?= number_format($countInProcess); ?> <span style="font-size:13px; font-weight:600; color:#64748B;">(<?= $pctInProcess; ?>%)</span></div>
        <span class="kpi-badge info" style="background:#FEF3C7; color:#B45309;">🟡 Seeking / Process</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Unplaced Seeking</span>
            <div class="kpi-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #EF4444;"><?= number_format($countUnplaced); ?> <span style="font-size:13px; font-weight:600; color:#64748B;">(<?= $pctUnplaced; ?>%)</span></div>
        <span class="kpi-badge" style="background:#FEE2E2; color:#B91C1C;">🔴 Open Candidates</span>
    </div>
</div>

<!-- =========================================================================
     SECTION 1: STUDENT PLACEMENT COHORT ANALYTICS (CHARTS)
     ========================================================================= -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 28px;">
    <!-- Chart 1: Donut Chart - Filtered Placement Distribution -->
    <div class="chart-card">
        <div class="card-header-flex" style="margin-bottom: 16px;">
            <div class="card-title">
                <h3 style="font-size: 17px; font-weight: 800; color: #0F172A;">📊 Filtered Cohort Status Breakdown (Candidates Count)</h3>
            </div>
            <span class="kpi-badge info">Cohort Total: <?= $totalCohort; ?></span>
        </div>

        <div class="donut-center-container" style="height: 230px;">
            <canvas id="placementDistributionChart"></canvas>
            <div class="donut-center-text">
                <div class="big-percent" style="font-size: 26px; color: #10B981; font-weight: 800;"><?= number_format($countPlaced); ?></div>
                <div class="sub-label" style="font-size: 11px; font-weight: 700; color: #64748B;">Total Placed</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 20px; text-align: center;">
            <div style="background: #ECFDF5; border: 1px solid #A7F3D0; padding: 10px 8px; border-radius: 12px;">
                <span style="font-size: 10px; color: #047857; font-weight: 800; text-transform: uppercase;">🟢 Placed</span>
                <h4 style="font-size: 18px; font-weight: 800; color: #065F46; margin-top: 2px;"><?= $countPlaced; ?></h4>
                <p style="font-size: 11px; color: #047857; font-weight: 600;"><?= $pctPlaced; ?>% of Filter</p>
            </div>
            <div style="background: #FEF3C7; border: 1px solid #FDE68A; padding: 10px 8px; border-radius: 12px;">
                <span style="font-size: 10px; color: #B45309; font-weight: 800; text-transform: uppercase;">🟡 In-Process</span>
                <h4 style="font-size: 18px; font-weight: 800; color: #92400E; margin-top: 2px;"><?= $countInProcess; ?></h4>
                <p style="font-size: 11px; color: #B45309; font-weight: 600;"><?= $pctInProcess; ?>% of Filter</p>
            </div>
            <div style="background: #FEE2E2; border: 1px solid #FCA5A5; padding: 10px 8px; border-radius: 12px;">
                <span style="font-size: 10px; color: #B91C1C; font-weight: 800; text-transform: uppercase;">🔴 Unplaced</span>
                <h4 style="font-size: 18px; font-weight: 800; color: #991B1B; margin-top: 2px;"><?= $countUnplaced; ?></h4>
                <p style="font-size: 11px; color: #B91C1C; font-weight: 600;"><?= $pctUnplaced; ?>% of Filter</p>
            </div>
            <div style="background: #F3F4F6; border: 1px solid #D1D5DB; padding: 10px 8px; border-radius: 12px;">
                <span style="font-size: 10px; color: #4B5563; font-weight: 800; text-transform: uppercase;">⛔ Detained</span>
                <h4 style="font-size: 18px; font-weight: 800; color: #1F2937; margin-top: 2px;"><?= $countDetained; ?></h4>
                <p style="font-size: 11px; color: #4B5563; font-weight: 600;"><?= $pctDetained; ?>% of Filter</p>
            </div>
        </div>
    </div>

    <!-- Chart 2: Branch Placement Rate Bar Chart -->
    <div class="chart-card">
        <div class="card-header-flex" style="margin-bottom: 16px;">
            <div class="card-title">
                <h3 style="font-size: 17px; font-weight: 800; color: #0F172A;">📈 Filtered Branch Placement Rates (%)</h3>
            </div>
            <span class="kpi-badge info" style="background:#ECFDF5; color:#047857;">5 Branches Analyzed</span>
        </div>

        <div style="height: 230px; position: relative;">
            <canvas id="branchPlacementChart"></canvas>
        </div>

        <div style="display: flex; gap: 8px; justify-content: space-between; margin-top: 20px; text-align: center;">
            <?php 
            $bColors = ['CSE'=>'#3B82F6', 'IT'=>'#10B981', 'ECE'=>'#F59E0B', 'MECH'=>'#8B5CF6', 'CIVIL'=>'#64748B'];
            foreach ($branchRates as $bName => $bRate): 
                $c = $bColors[$bName] ?? '#3B82F6';
                $bPl = $branchPlaced[$bName] ?? 0;
                $bTot = $branchTotals[$bName] ?? 0;
            ?>
                <div style="flex: 1; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 8px 4px;">
                    <span style="font-size: 11px; font-weight: 800; color: <?= $c; ?>;"><?= $bName; ?></span>
                    <div style="font-size: 15px; font-weight: 800; color: #0F172A; margin-top: 2px;"><?= $bRate; ?>%</div>
                    <span style="font-size: 10px; color: #64748B; font-weight: 600;"><?= $bPl; ?>/<?= $bTot; ?> Placed</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- =========================================================================
     POSITION 4: PARTICULAR CANDIDATE DOSSIER TRACKER CARD
     ========================================================================= -->
<div class="table-card" style="padding: 24px; margin-bottom: 28px; background: white; border: 1px solid var(--border-color); border-radius: 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
        <div>
            <h4 style="font-size: 17px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">🔍 Particular Candidate Dossier Tracker</h4>
            <p style="font-size: 13px; color: var(--text-muted);">Select or type enrollment number to immediately launch full-size candidate dossier popup.</p>
        </div>
        <span class="kpi-badge info">Instant Dossier Lookup</span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center;">
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 18px;">
            <label style="font-size: 12px; font-weight: 800; color: #475569; display: block; margin-bottom: 8px;">SELECT CANDIDATE FROM ROSTER:</label>
            <div style="display: flex; gap: 10px;">
                <select id="quickStudentSelect" class="form-control" style="flex:1;">
                    <option value="">-- Choose Candidate --</option>
                    <?php foreach ($allStudentsList as $ast): ?>
                        <option value="<?= $ast['id']; ?>"><?= htmlspecialchars($ast['full_name']); ?> (<?= $ast['enrollment_no']; ?> - <?= $ast['branch']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-primary" onclick="triggerQuickDossier()" style="white-space: nowrap; padding: 10px 18px;">Open Dossier</button>
            </div>
        </div>

        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 18px;">
            <label style="font-size: 12px; font-weight: 800; color: #475569; display: block; margin-bottom: 8px;">TYPE ENROLLMENT CODE:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="enrollmentInputCode" class="form-control" placeholder="e.g. 240130316041" value="<?= htmlspecialchars($enrollmentSearch); ?>" style="flex:1;">
                <button type="button" class="btn-primary" onclick="triggerEnrollmentSearch()" style="white-space: nowrap; padding: 10px 18px;">Find & Track</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     POSITION 5: MASTER PLACEMENT COHORT ROSTER TABLE
     ========================================================================= -->
<div class="table-card" style="padding: 24px; margin-bottom: 28px;">
    <div style="margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="font-weight: 800; font-size: 18px; color: #0F172A;">Master Placement Cohort Roster (<?= count($reportData); ?> Students)</h4>
            <p style="font-size: 13px; color: var(--text-muted);">Click any row to open candidate dossier with live resume PDF and certificates.</p>
        </div>
        <span class="kpi-badge info">Showing <?= count($reportData); ?> Candidates</span>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Enrollment No</th>
                    <th>Candidate Full Name</th>
                    <th>Branch & Batch</th>
                    <th>Academic CPI</th>
                    <th>Placement Status</th>
                    <th>Recruiting Enterprise(s)</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData as $row): ?>
                    <tr style="cursor: pointer; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;" onclick="viewStudentDetails(<?= $row['id']; ?>)">
                        <td style="border-top-left-radius: 16px; border-bottom-left-radius: 16px;">
                            <span class="enrollment-pill"><?= htmlspecialchars($row['enrollment_no']); ?></span>
                        </td>
                        <td><strong style="font-size: 14px; color: #0F172A;"><?= htmlspecialchars($row['full_name']); ?></strong><br><span style="font-size: 12px; color: #64748B;"><?= htmlspecialchars($row['email']); ?></span></td>
                        <td><span class="branch-badge"><?= htmlspecialchars($row['branch']); ?></span> <span style="font-size: 12px; color: var(--text-muted);">(<?= htmlspecialchars($row['batch_year']); ?>)</span></td>
                        <td><span class="cpi-pill <?= ($row['cpi']>=8.5)?'high':''; ?>">★ <?= number_format($row['cpi'], 2); ?></span></td>
                        <td>
                            <?php 
                            if ($row['is_detained']) {
                                echo "<span class='badge-status detained'><span class='status-dot'></span> ⛔ Detained</span>";
                            } else {
                                $stClass = ($row['placement_status']==='Placed') ? 'placed' : (($row['placement_status']==='In-Process') ? 'in-process' : 'unplaced');
                                echo "<span class='badge-status $stClass'><span class='status-dot'></span> {$row['placement_status']}</span>";
                            }
                            ?>
                        </td>
                        <td><?= $row['companies_placed'] ? "<strong style='color:#3B82F6;'>🏢 {$row['companies_placed']}</strong>" : "<span style='color:var(--text-muted);'>—</span>"; ?></td>
                        <td style="text-align: right; border-top-right-radius: 16px; border-bottom-right-radius: 16px;">
                            <button class="btn-action-icon dossier" style="padding: 7px 14px;">📄 View Dossier</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- =========================================================================
     POSITION 6: KPI COUNTER CARDS FOR COMPANIES
     ========================================================================= -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 28px;">
    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Total Corporate Partners</span>
            <div class="kpi-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #3B82F6;"><?= number_format($totalCompaniesCount); ?> Partners</div>
        <span class="kpi-badge info">Active Directory</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Total Drives Published</span>
            <div class="kpi-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #10B981;"><?= number_format($totalDrivesPosted); ?> Drives</div>
        <span class="kpi-badge up">🟢 Active Recruitment</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Students Hired</span>
            <div class="kpi-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #8B5CF6;"><?= number_format($totalStudentsHiredCount); ?> Placed</div>
        <span class="kpi-badge info" style="background:#F3E8FF; color:#7C3AED;">⭐ Corporate Selection</span>
    </div>

    <div class="kpi-card" style="padding: 18px 22px;">
        <div class="kpi-top">
            <span class="kpi-title">Top Compensation Package</span>
            <div class="kpi-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5H9.5a2.5 2.5 0 0 1 0-5H14M10 19.5h5.5a2.5 2.5 0 0 0 0-5H10"/></svg></div>
        </div>
        <div class="kpi-value" style="font-size: 24px; color: #F59E0B;">₹56.00 LPA</div>
        <span class="kpi-badge info" style="background:#FEF3C7; color:#B45309;">🏆 Peak CTC Record</span>
    </div>
</div>

<!-- =========================================================================
     POSITION 7: CORPORATE COMPANY-WISE PLACEMENT SECTION (WITH LEFT SIDE PIE CHART & RIGHT SIDE BAR CHART)
     ========================================================================= -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 28px;">
    <!-- LEFT SIDE: Company Placement Share Pie Chart (%) -->
    <div class="chart-card">
        <div class="card-header-flex" style="margin-bottom: 16px;">
            <div class="card-title">
                <h3 style="font-size: 17px; font-weight: 800; color: #0F172A;">📊 Company Placement Share (%)</h3>
            </div>
            <span class="kpi-badge info" style="background:#EEF2FF; color:#4F46E5;">Percentage Share</span>
        </div>

        <div style="height: 250px; position: relative;">
            <canvas id="companyPlacementPieChart"></canvas>
        </div>
    </div>

    <!-- RIGHT SIDE: Company Placement Headcount Bar Chart -->
    <div class="chart-card">
        <div class="card-header-flex" style="margin-bottom: 16px;">
            <div class="card-title">
                <h3 style="font-size: 17px; font-weight: 800; color: #0F172A;">🏢 Company-Wise Hired Candidates</h3>
            </div>
            <span class="kpi-badge info" style="background:#ECFDF5; color:#047857;">Headcount Hired</span>
        </div>

        <div style="height: 250px; position: relative;">
            <canvas id="companyPlacementChart"></canvas>
        </div>
    </div>
</div>

<!-- =========================================================================
     POSITION 8: LIST OF COMPANIES (CORPORATE RECRUITER DIRECTORY TABLE)
     ========================================================================= -->
<div class="table-card" style="padding: 24px; margin-bottom: 28px;">
    <div style="margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h4 style="font-weight: 800; font-size: 18px; color: #0F172A;">🏢 Corporate Recruiter Enterprises & Hiring Directory</h4>
            <p style="font-size: 13px; color: var(--text-muted);">Summary of recruiting partners, posted placement drives, selected candidate headcount, and top compensation packages.</p>
        </div>
        <span class="kpi-badge info" style="background:#EEF2FF; color:#4F46E5;"><?= count($companyStats); ?> Corporate Partners</span>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="min-width: 220px;">Corporate Recruiter Enterprise</th>
                    <th>Industry Vertical</th>
                    <th>HR Contact Person</th>
                    <th>HR Email Address</th>
                    <th>Drives Posted</th>
                    <th>Students Hired (Placed)</th>
                    <th style="text-align: right;">Top Package CTC</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companyStats as $cs): ?>
                    <tr style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px;">
                        <td style="border-top-left-radius: 16px; border-bottom-left-radius: 16px;">
                            <strong style="font-size: 15px; color: #0F172A;"><?= htmlspecialchars($cs['company_name']); ?></strong>
                        </td>
                        <td><span class="branch-badge"><?= htmlspecialchars($cs['industry']); ?></span></td>
                        <td><strong style="color:#334155;"><?= htmlspecialchars($cs['hr_name']); ?></strong></td>
                        <td><code style="color:#2563EB; font-weight:700;"><?= htmlspecialchars($cs['hr_email']); ?></code></td>
                        <td><span class="enrollment-pill"><?= $cs['drives_posted']; ?> Drives</span></td>
                        <td>
                            <span class="badge-status placed"><span class="status-dot"></span> <?= $cs['students_placed']; ?> Placed</span>
                        </td>
                        <td style="text-align: right; border-top-right-radius: 16px; border-bottom-right-radius: 16px;">
                            <strong style="color:#10B981; font-size:15px; font-weight:800;"><?= $cs['top_package'] ? htmlspecialchars($cs['top_package']) : 'Standard'; ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Full Size Dossier Modal Popup -->
<div class="modal-overlay" id="reportDossierModal">
    <div class="modal-box full-size">
        <div class="modal-header">
            <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Dr. Subhash University — Student Candidate Dossier</h3>
            <button class="close-modal" onclick="closeModal('reportDossierModal')">&times;</button>
        </div>
        <div id="reportDossierContent" class="dossier-scroll-area">
            <p style="color: var(--text-muted); text-align: center;">Loading candidate details...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Render Filtered Donut Chart (Student Cohort Section)
    const ctxDonut = document.getElementById('placementDistributionChart').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: ['Placed (<?= $countPlaced; ?>)', 'In-Process (<?= $countInProcess; ?>)', 'Unplaced (<?= $countUnplaced; ?>)', 'Detained (<?= $countDetained; ?>)'],
            datasets: [{
                data: [<?= $countPlaced; ?>, <?= $countInProcess; ?>, <?= $countUnplaced; ?>, <?= $countDetained; ?>],
                backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6B7280'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '76%',
            plugins: { legend: { display: false } }
        }
    });

    // 2. Render Filtered Branch Placement Bar Chart (Student Cohort Section)
    const ctxBar = document.getElementById('branchPlacementChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['CSE', 'IT', 'ECE', 'MECH', 'CIVIL'],
            datasets: [{
                label: 'Placement Rate (%)',
                data: [
                    <?= $branchRates['CSE']; ?>, 
                    <?= $branchRates['IT']; ?>, 
                    <?= $branchRates['ECE']; ?>, 
                    <?= $branchRates['MECH']; ?>, 
                    <?= $branchRates['CIVIL']; ?>
                ],
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#64748B'],
                borderRadius: 8,
                barThickness: 28
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) { return context.raw + '% Placement Rate'; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: function(val) { return val + '%'; }, font: { family: 'Plus Jakarta Sans', weight: '700' } },
                    grid: { color: '#F1F5F9' }
                },
                x: {
                    ticks: { font: { family: 'Plus Jakarta Sans', weight: '800' } },
                    grid: { display: false }
                }
            }
        }
    });

    // 3. Render Company Placement Share Pie Chart (Left Side of Corporate Section)
    const ctxCompPie = document.getElementById('companyPlacementPieChart').getContext('2d');
    new Chart(ctxCompPie, {
        type: 'pie',
        data: {
            labels: <?= json_encode($compNamesArr); ?>,
            datasets: [{
                data: <?= json_encode($compPercentagesArr); ?>,
                backgroundColor: ['#6366F1', '#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899'],
                borderWidth: 2,
                borderColor: '#FFFFFF'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', weight: '700' } } },
                tooltip: {
                    callbacks: {
                        label: function(context) { return context.label + ': ' + context.raw + '% Placement Share'; }
                    }
                }
            }
        }
    });

    // 4. Render Company-Wise Placement Bar Chart (Right Side of Corporate Section)
    const ctxComp = document.getElementById('companyPlacementChart').getContext('2d');
    new Chart(ctxComp, {
        type: 'bar',
        data: {
            labels: <?= json_encode($compNamesArr); ?>,
            datasets: [{
                label: 'Students Hired',
                data: <?= json_encode($compHiredArr); ?>,
                backgroundColor: ['#6366F1', '#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899'],
                borderRadius: 8,
                barThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) { return context.raw + ' Candidate(s) Hired'; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { family: 'Plus Jakarta Sans', weight: '700' } },
                    grid: { color: '#F1F5F9' }
                },
                x: {
                    ticks: { font: { family: 'Plus Jakarta Sans', weight: '800' } },
                    grid: { display: false }
                }
            }
        }
    });

    // Auto trigger dossier modal if searched via form
    <?php if ($enrollmentSearch): 
        $stmtSearch = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = ?");
        $stmtSearch->execute([$enrollmentSearch]);
        $searchedId = $stmtSearch->fetchColumn();
        if ($searchedId): ?>
            viewStudentDetails(<?= $searchedId; ?>);
        <?php endif; ?>
    <?php endif; ?>
});

function viewStudentDetails(studentId) {
    const container = document.getElementById('reportDossierContent');
    openModal('reportDossierModal');
    
    fetch('students.php?get_dossier=1&student_id=' + studentId)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}

function triggerQuickDossier() {
    const stId = document.getElementById('quickStudentSelect').value;
    if (!stId) {
        alert('Please select a student candidate from the dropdown.');
        return;
    }
    viewStudentDetails(stId);
}

function triggerEnrollmentSearch() {
    const code = document.getElementById('enrollmentInputCode').value.trim();
    if (!code) {
        alert('Please enter an enrollment number.');
        return;
    }
    
    fetch('students.php?get_dossier=1&student_id=0&enrollment_no=' + encodeURIComponent(code))
        .then(res => res.text())
        .then(html => {
            const container = document.getElementById('reportDossierContent');
            openModal('reportDossierModal');
            container.innerHTML = html;
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
