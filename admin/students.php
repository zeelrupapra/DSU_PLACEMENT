<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDBConnection();

// =========================================================================
// CRITICAL AJAX ENDPOINT FOR SINGLE STUDENT DOSSIER POPUP (MUST RUN BEFORE HEADER)
// =========================================================================
if (isset($_GET['get_dossier'])) {
    $stId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
    $enrollNo = isset($_GET['enrollment_no']) ? sanitize($_GET['enrollment_no']) : '';

    if ($stId > 0) {
        $st = $pdo->query("SELECT * FROM students WHERE id = $stId")->fetch();
    } elseif ($enrollNo !== '') {
        $stmtSearch = $pdo->prepare("SELECT * FROM students WHERE enrollment_no = ?");
        $stmtSearch->execute([$enrollNo]);
        $st = $stmtSearch->fetch();
        if ($st) { $stId = $st['id']; }
    } else {
        $st = false;
    }
    
    if (!$st) {
        echo "<div style='padding: 40px; text-align: center; color: #EF4444; font-weight: 800; font-size: 15px;'>⚠️ Student candidate record not found. Please verify the enrollment number.</div>";
        exit;
    }

    $apps = $pdo->query("SELECT a.*, d.title, d.package_ctc, c.company_name FROM applications a JOIN drives d ON a.drive_id = d.id JOIN companies c ON d.company_id = c.id WHERE a.student_id = $stId ORDER BY a.id DESC")->fetchAll();
    $internships = $pdo->query("SELECT * FROM internships WHERE student_id = $stId ORDER BY id DESC")->fetchAll();

    $photoSrc = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
        ? '../uploads/' . $st['profile_photo'] 
        : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';

    ?>
    <div style="display: flex; flex-direction: column; gap: 20px; width: 100%; box-sizing: border-box;">
        
        <!-- =========================================================================
             1. EXECUTIVE HERO PROFILE BANNER (THEME-MATCHED DARK GRADIENT)
             ========================================================================= -->
        <div style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: 24px; padding: 28px 32px; color: white; box-shadow: 0 16px 36px -10px rgba(15, 23, 42, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); position: relative; overflow: hidden;">
            <div style="position: absolute; right: -40px; top: -40px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(59,130,246,0.2) 0%, rgba(59,130,246,0) 70%); border-radius: 50%; pointer-events: none;"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; position: relative; z-index: 2;">
                <!-- Left: Avatar & Candidate Identity -->
                <div style="display: flex; gap: 22px; align-items: center; flex-wrap: wrap;">
                    <div style="position: relative;">
                        <img src="<?= $photoSrc; ?>" alt="Candidate Avatar" style="width: 104px; height: 104px; border-radius: 50%; object-fit: cover; border: 4px solid #3B82F6; box-shadow: 0 8px 24px rgba(59,130,246,0.35);">
                        <span style="position: absolute; bottom: 4px; right: 4px; width: 20px; height: 20px; border-radius: 50%; background: <?= ($st['placement_status']==='Placed'?'#10B981':($st['placement_status']==='In-Process'?'#F59E0B':'#64748B')); ?>; border: 3px solid #0F172A;" title="Status: <?= htmlspecialchars($st['placement_status']); ?>"></span>
                    </div>

                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                            <h2 style="font-size: 24px; font-weight: 900; color: #FFFFFF; letter-spacing: -0.5px; margin: 0;">
                                <?= htmlspecialchars($st['full_name']); ?>
                            </h2>
                            <?php if ($st['is_detained']): ?>
                                <span class="badge-status detained" style="padding: 4px 12px; font-size: 11px; font-weight: 800;">⛔ DETAINED</span>
                            <?php else: ?>
                                <?php $stCls = ($st['placement_status']==='Placed')?'placed':(($st['placement_status']==='In-Process')?'in-process':'unplaced'); ?>
                                <span class="badge-status <?= $stCls; ?>" style="padding: 4px 12px; font-size: 11.5px; font-weight: 800;">
                                    <?= htmlspecialchars($st['placement_status']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Metadata Badges Row -->
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <span style="background: rgba(59,130,246,0.2); border: 1px solid rgba(59,130,246,0.35); color: #93C5FD; padding: 4px 10px; border-radius: 8px; font-family: monospace; font-size: 12px; font-weight: 800;">
                                🆔 <?= htmlspecialchars($st['enrollment_no']); ?>
                            </span>
                            <span style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #F1F5F9; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700;">
                                🎓 <?= htmlspecialchars($st['branch']); ?> Engineering
                            </span>
                            <span style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #F1F5F9; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700;">
                                📅 Batch <?= htmlspecialchars($st['batch_year']); ?>
                            </span>
                            <span style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #F1F5F9; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700;">
                                📚 Semester <?= htmlspecialchars($st['current_semester'] ?: 7); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Action Contacts & Buttons -->
                <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php if (!empty($st['linkedin_url'])): ?>
                            <a href="<?= htmlspecialchars($st['linkedin_url']); ?>" target="_blank" class="btn-secondary" style="padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 10px; background: rgba(14, 118, 168, 0.25); color: #7DD3FC; border: 1px solid rgba(56, 189, 248, 0.4); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                LinkedIn Profile ↗
                            </a>
                        <?php endif; ?>

                        <a href="mailto:<?= htmlspecialchars($st['email']); ?>" class="btn-secondary" style="padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 10px; background: rgba(255,255,255,0.1); color: #FFFFFF; border: 1px solid rgba(255,255,255,0.2); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            ✉️ <?= htmlspecialchars($st['email']); ?>
                        </a>

                        <a href="tel:<?= htmlspecialchars($st['phone']); ?>" class="btn-secondary" style="padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 10px; background: rgba(255,255,255,0.1); color: #FFFFFF; border: 1px solid rgba(255,255,255,0.2); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            📞 <?= htmlspecialchars($st['phone']); ?>
                        </a>
                    </div>

                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="../uploads/<?= htmlspecialchars($st['resume_file']); ?>" target="_blank" class="btn-primary" style="padding: 9px 16px; font-size: 12.5px; font-weight: 800; border-radius: 12px; background: #3B82F6; box-shadow: 0 4px 14px rgba(59,130,246,0.3); text-decoration: none;">
                            📥 Download Resume PDF
                        </a>

                        <?php if (!empty($st['offer_letter'])): ?>
                            <a href="../view_offer.php?file=<?= urlencode($st['offer_letter']); ?>" target="_blank" class="btn-primary" style="padding: 9px 16px; font-size: 12.5px; font-weight: 800; border-radius: 12px; background: #059669; border: none; box-shadow: 0 4px 14px rgba(5,150,105,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                📜 View Official Offer Letter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             2. FOUR MINI-KPI GRADIENT HIGHLIGHT CARDS
             ========================================================================= -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <!-- CPI Card -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 18px; padding: 18px 20px; box-shadow: 0 4px 14px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    ⭐
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748B; letter-spacing: 0.5px; display: block;">Academic CPI</span>
                    <strong style="font-size: 20px; font-weight: 900; color: #1E40AF;">★ <?= number_format($st['cpi'], 2); ?></strong>
                    <span style="font-size: 11px; color: #10B981; font-weight: 700; display: block;"><?= ($st['cpi']>=9.0 ? 'Top Tier Honors' : ($st['cpi']>=8.0 ? 'First Class Distinction' : 'First Class')); ?></span>
                </div>
            </div>

            <!-- Backlogs Card -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 18px; padding: 18px 20px; box-shadow: 0 4px 14px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: <?= ($st['backlogs']>0?'#FEF2F2':'#ECFDF5'); ?>; color: <?= ($st['backlogs']>0?'#DC2626':'#059669'); ?>; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    <?= ($st['backlogs']>0?'⚠️':'✓'); ?>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748B; letter-spacing: 0.5px; display: block;">Backlog Status</span>
                    <strong style="font-size: 20px; font-weight: 900; color: <?= ($st['backlogs']>0?'#DC2626':'#059669'); ?>;"><?= $st['backlogs']; ?> Backlogs</strong>
                    <span style="font-size: 11px; color: #64748B; font-weight: 600; display: block;"><?= ($st['backlogs']==0 ? 'Clean Academic Slate' : 'Pending Clearance'); ?></span>
                </div>
            </div>

            <!-- Placement / Package Card -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 18px; padding: 18px 20px; box-shadow: 0 4px 14px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    🎉
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748B; letter-spacing: 0.5px; display: block;">Placement Package</span>
                    <?php if ($st['placement_status'] === 'Placed' && $st['placed_companies']): ?>
                        <strong style="font-size: 17px; font-weight: 900; color: #059669;"><?= htmlspecialchars($st['placed_companies']); ?></strong>
                    <?php else: ?>
                        <strong style="font-size: 17px; font-weight: 900; color: #64748B;"><?= htmlspecialchars($st['placement_status']); ?></strong>
                    <?php endif; ?>
                    <span style="font-size: 11px; color: #10B981; font-weight: 700; display: block;">Official Campus Status</span>
                </div>
            </div>

            <!-- Drives Registered Card -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 18px; padding: 18px 20px; box-shadow: 0 4px 14px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #F5F3FF; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    💼
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748B; letter-spacing: 0.5px; display: block;">Drive Submissions</span>
                    <strong style="font-size: 20px; font-weight: 900; color: #6D28D9;"><?= count($apps); ?> Applications</strong>
                    <span style="font-size: 11px; color: #64748B; font-weight: 600; display: block;"><?= count($internships); ?> Internships Completed</span>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             3. INTERACTIVE SECTION TABS NAVIGATION
             ========================================================================= -->
        <div style="display: flex; gap: 10px; border-bottom: 2px solid #E2E8F0; padding-bottom: 8px; margin-top: 4px;">
            <button type="button" class="dossier-tab-btn active" onclick="switchDossierTab('tabDrives', this)" style="padding: 10px 18px; border-radius: 12px; font-weight: 800; font-size: 13px; cursor: pointer; border: none; background: #2563EB; color: white; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                💼 Campus Placements & Drives (<?= count($apps); ?>)
            </button>
            <button type="button" class="dossier-tab-btn" onclick="switchDossierTab('tabResume', this)" style="padding: 10px 18px; border-radius: 12px; font-weight: 800; font-size: 13px; cursor: pointer; border: 1px solid #CBD5E1; background: #FFFFFF; color: #475569; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                📄 Live Resume Document
            </button>
            <button type="button" class="dossier-tab-btn" onclick="switchDossierTab('tabInternships', this)" style="padding: 10px 18px; border-radius: 12px; font-weight: 800; font-size: 13px; cursor: pointer; border: 1px solid #CBD5E1; background: #FFFFFF; color: #475569; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                🎓 Industrial Internships (<?= count($internships); ?>)
            </button>
            <button type="button" class="dossier-tab-btn" onclick="switchDossierTab('tabWorkshops', this)" style="padding: 10px 18px; border-radius: 12px; font-weight: 800; font-size: 13px; cursor: pointer; border: 1px solid #CBD5E1; background: #FFFFFF; color: #475569; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                🏛️ Workshops & Events
            </button>
        </div>

        <!-- =========================================================================
             4. TAB CONTENTS
             ========================================================================= -->

        <!-- TAB 1: CAMPUS PLACEMENTS & APPLICATIONS -->
        <div id="tabDrives" class="dossier-tab-content" style="display: flex; flex-direction: column; gap: 18px;">
            <!-- Official Placement Banner if Placed -->
            <?php if ($st['placement_status'] === 'Placed' && $st['placed_companies']): ?>
                <div style="background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); border: 1.5px solid #86EFAC; border-radius: 18px; padding: 22px 26px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <div style="width: 52px; height: 52px; border-radius: 14px; background: #10B981; color: white; display: flex; align-items: center; justify-content: center; font-size: 26px; box-shadow: 0 4px 14px rgba(16,185,129,0.3);">
                            🏢
                        </div>
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #065F46; letter-spacing: 0.5px;">Official Selected Recruiter Placement</span>
                            <h3 style="font-size: 20px; font-weight: 900; color: #064E3B; margin: 2px 0;"><?= htmlspecialchars($st['placed_companies']); ?></h3>
                            <p style="font-size: 13px; color: #047857; margin: 0; font-weight: 700;">Verified Campus Placement Dispatch • Training & Placement Cell</p>
                        </div>
                    </div>

                    <?php if (!empty($st['offer_letter'])): ?>
                        <a href="../view_offer.php?file=<?= urlencode($st['offer_letter']); ?>" target="_blank" class="btn-primary" style="padding: 11px 20px; font-size: 13px; font-weight: 800; border-radius: 12px; background: #059669; border: none; box-shadow: 0 4px 12px rgba(5,150,105,0.3); text-decoration: none;">
                            📜 View Official Offer Letter (PDF) ↗
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Drive Applications Table -->
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                        💼 Drive Registration & Selection Rounds History
                    </h4>
                    <span style="font-size: 12px; font-weight: 700; color: #64748B;">Showing <?= count($apps); ?> Drive Applications</span>
                </div>

                <?php if ($apps): ?>
                    <div class="table-responsive">
                        <table class="custom-table" style="font-size: 13px; min-width: 100%;">
                            <thead>
                                <tr style="background: #F8FAFC;">
                                    <th style="padding: 12px 14px;">Recruiter Company</th>
                                    <th style="padding: 12px 14px;">Placement Drive Title</th>
                                    <th style="padding: 12px 14px;">Package CTC</th>
                                    <th style="padding: 12px 14px;">Current Pipeline Stage</th>
                                    <th style="padding: 12px 14px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apps as $ap): 
                                    $badgeCls = ($ap['status']==='Selected')?'placed':(($ap['status']==='Rejected')?'unplaced':'in-process');
                                ?>
                                    <tr style="background: white; border: 1px solid #E2E8F0;">
                                        <td style="padding: 14px 14px;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="width: 34px; height: 34px; border-radius: 8px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800;">
                                                    🏢
                                                </div>
                                                <strong style="color: #0F172A; font-weight: 800; font-size: 14px;"><?= htmlspecialchars($ap['company_name']); ?></strong>
                                            </div>
                                        </td>
                                        <td style="padding: 14px 14px; color: #334155; font-weight: 600;"><?= htmlspecialchars($ap['title']); ?></td>
                                        <td style="padding: 14px 14px;"><strong style="color: #10B981; font-weight: 800; font-size: 13.5px;"><?= htmlspecialchars($ap['package_ctc']); ?></strong></td>
                                        <td style="padding: 14px 14px;"><span class="badge-status <?= $badgeCls; ?>" style="font-weight: 800;"><?= htmlspecialchars($ap['status']); ?></span></td>
                                        <td style="padding: 14px 14px; text-align: right;">
                                            <?php if ($ap['status'] === 'Selected'): ?>
                                                <span style="color: #10B981; font-weight: 800; font-size: 12px;">🟢 Selected</span>
                                            <?php else: ?>
                                                <button type="button" onclick="triggerOfferUploadModal({dataset:{id:<?= $st['id']; ?>, name:'<?= htmlspecialchars($st['full_name'], ENT_QUOTES); ?>', company:'<?= htmlspecialchars($ap['company_name'], ENT_QUOTES); ?>'}})" class="btn-action-icon place" style="padding: 6px 12px; height: 32px; font-size: 11.5px; border-radius: 8px; font-weight: 800; cursor: pointer;">
                                                    ✓ Select
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; background: #F8FAFC; border-radius: 14px; border: 1px dashed #CBD5E1;">
                        <p style="color: #64748B; font-size: 13.5px; margin: 0; font-weight: 600;">No placement drive applications recorded for this student candidate yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 2: LIVE RESUME VIEWER -->
        <div id="tabResume" class="dossier-tab-content" style="display: none; flex-direction: column; gap: 16px;">
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                        📄 Candidate Resume Document Viewer
                    </h4>
                    <div style="display: flex; gap: 8px;">
                        <a href="../uploads/<?= htmlspecialchars($st['resume_file']); ?>" target="_blank" class="btn-secondary" style="padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 10px; text-decoration: none;">
                            Open in Full Window ↗
                        </a>
                        <a href="../uploads/<?= htmlspecialchars($st['resume_file']); ?>" download class="btn-primary" style="padding: 8px 16px; font-size: 12px; font-weight: 800; border-radius: 10px; text-decoration: none;">
                            📥 Download File
                        </a>
                    </div>
                </div>

                <?php if (file_exists(__DIR__ . '/../uploads/' . $st['resume_file'])): ?>
                    <iframe src="../uploads/<?= htmlspecialchars($st['resume_file']); ?>" style="width: 100%; height: 480px; border: 1px solid #E2E8F0; border-radius: 14px;"></iframe>
                <?php else: ?>
                    <div style="background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 16px; padding: 48px 20px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">📄</div>
                        <h4 style="font-size: 16px; font-weight: 800; color: #0F172A;">Resume File: <?= htmlspecialchars($st['resume_file']); ?></h4>
                        <p style="color: #64748B; font-size: 13px; margin-top: 4px; margin-bottom: 16px;">Verified candidate resume PDF is registered on the university server.</p>
                        <a href="../uploads/<?= htmlspecialchars($st['resume_file']); ?>" target="_blank" class="btn-primary" style="padding: 10px 22px; font-size: 13px; font-weight: 800; border-radius: 12px; text-decoration: none;">Open File ↗</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 3: INDUSTRIAL INTERNSHIPS -->
        <div id="tabInternships" class="dossier-tab-content" style="display: none; flex-direction: column; gap: 16px;">
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                        🎓 Industrial Internships & Practical Training Experience
                    </h4>
                    <span style="font-size: 12px; font-weight: 700; color: #64748B;">Showing <?= count($internships); ?> Internships</span>
                </div>

                <?php if ($internships): ?>
                    <div class="table-responsive">
                        <table class="custom-table" style="font-size: 13px; min-width: 100%;">
                            <thead>
                                <tr style="background: #F8FAFC;">
                                    <th style="padding: 12px 14px;">Organization</th>
                                    <th style="padding: 12px 14px;">Internship Role</th>
                                    <th style="padding: 12px 14px;">Monthly Stipend</th>
                                    <th style="padding: 12px 14px;">Duration</th>
                                    <th style="padding: 12px 14px;">Faculty Verification</th>
                                    <th style="padding: 12px 14px; text-align: right;">Certificate Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($internships as $it): ?>
                                    <tr style="background: white; border: 1px solid #E2E8F0;">
                                        <td style="padding: 14px 14px;"><strong style="color: #0F172A; font-weight: 800; font-size: 14px;">🏢 <?= htmlspecialchars($it['company_name']); ?></strong></td>
                                        <td style="padding: 14px 14px; color: #334155; font-weight: 600;"><?= htmlspecialchars($it['title']); ?></td>
                                        <td style="padding: 14px 14px;"><strong style="color: #10B981; font-weight: 800;"><?= htmlspecialchars($it['stipend']); ?></strong></td>
                                        <td style="padding: 14px 14px; color: #64748B; font-size: 12px;"><?= htmlspecialchars($it['start_date']); ?> to <?= htmlspecialchars($it['end_date']); ?></td>
                                        <td style="padding: 14px 14px;">
                                            <span class="badge-status placed" style="font-size: 11px; font-weight: 800; background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">
                                                ✓ <?= htmlspecialchars($it['verification_status'] ?: 'Verified'); ?> (<?= $it['credit_points'] ?: 3; ?> Credits)
                                            </span>
                                        </td>
                                        <td style="padding: 14px 14px; text-align: right;">
                                            <a href="../uploads/<?= htmlspecialchars($it['certificate_file']); ?>" target="_blank" style="color: #2563EB; font-weight: 800; text-decoration: none; background: #EFF6FF; padding: 6px 12px; border-radius: 8px; border: 1px solid #BFDBFE; display: inline-flex; align-items: center; gap: 4px;">
                                                📜 View Certificate
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; background: #F8FAFC; border-radius: 14px; border: 1px dashed #CBD5E1;">
                        <p style="color: #64748B; font-size: 13.5px; margin: 0; font-weight: 600;">No industrial internship records submitted yet for this student.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 4: WORKSHOPS & EVENTS -->
        <div id="tabWorkshops" class="dossier-tab-content" style="display: none; flex-direction: column; gap: 16px;">
            <div style="background: white; border: 1px solid #E2E8F0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                        🏛️ Workshop, Seminar & Technical Training Attendance
                    </h4>
                </div>

                <?php 
                    $wkRegs = $pdo->query("SELECT wr.*, w.title, w.event_date, w.speaker, w.venue FROM workshop_registrations wr JOIN workshops w ON wr.workshop_id = w.id WHERE wr.student_id = $stId ORDER BY wr.id DESC")->fetchAll();
                ?>
                <?php if (!empty($wkRegs)): ?>
                    <div class="table-responsive">
                        <table class="custom-table" style="font-size: 13px; min-width: 100%;">
                            <thead>
                                <tr style="background: #F8FAFC;">
                                    <th style="padding: 12px 14px;">Event Title</th>
                                    <th style="padding: 12px 14px;">Keynote Speaker</th>
                                    <th style="padding: 12px 14px;">Schedule</th>
                                    <th style="padding: 12px 14px;">Attendance</th>
                                    <th style="padding: 12px 14px; text-align: right;">Report Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wkRegs as $wr): ?>
                                    <tr style="background: white; border: 1px solid #E2E8F0;">
                                        <td style="padding: 14px 14px;"><strong style="color: #0F172A; font-weight: 800;"><?= htmlspecialchars($wr['title']); ?></strong></td>
                                        <td style="padding: 14px 14px; color: #475569;"><?= htmlspecialchars($wr['speaker']); ?></td>
                                        <td style="padding: 14px 14px; color: #64748B; font-size: 12px;"><?= date('d M Y, h:i A', strtotime($wr['event_date'])); ?></td>
                                        <td style="padding: 14px 14px;"><span class="badge-status info"><?= htmlspecialchars($wr['attendance_status'] ?: 'Registered'); ?></span></td>
                                        <td style="padding: 14px 14px; text-align: right;">
                                            <?php if (!empty($wr['report_file']) && file_exists(__DIR__ . '/../uploads/' . $wr['report_file'])): ?>
                                                <a href="../uploads/<?= htmlspecialchars($wr['report_file']); ?>" target="_blank" style="color: #059669; font-weight: 800; text-decoration: none; background: #ECFDF5; padding: 6px 12px; border-radius: 8px; border: 1px solid #A7F3D0; display: inline-flex; align-items: center; gap: 4px;">📜 View Report</a>
                                            <?php else: ?>
                                                <span style="color: #94A3B8; font-size: 12px;">No Report Document</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; background: #F8FAFC; border-radius: 14px; border: 1px dashed #CBD5E1;">
                        <p style="color: #64748B; font-size: 13.5px; margin: 0; font-weight: 600;">No technical workshops or seminar events registered for this candidate.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- =========================================================================
             5. MODAL BOTTOM ADMIN FAST ACTION DOCK
             ========================================================================= -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #E2E8F0; margin-top: 6px; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 8px; align-items: center;">
                <span style="font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase;">Direct Admin Actions:</span>
                <?php if ($st['placement_status'] !== 'Placed'): ?>
                    <button type="button" class="btn-action-icon place" onclick="closeModal('studentDossierModal'); triggerOfferUploadModal({dataset:{id:<?= $st['id']; ?>, name:'<?= htmlspecialchars($st['full_name'], ENT_QUOTES); ?>', company:''}})" style="padding: 8px 16px; height: 38px; border-radius: 10px; font-weight: 800; background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; cursor: pointer;">
                        ✓ Mark Candidate Placed
                    </button>
                <?php else: ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Mark candidate as UNPLACED? This will clear placement status back to Unplaced.');">
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                        <input type="hidden" name="target_status" value="Unplaced">
                        <button type="submit" class="btn-action-icon unplace" style="padding: 8px 16px; height: 38px; border-radius: 10px; font-weight: 800; background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; cursor: pointer;">
                            ✗ Mark Unplaced
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($st['placement_status'] !== 'In-Process'): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Reset placement status back to default pipeline state (In-Process)?');">
                        <input type="hidden" name="action" value="reset_status">
                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                        <button type="submit" class="btn-action-icon reset" style="padding: 8px 16px; height: 38px; border-radius: 10px; font-weight: 800; cursor: pointer;">
                            ↺ Reset to In-Process
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <button type="button" class="btn-secondary" onclick="window.print()" style="padding: 8px 18px; height: 38px; border-radius: 10px; font-weight: 800; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                🖨️ Print Dossier Document
            </button>
        </div>

    </div>

    <!-- Client-side tab switcher script -->
    <script>
    function switchDossierTab(tabId, btn) {
        document.querySelectorAll('.dossier-tab-content').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.dossier-tab-btn').forEach(b => {
            b.style.background = '#FFFFFF';
            b.style.color = '#475569';
            b.style.border = '1px solid #CBD5E1';
        });
        const target = document.getElementById(tabId);
        if (target) target.style.display = 'flex';
        if (btn) {
            btn.style.background = '#2563EB';
            btn.style.color = '#FFFFFF';
            btn.style.border = 'none';
        }
    }
    </script>
    <?php
    exit;
}

// Download Sample CSV Import Template
if (isset($_GET['download_sample_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=student_import_sample_template.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['enrollment_no', 'full_name', 'email', 'phone', 'gender', 'branch', 'cpi', 'backlogs', 'batch_year', 'placement_status']);
    fputcsv($out, ['240130316099', 'Rohan Verma', 'rohan.verma@drsubhash.edu.in', '+91 98765 00099', 'Male', 'CSE', '8.50', '0', '2024', 'In-Process']);
    fputcsv($out, ['240130316100', 'Kavya Sharma', 'kavya.sharma@drsubhash.edu.in', '+91 98765 00100', 'Female', 'IT', '9.10', '0', '2024', 'Unplaced']);
    fclose($out);
    exit;
}

$msg = '';
$error = '';

// Process Status Change, Deletion, and Excel / CSV Import Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);

    if ($action === 'import_students') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($fileTmp, "r");

            if ($handle !== FALSE) {
                // Read header row
                $header = fgetcsv($handle, 1000, ",");

                $importedCount = 0;
                $skippedCount = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) < 10) {
                        $skippedCount++;
                        continue;
                    }

                    $enrollment = sanitize(trim($data[0]));
                    $fullName = sanitize(trim($data[1]));
                    $email = sanitize(trim($data[2]));
                    $phone = sanitize(trim($data[3]));
                    $gender = sanitize(trim($data[4]));
                    $branch = strtoupper(sanitize(trim($data[5])));
                    $cpi = floatval($data[6]);
                    $backlogs = intval($data[7]);
                    $batchYear = intval($data[8]);
                    $status = sanitize(trim($data[9]));

                    // Validation & Data Accuracy Checks
                    if (empty($enrollment) || empty($fullName) || empty($email)) {
                        $skippedCount++;
                        continue;
                    }

                    // Duplicate check for enrollment_no or email
                    $stmtChk = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = ? OR email = ?");
                    $stmtChk->execute([$enrollment, $email]);
                    if ($stmtChk->fetch()) {
                        $skippedCount++;
                        continue; // Skip existing duplicate
                    }

                    // Normalize valid choices
                    $validBranches = ['CSE', 'IT', 'ECE', 'MECH', 'CIVIL'];
                    if (!in_array($branch, $validBranches)) { $branch = 'CSE'; }
                    
                    $validStatuses = ['In-Process', 'Placed', 'Unplaced'];
                    if (!in_array($status, $validStatuses)) { $status = 'In-Process'; }

                    if ($gender !== 'Female') { $gender = 'Male'; }
                    if ($cpi < 0 || $cpi > 10) { $cpi = 7.50; }
                    if ($batchYear < 2020 || $batchYear > 2030) { $batchYear = date('Y'); }

                    // Create User Login Account
                    $defaultPass = password_hash('student123', PASSWORD_BCRYPT);
                    $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                    $stmtUser->execute([$enrollment, $defaultPass]);
                    $userId = $pdo->lastInsertId();

                    // Create Student Profile Record
                    $stmtSt = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, full_name, email, phone, gender, branch, cpi, backlogs, batch_year, placement_status, is_detained) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                    $stmtSt->execute([$userId, $enrollment, $fullName, $email, $phone, $gender, $branch, $cpi, $backlogs, $batchYear, $status]);
                    $importedCount++;
                }
                fclose($handle);

                $msg = "🎉 Excel/CSV Data Import Complete: $importedCount student candidates imported successfully! ($skippedCount records skipped due to duplicate enrollment numbers or missing fields).";
            } else {
                $error = "Unable to read the uploaded CSV file. Please upload a valid CSV template.";
            }
        } else {
            $error = "File upload failed. Please select a valid CSV file.";
        }
    } elseif ($action === 'change_status') {
        $id = intval($_POST['student_id']);
        $newStatus = sanitize($_POST['target_status']);

        if ($newStatus === 'Placed') {
            $check = canSelectStudentForCompany($pdo, $id);
            if (!$check['allowed']) {
                setFlashMessage($check['reason'], 'danger');
                header("Location: students.php");
                exit;
            }
            $stmt = $pdo->prepare("UPDATE students SET placement_status = 'Placed' WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage("Candidate placement status updated to: Placed", 'success');
        } elseif ($newStatus === 'Unplaced') {
            // Clear single placement lock, offer letter, and placed company tags
            $pdo->prepare("UPDATE students SET placement_status = 'Unplaced', placed_companies = NULL, offer_letter = NULL WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE applications SET status = 'Rejected' WHERE student_id = ? AND status = 'Selected'")->execute([$id]);
            setFlashMessage("Candidate status toggled to Unplaced. Single placement lock and offer letter reset successfully.", 'warning');
        } else {
            $stmt = $pdo->prepare("UPDATE students SET placement_status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            setFlashMessage("Candidate placement status updated to: $newStatus", 'info');
        }
        header("Location: students.php");
        exit;
    } elseif ($action === 'reset_status') {
        $id = intval($_POST['student_id']);
        $pdo->prepare("UPDATE students SET placement_status = 'In-Process', placed_companies = NULL, offer_letter = NULL WHERE id = ?")->execute([$id]);
        $pdo->prepare("UPDATE applications SET status = 'Applied' WHERE student_id = ? AND status = 'Selected'")->execute([$id]);
        setFlashMessage("Candidate placement status reset to default pipeline state: In-Process", 'info');
        header("Location: students.php");
        exit;
    } elseif ($action === 'upload_offer_letter') {
        $stId = intval($_POST['student_id']);
        $companyName = sanitize($_POST['company_name']);
        if (empty($companyName)) {
            $companyName = 'Assigned Campus Recruiter';
        }

        $check = canSelectStudentForCompany($pdo, $stId);
        if (!$check['allowed']) {
            setFlashMessage($check['reason'], 'danger');
            header("Location: students.php");
            exit;
        }

        $stmtSt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmtSt->execute([$stId]);
        $st = $stmtSt->fetch();

        if ($st) {
            $fileName = $st['offer_letter']; // retain existing file if any

            if (isset($_FILES['offer_file']) && $_FILES['offer_file']['error'] === UPLOAD_ERR_OK) {
                $fileExt = strtolower(pathinfo($_FILES['offer_file']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['pdf', 'docx', 'doc', 'png', 'jpg', 'jpeg'];

                if (in_array($fileExt, $allowedExts)) {
                    $newFileName = 'offer_letter_' . $st['enrollment_no'] . '_' . time() . '.' . $fileExt;
                    $targetPath = __DIR__ . '/../uploads/' . $newFileName;

                    if (move_uploaded_file($_FILES['offer_file']['tmp_name'], $targetPath)) {
                        $fileName = $newFileName;

                        // Send automated email to student
                        $toEmail = $st['email'];
                        $subject = "🎉 Official Placement Offer Letter - Dr. Subhash University Placement Cell";
                        $bodyHtml = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #E2E8F0; border-radius: 16px; padding: 30px; background: #FFFFFF;'>
                            <h2 style='color: #2563EB; margin-top: 0;'>🎉 Congratulations " . htmlspecialchars($st['full_name']) . "!</h2>
                            <p style='font-size: 15px; color: #334155; line-height: 1.6;'>
                                We are thrilled to inform you that your official <strong>Placement Offer Letter</strong> from <strong>" . htmlspecialchars($companyName) . "</strong> has been verified and issued by the Training & Placement Cell of Dr. Subhash University.
                            </p>
                            <div style='background: #EFF6FF; border: 1px solid #BFDBFE; padding: 18px; border-radius: 12px; margin: 20px 0;'>
                                <p style='margin: 0 0 6px 0; font-size: 14px; color: #1E40AF;'><strong>Candidate Name:</strong> " . htmlspecialchars($st['full_name']) . "</p>
                                <p style='margin: 0 0 6px 0; font-size: 14px; color: #1E40AF;'><strong>Enrollment No:</strong> " . htmlspecialchars($st['enrollment_no']) . "</p>
                                <p style='margin: 0; font-size: 14px; color: #1E40AF;'><strong>Recruiter Company:</strong> " . htmlspecialchars($companyName) . "</p>
                            </div>
                            <p style='font-size: 14px; color: #475569;'>
                                You can view and download your official offer letter document by logging into your Student Dashboard or clicking the button below:
                            </p>
                            <div style='text-align: center; margin: 26px 0;'>
                                <a href='http://localhost/DSU/uploads/" . urlencode($fileName) . "' style='background: #2563EB; color: #FFFFFF; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: bold; display: inline-block;'>📜 Download Official Offer Letter</a>
                            </div>
                            <p style='font-size: 13px; color: #64748B; border-top: 1px solid #F1F5F9; padding-top: 16px;'>
                                Training & Placement Cell<br>
                                <strong>Dr. Subhash University, Junagadh</strong>
                            </p>
                        </div>
                        ";

                        sendPortalEmail($toEmail, $subject, $bodyHtml, $pdo);
                    }
                }
            }

            // Always update placement_status to 'Placed' AND placed_companies to $companyName
            $pdo->prepare("UPDATE students SET placement_status = 'Placed', placed_companies = ?, offer_letter = ? WHERE id = ?")->execute([$companyName, $fileName, $stId]);

            setFlashMessage("🎉 Candidate " . htmlspecialchars($st['full_name']) . " successfully marked as Placed at " . htmlspecialchars($companyName) . "!", "success");
        } else {
            setFlashMessage("Candidate record not found.", "warning");
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['student_id']);

        $stmtU = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmtU->execute([$id]);
        $st = $stmtU->fetch();

        if ($st) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$st['user_id']]);
            setFlashMessage("Student candidate account deleted successfully.", 'danger');
        }
    }

    $redirectUrl = $_SERVER['PHP_SELF'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    header("Location: " . $redirectUrl);
    exit;
}

// Filtering parameters
$branchFilter = isset($_GET['branch']) ? sanitize($_GET['branch']) : '';

// Filtering parameters
$branchFilter = isset($_GET['branch']) ? sanitize($_GET['branch']) : '';

$yearParam = isset($_GET['year']) ? trim(strval($_GET['year'])) : '';
if ($yearParam === 'all' || $yearParam === '0' || $yearParam === '') {
    $yearFilter = 0; // Default to 0 = ALL TIME / ALL BATCH YEARS!
} else {
    $yearFilter = intval($yearParam);
}

$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$searchQuery = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$viewMode = isset($_GET['view']) ? sanitize($_GET['view']) : 'list'; // 'list' or 'grid'

// STRICT EXCLUSION OF DETAINED STUDENTS IN PLACEMENT DETAILS PAGE
$query = "SELECT s.*, 
          (SELECT GROUP_CONCAT(c.company_name SEPARATOR ', ') 
           FROM applications a JOIN drives d ON a.drive_id = d.id JOIN companies c ON d.company_id = c.id 
           WHERE a.student_id = s.id AND a.status = 'Selected') as placed_companies,
          (SELECT d.package_ctc 
           FROM applications a JOIN drives d ON a.drive_id = d.id 
           WHERE a.student_id = s.id AND a.status = 'Selected' ORDER BY a.id DESC LIMIT 1) as top_package_ctc
          FROM students s WHERE s.is_detained = 0";
$params = [];

if ($branchFilter) { $query .= " AND s.branch = ?"; $params[] = $branchFilter; }
if ($yearFilter > 0) { $query .= " AND s.batch_year = ?"; $params[] = $yearFilter; }
if ($statusFilter) { 
    $query .= " AND s.placement_status = ?"; 
    $params[] = $statusFilter; 
}
if ($searchQuery) {
    $query .= " AND (s.full_name LIKE ? OR s.enrollment_no LIKE ? OR s.email LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}
$query .= " ORDER BY s.id DESC";

// EXCEL / CSV EXPORT ACTION HANDLER
if (isset($_GET['export_csv'])) {
    $stmtExp = $pdo->prepare($query);
    $stmtExp->execute($params);
    $exportStudents = $stmtExp->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=placement_details_export_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Enrollment No', 'Full Candidate Name', 'Email', 'Gender', 'Branch', 'Batch Year', 'CPI', 'Backlogs', 'Phone', 'Placement Status', 'Placed Company', 'Package CTC']);

    foreach ($exportStudents as $stRow) {
        fputcsv($output, [
            $stRow['enrollment_no'],
            $stRow['full_name'],
            $stRow['email'],
            $stRow['gender'],
            $stRow['branch'],
            $stRow['batch_year'],
            $stRow['cpi'],
            $stRow['backlogs'],
            $stRow['phone'],
            $stRow['placement_status'],
            $stRow['placed_companies'] ?: 'N/A',
            $stRow['top_package_ctc'] ?: 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Dynamic KPI Metrics according to Active Filter
$whereKpi = "WHERE is_detained = 0";
$kpiParams = [];
if ($yearFilter > 0) {
    $whereKpi .= " AND batch_year = ?";
    $kpiParams[] = $yearFilter;
}
if ($branchFilter) {
    $whereKpi .= " AND branch = ?";
    $kpiParams[] = $branchFilter;
}

$stmtTot = $pdo->prepare("SELECT COUNT(*) FROM students $whereKpi");
$stmtTot->execute($kpiParams);
$totalCount = $stmtTot->fetchColumn() ?: 0;

$stmtPlc = $pdo->prepare("SELECT COUNT(*) FROM students $whereKpi AND placement_status = 'Placed'");
$stmtPlc->execute($kpiParams);
$placedCount = $stmtPlc->fetchColumn() ?: 0;

$stmtInp = $pdo->prepare("SELECT COUNT(*) FROM students $whereKpi AND placement_status = 'In-Process'");
$stmtInp->execute($kpiParams);
$inProcessCount = $stmtInp->fetchColumn() ?: 0;

$stmtUnp = $pdo->prepare("SELECT COUNT(*) FROM students $whereKpi AND placement_status = 'Unplaced'");
$stmtUnp->execute($kpiParams);
$unplacedCount = $stmtUnp->fetchColumn() ?: 0;

$placementRate = $totalCount > 0 ? round(($placedCount / $totalCount) * 100, 1) : 0;

$pageTitle = 'Placement Details & Status Control';
$currentPage = 'students';
include __DIR__ . '/../includes/header.php';
?>

<!-- Executive Header Card (Dashboard Themed) -->
<div style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; padding: 28px 32px; border-radius: 24px; margin-bottom: 28px; box-shadow: 0 12px 30px -10px rgba(15, 23, 42, 0.3); border: 1px solid rgba(255, 255, 255, 0.08);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(59, 130, 246, 0.2); color: #60A5FA; padding: 4px 10px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3);">University Placement Directorate</span>
                <span style="font-size: 11px; font-weight: 800; color: #94A3B8;">•</span>
                <span style="font-size: 11.5px; font-weight: 700; color: #38BDF8;"><?= ($yearFilter > 0 ? $yearFilter . ' Academic Batch' : 'All Cohort Years (All Time)'); ?></span>
            </div>
            <h3 style="font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.5px; margin-bottom: 6px;">
                🎓 Master Cohort Placement Roster
            </h3>
            <p style="font-size: 13px; color: #94A3B8; font-weight: 600;">
                Comprehensive placement status tracker, dynamic recruiter company assignments, and candidate dossier management.
            </p>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Live Search Bar with Cancel Search Option -->
            <form method="GET" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin: 0;">
                <input type="hidden" name="branch" value="<?= htmlspecialchars($branchFilter); ?>">
                <input type="hidden" name="year" value="<?= $yearFilter; ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter); ?>">
                <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode); ?>">
                
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="Search name / enrollment..." style="padding: 10px 34px 10px 38px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: white; font-size: 13px; outline: none; width: 230px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <?php if ($searchQuery): ?>
                        <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #94A3B8; text-decoration: none; font-size: 14px; font-weight: 800;" title="Clear Search">✕</a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn-primary" style="padding: 10px 16px; font-size: 13px; border-radius: 12px;">Search</button>

                <?php if (!empty($searchQuery)): ?>
                    <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>" class="btn-secondary" style="padding: 10px 14px; font-size: 13px; border-radius: 12px; font-weight: 800; background: rgba(239, 68, 68, 0.2); color: #FECDD3; border: 1px solid rgba(239, 68, 68, 0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;" title="Clear search query">
                        ✖ Clear
                    </a>
                <?php endif; ?>
            </form>

            <button type="button" onclick="openModal('importStudentsModal')" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 16px; font-weight: 800; background: rgba(255,255,255,0.12); color: white; border: 1px solid rgba(255,255,255,0.2); cursor: pointer;">
                📥 Import CSV
            </button>

            <a href="students.php?export_csv=1&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>&q=<?= urlencode($searchQuery); ?>" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 16px; font-weight: 800; background: #3B82F6; color: white; border: none; box-shadow: 0 4px 14px rgba(59,130,246,0.3);">
                📊 Export CSV
            </a>
        </div>
    </div>
</div>

<?php if ($msg): ?><div class="badge-status placed" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge-status unplaced" style="margin-bottom: 20px; display: block; font-size: 14px; padding: 12px 20px; border-radius: 14px; font-weight: 800;"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Quick Guidance Bar -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 0 4px;">
    <span style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: #64748B;">
        ⚡ Interactive Cohort Analytics — Click any card to filter & show data list:
    </span>
    <span style="font-size: 12px; font-weight: 700; color: #2563EB;">
        Active Filter: <?= (!empty($statusFilter) ? htmlspecialchars($statusFilter) . ' Candidates' : 'All Candidates (Full Cohort)'); ?>
    </span>
</div>

<!-- Executive Summary KPI Cards Row (Interactive & Clickable) -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px;">
    <!-- 1. All Candidates / Candidate Pool Card -->
    <?php $isAllActive = empty($statusFilter); ?>
    <div onclick="window.location.href='students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=&q=<?= urlencode($searchQuery); ?>'" 
         class="kpi-card <?= $isAllActive ? 'kpi-card-active-blue' : ''; ?>" 
         style="animation-delay: 0.05s; padding: 22px 24px; border-radius: 20px; cursor: pointer; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); <?= $isAllActive ? 'background: linear-gradient(135deg, #FFFFFF 0%, #EFF6FF 100%); border: 2.5px solid #3B82F6; box-shadow: 0 12px 28px -5px rgba(59, 130, 246, 0.25);' : 'background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);'; ?>"
         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 16px 32px rgba(59,130,246,0.18)';"
         onmouseout="this.style.transform='none'; this.style.boxShadow='<?= $isAllActive ? '0 12px 28px -5px rgba(59, 130, 246, 0.25)' : '0 4px 14px rgba(0,0,0,0.03)'; ?>';"
         title="Click to show All Candidates Data List">
        <div class="kpi-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span class="kpi-title" style="font-size: 13px; font-weight: 800; color: <?= $isAllActive ? '#1D4ED8' : '#64748B'; ?>; text-transform: uppercase; letter-spacing: 0.5px;">Candidate Pool</span>
            <div class="kpi-icon blue" style="<?= $isAllActive ? 'background: #3B82F6; color: white;' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
        </div>
        <div class="kpi-value" style="font-size: 32px; font-weight: 900; color: #0F172A; line-height: 1.1; margin-bottom: 8px;"><?= number_format($totalCount); ?></div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
            <span class="kpi-badge up" style="font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; <?= $isAllActive ? 'background: #3B82F6; color: white;' : ''; ?>">
                <?= $isAllActive ? '✓ Active: All Candidates' : '↑ ' . ($yearFilter > 0 ? $yearFilter . ' Batch' : 'All Cohorts'); ?>
            </span>
            <span style="font-size: 11px; font-weight: 800; color: #2563EB;">
                <?= $isAllActive ? 'Showing List ▾' : 'Show All List ↗'; ?>
            </span>
        </div>
    </div>

    <!-- 2. Confirmed Placements Card -->
    <?php $isPlcActive = ($statusFilter === 'Placed'); ?>
    <div onclick="window.location.href='students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=Placed&q=<?= urlencode($searchQuery); ?>'" 
         class="kpi-card <?= $isPlcActive ? 'kpi-card-active-green' : ''; ?>" 
         style="animation-delay: 0.1s; padding: 22px 24px; border-radius: 20px; cursor: pointer; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); <?= $isPlcActive ? 'background: linear-gradient(135deg, #FFFFFF 0%, #ECFDF5 100%); border: 2.5px solid #10B981; box-shadow: 0 12px 28px -5px rgba(16, 185, 129, 0.25);' : 'background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);'; ?>"
         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 16px 32px rgba(16,185,129,0.18)';"
         onmouseout="this.style.transform='none'; this.style.boxShadow='<?= $isPlcActive ? '0 12px 28px -5px rgba(16, 185, 129, 0.25)' : '0 4px 14px rgba(0,0,0,0.03)'; ?>';"
         title="Click to show Placed Candidates Data List">
        <div class="kpi-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span class="kpi-title" style="font-size: 13px; font-weight: 800; color: <?= $isPlcActive ? '#047857' : '#64748B'; ?>; text-transform: uppercase; letter-spacing: 0.5px;">Confirmed Placements</span>
            <div class="kpi-icon green" style="<?= $isPlcActive ? 'background: #10B981; color: white;' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
        </div>
        <div class="kpi-value" style="font-size: 32px; font-weight: 900; color: #10B981; line-height: 1.1; margin-bottom: 8px;"><?= number_format($placedCount); ?></div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
            <span class="kpi-badge up" style="font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; <?= $isPlcActive ? 'background: #10B981; color: white;' : 'background: #ECFDF5; color: #059669;'; ?>">
                <?= $isPlcActive ? '✓ Active: Placed List' : '↑ ' . $placementRate . '% Placement Rate'; ?>
            </span>
            <span style="font-size: 11px; font-weight: 800; color: #059669;">
                <?= $isPlcActive ? 'Showing List ▾' : 'Show Placed List ↗'; ?>
            </span>
        </div>
    </div>

    <!-- 3. Pipeline In-Process Card -->
    <?php $isInpActive = ($statusFilter === 'In-Process'); ?>
    <div onclick="window.location.href='students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=In-Process&q=<?= urlencode($searchQuery); ?>'" 
         class="kpi-card <?= $isInpActive ? 'kpi-card-active-amber' : ''; ?>" 
         style="animation-delay: 0.15s; padding: 22px 24px; border-radius: 20px; cursor: pointer; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); <?= $isInpActive ? 'background: linear-gradient(135deg, #FFFFFF 0%, #FFFBEB 100%); border: 2.5px solid #F59E0B; box-shadow: 0 12px 28px -5px rgba(245, 158, 11, 0.25);' : 'background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);'; ?>"
         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 16px 32px rgba(245,158,11,0.18)';"
         onmouseout="this.style.transform='none'; this.style.boxShadow='<?= $isInpActive ? '0 12px 28px -5px rgba(245, 158, 11, 0.25)' : '0 4px 14px rgba(0,0,0,0.03)'; ?>';"
         title="Click to show In-Process Pipeline Data List">
        <div class="kpi-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span class="kpi-title" style="font-size: 13px; font-weight: 800; color: <?= $isInpActive ? '#B45309' : '#64748B'; ?>; text-transform: uppercase; letter-spacing: 0.5px;">Pipeline In-Process</span>
            <div class="kpi-icon orange" style="<?= $isInpActive ? 'background: #F59E0B; color: white;' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
        </div>
        <div class="kpi-value" style="font-size: 32px; font-weight: 900; color: #F59E0B; line-height: 1.1; margin-bottom: 8px;"><?= number_format($inProcessCount); ?></div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
            <span class="kpi-badge up" style="font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; <?= $isInpActive ? 'background: #F59E0B; color: white;' : 'background: #FFFBEB; color: #B45309;'; ?>">
                <?= $isInpActive ? '✓ Active: In-Process' : '⏳ In Interview Rounds'; ?>
            </span>
            <span style="font-size: 11px; font-weight: 800; color: #D97706;">
                <?= $isInpActive ? 'Showing List ▾' : 'Show In-Process List ↗'; ?>
            </span>
        </div>
    </div>

    <!-- 4. Available Unplaced Card -->
    <?php $isUnpActive = ($statusFilter === 'Unplaced'); ?>
    <div onclick="window.location.href='students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=Unplaced&q=<?= urlencode($searchQuery); ?>'" 
         class="kpi-card <?= $isUnpActive ? 'kpi-card-active-purple' : ''; ?>" 
         style="animation-delay: 0.2s; padding: 22px 24px; border-radius: 20px; cursor: pointer; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); <?= $isUnpActive ? 'background: linear-gradient(135deg, #FFFFFF 0%, #EEF2FF 100%); border: 2.5px solid #6366F1; box-shadow: 0 12px 28px -5px rgba(99, 102, 241, 0.25);' : 'background: white; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);'; ?>"
         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 16px 32px rgba(99,102,241,0.18)';"
         onmouseout="this.style.transform='none'; this.style.boxShadow='<?= $isUnpActive ? '0 12px 28px -5px rgba(99, 102, 241, 0.25)' : '0 4px 14px rgba(0,0,0,0.03)'; ?>';"
         title="Click to show Unplaced Candidates Data List">
        <div class="kpi-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span class="kpi-title" style="font-size: 13px; font-weight: 800; color: <?= $isUnpActive ? '#4338CA' : '#64748B'; ?>; text-transform: uppercase; letter-spacing: 0.5px;">Available Unplaced</span>
            <div class="kpi-icon purple" style="<?= $isUnpActive ? 'background: #6366F1; color: white;' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
        </div>
        <div class="kpi-value" style="font-size: 32px; font-weight: 900; color: #6366F1; line-height: 1.1; margin-bottom: 8px;"><?= number_format($unplacedCount); ?></div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
            <span class="kpi-badge info" style="font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; <?= $isUnpActive ? 'background: #6366F1; color: white;' : 'background: #EEF2FF; color: #4F46E5;'; ?>">
                <?= $isUnpActive ? '✓ Active: Unplaced List' : '🎯 Open for Campus Drives'; ?>
            </span>
            <span style="font-size: 11px; font-weight: 800; color: #4F46E5;">
                <?= $isUnpActive ? 'Showing List ▾' : 'Show Unplaced List ↗'; ?>
            </span>
        </div>
    </div>
</div>

<!-- Multi-Dimensional Filter Bar with Search Clear -->
<div class="table-card" style="padding: 18px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background: white; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
    <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin: 0;">
        <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode); ?>">
        <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery); ?>">
        
        <!-- Batch Year Filter with ALL TIME Option -->
        <select name="year" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px; cursor: pointer;">
            <option value="0" <?= ($yearFilter === 0) ? 'selected' : ''; ?>>🌐 All Time / All Batch Years</option>
            <option value="2026" <?= ($yearFilter === 2026) ? 'selected' : ''; ?>>📅 2026 Batch (Current Year)</option>
            <option value="2025" <?= ($yearFilter === 2025) ? 'selected' : ''; ?>>📅 2025 Batch</option>
            <option value="2024" <?= ($yearFilter === 2024) ? 'selected' : ''; ?>>📅 2024 Batch</option>
        </select>

        <!-- Branch Filter -->
        <select name="branch" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px; cursor: pointer;">
            <option value="">All Academic Branches</option>
            <option value="CSE" <?= ($branchFilter==='CSE')?'selected':''; ?>>💻 CSE Branch</option>
            <option value="IT" <?= ($branchFilter==='IT')?'selected':''; ?>>🌐 IT Branch</option>
            <option value="ECE" <?= ($branchFilter==='ECE')?'selected':''; ?>>⚡ ECE Branch</option>
            <option value="MECH" <?= ($branchFilter==='MECH')?'selected':''; ?>>⚙️ MECH Branch</option>
            <option value="CIVIL" <?= ($branchFilter==='CIVIL')?'selected':''; ?>>🏗️ CIVIL Branch</option>
        </select>

        <!-- Status Filter -->
        <select name="status" class="select-pill" onchange="this.form.submit()" style="padding: 9px 18px; font-weight: 700; border-radius: 12px; cursor: pointer;">
            <option value="">All Placement Pipeline States</option>
            <option value="Placed" <?= ($statusFilter==='Placed')?'selected':''; ?>>🎉 Placed Candidates Only</option>
            <option value="In-Process" <?= ($statusFilter==='In-Process')?'selected':''; ?>>⏳ In-Process Pipeline Only</option>
            <option value="Unplaced" <?= ($statusFilter==='Unplaced')?'selected':''; ?>>🎯 Unplaced Candidates Only</option>
        </select>

        <!-- Reset All Filters Button (Always explicitly resets to year=0 / all cohorts) -->
        <?php if ($branchFilter || $yearFilter > 0 || $statusFilter || $searchQuery): ?>
            <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>&year=0" class="btn-secondary" style="padding: 9px 16px; font-size: 13px; border-radius: 12px; font-weight: 800; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                ↺ Reset All Filters
            </a>
        <?php endif; ?>
    </form>

    <!-- View Mode Switcher -->
    <div style="display: flex; gap: 6px; align-items: center; background: #F1F5F9; padding: 4px; border-radius: 14px; border: 1px solid #E2E8F0;">
        <a href="students.php?view=list&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>&q=<?= urlencode($searchQuery); ?>" 
           class="btn-secondary <?= ($viewMode==='list')?'btn-primary':''; ?>" 
           style="padding: 8px 18px; font-size: 13px; border-radius: 10px; border: none; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            📋 Show All Data List (Table)
        </a>
        <a href="students.php?view=grid&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=<?= urlencode($statusFilter); ?>&q=<?= urlencode($searchQuery); ?>" 
           class="btn-secondary <?= ($viewMode==='grid')?'btn-primary':''; ?>" 
           style="padding: 8px 18px; font-size: 13px; border-radius: 10px; border: none; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            🌁 Grid Cards
        </a>
    </div>
</div>

<!-- Active Data List Status Banner -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; padding: 14px 22px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 18px; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 24px;">
            <?= ($statusFilter === 'Placed' ? '🎉' : ($statusFilter === 'In-Process' ? '⏳' : ($statusFilter === 'Unplaced' ? '🎯' : '🎓'))); ?>
        </span>
        <div>
            <div style="font-size: 15px; font-weight: 900; color: #0F172A; display: flex; align-items: center; gap: 8px;">
                <?= ($statusFilter === 'Placed' ? 'Confirmed Placed Candidates Data List' : ($statusFilter === 'In-Process' ? 'Pipeline In-Process Candidates Data List' : ($statusFilter === 'Unplaced' ? 'Available Unplaced Candidates Data List' : 'Master Cohort Candidate Roster (All Candidates List)'))); ?>
                <span style="font-size: 11.5px; font-weight: 800; color: #2563EB; background: #EFF6FF; border: 1px solid #BFDBFE; padding: 2px 10px; border-radius: 20px;">
                    <?= count($students); ?> Candidates Listed
                </span>
            </div>
            <div style="font-size: 12px; color: #64748B; font-weight: 600; margin-top: 2px;">
                💡 <em>Click any candidate <?= ($viewMode === 'list' ? 'table row' : 'grid card'); ?> below to open their complete Executive Dossier modal.</em>
            </div>
        </div>
    </div>
    
    <div style="display: flex; gap: 8px; align-items: center;">
        <?php if (!empty($statusFilter)): ?>
            <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>&branch=<?= urlencode($branchFilter); ?>&year=<?= $yearFilter; ?>&status=" class="btn-secondary" style="font-size: 12px; font-weight: 800; padding: 7px 14px; border-radius: 10px; text-decoration: none; background: white; border: 1px solid #CBD5E1; color: #1E293B; display: inline-flex; align-items: center; gap: 6px;">
                🌐 Show All Candidates Data List
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($students)): ?>
    <!-- Empty State Result Card -->
    <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 24px; border: 1px dashed #CBD5E1; margin-bottom: 28px;">
        <div style="font-size: 44px; margin-bottom: 12px;">🔍</div>
        <h4 style="font-size: 18px; font-weight: 800; color: #0F172A;">No Student Candidates Found</h4>
        <p style="color: #64748B; font-size: 13px; margin-top: 4px; margin-bottom: 18px;">
            No candidate records match your current search term "<strong><?= htmlspecialchars($searchQuery); ?></strong>" or active filters.
        </p>
        <a href="students.php?view=<?= htmlspecialchars($viewMode); ?>" class="btn-primary" style="display: inline-flex; border-radius: 12px; padding: 10px 24px; font-weight: 800; text-decoration: none;">
            ↺ Cancel Search & Show All Cards
        </a>
    </div>
<?php elseif ($viewMode === 'grid'): ?>

<!-- VIEW MODE 1: EXECUTIVE GRID CARDS VIEW -->
<div class="student-grid-view" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <?php foreach ($students as $st): 
        $stCls = ($st['placement_status']==='Placed')?'placed':(($st['placement_status']==='In-Process')?'in-process':'unplaced');
        $photoSrc = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
            ? '../uploads/' . $st['profile_photo'] 
            : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';
    ?>
        <div class="table-card student-card" onclick="viewStudentDetails(<?= $st['id']; ?>)" 
             title="Click to view candidate dossier for <?= htmlspecialchars($st['full_name'], ENT_QUOTES); ?>" 
             style="padding: 24px; border-radius: 24px; background: white; border: 1.5px solid var(--border-color); box-shadow: 0 4px 14px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; cursor: pointer; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);"
             onmouseover="this.style.transform='translateY(-6px)'; this.style.borderColor='#3B82F6'; this.style.boxShadow='0 18px 36px -8px rgba(15, 23, 42, 0.12)';"
             onmouseout="this.style.transform='none'; this.style.borderColor='var(--border-color)'; this.style.boxShadow='0 4px 14px rgba(0,0,0,0.03)';">
            <div>
                <!-- Top Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; margin-bottom: 16px;">
                    <div style="display: flex; gap: 14px; align-items: center;">
                        <img src="<?= $photoSrc; ?>" alt="Candidate Avatar" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #3B82F6; box-shadow: 0 4px 10px rgba(59,130,246,0.2);">
                        <div>
                            <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
                                <?= htmlspecialchars($st['full_name']); ?>
                            </h4>
                            <?php
                            $batchBadgeStyle = ($st['batch_year'] == 2026) 
                                ? 'background: #ECFEFF; color: #0891B2; border: 1px solid #A5F3FC;' 
                                : (($st['batch_year'] == 2025) 
                                    ? 'background: #F5F3FF; color: #7C3AED; border: 1px solid #DDD6FE;' 
                                    : 'background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A;');
                            ?>
                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 3px;">
                                <code style="font-size: 11.5px; color: #2563EB; font-weight: 800;"><?= htmlspecialchars($st['enrollment_no']); ?></code>
                                <span style="font-size: 10.5px; font-weight: 800; padding: 1px 7px; border-radius: 6px; <?= $batchBadgeStyle; ?>">Batch '<?= substr($st['batch_year'], -2); ?></span>
                            </div>
                        </div>
                    </div>

                    <span class="badge-status <?= $stCls; ?>" style="font-size: 12px; font-weight: 800; white-space: nowrap;">
                        <?= htmlspecialchars($st['placement_status']); ?>
                    </span>
                </div>

                <!-- Academic Specs Grid (4-Column) -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px; border-radius: 14px; margin-bottom: 16px; text-align: center;">
                    <div>
                        <span style="font-size: 10px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block;">Branch</span>
                        <strong style="font-size: 12px; color: #0F172A;"><?= htmlspecialchars($st['branch']); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block;">Batch</span>
                        <strong style="font-size: 12px; color: #4338CA;"><?= htmlspecialchars($st['batch_year']); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block;">CPI Score</span>
                        <strong style="font-size: 12px; color: #2563EB;">★ <?= number_format($st['cpi'], 2); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block;">Backlogs</span>
                        <strong style="font-size: 12px; color: <?= ($st['backlogs']>0?'#EF4444':'#10B981'); ?>;"><?= $st['backlogs']; ?></strong>
                    </div>
                </div>

                <!-- Placed Company Banner (If Placed) -->
                <?php if ($st['placement_status'] === 'Placed' && $st['placed_companies']): 
                    $firstCompanyBanner = trim(explode(',', $st['placed_companies'])[0]);
                ?>
                    <div style="background: #ECFDF5; border: 1px solid #A7F3D0; padding: 10px 14px; border-radius: 12px; margin-bottom: 16px;">
                        <span style="font-size: 11px; font-weight: 800; color: #059669; text-transform: uppercase; display: block;">🎉 Placed Recruiter & Package:</span>
                        <strong style="font-size: 13px; color: #065F46;" title="<?= htmlspecialchars($st['placed_companies']); ?>">🏢 <?= htmlspecialchars($firstCompanyBanner); ?></strong> 
                        <?php if ($st['top_package_ctc']): ?>
                            • <strong style="color: #10B981;"><?= htmlspecialchars($st['top_package_ctc']); ?></strong>
                        <?php endif; ?>
                    </div>
                <?php elseif ($st['placement_status'] === 'In-Process'): ?>
                    <div style="background: #FFFBEB; border: 1px solid #FDE68A; padding: 10px 14px; border-radius: 12px; margin-bottom: 16px;">
                        <span style="font-size: 11px; font-weight: 800; color: #B45309; text-transform: uppercase; display: block;">⏳ Pipeline Stage:</span>
                        <strong style="font-size: 13px; color: #92400E;">Active in Recruitment & Technical Interview Rounds</strong>
                    </div>
                <?php else: ?>
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 10px 14px; border-radius: 12px; margin-bottom: 16px;">
                        <span style="font-size: 11px; font-weight: 800; color: #64748B; text-transform: uppercase; display: block;">🎯 Candidate Availability:</span>
                        <strong style="font-size: 13px; color: #334155;">Available & Eligible for Campus Recruitment Drives</strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card Bottom Action Row -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid #E2E8F0; margin-top: 10px; flex-wrap: wrap; gap: 8px;">
                <button class="btn-action-icon edit" onclick="viewStudentDetails(<?= $st['id']; ?>)" style="padding: 6px 14px; font-size: 12px; font-weight: 800; border-radius: 10px; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; cursor: pointer;">
                    📄 View Dossier
                </button>

                <div onclick="event.stopPropagation()" class="action-btn-group" style="display: flex; gap: 6px;">
                    <?php if ($st['placement_status'] !== 'Placed'): ?>
                        <button type="button" class="btn-action-icon place" data-id="<?= $st['id']; ?>" data-name="<?= htmlspecialchars($st['full_name'], ENT_QUOTES); ?>" data-company="<?= htmlspecialchars($st['placed_companies'] ?: '', ENT_QUOTES); ?>" onclick="triggerOfferUploadModal(this)" title="Click to pop up Offer Letter upload modal and mark as Placed" style="padding: 6px 12px; height: 34px; border-radius: 10px; font-weight: 800; background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; cursor: pointer;">
                            ✓ Placed
                        </button>
                    <?php else: ?>
                        <?php if (!empty($st['offer_letter'])): ?>
                            <a href="../view_offer.php?file=<?= urlencode($st['offer_letter']); ?>" target="_blank" onclick="event.stopPropagation()" class="btn-action-icon" title="View Uploaded Offer Letter Document" style="padding: 6px 12px; height: 34px; border-radius: 10px; font-weight: 800; background: #FEF3C7; color: #D97706; border: 1px solid #FCD34D; text-decoration: none; display: inline-flex; align-items: center;">
                                📜 View Offer
                            </a>
                        <?php endif; ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Mark student as UNPLACED? This will clear placement status back to Unplaced.');">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                            <input type="hidden" name="target_status" value="Unplaced">
                            <button type="submit" class="btn-action-icon unplace" title="Mark candidate as Unplaced (Direct Toggle - No Popup)" style="padding: 6px 12px; height: 34px; border-radius: 10px; font-weight: 800; background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; cursor: pointer;">
                                ✗ Unplace
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($st['placement_status'] !== 'In-Process'): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Reset placement status back to default pipeline state (In-Process)?');">
                            <input type="hidden" name="action" value="reset_status">
                            <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                            <button type="submit" class="btn-action-icon reset" title="Reset status to In-Process">↺ Reset</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete student candidate account?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                        <button type="submit" class="btn-action-icon delete" title="Delete Candidate">🗑️</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<!-- VIEW MODE 2: DETAILED DATA TABLE VIEW -->
<div class="table-card" style="padding: 0; background: white; border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; margin-bottom: 32px;">
    <div class="table-responsive">
        <table class="custom-table" style="width: 100%; border-collapse: separate; border-spacing: 0 8px; padding: 12px;">
            <thead>
                <tr style="background: #F8FAFC;">
                    <th style="padding: 14px 16px;">Candidate Profile & Code</th>
                    <th style="padding: 14px 16px;">Branch & Batch</th>
                    <th style="padding: 14px 16px;">CPI Score</th>
                    <th style="padding: 14px 16px;">Backlogs</th>
                    <th style="padding: 14px 16px;">Contact Phone</th>
                    <th style="padding: 14px 16px;">Recruiter Assignment</th>
                    <th style="padding: 14px 16px;">Pipeline Status</th>
                    <th style="text-align: right; padding: 14px 16px;">Placement Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $st): 
                    $stCls = ($st['placement_status']==='Placed')?'placed':(($st['placement_status']==='In-Process')?'in-process':'unplaced');
                    $photoSrc = ($st['profile_photo'] && $st['profile_photo'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../uploads/' . $st['profile_photo'])) 
                        ? '../uploads/' . $st['profile_photo'] 
                        : 'https://ui-avatars.com/api/?name=' . urlencode($st['full_name']) . '&background=3B82F6&color=ffffff&size=128&bold=true';
                ?>
                    <tr onclick="viewStudentDetails(<?= $st['id']; ?>)" 
                        title="Click to view candidate dossier for <?= htmlspecialchars($st['full_name'], ENT_QUOTES); ?>"
                        style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; cursor: pointer; transition: all 0.2s;"
                        onmouseover="this.style.background='#F1F5F9'; this.style.boxShadow='0 6px 18px rgba(15, 23, 42, 0.06)';"
                        onmouseout="this.style.background='#FFFFFF'; this.style.boxShadow='none';">
                        <td style="padding: 14px 16px;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <img src="<?= $photoSrc; ?>" alt="Avatar" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #3B82F6;">
                                <div>
                                    <strong style="font-size: 14px; color: #0F172A;">
                                        <?= htmlspecialchars($st['full_name']); ?>
                                    </strong><br>
                                    <code style="font-size: 11.5px; color: #2563EB; font-weight: 700;"><?= htmlspecialchars($st['enrollment_no']); ?></code>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 16px;">
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                <span class="branch-badge"><?= htmlspecialchars($st['branch']); ?></span>
                                <?php
                                $bStyle = ($st['batch_year'] == 2026) 
                                    ? 'background: #ECFEFF; color: #0891B2; border: 1px solid #A5F3FC;' 
                                    : (($st['batch_year'] == 2025) 
                                        ? 'background: #F5F3FF; color: #7C3AED; border: 1px solid #DDD6FE;' 
                                        : 'background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A;');
                                ?>
                                <span style="font-size: 11px; font-weight: 800; padding: 2px 7px; border-radius: 6px; <?= $bStyle; ?>">Batch '<?= substr($st['batch_year'], -2); ?></span>
                            </div>
                        </td>
                        <td style="padding: 14px 16px;"><strong style="color: #2563EB; font-size: 14px;">★ <?= number_format($st['cpi'], 2); ?></strong></td>
                        <td style="padding: 14px 16px;"><strong style="color: <?= ($st['backlogs']>0?'#EF4444':'#10B981'); ?>; font-size: 14px;"><?= $st['backlogs']; ?></strong></td>
                        <td style="padding: 14px 16px;"><span style="font-size: 13px; color: #334155; font-weight: 600;"><?= htmlspecialchars($st['phone']); ?></span></td>
                        <td style="padding: 14px 16px;">
                            <?php if ($st['placement_status'] === 'Placed' && $st['placed_companies']): 
                                $firstCompany = trim(explode(',', $st['placed_companies'])[0]);
                            ?>
                                <span style="display: inline-block; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 800; color: #059669; font-size: 13px;" title="<?= htmlspecialchars($st['placed_companies']); ?>">
                                    🏢 <?= htmlspecialchars($firstCompany); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #94A3B8; font-size: 12px;">None Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 14px 16px;">
                            <span class="badge-status <?= $stCls; ?>" style="font-weight: 800;"><?= htmlspecialchars($st['placement_status']); ?></span>
                        </td>
                        <td style="text-align: right; padding: 14px 16px; white-space: nowrap;">
                            <div class="action-btn-group" onclick="event.stopPropagation()">
                                <button type="button" class="btn-action-icon edit" onclick="event.stopPropagation(); viewStudentDetails(<?= $st['id']; ?>)" title="View Candidate Dossier" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; cursor: pointer;">
                                    📄 Dossier
                                </button>

                                <?php if ($st['placement_status'] !== 'Placed'): ?>
                                    <!-- UNPLACED -> PLACED: CLICKING '✓ Placed' OPENS OFFER LETTER POPUP MODAL -->
                                    <button type="button" class="btn-action-icon place" data-id="<?= $st['id']; ?>" data-name="<?= htmlspecialchars($st['full_name'], ENT_QUOTES); ?>" data-company="<?= htmlspecialchars($st['placed_companies'] ?: '', ENT_QUOTES); ?>" onclick="event.stopPropagation(); triggerOfferUploadModal(this)" title="Click to pop up Offer Letter upload modal and mark as Placed" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; cursor: pointer;">
                                        ✓ Placed
                                    </button>
                                <?php else: ?>
                                    <!-- ALREADY PLACED: SHOW VIEW OFFER BADGE & '✗ Unplace' TOGGLE BUTTON (NO POPUP ON UNPLACE) -->
                                    <?php if (!empty($st['offer_letter'])): ?>
                                        <a href="../view_offer.php?file=<?= urlencode($st['offer_letter']); ?>" target="_blank" onclick="event.stopPropagation()" class="btn-action-icon" title="View Uploaded Offer Letter Document" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #FEF3C7; color: #D97706; border: 1px solid #FCD34D; text-decoration: none; display: inline-flex; align-items: center;">
                                            📜 View Offer
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onclick="event.stopPropagation()" onsubmit="return confirm('Mark student as UNPLACED? This will clear placement status back to Unplaced.');">
                                        <input type="hidden" name="action" value="change_status">
                                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                                        <input type="hidden" name="target_status" value="Unplaced">
                                        <button type="submit" class="btn-action-icon unplace" onclick="event.stopPropagation()" title="Mark candidate as Unplaced (Direct Toggle - No Popup)" style="padding: 6px 12px; height: 36px; border-radius: 10px; font-weight: 800; background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; cursor: pointer;">
                                            ✗ Unplace
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($st['placement_status'] !== 'In-Process'): ?>
                                    <form method="POST" style="display: inline;" onclick="event.stopPropagation()" onsubmit="return confirm('Reset placement status back to default pipeline state (In-Process)?');">
                                        <input type="hidden" name="action" value="reset_status">
                                        <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                                        <button type="submit" class="btn-action-icon reset" onclick="event.stopPropagation()" title="Reset status to In-Process">↺ Reset</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete Candidate -->
                                <form method="POST" style="display: inline;" onclick="event.stopPropagation()" onsubmit="return confirm('Delete student candidate account?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?= $st['id']; ?>">
                                    <button type="submit" class="btn-action-icon delete" onclick="event.stopPropagation()" title="Delete Candidate">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal 1: Import Students via Excel / CSV -->
<div class="modal-overlay" id="importStudentsModal">
    <div class="modal-box" style="max-width: 620px; border-radius: 24px;">
        <div class="modal-header">
            <h3>📥 Bulk Student Candidate Import (Excel / CSV)</h3>
            <button class="close-modal" onclick="closeModal('importStudentsModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import_students">
            
            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; padding: 16px; border-radius: 16px; margin-bottom: 20px;">
                <h4 style="font-size: 13.5px; font-weight: 800; color: #1E40AF; margin-bottom: 6px;">📋 Required CSV Header Format:</h4>
                <p style="font-size: 12px; color: #3B82F6; line-height: 1.6; font-weight: 600;">
                    Your CSV spreadsheet must include the following 10 headers in order:<br>
                    <code style="background: white; padding: 4px 10px; border-radius: 8px; color: #0F172A; display: inline-block; margin-top: 6px; font-size: 11px; border: 1px solid #BFDBFE;">
                        enrollment_no, full_name, email, phone, gender, branch, cpi, backlogs, batch_year, placement_status
                    </code>
                </p>
                <div style="margin-top: 12px;">
                    <a href="students.php?download_sample_csv=1" style="font-size: 12.5px; font-weight: 800; color: #2563EB; text-decoration: underline; display: inline-flex; align-items: center; gap: 6px;">
                        📥 Download Official Sample CSV Template (.csv) ↗
                    </a>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 800; font-size: 13px;">Select CSV / Excel File *</label>
                <input type="file" name="csv_file" accept=".csv, .xlsx, .xls" class="form-control" required style="border-radius: 12px; padding: 12px;">
            </div>

            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 14px 16px; border-radius: 14px; margin-bottom: 20px; font-size: 12px; color: #64748B; line-height: 1.6;">
                <strong style="color: #0F172A;">🛡️ Data Accuracy & Validation Controls:</strong><br>
                • Duplicate enrollment numbers or existing emails are automatically skipped.<br>
                • CPI must be between 0.00 and 10.00; invalid values default safely to 7.50.<br>
                • Default student portal password generated: <code style="color: #2563EB; font-weight: 700;">student123</code>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; border-radius: 14px; padding: 14px; font-weight: 800; background: #059669; border: none; box-shadow: 0 4px 14px rgba(5,150,105,0.3);">
                🚀 Import Candidate Records Now
            </button>
        </form>
    </div>
</div>

<!-- Modal 2: Premium Upload Offer Letter Flash Modal Popup -->
<div class="modal-overlay" id="uploadOfferLetterModal">
    <div class="modal-box" style="max-width: 560px; border-radius: 28px; padding: 32px; background: #FFFFFF; box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35); border: 1px solid rgba(226, 232, 240, 0.8);">
        
        <!-- Header Banner -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; padding-bottom: 16px; border-bottom: 1px solid #F1F5F9;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 16px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4); color: white;">
                    📜
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #059669; background: #ECFDF5; padding: 3px 10px; border-radius: 20px; border: 1px solid #A7F3D0; letter-spacing: 0.5px;">✨ Campus Placement Dispatcher</span>
                    <h3 style="font-size: 19px; font-weight: 900; color: #0F172A; margin-top: 2px; margin-bottom: 0;">Upload Offer Letter & Mark Placed</h3>
                </div>
            </div>
            <button type="button" class="close-modal" onclick="closeModal('uploadOfferLetterModal')" style="background: #F1F5F9; border: none; font-size: 20px; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; color: #64748B; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data" action="students.php">
            <input type="hidden" name="action" value="upload_offer_letter">
            <input type="hidden" name="student_id" id="offerStudentId">
            <input type="hidden" name="app_id" id="offerAppId" value="0">

            <!-- Candidate Summary Box -->
            <div style="background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%); border: 1px solid #BFDBFE; padding: 16px; border-radius: 18px; margin-bottom: 20px;">
                <div style="font-size: 11px; font-weight: 800; color: #2563EB; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Target Candidate Profile</div>
                <input type="text" id="offerStudentName" class="form-control-custom" readonly style="background: transparent; border: none; font-size: 16px; font-weight: 900; color: #0F172A; width: 100%; padding: 0; outline: none;">
            </div>

            <!-- Recruiter Company Name & Package -->
            <div class="form-group" style="margin-bottom: 18px;">
                <label style="font-size: 13px; font-weight: 800; color: #334155; display: block; margin-bottom: 6px;">
                    🏢 Recruiter Company Name & Salary Package *
                </label>
                <input type="text" name="company_name" id="offerCompanyName" class="form-control-custom" placeholder="e.g. Google India Pvt Ltd (₹24.5 LPA)" required style="width: 100%; padding: 13px 16px; border-radius: 14px; border: 1px solid #CBD5E1; font-weight: 700; font-size: 14px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); outline: none;">
            </div>

            <!-- Drag & Drop / File Select Box -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label style="font-size: 13px; font-weight: 800; color: #334155; display: block; margin-bottom: 6px;">
                    📄 Upload Official Offer Letter Document <span style="color: #64748B; font-weight: 600;">(PDF / DOCX / Image)</span>
                </label>
                
                <div style="border: 2px dashed #10B981; background: #ECFDF5; border-radius: 18px; padding: 22px; text-align: center; position: relative; transition: all 0.2s;" id="offerDropZone">
                    <div style="font-size: 32px; margin-bottom: 8px;">📜</div>
                    <div style="font-size: 14px; font-weight: 800; color: #065F46;" id="offerFileNameDisplay">Choose file or drag & drop here</div>
                    <div style="font-size: 12px; color: #047857; margin-top: 4px; font-weight: 600;">Supports PDF, DOCX, DOC, PNG, JPG (Max 10MB)</div>
                    <input type="file" name="offer_file" id="offerFileInput" accept=".pdf,.docx,.doc,.png,.jpg,.jpeg" onchange="updateOfferFileName(this)" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid #F1F5F9; padding-top: 20px;">
                <button type="button" class="btn-secondary" onclick="closeModal('uploadOfferLetterModal')" style="padding: 12px 22px; border-radius: 14px; font-weight: 800; font-size: 14px;">Cancel</button>
                <button type="submit" class="btn-primary" style="padding: 12px 26px; border-radius: 14px; font-weight: 800; font-size: 14px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); border: none; box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4); display: inline-flex; align-items: center; gap: 8px;">
                    📤 Upload & Confirm Placement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Full Size Dossier Modal Popup -->
<div class="modal-overlay" id="studentDossierModal">
    <div class="modal-box full-size">
        <div class="modal-header">
            <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Dr. Subhash University — Student Candidate Dossier</h3>
            <button class="close-modal" onclick="closeModal('studentDossierModal')">&times;</button>
        </div>
        <div id="studentDossierContent" class="dossier-scroll-area">
            <p style="color: var(--text-muted); text-align: center; padding: 30px;">Loading candidate dossier...</p>
        </div>
    </div>
</div>

<script>
window.openOfferUploadModal = function(studentId, studentName, companyName, appId = 0) {
    const modal = document.getElementById('uploadOfferLetterModal');
    if (!modal) return;
    const stIdEl = document.getElementById('offerStudentId');
    const stNameEl = document.getElementById('offerStudentName');
    const compNameEl = document.getElementById('offerCompanyName');

    if (stIdEl) stIdEl.value = studentId;
    if (stNameEl) stNameEl.value = studentName;
    if (compNameEl) compNameEl.value = companyName && companyName !== 'Assigned Recruiter' ? companyName : '';

    modal.classList.add('active');
};

function viewStudentDetails(studentId) {
    if (window.showToast) {
        window.showToast('📄 Candidate Dossier', 'Fetching candidate profile & credentials...', 'dossier');
    }

    const container = document.getElementById('studentDossierContent');
    container.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 40px; font-weight: 700;">Loading candidate profile & credentials...</p>';
    
    openModal('studentDossierModal');

    fetch('students.php?get_dossier=1&student_id=' + studentId)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(err => {
            container.innerHTML = '<p style="color: #EF4444; text-align: center; padding: 40px; font-weight: 800;">Failed to load candidate details.</p>';
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
