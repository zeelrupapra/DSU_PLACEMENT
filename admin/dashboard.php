<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();

$batchFilter = isset($_GET['year']) ? intval($_GET['year']) : 2026;

// Query conditions for batch year filter
$whereStudent = "WHERE is_detained = 0";
$wherePlaced = "WHERE placement_status = 'Placed' AND is_detained = 0";
$paramsStudent = [];
$paramsPlaced = [];

if ($batchFilter > 0) {
    $whereStudent .= " AND batch_year = ?";
    $wherePlaced .= " AND batch_year = ?";
    $paramsStudent[] = $batchFilter;
    $paramsPlaced[] = $batchFilter;
}

// Fetch live metrics from DB
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM students $whereStudent");
$stmtTotal->execute($paramsStudent);
$totalStudents = $stmtTotal->fetchColumn() ?: ($batchFilter === 2026 ? 5248 : ($batchFilter === 2025 ? 4820 : 4210));

$stmtPlaced = $pdo->prepare("SELECT COUNT(*) FROM students $wherePlaced");
$stmtPlaced->execute($paramsPlaced);
$placedStudents = $stmtPlaced->fetchColumn() ?: ($batchFilter === 2026 ? 2156 : ($batchFilter === 2025 ? 1940 : 1680));

$totalCompanies = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn() ?: 312;

// Fetch Department-wise Placed student counts from DB (RAW NUMBERS)
$branches = ['CSE', 'IT', 'ECE', 'MECH', 'CIVIL'];
$deptCounts = [];
foreach ($branches as $br) {
    $sqlBr = "SELECT COUNT(*) FROM students WHERE branch = ? AND placement_status = 'Placed' AND is_detained = 0";
    $pBr = [$br];
    if ($batchFilter > 0) {
        $sqlBr .= " AND batch_year = ?";
        $pBr[] = $batchFilter;
    }
    $stBr = $pdo->prepare($sqlBr);
    $stBr->execute($pBr);
    $deptCounts[$br] = intval($stBr->fetchColumn());
}

$sqlOthers = "SELECT COUNT(*) FROM students WHERE branch NOT IN ('CSE','IT','ECE','MECH','CIVIL') AND placement_status = 'Placed' AND is_detained = 0";
$pOthers = [];
if ($batchFilter > 0) {
    $sqlOthers .= " AND batch_year = ?";
    $pOthers[] = $batchFilter;
}
$stOthers = $pdo->prepare($sqlOthers);
$stOthers->execute($pOthers);
$deptCounts['OTHERS'] = intval($stOthers->fetchColumn());

// Fallback student counts if DB has fewer entries for selected batch
if (array_sum($deptCounts) == 0) {
    if ($batchFilter === 2026) {
        $deptCounts = ['CSE' => 891, 'IT' => 523, 'ECE' => 394, 'MECH' => 248, 'CIVIL' => 121, 'OTHERS' => 86];
    } elseif ($batchFilter === 2025) {
        $deptCounts = ['CSE' => 780, 'IT' => 490, 'ECE' => 340, 'MECH' => 210, 'CIVIL' => 95, 'OTHERS' => 60];
    } else {
        $deptCounts = ['CSE' => 650, 'IT' => 410, 'ECE' => 290, 'MECH' => 180, 'CIVIL' => 80, 'OTHERS' => 40];
    }
}

$pageTitle = 'Admin Dashboard';
$currentPage = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<!-- KPI Summary Cards Row -->
<div class="kpi-grid">
    <div class="kpi-card" style="animation-delay: 0.05s;">
        <div class="kpi-top">
            <span class="kpi-title">Total Students</span>
            <div class="kpi-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($totalStudents); ?></div>
        <span class="kpi-badge up">↑ <?= ($batchFilter > 0 ? $batchFilter . ' Batch' : 'All Cohorts'); ?></span>
    </div>

    <div class="kpi-card" style="animation-delay: 0.1s;">
        <div class="kpi-top">
            <span class="kpi-title">Placed Students</span>
            <div class="kpi-icon green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($placedStudents); ?></div>
        <span class="kpi-badge up">↑ Placed</span>
    </div>

    <div class="kpi-card" style="animation-delay: 0.15s;">
        <div class="kpi-top">
            <span class="kpi-title">Average Package</span>
            <div class="kpi-icon orange">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
        </div>
        <div class="kpi-value">₹8.64 LPA</div>
        <span class="kpi-badge up">↑ 8.2%</span>
    </div>

    <div class="kpi-card" style="animation-delay: 0.2s;">
        <div class="kpi-top">
            <span class="kpi-title">Highest Package</span>
            <div class="kpi-icon purple">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
        </div>
        <div class="kpi-value">₹56.00 LPA</div>
        <span class="kpi-badge info">🏢 Microsoft</span>
    </div>

    <div class="kpi-card" style="animation-delay: 0.25s;">
        <div class="kpi-top">
            <span class="kpi-title">Companies</span>
            <div class="kpi-icon cyan">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
        </div>
        <div class="kpi-value"><?= number_format($totalCompanies); ?></div>
        <span class="kpi-badge up">↑ Active Drive</span>
    </div>
</div>

<!-- Main Analytics Grid 1: Line Chart & Donut Chart -->
<div class="dashboard-grid">
    <!-- Placement Overview Spline Chart -->
    <div class="chart-card" style="animation-delay: 0.3s;">
        <div class="card-header-flex">
            <div class="card-title">
                <h3>Placement Overview</h3>
                <p style="font-size: 12px; color: var(--text-muted);">Total Placed Candidates Growth (Numbers)</p>
            </div>
            <form method="GET" style="margin: 0;">
                <select name="year" class="select-pill" onchange="this.form.submit()" style="cursor: pointer; font-weight: 800; padding: 8px 16px;">
                    <option value="2026" <?= ($batchFilter === 2026) ? 'selected' : ''; ?>>📅 2026 Batch</option>
                    <option value="2025" <?= ($batchFilter === 2025) ? 'selected' : ''; ?>>📅 2025 Batch</option>
                    <option value="2024" <?= ($batchFilter === 2024) ? 'selected' : ''; ?>>📅 2024 Batch</option>
                    <option value="0" <?= ($batchFilter === 0) ? 'selected' : ''; ?>>🌐 All Batch Years</option>
                </select>
            </form>
        </div>

        <div class="metric-highlight">
            <span class="big-num"><?= number_format($placedStudents); ?></span>
            <span class="kpi-badge up">↑ Total Placed</span>
        </div>

        <div style="height: 250px; position: relative;">
            <canvas id="placementOverviewChart"></canvas>
        </div>
    </div>

    <!-- Department Wise Placement Donut / Pie Chart with Center Raw Number -->
    <div class="chart-card">
        <div class="card-header-flex">
            <div class="card-title">
                <h3>Department Wise Placement</h3>
                <p style="font-size: 12px; color: var(--text-muted);">Placed Candidates Count by Branch</p>
            </div>
        </div>

        <!-- Donut Container with Center Raw Number Overlay -->
        <div class="donut-center-container" style="height: 210px;">
            <canvas id="deptPlacementChart"></canvas>
            <div class="donut-center-text">
                <div class="big-percent" style="font-size: 26px; color: #10B981; font-weight: 800;"><?= number_format($placedStudents); ?></div>
                <div class="sub-label" style="font-size: 11px; font-weight: 700; color: #64748B;">Total Placed</div>
            </div>
        </div>

        <!-- Indicator Legend Grid with Raw Student Numbers -->
        <div class="dept-grid-legend" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color);">
            <div class="dept-pill-item" style="padding: 10px 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; white-space: nowrap;">
                <span class="dept-left" style="font-weight: 800; font-size: 13px; color: #334155; display: flex; align-items: center; gap: 8px; white-space: nowrap;"><span class="dot-indicator" style="background: #3B82F6; flex-shrink: 0;"></span> CSE</span>
                <span class="dept-val" style="font-weight: 800; font-size: 13px; color: #0F172A; white-space: nowrap;"><?= number_format($deptCounts['CSE']); ?> Placed</span>
            </div>
            <div class="dept-pill-item" style="padding: 10px 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; white-space: nowrap;">
                <span class="dept-left" style="font-weight: 800; font-size: 13px; color: #334155; display: flex; align-items: center; gap: 8px; white-space: nowrap;"><span class="dot-indicator" style="background: #8B5CF6; flex-shrink: 0;"></span> IT</span>
                <span class="dept-val" style="font-weight: 800; font-size: 13px; color: #0F172A; white-space: nowrap;"><?= number_format($deptCounts['IT']); ?> Placed</span>
            </div>
            <div class="dept-pill-item" style="padding: 10px 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; white-space: nowrap;">
                <span class="dept-left" style="font-weight: 800; font-size: 13px; color: #334155; display: flex; align-items: center; gap: 8px; white-space: nowrap;"><span class="dot-indicator" style="background: #F59E0B; flex-shrink: 0;"></span> ECE</span>
                <span class="dept-val" style="font-weight: 800; font-size: 13px; color: #0F172A; white-space: nowrap;"><?= number_format($deptCounts['ECE']); ?> Placed</span>
            </div>
            <div class="dept-pill-item" style="padding: 10px 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; white-space: nowrap;">
                <span class="dept-left" style="font-weight: 800; font-size: 13px; color: #334155; display: flex; align-items: center; gap: 8px; white-space: nowrap;"><span class="dot-indicator" style="background: #14B8A6; flex-shrink: 0;"></span> MECH</span>
                <span class="dept-val" style="font-weight: 800; font-size: 13px; color: #0F172A; white-space: nowrap;"><?= number_format($deptCounts['MECH']); ?> Placed</span>
            </div>
            <div class="dept-pill-item" style="padding: 10px 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; white-space: nowrap;">
                <span class="dept-left" style="font-weight: 800; font-size: 13px; color: #334155; display: flex; align-items: center; gap: 8px; white-space: nowrap;"><span class="dot-indicator" style="background: #06B6D4; flex-shrink: 0;"></span> CIVIL</span>
                <span class="dept-val" style="font-weight: 800; font-size: 13px; color: #0F172A; white-space: nowrap;"><?= number_format($deptCounts['CIVIL']); ?> Placed</span>
            </div>
            <div class="dept-pill-item" style="padding: 10px 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; white-space: nowrap;">
                <span class="dept-left" style="font-weight: 800; font-size: 13px; color: #334155; display: flex; align-items: center; gap: 8px; white-space: nowrap;"><span class="dot-indicator" style="background: #EC4899; flex-shrink: 0;"></span> OTHERS</span>
                <span class="dept-val" style="font-weight: 800; font-size: 13px; color: #0F172A; white-space: nowrap;"><?= number_format($deptCounts['OTHERS']); ?> Placed</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Analytics Grid 2: Bar Chart & Package Distribution -->
<div class="dashboard-grid">
    <!-- Recruitment Trend Bar Chart -->
    <div class="chart-card">
        <div class="card-header-flex">
            <div class="card-title">
                <h3>Recruitment Trend</h3>
            </div>
            <div style="display: flex; gap: 16px; font-size: 13px; font-weight: 700;">
                <span style="color: #3B82F6;">■ Companies</span>
                <span style="color: #06B6D4;">■ Offers</span>
            </div>
        </div>

        <div style="height: 240px; position: relative;">
            <canvas id="recruitmentTrendChart"></canvas>
        </div>
    </div>

    <!-- Package Distribution Progress Bars -->
    <div class="chart-card">
        <div class="card-header-flex">
            <div class="card-title">
                <h3>Package Distribution</h3>
            </div>
        </div>

        <div class="package-list">
            <div class="package-row">
                <div class="package-info">
                    <span>₹20+ LPA</span>
                    <span style="color: #3B82F6;">272 Students</span>
                </div>
                <div class="package-bar-bg">
                    <div class="package-bar-fill" style="width: 12.6%; background: #3B82F6;"></div>
                </div>
            </div>

            <div class="package-row">
                <div class="package-info">
                    <span>₹12-20 LPA</span>
                    <span style="color: #06B6D4;">610 Students</span>
                </div>
                <div class="package-bar-bg">
                    <div class="package-bar-fill" style="width: 28.3%; background: #06B6D4;"></div>
                </div>
            </div>

            <div class="package-row">
                <div class="package-info">
                    <span>₹8-12 LPA</span>
                    <span style="color: #F59E0B;">672 Students</span>
                </div>
                <div class="package-bar-bg">
                    <div class="package-bar-fill" style="width: 31.2%; background: #F59E0B;"></div>
                </div>
            </div>

            <div class="package-row">
                <div class="package-info">
                    <span>₹5-8 LPA</span>
                    <span style="color: #EC4899;">386 Students</span>
                </div>
                <div class="package-bar-bg">
                    <div class="package-bar-fill" style="width: 17.9%; background: #EC4899;"></div>
                </div>
            </div>

            <div class="package-row">
                <div class="package-info">
                    <span>Below ₹5 LPA</span>
                    <span style="color: #8B5CF6;">216 Students</span>
                </div>
                <div class="package-bar-bg">
                    <div class="package-bar-fill" style="width: 10.0%; background: #8B5CF6;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Placement Overview Line Chart (NUMERICAL CANDIDATE COUNTS)
    const ctx1 = document.getElementById('placementOverviewChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Placed Candidates',
                data: [350, 420, 480, 560, 680, 840, 1050, 1280, 1540, 1820, 2050, <?= intval($placedStudents); ?>],
                borderColor: '#3B82F6',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                backgroundColor: (context) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
                    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.25)');
                    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
                    return gradient;
                },
                pointBackgroundColor: '#3B82F6',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1400,
                easing: 'easeOutQuart'
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw + ' Placed Candidates';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { min: 0, ticks: { callback: v => v } }
            }
        }
    });

    // 2. Department Wise Placement Donut Chart (RAW STUDENT COUNTS)
    const ctx2 = document.getElementById('deptPlacementChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['CSE', 'IT', 'ECE', 'MECH', 'CIVIL', 'OTHERS'],
            datasets: [{
                data: [<?= $deptCounts['CSE']; ?>, <?= $deptCounts['IT']; ?>, <?= $deptCounts['ECE']; ?>, <?= $deptCounts['MECH']; ?>, <?= $deptCounts['CIVIL']; ?>, <?= $deptCounts['OTHERS']; ?>],
                backgroundColor: ['#3B82F6', '#8B5CF6', '#F59E0B', '#14B8A6', '#06B6D4', '#EC4899'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '76%',
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1400,
                easing: 'easeOutQuart'
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + ' Placed Candidates';
                        }
                    }
                }
            }
        }
    });

    // 3. Recruitment Trend Bar Chart
    const ctx3 = document.getElementById('recruitmentTrendChart').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Companies',
                    data: [38, 25, 45, 30, 47, 28, 55, 39, 21, 60, 98, 58],
                    backgroundColor: '#3B82F6',
                    borderRadius: 6
                },
                {
                    label: 'Offers',
                    data: [65, 38, 62, 45, 68, 42, 80, 56, 32, 88, 120, 75],
                    backgroundColor: '#06B6D4',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1400,
                easing: 'easeOutQuart'
            },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#F1F5F9' } }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
