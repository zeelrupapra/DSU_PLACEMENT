<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDBConnection();

echo "=== SEEDING FULL MULTI-YEAR COHORTS (2024, 2025, 2026) ===\n";

$adminPass = password_hash('admin123', PASSWORD_BCRYPT);
$studentPass = password_hash('student123', PASSWORD_BCRYPT);

// 1. Ensure Admins
$admins = [
    ['username' => 'admin', 'password' => $adminPass, 'role' => 'admin'],
    ['username' => 'admin@drsubhash.edu.in', 'password' => $adminPass, 'role' => 'admin'],
    ['username' => 'rupaparazeel224@gmail.com', 'password' => $adminPass, 'role' => 'admin'],
];
foreach ($admins as $adm) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
    $stmt->execute([$adm['username']]);
    $existing = $stmt->fetch();
    if ($existing) {
        $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE id = ?")->execute([$adm['password'], $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')")->execute([$adm['username'], $adm['password']]);
    }
}
echo "Admins seeded successfully (admin, admin@drsubhash.edu.in, rupaparazeel224@gmail.com).\n";

// 2. Ensure Companies & Recruiters
$companiesData = [
    [
        'name' => 'Google India Pvt Ltd',
        'hr_name' => 'Sundeep Pichai',
        'hr_email' => 'hr.google@gmail.com',
        'industry' => 'Cloud & AI Systems',
        'website' => 'https://careers.google.com',
        'phone' => '+91 98250 11221'
    ],
    [
        'name' => 'Microsoft Corporation',
        'hr_name' => 'Satya Nadella',
        'hr_email' => 'hr.microsoft@gmail.com',
        'industry' => 'Enterprise Cloud Software',
        'website' => 'https://careers.microsoft.com',
        'phone' => '+91 98250 22332'
    ],
    [
        'name' => 'Amazon Web Services (AWS)',
        'hr_name' => 'Andrew Jassy',
        'hr_email' => 'hr.aws@gmail.com',
        'industry' => 'Cloud Computing & DevOps',
        'website' => 'https://aws.amazon.com',
        'phone' => '+91 98250 33443'
    ],
    [
        'name' => 'Tata Consultancy Services (TCS)',
        'hr_name' => 'K. Krithivasan',
        'hr_email' => 'hr.tcs@gmail.com',
        'industry' => 'IT Services & Consulting',
        'website' => 'https://www.tcs.com',
        'phone' => '+91 98250 44554'
    ],
    [
        'name' => 'Infosys Limited',
        'hr_name' => 'Salil Parekh',
        'hr_email' => 'hr.infosys@gmail.com',
        'industry' => 'Digital Tech & AI',
        'website' => 'https://www.infosys.com',
        'phone' => '+91 98250 55665'
    ],
    [
        'name' => 'L&T Technology Services',
        'hr_name' => 'Amit Chadha',
        'hr_email' => 'hr.lt@gmail.com',
        'industry' => 'Engineering & IoT',
        'website' => 'https://www.ltts.com',
        'phone' => '+91 98250 66776'
    ]
];

$companyMap = []; // Name -> ID
foreach ($companiesData as $c) {
    $stmtC = $pdo->prepare("SELECT id FROM companies WHERE company_name = ?");
    $stmtC->execute([$c['name']]);
    $cId = $stmtC->fetchColumn();
    if (!$cId) {
        $ins = $pdo->prepare("INSERT INTO companies (company_name, hr_name, hr_email, industry, website, phone, is_closed) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $ins->execute([$c['name'], $c['hr_name'], $c['hr_email'], $c['industry'], $c['website'], $c['phone']]);
        $cId = $pdo->lastInsertId();
    } else {
        $pdo->prepare("UPDATE companies SET hr_name = ?, hr_email = ?, industry = ?, website = ?, phone = ? WHERE id = ?")
            ->execute([$c['hr_name'], $c['hr_email'], $c['industry'], $c['website'], $c['phone'], $cId]);
    }
    $companyMap[$c['name']] = $cId;

    // Seed Recruiter User
    $stmtRec = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
    $stmtRec->execute([$c['hr_email']]);
    $recId = $stmtRec->fetchColumn();
    if (!$recId) {
        $pdo->prepare("INSERT INTO users (username, password, role, company_id) VALUES (?, ?, 'recruiter', ?)")
            ->execute([$c['hr_email'], $studentPass, $cId]);
    } else {
        $pdo->prepare("UPDATE users SET password = ?, role = 'recruiter', company_id = ? WHERE id = ?")
            ->execute([$studentPass, $cId, $recId]);
    }
}
echo "Companies & Recruiter accounts seeded.\n";

// 3. Ensure Drives
$drivesData = [
    [
        'company' => 'Google India Pvt Ltd',
        'title' => 'Software Development Engineer (SWE-1)',
        'designation' => 'Software Engineer - Distributed Systems',
        'ctc' => '₹24.50 LPA',
        'numeric' => 24.50,
        'location' => 'Bengaluru / Hyderabad',
        'min_cpi' => 8.00,
        'branches' => 'CSE, IT, ECE',
        'status' => 'Completed'
    ],
    [
        'company' => 'Microsoft Corporation',
        'title' => 'Azure Cloud & AI Solutions Engineer',
        'designation' => 'Cloud Solutions Associate',
        'ctc' => '₹44.00 LPA',
        'numeric' => 44.00,
        'location' => 'Noida / Hyderabad',
        'min_cpi' => 8.50,
        'branches' => 'CSE, IT',
        'status' => 'Active'
    ],
    [
        'company' => 'Amazon Web Services (AWS)',
        'title' => 'Cloud Support Associate & DevOps Engineer',
        'designation' => 'DevOps Specialist',
        'ctc' => '₹18.00 LPA',
        'numeric' => 18.00,
        'location' => 'Bengaluru / Pune',
        'min_cpi' => 7.50,
        'branches' => 'CSE, IT, ECE',
        'status' => 'Active'
    ],
    [
        'company' => 'Tata Consultancy Services (TCS)',
        'title' => 'TCS Digital & Ninja Developer',
        'designation' => 'Systems Engineer',
        'ctc' => '₹9.00 LPA',
        'numeric' => 9.00,
        'location' => 'Ahmedabad / Gandhinagar',
        'min_cpi' => 6.50,
        'branches' => 'CSE, IT, ECE, MECH, CIVIL',
        'status' => 'Active'
    ],
    [
        'company' => 'Infosys Limited',
        'title' => 'Power Programmer & Specialist Engineer',
        'designation' => 'Associate Consultant',
        'ctc' => '₹9.50 LPA',
        'numeric' => 9.50,
        'location' => 'Pune / Mysuru',
        'min_cpi' => 7.00,
        'branches' => 'CSE, IT, ECE',
        'status' => 'Active'
    ],
    [
        'company' => 'L&T Technology Services',
        'title' => 'Graduate Engineer Trainee (GET)',
        'designation' => 'GET - Embedded & Design',
        'ctc' => '₹8.50 LPA',
        'numeric' => 8.50,
        'location' => 'Vadodara / Mumbai',
        'min_cpi' => 6.50,
        'branches' => 'ECE, MECH, CIVIL',
        'status' => 'Active'
    ]
];

$driveMap = [];
foreach ($drivesData as $drv) {
    $cId = $companyMap[$drv['company']] ?? 1;
    $stmtD = $pdo->prepare("SELECT id FROM drives WHERE company_id = ? AND title = ?");
    $stmtD->execute([$cId, $drv['title']]);
    $dId = $stmtD->fetchColumn();
    if (!$dId) {
        $insD = $pdo->prepare("INSERT INTO drives (company_id, title, designation, package_ctc, package_numeric, location, min_cpi, allowed_branches, drive_date, deadline, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, '2026-11-15', '2026-11-10', ?)");
        $insD->execute([$cId, $drv['title'], $drv['designation'], $drv['ctc'], $drv['numeric'], $drv['location'], $drv['min_cpi'], $drv['branches'], $drv['status']]);
        $dId = $pdo->lastInsertId();
    }
    $driveMap[$drv['company']] = $dId;
}
echo "Campus Drives seeded.\n";

// 4. Seed Multi-Year Students (Batches 2026, 2025, 2024)
$studentsList = [
    // --- 2026 COHORT (CURRENT GRADUATING BATCH) ---
    [
        'enrollment' => '260130316001',
        'name' => 'Aarav Patel',
        'email' => 'aarav.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01001',
        'gender' => 'Male',
        'branch' => 'CSE',
        'semester' => 7,
        'cpi' => 9.40,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'Placed',
        'placed_company' => 'Google India Pvt Ltd (₹24.50 LPA)',
        'package_numeric' => 24.50,
        'target_company' => 'Google India Pvt Ltd',
        'linkedin' => 'https://www.linkedin.com/in/aarav-patel-dsu',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '260130316002',
        'name' => 'Diya Sharma',
        'email' => 'diya.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01002',
        'gender' => 'Female',
        'branch' => 'IT',
        'semester' => 7,
        'cpi' => 8.90,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'In-Process',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => 'Microsoft Corporation',
        'linkedin' => 'https://www.linkedin.com/in/diya-sharma-tech',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '260130316003',
        'name' => 'Kabir Mehta',
        'email' => 'kabir.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01003',
        'gender' => 'Male',
        'branch' => 'ECE',
        'semester' => 7,
        'cpi' => 8.50,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'In-Process',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => 'Amazon Web Services (AWS)',
        'linkedin' => 'https://www.linkedin.com/in/kabir-mehta-ece',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '260130316004',
        'name' => 'Anaya Joshi',
        'email' => 'anaya.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01004',
        'gender' => 'Female',
        'branch' => 'CSE',
        'semester' => 7,
        'cpi' => 9.75,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'Placed',
        'placed_company' => 'Google India Pvt Ltd (₹24.50 LPA)',
        'package_numeric' => 24.50,
        'target_company' => 'Google India Pvt Ltd',
        'linkedin' => 'https://www.linkedin.com/in/anaya-joshi-ai',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '260130316005',
        'name' => 'Rudra Shah',
        'email' => 'rudra.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01005',
        'gender' => 'Male',
        'branch' => 'MECH',
        'semester' => 7,
        'cpi' => 7.80,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'Placed',
        'placed_company' => 'L&T Technology Services (₹8.50 LPA)',
        'package_numeric' => 8.50,
        'target_company' => 'L&T Technology Services',
        'linkedin' => 'https://www.linkedin.com/in/rudra-shah-mech',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '260130316006',
        'name' => 'Isha Trivedi',
        'email' => 'isha.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01006',
        'gender' => 'Female',
        'branch' => 'CIVIL',
        'semester' => 7,
        'cpi' => 8.20,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'In-Process',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => 'L&T Technology Services',
        'linkedin' => 'https://www.linkedin.com/in/isha-trivedi-civil',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '260130316007',
        'name' => 'Vivaan Kulkarni',
        'email' => 'vivaan.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01007',
        'gender' => 'Male',
        'branch' => 'IT',
        'semester' => 7,
        'cpi' => 8.65,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'In-Process',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => 'Tata Consultancy Services (TCS)',
        'linkedin' => 'https://www.linkedin.com/in/vivaan-kulkarni-devops',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '260130316008',
        'name' => 'Riya Desai',
        'email' => 'riya.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01008',
        'gender' => 'Female',
        'branch' => 'CSE',
        'semester' => 7,
        'cpi' => 9.10,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'In-Process',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => 'Infosys Limited',
        'linkedin' => 'https://www.linkedin.com/in/riya-desai-cloud',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '260130316009',
        'name' => 'Devansh Varma',
        'email' => 'devansh.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01009',
        'gender' => 'Male',
        'branch' => 'ECE',
        'semester' => 7,
        'cpi' => 7.40,
        'backlogs' => 1,
        'batch_year' => 2026,
        'status' => 'Unplaced',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => NULL,
        'linkedin' => 'https://www.linkedin.com/in/devansh-varma-iot',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '260130316010',
        'name' => 'Kavya Nair',
        'email' => 'kavya.2026@drsubhash.edu.in',
        'phone' => '+91 98251 01010',
        'gender' => 'Female',
        'branch' => 'IT',
        'semester' => 7,
        'cpi' => 8.95,
        'backlogs' => 0,
        'batch_year' => 2026,
        'status' => 'Placed',
        'placed_company' => 'Microsoft Corporation (₹44.00 LPA)',
        'package_numeric' => 44.00,
        'target_company' => 'Microsoft Corporation',
        'linkedin' => 'https://www.linkedin.com/in/kavya-nair-fullstack',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],

    // --- 2025 COHORT (PREVIOUS GRADUATION YEAR) ---
    [
        'enrollment' => '250130318041',
        'name' => 'Zeel Rupapara',
        'email' => 'rupaparazeel224@gmail.com', // User's personal email!
        'phone' => '+91 98252 02041',
        'gender' => 'Male',
        'branch' => 'CSE',
        'semester' => 8,
        'cpi' => 9.60,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'Placed',
        'placed_company' => 'Microsoft Corporation (₹44.00 LPA)',
        'package_numeric' => 44.00,
        'target_company' => 'Microsoft Corporation',
        'linkedin' => 'https://www.linkedin.com/in/zeel-rupapara-tech',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '250130318042',
        'name' => 'Priya Rathod',
        'email' => 'priya.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02042',
        'gender' => 'Female',
        'branch' => 'IT',
        'semester' => 8,
        'cpi' => 9.15,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'Placed',
        'placed_company' => 'Amazon Web Services (AWS) (₹18.00 LPA)',
        'package_numeric' => 18.00,
        'target_company' => 'Amazon Web Services (AWS)',
        'linkedin' => 'https://www.linkedin.com/in/priya-rathod-cloud',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '250130318043',
        'name' => 'Harshil Chudasama',
        'email' => 'harshil.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02043',
        'gender' => 'Male',
        'branch' => 'ECE',
        'semester' => 8,
        'cpi' => 8.45,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'Placed',
        'placed_company' => 'Tata Consultancy Services (TCS) (₹9.00 LPA)',
        'package_numeric' => 9.00,
        'target_company' => 'Tata Consultancy Services (TCS)',
        'linkedin' => 'https://www.linkedin.com/in/harshil-chudasama-ece',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '250130318044',
        'name' => 'Meet Solanki',
        'email' => 'meet.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02044',
        'gender' => 'Male',
        'branch' => 'CSE',
        'semester' => 8,
        'cpi' => 8.80,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'Placed',
        'placed_company' => 'Infosys Limited (₹9.50 LPA)',
        'package_numeric' => 9.50,
        'target_company' => 'Infosys Limited',
        'linkedin' => 'https://www.linkedin.com/in/meet-solanki-dev',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '250130318045',
        'name' => 'Janki Vaghela',
        'email' => 'janki.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02045',
        'gender' => 'Female',
        'branch' => 'IT',
        'semester' => 8,
        'cpi' => 8.90,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'In-Process',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => 'Google India Pvt Ltd',
        'linkedin' => 'https://www.linkedin.com/in/janki-vaghela',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '250130318046',
        'name' => 'Yash Parmar',
        'email' => 'yash.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02046',
        'gender' => 'Male',
        'branch' => 'MECH',
        'semester' => 8,
        'cpi' => 7.95,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'Placed',
        'placed_company' => 'L&T Technology Services (₹8.50 LPA)',
        'package_numeric' => 8.50,
        'target_company' => 'L&T Technology Services',
        'linkedin' => 'https://www.linkedin.com/in/yash-parmar-mech',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '250130318047',
        'name' => 'Nidhi Gondaliya',
        'email' => 'nidhi.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02047',
        'gender' => 'Female',
        'branch' => 'CIVIL',
        'semester' => 8,
        'cpi' => 8.10,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'In-Process',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => 'L&T Technology Services',
        'linkedin' => 'https://www.linkedin.com/in/nidhi-gondaliya',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '250130318048',
        'name' => 'Kevin Makwana',
        'email' => 'kevin.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02048',
        'gender' => 'Male',
        'branch' => 'CSE',
        'semester' => 8,
        'cpi' => 9.30,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'Placed',
        'placed_company' => 'Google India Pvt Ltd (₹24.50 LPA)',
        'package_numeric' => 24.50,
        'target_company' => 'Google India Pvt Ltd',
        'linkedin' => 'https://www.linkedin.com/in/kevin-makwana',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '250130318049',
        'name' => 'Hetvi Dobariya',
        'email' => 'hetvi.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02049',
        'gender' => 'Female',
        'branch' => 'IT',
        'semester' => 8,
        'cpi' => 8.55,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'Placed',
        'placed_company' => 'Tata Consultancy Services (TCS) (₹9.00 LPA)',
        'package_numeric' => 9.00,
        'target_company' => 'Tata Consultancy Services (TCS)',
        'linkedin' => 'https://www.linkedin.com/in/hetvi-dobariya',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '250130318050',
        'name' => 'Parthiv Bhatt',
        'email' => 'parthiv.2025@drsubhash.edu.in',
        'phone' => '+91 98252 02050',
        'gender' => 'Male',
        'branch' => 'ECE',
        'semester' => 8,
        'cpi' => 7.30,
        'backlogs' => 0,
        'batch_year' => 2025,
        'status' => 'Unplaced',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => NULL,
        'linkedin' => 'https://www.linkedin.com/in/parthiv-bhatt',
        'offer_letter' => NULL
    ],

    // --- 2024 COHORT (ALUMNI PLACEMENT RECORD) ---
    [
        'enrollment' => '240130316041',
        'name' => 'Rohan Kothari',
        'email' => 'rohan.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03041',
        'gender' => 'Male',
        'branch' => 'CSE',
        'semester' => 8,
        'cpi' => 9.50,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Placed',
        'placed_company' => 'Microsoft Corporation (₹44.00 LPA)',
        'package_numeric' => 44.00,
        'target_company' => 'Microsoft Corporation',
        'linkedin' => 'https://www.linkedin.com/in/rohan-kothari-swe',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '240130316042',
        'name' => 'Brijesh Vora',
        'email' => 'brijesh.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03042',
        'gender' => 'Male',
        'branch' => 'IT',
        'semester' => 8,
        'cpi' => 9.00,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Placed',
        'placed_company' => 'Amazon Web Services (AWS) (₹18.00 LPA)',
        'package_numeric' => 18.00,
        'target_company' => 'Amazon Web Services (AWS)',
        'linkedin' => 'https://www.linkedin.com/in/brijesh-vora',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '240130316043',
        'name' => 'Sneha Dholakia',
        'email' => 'sneha.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03043',
        'gender' => 'Female',
        'branch' => 'ECE',
        'semester' => 8,
        'cpi' => 8.75,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Placed',
        'placed_company' => 'Tata Consultancy Services (TCS) (₹9.00 LPA)',
        'package_numeric' => 9.00,
        'target_company' => 'Tata Consultancy Services (TCS)',
        'linkedin' => 'https://www.linkedin.com/in/sneha-dholakia',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '240130316044',
        'name' => 'Jaydeep Radadiya',
        'email' => 'jaydeep.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03044',
        'gender' => 'Male',
        'branch' => 'CSE',
        'semester' => 8,
        'cpi' => 9.20,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Placed',
        'placed_company' => 'Google India Pvt Ltd (₹24.50 LPA)',
        'package_numeric' => 24.50,
        'target_company' => 'Google India Pvt Ltd',
        'linkedin' => 'https://www.linkedin.com/in/jaydeep-radadiya',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '240130316045',
        'name' => 'Mansi Kanani',
        'email' => 'mansi.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03045',
        'gender' => 'Female',
        'branch' => 'IT',
        'semester' => 8,
        'cpi' => 8.85,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Placed',
        'placed_company' => 'Infosys Limited (₹9.50 LPA)',
        'package_numeric' => 9.50,
        'target_company' => 'Infosys Limited',
        'linkedin' => 'https://www.linkedin.com/in/mansi-kanani',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '240130316046',
        'name' => 'Darshan Savaliya',
        'email' => 'darshan.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03046',
        'gender' => 'Male',
        'branch' => 'MECH',
        'semester' => 8,
        'cpi' => 8.15,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Placed',
        'placed_company' => 'L&T Technology Services (₹8.50 LPA)',
        'package_numeric' => 8.50,
        'target_company' => 'L&T Technology Services',
        'linkedin' => 'https://www.linkedin.com/in/darshan-savaliya',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '240130316047',
        'name' => 'Pooja Mandavia',
        'email' => 'pooja.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03047',
        'gender' => 'Female',
        'branch' => 'CIVIL',
        'semester' => 8,
        'cpi' => 8.40,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Placed',
        'placed_company' => 'L&T Technology Services (₹8.50 LPA)',
        'package_numeric' => 8.50,
        'target_company' => 'L&T Technology Services',
        'linkedin' => 'https://www.linkedin.com/in/pooja-mandavia',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '240130316048',
        'name' => 'Chirag Kakadiya',
        'email' => 'chirag.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03048',
        'gender' => 'Male',
        'branch' => 'CSE',
        'semester' => 8,
        'cpi' => 8.60,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'In-Process',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => 'Tata Consultancy Services (TCS)',
        'linkedin' => 'https://www.linkedin.com/in/chirag-kakadiya',
        'offer_letter' => NULL
    ],
    [
        'enrollment' => '240130316049',
        'name' => 'Urvashi Vala',
        'email' => 'urvashi.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03049',
        'gender' => 'Female',
        'branch' => 'ECE',
        'semester' => 8,
        'cpi' => 7.90,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Placed',
        'placed_company' => 'Tata Consultancy Services (TCS) (₹9.00 LPA)',
        'package_numeric' => 9.00,
        'target_company' => 'Tata Consultancy Services (TCS)',
        'linkedin' => 'https://www.linkedin.com/in/urvashi-vala',
        'offer_letter' => 'offer_letter_260130316001.pdf'
    ],
    [
        'enrollment' => '240130316050',
        'name' => 'Manav Sutariya',
        'email' => 'manav.2024@drsubhash.edu.in',
        'phone' => '+91 98253 03050',
        'gender' => 'Male',
        'branch' => 'IT',
        'semester' => 8,
        'cpi' => 7.10,
        'backlogs' => 0,
        'batch_year' => 2024,
        'status' => 'Unplaced',
        'placed_company' => NULL,
        'package_numeric' => 0.00,
        'target_company' => NULL,
        'linkedin' => 'https://www.linkedin.com/in/manav-sutariya',
        'offer_letter' => NULL
    ]
];

foreach ($studentsList as $s) {
    // A. Check / Create User
    $stmtU = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
    $stmtU->execute([$s['enrollment']]);
    $uId = $stmtU->fetchColumn();

    if (!$uId) {
        $insU = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
        $insU->execute([$s['enrollment'], $studentPass]);
        $uId = $pdo->lastInsertId();
    } else {
        $pdo->prepare("UPDATE users SET password = ?, role = 'student' WHERE id = ?")->execute([$studentPass, $uId]);
    }

    // Also check if student has a secondary username with their email
    $stmtUEmail = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
    $stmtUEmail->execute([$s['email']]);
    $uEmailId = $stmtUEmail->fetchColumn();
    if (!$uEmailId) {
        $insUE = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
        $insUE->execute([$s['email'], $studentPass]);
    } else {
        $pdo->prepare("UPDATE users SET password = ?, role = 'student' WHERE id = ?")->execute([$studentPass, $uEmailId]);
    }

    // B. Check / Create Student Record
    $stmtS = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = ?");
    $stmtS->execute([$s['enrollment']]);
    $stId = $stmtS->fetchColumn();

    if (!$stId) {
        $insS = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, full_name, email, phone, gender, branch, current_semester, cpi, backlogs, batch_year, linkedin_url, resume_file, offer_letter, placed_companies, placement_status, is_detained) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'default_resume.pdf', ?, ?, ?, 0)");
        $insS->execute([
            $uId,
            $s['enrollment'],
            $s['name'],
            $s['email'],
            $s['phone'],
            $s['gender'],
            $s['branch'],
            $s['semester'],
            $s['cpi'],
            $s['backlogs'],
            $s['batch_year'],
            $s['linkedin'],
            $s['offer_letter'],
            $s['placed_company'],
            $s['status']
        ]);
        $stId = $pdo->lastInsertId();
    } else {
        $updS = $pdo->prepare("UPDATE students SET user_id = ?, full_name = ?, email = ?, phone = ?, gender = ?, branch = ?, current_semester = ?, cpi = ?, backlogs = ?, batch_year = ?, linkedin_url = ?, offer_letter = ?, placed_companies = ?, placement_status = ?, is_detained = 0 WHERE id = ?");
        $updS->execute([
            $uId,
            $s['name'],
            $s['email'],
            $s['phone'],
            $s['gender'],
            $s['branch'],
            $s['semester'],
            $s['cpi'],
            $s['backlogs'],
            $s['batch_year'],
            $s['linkedin'],
            $s['offer_letter'],
            $s['placed_company'],
            $s['status'],
            $stId
        ]);
    }

    // C. Create Drive Applications
    if ($s['target_company'] && isset($driveMap[$s['target_company']])) {
        $drvId = $driveMap[$s['target_company']];
        $appStatus = ($s['status'] === 'Placed') ? 'Selected' : (($s['status'] === 'In-Process') ? 'Interview Round' : 'Applied');

        $stmtApp = $pdo->prepare("SELECT id FROM applications WHERE student_id = ? AND drive_id = ?");
        $stmtApp->execute([$stId, $drvId]);
        $appId = $stmtApp->fetchColumn();

        if (!$appId) {
            $pdo->prepare("INSERT INTO applications (student_id, drive_id, status) VALUES (?, ?, ?)")
                ->execute([$stId, $drvId, $appStatus]);
        } else {
            $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$appStatus, $appId]);
        }
    }

    // D. Seed Industrial Internship
    $stmtInt = $pdo->prepare("SELECT id FROM internships WHERE student_id = ?");
    $stmtInt->execute([$stId]);
    if (!$stmtInt->fetchColumn()) {
        $pdo->prepare("INSERT INTO internships (student_id, company_name, title, stipend, start_date, end_date, certificate_file, status, verification_status, credit_points) 
                       VALUES (?, ?, 'Full Stack Software Intern', '₹20,000 / month', '2025-01-05', '2025-06-30', 'certificate_sample.pdf', 'Completed', 'Verified', 4)")
            ->execute([$stId, ($s['target_company'] ?: 'TCS Innovate')]);
    }
}

echo "All 30 students across 2024, 2025, and 2026 successfully seeded!\n";
echo "=== SEEDING SUMMARY ===\n";
$res = $pdo->query("SELECT batch_year, COUNT(*) as cnt, SUM(CASE WHEN placement_status = 'Placed' THEN 1 ELSE 0 END) as placed FROM students GROUP BY batch_year ORDER BY batch_year DESC")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
