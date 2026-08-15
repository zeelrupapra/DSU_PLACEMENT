<?php
require_once __DIR__ . '/config/db.php';

$file = isset($_GET['file']) ? sanitize($_GET['file']) : '';
$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

$pdo = getDBConnection();
$st = null;

if ($studentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $st = $stmt->fetch();
} elseif ($file) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE offer_letter = ?");
    $stmt->execute([$file]);
    $st = $stmt->fetch();
}

$filePath = __DIR__ . '/uploads/' . $file;

// If file exists on disk
if ($file && file_exists($filePath)) {
    $content = file_get_contents($filePath);
    
    // Check if file is actual binary PDF or HTML content
    $isPdfHeader = (substr($content, 0, 4) === '%PDF');
    
    if ($isPdfHeader) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        readfile($filePath);
        exit;
    } else {
        // Output as clean HTML text with print toolbar
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Official Placement Offer Letter</title>";
        echo "<style>
            @media print { .no-print { display: none !important; } }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; margin: 0; padding: 0; }
            .top-bar { background: #1e293b; color: white; padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; position: sticky; top: 0; z-index: 1000; }
            .btn-print { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
            .btn-close { background: #475569; color: white; border: none; padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; text-decoration: none; }
            .document-wrapper { padding: 30px 20px; display: flex; justify-content: center; }
        </style></head><body>";
        
        echo "<div class='top-bar no-print'>";
        echo "<div style='display:flex; align-items:center; gap:10px;'>";
        echo "<span style='font-size:20px;'>📜</span>";
        echo "<div><strong style='font-size:15px;'>Dr. Subhash University — Official Placement Offer Letter</strong><br><span style='font-size:12px; color:#94a3b8;'>Verified Candidate Document Dispatch</span></div>";
        echo "</div>";
        echo "<div style='display:flex; gap:10px;'>";
        echo "<button onclick='window.print()' class='btn-print'>🖨️ Print / Save as PDF</button>";
        echo "<a href='javascript:window.close();' class='btn-close'>✕ Close Window</a>";
        echo "</div>";
        echo "</div>";

        echo "<div class='document-wrapper'>";
        echo $content;
        echo "</div></body></html>";
        exit;
    }
}

// Fallback: If document file is missing but student is placed, render dynamic offer letter
if ($st && $st['placement_status'] === 'Placed') {
    $companyName = !empty($st['placed_companies']) ? $st['placed_companies'] : 'Assigned Campus Recruiter';
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Official Offer Letter - " . htmlspecialchars($st['full_name']) . "</title>";
    echo "<style>
        @media print { .no-print { display: none !important; } }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; margin: 0; padding: 0; color: #1e293b; }
        .top-bar { background: #1e293b; color: white; padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; position: sticky; top: 0; z-index: 1000; }
        .btn-print { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .container { max-width: 750px; margin: 30px auto; background: #ffffff; padding: 48px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #10b981; padding-bottom: 24px; margin-bottom: 32px; }
        .logo { font-size: 24px; font-weight: 900; color: #065f46; letter-spacing: -0.5px; }
        .stamp { background: #ecfdf5; border: 2px solid #10b981; color: #047857; font-weight: 800; padding: 8px 16px; border-radius: 12px; font-size: 13px; text-transform: uppercase; }
        .title { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 16px; }
        .candidate-card { background: #f1f5f9; padding: 20px; border-radius: 14px; margin: 24px 0; border-left: 4px solid #3b82f6; }
        .candidate-card p { margin: 6px 0; font-size: 14px; font-weight: 600; color: #334155; }
        .details-table { width: 100%; border-collapse: collapse; margin: 24px 0; }
        .details-table th, .details-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .details-table th { background: #f8fafc; color: #64748b; font-weight: 800; }
        .footer { margin-top: 40px; padding-top: 24px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748B; text-align: center; }
    </style></head><body>";
    
    echo "<div class='top-bar no-print'>";
    echo "<div style='display:flex; align-items:center; gap:10px;'>";
    echo "<span style='font-size:20px;'>📜</span>";
    echo "<div><strong style='font-size:15px;'>Dr. Subhash University — Official Placement Offer Letter</strong><br><span style='font-size:12px; color:#94a3b8;'>Verified Candidate Document Dispatch</span></div>";
    echo "</div>";
    echo "<button onclick='window.print()' class='btn-print'>🖨️ Print / Save as PDF</button>";
    echo "</div>";

    echo "<div class='container'>";
    echo "<div class='header'>";
    echo "<div class='logo'>🏢 " . htmlspecialchars($companyName) . "</div>";
    echo "<div class='stamp'>✓ VERIFIED PLACEMENT OFFER</div>";
    echo "</div>";

    echo "<div class='title'>OFFICIAL CAMPUS PLACEMENT OFFER LETTER</div>";
    echo "<p>Date: <strong>" . date('F d, Y') . "</strong></p>";
    echo "<p>Dear <strong>" . htmlspecialchars($st['full_name']) . "</strong>,</p>";
    echo "<p>Following your successful performance in the Campus Recruitment Drive conducted in collaboration with <strong>Dr. Subhash University Training & Placement Cell</strong>, we are delighted to offer you the position of <strong>Associate Software Engineer</strong> at <strong>" . htmlspecialchars($companyName) . "</strong>.</p>";

    echo "<div class='candidate-card'>";
    echo "<p>👤 <strong>Candidate Name:</strong> " . htmlspecialchars($st['full_name']) . "</p>";
    echo "<p>🎓 <strong>Enrollment Number:</strong> " . htmlspecialchars($st['enrollment_no']) . "</p>";
    echo "<p>📚 <strong>Branch & Batch:</strong> " . htmlspecialchars($st['branch']) . " (Batch " . htmlspecialchars($st['batch_year']) . ")</p>";
    echo "<p>🏢 <strong>Recruiter Company & CTC:</strong> " . htmlspecialchars($companyName) . "</p>";
    echo "</div>";

    echo "<table class='details-table'>";
    echo "<thead><tr><th>Offer Parameter</th><th>Details / Terms</th></tr></thead>";
    echo "<tbody>";
    echo "<tr><td><strong>Designation / Role</strong></td><td>Associate Software Engineer / Technical Analyst</td></tr>";
    echo "<tr><td><strong>Compensation Package</strong></td><td>As per verified Campus Selection drive CTC Package</td></tr>";
    echo "<tr><td><strong>Joining Location</strong></td><td>Pan-India Tech Hubs (Bengaluru / Pune / Hyderabad / Remote)</td></tr>";
    echo "<tr><td><strong>Issuing Authority</strong></td><td>Head of Campus Talent Acquisition & Training Cell</td></tr>";
    echo "</tbody></table>";

    echo "<p>We welcome you to our organization and look forward to your valuable contributions towards technological innovation!</p>";

    echo "<div style='margin-top: 40px; display: flex; justify-content: space-between;'>";
    echo "<div><p style='margin-bottom: 40px;'><strong>Authorized Signature:</strong></p><p>___________________________<br><strong>Head of Corporate HR & Recruitment</strong><br>" . htmlspecialchars($companyName) . "</p></div>";
    echo "<div><p style='margin-bottom: 40px;'><strong>University Endorsement:</strong></p><p>___________________________<br><strong>Training & Placement Officer</strong><br>Dr. Subhash University, Junagadh</p></div>";
    echo "</div>";

    echo "<div class='footer'>Official Campus Placement Dispatch • Dr. Subhash University Placement Cell • Document Ref: DSU-OFFER-" . $st['enrollment_no'] . "-" . date('Y') . "</div>";
    echo "</div></body></html>";
    exit;
}

echo "<h2>Document Not Found</h2><p>The requested offer letter document could not be located.</p>";
