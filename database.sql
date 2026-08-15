-- Dr. Subhash University Placement Portal - Full Database Backup & Commitment
-- Generated on 2026-08-15 08:05:38
-- Database: dr_subhash_placement

SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `dr_subhash_placement` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dr_subhash_placement`;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('1', 'admin@drsubhash.edu.in', '$2y$10$Fae89Wsr6riUEgRMC27B4O6VRn4XsSxLjOEQBNeUlZ1Sx93cVs3wK', 'admin', '2026-08-15 11:31:49');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('2', '240130316041', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('3', '240130316042', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('4', '240130316043', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('5', '240130316044', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('6', '240130316045', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('7', '250130318041', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('8', '250130318042', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('9', '250130318043', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('10', '250130318044', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('11', '250130318045', '$2y$10$XkO.Zhav9p6YZIOPGKYJPuliAnZMA.wZoh.yyrHbohy6/ImtWIH8.', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('12', '260130316001', '$2y$10$gnNjLxSuoVJd6j8dxqr0SOV9zogHiWAcQouK7gXCDw1OSTk9t6JGK', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('13', '260130316002', '$2y$10$gnNjLxSuoVJd6j8dxqr0SOV9zogHiWAcQouK7gXCDw1OSTk9t6JGK', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('14', '260130316003', '$2y$10$gnNjLxSuoVJd6j8dxqr0SOV9zogHiWAcQouK7gXCDw1OSTk9t6JGK', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('15', '260130316004', '$2y$10$gnNjLxSuoVJd6j8dxqr0SOV9zogHiWAcQouK7gXCDw1OSTk9t6JGK', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('16', '260130316005', '$2y$10$gnNjLxSuoVJd6j8dxqr0SOV9zogHiWAcQouK7gXCDw1OSTk9t6JGK', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('17', '260130316006', '$2y$10$gnNjLxSuoVJd6j8dxqr0SOV9zogHiWAcQouK7gXCDw1OSTk9t6JGK', 'student', '2026-08-15 11:31:50');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES ('18', '260130316007', '$2y$10$gnNjLxSuoVJd6j8dxqr0SOV9zogHiWAcQouK7gXCDw1OSTk9t6JGK', 'student', '2026-08-15 11:31:50');

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `enrollment_no` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT 'Male',
  `branch` varchar(50) NOT NULL,
  `cpi` decimal(4,2) DEFAULT 0.00,
  `backlogs` int(11) DEFAULT 0,
  `batch_year` int(11) NOT NULL,
  `resume_file` varchar(255) DEFAULT 'default_resume.pdf',
  `profile_photo` varchar(255) DEFAULT 'default_avatar.png',
  `placement_status` enum('In-Process','Placed','Unplaced') DEFAULT 'Unplaced',
  `is_detained` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `offer_letter` varchar(255) DEFAULT NULL,
  `placed_companies` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollment_no` (`enrollment_no`),
  UNIQUE KEY `email` (`email`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('1', '2', '240130316041', 'Aarav Mehta', 'aarav.mehta@drsubhash.edu.in', '+91 98765 43210', 'Male', 'CSE', '9.12', '0', '2024', 'default_resume.pdf', 'default_avatar.png', 'Placed', '0', '2026-08-15 11:31:50', 'offer_letter_240130316041.pdf', 'Google India Pvt Ltd (₹24.5 LPA)');
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('2', '3', '240130316042', 'Priya Sharma', 'priya.sharma@drsubhash.edu.in', '+91 98765 43211', 'Female', 'IT', '8.85', '0', '2024', 'default_resume.pdf', 'default_avatar.png', 'Placed', '0', '2026-08-15 11:31:50', 'offer_letter_240130316042.pdf', 'TCS Digital & Innovations (₹9.0 LPA)');
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('3', '4', '240130316043', 'Rohan Patel', 'rohan.patel@drsubhash.edu.in', '+91 98765 43212', 'Male', 'ECE', '8.20', '0', '2024', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('4', '5', '240130316044', 'Ananya Verma', 'ananya.verma@drsubhash.edu.in', '+91 98765 43213', 'Female', 'MECH', '7.95', '0', '2024', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('5', '6', '240130316045', 'Vikram Joshi', 'vikram.joshi@drsubhash.edu.in', '+91 98765 43214', 'Male', 'CIVIL', '7.50', '1', '2024', 'default_resume.pdf', 'default_avatar.png', 'Unplaced', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('6', '7', '250130318041', 'Siddharth Rao', 'siddharth.rao@drsubhash.edu.in', '+91 98765 43215', 'Male', 'CSE', '9.40', '0', '2025', 'default_resume.pdf', 'default_avatar.png', 'Placed', '0', '2026-08-15 11:31:50', 'offer_letter_250130318041.pdf', 'Microsoft R&D India (₹28.0 LPA)');
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('7', '8', '250130318042', 'Neha Kapoor', 'neha.kapoor@drsubhash.edu.in', '+91 98765 43216', 'Female', 'IT', '8.60', '0', '2025', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('8', '9', '250130318043', 'Karan Gupta', 'karan.gupta@drsubhash.edu.in', '+91 98765 43217', 'Male', 'ECE', '8.10', '0', '2025', 'default_resume.pdf', 'default_avatar.png', 'In-Process', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('9', '10', '250130318044', 'Sneha Nair', 'sneha.nair@drsubhash.edu.in', '+91 98765 43218', 'Female', 'MECH', '7.80', '0', '2025', 'default_resume.pdf', 'default_avatar.png', 'Unplaced', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('10', '11', '250130318045', 'Devansh Shah', 'devansh.shah@drsubhash.edu.in', '+91 98765 43219', 'Male', 'CSE', '6.20', '2', '2025', 'default_resume.pdf', 'default_avatar.png', 'Unplaced', '1', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('11', '12', '260130316001', 'Aarav Patel', 'aarav.patel@drsubhash.edu.in', '+91 98765 11001', 'Male', 'CSE', '9.25', '0', '2026', 'resume_260130316001.pdf', 'default_avatar.png', 'Placed', '0', '2026-08-15 11:31:50', 'offer_letter_260130316001.pdf', 'Infosys Power Programmer (₹12.5 LPA)');
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('12', '13', '260130316002', 'Diya Sharma', 'diya.sharma@drsubhash.edu.in', '+91 98765 11002', 'Female', 'IT', '8.90', '0', '2026', 'resume_260130316002.pdf', 'default_avatar.png', 'Placed', '0', '2026-08-15 11:31:50', 'offer_letter_260130316002.pdf', 'Wipro Turbo Engineering (₹8.5 LPA)');
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('13', '14', '260130316003', 'Kabir Mehta', 'kabir.mehta@drsubhash.edu.in', '+91 98765 11003', 'Male', 'ECE', '8.45', '0', '2026', 'resume_260130316003.pdf', 'default_avatar.png', 'In-Process', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('14', '15', '260130316004', 'Anaya Joshi', 'anaya.joshi@drsubhash.edu.in', '+91 98765 11004', 'Female', 'MECH', '8.10', '0', '2026', 'resume_260130316004.pdf', 'default_avatar.png', 'In-Process', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('15', '16', '260130316005', 'Rudra Shah', 'rudra.shah@drsubhash.edu.in', '+91 98765 11005', 'Male', 'CIVIL', '7.80', '0', '2026', 'resume_260130316005.pdf', 'default_avatar.png', 'Unplaced', '0', '2026-08-15 11:31:50', NULL, NULL);
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('16', '17', '260130316006', 'Isha Trivedi', 'isha.trivedi@drsubhash.edu.in', '+91 98765 11006', 'Female', 'CSE', '9.50', '0', '2026', 'resume_260130316006.pdf', 'default_avatar.png', 'Placed', '0', '2026-08-15 11:31:50', 'offer_letter_260130316006.pdf', 'Google India Pvt Ltd (₹24.5 LPA)');
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `gender`, `branch`, `cpi`, `backlogs`, `batch_year`, `resume_file`, `profile_photo`, `placement_status`, `is_detained`, `created_at`, `offer_letter`, `placed_companies`) VALUES ('17', '18', '260130316007', 'Vivaan Kulkarni', 'vivaan.kulkarni@drsubhash.edu.in', '+91 98765 11007', 'Male', 'IT', '8.65', '0', '2026', 'resume_260130316007.pdf', 'default_avatar.png', 'In-Process', '0', '2026-08-15 11:31:50', NULL, NULL);

DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) NOT NULL,
  `hr_name` varchar(100) DEFAULT NULL,
  `hr_email` varchar(100) DEFAULT NULL,
  `industry` varchar(100) DEFAULT 'IT & Software',
  `website` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT 'default_company.png',
  `is_closed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_name` (`company_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('1', 'Google India Pvt Ltd', 'Rajesh Sharma', 'careers-india@google.com', 'Cloud & Artificial Intelligence', 'https://careers.google.com', '+91 80 6721 0000', 'default_company.png', '0', '2026-08-15 11:31:50');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('2', 'Microsoft India', 'Kavita Reddy', 'recruitment@microsoft.com', 'Software & Enterprise Systems', 'https://careers.microsoft.com', '+91 40 6608 0000', 'default_company.png', '0', '2026-08-15 11:31:50');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('3', 'Amazon Web Services (AWS)', 'Amitabh Varma', 'aws-campus@amazon.com', 'Cloud Infrastructure & DevOps', 'https://amazon.jobs', '+91 80 4184 0000', 'default_company.png', '0', '2026-08-15 11:31:50');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('4', 'Tata Consultancy Services (TCS)', 'Sanjay Kulkarni', 'campus.careers@tcs.com', 'IT Consulting & Digital Solutions', 'https://tcs.com/careers', '+91 22 6778 9999', 'default_company.png', '0', '2026-08-15 11:31:50');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('5', 'Infosys Limited', 'Deepika Iyer', 'talent.acquisition@infosys.com', 'IT Services & Automation', 'https://infosys.com/careers', '+91 80 2852 0261', 'default_company.png', '0', '2026-08-15 11:31:50');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('6', 'Wipro Technologies', 'Vikram Singhania', 'campus.hiring@wipro.com', 'Cybersecurity & Enterprise Cloud', 'https://wipro.com/careers', '+91 80 2844 0011', 'default_company.png', '0', '2026-08-15 11:31:50');
INSERT INTO `companies` (`id`, `company_name`, `hr_name`, `hr_email`, `industry`, `website`, `phone`, `logo`, `is_closed`, `created_at`) VALUES ('7', 'Larsen & Toubro (L&T)', 'Meenal Deshmukh', 'hr@larsentoubro.com', 'Infrastructure & Heavy Engineering', 'https://larsentoubro.com', '+91 22 6752 5656', 'default_company.png', '0', '2026-08-15 11:31:50');

DROP TABLE IF EXISTS `drives`;
CREATE TABLE `drives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `designation` varchar(150) NOT NULL,
  `package_ctc` varchar(50) NOT NULL,
  `location` varchar(100) DEFAULT 'Pan India / Remote',
  `min_cpi` decimal(4,2) DEFAULT 6.50,
  `allowed_branches` varchar(255) DEFAULT 'CSE, IT, ECE, MECH, CIVIL',
  `max_backlogs` int(11) DEFAULT 0,
  `drive_date` date NOT NULL,
  `deadline` date NOT NULL,
  `deadline_time` varchar(20) DEFAULT '18:00',
  `description` text DEFAULT NULL,
  `status` enum('Upcoming','Active','Completed','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `drives_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('1', '1', 'Software Development Engineer - SDE-1', 'Software Engineer I', '₹28.50 LPA', 'Bengaluru / Hyderabad', '8.50', 'CSE, IT', '0', '2026-08-20', '2026-08-15', '18:00', 'High-impact engineering drive for cloud architecture, distributed systems, and LLM AI systems development.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('2', '2', 'Azure Cloud & DevOps Architect', 'Cloud Solutions Engineer', '₹24.00 LPA', 'Hyderabad / Remote', '8.00', 'CSE, IT, ECE', '0', '2026-08-25', '2026-08-18', '18:00', 'Designing resilient cloud infrastructure, microservices, and automated CI/CD pipeline deployment.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('3', '3', 'Systems Engineer - Infrastructure & Networks', 'Systems Development Engineer', '₹32.00 LPA', 'Bengaluru / Pune', '8.50', 'CSE, IT', '0', '2026-08-28', '2026-08-22', '18:00', 'Operating hyper-scale cloud server clusters, containerized Kubernetes, and network security protocols.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('4', '4', 'TCS Digital Engineer & Innovator', 'System Engineer Specialist', '₹8.50 LPA', 'Gandhinagar / Pune / Pan India', '7.00', 'ALL', '1', '2026-09-02', '2026-08-26', '18:00', 'Digital transformation drive across Java Spring Boot, React enterprise applications, and cloud data analytics.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('5', '5', 'Power Programmer & Systems Associate', 'Senior Systems Engineer', '₹9.50 LPA', 'Pune / Bengaluru', '7.50', 'CSE, IT, ECE', '0', '2026-06-10', '2026-06-05', '18:00', 'Enterprise application development and cloud migration drive.', 'Completed', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('6', '7', 'Graduate Engineer Trainee - Mechanical & Civil', 'Graduate Engineer Trainee', '₹7.20 LPA', 'Vadodara / Mumbai', '6.50', 'MECH, CIVIL', '1', '2026-09-10', '2026-09-01', '18:00', 'Industrial project execution, CAD design, structural engineering, and heavy plant operations.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('7', '1', '2026 Cloud & AI Solutions Engineer', '2026 Cloud & AI Solutions Engineer', '₹24.5 LPA', 'Bengaluru / Hybrid', '8.00', 'CSE, IT, ECE', '0', '2026-08-25', '2026-08-20', '18:00', 'Official 2026 Campus Drive for Dr. Subhash University candidates.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('8', '2', '2026 Enterprise Systems Developer', '2026 Enterprise Systems Developer', '₹22.0 LPA', 'Hyderabad / Remote', '8.00', 'CSE, IT', '0', '2026-09-10', '2026-09-05', '18:00', 'Official 2026 Campus Drive for Dr. Subhash University candidates.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('9', '3', '2026 DevOps & Infrastructure Trainee', '2026 DevOps & Infrastructure Trainee', '₹18.0 LPA', 'Gurugram / Onsite', '7.50', 'CSE, IT, ECE, MECH', '0', '2026-08-30', '2026-08-28', '18:00', 'Official 2026 Campus Drive for Dr. Subhash University candidates.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('10', '4', '2026 Digital Innovator & Software Analyst', '2026 Digital Innovator & Software Analyst', '₹9.0 LPA', 'Ahmedabad / Vadodara', '6.50', 'ALL', '0', '2026-09-15', '2026-09-12', '18:00', 'Official 2026 Campus Drive for Dr. Subhash University candidates.', 'Active', '2026-08-15 11:31:50');
INSERT INTO `drives` (`id`, `company_id`, `title`, `designation`, `package_ctc`, `location`, `min_cpi`, `allowed_branches`, `max_backlogs`, `drive_date`, `deadline`, `deadline_time`, `description`, `status`, `created_at`) VALUES ('11', '7', '2026 Graduate Engineering Trainee (GET)', '2026 Graduate Engineering Trainee (GET)', '₹8.5 LPA', 'Mumbai / Hazira', '6.50', 'MECH, CIVIL, ECE', '0', '2026-09-20', '2026-09-18', '18:00', 'Official 2026 Campus Drive for Dr. Subhash University candidates.', 'Active', '2026-08-15 11:31:50');

DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `drive_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('Applied','Shortlisted','Interview Round','Selected','Rejected') DEFAULT 'Applied',
  `remarks` text DEFAULT NULL,
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_app` (`drive_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`drive_id`) REFERENCES `drives` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('1', '1', '1', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('2', '2', '2', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('3', '3', '6', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('4', '4', '3', 'Shortlisted', 'Cleared online coding assessment; interview scheduled.', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('5', '4', '7', 'Interview Round', 'Technical interview in progress.', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('6', '6', '4', 'Applied', 'Application submitted with profile resume.', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('7', '4', '8', 'Applied', 'Application under review.', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('8', '5', '5', 'Rejected', 'CPI criteria below required threshold.', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('9', '7', '11', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('10', '7', '12', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('11', '7', '16', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('12', '7', '13', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('13', '7', '14', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('14', '7', '17', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('15', '8', '11', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('16', '8', '12', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('17', '8', '16', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('18', '8', '13', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('19', '8', '14', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('20', '8', '17', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('21', '9', '11', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('22', '9', '12', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('23', '9', '16', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('24', '9', '13', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('25', '9', '14', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('26', '9', '17', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('27', '10', '11', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('28', '10', '12', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('29', '10', '16', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('30', '10', '13', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('31', '10', '14', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('32', '10', '17', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('33', '11', '11', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('34', '11', '12', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('35', '11', '16', 'Selected', 'Offer Letter Generated & Verified', '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('36', '11', '13', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('37', '11', '14', 'Shortlisted', NULL, '2026-08-15 11:31:50');
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `status`, `remarks`, `applied_date`) VALUES ('38', '11', '17', 'Shortlisted', NULL, '2026-08-15 11:31:50');

DROP TABLE IF EXISTS `workshops`;
CREATE TABLE `workshops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `speaker` varchar(150) NOT NULL,
  `event_date` datetime NOT NULL,
  `venue` varchar(150) NOT NULL,
  `capacity` int(11) DEFAULT 100,
  `description` text DEFAULT NULL,
  `status` enum('Upcoming','Completed','Cancelled') DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `report_file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `workshops` (`id`, `title`, `speaker`, `event_date`, `venue`, `capacity`, `description`, `status`, `created_at`, `report_file`) VALUES ('1', 'AI & Generative LLM Architecture Masterclass', 'Dr. Anirudh Kulkarni (Lead AI Architect, Google AI)', '2026-08-22 10:00:00', 'Main Auditorium, Dr. Subhash University', '150', 'Hands-on keynote workshop covering PyTorch, Transformer Neural Networks, Prompt Engineering, and RAG vector databases.', 'Upcoming', '2026-08-15 11:31:50', NULL);
INSERT INTO `workshops` (`id`, `title`, `speaker`, `event_date`, `venue`, `capacity`, `description`, `status`, `created_at`, `report_file`) VALUES ('2', 'Full-Stack Cloud Microservices & DevOps Bootcamp', 'Er. Rajesh Varma (Principal Architect, Microsoft)', '2026-08-29 11:00:00', 'Seminar Hall 3, Engineering Block', '120', 'Building microservices with Node.js, Docker containers, Kubernetes orchestrations, and Azure deployment.', 'Upcoming', '2026-08-15 11:31:50', NULL);
INSERT INTO `workshops` (`id`, `title`, `speaker`, `event_date`, `venue`, `capacity`, `description`, `status`, `created_at`, `report_file`) VALUES ('3', 'Cybersecurity & Ethical Hacking Intensive', 'Er. Megha Trivedi (Cybersecurity Lead, Wipro)', '2026-09-05 09:30:00', 'Center for Excellence Computing Lab 1', '100', 'Penetration testing, network security audits, vulnerability assessment, and OWASP top 10 security practices.', 'Upcoming', '2026-08-15 11:31:50', NULL);

DROP TABLE IF EXISTS `workshop_registrations`;
CREATE TABLE `workshop_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workshop_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attendance_status` enum('Registered','Attended','Absent') DEFAULT 'Registered',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wk_reg` (`workshop_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `workshop_registrations_ibfk_1` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workshop_registrations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `workshop_registrations` (`id`, `workshop_id`, `student_id`, `registered_at`, `attendance_status`) VALUES ('1', '1', '1', '2026-08-15 11:31:50', 'Attended');
INSERT INTO `workshop_registrations` (`id`, `workshop_id`, `student_id`, `registered_at`, `attendance_status`) VALUES ('2', '1', '2', '2026-08-15 11:31:50', 'Attended');
INSERT INTO `workshop_registrations` (`id`, `workshop_id`, `student_id`, `registered_at`, `attendance_status`) VALUES ('3', '2', '3', '2026-08-15 11:31:50', 'Registered');
INSERT INTO `workshop_registrations` (`id`, `workshop_id`, `student_id`, `registered_at`, `attendance_status`) VALUES ('4', '1', '6', '2026-08-15 11:31:50', 'Attended');
INSERT INTO `workshop_registrations` (`id`, `workshop_id`, `student_id`, `registered_at`, `attendance_status`) VALUES ('5', '2', '7', '2026-08-15 11:31:50', 'Registered');
INSERT INTO `workshop_registrations` (`id`, `workshop_id`, `student_id`, `registered_at`, `attendance_status`) VALUES ('6', '3', '8', '2026-08-15 11:31:50', 'Registered');

DROP TABLE IF EXISTS `internships`;
CREATE TABLE `internships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `title` varchar(150) NOT NULL,
  `stipend` varchar(50) DEFAULT '₹15,000 / month',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `certificate_file` varchar(255) DEFAULT 'certificate_sample.pdf',
  `status` enum('Ongoing','Completed') DEFAULT 'Completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `internships_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `internships` (`id`, `student_id`, `company_name`, `title`, `stipend`, `start_date`, `end_date`, `certificate_file`, `status`, `created_at`) VALUES ('1', '1', 'Google India', 'Software Engineering Intern', '₹45,000 / month', '2026-01-10', '2026-06-30', 'certificate_sample.pdf', 'Completed', '2026-08-15 11:31:50');
INSERT INTO `internships` (`id`, `student_id`, `company_name`, `title`, `stipend`, `start_date`, `end_date`, `certificate_file`, `status`, `created_at`) VALUES ('2', '2', 'Microsoft India', 'Cloud Solutions Intern', '₹40,000 / month', '2026-01-15', '2026-06-30', 'certificate_sample.pdf', 'Completed', '2026-08-15 11:31:50');
INSERT INTO `internships` (`id`, `student_id`, `company_name`, `title`, `stipend`, `start_date`, `end_date`, `certificate_file`, `status`, `created_at`) VALUES ('3', '3', 'TCS Digital', 'Full-Stack Developer Intern', '₹20,000 / month', '2026-02-01', '2026-06-15', 'certificate_sample.pdf', 'Completed', '2026-08-15 11:31:50');
INSERT INTO `internships` (`id`, `student_id`, `company_name`, `title`, `stipend`, `start_date`, `end_date`, `certificate_file`, `status`, `created_at`) VALUES ('4', '6', 'Amazon Web Services', 'Cloud Infrastructure Intern', '₹50,000 / month', '2026-01-05', '2026-06-30', 'certificate_sample.pdf', 'Completed', '2026-08-15 11:31:50');

DROP TABLE IF EXISTS `mou_agreements`;
CREATE TABLE `mou_agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) NOT NULL,
  `signing_date` date NOT NULL,
  `validity_years` int(11) NOT NULL DEFAULT 3,
  `expiry_date` date NOT NULL,
  `training_goals` text DEFAULT NULL,
  `mou_doc_file` varchar(255) DEFAULT 'mou_sample.pdf',
  `status` enum('Active','Expired','Pending') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `mou_agreements` (`id`, `company_name`, `signing_date`, `validity_years`, `expiry_date`, `training_goals`, `mou_doc_file`, `status`, `created_at`) VALUES ('1', 'Google India Pvt Ltd', '2025-01-15', '5', '2030-01-15', 'Cloud computing labs, AI/ML workshops, student internships, and direct campus recruitment.', 'mou_sample.pdf', 'Active', '2026-08-15 11:31:50');
INSERT INTO `mou_agreements` (`id`, `company_name`, `signing_date`, `validity_years`, `expiry_date`, `training_goals`, `mou_doc_file`, `status`, `created_at`) VALUES ('2', 'Microsoft India', '2024-06-01', '3', '2027-06-01', 'Azure certification center, cloud developer bootcamps, and executive mentoring.', 'mou_sample.pdf', 'Active', '2026-08-15 11:31:50');
INSERT INTO `mou_agreements` (`id`, `company_name`, `signing_date`, `validity_years`, `expiry_date`, `training_goals`, `mou_doc_file`, `status`, `created_at`) VALUES ('3', 'Tata Consultancy Services (TCS)', '2023-08-10', '4', '2027-08-10', 'TCS iON digital learning integration, faculty development programs, and campus placement drives.', 'mou_sample.pdf', 'Active', '2026-08-15 11:31:50');
INSERT INTO `mou_agreements` (`id`, `company_name`, `signing_date`, `validity_years`, `expiry_date`, `training_goals`, `mou_doc_file`, `status`, `created_at`) VALUES ('4', 'Larsen & Toubro (L&T)', '2024-03-20', '3', '2027-03-20', 'Mechanical & Civil engineering practical training, site visits, and industrial internships.', 'mou_sample.pdf', 'Active', '2026-08-15 11:31:50');

DROP TABLE IF EXISTS `mous`;
CREATE TABLE `mous` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) NOT NULL,
  `signed_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `scope` text DEFAULT NULL,
  `status` enum('Active','Expired','Pending') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mail_logs`;
CREATE TABLE `mail_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'sent',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
